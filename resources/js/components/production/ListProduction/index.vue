<template>
  <div class="pl-page">
    <!-- Hero Header -->
    <v-card class="rounded-xl mb-5" variant="flat">
      <div class="pl-hero-bg"></div>
      <div class="pl-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="pl-hero-icon">
            <v-icon size="28" color="white">mdi-format-list-bulleted</v-icon>
          </div>
          <div>
            <div class="pl-hero-title">{{ $t('production_list') }}</div>
            <div class="text-white-darken-2 text-caption" style="opacity:.7">
              {{ $t('production_list_desc') }}
            </div>
          </div>
        </div>
      </div>
    </v-card>

    <!-- Date Picker & Chart Section -->
    <v-card class="rounded-xl mb-5 pa-5" variant="flat">
      <v-row>
        <v-col cols="12" md="3">
          <DatePicker
            :is-dark="main.is_dark"
            :max-date="new Date()"
            v-model.range="date"
            mode="date"
            :locale="main.lang.short"
            expanded
          />
          <div class="text-center mt-3">
            <v-chip color="info" variant="tonal" class="mr-2" size="small">
              <v-icon start size="13">mdi-calendar-start</v-icon>
              <span>{{ main.formateDate(date.start) }}</span>
            </v-chip>
            <v-icon size="14" color="grey">mdi-arrow-right</v-icon>
            <v-chip color="indigo" variant="tonal" class="ml-2" size="small">
              <span>{{ main.formateDate(date.end) }}</span>
              <v-icon end size="13">mdi-calendar-end</v-icon>
            </v-chip>
          </div>
        </v-col>
        <v-col cols="12" md="9">
          <!-- Production Yield Chart -->
          <div class="pl-chart-section">
            <div class="pl-chart-header">
              <v-icon size="20" color="info" class="mr-2">mdi-chart-line</v-icon>
              <span class="pl-chart-title">{{ $t('production_yield') }}</span>
              <v-spacer></v-spacer>
              <v-btn-toggle v-model="yieldPeriod" mandatory density="compact" color="info" variant="outlined" rounded="lg" class="mr-2">
                <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
                <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
                <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
              </v-btn-toggle>
              <v-btn
                v-if="!yieldLoading"
                icon
                size="x-small"
                variant="tonal"
                color="info"
                @click="fetchYield"
                :title="$t('refresh')"
              >
                <v-icon size="16">mdi-refresh</v-icon>
              </v-btn>
            </div>
            <div class="pl-chart-area">
              <v-progress-circular v-if="yieldLoading" indeterminate color="info" size="40"></v-progress-circular>
              <apexchart
                v-else-if="yieldChartSeries.length > 0"
                type="line"
                height="280"
                :options="yieldChartOptions"
                :series="yieldChartSeries"
                style="width:100%"
              ></apexchart>
              <div v-else class="pl-chart-placeholder">
                <v-icon size="48" color="grey-lighten-2">mdi-chart-areaspline</v-icon>
                <div class="pl-chart-placeholder-text">{{ $t('production_yield_chart_desc') }}</div>
              </div>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-card>

    <!-- Data Table -->
    <v-card class="rounded-xl" variant="flat">
      <DataTable
        :relations="relations"
        :show_expand="true"
        :is_neasted="true"
        :show_invoice="false"
        :advanced_relations="advanced_relations"
        :filters="filters"
        :allow_invoice="allow_invoice"
        :advanced_filters="advanced_filters"
        :advanced_filters_keys="advanced_filters_keys"
        :items="items"
        :sigma="sigma"
        :date="date"
        :headers="headers"
        :detail_headers="detail_headers"
        :fetchData="fetchProductionCharges"
        :_invoices_print_selected="_invoices_print_selected"
        :_exportToExcel="_exportToExcel"
        @sync_items="update_filtred_items_to_source"
        :delete_items="delete_items"
        :get_invoices_delete_selected_length="get_invoices_delete_selected_length"
        :listen_models="['Charge']"
      >
        <!-- CELLS -->
        <template v-slot:[`item.date`]="{ item }">
          <v-chip color="info" variant="tonal" size="small">
            {{ main.formateDate(item.date) }}
          </v-chip>
        </template>

        <template v-slot:[`item.tracking_numbers`]="{ item }">
          <v-chip v-for="v in item.tracking_numbers" :key="v" color="info" class="ma-1" size="small">{{ v }}</v-chip>
        </template>

        <template v-slot:[`item.tracking_onp_numbers`]="{ item }">
          <v-chip v-for="v in item.tracking_onp_numbers" :key="v" color="secondary" class="ma-1" size="small">{{ v }}</v-chip>
        </template>

        <template v-slot:[`item.production_tracking_numbers`]="{ item }">
          <v-chip v-for="v in item.production_tracking_numbers" :key="v" color="teal" class="ma-1" size="small">{{ v }}</v-chip>
        </template>

        <template v-slot:[`item.supplier_ids`]="{ item }">
          <template v-if="item.supplier_ids.length > 0">
            <v-chip v-for="id in item.supplier_ids" :key="id" color="deep-purple" class="ma-1" size="small">
              {{ suppliers.find(s => s.id === id)?.name || $t("n/a") }}
            </v-chip>
          </template>
          <template v-else>
            <v-chip :color="main.not_aviable_color" class="ma-1" size="small">
              <v-icon class="mr-1">mdi-circle-off-outline</v-icon> {{ $t("n/a") }}
            </v-chip>
          </template>
        </template>

        <template v-slot:[`item.truck_ids`]="{ item }">
          <template v-if="item.truck_ids.length > 0">
            <v-chip v-for="id in item.truck_ids" :key="id" color="pink" class="ma-1" size="small">
              {{ trucks.find(s => s.id === id)?.registration_plate || $t("n/a") }}
            </v-chip>
          </template>
          <template v-else>
            <v-chip :color="main.not_aviable_color" class="ma-1" size="small">
              <v-icon class="mr-1">mdi-circle-off-outline</v-icon> {{ $t("n/a") }}
            </v-chip>
          </template>
        </template>

        <template v-slot:[`item.charge_category_id`]="{ item }">
          <v-chip color="info" class="ma-1" size="small" variant="tonal">
            <v-icon start size="12">mdi-factory</v-icon>
            {{ charge_categories.find(f => f.id == item.charge_category_id)?.name || $t("n/a") }}
          </v-chip>
        </template>

        <template v-slot:[`item.origin_id`]="{ item }">
          <v-chip color="cyan" class="ma-1" size="small">
            {{ origins.find(o => o.id === item.origin_id)?.name || $t("n/a") }}
          </v-chip>
        </template>

        <template v-slot:[`item.ship_id`]="{ item }">
          <v-chip color="grey" class="ma-1" size="small">
            {{ ships.find(s => s.id === item.ship_id)?.name || $t("n/a") }}
          </v-chip>
        </template>

        <template v-slot:[`item.fishery_id`]="{ item }">
          <v-chip color="amber" class="ma-1" size="small">
            {{ fisheries.find(f => f.id === item.fishery_id)?.name || $t("n/a") }}
          </v-chip>
        </template>

        <template v-slot:[`item.total_weight`]="{ item }">
          {{ main.formatDisplayNumber(item.total_weight) }}
        </template>

        <template v-slot:[`item.total_price`]="{ item }">
          {{ main.formatDisplayNumber(item.total_price) }}
        </template>


        <template v-slot:[`item.actions`]="{ item }">
          <v-menu location="bottom end" transition="scale-transition" :close-on-content-click="true">
            <template v-slot:activator="{ props }">
              <v-btn icon="mdi-dots-vertical" size="x-small" variant="tonal" color="info" v-bind="props"></v-btn>
            </template>
            <v-card class="rounded-lg" min-width="180" variant="flat">
              <v-list slim density="compact" class="pa-1" lines>
                <v-list-item class="rounded-lg" prepend-icon="mdi-eye-outline" @click="viewItem(item)">
                  <v-list-item-title class="text-caption font-weight-bold text-info">{{ $t('view_charge') }}</v-list-item-title>
                </v-list-item>
                <v-list-item class="rounded-lg" prepend-icon="mdi-pencil-outline" @click="editItem(item)">
                  <v-list-item-title class="text-caption font-weight-bold text-orange">{{ $t('edit_item') }}</v-list-item-title>
                </v-list-item>
                <v-divider class="my-1"></v-divider>
                <v-list-item v-if="!item.production" class="rounded-lg" prepend-icon="mdi-plus-circle-outline" @click="addProduction(item)">
                  <v-list-item-title class="text-caption font-weight-bold text-teal">{{ $t('add_production') }}</v-list-item-title>
                </v-list-item>
                <v-list-item v-if="item.production" class="rounded-lg" prepend-icon="mdi-cog-outline" @click="viewProduction(item)">
                  <v-list-item-title class="text-caption font-weight-bold text-teal">{{ $t('view_production') }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card>
          </v-menu>
        </template>
      </DataTable>
    </v-card>
  </div>
</template>

<script>
import DataTable from '@/components/schema/plugins/DataTable.vue';
import VueApexCharts from 'vue3-apexcharts';
import axios from 'axios';
import moment from 'moment';
import * as XLSX from 'xlsx';
import { useMain } from '@/stores/Main';
import { useSupplier } from '@/stores/Supplier';
import { useOrigin } from '@/stores/Origin';
import { useFishery } from '@/stores/Fishery';
import { useShip } from '@/stores/Ship';
import { useChargeCategory } from '@/stores/ChargeCategory';
import { useCharge } from '@/stores/Charge';
import { useWarehouse } from '@/stores/Warehouse';
import { useFishFamily } from '@/stores/FishFamily';
import { useTruck } from '@/stores/Truck';

export default {
  components: { DataTable, apexchart: VueApexCharts },
  data() {
    return {
      // Yield chart
      yieldDates: [],
      yieldSeries: [],
      yieldLoading: false,
      yieldPeriod: 'monthly',
      // Palette for process types
      processTypeColors: ['#42A5F5', '#FF7043', '#AB47BC', '#26A69A', '#FFA726', '#EF5350', '#5C6BC0', '#66BB6A'],
      filters: {
        date: [],
        charge_category_id: [],
        tracking_numbers: [],
        tracking_onp_numbers: [],
        production_tracking_numbers: [],
        supplier_ids: [],
        truck_ids: [],
        origin_id: [],
        ship_id: [],
        fishery_id: [],
      },
      advanced_filters: {
        warehouse_id: [],
        stockable_type: [],
        stockable_id: [],
        fish_family_id: [],
      },
      advanced_filters_keys: ['warehouse_id', 'stockable_type', 'stockable_id', 'fish_family_id'],
      date: {
        start: new Date(2024, 2, 1),
        end: new Date(),
      },
      allow_invoice:false,
    };
  },
  computed: {
    main: () => useMain(),
    suppliers: () => useSupplier().getList,
    trucks: () => useTruck().getList,
    origins: () => useOrigin().getList,
    fisheries: () => useFishery().getList,
    ships: () => useShip().getList,
    warehouses: () => useWarehouse().getList,
    charge: () => useCharge(),
    charge_categories: () => useChargeCategory().getList,
    fish_families: () => useFishFamily().getList,

    // Yield chart computed
    yieldChartSeries() {
      if (!this.yieldSeries.length || !this.yieldDates.length) return [];
      const series = [];
      const colorPalette = this.processTypeColors;

      this.yieldSeries.forEach((pt, idx) => {
        const baseColor = colorPalette[idx % colorPalette.length];
        // Column: Reception (frais)
        series.push({
          name: `${pt.name} — ${this.$t('frais_weight')}`,
          type: 'column',
          data: pt.frais_weights,
          color: baseColor,
        });
        // Column: Production (congele)
        series.push({
          name: `${pt.name} — ${this.$t('congele_weight')}`,
          type: 'column',
          data: pt.congele_weights,
          color: this.lightenColor(baseColor, 40),
        });
        // Line: Yield %
        series.push({
          name: `${pt.name} — ${this.$t('yield_pct')}`,
          type: 'line',
          data: pt.yields,
          color: this.darkenColor(baseColor, 30),
        });
      });
      return series;
    },
    yieldChartColors() {
      const colors = [];
      const colorPalette = this.processTypeColors;
      this.yieldSeries.forEach((pt, idx) => {
        const baseColor = colorPalette[idx % colorPalette.length];
        colors.push(baseColor);
        colors.push(this.lightenColor(baseColor, 40));
        colors.push(this.darkenColor(baseColor, 30));
      });
      return colors;
    },
    yieldChartOptions() {
      const totalSeries = this.yieldChartSeries.length;
      // Build yaxis: first axis for weight, second for yield %
      const yaxis = [
        {
          title: { text: this.$t('weight_kg'), style: { fontWeight: 600 } },
          labels: { formatter: v => Number(v).toLocaleString() },
        },
      ];
      // Add hidden axes for column series (seriesName must match)
      // Then add yield axis on opposite side
      // Line series indices
      const lineIndices = [];
      this.yieldSeries.forEach((_, idx) => {
        lineIndices.push(idx * 3 + 2); // every 3rd series
      });

      // Build stroke widths
      const strokeWidths = [];
      for (let i = 0; i < totalSeries; i++) {
        strokeWidths.push(lineIndices.includes(i) ? 3 : 0);
      }

      return {
        chart: {
          type: 'line',
          stacked: false,
          toolbar: { show: false },
          fontFamily: 'Inter, system-ui, sans-serif',
          background: 'transparent',
        },
        plotOptions: {
          bar: { borderRadius: 4, columnWidth: '60%' },
        },
        colors: this.yieldChartColors,
        stroke: {
          width: strokeWidths,
          curve: 'smooth',
        },
        xaxis: {
          categories: this.yieldDates,
          labels: {
            style: { fontSize: '10px', fontWeight: 500 },
            rotate: -45,
            rotateAlways: this.yieldDates.length > 15,
          },
        },
        yaxis: [
          {
            seriesName: this.yieldChartSeries[0]?.name,
            title: { text: this.$t('weight_kg'), style: { fontWeight: 600 } },
            labels: { formatter: v => Number(v).toLocaleString() },
          },
          ...Array(Math.max(0, totalSeries - 2)).fill({show: false}),
          {
            opposite: true,
            seriesName: this.yieldChartSeries[lineIndices[0]]?.name,
            title: { text: this.$t('yield_pct'), style: { fontWeight: 600 } },
            labels: { formatter: v => Number(v).toFixed(1) + '%' },
            min: 0,
          },
        ],
        dataLabels: { enabled: false },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          fontWeight: 600,
          fontSize: '11px',
          itemMargin: { horizontal: 8, vertical: 4 },
        },
        tooltip: {
          shared: true,
          intersect: false,
          y: {
            formatter: (value, { seriesIndex }) => {
              if (lineIndices.includes(seriesIndex)) return Number(value).toFixed(2) + '%';
              return Number(value).toLocaleString() + ' KG';
            },
          },
        },
        grid: {
          borderColor: 'rgba(0,0,0,0.06)',
          strokeDashArray: 3,
        },
      };
    },

    fetchProductionCharges() {
      return this.charge.fetchProductionCharges;
    },
    items() {
      return this.charge.production_fetch_items;
    },
    sigma() {
      return [
        { name: 'total_price', icon: 'mdi-cash', paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'] },
        { name: 'total_weight', icon: 'mdi-weight-kilogram', paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'] },
      ];
    },
    relations() {
      return {
        supplier_ids: { source: this.suppliers, label: 'name' },
        truck_ids: { source: this.trucks, label: 'registration_plate' },
        ship_id: { source: this.ships, label: 'name' },
        origin_id: { source: this.origins, label: 'name' },
        fishery_id: { source: this.fisheries, label: 'name' },
        charge_category_id: { source: this.charge_categories, label: 'name' },
      };
    },
    advanced_relations() {
      return {
        warehouse_id: { path: 'warehouse_stockable_tracking_number_operations', source: this.warehouses, label: 'name' },
        stockable_type: { paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'] },
        stockable_id: { paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'] },
        fish_family_id: { paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'], source: this.fish_families, label: `name_${this.main.lang.code}` },
      };
    },
    headers() {
      return [
        { key: 'data-table-expand', align: 'center', visible: true },
        { align: 'center', title: this.$t('date'), key: 'date', visible: true },
        { align: 'center', title: this.$t('category'), key: 'charge_category_id', visible: true },
        { align: 'center', title: this.$t('ship'), key: 'ship_id', visible: true },
        { align: 'center', title: this.$t('tracking_number_short'), key: 'tracking_numbers', visible: true },
        { align: 'center', title: this.$t('production_tracking_numbers'), key: 'production_tracking_numbers', visible: true },
        { align: 'center', title: this.$t('suppliers'), key: 'supplier_ids', visible: true },
        { align: 'center', title: this.$t('truck_id'), key: 'truck_ids', visible: true },
        { align: 'center', title: this.$t('weight_kg'), key: 'total_weight', visible: true },
        { align: 'center', title: this.$t('price_dh', { currency: this.main.currency }), key: 'total_price', visible: true },
        { align: 'center', title: this.$t('origin'), key: 'origin_id', visible: true },
        { align: 'center', title: this.$t('actions'), key: 'actions', visible: true, sortable: false },
      ];
    },
    detail_headers() {
      return [
        { align: 'center', title: this.$t('classification'), key: 'classification', visible: true },
        { align: 'center', title: this.$t('product'), key: 'product', visible: true },
        { align: 'center', title: this.$t('product_family'), key: 'product_family', visible: true },
        { align: 'center', title: this.$t('warehouse'), key: 'warehouse', visible: true },
        { align: 'center', title: this.$t('truck_id'), key: 'truck_ids', visible: true },
        { align: 'center', title: this.$t('total_weight_on_kg'), key: 'total_weight', visible: true },
        { align: 'center', title: this.$t('unit_price_currency', { currency: this.main.currency }), key: 'unit_price', visible: true },
        { align: 'center', title: this.$t('total_price_on', { currency: this.main.currency }), key: 'total_price', visible: true },
        { align: 'center', title: this.$t('package_type'), key: 'package_type_id', visible: true },
        { align: 'center', title: this.$t('total_package_count_un'), key: 'total_package_count', visible: true },
      ];
    },
  },
  watch: {
    date: {
      handler() {
        this.fetchYield();
      },
      deep: true,
    },
    yieldPeriod() {
      this.fetchYield();
    },
  },
  mounted() {
    this.fetchYield();
  },
  methods: {
    // ── Yield ──
    async fetchYield() {
      this.yieldLoading = true;
      try {
        const startStr = moment(this.date.start).format('YYYY-MM-DD');
        const endStr   = moment(this.date.end).format('YYYY-MM-DD');
        const res = await axios.get('/api/production-yield', {
          params: { date_from: startStr, date_to: endStr, period: this.yieldPeriod },
        });
        this.yieldDates  = res.data.dates || [];
        this.yieldSeries = res.data.series || [];
      } catch (e) {
        console.error('Error fetching production yield:', e);
        this.yieldDates  = [];
        this.yieldSeries = [];
      }
      this.yieldLoading = false;
    },
    lightenColor(hex, percent) {
      const num = parseInt(hex.replace('#', ''), 16);
      const amt = Math.round(2.55 * percent);
      const R = Math.min(255, (num >> 16) + amt);
      const G = Math.min(255, ((num >> 8) & 0x00FF) + amt);
      const B = Math.min(255, (num & 0x0000FF) + amt);
      return `#${(1 << 24 | R << 16 | G << 8 | B).toString(16).slice(1)}`;
    },
    darkenColor(hex, percent) {
      const num = parseInt(hex.replace('#', ''), 16);
      const amt = Math.round(2.55 * percent);
      const R = Math.max(0, (num >> 16) - amt);
      const G = Math.max(0, ((num >> 8) & 0x00FF) - amt);
      const B = Math.max(0, (num & 0x0000FF) - amt);
      return `#${(1 << 24 | R << 16 | G << 8 | B).toString(16).slice(1)}`;
    },
    update_filtred_items_to_source(items) {
      // Production items don't need to sync back
    },
    get_invoices_delete_selected_length(that) {
      return that.items.filter(i => that.items_selected.includes(i.id))
        .filter(e => e.allow_invoice == true && e.invoice_id == null).length;
    },
    delete_items(ids) {
        this.charge.delete(ids);
    },
    _invoices_print_selected(that) {
      return that.items.filter(i => that.items_selected.includes(i.id))
        .filter(e => e.allow_invoice && e.invoice_id != null).map(i => i.id);
    },
    _exportToExcel(that) {
      if (!that.items_selected || !that.items_selected_length) return;
      const selectedRows = that.processedItems.filter(i => that.items_selected.includes(i.id));
      const cols = that.orderedVisibleHeaders.filter(h => h.key !== 'data-table-select' && h.key !== 'data-table-expand');
      const headerTitles = cols.map(h => h.title ?? h.key);
      const dataAoA = [
        headerTitles,
        ...selectedRows.map(row => cols.map(h => {
          if (h.key === 'date') return row[h.key];
          const v = that.resolveDisplayValue(row, h.key);
          if (v === '') return '';
          const num = parseFloat(v);
          if (!Number.isNaN(num) && String(v).trim() !== '') return num;
          return String(v);
        }))
      ];
      const ws = XLSX.utils.aoa_to_sheet(dataAoA);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Production');
      XLSX.writeFile(wb, `export_production_${new Date().toISOString().slice(0,10)}.xlsx`);
    },
    viewItem(item) {
      this.$router.push({ name: 'view_charge', params: { id: item.id } });
    },
    editItem(item) {
      this.$router.push({ name: 'edit_charge', params: { id: item.id } });
    },
    addProduction(item) {
      this.$router.push({ name: 'add_production', params: { chargeId: item.id } });
    },
    viewProduction(item) {
      this.$router.push({ name: 'view_production', params: { id: item.production.id } });
    },
  },
};
</script>

<style scoped>
/* ============================================================
   Production List — Premium Design
   ============================================================ */

.pl-page {
  font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
}

/* ── Hero ── */
.pl-hero-bg {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #0d47a1 0%, #1565C0 30%, #1E88E5 60%, #42A5F5 100%);
  border-radius: inherit;
}

.pl-hero-inner {
  position: relative; z-index: 1;
  padding: 28px 30px;
  color: #fff;
}

.pl-hero-icon {
  width: 54px; height: 54px;
  border-radius: 16px;
  background: rgba(255, 255, 255, .15);
  backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255, 255, 255, .2);
  transition: transform 0.3s ease;
}

.pl-hero-icon:hover {
  transform: scale(1.05);
}

.pl-hero-title {
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: 0.2px;
  line-height: 1.3;
}

.pl-hero-inner .text-caption {
  font-size: 0.8rem !important;
  font-weight: 400;
  letter-spacing: 0.2px;
}

/* ── Chart Section ── */
.pl-chart-section {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.pl-chart-header {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.pl-chart-title {
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.2px;
}

.pl-chart-area {
  flex: 1;
  min-height: 220px;
  border-radius: 14px;
  border: 1px dashed rgba(var(--v-border-color), .15);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.2s ease;
}

.pl-chart-area:hover {
  border-color: rgba(var(--v-border-color), .25);
}

.pl-chart-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  opacity: 0.5;
}

.pl-chart-placeholder-text {
  font-size: 0.78rem;
  font-weight: 500;
  text-align: center;
  max-width: 240px;
  line-height: 1.4;
}
</style>
