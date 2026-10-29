<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    protected $table = 'unidades';

    protected $fillable = ['aula_id', 'titulo', 'descripcion', 'orden', 'visible'];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'orden'   => 'integer',
        ];
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function clases()
    {
        return $this->hasMany(Clase::class)->orderBy('orden')->orderBy('id');
    }
}
