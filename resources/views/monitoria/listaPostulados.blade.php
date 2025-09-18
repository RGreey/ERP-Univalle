<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postulados a Monitorías</title>
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
                        <li><a class="dropdown-item" href="{{ route('consultarEventos') }}" style="color: #000000;">Consultar Eventos</a></li>
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
                    <i class="fa-solid fa-gear"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->email === 'soporte.caicedonia@correounivalle.edu.co')
                        <li><a class="dropdown-item" href="{{ route('admin.usuarios.index') }}">Administrar usuarios</a></li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"> Cerrar sesión <i class="fa-solid fa-right-from-bracket"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title">Postulados para la {{ $convocatoriaActiva->nombre }}</h2>
            <p class="card-text">
                <strong>Fecha de cierre:</strong> {{ \Carbon\Carbon::parse($convocatoriaActiva->fechaCierre)->format('d/m/Y') }}
            </p>
        </div>
    </div>

    {{-- Mensaje informativo sobre el período de la convocatoria --}}
    @if(isset($estadoPeriodo) && isset($convocatoriaActiva))
        @if($estadoPeriodo === 'entrevistas')
            <div class="alert alert-warning border-warning shadow-sm mb-4" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-left: 6px solid #f39c12;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-2"><i class="fas fa-clock me-2"></i>¡Período de Revisión Final!</h5>
                        <p class="mb-2">
                            <strong>📅 Acceso disponible hasta:</strong> 
                            <span class="badge bg-warning text-dark">{{ \Carbon\Carbon::parse($convocatoriaActiva->fechaEntrevistas)->format('d/m/Y') }}</span>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-ban me-2"></i>
                            <strong>Restricciones activas:</strong> Ya no se pueden aprobar más postulados para entrevista ni cambiar aprobados a pendiente.
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Acciones permitidas:</strong> Rechazar postulados (incluso los ya aprobados para entrevista) y revisar documentos.
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-info-circle me-2"></i>
                            Los postulados ya aprobados para entrevista pueden gestionarse en el módulo "Gestionar Entrevistas".
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>
                            <strong>Importante:</strong> Después de esta fecha, ya no podrás acceder a gestionar estos postulados.
                        </small>
                    </div>
                </div>
            </div>
        @elseif($estadoPeriodo === 'abierta')
            <div class="alert alert-success border-success shadow-sm mb-4" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left: 6px solid #28a745;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-plus fa-2x text-success me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-2"><i class="fas fa-hourglass-start me-2"></i>Recepción de Postulaciones Activa</h5>
                        <p class="mb-2">
                            <strong>📅 Cierre de postulaciones:</strong> 
                            <span class="badge bg-success">{{ \Carbon\Carbon::parse($convocatoriaActiva->fechaCierre)->format('d/m/Y') }}</span>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-user-check me-2"></i>
                            <strong>Acción requerida:</strong> Revisa y aprueba para entrevista a los postulados que cumplan requisitos.
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                            <strong>¡Importante!</strong> Después del cierre ({{ \Carbon\Carbon::parse($convocatoriaActiva->fechaCierre)->format('d/m/Y') }}) ya NO podrás aprobar más postulados para entrevista.
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Gestiona ahora para tener los candidatos listos para las entrevistas que inician después del {{ \Carbon\Carbon::parse($convocatoriaActiva->fechaCierre)->format('d/m/Y') }}.
                        </small>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if(session('aviso_aprobado'))
        <div id="alerta-aprobacion" class="alert alert-info d-flex align-items-center shadow-sm mb-4" style="background: #e3f2fd; border-left: 6px solid #1976d2; transition: opacity 0.5s;">
            <i class="fas fa-info-circle me-2" style="color: #1976d2; font-size: 1.5rem;"></i>
            <div>
                Si apruebas a un postulado y luego deseas revertir la aprobación, simplemente cambia su estado a <strong>Pendiente</strong> o <strong>Rechazado</strong>. El sistema actualizará automáticamente la lista de monitores activos.
            </div>
        </div>
        <script>
            setTimeout(function() {
                var alerta = document.getElementById('alerta-aprobacion');
                if (alerta) {
                    alerta.style.opacity = '0';
                    setTimeout(function() { alerta.style.display = 'none'; }, 600);
                }
            }, 15000);
        </script>
    @endif

    <div class="mb-3">
        <label for="filtroEstado" class="form-label fw-bold">Filtrar por estado:</label>
        <select id="filtroEstado" class="form-select w-auto d-inline-block ms-2">
            <option value="todos">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="aprobado_entrevista">Aprobado para entrevista</option>
            <option value="rechazado">Rechazado</option>
        </select>
    </div>

    @if($postulados->isEmpty())
        <div class="alert alert-info">
            <h4 class="alert-heading">No hay postulados</h4>
            <p>No hay postulados registrados para esta convocatoria.</p>
        </div>
    @else
        <div class="card">
            <div class="card-body">
        <table id="postuladosTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Monitoria de interés</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($postulados as $postulado)
                        <tr data-estado="{{ $postulado->estado }}" @if($postulado->estado == 'aprobado') style="background-color: #d4edda;" @endif>
                    <td>
                        {{ $postulado->name }}
                        @if($postulado->estado == 'aprobado')
                            <span class="badge bg-success ms-2">Monitor Activo</span>
                        @endif
                    </td>
                    <td>{{ $postulado->email }}</td>
                    <td>{{ $postulado->monitoria_nombre }}</td>
                    <td>
                        @if($postulado->estado == 'aprobado')
                            {{-- SOLO MOSTRAR INFORMACIÓN, NO PERMITIR CAMBIO --}}
                            <div class="text-success fw-bold">
                                <i class="fas fa-check-circle"></i> Aprobado (Monitor)
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Gestionar desde "Gestionar Monitores"
                            </small>
                        @else
                            {{-- PERMITIR CAMBIO SOLO SI NO ES MONITOR --}}
                            <form id="updateForm_{{ $postulado->id }}" action="{{ route('postulados.update', $postulado->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="input-group">
                                    <select name="estado" class="form-select" onchange="confirmUpdate(event, {{ $postulado->id }})">
                                    @if(isset($estadoPeriodo) && $estadoPeriodo === 'entrevistas' && $postulado->estado == 'aprobado_entrevista')
                                        {{-- Durante entrevistas, si ya está aprobado, no puede volver a pendiente --}}
                                        <option value="pendiente" disabled style="background-color: #f8f9fa; color: #6c757d;">Pendiente (no disponible)</option>
                                    @else
                                        <option value="pendiente" {{ $postulado->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    @endif
                                    <option value="rechazado" {{ $postulado->estado == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                                    @if(isset($estadoPeriodo) && $estadoPeriodo === 'entrevistas')
                                        {{-- Durante período de entrevistas --}}
                                        @if($postulado->estado == 'aprobado_entrevista')
                                            {{-- Si ya está aprobado para entrevista, permitir mantenerlo o rechazarlo, pero no cambiar a pendiente --}}
                                            <option value="aprobado_entrevista" selected>Aprobado para entrevista</option>
                                        @else
                                            {{-- Si no está aprobado, no permitir aprobarlo ahora --}}
                                            <option value="aprobado_entrevista" disabled style="background-color: #f8f9fa; color: #6c757d;">Aprobado para entrevista (periodo cerrado)</option>
                                        @endif
                                    @else
                                        <option value="aprobado_entrevista" {{ $postulado->estado == 'aprobado_entrevista' ? 'selected' : '' }}>Aprobado para entrevista</option>
                                    @endif
                                    
                                    {{-- OPCIÓN APROBADO (MONITOR) - SOLO LECTURA --}}
                                    </select>
                                </div>
                            </form>
                        @endif
                    </td>
                    <td>
                                <div class="btn-group" role="group">
                                    {{-- Eliminar el botón de PDF individual --}}
                                    {{-- @foreach($postulado->documentos as $documento)
                                        <button class="btn btn-primary btn-sm tt" data-bs-placement="top" title="Ver Documento" data-bs-toggle="modal" data-bs-target="#documentModal" data-url="{{ Storage::url($documento->url) }}" data-nombre="{{ $documento->nombreDocumento }}">
                                            <i class="fa-regular fa-file-pdf"></i>
                                        </button>
                                    @endforeach --}}
                        <button type="button" class="btn btn-warning btn-sm tt" data-bs-placement="top" title="Enviar correo" data-bs-toggle="modal" data-bs-target="#correoModal"
                                data-postulado-id="{{ $postulado->id }}"
                                data-postulado-nombre="{{ $postulado->name }}"
                                data-postulado-email="{{ $postulado->email }}">
                                <i class="fa-solid fa-at"></i>
                        </button>
                                    <button class="btn btn-primary btn-sm tt" data-bs-placement="top" title="Revisar Documentos y Requisitos" data-bs-toggle="modal" data-bs-target="#revisarModal_{{ $postulado->id }}">
                                        <i class="fa-solid fa-clipboard-check"></i>
                                    </button>
                                </div>
                    </td>
                </tr>
                        <!-- Modal de Revisión de Documentos y Requisitos para este postulado -->
                        <div class="modal fade" id="revisarModal_{{ $postulado->id }}" tabindex="-1" aria-labelledby="revisarModalLabel_{{ $postulado->id }}" aria-hidden="true">
                          <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="revisarModalLabel_{{ $postulado->id }}">Revisión de Documentos y Requisitos - {{ $postulado->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                              </div>
                              <div class="modal-body">
                                <div class="row">
                                  <div class="col-lg-7 col-12 mb-3 mb-lg-0">
                                    <div class="card h-100 shadow-sm">
                                      <div class="card-header bg-primary text-white py-2 d-flex align-items-center">
                                        <i class="fa-regular fa-file-pdf me-2"></i> Documento PDF
                                        @if(count($postulado->documentos) > 1)
                                          <select class="form-select form-select-sm ms-auto w-auto" id="selectDoc_{{ $postulado->id }}">
                                            @foreach($postulado->documentos as $i => $documento)
                                              <option value="{{ Storage::url($documento->url) }}">{{ $documento->nombreDocumento }}</option>
                                            @endforeach
                                          </select>
                                        @endif
                                      </div>
                                      <div class="card-body p-2" style="height: 70vh;">
                                        <iframe id="visorDoc_{{ $postulado->id }}" src="{{ count($postulado->documentos) ? Storage::url($postulado->documentos[0]->url) : '' }}" style="width: 100%; height: 100%; border: none;"></iframe>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="col-lg-5 col-12">
                                    <div class="card h-100 shadow-sm">
                                      <div class="card-header bg-success text-white py-2">
                                        <i class="fa-solid fa-list-check me-2"></i> Checklist de Requisitos
                                      </div>
                                      <div class="card-body p-3" style="max-height: 70vh; overflow-y: auto;">
                                        <form>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req1_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req1_{{ $postulado->id }}">
                                              Hoja de Vida D-10 firmada por el Coordinador
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req2_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req2_{{ $postulado->id }}">
                                              Formato Solicitud de Apoyo Bienestar
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req3_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req3_{{ $postulado->id }}">
                                              Copia del recibo de pago de matrícula financiera del semestre actual
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req4_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req4_{{ $postulado->id }}">
                                              Copia del recibo de pago de servicios públicos de la dirección de residencia actual
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req5_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req5_{{ $postulado->id }}">
                                              Carta de solicitud de apoyo económico requerido (monitoria)
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req6_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req6_{{ $postulado->id }}">
                                              Fotocopia de la cédula de ciudadanía del solicitante y de los padres
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req7_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req7_{{ $postulado->id }}">
                                              Copia del tabulado acumulado de matrícula académica
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req8_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req8_{{ $postulado->id }}">
                                              Matriculado al menos en el 60% de las asignaturas previstas por el Programa Académico
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req9_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req9_{{ $postulado->id }}">
                                              Haber cursado y aprobado el segundo semestre y cubierto al menos el 60% de las asignaturas previstas
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req10_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req10_{{ $postulado->id }}">
                                              Acreditar un promedio mínimo de 3.8
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req11_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req11_{{ $postulado->id }}">
                                              No haber sido sancionado disciplinariamente y no estar en bajo rendimiento académico
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req12_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req12_{{ $postulado->id }}">
                                              Disponibilidad diurna o nocturna, según necesidades de la dependencia
                                            </label>
                                          </div>
                                          <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="req13_{{ $postulado->id }}">
                                            <label class="form-check-label" for="req13_{{ $postulado->id }}">
                                              Demostrar competencia y aptitudes en el área
                                            </label>
                                          </div>
                                        </form>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                              </div>
                            </div>
                          </div>
                        </div>
            @endforeach
            </tbody>
        </table>
            </div>
        </div>
    @endif
</div>

<!-- Modal Documento -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="documentModalLabel">Documento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <iframe id="documentViewer" src="" style="width: 100%; height: 80vh;" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Correo -->
<div class="modal fade" id="correoModal" tabindex="-1" aria-labelledby="correoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="correoModalLabel">Enviar Correo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="correoForm" action="" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="postuladoNombre" class="form-label">Destinatario</label>
                        <input type="text" class="form-control" id="postuladoNombre" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="asunto" class="form-label">Asunto</label>
                        <select class="form-select" id="asunto" name="asunto" onchange="actualizarDetalles()">
                            <option value="">Seleccione un asunto</option>
                            <option value="inconsistencia_documentos">Inconsistencia en Documentos</option>
                            <option value="falta_documento">Falta de Documento</option>
                            <option value="documento_incompleto">Documento Incompleto</option>
                            <option value="documento_ilegible">Documento Ilegible</option>
                            <option value="documento_vencido">Documento Vencido</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="detalles" class="form-label">Detalles</label>
                        <textarea class="form-control" id="detalles" name="detalles" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="imagen" class="form-label">Adjuntar Imagen de Referencia</label>
                        <input class="form-control" type="file" id="imagen" name="imagen" accept="image/*">
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> 
                            Adjunte una imagen que muestre el problema o el área que necesita ser corregida en el documento.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="instrucciones" class="form-label">Instrucciones de Corrección</label>
                        <textarea class="form-control" id="instrucciones" name="instrucciones" rows="2" placeholder="Especifique los pasos a seguir para corregir el documento"></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
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
        $('#postuladosTable').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay información",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
                "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
                "infoFiltered": "(Filtrado de _MAX_ total entradas)",
                "infoPostFix": "",
                "thousands": ",",
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

        // Initialize Bootstrap tooltips
        const tooltips = document.querySelectorAll('.tt');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t);
        });

        // Set up the modal to display the document
        $('#documentModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var url = button.data('url'); // Extract info from data-* attributes
            var nombre = button.data('nombre');
            var modal = $(this);
            modal.find('.modal-title').text(nombre);
            modal.find('#documentViewer').attr('src', url);
        });
    });

    function confirmUpdate(event, postuladoId) {
        event.preventDefault();
        const select = event.target;
        const estado = select.value;
        const estadoAnterior = select.options[select.selectedIndex].text;
        const nuevoEstado = select.options[select.selectedIndex].text;

        // Guardar el estado anterior
        select.dataset.previousValue = select.value;

        if (estado === 'aprobado') {
            Swal.fire({
                title: '¿Aprobar postulado?',
                html: `
                    <p>Estás a punto de aprobar este postulado como monitor.</p>
                    <p>Recuerda que:</p>
                    <ul class="text-start">
                        <li>El estudiante no podrá ser aprobado en otra monitoría</li>
                        <li>Se verificará que haya vacantes disponibles</li>
                    </ul>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, aprobar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#updateForm_' + postuladoId).submit();
        } else {
                    // Restaurar el estado anterior
                    select.value = select.dataset.previousValue;
                }
            });
        } else if (estadoAnterior === 'Aprobado') {
            Swal.fire({
                title: '¿Cambiar estado?',
                html: `
                    <p>Estás a punto de cambiar el estado de <strong>Aprobado</strong> a <strong>${nuevoEstado}</strong>.</p>
                    <p>Esta acción:</p>
                    <ul class="text-start">
                        <li>Eliminará al estudiante de la lista de monitores activos</li>
                        <li>Liberará una vacante en la monitoría</li>
                        <li>No se podrá deshacer automáticamente</li>
                    </ul>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cambiar estado',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#updateForm_' + postuladoId).submit();
                } else {
                    // Restaurar el estado anterior
                    select.value = select.dataset.previousValue;
                }
            });
        } else {
            Swal.fire({
                title: '¿Cambiar estado?',
                text: `¿Estás seguro de cambiar el estado a ${nuevoEstado}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#updateForm_' + postuladoId).submit();
                } else {
                    // Restaurar el estado anterior
                    select.value = select.dataset.previousValue;
                }
            });
        }
    }


</script>

<script>
    $(document).ready(function() {
        $('#correoModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var postuladoId = button.data('postulado-id');
            var postuladoNombre = button.data('postulado-nombre');
            var postuladoEmail = button.data('postulado-email');

            var modal = $(this);
            modal.find('.modal-title').text('Enviar Correo');
            modal.find('#postuladoNombre').val(postuladoNombre);
            modal.find('#correoForm').attr('action', '/postulados/' + postuladoId + '/enviarCorreo');
            
            // Limpiar el formulario
            modal.find('#asunto').val('');
            modal.find('#detalles').val('');
            modal.find('#instrucciones').val('');
            modal.find('#imagen').val('');
        });
    });
</script>
<script>
    $(document).ready(function() {
        // Mostrar la alerta de SweetAlert si se ha enviado el correo
        @if(session('correo_enviado'))
            Swal.fire({
                icon: 'success',
                title: 'Correo Enviado',
                text: '{{ session('correo_enviado') }}',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Aceptar'
            });
        @endif
    });
</script>
@if (session('success'))
    <script>
        Swal.fire({
            title: '¡Éxito!',
            text: '{{ session('success') }}',
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            title: '¡Error!',
            text: '{{ session('error') }}',
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
    </script>
@endif

@if (session('info'))
    <script>
        Swal.fire({
            title: 'Información',
            text: '{{ session('info') }}',
            icon: 'info',
            confirmButtonText: 'Entendido'
        });
    </script>
@endif

<script>
function actualizarDetalles() {
    const asunto = document.getElementById('asunto').value;
    const detalles = document.getElementById('detalles');
    const instrucciones = document.getElementById('instrucciones');
    
    switch(asunto) {
        case 'inconsistencia_documentos':
            detalles.value = 'Estimado/a estudiante,\n\nHemos detectado inconsistencias en los documentos presentados. Por favor, revise los detalles a continuación.';
            instrucciones.value = 'Por favor, asegúrese de que todos los documentos sean consistentes entre sí y con la información proporcionada.';
            break;
        case 'falta_documento':
            detalles.value = 'Estimado/a estudiante,\n\nHemos notado que falta uno o más documentos requeridos en su postulación. Por favor, complete su documentación.';
            instrucciones.value = 'Por favor, adjunte los documentos faltantes según lo especificado en la convocatoria.';
            break;
        case 'documento_incompleto':
            detalles.value = 'Estimado/a estudiante,\n\nHemos detectado que uno o más documentos están incompletos. Por favor, complete la información faltante.';
            instrucciones.value = 'Por favor, complete toda la información requerida en los documentos.';
            break;
        case 'documento_ilegible':
            detalles.value = 'Estimado/a estudiante,\n\nHemos detectado que uno o más documentos no son legibles. Por favor, asegúrese de que la calidad de los documentos sea óptima.';
            instrucciones.value = 'Por favor, escanee o fotografíe los documentos con mejor calidad y asegúrese de que todo el texto sea legible.';
            break;
        case 'documento_vencido':
            detalles.value = 'Estimado/a estudiante,\n\nHemos detectado que uno o más documentos han expirado. Por favor, actualice su documentación.';
            instrucciones.value = 'Por favor, adjunte los documentos actualizados que estén vigentes.';
            break;
        default:
            detalles.value = '';
            instrucciones.value = '';
    }
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Para cada modal de revisión
    document.querySelectorAll('[id^=revisarModal_]').forEach(function(modal) {
        const postuladoId = modal.id.replace('revisarModal_', '');

        // Cambiar documento en el visor si hay select
        const selectDoc = modal.querySelector('#selectDoc_' + postuladoId);
        const visorDoc = modal.querySelector('#visorDoc_' + postuladoId);
        if (selectDoc && visorDoc) {
            selectDoc.addEventListener('change', function() {
                visorDoc.src = this.value;
            });
        }

        // Al abrir el modal, cargar checks desde localStorage
        modal.addEventListener('show.bs.modal', function() {
            const saved = JSON.parse(localStorage.getItem('requisitos_' + postuladoId) || '{}');
            Object.keys(saved).forEach(function(key) {
                const checkbox = modal.querySelector('#' + key);
                if (checkbox) checkbox.checked = saved[key];
            });
        });

        // Al cambiar cualquier checkbox, guardar en localStorage
        modal.querySelectorAll('input[type=checkbox]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const allChecks = {};
                modal.querySelectorAll('input[type=checkbox]').forEach(function(cb) {
                    allChecks[cb.id] = cb.checked;
                });
                localStorage.setItem('requisitos_' + postuladoId, JSON.stringify(allChecks));
            });
        });
    });
});
</script>

<script>
    document.getElementById('filtroEstado').addEventListener('change', function() {
        var estadoSeleccionado = this.value;
        document.querySelectorAll('#postuladosTable tbody tr').forEach(function(row) {
            if (estadoSeleccionado === 'todos' || row.getAttribute('data-estado') === estadoSeleccionado) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>