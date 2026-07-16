const CACHE_NAME = 'sudarshan-gym-cache-v3';
const urlsToCache = [
  './',
  './index.php',
  './manifest.json'
];

// Install Event: Cache files and forcefully skip waiting to activate immediately
self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Activate Event: Take control of all pages and clear out old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event: Network-first strategy for index.php to always get latest updates, 
// fallback to cache if offline.
self.addEventListener('fetch', event => {
  if (event.request.mode === 'navigate' || event.request.url.includes('index.php')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
  } else {
    // Standard Cache-First for other assets
    event.respondWith(
      caches.match(event.request)
        .then(response => {
          if (response) {
            return response;
          }
          return fetch(event.request);
        })
    );
  }
});
