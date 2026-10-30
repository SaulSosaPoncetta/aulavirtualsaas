<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $table = 'instituciones';

    protected $fillable = [
        'nombre',
        'cue',
        'nivel',
        'localidad',
        'provincia',
        'telefono',
        'email',
        'logo',
        'codigo_invitacion',
        'creada_por',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Institucion $i) {
            if (empty($i->codigo_invitacion)) {
                $i->codigo_invitacion = Aula::codigoUnico(fn ($c) => static::where('codigo_invitacion', $c)->exists(), 8);
            }
        });
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'institucion_user')
            ->withPivot('rol', 'activo')
            ->withTimestamps();
    }

    public function administradores()
    {
        return $this->usuarios()->wherePivot('rol', 'admin');
    }

    public function docentes()
    {
        return $this->usuarios()->wherePivot('rol', 'docente');
    }

    public function alumnos()
    {
        return $this->usuarios()->wherePivot('rol', 'alumno');
    }

    public function aulas()
    {
        return $this->hasMany(Aula::class);
    }

    public const NIVELES = [
        'inicial'       => 'Inicial',
        'primaria'      => 'Primaria',
        'secundaria'    => 'Secundaria',
        'tecnica'       => 'Secundaria técnica',
        'terciario'     => 'Terciario',
        'universitario' => 'Universitario',
        'otro'          => 'Otro / varios niveles',
    ];

    protected function tieneRol(User $user, array $roles): bool
    {
        return $this->usuarios()
            ->where('users.id', $user->id)
            ->wherePivotIn('rol', $roles)
            ->wherePivot('activo', true)
            ->exists();
    }

    /** Administrador de la institución (o superadmin). */
    public function esAdmin(User $user): bool
    {
        return $user->esSuperadmin() || $this->tieneRol($user, ['admin']);
    }

    /** Docente o administrador: puede crear aulas de la institución. */
    public function esDocente(User $user): bool
    {
        return $this->tieneRol($user, ['docente', 'admin']);
    }

    public function esAlumno(User $user): bool
    {
        return $this->tieneRol($user, ['alumno']);
    }

    public function getNivelLabelAttribute(): ?string
    {
        return $this->nivel ? (self::NIVELES[$this->nivel] ?? $this->nivel) : null;
    }
}
