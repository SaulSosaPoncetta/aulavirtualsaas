<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AulaController extends Controller
{
    public const COLORES = ['#0F766E', '#2563EB', '#7C3AED', '#DB2777', '#DC2626', '#EA580C', '#CA8A04', '#16A34A', '#475569'];

    // ===================== Listado =====================

    public function index(Request $request)
    {
        $user   = $request->user();
        $estado = $request->query('estado') === 'archivada' ? 'archivada' : 'activa';

        $base = $user->esSuperadmin()
            ? Aula::query()
            : Aula::whereHas('miembros', fn ($q) => $q->where('users.id', $user->id)->where('aula_user.estado', 'activo'));

        $aulas = $base->with(['institucion', 'titular'])
            ->withCount('alumnos')
            ->where('estado', $estado)
            ->orderByDesc('ciclo_lectivo')
            ->orderBy('materia')
            ->get();

        // Rol del usuario en cada aula (docente / alumno)
        $roles = DB::table('aula_user')
            ->where('user_id', $user->id)
            ->where('estado', 'activo')
            ->pluck('rol', 'aula_id');

        return view('aulas.index', compact('aulas', 'roles', 'estado'));
    }

    // ===================== Alta =====================

    public function create(Request $request)
    {
        $this->soloDocentes($request->user());

        return view('aulas.form', [
            'aula'    => new Aula(['ciclo_lectivo' => $this->anioActual(), 'color' => self::COLORES[0]]),
            'colores' => self::COLORES,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->soloDocentes($user);

        $datos = $this->validar($request);

        $aula = DB::transaction(function () use ($datos, $user) {
            $aula = Aula::create($datos + ['docente_id' => $user->id, 'estado' => 'activa']);

            $aula->miembros()->attach($user->id, [
                'rol'        => 'docente',
                'origen'     => 'creador',
                'estado'     => 'activo',
                'fecha_alta' => Carbon::now('America/Argentina/Buenos_Aires')->toDateString(),
            ]);

            return $aula;
        });

        return redirect()->route('aulas.show', $aula)
            ->with('exito', "Aula creada. Compartí el código {$aula->codigo_acceso} con tus alumnos.");
    }

    // ===================== Ver =====================

    public function show(Request $request, Aula $aula)
    {
        $user = $request->user();
        abort_unless($aula->puedeVer($user), 403);

        $puedeEditar = $aula->puedeEditar($user);

        $aula->load(['institucion', 'titular']);

        $unidades = $aula->unidades()->get();
        $clases   = $aula->clases()->with('recursos')->get();
        $tareas   = $aula->tareas()
            ->withCount([
                'entregas',
                'entregas as por_corregir_count' => fn ($q) => $q->where('estado', 'entregada'),
            ])
            ->get();

        // Los alumnos solo ven unidades visibles y contenido publicado (filtro en PHP)
        if (! $puedeEditar) {
            $unidades = $unidades->where('visible', true)->values();
            $clases   = $clases->filter(fn ($c) => $c->estaPublicada())->values();
            $tareas   = $tareas->filter(fn ($t) => $t->estaPublicada())->values();
        }

        $clasesPorUnidad = $clases->groupBy(fn ($c) => $c->unidad_id ?? 0);
        $tareasPorUnidad = $tareas->groupBy(fn ($t) => $t->unidad_id ?? 0);

        // Entregas del alumno, para mostrar el estado de cada tarea
        $misEntregas = $puedeEditar
            ? collect()
            : \App\Models\Entrega::where('alumno_id', $user->id)->whereIn('tarea_id', $tareas->pluck('id'))->get()->keyBy('tarea_id');

        // Próximas entregas pendientes del alumno (para la columna lateral)
        $pendientes = $puedeEditar
            ? collect()
            : $tareas->filter(fn ($t) => $t->fecha_limite && ! $t->estaVencida()
                && (! isset($misEntregas[$t->id]) || $misEntregas[$t->id]->estado === 'devuelta'))
                ->sortBy('fecha_limite')->take(5)->values();

        $docentes = $aula->docentes()->orderBy('apellido')->orderBy('name')->get();
        $alumnos  = $aula->alumnos()->orderBy('apellido')->orderBy('name')->get();

        return view('aulas.show', compact(
            'aula', 'unidades', 'clasesPorUnidad', 'tareasPorUnidad', 'misEntregas', 'pendientes',
            'docentes', 'alumnos', 'puedeEditar'
        ));
    }

    // ===================== Edición =====================

    public function edit(Request $request, Aula $aula)
    {
        $this->autorizarEdicion($request->user(), $aula);

        return view('aulas.form', ['aula' => $aula, 'colores' => self::COLORES]);
    }

    public function update(Request $request, Aula $aula)
    {
        $this->autorizarEdicion($request->user(), $aula);

        $aula->update($this->validar($request));

        return redirect()->route('aulas.show', $aula)->with('exito', 'Los datos del aula se actualizaron.');
    }

    public function archivar(Request $request, Aula $aula)
    {
        $this->autorizarEdicion($request->user(), $aula);

        $archivar = $aula->estado === 'activa';
        $aula->update(['estado' => $archivar ? 'archivada' : 'activa']);

        return redirect()->route('aulas.show', $aula)
            ->with('exito', $archivar ? 'El aula se archivó. Los alumnos la siguen viendo en "Archivadas".' : 'El aula volvió a estar activa.');
    }

    public function destroy(Request $request, Aula $aula)
    {
        $user = $request->user();
        abort_unless($user->esSuperadmin() || $aula->docente_id == $user->id, 403);

        $aula->delete();

        return redirect()->route('inicio')->with('exito', 'El aula se eliminó.');
    }

    // ===================== Código de acceso =====================

    public function regenerarCodigo(Request $request, Aula $aula)
    {
        $this->autorizarEdicion($request->user(), $aula);

        $aula->update([
            'codigo_acceso' => Aula::codigoUnico(fn ($c) => Aula::withTrashed()->where('codigo_acceso', $c)->exists()),
        ]);

        return back()->with('exito', "Nuevo código: {$aula->codigo_acceso}. El anterior ya no funciona.");
    }

    public function alternarCodigo(Request $request, Aula $aula)
    {
        $this->autorizarEdicion($request->user(), $aula);

        $aula->update(['codigo_activo' => ! $aula->codigo_activo]);

        return back()->with('exito', $aula->codigo_activo
            ? 'El código está habilitado: los alumnos pueden unirse.'
            : 'El código está deshabilitado: nadie más puede unirse con él.');
    }

    // ===================== Unirse con código =====================

    public function unirse(Request $request)
    {
        $request->merge(['codigo' => strtoupper(trim((string) $request->codigo))]);
        $datos = $request->validate(['codigo' => 'required|string|max:10'], [], ['codigo' => 'código de aula']);

        $user = $request->user();

        $aula = Aula::activas()->where('codigo_acceso', $datos['codigo'])->first();

        if (! $aula || ! $aula->codigo_activo) {
            return back()->withInput()->with('error', 'El código no existe o está deshabilitado. Revisalo con tu docente.');
        }

        $pivot = DB::table('aula_user')->where('aula_id', $aula->id)->where('user_id', $user->id)->first();

        if ($pivot && $pivot->estado === 'activo') {
            return redirect()->route('aulas.show', $aula)->with('exito', 'Ya estabas en esta aula.');
        }

        $hoy = Carbon::now('America/Argentina/Buenos_Aires')->toDateString();

        if ($pivot) {
            // Estaba dado de baja: se reincorpora
            $aula->miembros()->updateExistingPivot($user->id, ['estado' => 'activo', 'fecha_alta' => $hoy, 'fecha_baja' => null]);
        } else {
            $aula->miembros()->attach($user->id, [
                'rol' => 'alumno', 'origen' => 'codigo', 'estado' => 'activo', 'fecha_alta' => $hoy,
            ]);
        }

        return redirect()->route('aulas.show', $aula)->with('exito', "Te uniste a {$aula->nombre}.");
    }

    // ===================== Miembros =====================

    public function bajaMiembro(Request $request, Aula $aula, User $user)
    {
        $this->autorizarEdicion($request->user(), $aula);

        abort_if($user->id == $aula->docente_id, 422, 'No se puede quitar al docente titular.');

        $aula->miembros()->updateExistingPivot($user->id, [
            'estado'     => 'baja',
            'fecha_baja' => Carbon::now('America/Argentina/Buenos_Aires')->toDateString(),
        ]);

        return back()->with('exito', "{$user->nombre_completo} ya no forma parte del aula.");
    }

    public function salir(Request $request, Aula $aula)
    {
        $user = $request->user();
        abort_if($user->id == $aula->docente_id, 422, 'El docente titular no puede salir de su aula.');

        $aula->miembros()->updateExistingPivot($user->id, [
            'estado'     => 'baja',
            'fecha_baja' => Carbon::now('America/Argentina/Buenos_Aires')->toDateString(),
        ]);

        return redirect()->route('inicio')->with('exito', "Saliste de {$aula->nombre}.");
    }

    // ===================== Ayudas =====================

    protected function validar(Request $request): array
    {
        $datos = $request->validate([
            'materia'       => 'required|string|max:120',
            'curso'         => 'nullable|string|max:30',
            'division'      => 'nullable|string|max:30',
            'turno'         => 'nullable|in:Mañana,Tarde,Vespertino,Noche',
            'escuela'       => 'nullable|string|max:150',
            'ciclo_lectivo' => 'required|integer|min:2020|max:2100',
            'descripcion'   => 'nullable|string|max:2000',
            'color'         => 'required|in:' . implode(',', self::COLORES),
        ], [], [
            'ciclo_lectivo' => 'ciclo lectivo',
            'descripcion'   => 'descripción',
            'division'      => 'división',
        ]);

        return $datos;
    }

    protected function anioActual(): int
    {
        return (int) Carbon::now('America/Argentina/Buenos_Aires')->year;
    }

    protected function soloDocentes(User $user): void
    {
        abort_unless($user->esDocente() || $user->esSuperadmin(), 403, 'Solo los docentes pueden crear aulas.');
    }

    protected function autorizarEdicion(User $user, Aula $aula): void
    {
        abort_unless($aula->puedeEditar($user), 403);
    }
}
