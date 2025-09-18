@extends('layouts.app')

@section('title', 'Gestión de Backups')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-database me-2"></i>
                        Gestión de Backups de Base de Datos
                    </h4>
                    <button class="btn btn-primary" onclick="crearBackup()">
                        <i class="fas fa-plus me-2"></i>
                        Crear Nuevo Backup
                    </button>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Información:</strong> Los backups son copias completas de la base de datos en formato SQL. 
                        Se recomienda crear backups regularmente para proteger la información del sistema.
                    </div>

                    @if(count($backups) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre del Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Fecha de Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backups as $backup)
                                    <tr>
                                        <td>
                                            <i class="fas fa-file-archive text-primary me-2"></i>
                                            {{ $backup['name'] }}
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $backup['size'] }}</span>
                                        </td>
                                        <td>{{ $backup['date'] }}</td>
                                        <td>
                                            <button class="btn btn-success btn-sm" onclick="descargarBackup('{{ $backup['name'] }}')">
                                                <i class="fas fa-download me-1"></i>
                                                Descargar
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="eliminarBackup('{{ $backup['name'] }}')">
                                                <i class="fas fa-trash me-1"></i>
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-database fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay backups disponibles</h5>
                            <p class="text-muted">Crea tu primer backup haciendo clic en el botón "Crear Nuevo Backup"</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de progreso -->
<div class="modal fade" id="progressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Creando backup...</span>
                </div>
                <h5>Creando backup de la base de datos</h5>
                <p class="text-muted mb-0">Por favor espera, esto puede tomar unos momentos...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
function crearBackup() {
    Swal.fire({
        title: '¿Crear nuevo backup?',
        text: 'Se creará una copia completa de la base de datos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, crear backup',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar modal de progreso
            var progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
            progressModal.show();

            fetch('{{ route("admin.backups.create") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                progressModal.hide();
                
                if (data.success) {
                    Swal.fire({
                        title: '¡Backup creado!',
                        text: `El backup "${data.filename}" se ha creado exitosamente.`,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                progressModal.hide();
                Swal.fire({
                    title: 'Error',
                    text: 'Error inesperado al crear el backup.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

function descargarBackup(filename) {
    window.location.href = `{{ route("admin.backups.download", "") }}/${filename}`;
}

function eliminarBackup(filename) {
    Swal.fire({
        title: '¿Eliminar backup?',
        text: `Se eliminará permanentemente el archivo "${filename}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ route("admin.backups.delete", "") }}/${filename}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Error inesperado al eliminar el backup.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}
</script>
@endpush
