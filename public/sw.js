/* Business Purpose: minimal PWA service worker — cache shell assets, always network for app pages/API. */
const CACHE_NAME = 'gaz-pwa-v1';
const PRECACHE = [
  '/manifest.webmanifest',
  '/pwa/icon-192.png',
  '/pwa/icon-512.png',
  '/pwa/apple-touch-icon.png',
  '/favicon.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(PRECACHE))
      .then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)),
    )).then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  // Never cache Livewire/auth mutations or Vite HMR
  if (
    url.pathname.startsWith('/livewire')
    || url.pathname.startsWith('/sanctum')
    || url.pathname.includes('/api/')
    || url.pathname.startsWith('/@vite')
    || url.pathname.startsWith('/build/')
  ) {
    return;
  }

  // Navigations: network first, fallback to cache only if offline
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('/login') || caches.match(req)),
    );
    return;
  }

  // Static icons/manifest: cache first
  if (
    url.pathname.startsWith('/pwa/')
    || url.pathname === '/manifest.webmanifest'
    || url.pathname === '/favicon.png'
    || url.pathname === '/favicon.ico'
  ) {
    event.respondWith(
      caches.match(req).then((cached) => cached || fetch(req).then((res) => {
        const copy = res.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
        return res;
      })),
    );
  }
});
