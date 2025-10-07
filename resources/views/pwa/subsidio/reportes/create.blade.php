@extends('layouts.app')
@section('title','Nuevo reporte')

@section('content')
<div class="container">
<h3 class="mb-3">Enviar un reporte</h3>

@if($errors->any())
    <div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
    </div>
@endif

<form method="POST" action="{{ route('app.subsidio.reportes.store') }}">
    @csrf

    <div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
        @php $tipoOld = old('tipo'); @endphp
        @foreach(['servicio','higiene','trato','sugerencia','otro'] as $t)
            <option value="{{ $t }}" @selected($tipoOld===$t)>{{ ucfirst($t) }}</option>
        @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Sede (opcional)</label>
        <select name="sede" class="form-select">
        @php $sedeOld = old('sede'); @endphp
        <option value="">N/A</option>
        <option value="caicedonia" @selected($sedeOld==='caicedonia')>Caicedonia</option>
        <option value="sevilla" @selected($sedeOld==='sevilla')>Sevilla</option>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">Título (opcional)</label>
        <input name="titulo" class="form-control" maxlength="140" value="{{ old('titulo') }}" placeholder="Resumen corto">
    </div>

    <div class="col-12">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" rows="6" maxlength="5000" required placeholder="Describe con detalle lo que quieres reportar...">{{ old('descripcion') }}</textarea>
    </div>

    <div class="col-12">
        <button class="btn btn-primary">Enviar reporte</button>
        <a class="btn btn-link" href="{{ route('app.subsidio.reportes.index') }}">Volver</a>
    </div>
    </div>
</form>
</div>
@endsection