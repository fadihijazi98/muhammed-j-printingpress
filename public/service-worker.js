/*
 * Caches the shell (styles, script, icons) so the app installs as a desktop app
 * and still opens if the server is briefly unavailable.
 *
 * The server runs on this same machine, so the cache is a fallback, never the
 * first choice: a cache-first worker would keep serving yesterday's stylesheet
 * after the app is updated. Pages themselves are never cached — every screen
 * shows live figures from the database.
 */

const CACHE = 'mjpress-shell-v2';

const SHELL = [
    '/assets/app.css',
    '/assets/app.js',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/manifest.webmanifest'
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE).then(function (cache) {
            return cache.addAll(SHELL);
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) {
                    return key !== CACHE;
                }).map(function (key) {
                    return caches.delete(key);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    const isShellAsset = SHELL.indexOf(url.pathname) !== -1;

    if (!isShellAsset) {
        return;
    }

    event.respondWith(
        fetch(request).then(function (response) {
            if (response && response.ok) {
                const copy = response.clone();

                caches.open(CACHE).then(function (cache) {
                    cache.put(request, copy);
                });
            }

            return response;
        }).catch(function () {
            return caches.match(request);
        })
    );
});
