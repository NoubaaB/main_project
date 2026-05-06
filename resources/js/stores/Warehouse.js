import { defineStore } from "pinia";

export const useWarehouse = defineStore("warehouse", {
    state: () => ({
        list: [],
    }),
    getters: {
        getList: state => state.list,
        getMinList: state => state.list.map(e => ({ id: e.id, name: e.name })),
    }
});
