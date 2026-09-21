/**
 * Fish Farm ERP — Service Worker
 * ---------------------------------------------------------------------------
 * Strategy (documented in docs/PWA.md):
 *
 *   Static assets (CSS/JS/icons/fonts)  → cache-first, versioned
 *   HTML navigations                    → network-first, offline fallback page
 *   Everything else (data, POST, API)   → network only; NEVER cached
 *
 * We intentionally do NOT cache authenticated responses or any non-GET
 * request. The ERP requires the server for data; the service worker must not
 * imply that a write succeeded while offline.
 *
 * Bump CACHE_VERSION to invalidate old caches on the next activation.
 */

const CACHE_VERSION = "fishfarm-v1";
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PAGES_CACHE = `${CACHE_VERSION}-pages`;
const OFFLINE_URL = "/offline.html";

/* Assets pre-cached on install. Only public, non-sensitive files belong here. */
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    "/manifest.webmanifest",
    "/favicon.ico",
    "/icons/icon-192.png",
    "/icons/icon-512.png",
];

/* ------------------------------------------------------------------ install */
self.addEventListener("install", (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(STATIC_CACHE);
            // addAll fails the whole install if one asset 404s, so add defensively.
            await Promise.all(
                PRECACHE_ASSETS.map((url) =>
                    cache
                        .add(new Request(url, { cache: "reload" }))
                        .catch(() => null),
                ),
            );
            // Do not skipWaiting automatically: a new SW waits for the user to
            // refresh, so the page never changes underneath them mid-workflow.
        })(),
    );
});

/* ----------------------------------------------------------------- activate */
self.addEventListener("activate", (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter((key) => !key.startsWith(CACHE_VERSION))
                    .map((key) => caches.delete(key)),
            );
            await self.clients.claim();
        })(),
    );
});

/* -------------------------------------------------------------------- fetch */
self.addEventListener("fetch", (event) => {
    const { request } = event;

    // Only handle GET. POST/PUT/PATCH/DELETE always go to the network.
    if (request.method !== "GET") return;

    // Never touch other origins.
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Never cache authenticated/dynamic data routes.
    if (
        url.pathname.startsWith("/api/") ||
        url.pathname.startsWith("/storage/")
    )
        return;

    // HTML navigations → network-first with offline fallback.
    if (request.mode === "navigate") {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    // Static assets → cache-first.
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
    }
});

/* ------------------------------------------------------------------ helpers */
function isStaticAsset(pathname) {
    return /\.(css|js|mjs|png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|eot)$/i.test(
        pathname,
    );
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok && response.type === "basic") {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        // Nothing cached and offline: let the browser handle it.
        throw error;
    }
}

async function networkFirstNavigation(request) {
    try {
        const response = await fetch(request);

        // Cache a copy of successful, same-origin HTML pages so previously
        // visited pages remain viewable offline (read-only, stale-while-offline).
        if (response.ok && response.type === "basic") {
            const cache = await caches.open(PAGES_CACHE);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) return cached;

        // Final fallback: the static offline page.
        const offline = await caches.match(OFFLINE_URL);
        if (offline) return offline;

        return new Response("You are offline.", {
            status: 503,
            headers: { "Content-Type": "text/plain; charset=utf-8" },
        });
    }
}

/* ----------------------------------------------------------------- messages */
self.addEventListener("message", (event) => {
    // Explicit, user-triggered update: apply the waiting worker.
    if (event.data === "SKIP_WAITING") {
        self.skipWaiting();
    }
});
