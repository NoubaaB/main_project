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
          <v-chip color="#E65100" variant="tonal" size="small" class="font-weight-bold">
            <v-icon start size="14">mdi-bank</v-icon>
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
            color="#E65100"
            prepend-icon="mdi-plus"
            rounded="lg"
            elevation="0"
            class="text-white font-weight-bold"
            @click="openDialog(null)"
          >
            {{ $t('add_bank_name') }}
          </v-btn>
        </div>
      </div>

      <v-data-table
        :headers="headers"
        :items="filteredItems"
        :loading="loading ? '#E65100' : false"
        :multi-sort="true"
        density="comfortable"
        class="rounded-xl param-table"
        hover
        :search="search"
      >
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr>
            <th v-for="col in columns" :key="col.key" class="param-th">
              <div
                class="param-th-label"
                @click="col.sortable !== false ? toggleSort(col) : null"
                :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
              >
                {{ col.title }}
                <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
              </div>
            </th>
          </tr>
          <tr class="param-filter-row">
            <th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th">
              <v-autocomplete
                v-if="col.key !== 'actions'"
                v-model="columnFilters[col.key]"
                :items="getColumnOptions(col.key)"
                density="compact"
                variant="outlined"
                color="#E65100"
                class="param-filter-input"
                hide-details
                clearable
                multiple
                chips
                closable-chips
                :placeholder="$t('filter')"
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="x-small" color="#E65100" variant="tonal" closable>
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </th>
          </tr>
        </template>

        <template v-slot:[`item.name`]="{ item }">
          <div class="d-flex align-center ga-2">
            <v-icon size="18" color="#E65100">mdi-bank-outline</v-icon>
            <span class="font-weight-medium">{{ item.name }}</span>
          </div>
        </template>

        <template v-slot:[`item.created_at`]="{ item }">
          <v-chip size="x-small" variant="outlined" color="#E65100">
            <v-icon start size="12">mdi-calendar</v-icon>
            {{ formatDate(item.created_at) }}
          </v-chip>
        </template>

        <template v-slot:[`item.usage_count`]="{ item }">
          <v-chip size="x-small" color="#E65100" variant="tonal" class="font-weight-bold fnm-currency-num">{{ item.usage_count || 0 }}</v-chip>
        </template>

        <template v-slot:[`item.actions`]="{ item }">
          <div class="param-actions-cell">
            <v-tooltip :text="$t('edit')" location="top">
              <template #activator="{ props }">
                <v-btn v-bind="props" icon size="28" variant="tonal" color="#E65100" class="param-action-btn" @click="openDialog(item)">
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
    <v-dialog v-model="dialog" max-width="500" persistent scrollable>
      <v-card class="rounded-xl overflow-hidden param-dialog-card" elevation="24">
        <div class="param-dialog-header">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="param-dialog-icon-badge" style="background: rgba(230, 81, 0, 0.15); color: #E65100;">
                <v-icon size="24">{{ editing ? 'mdi-pencil-box-outline' : 'mdi-plus-circle-outline' }}</v-icon>
              </div>
              <div>
                <div class="param-dialog-title">{{ editing ? $t('edit_bank_name') : $t('add_bank_name') }}</div>
                <div class="param-dialog-subtitle">{{ $t('manage_bank_names_desc') }}</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" size="small" class="param-dialog-close" @click="dialog = false" />
          </div>
        </div>
        <v-card-text class="pa-5 param-dialog-body" style="max-height: 75vh;">
          <v-text-field
            v-model="form.name"
            :label="$t('bank_name')"
            variant="outlined"
            density="compact"
            rounded="lg"
            color="#E65100"
            prepend-inner-icon="mdi-bank"
            :rules="[v => !!v || $t('is_required', { type: $t('name') })]"
            @keydown.enter="save"
            hide-details="auto"
          ></v-text-field>
        </v-card-text>
        <v-card-actions class="param-dialog-actions pa-4 border-t bg-surface">
          <v-btn variant="tonal" color="grey" rounded="lg" class="px-4" @click="dialog = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn
            color="#E65100"
            variant="flat"
            rounded="lg"
            class="px-5 text-white font-weight-bold"
            @click="save"
            :loading="saving"
            :disabled="!form.name"
            prepend-icon="mdi-content-save"
          >
            {{ $t('save') }}
          </v-btn>
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
            <div class="font-weight-bold">{{ deleteTarget.name }}</div>
          </div>
        </div>
        <v-card-actions class="param-dialog-actions pa-4 border-t bg-surface">
          <v-btn variant="tonal" rounded="lg" color="grey" class="px-4" @click="deleteDialog = false">{{ $t('cancel') }}</v-btn>
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
      <v-progress-linear :model-value="snackbarProgress" color="#E65100" height="3" class="mb-2" rounded></v-progress-linear>
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
import { useBankName } from '@/stores/BankName';

export default {
  name: 'BankNameManagement',
  data() {
    return {
      items: [],
      loading: false,
      search: '',
      dialog: false,
      editing: null,
      saving: false,
      form: { name: '' },
      deleteDialog: false,
      deleteTarget: null,
      deleting: false,
      columnFilters: {},
      summarySnackbar: { show: false, title: '', detail: '', color: 'success' },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    bankNameStore() { return useBankName(); },
    headers() {
      return [
        { title: this.$t('name'), key: 'name', align: 'start' },
        { title: this.$t('created'), key: 'created_at', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' },
      ];
    },
    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(v => Array.isArray(v) ? v.length > 0 : !!v);
    },
    filteredItems() {
      if (!this.hasActiveColumnFilters) return this.items;
      return this.items.filter(item => {
        for (const [key, filterValues] of Object.entries(this.columnFilters)) {
          if (!filterValues || (Array.isArray(filterValues) && filterValues.length === 0)) continue;
          const val = key === 'created_at' ? this.formatDate(item[key]) : String(item[key] || '');
          if (Array.isArray(filterValues)) {
            if (!filterValues.includes(val)) return false;
          } else if (typeof filterValues === 'string') {
            if (!val.toLowerCase().includes(filterValues.toLowerCase())) return false;
          }
        }
        return true;
      });
    },
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
      if (d.model === 'BankName') this.fetchItems();
    },
    formatDate(d) {
      if (!d) return '-';
      try {
        const dt = new Date(d);
        return isNaN(dt.getTime()) ? d : dt.toLocaleDateString();
      } catch (e) {
        return d;
      }
    },
    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => {
        const v = key === 'created_at' ? this.formatDate(item[key]) : item[key];
        if (v !== undefined && v !== null && v !== '') set.add(String(v));
      });
      return Array.from(set).sort();
    },
    clearAllColumnFilters() {
      this.columnFilters = {};
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
        const r = await axios.get('/api/bank-names');
        this.items = r.data.bank_names || [];
        this.bankNameStore.list = this.items;
      } catch (e) {
        console.error(e);
      }
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
          this.triggerSummarySnackbar(this.$t('bank_name_updated'), this.form.name, 'success');
        } else {
          await axios.post('/api/bank-names', this.form);
          this.triggerSummarySnackbar(this.$t('bank_name_created'), this.form.name, 'success');
        }
        this.dialog = false;
        this.fetchItems();
      } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || 'Error';
        this.triggerSummarySnackbar(this.$t('error_occurred'), msg, 'error');
      }
      this.saving = false;
    },
    confirmDelete(item) {
      this.deleteTarget = item;
      this.deleteDialog = true;
    },
    async executeDelete() {
      this.deleting = true;
      if (!this.deleteTarget) {
        this.deleteDialog = false;
        this.deleting = false;
        return;
      }
      const targetName = this.deleteTarget.name;
      try {
        await axios.delete(`/api/bank-names/${this.deleteTarget.id}`);
        this.triggerSummarySnackbar(this.$t('bank_name_deleted'), targetName, 'success');
        this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), 'Delete failed', 'error');
      }
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
.param-table { border: 1px solid rgba(var(--v-border-color), .12); }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; cursor: pointer; }
.param-filter-row th { padding: 4px 6px 8px !important; }
.param-filter-input :deep(.v-field) { border-radius: 8px !important; font-size: 11px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 28px !important; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
</style>
