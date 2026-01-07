import { defineStore } from "pinia";
import { useCharge } from "./Charge";
import annyang from "annyang";
import moment from "moment";
import { usePackageType } from "./PackageType";
import { useGuanoType } from "./GuanoType";
import { useFishType } from "./FishType";
import { useFishFamily } from "./FishFamily";
import { useWarehouse } from "./Warehouse";
export const useMain = defineStore("main", {
    state: () => {
        return {
            menu_drawer: false,
            command_drawer: false,
            not_aviable_color: "grey",
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
            lang: null,
            product_ids:[],
            warehouse_ids:[],
            package_type:usePackageType(),
            guano_type:useGuanoType(),
            fish_type:useFishType(),
            charge:useCharge(),
            fish_family:useFishFamily(),
            warehouse:useWarehouse()
        };
    },
    actions: {
        initData: function () {
            let language = localStorage.getItem("language");
            let lang = null;
            if (language) {
                lang = this.langs.find(l => l.code == language);
            } else {
                lang = this.langs[0];
            }
            annyang.setLanguage(lang.annyang);
            this.lang = lang;

            //initialize the charge store with data from localStorage
            let charge = localStorage.getItem("charge") || '{}';
            if (charge == '{}') {
                this.charge.add_charge();  
            } else {
                Object.assign(this.charge.charge, JSON.parse(charge));
            }

            //initialize the charges store with data from localStorage
            let charges = localStorage.getItem("charges") || '[]';
            this.charge.charges = JSON.parse(charges);
            
            //other settings
            this.charge.input_tracking_number = localStorage.getItem("input_tracking_number") == "null" ? null : localStorage.getItem("input_tracking_number");
            this.charge.showAdditionalInfo = localStorage.getItem("showAdditionalInfo") == "true";
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
        formateDateCustom: function (date,format) {
            return moment(date).format(format);
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
        escapeHtml: function(unsafe) {
            if (typeof unsafe !== 'string') return '';
            return unsafe
                .replaceAll("\n", '<br>')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        },
        add_snackbar: function (message) {
            this.snackbar = { ...message };
        },
        getStockableName: function (stockable_id, stockable_type) {
            // return stockable_type + stockable_id;
            let type = null
            switch (stockable_type) {
                case "PackageType":
                    type = this.getPackageTypes.find(e => e.id == stockable_id);
                    break;
                case "FishType":
                    type = this.getFishTypes.find(e => e.id == stockable_id);
                    break;
                case "GuanoType":
                    type = this.getGuanoTypes.find(e => e.id == stockable_id);
                    break;
                default:
                    return isNaN(stockable_id) ? stockable_id : i18n.global.t("add_description");
            }
            if (!!type) {
                return type[`name_${this.lang.code}`];
            } else {
                return "N/A"
            }
        },
        getPackageName: function (package_type_id) {
            const package_type = this.getPackageTypes.find(package_type => package_type.id === package_type_id);
            return package_type ? package_type[`name_${this.lang.code}`] : 'N/A';
        },
        getWarehouseName: function (warehouse_id) {
            const warehouse = this.getWarehouses.find(warehouse => warehouse.id === warehouse_id);
            return warehouse ? warehouse.name : 'N/A';
        },
        getStockableFishFamily: function (stockable_id, stockable_type) {
            let type = this.getFishTypes.find(e => e.id == stockable_id);
            let is_fish = (stockable_type == "FishType");
            if (!!type && is_fish) {
                let fish_family = this.getFishFamily.find(f => f.id == type.fish_family_id)
                if (!!fish_family) {
                    return fish_family[`name_${this.lang.code}`];
                }
            }
            return "N/A"
        },
        getClassificationColor: function (stockable_type) {
            switch (stockable_type) {
                case "FishType":
                    return "blue";            
                case "GuanoType":
                    return "gray";            
                case "PackageType":
                    return "green";            
                default:
                    "grey"
                    return;
            }
        },
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
        },
        getFishTypes: (state) => {
            return state.fish_type.getList;
        },
        getPackageTypes: (state) => {
            return state.package_type.getList;
        },
        getGuanoTypes: (state) => {
            return state.guano_type.getList;
        },
        getWarehouses: (state) => {
            return state.warehouse.getList;
        },
        getFishFamily: (state) => {
            return state.fish_family.getList;
        }
    }
});