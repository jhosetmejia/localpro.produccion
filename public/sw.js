// LocalPro: permite instalar el sistema como app. Las páginas siempre se piden en línea
// (datos al día); si no hay internet se muestra un aviso en vez de la página de error del navegador.
const CACHE = 'localpro-v1';
const OFFLINE = '/offline.html';

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll([OFFLINE, '/icons/icon-192.png'])));
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k)))));
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    if (e.request.mode === 'navigate') {
        e.respondWith(fetch(e.request).catch(() => caches.match(OFFLINE)));
    }
});
