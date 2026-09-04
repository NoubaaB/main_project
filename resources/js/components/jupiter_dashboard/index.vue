<template>
  <div class="jupiter-dashboard">
    <!-- Hero Header -->
    <div class="jupiter-hero">
      <div class="jupiter-hero-inner">
        <div class="d-flex align-center justify-space-between flex-wrap ga-3">
          <div class="d-flex align-center ga-4">
            <div class="jupiter-hero-icon">
              <v-icon size="28" color="white">mdi-orbit-variant</v-icon>
            </div>
            <div>
              <div class="jupiter-hero-title">{{ $t('jupiter_dashboard') }}</div>
              <div class="text-white text-caption" style="opacity:.7">{{ $t('jupiter_subtitle') }}</div>
            </div>
          </div>
          <div class="d-flex align-center ga-2">
            <v-chip v-if="lastFetched" variant="flat" size="small" style="background: rgba(255,255,255,.18); color: white; backdrop-filter: blur(6px)">
              <v-icon start size="14">mdi-clock-outline</v-icon>
              {{ lastFetchedDisplay }}
            </v-chip>
            <v-btn
              variant="outlined"
              color="white"
              size="small"
              rounded
              prepend-icon="mdi-refresh"
              :loading="loading"
              @click="fetchData"
              id="jupiter-refresh-btn"
            >{{ $t('refresh') }}</v-btn>
          </div>
        </div>
      </div>
    </div>

    <!-- 1. Date Range Section -->
    <JupiterDateRange v-model="dateRange" />

    <!-- 2. Filter Section -->
    <JupiterFilterBuilder @apply="onFiltersApply" ref="filterBuilder" />

    <!-- 3. Chart Section -->
    <JupiterChart
      :categories="chartData.categories"
      :series="chartData.series"
      :costLines="chartData.cost_lines || []"
      :loading="loading"
      @update:periodicity="onPeriodicityChange"
      @update:measures="onMeasuresChange"
    />

    <!-- 4. Summary Cards -->
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
        <v-col>{{ snackbar.text }}</v-col>
      </v-row>
      <template v-slot:actions>
        <v-btn color="info" variant="text" @click="snackbar.show = false">{{ $t('close') }}</v-btn>
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
    // A simple string key so we can watch for actual date changes without deep watchers
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
      if (this.loading) return; // Prevent concurrent fetches
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

        // Show summary snackbar
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
      if (this.periodicity === val) return; // Skip if same value (prevents loop)
      this.periodicity = val;
      this.fetchData();
    },
    onMeasuresChange(val) {
      // Skip if same values (prevents loop from initial emit)
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
/* ============================================================
   Jupiter Dashboard — Cosmic Amber Theme
   ============================================================ */
.jupiter-dashboard {
  max-width: 100%;
}

.jupiter-hero {
  background: linear-gradient(135deg, #E65100 0%, #FF6F00 40%, #FFA726 100%);
  border-radius: 16px;
  padding: 2px;
  margin-bottom: 16px;
}

.jupiter-hero-inner {
  background: linear-gradient(135deg, rgba(230, 81, 0, .92) 0%, rgba(255, 111, 0, .92) 40%, rgba(255, 167, 38, .92) 100%);
  border-radius: 14px;
  padding: 24px 28px;
}

.jupiter-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, .18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.jupiter-hero-title {
  font-size: 22px;
  font-weight: 800;
  color: white;
  letter-spacing: -0.3px;
}
</style>
