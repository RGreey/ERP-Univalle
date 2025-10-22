@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title','Detalle de postulación')

@section('content')
<style>
    .uv-card { background:#fff; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,.06); padding:18px; }
    .uv-sub { color:#6c757d; }
    .uv-badge { font-size:.85rem; }
    .table-clean thead th { background:#f8f9fa; }
    .checkcell { font-size: 1rem; color:#198754; }
    .muted { color:#adb5bd; }
</style>

@php
    $mat = $postulacion->respuestas->first(fn($r)=>optional($r->pregunta)->tipo==='matrix_single');
    $filas = $mat?->pregunta?->filas ?? collect();
    $cols  = $mat?->pregunta?->columnas ?? collect();
    $map   = $mat?->respuesta_json ?? [];
@endphp

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h3 class="mb-1">{{ $postulacion->convocatoria->nombre }}</h3>
            <div class="uv-sub">
                Sede: <strong>{{ $postulacion->sede }}</strong>
                · Estado:
                <span class="badge bg-secondary uv-badge">{{ ucfirst($postulacion->estado) }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($postulacion->documento_pdf)
                <a href="{{ route('subsidio.postulaciones.pdf', $postulacion->id) }}" class="btn btn-outline-dark btn-sm">Descargar PDF</a>
            @endif
            <a href="{{ route('subsidio.postulaciones.index') }}" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="uv-card h-100">
                <h6 class="fw-semibold mb-2">Datos</h6>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="text-muted" style="width:35%">Programa</th><td>{{ $postulacion->programa ?? '—' }}</td></tr>
                        <tr><th class="text-muted">Correo</th><td>{{ $postulacion->correo ?? '—' }}</td></tr>
                        <tr><th class="text-muted">Teléfono</th><td>{{ $postulacion->telefono ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="uv-card h-100">
                <h6 class="fw-semibold mb-2">Preferencias de días</h6>
                @if($filas->isEmpty() || empty($map))
                    <div class="text-muted">No registrado.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-clean align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-start" style="width:180px">Día</th>
                                    @foreach($cols as $c)
                                        <th class="text-center">{{ $c->etiqueta }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filas as $f)
                                    @php $sel = $map[$f->id] ?? null; @endphp
                                    <tr>
                                        <td class="text-start">{{ $f->etiqueta }}</td>
                                        @foreach($cols as $c)
                                            <td class="text-center">
                                                @if($sel === $c->valor)
                                                    <span class="checkcell">✓</span>
                                                @else
                                                    <span class="muted">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection