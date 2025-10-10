@extends('layouts.app')
@section('title','Asistencias por fecha')

@section('content')
<div class="container">
@include('pwa.restaurantes.partials.back')
<h3 class="mb-3">Asistencias por fecha</h3>

<form class="row g-2 mb-3" method="GET" action="{{ route('restaurantes.asistencias.fecha') }}">
    <div class="col-auto">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" class="form-control">
    </div>
    <div class="col-auto align-self-end">
    <button class="btn btn-primary">Filtrar</button>
    <a href="{{ route('restaurantes.asistencias.semana',['lunes'=>$fecha->startOfWeek()->toDateString()]) }}" class="btn btn-outline-secondary">Ver semana</a>
    </div>
</form>

@if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

@if($items->isEmpty())
    <div class="alert alert-warning">Sin registros.</div>
@else
    <div class="table-responsive">
    <table class="table table-sm align-middle">
        <thead>
        <tr>
            <th>Sede</th><th>Estudiante</th><th>Correo</th><th>Estado</th>
            @if($editable)<th class="text-end">Acciones</th>@endif
        </tr>
        </thead>
        <tbody>
        @foreach($items as $a)
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
            @if($editable)
                <td class="text-end">
                @if($estado!=='cancelado')
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                    @csrf <input type="hidden" name="accion" value="asistio">
                    <button class="btn btn-sm btn-success">Asistió</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                    @csrf <input type="hidden" name="accion" value="pendiente">
                    <button class="btn btn-sm btn-outline-secondary">Pendiente</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                    @csrf <input type="hidden" name="accion" value="inasistencia">
                    <button class="btn btn-sm btn-warning">Inasistencia</button>
                    </form>
                @else
                    <span class="text-muted small">No editable</span>
                @endif
                </td>
            @endif
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
@endif
</div>
@endsection