import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

export const useStaticCharge = defineStore("static_charge", {
    state: () => ({
        list: [],
        loading: false,
        main: useMain(),
    }),
    actions: {
        async fetch() {
            this.loading = true;
            try {
                const res = await axios.get('/api/static-charges');
                this.list = res.data.static_charges || [];
            } catch (e) {
                console.error('Error fetching static charges:', e);
            }
            this.loading = false;
        },
        async store(data) {
            try {
                const res = await axios.post('/api/static-charges', data);
                this.list.unshift(res.data.static_charge);
                this.main.add_snackbar({ show: true, message: i18n.global.t('static_charge_created'), color: 'success' });
                return res.data.static_charge;
            } catch (e) {
                console.error('Error creating static charge:', e);
                this.main.add_snackbar({ show: true, message: i18n.global.t('error'), color: 'error' });
                throw e;
            }
        },
        async update(id, data) {
            try {
                const res = await axios.put(`/api/static-charges/${id}`, data);
                const idx = this.list.findIndex(s => s.id === id);
                if (idx !== -1) this.list[idx] = res.data.static_charge;
                this.main.add_snackbar({ show: true, message: i18n.global.t('static_charge_updated'), color: 'success' });
                return res.data.static_charge;
            } catch (e) {
                console.error('Error updating static charge:', e);
                this.main.add_snackbar({ show: true, message: i18n.global.t('error'), color: 'error' });
                throw e;
            }
        },
        async destroy(id) {
            try {
                await axios.delete(`/api/static-charges/${id}`);
                this.list = this.list.filter(s => s.id !== id);
                this.main.add_snackbar({ show: true, message: i18n.global.t('static_charge_deleted'), color: 'success' });
            } catch (e) {
                console.error('Error deleting static charge:', e);
                this.main.add_snackbar({ show: true, message: i18n.global.t('error'), color: 'error' });
                throw e;
            }
        },
        /** Handle WebSocket model change events */
        handleWsUpdate(payload) {
            if (!payload) return;
            const { action, id, data } = payload;
            if (action === 'created' && data) {
                const exists = this.list.find(s => s.id === data.id);
                if (!exists) this.list.unshift(data);
            } else if (action === 'updated' && data) {
                const idx = this.list.findIndex(s => s.id === data.id);
                if (idx !== -1) this.list[idx] = data;
                else this.list.unshift(data);
            } else if (action === 'deleted') {
                this.list = this.list.filter(s => s.id !== id);
            } else if (data) {
                // Legacy single-object update
                const idx = this.list.findIndex(s => s.id === data.id);
                if (idx !== -1) this.list[idx] = data;
                else this.list.push(data);
            }
        },
    },
    getters: {
        /** Active static charges (is_active === true) */
        activeCharges: (state) => state.list.filter(c => c.is_active),

        /**
         * Find all static charges active for a given date string (YYYY-MM-DD).
         */
        getChargesForDate: (state) => (dateStr) => {
            if (!dateStr) return [];
            const d = new Date(dateStr + 'T00:00:00');
            return state.list.filter(c => {
                if (!c.is_active) return false;
                if (c.date_range_type === 'all_time') return true;
                if (c.date_range_type === 'date_range') {
                    const start = new Date(c.date_start + 'T00:00:00');
                    const end = new Date(c.date_end + 'T00:00:00');
                    return d >= start && d <= end;
                }
                if (c.date_range_type === 'ongoing') {
                    const start = new Date(c.date_start + 'T00:00:00');
                    return d >= start;
                }
                return false;
            });
        },
    },
});
