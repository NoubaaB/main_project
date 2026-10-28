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
      <!-- Top Toolbar (Search, Filter, Count & Add) -->
      <div class="param-top-toolbar mb-4">
        <div class="d-flex align-center flex-grow-1" style="max-width: 360px;">
          <v-text-field
            v-model="search"
            prepend-inner-icon="mdi-magnify"
            :placeholder="$t('search') || 'Rechercher...'"
            density="compact"
            variant="outlined"
            rounded="lg"
            hide-details
            clearable
            class="param-search-input"
          />
        </div>
        <v-spacer></v-spacer>
        <div class="d-flex align-center ga-2 flex-wrap">
          <v-chip color="#4643a0" variant="tonal" size="small" class="font-weight-bold">
            <v-icon start size="14">mdi-tag-multiple-outline</v-icon>
            {{ filteredItems.length }} {{ $t('records') || 'enregistrements' }}
          </v-chip>
          <v-btn
            v-if="hasActiveColumnFilters || search"
            variant="tonal"
            color="warning"
            rounded="lg"
            size="small"
            prepend-icon="mdi-filter-off"
            @click="clearAllColumnFilters(); search = ''"
          >
            {{ $t('clear_filters') }}
          </v-btn>
          <v-btn
            color="#4643a0"
            prepend-icon="mdi-plus"
            rounded="lg"
            elevation="0"
            class="text-white font-weight-bold"
            @click="openDialog(null)"
          >
            {{ $t('add_shipping_category') }}
          </v-btn>
        </div>
      </div>

      <!-- Data Table -->
      <v-data-table
        :headers="headers"
        :items="filteredItems"
        :search="search"
        :loading="loading ? '#4643a0' : false"
        :multi-sort="true"
        density="comfortable"
        class="rounded-xl param-table"
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
          <v-chip color="#4643a0" size="small" variant="tonal" class="font-weight-bold">{{ item.shippings_count ?? item.sells_count ?? 0 }}</v-chip>
        </template>
        <template v-slot:[`item.sells_count`]="{ item }">
          <v-chip color="#4643a0" size="small" variant="tonal" class="font-weight-bold">{{ item.shippings_count ?? item.sells_count ?? 0 }}</v-chip>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <div class="param-actions-cell">
            <v-tooltip :text="$t('edit')" location="top">
              <template #activator="{ props }">
                <v-btn v-bind="props" icon size="28" variant="tonal" color="#4643a0" class="param-action-btn" @click="openDialog(item)">
                  <v-icon size="15">mdi-pencil</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
            <v-tooltip :text="$t('delete')" location="top">
              <template #activator="{ props }">
                <v-btn v-bind="props" icon size="28" variant="tonal" color="error" class="param-action-btn param-action-delete" @click="confirmDelete(item)">
                  <v-icon size="15">mdi-delete-outline</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </div>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- ============= CREATE/EDIT DIALOG ============= -->
    <v-dialog v-model="dialog" max-width="700" persistent scrollable>
      <v-card class="rounded-xl overflow-hidden param-dialog-card" elevation="24">
        <div class="param-dialog-header">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="param-dialog-icon-badge" style="background: rgba(70, 67, 160, 0.15); color: #4643a0;">
                <v-icon size="24">{{ editing ? 'mdi-pencil-box-outline' : 'mdi-plus-circle-outline' }}</v-icon>
              </div>
              <div>
                <div class="param-dialog-title">{{ editing ? $t('edit_shipping_category') : $t('add_shipping_category') }}</div>
                <div class="param-dialog-subtitle">{{ $t('manage_sell_categories_desc') }}</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" size="small" class="param-dialog-close" @click="dialog = false" />
          </div>
        </div>
        <v-card-text class="pa-5 param-dialog-body" style="max-height: 75vh;">
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="#4643a0" class="mr-2" size="20">mdi-translate</v-icon>
            {{ $t('names') }}
          </div>
          <v-row dense>
            <v-col cols="6">
              <v-text-field v-model="form.name" :label="$t('name')" prepend-inner-icon="mdi-tag-outline" variant="outlined" density="compact" rounded="lg" color="#4643a0"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.name_fr" :label="$t('name_fr')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#4643a0"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.name_en" :label="$t('name_en')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#4643a0"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.name_es" :label="$t('name_es')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#4643a0"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="form.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded="lg" color="#4643a0" rows="2" auto-grow></v-textarea>
            </v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="#4643a0" class="mr-2" size="20">mdi-toggle-switch-outline</v-icon>
            {{ $t('enabled_fields') }}
          </div>
          <v-row dense>
            <v-col cols="6" v-for="toggle in toggleFields" :key="toggle.key">
              <div class="scm-toggle-card rounded-lg pa-2 border d-flex align-center justify-space-between cursor-pointer" :class="{ 'scm-toggle-active': form[toggle.key] }" :style="{ '--toggle-color': form[toggle.key] ? toggle.activeColor : 'transparent' }" @click="form[toggle.key] = !form[toggle.key]">
                <div class="d-flex align-center ga-2">
                  <div class="scm-toggle-icon-wrap" :style="{ background: form[toggle.key] ? toggle.activeColor + '1A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form[toggle.key] ? toggle.activeColor : 'grey'" size="20">{{ toggle.icon }}</v-icon>
                  </div>
                  <div class="text-caption font-weight-medium">{{ $t(toggle.label) }}</div>
                </div>
                <v-switch v-model="form[toggle.key]" :color="toggle.activeColor" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="param-dialog-actions pa-4 border-t bg-surface">
          <v-btn variant="tonal" color="grey" rounded="lg" class="px-4" @click="dialog = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn color="#4643a0" variant="flat" rounded="lg" class="px-5 text-white font-weight-bold" @click="save" :loading="saving" prepend-icon="mdi-content-save">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CONFIRM DELETE DIALOG ============= -->
    <v-dialog v-model="confirmDeleteDialog" max-width="440">
      <v-card class="rounded-xl overflow-hidden param-dialog-card">
        <div class="pa-6 text-center">
          <div class="param-dialog-icon-badge mx-auto mb-4" style="background: rgba(244, 67, 54, 0.12); color: #f44336; width: 56px; height: 56px;">
            <v-icon size="32">mdi-alert-circle-outline</v-icon>
          </div>
          <div class="text-h6 font-weight-bold mb-2">{{ $t('confirm_delete') }}</div>
          <div class="text-body-2 text-medium-emphasis mb-4">
            {{ $t('confirm_delete_shipping_category_message') }}
          </div>
          <div v-if="deleteTarget" class="pa-3 rounded-lg text-left" style="background: rgba(244,67,54,.06); border: 1px solid rgba(244, 67, 54, 0.15);">
            <div class="font-weight-bold">{{ deleteTarget.name_fr || deleteTarget.name || deleteTarget.name_en }}</div>
            <div class="text-caption text-medium-emphasis" v-if="deleteTarget.description">{{ deleteTarget.description }}</div>
          </div>
        </div>
        <v-card-actions class="param-dialog-actions pa-4 border-t bg-surface">
          <v-btn variant="tonal" rounded="lg" color="grey" class="px-4" @click="confirmDeleteDialog = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="flat" rounded="lg" class="px-5 font-weight-bold" @click="executeDelete" :loading="deleting" prepend-icon="mdi-delete">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar with Countdown Progress Bar (Rule 5 & AddCharge style) -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="6000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="340"
      max-width="400"
      @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
    >
      <v-progress-linear :model-value="snackbarProgress" color="#4643a0" height="3" class="mb-2" rounded></v-progress-linear>
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
      search: '',
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
      summarySnackbar: { show: false, title: '', detail: '', color: 'success' },
      snackbarProgress: 100,
      snackbarTimer: null,
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
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' },
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
  beforeUnmount() {
    mitt.off('model-changed', this._onModelChanged);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
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
      if (['ShippingCategory', 'shippingCategory', 'sellCategory'].includes(data.model)) {
        this.fetchCategories();
        if (data.action) {
          const actionText = data.action === 'created' ? this.$t('created') : data.action === 'updated' ? this.$t('updated') : this.$t('deleted');
          this.triggerSummarySnackbar(
            `${this.$t('shipping_category')}: ${actionText}`,
            data.data?.name || data.data?.name_fr || '',
            'info'
          );
        }
      }
    },
    triggerSummarySnackbar(title, detail = '', color = 'success') {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, title, detail, color };
      this.snackbarProgress = 100;
      const duration = 6000;
      const interval = 50;
      const step = (interval / duration) * 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= step;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, interval);
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
        const displayName = this.form.name || this.form.name_fr || '';
        if (this.editing) {
          await axios.put(`/api/shipping-categories/${this.editing.id}`, this.form);
          this.triggerSummarySnackbar(this.$t('shipping_category_updated'), displayName, 'success');
        } else {
          await axios.post('/api/shipping-categories', this.form);
          this.triggerSummarySnackbar(this.$t('shipping_category_created'), displayName, 'success');
        }
        this.dialog = false;
        this.fetchCategories();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), this.$t('shipping_category_save_error'), 'error');
      }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.confirmDeleteDialog = true; },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      const targetName = this.deleteTarget.name || this.deleteTarget.name_fr || '';
      try {
        await axios.delete(`/api/shipping-categories/${this.deleteTarget.id}`);
        this.triggerSummarySnackbar(this.$t('shipping_category_deleted'), targetName, 'success');
        this.confirmDeleteDialog = false;
        this.fetchCategories();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), this.$t('shipping_category_delete_error'), 'error');
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

