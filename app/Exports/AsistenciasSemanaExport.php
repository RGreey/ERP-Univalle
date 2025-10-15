<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AsistenciasSemanaExport implements FromView, ShouldAutoSize, WithEvents
{
    public function __construct(
        public Carbon $lunes,
        public Carbon $rangIni,
        public Carbon $rangFin,
        public array $porSede,
        public array $alumnos // nuevo
    ) {}

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

                // Colores por estado
                $colors = [
                    'asistio'      => 'FF92D050', // verde
                    'pendiente'    => 'FFF2F2F2', // gris claro
                    'inasistencia' => 'FFFFC000', // ámbar
                    'cancelado'    => 'FFFF9999', // rojo claro
                ];

                // Suposición: las tablas de días están en las primeras 5 columnas (A..E).
                // La tabla “Ficha de estudiantes” inicia más a la derecha o debajo,
                // pero no contiene “(estado)”, así que no se verá afectada.
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

                        if (str_contains($lower, '(asistio)')) {
                            $match = 'asistio';
                        } elseif (str_contains($lower, '(pendiente)')) {
                            $match = 'pendiente';
                        } elseif (str_contains($lower, '(inasistencia)')) {
                            $match = 'inasistencia';
                        } elseif (str_contains($lower, '(cancelado)')) {
                            $match = 'cancelado';
                        }

                        if ($match && isset($colors[$match])) {
                            $style = $sheet->getStyleByColumnAndRow($col, $row);
                            $style->getFill()->setFillType(Fill::FILL_SOLID)
                                  ->getStartColor()->setARGB($colors[$match]);
                            $style->getAlignment()->setWrapText(true);
                        }
                    }
                }

                // Titulares en negrita
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getFont()->setItalic(true);
            },
        ];
    }
}