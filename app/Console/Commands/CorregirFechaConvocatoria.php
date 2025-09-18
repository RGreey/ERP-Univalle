<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;
use Carbon\Carbon;

class CorregirFechaConvocatoria extends Command
{
    protected $signature = 'convocatoria:corregir-fecha';
    protected $description = 'Corregir la fecha de cierre de la convocatoria 2025-II';

    public function handle()
    {
        $this->info('=== CORRECCIÓN DE FECHA DE CONVOCATORIA ===');
        $this->info('');
        
        // Buscar la convocatoria 2025-II
        $convocatoria = Convocatoria::where('nombre', 'like', '%2025-II%')->first();
        
        if (!$convocatoria) {
            $this->error('❌ No se encontró la convocatoria 2025-II');
            return 1;
        }
        
        $this->line('📋 Convocatoria encontrada: ' . $convocatoria->nombre);
        $this->line('📅 Fecha de cierre actual: ' . $convocatoria->fechaCierre);
        $this->line('📅 Fecha de entrevistas: ' . $convocatoria->fechaEntrevistas);
        $this->info('');
        
        // Verificar si necesita corrección
        $fechaActual = now();
        $fechaCierreActual = Carbon::parse($convocatoria->fechaCierre);
        $fechaCierreCorrecta = Carbon::parse('2025-09-01 00:00:00');
        
        if ($fechaCierreActual->eq($fechaCierreCorrecta)) {
            $this->line('✅ La fecha ya está correcta');
        } else {
            $this->warn('⚠️  La fecha necesita corrección');
            $this->line('   Actual: ' . $fechaCierreActual->format('Y-m-d H:i:s'));
            $this->line('   Correcta: ' . $fechaCierreCorrecta->format('Y-m-d H:i:s'));
            $this->info('');
            
            if ($this->confirm('¿Deseas corregir la fecha de cierre?')) {
                $convocatoria->fechaCierre = $fechaCierreCorrecta;
                $convocatoria->save();
                
                $this->info('✅ Fecha de cierre actualizada correctamente');
                $this->line('📅 Nueva fecha: ' . $convocatoria->fechaCierre);
                $this->info('');
                
                // Verificar el estado después de la corrección
                $this->info('🔍 VERIFICACIÓN POST-CORRECCIÓN:');
                $this->line('Hora actual: ' . $fechaActual);
                $this->line('¿Está abierta? ' . ($fechaActual->lt($fechaCierreCorrecta) ? 'SÍ' : 'NO'));
                
                if ($fechaActual->lt($fechaCierreCorrecta)) {
                    $this->info('🟢 La convocatoria ahora está ABIERTA');
                } else {
                    $this->warn('🟡 La convocatoria está CERRADA');
                }
            } else {
                $this->line('❌ Operación cancelada');
            }
        }
        
        $this->info('');
        $this->info('=== FIN DE CORRECCIÓN ===');
        
        return 0;
    }
}
