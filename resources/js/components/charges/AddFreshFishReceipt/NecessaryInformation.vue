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
                v-model="_receipt.input_tracking_number"
                :items="_receipt.type_charges"
                item-value="name"
                item-title="name"
                key="name"
                label="Type Of Charge"
                chips
                density="compact"
                variant="outlined"
                rounded
                color="info"
                hide-details
                >
                  <!-- Custom Chips Slot -->
                  <template #chip="{ item, index }">
                    <v-chip
                      :key="index"
                      class="text-white"
                      color="info accent-4"
                      closable
                      variant="tonal"
                      @click:close="removeTrackingNumber(item)"
                    >
                      {{ item.title }}
                    </v-chip>
                  </template>
                </v-autocomplete>
            </v-col>
          </v-row>
          
          <v-row class="text-center">
            <!-- Select Detail -->
            <v-col class="py-0">
              <v-switch
                density="compact"
                color="info"
                hide-details
                variant="outlined"
                rounded
                v-model="_receipt.add_fresh_fish_receive_details"
                label="Fish Receive"
                inset
              ></v-switch>
            </v-col>
            <v-col class="py-0">
              <v-switch
                density="compact"
                color="info"
                hide-details
                variant="outlined"
                rounded
                v-model="_receipt.add_freeze_fish_process_details"
                label="Freeze Process"
                inset
              ></v-switch>
            </v-col>
            <v-col cols="2" class="py-0">
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
              <VDatePicker  
                :is-dark="is_dark"
                expanded
                :max-date="new Date()"
                v-model="_receipt.receipt.date"
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
                  v-model="_receipt.receipt.tracking_numbers"
                  :items="_receipt.receipt.tracking_numbers"
                  label="Tracking Numbers (comma separated)"
                  multiple
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  clearable
                  hide-details
                  @keydown.enter.prevent="addNewTrackingNumber"
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
                        @click:close="_receipt.receipt.tracking_numbers = _receipt.receipt.tracking_numbers.filter(num => num !== item)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
              </v-col>
  
              <!-- Tracking ONP Numbers -->

              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="_receipt.receipt.tracking_onp_numbers"
                  :items="_receipt.receipt.tracking_onp_numbers"
                  label="Tracking Onp Numbers (comma separated)"
                  multiple
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  clearable
                  @keydown.enter.prevent="addNewOnpTrackingNumber"
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
                        @click:close="_receipt.receipt.tracking_onp_numbers = _receipt.receipt.tracking_onp_numbers.filter(num => num !== item)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
              </v-col>
  
              <!-- Provider -->

              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="_receipt.receipt.provider_ids"
                  :items="_provider.list"
                  item-value="id"
                  item-title="name"
                  key="id"
                  label="Providers"
                  multiple
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  clearable
                  @keydown.enter.prevent="addNewOnpTrackingNumber"
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
                        @click:close="_receipt.receipt.provider_ids = _receipt.receipt.provider_ids.filter(num => num !== item.value)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
              </v-col>
      
              <!-- Fishery -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  chips
                  v-model="_receipt.receipt.fishery_id"
                  :items="fisheries"
                  label="Fishery"
                  item-title="title"
                  item-value="value"
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
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  chips
                  v-model="_receipt.receipt.origin_id"
                  :items="origins"
                  label="Origin"
                  item-title="title"
                  item-value="value"
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

              <!-- Is Stockable -->
              <v-col cols="12" md="6">
                <v-switch
                  density="compact"
                  color="info"
                  hide-details
                  variant="outlined"
                  rounded
                  v-model="_receipt.receipt.is_stockable"
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
            <v-col class="pr-1">
              <v-btn block variant="tonal" rounded color="green" class="rounded-e-lg" @click="archive">Archive</v-btn>
            </v-col>
            <v-col class="pl-1">
              <v-btn block variant="outlined" rounded color="orange" class="rounded-s-lg" @click="reset">Cancel</v-btn>
            </v-col>
          </v-row>
        </v-card-actions>
      </v-card>
      <v-dialog
        v-model="dialog_note"
        max-width="600"
      >
        <v-sheet
          rounded="lg"
          width="100%"
          class="pa-9"
        >
          <h3 class="py-2">Add Receipt Note</h3>
          <v-divider class="py-2"></v-divider>
          <v-textarea
            v-model="_receipt.receipt.note"
            label="Receipt Note"
            density="compact"
            variant="outlined"
            rounded
            counter
            color="info"
            >
            </v-textarea>
        </v-sheet>
      </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useReceipt } from '@/stores/Receipt';
import { useProvider } from '@/stores/Provider';

export default {
  props: {
    receipt: {
      type: Object,
      required: true,
    }
  },
  data: function () {
    return {
      dialog_note:false,
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
    },
    _provider() {
      return useProvider();
    },
  },
  methods: {
    addNewTrackingNumber(event) {
      let inputValue = event.target.value.trim()
      if (inputValue && !this._receipt.receipt.tracking_numbers.includes(inputValue)) {
        this._receipt.receipt.tracking_numbers.push(inputValue)
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    removeTrackingNumber(item) {
      this._receipt.input_tracking_number = null;
    },
    addNewOnpTrackingNumber(event) {
      let inputValue = event.target.value.trim()
      if (inputValue && !this._receipt.receipt.tracking_onp_numbers.includes(inputValue)) {
        this._receipt.receipt.tracking_onp_numbers.push(inputValue)
      }
      this.searchInputTrackingNumbers = "";
      inputValue = "";
      event.target.value = '';
    },
    removeOnpTrackingNumber(item) {
      this._receipt.tracking_onp_numbers = this._receipt.tracking_onp_numbers.filter(num => num !== item);
    },
    update: function(){
      this._receipt.UpdateReceipt();
    },
    archive: function () {
      this._receipt.archive();
    },
    reset: function () {
      this._receipt.reset();
    },
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
    '_receipt.input_tracking_number': function (_) {
      this.update();
    },
    '_receipt.add_fresh_fish_receive_details': function (_) {
      this.update();
    },
    '_receipt.add_freeze_fish_process_details': function (_) {
      this.update();
    },
  }
}
</script>

<style>

</style>