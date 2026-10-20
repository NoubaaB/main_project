<template>
  <v-card class="fm-card" elevation="0">
    <!-- Hero Header -->
    <div class="fm-hero">
      <div class="fm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="fm-hero-icon">
            <v-icon size="28" color="white">mdi-cash-lock-open</v-icon>
          </div>
          <div>
            <div class="fm-hero-title">{{ $t('static_charge_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('static_charge_management_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toolbar with SegmentedPillToggle (Fish Management style) -->
    <div class="fm-toolbar-card mx-4 mt-3 mb-2 pa-2 rounded-xl border d-flex flex-wrap align-center justify-space-between ga-3">
      <div class="d-flex flex-wrap align-center ga-3">
        <SegmentedPillToggle
          v-model="activeTab"
          :items="[
            { value: 'charges', label: $t('static_charges'), icon: 'mdi-format-list-bulleted', badge: store.list.length },
            { value: 'info', label: $t('how_it_works'), icon: 'mdi-information-outline' }
          ]"
          color="#00BCD4"
        />
      </div>
    </div>

    <v-card-text class="pa-4">
      <v-window v-model="activeTab">
        <!-- ============= STATIC CHARGES TAB ============= -->
        <v-window-item value="charges">
          <div class="d-flex align-center ga-3 mb-3">
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
            <v-btn color="#00BCD4" prepend-icon="mdi-plus" rounded @click="openAddDialog">
              {{ $t('add_static_charge') }}
            </v-btn>
          </div>

          <!-- Empty State -->
          <div v-if="!store.list.length && !store.loading" class="text-center pa-8 text-grey">
            <v-icon size="56" color="grey-lighten-2">mdi-cash-lock-open</v-icon>
            <div class="mt-3 text-body-1">{{ $t('no_static_charges_found') }}</div>
            <v-btn
              class="mt-4"
              variant="flat"
              style="background-color: #00BCD4; color: #fff;"
              rounded
              prepend-icon="mdi-plus"
              @click="openAddDialog"
            >
              {{ $t('add_static_charge') }}
            </v-btn>
          </div>

          <!-- Data Table with Multi-Sort & Column Filters -->
          <v-data-table
            v-else
            :headers="headers"
            :items="filteredCharges"
            :loading="store.loading ? '#00BCD4' : false"
            :multi-sort="true"
            density="comfortable"
            class="rounded-lg param-table"
            hover
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
                    color="#00BCD4"
                    class="param-filter-input"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    :placeholder="$t('filter')"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" size="x-small" color="#00BCD4" variant="tonal" closable>
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
                </th>
              </tr>
            </template>

            <template v-slot:[`item.name`]="{ item }">
              <span class="font-weight-medium">{{ getLocalizedName(item) }}</span>
            </template>

            <template v-slot:[`item.amount`]="{ item }">
              <div class="text-center font-weight-bold" style="color: #00838F;">
                {{ formatNumber(item.amount) }} {{ main.currency }}
              </div>
            </template>

            <template v-slot:[`item.date_range_type`]="{ item }">
              <div class="text-center">
                <v-chip
                  size="small"
                  :color="dateRangeChipColor(item.date_range_type)"
                  variant="tonal"
                  label
                >
                  <v-icon start size="14">{{ dateRangeIcon(item.date_range_type) }}</v-icon>
                  {{ $t(item.date_range_type) }}
                </v-chip>
              </div>
            </template>

            <template v-slot:[`item.date_start`]="{ item }">
              <v-chip v-if="item.date_start" size="small" color="#00BCD4" variant="tonal" label>
                <v-icon start size="14">mdi-calendar-start</v-icon>
                {{ formatDate(item.date_start) }}
              </v-chip>
              <span v-else class="text-grey text-caption">—</span>
            </template>

            <template v-slot:[`item.date_end`]="{ item }">
              <v-chip v-if="item.date_end" size="small" color="warning" variant="tonal" label>
                <v-icon start size="14">mdi-calendar-end</v-icon>
                {{ formatDate(item.date_end) }}
              </v-chip>
              <v-chip v-else-if="item.date_range_type === 'ongoing'" size="small" color="success" variant="tonal" label>
                <v-icon start size="14">mdi-infinity</v-icon>
                {{ $t('ongoing') }}
              </v-chip>
              <v-chip v-else-if="item.date_range_type === 'all_time'" size="small" color="info" variant="tonal" label>
                <v-icon start size="14">mdi-infinity</v-icon>
                {{ $t('all_time') }}
              </v-chip>
              <span v-else class="text-grey text-caption">—</span>
            </template>

            <template v-slot:[`item.is_active`]="{ item }">
              <div class="text-center">
                <v-icon :color="item.is_active ? 'success' : 'grey'">
                  {{ item.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                </v-icon>
              </div>
            </template>

            <template v-slot:[`item.actions`]="{ item }">
              <div class="d-flex align-center justify-center">
                <v-btn icon size="x-small" variant="tonal" color="#00BCD4" @click="openEditDialog(item)" class="mr-1">
                  <v-icon size="16">mdi-pencil</v-icon>
                  <v-tooltip activator="parent">{{ $t('edit') }}</v-tooltip>
                </v-btn>
                <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)">
                  <v-icon size="16">mdi-delete</v-icon>
                  <v-tooltip activator="parent">{{ $t('delete') }}</v-tooltip>
                </v-btn>
              </div>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= HOW IT WORKS TAB ============= -->
        <v-window-item value="info">
          <v-card variant="outlined" class="rounded-xl border" color="#00BCD4">
            <v-card-title class="d-flex align-center pa-4 pb-2">
              <v-icon color="#00BCD4" class="mr-2">mdi-information-outline</v-icon>
              <span class="text-subtitle-1 font-weight-bold" style="color: #00838F;">{{ $t('how_it_works') }}</span>
            </v-card-title>
            <v-card-text class="pa-4 pt-2">
              <div class="sc-info-item mb-3">
                <v-icon size="18" color="#00BCD4" class="mr-2">mdi-infinity</v-icon>
                <span class="text-body-2">{{ $t('static_charge_info_all_time') }}</span>
              </div>
              <div class="sc-info-item mb-3">
                <v-icon size="18" color="warning" class="mr-2">mdi-calendar-range</v-icon>
                <span class="text-body-2">{{ $t('static_charge_info_date_range') }}</span>
              </div>
              <div class="sc-info-item mb-3">
                <v-icon size="18" color="success" class="mr-2">mdi-calendar-arrow-right</v-icon>
                <span class="text-body-2">{{ $t('static_charge_info_ongoing') }}</span>
              </div>
              <div class="sc-info-item">
                <v-icon size="18" color="#00BCD4" class="mr-2">mdi-chart-areaspline</v-icon>
                <span class="text-body-2">{{ $t('static_charge_info_charts') }}</span>
              </div>
            </v-card-text>
          </v-card>
        </v-window-item>
      </v-window>
    </v-card-text>

    <!-- Add/Edit Dialog -->
    <v-dialog v-model="dialog.show" max-width="620" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#00BCD4" class="mr-2">{{ dialog.editId ? 'mdi-pencil' : 'mdi-plus-circle' }}</v-icon>
          <span>{{ dialog.editId ? $t('edit_static_charge') : $t('add_static_charge') }}</span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="dialog.show = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider style="border-color: #00BCD4;"></v-divider>

        <v-card-text class="pa-4">
          <v-row>
            <!-- Names (multilingual) -->
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="dialog.form.name_en"
                :label="$t('name') + ' (EN)'"
                density="compact"
                variant="outlined"
                rounded
                color="#00BCD4"
                prepend-inner-icon="mdi-translate"
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="dialog.form.name_fr"
                :label="$t('name') + ' (FR)'"
                density="compact"
                variant="outlined"
                rounded
                color="#00BCD4"
                prepend-inner-icon="mdi-translate"
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="dialog.form.name_es"
                :label="$t('name') + ' (ES)'"
                density="compact"
                variant="outlined"
                rounded
                color="#00BCD4"
                prepend-inner-icon="mdi-translate"
              ></v-text-field>
            </v-col>

            <!-- Amount -->
            <v-col cols="12" sm="6">
              <v-text-field
                v-model.number="dialog.form.amount"
                :label="$t('static_charge_amount')"
                density="compact"
                variant="outlined"
                rounded
                color="#00BCD4"
                type="number"
                min="0"
                step="0.01"
                :prefix="main.currency"
                prepend-inner-icon="mdi-cash"
              ></v-text-field>
            </v-col>

            <!-- Active toggle -->
            <v-col cols="12" sm="6" class="d-flex align-center">
              <v-switch
                v-model="dialog.form.is_active"
                :label="$t('is_active')"
                color="#00BCD4"
                density="compact"
                hide-details
              ></v-switch>
            </v-col>

            <!-- Date Range Type -->
            <v-col cols="12">
              <v-select
                v-model="dialog.form.date_range_type"
                :items="dateRangeTypeOptions"
                :label="$t('date_range_type')"
                density="compact"
                variant="outlined"
                rounded
                color="#00BCD4"
                prepend-inner-icon="mdi-calendar-range"
              ></v-select>
            </v-col>

            <!-- Calendar section -->
            <v-col v-if="dialog.form.date_range_type !== 'all_time'" cols="12">
              <!-- Date Range mode: range picker -->
              <DatePicker
                v-if="dialog.form.date_range_type === 'date_range'"
                :is-dark="main.is_dark"
                v-model.range="dialog.dateRange"
                mode="date"
                :locale="main.lang?.short || 'en'"
                expanded
                color="teal"
              />
              <!-- Ongoing mode: single date picker (start only) -->
              <DatePicker
                v-if="dialog.form.date_range_type === 'ongoing'"
                :is-dark="main.is_dark"
                v-model="dialog.dateRange.start"
                mode="date"
                :locale="main.lang?.short || 'en'"
                expanded
                color="teal"
              />

              <!-- Date display chips -->
              <div class="text-center mt-3" role="group">
                <v-chip color="#00BCD4" variant="tonal" class="mr-2">
                  <v-icon left small class="mr-2">mdi-calendar-start</v-icon>
                  <span>{{ main.formateDate(dialog.dateRange.start) }}</span>
                </v-chip>
                <v-divider vertical></v-divider>
                <v-chip
                  :color="dialog.form.date_range_type === 'ongoing' ? 'success' : '#00BCD4'"
                  variant="tonal"
                  class="ml-2"
                >
                  <span>{{ dialog.form.date_range_type === 'ongoing' ? $t('ongoing') : main.formateDate(dialog.dateRange.end) }}</span>
                  <v-icon left small class="ml-2">{{ dialog.form.date_range_type === 'ongoing' ? 'mdi-infinity' : 'mdi-calendar-end' }}</v-icon>
                </v-chip>
              </div>
            </v-col>

            <!-- All Time info -->
            <v-col v-if="dialog.form.date_range_type === 'all_time'" cols="12">
              <v-alert type="info" variant="tonal" density="compact" rounded="lg" icon="mdi-infinity">
                {{ $t('static_charge_all_time_hint') }}
              </v-alert>
            </v-col>

            <!-- Description -->
            <v-col cols="12">
              <v-textarea
                v-model="dialog.form.description"
                :label="$t('description')"
                density="compact"
                variant="outlined"
                rounded
                color="#00BCD4"
                rows="2"
                auto-grow
                :counter="1000"
                :maxlength="1000"
              ></v-textarea>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider></v-divider>
        <v-card-actions class="pa-4">
          <v-btn variant="text" color="red-lighten-1" rounded @click="dialog.show = false">
            {{ $t('cancel') }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            style="background-color: #00BCD4; color: #fff;"
            rounded
            :loading="dialog.saving"
            :disabled="!canSave || dialog.saving"
            prepend-icon="mdi-content-save"
            @click="saveDialog"
          >
            {{ dialog.editId ? $t('update') : $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog.show" max-width="420">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="error" class="mr-2">mdi-alert-circle</v-icon>
          <span>{{ $t('confirm_delete_static_charge') }}</span>
        </v-card-title>
        <v-card-text class="pa-4 pt-0">
          {{ $t('confirm_delete_static_charge_text') }}
          <div v-if="deleteDialog.charge" class="mt-3 pa-3 rounded-lg" style="background: rgba(244,67,54,.06);">
            <strong>{{ getLocalizedName(deleteDialog.charge) }}</strong>
            <br>
            {{ formatNumber(deleteDialog.charge.amount) }} {{ main.currency }}
            <span class="text-grey ml-2">·</span>
            <span class="ml-2">{{ $t(deleteDialog.charge.date_range_type) }}</span>
          </div>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn variant="text" rounded @click="deleteDialog.show = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            color="error"
            rounded
            :loading="deleteDialog.deleting"
            prepend-icon="mdi-delete"
            @click="doDelete"
          >
            {{ $t('delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="snackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#00BCD4" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="snackbar.success ? 'success' : 'error'" class="mr-2">{{ snackbar.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ snackbar.text }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div v-if="snackbar.detail" class="d-flex flex-column ga-1 text-body-2">
        <div v-if="snackbar.detail.name"><v-icon size="14" color="#00BCD4" class="mr-1">mdi-tag</v-icon><span class="text-medium-emphasis">{{ $t('static_charge_name') }}:</span> <strong>{{ snackbar.detail.name }}</strong></div>
        <div v-if="snackbar.detail.amount"><v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('static_charge_amount') }}:</span> <strong>{{ snackbar.detail.amount }} {{ main.currency }}</strong></div>
        <div v-if="snackbar.detail.dateType"><v-icon size="14" color="#00BCD4" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('date_range_type') }}:</span> <strong>{{ snackbar.detail.dateType }}</strong></div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useStaticCharge } from '@/stores/StaticCharge';
import { useMain } from '@/stores/Main';

export default {
  name: 'StaticChargeManagement',
  components: { SegmentedPillToggle },
  data() {
    return {
      activeTab: 'charges',
      columnFilters: {},
      dialog: {
        show: false,
        editId: null,
        saving: false,
        dateRange: { start: new Date(), end: new Date() },
        form: {
          name_en: '',
          name_fr: '',
          name_es: '',
          amount: 0,
          date_range_type: 'all_time',
          description: '',
          is_active: true,
        },
      },
      deleteDialog: {
        show: false,
        charge: null,
        deleting: false,
      },
      snackbar: { show: false, text: '', success: true, detail: null },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    store() { return useStaticCharge(); },
    main() { return useMain(); },
    lang() { return this.main.lang?.code || 'en'; },
    headers() {
      return [
        { title: this.$t('static_charge_name'), key: 'name', align: 'start' },
        { title: this.$t('static_charge_amount'), key: 'amount', align: 'center' },
        { title: this.$t('date_range_type'), key: 'date_range_type', align: 'center' },
        { title: this.$t('date_start'), key: 'date_start', align: 'start' },
        { title: this.$t('date_end'), key: 'date_end', align: 'start' },
        { title: this.$t('status'), key: 'is_active', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(v => Array.isArray(v) ? v.length > 0 : !!v);
    },
    sortedCharges() {
      return [...this.store.list].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    },
    filteredCharges() {
      if (!this.hasActiveColumnFilters) return this.sortedCharges;
      return this.sortedCharges.filter(item => {
        for (const [key, filterValues] of Object.entries(this.columnFilters)) {
          if (!filterValues || (Array.isArray(filterValues) && filterValues.length === 0)) continue;
          let val = '';
          if (key === 'name') val = this.getLocalizedName(item);
          else if (key === 'amount') val = `${this.formatNumber(item.amount)} ${this.main.currency}`;
          else if (key === 'date_range_type') val = this.$t(item.date_range_type);
          else if (key === 'date_start') val = item.date_start ? this.formatDate(item.date_start) : '—';
          else if (key === 'date_end') {
            val = item.date_end
              ? this.formatDate(item.date_end)
              : (item.date_range_type === 'ongoing' ? this.$t('ongoing') : (item.date_range_type === 'all_time' ? this.$t('all_time') : '—'));
          } else if (key === 'is_active') {
            val = item.is_active ? this.$t('active') : this.$t('inactive');
          } else {
            val = String(item[key] || '');
          }

          if (Array.isArray(filterValues)) {
            if (!filterValues.includes(val)) return false;
          } else if (typeof filterValues === 'string') {
            if (!val.toLowerCase().includes(filterValues.toLowerCase())) return false;
          }
        }
        return true;
      });
    },
    dateRangeTypeOptions() {
      return [
        { title: this.$t('all_time'), value: 'all_time' },
        { title: this.$t('date_range'), value: 'date_range' },
        { title: this.$t('ongoing'), value: 'ongoing' },
      ];
    },
    canSave() {
      const f = this.dialog.form;
      if (!f.name_en && !f.name_fr && !f.name_es) return false;
      if (f.amount == null || f.amount < 0) return false;
      if (f.date_range_type === 'date_range' && (!this.dialog.dateRange.start || !this.dialog.dateRange.end)) return false;
      if (f.date_range_type === 'ongoing' && !this.dialog.dateRange.start) return false;
      return true;
    },
  },
  methods: {
    getLocalizedName(charge) {
      return charge[`name_${this.lang}`] || charge.name_fr || charge.name_en || charge.name_es || `#${charge.id}`;
    },
    formatNumber(n) {
      if (n == null || isNaN(n)) return '0';
      return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(d) {
      if (!d) return '-';
      return this.main.formateDate(d);
    },
    formatDateForApi(d) {
      if (!d) return null;
      const dt = d instanceof Date ? d : new Date(d);
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const day = String(dt.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    },
    getColumnOptions(key) {
      const set = new Set();
      this.sortedCharges.forEach(item => {
        let v = '';
        if (key === 'name') v = this.getLocalizedName(item);
        else if (key === 'amount') v = `${this.formatNumber(item.amount)} ${this.main.currency}`;
        else if (key === 'date_range_type') v = this.$t(item.date_range_type);
        else if (key === 'date_start') v = item.date_start ? this.formatDate(item.date_start) : '—';
        else if (key === 'date_end') {
          v = item.date_end
            ? this.formatDate(item.date_end)
            : (item.date_range_type === 'ongoing' ? this.$t('ongoing') : (item.date_range_type === 'all_time' ? this.$t('all_time') : '—'));
        } else if (key === 'is_active') {
          v = item.is_active ? this.$t('active') : this.$t('inactive');
        } else {
          v = item[key];
        }
        if (v !== undefined && v !== null && v !== '') set.add(String(v));
      });
      return Array.from(set).sort();
    },
    clearAllColumnFilters() {
      this.columnFilters = {};
    },
    dateRangeChipColor(type) {
      if (type === 'all_time') return 'info';
      if (type === 'ongoing') return 'success';
      return 'warning';
    },
    dateRangeIcon(type) {
      if (type === 'all_time') return 'mdi-infinity';
      if (type === 'ongoing') return 'mdi-calendar-arrow-right';
      return 'mdi-calendar-range';
    },
    openAddDialog() {
      this.dialog.editId = null;
      this.dialog.dateRange = { start: new Date(), end: new Date() };
      this.dialog.form = {
        name_en: '',
        name_fr: '',
        name_es: '',
        amount: 0,
        date_range_type: 'all_time',
        description: '',
        is_active: true,
      };
      this.dialog.show = true;
    },
    openEditDialog(s) {
      this.dialog.editId = s.id;
      this.dialog.form = {
        name_en: s.name_en || '',
        name_fr: s.name_fr || '',
        name_es: s.name_es || '',
        amount: parseFloat(s.amount) || 0,
        date_range_type: s.date_range_type || 'all_time',
        description: s.description || '',
        is_active: s.is_active ?? true,
      };
      this.dialog.dateRange = {
        start: s.date_start ? new Date(s.date_start) : new Date(),
        end: s.date_end ? new Date(s.date_end) : new Date(),
      };
      this.dialog.show = true;
    },
    async saveDialog() {
      this.dialog.saving = true;
      try {
        const payload = {
          name_en: this.dialog.form.name_en,
          name_fr: this.dialog.form.name_fr,
          name_es: this.dialog.form.name_es,
          amount: this.dialog.form.amount,
          date_range_type: this.dialog.form.date_range_type,
          date_start: this.dialog.form.date_range_type !== 'all_time' ? this.formatDateForApi(this.dialog.dateRange.start) : null,
          date_end: this.dialog.form.date_range_type === 'date_range' ? this.formatDateForApi(this.dialog.dateRange.end) : null,
          description: this.dialog.form.description,
          is_active: this.dialog.form.is_active,
        };

        const snapshot = {
          name: this.getLocalizedName(payload),
          amount: this.formatNumber(payload.amount),
          dateType: this.$t(payload.date_range_type),
        };

        if (this.dialog.editId) {
          await this.store.update(this.dialog.editId, payload);
          this.showSnackbar(this.$t('static_charge_updated'), true, snapshot);
        } else {
          await this.store.store(payload);
          this.showSnackbar(this.$t('static_charge_created'), true, snapshot);
        }
        this.dialog.show = false;
      } catch (e) {
        this.showSnackbar(this.$t('error'), false, null);
      }
      this.dialog.saving = false;
    },
    confirmDelete(s) {
      this.deleteDialog.charge = s;
      this.deleteDialog.show = true;
    },
    async doDelete() {
      if (!this.deleteDialog.charge) return;
      this.deleteDialog.deleting = true;
      try {
        const snapshot = {
          name: this.getLocalizedName(this.deleteDialog.charge),
          amount: this.formatNumber(this.deleteDialog.charge.amount),
          dateType: this.$t(this.deleteDialog.charge.date_range_type),
        };
        await this.store.destroy(this.deleteDialog.charge.id);
        this.deleteDialog.show = false;
        this.showSnackbar(this.$t('static_charge_deleted'), true, snapshot);
      } catch (e) {
        this.showSnackbar(this.$t('error'), false, null);
      }
      this.deleteDialog.deleting = false;
    },
    showSnackbar(text, success, detail) {
      clearInterval(this.snackbarTimer);
      this.snackbar = { show: true, text, success, detail };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 1.25;
        if (this.snackbarProgress <= 0) { clearInterval(this.snackbarTimer); }
      }, 100);
    },
  },
  async mounted() {
    if (!this.store.list.length) {
      await this.store.fetch();
    }
  },
};
</script>

<style scoped>
.fm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.fm-hero { background: linear-gradient(135deg, #0097A7 0%, #00BCD4 40%, #4DD0E1 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.fm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.fm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.fm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.fm-toolbar-card { background: rgba(var(--v-theme-surface), 0.7); backdrop-filter: blur(8px); }
.param-table { border: 1px solid rgba(0,188,212,.15); }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; cursor: pointer; }
.param-filter-row th { padding: 4px 6px 8px !important; }
.param-filter-input :deep(.v-field) { border-radius: 8px !important; font-size: 11px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 28px !important; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
.sc-info-item {
  display: flex;
  align-items: flex-start;
}
</style>
