import axios from "axios";
import { defineStore } from "pinia";
import { useMain } from "./Main";
import { useWorkforce } from "./Workforce";
import i18n from '@/plugins/i18n';

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
                const res = await axios.get('/api/workforce-movements/absences', { params });
                this.absences = res.data.workforces || [];
                this.summary = res.data.summary || this.summary;
                this.thresholdDays = res.data.threshold_days || 7;
                this.referenceDate = res.data.reference_date;
            } catch (e) {
                console.error('Error fetching workforce absences:', e);
            } finally {
                this.loading = false;
            }
        },

        async fetchSetting() {
            try {
                const res = await axios.get('/api/workforce-absence-settings');
                if (res.data && res.data.threshold_days) {
                    this.thresholdDays = parseInt(res.data.threshold_days, 10);
                }
                return this.thresholdDays;
            } catch (e) {
                console.error('Error fetching absence setting:', e);
                return this.thresholdDays;
            }
        },

        async saveSetting(thresholdDays) {
            try {
                const res = await axios.post('/api/workforce-absence-settings', {
                    threshold_days: thresholdDays,
                });
                this.thresholdDays = parseInt(res.data.threshold_days, 10);
                return res.data;
            } catch (e) {
                console.error('Error saving absence setting:', e);
                throw e;
            }
        },

        async recordMotif(workforceId, payload) {
            try {
                const res = await axios.post(`/api/workforce-movements/${workforceId}/motif`, payload);
                // Refresh local absences and workforce store
                await this.fetchAbsences();
                const workforceStore = useWorkforce();
                await workforceStore.fetch();
                return res.data;
            } catch (e) {
                console.error('Error recording absence motif:', e);
                throw e;
            }
        },

        async leaveCompany(workforceId, payload) {
            try {
                const res = await axios.post(`/api/workforce-movements/${workforceId}/leave-company`, payload);
                await this.fetchAbsences();
                const workforceStore = useWorkforce();
                await workforceStore.fetch();
                return res.data;
            } catch (e) {
                console.error('Error marking workforce departure:', e);
                throw e;
            }
        },

        async returnToWork(workforceId, payload) {
            try {
                const res = await axios.post(`/api/workforce-movements/${workforceId}/return-to-work`, payload);
                await this.fetchAbsences();
                const workforceStore = useWorkforce();
                await workforceStore.fetch();
                return res.data;
            } catch (e) {
                console.error('Error returning workforce to work:', e);
                throw e;
            }
        },

        async fetchHistory(workforceId) {
            this.history.loading = true;
            try {
                const res = await axios.get(`/api/workforce-movements/${workforceId}/history`);
                this.history.workforce = res.data.workforce;
                this.history.movements = res.data.movements || [];
                return res.data;
            } catch (e) {
                console.error('Error fetching movement history:', e);
                throw e;
            } finally {
                this.history.loading = false;
            }
        },

        async fetchStatisticsMovements(params) {
            this.statisticsData.loading = true;
            try {
                const res = await axios.get('/api/workforce-statistics/movements', { params });
                this.statisticsData.summary = res.data.summary || {};
                this.statisticsData.workforce_summaries = res.data.workforce_summaries || [];
                this.statisticsData.timeline = res.data.timeline || [];
                return res.data;
            } catch (e) {
                console.error('Error fetching statistics movements:', e);
                throw e;
            } finally {
                this.statisticsData.loading = false;
            }
        },
    },
});
