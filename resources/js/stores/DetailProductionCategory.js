import { defineStore } from "pinia";

export const useDetailProductionCategory = defineStore("detailProductionCategory", {
    state: () => ({ list: [] }),
    getters: {
        getList: state => state.list,
        getByProductionCategoryId: state => (id) => state.list.filter(d => d.production_category_id === id),
    }
});
