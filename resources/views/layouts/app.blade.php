<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://kit.fontawesome.com/71e9100085.js" crossorigin="anonymous"></script>
    <title>@yield('title', 'Dashboard')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    @php
    $user = auth()->user();
    $esBienestar = $user && method_exists($user, 'hasRole') ? $user->hasRole('AdminBienestar') : false;
    @endphp
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="{{ $esBienestar ? route('subsidio.admin.dashboard') : '#' }}">
                <img src="{{ asset('imagenes/header_logo.jpg') }}" alt="Logo de la universidad" style="max-height: 50px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                @if ($esBienestar)
                <a href="{{ route('subsidio.admin.dashboard') }}" class="btn btn-light custom-button" style="background-color: #ffffff; color: #000000; margin-right: 10px;">Módulo Bienestar</a>
                @endif
                @unless ($esBienestar)
                <a href="{{ route('dashboard') }}" class="btn btn-light custom-button" style="background-color: #ffffff; color: #000000; margin-right: 10px;">Inicio</a>
                
                @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Administrativo') || auth()->user()->hasRole('Profesor'))
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownEventos" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ffffff; color: #000000;">
                        Eventos
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownEventos" style="background-color: #ffffff;">
                        <li><a class="dropdown-item" href="{{ route('crearEvento') }}" style="color: #000000;">Crear Evento</a></li>
                        @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Administrativo') || auth()->user()->hasRole('Profesor'))
                            <li><a class="dropdown-item" href="{{ route('consultarEventos') }}" style="color: #000000;">Consultar tus eventos</a></li>
                        @endif
                    </ul>
                </div>
                @endif
                @endunless
                <div class="dropdown @if($esBienestar) d-none @endif">
                    @unless ($esBienestar)
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
                                // Usar helper centralizado con ajuste 00:00 → día siguiente
                                $convActiva = \App\Helpers\ConvocatoriaHelper::obtenerConvocatoriaActiva();
                                $mostrarEntrevistas = false;
                                if ($convActiva) {
                                    $mostrarEntrevistas = \App\Helpers\ConvocatoriaHelper::convocatoriaEnEntrevistas($convActiva->fechaCierre, $convActiva->fechaEntrevistas);
                                }
                            @endphp
                            @if($mostrarEntrevistas)
                                <li><a class="dropdown-item" href="{{ route('postulados.entrevistas') }}" style="color: #000000;">Gestionar Entrevistas</a></li>
                            @endif
                        @endif
                        @if(auth()->user()->hasRole('CooAdmin')|| auth()->user()->hasRole('AuxAdmin'))
                            <li><a class="dropdown-item" href="{{ route('postulados.index') }}" style="color: #000000;">Ver Postulados</a></li>
                        @endif
                        @if(auth()->user()->hasRole('Estudiante'))
                            @php
                                $monitorsActuales = auth()->user()->monitors()->with('user')->get();
                                $hoyEst = \Carbon\Carbon::today();
                                $monitorsActivosEst = $monitorsActuales->filter(function($m) use ($hoyEst) {
                                    return !$m->fecha_culminacion || \Carbon\Carbon::parse($m->fecha_culminacion)->gte($hoyEst);
                                });
                                
                                // Verificar si el estudiante puede acceder al seguimiento (después de fecha de entrevista)
                                $puedeAccederSeguimiento = true;
                                if ($monitorsActivosEst->count() > 0) {
                                    $monitoria = \App\Models\Monitoria::find($monitorsActivosEst->first()->monitoria);
                                    if ($monitoria) {
                                        $convocatoria = \App\Models\Convocatoria::find($monitoria->convocatoria);
                                        if ($convocatoria && $convocatoria->fechaEntrevistas) {
                                            $fechaEntrevistas = \Carbon\Carbon::parse($convocatoria->fechaEntrevistas);
                                            $fechaActual = \Carbon\Carbon::now();
                                            $puedeAccederSeguimiento = $fechaActual->gte($fechaEntrevistas);
                                        }
                                    }
                                }
                            @endphp
                            @if($monitorsActivosEst->count() == 0)
                                <li><a class="dropdown-item" href="{{ route('listaMonitorias') }}" style="color: #000000;">Postularse</a></li>
                            @elseif($puedeAccederSeguimiento)
                                <li><a class="dropdown-item" href="{{ route('seguimiento.monitoria', ['monitoria_id' => $monitorsActivosEst->first()->monitoria]) }}" style="color: #000000;">Seguimiento de Monitoría</a></li>
                            @else
                                <li><span class="dropdown-item text-muted" style="cursor: not-allowed;">Seguimiento de Monitoría <small>(Disponible después de entrevistas)</small></span></li>
                            @endif
                        @endif
                        @if(auth()->user()->monitoriasEncargadas()->exists())
                            @php
                                $hoy = \Carbon\Carbon::today();
                            @endphp
                            @foreach(auth()->user()->monitoriasEncargadas as $monitoria)
                                @php
                                    $monitors = $monitoria->monitors()->with('user')->get();
                                    $monitorsActivos = $monitors->filter(function($m) use ($hoy) {
                                        return !$m->fecha_culminacion || \Carbon\Carbon::parse($m->fecha_culminacion)->gte($hoy);
                                    });
                                @endphp
                                @if($monitorsActivos->count() > 0)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('seguimiento.monitoria', ['monitoria_id' => $monitoria->id]) }}" style="color: #000000;">
                                            Seguimiento de Monitoría: {{ $monitoria->nombre }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        @endif
                        
                    </ul>
                    @endunless
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
                            @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin'))
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
                        <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Cerrar sesión <i class="fa-solid fa-right-from-bracket"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container mt-4">
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
</body>
</html>
