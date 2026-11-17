/*
 * BookTala service worker.
 *
 * - Public pages: network first, saved copy when offline.
 * - Private areas (admin, staff, customer, login, booking): never stored; offline page instead.
 * - Static assets and CDN libraries: served from cache, refreshed in the background.
 * - Logging out clears stored pages so nobody sees the previous user's header.
 *
 * Bump VERSION whenever this file or the precached files change.
 */
const VERSION = 'bt-2026-11-19-1';
const PRECACHE = `${VERSION}-precache`;
const PAGES = `${VERSION}-pages`;
const ASSETS = `${VERSION}-assets`;
const IMAGES = `${VERSION}-images`;

const BASE = new URL(self.registration.scope);
const url = (path) => new URL(path, BASE).href;

const OFFLINE_URL = url('offline.html');
const PRECACHE_URLS = [
    OFFLINE_URL,
    url('manifest.webmanifest'),
    url('images/booktala-logo.png'),
    url('icons/icon-192.png'),
    url('icons/icon-512.png'),
    url('icons/apple-touch-icon.png'),
    url('icons/favicon-32.png'),
];
// Best-effort: pages worth having offline even before they are visited.
const WARM_PAGES = ['', 'apartments', 'contact', 'about', 'faq'].map(url);

const PRIVATE_PREFIXES = [
    'admin', 'staff', 'customer', 'login', 'logout', 'register', 'password',
    'forgot-password', 'reset-password', 'email', 'verify-email', 'auth',
    'booking', 'api', 'media', 'sanctum', 'owner',
];

const CDN_HOSTS = [
    'cdn.tailwindcss.com', 'cdnjs.cloudflare.com', 'cdn.jsdelivr.net',
    'unpkg.com', 'fonts.googleapis.com', 'fonts.gstatic.com',
];
const IMAGE_HOSTS = ['images.unsplash.com'];

const LIMITS = { [PAGES]: 40, [ASSETS]: 60, [IMAGES]: 60 };
const NETWORK_TIMEOUT_MS = 5000;

// ---------------------------------------------------------------------------

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(PRECACHE);
        await cache.addAll(PRECACHE_URLS);

        // Don't let a slow or failing page block installation.
        const pages = await caches.open(PAGES);
        await Promise.allSettled(WARM_PAGES.map(async (page) => {
            const response = await fetch(page, { credentials: 'same-origin' });
            if (isCacheablePage(response)) await pages.put(page, response);
        }));
    })());
    // The page decides when to switch to a new version (see SKIP_WAITING below),
    // except on the very first install, where there is nothing to replace.
    if (!self.registration.active) self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((key) => !key.startsWith(VERSION)).map((key) => caches.delete(key)));
        if (self.registration.navigationPreload) await self.registration.navigationPreload.enable();
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    const type = event.data && event.data.type;
    if (type === 'SKIP_WAITING') self.skipWaiting();
    if (type === 'CLEAR_USER_DATA') event.waitUntil(caches.delete(PAGES));
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const reqUrl = new URL(request.url);
    if (!reqUrl.protocol.startsWith('http')) return;

    if (request.mode === 'navigate') {
        event.respondWith(isPrivate(reqUrl) ? privatePage(event) : publicPage(event));
        return;
    }

    if (reqUrl.origin === BASE.origin) {
        if (isPrivate(reqUrl)) return; // API calls, uploads etc.: straight to the network.
        if (request.destination === 'image') {
            event.respondWith(staleWhileRevalidate(request, IMAGES));
        } else if (['style', 'script', 'font', 'manifest'].includes(request.destination)) {
            event.respondWith(staleWhileRevalidate(request, ASSETS));
        }
        return;
    }

    if (CDN_HOSTS.includes(reqUrl.hostname)) {
        event.respondWith(staleWhileRevalidate(request, ASSETS));
    } else if (request.destination === 'image' && IMAGE_HOSTS.some((h) => reqUrl.hostname.endsWith(h))) {
        event.respondWith(cacheFirst(request, IMAGES));
    }
});

// ---------------------------------------------------------------------------

function relativePath(reqUrl) {
    return reqUrl.pathname.startsWith(BASE.pathname)
        ? reqUrl.pathname.slice(BASE.pathname.length)
        : reqUrl.pathname.replace(/^\//, '');
}

function isPrivate(reqUrl) {
    if (reqUrl.origin !== BASE.origin) return false;
    const first = relativePath(reqUrl).split('/')[0];
    return PRIVATE_PREFIXES.includes(first);
}

function isCacheablePage(response) {
    return response && response.ok && response.type === 'basic'
        && (response.headers.get('Content-Type') || '').includes('text/html');
}

async function privatePage(event) {
    try {
        return (await event.preloadResponse) || (await fetch(event.request));
    } catch (e) {
        return offlineFallback();
    }
}

async function publicPage(event) {
    const cache = await caches.open(PAGES);
    try {
        const response = await withTimeout(
            (async () => (await event.preloadResponse) || fetch(event.request))(),
            NETWORK_TIMEOUT_MS,
        );
        if (isCacheablePage(response)) {
            event.waitUntil(cache.put(event.request, response.clone()).then(() => trim(PAGES)));
        }
        return response;
    } catch (e) {
        return (await cache.match(event.request))
            || (await cache.match(event.request, { ignoreSearch: true }))
            || offlineFallback();
    }
}

async function offlineFallback() {
    return (await caches.match(OFFLINE_URL)) || new Response('You are offline.', {
        status: 503,
        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
    });
}

async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    const network = fetch(request).then((response) => {
        if (response && (response.ok || response.type === 'opaque')) {
            cache.put(request, response.clone()).then(() => trim(cacheName));
        }
        return response;
    }).catch(() => cached);
    return cached || network;
}

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;
    try {
        const response = await fetch(request);
        if (response && (response.ok || response.type === 'opaque')) {
            await cache.put(request, response.clone());
            trim(cacheName);
        }
        return response;
    } catch (e) {
        return Response.error();
    }
}

async function trim(cacheName) {
    const max = LIMITS[cacheName];
    if (!max) return;
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    // Oldest entries first: delete until within the limit.
    for (let i = 0; i < keys.length - max; i++) await cache.delete(keys[i]);
}

function withTimeout(promise, ms) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('timeout')), ms);
        promise.then((value) => { clearTimeout(timer); resolve(value); }, (err) => { clearTimeout(timer); reject(err); });
    });
}
