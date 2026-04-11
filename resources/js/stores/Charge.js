import axios from "axios";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useMain } from "./Main";
import { useFishType } from "./FishType";
import { usePackageType } from "./PackageType";
import { useGuanoType } from "./GuanoType";
import { useSupplierInvoice } from "./SupplierInvoice";
import { useWarehouse } from "./Warehouse";
import { useWarehouseCategoryOperation } from "./WarehouseCategoryOperation";
import { useChargeCategory } from "./ChargeCategory";
import { useFormula } from "./Formula";
import { useNotification } from "./Notification";
import i18n from '@/plugins/i18n';

export const useCharge = defineStore("charge", {
    state: () => {
        return {
            api_url: "/api/charges/get_charges",
            production_api_url: "/api/charges/get_production_charges",
            loading: false,
            tracking_numbers: [],
            showAdditionalInfo: false,
            main: useMain(),
            fish_type: useFishType(),
            package_type: usePackageType(),
            guano_type: useGuanoType(),
            supplier_invoice: useSupplierInvoice(),
            warehouse: useWarehouse(),
            charge_category: useChargeCategory(),
            warehouse_category_operation: useWarehouseCategoryOperation(),
            formula: useFormula(),
            charge_category_operation: {
                charge_details: [],
                id: null,
                total_weight: 0.00,
                total_price: 0.00,
            },
            edit_charge: {},
            view_charge: null,
            view_charge_loading: false,
            charge: {},
            charges: [],
            charge_fetch_items: [],
            filtred_charge_fetch_items: [],
            production_fetch_items: [],
            warehouse_stockable_tracking_number_operations: [],
            non_warehouse_stockable_operations: [],
            invoice: {
                date: new Date(),
                type_invoice: "local",
                invoice_invoicer_ids: [],
                tracking_numbers: [],
                charge_ids: []
            }
        };
    },
    actions: {
        add_charge: function () {
            let charge_id = uuidv4();
            let _warehouse_category_operation = this.warehouse_category_operation.getList[0];

            this.charge = JSON.parse(JSON.stringify({
                id: charge_id,
                is_archived: false,
                date: new Date(), // Default to today
                trackingNumbersInput: "",
                tracking_numbers: [],
                note: null,
                tracking_onp_numbers: [],
                total_price: null,
                total_weight: null,
                origin_id: null,
                fishery_id: null,
                supplier_ids: [],
                truck_ids: [],
                charge_category_id: this.charge_category.getList[0]?.id || 1,
                daily_charge_id: null,
                warehouse_category_operations: [
                    {
                        id: _warehouse_category_operation?.id || null,
                        charge_details: [],
                        charge_id: charge_id,
                        total_weight: 0.00,
                        total_price: 0.00,
                    }
                ]
            }))
            console.log("this.charge", this.charge);
        },
        delete: async function (ids) {
            console.log("ids", ids)
            let res = await axios.delete('/api/charge', {
                params: {
                    ids
                }
            });
            this.charge_fetch_items = this.charge_fetch_items.filter(e => ids.includes(e.id) == false);
            this.production_fetch_items = this.production_fetch_items.filter(e => ids.includes(e.id) == false);
        },
        get_first_non_disabled_warehouse_category_operation: function () {
            const warehouse_category_operation_ids = this.charge.warehouse_category_operations?.map((warehouse_category_operation) => warehouse_category_operation.id) || [];
            return this.warehouse_category_operation.getList.filter((operation) => !warehouse_category_operation_ids.includes(operation.id))[0];
        },
        add_warehouse_category_operation: function () {
            let _warehouse_category_operation = this.get_first_non_disabled_warehouse_category_operation();

            this.charge.warehouse_category_operations.push({
                id: _warehouse_category_operation?.id || null,
                charge_details: [],
                charge_id: this.charge.id,
                total_weight: 0.00,
                total_price: 0.00,
            });
            console.log("add_warehouse_category_operation", this.charges);
        },
        delete_warehouse_category_operation: function (warehouse_category_operation) {
            this.charge.warehouse_category_operations = this.charge.warehouse_category_operations.filter(o => o.id != warehouse_category_operation.id);
            console.log("delete_warehouse_category_operation", warehouse_category_operation, this.charges);
        },
        add_charge_detail: function (warehouse_category_operation_id, charge_detail) {
            this.charge.warehouse_category_operations
                .find(o => o.id == warehouse_category_operation_id)
                .charge_details.push(charge_detail);
        },
        edit_charge_detail: function (warehouse_category_operation_id, charge_detail) {
            let _charge_detail = this.charge.warehouse_category_operations
                .find(o => o.id == warehouse_category_operation_id)
                .charge_details.find(d => d.id == charge_detail.id);

            Object.assign(_charge_detail, charge_detail);//dosen't replace the object reference to keep reactivity
        },
        remove_charge_detail: function (warehouse_category_operation_id, charge_detail_id) {
            this.charge.warehouse_category_operations
                .find(o => o.id == warehouse_category_operation_id)
                .charge_details = this.charge.warehouse_category_operations
                    .find(o => o.id == warehouse_category_operation_id)
                    .charge_details.filter(d => d.id != charge_detail_id);
        },
        deleteMany: function (selected) {
            this.charges = this.charges.filter(detail => !selected.includes(detail.id));
            this.UpdateCharge();
            this.main.add_snackbar({
                show: true,
                message: i18n.global.t("delete_many_charges"),
                color: "success"
            });
            console.log("deleteMany", this.charges);
        },
        submitCharge: function (router) {
            const chargeData = JSON.parse(JSON.stringify(this.charge));
            axios.post("/api/charge", this.charge)
                .then(response => {
                    console.log("charge_category_operation submitted successfully:", response.data);

                    // Trigger creation notification
                    const notification = useNotification();
                    const supplierNames = (chargeData.supplier_ids || [])
                        .map(id => this.main.supplier.list.find(s => s.id === id)?.name)
                        .filter(Boolean)
                        .join(', ');

                    const fields = [];
                    if (chargeData.tracking_numbers?.length) {
                        fields.push({
                            label: i18n.global.t('tracking_numbers'),
                            value: chargeData.tracking_numbers.join(', '),
                            icon: 'mdi-barcode'
                        });
                    }
                    if (supplierNames) {
                        fields.push({
                            label: i18n.global.t('suppliers'),
                            value: supplierNames,
                            icon: 'mdi-truck-delivery'
                        });
                    }
                    if (chargeData.total_price) {
                        fields.push({
                            label: i18n.global.t('total_price'),
                            value: this.main.formatDisplayNumber(chargeData.total_price) + ' ' + this.main.currency,
                            icon: 'mdi-cash'
                        });
                    }
                    if (chargeData.total_weight) {
                        fields.push({
                            label: i18n.global.t('total_weight'),
                            value: this.main.formatDisplayNumber(chargeData.total_weight) + ' Kg',
                            icon: 'mdi-weight-kilogram'
                        });
                    }

                    notification.notifyCreation({
                        type: 'charges',
                        icon: 'mdi-warehouse',
                        color: 'info',
                        fields
                    });

                    // Redirect to view charge page
                    const chargeId = response.data?.data?.id || response.data?.charge?.id || response.data?.id;
                    console.log("Redirect to view_charge with ID:", chargeId, "response.data:", response.data);
                    if (router && chargeId) {
                        router.push({ name: 'view_charge', params: { id: chargeId } });
                    }
                })
                .catch(error => {
                    console.error("Error submitting charge_category_operation:", error);
                });
        },
        fetchCharge: async function (date_start, date_end) {
            this.loading = true;
            let response = await this.main.fetchData(date_start, date_end, this.api_url)
            this.charge_fetch_items = response.charges;
            this.invoice.tracking_numbers = response.tracking_numbers;
            this.non_warehouse_stockable_operations = this.charge_fetch_items.flatMap(e => e.non_warehouse_stockable_operations.map(ee => { ee.charge_category_id = e.charge_category_id; return ee }))
            this.warehouse_stockable_tracking_number_operations = this.charge_fetch_items.flatMap(e => e.warehouse_stockable_tracking_number_operations.map(ee => { ee.charge_category_id = e.charge_category_id; return ee }))
            this.loading = false;
        },
        fetchProductionCharges: async function (date_start, date_end) {
            this.loading = true;
            try {
                const response = await this.main.fetchData(date_start, date_end, this.production_api_url);
                this.production_fetch_items = response.charges || [];
            } catch (e) {
                console.error('Error fetching production charges:', e);
                this.production_fetch_items = [];
            }
            this.loading = false;
        },
        fetchChargeById: function (id) {
            axios.get(`/api/charge/${id}`).then(response => {
                this.edit_charge.id = response.data.charge.id;
                this.edit_charge.is_archived = false;
                this.edit_charge.date = response.data.charge.date; // Default to today
                this.edit_charge.trackingNumbersInput = "";
                this.edit_charge.tracking_numbers = response.data.charge.tracking_numbers;
                this.edit_charge.note = response.data.charge.note;
                this.edit_charge.tracking_onp_numbers = response.data.charge.tracking_onp_numbers;
                this.edit_charge.origin_id = response.data.charge.origin_id;
                this.edit_charge.fishery_id = response.data.charge.fishery_id;
                this.edit_charge.supplier_ids = response.data.charge.suppliers.map(s => s.id);
                this.edit_charge.truck_ids = response.data.charge.truck_ids;
                this.edit_charge.charge_category_id = response.data.charge.charge_category_id;
                this.edit_charge.daily_charge_id = null;
                this.edit_charge.total_price = response.data.charge.total_price;
                this.edit_charge.total_weight = response.data.charge.total_weight;
                this.edit_charge.warehouse_category_operations = [
                    {
                        id: 1,
                        charge_details: [],
                        charge_id: this.edit_charge.id,
                        total_weight: 0.00,
                        total_price: 0.00,
                    }
                ];
            });
        },
        fetchChargeForView: async function (id) {
            this.view_charge_loading = true;
            this.view_charge = null;
            try {
                const response = await axios.get(`/api/charge/${id}`);
                this.view_charge = response.data.charge;
            } catch (error) {
                console.error("Error fetching charge for view:", error);
            } finally {
                this.view_charge_loading = false;
            }
        },
        PostInvoice: async function (supplier_invoice) {
            this.loading = true;
            let response = await axios.post("/api/supplier_invoice", supplier_invoice);
            response.data.supplier_invoice.charge_ids.forEach(charge_id => {
                let item = this.charge_fetch_items.find(i => i.id == charge_id);
                if (item) {
                    item.invoice_id = response.data.supplier_invoice.id;
                    item.supplier_invoice_tracking_number = response.data.supplier_invoice.tracking_numbers;
                    let invoice = this.supplier_invoice.supplier_invoice_fetch_items.find(i => i.id == response.data.supplier_invoice.id)
                    if (invoice) {
                        invoice = response.data.supplier_invoice; // Update the invoice in the supplier invoice store to update the tracking numbers and other details related to the invoice
                    } else {
                        this.supplier_invoice.supplier_invoice_fetch_items.push(response.data.supplier_invoice);
                    }
                }
            });
            this.invoice = {
                date: new Date(),
                type_invoice: "local",
                invoice_invoicer_ids: [],
                tracking_numbers: response.data.tracking_numbers,
                charge_ids: []
            };
            this.main.snackbar = {
                action_command: true,
                action_command_icon_color: "success",
                action_command_icon: "mdi-printer-check",
                action_command_text: i18n.global.t('supplier_invoice_created', {
                    tracking_numbers: response.data.supplier_invoice.tracking_numbers.toString()
                })
            };
            this.loading = false;
        },
        UpdateCharge: function () {
            this.charge.total_weight = +this.charge.warehouse_category_operations.reduce((total, operation) => {
                return +total + operation.charge_details.reduce((opTotal, detail) => opTotal + (+detail.total_weight_bill || 0), 0);
            }, 0);
            this.charge.total_price = +this.charge.warehouse_category_operations.reduce((total, operation) => {
                return +total + operation.charge_details.reduce((opTotal, detail) => opTotal + (+detail.total_price || 0), 0);
            }, 0);
            localStorage.setItem("charge", JSON.stringify(this.charge));
            localStorage.setItem("charges", JSON.stringify(this.charges));
            localStorage.setItem("showAdditionalInfo", this.showAdditionalInfo);
        },
        undoInvoiceItem: async function (item_id) {
            try {
                let res = await axios.get(`/api/supplier_invoice/undo-invoice-item/${item_id}`);
                let item = this.charge_fetch_items.find(i => i.id == item_id);
                if (item) item.invoice_id = null;
                let charge_item = this.charge.warehouse_category_operations.flatMap(o => o.charge_details).find(d => d.id == item_id);
                if (charge_item) charge_item.invoice_id = null;
                this.main.add_snackbar({
                    show: true,
                    message: i18n.global.t("undo_charge_from_invoice_item_successfully", { supply_number: res.data.supplier_invoice_tracking_numbers.toString(), charge_number: item.tracking_numbers.toString() }),
                    color: "success"
                });
            } catch (error) {
                console.log("error", error)
            }
        },
        archive: function () {
            this.charge.is_archived = true;
            const chargeData = JSON.parse(JSON.stringify(this.charge));
            this.charges.push(chargeData);
            this.add_charge();
            this.UpdateCharge();
            this.main.add_snackbar({
                show: true,
                message: i18n.global.t("charge_archived_successfully"),
                color: "success"
            });

            // Rich notification with charge details
            const notification = useNotification();
            const supplierNames = chargeData.supplier_ids
                .map(id => this.main.supplier.list.find(s => s.id === id)?.name)
                .filter(Boolean)
                .join(', ');

            const fields = [];
            if (chargeData.tracking_numbers?.length) {
                fields.push({
                    label: i18n.global.t('tracking_numbers'),
                    value: chargeData.tracking_numbers.join(', '),
                    icon: 'mdi-barcode'
                });
            }
            if (supplierNames) {
                fields.push({
                    label: i18n.global.t('suppliers'),
                    value: supplierNames,
                    icon: 'mdi-truck-delivery'
                });
            }
            if (chargeData.total_price) {
                fields.push({
                    label: i18n.global.t('total_price'),
                    value: this.main.formatDisplayNumber(chargeData.total_price) + ' ' + this.main.currency,
                    icon: 'mdi-cash'
                });
            }
            if (chargeData.total_weight) {
                fields.push({
                    label: i18n.global.t('total_weight'),
                    value: this.main.formatDisplayNumber(chargeData.total_weight) + ' Kg',
                    icon: 'mdi-weight-kilogram'
                });
            }

            notification.notifyCreation({
                type: 'charges',
                icon: 'mdi-warehouse',
                color: 'info',
                fields
            });

            console.log("archive charges", this.charge, this.charges);
        },
        edit_archived_charge: function (item_id) {
            this.charge = JSON.parse(JSON.stringify(this.charges.find(item => item.id === item_id)));
            console.log("edit_archived_charge", this.charge);
        },
        update_archive: function () {
            let charge = this.charges.find(c => c.id === this.charge.id);
            Object.assign(charge, this.charge);
            this.add_charge();
            this.UpdateCharge();
            this.main.add_snackbar({
                show: true,
                message: i18n.global.t("editing_archived_charge"),
                color: "success"
            });
            console.log("update_archive", charge);
        },
        reset: function () {
            this.add_charge();
            this.UpdateCharge();
            this.main.add_snackbar({
                show: true,
                message: i18n.global.t("reset_charge"),
                color: "success"
            });
            console.log("reset charge", this.charge);
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
        getCharge: (state) => {
            return state.charge_category.getList.find(e => e.id == state.charge.charge_category_id) || {};
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
        charge_categories: (state) => {
            return state.charge_category.getList;
        },
        unique_warehouse_category_operations_list: (state) => {
            const warehouse_category_operation_ids = state.charges.map((charge_category_operation) => charge_category_operation.id);
            return state.warehouse_category_operation_for_charge.map((operation) => operation.disabled = !warehouse_category_operation_ids.includes(operation.id) ? false : true) && state.warehouse_category_operation_for_charge;
        },
        get_fetched_operations: (state) => {
            let non_warehouse_stockable_operations = state.filtred_charge_fetch_items.flatMap(e => e.non_warehouse_stockable_operations)
            let warehouse_stockable_tracking_number_operations = state.filtred_charge_fetch_items.flatMap(e => e.warehouse_stockable_tracking_number_operations)

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
            return state.warehouse_category_operation.list.filter(o => !ids.has(o.id))
        },
        get_formulas_list: (state) => {
            let formulas = state.formula.getList.filter(e => e.type == "charge");
            return formulas;
        },
        warehouse_category_operation_for_charge: (state) => {
            return state.warehouse_category_operation.getList.filter(e => e.operation_type == "charge");
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