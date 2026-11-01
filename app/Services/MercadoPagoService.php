<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Suscripcion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Integración directa con la API REST de Mercado Pago (sin SDK de terceros),
 * para no depender de instalar paquetes vía Composer/Packagist.
 *
 * Docs: https://www.mercadopago.com.ar/developers/es/reference
 */
class MercadoPagoService
{
    protected string $baseUrl = 'https://api.mercadopago.com';

    protected function http()
    {
        return Http::withToken(config('mercadopago.access_token'))
            ->acceptJson()
            ->asJson();
    }

    /**
     * Pago único (Preference / Checkout Pro). Sirve tanto para suscripciones
     * "unico" como para el primer cobro manual de una recurrente.
     */
    public function crearPreferencia(Suscripcion $suscripcion): array
    {
        $clienteSistema = $suscripcion->clienteSistema;
        $cliente = $clienteSistema->cliente;
        $sistema = $clienteSistema->sistema;

        $response = $this->http()->post("{$this->baseUrl}/checkout/preferences", [
            'items' => [[
                'title' => $this->titulo($suscripcion, $sistema->nombre),
                'quantity' => 1,
                'currency_id' => $suscripcion->moneda,
                'unit_price' => (float) $suscripcion->monto,
            ]],
            'payer' => [
                'name' => $cliente->nombre,
                'email' => $cliente->email,
            ],
            'external_reference' => (string) $suscripcion->id_suscripcion,
            'back_urls' => config('mercadopago.back_urls'),
            'auto_return' => 'approved',
            'notification_url' => config('mercadopago.notification_url'),
        ]);

        $response->throw();

        return $response->json();
    }

    /**
     * Suscripción recurrente (Preapproval). Requiere que el cliente autorice
     * el débito automático desde el init_point que devuelve MP.
     */
    public function crearPreapproval(Suscripcion $suscripcion): array
    {
        $clienteSistema = $suscripcion->clienteSistema;
        $cliente = $clienteSistema->cliente;
        $sistema = $clienteSistema->sistema;

        $response = $this->http()->post("{$this->baseUrl}/preapproval", [
            'reason' => $this->titulo($suscripcion, $sistema->nombre),
            'external_reference' => (string) $suscripcion->id_suscripcion,
            'payer_email' => $cliente->email,
            'auto_recurring' => [
                'frequency' => max(1, (int) ($suscripcion->planCatalogo?->duracion_meses ?? 1)),
                'frequency_type' => 'months',
                'transaction_amount' => (float) $suscripcion->monto,
                'currency_id' => $suscripcion->moneda,
            ],
            'back_url' => config('mercadopago.back_urls.success'),
            'notification_url' => config('mercadopago.notification_url'),
            'status' => 'pending',
        ]);

        $response->throw();

        return $response->json();
    }

    protected function titulo(Suscripcion $suscripcion, string $sistema): string
    {
        $titulo = "{$sistema} - {$suscripcion->plan}";
        $plan = $suscripcion->planCatalogo;

        if ($plan && $plan->cobro_por_unidad && $suscripcion->cantidad > 1) {
            $titulo .= " ({$suscripcion->cantidad} ".($plan->unidad_nombre ?: 'unidades').')';
        }

        return $titulo;
    }

    /**
     * Pago único de un Pago concreto (el que ya tiene meses, cantidad y monto definidos).
     * external_reference = "pago-{id}": el webhook lo reconoce y aplica exactamente ese pago.
     */
    public function crearPreferenciaPago(Pago $pago, string $titulo, ?string $email): array
    {
        $datos = [
            'items' => [[
                'title' => $titulo,
                'quantity' => 1,
                'currency_id' => $pago->moneda,
                'unit_price' => round((float) $pago->monto, 2),
            ]],
            'external_reference' => 'pago-'.$pago->id_pago,
            'back_urls' => config('mercadopago.back_urls'),
            'auto_return' => 'approved',
            'notification_url' => config('mercadopago.notification_url'),
        ];

        if ($email) {
            $datos['payer'] = ['email' => $email];
        }

        $response = $this->http()->post("{$this->baseUrl}/checkout/preferences", $datos);
        $response->throw();

        return $response->json();
    }

    public function obtenerPago(string $paymentId): array
    {
        $response = $this->http()->get("{$this->baseUrl}/v1/payments/{$paymentId}");
        $response->throw();

        return $response->json();
    }

    public function obtenerPreapproval(string $preapprovalId): array
    {
        $response = $this->http()->get("{$this->baseUrl}/preapproval/{$preapprovalId}");
        $response->throw();

        return $response->json();
    }

    /**
     * Valida la firma "x-signature" que Mercado Pago envía en cada webhook.
     * https://www.mercadopago.com.ar/developers/es/docs/checkout-api/webhooks#validar-el-origen-de-la-notificacion
     */
    public function validarFirmaWebhook(string $xSignature, string $xRequestId, string $dataId): bool
    {
        $partes = collect(explode(',', $xSignature))
            ->mapWithKeys(function ($parte) {
                [$clave, $valor] = array_pad(explode('=', trim($parte), 2), 2, null);

                return [$clave => $valor];
            });

        $ts = $partes->get('ts');
        $hashRecibido = $partes->get('v1');

        if (! $ts || ! $hashRecibido) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $hashCalculado = hash_hmac('sha256', $manifest, config('mercadopago.webhook_secret'));

        return Str::of($hashCalculado)->lower()->exactly(Str::lower($hashRecibido));
    }
}
