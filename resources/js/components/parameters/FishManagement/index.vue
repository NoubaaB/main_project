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
            <v-text-field
              v-model="familySearch"
              prepend-inner-icon="mdi-magnify"
              :placeholder="$t('search')"
              variant="outlined"
              density="compact"
              rounded
              color="info"
              class="text-info"
              hide-details
              clearable
              style="max-width:400px"
            ></v-text-field>
            <v-spacer></v-spacer>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openFamilyDialog(null)">
              {{ $t('add_fish_family') }}
            </v-btn>
          </div>
          <v-data-table
            :headers="familyHeaders"
            :items="families"
            :loading="loadingFamilies"
            :search="familySearch"
            density="comfortable"
            class="rounded-lg"
            hover
          >
            <template v-slot:[`item.fish_types_count`]="{ item }">
              <v-chip color="info" size="small" variant="tonal">{{ item.fish_types_count || 0 }}</v-chip>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openFamilyDialog(item)" class="mr-1">
                <v-icon size="16">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteFamily(item)">
                <v-icon size="16">mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= FISH TYPES TAB ============= -->
        <v-window-item value="types">
          <div class="d-flex align-center ga-3 mb-3">
            <v-text-field
              v-model="typeSearch"
              prepend-inner-icon="mdi-magnify"
              :placeholder="$t('search')"
              variant="outlined"
              density="compact"
              rounded
              color="info"
              class="text-info"
              hide-details
              clearable
              style="max-width:400px"
            ></v-text-field>
            <v-spacer></v-spacer>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openTypeDialog(null)">
              {{ $t('add_fish_type') }}
            </v-btn>
          </div>
          <v-data-table
            :headers="typeHeaders"
            :items="fishTypes"
            :loading="loadingTypes"
            :search="typeSearch"
            density="comfortable"
            class="rounded-lg"
            hover
          >
            <template v-slot:[`item.fish_family`]="{ item }">
              <v-chip v-if="item.fish_family" color="teal" size="small" variant="tonal">{{ item.fish_family['name_' + main.lang.short] || item.fish_family.name_en }}</v-chip>
              <span v-else class="text-grey">—</span>
            </template>
            <template v-slot:[`item.fish_process_type`]="{ item }">
              <v-chip v-if="item.fish_process_type" size="small" variant="tonal" :color="item.productivity_color || 'info'">
                {{ item.fish_process_type.name }}
              </v-chip>
              <span v-else class="text-grey">—</span>
            </template>
            <template v-slot:[`item.productivity_color`]="{ item }">
              <div v-if="item.productivity_color" class="d-flex align-center justify-center">
                <div class="fm-color-dot" :style="{ background: item.productivity_color }"></div>
                <span class="text-caption ml-1">{{ item.productivity_color }}</span>
              </div>
              <span v-else class="text-grey">—</span>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openTypeDialog(item)" class="mr-1">
                <v-icon size="16">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteType(item)">
                <v-icon size="16">mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= PROCESS TYPES TAB ============= -->
        <v-window-item value="process_types">
          <div class="d-flex align-center ga-3 mb-3">
            <v-text-field
              v-model="processSearch"
              prepend-inner-icon="mdi-magnify"
              :placeholder="$t('search')"
              variant="outlined"
              density="compact"
              rounded
              color="info"
              class="text-info"
              hide-details
              clearable
              style="max-width:400px"
            ></v-text-field>
            <v-spacer></v-spacer>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openProcessDialog(null)">
              {{ $t('add_process_type') }}
            </v-btn>
          </div>
          <v-data-table
            :headers="processHeaders"
            :items="processTypesList"
            :loading="loadingProcessTypes"
            :search="processSearch"
            density="comfortable"
            class="rounded-lg"
            hover
          >
            <template v-slot:[`item.fish_types_count`]="{ item }">
              <v-chip color="info" size="small" variant="tonal">{{ item.fish_types_count || 0 }}</v-chip>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openProcessDialog(item)" class="mr-1">
                <v-icon size="16">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteProcess(item)">
                <v-icon size="16">mdi-delete</v-icon>
              </v-btn>
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
            <v-col cols="12">
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
import { useMain } from '@/stores/Main';
import { useFishProcessType } from '@/stores/FishProcessType';
import { useFishFamily } from '@/stores/FishFamily';
import { useFishType } from '@/stores/FishType';

export default {
  name: 'FishManagement',
  data() {
    return {
      activeTab: 'families',
      // Families
      families: [],
      loadingFamilies: false,
      familyDialog: false,
      editingFamily: null,
      familyForm: { name_fr: '', name_en: '', name_es: '' },
      familySearch: '',
      // Fish Types
      fishTypes: [],
      loadingTypes: false,
      typeDialog: false,
      editingType: null,
      typeForm: {
        name_fr: '', name_en: '', name_es: '', name_in_invoice: '',
        scientific_name: '', description: '',
        fish_family_id: null, fish_process_type_id: null,
        productivity_color: '#1E88E5',
      },
      typeSearch: '',
      // Process Types
      processTypesList: [],
      loadingProcessTypes: false,
      processDialog: false,
      editingProcess: null,
      processForm: { name: '', description: '' },
      processSearch: '',
      // Process Type autocomplete
      processTypeSearch: '',
      creatingProcessType: false,
      processTypeCreatedMsg: '',
      // Delete
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleteType: null,
      // General
      saving: false,
    };
  },
  computed: {
    main() { return useMain(); },
    fishProcessTypeStore() { return useFishProcessType(); },
    fishFamilyStore() { return useFishFamily(); },
    fishTypeStore() { return useFishType(); },
    processTypes() { return this.fishProcessTypeStore.getList; },
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
        { title: this.$t('fish_families'), key: 'fish_family', align: 'center' },
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
  },
  mounted() {
    this.fetchFamilies();
    this.fetchTypes();
    this.fetchProcessTypes();
  },
  methods: {
    familyTitle(item) {
      return item['name_' + this.main.lang.short] || item.name_en;
    },

    // ── Fish Families ──
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
      this.deleteType = 'family';
      this.confirmDeleteDialog = true;
    },

    // ── Fish Types ──
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
          description: item.description || '',
          fish_family_id: item.fish_family_id,
          fish_process_type_id: item.fish_process_type_id,
          productivity_color: item.productivity_color || '#1E88E5',
        };
      } else {
        this.typeForm = {
          name_fr: '', name_en: '', name_es: '', name_in_invoice: '',
          scientific_name: '', description: '',
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
      this.deleteType = 'type';
      this.confirmDeleteDialog = true;
    },

    // ── Process Types ──
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
      this.deleteType = 'process';
      this.confirmDeleteDialog = true;
    },

    // ── Process Type Create-on-Enter ──
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

    // ── Delete ──
    async executeDelete() {
      this.confirmDeleteDialog = false;
      const item = this.deleteTarget;
      if (!item) return;
      try {
        if (this.deleteType === 'family') {
          await axios.delete(`/api/fish-families/${item.id}`);
          this.fetchFamilies();
        } else if (this.deleteType === 'type') {
          await axios.delete(`/api/fish-types/${item.id}`);
          this.fetchTypes();
          this.fetchFamilies();
        } else if (this.deleteType === 'process') {
          await axios.delete(`/api/fish-process-types/${item.id}`);
          this.fetchProcessTypes();
        }
      } catch (e) { console.error(e); }
      this.deleteTarget = null;
      this.deleteType = null;
    },
  },
};
</script>

<style scoped>
.fm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.fm-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  height: 100px;
}
.fm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.fm-hero-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255,255,255,.15);
}
.fm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }

.fm-color-dot {
  width: 18px; height: 18px; border-radius: 50%;
  border: 2px solid rgba(0,0,0,.12);
  display: inline-block;
  flex-shrink: 0;
}
.fm-color-btn {
  text-transform: none;
  font-weight: 500;
  letter-spacing: 0;
  min-height: 40px;
}
</style>
