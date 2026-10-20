<template>
  <div class="main-dashboard pb-16">
    <!-- Atmospheric Hero -->
    <div class="md-hero elevation-2">
      <div class="hero-ambient-glow"></div>
      <div class="md-hero-inner">
        <div class="d-flex align-center justify-space-between flex-wrap ga-3">
          <!-- Left: Icon & Title -->
          <div class="d-flex align-center ga-3 ga-sm-4">
            <div class="md-hero-icon">
              <v-icon size="28" color="white">mdi-chart-box-multiple-outline</v-icon>
            </div>
            <div>
              <div class="d-flex align-center ga-2 flex-wrap">
                <span class="md-hero-title">{{ $t('main_dashboard') }}</span>
                <span class="hub-pill ml-1">
                  <span class="hub-dot"></span>
                  {{ $t('active_charts_overview') || 'Executive Analytics' }}
                </span>
              </div>
              <div class="text-white text-caption mt-1" style="opacity: 0.85; font-size: 12px; line-height: 1.4;">
                {{ $t('custom_charts_subtitle') }}
              </div>
            </div>
          </div>

          <!-- Right: Summary Chips & Manage Button -->
          <div class="d-flex align-center ga-2.5 flex-wrap">
            <v-chip variant="flat" size="small" class="hero-chip">
              <v-icon start size="14" class="mr-1.5">mdi-chart-bell-curve</v-icon>
              {{ activeCharts.length }} {{ $t('charts') || 'Charts' }}
            </v-chip>
            <v-btn
              v-if="canManage"
              variant="flat"
              color="white"
              size="small"
              rounded="pill"
              :to="{ name: 'chart_management' }"
              class="manage-btn text-none font-weight-bold px-4"
            >
              <v-icon start size="16" class="mr-1.5">mdi-cog-outline</v-icon>
              {{ $t('manage_charts') }}
            </v-btn>
          </div>
        </div>
      </div>
    </div>

    <!-- Dynamic Date Range Picker Bar (Compact & Eye-Relief) -->
    <v-card v-if="hasDynamicCharts" class="md-date-card rounded-2xl mb-4" elevation="1">
      <v-card-text class="pa-3 pa-sm-4">
        <div class="d-flex align-center justify-space-between flex-wrap ga-3">
          <!-- Left: Title & Active Range Pill with generous spacing and relief -->
          <div class="d-flex align-center ga-3">
            <div class="date-hero-mini-icon">
              <v-icon size="20" color="#00838F">mdi-calendar-range</v-icon>
            </div>
            <div>
              <div class="d-flex align-center ga-2">
                <span class="text-subtitle-2 font-weight-bold text-slate-800">{{ $t('dynamic_range') }}</span>
                <v-chip size="x-small" variant="flat" class="duration-chip font-weight-bold ml-1">
                  {{ dynamicDaysCount }} {{ $t('days_selected') }}
                </v-chip>
              </div>
              <div class="text-caption text-medium-emphasis mt-1 d-none d-sm-block" style="font-size: 11px !important; line-height: 1.4;">
                {{ formatDateDisplay(dateRange.start) }} &rarr; {{ formatDateDisplay(dateRange.end) }}
              </div>
            </div>
          </div>

          <!-- Quick Presets with SegmentedPillToggle (Activity Breakdown style) -->
          <div class="d-flex align-center flex-wrap ga-2.5">
            <SegmentedPillToggle
              v-model="activePreset"
              :items="presetItems"
              color="#00838F"
              size="small"
              @change="applyPreset"
            />

            <v-divider vertical class="mx-1 d-none d-sm-block" style="height: 28px;"></v-divider>

            <v-btn
              size="small"
              rounded="pill"
              :variant="calendarExpanded ? 'flat' : 'outlined'"
              :color="calendarExpanded ? '#00838F' : undefined"
              :class="['custom-cal-btn', { 'text-white': calendarExpanded }]"
              @click="calendarExpanded = !calendarExpanded"
            >
              <v-icon start size="15" class="mr-1.5">{{ calendarExpanded ? 'mdi-chevron-up' : 'mdi-calendar-edit' }}</v-icon>
              {{ calendarExpanded ? $t('collapse_calendar') : $t('expand_calendar') }}
            </v-btn>
          </div>
        </div>

        <!-- Expandable Calendar -->
        <v-expand-transition>
          <div v-if="calendarExpanded" class="expanded-calendar-wrap mt-3 pt-3 border-t">
            <div class="d-flex align-center justify-space-between mb-2 px-1">
              <span class="text-caption font-weight-medium text-medium-emphasis d-flex align-center">
                <v-icon size="15" class="mr-2 text-cyan-darken-3">mdi-information-outline</v-icon>
                {{ $t('dynamic_range_dashboard_hint') }}
              </span>
              <v-btn size="x-small" variant="text" color="#00838F" class="px-3" @click="calendarExpanded = false">
                <v-icon start size="14" class="mr-1">mdi-check</v-icon>
                {{ $t('done') || 'OK' }}
              </v-btn>
            </div>
            <DatePicker
              :is-dark="main.is_dark"
              v-model.range="dateRange"
              mode="date"
              :locale="main.lang?.short || 'en'"
              :max-date="new Date()"
              expanded
              color="teal"
              :columns="$vuetify.display.mdAndUp ? 2 : 1"
            />
          </div>
        </v-expand-transition>
      </v-card-text>
    </v-card>

    <!-- Loading State -->
    <div v-if="loading" class="d-flex flex-column align-center justify-center py-16">
      <v-progress-circular indeterminate color="#00838F" size="48" width="4" />
      <span class="text-caption text-medium-emphasis mt-3">{{ $t('loading') || 'Loading charts...' }}</span>
    </div>

    <!-- No Charts State -->
    <div v-else-if="!activeCharts.length" class="empty-state-card text-center py-16 px-4 rounded-2xl border-dashed">
      <v-icon size="64" color="#00838F" style="opacity: 0.5;" class="mb-3">mdi-chart-box-plus-outline</v-icon>
      <div class="text-h6 font-weight-bold text-slate-800 mb-1">{{ $t('no_charts_configured') }}</div>
      <div class="text-caption text-medium-emphasis mb-4" style="max-width: 420px; margin: 0 auto;">
        {{ $t('create_first_chart_prompt') }}
      </div>
      <v-btn
        v-if="canManage"
        variant="flat"
        color="#00838F"
        rounded="pill"
        prepend-icon="mdi-plus"
        :to="{ name: 'chart_management' }"
        class="text-white text-none font-weight-bold"
      >
        {{ $t('create_chart') }}
      </v-btn>
    </div>

    <!-- Active Charts Grid -->
    <v-row v-else class="mt-1" dense>
      <v-col
        v-for="chart in activeCharts"
        :key="chart.id"
        :cols="12"
        :md="chart.grid_cols || 6"
        class="mb-3"
      >
        <!-- A. Statistic Card -->
        <v-card v-if="chart.display_type === 'statistic'" class="luxury-stat-card rounded-2xl h-100" elevation="1">
          <v-card-text class="pa-5 d-flex flex-column justify-space-between h-100">
            <div>
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-subtitle-2 font-weight-bold text-medium-emphasis">{{ chart.title }}</span>
                <div class="d-flex align-center ga-1">
                  <v-chip v-if="chart.date_range_type === 'dynamic'" size="x-small" variant="tonal" color="#00838F" class="font-weight-medium">
                    {{ $t('dynamic_range') }}
                  </v-chip>
                  <v-btn v-if="canManage" icon="mdi-pencil-outline" size="x-small" variant="text" color="#00838F" @click="editChart(chart)" />
                </div>
              </div>
              <div class="text-caption text-medium-emphasis mb-3">{{ getDateLabel(chart) }}</div>
            </div>

            <div class="mt-2">
              <div v-if="chartDataMap[chart.id]" class="stat-value">
                {{ formatStatValue(chartDataMap[chart.id]) }}
              </div>
              <v-skeleton-loader v-else type="text" width="140" height="42" />
            </div>
          </v-card-text>
        </v-card>

        <!-- B. Chart Card -->
        <v-card v-else class="luxury-chart-card rounded-2xl h-100" elevation="1">
          <v-card-text class="pa-4 pa-sm-5">
            <!-- Header strip: Title, Type Chips & Actions -->
            <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-2">
              <div>
                <span class="text-subtitle-1 font-weight-bold text-slate-800">{{ chart.title }}</span>
                <div class="text-caption text-medium-emphasis">{{ getDateLabel(chart) }}</div>
              </div>

              <!-- Badges & Action Buttons (Grouped with proper padding to avoid float robot overlap) -->
              <div class="d-flex align-center ga-1.5 chart-card-actions">
                <v-chip v-if="chart.date_range_type === 'dynamic'" size="x-small" variant="tonal" color="#00838F" class="font-weight-medium">
                  {{ $t('dynamic_range') }}
                </v-chip>
                <v-chip size="x-small" variant="tonal" color="cyan-darken-3" class="text-uppercase font-weight-bold">
                  {{ chart.chart_type }}
                </v-chip>
                <v-btn icon size="x-small" variant="tonal" color="#00838F" @click="refreshChart(chart.id)">
                  <v-icon size="14">mdi-refresh</v-icon>
                  <v-tooltip activator="parent" location="top">{{ $t('refresh') }}</v-tooltip>
                </v-btn>
                <v-btn v-if="canManage" icon size="x-small" variant="tonal" color="#00838F" @click="editChart(chart)">
                  <v-icon size="14">mdi-pencil-outline</v-icon>
                  <v-tooltip activator="parent" location="top">{{ $t('edit') }}</v-tooltip>
                </v-btn>
              </div>
            </div>

            <v-divider class="my-2 border-dashed opacity-50" />

            <!-- Chart Body -->
            <div v-if="!chartDataMap[chart.id]" class="d-flex justify-center align-center py-12">
              <v-progress-circular indeterminate color="#00838F" size="36" width="3" />
            </div>
            <div v-else class="chart-preview-wrapper pt-2">
              <ChartPreview
                :chart-config="chart"
                :categories="chartDataMap[chart.id].categories"
                :series="chartDataMap[chart.id].series"
                :height="320"
              />
            </div>
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
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import moment from 'moment';

export default {
  name: 'MainDashboard',
  components: { ChartPreview, DatePicker, SegmentedPillToggle },
  data() {
    return {
      loading: false,
      chartDataMap: {},
      dateRange: {
        start: new Date(new Date().getFullYear(), 0, 1),
        end: new Date(),
      },
      calendarExpanded: false,
      activePreset: 'ytd',
    };
  },
  computed: {
    store() { return useCustomChart(); },
    auth() { return useAuth(); },
    main() { return useMain(); },
    activeCharts() { return this.store.sortedCharts.filter(c => c.is_active); },
    canManage() { return this.auth.isSuperAdmin || this.auth.hasPermission('dashboard.manage'); },
    hasDynamicCharts() { return this.activeCharts.some(c => c.date_range_type === 'dynamic'); },
    dynamicDaysCount() {
      if (!this.dateRange.start || !this.dateRange.end) return 0;
      return Math.max(1, moment(this.dateRange.end).diff(moment(this.dateRange.start), 'days') + 1);
    },
    presetItems() {
      return [
        { value: '7d', label: this.$t('last_7_days') || '7D' },
        { value: '30d', label: this.$t('last_30_days') || '30D' },
        { value: 'month', label: this.$t('this_month') || 'Month' },
        { value: '90d', label: this.$t('last_90_days') || '90D' },
        { value: 'ytd', label: this.$t('this_year') || 'YTD' },
      ];
    },
    presets() {
      return [
        { id: '7d', label: this.$t('last_7_days') || '7D' },
        { id: '30d', label: this.$t('last_30_days') || '30D' },
        { id: 'month', label: this.$t('this_month') || 'Month' },
        { id: '90d', label: this.$t('last_90_days') || '90D' },
        { id: 'ytd', label: this.$t('this_year') || 'YTD' },
      ];
    },
  },
  async mounted() {
    this.detectActivePreset(this.dateRange);
    await this.loadAllChartData();
  },
  methods: {
    applyPreset(id) {
      this.activePreset = id;
      const today = moment().endOf('day').toDate();
      let start = moment().subtract(30, 'days').startOf('day').toDate();

      if (id === '7d') {
        start = moment().subtract(6, 'days').startOf('day').toDate();
      } else if (id === '30d') {
        start = moment().subtract(29, 'days').startOf('day').toDate();
      } else if (id === 'month') {
        start = moment().startOf('month').toDate();
      } else if (id === '90d') {
        start = moment().subtract(89, 'days').startOf('day').toDate();
      } else if (id === 'ytd') {
        start = moment().startOf('year').toDate();
      }

      this.dateRange = { start, end: today };
    },
    detectActivePreset(range) {
      if (!range?.start || !range?.end) return;
      const diff = moment(range.end).diff(moment(range.start), 'days');
      if (diff === 6) this.activePreset = '7d';
      else if (diff === 29 || diff === 30) this.activePreset = '30d';
      else if (diff >= 88 && diff <= 91) this.activePreset = '90d';
      else if (moment(range.start).isSame(moment().startOf('month'), 'day')) this.activePreset = 'month';
      else if (moment(range.start).isSame(moment().startOf('year'), 'day')) this.activePreset = 'ytd';
      else this.activePreset = 'custom';
    },
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
      handler(val) {
        this.detectActivePreset(val);
        this.reloadDynamicCharts();
      },
      deep: true,
    },
  },
};
</script>

<style scoped>
.main-dashboard {
  font-family: 'Inter', sans-serif;
  max-width: 100%;
}

.md-hero {
  position: relative;
  background: linear-gradient(135deg, #006064 0%, #00838F 45%, #00ACC1 100%);
  border-radius: 20px;
  overflow: hidden;
  margin-bottom: 16px;
  border: 1px solid rgba(0, 172, 193, 0.25);
}

.hero-ambient-glow {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 240px;
  height: 240px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(77, 208, 225, 0.35) 0%, rgba(0, 131, 143, 0) 70%);
  pointer-events: none;
}

.md-hero-inner {
  position: relative;
  z-index: 1;
  padding: 22px 26px;
  color: #fff;
}

.md-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.md-hero-title {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.3px;
}

.hub-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 2px 10px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  font-size: 11px;
  font-weight: 700;
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.25);
}

.hub-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #E0F7FA;
  box-shadow: 0 0 8px #E0F7FA;
}

.hero-chip {
  background: rgba(255, 255, 255, 0.18) !important;
  color: white !important;
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.25);
}

.manage-btn {
  color: #00838F !important;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
}

.md-date-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(0, 131, 143, 0.18);
  box-shadow: 0 4px 20px rgba(0, 131, 143, 0.05);
  transition: all 0.25s ease;
}

:deep(.v-theme--dark) .md-date-card,
.v-theme--dark .md-date-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(0, 172, 193, 0.25);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.date-hero-mini-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(0, 131, 143, 0.15) 0%, rgba(0, 172, 193, 0.08) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(0, 131, 143, 0.2);
  flex-shrink: 0;
}

.duration-chip {
  background: rgba(0, 131, 143, 0.12) !important;
  color: #00838F !important;
  font-size: 10.5px !important;
}

.preset-btn {
  font-size: 11px !important;
  font-weight: 600;
  text-transform: none;
  background: rgba(0, 131, 143, 0.08);
  color: #00838F;
  transition: all 0.18s ease;
}

.preset-btn:hover {
  background: rgba(0, 131, 143, 0.16);
}

.preset-btn.preset-active {
  background: linear-gradient(135deg, #00838F 0%, #00ACC1 100%) !important;
  color: white !important;
  box-shadow: 0 2px 8px rgba(0, 131, 143, 0.3);
}

.custom-cal-btn {
  font-size: 11px !important;
  font-weight: 600;
  text-transform: none;
  border-color: rgba(0, 131, 143, 0.3) !important;
}

.expanded-calendar-wrap {
  border-top-color: rgba(0, 131, 143, 0.15) !important;
}

.expanded-calendar-wrap :deep(.vc-container) {
  width: 100% !important;
  border: none !important;
}

.empty-state-card {
  background: rgba(248, 250, 252, 0.6);
  border: 2px dashed rgba(0, 131, 143, 0.25);
}

.luxury-chart-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
  transition: all 0.25s ease;
}

:deep(.v-theme--dark) .luxury-chart-card,
.v-theme--dark .luxury-chart-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.luxury-chart-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 26px rgba(0, 0, 0, 0.08) !important;
}

.luxury-stat-card {
  background: linear-gradient(135deg, #F0FDFA 0%, #E0F2FE 100%);
  border: 1px solid rgba(0, 131, 143, 0.18);
  box-shadow: 0 4px 20px rgba(0, 131, 143, 0.06);
  transition: all 0.25s ease;
}

:deep(.v-theme--dark) .luxury-stat-card,
.v-theme--dark .luxury-stat-card {
  background: linear-gradient(135deg, #042F2E 0%, #082F49 100%) !important;
  border: 1px solid rgba(45, 212, 191, 0.25) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}

.stat-value {
  font-size: 32px;
  font-weight: 800;
  color: #00838F;
  letter-spacing: -0.5px;
}

:deep(.v-theme--dark) .stat-value,
.v-theme--dark .stat-value {
  color: #2DD4BF !important;
}

.chart-card-actions {
  margin-right: 4px;
}
</style>
