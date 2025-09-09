<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <span>
            {{ $t("add_freeze_fish_process") }}
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
                    <v-icon class="text-blue">{{_receipt.freeze_fish_process_detail ? 'mdi-numeric-4-circle-outline' : 'mdi-numeric-3-circle-outline'}}</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>{{$t("add_details")}}</span>
                  </v-col>
                </v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
            </v-col>
          </v-row>
          <v-row class="pt-2">
              <!-- select Type Article -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="_receipt.freeze_fish_process_detail.fish_type_id"
                  :items="_receipt.type_detail"
                  item-value="id"
                  item-title="name"
                  :label="$t('type_article')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  :hide-details="v$._receipt.freeze_fish_process_detail.fish_type_id.$errors.length==0"
                  :error-messages="v$._receipt.freeze_fish_process_detail.fish_type_id.$errors.map(e => e.$message)"
                  @blur="v$._receipt.freeze_fish_process_detail.fish_type_id.$touch()"
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
                        @click:close="_receipt.freeze_fish_process_detail.fish_type_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>


              <!-- select stock -->
              <v-col cols="12" md="6" v-if="_receipt.receipt.is_stockable">
                <v-autocomplete
                  v-model="_receipt.freeze_fish_process_detail.stock_id"
                  :items="_receipt.getStocks"
                  item-value="id"
                  item-title="name"
                  :label="$t('stock_name')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.freeze_fish_process_detail.stock_id.$errors.length==0"
                  :error-messages="v$._receipt.freeze_fish_process_detail.stock_id.$errors.map(e => e.$message)"
                  @blur="v$._receipt.freeze_fish_process_detail.stock_id.$touch()"                  
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="_receipt.freeze_fish_process_detail.stock_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
              
              <!-- weight -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.freeze_fish_process_detail.weight"
                  :label="$t('weight') + ' (Kg)'"
                  type="number" 
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.freeze_fish_process_detail.weight.$errors.length==0"
                  :error-messages="v$._receipt.freeze_fish_process_detail.weight.$errors.map(e => e.$message)"
                  @blur="v$._receipt.freeze_fish_process_detail.weight.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- unit price -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.freeze_fish_process_detail.unit_price"
                  :label="$t('pu') + ' (Dh)'"
                  type="number"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.freeze_fish_process_detail.unit_price.$errors.length==0"
                  :error-messages="v$._receipt.freeze_fish_process_detail.unit_price.$errors.map(e => e.$message)"
                  @blur="v$._receipt.freeze_fish_process_detail.unit_price.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- weight bill -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.freeze_fish_process_detail.weight_bill"
                  :label="$t('weight_bill') + ' (Kg)'"
                  type="number"
                  min="0"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.freeze_fish_process_detail.weight_bill.$errors.length==0"
                  :error-messages="v$._receipt.freeze_fish_process_detail.weight_bill.$errors.map(e => e.$message)"
                  @blur="v$._receipt.freeze_fish_process_detail.weight_bill.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- total price -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.freeze_fish_process_detail.price"
                  :label="$t('total_price') + ' (Dh)'"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.freeze_fish_process_detail.price.$errors.length==0"
                  :error-messages="v$._receipt.freeze_fish_process_detail.price.$errors.map(e => e.$message)"
                  @blur="v$._receipt.freeze_fish_process_detail.price.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- select package type -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="_receipt.freeze_fish_process_detail.package_id"
                  :items="_receipt.getPackages"
                  item-value="id"
                  item-title="name"
                  :label="$t('type_package')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.freeze_fish_process_detail.package_id.$errors.length==0"
                  :error-messages="v$._receipt.freeze_fish_process_detail.package_id.$errors.map(e => e.$message)"
                  @blur="v$._receipt.freeze_fish_process_detail.package_id.$touch()"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="_receipt.freeze_fish_process_detail.package_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>

              <!-- number of packages -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="_receipt.freeze_fish_process_detail.package_count"
                  :label="$t('nbr_boxes')"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$._receipt.freeze_fish_process_detail.package_count.$errors.length==0"
                  :error-messages="v$._receipt.freeze_fish_process_detail.package_count.$errors.map(e => e.$message)"
                  @blur="v$._receipt.freeze_fish_process_detail.package_count.$touch()"                  
                  >
                </v-text-field>
              </v-col>
              <v-col>
                <v-btn
                  color="info"
                  variant="tonal"
                  rounded="pill"
                  block
                  @click="submit"
                >
                  {{ $t("add_article") }}
                </v-btn>
              </v-col>
          </v-row>

            <v-row>
              <v-data-table
                :headers="headers"
                :items="_receipt.receipt.freeze_fish_process_details"
                tabindex="0"
                item-value="id"
                density="compact"
                item-key="id"
                hover
                hide-default-footer
              >
                <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
                  <tr>
                    <template v-for="column in columns" :key="column.key">
                      <th>
                        <div class="d-flex align-center">
                          <span
                            class="me-2 cursor-pointer"
                            @click="toggleSort(column)"
                            v-text="column.title"
                          ></span>

                          <v-icon
                            v-if="isSorted(column)"
                            :icon="getSortIcon(column)"
                            color="medium-emphasis"
                          ></v-icon>

                          <v-icon
                            v-if="column.removable"
                            color="medium-emphasis"
                            icon="$close"
                            @click="remove_header(column.key)"
                          ></v-icon>
                        </div>
                      </th>
                    </template>
                  </tr>
                </template>

                <template v-slot:top>
                  <v-expand-transition>
                    <div class="my-3" v-if="headers.length < 9">
                      <v-btn
                        rounded="lg"
                        size="small"
                        text="Reset"
                        variant="tonal"
                        block
                        color="info"
                        @click="onClickReset"
                      ></v-btn>

                      <v-divider></v-divider>
                    </div>
                  </v-expand-transition>
                </template>
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

                <template v-slot:[`item.fish_type_id`]="{ item }">
                  <v-chip color="secondary">
                    {{ _receipt.getFishTypeName(item.fish_type_id) }}
                  </v-chip>
                </template>
                <template v-slot:[`item.weight`]="{ item }">
                  <div class="font-weight-medium">
                    {{ _main.formatDisplayNumber(item.weight) }}
                  </div>
                </template>
                <template v-slot:[`item.weight_bill`]="{ item }">
                  <div class="font-weight-medium">
                    {{ _main.formatDisplayNumber(item.weight_bill) }}
                  </div>
                </template>
                <template v-slot:[`item.unit_price`]="{ item }">
                  <div class="font-weight-medium">
                    {{ _main.formatDisplayNumber(item.unit_price) }}
                  </div>
                </template>
                <template v-slot:[`item.price`]="{ item }">
                  <div class="font-weight-medium">
                    {{ _main.formatDisplayNumber(item.price) }}
                  </div>
                </template>
                <template v-slot:[`item.package_id`]="{ item }">
                  <v-chip color="secondary">
                    {{ _receipt.getPackageName(item.package_id) }}
                  </v-chip>
                </template>
                <template v-slot:[`item.package_count`]="{ item }">
                  <div class="font-weight-medium">
                    {{item.package_count}}
                  </div>
                </template>
                <template v-slot:[`item.stock_id`]="{ item }">
                  <v-chip color="secondary">
                    {{ _receipt.getStockName(item.stock_id) }}
                  </v-chip>
                </template>
                <template v-slot:no-data>
                  <div class="text-orange">
                    <v-icon icon="mdi-database-alert-outline" size="large" class="mx-2"></v-icon>
                    <span>{{$t("no_data_available")}}</span>
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
          headers: [],
          selected: [],
            v$: useVuelidate(),
            is_editing: false,
        };    
      },
      validations: {
        _receipt: {
          freeze_fish_process_detail: {
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
              package_count: {
                required,
                numeric,
                minLength: minLength(1)
              }
          }
        }
      },
      mounted() {
        this.headers = this.DEFAULT_HEADERS();
      },
      computed: {
        _receipt() {
          return useReceipt();
        },
        _main() {
          return useMain();
        },
      },
      methods:{
        DEFAULT_HEADERS () {
          return [
            { title: this.$t("type_article"), value: 'fish_type_id', removable: true },
            { title: this.$t("weight_(KG)"), value: 'weight', removable: true },
            { title: this.$t("weight_bill_(KG)"), value: 'weight_bill', removable: true },
            { title: this.$t("pu_(DH)"), value: 'unit_price', removable: true },
            { title: this.$t("total_price_(DH)"), value: 'price', removable: true },
            { title: this.$t("type_package"), value: 'package_id', removable: true },
            { title: this.$t("nbr_caisses"), value: 'package_count', removable: true },
            { title: this.$t("stock_name"), value: 'stock_id', removable: true },
            { title: this.$t("actions"), value: 'actions', align: 'end', sortable: false, removable: false }
          ]
        },
        update: function () {
          this._receipt.update_freeze_fish_process_detail();
          this.is_editing = false;
        },
        submit: function () {
          this._receipt.add_freeze_fish_process_detail();
        },
        edit: function (id) {
          this.is_editing = true;
          this._receipt.edit_freeze_fish_process_detail(id);
        },
        remove: function (id) {
          this._receipt.remove_freeze_fish_process_detail(id);
        },
        onClickReset : function() {
          this.headers = this.DEFAULT_HEADERS()
        },
        remove_header: function (key) {
          this.headers = this.headers.filter(header => header.value !== key);
        },
      },
      watch: {
        '_receipt.freeze_fish_process_detail': {
          handler: function () {
            this._receipt.UpdateReceipt();
          },
          deep: true
        },
        "_receipt.freeze_fish_process_detail.unit_price": function () {
          this._receipt.freeze_fish_process_detail.price = this._receipt.freeze_fish_process_detail.weight * this._receipt.freeze_fish_process_detail.unit_price;
        },
        "_receipt.freeze_fish_process_detail.weight": function (value) {
          this._receipt.freeze_fish_process_detail.weight_bill = value;
          this._receipt.freeze_fish_process_detail.price = this._main.formatNumber(this._receipt.freeze_fish_process_detail.weight * this._receipt.freeze_fish_process_detail.unit_price);
        },
        "_receipt.freeze_fish_process_detail.weight_bill": function (value) {
          if (this._receipt.freeze_fish_process_detail.package_id && value > 0) {
            this._receipt.freeze_fish_process_detail.package_count = +(value / this._receipt.getPackageSize(this._receipt.freeze_fish_process_detail.package_id,value)).toFixed(1);
          } else {
            this._receipt.freeze_fish_process_detail.package_count = 0;  
          }
        },
        "_receipt.freeze_fish_process_detail.package_id": function (value) {
          if (this._receipt.freeze_fish_process_detail.package_id && value > 0) {
            this._receipt.freeze_fish_process_detail.package_count = +(this._receipt.freeze_fish_process_detail.weight_bill / this._receipt.getPackageSize(value,this._receipt.freeze_fish_process_detail.weight_bill)).toFixed(1);
          } else {
            this._receipt.freeze_fish_process_detail.package_count = 0;  
          }
        }
      },
    }
</script>

<style scoped>

</style>