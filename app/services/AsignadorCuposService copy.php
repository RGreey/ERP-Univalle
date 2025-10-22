<?php

namespace App\Services;

use App\Models\ConvocatoriaSubsidio;
use App\Models\CupoDiario;
use App\Models\CupoAsignacion;
use App\Models\CupoPatron;
use App\Models\PostulacionSubsidio;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AsignadorCuposService
{
    // Máximo de cupos semanales por prioridad_final (1 = mayor prioridad)
    private array $maxPorSemana = [1=>5,2=>5,3=>4,4=>3,5=>3,6=>2,7=>2,8=>1,9=>1];

    // Crea/actualiza CupoDiario de TODO el periodo (L–V por sede) y sincroniza capacidad
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
            $f = $inicio->copy();
            while ($f->lte($fin)) {
                if (in_array($f->dayOfWeekIso, [1,2,3,4,5], true)) {
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
                $f->addDay();
            }
        });
        return $total;
    }

    // Planifica patrones semanales por estudiante usando reparto equitativo (no crea asignaciones aún)
    public function planificarPatrones(ConvocatoriaSubsidio $conv): int
    {
        // Semana base (solo se guarda el patrón por ISO de L–V)
        $lunesBase = Carbon::parse($conv->fecha_inicio_beneficio ?: now())
            ->startOfWeek(Carbon::MONDAY);

        $capSede = [
            'caicedonia' => (int)($conv->cupos_caicedonia ?? 0),
            'sevilla'    => (int)($conv->cupos_sevilla ?? 0),
        ];
        if ($capSede['caicedonia']===0 && $capSede['sevilla']===0) return 0;

        // Ocupación virtual de la semana base por día y sede
        $ocup = [];
        foreach ([1,2,3,4,5] as $d) {
            $ocup[$d] = ['caicedonia'=>0,'sevilla'=>0];
        }

        // Postulantes ordenados
        $postulantes = PostulacionSubsidio::with('user')
            ->where('convocatoria_id', $conv->id)
            ->whereIn('estado', ['evaluada','beneficiario'])
            ->orderByRaw('prioridad_final IS NULL') // nulls al final
            ->orderBy('prioridad_final')
            ->orderBy('created_at')
            ->get();

        // Preferencias mapeadas
        $prefs = $this->mapearPreferencias($postulantes);

        $creados = 0;

        DB::transaction(function () use ($postulantes, $prefs, $capSede, &$ocup, $conv, &$creados) {
            foreach ($postulantes as $p) {
                $uid  = $p->user_id;
                $prio = (int)($p->prioridad_final ?? 9);
                $max  = $this->maxPorSemana[$prio] ?? 1;

                // Sede normalizada
                $sede = mb_strtolower((string)($p->sede ?? 'caicedonia'));
                if (!in_array($sede, ['caicedonia','sevilla'], true)) {
                    $sede = 'caicedonia';
                }
                if (($capSede[$sede] ?? 0) <= 0) continue;

                $diasPrefer = $prefs[$uid]['dias'] ?? [1,2,3,4,5];

                // Elegir hasta $max días preferidos priorizando menor ocupación del día
                $elegidos = [];
                for ($k=0; $k<$max; $k++) {
                    // ordenar los días preferidos por ocupación asc y por día asc
                    $orden = collect($diasPrefer)
                        ->reject(fn($dISO)=> in_array($dISO, $elegidos, true))
                        ->map(fn($dISO)=> ['d'=>$dISO,'occ'=>$ocup[$dISO][$sede] ?? 0])
                        ->sortBy([['occ','asc'],['d','asc']])
                        ->pluck('d')
                        ->values();

                    $asignado = false;
                    foreach ($orden as $dISO) {
                        if (($ocup[$dISO][$sede] ?? 0) < ($capSede[$sede] ?? 0)) {
                            $ocup[$dISO][$sede] = ($ocup[$dISO][$sede] ?? 0) + 1;
                            $elegidos[] = $dISO;
                            $asignado = true;
                            break;
                        }
                    }
                    if (!$asignado) break; // no hay más espacio esa semana
                }

                // Guarda/actualiza patrón (días ISO semanales)
                CupoPatron::updateOrCreate(
                    ['convocatoria_id'=>$conv->id, 'user_id'=>$uid],
                    [
                        'postulacion_id' => $p->id,
                        'dias_iso'       => array_values($elegidos),
                    ]
                );
                if (!empty($elegidos)) $creados++;
            }
        });

        return $creados;
    }

    // Aplica los patrones en todo el periodo creando CupoAsignacion, sincronizando capacidad
    public function aplicarPatronesEnPeriodo(ConvocatoriaSubsidio $conv): int
    {
        if (!$conv->fecha_inicio_beneficio || !$conv->fecha_fin_beneficio) return 0;

        // Asegura cupos y capacidades
        $this->generarPeriodo($conv);

        $patrones = CupoPatron::with('postulacion.user')
            ->where('convocatoria_id', $conv->id)->get();

        $inicio = Carbon::parse($conv->fecha_inicio_beneficio)->startOfWeek(Carbon::MONDAY);
        $fin    = Carbon::parse($conv->fecha_fin_beneficio)->endOfWeek(Carbon::SUNDAY);

        $creadas = 0;

        DB::transaction(function () use ($patrones, $inicio, $fin, $conv, &$creadas) {
            $semana = $inicio->copy();
            while ($semana->lte($fin)) {
                foreach ($patrones as $pat) {
                    $post = $pat->postulacion; // contiene user y sede de la postulación
                    if (!$post) continue;

                    $sede = mb_strtolower((string)($post->sede ?? 'caicedonia'));
                    if (!in_array($sede, ['caicedonia','sevilla'], true)) $sede = 'caicedonia';

                    $dias = (array)($pat->dias_iso ?? []);
                    foreach ($dias as $dISO) {
                        // Fecha del día ISO dentro de esta semana
                        $fecha = $semana->copy()->addDays(((int)$dISO)-1);

                        // Solo asigna dentro del rango real de beneficio
                        if ($fecha->lt(Carbon::parse($conv->fecha_inicio_beneficio)) ||
                            $fecha->gt(Carbon::parse($conv->fecha_fin_beneficio))) {
                            continue;
                        }

                        // Cupo del día (capacidad sincronizada con la convocatoria)
                        $cap = ($sede==='caicedonia') ? (int)($conv->cupos_caicedonia ?? 0) : (int)($conv->cupos_sevilla ?? 0);
                        $cupo = CupoDiario::firstOrCreate(
                            ['convocatoria_id'=>$conv->id,'fecha'=>$fecha->toDateString(),'sede'=>$sede],
                            ['capacidad'=>$cap,'asignados'=>0]
                        );
                        if ($cupo->capacidad !== $cap) {
                            $cupo->capacidad = $cap;
                            $cupo->save();
                        }

                        // Evitar duplicado del usuario ese día (cualquier sede)
                        $ya = CupoAsignacion::whereHas('cupo', function($q) use ($conv, $fecha) {
                                $q->where('convocatoria_id', $conv->id)
                                  ->whereDate('fecha', $fecha->toDateString());
                            })
                            ->where('user_id', $post->user_id)
                            ->exists();
                        if ($ya) continue;

                        if ($cupo->asignados >= $cupo->capacidad) continue;

                        CupoAsignacion::create([
                            'cupo_diario_id' => $cupo->id,
                            'postulacion_id' => $pat->postulacion_id,
                            'user_id'        => $post->user_id,
                            'estado'         => 'asignado',
                            'asignado_en'    => now(),
                            'qr_token'       => bin2hex(random_bytes(16)),
                        ]);
                        $cupo->increment('asignados');
                        $creadas++;
                    }
                }
                $semana->addWeek();
            }
        });

        return $creadas;
    }

    // Intenta leer la matriz de preferencias; si no existe, usa L–V por defecto
    private function mapearPreferencias(Collection $postulantes): array
    {
        $out = [];
        foreach ($postulantes as $p) {
            $uid = $p->user_id;
            // Por simplicidad: si tienes una relación respuestas o JSON con matriz de días, puedes leerla aquí.
            // Fallback: lunes–viernes
            $out[$uid] = [
                'sede' => mb_strtolower((string)($p->sede ?? 'caicedonia')),
                'dias' => [1,2,3,4,5],
            ];
        }
        return $out;
    }
}