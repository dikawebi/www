const CACHE = 'sedia-v2';
const ASSETS = [
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/apple-touch-icon.png',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE)
      .then((c) => c.addAll(ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);

  // Cache-first untuk build assets (Vite JS/CSS)
  if (url.pathname.startsWith('/build/')) {
    e.respondWith(
      caches.match(e.request).then((r) =>
        r || fetch(e.request).then((res) => {
          const clone = res.clone();
          caches.open(CACHE).then((c) => c.put(e.request, clone));
          return res;
        })
      )
    );
    return;
  }

  // Cache-first untuk icons & static
  if (url.pathname.startsWith('/icons/') || url.pathname === '/manifest.json') {
    e.respondWith(
      caches.match(e.request).then((r) =>
        r || fetch(e.request).then((res) => {
          const clone = res.clone();
          caches.open(CACHE).then((c) => c.put(e.request, clone));
          return res;
        })
      )
    );
    return;
  }

  // Network-first untuk navigasi & Livewire (POST, HTML)
  if (e.request.method !== 'GET' || e.request.headers.get('accept')?.includes('text/html')) {
    e.respondWith(
      fetch(e.request).catch(() => {
        // Offline fallback: tampilkan halaman offline sederhana
        return new Response(
          `<!DOCTYPE html><html><head><title>Sedia POS - Offline</title>
          <meta name="viewport" content="width=device-width,initial-scale=1">
          <style>body{font-family:system-ui;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;margin:0}
          .box{text-align:center;padding:2rem;max-width:400px}.icon{font-size:3rem;margin-bottom:1rem}
          h2{margin:0.5rem 0;color:#1f2937}p{color:#6b7280;font-size:0.9rem}</style></head>
          <body><div class="box"><div class="icon">📡</div><h2>Koneksi Terputus</h2>
          <p>Tidak ada koneksi internet. Silakan periksa WiFi/kuota Anda, lalu muat ulang halaman.</p>
          <button onclick="location.reload()" style="margin-top:1rem;padding:0.6rem 1.5rem;background:#f59e0b;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer">Muat Ulang</button></div></body></html>`,
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
      })
    );
    return;
  }

  // Network-first untuk API & lainnya, fallback ke cache
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});
