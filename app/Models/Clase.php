<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clase extends Model
{
    use SoftDeletes;

    protected $table = 'clases';

    protected $fillable = [
        'aula_id', 'unidad_id', 'user_id', 'titulo', 'contenido',
        'fecha_publicacion', 'visible', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'datetime',
            'visible'           => 'boolean',
            'orden'             => 'integer',
        ];
    }

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

    public function recursos()
    {
        return $this->hasMany(Recurso::class)->orderBy('orden')->orderBy('id');
    }

    /** ¿Ya la pueden ver los alumnos? (visible y con fecha de publicación cumplida) */
    public function estaPublicada(): bool
    {
        if (! $this->visible) {
            return false;
        }

        return is_null($this->fecha_publicacion)
            || $this->fecha_publicacion->lte(Carbon::now('America/Argentina/Buenos_Aires'));
    }

    public function getEstadoAttribute(): string
    {
        if (! $this->visible) {
            return 'oculta';
        }

        return $this->estaPublicada() ? 'publicada' : 'programada';
    }
}
