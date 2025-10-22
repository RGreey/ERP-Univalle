@php
    $fecha = optional($cupo->fecha)?->format('Y-m-d');
    $sede  = ucfirst($cupo->sede ?? '');
@endphp

<p>¡Confirmado!</p>
<p>Tu cupo por reemplazo quedó asignado para hoy ({{ $fecha }}) en la sede {{ $sede }}.</p>
<p>Presenta tu QR habitual en el restaurante.</p>
<p>Bienestar Universitario</p>