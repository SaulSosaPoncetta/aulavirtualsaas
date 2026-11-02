<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CierrePeriodo extends Model
{
    protected $table = 'cierres_periodo';

    protected $fillable = ['periodo_id', 'alumno_id', 'nota', 'observacion'];

    protected function casts(): array
    {
        return ['nota' => 'decimal:2'];
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }
}
