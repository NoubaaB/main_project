import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useWebWorkerFn } from '@vueuse/core';

import { useFishType } from "./FishType";
import { usePackage } from "./Package";
import { useStock } from "./Stock";
import { useChargeType } from "./ChargeType";
import { id } from "date-fns/locale";

export const useReceipt = defineStore("receipt", {
    state: () => {
        return {
            showAdditionalInfo:false,
            fish_type: useFishType(),
            package: usePackage(),
            stock: useStock(),
            charge_type: useChargeType(),
            receipt: {
                id: uuidv4(),
                date: new Date(), // Default to today
                trackingNumbersInput: "",
                tracking_numbers: [],
                note:null,
                tracking_onp_numbers: [],
                total_price: null,
                total_weight: null,
                is_stockable: false,
                freeze_fish_production_id: null,
                primary_fish_detail_id: null,
                fishery_id: null,
                fresh_fish_receive_details: [],
                freeze_fish_process_details: [],
                origin_id: null,
                charge_type_id: 1,
                daily_charge_id: null,
            },
            fresh_fish_receive_detail: {
                id: uuidv4(),
                weight:null, 
                weight_bill:null, 
                unit_price:null, 
                price:null, 
                package_count:null, 
                fish_type_id:null, 
                stock_id:null, 
                package_id:null, 
            },
            freeze_fish_process_detail: {
                id: uuidv4(),
                weight:null, 
                weight_bill:null, 
                unit_price:null, 
                price:null, 
                package_count:null, 
                fish_type_id:null, 
                stock_id:null, 
                package_id:null, 
            },
            receipts:[],
            add_fresh_fish_receive_details: true,
            add_freeze_fish_process_details: false,
    
        };
    },
    actions: {
        submitReceipt: function () { 
            axios.post("/api/receipt", this.receipt)
                .then(response => {
                    console.log("Receipt submitted successfully:", response.data);
                })
                .catch(error => {
                    console.error("Error submitting receipt:", error);
                });
        },
        add_fresh_fish_receive_detail: function () {
            this.receipt.fresh_fish_receive_details.push({
                ...this.fresh_fish_receive_detail,
            });
            this.fresh_fish_receive_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
            };
        },
        add_freeze_fish_process_detail: function () {
            this.receipt.freeze_fish_process_details.push({
                ...this.freeze_fish_process_detail,
            });
            this.freeze_fish_process_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
            };
        },
        remove_fresh_fish_receive_detail: function (id) {
            this.receipt.fresh_fish_receive_details = this.receipt.fresh_fish_receive_details.filter(detail => detail.id !== id);
        },
        remove_freeze_fish_process_detail: function (id) {
            this.receipt.freeze_fish_process_details = this.receipt.freeze_fish_process_details.filter(detail => detail.id !== id);
        },
        UpdateReceipt: function () {
            localStorage.setItem("receipt", JSON.stringify(this.receipt));
            localStorage.setItem("fresh_fish_receive_detail", JSON.stringify(this.fresh_fish_receive_detail));
            localStorage.setItem("freeze_fish_process_detail", JSON.stringify(this.freeze_fish_process_detail));
            localStorage.setItem("receipts", JSON.stringify(this.receipts));
            localStorage.setItem("add_fresh_fish_receive_details", this.add_fresh_fish_receive_details);
            localStorage.setItem("add_freeze_fish_process_details", this.add_freeze_fish_process_details);
            localStorage.setItem("showAdditionalInfo", this.showAdditionalInfo);
            this.receipt.total_weight = this.receipt.fresh_fish_receive_details.reduce((acc, detail) => acc + +detail.weight_bill, 0);
            this.receipt.total_price = this.receipt.fresh_fish_receive_details.reduce((acc, detail) => acc + +detail.price, 0);
        },
        archive: function () {
            this.receipts.push(this.receipt);
            this.reset();
        },
        reset: function () {
            this.receipt = {
                id: uuidv4(),
                date: new Date(),
                trackingNumbersInput: "",
                tracking_numbers: [],
                note: null,
                tracking_onp_numbers: [],
                total_price: null,
                total_weight: null,
                is_stockable: false,
                freeze_fish_production_id: null,
                primary_fish_detail_id: null,
                fishery_id: null,
                origin_id: null,
                fresh_fish_receive_details: [],
                freeze_fish_process_details: [],
                charge_type_id: 1,
                daily_charge_id: null,
            };
            this.fresh_fish_receive_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
            };
            this.freeze_fish_process_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
            };
        },
        reset_fresh_fish_receive_detail: function () {
            this.fresh_fish_receive_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
                stock_id: null,
                package_id: null,
            };
        },
        reset_freeze_fish_process_detail: function () {
            this.freeze_fish_process_detail = {
                id: uuidv4(),
                weight: null,
                weight_bill: null,
                unit_price: null,
                price: null,
                package_count: null,
                fish_type_id: null,
                stock_id: null,
                package_id: null,
            };
        },
        deleteMany: function (selected) {
            this.receipts = this.receipts.filter(obj => !selected.includes(obj.id))
        },
        edit_fresh_fish_receive_detail: function (id) {
            this.fresh_fish_receive_detail = this.receipt.fresh_fish_receive_details.find(detail => detail.id === id);
        },
        edit_freeze_fish_process_detail: function (id) {
            this.freeze_fish_process_detail = this.receipt.freeze_fish_process_details.find(detail => detail.id === id);
        },
        update_fresh_fish_receive_detail: function () {
            const index = this.receipt.fresh_fish_receive_details.findIndex(detail => detail.id === this.fresh_fish_receive_detail.id);
            if (index !== -1) {
                this.receipt.fresh_fish_receive_details[index] = { ...this.fresh_fish_receive_detail };
            }
            this.reset_fresh_fish_receive_detail();
        },
        update_freeze_fish_process_detail: function () {
            const index = this.receipt.freeze_fish_process_details.findIndex(detail => detail.id === this.freeze_fish_process_detail.id);
            if (index !== -1) {
                this.receipt.freeze_fish_process_details[index] = { ...this.freeze_fish_process_detail };
            }
            this.reset_freeze_fish_process_detail();
        },
        getFishTypeName: function (fish_type_id) {
            const fishType = this.fish_type.getList.find(type => type.id === fish_type_id);
            return fishType ? fishType.name : 'N/A';
        },
        getStockName: function (stock_id) {
            const stock = this.getStocks.find(stock => stock.id === stock_id);
            return stock ? stock.name : 'N/A';
        },
        getPackageName: function (package_id) {
            const packageItem = this.getPackages.find(pkg => pkg.id === package_id);
            return packageItem ? packageItem.name : 'N/A';
        },
        getPackageSize: function (package_id,wieght) {
            const packageItem = this.getPackages.find(pkg => pkg.id === package_id);
            return packageItem ? packageItem.size : wieght;
        }
    },
    getters: {
        getTypeCharge: (state) => { 
            if (state.receipt.charge_type_id == null) {
                return null;
            }
            return state.type_charges?.find(e => e.name == state.receipt.charge_type_id)?.list_type_details ?? [];
        },
        getPackages: (state) => {
            return state.package.getList;
        },
        getStocks: (state) => {
            return state.stock.getList;
        },
        charge_types: (state) => {
            return state.charge_type.getList;
        },
        type_detail: (state) => {
            switch (state.receipt.charge_type_id) {
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