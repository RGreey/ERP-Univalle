@extends('layouts.app')
@section('title','Asistencias mensuales')

@section('content')
<div class="container">
    <x-admin.asistencias.volver keep="sede,convocatoria_id" />
<h3 class="mb-3">Asistencias mensuales</h3>

<form class="row g-2 mb-3" method="GET" action="{{ route('admin.asistencias.mensual') }}">
    <div class="col-auto">
    <label class="form-label">Mes</label>
    <input type="month" name="mes" class="form-control" value="{{ substr($mes,0,7) }}">
    <div class="form-text">{{ $tituloMes }}</div>
    </div>
    <div class="col-auto">
    <label class="form-label">Sede</label>
    <select name="sede" class="form-select">
        <option value="">Ambas</option>
        <option value="caicedonia" @selected(($sede ?? '')==='caicedonia')>Caicedonia</option>
        <option value="sevilla" @selected(($sede ?? '')==='sevilla')>Sevilla</option>
    </select>
    </div>
    <div class="col-auto">
    <label class="form-label">Convocatoria</label>
    <select name="convocatoria_id" class="form-select">
        <option value="">Todas</option>
        @foreach($convocatorias as $c)
        <option value="{{ $c->id }}" @selected(($convocatoriaId ?? null)==$c->id)>{{ $c->nombre }}</option>
        @endforeach
    </select>
    </div>
    <div class="col-auto align-self-end">
    <button class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-auto align-self-end">
    <a class="btn btn-outline-success"
       href="{{ route('admin.asistencias.mensual.export', [
            'mes'=>substr($mes,0,7),
            'sede'=>$sede,
            'convocatoria_id'=>($convocatoriaId ?? null)
       ]) }}">Exportar Excel (mes)</a>
    </div>
</form>

@forelse($weeks as $w)
    <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><strong>{{ $w['label'] }}</strong></div>
        <span class="text-muted">({{ $w['inicio']->toDateString() }} al {{ $w['fin']->toDateString() }})</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th style="min-width:220px">Estudiante</th>
                <th style="min-width:240px">Correo</th>
                @foreach($w['dias'] as $d)
                <th class="text-center">
                    {{ $d->format('Y-m-d') }}<br>
                    <small class="text-muted">{{ ucfirst($d->locale('es')->isoFormat('dddd')) }}</small>
                </th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @forelse($w['rows'] as $r)
                <tr>
                <td>{{ $r['nombre'] }}</td>
                <td class="text-muted">{{ $r['email'] }}</td>
                @foreach($w['dias'] as $d)
                    @php
                    $key = $d->toDateString();
                    $estado = $r['dias'][$key] ?? null;
                    $badge = match($estado){
                        'cancelado'    => 'danger',
                        'asistio'      => 'success',
                        'inasistencia' => 'warning',
                        'pendiente'    => 'secondary',
                        'festivo'      => 'info',
                        default        => null
                    };
                    @endphp
                    <td class="text-center">
                    @if($estado && $badge)
                        <span class="badge bg-{{ $badge }}">{{ $estado }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                    </td>
                @endforeach
                </tr>
            @empty
                <tr>
                <td colspan="{{ 2 + count($w['dias']) }}" class="text-muted p-3">Sin registros en esta semana.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    </div>
@empty
    <div class="alert alert-info">No hay semanas con datos para el mes seleccionado.</div>
@endforelse
</div>
@endsection