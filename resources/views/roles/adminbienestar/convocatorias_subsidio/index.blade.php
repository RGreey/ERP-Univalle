@extends('layouts.app')

@section('title', 'Convocatorias Subsidio')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Convocatorias de Subsidio Alimenticio</h2>
        <a href="{{ route('admin.convocatorias-subsidio.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Nueva convocatoria
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET" action="{{ route('admin.convocatorias-subsidio.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control" placeholder="Buscar por nombre" value="{{ request('q') }}">
        </div>
        <div class="col-md-2">
            <select name="estado" class="form-select" title="Estado calculado por fechas">
                <option value="">Estado (todos)</option>
                <option value="borrador" @selected(request('estado')==='borrador')>Borrador</option>
                <option value="activa" @selected(request('estado')==='activa')>Activa</option>
                <option value="cerrada" @selected(request('estado')==='cerrada')>Cerrada</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="periodo" class="form-select">
                <option value="">Periodo académico (todos)</option>
                @foreach ($periodos as $p)
                    <option value="{{ $p->id }}" @selected((string)request('periodo')===(string)$p->id)>
                        {{ $p->nombre }} ({{ \Carbon\Carbon::parse($p->fechaInicio)->format('Y-m-d') }} - {{ \Carbon\Carbon::parse($p->fechaFin)->format('Y-m-d') }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="vigencia" class="form-select" title="Filtra por fecha de apertura/cierre">
                <option value="">Vigencia (todas)</option>
                <option value="abiertas" @selected(request('vigencia')==='abiertas')>Abiertas hoy</option>
                <option value="cerradas" @selected(request('vigencia')==='cerradas')>Cerradas</option>
                <option value="proximas" @selected(request('vigencia')==='proximas')>Próximas (30 días)</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-secondary w-50" type="submit">Filtrar</button>
            <a class="btn btn-outline-dark w-50" href="{{ route('admin.convocatorias-subsidio.index') }}">Limpiar</a>
        </div>
    </form>

    @if($convocatorias->count() === 0)
        <div class="alert alert-info">No hay convocatorias registradas.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Periodo</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Cupos Caicedonia</th>
                        <th>Cupos Sevilla</th>
                        <th>Estado</th>
                        <th style="width:180px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($convocatorias as $c)
                    <tr>
                        <td>{{ $c->nombre }}</td>
                        <td>{{ optional($c->periodoAcademico)->nombre }}</td>
                        <td>{{ \Carbon\Carbon::parse($c->fecha_apertura)->format('Y-m-d') }}</td>
                        <td>{{ \Carbon\Carbon::parse($c->fecha_cierre)->format('Y-m-d') }}</td>
                        <td>{{ $c->cupos_caicedonia }}</td>
                        <td>{{ $c->cupos_sevilla }}</td>
                        <td>
                            @php $estado = $c->estado_actual; @endphp
                            <span class="badge 
                                @if($estado === 'activa') bg-success 
                                @elseif($estado === 'cerrada') bg-secondary 
                                @else bg-warning text-dark @endif">
                                {{ ucfirst($estado) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.convocatorias-subsidio.edit', $c->id) }}" class="btn btn-sm btn-outline-primary">
                                Editar
                            </a>
                            <form action="{{ route('admin.convocatorias-subsidio.destroy', $c->id) }}" method="POST" class="d-inline form-eliminar">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{ $convocatorias->links() }}
    @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.form-eliminar').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Eliminar convocatoria?',
            html: 'Esta acción <b>no se puede deshacer</b>.<br>Se eliminará la convocatoria seleccionada.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            confirmButtonColor: '#cd1f32',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

@if (session('success'))
Swal.fire({
    title: '¡Listo!',
    text: @json(session('success')),
    icon: 'success',
    confirmButtonColor: '#cd1f32'
});

@endif
</script>
@endpush