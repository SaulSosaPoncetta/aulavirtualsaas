<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'apellido',
        'dni',
        'email',
        'password',
        'origen',
        'gestionaula_id',
        'sgi_id',
        'avatar',
        'activo',
        'ultimo_acceso_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso_at'  => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
        ];
    }

    // ===================== Relaciones =====================

    public function instituciones()
    {
        return $this->belongsToMany(Institucion::class, 'institucion_user')
            ->withPivot('rol', 'activo')
            ->withTimestamps();
    }

    // Aulas que dicta (docente)
    public function aulasDocente()
    {
        return $this->hasMany(Aula::class, 'docente_id');
    }

    // Aulas en las que está inscripto (alumno)
    public function aulasAlumno()
    {
        return $this->belongsToMany(Aula::class, 'aula_alumno', 'alumno_id', 'aula_id')
            ->withPivot('origen', 'estado', 'fecha_alta', 'fecha_baja')
            ->withTimestamps();
    }

    // ===================== Ayudas =====================

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->apellido ? "{$this->apellido}, {$this->name}" : $this->name);
    }

    public function esDocente(): bool
    {
        return $this->hasRole('docente');
    }

    public function esAlumno(): bool
    {
        return $this->hasRole('alumno');
    }
}
