<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#4f46e5">
        <link rel="manifest" href="/manifest.json">
        
        <!-- Apple Mobile Web App Settings -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="PAK DOEL NET">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">

        <!-- Service Worker Registration -->
        <script>
            if ("serviceWorker" in navigator) {
                // Auto reload page when service worker updates and takes control
                let refreshing = false;
                navigator.serviceWorker.addEventListener('controllerchange', () => {
                    if (!refreshing) {
                        refreshing = true;
                        window.location.reload();
                    }
                });

                window.addEventListener("load", () => {
                    // Register service worker with timestamp to force fresh update check
                    navigator.serviceWorker.register("/sw.js?v=" + Date.now())
                        .then(reg => {
                            console.log("Service Worker registered:", reg.scope);
                            reg.addEventListener('updatefound', () => {
                                const newWorker = reg.installing;
                                newWorker.addEventListener('statechange', () => {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        newWorker.postMessage({ action: 'skipWaiting' });
                                    }
                                });
                            });
                        })
                        .catch(err => console.error("Service Worker registration failed:", err));
                });
            }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
