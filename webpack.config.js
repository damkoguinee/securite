const Encore = require('@symfony/webpack-encore');
const WebpackPwaManifest = require('webpack-pwa-manifest');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // Directory where compiled assets will be stored
    // .setOutputPath('build/')  // Dossier de sortie

    // Public path used by the web server to access the output path
    // .setPublicPath('/build')  // Chemin public pour correspondre à votre serveur

    // .setManifestKeyPrefix('build/')  // Préfixe utilisé dans le manifest

    
    .setOutputPath('public/build/')  

    .setPublicPath('/build')  

    .setManifestKeyPrefix('build/')  

    // Enabling image loader for images referenced in CSS/JS
    .configureImageRule({
        type: 'asset',
        maxSize: 8 * 1024 // Taille max pour l'encodage inline en base64
    })

    // Add your app.js entry file
    .addStyleEntry('all-styles', [
        './assets/css/style.scss',
    ])

    
    
    .addEntry('all-scripts', [
        './assets/app.js',
        './assets/js/ajax.js',
        './assets/js/ajaxClient.js',
        './assets/js/searchByAjax.js',
        './assets/js/form-loader.js',
        './assets/js/filters.js',
        './assets/js/formatMontant.js',
        './assets/js/main.js',
        './assets/js/map.js',
        './assets/js/option.js',
    ])

    // Enable various features
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    // Configure Babel
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })

    // Enables Sass/SCSS support
    .enableSassLoader()
    .autoProvidejQuery()

   .addPlugin(new WebpackPwaManifest({

        name: 'TechSecurity',
        short_name: 'Damko',
        description: "Application de gestion d'agences de sécurite.",

        background_color: '#ffffff',
        theme_color: '#317EFB',

        orientation: 'portrait',

        scope: '/',
        start_url: '/logescom/home/',

        id: '/appLogescom',

        display: "standalone",
        display_override: ["standalone", "minimal-ui"],

        icons: [
            {
                src: path.resolve('public/images/config/logopng.png'),
                sizes: [192, 256, 512],
                destination: 'icons'
            }
        ],

        screenshots: [
            {
                src: '/images/config/screenshot-desktop.png',
                sizes: '1280x720',
                type: 'image/png',
                form_factor: 'wide'
            },
            {
                src: '/images/config/screenshot-mobile.png',
                sizes: '720x1280',
                type: 'image/png'
            }
        ],

        filename: 'pwa-manifest.json'

    }))

    // Plugin pour copier le service worker
    .addPlugin(new CopyWebpackPlugin({
        patterns: [
            // Le fichier `service-worker.js` sera copié dans `public/build/`
            // { from: './assets/service-worker.js', to: 'public/build/service-worker.js' }

            {
                from: path.resolve(__dirname, 'assets/service-worker.js'), // Chemin source
                to: path.resolve(__dirname, 'public/build/service-worker.js') // Chemin de destination
            }
        ]
        
    }))
;

module.exports = Encore.getWebpackConfig();
