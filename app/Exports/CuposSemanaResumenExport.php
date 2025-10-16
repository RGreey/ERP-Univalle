<?php

namespace App\Exports;

use App\Models\ConvocatoriaSubsidio;
use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class CuposSemanaResumenExport implements FromArray, WithTitle
{
    public function __construct(
        private int $convocatoriaId,
        private Carbon $lunes
    ) {}

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        $conv = ConvocatoriaSubsidio::findOrFail($this->convocatoriaId);
        $rangIni = $this->lunes->copy();
        $rangFin = $this->lunes->copy()->addDays(6);

        // Traer asignaciones L–V con usuario y cupo
        $asignaciones = CupoAsignacion::with(['user','cupo'])
            ->whereHas('cupo', function($q) use ($conv, $rangIni, $rangFin) {
                $q->where('convocatoria_id', $conv->id)
                  ->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()])
                  ->whereRaw('WEEKDAY(fecha) <= 4'); // L–V
            })
            ->get();

        // Agrupar nombres por sede y día ISO
        $nombres = [
            'caicedonia' => [1=>[],2=>[],3=>[],4=>[],5=>[]],
            'sevilla'    => [1=>[],2=>[],3=>[],4=>[],5=>[]],
        ];

        foreach ($asignaciones as $a) {
            $sede = $a->cupo?->sede ?? null;
            $fecha = optional($a->cupo?->fecha);
            if (!$sede || !$fecha) continue;
            $dISO = $fecha->dayOfWeekIso;
            if ($dISO < 1 || $dISO > 5) continue;
            $nombres[$sede][$dISO][] = trim((string)($a->user?->name ?? '—'));
        }

        // Capacidades por sede y día
        $cuposSemana = CupoDiario::where('convocatoria_id', $conv->id)
            ->whereBetween('fecha', [$rangIni->toDateString(), $rangFin->toDateString()])
            ->orderBy('fecha')->orderBy('sede')->get()
            ->keyBy(fn($c)=> $c->fecha->toDateString().'|'.$c->sede);

        $capFor = function(string $sede, int $dISO) use ($cuposSemana, $conv, $rangIni) {
            $fecha = $rangIni->copy()->addDays($dISO-1)->toDateString();
            $key = $fecha.'|'.$sede;
            $c = $cuposSemana->get($key);
            if ($c) return (int) $c->capacidad;
            return (int) ($sede === 'caicedonia' ? ($conv->cupos_caicedonia ?? 0) : ($conv->cupos_sevilla ?? 0));
        };

        $countFor = function(string $sede, int $dISO) use ($nombres) {
            return count($nombres[$sede][$dISO] ?? []);
        };

        $dias = [1=>'LUNES',2=>'MARTES',3=>'MIÉRCOLES',4=>'JUEVES',5=>'VIERNES'];

        $rows = [];
        // Encabezado general
        $rows[] = ['Reporte semanal', 'Convocatoria:', $conv->nombre, 'Semana:', $this->lunes->format('Y-m-d').' al '.$rangFin->format('Y-m-d')];
        $rows[] = []; // fila en blanco

        foreach (['caicedonia','sevilla'] as $sede) {
            $rows[] = [Str::ucfirst($sede)];
            // Encabezados por día
            $header = array_merge([''], array_values($dias));
            $rows[] = $header;

            // Fila de asignados/capacidad
            $countsRow = ['asignados / capacidad'];
            foreach ([1,2,3,4,5] as $dISO) {
                $countsRow[] = $countFor($sede, $dISO).' / '.$capFor($sede, $dISO);
            }
            $rows[] = $countsRow;

            // Fila de nombres por día (separados por salto de línea)
            $namesRow = ['nombres'];
            foreach ([1,2,3,4,5] as $dISO) {
                $list = $nombres[$sede][$dISO] ?? [];
                // usar salto de línea; Excel suele respetarlo (Wrap Text)
                $namesRow[] = implode("\n• ", $list ? array_map(fn($x)=>"{$x}", $list) : []);
                if (empty($list)) {
                    $namesRow[count($namesRow)-1] = '—';
                }
            }
            $rows[] = $namesRow;

            // Espacio entre sedes
            $rows[] = [];
        }

        return $rows;
    }
}