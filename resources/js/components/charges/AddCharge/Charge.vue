<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <v-row>
            <v-col cols="6">
              <span>
                <v-autocomplete
                  v-model="charge.warehouse_category_operation_id"
                  :items="_charge.unique_warehouse_category_operations_list"
                  item-value="id"
                  :item-title="`name_${_main.lang.code}`"
                  :label="$t('charge_category_operation')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
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
                    <template v-slot:item="{ props, item }">
                      <v-list-item
                        v-bind="props"
                        :disabled="item.raw.disabled"
                        :title="item.raw[`name_${_main.lang.code}`]"
                      ></v-list-item>
                    </template>

                </v-autocomplete>
              </span>
            </v-col>
            <v-col class="text-end">
              <v-btn
                class="pa-2"
                size="small"
                color="red"
                icon="mdi-close"
                variant="outlined"
                @click="delete_detail"
              ></v-btn>
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
                    <v-icon class="text-blue">mdi-numeric-3-circle-outline</v-icon>
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
                  v-if="type_detail != null"
                  v-model="charge_detail.fish_type_id"
                  :items="type_detail"
                  item-value="id"
                  item-title="name"
                  :label="$t('type_article')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  :hide-details="v$.charge_detail.fish_type_id.$errors.length==0"
                  :error-messages="v$.charge_detail.fish_type_id.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.fish_type_id.$touch()"
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
                        @click:close="charge_detail.fish_type_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
                <v-textarea
                  v-else
                  color="info"
                  density="compact"
                  variant="outlined"
                  counter="250"
                  rounded
                  :label="$t('article_description')"
                  v-model="charge_detail.fish_type_id"
                  @keydown.enter.prevent="submit"
                  details
                  :error-messages="v$.charge_detail.fish_type_id.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.fish_type_id.$touch()"
                >
                </v-textarea>
              </v-col>
              


              <!-- select warehouse -->
              <v-col cols="12" md="6" v-if="_charge.charge.is_stockable">
                <v-autocomplete
                  v-model="charge_detail.warehouse_id"
                  :items="_charge.getWarehouses"
                  item-value="id"
                  item-title="name"
                  :label="$t('warehouse_name')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$.charge_detail.warehouse_id.$errors.length==0"
                  :error-messages="v$.charge_detail.warehouse_id.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.warehouse_id.$touch()"                  
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="charge_detail.warehouse_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
              
              <!-- weight -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="charge_detail.weight"
                  :label="$t('weight') + ' (Kg)'"
                  type="number" 
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$.charge_detail.weight.$errors.length==0"
                  :error-messages="v$.charge_detail.weight.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.weight.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- unit price -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="charge_detail.unit_price"
                  :label="$t('pu') + ' (Dh)'"
                  type="number"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$.charge_detail.unit_price.$errors.length==0"
                  :error-messages="v$.charge_detail.unit_price.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.unit_price.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- weight bill -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="charge_detail.weight_bill"
                  :label="$t('weight_bill') + ' (Kg)'"
                  type="number"
                  min="0"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$.charge_detail.weight_bill.$errors.length==0"
                  :error-messages="v$.charge_detail.weight_bill.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.weight_bill.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- total price -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="charge_detail.price"
                  :label="$t('total_price') + ' (Dh)'"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$.charge_detail.price.$errors.length==0"
                  :error-messages="v$.charge_detail.price.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.price.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- select package_type type -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="charge_detail.package_id"
                  :items="_charge.getPackages"
                  item-value="id"
                  item-title="name"
                  :label="$t('type_package')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$.charge_detail.package_id.$errors.length==0"
                  :error-messages="v$.charge_detail.package_id.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.package_id.$touch()"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                        @click:close="charge_detail.package_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>

              <!-- number of packages -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="charge_detail.package_count"
                  :label="$t('nbr_boxes')"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="info"
                  :hide-details="v$.charge_detail.package_count.$errors.length==0"
                  :error-messages="v$.charge_detail.package_count.$errors.map(e => e.$message)"
                  @blur="v$.charge_detail.package_count.$touch()"                  
                  >
                </v-text-field>
              </v-col>
              <v-col>
                <v-btn
                  :disabled="v$.$invalid"
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
                :items="get_charge_details"
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
                  <v-chip color="info">
                    {{ _charge.getFishTypeName(item.fish_type_id) }}
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
                    {{ _charge.getPackageName(item.package_id) }}
                  </v-chip>
                </template>
                <template v-slot:[`item.package_count`]="{ item }">
                  <div class="font-weight-medium">
                    {{item.package_count}}
                  </div>
                </template>
                <template v-slot:[`item.warehouse_id`]="{ item }">
                  <v-chip color="indigo">
                    {{ _charge.getWarehouseName(item.warehouse_id) }}
                  </v-chip>
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
    import { v4 as uuidv4 } from 'uuid';
    import { useCharge } from '@/stores/Charge';
    import { useMain } from '@/stores/Main';
    import {useWarehouseCategoryOperation} from '@/stores/WarehouseCategoryOperation';
    import useVuelidate from '@vuelidate/core';
    import { required, numeric, requiredIf } from '@vuelidate/validators';
    import { useFishType } from "@/stores/FishType";
    import { usePackageType } from "@/stores/PackageType";
    import { useGuanoType } from "@/stores/GuanoType";
    
    export default {
      props: {
        charge: {
          type: Object,
          required: true
        }
      },
      data: function () {
        return {
          headers: [],
          selected: [],
          v$: useVuelidate(),
          is_editing: false,
          charge_detail: {
              id: uuidv4(),
              weight: null,
              weight_bill: null,
              unit_price: null,
              price: null,
              package_count: null,
              fish_type_id: null,
              warehouse_category_operation_id: null,
              warehouse_id: null,
              package_id: null,
          },
          type_detail:null
        };    
      },
      validations: function () {
        return {
          charge_detail: {
              fish_type_id: {
                required,
              },
              warehouse_id: {
                requiredIf: requiredIf(() =>this._charge.charge.is_warehouseabl),
                numeric,
              },
              weight: {
                required,
                numeric,
              },
              unit_price: {
                required,
                numeric,
              },
              weight_bill: {
                required,
                numeric,
              },
              price: {
                required,
                numeric,
              },
              package_id: {
                required,
                numeric,
              },
              package_count: {
                required,
                numeric,
              }
          }
        }
      },
      mounted() {
        this.getTypeDetail()
        this.headers = this.DEFAULT_HEADERS();
      },
      computed: {
        _charge() {
          return useCharge();
        },
        _main() {
          return useMain();
        },
        _fish_type() {
          return useFishType();
        },
        _package_type() {
          return usePackageType();
        },
        _guano_type() {
          return useGuanoType();
        },
        _warehouseCategoryOperation() {
          return useWarehouseCategoryOperation();
        },
        get_charge_details() {
          return this._charge.charge_details.filter(e => e.warehouse_category_operation_id == this.charge.warehouse_category_operation_id);
        }
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
            { title: this.$t("warehouse_name"), value: 'warehouse_id', removable: true },
            { title: this.$t("actions"), value: 'actions', align: 'end', sortable: false, removable: false }
          ]
        },
        update: function () {
          this._charge.update_charge_detail();
          this.is_editing = false;
        },
        submit: function () {
          this._charge.add_charge_detail(this.charge_detail);
          this.reset_charge_detail();
        },
        reset_charge_detail: function () {
          this.charge_detail = {
              id: uuidv4(),
              weight: null,
              weight_bill: null,
              unit_price: null,
              price: null,
              package_count: null,
              fish_type_id: null,
              warehouse_category_operation_id: null,
              warehouse_id: null,
              package_id: null,
          };
          this.v$.$reset();
          this.is_editing = false;
        },
        edit: function (id) {
          this.is_editing = true;
          this._charge.edit_charge_detail(id);
        },
        remove: function (id) {
          this._charge.remove_charge_detail(id);
        },
        onClickReset : function() {
          this.headers = this.DEFAULT_HEADERS()
        },
        remove_header: function (key) {
          this.headers = this.headers.filter(header => header.value !== key);
        },
        delete_detail: function () {
          this._charge.delete_detail(this.charge.id);
        },
        getTypeDetail: function () {
          this.charge_detail.fish_type_id = null;
          this.charge_detail.warehouse_category_operation_id = this.charge.warehouse_category_operation_id;
          let warehouse_type = this._warehouseCategoryOperation.getList.find(e => e.id == this.charge.warehouse_category_operation_id)?.warehouse_type;
          switch (warehouse_type) {
            case "package_type":
                this.type_detail = this._package_type.getList;
                break;
            case "fish_type":
                this.type_detail = this._fish_type.getList;
                break;
            case "guano_type":
                this.type_detail = this._guano_type.getList;
                break;
            default:
              this.type_detail = null;
          }
        }
      },
      watch: {
        'charge_detail': {
          handler: function () {
            this._charge.UpdateCharge();
          },
          deep: true
        },
        "charge_detail.unit_price": function () {
          this.charge_detail.price = this.charge_detail.weight * this.charge_detail.unit_price;
        },
        "charge_detail.weight": function (value) {
          this.charge_detail.weight_bill = value;
          this.charge_detail.price = this._main.formatNumber(this.charge_detail.weight * this.charge_detail.unit_price);
        },
        "charge_detail.weight_bill": function (value) {
          if (this.charge_detail.package_id && value > 0) {
            this.charge_detail.package_count = +(value / this._charge.getPackageSize(this.charge_detail.package_id,value)).toFixed(1);
          } else {
            this.charge_detail.package_count = 0;  
          }
        },
        "charge_detail.package_id": function (value) {
          if (this.charge_detail.package_id && value > 0) {
            this.charge_detail.package_count = +(this.charge_detail.weight_bill / this._charge.getPackageSize(value,this.charge_detail.weight_bill)).toFixed(1);
          } else {
            this.charge_detail.package_count = 0;  
          }
        },
        "charge.warehouse_category_operation_id": function () {
          this.getTypeDetail();
        }
      },
    }
</script>

<style scoped>

</style>