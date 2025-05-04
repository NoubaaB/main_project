import { defineStore } from "pinia";

export const useStaticCharge = defineStore("static_charge", {
    state: () => {
        return {
            list: [],
            model: {
                description: "",
                total: 0.00,
                date_start: new Date(),
                date_end: new Date()
            }
        };
    },
    actions: {
        addStaticCharge: function (static_charge) {
            this.list.push(static_charge);
        },
        deleteStaticCharge: function (static_charge) {
            this.list = this.list.filter(e => e.id != static_charge.id);
        },
        updateStaticCharge: function (static_charge) {
            console.log("static_charge", static_charge)
        },
    },
    getters: {
        getList: state => state.list
    }
});