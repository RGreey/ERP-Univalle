@extends('layouts.app')
@section('title','Resumen semanal')

@section('content')
<div class="container">
  @include('pwa.restaurantes.partials.back')
  @include('pwa.restaurantes.partials.context')

  <h3 class="mb-3">Resumen semanal</h3>

  {{-- Filtros (GET) --}}
  <form class="row g-2 mb-2" method="GET" action="{{ route('restaurantes.asistencias.semana') }}">
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

  <div class="mb-3">
    <form method="POST" action="{{ route('restaurantes.asistencias.cerrar-semana') }}"
          onsubmit="return confirm('¿Cerrar semana? Pendientes pasarán a inasistencia (no afecta festivos).');"
          class="d-inline">
      @csrf
      <input type="hidden" name="lunes" value="{{ $lunes->toDateString() }}">
      <button class="btn btn-outline-danger w-100 w-sm-auto">Cerrar semana</button>
    </form>
  </div>

  @if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

  @if(empty($itemsAgrupados))
    <div class="alert alert-warning">Sin registros.</div>
  @else
    <div class="mb-3">
      <strong>Resumen:</strong>
      @foreach($resumen as $k=>$v)
        @php $color = match($k){ 'cancelado'=>'danger', 'asistio'=>'success', 'inasistencia'=>'warning', 'festivo'=>'info', default=>'secondary' }; @endphp
        <span class="badge bg-{{ $color }} me-1 mb-2">{{ $k }}: {{ $v }}</span>
      @endforeach
    </div>

    @foreach($itemsAgrupados as $fecha => $grupo)
      @php $esFestivo = ($festivos[$fecha] ?? false) ? true : false; @endphp

      <div class="card mb-3">
        <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
          <div class="d-flex align-items-center gap-2 mb-2 mb-sm-0">
            <strong>{{ $fecha }}</strong>
            @if($esFestivo)
              <span class="badge bg-info">festivo</span>
            @endif
          </div>

          <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
            <form method="POST" action="{{ route('restaurantes.asistencias.festivo') }}" class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
              @csrf
              <input type="hidden" name="fecha" value="{{ $fecha }}">
              @if(!$esFestivo)
                <input type="hidden" name="accion" value="marcar">
                <input type="text" name="motivo" class="form-control form-control-sm mb-2 mb-sm-0" style="max-width:180px" placeholder="Motivo (opcional)">
                <button class="btn btn-sm btn-outline-primary">Marcar festivo</button>
              @else
                <input type="hidden" name="accion" value="quitar">
                <button class="btn btn-sm btn-outline-secondary">Quitar festivo</button>
              @endif
            </form>

            <form method="POST" action="{{ route('restaurantes.asistencias.cerrar-dia') }}"
                  onsubmit="return confirm('¿Cerrar este día? Pendientes → inasistencia (no afecta festivos).');">
              @csrf
              <input type="hidden" name="fecha" value="{{ $fecha }}">
              <button class="btn btn-sm btn-outline-danger" @if($esFestivo) disabled @endif>Cerrar día</button>
            </form>

            <a href="{{ route('restaurantes.asistencias.fecha',['fecha'=>$fecha]) }}" class="btn btn-sm btn-outline-primary">Ver día</a>
          </div>
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
                      $badge = match($estado){ 'cancelado'=>'danger','asistio'=>'success','inasistencia'=>'warning','festivo'=>'info', default=>'secondary' };
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

          {{-- Móvil: vista compacta y apilada --}}
          <div class="d-sm-none">
            <div class="list-group list-group-flush">
              @foreach($grupo as $a)
                @php
                  $estado = $a->asistencia_estado ?? 'pendiente';
                  if ($estado==='no_show') $estado='inasistencia';
                  $badge = match($estado){ 'cancelado'=>'danger','asistio'=>'success','inasistencia'=>'warning','festivo'=>'info', default=>'secondary' };
                @endphp
                <div class="list-group-item py-2 mb-2">
                  <div class="fw-bold mb-1">{{ ucfirst($a->cupo?->sede ?? '') }}</div>
                  <div class="fw-semibold">{{ $a->user?->name }}</div>
                  <div class="text-muted small mb-1">{{ $a->user?->email }}</div>
                  <span class="badge bg-{{ $badge }}">{{ $estado }}</span>
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