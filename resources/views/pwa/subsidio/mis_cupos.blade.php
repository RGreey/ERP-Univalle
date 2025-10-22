@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
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
    <div class="d-flex gap-2 mb-3">
        <a class="btn btn-outline-primary btn-sm" href="{{ route('app.subsidio.standby') }}">Configurar standby</a>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('app.subsidio.mis-cupos', ['semana' => $lunes->copy()->subWeek()->toDateString()]) }}">&laquo; Semana anterior</a>
        <span class="text-muted">Semana que inicia: {{ $lunes->toDateString() }}</span>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('app.subsidio.mis-cupos', ['semana' => $lunes->copy()->addWeek()->toDateString()]) }}">Semana siguiente &raquo;</a>
    </div>

    <div class="card">
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
                            $fecha = \Carbon\Carbon::parse($a->cupo->fecha);
                            $estadoRaw = $a->asistencia_estado ?? 'pendiente';
                            // Normalizar: si es festivo, el estado visible es "festivo"
                            $estadoUi = ($a->cupo?->es_festivo) ? 'festivo' : ($estadoRaw === 'no_show' ? 'inasistencia' : $estadoRaw);
                            $nombreDia = ucfirst($fecha->locale('es')->isoFormat('dddd'));
                            $badgeColor = match($estadoUi){
                                'cancelado'   => 'danger',
                                'asistio'     => 'success',
                                'inasistencia'=> 'warning',
                                'festivo'     => 'info',
                                default       => 'secondary'
                            };
                        @endphp
                        <tr>
                            <td>{{ $fecha->toDateString() }} ({{ $nombreDia }})</td>
                            <td>{{ ucfirst($a->cupo->sede) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $badgeColor }}">{{ $estadoUi }}</span>
                            </td>
                            <td class="text-end">
                                {{-- Si es festivo no hay acciones --}}
                                @if($a->cupo?->es_festivo)
                                    <span class="text-muted small">Día festivo: no aplica.</span>
                                @else
                                    {{-- Cancelar --}}
                                    @if(!empty($a->can_cancel) && $a->can_cancel)
                                        <form method="POST" action="{{ route('app.subsidio.cancelar') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="asignacion_id" value="{{ $a->id }}">
                                            <input type="text" name="motivo" class="form-control form-control-sm d-inline-block" style="max-width:220px" placeholder="Motivo (opcional)">
                                            <button class="btn btn-sm btn-outline-danger" title="Hora límite: {{ $a->lim_cancel_hhmm }}">Cancelar</button>
                                        </form>
                                    @elseif($estadoUi === 'pendiente')
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
                                    @elseif($estadoUi === 'cancelado')
                                        <span class="d-inline-block ms-2" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top"
                                              title="No puedes deshacer: {{ $a->undo_reason }} Hora límite: {{ $a->lim_undo_hhmm }}">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled>Deshacer</button>
                                        </span>
                                    @endif
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
  tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });
});
</script>
@endpush