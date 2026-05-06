import { defineStore } from "pinia";

export const useDetailShippingCategory = defineStore("detailSellCategory", {
    state: () => ({ list: [] }),
    getters: {
        getList: state => state.list,
        getBySellCategoryId: state => (id) => state.list.filter(d => d.sell_category_id === id),
    }
});

