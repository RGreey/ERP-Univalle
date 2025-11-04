@extends('layouts.app')
@section('title','Editar Standby')
@section('content')
<div class="container">
  <h3 class="mb-3">Editar Standby</h3>
  
  <div class="mb-2">
    <strong>{{ $registro->user?->name }}</strong>
    <span class="text-muted small">({{ $registro->user?->codigo }}) — {{ $registro->user?->email }}</span>
  </div>
  <form method="POST" action="{{ route('admin.standby.update',$registro) }}" class="card p-3">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="activo" value="1" id="act" @checked($registro->activo)>
          <label class="form-check-label" for="act">Activo</label>
        </div>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="es_externo" value="1" id="ext" @checked($registro->es_externo)>
          <label class="form-check-label" for="ext">Es externo</label>
        </div>
      </div>
      @php $dias=['lun'=>'Lunes','mar'=>'Martes','mie'=>'Miércoles','jue'=>'Jueves','vie'=>'Viernes']; $sedes=['caicedonia','sevilla','ninguno']; @endphp
      @foreach($dias as $k=>$label)
      <div class="col-md-3">
        <label class="form-label">{{ $label }}</label>
        <select name="pref_{{ $k }}" class="form-select" required>
          @foreach($sedes as $s)
            <option value="{{ $s }}" @selected($registro->{'pref_'.$k}===$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      @endforeach
    </div>
    <div class="mt-3">
      <button class="btn btn-primary">Guardar</button>
      <a href="{{ route('admin.standby.index',['convocatoria_id'=>$registro->convocatoria_id]) }}" class="btn btn-outline-secondary">Volver</a>
    </div>
  </form>
</div>
@endsection