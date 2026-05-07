<template>
  <v-card class="si-card" elevation="0">
    <div class="si-hero"><div class="si-hero-inner"><div class="d-flex align-center ga-4"><div class="si-hero-icon"><v-icon size="28" color="white">mdi-receipt-text-outline</v-icon></div><div><div class="si-hero-title">{{ $t('storage_invoices') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('storage_invoice_desc') }}</div></div></div></div></div>
    <v-card-text class="pa-4">
      <v-row class="mb-4">
        <v-col cols="12" md="4">
          <v-autocomplete v-model="selectedWarehouse" :items="sejourWarehouses" item-title="name" item-value="id" :label="$t('select_warehouse')" chips density="compact" variant="outlined" rounded color="#a21ee5" class="text-info" hide-details @update:model-value="fetchInvoices">
            <template #chip="{ item }"><v-chip color="#a21ee5" variant="tonal">{{ item.title }}</v-chip></template>
          </v-autocomplete>
        </v-col>
        <v-col cols="6" md="2"><v-select v-model="selectedYear" :items="yearOptions" :label="$t('year')" density="compact" variant="outlined" rounded color="#a21ee5" class="text-info" hide-details @update:model-value="fetchInvoices"></v-select></v-col>
        <v-col cols="6" md="2"><v-select v-model="selectedMonth" :items="monthOptions" :label="$t('month_label')" density="compact" variant="outlined" rounded color="#a21ee5" class="text-info" hide-details></v-select></v-col>
        <v-col cols="6" md="2"><v-btn color="#a21ee5" variant="tonal" rounded block prepend-icon="mdi-eye-outline" :disabled="!selectedWarehouse || !selectedMonth" :loading="previewing" @click="previewInvoice">{{ $t('preview_invoice') }}</v-btn></v-col>
        <v-col cols="6" md="2"><v-btn color="#a21ee5" rounded block prepend-icon="mdi-file-document-plus-outline" :disabled="!selectedWarehouse || !selectedMonth" :loading="generating" @click="generateInvoice">{{ $t('generate_invoice') }}</v-btn></v-col>
      </v-row>

      <!-- Preview Card -->
      <v-expand-transition>
        <v-card v-if="previewData" class="mb-4 rounded-xl" variant="outlined" color="#a21ee5">
          <v-card-title class="d-flex align-center pa-4 pb-2">
            <v-icon color="#a21ee5" class="mr-2">mdi-calculator-variant-outline</v-icon>
            <span>{{ $t('invoice_preview') }} — {{ previewData.warehouse_name }} ({{ monthName(previewData.month) }} {{ previewData.year }})</span>
            <v-spacer></v-spacer>
            <v-btn icon size="small" variant="text" @click="previewData = null"><v-icon>mdi-close</v-icon></v-btn>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <div class="d-flex flex-wrap ga-3 mb-4">
              <v-chip color="info" variant="tonal" size="large" prepend-icon="mdi-arrow-down-bold">{{ $t('stock_in') }}: {{ formatNum(previewData.total_stock_in_kg) }} KG</v-chip>
              <v-chip color="warning" variant="tonal" size="large" prepend-icon="mdi-arrow-up-bold">{{ $t('stock_out') }}: {{ formatNum(previewData.total_stock_out_kg) }} KG</v-chip>
              <v-chip color="deep-purple" variant="tonal" size="large" prepend-icon="mdi-package-variant">{{ $t('remaining_stock') }}: {{ formatNum(previewData.remaining_stock_kg) }} KG</v-chip>
              <v-chip color="success" variant="elevated" size="large" prepend-icon="mdi-cash">{{ $t('total_to_pay') }}: {{ formatNum(previewData.total_amount) }} MAD</v-chip>
            </div>
            <v-data-table :headers="previewHeaders" :items="previewData.lines" density="comfortable" class="rounded-lg" hide-default-footer :items-per-page="-1">
              <template v-slot:[`item.description`]="{ item }"><span class="font-weight-medium">{{ item.description }}</span></template>
              <template v-slot:[`item.quantity_kg`]="{ item }">{{ formatNum(item.quantity_kg) }}</template>
              <template v-slot:[`item.quantity_days`]="{ item }">{{ item.quantity_days > 0 ? item.quantity_days : '-' }}</template>
              <template v-slot:[`item.unit_price`]="{ item }">{{ item.unit_price }} DH</template>
              <template v-slot:[`item.total_amount`]="{ item }"><span class="font-weight-bold text-success">{{ formatNum(item.total_amount) }} MAD</span></template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-expand-transition>

      <!-- Invoices List -->
      <v-data-table :headers="invoiceHeaders" :items="invoices" :loading="loading ? 'info' : false" density="comfortable" class="rounded-lg" hover :no-data-text="$t('no_invoices_found')">
        <template v-slot:[`item.invoice_number`]="{ item }"><v-chip color="#a21ee5" variant="tonal" size="small" class="font-weight-bold">{{ item.invoice_number }}</v-chip></template>
        <template v-slot:[`item.period`]="{ item }">{{ monthName(item.month) }} {{ item.year }}</template>
        <template v-slot:[`item.warehouse`]="{ item }">{{ item.warehouse?.name || '-' }}</template>
        <template v-slot:[`item.manipulation_in_amount`]="{ item }">{{ formatNum(item.manipulation_in_amount) }}</template>
        <template v-slot:[`item.manipulation_out_amount`]="{ item }">{{ formatNum(item.manipulation_out_amount) }}</template>
        <template v-slot:[`item.sejour_amount`]="{ item }">{{ formatNum(item.sejour_amount) }}</template>
        <template v-slot:[`item.total_amount`]="{ item }"><span class="font-weight-bold text-success">{{ formatNum(item.total_amount) }} MAD</span></template>
        <template v-slot:[`item.status`]="{ item }"><v-chip :color="item.status === 'finalized' ? 'success' : 'warning'" size="small" variant="tonal">{{ $t(item.status) }}</v-chip></template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon size="x-small" variant="tonal" color="#a21ee5" class="mr-1" @click="loadPrintInvoice(item)">
            <v-icon size="16">mdi-printer</v-icon>
            <v-tooltip activator="parent" location="top">{{ $t('print') }}</v-tooltip>
          </v-btn>
          <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)">
            <v-icon size="16">mdi-delete</v-icon>
            <v-tooltip activator="parent" location="top">{{ $t('delete') }}</v-tooltip>
          </v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
        <v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_invoice_storage') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Print Trigger (only visible when printInvoice is loaded) -->
    <div v-if="printInvoice" class="d-flex align-center ga-2 pa-3" style="position:fixed;bottom:20px;right:20px;z-index:999;background:rgba(162,30,229,.95);border-radius:16px;box-shadow:0 8px 32px rgba(162,30,229,.4)">
      <v-icon color="white" size="20">mdi-receipt-text-outline</v-icon>
      <span class="text-white text-body-2 font-weight-bold mr-2">{{ printInvoice.invoice_number }}</span>
      <v-btn v-print="printCfg" color="white" variant="flat" size="small" rounded prepend-icon="mdi-printer" style="color:#a21ee5">{{ $t('print') }}</v-btn>
      <v-btn icon size="x-small" variant="text" color="white" @click="printInvoice = null"><v-icon size="18">mdi-close</v-icon></v-btn>
    </div>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420">
      <v-progress-linear :model-value="snackbarProgress" color="#a21ee5" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="summarySnackbar.isError ? 'error' : 'success'" class="mr-2">{{ summarySnackbar.isError ? 'mdi-alert-circle' : 'mdi-check-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.warehouse"><v-icon size="14" color="#a21ee5" class="mr-1">mdi-warehouse</v-icon><span class="text-medium-emphasis">{{ $t('warehouse') }}:</span> <strong>{{ summarySnackbar.warehouse }}</strong></div>
        <div v-if="summarySnackbar.period"><v-icon size="14" color="#a21ee5" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('invoice_period') }}:</span> <strong>{{ summarySnackbar.period }}</strong></div>
        <div v-if="summarySnackbar.total"><v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_to_pay') }}:</span> <strong class="text-success">{{ summarySnackbar.total }}</strong></div>
      </div>
      <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn></template>
    </v-snackbar>
  </v-card>

  <!-- Hidden Print Area (same pattern as QualityControl) -->
  <div style="display:none"><div id="si-print-area">
    <div v-if="printInvoice" style="padding:24px 32px;max-width:900px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a1a">
      <!-- Header with Logo -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div><img :src="logoUrl" alt="Logo" style="height:52px" /></div>
        <div style="text-align:center;flex:1"><h1 style="font-size:20px;font-weight:800;color:#7B1FA2;margin:0">{{ $t('facture_de_stockage') }}</h1><p style="font-size:12px;color:#78909C;margin:2px 0 0">{{ printInvoice.warehouse?.name }}</p></div>
        <div style="text-align:right;font-size:11px;color:#546E7A"><div><b>{{ $t('invoice_number') }}:</b> {{ printInvoice.invoice_number }}</div><div><b>{{ $t('invoice_period') }}:</b> {{ monthName(printInvoice.month) }} {{ printInvoice.year }}</div><div><b>{{ $t('date') }}:</b> {{ new Date().toLocaleDateString() }}</div></div>
      </div>
      <div style="height:3px;background:linear-gradient(90deg,#7B1FA2,#CE93D8,#7B1FA2);border-radius:2px;margin:12px 0 20px"></div>
      <!-- Info Row -->
      <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
        <tr><td class="si-pl">{{ $t('warehouse') }}</td><td class="si-pv">{{ printInvoice.warehouse?.name }}</td><td class="si-pl">{{ $t('invoice_number') }}</td><td class="si-pv">{{ printInvoice.invoice_number }}</td></tr>
        <tr><td class="si-pl">{{ $t('invoice_period') }}</td><td class="si-pv">{{ monthName(printInvoice.month) }} {{ printInvoice.year }}</td><td class="si-pl">{{ $t('status') }}</td><td class="si-pv">{{ printInvoice.status }}</td></tr>
        <tr><td class="si-pl">{{ $t('stock_in') }} (KG)</td><td class="si-pv">{{ formatNum(printInvoice.total_stock_in_kg) }}</td><td class="si-pl">{{ $t('stock_out') }} (KG)</td><td class="si-pv">{{ formatNum(printInvoice.total_stock_out_kg) }}</td></tr>
      </table>
      <!-- Lines Table -->
      <table style="width:100%;border-collapse:collapse">
        <thead><tr>
          <th class="si-ph" style="text-align:left">{{ $t('prestations') }}</th>
          <th class="si-ph">{{ $t('quantity_kg') }} (KG)</th>
          <th class="si-ph">{{ $t('quantity_days_short') }} (J)</th>
          <th class="si-ph">{{ $t('unit_price_pu') }} (MAD)</th>
          <th class="si-ph">{{ $t('total') }} (MAD)</th>
        </tr></thead>
        <tbody>
          <tr v-for="line in printInvoice.lines" :key="line.id">
            <td class="si-pc">{{ line.description }}</td>
            <td class="si-pv2">{{ formatNum(line.quantity_kg) }}</td>
            <td class="si-pv2">{{ line.quantity_days > 0 ? line.quantity_days : '-' }}</td>
            <td class="si-pv2">{{ line.unit_price }}</td>
            <td class="si-pv2" style="font-weight:700">{{ formatNum(line.total_amount) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr><td colspan="4" style="text-align:right;font-weight:800;font-size:15px;padding:12px 10px;border-top:2px solid #7B1FA2">{{ $t('total_to_pay') }} (MAD):</td><td style="text-align:center;font-weight:800;font-size:18px;color:#7B1FA2;padding:12px 10px;border-top:2px solid #7B1FA2">{{ formatNum(printInvoice.total_amount) }}</td></tr>
        </tfoot>
      </table>
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
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import print from 'vue3-print-nb';

export default {
  name: 'StorageInvoiceList',
  directives: { print },
  data() {
    return {
      selectedWarehouse: null,
      selectedYear: new Date().getFullYear(),
      selectedMonth: new Date().getMonth() + 1,
      warehouses: [], invoices: [],
      loading: false, previewing: false, generating: false, deleting: false,
      previewData: null,
      deleteDialog: false, deleteTarget: null,
      printInvoice: null,
      printCfg: { id: 'si-print-area', popTitle: 'Storage Invoice' },
      summarySnackbar: { show: false, title: '', warehouse: '', period: '', total: '', isError: false },
      snackbarProgress: 100, snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    logoUrl() { return new URL('/storage/images/logo_login.png', window.location.origin).href; },
    sejourWarehouses() { return this.warehouses.filter(w => w.has_sejour); },
    yearOptions() { const c = new Date().getFullYear(); return Array.from({ length: 10 }, (_, i) => c - i); },
    monthOptions() { return Array.from({ length: 12 }, (_, i) => ({ title: this.monthName(i + 1), value: i + 1 })); },
    previewHeaders() {
      return [
        { title: this.$t('prestations'), key: 'description' },
        { title: this.$t('quantity_kg') + ' (KG)', key: 'quantity_kg', align: 'center' },
        { title: this.$t('quantity_days_short') + ' (J)', key: 'quantity_days', align: 'center' },
        { title: this.$t('unit_price_pu') + ' (MAD)', key: 'unit_price', align: 'center' },
        { title: this.$t('total') + ' (MAD)', key: 'total_amount', align: 'end' },
      ];
    },
    invoiceHeaders() {
      return [
        { title: 'N°', key: 'invoice_number', align: 'center' },
        { title: this.$t('invoice_period'), key: 'period', align: 'center' },
        { title: this.$t('warehouse'), key: 'warehouse', align: 'center' },
        { title: this.$t('manipulation_in'), key: 'manipulation_in_amount', align: 'center' },
        { title: this.$t('manipulation_out'), key: 'manipulation_out_amount', align: 'center' },
        { title: this.$t('sejour_stockage'), key: 'sejour_amount', align: 'center' },
        { title: this.$t('total') + ' (MAD)', key: 'total_amount', align: 'center' },
        { title: this.$t('status'), key: 'status', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
  },
  mounted() { this.fetchWarehouses(); mitt.on('model-changed', this._onModelChanged); },
  beforeUnmount() { mitt.off('model-changed', this._onModelChanged); clearInterval(this.snackbarTimer); },
  methods: {
    formatNum(v) { return this.main.formatDisplayNumber(v); },
    monthName(m) {
      const k = ['','month_january','month_february','month_march','month_april','month_may','month_june','month_july','month_august','month_september','month_october','month_november','month_december'];
      return this.$t(k[m] || '');
    },
    _onModelChanged(data) { if (data.model === 'StorageInvoice') this.fetchInvoices(); },
    async fetchWarehouses() { try { const r = await axios.get('/api/warehouses'); this.warehouses = r.data.warehouses || []; } catch (e) { console.error(e); } },
    async fetchInvoices() {
      if (!this.selectedWarehouse) { this.invoices = []; return; }
      this.loading = true;
      try { const r = await axios.get('/api/storage-invoices', { params: { warehouse_id: this.selectedWarehouse, year: this.selectedYear } }); this.invoices = r.data.storage_invoices || []; } catch (e) { console.error(e); }
      this.loading = false;
    },
    async previewInvoice() {
      this.previewing = true; this.previewData = null;
      try { const r = await axios.get('/api/storage-invoices/preview', { params: { warehouse_id: this.selectedWarehouse, year: this.selectedYear, month: this.selectedMonth } }); this.previewData = r.data.preview; }
      catch (e) { this.main.add_snackbar({ show: true, message: this.$t(e.response?.data?.error || 'error_occurred'), color: 'error' }); }
      this.previewing = false;
    },
    async generateInvoice() {
      this.generating = true;
      try {
        const r = await axios.post('/api/storage-invoices/generate', { warehouse_id: this.selectedWarehouse, year: this.selectedYear, month: this.selectedMonth });
        const inv = r.data.storage_invoice;
        this.previewData = null; this.fetchInvoices();
        this.showSnackbar(false, this.$t('invoice_generated_success'), inv.warehouse?.name, `${this.monthName(inv.month)} ${inv.year}`, `${this.formatNum(inv.total_amount)} MAD`);
      } catch (e) {
        const ek = e.response?.data?.error;
        this.showSnackbar(true, ek === 'invoice_already_exists' ? this.$t('invoice_already_exists') : ek === 'warehouse_no_sejour' ? this.$t('warehouse_no_sejour_error') : this.$t('error_occurred'), '', '', '');
      }
      this.generating = false;
    },
    confirmDelete(item) { this.deleteTarget = item; this.deleteDialog = true; },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      try { await axios.delete(`/api/storage-invoices/${this.deleteTarget.id}`); this.deleteDialog = false; this.fetchInvoices(); this.showSnackbar(false, this.$t('invoice_deleted_success'), this.deleteTarget.warehouse?.name, `${this.monthName(this.deleteTarget.month)} ${this.deleteTarget.year}`, ''); }
      catch (e) { this.main.add_snackbar({ show: true, message: this.$t('error_occurred'), color: 'error' }); }
      this.deleting = false; this.deleteTarget = null;
    },
    async loadPrintInvoice(item) {
      try { const r = await axios.get(`/api/storage-invoices/${item.id}`); this.printInvoice = r.data.storage_invoice; axios.post(`/api/storage-invoices/${item.id}/increment-print-counter`).catch(() => {}); }
      catch (e) { console.error(e); }
    },
    showSnackbar(isError, title, warehouse, period, total) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, isError, title, warehouse, period, total };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => { this.snackbarProgress -= 1.25; if (this.snackbarProgress <= 0) clearInterval(this.snackbarTimer); }, 100);
    },
  },
};
</script>

<style scoped>
.si-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.si-hero { background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.si-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.si-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.si-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
</style>

<style>
/* Print area table styles (unscoped so they work inside the hidden print div) */
.si-pl { font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#546E7A;background:#F5F5F5;padding:8px 12px;border:1px solid #E0E0E0;width:18% }
.si-pv { font-size:13px;color:#212121;padding:8px 12px;border:1px solid #E0E0E0;width:32% }
.si-ph { background:#7B1FA2;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;padding:10px 8px;border:1px solid #6A1B9A;text-align:center }
.si-pc { padding:8px 12px;border:1px solid #E0E0E0;font-weight:600;font-size:13px }
.si-pv2 { padding:8px 8px;border:1px solid #E0E0E0;font-size:13px;text-align:center;color:#212121 }
@media print { #si-print-area { display: block !important; } }
</style>
