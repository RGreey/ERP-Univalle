@extends('layouts.app')
@section('title','Asistencias diarias')

@section('content')

<div class="container">
<x-admin.asistencias.volver keep="sede,convocatoria_id" />
<h3 class="mb-3">Asistencias diarias</h3>


<form class="row g-2 mb-3" method="GET" action="{{ route('admin.asistencias.diario') }}">
    <div class="col-auto">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" class="form-control" value="{{ $fecha->toDateString() }}">
    </div>
    <div class="col-auto">
    <label class="form-label">Sede</label>
    <select name="sede" class="form-select">
        <option value="">Todas</option>
        <option value="caicedonia" @selected($sede==='caicedonia')>Caicedonia</option>
        <option value="sevilla" @selected($sede==='sevilla')>Sevilla</option>
    </select>
    </div>
    <div class="col-auto">
    <label class="form-label">Convocatoria</label>
    <select name="convocatoria_id" class="form-select">
        <option value="">Todas</option>
        @foreach($convocatorias as $c)
        <option value="{{ $c->id }}" @selected($convocatoriaId==$c->id)>{{ $c->nombre }}</option>
        @endforeach
    </select>
    </div>
    <div class="col-auto align-self-end">
    <button class="btn btn-primary">Filtrar</button>
    </div>
</form>

<div class="mb-2 small text-muted">
    Totales — Pendiente: {{ $totales['pendiente'] ?? 0 }} ·
    <span class="text-danger">Cancelado: {{ $totales['cancelado'] ?? 0 }}</span> ·
    <span class="text-success">Asistió: {{ $totales['asistio'] ?? 0 }}</span> ·
    <span class="text-warning">Inasistencia: {{ $totales['inasistencia'] ?? 0 }}</span> ·
    <span class="text-info">Festivo: {{ $totales['festivo'] ?? 0 }}</span>
</div>

<div class="card">
    <div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
            <th>Fecha</th>
            <th>Sede</th>
            <th>Estudiante</th>
            <th>Correo</th>
            <th>Estado</th>
            <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $a)
            @php
            $raw = $a->asistencia_estado ?? 'pendiente';
            $estado = ($a->cupo?->es_festivo) ? 'festivo' : ($raw === 'no_show' ? 'inasistencia' : $raw);
            $badge = match($estado){ 'cancelado'=>'danger','asistio'=>'success','inasistencia'=>'warning','festivo'=>'info', default=>'secondary' };
            $title = null;
            if ($estado==='cancelado') {
                $title = 'Motivo: '.($a->cancelacion_motivo ?? '—').' | Cancelada: '.(optional($a->cancelada_en)?->format('Y-m-d H:i') ?? '—').' | Origen: '.($a->cancelacion_origen ?? '—');
            } elseif ($estado==='festivo') {
                $title = 'Día festivo: no hay servicio';
            }
            @endphp
            <tr>
            <td>{{ optional($a->cupo?->fecha)?->toDateString() }}</td>
            <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
            <td>{{ $a->user?->name }}</td>
            <td>{{ $a->user?->email }}</td>
            <td>
                <span class="badge bg-{{ $badge }}" @if($title) data-bs-toggle="tooltip" title="{{ $title }}" @endif>
                {{ $estado }}
                </span>
            </td>
            <td class="small text-muted">
                @if($estado==='cancelado')
                Motivo: {{ $a->cancelacion_motivo ?? '—' }}<br>
                Hora: {{ optional($a->cancelada_en)?->format('H:i') ?? '—' }} ({{ optional($a->cancelada_en)?->format('Y-m-d') ?? '' }})
                @elseif($estado==='inasistencia')
                Marcado por cierre de día.
                @elseif($estado==='festivo')
                Declarado festivo.
                @else
                —
                @endif
            </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted p-3">Sin registros.</td></tr>
        @endforelse
        </tbody>
        </table>
    </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});
</script>
@endpush