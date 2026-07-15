const CACHE_NAME = 'expense-tracker-v1';
const STATIC_ASSETS = [
    '/expenses/',
    '/expenses/assets/css/app.css',
    '/expenses/assets/js/app.js',
    '/expenses/assets/js/offline.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/chart.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
    // Network-first strategy, fallback to cache if offline
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});