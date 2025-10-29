<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <span>
            {{ $t("charge_summary") }}
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
                    <v-icon color="yellow" class="text-blue">mdi-lightbulb-alert</v-icon>
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
              class="mx-auto"
              max-width="360"
              rounded="xl"
              variant="text"
            >
              <template v-slot:title>
                <div class="text-caption font-weight-bold">{{$t("total_price")}}</div>
              </template>
        
              <template v-slot:append>
                <v-chip
                  border="success sm opacity-100"
                  color="green"
                  size="small"
                  variant="text"
                >
                  <v-icon icon="mdi-arrow-up" start></v-icon> 13%
                </v-chip>
              </template>
        
              <template v-slot:text>
                <div class="text-h5 font-weight-black">{{_main.formatDisplayNumber(_charge.charge.total_price)}} MAD</div>
        
                <small class="text-caption text-medium-emphasis d-flex justify-space-between align-center">
                  <div>
                    <span class="text-green">
                      <v-avatar icon="mdi-arrow-up" size="x-small" variant="tonal"></v-avatar>
                      + {{ _main.formatDisplayNumber(_charge.charge.total_price) }} MAD
                    </span>
        
                    in the last week
                  </div>
                </small>
              </template>
              </v-card>            
            </v-col>
            <!--  Total Weight -->
            <v-col cols="12" md="6">
              <v-card
              border="opacity-50 info sm"
              class="mx-auto"
              max-width="360"
              rounded="xl"
              variant="text"
            >
              <template v-slot:title>
                <div class="text-caption font-weight-bold">{{$t("total_weight")}}</div>
              </template>
        
              <template v-slot:append>
                <v-chip
                  border="success sm opacity-100"
                  color="info"
                  size="small"
                  variant="text"
                >
                  <v-icon icon="mdi-scale" center></v-icon>
                </v-chip>
              </template>
        
              <template v-slot:text>
                <div class="text-h6 font-weight-black d-inline">
                  <small>
                    <v-btn
                      icon="mdi-weight-kilogram"
                      size="x-small"
                      variant="text"
                      color="info"
                      border="info sm"
                    ></v-btn>
                  </small>
                   {{ _main.formatDisplayNumber(_charge.charge.total_weight) }} Kg
                </div>
                <small class="text-caption text-medium-emphasis d-flex justify-space-between align-center">
                  <div>
                    {{ $t("total_weight_of_all_items_s_Weight_bill") }}
                  </div>
                </small>
              </template>
              </v-card>    
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider class="border-opacity-25" color="info"></v-divider>
        <!-- Submit Button -->
        <v-card-actions>
          <v-row class="text-center py-3">
            <v-col>
              <v-btn :disabled="v$.$invalid" block variant="tonal"  rounded color="info" @click="submit">{{$t("submit")}}</v-btn>
            </v-col>
            <v-divider inset vertical class="border-opacity-25" color="info"></v-divider>
            <v-col class="pr-1">
              <v-btn block variant="outlined" rounded color="amber" @click="reset_charge_detail">{{$t(('cancel'))}}</v-btn>
            </v-col>
          </v-row>
        </v-card-actions>
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
          submit: function () {
            this._charge.submitCharge();
          },
          reset_charge_detail: function () {
            this._charge.reset_charge_detail();
          }
        }
      }
</script>

<style scoped>

</style>