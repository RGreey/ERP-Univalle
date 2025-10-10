@extends('layouts.app')
@section('title','Reporte Asistencias Restaurantes')

@section('content')
<div class="container">
<h3 class="mb-3">Reporte de Asistencias (Restaurantes)</h3>

<form class="row g-2 mb-3">
    <div class="col-auto">
    <label class="form-label">Desde</label>
    <input type="date" name="desde" value="{{ $desde->toDateString() }}" class="form-control">
    </div>
    <div class="col-auto">
    <label class="form-label">Hasta</label>
    <input type="date" name="hasta" value="{{ $hasta->toDateString() }}" class="form-control">
    </div>
    <div class="col-auto align-self-end">
    <button class="btn btn-primary">Generar</button>
    </div>
</form>

@if($data->isEmpty())
    <div class="alert alert-info">Sin registros en el rango.</div>
@else
    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
        <tr>
            <th>Sede</th>
            <th>Total</th>
            <th>Pendiente</th>
            <th>Asistió</th>
            <th>Cancelado</th>
            <th>No show</th>
            <th>Inasistencia (legacy)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $sede=>$row)
            <tr>
            <td>{{ ucfirst($sede) }}</td>
            <td>{{ $row['total'] }}</td>
            <td>{{ $row['pendiente'] }}</td>
            <td>{{ $row['asistio'] }}</td>
            <td>{{ $row['cancelado'] }}</td>
            <td>{{ $row['no_show'] }}</td>
            <td>{{ $row['inasistencia'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
@endif
</div>
@endsection