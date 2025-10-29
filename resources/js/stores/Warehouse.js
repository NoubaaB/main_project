import { defineStore } from "pinia";

export const useWarehouse = defineStore("Warehouse", {
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
        addWarehouse: function (warehouse) {
            this.list.push(warehouse);
        },
        deleteWarehouse: function (warehouse) {
            this.list = this.list.filter(e => e.id != warehouse.id);
        },
        updateWarehouse: function (warehouse) {
            console.log("warehouse", warehouse)
        },
    },
    getters: {
        getList: state => state.list
    }
});