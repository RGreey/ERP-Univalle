@extends('layouts.app')

@section('title','Postulaciones')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Postulaciones · {{ $convocatoria->nombre }}</h3>
        <a href="{{ route('admin.convocatorias-subsidio.index') }}" class="btn btn-secondary">Volver</a>
    </div>

    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar (nombre o correo)">
        </div>
        <div class="col-md-2">
            <select name="estado" class="form-select">
                <option value="">Estado (todos)</option>
                @foreach(['enviada','evaluada','beneficiario','rechazada','anulada'] as $st)
                    <option value="{{ $st }}" @selected(request('estado')===$st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="sede" class="form-select">
                <option value="">Sede (todas)</option>
                <option value="Caicedonia" @selected(request('sede')==='Caicedonia')>Caicedonia</option>
                <option value="Sevilla" @selected(request('sede')==='Sevilla')>Sevilla</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" type="submit">Filtrar</button>
        </div>
    </form>

    @if($postulaciones->isEmpty())
        <div class="alert alert-info">No hay postulaciones.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Programa</th>
                        <th>Sede</th>
                        <th>Estado</th>
                        <th>Enviada</th>
                        <th style="width:240px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($postulaciones as $p)
                    <tr>
                        <td>{{ $p->user->name ?? $p->user->email }}</td>
                        <td>{{ $p->programa ?? '—' }}</td>
                        <td>{{ $p->sede }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst($p->estado) }}</span></td>
                        <td>{{ $p->created_at->format('Y-m-d H:i') }}</td>
                        <td class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.convocatorias-subsidio.postulaciones.show', $p->id) }}">Ver</a>
                            @if($p->documento_pdf)
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.convocatorias-subsidio.postulaciones.pdf', $p->id) }}">PDF</a>
                            @endif
                            <form action="{{ route('admin.convocatorias-subsidio.postulaciones.estado', $p->id) }}" method="POST" class="d-inline">
                                @csrf
                                <select name="estado" class="form-select form-select-sm d-inline-block" style="width:140px" onchange="this.form.submit()">
                                    @foreach(['enviada','evaluada','beneficiario','rechazada','anulada'] as $st)
                                        <option value="{{ $st }}" @selected($p->estado===$st)>{{ ucfirst($st) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{ $postulaciones->links() }}
    @endif
</div>
@endsection