@extends('layouts.app')
@section('title','Resumen semanal')

@section('content')
<div class="container">
  @include('pwa.restaurantes.partials.back')
  @include('pwa.restaurantes.partials.context')

  <h3 class="mb-3">Resumen semanal</h3>

  <form class="row g-2 mb-3" method="GET" action="{{ route('restaurantes.asistencias.semana') }}">
    <div class="col-12 col-sm-auto">
      <label class="form-label">Semana (lunes)</label>
      <input type="date" name="lunes" value="{{ $lunes->toDateString() }}" class="form-control">
    </div>
    <div class="col-12 col-sm-auto align-self-end">
      <button class="btn btn-primary w-100 w-sm-auto">Cargar</button>
    </div>
    <div class="col-12 col-sm-auto align-self-end">
      <a href="{{ route('restaurantes.asistencias.semana.export',['lunes'=>$lunes->toDateString()]) }}"
         class="btn btn-outline-success w-100 w-sm-auto">Exportar Excel</a>
    </div>
  </form>

  @if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

  @if(empty($itemsAgrupados))
    <div class="alert alert-warning">Sin registros.</div>
  @else
    <div class="mb-3">
      <strong>Resumen:</strong>
      @foreach($resumen as $k=>$v)
        @php $color = match($k){ 'cancelado'=>'danger', 'asistio'=>'success', 'inasistencia'=>'warning', default=>'secondary' }; @endphp
        <span class="badge bg-{{ $color }} me-1">{{ $k }}: {{ $v }}</span>
      @endforeach
    </div>

    @foreach($itemsAgrupados as $fecha => $grupo)
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>{{ $fecha }}</strong>
          <a href="{{ route('restaurantes.asistencias.fecha',['fecha'=>$fecha]) }}" class="btn btn-sm btn-outline-primary">Ver día</a>
        </div>

        <div class="card-body p-0">
          {{-- Escritorio / tablet: tabla clásica --}}
          <div class="d-none d-sm-block">
            <div class="table-responsive">
              <table class="table table-sm mb-0 align-middle">
                <thead>
                  <tr>
                    <th>Sede</th>
                    <th>Estudiante</th>
                    <th>Correo</th>
                    <th class="text-end">Estado</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($grupo as $a)
                    @php
                      $estado = $a->asistencia_estado ?? 'pendiente';
                      if ($estado==='no_show') $estado='inasistencia';
                      $badge = match($estado){ 'cancelado'=>'danger','asistio'=>'success','inasistencia'=>'warning', default=>'secondary' };
                    @endphp
                    <tr>
                      <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
                      <td>{{ $a->user?->name }}</td>
                      <td class="text-muted small">{{ $a->user?->email }}</td>
                      <td class="text-end"><span class="badge bg-{{ $badge }}">{{ $estado }}</span></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          {{-- Móvil: lista compacta sin scroll horizontal --}}
          <div class="d-sm-none">
            <div class="list-group list-group-flush">
              @foreach($grupo as $a)
                @php
                  $estado = $a->asistencia_estado ?? 'pendiente';
                  if ($estado==='no_show') $estado='inasistencia';
                  $badge = match($estado){ 'cancelado'=>'danger','asistio'=>'success','inasistencia'=>'warning', default=>'secondary' };
                @endphp
                <div class="list-group-item py-2">
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1">
                      <div class="text-muted small">{{ ucfirst($a->cupo?->sede ?? '') }}</div>
                      <div class="fw-semibold">{{ $a->user?->name }}</div>
                      <div class="text-muted small">{{ $a->user?->email }}</div>
                    </div>
                    <div class="text-end">
                      <span class="badge bg-{{ $badge }}">{{ $estado }}</span>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

        </div>
      </div>
    @endforeach
  @endif
</div>
@endsection