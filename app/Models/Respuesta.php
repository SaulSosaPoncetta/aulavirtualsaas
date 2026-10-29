<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Respuesta extends Model
{
    protected $table = 'respuestas';

    protected $fillable = ['intento_id', 'pregunta_id', 'opciones_ids', 'texto', 'correcta', 'puntos', 'comentario'];

    protected function casts(): array
    {
        return [
            'opciones_ids' => 'array',
            'correcta'     => 'boolean',
            'puntos'       => 'decimal:2',
        ];
    }

    public function intento()
    {
        return $this->belongsTo(Intento::class);
    }

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function eligio(int $opcionId): bool
    {
        return in_array($opcionId, array_map('intval', (array) $this->opciones_ids), true);
    }
}
