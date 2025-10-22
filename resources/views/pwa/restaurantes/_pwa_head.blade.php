@push('head')
<link rel="manifest" href="/restaurantes/manifest.webmanifest">
<meta name="theme-color" content="#cd1f32">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
@endpush

@push('scripts')
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
      try {
        const reg = await navigator.serviceWorker.register('/app/restaurantes/sw-restaurantes.js', { scope: '/app/restaurantes/' });
        // console.log('SW Restaurantes:', reg.scope);
      } catch (e) {
        console.error('SW register (restaurantes) failed', e);
      }
    });
  }
</script>
@endpush