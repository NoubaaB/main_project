import { defineStore } from "pinia";

export const useChargeType = defineStore("charge_type", {
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
        addCharge: function (charge) {
            this.list.push(charge);
        },
        deleteCharge: function (charge) {
            this.list = this.list.filter(e => e.id != charge.id);
        },
        updateCharge: function (charge) {
            console.log("charge", charge)
        },
    },
    getters: {
        getList: state => state.list
    }
});