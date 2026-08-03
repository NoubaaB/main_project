<template>
  <v-card class="scm-card" :elevation="embedded ? 0 : 0" :class="{ 'scm-embedded': embedded }">
    <!-- Hero Header -->
    <div v-if="!embedded" class="scm-hero">
      <div class="scm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="scm-hero-icon">
            <v-icon size="28" color="white">mdi-tag-multiple-outline</v-icon>
          </div>
          <div>
            <div class="scm-hero-title">{{ $t('shipping_category_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_sell_categories_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <!-- Add button -->
      <div class="d-flex align-center ga-3 mb-3">
        <v-spacer></v-spacer>
        <v-btn v-if="hasActiveColumnFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="clearAllColumnFilters">
          {{ $t('clear_filters') }}
        </v-btn>
        <v-btn color="#4643a0" prepend-icon="mdi-plus" rounded @click="openDialog(null)">
          {{ $t('add_shipping_category') }}
        </v-btn>
      </div>

      <!-- Data Table -->
      <v-data-table
        :headers="headers"
        :items="filteredItems"
        :loading="loading ? '#4643a0' : false"
        density="comfortable"
        class="rounded-lg param-table"
        hover
      >
        <!-- Custom headers with filter inputs -->
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr>
            <th v-for="col in columns" :key="col.key" class="param-th">
              <div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">
                {{ col.title }}
                <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
              </div>
            </th>
          </tr>
          <tr class="param-filter-row">
            <th v-for="col in columns" :key="col.key + '-filter'" class="param-filter-th">
              <v-autocomplete
                v-if="col.key !== 'actions'"
                v-model="columnFilters[col.key]"
                :items="getColumnOptions(col.key)"
                density="compact"
                variant="outlined"
                color="#4643a0"
                class="param-filter-input" style="color: #4643a0"
                hide-details
                clearable
                multiple
                chips
                closable-chips
                :placeholder="$t('filter')"
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="x-small" color="#4643a0" variant="tonal" closable>{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </th>
          </tr>
        </template>

        <!-- Boolean switch columns -->
        <template v-slot:[`item.enable_shipping_tracking_number`]="{ item }">
          <v-icon :color="item.enable_shipping_tracking_number ? 'success' : 'grey'" size="20">
            {{ item.enable_shipping_tracking_number ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.enable_tracking_onp_numbers`]="{ item }">
          <v-icon :color="item.enable_tracking_onp_numbers ? 'success' : 'grey'" size="20">
            {{ item.enable_tracking_onp_numbers ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.enable_fishery_id`]="{ item }">
          <v-icon :color="item.enable_fishery_id ? 'success' : 'grey'" size="20">
            {{ item.enable_fishery_id ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.enable_ship_id`]="{ item }">
          <v-icon :color="item.enable_ship_id ? 'success' : 'grey'" size="20">
            {{ item.enable_ship_id ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.enable_origin_id`]="{ item }">
          <v-icon :color="item.enable_origin_id ? 'success' : 'grey'" size="20">
            {{ item.enable_origin_id ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.enable_truck_id`]="{ item }">
          <v-icon :color="item.enable_truck_id ? 'success' : 'grey'" size="20">
            {{ item.enable_truck_id ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.enable_clients`]="{ item }">
          <v-icon :color="item.enable_clients ? 'success' : 'grey'" size="20">
            {{ item.enable_clients ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.enable_direction`]="{ item }">
          <v-icon :color="item.enable_direction ? 'success' : 'grey'" size="20">
            {{ item.enable_direction ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.enable_invoices`]="{ item }">
          <v-icon :color="item.enable_invoices ? 'success' : 'grey'" size="20">
            {{ item.enable_invoices ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>
        <template v-slot:[`item.shippings_count`]="{ item }">
          <v-chip color="#4643a0" size="small" variant="tonal">{{ item.shippings_count ?? item.sells_count ?? 0 }}</v-chip>
        </template>
        <template v-slot:[`item.sells_count`]="{ item }">
          <v-chip color="#4643a0" size="small" variant="tonal">{{ item.shippings_count ?? item.sells_count ?? 0 }}</v-chip>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="#4643a0" @click="openDialog(item)" class="mr-1">
            <v-icon size="16">mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)">
            <v-icon size="16">mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- ============= CREATE/EDIT DIALOG ============= -->
    <v-dialog v-model="dialog" max-width="700" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#4643a0" class="mr-2">mdi-tag-multiple-outline</v-icon>
          {{ editing ? $t('edit_shipping_category') : $t('add_shipping_category') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="#4643a0" class="mr-2" size="20">mdi-translate</v-icon>
            {{ $t('names') }}
          </div>
          <v-row>
            <v-col cols="6">
              <v-text-field v-model="form.name" :label="$t('name')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.name_fr" :label="$t('name_fr')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.name_en" :label="$t('name_en')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.name_es" :label="$t('name_es')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="form.description" :label="$t('description')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" rows="2"></v-textarea>
            </v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="#4643a0" class="mr-2" size="20">mdi-toggle-switch-outline</v-icon>
            {{ $t('enabled_fields') }}
          </div>
          <v-row dense>
            <v-col cols="6" v-for="toggle in toggleFields" :key="toggle.key">
              <div class="scm-toggle-card" :class="{ 'scm-toggle-active': form[toggle.key] }" :style="{ '--toggle-color': form[toggle.key] ? toggle.activeColor : 'transparent' }" @click="form[toggle.key] = !form[toggle.key]">
                <div class="scm-toggle-left">
                  <div class="scm-toggle-icon-wrap" :style="{ background: form[toggle.key] ? toggle.activeColor + '1A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form[toggle.key] ? toggle.activeColor : 'grey'" size="20">{{ toggle.icon }}</v-icon>
                  </div>
                  <div class="scm-toggle-info">
                    <div class="scm-toggle-label">{{ $t(toggle.label) }}</div>
                  </div>
                </div>
                <v-switch v-model="form[toggle.key]" :color="toggle.activeColor" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="#4643a0" variant="flat" rounded @click="save" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CONFIRM DELETE DIALOG ============= -->
    <v-dialog v-model="confirmDeleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_delete_shipping_category_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script>
import axios from 'axios';
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import { useShippingCategory } from '@/stores/ShippingCategory';

export default {
  name: 'ShippingCategoryManagement',
  props: { embedded: { type: Boolean, default: false } },
  data() {
    return {
      categories: [],
      loading: false,
      columnFilters: {},
      dialog: false,
      editing: null,
      form: this.emptyForm(),
      saving: false,
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleting: false,
      toggleFields: [
        { key: 'enable_shipping_tracking_number', label: 'enable_shipping_tracking_number', icon: 'mdi-barcode-scan', activeColor: '#2196F3' },
        { key: 'enable_tracking_onp_numbers', label: 'enable_tracking_onp_numbers', icon: 'mdi-barcode', activeColor: '#00BCD4' },
        { key: 'enable_fishery_id', label: 'enable_fishery', icon: 'mdi-fish', activeColor: '#4CAF50' },
        { key: 'enable_ship_id', label: 'enable_ship', icon: 'mdi-ferry', activeColor: '#3F51B5' },
        { key: 'enable_origin_id', label: 'enable_origin', icon: 'mdi-map-marker', activeColor: '#FF9800' },
        { key: 'enable_truck_id', label: 'enable_truck', icon: 'mdi-truck', activeColor: '#607D8B' },
        { key: 'enable_clients', label: 'enable_clients', icon: 'mdi-account-group', activeColor: '#E91E63' },
        { key: 'enable_direction', label: 'enable_direction', icon: 'mdi-compass-outline', activeColor: '#00897B' },
        { key: 'enable_invoices', label: 'enable_invoices', icon: 'mdi-file-document-outline', activeColor: '#FF5722' },
      ],
      booleanKeys: ['enable_shipping_tracking_number', 'enable_tracking_onp_numbers', 'enable_fishery_id', 'enable_ship_id', 'enable_origin_id', 'enable_truck_id', 'enable_clients', 'enable_direction', 'enable_invoices'],
    };
  },
  computed: {
    main() { return useMain(); },
    shippingCategoryStore() { return useShippingCategory(); },
    headers() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_en'), key: 'name_en', align: 'center' },
        { title: this.$t('name_es'), key: 'name_es', align: 'center' },
        { title: this.$t('shipping_tracking_number'), key: 'enable_shipping_tracking_number', align: 'center' },
        { title: this.$t('tracking_onp_numbers_short'), key: 'enable_tracking_onp_numbers', align: 'center' },
        { title: this.$t('fishery'), key: 'enable_fishery_id', align: 'center' },
        { title: this.$t('ship'), key: 'enable_ship_id', align: 'center' },
        { title: this.$t('origin'), key: 'enable_origin_id', align: 'center' },
        { title: this.$t('truck'), key: 'enable_truck_id', align: 'center' },
        { title: this.$t('clients'), key: 'enable_clients', align: 'center' },
        { title: this.$t('direction'), key: 'enable_direction', align: 'center' },
        { title: this.$t('enable_invoices'), key: 'enable_invoices', align: 'center' },
        { title: this.$t('shippings_count'), key: 'shippings_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    filteredItems() {
      return this.categories.filter(item => {
        return Object.entries(this.columnFilters).every(([key, selected]) => {
          if (!selected || !selected.length) return true;
          return selected.includes(this.getCellDisplayValue(key, item));
        });
      });
    },
    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(f => f && f.length > 0);
    },
  },
  mounted() {
    this.fetchCategories();
    mitt.on('model-changed', this._onModelChanged);
  },
  beforeUnmount() { mitt.off('model-changed', this._onModelChanged); },
  methods: {
    emptyForm() {
      return { name: '', name_fr: '', name_en: '', name_es: '', description: '', enable_shipping_tracking_number: false, enable_tracking_onp_numbers: false, enable_fishery_id: false, enable_ship_id: false, enable_origin_id: false, enable_truck_id: false, enable_clients: false, enable_direction: false, enable_invoices: false };
    },
    getCellDisplayValue(key, item) {
      if (this.booleanKeys.includes(key)) return item[key] ? this.$t('yes') : this.$t('no');
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      this.categories.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    clearAllColumnFilters() { this.columnFilters = {}; },
    _onModelChanged(data) {
      if (['ShippingCategory', 'shippingCategory', 'sellCategory'].includes(data.model)) this.fetchCategories();
    },
    async fetchCategories() {
      this.loading = true;
      try {
        const res = await axios.get('/api/shipping-categories');
        this.categories = res.data.shipping_categories || res.data.sell_categories || [];
        this.shippingCategoryStore.list = this.categories;
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = { name: item.name || '', name_fr: item.name_fr || '', name_en: item.name_en || '', name_es: item.name_es || '', description: item.description || '', enable_shipping_tracking_number: !!item.enable_shipping_tracking_number, enable_tracking_onp_numbers: !!item.enable_tracking_onp_numbers, enable_fishery_id: !!item.enable_fishery_id, enable_ship_id: !!item.enable_ship_id, enable_origin_id: !!item.enable_origin_id, enable_truck_id: !!item.enable_truck_id, enable_clients: !!item.enable_clients, enable_direction: !!item.enable_direction, enable_invoices: !!item.enable_invoices };
      } else { this.form = this.emptyForm(); }
      this.dialog = true;
    },
    async save() {
      this.saving = true;
      try {
        if (this.editing) {
          await axios.put(`/api/shipping-categories/${this.editing.id}`, this.form);
          this.main.add_snackbar({ show: true, message: this.$t('shipping_category_updated'), color: 'success' });
        } else {
          await axios.post('/api/shipping-categories', this.form);
          this.main.add_snackbar({ show: true, message: this.$t('shipping_category_created'), color: 'success' });
        }
        this.dialog = false;
        this.fetchCategories();
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('shipping_category_save_error'), color: 'error' });
      }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.confirmDeleteDialog = true; },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/shipping-categories/${this.deleteTarget.id}`);
        this.main.add_snackbar({ show: true, message: this.$t('shipping_category_deleted'), color: 'success' });
        this.confirmDeleteDialog = false;
        this.fetchCategories();
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('shipping_category_delete_error'), color: 'error' });
      }
      this.deleting = false;
      this.deleteTarget = null;
    },
  },
};
</script>

<style scoped>
.scm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.scm-hero { background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.scm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.scm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.scm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.scm-toggle-card { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 12px; border: 1.5px solid rgba(var(--v-border-color), .12); cursor: pointer; transition: all .2s ease; background: transparent; }
.scm-toggle-card:hover { border-color: rgba(var(--v-border-color), .25); }
.scm-toggle-active { border-color: var(--toggle-color) !important; background: color-mix(in srgb, var(--toggle-color) 4%, transparent); }
.scm-toggle-left { display: flex; align-items: center; gap: 10px; }
.scm-toggle-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: background .2s ease; }
.scm-toggle-info { min-width: 0; }
.scm-toggle-label { font-size: 13px; font-weight: 600; }
/* Column filter styles */
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; }
.param-filter-row th { padding: 0 4px 6px !important; }
.param-filter-th { vertical-align: top; }
.param-filter-input { min-width: 80px; }
.param-filter-input :deep(.v-field) { font-size: 11px !important; min-height: 30px !important; border-radius: 8px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 26px !important; font-size: 11px; }
.param-filter-input :deep(.v-field__clearable) { font-size: 14px; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
</style>

