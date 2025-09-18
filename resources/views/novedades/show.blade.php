@extends('layouts.app')

@section('content')
@if(session('success'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                confirmButtonText: 'Aceptar',
                customClass: {
                    confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
            });
        });
    </script>
@endif
<div class="container">
    <a href="{{ route('novedades.index') }}" class="btn btn-link mb-3">&larr; Volver al listado</a>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Detalle de Novedad</h4>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Título</dt>
                <dd class="col-sm-9">{{ $novedad->titulo }}</dd>

                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $novedad->descripcion }}</dd>

                <dt class="col-sm-3">Tipo</dt>
                <dd class="col-sm-9">{{ $novedad->tipo }}</dd>

                <dt class="col-sm-3">Lugar</dt>
                <dd class="col-sm-9">{{ $novedad->lugar->nombreLugar ?? '-' }}</dd>

                <dt class="col-sm-3">Ubicación detallada</dt>
                <dd class="col-sm-9">{{ $novedad->ubicacion_detallada }}</dd>

                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9">
                    <span class="badge bg-{{ $novedad->estado_novedad == 'pendiente' ? 'warning' : ($novedad->estado_novedad == 'en proceso' ? 'info' : ($novedad->estado_novedad == 'finalizada' ? 'primary' : 'success')) }}">
                        {{ ucfirst($novedad->estado_novedad) }}
                    </span>
                </dd>

                <dt class="col-sm-3">Fecha de solicitud</dt>
                <dd class="col-sm-9">{{ $novedad->fecha_solicitud ? \Carbon\Carbon::parse($novedad->fecha_solicitud)->format('d/m/Y H:i') : '-' }}</dd>

                <dt class="col-sm-3">Fecha de finalización</dt>
                <dd class="col-sm-9">{{ $novedad->fecha_finalizacion ? \Carbon\Carbon::parse($novedad->fecha_finalizacion)->format('d/m/Y H:i') : '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Evidencias</h5>
        </div>
        <div class="card-body">
            @if($novedad->evidencias->count())
                <div class="row">
                    @foreach($novedad->evidencias as $evidencia)
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                @if(Str::endsWith($evidencia->archivo_url, ['jpg','jpeg','png']))
                                    <img src="{{ asset('storage/' . $evidencia->archivo_url) }}" class="card-img-top" alt="Evidencia">
                                @else
                                    <a href="{{ asset('storage/' . $evidencia->archivo_url) }}" target="_blank" class="btn btn-outline-primary m-3">Ver archivo</a>
                                @endif
                                <div class="card-body">
                                    <p class="card-text">{{ $evidencia->descripcion }}</p>
                                    <small class="text-muted">Subida: {{ \Carbon\Carbon::parse($evidencia->fecha_subida)->format('d/m/Y H:i') }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">No hay evidencias registradas.</p>
            @endif
        </div>
    </div>

    @php
        $correosServicios = [
            'julio.ceballos@correounivalle.edu.co',
            'rubiel.gutierrez@correounivalle.edu.co',
            'jhonnathan.ososrio@correounivalle.edu.co',
            'rodrigo.buitrago@correounivalle.edu.co',
            'maria.cairasco@correounivalle.edu.co',
            'grajales.maria@correounivalle.edu.co',
            'diana.moscoso@correounivalle.edu.co',
            'luz.estella.quintero@correounivalle.edu.co',
            'loaiza.jhon@correounivalle.edu.co',
            'alarcon.ana@correounivalle.edu.co'
        ];
        $esServiciosGenerales = auth()->check() && in_array(auth()->user()->email, $correosServicios);
    @endphp
    @if($esServiciosGenerales && $novedad->estado_novedad !== 'cerrada')
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Agregar evidencia</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('novedades.addEvidencia', $novedad) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="archivo" class="form-label">Archivos (imágenes o PDF)</label>
                    <input type="file" class="form-control" id="archivo" name="archivo[]" required accept=".jpg,.jpeg,.png,.pdf" multiple>
                    <small class="text-muted">Puedes seleccionar varias imágenes o archivos a la vez.</small>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion" maxlength="255" 
                           placeholder="Ej: Pared pintada completamente, enchufe instalado y funcionando, lámpara reemplazada" required>
                    <small class="text-muted">Describe qué trabajo se realizó. Ejemplos: "Pared pintada completamente", "Enchufe instalado y funcionando", "Lámpara reemplazada"</small>
                </div>
                <button type="submit" class="btn btn-primary">Subir evidencias</button>
            </form>
        </div>
    </div>
    @if($novedad->evidencias->count() > 0 && $novedad->estado_novedad !== 'mantenimiento realizado')
        <form action="{{ route('novedades.updateEstado', $novedad) }}" method="POST" class="mb-4">
            @csrf
            <button type="submit" class="btn btn-warning">
                <i class="fa-solid fa-clipboard-check me-1"></i> Actualizar estado a mantenimiento realizado
            </button>
        </form>
    @endif
    @endif
    @if($novedad->estado_novedad === 'mantenimiento realizado' && auth()->id() === $novedad->usuario_id)
        <form action="{{ route('novedades.close', $novedad) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="fa-solid fa-lock"></i> Cerrar novedad
            </button>
        </form>
    @endif
</div>
@endsection 