<template>
  <div class="invoice-view-container pa-3">
    <v-row class="invoice-page-layout align-start flex-nowrap" no-gutters>
      <!-- Invoice Document on Left -->
      <v-col class="invoice-content-col d-flex justify-center">
        <div id="invoice" class="invoice-width">
          <SupplierInvoice
            v-for="supplier_invoice in supplier_invoices"
            :key="supplier_invoice.id"
            :supplier_invoice_data="supplier_invoice"
          />
        </div>
      </v-col>

      <!-- Luxury Controller on Right -->
      <v-col class="invoice-sidebar-col">
        <SupplierInvoiceContoller />
      </v-col>
    </v-row>
  </div>
</template>

<script>
  import { useSupplierInvoice } from '@/stores/SupplierInvoice';
  import SupplierInvoice from './SupplierInvoice.vue';
  import SupplierInvoiceContoller from './SupplierInvoiceContoller.vue';
  export default {
    components: { SupplierInvoice, SupplierInvoiceContoller },
    data: function () {
      return {
        supplier_invoices: []
      };
    },
    activated: function () {
      this.get_supplier_invoices();
    },
    mounted: function () {
      this.get_supplier_invoices();
    },
    computed: {
      supplierInvoice: () => useSupplierInvoice(),
    },
    methods: {
      get_supplier_invoices: async function () {
        let ids = this.$route.query.ids;
        if (typeof ids === 'string') {
          try {
            ids = JSON.parse(ids);
          } catch (e) {
            ids = ids.replace(/[\[\]]/g, '').split(',').map(s => Number(s.trim())).filter(Boolean);
          }
        }
        let data = await this.supplierInvoice.getSupplierInvoicesByIds(ids);
        this.supplier_invoices = data;
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
