@extends('layouts.app')

@section('title', 'Postulación Subsidio')

@section('content')
<style>
    .uv-form-card { background:#fff; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,.06); padding:22px; }
    .uv-section-title { font-weight:600; font-size:1.05rem; margin:18px 0 8px; color:#343a40; }
    .uv-help { color:#6c757d; font-size:.9rem; }
    .uv-divider { height:1px; background:#eee; margin:18px 0; }
    .uv-docs { background:#f1f3f5; border-left:4px solid #cd1f32; padding:14px; border-radius:6px; }
    .uv-docs ul { margin:8px 0 0 18px; }
    .uv-required::after { content:" *"; color:#dc3545; }

    /* Radios visibles en todas partes (incluida matriz, Opera, etc.) */
    .uv-radio { appearance:auto !important; -webkit-appearance: auto !important; width:16px; height:16px; accent-color:#cd1f32; }
    .form-check-input.uv-radio { border-color:#6c757d; }
    .uv-matrix table { background:#fff; }
    .uv-matrix thead th { background:#f8f9fa; font-weight:600; text-align:center; }
    .uv-matrix td, .uv-matrix th { vertical-align:middle; text-align:center; }
    .is-invalid + .invalid-feedback { display:block; }
</style>

<div class="container">
    <div class="uv-form-card">
        <h3 class="mb-1">{{ $convocatoria->nombre }}</h3>
        <div class="uv-help mb-3">Periodo: {{ optional($convocatoria->periodoAcademico)->nombre }}</div>

        <form id="postulacionForm" method="POST" action="{{ route('subsidio.postulacion.store', $convocatoria->id) }}" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="mb-3">
                <label class="form-label uv-required">Sede principal</label>
                <select name="sede" class="form-select @error('sede') is-invalid @enderror" required>
                    <option value="">Selecciona tu sede</option>
                    <option value="Caicedonia">Caicedonia</option>
                    <option value="Sevilla">Sevilla</option>
                </select>
                @error('sede')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @php $grupos = $encuesta->preguntas->groupBy('grupo'); @endphp

            @foreach($grupos as $grupo => $preguntas)
                @if($grupo)
                    <div class="uv-section-title">{{ $grupo }}</div>
                @endif

                @foreach($preguntas as $preg)
                    @php $ob = $preg->pivot->obligatoria; @endphp

                    @if($preg->tipo === 'parrafo')
                        <div class="uv-docs mb-3">
                            <div class="fw-semibold mb-1">{{ $preg->titulo }}</div>
                            {!! nl2br(e($preg->descripcion ?? '')) !!}
                        </div>
                        @continue
                    @endif

                    <div class="mb-3 uv-q" data-type="{{ $preg->tipo }}" data-id="{{ $preg->id }}" data-ob="{{ $ob ? 1 : 0 }}">
                        <label class="form-label @if($ob) uv-required @endif">{{ $preg->titulo }}</label>
                        @if($preg->descripcion)
                            <div class="uv-help mb-1">{{ $preg->descripcion }}</div>
                        @endif

                        @switch($preg->tipo)
                            @case('seleccion_unica')
                            @case('boolean')
                                @foreach($preg->opciones as $i => $op)
                                    <div class="form-check">
                                        <input class="form-check-input uv-radio"
                                            type="radio"
                                            name="respuestas[{{ $preg->id }}][opcion_id]"
                                            id="preg_{{ $preg->id }}_op_{{ $op->id }}"
                                            value="{{ $op->id }}"
                                            @if($ob && $i===0) required @endif>
                                        <label class="form-check-label" for="preg_{{ $preg->id }}_op_{{ $op->id }}">{{ $op->texto }}</label>
                                    </div>
                                @endforeach
                                <div class="invalid-feedback">Selecciona una opción.</div>
                                @break

                            @case('seleccion_multiple')
                                @foreach($preg->opciones as $op)
                                    <div class="form-check">
                                        <input class="form-check-input uv-radio"
                                            type="checkbox"
                                            name="respuestas[{{ $preg->id }}][opcion_ids][]"
                                            id="preg_{{ $preg->id }}_op_{{ $op->id }}"
                                            value="{{ $op->id }}">
                                        <label class="form-check-label" for="preg_{{ $preg->id }}_op_{{ $op->id }}">{{ $op->texto }}</label>
                                    </div>
                                @endforeach
                                <div class="invalid-feedback">Selecciona al menos una opción.</div>
                                @break

                            @case('texto')
                                <input type="text" class="form-control" name="respuestas[{{ $preg->id }}][texto]" autocomplete="off" @if($ob) required @endif>
                                <div class="invalid-feedback">Completa este campo.</div>
                                @break

                            @case('email')
                                <input type="email" class="form-control" name="respuestas[{{ $preg->id }}][texto]" value="{{ auth()->user()->email }}" @if($ob) required @endif>
                                <div class="invalid-feedback">Introduce un correo válido.</div>
                                @break

                            @case('telefono')
                                <input type="text" class="form-control" name="respuestas[{{ $preg->id }}][texto]" placeholder="Ej: 3001234567" @if($ob) required @endif>
                                <div class="invalid-feedback">Introduce un teléfono válido.</div>
                                @break

                            @case('numero')
                                <input type="number" class="form-control" step="1" name="respuestas[{{ $preg->id }}][numero]" @if($ob) required @endif>
                                <div class="invalid-feedback">Completa este campo.</div>
                                @break

                            @case('fecha')
                                <input type="date" class="form-control" name="respuestas[{{ $preg->id }}][fecha]" @if($ob) required @endif>
                                <div class="invalid-feedback">Selecciona una fecha.</div>
                                @break

                            @case('matrix_single')
                                @php $filas=$preg->filas; $cols=$preg->columnas; @endphp
                                <div class="uv-matrix table-responsive">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-start" style="width:180px"></th>
                                                @foreach($cols as $c)
                                                    <th>{{ $c->etiqueta }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($filas as $f)
                                            <tr>
                                                <td class="text-start">{{ $f->etiqueta }}</td>
                                                @foreach($cols as $c)
                                                    <td>
                                                        <input class="uv-radio" type="radio"
                                                            name="respuestas[{{ $preg->id }}][{{ $f->id }}]"
                                                            value="{{ $c->valor }}">
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="invalid-feedback mt-1">Debes escoger una opción por cada fila.</div>
                                @break

                            @case('programa_db')
                                <select class="form-select" name="respuestas[{{ $preg->id }}][programa_id]" @if($ob) required @endif>
                                    <option value="">Selecciona tu programa</option>
                                    @foreach($programas as $prog)
                                        <option value="{{ $prog->id }}">{{ $prog->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Selecciona tu programa.</div>
                                @break
                        @endswitch
                    </div>
                @endforeach

                <div class="uv-divider"></div>
            @endforeach

            <div class="mb-3">
                <label class="form-label uv-required">Documento único (PDF)</label>
                <input type="file" name="documento_pdf" class="form-control @error('documento_pdf') is-invalid @enderror" accept="application/pdf" required>
                <div class="uv-help mt-1">Sube un único PDF con todos los soportes (máx. 10MB).</div>

                <div class="uv-docs mt-2">
                    <div class="fw-semibold">Este PDF debe incluir (en este orden):</div>
                    <ul>
                        <li>Documento de la universidad con promedio acumulado.</li>
                        <li>Tabulado que verifique el mínimo de créditos matriculados.</li>
                        <li>Solicitud de apoyo a Bienestar (firmada). Si no la tienes, descarga la plantilla y fírmala.</li>
                        <li>Horario de clases (PDF).</li>
                        <li>Recibo de servicios públicos reciente del lugar de residencia (último recibo).</li>
                        <li>Copia del documento de identidad (ambos lados).</li>
                        <li>Otros soportes que consideres necesarios (opcional).</li>
                    </ul>
                    <a href="#" target="_blank" class="link-primary">Descargar plantilla de solicitud (PDF)</a>
                </div>

                @error('documento_pdf')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Enviar postulación</button>
                <a href="{{ route('subsidio.convocatorias.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const form = document.getElementById('postulacionForm');

    function setInvalid(el) {
        el.classList.add('is-invalid');
    }
    function clearInvalid() {
        document.querySelectorAll('.is-invalid').forEach(n=>n.classList.remove('is-invalid'));
    }

    form.addEventListener('submit', function(e) {
        clearInvalid();
        let ok = true;
        const groups = document.querySelectorAll('.uv-q');

        groups.forEach(g => {
            const type = g.dataset.type;
            const required = g.dataset.ob === '1';

            if (!required) return;

            if (type === 'seleccion_unica' || type === 'boolean') {
                const any = g.querySelector('input[type=radio]:checked');
                if (!any) {
                    ok = false;
                    // marca el primer radio del grupo
                    const first = g.querySelector('input[type=radio]');
                    if (first) setInvalid(first);
                }
            }

            if (type === 'seleccion_multiple') {
                const any = g.querySelector('input[type=checkbox]:checked');
                if (!any) {
                    ok = false;
                    const first = g.querySelector('input[type=checkbox]');
                    if (first) setInvalid(first);
                }
            }

            if (type === 'texto' || type === 'email' || type === 'telefono') {
                const inp = g.querySelector('input[type=text], input[type=email]');
                if (inp && (!inp.value || inp.value.trim() === '')) {
                    ok = false; setInvalid(inp);
                }
            }

            if (type === 'numero') {
                const inp = g.querySelector('input[type=number]');
                if (inp && (inp.value === '' || inp.value === null)) {
                    ok = false; setInvalid(inp);
                }
            }

            if (type === 'fecha') {
                const inp = g.querySelector('input[type=date]');
                if (inp && (!inp.value)) {
                    ok = false; setInvalid(inp);
                }
            }

            if (type === 'matrix_single') {
                // Por cada fila, debe haber un radio seleccionado
                const filas = g.querySelectorAll('tbody tr');
                filas.forEach(tr => {
                    const any = tr.querySelector('input[type=radio]:checked');
                    if (!any) {
                        ok = false;
                        const first = tr.querySelector('input[type=radio]');
                        if (first) setInvalid(first);
                    }
                });
            }

            if (type === 'programa_db') {
                const sel = g.querySelector('select');
                if (sel && !sel.value) {
                    ok = false; setInvalid(sel);
                }
            }
        });

        const pdf = form.querySelector('input[name="documento_pdf"]');
        if (pdf && !pdf.value) { ok = false; setInvalid(pdf); }

        if (!ok) {
            e.preventDefault();
            // Opcional: scroll al primer error
            const firstInvalid = document.querySelector('.is-invalid');
            if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
</script>
@endpush
@endsection