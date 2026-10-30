<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\InstitucionController;
use App\Models\Aula;
use App\Models\Institucion;
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
            'tipo'   => in_array($request->query('tipo'), ['alumno', 'institucion']) ? $request->query('tipo') : 'docente',
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

        $esAlumno      = $request->input('tipo') === 'alumno';
        $esInstitucion = $request->input('tipo') === 'institucion';

        // Datos de la institución (solo si se registra una institución)
        $datosInstitucion = $esInstitucion
            ? $request->validate(InstitucionController::reglas('inst_'), [], ['inst_nombre' => 'nombre de la institución'])
            : [];

        $datos = $request->validate([
            'tipo'     => 'required|in:docente,alumno,institucion',
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

        $institucion = null;

        $user = DB::transaction(function () use ($datos, $aula, $datosInstitucion, &$institucion) {
            $user = User::create([
                'name'     => trim($datos['name']),
                'apellido' => trim($datos['apellido']),
                'email'    => $datos['email'],
                'dni'      => $datos['dni'],
                'password' => $datos['password'],
                'activo'   => true,
            ]);

            if ($datos['tipo'] === 'institucion') {
                // Quien registra la institución queda como administrador (y puede dar clases)
                $user->assignRole('docente');

                $institucion = Institucion::create([
                    'nombre'     => $datosInstitucion['inst_nombre'],
                    'cue'        => $datosInstitucion['inst_cue'] ?? null,
                    'nivel'      => $datosInstitucion['inst_nivel'] ?? null,
                    'localidad'  => $datosInstitucion['inst_localidad'] ?? null,
                    'provincia'  => $datosInstitucion['inst_provincia'] ?? 'Buenos Aires',
                    'telefono'   => $datosInstitucion['inst_telefono'] ?? null,
                    'email'      => $datosInstitucion['inst_email'] ?? null,
                    'creada_por' => $user->id,
                    'activo'     => true,
                ]);
                $institucion->usuarios()->attach($user->id, ['rol' => 'admin', 'activo' => true]);
            } else {
                $user->assignRole($datos['tipo']);
            }

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

        if ($institucion) {
            return redirect()->route('instituciones.show', $institucion)
                ->with('exito', "¡Listo! Compartí el código {$institucion->codigo_invitacion} con tus docentes para que se sumen.");
        }

        $mensaje = $aula
            ? "¡Bienvenido/a! Ya estás inscripto/a en {$aula->nombre}."
            : '¡Bienvenido/a al Aula Virtual!';

        return redirect()->route('inicio')->with('exito', $mensaje);
    }
}
