@extends('layouts.app')
@section('title','Agregar a Standby')

@section('content')
<div class="container">
  <h3 class="mb-3">Agregar usuario a Standby</h3>

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.standby.store') }}" class="card p-3 shadow-sm">
    @csrf

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Convocatoria</label>
        <select name="convocatoria_id" class="form-select" required>
          @foreach($convocatorias as $c)
            <option value="{{ $c->id }}" @selected(old('convocatoria_id')==$c->id)>{{ $c->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Correo del estudiante</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="estudiante@correo.com">
        <div class="form-text">Si no existe, se creará automáticamente como Estudiante.</div>
      </div>

      <div class="col-md-4">
        <label class="form-label">Nombre (opcional si el usuario no existe)</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Nombre completo">
      </div>

      <div class="col-md-4 d-flex align-items-center">
        <div class="form-check me-3">
          <input class="form-check-input" type="checkbox" name="es_externo" value="1" id="ext" {{ old('es_externo') ? 'checked' : '' }}>
          <label class="form-check-label" for="ext">Es externo</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="activo" value="1" id="act" {{ old('activo', 1) ? 'checked' : '' }}>
          <label class="form-check-label" for="act">Activo</label>
        </div>
      </div>

      @php
        // Estos arrays llegan desde el controlador; si no, definimos por defecto
        $sedes = $sedes ?? ['caicedonia','sevilla','ninguno'];
        $dias  = $dias  ?? ['lun'=>'Lunes','mar'=>'Martes','mie'=>'Miércoles','jue'=>'Jueves','vie'=>'Viernes'];
      @endphp

      @foreach($dias as $k=>$label)
        <div class="col-md-4">
          <label class="form-label">{{ $label }}</label>
          <select name="pref_{{ $k }}" class="form-select" required>
            @foreach($sedes as $s)
              <option value="{{ $s }}" @selected(old('pref_'.$k, 'ninguno') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
      @endforeach
    </div>

    <div class="mt-3">
      <button class="btn btn-primary">Guardar</button>
      <a href="{{ route('admin.standby.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>
  </form>
</div>
@endsection