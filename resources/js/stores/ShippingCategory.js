import { defineStore } from "pinia";
import { useMain } from "./Main";

export const useShippingCategory = defineStore("sellCategory", {
    state: () => ({ list: [] }),
    actions: {
        get_shipping_category_name_by_id(id) {
            const lang = useMain().lang?.code || 'en';
            const cat = this.list.find(c => c.id == id);
            if (!cat) return `#${id}`;
            return cat[`name_${lang}`] || cat.name || `#${id}`;
        }
    },
    getters: {
        getList: state => state.list,
    }
});

