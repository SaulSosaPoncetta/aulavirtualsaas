<?php

namespace App\Services;

/**
 * Cuentas de precios, sin base de datos (así se pueden probar solas).
 *
 * - Escala: ['desde' => int, 'hasta' => ?int, 'precio' => float] (precio por unidad).
 * - Renovación: precio por unidad según la escala de la cantidad total × cantidad × meses.
 * - Ampliación (subir el cupo con el abono vigente): unidades nuevas × precio de la escala del nuevo cupo
 *   × meses que faltan para el vencimiento.
 */
class Cotizador
{
    /** Precio por unidad para una cantidad. Sin escalas, el precio base. null = ninguna escala la cubre. */
    public static function precioUnitario(float $precioBase, array $escalas, int $cantidad): ?float
    {
        if (empty($escalas)) {
            return $precioBase;
        }

        foreach ($escalas as $e) {
            $desde = (int) $e['desde'];
            $hasta = isset($e['hasta']) && $e['hasta'] !== null ? (int) $e['hasta'] : null;

            if ($cantidad >= $desde && ($hasta === null || $cantidad <= $hasta)) {
                return (float) $e['precio'];
            }
        }

        return null;
    }

    public static function renovacion(float $precioUnitario, int $cantidad, int $meses, bool $porUnidad, bool $precioMensual): float
    {
        $base = $porUnidad ? $precioUnitario * max($cantidad, 1) : $precioUnitario;

        return round($precioMensual ? $base * max($meses, 1) : $base, 2);
    }

    public static function ampliacion(float $precioUnitarioNuevoCupo, int $unidadesExtra, int $mesesRestantes): float
    {
        return round($precioUnitarioNuevoCupo * max($unidadesExtra, 0) * max($mesesRestantes, 1), 2);
    }

    /** Meses (de 30 días) que faltan, redondeado para arriba, mínimo 1. */
    public static function mesesRestantes(int $diasHastaVencimiento): int
    {
        return $diasHastaVencimiento <= 0 ? 1 : max(1, (int) ceil(($diasHastaVencimiento + 1) / 30));
    }
}
