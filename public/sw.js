// LocalPro: permite instalar el sistema como app. Las páginas siempre se piden al servidor (datos al día).
// Si falla, se reintenta; la pantalla "Sin conexión" solo aparece cuando el teléfono realmente no tiene internet.
// Si hay internet pero el servidor no responde, se deja ver el error real del navegador.
const CACHE = 'localpro-v3';
const OFFLINE = '/offline.html';

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll([OFFLINE, '/icons/icon-192.png'])));
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k)))));
    self.clients.claim();
});

const esperar = (ms) => new Promise((r) => setTimeout(r, ms));

async function navegar(request) {
    try {
        return await fetch(request);
    } catch (e1) {
        // Cambios de red (datos ↔ wifi) hacen fallar la primera petición: se reintenta
        await esperar(1200);
        try {
            return await fetch(request);
        } catch (e2) {
            if (self.navigator && self.navigator.onLine === false) {
                const r = await caches.match(OFFLINE);
                if (r) return r;
            }
            throw e2;
        }
    }
}

self.addEventListener('fetch', (e) => {
    if (e.request.mode === 'navigate' && e.request.method === 'GET') {
        e.respondWith(navegar(e.request));
    }
});
