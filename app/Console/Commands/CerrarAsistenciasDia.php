<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CerrarAsistenciasDia extends Command
{
    protected $signature = 'subsidio:cerrar-dia {fecha? : YYYY-MM-DD (opcional, por defecto ayer)}';
    protected $description = 'Marca como inasistencia las asignaciones pendientes del día especificado (L–V)';

    public function handle(): int
    {
        $tz = config('subsidio.timezone', 'America/Bogota');
        $fecha = $this->argument('fecha') ?: now($tz)->subDay()->toDateString();

        // Solo L–V
        $dow = now($tz)->parse($fecha)->dayOfWeekIso;
        if (!in_array($dow, config('subsidio.dias_habiles_iso', [1,2,3,4,5]), true)) {
            $this->info("Fecha $fecha no es día hábil. Nada por hacer.");
            return self::SUCCESS;
        }

        $affected = DB::table('subsidio_cupo_asignaciones as a')
            ->join('subsidio_cupos_diarios as d', 'd.id', '=', 'a.cupo_diario_id')
            ->whereDate('d.fecha', $fecha)
            ->where(function ($q) {
                $q->whereNull('a.asistencia_estado')
                  ->orWhere('a.asistencia_estado', 'pendiente');
            })
            ->update([
                'a.asistencia_estado' => 'inasistencia', // antes: no_show
                'a.updated_at'        => now(),
            ]);

        $this->info("Asignaciones marcadas como inasistencia para $fecha: $affected");
        return self::SUCCESS;
    }
}