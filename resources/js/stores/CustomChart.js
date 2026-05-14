import { defineStore } from 'pinia';
import axios from 'axios';

export const useCustomChart = defineStore('CustomChart', {
    state: () => ({
        list: [],
        loading: false,
        chartDataCache: {}, // { chartId: { categories, series, fetchedAt } }
    }),
    getters: {
        activeCharts: (state) => state.list.filter(c => c.is_active),
        sortedCharts: (state) => [...state.list].sort((a, b) => a.display_order - b.display_order),
    },
    actions: {
        async fetch() {
            this.loading = true;
            try {
                const { data } = await axios.get('/api/custom-charts');
                this.list = data.custom_charts || [];
            } catch (e) {
                console.warn('[CustomChart] fetch failed:', e);
            }
            this.loading = false;
        },

        async fetchChartData(chartId, forceRefresh = false, dateParams = null) {
            // Build a cache key that includes date params
            const cacheKey = dateParams ? `${chartId}_${dateParams.start}_${dateParams.end}` : chartId;
            const cached = this.chartDataCache[cacheKey];
            if (!forceRefresh && cached && (Date.now() - cached.fetchedAt < 60000)) {
                return cached;
            }
            try {
                const params = {};
                if (dateParams?.start) params.start = dateParams.start;
                if (dateParams?.end) params.end = dateParams.end;
                const { data } = await axios.get(`/api/custom-charts/${chartId}/data`, { params });
                const result = { categories: data.categories, series: data.series, fetchedAt: Date.now() };
                this.chartDataCache[cacheKey] = result;
                return result;
            } catch (e) {
                console.warn('[CustomChart] fetchChartData failed:', e);
                return { categories: [], series: [] };
            }
        },

        async create(payload) {
            const { data } = await axios.post('/api/custom-charts', payload);
            if (data.custom_chart) {
                this.list.push(data.custom_chart);
            }
            return data.custom_chart;
        },

        async update(id, payload) {
            const { data } = await axios.put(`/api/custom-charts/${id}`, payload);
            if (data.custom_chart) {
                const idx = this.list.findIndex(c => c.id === id);
                if (idx !== -1) this.list[idx] = data.custom_chart;
                // Invalidate cache
                delete this.chartDataCache[id];
            }
            return data.custom_chart;
        },

        async destroy(id) {
            await axios.delete(`/api/custom-charts/${id}`);
            this.list = this.list.filter(c => c.id !== id);
            delete this.chartDataCache[id];
        },

        async reorder(items) {
            await axios.post('/api/custom-charts/reorder', { items });
            items.forEach(({ id, display_order }) => {
                const c = this.list.find(ch => ch.id === id);
                if (c) c.display_order = display_order;
            });
        },

        /** WebSocket handler — same pattern as FortnightSetting */
        handleWsUpdate({ action, id, data }) {
            if (action === 'created' && data) {
                if (!this.list.find(c => c.id === id)) this.list.push(data);
            } else if (action === 'updated' && data) {
                const idx = this.list.findIndex(c => c.id === id);
                if (idx !== -1) this.list[idx] = data;
                this.invalidateCacheForChart(id);
            } else if (action === 'deleted') {
                this.list = this.list.filter(c => c.id !== id);
                this.invalidateCacheForChart(id);
            }
        },

        /** Clear all cache entries for a given chart (including date-parameterized keys) */
        invalidateCacheForChart(chartId) {
            const prefix = String(chartId);
            for (const key of Object.keys(this.chartDataCache)) {
                if (key === prefix || key.startsWith(prefix + '_')) {
                    delete this.chartDataCache[key];
                }
            }
        },

        invalidateAllCache() {
            this.chartDataCache = {};
        },
    },
});
