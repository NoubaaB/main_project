<template>
  <v-row>
    <v-col>
      <div id="invoice" class="invoice-width">
        <ClientInvoice
        v-for="client_invoice in client_invoices"
        :key="client_invoice.id"
        :client_invoice_data="client_invoice"
        />
      </div>
    </v-col>
    <v-col cols="4">
      <ClientInvoiceController />
    </v-col>
  </v-row>
</template>

<script>
  import { useClientInvoice } from '@/stores/ClientInvoice';
  import ClientInvoice from './ClientInvoice.vue';
  import ClientInvoiceController from './ClientInvoiceController.vue';
  export default {
  components: { ClientInvoice, ClientInvoiceController },
      data:function(){
        return {
          client_invoices : []
        }
      },
      activated:function(){
        this.get_client_invoices();
      },
      mounted:function(){
        this.get_client_invoices();
      },
      computed: {
        clientInvoice: () => useClientInvoice(),
      },
      methods:{
          get_client_invoices: async function () {
          let data = await this.clientInvoice.getClientInvoicesByIds(this.$route.query.ids)
          this.client_invoices = data;
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
