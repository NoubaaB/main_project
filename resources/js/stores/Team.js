import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

export const useTeam = defineStore("team", {
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
                const res = await axios.get('/api/teams');
                this.list = res.data.teams || [];
            } catch (e) {
                console.error('Error fetching teams:', e);
            }
            this.loading = false;
        },
        async create(data) {
            const res = await axios.post('/api/teams', data);
            this.list.push(res.data.team);
            this.main.add_snackbar({ show: true, message: i18n.global.t('team_created'), color: 'success' });
            return res.data.team;
        },
        async update(id, data) {
            const res = await axios.put(`/api/teams/${id}`, data);
            const idx = this.list.findIndex(t => t.id === id);
            if (idx !== -1) this.list[idx] = res.data.team;
            this.main.add_snackbar({ show: true, message: i18n.global.t('team_updated'), color: 'success' });
            return res.data.team;
        },
        async remove(id) {
            await axios.delete(`/api/teams/${id}`);
            this.list = this.list.filter(t => t.id !== id);
            this.main.add_snackbar({ show: true, message: i18n.global.t('team_deleted'), color: 'success' });
        },
        async bulkRemove(ids) {
            await Promise.all(ids.map(id => axios.delete(`/api/teams/${id}`)));
            this.list = this.list.filter(t => !ids.includes(t.id));
        },
    },
    getters: {
        getList: state => state.list,
    }
});
