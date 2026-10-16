import axios from 'axios';
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import * as XLSX from 'xlsx';
import { numberToFrenchWords } from '@/utils/numberToFrenchWords';

export default {
  name: 'OrderTransferList',
  data() {
    const n = new Date(), sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      transfers: [], loading: false,
      dateRange: [sm, n], dateMenu: false,
      colFilters: {}, colSorts: [], pinState: {}, filterMenus: {},
      columnOrder: null, dragCol: null, dragOverCol: null, colWidths: {},
      selectedIds: [], chartMode: 'monthly',
      deleteDialog: false, deleting: false, deleteId: null, deleteBulk: false,
      printTransfers: [], loadingBatchPrint: false,
      companySettings: {},
      summarySnackbar: { show: false, title: '', isError: false, number: '', amount: '', beneficiaries: '' },
      snackbarProgress: 100, snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    logoUrl() {
      if (this.companySettings.logo_invoice_url) return this.companySettings.logo_invoice_url;
      return new URL('/storage/images/logo_login.png', window.location.origin).href;
    },
    dateRangeVC: {
      get() { return { start: this.dateRange[0], end: this.dateRange[1] || this.dateRange[0] }; },
      set(v) { if (v && v.start && v.end) this.dateRange = [v.start, v.end]; }
    },
    allHeaders() {
      return [
        { title: this.$t('transfer_number'), key: 'transfer_number', align: 'center' },
        { title: this.$t('date'), key: 'date', align: 'center' },
        { title: this.$t('amount'), key: 'amount', align: 'center' },
        { title: this.$t('currency'), key: 'currency', align: 'center' },
        { title: this.$t('beneficiaries'), key: '_beneficiaries', align: 'center' },
        { title: this.$t('motif'), key: 'motif', align: 'center' },
        { title: this.$t('created_by'), key: '_created_by', align: 'center' },
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
      let items = this.transfers;
      Object.entries(this.colFilters).forEach(([k, vals]) => {
        if (!vals || !vals.length) return;
        items = items.filter(it => vals.includes(this.cellText(k, it)));
      });
      return this.sortItems(items);
    },
    allSelected() { return this.colFilteredItems.length > 0 && this.colFilteredItems.every(i => this.selectedIds.includes(i.id)); },
    someSelected() { return this.colFilteredItems.some(i => this.selectedIds.includes(i.id)); },
    totalAmount() { return this.colFilteredItems.reduce((s, t) => s + Number(t.amount || 0), 0); },
    totalMAD() { return this.colFilteredItems.filter(t => t.currency === 'MAD').reduce((s, t) => s + Number(t.amount || 0), 0); },
    totalEUR() { return this.colFilteredItems.filter(t => t.currency === 'EUR').reduce((s, t) => s + Number(t.amount || 0), 0); },
    totalUSD() { return this.colFilteredItems.filter(t => t.currency === 'USD').reduce((s, t) => s + Number(t.amount || 0), 0); },
    currencyStats() {
      const map = {};
      this.colFilteredItems.forEach(t => {
        if (!map[t.currency]) map[t.currency] = { name: t.currency, count: 0, total: 0 };
        map[t.currency].count++;
        map[t.currency].total += Number(t.amount || 0);
      });
      return Object.values(map);
    },
    chartSeries() {
      const buckets = {};
      this.colFilteredItems.forEach(t => {
        const d = new Date(t.date);
        let key;
        if (this.chartMode === 'monthly') key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        else key = String(d.getFullYear());
        if (!buckets[key]) buckets[key] = { mad: 0, eur: 0, usd: 0 };
        if (t.currency === 'MAD') buckets[key].mad += Number(t.amount || 0);
        else if (t.currency === 'EUR') buckets[key].eur += Number(t.amount || 0);
        else buckets[key].usd += Number(t.amount || 0);
      });
      const sorted = Object.keys(buckets).sort();
      return [
        { name: 'MAD', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].mad * 100) / 100 })) },
        { name: 'EUR', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].eur * 100) / 100 })) },
        { name: 'USD', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].usd * 100) / 100 })) },
      ];
    },
    chartOptions() {
      return {
        chart: { type: 'bar', height: 260, stacked: true, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        colors: ['#00897B', '#1565C0', '#F57C00'],
        xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 } } },
        yaxis: { labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
        tooltip: { shared: true, intersect: false, y: { formatter: v => this.main.formatDisplayNumber(v) } },
        grid: { borderColor: 'rgba(0,0,0,.06)', strokeDashArray: 4 },
        legend: { position: 'top', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: false },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
    printCfg() { return { id: 'ot-print-area', popTitle: 'Order Transfer' }; },
  },
  mounted() {
    this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
    this.fetchTransfers();
    this.fetchCompanySettings();
    mitt.on('model-changed', this._onMC);
  },
  beforeUnmount() { mitt.off('model-changed', this._onMC); clearInterval(this.snackbarTimer); },
  methods: {
    numberToFrenchWords,
    formatNum(v) { return this.main.formatDisplayNumber(v); },
    currencySymbol(c) { return c === 'EUR' ? '€' : c === 'USD' ? '$' : 'DH'; },
    _onMC(d) {
      if (d.model === 'OrderTransfer') {
        if (d.action === 'created' || d.action === 'updated') {
          this.fetchTransfers();
        } else if (d.action === 'deleted') {
          this.transfers = this.transfers.filter(t => t.id !== d.record_id);
        }
      }
    },
    mapTransfer(t) {
      return {
        ...t,
        _beneficiaries: (t.beneficiary_names || []).map(b => b.name).join(', ') || '-',
        _created_by: t.created_by_user ? t.created_by_user.name : (t.created_by?.name || '-'),
      };
    },
    onDateRangeChange(val) { if (val && val.start && val.end) { this.dateRange = [val.start, val.end]; this.fetchTransfers(); } },
    async fetchTransfers() {
      this.loading = true;
      try {
        const ds = this.fmtDate(this.dateRange[0]), de = this.fmtDate(this.dateRange[1] || this.dateRange[0]);
        const r = await axios.get('/api/order-transfers', { params: { date_start: ds, date_end: de } });
        this.transfers = (r.data.order_transfers || []).map(t => this.mapTransfer(t));
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    async fetchCompanySettings() {
      try { const r = await axios.get('/api/company-settings'); this.companySettings = r.data.settings || {}; } catch (e) { console.error(e); }
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; },
    formatDateFrench(dateStr) {
      const months = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
      const d = new Date(dateStr);
      return `${d.getDate()} ${months[d.getMonth()+1]} ${d.getFullYear()}`;
    },
    cellText(key, item) {
      if (key === 'date') return this.main.formateDate(item.date);
      if (key === 'amount') return this.main.formatDisplayNumber(item.amount);
      const v = item[key]; return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      const other = Object.entries(this.colFilters).filter(([k]) => k !== key);
      let items = this.transfers;
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
    colPinStyle(key, z) { const side = this.pinSide(key); if (!side) return {}; const bg = this.main?.is_dark ? '#1c1f28' : '#F5F5F5'; return { position: 'sticky', [side]: '0px', zIndex: z, background: bg }; },
    onDragStart(key) { this.dragCol = key; },
    onDragOver(key, e) { e.preventDefault(); this.dragOverCol = key; },
    onDragEnd() {
      if (this.dragCol && this.dragOverCol && this.dragCol !== this.dragOverCol) {
        const order = [...this.columnOrder]; const fi = order.indexOf(this.dragCol); const ti = order.indexOf(this.dragOverCol);
        if (fi > -1 && ti > -1) { order.splice(fi, 1); order.splice(ti, 0, this.dragCol); this.columnOrder = order; }
      }
      this.dragCol = null; this.dragOverCol = null;
    },
    toggleSelect(id) { const i = this.selectedIds.indexOf(id); if (i === -1) this.selectedIds.push(id); else this.selectedIds.splice(i, 1); },
    toggleSelectAll(val) {
      if (val) { const ids = this.colFilteredItems.map(i => i.id); this.selectedIds = [...new Set([...this.selectedIds, ...ids])]; }
      else { const ids = new Set(this.colFilteredItems.map(i => i.id)); this.selectedIds = this.selectedIds.filter(id => !ids.has(id)); }
    },
    confirmDelete(id) { this.deleteId = id; this.deleteBulk = false; this.deleteDialog = true; },
    confirmBulkDelete() { this.deleteBulk = true; this.deleteDialog = true; },
    async execDelete() {
      this.deleting = true;
      try {
        const ids = this.deleteBulk ? this.selectedIds : [this.deleteId];
        await axios.delete('/api/order-transfers', { data: { ids } });
        this.showSnackbar(false, this.$t('order_transfer_deleted'), '', '', '');
        this.selectedIds = []; this.deleteDialog = false; this.fetchTransfers();
      } catch (e) { console.error(e); }
      this.deleting = false;
    },
    editTransfer(item) { this.$emit('edit', item); },
    async loadPrintSingle(item) {
      try {
        const r = await axios.get(`/api/order-transfers/${item.id}`);
        this.printTransfers = [this.mapTransfer(r.data.order_transfer)];
      } catch (e) { console.error(e); }
    },
    async loadPrintMultiple() {
      this.loadingBatchPrint = true;
      try {
        const results = await Promise.all(this.selectedIds.map(id => axios.get(`/api/order-transfers/${id}`)));
        this.printTransfers = results.map(r => this.mapTransfer(r.data.order_transfer));
      } catch (e) { console.error(e); }
      this.loadingBatchPrint = false;
    },
    exportExcel() {
      const data = this.colFilteredItems.map(c => { const row = {}; this.headers.filter(h => h.key !== 'actions').forEach(h => { row[h.title] = this.cellText(h.key, c); }); return row; });
      const ws = XLSX.utils.json_to_sheet(data); const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'OrderTransfers'); XLSX.writeFile(wb, `order_transfers_${new Date().toISOString().slice(0,10)}.xlsx`);
    },
    showSnackbar(isError, title, number, amount, beneficiaries) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, isError, title, number, amount, beneficiaries };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => { this.snackbarProgress -= 1.25; if (this.snackbarProgress <= 0) clearInterval(this.snackbarTimer); }, 100);
    },
  },
};
