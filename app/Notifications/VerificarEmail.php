<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/** Se envía en cola: la página responde al instante y el mail sale por detrás. */
class VerificarEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 60;

    public const MINUTOS = 1440; // 24 horas

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(self::MINUTOS),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        return (new MailMessage)
            ->subject('Confirmá tu email — ' . config('app.name'))
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line('Gracias por registrarte en el Aula Virtual. Para activar tu cuenta, confirmá tu dirección de email.')
            ->action('Confirmar mi email', $url)
            ->line('El enlace vence en 24 horas.')
            ->line('Si no creaste una cuenta, ignorá este mensaje.')
            ->salutation('Aula Virtual — ' . config('empresa.marca'));
    }
}
