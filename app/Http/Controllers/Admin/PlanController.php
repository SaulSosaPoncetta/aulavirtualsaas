<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        return view('admin.planes', [
            'unipersonal' => Plan::where('tipo', 'unipersonal')->orderBy('orden')->get(),
            'escalas'     => Plan::where('tipo', 'institucional')->orderBy('docentes_desde')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        Plan::create($datos + ['orden' => (int) Plan::max('orden') + 1]);

        return back()->with('exito', 'Precio agregado.');
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validar($request, $plan));

        return back()->with('exito', 'Precio actualizado. Se aplica a los próximos pagos (los ya hechos no cambian).');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return back()->with('exito', 'Precio eliminado.');
    }

    protected function validar(Request $request, ?Plan $plan = null): array
    {
        $tipo = $plan?->tipo ?? $request->input('tipo');

        $datos = $request->validate([
            'tipo'           => $plan ? 'nullable' : 'required|in:unipersonal,institucional',
            'nombre'         => 'required|string|max:100',
            'descripcion'    => 'nullable|string|max:1000',
            'precio'         => 'required|numeric|min:0|max:99999999',
            'docentes_desde' => $tipo === 'institucional' ? 'required|integer|min:1|max:10000' : 'nullable',
            'docentes_hasta' => $tipo === 'institucional' ? 'nullable|integer|gte:docentes_desde|max:10000' : 'nullable',
        ], [
            'docentes_hasta.gte' => 'El "hasta" tiene que ser mayor o igual al "desde".',
        ]);

        return [
            'tipo'           => $tipo,
            'nombre'         => $datos['nombre'],
            'descripcion'    => $datos['descripcion'] ?? null,
            'precio'         => $datos['precio'],
            'docentes_desde' => $tipo === 'institucional' ? $datos['docentes_desde'] : null,
            'docentes_hasta' => $tipo === 'institucional' ? ($datos['docentes_hasta'] ?? null) : null,
            'activo'         => $request->boolean('activo', true),
        ];
    }
}
