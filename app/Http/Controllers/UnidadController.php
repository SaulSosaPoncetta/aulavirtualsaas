<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Unidad;
use Illuminate\Http\Request;

class UnidadController extends Controller
{
    public function store(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $datos = $this->validar($request);

        $aula->unidades()->create($datos + [
            'orden' => (int) $aula->unidades()->max('orden') + 1,
        ]);

        return redirect()->route('aulas.show', $aula)->with('exito', 'Unidad creada.');
    }

    public function update(Request $request, Unidad $unidad)
    {
        abort_unless($unidad->aula->puedeEditar($request->user()), 403);

        $unidad->update($this->validar($request));

        return redirect()->route('aulas.show', $unidad->aula_id)->with('exito', 'Unidad actualizada.');
    }

    public function destroy(Request $request, Unidad $unidad)
    {
        abort_unless($unidad->aula->puedeEditar($request->user()), 403);

        $aulaId = $unidad->aula_id;
        $unidad->delete(); // sus clases pasan a "General" (unidad_id = null)

        return redirect()->route('aulas.show', $aulaId)->with('exito', 'Unidad eliminada. Sus clases quedaron en "General".');
    }

    /** Subir o bajar la unidad un lugar. */
    public function mover(Request $request, Unidad $unidad, string $direccion)
    {
        abort_unless($unidad->aula->puedeEditar($request->user()), 403);

        $unidades = $unidad->aula->unidades()->get()->values();
        $pos      = $unidades->search(fn ($u) => $u->id == $unidad->id);
        $destino  = $direccion === 'arriba' ? $pos - 1 : $pos + 1;

        if ($pos !== false && isset($unidades[$destino])) {
            $ordenados = $unidades->all();
            [$ordenados[$pos], $ordenados[$destino]] = [$ordenados[$destino], $ordenados[$pos]];

            foreach (array_values($ordenados) as $i => $u) {
                $u->update(['orden' => $i + 1]);
            }
        }

        return redirect()->route('aulas.show', $unidad->aula_id);
    }

    protected function validar(Request $request): array
    {
        $datos = $request->validate([
            'titulo'      => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:2000',
        ], [], ['titulo' => 'título', 'descripcion' => 'descripción']);

        return $datos + ['visible' => $request->boolean('visible')];
    }
}
