<template>
  <v-row>
    <v-col>
      <div id="invoice" class="invoice-width">
        <SupplierInvoice 
        v-for="supplier_invoice in supplier_invoices" 
        :key="supplier_invoice.id" 
        :supplier_invoice_data="supplier_invoice" 
        />
      </div>
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
  components: { SupplierInvoice, SupplierInvoiceContoller },
      data:function(){
        return {
          supplier_invoices : []
        }
      },
      mounted:function(){
        this.get_supplier_invoices();
      },
      computed: {
        supplierInvoice: () => useSupplierInvoice(),
      },
      methods:{
        get_supplier_invoices: async function () {
          let data = await this.supplierInvoice.getSupplierInvoicesByIds(this.$route.query.ids)
          console.log(data,this.supplierInvoice.getItems)
          this.supplier_invoices = data;
        },
      }
    }
</script>

<style lang="scss" scoped >
  .invoice-width{
    width : var(--invoice-width) !important;
    margin: 0px;
    padding: 0px;
  }
</style>