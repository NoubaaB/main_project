import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useWebWorkerFn } from '@vueuse/core';

import { useFishType } from "./FishType";
import { usePackage } from "./Package";
import { useWarehouse } from "./Warehouse";
import { useChargeCategory } from "./ChargeCategory";

export const useCharge = defineStore("charge", {
    state: () => {
        return {
            showAdditionalInfo: false,
            fish_type: useFishType(),
            package: usePackage(),
            warehouse: useWarehouse(),
            charge_category: useChargeCategory(),
            charge: {
                id: uuidv4(),
                date: new Date(), // Default to today
                trackingNumbersInput: "",
                tracking_numbers: [],
                note: null,
                tracking_onp_numbers: [],
                charge_details_total_price: null,
                charge_details_total_weight: null,
                is_stockable: false,
                charge_details: [],
                production_details: [],
                origin_id: null,
                fishery_id: null,
                supplier_ids: [],
                charge_category_id: 1,
                daily_charge_id: null,
            },
            charge_detail: {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
                warehouse_id: null,
                package_id: null,
            },
            production_detail: {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
                warehouse_id: null,
                package_id: null,
            },
            charges: [],
            add_charge_details: true,
            add_production_details: false,

        };
    },
    actions: {
        submitCharge: function () {
            axios.post("/api/charge", this.charge)
                .then(response => {
                    console.log("charge submitted successfully:", response.data);
                })
                .catch(error => {
                    console.error("Error submitting charge:", error);
                });
        },
        add_charge_detail: function () {
            this.charge.charge_details.push({
                ...this.charge_detail,
            });
            this.charge_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
            };
        },
        add_production_detail: function () {
            this.charge.production_details.push({
                ...this.production_detail,
            });
            this.production_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
            };
        },
        remove_charge_detail: function (id) {
            this.charge.charge_details = this.charge.charge_details.filter(detail => detail.id !== id);
        },
        remove_production_detail: function (id) {
            this.charge.production_details = this.charge.production_details.filter(detail => detail.id !== id);
        },
        UpdateCharge: function () {
            localStorage.setItem("charge", JSON.stringify(this.charge));
            localStorage.setItem("charge_detail", JSON.stringify(this.charge_detail));
            localStorage.setItem("production_detail", JSON.stringify(this.production_detail));
            localStorage.setItem("charges", JSON.stringify(this.charges));
            localStorage.setItem("add_charge_details", this.add_charge_details);
            localStorage.setItem("add_production_details", this.add_production_details);
            localStorage.setItem("showAdditionalInfo", this.showAdditionalInfo);
            this.charge.charge_details_total_weight = this.charge.charge_details.reduce((acc, detail) => acc + +detail.weight_bill, 0);
            this.charge.charge_details_total_price = this.charge.charge_details.reduce((acc, detail) => acc + +detail.price, 0);
        },
        archive: function () {
            this.charges.push(this.charge);
            this.reset();
        },
        reset: function () {
            this.charge = {
                id: uuidv4(),
                date: new Date(), // Default to today
                trackingNumbersInput: "",
                tracking_numbers: [],
                note: null,
                tracking_onp_numbers: [],
                charge_details_total_price: null,
                charge_details_total_weight: null,
                is_stockable: false,
                charge_details: [],
                production_details: [],
                origin_id: null,
                fishery_id: null,
                supplier_ids: [],
                charge_category_id: 1,
                daily_charge_id: null,
            };
            this.charge_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
            };
            this.production_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
            };
        },
        reset_charge_detail: function () {
            this.charge_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
                warehouse_id: null,
                package_id: null,
            };
        },
        reset_production_detail: function () {
            this.production_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
                warehouse_id: null,
                package_id: null,
            };
        },
        deleteMany: function (selected) {
            this.charges = this.charges.filter(obj => !selected.includes(obj.id))
        },
        edit_charge_detail: function (id) {
            this.charge_detail = this.charge.charge_details.find(detail => detail.id === id);
        },
        edit_production_detail: function (id) {
            this.production_detail = this.charge.production_details.find(detail => detail.id === id);
        },
        update_charge_detail: function () {
            const index = this.charge.charge_details.findIndex(detail => detail.id === this.charge_detail.id);
            if (index !== -1) {
                this.charge.charge_details[index] = { ...this.charge_detail };
            }
            this.reset_charge_detail();
        },
        update_production_detail: function () {
            const index = this.charge.production_details.findIndex(detail => detail.id === this.production_detail.id);
            if (index !== -1) {
                this.charge.production_details[index] = { ...this.production_detail };
            }
            this.reset_production_detail();
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
        getPackages: (state) => {
            return state.package.getList;
        },
        getWarehouses: (state) => {
            return state.warehouse.getList;
        },
        charge_categories: (state) => {
            return state.charge_category.getList;
        },
        type_detail: (state) => {
            switch (state.charge.charge_category_id) {
                case 0:
                    return [];
                case 1:
                    return state.fish_type.getList;
                default:
                    return "";
            }
        }
    },
});