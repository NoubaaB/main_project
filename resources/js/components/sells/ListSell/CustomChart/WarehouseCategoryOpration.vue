<template>
  <v-card
    class="mini-card ma-1 rounded-xl"
    elevation="6"
    :style="cardStyle"
    :disabled="!hasData"
    :color="!hasData ? `grey-${main.mode}ten-5` : ''"
    @click="openDialog"
    :loading="sell.loading"
  >
    <template v-slot:loader="{ isActive }">
      <v-progress-linear v-if="isActive && hasData" color="info" indeterminate />
    </template>

    <div class="card-inner d-flex">
      <div style="flex:1; min-width:0;">
        <div class="kpi-master">
          <div
            v-for="(kpi, i) in cardDetails"
            :key="i"
            class="kpi-row d-flex align-center justify-space-between"
            :style="{ color: kpi.color }"
          >
            <div class="d-flex align-center">
              <v-icon small class="mr-1" :color="kpi.color">{{ kpi.icon }}</v-icon>
              <div class="kpi-label" style="color: initial">{{ kpi.name }}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumber(kpi.total) }} <small style="color: initial">{{ kpi.scale }}</small></div>
          </div>
        </div>
        <v-divider color="info" />
        <div class="mt-1">
          <h4 class="d-inline">{{ formulaName }}</h4>
        </div>
      </div>

      <div style="width:130px; display:flex; align-items:center; justify-content:center;">
        <ApexCharts type="area" :options="sparkOpts" :series="sparkSeries" :height="90" :width="130" />
      </div>
    </div>
  </v-card>

  <v-dialog v-model="dialog" max-width="1300" max-height="700">
    <ShowDetailChart
      v-if="dialog"
      @closeDialog="dialog = false"
      :card_details="cardDetails"
      :details="computedSeries"
      :formula="formula"
    />
  </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useSell } from '@/stores/Sell';
import ApexCharts from 'vue3-apexcharts';
import ShowDetailChart from './ShowDetailChart.vue';
import { Parser } from 'expr-eval';
import _ from 'lodash';

const parser = new Parser();

export default {
  components: { ApexCharts, ShowDetailChart },
  props: {
    formula: { type: Object, required: true },
    date:    { type: Object, default: null },
  },
  data: () => ({ dialog: false }),
  computed: {
    main: () => useMain(),
    sell: () => useSell(),
    formulaName() { return this.formula[`name_${this.main.lang.code}`]; },

    opsByDate() { return _.groupBy(this.sell.get_fetched_operations, 'date'); },
    dateLabels() { return _.uniq(_.map(this.sell.get_fetched_operations, 'date')); },
    hasData() { return this.dateLabels.length > 0; },

    computedSeries() {
      if (!this.hasData) return [];
      const labels = this.dateLabels, byDate = this.opsByDate, lang = this.main.lang.code;
      return this.formula.expressions.map(exp => {
        const totals = [], gVars = [];
        for (let d = 0; d < labels.length; d++) {
          const bucket = byDate[labels[d]] || [], vars = [];
          for (let v = 0; v < exp.variables.length; v++) {
            const vr = exp.variables[v];
            let items = bucket.filter(det => det.sell_category_id === vr.category_id);
            if (vr.stockable_type != null) items = items.filter(det => det.stockable_type === vr.stockable_type);
            if (vr.stockable_id != null)   items = items.filter(det => det.stockable_id === vr.stockable_id);
            const total = _.sumBy(items, vr.field);
            vars.push({ [vr.alias]: total });
            gVars.push({ [vr.alias]: total });
          }
          const params = Object.assign({}, ...vars);
          const val = parser.evaluate(exp.expression, params);
          const fallback = ['line', 'area'].includes(exp.chart_config.chart_type) ? 0 : null;
          totals.push(Number.isNaN(val) ? fallback : +val);
        }
        const gResult = _(gVars).flatMap(_.entries).groupBy(0).map((items, key) => ({ [key]: _.sumBy(items, i => Number(i[1]) || 0) })).value();
        const gParams = Object.assign({}, ...gResult);
        const totalData = parser.evaluate(exp.expression, gParams);
        return {
          name: exp.chart_config[`name_${lang}`], type: exp.chart_config.chart_type, data: totals,
          total_data: Number.isNaN(totalData) ? 0 : +totalData,
          mdi_icon: exp.chart_config.mdi_icon, mdi_icon_color: exp.chart_config.chart_color, scale: exp.chart_config.scale,
        };
      });
    },

    cardDetails() {
      return this.computedSeries.map(s => ({ name: s.name, icon: s.mdi_icon, total: s.total_data, color: s.mdi_icon_color, scale: s.scale }));
    },

    sparkSeries() { return this.computedSeries; },
    sparkColors() { return this.formula.expressions.map(exp => exp.chart_config.chart_color); },

    sparkOpts() {
      const series = this.sparkSeries;
      return {
        chart: { type: 'line', background: 'transparent', sparkline: { enabled: true }, toolbar: { show: false }, animations: { enabled: true, easing: 'easeout', speed: 400 } },
        theme: { mode: this.main.mode },
        labels: this.dateLabels,
        stroke: { curve: 'smooth' },
        fill: { type: 'line', gradient: { shadeIntensity: 20, inverseColors: false, opacityFrom: 1, opacityTo: 1 } },
        colors: this.sparkColors,
        markers: { size: 0 },
        grid: { show: false },
        xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: series.map(() => ({ show: false })),
        legend: { show: false },
        tooltip: {
          enabled: true, theme: this.main.mode,
          x: { show: true, formatter: val => this.main.formateDate(this.dateLabels[val - 1]) },
          y: { formatter: (val, { seriesIndex }) => `${this.main.formatDisplayNumber(val)} <small>${series[seriesIndex]?.scale || ''}</small>` },
        },
      };
    },

    cardStyle() { return { width: '32.5%', height: '150px', padding: '8px', 'padding-top': '21px', overflow: 'hidden' }; },
  },
  methods: {
    openDialog() { if (this.hasData) this.dialog = true; },
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
.v-icon { font-size: 14px; }
</style>
