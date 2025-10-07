@extends('layouts.app')
@section('title','Asistencias')

@section('content')
<div class="container">
<h3 class="mb-3">Asistencias</h3>

<div class="row g-3 mb-4">
    <div class="col-md-4">
    <div class="card h-100">
        <div class="card-body">
        <h5 class="card-title">Diario</h5>
        <p class="card-text">Lista de estudiantes asignados para una fecha con su estado.</p>
        <a class="btn btn-primary" href="{{ route('admin.asistencias.diario') }}">Abrir</a>
        </div>
    </div>
    </div>
    <div class="col-md-4">
    <div class="card h-100">
        <div class="card-body">
        <h5 class="card-title">Semanal</h5>
        <p class="card-text">Resumen por día de la semana (L–D) y sede.</p>
        <a class="btn btn-primary" href="{{ route('admin.asistencias.semanal') }}">Abrir</a>
        </div>
    </div>
    </div>
    <div class="col-md-4">
    <div class="card h-100">
        <div class="card-body">
        <h5 class="card-title">Mensual</h5>
        <p class="card-text">Totales por día y estado dentro de un mes.</p>
        <a class="btn btn-primary" href="{{ route('admin.asistencias.mensual') }}">Abrir</a>
        </div>
    </div>
    </div>
</div>

<h6 class="mb-2 text-muted">Resumen rápido de hoy ({{ $hoy }})</h6>
<div class="row g-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><strong>Pendiente</strong><div class="fs-4">{{ $totales['pendiente'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><strong>Cancelado</strong><div class="fs-4 text-danger">{{ $totales['cancelado'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><strong>Asistió</strong><div class="fs-4 text-success">{{ $totales['asistio'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><strong>No show</strong><div class="fs-4 text-warning">{{ $totales['no_show'] ?? 0 }}</div></div></div></div>
</div>
</div>
@endsection