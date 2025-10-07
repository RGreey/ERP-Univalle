@extends('layouts.app')
@section('title','Mis cupos (App)')

@push('head')
<link rel="manifest" href="/subsidio/manifest.webmanifest">
<meta name="theme-color" content="#cd1f32">
@endpush

@section('content')
<div class="container">
    <h3 class="mb-3">Mis cupos</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('app.subsidio.mis-cupos', ['semana' => $lunes->copy()->subWeek()->toDateString()]) }}">&laquo; Semana anterior</a>
        <div class="text-muted">Semana {{ $lunes->format('Y-m-d') }} al {{ $lunes->copy()->addDays(6)->format('Y-m-d') }}</div>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('app.subsidio.mis-cupos', ['semana' => $lunes->copy()->addWeek()->toDateString()]) }}">Siguiente semana &raquo;</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Sede</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($asignaciones as $a)
                        @php
                            $fecha = $a->cupo->fecha;
                            $estado = $a->asistencia_estado ?? 'pendiente';
                            $nombreDia = ucfirst($fecha->locale('es')->isoFormat('dddd'));
                        @endphp
                        <tr>
                            <td>{{ $fecha->toDateString() }} ({{ $nombreDia }})</td>
                            <td>{{ ucfirst($a->cupo->sede) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $estado === 'cancelado' ? 'danger' : ($estado === 'asistio' ? 'success' : ($estado === 'no_show' ? 'warning' : 'secondary')) }}">
                                    {{ $estado }}
                                </span>
                            </td>
                            <td class="text-end">
                                {{-- Cancelar --}}
                                @if(!empty($a->can_cancel) && $a->can_cancel)
                                    <form method="POST" action="{{ route('app.subsidio.cancelar') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="asignacion_id" value="{{ $a->id }}">
                                        <input type="text" name="motivo" class="form-control form-control-sm d-inline-block" style="max-width:220px" placeholder="Motivo (opcional)">
                                        <button class="btn btn-sm btn-outline-danger" title="Hora límite: {{ $a->lim_cancel_hhmm }}">Cancelar</button>
                                    </form>
                                @elseif($estado === 'pendiente')
                                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top"
                                          title="No puedes cancelar: {{ $a->cancel_reason }} Hora límite: {{ $a->lim_cancel_hhmm }}">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled>Cancelar</button>
                                    </span>
                                @endif

                                {{-- Deshacer --}}
                                @if(!empty($a->can_undo) && $a->can_undo)
                                    <form method="POST" action="{{ route('app.subsidio.deshacer') }}" class="d-inline ms-2">
                                        @csrf
                                        <input type="hidden" name="asignacion_id" value="{{ $a->id }}">
                                        <input type="text" name="motivo" class="form-control form-control-sm d-inline-block" style="max-width:220px" placeholder="Motivo (requerido)" required>
                                        <button class="btn btn-sm btn-outline-primary" title="Hora límite: {{ $a->lim_undo_hhmm }}">Deshacer</button>
                                    </form>
                                @elseif($estado === 'cancelado')
                                    <span class="d-inline-block ms-2" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top"
                                          title="No puedes deshacer: {{ $a->undo_reason }} Hora límite: {{ $a->lim_undo_hhmm }}">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled>Deshacer</button>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted p-4">No tienes cupos esta semana.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Inicializa tooltips de Bootstrap 5
document.addEventListener('DOMContentLoaded', function () {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl)
  })
});

// Registrar SW aislado (opcional)
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/subsidio/sw.js', { scope: '/app/subsidio/' }).catch(()=>{});
  });
}
</script>
@endpush