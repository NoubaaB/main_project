import { defineStore } from "pinia";

export const useVariableCharge = defineStore("variable_charge", {
    state: () => {
        return {
            list: [],
            model: {
                date:new Date(),
                days:null
            }
        };
    },
    actions: {
        addVariableCharge: function (variable_charge) {
            this.list.push(variable_charge);
        },
        deleteVariableCharge: function (variable_charge) {
            this.list = this.list.filter(e => e.id != variable_charge.id);
        },
        updateVariableCharge: function (variable_charge) {
            console.log("variable_charge", variable_charge)
        },
    },
    getters: {
        getList: state => state.list
    }
});