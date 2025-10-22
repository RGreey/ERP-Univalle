@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title','Asistencias del mes')

@section('content')
<div class="container">
@include('pwa.restaurantes.partials.back')
@include('pwa.restaurantes.partials.context')

<h3 class="mb-3">Asistencias del mes</h3>

{{-- Filtros (GET) --}}
<form class="row g-2 mb-2" method="GET" action="{{ route('restaurantes.asistencias.mes') }}">
    <div class="col-7 col-sm-4 col-md-3">
    <label class="form-label">Mes</label>
    <input type="month" name="mes" class="form-control" value="{{ $mes->format('Y-m') }}">
    </div>
    <div class="col-5 col-sm-3 col-md-2 d-grid align-self-end">
    <button class="btn btn-primary">Ver</button>
    </div>
    <div class="col-12 col-sm-5 col-md-7 d-flex gap-2 align-self-end justify-content-sm-end mt-2 mt-sm-0">
    <a class="btn btn-outline-secondary"
        href="{{ route('restaurantes.asistencias.mes', ['mes'=>$mes->copy()->subMonth()->format('Y-m')]) }}">&laquo; Mes anterior</a>
    <a class="btn btn-outline-secondary"
        href="{{ route('restaurantes.asistencias.mes', ['mes'=>$mes->copy()->addMonth()->format('Y-m')]) }}">Mes siguiente &raquo;</a>
    <a class="btn btn-outline-success"
        href="{{ route('restaurantes.asistencias.mes.export', ['mes'=>$mes->format('Y-m')]) }}">Exportar Excel (mes)</a>
    </div>
</form>

{{-- Acciones (POST) separadas para evitar anidamiento --}}
<div class="mb-3 d-flex justify-content-end">
  <form method="POST" action="{{ route('restaurantes.asistencias.cerrar-mes') }}"
        onsubmit="return confirm('¿Cerrar todo el mes? Pendientes → inasistencia (no afecta festivos ni días futuros).');"
        class="d-inline">
      @csrf
      <input type="hidden" name="mes" value="{{ $mes->format('Y-m') }}">
      <button class="btn btn-outline-danger">Cerrar mes</button>
  </form>
</div>

@if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

@if(empty($semanas))
    <div class="alert alert-warning">Sin registros para este mes.</div>
@else
    @php
    $color = fn($estado) => match($estado) {
        'asistio' => 'success',
        'inasistencia' => 'warning',
        'cancelado' => 'danger',
        'festivo' => 'info',
        default => 'secondary',
    };
    @endphp

    @foreach($semanas as $sem)
    @php $r = $sem['resumen'] ?? []; @endphp
    <div class="card mb-3">
        <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
            <div class="fw-bold">Semana</div>
            <div class="text-muted small">
                {{ $sem['lunes']->toDateString() }} a {{ $sem['domingo']->toDateString() }}
            </div>
            <div class="mt-2">
                @foreach($r as $k=>$v)
                <span class="badge bg-{{ $color($k) }} me-1">{{ $k }}: {{ $v }}</span>
                @endforeach
                <span class="badge bg-dark">Total: {{ $sem['total'] }}</span>
            </div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary"
                    href="{{ route('restaurantes.asistencias.semana', ['lunes'=>$sem['lunes']->toDateString()]) }}">
                    Ver semana
                </a>
                <form method="POST" action="{{ route('restaurantes.asistencias.cerrar-semana') }}"
                      onsubmit="return confirm('¿Cerrar esta semana? Pendientes → inasistencia (no afecta festivos ni días futuros).');">
                    @csrf
                    <input type="hidden" name="lunes" value="{{ $sem['lunes']->toDateString() }}">
                    <button class="btn btn-sm btn-outline-danger">Cerrar semana</button>
                </form>
            </div>
        </div>
        </div>
    </div>
    @endforeach
@endif
</div>
@endsection