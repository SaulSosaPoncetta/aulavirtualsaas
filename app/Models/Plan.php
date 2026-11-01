<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = ['tipo', 'nombre', 'descripcion', 'precio', 'docentes_desde', 'docentes_hasta', 'activo', 'orden'];

    protected function casts(): array
    {
        return [
            'precio'         => 'decimal:2',
            'docentes_desde' => 'integer',
            'docentes_hasta' => 'integer',
            'activo'         => 'boolean',
        ];
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true)->orderBy('orden')->orderBy('docentes_desde');
    }

    public function getRangoTextoAttribute(): string
    {
        if ($this->tipo !== 'institucional') {
            return '';
        }

        return $this->docentes_hasta
            ? "De {$this->docentes_desde} a {$this->docentes_hasta} docentes"
            : "Desde {$this->docentes_desde} docentes";
    }

    public static function pesos($valor): string
    {
        return '$ ' . number_format((float) $valor, 0, ',', '.');
    }
}
