<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $table = 'instituciones';

    protected $fillable = [
        'sgi_id',
        'nombre',
        'cue',
        'nivel',
        'localidad',
        'logo',
        'origen',
        'activo',
        'sincronizado_at',
    ];

    protected function casts(): array
    {
        return [
            'activo'          => 'boolean',
            'sincronizado_at' => 'datetime',
        ];
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'institucion_user')
            ->withPivot('rol', 'activo')
            ->withTimestamps();
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

    public function usaSgi(): bool
    {
        return ! is_null($this->sgi_id);
    }
}
