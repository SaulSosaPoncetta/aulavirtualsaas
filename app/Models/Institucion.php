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
}
