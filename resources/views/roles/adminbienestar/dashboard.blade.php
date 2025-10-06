@extends('layouts.app')

@section('title', 'Dashboard - AdminBienestar')

@section('content')
<style>
    :root{
        --uv-rojo: #cd1f32;      /* color institucional */
        --sidebar-w: 240px;      /* ancho del sidebar */
        --nav-h: 70px;  /* fallback si el JS no alcanza a medir */
        --nav-gap:12px;       /* espacio entre botones del navbar */ 
    }

    /* Evitar doble scroll: el scroll estará SOLO en el panel de contenido */
    html, body {
        height: 100%;
        overflow: hidden; /* importante para que no aparezca un scroll del body */
        background: #f8f9fa !important;
    }

    /* Contenedor fijo bajo el navbar que ocupa toda la ventana */
    .dashboard-shell {
        position: fixed;
        top: var(--nav-h);
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        width: 100vw;
        overflow: hidden; /* evitar scroll aquí; solo scrollea main */
        z-index: 1;       /* por debajo del navbar del layout */
    }

    /* Barra lateral */
    .dash-sidebar {
        width: var(--sidebar-w);
        background: var(--uv-rojo);
        color: #fff;
        padding-top: 24px;
        border-top: 1px solid #b10024; /* divide del navbar, visualmente limpio */
        box-shadow: 2px 0 8px #0001;
        overflow-y: auto; /* si el menú crece, scrollea SOLO el menú */
        -webkit-overflow-scrolling: touch;
        flex-shrink: 0;
    }
    .dash-sidebar h2 {
        margin: 0 0 18px 24px;
        font-weight: 800;
        font-size: 1.6rem;
    }
    .dash-menu { list-style: none; margin: 0; padding: 0; }
    .dash-menu li { border-left: 3px solid transparent; }
    .dash-menu a {
        color: #fff;
        text-decoration: none;
        display: block;
        padding: 12px 22px;
        font-size: 1.05rem;
        transition: background .15s ease;
    }
    .dash-menu li:hover,
    .dash-menu li.active {
        background: #b10024;
        border-left-color: #fff;
    }

    /* Área principal con scroll propio */
    .dash-main {
        flex: 1;
        background: #f8f9fa;
        padding: 28px;
        overflow-y: auto;           /* AQUÍ vive el scroll de contenido */
        -webkit-overflow-scrolling: touch;
        min-width: 0;
    }

    /* Tarjetas */
    .card-row { display: flex; gap: 18px; margin-bottom: 28px; flex-wrap: wrap; }
    .uv-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px #0001;
        padding: 22px;
        min-width: 260px;
        flex: 1;
    }
    .uv-card h5 { font-weight: 700; margin-bottom: 8px; }
    .uv-card p { color: #555; margin-bottom: 12px; }
    .uv-btn {
        background: var(--uv-rojo);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 7px 16px;
        text-decoration: none;
        display: inline-block;
    }
    .uv-btn:hover { background: #b10024; color: #fff; }

    /* Tabla */
    .table-mov { width: 100%; border-collapse: collapse; }
    .table-mov th, .table-mov td { border: 1px solid #e5e5e5; padding: 10px; }
    .table-mov th { background: #222; color: #fff; }

    .pinned { color: var(--uv-rojo); margin-right: 6px; }

    /* Responsive */
    @media (max-width: 992px) {
        :root { --sidebar-w: 210px; }
    }
    @media (max-width: 768px) {
        .dashboard-shell { top: calc(var(--nav-h) + 48px); } /* margen extra si el navbar se parte */
        .dash-sidebar { position: absolute; height: 100%; z-index: 2; }
        .dash-main { padding: 16px; }
    }
</style>

@php
    $nombreCompleto = auth()->user()->name;
    $primerNombre = explode(' ', $nombreCompleto)[0];
    $primerNombreMayuscula = ucfirst(strtolower($primerNombre));
@endphp

<div class="dashboard-shell" id="dashShell">
    <aside class="dash-sidebar">
        <h2>Panel Admistrativo</h2>
        <ul class="dash-menu">
            <li class="active"><a href="#">Inicio</a></li>
            <li><a href="{{ route('admin.estudiantes') }}">Ir a Gestión</a></li>
            <li><a href="{{ route('admin.convocatorias') }}">Convocatorias</a></li>
            <li><a href="{{ \Illuminate\Support\Facades\Route::has('admin.cupos.index') ? route('admin.cupos.index') : '#' }}">Cupos y Asistencias</a></li>
            <li><a href="{{ Route::has('admin.reportes') ? route('admin.reportes') : '#' }}">Reportes</a></li>
            {{-- Configuración eliminada por solicitud --}}
        </ul>
    </aside>

    <main class="dash-main">
        <h1>Bienvenido, {{ $primerNombreMayuscula }}! 👋</h1>

        <div class="card-row">
            <div class="uv-card">
                <h5>Estudiantes</h5>
                <p>Gestiona los estudiantes, edita datos y administra cupos.</p>
                <a class="uv-btn" href="{{ route('admin.estudiantes') }}">Ir a Gestión</a>
            </div>
            <div class="uv-card">
                <h5>Convocatorias</h5>
                <p>Crea, edita y administra convocatorias de subsidios.</p>
                <a class="uv-btn" href="{{ route('admin.convocatorias') }}">Ver Convocatorias</a>
            </div>
            <div class="uv-card">
                <h5>Cupos y Asistencias</h5> {{-- NUEVO --}}
                <p>Genera cupos, asigna por prioridad y controla asistencia diaria.</p>
                <a class="uv-btn" href="{{ \Illuminate\Support\Facades\Route::has('admin.cupos.index') ? route('admin.cupos.index') : '#' }}">Abrir módulo</a>
            </div>
        </div>

        <h4><span class="pinned">★</span>Últimos Movimientos</h4>
        <table class="table-mov">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Acción</th>
                    <th>Usuario</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>16/09/2025</td>
                    <td>Nuevo estudiante</td>
                    <td>Admin</td>
                    <td>Se registró a Juan Pérez</td>
                </tr>
                <tr>
                    <td>15/09/2025</td>
                    <td>Reporte generado</td>
                    <td>Admin</td>
                    <td>Informe mensual de subsidios</td>
                </tr>
                <tr>
                    <td>14/09/2025</td>
                    <td>Convocatoria cerrada</td>
                    <td>Admin</td>
                    <td>Convocatoria 2025-II</td>
                </tr>
            </tbody>
        </table>
    </main>
</div>

@push('scripts')
<script>
    // Medir altura real del navbar del layout y guardarla en --nav-h
    function setNavbarHeightVar() {
        const nav = document.querySelector('.navbar');
        const h = nav ? Math.round(nav.getBoundingClientRect().height) : 70;
        document.documentElement.style.setProperty('--nav-h', h + 'px');
    }
    setNavbarHeightVar();
    window.addEventListener('resize', setNavbarHeightVar);

    // Prevenir scroll accidental del body: solo scrollean .dash-main o .dash-sidebar
    document.body.addEventListener('wheel', (e) => {
        const target = e.target.closest('.dash-main, .dash-sidebar');
        if (!target) e.preventDefault();
    }, { passive: false });
</script>
@endpush
@endsection