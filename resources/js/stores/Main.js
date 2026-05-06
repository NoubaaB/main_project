import { defineStore } from "pinia";
import { useCharge } from "./Charge";
import { useShipping } from "./Shipping";
import annyang from "annyang";
import moment from "moment";
import { usePackageType } from "./PackageType";
import { useGuanoType } from "./GuanoType";
import { useFishType } from "./FishType";
import { useFishFamily } from "./FishFamily";
import { useWarehouse } from "./Warehouse";
import { useChargeCategory } from "./ChargeCategory";
import { useSupplier } from "./Supplier";
import { useTruck } from "./Truck";
import { useShippingCategory } from "./ShippingCategory";
import { useClient } from "./Client";
import i18n  from '@/plugins/i18n';

export const useMain = defineStore("main", {
    state: () => {
        return {
            menu_drawer: false,
            sidebar_mini: false,
            command_drawer: false,
            not_aviable_color: "grey",
            currency: "Dh",
            currency_unit: "Dirham",
            currency_subunit: "Centime",
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
            fish_family:useFishFamily(),
            warehouse: useWarehouse(),
            charge_category: useChargeCategory(),
            supplier: useSupplier(),
            client: useClient(),
            truck: useTruck(),
            snackbar: {
                action_command:false,
                action_command_icon_color:"",
                action_command_icon:"",
                action_command_text:""
            }
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
        },
        fetchData: async function (date_start, date_end, api_url, api_url_prop) {
            try {
                const res = await axios.get(api_url, {
                    params: {
                        date_start: moment(date_start).format('YYYY-MM-DD'),
                        date_end: moment(date_end).format('YYYY-MM-DD')
                    }
                })
                return res.data
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
        capitalize: function (str) {
            return str
                .split(' ')
                .map(w =>
                    w
                        .split('-')
                        .map(p => p.charAt(0).toUpperCase() + p.slice(1))
                        .join('-')
                )
                .join(' ');

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
        // Returns name_in_invoice if set, otherwise falls back to localized name
        getStockableInvoiceName: function (stockable_id, stockable_type) {
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
                return type.name_in_invoice || type[`name_${this.lang.code}`];
            } else {
                return "N/A"
            }
        },
        getTruckNames: function (truck_ids) {
            if (typeof truck_ids == "object") {
                return truck_ids.map(truck_id => {
                    const truck = this.getTrucks.find(t => t.id === truck_id);
                    return truck ? truck.registration_plate : i18n.global.t("n/a");
                }).toString()
            }
            const truck = this.getTrucks.find(t => t.id === truck_ids);
            return truck ? truck.registration_plate : i18n.global.t("n/a");
        },
        getChargeCategoryName: function (charge_category_id) {
            const charge_category = this.getChargeCataegories.find(charge_category => charge_category.id === charge_category_id);
            return charge_category ? charge_category[`name_${this.lang.code}`] : i18n.global.t("n/a");
        },
        getShippingCategoryName: function (shipping_category_id) {
            const shipping_category = this.getShippingCategories.find(cat => cat.id === shipping_category_id);
            return shipping_category ? shipping_category[`name_${this.lang.code}`] : i18n.global.t("n/a");
        },
        getSupplierName: function (supplier_id) {
            const supplier = this.getSuppliers.find(supplier => supplier.id === supplier_id);
            return supplier ? supplier.name : i18n.global.t("n/a");
        },
        getClientName: function (client_id) {
            const client = this.getClients.find(client => client.id === client_id);
            return client ? client.name : i18n.global.t("n/a");
        },
        getSupplierAddress: function (supplier_id) {
            const supplier = this.getSuppliers.find(supplier => supplier.id === supplier_id);
            return supplier ? supplier.address : i18n.global.t("n/a");
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
        getTrucks: (state) => {
            return state.truck.getList;
        },
        getSuppliers: (state) => {
            return state.supplier.getList;
        },
        getClients: (state) => {
            return state.client.getList;
        },
        getChargeCataegories: (state) => {
            return state.charge_category.getList;
        },
        getShippingCategories: () => {
            const { useShippingCategory } = require('./ShippingCategory');
            return useShippingCategory().getList;
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

