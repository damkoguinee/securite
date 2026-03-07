import './bootstrap.js';

/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.scss';
import './css/style.scss';

const $ = require("jquery");
global.$ = global.jQuery = $;

// require("bootstrap");

$(function() {
    console.log("chargement du js");
});

// Enregistrement du Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/build/service-worker.js')
            .then((registration) => {
                console.log('Service Worker enregistré avec succès:', registration);
            })
            .catch((error) => {
                console.error('Échec de l\'enregistrement du Service Worker:', error);
            });
    });
}


