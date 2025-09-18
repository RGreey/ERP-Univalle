<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Espacio;
use App\Models\ProgramaDependencia;

class EventosDelDiaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $eventos;

    /**
     * Create a new message instance.
     */
    public function __construct($eventos)
    {
        $this->eventos = $eventos;
    
        $this->nombresEventos = [];
    
        foreach ($eventos as $evento) {
            // Buscar el nombre del espacio
            $espacio = Espacio::find($evento->espacio);
            $nombreEspacio = $espacio ? $espacio->nombreEspacio : null;
    
            // Buscar todas las dependencias del evento usando la tabla pivote
            $dependenciasIds = \DB::table('evento_dependencia')
                ->where('evento_id', $evento->id)
                ->pluck('programadependencia_id');
    
            // Obtener los nombres de las dependencias
            $nombresDependencias = ProgramaDependencia::whereIn('id', $dependenciasIds)
                ->pluck('nombrePD')
                ->toArray();
    
            // Armamos el array para la vista
            $this->nombresEventos[] = [
                'evento' => $evento->nombreEvento,
                'horaInicio' => $evento->horaInicio,
                'propositoEvento' => $evento->propositoEvento,
                'nombreEspacio' => $nombreEspacio,
                'nombresDependencias' => $nombresDependencias,
            ];
        }
    }
    

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Eventos agendados para hoy',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.eventos-del-dia',
            with: [
                'eventos' => $this->eventos,
                'nombresEventos' => $this->nombresEventos,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
