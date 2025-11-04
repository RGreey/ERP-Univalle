<?php

namespace App\Observers;

use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use Carbon\Carbon;

class CupoAsignacionObserver
{
    public function updated(CupoAsignacion $asig)
    {
        // Si el estado pasó a inasistencia
        if ($asig->isDirty('asistencia_estado') && $asig->asistencia_estado === 'inasistencia') {
            $cupoActual = $asig->cupo;
            if (!$cupoActual) return;

            // Día siguiente
            $fechaSiguiente = Carbon::parse($cupoActual->fecha)->addDay();
            $convId = $cupoActual->convocatoria_id;
            $sede = $cupoActual->sede;

            // Buscar o crear el cupo del día siguiente
            $cupoSiguiente = CupoDiario::firstOrCreate(
                [
                    'convocatoria_id' => $convId,
                    'fecha' => $fechaSiguiente->toDateString(),
                    'sede' => $sede
                ],
                [
                    'capacidad' => 0,
                    'asignados' => 0
                ]
            );

            // Incrementar capacidad en +1 (cupos extra por inasistencia)
            $cupoSiguiente->capacidad += 1;
            $cupoSiguiente->save();
        }
    }
}