@extends('layouts.app')

@section('title', 'Dashboard - AdminBienestar')

@section('content')
<style>
    :root{ --uv-rojo:#cd1f32; --sidebar-w:240px; --nav-h:70px; }
    html, body { height:100%; overflow:hidden; background:#f8f9fa !important; }
    .dashboard-shell { position:fixed; top:var(--nav-h); left:0; right:0; bottom:0; display:flex; width:100vw; overflow:hidden; z-index:1; }
    .dash-sidebar { width:var(--sidebar-w); background:#cd1f32; color:#fff; padding-top:24px; border-top:1px solid #b10024; box-shadow:2px 0 8px #0001; overflow-y:auto; -webkit-overflow-scrolling:touch; flex-shrink:0; }
    .dash-sidebar h2 { margin:0 0 18px 24px; font-weight:800; font-size:1.6rem; }
    .dash-menu { list-style:none; margin:0; padding:0; }
    .dash-menu li { border-left:3px solid transparent; }
    .dash-menu a { color:#fff; text-decoration:none; display:block; padding:12px 22px; font-size:1.05rem; transition:background .15s ease; }
    .dash-menu li:hover, .dash-menu li.active { background:#b10024; border-left-color:#fff; }
    .dash-main { flex:1; background:#f8f9fa; padding:28px; overflow-y:auto; -webkit-overflow-scrolling:touch; min-width:0; }

    .kpis { display:grid; grid-template-columns: repeat(4, minmax(160px, 1fr)); gap:16px; margin-bottom:18px; }
    .kpi { background:#fff; border-radius:10px; box-shadow:0 2px 8px #0001; padding:16px; }
    .kpi h6 { margin:0 0 6px; color:#666; font-weight:600; }
    .kpi .val { font-size:1.6rem; font-weight:800; }

    .card { box-shadow:0 2px 8px #0001; }
    .chart-wrap { height: 360px; }  /* misma altura para ambos gráficos */
    .chart-wrap canvas { height:100% !important; } /* Chart.js toma alto del contenedor */

    .table-mov { width:100%; border-collapse:collapse; }
    .table-mov th, .table-mov td { border:1px solid #e5e5e5; padding:10px; }
    .table-mov th { background:#222; color:#fff; }
    .pinned { color:var(--uv-rojo); margin-right:6px; }

    @media (max-width: 1200px){ .kpis{ grid-template-columns: repeat(2, 1fr);} .chart-wrap{ height:300px; } }
    @media (max-width: 768px) { .dashboard-shell { top: calc(var(--nav-h) + 48px); } .dash-sidebar { position:absolute; height:100%; z-index:2; } .dash-main { padding:16px; } .chart-wrap{ height:260px; } }
</style>

@php
    use App\Models\CupoAsignacion;
    use App\Models\CupoDiario;
    use App\Models\ConvocatoriaSubsidio;

    $nombreCompleto = auth()->user()->name;
    $primerNombre = explode(' ', $nombreCompleto)[0];
    $primerNombreMayuscula = ucfirst(strtolower($primerNombre));

    $tz = config('subsidio.timezone', 'America/Bogota');

    // Filtros (GET)
    $desde = \Carbon\Carbon::parse(request('desde', now($tz)->copy()->subDays(13)->toDateString()), $tz)->startOfDay();
    $hasta = \Carbon\Carbon::parse(request('hasta', now($tz)->toDateString()), $tz)->endOfDay();
    $sede  = request('sede'); // null | caicedonia | sevilla
    $convocatoriaId = request('convocatoria_id');

    // Convocatorias para selector
    $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')->get(['id','nombre']);

    // Datos del rango (solo L–V)
    $items = CupoAsignacion::with(['user','cupo'])
        ->whereHas('cupo', function($q) use ($desde, $hasta, $sede, $convocatoriaId) {
            $q->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
              ->whereRaw('WEEKDAY(fecha) <= 4'); // L–V
            if ($sede) $q->where('sede', $sede);
            if ($convocatoriaId) $q->where('convocatoria_id', $convocatoriaId);
        })
        ->get();

    $normEstado = fn($e)=> $e === 'no_show' ? 'inasistencia' : ($e ?: 'pendiente');

    // Labels L–V en el rango
    $labels = [];
    for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
        if (in_array($d->dayOfWeekIso, [1,2,3,4,5], true)) $labels[] = $d->toDateString();
    }

    // Conteos diarios por estado
    $estados = ['pendiente','cancelado','asistio','inasistencia'];
    $daily = [];
    foreach ($labels as $lbl) $daily[$lbl] = ['pendiente'=>0,'cancelado'=>0,'asistio'=>0,'inasistencia'=>0];
    foreach ($items as $a) {
        $f = optional($a->cupo?->fecha)?->toDateString();
        if (!$f || !isset($daily[$f])) continue;
        $e = $normEstado($a->asistencia_estado ?? 'pendiente');
        $daily[$f][$e] = ($daily[$f][$e] ?? 0) + 1;
    }

    // Totales (KPIs y doughnut)
    $totales = ['pendiente'=>0,'cancelado'=>0,'asistio'=>0,'inasistencia'=>0];
    foreach ($daily as $byDay) foreach ($byDay as $k=>$v) $totales[$k] += $v;
    $kpiTotal   = array_sum($totales);
    $kpiAsistio = $totales['asistio'];
    $kpiCancel  = $totales['cancelado'];
    $kpiInasis  = $totales['inasistencia'];

    // Datos Chart.js
    $chartLabels = $labels;
    $chartPend   = array_map(fn($f)=> $daily[$f]['pendiente'] ?? 0, $labels);
    $chartCanc   = array_map(fn($f)=> $daily[$f]['cancelado'] ?? 0, $labels);
    $chartAsis   = array_map(fn($f)=> $daily[$f]['asistio'] ?? 0, $labels);
    $chartInas   = array_map(fn($f)=> $daily[$f]['inasistencia'] ?? 0, $labels);

    // Resumen de HOY por sede (para la tabla inferior)
    $hoy = now($tz)->toDateString();
    $hoyItems = CupoAsignacion::with('cupo')
        ->whereHas('cupo', fn($q)=> $q->whereDate('fecha', $hoy))
        ->get();
    $sedes = ['caicedonia','sevilla'];
    $resumenSede = [];
    foreach ($sedes as $s) {
        $col = $hoyItems->filter(fn($a)=> $a->cupo?->sede === $s);
        $resumenSede[$s] = [
            'pendiente'   => $col->where('asistencia_estado','pendiente')->count(),
            'cancelado'   => $col->where('asistencia_estado','cancelado')->count(),
            'asistio'     => $col->where('asistencia_estado','asistio')->count(),
            'inasistencia'=> $col->filter(fn($x)=> ($x->asistencia_estado ?? null) === 'inasistencia' || $x->asistencia_estado === 'no_show')->count(),
            'total'       => $col->count(),
        ];
    }

    // Cancelaciones recientes (últimos 10 según filtros del rango)
    $cancelRecientes = CupoAsignacion::with(['user','cupo'])
        ->where('asistencia_estado','cancelado')
        ->whereHas('cupo', function($q) use ($desde, $hasta, $sede, $convocatoriaId) {
            $q->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
            if ($sede) $q->where('sede', $sede);
            if ($convocatoriaId) $q->where('convocatoria_id', $convocatoriaId);
        })
        ->orderByDesc(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
        ->limit(10)->get();
@endphp

<div class="dashboard-shell" id="dashShell">
    <aside class="dash-sidebar">
        <h2>Panel Admistrativo</h2>
        <ul class="dash-menu">
            <li class="active"><a href="{{ route('admin.subsidio.admin.dashboard') }}">Inicio</a></li>
            <li><a href="{{ route('admin.estudiantes') }}">Gestión de estudiantes</a></li>
            <li><a href="{{ route('admin.convocatorias') }}">Convocatorias</a></li>
            <li><a href="{{ \Illuminate\Support\Facades\Route::has('admin.cupos.index') ? route('admin.cupos.index') : '#' }}">Cupos y Asignaciones</a></li>
            <li><a href="{{ route('admin.restaurantes.index') }}">Restaurantes</a></li>
            <li><a href="{{ route('admin.asistencias.index') }}">Asistencias</a></li>
            <li><a href="{{ route('admin.asistencias.cancelaciones') }}">Cancelaciones</a></li>
            <li><a href="{{ \Illuminate\Support\Facades\Route::has('admin.reportes') ? route('admin.reportes') : '#' }}">Reportes</a></li>
            
        </ul>
    </aside>

    <main class="dash-main">
        <h1>Bienvenido, {{ $primerNombreMayuscula }}! 👋</h1>
        <p class="text-muted mb-3">
            Aquí verás un resumen visual de las asistencias del período seleccionado. Ajusta los filtros para
            enfocarte por sede o convocatoria. Las estadísticas consideran únicamente días hábiles (lunes a viernes).
        </p>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.subsidio.admin.dashboard') }}" class="row g-2 mb-3">
            <div class="col-auto">
                <label class="form-label">Desde</label>
                <input type="date" name="desde" class="form-control" value="{{ $desde->toDateString() }}">
            </div>
            <div class="col-auto">
                <label class="form-label">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="{{ $hasta->copy()->startOfDay()->toDateString() }}">
            </div>
            <div class="col-auto">
                <label class="form-label">Sede</label>
                <select name="sede" class="form-select">
                    <option value="">Ambas</option>
                    <option value="caicedonia" @selected(($sede ?? '')==='caicedonia')>Caicedonia</option>
                    <option value="sevilla" @selected(($sede ?? '')==='sevilla')>Sevilla</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label">Convocatoria</label>
                <select name="convocatoria_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($convocatorias as $c)
                        <option value="{{ $c->id }}" @selected(($convocatoriaId ?? null)==$c->id)>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto align-self-end">
                <button class="btn btn-primary">Aplicar</button>
            </div>
        </form>

        {{-- KPIs --}}
        <div class="kpis">
            <div class="kpi"><h6>Total registros</h6><div class="val">{{ $kpiTotal }}</div></div>
            <div class="kpi"><h6>Asistió</h6><div class="val text-success">{{ $kpiAsistio }}</div></div>
            <div class="kpi"><h6>Cancelado</h6><div class="val text-danger">{{ $kpiCancel }}</div></div>
            <div class="kpi"><h6>Inasistencia</h6><div class="val text-warning">{{ $kpiInasis }}</div></div>
        </div>

        {{-- Gráficos ordenados y alineados --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header"><strong>Evolución diaria por estado (solo L–V)</strong></div>
                    <div class="card-body">
                        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header"><strong>Distribución por estado</strong></div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div class="chart-wrap" style="width:100%"><canvas id="chartPie"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tablas debajo de los gráficos --}}
        <h5 class="mb-2"><span class="pinned">★</span>Resumen de asistencias de hoy ({{ $hoy }})</h5>
        <div class="table-responsive mb-4">
            <table class="table-mov">
                <thead>
                    <tr>
                        <th>Sede</th>
                        <th>Pendiente</th>
                        <th>Cancelado</th>
                        <th>Asistió</th>
                        <th>Inasistencia</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumenSede as $s => $r)
                        <tr>
                            <td>{{ ucfirst($s) }}</td>
                            <td>{{ $r['pendiente'] }}</td>
                            <td>{{ $r['cancelado'] }}</td>
                            <td>{{ $r['asistio'] }}</td>
                            <td>{{ $r['inasistencia'] }}</td>
                            <td><strong>{{ $r['total'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h5 class="mb-2">Cancelaciones recientes</h5>
        <div class="table-responsive">
            <table class="table-mov">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Sede</th>
                        <th>Estudiante</th>
                        <th>Motivo</th>
                        <th>Cancelada en</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cancelRecientes as $a)
                        <tr>
                            <td>{{ optional($a->cupo?->fecha)?->toDateString() }}</td>
                            <td>{{ ucfirst($a->cupo?->sede ?? '') }}</td>
                            <td>{{ $a->user?->name }}</td>
                            <td style="max-width: 420px">{{ $a->cancelacion_motivo ?? '—' }}</td>
                            <td>{{ optional($a->cancelada_en)?->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Sin cancelaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script>
(function(){
    const labels = @json($chartLabels);
    const dataPend = @json($chartPend);
    const dataCanc = @json($chartCanc);
    const dataAsis = @json($chartAsis);
    const dataInas = @json($chartInas);

    // Barras apiladas
    const ctx1 = document.getElementById('chartDaily');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Pendiente',    data: dataPend, backgroundColor: '#6c757d' },
                    { label: 'Cancelado',    data: dataCanc, backgroundColor: '#dc3545' },
                    { label: 'Asistió',      data: dataAsis, backgroundColor: '#198754' },
                    { label: 'Inasistencia', data: dataInas, backgroundColor: '#fd7e14' },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { stacked:true }, y: { stacked:true, beginAtZero:true, ticks:{ precision:0 } } },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Doughnut
    const totalPend = dataPend.reduce((a,b)=>a+b,0);
    const totalCanc = dataCanc.reduce((a,b)=>a+b,0);
    const totalAsis = dataAsis.reduce((a,b)=>a+b,0);
    const totalInas = dataInas.reduce((a,b)=>a+b,0);

    const ctx2 = document.getElementById('chartPie');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Pendiente','Cancelado','Asistió','Inasistencia'],
                datasets: [{
                    data: [totalPend,totalCanc,totalAsis,totalInas],
                    backgroundColor: ['#6c757d','#dc3545','#198754','#fd7e14']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                cutout: '60%'
            }
        });
    }

    // Altura navbar -> variable CSS
    function setNavbarHeightVar() {
        const nav = document.querySelector('.navbar');
        const h = nav ? Math.round(nav.getBoundingClientRect().height) : 70;
        document.documentElement.style.setProperty('--nav-h', h + 'px');
    }
    setNavbarHeightVar();
    window.addEventListener('resize', setNavbarHeightVar);

    // Evitar scroll del body, solo paneles
    document.body.addEventListener('wheel', (e) => {
        const target = e.target.closest('.dash-main, .dash-sidebar');
        if (!target) e.preventDefault();
    }, { passive: false });
})();
</script>
@endpush
@endsection