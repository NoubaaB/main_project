<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <v-row class="mt-1">
            <v-col cols="6">
              <span>
                <v-autocomplete
                  v-model="warehouse_category_operation.id"
                  :items="productionWcoList"
                  item-value="id"
                  :item-title="`name_${_main.lang.code}`"
                  :disabled="is_editing"
                  :label="$t('warehouse_detail_category')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  color="teal"
                  class="text-teal"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="teal"
                        variant="tonal"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                    <template v-slot:item="{ props, item }">
                      <v-list-item
                        v-bind="props"
                        :disabled="is_used_warehouse_category_operation(item.raw.id)"
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
                @click="delete_warehouse_category_operation"
              ></v-btn>
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
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <v-icon class="text-teal">{{`mdi-numeric-${number}-circle-outline`}}</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>{{$t("add_details")}}</span>
                  </v-col>
                </v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="teal"></v-divider>
              </div>
            </v-col>
          </v-row>
          <v-row class="pt-2">
              <!-- select Type Article -->
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-if="type_detail != null"
                  v-model="detail.stockable_id"
                  :items="type_detail"
                  item-value="id"
                  :item-title="`name_${_main.lang.code}`"
                  :label="$t('type_article')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  :hide-details="v$.detail.stockable_id.$errors.length==0"
                  :error-messages="v$.detail.stockable_id.$errors.map(e => e.$message)"
                  @blur="v$.detail.stockable_id.$touch()"
                  color="teal"
                  class="text-teal"
                  ref="detail_focus_input"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="teal"
                        closable
                        variant="tonal"
                        @click:close="detail.stockable_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
                <v-textarea
                  v-else
                  color="teal"
                  density="compact"
                  variant="outlined"
                  counter="250"
                  rounded
                  :label="$t('article_description')"
                  v-model="detail.description"
                  @keydown.enter.prevent="submit"
                  details
                  :error-messages="v$.detail.description.$errors.map(e => e.$message)"
                  @blur="v$.detail.description.$touch()"
                  class="text-teal"
                  ref="detail_focus_input"
                >
                </v-textarea>
              </v-col>

              <!-- select warehouse -->
              <v-col cols="12" md="6" v-if="ability_warehouse_category_operation?.allow_warehouse">
                <v-autocomplete
                  v-model="detail.warehouse_id"
                  :items="getWarehouse"
                  item-value="id"
                  item-title="name"
                  :label="$t('warehouse_name')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  class="text-teal"
                  color="teal"
                  :hide-details="v$.detail.warehouse_id.$errors.length==0"
                  :error-messages="v$.detail.warehouse_id.$errors.map(e => e.$message)"
                  @blur="v$.detail.warehouse_id.$touch()"                  
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="teal"
                        closable
                        variant="tonal"
                        @click:close="detail.warehouse_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
              
              <!-- total_weight -->
              <v-col cols="12" md="6" v-if="ability_warehouse_category_operation?.allow_weight">
                <v-text-field
                  v-model="detail.total_weight"
                  :label="$t('weight') + ' (Kg)'"
                  type="number" 
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  class="text-teal"
                  color="teal"
                  :hide-details="v$.detail.total_weight.$errors.length==0"
                  :error-messages="v$.detail.total_weight.$errors.map(e => e.$message)"
                  @blur="v$.detail.total_weight.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- unit price -->
              <v-col cols="12" md="6" v-if="ability_warehouse_category_operation?.allow_price">
                <v-text-field
                  v-model="detail.unit_price"
                  :label="$t('pu') + ' (Dh)'"
                  type="number"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  class="text-teal"
                  color="teal"
                  :hide-details="v$.detail.unit_price.$errors.length==0"
                  :error-messages="v$.detail.unit_price.$errors.map(e => e.$message)"
                  @blur="v$.detail.unit_price.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- total_weight bill -->
              <v-col cols="12" md="6" v-if="ability_warehouse_category_operation?.allow_weight">
                <v-text-field
                  v-model="detail.total_weight_bill"
                  :label="$t('total_weight_invoice') + ' (Kg)'"
                  type="number"
                  min="0"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  class="text-teal"
                  color="teal"
                  :hide-details="v$.detail.total_weight_bill.$errors.length==0"
                  :error-messages="v$.detail.total_weight_bill.$errors.map(e => e.$message)"
                  @blur="v$.detail.total_weight_bill.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- total price -->
              <v-col cols="12" md="6" v-if="ability_warehouse_category_operation?.allow_price">
                <v-text-field
                  v-model="detail.total_price"
                  :label="$t('total_price') + ' (Dh)'"
                  type="number"
                  :readonly="!!ability_warehouse_category_operation?.allow_weight"
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  class="text-teal"
                  color="teal"
                  :hide-details="v$.detail.total_price.$errors.length==0"
                  :error-messages="v$.detail.total_price.$errors.map(e => e.$message)"
                  @blur="v$.detail.total_price.$touch()"
                  >
                </v-text-field>
              </v-col>

              <!-- select package_type type -->
              <v-col cols="12" md="6" v-if="ability_warehouse_category_operation?.allow_package_type">
                <v-autocomplete
                  v-model="detail.package_type_id"
                  :items="_production.package_type.getList"
                  item-value="id"
                  :item-title="`name_${_main.lang.code}`"
                  :label="$t('package_type')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  class="text-teal"
                  color="teal"
                  :hide-details="v$.detail.package_type_id.$errors.length==0"
                  :error-messages="v$.detail.package_type_id.$errors.map(e => e.$message)"
                  @blur="v$.detail.package_type_id.$touch()"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="teal"
                        closable
                        variant="tonal"
                        @click:close="detail.package_type_id = null"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>

              <!-- number of packages -->
              <v-col cols="12" md="6" v-if="ability_warehouse_category_operation?.allow_package_type">
                <v-text-field
                  v-model="detail.total_package_count"
                  :label="$t('nbr_boxes')"
                  type="number"
                  readonly
                  density="compact"
                  variant="outlined"
                  rounded
                  @keydown.enter.prevent="submit"
                  class="text-teal"
                  color="teal"
                  :hide-details="v$.detail.total_package_count.$errors.length==0"
                  :error-messages="v$.detail.total_package_count.$errors.map(e => e.$message)"
                  @blur="v$.detail.total_package_count.$touch()"                  
                  >
                </v-text-field>
              </v-col>

              <!-- truck ids -->
              <v-col cols="12" md="6" v-if="ability_warehouse_category_operation?.allow_detail_truck_id">
                <v-autocomplete
                  v-model="detail.truck_ids"
                  :items="_main.getTrucks.filter(e=>_production.production.truck_ids.includes(e.id))"
                  :rules="[!v$.detail.truck_ids.$invalid ]"
                  :hide-details="v$.detail.truck_ids.$errors.length==0"
                  :error-messages="v$.detail.truck_ids.$errors.map(e => e.$message)"
                  @blur="v$.detail.truck_ids.$touch()"
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
                  color="teal"
                  class="text-teal"
                  >
                    <!-- Custom Chips Slot -->
                    <template #chip="{ item, index }">
                      <v-chip
                        :key="index"
                        class="text-white"
                        color="teal"
                        closable
                        variant="tonal"
                        @click:close="detail.truck_ids = detail.truck_ids.filter(num => num !== item.value)"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                </v-autocomplete>
              </v-col>
              <v-col>
                <v-btn
                  :disabled="v$.$invalid"
                  :color="`${is_editing ? 'amber' : 'teal'}`"
                  variant="tonal"
                  rounded="pill"
                  block
                  @click="submit"
                >
                  {{is_editing ? $t("edit_article") : $t("add_article") }}
                </v-btn>
              </v-col>
          </v-row>

            <v-row>
              <v-data-table-virtual
                :headers="headers"
                :items="warehouse_category_operation.charge_details"
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
                    <div class="my-3" v-if="headers.length < header_initial_length">
                      <v-btn
                        rounded="lg"
                        size="small"
                        text="Reset"
                        variant="tonal"
                        block
                        color="teal"
                        @click="onClickReset"
                      ></v-btn>

                      <v-divider></v-divider>
                    </div>
                  </v-expand-transition>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                  <div class="d-flex ga-2 justify-end" v-if="item.id != detail.id">
                    <v-icon color="amber" icon="mdi-pencil" size="small" @click="edit(item.id)"></v-icon>
                    <v-icon color="red" icon="mdi-delete" size="small" @click="remove(item.id)"></v-icon>
                  </div>
                </template>

                <template v-slot:[`item.stockable_id`]="{ item }">
                  <v-chip :color="item.description ? 'grey' : stockable_color">
                    {{ item.description ? item.description : _main.getStockableName(item.stockable_id,item.stockable_type) }}
                  </v-chip>
                </template>
                <template v-slot:[`item.total_weight`]="{ item }">
                  <div class="font-weight-medium">
                    {{ _main.formatDisplayNumber(item.total_weight) }}
                  </div>
                </template>
                <template v-slot:[`item.total_weight_invoice`]="{ item }">
                  <div class="font-weight-medium">
                    {{ _main.formatDisplayNumber(item.total_weight_bill) }}
                  </div>
                </template>
                <template v-slot:[`item.unit_price`]="{ item }">
                  <div class="font-weight-medium">
                    {{ _main.formatDisplayNumber(item.unit_price) }}
                  </div>
                </template>
                <template v-slot:[`item.total_price`]="{ item }">
                  <div class="font-weight-medium">
                    {{ _main.formatDisplayNumber(item.total_price) }}
                  </div>
                </template>
                <template v-slot:[`item.package_type_id`]="{ item }">
                  <v-chip color="secondary">
                    {{ getPackageName(item.package_type_id) }}
                  </v-chip>
                </template>
                <template v-slot:[`item.total_package_count`]="{ item }">
                  <div class="font-weight-medium">
                    {{item.total_package_count}}
                  </div>
                </template>
                <template v-slot:[`item.warehouse_id`]="{ item }">
                  <v-chip color="indigo">
                    {{ _main.getWarehouseName(item.warehouse_id) }}
                  </v-chip>
                </template>
                <template v-slot:[`item.truck_ids`]="{ item }">
                  <v-chip color="indigo" v-if="item.truck_ids && item.truck_ids.length>0">
                    {{ _main.getTruckNames(item.truck_ids) }}
                  </v-chip>
                  <v-chip :color="_main.not_aviable_color" v-else>
                    {{ $t('n/a') }}
                  </v-chip>
                </template>
                <template v-slot:no-data>
                  <div class="text-orange">
                    <v-icon icon="mdi-database-alert-outline" size="large" class="mx-2"></v-icon>
                    <span>No data available</span>
                  </div>
                </template>
              </v-data-table-virtual>
            </v-row>
        </v-card-text>
      </v-card>
</template>

<script>
    import { v4 as uuidv4 } from 'uuid';
    import { useProduction } from '@/stores/Production';
    import { useMain } from '@/stores/Main';
    import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';
    import useVuelidate from '@vuelidate/core';
    import { required, numeric, minLength, requiredIf } from '@vuelidate/validators';
    import { useFishType } from "@/stores/FishType";
    import { useTruck } from "@/stores/Truck";
    import { usePackageType } from "@/stores/PackageType";
    import { useGuanoType } from "@/stores/GuanoType";
    
    export default {
      props: {
        warehouse_category_operation: {
          type: Object,
          required: true
        },
        number: {
          type: Number,
          required: true
        }
      },
      data: function () {
        return {
          headers: [],
          selected: [],
          v$: useVuelidate(),
          is_editing: false,
          header_initial_length: 0,
          detail: {
              id: uuidv4(),
              total_weight: null,
              total_weight_bill: null,
              unit_price: null,
              total_price: null,
              total_package_count: null,
              stockable_id: null,
              stockable_type: null,
              description: null,
              warehouse_category_operation_id: null,
              warehouse_id: null,
              truck_ids: [],
              package_type_id: null,
          },
          type_detail: null
        };    
      },
      validations: function () {
        return {
          detail: {
              stockable_id: {
                required: requiredIf(() => this.type_detail != null),
              },
              description: {
                required: requiredIf(() => this.type_detail == null),
              },
              warehouse_id: {
                requiredIf: requiredIf(() => this.ability_warehouse_category_operation?.allow_warehouse),
                numeric,
              },
              truck_ids: {
                minLength: minLength(0),
              },
              total_weight: {
                required: requiredIf(() => this.ability_warehouse_category_operation?.allow_weight),
                numeric,
              },
              unit_price: {
                required: requiredIf(() => this.ability_warehouse_category_operation?.allow_price),
                numeric,
              },
              total_weight_bill: {
                required: requiredIf(() => this.ability_warehouse_category_operation?.allow_weight),
                numeric,
              },
              total_price: {
                required: requiredIf(() => this.ability_warehouse_category_operation?.allow_price),
                numeric,
              },
              package_type_id: {
                required: requiredIf(() => this.ability_warehouse_category_operation?.allow_package_type),
                numeric,
              },
              total_package_count: {
                required: requiredIf(() => this.ability_warehouse_category_operation?.allow_package_type),
                numeric,
              }
          }
        }
      },
      mounted() {
        this.getTypeDetail();
        this.init_headers();
      },
      computed: {
        _production() {
          return useProduction();
        },
        _main() {
          return useMain();
        },
        _fish_type() {
          return useFishType();
        },
        _truck() {
          return useTruck();
        },
        _package_type() {
          return usePackageType();
        },
        _guano_type() {
          return useGuanoType();
        },
        _warehouse_category_operation() {
          return useWarehouseCategoryOperation();
        },
        // Only show WCOs with operation_type === 'production'
        productionWcoList() {
          return this._production.productionWcoList;
        },
        store_warehouse_category_operation: function () {
          return this.productionWcoList.find(e => e.id == this.warehouse_category_operation.id)
            || this._warehouse_category_operation.getList.find(e => e.id == this.warehouse_category_operation.id);
        },
        getWarehouse() {
          return this._production.warehouse.list.filter(e => e.warehouse_type == this.store_warehouse_category_operation?.warehouse_type);
        },
        ability_warehouse_category_operation: function () {
          return this._warehouse_category_operation.getList.find(e => e.id == this.warehouse_category_operation.id);
        },
        stockable_color: function () {
          let warehouse_type = this._warehouse_category_operation.getList.find(e => e.id == this.warehouse_category_operation.id)?.warehouse_type;
          switch (warehouse_type) {
            case "PackageType":
                return "teal";
            case "FishType":
                return "cyan";
            case "GuanoType":
                return "brown";
            default:
              return "grey";
          }
        }
      },
      methods: {
        DEFAULT_HEADERS () {
          return [
            { title: this.$t("type_article"), value: 'stockable_id', removable: true, visible: true },
            { title: this.$t("weight_(KG)"), value: 'total_weight', removable: true, visible: this.ability_warehouse_category_operation?.allow_weight },
            { title: this.$t("weight_invoice_(KG)"), value: 'total_weight_bill', removable: true, visible: this.ability_warehouse_category_operation?.allow_weight },
            { title: this.$t("pu_(DH)", { currency: this._main.currency }), value: 'unit_price', removable: true, visible: this.ability_warehouse_category_operation?.allow_price },
            { title: this.$t("total_price_(DH)", { currency: this._main.currency }), value: 'total_price', removable: true, visible: this.ability_warehouse_category_operation?.allow_price },
            { title: this.$t("package_type"), value: 'package_type_id', removable: true, visible: this.ability_warehouse_category_operation?.allow_package_type },
            { title: this.$t("nbr_caisses"), value: 'total_package_count', removable: true, visible: this.ability_warehouse_category_operation?.allow_package_type },
            { title: this.$t("warehouse_name"), value: 'warehouse_id', removable: true, visible: this.ability_warehouse_category_operation?.allow_warehouse },
            { title: this.$t("truck_id"), value: 'truck_ids', removable: true, visible: this.ability_warehouse_category_operation?.allow_detail_truck_id },
            { title: this.$t("actions"), value: 'actions', align: 'end', sortable: false, removable: false, visible: true }
          ]
        },
        remove: function (detail_id) {
          this.warehouse_category_operation.charge_details = this.warehouse_category_operation.charge_details.filter(e => e.id !== detail_id);
          this.re_calculate();
        },
        submit: function () {
          this.detail.total_weight = this.detail.total_weight == null ? 0.00 : this.detail.total_weight;
          this.detail.total_weight_bill = this.detail.total_weight_bill == null ? 0.00 : this.detail.total_weight_bill;
          this.detail.total_price = this.detail.total_price == null ? 0.00 : this.detail.total_price;
          this.detail.total_package_count = this.detail.total_package_count == null ? 0.00 : this.detail.total_package_count;
          // If stockable_type is null and description has a value, normalize
          if (this.detail.stockable_type == null && this.detail.description) {
            this.detail.stockable_id = null;
            this.detail.stockable_type = null;
          }
          if (this.is_editing) {
            const idx = this.warehouse_category_operation.charge_details.findIndex(e => e.id === this.detail.id);
            if (idx !== -1) {
              this.warehouse_category_operation.charge_details[idx] = JSON.parse(JSON.stringify(this.detail));
            }
            this.is_editing = false;
          } else {
            this.warehouse_category_operation.charge_details.push(JSON.parse(JSON.stringify(this.detail)));
          }
          this.re_calculate();
          this.reset_detail();
          this.$refs.detail_focus_input?.focus();
        },
        reset_detail: function () {
          this.detail = {
              id: uuidv4(),
              total_weight: null,
              total_weight_bill: null,
              unit_price: null,
              total_price: null,
              total_package_count: null,
              stockable_id: null,
              stockable_type: this.detail.stockable_type,
              description: null,
              warehouse_category_operation_id: this.warehouse_category_operation.id,
              warehouse_id: null,
              truck_ids: [],
              package_type_id: null,
          };
          this.v$.$reset();
          this.is_editing = false;
        },
        edit: function (id) {
          this.is_editing = true;
          this.detail = JSON.parse(JSON.stringify(this.warehouse_category_operation.charge_details.find(e => e.id == id)));
        },
        onClickReset: function () {
          this.headers = this.DEFAULT_HEADERS().filter(header => header.visible == true);
        },
        remove_header: function (key) {
          this.headers = this.headers.filter(header => header.value !== key);
        },
        delete_warehouse_category_operation: function () {
          this._production.remove_warehouse_category_operation(
            this._production.production.warehouse_category_operations.findIndex(
              e => e.id === this.warehouse_category_operation.id
            )
          );
        },
        getTypeDetail: function () {
          let warehouse_type = this._warehouse_category_operation
            .getList.find(e => e.id == this.warehouse_category_operation.id)?.warehouse_type;
          this.detail.warehouse_category_operation_id = this.warehouse_category_operation.id;
          this.detail.stockable_id = null;
          this.detail.stockable_type = warehouse_type;
          switch (warehouse_type) {
            case "PackageType":
                this.type_detail = this._package_type.getList;
                break;
            case "FishType":
              let production_role = this._warehouse_category_operation.getList.find(e => e.id == this.warehouse_category_operation.id)?.production_role;
              this.type_detail = this._fish_type.getList.filter(e => e.type == production_role);
                break;
            case "GuanoType":
                this.type_detail = this._guano_type.getList;
                break;
            default:
              this.type_detail = null;
          }
        },
        init_headers: function () {
          let headers = this.DEFAULT_HEADERS().filter(header => header.visible == true);
          this.header_initial_length = headers.length;
          this.headers = headers;
        },
        is_used_warehouse_category_operation: function (id) {
          return this._production.production.warehouse_category_operations.some(e => e.id == id);
        },
        re_calculate: function () {
          this.warehouse_category_operation.total_price = +this.warehouse_category_operation.charge_details.reduce((acc, detail) => {
            return acc + (+detail.total_price || 0);
          }, 0);
          this.warehouse_category_operation.total_weight = +this.warehouse_category_operation.charge_details.reduce((acc, detail) => {
            return acc + (+detail.total_weight || 0);
          }, 0);
        },
        getPackageName: function (id) {
          if (!id) return this.$t('n/a');
          const pkg = this._package_type.getList.find(e => e.id == id);
          return pkg ? pkg[`name_${this._main.lang.code}`] : this.$t('n/a');
        },
        checkTypeInDetails: function () {
          this.warehouse_category_operation.charge_details.forEach(detail => {
            detail.stockable_type = this.detail.stockable_type;
            let type = null;
            let first = null;
            switch (detail.stockable_type) {
                case "PackageType":
                    type = this._package_type.getList.find(e => e.id == detail.stockable_id);
                    first = this._package_type.getList[0];
                    break;
                case "FishType":
                    type = this._fish_type.getList.find(e => e.id == detail.stockable_id);
                    first = this._fish_type.getList[0];
                    break;
                case "GuanoType":
                    type = this._guano_type.getList.find(e => e.id == detail.stockable_id);
                    first = this._guano_type.getList[0];
                    break;
                default:
                  detail.stockable_id = this.$t("add_description");
                  return;
            }
            if (!!type) {
              detail.stockable_id = type.id;
            } else if (first) {
              detail.stockable_id = first.id;
            }
          });
        }
      },
      watch: {
        "detail.unit_price": function () {
          this.detail.unit_price = +this.detail.unit_price;
          this.detail.total_price = +this.detail.total_weight * this.detail.unit_price;
          this.re_calculate();
        },
        "detail.total_weight": function (value) {
          this.detail.total_weight = +value;
          this.detail.total_weight_bill = +value;
          this.detail.total_price = +this._main.formatNumber(this.detail.total_weight * this.detail.unit_price);
          this.re_calculate();
        },
        "detail.total_weight_bill": function (value) {
          if (this.detail.package_type_id && value > 0) {
            const pkg = this._package_type.getList.find(e => e.id == this.detail.package_type_id);
            const size = pkg?.size || 1;
            this.detail.total_package_count = +(value / size).toFixed(1);
          } else {
            this.detail.total_package_count = 0;  
          }
        },
        "detail.package_type_id": function (value) {
          if (this.detail.package_type_id && value > 0) {
            const pkg = this._package_type.getList.find(e => e.id == value);
            const size = pkg?.size || 1;
            this.detail.total_package_count = +(this.detail.total_weight_bill / size).toFixed(1);
          } else {
            this.detail.total_package_count = 0;  
          }
        },
        "warehouse_category_operation.id": {
          handler: function () {
            this.getTypeDetail();
            this.init_headers();
            this.checkTypeInDetails();
            this.warehouse_category_operation.charge_details.forEach(detail => {
              detail.warehouse_category_operation_id = this.warehouse_category_operation.id;
            });
          },
          deep: true,
          immediate: true,
        },
      },
    }
</script>
