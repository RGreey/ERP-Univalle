@extends('layouts.app')
@section('title','Asignación diaria')

@section('content')
<style>
    .uv-card { background:#fff; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,.06); padding:16px; }
    .uv-sub { color:#6c757d; font-size:.9rem; }
    .table-sm th, .table-sm td { padding:.35rem .5rem; }
    @media (max-width: 992px){
        .filters .btn, .filters .form-control, .filters .form-select { width: 100%; }
    }
</style>

<div class="container">
    @php $nombreDia = ucfirst($fecha->locale('es')->isoFormat('dddd')); @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Asignación diaria</h2>
            <div class="uv-sub">
                Convocatoria: <strong>{{ $convocatoria->nombre }}</strong> ·
                Fecha: <strong>{{ $fecha->toDateString() }} ({{ $nombreDia }})</strong> ·
                Sede: <strong>{{ ucfirst($sede) }}</strong>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cupos.index', ['convocatoria_id'=>$convocatoria->id, 'lunes'=>$lunes->toDateString()]) }}"
               class="btn btn-outline-secondary btn-sm">
                Volver a Cupos
            </a>
            <a href="{{ route('admin.cupos.reporte-semana', ['convocatoria_id'=>$convocatoria->id, 'lunes'=>$lunes->toDateString()]) }}"
               class="btn btn-secondary btn-sm">
                Ver reporte
            </a>
        </div>
    </div>

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

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="uv-card">
                <h5 class="mb-2">Cupo del día</h5>

                <form method="POST" action="{{ route('admin.cupos.dia.capacidad') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="cupo_diario_id" value="{{ $cupo->id }}">
                    <div class="col-6">
                        <label class="form-label">Capacidad</label>
                        <input type="number" name="capacidad" min="0" class="form-control" value="{{ $cupo->capacidad }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-dark w-100">Guardar</button>
                    </div>
                </form>

                <div class="mt-3 uv-sub">
                    Asignados: <strong>{{ $cupo->asignados }}</strong> / {{ $cupo->capacidad }}
                </div>

                <hr>

                <h6>Asignados</h6>
                @if($asignados->isEmpty())
                    <div class="text-muted">Sin asignados.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($asignados as $a)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $a->user?->name }}</div>
                                    <div class="small text-muted">{{ $a->user?->email }}</div>
                                </div>
                                <form method="POST" action="{{ route('admin.cupos.asignacion.eliminar', $a->id) }}" onsubmit="return confirm('¿Eliminar asignación?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Quitar</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="col-lg-8">
            <div class="uv-card">
                <form method="GET" class="row g-2 mb-2 filters">
                    <input type="hidden" name="convocatoria_id" value="{{ $convocatoria->id }}">
                    <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">
                    <input type="hidden" name="sede" value="{{ $sede }}">
                    <div class="col-md-5">
                        <input type="text" name="q" class="form-control" placeholder="Buscar estudiante (nombre o correo)" value="{{ $q }}">
                    </div>
                    <div class="col-md-5">
                        <div class="form-check mt-2">
                            {{-- Por defecto se incluyen otras sedes (no bloquea). Desmarca para ver solo la sede actual. --}}
                            <input type="checkbox" class="form-check-input" id="otras" name="incluir_otras_sedes" value="1" {{ $incluirOtrasSedes ? 'checked' : '' }}>
                            <label class="form-check-label" for="otras">Incluir otras sedes</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-secondary">Filtrar</button>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Candidatos (orden: prioridad, menor carga semanal, antigüedad)</h5>
                    <form method="POST" action="{{ route('admin.cupos.dia.auto-asignar') }}" onsubmit="return confirm('¿Auto-asignar hasta completar capacidad?')">
                        @csrf
                        <input type="hidden" name="convocatoria_id" value="{{ $convocatoria->id }}">
                        <input type="hidden" name="cupo_diario_id" value="{{ $cupo->id }}">
                        <input type="hidden" name="incluir_otras_sedes" value="{{ $incluirOtrasSedes ? 1 : 0 }}">
                        <div class="form-check form-check-inline me-2">
                            <input type="checkbox" class="form-check-input" id="limite" name="respetar_limite_semanal" value="1" checked>
                            <label for="limite" class="form-check-label small">Respetar límite semanal por prioridad</label>
                        </div>
                        <button class="btn btn-primary btn-sm">Auto-asignar</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Correo</th>
                                <th class="text-center">Prioridad</th>
                                <th class="text-center">Asig. semana</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($candidatos as $p)
                                <tr>
                                    <td>{{ $p->user?->name }}</td>
                                    <td class="text-muted small">{{ $p->user?->email }}</td>
                                    <td class="text-center"><span class="badge bg-dark">{{ $p->prioridad_final ?? '—' }}</span></td>
                                    <td class="text-center">{{ $p->semana_asignados }}</td>
                                    <td class="text-end">
                                        @if($p->asignado_este_dia)
                                            <span class="text-muted small">Ya asignado este día</span>
                                        @elseif($cupo->asignados >= $cupo->capacidad)
                                            <span class="text-danger small">Sin cupos</span>
                                        @else
                                            <form method="POST" action="{{ route('admin.cupos.dia.asignar') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="cupo_diario_id" value="{{ $cupo->id }}">
                                                <input type="hidden" name="postulacion_id" value="{{ $p->id }}">
                                                <button class="btn btn-sm btn-outline-primary">Asignar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">Sin candidatos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection