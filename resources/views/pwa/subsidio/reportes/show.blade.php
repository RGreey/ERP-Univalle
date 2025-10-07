@extends('layouts.app')
@section('title','Detalle del reporte')

@push('head')
<link rel="manifest" href="/subsidio/manifest.webmanifest">
<meta name="theme-color" content="#cd1f32">
@endpush

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
        <div class="text-muted">Tipo: {{ ucfirst($reporte->tipo) }} · Sede: {{ $reporte->sede ? ucfirst($reporte->sede) : 'N/A' }}</div>
        </div>
        @php
        $badge = match($reporte->estado){ 'pendiente'=>'secondary','en_proceso'=>'info','resuelto'=>'success','archivado'=>'dark', default=>'secondary' };
        @endphp
        <span class="badge bg-{{ $badge }} align-self-start">{{ $reporte->estado }}</span>
    </div>
    <hr>
    <p class="mb-0" style="white-space: pre-wrap">{{ $reporte->descripcion }}</p>
    </div>
</div>

@if($reporte->admin_respuesta)
    <div class="card">
    <div class="card-body">
        <div class="small text-muted">Respuesta de Bienestar {{ $reporte->respondido_en?->format('Y-m-d H:i') }}</div>
        <p class="mb-0" style="white-space: pre-wrap">{{ $reporte->admin_respuesta }}</p>
    </div>
    </div>
@endif

<div class="mt-3">
    <a class="btn btn-link" href="{{ route('app.subsidio.reportes.index') }}">Volver</a>
</div>
</div>
@endsection

@push('scripts')
<script>
if ('serviceWorker' in navigator) {
window.addEventListener('load', () => {
    navigator.serviceWorker.register('/subsidio/sw.js', { scope: '/app/subsidio/' }).catch(()=>{});
});
}
</script>
@endpush