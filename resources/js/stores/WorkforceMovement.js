import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import { useWorkforce } from "./Workforce";
import i18n from '@/plugins/i18n';
import {
    gqlGetWorkforceAbsences,
    gqlGetWorkforceAbsenceSettings,
    gqlSaveWorkforceAbsenceSetting,
    gqlRecordWorkforceMotif,
    gqlWorkforceLeaveCompany,
    gqlWorkforceReturnToWork,
    gqlGetWorkforceMovementHistory,
    gqlGetWorkforceMovementStatistics,
} from '@/services/appGraphql';

export const useWorkforceMovement = defineStore("workforceMovement", {
    state: () => {
        return {
            absences: [],
            summary: {
                total_workforces: 0,
                active_count: 0,
                absent_count: 0,
                left_company_count: 0,
                total_absence_incidents: 0,
                total_absence_days: 0,
            },
            thresholdDays: 7,
            referenceDate: null,
            loading: false,
            history: {
                workforce: null,
                movements: [],
                loading: false,
            },
            statisticsData: {
                summary: {},
                workforce_summaries: [],
                timeline: [],
                loading: false,
            },
            main: useMain(),
        };
    },
    actions: {
        async fetchAbsences(params = {}) {
            this.loading = true;
            try {
                const data = await gqlGetWorkforceAbsences({
                    threshold: params.threshold ? parseInt(params.threshold, 10) : null,
                    reference_date: params.reference_date || null,
                    status: params.status || null,
                    search: params.search || null,
                    team_id: params.team_id ? parseInt(params.team_id, 10) : null,
                    corporal_id: params.corporal_id ? parseInt(params.corporal_id, 10) : null,
                    section_id: params.section_id ? parseInt(params.section_id, 10) : null,
                });
                if (data) {
                    this.absences = data.workforces || [];
                    this.summary = data.summary || this.summary;
                    this.thresholdDays = data.threshold_days || 7;
                    this.referenceDate = data.reference_date;
                    return;
                }
                throw new Error('GraphQL returned empty absences');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchAbsences:', e);
                try {
                    const res = await axios.get('/api/workforce-movements/absences', { params });
                    this.absences = res.data.workforces || [];
                    this.summary = res.data.summary || this.summary;
                    this.thresholdDays = res.data.threshold_days || 7;
                    this.referenceDate = res.data.reference_date;
                } catch (err) {
                    console.error('Error fetching workforce absences:', err);
                }
            } finally {
                this.loading = false;
            }
        },

        async fetchSetting() {
            try {
                const data = await gqlGetWorkforceAbsenceSettings();
                if (data && data.threshold_days) {
                    this.thresholdDays = parseInt(data.threshold_days, 10);
                    return this.thresholdDays;
                }
                throw new Error('GraphQL returned empty settings');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchSetting:', e);
                try {
                    const res = await axios.get('/api/workforce-absence-settings');
                    if (res.data && res.data.threshold_days) {
                        this.thresholdDays = parseInt(res.data.threshold_days, 10);
                    }
                    return this.thresholdDays;
                } catch (err) {
                    console.error('Error fetching absence setting:', err);
                    return this.thresholdDays;
                }
            }
        },

        async saveSetting(thresholdDays) {
            try {
                const data = await gqlSaveWorkforceAbsenceSetting(parseInt(thresholdDays, 10));
                if (data && data.threshold_days) {
                    this.thresholdDays = parseInt(data.threshold_days, 10);
                    return data;
                }
                throw new Error('GraphQL saveSetting failed');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] saveSetting:', e);
                try {
                    const res = await axios.post('/api/workforce-absence-settings', {
                        threshold_days: thresholdDays,
                    });
                    this.thresholdDays = parseInt(res.data.threshold_days, 10);
                    return res.data;
                } catch (err) {
                    console.error('Error saving absence setting:', err);
                    throw err;
                }
            }
        },

        async recordMotif(workforceId, payload) {
            try {
                const data = await gqlRecordWorkforceMotif({
                    workforce_id: String(workforceId),
                    motif_date: payload.motif_date || payload.start_date || null,
                    reason: payload.reason,
                    notes: payload.notes || null,
                    start_date: payload.start_date || null,
                    end_date: payload.end_date || null,
                    days_count: payload.days_count ? parseInt(payload.days_count, 10) : null,
                    is_justified: Boolean(payload.is_justified),
                });
                await this.fetchAbsences();
                const workforceStore = useWorkforce();
                await workforceStore.fetch();
                return data;
            } catch (e) {
                console.warn('[GraphQL fallback to REST] recordMotif:', e);
                try {
                    const res = await axios.post(`/api/workforce-movements/${workforceId}/motif`, payload);
                    await this.fetchAbsences();
                    const workforceStore = useWorkforce();
                    await workforceStore.fetch();
                    return res.data;
                } catch (err) {
                    console.error('Error recording absence motif:', err);
                    throw err;
                }
            }
        },

        async leaveCompany(workforceId, payload) {
            try {
                const data = await gqlWorkforceLeaveCompany({
                    workforce_id: String(workforceId),
                    departure_date: payload.departure_date,
                    departure_reason: payload.departure_reason || null,
                    notes: payload.notes || null,
                });
                await this.fetchAbsences();
                const workforceStore = useWorkforce();
                await workforceStore.fetch();
                return data;
            } catch (e) {
                console.warn('[GraphQL fallback to REST] leaveCompany:', e);
                try {
                    const res = await axios.post(`/api/workforce-movements/${workforceId}/leave-company`, payload);
                    await this.fetchAbsences();
                    const workforceStore = useWorkforce();
                    await workforceStore.fetch();
                    return res.data;
                } catch (err) {
                    console.error('Error marking workforce departure:', err);
                    throw err;
                }
            }
        },

        async returnToWork(workforceId, payload) {
            try {
                const data = await gqlWorkforceReturnToWork({
                    workforce_id: String(workforceId),
                    return_date: payload.return_date,
                    restore_teams: payload.restore_teams !== undefined ? Boolean(payload.restore_teams) : true,
                    notes: payload.notes || null,
                });
                await this.fetchAbsences();
                const workforceStore = useWorkforce();
                await workforceStore.fetch();
                return data;
            } catch (e) {
                console.warn('[GraphQL fallback to REST] returnToWork:', e);
                try {
                    const res = await axios.post(`/api/workforce-movements/${workforceId}/return-to-work`, payload);
                    await this.fetchAbsences();
                    const workforceStore = useWorkforce();
                    await workforceStore.fetch();
                    return res.data;
                } catch (err) {
                    console.error('Error returning workforce to work:', err);
                    throw err;
                }
            }
        },

        async fetchHistory(workforceId) {
            this.history.loading = true;
            try {
                const data = await gqlGetWorkforceMovementHistory(String(workforceId));
                if (data && data.workforce) {
                    this.history.workforce = data.workforce;
                    this.history.movements = data.movements || [];
                    return data;
                }
                throw new Error('GraphQL returned empty movement history');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchHistory:', e);
                try {
                    const res = await axios.get(`/api/workforce-movements/${workforceId}/history`);
                    this.history.workforce = res.data.workforce;
                    this.history.movements = res.data.movements || [];
                    return res.data;
                } catch (err) {
                    console.error('Error fetching movement history:', err);
                    throw err;
                }
            } finally {
                this.history.loading = false;
            }
        },

        async fetchStatisticsMovements(params) {
            this.statisticsData.loading = true;
            try {
                const wfIds = params.workforce_ids
                    ? (Array.isArray(params.workforce_ids)
                        ? params.workforce_ids.map(id => parseInt(id, 10))
                        : String(params.workforce_ids).split(',').map(id => parseInt(id.trim(), 10)))
                    : null;
                const data = await gqlGetWorkforceMovementStatistics({
                    start_date: params.start_date || null,
                    end_date: params.end_date || null,
                    granularity: params.granularity || 'daily',
                    workforce_ids: wfIds,
                });
                if (data) {
                    this.statisticsData.summary = data.summary || {};
                    this.statisticsData.workforce_summaries = data.workforce_summaries || [];
                    this.statisticsData.timeline = data.timeline || [];
                    return data;
                }
                throw new Error('GraphQL returned empty statistics movements');
            } catch (e) {
                console.warn('[GraphQL fallback to REST] fetchStatisticsMovements:', e);
                try {
                    const res = await axios.get('/api/workforce-statistics/movements', { params });
                    this.statisticsData.summary = res.data.summary || {};
                    this.statisticsData.workforce_summaries = res.data.workforce_summaries || [];
                    this.statisticsData.timeline = res.data.timeline || [];
                    return res.data;
                } catch (err) {
                    console.error('Error fetching statistics movements:', err);
                    throw err;
                }
            } finally {
                this.statisticsData.loading = false;
            }
        },
    },
});
