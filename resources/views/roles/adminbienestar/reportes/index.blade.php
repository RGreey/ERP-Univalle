@extends('layouts.app')
@section('title','Reportes')

@section('content')
<div class="container">
    <x-admin.volver to="admin.subsidio.admin.dashboard" keep="q,estado" label="Volver" />
<h3 class="mb-3">Reportes de estudiantes</h3>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form class="row g-2 mb-3" method="GET" action="{{ route('admin.reportes') }}">
    <div class="col-auto"><input name="q" class="form-control" placeholder="Buscar texto" value="{{ $q }}"></div>
    <div class="col-auto">
    <select name="estado" class="form-select">
        <option value="">Todos</option>
        @foreach(['pendiente','en_proceso','resuelto','archivado'] as $e)
        <option value="{{ $e }}" @selected(($estado ?? '')===$e)>{{ ucfirst($e) }}</option>
        @endforeach
    </select>
    </div>
    <div class="col-auto">
    <select name="tipo" class="form-select">
        <option value="">Cualquier tipo</option>
        @foreach(['servicio','higiene','trato','sugerencia','otro'] as $t)
        <option value="{{ $t }}" @selected(($tipo ?? '')===$t)>{{ ucfirst($t) }}</option>
        @endforeach
    </select>
    </div>
    <div class="col-auto">
    <select name="sede" class="form-select">
        <option value="">Ambas sedes</option>
        <option value="caicedonia" @selected(($sede ?? '')==='caicedonia')>Caicedonia</option>
        <option value="sevilla" @selected(($sede ?? '')==='sevilla')>Sevilla</option>
    </select>
    </div>
    <div class="col-auto"><input type="date" name="desde" class="form-control" value="{{ $desde }}"></div>
    <div class="col-auto"><input type="date" name="hasta" class="form-control" value="{{ $hasta }}"></div>
    <div class="col-auto"><button class="btn btn-primary">Filtrar</button></div>
</form>

<div class="card">
    <div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
            <th>Fecha</th>
            <th>Estudiante</th>
            <th>Tipo</th>
            <th>Título</th>
            <th>Sede</th>
            <th>Estado</th>
            <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $r)
            @php $badge = match($r->estado){ 'pendiente'=>'secondary','en_proceso'=>'info','resuelto'=>'success','archivado'=>'dark', default=>'secondary' }; @endphp
            <tr>
                <td>{{ $r->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $r->user?->name }}</td>
                <td>{{ ucfirst($r->tipo) }}</td>
                <td>{{ $r->titulo ?? '—' }}</td>
                <td>{{ $r->sede ? ucfirst($r->sede) : 'N/A' }}</td>
                <td><span class="badge bg-{{ $badge }}">{{ $r->estado }}</span></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reportes.show',$r) }}">Ver</a></td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-muted p-3">Sin resultados.</td></tr>
            @endforelse
        </tbody>
        </table>
    </div>
    </div>
</div>

<div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection