<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Postulado;
use App\Models\User;

class EntrevistaProgramadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $postulado;
    public $user;
    public $monitoria;
    public $entrevistador;

    public function __construct($postulado, $user, $monitoria, $entrevistador)
    {
        $this->postulado = $postulado;
        $this->user = $user;
        $this->monitoria = $monitoria;
        $this->entrevistador = $entrevistador;
    }

    public function build()
    {
        $asunto = "Entrevista Programada - Monitoría: " . $this->monitoria->nombre;
        
        return $this->view('emails.entrevista_programada')
                    ->subject($asunto)
                    ->with([
                        'postulado' => $this->postulado,
                        'user' => $this->user,
                        'monitoria' => $this->monitoria,
                        'entrevistador' => $this->entrevistador
                    ]);
    }
}
