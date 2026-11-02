<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\ColumnaLibreta;
use App\Models\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Configuración de la libreta: períodos, columnas manuales y peso de cada actividad. */
class LibretaConfigController extends Controller
{
    public function edit(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        return view('libreta.configurar', [
            'aula'         => $aula,
            'periodos'     => $aula->periodos()->get(),
            'columnas'     => $aula->columnasLibreta()->with('periodo')->get(),
            'tareas'       => $aula->tareas()->orderBy('created_at')->get(),
            'evaluaciones' => $aula->evaluaciones()->orderBy('created_at')->get(),
            'anio'         => (int) ($aula->ciclo_lectivo ?: now('America/Argentina/Buenos_Aires')->year),
        ]);
    }

    // ===================== Períodos =====================

    /** Crea los períodos a partir de una plantilla (reemplaza los que haya). */
    public function plantilla(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $anio       = (int) ($aula->ciclo_lectivo ?: now('America/Argentina/Buenos_Aires')->year);
        $plantillas = Periodo::plantillas($anio);

        $datos = $request->validate(['plantilla' => ['required', 'in:' . implode(',', array_keys($plantillas))]]);

        DB::transaction(function () use ($aula, $plantillas, $datos) {
            $aula->periodos()->delete();

            foreach ($plantillas[$datos['plantilla']] as $i => [$nombre, $desde, $hasta]) {
                $aula->periodos()->create(['nombre' => $nombre, 'desde' => $desde, 'hasta' => $hasta, 'orden' => $i + 1]);
            }
        });

        return back()->with('ok', 'Períodos creados. Podés ajustar las fechas si tu escuela usa otras.');
    }

    public function storePeriodo(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $datos = $this->validarPeriodo($request);
        $datos['orden'] = ($aula->periodos()->max('orden') ?? 0) + 1;
        $aula->periodos()->create($datos);

        return back()->with('ok', 'Período agregado.');
    }

    public function updatePeriodo(Request $request, Aula $aula, Periodo $periodo)
    {
        abort_unless($aula->puedeEditar($request->user()) && $periodo->aula_id == $aula->id, 403);

        $periodo->update($this->validarPeriodo($request));

        return back()->with('ok', 'Período actualizado.');
    }

    public function destroyPeriodo(Request $request, Aula $aula, Periodo $periodo)
    {
        abort_unless($aula->puedeEditar($request->user()) && $periodo->aula_id == $aula->id, 403);

        $periodo->delete(); // borra sus notas de cierre; las columnas manuales quedan sin período

        return back()->with('ok', 'Período eliminado.');
    }

    protected function validarPeriodo(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'desde'  => ['required', 'date'],
            'hasta'  => ['required', 'date', 'after_or_equal:desde'],
        ], [], ['desde' => 'fecha de inicio', 'hasta' => 'fecha de fin']);
    }

    // ===================== Columnas manuales =====================

    public function storeColumna(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $aula->columnasLibreta()->create($this->validarColumna($request, $aula));

        return back()->with('ok', 'Columna agregada. Ya podés cargar las notas en la libreta.');
    }

    public function updateColumna(Request $request, Aula $aula, ColumnaLibreta $columna)
    {
        abort_unless($aula->puedeEditar($request->user()) && $columna->aula_id == $aula->id, 403);

        $datos = $this->validarColumna($request, $aula);

        $maxNota = $columna->notas()->max('nota');
        if ($maxNota !== null && (float) $maxNota > (float) $datos['puntaje_maximo']) {
            throw ValidationException::withMessages(['puntaje_maximo' => 'Ya hay notas mayores que ese puntaje máximo.']);
        }

        $columna->update($datos);

        return back()->with('ok', 'Columna actualizada.');
    }

    public function destroyColumna(Request $request, Aula $aula, ColumnaLibreta $columna)
    {
        abort_unless($aula->puedeEditar($request->user()) && $columna->aula_id == $aula->id, 403);

        $columna->delete();

        return back()->with('ok', 'Columna eliminada junto con sus notas.');
    }

    protected function validarColumna(Request $request, Aula $aula): array
    {
        $datos = $request->validate([
            'nombre'         => ['required', 'string', 'max:80'],
            'puntaje_maximo' => ['required', 'numeric', 'min:1', 'max:1000'],
            'peso'           => ['required', 'numeric', 'min:0', 'max:10'],
            'fecha'          => ['nullable', 'date'],
            'periodo_id'     => ['nullable', 'integer'],
        ], [], ['puntaje_maximo' => 'puntaje máximo']);

        if (! empty($datos['periodo_id']) && ! $aula->periodos()->whereKey($datos['periodo_id'])->exists()) {
            $datos['periodo_id'] = null;
        }

        $datos['periodo_id'] ??= null;

        return $datos;
    }

    // ===================== Pesos de tareas y evaluaciones =====================

    public function pesos(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $request->validate([
            'tareas'         => ['nullable', 'array'],
            'tareas.*'       => ['nullable', 'numeric', 'min:0', 'max:10'],
            'evaluaciones'   => ['nullable', 'array'],
            'evaluaciones.*' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ], [], ['tareas.*' => 'peso', 'evaluaciones.*' => 'peso']);

        foreach ($aula->tareas()->whereIn('id', array_keys((array) $request->input('tareas', [])))->get() as $t) {
            $t->forceFill(['peso' => $request->input("tareas.{$t->id}") ?? 1])->saveQuietly();
        }

        foreach ($aula->evaluaciones()->whereIn('id', array_keys((array) $request->input('evaluaciones', [])))->get() as $e) {
            $e->forceFill(['peso' => $request->input("evaluaciones.{$e->id}") ?? 1])->saveQuietly();
        }

        return back()->with('ok', 'Pesos actualizados.');
    }
}
