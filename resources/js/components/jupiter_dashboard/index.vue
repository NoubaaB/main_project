<template>
  <div class="jupiter-dashboard pb-16">
    <!-- Atmospheric Hero Header -->
    <div class="jupiter-hero elevation-2">
      <div class="hero-ambient-glow"></div>
      <div class="jupiter-hero-inner">
        <div class="d-flex align-center justify-space-between flex-wrap ga-3">
          <!-- Left: Icon & Title -->
          <div class="d-flex align-center ga-3 ga-sm-4">
            <div class="jupiter-hero-icon">
              <v-icon size="28" color="#06B6D4">mdi-orbit-variant</v-icon>
            </div>
            <div>
              <div class="d-flex align-center ga-2 flex-wrap">
                <span class="jupiter-hero-title">{{ $t('jupiter_dashboard') }}</span>
                <span class="live-status-pill ml-1">
                  <span class="live-dot"></span>
                  {{ $t('live_sync') || 'Live Stream' }}
                </span>
              </div>
              <div class="text-white text-caption mt-1" style="opacity: 0.85; font-size: 12px; line-height: 1.4;">
                {{ $t('jupiter_subtitle') }}
              </div>
            </div>
          </div>

          <!-- Right: Status info & Refresh Button with explicit icon margins -->
          <div class="d-flex align-center ga-2.5 flex-wrap">
            <v-chip v-if="lastFetched" variant="flat" size="small" class="hero-stat-pill">
              <v-icon start size="14" class="mr-1.5">mdi-clock-outline</v-icon>
              {{ lastFetchedDisplay }}
            </v-chip>
            <v-btn
              variant="flat"
              size="small"
              rounded="pill"
              :loading="loading"
              @click="fetchData"
              id="jupiter-refresh-btn"
              class="hero-refresh-btn text-none font-weight-bold px-4"
            >
              <v-icon start size="16" class="mr-1.5">mdi-refresh</v-icon>
              {{ $t('refresh') }}
            </v-btn>
          </div>
        </div>
      </div>
    </div>

    <!-- 1. Sleek Compact Date Range Section -->
    <JupiterDateRange v-model="dateRange" />

    <!-- 2. Responsive Filter Section -->
    <JupiterFilterBuilder @apply="onFiltersApply" ref="filterBuilder" />

    <!-- 3. Multi-mode Eye-Relief Chart Section -->
    <JupiterChart
      :categories="chartData.categories"
      :series="chartData.series"
      :costLines="chartData.cost_lines || []"
      :loading="loading"
      @update:periodicity="onPeriodicityChange"
      @update:measures="onMeasuresChange"
    />

    <!-- 4. Luxury Executive Summary Cards -->
    <JupiterSummaryCards :summary="chartData.summary" />

    <!-- Summary Snackbar -->
    <v-snackbar
      v-model="snackbar.show"
      location="top"
      :color="main.color_theme_reverse"
      :timeout="6000"
      rounded="pill"
    >
      <v-row align="center" no-gutters>
        <v-col cols="auto" class="mr-3">
          <v-icon :color="snackbar.iconColor">{{ snackbar.icon }}</v-icon>
        </v-col>
        <v-col class="text-caption font-weight-medium">{{ snackbar.text }}</v-col>
      </v-row>
      <template v-slot:actions>
        <v-btn color="info" variant="text" size="small" @click="snackbar.show = false">{{ $t('close') }}</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script>
import JupiterDateRange from './JupiterDateRange.vue';
import JupiterFilterBuilder from './JupiterFilterBuilder.vue';
import JupiterChart from './JupiterChart.vue';
import JupiterSummaryCards from './JupiterSummaryCards.vue';
import { useMain } from '@/stores/Main';
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'JupiterDashboard',
  components: {
    JupiterDateRange,
    JupiterFilterBuilder,
    JupiterChart,
    JupiterSummaryCards,
  },
  data() {
    return {
      dateRange: {
        start: moment().subtract(30, 'days').toDate(),
        end: new Date(),
      },
      periodicity: 'daily',
      measures: ['weight', 'price'],
      filters: [],
      chartData: {
        categories: [],
        series: [],
        summary: {
          charges: { count: 0, total_weight: 0, total_price: 0 },
          productions: { count: 0, total_weight: 0, total_price: 0 },
          shippings: { count: 0, total_weight: 0, total_price: 0 },
        },
      },
      loading: false,
      lastFetched: null,
      snackbar: {
        show: false,
        text: '',
        icon: 'mdi-check-circle',
        iconColor: 'success',
      },
    };
  },
  computed: {
    main() { return useMain(); },
    lastFetchedDisplay() {
      if (!this.lastFetched) return '';
      return moment(this.lastFetched).fromNow();
    },
    dateKey() {
      const s = this.dateRange.start ? moment(this.dateRange.start).format('YYYY-MM-DD') : '';
      const e = this.dateRange.end ? moment(this.dateRange.end).format('YYYY-MM-DD') : '';
      return `${s}|${e}`;
    },
  },
  watch: {
    dateKey(newVal, oldVal) {
      if (newVal && newVal !== oldVal) {
        this.debouncedFetch();
      }
    },
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      if (!this.dateRange.start || !this.dateRange.end) return;
      if (this.loading) return;
      this.loading = true;
      try {
        const params = {
          start: moment(this.dateRange.start).format('YYYY-MM-DD'),
          end: moment(this.dateRange.end).format('YYYY-MM-DD'),
          periodicity: this.periodicity,
          'measures[]': this.measures,
        };
        if (this.filters.length) {
          params.filters = JSON.stringify(this.filters);
        }
        const { data } = await axios.get('/api/jupiter-dashboard/chart-data', { params });
        this.chartData = data;
        this.lastFetched = new Date();

        const c = data.summary?.charges || {};
        const p = data.summary?.productions || {};
        const s = data.summary?.shippings || {};
        const totalRecords = (c.count || 0) + (p.count || 0) + (s.count || 0);
        this.snackbar = {
          show: true,
          text: `${this.$t('jupiter_data_loaded')}: ${totalRecords} ${this.$t('records')} — ${this.$t('charges')}: ${c.count || 0}, ${this.$t('production')}: ${p.count || 0}, ${this.$t('shippings')}: ${s.count || 0}`,
          icon: 'mdi-check-circle',
          iconColor: 'success',
        };
      } catch (e) {
        console.error('[Jupiter] fetchData failed:', e);
        this.snackbar = {
          show: true,
          text: this.$t('jupiter_fetch_error'),
          icon: 'mdi-alert-circle',
          iconColor: 'error',
        };
      }
      this.loading = false;
    },
    debouncedFetch: _.debounce(function () {
      this.fetchData();
    }, 600),
    onPeriodicityChange(val) {
      if (this.periodicity === val) return;
      this.periodicity = val;
      this.fetchData();
    },
    onMeasuresChange(val) {
      if (JSON.stringify(this.measures) === JSON.stringify(val)) return;
      this.measures = val;
      this.fetchData();
    },
    onFiltersApply(filters) {
      this.filters = filters;
      this.fetchData();
    },
  },
};
</script>

<style scoped>
.jupiter-dashboard {
  max-width: 100%;
}

.jupiter-hero {
  position: relative;
  background: linear-gradient(135deg, #0F172A 0%, #1E293B 45%, #0E7490 100%);
  border-radius: 20px;
  overflow: hidden;
  margin-bottom: 16px;
  border: 1px solid rgba(6, 182, 212, 0.2);
}

.hero-ambient-glow {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(6, 182, 212, 0.35) 0%, rgba(14, 116, 144, 0) 70%);
  pointer-events: none;
}

.jupiter-hero-inner {
  position: relative;
  z-index: 1;
  padding: 22px 26px;
}

.jupiter-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(6, 182, 212, 0.3);
  box-shadow: 0 4px 14px rgba(6, 182, 212, 0.2);
}

.jupiter-hero-title {
  font-size: 22px;
  font-weight: 800;
  color: white;
  letter-spacing: -0.3px;
}

.live-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 2px 10px;
  border-radius: 20px;
  background: rgba(6, 182, 212, 0.2);
  color: #38BDF8;
  font-size: 11px;
  font-weight: 700;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.live-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #38BDF8;
  box-shadow: 0 0 8px #38BDF8;
  animation: pulse-dot 2s infinite ease-in-out;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(0.85); }
}

.hero-stat-pill {
  background: rgba(255, 255, 255, 0.12) !important;
  color: white !important;
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.hero-refresh-btn {
  background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%) !important;
  color: white !important;
  box-shadow: 0 3px 12px rgba(8, 145, 178, 0.35);
}
</style>
