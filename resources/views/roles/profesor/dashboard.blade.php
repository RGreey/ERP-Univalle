<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Profesor</title>

    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://kit.fontawesome.com/71e9100085.js" crossorigin="anonymous"></script>
    <style>
        #toggleButton {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            opacity: 0.7;
            transition: opacity 0.3s ease;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        #toggleButton:hover {
            opacity: 1;
        }

        #chartContainer {
            position: relative;
            width: 100%;
            height: 400px;
        }

        .container {
            margin-top: 20px;
        }

        .chartjs-axis-label {
            display: none; /* Hide the X-axis labels */
}
        
    </style>
</head>

<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="#">
            <img src="{{ asset('imagenes/header_logo.jpg')}}" alt="Logo de la universidad" style="max-height: 50px;">
        </a><!-- Aquí está el logo de la universidad -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Botón dropdown con las opciones de eventos -->
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="dropdownEventos" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ffffff; color: #000000;">
                    Eventos
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownEventos" style="background-color: #ffffff;">
                    <li><a class="dropdown-item" href="{{ route('crearEvento') }}" style="color: #000000;">Crear Evento</a></li>
                    @if(auth()->user()->hasRole('CooAdmin') || auth()->user()->hasRole('AuxAdmin') || auth()->user()->hasRole('Profesor'))
                    <li><a class="dropdown-item" href="{{ route('consultarEventos') }}" style="color: #000000;">Consultar tus eventos</a></li>
                    @endif
                </ul>
            </div>

            <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMonitoria" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ffffff; color: #000000;">
                        Monitorias
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMonitoria" style="background-color: #ffffff;">
                        @if(auth()->user()->hasRole('CooAdmin'))
                        <li><a class="dropdown-item" href="{{ route('periodos.crear') }}" style="color: #000000;">Consultar Periodo Academico</a></li>
                        <li><a class="dropdown-item" href="{{ route('convocatoria.index') }}" style="color: #000000;">Crear Convocatoria</a></li>
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
        <!-- Botón de opciones -->
        <div class="d-flex">
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-gear"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"> <i class="fa-solid fa-right-from-bracket"> </i> Cerrar sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>


    <!-- Contenido principal del dashboard -->
    <div class="container mt-5">
        @php
            $nombreCompleto = auth()->user()->name;
            $primerNombre = explode(' ', $nombreCompleto)[0];
            $primerNombreMayuscula = ucfirst(strtolower($primerNombre));
        @endphp
        <h1>Bienvenido, {{ $primerNombreMayuscula }}! 👋</h1>
        <p>En esta página encontrarás las funciones disponibles para el rol de profesor</p>
    </div>

    <!-- Formulario para cerrar sesión -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <div class="container mt-3">
        <div class="session-status">
            @if(Auth::check())
                <span class="text-success">✓ Sesión activa</span>
            @else
                <span class="text-danger">✗ Sin sesión</span>
            @endif
        </div>
    </div>
        
    <div class="container mt-4">
        <div class="row g-4">
            <!-- Gráfico 1: Eventos por mes -->
            <div class="col-12 col-md-6">
                <div class="card shadow h-100 border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-primary"><i class="fa-solid fa-calendar-days me-2"></i>Eventos por Mes</h5>
                        <div class="position-relative">
                            <canvas id="eventosPorMesChart"></canvas>
                            <button id="toggleButton" class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-2" title="Filtrar"><i class="fa-solid fa-filter"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Gráfico 2: Horas Solicitadas vs Aceptadas / Monitorías por Modalidad -->
            <div class="col-12 col-md-6">
                <div class="card shadow h-100 border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-info">
                            <i class="fa-solid fa-chart-line me-2"></i>
                            <span id="chartTitle">Horas Solicitadas vs Aceptadas</span>
                            <button id="toggleChartButton" class="btn btn-sm btn-outline-info position-absolute top-0 end-0 m-2" title="Cambiar vista">
                                <i class="fa-solid fa-exchange-alt"></i>
                            </button>
                        </h5>
                        <canvas id="horasConvocatoriaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .card {
            transition: box-shadow 0.3s;
        }
        .card:hover {
            box-shadow: 0 0 24px 0 rgba(0,0,0,0.12);
        }
        .card-title {
            font-weight: 600;
        }
        @media (max-width: 767px) {
            .card {
                margin-bottom: 1rem;
            }
        }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Datos de ejemplo de eventos por mes
        var meses = [
            @foreach($eventosPorMes as $evento)
                '{{ strftime("%B", mktime(0, 0, 0, $evento->mes, 1)) }}',
            @endforeach
        ].map(function(month) {
            return new Date(Date.parse(month + ' 1, 2000')).toLocaleString('es', { month: 'long' });
        });
        var cantidades = [
            @foreach($eventosPorMes as $evento)
                {{ $evento->total }},
            @endforeach
        ];

        // Datos de ejemplo de eventos por mes y programaDependencia
        var mesesPrograma = [
            @foreach($eventosPorMesPrograma as $evento)
                '{{ strftime("%B", mktime(0, 0, 0, $evento->mes, 1)) }} ({{ $evento->nombre_programa_dependencia }})',
            @endforeach
        ].map(function(month) {
            return new Date(Date.parse(month.split(' (')[0] + ' 1, 2000')).toLocaleString('es', { month: 'long' }) + ' (' + month.split(' (')[1];
        });
        var cantidadesPrograma = [
            @foreach($eventosPorMesPrograma as $evento)
                {{ $evento->total }},
            @endforeach
        ];

        // Colores para los meses
        var coloresMeses = [
            '#FFC0CB', '#ADD8E6', '#C1E0AA', '#FFD7AA', '#B19CD9 ', '#7392FF ',
            '#FF8B62 ', '#FF6482', '#33FFBD', '#FF5733', '#33FF57', '#3357FF'
        ];

        // Configurar el gráfico
        var ctx = document.getElementById('eventosPorMesChart').getContext('2d');
        var eventosPorMesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Eventos por Mes',
                    data: cantidades,
                    backgroundColor: coloresMeses,
                    borderColor:
                    coloresMeses,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Eventos por Mes'
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toLocaleString() + ' eventos'; // Mostrar solo el número de eventos
                                }
                                return label;
                            }
                        }
                }

                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutBounce'
                },
                responsive: true,
                interaction: {
                    mode: 'nearest',
                    intersect: false
                }
            }
        });
        
        

        // Agregar evento al botón para alternar
        function updateChart(labels, data, labelText) {
            eventosPorMesChart.data.labels = labels;
            eventosPorMesChart.data.datasets[0].data = data;
            eventosPorMesChart.data.datasets[0].label = labelText;
            eventosPorMesChart.update();
        }

        // Botón para alternar entre vistas
        toggleButton.addEventListener('click', function() {
            if (eventosPorMesChart.data.datasets[0].label === 'Eventos por Mes') {
            updateChart(mesesPrograma, cantidadesPrograma, 'Eventos por Mes y ProgramaDependencia');
            } else {
            updateChart(meses, cantidades, 'Eventos por Mes');
            }
        });





        // Variables globales para los datos de ambos gráficos
        let horasChartData = null;
        let monitoriasChartData = null;
        let currentChart = null;
        let isShowingHoras = true;

        // --- Cargar datos de horas solicitadas vs aceptadas ---
        fetch('/dashboard/estadisticas-horas-convocatoria')
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    console.log('No hay datos de convocatorias disponibles');
                    return;
                }
                horasChartData = data;
                
                // Cargar también los datos de monitorías
                return fetch('/convocatoria/estadisticas-monitorias');
            })
            .then(response => response.json())
            .then(data => {
                monitoriasChartData = data;
                
                // Crear el gráfico inicial (horas)
                createHorasChart();
                
                // Configurar el botón de alternancia
                document.getElementById('toggleChartButton').addEventListener('click', toggleChart);
            })
            .catch(error => {
                console.error('Error al cargar estadísticas:', error);
            });

        function createHorasChart() {
            if (!horasChartData || horasChartData.length === 0) return;

            const modalidades = horasChartData.map(item => item.modalidad);
            const horasSolicitadas = horasChartData.map(item => item.horas_solicitadas);
            const horasAceptadas = horasChartData.map(item => item.horas_aceptadas);
            const limiteHoras = horasChartData.map(item => item.limite_horas);
            const porcentajes = horasChartData.map(item => item.porcentaje_aceptacion);
            
            const convocatoria = horasChartData[0].convocatoria;
            
            const ctx = document.getElementById('horasConvocatoriaChart').getContext('2d');
            
            if (currentChart) {
                currentChart.destroy();
            }
            
            currentChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: modalidades,
                    datasets: [
                        {
                            label: 'Horas Solicitadas',
                            data: horasSolicitadas,
                            backgroundColor: '#ff6384',
                            borderColor: '#ff6384',
                            borderWidth: 1
                        },
                        {
                            label: 'Horas Aceptadas',
                            data: horasAceptadas,
                            backgroundColor: '#36a2eb',
                            borderColor: '#36a2eb',
                            borderWidth: 1
                        },
                        {
                            label: 'Límite de Horas',
                            data: limiteHoras,
                            backgroundColor: '#cc65fe',
                            borderColor: '#cc65fe',
                            borderWidth: 1,
                            type: 'line',
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: {
                            display: true,
                            text: `Horas por Modalidad - ${convocatoria}`
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const dataIndex = context.dataIndex;
                                    const porcentaje = porcentajes[dataIndex];
                                    let label = context.dataset.label + ': ' + context.parsed.y + ' h';
                                    if (context.dataset.label === 'Horas Aceptadas') {
                                        label += ` (${porcentaje}% del límite)`;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Horas' }
                        }
                    }
                }
            });
        }

        function createMonitoriasChart() {
            if (!monitoriasChartData || monitoriasChartData.length === 0) return;

            const modalidades = monitoriasChartData.map(item => item.modalidad);
            const monitores = monitoriasChartData.map(item => item.monitores);
            const horas = monitoriasChartData.map(item => item.horas);
            
            const ctx = document.getElementById('horasConvocatoriaChart').getContext('2d');
            
            if (currentChart) {
                currentChart.destroy();
            }
            
            currentChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: modalidades,
                    datasets: [
                        {
                            label: 'Monitores',
                            data: monitores,
                            backgroundColor: '#4e79a7',
                            yAxisID: 'y',
                        },
                        {
                            label: 'Horas',
                            data: horas,
                            backgroundColor: '#f28e2b',
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: {
                            display: true,
                            text: 'Monitorías por Modalidad (Aprobadas)'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if(context.dataset.label === 'Horas') {
                                        return context.dataset.label + ': ' + context.parsed.y + ' h';
                                    }
                                    return context.dataset.label + ': ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Monitores' },
                            position: 'left',
                        },
                        y1: {
                            beginAtZero: true,
                            title: { display: true, text: 'Horas' },
                            position: 'right',
                            grid: { drawOnChartArea: false },
                        }
                    }
                }
            });
        }

        function toggleChart() {
            isShowingHoras = !isShowingHoras;
            
            if (isShowingHoras) {
                document.getElementById('chartTitle').textContent = 'Horas Solicitadas vs Aceptadas';
                createHorasChart();
            } else {
                document.getElementById('chartTitle').textContent = 'Monitorías por Modalidad';
                createMonitoriasChart();
            }
        }
    });
    </script>
    <!-- Script de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
