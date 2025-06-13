<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#3b82f6">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Mitarbeiterportal">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="application-name" content="Mitarbeiterportal">
        
        <!-- Apple Touch Icons -->
        <link rel="apple-touch-icon" sizes="72x72" href="/images/icons/icon-72x72.png">
        <link rel="apple-touch-icon" sizes="96x96" href="/images/icons/icon-96x96.png">
        <link rel="apple-touch-icon" sizes="128x128" href="/images/icons/icon-128x128.png">
        <link rel="apple-touch-icon" sizes="144x144" href="/images/icons/icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
        <link rel="apple-touch-icon" sizes="192x192" href="/images/icons/icon-192x192.png">
        <link rel="apple-touch-icon" sizes="384x384" href="/images/icons/icon-384x384.png">
        <link rel="apple-touch-icon" sizes="512x512" href="/images/icons/icon-512x512.png">
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/icon-16x16.png">
        
        <!-- Manifest -->
        <link rel="manifest" href="/manifest.json">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        
        <!-- PWA Installation Script -->
        <script>
            // Service Worker registrieren
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(registration => {
                            console.log('SW registered: ', registration);
                        })
                        .catch(registrationError => {
                            console.log('SW registration failed: ', registrationError);
                        });
                });
            }

            // PWA Installation
            let deferredPrompt;
            let installButton;

            window.addEventListener('beforeinstallprompt', (e) => {
                console.log('beforeinstallprompt fired');
                e.preventDefault();
                deferredPrompt = e;
                
                // Installation Banner anzeigen
                showInstallBanner();
            });

            window.addEventListener('appinstalled', (evt) => {
                console.log('App wurde installiert');
                hideInstallBanner();
            });

            function showInstallBanner() {
                // Banner erstellen falls noch nicht vorhanden
                if (!document.getElementById('pwa-install-banner')) {
                    const banner = document.createElement('div');
                    banner.id = 'pwa-install-banner';
                    banner.style.cssText = `
                        position: fixed;
                        bottom: 20px;
                        left: 20px;
                        right: 20px;
                        background: #3b82f6;
                        color: white;
                        padding: 15px;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        z-index: 1000;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        font-family: system-ui, -apple-system, sans-serif;
                    `;
                    
                    banner.innerHTML = `
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;">App installieren</div>
                            <div style="font-size: 14px; opacity: 0.9;">Installieren Sie das Mitarbeiterportal für schnelleren Zugriff</div>
                        </div>
                        <div>
                            <button id="pwa-install-btn" style="
                                background: white;
                                color: #3b82f6;
                                border: none;
                                padding: 8px 16px;
                                border-radius: 6px;
                                font-weight: 600;
                                margin-right: 8px;
                                cursor: pointer;
                            ">Installieren</button>
                            <button id="pwa-dismiss-btn" style="
                                background: transparent;
                                color: white;
                                border: 1px solid rgba(255,255,255,0.3);
                                padding: 8px 12px;
                                border-radius: 6px;
                                cursor: pointer;
                            ">×</button>
                        </div>
                    `;
                    
                    document.body.appendChild(banner);
                    
                    // Event Listeners
                    document.getElementById('pwa-install-btn').addEventListener('click', installPWA);
                    document.getElementById('pwa-dismiss-btn').addEventListener('click', hideInstallBanner);
                }
            }

            function hideInstallBanner() {
                const banner = document.getElementById('pwa-install-banner');
                if (banner) {
                    banner.remove();
                }
            }

            function installPWA() {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                        } else {
                            console.log('User dismissed the install prompt');
                        }
                        deferredPrompt = null;
                        hideInstallBanner();
                    });
                }
            }

            // Offline-Status überwachen
            window.addEventListener('online', () => {
                console.log('App ist wieder online');
                // Offline-Banner entfernen falls vorhanden
                const offlineBanner = document.getElementById('offline-banner');
                if (offlineBanner) {
                    offlineBanner.remove();
                }
            });

            window.addEventListener('offline', () => {
                console.log('App ist offline');
                showOfflineBanner();
            });

            function showOfflineBanner() {
                if (!document.getElementById('offline-banner')) {
                    const banner = document.createElement('div');
                    banner.id = 'offline-banner';
                    banner.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        background: #f59e0b;
                        color: white;
                        padding: 10px;
                        text-align: center;
                        z-index: 1001;
                        font-family: system-ui, -apple-system, sans-serif;
                        font-size: 14px;
                    `;
                    banner.textContent = 'Sie sind offline. Einige Funktionen sind möglicherweise nicht verfügbar.';
                    document.body.appendChild(banner);
                }
            }
        </script>
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>