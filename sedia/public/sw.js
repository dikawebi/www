const CACHE = 'sedia-v1';
const ASSETS = ['/manifest.json'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);
  // Cache-first untuk build assets, network-first untuk lainnya (jangan cache halaman Livewire/POST)
  if (url.pathname.startsWith('/build/')) {
    e.respondWith(caches.match(e.request).then((r) => r || fetch(e.request).then((res) => {
      const clone = res.clone();
      caches.open(CACHE).then((c) => c.put(e.request, clone));
      return res;
    })));
    return;
  }
  // Untuk navigasi dokumen, biarkan network (Livewire butuh fresh HTML)
  if (e.request.method !== 'GET' || e.request.headers.get('accept')?.includes('text/html')) {
    return;
  }
  e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
});
