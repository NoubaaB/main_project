import { defineStore } from "pinia";

export const useWarehouseCategoryOperation = defineStore("warehouse_category_operations", {
    state: () => {
        return {
            list: [],
            model: {
                name_fr: "",
                name_en: "",
                name_es: "",
                description: "",
                allow_price: true,
                allow_weight: true,
                is_stockable: true,
                operation_type: "both"
            }
        };
    },
    actions: {
        addWarehouseCategoryOperation: function (warehouse_category_operations) {
            this.list.push(warehouse_category_operations);
        },
        deleteWarehouseCategoryOperation: function (warehouse_category_operations) {
            this.list = this.list.filter(e => e.id != warehouse_category_operations.id);
        },
        updateWarehouseCategoryOperation: function (warehouse_category_operations) {
            console.log("warehouse_category_operations", warehouse_category_operations)
        },
    },
    getters: {
        getList: state => state.list
    }
});