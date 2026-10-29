<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Aula;
use App\Models\Tarea;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TareaController extends Controller
{
    public const EXTENSIONES = 'pdf,doc,docx,odt,xls,xlsx,ods,csv,ppt,pptx,odp,txt,jpg,jpeg,png,gif,webp,zip,rar,7z,mp3,wav,ogg,m4a,mp4,webm,mov';
    public const MAX_KB      = 20480; // 20 MB

    public function create(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        return view('tareas.form', [
            'aula'     => $aula,
            'tarea'    => new Tarea([
                'unidad_id'      => $request->query('unidad'),
                'visible'        => true,
                'acepta_tardias' => true,
                'puntaje_maximo' => 10,
            ]),
            'unidades' => $aula->unidades()->get(),
        ]);
    }

    public function store(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $datos = $this->validar($request, $aula);

        $tarea = DB::transaction(function () use ($request, $aula, $datos) {
            $tarea = $aula->tareas()->create($datos + ['user_id' => $request->user()->id]);
            $this->guardarArchivos($request, $tarea);

            return $tarea;
        });

        return redirect()->route('tareas.show', $tarea)->with('exito', 'Tarea publicada.');
    }

    public function show(Request $request, Tarea $tarea)
    {
        $aula = $tarea->aula;
        $user = $request->user();

        abort_unless($aula->puedeVer($user), 403);

        $puedeEditar = $aula->puedeEditar($user);
        abort_unless($puedeEditar || $tarea->estaPublicada(), 404);

        $tarea->load(['archivos', 'unidad', 'autor']);

        if ($puedeEditar) {
            // Vista docente: todos los alumnos con el estado de su entrega
            $alumnos  = $aula->alumnos()->orderBy('apellido')->orderBy('name')->get();
            $entregas = $tarea->entregas()->with('archivos')->get()->keyBy('alumno_id');

            $resumen = [
                'alumnos'    => $alumnos->count(),
                'entregadas' => $entregas->count(),
                'corregir'   => $entregas->where('estado', 'entregada')->count(),
                'corregidas' => $entregas->where('estado', 'corregida')->count(),
            ];

            return view('tareas.show', compact('tarea', 'aula', 'puedeEditar', 'alumnos', 'entregas', 'resumen'));
        }

        // Vista alumno: su propia entrega
        $entrega = $tarea->entregas()->with('archivos')->where('alumno_id', $user->id)->first();

        return view('tareas.show', [
            'tarea'       => $tarea,
            'aula'        => $aula,
            'puedeEditar' => false,
            'entrega'     => $entrega,
            'esAlumno'    => $aula->esAlumno($user),
        ]);
    }

    public function edit(Request $request, Tarea $tarea)
    {
        abort_unless($tarea->aula->puedeEditar($request->user()), 403);

        $tarea->load('archivos');

        return view('tareas.form', [
            'aula'     => $tarea->aula,
            'tarea'    => $tarea,
            'unidades' => $tarea->aula->unidades()->get(),
        ]);
    }

    public function update(Request $request, Tarea $tarea)
    {
        $aula = $tarea->aula;
        abort_unless($aula->puedeEditar($request->user()), 403);

        $datos = $this->validar($request, $aula);

        DB::transaction(function () use ($request, $tarea, $datos) {
            $tarea->update($datos);

            $quitar = array_map('intval', (array) $request->input('quitar_archivos', []));
            if ($quitar) {
                $tarea->archivos()->whereIn('id', $quitar)->get()->each->delete();
            }

            $this->guardarArchivos($request, $tarea);
        });

        return redirect()->route('tareas.show', $tarea)->with('exito', 'Tarea actualizada.');
    }

    public function destroy(Request $request, Tarea $tarea)
    {
        $aula = $tarea->aula;
        abort_unless($aula->puedeEditar($request->user()), 403);

        DB::transaction(function () use ($tarea) {
            $tarea->archivos()->get()->each->delete();

            foreach ($tarea->entregas()->with('archivos')->get() as $entrega) {
                $entrega->archivos->each->delete();
                $entrega->delete();
            }

            $tarea->delete();
        });

        return redirect()->route('aulas.show', $aula)->with('exito', 'Tarea eliminada, junto con sus entregas.');
    }

    // ===================== Ayudas =====================

    protected function validar(Request $request, Aula $aula): array
    {
        $datos = $request->validate([
            'titulo'            => 'required|string|max:200',
            'unidad_id'         => 'nullable|integer|exists:unidades,id,aula_id,' . $aula->id,
            'consigna'          => 'nullable|string|max:60000',
            'fecha_publicacion' => 'nullable|date',
            'fecha_limite'      => 'nullable|date',
            'puntaje_maximo'    => 'required|numeric|min:1|max:100',
            'archivos'          => 'nullable|array|max:10',
            'archivos.*'        => 'file|mimes:' . self::EXTENSIONES . '|max:' . self::MAX_KB,
            'quitar_archivos'   => 'nullable|array',
        ], [
            'archivos.*.mimes' => 'Uno de los archivos tiene un formato no permitido.',
            'archivos.*.max'   => 'Cada archivo puede pesar como máximo 20 MB.',
        ], [
            'titulo'            => 'título',
            'unidad_id'         => 'unidad',
            'fecha_publicacion' => 'fecha de publicación',
            'fecha_limite'      => 'fecha límite',
            'puntaje_maximo'    => 'nota máxima',
        ]);

        $tz = 'America/Argentina/Buenos_Aires';
        $publicacion = ! empty($datos['fecha_publicacion']) ? Carbon::parse($datos['fecha_publicacion'], $tz) : null;
        $limite      = ! empty($datos['fecha_limite']) ? Carbon::parse($datos['fecha_limite'], $tz) : null;

        if ($publicacion && $limite && $limite->lt($publicacion)) {
            throw ValidationException::withMessages([
                'fecha_limite' => 'La fecha límite no puede ser anterior a la de publicación.',
            ]);
        }

        return [
            'titulo'            => $datos['titulo'],
            'unidad_id'         => $datos['unidad_id'] ?? null,
            'consigna'          => $datos['consigna'] ?? null,
            'fecha_publicacion' => $publicacion,
            'fecha_limite'      => $limite,
            'puntaje_maximo'    => $datos['puntaje_maximo'],
            'acepta_tardias'    => $request->boolean('acepta_tardias'),
            'visible'           => $request->boolean('visible'),
        ];
    }

    protected function guardarArchivos(Request $request, Tarea $tarea): void
    {
        foreach ((array) $request->file('archivos', []) as $archivo) {
            if ($archivo && $archivo->isValid()) {
                Archivo::guardar($archivo, $tarea, "aulas/{$tarea->aula_id}/tareas/{$tarea->id}", $request->user()->id);
            }
        }
    }
}
