<?php

namespace App\Exports;

use App\Models\Evento;
use App\Models\Lugar;
use App\Models\Espacio;
use App\Models\ProgramaDependencia;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class EventosExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $eventos;

    public function __construct(Collection $eventos)
    {
        $this->eventos = $eventos;
    }

    public function collection()
    {
        return $this->eventos->map(function ($evento) {
            // Obtener los nombres de lugar y espacio
            $nombreLugar = Lugar::find($evento->lugar)?->nombreLugar;
            $nombreEspacio = Espacio::find($evento->espacio)?->nombreEspacio;

            // Obtener las dependencias asociadas al evento desde la tabla evento_dependencia
            $dependenciasIds = \DB::table('evento_dependencia')
                ->where('evento_id', $evento->id)
                ->pluck('programadependencia_id')
                ->toArray();

            // Obtener los nombres de las dependencias desde la tabla programa_dependencia
            $nombresDependencias = ProgramaDependencia::whereIn('id', $dependenciasIds)
                ->pluck('nombrePD')
                ->toArray();
            
            // Convertir las dependencias a una cadena separada por comas
            $nombresDependenciasString = implode(', ', $nombresDependencias);

            return [
                'ID' => $evento->id,
                'Nombre' => $evento->nombreEvento,
                'Proposito' => $evento->propositoEvento,
                'Programa-dependencia' => $nombresDependenciasString,
                'Usuario' => optional($evento->usuario)->name,
                'Lugar' => $nombreLugar,
                'Espacio' => $nombreEspacio,
                'Fecha Realizacion' => $evento->fechaRealizacion,
                'Hora Inicio' => $evento->horaInicio,
                'Hora Fin' => $evento->horaFin,
                'Estado' => $evento->estado,
                'Evento Creado el' => $evento->created_at,
                'Ultima Actualizacion' => $evento->updated_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Proposito',
            'Programa-dependencia',
            'Usuario',
            'Lugar',
            'Espacio',
            'Fecha Realizacion',
            'Hora Inicio',
            'Hora Fin',
            'Estado',
            'Evento Creado el',
            'Ultima Actualizacion',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar estilo de negrita a la fila de encabezados (fila 1)
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
        ]);

        // Alinear todas las celdas a la izquierda
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return [];
    }
}
