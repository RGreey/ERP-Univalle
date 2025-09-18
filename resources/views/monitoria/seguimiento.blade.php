@extends('layouts.app')

@section('styles')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    #actividadesTable tbody tr:hover {
        background: #f0f6ff;
    }
    #actividadesTable thead th {
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    #actividadesTable td, #actividadesTable th {
        vertical-align: middle;
    }
    #actividadesTable input, #actividadesTable textarea {
        font-size: 1rem;
    }
    #actividadesTable .btn-danger {
        box-shadow: 0 2px 6px rgba(220,53,69,0.08);
        transition: background 0.2s, color 0.2s;
    }
    #actividadesTable .btn-danger:hover {
        background: #b02a37;
        color: #fff;
    }
</style>
@endsection

@section('content')
<div class="container">
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                confirmButtonText: 'Aceptar'
            });
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                confirmButtonText: 'Aceptar'
            });
        });
    </script>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fa-solid fa-clipboard-list me-2"></i>Seguimiento de Monitoría</h2>
    </div>

    {{-- Verificar restricción de fecha de entrevista para estudiantes --}}
    @php
        $mostrarRestriccion = false;
        $fechaEntrevistas = null;
        
        if ($esMonitor && !$esEncargado) {
            // Obtener la convocatoria de esta monitoría
            $convocatoria = \App\Models\Convocatoria::find($monitoria->convocatoria);
            
            if ($convocatoria && $convocatoria->fechaEntrevistas) {
                $fechaEntrevistas = \Carbon\Carbon::parse($convocatoria->fechaEntrevistas);
                $fechaActual = \Carbon\Carbon::now();
                
                // Si aún no ha pasado la fecha de entrevista, mostrar restricción
                if ($fechaActual->lt($fechaEntrevistas)) {
                    $mostrarRestriccion = true;
                }
            }
        }
    @endphp

    {{-- Mensaje de restricción para estudiantes antes de la fecha de entrevista --}}
    @if($mostrarRestriccion)
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert" style="background:rgba(255,193,7,0.13);border-left:5px solid #ffc107;">
            <i class="fa-solid fa-clock me-2 text-warning"></i>
            <div>
                <strong>Acceso Restringido</strong>
                <br>
                El seguimiento de monitoría estará disponible después del <strong>{{ $fechaEntrevistas->format('d/m/Y') }}</strong> (fecha límite de entrevistas).
                <br>
                <small class="text-muted">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    Esta restricción permite que los encargados completen el proceso de selección antes de que los estudiantes puedan registrar sus actividades.
                </small>
            </div>
        </div>
        
        {{-- Mostrar solo información básica --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-semibold text-secondary mb-3">
                    <i class="fa-solid fa-info-circle me-2"></i>Información de tu Monitoría
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Monitoría:</strong> {{ $monitoria->nombre ?? 'N/A' }}</p>
                        <p><strong>Fecha límite de entrevistas:</strong> {{ $fechaEntrevistas->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Estado:</strong> <span class="badge bg-warning text-dark">En proceso de selección</span></p>
                        <p><strong>Acceso al seguimiento:</strong> <span class="badge bg-secondary">Después de {{ $fechaEntrevistas->format('d/m/Y') }}</span></p>
                    </div>
                </div>
            </div>
        </div>
        
        @php return; @endphp {{-- Detener la ejecución aquí para no mostrar el resto del contenido --}}
    @endif

    @php
        $monitoriaNombre = null;
        if (isset($monitoria) && $monitoria) {
            $monitoriaNombre = $monitoria->nombre ?? null;
        }
        if (!$monitoriaNombre && isset($monitor) && $monitor) {
            try {
                $monitoriaNombre = optional($monitor->monitoria)->nombre;
            } catch (\Throwable $e) {
                $monitoriaNombre = null;
            }
        }
    @endphp

    @if(!empty($monitoriaNombre))
        <div class="mb-2">
            <span class="badge bg-secondary"><i class="fa-solid fa-bookmark me-1"></i> Monitoría: {{ $monitoriaNombre }}</span>
        </div>
    @endif

    @if(isset($tieneMultiplesMonitores) && $tieneMultiplesMonitores && $esEncargado)
        <div class="alert alert-info mb-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <strong><i class="fa-solid fa-users me-1"></i> Esta monitoría tiene múltiples monitores</strong>
                </div>
                <div class="col-md-6">
                    <select class="form-select" id="monitorSelector" onchange="cambiarMonitor()">
                        @foreach($monitors as $m)
                            @php
                                $userName = \App\Models\User::find($m->user)->name ?? 'Monitor ' . $m->id;
                            @endphp
                            <option value="{{ $m->id }}" {{ $monitor && $monitor->id == $m->id ? 'selected' : '' }}>
                                {{ $userName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @endif

    @php
        use Carbon\Carbon;
        $fechaVinculacion = null;
        $fechaCulminacion = null;
        
        if (isset($monitor) && $monitor) {
            $fechaVinculacion = $monitor->fecha_vinculacion;
            $fechaCulminacion = $monitor->fecha_culminacion;
        } elseif (isset($monitoria) && $monitoria && $monitoria->monitor) {
            $fechaVinculacion = $monitoria->monitor->fecha_vinculacion;
            $fechaCulminacion = $monitoria->monitor->fecha_culminacion;
        }
        
        $diasRestantes = null;
        $enPeriodoValido = false;
        $mensajePeriodo = '';
        
        if ($fechaVinculacion && $fechaCulminacion) {
            try {
                $hoy = Carbon::today();
                $fechaInicio = Carbon::parse($fechaVinculacion);
                $fechaFin = Carbon::parse($fechaCulminacion);
                
                $diasRestantes = $hoy->diffInDays($fechaFin, false);
                $enPeriodoValido = $hoy->between($fechaInicio, $fechaFin);
                
                if ($hoy->lt($fechaInicio)) {
                    $mensajePeriodo = "La monitoría inicia el {$fechaInicio->format('d/m/Y')}. No puedes registrar actividades hasta esa fecha.";
                } elseif ($hoy->gt($fechaFin)) {
                    $mensajePeriodo = "La monitoría culminó el {$fechaFin->format('d/m/Y')}. Ya no puedes registrar actividades.";
                } else {
                    $mensajePeriodo = "Período válido: {$fechaInicio->format('d/m/Y')} - {$fechaFin->format('d/m/Y')}";
                }
            } catch (\Throwable $e) {
                $diasRestantes = null;
                $enPeriodoValido = false;
            }
        }
    @endphp

    @if($fechaVinculacion && $fechaCulminacion)
        @if($enPeriodoValido)
            @if($diasRestantes >= 0 && $diasRestantes <= 3)
                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert" style="background:rgba(255,193,7,0.13);border-left:5px solid #ffc107;">
                    <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>
                    <div>
                        <strong>Período válido:</strong> {{ Carbon::parse($fechaVinculacion)->format('d/m/Y') }} - {{ Carbon::parse($fechaCulminacion)->format('d/m/Y') }}
                        <br>
                        Tu monitoría finaliza en <strong>{{ $diasRestantes }} {{ $diasRestantes == 1 ? 'día' : 'días' }}</strong> ({{ Carbon::parse($fechaCulminacion)->format('d/m/Y') }}).
                        Por favor, diligencia el seguimiento y carga los documentos requeridos para el cierre.
                    </div>
                </div>
            @else
                <div class="alert alert-info d-flex align-items-center mb-3" role="alert" style="background:rgba(13,110,253,0.08);border-left:5px solid #0d6efd;">
                    <i class="fa-solid fa-circle-info me-2 text-info"></i>
                    <div>
                        <strong>Período válido:</strong> {{ Carbon::parse($fechaVinculacion)->format('d/m/Y') }} - {{ Carbon::parse($fechaCulminacion)->format('d/m/Y') }}
                        <br>
                        Puedes registrar actividades dentro de este período.
                    </div>
                </div>
            @endif
        @else
            <div class="alert alert-danger d-flex align-items-center mb-3" role="alert" style="background:rgba(220,53,69,0.08);border-left:5px solid #dc3545;">
                <i class="fa-solid fa-circle-exclamation me-2 text-danger"></i>
                <div>
                    <strong>{{ $mensajePeriodo }}</strong>
                    <br>
                    Solo se pueden registrar actividades dentro del período de vinculación.
                </div>
            </div>
        @endif
    @else
        <div class="alert alert-warning d-flex align-items-center mb-3" role="alert" style="background:rgba(255,193,7,0.13);border-left:5px solid #ffc107;">
            <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>
            <div>
                <strong>Atención:</strong> No se han definido las fechas de vinculación y culminación para esta monitoría.
                <br>
                Por favor, contacta al administrador para configurar las fechas antes de registrar actividades.
            </div>
        </div>
    @endif

    

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="mb-3">
                <h5 class="fw-semibold text-secondary mb-1">Meta de horas para <span class="text-primary">{{ $mesActual }}</span>:</h5>
                <div class="d-flex align-items-center gap-3">
                    <span class="fs-5 fw-bold text-success">{{ $metaHoras }} horas</span>
                    <div class="flex-grow-1">
                        <div class="progress" style="height: 24px;">
                            <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%">0%</div>
                        </div>
                    </div>
                </div>
            </div>
            <form action="{{ route('monitoria.seguimiento.guardar') }}" method="POST" id="seguimientoForm">
                @csrf

                <input type="hidden" name="monitor_id" id="monitor_id" value="{{ $monitor->id ?? ($monitoria->monitor->id ?? '') }}">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle bg-white shadow-sm rounded-4" id="actividadesTable" style="overflow:hidden;">
                        <thead class="table-primary text-center align-middle" style="font-size:1.05rem;">
                            <tr style="border-radius:12px;">
                                <th style="border-top-left-radius:12px;">Fecha</th>
                                <th>Hora Ingreso</th>
                                <th>Hora Salida</th>
                                <th>Total Horas</th>
                                <th>Actividad Realizada</th>
                                <th>Observación Encargado</th>
                                <th style="border-top-right-radius:12px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="actividadesBody" class="table-group-divider">
                        @foreach($actividades as $actividad)
                            <tr data-actividad-id="{{ $actividad->id }}" class="align-middle" style="transition:background 0.2s;">
                                <td>
                                    <input type="date" name="fecha_monitoria[]" class="form-control border-0 bg-light rounded-3" value="{{ $actividad->fecha_monitoria }}" required @if($esEncargado || !$enPeriodoValido) readonly disabled @endif>
                                    @if($esMonitor && $enPeriodoValido)
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="registrarFechaHoy(this)">Hoy</button>
                                    @endif
                                </td>
                                <td><input type="time" name="hora_ingreso[]" class="form-control border-0 bg-light rounded-3" value="{{ $actividad->hora_ingreso }}" required @if($esEncargado || !$enPeriodoValido) readonly disabled @endif></td>
                                <td><input type="time" name="hora_salida[]" class="form-control border-0 bg-light rounded-3" value="{{ $actividad->hora_salida }}" required @if($esEncargado || !$enPeriodoValido) readonly disabled @endif></td>
                                <td><input type="text" name="total_horas[]" class="form-control border-0 bg-light rounded-3 text-center fw-semibold" value="{{ $actividad->total_horas }}" readonly></td>
                                <td><textarea name="actividad_realizada[]" class="form-control border-0 bg-light rounded-3" rows="2" required @if($esEncargado || !$enPeriodoValido) readonly disabled @endif>{{ $actividad->actividad_realizada }}</textarea></td>
                                <td>
                                    @if($esEncargado)
                                        <div class="input-group">
                                            <textarea class="form-control border-0 bg-warning-subtle rounded-3" rows="2"
                                                id="observacion-{{ $actividad->id }}"
                                                placeholder="Observación para el monitor...">{{ $actividad->observacion_encargado }}</textarea>
                                            <button type="button" class="btn btn-outline-primary guardar-observacion-btn"
                                                data-actividad-id="{{ $actividad->id }}">
                                                <i class="fa-solid fa-floppy-disk"></i>
                                            </button>
                                        </div>
                                    @elseif($esMonitor && $actividad->observacion_encargado)
                                        <div class="alert alert-warning py-2 px-3 mb-0 d-flex align-items-center gap-2" style="background:rgba(255,193,7,0.13);border-left:4px solid #ffc107;">
                                            <i class="fa-solid fa-comment-dots me-2 text-warning"></i>
                                            <span class="fw-semibold text-dark">Observación:</span>
                                            <span class="text-dark">{{ $actividad->observacion_encargado }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($esMonitor && $enPeriodoValido)
                                    <button type="button" class="btn btn-danger btn-sm rounded-circle" title="Eliminar" onclick="eliminarFila(this)"><i class="fa-solid fa-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <label class="fw-semibold">Total de horas registradas:</label>
                        <input type="text" id="totalHorasMensuales" class="form-control" readonly>
                    </div>
                    <div class="col-md-6 text-end">
                        @if($esMonitor)
                        <button type="button" class="btn btn-outline-primary me-2" onclick="agregarFila()" @if(!$enPeriodoValido) disabled @endif>
                            <i class="fa-solid fa-plus"></i> Agregar Actividad
                        </button>
                        <button type="submit" class="btn btn-success" id="btnGuardarSeguimiento" @if(!$enPeriodoValido) disabled @endif>
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Seguimiento
                        </button>
                        @elseif($esEncargado)
                        <button type="submit" class="btn btn-success" id="btnFirmarActividades">
                            <i class="fa-solid fa-signature"></i> Firmar Actividades
                        </button>
                        @endif
                        <button type="button" class="btn btn-secondary ms-2" id="btnGenerarPDF" style="display:none;">
                            <i class="fa-solid fa-file-pdf"></i> Generar PDF
                        </button>
                    </div>
                </div>
            </form>

            {{-- Bloque de carga y visualización de asistencia mensual --}}
            @if($esMonitor && isset($monitoria) && $monitoria->modalidad === 'docencia')
            <div class="mt-4">
                <h5 class="fw-semibold mb-2"><i class="fa-solid fa-file-arrow-up text-primary me-2"></i>Asistencia Mensual</h5>
                @php
                    $puedeSubirAsistencia = isset($puedeSubirAsistencia) ? $puedeSubirAsistencia : false;
                    $asistenciaActual = isset($asistenciaActual) ? $asistenciaActual : null;
                @endphp
                @if($puedeSubirAsistencia)
                    <button class="btn btn-outline-success mb-2" id="btnMostrarCargaAsistencia">
                        <i class="fa-solid fa-upload"></i> Subir archivo de asistencia ({{ $mesActual }})
                    </button>
                @endif
                @if($asistenciaActual)
                    @php
                        \Log::info('DEBUG asistenciaActual', [
                            'monitor_id' => $asistenciaActual->monitor_id ?? null,
                            'mes' => $asistenciaActual->mes ?? null,
                            'anio' => $asistenciaActual->anio ?? null,
                            'asistencia_path' => $asistenciaActual->asistencia_path ?? null,
                        ]);
                    @endphp
                    @if($asistenciaActual && $asistenciaActual->monitor_id && $asistenciaActual->anio && $asistenciaActual->mes && !empty($asistenciaActual->asistencia_path))
                        <div class="mt-2 alert alert-success py-2 px-3 mb-0 d-flex align-items-center gap-2" style="font-size:0.95rem;">
                            <i class="fa-solid fa-check-circle me-2 text-success"></i>
                            Archivo actual cargado: <a href="{{ asset('storage/' . $asistenciaActual->asistencia_path) }}" target="_blank" class="fw-semibold text-success" style="text-decoration:underline;">{{ basename($asistenciaActual->asistencia_path) }}</a>
                            <button type="button" class="btn btn-sm btn-danger ms-3" id="btnBorrarAsistencia">
                                <i class="fa-solid fa-trash"></i> Borrar
                            </button>
                            <a href="{{ route('monitoria.asistencia.ver', [
                                'monitor_id' => $asistenciaActual->monitor_id,
                                'anio' => $asistenciaActual->anio,
                                'mes' => $asistenciaActual->mes
                            ]) }}" class="btn btn-sm btn-primary ms-2" target="_blank">
                                <i class="fa-solid fa-eye"></i> Ver asistencia
                            </a>
                        </div>
                    @endif
                @else
                    {{-- Si no existe asistencia, no mostrar nada relacionado --}}
                @endif
                <form id="formCargaAsistencia" action="{{ route('monitoria.asistencia.subir') }}" method="POST" enctype="multipart/form-data" style="display:none;">
                    @csrf
                    <input type="hidden" name="monitor_id" value="{{ $monitor->id ?? ($monitoria->monitor->id ?? '') }}">
                    <input type="hidden" name="mes" value="{{ now()->month }}">
                    <input type="hidden" name="anio" value="{{ $anioActual ?? date('Y') }}">
                    <div class="input-group mb-2">
                        <input type="file" name="archivo_asistencia" class="form-control" accept="application/pdf,image/*" required>
                        <button type="submit" class="btn btn-success"><i class="fa-solid fa-upload"></i> Subir</button>
                    </div>
                    <small class="text-muted">Formatos permitidos: PDF, imágenes. Tamaño máximo: 5MB.</small>
                </form>
            </div>
            <script>
                document.getElementById('btnMostrarCargaAsistencia')?.addEventListener('click', function() {
                    document.getElementById('formCargaAsistencia').style.display = 'block';
                    this.style.display = 'none';
                });
                document.getElementById('formCargaAsistencia')?.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(async res => {
                        let data;
                        try {
                            data = await res.json();
                        } catch (err) {
                            if (res.ok) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Archivo subido!',
                                    text: 'La asistencia mensual fue cargada correctamente.'
                                }).then(() => window.location.reload());
                                return;
                            } else {
                                const text = await res.text();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    html: '<div style="max-height:200px;overflow:auto;font-size:13px;text-align:left">' + text + '</div>'
                                });
                                throw new Error('Respuesta no JSON');
                            }
                        }
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Archivo subido!',
                                text: 'La asistencia mensual fue cargada correctamente.'
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo subir el archivo.'
                            });
                        }
                    })
                    .catch(() => {});
                });
                document.getElementById('btnBorrarAsistencia')?.addEventListener('click', function() {
                    Swal.fire({
                        icon: 'warning',
                        title: '¿Eliminar archivo?',
                        text: '¿Seguro que deseas borrar el archivo de asistencia mensual?',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, borrar',
                        cancelButtonText: 'Cancelar'
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch("{{ route('monitoria.asistencia.borrar', ['monitor_id' => $monitor->id ?? ($monitoria->monitor->id ?? ''), 'anio' => $anioActual ?? date('Y'), 'mes' => now()->month]) }}", {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Archivo eliminado',
                                        text: 'El archivo de asistencia fue borrado correctamente.'
                                    }).then(() => window.location.reload());
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'No se pudo borrar el archivo.'
                                    });
                                }
                            })
                            .catch(() => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'No se pudo borrar el archivo.'
                                });
                            });
                        }
                    });
                });
            </script>
            @endif
        </div>
    </div>

    @if($esEncargado)
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <i class="fa-solid fa-signature fa-2x text-primary me-2"></i>
                <h5 class="fw-semibold text-primary mb-0">Firma Digital del Encargado</h5>
            </div>
            <div class="alert alert-info d-flex align-items-center mb-4" style="background:rgba(13,110,253,0.08);border-left:5px solid #0d6efd;">
                <i class="fa-solid fa-circle-info me-2"></i>
                <div>
                    <strong>Recomendación:</strong> Sube una imagen de tu firma <b>sin fondo</b> (preferiblemente PNG o SVG transparente) para que se vea profesional en el PDF.
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subir firma</label>
                        <input type="file" class="form-control border-primary" id="firmaInput" accept="image/*">
                        <small class="text-muted">Formatos recomendados: PNG, SVG. Tamaño máximo sugerido: 1MB.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tamaño de la firma</label>
                        <input type="range" class="form-range" id="sizeSlider" min="30" max="100" value="70">
                        <div class="d-flex justify-content-between">
                            <small>30%</small>
                            <small id="sizeValue">70%</small>
                            <small>100%</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Posición vertical</label>
                        <input type="range" class="form-range" id="posSlider" min="-30" max="30" value="-15">
                        <div class="d-flex justify-content-between">
                            <small>-30px</small>
                            <small id="posValue">-15px</small>
                            <small>30px</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 bg-light shadow-sm">
                        <h6 class="text-center mb-3 text-secondary">Vista previa</h6>
                        <div style="width:300px;margin:auto;position:relative;height:80px;background:repeating-linear-gradient(135deg,#f8f9fa,#f8f9fa 10px,#e9ecef 10px,#e9ecef 20px);border-radius:8px;">
                            <div id="firmaPreview" style="width:70%;max-width:210px;max-height:80px;object-fit:contain;position:absolute;left:50%;bottom:-15px;transform:translateX(-50%);"></div>
                            <div style="width:100%;border-bottom:1.5px solid #222;height:0;position:absolute;left:0;bottom:0;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        La firma digital solo puede ser agregada por el encargado de la monitoría.
    </div>
    @endif

    {{-- Botón para abrir el modal de desempeño --}}
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalDesempeno">
        Evaluar Desempeño
    </button>
    {{-- Botón para ver PDF, siempre visible pero solo habilitado si hay ambas firmas --}}
    <a href="{{ route('monitoria.desempeno.pdf', ['monitor_id' => $monitor->id]) }}"
       class="btn btn-secondary mb-3"
       id="btnVerPDFDesempeno"
       @if(!isset($desempeno) || !$desempeno->firma_evaluador || !$desempeno->firma_evaluado) style="pointer-events:none;opacity:0.5;" title="Faltan firmas para generar PDF" @endif
       target="_blank">
        <i class="fa-solid fa-file-pdf"></i> Visualizar PDF
    </a>

    <!-- Modal Evaluación de Desempeño -->
    <div class="modal fade" id="modalDesempeno" tabindex="-1" aria-labelledby="modalDesempenoLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalDesempenoLabel">Evaluación de Desempeño del Monitor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            @include('monitoria.form_desempeno_monitor', ['esEncargado' => $esEncargado, 'esMonitor' => $esMonitor, 'monitor' => $monitor, 'desempeno' => $desempeno ?? null])
          </div>
        </div>
      </div>
    </div>

    <script>
    // Abrir PDF de desempeño
        // document.getElementById('btnVerPDFDesempeno')?.addEventListener('click', function() {
        //     const url = '/monitoria/desempeno/pdf/{{ $monitor->id }}';
        //     window.open(url, '_blank');
        // });
    </script>
</div>



<!-- Modal para mostrar el PDF -->
<div class="modal fade" id="modalPDF" tabindex="-1" aria-labelledby="modalPDFLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" style="max-width:1000px;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalPDFLabel">Seguimiento Monitoría - PDF</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="padding: 20px; background: #f8f9fa;">

        <div style="border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; background: #fff;">
          <iframe id="iframePDF" src="" style="width:100%;height:70vh;min-height:500px;max-height:70vh;border:none;display:block;"></iframe>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para mostrar el PDF de asistencia -->
<div class="modal fade" id="modalAsistenciaPDF" tabindex="-1" aria-labelledby="modalAsistenciaPDFLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" style="max-width:1000px;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalAsistenciaPDFLabel">Archivo de Asistencia Mensual</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="padding: 20px; background: #f8f9fa;">
        <div style="border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; background: #fff;">
          <iframe id="iframeAsistenciaPDF" src="" style="width:100%;height:70vh;min-height:500px;max-height:70vh;border:none;display:block;"></iframe>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
    const esEncargado = @json($esEncargado);
    const esMonitor = @json($esMonitor);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monitorId = document.getElementById('monitor_id').value;
        if (monitorId) {
            // cargarActividadesPrevias(monitorId); // Eliminado
        }
    });

    // Arreglo para almacenar las actividades previas cargadas
    let actividadesPrevias = [];

    // Función para cargar actividades previas
    function cargarActividadesPrevias(monitorId) {
    // fetch(`/api/actividades/${monitorId}`) // Eliminado
    //     .then(response => response.json()) // Eliminado
    //     .then(data => { // Eliminado
    //         const actividadesBody = document.getElementById('actividadesBody'); // Eliminado
    //         actividadesBody.innerHTML = ''; // Eliminado

    //         const actividadesOrdenadas = data.actividades.sort((a, b) => { // Eliminado
    //             const fechaA = new Date(a.fecha_monitoria); // Eliminado
    //             const fechaB = new Date(b.fecha_monitoria); // Eliminado
    //             return fechaA - fechaB; // Eliminado
    //         }); // Eliminado

    //         actividadesOrdenadas.forEach(actividad => { // Eliminado
    //             const newRow = actividadesBody.insertRow(); // Eliminado
    //             newRow.setAttribute('data-actividad-id', actividad.id); // Eliminado

    //             // --- Observación (según permisos) --- // Eliminado
    //             let observacionHtml = ''; // Eliminado
    //             if (esEncargado) { // Eliminado
    //                 observacionHtml = ` // Eliminado
    //                     <div class="input-group"> // Eliminado
    //                         <textarea class="form-control border-0 bg-warning-subtle rounded-3" rows="2" // Eliminado
    //                             id="observacion-${actividad.id}" // Eliminado
    //                             placeholder="Observación para el monitor...">${actividad.observacion_encargado ?? ''}</textarea> // Eliminado
    //                         <button type="button" class="btn btn-outline-primary guardar-observacion-btn" // Eliminado
    //                             data-actividad-id="${actividad.id}"> // Eliminado
    //                             <i class="fa-solid fa-floppy-disk"></i> // Eliminado
    //                         </button> // Eliminado
    //                     </div> // Eliminado
    //                 `; // Eliminado
    //             } else if (esMonitor && actividad.observacion_encargado) { // Eliminado
    //                 observacionHtml = ` // Eliminado
    //                     <div class="alert alert-warning py-2 px-3 mb-0 d-flex align-items-center gap-2" style="background:rgba(255,193,7,0.13);border-left:4px solid #ffc107;"> // Eliminado
    //                         <i class="fa-solid fa-comment-dots me-2 text-warning"></i> // Eliminado
    //                         <span class="fw-semibold text-dark">Observación:</span> // Eliminado
    //                         <span class="text-dark">${actividad.observacion_encargado}</span> // Eliminado
    //                     </div> // Eliminado
    //                 `; // Eliminado
    //             } // Eliminado

    //             // --- Botón eliminar solo para monitor --- // Eliminado
    //             let eliminarBtn = ''; // Eliminado
    //             if (esMonitor) { // Eliminado
    //                 eliminarBtn = `<button type="button" class="btn btn-danger btn-sm rounded-circle" title="Eliminar" onclick="eliminarFila(this)"><i class="fa-solid fa-trash"></i></button>`; // Eliminado
    //             } // Eliminado

    //             // --- Fila completa --- // Eliminado
    //             newRow.innerHTML = ` // Eliminado
    //                 <td> // Eliminado
    //                     <input type="date" name="fecha_monitoria[]" class="form-control border-0 bg-light rounded-3" value="${actividad.fecha_monitoria}" required ${esEncargado ? 'readonly disabled' : ''}> // Eliminado
    //                     ${esMonitor ? '<button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="registrarFechaHoy(this)">Hoy</button>' : ''} // Eliminado
    //                 </td> // Eliminado
    //                 <td><input type="time" name="hora_ingreso[]" class="form-control border-0 bg-light rounded-3" value="${actividad.hora_ingreso}" required ${esEncargado ? 'readonly disabled' : ''}></td> // Eliminado
    //                 <td><input type="time" name="hora_salida[]" class="form-control border-0 bg-light rounded-3" value="${actividad.hora_salida}" required ${esEncargado ? 'readonly disabled' : ''}></td> // Eliminado
    //                 <td><input type="text" name="total_horas[]" class="form-control border-0 bg-light rounded-3 text-center fw-semibold" value="${actividad.total_horas}" readonly></td> // Eliminado
    //                 <td><textarea name="actividad_realizada[]" class="form-control border-0 bg-light rounded-3" rows="2" required ${esEncargado ? 'readonly disabled' : ''}>${actividad.actividad_realizada}</textarea></td> // Eliminado
    //                 <td>${observacionHtml}</td> // Eliminado
    //                 <td>${eliminarBtn}</td> // Eliminado
    //             `; // Eliminado
    //         }); // Eliminado

    //         calcularTotalHorasMensuales(); // Eliminado
    //     }) // Eliminado
    //     .catch(error => console.error('Error al cargar actividades previas:', error)); // Eliminado
    }

    // Agregar nueva fila de actividad
    function agregarFila() {
        const table = document.getElementById('actividadesTable').getElementsByTagName('tbody')[0];
        const newRow = table.insertRow();
        newRow.innerHTML = `
            <td>
                <input type="date" name="fecha_monitoria[]" class="form-control border-0 bg-light rounded-3" required @if($esEncargado) readonly disabled @endif>
                @if($esMonitor)
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="registrarFechaHoy(this)">Hoy</button>
                @endif
            </td>
            <td><input type="time" name="hora_ingreso[]" class="form-control border-0 bg-light rounded-3" required @if($esEncargado) readonly disabled @endif></td>
            <td><input type="time" name="hora_salida[]" class="form-control border-0 bg-light rounded-3" required @if($esEncargado) readonly disabled @endif></td>
            <td><input type="text" name="total_horas[]" class="form-control border-0 bg-light rounded-3 text-center fw-semibold" readonly></td>
            <td><textarea name="actividad_realizada[]" class="form-control border-0 bg-light rounded-3" rows="2" required @if($esEncargado) readonly disabled @endif></textarea></td>
            <td>
                @if($esEncargado)
                <div class="input-group">
                    <textarea class="form-control border-0 bg-warning-subtle rounded-3" rows="2"
                        id="observacion-nueva"
                        placeholder="Observación para el monitor..."></textarea>
                    <button type="button" class="btn btn-outline-primary guardar-observacion-btn"
                        data-actividad-id="nueva">
                        <i class="fa-solid fa-floppy-disk"></i>
                    </button>
                </div>
                @endif
            </td>
            <td>
                @if($esMonitor)
                <button type="button" class="btn btn-danger btn-sm rounded-circle" title="Eliminar" onclick="eliminarFila(this)"><i class="fa-solid fa-trash"></i></button>
                @endif
            </td>
        `;
    }

    // Función para registrar la fecha de hoy
    function registrarFechaHoy(button) {
        const fechaInput = button.previousElementSibling;
        const hoy = new Date().toISOString().split('T')[0];
        fechaInput.value = hoy;
    }

    // Eliminar una fila de actividad
    function eliminarFila(button) {
        const fila = button.closest('tr'); // Obtener la fila asociada al botón
        const actividadId = fila.getAttribute('data-actividad-id'); // Obtener el ID de la actividad

        if (!actividadId) {
            // Si no tiene id, es una fila nueva generada por JS, simplemente la quitamos
            fila.remove();
            return;
        }

        // Mostrar el modal de confirmación con SweetAlert
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción eliminará la actividad de forma permanente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(result => {
            if (result.isConfirmed) {
                // Realizar la solicitud para eliminar la actividad en la base de datos
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch(`/api/actividades/eliminar/${actividadId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken, // Agregar el token CSRF al encabezado
                    },
                })
                    .then(response => {
                        if (response.ok) {
                            // Eliminar la fila del DOM
                            fila.remove();
                            calcularTotalHorasMensuales(); // Recalcular las horas totales

                            // Mostrar alerta de éxito
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: 'La actividad ha sido eliminada exitosamente.',
                            });
                        } else {
                            // Mostrar alerta de error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar la actividad en la base de datos.',
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error al realizar la solicitud de eliminación:', error);

                        // Mostrar alerta de error en caso de fallo de la solicitud
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un problema con la solicitud. Inténtalo de nuevo más tarde.',
                        });
                    });
            }
        });
    }

    // Calcular las horas totales automáticamente cuando se selecciona la hora de salida
    document.addEventListener('input', function(event) {
        if (event.target.name === 'hora_salida[]' || event.target.name === 'hora_ingreso[]') {
            const row = event.target.closest('tr');
            const horaIngreso = row.querySelector('input[name="hora_ingreso[]"]').value;
            const horaSalida = row.querySelector('input[name="hora_salida[]"]').value;
            const totalHorasInput = row.querySelector('input[name="total_horas[]"]');

            if (horaIngreso && horaSalida) {
                const totalHoras = calcularHoras(horaIngreso, horaSalida);
                totalHorasInput.value = totalHoras;
                calcularTotalHorasMensuales();
            }
        }
    });

    // Calcular el total de horas entre ingreso y salida
    function calcularHoras(horaIngreso, horaSalida) {
        const [horaIng, minIng] = horaIngreso.split(':').map(Number);
        const [horaSal, minSal] = horaSalida.split(':').map(Number);
        let totalMinutos = (horaSal * 60 + minSal) - (horaIng * 60 + minIng);
        if (totalMinutos < 0) totalMinutos += 24 * 60; // Ajuste para horarios de madrugada
        const horas = Math.floor(totalMinutos / 60);
        const minutos = totalMinutos % 60;
        return `${horas}:${minutos.toString().padStart(2, '0')}`;
    }

    // Calcular el total de horas mensuales
    function calcularTotalHorasMensuales() {
        const totalHorasInputs = document.querySelectorAll('input[name="total_horas[]"]');
        let totalMinutosMensuales = 0;
        totalHorasInputs.forEach(input => {
            const [horas, minutos] = input.value.split(':').map(Number);
            if (!isNaN(horas) && !isNaN(minutos)) {
                totalMinutosMensuales += horas * 60 + minutos;
            }
        });
        const horasMensuales = Math.floor(totalMinutosMensuales / 60);
        const minutosMensuales = totalMinutosMensuales % 60;
        document.getElementById('totalHorasMensuales').value = `${horasMensuales}:${minutosMensuales.toString().padStart(2, '0')}`;
        // Barra de progreso
        const metaHoras = {{ $metaHoras }};
        let porcentaje = metaHoras > 0 ? Math.min(100, Math.round((horasMensuales / metaHoras) * 100)) : 0;
        const barra = document.getElementById('barraProgreso');
        barra.style.width = porcentaje + '%';
        barra.textContent = porcentaje + '%';
        barra.classList.toggle('bg-success', porcentaje >= 100);
        barra.classList.toggle('bg-info', porcentaje < 100);
        // Mostrar/ocultar botón PDF
        document.getElementById('btnGenerarPDF').style.display = (porcentaje >= 100) ? 'inline-block' : 'none';
        // Mostrar/ocultar botón asistencia
        const btnAsistencia = document.getElementById('btnSubirAsistencia');
        if (btnAsistencia) {
            btnAsistencia.style.display = (porcentaje >= 100) ? 'inline-block' : 'none';
        }
    }

    // Evento para abrir el input de archivo al hacer click en el botón
    document.addEventListener('DOMContentLoaded', function() {
        const btnAsistencia = document.getElementById('btnSubirAsistencia');
        const inputAsistencia = document.getElementById('inputAsistencia');
        if (btnAsistencia && inputAsistencia) {
            btnAsistencia.addEventListener('click', function() {
                inputAsistencia.click();
            });
            inputAsistencia.addEventListener('change', function() {
                if (inputAsistencia.files.length > 0) {
                    document.getElementById('formAsistencia').submit();
                }
            });
        }
    });

    // Llama a calcularTotalHorasMensuales al cargar
    document.addEventListener('DOMContentLoaded', function() {
        calcularTotalHorasMensuales();
    });

    document.addEventListener('DOMContentLoaded', function () {
        const btnPDF = document.getElementById('btnGenerarPDF');
        if (btnPDF) {
            btnPDF.addEventListener('click', function () {
                const monitorId = document.getElementById('monitor_id').value;
                const mes = (new Date()).getMonth() + 1;
                const firma = encodeURIComponent(localStorage.getItem('firmaDigital') || '');
                const firmaSize = encodeURIComponent(localStorage.getItem('firmaSize') || 70);
                const firmaPos = encodeURIComponent(localStorage.getItem('firmaPos') || -15);
                const url = `/monitoria/seguimiento/pdf/${monitorId}/${mes}?firmaDigitalBase64=${firma}&firmaSize=${firmaSize}&firmaPos=${firmaPos}`;
                document.getElementById('iframePDF').src = url;
                const modal = new bootstrap.Modal(document.getElementById('modalPDF'));
                modal.show();
            });
        }
    });

    // Variables para la firma
    let firmaDigitalBase64 = null;
    const sizeSlider = document.getElementById('sizeSlider');
    const posSlider = document.getElementById('posSlider');
    const sizeValue = document.getElementById('sizeValue');
    const posValue = document.getElementById('posValue');
    const firmaPreview = document.getElementById('firmaPreview');

    // Precargar valores de la base de datos si existen
    @if(isset($actividades) && $actividades->first() && $actividades->first()->firma_digital)
        firmaDigitalBase64 = @json($actividades->first()->firma_digital);
        if (sizeSlider) sizeSlider.value = @json($actividades->first()->firma_size ?? 70);
        if (sizeValue && sizeSlider) sizeValue.textContent = sizeSlider.value + '%';
        if (posSlider) posSlider.value = @json($actividades->first()->firma_pos ?? -15);
        if (posValue && posSlider) posValue.textContent = posSlider.value + 'px';
        // Guardar en localStorage para mantener consistencia
        localStorage.setItem('firmaDigital', firmaDigitalBase64);
        if (sizeSlider) localStorage.setItem('firmaSize', sizeSlider.value);
        if (posSlider) localStorage.setItem('firmaPos', posSlider.value);
        if (typeof renderFirmaPreview === 'function') renderFirmaPreview(); // Solo aquí se aplica la corrección visual
    @endif

    // Manejar la subida de la firma
    const firmaInput = document.getElementById('firmaInput');
    if (firmaInput) {
        firmaInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    firmaDigitalBase64 = e.target.result;
                    localStorage.setItem('firmaDigital', firmaDigitalBase64);
                    if (firmaPreview) firmaPreview.innerHTML = `<img src="${firmaDigitalBase64}" alt="Firma Digital" style="width:100%;height:100%;object-fit:contain;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Actualizar vista previa al cambiar los sliders
    function renderFirmaPreview() {
        if (firmaDigitalBase64 && sizeSlider && posSlider && firmaPreview) {
            // Aplica el factor de corrección de 14px para igualar la vista previa al PDF
            let correccion = 14;
            let bottom = parseInt(posSlider.value, 10) + correccion;
            firmaPreview.innerHTML = `<img src="${firmaDigitalBase64}" alt="Firma Digital" style="width:${sizeSlider.value}%;max-width:210px;max-height:80px;object-fit:contain;position:absolute;left:50%;bottom:${bottom}px;transform:translateX(-50%);">`;
        }
    }

    if (sizeSlider) {
        sizeSlider.addEventListener('input', () => {
            localStorage.setItem('firmaSize', sizeSlider.value);
            if (sizeValue) sizeValue.textContent = sizeSlider.value + '%';
            renderFirmaPreview();
        });
    }

    if (posSlider) {
        posSlider.addEventListener('input', () => {
            localStorage.setItem('firmaPos', posSlider.value);
            if (posValue) posValue.textContent = posSlider.value + 'px';
            renderFirmaPreview();
        });
    }

    // Modificar el envío del formulario para incluir la firma
    document.getElementById('seguimientoForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir el envío por defecto

        // Habilitar temporalmente todos los campos deshabilitados para que se envíen
        const disabledFields = this.querySelectorAll('input:disabled, textarea:disabled');
        disabledFields.forEach(field => field.removeAttribute('disabled'));

        // Verificar que todos los campos requeridos estén presentes
        const fechas = document.querySelectorAll('input[name="fecha_monitoria[]"]');
        const horasIngreso = document.querySelectorAll('input[name="hora_ingreso[]"]');
        const horasSalida = document.querySelectorAll('input[name="hora_salida[]"]');
        const actividades = document.querySelectorAll('textarea[name="actividad_realizada[]"]');

        // Validar que todos los campos tengan valor y existen
        let camposValidos = true;
        fechas.forEach((fecha, index) => {
            if (!fecha || !horasIngreso[index] || !horasSalida[index] || !actividades[index] ||
                !fecha.value || !horasIngreso[index].value || !horasSalida[index].value || !actividades[index].value) {
                camposValidos = false;
            }
        });

        if (!camposValidos) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor, complete todos los campos requeridos.',
            });
            // Restaurar los campos a deshabilitado
            disabledFields.forEach(field => field.setAttribute('disabled', true));
            return;
        }

        // Si no hay firma en memoria, intenta recuperarla de localStorage
        if (!firmaDigitalBase64) {
            firmaDigitalBase64 = localStorage.getItem('firmaDigital') || '';
        }

        // Debug: Mostrar en consola lo que se va a enviar
        console.log('Datos a enviar:', {
            fechas: Array.from(fechas).map(f => f ? f.value : ''),
            horasIngreso: Array.from(horasIngreso).map(h => h ? h.value : ''),
            horasSalida: Array.from(horasSalida).map(h => h ? h.value : ''),
            actividades: Array.from(actividades).map(a => a ? a.value : ''),
            firma: firmaDigitalBase64 ? 'presente' : 'ausente',
            tamaño: sizeSlider ? sizeSlider.value : 70,
            posición: posSlider ? posSlider.value : -15
        });

        // Si es encargado, asegurarse de que la firma esté presente
        @if($esEncargado)
        if (!firmaDigitalBase64) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor, sube una firma antes de continuar.',
            });
            // Restaurar los campos a deshabilitado
            disabledFields.forEach(field => field.setAttribute('disabled', true));
            return;
        }
        @endif

        // Remover campos existentes si los hay
        const existingFirmaDigital = this.querySelector('input[name="firma_digital"]');
        const existingFirmaSize = this.querySelector('input[name="firma_size"]');
        const existingFirmaPos = this.querySelector('input[name="firma_pos"]');
        
        if (existingFirmaDigital) existingFirmaDigital.remove();
        if (existingFirmaSize) existingFirmaSize.remove();
        if (existingFirmaPos) existingFirmaPos.remove();

        // Crear y agregar los campos ocultos
        const firmaDigitalInput = document.createElement('input');
        firmaDigitalInput.type = 'hidden';
        firmaDigitalInput.name = 'firma_digital';
        firmaDigitalInput.value = firmaDigitalBase64;

        const firmaSizeInput = document.createElement('input');
        firmaSizeInput.type = 'hidden';
        firmaSizeInput.name = 'firma_size';
        firmaSizeInput.value = sizeSlider ? sizeSlider.value : 70;

        const firmaPosInput = document.createElement('input');
        firmaPosInput.type = 'hidden';
        firmaPosInput.name = 'firma_pos';
        firmaPosInput.value = posSlider ? posSlider.value : -15;

        this.appendChild(firmaDigitalInput);
        this.appendChild(firmaSizeInput);
        this.appendChild(firmaPosInput);

        // Verificar que los campos se agregaron correctamente
        console.log('Campos agregados:', {
            'firma_digital': this.querySelector('input[name="firma_digital"]')?.value ? 'presente' : 'ausente',
            'firma_size': this.querySelector('input[name="firma_size"]')?.value,
            'firma_pos': this.querySelector('input[name="firma_pos"]')?.value
        });

        // Enviar el formulario
        this.submit();

        // Restaurar los campos a deshabilitado después de enviar
        setTimeout(() => {
            disabledFields.forEach(field => field.setAttribute('disabled', true));
        }, 100);
    });

    // Delegación de eventos para guardar observación (funciona con elementos dinámicos)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('guardar-observacion-btn') || (e.target.closest && e.target.closest('.guardar-observacion-btn'))) {
            const btn = e.target.closest('.guardar-observacion-btn');
            const actividadId = btn.getAttribute('data-actividad-id');
            const textarea = document.getElementById('observacion-' + actividadId);
            const observacion = textarea ? textarea.value : '';
            if (!actividadId || !textarea) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo identificar la actividad o el campo de observación.',
                });
                return;
            }
            btn.disabled = true;
            fetch(`/seguimiento/guardar-observacion/${actividadId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ observacion_encargado: observacion })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: 'La observación fue guardada correctamente.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo guardar la observación.'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo guardar la observación.'
                });
            })
            .finally(() => {
                btn.disabled = false;
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const btnVerAsistencia = document.getElementById('btnVerAsistenciaActual');
        if (btnVerAsistencia) {
            btnVerAsistencia.addEventListener('click', function () {
                // Obtener datos desde atributos data-* para mayor robustez
                const monitorId = btnVerAsistencia.getAttribute('data-monitor-id');
                const anio = btnVerAsistencia.getAttribute('data-anio');
                const mes = btnVerAsistencia.getAttribute('data-mes');
                if (monitorId && anio && mes) {
                    // Construir la URL solo si existen los datos
                    const url = `/monitoria/asistencia/ver/${monitorId}/${anio}/${mes}`;
                    document.getElementById('iframeAsistenciaPDF').src = url;
                    const modal = new bootstrap.Modal(document.getElementById('modalAsistenciaPDF'));
                    modal.show();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivo no disponible',
                        text: 'No existe archivo de asistencia para mostrar.'
                    });
                }
            });
        }
    });
</script>

<script>
    function cambiarMonitor() {
        const monitorId = document.getElementById('monitorSelector').value;
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('monitor_id', monitorId);
        window.location.href = currentUrl.toString();
    }
</script>
@endsection