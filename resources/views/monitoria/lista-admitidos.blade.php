@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa-solid fa-users me-2"></i>
                        Lista de Admitidos - Gestión de Cédulas
                    </h4>
                    <div>
                        <a href="{{ route('lista-admitidos.pdf') }}" class="btn btn-light btn-sm">
                            <i class="fa-solid fa-download me-1"></i>
                            Descargar PDF
                        </a>
                        <a href="{{ route('admin.gestionMonitores') }}" class="btn btn-outline-light btn-sm ms-2">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fa-solid fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Información del documento -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fa-solid fa-info-circle me-2"></i>
                                    Información del documento
                                </h6>
                                <p class="mb-1"><strong>Convocatoria:</strong> Convocatoria Monitorías</p>
                                <p class="mb-1"><strong>Periodo:</strong> {{ $monitores->first()->periodo_academico_nombre ?? 'N/A' }}</p>
                                <p class="mb-0"><strong>Total de monitores:</strong> {{ $monitores->count() }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                    Nota importante
                                </h6>
                                <p class="mb-0">
                                    Complete las cédulas de todos los monitores antes de generar el PDF final.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Vista previa del documento -->
                    <div class="mb-4">
                        <h5 class="text-uppercase fw-bold mb-3">
                            <i class="fa-solid fa-eye me-2"></i>
                            Vista Previa del Documento
                        </h5>
                        
                        <!-- Título del documento -->
                        <div class="text-start mb-3">
                            <h6 class="fw-bold text-uppercase">
                                Convocatoria Aspirantes a Monitorías de Docencia y Administrativas
                            </h6>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-3">
                            <p class="text-justify small">
                                Con base en la Resolución No.040, de Julio 15 de 2002, del Consejo Superior de la Universidad del Valle, 
                                se realiza convocatoria a monitorías administrativas y académicas, para el periodo académico 
                                <strong>{{ $monitores->first()->periodo_academico_nombre ?? '2025-I' }}</strong>.
                            </p>
                            <p class="fw-bold small">Se publica la lista de admitidos.</p>
                        </div>

                        <!-- Tabla de vista previa -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 35%;">DEPENDENCIA</th>
                                        <th style="width: 15%;" class="text-center">HORAS A<br>LA<br>SEMANA</th>
                                        <th style="width: 10%;" class="text-center">VACANTE</th>
                                        <th style="width: 20%;" class="text-center">CC MONITOR</th>
                                        <th style="width: 20%;" class="text-center">FECHA DE<br>INICIO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($monitores as $monitor)
                                        <tr>
                                            <td>{{ $monitor->dependencia_nombre ?? 'N/A' }}</td>
                                            <td class="text-center">{{ $monitor->monitoria_intensidad ?? 'N/A' }}</td>
                                            <td class="text-center">1</td>
                                            <td class="text-center">
                                                @if($monitor->user_cedula)
                                                    {{ $monitor->user_cedula }}
                                                @else
                                                    <span class="text-warning small">
                                                        <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                                        Sin cédula
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ $monitor->fecha_vinculacion ? date('d/m/Y', strtotime($monitor->fecha_vinculacion)) : 'N/A' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <i class="fa-solid fa-info-circle me-2"></i>
                                                No hay monitores aprobados para gestionar.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Gestión de cédulas -->
                    <div>
                        <h5 class="text-uppercase fw-bold mb-3">
                            <i class="fa-solid fa-id-card me-2"></i>
                            Gestión de Cédulas
                        </h5>
                        
                        <form action="{{ route('lista-admitidos.actualizar-cedulas') }}" method="POST">
                            @csrf
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Monitor</th>
                                            <th>Monitoría</th>
                                            <th>Dependencia</th>
                                            <th>Periodo Académico</th>
                                            <th>Horas Semanales</th>
                                            <th>Cédula Actual</th>
                                            <th>Nueva Cédula</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($monitores as $monitor)
                                            <tr>
                                                <td>
                                                    <strong>{{ $monitor->user_name ?? 'N/A' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $monitor->user_email ?? 'N/A' }}</small>
                                                </td>
                                                <td>{{ $monitor->monitoria_nombre ?? 'N/A' }}</td>
                                                <td>{{ $monitor->dependencia_nombre ?? 'N/A' }}</td>
                                                <td class="text-center">{{ $monitor->periodo_academico_nombre ?? 'N/A' }}</td>
                                                <td class="text-center">{{ $monitor->monitoria_intensidad ?? 'N/A' }}</td>
                                                <td class="text-center">
                                                    @if($monitor->user_cedula)
                                                        <span class="badge bg-success">{{ $monitor->user_cedula }}</span>
                                                    @else
                                                        <span class="badge bg-warning">Sin cédula</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="hidden" name="cedulas[{{ $loop->index }}][monitor_id]" value="{{ $monitor->id }}">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="cedulas[{{ $loop->index }}][cedula]" 
                                                           value="{{ $monitor->user_cedula ?? '' }}"
                                                           placeholder="Ingrese cédula"
                                                           maxlength="20">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    <i class="fa-solid fa-info-circle me-2"></i>
                                                    No hay monitores aprobados para gestionar.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($monitores->count() > 0)
                                <div class="text-center mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-save me-2"></i>
                                        Guardar Cédulas
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Cédulas Actualizadas!',
            text: '{{ session('success') }}',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#198754'
        });
    </script>
@endif

@if(session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error al Actualizar',
            text: '{{ session('error') }}',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
        });
    </script>
@endif

@endsection
