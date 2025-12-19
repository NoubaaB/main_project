import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useWebWorkerFn } from '@vueuse/core';
import { useMain } from "./Main";
import { useFishType } from "./FishType";
import { usePackageType } from "./PackageType";
import { useGuanoType } from "./GuanoType";
import { useWarehouse } from "./Warehouse";
import { useWarehouseCategoryOperation } from "./WarehouseCategoryOperation";
import { useChargeCategory } from "./ChargeCategory";

import i18n  from '@/plugins/i18n'
import { _ } from "core-js";

export const useCharge = defineStore("charge", {
    state: () => {
        return {
            api_url: "/api/get_charges",
            api_url_prop: "charges",
            loading:false,
            showAdditionalInfo: false,
            main: useMain(),
            fish_type: useFishType(),
            package_type: usePackageType(),
            guano_type: useGuanoType(),
            warehouse: useWarehouse(),
            charge_category: useChargeCategory(),
            warehouse_category_operation: useWarehouseCategoryOperation(),
            charge_category_operation: {
                charge_details: [],
                id: null,
                total_weight:0.00,
                total_price:0.00,
            },
            charge: {},
            charges: [],
            charge_fetch_items: [],
            filtred_charge_fetch_items: [],
            warehouse_stockable_tracking_number_operations: [],
            non_warehouse_stockable_operations: [],
        };
    },
    actions: {
        add_charge: function () {
            let charge_id = uuidv4();
            let _warehouse_category_operation = this.warehouse_category_operation.getList[0];

            this.charge =  JSON.parse(JSON.stringify({
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
        get_first_non_disabled_warehouse_category_operation: function () {
            const warehouse_category_operation_ids = this.charge.warehouse_category_operations?.map((warehouse_category_operation) => warehouse_category_operation.id)|| [];
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
        submitCharge: function () {
            axios.post("/api/charge", this.charge)
                .then(response => {
                    console.log("charge_category_operation submitted successfully:", response.data);
                })
                .catch(error => {
                    console.error("Error submitting charge_category_operation:", error);
                });
        },
        fetchCharge: async function (date_start, date_end) {
            this.loading = true;
            this.charge_fetch_items = await this.main.fetchData(date_start, date_end, this.api_url, this.api_url_prop)
            this.non_warehouse_stockable_operations = this.charge_fetch_items.flatMap(e => e.non_warehouse_stockable_operations)
            this.warehouse_stockable_tracking_number_operations = this.charge_fetch_items.flatMap(e => e.warehouse_stockable_tracking_number_operations)
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
            console.log("UpdateCharge",this.charge);
        },
        archive: function () {
            this.charge.is_archived = true;
            this.charges.push(JSON.parse(JSON.stringify(this.charge)));
            this.add_charge();
            this.UpdateCharge();
            this.main.add_snackbar({
                show: true,
                message: i18n.global.t("charge_archived_successfully"),
                color: "success"
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
        getWarehouseName: function (warehouse_id) {
            const warehouse = this.getWarehouses.find(warehouse => warehouse.id === warehouse_id);
            return warehouse ? warehouse.name : 'N/A';
        },
        getPackageName: function (package_type_id) {
            const packageItem = this.getPackages.find(pkg => pkg.id === package_type_id);
            return packageItem ? packageItem[`name_${this.main.lang.code}`] : 'N/A';
        },
        getPackageSize: function (package_type_id, wieght) {
            const packageItem = this.getPackages.find(pkg => pkg.id === package_type_id);
            return packageItem ? packageItem.size : wieght;
        },
        getStockableName: function (stockable_id, stockable_type) {
            // return stockable_type + stockable_id;
            let type = null
            switch (stockable_type) {
                case "PackageType":
                    type = this.getPackages.find(e => e.id == stockable_id);
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
                return type[`name_${this.main.lang.code}`];
            } else {
                return "N/A"
            }
        },
        getWarehouseCategoryOperationName: function (warehouse_category_operation) {
            if (!warehouse_category_operation) return '';
            const operation = this.warehouse_category_operation.getList.find(op => op.id === warehouse_category_operation.id);
            return operation ? operation[`name_${this.main.lang.code}`] : '';
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
            return state.warehouse_category_operation.getList.map((operation) => operation.disabled = !warehouse_category_operation_ids.includes(operation.id) ? false : true) && state.warehouse_category_operation.getList;
        },
        get_fetched_operations: (state) => {
            let non_warehouse_stockable_operations = state.filtred_charge_fetch_items.flatMap(e => e.non_warehouse_stockable_operations)
            let warehouse_stockable_tracking_number_operations = state.filtred_charge_fetch_items.flatMap(e => e.warehouse_stockable_tracking_number_operations)

            return [...non_warehouse_stockable_operations , ...warehouse_stockable_tracking_number_operations]  
        },
        get_fetched_warehouse_category_operation_list: (state) => {
            let warehouse_category_operations = []
            state.get_fetched_operations?.forEach(item => {
                warehouse_category_operations.push({
                    id:uuidv4(),
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
                if (grouped_details.length>0) {
                    sup_unit_price = +(sup_unit_price / grouped_details.length).toFixed(2)
                } else {
                    sup_unit_price = 0;
                }
                data.push({
                    id: uuidv4(),
                    total_weight: +sup_total_weight.toFixed(2),
                    total_price: +(sup_total_price).toFixed(2),
                    total_package_count: +(sup_total_package_count).toFixed(2),
                    unit_price: +(sup_unit_price).toFixed(2),
                    warehouse_category_operation_id:w_id,
                    details: selected_warehouse_category_operations,
                    grouped_details: grouped_details
                })
            });
            return data;
        },
        get_placeholder_warehouse_category_operation_list: (state) => {
            const ids = new Set(state.get_fetched_warehouse_category_operation_list.map(o => o.warehouse_category_operation_id))
            return state.warehouse_category_operation.list.filter(o => !ids.has(o.id))
        }
    },
});