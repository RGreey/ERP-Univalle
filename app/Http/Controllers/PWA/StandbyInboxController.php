<?php

namespace App\Http\Controllers\PWA;

use App\Http\Controllers\Controller;
use App\Models\StandbyOferta;
use App\Models\StandbyRegistro;
use App\Models\CupoDiario;
use App\Models\CupoAsignacion;
use App\Models\PostulacionSubsidio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StandbyInboxController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Estudiante']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $tz   = config('subsidio.timezone', config('app.timezone', 'America/Bogota'));
        $now  = now($tz);

        // Ofertas personales
        $pendientes = StandbyOferta::with(['cupo'])
            ->where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->where(function ($q) use ($now) {
                $q->whereNull('vence_en')->orWhere('vence_en', '>', $now);
            })
            ->orderBy('vence_en', 'asc')
            ->get();

        $historial = StandbyOferta::with('cupo')
            ->where('user_id', $user->id)
            ->whereIn('estado', ['rechazada','expirada','aceptada'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Vacantes a futuro (L–V), elegibles por standby, calculadas con ocupados efectivos (excluye cancelados)
        $hoy = $now->copy()->startOfDay();

        $ocupadosSql = "
            SELECT cupo_diario_id, COUNT(*) AS ocupados
            FROM subsidio_cupo_asignaciones
            WHERE COALESCE(asistencia_estado, estado, 'asignado') <> 'cancelado'
            GROUP BY cupo_diario_id
        ";

        $vacantes = CupoDiario::query()
            ->from('subsidio_cupos_diarios as c')
            ->selectRaw("c.*, (c.capacidad - IFNULL(oc.ocupados, 0)) AS vacantes")
            ->leftJoin(DB::raw("($ocupadosSql) as oc"), 'oc.cupo_diario_id', '=', 'c.id')
            ->join('subsidio_standby_registros as r', function ($j) use ($user) {
                $j->on('r.convocatoria_id', '=', 'c.convocatoria_id')
                  ->where('r.user_id', '=', $user->id)
                  ->where('r.activo', '=', 1);
            })
            ->whereDate('c.fecha', '>=', $hoy->toDateString())
            ->whereRaw('WEEKDAY(c.fecha) <= 4')
            ->when(Schema()->hasColumn('subsidio_cupos_diarios','es_festivo'), function ($q) {
                $q->where(function($w){
                    $w->whereNull('c.es_festivo')->orWhere('c.es_festivo', false);
                });
            })
            ->whereRaw("
                (
                    (WEEKDAY(c.fecha) = 0 AND r.pref_lun = c.sede) OR
                    (WEEKDAY(c.fecha) = 1 AND r.pref_mar = c.sede) OR
                    (WEEKDAY(c.fecha) = 2 AND r.pref_mie = c.sede) OR
                    (WEEKDAY(c.fecha) = 3 AND r.pref_jue = c.sede) OR
                    (WEEKDAY(c.fecha) = 4 AND r.pref_vie = c.sede)
                )
            ")
            ->whereRaw('(c.capacidad - IFNULL(oc.ocupados, 0)) > 0')
            ->orderBy('c.fecha')
            ->orderBy('c.sede')
            ->paginate(50)
            ->withQueryString();

        // Marcar si el usuario ya tiene un cupo NO cancelado esos días (para UI)
        $fechasPagina = collect($vacantes->items())
            ->map(fn($c) => ($c->fecha instanceof Carbon) ? $c->fecha->toDateString() : (string) $c->fecha)
            ->unique()->values()->all();

        $asigDelUsuarioEnFechas = [];
        if (!empty($fechasPagina)) {
            $asigDelUsuarioEnFechas = CupoAsignacion::where('user_id', $user->id)
                ->whereHas('cupo', fn($q)=> $q->whereIn(DB::raw('DATE(fecha)'), $fechasPagina))
                ->whereRaw("COALESCE(asistencia_estado, estado, 'asignado') <> 'cancelado'")
                ->with('cupo:id,fecha')
                ->get()
                ->map(fn($a)=> optional($a->cupo?->fecha)?->toDateString())
                ->filter()
                ->unique()
                ->flip()
                ->toArray();
        }

        foreach ($vacantes as $c) {
            $fechaStr = ($c->fecha instanceof Carbon) ? $c->fecha->toDateString() : (string) $c->fecha;
            $c->ui_ya_tiene_ese_dia = array_key_exists($fechaStr, $asigDelUsuarioEnFechas);
        }

        return view('pwa.subsidio.standby.inbox', compact('pendientes','historial','now','vacantes'));
    }

    /**
     * Reclama una vacante:
     * - lock del cupo
     * - si el usuario tiene un registro ese día CANCELADO → reusar ese registro (UPDATE) para evitar violar el UNIQUE de día+usuario
     * - si no tiene registro ese día → crear
     * - expira ofertas si se llenó el cupo
     */
    public function claimCupo(Request $request, CupoDiario $cupo)
    {
        $user = $request->user();
        $tz   = config('subsidio.timezone', config('app.timezone', 'America/Bogota'));
        $now  = now($tz);

        $fecha = $cupo->fecha instanceof Carbon ? $cupo->fecha->copy() : Carbon::parse($cupo->fecha, $tz);

        // Ventana: permitir hoy hasta hora tardía (config) y cualquier fecha futura
        $limTardia = (string) config('subsidio.cancelacion_tardia_hasta','12:00');
        if ($fecha->isSameDay($now) && $now->format('H:i') > $limTardia) {
            return back()->with('error','Fuera de la ventana para tomar reemplazos (después de '.$limTardia.').');
        }

        // Elegibilidad por standby
        $map = [1=>'pref_lun',2=>'pref_mar',3=>'pref_mie',4=>'pref_jue',5=>'pref_vie'];
        $dISO = $fecha->dayOfWeekIso;
        if (!isset($map[$dISO])) return back()->with('error','Día no válido.');
        $col = $map[$dISO];

        $reg = StandbyRegistro::where('convocatoria_id', $cupo->convocatoria_id)
            ->where('user_id', $user->id)
            ->where('activo', true)
            ->where($col, $cupo->sede)
            ->first();
        if (!$reg) return back()->with('error','No eres elegible para esta vacante según tu standby.');

        try {
            DB::transaction(function () use ($cupo, $user, $fecha) {
                // 1) Lock cupo
                $c = CupoDiario::where('id', $cupo->id)->lockForUpdate()->firstOrFail();

                // 2) Ocupados efectivos (excluye cancelados)
                $ocupados = (int) DB::table('subsidio_cupo_asignaciones')
                    ->where('cupo_diario_id', $c->id)
                    ->whereRaw("COALESCE(asistencia_estado, estado, 'asignado') <> 'cancelado'")
                    ->count();

                if (($c->capacidad - $ocupados) <= 0) {
                    throw new \RuntimeException('La vacante ya fue tomada.');
                }

                // 3) Buscar registro del usuario en ese DÍA dentro de esta convocatoria (lock)
                $exist = CupoAsignacion::where('user_id', $user->id)
                    ->whereHas('cupo', function($q) use ($c, $fecha) {
                        $q->where('convocatoria_id', $c->convocatoria_id)
                          ->whereDate('fecha', $fecha->toDateString());
                    })
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                // Postulación vigente
                $postId = PostulacionSubsidio::where('convocatoria_id', $c->convocatoria_id)
                    ->where('user_id', $user->id)
                    ->whereIn('estado', ['evaluada','beneficiario'])
                    ->value('id');
                if (!$postId) throw new \RuntimeException('No tienes postulación vigente para esta convocatoria.');

                if ($exist) {
                    $efectivo = $exist->asistencia_estado ?? $exist->estado;
                    if ($efectivo !== 'cancelado') {
                        throw new \RuntimeException('Ya tienes una asignación vigente ese día.');
                    }

                    $exist->cupo_diario_id          = $c->id;
                    $exist->postulacion_id          = $postId;
                    $exist->estado                  = 'asignado';
                    $exist->asignado_en             = now();
                    $exist->qr_token                = bin2hex(random_bytes(16));
                    $exist->es_reemplazo            = true;
                    $exist->cancelada_en            = null;
                    $exist->cancelada_por_user_id   = null;
                    $exist->cancelacion_origen      = null;
                    $exist->cancelacion_motivo      = null;
                    $exist->reversion_en            = null;
                    $exist->reversion_por_user_id   = null;
                    $exist->reversion_motivo        = null;
                    $exist->asistencia_estado       = 'pendiente'; // SIEMPRE asigna aquí

                    // Validación extra para cualquier otro campo NOT NULL
                    foreach (['estado', 'qr_token'] as $campo) {
                        if (property_exists($exist, $campo) && empty($exist->$campo)) {
                            $exist->$campo = $campo === 'qr_token' ? bin2hex(random_bytes(16)) : 'asignado';
                        }
                    }

                    $exist->save();


                } else {
                    // No hay registro ese día → crear uno nuevo
                    CupoAsignacion::create([
                        'cupo_diario_id'          => $c->id,
                        'postulacion_id'          => $postId,
                        'user_id'                 => $user->id,
                        'estado'                  => 'asignado',
                        'asignado_en'             => now(),
                        'qr_token'                => bin2hex(random_bytes(16)),
                        'es_reemplazo'            => true,
                        'reemplaza_asignacion_id' => null,
                    ]);
                }

                // 4) Si quedó lleno, expira ofertas pendientes del mismo cupo
                $ocupados2 = (int) DB::table('subsidio_cupo_asignaciones')
                    ->where('cupo_diario_id', $c->id)
                    ->whereRaw("COALESCE(asistencia_estado, estado, 'asignado') <> 'cancelado'")
                    ->count();

                if ($c->capacidad - $ocupados2 <= 0) {
                    DB::table('subsidio_standby_ofertas')
                        ->where('cupo_diario_id', $c->id)
                        ->where('estado','pendiente')
                        ->update(['estado'=>'expirada']);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error','No fue posible tomar la vacante. Intenta nuevamente.');
        }

        return back()->with('success','Cupo tomado correctamente.');
    }

    // Ofertas personales (sin cambios)
    public function accept(Request $request, StandbyOferta $oferta)
    {
        $request->validate(['_token' => 'required']);
        if ($oferta->user_id !== $request->user()->id) abort(403);

        $tz  = config('subsidio.timezone', config('app.timezone', 'America/Bogota'));
        $now = now($tz);
        if ($oferta->estado !== 'pendiente' || ($oferta->vence_en && $oferta->vence_en->lte($now))) {
            return back()->with('error', 'Esta oferta ya no está disponible.');
        }

        $svc = app(\App\Services\StandbyOfferService::class);
        $result = app(\App\Services\StandbyOfferServiceAccept::class, ['svc' => $svc])->aceptar((string) $oferta->token);

        return back()->with($result['ok'] ? 'success' : 'error', $result['msg'] ?? ($result['ok'] ? 'Cupo tomado.' : 'No se pudo tomar el cupo.'));
    }

    public function decline(Request $request, StandbyOferta $oferta)
    {
        $request->validate(['_token' => 'required']);
        if ($oferta->user_id !== $request->user()->id) abort(403);
        if ($oferta->estado !== 'pendiente') return back()->with('error','Esta oferta ya no está disponible.');

        $oferta->estado = 'rechazada';
        $oferta->save();

        return back()->with('success', 'Oferta rechazada.');
    }
}

// Helper schema
if (!function_exists(__NAMESPACE__.'\\Schema')) {
    function Schema() { return app('db.schema'); }
}