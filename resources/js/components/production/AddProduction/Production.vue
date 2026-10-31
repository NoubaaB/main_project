<template>
  <div v-if="prodStore.production && selectedCategory" class="production-additional-fields">
    <v-row dense>
      <!-- Supplier -->
      <v-col v-if="selectedCategory.enable_suppliers" cols="12" sm="6">
        <v-autocomplete
          ref="supplierInput"
          v-model="prodStore.production.supplier_id"
          :items="supplierOptions"
          :label="$t('supplier')"
          prepend-inner-icon="mdi-account"
          density="compact" variant="outlined" rounded clearable
          color="#00897B" class="text-prod" hide-details
          @update:modelValue="onSupplierChanged"
          @update:search="val => supplierSearch = val"
          @keydown.enter.prevent="createSupplierIfNew"
        >
          <template #no-data>
            <v-list-item v-if="supplierSearch?.trim()">
              <v-list-item-title class="text-caption">
                <v-icon size="16" class="mr-1">mdi-plus-circle-outline</v-icon>
                {{ $t('press_enter_to_create') }} "<strong>{{ supplierSearch }}</strong>"
              </v-list-item-title>
            </v-list-item>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Supplier Plafond Warning / Blocking Alert Banner -->
      <v-col v-if="selectedCategory.enable_suppliers && prodStore.plafondCheckResult?.is_reached" cols="12">
        <v-alert
          :color="prodStore.plafondCheckResult.is_blocked ? 'error' : 'warning'"
          variant="tonal"
          rounded="lg"
          density="comfortable"
          :icon="prodStore.plafondCheckResult.is_blocked ? 'mdi-cancel' : 'mdi-alert'"
          class="font-weight-medium text-caption"
        >
          <div class="d-flex align-center justify-space-between flex-wrap ga-2">
            <div>
              <strong>{{ prodStore.plafondCheckResult.is_blocked ? $t('supplier_debt_plafond_reached_blocked') : $t('supplier_debt_plafond_reached_warning') }}:</strong>
              {{ prodStore.plafondCheckResult.message }}
            </div>
            <v-chip
              size="x-small"
              :color="prodStore.plafondCheckResult.is_blocked ? 'error' : 'warning'"
              variant="flat"
              class="font-weight-bold text-white"
            >
              {{ prodStore.plafondCheckResult.is_blocked ? $t('creation_blocked') : $t('warning_only') }}
            </v-chip>
          </div>
        </v-alert>
      </v-col>

      <!-- Client -->
      <v-col v-if="selectedCategory.enable_clients" cols="12" sm="6">
        <v-autocomplete
          v-model="prodStore.production.client_id"
          :items="clientOptions"
          :label="$t('client')"
          prepend-inner-icon="mdi-account-tie"
          density="compact" variant="outlined" rounded clearable
          color="#00897B" class="text-prod" hide-details
        ></v-autocomplete>
      </v-col>

      <!-- Fishery -->
      <v-col v-if="selectedCategory.enable_fishery_id" cols="12" sm="6">
        <v-autocomplete
          v-model="prodStore.production.fishery_id"
          :items="fisheryOptions"
          :label="$t('fishery')"
          prepend-inner-icon="mdi-fish"
          chips density="compact" variant="outlined" rounded clearable
          color="#00897B" class="text-prod" hide-details
        >
          <template #chip="{ item }"><v-chip color="#00897B" variant="tonal">{{ item.title }}</v-chip></template>
        </v-autocomplete>
      </v-col>

      <!-- Ship -->
      <v-col v-if="selectedCategory.enable_ship_id" cols="12" sm="6">
        <v-autocomplete
          ref="shipInput"
          v-model="prodStore.production.ship_id"
          :items="shipOptions"
          :label="$t('ship')"
          prepend-inner-icon="mdi-ferry"
          chips density="compact" variant="outlined" rounded clearable
          color="#00897B" class="text-prod" hide-details
          @update:search="val => shipSearch = val"
          @keydown.enter.prevent="createShipIfNew"
        >
          <template #chip="{ item }"><v-chip color="#00897B" variant="tonal">{{ item.title }}</v-chip></template>
          <template #no-data>
            <v-list-item v-if="shipSearch?.trim()">
              <v-list-item-title class="text-caption">
                <v-icon size="16" class="mr-1">mdi-plus-circle-outline</v-icon>
                {{ $t('press_enter_to_create') }} "<strong>{{ shipSearch }}</strong>"
              </v-list-item-title>
            </v-list-item>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Origin -->
      <v-col v-if="selectedCategory.enable_origin_id" cols="12" sm="6">
        <v-autocomplete
          v-model="prodStore.production.origin_id"
          :items="originOptions"
          :label="$t('origin')"
          prepend-inner-icon="mdi-map-marker"
          chips density="compact" variant="outlined" rounded clearable
          color="#00897B" class="text-prod" hide-details
        >
          <template #chip="{ item }"><v-chip color="#00897B" variant="tonal">{{ item.title }}</v-chip></template>
        </v-autocomplete>
      </v-col>

      <!-- Truck -->
      <v-col v-if="selectedCategory.enable_truck_id" cols="12" sm="6">
        <v-autocomplete
          ref="truckInput"
          v-model="prodStore.production.truck_ids"
          :items="truckOptions"
          :label="$t('truck')"
          prepend-inner-icon="mdi-truck"
          multiple chips density="compact" variant="outlined" rounded clearable
          color="#00897B" class="text-prod" hide-details
          @update:search="val => truckSearch = val"
          @keydown.enter.prevent="createTruckIfNew"
        >
          <template #chip="{ item, index }">
            <v-chip :key="index" color="#00897B" closable variant="tonal"
              @click:close="prodStore.production.truck_ids = prodStore.production.truck_ids.filter(id => id !== item.value)">
              {{ item.title }}
            </v-chip>
          </template>
          <template #no-data>
            <v-list-item v-if="truckSearch?.trim()">
              <v-list-item-title class="text-caption">
                <v-icon size="16" class="mr-1">mdi-plus-circle-outline</v-icon>
                {{ $t('press_enter_to_create') }} "<strong>{{ truckSearch }}</strong>"
              </v-list-item-title>
            </v-list-item>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Reception Tracking Numbers -->
      <v-col v-if="selectedCategory.enable_reception_tracking_number" cols="12" sm="6">
        <v-autocomplete
          ref="receptionTrackingInput"
          v-model="prodStore.production.reception_tracking_number"
          :items="prodStore.production.reception_tracking_number"
          :label="$t('reception_tracking_number')"
          prepend-inner-icon="mdi-barcode"
          multiple chips density="compact" variant="outlined" rounded clearable
          @keydown.enter.prevent="addReceptionTn"
          color="#00897B" class="text-prod" hide-details placeholder="ex :   --- --- --- --- ---"
          :disabled="is_checking_tracking" :loading="is_checking_tracking"
        >
          <template #chip="{ item, index }">
            <v-chip :key="index" color="#00897B" closable variant="tonal"
              @click:close="prodStore.production.reception_tracking_number = prodStore.production.reception_tracking_number.filter(n => n !== item.value)">
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Tracking ONP Numbers -->
      <v-col v-if="selectedCategory.enable_tracking_onp_numbers" cols="12" sm="6">
        <v-autocomplete
          ref="onpTrackingInput"
          v-model="prodStore.production.tracking_onp_numbers"
          :items="prodStore.production.tracking_onp_numbers"
          :label="$t('tracking_onp_numbers')"
          prepend-inner-icon="mdi-tag"
          multiple chips density="compact" variant="outlined" rounded clearable
          @keydown.enter.prevent="addOnpTn"
          color="#00897B" class="text-prod" hide-details placeholder="ex :   --- --- --- --- ---"
          :disabled="is_checking_onp_tracking" :loading="is_checking_onp_tracking"
        >
          <template #chip="{ item, index }">
            <v-chip :key="index" color="#00897B" closable variant="tonal"
              @click:close="prodStore.production.tracking_onp_numbers = prodStore.production.tracking_onp_numbers.filter(n => n !== item.value)">
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Production Tracking Numbers -->
      <v-col v-if="selectedCategory.enable_production_tracking_number" cols="12" sm="6">
        <v-autocomplete
          ref="trackingInput"
          v-model="prodStore.production.production_tracking_number"
          :items="prodStore.production.production_tracking_number"
          :label="$t('production_tracking_numbers')"
          prepend-inner-icon="mdi-pound"
          multiple chips density="compact" variant="outlined" rounded clearable
          @keydown.enter.prevent="addTn"
          color="#00897B" class="text-prod" hide-details placeholder="ex :   --- --- --- --- ---"
          :disabled="is_checking_production_tracking" :loading="is_checking_production_tracking"
        >
          <template #chip="{ item, index }">
            <v-chip :key="index" color="#00897B" closable variant="tonal"
              @click:close="prodStore.production.production_tracking_number = prodStore.production.production_tracking_number.filter(n => n !== item.value)">
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from 'axios';
import { useProduction } from '@/stores/Production';
import { useMain } from '@/stores/Main';
import { useProductionCategory } from '@/stores/ProductionCategory';
import { useAuth } from '@/stores/Auth';
import { useSupplier } from '@/stores/Supplier';

export default {
  name: 'ProductionForm',
  data() {
    return {
      is_checking_tracking: false,
      is_checking_production_tracking: false,
      is_checking_onp_tracking: false,
      supplierSearch: '',
      truckSearch: '',
      shipSearch: '',
    };
  },
  computed: {
    prodStore() { return useProduction(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    productionCategoryStore() { return useProductionCategory(); },
    selectedCategory() {
      if (!this.prodStore.production?.production_category_id) return null;
      return this.productionCategoryStore.list.find(c => c.id === this.prodStore.production.production_category_id);
    },
    supplierOptions() { return this.auth.supplier.list.map(s => ({ title: s.name, value: s.id })); },
    clientOptions() { return this.auth.client.list.map(c => ({ title: c.name, value: c.id })); },
    fisheryOptions() { return this.auth.fishery.list.map(f => ({ title: f.name, value: f.id })); },
    shipOptions() { return this.auth.ship.list.map(s => ({ title: s.name, value: s.id })); },
    originOptions() { return this.auth.origin.list.map(o => ({ title: o.name, value: o.id })); },
    truckOptions() { return this.auth.truck.list.map(t => ({ title: t.registration_plate, value: t.id })); },
  },
  methods: {
    prefixWithYear(value) {
      const dateStr = this.prodStore.production?.date;
      const yy = dateStr ? dateStr.substring(2, 4) : String(new Date().getFullYear()).substring(2);
      const prefix = yy + '-';
      if (value.startsWith(prefix)) return value;
      return prefix + value;
    },
    async addReceptionTn(event) {
      const raw = event.target.value?.trim(); if (!raw) return;
      if (!this.prodStore.production.reception_tracking_number) this.prodStore.production.reception_tracking_number = [];
      const nums = raw.includes(' ') ? raw.split(' ') : [raw];
      for (const n of nums) {
        const prefixed = this.prefixWithYear(n);
        if (this.prodStore.production.reception_tracking_number.includes(prefixed)) continue;
        this.is_checking_tracking = true;
        try {
          const res = await axios.post('/api/productions/check-reception-tracking-number', {
            tracking_number: prefixed,
            production_category_id: this.prodStore.production.production_category_id
          });
          if (res.data.exists) {
            const p = res.data.production;
            this.main.add_snackbar({
              show: true,
              message: this.$t('production_tracking_number_exists', { tracking_number: prefixed })
                + ' - ' + this.$t('production_tracking_number_duplicate_details', {
                  date: p.date, suppliers: p.suppliers || '', weight: p.weight || 0
                }),
              color: 'warning'
            });
            this.is_checking_tracking = false;
            continue;
          }
        } catch (e) { console.error(e); }
        this.is_checking_tracking = false;
        this.prodStore.production.reception_tracking_number.push(prefixed);
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.receptionTrackingInput?.focus(); });
    },
    async addTn(event) {
      const raw = event.target.value?.trim(); if (!raw) return;
      if (!this.prodStore.production.production_tracking_number) this.prodStore.production.production_tracking_number = [];
      const nums = raw.includes(' ') ? raw.split(' ') : [raw];
      for (const n of nums) {
        const prefixed = this.prefixWithYear(n);
        if (this.prodStore.production.production_tracking_number.includes(prefixed)) continue;
        this.is_checking_production_tracking = true;
        try {
          const res = await axios.post('/api/productions/check-production-tracking-number', {
            tracking_number: prefixed,
            production_category_id: this.prodStore.production.production_category_id
          });
          if (res.data.exists) {
            const p = res.data.production;
            this.main.add_snackbar({
              show: true,
              message: this.$t('production_tracking_number_exists', { tracking_number: prefixed })
                + ' - ' + this.$t('production_tracking_number_duplicate_details', {
                  date: p.date, suppliers: p.suppliers || '', weight: p.weight || 0
                }),
              color: 'warning'
            });
            this.is_checking_production_tracking = false;
            continue;
          }
        } catch (e) { console.error(e); }
        this.is_checking_production_tracking = false;
        this.prodStore.production.production_tracking_number.push(prefixed);
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.trackingInput?.focus(); });
    },
    async addOnpTn(event) {
      const raw = event.target.value?.trim(); if (!raw) return;
      if (!this.prodStore.production.tracking_onp_numbers) this.prodStore.production.tracking_onp_numbers = [];
      const nums = raw.includes(' ') ? raw.split(' ') : [raw];
      for (const n of nums) {
        const prefixed = this.prefixWithYear(n);
        if (this.prodStore.production.tracking_onp_numbers.includes(prefixed)) continue;
        this.is_checking_onp_tracking = true;
        try {
          const res = await axios.post('/api/productions/check-onp-tracking-number', {
            tracking_number: prefixed,
            production_category_id: this.prodStore.production.production_category_id
          });
          if (res.data.exists) {
            this.main.add_snackbar({ show: true, message: this.$t('tracking_number_exists', { tracking_number: prefixed }), color: 'warning' });
            this.is_checking_onp_tracking = false;
            continue;
          }
        } catch (e) { console.error(e); }
        this.is_checking_onp_tracking = false;
        this.prodStore.production.tracking_onp_numbers.push(prefixed);
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.onpTrackingInput?.focus(); });
    },
    async createSupplierIfNew() {
      const search = (this.supplierSearch || '').trim();
      if (!search) return;
      const existing = this.auth.supplier.list.find(s => s.name.toLowerCase() === search.toLowerCase());
      if (existing) {
        this.prodStore.production.supplier_id = existing.id;
        this.supplierSearch = '';
        this.$nextTick(() => { this.$refs.supplierInput?.focus(); });
        return;
      }
      try {
        const res = await axios.post('/api/supplier', { name: search });
        const newSupplier = res.data.supplier;
        this.auth.supplier.list.push(newSupplier);
        this.prodStore.production.supplier_id = newSupplier.id;
        this.supplierSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('supplier_created', { name: newSupplier.name }), color: 'success' });
        this.$nextTick(() => { this.$refs.supplierInput?.focus(); });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('supplier_create_error'), color: 'error' });
      }
    },
    async createTruckIfNew() {
      const search = (this.truckSearch || '').trim();
      if (!search) return;
      const existing = this.auth.truck.list.find(t => t.registration_plate.toLowerCase() === search.toLowerCase());
      if (existing) {
        if (!this.prodStore.production.truck_ids.includes(existing.id)) {
          this.prodStore.production.truck_ids.push(existing.id);
        }
        this.truckSearch = '';
        this.$nextTick(() => { this.$refs.truckInput?.focus(); });
        return;
      }
      try {
        const res = await axios.post('/api/truck', { registration_plate: search });
        const newTruck = res.data.truck;
        this.auth.truck.list.push(newTruck);
        this.prodStore.production.truck_ids.push(newTruck.id);
        this.truckSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('truck_created', { name: newTruck.registration_plate }), color: 'success' });
        this.$nextTick(() => { this.$refs.truckInput?.focus(); });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('truck_create_error'), color: 'error' });
      }
    },
    async createShipIfNew() {
      const search = (this.shipSearch || '').trim();
      if (!search) return;
      const existing = this.auth.ship.list.find(s => s.name.toLowerCase() === search.toLowerCase());
      if (existing) {
        this.prodStore.production.ship_id = existing.id;
        this.shipSearch = '';
        this.$nextTick(() => { this.$refs.shipInput?.blur(); });
        return;
      }
      try {
        const res = await axios.post('/api/ship', { name: search });
        const newShip = res.data.ship;
        this.auth.ship.list.push(newShip);
        this.prodStore.production.ship_id = newShip.id;
        this.shipSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('ship_created', { name: newShip.name }), color: 'success' });
        this.$nextTick(() => { this.$refs.shipInput?.blur(); });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('ship_create_error'), color: 'error' });
      }
    },
    async onSupplierChanged() {
      await this.checkSupplierPlafond();
    },
    async checkSupplierPlafond() {
      const supplierId = this.prodStore.production?.supplier_id;
      if (!supplierId) {
        this.prodStore.plafondCheckResult = null;
        return;
      }
      const date = this.prodStore.production?.date;
      const amount = this.prodStore.production?.total_price || 0;
      try {
        const res = await useSupplier().checkPlafond(supplierId, date, amount);
        this.prodStore.plafondCheckResult = res;
        if (res?.is_reached) {
          if (res.is_blocked) {
            this.main.add_snackbar({
              show: true,
              message: res.message || this.$t('supplier_debt_plafond_blocked_msg'),
              color: 'error',
            });
          } else {
            this.main.add_snackbar({
              show: true,
              message: res.message || this.$t('supplier_debt_plafond_warning_msg'),
              color: 'warning',
            });
          }
        }
      } catch (e) {
        console.error('[ProductionForm] Check supplier plafond failed:', e);
      }
    },
  },
  watch: {
    'prodStore.production.supplier_id'(newVal) {
      if (newVal) this.checkSupplierPlafond();
      else this.prodStore.plafondCheckResult = null;
    },
    'prodStore.production.total_price'(newVal, oldVal) {
      if (newVal !== oldVal && this.prodStore.production?.supplier_id) {
        this.checkSupplierPlafond();
      }
    },
    'prodStore.production.date'(newVal, oldVal) {
      if (newVal !== oldVal && this.prodStore.production?.supplier_id) {
        this.checkSupplierPlafond();
      }
    },
  },
  mounted() {
    if (this.prodStore.production?.supplier_id) {
      this.checkSupplierPlafond();
    }
  },
};
</script>

<style scoped>
.production-additional-fields { font-family: 'Inter', sans-serif; }
.text-prod { color: #00897B !important; }
</style>
