<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerificacionEmailController extends Controller
{
    /** Pantalla "revisá tu email". */
    public function aviso(Request $request)
    {
        if (! $request->user()->necesitaVerificarEmail()) {
            return redirect()->route('inicio');
        }

        return view('auth.verificar-email');
    }

    /** Link del mail. Funciona aunque se abra en otro dispositivo sin sesión (está firmado). */
    public function verificar(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless($user->email && hash_equals(sha1($user->getEmailForVerification()), $hash), 403, 'El enlace no es válido.');

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $destino = $request->user() ? route('inicio') : route('login');

        return redirect($destino)->with('exito', '¡Listo! Tu email quedó confirmado.');
    }

    public function reenviar(Request $request)
    {
        $user = $request->user();

        if (! $user->necesitaVerificarEmail()) {
            return redirect()->route('inicio');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('exito', "Te enviamos un nuevo enlace a {$user->email}.");
    }
}
