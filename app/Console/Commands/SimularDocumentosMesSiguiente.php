<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitor;
use App\Models\DocumentoMonitor;
use Carbon\Carbon;

class SimularDocumentosMesSiguiente extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documentos:simular-mes-siguiente {--monitor_id= : ID específico del monitor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simula documentos del mes siguiente para probar el sistema de historial';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitorId = $this->option('monitor_id');
        $mesSiguiente = Carbon::now()->addMonth();
        $mes = $mesSiguiente->month;
        $anio = $mesSiguiente->year;

        $this->info("Simulando documentos para {$mesSiguiente->format('F Y')}...");

        // Obtener monitores
        $query = Monitor::with('user');
        if ($monitorId) {
            $query->where('id', $monitorId);
        }
        $monitores = $query->get();

        if ($monitores->isEmpty()) {
            $this->error('No se encontraron monitores.');
            return;
        }

        $contador = 0;
        foreach ($monitores as $monitor) {
            $user = \App\Models\User::find($monitor->user);
            $nombreMonitor = $user ? $user->name : 'Sin nombre';
            $this->info("Procesando monitor: {$nombreMonitor} (ID: {$monitor->id})");

            // Simular seguimiento del mes siguiente
            $this->simularSeguimiento($monitor, $mes, $anio, $user);
            $contador++;

            // Simular asistencia (solo para algunos monitores)
            if ($contador % 2 == 0) {
                $this->simularAsistencia($monitor, $mes, $anio);
            }

            // Simular evaluación de desempeño (solo para algunos monitores)
            if ($contador % 3 == 0) {
                $this->simularEvaluacionDesempeno($monitor);
            }
        }

        $this->info("¡Simulación completada! Se crearon documentos para {$contador} monitores.");
        $this->info("Mes simulado: {$mesSiguiente->format('F Y')} ({$mes}/{$anio})");
    }

    private function simularSeguimiento($monitor, $mes, $anio, $user = null)
    {
        // Verificar si ya existe
        $existe = DocumentoMonitor::where('monitor_id', $monitor->id)
            ->where('tipo_documento', 'seguimiento')
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->exists();

        if (!$existe) {
            $estado = rand(0, 1) ? 'generado' : 'firmado'; // 50% de probabilidad

            DocumentoMonitor::create([
                'monitor_id' => $monitor->id,
                'tipo_documento' => 'seguimiento',
                'mes' => $mes,
                'anio' => $anio,
                'parametros_generacion' => [
                    'nombre' => $user ? $user->name : 'Monitor',
                    'cedula' => $user ? $user->cedula : '12345678',
                    'plan_academico' => 'Ingeniería de Sistemas',
                    'solicitante' => 'Departamento de Sistemas',
                    'proceso' => 'Desarrollo de Software'
                ],
                'estado' => $estado,
                'fecha_generacion' => Carbon::now()->subDays(rand(1, 15))
            ]);

            $this->line("  ✓ Seguimiento {$estado} creado");
        } else {
            $this->line("  - Seguimiento ya existe");
        }
    }

    private function simularAsistencia($monitor, $mes, $anio)
    {
        // Verificar si ya existe
        $existe = DocumentoMonitor::where('monitor_id', $monitor->id)
            ->where('tipo_documento', 'asistencia')
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->exists();

        if (!$existe) {
            DocumentoMonitor::create([
                'monitor_id' => $monitor->id,
                'tipo_documento' => 'asistencia',
                'mes' => $mes,
                'anio' => $anio,
                'ruta_archivo' => "asistencias/monitor_{$monitor->id}/{$anio}_{$mes}/asistencia_simulada.pdf",
                'estado' => 'generado',
                'fecha_generacion' => Carbon::now()->subDays(rand(1, 10))
            ]);

            $this->line("  ✓ Asistencia generada");
        } else {
            $this->line("  - Asistencia ya existe");
        }
    }

    private function simularEvaluacionDesempeno($monitor)
    {
        // Verificar si ya existe
        $existe = DocumentoMonitor::where('monitor_id', $monitor->id)
            ->where('tipo_documento', 'evaluacion_desempeno')
            ->exists();

        if (!$existe) {
            $estado = rand(0, 1) ? 'generado' : 'firmado'; // 50% de probabilidad

            DocumentoMonitor::create([
                'monitor_id' => $monitor->id,
                'tipo_documento' => 'evaluacion_desempeno',
                'parametros_generacion' => [
                    'periodo_academico' => '2024-2',
                    'fecha_evaluacion' => Carbon::now()->format('Y-m-d')
                ],
                'estado' => $estado,
                'fecha_generacion' => Carbon::now()->subDays(rand(1, 20))
            ]);

            $this->line("  ✓ Evaluación de desempeño {$estado} creada");
        } else {
            $this->line("  - Evaluación de desempeño ya existe");
        }
    }
}
