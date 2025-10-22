@php
  // Tolerante: usa el cupo pasado, o bien el de la oferta
  $cupo   = $cupo ?? ($oferta->cupo ?? null);
  $fecha  = optional($cupo?->fecha)->format('Y-m-d');
  $sede   = ucfirst($cupo->sede ?? '');
  $vence  = optional($oferta->vence_en)->format('H:i');
@endphp

<p>Hola {{ $oferta->user?->name ?? 'estudiante' }},</p>
<p>Hay un cupo disponible por reemplazo para {{ $fecha ? "hoy ({$fecha})" : 'hoy' }} en la sede {{ $sede }}.</p>
<p>Si puedes asistir, por favor acepta antes de las {{ $vence }}:</p>

<p>
  <a href="{{ $url }}" style="display:inline-block;background:#198754;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">
    Aceptar cupo
  </a>
</p>

<p class="text-muted" style="color:#6c757d;">
  Si alguien más acepta primero, es posible que el cupo ya no esté disponible.
</p>

<p>Bienestar Universitario</p>