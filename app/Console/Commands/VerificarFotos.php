<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaqueteEvidencia;

class VerificarFotos extends Command
{
    protected $signature = 'verificar:fotos {paquete_id}';
    protected $description = 'Verificar las fotos de un paquete';

    public function handle()
    {
        $paqueteId = $this->argument('paquete_id');
        
        $paquete = PaqueteEvidencia::with('fotos.actividad')->find($paqueteId);
        
        if (!$paquete) {
            $this->error("No se encontró el paquete con ID: {$paqueteId}");
            return 1;
        }
        
        $this->info("Paquete: {$paquete->sede} - {$paquete->mes}/{$paquete->anio}");
        $this->info("Total de fotos: {$paquete->fotos->count()}");
        
        if ($paquete->fotos->count() > 0) {
            $this->info("Fotos encontradas:");
            foreach ($paquete->fotos as $foto) {
                $this->line("- {$foto->archivo} (Actividad: {$foto->actividad->actividad})");
            }
        } else {
            $this->warn("No hay fotos en este paquete");
        }
        
        return 0;
    }
}
