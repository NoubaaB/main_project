import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";
import Fonts from 'unplugin-fonts/vite';
import Layouts from 'vite-plugin-vue-layouts';
export default defineConfig({
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
