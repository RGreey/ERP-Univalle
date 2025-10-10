<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('eventos:enviar-del-dia');
        $schedule->command('eventos:notificar-pendientes')->everyMinute();
        $schedule->command('subsidio:cerrar-dia')->dailyAt('23:59')->timezone(config('subsidio.timezone', 'America/Bogota'));
        $hora = config('subsidio.hora_corte_marcaje', '15:00');
        $schedule->command('subsidio:cerrar-dia')->weekdays()->at($hora);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
        
    }
}
