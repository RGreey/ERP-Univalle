@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title','Standby (reemplazos)')

@section('content')
<div class="container">
  <h3 class="mb-3">Lista de standby — {{ $convocatoria->nombre }}</h3>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

  <form method="POST" action="{{ route('app.subsidio.standby.save') }}" class="card p-3 shadow-sm">
    @csrf
    <input type="hidden" name="convocatoria_id" value="{{ $convocatoria->id }}">
    <div class="table-responsive mb-3">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Día</th>
            <th>Preferencia</th>
          </tr>
        </thead>
        <tbody>
          @php
            $dias = [
              'pref_lun' => 'Lunes',
              'pref_mar' => 'Martes',
              'pref_mie' => 'Miércoles',
              'pref_jue' => 'Jueves',
              'pref_vie' => 'Viernes',
            ];
            $val = fn($k)=> old($k, $reg->{$k} ?? 'ninguno');
          @endphp
          @foreach($dias as $campo => $label)
          <tr>
            <td>{{ $label }}</td>
            <td>
              <div class="d-flex gap-3">
                @foreach($enum as $op)
                  <div class="form-check">
                    <input class="form-check-input" type="radio" id="{{ $campo.'_'.$op }}" name="{{ $campo }}" value="{{ $op }}" @checked($val($campo)===$op)>
                    <label class="form-check-label" for="{{ $campo.'_'.$op }}">{{ ucfirst($op) }}</label>
                  </div>
                @endforeach
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" value="1" id="activo" name="activo" @checked(old('activo', $reg->activo ?? true))>
      <label class="form-check-label" for="activo">Quiero recibir ofertas de reemplazo cuando haya cupos</label>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary">Guardar</button>
      <a class="btn btn-outline-secondary" href="{{ route('app.subsidio.mis-cupos') }}">Volver</a>
    </div>
  </form>
</div>
@endsection