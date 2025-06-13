const CACHE_NAME = 'mitarbeiterportal-v1.0.0';
const STATIC_CACHE = 'static-v1.0.0';
const DYNAMIC_CACHE = 'dynamic-v1.0.0';

// Dateien, die immer gecacht werden sollen
const STATIC_FILES = [
  '/',
  '/dashboard',
  '/calendar',
  '/vacation',
  '/parking',
  '/manifest.json',
  '/images/icons/icon-192x192.png',
  '/images/icons/icon-512x512.png'
];

// API-Routen, die gecacht werden sollen
const API_CACHE_PATTERNS = [
  /^\/api\/calendar\/company/,
  /^\/api\/vacation\/balance/,
  /^\/api\/event-types/,
  /^\/api\/user\/role/,
  /^\/api\/notifications\/birthdays/
];

// Installationsevent
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('Service Worker: Caching static files');
        return cache.addAll(STATIC_FILES);
      })
      .then(() => {
        console.log('Service Worker: Static files cached');
        return self.skipWaiting();
      })
      .catch(err => {
        console.error('Service Worker: Error caching static files', err);
      })
  );
});

// Aktivierungsevent
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
              console.log('Service Worker: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('Service Worker: Activated');
        return self.clients.claim();
      })
  );
});

// Fetch-Event für Netzwerkanfragen
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Nur GET-Requests cachen
  if (request.method !== 'GET') {
    return;
  }

  // API-Requests mit Cache-First-Strategie
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(cacheFirstStrategy(request));
    return;
  }

  // Statische Assets mit Cache-First-Strategie
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirstStrategy(request));
    return;
  }

  // HTML-Seiten mit Network-First-Strategie
  if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(networkFirstStrategy(request));
    return;
  }

  // Alle anderen Requests mit Network-First-Strategie
  event.respondWith(networkFirstStrategy(request));
});

// Cache-First-Strategie
async function cacheFirstStrategy(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      console.log('Service Worker: Serving from cache', request.url);
      return cachedResponse;
    }

    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
      console.log('Service Worker: Cached new resource', request.url);
    }
    return networkResponse;
  } catch (error) {
    console.error('Service Worker: Cache-first strategy failed', error);
    return new Response('Offline - Ressource nicht verfügbar', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Network-First-Strategie
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
      console.log('Service Worker: Updated cache from network', request.url);
    }
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: Network failed, trying cache', request.url);
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    // Fallback für HTML-Seiten
    if (request.headers.get('accept')?.includes('text/html')) {
      const fallbackResponse = await caches.match('/dashboard');
      if (fallbackResponse) {
        return fallbackResponse;
      }
    }

    return new Response('Offline - Keine Verbindung', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Prüfen ob es sich um ein statisches Asset handelt
function isStaticAsset(pathname) {
  const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2'];
  return staticExtensions.some(ext => pathname.endsWith(ext));
}

// Background Sync für Offline-Aktionen
self.addEventListener('sync', event => {
  console.log('Service Worker: Background sync triggered', event.tag);
  
  if (event.tag === 'vacation-request') {
    event.waitUntil(syncVacationRequests());
  }
  
  if (event.tag === 'parking-reservation') {
    event.waitUntil(syncParkingReservations());
  }
});

// Vacation Requests synchronisieren
async function syncVacationRequests() {
  try {
    const requests = await getStoredRequests('vacation-requests');
    for (const request of requests) {
      try {
        const response = await fetch('/api/vacation/submit', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': await getCSRFToken()
          },
          body: JSON.stringify(request.data)
        });
        
        if (response.ok) {
          await removeStoredRequest('vacation-requests', request.id);
          console.log('Service Worker: Vacation request synced successfully');
        }
      } catch (error) {
        console.error('Service Worker: Failed to sync vacation request', error);
      }
    }
  } catch (error) {
    console.error('Service Worker: Background sync failed', error);
  }
}

// Parking Reservations synchronisieren
async function syncParkingReservations() {
  try {
    const requests = await getStoredRequests('parking-reservations');
    for (const request of requests) {
      try {
        const response = await fetch('/api/parking/reserve', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': await getCSRFToken()
          },
          body: JSON.stringify(request.data)
        });
        
        if (response.ok) {
          await removeStoredRequest('parking-reservations', request.id);
          console.log('Service Worker: Parking reservation synced successfully');
        }
      } catch (error) {
        console.error('Service Worker: Failed to sync parking reservation', error);
      }
    }
  } catch (error) {
    console.error('Service Worker: Background sync failed', error);
  }
}

// Hilfsfunktionen für IndexedDB
async function getStoredRequests(storeName) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('OfflineRequests', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => {
      const db = request.result;
      const transaction = db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const getAllRequest = store.getAll();
      
      getAllRequest.onsuccess = () => resolve(getAllRequest.result);
      getAllRequest.onerror = () => reject(getAllRequest.error);
    };
    
    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains(storeName)) {
        db.createObjectStore(storeName, { keyPath: 'id' });
      }
    };
  });
}

async function removeStoredRequest(storeName, id) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('OfflineRequests', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => {
      const db = request.result;
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const deleteRequest = store.delete(id);
      
      deleteRequest.onsuccess = () => resolve();
      deleteRequest.onerror = () => reject(deleteRequest.error);
    };
  });
}

async function getCSRFToken() {
  try {
    const response = await fetch('/sanctum/csrf-cookie');
    const cookies = document.cookie.split(';');
    const csrfCookie = cookies.find(cookie => cookie.trim().startsWith('XSRF-TOKEN='));
    return csrfCookie ? decodeURIComponent(csrfCookie.split('=')[1]) : '';
  } catch (error) {
    console.error('Service Worker: Failed to get CSRF token', error);
    return '';
  }
}

// Push-Benachrichtigungen
self.addEventListener('push', event => {
  console.log('Service Worker: Push message received');
  
  const options = {
    body: event.data ? event.data.text() : 'Neue Benachrichtigung',
    icon: '/images/icons/icon-192x192.png',
    badge: '/images/icons/icon-72x72.png',
    vibrate: [200, 100, 200],
    data: {
      url: '/dashboard'
    },
    actions: [
      {
        action: 'open',
        title: 'Öffnen',
        icon: '/images/icons/icon-72x72.png'
      },
      {
        action: 'close',
        title: 'Schließen'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('Mitarbeiterportal', options)
  );
});

// Benachrichtigung geklickt
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notification clicked');
  
  event.notification.close();
  
  if (event.action === 'open' || !event.action) {
    const url = event.notification.data?.url || '/dashboard';
    event.waitUntil(
      clients.openWindow(url)
    );
  }
});