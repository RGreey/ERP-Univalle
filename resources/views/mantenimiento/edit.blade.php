@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Editar Actividad de Mantenimiento</h2>
                <a href="{{ route('mantenimiento.show', $actividad) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Información de la Actividad</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('mantenimiento.update', $actividad) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="actividad" class="form-label">Nombre de la Actividad *</label>
                                    <input type="text" class="form-control @error('actividad') is-invalid @enderror" 
                                           id="actividad" name="actividad" value="{{ old('actividad', $actividad->actividad) }}" required>
                                    @error('actividad')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="orden" class="form-label">Orden</label>
                                    <input type="number" class="form-control @error('orden') is-invalid @enderror" 
                                           id="orden" name="orden" value="{{ old('orden', $actividad->orden) }}" min="0">
                                    @error('orden')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" name="descripcion" rows="3" 
                                      placeholder="Descripción detallada de la actividad">{{ old('descripcion', $actividad->descripcion) }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="frecuencia" class="form-label">Frecuencia *</label>
                                    <select class="form-select @error('frecuencia') is-invalid @enderror" 
                                            id="frecuencia" name="frecuencia" required>
                                        <option value="">Seleccionar frecuencia</option>
                                        <option value="anual" {{ old('frecuencia', $actividad->frecuencia) == 'anual' ? 'selected' : '' }}>Anual</option>
                                        <option value="trimestral" {{ old('frecuencia', $actividad->frecuencia) == 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                                        <option value="cuatrimestral" {{ old('frecuencia', $actividad->frecuencia) == 'cuatrimestral' ? 'selected' : '' }}>Cuatrimestral</option>
                                        <option value="mensual" {{ old('frecuencia', $actividad->frecuencia) == 'mensual' ? 'selected' : '' }}>Mensual</option>
                                        <option value="cuando_se_requiera" {{ old('frecuencia', $actividad->frecuencia) == 'cuando_se_requiera' ? 'selected' : '' }}>Cuando se requiera</option>
                                    </select>
                                    @error('frecuencia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                    <input type="date" class="form-control @error('fecha_inicio') is-invalid @enderror" 
                                           id="fecha_inicio" name="fecha_inicio" 
                                           value="{{ old('fecha_inicio', $actividad->fecha_inicio->format('Y-m-d')) }}" required>
                                    @error('fecha_inicio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_final" class="form-label">Fecha Final *</label>
                                    <input type="date" class="form-control @error('fecha_final') is-invalid @enderror" 
                                           id="fecha_final" name="fecha_final" 
                                           value="{{ old('fecha_final', $actividad->fecha_final->format('Y-m-d')) }}" required>
                                    @error('fecha_final')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="proveedor" class="form-label">Proveedor</label>
                                    <input type="text" class="form-control @error('proveedor') is-invalid @enderror" 
                                           id="proveedor" name="proveedor" 
                                           value="{{ old('proveedor', $actividad->proveedor) }}" 
                                           placeholder="Dejar vacío para Servicios Generales">
                                    <div class="form-text">Si está vacío, se asume que lo realizan los Servicios Generales de la universidad</div>
                                    @error('proveedor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="responsable" class="form-label">Responsable *</label>
                                    <input type="text" class="form-control @error('responsable') is-invalid @enderror" 
                                           id="responsable" name="responsable" 
                                           value="{{ old('responsable', $actividad->responsable) }}" 
                                           placeholder="Ej: Auxiliar de servicios generales, Bomberos, etc." required>
                                    @error('responsable')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('mantenimiento.show', $actividad) }}" class="btn btn-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Actividad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const frecuenciaSelect = document.getElementById('frecuencia');
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinalInput = document.getElementById('fecha_final');

    // Actualizar fecha final automáticamente según la frecuencia
    frecuenciaSelect.addEventListener('change', function() {
        const fechaInicio = new Date(fechaInicioInput.value);
        let fechaFinal = new Date(fechaInicio);

        switch (this.value) {
            case 'anual':
                fechaFinal.setFullYear(fechaFinal.getFullYear() + 1);
                break;
            case 'trimestral':
                fechaFinal.setMonth(fechaFinal.getMonth() + 3);
                break;
            case 'cuatrimestral':
                fechaFinal.setMonth(fechaFinal.getMonth() + 4);
                break;
            case 'mensual':
                fechaFinal.setMonth(fechaFinal.getMonth() + 1);
                break;
            case 'cuando_se_requiera':
                // Para cuando se requiera, mantener la misma fecha
                break;
        }

        fechaFinalInput.value = fechaFinal.toISOString().split('T')[0];
    });

    // Validar que fecha final no sea anterior a fecha inicio
    fechaFinalInput.addEventListener('change', function() {
        const fechaInicio = new Date(fechaInicioInput.value);
        const fechaFinal = new Date(this.value);

        if (fechaFinal < fechaInicio) {
            alert('La fecha final no puede ser anterior a la fecha de inicio');
            this.value = fechaInicioInput.value;
        }
    });
});
</script>
@endsection
