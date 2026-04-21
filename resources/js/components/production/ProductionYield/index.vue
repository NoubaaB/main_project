<template>
  <div :class="['py-page', main.is_dark ? 'py-dark' : 'py-light']">
    <!-- Hero Header -->
    <div class="py-hero">
      <div class="py-hero-bg"></div>
      <div class="py-hero-inner">
        <div class="d-flex align-center ga-3">
          <div class="py-hero-icon">
            <v-icon size="24" color="white">mdi-chart-waterfall</v-icon>
          </div>
          <div>
            <div class="py-hero-title">{{ $t('production_yield') }}</div>
            <div class="py-hero-subtitle">{{ $t('production_yield_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="py-filters">
      <v-row align="center" no-gutters class="ga-3">
        <v-col cols="12" md="3">
          <v-text-field
            v-model="dateFrom"
            :label="$t('date_from')"
            type="date"
            density="compact"
            variant="outlined"
            rounded
            class="text-info"
            color="info"
            hide-details
            @change="loadData"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="dateTo"
            :label="$t('date_to')"
            type="date"
            density="compact"
            variant="outlined"
            rounded
            class="text-info"
            color="info"
            hide-details
            @change="loadData"
          />
        </v-col>
        <v-col cols="auto">
          <v-btn
            color="info"
            variant="elevated"
            rounded
            size="small"
            :loading="loading"
            @click="loadData"
            class="px-5"
          >
            <v-icon start size="16">mdi-magnify</v-icon>
            {{ $t('search') }}
          </v-btn>
        </v-col>
      </v-row>
    </div>

    <!-- Summary Cards -->
    <div class="py-summary" v-if="summary">
      <div class="py-card py-card-fresh">
        <div class="py-card-icon">
          <v-icon size="22" color="#1565C0">mdi-fish</v-icon>
        </div>
        <div class="py-card-body">
          <div class="py-card-label">{{ $t('total_fresh_weight') }}</div>
          <div class="py-card-value">{{ formatWeight(summary.total_fresh_weight) }}</div>
        </div>
      </div>
      <div class="py-card py-card-frozen">
        <div class="py-card-icon">
          <v-icon size="22" color="#00838F">mdi-snowflake</v-icon>
        </div>
        <div class="py-card-body">
          <div class="py-card-label">{{ $t('total_frozen_weight') }}</div>
          <div class="py-card-value">{{ formatWeight(summary.total_frozen_weight) }}</div>
        </div>
      </div>
      <div class="py-card py-card-yield">
        <div class="py-card-icon">
          <v-icon size="22" color="#2E7D32">mdi-percent-outline</v-icon>
        </div>
        <div class="py-card-body">
          <div class="py-card-label">{{ $t('avg_production_yield') }}</div>
          <div class="py-card-value">{{ summary.avg_production_yield }}%</div>
        </div>
      </div>
      <div class="py-card py-card-workforce">
        <div class="py-card-icon">
          <v-icon size="22" color="#E65100">mdi-account-hard-hat</v-icon>
        </div>
        <div class="py-card-body">
          <div class="py-card-label">{{ $t('avg_workforce_yield') }}</div>
          <div class="py-card-value">{{ formatPrice(summary.avg_workforce_yield) }} DH/KG</div>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="py-chart-container">
      <div v-if="loading" class="py-loading">
        <v-progress-circular indeterminate color="info" size="44" width="3" />
        <div class="py-loading-text">{{ $t('loading') }}...</div>
      </div>
      <div v-else-if="!hasData" class="py-empty">
        <v-icon size="56" color="grey">mdi-chart-box-outline</v-icon>
        <div class="py-empty-text">{{ $t('no_data_available') }}</div>
        <div class="py-empty-hint">{{ $t('select_date_range_hint') }}</div>
      </div>
      <div v-else id="production-yield-chart" ref="chartContainer" class="py-chart"></div>
    </div>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';
import axios from 'axios';

export default {
  data() {
    return {
      dateFrom: '',
      dateTo: '',
      loading: false,
      summary: null,
      chartData: null,
      chartInstance: null,
    };
  },
  computed: {
    main() { return useMain(); },
    hasData() {
      return this.chartData && this.chartData.dates && this.chartData.dates.length > 0;
    },
  },
  methods: {
    initDefaultDates() {
      const now = new Date();
      const y = now.getFullYear();
      const m = now.getMonth();
      this.dateFrom = `${y}-${String(m + 1).padStart(2, '0')}-01`;
      const lastDay = new Date(y, m + 1, 0).getDate();
      this.dateTo = `${y}-${String(m + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
    },

    async loadData() {
      if (!this.dateFrom || !this.dateTo) return;
      this.loading = true;
      try {
        const res = await axios.get('/api/production-yield', {
          params: { date_from: this.dateFrom, date_to: this.dateTo },
        });
        this.chartData = res.data;
        this.summary = res.data.summary;
        this.$nextTick(() => this.renderChart());
      } catch (err) {
        console.error('Failed to load production yield:', err);
      } finally {
        this.loading = false;
      }
    },

    renderChart() {
      if (this.chartInstance) {
        this.chartInstance.destroy();
        this.chartInstance = null;
      }
      if (!this.chartData || !this.chartData.dates.length) return;

      const isDark = this.main.is_dark;
      const { dates, series_columns, production_yield, workforce_yield } = this.chartData;

      // Build column series (stacked bars)
      const columnSeries = Object.entries(series_columns).map(([name, data]) => ({
        name,
        type: 'column',
        data,
      }));

      // Build line series
      const lineSeries = [
        {
          name: this.$t('production_yield') + ' (%)',
          type: 'line',
          data: production_yield,
        },
        {
          name: this.$t('workforce_yield') + ' (DH/KG)',
          type: 'line',
          data: workforce_yield,
        },
      ];

      const allSeries = [...columnSeries, ...lineSeries];

      // Y-axis config: columns on left, lines on right
      const yaxis = [];
      // Primary Y-axis for weight
      yaxis.push({
        title: { text: this.$t('weight') + ' (KG)', style: { color: isDark ? '#aaa' : '#555', fontSize: '12px' } },
        labels: {
          formatter: v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : (v || 0).toFixed(0),
          style: { colors: isDark ? '#aaa' : '#555' },
        },
        min: 0,
      });
      // Hide y-axis for remaining column series
      for (let i = 1; i < columnSeries.length; i++) {
        yaxis.push({ show: false, seriesName: columnSeries[0].name });
      }
      // Production yield Y-axis (right)
      yaxis.push({
        opposite: true,
        title: { text: this.$t('production_yield') + ' (%)', style: { color: '#2E7D32', fontSize: '12px' } },
        labels: {
          formatter: v => (v || 0).toFixed(0) + '%',
          style: { colors: '#2E7D32' },
        },
        min: 0,
        max: 100,
      });
      // Workforce yield Y-axis (right)
      yaxis.push({
        opposite: true,
        title: { text: 'DH/KG', style: { color: '#E65100', fontSize: '12px' } },
        labels: {
          formatter: v => (v || 0).toFixed(2),
          style: { colors: '#E65100' },
        },
        min: 0,
      });

      // Color palette: column colors + line colors
      const columnColors = [
        '#1565C0', '#42A5F5', '#0097A7', '#4DD0E1',
        '#7B1FA2', '#BA68C8', '#0288D1', '#81D4FA',
        '#00695C', '#4DB6AC', '#C62828', '#EF5350',
      ];
      const lineColors = ['#2E7D32', '#E65100'];
      const colors = [
        ...columnSeries.map((_, i) => columnColors[i % columnColors.length]),
        ...lineColors,
      ];

      const options = {
        series: allSeries,
        chart: {
          type: 'line',
          height: 500,
          stacked: false,
          toolbar: { show: true, tools: { download: true, zoom: true, pan: true, reset: true } },
          background: 'transparent',
          fontFamily: "'Inter', 'Segoe UI', system-ui, sans-serif",
        },
        plotOptions: {
          bar: {
            columnWidth: '60%',
            borderRadius: 3,
          },
        },
        stroke: {
          width: [...columnSeries.map(() => 0), 3, 3],
          curve: 'smooth',
          dashArray: [...columnSeries.map(() => 0), 0, 5],
        },
        markers: {
          size: [...columnSeries.map(() => 0), 4, 4],
          strokeWidth: 2,
          hover: { sizeOffset: 3 },
        },
        xaxis: {
          categories: dates.map(d => {
            const dt = new Date(d + 'T00:00:00');
            return dt.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
          }),
          labels: {
            style: { colors: isDark ? '#aaa' : '#555', fontSize: '11px' },
            rotate: -45,
            rotateAlways: dates.length > 15,
          },
        },
        yaxis,
        colors,
        fill: {
          opacity: [...columnSeries.map(() => 0.85), 1, 1],
        },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          floating: false,
          fontSize: '12px',
          labels: { colors: isDark ? '#ccc' : '#333' },
          markers: { radius: 3 },
        },
        tooltip: {
          shared: true,
          intersect: false,
          theme: isDark ? 'dark' : 'light',
          y: {
            formatter: (val, { seriesIndex }) => {
              if (val === null || val === undefined) return '-';
              if (seriesIndex < columnSeries.length) {
                return val.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' KG';
              }
              if (seriesIndex === columnSeries.length) {
                return val.toFixed(1) + '%';
              }
              return val.toFixed(2) + ' DH/KG';
            },
          },
        },
        grid: {
          borderColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
          strokeDashArray: 3,
        },
        theme: { mode: isDark ? 'dark' : 'light' },
      };

      // Enable stacking only for column series
      // ApexCharts doesn't natively support mixed stacking so we use a custom group
      options.chart.stacked = true;
      options.chart.stackType = 'normal';

      const el = this.$refs.chartContainer;
      if (el) {
        this.chartInstance = new ApexCharts(el, options);
        this.chartInstance.render();
      }
    },

    formatWeight(val) {
      if (!val) return '0.00 KG';
      return parseFloat(val).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' KG';
    },
    formatPrice(val) {
      if (!val && val !== 0) return '0.00';
      return parseFloat(val).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
  },
  mounted() {
    this.initDefaultDates();
    this.loadData();
  },
  beforeUnmount() {
    if (this.chartInstance) {
      this.chartInstance.destroy();
    }
  },
  watch: {
    'main.is_dark'() {
      if (this.hasData) {
        this.$nextTick(() => this.renderChart());
      }
    },
  },
};
</script>

<style scoped>
/* ── Theme ── */
.py-dark {
  --py-bg: #0a0a0a;
  --py-card-bg: #141414;
  --py-card-border: rgba(255,255,255,0.06);
  --py-text: rgba(255,255,255,0.87);
  --py-text-muted: rgba(255,255,255,0.45);
  --py-filter-bg: #141414;
}
.py-light {
  --py-bg: #f5f7fa;
  --py-card-bg: #ffffff;
  --py-card-border: rgba(0,0,0,0.06);
  --py-text: #212121;
  --py-text-muted: rgba(0,0,0,0.45);
  --py-filter-bg: #ffffff;
}

/* ── Page ── */
.py-page {
  min-height: calc(100vh - 140px);
  background: var(--py-bg);
  border-radius: 20px;
  overflow: hidden;
}

/* ── Hero ── */
.py-hero { position: relative; overflow: hidden; }
.py-hero-bg {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 30%, #1E88E5 60%, #42A5F5 100%);
}
.py-hero-inner {
  position: relative; z-index: 1;
  padding: 18px 24px;
  display: flex; align-items: center;
  color: #fff;
}
.py-hero-icon {
  width: 46px; height: 46px; border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255,255,255,.15);
}
.py-hero-title { font-size: 17px; font-weight: 800; letter-spacing: .3px; }
.py-hero-subtitle { font-size: 12px; opacity: .7; margin-top: 2px; }

/* ── Filters ── */
.py-filters {
  padding: 16px 24px;
  background: var(--py-filter-bg);
  border-bottom: 1px solid var(--py-card-border);
}

/* ── Summary Cards ── */
.py-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
  padding: 18px 24px 6px;
}
.py-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border-radius: 16px;
  background: var(--py-card-bg);
  border: 1px solid var(--py-card-border);
  transition: all 0.3s ease;
}
.py-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.py-card-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.py-card-fresh .py-card-icon { background: rgba(21, 101, 192, 0.1); }
.py-card-frozen .py-card-icon { background: rgba(0, 131, 143, 0.1); }
.py-card-yield .py-card-icon { background: rgba(46, 125, 50, 0.1); }
.py-card-workforce .py-card-icon { background: rgba(230, 81, 0, 0.1); }

.py-card-label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--py-text-muted);
  margin-bottom: 2px;
}
.py-card-value {
  font-size: 18px;
  font-weight: 800;
  color: var(--py-text);
}

/* ── Chart Container ── */
.py-chart-container {
  padding: 18px 24px 24px;
  min-height: 500px;
}
.py-chart {
  background: var(--py-card-bg);
  border: 1px solid var(--py-card-border);
  border-radius: 16px;
  padding: 16px 12px;
}

/* ── Loading ── */
.py-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 14px;
}
.py-loading-text {
  font-size: 13px;
  color: var(--py-text-muted);
  font-weight: 500;
}

/* ── Empty State ── */
.py-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 10px;
}
.py-empty-text {
  font-size: 16px;
  font-weight: 700;
  color: var(--py-text);
}
.py-empty-hint {
  font-size: 13px;
  color: var(--py-text-muted);
}
</style>
