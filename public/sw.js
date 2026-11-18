// LocalPro: permite instalar el sistema como app. Las páginas siempre se piden al servidor (datos al día).
// Si falla, se reintenta; la pantalla "Sin conexión" solo aparece cuando el teléfono realmente no tiene internet.
// Si hay internet pero el servidor no responde, se deja ver el error real del navegador.
const CACHE = 'localpro-v4';
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

// 2026-11-18 (Jhoset): avisos de LP Chat aunque la app esté cerrada. El aviso llega vacío y aquí se pregunta quién escribió.
self.addEventListener('push', (e) => {
    e.waitUntil((async () => {
        let d = { titulo: 'LP Chat', texto: 'Tienes mensajes nuevos', url: '/chat', tag: 'lpchat' };
        try {
            const r = await fetch('/chat/api/aviso', { credentials: 'include', headers: { Accept: 'application/json' } });
            const j = await r.json();
            if (j && j.ok && j.titulo) d = j;
        } catch (x) {}
        const ya = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        // Si tiene el chat abierto y a la vista, no hace falta el aviso del celular
        if (ya.some((c) => c.visibilityState === 'visible' && new URL(c.url).pathname.startsWith('/chat'))) return;
        await self.registration.showNotification(d.titulo, {
            body: d.texto, tag: d.tag, renotify: true, icon: '/icons/lpchat-192.png', badge: '/icons/lpchat-192.png', data: { url: d.url },
        });
        if (self.navigator && self.navigator.setAppBadge && d.total) { try { await self.navigator.setAppBadge(d.total); } catch (x) {} }
    })());
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const url = (e.notification.data && e.notification.data.url) || '/chat';
    e.waitUntil((async () => {
        const ya = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const c of ya) {
            if (new URL(c.url).pathname.startsWith('/chat') && 'focus' in c) { try { await c.navigate(url); } catch (x) {} return c.focus(); }
        }
        return self.clients.openWindow(url);
    })());
});
