<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColumnaLibreta extends Model
{
    protected $table = 'columnas_libreta';

    protected $fillable = ['aula_id', 'periodo_id', 'nombre', 'puntaje_maximo', 'peso', 'fecha'];

    protected function casts(): array
    {
        return [
            'puntaje_maximo' => 'decimal:2',
            'peso'           => 'decimal:2',
            'fecha'          => 'date',
        ];
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function notas()
    {
        return $this->hasMany(NotaManual::class, 'columna_id');
    }
}
