@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Detalle de Actividad de Mantenimiento</h2>
                <div>
                    <a href="{{ route('mantenimiento.edit', $actividad) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="{{ route('mantenimiento.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <!-- Información de la Actividad -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Información General</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Actividad:</th>
                                    <td><strong>{{ $actividad->actividad }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Descripción:</th>
                                    <td>{{ $actividad->descripcion ?: 'Sin descripción' }}</td>
                                </tr>
                                <tr>
                                    <th>Frecuencia:</th>
                                    <td><span class="badge bg-info">{{ ucfirst($actividad->frecuencia) }}</span></td>
                                </tr>
                                <tr>
                                    <th>Rango de Fechas:</th>
                                    <td>{{ $actividad->rango_fechas }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Estado:</th>
                                    <td>
                                        @if($actividad->realizado)
                                            <span class="badge bg-success">✓ Realizada</span>
                                        @else
                                            <span class="badge bg-warning">⏳ Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Proveedor:</th>
                                    <td>
                                        @if($actividad->proveedor)
                                            <span class="badge bg-warning">{{ $actividad->proveedor }}</span>
                                        @else
                                            <span class="badge bg-primary">Servicios Generales</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Responsable:</th>
                                    <td>{{ $actividad->responsable }}</td>
                                </tr>
                                <tr>
                                    <th>Orden:</th>
                                    <td>{{ $actividad->orden }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Semanas del Año -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Semanas del Año {{ $anioActual }}</h5>
                    <form action="{{ route('mantenimiento.generar-semanas', $actividad) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="anio" value="{{ $anioActual }}">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-sync"></i> Regenerar Semanas
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> ¿Cómo marcar las semanas?</h6>
                        <ul class="mb-0">
                            <li><strong>✓ Ejecutada:</strong> Haz clic en el botón <i class="fas fa-check"></i> para marcar la semana como completada</li>
                            <li><strong>○ Pendiente:</strong> Haz clic en el botón <i class="fas fa-undo"></i> para marcar la semana como pendiente</li>
                            <li><strong>Regenerar Semanas:</strong> Este botón elimina todas las semanas del año y las crea nuevamente (útil para reiniciar el plan)</li>
                        </ul>
                    </div>
                    @if($semanasPorMes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Mes</th>
                                        <th>Semana 1</th>
                                        <th>Semana 2</th>
                                        <th>Semana 3</th>
                                        <th>Semana 4</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($semanasPorMes as $mes => $semanas)
                                    <tr>
                                        <td class="table-primary">
                                            <strong>{{ $semanas->first()->mes_nombre }}</strong>
                                        </td>
                                        @for($semana = 1; $semana <= 4; $semana++)
                                            @php
                                                $semanaActual = $semanas->where('semana', $semana)->first();
                                            @endphp
                                            <td class="text-center {{ $semanaActual && $semanaActual->ejecutado ? 'table-success' : '' }}">
                                                @if($semanaActual)
                                                    @if($semanaActual->ejecutado)
                                                        <div class="mb-1">
                                                            <span class="badge bg-success">✓ Ejecutada</span>
                                                        </div>
                                                        @if($semanaActual->fecha_ejecucion)
                                                            <small class="d-block">{{ $semanaActual->fecha_ejecucion->format('d/m/Y') }}</small>
                                                        @endif
                                                        <form action="{{ route('mantenimiento.semana.marcar-pendiente', $semanaActual) }}" method="POST" class="mt-1">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Marcar como pendiente">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <div class="mb-1">
                                                            <span class="badge bg-secondary">○ Pendiente</span>
                                                        </div>
                                                        <form action="{{ route('mantenimiento.semana.marcar-ejecutada', $semanaActual) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como ejecutada">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay semanas generadas para este año</h5>
                            <p class="text-muted">Genera las semanas para poder marcar las ejecuciones.</p>
                            <form action="{{ route('mantenimiento.generar-semanas', $actividad) }}" method="POST">
                                @csrf
                                <input type="hidden" name="anio" value="{{ $anioActual }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Generar Semanas
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
