@extends('layouts.app')
@section('title','Reporte semanal de cupos')

@section('content')
<style>
    /* Contenedor centrado y más estrecho para que se vea más grande y legible */
    .uv-wrap { max-width: 1100px; margin: 0 auto; }

    .uv-card { background:#fff; border-radius:14px; box-shadow:0 10px 26px rgba(0,0,0,.07); padding:22px; }
    .uv-title { font-weight: 800; font-size: 1.05rem; }
    .uv-sub { font-size: .85rem; color:#6c757d; }

    /* Una sola columna: una sede arriba y la otra abajo */
    .uv-grid { display: grid; grid-template-columns: 1fr; gap: 22px; }

    .uv-table th, .uv-table td { text-align: center; vertical-align: middle; }
    .uv-table th {
        background:#f3f5f7;
        text-transform: uppercase; letter-spacing: .03em;
        font-weight: 800; font-size: 1.05rem;
    }
    .uv-table td { padding: 16px 10px; }

    /* Números grandes y centrados */
    .uv-count { font-size: clamp(2rem, 3.2vw + .8rem, 3rem); font-weight: 900; line-height: 1; }
    .uv-mini { font-size: .78rem; color:#6c757d; margin-top: 6px; }

    /* Tabla de nombres (5 columnas) */
    .uv-nombres th { background:#f3f5f7; text-transform: uppercase; font-weight: 800; }
    .uv-col { max-height: 320px; overflow:auto; text-align:left; }
    .uv-col ul { padding-left: 18px; margin-bottom: 0; }
</style>

<div class="container uv-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Reporte semanal</h2>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('admin.cupos.exportar-semana') }}">
                <input type="hidden" name="convocatoria_id" value="{{ $convocatoria->id }}">
                <input type="hidden" name="lunes" value="{{ $lunes->toDateString() }}">
                <button class="btn btn-outline-success btn-sm">Exportar CSV</button>
            </form>
            <a class="btn btn-secondary btn-sm"
               href="{{ route('admin.cupos.index', ['convocatoria_id'=>$convocatoria->id, 'lunes'=>$lunes->toDateString()]) }}">
                Volver
            </a>
        </div>
    </div>

    <div class="mb-3 text-muted">
        Convocatoria: <strong>{{ $convocatoria->nombre }}</strong>
        · Semana: {{ $lunes->format('Y-m-d') }} — {{ $lunes->copy()->addDays(6)->format('Y-m-d') }}
    </div>

    @php
        // Fallbacks y helpers
        $cuposSemana = $cuposSemana ?? collect(); // por si no lo pasan en el controlador
        $sedes = ['caicedonia','sevilla'];
        $diasISO = [1,2,3,4,5];
        $labels = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes'];

        $key = fn(string $sede, int $dISO) => $lunes->copy()->addDays($dISO-1)->toDateString().'|'.$sede;

        // Conteo de asignados por día/sede desde las asignaciones ya agrupadas (fecha|sede)
        $countFor = function(string $sede, int $dISO) use ($key, $asignaciones) {
            return ($asignaciones->get($key($sede, $dISO)) ?? collect())->count();
        };

        // Capacidad por día/sede: intenta desde CupoDiario; si no existe, usa capacidad diaria definida en la convocatoria
        $capFor = function(string $sede, int $dISO) use ($key, $cuposSemana, $convocatoria) {
            $c = $cuposSemana->get($key($sede, $dISO));
            if ($c) return (int) $c->capacidad;
            return (int) ($sede === 'caicedonia' ? ($convocatoria->cupos_caicedonia ?? 0) : ($convocatoria->cupos_sevilla ?? 0));
        };

        // Lista de asignados (colección) para renderizar nombres
        $listFor = function(string $sede, int $dISO) use ($key, $asignaciones) {
            return $asignaciones->get($key($sede, $dISO)) ?? collect();
        };
    @endphp

    <div class="uv-grid">
        @foreach($sedes as $sede)
            <div class="uv-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="uv-title">{{ ucfirst($sede) }}</div>
                    <div class="uv-sub">Semana {{ $lunes->format('d/m') }}–{{ $lunes->copy()->addDays(4)->format('d/m') }}</div>
                </div>

                {{-- Resumen: asignados/capacidad por día (5 columnas) --}}
                <div class="table-responsive mb-3">
                    <table class="table table-bordered uv-table mb-0">
                        <thead>
                            <tr>
                                @foreach($diasISO as $d)
                                    <th>{{ $labels[$d] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @foreach($diasISO as $d)
                                    @php
                                        $n = $countFor($sede,$d);
                                        $cap = $capFor($sede,$d);
                                    @endphp
                                    <td>
                                        <div class="uv-count">{{ $n }} / {{ $cap }}</div>
                                        <div class="uv-mini">asignados / capacidad</div>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Detalle: lista de nombres por día (5 columnas) --}}
                <div class="table-responsive">
                    <table class="table table-bordered uv-nombres">
                        <thead>
                            <tr>
                                @foreach($diasISO as $d)
                                    <th class="text-center">{{ $labels[$d] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @foreach($diasISO as $d)
                                    @php $items = $listFor($sede,$d); @endphp
                                    <td class="uv-col">
                                        @if($items->isEmpty())
                                            <span class="text-muted">Sin asignados.</span>
                                        @else
                                            <ul>
                                                @foreach($items as $row)
                                                    <li>{{ $row->user?->name }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        @endforeach
    </div>
</div>
@endsection