<?php

namespace App\Http\Controllers;

use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use App\Models\ConvocatoriaSubsidio;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            ->orderBy(\App\Models\User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->get();

        $totales = $this->totalesEstados($items);

        return view('roles.adminbienestar.asistencias.diario', compact('items','fecha','sede','totales','convocatorias') + [
            'convocatoriaId' => $convId,
        ]);
    }

    // Cancelaciones
    public function cancelaciones(Request $request)
    {
        $tz    = config('subsidio.timezone', 'America/Bogota');
        $desde = Carbon::parse($request->input('desde', now($tz)->toDateString()), $tz)->startOfDay();
        $hasta = Carbon::parse($request->input('hasta', now($tz)->toDateString()), $tz)->endOfDay();
        $sede  = $request->input('sede');
        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);
        $convId = $request->input('convocatoria_id');

        $items = CupoAsignacion::with(['user','cupo'])
            ->where('asistencia_estado', 'cancelado')
            ->whereHas('cupo', function($q) use ($desde, $hasta, $sede, $convId) {
                $q->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) <= 4');
                if ($sede)   $q->where('sede', $sede);
                if ($convId) $q->where('convocatoria_id', $convId);
            })
            ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(\App\Models\User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->get();

        return view('roles.adminbienestar.asistencias.cancelaciones', compact('items','desde','hasta','sede','convocatorias') + [
            'convocatoriaId' => $convId,
        ]);
    }

    // Semanal — matriz L–V (ya lo tienes)
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
                  ->whereRaw('WEEKDAY(fecha) <= 4');
                if ($sede)   $q->where('sede', $sede);
                if ($convId) $q->where('convocatoria_id', $convId);
            })
            ->orderBy(\App\Models\User::select('name')->whereColumn('users.id','subsidio_cupo_asignaciones.user_id'))
            ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->get();

        $rows = [];
        foreach ($asigs as $a) {
            $uid = $a->user_id;
            if (!isset($rows[$uid])) {
                $rows[$uid] = [
                    'user_id' => $uid,
                    'nombre'  => $a->user?->name ?? '—',
                    'email'   => $a->user?->email ?? '—',
                    'dias'    => []
                ];
            }
            $f = $a->cupo->fecha->toDateString();
            $rows[$uid]['dias'][$f] = $this->estadoNorm($a->asistencia_estado ?? 'pendiente');
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

    // Mensual — 4 semanas del mes, cada una como matriz L–V
    public function mensual(Request $request)
    {
        $tz   = config('subsidio.timezone', 'America/Bogota');
        $mes  = $request->input('mes', now($tz)->format('Y-m')); // 'YYYY-MM'
        $inicio = Carbon::parse($mes.'-01', $tz)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();
        $sede   = $request->input('sede');
        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);
        $convId = $request->input('convocatoria_id');

        // Traer todas las asignaciones del mes (solo L–V)
        $asigsMes = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($inicio, $fin, $sede, $convId) {
                $q->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) <= 4');
                if ($sede)   $q->where('sede', $sede);
                if ($convId) $q->where('convocatoria_id', $convId);
            })
            ->get();

        // Mapear por usuario y fecha: user_id => ['nombre','email','dias' => ['Y-m-d'=>estado]]
        $byUser = [];
        foreach ($asigsMes as $a) {
            $uid = $a->user_id;
            if (!isset($byUser[$uid])) {
                $byUser[$uid] = [
                    'user_id' => $uid,
                    'nombre'  => $a->user?->name ?? '—',
                    'email'   => $a->user?->email ?? '—',
                    'dias'    => [],
                ];
            }
            $f = $a->cupo->fecha->toDateString();
            $byUser[$uid]['dias'][$f] = $this->estadoNorm($a->asistencia_estado ?? 'pendiente');
        }

        // 4 semanas fijas del mes: [1..7], [8..14], [15..21], [22..fin]
        $weeks = [];
        $ranges = [
            [$inicio->copy()->day(1),  $inicio->copy()->day(min(7,  $fin->day))],
            [$inicio->copy()->day(8),  $inicio->copy()->day(min(14, $fin->day))],
            [$inicio->copy()->day(15), $inicio->copy()->day(min(21, $fin->day))],
            [$inicio->copy()->day(22), $fin->copy()],
        ];

        foreach ($ranges as $i => [$wStart, $wEnd]) {
            // Lista de días L–V en la semana i
            $dias = collect();
            for ($d = $wStart->copy(); $d->lte($wEnd); $d->addDay()) {
                if (in_array($d->dayOfWeekIso, [1,2,3,4,5], true)) {
                    $dias->push($d->copy());
                }
            }

            // Construir filas (por usuario) para esta semana
            $rows = [];
            foreach ($byUser as $u) {
                $row = [
                    'user_id' => $u['user_id'],
                    'nombre'  => $u['nombre'],
                    'email'   => $u['email'],
                    'dias'    => [],
                ];
                foreach ($dias as $d) {
                    $key = $d->toDateString();
                    $row['dias'][$key] = $u['dias'][$key] ?? null;
                }
                // Mostrar la fila si tiene al menos un valor en la semana
                if (collect($row['dias'])->filter()->isNotEmpty()) {
                    $rows[] = $row;
                }
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

    // Helpers
    private function estadoNorm(string $estado): string
    {
        return $estado === 'no_show' ? 'inasistencia' : $estado;
    }

    private function totalesEstados($items): array
    {
        $counts = ['pendiente'=>0,'cancelado'=>0,'asistio'=>0,'inasistencia'=>0];
        foreach ($items as $a) {
            $e = $this->estadoNorm($a->asistencia_estado ?? 'pendiente');
            $counts[$e] = ($counts[$e] ?? 0) + 1;
        }
        return $counts;
    }
}