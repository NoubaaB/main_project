import { defineStore } from "pinia";
import { useMain } from "./Main";

export const useSellCategory = defineStore("sell_category", {
    state: () => {
        return {
            list: [],
            model: {
                date: new Date(),
                description: "",
                pu: 0.00,
                qte: 0,
                total: 0,
                variable_sell_id: null,
            },
            main : useMain()
        };
    },
    actions: {
        addSellCategory: function (sell_catgeory) {
            this.list.push(sell_catgeory);
        },
        deleteSellCategory: function (sell_catgeory) {
            this.list = this.list.filter(e => e.id != sell_catgeory.id);
        },
        updateSellCategory: function (sell_catgeory) {
            console.log("sell_catgeory", sell_catgeory)
        },
        get_sell_category_name_by_id: function (id) {
            const category = this.list.find(c => c.id == id);
            return category ? category.description : 'N/A';
        }
    },
    getters: {
        getList: state => state.list.map(e => {
            e.name = e[`name_${state.main.lang.code}`];
            return e;
        })
    }
});
