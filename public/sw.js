const CACHE_NAME = "pakdoelnet-pwa-cache-v3";
const ASSETS_TO_CACHE = [];

// Install event
self.addEventListener("install", (event) => {
  self.skipWaiting();
});

// Activate event - clean up all old caches
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          return caches.delete(cache);
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - network only, bypass all caches
self.addEventListener("fetch", (event) => {
  event.respondWith(fetch(event.request));
});

// Handle skipWaiting message from client
self.addEventListener("message", (event) => {
  if (event.data && event.data.action === "skipWaiting") {
    self.skipWaiting();
  }
});
