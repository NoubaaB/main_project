import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useChargeCategory } from '@/stores/ChargeCategory';
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
  name: 'ListCharge',
  data() {
    const n = new Date(), sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      charges: [], loading: false, selected: [],
      tableModuleKey: 'charge',
      moduleColor: '#42A5F5',
      dateRange: [sm, n], dateMenu: false,
      colFilters: {}, colSorts: [], pinState: {},
      expanded: {},
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
      invForm: { date: null, type_invoice: 'local', trackingNumbers: [], trackingInput: '' },
      invChecking: false,
      deleteDialog: false, deleting: false, deleteId: null, deleteBulk: false,
      filterMenus: {},
      columnOrder: null, // will be initialized from allHeaders keys
      dragCol: null, dragOverCol: null,
      colWidths: {},
      chartMode: 'daily',
      closedYears: [],
      searchQuery: '',
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
    cats() { return useChargeCategory().list },
    fishFamilies() { return useFishFamily().list },
    fishTypes() { return useFishType().list },
    guanoTypes() { return useGuanoType().list },
    packageTypes() { return usePackageType().list },
    warehouses() { return useWarehouse().list },
    fishProcessTypes() { return useFishProcessType().list },
    today() { return new Date().toISOString().slice(0, 10) },
    dateRangeVC: { get() { return { start: this.dateRange[0], end: this.dateRange[1] || this.dateRange[0] } }, set(v) { if (v && v.start && v.end) this.dateRange = [v.start, v.end] } },
    catName() { return (id) => { const l = this.main.lang?.code || 'en'; const c = this.cats.find(x => x.id === id); return c ? (c[`name_${l}`] || c.name || `#${id}`) : `#${id}` } },
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
      const items = this.charges || [];
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
    allHeaders() {
      return [
        { title: '', key: 'expand', sortable: false, width: 40, fixed: true },
        { title: this.$t('date'), key: 'date', align: 'center' },
        { title: this.$t('charge_category'), key: 'charge_category_id', align: 'center' },
        { title: this.$t('supplier'), key: '_suppliers', align: 'center' },
        { title: this.$t('reception_tracking_number'), key: '_tracking', align: 'center' },
        { title: this.$t('tracking_onp_numbers'), key: '_onp_tracking', align: 'center' },
        { title: this.$t('fishery'), key: '_fishery', align: 'center' },
        { title: this.$t('origin'), key: '_origin', align: 'center' },
        { title: this.$t('ship'), key: '_ship', align: 'center' },
        { title: this.$t('trucks'), key: '_trucks', align: 'center' },
        { title: this.$t('total_weight'), key: 'total_weight', align: 'center' },
        { title: this.$t('total_weight_invoice'), key: '_total_weight_invoice', align: 'center' },
        { title: this.$t('total_price'), key: 'total_price', align: 'center' },
        { title: this.$t('invoice_state'), key: '_invoice_state', align: 'center' },
        { title: this.$t('quality_control'), key: '_qc_status', align: 'center' },
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
    itemsById() {
      const m = new Map();
      const arr = this.charges || [];
      for (let i = 0; i < arr.length; i++) {
        m.set(arr[i].id, arr[i]);
      }
      return m;
    },
    colFilteredItems() {
      if (!this.workerFilteredIds) {
        return this.charges || [];
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
    totalWeightInvoice() {
      if (this.workerTotals && typeof this.workerTotals.totalWeightInvoice === 'number') {
        return this.workerTotals.totalWeightInvoice;
      }
      return this.colFilteredItems.reduce((s, c) => s + Number(c._total_weight_invoice || 0), 0);
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
    totalNotInvoiced() {
      if (this.workerTotals && typeof this.workerTotals.totalNotInvoiced === 'number') {
        return this.workerTotals.totalNotInvoiced;
      }
      return this.colFilteredItems.filter(c => !c._is_invoiced).length;
    },
    categoryStats() {
      if (this.workerCategoryStats && this.workerCategoryStats.length) {
        return this.workerCategoryStats;
      }
      const lang = this.main.lang?.code || 'en';
      const map = {};
      this.colFilteredItems.forEach(c => {
        const catId = c.charge_category_id;
        if (!map[catId]) {
          const cat = this.cats.find(x => x.id === catId);
          map[catId] = { name: cat ? (cat[`name_${lang}`] || cat.name || `#${catId}`) : `#${catId}`, weight: 0, price: 0, weightInvoice: 0, count: 0 };
        }
        map[catId].weight += Number(c.total_weight || 0);
        map[catId].price += Number(c.total_price || 0);
        map[catId].weightInvoice += Number(c._total_weight_invoice || 0);
        map[catId].count++;
      });
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
      return [{ name: this.$t('total_weight') + ' (Kg)', type: 'area', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].w * 100) / 100 })) }, { name: this.$t('total_price') + ' (' + this.main.currency + ')', type: 'line', data: sorted.map(k => ({ x: k, y: Math.round(buckets[k].p * 100) / 100 })) }];
    },
    chartOptions() {
      return {
        chart: { type: 'line', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
        stroke: { width: [2, 3], curve: 'smooth', dashArray: [0, 0] },
        fill: { type: ['gradient', 'solid'], gradient: { shadeIntensity: 1, opacityFrom: .4, opacityTo: .05, stops: [0, 90, 100] } },
        colors: ['#42A5F5', '#FF7043'],
        xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 }, rotate: -45, rotateAlways: this.chartMode === 'daily' }, tooltip: { enabled: false } },
        yaxis: [{ title: { text: this.$t('total_weight') + ' (Kg)', style: { fontSize: '11px', fontWeight: 700, color: '#42A5F5' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } }, { opposite: true, title: { text: this.$t('total_price') + ' (' + this.main.currency + ')', style: { fontSize: '11px', fontWeight: 700, color: '#FF7043' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } }],
        tooltip: { shared: true, intersect: false, y: { formatter: v => this.main.formatDisplayNumber(v) } },
        grid: { borderColor: 'rgba(0,0,0,.06)', strokeDashArray: 4 },
        legend: { position: 'top', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: false },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
    selectedCharges() { return this.charges.filter(c => this.selected.includes(c.id)) },
    uninvoicedSelectedCharges() { return this.selectedCharges.filter(c => !c._is_invoiced) },
    invoiceSuppliers() {
      const s = new Set();
      this.uninvoicedSelectedCharges.forEach(c => { if (c.supplier?.name) s.add(c.supplier.name) });
      return [...s];
    },
    canSetupInvoice() {
      if (!this.uninvoicedSelectedCharges.length) return false;
      const ids = this.uninvoicedSelectedCharges.map(c => c.supplier_id).filter(Boolean);
      if (!ids.length) return false;
      return new Set(ids).size === 1;
    },
    invMinDate() {
      if (!this.uninvoicedSelectedCharges.length) return null;
      const dates = this.uninvoicedSelectedCharges.map(c => new Date(c.date)).filter(d => !isNaN(d));
      return dates.length ? new Date(Math.min(...dates)) : null;
    },
    invMaxDate() {
      if (!this.uninvoicedSelectedCharges.length) return null;
      const dates = this.uninvoicedSelectedCharges.map(c => new Date(c.date)).filter(d => !isNaN(d));
      return dates.length ? new Date(Math.max(...dates)) : null;
    },
    invDisabledDates() {
      const rules = [];
      if (this.invMinDate) rules.push({ start: null, end: new Date(this.invMinDate.getTime() - 86400000) });
      if (this.invMaxDate) rules.push({ start: new Date(this.invMaxDate.getTime() + 86400000), end: null });
      // Disable all dates in closed fiscal years
      this.closedYears.forEach(y => {
        rules.push({ start: new Date(y, 0, 1), end: new Date(y, 11, 31) });
      });
      return rules.length ? rules : undefined;
    },
  },
  mounted() {
    this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
    if (this.loadTableConfig) this.loadTableConfig();
    this.fetchCharges(); this.fetchClosedYears(); mitt.on('model-changed', this.onMC);
  },
  activated() { this.fetchCharges(); this.fetchClosedYears(); },
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
        this._cachedWorkerItems = JSON.parse(JSON.stringify(this.charges || []));
      }
      this.isWorkerFiltering = true;
      const payload = {
        items: this._cachedWorkerItems,
        moduleType: 'charge',
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
      if (d.model === 'Charge') {
        const rd = d.record_data;
        // If record_data is empty (AI Assistant bulk operation) or action is 'refresh', do full refetch
        if (d.action === 'refresh' || !rd || (typeof rd === 'object' && Object.keys(rd).length === 0) || (Array.isArray(rd) && rd.length === 0)) {
          this.fetchCharges();
          return;
        }
        if ((d.action === 'created' || d.action === 'updated') && rd && rd.date) {
          const chargeDate = typeof rd.date === 'string' ? rd.date.slice(0, 10) : rd.date;
          const ds = this.fmtDate(this.dateRange[0]);
          const de = this.fmtDate(this.dateRange[1] || this.dateRange[0]);
          if (chargeDate >= ds && chargeDate <= de) {
            const mapped = this.mapCharge(rd);
            const idx = this.charges.findIndex(c => c.id === rd.id);
            if (idx !== -1) { this.charges.splice(idx, 1, mapped); }
            else { this.charges.unshift(mapped); }
          } else {
            // Date outside range — remove if it existed
            this.charges = this.charges.filter(c => c.id !== rd.id);
          }
          this._cachedWorkerItems = JSON.parse(JSON.stringify(this.charges));
          this.dispatchWorkerFilter(0);
        } else if (d.action === 'deleted' && d.record_id) {
          this.charges = this.charges.filter(c => c.id !== d.record_id);
          this._cachedWorkerItems = JSON.parse(JSON.stringify(this.charges));
          this.dispatchWorkerFilter(0);
        }
      } else if (d.model === 'SupplierInvoice') {
        this.fetchCharges();
      } else if (d.model === 'QualityControl') {
        this.fetchCharges();
      } else if (d.model === 'RecordIssue') {
        this._fetchIssueCounts('Charge');
      }
      if (d.model === 'FiscalYearClosing') this.fetchClosedYears();
    },
    mapCharge(c) {
      const lang = this.main.lang?.code || 'en';
      return {
        ...c,
        _suppliers: c.supplier?.name || '-',
        _tracking: (c.reception_tracking_number || []).join(', ') || '-',
        _onp_tracking: (c.tracking_onp_numbers || []).join(', ') || '-',
        _fishery: c.fishery ? (c.fishery[`name_${lang}`] || c.fishery.name || '-') : '-',
        _origin: c.origin ? (c.origin[`name_${lang}`] || c.origin.name || '-') : '-',
        _ship: c.ship ? (c.ship[`name_${lang}`] || c.ship.name || '-') : '-',
        _trucks: (c.trucks || []).map(t => t.name || t[`name_${lang}`]).join(', ') || '-',
        _total_weight_invoice: (c.detail_charges || []).reduce((s, dc) => s + Number(dc.weight || 0), 0),
        _invoice_state: c.supplier_invoice ? (c.supplier_invoice.tracking_numbers || []).join(', ') : null,
        _is_invoiced: !!c.invoice_id,
        _qc_status: c.quality_control ? c.quality_control.status : null,
        _qc: c.quality_control || null,
      };
    },
    onDateRangeChange(val) { if (val && val.start && val.end) { this.dateRange = [val.start, val.end]; this.fetchCharges() } },
    async fetchClosedYears() {
      try {
        const r = await axios.get('/api/fiscal_year_closings');
        this.closedYears = (r.data.closings || []).filter(c => c.is_closed).map(c => c.year);
      } catch (e) { console.error(e) }
    },
    async fetchCharges() {
      this.loading = true;
      try {
        const ds = this.dateRange[0], de = this.dateRange[1] || this.dateRange[0];
        const p = { date_start: this.fmtDate(ds), date_end: this.fmtDate(de) };
        const r = await axios.get('/api/charges/get_charges', { params: p });
        this.charges = (r.data.charges || []).map(c => this.mapCharge(c));
        this._cachedWorkerItems = JSON.parse(JSON.stringify(this.charges));
        this.dispatchWorkerFilter(0);
        // Fetch open issue counts and merge
        this._fetchIssueCounts('Charge');
      } catch (e) { console.error(e) }
      this.loading = false;
    },
    async _fetchIssueCounts(type) {
      try {
        const ids = this.charges.map(c => c.id);
        if (!ids.length) return;
        const r = await axios.post('/api/record-issues/batch-counts', { record_type: type, record_ids: ids });
        const counts = r.data.counts || {};
        this.charges = this.charges.map(c => ({ ...c, _open_issues: counts[c.id] || 0 }));
      } catch (e) { /* silent */ }
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; const y = d.getFullYear(); const m = String(d.getMonth() + 1).padStart(2, '0'); const day = String(d.getDate()).padStart(2, '0'); return `${y}-${m}-${day}` },
    cellText(key, item) {
      if (key === 'charge_category_id') return this.catName(item.charge_category_id);
      if (key === 'date') return this.main.formateDate(item.date);
      const v = item[key]; return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      if (this.workerFilterOptions && this.workerFilterOptions[key] && this.workerFilterOptions[key].length) {
        return this.workerFilterOptions[key];
      }
      // Compute from full dataset so options NEVER vanish when any filter is selected
      const s = new Set();
      this.charges.forEach(i => {
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
      this.charges.forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') counts[v] = (counts[v] || 0) + 1;
      });
      return counts;
    },
    onColFilterChange() {
      this.dispatchWorkerFilter(0);
    },
    sortItems(items) {
      if (!this.colSorts || !this.colSorts.length) return items;
      return [...items].sort((a, b) => { for (const { key, dir } of this.colSorts) { let va = key === 'charge_category_id' ? this.catName(a[key]) : a[key], vb = key === 'charge_category_id' ? this.catName(b[key]) : b[key]; if (va == null) va = ''; if (vb == null) vb = ''; const n = typeof va === 'number' && typeof vb === 'number'; if (n) { if (va !== vb) return dir === 'asc' ? va - vb : vb - va } else { const c = String(va).localeCompare(String(vb)); if (c) return dir === 'asc' ? c : -c } } return 0 });
    },
    groupDetails(charge) {
      const groups = {};
      (charge.detail_charges || []).forEach(dc => {
        const catId = dc.detail_charge_category_id;
        if (!groups[catId]) groups[catId] = { category: dc.detail_charge_category, items: [], totalW: 0, totalP: 0, totalWI: 0 };
        groups[catId].items.push(dc);
        groups[catId].totalW += Number(dc.weight || 0);
        groups[catId].totalP += Number(dc.total_price || 0);
        groups[catId].totalWI += Number(dc.weight_invoice || 0);
      });
      return Object.values(groups);
    },
    articleName(dc) {
      if (!dc.stockable_type || dc.stockable_type === 'text') return dc.article_description || '-';
      const s = dc.stockable; return s ? (s.name || s[`name_${this.main.lang?.code}`] || '-') : '-';
    },
    articleFamily(dc) {
      if ((dc.stockable_type === 'FishType' || dc.stockable_type?.includes('FishType')) && dc.stockable) {
        const fam = this.fishFamilies.find(f => f.id === dc.stockable.fish_family_id);
        return fam ? (fam[`name_${this.main.lang?.code}`] || fam.name || '-') : '-';
      }
      return '-';
    },
    catHasProduction(charge) { const cat = this.cats.find(c => c.id === charge.charge_category_id); return cat && cat.is_production },
    catHasQualityControl(charge) { const cat = this.cats.find(c => c.id === charge.charge_category_id); return cat && cat.enable_quality_control },
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
    async execDelete() {
      this.deleting = true;
      try {
        const ids = this.deleteBulk ? this.selected : [this.deleteId];
        await axios.delete('/api/charge', { data: { ids } });
        this.main.add_snackbar({ show: true, message: this.$t('charges_deleted_successfully'), color: 'success' });
        this.selected = []; this.deleteDialog = false; this.fetchCharges();
      } catch (e) { console.error(e) }
      this.deleting = false;
    },
    getInvoiceYear() {
      if (this.invForm.date) {
        const d = typeof this.invForm.date === 'string' ? new Date(this.invForm.date) : this.invForm.date;
        if (!isNaN(d)) return String(d.getFullYear()).slice(-2);
      }
      return String(new Date().getFullYear()).slice(-2);
    },
    getChargeCategoryId() {
      if (!this.uninvoicedSelectedCharges.length) return null;
      const ids = [...new Set(this.uninvoicedSelectedCharges.map(c => c.charge_category_id))];
      return ids.length === 1 ? ids[0] : ids[0];
    },
    async fetchNextTracking(type) {
      try {
        const params = { type: type || 'local' };
        const catId = this.getChargeCategoryId();
        if (catId) params.charge_category_id = catId;
        const dateStr = this.invForm.date ? this.fmtDate(this.invForm.date) : null;
        if (dateStr) params.date = dateStr;
        const r = await axios.get('/api/supplier_invoice/next-tracking-number', { params });
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
      // Replace raw with formatted
      this.invForm.trackingNumbers.splice(this.invForm.trackingNumbers.length - 1, 1);
      if (this.invForm.trackingNumbers.includes(v)) { this.main.add_snackbar({ show: true, message: this.$t('tracking_number_exists_in_invoice'), color: 'warning' }); return; }
      this.invChecking = true;
      try {
        const params = { tracking_number: v, type_invoice: type };
        const catId = this.getChargeCategoryId();
        if (catId) params.charge_category_id = catId;
        const r = await axios.get('/api/supplier_invoice/check-tracking-number', { params });
        if (r.data.exists) { this.main.add_snackbar({ show: true, message: this.$t('tracking_number_exists_in_invoice'), color: 'warning' }) }
        else { this.invForm.trackingNumbers.push(v) }
      } catch (e) { console.error(e) }
      this.invChecking = false;
    },
    async submitInvoice() {
      const uninvoicedIds = this.uninvoicedSelectedCharges.map(c => c.id);
      if (!uninvoicedIds.length || !this.invForm.trackingNumbers.length) return;
      const supplierId = this.uninvoicedSelectedCharges[0]?.supplier_id;
      try {
        await axios.post('/api/supplier_invoice', { date: this.fmtDate(this.invForm.date) || this.fmtDate(new Date()), type_invoice: this.invForm.type_invoice || 'local', tracking_numbers: this.invForm.trackingNumbers, charge_ids: uninvoicedIds, supplier_id: supplierId });
        this.main.add_snackbar({ show: true, message: this.$t('invoice_created_successfully'), color: 'success' });
        this.invForm = { date: null, type_invoice: 'local', trackingNumbers: [], trackingInput: '' };
        this.showInvoice = false; this.fetchCharges();
      } catch (e) { console.error(e); this.main.add_snackbar({ show: true, message: this.$t('error'), color: 'error' }) }
    },
    cancelInvoice() {
      this.selected = []; this.invForm = { date: null, type_invoice: 'local', trackingNumbers: [], trackingInput: '' }; this.showInvoice = false;
    },
    exportExcel() {
      const data = this.colFilteredItems.map(c => { const row = {}; this.headers.filter(h => h.key !== 'actions' && h.key !== 'expand').forEach(h => { row[h.title] = this.cellText(h.key, c) }); return row });
      const ws = XLSX.utils.json_to_sheet(data); const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Charges'); XLSX.writeFile(wb, `charges_${this.today}.xlsx`);
    },
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
