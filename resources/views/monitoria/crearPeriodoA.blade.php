<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Período Académico</title>
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/71e9100085.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
<nav class="navbar navbar-expand-lg navbar-light navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="#">
            <img src="{{ asset('imagenes/header_logo.jpg') }}" alt="Logo de la universidad" style="max-height: 50px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">

            <a href="{{ route('dashboard') }}" class="btn btn-light custom-button" style="background-color: #ffffff; color: #000000; margin-right: 10px;">Inicio</a>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="dropdownEventos" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ffffff; color: #000000;">
                    Eventos
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownEventos" style="background-color: #ffffff;">
                    <li><a class="dropdown-item" href="{{ route('crearEvento') }}" style="color: #000000;">Crear Evento</a></li>
                    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Administrativo'))
                        <li><a class="dropdown-item" href="{{ route('consultarEventos') }}" style="color: #000000;">Consultar tus eventos</a></li>
                    @endif
                </ul>
            </div>
            <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMonitoria" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ffffff; color: #000000;">
                        Monitorias
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMonitoria" style="background-color: #ffffff;">
                            @if(auth()->user()->hasRole('CooAdmin')|| auth()->user()->hasRole('AuxAdmin'))
                            <li><a class="dropdown-item" href="{{ route('periodos.crear') }}" style="color: #000000;">Consultar Periodo Academico</a></li>
                            <li><a class="dropdown-item" href="{{ route('convocatoria.index') }}" style="color: #000000;">Crear Convocatoria</a></li>
                            @endif
                            @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin'))
                            <li><a class="dropdown-item" href="{{ route('admin.gestionMonitores') }}" style="color: #000000;">Consultar Monitores</a></li>
                            @endif
                                                    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('Profesor') || auth()->user()->hasRole('Administrativo'))
                            <li><a class="dropdown-item" href="{{ route('monitoria.index') }}" style="color: #000000;">Gestionar Monitorias</a></li>
                        @endif
                            @if(auth()->user()->hasRole('CooAdmin')|| auth()->user()->hasRole('AuxAdmin'))
                            <li><a class="dropdown-item" href="{{ route('postulados.index') }}" style="color: #000000;">Ver Postulados</a></li>
                            @endif
                        </ul>
            </div>
            @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Profesor') || auth()->user()->hasRole('Administrativo'))
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="dropdownNovedades" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ffffff; color: #000000;">
                                Mantenimiento
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownNovedades" style="background-color: #ffffff;">
                                <li><a class="dropdown-item" href="{{ route('novedades.index') }}" style="color: #000000;">Gestionar Novedades</a></li>
                                @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin'))
                                    <li><a class="dropdown-item" href="{{ route('mantenimiento.index') }}" style="color: #000000;">Plan de Mantenimiento Preventivo</a></li>
                                    <li><a class="dropdown-item" href="{{ route('evidencias-mantenimiento.index') }}" style="color: #000000;">Evidencias de Mantenimiento</a></li>
                                @endif
                            </ul>
                        </div>
                    @endif
            <a href="{{ route('calendario') }}" class="btn btn-light custom-button" style="background-color: #ffffff; color: #000000; margin-left: 10px;">
                Calendario <i class="fa-regular fa-calendar"></i>
            </a>
        </div>
        <div class="d-flex">
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-gear "></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"> Cerrar sesión  <i class="fa-solid fa-right-from-bracket"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title">Gestión de Períodos Académicos</h2>
            <p class="card-text">
                <i class="fas fa-info-circle"></i> 
                Aquí puedes crear y gestionar los períodos académicos para las convocatorias de monitorías.
            </p>
        </div>
    </div>

    <div class="mb-3">
        <button type="button" class="btn btn-primary mb-3 tt" data-bs-placement="top" title="Crear Periodo Academico" data-bs-toggle="modal" data-bs-target="#crearPeriodoModal">
            <i class="fa-solid fa-plus"></i> Crear Nuevo Período
        </button>
    </div>

    <!-- Modal para crear período académico -->
    <div class="modal fade" id="crearPeriodoModal" tabindex="-1" aria-labelledby="crearPeriodoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="crearPeriodoForm" action="{{ route('periodos.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="crearPeriodoModalLabel">Crear Nuevo Período Académico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <i class="fa-solid fa-circle-info tt" data-bs-placement="right" title="Es crucial que las fechas de cada período académico estén claramente definidas, asegurando que reflejen correctamente el inicio y el fin de cada semestre o intersemestral."></i>
                        </div>
                        <div class="mb-3">
                            <label for="tipoPeriodo" class="form-label">Tipo de Período</label>
                            <select class="form-select" id="tipoPeriodo" name="tipoPeriodo" required>
                                <option value="regular">Regular (Semestral)</option>
                                <option value="intersemestral">Intersemestral</option>
                            </select>
                            <div class="form-text">Seleccione si es un período regular (semestral) o intersemestral.</div>
                        </div>
                        <div class="mb-3">
                            <label for="fechaInicio" class="form-label">Fecha de Inicio</label>
                            <input type="date" id="fechaInicio" name="fechaInicio" class="form-control" required>
                            <div class="form-text">La fecha de inicio determina el semestre (I o II) del período académico regular.</div>
                        </div>
                        <div class="mb-3">
                            <label for="fechaFin" class="form-label">Fecha de Fin</label>
                            <input type="date" id="fechaFin" name="fechaFin" class="form-control" required>
                            <div class="form-text">La fecha de fin debe ser posterior a la fecha de inicio.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla para mostrar períodos académicos -->
    <div class="card mt-4">
        <div class="card-body">
            <h3 class="card-title">Listado de Períodos Académicos</h3>
            <div class="table-responsive">
                <table id="periodosTable" class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Fecha de Inicio</th>
                            <th>Fecha de Fin</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periodos ?? [] as $periodo)
                            <tr>
                                <td>{{ $periodo->nombre }}</td>
                                <td>
                                    @if($periodo->tipo === 'intersemestral')
                                        <span class="badge bg-info">Intersemestral</span>
                                    @else
                                        <span class="badge bg-primary">Regular</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($periodo->fechaInicio)->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($periodo->fechaFin)->format('d/m/Y') }}</td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="editarPeriodo({{ $periodo->id }}, '{{ $periodo->fechaInicio }}', '{{ $periodo->fechaFin }}', '{{ $periodo->tipo }}')" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar período -->
<div class="modal fade" id="editarPeriodoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editarPeriodoForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Editar Período Académico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_periodo_id">
                    <div class="mb-3">
                        <label for="edit_tipoPeriodo" class="form-label">Tipo de Período</label>
                        <select class="form-select" id="edit_tipoPeriodo" name="tipoPeriodo" required>
                            <option value="regular">Regular (Semestral)</option>
                            <option value="intersemestral">Intersemestral</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_fechaInicio" class="form-label">Fecha de Inicio</label>
                        <input type="date" class="form-control" id="edit_fechaInicio" name="fechaInicio" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_fechaFin" class="form-label">Fecha de Fin</label>
                        <input type="date" class="form-control" id="edit_fechaFin" name="fechaFin" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Inicializar DataTable
        $('#periodosTable').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay períodos académicos registrados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ períodos",
                "infoEmpty": "Mostrando 0 a 0 de 0 períodos",
                "infoFiltered": "(filtrado de _MAX_ períodos totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ períodos",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron períodos académicos",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            order: [[1, 'desc']], // Ordenar por fecha de inicio descendente
            pageLength: 10,
            responsive: true
        });

        // Evento submit del formulario de creación
        $('#crearPeriodoForm').on('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            
            $.ajax({
                url: this.action,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Período Académico creado exitosamente.'
                    }).then(() => {
                        $('#crearPeriodoModal').modal('hide');
                        $('#crearPeriodoForm')[0].reset();
                        // Recargar la página para mostrar los datos actualizados
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Hubo un problema al crear el período académico.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        });

        // Evento submit del formulario de edición
        $('#editarPeriodoForm').on('submit', function(event) {
            event.preventDefault();
            const periodoId = $('#edit_periodo_id').val();
            
            const formData = {
                fechaInicio: $('#edit_fechaInicio').val(),
                fechaFin: $('#edit_fechaFin').val(),
                tipoPeriodo: $('#edit_tipoPeriodo').val(),
                _token: '{{ csrf_token() }}',
                _method: 'PUT'
            };
            
            $.ajax({
                url: `/periodos/${periodoId}`,
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Período Académico actualizado exitosamente.'
                    }).then(() => {
                        $('#editarPeriodoModal').modal('hide');
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Hubo un problema al actualizar el período académico.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        });
    });

    // Función para editar período
    function editarPeriodo(id, fechaInicio, fechaFin, tipo) {
        $('#edit_periodo_id').val(id);
        $('#edit_fechaInicio').val(fechaInicio.split(' ')[0]);
        $('#edit_fechaFin').val(fechaFin.split(' ')[0]);
        $('#edit_tipoPeriodo').val(tipo);
        $('#editarPeriodoModal').modal('show');
    }

    // Función para eliminar período
    function eliminarPeriodo(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/periodos/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Período Académico eliminado exitosamente.'
                    }).then(() => {
                        // Recargar la página para mostrar los datos actualizados
                        window.location.reload();
                    });
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo eliminar el período académico.'
                    });
                });
            }
        });
    }
</script>
<script>
    // Inicializar tooltips de Bootstrap
    const tooltips = document.querySelectorAll('.tt');
    tooltips.forEach(t => {
        new bootstrap.Tooltip(t);
    });
</script>   
</body>
</html>
