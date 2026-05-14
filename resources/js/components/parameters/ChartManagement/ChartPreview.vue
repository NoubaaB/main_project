<template>
  <div class="chart-preview-wrap">
    <apexchart
      v-if="computedOptions && computedSeries.length"
      :type="effectiveType"
      :height="height"
      :options="computedOptions"
      :series="computedSeries"
    />
    <div v-else class="chart-empty">
      <v-icon size="48" color="#00BCD4" class="mb-2">mdi-chart-box-outline</v-icon>
      <span class="text-medium-emphasis">{{ $t('no_data_available') }}</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ChartPreview',
  props: {
    chartConfig: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    height: { type: [String, Number], default: 350 },
  },
  computed: {
    /**
     * Check if any series is a pie/donut type.
     * Pie/donut overrides the entire chart since ApexCharts can't mix pie with cartesian types.
     */
    pieDonutOverride() {
      for (const s of this.series) {
        const sType = s.type || s.series_type;
        if (sType === 'pie' || sType === 'donut') return sType;
      }
      return null;
    },

    effectiveType() {
      // Pie/donut series override everything
      if (this.pieDonutOverride) return this.pieDonutOverride;
      const t = this.chartConfig.chart_type || 'bar';
      // If any series has a different type from the main chart type, treat as mixed
      if (this.isMixedChart) return 'line';
      if (t === 'mixed') return 'line';
      if (t === 'bar') return 'bar';
      return t;
    },

    /**
     * Detect if the chart should behave as mixed:
     * - Explicitly set to 'mixed', OR
     * - Series have heterogeneous types (e.g. one is 'column', another is 'line')
     */
    isMixedChart() {
      if (this.pieDonutOverride) return false;
      const chartType = this.chartConfig.chart_type || 'bar';
      if (chartType === 'mixed') return true;
      if (['pie', 'donut', 'radar', 'heatmap'].includes(chartType)) return false;

      // Map the main chart_type to the equivalent series type
      // ApexCharts uses 'bar' at chart level but 'column' at series level
      const mainSeriesType = chartType === 'bar' ? 'column' : chartType;

      // Check if any series has a type that differs from the main chart type
      // Exclude pie/donut from this check — they are handled separately
      return this.series.some(s => {
        const sType = s.type || s.series_type || mainSeriesType;
        if (sType === 'pie' || sType === 'donut') return false;
        return sType !== mainSeriesType;
      });
    },

    /**
     * Build a map of unique measure_units → yaxis index.
     * Example: { 'Kg': 0, '%': 1 }
     */
    unitAxisMap() {
      const map = {};
      let idx = 0;
      for (const s of this.series) {
        const unit = s.measure_unit || 'Kg';
        if (!(unit in map)) {
          map[unit] = idx++;
        }
      }
      return map;
    },

    hasMultiAxis() {
      return Object.keys(this.unitAxisMap).length > 1;
    },

    computedSeries() {
      // Pie/donut from chart_type OR from series-level override
      if (['pie', 'donut'].includes(this.chartConfig.chart_type) || this.pieDonutOverride) {
        // Use the first pie/donut series, or fallback to first series
        const pieSeries = this.series.find(s => ['pie', 'donut'].includes(s.type || s.series_type)) || this.series[0];
        return pieSeries ? (pieSeries.data || []) : [];
      }

      const chartType = this.chartConfig.chart_type || 'bar';
      const mainSeriesType = chartType === 'bar' ? 'column' : chartType;

      return this.series.map(s => {
        const seriesType = s.type || s.series_type || mainSeriesType;
        return {
          name: s.name,
          // Always pass type when chart is mixed (explicit or auto-detected)
          type: this.isMixedChart ? seriesType : undefined,
          data: s.data || [],
        };
      });
    },

    computedOptions() {
      const isDark = this.$vuetify?.theme?.current?.dark ?? false;
      const opts = this.chartConfig.chart_options || {};
      const colors = this.series.map(s => s.color).filter(Boolean);

      const base = {
        chart: {
          toolbar: { show: true, tools: { download: true, selection: false, zoom: false, pan: false } },
          background: 'transparent',
          fontFamily: 'Inter, sans-serif',
          foreColor: isDark ? '#ccc' : '#555',
        },
        colors: colors.length ? colors : ['#00BCD4', '#42A5F5', '#66BB6A', '#FFA726', '#AB47BC', '#EF5350', '#26C6DA', '#8D6E63'],
        stroke: { width: this.effectiveType === 'line' || this.chartConfig.chart_type === 'area' ? 3 : (this.isMixedChart ? 2 : 1), curve: 'smooth' },
        grid: { borderColor: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)', strokeDashArray: 4 },
        xaxis: {
          categories: this.categories,
          labels: { style: { fontSize: '11px' } },
        },
        yaxis: this.buildYAxis(isDark),
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          y: {
            formatter: (v, { seriesIndex }) => {
              const unit = this.series[seriesIndex]?.measure_unit || 'Kg';
              return this.formatNumber(v, unit);
            },
          },
        },
        dataLabels: { enabled: false },
        legend: { position: 'top', fontSize: '12px', fontWeight: 600 },
        fill: { opacity: this.chartConfig.chart_type === 'area' ? 0.3 : 1 },
        plotOptions: {
          bar: { borderRadius: 4, columnWidth: '60%' },
        },
      };

      if (opts.stacked) {
        base.chart = { ...base.chart, stacked: true };
      }

      // Pie/donut labels
      if (['pie', 'donut'].includes(this.chartConfig.chart_type) || this.pieDonutOverride) {
        base.labels = this.categories;
        base.dataLabels = { enabled: true, style: { fontSize: '13px', fontWeight: 600 } };
        base.legend = { position: 'bottom', fontSize: '13px', fontWeight: 500 };
        if (this.pieDonutOverride === 'donut' || this.chartConfig.chart_type === 'donut') {
          base.plotOptions = {
            pie: { donut: { size: '55%', labels: { show: true, total: { show: true, fontSize: '16px', fontWeight: 700 } } } },
          };
        }
        delete base.xaxis;
        delete base.yaxis;
        delete base.grid;
        delete base.stroke;
      }

      return base;
    },
  },
  methods: {
    /**
     * Build yaxis config:
     * - Single unit → single yaxis
     * - Multiple units → array of yaxis objects (left for first, right for others)
     */
    buildYAxis(isDark) {
      const unitMap = this.unitAxisMap;
      const units = Object.keys(unitMap);

      // Single axis (most common case)
      if (units.length <= 1) {
        const unit = units[0] || '';
        return {
          labels: {
            style: { fontSize: '11px' },
            formatter: (v) => this.formatNumber(v, unit),
          },
          title: { text: unit, style: { fontSize: '12px', fontWeight: 600 } },
        };
      }

      // Multi-axis: build one yaxis per unique unit
      // ApexCharts needs one yaxis entry PER SERIES when using multi-axis
      const yaxisArr = [];
      const unitSeen = {};

      for (let i = 0; i < this.series.length; i++) {
        const unit = this.series[i].measure_unit || 'Kg';
        const axisIdx = unitMap[unit];
        const isFirst = !(unit in unitSeen);
        unitSeen[unit] = true;

        yaxisArr.push({
          seriesName: isFirst ? this.series[i].name : yaxisArr.find(a => a._unit === unit)?.seriesName,
          opposite: axisIdx > 0,
          title: isFirst ? { text: unit, style: { fontSize: '12px', fontWeight: 600 } } : { text: '' },
          labels: {
            style: { fontSize: '11px' },
            formatter: (v) => this.formatNumber(v, unit),
          },
          show: isFirst,
          _unit: unit, // internal tracking, ignored by ApexCharts
        });
      }

      return yaxisArr;
    },

    formatNumber(val, unit = '') {
      if (val === null || val === undefined) return '0';
      const num = parseFloat(val);
      if (isNaN(num)) return val;
      const suffix = unit ? ` ${unit}` : '';
      // Format with space as thousands separator, dot as decimal
      const formatted = num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).replace(/\u202F/g, ' ').replace(',', '.');
      if (unit === '%') return formatted + '%';
      return formatted + suffix;
    },
  },
};
</script>

<style scoped>
.chart-preview-wrap { width: 100%; min-height: 200px; }
.chart-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; opacity: 0.5; }
</style>
