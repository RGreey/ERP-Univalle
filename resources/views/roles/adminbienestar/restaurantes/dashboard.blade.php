@extends('layouts.app')
@section('title','Dashboard Restaurante')

@section('content')
<div class="container">
<h3 class="mb-3">Dashboard Restaurante</h3>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card mb-4">
    <div class="card-body">
    <p class="mb-2">Panel inicial del módulo. Aquí luego irá selección de sede / métricas.</p>
    <p class="text-muted small mb-0">Próximamente: selector persistente y estadísticas.</p>
    </div>
</div>

<div class="d-flex flex-wrap gap-2">
    <a href="{{ route('restaurantes.asistencias.hoy') }}" class="btn btn-primary">Asistencias de hoy</a>
    <a href="{{ route('restaurantes.asistencias.fecha') }}" class="btn btn-outline-secondary">Consultar por fecha</a>
</div>
</div>
@endsection