@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="fw-bold text-primary mb-4"><i class="fa-solid fa-users-gear me-2"></i>Consultar Monitores</h2>
    
    <!-- Mensaje destacado -->
    <div class="alert alert-info d-flex align-items-center mb-4" style="background:rgba(13,110,253,0.08);border-left:5px solid #0d6efd;">
        <i class="fa-solid fa-circle-info me-2"></i>
        <div>
            <strong>Importante:</strong>
            <span class="fw-semibold">
                En cada columna de mes verás una <span class="text-primary">propuesta de horas</span> calculada automáticamente según las fechas y horas semanales del monitor.
            </span>
            <br>
            <span class="fw-semibold">
                <span class="text-danger">Debes revisar, completar y guardar las horas por mes de cada monitor.</span>
                Estas horas serán las que se reflejarán en el seguimiento mensual y en los reportes.
            </span>
            <br>
            <span class="text-secondary">Recuerda que puedes definir fechas generales y luego ajustar individualmente si lo requieres.</span>
        </div>
    </div>

    <!-- Fechas generales -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label for="fechaGeneralVinculacion" class="form-label">Fecha Vinculación General</label>
            <input type="date" id="fechaGeneralVinculacion" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="fechaGeneralCulminacion" class="form-label">Fecha Culminación General</label>
            <input type="date" id="fechaGeneralCulminacion" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-outline-primary" id="btnAplicarFechas">
                <i class="fa-solid fa-calendar-check"></i> Aplicar a todos
            </button>
        </div>
    </div>



    <form id="formGestionMonitores">
        <div class="table-responsive">
                            <table class="table table-bordered align-middle bg-white" id="tablaGestionMonitores">
                    <thead class="table-light sticky-header" id="theadGestionMonitores">
                        <tr id="headerRow">
                            <th class="sticky-col monitor-col">Monitoría</th>
                            <th class="sticky-col-2 monitor-col">Monitor</th>
                        <th class="horas-col">H/Sem</th>
                        <th class="horas-col">H/Tot</th>
                        <th class="fecha-col">F.Vinc</th>
                        <th class="fecha-col">F.Culm</th>
                        <!-- Meses dinámicos -->
                        <th class="historial-col">Historial</th>
                    </tr>
                </thead>
                <tbody id="tbodyGestionMonitores">
                    <tr><td colspan="12" class="text-center">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-info" onclick="imprimirTabla()">
                    <i class="fa-solid fa-print"></i> Imprimir Tabla
                </button>
                <button type="button" class="btn btn-outline-success" onclick="descargarHistorico()">
                    <i class="fa-solid fa-download"></i> Descargar Histórico Completo
                </button>
                                                         <a href="{{ route('lista-admitidos.index') }}" class="btn btn-outline-warning">
                            <i class="fa-solid fa-users"></i> Lista de Admitidos
                       </a>
                 
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<!-- Modal para mostrar el historial de documentos -->
<div class="modal fade" id="modalHistorialDocumentos" tabindex="-1" aria-labelledby="modalHistorialDocumentosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalHistorialDocumentosLabel">
                    <i class="fa-solid fa-history me-2"></i>Historial de Documentos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <div>
                        <strong>Monitor:</strong> <span id="nombreMonitorHistorial"></span>
                        <br>
                        <small>Aquí puedes ver todos los documentos generados para este monitor, incluyendo seguimientos mensuales, asistencias y evaluaciones de desempeño.</small>
                    </div>
                </div>
                
                <div id="contenidoHistorial">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando historial...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- DataTables y Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* Estilos para mejor visualización de la tabla */
.table-container {
    position: relative;
}



/* Vista Compacta - Columnas más pequeñas */
.vista-compacta .monitor-col { width: 180px; font-size: 13px; }
.vista-compacta .horas-col { width: 65px; font-size: 12px; }
.vista-compacta .fecha-col { width: 115px; font-size: 12px; }
.vista-compacta .mes-col { width: 75px; font-size: 12px; }
.vista-compacta .historial-col { width: 80px; font-size: 12px; }
.vista-compacta input { font-size: 12px; padding: 3px 5px; }



/* Columnas fijas para scroll horizontal */
.sticky-col {
    position: sticky;
    left: 0;
    background: white;
    z-index: 10;
    border-right: 2px solid #dee2e6;
}

.sticky-col-2 {
    position: sticky;
    left: 180px; /* Ajustar según el ancho de la primera columna en vista compacta */
    background: white;
    z-index: 10;
    border-right: 2px solid #dee2e6;
}

.sticky-header {
    position: sticky;
    top: 0;
    z-index: 11;
    background: white;
}




/* Responsive - eliminamos esta sección duplicada */

/* Tooltips para mejor UX */
.tooltip-hover {
    cursor: help;
}

/* Mejorar visibilidad de inputs pequeños */
.table input {
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.table input:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Estilos específicos para campos de texto largo */
.campo-largo {
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.3;
    cursor: help;
}

/* Control específico para campos largos */
.campo-largo {
    max-width: 170px; /* Un poco menos que el ancho de la columna para padding */
    width: 100%;
    box-sizing: border-box;
}

/* Efectos hover para campos largos */
.campo-largo:hover {
    background-color: #e3f2fd !important;
    border-color: #2196f3 !important;
    transform: scale(1.02);
    transition: all 0.2s ease;
    z-index: 5;
    position: relative;
}

/* Hacer los campos de monitoría y monitor más visibles */
.sticky-col input[type="text"], 
.sticky-col-2 input[type="text"] {
    font-weight: 600;
    color: #2c3e50;
    background-color: #f8f9fa;
}

.monitor-col input[type="text"] {
    font-weight: 600;
    color: #2c3e50;
    background-color: #f8f9fa;
}

/* Modo responsive para móviles */
@media (max-width: 768px) {
    .vista-compacta .monitor-col { width: 140px; font-size: 11px; }
    .vista-compacta .fecha-col { width: 100px; }
    .sticky-col-2 { left: 140px; }
}

/* Colores para diferentes tipos de datos */
.fecha-vinculacion { background-color: #f8f9fa; }
.fecha-culminacion { background-color: #f8f9fa; }
.horas-mensuales { background-color: #fff3cd; }
</style>

<script>
let mesesDinamicos = [];

function getMesesEntreFechas(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return [];
    const meses = [];
    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return [];
    const fechaActual = new Date(inicio.getFullYear(), inicio.getMonth(), 1);
    while (fechaActual <= new Date(fin.getFullYear(), fin.getMonth() + 1, 0)) {
        const nombreMes = fechaActual.toLocaleString('es-ES', { month: 'long' }).toLowerCase();
        meses.push(nombreMes);
        fechaActual.setMonth(fechaActual.getMonth() + 1);
    }
    return meses;
}

function calcularHorasMensuales(fechaInicio, fechaFin, horasSemanales) {
    if (!fechaInicio || !fechaFin || !horasSemanales) return {};
    const horasMensuales = {};
    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return {};
    const fechaActual = new Date(inicio.getFullYear(), inicio.getMonth(), 1);
    while (fechaActual <= new Date(fin.getFullYear(), fin.getMonth() + 1, 0)) {
        const nombreMes = fechaActual.toLocaleString('es-ES', { month: 'long' }).toLowerCase();
        const primerDia = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 1);
        const ultimoDia = new Date(fechaActual.getFullYear(), fechaActual.getMonth() + 1, 0);
        const inicioMes = new Date(Math.max(primerDia, inicio));
        const finMes = new Date(Math.min(ultimoDia, fin));
        const diasLaborables = Math.ceil((finMes - inicioMes) / (1000 * 60 * 60 * 24));
        const semanasEnMes = Math.ceil(diasLaborables / 7);
        horasMensuales[nombreMes] = semanasEnMes * horasSemanales;
        fechaActual.setMonth(fechaActual.getMonth() + 1);
    }
    return horasMensuales;
}

function inicializarDataTable() {
    if ($.fn.DataTable.isDataTable('#tablaGestionMonitores')) {
        $('#tablaGestionMonitores').DataTable().destroy();
    }
    $('#tablaGestionMonitores').DataTable({
        language: {
            "decimal": "",
            "emptyTable": "No hay datos disponibles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar columna ascendente",
                "sortDescending": ": activar para ordenar columna descendente"
            }
        },
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        responsive: true,
        destroy: true
    });
}

document.addEventListener('DOMContentLoaded', function() {
    fetch('/gestion-monitores/data')
        .then(res => res.json())
        .then(data => {
            // Guardar datos globalmente para el historial
            window.monitoriasData = data.monitorias || [];
            
            const tbody = document.getElementById('tbodyGestionMonitores');
            const thead = document.getElementById('headerRow');
            tbody.innerHTML = '';
            mesesDinamicos = [];

            if (!data.monitorias || data.monitorias.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" class="text-center">No hay monitorías activas.</td></tr>';
                return;
            }

            let mesesSet = new Set();
            let monitoriasArray = Array.isArray(data.monitorias) ? data.monitorias : Object.values(data.monitorias);
            monitoriasArray.forEach(m => {
                const meses = getMesesEntreFechas(m.fecha_vinculacion, m.fecha_culminacion);
                meses.forEach(mes => mesesSet.add(mes));
            });
            mesesDinamicos = Array.from(mesesSet);

            let headerHtml = `
                <th>Monitoría</th>
                <th>Monitor Elegido</th>
                <th>Horas Semanales</th>
                <th>Horas Totales</th>
                <th>Fecha Vinculación</th>
                <th>Fecha Culminación</th>
            `;
                            mesesDinamicos.forEach(mes => {
                    headerHtml += `<th class="mes-col">${mes.charAt(0).toUpperCase() + mes.slice(1)}</th>`;
                });
                headerHtml += '<th class="historial-col">Historial</th>';
                thead.innerHTML = headerHtml;

            monitoriasArray.forEach((m, idx) => {
                let horas = {};
                if (m.horas_mensuales) {
                    horas = typeof m.horas_mensuales === 'string' ? JSON.parse(m.horas_mensuales) : m.horas_mensuales;
                }
                const horasSugeridas = calcularHorasMensuales(m.fecha_vinculacion, m.fecha_culminacion, m.horas_semanales);

                // Calcular mes actual para el seguimiento
                const hoy = new Date();
                const mesActual = hoy.getMonth() + 1; // 1-12

                let row = `<tr>
                    <input type="hidden" name="monitores[${idx}][monitor_id]" value="${m.monitor_id}">
                    <td class="sticky-col monitor-col">
                        <input type="text" class="form-control campo-largo" name="monitores[${idx}][monitoria_nombre]" 
                               value="${m.nombre}" readonly title="Monitoría: ${m.nombre}" 
                               data-bs-toggle="tooltip" data-bs-placement="top">
                    </td>
                    <td class="sticky-col-2 monitor-col">
                        <input type="text" class="form-control campo-largo" name="monitores[${idx}][monitor_elegido]" 
                               value="${m.monitor_elegido}" readonly title="Monitor: ${m.monitor_elegido}"
                               data-bs-toggle="tooltip" data-bs-placement="top">
                    </td>
                    <td class="horas-col"><input type="number" class="form-control" name="monitores[${idx}][horas_semanales]" value="${m.horas_semanales}" readonly></td>
                    <td class="horas-col"><input type="number" class="form-control" name="monitores[${idx}][horas_totales]" value="${m.horas_totales ?? ''}" readonly></td>
                    <td class="fecha-col"><input type="date" class="form-control fecha-vinculacion" name="monitores[${idx}][fecha_vinculacion]" value="${m.fecha_vinculacion ? m.fecha_vinculacion.substring(0,10) : ''}" onchange="actualizarHorasMensuales(this)"></td>
                    <td class="fecha-col"><input type="date" class="form-control fecha-culminacion" name="monitores[${idx}][fecha_culminacion]" value="${m.fecha_culminacion ? m.fecha_culminacion.substring(0,10) : ''}" onchange="actualizarHorasMensuales(this)"></td>
                `;
                mesesDinamicos.forEach(mes => {
                    const valorActual = horas[mes] ?? '';
                    const valorSugerido = horasSugeridas[mes] ?? '';
                    row += `<td class="mes-col">
                        <input type="number" class="form-control horas-mensuales" 
                            name="monitores[${idx}][${mes}]" 
                            value="${valorActual}"
                            placeholder="${valorSugerido}"
                            title="Horas sugeridas: ${valorSugerido}">
                    </td>`;
                });
                // Columna de Historial
                row += `<td class="historial-col">
                    <div class="d-flex flex-column gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="mostrarHistorialDocumentos(${m.monitor_id}, '${m.monitor_elegido}')" title="Ver historial completo de documentos">
                            <i class="fa-solid fa-history"></i>
                        </button>
                    </div>
                </td>
                </tr>`;
                tbody.innerHTML += row;
            });

            setTimeout(() => {
                inicializarDataTable();
                // Inicializar tooltips de Bootstrap
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }, 100);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('tbodyGestionMonitores').innerHTML = 
                '<tr><td colspan="12" class="text-center text-danger">Error al cargar los datos.</td></tr>';
        });

    // Botón para aplicar fechas generales
    document.getElementById('btnAplicarFechas').addEventListener('click', function() {
        const fechaVinc = document.getElementById('fechaGeneralVinculacion').value;
        const fechaCulm = document.getElementById('fechaGeneralCulminacion').value;
        if (!fechaVinc || !fechaCulm) {
            Swal.fire({
                icon: 'warning',
                title: 'Fechas requeridas',
                text: 'Por favor selecciona ambas fechas generales para aplicar.',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        document.querySelectorAll('.fecha-vinculacion').forEach(input => input.value = fechaVinc);
        document.querySelectorAll('.fecha-culminacion').forEach(input => input.value = fechaCulm);
    });

    // Aplicar vista compacta como única vista
    const tabla = document.getElementById('tablaGestionMonitores');
    tabla.classList.add('vista-compacta');
});

// Función para actualizar las horas mensuales cuando cambian las fechas
function actualizarHorasMensuales(input) {
    const row = input.closest('tr');
    const fechaInicio = row.querySelector('input[name*="[fecha_vinculacion]"]').value;
    const fechaFin = row.querySelector('input[name*="[fecha_culminacion]"]').value;
    const horasSemanales = parseInt(row.querySelector('input[name*="[horas_semanales]"]').value);

    if (fechaInicio && fechaFin && horasSemanales) {
        const horasSugeridas = calcularHorasMensuales(fechaInicio, fechaFin, horasSemanales);
        row.querySelectorAll('input[type="number"]').forEach(input => {
            const matches = input.name.match(/\[(.*?)\]$/);
            if (matches) {
                const mes = matches[1];
                if (horasSugeridas[mes]) {
                    input.placeholder = horasSugeridas[mes];
                    input.title = `Horas sugeridas: ${horasSugeridas[mes]}`;
                }
            }
        });
    }
}

// Imprimir tabla con formato profesional (sin columna de seguimiento)
function imprimirTabla() {
    const tabla = document.getElementById('tablaGestionMonitores').cloneNode(true);
    
    // Remover DataTables y hacer la tabla más simple para impresión
    const inputs = tabla.querySelectorAll('input');
    inputs.forEach(input => {
        const span = document.createElement('span');
        span.textContent = input.value || input.placeholder || '';
        span.style.fontWeight = 'bold';
        input.parentNode.replaceChild(span, input);
    });

    // Remover la columna de documentos para el PDF
    const filas = tabla.querySelectorAll('tr');
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td, th');
        if (celdas.length > 0) {
            // Remover la última celda (columna de documentos)
            const ultimaCelda = celdas[celdas.length - 1];
            if (ultimaCelda) {
                ultimaCelda.remove();
            }
        }
    });

    // Crear contenido HTML para impresión
    const contenidoHTML = `
        <html>
        <head>
            <title>Consultar Monitores - ERP Univalle</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { color: #0d6efd; margin: 0; }
                .header p { color: #666; margin: 5px 0; }
                .info { background-color: #e7f3ff; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ERP Univalle</h1>
                <h2>Consultar Monitores</h2>
                <p>Fecha de impresión: ${new Date().toLocaleDateString('es-ES')}</p>
            </div>
            <div class="info">
                <strong>Información:</strong> Esta tabla muestra los monitores activos con sus fechas de vinculación, 
                culminación y horas mensuales asignadas. Las horas en cada columna representan las horas trabajadas por mes.
            </div>
            ${tabla.outerHTML}
        </body>
        </html>
    `;

    // Crear un iframe oculto para la impresión
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.style.position = 'fixed';
    iframe.style.top = '-9999px';
    iframe.style.left = '-9999px';
    document.body.appendChild(iframe);
    
    iframe.onload = function() {
        try {
            iframe.contentWindow.document.write(contenidoHTML);
            iframe.contentWindow.document.close();
            
            // Esperar un momento para que se cargue el contenido
            setTimeout(() => {
                iframe.contentWindow.print();
                
                // Remover el iframe después de un tiempo
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 1000);
            }, 500);
        } catch (error) {
            console.error('Error al imprimir:', error);
            // Fallback: usar ventana emergente si el iframe falla
            const ventana = window.open('', '', 'height=700,width=1200');
            ventana.document.write(contenidoHTML);
            ventana.document.close();
            ventana.print();
        }
    };
    
    iframe.src = 'about:blank';
}

// Descargar histórico completo
function descargarHistorico() {
    Swal.fire({
        title: 'Descargando Histórico',
        text: 'Preparando el archivo con todos los monitores históricos...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('/gestion-monitores/historico')
        .then(response => {
            if (response.ok) {
                return response.blob();
            }
            throw new Error('Error al generar el histórico');
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `historico_monitores_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            Swal.fire({
                icon: 'success',
                title: '¡Descarga Completada!',
                text: 'El archivo con el histórico completo de monitores se ha descargado correctamente.',
                confirmButtonText: 'Entendido'
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error en la Descarga',
                text: 'No se pudo generar el archivo del histórico. Por favor, intenta nuevamente.',
                confirmButtonText: 'Cerrar'
            });
        });
}

// Manejar el envío del formulario
document.getElementById('formGestionMonitores').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = {};
    for (let [key, value] of formData.entries()) {
        const matches = key.match(/monitores\[(\d+)\]\[(.*?)\]/);
        if (matches) {
            const [, index, field] = matches;
            if (!data[index]) data[index] = {};
            data[index][field] = value;
        }
    }
    const meses = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];
    Object.values(data).forEach(monitor => {
        monitor.horas_mensuales = {};
        meses.forEach(mes => {
            if (monitor.hasOwnProperty(mes)) {
                if (monitor[mes] !== '' && !isNaN(monitor[mes]) && parseInt(monitor[mes]) > 0) {
                    monitor.horas_mensuales[mes] = parseInt(monitor[mes]);
                }
                delete monitor[mes];
            }
        });
    });
    fetch('/gestion-monitores/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ monitores: Object.values(data) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Guardado!',
                text: 'Datos guardados correctamente. Recuerda que las horas mensuales serán las que se reflejarán en el seguimiento.',
                confirmButtonText: 'Entendido'
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al guardar los datos: ' + data.message,
                confirmButtonText: 'Cerrar'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al guardar los datos',
            confirmButtonText: 'Cerrar'
        });
    });
});

// Función para mostrar el historial de documentos
function mostrarHistorialDocumentos(monitorId, nombreMonitor) {
    // Actualizar el nombre del monitor en el modal
    document.getElementById('nombreMonitorHistorial').textContent = nombreMonitor;
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('modalHistorialDocumentos'));
    modal.show();
    
    // Buscar los datos del monitor en la tabla actual
    const monitorias = window.monitoriasData || [];
    const monitor = monitorias.find(m => m.monitor_id == monitorId);
    
    if (monitor && monitor.documentos) {
        mostrarContenidoHistorial(monitor.documentos);
    } else {
        // Si no hay datos en memoria, cargar desde el servidor
        cargarHistorialDesdeServidor(monitorId);
    }
}

// Función para mostrar el contenido del historial
function mostrarContenidoHistorial(documentos) {
    const contenido = document.getElementById('contenidoHistorial');
    
    if (!documentos || documentos.length === 0) {
        contenido.innerHTML = `
            <div class="alert alert-warning text-center">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                No hay documentos en el historial para este monitor.
            </div>
        `;
        return;
    }
    
    let html = '<div class="table-responsive">';
    html += '<table class="table table-striped table-hover">';
    html += '<thead class="table-light">';
    html += '<tr>';
    html += '<th>Tipo de Documento</th>';
    html += '<th>Período</th>';
    html += '<th>Estado</th>';
    html += '<th>Fecha Generación</th>';
    html += '<th>Acciones</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';
    
    documentos.forEach(doc => {
        const nombreMes = doc.mes ? obtenerNombreMes(doc.mes) : '';
        const periodo = doc.tipo_documento === 'evaluacion_desempeno' ? 
            (doc.parametros_generacion?.periodo_academico || 'N/A') :
            (doc.mes && doc.anio ? `${nombreMes} ${doc.anio}` : 'N/A');
        
        const estadoClase = doc.estado === 'firmado' ? 'bg-success text-white' : 
                           doc.estado === 'generado' ? 'bg-secondary text-white' : 'bg-warning text-dark';
        
        html += '<tr>';
        html += `<td><i class="${obtenerIconoTipo(doc.tipo_documento)} me-2"></i>${obtenerNombreTipo(doc.tipo_documento)}</td>`;
        html += `<td>${periodo}</td>`;
        html += `<td><span class="badge ${estadoClase}">${doc.estado}</span></td>`;
        html += `<td>${formatearFecha(doc.fecha_generacion)}</td>`;
        html += '<td>';
        
        // Botón para ver documento
        if (doc.tipo_documento === 'asistencia' && doc.ruta_archivo) {
            html += `<a href="/monitoria/asistencia/ver/${doc.monitor_id}/${doc.anio}/${doc.mes}" class="btn btn-sm btn-outline-primary me-1" target="_blank" title="Ver documento">
                <i class="fa-solid fa-eye"></i> Ver
            </a>`;
        } else if (doc.tipo_documento === 'seguimiento' && doc.mes) {
            html += `<a href="/monitoria/seguimiento/pdf/${doc.monitor_id}/${doc.mes}" class="btn btn-sm btn-outline-info me-1" target="_blank" title="Ver documento">
                <i class="fa-solid fa-eye"></i> Ver
            </a>`;
        } else if (doc.tipo_documento === 'evaluacion_desempeno') {
            html += `<a href="/monitoria/desempeno/pdf/${doc.monitor_id}" class="btn btn-sm btn-outline-primary me-1" target="_blank" title="Ver documento">
                <i class="fa-solid fa-eye"></i> Ver
            </a>`;
        }
        
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    
    contenido.innerHTML = html;
}

// Función para cargar historial desde el servidor
function cargarHistorialDesdeServidor(monitorId) {
    const contenido = document.getElementById('contenidoHistorial');
    contenido.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando historial desde el servidor...</p>
        </div>
    `;
    
    // Aquí podrías hacer una llamada AJAX para cargar el historial
    // Por ahora, mostraremos un mensaje de error
    setTimeout(() => {
        contenido.innerHTML = `
            <div class="alert alert-warning text-center">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                No se pudo cargar el historial. Intenta recargar la página.
            </div>
        `;
    }, 2000);
}

// Funciones auxiliares
function obtenerNombreMes(mes) {
    const meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    return meses[mes - 1] || '';
}

function obtenerNombreTipo(tipo) {
    const tipos = {
        'seguimiento': 'Seguimiento Mensual',
        'asistencia': 'Asistencia Mensual',
        'evaluacion_desempeno': 'Evaluación de Desempeño'
    };
    return tipos[tipo] || tipo;
}

function obtenerIconoTipo(tipo) {
    const iconos = {
        'seguimiento': 'fa-solid fa-eye',
        'asistencia': 'fa-solid fa-file-pdf',
        'evaluacion_desempeno': 'fa-solid fa-file-pdf'
    };
    return iconos[tipo] || 'fa-solid fa-file';
}

function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
@endsection