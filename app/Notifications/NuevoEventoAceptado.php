<?php

namespace App\Notifications;

use App\Models\Espacio;
use App\Models\Lugar;
use App\Models\User; 
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevoEventoAceptado extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($evento)
    {
        $this->evento = $evento;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Obtener el nombre del lugar asociado al evento si existe
        $nombreLugar = null;
        $lugar = Lugar::find($this->evento->lugar);
        if ($lugar) {
            $nombreLugar = $lugar->nombreLugar;
        }

        // Obtener el nombre del espacio asociado al evento si existe
        $nombreEspacio = null;
        $espacio = Espacio::find($this->evento->espacio);
        if ($espacio) {
            $nombreEspacio = $espacio->nombreEspacio;
        }

        // Obtener el nombre del organizador (creador del evento)
        $organizador = User::find($this->evento->user);
        $nombreOrganizador = $organizador ? $organizador->name : 'No disponible';

        return (new MailMessage)
            ->from('notificaciones@erpmanager.cloud', 'ErpManager')
            ->subject('Evento Aceptado')
            ->line('Se ha creado un nuevo evento en nuestra plataforma. A continuación, te proporcionamos los detalles:')
            ->line('Nombre del evento: ' . $this->evento->nombreEvento)
            ->line('Fecha de realización: ' . $this->evento->fechaRealizacion)
            ->line('Hora de inicio: ' . $this->evento->horaInicio)
            ->line('Hora finalización: ' . $this->evento->horaFin)
            ->line('Lugar: ' . $nombreLugar)
            ->line('Espacio: ' . $nombreEspacio)
            ->line('Organizador: ' . $nombreOrganizador) // Agrega el nombre del organizador
            ->line('Gracias por utilizar nuestra aplicación!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
