import { defineStore } from "pinia";
import { useCharge } from "./Charge";
import annyang from "annyang";
import moment from "moment";
import { isNumber } from "lodash";

export const useMain = defineStore("main", {
    state: () => {
        return {
            menu_drawer: false,
            command_drawer: false,
            currency: "Dh",
            date_format: "DD/MM/YYYY",
            snackbar: {
                show: false,
                message: "",
                color:""
            },
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
                    id: 1,
                    short: "es",
                    code: "es",
                    annyang: "es-ES",
                },
                {
                    id: 2,
                    short: "fr",
                    code: "fr",
                    annyang: "fr-FR",
                },
                // {
                //     id: 3,
                //     short: "ma",
                //     code: "ar",
                //     annyang: "ar-MA",
                // },
            ],
            lang: null
        };
    },
    actions: {
        initData: function () {
            //initialize the charge store with data from localStorage
            let charge = localStorage.getItem("charge") || '{}';
            if (charge == '{}') {
                useCharge().add_charge();  
            } else {
                Object.assign(useCharge().charge, JSON.parse(charge));
            }

            //initialize the charges store with data from localStorage
            let charges = localStorage.getItem("charges") || '[]';
            useCharge().charges = JSON.parse(charges);
            
            //other settings
            useCharge().input_tracking_number = localStorage.getItem("input_tracking_number") == "null" ? null : localStorage.getItem("input_tracking_number");
            useCharge().showAdditionalInfo = localStorage.getItem("showAdditionalInfo") == "true";

            let language = localStorage.getItem("language");
            let lang = null;
            if (language) {
                lang = this.langs.find(l => l.code == language);
            } else {
                lang = this.langs[0];
            }
            annyang.setLanguage(lang.annyang);
            this.lang = lang;
        },
        fetchData: async function (date_start, date_end, api_url, api_url_prop) {
            try {
                const res = await axios.get(api_url, {
                    params: {
                        date_start: moment(date_start).format('YYYY-MM-DD'),
                        date_end: moment(date_end).format('YYYY-MM-DD')
                    }
                })
                return res.data[api_url_prop] || []
            } catch (err) {
                console.error(`Error fetching ${api_url}`, err)
            } 
        },
        formatNumber: function (num) {
            if (isNaN(num) || num === null || num === undefined) {
                return 0.00;
            }
            return parseFloat(parseFloat(num).toFixed(2))
        },
        formateDate: function (date) {
            return moment(date).format(this.date_format);
        },
        formatDisplayNumber: function (num) {
            if (isNaN(num) || num === null || num === undefined) {
                return 0.00;
            }
            // Ensure num is a float
            num = parseFloat(num)

            // Format using Intl.NumberFormat with French locale
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(num)
        },
        formatDisplayNumberNonFloat: function (num) {
            if (isNaN(num) || num === null || num === undefined) {
                return 0.00;
            }
            // Ensure num is a float
            num = parseFloat(num)

            // Format using Intl.NumberFormat with French locale
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(num)
        },
        add_snackbar: function (message) {
            this.snackbar = { ...message };
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
        is_keybaord_set: (_) => {
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