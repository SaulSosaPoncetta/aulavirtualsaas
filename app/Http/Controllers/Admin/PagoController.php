<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Institucion;
use App\Models\Pago;
use App\Models\User;
use App\Services\AbonoService;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->query('estado', 'pendiente');

        $pagos = Pago::with(['abono.usuario', 'abono.institucion', 'creador'])
            ->when($estado !== 'todos', fn ($q) => $q->where('estado', $estado))
            ->when($request->filled('metodo'), fn ($q) => $q->where('metodo', $request->metodo))
            ->latest()
            ->paginate(25)->withQueryString();

        return view('admin.pagos', compact('pagos', 'estado'));
    }

    public function aprobar(Request $request, Pago $pago)
    {
        abort_unless($pago->estado === 'pendiente', 422, 'Solo se aprueban pagos pendientes.');

        $pago->abono->aplicarPago($pago, $request->user()->id);
        $abono = $pago->abono->fresh();

        return back()->with('exito', "Pago aprobado. {$abono->nombre}: vence el {$abono->vence_el?->format('d/m/Y')}"
            . ($abono->cupo_docentes ? ", cupo {$abono->cupo_docentes} docentes." : '.'));
    }

    public function rechazar(Request $request, Pago $pago)
    {
        abort_unless($pago->estado === 'pendiente', 422);

        $motivo = trim((string) $request->input('motivo'));

        $pago->update([
            'estado'         => 'rechazado',
            'observaciones'  => trim(($pago->observaciones ? $pago->observaciones . "\n" : '') . 'Rechazado: ' . ($motivo ?: 'sin motivo')),
            'registrado_por' => $request->user()->id,
        ]);

        return back()->with('exito', 'Pago rechazado.');
    }

    /** Formulario para cargar un pago a mano (cobro en efectivo, transferencia que no avisaron, etc.). */
    public function crear(Request $request, AbonoService $precios)
    {
        $buscar = trim((string) $request->query('buscar'));
        $resultados = collect();

        if ($buscar !== '') {
            $dni = preg_replace('/\D/', '', $buscar);

            $docentes = User::role('docente')
                ->where(fn ($q) => $q->where('email', 'like', "%{$buscar}%")->orWhere('apellido', 'like', "%{$buscar}%")
                    ->orWhere('name', 'like', "%{$buscar}%")->when($dni !== '', fn ($w) => $w->orWhere('dni', $dni)))
                ->take(10)->get()
                ->map(fn ($u) => ['tipo' => 'unipersonal', 'id' => $u->id, 'nombre' => $u->nombre_completo, 'detalle' => $u->email, 'abono' => $u->abono]);

            $insts = Institucion::where('nombre', 'like', "%{$buscar}%")->orWhere('cue', 'like', "%{$buscar}%")
                ->take(10)->get()
                ->map(fn ($i) => ['tipo' => 'institucional', 'id' => $i->id, 'nombre' => $i->nombre, 'detalle' => $i->localidad, 'abono' => $i->abono]);

            $resultados = $docentes->concat($insts);
        }

        return view('admin.pago_manual', [
            'buscar'     => $buscar,
            'resultados' => $resultados,
            'meses'      => $precios->opcionesMeses(),
            'precioUni'  => $precios->planUnipersonal()?->precio,
        ]);
    }

    public function guardar(Request $request, AbonoService $precios)
    {
        $datos = $request->validate([
            'tipo'          => 'required|in:unipersonal,institucional',
            'id'            => 'required|integer',
            'meses'         => 'required|integer|min:1|max:24',
            'cupo_docentes' => 'nullable|required_if:tipo,institucional|integer|min:1|max:' . config('abonos.cupo_maximo', 500),
            'metodo'        => 'required|in:transferencia,efectivo,mercadopago,otro',
            'importe'       => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:1000',
        ], [], ['cupo_docentes' => 'cantidad de docentes']);

        $abono = $datos['tipo'] === 'unipersonal'
            ? Abono::deUsuario(User::findOrFail($datos['id']))
            : Abono::deInstitucion(Institucion::findOrFail($datos['id']));

        $importe = $datos['importe']
            ?? $precios->cotizarRenovacion($abono, (int) $datos['meses'], $datos['cupo_docentes'] ?? null)
            ?? 0;

        $pago = $abono->pagos()->create([
            'concepto'      => 'renovacion',
            'metodo'        => $datos['metodo'],
            'estado'        => 'pendiente',
            'meses'         => $datos['meses'],
            'cupo_docentes' => $datos['tipo'] === 'institucional' ? $datos['cupo_docentes'] : null,
            'importe'       => $importe,
            'observaciones' => $datos['observaciones'] ?? 'Cargado por el administrador',
            'creado_por'    => $request->user()->id,
        ]);

        $abono->aplicarPago($pago, $request->user()->id);
        $abono->refresh();

        return redirect()->route('admin.pagos.index', ['estado' => 'aprobado'])
            ->with('exito', "Pago registrado. {$abono->nombre}: vence el {$abono->vence_el->format('d/m/Y')}.");
    }
}
