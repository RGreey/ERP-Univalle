@php
$ctxSede     = session('restaurante_codigo');
$ctxConvId   = session('restaurante_convocatoria_id');
$ctxConvName = session('restaurante_convocatoria_nombre');
if ($ctxConvId && !$ctxConvName) {
    $ctxConvName = \App\Models\ConvocatoriaSubsidio::find($ctxConvId)?->nombre;
}
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
<div class="d-flex flex-wrap align-items-center gap-2">
    @if($ctxSede)
    <span class="badge bg-info text-dark">Sede: {{ ucfirst($ctxSede) }}</span>
    @else
    <span class="badge bg-light text-dark">Sede: Todas</span>
    @endif

    @if($ctxConvId)
    <span class="badge bg-info text-dark">Convocatoria: {{ $ctxConvName ?? ('ID '.$ctxConvId) }}</span>
    @else
    <span class="badge bg-light text-dark">Convocatoria: Todas</span>
    @endif
</div>
<div class="pwa-top-actions">
    <a href="{{ route('restaurantes.dashboard') }}" class="btn btn-primary btn-sm">
    Cambiar contexto
    </a>
</div>
</div>