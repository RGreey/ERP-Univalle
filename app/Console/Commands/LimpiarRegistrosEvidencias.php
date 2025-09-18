<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\EvidenciaMantenimiento;

class LimpiarRegistrosEvidencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evidencias:limpiar-registros {--dry-run : Solo mostrar qué registros se eliminarían sin eliminarlos} {--confirmar : Confirmar sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar registros de evidencias que no tienen archivos físicos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $confirmar = $this->option('confirmar');

        $this->info('🔍 Buscando registros sin archivos físicos...');

        // Obtener todas las evidencias que tienen archivo_pdf pero el archivo no existe
        $evidenciasSinArchivo = EvidenciaMantenimiento::whereNotNull('archivo_pdf')
            ->get()
            ->filter(function ($evidencia) {
                return !Storage::disk('public')->exists($evidencia->archivo_pdf);
            });

        if ($evidenciasSinArchivo->isEmpty()) {
            $this->info('✅ No se encontraron registros sin archivos físicos.');
            return 0;
        }

        $this->warn("📁 Se encontraron " . $evidenciasSinArchivo->count() . " registros sin archivos físicos:");

        foreach ($evidenciasSinArchivo as $evidencia) {
            $this->line("   - ID: {$evidencia->id} | {$evidencia->titulo} | Archivo: {$evidencia->archivo_pdf}");
        }

        if ($dryRun) {
            $this->info('🔍 Modo dry-run: No se eliminaron registros.');
            return 0;
        }

        if (!$confirmar) {
            if (!$this->confirm('¿Estás seguro de que quieres eliminar estos registros?')) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }
        }

        // Mostrar progreso
        $bar = $this->output->createProgressBar($evidenciasSinArchivo->count());
        $bar->start();

        $eliminados = 0;
        $errores = 0;

        foreach ($evidenciasSinArchivo as $evidencia) {
            try {
                $evidencia->delete();
                $eliminados++;
            } catch (\Exception $e) {
                $this->error("\n❌ Error al eliminar registro ID {$evidencia->id}: " . $e->getMessage());
                $errores++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($eliminados > 0) {
            $this->info("✅ Se eliminaron {$eliminados} registros sin archivos físicos.");
        }

        if ($errores > 0) {
            $this->error("❌ Hubo {$errores} errores al eliminar registros.");
        }

        return 0;
    }
}
