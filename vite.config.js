import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";
import Fonts from 'unplugin-fonts/vite';
import Layouts from 'vite-plugin-vue-layouts';
import fs from 'node:fs';

let appVersionInfo = { version: '1.0.1', build: 1, build_time: '', commit: '' };
try {
    const raw = fs.readFileSync(new URL('./version.json', import.meta.url), 'utf8');
    appVersionInfo = JSON.parse(raw);
} catch (e) {}

export default defineConfig({
    define: {
        __APP_VERSION__: JSON.stringify(appVersionInfo.version),
        __APP_BUILD__: JSON.stringify(appVersionInfo.build),
        __APP_BUILD_TIME__: JSON.stringify(appVersionInfo.build_time),
        __APP_COMMIT__: JSON.stringify(appVersionInfo.commit),
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        Layouts(),
        vue(),
        Fonts({
            google: {
                families: [{
                    name: 'Roboto',
                    styles: 'wght@100;300;400;500;700;900',
                }],
            },
        }),
    ],
    // Add this server block right here:
    server: {
        host: '127.0.0.1', // Forces IPv4 instead of IPv6 [::1]
        cors: true,        // Allows cross-origin requests from dev.local
    },
    resolve: {
        alias: {
            vue: "vue/dist/vue.esm-bundler.js",
            '@': fileURLToPath(new URL('./resources/js', import.meta.url))
        },
        extensions: [
            '.js',
            '.json',
            '.jsx',
            '.mjs',
            '.ts',
            '.tsx',
            '.vue',
        ],
    },
    build: { chunkSizeWarningLimit: 11600, },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
                silenceDeprecations: ['import', 'legacy-js-api']
            }
        }
    }

});
