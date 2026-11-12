<template>
  <div class="invoice-view-container pa-3">
    <v-row class="invoice-page-layout align-start flex-nowrap" no-gutters>
      <!-- Invoice Document on Left -->
      <v-col class="invoice-content-col d-flex justify-center">
        <div id="invoice" class="invoice-width">
          <div v-if="loading" class="pa-8 bg-surface rounded-xl border d-flex flex-column ga-6">
            <!-- Header: Company & Invoice Info -->
            <div class="d-flex align-start justify-space-between flex-wrap ga-4 pb-4 border-b">
              <div class="d-flex align-center ga-3">
                <div class="skel-bone rounded-lg" style="width: 54px; height: 54px;"></div>
                <div class="d-flex flex-column ga-2">
                  <div class="skel-bone" style="width: 160px; height: 18px;"></div>
                  <div class="skel-bone" style="width: 120px; height: 12px;"></div>
                </div>
              </div>
              <div class="d-flex flex-column align-end ga-2">
                <div class="skel-bone" style="width: 110px; height: 26px; border-radius: 999px;"></div>
                <div class="skel-bone" style="width: 90px; height: 12px;"></div>
              </div>
            </div>

            <!-- Meta Details Grid -->
            <div class="d-flex justify-space-between flex-wrap ga-4 py-2">
              <div class="d-flex flex-column ga-2">
                <div class="skel-bone" style="width: 70px; height: 11px;"></div>
                <div class="skel-bone" style="width: 140px; height: 15px;"></div>
              </div>
              <div class="d-flex flex-column ga-2">
                <div class="skel-bone" style="width: 70px; height: 11px;"></div>
                <div class="skel-bone" style="width: 130px; height: 15px;"></div>
              </div>
              <div class="d-flex flex-column ga-2">
                <div class="skel-bone" style="width: 70px; height: 11px;"></div>
                <div class="skel-bone" style="width: 110px; height: 15px;"></div>
              </div>
            </div>

            <!-- Table Rows Skeleton -->
            <div class="border rounded-lg overflow-hidden">
              <div class="d-flex align-center justify-space-between pa-3 bg-surface-variant border-b">
                <div class="skel-bone" style="width: 30%; height: 14px;"></div>
                <div class="skel-bone" style="width: 20%; height: 14px;"></div>
                <div class="skel-bone" style="width: 20%; height: 14px;"></div>
                <div class="skel-bone" style="width: 15%; height: 14px;"></div>
              </div>
              <div v-for="r in 5" :key="'ci-view-skel-' + r" class="d-flex align-center justify-space-between pa-3 border-b">
                <div class="skel-bone" style="width: 35%; height: 13px;"></div>
                <div class="skel-bone" style="width: 18%; height: 13px;"></div>
                <div class="skel-bone" style="width: 18%; height: 13px;"></div>
                <div class="skel-bone" style="width: 14%; height: 13px;"></div>
              </div>
            </div>

            <!-- Totals Footer -->
            <div class="d-flex justify-end pt-2">
              <div class="d-flex flex-column align-end ga-2" style="min-width: 200px;">
                <div class="d-flex justify-space-between w-100">
                  <div class="skel-bone" style="width: 60px; height: 13px;"></div>
                  <div class="skel-bone" style="width: 80px; height: 13px;"></div>
                </div>
                <div class="d-flex justify-space-between w-100">
                  <div class="skel-bone" style="width: 70px; height: 13px;"></div>
                  <div class="skel-bone" style="width: 60px; height: 13px;"></div>
                </div>
                <div class="d-flex justify-space-between w-100 pt-2 border-t">
                  <div class="skel-bone" style="width: 70px; height: 18px;"></div>
                  <div class="skel-bone" style="width: 100px; height: 20px;"></div>
                </div>
              </div>
            </div>
          </div>
          <template v-else>
            <ClientInvoice
              v-for="client_invoice in client_invoices"
              :key="client_invoice.id"
              :client_invoice_data="client_invoice"
            />
          </template>
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
        client_invoices: [],
        loading: false
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
        this.loading = true;
        try {
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
        } finally {
          this.loading = false;
        }
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
    position: sticky !important;
    top: 70px !important;
    align-self: flex-start !important;
    z-index: 20;
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
      position: static !important;
      top: auto !important;
    }
  }

  @media print {
    .invoice-sidebar-col {
      display: none !important;
    }
  }
</style>
