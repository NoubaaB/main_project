<template>
  <v-card
    class="mini-card ma-1 rounded-xl"
    elevation="6"
    :style="cardStyle"
    :disabled="!data"
    :color="!data ? `grey-${main.mode}ten-5` : ''"
    @click="openDialog"
    :loading="charge.loading"
  >
    <template v-slot:loader="{ isActive }">
      <v-progress-linear v-if="isActive && !!data" color="info" indeterminate />
    </template>

    <div class="card-inner d-flex">
      <!-- LEFT: KPIs -->
      <div style="flex:1; min-width:0;">
        <div class="kpi-master">
          <div
            v-for="kpi in kpiRows"
            :key="kpi.key"
            class="kpi-row d-flex align-center justify-space-between"
            :class="{ 'disabled-text': !kpi.enabled }"
          >
            <div class="d-flex align-center">
              <v-icon small class="mr-1" :color="kpi.iconColor">{{ kpi.icon }}</v-icon>
              <div class="kpi-label">{{ kpi.label }}</div>
            </div>
            <div class="kpi-value">{{ kpi.value }} <small>{{ kpi.unit }}</small></div>
          </div>
        </div>
        <v-divider color="info" />
        <div class="mt-1">
          <h4 class="d-inline">{{ operationName }}</h4>
          <sub><small>({{ entriesCount }} {{ $t("entries_short") }})</small></sub>
        </div>
      </div>

      <!-- RIGHT: sparkline -->
      <div style="width:130px; display:flex; align-items:center; justify-content:center;">
        <ApexCharts
          type="area"
          :options="sparkOpts"
          :series="sparkSeries"
          :height="90"
          :width="130"
        />
      </div>
    </div>
  </v-card>

  <!-- Lazy-mounted dialog -->
  <v-dialog v-model="dialog" max-width="1300" max-height="700">
    <ShowDetailChart
      v-if="dialog"
      @closeDialog="dialog = false"
      :summary="kpiSummary"
      :details="data.grouped_details"
    />
  </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useCharge } from '@/stores/Charge';
import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';
import ApexCharts from 'vue3-apexcharts';
import ShowDetailChart from './ShowDetailChart.vue';

// Pre-built placeholder sparkline data (stable — no random re-renders)
const PLACEHOLDER_POINTS = Array.from({ length: 10 }, (_, i) => ({
  date: `2024-01-${String(i + 1).padStart(2, '0')}`,
  total_price: ((i * 7 + 3) % 11) * 10,
  total_weight: ((i * 5 + 2) % 9) * 10,
  unit_price: ((i * 3 + 1) % 7) * 2,
}));

const DEFAULT_SUMMARY = Object.freeze({
  total_price: 0,
  total_weight: 0,
  total_package_count: 0,
  unit_price: 0,
});

export default {
  components: { ApexCharts, ShowDetailChart },
  props: {
    data: { type: Object, default: null },
    id:   { type: Number, required: true },
    date: { type: Object, default: null },
  },
  data: () => ({ dialog: false }),
  computed: {
    main:   () => useMain(),
    charge: () => useCharge(),
    wco:    () => useWarehouseCategoryOperation(),

    ability() {
      return this.wco.getList.find(e => e.id === this.id) || {};
    },
    operationName() {
      return this.wco.getName(this.id);
    },
    entriesCount() {
      return this.charge.countWarehouseCategoryOperationUsage(this.id);
    },

    // ----- summary (NO side-effects) -----
    kpiSummary() {
      return this.data || DEFAULT_SUMMARY;
    },

    // ----- KPI rows (data-driven, no template repetition) -----
    kpiRows() {
      const s = this.kpiSummary;
      const a = this.ability;
      const m = this.main;
      const fv = (v, ok) => (ok ? v : 0);
      return [
        { key: 'price',   icon: 'mdi-cash',                  iconColor: 'green lighten-1',  label: this.$t('price'),     value: m.formatDisplayNumber(fv(s.total_price, a.allow_price)),             unit: m.currency,            enabled: !!a.allow_price },
        { key: 'weight',  icon: 'mdi-weight-kilogram',        iconColor: 'info lighten-1',   label: this.$t('weight'),    value: m.formatDisplayNumber(fv(s.total_weight, a.allow_weight)),            unit: 'Kg',                  enabled: !!a.allow_weight },
        { key: 'boxes',   icon: 'mdi-package-variant-closed',  iconColor: 'purple lighten-1', label: this.$t('boxes'),     value: m.formatDisplayNumberNonFloat(fv(s.total_package_count, a.allow_package_type)), unit: 'Un',   enabled: !!a.allow_package_type },
        { key: 'avg',     icon: 'mdi-percent',                 iconColor: 'orange lighten-1', label: this.$t('avg_price'), value: m.formatDisplayNumber(fv(s.unit_price, a.allow_price && a.allow_weight)),       unit: `${m.currency}/Kg`,    enabled: !!(a.allow_price && a.allow_weight) },
      ];
    },

    // ----- sparkline data (stable placeholder OR real) -----
    points() {
      return this.data ? this.data.grouped_details : PLACEHOLDER_POINTS;
    },

    // Pre-extract numeric arrays ONCE (avoid 3× .map per render)
    _prices()  { return this.points.map(d => +d.total_price  || 0); },
    _weights() { return this.points.map(d => +d.total_weight || 0); },
    _avgs()    { return this.points.map(d => +d.unit_price   || 0); },
    _dates()   { return this.points.map(d => d.date); },

    sparkSeries() {
      const a = this.ability;
      const s = [];
      if (a.allow_price)  s.push({ name: this.$t('price'),  type: 'column', scale: this.main.currency,                  data: this._prices });
      if (a.allow_weight) s.push({ name: this.$t('weight'), type: 'area',   scale: 'Kg',                                data: this._weights });
      if (a.allow_price && a.allow_weight) s.push({ name: this.$t('avg_price'), type: 'line', scale: `${this.main.currency}/Kg`, data: this._avgs });
      return s;
    },

    sparkColors() {
      const a = this.ability;
      const c = [];
      if (a.allow_price)                   c.push('#00e396');
      if (a.allow_weight)                  c.push('#2196F3');
      if (a.allow_price && a.allow_weight) c.push('#feb019');
      return c;
    },

    sparkOpts() {
      const series = this.sparkSeries;
      return {
        chart: {
          type: 'area',
          background: 'transparent',
          sparkline: { enabled: true },
          toolbar: { show: false },
          animations: { enabled: true, easing: 'easeout', speed: 400 },
        },
        theme: { mode: this.main.mode },
        labels: this._dates,
        stroke: { width: [0, 2, 3], curve: 'smooth', dashArray: [0, 0, 5] },
        fill: {
          type: 'line',
          gradient: { shadeIntensity: 20, inverseColors: false, opacityFrom: 1, opacityTo: 1, stops: [0, 80, 100] },
        },
        colors: this.sparkColors,
        markers: { size: 0 },
        grid: { show: false },
        xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: series.map((_, i) => ({ show: false, opposite: i === 1 })),
        legend: { show: false },
        tooltip: {
          enabled: true,
          theme: this.main.mode,
          x: { show: true, formatter: val => this.main.formateDate(this._dates[val - 1]) },
          y: { formatter: (val, { seriesIndex }) => `${this.main.formatDisplayNumber(val)} <small>${series[seriesIndex]?.scale || ''}</small>` },
        },
      };
    },

    cardStyle() {
      return { width: '32.5%', height: '150px', padding: '8px', 'padding-top': '21px', overflow: 'hidden' };
    },
  },
  methods: {
    openDialog() {
      if (this.data) this.dialog = true;
    },
  },
};
</script>

<style scoped>
.mini-card { display: flex; align-items: stretch; }
.card-inner { width: 100%; height: 100%; padding: 4px 6px; box-sizing: border-box; }
.kpi-row { font-size: 13px; line-height: 1; margin-bottom: 6px; }
.kpi-row .kpi-label { font-size: 13px; margin-left: 4px; }
.kpi-value { font-weight: 700; font-size: 13px; min-width: 60px; text-align: right; }
.kpi-master { height: 80% !important; }
.disabled-text { color: #9e9e9e; text-decoration: line-through; opacity: 0.7; cursor: not-allowed; user-select: none; }
.v-icon { font-size: 14px; }
</style>
