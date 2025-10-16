@extends('layouts.app')
@section('title','Restaurantes / Sedes')

@section('content')
<div class="container">
<x-admin.volver to="admin.subsidio.admin.dashboard" keep="q,estado" label="Volver" />
<h3 class="mb-3">Gestión de Restaurantes / Sedes</h3>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card mb-4">
    <div class="card-header"><strong>Crear nueva sede</strong></div>
    <div class="card-body">
    <form class="row g-2" method="POST" action="{{ route('admin.restaurantes.store') }}">
        @csrf
        <div class="col-md-3">
        <label class="form-label">Código</label>
        <input name="codigo" class="form-control" placeholder="caicedonia" required>
        <div class="form-text">Solo letras, números o guiones (alpha-dash).</div>
        </div>
        <div class="col-md-5">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" placeholder="Restaurante Caicedonia" required>
        </div>
        <div class="col-md-2 align-self-end">
        <button class="btn btn-primary w-100">Crear</button>
        </div>
    </form>
    </div>
</div>

@forelse($restaurantes as $r)
    <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
        <strong>{{ $r->nombre }}</strong>
        <span class="text-muted">({{ $r->codigo }})</span>
        </div>
        <form method="POST" action="{{ route('admin.restaurantes.destroy',$r) }}" onsubmit="return confirm('¿Eliminar sede?');">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm">Eliminar</button>
        </form>
    </div>
    <div class="card-body">
        <h6>Usuarios asignados</h6>
        <div class="table-responsive mb-2">
        <table class="table table-sm align-middle">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
            @forelse($r->users as $u)
                <tr>
                <td>{{ $u->name }}</td>
                <td class="text-muted">{{ $u->email }}</td>
                <td><span class="badge bg-secondary">{{ $u->rol }}</span></td>
                <td class="text-end">
                    <form method="POST" action="{{ route('admin.restaurantes.detach',$r->id) }}">
                    @csrf @method('DELETE')
                    <input type="hidden" name="user_id" value="{{ $u->id }}">
                    <button class="btn btn-sm btn-outline-danger">Quitar</button>
                    </form>
                </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Sin usuarios asignados.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>

        <form class="row g-2" method="POST" action="{{ route('admin.restaurantes.attach',$r) }}">
        @csrf
        <div class="col-md-4">
            <label class="form-label">Correo del usuario</label>
            <input type="email" name="email" class="form-control" placeholder="usuario@correo.edu.co" required>
            <div class="form-text">Si el usuario no tiene el rol, se le asignará automáticamente “Restaurante”.</div>
        </div>
        <div class="col-md-2 align-self-end">
            <button class="btn btn-outline-primary w-100">Asignar</button>
        </div>
        </form>
    </div>
    </div>
@empty
    <div class="alert alert-info">No hay sedes creadas.</div>
@endforelse
</div>
@endsection