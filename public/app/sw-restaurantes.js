const VERSION = 'restaurantes-v3-2025-10-22';
const STATIC = `restaurantes-static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

// Acepta raíz sin barra y con barra, y todo lo descendiente
const isInScope = (url) => {
  try {
    const u = new URL(url);
    if (u.origin !== location.origin) return false;
    return (
      u.pathname === '/app/restaurantes' ||
      u.pathname === '/app/restaurantes/' ||
      u.pathname.startsWith('/app/restaurantes/')
    );
  } catch { return false; }
};

const isAsset = (url) => {
  try {
    const u = new URL(url);
    if (u.origin !== location.origin) return false;
    return (
      u.pathname.startsWith('/restaurantes/') || // manifest e iconos
      u.pathname.startsWith('/icons/') ||
      u.pathname.startsWith('/css/') ||
      u.pathname.startsWith('/js/') ||
      u.pathname.startsWith('/build/')
    );
  } catch { return false; }
};

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(STATIC).then(c => c.addAll([
      OFFLINE_URL,
      '/restaurantes/manifest.json',
      // Precache del inicio canónico y con slash
      '/app/restaurantes',
      '/app/restaurantes/'
    ])).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== STATIC).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;

  // Navegaciones del módulo
  if (req.mode === 'navigate' && isInScope(req.url)) {
    e.respondWith(
      fetch(req).catch(async () => {
        const cache = await caches.open(STATIC);
        return (await cache.match(OFFLINE_URL)) || new Response('Offline', { status: 503 });
      })
    );
    return;
  }

  // Assets
  if (isAsset(req.url)) {
    e.respondWith(
      caches.open(STATIC).then(async (cache) => {
        const cached = await cache.match(req);
        const fetching = fetch(req).then(res => {
          if (res && res.status === 200) cache.put(req, res.clone());
          return res;
        }).catch(() => cached);
        return cached || fetching;
      })
    );
  }
});