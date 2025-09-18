<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Convocatoria;
use App\Models\Postulado;
use App\Models\Documento;
use App\Helpers\ConvocatoriaHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DiagnosticoServidor extends Command
{
    protected $signature = 'servidor:diagnostico {--postulacion-id= : ID de postulación específica para diagnosticar}';
    protected $description = 'Diagnóstico completo del servidor para problemas de postulaciones';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO COMPLETO DEL SERVIDOR');
        $this->info('=====================================');
        $this->info('');

        // 1. Verificar configuración del servidor
        $this->verificarConfiguracion();
        
        // 2. Verificar convocatorias
        $this->verificarConvocatorias();
        
        // 3. Verificar helper
        $this->verificarHelper();
        
        // 4. Verificar postulaciones recientes
        $this->verificarPostulaciones();
        
        // 5. Verificar documentos
        $this->verificarDocumentos();
        
        // 6. Verificar permisos de archivos
        $this->verificarPermisos();
        
        // 7. Verificar base de datos
        $this->verificarBaseDatos();

        $this->info('');
        $this->info('✅ DIAGNÓSTICO COMPLETADO');
        
        return 0;
    }

    private function verificarConfiguracion()
    {
        $this->info('📋 1. CONFIGURACIÓN DEL SERVIDOR:');
        
        $this->line('   Zona horaria: ' . config('app.timezone'));
        $this->line('   Hora actual: ' . now());
        $this->line('   Debug mode: ' . (config('app.debug') ? 'ON' : 'OFF'));
        $this->line('   Environment: ' . config('app.env'));
        $this->line('   Storage driver: ' . config('filesystems.default'));
        $this->line('   Database connection: ' . config('database.default'));
        
        $this->info('');
    }

    private function verificarConvocatorias()
    {
        $this->info('📅 2. CONVOCATORIAS EN BD:');
        
        $convocatorias = Convocatoria::orderBy('created_at', 'desc')->get();
        
        if ($convocatorias->isEmpty()) {
            $this->error('   ❌ No hay convocatorias en la base de datos');
        } else {
            foreach ($convocatorias as $conv) {
                $this->line("   📋 ID: {$conv->id} | {$conv->nombre}");
                $this->line("      Cierre: {$conv->fechaCierre} | Entrevistas: {$conv->fechaEntrevistas}");
                
                // Verificar con helper
                $fechaAjustada = ConvocatoriaHelper::ajustarFechaCierre($conv->fechaCierre);
                $estaAbierta = ConvocatoriaHelper::convocatoriaEstaAbierta($conv->fechaCierre);
                $enEntrevistas = ConvocatoriaHelper::convocatoriaEnEntrevistas($conv->fechaCierre, $conv->fechaEntrevistas);
                
                $this->line("      Ajustada: {$fechaAjustada->format('Y-m-d H:i:s')}");
                $this->line("      Estado: " . ($estaAbierta ? 'ABIERTA' : ($enEntrevistas ? 'ENTREVISTAS' : 'FINALIZADA')));
                $this->line('');
            }
        }
        
        $this->info('');
    }

    private function verificarHelper()
    {
        $this->info('🔧 3. VERIFICACIÓN DEL HELPER:');
        
        $convocatoria = Convocatoria::where('nombre', 'like', '%2025-II%')->first();
        
        if (!$convocatoria) {
            $this->error('   ❌ No se encontró convocatoria 2025-II para probar helper');
            return;
        }
        
        $this->line("   Convocatoria: {$convocatoria->nombre}");
        $this->line("   Fecha BD: {$convocatoria->fechaCierre}");
        
        $fechaAjustada = ConvocatoriaHelper::ajustarFechaCierre($convocatoria->fechaCierre);
        $estaAbierta = ConvocatoriaHelper::convocatoriaEstaAbierta($convocatoria->fechaCierre);
        $enEntrevistas = ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoria->fechaCierre, $convocatoria->fechaEntrevistas);
        $convocatoriaActiva = ConvocatoriaHelper::obtenerConvocatoriaActiva();
        
        $this->line("   Fecha ajustada: {$fechaAjustada->format('Y-m-d H:i:s')}");
        $this->line("   ¿Está abierta? " . ($estaAbierta ? 'SÍ' : 'NO'));
        $this->line("   ¿En entrevistas? " . ($enEntrevistas ? 'SÍ' : 'NO'));
        $this->line("   ¿Se obtiene como activa? " . ($convocatoriaActiva ? 'SÍ' : 'NO'));
        
        $this->info('');
    }

    private function verificarPostulaciones()
    {
        $this->info('📝 4. POSTULACIONES RECIENTES:');
        
        $postulaciones = Postulado::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        if ($postulaciones->isEmpty()) {
            $this->warn('   ⚠️ No hay postulaciones recientes');
        } else {
            foreach ($postulaciones as $post) {
                $this->line("   📋 ID: {$post->id} | Usuario ID: {$post->user} | Estado: {$post->estado}");
                $this->line("      Creada: {$post->created_at} | Convocatoria: {$post->convocatoria}");
                $this->line("      Monitoría ID: {$post->monitoria}");
                
                // Verificar documentos
                $documentos = Documento::where('postulado', $post->id)->count();
                $this->line("      Documentos: {$documentos}");
                $this->line('');
            }
        }
        
        $this->info('');
    }

    private function verificarDocumentos()
    {
        $this->info('📄 5. DOCUMENTOS DE POSTULACIONES:');
        
        $documentos = Documento::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        if ($documentos->isEmpty()) {
            $this->warn('   ⚠️ No hay documentos recientes');
        } else {
            foreach ($documentos as $doc) {
                $this->line("   📄 ID: {$doc->id} | Tipo: {$doc->tipo} | Postulación: {$doc->postulado}");
                $this->line("      Archivo: {$doc->archivo}");
                $this->line("      Creado: {$doc->created_at}");
                
                // Verificar si el archivo existe físicamente
                $existe = Storage::disk('public')->exists($doc->archivo);
                $this->line("      ¿Existe archivo? " . ($existe ? 'SÍ' : 'NO'));
                $this->line('');
            }
        }
        
        $this->info('');
    }

    private function verificarPermisos()
    {
        $this->info('🔐 6. PERMISOS DE ARCHIVOS:');
        
        $storagePath = storage_path('app/public');
        $this->line("   Ruta storage: {$storagePath}");
        
        if (is_dir($storagePath)) {
            $this->line("   ¿Directorio existe? SÍ");
            $this->line("   Permisos: " . substr(sprintf('%o', fileperms($storagePath)), -4));
            $this->line("   ¿Es escribible? " . (is_writable($storagePath) ? 'SÍ' : 'NO'));
        } else {
            $this->error("   ❌ Directorio storage no existe");
        }
        
        $this->info('');
    }

    private function verificarBaseDatos()
    {
        $this->info('🗄️ 7. ESTADO DE LA BASE DE DATOS:');
        
        try {
            // Verificar conexión
            DB::connection()->getPdo();
            $this->line("   ✅ Conexión a BD: OK");
            
            // Verificar tablas
            $tablas = ['convocatorias', 'postulados', 'documentos_postulados', 'monitorias'];
            foreach ($tablas as $tabla) {
                $existe = DB::getSchemaBuilder()->hasTable($tabla);
                $this->line("   Tabla {$tabla}: " . ($existe ? 'EXISTE' : 'NO EXISTE'));
            }
            
            // Contar registros
            $this->line("   Total convocatorias: " . Convocatoria::count());
            $this->line("   Total postulaciones: " . Postulado::count());
            $this->line("   Total documentos: " . Documento::count());
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error de BD: " . $e->getMessage());
        }
        
        $this->info('');
    }
}
