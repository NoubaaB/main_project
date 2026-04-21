<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <v-row class="text-center text-teal">
            <v-col cols="2">
              <v-btn icon="mdi-cog-outline" variant="tonal" color="teal" class="mr-2"></v-btn>
            </v-col>
            <v-divider class="text-teal" vertical></v-divider>
            <v-col class="text-left">
              <h2 class="d-inline">{{ $t("production_create_interface") }}</h2>
            </v-col>
          </v-row>
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col class="d-flex align-center">
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="teal"></v-divider>
              </div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0"><v-col class="pa-0">
                  <v-icon class="text-teal">mdi-numeric-1-circle-outline</v-icon>
                </v-col></v-row>
                <v-row class="pa-0"><v-col class="pa-0">
                  <span>{{$t("necessary_information")}}</span>
                </v-col></v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="teal"></v-divider>
              </div>
            </v-col>
          </v-row>

          <!-- Source Charge Info -->
          <v-alert v-if="sourceCharge" type="info" variant="tonal" density="compact" class="my-4 rounded-lg" icon="mdi-link-variant">
            <div class="text-caption font-weight-bold">{{ $t('linked_charge') }}</div>
            <div class="text-caption">
              {{ $t('tracking_numbers') }}: <v-chip v-for="tn in (sourceCharge.tracking_numbers || [])" :key="tn" size="x-small" color="info" class="mr-1" variant="tonal">{{ tn }}</v-chip>
              <span class="ml-2">{{ $t('date') }}: {{ main.formateDate(sourceCharge.date) }}</span>
              <span class="ml-2">{{ $t('total_weight') }}: {{ main.formatDisplayNumber(sourceCharge.total_weight) }} Kg</span>
            </div>
          </v-alert>

          <v-row class="text-center">
            <v-col>
              <v-autocomplete
                v-model="_production.production.production_category_id"
                :items="_production.production_categories"
                item-value="id"
                :item-title="`name_${main.lang.short}`"
                :label="$t('production_category')"
                chips
                density="compact"
                variant="outlined"
                rounded
                color="teal"
                class="text-teal"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="teal" closable variant="tonal">
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </v-col>
          </v-row>

          <!-- Date -->
          <v-row class="text-center">
            <v-col>
              <DatePicker  
                :is-dark="is_dark"
                expanded
                :max-date="new Date()"
                v-model="_production.production.date"
                :locale="main.lang.short"
                mode="date"
              />
            </v-col>
          </v-row>

          <div class="d-flex flex-wrap" style="gap: 0; margin: 0 -12px;">

            <!-- Suppliers -->
            <div class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
              <v-autocomplete
                v-model="_production.production.supplier_ids"
                :items="_supplier.list"
                item-value="id"
                item-title="name"
                :label="$t('suppliers')"
                multiple
                chips
                density="compact"
                variant="outlined"
                rounded
                color="teal"
                class="text-teal"
                clearable
                @keydown.enter.prevent="addNewSupplier"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="teal" closable variant="tonal" @click:close="_production.production.supplier_ids = _production.production.supplier_ids.filter(id => id !== item.value)">
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </div>

            <!-- Tracking Numbers -->
            <div v-if="currentCategory.enable_reception_tracking_number" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
              <v-autocomplete
                v-model="_production.production.tracking_numbers"
                :items="_production.production.tracking_numbers"
                :label="$t('tracking_numbers')"
                multiple
                chips
                density="compact"
                variant="outlined"
                rounded
                hide-details
                placeholder="ex :   --- --- --- --- ---"
                clearable
                @keydown.enter.prevent="addNewTrackingNumber"
                color="teal"
                class="text-teal"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="blue" closable variant="tonal" @click:close="_production.production.tracking_numbers = _production.production.tracking_numbers.filter(num => num !== item.value)">
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </div>

            <!-- Tracking ONP Numbers -->
            <div v-if="currentCategory.enable_tracking_onp_numbers" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
              <v-autocomplete
                v-model="_production.production.tracking_onp_numbers"
                :items="_production.production.tracking_onp_numbers"
                :label="$t('tracking_onp_numbers')"
                multiple
                chips
                density="compact"
                variant="outlined"
                rounded
                hide-details
                placeholder="ex :   --- --- --- --- ---"
                clearable
                @keydown.enter.prevent="addNewTrackingOnpNumber"
                color="teal"
                class="text-teal"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="cyan" closable variant="tonal" @click:close="_production.production.tracking_onp_numbers = _production.production.tracking_onp_numbers.filter(num => num !== item.value)">
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </div>

            <!-- Production Tracking Numbers -->
            <div v-if="currentCategory.enable_production_tracking_numbers" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
              <v-autocomplete
                ref="productionTrackingInput"
                v-model="_production.production.production_tracking_numbers"
                :items="_production.production.production_tracking_numbers"
                :label="$t('production_tracking_numbers')"
                multiple
                chips
                density="compact"
                variant="outlined"
                rounded
                hide-details
                placeholder="ex :   --- --- --- --- ---"
                clearable
                @keydown.enter.prevent="addNewProductionTrackingNumber"
                color="teal"
                class="text-teal"
                :disabled="is_checking_ptn"
                :loading="is_checking_ptn"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="teal" closable variant="tonal" @click:close="_production.production.production_tracking_numbers = _production.production.production_tracking_numbers.filter(num => num !== item.value)">
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </div>

            <!-- Fishery -->
            <div v-if="currentCategory.enable_fishery_id" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
              <v-autocomplete
                v-model="_production.production.fishery_id"
                :items="_fishery.list"
                item-value="id"
                item-title="name"
                :label="$t('fishery')"
                density="compact"
                variant="outlined"
                rounded
                color="teal"
                class="text-teal"
                clearable
              ></v-autocomplete>
            </div>

            <!-- Origin -->
            <div v-if="currentCategory.enable_origin_id" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
              <v-autocomplete
                v-model="_production.production.origin_id"
                :items="_origin.list"
                item-value="id"
                item-title="name"
                :label="$t('origin')"
                density="compact"
                variant="outlined"
                rounded
                color="teal"
                class="text-teal"
                clearable
              ></v-autocomplete>
            </div>

            <!-- Trucks -->
            <div v-if="currentCategory.enable_truck_id" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
              <v-autocomplete
                v-model="_production.production.truck_ids"
                :items="_truck.list"
                item-value="id"
                item-title="license_plate"
                :label="$t('truck')"
                multiple
                chips
                density="compact"
                variant="outlined"
                rounded
                color="teal"
                class="text-teal"
                clearable
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="teal" closable variant="tonal" @click:close="_production.production.truck_ids = _production.production.truck_ids.filter(id => id !== item.value)">
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </div>
          </div>

          <v-divider class="border-opacity-25 mb-3" color="teal"></v-divider>

          <!-- Submit -->
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn
              variant="tonal"
              color="teal"
              rounded
              @click="submit"
              :loading="_production.loading"
              prepend-icon="mdi-content-save"
            >
              {{ $t('save') }}
            </v-btn>
            <v-btn
              variant="tonal"
              color="teal"
              rounded
              @click="archiveProduction"
              prepend-icon="mdi-archive-arrow-down"
            >
              {{ $t('archive') }}
            </v-btn>
          </v-card-actions>
        </v-card-text>
    </v-card>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useProduction } from '@/stores/Production';
import { useCharge } from '@/stores/Charge';
import { useSupplier } from '@/stores/Supplier';
import { useTruck } from '@/stores/Truck';
import { useFishery } from '@/stores/Fishery';
import { useOrigin } from '@/stores/Origin';
import { useArchive } from '@/stores/Archive';
import axios from 'axios';

export default {
  data() {
    return {
      sourceCharge: null,
      is_checking_ptn: false,
    }
  },
  computed: {
    main() { return useMain(); },
    _production() { return useProduction(); },
    _charge() { return useCharge(); },
    _supplier() { return useSupplier(); },
    _truck() { return useTruck(); },
    _fishery() { return useFishery(); },
    _origin() { return useOrigin(); },
    currentCategory() {
      return this._production.production_category.list.find(c => c.id === this._production.production.production_category_id) || {};
    },
    is_dark() {
      return useMain().is_dark;
    },
  },
  async mounted() {
    await this._production.production_category.fetchAll();
    const chargeId = this.$route.params.chargeId;
    if (chargeId) {
      try {
        const res = await axios.get(`/api/charge/${chargeId}`);
        this.sourceCharge = res.data.charge;
      } catch (e) {
        console.error('Error loading source charge:', e);
      }
    }
  },
  methods: {
    prefixWithYear(value) {
      const yearPrefix = new Date().getFullYear().toString().slice(-2) + '-';
      if (value.startsWith(yearPrefix)) return value;
      return yearPrefix + value;
    },
    addNewTrackingNumber(event) {
      let inputValue = event.target.value.trim();
      if (inputValue) {
        const numbers = inputValue.includes(' ') ? inputValue.split(' ') : [inputValue];
        numbers.forEach(n => {
          const prefixed = this.prefixWithYear(n);
          if (!this._production.production.tracking_numbers.includes(prefixed)) {
            this._production.production.tracking_numbers.push(prefixed);
          }
        });
      }
      event.target.value = '';
    },
    addNewTrackingOnpNumber(event) {
      let inputValue = event.target.value.trim();
      if (inputValue) {
        const numbers = inputValue.includes(' ') ? inputValue.split(' ') : [inputValue];
        numbers.forEach(n => {
          const prefixed = this.prefixWithYear(n);
          if (!this._production.production.tracking_onp_numbers.includes(prefixed)) {
            this._production.production.tracking_onp_numbers.push(prefixed);
          }
        });
      }
      event.target.value = '';
    },
    async addNewProductionTrackingNumber(event) {
      let inputValue = event.target.value.trim();
      if (inputValue) {
        const numbers = inputValue.includes(' ') ? inputValue.split(' ') : [inputValue];
        for (const n of numbers) {
          const prefixed = this.prefixWithYear(n);
          if (this._production.production.production_tracking_numbers.includes(prefixed)) continue;
          // Check uniqueness against backend
          this.is_checking_ptn = true;
          const result = await this._production.checkProductionTrackingNumberUnique(prefixed);
          this.is_checking_ptn = false;
          if (!result.unique) {
            const p = result.production;
            this.main.add_snackbar({
              show: true,
              message: this.$t('production_tracking_number_exists', { tracking_number: prefixed })
                + ' — ' + this.$t('production_tracking_number_duplicate_details', {
                  date: this.main.formateDate(p.date),
                  suppliers: (p.suppliers || []).join(', ') || this.$t('n/a'),
                  weight: this.main.formatDisplayNumber(p.total_weight),
                }),
              color: 'warning'
            });
            continue;
          }
          this._production.production.production_tracking_numbers.push(prefixed);
        }
      }
      event.target.value = '';
      this.$nextTick(() => {
        this.$refs.productionTrackingInput?.focus();
      });
    },
    async addNewSupplier(event) {
      let inputValue = event.target.value.trim();
      if (inputValue) {
        let supplier = this._supplier.list.find(n => n.name == inputValue);
        if (supplier && !this._production.production.supplier_ids.includes(supplier.id)) {
          this._production.production.supplier_ids.push(supplier.id);
        }
      }
      event.target.value = '';
    },
    async submit() {
      const result = await this._production.submitProduction();
      if (result) {
        this._production.clearFormStorage();
        this.$router.push({ name: 'production_list' });
      }
    },
    archiveProduction() {
      const archive = useArchive();
      archive.archiveProduction(this._production.production);
      this._production.clearFormStorage();
      this._production.init_production();
    },
  }
}
</script>
