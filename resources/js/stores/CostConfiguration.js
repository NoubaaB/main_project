import axios from "axios";
import { defineStore } from "pinia";

export const useCostConfiguration = defineStore("costConfiguration", {
    state: () => ({
        list: [],
        categories: {
            charge: [],
            production: [],
            shipping: [],
        },
        dataSources: [],
        loading: false,
    }),
    actions: {
        async fetchAll() {
            this.loading = true;
            try {
                const r = await axios.get("/api/cost-configurations");
                this.list = r.data.cost_configurations || [];
            } catch (e) {
                console.error("Error fetching cost configurations:", e);
            }
            this.loading = false;
        },
        async fetchCategoriesAndSources() {
            try {
                const r = await axios.get("/api/cost-configurations/categories-and-sources");
                if (r.data.categories) {
                    this.categories = r.data.categories;
                }
                if (r.data.data_sources) {
                    this.dataSources = r.data.data_sources;
                }
            } catch (e) {
                console.error("Error fetching categories and sources:", e);
            }
        },
        async create(payload) {
            const r = await axios.post("/api/cost-configurations", payload);
            const item = r.data.cost_configuration;
            this.list.unshift(item);
            return item;
        },
        async update(id, payload) {
            const r = await axios.put(`/api/cost-configurations/${id}`, payload);
            const item = r.data.cost_configuration;
            const idx = this.list.findIndex(c => c.id === id);
            if (idx !== -1) {
                this.list.splice(idx, 1, item);
            }
            return item;
        },
        async remove(id) {
            await axios.delete(`/api/cost-configurations/${id}`);
            this.list = this.list.filter(c => c.id !== id);
        },
        async evaluateFormula(formula, values) {
            const r = await axios.post("/api/cost-configurations/evaluate", { formula, values });
            return r.data;
        },
    },
    getters: {
        getList: state => state.list,
        activeList: state => state.list.filter(c => c.enabled),
    },
});
