<?php

namespace App\Services;

use App\Models\Archivo;
use App\Models\Aula;
use App\Models\Institucion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Copia un aula (unidades, clases con su material, tareas y evaluaciones) para otro ciclo o curso.
 * No copia alumnos, entregas, intentos, notas ni el muro.
 *
 * Opciones:
 *  - clases, tareas, evaluaciones (bool): qué copiar
 *  - ocultar (bool): todo lo copiado queda oculto para revisarlo antes de publicar
 *  - fechas ('quitar' | 'correr'): borra las fechas o las corre los años de diferencia entre ciclos
 */
class DuplicarAulaService
{
    protected array $archivosCopiados = [];

    public function duplicar(Aula $origen, array $datos, User $user, array $op): Aula
    {
        $tz        = 'America/Argentina/Buenos_Aires';
        $anios     = (int) $datos['ciclo_lectivo'] - (int) $origen->ciclo_lectivo;
        $fecha     = fn (?Carbon $f) => $f === null || ($op['fechas'] ?? 'quitar') === 'quitar' ? null : $f->copy()->addYears($anios);
        $visible   = fn (bool $v) => ! empty($op['ocultar']) ? false : $v;

        try {
            return DB::transaction(function () use ($origen, $datos, $user, $op, $fecha, $visible, $tz) {
                // ----- Aula -----
                $nueva = Aula::create([
                    'institucion_id'   => $datos['institucion_id'],
                    'docente_id'       => $user->id,
                    'materia'          => $datos['materia'],
                    'curso'            => $datos['curso'],
                    'division'         => $datos['division'],
                    'turno'            => $datos['turno'],
                    'escuela'          => $datos['institucion_id'] ? null : $datos['escuela'],
                    'ciclo_lectivo'    => $datos['ciclo_lectivo'],
                    'descripcion'      => $origen->descripcion,
                    'color'            => $origen->color,
                    'alumnos_publican' => $origen->alumnos_publican,
                    'alumnos_comentan' => $origen->alumnos_comentan,
                    'estado'           => 'activa',
                ]);

                $nueva->miembros()->attach($user->id, [
                    'rol' => 'docente', 'origen' => 'creador', 'estado' => 'activo',
                    'fecha_alta' => Carbon::now($tz)->toDateString(),
                ]);

                if ($nueva->institucion_id) {
                    $inst = Institucion::find($nueva->institucion_id);
                    if (! $inst->ocupaLugar($user)) {
                        $inst->usuarios()->attach($user->id, ['rol' => 'docente', 'activo' => true]);
                    }
                }

                // ----- Unidades -----
                $mapaUnidades = [];
                foreach ($origen->unidades()->get() as $u) {
                    $copia = $u->replicate(['aula_id']);
                    $copia->aula_id = $nueva->id;
                    $copia->save();
                    $mapaUnidades[$u->id] = $copia->id;
                }
                $unidad = fn ($id) => $id ? ($mapaUnidades[$id] ?? null) : null;

                // ----- Clases y su material -----
                if (! empty($op['clases'])) {
                    foreach ($origen->clases()->with('recursos')->get() as $c) {
                        $copia = $c->replicate(['notificado_at']);
                        $copia->fill([
                            'aula_id'           => $nueva->id,
                            'unidad_id'         => $unidad($c->unidad_id),
                            'user_id'           => $user->id,
                            'fecha_publicacion' => $fecha($c->fecha_publicacion),
                            'visible'           => $visible($c->visible),
                        ]);
                        $copia->save();

                        foreach ($c->recursos as $r) {
                            $rc = $r->replicate();
                            $rc->clase_id = $copia->id;
                            if ($r->archivo_path) {
                                $rc->archivo_path = $this->copiarArchivo($r->archivo_path, "aulas/{$nueva->id}/clases/{$copia->id}");
                            }
                            $rc->save();
                        }
                    }
                }

                // ----- Tareas y sus archivos -----
                if (! empty($op['tareas'])) {
                    foreach ($origen->tareas()->with('archivos')->get() as $t) {
                        $copia = $t->replicate(['notificado_at']);
                        $copia->fill([
                            'aula_id'           => $nueva->id,
                            'unidad_id'         => $unidad($t->unidad_id),
                            'user_id'           => $user->id,
                            'fecha_publicacion' => $fecha($t->fecha_publicacion),
                            'fecha_limite'      => $fecha($t->fecha_limite),
                            'visible'           => $visible($t->visible),
                        ]);
                        $copia->save();

                        foreach ($t->archivos as $a) {
                            $copia->archivos()->create([
                                'path'    => $this->copiarArchivo($a->path, "aulas/{$nueva->id}/tareas/{$copia->id}"),
                                'nombre'  => $a->nombre,
                                'mime'    => $a->mime,
                                'tamano'  => $a->tamano,
                                'user_id' => $user->id,
                            ]);
                        }
                    }
                }

                // ----- Evaluaciones con sus preguntas -----
                if (! empty($op['evaluaciones'])) {
                    foreach ($origen->evaluaciones()->with('preguntas.opciones')->get() as $e) {
                        $copia = $e->replicate(['notificado_at']);
                        $copia->fill([
                            'aula_id'           => $nueva->id,
                            'unidad_id'         => $unidad($e->unidad_id),
                            'user_id'           => $user->id,
                            'fecha_publicacion' => $fecha($e->fecha_publicacion),
                            'fecha_cierre'      => $fecha($e->fecha_cierre),
                            'visible'           => $visible($e->visible),
                        ]);
                        $copia->save();

                        foreach ($e->preguntas as $p) {
                            $pc = $p->replicate();
                            $pc->evaluacion_id = $copia->id;
                            $pc->save();

                            foreach ($p->opciones as $o) {
                                $oc = $o->replicate();
                                $oc->pregunta_id = $pc->id;
                                $oc->save();
                            }
                        }
                    }
                }

                return $nueva;
            });
        } catch (\Throwable $e) {
            // Si algo falla, se borran los archivos que ya se habían copiado
            foreach ($this->archivosCopiados as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    /** Copia un archivo privado a la carpeta de la nueva aula. Devuelve la ruta nueva (o la original si no existe). */
    protected function copiarArchivo(string $path, string $carpeta): string
    {
        $disco = Storage::disk('local');

        if (! $disco->exists($path)) {
            return $path;
        }

        $destino = $carpeta . '/' . basename($path);
        $disco->copy($path, $destino);
        $this->archivosCopiados[] = $destino;

        return $destino;
    }

    /** Cantidades para mostrar en el formulario. */
    public function resumen(Aula $aula): array
    {
        return [
            'unidades'     => $aula->unidades()->count(),
            'clases'       => $aula->clases()->count(),
            'tareas'       => $aula->tareas()->count(),
            'evaluaciones' => $aula->evaluaciones()->count(),
            'preguntas'    => DB::table('preguntas')->whereIn('evaluacion_id', $aula->evaluaciones()->pluck('id'))->count(),
        ];
    }
}
