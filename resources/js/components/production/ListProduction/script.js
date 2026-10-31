import axios from 'axios';
import { gqlGetProductions, gqlGetProductionAnalytics } from '@/services/appGraphql.js';
import { useMain } from '@/stores/Main';
import { useProductionCategory } from '@/stores/ProductionCategory';
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
  name: 'ListProduction',
  data() {
    const n = new Date(), sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      productions: [], loading: false, selected: [],
      tableModuleKey: 'production',
      moduleColor: '#00897B',
      searchQuery: '',
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
      invForm: { date: null, type_invoice: 'local', invoiceKind: 'supplier', trackingNumbers: [], trackingInput: '' },
      invChecking: false,
      deleteDialog: false, deleting: false, deleteId: null, deleteBulk: false,
      filterMenus: {},
      columnOrder: null, dragCol: null, dragOverCol: null,
      colWidths: {},
      chartMode: 'daily',
      analyticsData: null,
      analyticsLoading: false,
      closedYears: [],
      workerFilteredIds: null,
      workerTotals: null,
      workerCategoryStats: null,
      workerChartBuckets: null,
      workerFilterOptions: {},
      workerFilterOptionCounts: {},
      workerSecondaryPackagingStats: null,
      isWorkerFiltering: false,
      recalculatingPackaging: false,
      summarySnackbar: { show: false, title: '', detail: '', color: 'success' },
      snackbarProgress: 100,
      snackbarTimer: null,
      secPackDisplayMode: (typeof localStorage !== 'undefined' && localStorage.getItem('jupiter_sec_pack_display_mode')) || 'price',
    };
  },
  computed: {
    main() { return useMain() },
    cats() { return useProductionCategory().list },
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
      const items = this.productions || [];
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
        { title: this.$t('production_category'), key: 'production_category_id', align: 'center' },
        { title: this.$t('production_type'), key: 'production_type', align: 'center' },
        { title: this.$t('supplier'), key: '_suppliers', align: 'center' },
        { title: this.$t('production_tracking_number'), key: '_tracking', align: 'center' },
        { title: this.$t('linked_charge'), key: '_charge', align: 'center' },
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
      Object.values(this.colFilters).forEach(vals => { if (vals && vals.length) count++; });
      const af = this.advFilter;
      if (af.warehouses?.length) count++;
      if (af.articleType) count++;
      if (af.fishFamilies?.length) count++;
      if (af.fishFreshFrozen?.length) count++;
      if (af.fishProcessTypes?.length) count++;
      if (af.guanoTypes?.length) count++;
      if (af.packageTypes?.length) count++;
      if (this.searchQuery && this.searchQuery.trim()) count++;
      return count;
    },
    itemsById() {
      const m = new Map();
      const arr = this.productions || [];
      for (let i = 0; i < arr.length; i++) {
        m.set(arr[i].id, arr[i]);
      }
      return m;
    },
    colFilteredItems() {
      if (!this.workerFilteredIds) {
        return this.productions || [];
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
    totalSecondaryPackagingCost() {
      if (this.workerTotals && typeof this.workerTotals.totalSecondaryPackagingCost === 'number') {
        return this.workerTotals.totalSecondaryPackagingCost;
      }
      return this.colFilteredItems.reduce((s, c) => s + Number(c.total_secondary_packaging_cost || 0), 0);
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
    totalInvoiceWeight() {
      if (this.workerTotals && typeof this.workerTotals.totalInvoiceWeight === 'number') {
        return this.workerTotals.totalInvoiceWeight;
      }
      return this.colFilteredItems.filter(c => c._is_invoiced).reduce((s, c) => s + Number(c.total_weight || 0), 0);
    },
    totalInvoicePrice() {
      if (this.workerTotals && typeof this.workerTotals.totalInvoicePrice === 'number') {
        return this.workerTotals.totalInvoicePrice;
      }
      return this.colFilteredItems.filter(c => c._is_invoiced).reduce((s, c) => s + Number(c.total_price || 0), 0);
    },
    totalFreshReception() {
      return (this.analyticsData?.fresh_reception || []).reduce((s, p) => s + (p.y || 0), 0);
    },
    totalFrozenProduction() {
      return (this.analyticsData?.frozen_production || []).reduce((s, p) => s + (p.y || 0), 0);
    },
    totalGuanoProduction() {
      return (this.analyticsData?.guano_production || []).reduce((s, p) => s + (p.y || 0), 0);
    },
    totalEquilibrium() {
      return (this.analyticsData?.equilibrium || []).reduce((s, p) => s + (p.y || 0), 0);
    },
    secondaryPackagingStats() {
      if (this.workerSecondaryPackagingStats) {
        const rawComps = this.workerSecondaryPackagingStats;
        const items = Object.values(rawComps).map(c => ({
          ...c,
          label: this.$t(c.key) || c.label,
          qte: Math.round((c.qte || 0) * 1000) / 1000,
          total_cost: Math.round((c.total_cost || 0) * 100) / 100,
        }));
        const calculatedTotal = items.reduce((sum, i) => sum + i.total_cost, 0);
        return {
          items,
          total_cost: Math.round(calculatedTotal * 100) / 100,
        };
      }
      const comps = {
        caisses: { key: 'caisses', label: this.$t('caisses'), unit: 'U', qte: 0, total_cost: 0 },
        cellophane: { key: 'cellophane', label: this.$t('cellophane'), unit: 'Kg', qte: 0, total_cost: 0 },
        plastique: { key: 'plastique', label: this.$t('plastique'), unit: 'Kg', qte: 0, total_cost: 0 },
        strapex: { key: 'strapex', label: this.$t('strapex'), unit: 'm', qte: 0, total_cost: 0 },
        palletes: { key: 'palletes', label: this.$t('palletes'), unit: 'U', qte: 0, total_cost: 0 },
        scotch: { key: 'scotch', label: this.$t('scotch'), unit: 'Rouleau', qte: 0, total_cost: 0 },
      };

      let directSum = 0;

      this.colFilteredItems.forEach(prod => {
        directSum += Number(prod.total_secondary_packaging_cost || 0);
        const details = prod.detail_productions || [];
        details.forEach(dp => {
          let secDetails = dp.secondary_packaging_details;
          if (typeof secDetails === 'string') {
            try { secDetails = JSON.parse(secDetails); } catch (e) { secDetails = null; }
          }
          if (secDetails) {
            const nbrBox = Number(secDetails.nbr_box || dp.nbr_box || 0);
            if (Array.isArray(secDetails.components) && secDetails.components.length > 0) {
              secDetails.components.forEach(c => {
                const k = c.key || c.name;
                if (!comps[k]) {
                  comps[k] = { key: k, label: c.name || k, unit: c.unit || '', qte: 0, total_cost: 0 };
                }
                if (c.unit && !comps[k].unit) comps[k].unit = c.unit;
                const qte = Number(c.qte || 0);
                const price = Number(c.price || 0);
                const compQte = nbrBox * qte;
                const compCost = compQte * price;
                comps[k].qte += compQte;
                comps[k].total_cost += compCost;
              });
            } else {
              ['caisses', 'cellophane', 'plastique', 'strapex', 'palletes', 'scotch'].forEach(comp => {
                const qte = Number(secDetails[`${comp}_qte`] || 0);
                const price = Number(secDetails[`${comp}_price`] || 0);
                const compQte = nbrBox * qte;
                const compCost = compQte * price;
                comps[comp].qte += compQte;
                comps[comp].total_cost += compCost;
              });
            }
          }
        });
      });

      const items = Object.values(comps).map(c => ({
        ...c,
        qte: Math.round(c.qte * 1000) / 1000,
        total_cost: Math.round(c.total_cost * 100) / 100,
      }));

      const calculatedTotal = items.reduce((sum, i) => sum + i.total_cost, 0);
      const grandTotal = Math.max(Math.round(calculatedTotal * 100) / 100, Math.round(directSum * 100) / 100);

      return {
        items,
        total_cost: grandTotal,
      };
    },
    categoryStats() {
      if (this.workerCategoryStats && this.workerCategoryStats.length) {
        return this.workerCategoryStats;
      }
      const lang = this.main.lang?.code || 'en'; const map = {};
      this.colFilteredItems.forEach(c => { const catId = c.production_category_id; if (!map[catId]) { const cat = this.cats.find(x => x.id === catId); map[catId] = { name: cat ? (cat[`name_${lang}`] || cat.name || `#${catId}`) : `#${catId}`, weight: 0, price: 0, invoiceWeight: 0, invoicePrice: 0, count: 0 } } map[catId].weight += Number(c.total_weight || 0); map[catId].price += Number(c.total_price || 0); if (c._is_invoiced) { map[catId].invoiceWeight += Number(c.total_weight || 0); map[catId].invoicePrice += Number(c.total_price || 0); } map[catId].count++ });
      return Object.values(map);
    },
    chartSeries() {
      const d = this.analyticsData;
      if (!d) return [{ name: '-', data: [] }];
      const barSeries = [
        { name: this.$t('fresh_reception') + ' (Kg)', type: 'bar', data: d.fresh_reception || [] },
        { name: this.$t('frozen_production') + ' (Kg)', type: 'bar', data: d.frozen_production || [] },
        { name: this.$t('guano_production') + ' (Kg)', type: 'bar', data: d.guano_production || [] },
        { name: this.$t('equilibrium') + ' (Kg)', type: 'bar', data: d.equilibrium || [] },
      ];
      const yieldLines = (d.yield_series || []).map(ys => ({
        name: ys.name + ' ' + this.$t('yield') + ' (%)',
        type: 'line',
        data: ys.data || [],
      }));
      return [...barSeries, ...yieldLines];
    },
    chartColors() {
      const d = this.analyticsData;
      const barColors = ['#42A5F5', '#AB47BC', '#8D6E63', '#edcd00ff'];
      const yieldColors = (d?.yield_series || []).map(ys => ys.color || '#FF9800');
      return [...barColors, ...yieldColors];
    },
    chartOptions() {
      const eqData = this.analyticsData?.equilibrium || [];
      const yieldCount = (this.analyticsData?.yield_series || []).length;
      // stroke widths: 0 for bars, 3 for lines
      const strokeWidths = [0, 0, 0, 0, ...Array(yieldCount).fill(3)];
      // Positional yaxis: one per series. All bar axes share the same seriesName for stacking.
      const yieldSeries = this.analyticsData?.yield_series || [];
      const firstBarName = this.$t('fresh_reception') + ' (Kg)';
      const yaxisArr = [
        {
          seriesName: firstBarName,
          title: { text: this.$t('total_weight') + ' (Kg)', style: { fontSize: '11px', fontWeight: 700, color: '#00897B' } },
          labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) },
        },
        { seriesName: firstBarName, show: false, labels: { formatter: v => '' } },
        { seriesName: firstBarName, show: false, labels: { formatter: v => '' } },
        { seriesName: firstBarName, show: false, labels: { formatter: v => '' } },
      ];
      yieldSeries.forEach((ys, i) => {
        yaxisArr.push({
          opposite: true,
          seriesName: ys.name + ' ' + this.$t('yield') + ' (%)',
          show: i === 0,
          title: i === 0 ? { text: this.$t('yield') + ' (%)', style: { fontSize: '11px', fontWeight: 700, color: '#FF9800' } } : undefined,
          labels: { style: { fontSize: '10px' }, formatter: v => Math.round(v) + '%' },
          min: 0,
        });
      });
      return {
        chart: { type: 'line', height: 300, stacked: true, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
        plotOptions: { bar: { columnWidth: '60%', borderRadius: 3, colors: { ranges: [{ from: -999999999, to: -0.001, color: '#EF5350' }] } } },
        stroke: { width: strokeWidths, curve: 'smooth' },
        colors: this.chartColors,
        xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 }, rotate: -45, rotateAlways: this.chartMode === 'daily' }, tooltip: { enabled: false } },
        yaxis: yaxisArr,
        tooltip: {
          shared: true, intersect: false, y: {
            formatter: (v, { seriesIndex, dataPointIndex }) => {
              if (seriesIndex >= 4) return Math.round(v) + '%';
              let txt = this.main.formatDisplayNumber(v) + ' Kg';
              if (seriesIndex === 3 && eqData[dataPointIndex]) {
                txt += ' (' + this.$t(eqData[dataPointIndex].label || 'equilibrium') + ')';
              }
              return txt;
            }
          }
        },
        grid: { borderColor: 'rgba(0, 0, 0, 0.24)', strokeDashArray: 2 },
        legend: { position: 'top', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: false },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
        markers: { size: Array(4).fill(0).concat(Array(yieldCount).fill(4)), hover: { sizeOffset: 2 } },
      };
    },
    selectedProductions() { return this.productions.filter(c => this.selected.includes(c.id)) },
    uninvoicedSelectedProductions() { return this.selectedProductions.filter(c => !c._is_invoiced) },
    invoiceSuppliers() { const s = new Set(); this.uninvoicedSelectedProductions.forEach(c => { if (c.supplier?.name) s.add(c.supplier.name); if (c.client?.name) s.add(c.client.name) }); return [...s] },
    canSetupInvoice() {
      if (!this.uninvoicedSelectedProductions.length) return false;
      // For supplier invoices, all must have the same supplier_id
      if (this.invForm.invoiceKind === 'supplier') {
        const ids = this.uninvoicedSelectedProductions.map(c => c.supplier_id).filter(Boolean);
        if (!ids.length) return false;
        return new Set(ids).size === 1;
      }
      // For client invoices, all must have the same client_id
      const ids = this.uninvoicedSelectedProductions.map(c => c.client_id).filter(Boolean);
      if (!ids.length) return false;
      return new Set(ids).size === 1;
    },
    invMinDate() {
      if (!this.uninvoicedSelectedProductions.length) return null;
      const dates = this.uninvoicedSelectedProductions.map(c => new Date(c.date)).filter(d => !isNaN(d));
      return dates.length ? new Date(Math.min(...dates)) : null;
    },
    invMaxDate() {
      if (!this.uninvoicedSelectedProductions.length) return null;
      const dates = this.uninvoicedSelectedProductions.map(c => new Date(c.date)).filter(d => !isNaN(d));
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
    this.fetchProductions(); this.fetchAnalytics(); this.fetchClosedYears(); mitt.on('model-changed', this.onMC);
  },
  activated() { this.fetchProductions(); this.fetchAnalytics(); this.fetchClosedYears(); },
  beforeUnmount() {
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
    mitt.off('model-changed', this.onMC);
    if (this._listWorker) {
      this._listWorker.terminate();
    }
  },
  watch: {
    secPackDisplayMode(val) {
      try {
        localStorage.setItem('jupiter_sec_pack_display_mode', val);
      } catch (e) {}
    },
    chartMode() {
      this.fetchAnalytics();
      this.dispatchWorkerFilter(0);
    },
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
        this._cachedWorkerItems = JSON.parse(JSON.stringify(this.productions || []));
      }
      this.isWorkerFiltering = true;
      const payload = {
        items: this._cachedWorkerItems,
        moduleType: 'production',
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
          this.workerSecondaryPackagingStats = res.secondaryPackagingStats;
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
      if (d.model === 'Production') {
        const rd = d.record_data;
        // If record_data is empty (AI Assistant bulk operation) or action is 'refresh', do full refetch
        if (d.action === 'refresh' || !rd || (typeof rd === 'object' && Object.keys(rd).length === 0) || (Array.isArray(rd) && rd.length === 0)) {
          this.fetchProductions(); this.fetchAnalytics();
          return;
        }
        if ((d.action === 'created' || d.action === 'updated') && rd && rd.date) {
          const prodDate = typeof rd.date === 'string' ? rd.date.slice(0, 10) : rd.date;
          const ds = this.fmtDate(this.dateRange[0]);
          const de = this.fmtDate(this.dateRange[1] || this.dateRange[0]);
          if (prodDate >= ds && prodDate <= de) {
            const mapped = this.mapProduction(rd);
            const idx = this.productions.findIndex(c => c.id === rd.id);
            if (idx !== -1) { this.productions.splice(idx, 1, mapped); }
            else { this.productions.unshift(mapped); }
          } else {
            this.productions = this.productions.filter(c => c.id !== rd.id);
          }
          this._cachedWorkerItems = JSON.parse(JSON.stringify(this.productions));
          this.dispatchWorkerFilter(0);
          this.fetchAnalytics();
        } else if (d.action === 'deleted' && d.record_id) {
          this.productions = this.productions.filter(c => c.id !== d.record_id);
          this._cachedWorkerItems = JSON.parse(JSON.stringify(this.productions));
          this.dispatchWorkerFilter(0);
          this.fetchAnalytics();
        }
      } else if (d.model === 'SupplierInvoice' || d.model === 'ClientInvoice' || d.model === 'PackageSecondaryConfig') {
        this.fetchProductions(); this.fetchAnalytics();
        if (d.model === 'PackageSecondaryConfig') {
          this.triggerSummarySnackbar(
            this.$t('realtime_sync'),
            this.$t('secondary_packaging_config') + (d.actor_name ? ` (${d.actor_name})` : ''),
            'info'
          );
        }
      } else if (d.model === 'RecordIssue') {
        this._fetchIssueCounts('Production');
      }
      if (d.model === 'FiscalYearClosing') this.fetchClosedYears();
    },
    mapProduction(c) {
      return {
        ...c,
        _suppliers: c.supplier?.name || c.client?.name || '-',
        _tracking: (c.production_tracking_number || []).join(', ') || '-',
        _charge: c.charge ? `#${c.charge.id} - ${c.charge.date ? c.charge.date.slice(0, 10) : ''}` : ' -',
        _invoice_state: c.supplier_invoice
          ? (c.supplier_invoice.tracking_numbers || []).join(', ')
          : (c.client_invoice ? (c.client_invoice.tracking_numbers || []).join(', ') : null),
        _invoice_type: c.supplier_invoice_id ? 'supplier' : (c.client_invoice_id ? 'client' : null),
        _is_invoiced: !!c.supplier_invoice_id || !!c.client_invoice_id,
      };
    },
    onDateRangeChange(val) { if (val && val.start && val.end) { this.dateRange = [val.start, val.end]; this.fetchProductions(); this.fetchAnalytics() } },
    async fetchAnalytics() {
      this.analyticsLoading = true;
      try {
        const ds = this.dateRange[0], de = this.dateRange[1] || this.dateRange[0];
        const date_start = this.fmtDate(ds), date_end = this.fmtDate(de);
        try {
          const analytics = await gqlGetProductionAnalytics({ date_start, date_end, mode: this.chartMode });
          if (analytics) {
            this.analyticsData = analytics;
          } else {
            throw new Error('GraphQL returned null');
          }
        } catch (gqlErr) {
          console.warn('[ListProduction] GraphQL analytics failed, falling back to REST:', gqlErr);
          const r = await axios.get('/api/productions/analytics', { params: { date_start, date_end, mode: this.chartMode } });
          this.analyticsData = r.data;
        }
      } catch (e) { console.error(e); this.analyticsData = null }
      this.analyticsLoading = false;
    },
    async fetchClosedYears() {
      try {
        const r = await axios.get('/api/fiscal_year_closings');
        this.closedYears = (r.data.closings || []).filter(c => c.is_closed).map(c => c.year);
      } catch (e) { console.error(e) }
    },
    async fetchProductions() {
      this.loading = true;
      try {
        const ds = this.dateRange[0], de = this.dateRange[1] || this.dateRange[0];
        const date_start = this.fmtDate(ds), date_end = this.fmtDate(de);
        let prods = [];
        try {
          prods = await gqlGetProductions({ date_start, date_end });
        } catch (gqlErr) {
          console.warn('[ListProduction] GraphQL fetch failed, falling back to REST:', gqlErr);
          const r = await axios.get('/api/productions', { params: { date_start, date_end } });
          prods = r.data.productions || [];
        }
        this.productions = (prods || []).map(c => this.mapProduction(c));
        this._cachedWorkerItems = JSON.parse(JSON.stringify(this.productions));
        this.dispatchWorkerFilter(0);
        // Fetch open issue counts and merge
        this._fetchIssueCounts('Production');
      } catch (e) { console.error(e) }
      this.loading = false;
    },
    async _fetchIssueCounts(type) {
      try {
        const ids = this.productions.map(c => c.id);
        if (!ids.length) return;
        const r = await axios.post('/api/record-issues/batch-counts', { record_type: type, record_ids: ids });
        const counts = r.data.counts || {};
        this.productions = this.productions.map(c => ({ ...c, _open_issues: counts[c.id] || 0 }));
      } catch (e) { /* silent */ }
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; const y = d.getFullYear(); const m = String(d.getMonth() + 1).padStart(2, '0'); const day = String(d.getDate()).padStart(2, '0'); return `${y}-${m}-${day}` },
    cellText(key, item) {
      if (key === 'production_category_id') return this.catName(item.production_category_id);
      if (key === 'date') return this.main.formateDate(item.date);
      if (key === 'production_type') return item.production_type ? this.$t(item.production_type) : '-';
      const v = item[key]; return v == null ? '-' : String(v);
    },
    productionTypeColor(type) {
      if (!type) return 'grey';
      if (type === 'intern' || type === 'standard') return '#00897B';
      if (type === 'extern' || type === 'sous_traitance') return '#FF9800';
      if (type === 'guano') return '#388E3C';
      return '#00897B';
    },
    filterOpts(key) {
      if (this.workerFilterOptions && this.workerFilterOptions[key] && this.workerFilterOptions[key].length) {
        return this.workerFilterOptions[key];
      }
      // Compute from full dataset so options NEVER vanish when any filter is selected
      const s = new Set();
      this.productions.forEach(i => {
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
      this.productions.forEach(i => {
        const v = this.cellText(key, i);
        if (v && v !== '-') counts[v] = (counts[v] || 0) + 1;
      });
      return counts;
    },
    onColFilterChange() {
      this.dispatchWorkerFilter(0);
    },
    sortItems(items) { if (!this.colSorts || !this.colSorts.length) return items; return [...items].sort((a, b) => { for (const { key, dir } of this.colSorts) { let va = key === 'production_category_id' ? this.catName(a[key]) : a[key], vb = key === 'production_category_id' ? this.catName(b[key]) : b[key]; if (va == null) va = ''; if (vb == null) vb = ''; const n = typeof va === 'number' && typeof vb === 'number'; if (n) { if (va !== vb) return dir === 'asc' ? va - vb : vb - va } else { const c = String(va).localeCompare(String(vb)); if (c) return dir === 'asc' ? c : -c } } return 0 }) },
    groupDetails(prod) { const groups = {}; (prod.detail_productions || []).forEach(dc => { const catId = dc.detail_production_category_id; if (!groups[catId]) groups[catId] = { category: dc.detail_production_category, items: [], totalW: 0, totalP: 0 }; groups[catId].items.push(dc); groups[catId].totalW += Number(dc.weight || 0); groups[catId].totalP += Number(dc.total_price || 0) }); return Object.values(groups) },
    articleName(dc) { if (!dc.stockable_type || dc.stockable_type === 'text') return dc.article_description || '-'; const s = dc.stockable; return s ? (s.name || s[`name_${this.main.lang?.code}`] || '-') : '-' },
    articleFamily(dc) { if ((dc.stockable_type === 'FishType' || dc.stockable_type?.includes('FishType')) && dc.stockable) { const fam = this.fishFamilies.find(f => f.id === dc.stockable.fish_family_id); return fam ? (fam[`name_${this.main.lang?.code}`] || fam.name || '-') : '-' } return '-' },
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
    async execDelete() { this.deleting = true; try { const ids = this.deleteBulk ? this.selected : [this.deleteId]; await axios.delete('/api/productions', { data: { ids } }); this.main.add_snackbar({ show: true, message: this.$t('productions_deleted_successfully'), color: 'success' }); this.selected = []; this.deleteDialog = false; this.fetchProductions() } catch (e) { console.error(e) } this.deleting = false },
    getInvoiceYear() {
      if (this.invForm.date) {
        const d = typeof this.invForm.date === 'string' ? new Date(this.invForm.date) : this.invForm.date;
        if (!isNaN(d)) return String(d.getFullYear()).slice(-2);
      }
      return String(new Date().getFullYear()).slice(-2);
    },
    getProductionCategoryId() {
      if (!this.uninvoicedSelectedProductions.length) return null;
      const ids = [...new Set(this.uninvoicedSelectedProductions.map(c => c.production_category_id))];
      return ids.length === 1 ? ids[0] : ids[0];
    },
    async fetchNextTracking(type) {
      try {
        const params = { type: type || 'local' };
        const catId = this.getProductionCategoryId();
        if (catId) params.production_category_id = catId;
        const dateStr = this.invForm.date ? this.fmtDate(this.invForm.date) : null;
        if (dateStr) params.date = dateStr;
        const ep = this.invForm.invoiceKind === 'supplier' ? '/api/supplier_invoice/next-tracking-number' : '/api/client_invoice/next-tracking-number';
        const r = await axios.get(ep, { params });
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
      const ep = this.invForm.invoiceKind === 'supplier' ? '/api/supplier_invoice/check-tracking-number' : '/api/client_invoice/check-tracking-number';
      try {
        const params = { tracking_number: v, type_invoice: type };
        const catId = this.getProductionCategoryId();
        if (catId) params.production_category_id = catId;
        const r = await axios.get(ep, { params });
        if (r.data.exists) { this.main.add_snackbar({ show: true, message: this.$t('tracking_number_exists_in_invoice'), color: 'warning' }) }
        else { this.invForm.trackingNumbers.push(v) }
      } catch (e) { console.error(e) }
      this.invChecking = false;
    },
    async submitInvoice() {
      const uninvoicedIds = this.uninvoicedSelectedProductions.map(c => c.id);
      if (!uninvoicedIds.length || !this.invForm.trackingNumbers.length) return;
      if (this.invForm.invoiceKind === 'supplier') {
        const supplierId = this.uninvoicedSelectedProductions[0]?.supplier_id;
        try {
          await axios.post('/api/supplier_invoice', { date: this.fmtDate(this.invForm.date) || this.fmtDate(new Date()), tracking_numbers: this.invForm.trackingNumbers, production_ids: uninvoicedIds, supplier_id: supplierId, type_invoice: this.invForm.type_invoice || 'local' });
          this.main.add_snackbar({ show: true, message: this.$t('invoice_created_successfully'), color: 'success' });
          this.cancelInvoice(); this.fetchProductions();
        } catch (e) { console.error(e); this.main.add_snackbar({ show: true, message: this.$t('error'), color: 'error' }) }
      } else {
        const clientId = this.uninvoicedSelectedProductions[0]?.client_id;
        try {
          await axios.post('/api/client_invoice', { date: this.fmtDate(this.invForm.date) || this.fmtDate(new Date()), type_invoice: this.invForm.type_invoice || 'local', tracking_numbers: this.invForm.trackingNumbers, production_ids: uninvoicedIds, client_id: clientId });
          this.main.add_snackbar({ show: true, message: this.$t('invoice_created_successfully'), color: 'success' });
          this.cancelInvoice(); this.fetchProductions();
        } catch (e) { console.error(e); this.main.add_snackbar({ show: true, message: this.$t('error'), color: 'error' }) }
      }
    },
    clearAllFilters() {
      this.colFilters = {};
      this.advFilter = { articleType: null, fishFamilies: [], fishFreshFrozen: [], fishProcessTypes: [], guanoTypes: [], packageTypes: [], warehouses: [] };
      this.searchQuery = '';
      this.dispatchWorkerFilter(0);
      if (this.resetTableScroll) this.resetTableScroll();
    },
    cancelInvoice() { this.selected = []; this.invForm = { date: null, type_invoice: 'local', trackingNumbers: [], trackingInput: '', invoiceKind: 'supplier' }; this.showInvoice = false; },
    exportExcel() { const data = this.colFilteredItems.map(c => { const row = {}; this.headers.filter(h => h.key !== 'actions' && h.key !== 'expand').forEach(h => { row[h.title] = this.cellText(h.key, c) }); return row }); const ws = XLSX.utils.json_to_sheet(data); const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Productions'); XLSX.writeFile(wb, `productions_${this.today}.xlsx`) },
    triggerSummarySnackbar(title, detail = '', color = 'success') {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, title, detail, color };
      this.snackbarProgress = 100;
      const duration = 5000;
      const interval = 50;
      const step = (interval / duration) * 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= step;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, interval);
    },
    async updatePackageSecondaryPackaging() {
      this.recalculatingPackaging = true;
      try {
        const ds = this.fmtDate(this.dateRange[0]);
        const de = this.fmtDate(this.dateRange[1] || this.dateRange[0]);
        const res = await axios.post('/api/productions/recalculate-secondary-packaging', {
          date_start: ds,
          date_end: de,
        });
        const result = res.data?.result || {};
        const count = result.productions_count ?? 0;
        const totalCost = this.main.formatDisplayNumber(result.total_secondary_packaging_cost ?? 0);
        await Promise.all([this.fetchProductions(), this.fetchAnalytics()]);
        this.triggerSummarySnackbar(
          this.$t('secondary_packaging_recalculated_success'),
          `${count} ${this.$t('productions_updated')} • ${totalCost} ${this.main.currency}`,
          'success'
        );
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(
          this.$t('error_occurred'),
          e.response?.data?.message || 'Recalculation failed',
          'error'
        );
      } finally {
        this.recalculatingPackaging = false;
      }
    },
    getPackagingIcon(key) {
      const icons = {
        caisses: 'mdi-package-variant-closed',
        cellophane: 'mdi-layers-triple-outline',
        plastique: 'mdi-shopping-outline',
        strapex: 'mdi-tape-measure',
        palletes: 'mdi-pallet',
        scotch: 'mdi-sticker-circle-outline',
      };
      return icons[key] || 'mdi-package-variant-closed';
    },
  },
};
