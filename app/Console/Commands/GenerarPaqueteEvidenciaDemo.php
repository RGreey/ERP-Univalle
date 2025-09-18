<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\PaqueteEvidencia;
use App\Models\FotoEvidencia;
use App\Models\ActividadMantenimiento;
use App\Http\Controllers\PaqueteEvidenciaController;
use Illuminate\Support\Facades\App;

class GenerarPaqueteEvidenciaDemo extends Command
{
    protected $signature = 'evidencias:demo-pdf {sede=MI} {mes?} {anio?}';
    protected $description = 'Crea un paquete de evidencias de ejemplo con fotos y genera su PDF';

    public function handle()
    {
        $sede = $this->argument('sede');
        $mes = $this->argument('mes') ? (int)$this->argument('mes') : now()->month;
        $anio = $this->argument('anio') ? (int)$this->argument('anio') : now()->year;

        // Asegurar actividades
        $actividades = ActividadMantenimiento::orderBy('orden')->take(3)->get();
        if ($actividades->count() === 0) {
            $this->error('No hay actividades de mantenimiento. Carga las predeterminadas desde el módulo de mantenimiento.');
            return 1;
        }

        $paquete = PaqueteEvidencia::firstOrCreate([
            'sede' => $sede,
            'mes' => $mes,
            'anio' => $anio,
        ]);

        // Preparar imágenes demo desde public/imagenes/header_logo.jpg
        $source = public_path('imagenes/header_logo.jpg');
        if (!file_exists($source)) {
            $this->error('No se encontró public/imagenes/header_logo.jpg para usar como demo.');
            return 1;
        }

        // Subir 2 fotos por las primeras 3 actividades
        foreach ($actividades as $idx => $act) {
            for ($i = 1; $i <= 2; $i++) {
                $dest = 'evidencias/fotos/demo_' . $act->id . '_' . $i . '.jpg';
                if (!Storage::disk('public')->exists($dest)) {
                    Storage::disk('public')->put($dest, file_get_contents($source));
                }
                FotoEvidencia::create([
                    'paquete_id' => $paquete->id,
                    'actividad_id' => $act->id,
                    'archivo' => $dest,
                    'descripcion' => 'Foto demo ' . $i,
                    'orden' => ($idx * 10) + $i,
                ]);
            }
        }

        // Generar PDF usando el controlador
        /** @var PaqueteEvidenciaController $controller */
        $controller = App::make(PaqueteEvidenciaController::class);
        $response = $controller->generarPdf($paquete);

        $this->info('Paquete ID: ' . $paquete->id);
        $this->info('Sede: ' . $paquete->sede . ' Periodo: ' . str_pad($paquete->mes,2,'0',STR_PAD_LEFT) . '/' . $paquete->anio);
        $this->info('PDF: ' . ($paquete->archivo_pdf ?: 'no generado'));
        $this->info('Listo. Puedes descargarlo desde el menú Evidencias de Mantenimiento.');

        return 0;
    }
}


