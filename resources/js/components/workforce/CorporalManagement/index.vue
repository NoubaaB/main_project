<template>
  <div class="cpm-page-wrap">
    <v-card class="cpm-card border" elevation="0">
      <!-- ================= HERO HEADER ================= -->
      <div class="cpm-hero">
        <div class="cpm-hero-inner">
          <div class="d-flex flex-wrap align-center justify-space-between ga-4">
            <div class="d-flex align-center ga-3">
              <div class="cpm-hero-icon">
                <v-icon size="28" color="white">mdi-account-tie</v-icon>
              </div>
              <div>
                <div class="cpm-hero-title">{{ $t('corporal_management') }}</div>
                <div class="text-white text-caption" style="opacity: .85;">{{ $t('manage_corporals_desc') }}</div>
              </div>
            </div>

            <!-- Hero KPI Chips -->
            <div class="d-flex flex-wrap align-center ga-2">
              <div class="cpm-kpi-chip">
                <v-icon size="16" color="#90CAF9" class="mr-1">mdi-account-tie</v-icon>
                <span class="cpm-kpi-num">{{ items.length }}</span>
                <span class="cpm-kpi-lbl">{{ $t('total') }}</span>
              </div>
              <div class="cpm-kpi-chip">
                <v-icon size="16" color="#A5D6A7" class="mr-1">mdi-calendar-check</v-icon>
                <span class="cpm-kpi-num">{{ quinzaineCount }}</span>
                <span class="cpm-kpi-lbl">{{ $t('quinzaine') }}</span>
              </div>
              <div class="cpm-kpi-chip">
                <v-icon size="16" color="#FFE082" class="mr-1">mdi-sitemap</v-icon>
                <span class="cpm-kpi-num">{{ withSectionsCount }}</span>
                <span class="cpm-kpi-lbl">{{ $t('with_sections') || 'With Sections' }}</span>
              </div>
              <div class="cpm-kpi-chip">
                <v-icon size="16" color="#80DEEA" class="mr-1">mdi-cash-multiple</v-icon>
                <span class="cpm-kpi-num">{{ avgRate }}</span>
                <span class="cpm-kpi-lbl">{{ main.currency || 'DH' }} avg</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= TOOLBAR & ACTIONS ================= -->
      <v-card-text class="pa-4 cpm-card-body">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-3">
          <!-- Left: Search & Quick Filter Pills -->
          <div class="d-flex flex-wrap align-center ga-2 flex-grow-1" style="max-width: 680px;">
            <v-text-field
              v-model="quickSearch"
              :placeholder="$t('search_corporals') || 'Search corporals by name, CNI, sections...'"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              rounded
              color="#42A5F5"
              class="cpm-search-input text-workforce"
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
              class="cpm-filter-toggle"
            >
              <v-btn value="all" size="small">{{ $t('all') }} ({{ items.length }})</v-btn>
              <v-btn value="quinzaine" size="small">{{ $t('quinzaine') }} ({{ quinzaineCount }})</v-btn>
              <v-btn value="sections" size="small">{{ $t('sections') }} ({{ withSectionsCount }})</v-btn>
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
              {{ $t('add_corporal') }}
            </v-btn>
          </div>
        </div>

        <!-- ================= RESPONSIVE DATA TABLE ================= -->
        <div class="cpm-table-wrap">
          <v-data-table-virtual
            :headers="headers"
            :items="filteredItems"
            :loading="loading ? '#42A5F5' : false"
            density="compact"
            class="rounded-xl cpm-table border"
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
                <th v-for="col in columns" :key="col.key" class="cpm-th">
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
                    class="cpm-th-label"
                    @click="col.sortable !== false ? toggleSort(col) : null"
                    :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
                  >
                    {{ col.title }}
                    <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
                  </div>
                </th>
              </tr>
              <tr class="cpm-filter-row">
                <th v-for="col in columns" :key="col.key + '-filter'" class="cpm-filter-th">
                  <v-autocomplete
                    v-if="col.key !== 'actions' && col.key !== 'data-table-select'"
                    v-model="columnFilters[col.key]"
                    :items="getColumnOptions(col.key)"
                    density="compact"
                    variant="outlined"
                    rounded
                    color="#42A5F5"
                    class="cpm-col-filter text-workforce"
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

            <!-- Corporal Name & Avatar -->
            <template v-slot:[`item.full_name`]="{ item }">
              <div class="d-flex align-center ga-2 py-1">
                <div class="cpm-avatar-circle">
                  {{ getInitials(item.full_name) }}
                </div>
                <div>
                  <div class="font-weight-bold text-body-2 text-truncate" style="max-width: 240px;">
                    {{ item.full_name }}
                  </div>
                  <div v-if="item.cni" class="text-caption text-medium-emphasis">
                    {{ item.cni }}
                  </div>
                </div>
              </div>
            </template>

            <!-- Sex -->
            <template v-slot:[`item.sex`]="{ item }">
              <v-chip :color="item.sex === 'male' ? 'blue' : 'pink'" size="x-small" variant="tonal" class="font-weight-medium">
                <v-icon size="12" class="mr-1">{{ item.sex === 'male' ? 'mdi-gender-male' : 'mdi-gender-female' }}</v-icon>
                {{ $t(item.sex) }}
              </v-chip>
            </template>

            <!-- CNI -->
            <template v-slot:[`item.cni`]="{ item }">
              <span class="text-caption font-mono">{{ item.cni || '-' }}</span>
            </template>

            <!-- Is Quinzaine -->
            <template v-slot:[`item.is_quinzaine`]="{ item }">
              <v-chip
                size="x-small"
                :color="item.is_quinzaine ? 'success' : 'grey'"
                :variant="item.is_quinzaine ? 'flat' : 'outlined'"
                class="font-weight-medium"
              >
                <v-icon size="12" class="mr-1">{{ item.is_quinzaine ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                {{ item.is_quinzaine ? $t('yes') : $t('no') }}
              </v-chip>
            </template>

            <!-- Sections chips -->
            <template v-slot:[`item.sections`]="{ item }">
              <div class="d-flex flex-wrap ga-1 justify-center py-1">
                <v-chip
                  v-for="s in (item.sections || [])"
                  :key="s.id"
                  size="x-small"
                  color="#1565C0"
                  variant="tonal"
                  class="font-weight-medium"
                >
                  <v-icon size="11" class="mr-1">mdi-sitemap-outline</v-icon>
                  {{ s.name }}
                </v-chip>
                <span v-if="!item.sections || !item.sections.length" class="text-grey text-caption">-</span>
              </div>
            </template>

            <!-- Daily Price -->
            <template v-slot:[`item.daily_price`]="{ item }">
              <v-chip size="small" color="success" variant="tonal" class="font-weight-bold">
                {{ formatNumber(item.daily_price) }} {{ main.currency || 'DH' }}
              </v-chip>
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

    <!-- ============= CREATE / EDIT CORPORAL DIALOG ============= -->
    <v-dialog v-model="dialog" max-width="660" persistent scrollable>
      <v-card class="rounded-2xl overflow-hidden elevation-12">
        <!-- Dialog Header Banner -->
        <div class="cpm-dialog-hero">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="cpm-dialog-icon">
                <v-icon size="24" color="white">{{ editing ? 'mdi-account-edit' : 'mdi-account-tie-hat' }}</v-icon>
              </div>
              <div>
                <div class="text-subtitle-1 font-weight-bold text-white">
                  {{ editing ? $t('edit_corporal') : $t('add_corporal') }}
                </div>
                <div class="text-caption text-white" style="opacity: .8;">
                  {{ editing ? form.full_name : $t('manage_corporals_desc') }}
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
          <div class="cpm-preview-card mb-4 pa-3 rounded-xl border">
            <div class="d-flex align-center ga-3">
              <div class="cpm-avatar-circle" style="width: 44px; height: 44px; font-size: 15px;">
                {{ getInitials(form.full_name) }}
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-center ga-2">
                  <span class="font-weight-bold text-body-1 text-truncate">
                    {{ form.full_name || $t('full_name') }}
                  </span>
                  <v-chip v-if="form.cni" size="x-small" color="grey" variant="tonal" class="font-mono">
                    {{ form.cni }}
                  </v-chip>
                </div>
                <div class="d-flex flex-wrap align-center ga-2 mt-1">
                  <v-chip size="x-small" color="success" variant="flat" class="text-white font-weight-bold">
                    {{ formatNumber(form.daily_price) }} {{ main.currency || 'DH' }}/day
                  </v-chip>
                  <v-chip v-if="form.is_quinzaine" size="x-small" color="#1565C0" variant="tonal">
                    {{ $t('quinzaine') }}
                  </v-chip>
                  <span class="text-caption text-medium-emphasis">
                    {{ (form.section_ids || []).length }} {{ $t('sections') }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Fields -->
          <v-row dense>
            <v-col cols="12">
              <v-text-field
                v-model="form.full_name"
                :label="$t('full_name')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-account-tie"
                :rules="[v => !!v || $t('is_required', { type: $t('full_name') })]"
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="6">
              <v-select
                v-model="form.sex"
                :items="sexOptions"
                :label="$t('sex')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-gender-male-female"
              ></v-select>
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.cni"
                :label="$t('cni')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                placeholder="ex: AB123456"
                prepend-inner-icon="mdi-card-account-details-outline"
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
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <!-- Sections Multi-Autocomplete -->
            <v-col cols="12">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption font-weight-bold text-medium-emphasis">
                  <v-icon size="14" color="#42A5F5" class="mr-1">mdi-sitemap-outline</v-icon>
                  {{ $t('affiliated_sections') || 'Affiliated Sections' }}
                </span>
                <v-chip size="x-small" color="#42A5F5" variant="tonal">
                  {{ (form.section_ids || []).length }} {{ $t('selected') }}
                </v-chip>
              </div>
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
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-sitemap"
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
                      <v-icon size="18" color="#1565C0" class="mr-2">mdi-sitemap-outline</v-icon>
                      <v-checkbox-btn
                        :model-value="(form.section_ids || []).includes(item.value)"
                        color="#42A5F5"
                        density="compact"
                        class="mr-1"
                      ></v-checkbox-btn>
                    </template>
                    <v-list-item-title class="font-weight-medium">{{ item.raw.name }}</v-list-item-title>
                    <v-list-item-subtitle v-if="item.raw.daily_price" class="text-caption text-medium-emphasis">
                      {{ formatNumber(item.raw.daily_price) }} {{ main.currency || 'DH' }}
                    </v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>

            <!-- Quinzaine Toggle Card -->
            <v-col cols="12">
              <div
                class="cpm-toggle-card"
                :class="{ 'cpm-toggle-active-blue': form.is_quinzaine }"
                @click="form.is_quinzaine = !form.is_quinzaine"
              >
                <div class="cpm-toggle-left">
                  <div class="cpm-toggle-icon-wrap" :style="{ background: form.is_quinzaine ? '#1565C01A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form.is_quinzaine ? '#1565C0' : 'grey'" size="20">mdi-calendar-check</v-icon>
                  </div>
                  <div>
                    <div class="cpm-toggle-label">{{ $t('is_quinzaine') }}</div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
                      {{ $t('quinzaine_eligible_desc') || 'Enable fortnight attendance tracking and activity logging' }}
                    </div>
                  </div>
                </div>
                <v-switch v-model="form.is_quinzaine" color="#42A5F5" inset density="compact" hide-details @click.stop></v-switch>
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
          {{ $t('confirm_delete_corporal_message') }}
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
    <transition name="cpm-snackbar-anim">
      <div v-if="snackbar.show" class="cpm-summary-snackbar">
        <div class="cpm-snackbar-card">
          <div class="cpm-snackbar-accent" style="background: #42A5F5;"></div>
          <div class="cpm-snackbar-body">
            <div class="cpm-snackbar-header">
              <v-icon :color="snackbar.color" size="20" class="mr-2">{{ snackbar.icon }}</v-icon>
              <span class="cpm-snackbar-title">{{ snackbar.title }}</span>
              <v-btn icon size="x-small" variant="text" class="ml-auto cpm-snackbar-close" @click="snackbar.show = false">
                <v-icon size="16">mdi-close</v-icon>
              </v-btn>
            </div>
            <div class="cpm-snackbar-grid">
              <div v-for="(f, i) in snackbar.fields" :key="i" class="cpm-snackbar-field">
                <span class="cpm-field-label">{{ f.label }}</span>
                <span class="cpm-field-val">
                  <v-icon v-if="f.icon" size="14" class="mr-1" style="opacity:.7">{{ f.icon }}</v-icon>
                  {{ f.value }}
                </span>
              </div>
            </div>
            <div class="cpm-snackbar-progress">
              <div class="cpm-progress-bar" style="background: #42A5F5;" :style="{ width: snackbarProgress + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useCorporal } from '@/stores/Corporal';
import { useSection } from '@/stores/Section';

export default {
  name: 'CorporalManagement',
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
      sexOptions: [
        { title: this.$t('male'), value: 'male' },
        { title: this.$t('female'), value: 'female' },
      ],
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
    corporalStore() { return useCorporal(); },
    sectionStore() { return useSection(); },
    items() { return this.corporalStore.list || []; },

    tableHeight() {
      return Math.max(340, this.windowHeight - 275);
    },

    quinzaineCount() {
      return this.items.filter(c => c.is_quinzaine).length;
    },
    withSectionsCount() {
      return this.items.filter(c => c.sections && c.sections.length > 0).length;
    },
    avgRate() {
      if (!this.items.length) return '0.00';
      const sum = this.items.reduce((acc, c) => acc + (parseFloat(c.daily_price) || 0), 0);
      return this.formatNumber(sum / this.items.length);
    },

    headers() {
      return [
        { title: this.$t('full_name'), key: 'full_name', align: 'start', minWidth: '220px' },
        { title: this.$t('sex'), key: 'sex', align: 'center', minWidth: '100px' },
        { title: this.$t('cni'), key: 'cni', align: 'center', minWidth: '110px' },
        { title: this.$t('is_quinzaine'), key: 'is_quinzaine', align: 'center', minWidth: '120px' },
        { title: this.$t('sections'), key: 'sections', align: 'center', minWidth: '220px' },
        { title: this.$t('daily_price'), key: 'daily_price', align: 'center', minWidth: '120px' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, minWidth: '90px' },
      ];
    },

    filteredItems() {
      let result = this.items;

      if (this.quickFilter === 'quinzaine') {
        result = result.filter(c => c.is_quinzaine);
      } else if (this.quickFilter === 'sections') {
        result = result.filter(c => c.sections && c.sections.length > 0);
      }

      if (this.quickSearch && this.quickSearch.trim()) {
        const q = this.quickSearch.toLowerCase().trim();
        result = result.filter(item => {
          const matchSections = (item.sections || []).some(s => s.name && s.name.toLowerCase().includes(q));
          return (
            (item.full_name && item.full_name.toLowerCase().includes(q)) ||
            (item.cni && item.cni.toLowerCase().includes(q)) ||
            matchSections
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
        full_name: '',
        sex: 'male',
        cni: '',
        daily_price: 0,
        is_quinzaine: true,
        section_ids: [],
      };
    },
    getCellDisplayValue(key, item) {
      if (key === 'is_quinzaine') return item.is_quinzaine ? this.$t('yes') : this.$t('no');
      if (key === 'sex') return this.$t(item[key]);
      if (key === 'daily_price') return this.formatNumber(item[key]);
      if (key === 'sections') return (item.sections || []).map(s => s.name).join(', ') || '-';
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
      await Promise.all([this.corporalStore.fetch(), this.sectionStore.fetch()]);
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = {
          full_name: item.full_name || '',
          sex: item.sex || 'male',
          cni: item.cni || '',
          daily_price: parseFloat(item.daily_price) || 0,
          is_quinzaine: !!item.is_quinzaine,
          section_ids: (item.sections || []).map(s => s.id),
        };
      } else {
        this.form = this.emptyForm();
      }
      this.dialog = true;
    },
    async save() {
      if (!this.form.full_name) return;
      this.saving = true;
      try {
        let corp;
        if (this.editing) {
          corp = await this.corporalStore.update(this.editing.id, this.form);
          this.triggerSummarySnackbar({
            title: this.$t('corporal_updated') || 'Corporal Updated',
            icon: 'mdi-check-circle',
            color: 'success',
            fields: [
              { label: this.$t('corporal'), value: corp.full_name, icon: 'mdi-account-tie' },
              { label: this.$t('daily_price'), value: `${this.formatNumber(corp.daily_price)} ${this.main.currency || 'DH'}`, icon: 'mdi-cash' },
              { label: this.$t('sections'), value: (corp.sections || []).length, icon: 'mdi-sitemap' },
            ],
          });
        } else {
          corp = await this.corporalStore.create(this.form);
          this.triggerSummarySnackbar({
            title: this.$t('corporal_created') || 'Corporal Created',
            icon: 'mdi-account-tie-hat',
            color: 'success',
            fields: [
              { label: this.$t('corporal'), value: corp.full_name, icon: 'mdi-account-tie' },
              { label: this.$t('daily_price'), value: `${this.formatNumber(corp.daily_price)} ${this.main.currency || 'DH'}`, icon: 'mdi-cash' },
              { label: this.$t('sections'), value: (corp.sections || []).length, icon: 'mdi-sitemap' },
            ],
          });
        }
        this.dialog = false;
        await this.corporalStore.fetch();
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('corporal_save_error') || 'Error saving corporal', color: 'error' });
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
        await this.corporalStore.remove(target.id);
        this.confirmDeleteDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('corporal_deleted') || 'Corporal Deleted',
          icon: 'mdi-delete-sweep',
          color: 'error',
          fields: [
            { label: this.$t('corporal'), value: target.full_name, icon: 'mdi-account-remove' },
          ],
        });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('corporal_delete_error') || 'Error deleting corporal', color: 'error' });
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
        await this.corporalStore.bulkRemove(this.selectedIds);
        this.bulkDeleteDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('bulk_deleted_success', { count, model: this.$t('corporals') || 'corporals' }) || `${count} corporals deleted`,
          icon: 'mdi-delete-sweep',
          color: 'error',
          fields: [
            { label: this.$t('deleted_count'), value: count, icon: 'mdi-account-multiple-remove' },
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
.cpm-page-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.cpm-card {
  border-radius: 20px !important;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  background: rgb(var(--v-theme-surface));
}
.cpm-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  flex-shrink: 0;
}
.cpm-hero-inner {
  position: relative;
  z-index: 1;
  padding: 16px 24px;
  color: #fff;
}
.cpm-hero-icon {
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
.cpm-hero-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0.3px;
}
.cpm-kpi-chip {
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
.cpm-kpi-num {
  font-weight: 800;
  margin-right: 4px;
}
.cpm-kpi-lbl {
  opacity: 0.85;
  font-size: 11px;
}

.cpm-card-body {
  flex: 1 1 0%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.cpm-search-input :deep(.v-field) {
  border-radius: 24px !important;
}
.cpm-filter-toggle {
  border-color: rgba(66, 165, 245, 0.3) !important;
}

.cpm-table-wrap {
  flex: 1 1 0%;
  min-height: 0;
  width: 100%;
  overflow-x: auto;
  border-radius: 12px;
}
.cpm-table {
  width: 100%;
  border-color: rgba(var(--v-border-color), 0.12) !important;
}

.cpm-avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1565C0, #42A5F5);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  letter-spacing: 0.3px;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(21, 101, 192, 0.25);
}

.cpm-th {
  padding: 8px 6px 4px !important;
  background: rgba(var(--v-theme-surface-variant), 0.04);
}
.cpm-th-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  opacity: 0.75;
  text-align: center;
  white-space: nowrap;
}
.cpm-filter-row th {
  padding: 0 4px 6px !important;
}
.cpm-filter-th {
  vertical-align: top;
}
.cpm-col-filter {
  min-width: 80px;
}
.cpm-col-filter :deep(.v-field) {
  font-size: 11px !important;
  min-height: 28px !important;
  border-radius: 8px !important;
}
.cpm-col-filter :deep(.v-field__input) {
  padding: 2px 6px !important;
  min-height: 24px !important;
  font-size: 11px;
}
.cpm-col-filter :deep(.v-field__clearable) {
  font-size: 13px;
}
.cpm-col-filter :deep(.v-chip) {
  height: 18px !important;
  font-size: 10px !important;
}

/* Dialog styling */
.cpm-dialog-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  padding: 16px 20px;
  display: flex;
  align-items: center;
}
.cpm-dialog-icon {
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
.cpm-preview-card {
  background: linear-gradient(135deg, rgba(21, 101, 192, 0.03) 0%, rgba(66, 165, 245, 0.08) 100%);
  border-color: rgba(66, 165, 245, 0.3) !important;
}

/* Toggle card */
.cpm-toggle-card {
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
.cpm-toggle-card:hover {
  border-color: rgba(66, 165, 245, 0.4);
  background: rgba(66, 165, 245, 0.03);
}
.cpm-toggle-active-blue {
  border-color: #1565C0 !important;
  background: rgba(21, 101, 192, 0.05);
}
.cpm-toggle-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.cpm-toggle-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s ease;
  flex-shrink: 0;
}
.cpm-toggle-label {
  font-size: 13px;
  font-weight: 700;
}

/* Summary Snackbar */
.cpm-summary-snackbar {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  max-width: 380px;
  width: calc(100vw - 48px);
}
.cpm-snackbar-card {
  display: flex;
  background: rgb(var(--v-theme-surface));
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18), 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-border-color), 0.12);
  overflow: hidden;
}
.cpm-snackbar-accent {
  width: 5px;
  flex-shrink: 0;
}
.cpm-snackbar-body {
  padding: 12px 14px;
  flex: 1;
  min-width: 0;
}
.cpm-snackbar-header {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
}
.cpm-snackbar-title {
  font-size: 13px;
  font-weight: 700;
}
.cpm-snackbar-close {
  opacity: 0.6;
}
.cpm-snackbar-close:hover {
  opacity: 1;
}
.cpm-snackbar-grid {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-bottom: 6px;
}
.cpm-snackbar-field {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
}
.cpm-field-label {
  color: rgba(var(--v-theme-on-surface), 0.65);
}
.cpm-field-val {
  font-weight: 700;
  display: inline-flex;
  align-items: center;
}
.cpm-snackbar-progress {
  height: 3px;
  background: rgba(0, 0, 0, 0.06);
  border-radius: 2px;
  overflow: hidden;
}
.cpm-progress-bar {
  height: 100%;
  transition: width 0.05s linear;
}

.cpm-snackbar-anim-enter-active,
.cpm-snackbar-anim-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.cpm-snackbar-anim-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
.cpm-snackbar-anim-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}
</style>
