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
        <v-text-field
          v-model="search"
          :label="$t('search')"
          variant="outlined"
          density="compact"
          rounded
          color="#E65100"
          prepend-inner-icon="mdi-magnify"
          hide-details
          clearable
          class="flex-grow-1"
          style="max-width:360px"
        ></v-text-field>
        <v-spacer></v-spacer>
        <v-btn
          v-if="hasActiveColumnFilters"
          variant="tonal"
          color="warning"
          rounded
          size="small"
          prepend-icon="mdi-filter-off"
          @click="clearAllColumnFilters"
        >
          {{ $t('clear_filters') }}
        </v-btn>
        <v-btn color="#E65100" prepend-icon="mdi-plus" rounded @click="openDialog(null)">
          {{ $t('add_bank_name') }}
        </v-btn>
      </div>

      <v-data-table
        :headers="headers"
        :items="filteredItems"
        :loading="loading ? '#E65100' : false"
        :multi-sort="true"
        density="comfortable"
        class="rounded-lg param-table"
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
          <v-chip size="x-small" color="#E65100" variant="tonal">{{ item.usage_count || 0 }}</v-chip>
        </template>

        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="#E65100" @click="openDialog(item)" class="mr-1">
            <v-icon size="16">mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)">
            <v-icon size="16">mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Edit/Add Dialog -->
    <v-dialog v-model="dialog" max-width="480" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#E65100" class="mr-2">mdi-bank</v-icon>
          <span class="font-weight-bold">{{ editing ? $t('edit_bank_name') : $t('add_bank_name') }}</span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider style="border-color: #E65100;"></v-divider>
        <v-card-text class="pa-5">
          <v-text-field
            v-model="form.name"
            :label="$t('bank_name')"
            variant="outlined"
            density="compact"
            rounded
            color="#E65100"
            prepend-inner-icon="mdi-bank"
            :rules="[v => !!v || $t('is_required', { type: $t('name') })]"
            @keydown.enter="save"
            hide-details="auto"
          ></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text" color="red-lighten-1">{{ $t('cancel') }}</v-btn>
          <v-btn
            color="#E65100"
            variant="flat"
            rounded
            @click="save"
            :loading="saving"
            :disabled="!form.name"
            prepend-icon="mdi-check"
          >
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">{{ $t('confirm_delete_message') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="deleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
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
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
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
</style>
