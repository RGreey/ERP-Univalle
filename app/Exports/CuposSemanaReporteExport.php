<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CuposSemanaReporteExport implements WithMultipleSheets
{
    public function __construct(
        private int $convocatoriaId,
        private Carbon $lunes
    ) {}

    public function sheets(): array
    {
        return [
            // Hoja 1: Resumen del reporte (matriz por sede y día)
            new CuposSemanaResumenExport($this->convocatoriaId, $this->lunes),

            // Hoja 2: Detalle (reutiliza el exportador existente, sin tocarlo)
            new CuposSemanaExport($this->convocatoriaId, $this->lunes),
        ];
    }
}