@extends('layouts.app')

@section('title', 'Dashboard - Estudiante')

@section('content')
    <div class="container mt-5">
        @php
            $nombreCompleto = auth()->user()->name;
            $primerNombre = explode(' ', $nombreCompleto)[0];
            $primerNombreMayuscula = ucfirst(strtolower($primerNombre));
        @endphp
        <h1>Bienvenido, {{ $primerNombreMayuscula }}! 👋</h1>
        <p>En esta página encontrarás las funciones disponibles para el rol de estudiante</p>
    </div>

    <!-- Estado de la sesión -->
    <div class="container mt-3">
        <div class="session-status">
            @if(Auth::check())
                <span class="text-success">✓ Sesión activa</span>
            @else
                <span class="text-danger">✗ Sin sesión</span>
            @endif
        </div>
    </div>
@endsection
