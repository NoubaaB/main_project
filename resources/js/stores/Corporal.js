import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

export const useCorporal = defineStore("corporal", {
    state: () => {
        return {
            list: [],
            loading: false,
            main: useMain(),
        };
    },
    actions: {
        async fetch() {
            this.loading = true;
            try {
                const res = await axios.get('/api/corporals');
                this.list = res.data.corporals || [];
            } catch (e) {
                console.error('Error fetching corporals:', e);
            }
            this.loading = false;
        },
        async create(data) {
            const res = await axios.post('/api/corporals', data);
            this.list.push(res.data.corporal);
            this.main.add_snackbar({ show: true, message: i18n.global.t('corporal_created'), color: 'success' });
            return res.data.corporal;
        },
        async update(id, data) {
            const res = await axios.put(`/api/corporals/${id}`, data);
            const idx = this.list.findIndex(c => c.id === id);
            if (idx !== -1) this.list[idx] = res.data.corporal;
            this.main.add_snackbar({ show: true, message: i18n.global.t('corporal_updated'), color: 'success' });
            return res.data.corporal;
        },
        async remove(id) {
            await axios.delete(`/api/corporals/${id}`);
            this.list = this.list.filter(c => c.id !== id);
            this.main.add_snackbar({ show: true, message: i18n.global.t('corporal_deleted'), color: 'success' });
        },
        async bulkRemove(ids) {
            await Promise.all(ids.map(id => axios.delete(`/api/corporals/${id}`)));
            this.list = this.list.filter(c => !ids.includes(c.id));
        },
    },
    getters: {
        getList: state => state.list,
    }
});
