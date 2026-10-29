<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluacion extends Model
{
    use SoftDeletes;

    protected $table = 'evaluaciones';

    public const RESULTADOS = [
        'al_finalizar' => 'Apenas termina',
        'al_cerrar'    => 'Cuando cierra la evaluación',
        'nunca'        => 'Solo la nota la ve el docente',
    ];

    protected $fillable = [
        'aula_id', 'unidad_id', 'user_id', 'titulo', 'instrucciones',
        'fecha_publicacion', 'fecha_cierre', 'duracion_minutos', 'intentos_permitidos',
        'mostrar_resultados', 'mezclar_preguntas', 'puntaje_maximo', 'visible',
    ];

    protected function casts(): array
    {
        return [
            'fecha_publicacion'   => 'datetime',
            'fecha_cierre'        => 'datetime',
            'duracion_minutos'    => 'integer',
            'intentos_permitidos' => 'integer',
            'mezclar_preguntas'   => 'boolean',
            'visible'             => 'boolean',
            'puntaje_maximo'      => 'decimal:2',
        ];
    }

    // ===================== Relaciones =====================

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function preguntas()
    {
        return $this->hasMany(Pregunta::class)->orderBy('orden')->orderBy('id');
    }

    public function intentos()
    {
        return $this->hasMany(Intento::class);
    }

    // ===================== Estado =====================

    public static function ahora(): Carbon
    {
        return Carbon::now('America/Argentina/Buenos_Aires');
    }

    public function estaPublicada(): bool
    {
        return $this->visible
            && (is_null($this->fecha_publicacion) || $this->fecha_publicacion->lte(static::ahora()));
    }

    public function getEstadoAttribute(): string
    {
        if (! $this->visible) {
            return 'oculta';
        }

        return $this->estaPublicada() ? 'publicada' : 'programada';
    }

    public function estaCerrada(): bool
    {
        return $this->fecha_cierre !== null && static::ahora()->gt($this->fecha_cierre);
    }

    /** Se puede rendir: publicada, sin cerrar y con preguntas. */
    public function estaAbierta(): bool
    {
        return $this->estaPublicada() && ! $this->estaCerrada() && $this->preguntas()->exists();
    }

    public function totalPuntos(): float
    {
        return (float) $this->preguntas()->sum('puntos');
    }

    /** ¿El alumno puede ver respuestas correctas y detalle? */
    public function resultadosVisibles(): bool
    {
        return match ($this->mostrar_resultados) {
            'al_finalizar' => true,
            'al_cerrar'    => $this->estaCerrada(),
            default        => false,
        };
    }

    public function getPuntajeMaximoTextoAttribute(): string
    {
        return static::numero($this->puntaje_maximo);
    }

    /** 7.50 → "7,5" */
    public static function numero($valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 2, ',', ''), '0'), ',');
    }
}
