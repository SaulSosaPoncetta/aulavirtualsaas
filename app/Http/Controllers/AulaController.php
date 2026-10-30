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
        $user = $request->user();
        $this->soloDocentes($user);

        // Necesita abono personal al día o una institución con abono activo
        $institucionesActivas = $user->institucionesDocente()->with('abono')->get()->unique('id')->filter->abonoActivo();
        if (! $user->abonoActivo() && $institucionesActivas->isEmpty()) {
            return redirect()->route('abonos.mio')
                ->with('error', 'Para crear aulas necesitás un abono activo (o sumarte a una institución que tenga abono).');
        }

        return view('aulas.form', [
            'instituciones' => $institucionesActivas->sortBy('nombre')->values(),
            'personalActivo' => $user->abonoActivo(),
            'aula'    => new Aula([
                'institucion_id'   => $request->query('institucion'),
                'ciclo_lectivo'    => $this->anioActual(),
                'color'            => self::COLORES[0],
                'alumnos_publican' => false,
                'alumnos_comentan' => true,
            ]),
            'colores' => self::COLORES,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->soloDocentes($user);

        $datos = $this->validar($request, $user);

        if ($error = $this->verificarAbonoParaCrear($user, $datos['institucion_id'])) {
            return back()->withInput()->with('error', $error);
        }

        $aula = DB::transaction(function () use ($datos, $user) {
            // Un administrador que crea un aula pasa a ocupar un lugar de docente
            if ($datos['institucion_id']) {
                $inst = \App\Models\Institucion::find($datos['institucion_id']);
                if (! $inst->ocupaLugar($user)) {
                    $inst->usuarios()->attach($user->id, ['rol' => 'docente', 'activo' => true]);
                }
            }

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

        $aula->load(['institucion.abono', 'titular.abono']);
        $puedeEscribir = $user->esSuperadmin() || $aula->escrituraHabilitada();

        $unidades = $aula->unidades()->get();
        $clases   = $aula->clases()->with('recursos')->get();
        $tareas   = $aula->tareas()
            ->withCount([
                'entregas',
                'entregas as por_corregir_count' => fn ($q) => $q->where('estado', 'entregada'),
            ])
            ->get();

        $evaluaciones = $aula->evaluaciones()
            ->withCount([
                'preguntas',
                'intentos as por_revisar_count' => fn ($q) => $q->where('estado', 'pendiente_revision'),
            ])
            ->get();

        // Los alumnos solo ven unidades visibles y contenido publicado (filtro en PHP)
        if (! $puedeEditar) {
            $evaluaciones = $evaluaciones->filter(fn ($e) => $e->estaPublicada())->values();
            $unidades = $unidades->where('visible', true)->values();
            $clases   = $clases->filter(fn ($c) => $c->estaPublicada())->values();
            $tareas   = $tareas->filter(fn ($t) => $t->estaPublicada())->values();
        }

        $clasesPorUnidad = $clases->groupBy(fn ($c) => $c->unidad_id ?? 0);
        $tareasPorUnidad = $tareas->groupBy(fn ($t) => $t->unidad_id ?? 0);
        $evaluacionesPorUnidad = $evaluaciones->groupBy(fn ($e) => $e->unidad_id ?? 0);

        // Cantidad de alumnos que rindieron cada evaluación (para el docente)
        $rindieron = $puedeEditar
            ? DB::table('intentos')
                ->whereIn('evaluacion_id', $evaluaciones->pluck('id'))
                ->where('estado', '!=', 'en_curso')
                ->selectRaw('evaluacion_id, COUNT(DISTINCT alumno_id) AS total')
                ->groupBy('evaluacion_id')
                ->pluck('total', 'evaluacion_id')
            : collect();

        // Intentos del alumno: mejor nota y estado por evaluación
        $misIntentos = $puedeEditar
            ? collect()
            : \App\Models\Intento::where('alumno_id', $user->id)
                ->whereIn('evaluacion_id', $evaluaciones->pluck('id'))
                ->get()->groupBy('evaluacion_id');

        // Entregas del alumno, para mostrar el estado de cada tarea
        $misEntregas = $puedeEditar
            ? collect()
            : \App\Models\Entrega::where('alumno_id', $user->id)->whereIn('tarea_id', $tareas->pluck('id'))->get()->keyBy('tarea_id');

        // Próximas entregas pendientes del alumno (para la columna lateral)
        $pendientes = $puedeEditar
            ? collect()
            : $tareas->filter(fn ($t) => $t->fecha_limite && ! $t->estaVencida()
                && (! isset($misEntregas[$t->id]) || $misEntregas[$t->id]->estado === 'devuelta'))
                ->map(fn ($t) => ['titulo' => $t->titulo, 'fecha' => $t->fecha_limite, 'url' => route('tareas.show', $t), 'tipo' => 'Tarea'])
                ->concat($evaluaciones->filter(fn ($e) => $e->fecha_cierre && ! $e->estaCerrada() && ! isset($misIntentos[$e->id]))
                    ->map(fn ($e) => ['titulo' => $e->titulo, 'fecha' => $e->fecha_cierre, 'url' => route('evaluaciones.show', $e), 'tipo' => 'Evaluación']))
                ->sortBy(fn ($x) => $x['fecha']->getTimestamp())->take(5)->values();

        // Muro: último aviso (fijado o más reciente) y cantidad de novedades
        $ultimoAviso = $aula->publicaciones()->with('autor')
            ->orderByDesc('fijada')->orderByDesc('created_at')->first();

        $vistoAt = DB::table('aula_user')->where('aula_id', $aula->id)->where('user_id', $user->id)->value('muro_visto_at');
        $nuevosMuro = $aula->publicaciones()
            ->where('user_id', '!=', $user->id)
            ->when($vistoAt, fn ($q) => $q->where('created_at', '>', $vistoAt))
            ->count();

        // Alumnos de la institución que todavía no están en el aula (para agregarlos)
        $alumnosInstitucion = collect();
        if ($puedeEditar && $aula->institucion_id) {
            $enAula = DB::table('aula_user')->where('aula_id', $aula->id)->where('estado', 'activo')->pluck('user_id');
            $alumnosInstitucion = $aula->institucion->alumnos()->wherePivot('activo', true)
                ->whereNotIn('users.id', $enAula)
                ->orderBy('apellido')->orderBy('name')->get();
        }

        $docentes = $aula->docentes()->orderBy('apellido')->orderBy('name')->get();
        $alumnos  = $aula->alumnos()->orderBy('apellido')->orderBy('name')->get();

        return view('aulas.show', compact(
            'aula', 'unidades', 'clasesPorUnidad', 'tareasPorUnidad', 'evaluacionesPorUnidad',
            'misEntregas', 'misIntentos', 'rindieron', 'pendientes', 'ultimoAviso', 'nuevosMuro', 'alumnosInstitucion', 'puedeEscribir',
            'docentes', 'alumnos', 'puedeEditar'
        ));
    }

    // ===================== Edición =====================

    public function edit(Request $request, Aula $aula)
    {
        $this->autorizarEdicion($request->user(), $aula);

        return view('aulas.form', [
            'aula'          => $aula,
            'colores'       => self::COLORES,
            'instituciones'  => $this->institucionesPermitidas($request->user(), $aula),
            'personalActivo' => true,
        ]);
    }

    public function update(Request $request, Aula $aula)
    {
        $this->autorizarEdicion($request->user(), $aula);

        $aula->update($this->validar($request, $request->user(), $aula));

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

        if (! $aula->escrituraHabilitada()) {
            return back()->withInput()->with('error', 'Esa aula está en modo solo lectura por falta de abono. Avisale a tu docente.');
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

    /** Inscribir alumnos de la institución del aula. */
    public function agregarAlumnos(Request $request, Aula $aula)
    {
        $this->autorizarEdicion($request->user(), $aula);
        abort_unless($aula->institucion_id, 422, 'El aula no pertenece a una institución.');

        $ids = array_map('intval', (array) $request->input('alumnos', []));

        if (! $ids) {
            return back()->with('error', 'No seleccionaste ningún alumno.');
        }

        // Solo alumnos que realmente son de la institución
        $validos = DB::table('institucion_user')
            ->where('institucion_id', $aula->institucion_id)->where('rol', 'alumno')->where('activo', true)
            ->whereIn('user_id', $ids)->pluck('user_id');

        $hoy = Carbon::now('America/Argentina/Buenos_Aires')->toDateString();
        $agregados = 0;

        foreach ($validos as $id) {
            $pivot = DB::table('aula_user')->where('aula_id', $aula->id)->where('user_id', $id)->first();

            if (! $pivot) {
                $aula->miembros()->attach($id, ['rol' => 'alumno', 'origen' => 'institucion', 'estado' => 'activo', 'fecha_alta' => $hoy]);
                $agregados++;
            } elseif ($pivot->estado !== 'activo') {
                $aula->miembros()->updateExistingPivot($id, ['estado' => 'activo', 'fecha_alta' => $hoy, 'fecha_baja' => null]);
                $agregados++;
            }
        }

        return back()->with('exito', $agregados === 1 ? 'Se agregó 1 alumno al aula.' : "Se agregaron {$agregados} alumnos al aula.");
    }

    // ===================== Ayudas =====================

    protected function validar(Request $request, User $user, ?Aula $aula = null): array
    {
        $permitidas = $this->institucionesPermitidas($user, $aula)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $datos = $request->validate([
            'institucion_id' => ['nullable', 'integer', function ($attr, $valor, $fallar) use ($permitidas) {
                if ($valor && ! in_array((int) $valor, $permitidas, true)) {
                    $fallar('No formás parte de esa institución.');
                }
            }],
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

        $datos['institucion_id'] = ! empty($datos['institucion_id']) ? (int) $datos['institucion_id'] : null;

        return $datos + [
            'alumnos_publican' => $request->boolean('alumnos_publican'),
            'alumnos_comentan' => $request->boolean('alumnos_comentan'),
        ];
    }

    protected function anioActual(): int
    {
        return (int) Carbon::now('America/Argentina/Buenos_Aires')->year;
    }

    protected function soloDocentes(User $user): void
    {
        abort_unless($user->esDocente() || $user->esSuperadmin(), 403, 'Solo los docentes pueden crear aulas.');
    }

    /** Devuelve un mensaje de error si no puede crear el aula por el abono, o null si puede. */
    protected function verificarAbonoParaCrear(User $user, ?int $institucionId): ?string
    {
        if ($user->esSuperadmin()) {
            return null;
        }

        if (! $institucionId) {
            return $user->abonoActivo()
                ? null
                : 'Para crear aulas personales necesitás tu abono unipersonal activo.';
        }

        $inst = \App\Models\Institucion::with('abono')->find($institucionId);

        if (! $inst->abonoActivo()) {
            return "El abono de {$inst->nombre} no está activo. Avisale al administrador de la institución.";
        }

        if (! $inst->ocupaLugar($user) && ! $inst->abono->hayCupo()) {
            return "{$inst->nombre} no tiene lugares de docente disponibles. El administrador tiene que ampliar el cupo.";
        }

        return null;
    }

    /** Instituciones que el docente puede asignar al aula (las suyas + la actual del aula). */
    protected function institucionesPermitidas(User $user, ?Aula $aula = null)
    {
        $lista = $user->institucionesDocente()->orderBy('nombre')->get()->unique('id')->values();

        if ($aula && $aula->institucion && ! $lista->contains('id', $aula->institucion_id)) {
            $lista->push($aula->institucion);
        }

        return $lista;
    }

    protected function autorizarEdicion(User $user, Aula $aula): void
    {
        abort_unless($aula->puedeEditar($user), 403);
    }
}
