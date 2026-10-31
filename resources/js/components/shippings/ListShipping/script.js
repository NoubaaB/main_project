import axios from 'axios';
import { gqlGetShippings } from '@/services/appGraphql.js';
import { useMain } from '@/stores/Main';
import { useShippingCategory } from '@/stores/ShippingCategory';
import { useFishFamily } from '@/stores/FishFamily';
import { useFishType } from '@/stores/FishType';
import { useGuanoType } from '@/stores/GuanoType';
import { usePackageType } from '@/stores/PackageType';
import { useWarehouse } from '@/stores/Warehouse';
import { useFishProcessType } from '@/stores/FishProcessType';
import mitt from '@/plugins/mitt.js';
import * as XLSX from 'xlsx';
import { createListWorker, extractAdvFilterOptions } from '@/utils/listDataWorker.js';
export default {
  name: 'ListShipping',
  data() {
    const n = new Date(), sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      tableModuleKey: 'shipping',
      moduleColor: '#4643a0',
      shippings: [], loading: false, selected: [],
      searchQuery: '',
      dateRange: [sm, n], dateMenu: false,
      colFilters: {}, colSorts: [], pinState: {},
      expanded: {},
      tableConfigMenu: false,
      showAdvanced: false, showInvoice: false,
      advFilter: {
        articleTypes: [],
        articleType: null,
        fishFamilies: [],
        fishFreshFrozen: [],
        fishProcessTypes: [],
        fishTypes: [],
        guanoTypes: [],
        packageTypes: [],
        warehouses: []
      },
      workerAdvOptions: null,
      invForm: { date: null, type_invoice: 'local', trackingNumbers: [], trackingInput: '', incoterm: null, currency: null, total_gross_weight: null, intermediary_company: '', intermediary_address: '', intermediary_country: '', destination_company: '', destination_address: '', destination_country: '', shipment_country: '', tax_percentage: 0, export_details: [] },
      invChecking: false,
      deleteDialog: false, deleting: false, deleteId: null, deleteBulk: false,
      filterMenus: {},
      columnOrder: null, dragCol: null, dragOverCol: null,
      colWidths: {},
      chartMode: 'daily',
      workerFilteredIds: null,
      workerTotals: null,
      workerCategoryStats: null,
      workerChartBuckets: null,
      workerFilterOptions: {},
      workerFilterOptionCounts: {},
      isWorkerFiltering: false,
    };
  },
  computed: {
    main() { return useMain() },
    cats() { return useShippingCategory().list },
    fishFamilies() { return useFishFamily().list },
    fishTypes() { return useFishType().list },
    guanoTypes() { return useGuanoType().list },
    packageTypes() { return usePackageType().list },
    warehouses() { return useWarehouse().list },
    fishProcessTypes() { return useFishProcessType().list },
    today() { return new Date().toISOString().slice(0, 10) },
    dateRangeVC: { get() { return { start: this.dateRange[0], end: this.dateRange[1] || this.dateRange[0] } }, set(v) { if (v && v.start && v.end) this.dateRange = [v.start, v.end] } },
    catName() { return (id) => { const l = this.main.lang?.code || 'en'; const c = this.cats.find(x => x.id === id); return c ? (c[`name_${l}`] || c.name || `#${id}`) : `#${id}` } },
    allHeaders() {
      return [
        { title: '', key: 'expand', sortable: false, width: 40, fixed: true },
        { title: this.$t('date'), key: 'date', align: 'center' },
        { title: this.$t('shipping_category'), key: 'shipping_category_id', align: 'center' },
        { title: this.$t('client'), key: '_clients', align: 'center' },
        { title: this.$t('shipping_tracking_number'), key: '_tracking', align: 'center' },
        { title: this.$t('origin'), key: '_origin', align: 'center' },
        { title: this.$t('direction'), key: 'direction', align: 'center' },
        { title: this.$t('trucks'), key: '_trucks', align: 'center' },
        { title: this.$t('total_weight'), key: 'total_weight', align: 'center' },
        { title: this.$t('total_price'), key: 'total_price', align: 'center' },
        { title: this.$t('invoice_state'), key: '_invoice_state', align: 'center' },
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
    activeFilterCount() {
      let count = 0;
      if (this.searchQuery && this.searchQuery.trim()) count++;
      if (this.colFilters) {
        Object.values(this.colFilters).forEach(v => {
          if (Array.isArray(v) && v.length) count += v.length;
        });
      }
      count += this.activeAdvFilterCount;
      return count;
    },
    activeAdvFilterCount() {
      if (!this.advFilter) return 0;
      const af = this.advFilter;
      let count = 0;
      if (af.articleTypes?.length) count += af.articleTypes.length;
      else if (af.articleType) count++;
      if (af.warehouses?.length) count += af.warehouses.length;
      if (af.fishFamilies?.length) count += af.fishFamilies.length;
      if (af.fishFreshFrozen?.length) count += af.fishFreshFrozen.length;
      if (af.fishProcessTypes?.length) count += af.fishProcessTypes.length;
      if (af.fishTypes?.length) count += af.fishTypes.length;
      if (af.guanoTypes?.length) count += af.guanoTypes.length;
      if (af.packageTypes?.length) count += af.packageTypes.length;
      return count;
    },
    dynamicAdvOptions() {
      const lang = this.main.lang?.code || 'en';
      const items = this.shippings || [];
      return extractAdvFilterOptions(
        items,
        this.advFilter || {},
        {
          fishFamilies: this.fishFamilies,
          fishTypes: this.fishTypes,
          fishProcessTypes: this.fishProcessTypes,
          guanoTypes: this.guanoTypes,
          packageTypes: this.packageTypes,
          warehouses: this.warehouses,
        },
        lang
      );
    },
    articleTypeOptions() {
      return this.dynamicAdvOptions?.articleTypes || [];
    },
    warehouseOptions() {
      return this.dynamicAdvOptions?.warehouses || [];
    },
    fishFamilyOptions() {
      return this.dynamicAdvOptions?.fishFamilies || [];
    },
    fishFreshFrozenOptions() {
      return this.dynamicAdvOptions?.fishFreshFrozen || [];
    },
    fishProcessTypeOptions() {
      return this.dynamicAdvOptions?.fishProcessTypes || [];
    },
    fishTypeOptions() {
      return this.dynamicAdvOptions?.fishTypes || [];
    },
    guanoTypeOptions() {
      return this.dynamicAdvOptions?.guanoTypes || [];
    },
    packageTypeOptions() {
      return this.dynamicAdvOptions?.packageTypes || [];
    },
    itemsById() {
      const m = new Map();
      const arr = this.shippings || [];
      for (let i = 0; i < arr.length; i++) {
        m.set(arr[i].id, arr[i]);
      }
      return m;
    },
    colFilteredItems() {
      if (!this.workerFilteredIds) {
        return this.shippings || [];
      }
      const map = this.itemsById;
      const res = [];
      const ids = this.workerFilteredIds;
      for (let i = 0; i < ids.length; i++) {
        const item = map.get(ids[i]);
        if (item) res.push(item);
      }
      return res;
    },
    totalWeight() {
      if (this.workerTotals && typeof this.workerTotals.totalWeight === 'number') {
        return this.workerTotals.totalWeight;
      }
      return this.colFilteredItems.reduce((s, c) => s + Number(c.total_weight || 0), 0);
    },
    totalPrice() {
      if (this.workerTotals && typeof this.workerTotals.totalPrice === 'number') {
        return this.workerTotals.totalPrice;
      }
      return this.colFilteredItems.reduce((s, c) => s + Number(c.total_price || 0), 0);
    },
    totalInvoiced() {
      if (this.workerTotals && typeof this.workerTotals.totalInvoiced === 'number') {
        return this.workerTotals.totalInvoiced;
      }
      return this.colFilteredItems.filter(c => c._is_invoiced).length;
    },
    categoryStats() {
      if (this.workerCategoryStats && this.workerCategoryStats.length) {
        return this.workerCategoryStats;
      }
      const lang = this.main.lang?.code || 'en'; const map = {};
      this.colFilteredItems.forEach(c => { const catId = c.shipping_category_id; if (!map[catId]) { const cat = this.cats.find(x => x.id === catId); map[catId] = { name: cat ? (cat[`name_${lang}`] || cat.name || `#${catId}`) : `#${catId}`, weight: 0, price: 0, count: 0 } } map[catId].weight += Number(c.total_weight || 0); map[catId].price += Number(c.total_price || 0); map[catId].count++ });
      return Object.values(map);
    },
    chartSeries() {
      if (this.workerChartBuckets) {
        const buckets = this.workerChartBuckets;
        const sorted = Object.keys(buckets).sort();
        return [
          { name: this.$t('total_weight') + ' (Kg)', type: 'area', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].w * 100) / 100 })) },
          { name: this.$t('total_price') + ' (' + this.main.currency + ')', type: 'line', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].p * 100) / 100 })) }
        ];
      }
      const items = this.colFilteredItems; const buckets = {};
      items.forEach(c => { const d = new Date(c.date); const dateStr = typeof c.date === 'string' ? c.date.slice(0, 10) : d.toISOString().slice(0, 10); let key; if (this.chartMode === 'daily') key = dateStr; else if (this.chartMode === 'monthly') key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); else key = String(d.getFullYear()); if (!buckets[key]) buckets[key] = { w: 0, p: 0 }; buckets[key].w += Number(c.total_weight || 0); buckets[key].p += Number(c.total_price || 0) });
      const sorted = Object.keys(buckets).sort();
      return [{ name: this.$t('total_weight') + ' (Kg)', type: 'area', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].w * 100) / 100 })) }, { name: this.$t('total_price') + ' (' + this.main.currency + ')', type: 'line', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].p * 100) / 100 })) }];
    },
    chartOptions() {
      return { chart: { type: 'line', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent', animations: { enabled: true, easing: 'easeinout', speed: 600 } }, stroke: { width: [2, 3], curve: 'smooth' }, fill: { type: ['gradient', 'solid'], gradient: { shadeIntensity: 1, opacityFrom: .4, opacityTo: .05, stops: [0, 90, 100] } }, colors: ['#4643a0', '#FF7043'], xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 }, rotate: -45, rotateAlways: this.chartMode === 'daily' }, tooltip: { enabled: false } }, yaxis: [{ title: { text: this.$t('total_weight') + ' (Kg)', style: { fontSize: '11px', fontWeight: 700, color: '#4643a0' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } }, { opposite: true, title: { text: this.$t('total_price') + ' (' + this.main.currency + ')', style: { fontSize: '11px', fontWeight: 700, color: '#FF7043' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } }], tooltip: { shared: true, intersect: false, y: { formatter: v => this.main.formatDisplayNumber(v) } }, grid: { borderColor: 'rgba(0,0,0,.06)', strokeDashArray: 4 }, legend: { position: 'top', fontSize: '11px', fontWeight: 600 }, dataLabels: { enabled: false }, theme: { mode: this.main.is_dark ? 'dark' : 'light' } };
    },
    selectedShippings() { return this.shippings.filter(c => this.selected.includes(c.id)) },
    selectedSells() { return this.selectedShippings },
    uninvoicedSelectedShippings() { return this.selectedShippings.filter(c => !c._is_invoiced) },
    invoiceClients() { const s = new Set(); this.uninvoicedSelectedShippings.forEach(c => { if (c.client?.name) s.add(c.client.name) }); return [...s] },
    canSetupInvoice() {
      if (!this.uninvoicedSelectedShippings.length) return false;
      const ids = this.uninvoicedSelectedShippings.map(c => c.client_id).filter(Boolean);
      if (!ids.length) return true;
      return new Set(ids).size === 1;
    },
    invMinDate() {
      if (!this.selectedShippings.length) return null;
      const dates = this.selectedShippings.map(c => new Date(c.date)).filter(d => !isNaN(d));
      return dates.length ? new Date(Math.min(...dates)) : null;
    },
    invMaxDate() {
      if (!this.selectedShippings.length) return null;
      const dates = this.selectedShippings.map(c => new Date(c.date)).filter(d => !isNaN(d));
      return dates.length ? new Date(Math.max(...dates)) : null;
    },
    invDisabledDates() {
      if (!this.invMinDate && !this.invMaxDate) return undefined;
      const rules = [];
      if (this.invMinDate) rules.push({ start: null, end: new Date(this.invMinDate.getTime() - 86400000) });
      if (this.invMaxDate) rules.push({ start: new Date(this.invMaxDate.getTime() + 86400000), end: null });
      return rules;
    },
  },
  mounted() {
    this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
    if (this.loadTableConfig) this.loadTableConfig();
    this.fetchShippings();
    mitt.on('model-changed', this.onMC);
  },
  activated() { this.fetchShippings(); },
  beforeUnmount() {
    mitt.off('model-changed', this.onMC);
    if (this._listWorker) {
      this._listWorker.terminate();
    }
  },
  methods: {
    initWorker() {
      if (!this._listWorker) {
        this._listWorker = createListWorker();
      }
    },
    async dispatchWorkerFilter(debounceMs = 0) {
      this.initWorker();
      if (!this._cachedWorkerItems || !this._cachedWorkerItems.length) {
        this._cachedWorkerItems = JSON.parse(JSON.stringify(this.shippings || []));
      }
      this.isWorkerFiltering = true;
      const payload = {
        items: this._cachedWorkerItems,
        moduleType: 'shipping',
        searchQuery: this.searchQuery,
        colFilters: JSON.parse(JSON.stringify(this.colFilters || {})),
        advFilter: JSON.parse(JSON.stringify(this.advFilter || {})),
        colSorts: JSON.parse(JSON.stringify(this.colSorts || [])),
        chartMode: this.chartMode,
        langCode: this.main.lang?.code || 'en',
        cats: JSON.parse(JSON.stringify(this.cats || [])),
        fishFamilies: JSON.parse(JSON.stringify(this.fishFamilies || [])),
        fishTypes: JSON.parse(JSON.stringify(this.fishTypes || [])),
        fishProcessTypes: JSON.parse(JSON.stringify(this.fishProcessTypes || [])),
        guanoTypes: JSON.parse(JSON.stringify(this.guanoTypes || [])),
        packageTypes: JSON.parse(JSON.stringify(this.packageTypes || [])),
        warehouses: JSON.parse(JSON.stringify(this.warehouses || [])),
        allHeaderKeys: this.allHeaders.map(h => h.key),
      };
      try {
        const res = await this._listWorker.execute(payload, debounceMs);
        if (res) {
          this.workerFilteredIds = res.filteredIds;
          this.workerTotals = res.totals;
          this.workerCategoryStats = res.categoryStats;
          this.workerChartBuckets = res.chartBuckets;
          this.workerFilterOptions = res.filterOptions || {};
          this.workerFilterOptionCounts = res.filterOptionCounts || {};
          if (res.advFilterOptions) {
            this.workerAdvOptions = res.advFilterOptions;
          }
        }
      } catch (e) {
        console.error('[dispatchWorkerFilter error]', e);
      } finally {
        this.isWorkerFiltering = false;
      }
    },
    onMC(d) {
      if (d.model === 'Shipping') {
        const rd = d.record_data;
        // If record_data is empty (AI Assistant bulk operation) or action is 'refresh', do full refetch
        if (d.action === 'refresh' || !rd || (typeof rd === 'object' && Object.keys(rd).length === 0) || (Array.isArray(rd) && rd.length === 0)) {
          this.fetchShippings();
          return;
        }
        if ((d.action === 'created' || d.action === 'updated') && rd && rd.date) {
          const shipDate = typeof rd.date === 'string' ? rd.date.slice(0, 10) : rd.date;
          const ds = this.fmtDate(this.dateRange[0]);
          const de = this.fmtDate(this.dateRange[1] || this.dateRange[0]);
          if (shipDate >= ds && shipDate <= de) {
            const mapped = this.mapShipping(rd);
            const idx = this.shippings.findIndex(c => c.id === rd.id);
            if (idx !== -1) { this.shippings.splice(idx, 1, mapped); }
            else { this.shippings.unshift(mapped); }
          } else {
            this.shippings = this.shippings.filter(c => c.id !== rd.id);
          }
          this._cachedWorkerItems = JSON.parse(JSON.stringify(this.shippings));
          this.dispatchWorkerFilter(0);
        } else if (d.action === 'deleted' && d.record_id) {
          this.shippings = this.shippings.filter(c => c.id !== d.record_id);
          this._cachedWorkerItems = JSON.parse(JSON.stringify(this.shippings));
          this.dispatchWorkerFilter(0);
        }
      } else if (d.model === 'ClientInvoice') {
        this.fetchShippings();
      } else if (d.model === 'RecordIssue') {
        this._fetchIssueCounts('Shipping');
      }
    },
    mapShipping(c) {
      const lang = this.main.lang?.code || 'en';
      return {
        ...c,
        _clients: c.client?.name || '-',
        _tracking: (c.shipping_tracking_number || []).join(', ') || '-',
        _origin: c.origin ? (c.origin[`name_${lang}`] || c.origin.name || '-') : '-',
        _trucks: (c.trucks || []).map(t => t.name || t[`name_${lang}`]).join(', ') || '-',
        _invoice_state: c.client_invoice ? (c.client_invoice.tracking_numbers || []).join(', ') : null,
        _is_invoiced: !!c.invoice_id,
      };
    },
    onDateRangeChange(val) { if (val && val.start && val.end) { this.dateRange = [val.start, val.end]; this.fetchShippings() } },
    async fetchShippings() {
      this.loading = true;
      try {
        const ds = this.dateRange[0], de = this.dateRange[1] || this.dateRange[0];
        const date_start = this.fmtDate(ds), date_end = this.fmtDate(de);
        let items = [];
        try {
          items = await gqlGetShippings({ date_start, date_end });
        } catch (gqlErr) {
          console.warn('[ListShipping] GraphQL fetch failed, falling back to REST:', gqlErr);
          const r = await axios.get('/api/shippings/get_shippings', { params: { date_start, date_end } });
          items = r.data.shippings || r.data.sells || [];
        }
        this.shippings = (items || []).map(c => this.mapShipping(c));
        this._cachedWorkerItems = JSON.parse(JSON.stringify(this.shippings));
        this.dispatchWorkerFilter(0);
        // Fetch open issue counts and merge
        this._fetchIssueCounts('Shipping');
      } catch (e) { console.error(e) }
      this.loading = false;
    },
    async _fetchIssueCounts(type) {
      try {
        const ids = this.shippings.map(c => c.id);
        if (!ids.length) return;
        const r = await axios.post('/api/record-issues/batch-counts', { record_type: type, record_ids: ids });
        const counts = r.data.counts || {};
        this.shippings = this.shippings.map(c => ({ ...c, _open_issues: counts[c.id] || 0 }));
      } catch (e) { /* silent */ }
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; const y = d.getFullYear(); const m = String(d.getMonth() + 1).padStart(2, '0'); const day = String(d.getDate()).padStart(2, '0'); return `${y}-${m}-${day}` },
    cellText(key, item) {
      if (key === 'shipping_category_id') return this.catName(item.shipping_category_id);
      if (key === 'date') return this.main.formateDate(item.date);
      const v = item[key]; return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      if (this.workerFilterOptions && this.workerFilterOptions[key] && this.workerFilterOptions[key].length) {
        return this.workerFilterOptions[key];
      }
      // Compute from full dataset so options NEVER vanish when any filter is selected
      const s = new Set();
      this.shippings.forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') s.add(v);
      });
      return [...s].sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }));
    },
    filterCounts(key) {
      if (this.workerFilterOptionCounts && this.workerFilterOptionCounts[key]) {
        return this.workerFilterOptionCounts[key];
      }
      const counts = {};
      this.shippings.forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') counts[v] = (counts[v] || 0) + 1;
      });
      return counts;
    },
    onColFilterChange() {
      this.dispatchWorkerFilter(0);
    },
    sortItems(items) { if (!this.colSorts || !this.colSorts.length) return items; return [...items].sort((a, b) => { for (const { key, dir } of this.colSorts) { let va = key === 'shipping_category_id' ? this.catName(a[key]) : a[key], vb = key === 'shipping_category_id' ? this.catName(b[key]) : b[key]; if (va == null) va = ''; if (vb == null) vb = ''; const n = typeof va === 'number' && typeof vb === 'number'; if (n) { if (va !== vb) return dir === 'asc' ? va - vb : vb - va } else { const c = String(va).localeCompare(String(vb)); if (c) return dir === 'asc' ? c : -c } } return 0 }) },
    groupDetails(shipping) { const groups = {}; (shipping.detail_shippings || []).forEach(dc => { const catId = dc.detail_shipping_category_id; if (!groups[catId]) groups[catId] = { category: dc.detail_shipping_category, items: [], totalW: 0, totalP: 0 }; groups[catId].items.push(dc); groups[catId].totalW += Number(dc.weight || 0); groups[catId].totalP += Number(dc.total_price || 0) }); return Object.values(groups) },
    articleName(dc) { if (!dc.stockable_type || dc.stockable_type === 'text') return dc.article_description || '-'; const s = dc.stockable; return s ? (s.name || s[`name_${this.main.lang?.code}`] || '-') : '-' },
    articleFamily(dc) { if ((dc.stockable_type === 'FishType' || dc.stockable_type?.includes('FishType')) && dc.stockable) { const fam = this.fishFamilies.find(f => f.id === dc.stockable.fish_family_id); return fam ? (fam[`name_${this.main.lang?.code}`] || fam.name || '-') : '-' } return '-' },
    clearAdvFilters() {
      this.advFilter = {
        articleTypes: [],
        articleType: null,
        fishFamilies: [],
        fishFreshFrozen: [],
        fishProcessTypes: [],
        guanoTypes: [],
        packageTypes: [],
        warehouses: [],
      };
      this.dispatchWorkerFilter(0);
    },
    clearAllFilters() {
      this.searchQuery = '';
      this.colFilters = {};
      this.clearAdvFilters();
    },
    removeAdvFilterItem(category, val) {
      if (!this.advFilter) return;
      if (Array.isArray(this.advFilter[category])) {
        this.advFilter[category] = this.advFilter[category].filter(x => x !== val);
      } else if (this.advFilter[category] === val) {
        this.advFilter[category] = null;
      }
      this.dispatchWorkerFilter(0);
    },
    confirmDelete(id) { this.deleteId = id; this.deleteBulk = false; this.deleteDialog = true },
    confirmBulkDelete() { this.deleteBulk = true; this.deleteDialog = true },
    async execDelete() { this.deleting = true; try { const ids = this.deleteBulk ? this.selected : [this.deleteId]; await axios.delete('/api/shipping', { data: { ids } }); this.main.add_snackbar({ show: true, message: this.$t('delete_many_shippings'), color: 'success' }); this.selected = []; this.deleteDialog = false; this.fetchShippings() } catch (e) { console.error(e) } this.deleting = false },
    getInvoiceYear() {
      if (this.invForm.date) {
        const d = typeof this.invForm.date === 'string' ? new Date(this.invForm.date) : this.invForm.date;
        if (!isNaN(d)) return String(d.getFullYear()).slice(-2);
      }
      return String(new Date().getFullYear()).slice(-2);
    },
    getShippingCategoryId() {
      if (!this.uninvoicedSelectedShippings.length) return null;
      const ids = [...new Set(this.uninvoicedSelectedShippings.map(c => c.shipping_category_id))];
      return ids.length === 1 ? ids[0] : ids[0];
    },
    async fetchNextTracking(type) {
      try {
        const params = { type: type || 'local' };
        const catId = this.getShippingCategoryId();
        if (catId) params.shipping_category_id = catId;
        const dateStr = this.invForm.date ? this.fmtDate(this.invForm.date) : null;
        if (dateStr) params.date = dateStr;
        const r = await axios.get('/api/client_invoice/next-tracking-number', { params });
        if (r.data.next_tracking_number) {
          this.invForm.trackingNumbers = [r.data.next_tracking_number];
        }
      } catch (e) { console.error(e) }
    },
    async onTypeInvoiceChange(val) {
      this.invForm.trackingNumbers = [];
      if (val) await this.fetchNextTracking(val);
    },
    async onTrackingUpdate(val) {
      if (!val || !val.length) return;
      const raw = val[val.length - 1]; if (!raw || !raw.trim()) return;
      let v = raw.trim();
      const y = this.getInvoiceYear();
      const type = this.invForm.type_invoice || 'local';
      if (type === 'export') {
        if (!v.startsWith(y + '-E')) v = y + '-E' + v.replace(/^0+/, '').padStart(4, '0');
      } else {
        if (!v.startsWith(y + '-')) v = y + '-' + v.replace(/^0+/, '').padStart(4, '0');
      }
      this.invForm.trackingNumbers.splice(this.invForm.trackingNumbers.length - 1, 1);
      if (this.invForm.trackingNumbers.includes(v)) { this.main.add_snackbar({ show: true, message: this.$t('tracking_number_exists_in_invoice'), color: 'warning' }); return; }
      this.invChecking = true;
      try {
        const params = { tracking_number: v, type_invoice: type };
        const catId = this.getShippingCategoryId();
        if (catId) params.shipping_category_id = catId;
        const r = await axios.get('/api/client_invoice/check-tracking-number', { params });
        if (r.data.exists) { this.main.add_snackbar({ show: true, message: this.$t('tracking_number_exists_in_invoice'), color: 'warning' }) }
        else { this.invForm.trackingNumbers.push(v) }
      } catch (e) { console.error(e) }
      this.invChecking = false;
    },
    addExportDetail() { this.invForm.export_details.push({ description: '', container_or_truck_id: '', weight_mt: 0, unit_price_per_ton: 0, total_price: 0 }) },
    removeExportDetail(i) { this.invForm.export_details.splice(i, 1) },
    calcDetailTotal(line) { line.total_price = Number((Number(line.weight_mt || 0) * Number(line.unit_price_per_ton || 0)).toFixed(2)); return line.total_price },
    exportSubTotal() { return this.invForm.export_details.reduce((s, l) => s + Number(l.total_price || 0), 0) },
    exportGrandTotal() { const sub = this.exportSubTotal(); return Number((sub + sub * (Number(this.invForm.tax_percentage || 0) / 100)).toFixed(2)) },
    resetInvForm() { return { date: null, type_invoice: 'local', trackingNumbers: [], trackingInput: '', incoterm: null, currency: null, total_gross_weight: null, intermediary_company: '', intermediary_address: '', intermediary_country: '', destination_company: '', destination_address: '', destination_country: '', shipment_country: '', tax_percentage: 0, export_details: [] } },
    async submitInvoice() {
      if (!this.selected.length || !this.invForm.trackingNumbers.length) return;
      try {
        const clientId = this.selectedShippings[0]?.client_id;
        const payload = { date: this.fmtDate(this.invForm.date) || this.fmtDate(new Date()), type_invoice: this.invForm.type_invoice || 'local', tracking_numbers: this.invForm.trackingNumbers, client_id: clientId, shipping_ids: this.selected };
        if (payload.type_invoice === 'export') {
          Object.assign(payload, { incoterm: this.invForm.incoterm, currency: this.invForm.currency, total_gross_weight: this.invForm.total_gross_weight, intermediary_company: this.invForm.intermediary_company, intermediary_address: this.invForm.intermediary_address, intermediary_country: this.invForm.intermediary_country, destination_company: this.invForm.destination_company, destination_address: this.invForm.destination_address, destination_country: this.invForm.destination_country, shipment_country: this.invForm.shipment_country, tax_percentage: this.invForm.tax_percentage, export_details: this.invForm.export_details });
        }
        await axios.post('/api/client_invoice', payload);
        this.main.add_snackbar({ show: true, message: this.$t('invoice_created_successfully'), color: 'success' });
        this.invForm = this.resetInvForm(); this.showInvoice = false; this.fetchShippings();
      } catch (e) { console.error(e) }
    },
    clearAdvFilters() {
      this.advFilter = {
        articleTypes: [],
        articleType: null,
        fishFamilies: [],
        fishFreshFrozen: [],
        fishProcessTypes: [],
        fishTypes: [],
        guanoTypes: [],
        packageTypes: [],
        warehouses: [],
      };
      this.dispatchWorkerFilter(0);
    },
    clearAllFilters() {
      this.colFilters = {};
      this.clearAdvFilters();
      this.searchQuery = '';
      this.dispatchWorkerFilter(0);
      if (this.resetTableScroll) this.resetTableScroll();
    },
    removeAdvFilterItem(category, val) {
      if (!this.advFilter) return;
      if (Array.isArray(this.advFilter[category])) {
        this.advFilter[category] = this.advFilter[category].filter(x => x !== val);
      } else if (this.advFilter[category] === val) {
        this.advFilter[category] = null;
      }
      this.dispatchWorkerFilter(0);
    },
    cancelInvoice() { this.selected = []; this.invForm = this.resetInvForm(); this.showInvoice = false; },
    exportExcel() { const data = this.colFilteredItems.map(c => { const row = {}; this.headers.filter(h => h.key !== 'actions' && h.key !== 'expand').forEach(h => { row[h.title] = this.cellText(h.key, c) }); return row }); const ws = XLSX.utils.json_to_sheet(data); const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Shippings'); XLSX.writeFile(wb, `shippings_${this.today}.xlsx`) },
  },
  watch: {
    searchQuery() {
      this.dispatchWorkerFilter(150);
      if (this.resetTableScroll) this.resetTableScroll();
    },
    colFilters: {
      handler() {
        this.dispatchWorkerFilter(0);
        if (this.resetTableScroll) this.resetTableScroll();
      },
      deep: true,
    },
    advFilter: {
      handler() {
        this.dispatchWorkerFilter(0);
        if (this.resetTableScroll) this.resetTableScroll();
      },
      deep: true,
    },
    colSorts: {
      handler() {
        this.dispatchWorkerFilter(0);
      },
      deep: true,
    },
    chartMode() {
      this.dispatchWorkerFilter(0);
    },
  },
};



