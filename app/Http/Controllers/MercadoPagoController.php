<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Institucion;
use App\Models\Pago;
use App\Services\AbonoService;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MercadoPagoController extends Controller
{
    public function __construct(protected AbonoService $precios, protected MercadoPagoService $mp)
    {
    }

    /** Docente: pagar el abono unipersonal con Mercado Pago. */
    public function pagarMio(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esDocente(), 403);
        abort_unless(MercadoPagoService::habilitado(), 404);

        $datos = $request->validate([
            'meses' => 'required|integer|in:' . implode(',', $this->precios->opcionesMeses()),
        ]);

        $abono   = Abono::deUsuario($user);
        $importe = $this->precios->cotizarRenovacion($abono, (int) $datos['meses']);
        abort_if($importe === null, 422, 'No hay precio cargado para el abono unipersonal.');

        $pago = $this->crearPago($abono, $user->id, [
            'concepto' => 'renovacion',
            'meses'    => (int) $datos['meses'],
            'importe'  => $importe,
        ]);

        return $this->irAMercadoPago($pago, 'Abono Aula Virtual — ' . $pago->detalle, $user->email, route('abonos.mio'));
    }

    /** Institución: renovar o ampliar con Mercado Pago. */
    public function pagarInstitucion(Request $request, Institucion $institucion)
    {
        $user = $request->user();
        abort_unless($institucion->esAdmin($user), 403);
        abort_unless(MercadoPagoService::habilitado(), 404);

        $concepto = $request->input('concepto') === 'ampliacion' ? 'ampliacion' : 'renovacion';

        $datos = $request->validate([
            'cupo_docentes' => 'required|integer|min:1|max:' . config('abonos.cupo_maximo', 500),
            'meses'         => 'required_unless:concepto,ampliacion|nullable|integer|in:' . implode(',', $this->precios->opcionesMeses()),
        ], [], ['cupo_docentes' => 'cantidad de docentes']);

        $abono  = Abono::deInstitucion($institucion);
        $usados = $abono->docentesUsados();
        $cupo   = (int) $datos['cupo_docentes'];

        if ($cupo < $usados) {
            return back()->withInput()->with('error', "Ya hay {$usados} docentes en la institución: el cupo no puede ser menor.");
        }

        if ($concepto === 'ampliacion') {
            $importe = $this->precios->cotizarAmpliacion($abono, $cupo);
            if ($importe === null) {
                return back()->withInput()->with('error', 'Para ampliar, el abono tiene que estar activo y el nuevo cupo ser mayor al actual.');
            }
            $meses = 0;
        } else {
            $meses   = (int) $datos['meses'];
            $importe = $this->precios->cotizarRenovacion($abono, $meses, $cupo);
            abort_if($importe === null, 422, 'No hay una escala de precios para esa cantidad de docentes.');
        }

        $pago = $this->crearPago($abono, $user->id, [
            'concepto'      => $concepto,
            'meses'         => $meses,
            'cupo_docentes' => $cupo,
            'importe'       => $importe,
        ]);

        return $this->irAMercadoPago(
            $pago,
            'Abono institucional Aula Virtual — ' . Str::limit($institucion->nombre, 40),
            $institucion->email ?? $user->email,
            route('abonos.institucion', $institucion)
        );
    }

    /** Vuelta desde Mercado Pago (éxito, pendiente o error). */
    public function retorno(Request $request)
    {
        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');
        $pago      = null;

        if ($paymentId && $paymentId !== 'null') {
            try {
                $pago = $this->mp->procesarPago((string) $paymentId);
            } catch (Throwable $e) {
                Log::error('Mercado Pago: error en el retorno', ['error' => $e->getMessage()]);
            }
        }

        // Si no vino el id del pago (abandonó), se busca por la referencia
        if (! $pago && Str::startsWith((string) $request->query('external_reference'), 'pago-')) {
            $pago = Pago::find((int) Str::after($request->query('external_reference'), 'pago-'));

            if ($pago && $pago->estado === 'pendiente' && $request->query('status') === 'null') {
                $pago->update(['estado' => 'cancelado']);
            }
        }

        $destino = $this->paginaAbono($pago);

        if (! $pago) {
            return redirect($destino)->with('error', 'No pudimos identificar el pago. Si se debitó, se va a acreditar en unos minutos.');
        }

        return match ($pago->estado) {
            'aprobado'  => redirect($destino)->with('exito', '¡Pago acreditado! Tu abono ya está activo hasta el ' . $pago->abono->fresh()->vence_el?->format('d/m/Y') . '.'),
            'rechazado' => redirect($destino)->with('error', 'Mercado Pago rechazó el pago. Podés intentar con otro medio.'),
            'cancelado' => redirect($destino)->with('error', 'El pago no se completó.'),
            default     => redirect($destino)->with('exito', 'El pago quedó pendiente en Mercado Pago (por ejemplo, efectivo en Rapipago o Pago Fácil). Se acredita solo cuando lo pagues.'),
        };
    }

    /** Notificación de Mercado Pago (webhook). Siempre responde 200 para que no reintente sin sentido. */
    public function webhook(Request $request)
    {
        if (! $this->mp->firmaValida($request)) {
            Log::warning('Mercado Pago: webhook con firma inválida', ['ip' => $request->ip()]);

            return response()->json(['ok' => false], 401);
        }

        $tipo = $request->input('type') ?? $request->query('type') ?? $request->query('topic');
        $id   = $request->input('data.id') ?? $request->query('data_id') ?? $request->query('id');

        if ($tipo === 'payment' && $id) {
            try {
                $this->mp->procesarPago((string) $id);
            } catch (Throwable $e) {
                Log::error('Mercado Pago: error procesando webhook', ['id' => $id, 'error' => $e->getMessage()]);

                return response()->json(['ok' => false], 500); // MP reintenta más tarde
            }
        }

        return response()->json(['ok' => true]);
    }

    // ===================== Ayudas =====================

    protected function crearPago(Abono $abono, int $userId, array $datos): Pago
    {
        // Cancela intentos anteriores de Mercado Pago que quedaron sin pagar
        $abono->pagos()->where('metodo', 'mercadopago')->where('estado', 'pendiente')->update(['estado' => 'cancelado']);

        return $abono->pagos()->create($datos + [
            'metodo'     => 'mercadopago',
            'estado'     => 'pendiente',
            'creado_por' => $userId,
        ]);
    }

    protected function irAMercadoPago(Pago $pago, string $titulo, ?string $email, string $volver)
    {
        try {
            $url = $this->mp->crearPreferencia($pago, $titulo, $email);
        } catch (Throwable $e) {
            $pago->update(['estado' => 'cancelado']);

            return redirect($volver)->with('error', $e->getMessage() . ' Probá de nuevo o pagá por transferencia.');
        }

        return redirect()->away($url);
    }

    protected function paginaAbono(?Pago $pago): string
    {
        if ($pago && $pago->abono->institucion_id) {
            return route('abonos.institucion', $pago->abono->institucion_id);
        }

        return route('abonos.mio');
    }
}
