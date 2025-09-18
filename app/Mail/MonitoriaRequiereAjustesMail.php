<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Monitoria;
use App\Models\User;

class MonitoriaRequiereAjustesMail extends Mailable
{
    use Queueable, SerializesModels;

    public $monitoria;
    public $comentarios;
    public $encargado;

    public function __construct(Monitoria $monitoria, ?string $comentarios, ?User $encargado)
    {
        $this->monitoria = $monitoria;
        $this->comentarios = $comentarios;
        $this->encargado = $encargado;
    }

    public function build()
    {
        return $this->subject('Monitoría requiere ajustes: ' . ($this->monitoria->nombre ?? ''))
            ->view('emails.monitoria_requiere_ajustes');
    }
}



