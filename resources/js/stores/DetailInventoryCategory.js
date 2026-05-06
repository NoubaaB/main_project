import { defineStore } from "pinia";

export const useDetailInventoryCategory = defineStore("detailInventoryCategory", {
    state: () => ({ list: [] }),
    getters: {
        getList: state => state.list,
    }
});
