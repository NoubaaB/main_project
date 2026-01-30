import axios from "axios";
import { defineStore } from "pinia";
import i18n  from '@/plugins/i18n';
import { useMain } from "./Main";
import {
    generateRecords,
    baseRecord,
    pools
} from "./RandomData.js";

let main = useMain();
let headers = [
    { align: "center", title: i18n.global.t("date"), key: 'date', visible: true },
    { align: "center", title: i18n.global.t("truck_id"), key: 'truck_id', visible: true },
    { align: "center", title: i18n.global.t("tracking_number_short"), key: 'tracking_numbers', visible: true },
    { align: "center", title: i18n.global.t("stockable"), key: 'stockable', visible: false },
    { align: "center", title: i18n.global.t("weight_kg"), key: 'total_weight', visible: true },
    { align: "center", title: i18n.global.t("unit_price_currency", { currency: main.currency }), key: 'unit_price', visible: true },
    { align: "center", title: i18n.global.t("price_dh", { currency: main.currency }), key: 'total_price', visible: true },
];
export const useSupplierInvoice = defineStore("supplierInvoice", {
    state: () => {
        return {
            loading:false,
            api_url: "/api/supplier_invoice/get_supplier_invoice",
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
            items: [],
            supplier_invoice: {},
            supplier_invoices: [],
            supplier_invoice_fetch_items: [],
            filtred_supplier_invoice_fetch_items: [],
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
        setGroupedItems: function(items){
            this.items = items;
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
            let grouped_headers = [{ align: "center", title: i18n.global.t("records_count"), key: 'records_count', visible: true }];

            if (state.items.length) return [...grouped_headers, ...headers]; return headers

        },
        getHeaders: (state) => {
            return state.headers
        },
        getItems:function(state){
            return generateRecords(baseRecord, pools, 73 ,false);
        }
    }
});