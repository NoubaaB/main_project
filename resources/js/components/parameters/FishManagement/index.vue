<template>
  <v-card class="fm-card" elevation="0">
    <!-- Hero Header -->
    <div class="fm-hero">
      <div class="fm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="fm-hero-icon">
            <v-icon size="28" color="white">mdi-fish</v-icon>
          </div>
          <div>
            <div class="fm-hero-title">{{ $t('fish_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_fish_families_types') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-tabs v-model="activeTab" color="info" class="mx-4 mt-2" slider-color="info">
      <v-tab value="families"><v-icon start>mdi-family-tree</v-icon>{{ $t('fish_families') }}</v-tab>
      <v-tab value="types"><v-icon start>mdi-fish</v-icon>{{ $t('fish_types') }}</v-tab>
      <v-tab value="process_types"><v-icon start>mdi-cog-outline</v-icon>{{ $t('process_types') }}</v-tab>
    </v-tabs>

    <v-divider></v-divider>

    <v-card-text class="pa-4">
      <v-window v-model="activeTab">
        <!-- ============= FISH FAMILIES TAB ============= -->
        <v-window-item value="families">
          <div class="d-flex align-center ga-3 mb-3">
            <v-spacer></v-spacer>
            <v-btn v-if="hasActiveFamilyFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="familyColumnFilters = {}">{{ $t('clear_filters') }}</v-btn>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openFamilyDialog(null)">{{ $t('add_fish_family') }}</v-btn>
          </div>
          <v-data-table :headers="familyHeaders" :items="filteredFamilies" :loading="loadingFamilies ? 'info' : false" density="comfortable" class="rounded-lg param-table" hover>
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
              <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="familyColumnFilters[col.key]" :items="getFamilyColumnOptions(col.key)" density="compact" variant="outlined" color="info" class="param-filter-input text-info" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="info" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
            </template>
            <template v-slot:[`item.fish_types_count`]="{ item }"><v-chip color="info" size="small" variant="tonal">{{ item.fish_types_count || 0 }}</v-chip></template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openFamilyDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteFamily(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= FISH TYPES TAB ============= -->
        <v-window-item value="types">
          <div class="d-flex align-center ga-3 mb-3">
            <v-spacer></v-spacer>
            <v-btn v-if="hasActiveTypeFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="typeColumnFilters = {}">{{ $t('clear_filters') }}</v-btn>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openTypeDialog(null)">{{ $t('add_fish_type') }}</v-btn>
          </div>
          <v-data-table :headers="typeHeaders" :items="filteredTypes" :loading="loadingTypes ? 'info' : false" density="comfortable" class="rounded-lg param-table" hover>
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
              <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="typeColumnFilters[col.key]" :items="getTypeColumnOptions(col.key)" density="compact" variant="outlined" color="info" class="param-filter-input text-info" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="info" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
            </template>
            <template v-slot:[`item.type`]="{ item }">
              <v-chip v-if="item.type" size="small" variant="tonal" :color="item.type === 'fresh' ? 'teal' : 'blue'">{{ $t(item.type) }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.fish_family`]="{ item }">
              <v-chip v-if="item.fish_family" color="teal" size="small" variant="tonal">{{ item.fish_family['name_' + main.lang.short] || item.fish_family.name_en }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.fish_process_type`]="{ item }">
              <v-chip v-if="item.fish_process_type" size="small" variant="tonal" :color="item.productivity_color || 'info'">{{ item.fish_process_type.name }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.productivity_color`]="{ item }">
              <div v-if="item.productivity_color" class="d-flex align-center justify-center">
                <div class="fm-color-dot" :style="{ background: item.productivity_color }"></div>
                <span class="text-caption ml-1">{{ item.productivity_color }}</span>
              </div>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.image`]="{ item }">
              <v-avatar v-if="item.image" size="32" rounded="lg"><v-img :src="item.image" :alt="item.name_en" cover></v-img></v-avatar>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openTypeDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteType(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= PROCESS TYPES TAB ============= -->
        <v-window-item value="process_types">
          <div class="d-flex align-center ga-3 mb-3">
            <v-spacer></v-spacer>
            <v-btn v-if="hasActiveProcessFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="processColumnFilters = {}">{{ $t('clear_filters') }}</v-btn>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openProcessDialog(null)">{{ $t('add_process_type') }}</v-btn>
          </div>
          <v-data-table :headers="processHeaders" :items="filteredProcessTypes" :loading="loadingProcessTypes ? 'info' : false" density="comfortable" class="rounded-lg param-table" hover>
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
              <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="processColumnFilters[col.key]" :items="getProcessColumnOptions(col.key)" density="compact" variant="outlined" color="info" class="param-filter-input text-info" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="info" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
            </template>
            <template v-slot:[`item.fish_types_count`]="{ item }"><v-chip color="info" size="small" variant="tonal">{{ item.fish_types_count || 0 }}</v-chip></template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openProcessDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteProcess(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-window-item>
      </v-window>
    </v-card-text>

    <!-- ============= FAMILY DIALOG ============= -->
    <v-dialog v-model="familyDialog" max-width="500" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-family-tree</v-icon>
          {{ editingFamily ? $t('edit_fish_family') : $t('add_fish_family') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-text-field v-model="familyForm.name_fr" :label="$t('name_fr')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3"></v-text-field>
          <v-text-field v-model="familyForm.name_en" :label="$t('name_en')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3"></v-text-field>
          <v-text-field v-model="familyForm.name_es" :label="$t('name_es')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="familyDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveFamily" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= FISH TYPE DIALOG ============= -->
    <v-dialog v-model="typeDialog" max-width="700" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-fish</v-icon>
          {{ editingType ? $t('edit_fish_type') : $t('add_fish_type') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="6">
              <v-text-field v-model="typeForm.name_fr" :label="$t('name_fr')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.name_en" :label="$t('name_en')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.name_es" :label="$t('name_es')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.name_in_invoice" :label="$t('name_in_invoice')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.scientific_name" :label="$t('scientific_name')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-autocomplete
                v-model="typeForm.fish_family_id"
                :items="families"
                :item-title="familyTitle"
                item-value="id"
                :label="$t('select_family')"
                variant="outlined"
                density="compact"
                rounded
                clearable
                chips
                color="info"
                class="text-info"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="info accent-4" variant="tonal">{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </v-col>
            <v-col cols="6">
              <v-autocomplete
                v-model="typeForm.type"
                :items="fishTypeOptions"
                item-title="label"
                item-value="value"
                :label="$t('fish_category_type')"
                variant="outlined"
                density="compact"
                rounded
                clearable
                chips
                color="info"
                class="text-info"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" :color="item.raw.value === 'fresh' ? 'teal' : 'blue'" variant="tonal">{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.process" :label="$t('fish_process')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.image" :label="$t('image_url')" variant="outlined" density="compact" rounded color="info" class="text-info" prepend-inner-icon="mdi-image-outline"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-textarea v-model="typeForm.description" :label="$t('description')" variant="outlined" density="compact" rounded color="info" class="text-info" rows="2"></v-textarea>
            </v-col>
          </v-row>

          <v-divider class="my-3"></v-divider>

          <!-- Productivity Section -->
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="info" class="mr-2" size="20">mdi-chart-line</v-icon>
            {{ $t('productivity_type') }}
          </div>

          <v-row>
            <v-col cols="8">
              <v-autocomplete
                v-model="typeForm.fish_process_type_id"
                :items="processTypes"
                item-title="name"
                item-value="id"
                :label="$t('select_productivity_type')"
                :placeholder="$t('type_and_enter_to_create')"
                variant="outlined"
                density="compact"
                rounded
                clearable
                chips
                color="info"
                class="text-info"
                :search="processTypeSearch"
                @update:search="processTypeSearch = $event"
                @keydown.enter.prevent="onProcessTypeEnter"
                :loading="creatingProcessType"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" :color="typeForm.productivity_color || 'info accent-4'" variant="tonal">{{ item.title }}</v-chip>
                </template>
                <template #no-data>
                  <v-list-item>
                    <v-list-item-title class="text-caption text-grey">
                      <v-icon size="16" class="mr-1">mdi-keyboard-return</v-icon>
                      {{ $t('type_and_enter_to_create') }}
                    </v-list-item-title>
                  </v-list-item>
                </template>
              </v-autocomplete>

              <!-- Created success message -->
              <v-slide-y-transition>
                <v-alert
                  v-if="processTypeCreatedMsg"
                  type="success"
                  variant="tonal"
                  density="compact"
                  class="mt-1"
                  closable
                  @click:close="processTypeCreatedMsg = ''"
                >
                  {{ processTypeCreatedMsg }}
                </v-alert>
              </v-slide-y-transition>
            </v-col>
            <v-col cols="4">
              <div class="text-caption text-grey mb-1">{{ $t('productivity_color') }}</div>
              <v-menu :close-on-content-click="false" location="bottom">
                <template v-slot:activator="{ props }">
                  <v-btn
                    v-bind="props"
                    variant="outlined"
                    rounded
                    block
                    class="fm-color-btn"
                    :style="{ borderColor: typeForm.productivity_color || '#ccc' }"
                  >
                    <div class="fm-color-dot mr-2" :style="{ background: typeForm.productivity_color || '#ccc' }"></div>
                    {{ typeForm.productivity_color || $t('select') }}
                  </v-btn>
                </template>
                <v-color-picker
                  v-model="typeForm.productivity_color"
                  :modes="['hexa']"
                  show-swatches
                  swatches-max-height="120"
                  elevation="4"
                  rounded
                ></v-color-picker>
              </v-menu>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="typeDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveType" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= PROCESS TYPE DIALOG ============= -->
    <v-dialog v-model="processDialog" max-width="500" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-cog-outline</v-icon>
          {{ editingProcess ? $t('edit_process_type') : $t('add_process_type') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-text-field v-model="processForm.name" :label="$t('name')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3"></v-text-field>
          <v-textarea v-model="processForm.description" :label="$t('description')" variant="outlined" density="compact" rounded color="info" class="text-info" rows="2"></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="processDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveProcess" :loading="saving">{{ $t('save') }}</v-btn>
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
          {{ $t('confirm_delete_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script>
import axios from 'axios';
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import { useFishProcessType } from '@/stores/FishProcessType';
import { useFishFamily } from '@/stores/FishFamily';
import { useFishType } from '@/stores/FishType';

export default {
  name: 'FishManagement',
  data() {
    return {
      activeTab: 'families',
      families: [],
      loadingFamilies: false,
      familyDialog: false,
      editingFamily: null,
      familyForm: { name_fr: '', name_en: '', name_es: '' },
      familyColumnFilters: {},
      fishTypes: [],
      loadingTypes: false,
      typeDialog: false,
      editingType: null,
      typeForm: {
        name_fr: '', name_en: '', name_es: '', name_in_invoice: '',
        scientific_name: '', image: '', description: '', process: '', type: null,
        fish_family_id: null, fish_process_type_id: null,
        productivity_color: '#1E88E5',
      },
      typeColumnFilters: {},
      processTypesList: [],
      loadingProcessTypes: false,
      processDialog: false,
      editingProcess: null,
      processForm: { name: '', description: '' },
      processColumnFilters: {},
      processTypeSearch: '',
      creatingProcessType: false,
      processTypeCreatedMsg: '',
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleteEntityType: null,
      saving: false,
    };
  },
  computed: {
    main() { return useMain(); },
    fishProcessTypeStore() { return useFishProcessType(); },
    fishFamilyStore() { return useFishFamily(); },
    fishTypeStore() { return useFishType(); },
    processTypes() { return this.fishProcessTypeStore.getList; },
    fishTypeOptions() {
      return [
        { value: 'fresh', label: this.$t('fresh') },
        { value: 'frozen', label: this.$t('frozen') },
      ];
    },
    familyHeaders() {
      return [
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_en'), key: 'name_en', align: 'center' },
        { title: this.$t('name_es'), key: 'name_es', align: 'center' },
        { title: this.$t('fish_types_count'), key: 'fish_types_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    typeHeaders() {
      return [
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_in_invoice'), key: 'name_in_invoice', align: 'center' },
        { title: this.$t('fish_category_type'), key: 'type', align: 'center' },
        { title: this.$t('fish_families'), key: 'fish_family', align: 'center' },
        { title: this.$t('image'), key: 'image', align: 'center' },
        { title: this.$t('productivity_type'), key: 'fish_process_type', align: 'center' },
        { title: this.$t('productivity_color'), key: 'productivity_color', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    processHeaders() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('description'), key: 'description', align: 'center' },
        { title: this.$t('fish_types_count'), key: 'fish_types_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    filteredFamilies() {
      return this.families.filter(item => Object.entries(this.familyColumnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(String(item[key] ?? ''));
      }));
    },
    filteredTypes() {
      return this.fishTypes.filter(item => Object.entries(this.typeColumnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(this.getTypeCellValue(key, item));
      }));
    },
    filteredProcessTypes() {
      return this.processTypesList.filter(item => Object.entries(this.processColumnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(String(item[key] ?? ''));
      }));
    },
    hasActiveFamilyFilters() { return Object.values(this.familyColumnFilters).some(f => f && f.length > 0); },
    hasActiveTypeFilters() { return Object.values(this.typeColumnFilters).some(f => f && f.length > 0); },
    hasActiveProcessFilters() { return Object.values(this.processColumnFilters).some(f => f && f.length > 0); },
  },
  mounted() {
    this.fetchFamilies();
    this.fetchTypes();
    this.fetchProcessTypes();
    mitt.on('model-changed', this._onModelChanged);
  },
  beforeUnmount() { mitt.off('model-changed', this._onModelChanged); },
  methods: {
    familyTitle(item) {
      return item['name_' + this.main.lang.short] || item.name_en;
    },
    getTypeCellValue(key, item) {
      if (key === 'type') return item.type ? this.$t(item.type) : '-';
      if (key === 'fish_family') return item.fish_family ? (item.fish_family['name_' + this.main.lang.short] || item.fish_family.name_en) : '-';
      if (key === 'fish_process_type') return item.fish_process_type ? item.fish_process_type.name : '-';
      return String(item[key] ?? '');
    },
    getFamilyColumnOptions(key) { const set = new Set(); this.families.forEach(i => set.add(String(i[key] ?? ''))); return [...set].sort(); },
    getTypeColumnOptions(key) { const set = new Set(); this.fishTypes.forEach(i => set.add(this.getTypeCellValue(key, i))); return [...set].sort(); },
    getProcessColumnOptions(key) { const set = new Set(); this.processTypesList.forEach(i => set.add(String(i[key] ?? ''))); return [...set].sort(); },
    _onModelChanged(data) {
      if (data.model === 'FishFamily') this.fetchFamilies();
      if (data.model === 'FishType') { this.fetchTypes(); this.fetchFamilies(); }
      if (data.model === 'FishProcessType') this.fetchProcessTypes();
    },

    // -- Fish Families --
    async fetchFamilies() {
      this.loadingFamilies = true;
      try {
        const res = await axios.get('/api/fish-families');
        this.families = res.data.fish_families || [];
      } catch (e) { console.error(e); }
      this.loadingFamilies = false;
    },
    openFamilyDialog(item) {
      this.editingFamily = item;
      this.familyForm = item
        ? { name_fr: item.name_fr, name_en: item.name_en, name_es: item.name_es }
        : { name_fr: '', name_en: '', name_es: '' };
      this.familyDialog = true;
    },
    async saveFamily() {
      this.saving = true;
      try {
        if (this.editingFamily) {
          await axios.put(`/api/fish-families/${this.editingFamily.id}`, this.familyForm);
        } else {
          await axios.post('/api/fish-families', this.familyForm);
        }
        this.familyDialog = false;
        this.fetchFamilies();
      } catch (e) { console.error(e); }
      this.saving = false;
    },
    deleteFamily(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'family';
      this.confirmDeleteDialog = true;
    },

    // -- Fish Types --
    async fetchTypes() {
      this.loadingTypes = true;
      try {
        const res = await axios.get('/api/fish-types');
        this.fishTypes = res.data.fish_types || [];
      } catch (e) { console.error(e); }
      this.loadingTypes = false;
    },
    openTypeDialog(item) {
      this.editingType = item;
      this.processTypeCreatedMsg = '';
      this.processTypeSearch = '';
      if (item) {
        this.typeForm = {
          name_fr: item.name_fr, name_en: item.name_en, name_es: item.name_es,
          name_in_invoice: item.name_in_invoice,
          scientific_name: item.scientific_name || '',
          image: item.image || '',
          description: item.description || '',
          process: item.process || '',
          type: item.type || null,
          fish_family_id: item.fish_family_id,
          fish_process_type_id: item.fish_process_type_id,
          productivity_color: item.productivity_color || '#1E88E5',
        };
      } else {
        this.typeForm = {
          name_fr: '', name_en: '', name_es: '', name_in_invoice: '',
          scientific_name: '', image: '', description: '', process: '', type: null,
          fish_family_id: null, fish_process_type_id: null,
          productivity_color: '#1E88E5',
        };
      }
      this.typeDialog = true;
    },
    async saveType() {
      this.saving = true;
      try {
        if (this.editingType) {
          await axios.put(`/api/fish-types/${this.editingType.id}`, this.typeForm);
        } else {
          await axios.post('/api/fish-types', this.typeForm);
        }
        this.typeDialog = false;
        this.fetchTypes();
        this.fetchFamilies(); // refresh counts
      } catch (e) { console.error(e); }
      this.saving = false;
    },
    deleteType(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'type';
      this.confirmDeleteDialog = true;
    },

    // -- Process Types --
    async fetchProcessTypes() {
      this.loadingProcessTypes = true;
      try {
        const res = await axios.get('/api/fish-process-types');
        this.processTypesList = res.data.fish_process_types || [];
        // Also update the store for the autocomplete
        this.fishProcessTypeStore.list = this.processTypesList;
      } catch (e) { console.error(e); }
      this.loadingProcessTypes = false;
    },
    openProcessDialog(item) {
      this.editingProcess = item;
      this.processForm = item
        ? { name: item.name, description: item.description || '' }
        : { name: '', description: '' };
      this.processDialog = true;
    },
    async saveProcess() {
      this.saving = true;
      try {
        if (this.editingProcess) {
          await axios.put(`/api/fish-process-types/${this.editingProcess.id}`, this.processForm);
        } else {
          await axios.post('/api/fish-process-types', this.processForm);
        }
        this.processDialog = false;
        this.fetchProcessTypes();
      } catch (e) { console.error(e); }
      this.saving = false;
    },
    deleteProcess(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'process';
      this.confirmDeleteDialog = true;
    },

    // -- Process Type Create-on-Enter --
    async onProcessTypeEnter() {
      const name = (this.processTypeSearch || '').trim();
      if (!name) return;
      // Check if already exists
      const existing = this.processTypes.find(pt => pt.name.toLowerCase() === name.toLowerCase());
      if (existing) {
        this.typeForm.fish_process_type_id = existing.id;
        this.processTypeSearch = '';
        return;
      }
      // Create new
      this.creatingProcessType = true;
      try {
        const res = await axios.post('/api/fish-process-types', { name, description: null });
        const created = res.data.fish_process_type;
        this.fishProcessTypeStore.addFishProcessType(created);
        this.typeForm.fish_process_type_id = created.id;
        this.processTypeCreatedMsg = `${this.$t('process_type_created')}: ${created.name}`;
        this.processTypeSearch = '';
        // Auto-clear message after 3s
        setTimeout(() => { this.processTypeCreatedMsg = ''; }, 3000);
      } catch (e) { console.error(e); }
      this.creatingProcessType = false;
    },

    // -- Delete --
    async executeDelete() {
      this.confirmDeleteDialog = false;
      const item = this.deleteTarget;
      if (!item) return;
      try {
        if (this.deleteEntityType === 'family') {
          await axios.delete(`/api/fish-families/${item.id}`);
          this.fetchFamilies();
        } else if (this.deleteEntityType === 'type') {
          await axios.delete(`/api/fish-types/${item.id}`);
          this.fetchTypes();
          this.fetchFamilies();
        } else if (this.deleteEntityType === 'process') {
          await axios.delete(`/api/fish-process-types/${item.id}`);
          this.fetchProcessTypes();
        }
      } catch (e) { console.error(e); }
      this.deleteTarget = null;
      this.deleteEntityType = null;
    },
  },
};
</script>

<style scoped>
.fm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.fm-hero { background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.fm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.fm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.fm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.fm-color-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(0,0,0,.12); display: inline-block; flex-shrink: 0; }
.fm-color-btn { text-transform: none; font-weight: 500; letter-spacing: 0; min-height: 40px; }
/* Column filter styles */
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; }
.param-filter-row th { padding: 0 4px 6px !important; }
.param-filter-th { vertical-align: top; }
.param-filter-input { min-width: 80px; }
.param-filter-input :deep(.v-field) { font-size: 11px !important; min-height: 30px !important; border-radius: 8px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 26px !important; font-size: 11px; }
.param-filter-input :deep(.v-field__clearable) { font-size: 14px; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
</style>
