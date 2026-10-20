<template>
  <v-card class="jupiter-chart-card rounded-2xl mb-4" elevation="1">
    <v-card-text class="pa-3 pa-sm-5">
      <!-- Toolbar Row 1: Title, Module Focus Filter & Config -->
      <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-3">
        <!-- Title & Icon with generous spacing and relief -->
        <div class="d-flex align-center ga-3">
          <div class="chart-mini-icon">
            <v-icon size="20" color="#0891B2">mdi-chart-timeline-variant</v-icon>
          </div>
          <div>
            <div class="d-flex align-center ga-2">
              <span class="text-subtitle-1 font-weight-bold">{{ $t('jupiter_chart') }}</span>
              <v-chip v-if="categories.length" size="x-small" variant="flat" class="data-points-badge font-weight-bold ml-1">
                {{ categories.length }} {{ periodicity === 'daily' ? $t('days_selected') : $t('categories') || 'periods' }}
              </v-chip>
            </div>
            <div class="text-caption text-medium-emphasis mt-1" style="font-size: 11px !important; line-height: 1.4;">
              {{ $t('jupiter_subtitle') }}
            </div>
          </div>
        </div>

        <!-- Module Focus Filter Pills using SegmentedPillToggle (Activity Breakdown style) -->
        <SegmentedPillToggle
          v-model="selectedModule"
          :items="moduleItems"
          color="#0891B2"
          size="small"
        />
      </div>

      <!-- Toolbar Row 2: Visual Type, Periodicity, Measures & Config (Activity Breakdown style) -->
      <div class="chart-controls-strip d-flex flex-wrap align-center justify-space-between ga-3 mb-4 pa-2.5 rounded-xl border">
        <!-- Left: Visual Type Mode (Bars vs Area vs Stacked) & Periodicity -->
        <div class="d-flex align-center flex-wrap ga-2.5">
          <SegmentedPillToggle
            v-model="chartVisualMode"
            :items="visualModeItems"
            color="#0891B2"
            size="small"
          />

          <v-divider vertical class="mx-1 d-none d-sm-block" style="height: 28px;"></v-divider>

          <SegmentedPillToggle
            v-model="periodicity"
            :items="periodicityItems"
            color="#0891B2"
            size="small"
          />
        </div>

        <!-- Right: Measures (Weight / Price) & Settings in Image 2 chip style -->
        <div class="d-flex align-center flex-wrap ga-2">
          <v-divider vertical class="mx-1 d-none d-md-block" style="height: 28px;"></v-divider>

          <!-- Measure Toggle Chips (Matching Image 2 Declared / Non-Declared style) -->
          <div class="d-flex align-center ga-2">
            <v-chip
              :variant="measures.includes('weight') ? 'flat' : 'outlined'"
              color="#00897B"
              size="small"
              class="cursor-pointer font-weight-bold measure-pill-chip"
              @click="toggleMeasure('weight')"
            >
              <v-icon start size="15" class="mr-1.5">
                {{ measures.includes('weight') ? 'mdi-check-circle' : 'mdi-weight-kilogram' }}
              </v-icon>
              {{ $t('weight_kg') }}
            </v-chip>

            <v-chip
              :variant="measures.includes('price') ? 'flat' : 'outlined'"
              color="#1565C0"
              size="small"
              class="cursor-pointer font-weight-bold measure-pill-chip"
              @click="toggleMeasure('price')"
            >
              <v-icon start size="15" class="mr-1.5">
                {{ measures.includes('price') ? 'mdi-check-circle' : 'mdi-cash' }}
              </v-icon>
              {{ $t('price_dh') }}
            </v-chip>
          </div>

          <!-- Config Modal Button -->
          <v-btn
            icon
            size="small"
            variant="tonal"
            color="#0891B2"
            rounded="pill"
            class="ml-1"
            @click="showConfig = true"
            id="jupiter-chart-config-btn"
          >
            <v-icon size="17">mdi-cog-outline</v-icon>
            <v-tooltip activator="parent" location="top">{{ $t('chart_configuration') }}</v-tooltip>
          </v-btn>
        </div>
      </div>

      <!-- Chart Content Area -->
      <div v-if="loading && !categories.length" class="d-flex flex-column align-center justify-center py-16">
        <v-progress-circular indeterminate color="#0891B2" size="44" width="4" />
        <span class="text-caption text-medium-emphasis mt-3">{{ $t('loading') || 'Loading...' }}</span>
      </div>

      <div v-else-if="!filteredSeries.length" class="text-center py-16">
        <v-icon size="48" color="#CBD5E1">mdi-chart-line-variant</v-icon>
        <div class="text-subtitle-2 text-medium-emphasis mt-2">{{ $t('no_charts_found_matching') }}</div>
      </div>

      <div v-else class="chart-canvas-wrap">
        <apexchart
          :key="chartRenderKey"
          :type="chartEffectiveType"
          :height="440"
          :options="chartOptions"
          :series="filteredSeries"
          ref="jupiterChart"
        />
      </div>
    </v-card-text>

    <!-- Configuration Dialog -->
    <v-dialog v-model="showConfig" max-width="650" scrollable>
      <v-card class="rounded-2xl">
        <v-card-title class="d-flex align-center ga-2 pa-4 text-white" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 40%, #0E7490 100%)">
          <v-icon color="#06B6D4">mdi-cog-outline</v-icon>
          <span class="font-weight-bold">{{ $t('chart_configuration') }}</span>
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" size="small" color="white" @click="showConfig = false" />
        </v-card-title>

        <v-card-text class="pa-4">
          <div class="text-caption text-medium-emphasis mb-3">{{ $t('group_series_desc') }}</div>

          <div v-for="(grp, gi) in chartConfig.groups" :key="gi" class="mb-3">
            <v-card variant="outlined" class="rounded-xl pa-3 border-slate-200">
              <div class="d-flex align-center ga-2 mb-2">
                <v-icon size="16" color="#0891B2">mdi-group</v-icon>
                <v-text-field
                  v-model="grp.name"
                  variant="plain"
                  density="compact"
                  hide-details
                  class="text-subtitle-2 font-weight-bold"
                  style="max-width: 200px"
                  color="#0891B2"
                />
                <v-spacer />
                <v-switch
                  v-model="grp.stacked"
                  :label="$t('stack_series')"
                  density="compact"
                  hide-details
                  color="#0891B2"
                  class="mr-2"
                  inset
                />
                <v-btn
                  icon="mdi-close"
                  size="x-small"
                  variant="text"
                  color="error"
                  @click="removeConfigGroup(gi)"
                  :disabled="chartConfig.groups.length <= 1"
                />
              </div>

              <div v-for="(seriesKey, si) in grp.series" :key="seriesKey" class="d-flex align-center ga-2 mb-1 pl-4">
                <div class="series-dot" :style="{ background: getSeriesColor(seriesKey) }"></div>
                <span class="text-body-2">{{ getSeriesLabel(seriesKey) }}</span>
                <v-spacer />
                <v-menu v-if="chartConfig.groups.length > 1">
                  <template v-slot:activator="{ props }">
                    <v-btn icon size="x-small" variant="text" v-bind="props">
                      <v-icon size="14">mdi-arrow-right-bold-box-outline</v-icon>
                      <v-tooltip activator="parent" location="top">{{ $t('move_to_group') }}</v-tooltip>
                    </v-btn>
                  </template>
                  <v-list density="compact" nav>
                    <v-list-item
                      v-for="(otherGrp, ogi) in chartConfig.groups"
                      :key="ogi"
                      :disabled="ogi === gi"
                      @click="moveSeries(gi, si, ogi)"
                    >
                      <v-list-item-title class="text-caption">{{ otherGrp.name }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </div>
              <div v-if="!grp.series.length" class="text-caption text-medium-emphasis pl-4">{{ $t('no_series_in_group') }}</div>
            </v-card>
          </div>

          <v-btn
            variant="tonal"
            color="#0891B2"
            rounded="pill"
            size="small"
            prepend-icon="mdi-plus"
            @click="addConfigGroup"
            id="jupiter-config-add-group-btn"
          >
            {{ $t('add_group') }}
          </v-btn>
        </v-card-text>

        <v-card-actions class="pa-4 pt-0">
          <v-spacer />
          <v-btn variant="flat" color="#0891B2" class="text-white font-weight-bold" rounded="pill" @click="saveConfig" id="jupiter-config-save-btn">
            <v-icon start>mdi-check</v-icon>
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useMain } from '@/stores/Main';

const STORAGE_KEY = 'jupiter_chart_config';

const ALL_SERIES_KEYS = [
  'charges_weight', 'charges_price',
  'productions_weight', 'productions_price',
  'shippings_weight', 'shippings_price',
];

const SERIES_COLORS = {
  charges_weight:     '#42A5F5',
  charges_price:      '#1565C0',
  productions_weight: '#00897B',
  productions_price:  '#004D40',
  shippings_weight:   '#6c66bb',
  shippings_price:    '#372e7d',
};

export default {
  name: 'JupiterChart',
  components: { apexchart: VueApexCharts, SegmentedPillToggle },
  props: {
    categories: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    costLines: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
  },
  emits: ['update:periodicity', 'update:measures'],
  data() {
    const saved = this.loadConfig();
    return {
      periodicity: saved.periodicity || 'daily',
      measures: saved.measures || ['weight', 'price'],
      showConfig: false,
      chartConfig: saved.chartConfig || this.defaultChartConfig(),
      selectedModule: 'all', // 'all', 'charges', 'productions', 'shippings'
      chartVisualMode: 'bar', // 'bar', 'area', 'stacked'
    };
  },
  computed: {
    main() { return useMain(); },
    moduleItems() {
      return [
        { value: 'all', label: this.$t('all_modules') || 'All Modules', icon: 'mdi-view-dashboard-outline' },
        { value: 'charges', label: this.$t('charges') || 'Charges', icon: 'mdi-truck-cargo-container' },
        { value: 'productions', label: this.$t('production') || 'Production', icon: 'mdi-factory' },
        { value: 'shippings', label: this.$t('shippings') || 'Shippings', icon: 'mdi-package-variant-closed' },
      ];
    },
    visualModeItems() {
      return [
        { value: 'bar', label: this.$t('chart_mode_bars') || 'Bars', icon: 'mdi-chart-bar' },
        { value: 'area', label: this.$t('chart_mode_area') || 'Area', icon: 'mdi-chart-bell-curve-cumulative' },
        { value: 'stacked', label: this.$t('chart_mode_stacked') || 'Stacked', icon: 'mdi-chart-box' },
      ];
    },
    periodicityItems() {
      return [
        { value: 'daily', label: this.$t('daily') || 'Daily', icon: 'mdi-calendar-today' },
        { value: 'monthly', label: this.$t('monthly') || 'Monthly', icon: 'mdi-calendar-month' },
        { value: 'yearly', label: this.$t('yearly') || 'Yearly', icon: 'mdi-calendar-range' },
      ];
    },
    chartEffectiveType() {
      if (this.chartVisualMode === 'area') return 'area';
      return 'bar';
    },
    chartRenderKey() {
      return `${this.chartVisualMode}_${this.selectedModule}_${this.periodicity}_${this.categories.length}`;
    },
    computedSeries() {
      if (!this.series.length) return [];
      const hasCost = this.costLines && this.costLines.length > 0;
      const result = [];
      for (const grp of this.chartConfig.groups) {
        for (const seriesKey of grp.series) {
          const apiSeries = this.series.find(s => `${s.type}_${s.measure}` === seriesKey);
          if (apiSeries) {
            const entry = {
              name: apiSeries.name,
              data: apiSeries.data,
              color: SERIES_COLORS[seriesKey] || '#0891B2',
              moduleType: apiSeries.type, // 'charges', 'productions', 'shippings'
            };
            if (this.chartVisualMode === 'area') {
              entry.type = 'area';
            } else if (hasCost) {
              entry.type = 'bar';
            } else {
              entry.group = grp.name;
            }
            result.push(entry);
          }
        }
      }
      return result;
    },
    costLineSeries() {
      if (!this.costLines || !this.costLines.length) return [];
      const lang = this.main.lang?.short || 'fr';
      return this.costLines.map(cl => ({
        name: cl[`name_${lang}`] || cl.name || `Cost #${cl.id}`,
        type: 'line',
        data: cl.data,
        color: cl.color || '#E11D48',
        moduleType: 'cost',
      }));
    },
    allSeries() {
      return [...this.computedSeries, ...this.costLineSeries];
    },
    filteredSeries() {
      if (this.selectedModule === 'all') return this.allSeries;
      return this.allSeries.filter(s => s.moduleType === this.selectedModule || s.moduleType === 'cost');
    },
    isStacked() {
      if (this.chartVisualMode === 'stacked') return true;
      return this.chartConfig.groups.some(g => g.stacked && g.series.length > 1);
    },
    hasCostLines() {
      return this.costLineSeries.length > 0;
    },
    chartOptions() {
      const isDark = this.main.is_dark;
      const isArea = this.chartVisualMode === 'area';
      const isStack = this.isStacked;

      const yaxes = [
        {
          title: {
            text: `${this.$t('weight_kg')} / ${this.$t('price_dh')}`,
            style: { color: isDark ? '#94A3B8' : '#64748B', fontSize: '11px', fontWeight: 600 },
          },
          labels: {
            formatter: v => this.main.formatDisplayNumber(v),
            style: { colors: isDark ? '#94A3B8' : '#64748B', fontSize: '11px' },
          },
        },
      ];

      return {
        chart: {
          type: this.chartEffectiveType,
          stacked: this.hasCostLines ? false : isStack,
          toolbar: {
            show: true,
            tools: {
              download: true,
              selection: false,
              zoom: true,
              zoomin: true,
              zoomout: true,
              pan: true,
              reset: true,
            },
          },
          fontFamily: "'Inter', sans-serif",
          animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 400,
          },
          background: 'transparent',
        },
        stroke: {
          curve: 'smooth',
          width: isArea ? 2.5 : (this.hasCostLines ? 2 : 0),
        },
        fill: isArea
          ? {
              type: 'gradient',
              gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [0, 95, 100],
              },
            }
          : { opacity: 0.95 },
        plotOptions: {
          bar: {
            borderRadius: 4,
            columnWidth: this.categories.length > 20 ? '75%' : '55%',
            dataLabels: { position: 'top' },
          },
        },
        dataLabels: { enabled: false },
        xaxis: {
          categories: this.categories,
          labels: {
            rotate: -45,
            style: {
              fontSize: '11px',
              colors: isDark ? '#94A3B8' : '#64748B',
            },
          },
          axisBorder: { show: false },
          axisTicks: { show: false },
        },
        yaxis: yaxes,
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          shared: true,
          intersect: false,
          y: {
            formatter: val => `${this.main.formatDisplayNumber(val)}`,
          },
        },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          fontSize: '12px',
          fontWeight: 600,
          labels: {
            colors: isDark ? '#E2E8F0' : '#334155',
          },
          markers: {
            radius: 6,
          },
        },
        grid: {
          borderColor: isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)',
          strokeDashArray: 4,
          padding: {
            left: 10,
            right: 25,
            bottom: 5,
          },
        },
        theme: { mode: isDark ? 'dark' : 'light' },
      };
    },
  },
  watch: {
    periodicity(val) {
      this.$emit('update:periodicity', val);
      this.persistConfig();
    },
    measures(val) {
      if (!val || !val.length) {
        this.measures = ['weight'];
        return;
      }
      this.$emit('update:measures', val);
      this.persistConfig();
    },
  },
  methods: {
    defaultChartConfig() {
      return {
        groups: [
          {
            name: 'Group 1',
            series: [...ALL_SERIES_KEYS],
            stacked: false,
          },
        ],
      };
    },
    loadConfig() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
          const parsed = JSON.parse(raw);
          return {
            periodicity: parsed.periodicity || 'daily',
            measures: parsed.measures || ['weight', 'price'],
            chartConfig: parsed.chartConfig || this.defaultChartConfig(),
          };
        }
      } catch (e) { /* corrupted */ }
      return {};
    },
    persistConfig() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
          periodicity: this.periodicity,
          measures: this.measures,
          chartConfig: this.chartConfig,
        }));
      } catch (e) { /* quota exceeded */ }
    },
    saveConfig() {
      this.persistConfig();
      this.showConfig = false;
    },
    addConfigGroup() {
      this.chartConfig.groups.push({
        name: `Group ${this.chartConfig.groups.length + 1}`,
        series: [],
        stacked: false,
      });
    },
    removeConfigGroup(gi) {
      if (this.chartConfig.groups.length <= 1) return;
      const removed = this.chartConfig.groups.splice(gi, 1)[0];
      if (removed.series.length) {
        this.chartConfig.groups[0].series.push(...removed.series);
      }
    },
    moveSeries(fromGi, seriesIdx, toGi) {
      const seriesKey = this.chartConfig.groups[fromGi].series.splice(seriesIdx, 1)[0];
      this.chartConfig.groups[toGi].series.push(seriesKey);
    },
    getSeriesColor(key) {
      return SERIES_COLORS[key] || '#0891B2';
    },
    getSeriesLabel(key) {
      const parts = key.split('_');
      const type = parts[0];
      const measure = parts[1];
      return `${this.$t(type)} — ${this.$t(measure)}`;
    },
    toggleMeasure(m) {
      const idx = this.measures.indexOf(m);
      if (idx > -1) {
        if (this.measures.length > 1) {
          this.measures.splice(idx, 1);
        }
      } else {
        this.measures.push(m);
      }
    },
  },
};
</script>

<style scoped>
.jupiter-chart-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(8, 145, 178, 0.18);
  box-shadow: 0 4px 20px rgba(8, 145, 178, 0.05);
  transition: all 0.25s ease;
}

:deep(.v-theme--dark) .jupiter-chart-card,
.v-theme--dark .jupiter-chart-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(6, 182, 212, 0.2);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.chart-mini-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.15) 0%, rgba(6, 182, 212, 0.08) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(8, 145, 178, 0.2);
  flex-shrink: 0;
}

.data-points-badge {
  background: rgba(8, 145, 178, 0.12) !important;
  color: #0891B2 !important;
  font-size: 10.5px !important;
  font-weight: 700;
}

.chart-controls-strip {
  background: rgba(248, 250, 252, 0.85);
  border: 1px solid rgba(226, 232, 240, 0.9);
}

:deep(.v-theme--dark) .chart-controls-strip,
.v-theme--dark .chart-controls-strip {
  background: rgba(30, 41, 59, 0.65);
  border-color: rgba(51, 65, 85, 0.6);
}

.measure-pill-chip {
  padding: 0 14px !important;
  height: 32px !important;
  font-size: 12px !important;
  letter-spacing: 0.15px;
  border-radius: 9999px !important;
  transition: all 0.2s ease;
}

.series-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.chart-canvas-wrap {
  width: 100%;
  min-height: 440px;
}
</style>
