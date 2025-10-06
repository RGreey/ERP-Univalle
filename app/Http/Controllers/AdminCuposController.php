<?php

namespace App\Http\Controllers;

use App\Models\ConvocatoriaSubsidio;
use App\Models\CupoDiario;
use App\Models\CupoAsignacion;
use App\Models\PostulacionSubsidio;
use App\Services\AsignadorCuposService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

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

        $candidatos = PostulacionSubsidio::with('user')
            ->where('convocatoria_id', $conv->id)
            ->whereIn('estado', ['evaluada','beneficiario'])
            ->when(!$incluirOtrasSedes, fn($qb)=>$qb->where('sede', ucfirst($sede)))
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

        if ($cupo->asignados >= $cupo->capacidad) {
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
            ->when(!$incluirOtrasSedes, fn($qb)=>$qb->where('sede', ucfirst($sede)))
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

        $creados = 0;

        DB::transaction(function () use ($cupo, $conv, $fecha, $sede, $respetarLimite, $maxPorSemana, $candidatos, $usersAsignadosEseDia, &$creados) {
            foreach ($candidatos as $p) {
                if ($cupo->asignados >= $cupo->capacidad) break;
                if (in_array($p->user_id, $usersAsignadosEseDia, true)) continue;

                if ($respetarLimite) {
                    $max = $maxPorSemana[$p->prioridad_orden] ?? 1;
                    $semanaCnt = (int) ($p->semana_asignados ?? 0);
                    if ($semanaCnt >= $max) continue;
                }

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
}