import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

export const useSection = defineStore("section", {
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
                const res = await axios.get('/api/sections');
                this.list = res.data.sections || [];
            } catch (e) {
                console.error('Error fetching sections:', e);
            }
            this.loading = false;
        },
        async create(data) {
            const res = await axios.post('/api/sections', data);
            this.list.push(res.data.section);
            this.main.add_snackbar({ show: true, message: i18n.global.t('section_created'), color: 'success' });
            return res.data.section;
        },
        async update(id, data) {
            const res = await axios.put(`/api/sections/${id}`, data);
            const idx = this.list.findIndex(s => s.id === id);
            if (idx !== -1) this.list[idx] = res.data.section;
            this.main.add_snackbar({ show: true, message: i18n.global.t('section_updated'), color: 'success' });
            return res.data.section;
        },
        async remove(id) {
            await axios.delete(`/api/sections/${id}`);
            this.list = this.list.filter(s => s.id !== id);
            this.main.add_snackbar({ show: true, message: i18n.global.t('section_deleted'), color: 'success' });
        },
        async bulkRemove(ids) {
            await Promise.all(ids.map(id => axios.delete(`/api/sections/${id}`)));
            this.list = this.list.filter(s => !ids.includes(s.id));
        },
    },
    getters: {
        getList: state => state.list,
    }
});
