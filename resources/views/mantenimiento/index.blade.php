@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Plan de Mantenimiento Preventivo</h2>
                <div>
                    <a href="{{ route('mantenimiento.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Actividad
                    </a>
                    <a href="{{ route('mantenimiento.exportar-excel') }}" class="btn btn-warning">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </a>
                    <form action="{{ route('mantenimiento.cargar-predeterminadas') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-download"></i> Cargar Actividades Predeterminadas
                        </button>
                    </form>
                    <form id="formEliminarTodas" action="{{ route('mantenimiento.eliminar-todas.post') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="button" id="btnEliminarTodas" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Eliminar Todas
                        </button>
                    </form>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="alert alert-info mb-4">
                <h6><i class="fas fa-info-circle"></i> Información del Sistema de Mantenimiento</h6>
                <div class="row">
                    <div class="col-md-6">
                        <h6>¿Cómo marcar actividades?</h6>
                        <ul class="mb-0">
                            <li>Haz clic en <i class="fas fa-eye"></i> para ver los detalles de una actividad</li>
                            <li>En la vista de detalles, marca cada semana como ejecutada o pendiente</li>
                            <li>Usa los botones <i class="fas fa-check"></i> y <i class="fas fa-undo"></i> para cambiar el estado</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>¿Qué hace "Regenerar Semanas"?</h6>
                        <ul class="mb-0">
                            <li>Elimina todas las semanas del año actual para esa actividad</li>
                            <li>Crea nuevas semanas desde cero</li>
                            <li>Útil cuando necesitas reiniciar el plan de mantenimiento</li>
                            <li><strong>¡Cuidado!</strong> Esto borrará todas las marcas de ejecución existentes</li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ $estadisticas['total_actividades'] }}</h4>
                                    <p class="mb-0">Total Actividades</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-tasks fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ $estadisticas['realizadas'] }}</h4>
                                    <p class="mb-0">Realizadas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ $estadisticas['pendientes'] }}</h4>
                                    <p class="mb-0">Pendientes</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ $anioActual }}</h4>
                                    <p class="mb-0">Año Actual</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filtros</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="filtroFrecuencia" class="form-label">Frecuencia</label>
                            <select class="form-select" id="filtroFrecuencia">
                                <option value="">Todas</option>
                                <option value="anual">Anual</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="cuatrimestral">Cuatrimestral</option>
                                <option value="mensual">Mensual</option>
                                <option value="cuando_se_requiera">Cuando se requiera</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroEstado" class="form-label">Estado</label>
                            <select class="form-select" id="filtroEstado">
                                <option value="">Todos</option>
                                <option value="realizada">Realizada</option>
                                <option value="pendiente">Pendiente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroProveedor" class="form-label">Proveedor</label>
                            <select class="form-select" id="filtroProveedor">
                                <option value="">Todos</option>
                                <option value="servicios_generales">Servicios Generales</option>
                                <option value="externo">Externo</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">
                                <i class="fas fa-times"></i> Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Actividades -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actividades de Mantenimiento</h5>
                </div>
                <div class="card-body">
                    @if($actividades->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped" id="tablaActividades">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Actividad</th>
                                    <th>Frecuencia</th>
                                    <th>Rango de Fechas</th>
                                    <th>Realizado</th>
                                    <th>Proveedor</th>
                                    <th>Responsable</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($actividades as $actividad)
                                <tr data-frecuencia="{{ $actividad->frecuencia }}" 
                                    data-estado="{{ $actividad->realizado ? 'realizada' : 'pendiente' }}"
                                    data-proveedor="{{ $actividad->proveedor ? 'externo' : 'servicios_generales' }}">
                                    <td>{{ $actividad->orden }}</td>
                                    <td>
                                        <strong>{{ $actividad->actividad }}</strong>
                                        @if($actividad->descripcion)
                                        <br><small class="text-muted">{{ Str::limit($actividad->descripcion, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $frecuenciaDisplay = match($actividad->frecuencia) {
                                                'anual' => 'Anual',
                                                'trimestral' => 'Trimestral', 
                                                'cuatrimestral' => 'Cuatrimestral',
                                                'mensual' => 'Mensual',
                                                'cuando_se_requiera' => 'Cuando se Requiera',
                                                default => ucfirst($actividad->frecuencia)
                                            };
                                            $frecuenciaClass = match($actividad->frecuencia) {
                                                'anual' => 'bg-danger',
                                                'trimestral' => 'bg-warning',
                                                'cuatrimestral' => 'bg-info',
                                                'mensual' => 'bg-success',
                                                'cuando_se_requiera' => 'bg-secondary',
                                                default => 'bg-info'
                                            };
                                        @endphp
                                        <span class="badge {{ $frecuenciaClass }}">{{ $frecuenciaDisplay }}</span>
                                    </td>
                                    <td>{{ $actividad->rango_fechas }}</td>
                                    <td>
                                        @if($actividad->realizado)
                                            <span class="badge bg-success">✓ Realizada</span>
                                        @else
                                            <span class="badge bg-warning">⏳ Pendiente</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($actividad->proveedor)
                                            <span class="badge bg-warning">{{ $actividad->proveedor }}</span>
                                        @else
                                            <span class="badge bg-primary">Servicios Generales</span>
                                        @endif
                                    </td>
                                    <td>{{ $actividad->responsable }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('mantenimiento.show', $actividad) }}" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('mantenimiento.edit', $actividad) }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($actividad->realizado)
                                                <form action="{{ route('mantenimiento.marcar-pendiente', $actividad) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Marcar como pendiente">
                                                        <i class="fas fa-clock"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('mantenimiento.marcar-realizada', $actividad) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como realizada">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('mantenimiento.destroy', $actividad) }}" method="POST" class="d-inline form-eliminar-actividad">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay actividades de mantenimiento registradas</h5>
                        <p class="text-muted">Comienza creando una nueva actividad o carga las actividades predeterminadas.</p>
                        <a href="{{ route('mantenimiento.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear Primera Actividad
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// SweetAlert2 confirmaciones
document.addEventListener('DOMContentLoaded', function() {
    const btnEliminarTodas = document.getElementById('btnEliminarTodas');
    const formEliminarTodas = document.getElementById('formEliminarTodas');
    if (btnEliminarTodas && formEliminarTodas) {
        btnEliminarTodas.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Eliminar todas las actividades?',
                text: 'Esta acción borrará TODAS las actividades y TODAS las semanas de mantenimiento de la base de datos. Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar todo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    formEliminarTodas.submit();
                }
            });
        });
    }



    document.querySelectorAll('.form-eliminar-actividad').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Eliminar esta actividad?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    // Filtros
    const filtroFrecuencia = document.getElementById('filtroFrecuencia');
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroProveedor = document.getElementById('filtroProveedor');
    const tabla = document.getElementById('tablaActividades');
    const filas = tabla.querySelectorAll('tbody tr');

    function aplicarFiltros() {
        const frecuencia = filtroFrecuencia.value;
        const estado = filtroEstado.value;
        const proveedor = filtroProveedor.value;

        filas.forEach(fila => {
            let mostrar = true;

            if (frecuencia && fila.dataset.frecuencia !== frecuencia) {
                mostrar = false;
            }

            if (estado && fila.dataset.estado !== estado) {
                mostrar = false;
            }

            if (proveedor && fila.dataset.proveedor !== proveedor) {
                mostrar = false;
            }

            fila.style.display = mostrar ? '' : 'none';
        });
    }

    filtroFrecuencia.addEventListener('change', aplicarFiltros);
    filtroEstado.addEventListener('change', aplicarFiltros);
    filtroProveedor.addEventListener('change', aplicarFiltros);
});

function limpiarFiltros() {
    document.getElementById('filtroFrecuencia').value = '';
    document.getElementById('filtroEstado').value = '';
    document.getElementById('filtroProveedor').value = '';
    
    const filas = document.querySelectorAll('#tablaActividades tbody tr');
    filas.forEach(fila => fila.style.display = '');
}
</script>
@endsection
