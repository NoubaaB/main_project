<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <span>
            Add Fresh Fish Receive
          </span>
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
                    <v-icon class="text-blue">mdi-numeric-1-circle-outline</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>Necessary Information</span>
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
                v-model="_receipt.selectedCharge"
                :items="_receipt.type_charges"
                label="Type Of Charge"
                chips
                density="compact"
                variant="outlined"
                rounded
                color="info"
                >
                  <!-- Custom Chips Slot -->
                  <template #chip="{ item, index }">
                    <v-chip
                      :key="index"
                      class="text-white"
                      color="info accent-4"
                      closable
                      variant="tonal"
                      @click:close="removeCharge(item)"
                    >
                      {{ item.title }}
                    </v-chip>
                  </template>
                </v-autocomplete>
            </v-col>
          </v-row>
          <v-row class="text-center">
            <!-- Date -->
            <v-col>
              <VDatePicker  
                :is-dark="is_dark"
                expanded
                :max-date="new Date()"
                v-model="receipt.date"
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
                    <v-icon class="text-blue">mdi-numeric-2-circle-outline</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>Addtional Information</span>
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
              @click="_receipt.showAdditionalInfo = !_receipt.showAdditionalInfo"
              >
              <v-icon>
                {{ _receipt.showAdditionalInfo ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
              </v-icon>
              expand
              </v-btn>
            </v-col>
          </v-row>

          <v-expand-transition>
            <v-row v-show="_receipt.showAdditionalInfo" class="pt-2">
              <!-- Tracking Numbers -->

              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="receipt.tracking_numbers"
                  :items="receipt.tracking_numbers"
                  label="Tracking Numbers (comma separated)"
                  multiple
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  clearable
                  @keydown.enter.prevent="addNewCharge"
                  color="info"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="removeCharge(item)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
              </v-col>
  
              <!-- Total Price -->
              <v-col cols="12" md="6">
                <v-text-field
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.total_price"
                  label="Total Price"
                  type="number"
                ></v-text-field>
              </v-col>
      
              <!-- Total Weight -->
              <v-col cols="12" md="6">
                <v-text-field
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.total_weight"
                  label="Total Weight"
                  type="number"
                ></v-text-field>
              </v-col>
      
              <!-- Freeze Fish Production -->
              <v-col cols="12" md="6">
                <v-select
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.freeze_fish_production_id"
                  :items="freezeFishProductions"
                  label="Freeze Fish Production"
                  item-title="title"
                  item-value="value"
                ></v-select>
              </v-col>
      
              <!-- Primary Fish Detail -->
              <v-col cols="12" md="6">
                <v-select
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.primary_fish_detail_id"
                  :items="primaryFishDetails"
                  label="Primary Fish Detail"
                  item-title="title"
                  item-value="value"
                ></v-select>
              </v-col>
      
              <!-- Fishery -->
              <v-col cols="12" md="6">
                <v-select
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.fishery_id"
                  :items="fisheries"
                  label="Fishery"
                  item-title="title"
                  item-value="value"
                ></v-select>
              </v-col>
      
              <!-- Origin -->
              <v-col cols="12" md="6">
                <v-select
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.origin_id"
                  :items="origins"
                  label="Origin"
                  item-title="title"
                  item-value="value"
                ></v-select>
              </v-col>
      
              <!-- Charge Type -->
              <v-col cols="12" md="6">
                <v-select
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.charge_type_id"
                  :items="chargeTypes"
                  label="Charge Type"
                  item-title="title"
                  item-value="value"
                ></v-select>
              </v-col>
      
              <!-- Daily Charge -->
              <v-col cols="12" md="6">
                <v-select
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.daily_charge_id"
                  :items="dailyCharges"
                  label="Daily Charge"
                  item-title="title"
                  item-value="value"
                ></v-select>
              </v-col>
      
              <!-- Is Stockable -->
              <v-col cols="12" md="6">
                <v-switch
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="receipt.is_stockable"
                  label="Is Stockable?"
                  inset
                ></v-switch>
            
              </v-col>
            </v-row>
          </v-expand-transition>

        </v-card-text>
        <v-divider class="border-opacity-25" color="info"></v-divider>
        <!-- Submit Button -->
        <v-card-actions cols="12">
          <v-row class="text-center pt-1">
            <v-col>
              <v-btn block variant="tonal" rounded color="info">Submit</v-btn>
            </v-col>
            <v-divider inset vertical class="border-opacity-25" color="info"></v-divider>
            <v-col>
              <v-btn block variant="outlined" rounded color="orange">Cancel</v-btn>
            </v-col>
          </v-row>
        </v-card-actions>
      </v-card>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useReceipt } from '@/stores/Receipt';

export default {
  props: {
    receipt: {
      type: Object,
      required: true
    }
  },
  data: function () {
    return {
      searchInputTrackingNumbers: '',
      // Example options - replace with API data
      freezeFishProductions: [
        { title: 'Production A', value: 1 },
        { title: 'Production B', value: 2 },
      ],
      primaryFishDetails: [
        { title: 'Sardine Detail', value: 1 },
        { title: 'Mackerel Detail', value: 2 },
      ],
      fisheries: [
        { title: 'Fishery One', value: 1 },
        { title: 'Fishery Two', value: 2 },
      ],
      origins: [
        { title: 'Local Origin', value: 1 },
        { title: 'Imported Origin', value: 2 },
      ],
      chargeTypes: [
        { title: 'Charge Type 1', value: 1 },
        { title: 'Charge Type 2', value: 2 },
      ],
      dailyCharges: [
        { title: '2025-06-30', value: 1 },
        { title: '2025-07-01', value: 2 },
      ],
    }
  },
  computed: {
    is_dark() {
      return useMain().is_dark;
    },
    _receipt() {
      return useReceipt();
    }
  },
  methods: {
    addNewCharge(event) {
      const inputValue = event.target.value.trim()
      if (inputValue && !this.receipt.tracking_numbers.includes(inputValue)) {
        this.receipt.tracking_numbers.push(inputValue)
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    removeCharge(item) {
      this._receipt.selectedCharge = null;
    },
    update: function(){
      this._receipt.UpdateReceipt(this._receipt.receipt , this._receipt.selectedCharge , this._receipt.showAdditionalInfo);
    }
  },
  watch: {
    receipt: {
      handler(_) {
        // Save receipt to localStorage whenever it changes
        this.update();
      },
      deep: true,
      immediate: true
    },
    '_receipt.showAdditionalInfo': function (_) {
      this.update();
    },
    '_receipt.selectedCharge': function (_) {
      this.update();
    },
  }
}
</script>

<style>

</style>