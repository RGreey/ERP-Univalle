<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Anotacion;

class AnotacionAgregadaMail extends Mailable
{
    public $anotacion;
    public $nombreEvento;

    public function __construct(Anotacion $anotacion, $nombreEvento)
    {
        $this->anotacion = $anotacion;
        $this->nombreEvento = $nombreEvento;
    }

    public function build()
    {
        $mail = $this->subject('Nueva Anotación Agregada')
                    ->view('emails.anotacion_agregada');
    
        // Verificar si el archivo existe y es un archivo no vacío
        $archivoPath = storage_path('app/public/' . $this->anotacion->archivo);
        if (!empty($this->anotacion->archivo) && file_exists($archivoPath)) {
            $mail->attach($archivoPath); // Adjuntar el archivo solo si existe
        }
    
        return $mail;
    }
}
