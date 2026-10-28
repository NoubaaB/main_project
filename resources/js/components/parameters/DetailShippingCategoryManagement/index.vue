<template>
  <v-card class="dscm-card" :elevation="embedded ? 0 : 0" :class="{ 'dscm-embedded': embedded }">
    <div v-if="!embedded" class="dscm-hero">
      <div class="dscm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="dscm-hero-icon"><v-icon size="28" color="white">mdi-format-list-checks</v-icon></div>
          <div>
            <div class="dscm-hero-title">{{ $t('detail_shipping_category_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_detail_sell_categories_desc') }}</div>
          </div>
        </div>
      </div>
    </div>
    <v-card-text class="pa-4">
      <!-- Top Toolbar (Category selector, Search, Filter, Count & Add) -->
      <div class="param-top-toolbar mb-4">
        <div class="d-flex align-center flex-grow-1 ga-3" style="max-width: 600px;">
          <v-autocomplete
            v-model="selectedCategoryId"
            :items="categoryOptions"
            :label="$t('shipping_category')"
            prepend-inner-icon="mdi-tag-multiple-outline"
            variant="outlined"
            density="compact"
            rounded="lg"
            color="#4643a0"
            hide-details
            clearable
            style="min-width: 240px;"
          ></v-autocomplete>
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
            <v-icon start size="14">mdi-format-list-checks</v-icon>
            {{ filteredItems.length }} {{ $t('records') || 'enregistrements' }}
          </v-chip>
          <v-btn
            v-if="hasActiveColumnFilters || search"
            variant="tonal"
            color="warning"
            rounded="lg"
            size="small"
            prepend-icon="mdi-filter-off"
            @click="columnFilters = {}; search = ''"
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
            :disabled="!selectedCategoryId"
          >
            {{ $t('add_detail_category') }}
          </v-btn>
        </div>
      </div>
      <v-data-table :headers="headers" :items="filteredItems" :search="search" :loading="loading ? '#4643a0' : false" :multi-sort="true" density="comfortable" class="rounded-xl param-table" hover>
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
          <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="columnFilters[col.key]" :items="getColumnOptions(col.key)" density="compact" variant="outlined" color="#4643a0" class="param-filter-input" style="color: #4643a0" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#4643a0" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
        </template>
        <template v-slot:[`item.shipping_category_name`]="{ item }">
          <v-chip color="#4643a0" size="small" variant="tonal" class="font-weight-bold">{{ getCategoryName(item.shipping_category_id) }}</v-chip>
        </template>
        <template v-slot:[`item.article_type`]="{ item }"><v-chip :color="articleTypeColor(item.article_type)" size="small" variant="tonal" class="font-weight-bold">{{ item.article_type }}</v-chip></template>
        <template v-slot:[`item.fish_type_filter`]="{ item }">
          <v-chip v-if="item.article_type === 'FishType'" :color="fishFilterColor(item.fish_type_filter)" size="small" variant="tonal">{{ $t(item.fish_type_filter || 'all') }}</v-chip>
          <span v-else class="text-grey">-</span>
        </template>
        <template v-for="bk in booleanKeys" v-slot:[`item.${bk}`]="{ item }"><v-icon :color="item[bk] ? 'success' : 'grey'" size="20">{{ item[bk] ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon></template>
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
    <v-dialog v-model="dialog" max-width="700" persistent scrollable>
      <v-card class="rounded-xl overflow-hidden param-dialog-card" elevation="24">
        <div class="param-dialog-header">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="param-dialog-icon-badge" style="background: rgba(70, 67, 160, 0.15); color: #4643a0;">
                <v-icon size="24">{{ editing ? 'mdi-pencil-box-outline' : 'mdi-plus-circle-outline' }}</v-icon>
              </div>
              <div>
                <div class="param-dialog-title">{{ editing ? $t('edit_detail_category') : $t('add_detail_category') }}</div>
                <div class="param-dialog-subtitle">{{ $t('manage_detail_sell_categories_desc') }}</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" size="small" class="param-dialog-close" @click="dialog = false" />
          </div>
        </div>
        <v-card-text class="pa-5 param-dialog-body" style="max-height: 75vh;">
          <v-row dense>
            <v-col cols="6"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#4643a0"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_en" :label="$t('name_en')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#4643a0"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_es" :label="$t('name_es')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#4643a0"></v-text-field></v-col>
            <v-col cols="6"><v-select v-model="form.article_type" :items="['text','FishType','GuanoType','PackageType']" :label="$t('type_article')" prepend-inner-icon="mdi-shape" variant="outlined" density="compact" rounded="lg" color="#4643a0"></v-select></v-col>
            <v-col cols="6" v-if="form.article_type === 'FishType'"><v-select v-model="form.fish_type_filter" :items="[{title:$t('all'),value:'all'},{title:$t('fresh'),value:'fresh'},{title:$t('frozen'),value:'frozen'}]" :label="$t('fish_type_filter')" variant="outlined" density="compact" rounded="lg" color="#4643a0" prepend-inner-icon="mdi-filter-variant"></v-select></v-col>
            <v-col cols="6" v-if="form.allow_warehouse"><v-select v-model="form.warehouse_operation" :items="[{title:'Stock In',value:'stock_in'},{title:'Stock Out',value:'stock_out'}]" :label="$t('warehouse_operation')" prepend-inner-icon="mdi-warehouse" variant="outlined" density="compact" rounded="lg" color="#4643a0" clearable></v-select></v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded="lg" color="#4643a0" rows="2" auto-grow></v-textarea></v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="#4643a0" class="mr-2" size="20">mdi-toggle-switch-outline</v-icon>
            {{ $t('enabled_fields') }}
          </div>
          <v-row dense>
            <v-col cols="6" v-for="toggle in toggleFields" :key="toggle.key">
              <div class="ccm-toggle-card rounded-lg pa-2 border d-flex align-center justify-space-between cursor-pointer" :class="{ 'ccm-toggle-active': form[toggle.key] }" :style="{ '--toggle-color': form[toggle.key] ? toggle.activeColor : 'transparent' }" @click="form[toggle.key] = !form[toggle.key]">
                <div class="d-flex align-center ga-2">
                  <div class="ccm-toggle-icon-wrap" :style="{ background: form[toggle.key] ? toggle.activeColor + '1A' : 'rgba(var(--v-border-color), .06)' }">
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
    <v-dialog v-model="confirmDeleteDialog" max-width="440">
      <v-card class="rounded-xl overflow-hidden param-dialog-card">
        <div class="pa-6 text-center">
          <div class="param-dialog-icon-badge mx-auto mb-4" style="background: rgba(244, 67, 54, 0.12); color: #f44336; width: 56px; height: 56px;">
            <v-icon size="32">mdi-alert-circle-outline</v-icon>
          </div>
          <div class="text-h6 font-weight-bold mb-2">{{ $t('confirm_delete') }}</div>
          <div class="text-body-2 text-medium-emphasis mb-4">
            {{ $t('confirm_delete_message') }}
          </div>
          <div v-if="deleteTarget" class="pa-3 rounded-lg text-left" style="background: rgba(244,67,54,.06); border: 1px solid rgba(244, 67, 54, 0.15);">
            <div class="font-weight-bold">{{ deleteTarget.name_fr || deleteTarget.name_en || deleteTarget.name_es }}</div>
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
import { useDetailShippingCategory } from '@/stores/DetailShippingCategory';
export default {
  name: 'DetailShippingCategoryManagement',
  props: { embedded: { type: Boolean, default: false } },
  data() {
    return {
      search: '',
      items: [], loading: false, selectedCategoryId: null, columnFilters: {},
      dialog: false, editing: null, form: this.emptyForm(), saving: false,
      confirmDeleteDialog: false, deleteTarget: null, deleting: false,
      booleanKeys: ['allow_weight','allow_unit_price','allow_price','allow_package_type','allow_nbr_box','allow_warehouse','allow_detail_truck_id','include_in_invoice'],
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
    };
  },
  computed: {
    main() { return useMain(); },
    categoryOptions() { return useShippingCategory().list.map(c => ({ title: c[`name_${this.main.lang?.code||'en'}`]||c.name||`#${c.id}`, value: c.id })); },
    filteredItems() {
      let list = this.selectedCategoryId ? this.items.filter(i => i.shipping_category_id === this.selectedCategoryId) : this.items;
      return list.filter(item => Object.entries(this.columnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(this.getCellDisplayValue(key, item));
      }));
    },
    hasActiveColumnFilters() { return Object.values(this.columnFilters).some(f => f && f.length > 0); },
    headers() { return [ { title: this.$t('shipping_category'), key: 'shipping_category_name', align: 'center' }, { title: this.$t('name_fr'), key: 'name_fr', align: 'center' }, { title: this.$t('type_article'), key: 'article_type', align: 'center' }, { title: this.$t('fish_type_filter'), key: 'fish_type_filter', align: 'center' }, ...this.booleanKeys.map(k => ({ title: this.$t(k), key: k, align: 'center' })), { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' } ]; },
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
      if (['DetailShippingCategory', 'DetailshippingCategory', 'ShippingCategory', 'shippingCategory', 'sellCategory'].includes(data.model)) {
        this.fetchItems();
        if (data.action && ['DetailShippingCategory', 'DetailshippingCategory'].includes(data.model)) {
          const actionText = data.action === 'created' ? this.$t('created') : data.action === 'updated' ? this.$t('updated') : this.$t('deleted');
          this.triggerSummarySnackbar(
            `${this.$t('detail_shipping_category')}: ${actionText}`,
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
    emptyForm() { return { name_fr:'',name_en:'',name_es:'',description:'',article_type:'text',fish_type_filter:'all',allow_weight:false,allow_unit_price:false,allow_price:false,allow_package_type:false,allow_nbr_box:false,allow_warehouse:false,warehouse_operation:null,allow_detail_truck_id:false,include_in_invoice:true,shipping_category_id:null }; },
    getCategoryName(categoryId) {
      const cat = useShippingCategory().list.find(c => c.id === categoryId);
      return cat ? (cat[`name_${this.main.lang?.code || 'en'}`] || cat.name || `#${categoryId}`) : `#${categoryId}`;
    },
    getCellDisplayValue(key, item) {
      if (key === 'shipping_category_name') return this.getCategoryName(item.shipping_category_id);
      if (this.booleanKeys.includes(key)) return item[key] ? this.$t('yes') : this.$t('no');
      if (key === 'article_type') return item.article_type || '';
      if (key === 'fish_type_filter') return item.article_type === 'FishType' ? this.$t(item.fish_type_filter || 'all') : '-';
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      const src = this.selectedCategoryId ? this.items.filter(i => i.shipping_category_id === this.selectedCategoryId) : this.items;
      src.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    articleTypeColor(t) { return {text:'grey',FishType:'blue',GuanoType:'brown',PackageType:'green'}[t]||'grey'; },
    fishFilterColor(f) { return {all:'info',fresh:'cyan',frozen:'indigo'}[f]||'grey'; },
    async fetchItems() {
      this.loading = true;
      try {
        const r = await axios.get('/api/detail-shipping-categories');
        this.items = r.data.detail_shipping_categories || r.data.detail_sell_categories || [];
        useDetailShippingCategory().list = this.items;
        if (!useShippingCategory().list.length) {
          const catRes = await axios.get('/api/shipping-categories');
          useShippingCategory().list = catRes.data.shipping_categories || catRes.data.sell_categories || [];
        }
      } catch(e) { console.error(e); }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      this.form = item ? { ...item } : this.emptyForm();
      if (!item) this.form.shipping_category_id = this.selectedCategoryId;
      this.dialog = true;
    },
    async save() {
      this.saving = true;
      try {
        const displayName = this.form.name_fr || this.form.name_en || '';
        if (this.editing) {
          await axios.put(`/api/detail-shipping-categories/${this.editing.id}`, this.form);
          this.triggerSummarySnackbar(this.$t('detail_category_updated'), displayName, 'success');
        } else {
          await axios.post('/api/detail-shipping-categories', this.form);
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
        await axios.delete(`/api/detail-shipping-categories/${this.deleteTarget.id}`);
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
.dscm-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.dscm-hero { background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.dscm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.dscm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.dscm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.ccm-toggle-card { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 12px; border: 1.5px solid rgba(var(--v-border-color), .12); cursor: pointer; transition: all .2s ease; }
.ccm-toggle-card:hover { border-color: rgba(var(--v-border-color), .25); }
.ccm-toggle-active { border-color: var(--toggle-color) !important; background: color-mix(in srgb, var(--toggle-color) 4%, transparent); }
.ccm-toggle-left { display: flex; align-items: center; gap: 10px; }
.ccm-toggle-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.ccm-toggle-label { font-size: 13px; font-weight: 600; }
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

