<template>
  <v-card>
    <v-card-title class="text-h6 shadow-sm text-center">
      <v-btn @click="$emit('closeDialog')" block rounded color="primary" variant="tonal">
        <v-icon class="mr-5">mdi-close-circle-outline</v-icon>
        {{ $t('close') }} {{ operationName }}
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
      <v-row>
        <v-col>
          <ApexCharts type="area" :options="chartOptions" :series="series" height="350" />
        </v-col>
      </v-row>
    </v-card-text>

    <v-divider />

    <v-card-actions>
      <v-row no-gutters class="text-center mb-2 mt-1">
        <v-col v-if="ability.allow_price">
          <v-chip color="green">
            <v-icon small class="mr-1" color="green lighten-1">mdi-cash</v-icon>
            <span class="mr-1">{{ $t("price") }}</span>
            <strong>{{ main.formatDisplayNumber(summary.total_price) }} ({{ main.currency }})</strong>
          </v-chip>
        </v-col>
        <v-col v-if="ability.allow_weight">
          <v-chip color="info">
            <v-icon small class="mr-1" color="info lighten-1">mdi-weight-kilogram</v-icon>
            <span class="mr-1">{{ $t("weight") }}</span>
            <strong>{{ main.formatDisplayNumber(summary.total_weight) }} (Kg)</strong>
          </v-chip>
        </v-col>
        <v-col v-if="ability.allow_package_type">
          <v-chip color="purple">
            <v-icon small class="mr-1" color="green lighten-1">mdi-package-variant-closed</v-icon>
            <span class="mr-1">{{ $t("boxes") }}</span>
            <strong>{{ main.formatDisplayNumberNonFloat(summary.total_package_count) }} (Un)</strong>
          </v-chip>
        </v-col>
        <v-col v-if="ability.allow_price && ability.allow_weight">
          <v-chip color="orange">
            <v-icon small class="mr-1" color="orange lighten-1">mdi-percent</v-icon>
            <span class="mr-1">{{ $t("avg_price") }}</span>
            <strong>{{ main.formatDisplayNumber(summary.unit_price) }} {{ main.currency }}/Kg</strong>
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
import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';
import locals from '@/locales/apexcharts_local.js';

const PERIOD_CFG = {
  day:   { fmt: 'YYYY-MM-DD', unit: 'days',   tooltip: 'DD/MM/YYYY' },
  month: { fmt: 'YYYY-MM',    unit: 'months',  tooltip: 'MM/YYYY' },
  year:  { fmt: 'YYYY',       unit: 'years',   tooltip: 'YYYY' },
};
const COLORS = { price: '#00e396', weight: '#2196F3', avg: '#feb019' };

export default {
  components: { ApexCharts },
  props: {
    details: { type: Array,  required: true },
    summary: { type: Object, required: true },
  },
  data: () => ({ type: 'day' }),
  computed: {
    main: () => useMain(),
    wco:  () => useWarehouseCategoryOperation(),

    ability() { return this.wco.getList.find(e => e.id === this.summary.warehouse_category_operation_id) || {}; },
    operationName() { return this.wco.getName(this.summary.warehouse_category_operation_id); },
    tooltipFmt() { return PERIOD_CFG[this.type].tooltip; },

    dateRange() {
      if (!this.details?.length) return [];
      const cfg = PERIOD_CFG[this.type];
      const first = _.minBy(this.details, o => new Date(o.date))?.date;
      const last  = _.maxBy(this.details, o => new Date(o.date))?.date;
      const s = moment.utc(first), e = moment.utc(last);
      if (!s.isValid() || !e.isValid() || s.isAfter(e)) return [];
      const out = [];
      for (let m = s.clone().startOf(this.type); m.isSameOrBefore(e.clone().endOf(this.type)); m.add(1, cfg.unit)) {
        out.push(m.format(cfg.fmt));
      }
      return out;
    },

    groupedByPeriod() {
      const cfg = PERIOD_CFG[this.type];
      return _.groupBy(this.details, item => {
        const m = moment.utc(item.date);
        return m.isValid() ? m.format(cfg.fmt) : '__invalid__';
      });
    },

    seriesArrays() {
      const periods = this.dateRange, grouped = this.groupedByPeriod;
      const prices = [], weights = [], avgs = [];
      for (let i = 0; i < periods.length; i++) {
        const bucket = grouped[periods[i]];
        if (!bucket || !bucket.length) { prices.push(0); weights.push(0); avgs.push(0); continue; }
        let tp = 0, tw = 0, up = 0;
        for (let j = 0; j < bucket.length; j++) {
          tp += (+bucket[j].total_price || 0);
          tw += (+bucket[j].total_weight || 0);
          up += (+bucket[j].unit_price || 0);
        }
        prices.push(tp); weights.push(tw); avgs.push(+(up / bucket.length).toFixed(2));
      }
      return { prices, weights, avgs };
    },

    series() {
      const a = this.ability, s = this.seriesArrays, out = [];
      if (a.allow_price)                   out.push({ name: this.$t('price'),     type: 'column', data: s.prices });
      if (a.allow_weight)                  out.push({ name: this.$t('weight'),    type: 'area',   data: s.weights });
      if (a.allow_price && a.allow_weight) out.push({ name: this.$t('avg_price'), type: 'line',   data: s.avgs });
      return out;
    },

    chartColors() {
      const a = this.ability, c = [];
      if (a.allow_price)                   c.push(COLORS.price);
      if (a.allow_weight)                  c.push(COLORS.weight);
      if (a.allow_price && a.allow_weight) c.push(COLORS.avg);
      return c;
    },

    yaxis() {
      const a = this.ability, m = this.main, fmt = v => m.formatDisplayNumber(v), ax = [];
      if (a.allow_price) ax.push({ seriesName: this.$t('price'), axisTicks: { show: true }, axisBorder: { show: true, color: COLORS.price }, labels: { style: { colors: COLORS.price }, formatter: fmt }, title: { text: this.$t('total_price_on', { currency: m.currency }), style: { color: COLORS.price } }, tooltip: { enabled: true } });
      if (a.allow_weight) ax.push({ seriesName: this.$t('weight'), opposite: true, axisTicks: { show: true }, axisBorder: { show: true, color: COLORS.weight }, labels: { style: { colors: COLORS.weight }, formatter: fmt }, title: { text: this.$t('total_weight_on_kg'), style: { color: COLORS.weight } }, tooltip: { enabled: true } });
      if (a.allow_price && a.allow_weight) ax.push({ seriesName: this.$t('unit_price'), axisTicks: { show: true }, axisBorder: { show: true, color: COLORS.avg }, labels: { style: { colors: COLORS.avg }, formatter: fmt }, title: { text: this.$t('unit_price_on', { currency: m.currency }), style: { color: COLORS.avg } } });
      return ax;
    },

    chartOptions() {
      return {
        chart: { type: 'line', locales: Object.values(locals), defaultLocale: this.main.lang.short, height: 350, stacked: false },
        labels: this.dateRange,
        theme: { mode: this.main.mode },
        tooltip: {
          enabled: true, theme: this.main.mode,
          x: { show: true, formatter: val => this.main.formateDateCustom(val, this.tooltipFmt) },
          y: { formatter: (val, { seriesIndex }) => seriesIndex === 1 ? `${this.main.formatDisplayNumber(val)} kg` : `${this.main.formatDisplayNumber(val)} ${this.main.currency}` },
        },
        colors: this.chartColors,
        dataLabels: { enabled: false },
        stroke: { width: [0, 2, 3], curve: 'smooth', dashArray: [0, 0, 5] },
        plotOptions: { bar: { columnWidth: '50%' } },
        fill: { opacity: [1, 0.25, 1], gradient: { inverseColors: false, shade: 'light', type: 'vertical', opacityFrom: 0.85, opacityTo: 0.55, stops: [100, 100, 100, 100] } },
        markers: { size: 0 },
        legend: { position: 'top', horizontalAlign: 'left' },
        xaxis: { type: 'datetime' },
        yaxis: this.yaxis,
      };
    },
  },
};
</script>

<style lang="scss" scoped></style>