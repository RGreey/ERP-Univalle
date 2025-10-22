const VERSION = 'subsidio-v1';
const STATIC = `static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

const isStatic = (url) => {
  try {
    const u = new URL(url);
    if (u.origin !== location.origin) return false;
    return (
      u.pathname.startsWith('/icons/') ||
      u.pathname.startsWith('/css/') ||
      u.pathname.startsWith('/js/') ||
      u.pathname.startsWith('/build/')
    );
  } catch { return false; }
};

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(STATIC).then(c => c.addAll([OFFLINE_URL])).then(() => self.skipWaiting())
  );
});
self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== STATIC).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});
self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;

  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(async () => {
      const cache = await caches.open(STATIC);
      return (await cache.match(OFFLINE_URL)) || new Response('Offline', { status: 503 });
    }));
    return;
  }

  if (isStatic(req.url)) {
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