<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RechazoEventoNotificacion extends Notification
{
    use Queueable;

    protected $evento;

    public function __construct($evento)
    {
        $this->evento = $evento;
    }

    public function via($notifiable)
    {
        return ['mail']; // Otras opciones pueden incluir 'database', 'broadcast', etc.
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Evento Rechazado')
            ->line('El evento "' . $this->evento->nombreEvento . '" ha sido rechazado.')
            ->line('Para conocer el motivo, revisa el historial de anotaciones de su evento.')
            ->line('Gracias por usar nuestra aplicación!');
    }

    public function toArray($notifiable)
    {
        return [
            // Puedes incluir información adicional si es necesario
        ];
    }
}