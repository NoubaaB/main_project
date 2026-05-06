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
      <div class="d-flex align-center ga-3 mb-3">
        <v-spacer></v-spacer>
        <v-btn v-if="hasActiveFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="columnFilters = {}">{{ $t('clear_filters') }}</v-btn>
        <v-btn color="#388E3C" prepend-icon="mdi-plus" rounded @click="openDialog(null)">{{ $t('add_guano_type') }}</v-btn>
      </div>

      <v-data-table :headers="headers" :items="filteredItems" :loading="loading ? '#388E3C' : false" density="comfortable" class="rounded-lg param-table" hover>
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
          <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="columnFilters[col.key]" :items="getColumnOptions(col.key)" density="compact" variant="outlined" color="#388E3C" class="param-filter-input" style="color:#388E3C" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#388E3C" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
        </template>
        <template v-slot:[`item.image`]="{ item }">
          <v-avatar v-if="item.image" size="32" rounded="lg"><v-img :src="item.image" cover></v-img></v-avatar>
          <span v-else class="text-grey">-</span>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="#388E3C" @click="openDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Edit/Add Dialog -->
    <v-dialog v-model="dialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#388E3C" class="mr-2">mdi-leaf</v-icon>
          {{ editing ? $t('edit_guano_type') : $t('add_guano_type') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="6"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" variant="outlined" density="compact" rounded color="#388E3C" class="mb-3"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_en" :label="$t('name_en')" variant="outlined" density="compact" rounded color="#388E3C" class="mb-3"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_es" :label="$t('name_es')" variant="outlined" density="compact" rounded color="#388E3C"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_in_invoice" :label="$t('name_in_invoice')" variant="outlined" density="compact" rounded color="#388E3C"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.scientific_name" :label="$t('scientific_name')" variant="outlined" density="compact" rounded color="#388E3C"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.image" :label="$t('image_url')" variant="outlined" density="compact" rounded color="#388E3C" prepend-inner-icon="mdi-image-outline"></v-text-field></v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" variant="outlined" density="compact" rounded color="#388E3C" rows="2"></v-textarea></v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="#388E3C" variant="flat" rounded @click="save" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">{{ $t('confirm_delete_message') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="deleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="snackbar.show" :timeout="4000" location="top right" :color="snackbar.color" rounded="lg">
      {{ snackbar.message }}
      <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false"></v-btn></template>
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
      items: [], loading: false,
      dialog: false, editing: null, saving: false,
      form: { name_fr: '', name_en: '', name_es: '', name_in_invoice: '', scientific_name: '', image: '', description: '' },
      columnFilters: {},
      deleteDialog: false, deleteTarget: null,
      snackbar: { show: false, message: '', color: 'success' },
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
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
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
  beforeUnmount() { mitt.off('model-changed', this._onMC); },
  methods: {
    _onMC(d) { if (d.model === 'GuanoType') this.fetchItems(); },
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
        if (this.editing) {
          await axios.put(`/api/guano-types/${this.editing.id}`, this.form);
          this.snackbar = { show: true, message: this.$t('guano_type_updated'), color: 'success' };
        } else {
          await axios.post('/api/guano-types', this.form);
          this.snackbar = { show: true, message: this.$t('guano_type_created'), color: 'success' };
        }
        this.dialog = false;
        this.fetchItems();
      } catch (e) { console.error(e); this.snackbar = { show: true, message: e.response?.data?.message || 'Error', color: 'error' }; }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.deleteDialog = true; },
    async executeDelete() {
      this.deleteDialog = false;
      if (!this.deleteTarget) return;
      try {
        await axios.delete(`/api/guano-types/${this.deleteTarget.id}`);
        this.snackbar = { show: true, message: this.$t('guano_type_deleted'), color: 'success' };
        this.fetchItems();
      } catch (e) { console.error(e); }
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
