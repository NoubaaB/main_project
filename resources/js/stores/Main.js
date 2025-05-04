import { defineStore } from "pinia";
import { useCommand } from "./Command";
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
            let commands = localStorage.getItem("commands") || '[]';
            useCommand().commands = JSON.parse(commands);
            let deleted_commands = localStorage.getItem("deleted_commands") || '[]';
            useCommand().deleted_commands = JSON.parse(deleted_commands);
            let commands_serving = localStorage.getItem("commands_serving") || '[]';
            useCommand().commands_serving = JSON.parse(commands_serving);
            let commands_completed = localStorage.getItem("commands_completed") || '[]';
            useCommand().commands_completed = JSON.parse(commands_completed);
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