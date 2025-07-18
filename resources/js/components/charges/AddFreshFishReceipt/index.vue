<template>
    <v-form ref="form" @submit.prevent="submitForm">
      <v-row>
        <v-col cols="12" md="6">
           <!-- Necessary Receipt information -->
          <NecessaryInformation :receipt="_receipt"/>
        </v-col>
        <v-divider vertical ></v-divider>
        <v-col cols="12" md="6">
          <!-- Fresh Fish Receive Details -->
          <FreshFishReceiveDetail v-if="_receipt.add_fresh_fish_receive_details" :receipt="_receipt.receipt" />
          <v-divider vertical ></v-divider>
          <!-- Freeze Fish Process Details -->
          <FreezeFishProcessDetail v-if="_receipt.add_freeze_fish_process_details" :receipt="_receipt.receipt" />
          <v-divider vertical ></v-divider>
          <!-- Receipt Summary -->
          <ReceiptSummary :receipt="_receipt.receipt" />
        </v-col>
      </v-row>
    </v-form>
  </template>
  
  <script>
    import { useReceipt } from '@/stores/Receipt';
    import NecessaryInformation from './NecessaryInformation.vue';
    import FreshFishReceiveDetail from './FreshFishReceiveDetail.vue';
    import FreezeFishProcessDetail from './FreezeFishProcessDetail.vue';
    import ReceiptSummary from './ReceiptSummary.vue';
    export default {
      components: {
        NecessaryInformation,
        FreshFishReceiveDetail,
        FreezeFishProcessDetail,
        ReceiptSummary
      },
      data: function() {
        return {
          form: null,
        }
      },
      computed: {
        _receipt: function () {
          return useReceipt();
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
  