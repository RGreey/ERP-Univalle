@extends('layouts.app')

@section('title', 'Editar Descripción General')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Editar Descripción General - Evidencias de Mantenimiento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Campus:</strong> {{ $paquete->sede }}
                        </div>
                        <div class="col-md-6">
                            <strong>Periodo:</strong> {{ str_pad($paquete->mes,2,'0',STR_PAD_LEFT) }}/{{ $paquete->anio }}
                        </div>
                    </div>

                    <form action="{{ route('evidencias-mantenimiento.paquetes.update', $paquete) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="descripcion_general" class="form-label">Descripción General del Trabajo Realizado</label>
                            <textarea 
                                name="descripcion_general" 
                                id="descripcion_general" 
                                class="form-control @error('descripcion_general') is-invalid @enderror" 
                                rows="6" 
                                placeholder="Ej: Los compañeros Rodrigo y John Edwin estuvieron realizando labores de mantenimiento en los laboratorios en las Áreas de psicología, entrada del laboratorio, baño principal, de resane, estuco, lijada y pintada, como también adecuación del baño los días 27 y 28 de enero..."
                            >{{ old('descripcion_general', $paquete->descripcion_general) }}</textarea>
                            @error('descripcion_general')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Esta descripción aparecerá al inicio del PDF de evidencias.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('evidencias-mantenimiento.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
