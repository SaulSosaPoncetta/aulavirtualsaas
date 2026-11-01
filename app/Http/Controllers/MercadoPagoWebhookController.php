<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Suscripcion;
use App\Services\AplicadorPagos;
use App\Services\MercadoPagoService;
use App\Services\NotificadorSistemaSatelite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoService $mercadoPago,
        NotificadorSistemaSatelite $notificador,
        AplicadorPagos $aplicador
    ) {
        $dataId = $request->query('data.id') ?? $request->input('data.id');
        $tipo = $request->query('type') ?? $request->input('type');

        $xSignature = $request->header('x-signature', '');
        $xRequestId = $request->header('x-request-id', '');

        if ($dataId && ! $mercadoPago->validarFirmaWebhook($xSignature, $xRequestId, (string) $dataId)) {
            Log::warning('Webhook de Mercado Pago con firma inválida', ['data_id' => $dataId]);

            return response()->json(['error' => 'firma inválida'], 401);
        }

        match ($tipo) {
            'payment' => $this->procesarPago((string) $dataId, $mercadoPago, $aplicador),
            'subscription_preapproval', 'preapproval' => $this->procesarPreapproval((string) $dataId, $mercadoPago, $notificador),
            default => Log::info('Webhook de Mercado Pago sin manejar', ['type' => $tipo]),
        };

        // Mercado Pago solo necesita un 200 para no reintentar
        return response()->json(['ok' => true]);
    }

    protected function procesarPago(string $paymentId, MercadoPagoService $mercadoPago, AplicadorPagos $aplicador): void
    {
        $datosPago = $mercadoPago->obtenerPago($paymentId);
        $referencia = (string) ($datosPago['external_reference'] ?? '');

        // Pagos creados desde la página de pago del Panel: "pago-{id}"
        if (str_starts_with($referencia, 'pago-')) {
            $this->procesarPagoDirecto((int) substr($referencia, 5), $paymentId, $datosPago, $aplicador);

            return;
        }

        // Cobros del débito automático (preapproval) y links viejos: referencia = id de la suscripción
        $suscripcion = Suscripcion::find($referencia ?: null);

        if (! $suscripcion) {
            Log::warning('Pago de MP sin suscripción asociada', ['payment_id' => $paymentId]);

            return;
        }

        $estadoPago = $this->estadoLocal($datosPago['status'] ?? null);

        $pago = Pago::updateOrCreate(
            ['mp_payment_id' => $paymentId],
            [
                'id_suscripcion' => $suscripcion->id_suscripcion,
                'monto' => $datosPago['transaction_amount'] ?? $suscripcion->monto,
                'moneda' => $datosPago['currency_id'] ?? $suscripcion->moneda,
                'estado' => $estadoPago,
                'fecha_pago' => $datosPago['date_approved'] ?? now(),
                'payload_raw' => $datosPago,
            ]
        );

        // Mercado Pago puede avisar varias veces del mismo pago: el vencimiento se extiende
        // solo la primera vez que el pago pasa a "aprobado".
        $recienAprobado = $estadoPago === 'aprobado' && ($pago->wasRecentlyCreated || $pago->wasChanged('estado'));

        if ($recienAprobado) {
            $aplicador->acreditar($suscripcion);
        } elseif ($estadoPago === 'reembolsado') {
            Log::warning('Pago reembolsado o contracargado en Mercado Pago: revisalo a mano', [
                'payment_id' => $paymentId,
                'id_suscripcion' => $suscripcion->id_suscripcion,
            ]);
        }
        // Un pago rechazado no cambia el estado: el servicio sigue hasta su fecha de vencimiento.
    }

    protected function procesarPagoDirecto(int $idPago, string $paymentId, array $datosPago, AplicadorPagos $aplicador): void
    {
        $estadoPago = $this->estadoLocal($datosPago['status'] ?? null);
        $aprobar = false;

        $pago = DB::transaction(function () use ($idPago, $paymentId, $datosPago, $estadoPago, &$aprobar) {
            $pago = Pago::lockForUpdate()->find($idPago);

            if (! $pago) {
                return null;
            }

            if ($pago->estado === 'aprobado') {
                return $pago; // ya estaba acreditado (aviso repetido)
            }

            $pago->mp_payment_id = $paymentId;
            $pago->payload_raw = $datosPago;

            if ($estadoPago === 'aprobado') {
                $cobrado = (float) ($datosPago['transaction_amount'] ?? 0);

                if ($cobrado + 1 < (float) $pago->monto) {
                    Log::warning('Mercado Pago: importe menor al esperado, revisalo a mano', [
                        'id_pago' => $pago->id_pago, 'cobrado' => $cobrado, 'esperado' => $pago->monto,
                    ]);
                    $pago->nota_cliente = trim($pago->nota_cliente."\nMP cobró $cobrado y se esperaban {$pago->monto}: revisar.");
                } else {
                    $pago->estado = 'aprobado';
                    $pago->fecha_pago = $datosPago['date_approved'] ?? now();
                    $aprobar = true;
                }
            } elseif (in_array($estadoPago, ['rechazado', 'reembolsado'], true)) {
                $pago->estado = $estadoPago;
            }

            $pago->save();

            return $pago;
        });

        if (! $pago) {
            Log::warning('Pago de MP con referencia desconocida', ['payment_id' => $paymentId, 'id_pago' => $idPago]);

            return;
        }

        // Fuera de la transacción: calcula el vencimiento y avisa al sistema (llamada HTTP).
        if ($aprobar) {
            $aplicador->acreditar($pago->suscripcion, $pago);
        }
    }

    protected function estadoLocal(?string $estadoMp): string
    {
        return match ($estadoMp) {
            'approved' => 'aprobado',
            'rejected', 'cancelled' => 'rechazado',
            'refunded', 'charged_back' => 'reembolsado',
            default => 'pendiente',
        };
    }

    protected function procesarPreapproval(string $preapprovalId, MercadoPagoService $mercadoPago, NotificadorSistemaSatelite $notificador): void
    {
        $datos = $mercadoPago->obtenerPreapproval($preapprovalId);
        $suscripcion = Suscripcion::where('mp_preapproval_id', $preapprovalId)->first();

        if (! $suscripcion) {
            Log::warning('Preapproval de MP sin suscripción asociada', ['preapproval_id' => $preapprovalId]);

            return;
        }

        // "authorized" no activa nada: el servicio se activa cuando entra el pago (ahí se calcula el vencimiento).
        $nuevoEstado = match ($datos['status'] ?? null) {
            'paused' => 'suspendida',
            'cancelled' => 'cancelada',
            default => null,
        };

        if ($nuevoEstado && $nuevoEstado !== $suscripcion->estado) {
            $suscripcion->update(['estado' => $nuevoEstado]);
            $notificador->avisarSuscripcion($suscripcion->fresh());
        }
    }
}
