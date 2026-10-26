<template>
  <v-card class="jupiter-chart-card rounded-2xl mb-4" elevation="1">
    <v-card-text class="pa-3 pa-sm-5">
      <!-- Section 1: Descriptive Header with Ambient Glow & Controls -->
      <div class="chart-header-block mb-3 p-3 rounded-xl border">
        <div class="d-flex align-center justify-space-between flex-wrap ga-3">
          <!-- Title, Icon & Worker Status Badge -->
          <div class="d-flex align-center ga-3">
            <div class="chart-mini-icon">
              <v-icon size="24" color="#0891B2">mdi-chart-areaspline</v-icon>
            </div>
            <div>
              <div class="d-flex align-center ga-2 flex-wrap">
                <span class="text-subtitle-1 font-weight-bold text-slate-800">{{ $t('factory_operations_yield_chart') || 'Factory Operations & Process Yield Analytics' }}</span>
                <v-chip v-if="categories.length" size="x-small" variant="flat" class="data-points-badge font-weight-bold ml-1">
                  {{ categories.length }} {{ periodicity === 'daily' ? ($t('days_selected') || 'Days') : periodicity === 'monthly' ? ($t('months') || 'Months') : ($t('years') || 'Years') }}
                </v-chip>
                <v-chip v-if="workerBusy" size="x-small" variant="tonal" color="cyan" class="font-weight-medium">
                  <v-progress-circular indeterminate size="10" width="1.5" class="mr-1"></v-progress-circular>
                  {{ $t('worker_crunching') || 'Crunching Data...' }}
                </v-chip>
              </div>
              <div class="text-caption text-medium-emphasis mt-1 header-desc">
                {{ $t('factory_operations_chart_desc') || 'Cumulative production balance, factory operations, and process yields across time' }}
              </div>
            </div>
          </div>

          <!-- Top Mode Switchers (View Mode, Chart Type, Measure) -->
          <div class="d-flex align-center flex-wrap ga-2">
            <!-- View Selector: Production Balance (exact like production/list) vs Factory Operations -->
            <SegmentedPillToggle
              v-model="chartDatasetMode"
              :items="datasetModeItems"
              color="#0891B2"
              size="small"
              @change="triggerWorkerRecalc"
            />

            <!-- Visual Type: Stacked / Columns / Line / Area -->
            <SegmentedPillToggle
              v-model="chartVisualMode"
              :items="visualModeItems"
              color="#0891B2"
              size="small"
              @change="triggerWorkerRecalc"
            />

            <!-- Measure (Weight vs Price) - only relevant for factory_operations -->
            <SegmentedPillToggle
              v-if="chartDatasetMode === 'operations'"
              v-model="activeMeasure"
              :items="measureModeItems"
              color="#0891B2"
              size="small"
              @change="triggerWorkerRecalc"
            />
          </div>
        </div>
      </div>

      <!-- Section 2: Data Visualisation Filter Bar (Category Selectors when in operations mode) -->
      <div v-if="chartDatasetMode === 'operations'" class="filters-ribbon d-flex align-center justify-space-between flex-wrap ga-2.5 mb-3 pa-2.5 rounded-xl border">
        <div class="d-flex align-center flex-wrap ga-2">
          <span class="text-caption font-weight-bold text-medium-emphasis d-flex align-center mr-1">
            <v-icon size="14" class="mr-1 text-cyan-darken-1">mdi-filter-variant</v-icon>
            {{ $t('filter_categories') || 'Filter Categories' }}:
          </span>

          <!-- Charges Category Selector Menu -->
          <v-menu v-model="chargeMenu" :close-on-content-click="false" location="bottom start">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                size="small"
                rounded="pill"
                variant="outlined"
                :color="isAllChargesSelected ? '#EF4444' : '#DC2626'"
                class="category-select-btn text-none"
              >
                <v-icon start size="15" class="mr-1">mdi-cash-minus</v-icon>
                {{ $t('charges') || 'Charges' }}:
                <strong class="ml-1">{{ chargeSelectionLabel }}</strong>
                <v-icon end size="14">mdi-chevron-down</v-icon>
              </v-btn>
            </template>
            <v-card min-width="260" max-width="320" class="rounded-xl pa-2 elevation-4">
              <div class="d-flex align-center justify-space-between px-2 py-1 border-b">
                <span class="text-caption font-weight-bold">{{ $t('charges_categories') || 'Charges Categories' }}</span>
                <v-btn size="x-small" variant="text" color="primary" @click="toggleAllCharges">
                  {{ isAllChargesSelected ? ($t('clear') || 'Clear') : ($t('all') || 'All') }}
                </v-btn>
              </div>
              <v-list density="compact" class="py-1">
                <v-list-item
                  v-for="cat in availableChargeCategories"
                  :key="cat.id"
                  @click="toggleChargeCat(cat.id)"
                  class="rounded-lg mb-0.5"
                >
                  <template #prepend>
                    <v-checkbox-btn :model-value="selectedChargeCatIds.includes(cat.id)" color="#EF4444" density="compact" />
                  </template>
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ cat['name_' + (main.lang?.short || 'en')] || cat.name || ('Cat #' + cat.id) }}
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card>
          </v-menu>

          <!-- Productions Category Selector Menu -->
          <v-menu v-model="prodMenu" :close-on-content-click="false" location="bottom start">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                size="small"
                rounded="pill"
                variant="outlined"
                :color="isAllProdSelected ? '#06B6D4' : '#0891B2'"
                class="category-select-btn text-none"
              >
                <v-icon start size="15" class="mr-1">mdi-factory</v-icon>
                {{ $t('production') || 'Production' }}:
                <strong class="ml-1">{{ prodSelectionLabel }}</strong>
                <v-icon end size="14">mdi-chevron-down</v-icon>
              </v-btn>
            </template>
            <v-card min-width="260" max-width="320" class="rounded-xl pa-2 elevation-4">
              <div class="d-flex align-center justify-space-between px-2 py-1 border-b">
                <span class="text-caption font-weight-bold">{{ $t('production_categories') || 'Production Categories' }}</span>
                <v-btn size="x-small" variant="text" color="primary" @click="toggleAllProd">
                  {{ isAllProdSelected ? ($t('clear') || 'Clear') : ($t('all') || 'All') }}
                </v-btn>
              </div>
              <v-list density="compact" class="py-1">
                <v-list-item
                  v-for="cat in availableProdCategories"
                  :key="cat.id"
                  @click="toggleProdCat(cat.id)"
                  class="rounded-lg mb-0.5"
                >
                  <template #prepend>
                    <v-checkbox-btn :model-value="selectedProdCatIds.includes(cat.id)" color="#06B6D4" density="compact" />
                  </template>
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ cat['name_' + (main.lang?.short || 'en')] || cat.name || ('Cat #' + cat.id) }}
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card>
          </v-menu>

          <!-- Shippings Category Selector Menu -->
          <v-menu v-model="shipMenu" :close-on-content-click="false" location="bottom start">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                size="small"
                rounded="pill"
                variant="outlined"
                :color="isAllShipSelected ? '#3B82F6' : '#2563EB'"
                class="category-select-btn text-none"
              >
                <v-icon start size="15" class="mr-1">mdi-truck-fast</v-icon>
                {{ $t('shippings') || 'Shippings' }}:
                <strong class="ml-1">{{ shipSelectionLabel }}</strong>
                <v-icon end size="14">mdi-chevron-down</v-icon>
              </v-btn>
            </template>
            <v-card min-width="260" max-width="320" class="rounded-xl pa-2 elevation-4">
              <div class="d-flex align-center justify-space-between px-2 py-1 border-b">
                <span class="text-caption font-weight-bold">{{ $t('shipping_categories') || 'Shipping Categories' }}</span>
                <v-btn size="x-small" variant="text" color="primary" @click="toggleAllShip">
                  {{ isAllShipSelected ? ($t('clear') || 'Clear') : ($t('all') || 'All') }}
                </v-btn>
              </div>
              <v-list density="compact" class="py-1">
                <v-list-item
                  v-for="cat in availableShipCategories"
                  :key="cat.id"
                  @click="toggleShipCat(cat.id)"
                  class="rounded-lg mb-0.5"
                >
                  <template #prepend>
                    <v-checkbox-btn :model-value="selectedShipCatIds.includes(cat.id)" color="#3B82F6" density="compact" />
                  </template>
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ cat['name_' + (main.lang?.short || 'en')] || cat.name || ('Cat #' + cat.id) }}
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card>
          </v-menu>
        </div>

        <v-btn
          size="x-small"
          variant="text"
          color="warning"
          prepend-icon="mdi-restore"
          class="text-none font-weight-bold"
          @click="resetCategorySelections"
        >
          {{ $t('reset_filters') || 'Reset Filters' }}
        </v-btn>
      </div>

      <!-- Section 3: Process Yield Row with Line Stroke Styles (Solid, Dashed, Dotted) -->
      <div class="yield-section-strip d-flex align-center justify-space-between flex-wrap ga-2.5 mb-3 pa-2.5 rounded-xl border">
        <!-- Yield Info & Formula Hint -->
        <div class="d-flex align-center flex-wrap ga-2">
          <div class="d-flex align-center ga-1.5 mr-1">
            <v-icon size="16" color="#10B981">mdi-chart-line</v-icon>
            <span class="text-caption font-weight-bold text-emerald-800">{{ $t('yield_calculation') || 'Process Yield' }}:</span>
          </div>
          <span class="text-caption text-medium-emphasis formula-hint d-none d-md-inline">
            ({{ $t('process_yield_formula_hint') || 'Yield % = Guano Weight / Frozen Fish Weight × 100' }})
          </span>
        </div>

        <!-- Process Type Toggle Pills with Stroke Indicators -->
        <div class="d-flex align-center flex-wrap ga-1.5">
          <!-- Master Yield Toggle -->
          <v-chip
            :variant="showYields ? 'flat' : 'outlined'"
            color="#10B981"
            size="small"
            class="font-weight-bold cursor-pointer"
            @click="toggleMasterYield"
          >
            <v-icon start size="14" class="mr-1">{{ showYields ? 'mdi-eye' : 'mdi-eye-off' }}</v-icon>
            {{ $t('yield_calculation') || 'Process Yield' }}
          </v-chip>

          <!-- Individual Process Type Toggle Pills with Stroke Menu -->
          <div
            v-for="(pt, idx) in yieldsList"
            :key="pt.id"
            class="d-inline-flex align-center yield-pill-group"
          >
            <v-chip
              :variant="isProcessActive(pt.id) ? 'flat' : 'outlined'"
              :color="getProcessColor(pt, idx)"
              size="small"
              class="font-weight-bold cursor-pointer transition-all"
              @click="toggleProcessType(pt.id)"
            >
              <v-icon start size="13" class="mr-1">{{ getStrokeIcon(pt.id) }}</v-icon>
              {{ pt.name }}
              <span class="ml-1 opacity-80">({{ pt.overall_yield }}%)</span>
            </v-chip>

            <!-- Line Style Switcher Menu (Solid, Dashed, Dotted) -->
            <v-menu location="bottom end">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon
                  size="x-small"
                  variant="text"
                  :color="getProcessColor(pt, idx)"
                  class="stroke-select-btn ml-0.5"
                  :title="`Stroke: ${processLineStyles[pt.id] || 'solid'}`"
                >
                  <v-icon size="13">mdi-dots-vertical</v-icon>
                </v-btn>
              </template>
              <v-list density="compact" class="py-1">
                <v-list-item @click="setProcessStroke(pt.id, 'solid')">
                  <template #prepend><v-icon size="16" color="#10B981">mdi-minus</v-icon></template>
                  <v-list-item-title class="text-caption">{{ $t('solid_line') || 'Solid Line' }}</v-list-item-title>
                </v-list-item>
                <v-list-item @click="setProcessStroke(pt.id, 'dashed')">
                  <template #prepend><v-icon size="16" color="#F59E0B">mdi-ray-vertex</v-icon></template>
                  <v-list-item-title class="text-caption">{{ $t('dashed_line') || 'Dashed Line' }}</v-list-item-title>
                </v-list-item>
                <v-list-item @click="setProcessStroke(pt.id, 'dotted')">
                  <template #prepend><v-icon size="16" color="#8B5CF6">mdi-dots-horizontal</v-icon></template>
                  <v-list-item-title class="text-caption">{{ $t('dotted_line') || 'Dotted Line' }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </div>
        </div>
      </div>

      <!-- Section 4: Chart Display Canvas -->
      <div v-if="loading && !categories.length" class="d-flex flex-column align-center justify-center py-16">
        <v-progress-circular indeterminate color="#0891B2" size="44" width="4" />
        <span class="text-caption text-medium-emphasis mt-3">{{ $t('loading') || 'Loading...' }}</span>
      </div>

      <div v-else-if="!computedSeries.length" class="d-flex flex-column align-center justify-center py-16">
        <v-icon size="48" color="#CBD5E1">mdi-chart-line-variant</v-icon>
        <div class="text-subtitle-2 text-medium-emphasis mt-2">{{ $t('no_charts_found_matching') || 'No data found for the selected range and filters' }}</div>
      </div>

      <div v-else class="chart-canvas-wrap">
        <apexchart
          :key="chartRenderKey"
          type="line"
          :height="460"
          :options="apexOptions"
          :series="computedSeries"
          ref="jupiterChart"
        />
      </div>
    </v-card-text>
  </v-card>
</template>

<script>
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useMain } from '@/stores/Main';
import { useWebWorkerFn } from '@vueuse/core';

// Pure WebWorker function for non-blocking data crunching
const transformChartDataWorker = (payload) => {
  const {
    datasetMode,
    analytics,
    operations,
    yields,
    selectedChargeCatIds,
    selectedProdCatIds,
    selectedShipCatIds,
    activeProcessTypeIds,
    processLineStyles,
    measure,
    chartType,
    showYields,
    categories,
  } = payload;

  const series = [];
  const colType = (chartType === 'stacked' || chartType === 'column') ? 'column' : (chartType === 'area' ? 'area' : 'line');

  if (datasetMode === 'balance') {
    // ── Mode 1: Production Analytics & Mass Equilibrium (exact like production/list) ──
    const freshData = (analytics?.fresh_reception || []).map(p => Number(p.y || 0));
    const frozenData = (analytics?.frozen_production || []).map(p => Number(p.y || 0));
    const guanoData = (analytics?.guano_production || []).map(p => Number(p.y || 0));
    const eqData = (analytics?.equilibrium || []).map(p => Number(p.y || 0));

    series.push({
      name: 'Fresh Reception (Kg)',
      type: colType,
      data: freshData,
      color: '#42A5F5',
      measure: 'weight',
      strokeDash: 0,
    });

    series.push({
      name: 'Frozen Production (Kg)',
      type: colType,
      data: frozenData,
      color: '#AB47BC',
      measure: 'weight',
      strokeDash: 0,
    });

    series.push({
      name: 'Guano Production (Kg)',
      type: colType,
      data: guanoData,
      color: '#8D6E63',
      measure: 'weight',
      strokeDash: 0,
    });

    series.push({
      name: 'Equilibrium (Kg)',
      type: colType,
      data: eqData,
      color: '#4CAF50',
      measure: 'weight',
      strokeDash: 0,
    });
  } else {
    // ── Mode 2: Factory Operations (Charges, Productions, Shippings with Category Filtering) ──
    // 1. Charges Series
    if (operations?.charges) {
      const byCat = operations.charges.by_category || [];
      const hasFilter = selectedChargeCatIds && selectedChargeCatIds.length > 0 && selectedChargeCatIds.length < byCat.length;

      if (hasFilter) {
        for (const cat of byCat) {
          if (selectedChargeCatIds.includes(cat.id)) {
            series.push({
              name: `Charge: ${cat.name_fr || cat.name || ('Cat #' + cat.id)}`,
              type: colType,
              data: measure === 'price' ? cat.price : cat.weight,
              measure: measure,
              color: '#EF4444',
              strokeDash: 0,
            });
          }
        }
      } else {
        series.push({
          name: measure === 'price' ? 'Total Charges (DH)' : 'Total Charges (Kg)',
          type: colType,
          data: measure === 'price' ? operations.charges.total_price : operations.charges.total_weight,
          measure: measure,
          color: '#EF4444',
          strokeDash: 0,
        });
      }
    }

    // 2. Productions Series
    if (operations?.productions) {
      const byCat = operations.productions.by_category || [];
      const hasFilter = selectedProdCatIds && selectedProdCatIds.length > 0 && selectedProdCatIds.length < byCat.length;

      if (hasFilter) {
        for (const cat of byCat) {
          if (selectedProdCatIds.includes(cat.id)) {
            series.push({
              name: `Prod: ${cat.name_fr || cat.name || ('Cat #' + cat.id)}`,
              type: colType,
              data: measure === 'price' ? cat.price : cat.weight,
              measure: measure,
              color: '#06B6D4',
              strokeDash: 0,
            });
          }
        }
      } else {
        series.push({
          name: measure === 'price' ? 'Total Productions (DH)' : 'Total Productions (Kg)',
          type: colType,
          data: measure === 'price' ? operations.productions.total_price : operations.productions.total_weight,
          measure: measure,
          color: '#06B6D4',
          strokeDash: 0,
        });
      }
    }

    // 3. Shippings Series
    if (operations?.shippings) {
      const byCat = operations.shippings.by_category || [];
      const hasFilter = selectedShipCatIds && selectedShipCatIds.length > 0 && selectedShipCatIds.length < byCat.length;

      if (hasFilter) {
        for (const cat of byCat) {
          if (selectedShipCatIds.includes(cat.id)) {
            series.push({
              name: `Ship: ${cat.name_fr || cat.name || ('Cat #' + cat.id)}`,
              type: colType,
              data: measure === 'price' ? cat.price : cat.weight,
              measure: measure,
              color: '#3B82F6',
              strokeDash: 0,
            });
          }
        }
      } else {
        series.push({
          name: measure === 'price' ? 'Total Shippings (DH)' : 'Total Shippings (Kg)',
          type: colType,
          data: measure === 'price' ? operations.shippings.total_price : operations.shippings.total_weight,
          measure: measure,
          color: '#3B82F6',
          strokeDash: 0,
        });
      }
    }
  }

  // ── Process Yield Series (% Guano / Frozen Fish) with stroke styles (Solid, Dashed, Dotted) ──
  if (showYields && yields && yields.length) {
    const defaultColors = ['#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#14B8A6'];
    for (let i = 0; i < yields.length; i++) {
      const yItem = yields[i];
      if (activeProcessTypeIds.includes(yItem.id)) {
        const style = processLineStyles[yItem.id] || (i % 3 === 1 ? 'dashed' : i % 3 === 2 ? 'dotted' : 'solid');
        const dashArray = style === 'dashed' ? 7 : style === 'dotted' ? 2 : 0;
        series.push({
          name: `Yield ${yItem.name} (%)`,
          type: 'line',
          data: yItem.yield_percent || [],
          measure: 'yield',
          process_id: yItem.id,
          color: yItem.productivity_color || defaultColors[i % defaultColors.length],
          strokeDash: dashArray,
          strokeType: style,
        });
      }
    }
  }

  return { series };
};

export default {
  name: 'JupiterChart',
  components: { SegmentedPillToggle },
  props: {
    analytics: {
      type: Object,
      default: () => ({ fresh_reception: [], frozen_production: [], guano_production: [], equilibrium: [] }),
    },
    operations: {
      type: Object,
      default: () => ({ charges: {}, productions: {}, shippings: {} }),
    },
    yields: {
      type: Array,
      default: () => [],
    },
    categories: {
      type: Array,
      default: () => [],
    },
    categoryOptions: {
      type: Object,
      default: () => ({
        charge_categories: [],
        production_categories: [],
        shipping_categories: [],
        fish_process_types: [],
      }),
    },
    loading: {
      type: Boolean,
      default: false,
    },
    periodicity: {
      type: String,
      default: 'daily',
    },
  },
  emits: ['update:periodicity'],
  data() {
    return {
      chartDatasetMode: 'balance', // 'balance' (production/list style) | 'operations' (charges/prod/ship)
      chartVisualMode: 'column', // 'column' | 'stacked' | 'line' | 'area'
      activeMeasure: 'weight',
      chargeMenu: false,
      prodMenu: false,
      shipMenu: false,
      selectedChargeCatIds: [],
      selectedProdCatIds: [],
      selectedShipCatIds: [],
      activeProcessTypeIds: [],
      processLineStyles: {},
      showYields: true,
      computedSeries: [],
      workerBusy: false,
      workerFn: null,
      chartRenderKey: 1,
    };
  },
  computed: {
    main() { return useMain(); },
    datasetModeItems() {
      return [
        { value: 'balance', label: this.$t('production_balance') || 'Production Balance', icon: 'mdi-scale-balance' },
        { value: 'operations', label: this.$t('factory_operations') || 'Operations (Charges/Ship)', icon: 'mdi-factory' },
      ];
    },
    visualModeItems() {
      return [
        { value: 'column', label: this.$t('columns_bars') || 'Columns', icon: 'mdi-chart-box' },
        { value: 'stacked', label: this.$t('stacked_columns') || 'Stacked', icon: 'mdi-chart-box-outline' },
        { value: 'line', label: this.$t('line_chart') || 'Line', icon: 'mdi-chart-line' },
        { value: 'area', label: this.$t('area_chart') || 'Area', icon: 'mdi-chart-areaspline' },
      ];
    },
    measureModeItems() {
      return [
        { value: 'weight', label: this.$t('weight_kg') || 'Weight (Kg)', icon: 'mdi-weight-kilogram' },
        { value: 'price', label: this.$t('price_dh') || 'Price (DH)', icon: 'mdi-cash' },
      ];
    },
    availableChargeCategories() {
      return this.operations?.charges?.by_category || this.categoryOptions?.charge_categories || [];
    },
    availableProdCategories() {
      return this.operations?.productions?.by_category || this.categoryOptions?.production_categories || [];
    },
    availableShipCategories() {
      return this.operations?.shippings?.by_category || this.categoryOptions?.shipping_categories || [];
    },
    yieldsList() {
      return this.yields || [];
    },
    isAllChargesSelected() {
      return !this.selectedChargeCatIds.length || this.selectedChargeCatIds.length === this.availableChargeCategories.length;
    },
    chargeSelectionLabel() {
      if (this.isAllChargesSelected) return this.$t('all_categories') || 'All';
      return `${this.selectedChargeCatIds.length}/${this.availableChargeCategories.length}`;
    },
    isAllProdSelected() {
      return !this.selectedProdCatIds.length || this.selectedProdCatIds.length === this.availableProdCategories.length;
    },
    prodSelectionLabel() {
      if (this.isAllProdSelected) return this.$t('all_categories') || 'All';
      return `${this.selectedProdCatIds.length}/${this.availableProdCategories.length}`;
    },
    isAllShipSelected() {
      return !this.selectedShipCatIds.length || this.selectedShipCatIds.length === this.availableShipCategories.length;
    },
    shipSelectionLabel() {
      if (this.isAllShipSelected) return this.$t('all_categories') || 'All';
      return `${this.selectedShipCatIds.length}/${this.availableShipCategories.length}`;
    },
    apexOptions() {
      const isDark = this.main.is_dark;
      const isStacked = this.chartVisualMode === 'stacked';
      const isPrice = this.activeMeasure === 'price' && this.chartDatasetMode === 'operations';
      const primaryTitle = isPrice ? 'Price (MAD)' : 'Weight (Kg)';

      const nonYieldSeries = this.computedSeries.filter(s => s.measure !== 'yield');
      const yieldSeries = this.computedSeries.filter(s => s.measure === 'yield');

      const firstBarName = nonYieldSeries[0]?.name || 'Operations';
      const firstYieldName = yieldSeries[0]?.name || 'Yield';

      const yaxisArr = [];

      // Non-yield series positional entries sharing the firstBarName
      nonYieldSeries.forEach((s, idx) => {
        yaxisArr.push({
          seriesName: firstBarName,
          show: idx === 0,
          title: idx === 0 ? {
            text: primaryTitle,
            style: { color: isDark ? '#94A3B8' : '#0891B2', fontSize: '11px', fontWeight: 700 },
          } : undefined,
          labels: {
            style: { colors: isDark ? '#94A3B8' : '#64748B', fontSize: '10px' },
            formatter: (v) => {
              if (v == null) return '0';
              if (Math.abs(v) >= 1000000) return (v / 1000000).toFixed(1) + 'M';
              if (Math.abs(v) >= 1000) return (v / 1000).toFixed(0) + 'k';
              return Math.round(v).toString();
            },
          },
        });
      });

      // Yield series positional entries sharing the firstYieldName (opposite secondary axis)
      yieldSeries.forEach((ys, idx) => {
        yaxisArr.push({
          opposite: true,
          seriesName: firstYieldName,
          show: idx === 0,
          title: idx === 0 ? {
            text: (this.$t('yield') || 'Yield') + ' (%)',
            style: { color: '#10B981', fontSize: '11px', fontWeight: 700 },
          } : undefined,
          labels: {
            style: { colors: '#10B981', fontSize: '10px' },
            formatter: (v) => (v != null ? `${Math.round(v)}%` : '0%'),
          },
          min: 0,
        });
      });

      const eqList = this.analytics?.equilibrium || [];

      return {
        chart: {
          id: 'jupiter-operations-chart',
          fontFamily: 'Inter, sans-serif',
          background: 'transparent',
          toolbar: {
            show: true,
            tools: { download: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
          },
          animations: { enabled: true, easing: 'easeinout', speed: 450 },
          stacked: isStacked,
        },
        theme: { mode: isDark ? 'dark' : 'light' },
        stroke: {
          width: this.computedSeries.map(s => s.measure === 'yield' ? 3 : (this.chartVisualMode === 'column' || isStacked ? 0 : 2.5)),
          curve: 'smooth',
          dashArray: this.computedSeries.map(s => s.strokeDash || 0),
        },
        plotOptions: {
          bar: {
            columnWidth: '60%',
            borderRadius: 5,
            borderRadiusApplication: 'end',
            colors: {
              ranges: [
                { from: -999999999, to: -0.001, color: '#EF5350' },
              ],
            },
          },
        },
        colors: this.computedSeries.map(s => s.color || '#0891B2'),
        dataLabels: { enabled: false },
        xaxis: {
          categories: this.categories,
          labels: {
            style: { colors: isDark ? '#94A3B8' : '#64748B', fontSize: '11px' },
            rotate: -35,
            rotateAlways: this.categories.length > 20,
          },
          axisBorder: { color: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' },
          axisTicks: { color: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' },
        },
        yaxis: yaxisArr.length ? yaxisArr : [{ title: { text: primaryTitle } }],
        grid: {
          borderColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
          strokeDashArray: 3,
        },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          labels: { colors: isDark ? '#E2E8F0' : '#1E293B' },
          markers: { radius: 6 },
        },
        tooltip: {
          shared: true,
          intersect: false,
          theme: isDark ? 'dark' : 'light',
          y: {
            formatter: (val, opts) => {
              if (val == null) return '—';
              const seriesObj = this.computedSeries[opts?.seriesIndex];
              if (seriesObj?.measure === 'yield') {
                return `${Number(val).toFixed(2)}%`;
              }
              const isPriceLocal = this.activeMeasure === 'price' && this.chartDatasetMode === 'operations';
              const unit = isPriceLocal ? ' MAD' : ' Kg';
              let txt = Number(val).toLocaleString() + unit;

              // If this is equilibrium series, append surplus/shortage tag
              if (seriesObj?.name === 'Equilibrium (Kg)' && eqList[opts?.dataPointIndex]) {
                const item = eqList[opts?.dataPointIndex];
                txt += ` (${item.label || (val >= 0 ? 'surplus' : 'shortage')})`;
              }
              return txt;
            },
          },
        },
      };
    },
  },
  watch: {
    analytics: {
      handler() { this.triggerWorkerRecalc(); },
      deep: true,
    },
    operations: {
      handler() {
        this.initSelections();
        this.triggerWorkerRecalc();
      },
      deep: true,
    },
    yields: {
      handler() {
        this.initYieldSelections();
        this.triggerWorkerRecalc();
      },
      deep: true,
    },
    categories() {
      this.triggerWorkerRecalc();
    },
  },
  created() {
    try {
      const { workerFn } = useWebWorkerFn(transformChartDataWorker);
      this.workerFn = workerFn;
    } catch (e) {
      console.warn('[JupiterWorker] Worker fallback:', e);
      this.workerFn = null;
    }
  },
  mounted() {
    this.initSelections();
    this.initYieldSelections();
    this.triggerWorkerRecalc();
  },
  methods: {
    initSelections() {
      if (this.availableChargeCategories.length && !this.selectedChargeCatIds.length) {
        this.selectedChargeCatIds = this.availableChargeCategories.map(c => c.id);
      }
      if (this.availableProdCategories.length && !this.selectedProdCatIds.length) {
        this.selectedProdCatIds = this.availableProdCategories.map(c => c.id);
      }
      if (this.availableShipCategories.length && !this.selectedShipCatIds.length) {
        this.selectedShipCatIds = this.availableShipCategories.map(c => c.id);
      }
    },
    initYieldSelections() {
      if (this.yieldsList.length && !this.activeProcessTypeIds.length) {
        this.activeProcessTypeIds = this.yieldsList.map(y => y.id);
      }
      this.yieldsList.forEach((y, i) => {
        if (!this.processLineStyles[y.id]) {
          this.processLineStyles[y.id] = i % 3 === 1 ? 'dashed' : i % 3 === 2 ? 'dotted' : 'solid';
        }
      });
    },
    isProcessActive(id) {
      return this.activeProcessTypeIds.includes(id);
    },
    getProcessColor(pt, idx) {
      if (pt.productivity_color) return pt.productivity_color;
      const palette = ['#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#14B8A6'];
      return palette[idx % palette.length];
    },
    getStrokeIcon(id) {
      const style = this.processLineStyles[id] || 'solid';
      if (style === 'dashed') return 'mdi-ray-vertex';
      if (style === 'dotted') return 'mdi-dots-horizontal';
      return 'mdi-minus';
    },
    setProcessStroke(id, style) {
      this.processLineStyles[id] = style;
      this.triggerWorkerRecalc();
    },
    toggleMasterYield() {
      this.showYields = !this.showYields;
      this.triggerWorkerRecalc();
    },
    toggleProcessType(id) {
      if (this.activeProcessTypeIds.includes(id)) {
        this.activeProcessTypeIds = this.activeProcessTypeIds.filter(i => i !== id);
      } else {
        this.activeProcessTypeIds.push(id);
      }
      this.triggerWorkerRecalc();
    },
    toggleChargeCat(id) {
      if (this.selectedChargeCatIds.includes(id)) {
        this.selectedChargeCatIds = this.selectedChargeCatIds.filter(i => i !== id);
      } else {
        this.selectedChargeCatIds.push(id);
      }
      this.triggerWorkerRecalc();
    },
    toggleAllCharges() {
      if (this.isAllChargesSelected) {
        this.selectedChargeCatIds = [];
      } else {
        this.selectedChargeCatIds = this.availableChargeCategories.map(c => c.id);
      }
      this.triggerWorkerRecalc();
    },
    toggleProdCat(id) {
      if (this.selectedProdCatIds.includes(id)) {
        this.selectedProdCatIds = this.selectedProdCatIds.filter(i => i !== id);
      } else {
        this.selectedProdCatIds.push(id);
      }
      this.triggerWorkerRecalc();
    },
    toggleAllProd() {
      if (this.isAllProdSelected) {
        this.selectedProdCatIds = [];
      } else {
        this.selectedProdCatIds = this.availableProdCategories.map(c => c.id);
      }
      this.triggerWorkerRecalc();
    },
    toggleShipCat(id) {
      if (this.selectedShipCatIds.includes(id)) {
        this.selectedShipCatIds = this.selectedShipCatIds.filter(i => i !== id);
      } else {
        this.selectedShipCatIds.push(id);
      }
      this.triggerWorkerRecalc();
    },
    toggleAllShip() {
      if (this.isAllShipSelected) {
        this.selectedShipCatIds = [];
      } else {
        this.selectedShipCatIds = this.availableShipCategories.map(c => c.id);
      }
      this.triggerWorkerRecalc();
    },
    resetCategorySelections() {
      this.selectedChargeCatIds = this.availableChargeCategories.map(c => c.id);
      this.selectedProdCatIds = this.availableProdCategories.map(c => c.id);
      this.selectedShipCatIds = this.availableShipCategories.map(c => c.id);
      this.activeProcessTypeIds = this.yieldsList.map(y => y.id);
      this.showYields = true;
      this.triggerWorkerRecalc();
    },
    async triggerWorkerRecalc() {
      const payload = {
        datasetMode: this.chartDatasetMode,
        analytics: JSON.parse(JSON.stringify(this.analytics || {})),
        operations: JSON.parse(JSON.stringify(this.operations || {})),
        yields: JSON.parse(JSON.stringify(this.yields || [])),
        categories: JSON.parse(JSON.stringify(this.categories || [])),
        selectedChargeCatIds: [...this.selectedChargeCatIds],
        selectedProdCatIds: [...this.selectedProdCatIds],
        selectedShipCatIds: [...this.selectedShipCatIds],
        activeProcessTypeIds: [...this.activeProcessTypeIds],
        processLineStyles: { ...this.processLineStyles },
        measure: this.activeMeasure,
        chartType: this.chartVisualMode,
        showYields: this.showYields,
      };

      this.workerBusy = true;
      try {
        if (this.workerFn) {
          const res = await this.workerFn(payload);
          this.computedSeries = res.series;
        } else {
          const res = transformChartDataWorker(payload);
          this.computedSeries = res.series;
        }
      } catch (err) {
        console.warn('[JupiterWorker] Worker fallback:', err);
        const res = transformChartDataWorker(payload);
        this.computedSeries = res.series;
      } finally {
        this.workerBusy = false;
        this.chartRenderKey++;
      }
    },
  },
};
</script>

<style scoped>
.jupiter-chart-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(8, 145, 178, 0.18);
  box-shadow: 0 4px 20px rgba(8, 145, 178, 0.05);
  transition: all 0.25s ease;
}

:deep(.v-theme--dark) .jupiter-chart-card,
.v-theme--dark .jupiter-chart-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(6, 182, 212, 0.2);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.chart-header-block {
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.04) 0%, rgba(6, 182, 212, 0.02) 100%);
  border-color: rgba(8, 145, 178, 0.15) !important;
}

:deep(.v-theme--dark) .chart-header-block,
.v-theme--dark .chart-header-block {
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.12) 0%, rgba(6, 182, 212, 0.04) 100%);
  border-color: rgba(6, 182, 212, 0.2) !important;
}

.chart-mini-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.15) 0%, rgba(6, 182, 212, 0.08) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(8, 145, 178, 0.25);
  flex-shrink: 0;
}

.data-points-badge {
  background: rgba(8, 145, 178, 0.12) !important;
  color: #0891B2 !important;
  font-size: 10px !important;
}

.filters-ribbon {
  background: rgba(248, 250, 252, 0.85);
  border-color: rgba(226, 232, 240, 0.9) !important;
}

:deep(.v-theme--dark) .filters-ribbon,
.v-theme--dark .filters-ribbon {
  background: rgba(30, 41, 59, 0.6);
  border-color: rgba(51, 65, 85, 0.8) !important;
}

.category-select-btn {
  font-size: 11px !important;
  letter-spacing: 0.1px;
}

.yield-section-strip {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.04) 0%, rgba(5, 150, 105, 0.02) 100%);
  border-color: rgba(16, 185, 129, 0.2) !important;
}

:deep(.v-theme--dark) .yield-section-strip,
.v-theme--dark .yield-section-strip {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(5, 150, 105, 0.04) 100%);
  border-color: rgba(16, 185, 129, 0.25) !important;
}

.formula-hint {
  font-size: 11px !important;
  font-style: italic;
}

.yield-pill-group {
  margin-right: 4px;
}

.stroke-select-btn {
  opacity: 0.7;
  transition: opacity 0.2s;
}
.stroke-select-btn:hover {
  opacity: 1;
}

.chart-canvas-wrap {
  width: 100%;
  position: relative;
}
</style>
