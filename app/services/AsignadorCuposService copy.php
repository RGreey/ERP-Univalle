<?php

namespace App\Services;

use App\Models\CupoDiario;
use App\Models\CupoAsignacion;
use App\Models\CupoPatron;
use App\Models\ConvocatoriaSubsidio;
use App\Models\PostulacionSubsidio;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AsignadorCuposService
{
    // Máximos semanales por prioridad (1 = mayor prioridad)
    private array $maxPorSemana = [1=>5,2=>5,3=>4,4=>3,5=>3,6=>2,7=>2,8=>1,9=>1];

    // Genera cupos diarios para TODO el periodo (lun-vie, por sede)
    public function generarPeriodo(ConvocatoriaSubsidio $conv): int
    {
        if (!$conv->fecha_inicio_beneficio || !$conv->fecha_fin_beneficio) {
            return 0;
        }
        $inicio = Carbon::parse($conv->fecha_inicio_beneficio)->startOfDay();
        $fin    = Carbon::parse($conv->fecha_fin_beneficio)->endOfDay();

        $capPorSede = [
            'caicedonia' => (int) ($conv->cupos_caicedonia ?? 0),
            'sevilla'    => (int) ($conv->cupos_sevilla ?? 0),
        ];

        $total = 0;

        DB::transaction(function () use ($conv, $inicio, $fin, $capPorSede, &$total) {
            $fecha = $inicio->copy();
            while ($fecha->lte($fin)) {
                if (in_array($fecha->dayOfWeekIso, [1,2,3,4,5], true)) {
                    foreach ($capPorSede as $sede => $cap) {
                        $cupo = CupoDiario::firstOrCreate(
                            ['convocatoria_id'=>$conv->id,'fecha'=>$fecha->toDateString(),'sede'=>$sede],
                            ['capacidad'=>$cap,'asignados'=>0]
                        );
                        if ($cupo->capacidad !== $cap) {
                            $cupo->capacidad = $cap;
                            $cupo->save();
                        }
                        $total++;
                    }
                }
                $fecha->addDay();
            }
        });

        return $total;
    }

    // Planifica PATRONES semanales por estudiante y los persiste (una vez por convocatoria)
    public function planificarPatrones(ConvocatoriaSubsidio $conv): int
    {
        // Semana base para resolver conflictos (lunes del inicio)
        $lunes = Carbon::parse($conv->fecha_inicio_beneficio)->startOfWeek(Carbon::MONDAY);

        // Asegúrate de tener cupos de esa semana base
        $this->generarSemana($conv, $lunes);

        // Map de ocupación por día/sede de la semana base (para no superar capacidad)
        $cuposSemana = CupoDiario::where('convocatoria_id', $conv->id)
            ->whereBetween('fecha', [$lunes->toDateString(), $lunes->copy()->addDays(6)->toDateString()])
            ->orderBy('fecha')->orderBy('sede')->get()
            ->keyBy(fn($c)=> $c->fecha->toDateString().'|'.$c->sede);

        // Candidatos
        $postulantes = PostulacionSubsidio::with('user')
            ->where('convocatoria_id', $conv->id)
            ->whereIn('estado', ['evaluada','beneficiario'])
            ->orderByRaw('prioridad_final IS NULL')
            ->orderBy('prioridad_final')
            ->orderBy('created_at')
            ->get();

        $preferencias = $this->mapearPreferencias($postulantes);

        $creados = 0;

        DB::transaction(function () use ($postulantes, $preferencias, $cuposSemana, $lunes, $conv, &$creados) {
            foreach ($postulantes as $p) {
                $uid  = $p->user_id;
                $prio = (int) ($p->prioridad_final ?? 9);
                $max  = $this->maxPorSemana[$prio] ?? 1;

                // Si ya tiene patrón, saltar
                if (CupoPatron::where('convocatoria_id',$conv->id)->where('user_id',$uid)->exists()) {
                    continue;
                }

                $diasPrefer = $preferencias[$uid] ?? [1,2,3,4,5];
                $sedesPos   = $p->sede ? [mb_strtolower($p->sede)] : ['caicedonia','sevilla'];

                $diasElegidos = [];
                // Greedy: intenta asignar primero preferencias, respetando capacidad semanal base
                foreach ([1,2,3,4,5] as $dISO) {
                    if (!in_array($dISO, $diasPrefer, true)) continue;

                    foreach ($sedesPos as $sede) {
                        $fecha = $lunes->copy()->addDays($dISO-1)->toDateString();
                        $key   = $fecha.'|'.$sede;
                        $cupo  = $cuposSemana[$key] ?? null;

                        if (!$cupo) continue;
                        if ($cupo->asignados >= $cupo->capacidad) continue;

                        // Reservar slot en patrón
                        $cupo->asignados++; // reservar virtualmente para la semana base
                        $diasElegidos[] = $dISO;

                        // Si ya alcanzó su máximo semanal, paramos
                        if (count($diasElegidos) >= $max) {
                            break 2;
                        }
                    }
                }

                // Si no alcanzó el máximo, y quedan días de lunes a viernes libres, completar
                if (count($diasElegidos) < $max) {
                    foreach ([1,2,3,4,5] as $dISO) {
                        if (in_array($dISO, $diasElegidos, true)) continue;

                        foreach ($sedesPos as $sede) {
                            $fecha = $lunes->copy()->addDays($dISO-1)->toDateString();
                            $key   = $fecha.'|'.$sede;
                            $cupo  = $cuposSemana[$key] ?? null;

                            if (!$cupo) continue;
                            if ($cupo->asignados >= $cupo->capacidad) continue;

                            $cupo->asignados++;
                            $diasElegidos[] = $dISO;

                            if (count($diasElegidos) >= $max) {
                                break 2;
                            }
                        }
                    }
                }

                if (!empty($diasElegidos)) {
                    CupoPatron::create([
                        'convocatoria_id' => $conv->id,
                        'postulacion_id'  => $p->id,
                        'user_id'         => $uid,
                        'dias_iso'        => array_values($diasElegidos),
                        'sede'            => $p->sede ? mb_strtolower($p->sede) : null,
                        'max_semanal'     => count($diasElegidos),
                    ]);
                    $creados++;
                }
            }
        });

        return $creados;
    }

    // Aplica los PATRONES a TODO el periodo, creando CupoAsignacion por cada fecha aplicable
    public function aplicarPatronesEnPeriodo(ConvocatoriaSubsidio $conv): int
    {
        if (!$conv->fecha_inicio_beneficio || !$conv->fecha_fin_beneficio) return 0;

        $inicio = Carbon::parse($conv->fecha_inicio_beneficio)->startOfWeek(Carbon::MONDAY);
        $fin    = Carbon::parse($conv->fecha_fin_beneficio)->endOfWeek(Carbon::SUNDAY);

        // Asegura cupos de todo el periodo
        $this->generarPeriodo($conv);

        $patrones = CupoPatron::where('convocatoria_id', $conv->id)->get();

        $creados = 0;

        DB::transaction(function () use ($patrones, $inicio, $fin, $conv, &$creados) {
            $semana = $inicio->copy();

            while ($semana->lte($fin)) {
                // Por cada patrón, materializar sus días en esta semana
                foreach ($patrones as $pat) {
                    $dias = (array) $pat->dias_iso;
                    $sedePat = $pat->sede; // si es null, elegimos sede con disponibilidad (intentamos ambas)

                    foreach ($dias as $dISO) {
                        $fecha = $semana->copy()->addDays($dISO-1);
                        if ($fecha->lt(Carbon::parse($conv->fecha_inicio_beneficio)) ||
                            $fecha->gt(Carbon::parse($conv->fecha_fin_beneficio))) {
                            continue;
                        }

                        $sedesIntento = $sedePat ? [$sedePat] : ['caicedonia','sevilla'];

                        foreach ($sedesIntento as $sede) {
                            // Cupo diario
                            $cupo = CupoDiario::firstOrCreate(
                                ['convocatoria_id'=>$conv->id,'fecha'=>$fecha->toDateString(),'sede'=>$sede],
                                ['capacidad'=> (int) ($sede==='caicedonia' ? ($conv->cupos_caicedonia ?? 0) : ($conv->cupos_sevilla ?? 0)), 'asignados'=>0]
                            );

                            // Evitar duplicado del mismo usuario ese día/sede
                            $existe = CupoAsignacion::where('cupo_diario_id',$cupo->id)
                                ->where('user_id', $pat->user_id)
                                ->exists();
                            if ($existe) break;

                            if ($cupo->asignados < $cupo->capacidad) {
                                CupoAsignacion::create([
                                    'cupo_diario_id' => $cupo->id,
                                    'postulacion_id' => $pat->postulacion_id,
                                    'user_id'        => $pat->user_id,
                                    'estado'         => 'asignado',
                                    'asignado_en'    => now(),
                                    'qr_token'       => bin2hex(random_bytes(16)),
                                ]);
                                $cupo->increment('asignados');
                                $creados++;
                                break;
                            }
                        }
                    }
                }

                $semana->addWeek();
            }
        });

        return $creados;
    }

    // Genera cupos para una SEMANA (helper interno/externo)
    public function generarSemana(ConvocatoriaSubsidio $conv, Carbon $lunes): Collection
    {
        $fechas = collect(range(0, 6))
            ->map(fn($d)=> $lunes->copy()->addDays($d))
            ->filter(fn($f)=> in_array($f->dayOfWeekIso, [1,2,3,4,5]));

        $capPorSede = [
            'caicedonia' => (int) ($conv->cupos_caicedonia ?? 0),
            'sevilla'    => (int) ($conv->cupos_sevilla ?? 0),
        ];

        $creados = collect();

        DB::transaction(function () use ($conv, $fechas, $capPorSede, &$creados) {
            foreach ($fechas as $f) {
                foreach ($capPorSede as $sede => $cap) {
                    $cupo = CupoDiario::firstOrCreate(
                        ['convocatoria_id'=>$conv->id,'fecha'=>$f->toDateString(),'sede'=>$sede],
                        ['capacidad'=>$cap, 'asignados'=>0]
                    );
                    if ($cupo->capacidad !== $cap) {
                        $cupo->capacidad = $cap;
                        $cupo->save();
                    }
                    $creados->push($cupo);
                }
            }
        });

        return $creados;
    }

    private function mapearPreferencias($postulantes): array
    {
        $map = [];
        foreach ($postulantes as $p) {
            $dias = [];
            if (isset($p->preferencias_dias) && is_array($p->preferencias_dias) && count($p->preferencias_dias)) {
                $dias = $p->preferencias_dias;
            } else {
                // Fallback: intenta desde respuestas JSON
                try {
                    $rels = $p->relationLoaded('respuestas') ? $p->respuestas : $p->respuestas()->with('pregunta')->get();
                    $r = $rels->first(function ($r) {
                        $tipo = optional($r->pregunta)->tipo;
                        $nombre = mb_strtolower(optional($r->pregunta)->nombre ?? '');
                        return ($tipo === 'matrix_single') || str_contains($nombre, 'preferencias') || str_contains($nombre, 'días');
                    });
                    if ($r && is_array($r->respuesta_json)) $dias = $r->respuesta_json;
                } catch (\Throwable $e) {
                    $dias = [];
                }
            }

            $iso = array_map(function ($d) {
                $d = mb_strtolower((string) $d);
                return match ($d) {
                    '1','lunes','lun'       => 1,
                    '2','martes','mar'      => 2,
                    '3','miercoles','miércoles','mie' => 3,
                    '4','jueves','jue'      => 4,
                    '5','viernes','vie'     => 5,
                    '6','sabado','sábado','sab' => 6,
                    '7','domingo','dom'     => 7,
                    default => null,
                };
            }, is_array($dias) ? $dias : []);
            $iso = array_values(array_filter($iso));
            $map[$p->user_id] = count($iso) ? $iso : [1,2,3,4,5];
        }
        return $map;
    }
}