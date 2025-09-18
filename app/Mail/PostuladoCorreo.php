<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class PostuladoCorreo extends Mailable
{
    use Queueable, SerializesModels;

    public $postulado;
    public $detalles;
    public $imagen;
    public $user;
    public $instrucciones;
    public $monitoria_nombre;

    public function __construct($postulado, $detalles, $imagen, $user, $instrucciones, $monitoria_nombre)
    {
        $this->postulado = $postulado;
        $this->detalles = $detalles;
        $this->imagen = $imagen;
        $this->user = $user;
        $this->instrucciones = $instrucciones;
        $this->monitoria_nombre = $monitoria_nombre;
    }

    public function build()
    {
        $email = $this->view('emails.postuladoCorreo')
                    ->subject('Postulacion Monitoria')
                    ->with([
                        'postulado' => $this->postulado,
                        'detalles' => $this->detalles,
                        'imageUrl' => $this->imagen ? asset('storage/' . $this->imagen) : null,
                        'instrucciones' => $this->instrucciones,
                        'monitoria_nombre' => $this->monitoria_nombre
                    ]);

        if ($this->imagen && file_exists(storage_path('app/public/' . $this->imagen))) {
            $email->attach(storage_path('app/public/' . $this->imagen));
        }

        return $email;
    }
}

