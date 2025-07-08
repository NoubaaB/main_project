<template>
    <v-form ref="form" @submit.prevent="submitForm">
      <v-row>
        <v-col cols="12" md="6">
           <!-- Necessary Receipt information -->
          <NecessaryInformation :receipt="receipt"/>
        </v-col>
        <v-divider vertical ></v-divider>
        <v-col cols="12" md="6">
          <!-- Receipt Details -->
          <ReceiptDetails :receipt="receipt" />
          <v-divider vertical ></v-divider>
          <!-- Receipt Summary -->
          <ReceiptSummary :receipt="receipt" />
        </v-col>
      </v-row>
    </v-form>
  </template>
  
  <script>
    import { useReceipt } from '@/stores/Receipt';
    import NecessaryInformation from './NecessaryInformation.vue';
    import ReceiptDetails from './ReceiptDetails.vue';
    import ReceiptSummary from './ReceiptSummary.vue';
    export default {
      components: {
        NecessaryInformation,
        ReceiptDetails,
        ReceiptSummary
      },
      data: function() {
        return {
          form: null,
        }
      },
      computed: {
        receipt: function () {
          return useReceipt().receipt;
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
  