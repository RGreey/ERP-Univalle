<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitoria;

class ActualizarRequisitosMonitorias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitorias:actualizar-requisitos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los requisitos de algunas monitorías con contenido más detallado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📝 ACTUALIZANDO REQUISITOS DE MONITORÍAS');
        $this->info('========================================');

        $requisitosDetallados = [
            1 => "• Conocimientos avanzados en programación orientada a objetos\n• Experiencia con Java, Python y C++\n• Capacidad para explicar conceptos complejos de manera clara\n• Disponibilidad para sesiones de práctica en laboratorio\n• Conocimiento de estructuras de datos y algoritmos",
            
            3 => "• Dominio de lenguajes de programación web (HTML, CSS, JavaScript)\n• Conocimientos básicos de bases de datos\n• Experiencia con frameworks como React o Vue.js\n• Capacidad para guiar proyectos prácticos\n• Conocimiento de metodologías ágiles",
            
            4 => "• Experiencia en investigación académica\n• Conocimientos de metodología de investigación\n• Capacidad para análisis de datos\n• Experiencia con herramientas estadísticas\n• Habilidad para redacción de informes técnicos\n• Conocimiento de software especializado en el área",
            
            8 => "• Experiencia en gestión administrativa\n• Conocimientos de Excel avanzado\n• Capacidad de organización y planificación\n• Experiencia en atención al cliente\n• Conocimientos básicos de contabilidad\n• Habilidad para manejo de archivos y documentación",
            
            11 => "• Conocimientos en análisis de datos\n• Experiencia con herramientas de visualización\n• Capacidad para interpretar resultados estadísticos\n• Conocimientos de machine learning básico\n• Experiencia con Python para análisis de datos\n• Habilidad para presentar resultados de manera clara",
            
            12 => "• Conocimientos sólidos en contabilidad financiera\n• Experiencia con software contable\n• Capacidad para análisis de estados financieros\n• Conocimientos de normativas contables\n• Experiencia en auditoría básica\n• Habilidad para explicar conceptos contables complejos"
        ];

        foreach ($requisitosDetallados as $id => $requisitos) {
            $monitoria = Monitoria::find($id);
            if ($monitoria) {
                $monitoria->requisitos = $requisitos;
                $monitoria->save();
                
                $this->info("✅ Actualizada monitoría: {$monitoria->nombre} (ID: {$id})");
            } else {
                $this->warn("⚠️  No se encontró monitoría con ID: {$id}");
            }
        }

        $this->info("\n🎉 Actualización completada!");
    }
}
