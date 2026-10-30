<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitucionController extends Controller
{
    /** Mis instituciones + unirse con código + crear. */
    public function index(Request $request)
    {
        $instituciones = $request->user()->instituciones()
            ->wherePivot('activo', true)
            ->withCount('aulas')
            ->orderBy('nombre')
            ->get()
            ->groupBy('id')
            ->map(fn ($grupo) => [
                'institucion' => $grupo->first(),
                'roles'       => $grupo->pluck('pivot.rol')->unique()->values(),
            ])
            ->values();

        return view('instituciones.index', compact('instituciones'));
    }

    public function create()
    {
        return view('instituciones.form', ['institucion' => new Institucion(['provincia' => 'Buenos Aires'])]);
    }

    public function store(Request $request)
    {
        $user  = $request->user();
        $datos = $this->validar($request);

        $institucion = DB::transaction(function () use ($datos, $user) {
            $institucion = Institucion::create($datos + ['creada_por' => $user->id, 'activo' => true]);
            $institucion->usuarios()->attach($user->id, ['rol' => 'admin', 'activo' => true]);

            return $institucion;
        });

        return redirect()->route('instituciones.show', $institucion)
            ->with('exito', "Institución creada. Compartí el código {$institucion->codigo_invitacion} con tus docentes.");
    }

    /** Panel de la institución (solo administradores). */
    public function show(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $docentes = $institucion->usuarios()
            ->wherePivotIn('rol', ['admin', 'docente'])->wherePivot('activo', true)
            ->orderBy('apellido')->orderBy('name')->get()
            ->groupBy('id')
            ->map(fn ($g) => ['user' => $g->first(), 'roles' => $g->pluck('pivot.rol')->unique()->values()])
            ->values();

        $buscar  = trim((string) $request->query('buscar'));
        $alumnos = $institucion->usuarios()
            ->wherePivot('rol', 'alumno')->wherePivot('activo', true)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('apellido', 'like', "%{$buscar}%")->orWhere('name', 'like', "%{$buscar}%")
                ->orWhere('dni', 'like', '%' . preg_replace('/\D/', '', $buscar) . '%')->orWhere('email', 'like', "%{$buscar}%")))
            ->orderBy('apellido')->orderBy('name')
            ->paginate(25)->withQueryString();

        $aulas = $institucion->aulas()->with('titular')->withCount('alumnos')
            ->orderByDesc('ciclo_lectivo')->orderBy('materia')->get();

        return view('instituciones.show', compact('institucion', 'docentes', 'alumnos', 'aulas', 'buscar'));
    }

    public function edit(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        return view('instituciones.form', compact('institucion'));
    }

    public function update(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $institucion->update($this->validar($request));

        return redirect()->route('instituciones.show', $institucion)->with('exito', 'Datos de la institución actualizados.');
    }

    public function regenerarCodigo(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $institucion->update([
            'codigo_invitacion' => Aula::codigoUnico(fn ($c) => Institucion::where('codigo_invitacion', $c)->exists(), 8),
        ]);

        return back()->with('exito', "Nuevo código de invitación: {$institucion->codigo_invitacion}. El anterior ya no funciona.");
    }

    /** Hacer administrador a un docente (o quitarle la administración). */
    public function alternarAdmin(Request $request, Institucion $institucion, User $user)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $esAdmin = $institucion->usuarios()->where('users.id', $user->id)->wherePivot('rol', 'admin')->exists();

        if ($esAdmin) {
            if ($institucion->administradores()->wherePivot('activo', true)->count() <= 1) {
                return back()->with('error', 'La institución tiene que tener al menos un administrador.');
            }
            DB::table('institucion_user')->where('institucion_id', $institucion->id)->where('user_id', $user->id)->where('rol', 'admin')->delete();

            // Sigue como docente
            if (! $institucion->usuarios()->where('users.id', $user->id)->wherePivot('rol', 'docente')->exists()) {
                $institucion->usuarios()->attach($user->id, ['rol' => 'docente', 'activo' => true]);
            }

            return back()->with('exito', "{$user->nombre_completo} ya no es administrador.");
        }

        $institucion->usuarios()->attach($user->id, ['rol' => 'admin', 'activo' => true]);

        return back()->with('exito', "{$user->nombre_completo} ahora es administrador.");
    }

    /** Quitar un docente o alumno de la institución. */
    public function quitarMiembro(Request $request, Institucion $institucion, User $user, string $rol)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        if ($rol === 'docente') {
            if ($institucion->usuarios()->where('users.id', $user->id)->wherePivot('rol', 'admin')->exists()) {
                return back()->with('error', 'Primero quitale la administración.');
            }
        }

        DB::table('institucion_user')
            ->where('institucion_id', $institucion->id)->where('user_id', $user->id)->where('rol', $rol)
            ->delete();

        return back()->with('exito', "{$user->nombre_completo} ya no figura en la institución. Sus aulas y su cuenta no se borran.");
    }

    /** Restablecer la contraseña de un alumno a su DNI (con cambio obligatorio al ingresar). */
    public function restablecerPassword(Request $request, Institucion $institucion, User $user)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);
        abort_unless($institucion->esAlumno($user), 404);

        if (! $user->dni) {
            return back()->with('error', "{$user->nombre_completo} no tiene DNI cargado: no se puede restablecer.");
        }

        $user->forceFill(['password' => $user->dni, 'debe_cambiar_password' => true])->save();

        return back()->with('exito', "La contraseña de {$user->nombre_completo} ahora es su DNI. Al ingresar va a tener que cambiarla.");
    }

    /** Un docente se suma a una institución con el código de invitación. */
    public function unirse(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esDocente(), 403, 'Solo los docentes pueden sumarse a una institución.');

        $request->merge(['codigo' => strtoupper(trim((string) $request->codigo))]);
        $request->validate(['codigo' => 'required|string|max:10'], [], ['codigo' => 'código de invitación']);

        $institucion = Institucion::where('codigo_invitacion', $request->codigo)->where('activo', true)->first();

        if (! $institucion) {
            return back()->withInput()->with('error', 'El código no corresponde a ninguna institución. Revisalo con la escuela.');
        }

        if ($institucion->esDocente($user)) {
            return back()->with('exito', "Ya formabas parte de {$institucion->nombre}.");
        }

        // Cada docente ocupa un lugar del abono de la institución
        $abono = $institucion->abono;
        if (! $abono || ! $abono->estaActivo()) {
            return back()->with('error', "{$institucion->nombre} todavía no tiene el abono activo. Avisale al administrador.");
        }
        if (! $abono->hayCupo()) {
            return back()->with('error', "{$institucion->nombre} completó su cupo de docentes. El administrador tiene que ampliarlo para que puedas sumarte.");
        }

        $institucion->usuarios()->attach($user->id, ['rol' => 'docente', 'activo' => true]);

        return redirect()->route('instituciones.index')
            ->with('exito', "Te sumaste a {$institucion->nombre}. Ahora podés crear aulas de esta institución.");
    }

    public function salir(Request $request, Institucion $institucion)
    {
        $user = $request->user();

        if ($institucion->usuarios()->where('users.id', $user->id)->wherePivot('rol', 'admin')->exists()) {
            return back()->with('error', 'Sos administrador: primero pasale la administración a otro docente.');
        }

        DB::table('institucion_user')
            ->where('institucion_id', $institucion->id)->where('user_id', $user->id)->where('rol', 'docente')
            ->delete();

        return redirect()->route('instituciones.index')->with('exito', "Saliste de {$institucion->nombre}. Tus aulas no se borran.");
    }

    // ===================== Ayudas =====================

    public static function reglas(string $prefijo = ''): array
    {
        return [
            $prefijo . 'nombre'    => 'required|string|max:150',
            $prefijo . 'cue'       => 'nullable|string|max:20',
            $prefijo . 'nivel'     => 'nullable|in:' . implode(',', array_keys(Institucion::NIVELES)),
            $prefijo . 'localidad' => 'nullable|string|max:100',
            $prefijo . 'provincia' => 'nullable|string|max:60',
            $prefijo . 'telefono'  => 'nullable|string|max:40',
            $prefijo . 'email'     => 'nullable|email|max:150',
        ];
    }

    protected function validar(Request $request): array
    {
        return $request->validate(self::reglas(), [], ['nombre' => 'nombre de la institución']);
    }
}
