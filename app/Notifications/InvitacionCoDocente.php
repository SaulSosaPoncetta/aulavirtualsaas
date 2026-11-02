<?php

namespace App\Notifications;

use App\Models\InvitacionAula;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitacionCoDocente extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public InvitacionAula $invitacion, public bool $tieneCuenta)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $i    = $this->invitacion;
        $aula = $i->aula;

        $mail = (new MailMessage)
            ->subject('Te invitaron a ser co-docente — ' . $aula->nombre)
            ->greeting('¡Hola!')
            ->line(($i->invitador?->nombre_completo ?? 'Un docente') . ' te invitó a dar clases juntos en el aula **' . $aula->nombre . '**'
                . ($aula->nombre_escuela ? ' (' . $aula->nombre_escuela . ')' : '') . ' del Aula Virtual.')
            ->line('Como co-docente vas a poder publicar clases, tareas y evaluaciones, y corregir.');

        if (! $this->tieneCuenta) {
            $mail->line('Todavía no tenés cuenta: registrate como docente **con este mismo email** y después abrí el enlace.');
        }

        return $mail
            ->action('Ver la invitación', route('invitaciones.ver', $i->token))
            ->line('La invitación vence el ' . $i->vence_at->format('d/m/Y') . '.')
            ->salutation('Aula Virtual — ' . config('empresa.marca'));
    }
}
