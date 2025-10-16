<?php

namespace App\Http\Controllers;

use App\Exports\AsistenciasMensualExport;
use App\Exports\AsistenciasSemanaExport;
use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use App\Models\ConvocatoriaSubsidio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AdminAsistenciasController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:AdminBienestar']);
    }

    // HUB
    public function index(Request $request)
    {
        $tz   = config('subsidio.timezone', 'America/Bogota');
        $hoy  = now($tz)->toDateString();

        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);
        $convId = $request->input('convocatoria_id');

        $items = CupoAsignacion::with('cupo')
            ->whereHas('cupo', function($q) use ($hoy, $convId) {
                $q->whereDate('fecha', $hoy)->whereRaw('WEEKDAY(fecha) <= 4');
                if ($convId) $q->where('convocatoria_id', $convId);
            })
            ->get();

        $totales = $this->totalesEstados($items);

        return view('roles.adminbienestar.asistencias.index', compact('hoy','totales','convocatorias') + [
            'convocatoriaId' => $convId,
        ]);
    }

    // Diario
    public function diario(Request $request)
    {
        $tz    = config('subsidio.timezone', 'America/Bogota');
        $fecha = Carbon::parse($request->input('fecha', now($tz)->toDateString()), $tz)->startOfDay();
        $sede  = $request->input('sede');
        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);
        $convId = $request->input('convocatoria_id');

        $items = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($fecha, $sede, $convId) {
                $q->whereDate('fecha', $fecha->toDateString())->whereRaw('WEEKDAY(fecha) <= 4');
                if ($sede)   $q->where('sede', $sede);
                if ($convId) $q->where('convocatoria_id', $convId);
            })
            ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->get();

        $totales = $this->totalesEstados($items);

        return view('roles.adminbienestar.asistencias.diario', compact('items','fecha','sede','totales','convocatorias') + [
            'convocatoriaId' => $convId,
        ]);
    }

    // Semanal — matriz L–V
    public function semanal(Request $request)
    {
        $tz = config('subsidio.timezone', 'America/Bogota');
        $lunes   = Carbon::parse($request->input('semana', now($tz)->toDateString()), $tz)->startOfWeek(Carbon::MONDAY);
        $domingo = $lunes->copy()->addDays(6);
        $sede    = $request->input('sede');
        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);
        $convId = $request->input('convocatoria_id');

        $dias = collect(range(0,4))->map(fn($d)=> $lunes->copy()->addDays($d))->values();

        $asigs = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($lunes, $domingo, $sede, $convId) {
                $q->whereBetween('fecha', [$lunes->toDateString(), $domingo->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) BETWEEN 0 AND 4');
                if ($sede)   $q->where('sede', $sede);
                if ($convId) $q->where('convocatoria_id', $convId);
            })
            ->orderBy(User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->get();

        $rows = [];
        foreach ($asigs as $a) {
            $uid = $a->user?->id ?? 0;
            if (!isset($rows[$uid])) {
                $rows[$uid] = [
                    'nombre'  => $a->user?->name ?? '—',
                    'email'   => $a->user?->email ?? '—',
                    'dias'    => []
                ];
            }
            $f = optional($a->cupo?->fecha)?->toDateString();
            if ($f) {
                // festivo tiene prioridad
                $rows[$uid]['dias'][$f] = !empty($a->cupo?->es_festivo)
                    ? 'festivo'
                    : $this->estadoNorm($a->asistencia_estado ?? 'pendiente');
            }
        }

        $asigRows = array_values($rows);
        usort($asigRows, static function (array $x, array $y) {
            return strcasecmp((string)($x['nombre'] ?? ''), (string)($y['nombre'] ?? ''));
        });

        return view('roles.adminbienestar.asistencias.semanal', [
            'lunes'          => $lunes,
            'domingo'        => $domingo,
            'sede'           => $sede,
            'dias'           => $dias,
            'rows'           => $asigRows,
            'convocatorias'  => $convocatorias,
            'convocatoriaId' => $convId,
        ]);
    }

    // Mensual — totales por día y estado dentro de un mes (L–V)
    public function mensual(Request $request)
    {
        $tz    = config('subsidio.timezone', 'America/Bogota');
        $mes   = $request->input('mes', now($tz)->toDateString());
        $sede  = $request->input('sede');
        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);
        $convId = $request->input('convocatoria_id');

        $inicio = Carbon::parse(substr($mes,0,7).'-01', $tz)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        $weeks = [];
        $wCursor = $inicio->copy()->startOfWeek(Carbon::MONDAY);
        for ($i=0; $i<6 && $wCursor->lte($fin); $i++, $wCursor->addWeek()) {
            $wStart = $wCursor->copy();
            $wEnd   = $wCursor->copy()->addDays(6);
            if ($wStart->gt($fin) && $wEnd->gt($fin)) break;

            $dias = collect(range(0,4))->map(fn($d)=> $wStart->copy()->addDays($d))->values();

            $items = CupoAsignacion::with(['user','cupo'])
                ->whereHas('cupo', function($q) use ($wStart,$wEnd,$sede,$convId) {
                    $q->whereBetween('fecha', [$wStart->toDateString(), $wEnd->toDateString()])
                      ->whereRaw('WEEKDAY(fecha) BETWEEN 0 AND 4');
                    if ($sede)   $q->where('sede', $sede);
                    if ($convId) $q->where('convocatoria_id', $convId);
                })
                ->get();

            $rows = [];
            foreach ($items as $a) {
                $uid = $a->user?->id ?? 0;
                if (!isset($rows[$uid])) {
                    $rows[$uid] = [
                        'nombre' => $a->user?->name ?? '—',
                        'email'  => $a->user?->email ?? '—',
                        'dias'   => []
                    ];
                }
                $f = optional($a->cupo?->fecha)?->toDateString();
                if ($f) $rows[$uid]['dias'][$f] = !empty($a->cupo?->es_festivo)
                    ? 'festivo'
                    : $this->estadoNorm($a->asistencia_estado ?? 'pendiente');
            }

            usort($rows, static function (array $x, array $y) {
                return strcasecmp((string)($x['nombre'] ?? ''), (string)($y['nombre'] ?? ''));
            });

            $weeks[] = [
                'label' => 'Semana '.($i+1),
                'inicio'=> $wStart,
                'fin'   => $wEnd,
                'dias'  => $dias,
                'rows'  => $rows,
            ];
        }

        $tituloMes = $inicio->locale('es')->isoFormat('MMMM YYYY');

        return view('roles.adminbienestar.asistencias.mensual', [
            'mes'            => $mes,
            'inicio'         => $inicio,
            'fin'            => $fin,
            'sede'           => $sede,
            'weeks'          => $weeks,
            'tituloMes'      => $tituloMes,
            'convocatorias'  => $convocatorias,
            'convocatoriaId' => $convId,
        ]);
    }

    public function cancelaciones(Request $request)
    {
        $tz = config('subsidio.timezone', 'America/Bogota');
        $fecha = $request->input('fecha') ? Carbon::parse($request->input('fecha'), $tz) : null;
        $sede  = $request->input('sede');
        $convId= $request->input('convocatoria_id');

        $query = \App\Models\CupoAsignacion::with(['user','cupo'])
            ->where('asistencia_estado','cancelado')
            ->whereHas('cupo', function($q) use ($fecha, $sede, $convId) {
                if ($fecha) $q->whereDate('fecha', $fecha->toDateString());
                if ($sede) $q->where('sede', $sede);
                if ($convId) $q->where('convocatoria_id', $convId);
            })
            ->orderByDesc('updated_at');

        $cancelaciones = $query->paginate(50);

        return view('roles.adminbienestar.asistencias.cancelaciones', compact('cancelaciones','fecha','sede','convId'));
    }

    // Exportar semana (matriz y colores)
    public function exportSemanalExcel(Request $request)
    {
        $tz     = config('subsidio.timezone', 'America/Bogota');
        $ref    = $request->input('lunes') ?? $request->input('semana') ?? now($tz)->toDateString();
        $lunes  = Carbon::parse($ref, $tz)->startOfWeek(Carbon::MONDAY);
        $sede   = $request->input('sede');
        $convId = $request->input('convocatoria_id');

        $rangIni = $lunes->copy();
        $rangFin = $lunes->copy()->addDays(6);

        $porSede = [];
        $alumnosSemana = [];
        $userIds = [];

        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($rangIni, $rangFin, $sede, $convId) {
                $q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()]);
                if ($sede)   $q->where('sede',$sede);
                if ($convId) $q->where('convocatoria_id',$convId);
            })
            ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'));

        $query->chunk(1000, function($chunk) use (&$porSede,&$alumnosSemana,&$userIds,$tz){
            foreach ($chunk as $a) {
                $fecha = $a->cupo?->fecha ? Carbon::parse($a->cupo->fecha, $tz) : null;
                if (!$fecha) continue;
                $dowIso = $fecha->dayOfWeekIso; if ($dowIso > 5) continue;

                $estado = $a->asistencia_estado ?? 'pendiente';
                if ($estado === 'no_show') $estado = 'inasistencia';
                if (!empty($a->cupo?->es_festivo)) $estado = 'festivo';

                $sedeKey = strtolower($a->cupo?->sede ?? 'sin_sede');
                $name    = $a->user?->name ?? '(sin nombre)';

                $porSede[$sedeKey][$dowIso] = $porSede[$sedeKey][$dowIso] ?? [];
                $porSede[$sedeKey][$dowIso][] = "{$name} ({$estado})";

                if ($a->user) {
                    $uid = $a->user->id;
                    $userIds[$uid] = true;
                    if (!isset($alumnosSemana[$uid])) {
                        $alumnosSemana[$uid] = [
                            'name'=>$name, 'email'=>$a->user->email ?? null,
                            'codigo'=>null,'programa'=>null,'numero'=>null,'sedes'=>[],
                        ];
                    }
                    $alumnosSemana[$uid]['sedes'][$sedeKey] = true;
                }
            }
        });

        if (!empty($userIds)) {
            $extra = $this->fetchEncuestaDatos(array_keys($userIds), $convId);
            foreach ($extra as $uid=>$info) {
                if (isset($alumnosSemana[$uid])) {
                    $alumnosSemana[$uid]['codigo']   = $alumnosSemana[$uid]['codigo']   ?? ($info['codigo']   ?? null);
                    $alumnosSemana[$uid]['programa'] = $alumnosSemana[$uid]['programa'] ?? ($info['programa'] ?? null);
                    $alumnosSemana[$uid]['numero']   = $alumnosSemana[$uid]['numero']   ?? ($info['numero']   ?? null);
                }
            }
        }

        foreach ($porSede as $sd=>&$map) for($d=1;$d<=5;$d++){ $map[$d] = $map[$d] ?? []; sort($map[$d], SORT_NATURAL|SORT_FLAG_CASE); }
        unset($map);

        $alumnos = [];
        foreach ($alumnosSemana as $uid=>$v) {
            $v['sedes'] = implode(', ', array_map('ucfirst', array_keys($v['sedes'])));
            $alumnos[] = $v;
        }
        usort($alumnos, fn($a,$b)=> strcasecmp($a['name'] ?? '', $b['name'] ?? ''));

        $filename = 'asistencias_semana_'.$rangIni->format('Ymd').'.xlsx';
        return Excel::download(new AsistenciasSemanaExport($lunes,$rangIni,$rangFin,$porSede,$alumnos), $filename);
    }

    public function exportMensualExcel(Request $request)
    {
        $tz    = config('subsidio.timezone', 'America/Bogota');
        $mesIn = $request->input('mes', now($tz)->format('Y-m'));
        $sede  = $request->input('sede');
        $convId= $request->input('convocatoria_id');

        $inicioMes = Carbon::parse(preg_match('/^\d{4}-\d{2}$/',$mesIn) ? ($mesIn.'-01') : $mesIn, $tz)->startOfMonth();
        $finMes    = $inicioMes->copy()->endOfMonth();

        $lunes = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $weeksData = [];

        for ($i=0; $i<6 && $lunes->lte($finMes); $i++, $lunes->addWeek()) {
            $rangIni = $lunes->copy();
            $rangFin = $lunes->copy()->addDays(6);

            $porSede = []; $alumnosSemana=[]; $userIds=[];

            $query = CupoAsignacion::with(['user','cupo'])
                ->whereHas('cupo', function($q) use ($rangIni,$rangFin,$sede,$convId){
                    $q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()]);
                    if ($sede)   $q->where('sede',$sede);
                    if ($convId) $q->where('convocatoria_id',$convId);
                })
                ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
                ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
                ->orderBy(User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'));

            $query->chunk(1000, function($chunk) use (&$porSede,&$alumnosSemana,&$userIds,$tz){
                foreach ($chunk as $a) {
                    $fecha = $a->cupo?->fecha ? Carbon::parse($a->cupo->fecha, $tz) : null;
                    if (!$fecha) continue;
                    $dowIso = $fecha->dayOfWeekIso; if ($dowIso>5) continue;

                    $estado = $a->asistencia_estado ?? 'pendiente';
                    if ($estado==='no_show') $estado='inasistencia';
                    if (!empty($a->cupo?->es_festivo)) $estado='festivo';

                    $sedeKey = strtolower($a->cupo?->sede ?? 'sin_sede');
                    $name    = $a->user?->name ?? '(sin nombre)';

                    $porSede[$sedeKey][$dowIso] = $porSede[$sedeKey][$dowIso] ?? [];
                    $porSede[$sedeKey][$dowIso][] = "{$name} ({$estado})";

                    if ($a->user) {
                        $uid = $a->user->id;
                        $userIds[$uid]=true;
                        if (!isset($alumnosSemana[$uid])) $alumnosSemana[$uid]=[
                            'name'=>$name,'email'=>$a->user->email ?? null,
                            'codigo'=>null,'programa'=>null,'numero'=>null,'sedes'=>[],
                        ];
                        $alumnosSemana[$uid]['sedes'][$sedeKey]=true;
                    }
                }
            });

            foreach ($porSede as $sd=>&$map) for($d=1;$d<=5;$d++){ $map[$d]=$map[$d]??[]; sort($map[$d],SORT_NATURAL|SORT_FLAG_CASE); }
            unset($map);

            // completar ficha con encuesta (AQUI la mejora que faltaba)
            if (!empty($userIds)) {
                $extra = $this->fetchEncuestaDatos(array_keys($userIds), $convId);
                foreach ($extra as $uid=>$info) {
                    if (isset($alumnosSemana[$uid])) {
                        $alumnosSemana[$uid]['codigo']   = $alumnosSemana[$uid]['codigo']   ?? ($info['codigo']   ?? null);
                        $alumnosSemana[$uid]['programa'] = $alumnosSemana[$uid]['programa'] ?? ($info['programa'] ?? null);
                        $alumnosSemana[$uid]['numero']   = $alumnosSemana[$uid]['numero']   ?? ($info['numero']   ?? null);
                    }
                }
            }

            $alumnos = [];
            foreach ($alumnosSemana as $uid=>$v) {
                $v['sedes'] = implode(', ', array_map('ucfirst', array_keys($v['sedes'])));
                $alumnos[] = $v;
            }
            usort($alumnos, fn($a,$b)=> strcasecmp($a['name'] ?? '', $b['name'] ?? ''));

            $weeksData[] = [
                'lunes'   => $rangIni->copy(),
                'rangIni' => $rangIni,
                'rangFin' => $rangFin,
                'porSede' => $porSede,
                'alumnos' => $alumnos,
            ];
        }

        $filename = 'asistencias_mes_'.$inicioMes->format('Ym').'.xlsx';
        return Excel::download(new AsistenciasMensualExport($weeksData), $filename);
    }

    // Helpers
    private function estadoNorm(string $estado): string
    {
        return $estado === 'no_show' ? 'inasistencia' : ($estado ?: 'pendiente');
    }

    private function totalesEstados($items): array
    {
        $counts = ['pendiente'=>0,'cancelado'=>0,'asistio'=>0,'inasistencia'=>0,'festivo'=>0];
        foreach ($items as $a) {
            $e = !empty($a->cupo?->es_festivo) ? 'festivo' : $this->estadoNorm($a->asistencia_estado ?? 'pendiente');
            if (!isset($counts[$e])) $counts[$e] = 0;
            $counts[$e]++;
        }
        return $counts;
    }

    // Opcional: ficha encuesta (código, programa, telefono)
    private function fetchEncuestaDatos(array $userIds, ?int $convId): array
    {
        if (empty($userIds)) return [];

        $titles = [
            'codigo'   => 'NÚMERO DE CÓDIGO (COMPLETO)',
            'programa' => 'PROGRAMA AL QUE PERTENECE',
            'telefono' => 'NÚMERO TELÉFONO DE CONTACTO',
        ];

        $pregs = DB::table('subsidio_preguntas')->select('id','titulo')
            ->whereIn('titulo', array_values($titles))->get()->pluck('id','titulo');

        $qidCodigo   = $pregs[$titles['codigo']]   ?? 3;
        $qidPrograma = $pregs[$titles['programa']] ?? 6;
        $qidTelefono = $pregs[$titles['telefono']] ?? 4;

        $postQuery = DB::table('subsidio_postulaciones as p')
            ->select('p.id','p.user_id')
            ->when($convId, fn($q)=>$q->where('p.convocatoria_id',$convId))
            ->whereIn('p.user_id',$userIds);

        $rows = DB::table(DB::raw("({$postQuery->toSql()}) as p"))
            ->mergeBindings($postQuery)
            ->select('p.user_id', DB::raw('MAX(p.id) as id'))
            ->groupBy('p.user_id')->get();

        $postIds=[]; foreach ($rows as $r) $postIds[(int)$r->id]=(int)$r->user_id;
        if (empty($postIds)) return [];

        $resps = DB::table('subsidio_respuestas as r')
            ->leftJoin('subsidio_opciones as o','o.id','=','r.opcion_id')
            ->whereIn('r.postulacion_id', array_keys($postIds))
            ->whereIn('r.pregunta_id', [$qidCodigo, $qidPrograma, $qidTelefono])
            ->select('r.postulacion_id','r.pregunta_id','r.respuesta_texto','r.respuesta_numero','o.texto as opcion_texto')
            ->get();

        $out=[]; foreach ($postIds as $postId=>$uid) $out[$uid]=['codigo'=>null,'programa'=>null,'numero'=>null];
        foreach ($resps as $r) {
            $uid = $postIds[(int)$r->postulacion_id] ?? null; if (!$uid) continue;
            if ((int)$r->pregunta_id === (int)$qidCodigo) {
                $out[$uid]['codigo'] = $out[$uid]['codigo'] ?? ($r->respuesta_texto ?? (string)$r->respuesta_numero ?? null);
            } elseif ((int)$r->pregunta_id === (int)$qidPrograma) {
                $val = $r->opcion_texto ?? $r->respuesta_texto ?? null;
                $out[$uid]['programa'] = $out[$uid]['programa'] ?? $val;
            } elseif ((int)$r->pregunta_id === (int)$qidTelefono) {
                $out[$uid]['numero'] = $out[$uid]['numero'] ?? ($r->respuesta_texto ?? (string)$r->respuesta_numero ?? null);
            }
        }
        return $out;
    }
}