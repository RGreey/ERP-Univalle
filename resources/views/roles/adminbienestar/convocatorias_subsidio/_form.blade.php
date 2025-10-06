@php
    $isEdit = isset($convocatoria);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control"
               value="{{ old('nombre', $convocatoria->nombre ?? '') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Periodo Académico</label>
        <select name="periodo_academico" class="form-select" required>
            <option value="" disabled {{ old('periodo_academico', $convocatoria->periodo_academico ?? '') === '' ? 'selected' : '' }}>
                Selecciona un periodo
            </option>
            @foreach($periodos as $p)
                <option value="{{ $p->id }}" @selected(old('periodo_academico', $convocatoria->periodo_academico ?? '') == $p->id)>
                    {{ $p->nombre ?? ($p->id.' - '.$p->fechaInicio.' a '.$p->fechaFin) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Fecha de apertura</label>
        <input type="date" name="fecha_apertura" class="form-control"
               value="{{ old('fecha_apertura', optional($convocatoria->fecha_apertura ?? null)?->toDateString()) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Fecha de cierre</label>
        <input type="date" name="fecha_cierre" class="form-control"
               value="{{ old('fecha_cierre', optional($convocatoria->fecha_cierre ?? null)?->toDateString()) }}" required>
    </div>
</div>

<hr class="my-3">

{{-- Periodo real del beneficio (para planificar cupos de todo el rango) --}}
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Inicio beneficio</label>
        <input type="date" name="fecha_inicio_beneficio" class="form-control"
               value="{{ old('fecha_inicio_beneficio', optional($convocatoria->fecha_inicio_beneficio ?? null)?->toDateString()) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Fin beneficio</label>
        <input type="date" name="fecha_fin_beneficio" class="form-control"
               value="{{ old('fecha_fin_beneficio', optional($convocatoria->fecha_fin_beneficio ?? null)?->toDateString()) }}">
        <div class="form-text">Hasta esta fecha se planifican cupos (lunes a viernes, por sede).</div>
    </div>
</div>

<hr class="my-3">

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Cupos Caicedonia (diarios)</label>
        <input type="number" name="cupos_caicedonia" min="0" class="form-control"
               value="{{ old('cupos_caicedonia', $convocatoria->cupos_caicedonia ?? 0) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Cupos Sevilla (diarios)</label>
        <input type="number" name="cupos_sevilla" min="0" class="form-control"
               value="{{ old('cupos_sevilla', $convocatoria->cupos_sevilla ?? 0) }}" required>
    </div>
</div>

<hr class="my-3">

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Encuesta asignada</label>
        <select name="encuesta_id" class="form-select">
            <option value="">(sin encuesta)</option>
            @foreach(($encuestas ?? []) as $e)
                <option value="{{ $e->id }}" @selected(old('encuesta_id', $convocatoria->encuesta_id ?? null) == $e->id)>
                    {{ $e->nombre }}
                </option>
            @endforeach
        </select>
        <div class="form-check mt-2">
            <input type="checkbox" class="form-check-input" id="recepcion_habilitada" name="recepcion_habilitada"
                   value="1" {{ old('recepcion_habilitada', $convocatoria->recepcion_habilitada ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="recepcion_habilitada">Recepción habilitada</label>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger mt-3">
        <ul class="mb-0">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        {{ $isEdit ? 'Guardar cambios' : 'Crear convocatoria' }}
    </button>
    <a href="{{ route('admin.convocatorias-subsidio.index') }}" class="btn btn-secondary">Cancelar</a>
</div>