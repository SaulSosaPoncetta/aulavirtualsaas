<?php

namespace App\Services;

use App\Models\Abono;
use App\Models\Plan;
use Illuminate\Support\Collection;

/**
 * Precios y cotizaciones de los abonos.
 */
class AbonoService
{
    public function planUnipersonal(): ?Plan
    {
        return Plan::activos()->where('tipo', 'unipersonal')->first();
    }

    /** @return Collection<Plan> escalas institucionales ordenadas */
    public function escalasInstitucionales(): Collection
    {
        return Plan::activos()->where('tipo', 'institucional')->get();
    }

    /** Precio mensual por docente según la cantidad contratada. */
    public function precioPorDocente(int $docentes): ?float
    {
        $escala = $this->escalasInstitucionales()->first(fn ($p) => $docentes >= (int) $p->docentes_desde
            && ($p->docentes_hasta === null || $docentes <= (int) $p->docentes_hasta));

        return $escala ? (float) $escala->precio : null;
    }

    /** Importe de una renovación. */
    public function cotizarRenovacion(Abono $abono, int $meses, ?int $cupo = null): ?float
    {
        if ($abono->tipo === 'unipersonal') {
            $plan = $this->planUnipersonal();

            return $plan ? round((float) $plan->precio * $meses, 2) : null;
        }

        $cupo   = max(1, (int) $cupo);
        $precio = $this->precioPorDocente($cupo);

        return $precio !== null ? round($precio * $cupo * $meses, 2) : null;
    }

    /**
     * Importe para ampliar el cupo de un abono institucional activo:
     * docentes nuevos × precio de la nueva escala × meses que faltan para el vencimiento.
     */
    public function cotizarAmpliacion(Abono $abono, int $nuevoCupo): ?float
    {
        $extra = $nuevoCupo - (int) $abono->cupo_docentes;

        if ($extra <= 0 || ! $abono->estaActivo()) {
            return null;
        }

        $precio = $this->precioPorDocente($nuevoCupo);

        return $precio !== null ? round($precio * $extra * $abono->mesesRestantes(), 2) : null;
    }

    public function opcionesMeses(): array
    {
        return config('abonos.meses', [1, 3, 6, 12]);
    }
}
