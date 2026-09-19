/* PNG Market service worker — notification click + ready for future Web Push */
self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
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

/* Placeholder for true Web Push payloads (VAPID) when server support is added */
self.addEventListener('push', function (event) {
  var data = { title: 'PNG Market', body: '', url: '/' };
  try {
    if (event.data) {
      var parsed = event.data.json();
      if (parsed && typeof parsed === 'object') {
        data.title = parsed.title || data.title;
        data.body = parsed.body || '';
        data.url = parsed.url || data.url;
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
      data: { url: data.url }
    })
  );
});
