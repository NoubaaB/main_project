import axios from "axios";
import { defineStore } from "pinia";
import { markRaw } from "vue";
import { v4 as uuidv4 } from 'uuid';
import { useMain } from "./Main";
import { useFishType } from "./FishType";
import { usePackageType } from "./PackageType";
import { useGuanoType } from "./GuanoType";

import { useWarehouse } from "./Warehouse";
import { useWarehouseCategoryOperation } from "./WarehouseCategoryOperation";
import { useSellCategory } from "./SellCategory";
import { useFormula } from "./Formula";
import { useNotification } from "./Notification";
import { useAuth } from "./Auth";
import i18n from '@/plugins/i18n';

export const useSell = defineStore("sell", {
    state: () => {
        return {
            api_url: "/api/sells/get_sells",
            // production removed
            loading: false,
            tracking_numbers: [],
            showAdditionalInfo: false,
            main: markRaw(useMain()),
            fish_type: markRaw(useFishType()),
            package_type: markRaw(usePackageType()),
            guano_type: markRaw(useGuanoType()),

            warehouse: markRaw(useWarehouse()),
            sell_category: markRaw(useSellCategory()),
            warehouse_category_operation: markRaw(useWarehouseCategoryOperation()),
            formula: markRaw(useFormula()),
            sell_category_operation: {
                sell_details: [],
                id: null,
                total_weight: 0.00,
                total_price: 0.00,
            },
            edit_sell: {},
            view_sell: null,
            view_sell_loading: false,
            sell: {},
            sell_fetch_items: [],
            filtred_sell_fetch_items: [],
            production_fetch_items: [],
            warehouse_stockable_tracking_number_operations: [],
            non_warehouse_stockable_operations: [],
            invoice: {
                date: new Date(),
                type_invoice: "local",
                invoice_invoicer_ids: [],
                tracking_numbers: [],
                sell_ids: []
            }
        };
    },
    actions: {
        add_sell: function () {
            // Try to restore from localStorage first
            const auth = useAuth();
            const userId = auth.user?.id;
            if (userId) {
                try {
                    const saved = localStorage.getItem(`form_sell_${userId}`);
                    if (saved) {
                        this.sell = JSON.parse(saved);
                        const savedInfo = localStorage.getItem(`form_sell_showAdditionalInfo_${userId}`);
                        if (savedInfo !== null) this.showAdditionalInfo = savedInfo === 'true';
                        return;
                    }
                } catch (e) {
                    console.error("Error restoring sell from localStorage:", e);
                }
            }

            let sell_id = uuidv4();
            let _warehouse_category_operation = this.warehouse_category_operation.getList[0];

            this.sell = JSON.parse(JSON.stringify({
                id: sell_id,
                date: new Date(),
                trackingNumbersInput: "",
                tracking_numbers: [],
                note: null,
                tracking_onp_numbers: [],
                total_price: null,
                total_weight: null,
                origin_id: null,
                fishery_id: null,
                client_ids: [],
                truck_ids: [],
                sell_category_id: this.sell_category.getList[0]?.id || 1,
                daily_sell_id: null,
                warehouse_category_operations: [
                    {
                        id: _warehouse_category_operation?.id || null,
                        sell_details: [],
                        sell_id: sell_id,
                        total_weight: 0.00,
                        total_price: 0.00,
                    }
                ]
            }))
        },
        delete: async function (ids) {
            let res = await axios.delete('/api/sell', {
                data: {
                    ids
                }
            });
            this.sell_fetch_items = this.sell_fetch_items.filter(e => ids.includes(e.id) == false);
        },
        get_first_non_disabled_warehouse_category_operation: function () {
            const warehouse_category_operation_ids = this.sell.warehouse_category_operations?.map((warehouse_category_operation) => warehouse_category_operation.id) || [];
            return this.warehouse_category_operation_for_sell.filter((operation) => !warehouse_category_operation_ids.includes(operation.id))[0];
        },
        add_warehouse_category_operation: function () {
            let _warehouse_category_operation = this.get_first_non_disabled_warehouse_category_operation();

            this.sell.warehouse_category_operations.push({
                id: _warehouse_category_operation?.id || null,
                sell_details: [],
                sell_id: this.sell.id,
                total_weight: 0.00,
                total_price: 0.00,
            });
            console.log("add_warehouse_category_operation", this.sells);
        },
        delete_warehouse_category_operation: function (warehouse_category_operation) {
            this.sell.warehouse_category_operations = this.sell.warehouse_category_operations.filter(o => o.id != warehouse_category_operation.id);
            console.log("delete_warehouse_category_operation", warehouse_category_operation, this.sells);
        },
        add_sell_detail: function (warehouse_category_operation_id, sell_detail) {
            this.sell.warehouse_category_operations
                .find(o => o.id == warehouse_category_operation_id)
                .sell_details.push(sell_detail);
        },
        edit_sell_detail: function (warehouse_category_operation_id, sell_detail) {
            let _sell_detail = this.sell.warehouse_category_operations
                .find(o => o.id == warehouse_category_operation_id)
                .sell_details.find(d => d.id == sell_detail.id);

            Object.assign(_sell_detail, sell_detail);//dosen't replace the object reference to keep reactivity
        },
        remove_sell_detail: function (warehouse_category_operation_id, sell_detail_id) {
            this.sell.warehouse_category_operations
                .find(o => o.id == warehouse_category_operation_id)
                .sell_details = this.sell.warehouse_category_operations
                    .find(o => o.id == warehouse_category_operation_id)
                    .sell_details.filter(d => d.id != sell_detail_id);
        },
        checkTrackingNumberUnique: async function (tracking_number, sell_category_id, exclude_sell_id = null) {
            try {
                const response = await axios.post('/api/sell/check-tracking-number', {
                    tracking_number,
                    sell_category_id,
                    exclude_sell_id,
                });
                return response.data;
            } catch (error) {
                console.error('Error checking tracking number uniqueness:', error);
                return { unique: true }; // Default to allowing if check fails
            }
        },

        submitSell: function (router) {
            const sellData = JSON.parse(JSON.stringify(this.sell));
            axios.post("/api/sell", this.sell)
                .then(response => {
                    console.log("sell_category_operation submitted successfully:", response.data);

                    // Trigger creation notification
                    const notification = useNotification();
                    const clientNames = (sellData.client_ids || [])
                        .map(id => this.main.client.list.find(s => s.id === id)?.name)
                        .filter(Boolean)
                        .join(', ');

                    const fields = [];
                    if (sellData.tracking_numbers?.length) {
                        fields.push({
                            label: i18n.global.t('tracking_numbers'),
                            value: sellData.tracking_numbers.join(', '),
                            icon: 'mdi-barcode'
                        });
                    }
                    if (clientNames) {
                        fields.push({
                            label: i18n.global.t('clients'),
                            value: clientNames,
                            icon: 'mdi-truck-delivery'
                        });
                    }
                    if (sellData.total_price) {
                        fields.push({
                            label: i18n.global.t('total_price'),
                            value: this.main.formatDisplayNumber(sellData.total_price) + ' ' + this.main.currency,
                            icon: 'mdi-cash'
                        });
                    }
                    if (sellData.total_weight) {
                        fields.push({
                            label: i18n.global.t('total_weight'),
                            value: this.main.formatDisplayNumber(sellData.total_weight) + ' Kg',
                            icon: 'mdi-weight-kilogram'
                        });
                    }

                    notification.notifyCreation({
                        type: 'sells',
                        icon: 'mdi-warehouse',
                        color: 'info',
                        fields
                    });

                    // Redirect to view sell page
                    const auth = useAuth();
                    const userId = auth.user?.id;
                    if (userId) {
                        localStorage.removeItem(`form_sell_${userId}`);
                        localStorage.removeItem(`form_sell_showAdditionalInfo_${userId}`);
                    }
                    const sellId = response.data?.data?.id || response.data?.sell?.id || response.data?.id;
                    console.log("Redirect to view_sell with ID:", sellId, "response.data:", response.data);
                    if (router && sellId) {
                        router.push({ name: 'view_sell', params: { id: sellId } });
                    }
                })
                .catch(error => {
                    console.error("Error submitting sell_category_operation:", error);
                });
        },
        fetchSell: async function (date_start, date_end) {
            this.loading = true;
            let response = await this.main.fetchData(date_start, date_end, this.api_url)
            this.sell_fetch_items = response.sells;
            this.invoice.tracking_numbers = response.tracking_numbers;
            this.non_warehouse_stockable_operations = this.sell_fetch_items.flatMap(e => e.non_warehouse_stockable_operations.map(ee => { ee.sell_category_id = e.sell_category_id; return ee }))
            this.warehouse_stockable_tracking_number_operations = this.sell_fetch_items.flatMap(e => e.warehouse_stockable_tracking_number_operations.map(ee => { ee.sell_category_id = e.sell_category_id; return ee }))
            this.loading = false;
        },
        fetchProductionSells: async function (date_start, date_end) {
            this.loading = true;
            try {
                const response = await this.main.fetchData(date_start, date_end, this.production_api_url);
                this.production_fetch_items = response.sells || [];
            } catch (e) {
                console.error('Error fetching production sells:', e);
                this.production_fetch_items = [];
            }
            this.loading = false;
        },
        fetchSellById: function (id) {
            axios.get(`/api/sell/${id}`).then(response => {
                this.edit_sell.id = response.data.sell.id;
                this.edit_sell.date = response.data.sell.date;
                this.edit_sell.trackingNumbersInput = "";
                this.edit_sell.tracking_numbers = response.data.sell.tracking_numbers;
                this.edit_sell.note = response.data.sell.note;
                this.edit_sell.tracking_onp_numbers = response.data.sell.tracking_onp_numbers;
                this.edit_sell.origin_id = response.data.sell.origin_id;
                this.edit_sell.fishery_id = response.data.sell.fishery_id;
                this.edit_sell.client_ids = response.data.sell.clients.map(s => s.id);
                this.edit_sell.truck_ids = response.data.sell.truck_ids;
                this.edit_sell.sell_category_id = response.data.sell.sell_category_id;
                this.edit_sell.daily_sell_id = null;
                this.edit_sell.total_price = response.data.sell.total_price;
                this.edit_sell.total_weight = response.data.sell.total_weight;
                this.edit_sell.warehouse_category_operations = [
                    {
                        id: 1,
                        sell_details: [],
                        sell_id: this.edit_sell.id,
                        total_weight: 0.00,
                        total_price: 0.00,
                    }
                ];
            });
        },
        fetchSellForView: async function (id) {
            this.view_sell_loading = true;
            this.view_sell = null;
            try {
                const response = await axios.get(`/api/sell/${id}`);
                this.view_sell = response.data.sell;
            } catch (error) {
                console.error("Error fetching sell for view:", error);
            } finally {
                this.view_sell_loading = false;
            }
        },
        PostInvoice: async function (client_invoice) {
            this.loading = true;
            let response = await axios.post("/api/client_invoice", client_invoice);
            response.data.client_invoice.sell_ids.forEach(sell_id => {
                let item = this.sell_fetch_items.find(i => i.id == sell_id);
                if (item) {
                    item.invoice_id = response.data.client_invoice.id;
                    item.client_invoice_tracking_number = response.data.client_invoice.tracking_numbers;
                    // let invoice = this.client_invoice.client_invoice_fetch_items.find(i => i.id == response.data.client_invoice.id)
                    if (invoice) {
                        invoice = response.data.client_invoice; // Update the invoice in the client invoice store to update the tracking numbers and other details related to the invoice
                    } else {
                        //    this.client_invoice.client_invoice_fetch_items.push(response.data.client_invoice);
                    }
                }
            });
            this.invoice = {
                date: new Date(),
                type_invoice: "local",
                invoice_invoicer_ids: [],
                tracking_numbers: response.data.tracking_numbers,
                sell_ids: []
            };
            this.main.snackbar = {
                action_command: true,
                action_command_icon_color: "success",
                action_command_icon: "mdi-printer-check",
                action_command_text: i18n.global.t('client_invoice_created', {
                    tracking_numbers: response.data.client_invoice.tracking_numbers.toString()
                })
            };
            this.loading = false;
        },
        UpdateSell: function () {
            if (!this.sell.warehouse_category_operations) return;
            this.sell.total_weight = +this.sell.warehouse_category_operations.reduce((total, operation) => {
                return +total + operation.sell_details.reduce((opTotal, detail) => opTotal + (+detail.total_weight_bill || 0), 0);
            }, 0);
            this.sell.total_price = +this.sell.warehouse_category_operations.reduce((total, operation) => {
                return +total + operation.sell_details.reduce((opTotal, detail) => opTotal + (+detail.total_price || 0), 0);
            }, 0);
            // Persist form to localStorage per user
            const auth = useAuth();
            const userId = auth.user?.id;
            if (userId) {
                try {
                    localStorage.setItem(`form_sell_${userId}`, JSON.stringify(this.sell));
                    localStorage.setItem(`form_sell_showAdditionalInfo_${userId}`, String(this.showAdditionalInfo));
                } catch (e) {
                    console.error("Error saving sell form to localStorage:", e);
                }
            }
        },
        undoInvoiceItem: async function (item_id) {
            try {
                let res = await axios.get(`/api/client_invoice/undo-invoice-item/${item_id}`);
                let item = this.sell_fetch_items.find(i => i.id == item_id);
                if (item) item.invoice_id = null;
                let sell_item = this.sell.warehouse_category_operations.flatMap(o => o.sell_details).find(d => d.id == item_id);
                if (sell_item) sell_item.invoice_id = null;
                this.main.add_snackbar({
                    show: true,
                    message: i18n.global.t("undo_sell_from_invoice_item_successfully", { supply_number: res.data.client_invoice_tracking_numbers.toString(), sell_number: item.tracking_numbers.toString() }),
                    color: "success"
                });
            } catch (error) {
                console.log("error", error)
            }
        },

        reset: function () {
            // Clear per-user storage
            const auth = useAuth();
            const userId = auth.user?.id;
            if (userId) {
                localStorage.removeItem(`form_sell_${userId}`);
                localStorage.removeItem(`form_sell_showAdditionalInfo_${userId}`);
            }
            // Force fresh form
            let sell_id = uuidv4();
            let _warehouse_category_operation = this.warehouse_category_operation.getList[0];
            this.sell = JSON.parse(JSON.stringify({
                id: sell_id,
                date: new Date(),
                trackingNumbersInput: "",
                tracking_numbers: [],
                note: null,
                tracking_onp_numbers: [],
                total_price: null,
                total_weight: null,
                origin_id: null,
                fishery_id: null,
                client_ids: [],
                truck_ids: [],
                sell_category_id: this.sell_category.getList[0]?.id || 1,
                daily_sell_id: null,
                warehouse_category_operations: [{
                    id: _warehouse_category_operation?.id || null,
                    sell_details: [],
                    sell_id: sell_id,
                    total_weight: 0.00,
                    total_price: 0.00,
                }]
            }));
            this.showAdditionalInfo = false;
            this.main.add_snackbar({
                show: true,
                message: i18n.global.t("reset_sell"),
                color: "success"
            });
        },
        getFishTypeName: function (stockable_id) {
            const fishType = this.fish_type.getList.find(type => type.id === stockable_id);
            return fishType ? fishType.name : 'N/A';
        },
        getPackageName: function (package_type_id) {
            const packageItem = this.getPackages.find(pkg => pkg.id === package_type_id);
            return packageItem ? packageItem[`name_${this.main.lang.code}`] : 'N/A';
        },
        getPackageSize: function (package_type_id, wieght) {
            const packageItem = this.getPackages.find(pkg => pkg.id === package_type_id);
            return packageItem ? packageItem.size : wieght;
        },
        getWarehouseCategoryOperationName: function (warehouse_category_operation) {
            if (!warehouse_category_operation) return '';
            const operation = this.warehouse_category_operation.getList.find(op => op.id === warehouse_category_operation.id);
            return operation ? operation[`name_${this.main.lang.code}`] : '';
        },
        countWarehouseCategoryOperationUsage: function (warehouse_category_operation_id) {
            return this.get_fetched_operations.filter(e => e.warehouse_category_operation_id == warehouse_category_operation_id).length;
        }
    },
    getters: {
        getSell: (state) => {
            return state.sell_category.getList.find(e => e.id == state.sell.sell_category_id) || {};
        },
        getFishTypes: (state) => {
            return state.fish_type.getList;
        },
        getPackages: (state) => {
            return state.package_type.getList;
        },
        getGuanoTypes: (state) => {
            return state.guano_type.getList;
        },
        getWarehouses: (state) => {
            return state.warehouse.getList;
        },
        sell_categories: (state) => {
            return state.sell_category.getList;
        },

        get_fetched_operations: (state) => {
            let non_warehouse_stockable_operations = state.filtred_sell_fetch_items.flatMap(e => e.non_warehouse_stockable_operations)
            let warehouse_stockable_tracking_number_operations = state.filtred_sell_fetch_items.flatMap(e => e.warehouse_stockable_tracking_number_operations)

            return [...non_warehouse_stockable_operations, ...warehouse_stockable_tracking_number_operations]
        },
        get_fetched_operations_cloned: (state) => {
            return [...state.non_warehouse_stockable_operations, ...state.warehouse_stockable_tracking_number_operations]
        },
        get_fetched_warehouse_category_operation_list: (state) => {
            let warehouse_category_operations = []
            state.get_fetched_operations?.forEach(item => {
                warehouse_category_operations.push({
                    id: item.id,
                    warehouse_category_operation_id: item.warehouse_category_operation_id,
                    date: item.date,
                    unit_price: item.unit_price,
                    total_weight: item.total_weight,
                    total_price: item.total_price,
                    total_package_count: item.total_package_count,
                    stockable_type: item.stockable_type,
                });
            })
            let warehouse_category_operations_ids = _.uniqBy(warehouse_category_operations, 'warehouse_category_operation_id').map(e => e.warehouse_category_operation_id);
            // return warehouse_category_operations;

            let data = [];
            warehouse_category_operations_ids.forEach(w_id => {
                let sup_total_weight = 0.00;
                let sup_total_price = 0.00;
                let sup_unit_price = 0.00;
                let sup_total_package_count = 0;

                let selected_warehouse_category_operations = warehouse_category_operations
                    .filter(e => e.warehouse_category_operation_id == w_id);

                let grouped_details = _(selected_warehouse_category_operations)
                    .groupBy('date')
                    .map((items, date) => {
                        let total_weight = 0.00;
                        let total_price = 0.00;
                        let total_package_count = 0.00;
                        let unit_price = 0.00;
                        items.forEach(i => {
                            total_weight += i.total_weight
                            total_price += i.total_price
                            total_package_count += i.total_package_count
                        })
                        total_weight = +(total_weight).toFixed(2);
                        total_price = +(total_price).toFixed(2);
                        total_package_count = +(total_package_count).toFixed(0);
                        if (total_price > 0 && total_weight > 0) {
                            unit_price = +(total_price / total_weight).toFixed(2)
                        } else {
                            unit_price = 0;
                        }
                        sup_total_weight += total_weight;
                        sup_total_price += total_price;
                        sup_total_package_count += total_package_count;
                        sup_unit_price += unit_price;
                        return {
                            date,
                            warehouse_category_operation_id: w_id,
                            total_weight,
                            total_price,
                            total_package_count,
                            unit_price
                        }
                    })
                    .value()
                if (grouped_details.length > 0) {
                    sup_unit_price = +(sup_unit_price / grouped_details.length).toFixed(2)
                } else {
                    sup_unit_price = 0;
                }
                data.push({
                    id: w_id,
                    total_weight: +sup_total_weight.toFixed(2),
                    total_price: +(sup_total_price).toFixed(2),
                    total_package_count: +(sup_total_package_count).toFixed(2),
                    unit_price: +(sup_unit_price).toFixed(2),
                    warehouse_category_operation_id: w_id,
                    details: selected_warehouse_category_operations,
                    grouped_details: grouped_details
                })
            });
            return data;
        },
        get_placeholder_warehouse_category_operation_list: (state) => {
            const ids = new Set(state.get_fetched_warehouse_category_operation_list.map(o => o.warehouse_category_operation_id))
            return state.warehouse_category_operation.list.filter(o => !ids.has(o.id) && (o.operation_type == "sell" || o.operation_type == "both"))
        },
        get_fetched_sell_category_list: (state) => {
            // Group all sell_fetch_items by sell_category_id
            const catIds = _.uniq(state.filtred_sell_fetch_items.map(e => e.sell_category_id));
            return catIds.map(catId => {
                const sells = state.filtred_sell_fetch_items.filter(e => e.sell_category_id === catId);
                let sup_total_weight = 0;
                let sup_total_price = 0;
                let sup_total_package_count = 0;

                const grouped_details = _(sells)
                    .groupBy('date')
                    .map((items, date) => {
                        let total_weight = _.sumBy(items, i => +i.total_weight || 0);
                        let total_price = _.sumBy(items, i => +i.total_price || 0);
                        let total_package_count = _.sumBy(items, i => +i.total_package_count || 0);
                        total_weight = +total_weight.toFixed(2);
                        total_price = +total_price.toFixed(2);
                        total_package_count = +total_package_count.toFixed(0);
                        const unit_price = total_weight > 0 ? +(total_price / total_weight).toFixed(2) : 0;
                        sup_total_weight += total_weight;
                        sup_total_price += total_price;
                        sup_total_package_count += total_package_count;
                        return { date, sell_category_id: catId, total_weight, total_price, total_package_count, unit_price };
                    })
                    .value();

                const unit_price = grouped_details.length > 0
                    ? +(_.sumBy(grouped_details, 'unit_price') / grouped_details.length).toFixed(2) : 0;

                return {
                    id: catId,
                    sell_category_id: catId,
                    total_weight: +sup_total_weight.toFixed(2),
                    total_price: +sup_total_price.toFixed(2),
                    total_package_count: +sup_total_package_count.toFixed(0),
                    unit_price,
                    sells_count: sells.length,
                    grouped_details,
                };
            });
        },
        get_formulas_list: (state) => {
            let formulas = state.formula.getList.filter(e => e.type == "sell");
            return formulas;
        },
        warehouse_category_operation_for_sell: (state) => {
            return state.warehouse_category_operation.getList
                .filter(e => e.operation_type == "sell" || e.operation_type == "both")
                .map(e => ({ ...e, disabled: !!e.disabled }));
        },
        stockables: (state) => {
            let data = state.get_fetched_operations_cloned.map(e => {
                let color = "";
                let obj = "";
                switch (e.stockable_type) {
                    case "FishType":
                        color = "blue";
                        obj = state.getFishTypes.find(t => t.id == e.stockable_id);
                        break;
                    case "GuanoType":
                        color = "grey-darken-4";
                        obj = state.getGuanoTypes.find(t => t.id == e.stockable_id);
                        break;
                    case "PackageType":
                        color = "success";
                        obj = state.getPackageTypes.find(t => t.id == e.stockable_id);
                        break;
                }
                return {
                    name: obj != null ? obj[`name_${state.main.lang.code}`] : i18n.global.t("n/a"),
                    stockable_id: e.stockable_id,
                    stockable_type: e.stockable_type,
                    color
                }
            })
            return _.uniqBy(data, o => `${o.stockable_id}|${o.stockable_type}`);
        },
    },
});
