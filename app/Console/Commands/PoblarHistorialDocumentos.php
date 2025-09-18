<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitor;
use App\Models\Seguimiento;
use App\Models\AsistenciaMonitoria;
use App\Models\DesempenoMonitor;
use App\Models\DocumentoMonitor;

class PoblarHistorialDocumentos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documentos:poblar-historial';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pobla el historial de documentos con los documentos existentes en el sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando poblamiento del historial de documentos...');

        // Poblar seguimientos
        $this->poblarSeguimientos();

        // Poblar asistencias
        $this->poblarAsistencias();

        // Poblar evaluaciones de desempeño
        $this->poblarEvaluacionesDesempeno();

        $this->info('¡Poblamiento del historial completado!');
    }

    private function poblarSeguimientos()
    {
        $this->info('Poblando seguimientos...');
        
        $seguimientos = Seguimiento::select('monitor', 'fecha_monitoria')
            ->distinct()
            ->get()
            ->groupBy('monitor');

        $contador = 0;
        foreach ($seguimientos as $monitorId => $seguimientosMonitor) {
            foreach ($seguimientosMonitor as $seguimiento) {
                $fecha = \Carbon\Carbon::parse($seguimiento->fecha_monitoria);
                $mes = $fecha->month;
                $anio = $fecha->year;

                // Verificar si ya existe
                $existe = DocumentoMonitor::where('monitor_id', $monitorId)
                    ->where('tipo_documento', 'seguimiento')
                    ->where('mes', $mes)
                    ->where('anio', $anio)
                    ->exists();

                if (!$existe) {
                    // Verificar si está firmado
                    $seguimientosFirmados = Seguimiento::where('monitor', $monitorId)
                        ->whereMonth('fecha_monitoria', $mes)
                        ->whereYear('fecha_monitoria', $anio)
                        ->whereNotNull('firma_digital')
                        ->exists();

                    DocumentoMonitor::create([
                        'monitor_id' => $monitorId,
                        'tipo_documento' => 'seguimiento',
                        'mes' => $mes,
                        'anio' => $anio,
                        'estado' => $seguimientosFirmados ? 'firmado' : 'generado',
                        'fecha_generacion' => $fecha
                    ]);

                    $contador++;
                }
            }
        }

        $this->info("Se agregaron {$contador} seguimientos al historial.");
    }

    private function poblarAsistencias()
    {
        $this->info('Poblando asistencias...');
        
        $asistencias = AsistenciaMonitoria::all();
        $contador = 0;

        foreach ($asistencias as $asistencia) {
            // Verificar si ya existe
            $existe = DocumentoMonitor::where('monitor_id', $asistencia->monitor_id)
                ->where('tipo_documento', 'asistencia')
                ->where('mes', $asistencia->mes)
                ->where('anio', $asistencia->anio)
                ->exists();

            if (!$existe) {
                DocumentoMonitor::create([
                    'monitor_id' => $asistencia->monitor_id,
                    'tipo_documento' => 'asistencia',
                    'mes' => $asistencia->mes,
                    'anio' => $asistencia->anio,
                    'ruta_archivo' => $asistencia->asistencia_path,
                    'estado' => 'generado',
                    'fecha_generacion' => $asistencia->created_at
                ]);

                $contador++;
            }
        }

        $this->info("Se agregaron {$contador} asistencias al historial.");
    }

    private function poblarEvaluacionesDesempeno()
    {
        $this->info('Poblando evaluaciones de desempeño...');
        
        $evaluaciones = DesempenoMonitor::all();
        $contador = 0;

        foreach ($evaluaciones as $evaluacion) {
            // Verificar si ya existe
            $existe = DocumentoMonitor::where('monitor_id', $evaluacion->monitor_id)
                ->where('tipo_documento', 'evaluacion_desempeno')
                ->exists();

            if (!$existe) {
                $estado = ($evaluacion->firma_evaluador && $evaluacion->firma_evaluado) ? 'firmado' : 'generado';

                DocumentoMonitor::create([
                    'monitor_id' => $evaluacion->monitor_id,
                    'tipo_documento' => 'evaluacion_desempeno',
                    'parametros_generacion' => [
                        'periodo_academico' => $evaluacion->periodo_academico,
                        'fecha_evaluacion' => $evaluacion->fecha_evaluacion
                    ],
                    'estado' => $estado,
                    'fecha_generacion' => $evaluacion->created_at
                ]);

                $contador++;
            }
        }

        $this->info("Se agregaron {$contador} evaluaciones de desempeño al historial.");
    }
}
