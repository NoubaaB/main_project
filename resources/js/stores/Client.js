import axios from "axios";
import { defineStore } from "pinia";

export const useClient = defineStore("client", {
    state: () => ({
        list: [],
        loading: false,
        model: {
            name: "",
            total_paid: 0.00,
            total_non_paid: ""
        }
    }),
    actions: {
        async fetchClients() {
            this.loading = true;
            try {
                const res = await axios.get("/api/clients");
                this.list = res.data.clients || [];
                return this.list;
            } catch (e) {
                console.error("[useClient] fetch error:", e);
                return [];
            } finally {
                this.loading = false;
            }
        },
        async add(name) {
            const res = await axios.post("/api/clients", { name });
            const client = res.data.client;
            if (!this.list.find(c => c.id === client.id)) {
                this.list.push(client);
            }
            return client.id;
        },
        async createClient(payload) {
            const res = await axios.post("/api/clients", payload);
            const client = res.data.client;
            const idx = this.list.findIndex(c => c.id === client.id);
            if (idx === -1) {
                this.list.push(client);
            } else {
                this.list[idx] = client;
            }
            return client;
        },
        async updateClient(id, payload) {
            const res = await axios.put(`/api/clients/${id}`, payload);
            const client = res.data.client;
            const idx = this.list.findIndex(c => c.id === client.id);
            if (idx !== -1) {
                this.list[idx] = client;
            } else {
                this.list.push(client);
            }
            return client;
        },
        async deleteClient(id) {
            await axios.delete(`/api/clients/${id}`);
            this.list = this.list.filter(e => e.id !== id);
        },
        async checkPlafond(clientId, date = null, amount = 0) {
            if (!clientId) return null;
            const params = {};
            if (date) params.date = date;
            if (amount !== undefined && amount !== null) params.amount = amount;
            const res = await axios.get(`/api/clients/${clientId}/check-plafond`, { params });
            return res.data;
        },
        delete(client) {
            this.list = this.list.filter(e => e.id != client.id);
        },
        update(client) {
            const idx = this.list.findIndex(c => c.id === client.id);
            if (idx !== -1) this.list[idx] = { ...this.list[idx], ...client };
        },
        getName(id) {
            return this.list.find(e => e.id == id)?.name || '';
        },
        get_client_name_by_id(id) {
            const client = this.list.find(c => c.id == id);
            return client ? client.name : 'N/A';
        }
    },
    getters: {
        getList: state => state.list,
        getMinList: state => state.list.map(e => ({ id: e.id, name: e.name }))
    }
});
