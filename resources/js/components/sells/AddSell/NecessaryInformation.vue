<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <v-row class="text-center text-info">
            <v-col cols="2">
              <v-btn icon="mdi-sticker-plus-outline" variant="tonal" color="info" class="mr-2"></v-btn>
            </v-col>
            <v-divider class="text-info" vertical></v-divider>
            <v-col class="text-left">
              <h2 class="d-inline">{{ $t("sell_create_interface") }}</h2>
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
                v-model="_sell.sell.sell_category_id"
                :items="_sell.sell_categories"
                item-value="id"
                :rules="[!v$._sell.sell.sell_category_id.$invalid ]"
                :hide-details="v$._sell.sell.sell_category_id.$errors.length==0"
                :error-messages="v$._sell.sell.sell_category_id.$errors.map(e => e.$message)"
                @blur="v$._sell.sell.sell_category_id.$touch()"
                :item-title="`name_${main.lang.short}`"
                key="id"
                :label="$t('sell_category')"
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
                v-model="_sell.sell.date"
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
              @click="_sell.showAdditionalInfo = !_sell.showAdditionalInfo"
              >
              <v-icon>
                {{ _sell.showAdditionalInfo ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
              </v-icon>
              {{ $t("expand") }}
              </v-btn>
            </v-col>
          </v-row>

          <v-expand-transition>
            <div v-show="_sell.showAdditionalInfo" class="pt-2">
              <div class="d-flex flex-wrap" style="gap: 0; margin: 0 -12px;">
                <!-- Tracking Numbers -->
                <div v-if="_sell.getSell.enable_tracking_numbers" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
                  <v-autocomplete
                    ref="trackingNumberInput"
                    v-model="_sell.sell.tracking_numbers"
                    :items="_sell.sell.tracking_numbers"
                    :rules="[!v$._sell.sell.tracking_numbers.$invalid ]"
                    :hide-details="v$._sell.sell.tracking_numbers.$errors.length==0"
                    :error-messages="v$._sell.sell.tracking_numbers.$errors.map(e => e.$message)"
                    @blur="v$._sell.sell.tracking_numbers.$touch()"
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
                    :disabled="is_checking_tracking"
                    :loading="is_checking_tracking"
                    >
                      <!-- Custom Chips Slot -->
                      <template #chip="{ item, index }">
                        <v-chip
                          :key="index"
                          class="text-white"
                          color="info accent-4"
                          closable
                          variant="tonal"
                          @click:close="_sell.sell.tracking_numbers = _sell.sell.tracking_numbers.filter(num => num !== item.value)"
                        >
                          {{ item.title }}
                        </v-chip>
                      </template>
                  </v-autocomplete>
                </div>

                <!-- Tracking ONP Numbers -->
                <div v-if="_sell.getSell.enable_tracking_onp_numbers" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
                  <v-autocomplete
                    v-model="_sell.sell.tracking_onp_numbers"
                    :items="_sell.sell.tracking_onp_numbers"
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
                          @click:close="_sell.sell.tracking_onp_numbers = _sell.sell.tracking_onp_numbers.filter(num => num !== item.value)"
                        >
                          {{ item.title }}
                        </v-chip>
                      </template>
                  </v-autocomplete>
                </div>

                <!-- Client -->
                <div v-if="_sell.getSell.enable_client_process_id" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
                  <v-autocomplete
                    v-model="_sell.sell.client_ids"
                    :items="_client.list"
                    :rules="[!v$._sell.sell.client_ids.$invalid ]"
                    :hide-details="v$._sell.sell.client_ids.$errors.length==0"
                    :error-messages="v$._sell.sell.client_ids.$errors.map(e => e.$message)"
                    @blur="v$._sell.sell.client_ids.$touch()"
                    item-value="id"
                    item-title="name"
                    multiple
                    key="id"
                    placeholder="ex :   Golden Logictics"
                    @keydown.enter.prevent="addNewClient"
                    :label="$t('clients')"
                    chips
                    density="compact"
                    variant="outlined"
                    rounded
                    clearable
                    color="info"
                    class="text-info"
                    :disabled="is_posting_clients"
                    :loading="is_posting_clients"
                    >
                      <!-- Custom Chips Slot -->
                      <template #chip="{ item, index }">
                        <v-chip
                          :key="index"
                          class="text-white"
                          color="info accent-4"
                          closable
                          variant="tonal"
                          @click:close="_sell.sell.client_ids = _sell.sell.client_ids.filter(num => num !== item.value)"
                        >
                          {{ item.title }}
                        </v-chip>
                      </template>
                  </v-autocomplete>
                </div>

                <!-- Fishery -->
                <div v-if="_sell.getSell.enable_fishery_id" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
                  <v-autocomplete
                    :rules="[!v$._sell.sell.fishery_id.$invalid ]"
                    :hide-details="v$._sell.sell.fishery_id.$errors.length==0"
                    :error-messages="v$._sell.sell.fishery_id.$errors.map(e => e.$message)"
                    @blur="v$._sell.sell.fishery_id.$touch()"
                    density="compact"
                    color="info"
                    variant="outlined"
                    rounded
                    chips
                    v-model="_sell.sell.fishery_id"
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
                </div>

                <!-- Origin -->
                <div v-if="_sell.getSell.enable_origin_id" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
                  <v-autocomplete
                    :rules="[!v$._sell.sell.origin_id.$invalid ]"
                    :hide-details="v$._sell.sell.origin_id.$errors.length==0"
                    :error-messages="v$._sell.sell.origin_id.$errors.map(e => e.$message)"
                    @blur="v$._sell.sell.origin_id.$touch()"
                    density="compact"
                    color="info"
                    variant="outlined"
                    rounded
                    chips
                    v-model="_sell.sell.origin_id"
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
                </div>

                <!-- Registration Plate -->
                <div v-if="_sell.getSell.enable_truck_id" class="pa-3" style="flex: 0 0 50%; max-width: 50%;">
                  <v-autocomplete
                    v-model="_sell.sell.truck_ids"
                    :items="_truck.list"
                    :rules="[!v$._sell.sell.truck_ids.$invalid ]"
                    :hide-details="v$._sell.sell.truck_ids.$errors.length==0"
                    :error-messages="v$._sell.sell.truck_ids.$errors.map(e => e.$message)"
                    @blur="v$._sell.sell.truck_ids.$touch()"
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
                          @click:close="_sell.sell.truck_ids = _sell.sell.truck_ids.filter(num => num !== item.value)"
                        >
                          {{ item.title }}
                        </v-chip>
                      </template>
                  </v-autocomplete>
                </div>
              </div>
            </div>
          </v-expand-transition>

        </v-card-text>
        <v-divider class="border-opacity-25" color="info"></v-divider>
        <!-- Submit Button -->
        <v-card-actions cols="12">
          <v-row class="text-center pt-1">
            <v-col>
              <v-btn :disabled="v$.$invalid" block variant="tonal" rounded color="info" @click="submit">{{$t('submit')}}</v-btn>
            </v-col>
            <v-col class="pl-1">
              <v-btn block variant="tonal" rounded color="teal" @click="archiveSell" prepend-icon="mdi-archive-arrow-down">{{$t('archive')}}</v-btn>
            </v-col>
            <v-col class="pl-1">
              <v-btn block variant="outlined" rounded color="orange" class="rounded-s-lg" @click="reset">{{$t('cancel')}}</v-btn>
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
            <span class="text-h6 font-weight-bold">{{$t('sell_note')}}</span>
          </v-card-title>
          <v-divider class="border-opacity-25" color="info"></v-divider>
          <v-card-text class="px-0 pb-0">
            <v-textarea
              v-model="_sell.sell.note"
              :label="$t('sell_note')"
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
import { useSell } from '@/stores/Sell';
import { useClient } from '@/stores/Client';
import { useTruck } from '@/stores/Truck';
import { useFishery } from '@/stores/Fishery';
import { useOrigin } from '@/stores/Origin';
import { useSellCategory } from '@/stores/SellCategory';
import { useArchive } from '@/stores/Archive';
import { useVuelidate } from '@vuelidate/core';
import { required , requiredIf } from '@vuelidate/validators';

export default {
  data: function () {
    return {
      v$ : useVuelidate(),
      dialog_note:false,
      searchInputTrackingNumbers: '',
      is_posting_clients: false,
      is_posting_trucks: false,
      is_checking_tracking: false
    }
  },
  validations() {
    return {
      _sell: {
        sell: {
          sell_category_id: { required },
          date: { required },
          tracking_numbers: { requiredIf: requiredIf(() =>this._sell.getSell.enable_tracking_numbers) },
          tracking_onp_numbers: { requiredIf: requiredIf(() =>this._sell.getSell.enable_tracking_onp_numbers) },
          client_ids: { requiredIf: requiredIf(() =>this._sell.getSell.enable_client_process_id) },
          truck_ids: { requiredIf: requiredIf(() =>this._sell.getSell.enable_truck_id) },
          fishery_id: { requiredIf: requiredIf(() =>this._sell.getSell.enable_fishery_id) },
          origin_id: { requiredIf: requiredIf(() =>this._sell.getSell.enable_origin_id) },
        }
      }
    }
  },
  computed: {
    main() {
      return useMain();
    },
    sell_category() {
      return useSellCategory();
    },
    is_dark() {
      return useMain().is_dark;
    },
    _sell() {
      return useSell();
    },
    _fishery() {
      return useFishery();
    },
    _client() {
      return useClient();
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
      this._sell.submitSell(this.$router);
    },
    getYearPrefix() {
      const d = this._sell.sell.date;
      if (!d) return '';
      const date = new Date(d);
      const yy = String(date.getFullYear()).slice(-2);
      return yy;
    },
    prefixWithYear(value) {
      const yy = this.getYearPrefix();
      if (!yy) return value;
      const prefix = yy + '-';
      // Don't double-prefix if it already starts with "YY-"
      if (value.startsWith(prefix)) return value;
      return prefix + value;
    },
    addNewTrackingNumber: async function (event) {
      let inputValue = event.target.value.trim()
      if (inputValue && !this._sell.sell.tracking_numbers.includes(inputValue)) {
        const numbersToAdd = inputValue.search(" ") > 0 ? inputValue.split(" ") : [inputValue];
        for (const input of numbersToAdd) {
          const prefixed = this.prefixWithYear(input);
          if (this._sell.sell.tracking_numbers.find(n => n == prefixed)) continue;
          // Check uniqueness against backend
          this.is_checking_tracking = true;
          const result = await this._sell.checkTrackingNumberUnique(
            prefixed,
            this._sell.sell.sell_category_id
          );
          this.is_checking_tracking = false;
          if (!result.unique) {
            const s = result.sell;
            this.main.add_snackbar({
              show: true,
              message: this.$t('tracking_number_exists', { tracking_number: prefixed })
                + ' — ' + this.$t('sell_tracking_number_duplicate_details', {
                  tracking_numbers: (s.tracking_numbers || []).join(', '),
                  date: this.main.formateDate(s.date),
                  client: (s.clients || []).join(', ') || this.$t('n/a'),
                  weight: this.main.formatDisplayNumber(s.total_weight),
                  price: this.main.formatDisplayNumber(s.total_price),
                  currency: this.main.currency
                }),
              color: 'warning'
            });
            continue;
          }
          this._sell.sell.tracking_numbers.push(prefixed);
        }
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
      this.$nextTick(() => {
        this.$refs.trackingNumberInput?.focus();
      });
    },
    removeTrackingNumber(item) {
      this._sell.sell.sell_category_id = null;
    },
    addNewOnpTrackingNumber(event) {
      let inputValue = event.target.value.trim()
      if (inputValue && !this._sell.sell.tracking_onp_numbers.includes(inputValue)) {
        if (inputValue.search(" ")>0) {
          let inputs = inputValue.split(" ");
          inputs.forEach(input => {
            const prefixed = this.prefixWithYear(input);
            if (!this._sell.sell.tracking_onp_numbers.find(n => n == prefixed)) {
              this._sell.sell.tracking_onp_numbers.push(prefixed)
            }
          })
        } else {
          const prefixed = this.prefixWithYear(inputValue);
          if (!this._sell.sell.tracking_onp_numbers.includes(prefixed)) {
            this._sell.sell.tracking_onp_numbers.push(prefixed)
          }
        }
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    addNewClient: async function (event) {
      this.is_posting_clients = true;
      let inputValue = event.target.value.trim()
      if (inputValue && !this._sell.sell.client_ids.includes(inputValue)) {
        let client = this._client.list.find(n => n.name == inputValue);
        if (client == undefined && this._sell.sell.client_ids.find(n => n == client?.id) == undefined) {
          let client_id = await this._client.add(inputValue);
          this.is_posting_clients = false;
          this._sell.sell.client_ids.push(client_id);
        } else if (client && this._sell.sell.client_ids.find(n => n == client?.id) == undefined) {
          this._sell.sell.client_ids.push(client.id);
        }
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    addNewTruck: async function (event) {
      this.is_posting_trucks = true;
      let inputValue = event.target.value.trim()
      if (inputValue && !this._sell.sell.truck_ids.includes(inputValue)) {
        if (inputValue.search(" ")>0) {
          let inputs = inputValue.split(" ");
          await inputs.forEach(async input => {
            let truck = this._truck.list.find(n => n.registration_plate == input)
            if (truck == undefined && this._sell.sell.truck_ids.find(n => n == truck?.id) == undefined) {
              let truck_id = await this._truck.add(input);
              this.is_posting_trucks = false;
              this._sell.sell.truck_ids.push(truck_id);
            } else if(truck && this._sell.sell.truck_ids.find(n => n != truck?.id)) {
              this._sell.sell.truck_ids.push(truck.id);
            }
          })
        } else {
          let truck = this._truck.list.find(n => n.registration_plate == inputValue);
          if (truck == undefined && this._sell.sell.truck_ids.find(n => n == truck?.id) == undefined) {
            let truck_id = await this._truck.add(inputValue);
            this.is_posting_trucks = false;
            this._sell.sell.truck_ids.push(truck_id);
          } else if (truck && this._sell.sell.truck_ids.find(n => n == truck?.id) == undefined) {
            this._sell.sell.truck_ids.push(truck.id);
          }
        }
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    removeOnpTrackingNumber(item) {
      this._sell.tracking_onp_numbers = this._sell.tracking_onp_numbers.filter(num => num !== item);
    },
    update: function(){
      this._sell.UpdateSell();
    },

    reset: function () {
      this._sell.reset();
    },
    archiveSell: function () {
      const archive = useArchive();
      archive.archiveSell(this._sell.sell);
      this._sell.reset();
    },
  },
  watch: {
    "_sell.sell": {
      handler(_) {
        // Save Sell to localStorage whenever it changes
        this.update();
      },
      deep: true,
      immediate: true
    },
    '_sell.showAdditionalInfo': function (_) {
      this.update();
    },
    '_sell.sell.sell_category_id': function (_) {
      this.update();
    },
  }
}
</script>

<style>

</style>