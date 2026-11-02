<?php

namespace App\Providers;

use App\Models\Clase;
use App\Models\Comentario;
use App\Models\Entrega;
use App\Models\Intento;
use App\Models\Pago;
use App\Models\Publicacion;
use App\Models\Tarea;
use App\Observers\ActividadObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Notificaciones automáticas
        foreach ([Clase::class, Tarea::class, Entrega::class, Intento::class, Publicacion::class, Comentario::class, Pago::class] as $modelo) {
            $modelo::observe(ActividadObserver::class);
        }
    }
}
