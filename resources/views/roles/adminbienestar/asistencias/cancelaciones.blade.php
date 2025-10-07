@extends('layouts.app')
@section('title','Cancelaciones')

@section('content')
<div class="container">
<h3 class="mb-3">Cancelaciones</h3>

<form class="row g-2 mb-3" method="GET" action="{{ route('admin.asistencias.cancelaciones') }}">
    <div class="col-auto">
    <label class="form-label">Desde</label>
    <input type="date" name="desde" class="form-control" value="{{ $desde->toDateString() }}">
    </div>
    <div class="col-auto">
    <label class="form-label">Hasta</label>
    <input type="date" name="hasta" class="form-control" value="{{ $hasta->toDateString() }}">
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
        <option value="{{ $c->id }}" @selected(($convocatoriaId ?? null)==$c->id)>{{ $c->nombre }}</option>
        @endforeach
    </select>
    </div>
    <div class="col-auto align-self-end">
    <button class="btn btn-primary">Filtrar</button>
    </div>
</form>

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
            <th>Motivo</th>
            <th>Cancelada en</th>
            <th>Origen</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $a)
            <tr>
            <td>{{ optional($a->cupo?->fecha)?->toDateString() }}</td>
            <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
            <td>{{ $a->user?->name }}</td>
            <td>{{ $a->user?->email }}</td>
            <td style="max-width: 340px">{{ $a->cancelacion_motivo ?? '—' }}</td>
            <td>{{ optional($a->cancelada_en)?->format('Y-m-d H:i') ?? '—' }}</td>
            <td>{{ $a->cancelacion_origen ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted p-3">Sin cancelaciones en el rango.</td></tr>
        @endforelse
        </tbody>
        </table>
    </div>
    </div>
</div>
</div>
@endsection