import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import mitt from '@/plugins/mitt.js';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';

export default {
  name: 'WarehouseDashboard',
  components: {
    SegmentedPillToggle,
  },
  data() {
    const today = new Date();
    const d30 = new Date(Date.now() - 30 * 86400000);
    return {
      warehouses: [],
      allWarehouses: [],
      selectedWarehouseIds: [],
      loading: false,
      fetchLoading: false,
      _refreshTimer: null,

      // View Mode: 'sub_element' (by Family/Category), 'phase' (by Factory Phase), 'master_list' (unified table), 'drilldown' (step-by-step)
      activeViewMode: 'sub_element',

      // Search & Filters
      searchQuery: '',
      filterPhase: null,
      filterType: null,
      filterFamily: null,
      filterStatus: 'in_stock', // 'all', 'in_stock', 'depleted'

      // Accordion expansions for sub-elements and phases
      expandedSubElements: {},
      expandedPhases: { Production: true, Charge: true, WarehouseTransfer: true, Inventory: true },
      expandedBalances: {},

      // Master List Pagination & Sorting
      tablePage: 1,
      tableItemsPerPage: 25,
      tableSortKey: 'date',
      tableSortDir: 'desc',

      // Step-by-step drilldown state
      drillLevel: 0,
      drillType: null,
      drillFamilyId: null,
      drillFamilyName: null,
      drillFishNameId: null,
      drillFishName: null,
      colFilters: {},
      l2FilterMenus: {},

      // Raw balances from backend
      balances: [],

      // Date range (VCalendar range object)
      dateRangeVC: { start: d30, end: today },
      dashChartMode: 'daily',

      // Chart dialog
      chartDialog: false,
      chartMode: 'pie', // 'pie' or 'line'
      chartTitle: '',
      pieSeries: [],
      pieLabels: [],
      pieColors: [],
      lineSeries: [],
      lineCategories: [],

      // Rule 5: Summary Snackbar with Countdown Progress Bar
      summarySnackbar: {
        show: false,
        title: '',
        message: '',
        icon: 'mdi-check-circle',
        color: '#a21ee5',
        details: null,
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },

    viewModeOptions() {
      return [
        {
          value: 'sub_element',
          label: this.$t('view_by_sub_element') || 'Par Sous-Élément',
          icon: 'mdi-shape-outline',
          badge: this.subElementsGrouped.length || null,
        },
        {
          value: 'phase',
          label: this.$t('view_by_phase') || 'Par Phase d\'Usine',
          icon: 'mdi-factory',
          badge: this.phasesGrouped.filter(p => p.articles.length > 0).length || null,
        },
        {
          value: 'master_list',
          label: this.$t('view_master_list') || 'Tableau Maître',
          icon: 'mdi-table-large',
          badge: this.filteredBalancesList.length || null,
        },
        {
          value: 'drilldown',
          label: this.$t('view_drilldown') || 'Navigation Pas-à-Pas',
          icon: 'mdi-file-tree-outline',
        },
      ];
    },

    // Total warehouses count and warehouses having non-zero stock
    warehousesWithStockList() {
      return this.allWarehouses.filter(w => Number(w.total_stock || w.total_weight || 0) > 0);
    },

    isProductionEffectWarehouse() {
      if (!this.selectedWarehouseIds || !this.selectedWarehouseIds.length) return false;
      const allW = (this.warehouses && this.warehouses.length) ? this.warehouses : this.allWarehouses;
      const selectedW = allW.filter(w => this.selectedWarehouseIds.includes(w.id || w.value));
      return selectedW.length > 0 && selectedW.some(w => !!w.allow_production_effect);
    },

    dateFrom() {
      const d = this.dateRangeVC?.start;
      if (!d) return '';
      if (typeof d === 'string') return d.substring(0, 10);
      return d.toISOString().substring(0, 10);
    },
    dateTo() {
      const d = this.dateRangeVC?.end;
      if (!d) return '';
      if (typeof d === 'string') return d.substring(0, 10);
      return d.toISOString().substring(0, 10);
    },

    // Normalized and enriched balances with all helper flags precomputed
    enrichedBalances() {
      return this.balances.map(b => {
        const cleanT = this.cleanType(b);
        const cleanP = this.cleanPhase(b);
        const artName = this.sLabel(b);
        const famName = this.familyName(b);
        const famId = b.stockable?.fish_family_id || (cleanT === 'PackageType' ? 'pkg' : cleanT === 'GuanoType' ? 'guano' : 0);
        const procName = this.processName(b);
        const remW = Number(b.remaining_weight || 0);
        const initW = Number(b.total_weight || 0);
        const remBoxes = this.getRemainingPackageCount(b);
        const initBoxes = Number(b.package_count || 0);
        const wapVal = Number(b.wap || 0);
        const effPrice = b.effective_total_price ? Number(b.effective_total_price) : (wapVal > 0 ? wapVal * remW : Number(b.total_price || 0));
        const outW = (b.operations || []).reduce((s, op) => s + Number(op.weight || 0), 0);
        const outB = (b.operations || []).reduce((s, op) => s + Number(op.package_count || 0), 0);
        const pctRem = initW > 0 ? Math.min(100, Math.round((remW / initW) * 100)) : (initBoxes > 0 ? Math.min(100, Math.round((remBoxes / initBoxes) * 100)) : 0);
        const isDepleted = cleanT === 'PackageType' ? remBoxes <= 0 : remW <= 0;
        const whObj = this.allWarehouses.find(w => w.id === b.warehouse_id);

        return {
          ...b,
          _cleanType: cleanT,
          _cleanPhase: cleanP,
          _articleName: artName,
          _familyName: famName,
          _familyId: famId,
          _processName: procName,
          _remainingWeight: remW,
          _initialWeight: initW,
          _remainingBoxes: remBoxes,
          _initialBoxes: initBoxes,
          _wap: Math.round(wapVal * 100) / 100,
          _effectivePrice: Math.round(effPrice * 100) / 100,
          _outWeight: Math.round(outW * 100) / 100,
          _outBoxes: outB,
          _pctRemaining: pctRem,
          _isDepleted: isDepleted,
          _dateDisplay: b.date ? String(b.date).substring(0, 10) : '-',
          _warehouseName: whObj ? whObj.title || whObj.name : `#${b.warehouse_id}`,
        };
      });
    },

    // Filtered balances by global search, status, type, family, and phase
    filteredBalancesList() {
      let list = this.enrichedBalances;

      // Filter Status
      if (this.filterStatus === 'in_stock') {
        list = list.filter(b => !b._isDepleted && !b.is_finished);
      } else if (this.filterStatus === 'depleted') {
        list = list.filter(b => b._isDepleted || b.is_finished);
      }

      // Filter by Type
      if (this.filterType) {
        list = list.filter(b => b._cleanType === this.filterType);
      }

      // Filter by Family
      if (this.filterFamily) {
        list = list.filter(b => b._familyId === this.filterFamily || b._familyName === this.filterFamily);
      }

      // Filter by Phase
      if (this.filterPhase) {
        list = list.filter(b => b._cleanPhase === this.filterPhase);
      }

      // Global Search
      if (this.searchQuery && this.searchQuery.trim()) {
        const q = this.searchQuery.trim().toLowerCase();
        list = list.filter(b => {
          return (
            (b._articleName && b._articleName.toLowerCase().includes(q)) ||
            (b.reception_tracking_number && String(b.reception_tracking_number).toLowerCase().includes(q)) ||
            (b.supplier_name && b.supplier_name.toLowerCase().includes(q)) ||
            (b.fishery_name && b.fishery_name.toLowerCase().includes(q)) ||
            (b.origin_name && b.origin_name.toLowerCase().includes(q)) ||
            (b._familyName && b._familyName.toLowerCase().includes(q)) ||
            (b._warehouseName && b._warehouseName.toLowerCase().includes(q)) ||
            (b._processName && b._processName.toLowerCase().includes(q))
          );
        });
      }

      return list;
    },

    // Distinct filter options available in current balance set
    availableTypes() {
      const s = new Set();
      this.enrichedBalances.forEach(b => s.add(b._cleanType));
      return [...s].map(t => ({ title: this.typeName(t), value: t }));
    },
    availableFamilies() {
      const map = new Map();
      this.enrichedBalances.forEach(b => {
        if (!map.has(b._familyId)) {
          map.set(b._familyId, { title: b._familyName, value: b._familyId, type: b._cleanType });
        }
      });
      return [...map.values()];
    },
    availablePhases() {
      return [
        { title: this.$t('phase_production') || 'Phase Production', value: 'Production', icon: 'mdi-factory', color: '#00897B' },
        { title: this.$t('phase_charge') || 'Phase Réception', value: 'Charge', icon: 'mdi-truck-delivery', color: '#1565C0' },
        { title: this.$t('phase_transfer') || 'Phase Transfert', value: 'WarehouseTransfer', icon: 'mdi-swap-horizontal-bold', color: '#F9A825' },
        { title: this.$t('phase_inventory') || 'Phase Inventaire', value: 'Inventory', icon: 'mdi-clipboard-check-outline', color: '#6D4C41' },
      ];
    },

    // Total Overall KPI metrics across active filtered items
    totalActiveBalancesCount() {
      return this.filteredBalancesList.length;
    },
    totalWeight() {
      return this.filteredBalancesList
        .filter(b => b._cleanType !== 'PackageType')
        .reduce((s, b) => s + b._remainingWeight, 0);
    },
    totalInitialWeight() {
      return this.filteredBalancesList
        .filter(b => b._cleanType !== 'PackageType')
        .reduce((s, b) => s + b._initialWeight, 0);
    },
    totalPackages() {
      return this.filteredBalancesList
        .filter(b => b._cleanType === 'PackageType')
        .reduce((s, b) => s + b._remainingBoxes, 0);
    },
    totalInitialPackages() {
      return this.filteredBalancesList
        .filter(b => b._cleanType === 'PackageType')
        .reduce((s, b) => s + b._initialBoxes, 0);
    },
    totalPrice() {
      return this.filteredBalancesList.reduce((s, b) => s + b._effectivePrice, 0);
    },
    totalEcart() {
      return this.filteredBalancesList
        .filter(b => b._cleanType === 'PackageType')
        .reduce((sum, b) => {
          const invOps = (b.operations || []).filter(op => op.source_type === 'DetailInventory');
          if (invOps.length > 0) {
            return sum + invOps.reduce((s, op) => s + Number(op.package_count || 0), 0);
          }
          return sum + Math.round((b._initialBoxes - (b._outBoxes + b._remainingBoxes)) * 100) / 100;
        }, 0);
    },

    // === GROUP 1: SUB-ELEMENT VIEW (Family / Sub-Element cards containing all individual articles) ===
    subElementsGrouped() {
      const map = {};
      this.filteredBalancesList.forEach(b => {
        const key = `${b._cleanType}_${b._familyId}`;
        if (!map[key]) {
          map[key] = {
            key,
            id: b._familyId,
            name: b._familyName,
            type: b._cleanType,
            typeColor: this.tColor(b._cleanType),
            typeIcon: this.tIcon(b._cleanType),
            totalWeight: 0,
            initialWeight: 0,
            totalBoxes: 0,
            initialBoxes: 0,
            totalPrice: 0,
            articles: [],
            speciesSet: new Set(),
          };
        }
        map[key].totalWeight += b._remainingWeight;
        map[key].initialWeight += b._initialWeight;
        map[key].totalBoxes += b._remainingBoxes;
        map[key].initialBoxes += b._initialBoxes;
        map[key].totalPrice += b._effectivePrice;
        map[key].speciesSet.add(b._articleName);
        map[key].articles.push(b);
      });

      return Object.values(map)
        .map(g => ({
          ...g,
          totalWeight: Math.round(g.totalWeight * 100) / 100,
          initialWeight: Math.round(g.initialWeight * 100) / 100,
          totalPrice: Math.round(g.totalPrice * 100) / 100,
          speciesCount: g.speciesSet.size,
          wap: g.totalWeight > 0 ? Math.round((g.totalPrice / g.totalWeight) * 100) / 100 : 0,
        }))
        .sort((a, b) => b.totalWeight - a.totalWeight);
    },

    // === GROUP 2: PHASE VIEW (Factory Flow Phases containing all individual articles) ===
    phasesGrouped() {
      const phaseKeys = ['Production', 'Charge', 'WarehouseTransfer', 'Inventory'];
      return phaseKeys.map(pk => {
        const meta = this.phaseMeta(pk);
        const articles = this.filteredBalancesList.filter(b => b._cleanPhase === pk);
        const totalW = articles.filter(b => b._cleanType !== 'PackageType').reduce((s, b) => s + b._remainingWeight, 0);
        const totalB = articles.filter(b => b._cleanType === 'PackageType').reduce((s, b) => s + b._remainingBoxes, 0);
        const totalP = articles.reduce((s, b) => s + b._effectivePrice, 0);

        return {
          key: pk,
          meta,
          articles,
          count: articles.length,
          totalWeight: Math.round(totalW * 100) / 100,
          totalBoxes: totalB,
          totalPrice: Math.round(totalP * 100) / 100,
        };
      });
    },

    // === GROUP 3: MASTER LIST SORTED & PAGINATED ===
    sortedMasterList() {
      const list = [...this.filteredBalancesList];
      const key = this.tableSortKey;
      const dir = this.tableSortDir === 'asc' ? 1 : -1;

      return list.sort((a, b) => {
        let va = a[key] ?? a['_' + key];
        let vb = b[key] ?? b['_' + key];
        if (va == null) va = '';
        if (vb == null) vb = '';
        if (typeof va === 'number' && typeof vb === 'number') {
          return (va - vb) * dir;
        }
        return String(va).localeCompare(String(vb)) * dir;
      });
    },
    paginatedMasterList() {
      const start = (this.tablePage - 1) * this.tableItemsPerPage;
      return this.sortedMasterList.slice(start, start + this.tableItemsPerPage);
    },
    masterTotalPages() {
      return Math.ceil(this.sortedMasterList.length / this.tableItemsPerPage) || 1;
    },

    // === LEGACY STEP-BY-STEP DRILLDOWN LEVEL DATA (Fixed and Normalized) ===
    articleTypes() {
      const groups = {};
      this.filteredBalancesList.forEach(b => {
        const t = b._cleanType;
        if (!groups[t]) groups[t] = { type: t, weight: 0, package_count: 0, price: 0, ecart: 0 };
        groups[t].weight += b._remainingWeight;
        groups[t].package_count += b._remainingBoxes;
        groups[t].price += b._effectivePrice;
      });
      return Object.values(groups);
    },
    fishFamilies() {
      const groups = {};
      this.filteredBalancesList.filter(b => b._cleanType === 'FishType').forEach(b => {
        const fid = b._familyId;
        if (!groups[fid]) groups[fid] = { id: fid, name: b._familyName, weight: 0, price: 0, packages: 0, packageTypes: new Set() };
        groups[fid].weight += b._remainingWeight;
        groups[fid].price += b._effectivePrice;
        groups[fid].packages += b._remainingBoxes;
        if (b.package_type) groups[fid].packageTypes.add(b.package_type[`name_${this.lang}`] || b.package_type.name || '');
      });
      return Object.values(groups).map(g => ({
        ...g,
        weight: Math.round(g.weight * 100) / 100,
        price: Math.round(g.price * 100) / 100,
        wap: g.weight > 0 ? Math.round((g.price / g.weight) * 100) / 100 : 0,
        packageTypeNames: [...g.packageTypes].filter(Boolean).join(', ') || '-',
      }));
    },
    fishNames() {
      if (this.drillLevel < 2 || !this.drillFamilyId) return [];
      const groups = {};
      this.filteredBalancesList
        .filter(b => b._cleanType === 'FishType' && b._familyId === this.drillFamilyId)
        .forEach(b => {
          const sid = b.stockable_id;
          const nm = b._articleName;
          if (!groups[sid]) groups[sid] = { id: sid, name: nm, weight: 0, price: 0, packages: 0, packageTypes: new Set(), count: 0 };
          groups[sid].weight += b._remainingWeight;
          groups[sid].price += b._effectivePrice;
          groups[sid].packages += b._remainingBoxes;
          groups[sid].count++;
          if (b.package_type) groups[sid].packageTypes.add(b.package_type[`name_${this.lang}`] || b.package_type.name || '');
        });
      return Object.values(groups).map(g => ({
        ...g,
        weight: Math.round(g.weight * 100) / 100,
        price: Math.round(g.price * 100) / 100,
        wap: g.weight > 0 ? Math.round((g.price / g.weight) * 100) / 100 : 0,
        packageTypeNames: [...g.packageTypes].filter(Boolean).join(', ') || '-',
      }));
    },
    rawFishArticles() {
      if (this.drillLevel < 3 || !this.drillFishNameId) return [];
      return this.filteredBalancesList.filter(
        b => b._cleanType === 'FishType' && b.stockable_id === this.drillFishNameId && b._familyId === this.drillFamilyId
      );
    },
    breadcrumbs() {
      const bc = [{ label: this.$t('warehouse_dashboard') || 'Entrepôt', level: 0, icon: 'mdi-warehouse' }];
      if (this.drillLevel >= 1 && this.drillType) {
        bc.push({ label: this.typeName(this.drillType), level: 1, icon: this.tIcon(this.drillType) });
      }
      if (this.drillLevel >= 2 && this.drillFamilyName) {
        bc.push({ label: this.drillFamilyName, level: 2, icon: 'mdi-fish' });
      }
      if (this.drillLevel >= 3 && this.drillFishName) {
        bc.push({ label: this.drillFishName, level: 3, icon: 'mdi-table' });
      }
      return bc;
    },

    // Per-type statistics panel
    warehouseStats() {
      const groups = {};
      this.filteredBalancesList.forEach(b => {
        const t = b._cleanType;
        if (!groups[t]) groups[t] = { type: t, name: this.typeName(t), count: 0, weight: 0, package_count: 0, price: 0 };
        groups[t].count++;
        groups[t].weight += b._remainingWeight;
        groups[t].package_count += b._remainingBoxes;
        groups[t].price += b._effectivePrice;
      });
      return Object.values(groups).sort((a, b) => (b.type === 'PackageType' ? b.package_count : b.weight) - (a.type === 'PackageType' ? a.package_count : a.weight));
    },

    // Analytics In/Out Chart
    dashChartSeries() {
      if (!this.filteredBalancesList.length || !this.dateFrom || !this.dateTo) return [{ name: '', data: [] }];
      const mode = this.dashChartMode || 'daily';
      const bucketKey = (dateStr) => {
        if (!dateStr) return null;
        const d = dateStr.substring(0, 10);
        if (mode === 'yearly') return d.substring(0, 4);
        if (mode === 'monthly') return d.substring(0, 7);
        return d;
      };
      const dateMapBal = {};
      const dateMapOps = {};
      this.filteredBalancesList.forEach(b => {
        const d = b.date ? String(b.date).substring(0, 10) : null;
        if (d && d >= this.dateFrom && d <= this.dateTo) {
          const k = bucketKey(d);
          if (!dateMapBal[k]) dateMapBal[k] = 0;
          dateMapBal[k] += b._initialWeight;
        }
        (b.operations || []).forEach(op => {
          const od = op.date ? String(op.date).substring(0, 10) : null;
          if (od && od >= this.dateFrom && od <= this.dateTo) {
            const k = bucketKey(od);
            if (!dateMapOps[k]) dateMapOps[k] = 0;
            dateMapOps[k] += Number(op.weight || 0);
          }
        });
      });
      const allKeys = [...new Set([...Object.keys(dateMapBal), ...Object.keys(dateMapOps)])].sort();
      if (!allKeys.length) return [{ name: '', data: [] }];
      return [
        { name: (this.$t('stock_in') || 'Entrées') + ' (Kg)', data: allKeys.map(k => ({ x: k, y: Math.round((dateMapBal[k] || 0) * 100) / 100 })) },
        { name: (this.$t('stock_out') || 'Sorties') + ' (Kg)', data: allKeys.map(k => ({ x: k, y: Math.round((dateMapOps[k] || 0) * 100) / 100 })) },
      ];
    },
    dashChartOptions() {
      return {
        chart: { type: 'area', toolbar: { show: true }, zoom: { enabled: true }, background: 'transparent' },
        stroke: { width: [3, 3], curve: 'smooth' },
        colors: ['#a21ee5', '#EF5350'],
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.05,
            stops: [0, 95, 100],
          },
        },
        xaxis: { type: 'category', labels: { rotate: -45, style: { fontSize: '10px' } } },
        yaxis: [
          { title: { text: (this.$t('stock_in') || 'Stock') + ' (Kg)', style: { fontSize: '11px' } }, labels: { formatter: v => this.main.formatDisplayNumber(v) } },
          { show: false, labels: { formatter: v => this.main.formatDisplayNumber(v) } },
        ],
        tooltip: { shared: true },
        legend: { position: 'top', fontSize: '11px' },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    pieOptions() {
      return {
        chart: { type: 'pie', background: 'transparent' },
        labels: this.pieLabels,
        colors: this.pieColors.length ? this.pieColors : undefined,
        legend: { position: 'bottom', fontSize: '12px' },
        tooltip: { y: { formatter: v => this.main.formatDisplayNumber(v) + ' Kg' } },
        dataLabels: {
          formatter: (val, opts) => {
            const w = opts.w.config.series[opts.seriesIndex];
            return `${val.toFixed(1)}%\n${this.main.formatDisplayNumber(w)} Kg`;
          },
        },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },
    lineOptions() {
      return {
        chart: { type: 'line', toolbar: { show: true }, zoom: { enabled: true }, background: 'transparent' },
        stroke: { width: 3, curve: 'smooth' },
        colors: ['#a21ee5'],
        xaxis: { categories: this.lineCategories, labels: { rotate: -45, style: { fontSize: '10px' } } },
        yaxis: [
          { title: { text: (this.$t('weight') || 'Poids') + ' (Kg)' }, labels: { formatter: v => this.main.formatDisplayNumber(v) } },
        ],
        tooltip: { shared: true },
        legend: { position: 'top' },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },
  },

  mounted() {
    this.restoreCachedWarehouses();
    this.fetchAllWarehouses();
    mitt.on('model-changed', this.onModelChanged);
  },
  activated() {
    mitt.on('model-changed', this.onModelChanged);
  },
  deactivated() {
    mitt.off('model-changed', this.onModelChanged);
  },
  beforeUnmount() {
    mitt.off('model-changed', this.onModelChanged);
    if (this._refreshTimer) clearTimeout(this._refreshTimer);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },

  methods: {
    // Restore cached warehouse selection from localStorage (Rule 3)
    restoreCachedWarehouses() {
      try {
        const cached = localStorage.getItem('last_selected_warehouses');
        if (cached) {
          const ids = JSON.parse(cached);
          if (Array.isArray(ids) && ids.length) {
            this.selectedWarehouseIds = ids;
          }
        }
      } catch (e) {
        console.error('Error restoring cached warehouses:', e);
      }
    },
    saveCachedWarehouses() {
      try {
        localStorage.setItem('last_selected_warehouses', JSON.stringify(this.selectedWarehouseIds));
      } catch (e) {
        console.error('Error saving cached warehouses:', e);
      }
    },

    // Real-time WebSocket sync (Rule 3 & Rule 5)
    onModelChanged(d) {
      const relevantModels = [
        'Warehouse', 'WarehouseBalance', 'WarehouseBalanceOperation',
        'Charge', 'Production', 'Shipping',
        'DetailCharge', 'DetailProduction', 'DetailShipping',
        'WarehouseTransfer', 'Inventory',
      ];
      if (relevantModels.includes(d.model)) {
        if (this.selectedWarehouseIds.length) {
          if (this._refreshTimer) clearTimeout(this._refreshTimer);
          this._refreshTimer = setTimeout(() => {
            this.fetchDashboardData();
            this.triggerSummarySnackbar({
              title: this.$t('stock_updated_notification') || 'Stock Synchronisé en Temps Réel',
              message: `${d.model} (${d.action || 'mise à jour'})`,
              color: '#a21ee5',
            });
          }, 800);
        }
      }
      if (d.model === 'Warehouse' && (d.action === 'created' || d.action === 'deleted')) {
        this.fetchAllWarehouses();
      }
    },

    // Trigger Rule 5 Summary Snackbar with countdown
    triggerSummarySnackbar({ title, message, color = '#a21ee5', details = null }) {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.summarySnackbar.title = title;
      this.summarySnackbar.message = message;
      this.summarySnackbar.color = color;
      this.summarySnackbar.details = details;
      this.summarySnackbar.show = true;
      this.snackbarProgress = 100;
      const step = 100 / 80;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= step;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, 100);
    },

    async fetchAllWarehouses() {
      try {
        const r = await axios.get('/api/warehouses');
        this.allWarehouses = (r.data.warehouses || []).map(w => ({
          ...w,
          title: w.name,
          value: w.id,
        }));
        // If no warehouse is selected yet, default to first with stock or first available
        if (!this.selectedWarehouseIds.length && this.allWarehouses.length) {
          const withStock = this.allWarehouses.filter(w => Number(w.total_stock || w.total_weight || 0) > 0);
          if (withStock.length > 0) {
            this.selectedWarehouseIds = [withStock[0].id];
          } else {
            this.selectedWarehouseIds = [this.allWarehouses[0].id];
          }
          this.saveCachedWarehouses();
          this.fetchDashboardData();
        } else if (this.selectedWarehouseIds.length) {
          this.fetchDashboardData();
        }
      } catch (e) {
        console.error('fetchAllWarehouses error:', e);
      }
    },

    async fetchDashboardData() {
      if (!this.selectedWarehouseIds.length) {
        this.balances = [];
        this.warehouses = [];
        return;
      }
      this.fetchLoading = true;
      try {
        const r = await axios.get('/api/warehouses/dashboard-data', {
          params: { warehouse_ids: this.selectedWarehouseIds },
        });
        this.warehouses = r.data.warehouses || [];
        this.balances = r.data.balances || [];
        // Expand first sub-elements by default
        this.$nextTick(() => {
          this.subElementsGrouped.slice(0, 3).forEach(g => {
            this.expandedSubElements[g.key] = true;
          });
        });
      } catch (e) {
        console.error('fetchDashboardData error:', e);
      }
      this.fetchLoading = false;
    },

    onWarehouseChange() {
      this.saveCachedWarehouses();
      this.drillLevel = 0;
      this.drillType = null;
      this.drillFamilyId = null;
      this.drillFamilyName = null;
      this.drillFishNameId = null;
      this.drillFishName = null;
      this.colFilters = {};
      this.expandedBalances = {};
      this.tablePage = 1;
      this.fetchDashboardData();
    },

    selectAllWarehouses() {
      this.selectedWarehouseIds = this.allWarehouses.map(w => w.id);
      this.onWarehouseChange();
    },
    clearAllWarehouses() {
      this.selectedWarehouseIds = [];
      this.onWarehouseChange();
    },
    selectWarehouseDirect(id) {
      if (!this.selectedWarehouseIds.includes(id)) {
        this.selectedWarehouseIds.push(id);
      }
      this.onWarehouseChange();
    },

    // Sub-element expansion toggles
    toggleSubElement(key) {
      this.expandedSubElements[key] = !this.expandedSubElements[key];
      this.expandedSubElements = { ...this.expandedSubElements };
    },
    togglePhase(key) {
      this.expandedPhases[key] = !this.expandedPhases[key];
      this.expandedPhases = { ...this.expandedPhases };
    },
    expandAllSubElements() {
      const next = {};
      this.subElementsGrouped.forEach(g => { next[g.key] = true; });
      this.expandedSubElements = next;
    },
    collapseAllSubElements() {
      this.expandedSubElements = {};
    },

    // Expand operations drawer for a single balance
    toggleExpand(balanceId) {
      this.expandedBalances[balanceId] = !this.expandedBalances[balanceId];
      this.expandedBalances = { ...this.expandedBalances };
    },

    // Step-by-step drilldown handlers
    drillIntoType(type) {
      this.drillType = type;
      this.drillLevel = 1;
      this.drillFamilyId = null;
      this.drillFamilyName = null;
      this.drillFishNameId = null;
      this.drillFishName = null;
    },
    drillIntoFamily(fam) {
      this.drillFamilyId = fam.id;
      this.drillFamilyName = fam.name;
      this.drillLevel = 2;
      this.drillFishNameId = null;
      this.drillFishName = null;
    },
    drillIntoFishName(fn) {
      this.drillFishNameId = fn.id;
      this.drillFishName = fn.name;
      this.drillLevel = 3;
    },
    drillTo(level) {
      this.drillLevel = level;
      if (level < 3) { this.drillFishNameId = null; this.drillFishName = null; }
      if (level < 2) { this.drillFamilyId = null; this.drillFamilyName = null; }
      if (level < 1) { this.drillType = null; }
    },

    // Normalization Helpers
    cleanType(b) {
      const t = b?.clean_stockable_type || b?.stockable_type || '';
      if (t.includes('FishType')) return 'FishType';
      if (t.includes('GuanoType')) return 'GuanoType';
      if (t.includes('PackageType')) return 'PackageType';
      return t || 'Other';
    },
    cleanPhase(b) {
      const st = b?.clean_source_type || b?.source_type || '';
      if (st.includes('Production')) return 'Production';
      if (st.includes('Charge')) return 'Charge';
      if (st.includes('Transfer')) return 'WarehouseTransfer';
      if (st.includes('Inventory')) return 'Inventory';
      if (st.includes('Shipping')) return 'Shipping';
      return 'Production';
    },
    phaseMeta(phase) {
      switch (phase) {
        case 'Production':
          return {
            icon: 'mdi-factory',
            color: '#00897B',
            bg: 'rgba(0, 137, 123, 0.1)',
            title: this.$t('phase_production') || 'Phase Production (Sortie d\'Usine)',
            shortTitle: this.$t('production') || 'Production',
            desc: 'Articles congelés et coproduits issus des chaînes de fabrication',
          };
        case 'Charge':
          return {
            icon: 'mdi-truck-delivery',
            color: '#1565C0',
            bg: 'rgba(21, 101, 192, 0.1)',
            title: this.$t('phase_charge') || 'Phase Réception (Achat Matière & Fournitures)',
            shortTitle: this.$t('reception') || 'Réception',
            desc: 'Poisson frais, emballages et matières premières directes des fournisseurs',
          };
        case 'WarehouseTransfer':
          return {
            icon: 'mdi-swap-horizontal-bold',
            color: '#F9A825',
            bg: 'rgba(249, 168, 37, 0.12)',
            title: this.$t('phase_transfer') || 'Phase Transfert Inter-Dépôts',
            shortTitle: this.$t('transfer') || 'Transfert',
            desc: 'Stock déplacé ou réaffecté depuis un autre entrepôt / chambre froide',
          };
        case 'Inventory':
          return {
            icon: 'mdi-clipboard-check-outline',
            color: '#6D4C41',
            bg: 'rgba(109, 76, 65, 0.1)',
            title: this.$t('phase_inventory') || 'Phase Inventaire & Régularisation',
            shortTitle: this.$t('inventory') || 'Inventaire',
            desc: 'Ajustements physiques et réconciliations de stock',
          };
        default:
          return {
            icon: 'mdi-cube-outline',
            color: '#a21ee5',
            bg: 'rgba(162, 30, 229, 0.1)',
            title: phase,
            shortTitle: phase,
            desc: '',
          };
      }
    },
    tColor(t) {
      return t === 'FishType' ? '#a21ee5' : t === 'GuanoType' ? '#FB8C00' : t === 'PackageType' ? '#6D4C41' : '#9E9E9E';
    },
    tIcon(t) {
      return t === 'FishType' ? 'mdi-fish' : t === 'GuanoType' ? 'mdi-leaf' : t === 'PackageType' ? 'mdi-package-variant-closed' : 'mdi-cube-outline';
    },
    typeName(t) {
      return t === 'FishType' ? (this.$t('fish') || 'Poisson') : t === 'GuanoType' ? (this.$t('guano') || 'Guano') : t === 'PackageType' ? (this.$t('packages') || 'Emballage') : t;
    },
    sLabel(balance) {
      if (balance?.article_name) return balance.article_name;
      const s = balance?.stockable;
      if (!s) return `#${balance?.stockable_id || '?'}`;
      return s[`name_${this.lang}`] || s.name || s.name_fr || s.name_en || s.name_es || `#${balance.stockable_id}`;
    },
    familyName(balance) {
      const fam = balance?.stockable?.fish_family;
      if (fam) return fam[`name_${this.lang}`] || fam.name || fam.name_fr || fam.name_en || (this.$t('unknown') || 'Inconnu');
      const type = this.cleanType(balance);
      if (type === 'PackageType') return this.$t('packaging') || 'Emballage';
      if (type === 'GuanoType') return this.$t('guano') || 'Guano';
      return this.$t('general_stock') || 'Stock Général';
    },
    processName(balance) {
      const p = balance?.stockable?.fish_process_type;
      if (p) return p[`name_${this.lang}`] || p.name || p.name_fr;
      return balance?.stockable?.process || null;
    },
    srcLabel(t) {
      if (!t) return '-';
      if (t.includes('Production')) return this.$t('source_production') || 'Production';
      if (t.includes('Charge')) return this.$t('source_charge') || 'Charge / Réception';
      if (t.includes('Transfer')) return this.$t('source_warehouse_transfer') || 'Transfert';
      if (t.includes('Shipping')) return this.$t('source_shipping') || 'Expédition';
      if (t.includes('Inventory')) return this.$t('source_inventory') || 'Inventaire';
      return t;
    },
    srcColor(t) {
      if (!t) return 'grey';
      if (t.includes('Production')) return '#00897B';
      if (t.includes('Charge')) return '#1565C0';
      if (t.includes('Transfer')) return '#F9A825';
      if (t.includes('Shipping')) return '#E65100';
      if (t.includes('Inventory')) return '#6D4C41';
      return 'grey';
    },
    getRemainingPackageCount(bal) {
      if (!bal) return 0;
      const initial = Number(bal.package_count || 0);
      const out = (bal.operations || []).reduce((s, op) => s + Number(op.package_count || 0), 0);
      return Math.max(0, Math.round((initial - out) * 100) / 100);
    },

    // Navigation to source document
    goToSource(item) {
      const st = item.source_type;
      if (!st || !item.source_id) return;
      if (st.includes('Charge')) this.$router.push({ name: 'edit_charge', params: { id: item.source_id } });
      else if (st.includes('Production')) this.$router.push({ name: 'edit_production', params: { id: item.source_id } });
      else if (st.includes('Shipping')) this.$router.push({ name: 'edit_shipping', params: { id: item.source_id } });
      else if (st.includes('WarehouseTransfer')) this.$router.push({ name: 'edit_warehouse_transfer', params: { id: item.source_id } });
    },
    goToSourceOp(op) {
      const st = op.source_type;
      if (!st || !op.source_id) return;
      if (st.includes('Charge')) this.$router.push({ name: 'edit_charge', params: { id: op.source_id } });
      else if (st.includes('Production')) this.$router.push({ name: 'edit_production', params: { id: op.source_id } });
      else if (st.includes('Shipping')) this.$router.push({ name: 'edit_shipping', params: { id: op.source_id } });
      else if (st.includes('WarehouseTransfer')) this.$router.push({ name: 'edit_warehouse_transfer', params: { id: op.source_id } });
    },

    // Sorting for master list
    toggleSort(key) {
      if (this.tableSortKey === key) {
        this.tableSortDir = this.tableSortDir === 'asc' ? 'desc' : 'asc';
      } else {
        this.tableSortKey = key;
        this.tableSortDir = 'desc';
      }
    },

    // Calendar & Presets
    onDateRangeVCChange(val) {
      if (val && val.start && val.end) {
        this.dateRangeVC = val;
      }
    },
    setDatePreset(preset) {
      const now = new Date();
      if (preset === 'today') {
        const startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        this.dateRangeVC = { start: startOfDay, end: now };
      } else if (preset === '7d') {
        this.dateRangeVC = { start: new Date(Date.now() - 7 * 86400000), end: now };
      } else if (preset === '30d') {
        this.dateRangeVC = { start: new Date(Date.now() - 30 * 86400000), end: now };
      } else if (preset === 'all') {
        this.dateRangeVC = { start: new Date(2020, 0, 1), end: now };
      }
    },

    // === EXPORT TO EXCEL / CSV ===
    exportToExcel() {
      try {
        const rows = this.filteredBalancesList.map(b => ({
          'ID': b.id,
          'Entrepôt': b._warehouseName,
          'Date': b._dateDisplay,
          'Phase': this.phaseMeta(b._cleanPhase).shortTitle,
          'Famille': b._familyName,
          'Article': b._articleName,
          'Procédé': b._processName || '-',
          'N° Lot / Réception': b.reception_tracking_number || '-',
          'Fournisseur': b.supplier_name || '-',
          'Poids Initial (Kg)': b._initialWeight,
          'Poids Restant (Kg)': b._remainingWeight,
          'Caisses Initiales': b._initialBoxes,
          'Caisses Restantes': b._remainingBoxes,
          'PMP / WAP': b._wap,
          'Valeur Stock': b._effectivePrice,
        }));
        const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + [
          Object.keys(rows[0] || {}).join(';'),
          ...rows.map(r => Object.values(r).map(v => `"${String(v).replace(/"/g, '""')}"`).join(';')),
        ].join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `Stock_Entrepot_${new Date().toISOString().substring(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        this.triggerSummarySnackbar({
          title: this.$t('export_csv') || 'Export Réussi',
          message: `${rows.length} articles exportés avec succès`,
        });
      } catch (e) {
        console.error('Export error:', e);
      }
    },

    // Print master table
    printTable() {
      window.print();
    },

    // === CHART DIALOGS ===
    _genColors(n) {
      const palette = ['#a21ee5', '#26A69A', '#FF7043', '#AB47BC', '#42A5F5', '#66BB6A', '#FFA726', '#EC407A', '#78909C', '#8D6E63', '#29B6F6', '#D4E157', '#7E57C2', '#26C6DA', '#FFCA28'];
      return Array.from({ length: n }, (_, i) => palette[i % palette.length]);
    },
    openSubElementPie(group) {
      const items = group.articles.map(b => ({
        name: `${b._articleName} (#${b.reception_tracking_number || b.id})`,
        weight: b._remainingWeight,
      })).filter(x => x.weight > 0);
      this._openPie(items, group.name);
    },
    openPhasePie(phaseGroup) {
      const items = phaseGroup.articles.map(b => ({
        name: `${b._articleName} (${b._familyName})`,
        weight: b._remainingWeight,
      })).filter(x => x.weight > 0);
      this._openPie(items, phaseGroup.meta.shortTitle);
    },
    openArticlePie(bal) {
      const ops = bal.operations || [];
      const items = ops.map(op => ({
        name: `${this.srcLabel(op.source_type)} #${op.source_id || '-'} (${this.main.formateDate(op.date)})`,
        weight: Math.round(Number(op.weight || 0) * 100) / 100,
      }));
      const rem = bal._remainingWeight;
      if (rem > 0) items.push({ name: this.$t('remaining_weight') || 'Poids Restant', weight: rem });
      this._openPie(items, `${bal._articleName} (Lot #${bal.reception_tracking_number || bal.id})`);
    },
    _openPie(items, titlePrefix) {
      this.pieLabels = items.map(i => i.name);
      this.pieSeries = items.map(i => i.weight);
      this.pieColors = this._genColors(items.length);
      this.chartTitle = titlePrefix + ' - ' + (this.$t('stock_distribution') || 'Répartition du Stock');
      this.chartMode = 'pie';
      this.chartDialog = true;
    },
    openLineChart(context) {
      const bals = this.filteredBalancesList.filter(b => {
        if (context.type === 'family') return b._familyId === context.familyId;
        return b.stockable_id === context.stockableId;
      });
      const dateMap = {};
      bals.forEach(b => {
        const d = b.date ? String(b.date).substring(0, 10) : null;
        if (d && d >= this.dateFrom && d <= this.dateTo) {
          if (!dateMap[d]) dateMap[d] = 0;
          dateMap[d] += b._initialWeight;
        }
        (b.operations || []).forEach(op => {
          const od = op.date ? String(op.date).substring(0, 10) : null;
          if (od && od >= this.dateFrom && od <= this.dateTo) {
            if (!dateMap[od]) dateMap[od] = 0;
            dateMap[od] -= Number(op.weight || 0);
          }
        });
      });
      const dates = Object.keys(dateMap).sort();
      this.lineCategories = dates;
      this.lineSeries = [
        { name: (this.$t('weight') || 'Poids') + ' (Kg)', data: dates.map(d => Math.round(dateMap[d] * 100) / 100) },
      ];
      this.chartTitle = (context.familyName || context.articleName) + ' - ' + (this.$t('stock_history') || 'Historique du Stock');
      this.chartMode = 'line';
      this.chartDialog = true;
    },
  },
};
