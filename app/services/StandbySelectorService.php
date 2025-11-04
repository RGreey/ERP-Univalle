<?php

namespace App\Services;

use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use App\Models\StandbyOferta;
use App\Models\StandbyRegistro;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB; // <-- Agregar este use


class StandbySelectorService
{
    public function elegiblesPara(CupoDiario $cupo, int $limite = 50): Collection
    {
        $fecha = $cupo->fecha->toDateString();
        $dow   = $cupo->fecha->dayOfWeekIso; // 1..7
        $colMap = [1=>'pref_lun',2=>'pref_mar',3=>'pref_mie',4=>'pref_jue',5=>'pref_vie'];
        $col   = $colMap[$dow] ?? null;
        if (!$col) return collect();

        $convId = (int) $cupo->convocatoria_id;
        $sede   = (string) $cupo->sede;

        // Usuarios que ya tienen cupo ese día (en cualquier sede) en esta convocatoria
        $yaConCupo = CupoAsignacion::whereHas('cupo', function($q) use ($convId, $fecha) {
                $q->where('convocatoria_id', $convId)->whereDate('fecha', $fecha);
            })->pluck('user_id');

        // Evitar re-enviar a quienes ya tienen una oferta pendiente VIGENTE
        $now = now(config('subsidio.timezone','America/Bogota'));
        $yaOfertados = StandbyOferta::where('cupo_diario_id', $cupo->id)
            ->where('estado','pendiente')
            ->where(function($q) use ($now) { $q->whereNull('vence_en')->orWhere('vence_en','>',$now); })
            ->pluck('user_id');

        $allowExternals = (bool) config('subsidio.standby_allow_externals', true);
        $estadosValidos = (array) config('subsidio.standby_valid_states', ['beneficiario','aprobada']);

        // Base: registros standby activos, pref del día igual a la sede
        $q = StandbyRegistro::query()
            ->select([
                'subsidio_standby_registros.user_id',
                // flag es_beneficiario para ordenar primero
                DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM subsidio_postulaciones p
                    WHERE p.user_id = subsidio_standby_registros.user_id
                      AND p.convocatoria_id = {$convId}
                      AND p.estado IN ('".implode("','", array_map('strval',$estadosValidos))."')
                ) THEN 1 ELSE 0 END AS es_beneficiario"),
            ])
            ->where('subsidio_standby_registros.convocatoria_id', $convId)
            ->where('subsidio_standby_registros.activo', true)
            ->where("subsidio_standby_registros.{$col}", $sede)
            ->whereNotIn('subsidio_standby_registros.user_id', $yaConCupo)
            ->whereNotIn('subsidio_standby_registros.user_id', $yaOfertados);

        if (!$allowExternals) {
            // Si no permitimos externos, exigir postulación válida
            $q->whereExists(function($q2) use ($convId, $estadosValidos) {
                $q2->select(DB::raw(1))
                   ->from('subsidio_postulaciones as p')
                   ->whereColumn('p.user_id','subsidio_standby_registros.user_id')
                   ->where('p.convocatoria_id', $convId)
                   ->whereIn('p.estado', $estadosValidos);
            });
        }

        // Orden: beneficiarios primero, luego por antigüedad de registro (si tienes created_at)
        $q->orderByDesc('es_beneficiario')
          ->orderBy('subsidio_standby_registros.created_at', 'asc');

        return $q->limit($limite)->pluck('user_id');
    }
}   