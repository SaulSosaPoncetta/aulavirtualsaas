<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarea extends Model
{
    use SoftDeletes;

    protected $table = 'tareas';

    protected $fillable = [
        'aula_id', 'unidad_id', 'user_id', 'titulo', 'consigna',
        'fecha_publicacion', 'fecha_limite', 'acepta_tardias', 'puntaje_maximo', 'visible',
    ];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'datetime',
            'fecha_limite'      => 'datetime',
            'acepta_tardias'    => 'boolean',
            'visible'           => 'boolean',
            'puntaje_maximo'    => 'decimal:2',
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

    public function archivos()
    {
        return $this->morphMany(Archivo::class, 'archivable')->orderBy('id');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }

    // ===================== Estado =====================

    protected static function ahora(): Carbon
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

    public function estaVencida(): bool
    {
        return $this->fecha_limite !== null && static::ahora()->gt($this->fecha_limite);
    }

    /** ¿El alumno puede entregar ahora? */
    public function aceptaEntregas(): bool
    {
        return $this->estaPublicada() && (! $this->estaVencida() || $this->acepta_tardias);
    }

    /** "10" en vez de "10.00" */
    public function getPuntajeMaximoTextoAttribute(): string
    {
        return rtrim(rtrim(number_format((float) $this->puntaje_maximo, 2, ',', ''), '0'), ',');
    }
}
