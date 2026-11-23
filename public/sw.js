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
const VERSION = 'bt-2026-11-23-1';
// This release replaces a worker that could leave pages unstyled, so it takes
// over without waiting for the "new version" prompt. Set back to false next release.
const ACTIVATE_IMMEDIATELY = true;
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
const WARM_PAGES = ['', 'apartments', 'contact', 'about', 'faq', 'terms', 'cancellation-policy', 'privacy'].map(url);

const PRIVATE_PREFIXES = [
    'admin', 'staff', 'customer', 'login', 'logout', 'register', 'password',
    'forgot-password', 'reset-password', 'email', 'verify-email', 'auth',
    'booking', 'api', 'media', 'sanctum', 'owner', 'affiliate', 'front-desk',
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
    // except on the very first install, where there is nothing to replace, and
    // for releases that must replace a faulty worker straight away.
    if (!self.registration.active || ACTIVATE_IMMEDIATELY) self.skipWaiting();
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
        if (isPrivate(reqUrl) && request.destination !== 'image') return; // API calls, uploads etc.: straight to the network.
        if (relativePath(reqUrl).startsWith('build/')) {
            // Vite output is content-hashed and never changes: cache first.
            event.respondWith(cacheFirst(request, ASSETS));
        } else if (request.destination === 'image') {
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

// Fetch from the network; if the original request fails (seen with stylesheet
// requests on some servers), retry once as a plain same-origin GET.
async function fetchWithRetry(request) {
    try {
        return await fetch(request);
    } catch (e) {
        const url = new URL(request.url);
        if (url.origin !== BASE.origin) throw e;
        return fetch(url.href, { credentials: 'same-origin' });
    }
}

async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    const network = fetchWithRetry(request).then((response) => {
        if (response && (response.ok || response.type === 'opaque')) {
            cache.put(request, response.clone()).then(() => trim(cacheName));
        }
        return response;
    });

    if (cached) {
        network.catch(() => {}); // background refresh only
        return cached;
    }
    // Nothing cached: the page needs this file, so never answer with nothing.
    try {
        return await network;
    } catch (e) {
        return Response.error();
    }
}

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;
    try {
        const response = await fetchWithRetry(request);
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

// --- Push notifications & background click handling -------------------------
self.addEventListener('push', (event) => {
    let data = { title: 'BookTala', body: 'New notification from BookTala', url: './' };
    try {
        if (event.data) {
            data = Object.assign(data, event.data.json());
        }
    } catch (e) {
        if (event.data) data.body = event.data.text();
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: url('icons/icon-192.png'),
            badge: url('icons/favicon-32.png'),
            data: { url: data.url || './' },
            vibrate: [100, 50, 100],
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data && event.notification.data.url ? event.notification.data.url : './';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
