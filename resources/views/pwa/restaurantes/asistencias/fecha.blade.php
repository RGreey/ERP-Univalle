@extends('layouts.app')
@section('title','Asistencias por fecha')

@section('content')
<div class="container">
  @include('pwa.restaurantes.partials.back')
  @include('pwa.restaurantes.partials.context')

  <h3 class="mb-3">Asistencias por fecha</h3>

  <form class="row g-2 mb-3" method="GET" action="{{ route('restaurantes.asistencias.fecha') }}">
    <div class="col-12 col-sm-auto">
      <label class="form-label">Fecha</label>
      <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" class="form-control">
    </div>
    <div class="col-12 col-sm-auto align-self-end">
      <button class="btn btn-primary w-100 w-sm-auto">Filtrar</button>
    </div>
    <div class="col-12 col-sm-auto align-self-end">
      <a href="{{ route('restaurantes.asistencias.semana',['lunes'=>$fecha->copy()->startOfWeek()->toDateString()]) }}"
         class="btn btn-outline-secondary w-100 w-sm-auto">Ver semana</a>
    </div>
  </form>

  @if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

  @if($items->isEmpty())
    <div class="alert alert-warning">Sin registros.</div>
  @else
    {{-- Escritorio: tabla --}}
    <div class="d-none d-sm-block">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Sede</th>
              <th>Estudiante</th>
              <th>Correo</th>
              <th class="text-end">Estado</th>
              @if($editable)<th class="text-end">Acciones</th>@endif
            </tr>
          </thead>
          <tbody>
            @foreach($items as $a)
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
                @if($editable)
                <td class="text-end">
                  @if($estado!=='cancelado')
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar-fecha',$a) }}" class="d-inline">
                      @csrf <input type="hidden" name="accion" value="asistio">
                      <button class="btn btn-sm btn-success">Asistió</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar-fecha',$a) }}" class="d-inline">
                      @csrf <input type="hidden" name="accion" value="pendiente">
                      <button class="btn btn-sm btn-outline-secondary">Pendiente</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar-fecha',$a) }}" class="d-inline">
                      @csrf <input type="hidden" name="accion" value="inasistencia">
                      <button class="btn btn-sm btn-warning">Inasistencia</button>
                    </form>
                  @else
                    <span class="text-muted small">No editable</span>
                  @endif
                </td>
                @endif
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{-- Móvil: lista compacta con acciones --}}
    <div class="d-sm-none">
      <div class="list-group list-group-flush">
        @foreach($items as $a)
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
                <div><span class="badge bg-{{ $badge }}">{{ $estado }}</span></div>
                @if($editable && $estado!=='cancelado')
                  <div class="mt-2">
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar-fecha',$a) }}" class="d-inline">
                      @csrf <input type="hidden" name="accion" value="asistio">
                      <button class="btn btn-success btn-sm px-2 py-1">✓</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar-fecha',$a) }}" class="d-inline">
                      @csrf <input type="hidden" name="accion" value="pendiente">
                      <button class="btn btn-outline-secondary btn-sm px-2 py-1">·</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar-fecha',$a) }}" class="d-inline">
                      @csrf <input type="hidden" name="accion" value="inasistencia">
                      <button class="btn btn-warning btn-sm px-2 py-1">!</button>
                    </form>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

  @endif
</div>
@endsection