<?php

namespace App\Services;

use App\Models\Aula;
use App\Models\Evaluacion;
use Illuminate\Support\Collection;

/**
 * Arma la libreta de calificaciones de un aula:
 * columnas = tareas y evaluaciones publicadas, filas = alumnos.
 */
class LibretaService
{
    /**
     * @param  bool  $contarCeros  Si true, lo vencido sin entregar cuenta como 0 en el promedio.
     * @return array{items: Collection, alumnos: Collection, celdas: array, promedios: array, resumenItems: array}
     */
    public function armar(Aula $aula, bool $contarCeros = false, ?int $soloAlumnoId = null): array
    {
        $alumnos = $aula->alumnos()->orderBy('apellido')->orderBy('name')->get();

        if ($soloAlumnoId) {
            $alumnos = $alumnos->where('id', $soloAlumnoId)->values();
        }

        $idsAlumnos = $alumnos->pluck('id');

        $tareas = $aula->tareas()
            ->with(['entregas' => fn ($q) => $q->whereIn('alumno_id', $idsAlumnos)])
            ->get()
            ->filter(fn ($t) => $t->estaPublicada());

        $evaluaciones = $aula->evaluaciones()
            ->with(['intentos' => fn ($q) => $q->whereIn('alumno_id', $idsAlumnos)])
            ->get()
            ->filter(fn ($e) => $e->estaPublicada());

        // ----- Columnas -----
        $items = collect();

        foreach ($tareas as $t) {
            $items->push([
                'clave'   => 't' . $t->id,
                'tipo'    => 'tarea',
                'titulo'  => $t->titulo,
                'max'     => (float) $t->puntaje_maximo,
                'fecha'   => $t->fecha_limite ?? $t->fecha_publicacion ?? $t->created_at,
                'url'     => route('tareas.show', $t),
                'vencido' => $t->estaVencida(),
                'modelo'  => $t,
            ]);
        }

        foreach ($evaluaciones as $e) {
            $items->push([
                'clave'   => 'e' . $e->id,
                'tipo'    => 'evaluacion',
                'titulo'  => $e->titulo,
                'max'     => (float) $e->puntaje_maximo,
                'fecha'   => $e->fecha_cierre ?? $e->fecha_publicacion ?? $e->created_at,
                'url'     => route('evaluaciones.show', $e),
                'vencido' => $e->estaCerrada(),
                'modelo'  => $e,
            ]);
        }

        $items = $items->sortBy(fn ($i) => $i['fecha']->getTimestamp())->values();

        // ----- Celdas -----
        $celdas = [];

        foreach ($alumnos as $al) {
            foreach ($items as $it) {
                $celdas[$al->id][$it['clave']] = $it['tipo'] === 'tarea'
                    ? $this->celdaTarea($it, $al->id)
                    : $this->celdaEvaluacion($it, $al->id);
            }
        }

        // ----- Promedios por alumno (escala 10) -----
        $promedios = [];

        foreach ($alumnos as $al) {
            $valores   = [];
            $calificadas = 0;

            foreach ($items as $it) {
                $c = $celdas[$al->id][$it['clave']];

                if ($c['nota'] !== null && $it['max'] > 0) {
                    $valores[] = $c['nota'] / $it['max'] * 10;
                    $calificadas++;
                } elseif ($contarCeros && $c['estado'] === 'falta') {
                    $valores[] = 0;
                }
            }

            $promedios[$al->id] = [
                'valor'       => $valores ? round(array_sum($valores) / count($valores), 2) : null,
                'calificadas' => $calificadas,
                'faltan'      => collect($celdas[$al->id] ?? [])->where('estado', 'falta')->count(),
            ];
        }

        // ----- Promedio por columna -----
        $resumenItems = [];

        foreach ($items as $it) {
            $notas = collect($alumnos)->map(fn ($al) => $celdas[$al->id][$it['clave']]['nota'])->filter(fn ($n) => $n !== null);
            $resumenItems[$it['clave']] = [
                'promedio'  => $notas->isNotEmpty() ? round($notas->avg(), 2) : null,
                'corregir'  => collect($alumnos)->filter(fn ($al) => $celdas[$al->id][$it['clave']]['estado'] === 'corregir')->count(),
            ];
        }

        return compact('items', 'alumnos', 'celdas', 'promedios', 'resumenItems');
    }

    /** Celda: ['nota' => ?float, 'texto' => string, 'estado' => nota|corregir|rehacer|curso|falta|pendiente, 'url' => ?string] */
    protected function celdaTarea(array $it, int $alumnoId): array
    {
        $entrega = $it['modelo']->entregas->firstWhere('alumno_id', $alumnoId);

        if (! $entrega) {
            return $it['vencido']
                ? $this->celda(null, 'No entregó', 'falta')
                : $this->celda(null, '—', 'pendiente');
        }

        $url = route('entregas.show', $entrega);

        return match ($entrega->estado) {
            'corregida' => $entrega->nota !== null
                ? $this->celda((float) $entrega->nota, Evaluacion::numero($entrega->nota), 'nota', $url)
                : $this->celda(null, 'Corregida', 'corregida', $url),
            'devuelta'  => $this->celda(null, 'Rehacer', 'rehacer', $url),
            default     => $this->celda(null, 'Por corregir', 'corregir', $url),
        };
    }

    protected function celdaEvaluacion(array $it, int $alumnoId): array
    {
        $intentos = $it['modelo']->intentos->where('alumno_id', $alumnoId);

        if ($intentos->isEmpty()) {
            return $it['vencido']
                ? $this->celda(null, 'No rindió', 'falta')
                : $this->celda(null, '—', 'pendiente');
        }

        $mejor = $intentos->whereNotNull('nota')->sortByDesc(fn ($i) => (float) $i->nota)->first();

        if ($mejor) {
            return $this->celda((float) $mejor->nota, Evaluacion::numero($mejor->nota), 'nota', route('intentos.show', $mejor));
        }

        $pendiente = $intentos->firstWhere('estado', 'pendiente_revision');
        if ($pendiente) {
            return $this->celda(null, 'Por corregir', 'corregir', route('intentos.show', $pendiente));
        }

        return $this->celda(null, 'En curso', 'curso');
    }

    protected function celda(?float $nota, string $texto, string $estado, ?string $url = null): array
    {
        return compact('nota', 'texto', 'estado', 'url');
    }
}
