<!DOCTYPE html>
<html lang="en">
<head>
    <title>Editar Evento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://kit.fontawesome.com/71e9100085.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        body {
            background-color: #ddd;
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
    <script>
        function toggleInventario() {
            var checkbox = document.getElementById("requiereInventario");
            var formularioInventario = document.getElementById("formularioInventario");

            if (checkbox.checked) {
                formularioInventario.style.display = "block";
            } else {
                formularioInventario.style.display = "none";
            }
        }
    </script>
</head>

<body onload="inicializarPagina()">
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
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
                            @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('Profesor'))
                            <li><a class="dropdown-item" href="{{ route('monitoria.index') }}" style="color: #000000;">Gestionar Monitorias</a></li>
                            @endif
                            @if(auth()->user()->hasRole('Profesor') || auth()->user()->hasRole('Administrativo'))
                                @php
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
<section class="vh-100 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row justify-content-center align-items-center h-100 ">
            <div class="col-sm-10 col-md-8 col-lg-10 col-xl-8">
                <div class="card bg-white text-white " style="border-radius: 1.5rem; padding: 2rem;">
                    <img src="{{ asset('imagenes/logou.png')}}" class="login-logo">
                    <div class="card-body p-6 ">
                        <div class="mb-md-4 mt-md-3 pb-4">
                            <h3 class="mb-4 text-black poppins-regular" >1. Descripción</h3>
                            <form id="formularioActualizado" action="{{ route('actualizarEvento', ['id' => $evento->id]) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('POST')

                                <div class="mb-3">
                                    <label for="nombreEvento" class ="text-black poppins-regular margen-campos">Nombre del evento:</label>
                                    <input type="text" id="nombreEvento" name="nombreEvento" class = "poppins-regular campo-largo"required value="{{ $evento->nombreEvento }}">
                                </div>
                                <!-- Propósito del evento -->
                                <div class="mb-3">
                                    <label for="propositoEvento" class="text-black poppins-regular">Propósito del evento:</label>
                                    <textarea id="propositoEvento" name="propositoEvento" placeholder="Describa el proposito del evento" class="text-black poppins-regular campo-proposito" required value>{{ $evento->propositoEvento }}</textarea>
                                </div>
                                <div class="row margen-campos">
                                    <div class="col-md-4">
                                        <!-- Fecha -->
                                        <div class="form-outline form-white mb-3">
                                            <label for="lugar" class="text-black poppins-regular mb-2">Fecha de realizacion</label>
                                            <input type="date" class="form-control form-control-lg" id="fechaRealizacion" name="fechaRealizacion" required value="{{ $evento->fechaRealizacion }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Hora de inicio -->
                                        <div class="form-outline form-white mb-3">
                                            <label for="horaInicio" class="text-black poppins-regular mb-2">Hora inicio</label>
                                            <input id="horaInicio" class="form-control form-control-lg" type="time" name="horaInicio" required value="{{ $evento->horaInicio }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Hora de fin -->
                                        <div class="form-outline form-white mb-3">
                                            <label for="horaFin" class="text-black poppins-regular mb-2">Hora fin</label>
                                            <input id="horaFin" class="form-control form-control-lg" type="time" name="horaFin" required value="{{ $evento->horaFin }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- Programa o dependencia -->
                                <div>
                                    <div class="mb-3">
                                        <label for="programasDependencia" class="text-black poppins-regular margen-campos">Programas o dependencias:</label>
                                        <select id="programaSelect" class="text-black poppins-regular">
                                            <option value="">Seleccione una opción</option>
                                            @foreach ($programasDependencia as $programa)
                                                <option value="{{ $programa->id }}">{{ $programa->nombrePD }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" id="agregarDependencia" class="btn btn-sm btn-primary mt-2">Agregar dependencia <i class="bi bi-arrow-down-circle"></i></button>
                                    </div>

                                    <!-- Lista de dependencias seleccionadas -->
                                    <div class="mb-3">
                                        <label class="text-black poppins-regular margen-campos">Dependencias seleccionadas:</label>
                                        <ul id="listaDependencias" class="list-group"></ul>
                                    </div>

                                    <!-- Campo oculto para enviar las dependencias al backend -->
                                    <input type="hidden" name="dependenciasSeleccionadas" id="dependenciasSeleccionadas">

                                    <!-- Usuario -->
                                    <input type="hidden" name="user" value="{{ Auth::id() }}">

                                    <!-- Lugar -->
                                    <div class="mb-3 ">
                                        <label for="lugar" class="text-black poppins-regular margen-campos">Lugar:</label>
                                        <select id="lugar" name="lugar" class="text-black poppins-regular" required onchange="obtenerEspacios(this.value)">
                                            <option value="">Seleccione una opción</option>
                                            @foreach ($lugares as $lugar)
                                                <option value="{{ $lugar->id }}" class="text-black poppins-regular" @if ($evento->lugar == $lugar->id) selected @endif>{{ $lugar->nombreLugar }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Espacio -->
                                    <div class="mb-3">
                                        <label for="espacio" class="text-black poppins-regular margen-campos">Espacio:</label>
                                        <select id="espacio" name="espacio" class="text-black poppins-regular" required>
                                            <option value="">Seleccione un espacio</option>
                                            @foreach ($espacios as $espacio)
                                            <option value="{{ $espacio->id }}" @if ($evento->espacio == $espacio->id) selected @endif>{{ $espacio->nombreEspacio }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Checkbox para requerir inventario -->
                                    
                                    <div class="mb-3">
                                        <h3 class="text-black poppins-regular ">2. Refrigerios</h3>
                                        <div class="form-check">
                                            <label class="form-check-label text-black poppins-regular" for="estacion_bebidas">Estación de café, Agua, Aromáticas</label>
                                            <input class="form-check-input" type="checkbox" id="estacion_bebidas" name="estacion_bebidas" value="true" @if ($detallesEvento->estacion_bebidas) checked @endif>
                                        </div> 
                                        <div class="form-check">
                                            <label for="requiereInventario" class="form-check-label text-black poppins-regular">¿Requiere inventario?</label>
                                            <input type="checkbox" id="requiereInventario" name="requiereInventario" class="form-check-input" onchange="toggleInventario()" @if (count($inventarioEvento) > 0) checked @endif>

                                        </div> 
                                    </div>
                            

                                    <!-- Formulario de inventario (inicialmente oculto) -->
                                    <div id="formularioInventario">
                                        @foreach($inventarioEvento as $inventario)
                                        <div class="mb-3 inventario-item">
                                            <!-- Tipo de inventario -->
                                            <label for="tipoInventario" class="text-black poppins-regular">Relacione aquí el nombre del producto:</label>
                                            <input type="text" id="tipoInventario" name="tipoInventario[]" class="form-control" value="{{ $inventario->tipo }}" required>

                                            <!-- Cantidad -->
                                            <label for="cantidadInventario" class="text-black poppins-regular">Cantidad:</label>
                                            <input type="number" id="cantidadInventario" name="cantidadInventario[]" class="form-control" value="{{ $inventario->cantidad }}" required>

                                            <!-- Botón para eliminar -->
                                            <button type="button" class="eliminarItem btn btn-secondary" onclick="eliminarItem(this)">
                                                <i class="fa-solid fa-minus"></i> Quitar
                                            </button>
                                        </div>
                                        @endforeach
                                    </div>

                                    <!-- Botón para agregar nuevos ítems -->
                                    <div class="mb-3">
                                        <button type="button" onclick="agregarItemInventario()" class="btn btn-secondary">Agregar otro ítem <i class="fa-solid fa-plus"></i> </button>
                                    </div>

                                    <!-- Detalles del evento -->
                                    <div class="mb-3">
                                        <!-- Sección de Logística -->
                                        <div class="mb-3">
                                            <h3 class="text-black poppins-regular ">3. Logística</h3>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="transporte" name="transporte" value=true @if ($detallesEvento->transporte) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="transporte">Transporte</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="audio" name="audio" value="true" @if ($detallesEvento->audio) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="audio">Audio</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="proyeccion" name="proyeccion" value="true" @if ($detallesEvento->proyeccion) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="proyeccion">Proyección</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="internet" name="internet" value="true" @if ($detallesEvento->internet) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="internet">Internet</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="presentacion_cultural" name="presentacion_cultural" value="true" @if ($detallesEvento->presentacion_cultural) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="presentacion_cultural">Presentación Cultural</label>
                                                    </div> 
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="certificacion" name="certificacion" value="true" @if ($detallesEvento->certificacion) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="certificacion">Certificacion</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="flyer" class="text-black poppins-regular">Flyer del Evento (Opcional)</label>
                                            <input type="file" class="form-control" id="flyer" name="flyer" accept="image/*">

                                            <!-- Mostrar el flyer actual si existe -->
                                            @if($evento->flyer)
                                                <div class="mt-3">
                                                    <p class="text-black poppins-regular">Flyer actual:</p>

                                                    <img src="{{ asset('storages/' . $evento->flyer) }}" alt="Flyer del Evento" class="img-fluid" style="max-width: 200px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#flyerModal">

                                                    <!-- Modal para mostrar el flyer en grande -->
                                                    <div class="modal fade" id="flyerModal" tabindex="-1" aria-labelledby="flyerModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="flyerModalLabel">Flyer del Evento</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body text-center">
                                                                    <img src="{{ asset('storages/' . $evento->flyer) }}" alt="Flyer del Evento" class="img-fluid" >
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <!-- Botón para descargar el flyer con ícono de FontAwesome -->
                                                                    <a href="{{ asset('storages/' . $evento->flyer) }}" class="btn btn-primary" download>
                                                                        <i class="fas fa-download"></i> Descargar
                                                                    </a>

                                                                    <!-- Botón para eliminar el flyer con ícono de FontAwesome -->
                                                                    <button type="submit" class="btn btn-danger" name="eliminar_flyer" value="1">
                                                                        <i class="fas fa-trash-alt"></i> Eliminar flyer
                                                                    </button>

                                                                    <!-- Botón para cerrar el modal -->
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <!-- Sección de Publicidad -->
                                        <div class="mb-3">
                                            <h3 class="text-black poppins-regular">4. Divulgación del evento</h3>
                                            <div class="row">
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="diseñoPublicitario" name="diseñoPublicitario" value="true" @if ($detallesEvento->diseñoPublicitario) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="diseñoPublicitario">Piezas Publicitarias</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="redes" name="redes" value="true" @if ($detallesEvento->redes) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="redes">Redes</label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="correo" name="correo" value="true" @if ($detallesEvento->correo) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="correo">Correo</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="whatsapp" name="whatsapp" value="true" @if ($detallesEvento->whatsapp) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="whatsapp">Whatsapp</label>
                                                </div>
                                            </div>
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="cubrimientoMedios" name="cubrimiento_medios" value="true " @if ($detallesEvento->cubrimiento_medios) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="cubrimientoMedios">Cubrimiento de Medios</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sección de Recursos Humanos -->
                                        <div class="mb-3">
                                            <h4 class="text-black poppins-regular">1. Recursos Humanos</h4>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="personal_recibo" name="personal_recibo" value="true" @if ($detallesEvento->personal_recibo) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="personal_recibo">Personal Recibo</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="seguridad" name="seguridad" value="true" @if ($detallesEvento->seguridad) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="seguridad">Seguridad</label>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="bienvenida" name="bienvenida" value="true" @if ($detallesEvento->bienvenida) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="bienvenida">Bienvenida</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="defensoria_civil" name="defensoria_civil" value="true" @if ($detallesEvento->defensoria_civil) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="defensoria_civil">Defensoria Civil</label>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="servicio_general" name="servicio_general" value="true" @if ($detallesEvento->servicio_general) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="servicio_general">Servicios Generales</label> 
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="otro_Recurso" name="otro_Recurso" value="true" @if ($detallesEvento->otro_Recurso) checked @endif>
                                                        <label class="form-check-label text-black poppins-regular" for="otro_Recurso">Otro(Especificar en detalles)</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <h4 class="text-black poppins-regular">2. Otros Detalles</h4>
                                            <textarea class="form-control text-dark poppins-regular" id="otros" name="otros" rows="3" placeholder="Ingresa otros detalles aquí...">{{ $detallesEvento->otros }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                        <div class="row">  
                                            <h3 class="text-black poppins-regular">7. Participantes</h3>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="estudiantes" name="estudiantes" value="true" @if ($detallesEvento->estudiantes) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="estudiantes">Estudiantes</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="profesores" name="profesores" value="true" @if ($detallesEvento->profesores) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="profesores">Profesores</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="administrativos" name="administrativos" value="true" @if ($detallesEvento->administrativos) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="administrativos">Administrativos</label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="empresarios" name="empresarios" value="true" @if ($detallesEvento->empresarios) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="empresarios">Empresarios</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="comunidad_general" name="comunidad_general" value="true" @if ($detallesEvento->comunidad_general) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="comunidad_general">Comunidad en General</label>
                                                </div>
                                            </div>
                                            <div class="col">    
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="egresados" name="egresados" value="true" @if ($detallesEvento->egresados) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="egresados">Egresados</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="invitados_externos" name="invitados_externos" value="true" @if ($detallesEvento->invitados_externos) checked @endif>
                                                    <label class="form-check-label text-black poppins-regular" for="invitados_externos">Invitados Externos</label>
                                                </div>
                                            </div>    
                                        </div>
                                    </div>
                                    </div>
                                    <!-- Botón de enviar -->
                                    <button type="button" id="btnActualizarEvento" class="btn btn-success">Actualizar evento </i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    let dependenciasSeleccionadas = @json($dependenciasEvento);

    // Homogeneizar la estructura
    dependenciasSeleccionadas = dependenciasSeleccionadas.map(dep => ({
        id: dep.id,
        nombre: dep.nombrePD
    }));

    // Mostrar las dependencias ya seleccionadas al cargar la página
    dependenciasSeleccionadas.forEach(dep => {
        $('#listaDependencias').append(`
            <li class="list-group-item d-flex justify-content-between align-items-center">
                ${dep.nombre}
                <button type="button" class="btn btn-sm btn-danger eliminar-dependencia" data-id="${dep.id}">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </li>
        `);
    });

    // Actualizar campo oculto inicial
    $('#dependenciasSeleccionadas').val(JSON.stringify(dependenciasSeleccionadas));

    // Evento para agregar dependencia
    $('#agregarDependencia').click(function () {
        const select = $('#programaSelect');
        const selectedId = select.val();
        const selectedText = select.find('option:selected').text();

        if (!selectedId) {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione una dependencia válida'
            });
            return;
        }

        // Evitar duplicados
        if (dependenciasSeleccionadas.some(dep => dep.id == selectedId)) {
            Swal.fire({
                icon: 'info',
                title: 'Esta dependencia ya ha sido seleccionada'
            });
            return;
        }

        // Agregar dependencia a la lista interna
        dependenciasSeleccionadas.push({ id: selectedId, nombre: selectedText });

        // Mostrar en la lista
        $('#listaDependencias').append(`
            <li class="list-group-item d-flex justify-content-between align-items-center">
                ${selectedText}
                <button type="button" class="btn btn-sm btn-danger eliminar-dependencia" data-id="${selectedId}">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </li>
        `);

        // Actualizar campo oculto
        $('#dependenciasSeleccionadas').val(JSON.stringify(dependenciasSeleccionadas));
    });

    // Eliminar dependencia de la lista
    $(document).on('click', '.eliminar-dependencia', function () {
        const id = $(this).data('id');

        dependenciasSeleccionadas = dependenciasSeleccionadas.filter(dep => dep.id != id);

        $(this).closest('li').remove();

        $('#dependenciasSeleccionadas').val(JSON.stringify(dependenciasSeleccionadas));
    });
</script>






<script>
    function agregarItemInventario() {
        const formularioInventario = document.getElementById('formularioInventario');
        const camposExistente = formularioInventario.getElementsByClassName('inventario-item');

        // Verificar si el último ítem agregado está completamente lleno
        if (camposExistente.length === 0 || todosCamposLlenos(camposExistente[camposExistente.length - 1])) {
            const nuevoItem = document.createElement('div');
            nuevoItem.classList.add('mb-3', 'inventario-item');
            nuevoItem.innerHTML = `
                <label class="text-black poppins-regular">Relacione aquí el nombre del producto:</label>
                <input type="text" name="tipoInventario[]" class="form-control" required>
                <label class="text-black poppins-regular">Cantidad:</label>
                <input type="number" name="cantidadInventario[]" class="form-control" required>
                <button type="button" class="eliminarItem btn btn-secondary" onclick="eliminarItem(this)">
                    <i class="fa-solid fa-minus"></i> Quitar
                </button>
            `;
            formularioInventario.appendChild(nuevoItem);
        } else {
            alert('Por favor, complete todos los campos del último ítem de inventario antes de agregar otro.');
        }
    }

    function todosCamposLlenos(item) {
        const tipoInventario = item.querySelector('input[name="tipoInventario[]"]');
        const cantidadInventario = item.querySelector('input[name="cantidadInventario[]"]');
        return tipoInventario.value.trim() !== '' && cantidadInventario.value.trim() !== '';
    }

    function eliminarItem(elemento) {
        const formularioInventario = document.getElementById('formularioInventario');
        const camposExistente = formularioInventario.getElementsByClassName('inventario-item');

        // Si hay más de un elemento existente, eliminar el elemento actual
        if (camposExistente.length > 1) {
            elemento.parentNode.remove();
        } else {
            // Limpiar campos en lugar de eliminar si es el último conjunto
            const tipoInventario = elemento.parentNode.querySelector('input[name="tipoInventario[]"]');
            const cantidadInventario = elemento.parentNode.querySelector('input[name="cantidadInventario[]"]');
            tipoInventario.value = '';
            cantidadInventario.value = '';
        }
    }
</script>
<script>
    document.getElementById('lugar').addEventListener('change', function() {
        var lugarId = this.value;
        obtenerEspacios(lugarId);
    });

    function obtenerEspacios(lugarId) {
        fetch('/obtener-espacios/' + lugarId)
            .then(response => response.json())
            .then(data => {
                var selectEspacio = document.getElementById('espacio');
                selectEspacio.innerHTML = '<option value="">Seleccione una opción</option>'; 

                data.forEach(espacio => {
                    var option = document.createElement('option');
                    option.value = espacio.id;
                    option.textContent = espacio.nombreEspacio;
                    selectEspacio.appendChild(option);
                });

                // Llamar a guardarEspacioSeleccionado después de cargar los espacios
                guardarEspacioSeleccionado();
            });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#btnActualizarEvento').click(function() {
            if (!validarEvento()) {
                return;
            }

            var eventoId = '{{ $evento->id }}';
            var nombreEvento = $('#nombreEvento').val();
            var propositoEvento = $('#propositoEvento').val();
            var fechaRealizacion = $('#fechaRealizacion').val();
            var horaInicio = $('#horaInicio').val();
            var horaFin = $('#horaFin').val();
            var lugar = $('#lugar').val();
            var espacio = $('#espacio').val();
            var requiereInventario = $('#requiereInventario').prop('checked');

            // Validación de campos vacíos
            var camposFaltantes = [];
            var validarCampo = (campo, nombre) => {
                if (campo.trim() === '') {
                    camposFaltantes.push(nombre);
                }
            };

            validarCampo(nombreEvento, 'Nombre del evento');
            validarCampo(propositoEvento, 'Propósito del evento');
            validarCampo(fechaRealizacion, 'Fecha de realización');
            validarCampo(horaInicio, 'Hora de inicio');
            validarCampo(horaFin, 'Hora de fin');
            validarCampo(lugar, 'Lugar');
            validarCampo(espacio, 'Espacio');

            // Validación de inventario
            var inventarioCompleto = $('input[name="tipoInventario[]"]').filter(function(index) {
                return $(this).val().trim() !== '' && $('input[name="cantidadInventario[]"]').eq(index).val().trim() !== '';
            }).length > 0;

            if (requiereInventario && !inventarioCompleto) {
                camposFaltantes.push('Inventario (debes completar al menos un ítem)');
            }

            // Mostrar mensaje de campos faltantes
            if (camposFaltantes.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campos incompletos',
                    html: '<b>Por favor, completa los siguientes campos:</b><br><ul>' + 
                        camposFaltantes.map(campo => `<li>${campo}</li>`).join('') + 
                        '</ul>'
                });
                return;
            }

            // Verificar si el nombre del evento ya existe antes de actualizar
            $.ajax({
                url: "/eventos/verificar-nombre",
                method: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    nombre: nombreEvento,
                    evento_id: eventoId,
                    espacio: espacio,
                    fecha_realizacion: fechaRealizacion
                },
                beforeSend: function() {
                    $('#btnActualizarEvento').prop('disabled', true);
                },
                success: function(response) {
                    if (response.existe) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Nombre de evento duplicado',
                            text: 'Ya existe un evento con este nombre. Por favor, elige otro nombre.'
                        });
                        $('#btnActualizarEvento').prop('disabled', false);
                    } else {
                        // Solo mostrar la confirmación si el nombre no está duplicado
                        Swal.fire({
                            title: '¿Deseas actualizar este evento?',
                            text: 'Comprueba la información antes de actualizar el evento',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#29FD53',
                            cancelButtonColor: '#9b9b9b',
                            confirmButtonText: 'Sí, Actualizar',
                            cancelButtonText: 'Comprobar información'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                var formData = new FormData($('#formularioActualizado')[0]);
                                formData.append('_token', '{{ csrf_token() }}');
                                var flyer = $('#flyer').prop('files')[0];
                                if (flyer) {
                                    formData.append('flyer', flyer);
                                }

                                $.ajax({
                                    url: "/eventos/" + eventoId + "/actualizar",
                                    method: "POST",
                                    data: formData,
                                    contentType: false,
                                    processData: false,
                                    beforeSend: function() {
                                        Swal.fire({
                                            title: 'Actualizando...',
                                            text: 'Por favor, espera un momento.',
                                            allowOutsideClick: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });
                                    },
                                    success: function(response) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Evento actualizado!',
                                            text: 'El evento se ha actualizado exitosamente.'
                                        }).then(() => location.reload());
                                    },
                                    error: function(xhr) {
                                        var errorMessage = 'Error al actualizar el evento.';
                                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                                        }
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            html: errorMessage
                                        });
                                    },
                                    complete: function() {
                                        $('#btnActualizarEvento').prop('disabled', false);
                                    }
                                });
                            } else {
                                $('#btnActualizarEvento').prop('disabled', false);
                            }
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        text: 'Ocurrió un error al verificar el nombre del evento.'
                    });
                    $('#btnActualizarEvento').prop('disabled', false);
                }
            });
        });

        // Función para validar si hay algún evento creado en la fecha y hora seleccionadas
        function validarEvento() {
            // Obtener los valores de los campos necesarios
            var lugar = $('#lugar').val();
            var espacio = $('#espacio').val();
            var fechaRealizacion = $('#fechaRealizacion').val();
            var horaInicio = $('#horaInicio').val();
            var horaFin = $('#horaFin').val();

            // Convertir la fecha y hora a un formato compatible para la comparación
            var fechaHoraInicio = new Date(fechaRealizacion + 'T' + horaInicio);
            var fechaHoraFin = new Date(fechaRealizacion + 'T' + horaFin);

            // Verificar si la fecha seleccionada es anterior a la fecha actual
            var fechaActual = new Date();
            if (fechaHoraInicio < fechaActual) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No puedes seleccionar una fecha de realización anterior al día de hoy.'
                });
                return false;
            }

            // **Validación 1**: No permitir cambiar la fecha al mismo día
            var fechaHoy = fechaActual.toISOString().slice(0, 10);
            if (fechaRealizacion === fechaHoy) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No puedes cambiar la fecha de realización para hoy mismo.'
                });
                return false;
            }

            // Verificar si faltan menos de 72 horas para la fecha seleccionada
            var diferenciaHoras = (fechaHoraInicio - fechaActual) / (1000 * 60 * 60);
            if (diferenciaHoras < 72) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No puedes crear un evento si faltan menos de 72 horas para la fecha seleccionada.'
                });
                return false;
            }

            // Verificar si la hora de inicio es anterior a la hora de fin
            if (fechaHoraInicio >= fechaHoraFin) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'La hora de inicio debe ser anterior a la hora de fin.'
                });
                return false;
            }

            // Verificar si la hora de inicio es anterior a la hora actual (solo si es la fecha actual)
            var horaActual = fechaActual.getHours() + ':' + fechaActual.getMinutes();
            if (fechaRealizacion === fechaHoy && horaInicio <= horaActual) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'La hora de inicio debe ser posterior a la hora actual.'
                });
                return false;
            }

            // Obtener la información de los eventos
            var eventos = obtenerInformacionEventos();

            // Iterar sobre los eventos existentes y realizar la comparación
            for (var i = 0; i < eventos.length; i++) {
                var evento = eventos[i];
                // Agregar una condición para verificar si el evento actual es el mismo que se está editando
                if (evento.id != '{{ $evento->id }}' && evento.lugar == lugar && evento.espacio == espacio) {
                    var eventoInicio = new Date(evento.fechaRealizacion + 'T' + evento.horaInicio);
                    var eventoFin = new Date(evento.fechaRealizacion + 'T' + evento.horaFin);

                    // Verificar si hay superposición de horarios
                    if (
                        (fechaHoraInicio >= eventoInicio && fechaHoraInicio < eventoFin) ||
                        (fechaHoraFin > eventoInicio && fechaHoraFin <= eventoFin) ||
                        (fechaHoraInicio <= eventoInicio && fechaHoraFin >= eventoFin)
                    ) {
                        // Mostrar mensaje de error y detener el envío del formulario
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'El horario seleccionado para este evento coincide con uno anteriormente agendado. Por favor, revise el calendario para confirmar la disponibilidad. Tenga en cuenta que un espacio no puede tener varios eventos coincidiendo en la misma hora.'
                        });
                        return false;
                    }
                }
            }

            // Si no hay conflictos, retornar true
            return true;
        }

        // Función para obtener la información de los eventos
        function obtenerInformacionEventos() {
            // Realizar la petición para obtener la información de los eventos
            var eventos;
            $.ajax({
                url: '/obtener-info',
                method: 'GET',
                async: false, // Hacer la petición síncrona para esperar la respuesta
                success: function(data) {
                    eventos = data.eventos;
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener la información de los eventos:', error);
                }
            });
            return eventos;
        }
    });
</script>
<script>
        function inicializarPagina() {
            toggleInventario();
            obtenerEspacios({{ $evento->lugar ?? 'null' }});
            guardarEspacioSeleccionado();
        }
    
        function guardarEspacioSeleccionado() {
            var espacioSeleccionado = "{{ $evento->espacio ?? '' }}";
            if (espacioSeleccionado) {
                document.getElementById('espacio').value = espacioSeleccionado;
            }
        }
</script>
</body>
</html>
