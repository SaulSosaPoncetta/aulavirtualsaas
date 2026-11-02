<?php

namespace App\Services;

use App\Models\Aula;
use App\Models\Entrega;
use App\Models\Evaluacion;
use App\Models\Intento;
use App\Models\Tarea;
use App\Models\Clase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pendientes y eventos de calendario de un usuario, juntando todas sus aulas.
 */
class AgendaService
{
    public static function ahora(): Carbon
    {
        return Carbon::now('America/Argentina/Buenos_Aires');
    }

    /** ids de aulas activas donde participa con ese rol. */
    public function aulasIds(User $user, string $rol): Collection
    {
        return DB::table('aula_user')
            ->join('aulas', 'aulas.id', '=', 'aula_user.aula_id')
            ->where('aula_user.user_id', $user->id)
            ->where('aula_user.rol', $rol)
            ->where('aula_user.estado', 'activo')
            ->where('aulas.estado', 'activa')
            ->whereNull('aulas.deleted_at')
            ->pluck('aulas.id');
    }

    // ===================== Alumno =====================

    public function pendientesAlumno(User $user): array
    {
        $aulas = $this->aulasIds($user, 'alumno');

        $tareas = Tarea::with('aula')->whereIn('aula_id', $aulas)->where('visible', true)->get()
            ->filter(fn ($t) => $t->estaPublicada());

        $entregas = Entrega::where('alumno_id', $user->id)->whereIn('tarea_id', $tareas->pluck('id'))->get()->keyBy('tarea_id');

        $porEntregar = $tareas->filter(fn ($t) => ! isset($entregas[$t->id]) && $t->aceptaEntregas())
            ->sortBy(fn ($t) => $t->fecha_limite?->getTimestamp() ?? PHP_INT_MAX)->values();

        $paraRehacer = $tareas->filter(fn ($t) => ($entregas[$t->id] ?? null)?->estado === 'devuelta')->values();

        $evaluaciones = Evaluacion::with('aula')->whereIn('aula_id', $aulas)->where('visible', true)
            ->whereHas('preguntas')->get()
            ->filter(fn ($e) => $e->estaPublicada() && ! $e->estaCerrada());

        $intentos = Intento::where('alumno_id', $user->id)->whereIn('evaluacion_id', $evaluaciones->pluck('id'))->get()->groupBy('evaluacion_id');

        $porRendir = $evaluaciones->filter(function ($e) use ($intentos) {
            $mios = $intentos[$e->id] ?? collect();

            return $mios->contains('estado', 'en_curso') || $mios->isEmpty();
        })->sortBy(fn ($e) => $e->fecha_cierre?->getTimestamp() ?? PHP_INT_MAX)->values();

        // Últimas notas
        $notasTareas = Entrega::with('tarea.aula')->where('alumno_id', $user->id)->where('estado', 'corregida')
            ->whereNotNull('corregida_at')->latest('corregida_at')->take(5)->get()
            ->map(fn ($e) => [
                'titulo' => $e->tarea?->titulo, 'aula' => $e->tarea?->aula, 'fecha' => $e->corregida_at,
                'nota' => $e->nota_texto, 'max' => $e->tarea?->puntaje_maximo_texto, 'url' => $e->tarea ? route('tareas.show', $e->tarea) : '#',
            ]);

        $notasEval = Intento::with('evaluacion.aula')->where('alumno_id', $user->id)->where('estado', 'finalizado')
            ->whereNotNull('nota')->latest('finalizado_at')->take(5)->get()
            ->map(fn ($i) => [
                'titulo' => $i->evaluacion?->titulo, 'aula' => $i->evaluacion?->aula, 'fecha' => $i->finalizado_at,
                'nota' => $i->nota_texto, 'max' => $i->evaluacion?->puntaje_maximo_texto, 'url' => route('intentos.show', $i),
            ]);

        $ultimasNotas = $notasTareas->concat($notasEval)
            ->sortByDesc(fn ($n) => $n['fecha']?->getTimestamp() ?? 0)->take(6)->values();

        return compact('porEntregar', 'paraRehacer', 'porRendir', 'ultimasNotas');
    }

    // ===================== Docente =====================

    public function pendientesDocente(User $user): array
    {
        $aulas = $this->aulasIds($user, 'docente');

        $entregasPorCorregir = Entrega::with(['tarea.aula', 'alumno'])
            ->where('estado', 'entregada')
            ->whereHas('tarea', fn ($q) => $q->whereIn('aula_id', $aulas))
            ->orderBy('entregada_at')
            ->get()
            ->groupBy('tarea_id')
            ->map(fn ($g) => ['tarea' => $g->first()->tarea, 'cantidad' => $g->count(), 'primera' => $g->first()])
            ->values();

        $intentosPorCorregir = Intento::with(['evaluacion.aula', 'alumno'])
            ->where('estado', 'pendiente_revision')
            ->whereHas('evaluacion', fn ($q) => $q->whereIn('aula_id', $aulas))
            ->orderBy('finalizado_at')
            ->get()
            ->groupBy('evaluacion_id')
            ->map(fn ($g) => ['evaluacion' => $g->first()->evaluacion, 'cantidad' => $g->count(), 'primero' => $g->first()])
            ->values();

        $desde = static::ahora();
        $hasta = $desde->copy()->addDays(14);

        $proximos = $this->eventos($user, $desde, $hasta)
            ->filter(fn ($e) => in_array($e['tipo'], ['tarea', 'cierre']))
            ->values();

        return compact('entregasPorCorregir', 'intentosPorCorregir', 'proximos');
    }

    // ===================== Calendario =====================

    /**
     * Eventos entre dos fechas de todas las aulas del usuario.
     * Cada evento: fecha, tipo (tarea|cierre|apertura|clase), titulo, aula, color, url, hecho (alumno), oculto (docente)
     */
    public function eventos(User $user, Carbon $desde, Carbon $hasta): Collection
    {
        $comoAlumno  = $this->aulasIds($user, 'alumno');
        $comoDocente = $this->aulasIds($user, 'docente');
        $todas       = $comoAlumno->concat($comoDocente)->unique();

        $esDocenteDe = fn ($aulaId) => $comoDocente->contains($aulaId);
        $aulas       = Aula::whereIn('id', $todas)->get()->keyBy('id');
        $eventos     = collect();

        // Tareas (vencimientos)
        $tareas = Tarea::whereIn('aula_id', $todas)->whereNotNull('fecha_limite')
            ->whereBetween('fecha_limite', [$desde, $hasta])->get()
            ->filter(fn ($t) => $esDocenteDe($t->aula_id) || ($t->visible && $t->estaPublicada()));

        $misEntregas = Entrega::where('alumno_id', $user->id)->whereIn('tarea_id', $tareas->pluck('id'))->pluck('tarea_id');

        foreach ($tareas as $t) {
            $eventos->push($this->evento($t->fecha_limite, 'tarea', 'Entrega: ' . $t->titulo, $aulas[$t->aula_id], route('tareas.show', $t),
                ! $esDocenteDe($t->aula_id) && $misEntregas->contains($t->id), ! $t->estaPublicada()));
        }

        // Evaluaciones (apertura programada y cierre)
        $evaluaciones = Evaluacion::whereIn('aula_id', $todas)
            ->where(fn ($q) => $q->whereBetween('fecha_cierre', [$desde, $hasta])->orWhereBetween('fecha_publicacion', [$desde, $hasta]))
            ->get()
            ->filter(fn ($e) => $esDocenteDe($e->aula_id) || $e->visible);

        $misIntentos = Intento::where('alumno_id', $user->id)->whereIn('evaluacion_id', $evaluaciones->pluck('id'))->pluck('evaluacion_id');

        foreach ($evaluaciones as $e) {
            $aula = $aulas[$e->aula_id];
            if ($e->fecha_publicacion && $e->fecha_publicacion->between($desde, $hasta)) {
                $eventos->push($this->evento($e->fecha_publicacion, 'apertura', 'Abre: ' . $e->titulo, $aula, route('evaluaciones.show', $e), false, ! $e->visible));
            }
            if ($e->fecha_cierre && $e->fecha_cierre->between($desde, $hasta)) {
                $eventos->push($this->evento($e->fecha_cierre, 'cierre', 'Cierra: ' . $e->titulo, $aula, route('evaluaciones.show', $e),
                    ! $esDocenteDe($e->aula_id) && $misIntentos->contains($e->id), ! $e->visible));
            }
        }

        // Clases programadas (solo el docente las ve antes de publicarse)
        Clase::whereIn('aula_id', $comoDocente)->whereBetween('fecha_publicacion', [$desde, $hasta])->get()
            ->each(fn ($c) => $eventos->push($this->evento($c->fecha_publicacion, 'clase', 'Se publica: ' . $c->titulo, $aulas[$c->aula_id], route('clases.show', $c), false, ! $c->visible)));

        return $eventos->sortBy(fn ($e) => $e['fecha']->getTimestamp())->values();
    }

    protected function evento(Carbon $fecha, string $tipo, string $titulo, Aula $aula, string $url, bool $hecho, bool $oculto): array
    {
        return [
            'fecha'  => $fecha,
            'tipo'   => $tipo,
            'titulo' => $titulo,
            'aula'   => $aula->nombre,
            'color'  => $aula->color,
            'url'    => $url,
            'hecho'  => $hecho,
            'oculto' => $oculto,
        ];
    }
}
