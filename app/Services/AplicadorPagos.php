<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Suscripcion;
use Illuminate\Support\Carbon;

/**
 * Único lugar donde un pago aprobado "se aplica" a una suscripción: calcula el nuevo
 * vencimiento, deja la suscripción activa y le avisa al sistema satélite.
 * Lo usan el webhook de Mercado Pago y la aprobación manual de transferencias.
 *
 * Con un Pago que trae sus datos (concepto, meses, cantidad, plan) se aplica eso.
 * Sin Pago (débito automático de Mercado Pago) se usa la duración del plan de la suscripción.
 */
class AplicadorPagos
{
    public function __construct(protected NotificadorSistemaSatelite $notificador)
    {
    }

    public function acreditar(Suscripcion $suscripcion, ?Pago $pago = null): Suscripcion
    {
        $plan = $pago?->plan ?? $suscripcion->planCatalogo;

        // Fechas como texto "Y-m-d" en la zona horaria del negocio, para no mezclar husos.
        $hoy = Carbon::today(config('pagos.zona_horaria'))->toDateString();

        if ($pago && $pago->concepto === 'ampliacion') {
            // Subir el cupo no cambia el vencimiento ni reactiva un abono vencido
            $suscripcion->update(['cantidad' => (int) $pago->cantidad]);
        } else {
            // Meses que cubre este pago. Sin datos: las recurrentes cubren 1 mes y las de pago único, sin vencimiento.
            $meses = $pago?->meses ?: ($plan ? $plan->duracion_meses : ($suscripcion->tipo === 'recurrente' ? 1 : null));
            $vencimiento = null;

            if ($meses !== null) {
                $actual = $suscripcion->fecha_vencimiento?->toDateString();

                // Si todavía le quedaban días, el nuevo período se suma al final del actual (no pierde días).
                $base = $actual && $actual >= $hoy ? $actual : $hoy;

                $vencimiento = Carbon::parse($base)->addMonthsNoOverflow((int) $meses)->toDateString();
            }

            $tipo = $plan?->tipo ?? $suscripcion->tipo;

            $datos = [
                'estado' => 'activa',
                'fecha_inicio' => $suscripcion->fecha_inicio ?? $hoy,
                'fecha_vencimiento' => $vencimiento,
                'proxima_fecha_cobro' => $tipo === 'recurrente' ? $vencimiento : null,
                'aviso_umbral' => null,
            ];

            if ($pago) {
                $datos += [
                    'id_plan' => $plan?->id_plan ?? $suscripcion->id_plan,
                    'plan' => $plan?->nombre ?? $suscripcion->plan,
                    'tipo' => $tipo,
                    'cantidad' => $pago->cantidad ?: $suscripcion->cantidad,
                    'monto' => $pago->monto,
                    'moneda' => $pago->moneda,
                ];
            }

            $suscripcion->update($datos);
        }

        $suscripcion = $suscripcion->fresh();
        $this->notificador->avisarSuscripcion($suscripcion);

        return $suscripcion;
    }
}
