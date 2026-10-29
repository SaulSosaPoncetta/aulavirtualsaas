<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    protected $table = 'preguntas';

    public const TIPOS = [
        'unica'           => 'Opción única',
        'multiple'        => 'Opción múltiple',
        'verdadero_falso' => 'Verdadero / Falso',
        'texto'           => 'Respuesta escrita',
    ];

    protected $fillable = ['evaluacion_id', 'tipo', 'enunciado', 'puntos', 'explicacion', 'orden'];

    protected function casts(): array
    {
        return [
            'puntos' => 'decimal:2',
            'orden'  => 'integer',
        ];
    }

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function opciones()
    {
        return $this->hasMany(Opcion::class)->orderBy('orden')->orderBy('id');
    }

    public function esAutomatica(): bool
    {
        return $this->tipo !== 'texto';
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getPuntosTextoAttribute(): string
    {
        return Evaluacion::numero($this->puntos);
    }

    /**
     * Corrige una respuesta de opciones.
     * Opción múltiple: todo o nada (tiene que elegir exactamente las correctas).
     *
     * @return array{0: bool, 1: float} [correcta, puntos]
     */
    public function corregir(array $elegidas): array
    {
        $correctas = $this->opciones->where('es_correcta', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $elegidas  = collect($elegidas)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        $ok = $elegidas !== [] && $elegidas === $correctas;

        return [$ok, $ok ? (float) $this->puntos : 0.0];
    }
}
