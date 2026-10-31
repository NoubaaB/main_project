import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';
import {
    gqlGetFortnightActivities,
    gqlGetFortnightActivitiesByDate,
    gqlGetFortnightDailyTotals,
    gqlGetFortnightSummary,
    gqlGetFortnightBreakdown,
    gqlGetWorkforceStatistics,
    gqlCreateFortnightActivity,
    gqlUpdateFortnightActivity,
    gqlDeleteFortnightActivity,
    gqlBulkDeleteFortnightActivities,
} from '@/services/appGraphql';

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
                const ids = Array.isArray(workforceIds) ? workforceIds : (workforceIds ? [workforceIds] : null);
                const data = await gqlGetFortnightActivities({
                    year: year ? parseInt(year, 10) : null,
                    month: month ? parseInt(month, 10) : null,
                    workforce_ids: ids && ids.length ? ids.map(id => parseInt(id, 10)) : null,
                    fortnight_period: fortnightPeriod ? parseInt(fortnightPeriod, 10) : null,
                });
                if (data && Array.isArray(data)) {
                    this.activities = data;
                } else {
                    throw new Error('GraphQL returned empty activities');
                }
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchActivities:', e);
                try {
                    const params = { year, month };
                    const ids = Array.isArray(workforceIds) ? workforceIds : [workforceIds];
                    if (ids.length > 0) params.workforce_ids = ids;
                    if (fortnightPeriod) params.fortnight_period = fortnightPeriod;
                    const res = await axios.get('/api/fortnight-activities', { params });
                    this.activities = res.data.activities || [];
                } catch (err) {
                    console.error('Error fetching fortnight activities:', err);
                    this.activities = [];
                }
            }
            this.loading = false;
        },
        async fetchSummary(params = {}) {
            try {
                const { year, month, workforce_ids, workforce_id } = params;
                const ids = Array.isArray(workforce_ids) ? workforce_ids.map(id => parseInt(id, 10)) : null;
                const data = await gqlGetFortnightSummary({
                    year: parseInt(year, 10),
                    month: parseInt(month, 10),
                    workforce_ids: ids && ids.length ? ids : null,
                    workforce_id: workforce_id ? parseInt(workforce_id, 10) : null,
                });
                if (data) {
                    this.summary = data;
                    return;
                }
                throw new Error('GraphQL returned empty summary');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchSummary:', e);
                try {
                    const res = await axios.get('/api/fortnight-activities/summary', { params });
                    this.summary = res.data;
                } catch (err) {
                    console.error('Error fetching summary:', err);
                }
            }
        },
        async create(data, silent = false) {
            try {
                const act = await gqlCreateFortnightActivity({
                    workforce_id: parseInt(data.workforce_id, 10),
                    date: data.date,
                    price: parseFloat(data.price),
                    section_id: data.section_id ? parseInt(data.section_id, 10) : null,
                    corporal_id: data.corporal_id ? parseInt(data.corporal_id, 10) : null,
                    is_fixed_price: Boolean(data.is_fixed_price),
                    fortnight_period: data.fortnight_period ? parseInt(data.fortnight_period, 10) : null,
                });
                if (act) {
                    this.activities.push(act);
                    if (!silent) this.main.add_snackbar({ show: true, message: i18n.global.t('activity_created'), color: 'success' });
                    return act;
                }
                throw new Error('GraphQL creation failed');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] create:', e);
                const res = await axios.post('/api/fortnight-activities', data);
                this.activities.push(res.data.activity);
                if (!silent) this.main.add_snackbar({ show: true, message: i18n.global.t('activity_created'), color: 'success' });
                return res.data.activity;
            }
        },
        async update(id, data) {
            try {
                const act = await gqlUpdateFortnightActivity({
                    id: String(id),
                    workforce_id: data.workforce_id ? parseInt(data.workforce_id, 10) : null,
                    date: data.date,
                    price: data.price !== undefined ? parseFloat(data.price) : null,
                    section_id: data.section_id ? parseInt(data.section_id, 10) : null,
                    corporal_id: data.corporal_id ? parseInt(data.corporal_id, 10) : null,
                    is_fixed_price: data.is_fixed_price !== undefined ? Boolean(data.is_fixed_price) : null,
                    fortnight_period: data.fortnight_period ? parseInt(data.fortnight_period, 10) : null,
                });
                if (act) {
                    const idx = this.activities.findIndex(a => a.id === id);
                    if (idx !== -1) this.activities[idx] = act;
                    return act;
                }
                throw new Error('GraphQL update failed');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] update:', e);
                const res = await axios.put(`/api/fortnight-activities/${id}`, data);
                const idx = this.activities.findIndex(a => a.id === id);
                if (idx !== -1) this.activities[idx] = res.data.activity;
                return res.data.activity;
            }
        },
        async remove(id) {
            try {
                const success = await gqlDeleteFortnightActivity(String(id));
                if (success) {
                    this.activities = this.activities.filter(a => a.id !== id);
                    return;
                }
                throw new Error('GraphQL delete returned false');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] remove:', e);
                await axios.delete(`/api/fortnight-activities/${id}`);
                this.activities = this.activities.filter(a => a.id !== id);
            }
        },
        async bulkDeleteByDate(workforceIds, date) {
            try {
                const ids = workforceIds && workforceIds.length ? workforceIds.map(id => parseInt(id, 10)) : null;
                const result = await gqlBulkDeleteFortnightActivities({ date, workforce_ids: ids });
                const deletedCount = result?.deleted_count || 0;

                if (ids && ids.length > 0) {
                    this.activities = this.activities.filter(a => {
                        const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                        return !(ids.includes(a.workforce_id) && aDate === date);
                    });
                } else {
                    this.activities = this.activities.filter(a => {
                        const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                        return aDate !== date;
                    });
                    this.dailyTotals = this.dailyTotals.filter(dt => dt.date !== date);
                }
                return deletedCount;
            } catch (e) {
                console.warn('[GraphQL fallback to REST] bulkDeleteByDate:', e);
                const payload = { date };
                if (workforceIds && workforceIds.length > 0) payload.workforce_ids = workforceIds;
                const res = await axios.post('/api/fortnight-activities/bulk-delete', payload);
                if (workforceIds && workforceIds.length > 0) {
                    this.activities = this.activities.filter(a => {
                        const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                        return !(workforceIds.includes(a.workforce_id) && aDate === date);
                    });
                } else {
                    this.activities = this.activities.filter(a => {
                        const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                        return aDate !== date;
                    });
                    this.dailyTotals = this.dailyTotals.filter(dt => dt.date !== date);
                }
                return res.data.deleted_count;
            }
        },
        /** Bulk delete activities across multiple dates */
        async bulkDeleteByDates(workforceIds, dates) {
            try {
                const ids = workforceIds && workforceIds.length ? workforceIds.map(id => parseInt(id, 10)) : null;
                const result = await gqlBulkDeleteFortnightActivities({ dates, workforce_ids: ids });
                const deletedCount = result?.deleted_count || 0;

                const dateSet = new Set(dates);
                if (ids && ids.length > 0) {
                    this.activities = this.activities.filter(a => {
                        const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                        return !(ids.includes(a.workforce_id) && dateSet.has(aDate));
                    });
                } else {
                    this.activities = this.activities.filter(a => {
                        const aDate = typeof a.date === 'string' ? a.date.split('T')[0] : a.date;
                        return !dateSet.has(aDate);
                    });
                    this.dailyTotals = this.dailyTotals.filter(dt => !dateSet.has(dt.date));
                }
                return deletedCount;
            } catch (e) {
                console.warn('[GraphQL fallback to REST] bulkDeleteByDates:', e);
                const payload = { dates };
                if (workforceIds && workforceIds.length > 0) payload.workforce_ids = workforceIds;
                const res = await axios.post('/api/fortnight-activities/bulk-delete', payload);
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
            }
        },
        async fetchDailyTotals(year, month) {
            this.loading = true;
            try {
                const data = await gqlGetFortnightDailyTotals({
                    year: parseInt(year, 10),
                    month: parseInt(month, 10),
                });
                if (data && Array.isArray(data)) {
                    this.dailyTotals = data;
                } else {
                    throw new Error('GraphQL returned empty daily totals');
                }
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchDailyTotals:', e);
                try {
                    const res = await axios.get('/api/fortnight-activities/daily-totals', { params: { year, month } });
                    this.dailyTotals = res.data.daily_totals || [];
                } catch (err) {
                    console.error('Error fetching daily totals:', err);
                    this.dailyTotals = [];
                }
            }
            this.loading = false;
        },
        /** Fetch all activities for a specific date (used in overview detail dialog) */
        async fetchActivitiesByDate(date) {
            try {
                const data = await gqlGetFortnightActivitiesByDate(date);
                if (data && Array.isArray(data)) {
                    return data;
                }
                throw new Error('GraphQL returned empty activities for date');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchActivitiesByDate:', e);
                try {
                    const res = await axios.get('/api/fortnight-activities/activities-by-date', { params: { date } });
                    return res.data.activities || [];
                } catch (err) {
                    console.error('Error fetching activities by date:', err);
                    return [];
                }
            }
        },
        async fetchBreakdown(params = {}) {
            try {
                const { year, month, workforce_ids } = params;
                const ids = Array.isArray(workforce_ids) ? workforce_ids.map(id => parseInt(id, 10)) : null;
                const data = await gqlGetFortnightBreakdown({
                    year: parseInt(year, 10),
                    month: parseInt(month, 10),
                    workforce_ids: ids && ids.length ? ids : null,
                });
                if (data) {
                    this.breakdown = data;
                    return;
                }
                throw new Error('GraphQL returned empty breakdown');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchBreakdown:', e);
                try {
                    const res = await axios.get('/api/fortnight-activities/breakdown', { params });
                    this.breakdown = res.data;
                } catch (err) {
                    console.error('Error fetching breakdown:', err);
                }
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
                const { date_start, date_end, workforce_ids, section_ids, corporal_ids } = params;
                const data = await gqlGetWorkforceStatistics({
                    date_start,
                    date_end,
                    workforce_ids: workforce_ids && workforce_ids.length ? workforce_ids.map(id => parseInt(id, 10)) : null,
                    section_ids: section_ids && section_ids.length ? section_ids.map(id => parseInt(id, 10)) : null,
                    corporal_ids: corporal_ids && corporal_ids.length ? corporal_ids.map(id => parseInt(id, 10)) : null,
                });
                if (data) {
                    return data;
                }
                throw new Error('GraphQL returned empty statistics');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchStatistics:', e);
                try {
                    const res = await axios.get('/api/fortnight-activities/statistics', { params });
                    return res.data;
                } catch (err) {
                    console.error('Error fetching statistics:', err);
                    return { daily: [], sections: [], corporals: [], grand_total: 0, total_activities: 0 };
                }
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
