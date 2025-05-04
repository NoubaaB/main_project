/**
 * plugins/vuetify.js
 *
 * Framework documentation: https://vuetifyjs.com`
 */

// Styles
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

// Composables

import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
//intarnetionalization
import { createI18n, useI18n } from "vue-i18n";
import { createVueI18nAdapter } from 'vuetify/locale/adapters/vue-i18n';
/* multi-languages */
import messages from "@/locales";

let lang = localStorage.getItem("language");
if (lang == null) {
    lang = "en";
    localStorage.setItem('language', lang);

}
export const i18n = createI18n({
    locale: lang,
    messages,
    legacy: false,
    globalInjection: true,
    silentTranslationWarn: true
})

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
        locale: {
            adapter: createVueI18nAdapter({ i18n, useI18n }),
        },
    })
};


