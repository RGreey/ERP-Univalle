@extends('layouts.app')
@section('title','Standby — Admin')
@section('content')
<div class="container">
  <h3 class="mb-3">Standby por convocatoria</h3>
  <x-admin.volver to="admin.subsidio.admin.dashboard" keep="q,estado" label="Volver" />
  <form class="row g-2 mb-3">
    <div class="col-auto">
      <label class="form-label mb-0 small">Convocatoria</label>
      <select name="convocatoria_id" class="form-select" onchange="this.form.submit()">
        @foreach($convocatorias as $c)
          <option value="{{ $c->id }}" @selected($convId==$c->id)>{{ $c->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-auto">
      <label class="form-label mb-0 small">Activo</label>
      <select name="activo" class="form-select" onchange="this.form.submit()">
        <option value="">Activo/Todos</option>
        <option value="1" @selected(($filtros['activo']??'')==='1')>Activos</option>
        <option value="0" @selected(($filtros['activo']??'')==='0')>Inactivos</option>
      </select>
    </div>

    <div class="col-auto">
      <label class="form-label mb-0 small d-block">&nbsp;</label>
      <div class="form-check mt-1">
        <input class="form-check-input" type="checkbox" name="externos" value="1" id="externos" @checked(!empty($filtros['externos'])) onchange="this.form.submit()">
        <label class="form-check-label" for="externos">Solo externos</label>
      </div>
    </div>

    <div class="col-auto">
      <label for="f-dia" class="form-label mb-0 small">Día</label>
      <select name="dia" id="f-dia" class="form-select">
        <option value="">Todos</option>
        @foreach(['lun'=>'Lunes','mar'=>'Martes','mie'=>'Miércoles','jue'=>'Jueves','vie'=>'Viernes'] as $k=>$v)
          <option value="{{ $k }}" @selected(($filtros['dia']??'')===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-auto">
      <label for="f-sede" class="form-label mb-0 small">Sede</label>
      <select name="sede" id="f-sede" class="form-select">
        <option value="">Todos</option>
        @foreach(['caicedonia'=>'Caicedonia','sevilla'=>'Sevilla','ninguno'=>'Ninguno'] as $k=>$v)
          <option value="{{ $k }}" @selected(($filtros['sede']??'')===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-auto">
      <label class="form-label mb-0 small">Buscar</label>
      <input type="text" name="q" class="form-control" placeholder="Buscar usuario" value="{{ $filtros['q'] ?? '' }}">
    </div>
    <div class="col-auto">
      <label class="form-label mb-0 small d-block">&nbsp;</label>
      <button class="btn btn-outline-secondary">Filtrar</button>
      <a href="{{ route('admin.standby.create') }}" class="btn btn-primary ms-2">Agregar</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>Usuario</th><th>Convocatoria</th><th>Activo</th><th>Externo</th>
          <th>Lu</th><th>Ma</th><th>Mi</th><th>Ju</th><th>Vi</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $r)
          <tr>
            <td>
              <div>{{ $r->user?->name }} <small class="text-muted">({{ $r->user?->codigo ?? '—' }})</small></div>
              <div class="text-muted small">{{ $r->user?->email }}</div>
            </td>
            <td>{{ $r->convocatoria?->nombre }}</td>
            <td>{!! $r->activo ? '<span class="badge text-bg-success">sí</span>' : '<span class="badge text-bg-secondary">no</span>' !!}</td>
            <td>{!! $r->es_externo ? '<span class="badge text-bg-warning">sí</span>' : '<span class="badge text-bg-light">no</span>' !!}</td>
            @foreach(['pref_lun','pref_mar','pref_mie','pref_jue','pref_vie'] as $col)
              <td>{{ $r->$col }}</td>
            @endforeach
            <td class="text-end">
              <form method="POST" action="{{ route('admin.standby.toggle-activo',$r) }}" class="d-inline">@csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-{{ $r->activo?'secondary':'success' }}">{{ $r->activo?'Desactivar':'Activar' }}</button>
              </form>
              <a href="{{ route('admin.standby.edit',$r) }}" class="btn btn-sm btn-outline-primary ms-1">Editar</a>
              <form method="POST" action="{{ route('admin.standby.destroy',$r) }}" class="d-inline ms-1" onsubmit="return confirm('¿Eliminar?');">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="text-muted p-4">Sin registros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $items->links() }}
</div>
@endsection