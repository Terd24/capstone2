/* OneCCI Service Worker */
const CACHE_NAME = 'onecci-cache-v1';
const OFFLINE_URL = '/onecci/offline.html';
const CORE_ASSETS = [
  '/onecci/manifest.webmanifest',
  '/onecci/offline.html',
  '/onecci/images/LogoCCI.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.map(k => (k !== CACHE_NAME ? caches.delete(k) : null))
    )).then(() => self.clients.claim())
  );
});

// Network-first for dynamic PHP pages, cache-first for static assets
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Only handle our scope
  if (!url.pathname.startsWith('/onecci/')) return;

  // Do NOT try to cache or intercept non-GET (e.g., POST to login.php)
  if (request.method !== 'GET') {
    return; // Let the browser handle it normally
  }

  // For static assets (images, css, js, manifest)
  const isStatic = /\.(png|jpg|jpeg|gif|webp|ico|svg|css|js|json|webmanifest)$/i.test(url.pathname);

  if (isStatic) {
    event.respondWith(
      caches.match(request).then((cached) => {
        const fetchPromise = fetch(request).then((networkResponse) => {
          const copy = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          return networkResponse;
        }).catch(() => cached);
        return cached || fetchPromise;
      })
    );
    return;
  }

  // For HTML/PHP pages: network first, fallback to cache, then offline page
  event.respondWith(
    fetch(request)
      .then((response) => {
        const copy = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        return response;
      })
      .catch(() => caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL)))
  );
});
