// plugins/i18n.js
import { createI18n, useI18n } from "vue-i18n";
import { fr as vFr, en as vEn, es as vEs } from 'vuetify/locale';
import rawMessages from "@/locales";

let lang = localStorage.getItem("language");
if (lang == null) {
    lang = "en";
    localStorage.setItem('language', lang);
}

// Seamlessly merge Vuetify's built-in component translations ($vuetify namespace)
const messages = {
    fr: {
        $vuetify: vFr,
        ...(rawMessages.fr || {})
    },
    en: {
        $vuetify: vEn,
        ...(rawMessages.en || {})
    },
    es: {
        $vuetify: vEs,
        ...(rawMessages.es || {})
    }
};

export { useI18n };

export default createI18n({
    locale: lang,
    messages,
    legacy: false,
    globalInjection: true,
    silentTranslationWarn: true,
    missingWarn: false,
    fallbackWarn: false,
});
