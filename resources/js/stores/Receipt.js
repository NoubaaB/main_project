import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useWebWorkerFn } from '@vueuse/core';


export const useReceipt = defineStore("receipt", {
    state: () => {
        return {
            showAdditionalInfo:false,
            selectedCharge:null,
            type_charges: ['Frais','RSW','Congele','Frais Revendu','Congelation','Glace','Emballage','Transport','Comission','Autre Charge'],
            receipt: {
                date: new Date(), // Default to today
                trackingNumbersInput: "",
                tracking_numbers: [],
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
    
        };
    },
    actions: {
        add: function () {
            
        },
        UpdateReceipt: function (receipt, selectedCharge, showAdditionalInfo) {
            useReceipt().receipt = receipt;
            localStorage.setItem("receipt", JSON.stringify(receipt));
            localStorage.setItem("selectedCharge", selectedCharge);
            localStorage.setItem("showAdditionalInfo", showAdditionalInfo);
        },
    },
    getters: {

    },
});