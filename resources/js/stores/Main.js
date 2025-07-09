import { defineStore } from "pinia";
import { useReceipt } from "./Receipt";
import annyang from "annyang";

export const useMain = defineStore("main", {
    state: () => {
        return {
            menu_drawer: false,
            command_drawer: false,
            currency: "Dh",
            is_dark: localStorage.getItem("theme") == "dark",
            langs: [
                {
                    id: 1,
                    short: "en",
                    code: "en",
                    // annyang: "en-US",
                    annyang: "fr-FR",
                },
                {
                    id: 2,
                    short: "fr",
                    code: "fr",
                    annyang: "fr-FR",
                },
                {
                    id: 3,
                    short: "ma",
                    code: "ar",
                    annyang: "ar-MA",
                },
            ],
            lang:null
        };
    },
    actions: {
        initData: function () {
            let receipt = localStorage.getItem("receipt") || '{}';
            Object.assign(useReceipt().receipt, JSON.parse(receipt))
            useReceipt().selectedCharge = localStorage.getItem("selectedCharge")=="null"? null : localStorage.getItem("selectedCharge");
            useReceipt().showAdditionalInfo = localStorage.getItem("showAdditionalInfo")=="true";
            let language = localStorage.getItem("language");
            let lang = null;
            if (language) {
                lang = this.langs.find(l => l.code == language);
            } else {
                lang = this.langs[0];
            }
            annyang.setLanguage(lang.annyang);
            this.lang = lang;
        }
    },
    getters: {
        color_theme: (state) => {
            return state.is_dark ? '#212121' : 'white'
        },
        color_theme_reverse: (state) => {
            return !state.is_dark ? '#212121' : 'white'
        },
        mode: (state) => {
            return state.is_dark ? "dark" : "light"
        },
        is_keybaord_set : (_) => {
            const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
            const userAgent = navigator.userAgent;

            // List of platforms that typically do not have physical keyboards
            const touchDevicePatterns = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;

            if (hasTouch && touchDevicePatterns.test(userAgent)) {
                return false; // Likely a touch device without a physical keyboard
            }

            return true; // Default to assuming a physical keyboard is present
        }
    }
});