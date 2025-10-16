<?php

namespace App\Exports;

use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CuposSemanaExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
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
        // Agregamos Estado para poder colorear y facilitar lectura
        return ['Fecha','Sede','Estudiante','Correo','Estado'];
    }

    public function array(): array
    {
        $rangIni = $this->lunes->copy()->toDateString();
        $rangFin = $this->lunes->copy()->addDays(6)->toDateString();

        $query = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($rangIni,$rangFin) {
                $q->where('convocatoria_id', $this->convocatoriaId)
                  ->whereBetween('fecha', [$rangIni, $rangFin]);
            })
            ->orderBy(CupoDiario::select('fecha')
                ->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('sede')
                ->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderByRaw('LOWER((SELECT name FROM users WHERE users.id = subsidio_cupo_asignaciones.user_id))');

        $rows = [];
        $query->chunk(1000, function ($items) use (&$rows) {
            foreach ($items as $a) {
                // Normalizar estado (incluye festivo y no_show -> inasistencia)
                $estado = $a->cupo?->es_festivo ? 'festivo' : ($a->asistencia_estado ?? 'pendiente');
                if ($estado === 'no_show') $estado = 'inasistencia';

                $fecha = $a->cupo?->fecha ? Carbon::parse($a->cupo->fecha)->format('Y-m-d') : null;

                $rows[] = [
                    $fecha,
                    ucfirst((string)($a->cupo->sede ?? '')),
                    (string) optional($a->user)->name,
                    (string) optional($a->user)->email,
                    $estado,
                ];
            }
        });

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // La columna Estado es la 5 (E). Empezamos en la fila 2
                $estadoCol = 5;
                $firstRow = 2;
                $lastRow  = (int) $sheet->getHighestRow();

                if ($lastRow < $firstRow) return;

                // Mapa de colores por estado (ARGB)
                $colors = [
                    'asistio'      => 'FF92D050', // verde
                    'pendiente'    => 'FFF2F2F2', // gris claro
                    'inasistencia' => 'FFFFC000', // ámbar
                    'cancelado'    => 'FFFF9999', // rojo claro
                    'festivo'      => 'FF9DC3E6', // azul claro
                ];

                for ($row = $firstRow; $row <= $lastRow; $row++) {
                    $cell = $sheet->getCellByColumnAndRow($estadoCol, $row);
                    $value = mb_strtolower(trim((string) $cell->getValue()), 'UTF-8');
                    if ($value === '') continue;

                    // Asegurar equivalencias
                    if ($value === 'no_show') $value = 'inasistencia';

                    if (isset($colors[$value])) {
                        $style = $sheet->getStyleByColumnAndRow($estadoCol, $row);
                        $style->getFill()->setFillType(Fill::FILL_SOLID)
                              ->getStartColor()->setARGB($colors[$value]);
                        $style->getAlignment()->setWrapText(true);
                    }
                }

                // Encabezados más visibles
                $sheet->getStyle('A1:E1')->getFont()->setBold(true);
            },
        ];
    }
}