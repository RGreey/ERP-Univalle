<?php

namespace App\Mail;

use App\Models\Novedad;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MantenimientoRealizadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $novedad;

    /**
     * Create a new message instance.
     */
    public function __construct(Novedad $novedad)
    {
        $this->novedad = $novedad;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Mantenimiento realizado: ' . $this->novedad->titulo)
            ->view('emails.mantenimiento_realizado');
    }
} 