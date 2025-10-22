<?php

namespace App\Mail;

use App\Models\StandbyOferta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StandbyConfirmacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StandbyOferta $oferta) {}

    public function build()
    {
        $cupo = $this->oferta->cupo;

        return $this->subject('Confirmación de reemplazo asignado')
            ->view('emails.standby.confirmacion', [
                'oferta' => $this->oferta,
                'cupo'   => $cupo,
            ]);
    }
}