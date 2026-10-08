/* PNG Market service worker — PWA installability + light offline shell + notifications */
var PNGM_SW_CACHE = 'pngm-shell-v13';
var PNGM_SHELL = [
  './',
  './manifest.webmanifest',
  './pwa/icon-192.png',
  './pwa/icon-512.png',
  './pwa/icon-512-maskable.png',
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

  // Let the browser handle page navigations. Intercepting them broke
  // redirects (Messages -> latest thread) and could leave the homepage on screen.
  if (req.mode === 'navigate') {
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
  var url = '/';
  try {
    if (event.notification && event.notification.data && event.notification.data.url) {
      url = String(event.notification.data.url) || url;
    }
  } catch (e) {}

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      var i;
      var c;
      for (i = 0; i < clientList.length; i++) {
        c = clientList[i];
        if (!c || !('focus' in c)) {
          continue;
        }
        try {
          if (c.url && new URL(c.url).origin === self.location.origin) {
            return (function (client, targetUrl) {
              return client.focus().then(function () {
                if (targetUrl && typeof client.navigate === 'function') {
                  return client.navigate(targetUrl);
                }
                if (targetUrl && typeof client.postMessage === 'function') {
                  client.postMessage({ type: 'pngm-navigate', url: targetUrl });
                }
              });
            })(c, url);
          }
        } catch (err) {}
      }
      if (url && self.clients.openWindow) {
        return self.clients.openWindow(url);
      }
    })
  );
});

self.addEventListener('message', function (event) {
  var data = event && event.data ? event.data : null;
  if (!data || data.type !== 'pngm-user') {
    return;
  }
  var id = parseInt(data.userId, 10) || 0;
  if (id > 0) {
    self.pngmUserId = id;
  }
});

/* VAPID Web Push payloads (JSON: title, body, url, icon, authorId) */
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
        if (parsed.authorId) data.authorId = parseInt(parsed.authorId, 10) || 0;
      }
    }
  } catch (e) {
    try {
      data.body = event.data ? event.data.text() : '';
    } catch (e2) {}
  }
  // Don't banner the person who just sent the message (same phone / same login).
  var authorId = parseInt(data.authorId, 10) || 0;
  var me = parseInt(self.pngmUserId, 10) || 0;
  if (authorId > 0 && me > 0 && authorId === me) {
    return;
  }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: data.icon,
      badge: './pwa/icon-192.png',
      data: { url: data.url },
      tag: 'pngm-push-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
      renotify: true,
      silent: false,
      vibrate: [200, 100, 200]
    }).then(function () {
      return self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
        list.forEach(function (client) {
          try { client.postMessage({ type: 'pngm-push-sound' }); } catch (e) {}
        });
      });
    })
  );
});
