<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\EvidenciaMantenimiento;

class LimpiarArchivosEvidencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evidencias:limpiar-archivos {--dry-run : Solo mostrar qué archivos se eliminarían sin eliminarlos} {--confirmar : Confirmar sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar archivos PDF huérfanos de evidencias de mantenimiento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $confirmar = $this->option('confirmar');

        $this->info('🔍 Buscando archivos PDF huérfanos...');

        // Obtener todos los archivos PDF en el directorio de evidencias
        $archivosFisicos = Storage::disk('public')->files('evidencias/pdf');
        
        // Obtener todas las rutas de archivos registradas en la base de datos
        $archivosRegistrados = EvidenciaMantenimiento::whereNotNull('archivo_pdf')
            ->pluck('archivo_pdf')
            ->toArray();

        // Encontrar archivos huérfanos (existen físicamente pero no en la BD)
        $archivosHuerfanos = array_diff($archivosFisicos, $archivosRegistrados);

        if (empty($archivosHuerfanos)) {
            $this->info('✅ No se encontraron archivos PDF huérfanos.');
            return 0;
        }

        $this->warn("📁 Se encontraron " . count($archivosHuerfanos) . " archivos PDF huérfanos:");

        foreach ($archivosHuerfanos as $archivo) {
            $tamaño = Storage::disk('public')->size($archivo);
            $tamañoFormateado = $this->formatearTamaño($tamaño);
            $this->line("   - {$archivo} ({$tamañoFormateado})");
        }

        if ($dryRun) {
            $this->info('🔍 Modo dry-run: No se eliminaron archivos.');
            return 0;
        }

        $totalTamaño = 0;
        foreach ($archivosHuerfanos as $archivo) {
            $totalTamaño += Storage::disk('public')->size($archivo);
        }

        $this->warn("💾 Total de espacio a liberar: " . $this->formatearTamaño($totalTamaño));

        if (!$confirmar) {
            if (!$this->confirm('¿Estás seguro de que quieres eliminar estos archivos?')) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }
        }

        // Mostrar progreso
        $bar = $this->output->createProgressBar(count($archivosHuerfanos));
        $bar->start();

        $eliminados = 0;
        $errores = 0;

        foreach ($archivosHuerfanos as $archivo) {
            try {
                Storage::disk('public')->delete($archivo);
                $eliminados++;
            } catch (\Exception $e) {
                $this->error("\n❌ Error al eliminar {$archivo}: " . $e->getMessage());
                $errores++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($eliminados > 0) {
            $this->info("✅ Se eliminaron {$eliminados} archivos PDF huérfanos.");
        }

        if ($errores > 0) {
            $this->error("❌ Hubo {$errores} errores al eliminar archivos.");
        }

        $this->info("💾 Espacio liberado: " . $this->formatearTamaño($totalTamaño));

        return 0;
    }

    /**
     * Formatear tamaño de archivo
     */
    private function formatearTamaño($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
