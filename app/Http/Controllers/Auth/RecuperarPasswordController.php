<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaPassword;

/**
 * Recuperación de contraseña por email.
 * Quien se registró solo con DNI la recupera a través de su docente o institución.
 */
class RecuperarPasswordController extends Controller
{
    public function solicitar()
    {
        return view('auth.recuperar');
    }

    public function enviar(Request $request)
    {
        $request->validate(['email' => 'required|email'], [], ['email' => 'email']);

        Password::sendResetLink(['email' => mb_strtolower(trim($request->email))]);

        // Mismo mensaje exista o no la cuenta (no revela qué emails están registrados)
        return back()->with('exito', 'Si el email está registrado, te enviamos un enlace para restablecer la contraseña. Revisá también la carpeta de spam.');
    }

    public function formulario(Request $request, string $token)
    {
        return view('auth.restablecer', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function restablecer(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', ReglaPassword::min(8)->letters()->numbers()],
        ], [], ['password' => 'contraseña']);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'              => $password,
                    'debe_cambiar_password' => false,
                    'remember_token'        => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($estado === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('exito', 'Tu contraseña se restableció. Ya podés ingresar.');
        }

        return back()->withInput($request->only('email'))
            ->with('error', 'El enlace no es válido o ya venció. Pedí uno nuevo.');
    }
}
