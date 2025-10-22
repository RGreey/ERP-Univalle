@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title','Nuevo reporte (Restaurante)')

@push('head')
<link rel="manifest" href="/restaurante/manifest.webmanifest">
<meta name="theme-color" content="#cd1f32">
@endpush

@section('content')
<div class="container">
<h3 class="mb-3">Enviar reporte</h3>

@if($errors->any())
    <div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('app.restaurante.reportes.store') }}">
    @csrf
    <div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
        @foreach(['comportamiento','inasistencia_reiterada','higiene','otro'] as $t)
            <option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>
        @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Sede (opcional)</label>
        <select name="sede" class="form-select">
        <option value="">N/A</option>
        <option value="caicedonia">Caicedonia</option>
        <option value="sevilla">Sevilla</option>
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label">Título (opcional)</label>
        <input name="titulo" class="form-control" maxlength="140" placeholder="Resumen corto">
    </div>
    <div class="col-md-6">
        <label class="form-label">Correo del estudiante (opcional)</label>
        <input type="email" name="estudiante_email" class="form-control" placeholder="para asociar el caso a un estudiante" />
    </div>
    <div class="col-12">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" rows="6" maxlength="5000" required placeholder="Describe el incidente, contexto, etc."></textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary">Enviar</button>
        <a class="btn btn-link" href="{{ route('app.restaurante.reportes.index') }}">Volver</a>
    </div>
    </div>
</form>
</div>
@endsection