import { defineStore } from "pinia";
import { useMain } from "./Main";

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
            },
            main : useMain()
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
        get_charge_category_name_by_id: function (id) {
            return this.list.find(c => c.id == id).description;
        }
    },
    getters: {
        getList: state => state.list.map(e => {
            e.name = e[`name_${state.main.lang.code}`];
            return e;
        })
    }
});