<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\PaqueteEvidencia;

class LimpiarPDFEspecificos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evidencias:limpiar-pdf-especificos {sede} {mes} {anio} {--confirmar : Confirmar sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar PDF específicos por sede, mes y año';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sede = $this->argument('sede');
        $mes = (int)$this->argument('mes');
        $anio = (int)$this->argument('anio');
        $confirmar = $this->option('confirmar');

        $this->info("🔍 Buscando PDF para: {$sede} - {$mes}/{$anio}");

        // Buscar paquetes que coincidan
        $paquetes = PaqueteEvidencia::where('sede', $sede)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->whereNotNull('archivo_pdf')
            ->get();

        if ($paquetes->isEmpty()) {
            $this->info('✅ No se encontraron PDF para eliminar.');
            return 0;
        }

        $this->warn("📁 Se encontraron " . $paquetes->count() . " PDF para eliminar:");

        foreach ($paquetes as $paquete) {
            $this->line("   - ID: {$paquete->id} | {$paquete->sede} - {$paquete->mes}/{$paquete->anio} | Archivo: {$paquete->archivo_pdf}");
        }

        if (!$confirmar) {
            if (!$this->confirm("¿Estás seguro de que quieres eliminar estos PDF?")) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }
        }

        $this->warn('🗑️ Eliminando PDF...');

        $eliminados = 0;
        $errores = 0;

        foreach ($paquetes as $paquete) {
            try {
                // Eliminar archivo físico
                if (Storage::disk('public')->exists($paquete->archivo_pdf)) {
                    Storage::disk('public')->delete($paquete->archivo_pdf);
                }
                
                // Limpiar referencia en la base de datos
                $paquete->update(['archivo_pdf' => null]);
                
                $eliminados++;
                $this->line("   ✅ PDF eliminado: {$paquete->archivo_pdf}");
                
            } catch (\Exception $e) {
                $this->error("   ❌ Error al eliminar PDF ID {$paquete->id}: " . $e->getMessage());
                $errores++;
            }
        }

        $this->info('');
        $this->info("✅ Operación completada:");
        $this->line("   - {$eliminados} PDF eliminados");
        
        if ($errores > 0) {
            $this->error("   - {$errores} errores");
        }

        return 0;
    }
}
