<?php

namespace App\Observers;

use App\Models\Clase;
use App\Models\Comentario;
use App\Models\Entrega;
use App\Models\Intento;
use App\Models\Pago;
use App\Models\Publicacion;
use App\Models\Tarea;
use App\Services\Notificador;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Escucha lo que pasa en las aulas y dispara las notificaciones.
 * Se ejecuta después de confirmar la transacción, y si algo falla
 * al notificar no se interrumpe la acción del usuario.
 */
class ActividadObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(protected Notificador $n)
    {
    }

    public function created(Model $m): void
    {
        $this->seguro(function () use ($m) {
            match (true) {
                $m instanceof Clase, $m instanceof Tarea => $this->n->contenidoPublicado($m),
                $m instanceof Entrega                    => $this->n->entrega($m),
                $m instanceof Publicacion                => $this->n->publicacion($m),
                $m instanceof Comentario                 => $this->n->comentario($m),
                default                                  => null,
            };
        });
    }

    public function updated(Model $m): void
    {
        $this->seguro(function () use ($m) {
            if ($m instanceof Clase || $m instanceof Tarea) {
                // Por ejemplo: estaba oculta y ahora se hizo visible
                $this->n->contenidoPublicado($m);
            } elseif ($m instanceof Entrega && ($m->wasChanged('estado') || $m->wasChanged('entregada_at'))) {
                $this->n->entrega($m);
            } elseif ($m instanceof Intento && $m->wasChanged('estado')) {
                $this->n->intento($m);
            } elseif ($m instanceof Pago && $m->wasChanged('estado') && $m->estado === 'aprobado') {
                $this->n->pagoAprobado($m);
            }
        });
    }

    protected function seguro(callable $accion): void
    {
        try {
            $accion();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
