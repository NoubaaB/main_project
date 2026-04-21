import { defineStore } from 'pinia'
import axios from 'axios'

export const useProductionCategory = defineStore('production_category', {
    state: () => ({
        list: [],
        loading: false,
    }),
    actions: {
        async fetchAll() {
            this.loading = true;
            try {
                const res = await axios.get('/api/production-categories');
                this.list = res.data.production_categories || [];
            } catch (e) {
                console.error('Error fetching production categories:', e);
            }
            this.loading = false;
        },
        async add(data) {
            const res = await axios.post('/api/production-categories', data);
            this.list.push(res.data.production_category);
            return res.data.production_category;
        },
        async update(id, data) {
            const res = await axios.put(`/api/production-categories/${id}`, data);
            const idx = this.list.findIndex(c => c.id === id);
            if (idx !== -1) this.list[idx] = res.data.production_category;
            return res.data.production_category;
        },
        async remove(id) {
            await axios.delete(`/api/production-categories/${id}`);
            this.list = this.list.filter(c => c.id !== id);
        }
    }
})
