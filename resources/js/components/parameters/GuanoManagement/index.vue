<template>
  <v-card class="gm-card" elevation="0">
    <!-- Hero Header -->
    <div class="gm-hero">
      <div class="gm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="gm-hero-icon">
            <v-icon size="28" color="white">mdi-leaf</v-icon>
          </div>
          <div>
            <div class="gm-hero-title">{{ $t('guano_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_guano_types') }}</div>
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
          <v-chip color="#388E3C" variant="tonal" size="small" class="font-weight-bold">
            <v-icon start size="14">mdi-leaf</v-icon>
            {{ filteredItems.length }} {{ $t('records') || 'enregistrements' }}
          </v-chip>
          <v-btn
            v-if="hasActiveFilters || search"
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
            color="#388E3C"
            prepend-icon="mdi-plus"
            rounded="lg"
            elevation="0"
            class="text-white font-weight-bold"
            @click="openDialog(null)"
          >
            {{ $t('add_guano_type') }}
          </v-btn>
        </div>
      </div>

      <v-data-table :headers="headers" :items="filteredItems" :search="search" :loading="loading ? '#388E3C' : false" :multi-sort="true" density="comfortable" class="rounded-xl param-table" hover>
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
          <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="columnFilters[col.key]" :items="getColumnOptions(col.key)" density="compact" variant="outlined" color="#388E3C" class="param-filter-input" style="color:#388E3C" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#388E3C" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
        </template>
        <template v-slot:[`item.image`]="{ item }">
          <v-avatar v-if="item.image" size="32" rounded="lg"><v-img :src="item.image" cover></v-img></v-avatar>
          <span v-else class="text-grey">-</span>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <div class="param-actions-cell">
            <v-tooltip :text="$t('edit')" location="top">
              <template #activator="{ props }">
                <v-btn v-bind="props" icon size="28" variant="tonal" color="#388E3C" class="param-action-btn" @click="openDialog(item)">
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

    <!-- Edit/Add Dialog -->
    <v-dialog v-model="dialog" max-width="600" persistent scrollable>
      <v-card class="rounded-xl overflow-hidden param-dialog-card" elevation="24">
        <div class="param-dialog-header">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="param-dialog-icon-badge" style="background: rgba(56, 142, 60, 0.15); color: #388E3C;">
                <v-icon size="24">{{ editing ? 'mdi-pencil-box-outline' : 'mdi-plus-circle-outline' }}</v-icon>
              </div>
              <div>
                <div class="param-dialog-title">{{ editing ? $t('edit_guano_type') : $t('add_guano_type') }}</div>
                <div class="param-dialog-subtitle">{{ $t('manage_guano_types') }}</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" size="small" class="param-dialog-close" @click="dialog = false" />
          </div>
        </div>
        <v-card-text class="pa-5 param-dialog-body" style="max-height: 75vh;">
          <v-row dense>
            <v-col cols="6"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#388E3C"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_en" :label="$t('name_en')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#388E3C"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_es" :label="$t('name_es')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded="lg" color="#388E3C"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_in_invoice" :label="$t('name_in_invoice')" prepend-inner-icon="mdi-receipt-text-outline" variant="outlined" density="compact" rounded="lg" color="#388E3C"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.scientific_name" :label="$t('scientific_name')" prepend-inner-icon="mdi-leaf" variant="outlined" density="compact" rounded="lg" color="#388E3C"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.image" :label="$t('image_url')" variant="outlined" density="compact" rounded="lg" color="#388E3C" prepend-inner-icon="mdi-image-outline"></v-text-field></v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded="lg" color="#388E3C" rows="2" auto-grow></v-textarea></v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="param-dialog-actions pa-4 border-t bg-surface">
          <v-btn variant="tonal" color="grey" rounded="lg" class="px-4" @click="dialog = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn color="#388E3C" variant="flat" rounded="lg" class="px-5 text-white font-weight-bold" @click="save" :loading="saving" prepend-icon="mdi-content-save">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="440">
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
            <div class="text-caption text-medium-emphasis" v-if="deleteTarget.scientific_name">{{ deleteTarget.scientific_name }}</div>
          </div>
        </div>
        <v-card-actions class="param-dialog-actions pa-4 border-t bg-surface">
          <v-btn variant="tonal" rounded="lg" color="grey" class="px-4" @click="deleteDialog = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="flat" rounded="lg" class="px-5 font-weight-bold" @click="executeDelete" prepend-icon="mdi-delete">{{ $t('delete') }}</v-btn>
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
      <v-progress-linear :model-value="snackbarProgress" color="#388E3C" height="3" class="mb-2" rounded></v-progress-linear>
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

export default {
  name: 'GuanoManagement',
  data() {
    return {
      search: '',
      items: [], loading: false,
      dialog: false, editing: null, saving: false,
      form: { name_fr: '', name_en: '', name_es: '', name_in_invoice: '', scientific_name: '', image: '', description: '' },
      columnFilters: {},
      deleteDialog: false, deleteTarget: null,
      summarySnackbar: { show: false, title: '', detail: '', color: 'success' },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    headers() {
      return [
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_en'), key: 'name_en', align: 'center' },
        { title: this.$t('name_es'), key: 'name_es', align: 'center' },
        { title: this.$t('name_in_invoice'), key: 'name_in_invoice', align: 'center' },
        { title: this.$t('image'), key: 'image', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' },
      ];
    },
    filteredItems() {
      return this.items.filter(item => Object.entries(this.columnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(String(item[key] ?? ''));
      }));
    },
    hasActiveFilters() { return Object.values(this.columnFilters).some(f => f && f.length > 0); },
  },
  mounted() {
    this.fetchItems();
    mitt.on('model-changed', this._onMC);
  },
  beforeUnmount() {
    mitt.off('model-changed', this._onMC);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  methods: {
    _onMC(d) {
      if (d.model === 'GuanoType') {
        this.fetchItems();
        if (d.action) {
          const actionText = d.action === 'created' ? this.$t('created') : d.action === 'updated' ? this.$t('updated') : this.$t('deleted');
          this.triggerSummarySnackbar(
            `${this.$t('guano_type')}: ${actionText}`,
            d.data?.name_fr || d.data?.name_en || '',
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
    getColumnOptions(key) { const set = new Set(); this.items.forEach(i => set.add(String(i[key] ?? ''))); return [...set].sort(); },
    async fetchItems() {
      this.loading = true;
      try { const r = await axios.get('/api/guano-types'); this.items = r.data.guano_types || []; } catch (e) { console.error(e); }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      this.form = item
        ? { name_fr: item.name_fr, name_en: item.name_en || '', name_es: item.name_es || '', name_in_invoice: item.name_in_invoice || '', scientific_name: item.scientific_name || '', image: item.image || '', description: item.description || '' }
        : { name_fr: '', name_en: '', name_es: '', name_in_invoice: '', scientific_name: '', image: '', description: '' };
      this.dialog = true;
    },
    async save() {
      this.saving = true;
      try {
        const displayName = this.form.name_fr || this.form.name_en || '';
        if (this.editing) {
          await axios.put(`/api/guano-types/${this.editing.id}`, this.form);
          this.triggerSummarySnackbar(this.$t('guano_type_updated'), displayName, 'success');
        } else {
          await axios.post('/api/guano-types', this.form);
          this.triggerSummarySnackbar(this.$t('guano_type_created'), displayName, 'success');
        }
        this.dialog = false;
        this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), e.response?.data?.message || 'Error', 'error');
      }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.deleteDialog = true; },
    async executeDelete() {
      this.deleteDialog = false;
      if (!this.deleteTarget) return;
      const targetName = this.deleteTarget.name_fr || this.deleteTarget.name_en || '';
      try {
        await axios.delete(`/api/guano-types/${this.deleteTarget.id}`);
        this.triggerSummarySnackbar(this.$t('guano_type_deleted'), targetName, 'success');
        this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), 'Delete failed', 'error');
      }
      this.deleteTarget = null;
    },
  },
};
</script>

<style scoped>
.gm-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.gm-hero { background: linear-gradient(135deg, #2E7D32 0%, #388E3C 40%, #66BB6A 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.gm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.gm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.gm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; cursor: pointer; }
.param-filter-row th { padding: 0 4px 6px !important; }
.param-filter-th { vertical-align: top; }
.param-filter-input { min-width: 80px; }
.param-filter-input :deep(.v-field) { font-size: 11px !important; min-height: 30px !important; border-radius: 8px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 26px !important; font-size: 11px; }
.param-filter-input :deep(.v-field__clearable) { font-size: 14px; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
</style>
