import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useSupplierInvoice } from '@/stores/SupplierInvoice';
import mitt from '@/plugins/mitt.js';
import * as XLSX from 'xlsx';

export default {
  name: 'ListSupplierInvoice',
  data() {
    const n = new Date(), sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      invoices: [], loading: false, selected: [],
      dateRange: [sm, n],
      colFilters: {}, colSorts: [], pinState: {},
      expanded: {},
      deleteDialog: false, deleting: false, deleteId: null, deleteBulk: false,
      filterMenus: {},
      columnOrder: null,
      dragCol: null, dragOverCol: null,
      colWidths: {},
      chartMode: 'daily',
    };
  },
  computed: {
    main() { return useMain() },
    store() { return useSupplierInvoice() },
    today() { return new Date().toISOString().slice(0, 10) },
    dateRangeVC: {
      get() { return { start: this.dateRange[0], end: this.dateRange[1] || this.dateRange[0] } },
      set(v) { if (v && v.start && v.end) this.dateRange = [v.start, v.end] }
    },
    allHeaders() {
      return [
        { title: '', key: 'expand', sortable: false, width: 40, fixed: true },
        { title: this.$t('date'), key: 'date', align: 'center' },
        { title: this.$t('supplier_invoice_number_short'), key: '_tracking_numbers', align: 'center' },
        { title: this.$t('suppliers'), key: '_suppliers', align: 'center' },
        { title: this.$t('total_weight'), key: 'total_weight', align: 'center' },
        { title: this.$t('price_dh', { currency: this.main.currency }), key: 'total_price', align: 'center' },
        { title: this.$t('print_counter'), key: 'print_counter', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, fixed: true },
      ];
    },
    headers() {
      if (!this.columnOrder) return this.allHeaders;
      const map = Object.fromEntries(this.allHeaders.map(h => [h.key, h]));
      const fixed = this.allHeaders.filter(h => h.fixed);
      const ordered = this.columnOrder.filter(k => map[k] && !map[k].fixed).map(k => map[k]);
      return [fixed[0], ...ordered.filter(h => this.pinState[h.key] === 'left'), ...ordered.filter(h => !this.pinState[h.key]), ...ordered.filter(h => this.pinState[h.key] === 'right'), fixed[fixed.length - 1]];
    },
    mappedInvoices() {
      const lang = this.main.lang?.code || 'en';
      return (this.invoices || []).map(inv => {
        const suppliers = new Set();
        (inv.charges || []).forEach(c => (c.suppliers || []).forEach(s => suppliers.add(s.name)));
        (inv.productions || []).forEach(p => (p.suppliers || []).forEach(s => suppliers.add(s.name)));
        return {
          ...inv,
          _tracking_numbers: (inv.tracking_numbers || []).join(', ') || '-',
          _suppliers: [...suppliers].join(', ') || '-',
        };
      });
    },
    colFilteredItems() {
      let items = this.mappedInvoices;
      Object.entries(this.colFilters).forEach(([k, vals]) => {
        if (!vals || !vals.length) return;
        items = items.filter(it => vals.includes(this.cellText(k, it)));
      });
      return this.sortItems(items);
    },
    totalWeight() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_weight || 0), 0) },
    totalPrice() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_price || 0), 0) },
    totalChargesCount() { return this.colFilteredItems.reduce((s, inv) => s + (inv.charges || []).length, 0) },
    totalProductionsCount() { return this.colFilteredItems.reduce((s, inv) => s + (inv.productions || []).length, 0) },
    supplierStats() {
      const map = {};
      this.colFilteredItems.forEach(inv => {
        const suppliers = new Set();
        (inv.charges || []).forEach(c => (c.suppliers || []).forEach(s => suppliers.add(s.name)));
        (inv.productions || []).forEach(p => (p.suppliers || []).forEach(s => suppliers.add(s.name)));
        const names = [...suppliers];
        if (!names.length) names.push('-');
        names.forEach(name => {
          if (!map[name]) map[name] = { name, weight: 0, price: 0, count: 0 };
          map[name].weight += Number(inv.total_weight || 0);
          map[name].price += Number(inv.total_price || 0);
          map[name].count++;
        });
      });
      return Object.values(map).sort((a, b) => b.weight - a.weight);
    },
    chartSeries() {
      const items = this.colFilteredItems;
      const buckets = {};
      items.forEach(inv => {
        const d = new Date(inv.date);
        const dateStr = typeof inv.date === 'string' ? inv.date.slice(0, 10) : d.toISOString().slice(0, 10);
        let key;
        if (this.chartMode === 'daily') key = dateStr;
        else if (this.chartMode === 'monthly') key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        else key = String(d.getFullYear());
        if (!buckets[key]) buckets[key] = { w: 0, p: 0, n: 0 };
        buckets[key].w += Number(inv.total_weight || 0);
        buckets[key].p += Number(inv.total_price || 0);
        buckets[key].n++;
      });
      const sorted = Object.keys(buckets).sort();
      return [
        { name: this.$t('total_weight') + ' (Kg)', type: 'area', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].w * 100) / 100 })) },
        { name: this.$t('total_price') + ' (' + this.main.currency + ')', type: 'line', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].p * 100) / 100 })) },
      ];
    },
    chartOptions() {
      return {
        chart: { type: 'line', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
        stroke: { width: [2, 3], curve: 'smooth', dashArray: [0, 0] },
        fill: { type: ['gradient', 'solid'], gradient: { shadeIntensity: 1, opacityFrom: .4, opacityTo: .05, stops: [0, 90, 100] } },
        colors: ['#E65100', '#2E7D32'],
        xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 }, rotate: -45, rotateAlways: this.chartMode === 'daily' }, tooltip: { enabled: false } },
        yaxis: [
          { title: { text: this.$t('total_weight') + ' (Kg)', style: { fontSize: '11px', fontWeight: 700, color: '#E65100' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
          { opposite: true, title: { text: this.$t('total_price') + ' (' + this.main.currency + ')', style: { fontSize: '11px', fontWeight: 700, color: '#2E7D32' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
        ],
        tooltip: { shared: true, intersect: false, y: { formatter: v => this.main.formatDisplayNumber(v) } },
        grid: { borderColor: 'rgba(0,0,0,.06)', strokeDashArray: 4 },
        legend: { position: 'top', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: false },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
  },
  mounted() {
    this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
    this.fetchInvoices();
    mitt.on('model-changed', this.onMC);
  },
  activated() { this.fetchInvoices(); },
  beforeUnmount() { mitt.off('model-changed', this.onMC) },
  methods: {
    onMC(d) { if (d.model === 'SupplierInvoice') this.fetchInvoices() },
    onDateRangeChange(val) { if (val && val.start && val.end) { this.dateRange = [val.start, val.end]; this.fetchInvoices() } },
    async fetchInvoices() {
      this.loading = true;
      try {
        const ds = this.dateRange[0], de = this.dateRange[1] || this.dateRange[0];
        const p = { date_start: this.fmtDate(ds), date_end: this.fmtDate(de) };
        const r = await axios.get('/api/supplier_invoice', { params: p });
        this.invoices = r.data.supplier_invoices || [];
      } catch (e) { console.error(e) }
      this.loading = false;
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; return d.toISOString().slice(0, 10) },
    cellText(key, item) {
      if (key === 'date') return this.main.formateDate(item.date);
      const v = item[key]; return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      const s = new Set();
      (this.mappedInvoices || []).forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') s.add(v);
      });
      return [...s].sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }));
    },
    filterCounts(key) {
      const counts = {};
      (this.mappedInvoices || []).forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') counts[v] = (counts[v] || 0) + 1;
      });
      return counts;
    },
    toggleSort(key) { const i = this.colSorts.findIndex(s => s.key === key); if (i === -1) this.colSorts.push({ key, dir: 'asc' }); else if (this.colSorts[i].dir === 'asc') this.colSorts[i].dir = 'desc'; else this.colSorts.splice(i, 1) },
    sortDir(key) { const s = this.colSorts.find(x => x.key === key); return s ? s.dir : null },
    sortIdx(key) { return this.colSorts.findIndex(x => x.key === key) + 1 },
    sortItems(items) {
      if (!this.colSorts.length) return items;
      return [...items].sort((a, b) => { for (const { key, dir } of this.colSorts) { let va = a[key], vb = b[key]; if (va == null) va = ''; if (vb == null) vb = ''; const n = typeof va === 'number' && typeof vb === 'number'; if (n) { if (va !== vb) return dir === 'asc' ? va - vb : vb - va } else { const c = String(va).localeCompare(String(vb)); if (c) return dir === 'asc' ? c : -c } } return 0 });
    },
    togglePin(k) { const cur = this.pinState[k]; if (!cur) this.pinState[k] = 'left'; else if (cur === 'left') this.pinState[k] = 'right'; else delete this.pinState[k]; this.pinState = { ...this.pinState } },
    isPinned(k) { return !!this.pinState[k] },
    pinSide(k) { return this.pinState[k] || null },
    getPinLeft(key) { let o = this.colWidths['__checkbox'] || 50; for (const h of this.headers) { if (h.key === key) break; if (this.pinState[h.key] === 'left') o += (this.colWidths[h.key] || 150) } return o },
    getPinRight(key) { let o = 0; const rev = [...this.headers].reverse(); for (const h of rev) { if (h.key === key) break; if (this.pinState[h.key] === 'right') o += (this.colWidths[h.key] || 150) } return o },
    toggleExpand(id) { this.expanded[id] = !this.expanded[id]; this.expanded = { ...this.expanded } },
    onDragStart(key) { this.dragCol = key },
    onDragOver(key, e) { e.preventDefault(); this.dragOverCol = key },
    onDragEnd() {
      if (this.dragCol && this.dragOverCol && this.dragCol !== this.dragOverCol) {
        const order = [...this.columnOrder];
        const fromIdx = order.indexOf(this.dragCol);
        const toIdx = order.indexOf(this.dragOverCol);
        if (fromIdx > -1 && toIdx > -1) { order.splice(fromIdx, 1); order.splice(toIdx, 0, this.dragCol); this.columnOrder = order }
      }
      this.dragCol = null; this.dragOverCol = null;
    },
    viewSelected() {
      if (!this.selected.length) return;
      this.$router.push({ name: 'view_supplier_invoice', query: { ids: this.selected } });
    },
    confirmDelete(id) { this.deleteId = id; this.deleteBulk = false; this.deleteDialog = true },
    confirmBulkDelete() { this.deleteBulk = true; this.deleteDialog = true },
    async execDelete() {
      this.deleting = true;
      try {
        const ids = this.deleteBulk ? this.selected : [this.deleteId];
        await this.store.deleteInvoices(ids);
        this.selected = []; this.deleteDialog = false; this.fetchInvoices();
      } catch (e) { console.error(e) }
      this.deleting = false;
    },
    supplierName(charge) {
      return (charge.suppliers || []).map(s => s.name).join(', ') || '-';
    },
    catName(charge) {
      const lang = this.main.lang?.code || 'en';
      const c = charge.charge_category;
      return c ? (c[`name_${lang}`] || c.name || '-') : '-';
    },
    prodCatName(prod) {
      const lang = this.main.lang?.code || 'en';
      const c = prod.production_category;
      return c ? (c[`name_${lang}`] || c.name || '-') : '-';
    },
    exportExcel() {
      const data = this.colFilteredItems.map(c => { const row = {}; this.headers.filter(h => h.key !== 'actions' && h.key !== 'expand').forEach(h => { row[h.title] = this.cellText(h.key, c) }); return row });
      const ws = XLSX.utils.json_to_sheet(data); const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'SupplierInvoices'); XLSX.writeFile(wb, `supplier_invoices_${this.today}.xlsx`);
    },
  },
};
