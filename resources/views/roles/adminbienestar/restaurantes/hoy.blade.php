@extends('layouts.app')
@section('title','Asistencias de hoy')

@section('content')
<div class="container">
<h3 class="mb-3">Asistencias de hoy</h3>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

<div class="mb-2 small text-muted">
    Fecha: {{ $hoy ?? now()->format('Y-m-d') }} |
    @isset($corte) Corte: {{ $corte->format('H:i') }} @endisset
</div>

@if(empty($items) || $items->isEmpty())
    <div class="alert alert-warning">No hay registros para hoy (o no hay sede seleccionada / asignada).</div>
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
                <th>Marcaje</th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $a)
                @php
                $estado = $a->asistencia_estado ?? 'pendiente';
                if ($estado === 'no_show') $estado = 'inasistencia';
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
                <td>
                    @if(($puedeMarcar ?? false))
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf
                        @if($estado !== 'asistio')
                        <input type="hidden" name="accion" value="asistio">
                        <button class="btn btn-sm btn-success">Marcar asistió</button>
                        @else
                        <input type="hidden" name="accion" value="pendiente">
                        <button class="btn btn-sm btn-outline-secondary">Revertir</button>
                        @endif
                    </form>
                    @else
                    <span class="text-muted small">Cerrado</span>
                    @endif
                </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    </div>

    <form method="POST" action="{{ route('restaurantes.asistencias.cerrar-dia') }}" class="mt-3">
    @csrf
    <input type="hidden" name="fecha" value="{{ $hoy }}">
    <button class="btn btn-outline-danger" @if(($puedeMarcar ?? false)) disabled title="Espera al corte" @endif>
        Cerrar día (marcar pendientes como inasistencia)
    </button>
    </form>
@endif
</div>
@endsection