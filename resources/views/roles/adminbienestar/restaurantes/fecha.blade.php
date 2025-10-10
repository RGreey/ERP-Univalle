@extends('layouts.app')
@section('title','Asistencias por fecha')

@section('content')
<div class="container">
<h3 class="mb-3">Asistencias por fecha</h3>

<form class="row g-2 mb-3" method="GET" action="{{ route('restaurantes.asistencias.fecha') }}">
    <div class="col-auto">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" class="form-control">
    </div>
    <div class="col-auto align-self-end">
    <button class="btn btn-primary">Filtrar</button>
    </div>
</form>

@if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

@if(empty($items) || $items->isEmpty())
    <div class="alert alert-warning">Sin registros.</div>
@else
    <div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>Sede</th>
                <th>Estudiante</th>
                <th>Correo</th>
                <th>Estado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $a)
                @php
                $estado = $a->asistencia_estado ?? 'pendiente';
                if ($estado==='no_show') $estado='inasistencia';
                $badge = match($estado){
                    'cancelado'=>'danger',
                    'asistio'=>'success',
                    'inasistencia'=>'warning',
                    default=>'secondary'
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
@endif
</div>
@endsection