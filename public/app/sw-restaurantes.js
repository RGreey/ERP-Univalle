const CACHE = 'restaurantes-pwa-v1';
const APP_SCOPE = '/app/restaurantes/';

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE).then(cache => cache.addAll([
      APP_SCOPE, // página base del módulo (enrutada por Laravel)
      '/restaurantes/manifest.json',
      '/restaurantes/icons/icon-192.png',
      '/restaurantes/icons/icon-512.png'
    ]))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  const isApp = url.pathname.startsWith(APP_SCOPE);
  const isAssets = url.pathname.startsWith('/restaurantes/');
  if (!isApp && !isAssets) return;

  event.respondWith(
    caches.match(req).then(cached => {
      const fetchPromise = fetch(req).then(res => {
        caches.open(CACHE).then(c => c.put(req, res.clone()));
        return res;
      }).catch(() => cached);
      return cached || fetchPromise;
    })
  );
});