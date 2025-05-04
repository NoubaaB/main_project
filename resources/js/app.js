import "./bootstrap";
// Plugins
import { registerPlugins } from '@/plugins/create.js';

// Components
import App from './App.vue';

// Composa bles  
import { createApp } from 'vue';

import { Swiper } from 'swiper/dist/js/swiper.esm.js';
window.Swiper = Swiper;

const app = createApp(App)

registerPlugins(app).then(res => {
    app.mount('#app')
})

