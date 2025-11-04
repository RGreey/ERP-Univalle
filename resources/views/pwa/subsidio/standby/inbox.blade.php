@extends('layouts.app')
@include('pwa.subsidio._pwa_head')
@section('title','Buzón de reemplazos')

@section('content')
<div class="container">
  <h3 class="mb-3">Buzón de reemplazos</h3>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))  <div class="alert alert-danger">{{ session('error') }}</div>@endif

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header"><strong>Ofertas personales</strong></div>
        <div class="card-body">
          @if($pendientes->isEmpty())
            <div class="text-muted">No tienes ofertas pendientes.</div>
          @else
            <div class="table-responsive">
              <table class="table align-middle">
                <thead><tr><th>Fecha</th><th>Sede</th><th>Vence en</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($pendientes as $o)
                  @php
                    $fecha = optional($o->cupo?->fecha)->format('Y-m-d');
                    $sede  = ucfirst($o->cupo?->sede ?? '');
                    $deadline = optional($o->vence_en)->toIso8601String();
                  @endphp
                  <tr>
                    <td>{{ $fecha ?: '—' }}</td>
                    <td>{{ $sede ?: '—' }}</td>
                    <td>
                      @if($o->vence_en)
                        <span class="countdown" data-deadline="{{ $deadline }}"></span>
                      @else
                        <span class="text-muted">Sin tiempo límite</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <form class="d-inline" method="POST" action="{{ route('app.subsidio.ofertas.accept', $o) }}">@csrf
                        <button class="btn btn-sm btn-success">Tomar cupo</button>
                      </form>
                      <form class="d-inline" method="POST" action="{{ route('app.subsidio.ofertas.decline', $o) }}">@csrf
                        <button class="btn btn-sm btn-outline-secondary">Rechazar</button>
                      </form>
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

    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Vacantes disponibles</strong>
          <small class="text-muted">Todas las fechas a futuro que coinciden con tu standby.</small>
        </div>
        <div class="card-body">
          @if(($vacantes->count() ?? 0) === 0)
            <div class="text-muted">Por ahora no hay vacantes disponibles.</div>
          @else
            <div class="table-responsive">
              <table class="table align-middle">
                <thead><tr><th>Fecha</th><th>Sede</th><th>Vacantes</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($vacantes as $c)
                  <tr>
                    <td>{{ optional($c->fecha)->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($c->sede) }}</td>
                    <td>{{ (int)($c->vacantes ?? 0) }}</td>
                    <td class="text-end">
                      @if(!empty($c->ui_ya_tiene_ese_dia))
                        <button class="btn btn-sm btn-outline-secondary" disabled title="Ya tienes un cupo no cancelado ese día.">Tomar</button>
                      @else
                        <form method="POST" action="{{ route('app.subsidio.ofertas.claim', $c->id) }}" class="d-inline">@csrf
                          <button class="btn btn-sm btn-primary">Tomar</button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
            <div class="mt-2">
              {{ $vacantes->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const els = document.querySelectorAll('.countdown');
  function fmt(ms){
    if (ms <= 0) return 'vencida';
    const s = Math.floor(ms/1000);
    const m = Math.floor(s/60);
    const ss = s%60;
    if (m >= 60) { const h = Math.floor(m/60), mm = m%60; return `${h}h ${mm}m`; }
    return `${m}m ${ss.toString().padStart(2,'0')}s`;
  }
  function tick(){
    const now = new Date();
    els.forEach(el=>{
      const dl = el.getAttribute('data-deadline');
      if (!dl) return;
      const diff = new Date(dl) - now;
      el.textContent = fmt(diff);
      if (diff <= 0) el.classList.add('text-danger');
    });
  }
  tick(); setInterval(tick, 1000);
})();
</script>
@endpush