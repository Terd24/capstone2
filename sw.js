/* OneCCI Service Worker */
const CACHE_NAME = 'onecci-cache-v11';
const OFFLINE_URL = '/offline.html';
const CORE_ASSETS = [
  '/manifest.webmanifest',
  '/offline.html',
  '/images/LogoCCI.png',
  '/images/Leftpic.png'
];

// Install event - cache core assets
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...');
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('Caching core assets...');
      return cache.addAll(CORE_ASSETS);
    })
    .then(() => {
      console.log('Skip waiting...');
      return self.skipWaiting();
    })
    .catch((error) => {
      console.error('Cache failed during install:', error);
    })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...');
  event.waitUntil(
    caches.keys().then(keys => {
      console.log('Cleaning up old caches...');
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            console.log('Deleting old cache:', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => {
      console.log('Claiming clients...');
      return self.clients.claim();
    })
  );
});

// Fetch event - handle network requests
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip unsupported URL schemes (chrome-extension, etc.)
  if (url.protocol !== 'http:' && url.protocol !== 'https:') {
    return;
  }

  // Only handle our scope
  if (!url.pathname.startsWith('/')) return;

  // Skip non-GET requests
  if (request.method !== 'GET') return;

  // Handle navigation requests (HTML pages)
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // If successful, update cache and return response
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone).catch((err) => {
                console.log('Cache put failed:', err);
              });
            });
          }
          return response;
        })
        .catch(() => {
          // Network failed, try cache
          console.log('Network failed, trying cache for:', request.url);
          return caches.match(request).then((cached) => {
            if (cached) {
              console.log('Found in cache:', request.url);
              return cached;
            }
            // No cache, return offline page
            console.log('Not in cache, returning offline page');
            return caches.match(OFFLINE_URL);
          });
        })
    );
    return;
  }

  // Handle API requests (JSON responses)
  if (request.headers.get('Accept')?.includes('application/json')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone).catch((err) => {
                console.log('Cache put failed:', err);
              });
            });
          }
          return response;
        })
        .catch(() => {
          // For API requests, we can't really serve cached data since it's dynamic
          // Return a meaningful error response
          return new Response(JSON.stringify({
            error: 'Offline',
            message: 'This feature requires an internet connection'
          }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
          });
        })
    );
    return;
  }

  // Handle static assets (images, CSS, JS, etc.)
  const isStatic = /\.(png|jpg|jpeg|gif|webp|ico|svg|css|js|json|webmanifest|woff|woff2|ttf|eot)$/i.test(url.pathname);

  if (isStatic) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) {
          // Return cached version
          return cached;
        }

        // Not in cache, fetch from network
        return fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            // Cache successful responses (only for http/https)
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone).catch((err) => {
                console.log('Cache put failed:', err);
              });
            });
          }
          return networkResponse;
        }).catch(() => {
          // Network failed for static asset
          return new Response('', { status: 404 });
        });
      })
    );
  }
});

// Background sync for offline actions (if supported)
self.addEventListener('sync', (event) => {
  console.log('Background sync:', event.tag);
  // Handle background sync events here if needed
});

// Push notifications (if supported)
self.addEventListener('push', (event) => {
  console.log('Push message received');
  // Handle push notifications here if needed
});
