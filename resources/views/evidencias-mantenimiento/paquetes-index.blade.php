@extends('layouts.app')

@section('title', 'Evidencias de Mantenimiento')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Evidencias de Mantenimiento (por sede/mes/año)</span>
        <form class="d-flex" method="GET">
            <select name="sede" class="form-select form-select-sm me-2" style="width:auto">
                <option value="">Todas las sedes</option>
                @foreach($sedes as $s)
                    <option value="{{ $s }}" {{ request('sede')===$s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            <select name="mes" class="form-select form-select-sm me-2" style="width:auto">
                <option value="">Mes</option>
                @foreach($meses as $m)
                    <option value="{{ $m }}" {{ (string)request('mes')===(string)$m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
            <select name="anio" class="form-select form-select-sm me-2" style="width:auto">
                <option value="">Año</option>
                @foreach($anios as $a)
                    <option value="{{ $a }}" {{ (string)request('anio')===(string)$a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary">Filtrar</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Sede</th>
                        <th>Periodo</th>
                        <th>Fotos</th>
                        <th>PDF</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paquetes as $p)
                        <tr>
                            <td>{{ $p->sede }}</td>
                            <td>{{ str_pad($p->mes,2,'0',STR_PAD_LEFT) }}/{{ $p->anio }}</td>
                            <td>{{ $p->fotos_count }}</td>
                            <td>
                                <span class="badge bg-info">Auto-generado</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-warning" href="{{ route('evidencias-mantenimiento.paquetes.edit', $p) }}" title="Editar descripción">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($p->archivo_pdf)
                                        <button class="btn btn-info" onclick="previsualizarPDF('{{ route('evidencias-mantenimiento.paquetes.previsualizar', $p) }}')">Vista Previa</button>
                                        <button class="btn btn-danger" onclick="limpiarPaquete({{ $p->id }})" title="Limpiar paquete completo">
                                            <i class="fas fa-trash"></i> Limpiar Todo
                                        </button>
                                    @else
                                        <a class="btn btn-success" href="{{ route('evidencias-mantenimiento.paquetes.generar-pdf', $p) }}" title="Generar PDF">
                                            <i class="fas fa-file-pdf"></i> Generar PDF
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">Sin registros</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $paquetes->withQueryString()->links() }}
    </div>
</div>

<!-- Modal para ver PDF -->
<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel">Evidencias de Mantenimiento - PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" style="height: 80vh;">
                <iframe id="visorPdf" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function limpiarPaquete(paqueteId) {
    Swal.fire({
        title: '¿Limpiar paquete completo?',
        text: 'Esta acción eliminará TODAS las fotos y el PDF del paquete. El paquete desaparecerá de la lista hasta que subas nuevas evidencias desde el PWA.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, limpiar todo',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario temporal para enviar DELETE
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/evidencias-mantenimiento/paquetes/${paqueteId}/limpiar`;
            
            // Agregar token CSRF
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Agregar método DELETE
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function previsualizarPDF(url) {
    // Mostrar indicador de carga
    const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
    const modalBody = document.querySelector('#pdfModal .modal-body');
    
    // Agregar mensaje de carga
    modalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3">Generando PDF...</p></div>';
    
    modal.show();
    
    // Hacer una petición AJAX para generar el PDF primero
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/pdf',
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (response.ok) {
            // Crear un blob del PDF
            return response.blob();
        } else {
            throw new Error('Error al generar el PDF - Status: ' + response.status);
        }
    })
    .then(blob => {
        console.log('Blob size:', blob.size);
        console.log('Blob type:', blob.type);
        
        // Crear URL del blob
        const pdfUrl = URL.createObjectURL(blob);
        console.log('PDF URL:', pdfUrl);
        
        // Crear iframe y mostrar el PDF
        const iframe = document.createElement('iframe');
        iframe.id = 'visorPdf';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = 'none';
        
        // Agregar evento onload para debugging
        iframe.onload = function() {
            console.log('Iframe loaded successfully');
        };
        
        iframe.onerror = function() {
            console.error('Iframe error');
        };
        
        iframe.src = pdfUrl;
        
        modalBody.innerHTML = '';
        modalBody.appendChild(iframe);
        
        // Verificar si el iframe se cargó después de un tiempo
        setTimeout(() => {
            if (iframe.contentDocument && iframe.contentDocument.body) {
                console.log('Iframe content loaded');
            } else {
                console.log('Iframe content not loaded yet');
            }
        }, 2000);
    })
    .catch(error => {
        console.error('Error:', error);
        modalBody.innerHTML = '<div class="text-center p-5"><div class="alert alert-danger">Error al generar el PDF: ' + error.message + '</div></div>';
    });
}
</script>
@endsection


