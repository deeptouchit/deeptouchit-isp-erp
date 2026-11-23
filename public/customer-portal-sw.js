const CACHE_NAME = 'customer-portal-shell-v1';
const SHELL = '/customer-portal-offline.html';

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.add(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Never cache portal pages, API responses, forms, invoices, payments or account data.
    if (request.method !== 'GET' || url.origin !== self.location.origin || url.pathname.startsWith('/portal/')) {
        event.respondWith(fetch(request));
        return;
    }

    event.respondWith(fetch(request).catch(() => caches.match(SHELL)));
});
