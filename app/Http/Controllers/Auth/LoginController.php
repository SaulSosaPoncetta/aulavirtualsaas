<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AutenticacionService;
use App\Services\Integraciones\GestionAulaClient;
use App\Services\Integraciones\SgiClient;
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

    public function store(
        Request $request,
        GestionAulaClient $gestionAula,
        SgiClient $sgi,
        AutenticacionService $auth
    ) {
        $datos = $request->validate([
            'tipo'     => 'required|in:docente,alumno',
            'usuario'  => 'required|string|max:120',
            'password' => 'required|string|max:120',
        ], [], [
            'usuario'  => $request->tipo === 'alumno' ? 'DNI' : 'email',
            'password' => 'contraseña',
        ]);

        $usuario = trim($datos['usuario']);
        if ($datos['tipo'] === 'alumno') {
            $usuario = preg_replace('/\D/', '', $usuario);
        }

        // Límite de intentos: 5 por minuto
        $clave = Str::lower($datos['tipo'] . '|' . $usuario . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $seg = RateLimiter::availableIn($clave);
            return back()->withInput($request->except('password'))
                ->with('error', "Demasiados intentos. Probá de nuevo en {$seg} segundos.");
        }

        // 1) Cuenta local con contraseña propia (superadmin o alumno sin SGI)
        $local = $datos['tipo'] === 'alumno'
            ? User::where('dni', $usuario)->whereNotNull('password')->first()
            : User::where('email', $usuario)->whereNotNull('password')->first();

        if ($local && Hash::check($datos['password'], $local->password)) {
            return $this->ingresar($request, $local, $clave);
        }

        // 2) Docente contra GestiónAula
        if ($datos['tipo'] === 'docente') {
            $res = $gestionAula->login($usuario, $datos['password']);

            if (! $res['ok']) {
                RateLimiter::hit($clave, 60);
                return back()->withInput($request->except('password'))->with('error', $res['mensaje']);
            }

            $user = $auth->sincronizarDocente($res['docente']);
            $request->session()->put('gestionaula_token', $res['token']);

            return $this->ingresar($request, $user, $clave);
        }

        // 3) Alumno contra el SGI
        $res = $sgi->loginAlumno($usuario, $datos['password']);

        if (! $res['ok']) {
            RateLimiter::hit($clave, 60);
            return back()->withInput($request->except('password'))->with('error', $res['mensaje']);
        }

        $user = $auth->sincronizarAlumno($res['alumno'], $res['institucion']);

        return $this->ingresar($request, $user, $clave);
    }

    protected function ingresar(Request $request, User $user, string $clave)
    {
        if (! $user->activo) {
            return back()->withInput($request->except('password'))
                ->with('error', 'Tu usuario está deshabilitado. Consultá con la institución.');
        }

        $token = $request->session()->get('gestionaula_token');

        Auth::login($user, $request->boolean('recordar'));
        $request->session()->regenerate();

        // regenerate() conserva los datos; se vuelve a asegurar el token
        if ($token) {
            $request->session()->put('gestionaula_token', $token);
        }

        RateLimiter::clear($clave);

        $user->forceFill(['ultimo_acceso_at' => Carbon::now('America/Argentina/Buenos_Aires')])->save();

        return redirect()->intended(route('inicio'));
    }

    public function destroy(Request $request, GestionAulaClient $gestionAula)
    {
        $gestionAula->logout($request->session()->get('gestionaula_token'));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('exito', 'Cerraste sesión correctamente.');
    }
}
