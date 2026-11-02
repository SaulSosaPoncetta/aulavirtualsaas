<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaManual extends Model
{
    protected $table = 'notas_manuales';

    protected $fillable = ['columna_id', 'alumno_id', 'nota', 'observacion'];

    protected function casts(): array
    {
        return ['nota' => 'decimal:2'];
    }

    public function columna()
    {
        return $this->belongsTo(ColumnaLibreta::class, 'columna_id');
    }
}
