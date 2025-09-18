<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;
use App\Models\Monitoria;
use Carbon\Carbon;

class ProbarPostulaciones extends Command
{
    protected $signature = 'postulaciones:probar';
    protected $description = 'Probar la funcionalidad de postulaciones';

    public function handle()
    {
        $this->info('=== PRUEBA DE FUNCIONALIDAD DE POSTULACIONES ===');
        $this->info('');
        
        // Información de zona horaria
        $this->info('📅 INFORMACIÓN DE ZONA HORARIA:');
        $this->line('Hora actual: ' . now());
        $this->info('');
        
        // Buscar convocatoria activa o en entrevistas
        $this->info('🔍 BUSCANDO CONVOCATORIA:');
        $convocatoria = Convocatoria::where(function($query) {
            $query->where('fechaCierre', '>=', now()) // Convocatoria aún abierta
                  ->orWhere(function($subQuery) {
                      $subQuery->where('fechaCierre', '<', now()) // Ya cerrada
                               ->where('fechaEntrevistas', '>=', now()); // Pero en período de entrevistas
                  });
        })->orderBy('fechaCierre', 'desc')->first();
        
        if ($convocatoria) {
            $this->line('✅ Convocatoria encontrada: ' . $convocatoria->nombre);
            $this->line('📅 Fecha de cierre: ' . $convocatoria->fechaCierre);
            $this->line('📅 Fecha de entrevistas: ' . $convocatoria->fechaEntrevistas);
            
            // Determinar estado
            $fechaActual = now();
            $enPeriodoEntrevistas = $fechaActual->gt($convocatoria->fechaCierre) && 
                                   $fechaActual->lte($convocatoria->fechaEntrevistas);
            
            if ($fechaActual->lt($convocatoria->fechaCierre)) {
                $this->line('🟢 Estado: CONVOCATORIA ABIERTA (se pueden postular)');
            } elseif ($enPeriodoEntrevistas) {
                $this->line('🟡 Estado: PERÍODO DE ENTREVISTAS (NO se pueden postular, solo gestionar)');
            } else {
                $this->line('🔴 Estado: CONVOCATORIA FINALIZADA');
            }
            
            $this->info('');
            
            // Probar monitorías activas
            $this->info('📋 MONITORÍAS ACTIVAS:');
            $monitoriasActivas = Monitoria::where('convocatoria', $convocatoria->id)
                ->where('estado', 'aprobado')
                ->count();
            
            if ($enPeriodoEntrevistas) {
                $this->line('❌ NO se deben mostrar monitorías activas (período de entrevistas)');
            } else {
                $this->line("✅ Se muestran {$monitoriasActivas} monitorías activas");
            }
            
        } else {
            $this->line('❌ No hay convocatoria activa o en período de entrevistas');
        }
        
        $this->info('');
        $this->info('=== FIN DE PRUEBA ===');
        
        return 0;
    }
}
