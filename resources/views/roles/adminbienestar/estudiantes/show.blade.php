@extends('layouts.app')

@section('title','Estudiante')

@section('content')
<style>
    .uv-card { background:#fff; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,.06); padding:18px; }
    .kv-table th { width: 34%; color:#6c757d; font-weight:600; }
</style>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">{{ $user->name }}</h3>
            <div class="text-muted">{{ $user->email }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.estudiantes.index') }}" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="uv-card h-100">
                <h6 class="fw-semibold mb-2">Datos de contacto</h6>
                <table class="table table-sm mb-0 kv-table">
                    <tbody>
                        <tr><th>Nombre</th><td>{{ $user->name }}</td></tr>
                        <tr><th>Correo</th><td>{{ $user->email }}</td></tr>
                        {{-- Si luego guardas teléfono/programa en perfil, muéstralos aquí --}}
                    </tbody>
                </table>
            </div>

            <div class="uv-card mt-3">
                <h6 class="fw-semibold mb-2">Observaciones internas</h6>

                <form method="POST" action="{{ route('admin.estudiantes.observaciones.store', $user->id) }}" class="mb-2">
                    @csrf
                    <textarea name="texto" rows="3" class="form-control" placeholder="Agregar nota interna…" required></textarea>
                    <div class="text-end mt-2">
                        <button class="btn btn-sm btn-outline-dark">Guardar nota</button>
                    </div>
                </form>

                @forelse($observaciones as $obs)
                    <div class="border rounded p-2 mb-2">
                        <div class="small text-muted">
                            Por: {{ $obs->admin?->name ?? '—' }} · {{ $obs->created_at->format('Y-m-d H:i') }}
                        </div>
                        <div>{{ $obs->texto }}</div>
                        <form method="POST" action="{{ route('admin.estudiantes.observaciones.destroy', [$user->id, $obs->id]) }}" class="mt-2">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </div>
                @empty
                    <div class="text-muted">Sin observaciones.</div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-8">
            <div class="uv-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-semibold mb-0">Historial de postulaciones</h6>
                </div>

                @if($postulaciones->isEmpty())
                    <div class="text-muted">No hay postulaciones registradas.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Convocatoria</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Sede</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($postulaciones as $p)
                                    <tr>
                                        <td>{{ $p->convocatoria?->nombre }}</td>
                                        <td>{{ $p->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($p->estado==='beneficiario') bg-success
                                                @elseif($p->estado==='evaluada') bg-primary
                                                @elseif($p->estado==='rechazada') bg-danger
                                                @elseif($p->estado==='anulada') bg-secondary
                                                @else bg-warning text-dark
                                                @endif">
                                                {{ ucfirst($p->estado) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($p->prioridad_final)
                                                <span class="badge {{ $p->prioridad_final <= 3 ? 'bg-success' : ($p->prioridad_final <= 6 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                    {{ $p->prioridad_final }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $p->sede }}</td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a class="btn btn-sm btn-outline-dark"
                                                   href="{{ route('admin.convocatorias-subsidio.postulaciones.show', $p->id) }}">
                                                    Ver
                                                </a>

                                                {{-- Cambiar estado (usa los endpoints existentes) --}}
                                                <form method="POST" action="{{ route('admin.convocatorias-subsidio.postulaciones.estado', $p->id) }}">
                                                    @csrf
                                                    <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        @foreach(['enviada','evaluada','beneficiario','rechazada','anulada'] as $st)
                                                            <option value="{{ $st }}" @selected($p->estado===$st)>{{ ucfirst($st) }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>

                                                {{-- Prioridad manual (usa el endpoint existente) --}}
                                                <form method="POST" action="{{ route('admin.convocatorias-subsidio.postulaciones.prioridad-manual', $p->id) }}">
                                                    @csrf
                                                    <select name="prioridad_final" class="form-select form-select-sm">
                                                        @for($i=1;$i<=9;$i++)
                                                            <option value="{{ $i }}" @selected(($p->prioridad_final) == $i)>{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                    <button class="btn btn-sm btn-outline-primary">Guardar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

@push('scripts')
@if (session('success'))
<script>
Swal.fire({ title: '¡Listo!', text: @json(session('success')), icon: 'success', confirmButtonColor: '#cd1f32' });
</script>
@endif
@endpush
@endsection