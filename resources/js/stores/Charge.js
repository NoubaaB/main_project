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


export const useCharge = defineStore("charge", {
    state: () => {
        return {
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
            },
            charge: {},
            charges: [],
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
            console.log("deleteMany", this.charges);
        },
        submitCharge: function () {
            axios.post("/api/charge_category_operation", this.charge_category_operation)
                .then(response => {
                    console.log("charge_category_operation submitted successfully:", response.data);
                })
                .catch(error => {
                    console.error("Error submitting charge_category_operation:", error);
                });
        },
        UpdateCharge: function () {
            this.charge.total_weight = this.charge.warehouse_category_operations.reduce((total, operation) => {
                return +total + operation.charge_details.reduce((opTotal, detail) => opTotal + (+detail.weight_bill || 0), 0);
            }, 0);
            this.charge.total_price = this.charge.warehouse_category_operations.reduce((total, operation) => {
                return +total + operation.charge_details.reduce((opTotal, detail) => opTotal + (+detail.price || 0), 0);
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
            console.log("update_archive", charge);
        },
        reset: function () {
            this.add_charge();
            this.UpdateCharge();
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
            switch (stockable_type) {
                case "package_type":
                    return this.getPackages.find(e => e.id == stockable_id)[`name_${this.main.lang.code}`];
                case "fish_type":
                    return this.getFishTypes.find(e => e.id == stockable_id)[`name_${this.main.lang.code}`];
                case "guano_type":
                    return this.getGuanoTypes.find(e => e.id == stockable_id)[`name_${this.main.lang.code}`];
                default:
                    return stockable_id;
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
        }
    },
});