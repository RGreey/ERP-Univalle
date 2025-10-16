@extends('layouts.app')
@section('title','Cancelaciones')

@section('content')
<div class="container">
<h3 class="mb-3">Cancelaciones</h3>

<form class="row g-2 mb-3" method="GET" action="{{ route('admin.asistencias.cancelaciones') }}">
    <div class="col-auto">
        <label class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control"
               value="{{ isset($fecha) ? $fecha->toDateString() : '' }}">
    </div>
    <div class="col-auto">
        <label class="form-label">Sede</label>
        <select name="sede" class="form-select">
            <option value="">Todas</option>
            <option value="caicedonia" @selected(($sede ?? '')==='caicedonia')>Caicedonia</option>
            <option value="sevilla" @selected(($sede ?? '')==='sevilla')>Sevilla</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label">Convocatoria</label>
        <select name="convocatoria_id" class="form-select">
            <option value="">Todas</option>
            @foreach($convocatorias ?? [] as $c)
                <option value="{{ $c->id }}" @selected(($convId ?? null)==$c->id)>{{ $c->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto align-self-end">
        <button class="btn btn-primary">Filtrar</button>
    </div>
</form>

@if($cancelaciones->isEmpty())
    <div class="alert alert-warning">No hay cancelaciones para los filtros seleccionados.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">Fecha</th>
                    <th class="text-nowrap">Sede</th>
                    <th class="text-nowrap">Estudiante</th>
                    <th class="text-nowrap">Correo</th>
                    <th class="text-nowrap">Motivo</th>
                    <th class="text-nowrap">Última actualización</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cancelaciones as $a)
                    <tr>
                        <td>{{ $a->cupo?->fecha }}</td>
                        <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
                        <td>{{ $a->user?->name }}</td>
                        <td class="text-muted">{{ $a->user?->email }}</td>
                        <td style="max-width:320px; white-space:pre-line;" class="text-break">
                            {{ $a->cancelacion_motivo ?? $a->cupo?->cancelacion_motivo ?? '(Sin motivo)' }}
                        </td>
                        <td class="text-muted">{{ $a->updated_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $cancelaciones->links() }}
@endif
</div>
@endsection