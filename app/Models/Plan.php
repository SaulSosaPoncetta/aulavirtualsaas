<?php

namespace App\Models;

use App\Services\Cotizador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';
    protected $primaryKey = 'id_plan';

    protected $fillable = [
        'id_sistema', 'codigo', 'nombre', 'descripcion', 'tipo', 'duracion_meses', 'meses_permitidos',
        'precio', 'moneda', 'cobro_por_unidad', 'unidad_nombre',
        'unidad_minima', 'unidad_maxima', 'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'cobro_por_unidad' => 'boolean',
        'activo' => 'boolean',
    ];

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(SistemaSaas::class, 'id_sistema', 'id_sistema');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'id_plan', 'id_plan');
    }

    public function escalas(): HasMany
    {
        return $this->hasMany(PlanEscala::class, 'id_plan', 'id_plan')->orderBy('desde');
    }

    /** Con "meses permitidos", el precio es por mes y el cliente elige cuántos meses paga. */
    public function esMensual(): bool
    {
        return filled($this->meses_permitidos);
    }

    /** @return int[] meses que se pueden pagar de una vez */
    public function mesesOpciones(): array
    {
        if ($this->esMensual()) {
            $meses = collect(explode(',', (string) $this->meses_permitidos))
                ->map(fn ($m) => (int) trim($m))
                ->filter(fn ($m) => $m > 0)
                ->unique()->sort()->values()->all();

            if ($meses) {
                return $meses;
            }
        }

        return [max(1, (int) ($this->duracion_meses ?? 1))];
    }

    /** Precio por unidad para una cantidad (según la escala). null = no hay escala para esa cantidad. */
    public function precioUnitario(int $cantidad): ?float
    {
        $escalas = $this->cobro_por_unidad
            ? $this->escalas->map(fn ($e) => ['desde' => $e->desde, 'hasta' => $e->hasta, 'precio' => (float) $e->precio])->all()
            : [];

        return Cotizador::precioUnitario((float) $this->precio, $escalas, $cantidad);
    }

    /** Importe de renovar (o contratar). null = combinación sin precio. */
    public function cotizarRenovacion(int $cantidad, ?int $meses = null): ?float
    {
        $meses ??= $this->mesesOpciones()[0];

        if (! in_array($meses, $this->mesesOpciones(), true)) {
            return null;
        }

        $unitario = $this->precioUnitario($cantidad);

        return $unitario === null
            ? null
            : Cotizador::renovacion($unitario, $cantidad, $meses, $this->cobro_por_unidad, $this->esMensual());
    }

    /** Importe de subir el cupo con el abono vigente. null = no corresponde o no hay precio. */
    public function cotizarAmpliacion(int $cantidadActual, int $cantidadNueva, int $mesesRestantes): ?float
    {
        if (! $this->cobro_por_unidad || $cantidadNueva <= $cantidadActual) {
            return null;
        }

        $unitario = $this->precioUnitario($cantidadNueva);

        return $unitario === null ? null : Cotizador::ampliacion($unitario, $cantidadNueva - $cantidadActual, $mesesRestantes);
    }

    /** Se mantiene por compatibilidad con el código anterior. */
    public function calcularMonto(int $cantidad = 1): float
    {
        return $this->cotizarRenovacion($cantidad) ?? 0.0;
    }

    /** Texto del período: "Mensual", "Anual", "12 meses", "Sin vencimiento", "Por meses (1, 3, 6, 12)". */
    public function periodoTexto(): string
    {
        if ($this->esMensual()) {
            return 'Por mes · se paga por '.implode(', ', $this->mesesOpciones()).' meses';
        }

        return match (true) {
            $this->duracion_meses === null => 'Sin vencimiento',
            $this->duracion_meses === 1 => 'Mensual',
            $this->duracion_meses === 3 => 'Trimestral',
            $this->duracion_meses === 6 => 'Semestral',
            $this->duracion_meses === 12 => 'Anual',
            default => $this->duracion_meses.' meses',
        };
    }
}
