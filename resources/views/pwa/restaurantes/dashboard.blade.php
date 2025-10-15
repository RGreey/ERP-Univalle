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
          <div class="col-12">
            <div class="row g-2">
              <div class="col-12 col-sm-6">
                <label class="form-label">Sede</label>
                <select name="sede" class="form-select">
                  <option value="">Todas mis sedes</option>
                  @foreach($sedes as $s)
                    <option value="{{ $s }}" @selected(($ctx['sede']??null)===$s)>{{ ucfirst($s) }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12 col-sm-6">
                <label class="form-label">Convocatoria</label>
                <select name="convocatoria_id" class="form-select">
                  <option value="">Todas</option>
                  @foreach($convocatorias as $c)
                    <option value="{{ $c->id }}" @selected(($ctx['convocatoria']??null)==$c->id)>{{ $c->nombre }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4 col-md-3">
            <button class="btn btn-primary w-100 btn-lg">Guardar</button>
          </div>
        </form>
      @endif
    </div>
  </div>

  {{-- Accesos rápidos (móvil): botones grandes en grid --}}
  <div class="row g-2">
    <div class="col-6">
      <a href="{{ route('restaurantes.asistencias.hoy') }}" class="btn btn-primary btn-lg w-100">Hoy</a>
    </div>
    <div class="col-6">
      <a href="{{ route('restaurantes.asistencias.fecha') }}" class="btn btn-outline-secondary btn-lg w-100">Por fecha</a>
    </div>
    <div class="col-6">
      <a href="{{ route('restaurantes.asistencias.semana') }}" class="btn btn-outline-primary btn-lg w-100">Semana</a>
    </div>
    <div class="col-6">
      <a href="{{ route('restaurantes.asistencias.mes') }}" class="btn btn-outline-success btn-lg w-100">Mes</a>
    </div>
  </div>
</div>
@endsection