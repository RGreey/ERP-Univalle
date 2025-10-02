@extends('layouts.app')

@section('title','Detalle de postulación')

@section('content')
<style>
    .pull-up { margin-top: -12px; } /* sube el contenido respecto al container global */
    .uv-card { background:#fff; border-radius:12px; box-shadow:0 10px 24px rgba(0,0,0,.06); padding:18px; }
    .uv-sub { color:#6c757d; }
    .uv-badge { font-size:.85rem; }
    .table-clean thead th { background:#f8f9fa; }
    .checkcell { font-size: 1rem; color:#198754; }
    .muted { color:#adb5bd; }
    .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    @media (max-width: 992px){ .grid-2 { grid-template-columns: 1fr; } }

    .header-actions { gap: .5rem; }
    .estado-pill { min-width: 160px; }
    .kv-table th { width: 40%; color:#6c757d; font-weight: 600; }
    .accordion-button { background:#f8f9fa; }
    .prio-actions .form-select { width: 88px; }
</style>

@php
    use Illuminate\Support\Str;
    $mat = $postulacion->respuestas->first(fn($r)=>optional($r->pregunta)->tipo==='matrix_single');
    $filas = $mat?->pregunta?->filas ?? collect();
    $cols  = $mat?->pregunta?->columnas ?? collect();
    $map   = $mat?->respuesta_json ?? [];

    $porGrupo = $postulacion->respuestas
        ->filter(fn($r)=>optional($r->pregunta)->tipo !== 'matrix_single')
        ->groupBy(fn($r)=>$r->pregunta->grupo ?? 'General');

    $renderRespuesta = function($r) {
        $t = $r->pregunta->tipo;
        if (in_array($t,['seleccion_unica','boolean'])) {
            return optional($r->opcion)->texto ?? '—';
        } elseif ($t==='seleccion_multiple') {
            $ids = $r->opcion_ids ?? [];
            $txt = $r->pregunta->opciones->whereIn('id',(array)$ids)->pluck('texto')->implode(', ');
            return $txt ?: '—';
        } elseif (in_array($t,['texto','email','telefono'])) {
            return $r->respuesta_texto ?: '—';
        } elseif ($t==='numero') {
            return $r->respuesta_numero ?? '—';
        } elseif ($t==='fecha') {
            return $r->respuesta_fecha ?? '—';
        }
        return '—';
    };
@endphp

<div class="container pull-up">
    <!-- Encabezado compacto -->
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 class="mb-1">Postulación de {{ $postulacion->user->name ?? $postulacion->user->email }}</h3>
            <div class="uv-sub">
                Convocatoria: <strong>{{ $postulacion->convocatoria->nombre }}</strong>
                · Sede: <strong>{{ $postulacion->sede }}</strong>
            </div>
        </div>
        <div class="d-flex align-items-center header-actions">
            @if($postulacion->documento_pdf)
                <a href="{{ route('admin.convocatorias-subsidio.postulaciones.pdf', ['postulacion'=>$postulacion->id]) }}" class="btn btn-outline-dark btn-sm">Descargar PDF</a>
            @endif

            <form method="POST" action="{{ route('admin.convocatorias-subsidio.postulaciones.estado', ['postulacion'=>$postulacion->id]) }}">
                @csrf
                <select name="estado" class="form-select form-select-sm estado-pill" onchange="this.form.submit()">
                    @foreach(['enviada','evaluada','beneficiario','rechazada','anulada'] as $st)
                        <option value="{{ $st }}" @selected($postulacion->estado===$st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('admin.convocatorias-subsidio.postulaciones.index', ['convocatoria'=>$postulacion->convocatoria_id]) }}" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>

    <!-- Tarjetas superiores -->
    <div class="row g-3 mb-2">
        <div class="col-lg-4">
            <div class="uv-card h-100">
                <h6 class="fw-semibold mb-2">Datos del estudiante</h6>
                <table class="table table-sm mb-0 kv-table">
                    <tbody>
                        <tr><th>Programa</th><td>{{ $postulacion->programa ?? '—' }}</td></tr>
                        <tr><th>Correo</th><td>{{ $postulacion->correo ?? $postulacion->user->email }}</td></tr>
                        <tr><th>Teléfono</th><td>{{ $postulacion->telefono ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="uv-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-semibold mb-0">Resumen de prioridad</h6>
                    <div class="d-flex align-items-center prio-actions gap-2">
                        <form method="POST" action="{{ route('admin.convocatorias-subsidio.postulaciones.recalcular', ['postulacion'=>$postulacion->id]) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary">Recalcular y guardar</button>
                        </form>

                        <form method="POST" action="{{ route('admin.convocatorias-subsidio.postulaciones.prioridad-manual', ['postulacion'=>$postulacion->id]) }}" class="d-flex align-items-center gap-2">
                            @csrf
                            <select name="prioridad_final" class="form-select form-select-sm" title="Fijar prioridad manual">
                                @for($i=1;$i<=9;$i++)
                                    <option value="{{ $i }}" @selected(($postulacion->prioridad_final ?? $prio['final']) == $i)>{{ $i }}</option>
                                @endfor
                            </select>
                            <button class="btn btn-sm btn-outline-dark">Guardar manual</button>
                        </form>
                    </div>
                </div>

                <div class="grid-2">
                    <table class="table table-sm mb-0 kv-table">
                        <tbody>
                            <tr><th>Residencia</th><td>{{ $prio['detalles']['residencia'] ?? '—' }}</td></tr>
                            <tr><th>Estrato (urbano)</th><td>{{ $prio['detalles']['estrato'] ?? 'No aplica' }}</td></tr>
                            <tr><th>Jornada</th><td>{{ $prio['detalles']['jornada'] ?? '—' }}</td></tr>
                        </tbody>
                    </table>

                    <table class="table table-sm mb-0 kv-table">
                        <tbody>
                            <tr><th>Prioridad base</th><td><span class="badge bg-dark">{{ $prio['base'] ?? '—' }}</span></td></tr>
                            <tr><th>Ajustes F1/F2</th><td>{{ $prio['delta_auto'] }} (F1: {{ $prio['f1'] ? 'Sí' : 'No' }}, F2: {{ $prio['f2'] ? 'Sí' : 'No' }})</td></tr>
                            <tr><th>Mejora total</th><td>{{ $prio['total_mejora'] }}</td></tr>
                            <tr>
                                <th>Prioridad final</th>
                                <td>
                                    @php $pf = $postulacion->prioridad_final ?? $prio['final']; @endphp
                                    @if($pf !== null)
                                        <span class="badge {{ $pf <= 3 ? 'bg-success' : ($pf <= 6 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                            {{ $pf }}
                                        </span>
                                        @if($postulacion->prioridad_final)
                                            <span class="uv-sub ms-2">
                                                Guardada: {{ $postulacion->prioridad_final }}
                                                @if($postulacion->prioridad_calculada_en)
                                                    ({{ $postulacion->prioridad_calculada_en->format('Y-m-d H:i') }})
                                                @endif
                                            </span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="uv-sub mt-2">Regla: base (residencia/estrato/jornada), +1 F1, +1 F2, tope mejora 3, piso final 2.</div>
            </div>
        </div>
    </div>

    <!-- Matriz de días -->
    <div class="uv-card mb-3">
        <h6 class="fw-semibold mb-2">Preferencias de días</h6>
        @if(($filas->count() === 0) || empty($map))
            <div class="text-muted">No registrado.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-clean align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-start" style="width:180px">Día</th>
                            @foreach($cols as $c)
                                <th class="text-center">{{ $c->etiqueta }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($filas as $f)
                            @php $sel = $map[$f->id] ?? null; @endphp
                            <tr>
                                <td class="text-start">{{ $f->etiqueta }}</td>
                                @foreach($cols as $c)
                                    <td class="text-center">
                                        @if($sel === $c->valor)
                                            <span class="checkcell">✓</span>
                                        @else
                                            <span class="muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Respuestas completas en acordeones -->
    <div class="uv-card">
        <h6 class="fw-semibold mb-2">Respuestas completas</h6>

        @if($porGrupo->isEmpty())
            <div class="text-muted">No hay respuestas para mostrar.</div>
        @else
            <div class="accordion" id="accRespuestas">
                @foreach($porGrupo as $grupo => $resps)
                    @php $accId = 'grp_'.Str::slug($grupo ?: 'General','_'); @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="h_{{ $accId }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c_{{ $accId }}" aria-expanded="false" aria-controls="c_{{ $accId }}">
                                {{ $grupo ?: 'General' }}
                            </button>
                        </h2>
                        <div id="c_{{ $accId }}" class="accordion-collapse collapse" aria-labelledby="h_{{ $accId }}" data-bs-parent="#accRespuestas">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr><th style="width:45%">Pregunta</th><th>Respuesta</th></tr>
                                        </thead>
                                        <tbody>
                                        @foreach($resps as $r)
                                            <tr>
                                                <td>{{ $r->pregunta->titulo }}</td>
                                                <td>{{ $renderRespuesta($r) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@push('scripts')
@if (session('success'))
<script>
Swal.fire({ title: '¡Listo!', text: @json(session('success')), icon: 'success', confirmButtonColor: '#cd1f32' });
</script>
@endif
@if (session('info'))
<script>
Swal.fire({ title: 'Info', text: @json(session('info')), icon: 'info', confirmButtonColor: '#cd1f32' });
</script>
@endif
@endpush
@endsection