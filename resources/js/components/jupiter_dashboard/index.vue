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

          <!-- Right: Status info & Refresh Button -->
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

    <!-- 1. Full-Width Extended VCalendar Date Range Section with 6 Assistant Presets & Periodicity -->
    <JupiterDateRange
      v-model="dateRange"
      :periodicity="periodicity"
      @update:periodicity="onPeriodicityChange"
    />

    <!-- 2. Main Analytics & Statistics Row (Exact like production/list) -->
    <v-row dense class="mb-4">
      <!-- Left: Analytics Chart Card (Production Balance / Factory Operations / Yields) -->
      <v-col cols="12" lg="8">
        <JupiterChart
          :analytics="chartData.analytics"
          :operations="chartData.operations"
          :yields="chartData.yields"
          :categories="chartData.categories"
          :categoryOptions="chartData.category_options"
          :loading="loading"
          :periodicity="periodicity"
          @update:periodicity="onPeriodicityChange"
        />
      </v-col>

      <!-- Right: Statistics Card (Categories with progress bars, Invoicing, Mass Equilibrium, Packaging, Yields) -->
      <v-col cols="12" lg="4">
        <JupiterStatistics
          :statistics="chartData.statistics"
        />
      </v-col>
    </v-row>

    <!-- 3. Consolidated Operational & Financial Summary Cards & Charges Categories Breakdown -->
    <JupiterSummaryCards
      :summary="operationalSummary"
      :chargesCategoryStats="chartData.statistics?.charges_category_stats || []"
      :chargeCategories="chartData.category_options?.charge_categories || []"
      :loading="loading"
    />

    <!-- Summary Snackbar with Countdown Progress Bar (Rule 5 & AddCharge style) -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="6000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="340"
      max-width="420"
      @update:modelValue="onSnackbarVisibilityChange"
    >
      <v-progress-linear :model-value="snackbarProgress" color="#0891B2" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-1">
        <v-icon :color="summarySnackbar.color || 'success'" class="mr-2">
          {{ summarySnackbar.color === 'error' ? 'mdi-alert-circle' : 'mdi-check-circle' }}
        </v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <div v-if="summarySnackbar.detail" class="text-caption text-medium-emphasis ms-7">
        {{ summarySnackbar.detail }}
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script>
import JupiterDateRange from './JupiterDateRange.vue';
import JupiterChart from './JupiterChart.vue';
import JupiterStatistics from './JupiterStatistics.vue';
import JupiterSummaryCards from './JupiterSummaryCards.vue';
import { useMain } from '@/stores/Main';
import mitt from '@/plugins/mitt';
import axios from 'axios';
import moment from 'moment';
import _ from 'lodash';

export default {
  name: 'JupiterDashboard',
  components: {
    JupiterDateRange,
    JupiterChart,
    JupiterStatistics,
    JupiterSummaryCards,
  },
  data() {
    return {
      dateRange: {
        start: moment().subtract(30, 'days').toDate(),
        end: new Date(),
      },
      periodicity: 'daily',
      chartData: {
        categories: [],
        analytics: {
          fresh_reception: [],
          frozen_production: [],
          guano_production: [],
          equilibrium: [],
        },
        operations: { charges: {}, productions: {}, shippings: {} },
        yields: [],
        statistics: {
          totals: {
            fresh_reception: 0,
            frozen_production: 0,
            guano_production: 0,
            equilibrium: 0,
            is_surplus: true,
            charges: {},
            productions: {},
            shippings: {},
          },
          invoicing: {
            total_invoiced: 0,
            total_not_invoiced: 0,
            invoiced_weight: 0,
            invoiced_price: 0,
          },
          category_stats: [],
          secondary_packaging: { total_cost: 0, items: [] },
          yields: [],
        },
        category_options: {
          charge_categories: [],
          production_categories: [],
          shipping_categories: [],
          fish_process_types: [],
        },
      },
      loading: false,
      lastFetched: null,
      summarySnackbar: {
        show: false,
        title: '',
        detail: '',
        color: 'success',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    lastFetchedDisplay() {
      if (!this.lastFetched) return '';
      return moment(this.lastFetched).fromNow();
    },
    dateKey() {
      const s = this.dateRange?.start ? moment(this.dateRange.start).format('YYYY-MM-DD') : '';
      const e = this.dateRange?.end ? moment(this.dateRange.end).format('YYYY-MM-DD') : '';
      return `${s}|${e}`;
    },
    operationalSummary() {
      const totals = this.chartData?.statistics?.totals || {};
      return {
        charges: totals.charges || { count: 0, total_weight: 0, total_price: 0, avg_price_per_kg: 0, avg_price: 0, avg_weight: 0 },
        productions: totals.productions || { count: 0, total_weight: 0, total_price: 0, total_sec_cost: 0 },
        shippings: totals.shippings || { count: 0, total_weight: 0, total_price: 0 },
        yields: this.chartData?.statistics?.yields || [],
        equilibrium: totals.equilibrium || 0,
        is_surplus: totals.is_surplus ?? true,
      };
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
    mitt.on('model-changed', this.handleModelChanged);
  },
  beforeUnmount() {
    mitt.off('model-changed', this.handleModelChanged);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  methods: {
    async fetchData() {
      if (!this.dateRange?.start || !this.dateRange?.end) return;
      if (this.loading) return;
      this.loading = true;
      try {
        const params = {
          start: moment(this.dateRange.start).format('YYYY-MM-DD'),
          end: moment(this.dateRange.end).format('YYYY-MM-DD'),
          periodicity: this.periodicity,
        };
        const { data } = await axios.get('/api/jupiter-dashboard/chart-data', { params });
        this.chartData = data;
        this.lastFetched = new Date();

        const totals = data.statistics?.totals || {};
        const c = totals.charges || {};
        const p = totals.productions || {};
        const s = totals.shippings || {};
        const totalRecords = (c.count || 0) + (p.count || 0) + (s.count || 0);

        this.triggerSummarySnackbar(
          this.$t('jupiter_data_loaded') || 'Jupiter Data Loaded',
          `${totalRecords} ${this.$t('records') || 'records'} — ${this.$t('charges') || 'Charges'}: ${c.count || 0}, ${this.$t('production') || 'Production'}: ${p.count || 0}, ${this.$t('shippings') || 'Shippings'}: ${s.count || 0}`,
          'success'
        );
      } catch (e) {
        console.error('[Jupiter] fetchData failed:', e);
        this.triggerSummarySnackbar(
          this.$t('jupiter_fetch_error') || 'Failed to load Jupiter Dashboard data',
          e.message || '',
          'error'
        );
      }
      this.loading = false;
    },
    debouncedFetch: _.debounce(function () {
      this.fetchData();
    }, 500),
    onPeriodicityChange(val) {
      if (this.periodicity === val) return;
      this.periodicity = val;
      this.fetchData();
    },
    handleModelChanged(event) {
      const relevant = ['charge', 'production', 'shipping', 'detail', 'guano', 'fish'];
      const modelName = (event?.model || '').toLowerCase();
      if (!modelName || relevant.some(r => modelName.includes(r))) {
        this.triggerSummarySnackbar(
          this.$t('live_sync_update') || 'Live Sync Update',
          this.$t('data_synchronized') || 'Factory data synchronized in real-time.',
          'info'
        );
        this.debouncedFetch();
      }
    },
    triggerSummarySnackbar(title, detail = '', color = 'success') {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.snackbarProgress = 100;
      this.summarySnackbar = { show: true, title, detail, color };
      const step = 100 / 60;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= step;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, 100);
    },
    onSnackbarVisibilityChange(val) {
      if (!val && this.snackbarTimer) {
        clearInterval(this.snackbarTimer);
      }
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
  background: rgba(6, 182, 212, 0.15);
  border: 1px solid rgba(6, 182, 212, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(8px);
}

.jupiter-hero-title {
  font-size: 24px;
  font-weight: 800;
  color: #FFFFFF;
  letter-spacing: -0.5px;
}

.live-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  color: #22D3EE;
  background: rgba(6, 182, 212, 0.12);
  border: 1px solid rgba(6, 182, 212, 0.3);
  border-radius: 9999px;
  padding: 3px 10px;
  letter-spacing: 0.3px;
  text-transform: uppercase;
}

.live-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22D3EE;
  box-shadow: 0 0 8px #22D3EE;
  animation: pulse-dot 2s infinite ease-in-out;
}

@keyframes pulse-dot {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.4;
    transform: scale(0.85);
  }
}

.hero-stat-pill {
  background: rgba(255, 255, 255, 0.08) !important;
  color: rgba(255, 255, 255, 0.85) !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  font-weight: 600;
  backdrop-filter: blur(8px);
}

.hero-refresh-btn {
  background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%) !important;
  color: #FFFFFF !important;
  box-shadow: 0 4px 14px rgba(6, 182, 212, 0.35);
  transition: all 0.2s ease;
}

.hero-refresh-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(6, 182, 212, 0.45);
}
</style>
