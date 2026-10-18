<template>
  <div v-if="shippingStore.shipping && selectedCategory" class="shipping-additional-fields">
    <v-row dense>
      <!-- Shipping Tracking Numbers -->
      <v-col v-if="selectedCategory.enable_shipping_tracking_number" cols="12" sm="6">
        <v-autocomplete
          ref="trackingNumberInput"
          v-model="shippingStore.shipping.shipping_tracking_number"
          :items="shippingStore.shipping.shipping_tracking_number"
          :label="$t('shipping_tracking_number')"
          placeholder="ex :   --- --- --- --- ---"
          prepend-inner-icon="mdi-barcode"
          multiple chips density="compact" variant="outlined" rounded clearable
          @keydown.enter.prevent="addNewTrackingNumber"
          color="#4643a0" class="text-shipping"
          :disabled="is_checking_shipping_tracking" :loading="is_checking_shipping_tracking"
          hide-details
        >
          <template #chip="{ item, index }">
            <v-chip :key="index" color="#4643a0" closable variant="tonal"
              @click:close="shippingStore.shipping.shipping_tracking_number = shippingStore.shipping.shipping_tracking_number.filter(num => num !== item.value)">
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Tracking ONP Numbers -->
      <v-col v-if="selectedCategory.enable_tracking_onp_numbers" cols="12" sm="6">
        <v-autocomplete
          ref="onpTrackingInput"
          v-model="shippingStore.shipping.tracking_onp_numbers"
          :items="shippingStore.shipping.tracking_onp_numbers"
          :label="$t('tracking_onp_numbers')"
          prepend-inner-icon="mdi-tag"
          multiple chips density="compact" variant="outlined" rounded hide-details
          placeholder="ex :   --- --- --- --- ---" clearable
          @keydown.enter.prevent="addNewOnpTrackingNumber"
          color="#4643a0" class="text-shipping"
        >
          <template #chip="{ item, index }">
            <v-chip :key="index" color="#4643a0" closable variant="tonal"
              @click:close="shippingStore.shipping.tracking_onp_numbers = shippingStore.shipping.tracking_onp_numbers.filter(num => num !== item.value)">
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Client -->
      <v-col v-if="selectedCategory.enable_clients" cols="12" sm="6">
        <v-autocomplete
          ref="clientInput"
          v-model="shippingStore.shipping.client_id"
          :items="clientOptions"
          :label="$t('client')"
          prepend-inner-icon="mdi-account"
          density="compact" variant="outlined" rounded clearable
          color="#4643a0" class="text-shipping" hide-details
          @update:search="val => clientSearch = val"
          @keydown.enter.prevent="createClientIfNew"
        >
          <template #no-data>
            <v-list-item v-if="clientSearch?.trim()">
              <v-list-item-title class="text-caption">
                <v-icon size="16" class="mr-1">mdi-plus-circle-outline</v-icon>
                {{ $t('press_enter_to_create') }} "<strong>{{ clientSearch }}</strong>"
              </v-list-item-title>
            </v-list-item>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Origin -->
      <v-col v-if="selectedCategory.enable_origin_id" cols="12" sm="6">
        <v-autocomplete
          v-model="shippingStore.shipping.origin_id"
          :items="originOptions"
          :label="$t('origin')"
          prepend-inner-icon="mdi-map-marker"
          chips density="compact" variant="outlined" rounded clearable
          color="#4643a0" class="text-shipping" hide-details
        >
          <template #chip="{ item }"><v-chip color="#4643a0" variant="tonal">{{ item.title }}</v-chip></template>
        </v-autocomplete>
      </v-col>

      <!-- Direction -->
      <v-col v-if="selectedCategory.enable_direction" cols="12" sm="6">
        <v-autocomplete
          v-model="shippingStore.shipping.direction"
          :items="[{ title: $t('local'), value: 'local' }, { title: $t('export'), value: 'export' }]"
          :label="$t('direction')"
          prepend-inner-icon="mdi-swap-horizontal"
          chips density="compact" variant="outlined" rounded clearable
          color="#4643a0" class="text-shipping" hide-details
        ></v-autocomplete>
      </v-col>

      <!-- Fishery -->
      <v-col v-if="selectedCategory.enable_fishery_id" cols="12" sm="6">
        <v-autocomplete
          v-model="shippingStore.shipping.fishery_id"
          :items="fisheryOptions"
          :label="$t('fishery')"
          prepend-inner-icon="mdi-fish"
          chips density="compact" variant="outlined" rounded clearable
          color="#4643a0" class="text-shipping" hide-details
        >
          <template #chip="{ item }"><v-chip color="#4643a0" variant="tonal">{{ item.title }}</v-chip></template>
        </v-autocomplete>
      </v-col>

      <!-- Ship -->
      <v-col v-if="selectedCategory.enable_ship_id" cols="12" sm="6">
        <v-autocomplete
          ref="shipInput"
          v-model="shippingStore.shipping.ship_id"
          :items="shipOptions"
          :label="$t('ship')"
          prepend-inner-icon="mdi-ferry"
          chips density="compact" variant="outlined" rounded clearable
          color="#4643a0" class="text-shipping" hide-details
          @update:search="val => shipSearch = val"
          @keydown.enter.prevent="createShipIfNew"
        >
          <template #chip="{ item }"><v-chip color="#4643a0" variant="tonal">{{ item.title }}</v-chip></template>
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

      <!-- Truck -->
      <v-col v-if="selectedCategory.enable_truck_id" cols="12" sm="6">
        <v-autocomplete
          ref="truckInput"
          v-model="shippingStore.shipping.truck_ids"
          :items="truckOptions"
          :label="$t('truck')"
          prepend-inner-icon="mdi-truck"
          multiple chips density="compact" variant="outlined" rounded clearable
          color="#4643a0" class="text-shipping" hide-details
          @update:search="val => truckSearch = val"
          @keydown.enter.prevent="createTruckIfNew"
        >
          <template #chip="{ item, index }">
            <v-chip :key="index" color="#4643a0" closable variant="tonal"
              @click:close="shippingStore.shipping.truck_ids = shippingStore.shipping.truck_ids.filter(id => id !== item.value)">
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
    </v-row>
  </div>
</template>

<script>
import axios from 'axios';
import { useShipping } from '@/stores/Shipping';
import { useMain } from '@/stores/Main';
import { useShippingCategory } from '@/stores/ShippingCategory';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'ShippingForm',
  data() {
    return {
      is_checking_shipping_tracking: false,
      truckSearch: '',
      clientSearch: '',
      shipSearch: '',
    };
  },
  computed: {
    shippingStore() { return useShipping(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    shippingCategoryStore() { return useShippingCategory(); },
    selectedCategory() {
      if (!this.shippingStore.shipping?.shipping_category_id) return null;
      return this.shippingCategoryStore.list.find(c => c.id === this.shippingStore.shipping.shipping_category_id);
    },
    clientOptions() { return this.auth.client.list.map(c => ({ title: c.name, value: c.id })); },
    originOptions() { return this.auth.origin.list.map(o => ({ title: o.name, value: o.id })); },
    fisheryOptions() { return this.auth.fishery.list.map(f => ({ title: f.name, value: f.id })); },
    shipOptions() { return this.auth.ship.list.map(s => ({ title: s.name, value: s.id })); },
    truckOptions() { return this.auth.truck.list.map(t => ({ title: t.registration_plate, value: t.id })); },
  },
  methods: {
    prefixWithYear(value) {
      const dateStr = this.shippingStore.shipping?.date;
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
        if (this.shippingStore.shipping.shipping_tracking_number.includes(prefixed)) continue;
        this.is_checking_shipping_tracking = true;
        try {
          const res = await axios.post('/api/shipping/check-tracking-number', {
            shipping_tracking_number: prefixed,
            shipping_category_id: this.shippingStore.shipping.shipping_category_id
          });
          if (res.data.exists) {
            const s = res.data.shipping;
            this.main.add_snackbar({
              show: true,
              message: this.$t('tracking_number_exists', { tracking_number: prefixed })
                + ' - ' + this.$t('sell_tracking_number_duplicate_details', {
                  date: s.date, client: s.client || '', weight: s.weight || 0, price: s.price || 0, currency: this.main.currency
                }),
              color: 'warning'
            });
            this.is_checking_shipping_tracking = false;
            continue;
          }
        } catch (e) { console.error(e); }
        this.is_checking_shipping_tracking = false;
        this.shippingStore.shipping.shipping_tracking_number.push(prefixed);
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.trackingNumberInput?.focus(); });
    },
    addNewOnpTrackingNumber(event) {
      let inputValue = event.target.value.trim();
      if (!inputValue) return;
      const numbersToAdd = inputValue.includes(' ') ? inputValue.split(' ') : [inputValue];
      for (const input of numbersToAdd) {
        if (!this.shippingStore.shipping.tracking_onp_numbers.includes(input)) {
          this.shippingStore.shipping.tracking_onp_numbers.push(input);
        }
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.onpTrackingInput?.focus(); });
    },
    async createClientIfNew() {
      const search = (this.clientSearch || '').trim();
      if (!search) return;
      const existing = this.auth.client.list.find(c => c.name.toLowerCase() === search.toLowerCase());
      if (existing) {
        this.shippingStore.shipping.client_id = existing.id;
        this.clientSearch = '';
        this.$nextTick(() => { this.$refs.clientInput?.focus(); });
        return;
      }
      try {
        const res = await axios.post('/api/clients', { name: search });
        const newClient = res.data.client;
        this.auth.client.list.push(newClient);
        this.shippingStore.shipping.client_id = newClient.id;
        this.clientSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('client_created', { name: newClient.name }), color: 'success' });
        this.$nextTick(() => { this.$refs.clientInput?.focus(); });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('client_create_error'), color: 'error' });
      }
    },
    async createShipIfNew() {
      const search = (this.shipSearch || '').trim();
      if (!search) return;
      const existing = this.auth.ship.list.find(s => s.name.toLowerCase() === search.toLowerCase());
      if (existing) {
        this.shippingStore.shipping.ship_id = existing.id;
        this.shipSearch = '';
        this.$nextTick(() => { this.$refs.shipInput?.blur(); });
        return;
      }
      try {
        const res = await axios.post('/api/ships', { name: search });
        const newShip = res.data.ship;
        this.auth.ship.list.push(newShip);
        this.shippingStore.shipping.ship_id = newShip.id;
        this.shipSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('ship_created', { name: newShip.name }), color: 'success' });
        this.$nextTick(() => { this.$refs.shipInput?.blur(); });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('ship_create_error'), color: 'error' });
      }
    },
    async createTruckIfNew() {
      const search = (this.truckSearch || '').trim();
      if (!search) return;
      const existing = this.auth.truck.list.find(t => t.registration_plate.toLowerCase() === search.toLowerCase());
      if (existing) {
        if (!this.shippingStore.shipping.truck_ids.includes(existing.id)) {
          this.shippingStore.shipping.truck_ids.push(existing.id);
        }
        this.truckSearch = '';
        this.$nextTick(() => { this.$refs.truckInput?.focus(); });
        return;
      }
      try {
        const res = await axios.post('/api/truck', { registration_plate: search });
        const newTruck = res.data.truck;
        this.auth.truck.list.push(newTruck);
        this.shippingStore.shipping.truck_ids.push(newTruck.id);
        this.truckSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('truck_created', { name: newTruck.registration_plate }), color: 'success' });
        this.$nextTick(() => { this.$refs.truckInput?.focus(); });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('truck_create_error'), color: 'error' });
      }
    },
  },
};
</script>

<style scoped>
.shipping-additional-fields { font-family: 'Inter', sans-serif; }
.text-shipping { color: #4643a0 !important; }
</style>

