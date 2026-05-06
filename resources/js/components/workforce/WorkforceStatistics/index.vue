<template>
  <div class="wfs-dashboard">
    <!-- =========== HEADER =========== -->
    <div class="wfs-header">
      <div class="wfs-header-glow"></div>
      <div class="d-flex align-center ga-4 mb-1">
        <div class="wfs-icon-wrap">
          <v-icon size="28" color="white">mdi-chart-bar</v-icon>
        </div>
        <div>
          <h1 class="wfs-title">{{ $t('workforce_statistics') }}</h1>
          <p class="wfs-subtitle">{{ $t('workforce_statistics_desc') }}</p>
        </div>
      </div>
    </div>

    <!-- =========== MONTH SELECTOR =========== -->
    <v-card class="rounded-xl mb-4" variant="outlined" color="info">
      <v-card-text class="pa-4">
        <div class="d-flex align-center flex-wrap ga-3">
          <v-icon color="info" class="mr-1">mdi-calendar-range</v-icon>
          <span class="text-subtitle-2 font-weight-bold">{{ $t('select_months') }}</span>
          <div class="d-flex flex-wrap ga-2 ml-2">
            <v-chip
              v-for="(name, idx) in monthNames"
              :key="idx"
              :color="selectedMonths.includes(idx) ? 'info' : isMonthDisabled(idx) ? 'grey-lighten-2' : 'default'"
              :variant="selectedMonths.includes(idx) ? 'flat' : 'outlined'"
              size="small"
              :disabled="isMonthDisabled(idx)"
              @click="toggleMonth(idx)"
              class="wfs-month-chip"
              :style="isMonthDisabled(idx) ? 'opacity: 0.45; cursor: not-allowed;' : ''"
            >
              {{ name }}
            </v-chip>
          </div>
          <v-select
            v-model="selectedYear"
            :items="yearOptions"
            density="compact"
            variant="outlined"
            rounded
            hide-details
            color="info"
            class="wfs-year-select text-info"
            style="max-width: 120px;"
          ></v-select>
        </div>
      </v-card-text>
    </v-card>

    <!-- =========== FILTERS =========== -->
    <v-card class="rounded-xl mb-4" variant="outlined" color="info">
      <v-card-text class="pa-4">
        <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
          <v-icon color="info" class="mr-2" size="20">mdi-filter-variant</v-icon>
          {{ $t('filters') }}
        </div>
        <v-row dense>
          <v-col cols="12" md="4">
            <v-autocomplete
              v-model="filterWorkforceIds"
              :items="workforceStore.list"
              item-value="id"
              :item-title="workforceItemTitle"
              :label="$t('select_workforces')"
              density="compact"
              variant="outlined"
              rounded
              color="info"
              class="text-info"
              multiple
              chips
              closable-chips
              clearable
              hide-details
              :filter="workforceFilter"
            >
              <template v-slot:item="{ props, item }">
                <v-list-item v-bind="props">
                  <template v-slot:prepend>
                    <v-checkbox-input :model-value="filterWorkforceIds.includes(item.raw.id)" density="compact" hide-details />
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>
          <v-col cols="12" md="4">
            <v-autocomplete
              v-model="filterSectionIds"
              :items="sectionStore.list"
              item-value="id"
              item-title="name"
              :label="$t('sections')"
              density="compact"
              variant="outlined"
              rounded
              color="info"
              class="text-info"
              multiple
              chips
              closable-chips
              clearable
              hide-details
            >
              <template v-slot:item="{ props, item }">
                <v-list-item v-bind="props">
                  <template v-slot:prepend>
                    <v-checkbox-input :model-value="filterSectionIds.includes(item.raw.id)" density="compact" hide-details />
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>
          <v-col cols="12" md="4">
            <v-autocomplete
              v-model="filterCorporalIds"
              :items="corporalStore.list"
              item-value="id"
              item-title="full_name"
              :label="$t('corporals')"
              density="compact"
              variant="outlined"
              rounded
              color="info"
              class="text-info"
              multiple
              chips
              closable-chips
              clearable
              hide-details
            >
              <template v-slot:item="{ props, item }">
                <v-list-item v-bind="props">
                  <template v-slot:prepend>
                    <v-checkbox-input :model-value="filterCorporalIds.includes(item.raw.id)" density="compact" hide-details />
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- =========== KPI CARDS =========== -->
    <v-row class="mb-4">
      <v-col cols="6" md="3" v-for="kpi in kpiCards" :key="kpi.key">
        <div class="wfs-kpi-card">
          <div class="wfs-kpi-icon" :style="{ background: kpi.bg }">
            <v-icon :color="kpi.color" size="26">{{ kpi.icon }}</v-icon>
          </div>
          <div>
            <div class="wfs-kpi-val">{{ kpi.value }}</div>
            <div class="wfs-kpi-lbl">{{ kpi.label }}</div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- =========== ACTIVITY CHART =========== -->
    <v-card class="rounded-xl mb-4" variant="outlined" color="info">
      <v-card-title class="d-flex align-center justify-space-between pa-4 pb-2">
        <div class="d-flex align-center">
          <v-icon color="info" class="mr-2">mdi-chart-line</v-icon>
          <span class="text-subtitle-1 font-weight-bold">{{ $t('activity_totals') }}</span>
        </div>
        <v-btn-toggle v-model="chartPeriod" mandatory density="compact" color="info" variant="outlined" rounded="lg">
          <v-btn value="daily" size="small">{{ $t('daily') }}</v-btn>
          <v-btn value="monthly" size="small">{{ $t('monthly') }}</v-btn>
          <v-btn value="yearly" size="small">{{ $t('yearly') }}</v-btn>
        </v-btn-toggle>
      </v-card-title>
      <v-card-text class="pa-4 pt-0">
        <v-skeleton-loader v-if="loading" type="image" height="320" class="rounded-lg"></v-skeleton-loader>
        <apexchart
          v-else-if="mainChartSeries.length > 0 && mainChartSeries[0].data.length > 0"
          type="area"
          height="320"
          :options="mainChartOptions"
          :series="mainChartSeries"
        ></apexchart>
        <div v-else class="text-center pa-8 text-grey">
          <v-icon size="48" color="grey-lighten-1">mdi-chart-line-variant</v-icon>
          <div class="mt-2">{{ $t('no_activities_found') }}</div>
        </div>
      </v-card-text>
    </v-card>

    <!-- =========== SECTION & CORPORAL CHARTS =========== -->
    <v-row class="mb-4">
      <v-col cols="12" md="6">
        <v-card class="rounded-xl" variant="outlined" color="orange">
          <v-card-title class="d-flex align-center pa-4 pb-2">
            <v-icon color="orange" class="mr-2">mdi-sitemap-outline</v-icon>
            <span class="text-subtitle-1 font-weight-bold">{{ $t('total_by_section') }}</span>
          </v-card-title>
          <v-card-text class="pa-4 pt-0">
            <v-skeleton-loader v-if="loading" type="image" height="280" class="rounded-lg"></v-skeleton-loader>
            <apexchart
              v-else-if="sectionChartSeries.length > 0 && sectionChartSeries[0].data.length > 0"
              type="bar"
              height="280"
              :options="sectionChartOptions"
              :series="sectionChartSeries"
            ></apexchart>
            <div v-else class="text-center pa-6 text-grey">
              <v-icon size="36" color="grey-lighten-1">mdi-sitemap-outline</v-icon>
              <div class="mt-1 text-caption">{{ $t('no_section_data') }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="6">
        <v-card class="rounded-xl" variant="outlined" color="purple">
          <v-card-title class="d-flex align-center pa-4 pb-2">
            <v-icon color="purple" class="mr-2">mdi-account-tie</v-icon>
            <span class="text-subtitle-1 font-weight-bold">{{ $t('total_by_corporal') }}</span>
          </v-card-title>
          <v-card-text class="pa-4 pt-0">
            <v-skeleton-loader v-if="loading" type="image" height="280" class="rounded-lg"></v-skeleton-loader>
            <apexchart
              v-else-if="corporalChartSeries.length > 0 && corporalChartSeries[0].data.length > 0"
              type="bar"
              height="280"
              :options="corporalChartOptions"
              :series="corporalChartSeries"
            ></apexchart>
            <div v-else class="text-center pa-6 text-grey">
              <v-icon size="36" color="grey-lighten-1">mdi-account-tie</v-icon>
              <div class="mt-1 text-caption">{{ $t('no_corporal_data') }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import { useMain } from '@/stores/Main';
import { useWorkforce } from '@/stores/Workforce';
import { useCorporal } from '@/stores/Corporal';
import { useSection } from '@/stores/Section';
import { useFortnightActivity } from '@/stores/FortnightActivity';

export default {
  name: 'WorkforceStatistics',
  components: { apexchart: VueApexCharts },
  data() {
    const now = new Date();
    return {
      selectedYear: now.getFullYear(),
      selectedMonths: [now.getMonth()],
      filterWorkforceIds: [],
      filterSectionIds: [],
      filterCorporalIds: [],
      loading: false,
      chartPeriod: 'daily',
      stats: { daily: [], sections: [], corporals: [], grand_total: 0, total_activities: 0 },
      monthNames: [
        this.$t('month_january') || 'Jan', this.$t('month_february') || 'Feb',
        this.$t('month_march') || 'Mar', this.$t('month_april') || 'Apr',
        this.$t('month_may') || 'May', this.$t('month_june') || 'Jun',
        this.$t('month_july') || 'Jul', this.$t('month_august') || 'Aug',
        this.$t('month_september') || 'Sep', this.$t('month_october') || 'Oct',
        this.$t('month_november') || 'Nov', this.$t('month_december') || 'Dec',
      ],
    };
  },
  computed: {
    main() { return useMain(); },
    workforceStore() { return useWorkforce(); },
    corporalStore() { return useCorporal(); },
    sectionStore() { return useSection(); },
    activityStore() { return useFortnightActivity(); },
    yearOptions() {
      const y = new Date().getFullYear();
      return [y - 2, y - 1, y];
    },
    dateRange() {
      if (!this.selectedMonths.length) return null;
      const sorted = [...this.selectedMonths].sort((a, b) => a - b);
      const startMonth = sorted[0];
      const endMonth = sorted[sorted.length - 1];
      const start = `${this.selectedYear}-${String(startMonth + 1).padStart(2, '0')}-01`;
      const lastDay = new Date(this.selectedYear, endMonth + 1, 0).getDate();
      const end = `${this.selectedYear}-${String(endMonth + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
      return { start, end };
    },
    currency() { return this.main.currency || 'DH'; },
    kpiCards() {
      return [
        { key: 'total', icon: 'mdi-cash-multiple', label: this.$t('total_amount'), value: `${this.fmt(this.stats.grand_total)} ${this.currency}`, bg: 'rgba(25,118,210,0.1)', color: 'info' },
        { key: 'count', icon: 'mdi-counter', label: this.$t('total_activities_kpi'), value: this.stats.total_activities, bg: 'rgba(46,125,50,0.1)', color: 'success' },
        { key: 'sections', icon: 'mdi-sitemap-outline', label: this.$t('sections'), value: this.stats.sections.length, bg: 'rgba(239,108,0,0.1)', color: 'orange' },
        { key: 'corporals', icon: 'mdi-account-tie', label: this.$t('corporals'), value: this.stats.corporals.length, bg: 'rgba(156,39,176,0.1)', color: 'purple' },
      ];
    },
    // Aggregated chart data based on selected period
    aggregatedChartData() {
      const daily = this.stats.daily || [];
      if (!daily.length) return [];

      if (this.chartPeriod === 'daily') {
        return daily.map(d => ({ label: d.date, total: d.total }));
      }

      if (this.chartPeriod === 'monthly') {
        const map = {};
        daily.forEach(d => {
          const key = d.date.substring(0, 7); // YYYY-MM
          if (!map[key]) map[key] = { label: key, total: 0 };
          map[key].total += d.total;
        });
        return Object.values(map);
      }

      // yearly
      const map = {};
      daily.forEach(d => {
        const key = d.date.substring(0, 4); // YYYY
        if (!map[key]) map[key] = { label: key, total: 0 };
        map[key].total += d.total;
      });
      return Object.values(map);
    },
    mainChartSeries() {
      return [{ name: this.$t('total_amount'), data: this.aggregatedChartData.map(d => d.total) }];
    },
    mainChartOptions() {
      return {
        chart: { type: 'area', toolbar: { show: true }, fontFamily: 'inherit', zoom: { enabled: true } },
        colors: ['#1E88E5'],
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
        markers: { size: 4 },
        xaxis: { categories: this.aggregatedChartData.map(d => d.label), labels: { rotate: -45, rotateAlways: this.aggregatedChartData.length > 15 } },
        yaxis: { labels: { formatter: v => this.fmt(v) + ' ' + this.currency } },
        tooltip: { y: { formatter: v => this.fmt(v) + ' ' + this.currency } },
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
        dataLabels: { enabled: this.chartPeriod !== 'daily', formatter: v => this.fmt(v), style: { fontSize: '11px' } },
      };
    },
    // Section chart
    sectionChartSeries() {
      return [{ name: this.$t('total_amount'), data: this.stats.sections.map(s => s.total) }];
    },
    sectionChartOptions() {
      return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#EF6C00'],
        plotOptions: { bar: { borderRadius: 8, horizontal: false, columnWidth: '60%', distributed: true } },
        xaxis: { categories: this.stats.sections.map(s => s.name) },
        yaxis: { labels: { formatter: v => this.fmt(v) } },
        tooltip: { y: { formatter: v => this.fmt(v) + ' ' + this.currency + ' (' + this.$t('count') + ')' } },
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
        dataLabels: { enabled: true, formatter: v => this.fmt(v), style: { fontSize: '11px' } },
        legend: { show: false },
      };
    },
    // Corporal chart
    corporalChartSeries() {
      return [{ name: this.$t('total_amount'), data: this.stats.corporals.map(c => c.total) }];
    },
    corporalChartOptions() {
      return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#7B1FA2'],
        plotOptions: { bar: { borderRadius: 8, horizontal: false, columnWidth: '60%', distributed: true } },
        xaxis: { categories: this.stats.corporals.map(c => c.name) },
        yaxis: { labels: { formatter: v => this.fmt(v) } },
        tooltip: { y: { formatter: v => this.fmt(v) + ' ' + this.currency } },
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
        dataLabels: { enabled: true, formatter: v => this.fmt(v), style: { fontSize: '11px' } },
        legend: { show: false },
      };
    },
  },
  watch: {
    selectedYear() {
      // Auto-deselect any months that are now disabled for the newly selected year
      this.selectedMonths = this.selectedMonths.filter(m => !this.isMonthDisabled(m));
      // If all were removed, default to the latest available month
      if (!this.selectedMonths.length) {
        const now = new Date();
        if (this.selectedYear === now.getFullYear()) {
          this.selectedMonths = [now.getMonth()];
        } else {
          this.selectedMonths = [11]; // December for past years
        }
      }
      this.loadStats();
    },
    selectedMonths: {
      handler() {
        // Debounce: wait 600ms after last month toggle before fetching
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => {
          this.loadStats();
        }, 600);
      },
      deep: true,
    },
    filterWorkforceIds() { this.loadStats(); },
    filterSectionIds() { this.loadStats(); },
    filterCorporalIds() { this.loadStats(); },
  },
  beforeUnmount() {
    clearTimeout(this._debounceTimer);
  },
  async mounted() {
    await Promise.all([
      this.workforceStore.fetch(),
      this.corporalStore.fetch(),
      this.sectionStore.fetch(),
    ]);
    this.loadStats();
  },
  methods: {
    fmt(num) {
      if (this.main.formatDisplayNumber) return this.main.formatDisplayNumber(num);
      return Number(num || 0).toFixed(2);
    },
    workforceItemTitle(item) {
      return `${item.full_name} (${item.matricule})`;
    },
    workforceFilter(itemTitle, queryText, item) {
      const q = queryText.toLowerCase();
      const raw = item.raw;
      return (raw.full_name || '').toLowerCase().includes(q)
        || (raw.matricule || '').toLowerCase().includes(q);
    },
    isMonthDisabled(idx) {
      const now = new Date();
      // For the current year, disable months that haven't been reached
      if (this.selectedYear === now.getFullYear()) {
        return idx > now.getMonth();
      }
      // For past years, all months are enabled
      return false;
    },
    toggleMonth(idx) {
      if (this.isMonthDisabled(idx)) return;
      const pos = this.selectedMonths.indexOf(idx);
      if (pos >= 0) {
        if (this.selectedMonths.length > 1) this.selectedMonths.splice(pos, 1);
      } else {
        this.selectedMonths.push(idx);
      }
    },
    async loadStats() {
      if (!this.selectedMonths.length) return;

      this.loading = true;

      // Build base filter params
      const baseParams = {};
      if (this.filterWorkforceIds.length) baseParams.workforce_ids = this.filterWorkforceIds;
      if (this.filterSectionIds.length) baseParams.section_ids = this.filterSectionIds;
      if (this.filterCorporalIds.length) baseParams.corporal_ids = this.filterCorporalIds;

      // Make one API call per selected month to avoid including unselected months in between
      const requests = this.selectedMonths.map(monthIdx => {
        const start = `${this.selectedYear}-${String(monthIdx + 1).padStart(2, '0')}-01`;
        const lastDay = new Date(this.selectedYear, monthIdx + 1, 0).getDate();
        const end = `${this.selectedYear}-${String(monthIdx + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
        return this.activityStore.fetchStatistics({ ...baseParams, date_start: start, date_end: end });
      });

      const results = await Promise.all(requests);

      // Merge all month results into one stats object
      const merged = { daily: [], sections: [], corporals: [], grand_total: 0, total_activities: 0 };
      const sectionMap = {};
      const corporalMap = {};

      results.forEach(res => {
        // Merge daily (unique by date)
        (res.daily || []).forEach(d => merged.daily.push(d));

        // Merge sections (aggregate by id)
        (res.sections || []).forEach(s => {
          if (!sectionMap[s.id]) {
            sectionMap[s.id] = { ...s };
          } else {
            sectionMap[s.id].total += s.total;
            sectionMap[s.id].count += s.count;
          }
        });

        // Merge corporals (aggregate by id)
        (res.corporals || []).forEach(c => {
          if (!corporalMap[c.id]) {
            corporalMap[c.id] = { ...c };
          } else {
            corporalMap[c.id].total += c.total;
            corporalMap[c.id].count += c.count;
          }
        });

        merged.grand_total += res.grand_total || 0;
        merged.total_activities += res.total_activities || 0;
      });

      // Sort daily by date
      merged.daily.sort((a, b) => a.date.localeCompare(b.date));
      merged.sections = Object.values(sectionMap);
      merged.corporals = Object.values(corporalMap);

      this.stats = merged;
      this.loading = false;
    },
  },
};
</script>

<style scoped>
.wfs-dashboard { padding: 0 8px; font-family: 'Inter', sans-serif; }

/* Header */
.wfs-header { background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%); border-radius: 20px; padding: 28px 32px; margin-bottom: 20px; position: relative; overflow: hidden; }
.wfs-header-glow { position: absolute; top: -40px; right: -40px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%); pointer-events: none; }
.wfs-icon-wrap { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.wfs-title { font-size: 19px; font-weight: 800; color: #fff; letter-spacing: .3px; margin: 0; }
.wfs-subtitle { font-size: 13px; color: rgba(255,255,255,.7); margin: 0; }

/* Month chips */
.wfs-month-chip { cursor: pointer; transition: all .2s ease; font-weight: 600; }
.wfs-month-chip:hover { transform: scale(1.05); }
.wfs-year-select { flex-shrink: 0; }

/* KPI Cards */
.wfs-kpi-card { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid rgba(0,0,0,.06); border-radius: 16px; padding: 16px 18px; transition: box-shadow .2s ease, transform .2s ease; }
.wfs-kpi-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.06); transform: translateY(-2px); }
.wfs-kpi-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.wfs-kpi-val { font-size: 18px; font-weight: 800; line-height: 1.2; display: block; }
.wfs-kpi-lbl { font-size: 12px; color: #666; font-weight: 500; }
</style>
