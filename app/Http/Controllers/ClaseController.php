<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Clase;
use App\Models\Recurso;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaseController extends Controller
{
    /** Tipos de archivo permitidos y tamaño máximo (KB) */
    public const EXTENSIONES = 'pdf,doc,docx,odt,xls,xlsx,ods,csv,ppt,pptx,odp,txt,jpg,jpeg,png,gif,webp,zip,rar,7z,mp3,wav,ogg,m4a,mp4,webm,mov';
    public const MAX_KB      = 20480; // 20 MB

    public function create(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        return view('clases.form', [
            'aula'     => $aula,
            'clase'    => new Clase(['unidad_id' => $request->query('unidad'), 'visible' => true]),
            'unidades' => $aula->unidades()->get(),
        ]);
    }

    public function store(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $datos = $this->validar($request, $aula);

        $clase = DB::transaction(function () use ($request, $aula, $datos) {
            $clase = $aula->clases()->create($datos + [
                'user_id' => $request->user()->id,
                'orden'   => (int) $aula->clases()->where('unidad_id', $datos['unidad_id'])->max('orden') + 1,
            ]);

            $this->guardarRecursos($request, $clase);

            return $clase;
        });

        return redirect()->route('clases.show', $clase)->with('exito', 'Clase publicada.');
    }

    public function show(Request $request, Clase $clase)
    {
        $aula = $clase->aula;
        $user = $request->user();

        abort_unless($aula->puedeVer($user), 403);

        $puedeEditar = $aula->puedeEditar($user);
        abort_unless($puedeEditar || $clase->estaPublicada(), 404);

        $clase->load(['recursos', 'unidad', 'autor']);

        return view('clases.show', compact('clase', 'aula', 'puedeEditar'));
    }

    public function edit(Request $request, Clase $clase)
    {
        abort_unless($clase->aula->puedeEditar($request->user()), 403);

        $clase->load('recursos');

        return view('clases.form', [
            'aula'     => $clase->aula,
            'clase'    => $clase,
            'unidades' => $clase->aula->unidades()->get(),
        ]);
    }

    public function update(Request $request, Clase $clase)
    {
        $aula = $clase->aula;
        abort_unless($aula->puedeEditar($request->user()), 403);

        $datos = $this->validar($request, $aula);

        DB::transaction(function () use ($request, $clase, $datos) {
            $clase->update($datos);

            // Recursos marcados para quitar
            $quitar = array_map('intval', (array) $request->input('quitar_recursos', []));
            if ($quitar) {
                $clase->recursos()->whereIn('id', $quitar)->get()->each->delete();
            }

            $this->guardarRecursos($request, $clase);
        });

        return redirect()->route('clases.show', $clase)->with('exito', 'Clase actualizada.');
    }

    public function destroy(Request $request, Clase $clase)
    {
        $aula = $clase->aula;
        abort_unless($aula->puedeEditar($request->user()), 403);

        DB::transaction(function () use ($clase) {
            $clase->recursos()->get()->each->delete(); // borra también los archivos
            $clase->delete();
        });

        return redirect()->route('aulas.show', $aula)->with('exito', 'Clase eliminada.');
    }

    // ===================== Ayudas =====================

    protected function validar(Request $request, Aula $aula): array
    {
        $datos = $request->validate([
            'titulo'            => 'required|string|max:200',
            'unidad_id'         => 'nullable|integer|exists:unidades,id,aula_id,' . $aula->id,
            'contenido'         => 'nullable|string|max:60000',
            'fecha_publicacion' => 'nullable|date',
            'archivos'          => 'nullable|array|max:10',
            'archivos.*'        => 'file|mimes:' . self::EXTENSIONES . '|max:' . self::MAX_KB,
            'enlaces'           => 'nullable|array|max:20',
            'enlaces.*.url'     => 'nullable|url|max:1000',
            'enlaces.*.titulo'  => 'nullable|string|max:200',
            'quitar_recursos'   => 'nullable|array',
        ], [
            'archivos.*.mimes' => 'Uno de los archivos tiene un formato no permitido.',
            'archivos.*.max'   => 'Cada archivo puede pesar como máximo 20 MB.',
            'enlaces.*.url.url' => 'Uno de los enlaces no es una dirección válida (tiene que empezar con http:// o https://).',
        ], [
            'titulo'            => 'título',
            'unidad_id'         => 'unidad',
            'fecha_publicacion' => 'fecha de publicación',
        ]);

        return [
            'titulo'            => $datos['titulo'],
            'unidad_id'         => $datos['unidad_id'] ?? null,
            'contenido'         => $datos['contenido'] ?? null,
            'fecha_publicacion' => ! empty($datos['fecha_publicacion'])
                ? Carbon::parse($datos['fecha_publicacion'], 'America/Argentina/Buenos_Aires')
                : null,
            'visible'           => $request->boolean('visible'),
        ];
    }

    protected function guardarRecursos(Request $request, Clase $clase): void
    {
        $orden = (int) $clase->recursos()->max('orden');

        foreach ((array) $request->file('archivos', []) as $archivo) {
            if (! $archivo || ! $archivo->isValid()) {
                continue;
            }

            $clase->recursos()->create([
                'tipo'           => 'archivo',
                'archivo_path'   => $archivo->store("aulas/{$clase->aula_id}/clases/{$clase->id}", 'local'),
                'archivo_nombre' => $archivo->getClientOriginalName(),
                'archivo_mime'   => $archivo->getClientMimeType(),
                'archivo_tamano' => $archivo->getSize(),
                'orden'          => ++$orden,
            ]);
        }

        foreach ((array) $request->input('enlaces', []) as $enlace) {
            $url = trim((string) ($enlace['url'] ?? ''));
            if ($url === '') {
                continue;
            }

            $recurso = new Recurso(['url' => $url]);

            $clase->recursos()->create([
                'tipo'   => $recurso->youtube_id ? 'video' : 'enlace',
                'titulo' => trim((string) ($enlace['titulo'] ?? '')) ?: null,
                'url'    => $url,
                'orden'  => ++$orden,
            ]);
        }
    }
}
