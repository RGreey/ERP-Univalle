@extends('layouts.app')
@section('title','Dashboard Restaurantes')

@section('content')
<div class="container">
  <h3 class="mb-3">Dashboard Restaurantes</h3>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="card mb-4">
    <div class="card-header"><strong>Contexto de trabajo</strong></div>
    <div class="card-body">
      @if(empty($sedes))
        <div class="alert alert-warning mb-0">No tienes sedes asignadas. Pide a AdminBienestar que te agregue.</div>
      @else
        <form class="row g-3" method="POST" action="{{ route('restaurantes.context.set') }}">
          @csrf
          <div class="col-md-4">
            <label class="form-label">Sede</label>
            <select name="sede" class="form-select">
              <option value="">Todas mis sedes</option>
              @foreach($sedes as $s)
                <option value="{{ $s }}" @selected(($ctx['sede']??null)===$s)>{{ ucfirst($s) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Convocatoria</label>
            <select name="convocatoria_id" class="form-select">
              <option value="">Todas</option>
              @foreach($convocatorias as $c)
                <option value="{{ $c->id }}" @selected(($ctx['convocatoria']??null)==$c->id)>{{ $c->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 align-self-end">
            <button class="btn btn-primary w-100">Guardar</button>
          </div>
        </form>
      @endif
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2">
    <a href="{{ route('restaurantes.asistencias.hoy') }}" class="btn btn-primary">Asistencias de hoy</a>
    <a href="{{ route('restaurantes.asistencias.fecha') }}" class="btn btn-outline-secondary">Asistencias por fecha</a>
    <a href="{{ route('restaurantes.asistencias.semana') }}" class="btn btn-outline-primary">Resumen semanal</a>
  </div>
</div>
@endsection