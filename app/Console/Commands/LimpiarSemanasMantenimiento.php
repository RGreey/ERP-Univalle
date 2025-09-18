<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SemanaMantenimiento;

class LimpiarSemanasMantenimiento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mantenimiento:limpiar-semanas {--anio= : Año específico a limpiar (opcional)} {--confirmar : Confirmar sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar todas las semanas de mantenimiento de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $anio = $this->option('anio');
        $confirmar = $this->option('confirmar');

        // Contar registros a eliminar
        $query = SemanaMantenimiento::query();
        if ($anio) {
            $query->where('anio', $anio);
            $this->info("Se encontraron semanas para el año: {$anio}");
        } else {
            $this->info("Se encontraron semanas para todos los años");
        }
        
        $totalSemanas = $query->count();
        
        if ($totalSemanas === 0) {
            $this->info('No hay semanas de mantenimiento para eliminar.');
            return 0;
        }

        $this->warn("Se van a eliminar {$totalSemanas} registros de semanas de mantenimiento.");
        
        if ($anio) {
            $this->warn("Año específico: {$anio}");
        }

        if (!$confirmar) {
            if (!$this->confirm('¿Estás seguro de que quieres continuar?')) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        // Mostrar progreso
        $bar = $this->output->createProgressBar($totalSemanas);
        $bar->start();

        // Eliminar en lotes para mejor rendimiento
        $query->chunk(1000, function ($semanas) use ($bar) {
            foreach ($semanas as $semana) {
                $semana->delete();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        
        $this->info("¡Completado! Se eliminaron {$totalSemanas} registros de semanas de mantenimiento.");
        
        if ($anio) {
            $this->info("Año limpiado: {$anio}");
        } else {
            $this->info("Se limpiaron todas las semanas de todos los años.");
        }

        return 0;
    }
}
