<?php

namespace App\Exports;

use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CuposSemanaExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private int $convocatoriaId,
        private Carbon $lunes
    ) {}

    public function title(): string
    {
        return 'Semana '.$this->lunes->format('Y-m-d');
    }

    public function headings(): array
    {
        return ['Fecha','Sede','Estudiante','Correo'];
    }

    public function array(): array
    {
        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) {
                $q->where('convocatoria_id', $this->convocatoriaId)
                  ->whereBetween('fecha', [$this->lunes->toDateString(), $this->lunes->copy()->addDays(6)->toDateString()]);
            })
            ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'));

        $rows = [];
        $query->chunk(1000, function ($items) use (&$rows) {
            foreach ($items as $a) {
                $rows[] = [
                    optional($a->cupo->fecha)->format('Y-m-d'),
                    ucfirst($a->cupo->sede),
                    optional($a->user)->name,
                    optional($a->user)->email,
                ];
            }
        });

        return $rows;
    }
}