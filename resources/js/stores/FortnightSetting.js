import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

export const useFortnightSetting = defineStore("fortnight_setting", {
    state: () => ({
        list: [],
        loading: false,
        main: useMain(),
    }),
    actions: {
        async fetch() {
            this.loading = true;
            try {
                const res = await axios.get('/api/fortnight-settings');
                this.list = res.data.settings || [];
            } catch (e) {
                console.error('Error fetching fortnight settings:', e);
            }
            this.loading = false;
        },
        async store(data) {
            try {
                const res = await axios.post('/api/fortnight-settings', data);
                this.list.unshift(res.data.setting);
                this.main.add_snackbar({ show: true, message: i18n.global.t('setting_created'), color: 'success' });
                return res.data.setting;
            } catch (e) {
                console.error('Error creating fortnight setting:', e);
                this.main.add_snackbar({ show: true, message: i18n.global.t('error'), color: 'error' });
                throw e;
            }
        },
        async update(id, data) {
            try {
                const res = await axios.put(`/api/fortnight-settings/${id}`, data);
                const idx = this.list.findIndex(s => s.id === id);
                if (idx !== -1) this.list[idx] = res.data.setting;
                this.main.add_snackbar({ show: true, message: i18n.global.t('settings_updated'), color: 'success' });
                return res.data.setting;
            } catch (e) {
                console.error('Error updating fortnight setting:', e);
                this.main.add_snackbar({ show: true, message: i18n.global.t('error'), color: 'error' });
                throw e;
            }
        },
        async destroy(id) {
            try {
                await axios.delete(`/api/fortnight-settings/${id}`);
                this.list = this.list.filter(s => s.id !== id);
                this.main.add_snackbar({ show: true, message: i18n.global.t('setting_deleted'), color: 'success' });
            } catch (e) {
                console.error('Error deleting fortnight setting:', e);
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
        /**
         * Find the setting whose date range covers a given date string (YYYY-MM-DD).
         * Returns null if no matching setting.
         */
        getSettingForDate: (state) => (dateStr) => {
            if (!dateStr) return null;
            const d = new Date(dateStr + 'T00:00:00');
            return state.list.find(s => {
                const start = new Date(s.date_start + 'T00:00:00');
                if (d < start) return false;
                if (s.date_end) {
                    const end = new Date(s.date_end + 'T00:00:00');
                    if (d > end) return false;
                }
                return true;
            }) || null;
        },
        /**
         * Get Q1 max salary for a given date. Returns 0 if no matching setting.
         */
        q1MaxSalaryForDate: (state) => (dateStr) => {
            const store = useFortnightSetting();
            const setting = store.getSettingForDate(dateStr);
            return setting ? parseFloat(setting.q1_max_salary) || 0 : 0;
        },
        /**
         * Get Q2 max salary for a given date. Returns 0 if no matching setting.
         */
        q2MaxSalaryForDate: (state) => (dateStr) => {
            const store = useFortnightSetting();
            const setting = store.getSettingForDate(dateStr);
            return setting ? parseFloat(setting.q2_max_salary) || 0 : 0;
        },
        /** Backward compat: first setting q1 max */
        q1MaxSalary: (state) => {
            if (!state.list.length) return 0;
            return parseFloat(state.list[0].q1_max_salary) || 0;
        },
        /** Backward compat: first setting q2 max */
        q2MaxSalary: (state) => {
            if (!state.list.length) return 0;
            return parseFloat(state.list[0].q2_max_salary) || 0;
        },
        monthMaxSalary() {
            return this.q1MaxSalary + this.q2MaxSalary;
        },
    },
});
