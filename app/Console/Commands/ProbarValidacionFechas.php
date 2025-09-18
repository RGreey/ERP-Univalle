<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitor;
use Carbon\Carbon;

class ProbarValidacionFechas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fechas:probar-validacion {--monitor_id= : ID específico del monitor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la validación de fechas de vinculación y culminación';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitorId = $this->option('monitor_id');
        $hoy = Carbon::today();

        $this->info('📅 PRUEBA DE VALIDACIÓN DE FECHAS');
        $this->info('================================');

        // Obtener monitores
        $query = Monitor::with('user');
        if ($monitorId) {
            $query->where('id', $monitorId);
        }
        $monitores = $query->get();

        if ($monitores->isEmpty()) {
            $this->error('No se encontraron monitores.');
            return;
        }

        foreach ($monitores as $monitor) {
            $user = \App\Models\User::find($monitor->user);
            $nombreMonitor = $user ? $user->name : 'Sin nombre';

            $this->info("\n👤 Monitor: {$nombreMonitor} (ID: {$monitor->id})");
            $this->info(str_repeat('-', 50));

            if (!$monitor->fecha_vinculacion || !$monitor->fecha_culminacion) {
                $this->warn("  ❌ No tiene fechas configuradas");
                $this->line("     Fecha vinculación: " . ($monitor->fecha_vinculacion ?? 'No definida'));
                $this->line("     Fecha culminación: " . ($monitor->fecha_culminacion ?? 'No definida'));
                continue;
            }

            $fechaInicio = Carbon::parse($monitor->fecha_vinculacion);
            $fechaFin = Carbon::parse($monitor->fecha_culminacion);
            $enPeriodoValido = $hoy->between($fechaInicio, $fechaFin);
            $diasRestantes = $hoy->diffInDays($fechaFin, false);

            $this->line("  📅 Fecha vinculación: {$fechaInicio->format('d/m/Y')}");
            $this->line("  📅 Fecha culminación: {$fechaFin->format('d/m/Y')}");
            $this->line("  📅 Fecha actual: {$hoy->format('d/m/Y')}");

            if ($enPeriodoValido) {
                $this->info("  ✅ En período válido");
                if ($diasRestantes >= 0 && $diasRestantes <= 3) {
                    $this->warn("  ⚠️  Finaliza en {$diasRestantes} días");
                }
            } else {
                if ($hoy->lt($fechaInicio)) {
                    $diasParaIniciar = $hoy->diffInDays($fechaInicio, false);
                    $this->error("  ❌ Monitoría aún no inicia (faltan {$diasParaIniciar} días)");
                } else {
                    $this->error("  ❌ Monitoría ya culminó (hace " . abs($diasRestantes) . " días)");
                }
            }

            // Probar fechas específicas
            $this->line("\n  🧪 Pruebas de fechas:");
            
            $fechasPrueba = [
                $fechaInicio->copy()->subDays(1),
                $fechaInicio,
                $fechaInicio->copy()->addDays(5),
                $fechaFin->copy()->subDays(5),
                $fechaFin,
                $fechaFin->copy()->addDays(1)
            ];

            foreach ($fechasPrueba as $fechaPrueba) {
                $esValida = $fechaPrueba->between($fechaInicio, $fechaFin);
                $estado = $esValida ? '✅' : '❌';
                $this->line("     {$estado} {$fechaPrueba->format('d/m/Y')}");
            }
        }

        $this->info("\n📊 RESUMEN");
        $this->info(str_repeat('-', 30));
        
        $conFechas = $monitores->filter(function($m) {
            return $m->fecha_vinculacion && $m->fecha_culminacion;
        })->count();
        
        $enPeriodoValido = $monitores->filter(function($m) use ($hoy) {
            if (!$m->fecha_vinculacion || !$m->fecha_culminacion) return false;
            return $hoy->between(
                Carbon::parse($m->fecha_vinculacion),
                Carbon::parse($m->fecha_culminacion)
            );
        })->count();

        $this->line("Total monitores: {$monitores->count()}");
        $this->line("Con fechas configuradas: {$conFechas}");
        $this->line("En período válido: {$enPeriodoValido}");
    }
}
