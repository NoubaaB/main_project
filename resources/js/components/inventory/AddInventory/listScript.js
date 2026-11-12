import axios from 'axios';
import { gqlGetInventories } from '@/services/appGraphql.js';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import mitt from '@/plugins/mitt.js';
import tableConfigMixin from '@/utils/tableConfigMixin.js';
import virtualTableMixin from '@/utils/virtualTableMixin.js';
import { getDatePresetRange, detectMatchingPreset, getDatePresetOptions } from '@/utils/datePresets.js';

const C = '#6D4C41';

export default {
  name: 'ListInventory',
  mixins: [tableConfigMixin, virtualTableMixin],
  data() {
    const n = new Date();
    const sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      inventories: [],
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
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    today() { return new Date().toISOString().slice(0, 10); },
    tableModuleKey() { return 'inventory'; },
    moduleColor() { return C; },
    datePickerColor() { return 'brown'; },
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
    detailCats() { return this.auth.detail_inventory_category?.list || []; },
    allHeaders() {
      return [
        { title: '', key: 'expand', sortable: false, width: 40, fixed: true },
        { title: this.$t('date'), key: 'date', align: 'center' },
        { title: this.$t('inventory_tracking_number'), key: '_tracking', align: 'center' },
        { title: this.$t('description'), key: 'description', align: 'center' },
        { title: this.$t('total_weight'), key: 'total_weight', align: 'center' },
        { title: this.$t('total_price'), key: 'total_price', align: 'center' },
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
      let items = this.inventories;
      if (this.searchQuery && this.searchQuery.trim()) {
        const q = this.searchQuery.trim().toLowerCase();
        items = items.filter(it => {
          return (it._tracking || '').toLowerCase().includes(q)
            || (it.description || '').toLowerCase().includes(q)
            || String(it.total_weight || '').includes(q)
            || String(it.total_price || '').includes(q);
        });
      }
      Object.entries(this.colFilters).forEach(([k, vals]) => {
        if (!vals || !vals.length) return;
        items = items.filter(it => vals.includes(this.cellText(k, it)));
      });
      return this.sortItems(items);
    },
    totalWeight() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_weight || 0), 0); },
    totalPrice() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_price || 0), 0); },
    categoryStats() {
      const map = {};
      this.colFilteredItems.forEach(inv => {
        (inv.detail_inventories || []).forEach(di => {
          const catId = di.detail_inventory_category_id;
          if (!map[catId]) {
            const cat = this.detailCats.find(x => x.id === catId);
            map[catId] = {
              name: cat ? (cat[`name_${this.lang}`] || cat.name_fr || cat.name_en || `#${catId}`) : `#${catId}`,
              weight: 0,
              price: 0,
              count: 0,
            };
          }
          map[catId].weight += Number(di.weight || 0);
          map[catId].price += Number(di.total_price || 0);
          map[catId].count++;
        });
      });
      return Object.values(map);
    },
    chartSeries() {
      const items = this.colFilteredItems;
      const buckets = {};
      items.forEach(c => {
        const d = new Date(c.date);
        const dateStr = typeof c.date === 'string' ? c.date.slice(0, 10) : d.toISOString().slice(0, 10);
        let key;
        if (this.chartMode === 'daily') key = dateStr;
        else if (this.chartMode === 'monthly') key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        else key = String(d.getFullYear());
        if (!buckets[key]) buckets[key] = { w: 0, p: 0 };
        buckets[key].w += Number(c.total_weight || 0);
        buckets[key].p += Number(c.total_price || 0);
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
        colors: [C, '#FF7043'],
        xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 }, rotate: -45, rotateAlways: this.chartMode === 'daily' }, tooltip: { enabled: false } },
        yaxis: [
          { title: { text: this.$t('total_weight') + ' (Kg)', style: { fontSize: '11px', fontWeight: 700, color: C } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
          { opposite: true, title: { text: this.$t('total_price') + ' (' + this.main.currency + ')', style: { fontSize: '11px', fontWeight: 700, color: '#FF7043' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
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
    this.fetchInventories();
    mitt.on('model-changed', this.onMC);
  },
  activated() { this.fetchInventories(); },
  beforeUnmount() { mitt.off('model-changed', this.onMC); },
  methods: {
    onMC(d) { if (d && d.model === 'Inventory') this.fetchInventories(); },
    onDatePresetChange(preset) {
      if (!preset) return;
      const range = getDatePresetRange(preset);
      if (range && range.length === 2) {
        this.dateRange = [range[0], range[1]];
        this.fetchInventories();
      }
    },
    onDateRangeChange(val) {
      if (val && val.start && val.end) {
        this.dateRange = [val.start, val.end];
      }
      this.datePreset = detectMatchingPreset(this.dateRange[0], this.dateRange[1]);
      this.fetchInventories();
    },
    onColFilterChange() {
      this.resetTableScroll?.();
    },
    async fetchInventories() {
      this.loading = true;
      try {
        const ds = this.dateRange[0], de = this.dateRange[1] || this.dateRange[0];
        const date_start = this.fmtDate(ds), date_end = this.fmtDate(de);
        let items = [];
        try {
          items = await gqlGetInventories({ date_start, date_end });
        } catch (gqlErr) {
          console.warn('[ListInventory] GraphQL fetch failed, falling back to REST:', gqlErr);
          const r = await axios.get('/api/inventories/get_inventories', { params: { date_start, date_end } });
          items = r.data.inventories || [];
        }
        this.inventories = (items || []).map(c => ({
          ...c,
          _tracking: (c.inventory_tracking_numbers || []).join(', ') || '-',
          _details_count: (c.detail_inventories || []).length,
        }));
      } catch (e) {
        console.error(e);
      }
      this.loading = false;
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; return d.toISOString().slice(0, 10); },
    cellText(key, item) {
      if (!item) return '-';
      if (key === 'date') return this.main.formateDate ? this.main.formateDate(item.date) : (item.date || '').slice(0, 10);
      const v = item[key];
      return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      const s = new Set();
      (this.inventories || []).forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') s.add(v);
      });
      return [...s].sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }));
    },
    filterCounts(key) {
      const counts = {};
      (this.inventories || []).forEach(i => {
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
        const fromIdx = order.indexOf(this.dragCol);
        const toIdx = order.indexOf(this.dragOverCol);
        if (fromIdx > -1 && toIdx > -1) {
          order.splice(fromIdx, 1);
          order.splice(toIdx, 0, this.dragCol);
          this.columnOrder = order;
        }
      }
      this.dragCol = null;
      this.dragOverCol = null;
    },
    groupDetails(inv) {
      const groups = {};
      (inv.detail_inventories || []).forEach(di => {
        const catId = di.detail_inventory_category_id || 'uncategorized';
        if (!groups[catId]) {
          const cat = catId !== 'uncategorized' ? this.detailCats.find(x => x.id === catId) : null;
          groups[catId] = {
            category: cat || { name_en: catId === 'uncategorized' ? 'Warehouse Stock' : '#' + catId },
            items: [],
            totalW: 0,
            totalP: 0,
          };
        }
        groups[catId].items.push(di);
        groups[catId].totalW += Number(di.weight || 0);
        groups[catId].totalP += Number(di.total_price || 0);
      });
      return Object.values(groups);
    },
    articleName(di) {
      if (!di.stockable_type || di.stockable_type === 'text') return di.article_description || '-';
      const s = di.stockable;
      return s ? (s.name || s[`name_${this.lang}`] || '-') : '-';
    },
    confirmDelete(id) { this.deleteId = id; this.deleteBulk = false; this.deleteDialog = true; },
    confirmBulkDelete() { this.deleteBulk = true; this.deleteDialog = true; },
    async execDelete() {
      this.deleting = true;
      try {
        const ids = this.deleteBulk ? this.selected : [this.deleteId];
        await axios.delete('/api/inventory', { data: { ids } });
        this.main.add_snackbar({ show: true, message: this.$t('deleted_successfully'), color: 'success' });
        this.selected = [];
        this.deleteDialog = false;
        this.fetchInventories();
      } catch (e) {
        console.error(e);
      }
      this.deleting = false;
    },
    editInventory(item) {
      this.$emit('edit-inventory', item);
    },
  },
};
