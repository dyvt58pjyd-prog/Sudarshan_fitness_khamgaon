const CACHE_NAME = 'sudarshan-cache-v7-turbo';
const STATIC_ASSETS = [
  './manifest.json',
  './css/style.css?v=3.0',
  './css/dashMain.css?v=3.0',
  './css/premium.css?v=3.0',
  './css/entypo.css',
  './js/Script.js',
  './js/ui_sounds.js'
];

// Install Event: Cache core static assets and skip waiting immediately
self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS).catch(err => {
        console.warn('PWA Asset Pre-cache warning:', err);
      });
    })
  );
});

// Activate Event: Purge ALL obsolete caches and claim clients
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(name => {
          if (name !== CACHE_NAME) {
            console.log('Purging legacy cache:', name);
            return caches.delete(name);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Listen for explicit cache clear command from client UI
self.addEventListener('message', event => {
  if (event.data && event.data.action === 'CLEAR_ALL_CACHE') {
    caches.keys().then(names => {
      return Promise.all(names.map(name => caches.delete(name)));
    }).then(() => {
      if (event.ports && event.ports[0]) {
        event.ports[0].postMessage({ success: true });
      }
    });
  }
});

// Fetch Event: Intelligent Strategy
self.addEventListener('fetch', event => {
  const req = event.request;

  // 1. Never intercept non-GET requests (e.g. POST for Face ID verify, logins, form submissions)
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);

  // 2. Network-First for Navigation, PHP pages, and API endpoints (always fresh dynamic data)
  const isDynamic = req.mode === 'navigate' || 
                    url.pathname.endsWith('.php') || 
                    url.pathname.includes('/api/') || 
                    url.pathname.includes('/dashboard/');

  if (isDynamic) {
    event.respondWith(
      fetch(req).catch(() => caches.match(req))
    );
    return;
  }

  // 3. Cache-First with Network Fallback for static assets (CSS, JS, Fonts, Images)
  event.respondWith(
    caches.match(req).then(cachedResponse => {
      if (cachedResponse) {
        return cachedResponse;
      }
      return fetch(req).then(networkResponse => {
        // Cache successful image or font responses dynamically
        if (networkResponse && networkResponse.status === 200 && (
            url.pathname.includes('/images/') || 
            url.pathname.includes('/fonts/') ||
            url.pathname.endsWith('.png') ||
            url.pathname.endsWith('.jpg') ||
            url.pathname.endsWith('.svg')
        )) {
          const resClone = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(req, resClone));
        }
        return networkResponse;
      });
    })
  );
});
