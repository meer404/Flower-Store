const CACHE_VERSION = '2026-03-27-1';
const CORE_CACHE = `core-${CACHE_VERSION}`;
const RUNTIME_CACHE = `runtime-${CACHE_VERSION}`;
const OFFLINE_PATH = 'offline.html';
const SYNC_TAG = 'pwa-sync-queue';
const QUEUE_DB = 'pwa-queue-db';
const QUEUE_STORE = 'requests';

const CORE_ASSETS = [
    'index.php',
    'shop.php',
    'manifest.json',
    'pwa.js',
    'service-worker.js',
    OFFLINE_PATH
];

const POST_QUEUE_PATHS = new Set([
    'cart_action.php',
    'wishlist_action.php'
]);

const toAbsolute = (path) => new URL(path, self.registration.scope).toString();

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CORE_CACHE).then((cache) => cache.addAll(CORE_ASSETS.map(toAbsolute)))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => ![CORE_CACHE, RUNTIME_CACHE].includes(key))
                .map((key) => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        event.respondWith(handleNonGet(event));
        return;
    }

    const url = new URL(event.request.url);
    const isSameOrigin = url.origin === self.location.origin;

    if (event.request.mode === 'navigate') {
        event.respondWith(networkFirst(event.request));
        return;
    }

    if (isSameOrigin && url.pathname.includes('/admin/')) {
        event.respondWith(fetch(event.request));
        return;
    }

    if (isSameOrigin && url.pathname.endsWith('product.php')) {
        event.respondWith(staleWhileRevalidate(event.request));
        return;
    }

    if (event.request.destination === 'image' || event.request.destination === 'style' || event.request.destination === 'script') {
        event.respondWith(cacheFirst(event.request));
        return;
    }

    event.respondWith(staleWhileRevalidate(event.request));
});

const networkFirst = async (request) => {
    try {
        const response = await fetch(request);
        const cache = await caches.open(RUNTIME_CACHE);
        if (isCacheable(response)) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        return caches.match(toAbsolute(OFFLINE_PATH));
    }
};

const cacheFirst = async (request) => {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    const response = await fetch(request);
    const cache = await caches.open(RUNTIME_CACHE);
    if (isCacheable(response)) {
        cache.put(request, response.clone());
    }
    return response;
};

const staleWhileRevalidate = async (request) => {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await cache.match(request);
    const networkFetch = fetch(request).then((response) => {
        if (isCacheable(response)) {
            cache.put(request, response.clone());
        }
        return response;
    });

    if (cached) {
        networkFetch.catch(() => undefined);
        return cached;
    }

    try {
        return await networkFetch;
    } catch (error) {
        return caches.match(toAbsolute(OFFLINE_PATH));
    }
};

const isCacheable = (response) => {
    if (!response) {
        return false;
    }
    if (response.status === 200) {
        return true;
    }
    return response.type === 'opaque';
};

const handleNonGet = async (event) => {
    const request = event.request;
    const url = new URL(request.url);
    const isSameOrigin = url.origin === self.location.origin;
    const isQueueTarget = isSameOrigin && POST_QUEUE_PATHS.has(url.pathname.split('/').pop() || '');
    const isNavigation = request.mode === 'navigate';

    if (!isQueueTarget || isNavigation) {
        return fetch(request);
    }

    try {
        return await fetch(request);
    } catch (error) {
        const queued = await queueRequest(request);
        if (queued) {
            if ('sync' in self.registration) {
                await self.registration.sync.register(SYNC_TAG);
            }
            return new Response(JSON.stringify({ success: true, queued: true }), {
                headers: { 'Content-Type': 'application/json' }
            });
        }
        return new Response(JSON.stringify({ success: false, queued: false }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
};

self.addEventListener('sync', (event) => {
    if (event.tag === SYNC_TAG) {
        event.waitUntil(replayQueuedRequests());
    }
});

const requestToPromise = (request) => new Promise((resolve, reject) => {
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});

const transactionDone = (tx) => new Promise((resolve, reject) => {
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
    tx.onabort = () => reject(tx.error);
});

const openQueueDb = () => new Promise((resolve, reject) => {
    const request = indexedDB.open(QUEUE_DB, 1);
    request.onupgradeneeded = () => {
        const db = request.result;
        if (!db.objectStoreNames.contains(QUEUE_STORE)) {
            db.createObjectStore(QUEUE_STORE, { keyPath: 'id', autoIncrement: true });
        }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});

const queueRequest = async (request) => {
    try {
        const db = await openQueueDb();
        const body = await request.clone().text();
        const headers = {};
        request.headers.forEach((value, key) => {
            headers[key] = value;
        });

        const tx = db.transaction(QUEUE_STORE, 'readwrite');
        const store = tx.objectStore(QUEUE_STORE);
        store.add({
            url: request.url,
            method: request.method,
            headers,
            body
        });
        await transactionDone(tx);
        db.close();
        return true;
    } catch (error) {
        return false;
    }
};

const replayQueuedRequests = async () => {
    const db = await openQueueDb();
    const readTx = db.transaction(QUEUE_STORE, 'readonly');
    const store = readTx.objectStore(QUEUE_STORE);
    const allItems = await requestToPromise(store.getAll());
    await transactionDone(readTx);

    for (const item of allItems) {
        try {
            await fetch(item.url, {
                method: item.method,
                headers: item.headers,
                body: item.body,
                credentials: 'include'
            });
            const deleteTx = db.transaction(QUEUE_STORE, 'readwrite');
            deleteTx.objectStore(QUEUE_STORE).delete(item.id);
            await transactionDone(deleteTx);
        } catch (error) {
            // Keep item for next sync attempt.
        }
    }
    db.close();
};

self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Bloom & Vine';
    const options = {
        body: data.body || 'New updates are available.',
        icon: data.icon || 'https://www.pwabuilder.com/assets/icons/icon_192.png',
        badge: data.badge || 'https://www.pwabuilder.com/assets/icons/icon_192.png',
        data: {
            url: data.url || 'index.php'
        }
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data && event.notification.data.url ? event.notification.data.url : 'index.php';
    event.waitUntil(clients.openWindow(toAbsolute(target)));
});
