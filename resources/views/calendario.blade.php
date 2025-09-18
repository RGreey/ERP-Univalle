<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/calendario.css') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href='https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.13.1/css/all.css' rel='stylesheet'>
    <script src="https://kit.fontawesome.com/71e9100085.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        
        #calendar-container {
            display: flex;
            flex-direction: column;
            position: relative;
            width: 100%;
            height: 800px;
        }

        #calendar {
            width: 100%;
            height: 100%;
        }

        .navbar-custom {
            background-color: #cd1f32;
        }

        .navbar-brand img {
            max-height: 50px;
        }

        .navbar-nav {
            display: flex;
            align-items: center;
        }

        .navbar-nav .nav-link {
            color: #ffffff !important;
            margin-left: 10px;
        }


    </style>
</head>
<body>
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
<div id="calendar-container">
<div>
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
            <div class="container-fluid">
                <a class="navbar-brand text-white" href="#">
                    <img src="{{ asset('imagenes/header_logo.jpg')}}" alt="Logo de la universidad" style="max-height: 50px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
        
                    <a href="{{ route('dashboard') }}" class="btn btn-light custom-button" style="background-color: #ffffff; color: #000000; margin-right: 10px;">Inicio</a> 
                    
                    <div class="dropdown">
                        @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Administrativo') || auth()->user()->hasRole('Profesor'))
                        <button class="btn btn-light dropdown-toggle" type="button" id="dropdownEventos" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ffffff; color: #000000;">
                            Eventos
                        </button>
                        @endif
                        <ul class="dropdown-menu" aria-labelledby="dropdownEventos" style="background-color: #ffffff;">
                            <li><a class="dropdown-item" href="{{ route('crearEvento') }}" style="color: #000000;">Crear Evento</a></li>
                            @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Administrativo') || auth()->user()->hasRole('Profesor'))
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
                                // Usar helper centralizado y respetar 00:00 → día siguiente
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
                            @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->email === 'soporte.caicedonia@correounivalle.edu.co')
                                <li><a class="dropdown-item" href="{{ route('admin.usuarios.index') }}">Administrar usuarios</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"> Cerrar sesión  <i class="fa-solid fa-right-from-bracket"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </div>
    <div id="calendar"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core/locales/es.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            themeSystem: 'bootstrap',
            locale: 'es',
            initialView: 'dayGridMonth',
            header: {
                left: 'prev,next',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            }, 
            events: '/obtener-eventos',
            eventClick: function(info) {
                var fechaInicio = info.event.start;
                var fechaFin = info.event.end;
                var titulo = info.event.title;
                var lugar = info.event.extendedProps.lugar;
                var espacio = info.event.extendedProps.espacio;

                var formatoFecha = { year: 'numeric', month: 'long', day: 'numeric' };
                var formatoHora = { hour: 'numeric', minute: 'numeric', hour12: true };
                var textoFechaInicio = fechaInicio.toLocaleDateString(undefined, formatoFecha);
                var textoHoraInicio = fechaInicio.toLocaleTimeString(undefined, formatoHora);
                var textoHoraFin = fechaFin.toLocaleTimeString(undefined, formatoHora);

                Swal.fire({
                    title: titulo,
                    html: '<p>Fecha de realización: ' + textoFechaInicio + '</p>' +
                        '<p>Hora de inicio: ' + textoHoraInicio + '</p>' +
                        '<p>Hora de finalización: ' + textoHoraFin + '</p>' +
                        '<p>Lugar: ' + lugar + '</p>' +
                        '<p>Espacio: ' + espacio + '</p>',
                    icon: 'info',
                    confirmButtonText: 'Ok'
                });

                info.el.style.backgroundColor = 'red';
            },
        });

        calendar.render();

    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
