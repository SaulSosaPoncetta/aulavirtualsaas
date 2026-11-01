<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pago;
use App\Models\SistemaSaas;
use App\Models\Suscripcion;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $totalClientes = Cliente::count();

        $suscripcionesPorEstado = Suscripcion::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $cobradoEsteMes = Pago::where('estado', 'aprobado')
            ->whereMonth('fecha_pago', now()->month)
            ->whereYear('fecha_pago', now()->year)
            ->sum('monto');

        $ingresoRecurrente = Suscripcion::where('estado', 'activa')
            ->where('tipo', 'recurrente')
            ->sum('monto');

        $proximosVencimientos = Suscripcion::with('clienteSistema.cliente', 'clienteSistema.sistema')
            ->where('tipo', 'recurrente')
            ->whereNotNull('proxima_fecha_cobro')
            ->orderBy('proxima_fecha_cobro')
            ->take(8)
            ->get();

        $transferenciasPorRevisar = Pago::where('medio_pago', 'transferencia')
            ->where('estado', 'pendiente')
            ->whereNotNull('comprobante_path')
            ->count();

        $tarjetas = $this->tarjetasPorSistema();

        return view('home', compact(
            'totalClientes',
            'suscripcionesPorEstado',
            'cobradoEsteMes',
            'ingresoRecurrente',
            'proximosVencimientos',
            'tarjetas',
            'transferenciasPorRevisar'
        ));
    }

    /**
     * Una tarjeta por cada sistema SaaS registrado en el hub, con sus números de cobro.
     */
    protected function tarjetasPorSistema(): Collection
    {
        $sistemas = SistemaSaas::withCount('clienteSistemas')->orderBy('nombre')->get();

        // Suscripciones por sistema y estado
        $subs = Suscripcion::query()
            ->join('cliente_sistemas as cs', 'cs.id_cliente_sistema', '=', 'suscripciones.id_cliente_sistema')
            ->selectRaw('cs.id_sistema, suscripciones.estado, count(*) as total')
            ->groupBy('cs.id_sistema', 'suscripciones.estado')
            ->get()
            ->groupBy('id_sistema');

        // Ingreso mensual recurrente, próximo cobro y cobros que vencen en 7 días
        $recurrentes = Suscripcion::query()
            ->join('cliente_sistemas as cs', 'cs.id_cliente_sistema', '=', 'suscripciones.id_cliente_sistema')
            ->where('suscripciones.estado', 'activa')
            ->where('suscripciones.tipo', 'recurrente')
            ->selectRaw('cs.id_sistema, sum(suscripciones.monto) as mrr, min(suscripciones.proxima_fecha_cobro) as proximo,
                sum(case when suscripciones.proxima_fecha_cobro between ? and ? then 1 else 0 end) as por_vencer', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->groupBy('cs.id_sistema')
            ->get()
            ->keyBy('id_sistema');

        // Pagos aprobados (mes en curso y total histórico) y pagos pendientes
        $pagosBase = fn () => Pago::query()
            ->join('suscripciones as s', 's.id_suscripcion', '=', 'pagos.id_suscripcion')
            ->join('cliente_sistemas as cs', 'cs.id_cliente_sistema', '=', 's.id_cliente_sistema')
            ->groupBy('cs.id_sistema');

        $cobradoMes = $pagosBase()
            ->where('pagos.estado', 'aprobado')
            ->whereMonth('pagos.fecha_pago', now()->month)
            ->whereYear('pagos.fecha_pago', now()->year)
            ->selectRaw('cs.id_sistema, sum(pagos.monto) as total')
            ->pluck('total', 'id_sistema');

        $cobradoTotal = $pagosBase()
            ->where('pagos.estado', 'aprobado')
            ->selectRaw('cs.id_sistema, sum(pagos.monto) as total')
            ->pluck('total', 'id_sistema');

        $pagosPendientes = $pagosBase()
            ->where('pagos.estado', 'pendiente')
            ->where(fn ($q) => $q->whereNotNull('pagos.comprobante_path')->orWhereNotNull('pagos.mp_payment_id'))
            ->selectRaw('cs.id_sistema, count(*) as total')
            ->pluck('total', 'id_sistema');

        return $sistemas->map(function (SistemaSaas $s) use ($subs, $recurrentes, $cobradoMes, $cobradoTotal, $pagosPendientes) {
            $porEstado = ($subs[$s->id_sistema] ?? collect())->pluck('total', 'estado');
            $rec = $recurrentes[$s->id_sistema] ?? null;

            return (object) [
                'sistema' => $s,
                'clientes' => $s->cliente_sistemas_count,
                'activas' => (int) ($porEstado['activa'] ?? 0),
                'pendientes' => (int) ($porEstado['pendiente'] ?? 0),
                'vencidas' => (int) (($porEstado['vencida'] ?? 0) + ($porEstado['suspendida'] ?? 0)),
                'mrr' => (float) ($rec->mrr ?? 0),
                'proximo' => $rec?->proximo,
                'porVencer' => (int) ($rec->por_vencer ?? 0),
                'cobradoMes' => (float) ($cobradoMes[$s->id_sistema] ?? 0),
                'cobradoTotal' => (float) ($cobradoTotal[$s->id_sistema] ?? 0),
                'pagosPendientes' => (int) ($pagosPendientes[$s->id_sistema] ?? 0),
            ];
        });
    }
}
