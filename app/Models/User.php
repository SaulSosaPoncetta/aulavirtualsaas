<?php

namespace App\Models;

use App\Notifications\RestablecerPassword;
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
        'debe_cambiar_password',
        'telefono',
        'fecha_nacimiento',
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
            'email_verified_at'     => 'datetime',
            'ultimo_acceso_at'      => 'datetime',
            'fecha_nacimiento'      => 'date',
            'password'              => 'hashed',
            'activo'                => 'boolean',
            'debe_cambiar_password' => 'boolean',
        ];
    }

    // ===================== Búsqueda para el login =====================

    /** Busca por email (si tiene @) o por DNI (solo números). */
    public static function buscarPorUsuario(string $valor): ?self
    {
        $valor = trim($valor);

        if (str_contains($valor, '@')) {
            return static::where('email', mb_strtolower($valor))->first();
        }

        $dni = preg_replace('/\D/', '', $valor);

        return $dni !== '' ? static::where('dni', $dni)->first() : null;
    }

    // ===================== Relaciones =====================

    public function instituciones()
    {
        return $this->belongsToMany(Institucion::class, 'institucion_user')
            ->withPivot('rol', 'activo')
            ->withTimestamps();
    }

    // Aulas de las que es titular
    public function aulasTitular()
    {
        return $this->hasMany(Aula::class, 'docente_id');
    }

    // Todas las aulas en las que participa (como docente o alumno)
    public function aulas()
    {
        return $this->belongsToMany(Aula::class, 'aula_user')
            ->withPivot('rol', 'origen', 'estado', 'fecha_alta', 'fecha_baja')
            ->withTimestamps();
    }

    public function aulasComoDocente()
    {
        return $this->aulas()->wherePivot('rol', 'docente')->wherePivot('estado', 'activo');
    }

    public function aulasComoAlumno()
    {
        return $this->aulas()->wherePivot('rol', 'alumno')->wherePivot('estado', 'activo');
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

    public function esSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerPassword($token));
    }
}
