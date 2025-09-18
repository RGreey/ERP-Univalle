<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>ERP Universidad del Valle</title>
    <style>
        /* Estilos generales */
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            overflow-x: hidden;
        }

        /* Estilos de la cabecera */
        .header {
            background-color: #cd1f32;
            color: #fff;
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }




        .header nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .header nav ul li {
            margin-right: 1.5rem;
        }

        .header nav ul li:last-child {
            margin-right: 0;
        }

        .header nav ul li a {
            color: #fff; 
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            position: relative;
        }

        .header nav ul li a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: #fff;
            bottom: 0;
            left: 0;
            transition: width 0.3s ease;
        }

        .header nav ul li a:hover:after {
            width: 100%;
        }

        /* Alinea los botones a la derecha */
        .header .buttons {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .header .buttons .btn {
            margin-left: 1rem;
            background-color: transparent;
            color: #fff;
            border: 1px solid #fff;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .header .buttons .btn:hover {
            background-color: #fff;
            color: #cd1f32;
        }

        /* Contenido principal */
        .main-content {
            margin-top: auto;
            padding: 2rem 1rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Estilos del texto descriptivo */
        .description {
            text-align: center;
            margin-bottom: 2rem;
        }

        .description h1 {
            font-size: 2.5rem;
            color: #cd1f32;
            margin-bottom: 0.5rem;
        }

        .description p {
            font-size: 1.2rem;
            color: #555;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Estilos del carrusel */
        .carousel-container {
            position: relative;
            max-width: 1000px;
            margin: 0 auto 3rem auto;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .carousel {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .carousel-slide {
            min-width: 100%;
            position: relative;
        }

        .carousel-slide img {
            width: 100%;
            height: 500px;
            object-fit: cover;
        }

        .carousel-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            color: white;   
            padding: 1rem;
            text-align: center;
        }

        .carousel-caption h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }

        .carousel-caption p {
            margin: 0;
            font-size: 1rem;
        }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.5);
            color: #333;
            border: none;
            cursor: pointer;
            padding: 1rem;
            font-size: 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .carousel-btn:hover {
            background: rgba(255, 255, 255, 0.8);
        }

        .carousel-btn.prev {
            left: 10px;
        }

        .carousel-btn.next {
            right: 10px;
        }

        .carousel-indicators {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: #fff;
        }

        /* Sección de características */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #cd1f32;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Estilos del footer */
        .footer {
            background-color: #fff;
            color: #333;
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #eee;
            margin-top: 2rem;
        }

        .footer p {
            margin: 0;
        }

        /* Estilos para pantallas pequeñas */
        @media (max-width: 768px) {
            .header .container {
                flex-direction: column;
                align-items: center;
                padding: 1rem;
            }

            .header .logo {
                margin-bottom: 1rem;
            }

            .header nav ul {
                flex-direction: column;
                text-align: center;
                margin-bottom: 1rem;
            }

            .header nav ul li {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }

            .header .buttons {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .header .buttons .btn {
                margin: 0.5rem;
            }

            .main-content {
                margin-top: 12rem;
            }

            .carousel-slide img {
                height: 250px;
            }

            .carousel-caption h3 {
                font-size: 1.2rem;
            }

            .carousel-caption p {
                font-size: 0.9rem;
            }

            .carousel-btn {
                padding: 0.5rem;
                font-size: 1rem;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <style>
        /* Estilos generales */
        body {
            margin: 0;
            font-family:'Poppins', sans-serif; /* Cambiado a una fuente genérica */
        }

        /* Estilos de la cabecera */
        .header {
            background-color: #cd1f32; /* Cambiado a rojo */
            color: #fff;
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 3rem;
            padding-right: 3rem;
        }

        .header .logo {
            width: 100px;
            height: 30px;
            background-color: #fff;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .description {
            text-align: center;
            font-size: 2rem;
            margin-top: 8rem; /* Aumentamos el margen superior para compensar el header fijo */
            padding: 0 1rem; /* Un poco de relleno lateral para móviles */
        }

        .header .logo img {
            width: auto;
            height: 100%;
        }

        .header nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .header nav ul li {
            margin-right: 1rem;
        }

        .header nav ul li:last-child {
            margin-right: 0;
        }

        .header nav ul li a {
            color: #fff; 
            text-decoration: none;
        }



        /* Alinea los botones a la derecha */
        .header .buttons {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .header .buttons .btn {
            margin-left: 1rem;
            background-color: #cd1f32; /* Cambiado a rojo */
            color: #fff; /* Cambiado a blanco */
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none; /* Añadido para quitar subrayado en el botón */
        }

        .header .buttons .btn:hover {
            background-color: #fff; /* Cambiado a blanco */
            color: #cd1f32; /* Cambiado a rojo */
        }

        /* Estilos del texto descriptivo */
        .description {
            text-align: center;
            font-size: 2rem;
            margin-top: 6rem;
        }
        /* Estilos del footer */
        .footer {
            background-color: #ffff; 
            color: #333; 
            padding: 1rem;
            text-align: center;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }



    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="{{ asset('imagenes/header_logo.jpg')}}" alt="Logo Universidad del Valle">
            </div>
            <nav>
                <ul>
                    <li><a href="#">Inicio</a></li>
                    <li><a href="#">Servicios</a></li>
                    <li><a href="#">Contacto</a></li>
                </ul>
            </nav>
            <div class="buttons">
                @if (Route::has('login'))
                    <div class="sm:fixed sm:top-0 sm:right-0 p-3 text-right z-10">
                        @auth
                            <a href="{{ url('/home') }}" class="btn">Home</a>
                        @else
                            <a  href="{{ route('login') }}" class="btn">Iniciar sesión</a>
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="description">
            <h1>¡Bienvenido al ERP de la Universidad del Valle!</h1>
            <p>Sistema integral para la gestión de eventos, monitorias y procesos académicos de la Seccional Eje Cafetero.</p>
        </section>

        <section class="carousel-container">
            <div class="carousel">
                <div class="carousel-slide">
                    <img src="{{ asset('imagenes/calendario.png')}}" alt="Modulo de Eventos">
                    <div class="carousel-caption">
                        <h3>Eventos Académicos</h3>
                        <p>Planificación y difusión de actividades académicas, culturales y administrativas en la sede.</p>
                    </div>
                </div>
                <div class="carousel-slide">
                    <img src="{{ asset('imagenes/seguimiento.png')}}" alt="Modulo de Monitorías">
                    <div class="carousel-caption">
                        <h3>Monitorías</h3>
                        <p>Gestión integral de convocatorias, postulaciones y asignaciones para el apoyo académico estudiantil.</p>
                    </div>
                </div>
                <div class="carousel-slide">
                    <img src="{{ asset('imagenes/estadisticas.png')}}" alt="Estadísticas">
                    <div class="carousel-caption">
                        <h3>Estadísticas y reportes</h3>
                        <p>Visualiza y descarga los reportes con datos clave para seguimiento y acreditación</p>
                    </div>
                </div>
            </div>
            <button class="carousel-btn prev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-btn next">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="carousel-indicators">
                <span class="indicator active"></span>
                <span class="indicator"></span>
                <span class="indicator"></span>
            </div>
        </section>

        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Gestión de Eventos</h3>
                <p>Organiza, programa y difunde eventos académicos, culturales y administrativos realizados en la sede. Registra participantes y gestiona la logística de manera centralizada.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <h3>Gestión de Monitorías</h3>
                <p>Administra convocatorias, postulaciones y asignaciones de monitorías académicas. Facilita el seguimiento de procesos y documentos requeridos por los estudiantes.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Reportes y Estadísticas</h3>
                <p>Genera reportes automáticos y visualiza estadísticas clave sobre eventos y monitorías para apoyar la toma de decisiones institucionales.</p>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>Desarrollado por Sebastian Giraldo y GIIDCE - © 2024 Universidad del Valle Sede Caicedonia</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.querySelector('.carousel');
            const slides = document.querySelectorAll('.carousel-slide');
            const prevBtn = document.querySelector('.carousel-btn.prev');
            const nextBtn = document.querySelector('.carousel-btn.next');
            const indicators = document.querySelectorAll('.indicator');
            
            let currentIndex = 0;
            const slideCount = slides.length;
            
            // Función para actualizar el carrusel
            function updateCarousel() {
                carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
                
                // Actualizar indicadores
                indicators.forEach((indicator, index) => {
                    if (index === currentIndex) {
                        indicator.classList.add('active');
                    } else {
                        indicator.classList.remove('active');
                    }
                });
            }
            
            // Evento para el botón anterior
            prevBtn.addEventListener('click', function() {
                currentIndex = (currentIndex - 1 + slideCount) % slideCount;
                updateCarousel();
            });
            
            // Evento para el botón siguiente
            nextBtn.addEventListener('click', function() {
                currentIndex = (currentIndex + 1) % slideCount;
                updateCarousel();
            });
            
            // Eventos para los indicadores
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', function() {
                    currentIndex = index;
                    updateCarousel();
                });
            });
            
            // Cambio automático de diapositivas cada 5 segundos
            setInterval(function() {
                currentIndex = (currentIndex + 1) % slideCount;
                updateCarousel();
            }, 5000);
        });
    </script>
</body>
</html>