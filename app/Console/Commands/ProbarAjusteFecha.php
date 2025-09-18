<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;
use Carbon\Carbon;

class ProbarAjusteFecha extends Command
{
    protected $signature = 'convocatoria:probar-ajuste';
    protected $description = 'Probar el ajuste de fecha de cierre';

    public function handle()
    {
        $this->info('=== PRUEBA DE AJUSTE DE FECHA ===');
        $this->info('');
        
        // Buscar la convocatoria
        $convocatoria = Convocatoria::where('nombre', 'like', '%2025-II%')->first();
        
        if (!$convocatoria) {
            $this->error('No se encontró la convocatoria');
            return 1;
        }
        
        $this->line('📋 Convocatoria: ' . $convocatoria->nombre);
        $this->line('📅 Fecha en BD: ' . $convocatoria->fechaCierre);
        $this->line('⏰ Hora actual: ' . now());
        $this->info('');
        
        // Probar el ajuste
        $fechaOriginal = Carbon::parse($convocatoria->fechaCierre);
        $ajustesFechas = config('app.ajustes_fechas_cierre', []);
        
        $this->info('🔧 CONFIGURACIÓN DE AJUSTES:');
        foreach ($ajustesFechas as $ajuste) {
            $this->line("   {$ajuste['fecha_original']} → {$ajuste['fecha_ajustada']}");
            $this->line("   {$ajuste['descripcion']}");
        }
        $this->info('');
        
        // Verificar si aplica algún ajuste
        $fechaAjustada = $fechaOriginal;
        foreach ($ajustesFechas as $ajuste) {
            if ($fechaOriginal->format('Y-m-d H:i:s') === $ajuste['fecha_original']) {
                $fechaAjustada = Carbon::parse($ajuste['fecha_ajustada']);
                $this->info('✅ AJUSTE APLICADO:');
                $this->line("   Original: {$fechaOriginal->format('Y-m-d H:i:s')}");
                $this->line("   Ajustada: {$fechaAjustada->format('Y-m-d H:i:s')}");
                break;
            }
        }
        
        if ($fechaAjustada->eq($fechaOriginal)) {
            $this->line('ℹ️  No se aplicó ningún ajuste');
        }
        
        $this->info('');
        $this->info('🎯 ESTADO DE LA CONVOCATORIA:');
        $fechaActual = now();
        
        if ($fechaActual->lt($fechaAjustada)) {
            $this->info('🟢 CONVOCATORIA ABIERTA (se pueden postular)');
            $tiempoRestante = $fechaAjustada->diffForHumans();
            $this->line("   ⏳ Cierra: {$tiempoRestante}");
        } else {
            $this->warn('🔴 CONVOCATORIA CERRADA');
        }
        
        $this->info('');
        $this->info('=== FIN DE PRUEBA ===');
        
        return 0;
    }
}
