<?php

namespace App\Services;

use App\Models\ConvocatoriaSubsidio;
use App\Models\CupoDiario;
use App\Models\CupoAsignacion;
use App\Models\PostulacionSubsidio;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AsignadorCuposService
{
    // Máximo de cupos semanales por prioridad_final (1 = mayor prioridad)
    private array $maxPorSemana = [1=>5,2=>5,3=>4,4=>3,5=>3,6=>2,7=>2,8=>1,9=>1];

    // Crea/actualiza CupoDiario de TODO el período SOLO L–V por sede, y sincroniza capacidad
    public function generarPeriodo(ConvocatoriaSubsidio $conv): int
    {
        if (!$conv->fecha_inicio_beneficio || !$conv->fecha_fin_beneficio) return 0;

        $inicio = Carbon::parse($conv->fecha_inicio_beneficio)->startOfDay();
        $fin    = Carbon::parse($conv->fecha_fin_beneficio)->endOfDay();

        $cap = [
            'caicedonia' => (int)($conv->cupos_caicedonia ?? 0),
            'sevilla'    => (int)($conv->cupos_sevilla ?? 0),
        ];

        $total = 0;
        DB::transaction(function () use ($conv, $inicio, $fin, $cap, &$total) {
            for ($f = $inicio->copy(); $f->lte($fin); $f->addDay()) {
                if (!in_array($f->dayOfWeekIso, [1,2,3,4,5], true)) continue; // solo L–V

                foreach (['caicedonia','sevilla'] as $sede) {
                    $cupo = CupoDiario::firstOrCreate(
                        ['convocatoria_id'=>$conv->id,'fecha'=>$f->toDateString(),'sede'=>$sede],
                        ['capacidad'=>$cap[$sede], 'asignados'=>0]
                    );
                    if ($cupo->capacidad !== $cap[$sede]) {
                        $cupo->capacidad = $cap[$sede];
                        $cupo->save();
                    }
                    $total++;
                }
            }
        });
        return $total;
    }

    // Asigna automáticamente la SEMANA seleccionada (L–V, ambas sedes), respetando límite por prioridad y excluyendo sábados/domingos
    public function autoAsignarSemana(ConvocatoriaSubsidio $conv, Carbon $lunes): int
    {
        $this->generarPeriodo($conv); // asegura cupos con capacidad

        $domingo = $lunes->copy()->endOfWeek(Carbon::SUNDAY);

        // Conteo de asignaciones de la semana (SOLO L–V)
        $semanaCnt = CupoAsignacion::select('user_id', DB::raw('COUNT(*) as c'))
            ->whereHas('cupo', function($q) use ($conv, $lunes, $domingo) {
                $q->where('convocatoria_id', $conv->id)
                  ->whereBetween('fecha', [$lunes->toDateString(), $domingo->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) <= 4'); // L–V
            })
            ->groupBy('user_id')->pluck('c','user_id');

        // Candidatos ordenados (ambas sedes por defecto; las preferencias reales se pueden inyectar aquí si las tienes)
        $candidatos = PostulacionSubsidio::with('user')
            ->where('convocatoria_id', $conv->id)
            ->whereIn('estado', ['evaluada','beneficiario'])
            ->orderByRaw('prioridad_final IS NULL')
            ->orderBy('prioridad_final')
            ->orderBy('created_at')
            ->get();

        $maxPorSemana = $this->maxPorSemana;
        $totalCreadas = 0;

        DB::transaction(function () use ($conv, $lunes, $candidatos, $maxPorSemana, &$semanaCnt, &$totalCreadas) {
            foreach ([1,2,3,4,5] as $dISO) { // L–V
                $fecha = $lunes->copy()->addDays($dISO-1);

                // Para evitar doble reserva en el día (cualquier sede)
                $usersAsignadosEseDia = CupoAsignacion::whereHas('cupo', function($q2) use ($conv, $fecha) {
                        $q2->where('convocatoria_id', $conv->id)->whereDate('fecha', $fecha->toDateString());
                    })->pluck('user_id')->unique()->all();

                foreach (['caicedonia','sevilla'] as $sede) {
                    $cap = ($sede==='caicedonia') ? (int)($conv->cupos_caicedonia ?? 0) : (int)($conv->cupos_sevilla ?? 0);
                    if ($cap <= 0) continue;

                    $cupo = CupoDiario::firstOrCreate(
                        ['convocatoria_id'=>$conv->id,'fecha'=>$fecha->toDateString(),'sede'=>$sede],
                        ['capacidad'=>$cap,'asignados'=>0]
                    );
                    if ($cupo->capacidad !== $cap) {
                        $cupo->capacidad = $cap;
                        $cupo->save();
                    }
                    if ($cupo->asignados >= $cupo->capacidad) continue;

                    $eligibles = $candidatos->map(function($p) use ($semanaCnt) {
                            $p->prioridad_orden  = (int)($p->prioridad_final ?? 999);
                            $p->semana_asignados = (int)($semanaCnt[$p->user_id] ?? 0);
                            return $p;
                        })
                        ->sortBy([
                            ['prioridad_orden','asc'],
                            ['semana_asignados','asc'],
                            ['created_at','asc'],
                        ])
                        ->values();

                    foreach ($eligibles as $p) {
                        if ($cupo->asignados >= $cupo->capacidad) break;

                        // Límite semanal por prioridad (ya sin contar sábados/domingos)
                        $prio = (int)($p->prioridad_final ?? 9);
                        $max  = $maxPorSemana[$prio] ?? 1;
                        $ya   = (int)($semanaCnt[$p->user_id] ?? 0);
                        if ($ya >= $max) continue;

                        // Evita duplicado ese día (en cualquier sede)
                        if (in_array($p->user_id, $usersAsignadosEseDia, true)) continue;

                        CupoAsignacion::create([
                            'cupo_diario_id' => $cupo->id,
                            'postulacion_id' => $p->id,
                            'user_id'        => $p->user_id,
                            'estado'         => 'asignado',
                            'asignado_en'    => now(),
                            'qr_token'       => bin2hex(random_bytes(16)),
                        ]);
                        $cupo->increment('asignados');
                        $semanaCnt[$p->user_id] = $ya + 1;
                        $usersAsignadosEseDia[] = $p->user_id;
                        $totalCreadas++;
                    }
                }
            }
        });

        return $totalCreadas;
    }

    // Replica la semana seleccionada (L–V) a TODO el período SOBRESCRIBIENDO semanas futuras
    public function aplicarSemanaATodoPeriodo(ConvocatoriaSubsidio $conv, Carbon $lunes): int
    {
        $this->generarPeriodo($conv); // asegura cupos con capacidad

        $domingo = $lunes->copy()->endOfWeek(Carbon::SUNDAY);

        // Toma como plantilla las asignaciones existentes L–V de ESTA semana
        $plantilla = CupoAsignacion::with(['cupo'])
            ->whereHas('cupo', function($q) use ($conv, $lunes, $domingo) {
                $q->where('convocatoria_id', $conv->id)
                  ->whereBetween('fecha', [$lunes->toDateString(), $domingo->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) <= 4'); // L–V
            })->get();

        if ($plantilla->isEmpty()) return 0;

        // Agrupar por (díaISO|sede) para replicar
        $tpl = $plantilla->groupBy(function($a){
            return $a->cupo->fecha->dayOfWeekIso.'|'.$a->cupo->sede;
        });

        $inicio = Carbon::parse($conv->fecha_inicio_beneficio)->startOfWeek(Carbon::MONDAY);
        $fin    = Carbon::parse($conv->fecha_fin_beneficio)->endOfWeek(Carbon::SUNDAY);

        $creadas = 0;

        DB::transaction(function () use ($conv, $inicio, $fin, $lunes, $tpl, &$creadas) {

            // Recorre todas las semanas del período
            for ($sem = $inicio->copy(); $sem->lte($fin); $sem->addWeek()) {
                // No re-aplicar en la semana plantilla
                if ($sem->isSameDay($lunes)) continue;

                // 1) Limpiar la semana destino L–V (ambas sedes): BORRA y pone asignados=0
                foreach ([1,2,3,4,5] as $dISO) {
                    $fechaTarget = $sem->copy()->addDays($dISO-1)->toDateString();

                    // Busca todos los cupos del día (ambas sedes) y elimina sus asignaciones
                    $ids = CupoDiario::where('convocatoria_id',$conv->id)
                        ->whereDate('fecha',$fechaTarget)
                        ->pluck('id');

                    if ($ids->isNotEmpty()) {
                        CupoAsignacion::whereIn('cupo_diario_id', $ids)->delete();
                        CupoDiario::whereIn('id',$ids)->update(['asignados'=>0]);
                    }
                }

                // 2) Replicar exactamente la plantilla
                foreach ($tpl as $key => $lista) {
                    [$dISO, $sede] = explode('|', $key);
                    $dISO = (int)$dISO;
                    if (!in_array($dISO, [1,2,3,4,5], true)) continue;

                    $fechaTarget = $sem->copy()->addDays($dISO-1);
                    if ($fechaTarget->lt(Carbon::parse($conv->fecha_inicio_beneficio)) ||
                        $fechaTarget->gt(Carbon::parse($conv->fecha_fin_beneficio))) {
                        continue;
                    }

                    $cap = ($sede==='caicedonia') ? (int)($conv->cupos_caicedonia ?? 0) : (int)($conv->cupos_sevilla ?? 0);
                    if ($cap <= 0) continue;

                    $cupo = CupoDiario::firstOrCreate(
                        ['convocatoria_id'=>$conv->id,'fecha'=>$fechaTarget->toDateString(),'sede'=>$sede],
                        ['capacidad'=>$cap,'asignados'=>0]
                    );
                    if ($cupo->capacidad !== $cap) {
                        $cupo->capacidad = $cap;
                        $cupo->save();
                    }

                    // Insertar siguiendo capacidad
                    foreach ($lista as $aBase) {
                        if ($cupo->asignados >= $cupo->capacidad) break;

                        CupoAsignacion::create([
                            'cupo_diario_id' => $cupo->id,
                            'postulacion_id' => $aBase->postulacion_id,
                            'user_id'        => $aBase->user_id,
                            'estado'         => 'asignado',
                            'asignado_en'    => now(),
                            'qr_token'       => bin2hex(random_bytes(16)),
                        ]);
                        $cupo->increment('asignados');
                        $creadas++;
                    }
                }
            }
        });

        return $creadas;
    }
}