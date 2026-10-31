import axios from "axios";
import { defineStore } from "pinia";

export const useSupplier = defineStore("supplier", {
    state: () => ({
        list: [],
        loading: false,
        model: {
            name: "",
            ice: "",
            country: "",
            city: "",
            address: "",
            tel: "",
            logo: null,
            state: true,
        }
    }),
    actions: {
        async fetchSuppliers() {
            this.loading = true;
            try {
                const res = await axios.get("/api/suppliers");
                this.list = res.data.suppliers || [];
                return this.list;
            } catch (e) {
                console.error("[useSupplier] fetch error:", e);
                return [];
            } finally {
                this.loading = false;
            }
        },
        async add(name) {
            const res = await axios.post("/api/supplier", { name });
            const supplier = res.data.supplier;
            if (!this.list.find(s => s.id === supplier.id)) {
                this.list.push(supplier);
            }
            return supplier.id;
        },
        async createSupplier(payload) {
            const res = await axios.post("/api/suppliers", payload);
            const supplier = res.data.supplier;
            const idx = this.list.findIndex(s => s.id === supplier.id);
            if (idx === -1) {
                this.list.push(supplier);
            } else {
                this.list[idx] = supplier;
            }
            return supplier;
        },
        async updateSupplier(id, payload) {
            const res = await axios.put(`/api/suppliers/${id}`, payload);
            const supplier = res.data.supplier;
            const idx = this.list.findIndex(s => s.id === supplier.id);
            if (idx !== -1) {
                this.list[idx] = supplier;
            } else {
                this.list.push(supplier);
            }
            return supplier;
        },
        async deleteSupplier(id) {
            await axios.delete(`/api/suppliers/${id}`);
            this.list = this.list.filter(e => e.id !== id);
        },
        async checkPlafond(supplierId, date = null, amount = 0) {
            if (!supplierId) return null;
            const params = {};
            if (date) params.date = date;
            if (amount !== undefined && amount !== null) params.amount = amount;
            const res = await axios.get(`/api/suppliers/${supplierId}/check-plafond`, { params });
            return res.data;
        },
        get_supplier_name_by_id(id) {
            const name = this.list.find(s => s.id == id)?.name;
            return name || 'N/A';
        }
    },
    getters: {
        getList: state => state.list,
        getMinList: state => state.list.map(e => ({ id: e.id, name: e.name }))
    }
});
