import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        prefix: 'p',
                        darkModeSelector: '.dark',
                        cssLayer: false
                    }
                }
            });

        // PWA-spezifische Funktionen
        app.config.globalProperties.$pwa = {
            // Offline-Status prüfen
            isOnline: () => navigator.onLine,
            
            // Daten für Offline-Sync speichern
            storeForSync: async (storeName, data) => {
                if ('indexedDB' in window) {
                    try {
                        const db = await openDB();
                        const transaction = db.transaction([storeName], 'readwrite');
                        const store = transaction.objectStore(storeName);
                        await store.add({
                            id: Date.now(),
                            data: data,
                            timestamp: new Date().toISOString()
                        });
                        
                        // Background Sync registrieren
                        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
                            const registration = await navigator.serviceWorker.ready;
                            await registration.sync.register(storeName);
                        }
                    } catch (error) {
                        console.error('Fehler beim Speichern für Offline-Sync:', error);
                    }
                }
            },
            
            // Push-Benachrichtigungen aktivieren
            enableNotifications: async () => {
                if ('Notification' in window && 'serviceWorker' in navigator) {
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        const registration = await navigator.serviceWorker.ready;
                        // Hier würden Sie normalerweise den Push-Service konfigurieren
                        console.log('Push-Benachrichtigungen aktiviert');
                        return true;
                    }
                }
                return false;
            }
        };

        // IndexedDB für Offline-Funktionalität
        function openDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open('OfflineRequests', 1);
                
                request.onerror = () => reject(request.error);
                request.onsuccess = () => resolve(request.result);
                
                request.onupgradeneeded = () => {
                    const db = request.result;
                    
                    // Stores für verschiedene Offline-Aktionen erstellen
                    if (!db.objectStoreNames.contains('vacation-requests')) {
                        db.createObjectStore('vacation-requests', { keyPath: 'id' });
                    }
                    if (!db.objectStoreNames.contains('parking-reservations')) {
                        db.createObjectStore('parking-reservations', { keyPath: 'id' });
                    }
                    if (!db.objectStoreNames.contains('calendar-events')) {
                        db.createObjectStore('calendar-events', { keyPath: 'id' });
                    }
                };
            });
        }

        return app.mount(el);
    },
    progress: {
        color: '#3b82f6',
    },
});