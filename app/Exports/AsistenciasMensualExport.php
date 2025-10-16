<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AsistenciasMensualExport implements WithMultipleSheets
{
    /**
     * @param array<int,array{lunes:\Carbon\Carbon, rangIni:\Carbon\Carbon, rangFin:\Carbon\Carbon, porSede:array, alumnos:array}> $weeks
     */
    public function __construct(private array $weeks) {}

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->weeks as $w) {
            $sheets[] = new AsistenciasSemanaExport($w['lunes'], $w['rangIni'], $w['rangFin'], $w['porSede'], $w['alumnos']);
        }
        return $sheets;
    }
}