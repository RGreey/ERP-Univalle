<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventoPDFMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $asunto;
    public $contenido;
    public $pdf;

    public function __construct($asunto, $contenido, $pdf)
    {
        $this->asunto = $asunto;
        $this->contenido = $contenido;
        $this->pdf = $pdf;
    }

    public function build()
    {
        return $this->view('emails.evento')
            ->subject($this->asunto)
            ->with(['contenido' => $this->contenido])
            ->attachData($this->pdf, 'evento.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}