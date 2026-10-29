<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Aula extends Model
{
    use SoftDeletes;

    protected $table = 'aulas';

    protected $fillable = [
        'institucion_id',
        'docente_id',
        'gestionaula_designacion_id',
        'sgi_curso_id',
        'materia',
        'curso',
        'division',
        'turno',
        'ciclo_lectivo',
        'descripcion',
        'imagen',
        'codigo_acceso',
        'codigo_activo',
        'estado',
        'sincronizado_at',
    ];

    protected function casts(): array
    {
        return [
            'codigo_activo'   => 'boolean',
            'ciclo_lectivo'   => 'integer',
            'sincronizado_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Aula $aula) {
            if (empty($aula->codigo_acceso)) {
                $aula->codigo_acceso = static::generarCodigo();
            }
        });
    }

    // Código de 6 caracteres sin letras/números confusos (0, O, 1, I)
    public static function generarCodigo(): string
    {
        do {
            $codigo = Str::upper(substr(str_shuffle(str_repeat('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', 3)), 0, 6));
        } while (static::withTrashed()->where('codigo_acceso', $codigo)->exists());

        return $codigo;
    }

    // ===================== Relaciones =====================

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function alumnos()
    {
        return $this->belongsToMany(User::class, 'aula_alumno', 'aula_id', 'alumno_id')
            ->withPivot('origen', 'estado', 'fecha_alta', 'fecha_baja')
            ->withTimestamps();
    }

    public function alumnosActivos()
    {
        return $this->alumnos()->wherePivot('estado', 'activo');
    }

    // ===================== Ayudas =====================

    public function getNombreAttribute(): string
    {
        $curso = trim(($this->curso ?? '') . ' ' . ($this->division ?? ''));
        return $curso ? "{$this->materia} — {$curso}" : $this->materia;
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }
}
