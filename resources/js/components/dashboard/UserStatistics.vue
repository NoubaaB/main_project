<template>
<div class="us-page">
  <!-- Hero -->
  <div class="us-hero"><div class="us-hero-inner">
    <div class="d-flex align-center ga-4">
      <div class="us-hero-icon"><v-icon size="28" color="white">mdi-chart-timeline-variant-shimmer</v-icon></div>
      <div><div class="us-hero-title">{{ $t('user_statistics') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('user_statistics_desc') }}</div></div>
    </div>
    <div class="d-flex align-center ga-2">
      <v-btn variant="tonal" color="white" size="small" rounded prepend-icon="mdi-refresh" @click="fetchData" :loading="loading">{{ $t('refresh') }}</v-btn>
      <v-btn variant="tonal" color="white" size="small" rounded prepend-icon="mdi-arrow-left" @click="$router.push({name:'index'})">{{ $t('back') }}</v-btn>
    </div>
  </div></div>

  <!-- Date Range -->
  <v-card class="rounded-xl mb-4" elevation="1">
    <v-card-text class="pa-4">
      <div class="d-flex align-center ga-3 flex-wrap">
        <v-icon color="#7C4DFF">mdi-calendar-range</v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ $t('date_range') }}</span>
        <v-spacer />
        <v-chip color="#7C4DFF" variant="tonal" size="small"><v-icon start size="14">mdi-calendar-start</v-icon>{{ dateStart }}</v-chip>
        <v-icon size="16" color="grey">mdi-arrow-right</v-icon>
        <v-chip color="#7C4DFF" variant="tonal" size="small"><v-icon start size="14">mdi-calendar-end</v-icon>{{ dateEnd }}</v-chip>
      </div>
      <div class="d-flex justify-center mt-3">
        <DatePicker v-model.range="dateRange" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="purple" :max-date="new Date()" expanded :columns="2" @update:model-value="onDateChange" />
      </div>
    </v-card-text>
  </v-card>

  <!-- Loading -->
  <div v-if="loading" class="d-flex justify-center py-16"><v-progress-circular indeterminate color="#7C4DFF" size="48" /></div>

  <template v-else-if="stats">
    <!-- Today Summary Cards -->
    <v-row dense class="mb-4">
      <v-col cols="6" md="3"><v-card class="rounded-xl" elevation="1"><v-card-text class="text-center pa-4">
        <v-icon size="28" color="#7C4DFF" class="mb-2">mdi-lightning-bolt</v-icon>
        <div class="text-caption text-medium-emphasis">{{ $t('today_actions') }}</div>
        <div class="text-h5 font-weight-bold" style="color:#7C4DFF">{{ stats.today_stats?.total || 0 }}</div>
      </v-card-text></v-card></v-col>
      <v-col cols="6" md="3"><v-card class="rounded-xl" elevation="1"><v-card-text class="text-center pa-4">
        <v-icon size="28" color="#00BFA5" class="mb-2">mdi-account-group</v-icon>
        <div class="text-caption text-medium-emphasis">{{ $t('active_users_today') }}</div>
        <div class="text-h5 font-weight-bold" style="color:#00BFA5">{{ stats.today_stats?.active_users || 0 }}</div>
      </v-card-text></v-card></v-col>
      <v-col cols="6" md="3"><v-card class="rounded-xl" elevation="1"><v-card-text class="text-center pa-4">
        <v-icon size="28" color="#4CAF50" class="mb-2">mdi-plus-circle</v-icon>
        <div class="text-caption text-medium-emphasis">{{ $t('today_creates') }}</div>
        <div class="text-h5 font-weight-bold text-success">{{ stats.today_stats?.creates || 0 }}</div>
      </v-card-text></v-card></v-col>
      <v-col cols="6" md="3"><v-card class="rounded-xl" elevation="1"><v-card-text class="text-center pa-4">
        <v-icon size="28" color="#FF9800" class="mb-2">mdi-pencil-circle</v-icon>
        <div class="text-caption text-medium-emphasis">{{ $t('today_updates') }}</div>
        <div class="text-h5 font-weight-bold text-warning">{{ stats.today_stats?.updates || 0 }}</div>
      </v-card-text></v-card></v-col>
    </v-row>

    <!-- Daily Activity Chart -->
    <v-card class="rounded-xl mb-4" elevation="1">
      <v-card-text class="pa-5">
        <div class="d-flex align-center ga-2 mb-3">
          <v-icon color="#7C4DFF" size="20">mdi-chart-areaspline</v-icon>
          <span class="text-subtitle-1 font-weight-bold">{{ $t('daily_activity_trend') }}</span>
        </div>
        <apexchart v-if="dailyChartSeries[0].data.length" type="area" :height="280" :options="dailyChartOptions" :series="dailyChartSeries" />
        <div v-else class="text-center py-8 text-medium-emphasis">{{ $t('no_data_available') }}</div>
      </v-card-text>
    </v-card>

    <v-row dense class="mb-4">
      <!-- Module Breakdown Pie -->
      <v-col cols="12" md="5">
        <v-card class="rounded-xl" elevation="1" style="height:100%">
          <v-card-text class="pa-5">
            <div class="d-flex align-center ga-2 mb-3">
              <v-icon color="#7C4DFF" size="20">mdi-chart-donut</v-icon>
              <span class="text-subtitle-1 font-weight-bold">{{ $t('module_breakdown') }}</span>
            </div>
            <apexchart v-if="moduleSeries.length" type="donut" :height="280" :options="moduleChartOptions" :series="moduleSeries" />
            <div v-else class="text-center py-8 text-medium-emphasis">{{ $t('no_data_available') }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <!-- Hourly Distribution -->
      <v-col cols="12" md="7">
        <v-card class="rounded-xl" elevation="1" style="height:100%">
          <v-card-text class="pa-5">
            <div class="d-flex align-center ga-2 mb-3">
              <v-icon color="#7C4DFF" size="20">mdi-clock-outline</v-icon>
              <span class="text-subtitle-1 font-weight-bold">{{ $t('hourly_distribution') }}</span>
            </div>
            <apexchart v-if="hourlySeries[0].data.length" type="bar" :height="280" :options="hourlyChartOptions" :series="hourlySeries" />
            <div v-else class="text-center py-8 text-medium-emphasis">{{ $t('no_data_available') }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- User Leaderboard -->
    <v-card class="rounded-xl mb-4" elevation="1">
      <v-card-text class="pa-5">
        <div class="d-flex align-center ga-2 mb-4">
          <v-icon color="#7C4DFF" size="20">mdi-podium-gold</v-icon>
          <span class="text-subtitle-1 font-weight-bold">{{ $t('user_leaderboard') }}</span>
          <v-spacer />
          <v-chip size="small" color="#7C4DFF" variant="tonal">{{ stats.user_stats.length }} {{ $t('users') }}</v-chip>
        </div>
        <div class="us-table-wrap">
          <table class="us-table">
            <thead><tr>
              <th>#</th><th>{{ $t('user') }}</th><th>{{ $t('total_actions') }}</th>
              <th class="text-success">{{ $t('creates') }}</th><th class="text-warning">{{ $t('updates') }}</th>
              <th class="text-error">{{ $t('deletes') }}</th><th>{{ $t('top_module') }}</th>
              <th>{{ $t('last_activity') }}</th><th>{{ $t('activity_chart') }}</th>
            </tr></thead>
            <tbody>
              <tr v-for="(u, i) in stats.user_stats" :key="u.user_id" class="us-row">
                <td><v-chip :color="i<3?['#FFD700','#C0C0C0','#CD7F32'][i]:'grey'" size="x-small" variant="flat" class="font-weight-bold">{{ i+1 }}</v-chip></td>
                <td><div class="d-flex align-center ga-2"><v-avatar size="28" :color="avatarColor(u.user_id)"><span class="text-white text-caption font-weight-bold">{{ u.user_name?.charAt(0)?.toUpperCase() }}</span></v-avatar><div><div class="font-weight-medium text-body-2">{{ u.user_name }}</div><div class="text-caption text-medium-emphasis">{{ u.user_email }}</div></div></div></td>
                <td class="font-weight-bold" style="color:#7C4DFF">{{ u.total_actions }}</td>
                <td class="text-success font-weight-medium">{{ u.creates }}</td>
                <td class="text-warning font-weight-medium">{{ u.updates }}</td>
                <td class="text-error font-weight-medium">{{ u.deletes }}</td>
                <td><v-chip v-if="topModule(u)" size="x-small" color="#7C4DFF" variant="tonal" class="us-clickable" @click="navigateToModule(topModule(u))"><v-icon start size="12">{{ moduleIcon(topModule(u)) }}</v-icon>{{ topModule(u) }}</v-chip><span v-else class="text-grey">-</span></td>
                <td class="text-caption">{{ formatDateTime(u.last_activity) }}</td>
                <td style="min-width:120px"><apexchart type="area" :height="36" :width="120" :options="sparkOpts" :series="[{data:sparkData(u)}]" /></td>
              </tr>
            </tbody>
          </table>
        </div>
      </v-card-text>
    </v-card>

    <!-- Recent Activity Feed -->
    <v-card class="rounded-xl mb-4" elevation="1">
      <v-card-text class="pa-5">
        <div class="d-flex align-center ga-2 mb-4">
          <v-icon color="#7C4DFF" size="20">mdi-history</v-icon>
          <span class="text-subtitle-1 font-weight-bold">{{ $t('recent_activity') }}</span>
        </div>
        <div class="us-feed">
          <div v-for="a in stats.recent_activity" :key="a.id" class="us-feed-item">
            <div class="us-feed-dot" :style="{background: actionColor(a.action)}"></div>
            <div class="flex-grow-1">
              <div class="d-flex align-center ga-2 flex-wrap">
                <span class="font-weight-medium text-body-2">{{ a.user_name }}</span>
                <v-chip :color="actionColor(a.action)" size="x-small" variant="tonal">{{ $t(actionLabel(a.action)) }}</v-chip>
                <v-chip size="x-small" variant="outlined" class="us-clickable" @click="navigateToRecord(a.auditable_type, a.auditable_id)"><v-icon start size="12">{{ moduleIcon(a.auditable_type) }}</v-icon>{{ a.auditable_type }} #{{ a.auditable_id }}<v-icon end size="10">mdi-open-in-new</v-icon></v-chip>
              </div>
              <div class="text-caption text-medium-emphasis mt-1">{{ formatDateTime(a.created_at) }}</div>
            </div>
          </div>
          <div v-if="!stats.recent_activity?.length" class="text-center py-6 text-medium-emphasis">{{ $t('no_data_available') }}</div>
        </div>
      </v-card-text>
    </v-card>
  </template>
</div>
</template>

<script>
import axios from 'axios';
import VueApexCharts from 'vue3-apexcharts';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'UserStatistics',
  components: { apexchart: VueApexCharts },
  data() {
    return {
      loading: false,
      stats: null,
      dateRange: { start: new Date(new Date().setDate(new Date().getDate() - 30)), end: new Date() },
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    dateStart() { return this.fmtDate(this.dateRange.start); },
    dateEnd() { return this.fmtDate(this.dateRange.end); },
    dailyChartSeries() {
      if (!this.stats?.global_daily) return [{ name: 'Actions', data: [] }];
      return [
        { name: this.$t('creates'), data: this.stats.global_daily.map(d => d.creates) },
        { name: this.$t('updates'), data: this.stats.global_daily.map(d => d.updates) },
        { name: this.$t('deletes'), data: this.stats.global_daily.map(d => d.deletes) },
      ];
    },
    dailyChartOptions() {
      return {
        chart: { toolbar: { show: false }, fontFamily: 'Inter', stacked: true },
        xaxis: { categories: (this.stats?.global_daily || []).map(d => d.date), labels: { rotate: -45, style: { fontSize: '10px' } } },
        colors: ['#4CAF50', '#FF9800', '#EF5350'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.1 } },
        dataLabels: { enabled: false },
        tooltip: { theme: this.main.is_dark ? 'dark' : 'light' },
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
      };
    },
    moduleSeries() { return (this.stats?.module_breakdown || []).map(m => m.count); },
    moduleChartOptions() {
      return {
        labels: (this.stats?.module_breakdown || []).map(m => m.auditable_type),
        colors: ['#7C4DFF', '#00BFA5', '#FF6D00', '#2979FF', '#F50057', '#00E676', '#FFD600', '#AA00FF', '#00B8D4', '#C51162'],
        legend: { position: 'bottom', fontSize: '12px' },
        dataLabels: { enabled: true, formatter: (v) => Math.round(v) + '%' },
        tooltip: { theme: this.main.is_dark ? 'dark' : 'light' },
        plotOptions: { pie: { donut: { size: '55%', labels: { show: true, total: { show: true, label: this.$t('total'), fontSize: '14px' } } } } },
      };
    },
    hourlySeries() {
      const hourData = new Array(24).fill(0);
      (this.stats?.hourly || []).forEach(h => { hourData[h.hour] = h.count; });
      return [{ name: this.$t('actions'), data: hourData }];
    },
    hourlyChartOptions() {
      return {
        chart: { toolbar: { show: false }, fontFamily: 'Inter' },
        xaxis: { categories: Array.from({ length: 24 }, (_, i) => `${String(i).padStart(2, '0')}h`) },
        colors: ['#7C4DFF'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
        fill: { type: 'gradient', gradient: { shade: 'light', type: 'vertical', opacityFrom: 1, opacityTo: 0.7 } },
        dataLabels: { enabled: false },
        tooltip: { theme: this.main.is_dark ? 'dark' : 'light' },
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
      };
    },
    sparkOpts() {
      return {
        chart: { sparkline: { enabled: true }, type: 'area' },
        stroke: { curve: 'smooth', width: 1.5 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        colors: ['#7C4DFF'],
        tooltip: { enabled: false },
      };
    },
  },
  mounted() { this.fetchData(); },
  methods: {
    fmtDate(d) {
      if (!d) return '';
      const dt = d instanceof Date ? d : new Date(d);
      return `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
    },
    formatDateTime(dt) {
      if (!dt) return '-';
      const d = new Date(dt);
      return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ' ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    },
    onDateChange() { this.fetchData(); },
    async fetchData() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/user-statistics', { params: { date_start: this.dateStart, date_end: this.dateEnd } });
        this.stats = data;
      } catch (e) { console.error('[UserStats]', e); }
      this.loading = false;
    },
    topModule(u) { return u.modules?.length ? u.modules.sort((a, b) => b.count - a.count)[0].module : null; },
    sparkData(u) {
      if (!u.daily?.length) return [0];
      return u.daily.map(d => d.count);
    },
    avatarColor(id) { const c = ['#7C4DFF', '#00BFA5', '#FF6D00', '#2979FF', '#F50057', '#00E676', '#C51162']; return c[id % c.length]; },
    actionColor(a) { return { created: '#4CAF50', updated: '#FF9800', deleted: '#EF5350' }[a] || '#78909C'; },
    actionLabel(a) { return { created: 'record_created', updated: 'record_updated', deleted: 'record_deleted' }[a] || a; },

    /* ── Source navigation helpers ── */
    moduleIcon(type) {
      const icons = {
        Charge: 'mdi-truck-cargo-container', Production: 'mdi-factory',
        Shipping: 'mdi-package-variant-closed', Inventory: 'mdi-clipboard-text-clock-outline',
        WarehouseTransfer: 'mdi-swap-horizontal', SupplierInvoice: 'mdi-file-document-check',
        ClientInvoice: 'mdi-account-cash', StorageInvoice: 'mdi-warehouse',
        OrderTransfer: 'mdi-bank-transfer', FortnightActivity: 'mdi-calendar-month',
      };
      return icons[type] || 'mdi-cube-outline';
    },
    /**
     * Navigate to a module's list page (from Top Module chip).
     */
    navigateToModule(type) {
      const listRoutes = {
        Charge: 'list_charge', Production: 'production_list',
        Shipping: 'list_shipping', Inventory: 'list_inventory',
        WarehouseTransfer: 'list_warehouse_transfer', SupplierInvoice: 'list_supplier_invoice',
        ClientInvoice: 'list_client_invoice', StorageInvoice: 'storage_invoices',
        OrderTransfer: 'order_transfers_page', FortnightActivity: 'fortnight_management',
      };
      const routeName = listRoutes[type];
      if (routeName) {
        this.$router.push({ name: routeName });
      }
    },
    /**
     * Navigate to a specific record's view/edit page (from Recent Activity chip).
     */
    navigateToRecord(type, id) {
      const viewRoutes = {
        Charge:             { name: 'view_charge',            paramKey: 'id' },
        Production:         { name: 'view_production',        paramKey: 'id' },
        Shipping:           { name: 'view_shipping',          paramKey: 'id' },
        Inventory:          { name: 'edit_inventory',         paramKey: 'id' },
        WarehouseTransfer:  { name: 'edit_warehouse_transfer', paramKey: 'id' },
        SupplierInvoice:    { name: 'list_supplier_invoice',  paramKey: null },
        ClientInvoice:      { name: 'list_client_invoice',    paramKey: null },
        StorageInvoice:     { name: 'storage_invoices',       paramKey: null },
        OrderTransfer:      { name: 'order_transfers_page',   paramKey: null },
        FortnightActivity:  { name: 'fortnight_management',   paramKey: null },
      };
      const route = viewRoutes[type];
      if (!route) return;
      if (route.paramKey && id) {
        this.$router.push({ name: route.name, params: { [route.paramKey]: id } });
      } else {
        this.$router.push({ name: route.name });
      }
    },
  },
};
</script>

<style scoped>
.us-page { font-family: 'Inter', sans-serif; }
.us-hero { background: linear-gradient(135deg, #4527A0 0%, #7C4DFF 40%, #B388FF 100%); border-radius: 20px; margin-bottom: 16px; }
.us-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.us-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.us-hero-title { font-size: 20px; font-weight: 800; letter-spacing: .3px; }
.us-table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid rgba(0,0,0,.08); }
.us-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.us-table th { padding: 10px 12px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; border-bottom: 2px solid rgba(0,0,0,.08); white-space: nowrap; opacity: .7; }
.us-table td { padding: 8px 12px; text-align: center; border-bottom: 1px solid rgba(0,0,0,.04); white-space: nowrap; }
.us-row:hover { background: rgba(124,77,255,.04); }
.us-feed { max-height: 400px; overflow-y: auto; }
.us-feed-item { display: flex; align-items: flex-start; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,.04); }
.us-feed-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.us-clickable { cursor: pointer !important; transition: all 0.2s ease; }
.us-clickable:hover { transform: scale(1.05); box-shadow: 0 2px 8px rgba(124,77,255,.2); }
</style>
