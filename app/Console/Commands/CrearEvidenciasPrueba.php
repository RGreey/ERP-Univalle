<?php

namespace App\Console\Commands;

use App\Models\EvidenciaMantenimiento;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CrearEvidenciasPrueba extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evidencias:crear-prueba {--cantidad=5 : Cantidad de evidencias a crear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear evidencias de mantenimiento de prueba para testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cantidad = $this->option('cantidad');
        
        $this->info("Creando {$cantidad} evidencias de mantenimiento de prueba...");

        // Obtener usuarios de recursos generales (usando el campo rol)
        $usuarios = User::whereIn('rol', ['Administrativo', 'Profesor'])->get();

        if ($usuarios->isEmpty()) {
            $this->error('No se encontraron usuarios disponibles');
            $this->info('Usuarios disponibles:');
            $roles = User::distinct()->pluck('rol')->toArray();
            foreach ($roles as $rol) {
                $this->info("- {$rol}");
            }
            return 1;
        }

        $sedes = ['MI', 'VC', 'LI', 'Nodo'];
        $meses = range(1, 12);
        $anios = [2024, 2025];
        $estados = ['pendiente', 'aprobado', 'rechazado'];

        $bar = $this->output->createProgressBar($cantidad);
        $bar->start();

        for ($i = 0; $i < $cantidad; $i++) {
            $sede = $sedes[array_rand($sedes)];
            $mes = $meses[array_rand($meses)];
            $anio = $anios[array_rand($anios)];
            $estado = $estados[array_rand($estados)];
            $usuario = $usuarios->random();

            // Verificar si ya existe una evidencia para esta combinación
            $existente = EvidenciaMantenimiento::where('sede', $sede)
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->exists();

            if ($existente) {
                continue; // Saltar si ya existe
            }

            $titulos = [
                'Mantenimiento preventivo laboratorios',
                'Limpieza y desinfección áreas comunes',
                'Revisión sistemas eléctricos',
                'Mantenimiento equipos de cómputo',
                'Adecuación espacios académicos',
                'Mantenimiento sistema de aire acondicionado',
                'Limpieza tanques de agua',
                'Mantenimiento jardines y áreas verdes',
                'Revisión sistema de seguridad',
                'Mantenimiento mobiliario académico'
            ];

            $descripciones = [
                'Se realizó mantenimiento preventivo en los laboratorios de computación, incluyendo limpieza de equipos, revisión de conexiones eléctricas y actualización de software.',
                'Se ejecutó limpieza profunda y desinfección en todas las áreas comunes, incluyendo pasillos, baños y salas de espera.',
                'Se realizó revisión completa del sistema eléctrico, verificando conexiones, interruptores y tomacorrientes.',
                'Se llevó a cabo mantenimiento preventivo en todos los equipos de cómputo, incluyendo limpieza interna y actualización de software.',
                'Se realizaron adecuaciones en los espacios académicos para mejorar la experiencia de aprendizaje.',
                'Se ejecutó mantenimiento del sistema de aire acondicionado, incluyendo limpieza de filtros y revisión de funcionamiento.',
                'Se realizó limpieza y desinfección de tanques de agua potable.',
                'Se ejecutó mantenimiento en jardines y áreas verdes, incluyendo poda, riego y fertilización.',
                'Se realizó revisión completa del sistema de seguridad, verificando cámaras y alarmas.',
                'Se ejecutó mantenimiento del mobiliario académico, incluyendo reparaciones y ajustes.'
            ];

            $titulo = $titulos[array_rand($titulos)];
            $descripcion = $descripciones[array_rand($descripciones)];

            // Crear evidencia
            $evidencia = EvidenciaMantenimiento::create([
                'sede' => $sede,
                'titulo' => $titulo . ' - ' . $this->getMesNombre($mes) . ' ' . $anio,
                'descripcion' => $descripcion,
                'mes' => $mes,
                'anio' => $anio,
                'archivo_pdf' => null, // No creamos archivos reales para las pruebas
                'usuario_id' => $usuario->id,
                'estado' => $estado,
                'fecha_creacion' => now()->subDays(rand(1, 30)),
            ]);

            // Si está aprobada o rechazada, agregar fecha de aprobación
            if ($estado !== 'pendiente') {
                $evidencia->update([
                    'fecha_aprobacion' => $evidencia->fecha_creacion->addDays(rand(1, 7)),
                    'aprobado_por' => User::whereIn('rol', ['CooAdmin', 'AuxAdmin'])->inRandomOrder()->first()->id ?? 1
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('¡Evidencias de prueba creadas exitosamente!');
        
        // Mostrar estadísticas
        $this->newLine();
        $this->info('Estadísticas:');
        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['Pendientes', EvidenciaMantenimiento::where('estado', 'pendiente')->count()],
                ['Aprobadas', EvidenciaMantenimiento::where('estado', 'aprobado')->count()],
                ['Rechazadas', EvidenciaMantenimiento::where('estado', 'rechazado')->count()],
                ['Total', EvidenciaMantenimiento::count()],
            ]
        );

        return 0;
    }

    private function getMesNombre($mes)
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $meses[$mes] ?? 'Desconocido';
    }
}
