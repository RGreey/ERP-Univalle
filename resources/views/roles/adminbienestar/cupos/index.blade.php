@extends('layouts.app')
@section('title','Cupos y Asistencias')

@section('content')
<style>
    .uv-card { background:#fff; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,.06); padding:18px; }
    .table thead th { background:#f8f9fa; }
    .progress { height: 8px; }
    @media (max-width: 992px){
        .actions { gap:.5rem; display:flex; flex-direction: column; align-items: stretch !important; }
        .filters .btn, .filters .form-control, .filters .form-select { width: 100%; }
    }
</style>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Cupos y Asistencias</h2>
        <a href="{{ route('admin.convocatorias') }}" class="btn btn-outline-dark btn-sm">Ver convocatorias</a>
    </div>

    <form method="GET" class="row g-2 mb-3 filters">
        <div class="col-lg-5">
            <label class="form-label">Convocatoria</label>
            <select name="convocatoria_id" class="form-select" onchange="this.form.submit()">
                @forelse($convocatorias as $c)
                    <option value="{{ $c->id }}" @selected(($convId ?? null)==$c->id)>{{ $c->nombre }}</option>
                @empty
                    <option value="">No hay convocatorias</option>
                @endforelse
            </select>
        </div>
        <div class="col-lg-3">
            <label class="form-label">Lunes de la semana</label>
            <input type="date" name="lunes" class="form-control" value="{{ $lunes->toDateString() }}">
        </div>
        <div class="col-lg-2 d-grid">
            <label class="form-label">&nbsp;</label>
            <button class="btn btn-outline-secondary">Cambiar semana</button>
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!($convId ?? null))
        <div class="alert alert-info">Selecciona una convocatoria para continuar.</div>
    @else
        <div class="mb-2 d-flex flex-wrap gap-2 actions">
            <form method="POST" action="{{ route('admin.cupos.generar-plantilla') }}" class="d-inline">
                @csrf
                <input type="hidden" name="convocatoria_id" value="{{ $convId }}">
                <input type="hidden" name="lunes" value="{{ $lunes->toDateString() }}">
                <button class="btn btn-outline-primary">Generar plantilla con la semana actual</button>
            </form>

            <form method="POST" action="{{ route('admin.cupos.aplicar-plantilla') }}" class="d-inline">
                @csrf
                <input type="hidden" name="convocatoria_id" value="{{ $convId }}">
                <input type="hidden" name="lunes" value="{{ $lunes->toDateString() }}">
                <button class="btn btn-dark">Aplicar a todos los días del período</button>
            </form>

            <form method="POST" action="{{ route('admin.cupos.auto-asignar-semana') }}" class="d-inline">
                @csrf
                <input type="hidden" name="convocatoria_id" value="{{ $convId }}">
                <input type="hidden" name="lunes" value="{{ $lunes->toDateString() }}">
                <button class="btn btn-outline-primary">Asignar automáticamente semana actual</button>
            </form>

            <form method="GET" action="{{ route('admin.cupos.exportar-semana') }}" class="ms-auto">
                <input type="hidden" name="convocatoria_id" value="{{ $convId }}">
                <input type="hidden" name="lunes" value="{{ $lunes->toDateString() }}">
                <button class="btn btn-outline-success">Exportar CSV (semana)</button>
            </form>

            <form method="GET" action="{{ route('admin.cupos.reporte-semana') }}">
                <input type="hidden" name="convocatoria_id" value="{{ $convId }}">
                <input type="hidden" name="lunes" value="{{ $lunes->toDateString() }}">
                <button class="btn btn-outline-secondary">Ver reporte semanal</button>
            </form>
        </div>

        @if(($convocatoria ?? null) && (!$convocatoria->fecha_inicio_beneficio || !$convocatoria->fecha_fin_beneficio))
            <div class="alert alert-warning">
                Define <strong>fecha de inicio y fin del beneficio</strong> en la convocatoria para planificar el periodo.
            </div>
        @endif

        <div class="uv-card mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-semibold mb-0">
                    Semana: {{ $lunes->format('Y-m-d') }} al {{ $lunes->copy()->addDays(6)->format('Y-m-d') }}
                </h6>
                <div class="text-muted small">
                    Asignaciones de la semana: <strong>{{ number_format($asignadosSemana ?? 0) }}</strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:22%">Fecha</th>
                            <th style="width:16%">Sede</th>
                            <th style="width:14%">Capacidad</th>
                            <th style="width:14%">Asignados</th>
                            <th>Ocupación</th>
                            <th style="width:14%">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // SOLO L–V
                            $dias = collect([0,1,2,3,4])->map(fn($d)=>$lunes->copy()->addDays($d));
                            $sedes = ['caicedonia','sevilla'];
                            $byKey = $cupos->keyBy(fn($c)=>$c->fecha->toDateString().'|'.$c->sede);
                        @endphp

                        @forelse($dias as $d)
                            @foreach($sedes as $sede)
                                @php
                                    $key = $d->toDateString().'|'.$sede;
                                    $c = $byKey->get($key);
                                    $cap = $c->capacidad ?? (($sede==='caicedonia') ? ($convocatoria->cupos_caicedonia ?? 0) : ($convocatoria->cupos_sevilla ?? 0));
                                    $asg = $c->asignados ?? 0;
                                    $pct = $cap > 0 ? round(($asg/$cap)*100) : 0;
                                    $nombreDia = ucfirst($d->locale('es')->isoFormat('dddd'));
                                @endphp
                                <tr>
                                    <td>{{ $d->format('Y-m-d') }} ({{ $nombreDia }})</td>
                                    <td>{{ ucfirst($sede) }}</td>
                                    <td>{{ $cap }}</td>
                                    <td>{{ $asg }}</td>
                                    <td>
                                        <div class="progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar {{ $pct>=100 ? 'bg-danger' : ($pct>=70 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('admin.cupos.dia', ['convocatoria_id'=>$convId, 'fecha'=>$d->toDateString(), 'sede'=>$sede]) }}">
                                            Gestionar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="6" class="text-muted">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection