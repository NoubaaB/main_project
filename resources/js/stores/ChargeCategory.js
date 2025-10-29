import { defineStore } from "pinia";

export const useChargeCategory = defineStore("charge_category", {
    state: () => {
        return {
            list: [],
            model: {
                date: new Date(),
                description: "",
                pu: 0.00,
                qte: 0,
                total: 0,
                variable_charge_id: null,
            }
        };
    },
    actions: {
        addChargeCategory: function (charge_catgeory) {
            this.list.push(charge_catgeory);
        },
        deleteChargeCategory: function (charge_catgeory) {
            this.list = this.list.filter(e => e.id != charge_catgeory.id);
        },
        updateChargeCategory: function (charge_catgeory) {
            console.log("charge_catgeory", charge_catgeory)
        },
    },
    getters: {
        getList: state => state.list
    }
});