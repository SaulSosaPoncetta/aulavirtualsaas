<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestablecerPassword extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        $minutos = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Restablecer contraseña — ' . config('app.name'))
            ->greeting('Hola, ' . $notifiable->name)
            ->line('Recibimos un pedido para restablecer la contraseña de tu cuenta del Aula Virtual.')
            ->action('Restablecer contraseña', $url)
            ->line("El enlace vence en {$minutos} minutos.")
            ->line('Si no lo pediste vos, ignorá este mensaje: tu contraseña no cambia.')
            ->salutation('Aula Virtual — MiGestión');
    }
}
