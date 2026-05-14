<template>
  <v-card class="chart-form-card" elevation="0">
    <!-- Hero -->
    <div class="cf-hero">
      <div class="cf-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="cf-hero-icon"><v-icon size="28" color="white">{{ editing ? 'mdi-pencil-outline' : 'mdi-chart-areaspline' }}</v-icon></div>
          <div>
            <div class="cf-hero-title">{{ editing ? $t('edit_chart_or_stat') : $t('create_chart_or_stat') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('chart_builder_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text>
      <!-- Stepper -->
      <v-stepper v-model="step" alt-labels flat class="bg-transparent elevation-0">
        <v-stepper-header>
          <v-stepper-item :value="1" :complete="step > 1" color="#00BCD4" :title="$t('configuration')" @click="goToStep(1)" style="cursor:pointer" />
          <v-divider /><v-stepper-item :value="2" :complete="step > 2" color="#00BCD4" :title="$t('date_range')" @click="goToStep(2)" style="cursor:pointer" />
          <v-divider /><v-stepper-item :value="3" :complete="step > 3" color="#00BCD4" :title="$t('series_builder')" @click="goToStep(3)" style="cursor:pointer" />
          <v-divider /><v-stepper-item :value="4" color="#00BCD4" :title="$t('preview')" @click="goToStep(4)" style="cursor:pointer" />
        </v-stepper-header>

        <v-stepper-window>
          <!-- STEP 1: Config -->
          <v-stepper-window-item :value="1">
            <v-row class="mt-2">
              <v-col cols="12" md="6">
                <v-text-field v-model="form.title" :label="$t('chart_title')" variant="outlined" rounded density="compact" color="#00BCD4" />
              </v-col>
              <v-col cols="12" md="3">
                <v-select v-model="form.display_type" :items="displayTypes" :label="$t('display_type')" variant="outlined" rounded density="compact" color="#00BCD4" />
              </v-col>
              <v-col v-if="form.display_type === 'chart'" cols="12" md="3">
                <v-select v-model="form.chart_type" :items="chartTypes" :label="$t('chart_type')" variant="outlined" rounded density="compact" color="#00BCD4" />
              </v-col>
              <v-col cols="12" md="4">
                <v-slider v-model="form.grid_cols" :min="3" :max="12" :step="1" :label="$t('grid_columns')" color="#00BCD4" thumb-label />
              </v-col>
              <v-col cols="12" md="4">
                <v-switch v-model="chartOptions.stacked" :label="$t('stacked')" color="#00BCD4" density="compact" hide-details />
              </v-col>
              <v-col cols="12" md="4">
                <v-switch v-model="form.is_active" :label="$t('is_active')" color="#00BCD4" density="compact" hide-details />
              </v-col>
            </v-row>
          </v-stepper-window-item>

          <!-- STEP 2: Date Range -->
          <v-stepper-window-item :value="2">
            <v-row class="mt-2">
              <v-col cols="12" md="6">
                <v-select v-model="form.date_range_type" :items="dateRangeTypes" :label="$t('date_range_type')" variant="outlined" rounded density="compact" color="#00BCD4" />
              </v-col>
              <v-col v-if="form.date_range_type === 'fixed_month'" cols="6" md="3">
                <v-select v-model="form.fixed_month" :items="months" :label="$t('month')" variant="outlined" rounded density="compact" color="#00BCD4" />
              </v-col>
              <v-col v-if="form.date_range_type === 'fixed_month' || form.date_range_type === 'fixed_year'" cols="6" md="3">
                <v-select v-model="form.fixed_year" :items="years" :label="$t('year')" variant="outlined" rounded density="compact" color="#00BCD4" />
              </v-col>
              <v-col v-if="form.date_range_type === 'dynamic'" cols="12" md="6">
                <v-alert type="info" variant="tonal" density="compact" rounded="lg" icon="mdi-information-outline" class="text-body-2">
                  {{ $t('dynamic_range_dashboard_hint') }}
                </v-alert>
              </v-col>
            </v-row>
          </v-stepper-window-item>

          <!-- STEP 3: Series -->
          <v-stepper-window-item :value="3">
            <div class="d-flex align-center justify-space-between mb-3">
              <span class="text-subtitle-1 font-weight-bold">{{ $t('series_builder') }} ({{ form.series.length }})</span>
              <v-btn variant="tonal" color="#00BCD4" size="small" prepend-icon="mdi-plus" @click="addSeries">{{ $t('add_series') }}</v-btn>
            </div>

            <v-expansion-panels v-model="openSeriesPanel" variant="accordion">
              <v-expansion-panel v-for="(s, idx) in form.series" :key="idx">
                <v-expansion-panel-title>
                  <div class="d-flex align-center ga-2">
                    <v-avatar size="20" :color="s.color || '#00BCD4'"><span style="font-size:10px;color:#fff">S{{ idx }}</span></v-avatar>
                    <span class="font-weight-medium">{{ s.name || `Series ${idx + 1}` }}</span>
                    <v-chip v-if="s.is_formula" size="x-small" color="purple" variant="tonal">{{ $t('formula') }}</v-chip>
                  </div>
                  <template #actions>
                    <v-tooltip :text="s.is_visible === false ? $t('hidden') : $t('visible')" location="top">
                      <template #activator="{ props }">
                        <v-btn v-bind="props" :icon="s.is_visible === false ? 'mdi-eye-off' : 'mdi-eye'" size="x-small" variant="text" :color="s.is_visible === false ? 'grey' : '#00BCD4'" @click.stop="s.is_visible = !s.is_visible" />
                      </template>
                    </v-tooltip>
                    <v-btn icon="mdi-delete" size="x-small" variant="text" color="error" @click.stop="removeSeries(idx)" />
                  </template>
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                  <v-row dense>
                    <v-col cols="12" md="4">
                      <v-text-field v-model="s.name" :label="$t('name')" variant="outlined" rounded density="compact" color="#00BCD4" />
                    </v-col>
                    <v-col cols="6" md="2">
                      <v-select v-model="s.series_type" :items="seriesTypes" :label="$t('type')" variant="outlined" rounded density="compact" color="#00BCD4" />
                    </v-col>
                    <v-col cols="6" md="2">
                      <v-select v-model="s.measure_unit" :items="measureUnits" :label="$t('measure_unit')" variant="outlined" rounded density="compact" color="#00BCD4" />
                    </v-col>
                    <v-col cols="6" md="2">
                      <v-text-field v-model="s.color" :label="$t('color')" type="color" variant="outlined" rounded density="compact" />
                    </v-col>
                    <v-col cols="6" md="2">
                      <v-switch v-model="s.is_formula" :label="$t('formula_mode')" color="purple" density="compact" hide-details />
                    </v-col>
                  </v-row>

                  <!-- Non-formula fields -->
                  <template v-if="!s.is_formula">
                    <v-row dense>
                      <v-col cols="12" md="3">
                        <v-select v-model="s.data_source" :items="dataSources" :label="$t('data_source')" variant="outlined" rounded density="compact" color="#00BCD4" />
                      </v-col>
                      <v-col cols="12" md="3">
                        <v-select v-model="s.aggregate_field" :items="getAggFields(s.data_source)" :label="$t('aggregate_field')" variant="outlined" rounded density="compact" color="#00BCD4" />
                      </v-col>
                      <v-col cols="6" md="3">
                        <v-select v-model="s.aggregate_function" :items="aggFunctions" :label="$t('aggregate_function')" variant="outlined" rounded density="compact" color="#00BCD4" />
                      </v-col>
                      <v-col cols="6" md="3">
                        <v-select v-model="s.group_by" :items="groupByOptions" :label="$t('group_by_period')" variant="outlined" rounded density="compact" color="#00BCD4" />
                      </v-col>
                    </v-row>

                    <!-- Filters -->
                    <v-row dense>
                      <v-col v-if="showFilter(s.data_source, 'category')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.category_id" :items="getCategoryOptions(s.data_source)" :label="$t('category')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'fish_process_type')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.fish_process_type_id" :items="fishProcessTypeOptions" :label="$t('process_types')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'supplier')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.supplier_id" :items="supplierOptions" :label="$t('supplier')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'client')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.client_id" :items="clientOptions" :label="$t('client')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'fishery')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.fishery_id" :items="fisheryOptions" :label="$t('fishery')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'origin')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.origin_id" :items="originOptions" :label="$t('origin')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'ship')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.ship_id" :items="shipOptions" :label="$t('ship')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'stockable')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.stockable_id" :items="stockableOptions" :label="$t('product')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'warehouse')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.warehouse_id" :items="warehouseOptions" :label="$t('warehouse_name')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'warehouse_category')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.warehouse_category_id" :items="warehouseCategoryOptions" :label="$t('warehouse_category')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'has_sejour')" cols="12" md="4">
                        <v-select v-model="s.filters.has_sejour" :items="sejourOptions" :label="$t('sejour_stockage')" variant="outlined" rounded density="compact" color="#00BCD4" clearable />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'production_type')" cols="12" md="4">
                        <v-select v-model="s.filters.production_type" :items="productionTypeOptions" :label="$t('production_type')" variant="outlined" rounded density="compact" color="#00BCD4" clearable />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'direction')" cols="12" md="4">
                        <v-select v-model="s.filters.direction" :items="directionOptions" :label="$t('direction')" variant="outlined" rounded density="compact" color="#00BCD4" clearable />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'section')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.section_id" :items="sectionOptions" :label="$t('section')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'corporal')" cols="12" md="4">
                        <v-autocomplete v-model="s.filters.corporal_id" :items="corporalOptions" :label="$t('corporal')" variant="outlined" rounded density="compact" color="#00BCD4" clearable chips />
                      </v-col>
                      <v-col v-if="showFilter(s.data_source, 'fortnight_period')" cols="12" md="4">
                        <v-select v-model="s.filters.fortnight_period" :items="fortnightPeriodOptions" :label="$t('fortnight_period')" variant="outlined" rounded density="compact" color="#00BCD4" clearable />
                      </v-col>
                    </v-row>
                  </template>

                  <!-- Formula fields -->
                  <template v-else>
                    <v-row dense>
                      <v-col cols="12" md="8">
                        <v-text-field v-model="s.formula_expression" :label="$t('formula_expression')" :hint="formulaHint(idx)" persistent-hint variant="outlined" rounded density="compact" color="purple" />
                      </v-col>
                      <v-col cols="12" md="4">
                        <v-select v-model="s.group_by" :items="groupByOptions" :label="$t('group_by_period')" variant="outlined" rounded density="compact" color="purple" />
                      </v-col>
                    </v-row>
                  </template>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </v-stepper-window-item>

          <!-- STEP 4: Preview -->
          <v-stepper-window-item :value="4">
            <div v-if="previewLoading" class="d-flex justify-center py-10">
              <v-progress-circular indeterminate color="#00BCD4" />
            </div>
            <template v-else>
              <!-- Statistic Preview -->
              <div v-if="form.display_type === 'statistic'" class="stat-preview-card">
                <div class="stat-preview-label">{{ form.title || $t('statistic') }}</div>
                <div class="stat-preview-value">{{ previewStatValue }}</div>
              </div>
              <!-- Chart Preview -->
              <ChartPreview v-else :chart-config="form" :categories="previewCategories" :series="previewSeries" :height="380" />
            </template>
          </v-stepper-window-item>
        </v-stepper-window>
      </v-stepper>

      <!-- Navigation -->
      <v-row class="mt-4">
        <v-col cols="6">
          <v-btn v-if="step > 1" block variant="outlined" color="#00BCD4" rounded @click="step--">{{ $t('back') }}</v-btn>
        </v-col>
        <v-col cols="6">
          <v-btn v-if="step < 4" block variant="tonal" color="#00BCD4" rounded @click="goNext">{{ step === 3 ? $t('chart_preview_live') : $t('next') }}</v-btn>
          <v-btn v-else block variant="outlined" color="#00BCD4" rounded :loading="saving" @click="save">{{ editing ? $t('update') : $t('submit') }}</v-btn>
        </v-col>
      </v-row>
    </v-card-text>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="snackbar.show" :timeout="5000" location="top right" color="surface" rounded="lg" elevation="12" min-width="320">
      <div class="d-flex align-center mb-1">
        <v-icon :color="snackbar.success ? 'success' : 'error'" class="mr-2">{{ snackbar.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ snackbar.text }}</span>
      </div>
      <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false" /></template>
    </v-snackbar>
  </v-card>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useCustomChart } from '@/stores/CustomChart';
import ChartPreview from './ChartPreview.vue';

export default {
  name: 'ChartForm',
  components: { ChartPreview },
  props: { editChart: { type: Object, default: null } },
  emits: ['saved', 'cancel'],
  data() {
    return {
      step: 1,
      saving: false,
      openSeriesPanel: 0,
      previewLoading: false,
      previewCategories: [],
      previewSeries: [],
      chartOptions: { stacked: false },
      snackbar: { show: false, text: '', success: true },
      form: this.getDefaultForm(),
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    store() { return useCustomChart(); },
    editing() { return !!this.editChart; },
    lang() { return this.main.lang?.code || 'en'; },
    chartTypes() {
      return [
        { title: this.$t('type_bar'), value: 'bar' },
        { title: this.$t('type_line'), value: 'line' },
        { title: this.$t('type_area'), value: 'area' },
        { title: this.$t('type_pie'), value: 'pie' },
        { title: this.$t('type_donut'), value: 'donut' },
        { title: this.$t('type_radar'), value: 'radar' },
        { title: this.$t('type_mixed'), value: 'mixed' },
      ];
    },
    displayTypes() {
      return [
        { title: this.$t('chart'), value: 'chart' },
        { title: this.$t('statistic'), value: 'statistic' },
      ];
    },
    dateRangeTypes() {
      return [
        { title: this.$t('dynamic_range'), value: 'dynamic' },
        { title: this.$t('fixed_month'), value: 'fixed_month' },
        { title: this.$t('fixed_year'), value: 'fixed_year' },
        { title: this.$t('all_dates'), value: 'all' },
      ];
    },
    months() { return Array.from({ length: 12 }, (_, i) => ({ title: String(i + 1).padStart(2, '0'), value: i + 1 })); },
    years() { const y = new Date().getFullYear(); return Array.from({ length: 10 }, (_, i) => y - 5 + i); },
    seriesTypes() {
      return [
        { title: this.$t('type_bar'), value: 'column' },
        { title: this.$t('type_line'), value: 'line' },
        { title: this.$t('type_area'), value: 'area' },
        { title: this.$t('type_pie'), value: 'pie' },
        { title: this.$t('type_donut'), value: 'donut' },
      ];
    },
    measureUnits() {
      return [
        { title: 'Kg', value: 'Kg' },
        { title: 'DH', value: 'DH' },
        { title: 'DH/KG', value: 'DH/KG' },
        { title: '%', value: '%' },
      ];
    },
    dataSources() {
      return [
        { title: this.$t('source_charges'), value: 'charges' },
        { title: this.$t('source_productions'), value: 'productions' },
        { title: this.$t('source_shippings'), value: 'shippings' },
        { title: this.$t('source_warehouses'), value: 'warehouses' },
        { title: this.$t('source_fortnight'), value: 'fortnight_activities' },
        { title: this.$t('source_static_charges'), value: 'static_charges' },
      ];
    },
    aggFunctions() { return ['sum', 'avg', 'count', 'min', 'max'].map(v => ({ title: v.toUpperCase(), value: v })); },
    groupByOptions() {
      return [
        { title: this.$t('day'), value: 'day' },
        { title: this.$t('month'), value: 'month' },
        { title: this.$t('year'), value: 'year' },
      ];
    },
    fishProcessTypeOptions() {
      return (this.auth.fish_process_type?.list || []).map(f => ({ title: f.name, value: f.id }));
    },
    supplierOptions() {
      return (this.auth.supplier?.list || []).map(s => ({ title: s.name, value: s.id }));
    },
    clientOptions() {
      return (this.auth.client?.list || []).map(c => ({ title: c.name, value: c.id }));
    },
    fisheryOptions() {
      return (this.auth.fishery?.list || []).map(f => ({ title: f.name, value: f.id }));
    },
    stockableOptions() {
      const items = [];
      (this.auth.fish_type?.list || []).forEach(f => items.push({ title: f[`name_${this.lang}`] || f.name_fr || f.name_en, value: f.id }));
      return items;
    },
    originOptions() {
      return (this.auth.origin?.list || []).map(o => ({ title: o.name, value: o.id }));
    },
    shipOptions() {
      return (this.auth.ship?.list || []).map(s => ({ title: s.name, value: s.id }));
    },
    sectionOptions() {
      return (this.auth.section?.list || []).map(s => ({ title: s[`name_${this.lang}`] || s.name_fr || s.name_en || s.name, value: s.id }));
    },
    corporalOptions() {
      return (this.auth.corporal?.list || []).map(c => ({ title: c.name, value: c.id }));
    },
    productionTypeOptions() {
      return [
        { title: this.$t('intern'), value: 'intern' },
        { title: this.$t('extern'), value: 'extern' },
      ];
    },
    directionOptions() {
      return [
        { title: this.$t('local'), value: 'local' },
        { title: this.$t('export'), value: 'export' },
      ];
    },
    fortnightPeriodOptions() {
      return [
        { title: 'Q1 (1-15)', value: 'Q1' },
        { title: 'Q2 (16-end)', value: 'Q2' },
      ];
    },
    warehouseOptions() {
      return (this.auth.warehouse?.list || []).map(w => ({ title: w.name, value: w.id }));
    },
    warehouseCategoryOptions() {
      // Build unique categories from warehouse list
      const cats = {};
      (this.auth.warehouse?.list || []).forEach(w => {
        if (w.category && w.category.id) {
          cats[w.category.id] = { title: w.category.name, value: w.category.id };
        } else if (w.warehouse_category_id) {
          cats[w.warehouse_category_id] = { title: `Category #${w.warehouse_category_id}`, value: w.warehouse_category_id };
        }
      });
      return Object.values(cats);
    },
    sejourOptions() {
      return [
        { title: this.$t('yes'), value: true },
        { title: this.$t('no'), value: false },
      ];
    },
    previewStatValue() {
      if (!this.previewSeries?.length || !this.previewSeries[0]?.data?.length) return '0.00';
      const unit = this.previewSeries[0].measure_unit || '';
      const total = this.previewSeries[0].data.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
      const suffix = unit ? ` ${unit}` : '';
      const formatted = total.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).replace(/\u202F/g, ' ').replace(',', '.');
      if (unit === '%') return formatted + '%';
      return formatted + suffix;
    },
  },
  watch: {
    editChart: {
      handler(v) {
        if (v) {
          this.loadEdit(v);
        } else {
          // Reset form when switching from edit to create mode
          this.form = this.getDefaultForm();
          this.chartOptions.stacked = false;
          this.step = 1;
          this.previewCategories = [];
          this.previewSeries = [];
        }
      },
      immediate: true,
    },
  },
  methods: {
    getDefaultForm() {
      return {
        title: '', chart_type: 'bar', display_type: 'chart', date_range_type: 'dynamic',
        fixed_month: null, fixed_year: new Date().getFullYear(), dynamic_start: new Date(new Date().getFullYear(), 0, 1).toISOString().slice(0, 10),
        dynamic_end: new Date().toISOString().slice(0, 10), grid_cols: 6, is_active: true, chart_options: {},
        series: [this.getDefaultSeries(0)],
      };
    },
    getDefaultSeries(idx) {
      return {
        name: '', series_type: 'column', measure_unit: 'Kg', is_visible: true, color: ['#00BCD4', '#42A5F5', '#66BB6A', '#FFA726', '#AB47BC', '#EF5350'][idx % 6],
        data_source: 'charges', aggregate_field: 'total_weight', aggregate_function: 'sum', group_by: 'month',
        filters: {}, is_formula: false, formula_expression: '', formula_series_refs: [], sort_order: idx,
      };
    },
    loadEdit(chart) {
      this.form = {
        ...chart,
        dynamic_start: chart.dynamic_start ? chart.dynamic_start.slice(0, 10) : null,
        dynamic_end: chart.dynamic_end ? chart.dynamic_end.slice(0, 10) : null,
        chart_options: chart.chart_options || {},
        series: (chart.series || []).map(s => ({ ...s, filters: s.filters || {} })),
      };
      this.chartOptions.stacked = this.form.chart_options?.stacked || false;
      this.step = 1;
    },
    addSeries() { this.form.series.push(this.getDefaultSeries(this.form.series.length)); },
    removeSeries(idx) { if (this.form.series.length > 1) this.form.series.splice(idx, 1); },
    getAggFields(source) {
      const map = {
        charges: ['total_weight', 'total_price', 'count'],
        productions: ['total_weight', 'total_price', 'fresh_reception_weight', 'frozen_production_weight', 'guano_production_weight', 'count'],
        shippings: ['total_weight', 'total_price', 'count'],
        warehouses: ['total_weight', 'remaining_weight', 'total_price', 'count'],
        fortnight_activities: ['price', 'count'],
        static_charges: ['amount', 'count'],
      };
      return (map[source] || ['total_weight']).map(v => ({ title: this.$t(`field_${v}`) || v, value: v }));
    },
    showFilter(source, filter) {
      const map = {
        charges: ['category', 'fish_process_type', 'supplier', 'fishery', 'origin', 'ship', 'stockable'],
        productions: ['category', 'fish_process_type', 'supplier', 'client', 'fishery', 'origin', 'ship', 'stockable', 'production_type'],
        shippings: ['category', 'client', 'fishery', 'origin', 'ship', 'stockable', 'direction'],
        warehouses: ['stockable', 'warehouse', 'warehouse_category', 'has_sejour'],
        fortnight_activities: ['section', 'corporal', 'fortnight_period'],
        static_charges: [],
      };
      return (map[source] || []).includes(filter);
    },
    getCategoryOptions(source) {
      const map = {
        charges: this.auth.charge_category?.list || [],
        productions: this.auth.production_category?.list || [],
        shippings: this.auth.sell_category?.list || [],
      };
      return (map[source] || []).map(c => ({ title: c[`name_${this.lang}`] || c.name_fr || c.name_en || `#${c.id}`, value: c.id }));
    },
    async goToStep(target) {
      if (target === 4) {
        this.step = 4;
        await this.loadPreview();
      } else {
        this.step = target;
      }
    },
    async goNext() {
      if (this.step === 3) {
        this.step = 4;
        await this.loadPreview();
      } else {
        this.step++;
      }
    },
    async loadPreview() {
      this.previewLoading = true;
      try {
        // If editing an existing chart, try fetching real data but use form's series config
        if (this.editing && this.editChart?.id) {
          const result = await this.store.fetchChartData(this.editChart.id, true);
          if (result?.categories?.length) {
            // Merge live data with form's current series appearance settings
            this.previewCategories = result.categories;
            this.previewSeries = this.form.series.map((s, i) => {
              const serverSeries = result.series[i];
              return {
                name: s.name || `Series ${i + 1}`,
                type: s.series_type || 'column',
                color: s.color,
                measure_unit: s.measure_unit || 'Kg',
                data: serverSeries?.data || Array.from({ length: result.categories.length }, () => Math.floor(Math.random() * 1000)),
              };
            });
            this.previewLoading = false;
            return;
          }
        }
      } catch { /* fallback to sample */ }

      // Fallback: generate sample preview from form data
      const sampleCats = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
      this.previewCategories = sampleCats;
      this.previewSeries = this.form.series
        .filter(s => s.is_visible !== false)
        .map((s, i) => ({
          name: s.name || `Series ${i + 1}`,
          type: s.series_type || 'column',
          color: s.color,
          measure_unit: s.measure_unit || 'Kg',
          data: Array.from({ length: 6 }, () => Math.floor(Math.random() * 1000)),
        }));
      this.previewLoading = false;
    },
    async save() {
      this.saving = true;
      try {
        this.form.chart_options = { ...this.form.chart_options, stacked: this.chartOptions.stacked };
        if (this.editing) {
          await this.store.update(this.editChart.id, this.form);
          this.snackbar = { show: true, text: this.$t('chart_updated_success'), success: true };
        } else {
          await this.store.create(this.form);
          this.snackbar = { show: true, text: this.$t('chart_saved_success'), success: true };
        }
        this.$emit('saved');
        this.form = this.getDefaultForm();
        this.step = 1;
      } catch (e) {
        this.snackbar = { show: true, text: e.response?.data?.message || 'Error', success: false };
      }
      this.saving = false;
    },
    formulaHint(currentIdx) {
      const aliases = this.form.series
        .map((s, i) => i !== currentIdx && !s.is_formula ? `S${i} = ${s.name || s.data_source}` : null)
        .filter(Boolean);
      return aliases.length
        ? `${this.$t('available')}: ${aliases.join(', ')} — Ex: (S0 / S1) * 100`
        : 'Ex: (S0 / S1) * 100';
    },
  },
};
</script>

<style scoped>
.chart-form-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.cf-hero { background: linear-gradient(135deg, #0097A7 0%, #00BCD4 40%, #4DD0E1 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.cf-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; }
.cf-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.cf-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
.stat-preview-card { background: linear-gradient(135deg, #f5fdff 0%, #e0f7fa 100%); border-radius: 16px; padding: 40px; text-align: center; }
.stat-preview-label { font-size: 16px; font-weight: 600; color: #546E7A; margin-bottom: 12px; }
.stat-preview-value { font-size: 48px; font-weight: 800; color: #00838F; letter-spacing: -1px; }
</style>
