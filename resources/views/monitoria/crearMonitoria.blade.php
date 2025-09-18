<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear y Listar Monitorías</title>
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://kit.fontawesome.com/71e9100085.js" crossorigin="anonymous"></script>
    <style>
        .table td, .table th {
            white-space: normal !important;
            word-break: break-word;
        }
        .tabla-modal-monitorias th,
        .tabla-modal-monitorias td {
            white-space: normal !important;
            word-break: break-word;
            vertical-align: middle;
        }
        .tabla-modal-monitorias th:nth-child(4), /* Vacante */
        .tabla-modal-monitorias td:nth-child(4) {
            min-width: 80px;
        }
        .tabla-modal-monitorias th:nth-child(6), /* Horario */
        .tabla-modal-monitorias td:nth-child(6) {
            min-width: 90px;
        }
        .tabla-modal-monitorias th:nth-child(7), /* Requisitos */
        .tabla-modal-monitorias td:nth-child(7) {
            min-width: 160px;
        }
        .tabla-modal-monitorias th:nth-child(8), /* Modalidad */
        .tabla-modal-monitorias td:nth-child(8) {
            min-width: 100px;
        }
        .tabla-modal-monitorias th:nth-child(9), /* Estado */
        .tabla-modal-monitorias td:nth-child(9) {
            min-width: 100px;
        }
        .tabla-modal-monitorias td:nth-child(9) {
            min-width: 100px;
        }
    </style>
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
                        @if(auth()->user()->hasRole('Profesor') || auth()->user()->hasRole('Administrativo'))
                            @php
                                // Buscar convocatoria activa o en período de entrevistas
                                $convActiva = \App\Models\Convocatoria::where(function($query) {
                                    $query->where('fechaCierre', '>=', now())
                                          ->orWhere(function($subQuery) {
                                              $subQuery->where('fechaCierre', '<', now())
                                                       ->where('fechaEntrevistas', '>=', now());
                                          });
                                })->first();
                            @endphp
                            @if($convActiva)
                                <li><a class="dropdown-item" href="{{ route('postulados.entrevistas') }}" style="color: #000000;">Gestionar Entrevistas</a></li>
                            @endif
                        @endif
                            @if(auth()->user()->hasRole('CooAdmin')|| auth()->user()->hasRole('AuxAdmin'))
                            <li><a class="dropdown-item" href="{{ route('postulados.index') }}" style="color: #000000;">Ver Postulados</a></li>
                            @endif
                            @if(auth()->user()->monitoriasEncargadas()->exists())
                                @php
                                    $hoy = \Carbon\Carbon::today();
                                @endphp
                                @foreach(auth()->user()->monitoriasEncargadas as $monitoria)
                                    @if($monitoria->monitor && (!$monitoria->monitor->fecha_culminacion || \Carbon\Carbon::parse($monitoria->monitor->fecha_culminacion)->gte($hoy)))
                                        <li>
                                            <a class="dropdown-item" href="{{ route('seguimiento.monitoria', ['monitoria_id' => $monitoria->id]) }}" style="color: #000000;">
                                                Seguimiento de Monitoría: {{ $monitoria->nombre }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
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
    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <div>
            En el PDF de convocatoria solo saldrán las monitorías que estén aprobadas.
        </div>
    </div>
    <div class="mb-3">
        <button type="button" class="btn btn-primary mb-3 tt" data-bs-placement="top" title="Solicitar monitoria" data-bs-toggle="modal" data-bs-target="#crearMonitoriaModal">
            <i class="fas fa-chalkboard-teacher"></i>
        </button>
        <button type="button" class="btn btn-secondary mb-3 tt" data-bs-placement="top" title="Generar Convocatoria"data-bs-toggle="modal" data-bs-target="#verMonitoriasModal">
            <i class="fa-solid fa-file-pdf"></i>
        </button>
    </div>
    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin'))
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Horas Administrativas</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3" style="height: 25px;">
                            @php
                                $porcentajeAdmin = $convocatoria ? (($horasAprobAdmin / $convocatoria->horas_administrativo) * 100) : 0;
                                $colorAdmin = $porcentajeAdmin > 90 ? 'danger' : ($porcentajeAdmin > 70 ? 'warning' : 'info');
                            @endphp
                            <div class="progress-bar bg-{{ $colorAdmin }}" role="progressbar" 
                                style="width: {{ $porcentajeAdmin }}%;" 
                                aria-valuenow="{{ $porcentajeAdmin }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($porcentajeAdmin, 1) }}%
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Total:</strong> {{ $convocatoria->horas_administrativo ?? 0 }}</p>
                                <p class="mb-1"><strong>Aprobadas:</strong> {{ $horasAprobAdmin ?? 0 }}</p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Disponibles:</strong> {{ ($convocatoria->horas_administrativo ?? 0) - ($horasAprobAdmin ?? 0) }}</p>
                                <p class="mb-1"><small class="text-muted">Las solicitudes pendientes no afectan el cupo</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Horas Docencia</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3" style="height: 25px;">
                            @php
                                $porcentajeDoc = $convocatoria ? (($horasAprobDoc / $convocatoria->horas_docencia) * 100) : 0;
                                $colorDoc = $porcentajeDoc > 90 ? 'danger' : ($porcentajeDoc > 70 ? 'warning' : 'success');
                            @endphp
                            <div class="progress-bar bg-{{ $colorDoc }}" role="progressbar" 
                                style="width: {{ $porcentajeDoc }}%;" 
                                aria-valuenow="{{ $porcentajeDoc }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($porcentajeDoc, 1) }}%
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Total:</strong> {{ $convocatoria->horas_docencia ?? 0 }}</p>
                                <p class="mb-1"><strong>Aprobadas:</strong> {{ $horasAprobDoc ?? 0 }}</p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Disponibles:</strong> {{ ($convocatoria->horas_docencia ?? 0) - ($horasAprobDoc ?? 0) }}</p>
                                <p class="mb-1"><small class="text-muted">Las solicitudes pendientes no afectan el cupo</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Horas Investigación</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3" style="height: 25px;">
                            @php
                                $porcentajeInv = $convocatoria ? (($horasAprobInv / $convocatoria->horas_investigacion) * 100) : 0;
                                $colorInv = $porcentajeInv > 90 ? 'danger' : ($porcentajeInv > 70 ? 'warning' : 'primary');
                            @endphp
                            <div class="progress-bar bg-{{ $colorInv }}" role="progressbar" 
                                style="width: {{ $porcentajeInv }}%;" 
                                aria-valuenow="{{ $porcentajeInv }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($porcentajeInv, 1) }}%
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Total:</strong> {{ $convocatoria->horas_investigacion ?? 0 }}</p>
                                <p class="mb-1"><strong>Aprobadas:</strong> {{ $horasAprobInv ?? 0 }}</p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Disponibles:</strong> {{ ($convocatoria->horas_investigacion ?? 0) - ($horasAprobInv ?? 0) }}</p>
                                <p class="mb-1"><small class="text-muted">Las solicitudes pendientes no afectan el cupo</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Botón para mostrar monitorías pasadas en un modal -->
    <div class="text-end mb-3">
        <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#monitoriasPasadasModal">
            <i class="fas fa-history me-2"></i>Ver Monitorías Pasadas
        </button>
    </div>

    <!-- Modal de Monitorías Pasadas -->
    <div class="modal fade" id="monitoriasPasadasModal" tabindex="-1" aria-labelledby="monitoriasPasadasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="monitoriasPasadasModalLabel"><i class="fas fa-history me-2"></i>Monitorías Pasadas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table table-bordered table-striped tabla-modal-monitorias" style="min-width: 1000px;">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Convocatoria</th>
                                    <th>Programa/Dependencia</th>
                                    <th>Vacante</th>
                                    <th>Intensidad</th>
                                    <th>Horario</th>
                                    <th>Requisitos</th>
                                    <th>Modalidad</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monitoriasPasadas as $monitoria)
                                    <tr>
                                        <td>{{ $monitoria->nombre }}</td>
                                        <td>{{ $monitoria->nombreConvocatoria }}</td>
                                        <td>{{ $monitoria->nombreProgramaDependencia }}</td>
                                        <td>{{ $monitoria->vacante }}</td>
                                        <td>{{ $monitoria->intensidad }} Horas/Semana</td>
                                        <td>{{ $monitoria->horario }}</td>
                                        <td>{{ Str::limit($monitoria->requisitos, 50) }}</td>
                                        <td>{{ $monitoria->modalidad }}</td>
                                        <td>{{ $monitoria->estado }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No hay monitorías pasadas para la convocatoria anterior.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Card bonita para la tabla de monitorías activas -->
    <div class="container-fluid px-0">
        <div class="card mt-4" style="width: 100%; margin: 0;">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0"><i class="fas fa-list me-2"></i>Listado de Monitorías Activas</h3>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="monitoriasTable" class="table table-striped table-bordered" style="width: 100%;">
                        <thead>
                            <tr>
                                <!--<th>ID</th>-->
                                <th>Nombre</th>
                                <th>Convocatoria</th>
                                <th>Programa/Dependencia</th>
                                <th>Vacante</th>
                                <th>Intensidad</th>
                                <th>Horario</th>
                                <th>Requisitos</th>
                                <th>Modalidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monitorias as $monitoria)
                                <!-- Mostrar solo las activas -->
                                <tr>
                                    <!--<td>{{ $monitoria->id }}</td>-->
                                    <td>{{ $monitoria->nombre }}</td>
                                    <td>{{ $monitoria->nombreConvocatoria }}</td>
                                    <td>{{ $monitoria->nombreProgramaDependencia }}</td>
                                    <td>{{ $monitoria->vacante }}</td>
                                    <td>{{ $monitoria->intensidad }} Horas/Semana</td>
                                    <td>{{ $monitoria->horario }}</td>
                                    <td>{{ Str::limit($monitoria->requisitos, 50) }}</td>
                                    <td>{{ $monitoria->modalidad }}</td>
                                    <td>
                                        @php
                                            $estados = [
                                                'creado' => ['label' => 'Creado', 'color' => 'primary'],
                                                'autorizado' => ['label' => 'Autorizado', 'color' => 'info'],
                                                'requiere_ajustes' => ['label' => 'Requiere Ajustes', 'color' => 'warning'],
                                                'aprobado' => ['label' => 'Aprobado', 'color' => 'success'],
                                                'rechazado' => ['label' => 'Rechazado', 'color' => 'danger'],
                                            ];
                                            $estadoActual = $monitoria->estado;
                                            $badge = $estados[$estadoActual] ?? ['label' => ucfirst($estadoActual), 'color' => 'secondary'];
                                        @endphp
                                        @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin'))
                                            <div>
                                                <select class="form-select form-select-sm estado-select" data-monitoria-id="{{ $monitoria->id }}">
                                                    <option value="creado" {{ $monitoria->estado == 'creado' ? 'selected' : '' }}>Creado</option>
                                                    <option value="autorizado" {{ $monitoria->estado == 'autorizado' ? 'selected' : '' }}>Autorizado</option>
                                                    <option value="requiere_ajustes" {{ $monitoria->estado == 'requiere_ajustes' ? 'selected' : '' }}>Requiere Ajustes</option>
                                                    <option value="aprobado" {{ $monitoria->estado == 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                                                    <option value="rechazado" {{ $monitoria->estado == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                                                </select>
                                                <div class="mt-1">
                                                    <span class="badge bg-{{ $badge['color'] }}">{{ $badge['label'] }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="badge bg-{{ $badge['color'] }}">{{ $badge['label'] }}</span>
                                            @if($monitoria->estado == 'requiere_ajustes' && $monitoria->comentarios_ajustes)
                                                <button type="button" class="btn btn-link btn-sm p-0 ms-2" data-bs-toggle="modal" data-bs-target="#verComentariosModal" data-comentarios="{{ $monitoria->comentarios_ajustes }}">
                                                    <i class="fas fa-comment-dots text-warning"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $postuladosCount = \App\Models\Postulado::where('monitoria', $monitoria->id)->count();
                                            $badgeColor = $postuladosCount == 0 ? 'secondary' : ($postuladosCount < $monitoria->vacante ? 'warning' : 'success');
                                            $title = 'Postulados: ' . $postuladosCount;
                                        @endphp
                                        <span class="badge bg-{{ $badgeColor }} tt me-2" data-bs-placement="top" title="{{ $title }}">
                                            <i class="fa-solid fa-user-group me-1"></i>{{ $postuladosCount }}
                                        </span>
                                        @if($monitoria->estado !== 'aprobado')
                                        <button type="button" class="btn btn-warning btn-sm editar-monitoria tt" data-bs-placement="top" title="Editar Monitoria" data-bs-toggle="modal" data-bs-target="#editarMonitoriaModal" data-monitoria-id="{{ $monitoria->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @endif
                                        @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin'))
                                            @if($monitoria->estado == 'requiere_ajustes')
                                                <button type="button" class="btn btn-info btn-sm tt" data-bs-placement="top" title="Gestionar Comentarios" data-bs-toggle="modal" data-bs-target="#comentariosModal" data-monitoria-id="{{ $monitoria->id }}" data-comentarios="{{ $monitoria->comentarios_ajustes }}">
                                                    <i class="fas fa-comments"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear monitoria -->
<div class="modal fade" id="crearMonitoriaModal" tabindex="-1" aria-labelledby="crearMonitoriaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('monitoria.store') }}" method="POST" id="crearMonitoriaForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="crearMonitoriaModalLabel">Solicitar Monitoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Mensaje informativo sobre el cupo de horas -->
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Las solicitudes de monitoría pueden crearse sin restricción de horas. El control de cupo solo se aplica al aprobar las monitorías.
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label> <i class="fa-solid fa-circle-info tt" data-bs-placement="right" title="Se recomienda seguir un estandar para definir el nombre de la monitoria, Por ejemplo: Monitoria de programacion."></i>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre de la monitoria" required>
                    </div>
                    <div class="mb-3">
                        <label for="convocatoria" class="form-label">Convocatoria</label>
                        @php
                            $hoy = \Carbon\Carbon::now();
                        @endphp
                        <select class="form-select" id="convocatoria" name="convocatoria" required>
                            @foreach($convocatorias as $convocatoria)
                                @if(\Carbon\Carbon::parse($convocatoria->fechaCierre) >= $hoy)
                                    <option value="{{ $convocatoria->id }}">{{ $convocatoria->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="programadependencia" class="form-label">Programa o Dependencia</label>
                        <select class="form-select" id="programadependencia" name="programadependencia" required>
                            @foreach($programadependencias as $programadependencia)
                                <option value="{{ $programadependencia->id }}">{{ $programadependencia->nombrePD }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="vacante" class="form-label">Vacante</label>
                        <input type="number" class="form-control" id="vacante" name="vacante" placeholder="Numero de monitores solicitados" min="1" required>
                    </div>
                    <div class="mb-3">
                    <label for="intensidad" class="form-label">Intensidad (horas/semana)</label>
                        <input type="number" class="form-control" id="intensidad" name="intensidad" placeholder="Cantidad de horas por semana" min="1" required>
                        <div id="horasInfo" class="form-text">
                            Total horas solicitadas: <span id="totalHorasSolicitadas">0</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="horario" class="form-label">Horario</label>
                        <select class="form-select" id="horario" name="horario" required>
                            <option value="diurno">Diurno</option>
                            <option value="nocturno">Nocturno</option>
                            <option value="mixto">Mixto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="requisitos" class="form-label">Requisitos</label>
                        <textarea class="form-control" id="requisitos" name="requisitos" placeholder="Aptitudes con las cuales debe contar el monitor" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="modalidad" class="form-label">Modalidad</label>
                        <select class="form-select" id="modalidad" name="modalidad" required>
                            <option value="administrativo">Administrativo</option>
                            <option value="docencia">Docencia</option>
                            <option value="investigacion">Investigación</option>
                        </select>
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

<div class="modal fade" id="verMonitoriasModal" tabindex="-1" aria-labelledby="verMonitoriasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verMonitoriasModalLabel">Listado de Monitorias en PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe src="{{ route('monitorias.pdf') }}" width="100%" height="500px"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="editarMonitoriaModal" tabindex="-1" aria-labelledby="editarMonitoriaModalLabel" aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
        <form id="formEditarMonitoria" action="{{ route('monitoria.update') }}"  method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="editarMonitoriaModalLabel">Editar Monitoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="monitoria_id" id="monitoria_id">
                <div class="mb-3">
                    <label for="edit_nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="edit_nombre" name="edit_nombre" required>
                </div>
                <div class="mb-3">
                    <label for="edit_convocatoria" class="form-label">Convocatoria</label>
                    <select class="form-select" id="edit_convocatoria" name="edit_convocatoria" required>
                        @foreach($convocatorias as $convocatoria)
                            <option value="{{ $convocatoria->id }}">{{ $convocatoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_programadependencia" class="form-label">Programa o Dependencia</label>
                    <select class="form-select" id="edit_programadependencia" name="edit_programadependencia" required>
                        @foreach($programadependencias as $programadependencia)
                            <option value="{{ $programadependencia->id }}">{{ $programadependencia->nombrePD }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_vacante" class="form-label">Vacante</label>
                    <input type="number" class="form-control" id="edit_vacante" name="edit_vacante">
                </div>
                <div class="mb-3">
                <label for="edit_intensidad" class="form-label">Intensidad (horas/semana)</label>
                    <input type="number" class="form-control" id="edit_intensidad" name="edit_intensidad">
                    <div id="edit_horasInfo" class="form-text">
                        Total horas solicitadas: <span id="edit_totalHorasSolicitadas">0</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_horario" class="form-label">Horario</label>
                    <select class="form-select" id="edit_horario" name="edit_horario" required>
                        <option value="diurno">Diurno</option>
                        <option value="nocturno">Nocturno</option>
                        <option value="mixto">Mixto</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_requisitos" class="form-label">Requisitos</label>
                    <textarea class="form-control" id="edit_requisitos" name="edit_requisitos" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="edit_modalidad" class="form-label">Modalidad</label>
                    <select class="form-select" id="edit_modalidad" name="edit_modalidad" required>
                        <option value="administrativo">Administrativo</option>
                        <option value="docencia">Docencia</option>
                        <option value="investigacion">Investigación</option>
                    </select>
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

<!-- Modal para comentarios -->
<div class="modal fade" id="comentariosModal" tabindex="-1" aria-labelledby="comentariosModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="comentariosModalLabel">Gestionar Comentarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="comentarios_textarea" class="form-label">Comentarios</label>
                    <textarea class="form-control" id="comentarios_textarea" rows="4" placeholder="Especifique los ajustes requeridos..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="guardarComentarios">Guardar Comentarios</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver comentarios (solicitante) -->
<div class="modal fade" id="verComentariosModal" tabindex="-1" aria-labelledby="verComentariosModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="verComentariosModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ajustes Requeridos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Por favor, revise los siguientes ajustes requeridos para su monitoría:
                </div>
                <div class="p-3 bg-light rounded">
                    <p id="comentarios_solicitante" class="mb-0"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
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

// === Calcular horas solicitadas automáticamente ===
$('#vacante, #intensidad').on('input', function() {
    const vacantes = parseInt($('#vacante').val()) || 0;
    const intensidad = parseInt($('#intensidad').val()) || 0;
    $('#totalHorasSolicitadas').text(vacantes * intensidad);
});

// === Envío del formulario de solicitud de monitoría ===
$('#crearMonitoriaForm').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);

    const vacantes = parseInt($('#vacante').val()) || 0;
    const intensidad = parseInt($('#intensidad').val()) || 0;

    if (vacantes <= 0 || intensidad <= 0) {
        return Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: 'Las vacantes y la intensidad deben ser mayores a 0.'
        });
    }

    $.ajax({
        url: form.attr('action'),
        method: form.attr('method'),
        data: form.serialize(),
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Monitoría Solicitada',
                html: `
                    <div class="text-left">
                        <p>${response.message}</p>
                        ${response.detalles ? `
                            <small class="text-muted">Estado: ${response.detalles.estado || 'Pendiente'}</small><br>
                            <small class="text-muted">Horas solicitadas: ${response.detalles.horas_solicitadas || (vacantes * intensidad)}</small>
                        ` : ''}
                    </div>
                `
            }).then(() => location.reload());
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            const errorMessage = response.message || 'Ocurrió un error al crear la monitoría.';
            const d = response.detalles || {};

            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: `
                    <div class="text-left">
                        <p>${errorMessage}</p>
                        ${d.horas_totales ? `
                            <div class="mt-3">
                                <p><strong>Detalles de horas:</strong></p>
                                <ul>
                                    <li>Horas totales: ${d.horas_totales}</li>
                                    <li>Horas usadas: ${d.horas_usadas}</li>
                                    <li>Horas solicitadas: ${d.horas_solicitadas}</li>
                                    <li>Horas disponibles: ${d.horas_disponibles}</li>
                                </ul>
                                <p class="text-muted"><small>Nota: Solo las monitorías aprobadas afectan el cupo de horas.</small></p>
                            </div>
                        ` : ''}
                    </div>
                `
            });
        }
    });
});

// === Inicializar tooltips de Bootstrap ===
document.querySelectorAll('.tt').forEach(t => {
    new bootstrap.Tooltip(t);
});

// === Inicializar DataTable ===
$('#monitoriasTable').DataTable({
    language: {
        decimal: "",
        emptyTable: "No hay información",
        info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        infoEmpty: "Mostrando 0 a 0 de 0 entradas",
        infoFiltered: "(filtrado de _MAX_ entradas)",
        lengthMenu: "Mostrar _MENU_ entradas",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron resultados",
        paginate: {
            first: "Primero",
            last: "Último",
            next: "Siguiente",
            previous: "Anterior"
        }
    },
    columnDefs: [
        {
            targets: ["Requisitos"],
            render: function(data) {
                return `<div style="white-space: normal; word-wrap: break-word;">${data}</div>`;
            }
        }
    ]
});

// === Cambio de estado de monitoría ===
$(document).on('change', '.estado-select', function() {
    const selectElement = $(this);
    const monitoriaId = selectElement.data('monitoria-id');
    const nuevoEstado = selectElement.val();
    const ultimoEstado = selectElement.data('ultimo-estado') || 'aprobado_solicitante';

    if (nuevoEstado === 'aprobado') {
        Swal.fire({
            title: '¿Confirmar aprobación?',
            text: 'Se verificará la disponibilidad de horas antes de aprobar.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                actualizarEstado(monitoriaId, nuevoEstado, selectElement);
            } else {
                selectElement.val(ultimoEstado);
            }
        });
    } else {
        actualizarEstado(monitoriaId, nuevoEstado, selectElement);
    }
});

function actualizarEstado(monitoriaId, nuevoEstado, selectElement) {
    selectElement.data('ultimo-estado', nuevoEstado);

    $.ajax({
        url: `{{ route('monitorias.updateEstado', '') }}/${monitoriaId}`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            estado: nuevoEstado
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Estado actualizado',
                html: response.message ? `
                    <div class="text-left">
                        <p>${response.message}</p>
                    </div>
                ` : 'Estado actualizado correctamente.'
            }).then(() => {
                location.reload();
            });
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            const errorMessage = response.message || 'Error al actualizar el estado.';
            const d = response.detalles || {};

            selectElement.val(selectElement.data('ultimo-estado'));

            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: `
                    <div class="text-left">
                        <p>${errorMessage}</p>
                        ${d.horas_totales ? `
                            <div class="mt-3">
                                <p><strong>Detalles:</strong></p>
                                <ul>
                                    <li>Horas totales: ${d.horas_totales}</li>
                                    <li>Horas usadas: ${d.horas_usadas}</li>
                                    <li>Horas solicitadas: ${d.horas_solicitadas}</li>
                                    <li>Horas disponibles: ${d.horas_disponibles}</li>
                                </ul>
                            </div>
                        ` : ''}
                        ${nuevoEstado === 'aprobado' ? `
                            <p class="text-muted mt-2"><small>Solo se pueden aprobar monitorías si hay horas disponibles.</small></p>
                        ` : ''}
                    </div>
                `
            });
        }
    });
}

// Manejar la visibilidad del campo de comentarios
$(document).on('change', '.estado-select', function() {
    const selectElement = $(this);
    const comentariosDiv = selectElement.closest('div').find('.comentarios-ajustes');
    
    if (selectElement.val() === 'requiere_ajustes') {
        comentariosDiv.show();
    } else {
        comentariosDiv.hide();
    }
});

// Guardar comentarios de ajustes
$(document).on('click', '.guardar-comentarios', function() {
    const button = $(this);
    const monitoriaId = button.data('monitoria-id');
    const comentarios = button.closest('.comentarios-ajustes').find('textarea').val().trim();

    if (!comentarios) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Debe ingresar un comentario antes de guardar.'
        });
        return;
    }

    $.ajax({
        url: `/monitorias/${monitoriaId}/comentarios`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            comentarios: comentarios
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Comentarios guardados',
                text: 'Los comentarios se han guardado correctamente.'
            });
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message || 'Ocurrió un error al guardar los comentarios.'
            });
        }
    });
});

// Mostrar comentarios existentes al cargar la página
$('.estado-select').each(function() {
    const selectElement = $(this);
    if (selectElement.val() === 'requiere_ajustes') {
        selectElement.closest('div').find('.comentarios-ajustes').show();
    }
});

// Manejar la apertura del modal de comentarios
$('#comentariosModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const monitoriaId = button.data('monitoria-id');
    const comentarios = button.data('comentarios');

    const modal = $(this);
    modal.find('#comentarios_textarea').val(comentarios);
    modal.find('#guardarComentarios').data('monitoria-id', monitoriaId);
});

// Manejar la apertura del modal de ver comentarios
$('#verComentariosModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const comentarios = button.data('comentarios');
    $(this).find('#comentarios_solicitante').text(comentarios);
});

// Guardar comentarios
$('#guardarComentarios').on('click', function() {
    const button = $(this);
    const monitoriaId = button.data('monitoria-id');
    const comentarios = $('#comentarios_textarea').val().trim();

    if (!comentarios) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Debe ingresar un comentario antes de guardar.'
        });
        return;
    }

    $.ajax({
        url: `/monitorias/${monitoriaId}/comentarios`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            comentarios: comentarios
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Comentarios guardados',
                text: 'Los comentarios se han guardado correctamente.'
            }).then(() => {
                location.reload();
            });
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message || 'Error al guardar los comentarios.'
            });
        }
    });
});

});
</script>

<script>
$(document).ready(function() {
// Al hacer clic en editar monitoria
$(document).on('click', '.editar-monitoria', function() {
    var monitoria_id = $(this).data('monitoria-id');

    // Realizar una petición AJAX para obtener los datos de la monitoría
    $.ajax({
        url: '{{ route('monitoria.get') }}',
        method: 'GET',
        data: { monitoria_id: monitoria_id },
        success: function(response) {
            // Llenar el formulario de edición con los datos de la monitoría
            $('#monitoria_id').val(response.monitoria.id);
            $('#edit_nombre').val(response.monitoria.nombre);
            $('#edit_convocatoria').val(response.monitoria.convocatoria);
            $('#edit_programadependencia').val(response.monitoria.programadependencia);
            $('#edit_vacante').val(response.monitoria.vacante);
            $('#edit_intensidad').val(response.monitoria.intensidad);
            $('#edit_horario').val(response.monitoria.horario);
            $('#edit_requisitos').val(response.monitoria.requisitos);
            $('#edit_modalidad').val(response.monitoria.modalidad);

            // Calcular y mostrar las horas totales
            const vacantes = parseInt(response.monitoria.vacante) || 0;
            const intensidad = parseInt(response.monitoria.intensidad) || 0;
            $('#edit_totalHorasSolicitadas').text(vacantes * intensidad);

            // Mostrar el modal
            $('#editarMonitoriaModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error(error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar los datos de la monitoría. Inténtelo de nuevo.'
            });
        }
    });
});

// Calcular horas en tiempo real
$('#edit_vacante, #edit_intensidad').on('input', function() {
    const vacantes = parseInt($('#edit_vacante').val()) || 0;
    const intensidad = parseInt($('#edit_intensidad').val()) || 0;
    $('#edit_totalHorasSolicitadas').text(vacantes * intensidad);
});

// Manejar envío del formulario de edición
$('#formEditarMonitoria').submit(function(e) {
    e.preventDefault();
    var form = $(this);
    
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Monitoria actualizada',
                html: `
                    <div class="text-left">
                        <p>${response.message || 'Monitoria actualizada correctamente'}</p>
                        ${response.detalles ? `
                            <div class="mt-2">
                                <small class="text-muted">Estado: ${response.detalles.estado}</small><br>
                                <small class="text-muted">Horas solicitadas: ${response.detalles.horas_solicitadas}</small>
                            </div>
                        ` : ''}
                    </div>
                `
            }).then(() => {
                location.reload();
            });
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            const errorMessage = response.message || 'Ocurrió un error al actualizar la monitoría.';
            const d = response.detalles || {};

            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: `
                    <div class="text-left">
                        <p>${errorMessage}</p>
                        ${d.horas_totales ? `
                            <div class="mt-3">
                                <p><strong>Detalles:</strong></p>
                                <ul>
                                    <li>Horas totales: ${d.horas_totales}</li>
                                    <li>Horas usadas: ${d.horas_usadas}</li>
                                    <li>Horas solicitadas: ${d.horas_solicitadas}</li>
                                    <li>Horas disponibles: ${d.horas_disponibles}</li>
                                </ul>
                            </div>
                        ` : ''}
                    </div>
                `
            });
        }
    });
});

});
</script>

<script>
$('#nombre').on('input', function(){
    
});
$('#edit_nombre').on('input', function(){
    
});
</script>

</body>
</html>