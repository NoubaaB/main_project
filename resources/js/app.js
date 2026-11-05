import "./bootstrap";
// Plugins
import { registerPlugins } from '@/plugins/create.js';

// Components
import App from './App.vue';

// Composa bles  
import * as Vue from 'vue';
import { createApp } from 'vue';

if (typeof window !== 'undefined') {
    window.Vue = Vue;
}

import { Swiper } from 'swiper/dist/js/swiper.esm.js';
window.Swiper = Swiper;

const app = createApp(App)
app.config.globalProperties.clearInterval = window.clearInterval;
app.config.globalProperties.setInterval = window.setInterval;

registerPlugins(app).then(res => {
    app.mount('#app')
})

