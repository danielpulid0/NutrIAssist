// NutrIAssist Service Worker — v1.0
// Cache-First para assets estáticos | Network-First para vistas PHP

const CACHE_NAME = 'nutriassist-v1';

const STATIC_ASSETS = [
  '/nutriassist/',
  '/nutriassist/index.php',
  '/nutriassist/assets/css/global.css',
  '/nutriassist/assets/img/avocado.svg',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
];

// INSTALL — pre-cachear assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

// ACTIVATE — limpiar caches viejos
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// FETCH — estrategia híbrida
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Controllers → siempre red (datos dinámicos, nunca cachear)
  if (url.pathname.includes('/controllers/')) {
    event.respondWith(fetch(event.request));
    return;
  }

  // PHP views → Network-First con fallback a cache
  if (url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(event.request)
        .then(res => {
          const clone = res.clone();
          caches.open(CACHE_NAME).then(c => c.put(event.request, clone));
          return res;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // Assets estáticos → Cache-First
  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;
      return fetch(event.request).then(res => {
        const clone = res.clone();
        caches.open(CACHE_NAME).then(c => c.put(event.request, clone));
        return res;
      });
    })
  );
});
