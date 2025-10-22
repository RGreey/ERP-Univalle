<?php

namespace App\Console\Commands;

use App\Models\CupoDiario;
use App\Models\StandbyOferta;
use App\Services\StandbyOfferService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class StandbyExpireAndReplenish extends Command
{
    protected $signature = 'standby:rotate';
    protected $description = 'Expira ofertas vencidas y repone nuevas hasta completar paralelas por vacante (antes de las 12:00).';

    public function handle(): int
    {
        $tz = config('subsidio.timezone','America/Bogota');
        $now= now($tz);
        $parallel = (int) config('subsidio.standby_parallel_offers', 5);
        $ttlMin   = (int) config('subsidio.oferta_ttl_min', 10);

        // 1) Expirar ofertas vencidas
        $exp = StandbyOferta::where('estado','pendiente')
            ->where(function($q) use ($now) {
                $q->whereNotNull('vence_en')->where('vence_en','<',$now);
            })->update(['estado'=>'expirada','updated_at'=>$now]);
        $this->info("Ofertas expiradas: $exp");

        // 2) Reponer por cupo con vacantes y menos de N pendientes
        $svc = app(StandbyOfferService::class);

        // Buscar cupos de HOY con ofertas pendientes (o consumidas) aún con vacantes
        $hoy = $now->toDateString();
        $cuposHoy = CupoDiario::whereDate('fecha', $hoy)->get();

        $limiteDia = Carbon::parse($hoy.' '.config('subsidio.cancelacion_tardia_hasta','12:00'), $tz);
        if ($now->gte($limiteDia)) {
            $this->info('Pasada la hora de corte, no se reponen ofertas.');
            return self::SUCCESS;
        }

        $totalRep = 0;
        foreach ($cuposHoy as $cupo) {
            $vac = $cupo->vacantesActivas();
            if ($vac <= 0) continue;

            // ¿Cuántas pendientes existen?
            $pend = \App\Models\StandbyOferta::where('cupo_diario_id',$cupo->id)->where('estado','pendiente')->count();
            $targetPend = $vac * $parallel;
            // Por cada vacante, queremos al menos $parallel pendientes
            $targetPend = $vac * $parallel;
            if ($pend < $targetPend) {
                $lotes = intdiv(($targetPend - $pend + $parallel - 1), $parallel);
                for ($i=0; $i<$lotes; $i++) {
                    $totalRep += $svc->emitirLote($cupo, $parallel, $ttlMin);
                }
            }
        }
        $this->info("Ofertas repuestas: $totalRep");

        return self::SUCCESS;
    }
}