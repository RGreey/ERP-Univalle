@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title','Detalle del reporte (Restaurante)')

@push('head')
<link rel="manifest" href="/restaurante/manifest.webmanifest">
<meta name="theme-color" content="#cd1f32">
@endpush

@section('content')
<div class="container">
<h3 class="mb-3">Reporte</h3>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card">
    <div class="card-body">
    <div class="small text-muted">{{ $reporte->created_at->format('Y-m-d H:i') }}</div>
    <h5 class="mb-0">{{ $reporte->titulo ?? 'Sin título' }}</h5>
    <div class="text-muted mb-2">
        Tipo: {{ ucfirst(str_replace('_',' ',$reporte->tipo)) }} · Sede: {{ $reporte->sede ? ucfirst($reporte->sede) : 'N/A' }}
    </div>

    @php
        $badge = match($reporte->estado){
            'pendiente'=>'secondary','en_proceso'=>'info','resuelto'=>'success','archivado'=>'dark', default=>'secondary'
        };
    @endphp
    <span class="badge bg-{{ $badge }}">{{ $reporte->estado }}</span>

    <hr>
    <div style="white-space:pre-line">{{ $reporte->descripcion }}</div>

    @if(!empty($reporte->admin_respuesta))
        <hr>
        <div class="fw-semibold mb-1">Respuesta de Bienestar</div>
        <div class="border rounded p-2 bg-light" style="white-space:pre-line">{{ $reporte->admin_respuesta }}</div>
        <div class="text-muted small mt-1">Actualizado: {{ $reporte->updated_at->format('Y-m-d H:i') }}</div>
    @endif
    </div>
</div>

<div class="mt-3">
    <a class="btn btn-link" href="{{ route('app.restaurante.reportes.index') }}">Volver</a>
</div>
</div>
@endsection