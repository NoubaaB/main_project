<template>
    <v-form ref="form" @submit.prevent="submitForm" v-if="_sell.edit_sell.id">
      <v-row>
        <v-col cols="12" md="5">
           <!-- Necessary Sell information -->
          <NecessaryInformation />
        </v-col>
        <v-divider vertical ></v-divider>
        <v-col cols="12" md="7">
          <!-- Fresh Fish Receive Details -->
          <Sell class="mb-5" v-for="(warehouse_category_operation , index) in _sell.edit_sell.warehouse_category_operations" :key="warehouse_category_operation.id"  :warehouse_category_operation="warehouse_category_operation" :number="index+1" />
          <v-divider class="my-5" ></v-divider>
          <!-- Sell Summary -->
          <SellSummary />
        </v-col>
      </v-row>
    </v-form>
  </template>
  
  <script>
    import { useSell } from '@/stores/Sell';
    import NecessaryInformation from './NecessaryInformation.vue';
    import Sell from './Sell.vue';
    import SellSummary from './SellSummary.vue';
    export default {
      components: {
        NecessaryInformation,
        Sell,
        SellSummary
      },
      beforeMount: function () {
         if(this.$route.params.id) this._sell.fetchSellById(this.$route.params.id);  
      },
      data: function() {
        return {
          form: null,
        }
      },
      computed: {
        _sell: function () {
          return useSell();
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
  