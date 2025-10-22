@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title','Reportes (Restaurante)')

@push('head')
<meta name="theme-color" content="#cd1f32">
@endpush

@section('content')
<div class="container">
<h3 class="mb-3">Mis reportes (Restaurante)</h3>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<a class="btn btn-primary btn-sm mb-3" href="{{ route('app.restaurante.reportes.create') }}">Nuevo reporte</a>

<div class="card">
    <div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Título</th>
            <th>Estado</th>
            <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $r)
            @php $badge = match($r->estado){ 'pendiente'=>'secondary','en_proceso'=>'info','resuelto'=>'success','archivado'=>'dark', default=>'secondary' }; @endphp
            <tr>
                <td>{{ $r->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ ucfirst($r->tipo) }}</td>
                <td>{{ $r->titulo ?? '—' }}</td>
                <td><span class="badge bg-{{ $badge }}">{{ $r->estado }}</span></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('app.restaurante.reportes.show',$r) }}">Ver</a></td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted p-3">Sin reportes aún.</td></tr>
            @endforelse
        </tbody>
        </table>
    </div>
    </div>
</div>
</div>
@endsection