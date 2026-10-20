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
      <div class="chart-empty-icon">
        <v-icon size="36" color="#0d9488">mdi-chart-box-outline</v-icon>
      </div>
      <div class="text-subtitle-2 font-weight-medium mt-3 text-high-emphasis">{{ $t('no_data_available') || 'Aucune donnée disponible' }}</div>
      <span class="text-caption text-medium-emphasis mt-1">{{ $t('adjust_filters_hint') || 'Configurez vos séries et horizons temporels' }}</span>
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
    pieDonutOverride() {
      for (const s of this.series) {
        const sType = s.type || s.series_type;
        if (sType === 'pie' || sType === 'donut') return sType;
      }
      return null;
    },

    effectiveType() {
      if (this.pieDonutOverride) return this.pieDonutOverride;
      const t = this.chartConfig.chart_type || 'bar';
      if (this.isMixedChart) return 'line';
      if (t === 'mixed') return 'line';
      if (t === 'bar') return 'bar';
      if (t === 'radar') return 'radar';
      return t;
    },

    isMixedChart() {
      if (this.pieDonutOverride) return false;
      const chartType = this.chartConfig.chart_type || 'bar';
      if (chartType === 'mixed') return true;
      if (['pie', 'donut', 'radar', 'heatmap'].includes(chartType)) return false;

      const mainSeriesType = chartType === 'bar' ? 'column' : chartType;

      return this.series.some(s => {
        const sType = s.type || s.series_type || mainSeriesType;
        if (sType === 'pie' || sType === 'donut') return false;
        return sType !== mainSeriesType;
      });
    },

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
      if (['pie', 'donut'].includes(this.chartConfig.chart_type) || this.pieDonutOverride) {
        const pieSeries = this.series.find(s => ['pie', 'donut'].includes(s.type || s.series_type)) || this.series[0];
        return pieSeries ? (pieSeries.data || []) : [];
      }

      const chartType = this.chartConfig.chart_type || 'bar';
      const mainSeriesType = chartType === 'bar' ? 'column' : chartType;

      return this.series.map(s => {
        const seriesType = s.type || s.series_type || mainSeriesType;
        return {
          name: s.name,
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
          toolbar: {
            show: true,
            tools: { download: true, selection: false, zoom: false, pan: false }
          },
          background: 'transparent',
          fontFamily: "'Inter', sans-serif",
          foreColor: isDark ? 'rgba(255, 255, 255, 0.7)' : '#475569',
          dropShadow: {
            enabled: false
          }
        },
        colors: colors.length ? colors : ['#0d9488', '#2563eb', '#059669', '#d97706', '#7c3aed', '#0284c7', '#e11d48', '#475569'],
        stroke: {
          width: this.effectiveType === 'line' || this.chartConfig.chart_type === 'area' ? 3 : (this.isMixedChart ? 2 : 1),
          curve: 'smooth'
        },
        grid: {
          borderColor: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)',
          strokeDashArray: 4,
          padding: { top: 0, right: 12, bottom: 0, left: 12 }
        },
        xaxis: {
          categories: this.categories,
          labels: {
            style: {
              fontSize: '11px',
              fontWeight: 500,
              colors: isDark ? 'rgba(255, 255, 255, 0.6)' : '#64748b'
            }
          },
          axisBorder: {
            color: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.08)'
          },
          axisTicks: {
            color: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.08)'
          }
        },
        yaxis: this.buildYAxis(isDark),
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          style: { fontSize: '12px', fontFamily: "'Inter', sans-serif" },
          y: {
            formatter: (v, { seriesIndex }) => {
              const unit = this.series[seriesIndex]?.measure_unit || 'Kg';
              return this.formatNumber(v, unit);
            },
          },
        },
        dataLabels: { enabled: false },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          fontSize: '12px',
          fontWeight: 600,
          markers: { radius: 12, offsetX: -2 },
          itemMargin: { horizontal: 10, vertical: 4 }
        },
        fill: {
          type: this.chartConfig.chart_type === 'area' ? 'gradient' : 'solid',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [0, 95, 100]
          },
          opacity: this.chartConfig.chart_type === 'area' ? 0.8 : 1
        },
        plotOptions: {
          bar: {
            borderRadius: 6,
            columnWidth: '55%',
            borderRadiusApplication: 'end'
          },
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
            pie: {
              donut: {
                size: '60%',
                labels: {
                  show: true,
                  total: {
                    show: true,
                    fontSize: '15px',
                    fontWeight: 700,
                    color: isDark ? '#fff' : '#0f172a'
                  }
                }
              }
            },
          };
        }
        delete base.xaxis;
        delete base.yaxis;
        delete base.grid;
        delete base.stroke;
      }

      // Radar options
      if (this.chartConfig.chart_type === 'radar') {
        base.plotOptions = {
          radar: {
            polygons: {
              strokeColors: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)',
              connectorColors: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)',
            }
          }
        };
      }

      return base;
    },
  },
  methods: {
    buildYAxis(isDark) {
      const unitMap = this.unitAxisMap;
      const units = Object.keys(unitMap);

      if (units.length <= 1) {
        const unit = units[0] || '';
        return {
          labels: {
            style: {
              fontSize: '11px',
              colors: isDark ? 'rgba(255, 255, 255, 0.6)' : '#64748b'
            },
            formatter: (v) => this.formatNumber(v, unit),
          },
          title: {
            text: unit,
            style: {
              fontSize: '12px',
              fontWeight: 600,
              color: isDark ? 'rgba(255, 255, 255, 0.7)' : '#475569'
            }
          },
        };
      }

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
          title: isFirst ? {
            text: unit,
            style: {
              fontSize: '12px',
              fontWeight: 600,
              color: isDark ? 'rgba(255, 255, 255, 0.7)' : '#475569'
            }
          } : { text: '' },
          labels: {
            style: {
              fontSize: '11px',
              colors: isDark ? 'rgba(255, 255, 255, 0.6)' : '#64748b'
            },
            formatter: (v) => this.formatNumber(v, unit),
          },
          show: isFirst,
          _unit: unit,
        });
      }

      return yaxisArr;
    },

    formatNumber(val, unit = '') {
      if (val === null || val === undefined) return '0';
      const num = parseFloat(val);
      if (isNaN(num)) return val;
      const suffix = unit ? ` ${unit}` : '';
      const formatted = num.toLocaleString('fr-FR', {
        minimumFractionDigits: num % 1 !== 0 ? 2 : 0,
        maximumFractionDigits: 2
      }).replace(/\u202F/g, ' ').replace(',', '.');
      if (unit === '%') return formatted + '%';
      return formatted + suffix;
    },
  },
};
</script>

<style scoped>
.chart-preview-wrap {
  width: 100%;
  min-height: 220px;
}
.chart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 240px;
  padding: 32px 16px;
  border-radius: 16px;
  border: 1px dashed rgba(var(--v-theme-on-surface), 0.12);
  background: rgba(var(--v-theme-on-surface), 0.01);
}
.chart-empty-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: rgba(13, 148, 136, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
