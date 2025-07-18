import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useWebWorkerFn } from '@vueuse/core';

import { useFishType } from "./FishType";
import { id } from "date-fns/locale";

export const useReceipt = defineStore("receipt", {
    state: () => {
        return {
            showAdditionalInfo:false,
            input_tracking_number: null,
            fish_type: useFishType(),
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
                origin_id: null,
                charge_type_id: null,
                daily_charge_id: null,
            },
            model_fresh_fish_receive_detail: {
                id: uuidv4(),
                weight:0.00, 
                unit_price:0.00, 
                price:0.00, 
                packag_count:0.00, 
                fish_type_id:null, 
                package_id:null, 
            },
            receipts:[],
            fresh_fish_receive_details: [],
            freeze_fish_process_details: [],
            add_fresh_fish_receive_details: true,
            add_freeze_fish_process_details: false,
    
        };
    },
    actions: {
        add: function () {
            
        },
        UpdateReceipt: function () {
            console.log("UpdateReceipt", this.add_freeze_fish_process_details);
            localStorage.setItem("receipt", JSON.stringify(this.receipt));
            localStorage.setItem("receipts", JSON.stringify(this.receipts));
            localStorage.setItem("add_fresh_fish_receive_details", this.add_fresh_fish_receive_details);
            localStorage.setItem("add_freeze_fish_process_details", this.add_freeze_fish_process_details);
            localStorage.setItem("fresh_fish_receive_details", JSON.stringify(this.fresh_fish_receive_details));
            localStorage.setItem("freeze_fish_process_details", JSON.stringify(this.freeze_fish_process_details));
            localStorage.setItem("input_tracking_number", this.input_tracking_number);
            localStorage.setItem("showAdditionalInfo", this.showAdditionalInfo);
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
                charge_type_id: null,
                daily_charge_id: null,
            };
            console.log("reset receipt",this.add_fresh_fish_receive_details);
            this.fresh_fish_receive_details = [];
            this.freeze_fish_process_details = [];
        },
        deleteMany: function (selected) {
            this.receipts = this.receipts.filter(receipt =>
                selected.some(_selected => _selected.id === receipt.id)
            );

        },
    },
    getters: {
        getTypeCharge: (state) => { 
            if (state.input_tracking_number == null) {
                return null;
            }
            return state.type_charges?.find(e => e.name == state.input_tracking_number)?.list_type_details ?? [];
        },
        type_charges: (state) => {
            return [
                {
                    name: 'Frais',
                    allow_fresh_fish_receive_details: true,
                    allow_freeze_fish_process_details: false,
                    add_wieght: true,
                    list_type_details: state.fish_type.getList,
                },
                {
                    name: 'RSW',
                    allow_fresh_fish_receive_details: false,
                    allow_freeze_fish_process_details: true,
                    add_wieght: false,
                    list_type_details: []
                },
                {
                    name: 'Congele',
                    allow_fresh_fish_receive_details: false,
                    allow_freeze_fish_process_details: true,
                    add_wieght: false,
                    list_type_details: []
                },
                {
                    name: 'Frais Revendu',
                    allow_fresh_fish_receive_details: true,
                    allow_freeze_fish_process_details: false,
                    add_wieght: true,
                    list_type_details: []
                },
                {
                    name: 'Congelation',
                    allow_fresh_fish_receive_details: false,
                    allow_freeze_fish_process_details: true,
                    add_wieght: false,
                    list_type_details: []
                },
                {
                    name: 'Emballage',
                    allow_fresh_fish_receive_details: true,
                    allow_freeze_fish_process_details: false,
                    add_wieght: true,
                    list_type_details: []
                },
                {
                    name: 'Glace',
                    allow_fresh_fish_receive_details: true,
                    allow_freeze_fish_process_details: false,
                    add_wieght: true,
                    list_type_details: null
                },
                {
                    name: 'Transport',
                    allow_fresh_fish_receive_details: true,
                    allow_freeze_fish_process_details: false,
                    add_wieght: true,
                    list_type_details: null
                },
                {
                    name: 'Comission',
                    allow_fresh_fish_receive_details: true,
                    allow_freeze_fish_process_details: false,
                    add_wieght: true,
                    list_type_details: null
                },
                {
                    name: 'Autre Charge',
                    allow_fresh_fish_receive_details: true,
                    allow_freeze_fish_process_details: false,
                    add_wieght: true,
                    list_type_details: null
                }
            ];
        }
    },
});