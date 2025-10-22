<?php

namespace App\Listeners;

use App\Events\CupoCancelado;
use App\Models\CupoDiario;
use App\Services\StandbyOfferService;

class EmitirOfertasStandby
{
    public function __construct(private StandbyOfferService $svc) {}

    public function handle(CupoCancelado $event): void
    {
        $cupo = CupoDiario::find($event->cupoDiarioId);
        if (!$cupo) return;

        // Vacantes por ocupación activa
        $vacantes = $cupo->vacantesActivas();
        if ($vacantes <= 0) return;

        $parallel = (int) config('subsidio.standby_parallel_offers', 5);
        $ttlMin   = (int) config('subsidio.oferta_ttl_min', 10);

        $this->svc->emitirOfertasPorVacantes($cupo, $vacantes, $parallel, $ttlMin);
    }
}