<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Subida de imágenes desde el editor de texto (solo docentes).
 * Se guardan en el disco público para poder mostrarlas dentro del contenido.
 */
class EditorController extends Controller
{
    public function imagen(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esDocente() || $user->esSuperadmin(), 403);

        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ], [
            'imagen.max' => 'La imagen puede pesar como máximo 4 MB.',
        ]);

        $path = $request->file('imagen')->store('editor/' . $user->id . '/' . now()->format('Y-m'), 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }
}
