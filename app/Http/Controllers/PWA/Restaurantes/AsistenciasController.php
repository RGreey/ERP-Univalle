<?php

namespace App\Http\Controllers\PWA\Restaurantes;

use App\Exports\AsistenciasMensualExport;
use App\Exports\AsistenciasSemanaExport;
use App\Http\Controllers\Controller;
use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class AsistenciasController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Restaurante']);
    }

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
        if ($a->relationLoaded('cupo') && $a->cupo && !empty($a->cupo->es_festivo)) {
            $a->asistencia_estado = 'festivo';
            return;
        }
        if ($a->asistencia_estado === 'no_show') {
            $a->asistencia_estado = 'inasistencia';
        }
        if (!$a->asistencia_estado) {
            $a->asistencia_estado = 'pendiente';
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
        Log::info('[REST] '.$event, $payload + [
            'user_id' => auth()->id(),
            'ip'      => request()->ip(),
        ]);
    }

    /* ============= FESTIVOS ============= */
    public function marcarFestivo(Request $request)
    {
        $data = $request->validate([
            'fecha'  => 'required|date',
            'accion' => 'required|in:marcar,quitar',
            'motivo' => 'nullable|string|max:180',
        ]);

        $tz = $this->tz();
        $fecha = Carbon::parse($data['fecha'],$tz)->toDateString();

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        abort_if(empty($allowed), 403, 'No tienes sedes asignadas.');

        $upd = CupoDiario::query()
            ->when($convId, fn($q)=>$q->where('convocatoria_id',$convId))
            ->when($sedeSel, fn($q)=>$q->where('sede',$sedeSel), fn($q)=>$q->whereIn('sede',$allowed))
            ->whereDate('fecha', $fecha)
            ->update([
                'es_festivo'     => $data['accion']==='marcar',
                'festivo_motivo' => $data['accion']==='marcar' ? ($data['motivo'] ?? null) : null,
                'updated_at'     => now(),
            ]);

        $this->logDebug('festivo.update', [
            'fecha'=>$fecha, 'accion'=>$data['accion'], 'rows'=>$upd
        ]);

        return back()->with($upd ? 'success' : 'warning',
            $data['accion']==='marcar'
                ? "Día $fecha marcado como festivo."
                : ($upd ? "Día $fecha restaurado (quitado festivo)." : "No se encontraron cupos para actualizar en $fecha.")
        );
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
                'mensaje'=>'No tienes sedes asignadas.',
                'esFestivoDia'=>false,
            ]);
        }

        $base = CupoAsignacion::with(['user','cupo' => function($q){ $q->select('*'); }])
            ->whereHas('cupo', fn($q)=>$q->whereDate('fecha',$hoy));
        $this->applyContextFilters($base,$sedeSel,$convId,$allowed);

        $items = $base->get();
        $items->each(fn($a)=>$this->normalizeEstado($a));

        $pendientes   = $items->where('asistencia_estado','pendiente')->values();
        $asistidas    = $items->where('asistencia_estado','asistio')->values();
        $canceladas   = $items->where('asistencia_estado','cancelado')->values();
        $inasistencias= $items->where('asistencia_estado','inasistencia')->values();

        $esFestivoDia = CupoDiario::query()
            ->when($convId, fn($q)=>$q->where('convocatoria_id',$convId))
            ->when($sedeSel, fn($q)=>$q->where('sede',$sedeSel), fn($q)=>$q->whereIn('sede',$allowed))
            ->whereDate('fecha',$hoy)
            ->where('es_festivo', 1)
            ->exists();

        return view('pwa.restaurantes.asistencias.hoy', compact(
            'hoy','corte','pendientes','asistidas','canceladas','inasistencias','esFestivoDia'
        ));
    }

    /* ============= MARCAR (día actual) ============= */

    public function marcar(Request $request, CupoAsignacion $asignacion)
    {
        $request->validate(['accion' => 'required|in:asistio,pendiente,inasistencia']);

        $tz = $this->tz();
        $hoy = now($tz)->toDateString();

        $asignacion->load('cupo','user');
        if (! $asignacion->cupo) abort(403,'Asignación sin cupo');
        if (!empty($asignacion->cupo->es_festivo)) abort(403,'Día festivo: no editable.');

        $fechaAsignacion = optional($asignacion->cupo->fecha)->toDateString();
        if ($fechaAsignacion !== $hoy) abort(403,'No es el día actual ('.$fechaAsignacion.' != '.$hoy.')');

        if ($asignacion->asistencia_estado === 'cancelado') abort(403,'Cupo cancelado no editable.');

        [$allowed] = $this->currentContext();
        if (empty($allowed)) abort(403,'No tienes sedes asignadas.');
        if (!in_array($asignacion->cupo->sede,$allowed,true)) abort(403,'Sede no permitida: '.$asignacion->cupo->sede);

        $accion = $request->accion;
        if ($accion === 'asistio') {
            $asignacion->asistencia_estado = 'asistio';
            $asignacion->asistencia_marcada_en = now($tz);
            $asignacion->asistencia_marcada_por_user_id = auth()->id();
        } elseif ($accion === 'pendiente') {
            $asignacion->asistencia_estado = 'pendiente';
            $asignacion->asistencia_marcada_en = null;
            $asignacion->asistencia_marcada_por_user_id = null;
        } else {
            $asignacion->asistencia_estado = 'inasistencia';
            $asignacion->asistencia_marcada_en = null;
            $asignacion->asistencia_marcada_por_user_id = null;
        }
        $asignacion->save();

        return back()->with('success','Estado actualizado.');
    }

    /* ============= MARCAR FECHA (histórico / editable) ============= */

    public function marcarFecha(Request $request, CupoAsignacion $asignacion)
    {
        $request->validate(['accion' => 'required|in:asistio,pendiente,inasistencia']);

        $tz = $this->tz();
        $asignacion->load('cupo','user');

        if (!$asignacion->cupo) abort(403,'Asignación sin cupo');
        if (!empty($asignacion->cupo->es_festivo)) abort(403,'Día festivo: no editable.');
        if ($asignacion->asistencia_estado === 'cancelado') abort(403,'Cupo cancelado no editable.');

        [$allowed] = $this->currentContext();
        if (empty($allowed)) abort(403,'No tienes sedes asignadas.');
        if (!in_array($asignacion->cupo->sede,$allowed,true)) abort(403,'Sede no permitida: '.$asignacion->cupo->sede);

        $accion = $request->accion;
        if ($accion === 'asistio') {
            $asignacion->asistencia_estado = 'asistio';
            $asignacion->asistencia_marcada_en = now($this->tz());
            $asignacion->asistencia_marcada_por_user_id = auth()->id();
        } elseif ($accion === 'pendiente') {
            $asignacion->asistencia_estado = 'pendiente';
            $asignacion->asistencia_marcada_en = null;
            $asignacion->asistencia_marcada_por_user_id = null;
        } else {
            $asignacion->asistencia_estado = 'inasistencia';
            $asignacion->asistencia_marcada_en = null;
            $asignacion->asistencia_marcada_por_user_id = null;
        }
        $asignacion->save();

        return back()->with('success','Estado actualizado.');
    }

    /* ============= FECHA (vista) ============= */

    public function fecha(Request $request)
    {
        $tz = $this->tz();
        $fecha = Carbon::parse($request->input('fecha', now($tz)->toDateString()),$tz)->startOfDay();

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        if (empty($allowed)) {
            return view('pwa.restaurantes.asistencias.fecha', [
                'items'=>collect(),'fecha'=>$fecha,'editable'=>false,'mensaje'=>'No tienes sedes asignadas.',
                'esFestivoDia'=>false,
            ]);
        }

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

        $esFestivoDia = CupoDiario::query()
            ->when($convId, fn($q)=>$q->where('convocatoria_id',$convId))
            ->when($sedeSel, fn($q)=>$q->where('sede',$sedeSel), fn($q)=>$q->whereIn('sede',$allowed))
            ->whereDate('fecha',$fecha->toDateString())
            ->where('es_festivo', 1)
            ->exists();

        return view('pwa.restaurantes.asistencias.fecha', [
            'items'=>$items,'fecha'=>$fecha,'editable'=>$editable,'mensaje'=>null,'esFestivoDia'=>$esFestivoDia
        ]);
    }

    /* ============= SEMANA (vista) ============= */

    public function semana(Request $request)
    {
        $tz    = $this->tz();
        $lunes = Carbon::parse($request->input('lunes', now($tz)->toDateString()),$tz)
            ->startOfWeek(Carbon::MONDAY);

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        if (empty($allowed)) {
            return view('pwa.restaurantes.asistencias.semana', [
                'itemsAgrupados'=>[],'lunes'=>$lunes,'mensaje'=>'No tienes sedes asignadas.',
                'resumen'=>[], 'festivos'=>[]
            ]);
        }

        $rangIni = $lunes->copy();
        $rangFin = $lunes->copy()->addDays(6);

        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', fn($q)=>$q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()]));
        $this->applyContextFilters($query,$sedeSel,$convId,$allowed);

        $rows = [];
        $festivos = [];

        $query->chunk(1000, function($chunk) use (&$rows,&$festivos,$tz){
            foreach ($chunk as $a) {
                $this->normalizeEstado($a);

                $fechaRaw = $a->cupo?->fecha;
                $fechaKey = $fechaRaw ? Carbon::parse($fechaRaw, $tz)->toDateString() : null;
                if (!$fechaKey) continue;

                $rows[$fechaKey]   = $rows[$fechaKey]   ?? [];
                $rows[$fechaKey][] = $a;

                if (!empty($a->cupo?->es_festivo)) $festivos[$fechaKey] = true;
                else $festivos[$fechaKey] = $festivos[$fechaKey] ?? false;
            }
        });

        ksort($rows);
        $resumen = [];
        foreach ($rows as $lista) {
            foreach ($lista as $a) {
                $resumen[$a->asistencia_estado] = ($resumen[$a->asistencia_estado] ?? 0) + 1;
            }
        }

        return view('pwa.restaurantes.asistencias.semana', [
            'itemsAgrupados'=>$rows,'lunes'=>$lunes,'resumen'=>$resumen,'mensaje'=>null,'festivos'=>$festivos
        ]);
    }

    /* ============= EXPORTAR SEMANA (Excel) ============= */

    public function exportSemana(Request $request)
    {
        $tz    = $this->tz();
        $lunes = Carbon::parse($request->input('lunes', now($tz)->toDateString()),$tz)->startOfWeek(Carbon::MONDAY);

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        abort_if(empty($allowed),403);

        $rangIni = $lunes->copy();
        $rangFin = $lunes->copy()->addDays(6);

        $porSede = [];
        $alumnosSemana = [];
        $userIds = [];

        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($rangIni, $rangFin) {
                $q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()]);
            });
        $this->applyContextFilters($query,$sedeSel,$convId,$allowed);

        $query
            ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->chunk(1000, function ($chunk) use (&$porSede, &$alumnosSemana, &$userIds, $tz) {
                foreach ($chunk as $a) {
                    $fecha = $a->cupo?->fecha ? Carbon::parse($a->cupo->fecha, $tz) : null;
                    if (!$fecha) continue;
                    $dowIso = $fecha->dayOfWeekIso; if ($dowIso > 5) continue;

                    $estado = $a->asistencia_estado ?? 'pendiente';
                    if ($estado === 'no_show') $estado = 'inasistencia';
                    if (!empty($a->cupo?->es_festivo)) $estado = 'festivo';

                    $sede   = strtolower($a->cupo?->sede ?? 'sin_sede');
                    $user   = $a->user;
                    $name   = $user?->name ?? '(sin nombre)';

                    $label = "{$name} ({$estado})";
                    $porSede[$sede][$dowIso] = $porSede[$sede][$dowIso] ?? [];
                    $porSede[$sede][$dowIso][] = $label;

                    if ($user) {
                        $uid = $user->id;
                        $userIds[$uid] = true;
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

        foreach ($porSede as $sede => &$map) {
            for ($d=1; $d<=5; $d++) {
                $map[$d] = $map[$d] ?? [];
                sort($map[$d], SORT_NATURAL | SORT_FLAG_CASE);
            }
        }
        unset($map);

        $userIdsList = array_keys($userIds);
        if (!empty($userIdsList)) {
            $encuestaDatos = $this->fetchEncuestaDatos($userIdsList, $convId);
            foreach ($encuestaDatos as $uid => $info) {
                if (isset($alumnosSemana[$uid])) {
                    $alumnosSemana[$uid]['codigo']   = $alumnosSemana[$uid]['codigo']   ?? ($info['codigo']   ?? null);
                    $alumnosSemana[$uid]['programa'] = $alumnosSemana[$uid]['programa'] ?? ($info['programa'] ?? null);
                    $alumnosSemana[$uid]['numero']   = $alumnosSemana[$uid]['numero']   ?? ($info['numero']   ?? null);
                }
            }
        }

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
            new AsistenciasSemanaExport($lunes, $rangIni, $rangFin, $porSede, $alumnos),
            $filename
        );
    }

    /* ============= CIERRES MASIVOS ============= */

    private function cerrarRangoJoin(Carbon $ini, Carbon $fin, array $allowed, ?string $sedeSel, ?int $convId): int
    {
        return DB::table('subsidio_cupo_asignaciones as a')
            ->join('subsidio_cupos_diarios as d','d.id','=','a.cupo_diario_id')
            ->when($convId, fn($qq)=>$qq->where('d.convocatoria_id',$convId))
            ->when($sedeSel, fn($qq)=>$qq->where('d.sede',$sedeSel), fn($qq)=>$qq->whereIn('d.sede',$allowed))
            ->whereBetween('d.fecha', [$ini->toDateString(), $fin->toDateString()])
            ->whereRaw('WEEKDAY(d.fecha) BETWEEN 0 AND 4')
            ->where(function($q){
                $q->whereNull('d.es_festivo')->orWhere('d.es_festivo', 0)->orWhere('d.es_festivo', false);
            })
            ->where(function($q){
                $q->whereNull('a.asistencia_estado')->orWhere('a.asistencia_estado','pendiente');
            })
            ->update([
                'a.asistencia_estado'=>'inasistencia',
                'a.updated_at'=>now(),
            ]);
    }

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
                $q->whereNull('d.es_festivo')->orWhere('d.es_festivo', 0)->orWhere('d.es_festivo', false);
            })
            ->where(function($q){
                $q->whereNull('a.asistencia_estado')
                  ->orWhere('a.asistencia_estado','pendiente');
            })
            ->update([
                'a.asistencia_estado'=>'inasistencia',
                'a.updated_at'=>now(),
            ]);

        $this->logDebug('cerrar_dia', ['fecha'=>$fecha,'afectados'=>$afectados]);

        return back()->with($afectados>0 ? 'success' : 'warning',
            $afectados>0
                ? "Cierre ejecutado. Pendientes → inasistencia: {$afectados}"
                : "No se encontraron pendientes por cerrar en {$fecha}."
        );
    }

    public function cerrarSemana(Request $request)
    {
        $data = $request->validate(['lunes'=>'required|date']);
        $tz    = $this->tz();
        $lunes = Carbon::parse($data['lunes'], $tz)->startOfWeek(Carbon::MONDAY);

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        abort_if(empty($allowed),403,'No tienes sedes asignadas.');

        $ini = $lunes->copy();
        $fin = $lunes->copy()->addDays(6);

        $afectados = $this->cerrarRangoJoin($ini,$fin,$allowed,$sedeSel,$convId);

        return back()->with($afectados>0 ? 'success' : 'warning',
            $afectados>0
                ? "Semana cerrada. Pendientes → inasistencia: {$afectados}"
                : "No se encontraron pendientes por cerrar en la semana seleccionada."
        );
    }

    public function cerrarMes(Request $request)
    {
        $tz  = $this->tz();
        $mesParam = $request->input('mes', now($tz)->format('Y-m'));
        $base = Carbon::parse((preg_match('/^\d{4}-\d{2}$/', $mesParam) ? $mesParam.'-01' : $mesParam), $tz);

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        abort_if(empty($allowed),403,'No tienes sedes asignadas.');

        $ini = $base->copy()->startOfMonth();
        $fin = $base->copy()->endOfMonth();

        $afectados = $this->cerrarRangoJoin($ini,$fin,$allowed,$sedeSel,$convId);

        return back()->with($afectados>0 ? 'success' : 'warning',
            $afectados>0
                ? "Mes cerrado. Pendientes → inasistencia: {$afectados}"
                : "No se encontraron pendientes por cerrar en el mes seleccionado."
        );
    }

    /* ============= MES (vista) ============= */

    public function mes(Request $request)
    {
        $tz = $this->tz();
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

        $lunesCursor = $mes->copy()->startOfWeek(Carbon::MONDAY);
        $semanas = [];

        for ($i=0; $i<6; $i++) {
            $lunes = $lunesCursor->copy();
            $domingo = $lunes->copy()->addDays(6);

            if ($lunes->greaterThan($finMes) && $domingo->greaterThan($finMes)) break;

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

    /* ============= EXPORTAR MES (Excel) ============= */

    public function exportMes(Request $request)
    {
        $tz  = $this->tz();
        $mesParam = $request->input('mes', now($tz)->format('Y-m'));
        $base = Carbon::parse((preg_match('/^\d{4}-\d{2}$/', $mesParam) ? $mesParam.'-01' : $mesParam), $tz);

        $mes = $base->copy()->startOfMonth();
        $finMes = $base->copy()->endOfMonth();

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        abort_if(empty($allowed),403);

        $lunesCursor = $mes->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];

        for ($i=0; $i<6 && $lunesCursor->lte($finMes); $i++, $lunesCursor->addWeek()) {
            $rangIni = $lunesCursor->copy();
            $rangFin = $lunesCursor->copy()->addDays(6);

            $porSede = []; $alumnosSemana=[]; $userIds=[];

            $query = CupoAsignacion::with(['user','cupo'])
                ->whereHas('cupo', function($q) use ($rangIni, $rangFin) {
                    $q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()]);
                });
            $this->applyContextFilters($query,$sedeSel,$convId,$allowed);

            $query
                ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
                ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
                ->orderBy(User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
                ->chunk(1000, function ($chunk) use (&$porSede, &$alumnosSemana, &$userIds, $tz) {
                    foreach ($chunk as $a) {
                        $fecha = $a->cupo?->fecha ? Carbon::parse($a->cupo->fecha, $tz) : null;
                        if (!$fecha) continue;
                        $dowIso = $fecha->dayOfWeekIso; if ($dowIso > 5) continue;

                        $estado = $a->asistencia_estado ?? 'pendiente';
                        if ($estado === 'no_show') $estado = 'inasistencia';
                        if (!empty($a->cupo?->es_festivo)) $estado = 'festivo';

                        $sede   = strtolower($a->cupo?->sede ?? 'sin_sede');
                        $user   = $a->user;
                        $name   = $user?->name ?? '(sin nombre)';

                        $label = "{$name} ({$estado})";
                        $porSede[$sede][$dowIso] = $porSede[$sede][$dowIso] ?? [];
                        $porSede[$sede][$dowIso][] = $label;

                        if ($user) {
                            $uid = $user->id;
                            $userIds[$uid] = true;
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

            foreach ($porSede as $sede => &$map) {
                for ($d=1; $d<=5; $d++) {
                    $map[$d] = $map[$d] ?? [];
                    sort($map[$d], SORT_NATURAL | SORT_FLAG_CASE);
                }
            }
            unset($map);

            $userIdsList = array_keys($userIds);
            if (!empty($userIdsList)) {
                $encuestaDatos = $this->fetchEncuestaDatos($userIdsList, $convId);
                foreach ($encuestaDatos as $uid => $info) {
                    if (isset($alumnosSemana[$uid])) {
                        $alumnosSemana[$uid]['codigo']   = $alumnosSemana[$uid]['codigo']   ?? ($info['codigo']   ?? null);
                        $alumnosSemana[$uid]['programa'] = $alumnosSemana[$uid]['programa'] ?? ($info['programa'] ?? null);
                        $alumnosSemana[$uid]['numero']   = $alumnosSemana[$uid]['numero']   ?? ($info['numero']   ?? null);
                    }
                }
            }

            $alumnos = collect($alumnosSemana)
                ->map(function ($v) {
                    $v['sedes'] = implode(', ', array_map('ucfirst', array_keys($v['sedes'])));
                    return $v;
                })
                ->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)
                ->values()
                ->all();

            $weeks[] = [
                'lunes'   => $rangIni->copy(),
                'rangIni' => $rangIni,
                'rangFin' => $rangFin,
                'porSede' => $porSede,
                'alumnos' => $alumnos,
            ];
        }

        $filename = 'asistencias_mes_'.$mes->format('Ym').'.xlsx';
        return Excel::download(new AsistenciasMensualExport($weeks), $filename);
    }

    /* ============= Helper: ficha encuesta ============= */

    private function fetchEncuestaDatos(array $userIds, ?int $convId): array
    {
        if (empty($userIds)) return [];

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
            ->groupBy('p.user_id')
            ->get();

        $postIds = [];
        foreach ($rows as $r) {
            $postIds[(int)$r->id] = (int)$r->user_id;
        }
        if (empty($postIds)) return [];

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

        $out = [];
        foreach ($postIds as $postId => $uid) {
            $out[$uid] = ['codigo'=>null,'programa'=>null,'numero'=>null];
        }

        foreach ($resps as $r) {
            $uid = $postIds[(int)$r->postulacion_id] ?? null;
            if (!$uid) continue;

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