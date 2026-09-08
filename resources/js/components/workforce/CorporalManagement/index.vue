<template>
  <v-card class="cpm-card" elevation="0">
    <!-- Hero Header -->
    <div class="cpm-hero">
      <div class="cpm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="cpm-hero-icon">
            <v-icon size="28" color="white">mdi-account-tie</v-icon>
          </div>
          <div>
            <div class="cpm-hero-title">{{ $t('corporal_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_corporals_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <div class="d-flex align-center ga-3 mb-3">
        <v-spacer></v-spacer>
        <v-btn v-if="hasActiveColumnFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="clearAllColumnFilters">
          {{ $t('clear_filters') }}
        </v-btn>
        <v-btn v-if="selectedIds.length > 0" color="error" prepend-icon="mdi-delete-sweep" rounded @click="bulkDeleteDialog = true">
          {{ $t('delete') }} ({{ selectedIds.length }})
        </v-btn>
        <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openDialog(null)">
          {{ $t('add_corporal') }}
        </v-btn>
      </div>

      <v-data-table-virtual
        :headers="headers"
        :items="filteredItems"
        :loading="loading ? 'info' : false"
        density="comfortable"
        class="rounded-lg param-table"
        hover
        show-select
        v-model="selectedIds"
        item-value="id"
        :return-object="false"
        fixed-header
        height="650"
      >
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr>
            <th v-for="col in columns" :key="col.key" class="param-th">
              <template v-if="col.key === 'data-table-select'">
                <v-checkbox-btn
                  :model-value="selectAll"
                  :indeterminate="someSelected && !selectAll"
                  color="#42A5F5"
                  density="compact"
                  hide-details
                  @update:model-value="toggleSelectAll"
                ></v-checkbox-btn>
              </template>
              <div v-else class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">
                {{ col.title }}
                <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
              </div>
            </th>
          </tr>
          <tr class="param-filter-row">
            <th v-for="col in columns" :key="col.key + '-filter'" class="param-filter-th">
              <v-autocomplete
                v-if="col.key !== 'actions' && col.key !== 'data-table-select'"
                v-model="columnFilters[col.key]"
                :items="getColumnOptions(col.key)"
                density="compact"
                variant="outlined"
                color="info"
                class="param-filter-input text-info"
                hide-details clearable multiple chips closable-chips
                :placeholder="$t('filter')"
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="x-small" color="info" variant="tonal" closable>{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </th>
          </tr>
        </template>

        <template v-slot:[`item.sex`]="{ item }">
          <v-chip :color="item.sex === 'male' ? 'blue' : 'pink'" size="small" variant="tonal">
            <v-icon size="14" class="mr-1">{{ item.sex === 'male' ? 'mdi-gender-male' : 'mdi-gender-female' }}</v-icon>
            {{ $t(item.sex) }}
          </v-chip>
        </template>

        <template v-slot:[`item.is_quinzaine`]="{ item }">
          <v-icon :color="item.is_quinzaine ? 'success' : 'grey'" size="20">
            {{ item.is_quinzaine ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
          </v-icon>
        </template>

        <template v-slot:[`item.sections`]="{ item }">
          <div class="d-flex flex-wrap ga-1 justify-center">
            <v-chip v-for="s in (item.sections || [])" :key="s.id" size="x-small" color="info" variant="tonal">{{ s.name }}</v-chip>
            <span v-if="!item.sections || !item.sections.length" class="text-grey text-caption">-</span>
          </div>
        </template>

        <template v-slot:[`item.daily_price`]="{ item }">
          <v-chip size="small" color="success" variant="tonal">
            {{ main.formatDisplayNumber ? main.formatDisplayNumber(item.daily_price) : item.daily_price }} {{ main.currency || 'DH' }}
          </v-chip>
        </template>

        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="info" @click="openDialog(item)" class="mr-1">
            <v-icon size="16">mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)">
            <v-icon size="16">mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table-virtual>
    </v-card-text>

    <!-- ============= CREATE/EDIT DIALOG ============= -->
    <v-dialog v-model="dialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-account-tie</v-icon>
          {{ editing ? $t('edit_corporal') : $t('add_corporal') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="12">
              <v-text-field v-model="form.full_name" :label="$t('full_name')" variant="outlined" density="compact" rounded color="info" class="text-info" :rules="[v => !!v || $t('is_required', { type: $t('full_name') })]"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-select v-model="form.sex" :items="sexOptions" :label="$t('sex')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-select>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.cni" :label="$t('cni')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-text-field v-model.number="form.daily_price" :label="$t('daily_price')" variant="outlined" density="compact" rounded color="info" class="text-info" type="number" min="0" step="0.01" :suffix="main.currency || 'DH'"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-autocomplete
                v-model="form.section_ids"
                :items="sectionStore.list"
                item-value="id"
                item-title="name"
                :label="$t('sections')"
                multiple
                chips
                closable-chips
                density="compact"
                variant="outlined"
                rounded
                color="info"
                class="text-info"
                hide-details
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="small" color="info" variant="tonal" closable>{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </v-col>
            <v-col cols="12">
              <div class="cpm-toggle-card" :class="{ 'cpm-toggle-active': form.is_quinzaine }" @click="form.is_quinzaine = !form.is_quinzaine">
                <div class="cpm-toggle-left">
                  <div class="cpm-toggle-icon-wrap" :style="{ background: form.is_quinzaine ? '#4CAF501A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form.is_quinzaine ? 'success' : 'grey'" size="20">mdi-calendar-check</v-icon>
                  </div>
                  <div class="cpm-toggle-label">{{ $t('is_quinzaine') }}</div>
                </div>
                <v-switch v-model="form.is_quinzaine" color="success" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="save" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CONFIRM DELETE DIALOG ============= -->
    <v-dialog v-model="confirmDeleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_delete_corporal_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= BULK DELETE CONFIRMATION DIALOG ============= -->
    <v-dialog v-model="bulkDeleteDialog" max-width="450">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_bulk_delete_message', { count: selectedIds.length }) }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="bulkDeleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeBulkDelete" :loading="bulkDeleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useCorporal } from '@/stores/Corporal';
import { useSection } from '@/stores/Section';

export default {
  name: 'CorporalManagement',
  data() {
    return {
      loading: false,
      columnFilters: {},
      dialog: false,
      editing: null,
      form: this.emptyForm(),
      saving: false,
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleting: false,
      bulkDeleteDialog: false,
      bulkDeleting: false,
      selectedIds: [],
      sexOptions: [
        { title: this.$t('male'), value: 'male' },
        { title: this.$t('female'), value: 'female' },
      ],
    };
  },
  computed: {
    main() { return useMain(); },
    corporalStore() { return useCorporal(); },
    sectionStore() { return useSection(); },
    items() { return this.corporalStore.list; },
    headers() {
      return [
        { title: this.$t('full_name'), key: 'full_name', align: 'start' },
        { title: this.$t('sex'), key: 'sex', align: 'center' },
        { title: this.$t('cni'), key: 'cni', align: 'center' },
        { title: this.$t('daily_price'), key: 'daily_price', align: 'center' },
        { title: this.$t('is_quinzaine'), key: 'is_quinzaine', align: 'center' },
        { title: this.$t('sections'), key: 'sections', align: 'center', sortable: false },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
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
    selectAll() {
      return this.selectedIds.length === this.filteredItems.length && this.filteredItems.length > 0;
    },
    someSelected() {
      return this.selectedIds.length > 0;
    },
  },
  mounted() { this.fetchData(); },
  methods: {
    emptyForm() {
      return { full_name: '', sex: 'male', cni: '', is_quinzaine: false, daily_price: 0, section_ids: [] };
    },
    getCellDisplayValue(key, item) {
      if (key === 'is_quinzaine') return item[key] ? this.$t('yes') : this.$t('no');
      if (key === 'sex') return this.$t(item[key]);
      if (key === 'sections') return (item.sections || []).map(s => s.name).join(', ') || '-';
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    clearAllColumnFilters() { this.columnFilters = {}; },
    toggleSelectAll(v) {
      this.selectedIds = v ? this.filteredItems.map(i => i.id) : [];
    },
    async fetchData() {
      this.loading = true;
      await Promise.all([this.corporalStore.fetch(), this.sectionStore.fetch()]);
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = {
          full_name: item.full_name || '', sex: item.sex || 'male', cni: item.cni || '',
          is_quinzaine: !!item.is_quinzaine,
          daily_price: parseFloat(item.daily_price) || 0,
          section_ids: (item.sections || []).map(s => s.id),
        };
      } else { this.form = this.emptyForm(); }
      this.dialog = true;
    },
    async save() {
      if (!this.form.full_name) return;
      this.saving = true;
      try {
        if (this.editing) {
          await this.corporalStore.update(this.editing.id, this.form);
        } else {
          await this.corporalStore.create(this.form);
        }
        this.dialog = false;
        await this.corporalStore.fetch();
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('corporal_save_error'), color: 'error' });
      }
      this.saving = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.confirmDeleteDialog = true; },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      try {
        await this.corporalStore.remove(this.deleteTarget.id);
        this.confirmDeleteDialog = false;
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('corporal_delete_error'), color: 'error' });
      }
      this.deleting = false;
      this.deleteTarget = null;
    },
    async executeBulkDelete() {
      if (!this.selectedIds.length) return;
      this.bulkDeleting = true;
      const count = this.selectedIds.length;
      try {
        await this.corporalStore.bulkRemove(this.selectedIds);
        this.bulkDeleteDialog = false;
        this.selectedIds = [];
        this.main.add_snackbar({ show: true, message: this.$t('bulk_deleted_success', { count, model: this.$t('corporals') }), color: 'success' });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('bulk_delete_error'), color: 'error' });
      }
      this.bulkDeleting = false;
    },
  },
};
</script>

<style scoped>
.cpm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.cpm-hero { background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.cpm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.cpm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.cpm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.cpm-toggle-card { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 12px; border: 1.5px solid rgba(var(--v-border-color), .12); cursor: pointer; transition: all .2s ease; }
.cpm-toggle-card:hover { border-color: rgba(var(--v-border-color), .25); }
.cpm-toggle-active { border-color: #4CAF50 !important; background: rgba(76,175,80,.04); }
.cpm-toggle-left { display: flex; align-items: center; gap: 10px; }
.cpm-toggle-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.cpm-toggle-label { font-size: 13px; font-weight: 600; }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; }
.param-filter-row th { padding: 0 4px 6px !important; }
.param-filter-th { vertical-align: top; }
.param-filter-input { min-width: 80px; }
.param-filter-input :deep(.v-field) { font-size: 11px !important; min-height: 30px !important; border-radius: 8px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 26px !important; font-size: 11px; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
</style>
