<template>
  <div class="invoice-view-container pa-3">
    <v-row class="invoice-page-layout align-start flex-nowrap" no-gutters>
      <!-- Invoice Document on Left -->
      <v-col class="invoice-content-col d-flex justify-center">
        <div id="invoice" class="invoice-width">
          <ClientInvoice
            v-for="client_invoice in client_invoices"
            :key="client_invoice.id"
            :client_invoice_data="client_invoice"
          />
        </div>
      </v-col>

      <!-- Luxury Controller on Right -->
      <v-col class="invoice-sidebar-col">
        <ClientInvoiceController />
      </v-col>
    </v-row>
  </div>
</template>

<script>
  import { useClientInvoice } from '@/stores/ClientInvoice';
  import ClientInvoice from './ClientInvoice.vue';
  import ClientInvoiceController from './ClientInvoiceController.vue';
  export default {
    components: { ClientInvoice, ClientInvoiceController },
    data: function () {
      return {
        client_invoices: []
      };
    },
    activated: function () {
      this.get_client_invoices();
    },
    mounted: function () {
      this.get_client_invoices();
    },
    computed: {
      clientInvoice: () => useClientInvoice(),
    },
    methods: {
      get_client_invoices: async function () {
        let ids = this.$route.query.ids;
        if (typeof ids === 'string') {
          try {
            ids = JSON.parse(ids);
          } catch (e) {
            ids = ids.replace(/[\[\]]/g, '').split(',').map(s => Number(s.trim())).filter(Boolean);
          }
        }
        let data = await this.clientInvoice.getClientInvoicesByIds(ids);
        this.client_invoices = data;
      },
    }
  }
</script>

<style lang="scss" scoped>
  .invoice-view-container {
    width: 100%;
  }
  .invoice-page-layout {
    position: relative;
    width: 100%;
    margin: 0;
    gap: 20px;
  }
  .invoice-content-col {
    flex: 1 1 auto;
    min-width: 0;
    padding: 0;
  }
  .invoice-sidebar-col {
    width: 420px;
    max-width: 430px;
    min-width: 370px;
    flex: 0 0 420px;
    padding: 0;
  }
  .invoice-width {
    width: var(--invoice-width) !important;
    margin: 0 auto;
    padding: 0;
  }

  @media (max-width: 1180px) {
    .invoice-page-layout {
      flex-wrap: wrap !important;
    }
    .invoice-sidebar-col {
      width: 100% !important;
      max-width: 100% !important;
      flex: 1 1 100% !important;
    }
  }
</style>
