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

        $hoy = now($tz)->startOfDay();
        $editable = $fecha->equalTo($hoy);

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
        $lunes = Carbon::parse($request->input('lunes', now($tz)->toDateString()),$tz)
            ->startOfWeek(Carbon::MONDAY);

        [$allowed,$sedeSel,$convId] = $this->currentContext();
        abort_if(empty($allowed),403);

        $rangIni = $lunes->copy();
        $rangFin = $lunes->copy()->addDays(6);

        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', fn($q)=>$q->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()]));
        $this->applyContextFilters($query,$sedeSel,$convId,$allowed);

        $rows = [];
        $query->chunk(1000, function($chunk) use (&$rows){
            foreach ($chunk as $a) {
                if ($a->asistencia_estado === 'no_show') $a->asistencia_estado = 'inasistencia';
                $rows[] = [
                    $a->cupo?->fecha?->format('Y-m-d'),
                    $a->cupo?->sede,
                    $a->user?->name,
                    $a->user?->email,
                    $a->asistencia_estado,
                ];
            }
        });

        $filename = 'asistencias_semana_'.$rangIni->format('Ymd').'.csv';
        $handle = fopen('php://temp','r+');
        fputcsv($handle, ['fecha','sede','estudiante','email','estado']);
        foreach ($rows as $line) fputcsv($handle,$line);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv,200,[
            'Content-Type'=>'text/csv',
            'Content-Disposition'=>"attachment; filename=\"{$filename}\""
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
}