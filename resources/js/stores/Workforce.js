import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

export const useWorkforce = defineStore("workforce", {
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
                const res = await axios.get('/api/workforces');
                this.list = res.data.workforces || [];
            } catch (e) {
                console.error('Error fetching workforces:', e);
            }
            this.loading = false;
        },
        async create(data) {
            const res = await axios.post('/api/workforces', data);
            this.list.push(res.data.workforce);
            this.main.add_snackbar({ show: true, message: i18n.global.t('workforce_created'), color: 'success' });
            return res.data.workforce;
        },
        async update(id, data) {
            const res = await axios.put(`/api/workforces/${id}`, data);
            const idx = this.list.findIndex(w => w.id === id);
            if (idx !== -1) this.list[idx] = res.data.workforce;
            this.main.add_snackbar({ show: true, message: i18n.global.t('workforce_updated'), color: 'success' });
            return res.data.workforce;
        },
        async remove(id) {
            await axios.delete(`/api/workforces/${id}`);
            this.list = this.list.filter(w => w.id !== id);
            this.main.add_snackbar({ show: true, message: i18n.global.t('workforce_deleted'), color: 'success' });
        },
        async bulkRemove(ids) {
            await Promise.all(ids.map(id => axios.delete(`/api/workforces/${id}`)));
            this.list = this.list.filter(w => !ids.includes(w.id));
        },
    },
    getters: {
        getList: state => state.list,
    }
});
