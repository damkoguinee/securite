// self.addEventListener('install', (event) => {
//     console.log('Service Worker: Installation en cours...');
//     event.waitUntil(
//         caches.open('v1').then((cache) => {
//             // console.log('Service Worker: Cache ouvert');
//             return cache.addAll([
//                 '/',
//                 '/index.html',
//                 '/styles.css',
//                 '/build/all-scripts.js',
//                 '/icons/icon-192x192.png',
//                 // Ajoutez d'autres fichiers à mettre en cache
//             ]);
//         })
//     );
// });

// self.addEventListener('fetch', (event) => {
//     console.log('Service Worker: Interception de la requête pour', event.request.url);
//     event.respondWith(
//         caches.match(event.request).then((response) => {
//             return response || fetch(event.request);
//         })
//     );
// });


self.addEventListener('install', (event) => {
    // console.log('Service Worker: Installation en cours...');
    event.waitUntil(
        caches.open('v1').then((cache) => {
            // console.log('Service Worker: Cache ouvert');
            return cache.addAll([
                '/',
                '/build/all-scripts.js', // Rajout de public/build
                '/build/icons/icon-192x192.png', // Rajout de public/build
                // Ajoutez d'autres fichiers à mettre en cache
            ]);
        })
    );
});

self.addEventListener('fetch', (event) => {
    console.log('Service Worker: Interception de la requête pour', event.request.url);
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});

