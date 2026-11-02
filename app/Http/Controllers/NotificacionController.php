<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = $request->user()->notifications()->paginate(20);

        return view('notificaciones.index', compact('notificaciones'));
    }

    /** Marca como leída y lleva a donde corresponde. */
    public function ir(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();

        $url = (string) ($n->data['url'] ?? '');

        // Solo direcciones del propio sistema
        $esInterna = Str::startsWith($url, '/') || Str::startsWith($url, rtrim(config('app.url'), '/'));

        return redirect($esInterna && $url !== '' ? $url : route('notificaciones.index'));
    }

    public function leerTodas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('exito', 'Todas las notificaciones quedaron como leídas.');
    }

    public function preferencias(Request $request)
    {
        $user = $request->user();
        $user->update(['notificar_email' => $request->boolean('notificar_email')]);

        return back()->with('exito', $user->notificar_email
            ? 'Vas a recibir los avisos importantes también por email.'
            : 'Ya no vas a recibir avisos por email (siguen apareciendo en la campanita).');
    }
}
