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
                    <v-icon class="text-blue">mdi-numeric-3-circle-outline</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>Add Details</span>
                  </v-col>
                </v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
            </v-col>
          </v-row>
          <v-row class="pt-2">
              <!-- select fish type -->

              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="_receipt.fresh_fish_receive_detail.fish_type_id"
                  :items="_receipt.getTypeCharge"
                  item-value="id"
                  item-title="name"
                  label="Fish Type"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  :hide-details="v$._receipt.fresh_fish_receive_detail.fish_type_id.$errors.length==0"
                  :error-messages="v$._receipt.fresh_fish_receive_detail.fish_type_id.$errors.map(e => e.$message)"
                  @blur="v$._receipt.fresh_fish_receive_detail.fish_type_id.$touch()"
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
                        @click:close="_receipt.fresh_fish_receive_detail.fish_type_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>


              <!-- select stock -->

              <v-col cols="12" md="6" v-if="_receipt.receipt.is_stockable">
                <v-autocomplete
                  v-model="_receipt.fresh_fish_receive_detail.stock_id"
                  :items="_receipt.getTypeCharge"
                  item-value="id"
                  item-title="name"
                  label="Stock Name"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.fresh_fish_receive_detail.stock_id.$errors.length==0"
                  :error-messages="v$._receipt.fresh_fish_receive_detail.stock_id.$errors.map(e => e.$message)"
                  @blur="v$._receipt.fresh_fish_receive_detail.stock_id.$touch()"                  
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="_receipt.fresh_fish_receive_detail.stock_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
              
              <!-- weight -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.fresh_fish_receive_detail.weight"
                  label="Weight (KG)"
                  type="number" 
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.fresh_fish_receive_detail.weight.$errors.length==0"
                  :error-messages="v$._receipt.fresh_fish_receive_detail.weight.$errors.map(e => e.$message)"
                  @blur="v$._receipt.fresh_fish_receive_detail.weight.$touch()"
                  >
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.fresh_fish_receive_detail.unit_price"
                  label="PU (Dh)"
                  type="number"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.fresh_fish_receive_detail.unit_price.$errors.length==0"
                  :error-messages="v$._receipt.fresh_fish_receive_detail.unit_price.$errors.map(e => e.$message)"
                  @blur="v$._receipt.fresh_fish_receive_detail.unit_price.$touch()"
                  >
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.fresh_fish_receive_detail.weight_bill"
                  label="Weight Bill (Kg)"
                  type="number"
                  min="0"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.fresh_fish_receive_detail.weight_bill.$errors.length==0"
                  :error-messages="v$._receipt.fresh_fish_receive_detail.weight_bill.$errors.map(e => e.$message)"
                  @blur="v$._receipt.fresh_fish_receive_detail.weight_bill.$touch()"
                  >
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.fresh_fish_receive_detail.price"
                  label="Total Price (Dh)"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.fresh_fish_receive_detail.price.$errors.length==0"
                  :error-messages="v$._receipt.fresh_fish_receive_detail.price.$errors.map(e => e.$message)"
                  @blur="v$._receipt.fresh_fish_receive_detail.price.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- select package type -->

              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="_receipt.fresh_fish_receive_detail.package_id"
                  :items="_receipt.getTypeCharge"
                  item-value="id"
                  item-title="name"
                  label="Package Type"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.fresh_fish_receive_detail.package_id.$errors.length==0"
                  :error-messages="v$._receipt.fresh_fish_receive_detail.package_id.$errors.map(e => e.$message)"
                  @blur="v$._receipt.fresh_fish_receive_detail.package_id.$touch()"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="_receipt.fresh_fish_receive_detail.package_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.fresh_fish_receive_detail.packag_count"
                  label="NBR Caisses"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.fresh_fish_receive_detail.packag_count.$errors.length==0"
                  :error-messages="v$._receipt.fresh_fish_receive_detail.packag_count.$errors.map(e => e.$message)"
                  @blur="v$._receipt.fresh_fish_receive_detail.packag_count.$touch()"                  
                  >
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">

              </v-col>
            </v-row>
            <!-- Submit Button -->
            <v-card-actions cols="12">
              <v-row class="text-center py-3">
                <v-col v-if="is_editing">
                  <v-btn block variant="tonal" rounded color="indigo" @click="update">Edit</v-btn>
                </v-col>
                <v-col v-else>
                  <v-btn block variant="tonal" rounded color="info" @click="submit">Submit</v-btn>
                </v-col>
                <v-divider inset vertical class="border-opacity-25" color="info"></v-divider>
                <v-col class="pr-1">
                  <v-btn block variant="outlined" rounded color="amber" @click="reset_fresh_fish_receive_detail">Cancel</v-btn>
                </v-col>
              </v-row>
            </v-card-actions>
            <v-divider class="border-opacity-25 pb-3" color="info"></v-divider>

            <v-row>
              <v-data-table
                v-model="selected"
                :headers="headers"
                :items="_receipt.receipt.fresh_fish_receive_details"
                tabindex="0"
                item-value="id"
                show-select
                density="compact"
                item-key="id"
                hide-default-footer
              >
                <!-- Custom select-all checkbox slot -->
                <template v-slot:[`header.data-table-select`]="{ allSelected, selectAll }">
                  <v-checkbox
                    :model-value="allSelected"
                    @update:model-value="selectAll"
                    :indeterminate="selected.length > 0 && !allSelected"
                    color="info"
                    class="px-0"
                    hide-details
                  ></v-checkbox>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                  <div class="d-flex ga-2 justify-end">
                    <v-icon color="amber" icon="mdi-pencil" size="small" @click="edit(item.id)"></v-icon>

                    <v-icon color="red" icon="mdi-delete" size="small" @click="remove(item.id)"></v-icon>
                  </div>
                </template>

                <template v-slot:no-data>
                  <div class="text-orange">
                    <v-icon icon="mdi-database-alert-outline" size="large" class="mx-2"></v-icon>
                    <span>No data available</span>
                  </div>
                </template>
              </v-data-table>
            </v-row>
        </v-card-text>
      </v-card>
</template>

<script>
    import { useReceipt } from '@/stores/Receipt';
    import { useMain } from '@/stores/Main';
    import useVuelidate from '@vuelidate/core'
    import { required, numeric , minLength  } from '@vuelidate/validators'
    export default {
      data: function () {
        return {
          selected: [],
            v$: useVuelidate(),
            is_editing: false,
        };    
      },
      validations: {
        _receipt: {
          fresh_fish_receive_detail: {
              fish_type_id: {
                required,
                numeric,
                minLength: minLength(1)
              },
              stock_id: {
                required,
                numeric,
                minLength: minLength(1)
              },
              weight: {
                required,
                numeric,
                minLength: minLength(1)
              },
              unit_price: {
                required,
                numeric,
                minLength: minLength(1)
              },
              weight_bill: {
                required,
                numeric,
                minLength: minLength(1)
              },
              price: {
                required,
                numeric,
                minLength: minLength(1)
              },
              package_id: {
                required,
                numeric,
                minLength: minLength(1)
              },
              packag_count: {
                required,
                numeric,
                minLength: minLength(1)
              }
          }
        }
      },
      computed: {
        headers: function () {
          return [
            { title: 'Fish Type', value: 'fish_type_id' },
            { title: 'Weight (Kg)', value: 'weight' },
            { title: 'Weight Bill (KG)', value: 'weight' },
            { title: 'PU (Dh)', value: 'unit_price' },
            { title: 'Total Price (Dh)', value: 'price' },
            { title: 'Type Package', value: 'package_id' },
            { title: 'NBR Caisses', value: 'packag_count' },
            { title: 'Stock Name', value: 'stock_id' },
            { title: 'Actions', value: 'actions', align: 'end', sortable: false },
          ];
        },
        _receipt() {
          return useReceipt();
        },
        main() {
          return useMain();
        }
      },
      methods:{
        submit: function () {
          this._receipt.add_fresh_fish_receive_detail();
        },
        update: function () {
          this._receipt.update_fresh_fish_receive_detail();
          this.is_editing = false;
        },
        edit: function (id) {
          this.is_editing = true;
          this._receipt.edit_fresh_fish_receive_detail(id);
        },
        remove: function (id) {
          this._receipt.remove_fresh_fish_receive_detail(id);
        },
        reset_fresh_fish_receive_detail: function () {
          this._receipt.reset_fresh_fish_receive_detail();
        }
      },
      watch: {
        '_receipt.fresh_fish_receive_detail': {
          handler: function () {
            this._receipt.UpdateReceipt();
          },
          deep: true
        },
        "_receipt.fresh_fish_receive_detail.unit_price": function () {
          this._receipt.fresh_fish_receive_detail.price = this._receipt.fresh_fish_receive_detail.weight * this._receipt.fresh_fish_receive_detail.unit_price;
        },
        "_receipt.fresh_fish_receive_detail.weight": function () {
          this._receipt.fresh_fish_receive_detail.weight_bill = this._receipt.fresh_fish_receive_detail.weight;
          this._receipt.fresh_fish_receive_detail.price = this.main.formatNumber(this._receipt.fresh_fish_receive_detail.weight * this._receipt.fresh_fish_receive_detail.unit_price);
        }
      },
    }
</script>

<style scoped>

</style>