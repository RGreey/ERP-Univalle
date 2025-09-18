<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\PaqueteEvidencia;
use App\Models\FotoEvidencia;

class LimpiarPaquetesEvidencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evidencias:limpiar-paquetes {--dry-run : Solo mostrar qué se eliminaría sin eliminar} {--confirmar : Confirmar sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar todos los paquetes de evidencias y sus fotos asociadas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $confirmar = $this->option('confirmar');

        $this->info('🔍 Buscando paquetes de evidencias...');

        $paquetes = PaqueteEvidencia::with('fotos')->get();
        $fotos = FotoEvidencia::all();

        if ($paquetes->isEmpty() && $fotos->isEmpty()) {
            $this->info('✅ No se encontraron paquetes de evidencias ni fotos.');
            return 0;
        }

        $this->warn("📁 Se encontraron:");
        $this->line("   - {$paquetes->count()} paquetes de evidencias");
        $this->line("   - {$fotos->count()} fotos de evidencia");

        // Mostrar detalles
        foreach ($paquetes as $paquete) {
            $this->line("   - Paquete ID {$paquete->id}: {$paquete->sede} - {$paquete->mes}/{$paquete->anio} ({$paquete->fotos->count()} fotos)");
        }

        if ($dryRun) {
            $this->info('🔍 Modo dry-run: No se eliminó nada.');
            return 0;
        }

        if (!$confirmar) {
            if (!$this->confirm('¿Estás seguro de que quieres eliminar TODOS los paquetes y fotos?')) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }
        }

        $this->warn('🗑️ Eliminando paquetes y fotos...');

        // Eliminar fotos primero (por las foreign keys)
        $fotosEliminadas = 0;
        $archivosEliminados = 0;

        foreach ($fotos as $foto) {
            try {
                // Eliminar archivo físico
                if (Storage::disk('public')->exists($foto->archivo)) {
                    Storage::disk('public')->delete($foto->archivo);
                    $archivosEliminados++;
                }
                
                $foto->delete();
                $fotosEliminadas++;
            } catch (\Exception $e) {
                $this->error("❌ Error al eliminar foto ID {$foto->id}: " . $e->getMessage());
            }
        }

        // Eliminar paquetes
        $paquetesEliminados = 0;
        foreach ($paquetes as $paquete) {
            try {
                // Eliminar archivo PDF si existe
                if ($paquete->archivo_pdf && Storage::disk('public')->exists($paquete->archivo_pdf)) {
                    Storage::disk('public')->delete($paquete->archivo_pdf);
                    $archivosEliminados++;
                }
                
                $paquete->delete();
                $paquetesEliminados++;
            } catch (\Exception $e) {
                $this->error("❌ Error al eliminar paquete ID {$paquete->id}: " . $e->getMessage());
            }
        }

        $this->info("✅ Operación completada:");
        $this->line("   - {$paquetesEliminados} paquetes eliminados");
        $this->line("   - {$fotosEliminadas} fotos eliminadas");
        $this->line("   - {$archivosEliminados} archivos físicos eliminados");

        return 0;
    }
}
