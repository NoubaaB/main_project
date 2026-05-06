import { defineStore } from "pinia";

export const useDetailChargeCategory = defineStore("detailChargeCategory", {
    state: () => ({ list: [] }),
    getters: {
        getList: state => state.list,
        getByChargeCategoryId: state => (id) => state.list.filter(d => d.charge_category_id === id),
    }
});
