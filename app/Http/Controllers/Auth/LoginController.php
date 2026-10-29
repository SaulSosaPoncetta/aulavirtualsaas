<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'usuario'  => 'required|string|max:150',
            'password' => 'required|string|max:120',
        ], [], [
            'usuario'  => 'email o DNI',
            'password' => 'contraseña',
        ]);

        // Límite: 5 intentos por minuto por usuario + IP
        $clave = Str::lower(trim($datos['usuario'])) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $seg = RateLimiter::availableIn($clave);
            return back()->withInput($request->only('usuario'))
                ->with('error', "Demasiados intentos. Probá de nuevo en {$seg} segundos.");
        }

        $user = User::buscarPorUsuario($datos['usuario']);

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            RateLimiter::hit($clave, 60);
            return back()->withInput($request->only('usuario'))
                ->with('error', 'El email/DNI o la contraseña no son correctos.');
        }

        if (! $user->activo) {
            return back()->withInput($request->only('usuario'))
                ->with('error', 'Tu usuario está deshabilitado. Consultá con tu docente o institución.');
        }

        RateLimiter::clear($clave);

        Auth::login($user, $request->boolean('recordar'));
        $request->session()->regenerate();

        $user->forceFill(['ultimo_acceso_at' => Carbon::now('America/Argentina/Buenos_Aires')])->save();

        if ($user->debe_cambiar_password) {
            return redirect()->route('password.cambiar')
                ->with('exito', 'Antes de continuar, elegí una contraseña nueva.');
        }

        return redirect()->intended(route('inicio'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('exito', 'Cerraste sesión correctamente.');
    }
}
