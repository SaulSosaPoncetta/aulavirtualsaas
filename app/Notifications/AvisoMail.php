<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mismo aviso, por email (en cola, para no demorar la página).
 */
class AvisoMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $datos)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->datos;

        $mail = (new MailMessage)
            ->subject($d['titulo'] . (! empty($d['aula']) ? ' — ' . $d['aula'] : ''))
            ->greeting('¡Hola, ' . $notifiable->name . '!');

        if (! empty($d['aula'])) {
            $mail->line('**' . $d['aula'] . '**');
        }

        return $mail
            ->line($d['texto'])
            ->action('Ver en el Aula Virtual', $d['url'])
            ->line('Podés dejar de recibir estos emails desde "Notificaciones" en el Aula Virtual.')
            ->salutation('Aula Virtual — ' . config('empresa.marca'));
    }
}
