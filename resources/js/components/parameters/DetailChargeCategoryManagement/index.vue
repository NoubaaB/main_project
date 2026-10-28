<template>
  <v-card class="dccm-card" :elevation="embedded ? 0 : 0" :class="{ 'dccm-embedded': embedded }">
    <div v-if="!embedded" class="dccm-hero">
      <div class="dccm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="dccm-hero-icon">
            <v-icon size="28" color="white">mdi-format-list-checks</v-icon>
          </div>
          <div>
            <div class="dccm-hero-title">{{ $t('detail_charge_category_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_detail_charge_categories_desc') }}</div>
          </div>
        </div>
      </div>
    </div>
    <v-card-text class="pa-4">
      <div class="param-top-toolbar mb-4">
        <div class="d-flex align-center ga-3 flex-wrap flex-grow-1">
          <v-autocomplete
            v-model="selectedChargeCategoryId"
            :items="chargeCategoryOptions"
            :label="$t('charge_category')"
            prepend-inner-icon="mdi-shape-outline"
            variant="outlined"
            density="compact"
            rounded="lg"
            color="#42A5F5"
            style="max-width:240px; min-width: 180px;"
            hide-details
            clearable
          ></v-autocomplete>
          <v-text-field
            v-model="search"
            :label="$t('search')"
            variant="outlined"
            density="compact"
            rounded="lg"
            color="#42A5F5"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            class="param-search-input"
          ></v-text-field>
          <v-chip size="small" variant="tonal" color="#42A5F5" class="font-weight-bold fnm-currency-num">
            <v-icon start size="14">mdi-format-list-checks</v-icon>
            {{ filteredItems.length }} {{ $t('records') }}
          </v-chip>
          <v-btn v-if="hasActiveColumnFilters" variant="tonal" color="warning" rounded="lg" size="small" prepend-icon="mdi-filter-off" @click="columnFilters = {}">{{ $t('clear_filters') }}</v-btn>
        </div>
        <v-btn color="#42A5F5" variant="flat" rounded="lg" prepend-icon="mdi-plus" class="text-capitalize font-weight-bold" elevation="2" @click="openDialog(null)" :disabled="!selectedChargeCategoryId">{{ $t('add_detail_category') }}</v-btn>
      </div>

      <v-data-table :headers="headers" :items="filteredItems" :loading="loading ? '#42A5F5' : false" :multi-sort="true" density="comfortable" class="rounded-xl param-table" hover :search="search">
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th" :style="col.width ? { width: col.width } : {}"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
          <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th" :style="col.width ? { width: col.width } : {}"><v-autocomplete v-if="col.key !== 'actions'" v-model="columnFilters[col.key]" :items="getColumnOptions(col.key)" density="compact" variant="outlined" color="#42A5F5" class="param-filter-input" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#42A5F5" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
        </template>
        <template v-slot:[`item.charge_category_name`]="{ item }">
          <v-chip color="#42A5F5" size="small" variant="tonal">{{ getCategoryName(item.charge_category_id) }}</v-chip>
        </template>
        <template v-slot:[`item.article_type`]="{ item }">
          <v-chip :color="articleTypeColor(item.article_type)" size="small" variant="tonal">{{ item.article_type }}</v-chip>
        </template>
        <template v-slot:[`item.fish_type_filter`]="{ item }">
          <v-chip v-if="item.article_type === 'FishType'" :color="fishFilterColor(item.fish_type_filter)" size="small" variant="tonal">{{ $t(item.fish_type_filter || 'all') }}</v-chip>
          <span v-else class="text-grey">-</span>
        </template>
        <template v-for="bk in booleanKeys" v-slot:[`item.${bk}`]="{ item }">
          <v-icon :color="item[bk] ? 'success' : 'grey'" size="20">{{ item[bk] ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
        </template>
        <template v-slot:[`item.warehouse_operation`]="{ item }">
          <v-chip v-if="item.warehouse_operation" :color="item.warehouse_operation === 'stock_in' ? 'success' : 'warning'" size="small" variant="tonal">{{ item.warehouse_operation }}</v-chip>
          <span v-else class="text-grey">-</span>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <div class="param-actions-cell">
            <v-tooltip :text="$t('edit')" location="top">
              <template #activator="{ props }">
                <v-btn v-bind="props" icon size="28" variant="tonal" color="#42A5F5" class="param-action-btn" @click="openDialog(item)">
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
    <v-dialog v-model="dialog" max-width="720" persistent scrollable>
      <v-card class="param-dialog-card">
        <div class="param-dialog-header">
          <div class="d-flex align-center ga-3">
            <div class="param-dialog-icon-badge" style="background: rgba(66, 165, 245, 0.12); color: #42A5F5;">
              <v-icon size="22">mdi-format-list-checks</v-icon>
            </div>
            <div>
              <div class="param-dialog-title">{{ editing ? $t('edit_detail_category') : $t('add_detail_category') }}</div>
              <div class="param-dialog-subtitle">{{ $t('parameters') }} • {{ $t('detail_charge_categories') }}</div>
            </div>
          </div>
          <v-btn icon variant="text" size="small" density="comfortable" @click="dialog = false">
            <v-icon size="18">mdi-close</v-icon>
          </v-btn>
        </div>
        <v-card-text class="param-dialog-body">
          <v-row dense>
            <v-col cols="12" sm="4"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#42A5F5"></v-text-field></v-col>
            <v-col cols="12" sm="4"><v-text-field v-model="form.name_en" :label="$t('name_en')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#42A5F5"></v-text-field></v-col>
            <v-col cols="12" sm="4"><v-text-field v-model="form.name_es" :label="$t('name_es')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#42A5F5"></v-text-field></v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="form.article_type" :items="articleTypes" :label="$t('type_article')" prepend-inner-icon="mdi-shape" variant="outlined" density="compact" rounded="lg" color="#42A5F5"></v-select>
            </v-col>
            <v-col cols="12" sm="6" v-if="form.article_type === 'FishType'">
              <v-select v-model="form.fish_type_filter" :items="[{title:$t('all'),value:'all'},{title:$t('fresh'),value:'fresh'},{title:$t('frozen'),value:'frozen'}]" :label="$t('fish_type_filter')" variant="outlined" density="compact" rounded="lg" color="#42A5F5">
                <template #prepend-inner><v-icon size="18" color="#42A5F5" class="mr-1">mdi-filter-variant</v-icon></template>
              </v-select>
            </v-col>
            <v-col cols="12" sm="6" v-if="form.allow_warehouse">
              <v-select v-model="form.warehouse_operation" :items="warehouseOps" :label="$t('warehouse_operation')" prepend-inner-icon="mdi-warehouse" variant="outlined" density="compact" rounded="lg" color="#42A5F5" clearable></v-select>
            </v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded="lg" color="#42A5F5" rows="2"></v-textarea></v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="#42A5F5" class="mr-2" size="20">mdi-toggle-switch-outline</v-icon>
            {{ $t('enabled_fields') }}
          </div>
          <v-row dense>
            <v-col cols="12" sm="6" v-for="toggle in toggleFields" :key="toggle.key">
              <div class="ccm-toggle-card rounded-lg" :class="{ 'ccm-toggle-active': form[toggle.key] }" :style="{ '--toggle-color': form[toggle.key] ? toggle.activeColor : 'transparent' }" @click="form[toggle.key] = !form[toggle.key]">
                <div class="ccm-toggle-left">
                  <div class="ccm-toggle-icon-wrap rounded-lg" :style="{ background: form[toggle.key] ? toggle.activeColor + '1A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form[toggle.key] ? toggle.activeColor : 'grey'" size="20">{{ toggle.icon }}</v-icon>
                  </div>
                  <div class="ccm-toggle-label">{{ $t(toggle.label) }}</div>
                </div>
                <v-switch v-model="form[toggle.key]" :color="toggle.activeColor" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="param-dialog-actions">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="tonal" rounded="lg" class="text-capitalize">{{ $t('cancel') }}</v-btn>
          <v-btn color="#42A5F5" variant="flat" rounded="lg" class="text-capitalize font-weight-bold" @click="save" :loading="saving">{{ $t('save') }}</v-btn>
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
            {{ $t('confirm_delete_message') }}
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
      <v-progress-linear :model-value="snackbarProgress" color="#42A5F5" height="3" class="mb-2" rounded></v-progress-linear>
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
import { useChargeCategory } from '@/stores/ChargeCategory';
import { useDetailChargeCategory } from '@/stores/DetailChargeCategory';

export default {
  name: 'DetailChargeCategoryManagement',
  props: { embedded: { type: Boolean, default: false } },
  data() {
    return {
      items: [], loading: false, selectedChargeCategoryId: null, columnFilters: {},
      dialog: false, editing: null, form: this.emptyForm(), saving: false,
      confirmDeleteDialog: false, deleteTarget: null, deleting: false,
      articleTypes: ['text', 'FishType', 'GuanoType', 'PackageType'],
      warehouseOps: [{ title: 'Stock In', value: 'stock_in' }, { title: 'Stock Out', value: 'stock_out' }],
      booleanKeys: ['allow_weight', 'allow_unit_price', 'allow_price', 'allow_package_type', 'allow_nbr_box', 'allow_warehouse', 'allow_detail_truck_id', 'include_in_invoice'],
      toggleFields: [
        { key: 'allow_weight', label: 'allow_weight', icon: 'mdi-weight-kilogram', activeColor: '#2196F3' },
        { key: 'allow_unit_price', label: 'allow_unit_price', icon: 'mdi-cash', activeColor: '#4CAF50' },
        { key: 'allow_price', label: 'allow_price', icon: 'mdi-currency-usd', activeColor: '#FF9800' },
        { key: 'allow_package_type', label: 'allow_package_type', icon: 'mdi-package-variant', activeColor: '#9C27B0' },
        { key: 'allow_nbr_box', label: 'allow_nbr_box', icon: 'mdi-archive-outline', activeColor: '#607D8B' },
        { key: 'allow_warehouse', label: 'allow_warehouse', icon: 'mdi-warehouse', activeColor: '#009688' },
        { key: 'allow_detail_truck_id', label: 'allow_detail_truck_id', icon: 'mdi-truck', activeColor: '#795548' },
        { key: 'include_in_invoice', label: 'include_in_invoice', icon: 'mdi-file-document-check-outline', activeColor: '#FF5722' },
      ],
      summarySnackbar: { show: false, title: '', detail: '', color: 'success' },
      snackbarProgress: 100,
      snackbarTimer: null,
      search: '',
    };
  },
  computed: {
    main() { return useMain(); },
    chargeCategoryStore() { return useChargeCategory(); },
    detailStore() { return useDetailChargeCategory(); },
    chargeCategoryOptions() {
      return this.chargeCategoryStore.list.map(c => ({ title: c[`name_${this.main.lang?.code || 'en'}`] || c.name || `#${c.id}`, value: c.id }));
    },
    filteredItems() {
      let list = this.selectedChargeCategoryId ? this.items.filter(i => i.charge_category_id === this.selectedChargeCategoryId) : this.items;
      return list.filter(item => Object.entries(this.columnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(this.getCellDisplayValue(key, item));
      }));
    },
    hasActiveColumnFilters() { return Object.values(this.columnFilters).some(f => f && f.length > 0); },
    headers() {
      return [
        { title: this.$t('charge_category'), key: 'charge_category_name', align: 'center' },
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_en'), key: 'name_en', align: 'center' },
        { title: this.$t('type_article'), key: 'article_type', align: 'center' },
        { title: this.$t('fish_type_filter'), key: 'fish_type_filter', align: 'center' },
        { title: this.$t('allow_weight'), key: 'allow_weight', align: 'center' },
        { title: this.$t('allow_unit_price'), key: 'allow_unit_price', align: 'center' },
        { title: this.$t('allow_price'), key: 'allow_price', align: 'center' },
        { title: this.$t('allow_warehouse'), key: 'allow_warehouse', align: 'center' },
        { title: this.$t('warehouse_operation'), key: 'warehouse_operation', align: 'center' },
        { title: this.$t('include_in_invoice'), key: 'include_in_invoice', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' },
      ];
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
    _onModelChanged(data) {
      if (['DetailChargeCategory', 'ChargeCategory'].includes(data.model)) {
        this.fetchItems();
        if (data.action && data.model === 'DetailChargeCategory') {
          const actionText = data.action === 'created' ? this.$t('created') : data.action === 'updated' ? this.$t('updated') : this.$t('deleted');
          this.triggerSummarySnackbar(
            `${this.$t('detail_charge_category')}: ${actionText}`,
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
    emptyForm() {
      return { name_fr: '', name_en: '', name_es: '', description: '', article_type: 'text', fish_type_filter: 'all', allow_weight: false, allow_unit_price: false, allow_price: false, allow_package_type: false, allow_nbr_box: false, allow_warehouse: false, allow_detail_truck_id: false, warehouse_operation: null, include_in_invoice: true, charge_category_id: null };
    },
    getCategoryName(categoryId) {
      const cat = this.chargeCategoryStore.list.find(c => c.id === categoryId);
      return cat ? (cat[`name_${this.main.lang?.code || 'en'}`] || cat.name || `#${categoryId}`) : `#${categoryId}`;
    },
    getCellDisplayValue(key, item) {
      if (key === 'charge_category_name') return this.getCategoryName(item.charge_category_id);
      if (this.booleanKeys.includes(key)) return item[key] ? this.$t('yes') : this.$t('no');
      if (key === 'article_type') return item.article_type || '';
      if (key === 'fish_type_filter') return item.article_type === 'FishType' ? this.$t(item.fish_type_filter || 'all') : '-';
      if (key === 'warehouse_operation') return item.warehouse_operation || '-';
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      const src = this.selectedChargeCategoryId ? this.items.filter(i => i.charge_category_id === this.selectedChargeCategoryId) : this.items;
      src.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    articleTypeColor(t) { return { text: 'grey', FishType: 'blue', GuanoType: 'brown', PackageType: 'green' }[t] || 'grey'; },
    fishFilterColor(f) { return { all: 'info', fresh: 'cyan', frozen: 'indigo' }[f] || 'grey'; },
    async fetchItems() {
      this.loading = true;
      try {
        const res = await axios.get('/api/detail-charge-categories');
        this.items = res.data.detail_charge_categories || [];
        this.detailStore.list = this.items;
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) { this.form = { ...item }; }
      else { this.form = this.emptyForm(); this.form.charge_category_id = this.selectedChargeCategoryId; }
      this.dialog = true;
    },
    async save() {
      this.saving = true;
      try {
        const displayName = this.form.name_fr || this.form.name_en || '';
        if (this.editing) {
          await axios.put(`/api/detail-charge-categories/${this.editing.id}`, this.form);
          this.triggerSummarySnackbar(this.$t('detail_category_updated'), displayName, 'success');
        } else {
          await axios.post('/api/detail-charge-categories', this.form);
          this.triggerSummarySnackbar(this.$t('detail_category_created'), displayName, 'success');
        }
        this.dialog = false;
        await this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), this.$t('detail_category_save_error'), 'error');
      }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.confirmDeleteDialog = true; },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      const targetName = this.deleteTarget.name_fr || this.deleteTarget.name_en || '';
      try {
        await axios.delete(`/api/detail-charge-categories/${this.deleteTarget.id}`);
        this.triggerSummarySnackbar(this.$t('detail_category_deleted'), targetName, 'success');
        this.confirmDeleteDialog = false;
        await this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), 'Delete failed', 'error');
      }
      this.deleting = false;
      this.deleteTarget = null;
    },
  },
};
</script>

<style scoped>
.dccm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.dccm-hero { background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.dccm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.dccm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.dccm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.ccm-toggle-card { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 12px; border: 1.5px solid rgba(var(--v-border-color), .12); cursor: pointer; transition: all .2s ease; }
.ccm-toggle-card:hover { border-color: rgba(var(--v-border-color), .25); }
.ccm-toggle-active { border-color: var(--toggle-color) !important; background: color-mix(in srgb, var(--toggle-color) 4%, transparent); }
.ccm-toggle-left { display: flex; align-items: center; gap: 10px; }
.ccm-toggle-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.ccm-toggle-label { font-size: 13px; font-weight: 600; }
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
