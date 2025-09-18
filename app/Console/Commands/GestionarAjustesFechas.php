<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;
use App\Helpers\ConvocatoriaHelper;
use Carbon\Carbon;

class GestionarAjustesFechas extends Command
{
    protected $signature = 'convocatoria:gestionar-ajustes {--convocatoria-id= : ID de convocatoria específica}';
    protected $description = 'Gestionar ajustes de fechas para convocatorias';

    public function handle()
    {
        $this->info('🎯 GESTIÓN DE AJUSTES DE FECHAS');
        $this->info('===============================');
        $this->info('');

        // Mostrar convocatorias existentes
        $this->mostrarConvocatorias();

        // Verificar ajustes automáticos
        $this->verificarAjustesAutomaticos();

        // Opciones de gestión
        $this->mostrarOpciones();

        return 0;
    }

    private function mostrarConvocatorias()
    {
        $this->info('📋 CONVOCATORIAS EXISTENTES:');
        
        $convocatorias = Convocatoria::orderBy('created_at', 'desc')->get();
        
        if ($convocatorias->isEmpty()) {
            $this->warn('   No hay convocatorias registradas');
            return;
        }

        foreach ($convocatorias as $conv) {
            $infoAjuste = ConvocatoriaHelper::obtenerInfoAjuste($conv->fechaCierre);
            $estado = ConvocatoriaHelper::convocatoriaEstaAbierta($conv->fechaCierre) ? 'ABIERTA' : 'CERRADA';
            
            $this->line("   📅 ID {$conv->id}: {$conv->nombre}");
            $this->line("      Fecha BD: {$conv->fechaCierre}");
            $this->line("      Fecha ajustada: {$infoAjuste['fecha_ajustada']}");
            $this->line("      Estado: {$estado}");
            
            if ($infoAjuste['se_ajusto']) {
                $this->line("      ⚙️ Ajuste: {$infoAjuste['descripcion']}");
            } else {
                $this->line("      ✅ Sin ajuste necesario");
            }
            $this->line('');
        }
    }

    private function verificarAjustesAutomaticos()
    {
        $this->info('🔍 VERIFICACIÓN DE AJUSTES AUTOMÁTICOS:');
        
        $convocatorias = Convocatoria::all();
        $necesitanAjuste = 0;
        
        foreach ($convocatorias as $conv) {
            if (ConvocatoriaHelper::necesitaAjusteAutomatico($conv->fechaCierre)) {
                $necesitanAjuste++;
                $this->line("   ⚠️ Convocatoria ID {$conv->id} ({$conv->nombre})");
                $this->line("      Fecha: {$conv->fechaCierre}");
                $this->line("      Necesita ajuste automático");
            }
        }
        
        if ($necesitanAjuste === 0) {
            $this->line("   ✅ Todas las convocatorias están correctamente configuradas");
        } else {
            $this->line("   📝 {$necesitanAjuste} convocatoria(s) necesitan ajuste automático");
        }
        
        $this->info('');
    }

    private function mostrarOpciones()
    {
        $this->info('🛠️ OPCIONES DE GESTIÓN:');
        $this->line('');
        $this->line('1. Para futuras convocatorias, el sistema ahora:');
        $this->line('   ✅ Detecta automáticamente fechas que terminan en 31');
        $this->line('   ✅ Las interpreta como medianoche del día siguiente');
        $this->line('   ✅ No requiere configuración manual');
        $this->line('');
        $this->line('2. Para casos especiales, puedes agregar en config/app.php:');
        $this->line('   ```php');
        $this->line('   \'ajustes_fechas_cierre\' => [');
        $this->line('       [');
        $this->line('           \'fecha_original\' => \'2025-08-31 00:00:00\',');
        $this->line('           \'fecha_ajustada\' => \'2025-09-01 00:00:00\',');
        $this->line('           \'descripcion\' => \'Convocatoria específica\'');
        $this->line('       ],');
        $this->line('   ],');
        $this->line('   ```');
        $this->line('');
        $this->line('3. Comandos útiles:');
        $this->line('   php artisan convocatoria:probar-sistema');
        $this->line('   php artisan convocatoria:gestionar-ajustes');
        $this->line('');
    }
}
