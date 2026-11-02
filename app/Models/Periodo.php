<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    protected $table = 'periodos';

    protected $fillable = ['aula_id', 'nombre', 'desde', 'hasta', 'orden'];

    protected function casts(): array
    {
        return [
            'desde' => 'date',
            'hasta' => 'date',
            'orden' => 'integer',
        ];
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function cierres()
    {
        return $this->hasMany(CierrePeriodo::class);
    }

    public function contiene(?Carbon $fecha): bool
    {
        if (! $fecha) {
            return false;
        }

        $f = $fecha->format('Y-m-d');

        return $f >= $this->desde->format('Y-m-d') && $f <= $this->hasta->format('Y-m-d');
    }

    /** Juegos de períodos sugeridos para un ciclo lectivo. */
    public static function plantillas(int $anio): array
    {
        return [
            'trimestres' => [
                ['1° Trimestre', "$anio-03-01", "$anio-05-31"],
                ['2° Trimestre', "$anio-06-01", "$anio-08-31"],
                ['3° Trimestre', "$anio-09-01", "$anio-12-31"],
            ],
            'cuatrimestres' => [
                ['1° Cuatrimestre', "$anio-03-01", "$anio-07-15"],
                ['2° Cuatrimestre', "$anio-07-16", "$anio-12-31"],
            ],
            'bimestres' => [
                ['1° Bimestre', "$anio-03-01", "$anio-04-30"],
                ['2° Bimestre', "$anio-05-01", "$anio-07-15"],
                ['3° Bimestre', "$anio-07-16", "$anio-09-30"],
                ['4° Bimestre', "$anio-10-01", "$anio-12-31"],
            ],
            'anual' => [
                ['Anual', "$anio-03-01", "$anio-12-31"],
            ],
        ];
    }
}
