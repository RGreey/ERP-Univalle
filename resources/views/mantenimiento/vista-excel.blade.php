@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Plan de Mantenimiento Preventivo - Vista Excel</h2>
                <div>
                    <a href="{{ route('mantenimiento.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>

            @if($actividades->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Plan de Mantenimiento Preventivo - Año {{ $anioActual }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" style="font-size: 12px;">
                            <thead class="table-dark">
                                <tr>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 50px;">Orden</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 200px;">Actividad</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 100px;">Descripción</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 80px;">Frecuencia</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 100px;">Fecha Inicio</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 100px;">Fecha Final</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 80px;">Realizado</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 120px;">Proveedor</th>
                                    <th rowspan="2" class="text-center align-middle" style="min-width: 150px;">Responsable</th>
                                    <th colspan="48" class="text-center">Semanas del Año {{ $anioActual }}</th>
                                </tr>
                                <tr>
                                    @for($mes = 1; $mes <= 12; $mes++)
                                        @for($semana = 1; $semana <= 4; $semana++)
                                            <th class="text-center" style="min-width: 30px; font-size: 10px;">
                                                {{ $mes }}-{{ $semana }}
                                            </th>
                                        @endfor
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($actividades as $actividad)
                                <tr>
                                    <td class="text-center">{{ $actividad->orden }}</td>
                                    <td><strong>{{ $actividad->actividad }}</strong></td>
                                    <td>{{ Str::limit($actividad->descripcion, 50) ?: '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ ucfirst($actividad->frecuencia) }}</span>
                                    </td>
                                    <td class="text-center">{{ $actividad->fecha_inicio->format('d/m/Y') }}</td>
                                    <td class="text-center">{{ $actividad->fecha_final->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        @if($actividad->realizado)
                                            <span class="badge bg-success">✓</span>
                                        @else
                                            <span class="badge bg-warning">○</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($actividad->proveedor)
                                            <span class="badge bg-warning">{{ $actividad->proveedor }}</span>
                                        @else
                                            <span class="badge bg-primary">Servicios Generales</span>
                                        @endif
                                    </td>
                                    <td>{{ $actividad->responsable }}</td>
                                    
                                    @for($mes = 1; $mes <= 12; $mes++)
                                        @for($semana = 1; $semana <= 4; $semana++)
                                            @php
                                                $semanaActual = $actividad->semanas()
                                                    ->where('anio', $anioActual)
                                                    ->where('mes', $mes)
                                                    ->where('semana', $semana)
                                                    ->first();
                                            @endphp
                                            <td class="text-center {{ $semanaActual && $semanaActual->ejecutado ? 'table-success' : '' }}">
                                                @if($semanaActual && $semanaActual->ejecutado)
                                                    <span class="badge bg-success">✓</span>
                                                @else
                                                    <span class="text-muted">○</span>
                                                @endif
                                            </td>
                                        @endfor
                                    @endfor
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Leyenda</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><span class="badge bg-success">✓</span> Actividad realizada</li>
                                <li><span class="text-muted">○</span> Actividad pendiente</li>
                                <li><span class="badge bg-info">Anual</span> Frecuencia anual</li>
                                <li><span class="badge bg-info">Trimestral</span> Frecuencia trimestral</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><span class="badge bg-primary">Servicios Generales</span> Proveedor interno</li>
                                <li><span class="badge bg-warning">Externo</span> Proveedor externo</li>
                                <li><span class="badge bg-success">✓</span> Semana ejecutada</li>
                                <li><span class="text-muted">○</span> Semana pendiente</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4>{{ $actividades->count() }}</h4>
                            <p class="mb-0">Total Actividades</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h4>{{ $actividades->where('realizado', true)->count() }}</h4>
                            <p class="mb-0">Realizadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h4>{{ $actividades->where('realizado', false)->count() }}</h4>
                            <p class="mb-0">Pendientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h4>{{ $actividades->whereNotNull('proveedor')->count() }}</h4>
                            <p class="mb-0">Con Proveedor Externo</p>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-table fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay actividades de mantenimiento registradas</h5>
                    <p class="text-muted">Para ver el plan en formato Excel, primero debes crear actividades de mantenimiento.</p>
                    <a href="{{ route('mantenimiento.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Primera Actividad
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .card-header {
        display: none !important;
    }
    
    .table {
        font-size: 10px !important;
    }
    
    .table th, .table td {
        padding: 2px !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-body {
        padding: 0 !important;
    }
}

.table th, .table td {
    vertical-align: middle;
    padding: 4px;
}

.badge {
    font-size: 10px;
}
</style>
@endsection
