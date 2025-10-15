@php
$prev = url()->previous();
$curr = url()->current();
$fallback = route('restaurantes.dashboard');
$usePrev = $prev && $prev !== $curr;
@endphp
<div class="pwa-top-actions">
<a href="{{ $usePrev ? $prev : $fallback }}"
    class="btn btn-secondary btn-sm"
    onclick="if (document.referrer && document.referrer !== window.location.href) { history.back(); return false; }">
    &#8592; Volver
</a>
</div>