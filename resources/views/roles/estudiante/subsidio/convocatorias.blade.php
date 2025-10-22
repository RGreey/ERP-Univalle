@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title', 'Convocatorias abiertas')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Convocatorias abiertas de Subsidio</h2>
        <a href="{{ route('subsidio.postulaciones.index') }}" class="btn btn-outline-dark">
            Mis postulaciones
        </a>
    </div>

    @if($convocatorias->isEmpty())
        <div class="alert alert-info">No hay convocatorias abiertas en este momento.</div>
    @else
        <div class="list-group">
            @foreach($convocatorias as $c)
                @php
                    // Si cargamos la relación filtrada por el usuario, aquí habrá 0 o 1 registro
                    $miPost = $c->postulaciones->first();
                @endphp

                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">{{ $c->nombre }}</div>
                        <small>Del {{ \Carbon\Carbon::parse($c->fecha_apertura)->format('Y-m-d') }} al {{ \Carbon\Carbon::parse($c->fecha_cierre)->format('Y-m-d') }}</small>
                        @if($miPost)
                            <div class="mt-1">
                                <span class="badge bg-secondary">Estado: {{ ucfirst($miPost->estado) }}</span>
                                <small class="text-muted ms-2">Enviada: {{ $miPost->created_at->format('Y-m-d H:i') }}</small>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex gap-2">
                        @if($miPost)
                            <a class="btn btn-outline-primary" href="{{ route('subsidio.postulaciones.show', $miPost->id) }}">
                                Ver postulación
                            </a>
                        @else
                            <a class="btn btn-primary" href="{{ route('subsidio.postulacion.create', $c->id) }}">
                                Postular
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection