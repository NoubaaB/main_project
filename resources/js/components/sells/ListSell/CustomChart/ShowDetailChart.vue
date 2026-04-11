<template>
  <v-card>
    <v-card-title class="text-h6 shadow-sm text-center">
      <v-btn @click="close" block rounded color="primary" variant="tonal">
        <v-icon class="mr-5">mdi-close-circle-outline</v-icon>
        {{ $t('close') }}
        {{ warehouse_category_operation_name }}
      </v-btn>
    </v-card-title>
    <v-divider></v-divider>
    <v-card-text>
      <v-row>
        <v-col>
          <v-tabs
            v-model="type"
            color="primary"
            height="60"
            grow
            align-tabs="center"
            slider-color="#f78166"
          >
            <v-tab value="day" class="rounded-ts-xl">
              <v-icon start>mdi-calendar-today</v-icon>
              {{ $t("daily") }}
            </v-tab>
            <v-tab value="month">
              <v-icon start>mdi-calendar-month</v-icon>
              {{ $t("monthly") }}
            </v-tab>
            <v-tab value="year" class="rounded-te-xl">
              <v-icon start>mdi-calendar</v-icon>
              {{ $t("yearly") }}
            </v-tab>
          </v-tabs>
        </v-col>
      </v-row>

      <!-- Loading overlay while worker is running -->
        <!-- ✅ AFTER — v-show keeps the element alive, just hides it -->
        <v-row v-show="isWorkerRunning">
        <v-col class="d-flex justify-center align-center" style="min-height:350px">
            <div class="text-center">
            <v-progress-circular indeterminate color="primary" size="64" />
            <div class="mt-3 text-body-2 text-medium-emphasis">{{$t("computing_chart_data")}}</div>
            </div>
        </v-col>
        </v-row>

        <v-row v-show="!isWorkerRunning">
        <v-col>
            <ApexCharts
            type="area"
            :options="chartOptions"
            :series="series"
            height="350"
            />
        </v-col>
        </v-row>
    </v-card-text>
    <v-divider></v-divider>
    <v-card-actions>
      <v-row no-gutters class="text-center mb-2 mt-1">
        <v-col
          :style="{ color: card_detail.color }"
          v-for="(card_detail, index) in card_details"
          :key="index"
        >
          <v-chip :color="card_detail.color">
            <span class="mr-2">
              <v-icon small class="mr-1" :color="`${card_detail.color} lighten-1`">
                {{ card_detail.mdi_icon }}
              </v-icon>
              <span>{{ card_detail.name }}</span>
            </span>
            <strong>
              {{ main.formatDisplayNumber(card_detail.total) }} ({{ card_detail.scale }})
            </strong>
          </v-chip>
        </v-col>
      </v-row>
    </v-card-actions>
  </v-card>
</template>

<script>
import ApexCharts from 'vue3-apexcharts'
import { useMain } from '@/stores/Main'
import { useSell } from '@/stores/Sell'
import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation'
import locals from '@/locales/apexcharts_local.js'
import { useWebWorkerFn } from '@vueuse/core'
import { computed } from 'vue'

// ─────────────────────────────────────────────────────────────────────────────
// Worker function — must be fully self-contained (no closures, no imports).
// All external data is passed as a single plain-object argument.
// lodash, moment and expr-eval are injected via the `dependencies` option below.
// ─────────────────────────────────────────────────────────────────────────────
function workerComputeSeries({
  generateRange,
  type,
  expressions,
  operations,
  langCode,
}) {
  /* global _, moment, exprEval */
  const parser = new exprEval.Parser()
  const fmt =
    type === 'month' ? 'YYYY-MM' : type === 'year' ? 'YYYY' : 'YYYY-MM-DD'

  const data = []

  expressions.forEach((exp) => {
    const total_expression = []

    generateRange.forEach((date) => {
      const variables = []

      exp.variables.forEach((variable) => {
        let items = operations.filter(
          (detail) =>
            moment(detail.date).format(fmt) === moment(date).format(fmt) &&
            detail.sell_category_id === variable.category_id
        )

        if (variable.stockable_type != null) {
          items = items.filter(
            (detail) => detail.stockable_type === variable.stockable_type
          )
        }
        if (variable.stockable_id != null) {
          items = items.filter(
            (detail) => detail.stockable_id === variable.stockable_id
          )
        }

        const total = _.sumBy(items, variable.field)
        variables.push({ [variable.alias]: total })
      })

      const params = Object.assign({}, ...variables)
      const total_value = parser.evaluate(exp.expression, params)

      const default_return = ['line', 'area'].includes(exp.chart_config.chart_type)
        ? 0.0
        : null

      total_expression.push(Number.isNaN(total_value) ? default_return : +total_value)
    })

    let total_data = 0.0
    if (exp.chart_config.use_avg) {
      const nonZero = total_expression.filter((val) => val > 0).length
      total_data = +(_.sum(total_expression) / (nonZero > 0 ? nonZero : 1))
    } else {
      total_data = +_.sum(total_expression)
    }

    data.push({
      name: exp.chart_config[`name_${langCode}`],
      type: exp.chart_config.chart_type,
      data: total_expression,
      total_data,
      mdi_icon: exp.chart_config.mdi_icon,
      mdi_icon_color: exp.chart_config.chart_color,
      scale: exp.chart_config.scale,
    })
  })

  return data
}

export default {
  name: 'WarehouseCategoryChart',

  components: { ApexCharts },

  props: {
    formula: Object,
    card_details: Array,
  },

  // ── setup: initialise the web-worker once and expose it to Options API ──
  setup() {
    const { workerFn, workerStatus, workerTerminate } = useWebWorkerFn(
      workerComputeSeries,
      {
        // CDN urls are injected with importScripts() inside the worker.
        // Adjust versions to match what your project already bundles.
        dependencies: [
          'https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js',
          'https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js',
          'https://cdn.jsdelivr.net/npm/expr-eval@2.0.2/dist/bundle.min.js',
        ],
      }
    )

    const isWorkerRunning = computed(() => workerStatus.value === 'RUNNING')

    return { workerFn, workerStatus, workerTerminate, isWorkerRunning }
  },

  data() {
    return {
      type: 'day',
      tooltip_date_format: 'DD/MM/YYYY',
      series: [],
    }
  },

  watch: {
    // Re-compute series whenever the time granularity tab changes
    type: {
      handler(val) {
        const formats = { day: 'DD/MM/YYYY', month: 'MM/YYYY', year: 'YYYY' }
        this.tooltip_date_format = formats[val] ?? 'DD/MM/YYYY'
        this.getDataSeries()
      },
    },

    // Re-compute if the underlying operations change (e.g. after a store fetch)
    'sell.get_fetched_operations': {
      handler() { this.getDataSeries() },
      deep: false,   // the store returns a new array reference on update
    },
  },

  computed: {
    main: () => useMain(),
    sell: () => useSell(),
    warehouse_category_operation: () => useWarehouseCategoryOperation(),

    warehouse_category_operation_name() {
      return this.formula[`name_${this.main.lang.code}`]
    },

    generateRange() {
      const ops = this.sell.get_fetched_operations
      const firstDate = _.minBy(ops, (o) => new Date(o.date))?.date
      const latestDate = _.maxBy(ops, (o) => new Date(o.date))?.date
      const s = moment.utc(firstDate)
      const e = moment.utc(latestDate)
      if (!s.isValid() || !e.isValid() || s.isAfter(e)) return []

      const fmt =
        this.type === 'month' ? 'YYYY-MM' :
        this.type === 'year'  ? 'YYYY'    : 'YYYY-MM-DD'
      const unit =
        this.type === 'month' ? 'months' :
        this.type === 'year'  ? 'years'  : 'days'

      const out = []
      for (
        let m = s.clone().startOf(this.type);
        m.isSameOrBefore(e.clone().endOf(this.type));
        m.add(1, unit)
      ) {
        out.push(m.format(fmt))
      }
      return out
    },

    yaxisOptions() {
      return this.formula.expressions
        .filter((exp) => exp.chart_config.display_yaxis)
        .map((exp) => ({
          opposite: exp.chart_config.opposite === 1,
          show: true,
          axisTicks: { show: true },
          axisBorder: { show: true, color: exp.chart_config.chart_color },
          labels: {
            style: { colors: exp.chart_config.chart_color },
            formatter: (value) => this.main.formatDisplayNumber(value),
          },
          title: {
            text: exp.chart_config.scale,
            style: { color: exp.chart_config.chart_color },
          },
          tooltip: { enabled: true },
        }))
    },

    chartOptions() {
      return {
        chart: {
          type: 'line',
          locales: Object.entries(locals).map(([, value]) => value),
          defaultLocale: this.main.lang.short,
          height: 350,
          background: 'transparent',
          stacked: false,
        },
        labels: this.generateRange,
        theme: { mode: this.main.mode },
        tooltip: {
          enabled: true,
          theme: this.main.mode,
          x: {
            show: true,
            formatter: (val) =>
              this.main.formateDateCustom(val, this.tooltip_date_format),
          },
          y: {
            formatter: (val, { seriesIndex }) =>
              `${this.main.formatDisplayNumber(val)} <small>${this.series[seriesIndex]?.scale ?? ''}</small>`,
          },
        },
        dataLabels: { enabled: false },
        stroke: { width: [0, 2, 3], curve: 'smooth' },
        plotOptions: { bar: { columnWidth: '50%' } },
        fill: {
          opacity: [1, 0.25, 1],
          gradient: {
            inverseColors: false,
            shade: 'light',
            type: 'vertical',
            opacityFrom: 1,
            opacityTo: 1,
            stops: [100, 100, 100],
          },
        },
        markers: { size: 0 },
        legend: { position: 'top', horizontalAlign: 'left' },
        xaxis: { type: 'datetime' },
        yaxis: this.yaxisOptions,
      }
    },
  },

  mounted() {
    // Kick off the initial computation as soon as the component is ready
    this.getDataSeries()
  },

  methods: {
    close() {
      this.$emit('closeDialog')
    },

    formatCurrency(v) {
      return `${this.main.formatDisplayNumber(v)} ${this.main.currency}`
    },

    // ── Offloads the heavy loop to the Web Worker ──────────────────────────
async getDataSeries() {
  if (this.generateRange.length === 0) {
    this.series = []
    return
  }

  if (this.isWorkerRunning) {
    this.workerTerminate('PENDING')
  }

  try {
    // ✅ JSON round-trip strips Proxy/reactive wrappers so postMessage can clone the data
    const result = await this.workerFn({
      generateRange: JSON.parse(JSON.stringify(this.generateRange)),
      type: this.type,
      expressions: JSON.parse(JSON.stringify(this.formula.expressions)),
      operations: JSON.parse(JSON.stringify(this.sell.get_fetched_operations)),
      langCode: this.main.lang.code,
    })

    if (result !== undefined) {
      this.series = result
    }
  } catch (err) {
    console.error('[WebWorker] getDataSeries failed:', err)
    this.series = []
  }
},
  },
}
</script>

<style lang="scss" scoped></style>