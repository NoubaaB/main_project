<template>
  <v-card class="wm-card" elevation="0">
    <!-- Hero Header -->
    <div class="wm-hero">
      <div class="wm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="wm-hero-icon">
            <v-icon size="28" color="white">mdi-warehouse</v-icon>
          </div>
          <div>
            <div class="wm-hero-title">{{ $t('warehouse_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_warehouses_categories') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-tabs v-model="activeTab" color="info" class="mx-4 mt-2" slider-color="info">
      <v-tab value="warehouses"><v-icon start>mdi-warehouse</v-icon>{{ $t('warehouses') }}</v-tab>
      <v-tab value="categories"><v-icon start>mdi-tag-multiple-outline</v-icon>{{ $t('categories') }}</v-tab>
      <v-tab value="sejour"><v-icon start>mdi-calendar-clock</v-icon>{{ $t('sejour_rates') }}</v-tab>
    </v-tabs>

    <v-divider></v-divider>

    <v-card-text class="pa-4">
      <!-- ============= WAREHOUSES TAB ============= -->
      <v-window v-model="activeTab">
        <v-window-item value="warehouses">
          <div class="d-flex align-center ga-3 mb-3">
            <v-text-field
              v-model="warehouseSearch"
              prepend-inner-icon="mdi-magnify"
              :placeholder="$t('search_warehouse')"
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
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openWarehouseDialog(null)">
              {{ $t('add_warehouse') }}
            </v-btn>
          </div>
          <v-data-table
            :headers="warehouseHeaders"
            :items="warehouses"
            :loading="loadingWarehouses"
            :search="warehouseSearch"
            density="comfortable"
            class="rounded-lg"
            hover
          >
            <template v-slot:[`item.has_sejour`]="{ item }">
              <v-chip :color="item.has_sejour ? 'warning' : 'grey'" size="small" variant="tonal">
                {{ item.has_sejour ? $t('yes') : $t('no') }}
              </v-chip>
            </template>
            <template v-slot:[`item.operation_service_fee`]="{ item }">
              {{ item.has_sejour ? item.operation_service_fee + ' DH' : '—' }}
            </template>
            <template v-slot:[`item.category`]="{ item }">
              <v-chip :color="categoryColor(item.category)" size="small" variant="tonal">{{ item.category || 'N/A' }}</v-chip>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openWarehouseDialog(item)" class="mr-1">
                <v-icon size="16">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteWarehouse(item)">
                <v-icon size="16">mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= CATEGORIES TAB ============= -->
        <v-window-item value="categories">
          <div class="d-flex justify-end mb-3">
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openCategoryDialog(null)">
              {{ $t('add_category') }}
            </v-btn>
          </div>
          <v-data-table
            :headers="categoryHeaders"
            :items="categories"
            :loading="loadingCategories"
            density="comfortable"
            class="rounded-lg"
            hover
          >
            <template v-slot:[`item.warehouse_count`]="{ item }">
              <v-chip color="info" size="small" variant="tonal">{{ item.warehouse_count || 0 }}</v-chip>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openCategoryDialog(item)" class="mr-1">
                <v-icon size="16">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteCategory(item)">
                <v-icon size="16">mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= SEJOUR RATES TAB ============= -->
        <v-window-item value="sejour">
          <v-row>
            <v-col cols="12" md="4">
              <!-- Warehouse selector + DatePicker range -->
              <v-autocomplete
                v-model="selectedSejourWarehouse"
                :items="sejourWarehouses"
                item-title="name"
                item-value="id"
                :label="$t('select_warehouse')"
                chips
                density="compact"
                variant="outlined"
                rounded
                color="info"
                class="text-info mb-4"
                hide-details
                @update:model-value="fetchSejourRates"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="info accent-4" variant="tonal">{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>

              <!-- Range Date Picker for rate config -->
              <template v-if="selectedSejourWarehouse">
                <v-switch
                  v-model="rateOpenEnded"
                  :label="$t('open_ended')"
                  color="warning"
                  density="compact"
                  hide-details
                  class="mb-2"
                ></v-switch>

                <DatePicker
                  :is-dark="main.is_dark"
                  v-model.range="rateDateRange"
                  mode="date"
                  :locale="main.lang.short"
                  expanded
                  v-if="!rateOpenEnded"
                />
                <DatePicker
                  :is-dark="main.is_dark"
                  v-model="rateDateRange.start"
                  mode="date"
                  :locale="main.lang.short"
                  expanded
                  v-if="rateOpenEnded"
                />
                <div class="text-center mt-3" role="group">
                  <v-chip color="info" variant="tonal" class="mr-2">
                    <v-icon left small class="mr-2">mdi-calendar-start</v-icon>
                    <span>{{ main.formateDate(rateOpenEnded ? rateDateRange.start : rateDateRange.start) }}</span>
                  </v-chip>
                  <v-divider vertical></v-divider>
                  <v-chip :color="rateOpenEnded ? 'warning' : 'indigo'" variant="tonal" class="ml-2">
                    <span>{{ rateOpenEnded ? $t('ongoing') : main.formateDate(rateDateRange.end) }}</span>
                    <v-icon left small class="ml-2">{{ rateOpenEnded ? 'mdi-infinity' : 'mdi-calendar-end' }}</v-icon>
                  </v-chip>
                </div>

                <!-- Overlap error -->
                <v-alert v-if="overlapError" type="error" variant="tonal" density="compact" class="mt-3" closable @click:close="overlapError = false">
                  {{ $t('date_overlap_error') }}
                </v-alert>

                <!-- Fee inputs -->
                <v-text-field
                  v-model.number="rateForm.weight_unit"
                  :label="$t('weight_unit') + ' (KG)'"
                  variant="outlined" density="compact" rounded color="deep-purple" class="text-deep-purple mt-4"
                  type="number" step="1" min="1" hide-details
                  suffix="KG"
                ></v-text-field>
                <v-text-field
                  v-model.number="rateForm.daily_rate_per_kg"
                  :label="$t('sejour_unit_price') + ' (DH/' + formatWeightUnit(rateForm.weight_unit) + ' KG)'"
                  variant="outlined" density="compact" rounded color="info" class="text-info mt-3"
                  type="number" step="0.0001" hide-details
                ></v-text-field>
                <v-text-field
                  v-model.number="rateForm.service_unit_price"
                  :label="$t('service_unit_price') + ' (DH/' + formatWeightUnit(rateForm.weight_unit) + ' KG)'"
                  variant="outlined" density="compact" rounded color="warning" class="text-warning mt-3"
                  type="number" step="0.0001" hide-details
                ></v-text-field>

                <div class="d-flex ga-3 mt-4">
                  <v-btn color="info" prepend-icon="mdi-content-save" rounded block @click="saveRate" :loading="saving">
                    {{ editingRate ? $t('update_rate') : $t('add_rate') }}
                  </v-btn>
                </div>
                <v-btn v-if="editingRate" color="grey" variant="text" size="small" block class="mt-1" @click="cancelEditRate">
                  {{ $t('cancel') }}
                </v-btn>
              </template>
            </v-col>

            <v-col cols="12" md="8">
              <!-- Empty state when no warehouse selected -->
              <div v-if="!selectedSejourWarehouse" class="sejour-empty-state">
                <div class="sejour-empty-icon">
                  <v-icon size="64" color="grey-lighten-1">mdi-warehouse</v-icon>
                </div>
                <h3 class="text-h5 font-weight-bold mt-5 mb-2" style="color: rgba(var(--v-theme-on-surface), .75)">
                  {{ $t('no_warehouse_selected') }}
                </h3>
                <p class="text-body-1 text-medium-emphasis mx-auto" style="max-width: 420px; line-height: 1.7">
                  {{ $t('no_warehouse_selected_hint') }}
                </p>
                <v-icon size="32" color="info" class="mt-4 sejour-empty-arrow">mdi-arrow-left-bold</v-icon>
              </div>

              <!-- Rates Table -->
              <v-data-table
                v-if="selectedSejourWarehouse"
                :headers="rateHeaders"
                :items="sejourRates"
                :loading="loadingRates"
                density="comfortable"
                class="rounded-lg mb-6"
                hover
              >
                <template v-slot:[`item.start_date`]="{ item }">
                  {{ main.formateDate(item.start_date) }}
                </template>
                <template v-slot:[`item.end_date`]="{ item }">
                  <v-chip v-if="!item.end_date" color="warning" size="small" variant="tonal">
                    <v-icon start size="14">mdi-infinity</v-icon>{{ $t('ongoing') }}
                  </v-chip>
                  <span v-else>{{ main.formateDate(item.end_date) }}</span>
                </template>
                <template v-slot:[`item.daily_rate_per_kg`]="{ item }">
                  <span class="font-weight-bold text-info">{{ item.daily_rate_per_kg }} DH/{{ formatWeightUnit(item.weight_unit) }} KG</span>
                </template>
                <template v-slot:[`item.weight_unit`]="{ item }">
                  <v-chip color="deep-purple" size="small" variant="tonal" class="font-weight-bold">{{ formatWeightUnit(item.weight_unit || 1000) }} KG</v-chip>
                </template>
                <template v-slot:[`item.service_unit_price`]="{ item }">
                  <span class="font-weight-bold text-warning">{{ item.service_unit_price || 0 }} DH/{{ formatWeightUnit(item.weight_unit) }} KG</span>
                </template>
                <template v-slot:[`item.actions`]="{ item }">
                  <v-btn icon size="x-small" variant="tonal" color="info" @click="openRateDialog(item)" class="mr-1">
                    <v-icon size="16">mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteRate(item)">
                    <v-icon size="16">mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-col>
          </v-row>
        </v-window-item>
      </v-window>
    </v-card-text>

    <!-- ============= WAREHOUSE DIALOG ============= -->
    <v-dialog v-model="warehouseDialog" max-width="550" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-warehouse</v-icon>
          {{ editingWarehouse ? $t('edit_warehouse') : $t('add_warehouse') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-text-field v-model="warehouseForm.name" :label="$t('name')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3"></v-text-field>
          <v-textarea v-model="warehouseForm.description" :label="$t('description')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3" rows="2"></v-textarea>
          <v-select v-model="warehouseForm.warehouse_category_id" :items="categories" item-title="name" item-value="id" :label="$t('category')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3" clearable></v-select>
          <v-select v-model="warehouseForm.warehouse_type" :items="['FishType','GuanoType','PackageType']" :label="$t('warehouse_type')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3" clearable></v-select>
          <v-switch v-model="warehouseForm.has_sejour" :label="$t('has_sejour')" color="warning" density="compact" class="mb-3"></v-switch>
          <v-text-field v-if="warehouseForm.has_sejour" v-model.number="warehouseForm.operation_service_fee" :label="$t('operation_service_fee') + ' (DH)'" variant="outlined" density="compact" rounded color="info" class="text-info" type="number"></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="warehouseDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveWarehouse" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CATEGORY DIALOG ============= -->
    <v-dialog v-model="categoryDialog" max-width="450" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-tag-multiple-outline</v-icon>
          {{ editingCategory ? $t('edit_category') : $t('add_category') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-text-field v-model="categoryForm.name" :label="$t('name')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3"></v-text-field>
          <v-textarea v-model="categoryForm.description" :label="$t('description')" variant="outlined" density="compact" rounded color="info" class="text-info" rows="2"></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="categoryDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveCategory" :loading="saving">{{ $t('save') }}</v-btn>
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

export default {
  name: 'WarehouseManagement',
  data() {
    return {
      activeTab: 'warehouses',
      // Warehouses
      warehouses: [],
      loadingWarehouses: false,
      warehouseDialog: false,
      editingWarehouse: null,
      warehouseForm: { name: '', description: '', warehouse_type: null, warehouse_category_id: null, has_sejour: false, operation_service_fee: 0 },
      warehouseSearch: '',
      // Categories
      categories: [],
      loadingCategories: false,
      categoryDialog: false,
      editingCategory: null,
      categoryForm: { name: '', description: '' },
      // Sejour Rates
      selectedSejourWarehouse: null,
      sejourRates: [],
      loadingRates: false,
      editingRate: null,
      rateForm: { daily_rate_per_kg: 0, service_unit_price: 0, weight_unit: 1000 },
      rateDateRange: { start: new Date(), end: new Date() },
      rateOpenEnded: false,
      overlapError: false,
      // Delete confirmation
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleteType: null,
      // General
      saving: false,
    };
  },
  computed: {
    main() { return useMain(); },
    warehouseHeaders() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('description'), key: 'description', align: 'center' },
        { title: this.$t('category'), key: 'category', align: 'center' },
        { title: this.$t('warehouse_type'), key: 'warehouse_type', align: 'center' },
        { title: this.$t('has_sejour'), key: 'has_sejour', align: 'center' },
        { title: this.$t('operation_service_fee'), key: 'operation_service_fee', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    categoryHeaders() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('description'), key: 'description', align: 'center' },
        { title: this.$t('warehouses'), key: 'warehouse_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    rateHeaders() {
      return [
        { title: this.$t('start_date'), key: 'start_date', align: 'center' },
        { title: this.$t('end_date'), key: 'end_date', align: 'center' },
        { title: this.$t('weight_unit'), key: 'weight_unit', align: 'center' },
        { title: this.$t('sejour_unit_price'), key: 'daily_rate_per_kg', align: 'center' },
        { title: this.$t('service_unit_price'), key: 'service_unit_price', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    sejourWarehouses() {
      return this.warehouses.filter(w => w.has_sejour);
    },
  },
  mounted() {
    this.fetchWarehouses();
    this.fetchCategories();
  },
  methods: {
    formatNum(v) { return this.main.formatDisplayNumber(v); },
    formatWeightUnit(v) {
      const n = Number(v) || 1000;
      return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(n);
    },
    categoryColor(cat) {
      if (!cat) return 'grey';
      const c = cat.toLowerCase();
      if (c === 'local') return 'success';
      if (c === 'foreign') return 'deep-purple';
      if (c === 'client') return 'orange';
      return 'info';
    },

    // ── Warehouses ──
    async fetchWarehouses() {
      this.loadingWarehouses = true;
      try {
        const res = await axios.get('/api/warehouses');
        this.warehouses = res.data.warehouses || [];
      } catch (e) { console.error(e); }
      this.loadingWarehouses = false;
    },
    openWarehouseDialog(item) {
      this.editingWarehouse = item;
      this.warehouseForm = item
        ? { name: item.name, description: item.description, warehouse_type: item.warehouse_type, warehouse_category_id: item.warehouse_category_id, has_sejour: item.has_sejour || false, operation_service_fee: item.operation_service_fee || 0 }
        : { name: '', description: '', warehouse_type: null, warehouse_category_id: null, has_sejour: false, operation_service_fee: 0 };
      this.warehouseDialog = true;
    },
    async saveWarehouse() {
      this.saving = true;
      try {
        if (this.editingWarehouse) {
          await axios.put(`/api/warehouses/${this.editingWarehouse.id}`, this.warehouseForm);
        } else {
          await axios.post('/api/warehouses', this.warehouseForm);
        }
        this.warehouseDialog = false;
        this.fetchWarehouses();
      } catch (e) { console.error(e); }
      this.saving = false;
    },
    async deleteWarehouse(item) {
      this.deleteTarget = item;
      this.deleteType = 'warehouse';
      this.confirmDeleteDialog = true;
    },

    // ── Categories ──
    async fetchCategories() {
      this.loadingCategories = true;
      try {
        const res = await axios.get('/api/warehouse-categories');
        this.categories = res.data.categories || [];
      } catch (e) { console.error(e); }
      this.loadingCategories = false;
    },
    openCategoryDialog(item) {
      this.editingCategory = item;
      this.categoryForm = item
        ? { name: item.name, description: item.description }
        : { name: '', description: '' };
      this.categoryDialog = true;
    },
    async saveCategory() {
      this.saving = true;
      try {
        if (this.editingCategory) {
          await axios.put(`/api/warehouse-categories/${this.editingCategory.id}`, this.categoryForm);
        } else {
          await axios.post('/api/warehouse-categories', this.categoryForm);
        }
        this.categoryDialog = false;
        this.fetchCategories();
        this.fetchWarehouses();
      } catch (e) { console.error(e); }
      this.saving = false;
    },
    async deleteCategory(item) {
      this.deleteTarget = item;
      this.deleteType = 'category';
      this.confirmDeleteDialog = true;
    },

    // ── Sejour Rates ──
    async fetchSejourRates() {
      if (!this.selectedSejourWarehouse) return;
      this.loadingRates = true;
      this.sejourResult = null;
      try {
        const res = await axios.get(`/api/warehouses/${this.selectedSejourWarehouse}/sejour-rates`);
        this.sejourRates = res.data.rates || [];
      } catch (e) { console.error(e); }
      this.loadingRates = false;
    },
    openRateDialog(item) {
      this.editingRate = item;
      this.overlapError = false;
      if (item) {
        this.rateOpenEnded = !item.end_date;
        this.rateDateRange = {
          start: new Date(item.start_date),
          end: item.end_date ? new Date(item.end_date) : new Date(),
        };
        this.rateForm = { daily_rate_per_kg: item.daily_rate_per_kg, service_unit_price: item.service_unit_price || 0, weight_unit: item.weight_unit || 1000 };
      } else {
        this.rateOpenEnded = false;
        this.rateForm = { daily_rate_per_kg: 0, service_unit_price: 0, weight_unit: 1000 };
        this.rateDateRange = { start: new Date(), end: new Date() };
      }
    },
    cancelEditRate() {
      this.editingRate = null;
      this.rateOpenEnded = false;
      this.overlapError = false;
      this.rateForm = { daily_rate_per_kg: 0, service_unit_price: 0, weight_unit: 1000 };
      this.rateDateRange = { start: new Date(), end: new Date() };
    },
    formatDateForApi(d) {
      if (!d) return null;
      const dt = d instanceof Date ? d : new Date(d);
      return dt.toISOString().slice(0, 10);
    },
    async saveRate() {
      this.saving = true;
      this.overlapError = false;
      try {
        const payload = {
          start_date: this.formatDateForApi(this.rateDateRange.start),
          end_date: this.rateOpenEnded ? null : this.formatDateForApi(this.rateDateRange.end),
          daily_rate_per_kg: this.rateForm.daily_rate_per_kg,
          service_unit_price: this.rateForm.service_unit_price,
          weight_unit: this.rateForm.weight_unit,
        };
        if (this.editingRate) {
          await axios.put(`/api/warehouses/sejour-rates/${this.editingRate.id}`, payload);
        } else {
          await axios.post(`/api/warehouses/${this.selectedSejourWarehouse}/sejour-rates`, payload);
        }
        this.cancelEditRate();
        this.fetchSejourRates();
      } catch (e) {
        if (e.response && e.response.status === 422 && e.response.data?.error === 'date_overlap') {
          this.overlapError = true;
        }
        console.error(e);
      }
      this.saving = false;
    },
    async deleteRate(item) {
      this.deleteTarget = item;
      this.deleteType = 'rate';
      this.confirmDeleteDialog = true;
    },
    async executeDelete() {
      this.confirmDeleteDialog = false;
      const item = this.deleteTarget;
      if (!item) return;
      try {
        if (this.deleteType === 'warehouse') {
          await axios.delete(`/api/warehouses/${item.id}`);
          this.fetchWarehouses();
        } else if (this.deleteType === 'category') {
          await axios.delete(`/api/warehouse-categories/${item.id}`);
          this.fetchCategories();
        } else if (this.deleteType === 'rate') {
          await axios.delete(`/api/warehouses/sejour-rates/${item.id}`);
          this.fetchSejourRates();
        }
      } catch (e) { console.error(e); }
      this.deleteTarget = null;
      this.deleteType = null;
    },
  },
};
</script>

<style scoped>
.wm-card { border-radius: 20px !important; overflow: visible; position: relative; }
.wm-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  height: 100px;
}
.wm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.wm-hero-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255,255,255,.15);
}
.wm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }

/* Séjour empty state */
.sejour-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 60px 24px;
  min-height: 400px;
}
.sejour-empty-icon {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: rgba(var(--v-theme-on-surface), .04);
  border: 2px dashed rgba(var(--v-theme-on-surface), .12);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: sejour-float 3s ease-in-out infinite;
}
@keyframes sejour-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
.sejour-empty-arrow {
  animation: sejour-pulse 2s ease-in-out infinite;
}
@keyframes sejour-pulse {
  0%, 100% { opacity: .3; transform: translateX(0); }
  50% { opacity: 1; transform: translateX(-8px); }
}
</style>
