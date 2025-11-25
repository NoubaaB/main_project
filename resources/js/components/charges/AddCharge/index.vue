<template>
    <v-form ref="form" @submit.prevent="submitForm">
      <v-row>
        <v-col cols="12" md="5">
           <!-- Necessary Charge information -->
          <NecessaryInformation :charge="_charge"/>
        </v-col>
        <v-divider vertical ></v-divider>
        <v-col cols="12" md="7">
          <!-- Fresh Fish Receive Details -->
          <Charge class="mt-5" v-for="charge in _charge.charges" :key="charge.id"  :charge="charge" />
          <v-divider vertical ></v-divider>
          <!-- Charge Summary -->
          <ChargeSummary :charge="_charge.charge" />
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
  