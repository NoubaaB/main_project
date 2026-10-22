const CACHE_NAME = 'jupiter-shell-cache-v17';
const DYNAMIC_CACHE_NAME = 'jupiter-dynamic-cache-v17';

const PRECACHE_ASSETS = [
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

  // Rule 3 Bypass: WebSockets, Echo/Pusher, dynamic manifest, and API requests must always go to network (real-time sync)
  if (
    url.pathname.includes('/api/') ||
    url.pathname.includes('/broadcasting/') ||
    url.pathname.includes('/manifest.json') ||
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

// ─── Web Push Event Listener ───────────────────────────────────────
self.addEventListener('push', (event) => {
  console.log('[Service Worker] Web Push event received:', event);

  let data = {};
  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data = {
        title: 'Jupiter ERP Notification',
        body: event.data.text()
      };
    }
  }

  const title = data.title || 'Jupiter ERP Notification';
  const options = {
    body: data.body || 'New activity in Jupiter ERP',
    icon: data.icon || '/images/jupiter-logo.png',
    badge: data.badge || '/favicon.ico',
    tag: data.tag || ('jupiter-' + Date.now()),
    renotify: data.renotify !== false,
    data: data.data || { url: '/' },
    vibrate: [100, 50, 100],
    actions: [
      { action: 'open', title: 'Open' },
      { action: 'dismiss', title: 'Dismiss' }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// ─── Notification Click Event Listener ─────────────────────────────
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  if (event.action === 'dismiss') {
    return;
  }

  const targetUrl = (event.notification.data && event.notification.data.url)
    ? event.notification.data.url
    : '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      // If a client window is already open, focus it and notify via message
      for (let i = 0; i < windowClients.length; i++) {
        const client = windowClients[i];
        if ('focus' in client) {
          client.postMessage({
            type: 'NAVIGATE_TO',
            url: targetUrl,
            notificationData: event.notification.data
          });
          return client.focus();
        }
      }
      // If no open client exists, open a new window
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});

