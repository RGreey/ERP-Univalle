@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Editar usuario</h2>
    <form action="{{ route('admin.usuarios.update', $usuario->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $usuario->name) }}" required>
            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Correo</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ $usuario->email }}" readonly>
        </div>
        <div class="mb-3">
            <label for="rol" class="form-label">Rol</label>
            <select class="form-select" id="rol" name="rol" required>
                <option value="Estudiante" {{ $usuario->rol == 'Estudiante' ? 'selected' : '' }}>Estudiante</option>
                <option value="Profesor" {{ $usuario->rol == 'Profesor' ? 'selected' : '' }}>Profesor</option>
                <option value="Administrativo" {{ $usuario->rol == 'Administrativo' ? 'selected' : '' }}>Administrativo</option>
                <option value="CooAdmin" {{ $usuario->rol == 'CooAdmin' ? 'selected' : '' }}>CooAdmin</option>
                <option value="AuxAdmin" {{ $usuario->rol == 'AuxAdmin' ? 'selected' : '' }}>AuxAdmin</option>
            </select>
            @error('rol')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Nueva contraseña (opcional)</label>
            <input type="password" class="form-control" id="password" name="password">
            <small class="text-muted">Deja este campo vacío si no deseas cambiar la contraseña.</small>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar usuario</button>
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary ms-2">Cancelar</a>
    </form>
</div>
@endsection 