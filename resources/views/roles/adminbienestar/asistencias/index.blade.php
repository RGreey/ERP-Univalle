@extends('layouts.app')
@section('title','Asistencias')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Asistencias</h3>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.subsidio.admin.dashboard') }}">Ver dashboard</a>
            <a class="btn btn-outline-danger" href="{{ route('admin.asistencias.cancelaciones') }}">Cancelaciones</a>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Diario</h5>
                    <p class="card-text text-muted">Lista de estudiantes asignados para una fecha con su estado.</p>
                    <a class="btn btn-primary" href="{{ route('admin.asistencias.diario') }}">Abrir</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Semanal</h5>
                    <p class="card-text text-muted">Resumen por día de la semana (L–V) y sede.</p>
                    <a class="btn btn-primary" href="{{ route('admin.asistencias.semanal') }}">Abrir</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Mensual</h5>
                    <p class="card-text text-muted">Totales por día y estado dentro de un mes.</p>
                    <a class="btn btn-primary" href="{{ route('admin.asistencias.mensual') }}">Abrir</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumen rápido de hoy --}}
    <h6 class="mb-2 text-muted">Resumen rápido de hoy ({{ $hoy }})</h6>
    <div class="row g-3">
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Pendiente</div>
                    <div class="fs-4 fw-bold">{{ $totales['pendiente'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Cancelado</div>
                    <div class="fs-4 fw-bold text-danger">{{ $totales['cancelado'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Asistió</div>
                    <div class="fs-4 fw-bold text-success">{{ $totales['asistio'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Inasistencia</div>
                    <div class="fs-4 fw-bold text-warning">{{ $totales['inasistencia'] ?? ($totales['no_show'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Festivo</div>
                    <div class="fs-4 fw-bold text-info">{{ $totales['festivo'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Total</div>
                    @php
                        $sum = ($totales['pendiente'] ?? 0)+($totales['cancelado'] ?? 0)+($totales['asistio'] ?? 0)+($totales['inasistencia'] ?? ($totales['no_show'] ?? 0))+($totales['festivo'] ?? 0);
                    @endphp
                    <div class="fs-4 fw-bold">{{ $sum }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection