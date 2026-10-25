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

      <v-data-table :headers="headers" :items="filteredItems" :loading="loading ? '#546E7A' : false" :multi-sort="true" density="comfortable" class="rounded-lg param-table" hover>
        <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
          <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
          <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="columnFilters[col.key]" :items="getColumnOptions(col.key)" density="compact" variant="outlined" color="#546E7A" class="param-filter-input" style="color:#546E7A" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#546E7A" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
        </template>
        <template v-slot:[`item.components`]="{ item }">
          <div v-if="item.components && item.components.length" class="d-flex flex-wrap ga-1 justify-center">
            <v-chip
              v-for="comp in item.components"
              :key="comp.id"
              size="x-small"
              color="#546E7A"
              variant="tonal"
              class="font-weight-bold"
            >
              {{ comp.target_package_type ? (comp.target_package_type['name_' + main.lang.short] || comp.target_package_type.name_fr) : `#${comp.target_package_type_id}` }}
              <span class="fnm-currency-num ms-1">({{ Number(comp.multiplier) }}x)</span>
            </v-chip>
          </div>
          <span v-else class="text-grey text-caption">-</span>
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
    <v-dialog v-model="dialog" max-width="650" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#546E7A" class="mr-2">mdi-package-variant-closed</v-icon>
          {{ editing ? $t('edit_package_type') : $t('add_package_type') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="6"><v-text-field v-model="form.name_fr" :label="$t('name_fr')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded color="#546E7A" class="mb-3"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_en" :label="$t('name_en')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded color="#546E7A" class="mb-3"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_es" :label="$t('name_es')" prepend-inner-icon="mdi-translate" variant="outlined" density="compact" rounded color="#546E7A"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.name_in_invoice" :label="$t('name_in_invoice')" prepend-inner-icon="mdi-receipt-text-outline" variant="outlined" density="compact" rounded color="#546E7A"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.size" :label="$t('size')" prepend-inner-icon="mdi-ruler-square" variant="outlined" density="compact" rounded color="#546E7A"></v-text-field></v-col>
            <v-col cols="6"><v-text-field v-model="form.type_emballage" :label="$t('packaging_type')" prepend-inner-icon="mdi-package-variant" variant="outlined" density="compact" rounded color="#546E7A"></v-text-field></v-col>
            <v-col cols="12"><v-textarea v-model="form.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded color="#546E7A" rows="2"></v-textarea></v-col>
          </v-row>

          <v-divider class="my-3"></v-divider>

          <!-- Linked Package Components Section -->
          <div class="d-flex align-center justify-space-between mb-2">
            <div class="text-subtitle-2 font-weight-bold text-blue-grey d-flex align-center">
              <v-icon size="18" color="#546E7A" class="mr-1">mdi-link-variant</v-icon>
              {{ $t('linked_packages') }}
            </div>
            <v-btn size="small" color="#546E7A" variant="tonal" rounded prepend-icon="mdi-plus" @click="addLinkedPackageComponent">
              {{ $t('add_linked_package') }}
            </v-btn>
          </div>

          <div v-if="form.components && form.components.length">
            <v-card v-for="(comp, cIdx) in form.components" :key="cIdx" variant="outlined" color="#546E7A" class="pa-3 rounded-lg mb-2">
              <div class="d-flex align-center ga-2">
                <v-autocomplete
                  v-model="comp.target_package_type_id"
                  :items="targetPackageOptions"
                  item-title="title"
                  item-value="value"
                  :label="$t('target_package_type')"
                  prepend-inner-icon="mdi-package-variant-closed"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="#546E7A"
                  hide-details
                  class="flex-grow-1"
                ></v-autocomplete>

                <v-text-field
                  v-model.number="comp.multiplier"
                  type="number"
                  step="0.0001"
                  min="0.0001"
                  :label="$t('multiplier')"
                  prepend-inner-icon="mdi-close"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="#546E7A"
                  hide-details
                  style="max-width: 140px"
                ></v-text-field>

                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="removeLinkedPackageComponent(cIdx)"
                ></v-btn>
              </div>
            </v-card>
          </div>
          <div v-else class="text-caption text-grey text-center pa-3" style="border: 1px dashed rgba(84, 110, 122, 0.3); border-radius: 8px">
            {{ $t('no_data') }} — {{ $t('add_linked_package') }}
          </div>
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

            <!-- Components Fields Grid (Measure, Quantity & Price per package) -->
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="text-caption font-weight-bold text-blue-grey">{{ $t('secondary_packaging') }} Components (Measure, Quantity & Price per package)</div>
              <v-btn size="small" color="#546E7A" variant="tonal" rounded prepend-icon="mdi-plus" @click="addSecondaryComponent">
                {{ $t('add_component') }}
              </v-btn>
            </div>

            <v-row dense>
              <v-col v-for="(comp, cIdx) in secondaryForm.components" :key="comp.key || cIdx" cols="12" sm="6">
                <v-card variant="tonal" color="#546E7A" class="pa-2 rounded-lg mb-2">
                  <div class="d-flex align-center justify-space-between mb-1">
                    <div class="text-caption font-weight-bold text-truncate flex-grow-1 mr-1">
                      <v-text-field
                        v-if="comp.is_custom"
                        v-model="comp.name"
                        :placeholder="$t('component_name')"
                        variant="underlined"
                        density="compact"
                        hide-details
                        color="#546E7A"
                        class="text-caption font-weight-bold pa-0 ma-0"
                      ></v-text-field>
                      <span v-else>{{ comp.name }}</span>
                    </div>
                    <v-btn
                      v-if="comp.is_custom"
                      icon="mdi-close-circle"
                      size="x-small"
                      variant="text"
                      color="error"
                      @click="removeSecondaryComponent(cIdx)"
                      :title="$t('remove_component')"
                    ></v-btn>
                  </div>
                  <v-row dense>
                    <v-col cols="4">
                      <v-combobox
                        v-model="comp.unit"
                        :items="['U', 'Kg', 'g', 'm', 'Rouleau', 'Pièce', 'Carton', 'L']"
                        :label="$t('measure')"
                        prepend-inner-icon="mdi-ruler"
                        variant="outlined"
                        density="compact"
                        rounded
                        color="#546E7A"
                        hide-details
                      ></v-combobox>
                    </v-col>
                    <v-col cols="4">
                      <v-text-field
                        v-model.number="comp.qte"
                        type="number"
                        step="0.001"
                        min="0"
                        :label="$t('qte')"
                        prepend-inner-icon="mdi-numeric"
                        variant="outlined"
                        density="compact"
                        rounded
                        color="#546E7A"
                        hide-details
                      ></v-text-field>
                    </v-col>
                    <v-col cols="4">
                      <v-text-field
                        v-model.number="comp.price"
                        type="number"
                        step="0.001"
                        min="0"
                        :label="$t('price') + ' (DH)'"
                        prepend-inner-icon="mdi-cash"
                        variant="outlined"
                        density="compact"
                        rounded
                        color="#546E7A"
                        hide-details
                      ></v-text-field>
                    </v-col>
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
            :multi-sort="true"
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
                <template v-if="item.resolved_components && item.resolved_components.length">
                  <v-chip
                    v-for="(comp, cIdx) in item.resolved_components.filter(c => c.qte > 0)"
                    :key="cIdx"
                    size="x-small"
                    variant="tonal"
                    color="blue-grey"
                  >
                    {{ comp.name }} ({{ comp.unit || 'U' }}): {{ comp.qte }}x{{ comp.price }}
                  </v-chip>
                </template>
                <template v-else>
                  <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.caisses_qte > 0">Caisses (U): {{ item.caisses_qte }}x{{ item.caisses_price }}</v-chip>
                  <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.cellophane_qte > 0">Cello (Kg): {{ item.cellophane_qte }}x{{ item.cellophane_price }}</v-chip>
                  <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.plastique_qte > 0">Plast (Kg): {{ item.plastique_qte }}x{{ item.plastique_price }}</v-chip>
                  <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.strapex_qte > 0">Strap (m): {{ item.strapex_qte }}x{{ item.strapex_price }}</v-chip>
                  <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.palletes_qte > 0">Pallet (U): {{ item.palletes_qte }}x{{ item.palletes_price }}</v-chip>
                  <v-chip size="x-small" variant="tonal" color="blue-grey" v-if="item.scotch_qte > 0">Scotch (Rouleau): {{ item.scotch_qte }}x{{ item.scotch_price }}</v-chip>
                </template>
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
      <v-progress-linear :model-value="snackbarProgress" color="#546E7A" height="3" class="mb-2" rounded></v-progress-linear>
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
  name: 'PackageManagement',
  data() {
    return {
      items: [], loading: false,
      dialog: false, editing: null, saving: false,
      form: { name_fr: '', name_en: '', name_es: '', name_in_invoice: '', size: '', description: '', type_emballage: '', components: [] },
      columnFilters: {},
      deleteDialog: false, deleteTarget: null,
      summarySnackbar: { show: false, title: '', detail: '', color: 'success' },
      snackbarProgress: 100,
      snackbarTimer: null,

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
        components: [],
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
        { title: this.$t('linked_packages'), key: 'components', align: 'center', sortable: false },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    targetPackageOptions() {
      const currentId = this.editing ? this.editing.id : null;
      return this.items
        .filter(i => i.id !== currentId)
        .map(i => ({
          title: i['name_' + this.main.lang.short] || i.name_fr || i.name_en || `#${i.id}`,
          value: i.id
        }));
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
      if (!this.secondaryForm.components) return 0;
      const total = this.secondaryForm.components.reduce((sum, c) => {
        return sum + (Number(c.qte || 0) * Number(c.price || 0));
      }, 0);
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
  beforeUnmount() {
    mitt.off('model-changed', this._onMC);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  methods: {
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
    _onMC(d) {
      if (d.model === 'PackageType') {
        this.fetchItems();
        if (d.action && d.user_id !== this.main.user?.id) {
          const actionText = d.action === 'created' ? this.$t('created') : d.action === 'updated' ? this.$t('updated') : this.$t('deleted');
          this.triggerSummarySnackbar(
            `${this.$t('package_type')}: ${actionText}`,
            d.data?.name_fr || d.data?.name_en || '',
            'info'
          );
        }
      }
      if (d.model === 'PackageSecondaryConfig' && this.selectedPackage) {
        this.fetchSecondaryRules();
        if (d.action && d.user_id !== this.main.user?.id) {
          const actionText = d.action === 'created' ? this.$t('created') : d.action === 'updated' ? this.$t('updated') : this.$t('deleted');
          this.triggerSummarySnackbar(
            `${this.$t('secondary_packaging_config')}: ${actionText}`,
            '',
            'info'
          );
        }
      }
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
        ? {
            name_fr: item.name_fr,
            name_en: item.name_en || '',
            name_es: item.name_es || '',
            name_in_invoice: item.name_in_invoice || '',
            size: item.size || '',
            description: item.description || '',
            type_emballage: item.type_emballage || '',
            components: (item.components || []).map(c => ({
              target_package_type_id: c.target_package_type_id,
              multiplier: Number(c.multiplier ?? 1.0)
            }))
          }
        : {
            name_fr: '',
            name_en: '',
            name_es: '',
            name_in_invoice: '',
            size: '',
            description: '',
            type_emballage: '',
            components: []
          };
      this.dialog = true;
    },
    addLinkedPackageComponent() {
      if (!this.form.components) this.form.components = [];
      this.form.components.push({
        target_package_type_id: null,
        multiplier: 1.0000
      });
    },
    removeLinkedPackageComponent(index) {
      if (this.form.components) {
        this.form.components.splice(index, 1);
      }
    },
    async save() {
      this.saving = true;
      try {
        const payload = {
          ...this.form,
          size: this.form.size !== null && this.form.size !== undefined ? String(this.form.size) : '',
        };
        const displayName = this.form.name_fr || this.form.name_en || '';
        if (this.editing) {
          await axios.put(`/api/package-types/${this.editing.id}`, payload);
          this.triggerSummarySnackbar(this.$t('package_type_updated'), displayName, 'success');
        } else {
          await axios.post('/api/package-types', payload);
          this.triggerSummarySnackbar(this.$t('package_type_created'), displayName, 'success');
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
        await axios.delete(`/api/package-types/${this.deleteTarget.id}`);
        this.triggerSummarySnackbar(this.$t('package_type_deleted'), targetName, 'success');
        this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), 'Delete failed', 'error');
      }
      this.deleteTarget = null;
    },

    // Secondary Packaging Configuration Methods
    defaultSecondaryComponents() {
      return [
        { key: 'caisses', name: this.$t('caisses'), unit: 'U', qte: 0, price: 0, is_custom: false },
        { key: 'cellophane', name: this.$t('cellophane'), unit: 'Kg', qte: 0, price: 0, is_custom: false },
        { key: 'plastique', name: this.$t('plastique'), unit: 'Kg', qte: 0, price: 0, is_custom: false },
        { key: 'strapex', name: this.$t('strapex'), unit: 'm', qte: 0, price: 0, is_custom: false },
        { key: 'palletes', name: this.$t('palletes'), unit: 'U', qte: 0, price: 0, is_custom: false },
        { key: 'scotch', name: this.$t('scotch'), unit: 'Rouleau', qte: 0, price: 0, is_custom: false },
      ];
    },
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
        components: this.defaultSecondaryComponents(),
      };
    },
    addSecondaryComponent() {
      if (!this.secondaryForm.components) this.secondaryForm.components = [];
      this.secondaryForm.components.push({
        key: 'comp_' + Date.now(),
        name: '',
        unit: 'U',
        qte: 0,
        price: 0,
        is_custom: true,
      });
    },
    removeSecondaryComponent(idx) {
      if (this.secondaryForm.components) {
        this.secondaryForm.components.splice(idx, 1);
      }
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
      let comps = rule.resolved_components || rule.components;
      if (!comps || !comps.length) {
        comps = [
          { key: 'caisses', name: this.$t('caisses'), unit: 'U', qte: Number(rule.caisses_qte || 0), price: Number(rule.caisses_price || 0), is_custom: false },
          { key: 'cellophane', name: this.$t('cellophane'), unit: 'Kg', qte: Number(rule.cellophane_qte || 0), price: Number(rule.cellophane_price || 0), is_custom: false },
          { key: 'plastique', name: this.$t('plastique'), unit: 'Kg', qte: Number(rule.plastique_qte || 0), price: Number(rule.plastique_price || 0), is_custom: false },
          { key: 'strapex', name: this.$t('strapex'), unit: 'm', qte: Number(rule.strapex_qte || 0), price: Number(rule.strapex_price || 0), is_custom: false },
          { key: 'palletes', name: this.$t('palletes'), unit: 'U', qte: Number(rule.palletes_qte || 0), price: Number(rule.palletes_price || 0), is_custom: false },
          { key: 'scotch', name: this.$t('scotch'), unit: 'Rouleau', qte: Number(rule.scotch_qte || 0), price: Number(rule.scotch_price || 0), is_custom: false },
        ];
      } else {
        comps = comps.map(c => ({
          key: c.key || ('comp_' + Math.random()),
          name: c.name || '',
          unit: c.unit || 'U',
          qte: Number(c.qte || 0),
          price: Number(c.price || 0),
          is_custom: !!c.is_custom,
        }));
      }
      this.secondaryForm.components = comps;
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
          components: this.secondaryForm.components || [],
        };

        if (this.editingSecondaryRule) {
          await axios.put(`/api/package-secondary-configs/${this.editingSecondaryRule.id}`, payload);
          this.triggerSummarySnackbar(this.$t('secondary_packaging_config') + ': ' + this.$t('updated'), startDate, 'success');
        } else {
          await axios.post('/api/package-secondary-configs', payload);
          this.triggerSummarySnackbar(this.$t('secondary_packaging_config') + ': ' + this.$t('created'), startDate, 'success');
        }
        this.resetSecondaryForm();
        await this.fetchSecondaryRules();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), e.response?.data?.message || 'Error saving secondary rule', 'error');
      }
      this.savingSecondaryRule = false;
    },
    async deleteSecondaryRule(rule) {
      try {
        await axios.delete(`/api/package-secondary-configs/${rule.id}`);
        this.triggerSummarySnackbar(this.$t('secondary_packaging_config') + ': ' + this.$t('deleted'), '', 'success');
        await this.fetchSecondaryRules();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), 'Delete failed', 'error');
      }
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
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
</style>
