<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecursoController extends Controller
{
    /** Descarga o muestra un archivo (solo miembros del aula). */
    public function descargar(Request $request, Recurso $recurso)
    {
        $clase = $recurso->clase;
        $aula  = $clase?->aula;
        $user  = $request->user();

        abort_unless($aula && $aula->puedeVer($user), 403);
        abort_unless($aula->puedeEditar($user) || $clase->estaPublicada(), 404);
        abort_unless($recurso->archivo_path && Storage::disk('local')->exists($recurso->archivo_path), 404);

        $headers = ['Content-Type' => $recurso->archivo_mime ?: 'application/octet-stream'];

        // PDF e imágenes se abren en el navegador; el resto se descarga
        $enLinea = $request->boolean('ver') && preg_match('~^(application/pdf|image/)~', (string) $recurso->archivo_mime);

        return $enLinea
            ? Storage::disk('local')->response($recurso->archivo_path, $recurso->archivo_nombre, $headers)
            : Storage::disk('local')->download($recurso->archivo_path, $recurso->archivo_nombre, $headers);
    }
}
