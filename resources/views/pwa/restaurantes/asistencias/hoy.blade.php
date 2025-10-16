@extends('layouts.app')
@section('title','Asistencias de hoy')

@section('content')
<div class="container">
  @include('pwa.restaurantes.partials.back')
  @include('pwa.restaurantes.partials.context')

  {{-- Toggle festivo fijo en “Hoy” --}}
  <div class="d-flex flex-wrap gap-2 justify-content-end mb-2">
    <form method="POST" action="{{ route('restaurantes.asistencias.festivo') }}" class="d-inline-flex gap-2">
      @csrf
      <input type="hidden" name="fecha" value="{{ $hoy }}">
      @if(empty($esFestivoDia))
        <input type="hidden" name="accion" value="marcar">
        <input type="text" name="motivo" class="form-control form-control-sm" style="width: 220px" placeholder="Motivo (opcional)">
        <button class="btn btn-sm btn-outline-primary">Marcar festivo</button>
      @else
        <input type="hidden" name="accion" value="quitar">
        <button class="btn btn-sm btn-outline-secondary">Quitar festivo</button>
      @endif
    </form>
  </div>

  <h3 class="mb-2">Asistencias de hoy</h3>
  <div class="small text-muted mb-3">
    Fecha: {{ $hoy }} | Corte: {{ $corte->format('H:i') }}
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
  @if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif
  @if(!empty($esFestivoDia))
    <div class="alert alert-primary py-2">Día festivo: no se prestó servicio.</div>
  @endif

  {{-- Pendientes --}}
  <div class="card mb-3">
    <div class="card-header"><strong>Pendientes ({{ $pendientes->count() }})</strong></div>
    <div class="card-body p-0">
      @if($pendientes->isEmpty())
        <p class="p-3 text-muted mb-0">Sin pendientes.</p>
      @else
        <div class="d-none d-sm-block">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead><tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th class="text-end">Acciones</th></tr></thead>
              <tbody>
                @foreach($pendientes as $a)
                <tr>
                  <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
                  <td>{{ $a->user?->name }}</td>
                  <td class="text-muted small">{{ $a->user?->email }}</td>
                  <td class="text-end">
                    @if(empty($esFestivoDia))
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="asistio">
                        <button class="btn btn-sm btn-success">Asistió</button>
                      </form>
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="inasistencia">
                        <button class="btn btn-sm btn-warning">Inasistencia</button>
                      </form>
                    @else
                      <span class="text-muted small">Día festivo</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-sm-none">
          <div class="list-group list-group-flush">
            @foreach($pendientes as $a)
              <div class="list-group-item py-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="flex-grow-1">
                    <div class="text-muted small">{{ ucfirst($a->cupo?->sede ?? '') }}</div>
                    <div class="fw-semibold">{{ $a->user?->name }}</div>
                    <div class="text-muted small">{{ $a->user?->email }}</div>
                  </div>
                  <div class="text-end">
                    @if(empty($esFestivoDia))
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="asistio">
                        <button class="btn btn-success btn-sm px-2 py-1">✓</button>
                      </form>
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="inasistencia">
                        <button class="btn btn-warning btn-sm px-2 py-1">!</button>
                      </form>
                    @else
                      <span class="badge bg-info">festivo</span>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>

  {{-- Asistió --}}
  <div class="card mb-3">
    <div class="card-header"><strong>Asistió ({{ $asistidas->count() }})</strong></div>
    <div class="card-body p-0">
      @if($asistidas->isEmpty())
        <p class="p-3 text-muted mb-0">Sin registros.</p>
      @else
        <div class="d-none d-sm-block">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead><tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th>Marcado en</th><th class="text-end">Acciones</th></tr></thead>
              <tbody>
                @foreach($asistidas as $a)
                <tr>
                  <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
                  <td>{{ $a->user?->name }}</td>
                  <td class="text-muted small">{{ $a->user?->email }}</td>
                  <td class="text-muted small">{{ optional($a->asistencia_marcada_en)->format('H:i') }}</td>
                  <td class="text-end">
                    @if(empty($esFestivoDia))
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="pendiente">
                        <button class="btn btn-sm btn-outline-secondary">Pendiente</button>
                      </form>
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="inasistencia">
                        <button class="btn btn-sm btn-warning">Inasistencia</button>
                      </form>
                    @else
                      <span class="text-muted small">Día festivo</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-sm-none">
          <div class="list-group list-group-flush">
            @foreach($asistidas as $a)
              <div class="list-group-item py-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="flex-grow-1">
                    <div class="text-muted small">{{ ucfirst($a->cupo?->sede ?? '') }}</div>
                    <div class="fw-semibold">{{ $a->user?->name }}</div>
                    <div class="text-muted small">{{ $a->user?->email }}</div>
                    <div class="text-muted small mt-1">Marcado en: {{ optional($a->asistencia_marcada_en)->format('H:i') }}</div>
                  </div>
                  <div class="text-end">
                    @if(empty($esFestivoDia))
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="pendiente">
                        <button class="btn btn-outline-secondary btn-sm px-2 py-1">·</button>
                      </form>
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="inasistencia">
                        <button class="btn btn-warning btn-sm px-2 py-1">!</button>
                      </form>
                    @else
                      <span class="badge bg-info">festivo</span>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>

  {{-- Cancelaciones --}}
  <div class="card mb-3">
    <div class="card-header"><strong>Cancelaciones de hoy ({{ $canceladas->count() }})</strong></div>
    <div class="card-body p-0">
      @if($canceladas->isEmpty())
        <p class="p-3 text-muted mb-0">Sin registros.</p>
      @else
        <div class="d-none d-sm-block">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead><tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th class="text-end">Estado</th></tr></thead>
              <tbody>
                @foreach($canceladas as $a)
                <tr>
                  <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
                  <td>{{ $a->user?->name }}</td>
                  <td class="text-muted small">{{ $a->user?->email }}</td>
                  <td class="text-end"><span class="badge bg-danger">cancelado</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-sm-none">
          <div class="list-group list-group-flush">
            @foreach($canceladas as $a)
              <div class="list-group-item py-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="flex-grow-1">
                    <div class="text-muted small">{{ ucfirst($a->cupo?->sede ?? '') }}</div>
                    <div class="fw-semibold">{{ $a->user?->name }}</div>
                    <div class="text-muted small">{{ $a->user?->email }}</div>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-danger">cancelado</span>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>

  {{-- Inasistencias --}}
  <div class="card mb-4">
    <div class="card-header"><strong>Inasistencias ({{ $inasistencias->count() }})</strong></div>
    <div class="card-body p-0">
      @if($inasistencias->isEmpty())
        <p class="p-3 text-muted mb-0">Sin inasistencias.</p>
      @else
        <div class="d-none d-sm-block">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead><tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th class="text-end">Acciones</th></tr></thead>
              <tbody>
                @foreach($inasistencias as $a)
                <tr>
                  <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
                  <td>{{ $a->user?->name }}</td>
                  <td class="text-muted small">{{ $a->user?->email }}</td>
                  <td class="text-end">
                    @if(empty($esFestivoDia))
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="asistio">
                        <button class="btn btn-sm btn-success">Asistió</button>
                      </form>
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="pendiente">
                        <button class="btn btn-sm btn-outline-secondary">Pendiente</button>
                      </form>
                    @else
                      <span class="text-muted small">Día festivo</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-sm-none">
          <div class="list-group list-group-flush">
            @foreach($inasistencias as $a)
              <div class="list-group-item py-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="flex-grow-1">
                    <div class="text-muted small">{{ ucfirst($a->cupo?->sede ?? '') }}</div>
                    <div class="fw-semibold">{{ $a->user?->name }}</div>
                    <div class="text-muted small">{{ $a->user?->email }}</div>
                  </div>
                  <div class="text-end">
                    @if(empty($esFestivoDia))
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="asistio">
                        <button class="btn btn-success btn-sm px-2 py-1">✓</button>
                      </form>
                      <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                        @csrf <input type="hidden" name="accion" value="pendiente">
                        <button class="btn btn-outline-secondary btn-sm px-2 py-1">·</button>
                      </form>
                    @else
                      <span class="badge bg-info">festivo</span>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>

  <form method="POST" action="{{ route('restaurantes.asistencias.cerrar-dia') }}">
    @csrf
    <input type="hidden" name="fecha" value="{{ $hoy }}">
    <button class="btn btn-outline-danger">Cerrar día (pendientes → inasistencia)</button>
  </form>
</div>
@endsection