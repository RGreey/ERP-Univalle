<!DOCTYPE html>
<html lang="en">
<head>
    <title>Crear Evento</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    
</head>
<body>
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
                                                    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('Profesor') || auth()->user()->hasRole('Administrativo'))
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
                            <form id="formularioEvento" enctype="multipart/form-data">
                                @csrf
                                <!-- Nombre del evento -->
                                <div class="mb-3">
                                    <label for="nombreEvento" class ="text-black poppins-regular margen-campos">Nombre del evento:</label>
                                    <input type="text" id="nombreEvento" name="nombreEvento" class = "poppins-regular campo-largo"required>
                                </div>
                                <!-- Propósito del evento -->
                                <div class="mb-3">
                                    <label for="propositoEvento" class ="text-black poppins-regular">Propósito del evento:</label>
                                    <textarea id="propositoEvento" name="propositoEvento" placeholder= "Describa el proposito del evento" class ="text-black poppins-regular campo-proposito" required></textarea>
                                </div>
                                <div class="row margen-campos">
                                    <div class="col-md-4">
                                        <!-- Fecha -->
                                        <div class="form-outline form-white mb-3">
                                            <label for="lugar" class ="text-black poppins-regular mb-2">Fecha de realizacion</label>
                                            <input type="date" class="form-control form-control-lg" id="fechaRealizacion" name="fechaRealizacion" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Hora de inicio -->
                                        <div class="form-outline form-white mb-3">
                                            <label for="horaInicio" class="text-black poppins-regular mb-2">Hora inicio</label>
                                            <input id="horaInicio" class="form-control form-control-lg" type="time" name="horaInicio" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Hora de fin -->
                                        <div class="form-outline form-white mb-3">
                                            <label for="horaFin" class="text-black poppins-regular mb-2">Hora fin</label>
                                            <input id="horaFin" class="form-control form-control-lg" type="time" name="horaFin" required>
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
                                    <select id="lugar" name="lugar" class="text-black poppins-regular" required>
                                        <option value="">Seleccione una opción</option>
                                        @foreach ($lugares as $lugar)
                                            <option value="{{ $lugar->id }}" class="text-black poppins-regular">{{ $lugar->nombreLugar }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Espacio -->                            
                                <div class="mb-3">
                                    <label for="espacio" class="text-black poppins-regular margen-campos">Espacio:</label>
                                    <select id="espacio" name="espacio" class="text-black poppins-regular" required>
                                        <option value="">Seleccione un espacio</option>
                                    </select>
                                </div>
                                <div style="text-align: right; margin-bottom: 10px;">
                                    <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleAllCheckboxes(this)">
                                    <label class="form-check-label text-black poppins-regular" for="selectAll">Seleccionar todos</label>
                                </div>

                                <!-- Checkbox para requerir inventario -->
                                <div class="mb-3">
                                    <h3 class="text-black poppins-regular ">2. Refrigerios - Alimentacion</h3>
                                    <div class="form-check">
                                
                                        <label class="form-check-label text-black poppins-regular" for="estacion_bebidas">Estación de café, Agua, Aromáticas</label>
                                        <input class="form-check-input" type="checkbox" id="estacion_bebidas" name="estacion_bebidas" value="true">
                                    </div> 
                                    <div class="form-check">
                                        <label for="requiereInventario" class="form-check-label text-black poppins-regular">¿Requiere refrigerio o alimentacion?</label>                                      
                                        <input type="checkbox" id="requiereInventario" name="requiereInventario" class="form-check-input" onchange="toggleInventario()">
                                    </div>
                                </div>

                
                                <!-- Formulario de inventario (inicialmente oculto) -->
                                <div id="formularioInventario" style="display: none;">
                                    <div class="mb-3 inventario-item">
                                        <label class="text-black poppins-regular">Relacione aquí el nombre del producto</label>
                                        <input type="text" name="tipoInventario[]" class="form-control">
                                        <label class="text-black poppins-regular">Cantidad:</label>
                                        <input type="number" name="cantidadInventario[]" class="form-control" >
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" onclick="agregarItemInventario()" class="btn btn-secondary">Agregar otro ítem <i class="fa-solid fa-plus"></i> </button>
                                    </div>
                                </div>



                                <!-- Detalles del evento -->
                                <div class="mb-3">

                                    <!-- Sección de Logística -->
                                    <div class="mb-3">
                                        <h3 class="text-black poppins-regular ">3. Logística</h3>
                                        <div class="row">
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="transporte" name="transporte" value=true>
                                                    <label class="form-check-label text-black poppins-regular" for="transporte">Transporte</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="audio" name="audio" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="audio">Audio</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="proyeccion" name="proyeccion" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="proyeccion">Proyección</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="internet" name="internet" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="internet">Internet</label>
                                                </div>  
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="presentacion_cultural" name="presentacion_cultural" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="presentacion_cultural">Presentación Cultural</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="certificacion" name="certificacion" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="certificacion">Certificacion</label>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="flyer" class="text-black poppins-regular">Flyer del Evento (Opcional)</label>
                                        <input type="file" class="form-control" id="flyer" name="flyer" accept="image/*">
                                    </div>
                                    <!-- Sección de Publicidad -->
                                    <div class="mb-3">
                                        <h3 class="text-black poppins-regular">4. Divulgación del evento</h3>
                                        <div class="row">
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="diseñoPublicitario" name="diseñoPublicitario" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="diseñoPublicitario">Piezas Publicitarias</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="redes" name="redes" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="redes">Redes</label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="correo" name="correo" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="correo">Correo</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="whatsapp" name="whatsapp" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="whatsapp">Whatsapp</label>
                                                </div>
                                            </div>                            
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="cubrimientoMedios" name="cubrimiento_medios" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="cubrimientoMedios">Cubrimiento de Medios</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección de Recursos Humanos -->
                                    <div class="mb-3">
                                        <h3 class="text-black poppins-regular">5. Recursos Humanos</h4>
                                        <div class="row">
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="personal_recibo" name="personal_recibo" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="personal_recibo">Personal Recibo</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="seguridad" name="seguridad" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="seguridad">Seguridad</label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="bienvenida" name="bienvenida" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="bienvenida">Bienvenida</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="defensoria_civil" name="defensoria_civil" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="defensoria_civil">Defensoria Civil</label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="servicio_general" name="servicio_general" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="servicio_general">Servicios Generales</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="otro_Recurso" name="otro_Recurso" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="otro_Recurso">Otro(Especificar en detalles)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                    <h3 class="text-black poppins-regular">6. Otros Detalles</h4>
                                        <textarea class="form-control text-dark poppins-regular" id="otros" name="otros" rows="3" placeholder="Ingresa otros detalles aquí..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <div class="row">  
                                            <h3 class="text-black poppins-regular">7. Participantes</h3>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="estudiantes" name="estudiantes" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="estudiantes">Estudiantes</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="profesores" name="profesores" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="profesores">Profesores</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="administrativos" name="administrativos" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="administrativos">Administrativos</label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="empresarios" name="empresarios" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="empresarios">Empresarios</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="comunidad_general" name="comunidad_general" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="comunidad_general">Comunidad en General</label>
                                                </div>
                                            </div>
                                            <div class="col">    
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="egresados" name="egresados" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="egresados">Egresados</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="invitados_externos" name="invitados_externos" value="true">
                                                    <label class="form-check-label text-black poppins-regular" for="invitados_externos">Invitados Externos</label>
                                                </div>
                                            </div>    
                                        </div>
                                    </div>
                                    
                            </div>
                                <button type="button" id="btnCrearEvento" class="btn btn-success">Crear Evento </i></button>
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
    let dependenciasSeleccionadas = [];

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
                <button type="button" class="btn btn-sm btn-danger eliminar-dependencia" data-id="${selectedId}"><i class="bi bi-trash"></i> Eliminar</button>
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
                <input type="text" name="tipoInventario[]" class="form-control" >
                <label class="text-black poppins-regular">Cantidad:</label>
                <input type="number" name="cantidadInventario[]" class="form-control" >
                <button type="button" class="eliminarItem btn btn-secondary" onclick="eliminarItem(this)"><i class="fa-solid fa-minus"></i></button>
            `;
            if (camposExistente.length === 0 || todosCamposLlenos(camposExistente[camposExistente.length - 1])) {
                formularioInventario.appendChild(nuevoItem);
            } else {
                alert('Por favor, complete todos los campos del último ítem de inventario antes de agregar otro.');
            }
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
    // Función para seleccionar/deseleccionar todos los checkboxes
    function toggleAllCheckboxes(source) {
        var checkboxes = document.querySelectorAll('.form-check-input');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = source.checked;

            // Verificar si el checkbox es el de "requiereInventario" para disparar la función correspondiente
            if (checkbox.id === "requiereInventario") {
                toggleInventario(); // Llamar a la función que controla el formulario
            }
        });
    }

    // Función para mostrar/ocultar el formulario de inventario
    function toggleInventario() {
        var checkbox = document.getElementById("requiereInventario");
        var formularioInventario = document.getElementById("formularioInventario");

        if (checkbox.checked) {
            formularioInventario.style.display = "block";
        } else {
            formularioInventario.style.display = "none";
        }
    }

    // Asegurarse de que se ejecute la función al cargar la página si el checkbox ya está seleccionado
    document.addEventListener('DOMContentLoaded', function () {
        toggleInventario();
    });
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
            });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#btnCrearEvento').click(function() {
            // Validar el evento antes de confirmar su creación
            if (!validarEvento()) {
                return; // Detener el proceso si la validación no pasa
            }

            // Obtener los valores de los campos requeridos
            var nombreEvento = $('#nombreEvento').val();
            var propositoEvento = $('#propositoEvento').val();
            var fechaRealizacion = $('#fechaRealizacion').val();
            var horaInicio = $('#horaInicio').val();
            var horaFin = $('#horaFin').val();
            var lugar = $('#lugar').val();
            var espacio = $('#espacio').val();
            var requiereInventario = $('#requiereInventario').prop('checked');

            // Comprobar si algún campo requerido está vacío
            var camposFaltantes = [];

            // Verificar que los valores no sean undefined ni null antes de aplicar trim
            if (!nombreEvento || nombreEvento.trim() === '') {
                camposFaltantes.push('Nombre del evento');
            }
            if (!propositoEvento || propositoEvento.trim() === '') {
                camposFaltantes.push('Propósito del evento');
            }
            if (!fechaRealizacion || fechaRealizacion.trim() === '') {
                camposFaltantes.push('Fecha de realización');
            }
            if (!horaInicio || horaInicio.trim() === '') {
                camposFaltantes.push('Hora de inicio');
            }
            if (!horaFin || horaFin.trim() === '') {
                camposFaltantes.push('Hora de fin');
            }
            
            // Validar que las dependencias no estén vacías
            var dependenciasSeleccionadas = $('#dependenciasSeleccionadas').val();
            if (!dependenciasSeleccionadas || dependenciasSeleccionadas.trim() === '[]') {
                camposFaltantes.push('Programa o dependencia');
            }

            if (!lugar || lugar.trim() === '') {
                camposFaltantes.push('Lugar');
            }
            if (!espacio || espacio.trim() === '') {
                camposFaltantes.push('Espacio');
            }

            // Verificar si algún ítem de inventario está incompleto
            if (requiereInventario) {
                var inventarioCompleto = validarInventario();
                if (!inventarioCompleto) {
                    return; // Detener el envío del formulario si el inventario no está completo
                }
            }

            // Si hay campos faltantes, mostrar la alerta con los campos que faltan
            if (camposFaltantes.length > 0) {
                var mensajeError = 'Por favor, complete los siguientes campos requeridos:<br>';
                mensajeError += '<ul>';
                camposFaltantes.forEach(function(campo) {
                    mensajeError += '<li>' + campo + '</li>';
                });
                mensajeError += '</ul>';
                Swal.fire({
                    icon: 'error',
                    title: 'Campos incompletos',
                    html: mensajeError
                });
                return; // Detener el envío del formulario si hay campos faltantes
            }

            // Mostrar confirmación de creación
            Swal.fire({
                title: '¿Deseas crear este evento?',
                text: 'Comprueba la información antes de crear el evento',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#29FD53',
                cancelButtonColor: '#9b9b9b',
                confirmButtonText: 'Sí, Crear',
                cancelButtonText: 'Comprobar información'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Verificar si el nombre del evento ya existe
                    $.ajax({
                        url: "/eventos/verificar-nombre",
                        method: "POST",
                        data: {
                            _token: '{{ csrf_token() }}',
                            nombre: nombreEvento,
                            evento_id: 0, // Para creación es 0
                            espacio: espacio,
                            fecha_realizacion: fechaRealizacion
                        },
                        success: function(response) {
                            if (response.existe) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Nombre de evento duplicado',
                                    text: 'Ya existe un evento con este nombre en el mismo espacio. Por favor, elige otro nombre.'
                                });
                            } else {
                                Swal.fire({
                                    title: 'Guardando evento...',
                                    allowOutsideClick: false,
                                    onBeforeOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                // Crear un objeto FormData para enviar datos y archivos
                                var formData = new FormData($('#formularioEvento')[0]);

                                // Enviar los datos y el archivo flyer
                                $.ajax({
                                    url: "{{ route('guardarEvento') }}",
                                    method: "POST",
                                    data: formData,
                                    contentType: false,
                                    processData: false,
                                    success: function(response) {
                                        Swal.close();
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Evento creado!',
                                            text: 'El evento se ha creado exitosamente.'
                                        });

                                        // Limpiar el formulario después de crear el evento
                                        document.getElementById('formularioEvento').reset();
                                    },
                                    error: function(xhr, status, error) {
                                        Swal.close();

                                        if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors['tipoInventario[]']) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: 'Por favor, completa todos los tipos y cantidades de inventario.'
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: 'Ha ocurrido un error al crear el evento.'
                                            });
                                        }
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
                        }
                    });
                }
            });
        });


        // Función para validar si el inventario está completo
        function validarInventario() {
            var inventarioIncompleto = false;
            $('input[name="tipoInventario[]"]').each(function(index, tipo) {
                var tipoValor = $(tipo).val().trim();
                var cantidadValor = $('input[name="cantidadInventario[]"]').eq(index).val().trim();

                if (tipoValor === '' || cantidadValor === '') {
                    inventarioIncompleto = true;
                    return false; // Detener la iteración si se encuentra un ítem de inventario incompleto
                }
            });

            // Si el inventario está incompleto, mostrar mensaje de error
            if (inventarioIncompleto) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, completa todos los tipos y cantidades de inventario.'
                });
                return false;
            }

            return true;
        }

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
            if (fechaRealizacion === fechaActual.toISOString().slice(0, 10) && horaInicio <= horaActual) {
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
                if (evento.lugar == lugar && evento.espacio == espacio) {
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
                async: false, 
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
</body>
</html>

