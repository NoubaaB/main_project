import axios from 'axios';
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
export default {
  name: 'ListProduction',
  data() {
    const n = new Date(), sm = new Date(n.getFullYear(), n.getMonth(), 1);
    return {
      productions: [], loading: false, selected: [],
      dateRange: [sm, n], dateMenu: false,
      colFilters: {}, colSorts: [], pinState: {},
      expanded: {},
      showAdvanced: false, showInvoice: false,
      advFilter: { articleType: null, fishFamilies: [], fishFreshFrozen: [], fishProcessTypes: [], guanoTypes: [], packageTypes: [], warehouses: [] },
      invForm: { date: null, type_invoice: null, invoiceKind: 'supplier', trackingNumbers: [], trackingInput: '' },
      invChecking: false,
      deleteDialog: false, deleting: false, deleteId: null, deleteBulk: false,
      filterMenus: {},
      columnOrder: null, dragCol: null, dragOverCol: null,
      colWidths: {},
      chartMode: 'daily',
      analyticsData: null,
      analyticsLoading: false,
      closedYears: [],
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
    colFilteredItems() {
      let items = this.productions;
      if (this.showAdvanced) { const af = this.advFilter; if (af.warehouses.length || af.articleType || af.fishFamilies.length || af.fishProcessTypes.length || af.guanoTypes.length || af.packageTypes.length) { items = items.filter(p => { const dcs = p.detail_productions || []; return dcs.some(dc => { if (af.warehouses.length && !af.warehouses.includes(dc.warehouse_id)) return false; if (af.articleType) { if (af.articleType === 'text' && dc.stockable_type) return false; if (af.articleType === 'FishType' && dc.stockable_type !== 'FishType') return false; if (af.articleType === 'GuanoType' && dc.stockable_type !== 'GuanoType') return false; if (af.articleType === 'PackageType' && dc.stockable_type !== 'PackageType') return false } if (af.articleType === 'FishType') { const ft = this.fishTypes.find(f => f.id === dc.stockable_id); if (ft) { if (af.fishFamilies.length && !af.fishFamilies.includes(ft.fish_family_id)) return false; if (af.fishFreshFrozen.length && !af.fishFreshFrozen.includes(ft.type)) return false; if (af.fishProcessTypes.length && !af.fishProcessTypes.includes(ft.fish_process_type_id)) return false } } if (af.articleType === 'GuanoType' && af.guanoTypes.length && !af.guanoTypes.includes(dc.stockable_id)) return false; if (af.articleType === 'PackageType' && af.packageTypes.length && !af.packageTypes.includes(dc.stockable_id)) return false; return true }) }) } }
      Object.entries(this.colFilters).forEach(([k, vals]) => { if (!vals || !vals.length) return; items = items.filter(it => vals.includes(this.cellText(k, it))) });
      return this.sortItems(items);
    },
    totalWeight() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_weight || 0), 0) },
    totalPrice() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_price || 0), 0) },
    totalInvoiced() { return this.colFilteredItems.filter(c => c._is_invoiced).length },
    totalNotInvoiced() { return this.colFilteredItems.filter(c => !c._is_invoiced).length },
    totalInvoiceWeight() { return this.colFilteredItems.filter(c => c._is_invoiced).reduce((s, c) => s + Number(c.total_weight || 0), 0) },
    totalInvoicePrice() { return this.colFilteredItems.filter(c => c._is_invoiced).reduce((s, c) => s + Number(c.total_price || 0), 0) },
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
    categoryStats() {
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
  mounted() { this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key); this.fetchProductions(); this.fetchAnalytics(); this.fetchClosedYears(); mitt.on('model-changed', this.onMC) },
  activated() { this.fetchProductions(); this.fetchAnalytics(); this.fetchClosedYears(); },
  beforeUnmount() { mitt.off('model-changed', this.onMC) },
  watch: {
    chartMode() { this.fetchAnalytics() },
  },
  methods: {
    onMC(d) {
      if (d.model === 'Production') {
        const rd = d.record_data;
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
          this.fetchAnalytics();
        } else if (d.action === 'deleted' && d.record_id) {
          this.productions = this.productions.filter(c => c.id !== d.record_id);
          this.fetchAnalytics();
        }
      } else if (d.model === 'SupplierInvoice' || d.model === 'ClientInvoice') {
        this.fetchProductions(); this.fetchAnalytics();
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
        const r = await axios.get('/api/productions/analytics', { params: { date_start: this.fmtDate(ds), date_end: this.fmtDate(de), mode: this.chartMode } });
        this.analyticsData = r.data;
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
        const r = await axios.get('/api/productions', { params: { date_start: this.fmtDate(ds), date_end: this.fmtDate(de) } });
        this.productions = (r.data.productions || []).map(c => this.mapProduction(c));
      } catch (e) { console.error(e) }
      this.loading = false;
    },
    fmtDate(d) { if (!d) return ''; if (typeof d === 'string') return d; const y = d.getFullYear(); const m = String(d.getMonth() + 1).padStart(2, '0'); const day = String(d.getDate()).padStart(2, '0'); return `${y}-${m}-${day}` },
    cellText(key, item) {
      if (key === 'production_category_id') return this.catName(item.production_category_id);
      if (key === 'date') return this.main.formateDate(item.date);
      if (key === 'production_type') return item.production_type || '-';
      const v = item[key]; return v == null ? '-' : String(v);
    },
    filterOpts(key) { const other = Object.entries(this.colFilters).filter(([k]) => k !== key); let items = this.productions; other.forEach(([k, vals]) => { if (vals && vals.length) items = items.filter(it => vals.includes(this.cellText(k, it))) }); const s = new Set(); items.forEach(i => s.add(this.cellText(key, i))); return [...s].sort() },
    toggleSort(key) { const i = this.colSorts.findIndex(s => s.key === key); if (i === -1) this.colSorts.push({ key, dir: 'asc' }); else if (this.colSorts[i].dir === 'asc') this.colSorts[i].dir = 'desc'; else this.colSorts.splice(i, 1) },
    sortDir(key) { const s = this.colSorts.find(x => x.key === key); return s ? s.dir : null },
    sortIdx(key) { return this.colSorts.findIndex(x => x.key === key) + 1 },
    sortItems(items) { if (!this.colSorts.length) return items; return [...items].sort((a, b) => { for (const { key, dir } of this.colSorts) { let va = key === 'production_category_id' ? this.catName(a[key]) : a[key], vb = key === 'production_category_id' ? this.catName(b[key]) : b[key]; if (va == null) va = ''; if (vb == null) vb = ''; const n = typeof va === 'number' && typeof vb === 'number'; if (n) { if (va !== vb) return dir === 'asc' ? va - vb : vb - va } else { const c = String(va).localeCompare(String(vb)); if (c) return dir === 'asc' ? c : -c } } return 0 }) },
    togglePin(k) { const cur = this.pinState[k]; if (!cur) this.pinState[k] = 'left'; else if (cur === 'left') this.pinState[k] = 'right'; else delete this.pinState[k]; this.pinState = { ...this.pinState } },
    isPinned(k) { return !!this.pinState[k] },
    pinSide(k) { return this.pinState[k] || null },
    getPinLeft(key) { let o = this.colWidths['__checkbox'] || 50; for (const h of this.headers) { if (h.key === key) break; if (this.pinState[h.key] === 'left') o += (this.colWidths[h.key] || 150) } return o },
    getPinRight(key) { let o = 0; const rev = [...this.headers].reverse(); for (const h of rev) { if (h.key === key) break; if (this.pinState[h.key] === 'right') o += (this.colWidths[h.key] || 150) } return o },
    toggleExpand(id) { this.expanded[id] = !this.expanded[id]; this.expanded = { ...this.expanded } },
    onDragStart(key) { this.dragCol = key },
    onDragOver(key, e) { e.preventDefault(); this.dragOverCol = key },
    onDragEnd() { if (this.dragCol && this.dragOverCol && this.dragCol !== this.dragOverCol) { const order = [...this.columnOrder]; const fi = order.indexOf(this.dragCol); const ti = order.indexOf(this.dragOverCol); if (fi > -1 && ti > -1) { order.splice(fi, 1); order.splice(ti, 0, this.dragCol); this.columnOrder = order } } this.dragCol = null; this.dragOverCol = null },
    groupDetails(prod) { const groups = {}; (prod.detail_productions || []).forEach(dc => { const catId = dc.detail_production_category_id; if (!groups[catId]) groups[catId] = { category: dc.detail_production_category, items: [], totalW: 0, totalP: 0 }; groups[catId].items.push(dc); groups[catId].totalW += Number(dc.weight || 0); groups[catId].totalP += Number(dc.total_price || 0) }); return Object.values(groups) },
    articleName(dc) { if (!dc.stockable_type || dc.stockable_type === 'text') return dc.article_description || '-'; const s = dc.stockable; return s ? (s.name || s[`name_${this.main.lang?.code}`] || '-') : '-' },
    articleFamily(dc) { if (dc.stockable_type === 'FishType' && dc.stockable) { const fam = this.fishFamilies.find(f => f.id === dc.stockable.fish_family_id); return fam ? (fam[`name_${this.main.lang?.code}`] || fam.name || '-') : '-' } return '-' },
    confirmDelete(id) { this.deleteId = id; this.deleteBulk = false; this.deleteDialog = true },
    confirmBulkDelete() { this.deleteBulk = true; this.deleteDialog = true },
    async execDelete() { this.deleting = true; try { const ids = this.deleteBulk ? this.selected : [this.deleteId]; await axios.delete('/api/productions', { data: { ids } }); this.main.add_snackbar({ show: true, message: this.$t('productions_deleted_successfully'), color: 'success' }); this.selected = []; this.deleteDialog = false; this.fetchProductions() } catch (e) { console.error(e) } this.deleting = false },
    async onTrackingUpdate(val) { if (!val || !val.length) return; const raw = val[val.length - 1]; if (!raw || !raw.trim()) return; let v = raw.trim(); const y = String(new Date().getFullYear()).slice(-2); if (!v.startsWith(y + '-')) v = y + '-' + v.replace(/^0+/, '').padStart(4, '0'); this.invForm.trackingNumbers.splice(this.invForm.trackingNumbers.length - 1, 1); if (this.invForm.trackingNumbers.includes(v)) { this.main.add_snackbar({ show: true, message: this.$t('tracking_number_exists_in_invoice'), color: 'warning' }); return; } this.invChecking = true; const ep = this.invForm.invoiceKind === 'supplier' ? '/api/supplier_invoice/check-tracking-number' : '/api/client_invoice/check-tracking-number'; try { const r = await axios.get(ep, { params: { tracking_number: v } }); if (r.data.exists) { this.main.add_snackbar({ show: true, message: this.$t('tracking_number_exists_in_invoice'), color: 'warning' }) } else { this.invForm.trackingNumbers.push(v) } } catch (e) { console.error(e) } this.invChecking = false },
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
    cancelInvoice() { this.selected = []; this.invForm = { date: null, type_invoice: null, trackingNumbers: [], trackingInput: '', invoiceKind: 'supplier' }; this.showInvoice = false; },
    exportExcel() { const data = this.colFilteredItems.map(c => { const row = {}; this.headers.filter(h => h.key !== 'actions' && h.key !== 'expand').forEach(h => { row[h.title] = this.cellText(h.key, c) }); return row }); const ws = XLSX.utils.json_to_sheet(data); const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Productions'); XLSX.writeFile(wb, `productions_${this.today}.xlsx`) },
  },
};
