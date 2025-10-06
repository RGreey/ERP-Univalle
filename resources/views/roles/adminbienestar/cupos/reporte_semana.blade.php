@extends('layouts.app')
@section('title','Reporte semanal de cupos')

@section('content')
<style>
    .uv-card { background:#fff; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,.06); padding:18px; }
    .day-col { vertical-align: top; }
    .day-col ul { padding-left: 18px; margin: 0; }
    .day-col li { margin: 2px 0; }
    .section-title { font-weight:700; font-size:1.05rem; }
</style>

@php
    $dias = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes'];
@endphp

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Reporte semanal de cupos</h2>
        <a href="{{ route('admin.cupos.index') }}" class="btn btn-outline-dark btn-sm">Volver a Cupos</a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <label class="form-label">Convocatoria</label>
            <select name="convocatoria_id" class="form-select" onchange="this.form.submit()">
                @foreach($convocatorias as $c)
                    <option value="{{ $c->id }}" @selected($convId==$c->id)>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Lunes de la semana</label>
            <input type="date" name="lunes" class="form-control" value="{{ $lunes->toDateString() }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Sede</label>
            <select name="sede" class="form-select">
                <option value="">Ambas</option>
                <option value="caicedonia" @selected($sede==='caicedonia')>Caicedonia</option>
                <option value="sevilla" @selected($sede==='sevilla')>Sevilla</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <label class="form-label">&nbsp;</label>
            <button class="btn btn-outline-secondary">Ver</button>
        </div>
    </form>

    <div class="d-flex justify-content-end mb-2">
        <form method="GET" action="{{ route('admin.cupos.exportar-semana-xls') }}">
            <input type="hidden" name="convocatoria_id" value="{{ $convId }}">
            <input type="hidden" name="lunes" value="{{ $lunes->toDateString() }}">
            <input type="hidden" name="sede" value="{{ $sede }}">
            <button class="btn btn-outline-success">Exportar Excel (.xls)</button>
        </form>
    </div>

    @forelse($dataPorSede as $sd => $map)
        <div class="uv-card mb-3">
            <div class="section-title mb-2">Sede: {{ ucfirst($sd) }} — Semana {{ $lunes->format('Y-m-d') }} a {{ $lunes->copy()->addDays(6)->format('Y-m-d') }}</div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach($dias as $k => $name)
                                <th>{{ $name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($dias as $k => $name)
                                <td class="day-col">
                                    @if(!empty($map[$k]))
                                        <ul>
                                            @foreach($map[$k] as $nom)
                                                <li>{{ $nom }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">Sin asignaciones</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No hay datos para este filtro.</div>
    @endforelse
</div>
@endsection