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
                  v-model="_receipt.model_fresh_fish_receive_detail.fish_type_id"
                  :items="_receipt.getTypeCharge"
                  item-value="id"
                  item-title="name"
                  label="Fish Type"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  @keydown.enter.prevent="submit"
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
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>

              <!-- select package type -->

              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="_receipt.model_fresh_fish_receive_detail.package_id"
                  :items="_receipt.getTypeCharge"
                  item-value="id"
                  item-title="name"
                  label="Package Type"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  @keydown.enter.prevent="submit"
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
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
              
              <!-- weight -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.model_fresh_fish_receive_detail.weight"
                  label="Weight (KG)"
                  type="number" 
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  @keydown.enter.prevent="submit"
                  color="info"
                  >
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.model_fresh_fish_receive_detail.unit_price"
                  label="PU (Dh)"
                  type="number"
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  @keydown.enter.prevent="submit"
                  color="info"
                  >
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.model_fresh_fish_receive_detail.price"
                  label="Total Price (Dh)"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  @keydown.enter.prevent="submit"
                  color="info"
                  >
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.model_fresh_fish_receive_detail.packag_count"
                  label="NBR Caisses"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  @keydown.enter.prevent="submit"
                  color="info"
                  >
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">

              </v-col>
            </v-row>

            <v-row>
              <v-data-table
                v-model="selected"
                :headers="headers"
                :items="_receipt.fresh_fish_receive_details"
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
    export default {
      data: function () {
        return {
          selected: [],
        };    
      },
      computed: {
        headers: function () {
          return [
            { title: 'Fish Type', value: 'fish_type.name' },
            { title: 'Package Type', value: 'package.name' },
            { title: 'Weight (KG)', value: 'weight' },
            { title: 'PU (Dh)', value: 'unit_price' },
            { title: 'Total Price (Dh)', value: 'price' },
            { title: 'NBR Caisses', value: 'packag_count' },
            { title: 'Actions', value: 'actions', align: 'end', sortable: false },
          ];
        },
        _receipt() {
          return useReceipt();
        }
      },
      methods:{
        submit: function () {
          this._receipt.add();
        },
        edit: function (id) {
          this._receipt.edit(id);
        },
        remove: function (id) {
          this._receipt.remove(id);
        },
      }    
    }
</script>

<style scoped>

</style>