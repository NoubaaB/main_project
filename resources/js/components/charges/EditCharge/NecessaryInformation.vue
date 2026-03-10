<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <v-row class="text-center text-info">
            <v-col cols="2">
              <v-btn icon="mdi-sticker-plus-outline" variant="tonal" color="info" class="mr-2"></v-btn>
            </v-col>
            <v-divider class="text-info" vertical></v-divider>
            <v-col class="text-left">
              <h2 class="d-inline">{{ $t("charge_edit_interface") }}</h2>
            </v-col>
          </v-row>
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col class="d-flex align-center">
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <v-icon class="text-info">mdi-numeric-1-circle-outline</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>{{$t("necessary_information")}}</span>
                  </v-col>
                </v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
            </v-col>
          </v-row>
          <v-row class="text-center">
            <v-col>
              <v-autocomplete
                v-model="_charge.edit_charge.charge_category_id"
                :items="_charge.charge_categories"
                item-value="id"
                :rules="[!v$._charge.edit_charge.charge_category_id.$invalid ]"
                :hide-details="v$._charge.edit_charge.charge_category_id.$errors.length==0"
                :error-messages="v$._charge.edit_charge.charge_category_id.$errors.map(e => e.$message)"
                @blur="v$._charge.edit_charge.charge_category_id.$touch()"
                :item-title="`name_${main.lang.short}`"
                key="id"
                :label="$t('charge_category')"
                chips
                density="compact"
                variant="outlined"
                rounded
                color="info"
                class="text-info"
                >
                  <!-- Custom Chips Slot -->
                  <template #chip="{ item, index }">
                    <v-chip
                      :key="index"
                      class="text-white"
                      color="info accent-4"
                      variant="tonal"
                    >
                      {{ item.title }}
                    </v-chip>
                  </template>
              </v-autocomplete>
            </v-col>
            <v-col cols="2" class="py-2 pl-0">
              <v-btn
                variant="tonal"
                color="info"
                icon="mdi-notebook-plus-outline"
                @click="dialog_note = !dialog_note">
              </v-btn>
            </v-col>
          </v-row>
          <v-row class="text-center">
            <!-- Date -->
            <v-col>
              <DatePicker  
                :is-dark="is_dark"
                expanded
                :max-date="new Date()"
                v-model="_charge.edit_charge.date"
                :locale="main.lang.short"
                mode="date"
              />
            </v-col>
          </v-row>
          <v-row>
            <v-col class="d-flex align-center">
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <v-icon class="text-info">mdi-numeric-2-circle-outline</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>{{$t("addtional_information")}}</span>
                  </v-col>
                </v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
            </v-col>
          </v-row>
          <v-row>
            <v-col>
              <v-btn
              block
              variant="tonal"
              color="info"
              rounded
              @click="_charge.showAdditionalInfo = !_charge.showAdditionalInfo"
              >
              <v-icon>
                {{ _charge.showAdditionalInfo ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
              </v-icon>
              {{ $t("expand") }}
              </v-btn>
            </v-col>
          </v-row>

          <v-expand-transition>
            <v-row v-show="_charge.showAdditionalInfo" class="pt-2">
              <!-- Tracking Numbers -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-if="_charge.getCharge.enable_tracking_numbers"
                  v-model="_charge.edit_charge.tracking_numbers"
                  :items="_charge.edit_charge.tracking_numbers"
                  :rules="[!v$._charge.edit_charge.tracking_numbers.$invalid ]"
                  :hide-details="v$._charge.edit_charge.tracking_numbers.$errors.length==0"
                  :error-messages="v$._charge.edit_charge.tracking_numbers.$errors.map(e => e.$message)"
                  @blur="v$._charge.edit_charge.tracking_numbers.$touch()"
                  :label="$t('tracking_numbers')"
                  placeholder="ex :   --- --- --- --- ---"
                  multiple
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  clearable
                  @keydown.enter.prevent="addNewTrackingNumber"
                  color="info"
                  class="text-info"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="_charge.edit_charge.tracking_numbers = _charge.edit_charge.tracking_numbers.filter(num => num !== item.value)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
              <!-- Tracking ONP Numbers -->

              <v-col cols="12" md="6">
                <v-autocomplete
                  v-if="_charge.getCharge.enable_tracking_onp_numbers"
                  v-model="_charge.edit_charge.tracking_onp_numbers"
                  :items="_charge.edit_charge.tracking_onp_numbers"
                  :label="$t('tracking_onp_numbers')"
                  multiple
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  placeholder="ex :   --- --- --- --- ---"
                  clearable
                  @keydown.enter.prevent="addNewOnpTrackingNumber"
                  color="info"
                  class="text-info"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="_charge.edit_charge.tracking_onp_numbers = _charge.edit_charge.tracking_onp_numbers.filter(num => num !== item.value)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
  
              <!-- Supplier -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-if="_charge.getCharge.enable_supplier_process_id"
                  v-model="_charge.edit_charge.supplier_ids"
                  :items="_supplier.list"
                  :rules="[!v$._charge.edit_charge.supplier_ids.$invalid ]"
                  :hide-details="v$._charge.edit_charge.supplier_ids.$errors.length==0"
                  :error-messages="v$._charge.edit_charge.supplier_ids.$errors.map(e => e.$message)"
                  @blur="v$._charge.edit_charge.supplier_ids.$touch()"
                  item-value="id"
                  item-title="name"
                  multiple
                  key="id"
                  placeholder="ex :   Golden Logictics"
                  @keydown.enter.prevent="addNewSupplier"
                  :label="$t('suppliers')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  clearable
                  color="info"
                  class="text-info"
                  :disabled="is_posting_suppliers"
                  :loading="is_posting_suppliers"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="_charge.edit_charge.supplier_ids = _charge.edit_charge.supplier_ids.filter(num => num !== item.value)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
      
              <!-- Fishery -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-if="_charge.getCharge.enable_fishery_id"
                  :rules="[!v$._charge.edit_charge.fishery_id.$invalid ]"
                  :hide-details="v$._charge.edit_charge.fishery_id.$errors.length==0"
                  :error-messages="v$._charge.edit_charge.fishery_id.$errors.map(e => e.$message)"
                  @blur="v$._charge.edit_charge.fishery_id.$touch()"
                  density="compact"
                  color="info"
                  variant="outlined"
                  rounded
                  chips
                  v-model="_charge.edit_charge.fishery_id"
                  :items="_fishery.getList"
                  :label="$t('fishery')"
                  :item-title="`name_${main.lang.short}`"
                  item-value="id"
                  class="text-info"
                >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        variant="tonal"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
      
              <!-- Origin -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-if="_charge.getCharge.enable_origin_id"
                  :rules="[!v$._charge.edit_charge.origin_id.$invalid ]"
                  :hide-details="v$._charge.edit_charge.origin_id.$errors.length==0"
                  :error-messages="v$._charge.edit_charge.origin_id.$errors.map(e => e.$message)"
                  @blur="v$._charge.edit_charge.origin_id.$touch()"
                  density="compact"
                  color="info"
                  variant="outlined"
                  rounded
                  chips
                  v-model="_charge.edit_charge.origin_id"
                  :items="_origin.getList"
                  :label="$t('origin')"
                  item-title="name"
                  item-value="id"
                  class="text-info"
                >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        variant="tonal"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>              
                </v-autocomplete>
              </v-col>
  
              <!-- Registratio Plate -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-if="_charge.getCharge.enable_truck_id"
                  v-model="_charge.edit_charge.truck_ids"
                  :items="_truck.list"
                  :rules="[!v$._charge.edit_charge.truck_ids.$invalid ]"
                  :hide-details="v$._charge.edit_charge.truck_ids.$errors.length==0"
                  :error-messages="v$._charge.edit_charge.truck_ids.$errors.map(e => e.$message)"
                  @blur="v$._charge.edit_charge.truck_ids.$touch()"
                  item-value="id"
                  item-title="registration_plate"
                  multiple
                  key="id"
                  :label="$t('truck_id')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  clearable
                  placeholder="ex :   11111-A-11"
                  @keydown.enter.prevent="addNewTruck"
                  color="info"
                  class="text-info"
                  :disabled="is_posting_trucks"
                  :loading="is_posting_trucks"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="_charge.edit_charge.truck_ids = _charge.edit_charge.truck_ids.filter(num => num !== item.value)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
            </v-row>
          </v-expand-transition>

        </v-card-text>
        <v-divider class="border-opacity-25" color="info"></v-divider>
        <!-- Submit Button -->
        <v-card-actions cols="12">
          <v-row class="text-center pt-1">
            <v-col>
              <v-btn :disabled="v$.$invalid" block variant="tonal" rounded color="info" @click="submit">{{$t('submit')}}</v-btn>
            </v-col>
          </v-row>
        </v-card-actions>
      </v-card>
      <v-dialog
        v-model="dialog_note"
        width="400"
      >
        <v-card
          rounded="xl"
          class="pa-1"
        >
          <v-card-title>
            <v-btn
              variant="tonal"
              color="info"
              class="mx-2"
              icon="mdi-notebook-plus-outline">
            </v-btn>
            <span class="text-h6 font-weight-bold">{{$t('charge_note')}}</span>
          </v-card-title>
          <v-divider class="border-opacity-25" color="info"></v-divider>
          <v-card-text class="px-0 pb-0">
            <v-textarea
              v-model="_charge.edit_charge.note"
              :label="$t('charge_note')"
              density="compact"
              variant="outlined"
              rounded
              counter
              color="info"
              >
              </v-textarea>
          </v-card-text>
          <v-divider class="border-opacity-25" color="info"></v-divider>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn
              variant="tonal"
              color="info"
              rounded
              @click="dialog_note = false"
            >
              {{ $t("close") }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useCharge } from '@/stores/Charge';
import { useSupplier } from '@/stores/Supplier';
import { useTruck } from '@/stores/Truck';
import { useFishery } from '@/stores/Fishery';
import { useOrigin } from '@/stores/Origin';
import { useChargeCategory } from '@/stores/ChargeCategory';
import { useVuelidate } from '@vuelidate/core';
import { required , requiredIf } from '@vuelidate/validators';

export default {
  data: function () {
    return {
      v$ : useVuelidate(),
      dialog_note:false,
      searchInputTrackingNumbers: '',
      is_posting_suppliers: false,
      is_posting_trucks: false
    }
  },
  validations() {
    return {
      _charge: {
        edit_charge: {
          charge_category_id: { required },
          date: { required },
          tracking_numbers: { requiredIf: requiredIf(() =>this._charge.getCharge.enable_tracking_numbers) },
          tracking_onp_numbers: { requiredIf: requiredIf(() =>this._charge.getCharge.enable_tracking_onp_numbers) },
          supplier_ids: { requiredIf: requiredIf(() =>this._charge.getCharge.enable_supplier_process_id) },
          truck_ids: { requiredIf: requiredIf(() =>this._charge.getCharge.enable_truck_id) },
          fishery_id: { requiredIf: requiredIf(() =>this._charge.getCharge.enable_fishery_id) },
          origin_id: { requiredIf: requiredIf(() =>this._charge.getCharge.enable_origin_id) },
        }
      }
    }
  },
  computed: {
    main() {
      return useMain();
    },
    charge_category() {
      return useChargeCategory();
    },
    is_dark() {
      return useMain().is_dark;
    },
    _charge() {
      return useCharge();
    },
    _fishery() {
      return useFishery();
    },
    _supplier() {
      return useSupplier();
    },
    _origin() {
      return useOrigin();
    },
    _truck() {
      return useTruck();
    },
  },
  methods: {
    submit: function () {
      this._charge.submitCharge();
    },
    addNewTrackingNumber(event) {
      let inputValue = event.target.value.trim()
      if (inputValue && !this._charge.edit_charge.tracking_numbers.includes(inputValue)) {
        if (inputValue.search(" ")>0) {
          let inputs = inputValue.split(" ");
          inputs.forEach(input => {
            if (!this._charge.edit_charge.tracking_numbers.find(n => n == input)) {
              this._charge.edit_charge.tracking_numbers.push(input)
            }
          })
        } else {
          this._charge.edit_charge.tracking_numbers.push(inputValue)
        }
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    removeTrackingNumber(item) {
      this._charge.edit_charge.charge_category_id = null;
    },
    addNewOnpTrackingNumber(event) {
      let inputValue = event.target.value.trim()
      if (inputValue && !this._charge.edit_charge.tracking_onp_numbers.includes(inputValue)) {
        if (inputValue.search(" ")>0) {
          let inputs = inputValue.split(" ");
          inputs.forEach(input => {
            if (!this._charge.edit_charge.tracking_onp_numbers.find(n => n == input)) {
              this._charge.edit_charge.tracking_onp_numbers.push(input)
            }
          })
        } else {
          this._charge.edit_charge.tracking_onp_numbers.push(inputValue)      }
        }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    addNewSupplier: async function (event) {
      this.is_posting_suppliers = true;
      let inputValue = event.target.value.trim()
      if (inputValue && !this._charge.edit_charge.supplier_ids.includes(inputValue)) {
        let supplier = this._supplier.list.find(n => n.name == inputValue);
        if (supplier == undefined && this._charge.edit_charge.supplier_ids.find(n => n == supplier?.id) == undefined) {
          let supplier_id = await this._supplier.add(inputValue);
          this.is_posting_suppliers = false;
          this._charge.edit_charge.supplier_ids.push(supplier_id);
        } else if (supplier && this._charge.edit_charge.supplier_ids.find(n => n == supplier?.id) == undefined) {
          this._charge.edit_charge.supplier_ids.push(supplier.id);
        }
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    addNewTruck: async function (event) {
      this.is_posting_trucks = true;
      let inputValue = event.target.value.trim()
      if (inputValue && !this._charge.edit_charge.truck_ids.includes(inputValue)) {
        if (inputValue.search(" ")>0) {
          let inputs = inputValue.split(" ");
          await inputs.forEach(async input => {
            let truck = this._truck.list.find(n => n.registration_plate == input)
            if (truck == undefined && this._charge.edit_charge.truck_ids.find(n => n == truck?.id) == undefined) {
              let truck_id = await this._truck.add(input);
              this.is_posting_trucks = false;
              this._charge.edit_charge.truck_ids.push(truck_id);
            } else if(truck && this._charge.edit_charge.truck_ids.find(n => n != truck?.id)) {
              this._charge.edit_charge.truck_ids.push(truck.id);
            }
          })
        } else {
          let truck = this._truck.list.find(n => n.registration_plate == inputValue);
          if (truck == undefined && this._charge.edit_charge.truck_ids.find(n => n == truck?.id) == undefined) {
            let truck_id = await this._truck.add(inputValue);
            this.is_posting_trucks = false;
            this._charge.edit_charge.truck_ids.push(truck_id);
          } else if (truck && this._charge.edit_charge.truck_ids.find(n => n == truck?.id) == undefined) {
            this._charge.edit_charge.truck_ids.push(truck.id);
          }
        }
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    removeOnpTrackingNumber(item) {
      this._charge.tracking_onp_numbers = this._charge.tracking_onp_numbers.filter(num => num !== item);
    },
  },
}
</script>

<style>

</style>