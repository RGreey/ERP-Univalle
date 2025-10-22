<?php

namespace App\Http\Controllers;

use App\Models\ConvocatoriaSubsidio;
use App\Models\CupoDiario;
use App\Models\CupoAsignacion;
use App\Models\PostulacionSubsidio;
use App\Services\AsignadorCuposService;
use App\Services\StandbyOfferService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CuposReporteSemanaExport;

class AdminCuposController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:AdminBienestar']);
    }

    public function index(Request $request)
    {
        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')
            ->get(['id','nombre','cupos_caicedonia','cupos_sevilla','fecha_inicio_beneficio','fecha_fin_beneficio']);

        $convId = $request->input('convocatoria_id') ?: optional($convocatorias->first())->id;
        $lunes  = Carbon::parse($request->input('lunes', now()->startOfWeek(Carbon::MONDAY)))->startOfWeek(Carbon::MONDAY);

        $cupos = collect();
        $conv  = null;

        if ($convId) {
            $conv = ConvocatoriaSubsidio::find($convId);
            $cupos = CupoDiario::where('convocatoria_id', $convId)
                ->whereBetween('fecha', [$lunes->toDateString(), $lunes->copy()->addDays(6)->toDateString()])
                ->orderBy('fecha')->orderBy('sede')->get();
        }

        $asignadosSemana = 0;
        if ($convId) {
            $asignadosSemana = CupoAsignacion::whereHas('cupo', function($q) use ($convId, $lunes) {
                $q->where('convocatoria_id', $convId)
                  ->whereBetween('fecha', [$lunes->toDateString(), $lunes->copy()->addDays(6)->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) <= 4'); // mostrar solo L–V en el marcador
            })->count();
        }

        return view('roles.adminbienestar.cupos.index', [
            'convocatorias'   => $convocatorias,
            'convId'          => $convId,
            'convocatoria'    => $conv,
            'lunes'           => $lunes,
            'cupos'           => $cupos,
            'asignadosSemana' => $asignadosSemana,
        ]);
    }

    // Asignar automáticamente semana actual (L–V)
    public function autoAsignarSemana(Request $request, AsignadorCuposService $svc)
    {
        $data = $request->validate([
            'convocatoria_id' => ['required','integer','exists:convocatorias_subsidio,id'],
            'lunes'           => ['required','date'],
        ]);
        $conv  = ConvocatoriaSubsidio::findOrFail($data['convocatoria_id']);
        $lunes = Carbon::parse($data['lunes'])->startOfWeek(Carbon::MONDAY);

        if (!$conv->fecha_inicio_beneficio || !$conv->fecha_fin_beneficio) {
            return back()->with('success', 'Define fecha de inicio y fin del beneficio en la convocatoria.')->withInput();
        }

        $n = $svc->autoAsignarSemana($conv, $lunes);

        return back()->with('success', "Semana auto-asignada (L–V). Nuevas asignaciones: {$n}.")->withInput();
    }

    // Gestión diaria (con contadores de semana L–V y sin bloqueo por sede)
    public function dia(Request $request)
    {
        $data = $request->validate([
            'convocatoria_id'     => ['required','integer','exists:convocatorias_subsidio,id'],
            'fecha'               => ['required','date'],
            'sede'                => ['required','in:caicedonia,sevilla'],
            'incluir_otras_sedes' => ['sometimes','boolean'],
            'q'                   => ['sometimes','nullable','string'],
        ]);

        $conv   = ConvocatoriaSubsidio::findOrFail($data['convocatoria_id']);
        $fecha  = Carbon::parse($data['fecha'])->toImmutable();
        $sede   = $data['sede'];
        $lunes  = $fecha->startOfWeek(Carbon::MONDAY);
        $domingo= $fecha->endOfWeek(Carbon::SUNDAY);
        $incluirOtrasSedes = array_key_exists('incluir_otras_sedes', $data) ? (bool)$data['incluir_otras_sedes'] : true;
        $q = $data['q'] ?? null;

        // Asegura el cupo del día
        $capBase = ($sede === 'caicedonia') ? (int)($conv->cupos_caicedonia ?? 0) : (int)($conv->cupos_sevilla ?? 0);
        $cupo = CupoDiario::firstOrCreate(
            ['convocatoria_id'=>$conv->id, 'fecha'=>$fecha->toDateString(), 'sede'=>$sede],
            ['capacidad'=> $capBase, 'asignados'=>0]
        );

        $asignados = CupoAsignacion::with('user')
            ->where('cupo_diario_id', $cupo->id)
            ->orderBy('created_at')
            ->get();

        $usersAsignadosEseDia = CupoAsignacion::whereHas('cupo', function($q2) use ($conv, $fecha) {
                $q2->where('convocatoria_id', $conv->id)->whereDate('fecha', $fecha->toDateString());
            })
            ->pluck('user_id')->unique()->all();

        // Conteo de semana SOLO L–V
        $cntSemana = CupoAsignacion::select('user_id', DB::raw('COUNT(*) as c'))
            ->whereHas('cupo', function($q2) use ($conv, $lunes, $domingo) {
                $q2->where('convocatoria_id', $conv->id)
                   ->whereBetween('fecha', [$lunes->toDateString(), $domingo->toDateString()])
                   ->whereRaw('WEEKDAY(fecha) <= 4'); // L–V
            })
            ->groupBy('user_id')
            ->pluck('c', 'user_id');

        // Candidatos listados (para la vista) — no filtramos aquí por sede; el filtro estricto se hace al auto-asignar
        $candidatos = PostulacionSubsidio::with('user')
            ->where('convocatoria_id', $conv->id)
            ->whereIn('estado', ['evaluada','beneficiario'])
            ->when($q, fn($qb)=>$qb->whereHas('user', fn($uq)=>$uq->where('name','like',"%$q%")->orWhere('email','like',"%$q%")))
            ->get()
            ->map(function ($p) use ($cntSemana, $usersAsignadosEseDia) {
                $p->semana_asignados = (int) ($cntSemana[$p->user_id] ?? 0);
                $p->asignado_este_dia = in_array($p->user_id, $usersAsignadosEseDia, true);
                $p->prioridad_orden = (int) ($p->prioridad_final ?? 999);
                return $p;
            })
            ->sortBy([
                ['prioridad_orden','asc'],
                ['semana_asignados','asc'],
                ['created_at','asc'],
            ])
            ->values();

        return view('roles.adminbienestar.cupos.dia', compact(
            'conv', 'fecha','sede','cupo','asignados','candidatos','lunes','domingo','incluirOtrasSedes','q'
        ))->with(['convocatoria'=>$conv]);
    }

    // “Generar plantilla con la semana actual” (solo muestra mensaje; la plantilla es la propia semana actual)
    public function generarPlantillaSemana(Request $request)
    {
        $data = $request->validate([
            'convocatoria_id' => ['required','integer','exists:convocatorias_subsidio,id'],
            'lunes'           => ['required','date'],
        ]);
        $conv  = ConvocatoriaSubsidio::findOrFail($data['convocatoria_id']);
        $lunes = Carbon::parse($data['lunes'])->startOfWeek(Carbon::MONDAY);

        $tiene = CupoAsignacion::whereHas('cupo', function($q) use ($conv, $lunes) {
                $q->where('convocatoria_id', $conv->id)
                  ->whereBetween('fecha', [$lunes->toDateString(), $lunes->copy()->addDays(6)->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) <= 4'); // L–V
            })->exists();

        if (!$tiene) {
            return back()->with('success', 'No hay asignaciones en la semana seleccionada. Usa "Asignar automáticamente semana actual" primero.')->withInput();
        }

        return back()->with('success', 'Plantilla lista a partir de la semana actual. Ahora puedes "Aplicar a todos los días del período".')->withInput();
    }

    // Replica la semana actual a todo el período SOBRESCRIBIENDO semanas futuras (L–V)
    public function aplicarPlantillaPeriodo(Request $request, AsignadorCuposService $svc)
    {
        $data = $request->validate([
            'convocatoria_id' => ['required','integer','exists:convocatorias_subsidio,id'],
            'lunes'           => ['required','date'],
        ]);
        $conv  = ConvocatoriaSubsidio::findOrFail($data['convocatoria_id']);
        $lunes = Carbon::parse($data['lunes'])->startOfWeek(Carbon::MONDAY);

        if (!$conv->fecha_inicio_beneficio || !$conv->fecha_fin_beneficio) {
            return back()->with('success', 'Define fecha de inicio y fin del beneficio en la convocatoria.')->withInput();
        }

        $n = $svc->aplicarSemanaATodoPeriodo($conv, $lunes);

        return back()->with('success', "Plantilla aplicada al período (sobrescritura L–V). Nuevas asignaciones: {$n}.")->withInput();
    }

    // Reporte HTML de la semana
    public function reporteSemana(Request $request)
    {
        $data = $request->validate([
            'convocatoria_id' => ['required','integer','exists:convocatorias_subsidio,id'],
            'lunes'           => ['required','date'],
        ]);
        $conv  = ConvocatoriaSubsidio::findOrFail($data['convocatoria_id']);
        $lunes = Carbon::parse($data['lunes'])->startOfWeek(Carbon::MONDAY);

        $asignaciones = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($conv, $lunes) {
                $q->where('convocatoria_id', $conv->id)
                  ->whereBetween('fecha', [$lunes->toDateString(), $lunes->copy()->addDays(6)->toDateString()]);
            })
            ->get()
            ->groupBy(fn($a) => $a->cupo->fecha->toDateString().'|'.$a->cupo->sede);

        $cuposSemana = CupoDiario::where('convocatoria_id', $conv->id)
            ->whereBetween('fecha', [$lunes->toDateString(), $lunes->copy()->addDays(6)->toDateString()])
            ->orderBy('fecha')->orderBy('sede')->get()
            ->keyBy(fn($c)=> $c->fecha->toDateString().'|'.$c->sede);

        return view('roles.adminbienestar.cupos.reporte', compact('conv','lunes','asignaciones','cuposSemana'))
               ->with(['convocatoria'=>$conv]);
    }

    public function exportarReporteSemanaExcel(Request $request)
    {
        $data = $request->validate([
            'convocatoria_id' => ['required','integer','exists:convocatorias_subsidio,id'],
            'lunes'           => ['required','date'],
        ]);

        $conv  = ConvocatoriaSubsidio::findOrFail($data['convocatoria_id']);
        $lunes = Carbon::parse($data['lunes'])->startOfWeek(Carbon::MONDAY);

        $filename = 'reporte_semana_'.$conv->id.'_'.$lunes->format('Ymd').'.xlsx';

        return Excel::download(
            new CuposReporteSemanaExport($conv->id, $lunes),
            $filename
        );
    }
    // Export CSV
    public function exportarSemana(Request $request)
    {
        $data = $request->validate([
            'convocatoria_id' => ['required','integer','exists:convocatorias_subsidio,id'],
            'lunes'           => ['required','date'],
        ]);

        $conv  = ConvocatoriaSubsidio::findOrFail($data['convocatoria_id']);
        $lunes = Carbon::parse($data['lunes'])->startOfWeek(Carbon::MONDAY);
        $filename = 'cupos_semana_'.$conv->id.'_'.$lunes->format('Ymd').'.csv';

        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($conv, $lunes) {
                $q->where('convocatoria_id', $conv->id)
                  ->whereBetween('fecha', [$lunes->toDateString(), $lunes->copy()->addDays(6)->toDateString()]);
            })
            ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'));

        return Response::streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fputcsv($out, ['Fecha','Sede','Estudiante','Correo']);
            $query->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    fputcsv($out, [
                        optional($a->cupo->fecha)->format('Y-m-d'),
                        ucfirst($a->cupo->sede),
                        optional($a->user)->name,
                        optional($a->user)->email,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Gestión diaria (con contadores de semana L–V y sin bloqueo por sede)


    public function actualizarCapacidadDia(Request $request)
    {
        $data = $request->validate([
            'cupo_diario_id' => ['required','integer','exists:subsidio_cupos_diarios,id'],
            'capacidad'      => ['required','integer','min:0'],
        ]);

        $cupo = CupoDiario::findOrFail($data['cupo_diario_id']);
        if ($data['capacidad'] < $cupo->asignados) {
            return back()->with('success', 'La capacidad no puede ser menor que los asignados actuales ('.$cupo->asignados.').');
        }
        $cupo->capacidad = $data['capacidad'];
        $cupo->save();

        return back()->with('success', 'Capacidad actualizada.');
    }


    public function asignarManual(Request $request)
    {
        $data = $request->validate([
            'cupo_diario_id' => ['required','integer','exists:subsidio_cupos_diarios,id'],
            'postulacion_id' => ['required','integer','exists:subsidio_postulaciones,id'],
        ]);

        $cupo = CupoDiario::findOrFail($data['cupo_diario_id']);
        $post = PostulacionSubsidio::with('user')->findOrFail($data['postulacion_id']);

        $ya = CupoAsignacion::whereHas('cupo', function($q) use ($cupo) {
                $q->where('convocatoria_id', $cupo->convocatoria_id)->whereDate('fecha', $cupo->fecha->toDateString());
            })
            ->where('user_id', $post->user_id)
            ->exists();
        if ($ya) {
            return back()->with('success', 'El estudiante ya está asignado este día.');
        }

        $activos = $cupo->ocupacionActiva();
        if ($activos >= $cupo->capacidad) {
            return back()->with('success', 'No hay cupos disponibles para este día.');
        }

        CupoAsignacion::create([
            'cupo_diario_id' => $cupo->id,
            'postulacion_id' => $post->id,
            'user_id'        => $post->user_id,
            'estado'         => 'asignado',
            'asignado_en'    => now(),
            'qr_token'       => bin2hex(random_bytes(16)),
        ]);
        $cupo->increment('asignados');

        return back()->with('success', 'Estudiante asignado.');
    }

    public function desasignarManual(CupoAsignacion $asignacion)
    {
        $cupo = $asignacion->cupo;
        $asignacion->delete();
        if ($cupo && $cupo->asignados > 0) {
            $cupo->decrement('asignados');
        }
        return back()->with('success', 'Asignación eliminada.');
    }

    // Auto-asignar cupos del día (con límite semanal L–V)
    public function autoAsignarDia(Request $request)
    {
        $data = $request->validate([
            'convocatoria_id'        => ['required','integer','exists:convocatorias_subsidio,id'],
            'cupo_diario_id'         => ['required','integer','exists:subsidio_cupos_diarios,id'],
            'respetar_limite_semanal'=> ['sometimes','boolean'],
            'incluir_otras_sedes'    => ['sometimes','boolean'],
        ]);

        $maxPorSemana = [1=>5,2=>5,3=>4,4=>3,5=>3,6=>2,7=>2,8=>1,9=>1];

        $conv = ConvocatoriaSubsidio::findOrFail($data['convocatoria_id']);
        $cupo = CupoDiario::findOrFail($data['cupo_diario_id']);
        $fecha  = $cupo->fecha->toImmutable();
        $lunes  = $fecha->startOfWeek(Carbon::MONDAY);
        $domingo= $fecha->endOfWeek(Carbon::SUNDAY);
        $sede   = $cupo->sede;

        $respetarLimite   = (bool) ($data['respetar_limite_semanal'] ?? true);
        $incluirOtrasSedes= array_key_exists('incluir_otras_sedes', $data) ? (bool)$data['incluir_otras_sedes'] : true;

        $usersAsignadosEseDia = CupoAsignacion::whereHas('cupo', function($q2) use ($conv, $fecha) {
                $q2->where('convocatoria_id', $conv->id)->whereDate('fecha', $fecha->toDateString());
            })->pluck('user_id')->unique()->all();

        // Conteo semanal SOLO L–V
        $cntSemana = CupoAsignacion::select('user_id', DB::raw('COUNT(*) as c'))
            ->whereHas('cupo', function($q2) use ($conv, $lunes, $domingo) {
                $q2->where('convocatoria_id', $conv->id)
                   ->whereBetween('fecha', [$lunes->toDateString(), $domingo->toDateString()])
                   ->whereRaw('WEEKDAY(fecha) <= 4'); // L–V
            })
            ->groupBy('user_id')
            ->pluck('c', 'user_id');

        $candidatos = PostulacionSubsidio::with('user')
            ->where('convocatoria_id', $conv->id)
            ->whereIn('estado', ['evaluada','beneficiario'])
            ->get()
            ->map(function ($p) use ($cntSemana) {
                $p->semana_asignados = (int) ($cntSemana[$p->user_id] ?? 0);
                $p->prioridad_orden  = (int) ($p->prioridad_final ?? 999);
                return $p;
            })
            ->sortBy([
                ['prioridad_orden','asc'],
                ['semana_asignados','asc'],
                ['created_at','asc'],
            ])
            ->values();

        // Cachear preferencias
        $prefCache = [];
        foreach ($candidatos as $p) {
            $prefCache[$p->user_id] = $this->preferenciasPorDiaCtl($p);
        }

        $creados = 0;

        DB::transaction(function () use ($cupo, $conv, $fecha, $sede, $respetarLimite, $maxPorSemana, $candidatos, $usersAsignadosEseDia, $incluirOtrasSedes, $prefCache, &$creados) {
            $dISO = $fecha->dayOfWeekIso;

            foreach ($candidatos as $p) {
                if ($cupo->asignados >= $cupo->capacidad) break;
                if (in_array($p->user_id, $usersAsignadosEseDia, true)) continue;

                $row = $prefCache[$p->user_id][$dISO] ?? ['caicedonia'=>true,'sevilla'=>true,'no'=>false];

                // Si marcó "no" para el día, no asignar.
                if (!empty($row['no'])) continue;

                // Si explicitó sede, exigir esa misma sede. Si no explicitó y $incluirOtrasSedes=false, no asignar.
                $explicit = array_key_exists('caicedonia',$row) || array_key_exists('sevilla',$row);
                if ($explicit) {
                    if (empty($row[$sede])) continue;
                } else {
                    if (!$incluirOtrasSedes) continue;
                }

                if ($respetarLimite) {
                    $max = $maxPorSemana[$p->prioridad_orden] ?? 1;
                    $semanaCnt = (int) ($p->semana_asignados ?? 0);
                    if ($semanaCnt >= $max) continue;
                }

                // Evitar duplicado en esta sede/día
                $existe = CupoAsignacion::whereHas('cupo', function($q) use ($conv, $fecha, $sede) {
                        $q->where('convocatoria_id', $conv->id)
                          ->whereDate('fecha', $fecha->toDateString())
                          ->where('sede', $sede);
                    })
                    ->where('user_id', $p->user_id)
                    ->exists();
                if ($existe) continue;

                CupoAsignacion::create([
                    'cupo_diario_id' => $cupo->id,
                    'postulacion_id' => $p->id,
                    'user_id'        => $p->user_id,
                    'estado'         => 'asignado',
                    'asignado_en'    => now(),
                    'qr_token'       => bin2hex(random_bytes(16)),
                ]);
                $cupo->increment('asignados');
                $creados++;
            }
        });

        return back()->with('success', "Auto-asignados: {$creados}.");
    }

    private function preferenciasPorDiaCtl(PostulacionSubsidio $p): array
    {
        $raw = (array) ($p->preferencias_dias ?? []);
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

    private function parsePreferenciasDiasController(PostulacionSubsidio $p): array
    {
        $dias = ['lunes'=>1,'martes'=>2,'miercoles'=>3,'miércoles'=>3,'jueves'=>4,'viernes'=>5];
        $sedes = ['caicedonia','sevilla'];

        $candidatos = [];
        foreach (['preferencias_dias','pref_dias','dias_preferidos','dias','preferencias'] as $prop) {
            if (isset($p->{$prop})) $candidatos[] = $p->{$prop};
        }
        foreach (['respuestas_json','respuestas'] as $prop) {
            if (isset($p->{$prop})) $candidatos[] = $p->{$prop};
        }

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

        if (empty($data)) {
            $out = [];
            foreach ([1,2,3,4,5] as $dISO) {
                $out[$dISO] = ['caicedonia'=>true,'sevilla'=>true,'no'=>false];
            }
            return $out;
        }

        $flat = [];
        $flatten = function ($arr, $prefix='') use (&$flatten, &$flat) {
            foreach ($arr as $k=>$v) {
                $key = ($prefix === '' ? $k : $prefix.'.'.$k);
                if (is_array($v)) $flatten($v, $key);
                else $flat[$key] = $v;
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
            if (is_numeric($v)) return ((int)$v) === 1;
            $s = mb_strtolower(trim((string)$v));
            return in_array($s, ['1','true','si','sí','on','y','yes','x','✓','check','checked'], true);
        };

        $pref = [];
        foreach ($flat as $k => $v) {
            $nk = $normKey($k);
            $foundDia = null;
            foreach ($dias as $diaTxt => $dISO) {
                if (str_contains($nk, $diaTxt)) { $foundDia = $dISO; break; }
            }
            if (!$foundDia) continue;

            if (str_contains($nk, 'no_necesita') || str_contains($nk, 'no-necesita') || str_contains($nk, 'ninguno') || str_contains($nk, 'no')) {
                $pref[$foundDia]['no'] = $truthy($v);
            }

            foreach ($sedes as $s) {
                if (str_contains($nk, $s)) {
                    $pref[$foundDia][$s] = $truthy($v);
                }
            }
        }

        $out = [];
        foreach ([1,2,3,4,5] as $dISO) {
            $row = $pref[$dISO] ?? [];
            $no  = (bool)($row['no'] ?? false);
            $c = array_key_exists('caicedonia', $row) ? (bool)$row['caicedonia'] : !$no;
            $s = array_key_exists('sevilla', $row)    ? (bool)$row['sevilla']    : !$no;
            if ($no) { $c = false; $s = false; }
            $out[$dISO] = ['caicedonia'=>$c,'sevilla'=>$s,'no'=>$no];
        }

        return $out;
    }


    public function diaDefault(Request $request)
    {
        $tz = config('subsidio.timezone','America/Bogota');
        $conv = ConvocatoriaSubsidio::orderByDesc('created_at')->first();

        if (!$conv) {
            return redirect()->route('admin.subsidio.admin.dashboard')
                ->with('error','No hay convocatorias de subsidio para gestionar.');
        }

        $fecha = now($tz)->toDateString();
        $sede  = $request->query('sede', 'caicedonia'); // cambia por 'sevilla' si prefieres

        return redirect()->route('admin.cupos.dia', [
            'convocatoria_id' => $conv->id,
            'fecha'           => $fecha,
            'sede'            => $sede,
        ]);
    }


    public function reponerOfertas(Request $request, \App\Services\StandbyOfferService $svc)
{
    $data = $request->validate([
        'cupo_diario_id' => ['required','integer','exists:subsidio_cupos_diarios,id'],
    ]);

    $cupo = \App\Models\CupoDiario::findOrFail($data['cupo_diario_id']);

    // 1) Caducar pendientes vencidas para no bloquear reenvíos
    $svc->expirePendientesVencidas($cupo);

    // 2) Vacantes por ocupación activa (NO cancelados)
    $activos = \App\Models\CupoAsignacion::where('cupo_diario_id', $cupo->id)
        ->where(function($q){
            $q->whereNull('asistencia_estado')
              ->orWhere('asistencia_estado','!=','cancelado');
        })
        ->count();

    $vac = max(0, (int)$cupo->capacidad - (int)$activos);
    if ($vac <= 0) {
        return back()->with('success','No hay vacantes para reponer.');
    }

    // 3) Emitir lotes
    $parallel = (int) config('subsidio.standby_parallel_offers', 5);
    $ttlMin   = (int) config('subsidio.oferta_ttl_min', 10);

    $creadas = $svc->emitirOfertasPorVacantes($cupo, $vac, $parallel, $ttlMin);

    return back()->with('success', "Ofertas emitidas: {$creadas}.");
}
}