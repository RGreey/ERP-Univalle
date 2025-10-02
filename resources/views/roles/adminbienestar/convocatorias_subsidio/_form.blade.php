@php
    $isEdit = isset($convocatoria);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $convocatoria->nombre ?? '') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Periodo Académico</label>
        <select name="periodo_academico" class="form-select" required>
            <option value="" disabled {{ old('periodo_academico', $convocatoria->periodo_academico ?? '') === '' ? 'selected' : '' }}>
                Selecciona un periodo
            </option>
            @foreach($periodos as $p)
                <option value="{{ $p->id }}" {{ (string)old('periodo_academico', $convocatoria->periodo_academico ?? '') === (string)$p->id ? 'selected' : '' }}>
                    {{ $p->nombre }} ({{ \Carbon\Carbon::parse($p->fechaInicio)->format('Y-m-d') }} - {{ \Carbon\Carbon::parse($p->fechaFin)->format('Y-m-d') }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Fecha apertura</label>
        <input type="date" name="fecha_apertura" class="form-control" value="{{ old('fecha_apertura', $convocatoria->fecha_apertura ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Fecha cierre</label>
        <input type="date" name="fecha_cierre" class="form-control" value="{{ old('fecha_cierre', $convocatoria->fecha_cierre ?? '') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Cupos Caicedonia</label>
        <input type="number" name="cupos_caicedonia" min="0" class="form-control" value="{{ old('cupos_caicedonia', $convocatoria->cupos_caicedonia ?? 0) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Cupos Sevilla</label>
        <input type="number" name="cupos_sevilla" min="0" class="form-control" value="{{ old('cupos_sevilla', $convocatoria->cupos_sevilla ?? 0) }}" required>
    </div>

    <div class="col-md-8">
        <label class="form-label">Encuesta asignada</label>
        <select name="encuesta_id" class="form-select">
            <option value="">— Sin encuesta —</option>
            @foreach(($encuestas ?? []) as $e)
                <option value="{{ $e->id }}" {{ (string)old('encuesta_id', $convocatoria->encuesta_id ?? '') === (string)$e->id ? 'selected' : '' }}>
                    {{ $e->nombre }} (v{{ $e->version }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Los estudiantes solo podrán postular si la convocatoria está activa y tiene encuesta asignada.</small>
    </div>

    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="recepcion_habilitada" id="recepcion_habilitada"
                   value="1" {{ old('recepcion_habilitada', $convocatoria->recepcion_habilitada ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="recepcion_habilitada">
                Recepción habilitada
            </label>
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
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Guardar cambios' : 'Crear convocatoria' }}</button>
    <a href="{{ route('admin.convocatorias-subsidio.index') }}" class="btn btn-secondary">Cancelar</a>
</div>