import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useWarehouseTransferCategory } from '@/stores/WarehouseTransferCategory';
import { useWarehouse } from '@/stores/Warehouse';
import { useTruck } from '@/stores/Truck';
import mitt from '@/plugins/mitt.js';
import tableConfigMixin from '@/utils/tableConfigMixin.js';
import virtualTableMixin from '@/utils/virtualTableMixin.js';
import { getDatePresetRange, detectMatchingPreset, getDatePresetOptions } from '@/utils/datePresets.js';

export default {
  name: 'ListWarehouseTransfer',
  mixins: [tableConfigMixin, virtualTableMixin],
  emits: ['transfer-deleted'],
  data() {
    const n = new Date();
    const sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      transfers: [],
      loading: false,
      selected: [],
      dateRange: [sm, n],
      datePreset: 'this_month',
      searchQuery: '',
      colFilters: {},
      colSorts: [],
      pinState: {},
      expanded: {},
      deleteDialog: false,
      deleting: false,
      deleteId: null,
      deleteBulk: false,
      filterMenus: {},
      columnOrder: null,
      dragCol: null,
      dragOverCol: null,
      colWidths: {},
      chartMode: 'daily',
    };
  },
  computed: {
    main() { return useMain(); },
    cats() { return useWarehouseTransferCategory().list; },
    warehouses() { return useWarehouse().list; },
    trucks() { return useTruck().list; },
    today() { return new Date().toISOString().slice(0, 10); },
    tableModuleKey() { return 'warehouse_transfers'; },
    moduleColor() { return '#F9A825'; },
    datePickerColor() { return 'yellow'; },
    datePresetOptions() { return getDatePresetOptions(this.$t); },
    selectedSet() { return new Set(this.selected); },
    selectAll: {
      get() {
        return this.colFilteredItems.length > 0 && this.colFilteredItems.every(i => this.selectedSet.has(i.id));
      },
      set(val) {
        if (val) {
          const set = new Set(this.selected);
          this.colFilteredItems.forEach(i => set.add(i.id));
          this.selected = Array.from(set);
        } else {
          const visibleIds = new Set(this.colFilteredItems.map(i => i.id));
          this.selected = this.selected.filter(id => !visibleIds.has(id));
        }
      },
    },
    dateRangeVC: {
      get() { return { start: this.dateRange[0], end: this.dateRange[1] || this.dateRange[0] }; },
      set(v) { if (v && v.start && v.end) this.dateRange = [v.start, v.end]; },
    },
    allHeaders() {
      return [
        { title: '', key: 'expand', sortable: false, width: 40, fixed: true },
        { title: this.$t('date'), key: 'date', align: 'center' },
        { title: this.$t('transfer_tracking_number'), key: '_tracking', align: 'center' },
        { title: this.$t('trucks'), key: '_trucks', align: 'center' },
        { title: this.$t('total_weight'), key: 'total_weight', align: 'center' },
        { title: this.$t('details'), key: '_details_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, fixed: true },
      ];
    },
    headers() {
      const hiddenSet = new Set(this.hiddenColumns || []);
      const expandCol = this.allHeaders.find(h => h.key === 'expand');
      const actionsCol = this.allHeaders.find(h => h.key === 'actions');
      const baseToggleable = this.allHeaders.filter(h => !h.fixed && !hiddenSet.has(h.key));
      let orderedCols = baseToggleable;
      if (this.columnOrder && this.columnOrder.length) {
        const map = Object.fromEntries(this.allHeaders.map(h => [h.key, h]));
        const ordered = this.columnOrder
          .filter(k => map[k] && !map[k].fixed && !hiddenSet.has(k))
          .map(k => map[k]);
        const unhandled = baseToggleable.filter(h => !this.columnOrder.includes(h.key));
        orderedCols = [...ordered, ...unhandled];
      }
      const res = [];
      if (expandCol) res.push(expandCol);
      res.push(...orderedCols.filter(h => this.pinState[h.key] === 'left'));
      res.push(...orderedCols.filter(h => !this.pinState[h.key]));
      res.push(...orderedCols.filter(h => this.pinState[h.key] === 'right'));
      if (actionsCol) res.push(actionsCol);
      return res;
    },
    colFilteredItems() {
      let items = this.transfers;
      if (this.searchQuery && this.searchQuery.trim()) {
        const q = this.searchQuery.trim().toLowerCase();
        items = items.filter(it => {
          return (it._tracking || '').toLowerCase().includes(q)
            || (it._trucks || '').toLowerCase().includes(q)
            || String(it.total_weight || '').includes(q);
        });
      }
      Object.entries(this.colFilters).forEach(([k, vals]) => {
        if (!vals || !vals.length) return;
        items = items.filter(it => vals.includes(this.cellText(k, it)));
      });
      return this.sortItems(items);
    },
    totalWeight() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_weight || 0), 0); },
    categoryStats() {
      const lang = this.main.lang?.code || 'en';
      const map = {};
      this.colFilteredItems.forEach(c => {
        (c.detail_warehouse_transfers || []).forEach(d => {
          const catId = d.warehouse_transfer_category_id;
          if (!map[catId]) {
            const cat = this.cats.find(x => x.id === catId);
            map[catId] = { name: cat ? (cat[`name_${lang}`] || cat.name_fr || `#${catId}`) : `#${catId}`, weight: 0, count: 0 };
          }
          map[catId].weight += Number(d.weight || 0);
          map[catId].count++;
        });
      });
      return Object.values(map);
    },
    chartSeries() {
      const items = this.colFilteredItems;
      if (!items.length) return [{ name: '-', data: [] }];
      const map = {};
      items.forEach(c => {
        if (!c.date) return;
        const d = new Date(c.date);
        let key;
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
  },
  mounted() {
    this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
    this.fetchTransfers();
    mitt.on('model-changed', this.onMC);
  },
  activated() { this.fetchTransfers(); },
  beforeUnmount() { mitt.off('model-changed', this.onMC); },
  methods: {
    onMC(d) { if (d && d.model === 'WarehouseTransfer') this.fetchTransfers(); },
    onDatePresetChange(preset) {
      if (!preset) return;
      const range = getDatePresetRange(preset);
      if (range && range.length === 2) {
        this.dateRange = [range[0], range[1]];
        this.fetchTransfers();
      }
    },
    onDateRangeChange(val) {
      if (val && val.start && val.end) {
        this.dateRange = [val.start, val.end];
      }
      this.datePreset = detectMatchingPreset(this.dateRange[0], this.dateRange[1]);
      this.fetchTransfers();
    },
    onColFilterChange() {
      this.resetTableScroll?.();
    },
    async fetchTransfers() {
      this.loading = true;
      try {
        const ds = this.dateRange[0], de = this.dateRange[1] || this.dateRange[0];
        const p = { date_start: this.fmtDate(ds), date_end: this.fmtDate(de) };
        const r = await axios.get('/api/warehouse-transfers/get_transfers', { params: p });
        this.transfers = (r.data.transfers || []).map(c => ({
          ...c,
          _tracking: (c.transfer_tracking_number || []).join(', ') || '-',
          _trucks: (c.trucks || []).map(t => t.name).join(', ') || '-',
          _details_count: (c.detail_warehouse_transfers || []).length,
        }));
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; return d.toISOString().slice(0, 10); },
    cellText(key, item) {
      if (!item) return '-';
      if (key === 'date') return this.main.formateDate(item.date);
      const v = item[key];
      return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      const s = new Set();
      (this.transfers || []).forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') s.add(v);
      });
      return [...s].sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }));
    },
    filterCounts(key) {
      const counts = {};
      (this.transfers || []).forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') counts[v] = (counts[v] || 0) + 1;
      });
      return counts;
    },
    sortDir(key) { const s = this.colSorts.find(x => x.key === key); return s ? s.dir : null; },
    sortIdx(key) { return this.colSorts.findIndex(x => x.key === key) + 1; },
    sortItems(items) {
      if (!this.colSorts.length) return items;
      return [...items].sort((a, b) => {
        for (const { key, dir } of this.colSorts) {
          let va = a[key], vb = b[key];
          if (va == null) va = '';
          if (vb == null) vb = '';
          const n = typeof va === 'number' && typeof vb === 'number';
          if (n) {
            if (va !== vb) return dir === 'asc' ? va - vb : vb - va;
          } else {
            const c = String(va).localeCompare(String(vb));
            if (c) return dir === 'asc' ? c : -c;
          }
        }
        return 0;
      });
    },
    toggleItemSelect(id) {
      const idx = this.selected.indexOf(id);
      if (idx > -1) this.selected.splice(idx, 1);
      else this.selected.push(id);
    },
    togglePin(k) {
      const cur = this.pinState[k];
      if (!cur) this.pinState[k] = 'left';
      else if (cur === 'left') this.pinState[k] = 'right';
      else delete this.pinState[k];
      this.pinState = { ...this.pinState };
    },
    isPinned(k) { return !this.pinState[k]; },
    pinSide(k) { return this.pinState[k] || null; },
    toggleExpand(id) {
      this.expanded[id] = !this.expanded[id];
      this.expanded = { ...this.expanded };
    },
    onDragStart(key) { this.dragCol = key; },
    onDragOver(key, e) { e.preventDefault(); this.dragOverCol = key; },
    onDragEnd() {
      if (this.dragCol && this.dragOverCol && this.dragCol !== this.dragOverCol) {
        const order = [...this.columnOrder];
        const fi = order.indexOf(this.dragCol);
        const ti = order.indexOf(this.dragOverCol);
        if (fi > -1 && ti > -1) {
          order.splice(fi, 1);
          order.splice(ti, 0, this.dragCol);
          this.columnOrder = order;
        }
      }
      this.dragCol = null;
      this.dragOverCol = null;
    },
    groupDetails(transfer) {
      const groups = {};
      (transfer.detail_warehouse_transfers || []).forEach(d => {
        const catId = d.warehouse_transfer_category_id;
        if (!groups[catId]) {
          groups[catId] = { category: d.warehouse_transfer_category, items: [], totalW: 0 };
        }
        groups[catId].items.push(d);
        groups[catId].totalW += Number(d.weight || 0);
      });
      return Object.values(groups);
    },
    articleName(d) {
      if (!d.stockable_type || d.stockable_type === 'text') return d.article_description || '-';
      const s = d.stockable;
      return s ? (s.name || s[`name_${this.main.lang?.code}`] || '-') : '-';
    },
    warehouseName(id) {
      const w = this.warehouses.find(x => x.id === id);
      return w ? w.name : '-';
    },
    confirmDelete(id) { this.deleteId = id; this.deleteBulk = false; this.deleteDialog = true; },
    confirmBulkDelete() { this.deleteBulk = true; this.deleteDialog = true; },
    async execDelete() {
      this.deleting = true;
      try {
        const ids = this.deleteBulk ? this.selected : [this.deleteId];
        const deletedTransfers = this.transfers.filter(i => ids.includes(i.id));
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
        this.selected = [];
        this.deleteDialog = false;
        this.fetchTransfers();
      } catch (e) { console.error(e); }
      this.deleting = false;
    },
  },
};
