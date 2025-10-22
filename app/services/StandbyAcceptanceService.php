<?php

namespace App\Services;

class StandbyAcceptanceService
{
    public function aceptar(string $token): array
    {
        return app(StandbyOfferService::class)->aceptarPorToken($token);
    }
}