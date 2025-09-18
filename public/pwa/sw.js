const CACHE_NAME = 'novedades-v2';
const urlsToCache = [
  '/pwa/',
  '/pwa/style.css',
  '/pwa/app.js',
  '/pwa/manifest.json',
  '/pwa/icons/icon-16x16.png',
  '/pwa/icons/icon-32x32.png',
  '/pwa/icons/icon-72x72.png',
  '/pwa/icons/icon-96x96.png',
  '/pwa/icons/icon-128x128.png',
  '/pwa/icons/icon-144x144.png',
  '/pwa/icons/icon-152x152.png',
  '/pwa/icons/icon-192x192.png',
  '/pwa/icons/icon-384x384.png',
  '/pwa/icons/icon-512x512.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js',
  'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// Install event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache opened');
        return cache.addAll(urlsToCache);
      })
  );
});

// Fetch event
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        return response || fetch(event.request);
      })
  );
});

// Activate event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
}); 