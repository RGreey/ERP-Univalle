<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitoria;

class VerificarMonitoriasRequisitos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitorias:verificar-requisitos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica las monitorías y sus requisitos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📋 VERIFICACIÓN DE MONITORÍAS Y REQUISITOS');
        $this->info('==========================================');

        $monitorias = Monitoria::select('id', 'nombre', 'requisitos', 'estado')->get();

        if ($monitorias->isEmpty()) {
            $this->error('No se encontraron monitorías.');
            return;
        }

        foreach ($monitorias as $monitoria) {
            $this->info("\n👤 Monitoría: {$monitoria->nombre} (ID: {$monitoria->id})");
            $this->info("Estado: {$monitoria->estado}");
            
            if ($monitoria->requisitos) {
                $this->info("✅ Tiene requisitos específicos:");
                $this->line("   " . substr($monitoria->requisitos, 0, 100) . "...");
            } else {
                $this->warn("❌ No tiene requisitos específicos");
            }
        }

        $conRequisitos = $monitorias->where('requisitos', '!=', '')->count();
        $sinRequisitos = $monitorias->where('requisitos', '=', '')->count();

        $this->info("\n📊 RESUMEN");
        $this->info(str_repeat('-', 30));
        $this->line("Total monitorías: {$monitorias->count()}");
        $this->line("Con requisitos: {$conRequisitos}");
        $this->line("Sin requisitos: {$sinRequisitos}");
    }
}
