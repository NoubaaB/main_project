import axios from "axios";
import { defineStore } from "pinia";
import i18n from '@/plugins/i18n';
import { useMain } from "./Main";

export const useSupplierInvoice = defineStore("supplierInvoice", {
    state: () => {
        return {
            loading: false,
            api_url: "/api/supplier_invoice",
            list: [],
            supplier_invoice_fetch_items: [],
            // -- View/Print page state --
            nbr_pages: 0,
            invoices_length: 0,
            allow_qr_code: false,
            allow_bg_image: true,
            use_simple_invoice: false,
            final_page_rows_nbr: 35,
            active_page_rows_nbr: 32,
            g_items: [],
            items: [],

            // -- Display mode: 'detail' or 'charge' --
            display_mode: 'detail',

            // -- Headers for DETAIL mode (detailCharges + detailProductions per line) --
            detail_headers: [
                { align: "center", title: i18n.global.t("date"), key: 'date', visible: true },
                { align: "center", title: i18n.global.t("article_type"), key: 'article_type', visible: true },
                { align: "center", title: i18n.global.t("license_plate"), key: 'license_plate', visible: true },
                { align: "center", title: i18n.global.t("rtn"), key: 'reception_tracking_number', visible: true },
                { align: "center", title: i18n.global.t("weight_kg"), key: 'total_weight', visible: true },
                { align: "center", title: i18n.global.t("unit_price_currency", { currency: useMain().currency }), key: 'unit_price', visible: true },
                { align: "center", title: i18n.global.t("price_dh", { currency: useMain().currency }), key: 'total_price', visible: true },
            ],

            // -- Headers for CHARGE mode (one row per charge / production) --
            charge_headers: [
                { align: "center", title: i18n.global.t("date"), key: 'date', visible: true },
                { align: "center", title: i18n.global.t("source"), key: 'source', visible: true },
                { align: "center", title: i18n.global.t("rtn"), key: 'reception_tracking_number', visible: true },
                { align: "center", title: i18n.global.t("category"), key: 'category_name', visible: true },
                { align: "center", title: i18n.global.t("weight_kg"), key: 'total_weight', visible: true },
                { align: "center", title: i18n.global.t("unit_price_currency", { currency: useMain().currency }), key: 'unit_price', visible: true },
                { align: "center", title: i18n.global.t("price_dh", { currency: useMain().currency }), key: 'total_price', visible: true },
            ],

            // Active headers (set dynamically based on display_mode)
            _headers: [],
            headers: [],

            grouped_headers: { align: "center", title: i18n.global.t("records_count"), key: 'records_count', visible: true },
        };
    },
    actions: {
        // -- Switch display mode and apply correct headers --
        setDisplayMode(mode) {
            this.display_mode = mode;
            if (mode === 'detail') {
                this._headers = [...this.detail_headers];
                this.headers = [...this.detail_headers];
            } else {
                this._headers = [...this.charge_headers];
                this.headers = [...this.charge_headers];
            }
            // Clear grouped items when switching modes
            this.g_items = [];
        },

        // -- Initialize headers on first load --
        initHeaders() {
            this.setDisplayMode(this.display_mode);
        },

        // -- Fetch supplier invoices by date range --
        fetchSupplierInvoices: async function (date_start, date_end) {
            this.loading = true;
            try {
                let response = await axios.get(this.api_url, {
                    params: { date_start, date_end }
                });
                this.supplier_invoice_fetch_items = response.data.supplier_invoices || [];
            } catch (error) {
                console.error("fetchSupplierInvoices error:", error);
            }
            this.loading = false;
        },

        // -- Create supplier invoice --
        createInvoice: async function ({ charge_ids, production_ids, tracking_numbers, date }) {
            try {
                let res = await axios.post(this.api_url, {
                    date,
                    tracking_numbers,
                    charge_ids: charge_ids || [],
                    production_ids: production_ids || [],
                });
                this.supplier_invoice_fetch_items.push(res.data.supplier_invoice);
                useMain().add_snackbar({
                    show: true,
                    message: i18n.global.t("invoice_created_successfully"),
                    color: "success"
                });
                return res.data.supplier_invoice;
            } catch (error) {
                console.error("createInvoice error:", error);
                useMain().add_snackbar({
                    show: true,
                    message: i18n.global.t("error"),
                    color: "error"
                });
                throw error;
            }
        },

        // -- Delete supplier invoices --
        deleteInvoices: async function (ids) {
            try {
                let res = await axios.delete(this.api_url, { params: { ids } });
                this.supplier_invoice_fetch_items = this.supplier_invoice_fetch_items.filter(
                    e => !ids.includes(e.id)
                );
                useMain().add_snackbar({
                    show: true,
                    message: i18n.global.t("delete_suppilier_invoice", {
                        supply_number: (res.data.deleted_tracking_numbers || []).toString()
                    }),
                    color: "success"
                });
            } catch (error) {
                console.error("deleteInvoices error:", error);
            }
        },

        // -- Get invoices by IDs (for view/print page) --
        getSupplierInvoicesByIds: async function (ids) {
            try {
                let res = await axios.get(this.api_url + '/supplier-invoices-by-ids', {
                    params: { ids }
                });
                let data = this.formatedSupplierInvoiceData(res.data.supplier_invoices);
                // Initialize headers if not set yet
                if (!this._headers.length) this.initHeaders();
                return data;
            } catch (error) {
                console.error("getSupplierInvoicesByIds error:", error);
                return [];
            }
        },

        // -- Increment print counter --
        incrementPrintCounter: async function (id) {
            try {
                let res = await axios.post(this.api_url + '/increment-print-counter', { id });
                const item = this.supplier_invoice_fetch_items.find(e => e.id === id);
                if (item) item.print_counter = res.data.print_counter;
                return res.data.print_counter;
            } catch (error) {
                console.error("incrementPrintCounter error:", error);
            }
        },

        // -- Grouped items (for view page) --
        setGroupedItems: function (items) {
            this.g_items = items;
        },

        // -- Format data for view/print page --
        formatedSupplierInvoiceData: function (data) {
            const main = useMain();
            setTimeout(() => {
                this.nbr_pages = document.getElementsByClassName('_invoice_page').length;
                this.invoices_length = data.length;
            }, 100);
            return data.map(supplier_invoice => {

                // =======================================================
                //  DETAIL ITEMS (one row per detailCharge / detailProduction)
                // =======================================================
                const detailChargeItems = (supplier_invoice.charges || []).flatMap(charge => {
                    const details = charge.detail_charges || [];
                    const truckNames = (charge.trucks || []).map(t => t.registration_plate || t.name).join(', ');
                    return details.map(detail => ({
                        date: charge.date,
                        article_type: detail.article_description || main.getStockableInvoiceName(detail.stockable_id, detail.stockable_type, detail),
                        license_plate: truckNames,
                        reception_tracking_number: charge.reception_tracking_number,
                        total_weight: detail.weight,
                        unit_price: detail.unit_price,
                        total_price: detail.total_price,
                    }));
                });

                const detailProductionItems = (supplier_invoice.productions || []).flatMap(production => {
                    const details = production.detail_productions || [];
                    const trackingNumbers = Array.isArray(production.reception_tracking_number)
                        ? production.reception_tracking_number.join(', ')
                        : (production.reception_tracking_number || '');
                    return details.map(detail => ({
                        date: production.date,
                        article_type: detail.article_description || main.getStockableInvoiceName(detail.stockable_id, detail.stockable_type, detail),
                        license_plate: '',
                        reception_tracking_number: trackingNumbers,
                        total_weight: detail.weight,
                        unit_price: detail.unit_price,
                        total_price: detail.total_price,
                    }));
                });

                // =======================================================
                //  CHARGE ITEMS (one row per charge / production parent)
                // =======================================================
                const chargeParentItems = (supplier_invoice.charges || []).map(charge => {
                    const catName = charge.charge_category ? (charge.charge_category.name || '') : '';
                    const w = parseFloat(charge.total_weight) || 0;
                    const p = parseFloat(charge.total_price) || 0;
                    return {
                        date: charge.date,
                        source: i18n.global.t('charge'),
                        reception_tracking_number: charge.reception_tracking_number,
                        category_name: catName,
                        total_weight: charge.total_weight,
                        unit_price: w > 0 ? (p / w).toFixed(2) : '0.00',
                        total_price: charge.total_price,
                    };
                });

                const productionParentItems = (supplier_invoice.productions || []).map(production => {
                    const catName = production.production_category ? (production.production_category.name || '') : '';
                    const trackingNumbers = Array.isArray(production.reception_tracking_number)
                        ? production.reception_tracking_number.join(', ')
                        : (production.reception_tracking_number || '');
                    const w = parseFloat(production.total_weight) || 0;
                    const p = parseFloat(production.total_price) || 0;
                    return {
                        date: production.date,
                        source: i18n.global.t('production'),
                        reception_tracking_number: trackingNumbers,
                        category_name: catName,
                        total_weight: production.total_weight,
                        unit_price: w > 0 ? (p / w).toFixed(2) : '0.00',
                        total_price: production.total_price,
                    };
                });

                // =======================================================
                //  Supplier names & addresses
                // =======================================================
                const supplierSet = new Set();
                (supplier_invoice.charges || []).forEach(c => {
                    (c.suppliers || []).forEach(s => supplierSet.add(s.name));
                });
                (supplier_invoice.productions || []).forEach(p => {
                    (p.suppliers || []).forEach(s => supplierSet.add(s.name));
                });

                const addressSet = new Set();
                (supplier_invoice.charges || []).forEach(c => {
                    (c.suppliers || []).forEach(s => {
                        if (s.address) addressSet.add(s.address);
                    });
                });
                (supplier_invoice.productions || []).forEach(p => {
                    (p.suppliers || []).forEach(s => {
                        if (s.address) addressSet.add(s.address);
                    });
                });

                return {
                    id: supplier_invoice.id,
                    date: supplier_invoice.date,
                    // Both item arrays stored; SupplierInvoice.vue picks the right one
                    detail_items: [...detailChargeItems, ...detailProductionItems],
                    charge_items: [...chargeParentItems, ...productionParentItems],
                    total_price: supplier_invoice.total_price,
                    total_weight: supplier_invoice.total_weight,
                    tracking_numbers: (supplier_invoice.tracking_numbers || []).toString(),
                    address: supplier_invoice.supplier?.address || [...addressSet],
                    supplier: supplier_invoice.supplier,
                    supplier_name: supplier_invoice.supplier?.name || [...supplierSet].join(', '),
                    supplier_ids: supplier_invoice.supplier?.name || [...supplierSet].join(', '),
                };
            });
        },
    },
    getters: {
        getList: state => state.list,
        invoice_headers: (state) => {
            return state.g_items.length > 0 ? [...state._headers, state.grouped_headers] : state._headers;
        },
        getHeaders: (state) => {
            return state.headers;
        },
        getItems: function (state) {
            return state.g_items.length == 0 ? state.items : state.g_items;
        }
    }
});