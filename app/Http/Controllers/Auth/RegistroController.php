<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class RegistroController extends Controller
{
    public function create(Request $request)
    {
        return view('auth.registro', [
            'tipo'   => $request->query('tipo') === 'alumno' ? 'alumno' : 'docente',
            'codigo' => strtoupper((string) $request->query('codigo', '')),
        ]);
    }

    public function store(Request $request)
    {
        // Normalizar antes de validar
        $request->merge([
            'dni'    => $request->filled('dni') ? preg_replace('/\D/', '', $request->dni) : null,
            'email'  => $request->filled('email') ? mb_strtolower(trim($request->email)) : null,
            'codigo' => $request->filled('codigo') ? strtoupper(trim($request->codigo)) : null,
        ]);

        $esAlumno = $request->input('tipo') === 'alumno';

        $datos = $request->validate([
            'tipo'     => 'required|in:docente,alumno',
            'name'     => 'required|string|max:80',
            'apellido' => 'required|string|max:80',
            'email'    => [$esAlumno ? 'required_without:dni' : 'required', 'nullable', 'email', 'max:150', 'unique:users,email'],
            'dni'      => [$esAlumno ? 'required_without:email' : 'nullable', 'nullable', 'digits_between:6,10', 'unique:users,dni'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'codigo'   => 'nullable|string|max:10',
        ], [
            'email.required_without' => 'Ingresá tu email o tu DNI (al menos uno de los dos).',
            'dni.required_without'   => 'Ingresá tu DNI o tu email (al menos uno de los dos).',
            'email.unique'           => 'Ya hay una cuenta con ese email. Probá ingresar o recuperar la contraseña.',
            'dni.unique'             => 'Ya hay una cuenta con ese DNI. Probá ingresar o recuperar la contraseña.',
        ], [
            'name'     => 'nombre',
            'password' => 'contraseña',
            'codigo'   => 'código de aula',
        ]);

        // Código de aula (opcional, solo alumnos)
        $aula = null;
        if ($esAlumno && $datos['codigo']) {
            $aula = Aula::activas()
                ->where('codigo_acceso', $datos['codigo'])
                ->where('codigo_activo', true)
                ->first();

            if (! $aula) {
                return back()->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['codigo' => 'El código de aula no existe o está deshabilitado.']);
            }
        }

        $user = DB::transaction(function () use ($datos, $aula) {
            $user = User::create([
                'name'     => trim($datos['name']),
                'apellido' => trim($datos['apellido']),
                'email'    => $datos['email'],
                'dni'      => $datos['dni'],
                'password' => $datos['password'],
                'activo'   => true,
            ]);

            $user->assignRole($datos['tipo']);

            if ($aula) {
                $aula->miembros()->attach($user->id, [
                    'rol'        => 'alumno',
                    'origen'     => 'codigo',
                    'estado'     => 'activo',
                    'fecha_alta' => Carbon::now('America/Argentina/Buenos_Aires')->toDateString(),
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $mensaje = $aula
            ? "¡Bienvenido/a! Ya estás inscripto/a en {$aula->nombre}."
            : '¡Bienvenido/a al Aula Virtual!';

        return redirect()->route('inicio')->with('exito', $mensaje);
    }
}
