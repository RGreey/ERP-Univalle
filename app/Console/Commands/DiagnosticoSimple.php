<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;
use App\Models\Postulado;
use App\Models\Documento;
use App\Helpers\ConvocatoriaHelper;
use Carbon\Carbon;

class DiagnosticoSimple extends Command
{
    protected $signature = 'servidor:diagnostico-simple';
    protected $description = 'Diagnóstico simple del servidor para problemas de postulaciones';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO SIMPLE DEL SERVIDOR');
        $this->info('==================================');
        $this->info('');

        // 1. Configuración básica
        $this->info('📋 CONFIGURACIÓN:');
        $this->line('   Zona horaria: ' . config('app.timezone'));
        $this->line('   Hora actual: ' . now());
        $this->line('   Environment: ' . config('app.env'));
        $this->info('');

        // 2. Convocatoria
        $this->info('📅 CONVOCATORIA:');
        $convocatoria = Convocatoria::where('nombre', 'like', '%2025-II%')->first();
        
        if (!$convocatoria) {
            $this->error('   ❌ No se encontró convocatoria 2025-II');
            return 1;
        }
        
        $this->line("   Nombre: {$convocatoria->nombre}");
        $this->line("   Fecha BD: {$convocatoria->fechaCierre}");
        $this->line("   Entrevistas: {$convocatoria->fechaEntrevistas}");
        $this->info('');

        // 3. Helper
        $this->info('🔧 HELPER:');
        $fechaAjustada = ConvocatoriaHelper::ajustarFechaCierre($convocatoria->fechaCierre);
        $estaAbierta = ConvocatoriaHelper::convocatoriaEstaAbierta($convocatoria->fechaCierre);
        $enEntrevistas = ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoria->fechaCierre, $convocatoria->fechaEntrevistas);
        $convocatoriaActiva = ConvocatoriaHelper::obtenerConvocatoriaActiva();
        
        $this->line("   Fecha ajustada: {$fechaAjustada->format('Y-m-d H:i:s')}");
        $this->line("   ¿Está abierta? " . ($estaAbierta ? 'SÍ' : 'NO'));
        $this->line("   ¿En entrevistas? " . ($enEntrevistas ? 'SÍ' : 'NO'));
        $this->line("   ¿Se obtiene como activa? " . ($convocatoriaActiva ? 'SÍ' : 'NO'));
        $this->info('');

        // 4. Postulaciones
        $this->info('📝 POSTULACIONES:');
        $totalPostulaciones = Postulado::count();
        $this->line("   Total: {$totalPostulaciones}");
        
        if ($totalPostulaciones > 0) {
            $ultimaPostulacion = Postulado::orderBy('created_at', 'desc')->first();
            $this->line("   Última: ID {$ultimaPostulacion->id} - {$ultimaPostulacion->created_at}");
            $this->line("   Estado: {$ultimaPostulacion->estado}");
        }
        $this->info('');

        // 5. Documentos
        $this->info('📄 DOCUMENTOS:');
        $totalDocumentos = Documento::count();
        $this->line("   Total: {$totalDocumentos}");
        
        if ($totalDocumentos > 0) {
            $ultimoDocumento = Documento::orderBy('created_at', 'desc')->first();
            $this->line("   Último: ID {$ultimoDocumento->id} - {$ultimoDocumento->created_at}");
            $this->line("   Tipo: {$ultimoDocumento->tipo}");
            $this->line("   Postulación: {$ultimoDocumento->postulado}");
        }
        $this->info('');

        // 6. Estado del sistema
        $this->info('🎯 ESTADO DEL SISTEMA:');
        if ($estaAbierta) {
            $this->info('   🟢 CONVOCATORIA ABIERTA');
            $this->line('   ✅ Se pueden postular estudiantes');
            $this->line('   ✅ Se pueden aprobar postulados');
        } elseif ($enEntrevistas) {
            $this->warn('   🟡 PERÍODO DE ENTREVISTAS');
            $this->line('   ❌ NO se pueden postular estudiantes');
            $this->line('   ❌ NO se pueden aprobar más postulados');
        } else {
            $this->error('   🔴 CONVOCATORIA FINALIZADA');
            $this->line('   ❌ NO se pueden postular estudiantes');
            $this->line('   ❌ NO se pueden aprobar postulados');
        }

        $this->info('');
        $this->info('✅ DIAGNÓSTICO COMPLETADO');
        
        return 0;
    }
}
