<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    protected $table = 'entregas';

    public const ESTADOS = [
        'entregada' => ['Entregada', 'primary'],
        'devuelta'  => ['Para rehacer', 'warning'],
        'corregida' => ['Corregida', 'success'],
    ];

    protected $fillable = [
        'tarea_id', 'alumno_id', 'texto', 'estado', 'entregada_at',
        'nota', 'devolucion', 'corregida_at', 'corregida_por',
    ];

    protected function casts(): array
    {
        return [
            'entregada_at' => 'datetime',
            'corregida_at' => 'datetime',
            'nota'         => 'decimal:2',
        ];
    }

    public function tarea()
    {
        return $this->belongsTo(Tarea::class)->withTrashed();
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

    public function correctora()
    {
        return $this->belongsTo(User::class, 'corregida_por');
    }

    public function archivos()
    {
        return $this->morphMany(Archivo::class, 'archivable')->orderBy('id');
    }

    public function esTardia(): bool
    {
        $limite = $this->tarea?->fecha_limite;

        return $limite && $this->entregada_at && $this->entregada_at->gt($limite);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado][0] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return self::ESTADOS[$this->estado][1] ?? 'secondary';
    }

    /** "7,5" en vez de "7.50" */
    public function getNotaTextoAttribute(): ?string
    {
        if ($this->nota === null) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $this->nota, 2, ',', ''), '0'), ',');
    }
}
