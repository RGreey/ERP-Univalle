@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fa-solid fa-screwdriver-wrench me-2"></i>Gestión de Novedades de Mantenimiento</h2>
        <a href="#" class="btn btn-success shadow" data-bs-toggle="modal" data-bs-target="#crearNovedadModal">
            <i class="fa-solid fa-plus"></i> Nueva novedad
        </a>
    </div>
    <form method="GET" class="mb-3">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="estado" class="col-form-label fw-semibold">Filtrar por estado:</label>
            </div>
            <div class="col-auto">
                <select name="estado" id="estado" class="form-select" onchange="this.form.submit()">
                    <option value="" {{ request('estado') == '' ? 'selected' : '' }}>Todas</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="mantenimiento realizado" {{ request('estado') == 'mantenimiento realizado' ? 'selected' : '' }}>Mantenimiento realizado</option>
                    <option value="cerrada" {{ request('estado') == 'cerrada' ? 'selected' : '' }}>Cerrada</option>
                </select>
            </div>
        </div>
    </form>
    @if(session('success'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    },
                    buttonsStyling: false
                });
            });
        </script>
    @endif
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0" style="border-radius:12px;overflow:hidden;">
                    <thead class="table-primary text-center align-middle" style="font-size:1.05rem;">
                        <tr>
                            <th style="border-top-left-radius:12px;">Título</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Lugar</th>
                            <th>Ubicación detallada</th>
                            <th>Fecha solicitud</th>
                            <th style="border-top-right-radius:12px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($novedades as $novedad)
                            <tr style="transition:background 0.2s;">
                                <td class="fw-semibold text-primary">{{ $novedad->titulo }}</td>
                                <td><span class="badge bg-secondary">{{ $novedad->tipo }}</span></td>
                                <td>
                                    <span class="badge 
                                        @if($novedad->estado_novedad == 'pendiente') bg-warning text-dark
                                        @elseif($novedad->estado_novedad == 'mantenimiento realizado') bg-primary
                                        @elseif($novedad->estado_novedad == 'cerrada') bg-success
                                        @else bg-secondary @endif
                                    ">
                                        <i class="fa-solid fa-circle me-1"></i>{{ ucfirst($novedad->estado_novedad) }}
                                    </span>
                                </td>
                                <td><i class="fa-solid fa-building me-1 text-secondary"></i>{{ $novedad->lugar->nombreLugar ?? '-' }}</td>
                                <td><i class="fa-solid fa-location-dot me-1 text-danger"></i>{{ $novedad->ubicacion_detallada }}</td>
                                <td><span class="text-muted"><i class="fa-regular fa-calendar me-1"></i>{{ $novedad->fecha_solicitud ? \Carbon\Carbon::parse($novedad->fecha_solicitud)->format('d/m/Y H:i') : '-' }}</span></td>
                                <td>
                                    <a href="{{ route('novedades.show', $novedad) }}" class="btn btn-sm btn-outline-primary shadow-sm"><i class="fa-solid fa-eye"></i> Ver</a>
                                    @if($novedad->estado_novedad === 'mantenimiento realizado' && auth()->id() === $novedad->usuario_id)
                                        <form action="{{ route('novedades.close', $novedad) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success shadow-sm"><i class="fa-solid fa-lock"></i> Cerrar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay novedades registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@include('novedades.partials.crear-modal')
@endsection 