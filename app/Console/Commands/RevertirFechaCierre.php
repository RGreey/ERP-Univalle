<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;

class RevertirFechaCierre extends Command
{
    protected $signature = 'convocatoria:revertir-fecha';
    protected $description = 'Revertir fecha de cierre a la original';

    public function handle()
    {
        $convocatoria = Convocatoria::where('nombre', 'like', '%2025-II%')->first();
        
        if (!$convocatoria) {
            $this->error('No se encontró la convocatoria');
            return 1;
        }
        
        $this->line('Fecha actual: ' . $convocatoria->fechaCierre);
        $convocatoria->fechaCierre = '2025-08-31 00:00:00';
        $convocatoria->save();
        $this->line('Fecha revertida: ' . $convocatoria->fechaCierre);
        $this->info('✅ Revertida correctamente');
        
        return 0;
    }
}
