<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;
use App\Helpers\ConvocatoriaHelper;
use Carbon\Carbon;

class ProbarSistemaCompleto extends Command
{
    protected $signature = 'convocatoria:probar-sistema';
    protected $description = 'Probar todo el sistema de convocatorias con ajustes';

    public function handle()
    {
        $this->info('=== PRUEBA COMPLETA DEL SISTEMA ===');
        $this->info('');
        
        // Buscar la convocatoria
        $convocatoria = Convocatoria::where('nombre', 'like', '%2025-II%')->first();
        
        if (!$convocatoria) {
            $this->error('No se encontró la convocatoria');
            return 1;
        }
        
        $this->line('📋 Convocatoria: ' . $convocatoria->nombre);
        $this->line('📅 Fecha en BD: ' . $convocatoria->fechaCierre);
        $this->line('📅 Entrevistas: ' . $convocatoria->fechaEntrevistas);
        $this->line('⏰ Hora actual: ' . now());
        $this->info('');
        
        // Probar el helper
        $this->info('🔧 PRUEBAS DEL HELPER:');
        
        $fechaAjustada = ConvocatoriaHelper::ajustarFechaCierre($convocatoria->fechaCierre);
        $this->line("   Fecha ajustada: {$fechaAjustada->format('Y-m-d H:i:s')}");
        
        $estaAbierta = ConvocatoriaHelper::convocatoriaEstaAbierta($convocatoria->fechaCierre);
        $this->line("   ¿Está abierta? " . ($estaAbierta ? 'SÍ' : 'NO'));
        
        $enEntrevistas = ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoria->fechaCierre, $convocatoria->fechaEntrevistas);
        $this->line("   ¿En entrevistas? " . ($enEntrevistas ? 'SÍ' : 'NO'));
        
        $convocatoriaActiva = ConvocatoriaHelper::obtenerConvocatoriaActiva();
        $this->line("   ¿Se obtiene como activa? " . ($convocatoriaActiva ? 'SÍ' : 'NO'));
        
        $this->info('');
        
        // Estado del sistema
        $this->info('🎯 ESTADO DEL SISTEMA:');
        
        if ($estaAbierta) {
            $this->info('🟢 CONVOCATORIA ABIERTA');
            $this->line('   ✅ Se pueden postular estudiantes');
            $this->line('   ✅ Se pueden aprobar postulados');
            $this->line('   ✅ Se muestran monitorías activas');
        } elseif ($enEntrevistas) {
            $this->warn('🟡 PERÍODO DE ENTREVISTAS');
            $this->line('   ❌ NO se pueden postular estudiantes');
            $this->line('   ❌ NO se pueden aprobar más postulados');
            $this->line('   ✅ Se pueden gestionar entrevistas');
        } else {
            $this->error('🔴 CONVOCATORIA FINALIZADA');
            $this->line('   ❌ NO se pueden postular estudiantes');
            $this->line('   ❌ NO se pueden aprobar postulados');
            $this->line('   ❌ NO se pueden gestionar entrevistas');
        }
        
        $this->info('');
        $this->info('=== FIN DE PRUEBA ===');
        
        return 0;
    }
}
