@extends('layouts.app')
@section('title','Resumen semanal')

@section('content')
<div class="container">
@include('pwa.restaurantes.partials.back')
<h3 class="mb-3">Resumen semanal</h3>

<form class="row g-2 mb-3" method="GET" action="{{ route('restaurantes.asistencias.semana') }}">
    <div class="col-auto">
    <label class="form-label">Semana (lunes)</label>
    <input type="date" name="lunes" value="{{ $lunes->toDateString() }}" class="form-control">
    </div>
    <div class="col-auto align-self-end">
    <button class="btn btn-primary">Cargar</button>
    <a href="{{ route('restaurantes.asistencias.semana.export',['lunes'=>$lunes->toDateString()]) }}" class="btn btn-outline-success">Exportar CSV</a>
    </div>
</form>

@if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

@if(empty($itemsAgrupados))
    <div class="alert alert-warning">Sin registros.</div>
@else
    <div class="mb-3">
    <strong>Resumen:</strong>
    @foreach($resumen as $k=>$v)
        <span class="badge bg-secondary me-1">{{ $k }}: {{ $v }}</span>
    @endforeach
    </div>
    @foreach($itemsAgrupados as $fecha => $grupo)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between">
        <div>
            <strong>{{ $fecha }}</strong>
        </div>
        <a href="{{ route('restaurantes.asistencias.fecha',['fecha'=>$fecha]) }}" class="btn btn-sm btn-outline-primary">Ver día</a>
        </div>
        <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
            <thead>
                <tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th>Estado</th></tr>
            </thead>
            <tbody>
                @foreach($grupo as $a)
                @php
                    $estado = $a->asistencia_estado ?? 'pendiente';
                    if ($estado==='no_show') $estado='inasistencia';
                    $badge = match($estado){
                    'cancelado'=>'danger','asistio'=>'success','inasistencia'=>'warning',default=>'secondary'
                    };
                @endphp
                <tr>
                    <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
                    <td>{{ $a->user?->name }}</td>
                    <td class="text-muted small">{{ $a->user?->email }}</td>
                    <td><span class="badge bg-{{ $badge }}">{{ $estado }}</span></td>
                </tr>
                @endforeach
            </tbody>
            </table>
        </div>
        </div>
    </div>
    @endforeach
@endif
</div>
@endsection