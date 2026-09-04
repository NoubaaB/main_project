<template>
  <v-card class="jupiter-chart-card rounded-xl mb-4" elevation="1">
    <v-card-text class="pa-4">
      <!-- Header -->
      <div class="d-flex align-center ga-3 mb-4 flex-wrap">
        <v-icon style="color: #FF6F00">mdi-chart-bar</v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ $t('jupiter_chart') }}</span>
        <v-spacer />

        <!-- Periodicity -->
        <v-btn-toggle v-model="periodicity" mandatory density="compact" rounded="lg" variant="outlined" divided class="jupiter-toggle">
          <v-btn value="daily" size="small" :id="'jupiter-period-daily'">{{ $t('daily') }}</v-btn>
          <v-btn value="monthly" size="small" :id="'jupiter-period-monthly'">{{ $t('monthly') }}</v-btn>
          <v-btn value="yearly" size="small" :id="'jupiter-period-yearly'">{{ $t('yearly') }}</v-btn>
        </v-btn-toggle>

        <!-- Measure toggles (multi-select) -->
        <v-btn-toggle v-model="measures" multiple density="compact" rounded="lg" variant="outlined" divided class="jupiter-toggle ml-2">
          <v-btn value="weight" size="small" :id="'jupiter-measure-weight'">
            <v-icon start size="16">mdi-weight-kilogram</v-icon>
            {{ $t('weight_kg') }}
          </v-btn>
          <v-btn value="price" size="small" :id="'jupiter-measure-price'">
            <v-icon start size="16">mdi-cash</v-icon>
            {{ $t('price_dh') }}
          </v-btn>
        </v-btn-toggle>

        <!-- Config button -->
        <v-btn icon variant="text" size="small" @click="showConfig = true" id="jupiter-chart-config-btn">
          <v-icon size="20" style="color: #FF6F00">mdi-cog</v-icon>
          <v-tooltip activator="parent" location="top">{{ $t('chart_configuration') }}</v-tooltip>
        </v-btn>
      </div>

      <!-- Chart -->
      <div v-if="loading && !categories.length" class="d-flex justify-center py-10">
        <v-progress-circular indeterminate style="color: #FF6F00" size="40" />
      </div>
      <apexchart
        v-if="categories.length"
        type="bar"
        :height="420"
        :options="chartOptions"
        :series="allSeries"
        ref="jupiterChart"
      />
    </v-card-text>

    <!-- Configuration Dialog -->
    <v-dialog v-model="showConfig" max-width="650" scrollable>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center ga-2 pa-4" style="background: linear-gradient(135deg, #E65100 0%, #FF6F00 40%, #FFA726 100%)">
          <v-icon color="white">mdi-cog</v-icon>
          <span class="text-white font-weight-bold">{{ $t('chart_configuration') }}</span>
          <v-spacer />
          <v-btn icon variant="text" size="small" @click="showConfig = false">
            <v-icon color="white">mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text class="pa-4">
          <div class="text-caption text-medium-emphasis mb-3">{{ $t('group_series_desc') }}</div>

          <!-- Groups -->
          <div v-for="(grp, gi) in chartConfig.groups" :key="gi" class="mb-4">
            <v-card variant="outlined" class="rounded-lg" style="border-color: rgba(255,111,0,.3)">
              <v-card-text class="pa-3">
                <div class="d-flex align-center ga-2 mb-2">
                  <v-icon size="16" style="color: #FF6F00">mdi-group</v-icon>
                  <v-text-field
                    v-model="grp.name"
                    variant="plain"
                    density="compact"
                    hide-details
                    class="text-subtitle-2 font-weight-bold"
                    style="max-width: 200px"
                    color="#FF6F00"
                  />
                  <v-spacer />
                  <v-switch
                    v-model="grp.stacked"
                    :label="$t('stack_series')"
                    density="compact"
                    hide-details
                    color="#FF6F00"
                    class="mr-2"
                    inset
                  />
                  <v-btn
                    icon size="x-small" variant="text" color="error"
                    @click="removeConfigGroup(gi)"
                    :disabled="chartConfig.groups.length <= 1"
                  >
                    <v-icon size="14">mdi-close</v-icon>
                  </v-btn>
                </div>

                <!-- Series in this group -->
                <div v-for="(seriesKey, si) in grp.series" :key="seriesKey" class="d-flex align-center ga-2 mb-1 pl-4">
                  <div
                    class="jupiter-series-dot"
                    :style="{ background: getSeriesColor(seriesKey) }"
                  ></div>
                  <span class="text-body-2">{{ getSeriesLabel(seriesKey) }}</span>
                  <v-spacer />
                  <!-- Move to another group -->
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
              </v-card-text>
            </v-card>
          </div>

          <v-btn
            variant="tonal"
            style="color: #FF6F00"
            rounded
            size="small"
            prepend-icon="mdi-plus"
            @click="addConfigGroup"
            id="jupiter-config-add-group-btn"
          >{{ $t('add_group') }}</v-btn>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer />
          <v-btn variant="flat" style="background: #FF6F00; color: white" rounded @click="saveConfig" id="jupiter-config-save-btn">
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
  components: { apexchart: VueApexCharts },
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

    };
  },
  computed: {
    main() { return useMain(); },
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
              color: SERIES_COLORS[seriesKey] || '#999',
            };
            // Only set type and remove group when mixing with cost lines
            if (hasCost) {
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
        color: cl.color || '#FF5722',
      }));
    },
    allSeries() {
      return [...this.computedSeries, ...this.costLineSeries];
    },
    isStacked() {
      return this.chartConfig.groups.some(g => g.stacked && g.series.length > 1);
    },
    hasCostLines() {
      return this.costLineSeries.length > 0;
    },
    chartOptions() {
      // Build one yaxis entry per bar series pointing to weight or price axis
      const yaxes = [];
      const barSeries = this.computedSeries;
      const costSeries = this.costLineSeries;

      if (barSeries.length > 0) {
        // First yaxis (primary) for bars
        yaxes.push({
          title: { text: this.$t('weight_kg') + ' / ' + this.$t('price_dh') },
          labels: { formatter: v => this.main.formatDisplayNumber(v) },
        });
        // Additional bar series share the same yaxis (show: false)
        for (let i = 1; i < barSeries.length; i++) {
          yaxes.push({
            show: false,
            labels: { formatter: v => this.main.formatDisplayNumber(v) },
          });
        }
      }

      // Add a yaxis for each cost line
      costSeries.forEach((cl, idx) => {
        yaxes.push({
          opposite: true,
          show: idx === 0, // Only show the first cost axis
          title: idx === 0 ? { text: this.$t('cost_lines'), style: { color: '#E91E63' } } : undefined,
          labels: { formatter: v => this.main.formatDisplayNumber(v) },
        });
      });

      const strokeWidths = this.allSeries.map(s => s.type === 'line' ? 3 : 0);

      return {
        chart: {
          type: 'bar',
          stacked: this.hasCostLines ? false : this.isStacked,
          toolbar: { show: true },
          fontFamily: 'inherit',
          animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 500,
          },
        },
        ...(this.hasCostLines ? { stroke: { width: strokeWidths, curve: 'smooth' } } : {}),
        plotOptions: {
          bar: {
            borderRadius: 4,
            columnWidth: '60%',
            dataLabels: { position: 'top' },
          },
        },
        dataLabels: { enabled: false },
        xaxis: {
          categories: this.categories,
          labels: {
            rotate: -45,
            style: { fontSize: '11px' },
          },
        },
        yaxis: yaxes.length > 0 ? yaxes : [{}],
        tooltip: {
          shared: true,
          intersect: false,
          y: {
            formatter: (val) => this.main.formatDisplayNumber(val),
          },
        },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          fontSize: '12px',
        },
        fill: { opacity: 1 },
        grid: {
          borderColor: this.main.is_dark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)',
        },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
  },
  watch: {
    periodicity(val) {
      this.$emit('update:periodicity', val);
      this.persistConfig();
    },
    measures(val) {
      // Ensure at least one measure is selected
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
      // Move orphaned series to first group
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
      return SERIES_COLORS[key] || '#999';
    },
    getSeriesLabel(key) {
      const parts = key.split('_');
      const type = parts[0];
      const measure = parts[1];
      return `${this.$t(type)} — ${this.$t(measure)}`;
    },
  },
};
</script>

<style scoped>
.jupiter-chart-card {
  border: 1px solid rgba(255, 111, 0, 0.15);
}

.jupiter-toggle :deep(.v-btn--active) {
  background: rgba(255, 111, 0, 0.15) !important;
  color: #FF6F00 !important;
}

.jupiter-series-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
</style>
