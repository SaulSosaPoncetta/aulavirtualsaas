<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Entrega;
use App\Models\Tarea;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntregaController extends Controller
{
    /** El alumno entrega (o reenvía) su trabajo. */
    public function guardar(Request $request, Tarea $tarea)
    {
        $user = $request->user();
        $aula = $tarea->aula;

        abort_unless($aula->esAlumno($user), 403, 'Solo los alumnos del aula pueden entregar.');
        abort_unless($tarea->estaPublicada(), 404);

        if (! $tarea->aceptaEntregas()) {
            return back()->with('error', 'La fecha límite ya pasó y esta tarea no acepta entregas tardías.');
        }

        $entrega = Entrega::where('tarea_id', $tarea->id)->where('alumno_id', $user->id)->first();

        if ($entrega && $entrega->estado === 'corregida') {
            return back()->with('error', 'Esta entrega ya fue corregida y no se puede modificar.');
        }

        $datos = $request->validate([
            'texto'           => 'nullable|string|max:60000',
            'archivos'        => 'nullable|array|max:5',
            'archivos.*'      => 'file|mimes:' . TareaController::EXTENSIONES . '|max:' . TareaController::MAX_KB,
            'quitar_archivos' => 'nullable|array',
        ], [
            'archivos.*.mimes' => 'Uno de los archivos tiene un formato no permitido.',
            'archivos.*.max'   => 'Cada archivo puede pesar como máximo 20 MB.',
            'archivos.max'     => 'Podés subir hasta 5 archivos por vez.',
        ]);

        $quitar    = array_map('intval', (array) ($datos['quitar_archivos'] ?? []));
        $quedan    = $entrega ? $entrega->archivos()->whereNotIn('id', $quitar)->count() : 0;
        $nuevos    = count(array_filter((array) $request->file('archivos', [])));
        $texto     = trim((string) ($datos['texto'] ?? ''));

        if ($texto === '' && $quedan + $nuevos === 0) {
            return back()->withInput()->with('error', 'La entrega está vacía: escribí una respuesta o adjuntá al menos un archivo.');
        }

        DB::transaction(function () use ($request, $tarea, $user, $entrega, $texto, $quitar) {
            $entrega ??= new Entrega(['tarea_id' => $tarea->id, 'alumno_id' => $user->id]);

            $entrega->fill([
                'texto'        => $texto !== '' ? $texto : null,
                'estado'       => 'entregada',
                'entregada_at' => Carbon::now('America/Argentina/Buenos_Aires'),
            ])->save();

            if ($quitar) {
                $entrega->archivos()->whereIn('id', $quitar)->get()->each->delete();
            }

            foreach ((array) $request->file('archivos', []) as $archivo) {
                if ($archivo && $archivo->isValid()) {
                    Archivo::guardar($archivo, $entrega, "aulas/{$tarea->aula_id}/tareas/{$tarea->id}/entregas/{$user->id}", $user->id);
                }
            }
        });

        return redirect()->route('tareas.show', $tarea)->with('exito', '¡Listo! Tu entrega se envió.');
    }

    /** El docente ve la entrega de un alumno para corregirla. */
    public function show(Request $request, Entrega $entrega)
    {
        $tarea = $entrega->tarea;
        abort_unless($tarea->aula->puedeEditar($request->user()), 403);

        $entrega->load(['alumno', 'archivos', 'correctora']);

        // Para ir a la entrega anterior / siguiente sin volver a la lista
        $ids = $tarea->entregas()->join('users', 'users.id', '=', 'entregas.alumno_id')
            ->orderBy('users.apellido')->orderBy('users.name')
            ->pluck('entregas.id')->values();
        $pos = $ids->search($entrega->id);

        return view('entregas.show', [
            'entrega'   => $entrega,
            'tarea'     => $tarea,
            'aula'      => $tarea->aula,
            'anterior'  => $pos !== false && $pos > 0 ? $ids[$pos - 1] : null,
            'siguiente' => $pos !== false && $pos < $ids->count() - 1 ? $ids[$pos + 1] : null,
        ]);
    }

    /** El docente corrige (nota + devolución) o devuelve para rehacer. */
    public function corregir(Request $request, Entrega $entrega)
    {
        $tarea = $entrega->tarea;
        abort_unless($tarea->aula->puedeEditar($request->user()), 403);

        $request->merge(['nota' => $request->filled('nota') ? str_replace(',', '.', $request->nota) : null]);

        $datos = $request->validate([
            'accion'     => 'required|in:corregir,devolver',
            'nota'       => 'nullable|numeric|min:0|max:' . (float) $tarea->puntaje_maximo,
            'devolucion' => 'nullable|string|max:20000',
        ], [
            'nota.max' => 'La nota no puede superar ' . $tarea->puntaje_maximo_texto . '.',
        ], [
            'devolucion' => 'devolución',
        ]);

        if ($datos['accion'] === 'devolver' && empty(trim((string) $datos['devolucion']))) {
            return back()->withInput()->withErrors(['devolucion' => 'Para devolver la entrega escribí qué tiene que corregir el alumno.']);
        }

        $entrega->update([
            'estado'        => $datos['accion'] === 'corregir' ? 'corregida' : 'devuelta',
            'nota'          => $datos['nota'],
            'devolucion'    => $datos['devolucion'],
            'corregida_at'  => Carbon::now('America/Argentina/Buenos_Aires'),
            'corregida_por' => $request->user()->id,
        ]);

        $mensaje = $datos['accion'] === 'corregir'
            ? 'Entrega corregida.'
            : 'Entrega devuelta: el alumno puede volver a enviarla.';

        // Si viene del botón "Guardar y seguir", ir a la próxima entrega
        if ($request->filled('siguiente')) {
            return redirect()->route('entregas.show', (int) $request->siguiente)->with('exito', $mensaje);
        }

        return redirect()->route('tareas.show', $tarea)->with('exito', $mensaje);
    }
}
