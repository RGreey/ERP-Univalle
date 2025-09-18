<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;

class ActualizarFechaCierre extends Command
{
    protected $signature = 'convocatoria:actualizar-fecha';
    protected $description = 'Actualizar fecha de cierre de convocatoria 2025-II';

    public function handle()
    {
        $convocatoria = Convocatoria::where('nombre', 'like', '%2025-II%')->first();
        
        if (!$convocatoria) {
            $this->error('No se encontró la convocatoria');
            return 1;
        }
        
        $this->line('Fecha actual: ' . $convocatoria->fechaCierre);
        $convocatoria->fechaCierre = '2025-09-01 00:00:00';
        $convocatoria->save();
        $this->line('Fecha nueva: ' . $convocatoria->fechaCierre);
        $this->info('✅ Actualizada correctamente');
        
        return 0;
    }
}
