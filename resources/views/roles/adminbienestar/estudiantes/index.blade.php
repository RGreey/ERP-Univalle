@extends('layouts.app')

@section('title','Estudiantes (Subsidio)')

@section('content')
<style>
    .uv-card { background:#fff; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,.06); padding:18px; }
    .badge-state { font-size:.85rem; }
    .table thead th { background:#f8f9fa; }
    @media (max-width: 992px){
        .filters .btn, .filters .form-control, .filters .form-select { width: 100%; }
    }
</style>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Estudiantes (Subsidio)</h2>
        <a href="{{ route('admin.convocatorias') }}" class="btn btn-outline-dark btn-sm">Ver convocatorias</a>
    </div>

    <!-- Filtros -->
    <form method="GET" class="row g-2 mb-3 filters">
        <div class="col-lg-5">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre o correo">
        </div>
        <div class="col-lg-3">
            <select name="estado" class="form-select" title="Último estado de postulación">
                <option value="">Último estado (todos)</option>
                @foreach(['beneficiario','evaluada','enviada','rechazada','anulada'] as $st)
                    <option value="{{ $st }}" @selected($estado===$st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 d-grid">
            <button class="btn btn-outline-secondary">Filtrar</button>
        </div>
        <div class="col-lg-2 d-grid">
            <a href="{{ route('admin.estudiantes') }}" class="btn btn-outline-dark">Limpiar</a>
        </div>
    </form>

    @if($ultimas->isEmpty())
        <div class="alert alert-info">No hay estudiantes con postulaciones registradas.</div>
    @else
        <div class="table-responsive uv-card">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:26%">Estudiante</th>
                        <th style="width:22%">Correo</th>
                        <th style="width:16%">Última postulación</th>
                        <th style="width:12%">Estado</th>
                        <th style="width:8%">Prioridad</th>
                        <th class="text-end" style="width:16%">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($ultimas as $row)
                    @php
                        $u = $row->user;
                        $pf = $row->prioridad_final;
                    @endphp
                    <tr>
                        <td>{{ $u?->name ?? '—' }}</td>
                        <td>{{ $u?->email ?? '—' }}</td>
                        <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            <span class="badge badge-state
                                @if($row->estado==='beneficiario') bg-success
                                @elseif($row->estado==='evaluada') bg-primary
                                @elseif($row->estado==='rechazada') bg-danger
                                @elseif($row->estado==='anulada') bg-secondary
                                @else bg-warning text-dark
                                @endif">
                                {{ ucfirst($row->estado) }}
                            </span>
                        </td>
                        <td>
                            @if($pf)
                                <span class="badge {{ $pf <= 3 ? 'bg-success' : ($pf <= 6 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                    {{ $pf }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($u)
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.estudiantes.show', $u->id) }}">
                                    Gestionar
                                </a>
                            @endif
                            {{-- Sin edición aquí: el índice es solo consulta --}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $ultimas->links() }}
        </div>
    @endif
</div>
@endsection     