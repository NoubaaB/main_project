import axios from "axios";
import { defineStore } from "pinia";

export const useBankName = defineStore("bankName", {
    state: () => ({
        list: [],
        loading: false,
    }),
    actions: {
        async fetchAll() {
            this.loading = true;
            try {
                const r = await axios.get("/api/bank-names");
                this.list = r.data.bank_names || [];
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        async create(name) {
            const r = await axios.post("/api/bank-names", { name });
            const bankName = r.data.bank_name;
            this.list.push(bankName);
            return bankName;
        },
        async update(id, name) {
            const r = await axios.put(`/api/bank-names/${id}`, { name });
            const idx = this.list.findIndex(b => b.id === id);
            if (idx !== -1) this.list[idx] = r.data.bank_name;
            return r.data.bank_name;
        },
        async remove(id) {
            await axios.delete(`/api/bank-names/${id}`);
            this.list = this.list.filter(b => b.id !== id);
        },
    },
    getters: {
        getList: state => state.list,
        nameItems: state => state.list.map(b => ({ title: b.name, value: b.name })),
    },
});
