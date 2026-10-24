<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- <link rel="icon" type="image/gif" href="/storage/atom.gif"> -->
    <link rel="icon" type="image/png" href="/storage/icons/icon-144x144.png" sizes="144x144" async defer>
    <link id="pwa-manifest-link" rel="manifest" href="{{asset('/js/manifest.json')}}">
    <link rel="apple-touch-icon" href="/storage/icons/icon-192x192.png" sizes="192x192">
    <meta name="theme-color" id="meta-theme-color" content="#121212" />
    <meta name="background_color" id="meta-background-color" content="#121212" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" id="meta-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Jupiter">
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('theme');
                var isDark = savedTheme ? (savedTheme === 'dark') : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                var themeColor = isDark ? '#121212' : '#FFFFFF';
                var metaTheme = document.getElementById('meta-theme-color');
                if (metaTheme) metaTheme.setAttribute('content', themeColor);
                var metaBg = document.getElementById('meta-background-color');
                if (metaBg) metaBg.setAttribute('content', themeColor);
                var metaStatus = document.getElementById('meta-status-bar-style');
                if (metaStatus) metaStatus.setAttribute('content', isDark ? 'black-translucent' : 'default');
                var manifestLink = document.getElementById('pwa-manifest-link');
                if (manifestLink) manifestLink.setAttribute('href', '/manifest.json?theme=' + (isDark ? 'dark' : 'light'));
                document.documentElement.classList.add(isDark ? 'theme-dark' : 'theme-light');
                document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
            } catch(e) {}
        })();
    </script>

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1, minimum-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <link href="{{asset('/css/main.css')}}" rel="stylesheet">
    <link href="{{asset('/css/vuetify.min.css')}}" rel="stylesheet">
    <link href="{{asset('/css/materialdesignicons.min.css')}}" rel="stylesheet">
    <script src="{{asset('/js/vue.global.js')}}"></script>
    <script src="{{asset('/js/vuetify.min.js')}}"></script>
    <script src="{{asset('/js/apexcharts.js')}}"></script>
    <script type="importmap">
        {
            "imports": {
                "vue": "{{asset('/js/vue.esm-browser.js')}}",
                "vuetify": "{{asset('/js/vuetify.esm.js')}}"
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
                .then(function (registration) {
                    console.log('Service Worker registered with scope:', registration.scope);
                }).catch(function (error) {
                    console.log('Service Worker registration failed:', error);
                });
        }
    </script>
</body>

</html>