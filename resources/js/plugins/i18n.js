// plugins/i18n.js

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

export default createI18n({
    locale: lang,
    messages,
    legacy: false,
    globalInjection: true,
    silentTranslationWarn: true
})