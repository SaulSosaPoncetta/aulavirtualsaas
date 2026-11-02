<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Notificador;
use Illuminate\Support\Facades\DB;
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

    /**
     * Alumno sin email: ingresa su DNI y se avisa a los docentes de sus aulas
     * para que le generen una clave temporal. No revela si el DNI existe.
     */
    public function pedirAyuda(Request $request, Notificador $notificador)
    {
        $request->merge(['dni' => preg_replace('/\D/', '', (string) $request->dni)]);
        $request->validate(['dni' => 'required|digits_between:6,10'], [], ['dni' => 'DNI']);

        $alumno = User::where('dni', $request->dni)->first();

        // Solo para quien no puede recuperarla por email
        if ($alumno && $alumno->activo && ! ($alumno->email && $alumno->email_verified_at)) {
            $aulas = $alumno->aulasComoAlumno()->where('aulas.estado', 'activa')->get();

            foreach ($aulas as $aula) {
                $notificador->enviar($aula->docentes()->get(), [
                    'tipo'   => 'ayuda',
                    'titulo' => 'Un alumno olvidó su contraseña',
                    'texto'  => $alumno->nombre_completo . ' (DNI ' . $alumno->dni . ') pidió ayuda para ingresar. Generale una clave temporal desde la lista de alumnos.',
                    'url'    => route('aulas.show', $aula) . '#alumno' . $alumno->id,
                    'icono'  => 'bi-key',
                    'aula'   => $aula->nombre,
                ], false, 0);
            }

            // Si es alumno de una institución, también a sus administradores
            foreach ($alumno->instituciones()->wherePivot('rol', 'alumno')->get() as $inst) {
                $notificador->enviar($inst->administradores()->wherePivot('activo', true)->get(), [
                    'tipo'   => 'ayuda',
                    'titulo' => 'Un alumno olvidó su contraseña',
                    'texto'  => $alumno->nombre_completo . ' (DNI ' . $alumno->dni . ') pidió ayuda para ingresar.',
                    'url'    => route('instituciones.show', ['institucion' => $inst, 'buscar' => $alumno->dni]),
                    'icono'  => 'bi-key',
                    'aula'   => $inst->nombre,
                ], false, 0);
            }
        }

        return back()->with('exito', 'Listo. Si el DNI está registrado, les avisamos a tus docentes: pediles en la próxima clase tu clave temporal.');
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
