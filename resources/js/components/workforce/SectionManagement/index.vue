<template>
  <div class="scm-page-wrap">
    <v-card class="scm-card border" elevation="0">
      <!-- ================= HERO HEADER ================= -->
      <div class="scm-hero">
        <div class="scm-hero-inner">
          <div class="d-flex flex-wrap align-center justify-space-between ga-4">
            <div class="d-flex align-center ga-3">
              <div class="scm-hero-icon">
                <v-icon size="28" color="white">mdi-sitemap-outline</v-icon>
              </div>
              <div>
                <div class="scm-hero-title">{{ $t('section_management') }}</div>
                <div class="text-white text-caption" style="opacity: .85;">{{ $t('manage_sections_desc') }}</div>
              </div>
            </div>

            <!-- Hero KPI Chips -->
            <div class="d-flex flex-wrap align-center ga-2">
              <div class="scm-kpi-chip">
                <v-icon size="16" color="#90CAF9" class="mr-1">mdi-sitemap</v-icon>
                <span class="scm-kpi-num">{{ items.length }}</span>
                <span class="scm-kpi-lbl">{{ $t('total') }}</span>
              </div>
              <div class="scm-kpi-chip">
                <v-icon size="16" color="#A5D6A7" class="mr-1">mdi-pencil-lock-outline</v-icon>
                <span class="scm-kpi-num">{{ modifiableCount }}</span>
                <span class="scm-kpi-lbl">{{ $t('modifiable') || 'Modifiable' }}</span>
              </div>
              <div class="scm-kpi-chip">
                <v-icon size="16" color="#FFE082" class="mr-1">mdi-account-tie</v-icon>
                <span class="scm-kpi-num">{{ totalCorporalsLinked }}</span>
                <span class="scm-kpi-lbl">{{ $t('corporals') }}</span>
              </div>
              <div class="scm-kpi-chip">
                <v-icon size="16" color="#80DEEA" class="mr-1">mdi-cash-multiple</v-icon>
                <span class="scm-kpi-num">{{ avgPrice }}</span>
                <span class="scm-kpi-lbl">{{ main.currency || 'DH' }} avg</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= TOOLBAR & ACTIONS ================= -->
      <v-card-text class="pa-4 scm-card-body">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-3">
          <!-- Left: Search & Quick Filter Pills -->
          <div class="d-flex flex-wrap align-center ga-2 flex-grow-1" style="max-width: 680px;">
            <v-text-field
              v-model="quickSearch"
              :placeholder="$t('search_sections') || 'Search sections by name, corporals...'"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              rounded
              color="#42A5F5"
              class="scm-search-input text-workforce"
              hide-details
              clearable
              style="min-width: 260px;"
            ></v-text-field>

            <v-btn-toggle
              v-model="quickFilter"
              density="compact"
              rounded
              mandatory
              variant="outlined"
              color="#42A5F5"
              class="scm-filter-toggle"
            >
              <v-btn value="all" size="small">{{ $t('all') }} ({{ items.length }})</v-btn>
              <v-btn value="modifiable" size="small">{{ $t('is_price_modifiable') }} ({{ modifiableCount }})</v-btn>
              <v-btn value="fixed" size="small">{{ $t('fixed_pay') }} ({{ items.length - modifiableCount }})</v-btn>
            </v-btn-toggle>
          </div>

          <!-- Right: Actions -->
          <div class="d-flex align-center ga-2">
            <v-btn
              v-if="hasActiveColumnFilters || quickSearch"
              variant="tonal"
              color="warning"
              rounded
              size="small"
              prepend-icon="mdi-filter-off"
              @click="clearAllFilters"
            >
              {{ $t('clear_filters') }}
            </v-btn>
            <v-btn
              v-if="selectedIds.length > 0"
              color="error"
              prepend-icon="mdi-delete-sweep"
              rounded
              size="small"
              variant="flat"
              @click="bulkDeleteDialog = true"
            >
              {{ $t('delete') }} ({{ selectedIds.length }})
            </v-btn>
            <v-btn
              color="#42A5F5"
              prepend-icon="mdi-plus"
              rounded
              size="small"
              variant="flat"
              class="text-white font-weight-bold shadow-sm"
              @click="openDialog(null)"
            >
              {{ $t('add_section') }}
            </v-btn>
          </div>
        </div>

        <!-- ================= RESPONSIVE DATA TABLE ================= -->
        <div class="scm-table-wrap">
          <v-data-table-virtual
            :headers="headers"
            :items="filteredItems"
            :loading="loading ? '#42A5F5' : false"
            density="compact"
            class="rounded-xl scm-table border"
            hover
            show-select
            v-model="selectedIds"
            item-value="id"
            :return-object="false"
            fixed-header
            :height="tableHeight"
          >
            <!-- Custom headers with integrated column filters -->
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr>
                <th v-for="col in columns" :key="col.key" class="scm-th">
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
                  <div
                    v-else
                    class="scm-th-label"
                    @click="col.sortable !== false ? toggleSort(col) : null"
                    :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
                  >
                    {{ col.title }}
                    <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
                  </div>
                </th>
              </tr>
              <tr class="scm-filter-row">
                <th v-for="col in columns" :key="col.key + '-filter'" class="scm-filter-th">
                  <v-autocomplete
                    v-if="col.key !== 'actions' && col.key !== 'data-table-select'"
                    v-model="columnFilters[col.key]"
                    :items="getColumnOptions(col.key)"
                    density="compact"
                    variant="outlined"
                    rounded
                    color="#42A5F5"
                    class="scm-col-filter text-workforce"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    :placeholder="$t('filter')"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" size="x-small" color="#42A5F5" variant="tonal" closable>{{ item.title }}</v-chip>
                    </template>
                  </v-autocomplete>
                </th>
              </tr>
            </template>

            <!-- Section Name with Icon -->
            <template v-slot:[`item.name`]="{ item }">
              <div class="d-flex align-center ga-2 py-1">
                <div class="scm-avatar-circle">
                  <v-icon size="16" color="#1565C0">mdi-sitemap</v-icon>
                </div>
                <div>
                  <div class="font-weight-bold text-body-2 text-truncate" style="max-width: 240px;">
                    {{ item.name }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ (item.corporals || []).length }} {{ $t('corporals') }}
                  </div>
                </div>
              </div>
            </template>

            <!-- Daily Price -->
            <template v-slot:[`item.daily_price`]="{ item }">
              <v-chip size="small" color="success" variant="tonal" class="font-weight-bold">
                {{ formatNumber(item.daily_price) }} {{ main.currency || 'DH' }}
              </v-chip>
            </template>

            <!-- Price Modifiable -->
            <template v-slot:[`item.is_price_modifiable`]="{ item }">
              <v-chip
                size="x-small"
                :color="item.is_price_modifiable ? 'success' : 'grey'"
                :variant="item.is_price_modifiable ? 'flat' : 'outlined'"
                class="font-weight-medium"
              >
                <v-icon size="12" class="mr-1">
                  {{ item.is_price_modifiable ? 'mdi-pencil-outline' : 'mdi-lock-outline' }}
                </v-icon>
                {{ item.is_price_modifiable ? $t('modifiable') || 'Modifiable' : $t('fixed') || 'Fixed' }}
              </v-chip>
            </template>

            <!-- Associated Corporals -->
            <template v-slot:[`item.corporals`]="{ item }">
              <div class="d-flex flex-wrap ga-1 justify-center py-1">
                <v-chip
                  v-for="c in (item.corporals || [])"
                  :key="c.id"
                  size="x-small"
                  color="#1565C0"
                  variant="tonal"
                  class="font-weight-medium"
                >
                  <v-icon size="11" class="mr-1">mdi-account-tie</v-icon>
                  {{ c.full_name }}
                </v-chip>
                <span v-if="!item.corporals || !item.corporals.length" class="text-grey text-caption">-</span>
              </div>
            </template>

            <!-- Actions -->
            <template v-slot:[`item.actions`]="{ item }">
              <div class="d-flex align-center justify-center ga-1">
                <v-btn icon size="x-small" variant="tonal" color="#42A5F5" @click="openDialog(item)" :title="$t('edit')">
                  <v-icon size="15">mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)" :title="$t('delete')">
                  <v-icon size="15">mdi-delete</v-icon>
                </v-btn>
              </div>
            </template>
          </v-data-table-virtual>
        </div>
      </v-card-text>
    </v-card>

    <!-- ============= CREATE / EDIT SECTION DIALOG ============= -->
    <v-dialog v-model="dialog" max-width="600" persistent scrollable>
      <v-card class="rounded-2xl overflow-hidden elevation-12">
        <!-- Dialog Header Banner -->
        <div class="scm-dialog-hero">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="scm-dialog-icon">
                <v-icon size="24" color="white">{{ editing ? 'mdi-sitemap-outline' : 'mdi-plus-box-outline' }}</v-icon>
              </div>
              <div>
                <div class="text-subtitle-1 font-weight-bold text-white">
                  {{ editing ? $t('edit_section') : $t('add_section') }}
                </div>
                <div class="text-caption text-white" style="opacity: .8;">
                  {{ editing ? form.name : $t('manage_sections_desc') }}
                </div>
              </div>
            </div>
            <v-btn icon size="small" variant="text" color="white" @click="dialog = false">
              <v-icon size="20">mdi-close</v-icon>
            </v-btn>
          </div>
        </div>

        <v-card-text class="pa-5" style="max-height: 72vh;">
          <!-- Live Preview Card -->
          <div class="scm-preview-card mb-4 pa-3 rounded-xl border">
            <div class="d-flex align-center ga-3">
              <div class="scm-avatar-circle" style="width: 44px; height: 44px;">
                <v-icon size="22" color="#1565C0">mdi-sitemap</v-icon>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-center ga-2">
                  <span class="font-weight-bold text-body-1 text-truncate">
                    {{ form.name || $t('section_name') }}
                  </span>
                </div>
                <div class="d-flex flex-wrap align-center ga-2 mt-1">
                  <v-chip size="x-small" color="success" variant="flat" class="text-white font-weight-bold">
                    {{ formatNumber(form.daily_price) }} {{ main.currency || 'DH' }}/day
                  </v-chip>
                  <v-chip
                    size="x-small"
                    :color="form.is_price_modifiable ? 'success' : 'grey'"
                    variant="tonal"
                  >
                    {{ form.is_price_modifiable ? $t('modifiable') || 'Modifiable' : $t('fixed') || 'Fixed' }}
                  </v-chip>
                  <span class="text-caption text-medium-emphasis">
                    {{ (form.corporal_ids || []).length }} {{ $t('corporals') }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Fields -->
          <v-row dense>
            <v-col cols="12">
              <v-text-field
                v-model="form.name"
                :label="$t('section_name')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-sitemap-outline"
                :rules="[v => !!v || $t('is_required', { type: $t('section_name') })]"
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model.number="form.daily_price"
                :label="$t('daily_price')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                type="number"
                min="0"
                step="0.01"
                prepend-inner-icon="mdi-cash"
                :prefix="'Dh'"
                :suffix="main.currency || 'DH'"
                :rules="[v => v !== null && v !== undefined && v >= 0 || $t('is_required', { type: $t('daily_price') })]"
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <!-- Corporals Multi-Autocomplete -->
            <v-col cols="12">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption font-weight-bold text-medium-emphasis">
                  <v-icon size="14" color="#42A5F5" class="mr-1">mdi-account-tie</v-icon>
                  {{ $t('assigned_corporals') || 'Assigned Corporals' }}
                </span>
                <v-chip size="x-small" color="#42A5F5" variant="tonal">
                  {{ (form.corporal_ids || []).length }} {{ $t('selected') }}
                </v-chip>
              </div>
              <v-autocomplete
                v-model="form.corporal_ids"
                :items="corporalStore.list"
                item-value="id"
                item-title="full_name"
                :label="$t('corporals')"
                multiple
                chips
                closable-chips
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-account-tie"
                clearable
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="small" color="#1565C0" variant="tonal" closable>
                    {{ item.title }}
                  </v-chip>
                </template>
                <template #item="{ item, props }">
                  <v-list-item v-bind="props" class="rounded-lg my-1">
                    <template #prepend>
                      <v-avatar size="26" color="blue-lighten-4" class="text-blue-darken-3 text-caption font-weight-bold mr-2">
                        {{ getInitials(item.raw.full_name) }}
                      </v-avatar>
                      <v-checkbox-btn
                        :model-value="(form.corporal_ids || []).includes(item.value)"
                        color="#42A5F5"
                        density="compact"
                        class="mr-1"
                      ></v-checkbox-btn>
                    </template>
                    <v-list-item-title class="font-weight-medium">{{ item.raw.full_name }}</v-list-item-title>
                    <v-list-item-subtitle v-if="item.raw.cni" class="text-caption text-medium-emphasis">
                      {{ item.raw.cni }}
                    </v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>

            <!-- Price Modifiable Switch Card -->
            <v-col cols="12">
              <div
                class="scm-toggle-card"
                :class="{ 'scm-toggle-active-blue': form.is_price_modifiable }"
                @click="form.is_price_modifiable = !form.is_price_modifiable"
              >
                <div class="scm-toggle-left">
                  <div class="scm-toggle-icon-wrap" :style="{ background: form.is_price_modifiable ? '#1565C01A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form.is_price_modifiable ? '#1565C0' : 'grey'" size="20">mdi-pencil-lock-outline</v-icon>
                  </div>
                  <div>
                    <div class="scm-toggle-label">{{ $t('is_price_modifiable') }}</div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
                      {{ $t('is_price_modifiable_desc') || 'Allow adjusting daily rate during activity logging' }}
                    </div>
                  </div>
                </div>
                <v-switch v-model="form.is_price_modifiable" color="#42A5F5" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn
            color="#42A5F5"
            variant="flat"
            rounded
            class="text-white px-5 font-weight-bold"
            @click="save"
            :loading="saving"
          >
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CONFIRM DELETE DIALOG ============= -->
    <v-dialog v-model="confirmDeleteDialog" max-width="420">
      <v-card class="rounded-2xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_delete_section_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= BULK DELETE CONFIRMATION DIALOG ============= -->
    <v-dialog v-model="bulkDeleteDialog" max-width="450">
      <v-card class="rounded-2xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
          {{ $t('bulk_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_bulk_delete_message', { count: selectedIds.length }) }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="bulkDeleteDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeBulkDelete" :loading="bulkDeleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= SUMMARY SNACKBAR ============= -->
    <transition name="scm-snackbar-anim">
      <div v-if="snackbar.show" class="scm-summary-snackbar">
        <div class="scm-snackbar-card">
          <div class="scm-snackbar-accent" style="background: #42A5F5;"></div>
          <div class="scm-snackbar-body">
            <div class="scm-snackbar-header">
              <v-icon :color="snackbar.color" size="20" class="mr-2">{{ snackbar.icon }}</v-icon>
              <span class="scm-snackbar-title">{{ snackbar.title }}</span>
              <v-btn icon size="x-small" variant="text" class="ml-auto scm-snackbar-close" @click="snackbar.show = false">
                <v-icon size="16">mdi-close</v-icon>
              </v-btn>
            </div>
            <div class="scm-snackbar-grid">
              <div v-for="(f, i) in snackbar.fields" :key="i" class="scm-snackbar-field">
                <span class="scm-field-label">{{ f.label }}</span>
                <span class="scm-field-val">
                  <v-icon v-if="f.icon" size="14" class="mr-1" style="opacity:.7">{{ f.icon }}</v-icon>
                  {{ f.value }}
                </span>
              </div>
            </div>
            <div class="scm-snackbar-progress">
              <div class="scm-progress-bar" style="background: #42A5F5;" :style="{ width: snackbarProgress + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useSection } from '@/stores/Section';
import { useCorporal } from '@/stores/Corporal';

export default {
  name: 'SectionManagement',
  data() {
    return {
      windowHeight: typeof window !== 'undefined' ? window.innerHeight : 800,
      loading: false,
      quickSearch: '',
      quickFilter: 'all',
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
      snackbar: {
        show: false,
        title: '',
        color: 'success',
        icon: 'mdi-check-circle',
        fields: [],
      },
      snackbarProgress: 100,
      snackbarTimer: null,
      snackbarInterval: null,
    };
  },
  computed: {
    main() { return useMain(); },
    sectionStore() { return useSection(); },
    corporalStore() { return useCorporal(); },
    items() { return this.sectionStore.list || []; },

    tableHeight() {
      return Math.max(340, this.windowHeight - 275);
    },

    modifiableCount() {
      return this.items.filter(s => s.is_price_modifiable).length;
    },
    totalCorporalsLinked() {
      return this.items.reduce((acc, s) => acc + (s.corporals ? s.corporals.length : 0), 0);
    },
    avgPrice() {
      if (!this.items.length) return '0.00';
      const sum = this.items.reduce((acc, s) => acc + (parseFloat(s.daily_price) || 0), 0);
      return this.formatNumber(sum / this.items.length);
    },

    headers() {
      return [
        { title: this.$t('section_name'), key: 'name', align: 'start', minWidth: '220px' },
        { title: this.$t('daily_price'), key: 'daily_price', align: 'center', minWidth: '130px' },
        { title: this.$t('is_price_modifiable'), key: 'is_price_modifiable', align: 'center', minWidth: '140px' },
        { title: this.$t('corporals'), key: 'corporals', align: 'center', minWidth: '220px' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, minWidth: '90px' },
      ];
    },

    filteredItems() {
      let result = this.items;

      if (this.quickFilter === 'modifiable') {
        result = result.filter(s => s.is_price_modifiable);
      } else if (this.quickFilter === 'fixed') {
        result = result.filter(s => !s.is_price_modifiable);
      }

      if (this.quickSearch && this.quickSearch.trim()) {
        const q = this.quickSearch.toLowerCase().trim();
        result = result.filter(item => {
          const matchCorporals = (item.corporals || []).some(c => c.full_name && c.full_name.toLowerCase().includes(q));
          return (
            (item.name && item.name.toLowerCase().includes(q)) ||
            matchCorporals
          );
        });
      }

      return result.filter(item => {
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

  mounted() {
    this.fetchData();
    window.addEventListener('resize', this.onResize);
  },
  unmounted() {
    window.removeEventListener('resize', this.onResize);
    this.clearSnackbarTimers();
  },

  methods: {
    onResize() {
      this.windowHeight = window.innerHeight;
    },
    formatNumber(val) {
      if (!val && val !== 0) return '0.00';
      return parseFloat(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    getInitials(name) {
      if (!name) return 'CP';
      const parts = name.trim().split(/\s+/);
      if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
      return name.slice(0, 2).toUpperCase();
    },
    emptyForm() {
      return {
        name: '',
        daily_price: 0,
        is_price_modifiable: false,
        corporal_ids: [],
      };
    },
    getCellDisplayValue(key, item) {
      if (key === 'is_price_modifiable') return item.is_price_modifiable ? this.$t('yes') : this.$t('no');
      if (key === 'daily_price') return this.formatNumber(item[key]);
      if (key === 'corporals') return (item.corporals || []).map(c => c.full_name).join(', ') || '-';
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    clearAllFilters() {
      this.columnFilters = {};
      this.quickSearch = '';
      this.quickFilter = 'all';
    },
    toggleSelectAll(v) {
      this.selectedIds = v ? this.filteredItems.map(i => i.id) : [];
    },
    async fetchData() {
      this.loading = true;
      await Promise.all([this.sectionStore.fetch(), this.corporalStore.fetch()]);
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = {
          name: item.name || '',
          daily_price: parseFloat(item.daily_price) || 0,
          is_price_modifiable: !!item.is_price_modifiable,
          corporal_ids: (item.corporals || []).map(c => c.id),
        };
      } else {
        this.form = this.emptyForm();
      }
      this.dialog = true;
    },
    async save() {
      if (!this.form.name) return;
      this.saving = true;
      try {
        let sec;
        if (this.editing) {
          sec = await this.sectionStore.update(this.editing.id, this.form);
          this.triggerSummarySnackbar({
            title: this.$t('section_updated') || 'Section Updated',
            icon: 'mdi-check-circle',
            color: 'success',
            fields: [
              { label: this.$t('section'), value: sec.name, icon: 'mdi-sitemap' },
              { label: this.$t('daily_price'), value: `${this.formatNumber(sec.daily_price)} ${this.main.currency || 'DH'}`, icon: 'mdi-cash' },
              { label: this.$t('corporals'), value: (sec.corporals || []).length, icon: 'mdi-account-tie' },
            ],
          });
        } else {
          sec = await this.sectionStore.create(this.form);
          this.triggerSummarySnackbar({
            title: this.$t('section_created') || 'Section Created',
            icon: 'mdi-sitemap-outline',
            color: 'success',
            fields: [
              { label: this.$t('section'), value: sec.name, icon: 'mdi-sitemap' },
              { label: this.$t('daily_price'), value: `${this.formatNumber(sec.daily_price)} ${this.main.currency || 'DH'}`, icon: 'mdi-cash' },
              { label: this.$t('corporals'), value: (sec.corporals || []).length, icon: 'mdi-account-tie' },
            ],
          });
        }
        this.dialog = false;
        await this.sectionStore.fetch();
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('section_save_error') || 'Error saving section', color: 'error' });
      } finally {
        this.saving = false;
      }
    },
    confirmDelete(item) {
      this.deleteTarget = item;
      this.confirmDeleteDialog = true;
    },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      const target = { ...this.deleteTarget };
      try {
        await this.sectionStore.remove(target.id);
        this.confirmDeleteDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('section_deleted') || 'Section Deleted',
          icon: 'mdi-delete-sweep',
          color: 'error',
          fields: [
            { label: this.$t('section'), value: target.name, icon: 'mdi-sitemap' },
          ],
        });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('section_delete_error') || 'Error deleting section', color: 'error' });
      } finally {
        this.deleting = false;
        this.deleteTarget = null;
      }
    },
    async executeBulkDelete() {
      if (!this.selectedIds.length) return;
      this.bulkDeleting = true;
      const count = this.selectedIds.length;
      try {
        await this.sectionStore.bulkRemove(this.selectedIds);
        this.bulkDeleteDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('bulk_deleted_success', { count, model: this.$t('sections') || 'sections' }) || `${count} sections deleted`,
          icon: 'mdi-delete-sweep',
          color: 'error',
          fields: [
            { label: this.$t('deleted_count'), value: count, icon: 'mdi-delete-sweep' },
          ],
        });
        this.selectedIds = [];
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('bulk_delete_error') || 'Error in bulk deletion', color: 'error' });
      } finally {
        this.bulkDeleting = false;
      }
    },

    triggerSummarySnackbar({ title, color = 'success', icon = 'mdi-check-circle', fields = [] }) {
      this.clearSnackbarTimers();
      this.snackbar = { show: true, title, color, icon, fields };
      this.snackbarProgress = 100;

      const duration = 4000;
      const step = 50;
      const decrement = (step / duration) * 100;

      this.snackbarInterval = setInterval(() => {
        this.snackbarProgress = Math.max(0, this.snackbarProgress - decrement);
      }, step);

      this.snackbarTimer = setTimeout(() => {
        this.snackbar.show = false;
        this.clearSnackbarTimers();
      }, duration);
    },
    clearSnackbarTimers() {
      if (this.snackbarTimer) { clearTimeout(this.snackbarTimer); this.snackbarTimer = null; }
      if (this.snackbarInterval) { clearInterval(this.snackbarInterval); this.snackbarInterval = null; }
    },
  },
};
</script>

<style scoped>
.scm-page-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.scm-card {
  border-radius: 20px !important;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  background: rgb(var(--v-theme-surface));
}
.scm-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  flex-shrink: 0;
}
.scm-hero-inner {
  position: relative;
  z-index: 1;
  padding: 16px 24px;
  color: #fff;
}
.scm-hero-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
  flex-shrink: 0;
}
.scm-hero-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0.3px;
}
.scm-kpi-chip {
  padding: 4px 12px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: inline-flex;
  align-items: center;
  color: #fff;
  font-size: 12px;
}
.scm-kpi-num {
  font-weight: 800;
  margin-right: 4px;
}
.scm-kpi-lbl {
  opacity: 0.85;
  font-size: 11px;
}

.scm-card-body {
  flex: 1 1 0%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.scm-search-input :deep(.v-field) {
  border-radius: 24px !important;
}
.scm-filter-toggle {
  border-color: rgba(66, 165, 245, 0.3) !important;
}

.scm-table-wrap {
  flex: 1 1 0%;
  min-height: 0;
  width: 100%;
  overflow-x: auto;
  border-radius: 12px;
}
.scm-table {
  width: 100%;
  border-color: rgba(var(--v-border-color), 0.12) !important;
}

.scm-avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: rgba(66, 165, 245, 0.12);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid rgba(66, 165, 245, 0.25);
}

.scm-th {
  padding: 8px 6px 4px !important;
  background: rgba(var(--v-theme-surface-variant), 0.04);
}
.scm-th-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  opacity: 0.75;
  text-align: center;
  white-space: nowrap;
}
.scm-filter-row th {
  padding: 0 4px 6px !important;
}
.scm-filter-th {
  vertical-align: top;
}
.scm-col-filter {
  min-width: 80px;
}
.scm-col-filter :deep(.v-field) {
  font-size: 11px !important;
  min-height: 28px !important;
  border-radius: 8px !important;
}
.scm-col-filter :deep(.v-field__input) {
  padding: 2px 6px !important;
  min-height: 24px !important;
  font-size: 11px;
}
.scm-col-filter :deep(.v-field__clearable) {
  font-size: 13px;
}
.scm-col-filter :deep(.v-chip) {
  height: 18px !important;
  font-size: 10px !important;
}

/* Dialog styling */
.scm-dialog-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  padding: 16px 20px;
  display: flex;
  align-items: center;
}
.scm-dialog-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
}
.scm-preview-card {
  background: linear-gradient(135deg, rgba(21, 101, 192, 0.03) 0%, rgba(66, 165, 245, 0.08) 100%);
  border-color: rgba(66, 165, 245, 0.3) !important;
}

/* Toggle card */
.scm-toggle-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-radius: 14px;
  border: 1.5px solid rgba(var(--v-border-color), 0.12);
  cursor: pointer;
  transition: all 0.2s ease;
  background: rgba(var(--v-theme-surface-variant), 0.02);
  height: 100%;
}
.scm-toggle-card:hover {
  border-color: rgba(66, 165, 245, 0.4);
  background: rgba(66, 165, 245, 0.03);
}
.scm-toggle-active-blue {
  border-color: #1565C0 !important;
  background: rgba(21, 101, 192, 0.05);
}
.scm-toggle-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.scm-toggle-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s ease;
  flex-shrink: 0;
}
.scm-toggle-label {
  font-size: 13px;
  font-weight: 700;
}

/* Summary Snackbar */
.scm-summary-snackbar {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  max-width: 380px;
  width: calc(100vw - 48px);
}
.scm-snackbar-card {
  display: flex;
  background: rgb(var(--v-theme-surface));
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18), 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-border-color), 0.12);
  overflow: hidden;
}
.scm-snackbar-accent {
  width: 5px;
  flex-shrink: 0;
}
.scm-snackbar-body {
  padding: 12px 14px;
  flex: 1;
  min-width: 0;
}
.scm-snackbar-header {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
}
.scm-snackbar-title {
  font-size: 13px;
  font-weight: 700;
}
.scm-snackbar-close {
  opacity: 0.6;
}
.scm-snackbar-close:hover {
  opacity: 1;
}
.scm-snackbar-grid {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-bottom: 6px;
}
.scm-snackbar-field {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
}
.scm-field-label {
  color: rgba(var(--v-theme-on-surface), 0.65);
}
.scm-field-val {
  font-weight: 700;
  display: inline-flex;
  align-items: center;
}
.scm-snackbar-progress {
  height: 3px;
  background: rgba(0, 0, 0, 0.06);
  border-radius: 2px;
  overflow: hidden;
}
.scm-progress-bar {
  height: 100%;
  transition: width 0.05s linear;
}

.scm-snackbar-anim-enter-active,
.scm-snackbar-anim-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.scm-snackbar-anim-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
.scm-snackbar-anim-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}
</style>
