<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Entrega;
use App\Models\Tarea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArchivoController extends Controller
{
    /** Descarga o muestra un archivo de tarea o de entrega, controlando permisos. */
    public function descargar(Request $request, Archivo $archivo)
    {
        $user   = $request->user();
        $duenio = $archivo->archivable;

        if ($duenio instanceof Tarea) {
            $aula = $duenio->aula;
            abort_unless($aula && $aula->puedeVer($user), 403);
            abort_unless($aula->puedeEditar($user) || $duenio->estaPublicada(), 404);
        } elseif ($duenio instanceof Entrega) {
            $aula = $duenio->tarea?->aula;
            abort_unless($aula && ($duenio->alumno_id == $user->id || $aula->puedeEditar($user)), 403);
        } else {
            abort(404);
        }

        abort_unless(Storage::disk('local')->exists($archivo->path), 404);

        $headers = ['Content-Type' => $archivo->mime ?: 'application/octet-stream'];

        return $request->boolean('ver') && $archivo->se_ve_en_linea
            ? Storage::disk('local')->response($archivo->path, $archivo->nombre, $headers)
            : Storage::disk('local')->download($archivo->path, $archivo->nombre, $headers);
    }
}
