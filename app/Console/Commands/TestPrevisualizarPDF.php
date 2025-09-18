<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaqueteEvidencia;
use App\Http\Controllers\PaqueteEvidenciaController;

class TestPrevisualizarPDF extends Command
{
    protected $signature = 'test:previsualizar {paquete_id}';
    protected $description = 'Probar el método de previsualización de PDF';

    public function handle()
    {
        $paqueteId = $this->argument('paquete_id');
        
        try {
            $paquete = PaqueteEvidencia::find($paqueteId);
            
            if (!$paquete) {
                $this->error("No se encontró el paquete con ID: {$paqueteId}");
                return 1;
            }
            
            $this->info("Paquete encontrado: {$paquete->sede} - {$paquete->mes}/{$paquete->anio}");
            $this->info("Fotos: {$paquete->fotos()->count()}");
            $this->info("PDF existente: " . ($paquete->archivo_pdf ? 'Sí' : 'No'));
            
            // Probar el método de previsualización
            $controller = new PaqueteEvidenciaController();
            $response = $controller->previsualizar($paquete);
            
            $this->info("Respuesta del controlador: " . get_class($response));
            $this->info("Status: " . $response->getStatusCode());
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Archivo: " . $e->getFile());
            $this->error("Línea: " . $e->getLine());
            return 1;
        }
    }
}
