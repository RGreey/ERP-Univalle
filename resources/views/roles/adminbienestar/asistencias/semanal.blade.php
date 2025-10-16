@extends('layouts.app')
@section('title','Asistencias semanales')

@section('content')
<div class="container">
<h3 class="mb-3">Asistencias semanales</h3>

<form class="row g-2 mb-3" method="GET" action="{{ route('admin.asistencias.semanal') }}">
    <div class="col-auto">
    <label class="form-label">Semana (cualquier fecha)</label>
    <input type="date" name="semana" class="form-control" value="{{ $lunes->toDateString() }}">
    </div>
    <div class="col-auto">
    <label class="form-label">Sede</label>
    <select name="sede" class="form-select">
        <option value="">Ambas</option>
        <option value="caicedonia" @selected($sede==='caicedonia')>Caicedonia</option>
        <option value="sevilla" @selected($sede==='sevilla')>Sevilla</option>
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
       href="{{ route('admin.asistencias.semanal.export', [
            'lunes'=>$lunes->toDateString(),
            'sede'=>$sede,
            'convocatoria_id'=>($convocatoriaId ?? null)
       ]) }}">Exportar Excel</a>
    </div>
</form>

<p class="text-muted">Semana {{ $lunes->format('Y-m-d') }} al {{ $domingo->format('Y-m-d') }} (solo L–V)</p>

<div class="table-responsive">
    <table class="table table-sm align-middle">
    <thead>
        <tr>
        <th style="min-width:220px">Estudiante</th>
        <th style="min-width:240px">Correo</th>
        @foreach($dias as $d)
            <th class="text-center">
            {{ $d->format('Y-m-d') }}<br>
            <small class="text-muted">{{ ucfirst($d->locale('es')->isoFormat('dddd')) }}</small>
            </th>
        @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $r)
        <tr>
            <td>{{ $r['nombre'] }}</td>
            <td class="text-muted">{{ $r['email'] }}</td>
            @foreach($dias as $d)
            @php
                $key = $d->toDateString();
                $estado = $r['dias'][$key] ?? null;
                $badge = match($estado){
                'cancelado'   => 'danger',
                'asistio'     => 'success',
                'inasistencia'=> 'warning',
                'pendiente'   => 'secondary',
                'festivo'     => 'info',
                default       => null
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
        <tr><td colspan="{{ 2 + count($dias) }}" class="text-muted p-3">Sin registros para los filtros seleccionados.</td></tr>
        @endforelse
    </tbody>
    </table>
</div>
</div>
@endsection