<template>
  <v-card class="bnm-card" elevation="0">
    <!-- Hero Header -->
    <div class="bnm-hero">
      <div class="bnm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="bnm-hero-icon">
            <v-icon size="28" color="white">mdi-bank</v-icon>
          </div>
          <div>
            <div class="bnm-hero-title">{{ $t('bank_name_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_bank_names_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <div class="d-flex align-center ga-3 mb-3">
        <v-text-field v-model="search" :label="$t('search')" variant="outlined" density="compact" rounded color="#E65100"
          prepend-inner-icon="mdi-magnify" hide-details clearable class="flex-grow-1" style="max-width:360px"></v-text-field>
        <v-spacer></v-spacer>
        <v-btn color="#E65100" prepend-icon="mdi-plus" rounded @click="openDialog(null)">{{ $t('add_bank_name') }}</v-btn>
      </div>

      <v-data-table :headers="headers" :items="filteredItems" :loading="loading ? '#E65100' : false"
        density="comfortable" class="rounded-lg param-table" hover :search="search">
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
        </template>
        <template v-slot:[`item.usage_count`]="{ item }">
          <v-chip size="x-small" color="#E65100" variant="tonal">{{ item.usage_count || 0 }}</v-chip>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="#E65100" @click="openDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Edit/Add Dialog -->
    <v-dialog v-model="dialog" max-width="450" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#E65100" class="mr-2">mdi-bank</v-icon>
          {{ editing ? $t('edit_bank_name') : $t('add_bank_name') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-text-field v-model="form.name" :label="$t('bank_name')" variant="outlined" density="compact" rounded
            color="#E65100" :rules="[v => !!v || $t('is_required', { type: $t('name') })]"
            @keydown.enter="save"></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="#E65100" variant="flat" rounded @click="save" :loading="saving" :disabled="!form.name">{{ $t('save') }}</v-btn>
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
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
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
import { useBankName } from '@/stores/BankName';

export default {
  name: 'BankNameManagement',
  data() {
    return {
      items: [], loading: false, search: '',
      dialog: false, editing: null, saving: false,
      form: { name: '' },
      deleteDialog: false, deleteTarget: null, deleting: false,
      snackbar: { show: false, message: '', color: 'success' },
    };
  },
  computed: {
    main() { return useMain(); },
    bankNameStore() { return useBankName(); },
    headers() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('created'), key: 'created_at', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    filteredItems() {
      return this.items;
    },
  },
  mounted() {
    this.fetchItems();
    mitt.on('model-changed', this._onMC);
  },
  beforeUnmount() { mitt.off('model-changed', this._onMC); },
  methods: {
    _onMC(d) { if (d.model === 'BankName') this.fetchItems(); },
    async fetchItems() {
      this.loading = true;
      try {
        const r = await axios.get('/api/bank-names');
        this.items = r.data.bank_names || [];
        // Also update the store so Settlement forms get live data
        this.bankNameStore.list = this.items;
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      this.form = item ? { name: item.name } : { name: '' };
      this.dialog = true;
    },
    async save() {
      if (!this.form.name) return;
      this.saving = true;
      try {
        if (this.editing) {
          await axios.put(`/api/bank-names/${this.editing.id}`, this.form);
          this.snackbar = { show: true, message: this.$t('bank_name_updated'), color: 'success' };
        } else {
          await axios.post('/api/bank-names', this.form);
          this.snackbar = { show: true, message: this.$t('bank_name_created'), color: 'success' };
        }
        this.dialog = false;
        this.fetchItems();
      } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || 'Error';
        this.snackbar = { show: true, message: msg, color: 'error' };
      }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.deleteDialog = true; },
    async executeDelete() {
      this.deleting = true;
      if (!this.deleteTarget) { this.deleteDialog = false; this.deleting = false; return; }
      try {
        await axios.delete(`/api/bank-names/${this.deleteTarget.id}`);
        this.snackbar = { show: true, message: this.$t('bank_name_deleted'), color: 'success' };
        this.fetchItems();
      } catch (e) { console.error(e); this.snackbar = { show: true, message: 'Error', color: 'error' }; }
      this.deleteTarget = null;
      this.deleteDialog = false;
      this.deleting = false;
    },
  },
};
</script>

<style scoped>
.bnm-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.bnm-hero { background: linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.bnm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.bnm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.bnm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; cursor: pointer; }
</style>
