<template>
    <v-card class="ma-auto rounded-lg">
        <v-row>
          <v-col>
              <v-btn variant="tonal" block rounded color="info" @click="_charge.add_warehouse_category_operation">
                <v-icon class="mx-2">mdi-receipt-text-plus</v-icon>
                {{$t("add_detail_charge")}}
              </v-btn>
          </v-col>
        </v-row>
        <v-card-text>
          <v-row>
            <v-col class="d-flex align-center">
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <v-icon color="yellow" class="text-info">mdi-lightbulb-alert</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>{{$t("summary")}}</span>
                  </v-col>
                </v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
            </v-col>
          </v-row>
          <v-row class="pt-2">
            <!-- Total Price -->
            <v-col cols="12" md="6">
              <v-card
              border="opacity-50 success sm"
              class="mx-auto bg-green-lighten-5"
              max-width="360"
              rounded="xl"
              color="green-darken-3"
              variant="text"
            >
              <template v-slot:title>
                <div class="text-caption font-weight-bold">{{$t("total_price")}}</div>
              </template>
        
              <template v-slot:append>
                <v-chip
                  border="success sm opacity-100"
                  color="green"
                  variant="tonal"
                >
                  <v-icon icon="mdi-cash"></v-icon>
                </v-chip>
              </template>
        
              <template v-slot:text>
                <div class="text-h5 font-weight-black">
                  <small>
                    <v-btn
                      icon="mdi-cash-multiple"
                      size="x-small"
                      variant="text"
                      color="green"
                      border="green sm"
                      class="ma-1"
                    ></v-btn>
                  </small>                  
                  {{_main.formatDisplayNumber(_charge.charge.total_price)}} MAD
                </div>
                <small class="text-caption text-medium-emphasis d-flex justify-space-between align-center"
                v-for="warehouse_category_operation in _charge.charge.warehouse_category_operations"
                :key="warehouse_category_operation.id"
                >
                  <div>
                    <span class="text-green">
                      <v-avatar icon="mdi-cash" size="x-small" variant="tonal"></v-avatar>
                      + {{ _main.formatDisplayNumber(get_warehouse_category_operation_total_price(warehouse_category_operation)) }} MAD
                    </span>
                    
                    ({{ get_warehouse_category_operation_name(warehouse_category_operation) }})
                  </div>
                </small>
              </template>
              </v-card>            
            </v-col>
            <!--  Total Weight -->
            <v-col cols="12" md="6">
              <v-card
              border="opacity-50 warning sm"
              class="mx-auto bg-orange-lighten-5"
              color="orange-darken-3"
              max-width="360"
              rounded="xl"
              variant="text"
            >
                <template v-slot:title>
                  <div class="text-caption font-weight-bold">{{$t("total_weight")}}</div>
                </template>
          
                <template v-slot:append>
                  <v-chip
                    border="warning sm opacity-100"
                    color="orange"
                    variant="tonal"
                  >
                    <v-icon icon="mdi-scale-balance" center></v-icon>
                  </v-chip>
                </template>
          
                <template v-slot:text>
                  <div class="text-h6 font-weight-black d-inline">
                    <small>
                      <v-btn
                        icon="mdi-weight-kilogram"
                        size="x-small"
                        variant="text"
                        color="orange"
                        border="orange sm"
                        class="ma-1"
                      ></v-btn>
                    </small>
                    {{ _main.formatDisplayNumber(_charge.charge.total_weight) }} KG
                  </div>
                  <small class="text-caption text-medium-emphasis d-flex justify-space-between align-center"
                  v-for="warehouse_category_operation in _charge.charge.warehouse_category_operations"
                  :key="warehouse_category_operation.id"
                  >
                    <div>
                      <span class="text-orange">
                        <v-avatar icon="mdi-weight-kilogram" size="x-small" variant="tonal"></v-avatar>
                        + {{ _main.formatDisplayNumber(get_warehouse_category_operation_total_weight(warehouse_category_operation)) }} KG
                      </span>
                      
                      ({{ get_warehouse_category_operation_name(warehouse_category_operation) }})
                    </div>
                  </small>              
                </template>
              </v-card>    
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
</template>

<script>
    import { useCharge } from '@/stores/Charge';
    import { useMain } from '@/stores/Main';
    import { useVuelidate } from '@vuelidate/core';
    import { required , minValue } from '@vuelidate/validators';

    export default {
        data: function () {
          return {
            v$: useVuelidate()
          }
        },
        computed: {
          _charge() {
              return useCharge();
          },
          _main() {
              return useMain();
          }
        },
        validations() {
          return {
            _charge: {
              charge: {
                charge_type_id: { required },
                date: { required },
                tracking_numbers: { required },
                supplier_ids: { required },
                fishery_id: { required },
                origin_id: { required },
                total_price: { required , maxValueValue : minValue(1) },
                total_weight: { required , maxValueValue : minValue(1) },
                is_warehouseable: { required }
              }
            }
          }
        },
        methods:{
          get_warehouse_category_operation_total_price: function (warehouse_category_operation) {
            return warehouse_category_operation.total_price;
          },
          get_warehouse_category_operation_total_weight: function (warehouse_category_operation) {
            return warehouse_category_operation.total_weight
          },
          get_warehouse_category_operation_name: function (warehouse_category_operation) {
            return this._charge.getWarehouseCategoryOperationName(warehouse_category_operation);
          }
        }
      }
</script>

<style scoped>

</style>