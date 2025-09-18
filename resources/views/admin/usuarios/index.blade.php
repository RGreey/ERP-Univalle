@extends('layouts.app')

@section('content')
<style>
    /* Estilos para paginación personalizada sin iconos */
    .pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.25rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .pagination .page-item {
        margin: 0 0.125rem;
    }
    
    .pagination .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 2.5rem;
        height: 2.5rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.5;
        border-radius: 0.25rem;
        border: 1px solid #dee2e6;
        background-color: #fff;
        color: #0d6efd;
        text-decoration: none;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
    }
    
    .pagination .page-link:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
        color: #0a58ca;
        text-decoration: none;
    }
    
    .pagination .page-link:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        outline: none;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
        font-weight: 600;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        pointer-events: none;
        background-color: #fff;
        border-color: #dee2e6;
        opacity: 0.6;
    }
    
    /* Información de resultados */
    .pagination-info {
        color: #6c757d;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
    
    /* Mejorar la responsividad de la tabla */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table th,
    .table td {
        white-space: nowrap;
        min-width: 120px;
    }
    
    .table th:first-child,
    .table td:first-child {
        min-width: 150px;
    }
    
    .table th:last-child,
    .table td:last-child {
        min-width: 200px;
    }
    
    /* Estilos para los badges */
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Estilos para los botones de acción */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
    
    /* Asegurar que los formularios inline no se desborden */
    .d-inline {
        display: inline-block !important;
    }
    
    /* Responsive para pantallas pequeñas */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
    }
</style>

<div class="container">
    <h2 class="mb-4">Administración de Usuarios</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('admin.usuarios.create') }}" class="btn btn-success me-2">
                <i class="fa fa-user-plus me-1"></i> Crear usuario
            </a>
            <!-- Debug: Rol actual del usuario: {{ auth()->user()->rol }} -->
            @if(in_array(auth()->user()->rol, ['Administrativo', 'CooAdmin', 'AuxAdmin']) || auth()->user()->email === 'soporte.caicedonia@correounivalle.edu.co')
                <a href="{{ route('admin.backups.index') }}" class="btn btn-warning">
                    <i class="fas fa-database me-1"></i> Gestionar Backups
                </a>
            @else
                <!-- El usuario no tiene rol de administrador y no es el usuario especial, su rol es: {{ auth()->user()->rol }} -->
            @endif
        </div>
    </div>
    <form method="GET" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o correo" value="{{ request('search') }}">
        </div>
        <div class="col-auto">
            <select name="rol" class="form-select">
                <option value="">Todos los roles</option>
                <option value="Estudiante" {{ request('rol') == 'Estudiante' ? 'selected' : '' }}>Estudiante</option>
                <option value="Profesor" {{ request('rol') == 'Profesor' ? 'selected' : '' }}>Profesor</option>
                <option value="Administrativo" {{ request('rol') == 'Administrativo' ? 'selected' : '' }}>Administrativo</option>
                <option value="CooAdmin" {{ request('rol') == 'CooAdmin' ? 'selected' : '' }}>CooAdmin</option>
                <option value="AuxAdmin" {{ request('rol') == 'AuxAdmin' ? 'selected' : '' }}>AuxAdmin</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol actual</th>
                    <th>Rol solicitado</th>
                    <th>Verificado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->name }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td><span class="badge bg-secondary">{{ $usuario->rol }}</span></td>
                        <td>
                            @if($usuario->rol_solicitado && $usuario->rol !== $usuario->rol_solicitado)
                                <span class="badge bg-warning text-dark">{{ $usuario->rol_solicitado }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($usuario->email_verified_at)
                                <span class="badge bg-success">Sí</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                        <td>
                            @if($usuario->rol_solicitado && $usuario->rol !== $usuario->rol_solicitado && in_array($usuario->rol_solicitado, ['Profesor','Administrativo']))
                                <form action="{{ route('admin.usuarios.aprobarRol', $usuario->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Aprobar rol</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.usuarios.edit', $usuario->id) }}" class="btn btn-sm btn-primary">Editar</a>
                            <form action="{{ route('admin.usuarios.destroy', $usuario->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay usuarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        @if($usuarios->hasPages())
            <nav aria-label="Navegación de páginas">
                <ul class="pagination justify-content-center">
                    {{-- Botón Anterior --}}
                    @if($usuarios->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link">Anterior</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $usuarios->previousPageUrl() }}" rel="prev">Anterior</a>
                        </li>
                    @endif

                    {{-- Números de página --}}
                    @foreach($usuarios->getUrlRange(1, $usuarios->lastPage()) as $page => $url)
                        @if($page == $usuarios->currentPage())
                            <li class="page-item active">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach

                    {{-- Botón Siguiente --}}
                    @if($usuarios->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $usuarios->nextPageUrl() }}" rel="next">Siguiente</a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link">Siguiente</span>
                        </li>
                    @endif
                </ul>
            </nav>
            
            {{-- Información de resultados --}}
            <div class="text-center pagination-info">
                Mostrando {{ $usuarios->firstItem() ?? 0 }} al {{ $usuarios->lastItem() ?? 0 }} de {{ $usuarios->total() }} resultados
            </div>
        @endif
    </div>
</div>
@endsection 