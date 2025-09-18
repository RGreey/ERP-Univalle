<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaqueteEvidencia;
use App\Models\ActividadMantenimiento;
use App\Models\FotoEvidencia;
use Illuminate\Support\Facades\Storage;

class ProbarPDFEvidencias extends Command
{
    protected $signature = 'probar:pdf-evidencia {paquete_id}';
    protected $description = 'Probar la generación y visualización de PDF de evidencias';

    public function handle()
    {
        $paqueteId = $this->argument('paquete_id');
        
        $paquete = PaqueteEvidencia::find($paqueteId);
        
        if (!$paquete) {
            $this->error("No se encontró el paquete con ID: {$paqueteId}");
            return 1;
        }
        
        $this->info("Paquete encontrado:");
        $this->info("- Sede: {$paquete->sede}");
        $this->info("- Periodo: {$paquete->mes}/{$paquete->anio}");
        $this->info("- Fotos: {$paquete->fotos()->count()}");
        $this->info("- PDF generado: " . ($paquete->archivo_pdf ? 'Sí' : 'No'));
        
        if ($paquete->archivo_pdf) {
            $this->info("- Ruta del PDF: {$paquete->archivo_pdf}");
            $this->info("- Existe en storage: " . (Storage::disk('public')->exists($paquete->archivo_pdf) ? 'Sí' : 'No'));
            
            // URLs de prueba
            $baseUrl = config('app.url');
            $this->info("\nURLs de prueba:");
            $this->info("- Previsualización: {$baseUrl}/evidencias-mantenimiento/paquetes/{$paquete->id}/previsualizar");
            $this->info("- Descarga: {$baseUrl}/evidencias-mantenimiento/paquetes/{$paquete->id}/descargar");
        } else {
            $this->warn("El PDF no ha sido generado aún. Ejecuta primero la generación del PDF.");
        }
        
        return 0;
    }
}
