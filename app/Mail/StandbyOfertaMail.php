<?php

namespace App\Mail;

use App\Models\StandbyOferta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StandbyOfertaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StandbyOferta $oferta) {}

    public function build()
    {
        // Aseguramos relaciones disponibles
        $oferta = $this->oferta->load(['cupo','user']);
        $cupo   = $oferta->cupo;

        $url = route('standby.oferta.aceptar', ['token' => $oferta->token]);

        return $this->subject('Oferta de reemplazo de almuerzo')
            ->view('emails.standby.oferta', [
                'oferta' => $oferta,
                'cupo'   => $cupo,
                'url'    => $url,
            ]);
    }
}