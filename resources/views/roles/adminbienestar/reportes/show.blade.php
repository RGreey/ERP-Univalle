@extends('layouts.app')
@section('title','Reporte')

@section('content')
<div class="container">
<h3 class="mb-3">Reporte</h3>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card mb-3">
    <div class="card-body">
    <div class="d-flex justify-content-between">
        <div>
        <div class="small text-muted">{{ $reporte->created_at->format('Y-m-d H:i') }}</div>
        <h5 class="mb-0">{{ $reporte->titulo ?? 'Sin título' }}</h5>
        <div class="text-muted">
            Estudiante: {{ $reporte->user?->name }} · Tipo: {{ ucfirst($reporte->tipo) }} · Sede: {{ $reporte->sede ? ucfirst($reporte->sede) : 'N/A' }}
        </div>
        </div>
        @php $badge = match($reporte->estado){ 'pendiente'=>'secondary','en_proceso'=>'info','resuelto'=>'success','archivado'=>'dark', default=>'secondary' }; @endphp
        <span class="badge bg-{{ $badge }} align-self-start">{{ $reporte->estado }}</span>
    </div>
    <hr>
    <p class="mb-0" style="white-space: pre-wrap">{{ $reporte->descripcion }}</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
    <form method="POST" action="{{ route('admin.reportes.estado', $reporte) }}">
        @csrf
        <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select" required>
            @foreach(['pendiente','en_proceso','resuelto','archivado'] as $e)
                <option value="{{ $e }}" @selected($reporte->estado===$e)>{{ ucfirst($e) }}</option>
            @endforeach
            </select>
        </div>
        <div class="col-md-9">
            <label class="form-label">Respuesta (opcional)</label>
            <textarea name="admin_respuesta" class="form-control" rows="4" placeholder="Escribe una respuesta para el estudiante (opcional)">{{ old('admin_respuesta', $reporte->admin_respuesta) }}</textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Guardar</button>
            <a class="btn btn-link" href="{{ route('admin.reportes') }}">Volver</a>
        </div>
        </div>
    </form>
    </div>
</div>
</div>
@endsection