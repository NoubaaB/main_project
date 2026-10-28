<template>
  <v-card class="wco-card" elevation="0">
    <!-- Hero Header -->
    <div class="wco-hero">
      <div class="wco-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="wco-hero-icon">
            <v-icon size="28" color="white">mdi-cog-transfer-outline</v-icon>
          </div>
          <div>
            <div class="wco-hero-title">{{ $t('wco_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_wco_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <div class="param-top-toolbar mb-4">
        <div class="d-flex align-center ga-3 flex-wrap flex-grow-1">
          <v-text-field
            v-model="search"
            :label="$t('search')"
            variant="outlined"
            density="compact"
            rounded="lg"
            color="#a21ee5"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            class="param-search-input"
          ></v-text-field>
          <v-chip size="small" variant="tonal" color="#a21ee5" class="font-weight-bold fnm-currency-num">
            <v-icon start size="14">mdi-cog-transfer-outline</v-icon>
            {{ filteredItems.length }} {{ $t('records') }}
          </v-chip>
          <v-btn v-if="hasActiveColumnFilters" variant="tonal" color="warning" rounded="lg" size="small" prepend-icon="mdi-filter-off" @click="clearAllColumnFilters">
            {{ $t('clear_filters') }}
          </v-btn>
        </div>
        <v-btn color="#a21ee5" variant="flat" rounded="lg" prepend-icon="mdi-plus" class="text-capitalize font-weight-bold" elevation="2" @click="openDialog(null)">
          {{ $t('add_wco') }}
        </v-btn>
      </div>

      <v-data-table :headers="headers" :items="filteredItems" :loading="loading ? '#a21ee5' : false" density="comfortable" class="rounded-xl param-table" hover :search="search">
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr>
            <th v-for="col in columns" :key="col.key" class="param-th" :style="col.width ? { width: col.width } : {}">
              <div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">
                {{ col.title }}
                <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
              </div>
            </th>
          </tr>
          <tr class="param-filter-row">
            <th v-for="col in columns" :key="col.key + '-filter'" class="param-filter-th" :style="col.width ? { width: col.width } : {}">
              <v-autocomplete
                v-if="col.key !== 'actions'"
                v-model="columnFilters[col.key]"
                :items="getColumnOptions(col.key)"
                density="compact"
                variant="outlined"
                color="#a21ee5"
                class="param-filter-input"
                hide-details
                clearable
                multiple
                chips
                closable-chips
                :placeholder="$t('filter')"
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="x-small" color="#a21ee5" variant="tonal" closable>{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </th>
          </tr>
        </template>

        <template v-slot:[`item.allow_weight`]="{ item }">
          <v-icon :color="item.allow_weight ? 'success' : 'grey'" size="20">{{ item.allow_weight ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
        </template>
        <template v-slot:[`item.allow_price`]="{ item }">
          <v-icon :color="item.allow_price ? 'success' : 'grey'" size="20">{{ item.allow_price ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
        </template>
        <template v-slot:[`item.allow_package_type`]="{ item }">
          <v-icon :color="item.allow_package_type ? 'success' : 'grey'" size="20">{{ item.allow_package_type ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
        </template>
        <template v-slot:[`item.allow_warehouse`]="{ item }">
          <v-icon :color="item.allow_warehouse ? 'success' : 'grey'" size="20">{{ item.allow_warehouse ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
        </template>
        <template v-slot:[`item.allow_detail_truck_id`]="{ item }">
          <v-icon :color="item.allow_detail_truck_id ? 'success' : 'grey'" size="20">{{ item.allow_detail_truck_id ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
        </template>
        <template v-slot:[`item.production_role`]="{ item }">
          <v-chip v-if="item.production_role" :color="productionRoleColor(item.production_role)" size="small" variant="tonal">{{ $t(item.production_role) }}</v-chip>
          <span v-else class="text-grey">-</span>
        </template>
        <template v-slot:[`item.operation_type`]="{ item }">
          <v-chip :color="operationTypeColor(item.operation_type)" size="small" variant="tonal">{{ $t(item.operation_type + '_type') }}</v-chip>
        </template>
        <template v-slot:[`item.warehouse_type`]="{ item }">
          <v-chip v-if="item.warehouse_type" :color="warehouseTypeColor(item.warehouse_type)" size="small" variant="tonal">{{ $t(item.warehouse_type) }}</v-chip>
          <span v-else class="text-grey">-</span>
        </template>
        <template v-slot:[`item.usage_count`]="{ item }">
          <v-chip color="#a21ee5" size="small" variant="tonal" class="fnm-currency-num font-weight-bold">{{ (item.warehouse_stockable_tracking_number_operations_count || 0) + (item.non_warehouse_stockable_operations_count || 0) }}</v-chip>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <div class="param-actions-cell">
            <v-tooltip :text="$t('edit')" location="top">
              <template #activator="{ props }">
                <v-btn v-bind="props" icon size="28" variant="tonal" color="#a21ee5" class="param-action-btn" @click="openDialog(item)">
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

    <!-- CREATE/EDIT DIALOG -->
    <v-dialog v-model="dialog" max-width="760" persistent scrollable>
      <v-card class="param-dialog-card">
        <div class="param-dialog-header">
          <div class="d-flex align-center ga-3">
            <div class="param-dialog-icon-badge" style="background: rgba(162, 30, 229, 0.12); color: #a21ee5;">
              <v-icon size="22">mdi-cog-transfer-outline</v-icon>
            </div>
            <div>
              <div class="param-dialog-title">{{ editing ? $t('edit_wco') : $t('add_wco') }}</div>
              <div class="param-dialog-subtitle">{{ $t('parameters') }} • {{ $t('wco_management') }}</div>
            </div>
          </div>
          <v-btn icon variant="text" size="small" density="comfortable" @click="dialog = false">
            <v-icon size="18">mdi-close</v-icon>
          </v-btn>
        </div>
        <v-card-text class="param-dialog-body">
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center"><v-icon color="#a21ee5" class="mr-2" size="20">mdi-translate</v-icon>{{ $t('names') }}</div>
          <v-row dense>
            <v-col cols="12" sm="4"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#a21ee5"></v-text-field></v-col>
            <v-col cols="12" sm="4"><v-text-field v-model="form.name_en" :label="$t('name_en')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#a21ee5"></v-text-field></v-col>
            <v-col cols="12" sm="4"><v-text-field v-model="form.name_es" :label="$t('name_es')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#a21ee5"></v-text-field></v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded="lg" color="#a21ee5" rows="2"></v-textarea></v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center"><v-icon color="#a21ee5" class="mr-2" size="20">mdi-format-list-bulleted-type</v-icon>{{ $t('type') }}</div>
          <v-row dense>
            <v-col cols="12" sm="4"><v-select v-model="form.production_role" :items="productionRoleOptions" :label="$t('production_role')" prepend-inner-icon="mdi-account-hard-hat" variant="outlined" density="compact" rounded="lg" color="#a21ee5" clearable></v-select></v-col>
            <v-col cols="12" sm="4"><v-select v-model="form.operation_type" :items="operationTypeOptions" :label="$t('operation_type')" prepend-inner-icon="mdi-swap-horizontal" variant="outlined" density="compact" rounded="lg" color="#a21ee5"></v-select></v-col>
            <v-col cols="12" sm="4"><v-select v-model="form.warehouse_type" :items="warehouseTypeOptions" :label="$t('warehouse_type_label')" prepend-inner-icon="mdi-warehouse" variant="outlined" density="compact" rounded="lg" color="#a21ee5" clearable></v-select></v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center"><v-icon color="#a21ee5" class="mr-2" size="20">mdi-toggle-switch-outline</v-icon>{{ $t('enabled_fields') }}</div>
          <v-row dense>
            <v-col cols="12" sm="6" v-for="toggle in toggleFields" :key="toggle.key">
              <div class="wco-toggle-card rounded-lg" :class="{ 'wco-toggle-active': form[toggle.key] }" :style="{ '--toggle-color': form[toggle.key] ? toggle.activeColor : 'transparent' }" @click="form[toggle.key] = !form[toggle.key]">
                <div class="wco-toggle-left">
                  <div class="wco-toggle-icon-wrap rounded-lg" :style="{ background: form[toggle.key] ? toggle.activeColor + '1A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form[toggle.key] ? toggle.activeColor : 'grey'" size="20">{{ toggle.icon }}</v-icon>
                  </div>
                  <div class="wco-toggle-info"><div class="wco-toggle-label">{{ $t(toggle.label) }}</div></div>
                </div>
                <v-switch v-model="form[toggle.key]" :color="toggle.activeColor" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="param-dialog-actions">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="tonal" rounded="lg" class="text-capitalize">{{ $t('cancel') }}</v-btn>
          <v-btn color="#a21ee5" variant="flat" rounded="lg" class="text-capitalize font-weight-bold" @click="save" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- CONFIRM DELETE DIALOG -->
    <v-dialog v-model="confirmDeleteDialog" max-width="440">
      <v-card class="param-dialog-card">
        <div class="pa-6 text-center">
          <div class="param-dialog-icon-badge mx-auto mb-4" style="background: rgba(239, 68, 68, 0.12); color: #ef4444; width: 56px; height: 56px;">
            <v-icon size="28">mdi-trash-can-outline</v-icon>
          </div>
          <div class="text-h6 font-weight-bold mb-2">{{ $t('confirm_delete') }}</div>
          <div class="text-body-2 text-medium-emphasis mb-6">
            {{ $t('confirm_delete_wco_message') }}
          </div>
          <div class="d-flex align-center ga-3 justify-center">
            <v-btn variant="tonal" rounded="lg" class="text-capitalize px-5" @click="confirmDeleteDialog = false">{{ $t('cancel') }}</v-btn>
            <v-btn color="error" variant="flat" rounded="lg" class="text-capitalize font-weight-bold px-6" elevation="2" @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
          </div>
        </div>
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
      <v-progress-linear :model-value="snackbarProgress" color="#a21ee5" height="3" class="mb-2" rounded></v-progress-linear>
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
import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';

export default {
  name: 'WarehouseCategoryOperationManagement',
  data() {
    return {
      items: [],
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
        { key: 'allow_weight', label: 'allow_weight', icon: 'mdi-weight-kilogram', activeColor: '#2196F3' },
        { key: 'allow_price', label: 'allow_price', icon: 'mdi-currency-usd', activeColor: '#4CAF50' },
        { key: 'allow_package_type', label: 'allow_package_type', icon: 'mdi-package-variant-closed', activeColor: '#FF9800' },
        { key: 'allow_warehouse', label: 'allow_warehouse', icon: 'mdi-warehouse', activeColor: '#9C27B0' },
        { key: 'allow_detail_truck_id', label: 'allow_detail_truck_id', icon: 'mdi-truck', activeColor: '#607D8B' },
      ],
      booleanKeys: ['allow_weight', 'allow_price', 'allow_package_type', 'allow_warehouse', 'allow_detail_truck_id'],
      summarySnackbar: { show: false, title: '', detail: '', color: 'success' },
      snackbarProgress: 100,
      snackbarTimer: null,
      search: '',
    };
  },
  computed: {
    main() { return useMain(); },
    wcoStore() { return useWarehouseCategoryOperation(); },
    headers() {
      return [
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_en'), key: 'name_en', align: 'center' },
        { title: this.$t('name_es'), key: 'name_es', align: 'center' },
        { title: this.$t('allow_weight'), key: 'allow_weight', align: 'center' },
        { title: this.$t('allow_price'), key: 'allow_price', align: 'center' },
        { title: this.$t('allow_package_type'), key: 'allow_package_type', align: 'center' },
        { title: this.$t('allow_warehouse'), key: 'allow_warehouse', align: 'center' },
        { title: this.$t('allow_detail_truck_id'), key: 'allow_detail_truck_id', align: 'center' },
        { title: this.$t('production_role'), key: 'production_role', align: 'center' },
        { title: this.$t('operation_type'), key: 'operation_type', align: 'center' },
        { title: this.$t('warehouse_type_label'), key: 'warehouse_type', align: 'center' },
        { title: this.$t('usage_count'), key: 'usage_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' },
      ];
    },
    productionRoleOptions() {
      return [
        { title: this.$t('fresh'), value: 'fresh' },
        { title: this.$t('frozen'), value: 'frozen' },
        { title: this.$t('guano'), value: 'guano' },
      ];
    },
    operationTypeOptions() {
      return [
        { title: this.$t('charge_type'), value: 'charge' },
        { title: this.$t('sell_type'), value: 'shipping' },
        { title: this.$t('both_type'), value: 'both' },
      ];
    },
    warehouseTypeOptions() {
      return [
        { title: this.$t('FishType'), value: 'FishType' },
        { title: this.$t('GuanoType'), value: 'GuanoType' },
        { title: this.$t('PackageType'), value: 'PackageType' },
      ];
    },
    filteredItems() {
      return this.items.filter(item => {
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
    this.fetchItems();
    mitt.on('model-changed', this._onModelChanged);
  },
  beforeUnmount() {
    mitt.off('model-changed', this._onModelChanged);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  methods: {
    emptyForm() {
      return { name_fr: '', name_en: '', name_es: '', description: '', allow_weight: false, allow_price: true, allow_package_type: true, allow_warehouse: true, allow_detail_truck_id: false, production_role: null, operation_type: 'both', warehouse_type: null };
    },
    getCellDisplayValue(key, item) {
      if (this.booleanKeys.includes(key)) return item[key] ? this.$t('yes') : this.$t('no');
      if (key === 'production_role') return item[key] ? this.$t(item[key]) : '-';
      if (key === 'operation_type') return item[key] ? this.$t(item[key] + '_type') : '-';
      if (key === 'warehouse_type') return item[key] ? this.$t(item[key]) : '-';
      if (key === 'usage_count') return String((item.warehouse_stockable_tracking_number_operations_count || 0) + (item.non_warehouse_stockable_operations_count || 0));
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    clearAllColumnFilters() { this.columnFilters = {}; },
    _onModelChanged(data) {
      if (data.model === 'WarehouseCategory') {
        this.fetchItems();
        if (data.action) {
          const actionText = data.action === 'created' ? this.$t('created') : data.action === 'updated' ? this.$t('updated') : this.$t('deleted');
          this.triggerSummarySnackbar(
            `${this.$t('warehouse_category_operation')}: ${actionText}`,
            data.data?.name_fr || data.data?.name_en || '',
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
    async fetchItems() {
      this.loading = true;
      try {
        const res = await axios.get('/api/warehouse-category-operations');
        this.items = res.data.warehouse_category_operations || [];
        this.wcoStore.list = this.items;
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = { name_fr: item.name_fr || '', name_en: item.name_en || '', name_es: item.name_es || '', description: item.description || '', allow_weight: !!item.allow_weight, allow_price: !!item.allow_price, allow_package_type: !!item.allow_package_type, allow_warehouse: !!item.allow_warehouse, allow_detail_truck_id: !!item.allow_detail_truck_id, production_role: item.production_role || null, operation_type: item.operation_type || 'both', warehouse_type: item.warehouse_type || null };
      } else { this.form = this.emptyForm(); }
      this.dialog = true;
    },
    async save() {
      this.saving = true;
      try {
        const displayName = this.form.name_fr || this.form.name_en || '';
        if (this.editing) {
          await axios.put(`/api/warehouse-category-operations/${this.editing.id}`, this.form);
          this.triggerSummarySnackbar(this.$t('wco_updated'), displayName, 'success');
        } else {
          await axios.post('/api/warehouse-category-operations', this.form);
          this.triggerSummarySnackbar(this.$t('wco_created'), displayName, 'success');
        }
        this.dialog = false;
        this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), this.$t('wco_save_error'), 'error');
      }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.confirmDeleteDialog = true; },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      const targetName = this.deleteTarget.name_fr || this.deleteTarget.name_en || '';
      try {
        await axios.delete(`/api/warehouse-category-operations/${this.deleteTarget.id}`);
        this.triggerSummarySnackbar(this.$t('wco_deleted'), targetName, 'success');
        this.confirmDeleteDialog = false;
        this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), this.$t('wco_delete_error'), 'error');
      }
      this.deleting = false;
      this.deleteTarget = null;
    },
    productionRoleColor(role) { return { fresh: 'blue', frozen: 'cyan', guano: 'brown' }[role] || 'grey'; },
    operationTypeColor(type) { return { charge: 'orange', sell: 'green', both: 'info' }[type] || 'grey'; },
    warehouseTypeColor(type) { return { FishType: 'blue', GuanoType: 'brown', PackageType: 'green' }[type] || 'grey'; },
  },
};
</script>

<style scoped>
.wco-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.wco-hero { background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.wco-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.wco-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.wco-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.wco-toggle-card { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 12px; border: 1.5px solid rgba(var(--v-border-color), .12); cursor: pointer; transition: all .2s ease; background: transparent; }
.wco-toggle-card:hover { border-color: rgba(var(--v-border-color), .25); }
.wco-toggle-active { border-color: var(--toggle-color) !important; background: color-mix(in srgb, var(--toggle-color) 4%, transparent); }
.wco-toggle-left { display: flex; align-items: center; gap: 10px; }
.wco-toggle-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: background .2s ease; }
.wco-toggle-info { min-width: 0; }
.wco-toggle-label { font-size: 13px; font-weight: 600; }
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

