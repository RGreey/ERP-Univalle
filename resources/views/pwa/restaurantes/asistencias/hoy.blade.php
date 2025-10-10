@extends('layouts.app')
@section('title','Asistencias de hoy')

@section('content')
<div class="container">
  @include('pwa.restaurantes.partials.back')

  <h3 class="mb-2">Asistencias de hoy</h3>
  <div class="small text-muted mb-3">
    Fecha: {{ $hoy }} | Corte: {{ $corte->format('H:i') }}
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(isset($mensaje))<div class="alert alert-info">{{ $mensaje }}</div>@endif

  {{-- PENDIENTES --}}
  <div class="card mb-3">
    <div class="card-header"><strong>Pendientes ({{ $pendientes->count() }})</strong></div>
    <div class="card-body p-0">
      @if($pendientes->isEmpty())
        <p class="p-3 text-muted mb-0">Sin pendientes.</p>
      @else
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
              @foreach($pendientes as $a)
                <tr>
                  <td>{{ ucfirst($a->cupo?->sede) }}</td>
                  <td>{{ $a->user?->name }}</td>
                  <td class="text-muted small">{{ $a->user?->email }}</td>
                  <td class="text-end">
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="accion" value="asistio">
                      <button class="btn btn-sm btn-success">Asistió</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="accion" value="inasistencia">
                      <button class="btn btn-sm btn-warning">Inasistencia</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  {{-- ASISTIÓ --}}
  <div class="card mb-3">
    <div class="card-header"><strong>Asistió ({{ $asistidas->count() }})</strong></div>
    <div class="card-body p-0">
      @if($asistidas->isEmpty())
        <p class="p-3 text-muted mb-0">Sin registros.</p>
      @else
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th>Marcado en</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
              @foreach($asistidas as $a)
                <tr>
                  <td>{{ ucfirst($a->cupo?->sede) }}</td>
                  <td>{{ $a->user?->name }}</td>
                  <td class="text-muted small">{{ $a->user?->email }}</td>
                  <td class="small">{{ optional($a->asistencia_marcada_en)->format('H:i') }}</td>
                  <td class="text-end">
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="accion" value="pendiente">
                      <button class="btn btn-sm btn-outline-secondary">Pendiente</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="accion" value="inasistencia">
                      <button class="btn btn-sm btn-warning">Inasistencia</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  {{-- CANCELADAS --}}
  <div class="card mb-3">
    <div class="card-header"><strong>Cancelaciones de hoy ({{ $canceladas->count() }})</strong></div>
    <div class="card-body p-0">
      @if($canceladas->isEmpty())
        <p class="p-3 text-muted mb-0">Sin cancelaciones.</p>
      @else
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th>Estado</th></tr></thead>
            <tbody>
              @foreach($canceladas as $a)
                <tr>
                  <td>{{ ucfirst($a->cupo?->sede) }}</td>
                  <td>{{ $a->user?->name }}</td>
                  <td class="text-muted small">{{ $a->user?->email }}</td>
                  <td><span class="badge bg-danger">cancelado</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  {{-- INASISTENCIAS --}}
  <div class="card mb-4">
    <div class="card-header"><strong>Inasistencias ({{ $inasistencias->count() }})</strong></div>
    <div class="card-body p-0">
      @if($inasistencias->isEmpty())
        <p class="p-3 text-muted mb-0">Sin inasistencias.</p>
      @else
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Sede</th><th>Estudiante</th><th>Correo</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
              @foreach($inasistencias as $a)
                <tr>
                  <td>{{ ucfirst($a->cupo?->sede) }}</td>
                  <td>{{ $a->user?->name }}</td>
                  <td class="text-muted small">{{ $a->user?->email }}</td>
                  <td class="text-end">
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="accion" value="asistio">
                      <button class="btn btn-sm btn-success">Asistió</button>
                    </form>
                    <form method="POST" action="{{ route('restaurantes.asistencias.marcar',$a) }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="accion" value="pendiente">
                      <button class="btn btn-sm btn-outline-secondary">Pendiente</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  <form method="POST" action="{{ route('restaurantes.asistencias.cerrar-dia') }}">
    @csrf
    <input type="hidden" name="fecha" value="{{ $hoy }}">
    <button class="btn btn-outline-danger">
      Cerrar día (pendientes → inasistencia)
    </button>
  </form>
</div>
@endsection