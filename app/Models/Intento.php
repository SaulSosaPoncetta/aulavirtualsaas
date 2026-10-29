<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Intento extends Model
{
    protected $table = 'intentos';

    /** Minutos de tolerancia después del límite (demoras de conexión). */
    public const TOLERANCIA_MIN = 2;

    public const ESTADOS = [
        'en_curso'           => ['En curso', 'info'],
        'pendiente_revision' => ['Falta corregir', 'warning'],
        'finalizado'         => ['Finalizado', 'success'],
    ];

    protected $fillable = [
        'evaluacion_id', 'alumno_id', 'numero', 'iniciado_at', 'finalizado_at',
        'estado', 'puntaje', 'nota', 'corregido_por',
    ];

    protected function casts(): array
    {
        return [
            'iniciado_at'   => 'datetime',
            'finalizado_at' => 'datetime',
            'puntaje'       => 'decimal:2',
            'nota'          => 'decimal:2',
            'numero'        => 'integer',
        ];
    }

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class)->withTrashed();
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

    public function respuestas()
    {
        return $this->hasMany(Respuesta::class);
    }

    // ===================== Tiempo =====================

    /** Momento en que se termina el tiempo (duración o cierre, lo que llegue primero). */
    public function limite(): ?Carbon
    {
        $ev = $this->evaluacion;
        $candidatos = [];

        if ($ev->duracion_minutos) {
            $candidatos[] = $this->iniciado_at->copy()->addMinutes($ev->duracion_minutos);
        }
        if ($ev->fecha_cierre) {
            $candidatos[] = $ev->fecha_cierre->copy();
        }

        return $candidatos ? collect($candidatos)->sortBy(fn ($c) => $c->getTimestamp())->first() : null;
    }

    public function segundosRestantes(): ?int
    {
        $limite = $this->limite();

        return $limite ? max(0, Evaluacion::ahora()->diffInSeconds($limite, false)) : null;
    }

    /** Pasó el límite + la tolerancia. */
    public function fueraDeTiempo(): bool
    {
        $limite = $this->limite();

        return $limite !== null && Evaluacion::ahora()->gt($limite->copy()->addMinutes(self::TOLERANCIA_MIN));
    }

    // ===================== Corrección =====================

    /**
     * Guarda las respuestas enviadas, corrige las automáticas y cierra el intento.
     * $datos = [pregunta_id => opcion_id | [opcion_ids] | 'texto']
     */
    public function finalizar(array $datos, bool $descartar = false): void
    {
        DB::transaction(function () use ($datos, $descartar) {
            $preguntas = $this->evaluacion->preguntas()->with('opciones')->get();

            foreach ($preguntas as $p) {
                $valor = $descartar ? null : ($datos[$p->id] ?? null);

                $fila = ['opciones_ids' => null, 'texto' => null, 'correcta' => null, 'puntos' => null];

                if ($p->tipo === 'texto') {
                    $texto = trim((string) $valor);
                    $fila['texto'] = $texto !== '' ? mb_substr($texto, 0, 20000) : null;

                    if ($fila['texto'] === null) {
                        // En blanco: se corrige solo con 0
                        $fila['correcta'] = false;
                        $fila['puntos']   = 0;
                    }
                } else {
                    $validas = $p->opciones->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $elegidas = array_values(array_intersect(array_map('intval', (array) $valor), $validas));

                    [$ok, $puntos] = $p->corregir($elegidas);

                    $fila['opciones_ids'] = $elegidas ?: null;
                    $fila['correcta']     = $ok;
                    $fila['puntos']       = $puntos;
                }

                Respuesta::updateOrCreate(['intento_id' => $this->id, 'pregunta_id' => $p->id], $fila);
            }

            $limite = $this->limite();
            $ahora  = Evaluacion::ahora();

            $this->finalizado_at = $limite && $ahora->gt($limite) ? $limite : $ahora;
            $this->recalcular();
        });
    }

    /** Suma puntos y calcula la nota; si hay respuestas escritas sin corregir queda pendiente. */
    public function recalcular(): void
    {
        $respuestas = $this->respuestas()->get();
        $total      = $this->evaluacion->totalPuntos();
        $puntaje    = (float) $respuestas->sum(fn ($r) => (float) $r->puntos);
        $pendientes = $respuestas->contains(fn ($r) => $r->puntos === null);

        $this->puntaje = round($puntaje, 2);
        $this->estado  = $pendientes ? 'pendiente_revision' : 'finalizado';
        $this->nota    = $pendientes || $total <= 0
            ? null
            : round($puntaje / $total * (float) $this->evaluacion->puntaje_maximo, 2);

        $this->save();
    }

    /** Si quedó abierto y se pasó del tiempo, se cierra con lo que haya (en blanco). */
    public function cerrarSiVencido(): void
    {
        if ($this->estado === 'en_curso' && $this->fueraDeTiempo()) {
            $this->finalizar([], true);
        }
    }

    // ===================== Textos =====================

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado][0] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return self::ESTADOS[$this->estado][1] ?? 'secondary';
    }

    public function getNotaTextoAttribute(): ?string
    {
        return $this->nota === null ? null : Evaluacion::numero($this->nota);
    }

    public function getPuntajeTextoAttribute(): ?string
    {
        return $this->puntaje === null ? null : Evaluacion::numero($this->puntaje);
    }
}
