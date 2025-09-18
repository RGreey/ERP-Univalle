<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitoria;
use App\Models\Convocatoria;

class ProbarModalRequisitos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modal:probar-requisitos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la funcionalidad de la modal con requisitos específicos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 PRUEBA DE MODAL CON REQUISITOS');
        $this->info('==================================');

        // Obtener la convocatoria activa
        $convocatoriaActiva = Convocatoria::where('fechaCierre', '>=', now())->first();

        if (!$convocatoriaActiva) {
            $this->error('No hay convocatoria activa.');
            return;
        }

        $this->info("📅 Convocatoria activa: {$convocatoriaActiva->nombre}");
        $this->info("📅 Fecha de cierre: {$convocatoriaActiva->fechaCierre}");

        // Obtener monitorías aprobadas de la convocatoria activa
        $monitorias = Monitoria::where('convocatoria', $convocatoriaActiva->id)
            ->where('estado', 'aprobado')
            ->with('programadependencia')
            ->get();

        if ($monitorias->isEmpty()) {
            $this->error('No hay monitorías aprobadas en la convocatoria activa.');
            return;
        }

        $this->info("\n📋 Monitorías disponibles para postulación:");
        $this->info(str_repeat('-', 60));

        foreach ($monitorias as $monitoria) {
            $programaDependencia = $monitoria->programadependencia;
            $nombrePD = $programaDependencia ? $programaDependencia->nombrePD : 'No definido';
            
            $this->info("\n🎯 Monitoría: {$monitoria->nombre}");
            $this->line("   ID: {$monitoria->id}");
            $this->line("   Programa/Dependencia: {$nombrePD}");
            $this->line("   Modalidad: {$monitoria->modalidad}");
            $this->line("   Horario: {$monitoria->horario}");
            $this->line("   Vacantes: {$monitoria->vacante}");
            $this->line("   Intensidad: {$monitoria->intensidad} horas/semana");
            
            if ($monitoria->requisitos) {
                $this->info("   ✅ Tiene requisitos específicos:");
                $requisitosCortos = substr($monitoria->requisitos, 0, 80) . "...";
                $this->line("      {$requisitosCortos}");
            } else {
                $this->warn("   ❌ No tiene requisitos específicos");
            }
        }

        $conRequisitos = $monitorias->where('requisitos', '!=', '')->count();
        $sinRequisitos = $monitorias->where('requisitos', '=', '')->count();

        $this->info("\n📊 RESUMEN DE MONITORÍAS DISPONIBLES");
        $this->info(str_repeat('-', 40));
        $this->line("Total monitorías aprobadas: {$monitorias->count()}");
        $this->line("Con requisitos específicos: {$conRequisitos}");
        $this->line("Sin requisitos específicos: {$sinRequisitos}");

        $this->info("\n💡 INSTRUCCIONES PARA PROBAR:");
        $this->line("1. Ve a la página de postulación de monitorías");
        $this->line("2. Haz clic en el botón de postularse de cualquier monitoría");
        $this->line("3. Verifica que aparezcan los requisitos específicos en la modal");
        $this->line("4. Los requisitos deben mostrarse con iconos de check y formato mejorado");
    }
}
