<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteSistema;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\SistemaSaas;
use App\Models\Suscripcion;
use App\Services\Cotizador;
use App\Services\EnlacePago;
use App\Services\MercadoPagoService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Página pública de pago del Panel. Se entra únicamente con un enlace firmado
 * generado por un sistema satélite (ver App\Services\EnlacePago).
 */
class PagoPublicoController extends Controller
{
    public function mostrar(Request $request)
    {
        [$sistema, $clienteSistema, $q] = $this->resolver($request);

        $planes = $sistema->planes()->with('escalas')->where('activo', true)->orderBy('precio')->get();

        if (! empty($q['plan'])) {
            $planes = $planes->where('codigo', $q['plan'])->values();
        }

        if ($planes->isEmpty()) {
            return $this->invalido('Este sistema todavía no tiene planes disponibles para contratar. Escribinos y lo resolvemos.');
        }

        $suscripcion = $this->suscripcionVigente($clienteSistema);
        $hoy = $this->hoy();
        $minimo = (int) ($q['minimo'] ?? 0);

        $planesJs = $planes->map(fn (Plan $p) => [
            'id' => $p->id_plan,
            'nombre' => $p->nombre,
            'descripcion' => $p->descripcion,
            'moneda' => $p->moneda,
            'precio' => (float) $p->precio,
            'tipo' => $p->tipo,
            'periodo' => $p->periodoTexto(),
            'porUnidad' => (bool) $p->cobro_por_unidad,
            'unidad' => $p->unidad_nombre ?: 'unidades',
            'min' => max((int) $p->unidad_minima, $minimo),
            'max' => $p->unidad_maxima ? (int) $p->unidad_maxima : null,
            'mensual' => $p->esMensual(),
            'meses' => $p->mesesOpciones(),
            'escalas' => $p->cobro_por_unidad
                ? $p->escalas->map(fn ($e) => ['desde' => (int) $e->desde, 'hasta' => $e->hasta ? (int) $e->hasta : null, 'precio' => (float) $e->precio])->values()
                : [],
        ])->values();

        $suscJs = [
            'idPlan' => $suscripcion?->id_plan,
            'cantidad' => (int) ($suscripcion?->cantidad ?? 0),
            'vigente' => $this->estaVigente($suscripcion, $hoy),
            'vence' => $suscripcion?->fecha_vencimiento?->format('d/m/Y'),
            'mesesRestantes' => $this->estaVigente($suscripcion, $hoy) ? $this->mesesRestantes($suscripcion, $hoy) : 1,
        ];

        return view('pagar.index', [
            'sistema' => $sistema,
            'cliente' => $clienteSistema->cliente,
            'nombreCuenta' => $q['nombre'] ?? $clienteSistema->cliente->nombre,
            'planes' => $planes,
            'planesJs' => $planesJs,
            'suscJs' => $suscJs,
            'suscripcion' => $suscripcion,
            'cantidadInicial' => (int) ($q['cantidad'] ?? 0),
            'queryFirmada' => http_build_query($q, '', '&', PHP_QUERY_RFC3986),
            'transferenciaActiva' => (bool) config('pagos.transferencia.activa'),
        ]);
    }

    public function iniciar(Request $request, MercadoPagoService $mercadoPago)
    {
        [$sistema, $clienteSistema, $q] = $this->resolver($request);

        $request->validate([
            'id_plan' => 'required|integer',
            'cantidad' => 'nullable|integer|min:1',
            'meses' => 'nullable|integer|min:1|max:120',
            'concepto' => 'nullable|in:renovacion,ampliacion',
            'medio' => 'nullable|in:mercadopago,transferencia',
        ]);

        $medio = $request->input('medio') === 'transferencia' && config('pagos.transferencia.activa')
            ? 'transferencia'
            : 'mercadopago';

        $plan = Plan::with('escalas')
            ->where('id_plan', $request->id_plan)
            ->where('id_sistema', $sistema->id_sistema)
            ->where('activo', true)
            ->first();

        if (! $plan || (! empty($q['plan']) && $plan->codigo !== $q['plan'])) {
            return back()->with('error', 'El plan elegido no está disponible.');
        }

        $hoy = $this->hoy();
        $suscripcion = $this->suscripcionVigente($clienteSistema);

        // ---- Cantidad (cupo) ----
        $cantidad = 1;
        if ($plan->cobro_por_unidad) {
            $cantidad = (int) ($request->input('cantidad') ?: ($q['cantidad'] ?? $plan->unidad_minima));
            $unidad = $plan->unidad_nombre ?: 'unidades';
            $minimo = max((int) $plan->unidad_minima, (int) ($q['minimo'] ?? 0));

            if ($cantidad < $minimo || ($plan->unidad_maxima && $cantidad > $plan->unidad_maxima)) {
                $rango = $plan->unidad_maxima ? "entre {$minimo} y {$plan->unidad_maxima}" : "de al menos {$minimo}";

                return back()->withInput()->with('error', "La cantidad de {$unidad} debe ser {$rango}.");
            }
        }

        // ---- Importe ----
        $concepto = $request->input('concepto') === 'ampliacion' ? 'ampliacion' : 'renovacion';
        $meses = null;

        if ($concepto === 'ampliacion') {
            $puede = $plan->cobro_por_unidad
                && $this->estaVigente($suscripcion, $hoy)
                && (int) $suscripcion->id_plan === (int) $plan->id_plan
                && $cantidad > (int) $suscripcion->cantidad;

            if (! $puede) {
                return back()->withInput()->with('error', 'Para ampliar, el abono tiene que estar vigente y el nuevo cupo ser mayor al actual.');
            }

            $importe = $plan->cotizarAmpliacion((int) $suscripcion->cantidad, $cantidad, $this->mesesRestantes($suscripcion, $hoy));
        } else {
            $meses = (int) ($request->input('meses') ?: $plan->mesesOpciones()[0]);
            $importe = $plan->cotizarRenovacion($cantidad, $meses);
        }

        if ($importe === null || $importe <= 0) {
            return back()->withInput()->with('error', 'No hay un precio cargado para esa combinación. Escribinos y lo resolvemos.');
        }

        $datosSuscripcion = [
            'id_plan' => $plan->id_plan,
            'plan' => $plan->nombre,
            'tipo' => $plan->tipo,
            'cantidad' => $cantidad,
            'monto' => $importe,
            'moneda' => $plan->moneda,
        ];

        try {
            // Débito automático de Mercado Pago (suscripción recurrente)
            if ($medio === 'mercadopago' && $plan->tipo === 'recurrente' && $concepto === 'renovacion') {
                return $this->pagarConDebitoAutomatico($clienteSistema, $suscripcion, $datosSuscripcion, $mercadoPago);
            }

            // Resto de los casos: un pago puntual que lleva anotado qué se paga
            if (! $suscripcion) {
                $suscripcion = Suscripcion::create($datosSuscripcion + [
                    'id_cliente_sistema' => $clienteSistema->id_cliente_sistema,
                    'estado' => 'pendiente',
                    'fecha_inicio' => now(),
                ]);
            } elseif ($suscripcion->estado === 'pendiente') {
                $suscripcion->update($datosSuscripcion); // todavía no pagó nunca: se actualiza lo elegido
            }

            $pago = $this->pagoPendiente($suscripcion, $medio, [
                'id_plan' => $plan->id_plan,
                'concepto' => $concepto,
                'meses' => $meses,
                'cantidad' => $cantidad,
                'monto' => $importe,
                'moneda' => $plan->moneda,
            ]);

            if ($medio === 'transferencia') {
                return redirect()->to(URL::temporarySignedRoute(
                    'pagar.transferencia', now()->addDays(7), ['pago' => $pago->id_pago]
                ));
            }

            $resultado = $mercadoPago->crearPreferenciaPago(
                $pago,
                $sistema->nombre.' - '.$pago->detalleTexto(),
                $clienteSistema->cliente->email
            );
            $pago->update(['mp_preference_id' => $resultado['id'] ?? null]);

            return redirect()->away($resultado['init_point']);
        } catch (\Throwable $e) {
            Log::error('No se pudo generar el pago', [
                'sistema' => $sistema->slug,
                'ref' => $clienteSistema->referencia_externa,
                'medio' => $medio,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $medio === 'transferencia'
                ? 'No pudimos preparar la transferencia. Probá de nuevo en unos minutos.'
                : 'No pudimos conectar con Mercado Pago. Probá de nuevo en unos minutos.');
        }
    }

    /**
     * Enlace de renovación de los mails (URL firmada de Laravel, válida 30 días).
     * Genera al momento un enlace de pago fresco y redirige a la página de pago.
     */
    public function renovar(ClienteSistema $clienteSistema)
    {
        $sistema = $clienteSistema->sistema;

        if (! $sistema || ! $sistema->activo || ! $clienteSistema->referencia_externa) {
            return $this->invalido('No pudimos abrir el pago. Entrá al sistema y tocá "Pagar".');
        }

        $suscripcion = $clienteSistema->suscripciones()
            ->where('estado', '!=', 'cancelada')
            ->latest('id_suscripcion')
            ->with('planCatalogo')
            ->first();

        $params = ['ref' => $clienteSistema->referencia_externa];

        if ($suscripcion?->planCatalogo?->activo) {
            $params['plan'] = $suscripcion->planCatalogo->codigo;
            $params['cantidad'] = (string) max(1, (int) $suscripcion->cantidad);
        }

        return redirect()->to(EnlacePago::generarUrl($sistema, $params));
    }

    /** A donde vuelve Mercado Pago después del pago (éxito, error o pendiente). */
    public function resultado(Request $request, string $estado)
    {
        $suscripcion = null;
        $referencia = (string) $request->query('external_reference');

        if (str_starts_with($referencia, 'pago-')) {
            $suscripcion = Pago::find((int) substr($referencia, 5))?->suscripcion;
        } elseif ($referencia !== '') {
            $suscripcion = Suscripcion::find($referencia);
        } elseif ($request->filled('preapproval_id')) {
            $suscripcion = Suscripcion::where('mp_preapproval_id', $request->query('preapproval_id'))->first();
        }

        $sistema = $suscripcion?->clienteSistema?->sistema;

        return view('pagar.resultado', [
            'estado' => in_array($estado, ['exito', 'error', 'pendiente'], true) ? $estado : 'pendiente',
            'sistema' => $sistema,
        ]);
    }

    // ===================== Ayudas =====================

    /** Débito automático: se crea o actualiza la suscripción y se manda al cliente a autorizarlo en Mercado Pago. */
    protected function pagarConDebitoAutomatico(ClienteSistema $clienteSistema, ?Suscripcion $suscripcion, array $datos, MercadoPagoService $mercadoPago)
    {
        $cambio = false;
        if ($suscripcion) {
            $cambio = (float) $suscripcion->monto !== (float) $datos['monto']
                || (int) $suscripcion->id_plan !== (int) $datos['id_plan']
                || $suscripcion->tipo !== $datos['tipo'];
        }

        if ($suscripcion && $suscripcion->tipo === 'recurrente' && $suscripcion->mp_preapproval_id) {
            $actual = $mercadoPago->obtenerPreapproval($suscripcion->mp_preapproval_id);
            if (($actual['status'] ?? null) === 'authorized') {
                return back()->with('error', $cambio
                    ? 'Tenés un débito automático activo. Para cambiar de plan o de cantidad escribinos y lo ajustamos.'
                    : 'Tu débito automático ya está activo: no hace falta que pagues manualmente.');
            }
        }

        if ($suscripcion) {
            if ($cambio) {
                $datos['mp_preapproval_id'] = null;
            }
            $suscripcion->update($datos);
        } else {
            $suscripcion = Suscripcion::create($datos + [
                'id_cliente_sistema' => $clienteSistema->id_cliente_sistema,
                'estado' => 'pendiente',
                'fecha_inicio' => now(),
            ]);
        }

        $suscripcion = $suscripcion->fresh('planCatalogo');

        if ($suscripcion->mp_preapproval_id) {
            $existente = $mercadoPago->obtenerPreapproval($suscripcion->mp_preapproval_id);
            if (($existente['status'] ?? null) === 'pending' && ! empty($existente['init_point'])) {
                return redirect()->away($existente['init_point']);
            }
        }

        $resultado = $mercadoPago->crearPreapproval($suscripcion);
        $suscripcion->update(['mp_preapproval_id' => $resultado['id']]);

        return redirect()->away($resultado['init_point']);
    }

    /** Reutiliza el pago pendiente idéntico o crea uno nuevo (anulando los pedidos viejos que quedaron sin usar). */
    protected function pagoPendiente(Suscripcion $suscripcion, string $medio, array $campos): Pago
    {
        $pendientes = $suscripcion->pagos()
            ->where('medio_pago', $medio)
            ->where('estado', 'pendiente')
            ->whereNull('mp_payment_id')
            ->get();

        $igual = $pendientes->first(fn (Pago $p) => (int) $p->id_plan === (int) $campos['id_plan']
            && $p->concepto === $campos['concepto']
            && (int) $p->meses === (int) ($campos['meses'] ?? 0)
            && (int) $p->cantidad === (int) $campos['cantidad']
            && (float) $p->monto === (float) $campos['monto']);

        if ($igual) {
            return $igual;
        }

        $pendientes->whereNull('comprobante_path')->each(fn (Pago $p) => $p->update([
            'estado' => 'rechazado',
            'motivo_rechazo' => 'Reemplazado por un pedido nuevo.',
        ]));

        return Pago::create($campos + [
            'id_suscripcion' => $suscripcion->id_suscripcion,
            'medio_pago' => $medio,
            'estado' => 'pendiente',
        ]);
    }

    protected function suscripcionVigente(ClienteSistema $clienteSistema): ?Suscripcion
    {
        return $clienteSistema->suscripciones()
            ->where('estado', '!=', 'cancelada')
            ->latest('id_suscripcion')
            ->first();
    }

    protected function hoy(): string
    {
        return Carbon::today(config('pagos.zona_horaria'))->toDateString();
    }

    /** Abono activo y con vencimiento todavía no pasado. */
    protected function estaVigente(?Suscripcion $s, string $hoy): bool
    {
        return $s
            && $s->estado === 'activa'
            && $s->fecha_vencimiento
            && $s->fecha_vencimiento->toDateString() >= $hoy;
    }

    protected function mesesRestantes(Suscripcion $s, string $hoy): int
    {
        $dias = (int) Carbon::parse($hoy)->diffInDays(Carbon::parse($s->fecha_vencimiento->toDateString()), false);

        return Cotizador::mesesRestantes($dias);
    }

    /**
     * Valida el enlace firmado y devuelve [sistema, clienteSistema, parámetros firmados].
     * Si algo no cierra corta con la pantalla de enlace inválido.
     */
    protected function resolver(Request $request): array
    {
        $q = EnlacePago::filtrar($request->query());

        $sistema = SistemaSaas::where('slug', $q['sistema'] ?? '')->where('activo', true)->first();

        if (! $sistema || empty($q['ref']) || ! EnlacePago::verificar($q, $sistema->webhook_secret)) {
            throw new HttpResponseException($this->invalido('El enlace de pago no es válido o venció. Volvé al sistema y tocá "Pagar" de nuevo.'));
        }

        $clienteSistema = ClienteSistema::where('id_sistema', $sistema->id_sistema)
            ->where('referencia_externa', $q['ref'])
            ->with('cliente')
            ->first();

        if (! $clienteSistema) {
            if (empty($q['email']) || empty($q['nombre'])) {
                throw new HttpResponseException($this->invalido('No encontramos tu cuenta. Volvé al sistema y tocá "Pagar" de nuevo.'));
            }

            $clienteSistema = $this->altaCliente($sistema, $q);
        }

        return [$sistema, $clienteSistema, $q];
    }

    /**
     * El sistema todavía no había registrado la cuenta: la damos de alta con los datos firmados del enlace.
     * Un mismo cliente (mismo email) puede tener varias cuentas en un sistema, distinguidas por la referencia.
     */
    protected function altaCliente(SistemaSaas $sistema, array $q): ClienteSistema
    {
        $cuit = Cliente::normalizarCuit($q['cuit'] ?? null);

        $cliente = ($cuit ? Cliente::where('identificacion_fiscal', $cuit)->first() : null)
            ?? Cliente::where('email', $q['email'])->first()
            ?? Cliente::create([
                'nombre' => $q['nombre'],
                'email' => $q['email'],
                'identificacion_fiscal' => $cuit,
            ]);

        return ClienteSistema::create([
            'id_cliente' => $cliente->id_cliente,
            'id_sistema' => $sistema->id_sistema,
            'referencia_externa' => $q['ref'],
            'email_alta' => $q['email'],
            'identificacion_fiscal_alta' => $cuit,
        ])->load('cliente');
    }

    protected function invalido(string $mensaje)
    {
        return response()->view('pagar.invalido', ['mensaje' => $mensaje], 403);
    }
}
