/* Service Worker — Plataforma push notifications */

self.addEventListener('push', (e) => {
  const data = e.data?.json() ?? {};
  e.waitUntil(
    self.registration.showNotification(data.title ?? 'Plataforma', {
      body: data.body ?? '',
      icon: data.icon ?? '/favicon.ico',
      badge: '/favicon.ico',
      data: { url: data.url ?? '/' },
    })
  );
});

self.addEventListener('notificationclick', (e) => {
  e.notification.close();
  const target = e.notification.data?.url ?? '/';
  e.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((wins) => {
      for (const win of wins) {
        if (win.url === target && 'focus' in win) return win.focus();
      }
      if (clients.openWindow) return clients.openWindow(target);
    })
  );
});
