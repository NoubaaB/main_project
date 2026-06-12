const CACHE_NAME = 'jupiter-shell-cache-v1';
const DYNAMIC_CACHE_NAME = 'jupiter-dynamic-cache-v1';

const PRECACHE_ASSETS = [
  '/',
  '/js/manifest.json',
  '/css/main.css',
  '/css/vuetify.min.css',
  '/css/materialdesignicons.min.css',
  '/js/vue.global.js',
  '/js/vuetify.min.js',
  '/js/apexcharts.js',
  '/js/vue.esm-browser.js',
  '/js/vuetify.esm.js',
  '/images/jupiter-logo.png',
  '/images/white-jupiter-logo.png'
];

// Install Event
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Pre-caching offline shell');
      return cache.addAll(PRECACHE_ASSETS).catch((err) => {
        console.error('[Service Worker] Pre-cache failed', err);
      });
    })
  );
});

// Activate Event
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME && key !== DYNAMIC_CACHE_NAME) {
            console.log('[Service Worker] Removing old cache', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Bypass non-http/https schemes (e.g. chrome-extension, ws/wss)
  if (!request.url.startsWith('http')) {
    return;
  }

  // Rule 3 Bypass: WebSockets, Echo/Pusher, and API requests must always go to network (real-time sync)
  if (
    url.pathname.includes('/api/') ||
    url.pathname.includes('/broadcasting/') ||
    request.url.includes('pusher') ||
    request.url.includes('sockjs') ||
    request.method !== 'GET'
  ) {
    event.respondWith(fetch(request));
    return;
  }

  // Handle SPA routing: serve the index shell for navigation requests (HTML pages)
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => {
        return caches.match('/');
      })
    );
    return;
  }

  // Cache-First for Vite hashed assets, fonts, and static libraries
  const isViteAsset = url.pathname.includes('/build/assets/');
  const isFont = url.pathname.includes('/font/') || url.pathname.includes('/materialdesignicons') || url.pathname.endsWith('.woff2') || url.pathname.endsWith('.woff') || url.pathname.endsWith('.ttf');
  const isStaticLib = PRECACHE_ASSETS.includes(url.pathname);

  if (isViteAsset || isFont || isStaticLib) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const cacheName = isStaticLib ? CACHE_NAME : DYNAMIC_CACHE_NAME;
            return caches.open(cacheName).then((cache) => {
              cache.put(request, networkResponse.clone());
              return networkResponse;
            });
          }
          return networkResponse;
        });
      })
    );
    return;
  }

  // Stale-While-Revalidate for other GET requests
  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      const fetchPromise = fetch(request).then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
          return caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
            cache.put(request, networkResponse.clone());
            return networkResponse;
          });
        }
        return networkResponse;
      }).catch(() => {
        // Suppress network errors for fetch requests when offline
      });

      return cachedResponse || fetchPromise;
    })
  );
});
