import { defineStore } from "pinia";

export const useStock = defineStore("Stock", {
    state: () => {
        return {
            list: [],
            model: {
                name: "",
                description: "",
                total_weight: 0.00,
                total_price: 0.00,
                total_package_count: 0.00,
            }
        };
    },
    actions: {
        addStock: function (stock) {
            this.list.push(stock);
        },
        deleteStock: function (stock) {
            this.list = this.list.filter(e => e.id != stock.id);
        },
        updateStock: function (stock) {
            console.log("stock", stock)
        },
    },
    getters: {
        getList: state => state.list
    }
});