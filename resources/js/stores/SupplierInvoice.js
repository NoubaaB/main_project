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
            typeCharts: [
                {
                    value:"overview",
                    text: "supplier_invoice_list_overview",
                    chart_icon:"mdi-chart-box-multiple"
                },
                {
                    value: "by_supplier_invoice",
                    text: "by_supplier_invoice",
                    chart_icon: "mdi-chart-box-outline"
                }
            ],
            selected_supplier_id: null,
            selectTypeChart:'overview',
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
        delete: async function (ids) {
            let res = await axios.delete('/api/supplier_invoice', {
                params: {
                    ids
                }
            });
            this.supplier_invoice_fetch_items = this.supplier_invoice_fetch_items.filter(e => ids.includes(e.id) == false);
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
        getSupplierInvoicesByIds: async function (ids) {
            try {
                let res = await axios.get('/api/supplier_invoice/supplier-invoices-by-ids', {
                    params: {
                        ids
                    }
                });
                let data = this.formatedSupplierInvoiceData(res.data.supplier_invoices);
                return data;
            } catch (error) {
             console.log("error",error)   
            }
        },
        formatedSupplierInvoiceData: function (data) {
            return data.map(supplier_invoice => {
                return {
                    id: supplier_invoice.id,
                    date: supplier_invoice.date,
                    type_invoice: supplier_invoice.type_invoice,
                    items: supplier_invoice.charges.map(charge => {
                        let details = [...charge.non_warehouse_stockable_operations, ...charge.warehouse_stockable_tracking_number_operations];
                        return details.map(detail => {
                            const truck_ids = charge.truck_ids ? charge.truck_ids : detail.truck_ids;
                            return {
                                charge_category_id: this.main.getChargeCategoryName(charge.charge_category_id),
                                charge_id: charge.id,
                                date: charge.date,
                                stockable: this.main.getStockableName(detail.stockable_id, detail.stockable_type),
                                stockable_type: detail.stockable_type,
                                total_price: detail.total_price,
                                total_weight: detail.total_weight_bill,
                                tracking_numbers: charge.tracking_numbers,
                                truck_id: this.main.getTruckNames(truck_ids),
                                unit_price: detail.unit_price,
                            }
                        });
                    }).flat(),
                    total_price: supplier_invoice.total_price,
                    total_weight: supplier_invoice.total_weight,
                    tracking_numbers: supplier_invoice.tracking_numbers,
                    address: supplier_invoice.supplier_ids.map(supplier_id => this.main.getSupplierAddress(supplier_id)),
                    supplier_ids: supplier_invoice.supplier_ids.map(supplier_id => this.main.getSupplierName(supplier_id)).toString().split("'")[0],
                }
            });
        }
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