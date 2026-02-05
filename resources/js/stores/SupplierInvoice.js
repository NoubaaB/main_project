import axios from "axios";
import { defineStore } from "pinia";
import i18n  from '@/plugins/i18n';
import { useMain } from "./Main";
import { generateRandomData } from "./RandomData.js";

let main = useMain();
let headers = [
    { align: "center", title: i18n.global.t("date"), key: 'date', visible: true },
    { align: "center", title: i18n.global.t("truck_id"), key: 'truck_id', visible: true },
    { align: "center", title: i18n.global.t("tracking_number_short"), key: 'tracking_numbers', visible: true },
    { align: "center", title: i18n.global.t("charge_category_id_short"), key: 'charge_category_id', visible: true },
    { align: "center", title: i18n.global.t("stockable"), key: 'stockable', visible: false },
    { align: "center", title: i18n.global.t("weight_kg"), key: 'total_weight', visible: true },
    { align: "center", title: i18n.global.t("unit_price_currency", { currency: main.currency }), key: 'unit_price', visible: true },
    { align: "center", title: i18n.global.t("price_dh", { currency: main.currency }), key: 'total_price', visible: true },
];
export const useSupplierInvoice = defineStore("supplierInvoice", {
    state: () => {
        return {
            loading:false,
            api_url: "/api/supplier_invoice",
            nbr_pages: 0,
            is_show: true,
            showAdditionalInfo: false,
            main: main,
            list: [],
            model: {
                name: "",
                total_paid: 0.00,
                total_non_paid: ""
            },
            final_page_rows_nbr: 35,
            active_page_rows_nbr: 32,
            allow_qr_code:false,
            allow_bg_image:true,
            use_simple_invoice: false,
            headers: headers,
            items: generateRandomData(3),
            g_items: [],
            selectedYear: new Date().getFullYear(),
            supplier_invoice: {},
            supplier_invoices: [],
            supplier_invoice_fetch_items: [],
            filtred_supplier_invoice_fetch_items: [],
            grouped_headers: { align: "center", title: i18n.global.t("records_count"), key: 'records_count', visible: true }
        };
    },
    actions: {
        add: async function (name) {
            return await axios.post("/api/supplierInvoices", {
                name
            }).then(res => {
                this.list.push(res.data.supplierInvoice);
                return res.data.supplierInvoice.id
            })
        },
        delete: function (supplierInvoice) {
            this.list = this.list.filter(e => e.id != supplierInvoice.id);
        },
        update: function (supplierInvoice) {
            console.log("supplierInvoice", supplierInvoice)
        },
        setGroupedItems: function (items) {
            this.g_items = items;
        },
        getName: function (id) {
            return this.list.find(e => e.id == id).name;
        },
        fetchSupplierInvoice: async function (date_start, date_end) {
            this.loading = true;
            let response = await this.main.fetchData(date_start, date_end, this.api_url)
            this.supplier_invoice_fetch_items = response.supplier_invoices;
            this.loading = false;
        },
    },
    getters: {
        getList: state => state.list,
        getMinList: state => state.list.map(e => { return { id: e.id, name: e.name } }),
        invoice_headers: (state) => { 
            return state.g_items.length > 0 ? [...headers, state.grouped_headers] : headers;
        },
        getHeaders: (state) => {
            return state.headers
        },
        getItems: function (state) {
            setTimeout(() => {
                this.nbr_pages = document.getElementsByClassName('_invoice_page').length;
            }, 0);
            return state.g_items.length == 0 ? state.items : state.g_items;
        }
    }
});

/*

[
    { 
        id: 1, 
        date: "2025-02-05", 
        tracking_numbers: ["0001","00002"], 
        supplier_ids: [8,5], 
        items : [
            { 
                date: "2025-10-01", 
                charge_id: 2, 
                charge_category_id: "Emballage", 
                truck_id: "15114-A-10", 
                tracking_numbers: ["100001","10002"],
                stockable: "MAQ SS", 
                stockable_type: "FishType", 
                total_weight: 200, 
                unit_price: 5, 
                total_price: 1000 
            },
        ],
        total_price:0000, 
        total_weight:0000 
    },
]

*/