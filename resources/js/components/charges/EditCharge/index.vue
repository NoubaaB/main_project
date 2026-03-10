<template>
    <v-form ref="form" @submit.prevent="submitForm" v-if="_charge.edit_charge.id">
      <v-row>
        <v-col cols="12" md="5">
           <!-- Necessary Charge information -->
          <NecessaryInformation />
        </v-col>
        <v-divider vertical ></v-divider>
        <v-col cols="12" md="7">
          <!-- Fresh Fish Receive Details -->
          <Charge class="mb-5" v-for="(warehouse_category_operation , index) in _charge.edit_charge.warehouse_category_operations" :key="warehouse_category_operation.id"  :warehouse_category_operation="warehouse_category_operation" :number="index+1" />
          <v-divider class="my-5" ></v-divider>
          <!-- Charge Summary -->
          <ChargeSummary />
        </v-col>
      </v-row>
    </v-form>
  </template>
  
  <script>
    import { useCharge } from '@/stores/Charge';
    import NecessaryInformation from './NecessaryInformation.vue';
    import Charge from './Charge.vue';
    import ChargeSummary from './ChargeSummary.vue';
    export default {
      components: {
        NecessaryInformation,
        Charge,
        ChargeSummary
      },
      beforeMount: function () {
         if(this.$route.params.id) this._charge.fetchChargeById(this.$route.params.id);  
      },
      data: function() {
        return {
          form: null,
        }
      },
      computed: {
        _charge: function () {
          return useCharge();
        }
      },
      methods: {
        submitForm() {
          this.$refs.form.validate().then(success => {
            if (success) {
              console.log("Form submitted:", this.freshFishReceive);
              // Axios POST or emit event here
            }
          })
        },
      },
    }
  </script>
  