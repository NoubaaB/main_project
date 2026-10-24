import router from "@/router";
import "@mdi/font/css/materialdesignicons.css" // Ensure you are using css-loader
import "vuetify/styles";
import { crerate, i18n } from './vuetify';
import VueApexCharts from "vue3-apexcharts";
import { SnackbarService, Vue3Snackbar } from "vue3-snackbar";
import "vue3-snackbar/styles";

import methods from "@/plugins/methods.js";
import { useAuth } from '@/stores/Auth';
import { useMain } from '@/stores/Main';
import { usePwa } from '@/stores/pwa';
import { setupCalendar, DatePicker } from 'v-calendar';
import 'v-calendar/style.css';

import { setActivePinia } from 'pinia';
import pinia from "@/stores";
export async function registerPlugins(app) {
    setActivePinia(pinia);
    app
        .use(crerate())
        .use(pinia)
        .use(setupCalendar, {})
        .component('DatePicker', DatePicker)
        .use(i18n)
        .use(VueApexCharts)
        .use(SnackbarService)
        .component("vue3-snackbar", Vue3Snackbar)
        .mixin(
            {
                methods,
            }
        );

    // Prevent Vue dev warnings from crashing browser extension console interceptors
    // that try to String() reactive proxy objects
    app.config.warnHandler = (msg, instance, trace) => {
        // Log only the string message, avoiding reactive proxy serialization
        console.log('[Vue warn]:', msg);
    };
    if (localStorage.getItem("token")) {
        try {
            await useAuth(pinia).attemp().catch(err => {
                router.push({ name: "login" });
                return err;
            });
        } catch (e) {
            console.error("Auth attempt error:", e);
            router.push({ name: "login" });
        }
    }
    useMain(pinia).initData();
    usePwa(pinia).init();

    app.use(router);
}
