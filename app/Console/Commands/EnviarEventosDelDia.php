<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Evento;
use App\Models\User;
use App\Mail\EventosDelDiaMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EnviarEventosDelDia extends Command
{
    protected $signature = 'eventos:enviar-del-dia';
    protected $description = 'Envía a todos los usuarios los eventos programados para hoy';

    public function handle()
    {
        $hoy = Carbon::today()->toDateString();

        $eventos = Evento::whereDate('fechaRealizacion', $hoy)
                        ->where('estado', 'aceptado')
                        ->get();

        if ($eventos->isEmpty()) {
            $this->info("No hay eventos aceptados para hoy. No se enviará ningún correo.");
            return;
        }

        $usuarios = User::all(); // Puedes filtrar si lo deseas

        foreach ($usuarios as $usuario) {
            Mail::to($usuario->email)->send(new EventosDelDiaMail($eventos));
        }

        $this->info('Correos enviados a todos los usuarios con los eventos aceptados del día.');
    }
}
