<template>
  <v-card class="pm-card" elevation="0">
    <!-- Hero Header -->
    <div class="pm-hero">
      <div class="pm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="pm-hero-icon">
            <v-icon size="28" color="white">mdi-package-variant-closed</v-icon>
          </div>
          <div>
            <div class="pm-hero-title">{{ $t('package_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_package_types') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <div class="d-flex align-center ga-3 mb-3">
        <v-spacer></v-spacer>
        <v-btn v-if="hasActiveFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="columnFilters = {}">{{ $t('clear_filters') }}</v-btn>
        <v-btn color="#546E7A" prepend-icon="mdi-plus" rounded @click="openDialog(null)">{{ $t('add_package_type') }}</v-btn>
      </div>

      <v-data-table :headers="headers" :items="filteredItems" :loading="loading ? '#546E7A' : false" density="comfortable" class="rounded-lg param-table" hover>
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
          <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="columnFilters[col.key]" :items="getColumnOptions(col.key)" density="compact" variant="outlined" color="#546E7A" class="param-filter-input" style="color:#546E7A" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#546E7A" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="#546E7A" @click="openSecondaryDialog(item)" class="mr-1" :title="$t('secondary_packaging_config')">
            <v-icon size="16">mdi-layers-outline</v-icon>
          </v-btn>
          <v-btn icon size="x-small" variant="tonal" color="#546E7A" @click="openDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Edit/Add Package Dialog -->
    <v-dialog v-model="dialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#546E7A" class="mr-2">mdi-package-variant-closed</v-icon>
          {{ editing ? $t('edit_package_type') : $t('add_package_type') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="6"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" variant="outlined" density="compact" rounded color="#546E7A" class="mb-3"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_en" :label="$t('name_en')" variant="outlined" density="compact" rounded color="#546E7A" class="mb-3"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_es" :label="$t('name_es')" variant="outlined" density="compact" rounded color="#546E7A"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_in_invoice" :label="$t('name_in_invoice')" variant="outlined" density="compact" rounded color="#546E7A"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.size" :label="$t('size')" variant="outlined" density="compact" rounded color="#546E7A"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.type_emballage" :label="$t('packaging_type')" variant="outlined" density="compact" rounded color="#546E7A"></v-text-field></v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" variant="outlined" density="compact" rounded color="#546E7A" rows="2"></v-textarea></v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="#546E7A" variant="flat" rounded @click="save" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Secondary Packaging Configuration Dialog with DatePicker (WAP-like UX) -->
    <v-dialog v-model="secondaryDialog" max-width="900" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#546E7A" class="mr-2">mdi-layers-outline</v-icon>
          <span class="text-subtitle-1 font-weight-bold">
            {{ $t('secondary_packaging_config') }} - {{ selectedPackage ? (selectedPackage['name_' + main.lang.short] || selectedPackage.name_fr || selectedPackage.name_en) : '' }}
          </span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="secondaryDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-5">
          <!-- Rule Entry Form -->
          <v-card variant="outlined" class="rounded-lg pa-4 mb-4" style="border-color: rgba(84, 110, 122, 0.3); background: rgba(84, 110, 122, 0.02)">
            <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center text-blue-grey-darken-2">
              <v-icon size="18" color="#546E7A" class="mr-1">mdi-plus-circle-outline</v-icon>
              {{ editingSecondaryRule ? $t('edit_secondary_rule') : $t('add_secondary_rule') }}
            </div>

            <!-- Date Range Selector using DatePicker -->
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="text-caption font-weight-bold text-blue-grey">{{ $t('secondary_packaging_config') }}</div>
              <v-checkbox
                v-model="secondaryForm.is_range"
                :label="$t('date_range')"
                density="compact"
                hide-details
                color="#546E7A"
              ></v-checkbox>
            </div>

            <DatePicker
              v-if="secondaryForm.is_range"
              v-model.range="secondaryDateRangeVC"
              expanded
              :is-dark="main.is_dark"
              mode="date"
              color="gray"
              class="rounded-lg mb-2"
            />
            <DatePicker
              v-else
              v-model="secondarySingleDateVC"
              expanded
              :is-dark="main.is_dark"
              mode="date"
              color="gray"
              class="rounded-lg mb-2"
            />

            <div class="d-flex align-center ga-4 text-caption mb-3">
              <div><b>{{ $t('start_date') }}:</b> <span class="text-blue-grey-darken-2 font-weight-bold">{{ secondaryStartDateDisplay }}</span></div>
              <div><b>{{ $t('end_date') }}:</b> <span class="text-blue-grey-darken-2 font-weight-bold">{{ secondaryEndDateDisplay }}</span></div>
            </div>

            <v-divider class="my-3"></v-divider>

            <!-- 6 Components Fields Grid (qte & price for each) -->
            <div class="text-caption font-weight-bold text-blue-grey mb-2">{{ $t('secondary_packaging') }} Components (Quantity & Price per package)</div>
            <v-row dense>
              <!-- Caisses -->
              <v-col cols="6" sm="4">
                <v-card variant="tonal" color="#546E7A" class="pa-2 rounded-lg mb-2">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('caisses') }}</div>
                  <v-row dense>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.caisses_qte" type="number" step="0.001" min="0" :label="$t('caisses_qte')" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.caisses_price" type="number" step="0.001" min="0" :label="$t('caisses_price') + ' (DH)'" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                  </v-row>
                </v-card>
              </v-col>

              <!-- Cellophane -->
              <v-col cols="6" sm="4">
                <v-card variant="tonal" color="#546E7A" class="pa-2 rounded-lg mb-2">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('cellophane') }}</div>
                  <v-row dense>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.cellophane_qte" type="number" step="0.001" min="0" :label="$t('cellophane_qte')" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.cellophane_price" type="number" step="0.001" min="0" :label="$t('cellophane_price') + ' (DH)'" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                  </v-row>
                </v-card>
              </v-col>

              <!-- Plastique -->
              <v-col cols="6" sm="4">
                <v-card variant="tonal" color="#546E7A" class="pa-2 rounded-lg mb-2">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('plastique') }}</div>
                  <v-row dense>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.plastique_qte" type="number" step="0.001" min="0" :label="$t('plastique_qte')" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.plastique_price" type="number" step="0.001" min="0" :label="$t('plastique_price') + ' (DH)'" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                  </v-row>
                </v-card>
              </v-col>

              <!-- Strapex -->
              <v-col cols="6" sm="4">
                <v-card variant="tonal" color="#546E7A" class="pa-2 rounded-lg mb-2">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('strapex') }}</div>
                  <v-row dense>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.strapex_qte" type="number" step="0.001" min="0" :label="$t('strapex_qte')" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.strapex_price" type="number" step="0.001" min="0" :label="$t('strapex_price') + ' (DH)'" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                  </v-row>
                </v-card>
              </v-col>

              <!-- Palletes -->
              <v-col cols="6" sm="4">
                <v-card variant="tonal" color="#546E7A" class="pa-2 rounded-lg mb-2">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('palletes') }}</div>
                  <v-row dense>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.palletes_qte" type="number" step="0.001" min="0" :label="$t('palletes_qte')" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.palletes_price" type="number" step="0.001" min="0" :label="$t('palletes_price') + ' (DH)'" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                  </v-row>
                </v-card>
              </v-col>

              <!-- Scotch -->
              <v-col cols="6" sm="4">
                <v-card variant="tonal" color="#546E7A" class="pa-2 rounded-lg mb-2">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('scotch') }}</div>
                  <v-row dense>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.scotch_qte" type="number" step="0.001" min="0" :label="$t('scotch_qte')" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                    <v-col cols="6"><v-text-field v-model.number="secondaryForm.scotch_price" type="number" step="0.001" min="0" :label="$t('scotch_price') + ' (DH)'" variant="outlined" density="compact" rounded color="#546E7A" hide-details></v-text-field></v-col>
                  </v-row>
                </v-card>
              </v-col>
            </v-row>

            <!-- Unit Cost Preview & Save -->
            <div class="d-flex align-center justify-space-between mt-3 pt-2" style="border-top: 1px dashed rgba(84, 110, 122, 0.3)">
              <div>
                <span class="text-caption text-grey">{{ $t('unit_cost_per_package') }}: </span>
                <b class="text-blue-grey-darken-3 text-subtitle-1">{{ calculatedUnitCost }} DH</b>
              </div>
              <div>
                <v-btn v-if="editingSecondaryRule" variant="text" size="small" @click="resetSecondaryForm" class="mr-2">{{ $t('cancel') }}</v-btn>
                <v-btn color="#546E7A" variant="flat" rounded :loading="savingSecondaryRule" @click="saveSecondaryRule" prepend-icon="mdi-check">
                  {{ editingSecondaryRule ? $t('update') : $t('save') }}
                </v-btn>
              </div>
            </div>
          </v-card>

          <!-- Existing Secondary Packaging Rules Table -->
          <div class="text-subtitle-2 font-weight-bold mb-2">{{ $t('secondary_packaging_config') }}</div>
          <v-data-table
            :headers="secondaryHeaders"
            :items="secondaryRulesList"
            :loading="loadingSecondaryRules ? '#546E7A' : false"
            density="compact"
            class="rounded-lg param-table"
            hover
          >
            <template v-slot:[`item.date_start`]="{ item }">
              <span>{{ main.formateDate(item.date_start) }}</span>
            </template>
            <template v-slot:[`item.date_end`]="{ item }">
              <v-chip size="x-small" :color="item.date_end ? '#546E7A' : 'warning'" variant="tonal" class="font-weight-bold">
                {{ item.date_end ? main.formateDate(item.date_end) : $t('open_ended') }}
              </v-chip>
            </template>
            <template v-slot:[`item.breakdown`]="{ item }">
              <div class="text-caption ga-1 d-flex flex-wrap">
                <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.caisses_qte > 0">Caisses: {{ item.caisses_qte }}x{{ item.caisses_price }}</v-chip>
                <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.cellophane_qte > 0">Cello: {{ item.cellophane_qte }}x{{ item.cellophane_price }}</v-chip>
                <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.plastique_qte > 0">Plast: {{ item.plastique_qte }}x{{ item.plastique_price }}</v-chip>
                <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.strapex_qte > 0">Strap: {{ item.strapex_qte }}x{{ item.strapex_price }}</v-chip>
                <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.palletes_qte > 0">Pallet: {{ item.palletes_qte }}x{{ item.palletes_price }}</v-chip>
                <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.scotch_qte > 0">Scotch: {{ item.scotch_qte }}x{{ item.scotch_price }}</v-chip>
              </div>
            </template>
            <template v-slot:[`item.unit_cost`]="{ item }">
              <b class="text-blue-grey-darken-2">{{ main.formatDisplayNumber(item.unit_cost) }} DH</b>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="#546E7A" @click="editSecondaryRule(item)" class="mr-1"><v-icon size="14">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteSecondaryRule(item)"><v-icon size="14">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="secondaryDialog = false" variant="text">{{ $t('close') }}</v-btn>
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
  name: 'PackageManagement',
  data() {
    return {
      items: [], loading: false,
      dialog: false, editing: null, saving: false,
      form: { name_fr: '', name_en: '', name_es: '', name_in_invoice: '', size: '', description: '', type_emballage: '' },
      columnFilters: {},
      deleteDialog: false, deleteTarget: null,
      snackbar: { show: false, message: '', color: 'success' },

      // Secondary Packaging Configuration State with DatePicker
      secondaryDialog: false,
      selectedPackage: null,
      loadingSecondaryRules: false,
      secondaryRulesList: [],
      editingSecondaryRule: null,
      savingSecondaryRule: false,
      secondarySingleDateVC: new Date(),
      secondaryDateRangeVC: { start: new Date(), end: new Date() },
      secondaryForm: {
        is_range: false,
        caisses_qte: 0, caisses_price: 0,
        cellophane_qte: 0, cellophane_price: 0,
        plastique_qte: 0, plastique_price: 0,
        strapex_qte: 0, strapex_price: 0,
        palletes_qte: 0, palletes_price: 0,
        scotch_qte: 0, scotch_price: 0,
      },
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
        { title: this.$t('size'), key: 'size', align: 'center' },
        { title: this.$t('packaging_type'), key: 'type_emballage', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    secondaryHeaders() {
      return [
        { title: this.$t('start_date'), key: 'date_start', align: 'center' },
        { title: this.$t('end_date'), key: 'date_end', align: 'center' },
        { title: this.$t('secondary_packaging'), key: 'breakdown', align: 'center' },
        { title: this.$t('unit_cost_per_package'), key: 'unit_cost', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    secondaryStartDateDisplay() {
      if (this.secondaryForm.is_range) {
        if (!this.secondaryDateRangeVC) return '-';
        const d = this.secondaryDateRangeVC.start || this.secondaryDateRangeVC;
        return d ? (typeof d === 'string' ? d.substring(0, 10) : new Date(d).toISOString().substring(0, 10)) : '-';
      } else {
        if (!this.secondarySingleDateVC) return '-';
        const d = this.secondarySingleDateVC;
        return d ? (typeof d === 'string' ? d.substring(0, 10) : new Date(d).toISOString().substring(0, 10)) : '-';
      }
    },
    secondaryEndDateDisplay() {
      if (!this.secondaryForm.is_range) return this.$t('open_ended');
      if (!this.secondaryDateRangeVC || !this.secondaryDateRangeVC.end) return '-';
      const d = this.secondaryDateRangeVC.end;
      return d ? (typeof d === 'string' ? d.substring(0, 10) : new Date(d).toISOString().substring(0, 10)) : '-';
    },
    calculatedUnitCost() {
      const f = this.secondaryForm;
      const total = (Number(f.caisses_qte || 0) * Number(f.caisses_price || 0))
        + (Number(f.cellophane_qte || 0) * Number(f.cellophane_price || 0))
        + (Number(f.plastique_qte || 0) * Number(f.plastique_price || 0))
        + (Number(f.strapex_qte || 0) * Number(f.strapex_price || 0))
        + (Number(f.palletes_qte || 0) * Number(f.palletes_price || 0))
        + (Number(f.scotch_qte || 0) * Number(f.scotch_price || 0));
      return Math.round(total * 10000) / 10000;
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
    _onMC(d) {
      if (d.model === 'PackageType') this.fetchItems();
      if (d.model === 'PackageSecondaryConfig' && this.selectedPackage) this.fetchSecondaryRules();
    },
    getColumnOptions(key) { const set = new Set(); this.items.forEach(i => set.add(String(i[key] ?? ''))); return [...set].sort(); },
    async fetchItems() {
      this.loading = true;
      try { const r = await axios.get('/api/package-types'); this.items = r.data.package_types || []; } catch (e) { console.error(e); }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      this.form = item
        ? { name_fr: item.name_fr, name_en: item.name_en || '', name_es: item.name_es || '', name_in_invoice: item.name_in_invoice || '', size: item.size || '', description: item.description || '', type_emballage: item.type_emballage || '' }
        : { name_fr: '', name_en: '', name_es: '', name_in_invoice: '', size: '', description: '', type_emballage: '' };
      this.dialog = true;
    },
    async save() {
      this.saving = true;
      try {
        if (this.editing) {
          await axios.put(`/api/package-types/${this.editing.id}`, this.form);
          this.snackbar = { show: true, message: this.$t('package_type_updated'), color: 'success' };
        } else {
          await axios.post('/api/package-types', this.form);
          this.snackbar = { show: true, message: this.$t('package_type_created'), color: 'success' };
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
        await axios.delete(`/api/package-types/${this.deleteTarget.id}`);
        this.snackbar = { show: true, message: this.$t('package_type_deleted'), color: 'success' };
        this.fetchItems();
      } catch (e) { console.error(e); }
      this.deleteTarget = null;
    },

    // Secondary Packaging Configuration Methods
    openSecondaryDialog(pkg) {
      this.selectedPackage = pkg;
      this.resetSecondaryForm();
      this.fetchSecondaryRules();
      this.secondaryDialog = true;
    },
    resetSecondaryForm() {
      this.editingSecondaryRule = null;
      this.secondarySingleDateVC = new Date();
      this.secondaryDateRangeVC = { start: new Date(), end: new Date() };
      this.secondaryForm = {
        is_range: false,
        caisses_qte: 0, caisses_price: 0,
        cellophane_qte: 0, cellophane_price: 0,
        plastique_qte: 0, plastique_price: 0,
        strapex_qte: 0, strapex_price: 0,
        palletes_qte: 0, palletes_price: 0,
        scotch_qte: 0, scotch_price: 0,
      };
    },
    async fetchSecondaryRules() {
      if (!this.selectedPackage) return;
      this.loadingSecondaryRules = true;
      try {
        const res = await axios.get('/api/package-secondary-configs', { params: { package_type_id: this.selectedPackage.id } });
        this.secondaryRulesList = res.data.package_secondary_configs || [];
      } catch (e) { console.error(e); }
      this.loadingSecondaryRules = false;
    },
    editSecondaryRule(rule) {
      this.editingSecondaryRule = rule;
      const s = rule.date_start ? new Date(rule.date_start) : new Date();
      const e = rule.date_end ? new Date(rule.date_end) : null;
      this.secondaryForm.is_range = !!rule.date_end;
      if (rule.date_end) {
        this.secondaryDateRangeVC = { start: s, end: e };
      } else {
        this.secondarySingleDateVC = s;
      }
      this.secondaryForm = {
        is_range: !!rule.date_end,
        caisses_qte: Number(rule.caisses_qte || 0), caisses_price: Number(rule.caisses_price || 0),
        cellophane_qte: Number(rule.cellophane_qte || 0), cellophane_price: Number(rule.cellophane_price || 0),
        plastique_qte: Number(rule.plastique_qte || 0), plastique_price: Number(rule.plastique_price || 0),
        strapex_qte: Number(rule.strapex_qte || 0), strapex_price: Number(rule.strapex_price || 0),
        palletes_qte: Number(rule.palletes_qte || 0), palletes_price: Number(rule.palletes_price || 0),
        scotch_qte: Number(rule.scotch_qte || 0), scotch_price: Number(rule.scotch_price || 0),
      };
    },
    async saveSecondaryRule() {
      if (!this.selectedPackage) return;
      this.savingSecondaryRule = true;
      try {
        const startDate = this.secondaryStartDateDisplay;
        const endDate = this.secondaryForm.is_range ? this.secondaryEndDateDisplay : null;

        const payload = {
          package_type_id: this.selectedPackage.id,
          date_start: startDate,
          date_end: endDate,
          caisses_qte: this.secondaryForm.caisses_qte, caisses_price: this.secondaryForm.caisses_price,
          cellophane_qte: this.secondaryForm.cellophane_qte, cellophane_price: this.secondaryForm.cellophane_price,
          plastique_qte: this.secondaryForm.plastique_qte, plastique_price: this.secondaryForm.plastique_price,
          strapex_qte: this.secondaryForm.strapex_qte, strapex_price: this.secondaryForm.strapex_price,
          palletes_qte: this.secondaryForm.palletes_qte, palletes_price: this.secondaryForm.palletes_price,
          scotch_qte: this.secondaryForm.scotch_qte, scotch_price: this.secondaryForm.scotch_price,
        };

        if (this.editingSecondaryRule) {
          await axios.put(`/api/package-secondary-configs/${this.editingSecondaryRule.id}`, payload);
          this.snackbar = { show: true, message: this.$t('updated'), color: 'success' };
        } else {
          await axios.post('/api/package-secondary-configs', payload);
          this.snackbar = { show: true, message: this.$t('created'), color: 'success' };
        }
        this.resetSecondaryForm();
        await this.fetchSecondaryRules();
      } catch (e) {
        console.error(e);
        this.snackbar = { show: true, message: e.response?.data?.message || 'Error saving secondary rule', color: 'error' };
      }
      this.savingSecondaryRule = false;
    },
    async deleteSecondaryRule(rule) {
      try {
        await axios.delete(`/api/package-secondary-configs/${rule.id}`);
        this.snackbar = { show: true, message: this.$t('deleted'), color: 'success' };
        await this.fetchSecondaryRules();
      } catch (e) { console.error(e); }
    },
  },
};
</script>

<style scoped>
.pm-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.pm-hero { background: linear-gradient(135deg, #37474F 0%, #546E7A 40%, #78909C 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.pm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.pm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.pm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
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
