import { defineStore } from "pinia";

export const useWarehouseTransferCategory = defineStore("warehouseTransferCategory", {
    state: () => ({ list: [] }),
    getters: {
        getList: state => state.list,
    }
});
