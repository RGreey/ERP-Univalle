
<form id="formDesempenoMonitor" action="{{ route('monitoria.desempeno.guardar') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="monitor_id" value="{{ $monitor->id }}">
    <div class="row mb-2">
        <div class="col-md-4">
            <label class="form-label">Período Académico</label>
            <input type="text" name="periodo_academico" class="form-control" required
                value="{{ old('periodo_academico', $desempeno->periodo_academico ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
        <div class="col-md-4">
            <label class="form-label">Código Estudiantil</label>
            <input type="text" name="codigo_estudiantil" class="form-control" required
                value="{{ old('codigo_estudiantil', $desempeno->codigo_estudiantil ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
        <div class="col-md-4">
            <label class="form-label">Programa Académico</label>
            <input type="text" name="programa_academico" class="form-control" required
                value="{{ old('programa_academico', $desempeno->programa_academico ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-6">
            <label class="form-label">Apellidos del Estudiante</label>
            <input type="text" name="apellidos_estudiante" class="form-control" required
                value="{{ old('apellidos_estudiante', $desempeno->apellidos_estudiante ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nombres del Estudiante</label>
            <input type="text" name="nombres_estudiante" class="form-control" required
                value="{{ old('nombres_estudiante', $desempeno->nombres_estudiante ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-6">
            <label class="form-label">Modalidad de la Monitoría</label>
            <input type="text" name="modalidad_monitoria" class="form-control" required
                value="{{ old('modalidad_monitoria', $desempeno->modalidad_monitoria ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
        <div class="col-md-6">
            <label class="form-label">Dependencia</label>
            <input type="text" name="dependencia" class="form-control" required
                value="{{ old('dependencia', $desempeno->dependencia ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
    </div>
    <hr>
    <h5 class="fw-bold text-primary mb-2">Datos del Evaluador</h5>
    <div class="row mb-2">
        <div class="col-md-3">
            <label class="form-label">Identificación</label>
            <input type="text" name="evaluador_identificacion" class="form-control" required
                value="{{ old('evaluador_identificacion', $desempeno->evaluador_identificacion ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
        <div class="col-md-3">
            <label class="form-label">Apellidos</label>
            <input type="text" name="evaluador_apellidos" class="form-control" required
                value="{{ old('evaluador_apellidos', $desempeno->evaluador_apellidos ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
        <div class="col-md-3">
            <label class="form-label">Nombres</label>
            <input type="text" name="evaluador_nombres" class="form-control" required
                value="{{ old('evaluador_nombres', $desempeno->evaluador_nombres ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
        <div class="col-md-3">
            <label class="form-label">Cargo</label>
            <input type="text" name="evaluador_cargo" class="form-control" required
                value="{{ old('evaluador_cargo', $desempeno->evaluador_cargo ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-6">
            <label class="form-label">Dependencia</label>
            <input type="text" name="evaluador_dependencia" class="form-control" required
                value="{{ old('evaluador_dependencia', $desempeno->evaluador_dependencia ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
    </div>
    <hr>
    <h5 class="fw-bold text-primary mb-2">Fechas de la Monitoría</h5>
    <div class="row mb-2">
        <div class="col-md-6">
            <label class="form-label">Fecha de Inicio</label>
            <input type="date" name="fecha_inicio" class="form-control" required
                value="{{ old('fecha_inicio', $desempeno->fecha_inicio ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
        <div class="col-md-6">
            <label class="form-label">Fecha de Fin</label>
            <input type="date" name="fecha_fin" class="form-control" required
                value="{{ old('fecha_fin', $desempeno->fecha_fin ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
    </div>
    <hr>
    <h5 class="fw-bold text-primary mb-2">Factores de Evaluación</h5>
    <table class="table table-bordered align-middle bg-white mb-3">
        <thead class="table-light">
            <tr>
                <th style="width:5%">No.</th>
                <th style="width:60%">Factor</th>
                <th style="width:35%">Calificación (1-5)</th>
            </tr>
        </thead>
        <tbody>
            @php
            $factores = [
                'calidad_trabajo' => 'Calidad del trabajo',
                'sigue_instrucciones' => 'Sigue instrucciones y procedimientos establecidos',
                'responsable_actividad' => 'Es responsable con la actividad asignada',
                'iniciativa' => 'Tiene iniciativa',
                'cumplimiento_horario' => 'Cumplimiento de horario',
                'relaciones_interpersonales' => 'Relaciones interpersonales',
                'cooperacion' => 'Cooperación',
                'atencion_usuario' => 'Atención al usuario',
                'asume_compromisos' => 'Asume los compromisos con la dependencia',
                'maneja_informacion' => 'Maneja información de forma reservada, ética, exclusiva para los fines de la universidad'
            ];
            $i = 1;
            @endphp
            @foreach($factores as $campo => $nombre)
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $nombre }}</td>
                <td>
                    <input type="number" name="{{ $campo }}" class="form-control calificacion-input" min="1" max="5" step="0.1" style="width:70px;"
                        value="{{ old($campo, $desempeno->$campo ?? '') }}" @if($desempeno) readonly @endif>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mb-3">
        <label class="form-label">Sugerencias y comentarios del evaluador</label>
        <textarea name="sugerencias" class="form-control" rows="3" @if($desempeno) readonly @endif>{{ old('sugerencias', $desempeno->sugerencias ?? '') }}</textarea>
    </div>
    <div class="row mb-2">
        <div class="col-md-4">
            <label class="form-label">Fecha de Evaluación</label>
            <input type="date" name="fecha_evaluacion" class="form-control" required
                value="{{ old('fecha_evaluacion', $desempeno->fecha_evaluacion ?? '') }}"
                @if($desempeno) readonly @endif>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-6">
            <label class="form-label">Firma del Evaluador</label>
            @if($desempeno && $desempeno->firma_evaluador)
                <div class="mb-2">
                    <img src="{{ $desempeno->firma_evaluador }}" alt="Firma Encargado" style="max-width:200px;max-height:80px;">
                </div>
            @elseif($esEncargado)
                <input type="file" name="firma_evaluador" class="form-control" accept="image/*">
            @else
                <span class="text-muted">Pendiente de firma por el evaluador.</span>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label">Firma del Monitor</label>
            @if($desempeno && $desempeno->firma_evaluado)
                <div class="mb-2">
                    <img src="{{ $desempeno->firma_evaluado }}" alt="Firma Monitor" style="max-width:200px;max-height:80px;">
                </div>
            @elseif($esMonitor)
                <input type="file" name="firma_evaluado" class="form-control" accept="image/*">
            @else
                <span class="text-muted">Pendiente de firma por el monitor.</span>
            @endif
        </div>
    </div>
    @if(($esEncargado && (!$desempeno || !$desempeno->firma_evaluador)) || ($esMonitor && (!$desempeno || !$desempeno->firma_evaluado)))
    <div class="row mb-2">
        <div class="col-md-12 text-end">
            <span class="fw-bold fs-5 text-success">Puntaje final: <span id="puntajeFinal">0.00</span></span>
        </div>
    </div>
    <div class="mt-3 d-flex justify-content-end">
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Guardar Evaluación</button>
    </div>
    @endif
</form>
@if($desempeno)
    <div class="mt-4">
        <button type="button" class="btn btn-danger" id="btnRehacerEvaluacion" data-desempeno-id="{{ $desempeno->id }}">
            <i class="fa-solid fa-rotate-left"></i> Rehacer evaluación
        </button>
    </div>
@endif
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Solo cálculo de puntaje dinámico
    function calcularPuntaje() {
        let suma = 0, num = 0;
        document.querySelectorAll('.calificacion-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val)) {
                suma += val;
                num++;
            }
        });
        const puntaje = num ? (suma / num) : 0;
        const puntajeFinalElem = document.getElementById('puntajeFinal');
        if (puntajeFinalElem) {
            puntajeFinalElem.textContent = puntaje.toFixed(2);
        }
    }
    document.querySelectorAll('.calificacion-input').forEach(input => {
        input.addEventListener('input', calcularPuntaje);
    });
    calcularPuntaje();

    // Envío AJAX del formulario de desempeño
    const form = document.getElementById('formDesempenoMonitor');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: 'La evaluación de desempeño fue guardada correctamente.'
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo guardar la evaluación.'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo guardar la evaluación.'
                });
            });
        });
    }

    // Borrar evaluación (rehacer)
    const btnRehacer = document.getElementById('btnRehacerEvaluacion');
    if (btnRehacer) {
        btnRehacer.addEventListener('click', function() {
            const desempenoId = btnRehacer.getAttribute('data-desempeno-id');
            console.log('Intentando borrar desempeno_id:', desempenoId);
            Swal.fire({
                icon: 'warning',
                title: '¿Rehacer evaluación?',
                text: 'Esto eliminará la evaluación actual y podrás llenarla de nuevo.',
                showCancelButton: true,
                confirmButtonText: 'Sí, rehacer',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('{{ route('monitoria.desempeno.borrar') }}', {
                        method: 'POST',
                        body: new URLSearchParams({
                            '_token': document.querySelector('meta[name="csrf-token"]').content,
                            'desempeno_id': desempenoId
                        }),
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        console.log('Respuesta del backend:', data);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Evaluación eliminada',
                                text: 'Ahora puedes volver a llenarla.'
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo eliminar la evaluación.'
                            });
                        }
                    })
                    .catch((err) => {
                        console.error('Error en fetch:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo eliminar la evaluación.'
                        });
                    });
                }
            });
        });
    }
});
</script>
<style>
.calificacion-input {
    font-size: 1.2rem;
    font-weight: bold;
    color: #198754;
    background: #e9f7ef;
    border: 1px solid #198754;
    border-radius: 6px;
    text-align: center;
}
</style>
