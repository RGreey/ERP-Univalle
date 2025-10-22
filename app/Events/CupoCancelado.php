<?php

namespace App\Events;

use App\Models\CupoDiario;
use Illuminate\Foundation\Events\Dispatchable;

class CupoCancelado
{
    use Dispatchable;

    public function __construct(
        public int $cupoDiarioId,
        public ?int $asignacionCanceladaId = null,
        public ?string $motivo = null
    ) {}
}