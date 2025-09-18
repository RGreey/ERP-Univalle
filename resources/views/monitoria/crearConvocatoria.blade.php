@extends('layouts.app')

@section('title', 'Crear Convocatoria')

@section('content')
    <div class="container mt-5">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Gestión de Convocatorias</h2>
                <p class="card-text">
                    <i class="fas fa-info-circle"></i> 
                    Aquí puedes crear y gestionar las convocatorias para las monitorías académicas.
                </p>
            </div>
        </div>

        <div class="mb-3">
            <button type="button" class="btn btn-primary mb-3 tt" data-bs-placement="top" title="Crear Nueva Convocatoria" data-bs-toggle="modal" data-bs-target="#crearConvocatoriaModal">
                <i class="fa-solid fa-plus"></i> Crear Nueva Convocatoria
        </button>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Listado de Convocatorias</h3>
                <div class="table-responsive">
        <table id="convocatoriasTable" class="table table-striped table-bordered">
                        <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Período Académico</th>
                    <th>Fecha de Apertura</th>
                    <th>Fecha de Cierre</th>
                    <th>Fecha Entrevistas</th>
                    <th>Cupo Disponible</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($convocatorias as $convocatoria)
                    <tr>
                        <td>{{ $convocatoria->nombre }}</td>
                        <td>{{ $convocatoria->nombrePeriodo }}</td>
                        <td>{{ \Carbon\Carbon::parse($convocatoria->fechaApertura)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($convocatoria->fechaCierre)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($convocatoria->fechaEntrevistas)->format('d/m/Y') }}</td>
                        <td>
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-info mb-1">
                                                <i class="fas fa-user-tie"></i> Administrativas: {{ $convocatoria->horas_administrativo }}h
                                            </span>
                                            <span class="badge bg-success mb-1">
                                                <i class="fas fa-chalkboard-teacher"></i> Docencia: {{ $convocatoria->horas_docencia }}h
                                            </span>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-flask"></i> Investigación: {{ $convocatoria->horas_investigacion }}h
                                            </span>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarConvocatoriaModal{{ $convocatoria->id }}" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarConvocatoria({{ $convocatoria->id }})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @if(isset($convocatoriaAnterior) && $convocatoriaAnterior->id == $convocatoria->id)
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#reabrirConvocatoriaModal{{ $convocatoria->id }}" title="Reabrir convocatoria">
                                    <i class="fas fa-redo"></i> Reabrir
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear nueva convocatoria -->
    <div class="modal fade" id="crearConvocatoriaModal" tabindex="-1" aria-labelledby="crearConvocatoriaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('convocatorias.store') }}" method="POST" id="crearConvocatoriaForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="crearConvocatoriaModalLabel">Crear Nueva Convocatoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <i class="fa-solid fa-circle-info tt" data-bs-placement="right" title="Se recomienda seguir un estándar para definir el nombre de la convocatoria, por ejemplo: Convocatoria monitorias 2024-II"></i>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="periodoAcademico" class="form-label">Período Académico</label>
                            <select class="form-select" id="periodoAcademico" name="periodoAcademico" required>
                                @foreach ($periodosAcademicos as $periodo)
                                    <option value="{{ $periodo->id }}">{{ $periodo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="horas_administrativo" class="form-label">Horas Administrativo</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="horas_administrativo" name="horas_administrativo" min="1" required>
                                        <span class="input-group-text">horas</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="horas_docencia" class="form-label">Horas Docencia</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="horas_docencia" name="horas_docencia" min="1" required>
                                        <span class="input-group-text">horas</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="horas_investigacion" class="form-label">Horas Investigación</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="horas_investigacion" name="horas_investigacion" min="1" required>
                                        <span class="input-group-text">horas</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fechaApertura" class="form-label">Fecha de Apertura</label>
                            <input type="date" class="form-control" id="fechaApertura" name="fechaApertura" required>
                        </div>
                            </div>
                            <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fechaCierre" class="form-label">Fecha de Cierre</label>
                            <input type="date" class="form-control" id="fechaCierre" name="fechaCierre" required>
                        </div>
                            </div>
                            <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fechaEntrevistas" class="form-label">Fecha Entrevistas</label>
                            <input type="date" class="form-control" id="fechaEntrevistas" name="fechaEntrevistas" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Convocatoria</button>
                    </div>
                </form>
            </div>
        </div>
                        </div>

    <!-- Modal para editar convocatoria -->
    @foreach ($convocatorias as $convocatoria)
    <div class="modal fade" id="editarConvocatoriaModal{{ $convocatoria->id }}" tabindex="-1" aria-labelledby="editarConvocatoriaModalLabel{{ $convocatoria->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('convocatorias.update', $convocatoria->id) }}" method="POST" id="editarConvocatoriaForm{{ $convocatoria->id }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarConvocatoriaModalLabel{{ $convocatoria->id }}">Editar Convocatoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre{{ $convocatoria->id }}" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre{{ $convocatoria->id }}" name="nombre" value="{{ $convocatoria->nombre }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="periodoAcademico{{ $convocatoria->id }}" class="form-label">Período Académico</label>
                                    <select class="form-select" id="periodoAcademico{{ $convocatoria->id }}" name="periodoAcademico" required>
                                        @foreach ($periodosAcademicos as $periodo)
                                            <option value="{{ $periodo->id }}" {{ $convocatoria->periodoAcademico == $periodo->id ? 'selected' : '' }}>
                                                {{ $periodo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="horas_administrativo{{ $convocatoria->id }}" class="form-label">Horas Administrativo</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="horas_administrativo{{ $convocatoria->id }}" name="horas_administrativo" value="{{ $convocatoria->horas_administrativo }}" min="1" required>
                                        <span class="input-group-text">horas</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="horas_docencia{{ $convocatoria->id }}" class="form-label">Horas Docencia</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="horas_docencia{{ $convocatoria->id }}" name="horas_docencia" value="{{ $convocatoria->horas_docencia }}" min="1" required>
                                        <span class="input-group-text">horas</span>
                                    </div>
                                </div>
                        <div class="mb-3">
                                    <label for="horas_investigacion{{ $convocatoria->id }}" class="form-label">Horas Investigación</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="horas_investigacion{{ $convocatoria->id }}" name="horas_investigacion" value="{{ $convocatoria->horas_investigacion }}" min="1" required>
                                        <span class="input-group-text">horas</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="fechaApertura{{ $convocatoria->id }}" class="form-label">Fecha de Apertura</label>
                                    <input type="date" class="form-control" id="fechaApertura{{ $convocatoria->id }}" name="fechaApertura" value="{{ \Carbon\Carbon::parse($convocatoria->fechaApertura)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="fechaCierre{{ $convocatoria->id }}" class="form-label">Fecha de Cierre</label>
                                    <input type="date" class="form-control" id="fechaCierre{{ $convocatoria->id }}" name="fechaCierre" value="{{ \Carbon\Carbon::parse($convocatoria->fechaCierre)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                        <div class="mb-3">
                                    <label for="fechaEntrevistas{{ $convocatoria->id }}" class="form-label">Fecha Entrevistas</label>
                                    <input type="date" class="form-control" id="fechaEntrevistas{{ $convocatoria->id }}" name="fechaEntrevistas" value="{{ \Carbon\Carbon::parse($convocatoria->fechaEntrevistas)->format('Y-m-d') }}" required>
                                </div>
                            </div>
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
    @endforeach

    <!-- Modales para reabrir convocatoria -->
    @foreach ($convocatorias as $convocatoria)
        @if(isset($convocatoriaAnterior) && $convocatoriaAnterior->id == $convocatoria->id)
        <div class="modal fade" id="reabrirConvocatoriaModal{{ $convocatoria->id }}" tabindex="-1" aria-labelledby="reabrirConvocatoriaModalLabel{{ $convocatoria->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('convocatorias.reabrir', $convocatoria->id) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="reabrirConvocatoriaModalLabel{{ $convocatoria->id }}">Reabrir Convocatoria</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="nueva_fecha_cierre{{ $convocatoria->id }}" class="form-label">Nueva fecha de cierre</label>
                                <input type="date" class="form-control" id="nueva_fecha_cierre{{ $convocatoria->id }}" name="nueva_fecha_cierre" required min="{{ now()->addDay()->format('Y-m-d') }}">
                                <div class="form-text">Seleccione una fecha posterior a hoy.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Reabrir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endforeach
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '{{ session('success') }}',
                confirmButtonText: 'Aceptar'
            });
        @endif
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                confirmButtonText: 'Aceptar'
            });
        @endif

        // Inicializar DataTable
        $('#convocatoriasTable').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay convocatorias registradas",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ convocatorias",
                "infoEmpty": "Mostrando 0 a 0 de 0 convocatorias",
                "infoFiltered": "(filtrado de _MAX_ convocatorias totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ convocatorias",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron convocatorias",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            order: [[2, 'desc']], // Ordenar por fecha de apertura descendente
            pageLength: 10,
            responsive: true
        });

        // Validación de fechas para crear
        $('#crearConvocatoriaForm').on('submit', function(event) {
            const fechaApertura = new Date($('#fechaApertura').val());
            const fechaCierre = new Date($('#fechaCierre').val());
            const fechaEntrevistas = new Date($('#fechaEntrevistas').val());

            if (fechaCierre <= fechaApertura) {
                event.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error en las fechas',
                    text: 'La fecha de cierre debe ser posterior a la fecha de apertura'
                });
                return false;
            }

            if (fechaEntrevistas <= fechaCierre) {
            event.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error en las fechas',
                    text: 'La fecha de entrevistas debe ser posterior a la fecha de cierre'
                });
                return false;
            }
            // Si pasa la validación, el formulario se envía normalmente (no AJAX)
        });

        // Validación de fechas para editar
        $('[id^="editarConvocatoriaForm"]').each(function() {
            $(this).on('submit', function(event) {
                const formId = $(this).attr('id');
                const convocatoriaId = formId.replace('editarConvocatoriaForm', '');
                
                const fechaApertura = new Date($(`#fechaApertura${convocatoriaId}`).val());
                const fechaCierre = new Date($(`#fechaCierre${convocatoriaId}`).val());
                const fechaEntrevistas = new Date($(`#fechaEntrevistas${convocatoriaId}`).val());

                if (fechaCierre <= fechaApertura) {
                    event.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en las fechas',
                        text: 'La fecha de cierre debe ser posterior a la fecha de apertura'
                    });
                    return false;
                }

                if (fechaEntrevistas <= fechaCierre) {
                    event.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en las fechas',
                        text: 'La fecha de entrevistas debe ser posterior a la fecha de cierre'
                    });
                    return false;
                }
                // Si pasa la validación, el formulario se envía normalmente (no AJAX)
            });
        });
    });

    // Función para eliminar convocatoria
    function eliminarConvocatoria(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/convocatorias/${id}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
            Swal.fire({
                icon: 'success',
                            title: 'Eliminado',
                            text: 'Convocatoria eliminada exitosamente'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function() {
            Swal.fire({
                icon: 'error',
                            title: 'Error',
                            text: 'No se pudo eliminar la convocatoria'
            });
                    }
                });
            }
    });
    }

    // Inicializar tooltips de Bootstrap
    const tooltips = document.querySelectorAll('.tt');
    tooltips.forEach(t => {
        new bootstrap.Tooltip(t);
    });
</script>
@endpush