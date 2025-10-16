<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AsistenciasSemanaExport implements FromView, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        public Carbon $lunes,
        public Carbon $rangIni,
        public Carbon $rangFin,
        public array $porSede,
        public array $alumnos
    ) {}

    public function title(): string
    {
        return 'Semana '.$this->lunes->format('Y-m-d');
    }

    public function view(): View
    {
        return view('exports.asistencias_semana', [
            'lunes'   => $this->lunes,
            'rangIni' => $this->rangIni,
            'rangFin' => $this->rangFin,
            'porSede' => $this->porSede,
            'alumnos' => $this->alumnos,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $colors = [
                    'asistio'      => 'FF92D050',
                    'pendiente'    => 'FFF2F2F2',
                    'inasistencia' => 'FFFFC000',
                    'cancelado'    => 'FFFF9999',
                    'festivo'      => 'FF17A2B8',
                ];

                $firstColIndex = 1; // A
                $lastColIndex  = 5; // E
                $lastRow       = (int) $sheet->getHighestRow();

                for ($row = 1; $row <= $lastRow; $row++) {
                    for ($col = $firstColIndex; $col <= $lastColIndex; $col++) {
                        $cell  = $sheet->getCellByColumnAndRow($col, $row);
                        $value = trim((string) $cell->getValue());
                        if ($value === '') continue;

                        $lower = mb_strtolower($value, 'UTF-8');
                        $match = null;

                        if (str_contains($lower, '(asistio)'))      $match = 'asistio';
                        elseif (str_contains($lower, '(pendiente)'))   $match = 'pendiente';
                        elseif (str_contains($lower, '(inasistencia)'))$match = 'inasistencia';
                        elseif (str_contains($lower, '(cancelado)'))   $match = 'cancelado';
                        elseif (str_contains($lower, '(festivo)'))     $match = 'festivo';

                        if ($match && isset($colors[$match])) {
                            $style = $sheet->getStyleByColumnAndRow($col, $row);
                            $style->getFill()->setFillType(Fill::FILL_SOLID)
                                  ->getStartColor()->setARGB($colors[$match]);
                            $style->getAlignment()->setWrapText(true);
                        }
                    }
                }

                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getFont()->setItalic(true);
            },
        ];
    }
}