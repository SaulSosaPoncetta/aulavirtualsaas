<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pago extends Model
{
    protected $table = 'pagos';

    public const METODOS = [
        'mercadopago'   => 'Mercado Pago',
        'transferencia' => 'Transferencia',
        'efectivo'      => 'Efectivo',
        'otro'          => 'Otro',
    ];

    public const ESTADOS = [
        'pendiente' => ['Pendiente', 'warning'],
        'aprobado'  => ['Aprobado', 'success'],
        'rechazado' => ['Rechazado', 'danger'],
        'cancelado' => ['Cancelado', 'secondary'],
    ];

    protected $fillable = [
        'abono_id', 'concepto', 'metodo', 'estado', 'meses', 'cupo_docentes', 'importe',
        'comprobante_path', 'observaciones', 'mp_preference_id', 'mp_payment_id', 'mp_status',
        'aprobado_at', 'creado_por', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'importe'       => 'decimal:2',
            'meses'         => 'integer',
            'cupo_docentes' => 'integer',
            'aprobado_at'   => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Pago $p) {
            if ($p->comprobante_path) {
                Storage::disk('local')->delete($p->comprobante_path);
            }
        });
    }

    public function abono()
    {
        return $this->belongsTo(Abono::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function getMetodoLabelAttribute(): string
    {
        return self::METODOS[$this->metodo] ?? $this->metodo;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado][0] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return self::ESTADOS[$this->estado][1] ?? 'secondary';
    }

    public function getDetalleAttribute(): string
    {
        if ($this->concepto === 'ampliacion') {
            return "Ampliación a {$this->cupo_docentes} docentes";
        }

        $txt = $this->meses . ' ' . ($this->meses === 1 ? 'mes' : 'meses');

        return $this->cupo_docentes ? "{$txt} · {$this->cupo_docentes} docentes" : $txt;
    }
}
