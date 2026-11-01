<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\AplicadorPagos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PagoAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superadmin']);
    }

    public function index(Request $request)
    {
        $pagos = Pago::with('suscripcion.clienteSistema.cliente', 'suscripcion.clienteSistema.sistema')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('medio'), fn ($q) => $q->where('medio_pago', $request->medio))
            // Primero las transferencias con comprobante esperando tu revisión
            ->orderByRaw("(estado = 'pendiente' and comprobante_path is not null) desc")
            ->latest('id_pago')
            ->paginate(20)
            ->withQueryString();

        $porRevisar = Pago::where('medio_pago', 'transferencia')
            ->where('estado', 'pendiente')
            ->whereNotNull('comprobante_path')
            ->count();

        return view('pagos.index', compact('pagos', 'porRevisar'));
    }

    public function show(Pago $pago)
    {
        $pago->load('suscripcion.clienteSistema.cliente', 'suscripcion.clienteSistema.sistema', 'suscripcion.planCatalogo', 'revisor');

        return view('pagos.show', compact('pago'));
    }

    public function comprobante(Pago $pago)
    {
        abort_unless($pago->comprobante_path && Storage::exists($pago->comprobante_path), 404);

        return Storage::response($pago->comprobante_path, $pago->comprobante_nombre ?: basename($pago->comprobante_path));
    }

    public function aprobar(Pago $pago, AplicadorPagos $aplicador)
    {
        $aplicado = DB::transaction(function () use ($pago) {
            $p = Pago::lockForUpdate()->find($pago->id_pago);

            if ($p->estado !== 'pendiente') {
                return false;
            }

            $p->update([
                'estado' => 'aprobado',
                'fecha_pago' => $p->fecha_transferencia ?? now(),
                'revisado_por' => auth()->id(),
                'revisado_at' => now(),
                'motivo_rechazo' => null,
            ]);

            return true;
        });

        if (! $aplicado) {
            return back()->with('error', 'Este pago ya fue revisado.');
        }

        // Fuera de la transacción: calcula el vencimiento y avisa al sistema (llamada HTTP).
        $aplicador->acreditar($pago->suscripcion, $pago->fresh());

        return redirect()->route('pagos.show', $pago)
            ->with('success', 'Pago aprobado: la suscripción quedó activa y se avisó al sistema.');
    }

    public function rechazar(Request $request, Pago $pago)
    {
        $data = $request->validate(['motivo' => 'required|string|max:500']);

        if ($pago->estado !== 'pendiente') {
            return back()->with('error', 'Este pago ya fue revisado.');
        }

        $pago->update([
            'estado' => 'rechazado',
            'motivo_rechazo' => $data['motivo'],
            'revisado_por' => auth()->id(),
            'revisado_at' => now(),
        ]);

        return redirect()->route('pagos.show', $pago)->with('success', 'Pago rechazado. La suscripción no se modificó.');
    }
}
