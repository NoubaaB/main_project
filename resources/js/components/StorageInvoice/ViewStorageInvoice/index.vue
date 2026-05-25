<template>
<v-card class="vsi-card" elevation="0">
  <div class="vsi-hero">
    <div class="vsi-hero-inner">
      <div class="d-flex align-center ga-4">
        <div class="vsi-hero-icon"><v-icon size="28" color="white">mdi-receipt-text-outline</v-icon></div>
        <div>
          <div class="vsi-hero-title">{{ $t('view_storage_invoice') }} <span v-if="invoice">#{{ invoice.invoice_number }}</span></div>
          <div class="text-white text-caption" style="opacity:.7">{{ invoice ? monthName(invoice.month) + ' ' + invoice.year : '' }}</div>
        </div>
      </div>
      <div class="d-flex ga-2 align-center">
        <v-btn v-if="invoice" v-print="printCfg" variant="tonal" color="white" rounded size="small" prepend-icon="mdi-printer">{{ $t('print') }}</v-btn>
        <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-arrow-left" @click="$router.push({ name: 'storage_invoices' })">{{ $t('back') }}</v-btn>
      </div>
    </div>
  </div>

  <v-card-text v-if="loading" class="pa-10 text-center"><v-progress-circular indeterminate color="#a21ee5"></v-progress-circular></v-card-text>

  <v-card-text v-else-if="invoice" class="pa-5">
    <!-- Info Cards -->
    <v-row dense class="mb-4">
      <v-col cols="12" sm="6" md="3">
        <div class="info-card"><v-icon size="20" color="#a21ee5" class="mb-1">mdi-warehouse</v-icon><div class="info-label">{{ $t('warehouse') }}</div><div class="info-value">{{ invoice.warehouse?.name || '-' }}</div></div>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <div class="info-card"><v-icon size="20" color="#a21ee5" class="mb-1">mdi-calendar-month</v-icon><div class="info-label">{{ $t('invoice_period') }}</div><div class="info-value">{{ monthName(invoice.month) }} {{ invoice.year }}</div></div>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <div class="info-card"><v-icon size="20" color="#a21ee5" class="mb-1">mdi-truck-delivery-outline</v-icon><div class="info-label">{{ $t('supplier') }}</div><div class="info-value">{{ invoice.supplier?.name || '-' }}</div></div>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <div class="info-card"><v-icon size="20" color="success" class="mb-1">mdi-cash</v-icon><div class="info-label">{{ $t('total_to_pay') }}</div><div class="info-value text-success">{{ formatNum(invoice.total_amount) }} MAD</div></div>
      </v-col>
    </v-row>

    <!-- Stock Summary -->
    <v-row dense class="mb-4">
      <v-col cols="6" md="3">
        <div class="info-card"><v-icon size="18" color="info" class="mb-1">mdi-arrow-down-bold</v-icon><div class="info-label">{{ $t('stock_in') }}</div><div class="info-value text-info">{{ formatNum(invoice.total_stock_in_kg) }} KG</div></div>
      </v-col>
      <v-col cols="6" md="3">
        <div class="info-card"><v-icon size="18" color="warning" class="mb-1">mdi-arrow-up-bold</v-icon><div class="info-label">{{ $t('stock_out') }}</div><div class="info-value text-warning">{{ formatNum(invoice.total_stock_out_kg) }} KG</div></div>
      </v-col>
      <v-col cols="6" md="3">
        <div class="info-card"><v-icon size="18" color="deep-purple" class="mb-1">mdi-arrow-down-bold-circle</v-icon><div class="info-label">{{ $t('manipulation_in') }}</div><div class="info-value">{{ formatNum(invoice.manipulation_in_amount) }} MAD</div></div>
      </v-col>
      <v-col cols="6" md="3">
        <div class="info-card"><v-icon size="18" color="orange" class="mb-1">mdi-arrow-up-bold-circle</v-icon><div class="info-label">{{ $t('manipulation_out') }}</div><div class="info-value">{{ formatNum(invoice.manipulation_out_amount) }} MAD</div></div>
      </v-col>
    </v-row>

    <!-- Extra Info Row -->
    <v-row dense class="mb-5">
      <v-col cols="6" md="3">
        <div class="info-card"><v-icon size="18" color="#a21ee5" class="mb-1">mdi-clock-outline</v-icon><div class="info-label">{{ $t('sejour_stockage') }}</div><div class="info-value">{{ formatNum(invoice.sejour_amount) }} MAD</div></div>
      </v-col>
      <v-col cols="6" md="3">
        <div class="info-card"><v-icon size="18" class="mb-1">mdi-tag-outline</v-icon><div class="info-label">{{ $t('status') }}</div><div class="info-value"><v-chip :color="invoice.status==='finalized'?'success':'warning'" size="small" variant="tonal">{{ $t(invoice.status) }}</v-chip></div></div>
      </v-col>
      <v-col cols="6" md="3">
        <div class="info-card"><v-icon size="18" class="mb-1">mdi-file-document-outline</v-icon><div class="info-label">{{ $t('invoice_number') }}</div><div class="info-value"><v-chip color="#a21ee5" variant="tonal" size="small" class="font-weight-bold">{{ invoice.invoice_number }}</v-chip></div></div>
      </v-col>
      <v-col cols="6" md="3">
        <div class="info-card"><v-icon size="18" class="mb-1">mdi-account-outline</v-icon><div class="info-label">{{ $t('generated_by') }}</div><div class="info-value">{{ invoice.generated_by_user?.name || '-' }}</div></div>
      </v-col>
    </v-row>

    <!-- Invoice Lines Table -->
    <div>
      <div class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center"><v-icon size="20" color="#a21ee5" class="mr-2">mdi-table</v-icon>{{ $t('prestations') }}</div>
      <v-data-table-virtual
        :headers="lineHeaders"
        :items="invoice.lines || []"
        density="comfortable"
        class="rounded-lg"
        hover
      >
        <template v-slot:[`item.description`]="{ item }"><span class="font-weight-medium">{{ item.description }}</span></template>
        <template v-slot:[`item.quantity_kg`]="{ item }">{{ formatNum(item.quantity_kg) }} KG</template>
        <template v-slot:[`item.quantity_days`]="{ item }">{{ item.quantity_days > 0 ? item.quantity_days : '-' }}</template>
        <template v-slot:[`item.unit_price`]="{ item }">{{ item.unit_price }} MAD</template>
        <template v-slot:[`item.total_amount`]="{ item }"><span class="font-weight-bold text-success">{{ formatNum(item.total_amount) }} MAD</span></template>
      </v-data-table-virtual>
    </div>

  </v-card-text>

  <v-card-text v-else class="pa-10 text-center">
    <v-icon size="60" color="grey-lighten-2" class="mb-3">mdi-file-search-outline</v-icon>
    <p class="text-body-1 text-medium-emphasis">{{ $t('storage_invoice_not_found') }}</p>
    <v-btn color="#a21ee5" rounded variant="tonal" class="mt-3" prepend-icon="mdi-arrow-left" @click="$router.push({ name: 'storage_invoices' })">{{ $t('back') }}</v-btn>
  </v-card-text>
</v-card>

<!-- Hidden Print Area -->
<div style="display:none"><div id="vsi-print-area" v-if="invoice">
  <div style="padding:24px 32px;max-width:900px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a1a">
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
      <div><img :src="logoUrl" alt="Logo" style="height:52px" /></div>
      <div style="text-align:center;flex:1"><h1 style="font-size:20px;font-weight:800;color:#7B1FA2;margin:0">{{ $t('facture_de_stockage') }}</h1><p style="font-size:12px;color:#78909C;margin:2px 0 0">{{ invoice.warehouse?.name }}</p></div>
      <div style="text-align:right;font-size:11px;color:#546E7A"><div><b>{{ $t('invoice_number') }}:</b> {{ invoice.invoice_number }}</div><div><b>{{ $t('invoice_period') }}:</b> {{ monthName(invoice.month) }} {{ invoice.year }}</div><div><b>{{ $t('date') }}:</b> {{ new Date().toLocaleDateString() }}</div></div>
    </div>
    <div style="height:3px;background:linear-gradient(90deg,#7B1FA2,#CE93D8,#7B1FA2);border-radius:2px;margin:12px 0 20px"></div>
    <!-- Info Row -->
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px"><tbody>
      <tr><td class="si-pl">{{ $t('warehouse') }}</td><td class="si-pv">{{ invoice.warehouse?.name }}</td><td class="si-pl">{{ $t('invoice_number') }}</td><td class="si-pv">{{ invoice.invoice_number }}</td></tr>
      <tr><td class="si-pl">{{ $t('invoice_period') }}</td><td class="si-pv">{{ monthName(invoice.month) }} {{ invoice.year }}</td><td class="si-pl">{{ $t('status') }}</td><td class="si-pv">{{ invoice.status }}</td></tr>
      <tr><td class="si-pl">{{ $t('supplier') }}</td><td class="si-pv">{{ invoice.supplier?.name || '-' }}</td><td class="si-pl">{{ $t('stock_in') }} (KG)</td><td class="si-pv">{{ formatNum(invoice.total_stock_in_kg) }}</td></tr>
      <tr><td class="si-pl">{{ $t('stock_out') }} (KG)</td><td class="si-pv">{{ formatNum(invoice.total_stock_out_kg) }}</td><td class="si-pl"></td><td class="si-pv"></td></tr>
    </tbody></table>
    <!-- Lines -->
    <table style="width:100%;border-collapse:collapse"><thead><tr>
      <th class="si-ph" style="text-align:left">{{ $t('prestations') }}</th><th class="si-ph">{{ $t('quantity_kg') }} (KG)</th><th class="si-ph">{{ $t('quantity_days_short') }} (J)</th><th class="si-ph">{{ $t('unit_price_pu') }} (MAD)</th><th class="si-ph">{{ $t('total') }} (MAD)</th>
    </tr></thead><tbody>
      <tr v-for="line in invoice.lines" :key="line.id"><td class="si-pc">{{ line.description }}</td><td class="si-pv2">{{ formatNum(line.quantity_kg) }}</td><td class="si-pv2">{{ line.quantity_days > 0 ? line.quantity_days : '-' }}</td><td class="si-pv2">{{ line.unit_price }}</td><td class="si-pv2" style="font-weight:700">{{ formatNum(line.total_amount) }}</td></tr>
    </tbody><tfoot>
      <tr><td colspan="4" style="text-align:right;font-weight:800;font-size:15px;padding:12px 10px;border-top:2px solid #7B1FA2">{{ $t('total_to_pay') }} (MAD):</td><td style="text-align:center;font-weight:800;font-size:18px;color:#7B1FA2;padding:12px 10px;border-top:2px solid #7B1FA2">{{ formatNum(invoice.total_amount) }}</td></tr>
    </tfoot></table>
    <!-- Signatures -->
    <div style="display:flex;justify-content:space-between;margin-top:48px;gap:60px">
      <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:40px;margin-bottom:6px"></div><div style="font-size:11px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('la_direction') }}</div></div>
      <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:40px;margin-bottom:6px"></div><div style="font-size:11px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('company_signature') }}</div></div>
    </div>
    <div style="text-align:center;margin-top:24px;font-size:10px;color:#9E9E9E">{{ $t('printed_on') }}: {{ new Date().toLocaleString() }}</div>
  </div>
</div></div>
</template>

<script>
import axios from 'axios';
import { useMain } from '@/stores/Main';
import print from 'vue3-print-nb';

export default {
  name: 'ViewStorageInvoice',
  directives: { print },
  data() {
    return {
      invoice: null,
      loading: true,
      printCfg: { id: 'vsi-print-area', popTitle: 'Storage Invoice' },
    };
  },
  computed: {
    main() { return useMain(); },
    logoUrl() { return new URL('/storage/images/logo_login.png', window.location.origin).href; },
    lineHeaders() {
      return [
        { title: this.$t('prestations'), key: 'description', align: 'start' },
        { title: this.$t('quantity_kg') + ' (KG)', key: 'quantity_kg', align: 'center' },
        { title: this.$t('quantity_days_short') + ' (J)', key: 'quantity_days', align: 'center' },
        { title: this.$t('unit_price_pu') + ' (MAD)', key: 'unit_price', align: 'center' },
        { title: this.$t('total') + ' (MAD)', key: 'total_amount', align: 'end' },
      ];
    },
  },
  mounted() { this.fetchInvoice(); },
  methods: {
    formatNum(v) { return this.main.formatDisplayNumber(v); },
    monthName(m) {
      const k = ['', 'month_january', 'month_february', 'month_march', 'month_april', 'month_may', 'month_june', 'month_july', 'month_august', 'month_september', 'month_october', 'month_november', 'month_december'];
      return this.$t(k[m] || '');
    },
    async fetchInvoice() {
      this.loading = true;
      try {
        const res = await axios.get(`/api/storage-invoices/${this.$route.params.id}`);
        this.invoice = res.data.storage_invoice;
      } catch (e) { console.error(e); this.invoice = null; }
      this.loading = false;
    },
  },
};
</script>

<style scoped>
.vsi-card { border-radius: 20px !important; font-family: 'Inter', sans-serif; }
.vsi-hero { background: linear-gradient(135deg, #6A1B9A 0%, #8E24AA 40%, #CE93D8 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 80px; }
.vsi-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.vsi-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; }
.vsi-hero-title { font-size: 18px; font-weight: 800; }
.info-card { padding: 16px; border-radius: 14px; border: 1.5px solid rgba(var(--v-border-color), .1); text-align: center; }
.info-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: rgba(var(--v-theme-on-surface), .5); }
.info-value { font-size: 15px; font-weight: 800; margin-top: 2px; }
</style>

<style>
/* Print area styles (unscoped) */
#vsi-print-area .si-pl { padding: 6px 10px; font-weight: 700; font-size: 12px; color: #546E7A; width: 18%; border-bottom: 1px solid #EEEEEE; }
#vsi-print-area .si-pv { padding: 6px 10px; font-size: 12px; border-bottom: 1px solid #EEEEEE; }
#vsi-print-area .si-ph { background: #F3E5F5; color: #4A148C; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 8px 10px; border-bottom: 2px solid #CE93D8; text-align: center; letter-spacing: .5px; }
#vsi-print-area .si-pc { padding: 7px 10px; border-bottom: 1px solid #EEE; font-size: 12px; }
#vsi-print-area .si-pv2 { padding: 7px 10px; text-align: center; border-bottom: 1px solid #EEE; font-size: 12px; }
@media print { #vsi-print-area { display: block !important; } }
</style>
