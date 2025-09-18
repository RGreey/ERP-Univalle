<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentoMonitor;
use App\Models\Monitor;
use Carbon\Carbon;

class VerHistorialDocumentos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documentos:ver-historial {--monitor_id= : ID específico del monitor} {--tipo= : Tipo de documento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra el historial de documentos de los monitores';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitorId = $this->option('monitor_id');
        $tipo = $this->option('tipo');

        $this->info('📋 HISTORIAL DE DOCUMENTOS');
        $this->info('========================');

        // Construir consulta
        $query = DocumentoMonitor::with('monitor.user');
        
        if ($monitorId) {
            $query->where('monitor_id', $monitorId);
        }
        
        if ($tipo) {
            $query->where('tipo_documento', $tipo);
        }

        $documentos = $query->orderBy('fecha_generacion', 'desc')->get();

        if ($documentos->isEmpty()) {
            $this->warn('No se encontraron documentos en el historial.');
            return;
        }

        // Agrupar por monitor
        $documentosPorMonitor = $documentos->groupBy('monitor_id');

        foreach ($documentosPorMonitor as $monitorId => $docs) {
            $monitor = $docs->first()->monitor;
            $user = \App\Models\User::find($monitor->user);
            $nombreMonitor = $user ? $user->name : 'Sin nombre';

            $this->info("\n👤 Monitor: {$nombreMonitor} (ID: {$monitorId})");
            $this->info(str_repeat('-', 50));

            foreach ($docs as $doc) {
                $fecha = Carbon::parse($doc->fecha_generacion)->format('d/m/Y H:i');
                $estado = $this->getEstadoColor($doc->estado);
                $tipo = $this->getTipoDocumento($doc->tipo_documento);
                
                $periodo = $this->getPeriodo($doc);
                
                $this->line("  📄 {$tipo} - {$periodo}");
                $this->line("     📅 {$fecha} | {$estado}");
                
                if ($doc->ruta_archivo) {
                    $this->line("     📁 Archivo: {$doc->ruta_archivo}");
                }
            }
        }

        // Estadísticas
        $this->info("\n📊 ESTADÍSTICAS");
        $this->info(str_repeat('-', 30));
        
        $totalDocumentos = $documentos->count();
        $porTipo = $documentos->groupBy('tipo_documento')->map->count();
        $porEstado = $documentos->groupBy('estado')->map->count();

        $this->line("Total documentos: {$totalDocumentos}");
        
        foreach ($porTipo as $tipo => $cantidad) {
            $tipoNombre = $this->getTipoDocumento($tipo);
            $this->line("  {$tipoNombre}: {$cantidad}");
        }

        $this->line("\nPor estado:");
        foreach ($porEstado as $estado => $cantidad) {
            $estadoColor = $this->getEstadoColor($estado);
            $this->line("  {$estadoColor}: {$cantidad}");
        }
    }

    private function getEstadoColor($estado)
    {
        $colores = [
            'generado' => '<fg=blue>Generado</>',
            'firmado' => '<fg=green>Firmado</>',
            'pendiente' => '<fg=yellow>Pendiente</>'
        ];
        
        return $colores[$estado] ?? $estado;
    }

    private function getTipoDocumento($tipo)
    {
        $tipos = [
            'seguimiento' => '📋 Seguimiento Mensual',
            'asistencia' => '📊 Asistencia Mensual',
            'evaluacion_desempeno' => '📈 Evaluación de Desempeño'
        ];
        
        return $tipos[$tipo] ?? $tipo;
    }

    private function getPeriodo($doc)
    {
        if ($doc->tipo_documento === 'evaluacion_desempeno') {
            $periodo = $doc->parametros_generacion['periodo_academico'] ?? 'N/A';
            return "Período: {$periodo}";
        }
        
        if ($doc->mes && $doc->anio) {
            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            
            $mes = $meses[$doc->mes] ?? $doc->mes;
            return "{$mes} {$doc->anio}";
        }
        
        return 'N/A';
    }
}
