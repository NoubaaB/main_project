import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useWarehouseTransferCategory } from '@/stores/WarehouseTransferCategory';
import { useWarehouse } from '@/stores/Warehouse';
import { useTruck } from '@/stores/Truck';
import mitt from '@/plugins/mitt.js';
import * as XLSX from 'xlsx';
export default {
  name: 'ListWarehouseTransfer',
  emits: ['transfer-deleted'],
  data() {
    const n = new Date(), sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      transfers: [], loading: false, selected: [],
      dateRange: [sm, n], dateMenu: false,
      colFilters: {}, colSorts: [], pinState: {},
      expanded: {},
      deleteDialog: false, deleting: false, deleteId: null, deleteBulk: false,
      filterMenus: {},
      columnOrder: null, dragCol: null, dragOverCol: null,
      colWidths: {},
      chartMode: 'daily',
    };
  },
  computed: {
    main() { return useMain() },
    cats() { return useWarehouseTransferCategory().list },
    warehouses() { return useWarehouse().list },
    trucks() { return useTruck().list },
    today() { return new Date().toISOString().slice(0, 10) },
    dateRangeVC: { get() { return { start: this.dateRange[0], end: this.dateRange[1] || this.dateRange[0] } }, set(v) { if (v && v.start && v.end) this.dateRange = [v.start, v.end] } },
    allHeaders() {
      return [
        { title: '', key: 'expand', sortable: false, width: 40, fixed: true },
        { title: this.$t('date'), key: 'date', align: 'center' },
        { title: this.$t('transfer_tracking_number'), key: '_tracking', align: 'center' },
        { title: this.$t('trucks'), key: '_trucks', align: 'center' },
        { title: this.$t('total_weight'), key: 'total_weight', align: 'center' },
        { title: this.$t('details'), key: '_details_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, fixed: true },
      ]
    },
    headers() {
      if (!this.columnOrder) return this.allHeaders;
      const map = Object.fromEntries(this.allHeaders.map(h => [h.key, h]));
      const fixed = this.allHeaders.filter(h => h.fixed);
      const ordered = this.columnOrder.filter(k => map[k] && !map[k].fixed).map(k => map[k]);
      return [fixed[0], ...ordered.filter(h => this.pinState[h.key] === 'left'), ...ordered.filter(h => !this.pinState[h.key]), ...ordered.filter(h => this.pinState[h.key] === 'right'), fixed[fixed.length - 1]];
    },
    colFilteredItems() {
      let items = this.transfers;
      Object.entries(this.colFilters).forEach(([k, vals]) => { if (!vals || !vals.length) return; items = items.filter(it => vals.includes(this.cellText(k, it))) });
      return this.sortItems(items);
    },
    totalWeight() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_weight || 0), 0) },
    categoryStats() {
      const lang = this.main.lang?.code || 'en'; const map = {};
      this.colFilteredItems.forEach(c => {
        (c.detail_warehouse_transfers || []).forEach(d => {
          const catId = d.warehouse_transfer_category_id;
          if (!map[catId]) { const cat = this.cats.find(x => x.id === catId); map[catId] = { name: cat ? (cat[`name_${lang}`] || cat.name_fr || `#${catId}`) : `#${catId}`, weight: 0, count: 0 } }
          map[catId].weight += Number(d.weight || 0); map[catId].count++;
        });
      });
      return Object.values(map);
    },
    chartSeries() {
      const items = this.colFilteredItems; if (!items.length) return [{ name: '-', data: [] }];
      const map = {};
      items.forEach(c => {
        if (!c.date) return;
        const d = new Date(c.date); let key;
        if (this.chartMode === 'daily') key = c.date;
        else if (this.chartMode === 'monthly') key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        else key = String(d.getFullYear());
        if (!map[key]) map[key] = { weight: 0 };
        map[key].weight += Number(c.total_weight || 0);
      });
      const sorted = Object.entries(map).sort(([a], [b]) => a.localeCompare(b));
      return [
        { name: this.$t('total_weight') + ' (Kg)', data: sorted.map(([k, v]) => ({ x: this.chartMode === 'daily' ? this.main.formateDate(k) : k, y: v.weight })) },
      ];
    },
    chartOptions() {
      return {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#F9A825'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
        xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 }, rotate: -45, rotateAlways: this.chartMode === 'daily' }, tooltip: { enabled: false } },
        yaxis: { title: { text: this.$t('total_weight') + ' (Kg)', style: { fontSize: '11px', fontWeight: 700, color: '#F9A825' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
        tooltip: { shared: true, intersect: false, y: { formatter: v => this.main.formatDisplayNumber(v) + ' Kg' } },
        grid: { borderColor: 'rgba(0,0,0,0.24)', strokeDashArray: 2 },
        legend: { position: 'top', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: false },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
    selectAll: {
      get() { return this.selected.length === this.colFilteredItems.length && this.colFilteredItems.length > 0 },
      set(v) { this.selected = v ? this.colFilteredItems.map(c => c.id) : [] }
    },
  },
  mounted() {
    this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
    this.fetchTransfers();
    mitt.on('model-changed', this.onMC);
  },
  beforeUnmount() { mitt.off('model-changed', this.onMC) },
  methods: {
    onMC(d) { if (d.model === 'WarehouseTransfer') this.fetchTransfers() },
    onDateRangeChange(val) { if (val && val.start && val.end) { this.dateRange = [val.start, val.end]; this.fetchTransfers() } },
    async fetchTransfers() {
      this.loading = true;
      try {
        const ds = this.dateRange[0], de = this.dateRange[1] || this.dateRange[0];
        const p = { date_start: this.fmtDate(ds), date_end: this.fmtDate(de) };
        const r = await axios.get('/api/warehouse-transfers/get_transfers', { params: p });
        this.transfers = (r.data.transfers || []).map(c => ({ ...c,
          _tracking: (c.transfer_tracking_number || []).join(', ') || '-',
          _trucks: (c.trucks || []).map(t => t.name).join(', ') || '-',
          _details_count: (c.detail_warehouse_transfers || []).length,
        }));
      } catch (e) { console.error(e) }
      this.loading = false;
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; return d.toISOString().slice(0, 10) },
    cellText(key, item) {
      if (key === 'date') return this.main.formateDate(item.date);
      const v = item[key]; return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      const other = Object.entries(this.colFilters).filter(([k]) => k !== key);
      let items = this.transfers;
      other.forEach(([k, vals]) => { if (vals && vals.length) items = items.filter(it => vals.includes(this.cellText(k, it))) });
      const s = new Set(); items.forEach(i => s.add(this.cellText(key, i))); return [...s].sort();
    },
    toggleSort(key) { const i = this.colSorts.findIndex(s => s.key === key); if (i === -1) this.colSorts.push({ key, dir: 'asc' }); else if (this.colSorts[i].dir === 'asc') this.colSorts[i].dir = 'desc'; else this.colSorts.splice(i, 1) },
    sortDir(key) { const s = this.colSorts.find(x => x.key === key); return s ? s.dir : null },
    sortIdx(key) { return this.colSorts.findIndex(x => x.key === key) + 1 },
    sortItems(items) { if (!this.colSorts.length) return items; return [...items].sort((a, b) => { for (const { key, dir } of this.colSorts) { let va = a[key], vb = b[key]; if (va == null) va = ''; if (vb == null) vb = ''; const n = typeof va === 'number' && typeof vb === 'number'; if (n) { if (va !== vb) return dir === 'asc' ? va - vb : vb - va } else { const c = String(va).localeCompare(String(vb)); if (c) return dir === 'asc' ? c : -c } } return 0 }) },
    togglePin(k) { const cur = this.pinState[k]; if (!cur) this.pinState[k] = 'left'; else if (cur === 'left') this.pinState[k] = 'right'; else delete this.pinState[k]; this.pinState = { ...this.pinState } },
    isPinned(k) { return !!this.pinState[k] },
    pinSide(k) { return this.pinState[k] || null },
    getPinLeft(key) { let o = this.colWidths['__checkbox'] || 50; for (const h of this.headers) { if (h.key === key) break; if (this.pinState[h.key] === 'left') o += (this.colWidths[h.key] || 150) } return o },
    getPinRight(key) { let o = 0; const rev = [...this.headers].reverse(); for (const h of rev) { if (h.key === key) break; if (this.pinState[h.key] === 'right') o += (this.colWidths[h.key] || 150) } return o },
    toggleExpand(id) { this.expanded[id] = !this.expanded[id]; this.expanded = { ...this.expanded } },
    onDragStart(key) { this.dragCol = key },
    onDragOver(key, e) { e.preventDefault(); this.dragOverCol = key },
    onDragEnd() { if (this.dragCol && this.dragOverCol && this.dragCol !== this.dragOverCol) { const order = [...this.columnOrder]; const fi = order.indexOf(this.dragCol); const ti = order.indexOf(this.dragOverCol); if (fi > -1 && ti > -1) { order.splice(fi, 1); order.splice(ti, 0, this.dragCol); this.columnOrder = order } } this.dragCol = null; this.dragOverCol = null },
    groupDetails(transfer) {
      const groups = {}; (transfer.detail_warehouse_transfers || []).forEach(d => {
        const catId = d.warehouse_transfer_category_id;
        if (!groups[catId]) groups[catId] = { category: d.warehouse_transfer_category, items: [], totalW: 0 };
        groups[catId].items.push(d); groups[catId].totalW += Number(d.weight || 0);
      }); return Object.values(groups);
    },
    articleName(d) {
      if (!d.stockable_type || d.stockable_type === 'text') return d.article_description || '-';
      const s = d.stockable; return s ? (s.name || s[`name_${this.main.lang?.code}`] || '-') : '-';
    },
    warehouseName(id) { const w = this.warehouses.find(x => x.id === id); return w ? w.name : '-'; },
    confirmDelete(id) { this.deleteId = id; this.deleteBulk = false; this.deleteDialog = true },
    confirmBulkDelete() { this.deleteBulk = true; this.deleteDialog = true },
    async execDelete() {
      this.deleting = true;
      try {
        const ids = this.deleteBulk ? this.selected : [this.deleteId];
        // Build snapshot before deleting
        const deletedTransfers = this.items.filter(i => ids.includes(i.id));
        const totalWeight = deletedTransfers.reduce((s, t) => s + Number(t.total_weight || 0), 0);
        const dates = [...new Set(deletedTransfers.map(t => t.date).filter(Boolean))].join(', ');
        const trackingNumbers = deletedTransfers.flatMap(t => t.transfer_tracking_number || []).join(', ');
        const articleCount = deletedTransfers.reduce((s, t) => s + (t.details?.length || 0), 0);
        await axios.delete('/api/warehouse-transfer', { data: { ids } });
        this.$emit('transfer-deleted', {
          date: dates,
          trackingNumbers,
          trucks: '',
          totalWeight: this.main.formatDisplayNumber(totalWeight),
          articleCount,
        });
        this.selected = []; this.deleteDialog = false; this.fetchTransfers();
      } catch (e) { console.error(e) }
      this.deleting = false;
    },
    exportExcel() {
      const data = this.colFilteredItems.map(c => {
        const row = {};
        this.headers.filter(h => h.key !== 'actions' && h.key !== 'expand').forEach(h => { row[h.title] = this.cellText(h.key, c) });
        return row;
      });
      const ws = XLSX.utils.json_to_sheet(data); const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Transfers'); XLSX.writeFile(wb, `transfers_${this.today}.xlsx`);
    },
  },
};
