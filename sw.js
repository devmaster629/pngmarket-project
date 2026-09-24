/* PNG Market service worker — PWA installability + light offline shell + notifications */
var PNGM_SW_CACHE = 'pngm-shell-v2';
var PNGM_SHELL = [
  './',
  './manifest.webmanifest',
  './pwa/icon-192.png',
  './pwa/icon-512.png',
  './pwa/apple-touch-icon.png'
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(PNGM_SW_CACHE).then(function (cache) {
      return cache.addAll(PNGM_SHELL).catch(function () {
        // Partial cache is fine (paths may differ under rewrite).
      });
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (k) { return k !== PNGM_SW_CACHE; }).map(function (k) {
          return caches.delete(k);
        })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', function (event) {
  var req = event.request;
  if (req.method !== 'GET') {
    return;
  }
  var url = new URL(req.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  // Navigation: network-first, fall back to cached shell.
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(function () {
        return caches.match('./').then(function (cached) {
          return cached || caches.match(req);
        });
      })
    );
    return;
  }

  // Static PWA assets: cache-first.
  if (url.pathname.indexOf('/pwa/') === 0 || url.pathname.endsWith('manifest.webmanifest') || url.pathname.endsWith('/sw.js')) {
    event.respondWith(
      caches.match(req).then(function (cached) {
        return cached || fetch(req).then(function (res) {
          var copy = res.clone();
          caches.open(PNGM_SW_CACHE).then(function (cache) {
            cache.put(req, copy);
          });
          return res;
        });
      })
    );
  }
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = '';
  try {
    if (event.notification && event.notification.data && event.notification.data.url) {
      url = String(event.notification.data.url);
    }
  } catch (e) {}
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      if (url) {
        for (var i = 0; i < clientList.length; i++) {
          var c = clientList[i];
          if (c.url && 'focus' in c) {
            c.navigate(url);
            return c.focus();
          }
        }
        if (self.clients.openWindow) {
          return self.clients.openWindow(url);
        }
      } else if (clientList.length && 'focus' in clientList[0]) {
        return clientList[0].focus();
      }
    })
  );
});

/* VAPID Web Push payloads (JSON: title, body, url, icon) */
self.addEventListener('push', function (event) {
  var data = { title: 'PNG Market', body: '', url: '/', icon: './pwa/icon-192.png' };
  try {
    if (event.data) {
      var parsed = event.data.json();
      if (parsed && typeof parsed === 'object') {
        data.title = parsed.title || data.title;
        data.body = parsed.body || '';
        data.url = parsed.url || data.url;
        if (parsed.icon) data.icon = parsed.icon;
      }
    }
  } catch (e) {
    try {
      data.body = event.data ? event.data.text() : '';
    } catch (e2) {}
  }
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: data.icon,
      badge: './pwa/icon-192.png',
      data: { url: data.url },
      renotify: true,
      tag: 'pngm-push-' + String(data.title || '').slice(0, 32)
    })
  );
});
