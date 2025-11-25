import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useWebWorkerFn } from '@vueuse/core';

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
            fish_type: useFishType(),
            package_type: usePackageType(),
            guano_type: useGuanoType(),
            warehouse: useWarehouse(),
            charge_category: useChargeCategory(),
            warehouse_category_operation: useWarehouseCategoryOperation(),
            charge: {
                id: uuidv4(),
                date: new Date(), // Default to today
                trackingNumbersInput: "",
                tracking_numbers: [],
                note: null,
                tracking_onp_numbers: [],
                total_price: null,
                total_weight: null,
                is_stockable: false,
                warehouse_stockable_tracking_number_operations: [],
                non_warehouse_stockable_operations: [],
                origin_id: null,
                fishery_id: null,
                supplier_ids: [],
                charge_category_id: 1,
                daily_charge_id: null,
            },
            charge_details: [],
            charges: [],

        };
    },
    actions: {
        add_charges: function () {
            this.charges.push({
                id: uuidv4(),
                warehouse_category_operation_id: this.warehouse_category_operation.getList[0]?.id || null,
            });  
        },
        delete_detail: function (charge_id) {
            this.charges = this.charges.filter(e => e.id != charge_id);
        },
        submitCharge: function () {
            axios.post("/api/charge", this.charge)
                .then(response => {
                    console.log("charge submitted successfully:", response.data);
                })
                .catch(error => {
                    console.error("Error submitting charge:", error);
                });
        },
        UpdateCharge: function () {
            localStorage.setItem("charge", JSON.stringify(this.charge));
            localStorage.setItem("warehouse_stockable_tracking_number_operation", JSON.stringify(this.warehouse_stockable_tracking_number_operation));
            localStorage.setItem("non_warehouse_stockable_operation", JSON.stringify(this.non_warehouse_stockable_operation));
            localStorage.setItem("charges", JSON.stringify(this.charges));
            localStorage.setItem("showAdditionalInfo", this.showAdditionalInfo);
            this.charge.total_weight = this.charge.warehouse_stockable_tracking_number_operations.reduce((acc, detail) => acc + +detail.weight_bill, 0);
            this.charge.total_price = this.charge.warehouse_stockable_tracking_number_operations.reduce((acc, detail) => acc + +detail.price, 0);
        },
        archive: function () {
            this.charges.push(this.charge);
            this.reset();
        },
        getFishTypeName: function (fish_type_id) {
            const fishType = this.fish_type.getList.find(type => type.id === fish_type_id);
            return fishType ? fishType.name : 'N/A';
        },
        getWarehouseName: function (warehouse_id) {
            const warehouse = this.getWarehouses.find(warehouse => warehouse.id === warehouse_id);
            return warehouse ? warehouse.name : 'N/A';
        },
        getPackageName: function (package_id) {
            const packageItem = this.getPackages.find(pkg => pkg.id === package_id);
            return packageItem ? packageItem.name : 'N/A';
        },
        getPackageSize: function (package_id, wieght) {
            const packageItem = this.getPackages.find(pkg => pkg.id === package_id);
            return packageItem ? packageItem.size : wieght;
        }
    },
    getters: {
        getTypeCharge: (state) => {
            if (state.charge.charge_category_id == null) {
                return null;
            }
            return state.charge_categories?.find(e => e.name == state.charge.charge_category_id)?.list_type_details ?? [];
        },
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
    },
});