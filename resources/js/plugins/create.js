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
import { setupCalendar, DatePicker } from 'v-calendar';
import 'v-calendar/style.css';

import pinia from "@/stores";
export async function registerPlugins(app) {
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
        await useAuth().attemp().then(res => {
            return res;
        }).catch(err => {
            router.push({ name: "login" });
            return err;
        });
    }
    useMain().initData();

    app.use(router);
}
