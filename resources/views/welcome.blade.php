<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- <link rel="icon" type="image/gif" href="/storage/atom.gif"> -->
    <link rel="icon" type="image/png" href="/storage/icons/icon-144x144.png" sizes="144x144" async defer>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/storage/icons/icon-144x144.png" sizes="144x144">
    <meta name="theme-color" content="#fff" />
    <meta name="background_color" content="#fff" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1, minimum-scale=1">
    <title>{{config('app.name')}} App</title>
    @vite(['resources/css/app.css'])
    <link href="https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script type="importmap">
        {
            "imports": {
                "vue": "https://cdn.jsdelivr.net/npm/vue@latest/dist/vue.esm-browser.js",
                "vuetify": "https://cdn.jsdelivr.net/npm/vuetify@3.7.15/dist/vuetify.esm.js"
            }
        }
    </script>

</head>

<body>
    <div id="app"></div>
    @vite(['resources/js/app.js'])

    <script>
        const {
            createApp
        } = Vue
        const {
            createVuetify
        } = Vuetify

        const vuetify = createVuetify();
    </script>
    <script>
        // Register the Service Worker 
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(function(registration) {
                    console.log('Service Worker registered with scope:', registration.scope);
                }).catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
        }
    </script>
</body>

</html>