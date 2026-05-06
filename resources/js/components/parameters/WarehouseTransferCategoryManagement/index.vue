<template>
  <v-card class="wtcm-card" elevation="0">
    <div class="wtcm-hero">
      <div class="wtcm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="wtcm-hero-icon">
            <v-icon size="28" color="white">mdi-swap-horizontal</v-icon>
          </div>
          <div>
            <div class="wtcm-hero-title">{{ $t('warehouse_transfer_category_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_transfer_categories_desc') }}</div>
          </div>
        </div>
      </div>
    </div>
    <v-card-text class="pa-4">
      <div class="d-flex align-center ga-3 mb-3">
        <v-spacer></v-spacer>
        <v-btn v-if="hasActiveColumnFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="columnFilters = {}">{{ $t('clear_filters') }}</v-btn>
        <v-btn color="#F9A825" prepend-icon="mdi-plus" rounded @click="openDialog(null)">{{ $t('add_transfer_category') }}</v-btn>
      </div>
      <v-data-table :headers="headers" :items="filteredItems" :loading="loading ? '#F9A825' : false" density="comfortable" class="rounded-lg param-table" hover>
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
          <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="columnFilters[col.key]" :items="getColumnOptions(col.key)" density="compact" variant="outlined" color="#F9A825" class="param-filter-input" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#F9A825" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
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
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="#F9A825" @click="openDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <v-dialog v-model="dialog" max-width="700" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#F9A825" class="mr-2">mdi-swap-horizontal</v-icon>
          {{ editing ? $t('edit_transfer_category') : $t('add_transfer_category') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="6"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" variant="outlined" density="compact" rounded color="#F9A825"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_en" :label="$t('name_en')" variant="outlined" density="compact" rounded color="#F9A825"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_es" :label="$t('name_es')" variant="outlined" density="compact" rounded color="#F9A825"></v-text-field></v-col>
            <v-col cols="6">
              <v-select v-model="form.article_type" :items="articleTypes" :label="$t('type_article')" variant="outlined" density="compact" rounded color="#F9A825"></v-select>
            </v-col>
            <v-col cols="6" v-if="form.article_type === 'FishType'">
              <v-select v-model="form.fish_type_filter" :items="[{title:$t('all'),value:'all'},{title:$t('fresh'),value:'fresh'},{title:$t('frozen'),value:'frozen'}]" :label="$t('fish_type_filter')" variant="outlined" density="compact" rounded color="#F9A825">
                <template #prepend-inner><v-icon size="18" color="#F9A825" class="mr-1">mdi-filter-variant</v-icon></template>
              </v-select>
            </v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" variant="outlined" density="compact" rounded color="#F9A825" rows="2"></v-textarea></v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="#F9A825" class="mr-2" size="20">mdi-toggle-switch-outline</v-icon>
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
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="#F9A825" variant="flat" rounded @click="save" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="confirmDeleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_message') }}</v-card-text>
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

export default {
  name: 'WarehouseTransferCategoryManagement',
  data() {
    return {
      items: [], loading: false, columnFilters: {},
      dialog: false, editing: null, form: this.emptyForm(), saving: false,
      confirmDeleteDialog: false, deleteTarget: null, deleting: false,
      articleTypes: ['text', 'FishType', 'GuanoType', 'PackageType'],
      booleanKeys: ['allow_weight', 'allow_package_type', 'allow_nbr_box', 'allow_warehouse', 'allow_trucks'],
      toggleFields: [
        { key: 'allow_weight', label: 'allow_weight', icon: 'mdi-weight-kilogram', activeColor: '#2196F3' },
        { key: 'allow_package_type', label: 'allow_package_type', icon: 'mdi-package-variant', activeColor: '#9C27B0' },
        { key: 'allow_nbr_box', label: 'allow_nbr_box', icon: 'mdi-archive-outline', activeColor: '#607D8B' },
        { key: 'allow_warehouse', label: 'allow_warehouse', icon: 'mdi-warehouse', activeColor: '#009688' },
        { key: 'allow_trucks', label: 'allow_trucks', icon: 'mdi-truck', activeColor: '#FF5722' },
      ],
    };
  },
  computed: {
    main() { return useMain(); },
    filteredItems() {
      return this.items.filter(item => Object.entries(this.columnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(this.getCellDisplayValue(key, item));
      }));
    },
    hasActiveColumnFilters() { return Object.values(this.columnFilters).some(f => f && f.length > 0); },
    headers() {
      return [
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_en'), key: 'name_en', align: 'center' },
        { title: this.$t('type_article'), key: 'article_type', align: 'center' },
        { title: this.$t('fish_type_filter'), key: 'fish_type_filter', align: 'center' },
        { title: this.$t('allow_weight'), key: 'allow_weight', align: 'center' },
        { title: this.$t('allow_package_type'), key: 'allow_package_type', align: 'center' },
        { title: this.$t('allow_nbr_box'), key: 'allow_nbr_box', align: 'center' },
        { title: this.$t('allow_warehouse'), key: 'allow_warehouse', align: 'center' },
        { title: this.$t('allow_trucks'), key: 'allow_trucks', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
  },
  mounted() {
    this.fetchItems();
    mitt.on('model-changed', this._onModelChanged);
  },
  beforeUnmount() {
    mitt.off('model-changed', this._onModelChanged);
  },
  methods: {
    _onModelChanged(data) {
      if (data.model === 'WarehouseTransferCategory') this.fetchItems();
    },
    emptyForm() {
      return { name_fr: '', name_en: '', name_es: '', description: '', article_type: 'text', fish_type_filter: 'all', allow_weight: false, allow_package_type: false, allow_nbr_box: false, allow_warehouse: false, allow_trucks: false };
    },
    getCellDisplayValue(key, item) {
      if (this.booleanKeys.includes(key)) return item[key] ? this.$t('yes') : this.$t('no');
      if (key === 'article_type') return item.article_type || '';
      if (key === 'fish_type_filter') return item.article_type === 'FishType' ? this.$t(item.fish_type_filter || 'all') : '-';
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    articleTypeColor(t) { return { text: 'grey', FishType: 'blue', GuanoType: 'brown', PackageType: 'green' }[t] || 'grey'; },
    fishFilterColor(f) { return { all: 'info', fresh: 'cyan', frozen: 'indigo' }[f] || 'grey'; },
    async fetchItems() {
      this.loading = true;
      try {
        const res = await axios.get('/api/warehouse-transfer-categories');
        this.items = res.data.warehouse_transfer_categories || [];
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) { this.form = { ...item }; }
      else { this.form = this.emptyForm(); }
      this.dialog = true;
    },
    async save() {
      this.saving = true;
      try {
        if (this.editing) {
          await axios.put(`/api/warehouse-transfer-categories/${this.editing.id}`, this.form);
          this.main.add_snackbar({ show: true, message: this.$t('transfer_category_updated'), color: 'success' });
        } else {
          await axios.post('/api/warehouse-transfer-categories', this.form);
          this.main.add_snackbar({ show: true, message: this.$t('transfer_category_created'), color: 'success' });
        }
        this.dialog = false;
        await this.fetchItems();
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('detail_category_save_error'), color: 'error' });
      }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.confirmDeleteDialog = true; },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/warehouse-transfer-categories/${this.deleteTarget.id}`);
        this.main.add_snackbar({ show: true, message: this.$t('transfer_category_deleted'), color: 'success' });
        this.confirmDeleteDialog = false;
        await this.fetchItems();
      } catch (e) { console.error(e); }
      this.deleting = false;
      this.deleteTarget = null;
    },
  },
};
</script>

<style scoped>
.wtcm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.wtcm-hero { background: linear-gradient(135deg, #F57F17 0%, #F9A825 40%, #FDD835 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.wtcm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.wtcm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.wtcm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
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
