<?php

namespace App\Exports;

use App\Models\ConvocatoriaSubsidio;
use App\Models\CupoAsignacion;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CuposReporteSemanaExport implements FromView, ShouldAutoSize, WithTitle, WithEvents
{
    public function __construct(
        private int $convocatoriaId,
        private Carbon $lunes
    ) {}

    public function title(): string
    {
        return 'Resumen '.$this->lunes->format('Y-m-d');
    }

    public function view(): View
    {
        $convocatoria = ConvocatoriaSubsidio::findOrFail($this->convocatoriaId);
        $rangIni = $this->lunes->copy();
        $rangFin = $this->lunes->copy()->addDays(6);

        $asignaciones = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($convocatoria, $rangIni, $rangFin) {
                $q->where('convocatoria_id', $convocatoria->id)
                  ->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) <= 4');
            })
            ->get();

        $dataPorSede = [
            'caicedonia' => [1=>[],2=>[],3=>[],4=>[],5=>[]],
            'sevilla'    => [1=>[],2=>[],3=>[],4=>[],5=>[]],
        ];

        foreach ($asignaciones as $a) {
            $sede  = $a->cupo?->sede ?? null;
            $fecha = $a->cupo?->fecha;
            if (!$sede || !$fecha) continue;
            $dISO = $fecha->dayOfWeekIso;
            if ($dISO < 1 || $dISO > 5) continue;
            $dataPorSede[$sede][$dISO][] = trim((string)($a->user?->name ?? '—'));
        }

        $dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes'];

        return view('roles.adminbienestar.cupos._reporte_semana_xls', [
            'convocatoria' => $convocatoria,
            'lunes'        => $this->lunes,
            'dataPorSede'  => $dataPorSede,
            'dias'         => $dias,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate(); // Worksheet

                // Aplica wrap text y alineación vertical al rango ocupado
                $range = $sheet->calculateWorksheetDimension(); // ej. "A1:E20"
                $sheet->getStyle($range)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                // Alto de fila automático (que respete wrap)
                $sheet->getDefaultRowDimension()->setRowHeight(-1);
            },
        ];
    }
}