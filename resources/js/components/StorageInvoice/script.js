import axios from 'axios';
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useCompanySettings } from '@/stores/CompanySettings';
import * as XLSX from 'xlsx';

export default {
  name: 'StorageInvoiceList',
  data() {
    return {
      selectedWarehouses: [], selectedYear: new Date().getFullYear(), selectedMonth: new Date().getMonth() + 1,
      warehouses: [], invoices: [],
      loading: false, previewing: false, generating: false, deleting: false,
      previewData: null, deleteDialog: false, deleteTarget: null,
      printInvoice: null,
      summarySnackbar: { show: false, title: '', warehouse: '', period: '', total: '', isError: false },
      snackbarProgress: 100, snackbarTimer: null,
      // Filter/sort/pin state (like ListCharge)
      colFilters: {}, colSorts: [], pinState: {}, filterMenus: {},
      columnOrder: null, dragCol: null, dragOverCol: null, colWidths: {},
      // Selection & batch print
      selectedIds: [], printInvoices: [], loadingBatchPrint: false,
      // Analytics
      chartMode: 'monthly',
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    logoUrl() {
      const cs = useCompanySettings();
      if (cs.settings.logo_invoice_url) return cs.settings.logo_invoice_url;
      return new URL('/storage/images/logo_login.png', window.location.origin).href;
    },
    sejourWarehouses() { return this.warehouses.filter(w => w.has_sejour); },
    allSelected() { return this.colFilteredItems.length > 0 && this.colFilteredItems.every(i => this.selectedIds.includes(i.id)); },
    someSelected() { return this.colFilteredItems.some(i => this.selectedIds.includes(i.id)); },
    yearOptions() { const c = new Date().getFullYear(); return Array.from({ length: 10 }, (_, i) => c - i); },
    monthOptions() { return Array.from({ length: 12 }, (_, i) => ({ title: this.monthName(i + 1), value: i + 1 })); },
    allHeaders() {
      return [
        { title: 'N°', key: 'invoice_number', align: 'center' },
        { title: this.$t('invoice_period'), key: '_period', align: 'center' },
        { title: this.$t('warehouse'), key: '_warehouse', align: 'center' },
        { title: this.$t('stock_in') + ' (KG)', key: 'total_stock_in_kg', align: 'center' },
        { title: this.$t('stock_out') + ' (KG)', key: 'total_stock_out_kg', align: 'center' },
        { title: this.$t('manipulation_in'), key: 'manipulation_in_amount', align: 'center' },
        { title: this.$t('manipulation_out'), key: 'manipulation_out_amount', align: 'center' },
        { title: this.$t('sejour_stockage'), key: 'sejour_amount', align: 'center' },
        { title: this.$t('total') + ' (MAD)', key: 'total_amount', align: 'center' },
        { title: this.$t('status'), key: 'status', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, fixed: true },
      ];
    },
    headers() {
      if (!this.columnOrder) return this.allHeaders;
      const map = Object.fromEntries(this.allHeaders.map(h => [h.key, h]));
      const fixed = this.allHeaders.filter(h => h.fixed);
      const ordered = this.columnOrder.filter(k => map[k] && !map[k].fixed).map(k => map[k]);
      return [...ordered.filter(h => this.pinState[h.key] === 'left'), ...ordered.filter(h => !this.pinState[h.key]), ...ordered.filter(h => this.pinState[h.key] === 'right'), ...fixed];
    },
    colFilteredItems() {
      let items = this.invoices;
      Object.entries(this.colFilters).forEach(([k, vals]) => {
        if (!vals || !vals.length) return;
        items = items.filter(it => vals.includes(this.cellText(k, it)));
      });
      return this.sortItems(items);
    },
    // Statistics
    totalManipIn() { return this.colFilteredItems.reduce((s, i) => s + Number(i.manipulation_in_amount || 0), 0); },
    totalManipOut() { return this.colFilteredItems.reduce((s, i) => s + Number(i.manipulation_out_amount || 0), 0); },
    totalSejour() { return this.colFilteredItems.reduce((s, i) => s + Number(i.sejour_amount || 0), 0); },
    totalAmount() { return this.colFilteredItems.reduce((s, i) => s + Number(i.total_amount || 0), 0); },
    totalStockIn() { return this.colFilteredItems.reduce((s, i) => s + Number(i.total_stock_in_kg || 0), 0); },
    totalStockOut() { return this.colFilteredItems.reduce((s, i) => s + Number(i.total_stock_out_kg || 0), 0); },
    warehouseStats() {
      const map = {};
      this.colFilteredItems.forEach(inv => {
        const wn = inv._warehouse;
        if (!map[wn]) map[wn] = { name: wn, count: 0, manipIn: 0, manipOut: 0, sejour: 0, total: 0 };
        map[wn].count++;
        map[wn].manipIn += Number(inv.manipulation_in_amount || 0);
        map[wn].manipOut += Number(inv.manipulation_out_amount || 0);
        map[wn].sejour += Number(inv.sejour_amount || 0);
        map[wn].total += Number(inv.total_amount || 0);
      });
      return Object.values(map);
    },
    // Chart
    chartSeries() {
      const buckets = {};
      this.colFilteredItems.forEach(inv => {
        let key;
        if (this.chartMode === 'monthly') key = inv.year + '-' + String(inv.month).padStart(2, '0');
        else key = String(inv.year);
        if (!buckets[key]) buckets[key] = { mi: 0, mo: 0, sj: 0 };
        buckets[key].mi += Number(inv.manipulation_in_amount || 0);
        buckets[key].mo += Number(inv.manipulation_out_amount || 0);
        buckets[key].sj += Number(inv.sejour_amount || 0);
      });
      const sorted = Object.keys(buckets).sort();
      return [
        { name: this.$t('manipulation_in'), data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].mi * 100) / 100 })) },
        { name: this.$t('manipulation_out'), data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].mo * 100) / 100 })) },
        { name: this.$t('sejour_stockage'), data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].sj * 100) / 100 })) },
      ];
    },
    chartOptions() {
      return {
        chart: { type: 'bar', height: 280, stacked: true, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        colors: ['#7E57C2', '#CE93D8', '#AB47BC'],
        xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 } } },
        yaxis: { title: { text: 'MAD', style: { fontSize: '11px', fontWeight: 700, color: '#7E57C2' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
        tooltip: { shared: true, intersect: false, y: { formatter: v => this.main.formatDisplayNumber(v) + ' MAD' } },
        grid: { borderColor: 'rgba(0,0,0,.06)', strokeDashArray: 4 },
        legend: { position: 'top', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: false },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
    previewHeaders() {
      return [
        { title: this.$t('prestations'), key: 'description' },
        { title: this.$t('quantity_kg') + ' (KG)', key: 'quantity_kg', align: 'center' },
        { title: this.$t('quantity_days_short') + ' (J)', key: 'quantity_days', align: 'center' },
        { title: this.$t('unit_price_pu') + ' (MAD)', key: 'unit_price', align: 'center' },
        { title: this.$t('total') + ' (MAD)', key: 'total_amount', align: 'end' },
      ];
    },
  },
  mounted() {
    this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
    this.fetchWarehouses();
    mitt.on('model-changed', this._onModelChanged);
  },
  beforeUnmount() { mitt.off('model-changed', this._onModelChanged); clearInterval(this.snackbarTimer); },
  methods: {
    formatNum(v) { return this.main.formatDisplayNumber(v); },
    monthName(m) {
      const k = ['', 'month_january', 'month_february', 'month_march', 'month_april', 'month_may', 'month_june', 'month_july', 'month_august', 'month_september', 'month_october', 'month_november', 'month_december'];
      return this.$t(k[m] || '');
    },
    _onModelChanged(data) { if (data.model === 'StorageInvoice') this.fetchInvoices(); },
    mapInvoice(inv) {
      return { ...inv, _period: this.monthName(inv.month) + ' ' + inv.year, _warehouse: inv.warehouse?.name || '-' };
    },
    // Data
    async fetchWarehouses() { try { const r = await axios.get('/api/warehouses'); this.warehouses = r.data.warehouses || []; } catch (e) { console.error(e); } },
    async fetchInvoices() {
      if (!this.selectedWarehouses.length) { this.invoices = []; return; }
      this.loading = true;
      try { const r = await axios.get('/api/storage-invoices', { params: { warehouse_ids: this.selectedWarehouses.join(','), year: this.selectedYear } }); this.invoices = (r.data.storage_invoices || []).map(i => this.mapInvoice(i)); } catch (e) { console.error(e); }
      this.loading = false;
    },
    async previewInvoice() {
      this.previewing = true; this.previewData = null;
      try { const r = await axios.get('/api/storage-invoices/preview', { params: { warehouse_id: this.selectedWarehouses[0], year: this.selectedYear, month: this.selectedMonth } }); this.previewData = r.data.preview; }
      catch (e) { this.main.add_snackbar({ show: true, message: this.$t(e.response?.data?.error || 'error_occurred'), color: 'error' }); }
      this.previewing = false;
    },
    async generateInvoice() {
      this.generating = true;
      try {
        const r = await axios.post('/api/storage-invoices/generate', { warehouse_id: this.selectedWarehouses[0], year: this.selectedYear, month: this.selectedMonth });
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
      try { await axios.delete(`/api/storage-invoices/${this.deleteTarget.id}`); this.deleteDialog = false; this.fetchInvoices(); this.showSnackbar(false, this.$t('invoice_deleted_success'), this.deleteTarget._warehouse, `${this.monthName(this.deleteTarget.month)} ${this.deleteTarget.year}`, ''); }
      catch (e) { this.main.add_snackbar({ show: true, message: this.$t('error_occurred'), color: 'error' }); }
      this.deleting = false; this.deleteTarget = null;
    },
    async loadPrintInvoice(item) {
      try {
        const r = await axios.get(`/api/storage-invoices/${item.id}`);
        this.printInvoice = r.data.storage_invoice;
        this.printInvoices = [r.data.storage_invoice];
        axios.post(`/api/storage-invoices/${item.id}/increment-print-counter`).catch(() => {});
      } catch (e) { console.error(e); }
    },
    async loadPrintMultiple() {
      this.loadingBatchPrint = true;
      try {
        const results = await Promise.all(this.selectedIds.map(id => axios.get(`/api/storage-invoices/${id}`)));
        this.printInvoices = results.map(r => r.data.storage_invoice);
        this.printInvoice = this.printInvoices[0];
        this.selectedIds.forEach(id => axios.post(`/api/storage-invoices/${id}/increment-print-counter`).catch(() => {}));
      } catch (e) { console.error(e); }
      this.loadingBatchPrint = false;
    },
    toggleSelect(id) {
      const i = this.selectedIds.indexOf(id);
      if (i === -1) this.selectedIds.push(id); else this.selectedIds.splice(i, 1);
    },
    toggleSelectAll(val) {
      if (val) { const ids = this.colFilteredItems.map(i => i.id); this.selectedIds = [...new Set([...this.selectedIds, ...ids])]; }
      else { const ids = new Set(this.colFilteredItems.map(i => i.id)); this.selectedIds = this.selectedIds.filter(id => !ids.has(id)); }
    },
    // Filter/Sort/Pin (same as ListCharge)
    cellText(key, item) {
      if (key === '_period') return item._period;
      if (key === '_warehouse') return item._warehouse;
      if (key === 'status') return this.$t(item.status);
      const v = item[key]; return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      const other = Object.entries(this.colFilters).filter(([k]) => k !== key);
      let items = this.invoices;
      other.forEach(([k, vals]) => { if (vals && vals.length) items = items.filter(it => vals.includes(this.cellText(k, it))); });
      const s = new Set(); items.forEach(i => s.add(this.cellText(key, i)));
      return [...s].sort();
    },
    toggleSort(key) { const i = this.colSorts.findIndex(s => s.key === key); if (i === -1) this.colSorts.push({ key, dir: 'asc' }); else if (this.colSorts[i].dir === 'asc') this.colSorts[i].dir = 'desc'; else this.colSorts.splice(i, 1); },
    sortDir(key) { const s = this.colSorts.find(x => x.key === key); return s ? s.dir : null; },
    sortIdx(key) { return this.colSorts.findIndex(x => x.key === key) + 1; },
    sortItems(items) {
      if (!this.colSorts.length) return items;
      return [...items].sort((a, b) => { for (const { key, dir } of this.colSorts) { let va = a[key], vb = b[key]; if (va == null) va = ''; if (vb == null) vb = ''; const n = typeof va === 'number' && typeof vb === 'number'; if (n) { if (va !== vb) return dir === 'asc' ? va - vb : vb - va; } else { const c = String(va).localeCompare(String(vb)); if (c) return dir === 'asc' ? c : -c; } } return 0; });
    },
    togglePin(k) { const cur = this.pinState[k]; if (!cur) this.pinState[k] = 'left'; else if (cur === 'left') this.pinState[k] = 'right'; else delete this.pinState[k]; this.pinState = { ...this.pinState }; },
    isPinned(k) { return !!this.pinState[k]; },
    pinSide(k) { return this.pinState[k] || null; },
    getPinLeft(key) { let o = 0; for (const h of this.headers) { if (h.key === key) break; if (this.pinState[h.key] === 'left') o += (this.colWidths[h.key] || 120); } return o; },
    getPinRight(key) { let o = 0; const rev = [...this.headers].reverse(); for (const h of rev) { if (h.key === key) break; if (this.pinState[h.key] === 'right') o += (this.colWidths[h.key] || 120); } return o; },
    colPinStyle(key, z) { const side = this.pinSide(key); if (!side) return {}; const bg = this.main?.is_dark ? '#1c1f28' : '#F5F5F5'; if (side === 'left') return { position: 'sticky', left: this.getPinLeft(key) + 'px', zIndex: z, background: bg }; return { position: 'sticky', right: this.getPinRight(key) + 'px', zIndex: z, background: bg }; },
    measureColWidths() { this.$nextTick(() => { const ths = this.$refs.tableWrap?.querySelectorAll('thead th'); if (!ths) return; const w = {}; ths.forEach((th, i) => { const key = this.headers[i]?.key; if (key) w[key] = th.offsetWidth; }); this.colWidths = w; }); },
    // Drag columns
    onDragStart(key) { this.dragCol = key; },
    onDragOver(key, e) { e.preventDefault(); this.dragOverCol = key; },
    onDragEnd() {
      if (this.dragCol && this.dragOverCol && this.dragCol !== this.dragOverCol) {
        const order = [...this.columnOrder]; const fi = order.indexOf(this.dragCol); const ti = order.indexOf(this.dragOverCol);
        if (fi > -1 && ti > -1) { order.splice(fi, 1); order.splice(ti, 0, this.dragCol); this.columnOrder = order; }
      }
      this.dragCol = null; this.dragOverCol = null;
    },
    // Export
    exportExcel() {
      const data = this.colFilteredItems.map(c => { const row = {}; this.headers.filter(h => h.key !== 'actions').forEach(h => { row[h.title] = this.cellText(h.key, c); }); return row; });
      const ws = XLSX.utils.json_to_sheet(data); const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'StorageInvoices'); XLSX.writeFile(wb, `storage_invoices_${new Date().toISOString().slice(0, 10)}.xlsx`);
    },
    showSnackbar(isError, title, warehouse, period, total) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, isError, title, warehouse, period, total };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => { this.snackbarProgress -= 1.25; if (this.snackbarProgress <= 0) clearInterval(this.snackbarTimer); }, 100);
    },
  },
  watch: {
    headers() { this.measureColWidths(); },
    pinState: { handler() { this.measureColWidths(); }, deep: true },
  },
  updated() { this.measureColWidths(); },
};
