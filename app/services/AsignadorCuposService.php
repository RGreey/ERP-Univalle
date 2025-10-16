<?php

namespace App\Services;

use App\Models\ConvocatoriaSubsidio;
use App\Models\CupoDiario;
use App\Models\CupoAsignacion;
use App\Models\PostulacionSubsidio;
use Carbon\Carbon;
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

    // Asigna automáticamente la SEMANA seleccionada (L–V), respetando preferencias por día/sede y el límite semanal por prioridad
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

        // Candidatos ordenados (prioridad -> menos cupos en la semana -> antigüedad)
        $candidatos = PostulacionSubsidio::with('user')
            ->where('convocatoria_id', $conv->id)
            ->whereIn('estado', ['evaluada','beneficiario'])
            ->orderByRaw('prioridad_final IS NULL')
            ->orderBy('prioridad_final')
            ->orderBy('created_at')
            ->get();

        // Cachear preferencias por postulante (lee la respuesta tipo matrix_single)
        $prefCache = [];
        foreach ($candidatos as $p) {
            $prefCache[$p->user_id] = $this->preferenciasPorDia($p);
        }

        $maxPorSemana = $this->maxPorSemana;
        $totalCreadas = 0;

        DB::transaction(function () use ($conv, $lunes, $candidatos, $maxPorSemana, &$semanaCnt, &$totalCreadas, $prefCache) {
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

                    // Elegibles que marcaron esta sede en este día y no marcaron "no_dia"
                    $eligibles = $candidatos->filter(function ($p) use ($prefCache, $dISO, $sede) {
                            $row = $prefCache[$p->user_id][$dISO] ?? ['caicedonia'=>true,'sevilla'=>true,'no'=>false];
                            if (!empty($row['no'])) return false;
                            return !empty($row[$sede]);
                        })
                        ->map(function($p) use ($semanaCnt) {
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

                        // Límite semanal por prioridad (L–V)
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

    private function preferenciasPorDia(PostulacionSubsidio $p): array
    {
        // Según el modelo, existe el accesor getPreferenciasDiasAttribute() que retorna el JSON de matrix_single
        $raw = (array) ($p->preferencias_dias ?? []);
        // Esperamos valores 'caicedonia', 'sevilla', 'no_dia' por fila (lunes..viernes)
        // Intentamos mapear tanto si vienen con claves numéricas 1..5 como si vienen con texto.
        $diasKeyToIso = [
            '1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,
            'lunes'=>1,'martes'=>2,'miercoles'=>3,'miércoles'=>3,'jueves'=>4,'viernes'=>5,
        ];

        $norm = function (string $v): string {
            $v = mb_strtolower(trim($v));
            $v = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $v);
            return $v;
        };

        $out = [];
        // Inicializar por defecto (si no hay info, permitir ambas sedes)
        foreach ([1,2,3,4,5] as $i) $out[$i] = ['caicedonia'=>true,'sevilla'=>true,'no'=>false];

        foreach ($raw as $k => $val) {
            $kNorm = $norm((string)$k);
            $dISO  = $diasKeyToIso[$kNorm] ?? null;
            if (!$dISO) continue;

            $vNorm = $norm((string)$val);
            if ($vNorm === 'no_dia' || $vNorm === 'no' || $vNorm === 'ninguno') {
                $out[$dISO] = ['caicedonia'=>false,'sevilla'=>false,'no'=>true];
            } elseif ($vNorm === 'caicedonia') {
                $out[$dISO] = ['caicedonia'=>true,'sevilla'=>false,'no'=>false];
            } elseif ($vNorm === 'sevilla') {
                $out[$dISO] = ['caicedonia'=>false,'sevilla'=>true,'no'=>false];
            }
        }

        return $out;
    }
    /*
     |---------------------------------------
     | Preferencias de días y sedes (parsing)
     |---------------------------------------
     | Devuelve un arreglo:
     |   [ dISO => ['caicedonia'=>bool,'sevilla'=>bool,'no'=>bool], ... ]
     | Si no hay datos, por compatibilidad: habilita ambas sedes (no=false) en L–V.
     */
    private function parsePreferenciasDias(PostulacionSubsidio $p): array
    {
        $dias = ['lunes'=>1,'martes'=>2,'miercoles'=>3,'miércoles'=>3,'jueves'=>4,'viernes'=>5];
        $sedes = ['caicedonia','sevilla'];

        // 1) Recolectar posibles fuentes
        $candidatos = [];

        foreach (['preferencias_dias','pref_dias','dias_preferidos','dias','preferencias'] as $prop) {
            if (isset($p->{$prop})) {
                $candidatos[] = $p->{$prop};
            }
        }
        foreach (['respuestas_json','respuestas'] as $prop) {
            if (isset($p->{$prop})) {
                $candidatos[] = $p->{$prop};
            }
        }

        // 2) Normalizar a array
        $data = [];
        foreach ($candidatos as $src) {
            if (is_string($src)) {
                $decoded = json_decode($src, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[] = $decoded;
                }
            } elseif (is_array($src)) {
                $data[] = $src;
            } elseif (is_object($src)) {
                $data[] = json_decode(json_encode($src), true);
            }
        }
        // Si no se encontró nada, default: ambos true L–V
        if (empty($data)) {
            $out = [];
            foreach ([1,2,3,4,5] as $dISO) {
                $out[$dISO] = ['caicedonia'=>true,'sevilla'=>true,'no'=>false];
            }
            return $out;
        }

        // 3) Aplanar y detectar claves
        $flat = [];
        $flatten = function ($arr, $prefix='') use (&$flatten, &$flat) {
            foreach ($arr as $k=>$v) {
                $key = ($prefix === '' ? $k : $prefix.'.'.$k);
                if (is_array($v)) {
                    $flatten($v, $key);
                } else {
                    $flat[$key] = $v;
                }
            }
        };
        foreach ($data as $arr) $flatten($arr);

        $normKey = function (string $k): string {
            $k = mb_strtolower($k);
            $k = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $k);
            return $k;
        };

        $truthy = function ($v): bool {
            if (is_bool($v)) return $v;
            $s = $v;
            if (is_numeric($v)) return ((int)$v) === 1;
            $s = mb_strtolower(trim((string)$v));
            return in_array($s, ['1','true','si','sí','on','y','yes','x','✓','check','checked'], true);
        };

        $pref = [];
        foreach ($flat as $k => $v) {
            $nk = $normKey($k);
            // Intentar detectar día
            $foundDia = null;
            foreach ($dias as $diaTxt => $dISO) {
                if (str_contains($nk, $diaTxt)) { $foundDia = $dISO; break; }
            }
            if (!$foundDia) continue;

            // Detectar "no necesito"
            if (str_contains($nk, 'no_necesita') || str_contains($nk, 'no-necesita') || str_contains($nk, 'ninguno') || str_contains($nk, 'no')) {
                $pref[$foundDia]['no'] = $truthy($v);
                // si es "no", de todos modos seguimos por si hay claves más específicas que lo anulan
            }

            // Detectar sede
            foreach ($sedes as $s) {
                if (str_contains($nk, $s)) {
                    $pref[$foundDia][$s] = $truthy($v);
                }
            }
        }

        // 4) Completar defaults donde falte información
        $out = [];
        foreach ([1,2,3,4,5] as $dISO) {
            $row = $pref[$dISO] ?? [];
            $no  = (bool)($row['no'] ?? false);

            // Si no hay nada explícito y tampoco "no", se habilitan ambas sedes
            $c = array_key_exists('caicedonia', $row) ? (bool)$row['caicedonia'] : !$no;
            $s = array_key_exists('sevilla', $row)    ? (bool)$row['sevilla']    : !$no;

            // Si marcó NO, forzamos ambas sedes a false
            if ($no) { $c = false; $s = false; }

            $out[$dISO] = ['caicedonia'=>$c,'sevilla'=>$s,'no'=>$no];
        }

        return $out;
    }
}