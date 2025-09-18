<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;
use Carbon\Carbon;

class VerificarConvocatoria extends Command
{
    protected $signature = 'convocatoria:verificar';
    protected $description = 'Verificar el estado de las convocatorias y zona horaria';

    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE CONVOCATORIAS ===');
        $this->info('');
        
        // Información de zona horaria
        $this->info('📅 INFORMACIÓN DE ZONA HORARIA:');
        $this->line('Zona horaria configurada: ' . config('app.timezone'));
        $this->line('Hora actual del servidor: ' . now());
        $this->line('Hora UTC: ' . now()->utc());
        $this->line('Diferencia con UTC: ' . now()->format('P'));
        $this->info('');
        
        // Convocatoria activa
        $this->info('🔍 CONVOCATORIA ACTIVA:');
        $convocatoriaActiva = Convocatoria::where('fechaCierre', '>=', now())->first();
        
        if ($convocatoriaActiva) {
            $this->line('✅ Convocatoria encontrada: ' . $convocatoriaActiva->nombre);
            $this->line('📅 Fecha de cierre: ' . $convocatoriaActiva->fechaCierre);
            $this->line('⏰ Hora actual: ' . now());
            $this->line('🔄 ¿Está activa? ' . ($convocatoriaActiva->fechaCierre >= now() ? 'SÍ' : 'NO'));
            
            // Calcular tiempo restante
            $tiempoRestante = Carbon::parse($convocatoriaActiva->fechaCierre)->diffForHumans();
            $this->line('⏳ Tiempo restante: ' . $tiempoRestante);
        } else {
            $this->line('❌ No hay convocatoria activa');
        }
        
        // Convocatoria en período de entrevistas
        $this->info('');
        $this->info('🎯 CONVOCATORIA EN PERÍODO DE ENTREVISTAS:');
        $convocatoriaEntrevistas = Convocatoria::where('fechaCierre', '<', now())
            ->where('fechaEntrevistas', '>=', now())
            ->first();
        
        if ($convocatoriaEntrevistas) {
            $this->line('✅ Convocatoria en entrevistas: ' . $convocatoriaEntrevistas->nombre);
            $this->line('📅 Fecha de cierre: ' . $convocatoriaEntrevistas->fechaCierre);
            $this->line('📅 Fecha de entrevistas: ' . $convocatoriaEntrevistas->fechaEntrevistas);
            $this->line('⏰ Hora actual: ' . now());
            $this->line('🔄 ¿Está en entrevistas? ' . ($convocatoriaEntrevistas->fechaEntrevistas >= now() ? 'SÍ' : 'NO'));
            
            // Calcular tiempo restante
            $tiempoRestante = Carbon::parse($convocatoriaEntrevistas->fechaEntrevistas)->diffForHumans();
            $this->line('⏳ Tiempo restante para entrevistas: ' . $tiempoRestante);
        } else {
            $this->line('❌ No hay convocatoria en período de entrevistas');
        }
        
        $this->info('');
        
        // Todas las convocatorias
        $this->info('📋 TODAS LAS CONVOCATORIAS:');
        $convocatorias = Convocatoria::orderBy('fechaCierre', 'desc')->get();
        
        foreach ($convocatorias as $conv) {
            $estado = $conv->fechaCierre >= now() ? '🟢 ACTIVA' : '🔴 CERRADA';
            $this->line("{$estado} - {$conv->nombre}");
            $this->line("   📅 Cierre: {$conv->fechaCierre}");
            if ($conv->fechaEntrevistas) {
                $this->line("   📅 Entrevistas: {$conv->fechaEntrevistas}");
            }
            $this->line("");
        }
        
        $this->info('');
        $this->info('=== FIN DE VERIFICACIÓN ===');
        
        return 0;
    }
}
