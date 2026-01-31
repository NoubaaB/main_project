<template>
  <v-row>
    <v-col>
      <SupplierInvoice 
      v-for="supplier_invoice in supplier_invoices" 
      :key="supplier_invoice.id" 
      :supplier_invoice_data="supplier_invoice" 
      />
    </v-col>
    <v-col cols="4">
      <SupplierInvoiceContoller />
    </v-col>
  </v-row>
</template>

<script>
  import { useSupplierInvoice } from '@/stores/SupplierInvoice';
  import SupplierInvoice from './SupplierInvoice.vue';
  import SupplierInvoiceContoller from './SupplierInvoiceContoller.vue';
  export default {
      components: { SupplierInvoice , SupplierInvoiceContoller },
      mounted() {
        console.log('Mounted ViewSupplierInvoice',this.supplierInvoice.getItems);
      },
      computed: {
        supplierInvoice: () => useSupplierInvoice(),
        supplier_invoices: function () {
          return this.supplierInvoice.getItems;
        },
      },
    }
</script>

<style lang="scss" scoped>

</style>