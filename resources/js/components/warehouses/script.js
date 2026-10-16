import axios from 'axios';
import { useMain } from '@/stores/Main';
import mitt from '@/plugins/mitt.js';

export default {
  name: 'WarehouseDashboard',
  data() {
    const today = new Date();
    const d30 = new Date(Date.now() - 30 * 86400000);
    return {
      warehouses: [],
      allWarehouses: [],
      selectedWarehouseIds: [],
      loading: false,
      fetchLoading: false,
      drillLevel: 0,
      drillType: null,
      drillFamilyId: null,
      drillFamilyName: null,
      drillFishNameId: null,
      drillFishName: null,
      balances: [],
      expandedBalances: {},
      colFilters: {},
      l2FilterMenus: {},
      _refreshTimer: null,
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
      pieDrillStack: [], // for drill-down history
      pieDrillData: null, // current level raw data for click
      lineSeries: [],
      lineCategories: [],
    };
  },
  computed: {
    main() { return useMain() },
    lang() { return this.main.lang?.code || 'en' },
    articleTypes() {
      const groups = {};
      this.balances.forEach(b => {
        const t = b.stockable_type || 'Other';
        if (!groups[t]) groups[t] = { type: t, weight: 0, package_count: 0, price: 0, ecart: 0 };
        const w = Number(b.remaining_weight || 0);
        const pkgs = this.getRemainingPackageCount(b);
        const wapVal = Number(b.wap || 0);
        const priceVal = wapVal > 0 ? wapVal * w : Number(b.total_price || 0);

        if (t === 'PackageType') {
          const outPkg = (b.operations || []).reduce((sum, op) => sum + Number(op.package_count || 0), 0);
          const invOps = (b.operations || []).filter(op => op.source_type === 'DetailInventory');
          let ec = 0;
          if (invOps.length > 0) {
            ec = invOps.reduce((sum, op) => sum + Number(op.package_count || 0), 0);
          } else {
            ec = Math.round((Number(b.package_count || 0) - (outPkg + pkgs)) * 100) / 100;
          }
          groups[t].ecart += ec;
        }

        groups[t].weight += w;
        groups[t].package_count += pkgs;
        groups[t].price += priceVal;
      });
      return this.applyFilters(Object.values(groups), 'level0');
    },
    fishFamilies() {
      const groups = {};
      this.filteredBalances.filter(b => b.stockable_type === 'FishType').forEach(b => {
        const fam = b.stockable?.fish_family;
        const fname = fam ? (fam[`name_${this.lang}`] || fam.name || fam.name_fr || 'Unknown') : this.$t('unknown');
        const fid = fam?.id || 0;
        if (!groups[fid]) groups[fid] = { id: fid, name: fname, weight: 0, price: 0, packages: 0, packageTypes: new Set() };
        const w = Number(b.remaining_weight || 0);
        const wapVal = Number(b.wap || 0);
        const p = wapVal > 0 ? wapVal * w : 0;
        groups[fid].weight += w;
        groups[fid].price += p;
        groups[fid].packages += Number(b.package_count || 0);
        if (b.package_type) groups[fid].packageTypes.add(b.package_type[`name_${this.lang}`] || '');
      });
      return this.applyFilters(Object.values(groups).map(g => ({
        ...g,
        wap: g.weight > 0 ? Math.round((g.price / g.weight) * 100) / 100 : 0,
        packageTypeNames: [...g.packageTypes].filter(Boolean).join(', ') || '-',
      })), 'level1_fish');
    },
    guanoArticles() {
      return this.applyFilters(this.filteredBalances
        .filter(b => b.stockable_type === 'GuanoType')
        .map(b => {
          const w = Number(b.remaining_weight || b.total_weight || 0);
          const wapVal = Number(b.wap || 0);
          const priceVal = wapVal > 0 ? wapVal * w : Number(b.total_price || 0);
          return {
            ...b,
            wap: Math.round(wapVal * 100) / 100,
            total_price: Math.round(priceVal * 100) / 100,
            articleName: this.sLabel(b),
            _date_display: b.date ? String(b.date).substring(0, 10) : '-',
            _source_label: b.source_type ? this.srcLabel(b.source_type) : '-',
          };
        }), 'level1_guano');
    },
    packageArticles() {
      return this.applyFilters(this.filteredBalances
        .filter(b => b.stockable_type === 'PackageType')
        .map(b => {
          const w = Number(b.remaining_weight || b.total_weight || 0);
          const wapVal = Number(b.wap || 0);
          const priceVal = wapVal > 0 ? wapVal * w : Number(b.total_price || 0);
          const outPkg = (b.operations || []).reduce((sum, op) => sum + Number(op.package_count || 0), 0);
          const remainingPkg = this.getRemainingPackageCount(b);

          const invOps = (b.operations || []).filter(op => op.source_type === 'DetailInventory');
          let ecart = 0;
          if (invOps.length > 0) {
            ecart = invOps.reduce((sum, op) => sum + Number(op.package_count || 0), 0);
          } else {
            ecart = Math.round((Number(b.package_count || 0) - (outPkg + remainingPkg)) * 100) / 100;
          }

          return {
            ...b,
            wap: Math.round(wapVal * 100) / 100,
            total_price: Math.round(priceVal * 100) / 100,
            articleName: this.sLabel(b),
            _date_display: b.date ? String(b.date).substring(0, 10) : '-',
            _source_label: b.source_type ? this.srcLabel(b.source_type) : '-',
            out_packages: outPkg,
            remaining_packages: remainingPkg,
            ecart: ecart,
          };
        }), 'level1_package');
    },
    totalEcart() {
      return this.packageArticles.reduce((sum, b) => sum + (Number(b.ecart) || 0), 0);
    },
    fishNames() {
      if (this.drillLevel < 2 || !this.drillFamilyId) return [];
      const groups = {};
      this.filteredBalances
        .filter(b => b.stockable_type === 'FishType' && (b.stockable?.fish_family_id || 0) === this.drillFamilyId)
        .forEach(b => {
          const sid = b.stockable_id;
          const nm = this.sLabel(b);
          if (!groups[sid]) groups[sid] = { id: sid, name: nm, weight: 0, price: 0, packages: 0, packageTypes: new Set(), count: 0 };
          const w = Number(b.remaining_weight || 0);
          const wapVal = Number(b.wap || 0);
          const p = wapVal > 0 ? wapVal * w : 0;
          groups[sid].weight += w;
          groups[sid].price += p;
          groups[sid].packages += Number(b.package_count || 0);
          groups[sid].count++;
          if (b.package_type) groups[sid].packageTypes.add(b.package_type[`name_${this.lang}`] || '');
        });
      return this.applyFilters(Object.values(groups).map(g => ({
        ...g,
        wap: g.weight > 0 ? Math.round((g.price / g.weight) * 100) / 100 : 0,
        packageTypeNames: [...g.packageTypes].filter(Boolean).join(', ') || '-',
      })), 'level2_fishname');
    },
    rawFishArticles() {
      if (this.drillLevel < 3 || !this.drillFishNameId) return [];
      return this.filteredBalances
        .filter(b => b.stockable_type === 'FishType' && b.stockable_id === this.drillFishNameId && (b.stockable?.fish_family_id || 0) === this.drillFamilyId)
        .map(b => {
          const w = Number(b.remaining_weight || b.total_weight || 0);
          const wapVal = Number(b.wap || 0);
          const priceVal = wapVal > 0 ? wapVal * w : 0;
          return {
            ...b,
            wap: Math.round(wapVal * 100) / 100,
            total_price: Math.round(priceVal * 100) / 100,
            articleName: this.sLabel(b),
            _date_display: b.date ? b.date.substring(0, 10) : '-',
            _source_label: b.source_type ? this.srcLabel(b.source_type) : '-',
          };
        });
    },
    fishArticles() {
      return this.applyFilters(this.rawFishArticles, 'level3');
    },
    isProductionEffectWarehouse() {
      if (!this.selectedWarehouseIds || !this.selectedWarehouseIds.length) return false;
      const allW = (this.warehouses && this.warehouses.length) ? this.warehouses : this.allWarehouses;
      const selectedW = allW.filter(w => this.selectedWarehouseIds.includes(w.id || w.value));
      return selectedW.length > 0 && selectedW.some(w => !!w.allow_production_effect);
    },
    l2Headers() {
      const c = this.main.currency;
      const headers = [
        { key: '_date_display', label: this.$t('date'), filterable: true },
        { key: 'reception_tracking_number', label: this.$t('reception_tracking_number_col'), filterable: true },
        { key: 'supplier_name', label: this.$t('suppliers'), filterable: true },
        { key: 'origin_name', label: this.$t('origin'), filterable: true },
        { key: 'fishery_name', label: this.$t('fishery'), filterable: true },
        { key: '_source_label', label: this.$t('source'), filterable: true },
      ];
      if (!this.isProductionEffectWarehouse) {
        headers.push({ key: 'total_weight', label: this.$t('total_weight') + ' (Kg)', filterable: false });
      }
      headers.push({ key: 'package_count', label: this.$t('package_count'), filterable: false });
      if (!this.isProductionEffectWarehouse) {
        headers.push({ key: 'wap', label: this.$t('wap') + ` (${c}/Kg)`, filterable: false });
      }
      headers.push({ key: 'total_price', label: this.$t('total_price') + ` (${c})`, filterable: false });
      if (!this.isProductionEffectWarehouse) {
        headers.push({ key: 'remaining_weight', label: this.$t('remaining') + ' (Kg)', filterable: false });
      }
      return headers;
    },
    filteredBalances() {
      return this.balances.filter(b => {
        if (b.stockable_type === 'PackageType') {
          return !b.is_finished && this.getRemainingPackageCount(b) > 0;
        }
        return !b.is_finished && Number(b.remaining_weight) > 0;
      });
    },
    breadcrumbs() {
      const bc = [{ label: this.$t('warehouse_dashboard'), level: 0, icon: 'mdi-warehouse' }];
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
    totalWeight() {
      return this.filteredBalances
        .filter(b => b.stockable_type !== 'PackageType')
        .reduce((s, b) => s + Number(b.remaining_weight || 0), 0);
    },
    totalPackages() {
      return this.filteredBalances
        .filter(b => b.stockable_type === 'PackageType')
        .reduce((s, b) => s + this.getRemainingPackageCount(b), 0);
    },
    totalPrice() {
      return this.filteredBalances.reduce((s, b) => {
        const w = Number(b.remaining_weight || 0);
        const wapVal = Number(b.wap || 0);
        const priceVal = wapVal > 0 ? wapVal * w : Number(b.total_price || 0);
        return s + priceVal;
      }, 0);
    },
    totalBalances() { return this.filteredBalances.length },
    totalStockIn() { return this.filteredBalances.reduce((s, b) => s + Number(b.total_weight || 0), 0) },
    totalStockOut() {
      return this.filteredBalances.reduce((s, b) => {
        return s + (b.operations || []).reduce((os, op) => os + Number(op.weight || 0), 0);
      }, 0);
    },
    // Per-type statistics
    warehouseStats() {
      const groups = {};
      this.filteredBalances.forEach(b => {
        const t = b.stockable_type || 'Other';
        if (!groups[t]) groups[t] = { type: t, name: this.typeName(t), count: 0, weight: 0, package_count: 0, price: 0 };
        const w = Number(b.remaining_weight || 0);
        const pkgs = this.getRemainingPackageCount(b);
        const wapVal = Number(b.wap || 0);
        const priceVal = wapVal > 0 ? wapVal * w : Number(b.total_price || 0);
        groups[t].count++;
        groups[t].weight += w;
        groups[t].package_count += pkgs;
        groups[t].price += priceVal;
      });
      return Object.values(groups).sort((a, b) => (b.type === 'PackageType' ? b.package_count : b.weight) - (a.type === 'PackageType' ? a.package_count : a.weight));
    },
    // Dashboard analytics chart
    dashChartSeries() {
      if (!this.filteredBalances.length || !this.dateFrom || !this.dateTo) return [{ name: '', data: [] }];
      const mode = this.dashChartMode || 'daily';
      const bucketKey = (dateStr) => {
        if (!dateStr) return null;
        const d = dateStr.substring(0, 10);
        if (mode === 'yearly') return d.substring(0, 4);
        if (mode === 'monthly') return d.substring(0, 7);
        return d; // daily
      };
      const dateMapBal = {}; // stock-in per bucket
      const dateMapOps = {}; // stock-out per bucket
      this.filteredBalances.forEach(b => {
        const d = b.date ? b.date.substring(0, 10) : null;
        if (d && d >= this.dateFrom && d <= this.dateTo) {
          const k = bucketKey(d);
          if (!dateMapBal[k]) dateMapBal[k] = 0;
          dateMapBal[k] += Number(b.total_weight || 0);
        }
        (b.operations || []).forEach(op => {
          const od = op.date ? op.date.substring(0, 10) : null;
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
        { name: this.$t('stock_in') + ' (Kg)', data: allKeys.map(k => ({ x: k, y: Math.round((dateMapBal[k] || 0) * 100) / 100 })) },
        { name: this.$t('stock_out') + ' (Kg)', data: allKeys.map(k => ({ x: k, y: Math.round((dateMapOps[k] || 0) * 100) / 100 })) },
      ];
    },
    dashChartOptions() {
      return {
        chart: { type: 'line', toolbar: { show: true }, zoom: { enabled: true }, background: 'transparent' },
        stroke: { width: [3, 3], curve: 'smooth' },
        colors: ['#5C6BC0', '#EF5350'],
        xaxis: { type: 'category', labels: { rotate: -45, style: { fontSize: '10px' } } },
        yaxis: [
          { title: { text: this.$t('stock_in') + ' (Kg)', style: { fontSize: '11px' } }, labels: { formatter: v => this.main.formatDisplayNumber(v) } },
          { show: false, labels: { formatter: v => this.main.formatDisplayNumber(v) } },
        ],
        tooltip: { shared: true },
        legend: { position: 'top', fontSize: '11px' },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
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
    // Chart options
    pieOptions() {
      return {
        chart: { type: 'pie', events: { dataPointSelection: (e, c, cfg) => this.onPieClick(cfg.dataPointIndex) }, background: 'transparent' },
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
        responsive: [{ breakpoint: 480, options: { chart: { width: 320 }, legend: { position: 'bottom' } } }],
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },
    lineOptions() {
      return {
        chart: { type: 'line', toolbar: { show: true }, zoom: { enabled: true }, background: 'transparent' },
        stroke: { width: 3, curve: 'smooth' },
        colors: ['#26A69A'],
        xaxis: { categories: this.lineCategories, labels: { rotate: -45, style: { fontSize: '10px' } } },
        yaxis: [
          { title: { text: this.$t('weight') + ' (Kg)' }, labels: { formatter: v => this.main.formatDisplayNumber(v) } },
        ],
        tooltip: { shared: true },
        legend: { position: 'top' },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },
  },
  mounted() {
    this.fetchAllWarehouses();
    mitt.on('model-changed', this.onModelChanged);
  },
  activated() { mitt.on('model-changed', this.onModelChanged); },
  deactivated() { mitt.off('model-changed', this.onModelChanged); },
  beforeUnmount() {
    mitt.off('model-changed', this.onModelChanged);
    if (this._refreshTimer) clearTimeout(this._refreshTimer);
  },
  methods: {
    onModelChanged(d) {
      if (['Warehouse', 'WarehouseBalance', 'WarehouseBalanceOperation', 'Charge', 'Production', 'Shipping', 'DetailCharge', 'DetailProduction', 'DetailShipping', 'WarehouseTransfer'].includes(d.model)) {
        if (this.selectedWarehouseIds.length) {
          if (this._refreshTimer) clearTimeout(this._refreshTimer);
          this._refreshTimer = setTimeout(() => { this.fetchDashboardData(); }, 800);
        }
      }
      if (d.model === 'Warehouse' && (d.action === 'created' || d.action === 'deleted')) this.fetchAllWarehouses();
    },
    async fetchAllWarehouses() {
      try {
        const r = await axios.get('/api/warehouses');
        this.allWarehouses = (r.data.warehouses || []).map(w => ({ ...w, title: w.name, value: w.id }));
      } catch (e) { console.error(e) }
    },
    async fetchDashboardData() {
      if (!this.selectedWarehouseIds.length) { this.balances = []; this.warehouses = []; return; }
      this.fetchLoading = true;
      try {
        const r = await axios.get('/api/warehouses/dashboard-data', { params: { warehouse_ids: this.selectedWarehouseIds } });
        this.warehouses = r.data.warehouses || [];
        this.balances = r.data.balances || [];
      } catch (e) { console.error(e) }
      this.fetchLoading = false;
    },
    onWarehouseChange() {
      this.drillLevel = 0; this.drillType = null; this.drillFamilyId = null; this.drillFamilyName = null; this.drillFishNameId = null; this.drillFishName = null;
      this.colFilters = {}; this.expandedBalances = {};
      this.fetchDashboardData();
    },
    drillIntoType(type) { this.drillType = type; this.drillLevel = 1; this.drillFamilyId = null; this.drillFamilyName = null; this.drillFishNameId = null; this.drillFishName = null; this.colFilters = {}; },
    drillIntoFamily(fam) { this.drillFamilyId = fam.id; this.drillFamilyName = fam.name; this.drillLevel = 2; this.drillFishNameId = null; this.drillFishName = null; this.colFilters = {}; },
    drillIntoFishName(fn) { this.drillFishNameId = fn.id; this.drillFishName = fn.name; this.drillLevel = 3; this.colFilters = {}; },
    drillTo(level) { this.drillLevel = level; if (level < 3) { this.drillFishNameId = null; this.drillFishName = null; } if (level < 2) { this.drillFamilyId = null; this.drillFamilyName = null; } if (level < 1) { this.drillType = null; } this.colFilters = {}; },
    toggleExpand(balanceId) { this.expandedBalances[balanceId] = !this.expandedBalances[balanceId]; this.expandedBalances = { ...this.expandedBalances }; },

    // --- Helpers --- 
    tColor(t) { return t === 'FishType' ? '#00ACC1' : t === 'GuanoType' ? '#FB8C00' : t === 'PackageType' ? '#6D4C41' : '#9E9E9E' },
    tIcon(t) { return t === 'FishType' ? 'mdi-fish' : t === 'GuanoType' ? 'mdi-leaf' : t === 'PackageType' ? 'mdi-package-variant' : 'mdi-cube-outline' },
    typeName(t) { return t === 'FishType' ? this.$t('fish') : t === 'GuanoType' ? this.$t('guano') : t === 'PackageType' ? this.$t('packages') : t },
    sLabel(balance) {
      const s = balance?.stockable;
      if (!s) return `#${balance?.stockable_id || '?'}`;
      return s[`name_${this.lang}`] || s.name || s.name_fr || s.name_en || `#${balance.stockable_id}`;
    },
    srcLabel(t) { return t === 'DetailCharge' ? this.$t('source_charge') : t === 'DetailProduction' ? this.$t('source_production') : t === 'DetailShipping' ? this.$t('source_shipping') : t === 'WarehouseTransfer' ? this.$t('source_warehouse_transfer') : t || '-' },
    srcColor(t) { return t === 'DetailCharge' ? 'blue' : t === 'DetailProduction' ? 'purple' : t === 'DetailShipping' ? 'orange' : t === 'WarehouseTransfer' ? '#F9A825' : 'grey' },
    goToSource(item) {
      const st = item.source_type;
      if (!st || !item.source_id) return;
      if (st === 'DetailCharge') this.$router.push({ name: 'edit_charge', params: { id: item.source_id } });
      else if (st === 'DetailProduction') this.$router.push({ name: 'edit_production', params: { id: item.source_id } });
      else if (st === 'DetailShipping') this.$router.push({ name: 'edit_shipping', params: { id: item.source_id } });
      else if (st === 'WarehouseTransfer') this.$router.push({ name: 'edit_warehouse_transfer', params: { id: item.source_id } });
    },
    goToSourceOp(op) {
      const st = op.source_type;
      if (!st || !op.source_id) return;
      if (st === 'DetailCharge') this.$router.push({ name: 'edit_charge', params: { id: op.source_id } });
      else if (st === 'DetailProduction') this.$router.push({ name: 'edit_production', params: { id: op.source_id } });
      else if (st === 'DetailShipping') this.$router.push({ name: 'edit_shipping', params: { id: op.source_id } });
      else if (st === 'WarehouseTransfer') this.$router.push({ name: 'edit_warehouse_transfer', params: { id: op.source_id } });
    },
    getRemainingPackageCount(bal) {
      if (!bal) return 0;
      const initial = Number(bal.package_count || 0);
      const out = (bal.operations || []).reduce((s, op) => s + Number(op.package_count || 0), 0);
      return Math.max(0, Math.round((initial - out) * 100) / 100);
    },

    onDateRangeVCChange(val) {
      if (val && val.start && val.end) {
        this.dateRangeVC = val;
      }
    },
    _dateStr(d) {
      if (!d) return '';
      if (typeof d === 'string') return d.substring(0, 10);
      return d.toISOString().substring(0, 10);
    },

    // === PIE CHART ===
    _genColors(n) {
      const palette = ['#5C6BC0', '#26A69A', '#FF7043', '#AB47BC', '#42A5F5', '#66BB6A', '#FFA726', '#EC407A', '#78909C', '#8D6E63', '#29B6F6', '#D4E157', '#7E57C2', '#26C6DA', '#FFCA28'];
      return Array.from({ length: n }, (_, i) => palette[i % palette.length]);
    },
    // Level 0 pie: article type -' show FISH FAMILIES
    openPieL0(type) {
      const bals = this.filteredBalances.filter(b => b.stockable_type === type);
      const groups = {};
      bals.forEach(b => {
        const fam = b.stockable?.fish_family;
        const fname = fam ? (fam[`name_${this.lang}`] || fam.name || fam.name_fr || 'Unknown') : (this.sLabel(b) || this.$t('unknown'));
        const fid = fam?.id || b.stockable_id || 0;
        if (!groups[fid]) groups[fid] = { id: fid, name: fname, weight: 0 };
        groups[fid].weight += Number(b.remaining_weight || 0);
      });
      const items = Object.values(groups).filter(g => g.weight > 0);
      this._openPie(items, this.typeName(type));
    },
    // Level 1 pie: family -' show FISH ARTICLES (fish types)
    openPieL1(fam) {
      const bals = this.filteredBalances.filter(b => b.stockable_type === 'FishType' && (b.stockable?.fish_family_id || 0) === fam.id);
      const groups = {};
      bals.forEach(b => {
        const nm = this.sLabel(b);
        const sid = b.stockable_id;
        if (!groups[sid]) groups[sid] = { id: sid, name: nm, weight: 0 };
        groups[sid].weight += Number(b.remaining_weight || 0);
      });
      const items = Object.values(groups).filter(g => g.weight > 0);
      this._openPie(items, fam.name);
    },
    // Level 2 pie: fish article -' show BALANCE OPERATIONS
    openPieL2(bal) {
      const ops = bal.operations || [];
      if (!ops.length) {
        // No operations - show single balance as 100%
        const items = [{ name: bal.reception_tracking_number || '-', weight: Math.round(Number(bal.remaining_weight) * 100) / 100, _leaf: true }];
        this._openPie(items, this.sLabel(bal));
        return;
      }
      const items = ops.map(op => ({
        name: `${this.srcLabel(op.source_type)} #${op.source_id || '-'} (${this.main.formateDate(op.date)})`,
        weight: Math.round(Number(op.weight || 0) * 100) / 100,
        _leaf: true,
      }));
      // Add remaining stock as a slice
      const remain = Number(bal.remaining_weight || 0);
      if (remain > 0) items.push({ name: this.$t('remaining_weight'), weight: Math.round(remain * 100) / 100, _leaf: true });
      this._openPie(items, (bal.articleName || this.sLabel(bal)) + ' - ' + (bal.reception_tracking_number || ''));
    },
    _openPie(items, titlePrefix) {
      this.pieDrillStack = [];
      this.pieDrillData = items;
      this.pieLabels = items.map(i => i.name);
      this.pieSeries = items.map(i => i.weight);
      this.pieColors = this._genColors(items.length);
      this.chartTitle = titlePrefix + ' - ' + this.$t('stock_distribution');
      this.chartMode = 'pie';
      this.chartDialog = true;
    },
    onPieClick(idx) {
      // No drill-down needed since each level now shows its correct data directly
      return;
    },
    pieDrillBack() {
      if (!this.pieDrillStack.length) return;
      const prev = this.pieDrillStack.pop();
      this.pieDrillData = prev.data;
      this.pieLabels = prev.labels;
      this.pieSeries = prev.series;
      this.pieColors = prev.colors;
      this.chartTitle = prev.title;
    },

    // === LINE CHART ===
    openLineChart(context) {
      // context: { type: 'family'|'article', familyId, familyName, stockableId, articleName }
      const bals = this.filteredBalances.filter(b => {
        if (context.type === 'family') return b.stockable_type === 'FishType' && (b.stockable?.fish_family_id || 0) === context.familyId;
        return b.stockable_type === 'FishType' && b.stockable_id === context.stockableId;
      });
      // Group operations by date within range
      const dateMap = {};
      bals.forEach(b => {
        const d = b.date ? b.date.substring(0, 10) : null;
        if (d && d >= this.dateFrom && d <= this.dateTo) {
          if (!dateMap[d]) dateMap[d] = { weight: 0 };
          dateMap[d].weight += Number(b.total_weight || 0);
        }
        // Also include operations
        (b.operations || []).forEach(op => {
          const od = op.date ? op.date.substring(0, 10) : null;
          if (od && od >= this.dateFrom && od <= this.dateTo) {
            if (!dateMap[od]) dateMap[od] = { weight: 0 };
            dateMap[od].weight -= Number(op.weight || 0);
          }
        });
      });
      const dates = Object.keys(dateMap).sort();
      this.lineCategories = dates;
      this.lineSeries = [
        { name: this.$t('weight') + ' (Kg)', data: dates.map(d => Math.round(dateMap[d].weight * 100) / 100) },
      ];
      this.chartTitle = (context.familyName || context.articleName) + ' - ' + this.$t('stock_history');
      this.chartMode = 'line';
      this.chartDialog = true;
    },



    // === Filters ===
    applyFilters(rows, levelKey) {
      const filters = this.colFilters[levelKey];
      if (!filters) return rows;
      let result = [...rows];
      Object.entries(filters).forEach(([key, vals]) => { if (vals && vals.length) result = result.filter(r => vals.includes(String(r[key] ?? '-'))); });
      return result;
    },
    setFilter(levelKey, col, vals) { if (!this.colFilters[levelKey]) this.colFilters[levelKey] = {}; this.colFilters[levelKey][col] = vals; this.colFilters = { ...this.colFilters }; },
    clearFilter(levelKey, col) { if (this.colFilters[levelKey]) { delete this.colFilters[levelKey][col]; this.colFilters = { ...this.colFilters }; } },
    filterOpts(col, rows) { const vals = new Set(); rows.forEach(r => vals.add(String(r[col] ?? '-'))); return [...vals].sort(); },
  },
};

