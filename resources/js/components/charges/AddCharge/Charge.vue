<template>
  <div v-if="chargeStore.charge && selectedCategory" class="charge-additional-fields">
    <v-row dense>
      <!-- Reception Tracking Numbers -->
      <v-col v-if="selectedCategory.enable_reception_tracking_number" cols="12" sm="6">
        <v-autocomplete
          ref="trackingNumberInput"
          v-model="chargeStore.charge.reception_tracking_number"
          :items="chargeStore.charge.reception_tracking_number"
          :label="$t('reception_tracking_number')"
          placeholder="ex : 24-00123"
          multiple
          chips
          density="compact"
          variant="outlined"
          rounded
          clearable
          prepend-inner-icon="mdi-barcode"
          @keydown.enter.prevent="addNewTrackingNumber"
          color="#42A5F5"
          class="text-charge"
          :disabled="is_checking_tracking"
          :loading="is_checking_tracking"
          hide-details
        >
          <template #chip="{ item, index }">
            <v-chip
              :key="index"
              color="#42A5F5"
              closable
              variant="tonal"
              @click:close="chargeStore.charge.reception_tracking_number = chargeStore.charge.reception_tracking_number.filter(num => num !== item.value)"
            >
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Tracking ONP Numbers -->
      <v-col v-if="selectedCategory.enable_tracking_onp_numbers" cols="12" sm="6">
        <v-autocomplete
          ref="onpTrackingInput"
          v-model="chargeStore.charge.tracking_onp_numbers"
          :items="chargeStore.charge.tracking_onp_numbers"
          :label="$t('tracking_onp_numbers')"
          multiple
          chips
          density="compact"
          variant="outlined"
          rounded
          hide-details
          placeholder="ex : 24-ONP-01"
          clearable
          prepend-inner-icon="mdi-tag-text-outline"
          @keydown.enter.prevent="addNewOnpTrackingNumber"
          color="#42A5F5"
          class="text-charge"
          :disabled="is_checking_onp_tracking"
          :loading="is_checking_onp_tracking"
        >
          <template #chip="{ item, index }">
            <v-chip
              :key="index"
              color="#42A5F5"
              closable
              variant="tonal"
              @click:close="chargeStore.charge.tracking_onp_numbers = chargeStore.charge.tracking_onp_numbers.filter(num => num !== item.value)"
            >
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Suppliers -->
      <v-col v-if="selectedCategory.enable_suppliers" cols="12" sm="6">
        <v-autocomplete
          ref="supplierInput"
          v-model="chargeStore.charge.supplier_id"
          :items="supplierOptions"
          :label="$t('supplier')"
          density="compact"
          variant="outlined"
          rounded
          clearable
          prepend-inner-icon="mdi-account"
          color="#42A5F5"
          class="text-charge"
          hide-details
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

      <!-- Fishery -->
      <v-col v-if="selectedCategory.enable_fishery_id" cols="12" sm="6">
        <v-autocomplete
          v-model="chargeStore.charge.fishery_id"
          :items="fisheryOptions"
          :label="$t('fishery')"
          chips
          density="compact"
          variant="outlined"
          rounded
          clearable
          prepend-inner-icon="mdi-ferry"
          color="#42A5F5"
          class="text-charge"
          hide-details
        >
          <template #chip="{ item }">
            <v-chip color="#42A5F5" variant="tonal">{{ item.title }}</v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Origin -->
      <v-col v-if="selectedCategory.enable_origin_id" cols="12" sm="6">
        <v-autocomplete
          v-model="chargeStore.charge.origin_id"
          :items="originOptions"
          :label="$t('origin')"
          chips
          density="compact"
          variant="outlined"
          rounded
          clearable
          prepend-inner-icon="mdi-map-marker"
          color="#42A5F5"
          class="text-charge"
          hide-details
        >
          <template #chip="{ item }">
            <v-chip color="#42A5F5" variant="tonal">{{ item.title }}</v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Truck -->
      <v-col v-if="selectedCategory.enable_truck_id" cols="12" sm="6">
        <v-autocomplete
          ref="truckInput"
          v-model="chargeStore.charge.truck_ids"
          :items="truckOptions"
          :label="$t('truck')"
          multiple
          chips
          density="compact"
          variant="outlined"
          rounded
          clearable
          prepend-inner-icon="mdi-truck"
          color="#42A5F5"
          class="text-charge"
          hide-details
          @update:search="val => truckSearch = val"
          @keydown.enter.prevent="createTruckIfNew"
        >
          <template #chip="{ item, index }">
            <v-chip
              :key="index"
              color="#42A5F5"
              closable
              variant="tonal"
              @click:close="chargeStore.charge.truck_ids = chargeStore.charge.truck_ids.filter(id => id !== item.value)"
            >
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

      <!-- Ship -->
      <v-col v-if="selectedCategory.enable_ship_id" cols="12" sm="6">
        <v-autocomplete
          ref="shipInput"
          v-model="chargeStore.charge.ship_id"
          :items="shipOptions"
          :label="$t('ship')"
          chips
          density="compact"
          variant="outlined"
          rounded
          clearable
          prepend-inner-icon="mdi-sail-boat"
          color="#42A5F5"
          class="text-charge"
          hide-details
          @update:search="val => shipSearch = val"
          @keydown.enter.prevent="createShipIfNew"
        >
          <template #chip="{ item }">
            <v-chip color="#42A5F5" variant="tonal">{{ item.title }}</v-chip>
          </template>
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
    </v-row>
  </div>
</template>

<script>
import axios from 'axios';
import { useCharge } from '@/stores/Charge';
import { useMain } from '@/stores/Main';
import { useChargeCategory } from '@/stores/ChargeCategory';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'ChargeForm',
  data() {
    return {
      is_checking_tracking: false,
      is_checking_onp_tracking: false,
      supplierSearch: '',
      truckSearch: '',
      shipSearch: '',
    };
  },
  computed: {
    chargeStore() { return useCharge(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    chargeCategoryStore() { return useChargeCategory(); },
    selectedCategory() {
      if (!this.chargeStore.charge?.charge_category_id) return null;
      return this.chargeCategoryStore.list.find(c => c.id === this.chargeStore.charge.charge_category_id);
    },
    supplierOptions() { return this.auth.supplier.list.map(s => ({ title: s.name, value: s.id })); },
    fisheryOptions() { return this.auth.fishery.list.map(f => ({ title: f.name, value: f.id })); },
    shipOptions() { return this.auth.ship.list.map(s => ({ title: s.name, value: s.id })); },
    originOptions() { return this.auth.origin.list.map(o => ({ title: o.name, value: o.id })); },
    truckOptions() { return this.auth.truck.list.map(t => ({ title: t.registration_plate, value: t.id })); },
  },
  methods: {
    prefixWithYear(value) {
      const dateStr = this.chargeStore.charge?.date;
      const yy = dateStr ? dateStr.substring(2, 4) : String(new Date().getFullYear()).substring(2);
      const prefix = yy + '-';
      if (value.startsWith(prefix)) return value;
      return prefix + value;
    },
    async addNewTrackingNumber(event) {
      let inputValue = event.target.value.trim();
      if (!inputValue) return;
      const numbersToAdd = inputValue.includes(' ') ? inputValue.split(' ') : [inputValue];
      for (const input of numbersToAdd) {
        const prefixed = this.prefixWithYear(input);
        if (this.chargeStore.charge.reception_tracking_number.includes(prefixed)) continue;
        this.is_checking_tracking = true;
        try {
          const res = await axios.post('/api/charge/check-tracking-number', {
            tracking_number: prefixed,
            charge_category_id: this.chargeStore.charge.charge_category_id
          });
          if (res.data.exists) {
            const c = res.data.charge;
            this.main.add_snackbar({
              show: true,
              message: this.$t('tracking_number_exists', { tracking_number: prefixed })
                + ' - ' + this.$t('tracking_number_duplicate_details', {
                  date: c.date, provider: c.provider || '', weight: c.weight || 0, price: c.price || 0, currency: this.main.currency
                }),
              color: 'warning'
            });
            this.is_checking_tracking = false;
            continue;
          }
        } catch (e) { console.error(e); }
        this.is_checking_tracking = false;
        this.chargeStore.charge.reception_tracking_number.push(prefixed);
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.trackingNumberInput?.focus(); });
    },
    async addNewOnpTrackingNumber(event) {
      let inputValue = event.target.value.trim();
      if (!inputValue) return;
      const numbersToAdd = inputValue.includes(' ') ? inputValue.split(' ') : [inputValue];
      for (const input of numbersToAdd) {
        const prefixed = this.prefixWithYear(input);
        if (this.chargeStore.charge.tracking_onp_numbers.includes(prefixed)) continue;
        this.is_checking_onp_tracking = true;
        try {
          const res = await axios.post('/api/charge/check-onp-tracking-number', {
            tracking_number: prefixed,
            charge_category_id: this.chargeStore.charge.charge_category_id
          });
          if (res.data.exists) {
            const c = res.data.charge;
            this.main.add_snackbar({
              show: true,
              message: this.$t('tracking_number_exists', { tracking_number: prefixed })
                + ' - ' + this.$t('tracking_number_duplicate_details', {
                  date: c.date, provider: c.provider || '', weight: c.weight || 0, price: c.price || 0, currency: this.main.currency
                }),
              color: 'warning'
            });
            this.is_checking_onp_tracking = false;
            continue;
          }
        } catch (e) { console.error(e); }
        this.is_checking_onp_tracking = false;
        this.chargeStore.charge.tracking_onp_numbers.push(prefixed);
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.onpTrackingInput?.focus(); });
    },
    async createSupplierIfNew() {
      const search = (this.supplierSearch || '').trim();
      if (!search) return;
      // Check if it already exists (case-insensitive)
      const existing = this.auth.supplier.list.find(s => s.name.toLowerCase() === search.toLowerCase());
      if (existing) {
        this.chargeStore.charge.supplier_id = existing.id;
        this.supplierSearch = '';
        this.$nextTick(() => { this.$refs.supplierInput?.focus(); });
        return;
      }
      try {
        const res = await axios.post('/api/supplier', { name: search });
        const newSupplier = res.data.supplier;
        this.auth.supplier.list.push(newSupplier);
        this.chargeStore.charge.supplier_id = newSupplier.id;
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
        if (!this.chargeStore.charge.truck_ids.includes(existing.id)) {
          this.chargeStore.charge.truck_ids.push(existing.id);
        }
        this.truckSearch = '';
        this.$nextTick(() => { this.$refs.truckInput?.focus(); });
        return;
      }
      try {
        const res = await axios.post('/api/truck', { registration_plate: search });
        const newTruck = res.data.truck;
        this.auth.truck.list.push(newTruck);
        this.chargeStore.charge.truck_ids.push(newTruck.id);
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
        this.chargeStore.charge.ship_id = existing.id;
        this.shipSearch = '';
        this.$nextTick(() => {
          const shipEl = this.$refs.shipInput?.$el;
          if (shipEl) {
            const next = shipEl.closest('.pa-3')?.nextElementSibling?.querySelector('input');
            if (next) next.focus(); else this.$refs.shipInput?.blur();
          }
        });
        return;
      }
      try {
        const res = await axios.post('/api/ship', { name: search });
        const newShip = res.data.ship;
        this.auth.ship.list.push(newShip);
        this.chargeStore.charge.ship_id = newShip.id;
        this.shipSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('ship_created', { name: newShip.name }), color: 'success' });
        this.$nextTick(() => {
          const shipEl = this.$refs.shipInput?.$el;
          if (shipEl) {
            const next = shipEl.closest('.pa-3')?.nextElementSibling?.querySelector('input');
            if (next) next.focus(); else this.$refs.shipInput?.blur();
          }
        });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('ship_create_error'), color: 'error' });
      }
    },
  },
};
</script>

<style scoped>
.charge-additional-fields { font-family: 'Inter', sans-serif; }
.text-charge { color: #42A5F5 !important; }
</style>
