/**
 * plugins/vuetify.js
 *
 * Framework documentation: https://vuetifyjs.com`
 */

// Styles
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import en from "@/locales/en/app.json";
import fr from "@/locales/fr/app.json";
import es from "@/locales/es/app.json";
// Composables

import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import _i18n  from './i18n';

export const  i18n = _i18n;

//detects theme
if (!localStorage.getItem("theme")) {
    localStorage.setItem("theme", window.matchMedia('(prefers-color-scheme: dark)').matches ? "dark" : "light")
}
const theme = localStorage.getItem("theme");
// https://vuetifyjs.com/en/introduction/why-vuetify/#feature-guides
export function crerate() {
    return createVuetify({
        theme: {
            defaultTheme: theme,
        },
        components,
        directives,
        messages: {
            fr: {
                // Your French translations
                ...fr, // Merge Vuetify's French translations
            },
            en: {
                // Ensure English translations are present
                ...en, // Merge Vuetify's English translations (fallback)
            },
            es: {
                // Ensure English translations are present
                ...es, // Merge Vuetify's English translations (fallback)
            }
        },
    })
};


