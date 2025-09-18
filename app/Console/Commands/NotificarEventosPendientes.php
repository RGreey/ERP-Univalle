<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\NotificacionEventoPendiente;

class NotificarEventosPendientes extends Command
{
    protected $signature = 'eventos:notificar-pendientes';
    protected $description = 'Notifica a coordinacion administrativa si hay eventos sin gestionar a 2 días de su fecha de realización';

    public function handle()
    {
        $fechaObjetivo = Carbon::today()->addDays(2)->toDateString();

        $eventos = Evento::where('estado', 'creado')
                    ->whereDate('fechaRealizacion', $fechaObjetivo)
                    ->get();

        if ($eventos->isEmpty()) {
            $this->info("No hay eventos pendientes de gestión en dos días.");
            return;
        }

        $admin = User::where('email', 'sebastian.gply@gmail.com')->first(); // Cambia por el correo real

        if ($admin) {
            Mail::to($admin->email)->send(new NotificacionEventoPendiente($eventos));
            $this->info("Se ha notificado al administrador sobre eventos pendientes.");
        } else {
            $this->error("No se encontró el usuario administrador.");
        }
    }
}

