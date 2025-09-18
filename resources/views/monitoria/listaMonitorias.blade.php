<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitorias Activas</title>
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://kit.fontawesome.com/71e9100085.js" crossorigin="anonymous"></script>
</head>
<body>
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
<nav class="navbar navbar-expand-lg navbar-light navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="#">
            <img src="{{ asset('imagenes/header_logo.jpg')}}" alt="Logo de la universidad" style="max-height: 50px;"> 
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <a href="{{ route('dashboard') }}" class="btn btn-light custom-button" style="background-color: #ffffff; color: #000000; margin-right: 10px;">Inicio</a>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMonitoria" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ffffff; color: #000000;">
                        Monitorias
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMonitoria" style="background-color: #ffffff;">
                        @if(auth()->user()->hasRole('Estudiante'))
                        <li><a class="dropdown-item" href="{{ route('listaMonitorias') }}" style="color: #000000;">Postularse</a></li>
                        @endif

                    </ul>
                </div>

                <a href="{{ route('calendario') }}" class="btn btn-light custom-button" style="background-color: #ffffff; color: #000000; margin-left: 10px;">
                    Calendario <i class="fa-regular fa-calendar"></i>
                </a>
            </ul>
        </div>
        <div class="d-flex">
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-gear "></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->email === 'soporte.caicedonia@correounivalle.edu.co')
                        <li><a class="dropdown-item" href="{{ route('admin.usuarios.index') }}">Administrar usuarios</a></li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"> Cerrar sesión  <i class="fa-solid fa-right-from-bracket"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<div class="container mt-5">
    <!-- Encabezado atractivo -->
    <div class="text-center mb-4">
        <h1 class="display-5 fw-bold mb-2"><i class="fa-solid fa-user-graduate me-2"></i>Postúlate a una Monitoría</h1>
        <p class="lead text-muted">Consulta las monitorías activas y postúlate cargando tus documentos en un solo PDF.</p>
    </div>
    <!-- Tarjeta de convocatoria activa -->
    <div id="convocatoriaActiva" class="mb-4"></div>
    <div id="monitoriasList">
        <div id="tablaMonitorias" style="display: none;">
            <div class="card shadow rounded-4">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fa-solid fa-list me-2"></i> Monitorías Disponibles
                </div>
                <div class="card-body p-0">
                    <table id="monitoriasTable" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Dependencia</th>
                                <th>Vacante</th>
                                <th>Intensidad</th>
                                <th>Horario</th>
                                <th>Modalidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filas de la tabla se agregarán dinámicamente aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="noConvocatoria" class="text-center" style="display: none;">
            <div class="alert alert-info mt-4">
                <h4 class="alert-heading"><i class="fa-solid fa-circle-info me-2"></i>No hay convocatoria activa</h4>
                <p>En este momento no hay ninguna convocatoria de monitorías activa.</p>
                <hr>
                <p class="mb-0">Te recomendamos estar atento a las próximas convocatorias que se publiquen.</p>
            </div>
        </div>
    </div>
</div>
<!-- Modal para postulación -->
<div class="modal fade" id="postularModal" tabindex="-1" aria-labelledby="postularModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="postularForm" action="{{ route('postular') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="monitoria_id" id="monitoria_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="postularModalLabel"><i class="fa-solid fa-file-upload me-2"></i>Requisitos para Postularse a la Monitoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-secondary mb-3">
                        <strong>Recuerda:</strong> Debes cargar todos los documentos en un solo archivo PDF.<br>
                        <span class="text-muted">Revisa cuidadosamente los requisitos antes de postularte.</span>
                    </div>
                    
                    <!-- Sección de Requisitos Específicos de la Monitoría -->
                    <div class="card border-info mb-3" id="requisitosEspecificos" style="display: none;">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fa-solid fa-clipboard-list me-2"></i>Requisitos Específicos de esta Monitoría</h6>
                        </div>
                        <div class="card-body">
                            <div id="requisitosMonitoria" class="mb-2">
                                <!-- Los requisitos se cargarán dinámicamente aquí -->
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-2 mb-0">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Este dato es obligatorio. La cédula se utilizará para consultar los resultados de la convocatoria.
                        </div>
                    <div class="mb-3">
                        <label for="cedula" class="form-label fw-bold">Cédula</label>
                        <input type="text" class="form-control" name="cedula" id="cedula" value="{{ auth()->user()->cedula }}" placeholder="Ingresa tu número de cédula" required>

                    </div>
                    <ul class="list-group mb-3">
                        <li class="list-group-item">Ingresar a la página de la Universidad, descargar el formato Hoja de Vida D-10, diligenciarlo y hacer firmar del respectivo Coordinador del programa de estudios.</li>
                        <li class="list-group-item">Diligenciar el formato SOLICITUD DE APOYO SERVICIOS DE BIENESTAR.</li>
                        <li class="list-group-item">Copia del recibo de pago de matrícula financiera del semestre actual.</li>
                        <li class="list-group-item">Copia del recibo de pago de servicios públicos de la dirección de residencia actual.</li>
                        <li class="list-group-item">Carta de solicitud de apoyo económico requerido, soportando porque lo requiere (monitoria).</li>
                        <li class="list-group-item">Fotocopia de la cédula de ciudadanía del solicitante y de los padres.</li>
                        <li class="list-group-item">Copia del tabulado acumulado de matrícula académica.</li>
                        <li class="list-group-item">Estar matriculado al menos en el 60% de las asignaturas previstas por el Programa Académico, para el respectivo semestre.</li>
                        <li class="list-group-item">Haber cursado y aprobado el segundo semestre del Programa Académico en que se encuentre matriculado y haber cubierto al menos el 60% de las asignaturas previstas para los semestres cursados.</li>
                        <li class="list-group-item">Acreditar un promedio mínimo de 3.8 (tres, punto, ocho).</li>
                        <li class="list-group-item">No haber sido sancionado disciplinariamente y no estar en bajo rendimiento académico.</li>
                        <li class="list-group-item">Disponibilidad diurna o nocturna, de acuerdo a las necesidades de la dependencia.</li>
                        <li class="list-group-item">Demostrar competencia y aptitudes en el área en la cual va a realizar su actividad.</li>
                        <li class="list-group-item">Presentar la solicitud de monitoria anexando la documentación requerida en un solo documento escaneado y en un solo archivo PDF.</li>
                    </ul>
                    <div id="documentoSection" class="mb-3"></div>
                    <div class="mb-3">
                        <label for="documento_url" class="form-label fw-bold">Cargar Documento PDF</label>
                        <input type="file" class="form-control" name="documento_url" id="documento_url" accept="application/pdf" required>
                        <div class="form-text">Solo se acepta un archivo PDF. Máximo 10MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>Enviar Postulación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para visualizar PDF -->
<div class="modal fade" id="verPdfModal" tabindex="-1" aria-labelledby="verPdfModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="verPdfModalLabel">Documento PDF</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="height: 80vh;">
        <iframe id="visorPdf" src="" style="width: 100%; height: 100%; border: none;"></iframe>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Obtener la convocatoria activa y las monitorias
    $.ajax({
        url: '/monitorias/lista',
        method: 'GET',
        success: function(response) {
            if (response.convocatoriaActiva) {
                // Mostrar información de la convocatoria
                $('#convocatoriaActiva').html(`
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">${response.convocatoriaActiva.nombre}</h2>
                            <p class="card-text">
                                <strong>Fecha de cierre:</strong> ${new Date(response.convocatoriaActiva.fechaCierre).toLocaleDateString()}
                            </p>
                        </div>
                    </div>
                `);

                // Mostrar la tabla y ocultar el mensaje de no convocatoria
                $('#tablaMonitorias').show();
                $('#noConvocatoria').hide();

                let monitoriasHtml = '';

                if (Array.isArray(response.monitoriasActivas) && response.monitoriasActivas.length > 0) {
                    response.monitoriasActivas.forEach(monitoria => {
                        monitoriasHtml += `
                            <tr data-monitoria='${JSON.stringify(monitoria)}'>
                                <td>${monitoria.nombre}</td>
                                <td>${monitoria.programadependencia_nombre}</td>
                                <td>${monitoria.vacante}</td>
                                <td>${monitoria.intensidad} Horas/Semana</td>
                                <td>${monitoria.horario}</td>
                                <td>${monitoria.modalidad}</td>
                                <td>
                                    <button class="btn btn-primary postular-btn" 
                                        data-bs-placement="top" 
                                        title="Postularse" 
                                        data-monitoria-id="${monitoria.id}">
                                        <i class="fa-solid fa-paperclip"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    monitoriasHtml = `
                        <tr>
                            <td colspan="7" class="text-center">
                                No hay monitorías disponibles en este momento.
                            </td>
                        </tr>
                    `;
                }

                $('#monitoriasTable tbody').html(monitoriasHtml);
                $('#monitoriasTable').DataTable({
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay información",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
                        "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
                        "infoFiltered": "(Filtrado de _MAX_ total entradas)",
                        "lengthMenu": "Mostrar _MENU_ Entradas",
                        "loadingRecords": "Cargando...",
                        "processing": "Procesando...",
                        "search": "Buscar:",
                        "zeroRecords": "Sin resultados encontrados",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    }
                });
            } else {
                // Ocultar la tabla y mostrar el mensaje de no convocatoria
                $('#tablaMonitorias').hide();
                $('#noConvocatoria').show();
                $('#convocatoriaActiva').empty();
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al obtener las monitorías activas.',
            });
        }
    });

    // Manejador de eventos para postularse (debe estar fuera de la llamada AJAX)
    $(document).on('click', '.postular-btn', function() {
        var monitoriaId = $(this).data('monitoria-id');
        var monitoriaData = $(this).closest('tr').data('monitoria');
        $('#monitoria_id').val(monitoriaId);

        // Actualizar el título de la modal con el nombre de la monitoría
        if (monitoriaData && monitoriaData.nombre) {
            $('#postularModalLabel').html(`<i class="fa-solid fa-file-upload me-2"></i>Postularse a: ${monitoriaData.nombre}`);
        }

        // Mostrar requisitos específicos de la monitoría
        if (monitoriaData && monitoriaData.requisitos) {
            $('#requisitosEspecificos').show();
            
            // Formatear los requisitos para mejor presentación
            let requisitosFormateados = monitoriaData.requisitos
                .replace(/\n/g, '<br>')
                .replace(/•/g, '<i class="fa-solid fa-check text-success me-2"></i>');
            
            $('#requisitosMonitoria').html(`
                <div class="alert alert-warning mb-3">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    <strong>Atención:</strong> Esta monitoría tiene requisitos específicos que debes cumplir antes de postularte.
                </div>
                <div class="bg-light p-3 rounded border">
                    <h6 class="fw-bold mb-3 text-primary">
                        <i class="fa-solid fa-clipboard-check me-2"></i>
                        Requisitos Específicos de esta Monitoría:
                    </h6>
                    <div class="requisitos-lista">
                        ${requisitosFormateados}
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    <strong>Recuerda:</strong> Además de estos requisitos específicos, debes cumplir con todos los requisitos generales listados más abajo.
                </div>
            `);
        } else {
            $('#requisitosEspecificos').hide();
        }

        // Cargar el documento si existe
        $.ajax({
            url: `/documentos/${monitoriaId}`,
            method: 'GET',
            success: function(response) {
                let documentoSection = $('#documentoSection');
                if (response.documento) {
                    documentoSection.html(`
                        <div class="card border-primary mb-2">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="fa-regular fa-file-pdf fa-2x text-danger me-3"></i>
                                    <div>
                                        <strong>Documento Subido:</strong><br>
                                        <span class="text-break">${response.documento.nombreDocumento}</span>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-primary btn-sm me-2 ver-pdf-btn" data-url="${response.documento.url}">
                                        <i class="fa-solid fa-eye"></i> Ver
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-btn" data-monitoria-id="${monitoriaId}">
                                        <i class="fa-regular fa-trash-can"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    documentoSection.empty();
                }
            },
            error: function() {
                $('#documentoSection').empty();
            }
        });

        $('#postularModal').modal('show');
    });

    // Manejador de evento para eliminar el documento
    $(document).on('click', '.delete-btn', function() {
        var monitoriaId = $(this).data('monitoria-id');

        Swal.fire({
            title: '¿Estás seguro?',
            text: '¡No podrás recuperar este documento después de eliminarlo!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminarlo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/postulacion/${monitoriaId}`,  // Actualiza la URL de acuerdo a la ruta en tu servidor
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'  // Agrega el token CSRF para la seguridad
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Documento y Postulación Eliminados',
                            text: 'El documento y la postulación han sido eliminados con éxito.'
                        }).then(() => {
                            // Limpiar la sección del documento y mostrar el formulario
                            $('#documentoSection').empty();
                            $('#documento_url').val(''); // Limpiar el campo de archivo
                        });
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al intentar eliminar el documento y la postulación.'
                        });
                    }
                });
            }
        });
    });

    // Manejar el formulario de postulación
    $('#postularForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: formData,
            contentType: false,
            processData: false,
            success: function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Postulación Exitosa',
                    text: 'Te has postulado correctamente a la monitoria.',
                }).then((result) => {
                    if (result.isConfirmed || result.isDismissed) {
                        location.reload();
                    }
                });
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Ocurrió un error al enviar la postulación.',
                });
            }
        });
    });

    // Evento para ver el PDF en modal
    $(document).on('click', '.ver-pdf-btn', function() {
        var url = $(this).data('url');
        $('#visorPdf').attr('src', url);
        $('#verPdfModal').modal('show');
    });

    // Limpiar modal cuando se cierre
    $('#postularModal').on('hidden.bs.modal', function() {
        $('#postularModalLabel').html('<i class="fa-solid fa-file-upload me-2"></i>Requisitos para Postularse a la Monitoría');
        $('#requisitosEspecificos').hide();
        $('#requisitosMonitoria').empty();
    });

});
</script>

<style>
    /* Estilos para los requisitos */
    .requisitos-lista {
        line-height: 1.6;
    }
    
    .requisitos-lista br {
        margin-bottom: 8px;
    }
    
    .requisitos-lista i.fa-check {
        font-size: 0.9em;
    }
    
    #requisitosEspecificos .card-header {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }
    
    .modal-body {
        max-height: 80vh;
        overflow-y: auto;
    }
</style>

</body>
</html>
