import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

export const useFortnightActivity = defineStore("fortnight_activity", {
    state: () => {
        return {
            activities: [],
            summary: {
                first_fortnight: { total: 0, working_days: 0 },
                second_fortnight: { total: 0, working_days: 0 },
                month_total: 0,
            },
            breakdown: { sections: [], corporals: [], workforces: [] },
            loading: false,
            main: useMain(),
        };
    },
    actions: {
        async fetchActivities(workforceId, year, month, fortnightPeriod) {
            this.loading = true;
            try {
                const params = { workforce_id: workforceId, year, month };
                if (fortnightPeriod) params.fortnight_period = fortnightPeriod;
                const res = await axios.get('/api/fortnight-activities', { params });
                this.activities = res.data.activities || [];
            } catch (e) {
                console.error('Error fetching fortnight activities:', e);
                this.activities = [];
            }
            this.loading = false;
        },
        async fetchSummary(params = {}) {
            try {
                const res = await axios.get('/api/fortnight-activities/summary', { params });
                this.summary = res.data;
            } catch (e) {
                console.error('Error fetching summary:', e);
            }
        },
        async create(data, silent = false) {
            const res = await axios.post('/api/fortnight-activities', data);
            this.activities.push(res.data.activity);
            if (!silent) this.main.add_snackbar({ show: true, message: i18n.global.t('activity_created'), color: 'success' });
            return res.data.activity;
        },
        async update(id, data) {
            const res = await axios.put(`/api/fortnight-activities/${id}`, data);
            const idx = this.activities.findIndex(a => a.id === id);
            if (idx !== -1) this.activities[idx] = res.data.activity;
            return res.data.activity;
        },
        async remove(id) {
            await axios.delete(`/api/fortnight-activities/${id}`);
            this.activities = this.activities.filter(a => a.id !== id);
        },
        async bulkDeleteByDate(workforceIds, date) {
            const res = await axios.post('/api/fortnight-activities/bulk-delete', {
                workforce_ids: workforceIds,
                date: date,
            });
            // Remove deleted activities from local state
            this.activities = this.activities.filter(a => {
                const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                return !(workforceIds.includes(a.workforce_id) && aDate === date);
            });
            return res.data.deleted_count;
        },
        async fetchBreakdown(params = {}) {
            try {
                const res = await axios.get('/api/fortnight-activities/breakdown', { params });
                this.breakdown = res.data;
            } catch (e) {
                console.error('Error fetching breakdown:', e);
            }
        },
        async fetchExportData(params = {}) {
            try {
                const res = await axios.get('/api/fortnight-activities/export', { params });
                return res.data.rows || [];
            } catch (e) {
                console.error('Error fetching export data:', e);
                return [];
            }
        },
        async fetchStatistics(params = {}) {
            try {
                const res = await axios.get('/api/fortnight-activities/statistics', { params });
                return res.data;
            } catch (e) {
                console.error('Error fetching statistics:', e);
                return { daily: [], sections: [], corporals: [], grand_total: 0, total_activities: 0 };
            }
        },
    },
    getters: {
        getActivities: state => state.activities,
        /** Group activities by date string for calendar rendering */
        activitiesByDate: state => {
            const map = {};
            state.activities.forEach(a => {
                const dateStr = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                if (!map[dateStr]) map[dateStr] = [];
                map[dateStr].push(a);
            });
            return map;
        },
    }
});
