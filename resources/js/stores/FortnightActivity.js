import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

export const useFortnightActivity = defineStore("fortnight_activity", {
    state: () => {
        return {
            activities: [],
            dailyTotals: [],
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
        async fetchActivities(workforceIds, year, month, fortnightPeriod) {
            this.loading = true;
            try {
                const params = { year, month };
                // Support both array and single ID for backward compatibility
                const ids = Array.isArray(workforceIds) ? workforceIds : [workforceIds];
                if (ids.length > 0) params.workforce_ids = ids;
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
            const payload = { date };
            if (workforceIds && workforceIds.length > 0) {
                payload.workforce_ids = workforceIds;
            }
            const res = await axios.post('/api/fortnight-activities/bulk-delete', payload);
            // Remove deleted activities from local state
            if (workforceIds && workforceIds.length > 0) {
                this.activities = this.activities.filter(a => {
                    const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                    return !(workforceIds.includes(a.workforce_id) && aDate === date);
                });
            } else {
                // No workforce filter: remove all activities for this date
                this.activities = this.activities.filter(a => {
                    const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                    return aDate !== date;
                });
                // Also remove from dailyTotals
                this.dailyTotals = this.dailyTotals.filter(dt => dt.date !== date);
            }
            return res.data.deleted_count;
        },
        /** Bulk delete activities across multiple dates */
        async bulkDeleteByDates(workforceIds, dates) {
            const payload = { dates };
            if (workforceIds && workforceIds.length > 0) {
                payload.workforce_ids = workforceIds;
            }
            const res = await axios.post('/api/fortnight-activities/bulk-delete', payload);
            // Remove from local state
            const dateSet = new Set(dates);
            if (workforceIds && workforceIds.length > 0) {
                this.activities = this.activities.filter(a => {
                    const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                    return !(workforceIds.includes(a.workforce_id) && dateSet.has(aDate));
                });
            } else {
                this.activities = this.activities.filter(a => {
                    const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                    return !dateSet.has(aDate);
                });
                this.dailyTotals = this.dailyTotals.filter(dt => !dateSet.has(dt.date));
            }
            return res.data.deleted_count;
        },
        async fetchDailyTotals(year, month) {
            this.loading = true;
            try {
                const res = await axios.get('/api/fortnight-activities/daily-totals', { params: { year, month } });
                this.dailyTotals = res.data.daily_totals || [];
            } catch (e) {
                console.error('Error fetching daily totals:', e);
                this.dailyTotals = [];
            }
            this.loading = false;
        },
        /** Fetch all activities for a specific date (used in overview detail dialog) */
        async fetchActivitiesByDate(date) {
            try {
                const res = await axios.get('/api/fortnight-activities/activities-by-date', { params: { date } });
                return res.data.activities || [];
            } catch (e) {
                console.error('Error fetching activities by date:', e);
                return [];
            }
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
        /** Map daily totals by date for the "all workforces" calendar view */
        dailyTotalsByDate: state => {
            const map = {};
            state.dailyTotals.forEach(dt => {
                map[dt.date] = dt;
            });
            return map;
        },
    }
});
