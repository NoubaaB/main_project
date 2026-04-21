<template>
  <v-card>
    <v-card-title class="text-h6 shadow-sm text-center">
      <v-btn @click="$emit('closeDialog')" block rounded color="primary" variant="tonal">
        <v-icon class="mr-5">mdi-close-circle-outline</v-icon>
        {{ $t('close') }} {{ formulaName }}
      </v-btn>
    </v-card-title>

    <v-divider />

    <v-card-text>
      <v-row>
        <v-col>
          <v-tabs v-model="type" color="primary" height="60" grow align-tabs="center" slider-color="#f78166">
            <v-tab value="day"   class="rounded-ts-xl"><v-icon start>mdi-calendar-today</v-icon>  {{ $t("daily") }}</v-tab>
            <v-tab value="month">                       <v-icon start>mdi-calendar-month</v-icon> {{ $t("monthly") }}</v-tab>
            <v-tab value="year"  class="rounded-te-xl"><v-icon start>mdi-calendar</v-icon>        {{ $t("yearly") }}</v-tab>
          </v-tabs>
        </v-col>
      </v-row>

      <v-row v-show="isWorkerRunning">
        <v-col class="d-flex justify-center align-center" style="min-height:350px">
          <div class="text-center">
            <v-progress-circular indeterminate color="primary" size="64" />
            <div class="mt-3 text-body-2 text-medium-emphasis">{{ $t("computing_chart_data") }}</div>
          </div>
        </v-col>
      </v-row>

      <v-row v-show="!isWorkerRunning">
        <v-col>
          <ApexCharts type="area" :options="chartOptions" :series="series" height="350" />
        </v-col>
      </v-row>
    </v-card-text>

    <v-divider />

    <v-card-actions>
      <v-row no-gutters class="text-center mb-2 mt-1">
        <v-col v-for="(kpi, i) in card_details" :key="i" :style="{ color: kpi.color }">
          <v-chip :color="kpi.color">
            <v-icon small class="mr-1" :color="`${kpi.color} lighten-1`">{{ kpi.icon || kpi.mdi_icon }}</v-icon>
            <span class="mr-1">{{ kpi.name }}</span>
            <strong>{{ main.formatDisplayNumber(kpi.total) }} ({{ kpi.scale }})</strong>
          </v-chip>
        </v-col>
      </v-row>
    </v-card-actions>
  </v-card>
</template>

<script>
import ApexCharts from 'vue3-apexcharts';
import _ from 'lodash';
import moment from 'moment';
import { useMain } from '@/stores/Main';
import { useSell } from '@/stores/Sell';
import locals from '@/locales/apexcharts_local.js';
import { useWebWorkerFn } from '@vueuse/core';
import { computed } from 'vue';

const PERIOD_CFG = {
  day:   { fmt: 'YYYY-MM-DD', unit: 'days',   tooltip: 'DD/MM/YYYY' },
  month: { fmt: 'YYYY-MM',    unit: 'months',  tooltip: 'MM/YYYY' },
  year:  { fmt: 'YYYY',       unit: 'years',   tooltip: 'YYYY' },
};

function workerComputeSeries({ generateRange, type, expressions, operations, langCode }) {
  /* global _, moment, exprEval */
  const parser = new exprEval.Parser();
  const fmt = type === 'month' ? 'YYYY-MM' : type === 'year' ? 'YYYY' : 'YYYY-MM-DD';

  // Pre-index operations by formatted date
  const byDate = {};
  for (let i = 0; i < operations.length; i++) {
    const op = operations[i];
    const key = moment(op.date).format(fmt);
    if (!byDate[key]) byDate[key] = [];
    byDate[key].push(op);
  }

  return expressions.map(exp => {
    const totals = [];
    generateRange.forEach(date => {
      const fmtDate = moment(date).format(fmt);
      const bucket = byDate[fmtDate] || [];
      const vars = [];
      exp.variables.forEach(vr => {
        let items = bucket.filter(d => d.sell_category_id === vr.category_id);
        if (vr.stockable_type != null) items = items.filter(d => d.stockable_type === vr.stockable_type);
        if (vr.stockable_id != null)   items = items.filter(d => d.stockable_id === vr.stockable_id);
        vars.push({ [vr.alias]: _.sumBy(items, vr.field) });
      });
      const params = Object.assign({}, ...vars);
      const val = parser.evaluate(exp.expression, params);
      const fallback = ['line', 'area'].includes(exp.chart_config.chart_type) ? 0 : null;
      totals.push(Number.isNaN(val) ? fallback : +val);
    });

    let total_data = 0;
    if (exp.chart_config.use_avg) {
      const nonZero = totals.filter(v => v > 0).length;
      total_data = +(_.sum(totals) / (nonZero > 0 ? nonZero : 1));
    } else {
      total_data = +_.sum(totals);
    }

    return {
      name: exp.chart_config[`name_${langCode}`], type: exp.chart_config.chart_type, data: totals, total_data,
      mdi_icon: exp.chart_config.mdi_icon, mdi_icon_color: exp.chart_config.chart_color, scale: exp.chart_config.scale,
    };
  });
}

export default {
  components: { ApexCharts },
  props: {
    formula:      { type: Object, required: true },
    card_details: { type: Array,  required: true },
  },

  setup() {
    const { workerFn, workerStatus, workerTerminate } = useWebWorkerFn(workerComputeSeries, {
      dependencies: [
        'https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js',
        'https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js',
        'https://cdn.jsdelivr.net/npm/expr-eval@2.0.2/dist/bundle.min.js',
      ],
    });
    const isWorkerRunning = computed(() => workerStatus.value === 'RUNNING');
    return { workerFn, workerStatus, workerTerminate, isWorkerRunning };
  },

  data: () => ({ type: 'day', series: [] }),

  watch: {
    type() { this.computeSeries(); },
    'sell.get_fetched_operations'() { this.computeSeries(); },
  },

  computed: {
    main: () => useMain(),
    sell: () => useSell(),
    formulaName() { return this.formula[`name_${this.main.lang.code}`]; },
    tooltipFmt() { return PERIOD_CFG[this.type].tooltip; },

    dateRange() {
      const ops = this.sell.get_fetched_operations;
      if (!ops?.length) return [];
      const cfg = PERIOD_CFG[this.type];
      const first = _.minBy(ops, o => new Date(o.date))?.date;
      const last  = _.maxBy(ops, o => new Date(o.date))?.date;
      const s = moment.utc(first), e = moment.utc(last);
      if (!s.isValid() || !e.isValid() || s.isAfter(e)) return [];
      const out = [];
      for (let m = s.clone().startOf(this.type); m.isSameOrBefore(e.clone().endOf(this.type)); m.add(1, cfg.unit)) {
        out.push(m.format(cfg.fmt));
      }
      return out;
    },

    yaxis() {
      return this.formula.expressions
        .filter(exp => exp.chart_config.display_yaxis)
        .map(exp => ({
          opposite: exp.chart_config.opposite === 1, show: true,
          axisTicks: { show: true }, axisBorder: { show: true, color: exp.chart_config.chart_color },
          labels: { style: { colors: exp.chart_config.chart_color }, formatter: v => this.main.formatDisplayNumber(v) },
          title: { text: exp.chart_config.scale, style: { color: exp.chart_config.chart_color } },
          tooltip: { enabled: true },
        }));
    },

    chartOptions() {
      return {
        chart: { type: 'line', locales: Object.values(locals), defaultLocale: this.main.lang.short, height: 350, background: 'transparent', stacked: false },
        labels: this.dateRange,
        theme: { mode: this.main.mode },
        tooltip: {
          enabled: true, theme: this.main.mode,
          x: { show: true, formatter: val => this.main.formateDateCustom(val, this.tooltipFmt) },
          y: { formatter: (val, { seriesIndex }) => `${this.main.formatDisplayNumber(val)} <small>${this.series[seriesIndex]?.scale ?? ''}</small>` },
        },
        dataLabels: { enabled: false },
        stroke: { width: [0, 2, 3], curve: 'smooth' },
        plotOptions: { bar: { columnWidth: '50%' } },
        fill: { opacity: [1, 0.25, 1], gradient: { inverseColors: false, shade: 'light', type: 'vertical', opacityFrom: 1, opacityTo: 1, stops: [100, 100, 100] } },
        markers: { size: 0 },
        legend: { position: 'top', horizontalAlign: 'left' },
        xaxis: { type: 'datetime' },
        yaxis: this.yaxis,
      };
    },
  },

  mounted() { this.computeSeries(); },

  methods: {
    async computeSeries() {
      if (!this.dateRange.length) { this.series = []; return; }
      if (this.isWorkerRunning) this.workerTerminate('PENDING');
      try {
        const result = await this.workerFn({
          generateRange: JSON.parse(JSON.stringify(this.dateRange)),
          type: this.type,
          expressions: JSON.parse(JSON.stringify(this.formula.expressions)),
          operations: JSON.parse(JSON.stringify(this.sell.get_fetched_operations)),
          langCode: this.main.lang.code,
        });
        if (result !== undefined) this.series = result;
      } catch (err) {
        console.error('[WebWorker] computeSeries failed:', err);
        this.series = [];
      }
    },
  },
};
</script>

<style lang="scss" scoped></style>