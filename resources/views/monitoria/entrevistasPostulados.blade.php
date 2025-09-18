@extends('layouts.app')

@section('title', 'Entrevistas de Postulados')
@if(session('error_swal'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: '¡Atención!',
                    text: '{{ session('error_swal') }}',
                    confirmButtonText: 'Entendido'
                });
            });
        </script>
    @endif
@section('content')
<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title">Gestión de Entrevistas de Postulados</h2>
            @if(isset($nombreMonitoria) && $nombreMonitoria)
                <h4 class="mb-0">Monitoría: <span class="text-primary">{{ $nombreMonitoria }}</span></h4>
            @else
                <h4 class="mb-0">Monitorías: <span class="text-primary">Múltiples monitorías</span></h4>
            @endif
            <p class="card-text">
                Aquí puedes agendar entrevistas, registrar el concepto y aprobar o rechazar postulados para tus monitorías.
            </p>
        </div>
    </div>

    {{-- Mensaje informativo sobre el período de la convocatoria --}}
    @if(isset($estadoPeriodo) && isset($convocatoriaActiva))
        @if($estadoPeriodo === 'entrevistas')
            <div class="alert alert-warning border-warning shadow-sm mb-4" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-left: 6px solid #f39c12;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-check fa-2x text-warning me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-2"><i class="fas fa-clock me-2"></i>Período de Entrevistas Activo</h5>
                        <p class="mb-2">
                            <strong>📅 Fecha límite para entrevistas:</strong> 
                            <span class="badge bg-warning text-dark">{{ \Carbon\Carbon::parse($convocatoriaActiva->fechaEntrevistas)->format('d/m/Y') }}</span>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Es momento de:</strong> Programar entrevistas, evaluar candidatos y tomar decisiones finales de aprobación o rechazo.
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>
                            Recuerda que después de la fecha límite de entrevistas, este módulo ya no estará disponible.
                        </small>
                    </div>
                </div>
            </div>
        @elseif($estadoPeriodo === 'abierta')
            <div class="alert alert-info border-info shadow-sm mb-4" style="background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); border-left: 6px solid #17a2b8;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-door-open fa-2x text-info me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-2"><i class="fas fa-hourglass-start me-2"></i>Convocatoria Abierta</h5>
                        <p class="mb-2">
                            <strong>📅 Cierre de postulaciones:</strong> 
                            <span class="badge bg-info">{{ \Carbon\Carbon::parse($convocatoriaActiva->fechaCierre)->format('d/m/Y') }}</span>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-info-circle me-2"></i>
                            Los estudiantes aún pueden postularse. Las entrevistas comenzarán después del cierre.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($postulados->isEmpty())
        <div class="alert alert-info">No hay postulados pendientes de entrevista para tus monitorías.</div>
    @else
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Estudiante</th>
                    <th>Correo</th>
                    <th>Monitoría</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($postulados as $postulado)
                <tr @if($postulado->estado == 'aprobado') style="background: #e6ffe6;" @endif>
                    <td>
                        {{ $postulado->user_name ?? 'N/A' }}
                        @if($postulado->estado == 'aprobado')
                            <span class="badge bg-success ms-2">Monitor seleccionado</span>
                        @endif
                        @if($postulado->entrevista_fecha)
                            <span class="badge bg-warning text-dark ms-2">Entrevista programada</span>
                        @endif
                    </td>
                    <td>{{ $postulado->user_email ?? 'N/A' }}</td>
                    <td>{{ $postulado->monitoria_nombre ?? 'N/A' }}</td>
                    <td>
                        <button type="button" class="btn {{ $postulado->entrevista_fecha ? 'btn-warning' : 'btn-info' }} btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalEntrevista{{ $postulado->id }}">
                            <i class="fa-solid fa-calendar-check"></i> {{ $postulado->entrevista_fecha ? 'Reprogramar' : 'Entrevista' }}
                        </button>
                        <button type="button" class="btn btn-primary btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalDecidir{{ $postulado->id }}" @if($postulado->estado == 'aprobado') disabled @endif>
                            <i class="fa-solid fa-gavel"></i> Decidir
                        </button>
                        @php $doc = $postulado->documentos->first(); @endphp
                        @if($doc)
                            <button type="button" class="btn btn-secondary btn-sm ver-pdf-btn" data-bs-toggle="modal" data-bs-target="#pdfModal" data-url="{{ Storage::url($doc->url) }}">
                                <i class="fa-regular fa-file-pdf"></i> PDF
                            </button>
                        @endif
                        @if(in_array($postulado->estado, ['aprobado', 'rechazado']))
                            <form action="{{ route('postulados.revertirDecision', $postulado->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm mb-1">
                                    <i class="fa-solid fa-rotate-left"></i> Revertir decisión
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>

                <!-- Modal Entrevista -->
                <div class="modal fade" id="modalEntrevista{{ $postulado->id }}" tabindex="-1" aria-labelledby="modalEntrevistaLabel{{ $postulado->id }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('postulados.guardarEntrevista', $postulado->id) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalEntrevistaLabel{{ $postulado->id }}">
                                        {{ $postulado->entrevista_fecha ? 'Reprogramar' : 'Programar' }} Entrevista - {{ $postulado->user_name }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    @if($postulado->entrevista_fecha)
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Entrevista actual:</strong> {{ \Carbon\Carbon::parse($postulado->entrevista_fecha)->format('d/m/Y \a \l\a\s g:i A') }}
                                            @if($postulado->entrevista_medio == 'virtual' && $postulado->entrevista_link)
                                                <br><strong>Link:</strong> {{ $postulado->entrevista_link }}
                                            @elseif($postulado->entrevista_medio == 'presencial' && $postulado->entrevista_lugar)
                                                <br><strong>Lugar:</strong> {{ $postulado->entrevista_lugar }}
                                            @endif
                                        </div>
                                    @endif
                                    <div class="mb-2">
                                        <label class="form-label">Fecha y hora</label>
                                        <input type="datetime-local" name="entrevista_fecha" class="form-control" value="{{ $postulado->entrevista_fecha ? \Carbon\Carbon::parse($postulado->entrevista_fecha)->format('Y-m-d\TH:i') : '' }}" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Medio</label>
                                        <select name="entrevista_medio" class="form-select entrevista-medio-select" data-id="{{ $postulado->id }}" required>
                                            <option value="">Seleccione...</option>
                                            <option value="presencial" {{ $postulado->entrevista_medio == 'presencial' ? 'selected' : '' }}>Presencial</option>
                                            <option value="virtual" {{ $postulado->entrevista_medio == 'virtual' ? 'selected' : '' }}>Virtual</option>
                                        </select>
                                    </div>
                                    <div class="mb-2 entrevista-link-div" id="link-div-{{ $postulado->id }}" style="display: {{ $postulado->entrevista_medio == 'virtual' ? 'block' : 'none' }};">
                                        <label class="form-label">Link de la entrevista (si es virtual)</label>
                                        <input type="text" name="entrevista_link" class="form-control" value="{{ $postulado->entrevista_link }}">
                                    </div>
                                    <div class="mb-2 entrevista-lugar-div" id="lugar-div-{{ $postulado->id }}" style="display: {{ $postulado->entrevista_medio == 'presencial' ? 'block' : 'none' }};">
                                        <label class="form-label">Lugar de la entrevista (si es presencial)</label>
                                        <input type="text" name="entrevista_lugar" class="form-control" value="{{ $postulado->entrevista_lugar }}" placeholder="Ej: Sede María Inmaculada, Salón 5">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Concepto de la entrevista</label>
                                        <textarea name="concepto_entrevista" class="form-control" rows="3" required>{{ $postulado->concepto_entrevista }}</textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="submit" class="btn btn-success">{{ $postulado->entrevista_fecha ? 'Actualizar' : 'Guardar' }} Entrevista</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Decidir -->
                <div class="modal fade" id="modalDecidir{{ $postulado->id }}" tabindex="-1" aria-labelledby="modalDecidirLabel{{ $postulado->id }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('postulados.decidirEntrevista', $postulado->id) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalDecidirLabel{{ $postulado->id }}">Decisión - {{ $postulado->user_name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>              
                                </div>
                                <div class="modal-body">
                                    <div class="mb-2">
                                        <strong>Fecha y hora de entrevista:</strong> {{ $postulado->entrevista_fecha ? \Carbon\Carbon::parse($postulado->entrevista_fecha)->format('d/m/Y H:i') : 'No definida' }}<br>
                                        <strong>Medio:</strong> {{ ucfirst($postulado->entrevista_medio) ?? 'No definido' }}<br>
                                        @if($postulado->entrevista_medio == 'virtual')
                                            <strong>Link:</strong> {{ $postulado->entrevista_link ?? 'No definido' }}<br>
                                        @elseif($postulado->entrevista_medio == 'presencial')
                                            <strong>Lugar:</strong> {{ $postulado->entrevista_lugar ?? 'No definido' }}<br>
                                        @endif
                                        <strong>Concepto:</strong> <span class="text-primary">{{ $postulado->concepto_entrevista ?? 'No definido' }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Concepto de la entrevista</label>
                                        <textarea name="concepto_entrevista" class="form-control" rows="3" required>{{ $postulado->concepto_entrevista }}</textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Decisión final</label>
                                        <select name="estado" class="form-select" required>
                                            <option value="aprobado">Aprobar como monitor</option>
                                            <option value="rechazado">Rechazar</option>
                                        </select>
                                    </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Decisión</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @endforeach
            </tbody>
        </table>
    </div>
    <!-- Modal para ver PDF -->
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel">Documento PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" style="height: 80vh;">
                    <iframe id="visorPdf" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.entrevista-medio-select').forEach(function(select) {
            select.addEventListener('change', function() {
                var id = this.getAttribute('data-id');
                var linkDiv = document.getElementById('link-div-' + id);
                var lugarDiv = document.getElementById('lugar-div-' + id);
                if (this.value === 'virtual') {
                    linkDiv.style.display = 'block';
                    lugarDiv.style.display = 'none';
                } else if (this.value === 'presencial') {
                    linkDiv.style.display = 'none';
                    lugarDiv.style.display = 'block';
                } else {
                    linkDiv.style.display = 'none';
                    lugarDiv.style.display = 'none';
                }
            });
        });
        // Mostrar PDF en modal
        document.querySelectorAll('.ver-pdf-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var url = this.getAttribute('data-url');
                document.getElementById('visorPdf').src = url;
            });
        });
    });
</script>
@endpush