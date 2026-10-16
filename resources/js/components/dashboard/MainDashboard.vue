<template>
  <div class="main-dashboard">
    <!-- Hero -->
    <div class="md-hero">
      <div class="md-hero-inner">
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center ga-4">
            <div class="md-hero-icon"><v-icon size="28" color="white">mdi-chart-box-outline</v-icon></div>
            <div>
              <div class="md-hero-title">{{ $t('main_dashboard') }}</div>
              <div class="text-white text-caption" style="opacity:.7">{{ $t('custom_charts_subtitle') }}</div>
            </div>
          </div>
          <v-btn v-if="canManage" variant="outlined" color="white" size="small" rounded prepend-icon="mdi-cog-outline" :to="{ name: 'chart_management' }">{{ $t('manage_charts') }}</v-btn>
        </div>
      </div>
    </div>

    <!-- Dynamic Date Range Picker -->
    <v-card v-if="hasDynamicCharts" class="md-date-card rounded-xl mb-4" elevation="1">
      <v-card-text class="pa-4">
        <div class="d-flex align-center ga-3 mb-3">
          <v-icon color="#00BCD4">mdi-calendar-range</v-icon>
          <span class="text-subtitle-2 font-weight-bold">{{ $t('dynamic_range') }}</span>
          <v-spacer />
          <v-chip color="#00BCD4" variant="tonal" size="small" label>
            <v-icon start size="14">mdi-calendar-start</v-icon>
            {{ formatDateDisplay(dateRange.start) }}
          </v-chip>
          <v-icon size="16" color="grey">mdi-arrow-right</v-icon>
          <v-chip color="#00BCD4" variant="tonal" size="small" label>
            <v-icon start size="14">mdi-calendar-end</v-icon>
            {{ formatDateDisplay(dateRange.end) }}
          </v-chip>
        </div>
        <DatePicker
          :is-dark="main.is_dark"
          v-model.range="dateRange"
          mode="date"
          :locale="main.lang.short"
          :max-date="new Date()"
          expanded
          color="teal"
          :columns="2"
        />
      </v-card-text>
    </v-card>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center py-16">
      <v-progress-circular indeterminate color="#00BCD4" size="48" />
    </div>

    <!-- No charts -->
    <div v-else-if="!activeCharts.length" class="text-center py-16">
      <v-icon size="80" color="#E0E0E0" class="mb-4">mdi-chart-box-plus-outline</v-icon>
      <div class="text-h6 text-medium-emphasis mb-2">{{ $t('no_charts_configured') }}</div>
      <v-btn v-if="canManage" variant="tonal" color="#00BCD4" rounded :to="{ name: 'chart_management' }">{{ $t('create_chart') }}</v-btn>
    </div>

    <!-- Charts grid -->
    <v-row v-else class="mt-2">
      <v-col
        v-for="chart in activeCharts"
        :key="chart.id"
        :cols="12"
        :md="chart.grid_cols || 6"
      >
        <!-- Statistic Card -->
        <v-card v-if="chart.display_type === 'statistic'" class="stat-card rounded-xl" elevation="2">
          <v-card-text class="pa-6">
            <div class="d-flex align-center justify-space-between mb-3">
              <span class="text-subtitle-2 font-weight-medium text-medium-emphasis">{{ chart.title }}</span>
              <div class="d-flex align-center ga-1">
                <v-chip v-if="chart.date_range_type === 'dynamic'" size="x-small" variant="tonal" color="teal">{{ $t('dynamic_range') }}</v-chip>
                <v-btn v-if="canManage" icon="mdi-pencil" size="x-small" variant="text" color="#00BCD4" @click="editChart(chart)" />
              </div>
            </div>
            <div v-if="chartDataMap[chart.id]" class="stat-value">{{ formatStatValue(chartDataMap[chart.id]) }}</div>
            <v-skeleton-loader v-else type="text" width="120" />
          </v-card-text>
        </v-card>

        <!-- Chart Card -->
        <v-card v-else class="chart-card rounded-xl" elevation="2">
          <v-card-text class="pa-5">
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="text-subtitle-1 font-weight-bold">{{ chart.title }}</span>
              <div class="d-flex align-center ga-1">
                <v-chip v-if="chart.date_range_type === 'dynamic'" size="x-small" variant="tonal" color="teal">{{ $t('dynamic_range') }}</v-chip>
                <v-chip size="x-small" variant="tonal" color="#00BCD4">{{ chart.chart_type }}</v-chip>
                <v-btn icon="mdi-refresh" size="x-small" variant="text" @click="refreshChart(chart.id)" />
                <v-btn v-if="canManage" icon="mdi-pencil" size="x-small" variant="text" color="#00BCD4" @click="editChart(chart)" />
              </div>
            </div>
            <div class="text-caption text-medium-emphasis mb-3">{{ getDateLabel(chart) }}</div>

            <div v-if="!chartDataMap[chart.id]" class="d-flex justify-center py-8">
              <v-progress-circular indeterminate color="#00BCD4" size="32" />
            </div>
            <ChartPreview
              v-else
              :chart-config="chart"
              :categories="chartDataMap[chart.id].categories"
              :series="chartDataMap[chart.id].series"
              :height="300"
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { useCustomChart } from '@/stores/CustomChart';
import { useAuth } from '@/stores/Auth';
import { useMain } from '@/stores/Main';
import ChartPreview from '@/components/parameters/ChartManagement/ChartPreview.vue';

export default {
  name: 'MainDashboard',
  components: { ChartPreview },
  data() {
    return {
      loading: false,
      chartDataMap: {},
      dateRange: {
        start: new Date(new Date().getFullYear(), 0, 1),
        end: new Date(),
      },
    };
  },
  computed: {
    store() { return useCustomChart(); },
    auth() { return useAuth(); },
    main() { return useMain(); },
    activeCharts() { return this.store.sortedCharts.filter(c => c.is_active); },
    canManage() { return this.auth.isSuperAdmin || this.auth.hasPermission('dashboard.manage'); },
    hasDynamicCharts() { return this.activeCharts.some(c => c.date_range_type === 'dynamic'); },
  },
  async mounted() {
    await this.loadAllChartData();
  },
  methods: {
    formatDateForApi(d) {
      if (!d) return null;
      const dt = d instanceof Date ? d : new Date(d);
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const day = String(dt.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    },
    formatDateDisplay(d) {
      if (!d) return '-';
      return this.main.formateDate(d);
    },
    getDynamicDateParams() {
      return {
        start: this.formatDateForApi(this.dateRange.start),
        end: this.formatDateForApi(this.dateRange.end),
      };
    },
    async loadAllChartData(forceRefresh = false) {
      this.loading = true;
      const dateParams = this.getDynamicDateParams();
      const promises = this.activeCharts.map(async (chart) => {
        try {
          const params = chart.date_range_type === 'dynamic' ? dateParams : null;
          const result = await this.store.fetchChartData(chart.id, forceRefresh, params);
          this.chartDataMap[chart.id] = result;
        } catch { /* skip */ }
      });
      await Promise.all(promises);
      this.chartDataMap = { ...this.chartDataMap };
      this.loading = false;
    },
    async refreshChart(chartId) {
      const chart = this.activeCharts.find(c => c.id === chartId);
      this.chartDataMap = { ...this.chartDataMap, [chartId]: null };
      const params = chart?.date_range_type === 'dynamic' ? this.getDynamicDateParams() : null;
      const result = await this.store.fetchChartData(chartId, true, params);
      this.chartDataMap = { ...this.chartDataMap, [chartId]: result };
    },
    async reloadDynamicCharts() {
      const dateParams = this.getDynamicDateParams();
      // Clear cache + data for dynamic charts
      const dynamicCharts = this.activeCharts.filter(c => c.date_range_type === 'dynamic');
      for (const c of dynamicCharts) {
        this.chartDataMap[c.id] = null;
      }
      this.chartDataMap = { ...this.chartDataMap };

      const promises = dynamicCharts.map(async (chart) => {
        try {
          const result = await this.store.fetchChartData(chart.id, true, dateParams);
          this.chartDataMap[chart.id] = result;
        } catch { /* skip */ }
      });
      await Promise.all(promises);
      this.chartDataMap = { ...this.chartDataMap };
    },
    formatStatValue(data) {
      if (!data?.series?.length || !data.series[0]?.data?.length) return '0';
      const unit = data.series[0].measure_unit || '';
      const total = data.series[0].data.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
      const suffix = unit ? ` ${unit}` : '';
      const formatted = total.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).replace(/\u202F/g, ' ').replace(',', '.');
      if (unit === '%') return formatted + '%';
      return formatted + suffix;
    },
    getDateLabel(chart) {
      if (chart.date_range_type === 'all') return this.$t('all_dates');
      if (chart.date_range_type === 'fixed_year') return `${chart.fixed_year}`;
      if (chart.date_range_type === 'fixed_month') return `${String(chart.fixed_month).padStart(2, '0')}/${chart.fixed_year}`;
      // Dynamic — show the currently selected range from the picker
      return `${this.formatDateForApi(this.dateRange.start)} → ${this.formatDateForApi(this.dateRange.end)}`;
    },
    editChart(chart) {
      this.$router.push({ name: 'chart_management', query: { edit: chart.id } });
    },
  },
  watch: {
    'store.list': {
      handler() { this.loadAllChartData(true); },
      deep: true,
    },
    dateRange: {
      handler() { this.reloadDynamicCharts(); },
      deep: true,
    },
  },
};
</script>

<style scoped>
.main-dashboard { font-family: 'Inter', sans-serif; }
.md-hero { background: linear-gradient(135deg, #0097A7 0%, #00BCD4 40%, #4DD0E1 100%); border-radius: 20px; margin-bottom: 16px; }
.md-hero-inner { position: relative; z-index: 1; padding: 24px 28px; color: #fff; }
.md-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.md-hero-title { font-size: 22px; font-weight: 800; letter-spacing: .3px; }
.md-date-card { border: 1px solid rgba(0, 188, 212, 0.15); }
.md-date-card :deep(.vc-container) { width: 100% !important; border: none !important; }
.chart-card { transition: box-shadow .2s; }
.chart-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.1) !important; }
.stat-card { background: linear-gradient(135deg, #f5fdff 0%, #e0f7fa 100%); }
:deep(.v-theme--dark) .stat-card,
.v-theme--dark .stat-card {
  background: linear-gradient(135deg, #0e2a30 0%, #16353d 100%) !important;
  border: 1px solid rgba(0, 188, 212, 0.25) !important;
}
.stat-value { font-size: 32px; font-weight: 800; color: #00838F; }
:deep(.v-theme--dark) .stat-value,
.v-theme--dark .stat-value {
  color: #26c6da !important;
}
</style>
