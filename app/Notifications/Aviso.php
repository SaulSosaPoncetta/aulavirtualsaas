<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Aviso de la campanita (se guarda en la base, al instante).
 * $datos: tipo, titulo, texto, url, icono, aula
 */
class Aviso extends Notification
{
    public function __construct(public array $datos)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->datos;
    }
}
