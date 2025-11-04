<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Vincular interfaz -> implementación
        $this->app->bind(CalculadoraPrioridad::class, PrioridadNivelService::class);

        // Si quieres singleton:
        // $this->app->singleton(CalculadoraPrioridad::class, PrioridadNivelService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\CupoAsignacion::observe(\App\Observers\CupoAsignacionObserver::class);

        // Comparte contadores para la navbar (convocatorias, standby inbox, cancelaciones)
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            // Asegura que siempre existan las variables usadas por el layout
            $subsidioConvocatoriasCount = 0;
            $standbyInboxCount          = 0;
            $adminCancelacionesCount    = null; // solo para AdminBienestar

            if ($user) {
                $tz   = (string) config('subsidio.timezone', config('app.timezone', 'America/Bogota'));
                $now  = now($tz);
                $hoy  = $now->copy()->startOfDay();

                // Estudiante: convocatorias abiertas + buzón (ofertas personales + vacantes no personales)
                if (method_exists($user, 'hasRole') && $user->hasRole('Estudiante')) {
                    // Mantén el span de convocatorias (no se toca)
                    try {
                        $subsidioConvocatoriasCount = \App\Models\ConvocatoriaSubsidio::abiertasParaPostulacion()->count();
                    } catch (\Throwable $e) {
                        $subsidioConvocatoriasCount = 0;
                    }

                    try {
                        // 1) Ofertas personales pendientes (no vencidas y para fechas >= hoy)
                        $ofertasPersonales = \App\Models\StandbyOferta::query()
                            ->where('user_id', $user->id)
                            ->where('estado', 'pendiente')
                            ->where(function ($q) use ($now) {
                                $q->whereNull('vence_en')->orWhere('vence_en', '>', $now);
                            })
                            ->whereHas('cupo', fn($q) => $q->whereDate('fecha', '>=', $hoy->toDateString()))
                            ->count();

                        // 2) Vacantes “no personales” elegibles por standby (L–V), mismas reglas del buzón
                        $ocupadosSql = "
                            SELECT cupo_diario_id, COUNT(*) AS ocupados
                            FROM subsidio_cupo_asignaciones
                            WHERE COALESCE(asistencia_estado, estado, 'asignado') <> 'cancelado'
                            GROUP BY cupo_diario_id
                        ";

                        $qVac = DB::table('subsidio_cupos_diarios as c')
                            ->leftJoin(DB::raw("($ocupadosSql) as oc"), 'oc.cupo_diario_id', '=', 'c.id')
                            ->join('subsidio_standby_registros as r', function ($j) use ($user) {
                                $j->on('r.convocatoria_id', '=', 'c.convocatoria_id')
                                  ->where('r.user_id', '=', $user->id)
                                  ->where('r.activo', '=', 1);
                            })
                            ->whereDate('c.fecha', '>=', $hoy->toDateString())
                            ->whereRaw('WEEKDAY(c.fecha) <= 4')
                            ->when(Schema::hasColumn('subsidio_cupos_diarios', 'es_festivo'), function ($q) {
                                $q->where(function ($w) {
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
                            // No contar si el estudiante ya tiene una asignación NO cancelada ese mismo día
                            ->whereNotExists(function ($q) use ($user) {
                                $q->select(DB::raw(1))
                                  ->from('subsidio_cupo_asignaciones as a')
                                  ->join('subsidio_cupos_diarios as c2', 'c2.id', '=', 'a.cupo_diario_id')
                                  ->where('a.user_id', $user->id)
                                  ->whereRaw('DATE(c2.fecha) = DATE(c.fecha)')
                                  ->whereRaw("COALESCE(a.asistencia_estado, a.estado, 'asignado') <> 'cancelado'");
                            });

                        $vacantesNoPersonales = (int) $qVac->count();

                        $standbyInboxCount = $ofertasPersonales + $vacantesNoPersonales;
                    } catch (\Throwable $e) {
                        // Si aún no existen tablas/modelos, no rompas el layout
                        $standbyInboxCount = 0;
                    }
                }

                // AdminBienestar: badge con cancelaciones de HOY
                if (method_exists($user, 'hasRole') && $user->hasRole('AdminBienestar')) {
                    try {
                        $adminCancelacionesCount = DB::table('subsidio_cupo_asignaciones as a')
                            ->join('subsidio_cupos_diarios as c', 'c.id', '=', 'a.cupo_diario_id')
                            ->where('a.asistencia_estado', 'cancelado')
                            ->whereDate('c.fecha', $hoy->toDateString())
                            ->count();
                    } catch (\Throwable $e) {
                        $adminCancelacionesCount = 0;
                    }
                }
            }

            // Pasar variables al layout
            $view->with('subsidioConvocatoriasCount', $subsidioConvocatoriasCount);
            $view->with('standbyInboxCount', $standbyInboxCount);
            if (!is_null($adminCancelacionesCount)) {
                $view->with('adminCancelacionesCount', $adminCancelacionesCount);
            }
        });
    }
}