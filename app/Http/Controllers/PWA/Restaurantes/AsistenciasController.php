<?php

namespace App\Http\Controllers\PWA\Restaurantes;

use App\Http\Controllers\Controller;
use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AsistenciasSemanaExport;

class AsistenciasController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Restaurante']);
    }

    /* ============= Helpers ============= */

    protected function tz(): string
    {
        return config('subsidio.timezone', config('app.timezone', 'America/Bogota'));
    }

    protected function allowedSedes(): array
    {
        if (!method_exists(auth()->user(),'restaurantes')) return [];
        return auth()->user()
            ->restaurantes()
            ->pluck('codigo')
            ->map(fn($c)=> strtolower(trim($c)))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeEstado(CupoAsignacion $a): void
    {
        if ($a->asistencia_estado === 'no_show') {
            $a->asistencia_estado = 'inasistencia';
        }
    }

    private function applyContextFilters($query, ?string $sedeSel, ?int $convId, array $allowed)
    {
        $query->whereHas('cupo', function($q) use ($sedeSel,$convId,$allowed){
            $q->whereIn('sede',$allowed);
            if ($sedeSel) $q->where('sede',$sedeSel);
            if ($convId) $q->where('convocatoria_id',$convId);
        });
    }

    private function currentContext(): array
    {
        $allowed = $this->allowedSedes();
        $sede = session('restaurante_codigo');
        if ($sede && !in_array($sede,$allowed,true)) {
            $sede = null;
            session()->forget('restaurante_codigo');
        }
        $conv = session('restaurante_convocatoria_id');
        return [$allowed,$sede,$conv];
    }

    private function logDebug(string $event, array $payload = []): void
    {
        Log::info('[REST-MARCA] '.$event, $payload + [
            'user_id' => auth()->id(),
            'rol'     => auth()->user()->rol ?? null,
            'ip'      => request()->ip(),
        ]);
    }

    /* ============= HOY ============= */

    public function hoy(Request $request)
    {
        $tz   = $this->tz();
        $hoy  = now($tz)->toDateString();
        $corte= Carbon::parse($hoy.' '.config('subsidio.hora_corte_marcaje','15:00'), $tz);

        [$allowed,$sedeSel,$convId] = $this->currentContext();

        if (empty($allowed)) {
            return view('pwa.restaurantes.asistencias.hoy', [
                'hoy'=>$hoy,'corte'=>$corte,
                'pendientes'=>collect(),'asistidas'=>collect(),'canceladas'=>collect(),'inasistencias'=>collect(),
                'mensaje'=>'No tienes sedes asignadas.'
            ]);
        }

        $base = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', fn($q)=>$q->whereDate('fecha',$hoy));
        $this->applyContextFilters($base,$sedeSel,$convId,$allowed);

        $items = $base
            ->orderBy(CupoDiario::select('sede')
                ->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(User::select('name')
                ->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->get();

        $items->each(fn($a)=>$this->normalizeEstado($a));

        $pendientes    = $items->where('asistencia_estado','pendiente')->values();
        $asistidas     = $items->where('asistencia_estado','asistio')->values();
        $canceladas    = $items->where('asistencia_estado','cancelado')->values();
        $inasistencias = $items->where('asistencia_estado','inasistencia')->values();

        return view('pwa.restaurantes.asistencias.hoy', [
            'hoy'=>$hoy,
            'corte'=>$corte,
            'pendientes'=>$pendientes,
            'asistidas'=>$asistidas,
            'canceladas'=>$canceladas,
            'inasistencias'=>$inasistencias,
            'mensaje'=>(!$sedeSel?'Sin sede filtrada (todas)':null),
        ]);
    }

    /* ============= MARCAR (día actual) ============= */

    public function marcar(Request $request, CupoAsignacion $asignacion)
    {
        $request->validate([
            'accion' => 'required|in:asistio,pendiente,inasistencia'
        ]);

        $tz   = $this->tz();
        $hoy  = now($tz)->toDateString();

        $asignacion->load('cupo','user');

        if (! $asignacion->cupo) {
            $this->logDebug('marcar.fail.sin_cupo', ['asignacion_id'=>$asignacion->id]);
            abort(403,'Asignación sin cupo');
        }

        $fechaAsignacion = optional($asignacion->cupo->fecha)->toDateString();
        if ($fechaAsignacion !== $hoy) {
            $this->logDebug('marcar.fail.fecha_distinta', [
                'asignacion_id'=>$asignacion->id,
                'fecha_asignacion'=>$fechaAsignacion,
                'hoy'=>$hoy
            ]);
            abort(403,'No es el día actual ('.$fechaAsignacion.' != '.$hoy.')');
        }

        if ($asignacion->asistencia_estado === 'cancelado') {
            $this->logDebug('marcar.fail.cancelado', ['asignacion_id'=>$asignacion->id]);
            abort(403,'Cupo cancelado no editable.');
        }

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        if (empty($allowed)) {
            $this->logDebug('marcar.fail.sin_sedes', ['asignacion_id'=>$asignacion->id]);
            abort(403,'No tienes sedes asignadas.');
        }

        $sedeCupo = $asignacion->cupo->sede;
        if (!in_array($sedeCupo,$allowed,true)) {
            $this->logDebug('marcar.fail.sede_no_permitida', [
                'asignacion_id'=>$asignacion->id,
                'sede_cupo'=>$sedeCupo,
                'allowed'=>$allowed
            ]);
            abort(403,'Sede no permitida: '.$sedeCupo);
        }

        $accion = $request->accion;
        $this->logDebug('marcar.try', [
            'asignacion_id'=>$asignacion->id,
            'accion'=>$accion,
            'estado_actual'=>$asignacion->asistencia_estado
        ]);

        if ($accion === 'asistio') {
            $asignacion->asistencia_estado = 'asistio';
            $asignacion->asistencia_marcada_en = now($tz);
            $asignacion->asistencia_marcada_por_user_id = auth()->id();
        } elseif ($accion === 'pendiente') {
            $asignacion->asistencia_estado = 'pendiente';
            $asignacion->asistencia_marcada_en = null;
            $asignacion->asistencia_marcada_por_user_id = null;
        } else { // inasistencia
            $asignacion->asistencia_estado = 'inasistencia';
            $asignacion->asistencia_marcada_en = null;
            $asignacion->asistencia_marcada_por_user_id = null;
        }

        $asignacion->save();

        $this->logDebug('marcar.ok', [
            'asignacion_id'=>$asignacion->id,
            'nuevo_estado'=>$asignacion->asistencia_estado
        ]);

        return back()->with('success','Estado actualizado.');
    }

    /* ============= FECHA (histórico / editable si hoy) ============= */

    public function marcarFecha(Request $request, CupoAsignacion $asignacion)
    {
        $request->validate([
            'accion' => 'required|in:asistio,pendiente,inasistencia'
        ]);

        $tz = $this->tz();

        $asignacion->load('cupo','user');

        if (!$asignacion->cupo) {
            $this->logDebug('marcar_fecha.fail.sin_cupo', ['asignacion_id'=>$asignacion->id]);
            abort(403,'Asignación sin cupo');
        }

        // Mantener restricción: no editable si está cancelado
        if ($asignacion->asistencia_estado === 'cancelado') {
            $this->logDebug('marcar_fecha.fail.cancelado', ['asignacion_id'=>$asignacion->id]);
            abort(403,'Cupo cancelado no editable.');
        }

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        if (empty($allowed)) {
            $this->logDebug('marcar_fecha.fail.sin_sedes', ['asignacion_id'=>$asignacion->id]);
            abort(403,'No tienes sedes asignadas.');
        }

        $sedeCupo = $asignacion->cupo->sede;
        if (!in_array($sedeCupo,$allowed,true)) {
            $this->logDebug('marcar_fecha.fail.sede_no_permitida', [
                'asignacion_id'=>$asignacion->id,
                'sede_cupo'=>$sedeCupo,
                'allowed'=>$allowed
            ]);
            abort(403,'Sede no permitida: '.$sedeCupo);
        }

        $accion = $request->accion;
        $this->logDebug('marcar_fecha.try', [
            'asignacion_id'=>$asignacion->id,
            'accion'=>$accion,
            'estado_actual'=>$asignacion->asistencia_estado
        ]);

        if ($accion === 'asistio') {
            $asignacion->asistencia_estado = 'asistio';
            $asignacion->asistencia_marcada_en = now($tz);
            $asignacion->asistencia_marcada_por_user_id = auth()->id();
        } elseif ($accion === 'pendiente') {
            $asignacion->asistencia_estado = 'pendiente';
            $asignacion->asistencia_marcada_en = null;
            $asignacion->asistencia_marcada_por_user_id = null;
        } else { // inasistencia
            $asignacion->asistencia_estado = 'inasistencia';
            $asignacion->asistencia_marcada_en = null;
            $asignacion->asistencia_marcada_por_user_id = null;
        }

        $asignacion->save();

        $this->logDebug('marcar_fecha.ok', [
            'asignacion_id'=>$asignacion->id,
            'nuevo_estado'=>$asignacion->asistencia_estado
        ]);

        return back()->with('success','Estado actualizado.');
    } 

     public function fecha(Request $request)
    {
        $tz = $this->tz();
        $fecha = Carbon::parse($request->input('fecha', now($tz)->toDateString()),$tz)->startOfDay();

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        if (empty($allowed)) {
            return view('pwa.restaurantes.asistencias.fecha', [
                'items'=>collect(),'fecha'=>$fecha,'editable'=>false,'mensaje'=>'No tienes sedes asignadas.'
            ]);
        }

        // Antes: solo editable si $fecha == hoy. Ahora permitimos corrección siempre:
        $editable = true;

        $base = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', fn($q)=>$q->whereDate('fecha',$fecha->toDateString()));
        $this->applyContextFilters($base,$sedeSel,$convId,$allowed);

        $items = $base
            ->orderBy(CupoDiario::select('sede')
                ->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(User::select('name')
                ->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->get();

        $items->each(fn($a)=>$this->normalizeEstado($a));

        return view('pwa.restaurantes.asistencias.fecha', [
            'items'=>$items,
            'fecha'=>$fecha,
            'editable'=>$editable,
            'mensaje'=>null
        ]);
    }

    /* ============= SEMANA ============= */

    public function semana(Request $request)
    {
        $tz    = $this->tz();
        $lunes = Carbon::parse($request->input('lunes', now($tz)->toDateString()),$tz)
            ->startOfWeek(Carbon::MONDAY);

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        if (empty($allowed)) {
            return view('pwa.restaurantes.asistencias.semana', [
                'itemsAgrupados'=>[],'lunes'=>$lunes,'resumen'=>[],'mensaje'=>'No tienes sedes asignadas.'
            ]);
        }

        $rangIni = $lunes->copy();
        $rangFin = $lunes->copy()->addDays(6);

        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($rangIni,$rangFin){
                $q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()]);
            });
        $this->applyContextFilters($query,$sedeSel,$convId,$allowed);

        $items = $query
            ->orderBy(CupoDiario::select('fecha')
                ->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('sede')
                ->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(User::select('name')
                ->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->get();

        $items->each(fn($a)=>$this->normalizeEstado($a));

        $itemsAgrupados = $items->groupBy(fn($a)=>$a->cupo?->fecha?->format('Y-m-d'));
        $resumen = $items->groupBy('asistencia_estado')->map->count();

        return view('pwa.restaurantes.asistencias.semana', compact('itemsAgrupados','lunes','resumen'));
    }

    public function exportSemana(Request $request)
    {
        $tz    = $this->tz();
        $lunes = Carbon::parse($request->input('lunes', now($tz)->toDateString()), $tz)
            ->startOfWeek(Carbon::MONDAY);

        [$allowed, $sedeSel, $convId] = $this->currentContext();
        abort_if(empty($allowed), 403);

        $rangIni = $lunes->copy();
        $rangFin = $lunes->copy()->addDays(6);

        // Opcional: autocerrar pendientes de días pasados
        if ($request->boolean('autocerrar')) {
            $this->autoCerrarRango($rangIni, $rangFin, $allowed, $sedeSel, $convId, $tz);
        }

        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function ($q) use ($rangIni, $rangFin) {
                $q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()])
                ->whereRaw('WEEKDAY(fecha) BETWEEN 0 AND 4');
            });

        $this->applyContextFilters($query, $sedeSel, $convId, $allowed);

        $porSede = [];
        $alumnosSemana = [];
        $userIds = [];

        $query->orderBy(CupoDiario::select('sede')
                    ->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('fecha')
                    ->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(User::select('name')
                    ->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->chunk(1000, function ($chunk) use (&$porSede, &$alumnosSemana, &$userIds, $tz) {
                    foreach ($chunk as $a) {
                        $fecha = $a->cupo?->fecha ? Carbon::parse($a->cupo->fecha, $tz) : null;
                        if (!$fecha) continue;
                        $dowIso = $fecha->dayOfWeekIso; // 1..7
                        if ($dowIso > 5) continue;

                        $estado = $a->asistencia_estado ?? 'pendiente';
                        if ($estado === 'no_show') $estado = 'inasistencia';

                        $sede   = strtolower($a->cupo?->sede ?? 'sin_sede');
                        $user   = $a->user;
                        $name   = $user?->name ?? '(sin nombre)';

                        $label = "{$name} ({$estado})";
                        $porSede[$sede][$dowIso][] = $label;

                        if ($user) {
                            $uid = $user->id;
                            $userIds[$uid] = true; // set

                            if (!isset($alumnosSemana[$uid])) {
                                $alumnosSemana[$uid] = [
                                    'name'     => $name,
                                    'email'    => $user->email ?? null,
                                    'codigo'   => null,
                                    'programa' => null,
                                    'numero'   => null,
                                    'sedes'    => [],
                                ];
                            }
                            $alumnosSemana[$uid]['sedes'][$sede] = true;
                        }
                    }
            });

        // Completar ficha con datos de la encuesta (por conv)
        $userIdsList = array_keys($userIds);
        if (!empty($userIdsList)) {
            $encuestaDatos = $this->fetchEncuestaDatos($userIdsList, $convId);
            foreach ($encuestaDatos as $uid => $info) {
                if (isset($alumnosSemana[$uid])) {
                    $alumnosSemana[$uid]['codigo']   = $info['codigo']   ?? $alumnosSemana[$uid]['codigo'];
                    $alumnosSemana[$uid]['programa'] = $info['programa'] ?? $alumnosSemana[$uid]['programa'];
                    $alumnosSemana[$uid]['numero']   = $info['numero']   ?? $alumnosSemana[$uid]['numero'];
                }
            }
        }

        foreach ($porSede as $sede => &$map) {
            for ($d=1; $d<=5; $d++) {
                $map[$d] = $map[$d] ?? [];
                sort($map[$d], SORT_NATURAL | SORT_FLAG_CASE);
            }
        }
        unset($map);

        $alumnos = collect($alumnosSemana)
            ->map(function ($v) {
                $v['sedes'] = implode(', ', array_map('ucfirst', array_keys($v['sedes'])));
                return $v;
            })
            ->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)
            ->values()
            ->all();

        $filename = 'asistencias_semana_'.$rangIni->format('Ymd').'.xlsx';

        return Excel::download(
            new \App\Exports\AsistenciasSemanaExport($lunes, $rangIni, $rangFin, $porSede, $alumnos),
            $filename
        );
    }

    // Helper: obtener Código, Programa y Número desde la última postulación del usuario (por conv)
    private function fetchEncuestaDatos(array $userIds, ?int $convId): array
{
    if (empty($userIds)) return [];

    // 0) Resolver pregunta_id por título (case-insensitive típico en MySQL).
    $titles = [
        'codigo'   => 'NÚMERO DE CÓDIGO (COMPLETO)',
        'programa' => 'PROGRAMA AL QUE PERTENECE',
        'telefono' => 'NÚMERO TELÉFONO DE CONTACTO',
    ];

    $pregs = DB::table('subsidio_preguntas')
        ->select('id','titulo')
        ->whereIn('titulo', array_values($titles))
        ->get()
        ->pluck('id','titulo');

    // Fallback por si cambian minúsculas/acentos o no se encuentra por título:
    // (Según tu captura, suelen ser 3=código, 6=programa, 4=teléfono)
    $qidCodigo   = $pregs[$titles['codigo']]   ?? 3;
    $qidPrograma = $pregs[$titles['programa']] ?? 6;
    $qidTelefono = $pregs[$titles['telefono']] ?? 4;

    // 1) última postulación por usuario (filtrada por convocatoria si se provee)
    $postQuery = DB::table('subsidio_postulaciones as p')
        ->select('p.id','p.user_id')
        ->when($convId, fn($q)=>$q->where('p.convocatoria_id',$convId))
        ->whereIn('p.user_id',$userIds);

    // Subconsulta para tomar la última por usuario (MAX id)
    $rows = DB::table(DB::raw("({$postQuery->toSql()}) as p"))
        ->mergeBindings($postQuery)
        ->select('p.user_id', DB::raw('MAX(p.id) as id'))
        ->groupBy('p.user_id')
        ->get();

    $postIds = [];
    foreach ($rows as $r) {
        $postIds[(int)$r->id] = (int)$r->user_id;
    }
    if (empty($postIds)) return [];

    // 2) Traer respuestas de esas postulaciones para esas preguntas
    //    Unimos a subsidio_opciones para obtener el texto del programa (selección única).
    $resps = DB::table('subsidio_respuestas as r')
        ->leftJoin('subsidio_opciones as o','o.id','=','r.opcion_id')
        ->whereIn('r.postulacion_id', array_keys($postIds))
        ->whereIn('r.pregunta_id', [$qidCodigo, $qidPrograma, $qidTelefono])
        ->select(
            'r.postulacion_id','r.pregunta_id',
            'r.respuesta_texto','r.respuesta_numero',
            'o.texto as opcion_texto'
        )
        ->get();

    $out = []; // user_id => ['codigo'=>..., 'programa'=>..., 'numero'=>telefono]
    foreach ($postIds as $postId => $uid) {
        $out[$uid] = ['codigo'=>null,'programa'=>null,'numero'=>null];
    }

    foreach ($resps as $r) {
        $uid = $postIds[(int)$r->postulacion_id] ?? null;
        if (!$uid) continue;

        if ((int)$r->pregunta_id === (int)$qidCodigo) {
            // Código viene en texto (según tu definición)
            $out[$uid]['codigo'] = $out[$uid]['codigo'] ?? ($r->respuesta_texto ?? (string)$r->respuesta_numero ?? null);
        } elseif ((int)$r->pregunta_id === (int)$qidPrograma) {
            // Programa normalmente es selección única: usamos texto de la opción.
            $val = $r->opcion_texto ?? $r->respuesta_texto ?? null;
            $out[$uid]['programa'] = $out[$uid]['programa'] ?? $val;
        } elseif ((int)$r->pregunta_id === (int)$qidTelefono) {
            // Teléfono (número) lo mapeamos al campo "numero" que usa la vista
            $val = $r->respuesta_texto ?? (string)$r->respuesta_numero ?? null;
            $out[$uid]['numero'] = $out[$uid]['numero'] ?? $val;
        }
    }

    return $out;
    }

    // (opcional) helper para autocierre en rango (ya te lo dejé antes)
    private function autoCerrarRango(\Carbon\Carbon $ini, \Carbon\Carbon $fin, array $allowed, ?string $sedeSel, ?int $convId, string $tz): int
    {
        $hoy = now($tz)->startOfDay();
        $from = $ini->copy()->startOfDay();
        $to   = $fin->copy()->endOfDay();
        if ($to->greaterThanOrEqualTo($hoy)) {
            $to = $hoy->copy()->subDay()->endOfDay();
        }
        if ($to->lessThan($from)) return 0;

        return DB::table('subsidio_cupo_asignaciones as a')
            ->join('subsidio_cupos_diarios as d','d.id','=','a.cupo_diario_id')
            ->when($convId, fn($qq)=>$qq->where('d.convocatoria_id',$convId))
            ->when($sedeSel, fn($qq)=>$qq->where('d.sede',$sedeSel), fn($qq)=>$qq->whereIn('d.sede',$allowed))
            ->whereBetween('d.fecha', [$from->toDateString(), $to->toDateString()])
            ->whereRaw('WEEKDAY(d.fecha) BETWEEN 0 AND 4')
            ->where(function($q){
                $q->whereNull('a.asistencia_estado')
                ->orWhere('a.asistencia_estado','pendiente');
            })
            ->update([
                'a.asistencia_estado'=>'inasistencia',
                'a.updated_at'=>now(),
            ]);
}
    /* ============= CERRAR DÍA ============= */

    public function cerrarDia(Request $request)
    {
        $tz    = $this->tz();
        $fecha = Carbon::parse($request->input('fecha', now($tz)->toDateString()),$tz)->toDateString();

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        abort_if(empty($allowed),403,'No tienes sedes asignadas.');

        if (!in_array(Carbon::parse($fecha,$tz)->dayOfWeekIso,[1,2,3,4,5],true)) {
            return back()->with('warning','No es día hábil.');
        }

        $afectados = DB::table('subsidio_cupo_asignaciones as a')
            ->join('subsidio_cupos_diarios as d','d.id','=','a.cupo_diario_id')
            ->when($convId, fn($qq)=>$qq->where('d.convocatoria_id',$convId))
            ->when($sedeSel, fn($qq)=>$qq->where('d.sede',$sedeSel), fn($qq)=>$qq->whereIn('d.sede',$allowed))
            ->whereDate('d.fecha',$fecha)
            ->where(function($q){
                $q->whereNull('a.asistencia_estado')
                  ->orWhere('a.asistencia_estado','pendiente');
            })
            ->update([
                'a.asistencia_estado'=>'inasistencia',
                'a.updated_at'=>now(),
            ]);

        $this->logDebug('cerrar_dia', ['fecha'=>$fecha,'afectados'=>$afectados]);

        return back()->with('success',"Cierre ejecutado. Pendientes → inasistencia: {$afectados}");
    }

    public function mes(Request $request)
    {
        $tz = $this->tz();

        // Param mes en formato YYYY-MM (o cualquier fecha del mes)
        $mesParam = $request->input('mes');
        try {
            if ($mesParam && preg_match('/^\d{4}-\d{2}$/', $mesParam)) {
                $base = Carbon::parse($mesParam.'-01', $tz);
            } else {
                $base = Carbon::parse($mesParam ?? now($tz)->toDateString(), $tz);
            }
        } catch (\Exception $e) {
            $base = now($tz);
        }

        $mes = $base->copy()->startOfMonth();
        $finMes = $base->copy()->endOfMonth();

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        if (empty($allowed)) {
            return view('pwa.restaurantes.asistencias.mes', [
                'mes'=>$mes,'semanas'=>[],'mensaje'=>'No tienes sedes asignadas.'
            ]);
        }

        // Encontrar el lunes anterior (o igual) al primer día del mes
        $lunesCursor = $mes->copy()->startOfWeek(Carbon::MONDAY);
        $semanas = [];

        // Iterar hasta que el lunes ya sea posterior al fin de mes (máximo 6 semanas)
        for ($i=0; $i<6; $i++) {
            $lunes = $lunesCursor->copy();
            $domingo = $lunes->copy()->addDays(6);

            // Si toda la semana está después del fin de mes y ya pasamos el fin, cortamos
            if ($lunes->greaterThan($finMes) && $domingo->greaterThan($finMes)) break;

            // Consultar resumen de esa semana (solo días hábiles Lun–Vie)
            $rangIni = $lunes->copy();
            $rangFin = $domingo->copy();

            $query = CupoAsignacion::with(['user','cupo'])
                ->whereHas('cupo', function($q) use ($rangIni, $rangFin) {
                    $q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()])
                      ->whereRaw('WEEKDAY(fecha) BETWEEN 0 AND 4');
                });
            $this->applyContextFilters($query,$sedeSel,$convId,$allowed);

            $items = $query->get();
            $items->each(fn($a)=>$this->normalizeEstado($a));

            $resumen = $items->groupBy('asistencia_estado')->map->count()->toArray();
            $total = $items->count();

            // Guardar info de la semana solo si cruza el mes seleccionado (para no mostrar la "semana anterior" completa si cae fuera)
            if ($lunes->lessThanOrEqualTo($finMes) && $domingo->greaterThanOrEqualTo($mes)) {
                $semanas[] = [
                    'lunes'   => $lunes->copy(),
                    'domingo' => $domingo->copy(),
                    'resumen' => $resumen,
                    'total'   => $total,
                ];
            }

            $lunesCursor->addWeek();
        }

        return view('pwa.restaurantes.asistencias.mes', compact('mes','semanas'));
    }
}