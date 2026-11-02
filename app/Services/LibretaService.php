<?php

namespace App\Services;

use App\Models\Aula;
use App\Models\CierrePeriodo;
use App\Models\Evaluacion;
use App\Models\NotaManual;
use App\Models\Periodo;
use Illuminate\Support\Collection;

/**
 * Libreta de calificaciones:
 * columnas = tareas, evaluaciones y notas manuales (cada una con su peso y su período);
 * filas = alumnos. Promedio ponderado en escala 10 y nota de cierre por período.
 */
class LibretaService
{
    /**
     * @param  int|null  $periodoId  null = todo el ciclo
     */
    public function armar(Aula $aula, bool $contarCeros = false, ?int $soloAlumnoId = null, ?int $periodoId = null): array
    {
        $periodos = $aula->periodos()->get();
        $periodo  = $periodoId ? $periodos->firstWhere('id', $periodoId) : null;

        $alumnos = $aula->alumnos()->orderBy('apellido')->orderBy('name')->get();
        if ($soloAlumnoId) {
            $alumnos = $alumnos->where('id', $soloAlumnoId)->values();
        }
        $ids = $alumnos->pluck('id');

        $items = $this->items($aula, $ids, $periodos);

        if ($periodo) {
            $items = $items->where('periodo_id', $periodo->id)->values();
        }

        // ----- Celdas -----
        $celdas = [];
        foreach ($alumnos as $al) {
            foreach ($items as $it) {
                $celdas[$al->id][$it['clave']] = match ($it['tipo']) {
                    'tarea'      => $this->celdaTarea($it, $al->id),
                    'evaluacion' => $this->celdaEvaluacion($it, $al->id),
                    default      => $this->celdaManual($it, $al->id),
                };
            }
        }

        // ----- Promedio ponderado por alumno (escala 10) -----
        $promedios = [];
        foreach ($alumnos as $al) {
            $promedios[$al->id] = $this->promedio($items, $celdas[$al->id] ?? [], $contarCeros);
        }

        // ----- Promedio por columna -----
        $resumenItems = [];
        foreach ($items as $it) {
            $notas = $alumnos->map(fn ($al) => $celdas[$al->id][$it['clave']]['nota'])->filter(fn ($n) => $n !== null);
            $resumenItems[$it['clave']] = [
                'promedio' => $notas->isNotEmpty() ? round($notas->avg(), 2) : null,
                'corregir' => $alumnos->filter(fn ($al) => $celdas[$al->id][$it['clave']]['estado'] === 'corregir')->count(),
            ];
        }

        // ----- Notas de cierre del período -----
        $cierres = $periodo
            ? CierrePeriodo::where('periodo_id', $periodo->id)->whereIn('alumno_id', $ids)->get()->keyBy('alumno_id')
            : collect();

        return compact('items', 'alumnos', 'celdas', 'promedios', 'resumenItems', 'periodos', 'periodo', 'cierres');
    }

    /**
     * Boletín de un alumno: por cada período, promedio calculado y nota de cierre; y nota final.
     */
    public function boletin(Aula $aula, int $alumnoId, bool $contarCeros = false): array
    {
        $periodos = $aula->periodos()->get();
        $todo     = $this->armar($aula, $contarCeros, $alumnoId);
        $cierres  = CierrePeriodo::whereIn('periodo_id', $periodos->pluck('id'))->where('alumno_id', $alumnoId)->get()->keyBy('periodo_id');

        $filas = $periodos->map(function ($p) use ($todo, $alumnoId, $contarCeros, $cierres) {
            $items = $todo['items']->where('periodo_id', $p->id)->values();
            $prom  = $this->promedio($items, $todo['celdas'][$alumnoId] ?? [], $contarCeros);
            $c     = $cierres[$p->id] ?? null;

            return [
                'periodo'     => $p,
                'items'       => $items,
                'promedio'    => $prom['valor'],
                'cierre'      => $c?->nota !== null ? (float) $c->nota : null,
                'observacion' => $c?->observacion,
                'definitiva'  => $c?->nota !== null ? (float) $c->nota : $prom['valor'],
            ];
        });

        $definitivas = $filas->pluck('definitiva')->filter(fn ($v) => $v !== null);

        return [
            'filas'     => $filas,
            'sinPeriodo'=> $todo['items']->whereNull('periodo_id')->values(),
            'celdas'    => $todo['celdas'][$alumnoId] ?? [],
            'final'     => $definitivas->isNotEmpty() ? round($definitivas->avg(), 2) : null,
            'general'   => $todo['promedios'][$alumnoId]['valor'] ?? null,
        ];
    }

    // ===================== Columnas =====================

    protected function items(Aula $aula, Collection $ids, Collection $periodos): Collection
    {
        $buscarPeriodo = fn ($fecha) => $periodos->first(fn ($p) => $p->contiene($fecha))?->id;

        $items = collect();

        $tareas = $aula->tareas()->with(['entregas' => fn ($q) => $q->whereIn('alumno_id', $ids)])->get()
            ->filter(fn ($t) => $t->estaPublicada());

        foreach ($tareas as $t) {
            $fecha = $t->fecha_limite ?? $t->fecha_publicacion ?? $t->created_at;
            $items->push([
                'clave' => 't' . $t->id, 'tipo' => 'tarea', 'id' => $t->id, 'titulo' => $t->titulo,
                'max' => (float) $t->puntaje_maximo, 'peso' => (float) ($t->peso ?? 1), 'fecha' => $fecha,
                'periodo_id' => $buscarPeriodo($fecha), 'url' => route('tareas.show', $t),
                'vencido' => $t->estaVencida(), 'modelo' => $t,
            ]);
        }

        $evaluaciones = $aula->evaluaciones()->with(['intentos' => fn ($q) => $q->whereIn('alumno_id', $ids)])->get()
            ->filter(fn ($e) => $e->estaPublicada());

        foreach ($evaluaciones as $e) {
            $fecha = $e->fecha_cierre ?? $e->fecha_publicacion ?? $e->created_at;
            $items->push([
                'clave' => 'e' . $e->id, 'tipo' => 'evaluacion', 'id' => $e->id, 'titulo' => $e->titulo,
                'max' => (float) $e->puntaje_maximo, 'peso' => (float) ($e->peso ?? 1), 'fecha' => $fecha,
                'periodo_id' => $buscarPeriodo($fecha), 'url' => route('evaluaciones.show', $e),
                'vencido' => $e->estaCerrada(), 'modelo' => $e,
            ]);
        }

        $columnas = $aula->columnasLibreta()->get();
        $notas    = NotaManual::whereIn('columna_id', $columnas->pluck('id'))->whereIn('alumno_id', $ids)->get()->groupBy('columna_id');

        foreach ($columnas as $c) {
            $fecha = $c->fecha ?? $c->created_at;
            $items->push([
                'clave' => 'm' . $c->id, 'tipo' => 'manual', 'id' => $c->id, 'titulo' => $c->nombre,
                'max' => (float) $c->puntaje_maximo, 'peso' => (float) $c->peso, 'fecha' => $fecha,
                'periodo_id' => $c->periodo_id ?? $buscarPeriodo($fecha), 'url' => null,
                'vencido' => false, 'modelo' => $c, 'notas' => ($notas[$c->id] ?? collect())->keyBy('alumno_id'),
            ]);
        }

        return $items->sortBy(fn ($i) => $i['fecha']->getTimestamp())->values();
    }

    // ===================== Promedio =====================

    protected function promedio(Collection $items, array $celdasAlumno, bool $contarCeros): array
    {
        $suma = 0;
        $pesos = 0;
        $calificadas = 0;
        $faltan = 0;

        foreach ($items as $it) {
            $c = $celdasAlumno[$it['clave']] ?? null;
            if (! $c || $it['max'] <= 0 || $it['peso'] <= 0) {
                continue;
            }

            if ($c['nota'] !== null) {
                $suma  += $c['nota'] / $it['max'] * 10 * $it['peso'];
                $pesos += $it['peso'];
                $calificadas++;
            } elseif ($c['estado'] === 'falta') {
                $faltan++;
                if ($contarCeros) {
                    $pesos += $it['peso'];
                }
            }
        }

        return [
            'valor'       => $pesos > 0 ? round($suma / $pesos, 2) : null,
            'calificadas' => $calificadas,
            'faltan'      => $faltan,
        ];
    }

    // ===================== Celdas =====================

    protected function celdaTarea(array $it, int $alumnoId): array
    {
        $e = $it['modelo']->entregas->firstWhere('alumno_id', $alumnoId);

        if (! $e) {
            return $it['vencido'] ? $this->celda(null, 'No entregó', 'falta') : $this->celda(null, '—', 'pendiente');
        }

        $url = route('entregas.show', $e);

        return match ($e->estado) {
            'corregida' => $e->nota !== null
                ? $this->celda((float) $e->nota, Evaluacion::numero($e->nota), 'nota', $url)
                : $this->celda(null, 'Corregida', 'corregida', $url),
            'devuelta'  => $this->celda(null, 'Rehacer', 'rehacer', $url),
            default     => $this->celda(null, 'Por corregir', 'corregir', $url),
        };
    }

    protected function celdaEvaluacion(array $it, int $alumnoId): array
    {
        $intentos = $it['modelo']->intentos->where('alumno_id', $alumnoId);

        if ($intentos->isEmpty()) {
            return $it['vencido'] ? $this->celda(null, 'No rindió', 'falta') : $this->celda(null, '—', 'pendiente');
        }

        $mejor = $intentos->whereNotNull('nota')->sortByDesc(fn ($i) => (float) $i->nota)->first();
        if ($mejor) {
            return $this->celda((float) $mejor->nota, Evaluacion::numero($mejor->nota), 'nota', route('intentos.show', $mejor));
        }

        $pend = $intentos->firstWhere('estado', 'pendiente_revision');

        return $pend
            ? $this->celda(null, 'Por corregir', 'corregir', route('intentos.show', $pend))
            : $this->celda(null, 'En curso', 'curso');
    }

    protected function celdaManual(array $it, int $alumnoId): array
    {
        $n = $it['notas'][$alumnoId] ?? null;

        return $n && $n->nota !== null
            ? $this->celda((float) $n->nota, Evaluacion::numero($n->nota), 'nota', null, $n->observacion)
            : $this->celda(null, '—', 'pendiente', null, $n?->observacion);
    }

    protected function celda(?float $nota, string $texto, string $estado, ?string $url = null, ?string $obs = null): array
    {
        return compact('nota', 'texto', 'estado', 'url') + ['observacion' => $obs];
    }

    public function periodoActual(Collection $periodos): ?Periodo
    {
        return $periodos->first(fn ($p) => $p->contiene(now('America/Argentina/Buenos_Aires')));
    }
}
