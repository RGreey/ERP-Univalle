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

    <style>
        /* Ajustes visuales del navbar para que no se vea "punto" ni desalineado */
        .navbar-custom { background-color: #cd1f32; } /* Rojo Univalle */
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link,
        .navbar-custom .dropdown-toggle { color: #ffffff !important; }
        .navbar-custom .nav-link:hover,
        .navbar-custom .dropdown-item:hover { color: #cd1f32 !important; }
        .navbar-custom .dropdown-menu { border: 0; box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .navbar-custom .navbar-toggler { border-color: rgba(255,255,255,.2); }
        .navbar-custom .navbar-toggler-icon { filter: invert(1) grayscale(1); }
        /* Asegura que los items del navbar no tengan viñeta en ningún caso */
        .navbar-nav > .nav-item { list-style: none; }
        /* Botón de engranaje (menú usuario) */
        .navbar-custom .btn.btn-light { background: #fff; color: #000; }
        /* Espaciado consistente entre items */
        .navbar-nav .nav-link { padding: .5rem .75rem; }
    </style>
</head>
<body>
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>

@php
    $user = auth()->user();
    $hasRole = fn($r) => $user && method_exists($user,'hasRole') ? $user->hasRole($r) : false;
    $esBienestar    = $hasRole('AdminBienestar');
    $esRestaurante  = $hasRole('Restaurante');
@endphp

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ $esBienestar ? route('subsidio.admin.dashboard') : route('dashboard') }}">
            <img src="{{ asset('imagenes/header_logo.jpg') }}" alt="Universidad del Valle" style="max-height: 50px;">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- IZQUIERDA: menú principal -->
            <ul class="navbar-nav me-auto align-items-lg-center">
                @if ($esBienestar)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('subsidio.admin.dashboard') }}">
                            Módulo Bienestar
                        </a>
                    </li>
                @elseif ($esRestaurante)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('restaurantes.dashboard') }}">
                            Gestión del restaurante
                        </a>
                    </li>    
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Inicio</a>
                    </li>

                    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Administrativo') || auth()->user()->hasRole('Profesor'))
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="ddEventos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Eventos
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="ddEventos">
                                <li><a class="dropdown-item" href="{{ route('crearEvento') }}">Crear Evento</a></li>
                                <li><a class="dropdown-item" href="{{ route('consultarEventos') }}">Consultar tus eventos</a></li>
                            </ul>
                        </li>
                    @endif

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="ddMonitorias" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Monitorias
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="ddMonitorias">
                            @if(auth()->user()->hasRole('CooAdmin')|| auth()->user()->hasRole('AuxAdmin'))
                                <li><a class="dropdown-item" href="{{ route('periodos.crear') }}">Consultar Periodo Académico</a></li>
                                <li><a class="dropdown-item" href="{{ route('convocatoria.index') }}">Crear Convocatoria</a></li>
                            @endif
                            @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin'))
                                <li><a class="dropdown-item" href="{{ route('admin.gestionMonitores') }}">Consultar Monitores</a></li>
                            @endif
                            @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('Profesor') || auth()->user()->hasRole('Administrativo'))
                                <li><a class="dropdown-item" href="{{ route('monitoria.index') }}">Gestionar Monitorias</a></li>
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
                                    <li><a class="dropdown-item" href="{{ route('postulados.entrevistas') }}">Gestionar Entrevistas</a></li>
                                @endif
                            @endif

                            @if(auth()->user()->hasRole('CooAdmin')|| auth()->user()->hasRole('AuxAdmin'))
                                <li><a class="dropdown-item" href="{{ route('postulados.index') }}">Ver Postulados</a></li>
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
                                    <li><a class="dropdown-item" href="{{ route('listaMonitorias') }}">Postularse</a></li>
                                @elseif($puedeAccederSeguimiento)
                                    <li><a class="dropdown-item" href="{{ route('seguimiento.monitoria', ['monitoria_id' => $monitorsActivosEst->first()->monitoria]) }}">Seguimiento de Monitoría</a></li>
                                @else
                                    <li><span class="dropdown-item text-muted" style="cursor: not-allowed;">Seguimiento de Monitoría <small>(Disponible después de entrevistas)</small></span></li>
                                @endif
                            @endif

                            @if(auth()->user()->monitoriasEncargadas()->exists())
                                @php $hoy = \Carbon\Carbon::today(); @endphp
                                @foreach(auth()->user()->monitoriasEncargadas as $monitoria)
                                    @php
                                        $monitors = $monitoria->monitors()->with('user')->get();
                                        $monitorsActivos = $monitors->filter(function($m) use ($hoy) {
                                            return !$m->fecha_culminacion || \Carbon\Carbon::parse($m->fecha_culminacion)->gte($hoy);
                                        });
                                    @endphp
                                    @if($monitorsActivos->count() > 0)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('seguimiento.monitoria', ['monitoria_id' => $monitoria->id]) }}">
                                                Seguimiento de Monitoría: {{ $monitoria->nombre }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                    </li>

                    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Profesor') || auth()->user()->hasRole('Administrativo'))
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="ddMantenimiento" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Mantenimiento
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="ddMantenimiento">
                                <li><a class="dropdown-item" href="{{ route('novedades.index') }}">Gestionar Novedades</a></li>
                                @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin'))
                                    <li><a class="dropdown-item" href="{{ route('mantenimiento.index') }}">Plan de Mantenimiento Preventivo</a></li>
                                    <li><a class="dropdown-item" href="{{ route('evidencias-mantenimiento.index') }}">Evidencias de Mantenimiento</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if(auth()->check() && auth()->user()->hasRole('Estudiante'))
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="subsidioDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>Subsidio Alimenticio</span>
                                @isset($subsidioConvocatoriasCount)
                                    @if($subsidioConvocatoriasCount > 0)
                                        <span class="badge bg-success ms-2">{{ $subsidioConvocatoriasCount }}</span>
                                    @endif
                                @endisset
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="subsidioDropdown">
                                <li>
                                    <a class="dropdown-item" href="{{ route('subsidio.convocatorias.index') }}">
                                        Postulaciones
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('app.subsidio.mis-cupos') }}">
                                        Cupos
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('app.subsidio.reportes.index') }}">
                                        Reportes
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('calendario') }}">
                            Calendario <i class="fa-regular fa-calendar"></i>
                        </a>
                    </li>
                @endif
            </ul>

            <!-- DERECHA: menú usuario -->
            <div class="d-flex">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-gear"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                        @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->email === 'soporte.caicedonia@correounivalle.edu.co')
                            <li><a class="dropdown-item" href="{{ route('admin.usuarios.index') }}">Administrar usuarios</a></li>
                        @endif
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Cerrar sesión <i class="fa-solid fa-right-from-bracket"></i>
                            </a>
                        </li>
                    </ul>
                </div>
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
