<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aula extends Model
{
    use SoftDeletes;

    protected $table = 'aulas';

    protected $fillable = [
        'institucion_id',
        'docente_id',
        'materia',
        'curso',
        'division',
        'turno',
        'escuela',
        'ciclo_lectivo',
        'descripcion',
        'imagen',
        'color',
        'codigo_acceso',
        'codigo_activo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'codigo_activo' => 'boolean',
            'ciclo_lectivo' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Aula $aula) {
            if (empty($aula->codigo_acceso)) {
                $aula->codigo_acceso = static::codigoUnico(
                    fn ($c) => static::withTrashed()->where('codigo_acceso', $c)->exists()
                );
            }
        });
    }

    /** Código aleatorio sin caracteres confusos (0, O, 1, I). */
    public static function codigoUnico(Closure $existe, int $largo = 6): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $codigo = '';
            for ($i = 0; $i < $largo; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while ($existe($codigo));

        return $codigo;
    }

    // ===================== Relaciones =====================

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }

    public function titular()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function miembros()
    {
        return $this->belongsToMany(User::class, 'aula_user')
            ->withPivot('rol', 'origen', 'estado', 'fecha_alta', 'fecha_baja')
            ->withTimestamps();
    }

    public function docentes()
    {
        return $this->miembros()->wherePivot('rol', 'docente')->wherePivot('estado', 'activo');
    }

    public function alumnos()
    {
        return $this->miembros()->wherePivot('rol', 'alumno')->wherePivot('estado', 'activo');
    }

    public function unidades()
    {
        return $this->hasMany(Unidad::class)->orderBy('orden')->orderBy('id');
    }

    public function clases()
    {
        return $this->hasMany(Clase::class)->orderBy('orden')->orderBy('id');
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class)->orderByRaw('CASE WHEN fecha_limite IS NULL THEN 1 ELSE 0 END')->orderBy('fecha_limite')->orderBy('id');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class)->orderByRaw('CASE WHEN fecha_cierre IS NULL THEN 1 ELSE 0 END')->orderBy('fecha_cierre')->orderBy('id');
    }

    /** ¿El usuario es alumno activo de esta aula? */
    public function esAlumno(User $user): bool
    {
        return $this->miembros()
            ->where('users.id', $user->id)
            ->wherePivot('rol', 'alumno')
            ->wherePivot('estado', 'activo')
            ->exists();
    }

    // ===================== Ayudas =====================

    public function getNombreAttribute(): string
    {
        $curso = trim(($this->curso ?? '') . ' ' . ($this->division ?? ''));
        return $curso ? "{$this->materia} — {$curso}" : $this->materia;
    }

    public function getNombreEscuelaAttribute(): ?string
    {
        return $this->institucion?->nombre ?? $this->escuela;
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    public function esMiembro(User $user): bool
    {
        return $this->miembros()->where('users.id', $user->id)->wherePivot('estado', 'activo')->exists();
    }

    /** Titular, co-docente activo o superadmin. */
    public function puedeEditar(User $user): bool
    {
        if ($user->esSuperadmin() || $this->docente_id == $user->id) {
            return true;
        }

        return $this->miembros()
            ->where('users.id', $user->id)
            ->wherePivot('rol', 'docente')
            ->wherePivot('estado', 'activo')
            ->exists();
    }

    /** Miembro activo o superadmin. */
    public function puedeVer(User $user): bool
    {
        return $user->esSuperadmin() || $this->esMiembro($user);
    }
}
