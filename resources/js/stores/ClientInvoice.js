import axios from "axios";
import { defineStore } from "pinia";
import i18n from '@/plugins/i18n';
import { useMain } from "./Main";

export const useClientInvoice = defineStore("clientInvoice", {
    state: () => {
        return {
            loading: false,
            api_url: "/api/client_invoice",
            list: [],
            client_invoice_fetch_items: [],
            // -- View/Print page state --
            nbr_pages: 0,
            invoices_length: 0,
            allow_qr_code: false,
            allow_bg_image: true,
            use_simple_invoice: false,
            use_export_layout: false,
            show_intermediary_info: true,
            final_page_rows_nbr: 35,
            active_page_rows_nbr: 32,
            g_items: [],
            items: [],

            // -- Display mode: 'detail' or 'shipping' --
            display_mode: 'detail',

            // -- Headers for DETAIL mode (detailShippings + detailProductions per line) --
            detail_headers: [
                { align: "center", title: i18n.global.t("date"), key: 'date', visible: true },
                { align: "center", title: i18n.global.t("article_type"), key: 'article_type', visible: true },
                { align: "center", title: i18n.global.t("license_plate"), key: 'license_plate', visible: true },
                { align: "center", title: i18n.global.t("shipping_tracking_number"), key: 'shipping_tracking_number', visible: true },
                { align: "center", title: i18n.global.t("weight_kg"), key: 'total_weight', visible: true },
                { align: "center", title: i18n.global.t("unit_price_currency", { currency: useMain().currency }), key: 'unit_price', visible: true },
                { align: "center", title: i18n.global.t("price_dh", { currency: useMain().currency }), key: 'total_price', visible: true },
            ],

            // -- Headers for SHIPPING/PRODUCTION mode (one row per shipping / production) --
            shipping_headers: [
                { align: "center", title: i18n.global.t("date"), key: 'date', visible: true },
                { align: "center", title: i18n.global.t("source"), key: 'source', visible: true },
                { align: "center", title: i18n.global.t("shipping_tracking_number"), key: 'shipping_tracking_number', visible: true },
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
                this._headers = [...this.shipping_headers];
                this.headers = [...this.shipping_headers];
            }
            // Clear grouped items when switching modes
            this.g_items = [];
        },

        // -- Initialize headers on first load --
        initHeaders() {
            this.setDisplayMode(this.display_mode);
        },

        // -- Fetch client invoices by date range --
        fetchClientInvoices: async function (date_start, date_end) {
            this.loading = true;
            try {
                let response = await axios.get(this.api_url, {
                    params: { date_start, date_end }
                });
                this.client_invoice_fetch_items = response.data.client_invoices || [];
            } catch (error) {
                console.error("fetchClientInvoices error:", error);
            }
            this.loading = false;
        },

        // -- Delete client invoices --
        deleteInvoices: async function (ids) {
            try {
                let res = await axios.delete(this.api_url, { params: { ids } });
                this.client_invoice_fetch_items = this.client_invoice_fetch_items.filter(
                    e => !ids.includes(e.id)
                );
                useMain().add_snackbar({
                    show: true,
                    message: i18n.global.t("delete_client_invoice", {
                        invoice_number: (res.data.deleted_tracking_numbers || []).toString()
                    }),
                    color: "success"
                });
            } catch (error) {
                console.error("deleteInvoices error:", error);
            }
        },

        // -- Get invoices by IDs (for view/print page) --
        getClientInvoicesByIds: async function (ids) {
            try {
                let res = await axios.get(this.api_url + '/client-invoices-by-ids', {
                    params: { ids }
                });
                const invoices = res.data.client_invoices || [];
                this.use_export_layout = invoices.some(inv => inv.type_invoice === 'export');
                let data = this.formatedClientInvoiceData(invoices);
                // Initialize headers if not set yet
                if (!this._headers.length) this.initHeaders();
                return data;
            } catch (error) {
                console.error("getClientInvoicesByIds error:", error);
                return [];
            }
        },

        // -- Increment print counter --
        incrementPrintCounter: async function (id) {
            try {
                let res = await axios.post(this.api_url + '/increment-print-counter', { id });
                const item = this.client_invoice_fetch_items.find(e => e.id === id);
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
        formatedClientInvoiceData: function (data) {
            const main = useMain();
            setTimeout(() => {
                this.nbr_pages = document.getElementsByClassName('_invoice_page').length;
                this.invoices_length = data.length;
            }, 100);
            return data.map(client_invoice => {

                // =======================================================
                //  DETAIL ITEMS (one row per detailShipping / detailProduction)
                // =======================================================
                const detailShippingItems = (client_invoice.shippings || []).flatMap(shipping => {
                    const details = shipping.detail_shippings || [];
                    const trackingNumbers = Array.isArray(shipping.shipping_tracking_number)
                        ? shipping.shipping_tracking_number.join(', ')
                        : (shipping.shipping_tracking_number || '');
                    return details.map(detail => ({
                        date: shipping.date,
                        article_type: main.getStockableInvoiceName(detail.stockable_id, detail.stockable_type),
                        license_plate: '',
                        shipping_tracking_number: trackingNumbers,
                        total_weight: detail.weight,
                        unit_price: detail.unit_price,
                        total_price: detail.total_price,
                    }));
                });

                const detailProductionItems = (client_invoice.productions || []).flatMap(production => {
                    const details = production.detail_productions || [];
                    const trackingNumbers = Array.isArray(production.production_tracking_number)
                        ? production.production_tracking_number.join(', ')
                        : (production.production_tracking_number || '');
                    return details.map(detail => ({
                        date: production.date,
                        article_type: main.getStockableInvoiceName(detail.stockable_id, detail.stockable_type),
                        license_plate: '',
                        shipping_tracking_number: trackingNumbers,
                        total_weight: detail.weight,
                        unit_price: detail.unit_price,
                        total_price: detail.total_price,
                    }));
                });

                // =======================================================
                //  SHIPPING/PRODUCTION ITEMS (one row per parent)
                // =======================================================
                const shippingParentItems = (client_invoice.shippings || []).map(shipping => {
                    const catName = shipping.shipping_category ? (shipping.shipping_category.name || '') : '';
                    const w = parseFloat(shipping.total_weight) || 0;
                    const p = parseFloat(shipping.total_price) || 0;
                    const trackingNumbers = Array.isArray(shipping.shipping_tracking_number)
                        ? shipping.shipping_tracking_number.join(', ')
                        : (shipping.shipping_tracking_number || '');
                    return {
                        date: shipping.date,
                        source: i18n.global.t('shipping'),
                        shipping_tracking_number: trackingNumbers,
                        category_name: catName,
                        total_weight: shipping.total_weight,
                        unit_price: w > 0 ? (p / w).toFixed(2) : '0.00',
                        total_price: shipping.total_price,
                    };
                });

                const productionParentItems = (client_invoice.productions || []).map(production => {
                    const catName = production.production_category ? (production.production_category.name || '') : '';
                    const trackingNumbers = Array.isArray(production.production_tracking_number)
                        ? production.production_tracking_number.join(', ')
                        : (production.production_tracking_number || '');
                    const w = parseFloat(production.total_weight) || 0;
                    const p = parseFloat(production.total_price) || 0;
                    return {
                        date: production.date,
                        source: i18n.global.t('production'),
                        shipping_tracking_number: trackingNumbers,
                        category_name: catName,
                        total_weight: production.total_weight,
                        unit_price: w > 0 ? (p / w).toFixed(2) : '0.00',
                        total_price: production.total_price,
                    };
                });

                // =======================================================
                //  Client names & addresses
                // =======================================================
                const clientName = client_invoice.client?.name || '';
                const clientAddress = client_invoice.client?.address || '';

                return {
                    id: client_invoice.id,
                    date: client_invoice.date,
                    // Both item arrays stored; ClientInvoice.vue picks the right one
                    detail_items: [...detailShippingItems, ...detailProductionItems],
                    shipping_items: [...shippingParentItems, ...productionParentItems],
                    total_price: client_invoice.total_price,
                    total_weight: client_invoice.total_weight,
                    tracking_numbers: (client_invoice.tracking_numbers || []).toString(),
                    address: clientAddress ? [clientAddress] : [],
                    client_name: clientName,
                    client: client_invoice.client,
                    invoice: client_invoice.type_invoice == "export" ? client_invoice : null
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
