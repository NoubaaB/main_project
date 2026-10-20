<template>
  <v-card class="dpcm-card" :elevation="embedded ? 0 : 0" :class="{ 'dpcm-embedded': embedded }">
    <div v-if="!embedded" class="dpcm-hero">
      <div class="dpcm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="dpcm-hero-icon"><v-icon size="28" color="white">mdi-format-list-checks</v-icon></div>
          <div>
            <div class="dpcm-hero-title">{{ $t('detail_production_category_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_detail_production_categories_desc') }}</div>
          </div>
        </div>
      </div>
    </div>
    <v-card-text class="pa-4">
      <div class="d-flex align-center ga-3 mb-3">
        <v-autocomplete v-model="selectedCategoryId" :items="categoryOptions" :label="$t('production_category')" prepend-inner-icon="mdi-cog-outline" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B; max-width:300px" hide-details clearable></v-autocomplete>
        <v-spacer></v-spacer>
        <v-btn v-if="hasActiveColumnFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="columnFilters = {}">{{ $t('clear_filters') }}</v-btn>
        <v-btn color="#00897B" prepend-icon="mdi-plus" rounded @click="openDialog(null)" :disabled="!selectedCategoryId">{{ $t('add_detail_category') }}</v-btn>
      </div>
      <v-data-table :headers="headers" :items="filteredItems" :loading="loading ? '#00897B' : false" :multi-sort="true" density="comfortable" class="rounded-lg param-table" hover>
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
          <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="columnFilters[col.key]" :items="getColumnOptions(col.key)" density="compact" variant="outlined" color="#00897B" class="param-filter-input" style="color: #00897B" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#00897B" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
        </template>
        <template v-slot:[`item.production_category_name`]="{ item }">
          <v-chip color="#00897B" size="small" variant="tonal">{{ getCategoryName(item.production_category_id) }}</v-chip>
        </template>
        <template v-slot:[`item.article_type`]="{ item }"><v-chip :color="articleTypeColor(item.article_type)" size="small" variant="tonal">{{ item.article_type }}</v-chip></template>
        <template v-slot:[`item.fish_type_filter`]="{ item }">
          <v-chip v-if="item.article_type === 'FishType'" :color="fishFilterColor(item.fish_type_filter)" size="small" variant="tonal">{{ $t(item.fish_type_filter || 'all') }}</v-chip>
          <span v-else class="text-grey">-</span>
        </template>
        <template v-for="bk in booleanKeys" v-slot:[`item.${bk}`]="{ item }"><v-icon :color="item[bk] ? 'success' : 'grey'" size="20">{{ item[bk] ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon></template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="#00897B" @click="openDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
        </template>
      </v-data-table>
    </v-card-text>
    <v-dialog v-model="dialog" max-width="700" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2"><v-icon color="#00897B" class="mr-2">mdi-format-list-checks</v-icon>{{ editing ? $t('edit_detail_category') : $t('add_detail_category') }}</v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="6"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_en" :label="$t('name_en')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_es" :label="$t('name_es')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B"></v-text-field></v-col>
            <v-col cols="6"><v-select v-model="form.article_type" :items="['text','FishType','GuanoType','PackageType']" :label="$t('type_article')" prepend-inner-icon="mdi-shape" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B"></v-select></v-col>
            <v-col cols="6" v-if="form.article_type === 'FishType'"><v-select v-model="form.fish_type_filter" :items="[{title:$t('all'),value:'all'},{title:$t('fresh'),value:'fresh'},{title:$t('frozen'),value:'frozen'}]" :label="$t('fish_type_filter')" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B"><template #prepend-inner><v-icon size="18" color="#00897B" class="mr-1">mdi-filter-variant</v-icon></template></v-select></v-col>
            <v-col cols="6" v-if="form.allow_warehouse"><v-select v-model="form.warehouse_operation" :items="[{title:'Stock In',value:'stock_in'},{title:'Stock Out',value:'stock_out'}]" :label="$t('warehouse_operation')" prepend-inner-icon="mdi-warehouse" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" clearable></v-select></v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" rows="2"></v-textarea></v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="#00897B" class="mr-2" size="20">mdi-toggle-switch-outline</v-icon>
            {{ $t('enabled_fields') }}
          </div>
          <v-row dense>
            <v-col cols="6" v-for="toggle in toggleFields" :key="toggle.key">
              <div class="ccm-toggle-card" :class="{ 'ccm-toggle-active': form[toggle.key] }" :style="{ '--toggle-color': form[toggle.key] ? toggle.activeColor : 'transparent' }" @click="form[toggle.key] = !form[toggle.key]">
                <div class="ccm-toggle-left">
                  <div class="ccm-toggle-icon-wrap" :style="{ background: form[toggle.key] ? toggle.activeColor + '1A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form[toggle.key] ? toggle.activeColor : 'grey'" size="20">{{ toggle.icon }}</v-icon>
                  </div>
                  <div class="ccm-toggle-label">{{ $t(toggle.label) }}</div>
                </div>
                <v-switch v-model="form[toggle.key]" :color="toggle.activeColor" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="dialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="#00897B" variant="flat" rounded @click="save" :loading="saving">{{ $t('save') }}</v-btn></v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="confirmDeleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="pa-5 pb-2"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
        <v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_message') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
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
      <v-progress-linear :model-value="snackbarProgress" color="#00897B" height="3" class="mb-2" rounded></v-progress-linear>
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
import { useProductionCategory } from '@/stores/ProductionCategory';
import { useDetailProductionCategory } from '@/stores/DetailProductionCategory';
export default {
  name: 'DetailProductionCategoryManagement',
  props: { embedded: { type: Boolean, default: false } },
  data() {
    return {
      items: [], loading: false, selectedCategoryId: null, columnFilters: {},
      dialog: false, editing: null, form: this.emptyForm(), saving: false,
      confirmDeleteDialog: false, deleteTarget: null, deleting: false,
      booleanKeys: ['allow_weight','allow_unit_price','allow_price','allow_package_type','allow_nbr_box','allow_warehouse','allow_detail_truck_id','include_in_invoice','allow_secondary_packaging'],
      toggleFields: [
        { key: 'allow_weight', label: 'allow_weight', icon: 'mdi-weight-kilogram', activeColor: '#2196F3' },
        { key: 'allow_unit_price', label: 'allow_unit_price', icon: 'mdi-cash', activeColor: '#4CAF50' },
        { key: 'allow_price', label: 'allow_price', icon: 'mdi-currency-usd', activeColor: '#FF9800' },
        { key: 'allow_package_type', label: 'allow_package_type', icon: 'mdi-package-variant', activeColor: '#9C27B0' },
        { key: 'allow_nbr_box', label: 'allow_nbr_box', icon: 'mdi-archive-outline', activeColor: '#607D8B' },
        { key: 'allow_warehouse', label: 'allow_warehouse', icon: 'mdi-warehouse', activeColor: '#009688' },
        { key: 'allow_detail_truck_id', label: 'allow_detail_truck_id', icon: 'mdi-truck', activeColor: '#795548' },
        { key: 'include_in_invoice', label: 'include_in_invoice', icon: 'mdi-file-document-check-outline', activeColor: '#FF5722' },
        { key: 'allow_secondary_packaging', label: 'allow_secondary_packaging', icon: 'mdi-layers-outline', activeColor: '#00897B' },
      ],
      summarySnackbar: { show: false, title: '', detail: '', color: 'success' },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    categoryOptions() { return useProductionCategory().list.map(c => ({ title: c[`name_${this.main.lang?.code||'en'}`]||c.name||`#${c.id}`, value: c.id })); },
    filteredItems() {
      let list = this.selectedCategoryId ? this.items.filter(i => i.production_category_id === this.selectedCategoryId) : this.items;
      return list.filter(item => Object.entries(this.columnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(this.getCellDisplayValue(key, item));
      }));
    },
    hasActiveColumnFilters() { return Object.values(this.columnFilters).some(f => f && f.length > 0); },
    headers() { return [ { title: this.$t('production_category'), key: 'production_category_name', align: 'center' }, { title: this.$t('name_fr'), key: 'name_fr', align: 'center' }, { title: this.$t('type_article'), key: 'article_type', align: 'center' }, { title: this.$t('fish_type_filter'), key: 'fish_type_filter', align: 'center' }, ...this.booleanKeys.map(k => ({ title: this.$t(k), key: k, align: 'center' })), { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false } ]; },
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
      if (['DetailProductionCategory', 'ProductionCategory'].includes(data.model)) {
        this.fetchItems();
        if (data.action && data.model === 'DetailProductionCategory') {
          const actionText = data.action === 'created' ? this.$t('created') : data.action === 'updated' ? this.$t('updated') : this.$t('deleted');
          this.triggerSummarySnackbar(
            `${this.$t('detail_production_category')}: ${actionText}`,
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
    emptyForm() { return { name_fr:'',name_en:'',name_es:'',description:'',article_type:'text',fish_type_filter:'all',allow_weight:false,allow_unit_price:false,allow_price:false,allow_package_type:false,allow_nbr_box:false,allow_warehouse:false,warehouse_operation:null,allow_detail_truck_id:false,include_in_invoice:true,allow_secondary_packaging:false,production_category_id:null }; },
    getCategoryName(categoryId) {
      const cat = useProductionCategory().list.find(c => c.id === categoryId);
      return cat ? (cat[`name_${this.main.lang?.code || 'en'}`] || cat.name || `#${categoryId}`) : `#${categoryId}`;
    },
    getCellDisplayValue(key, item) {
      if (key === 'production_category_name') return this.getCategoryName(item.production_category_id);
      if (this.booleanKeys.includes(key)) return item[key] ? this.$t('yes') : this.$t('no');
      if (key === 'article_type') return item.article_type || '';
      if (key === 'fish_type_filter') return item.article_type === 'FishType' ? this.$t(item.fish_type_filter || 'all') : '-';
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      const src = this.selectedCategoryId ? this.items.filter(i => i.production_category_id === this.selectedCategoryId) : this.items;
      src.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    articleTypeColor(t) { return {text:'grey',FishType:'blue',GuanoType:'brown',PackageType:'green'}[t]||'grey'; },
    fishFilterColor(f) { return {all:'info',fresh:'cyan',frozen:'indigo'}[f]||'grey'; },
    async fetchItems() { this.loading=true; try { const r=await axios.get('/api/detail-production-categories'); this.items=r.data.detail_production_categories||[]; useDetailProductionCategory().list=this.items; } catch(e){console.error(e);} this.loading=false; },
    openDialog(item) { this.editing=item; this.form=item?{...item}:this.emptyForm(); if(!item) this.form.production_category_id=this.selectedCategoryId; this.dialog=true; },
    async save() {
      this.saving = true;
      try {
        const displayName = this.form.name_fr || this.form.name_en || '';
        if (this.editing) {
          await axios.put(`/api/detail-production-categories/${this.editing.id}`, this.form);
          this.triggerSummarySnackbar(this.$t('detail_category_updated'), displayName, 'success');
        } else {
          await axios.post('/api/detail-production-categories', this.form);
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
        await axios.delete(`/api/detail-production-categories/${this.deleteTarget.id}`);
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
.dpcm-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.dpcm-hero { background: linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.dpcm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.dpcm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.dpcm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
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
