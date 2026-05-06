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
export default {
  name: 'ChargeProductionManagement',
  data() {
    return {
      charges: [], loading: false,
      colFilters: {}, colSorts: [], pinState: {},
      expanded: {},
      filterMenus: {},
      columnOrder: null,
      dragCol: null, dragOverCol: null,
      colWidths: {},
      chartMode: 'daily',
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
    catName() {
      return (id) => {
        const l = this.main.lang?.code || 'en';
        const c = this.cats.find(x => x.id === id);
        return c ? (c[`name_${l}`] || c.name || `#${id}`) : `#${id}`;
      };
    },
    allHeaders() {
      return [
        { title: '', key: 'expand', sortable: false, width: 40, fixed: true },
        { title: this.$t('date'), key: 'date', align: 'center' },
        { title: this.$t('charge_category'), key: 'charge_category_id', align: 'center' },
        { title: this.$t('suppliers'), key: '_suppliers', align: 'center' },
        { title: this.$t('reception_tracking_number'), key: '_tracking', align: 'center' },
        { title: this.$t('tracking_onp_numbers'), key: '_onp_tracking', align: 'center' },
        { title: this.$t('fishery'), key: '_fishery', align: 'center' },
        { title: this.$t('origin'), key: '_origin', align: 'center' },
        { title: this.$t('ship'), key: '_ship', align: 'center' },
        { title: this.$t('trucks'), key: '_trucks', align: 'center' },
        { title: this.$t('total_weight'), key: 'total_weight', align: 'center' },
        { title: this.$t('total_price'), key: 'total_price', align: 'center' },
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
    colFilteredItems() {
      let items = this.charges;
      Object.entries(this.colFilters).forEach(([k, vals]) => {
        if (!vals || !vals.length) return;
        items = items.filter(it => vals.includes(this.cellText(k, it)));
      });
      return this.sortItems(items);
    },
    totalWeight() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_weight || 0), 0) },
    totalPrice() { return this.colFilteredItems.reduce((s, c) => s + Number(c.total_price || 0), 0) },
    categoryStats() {
      const lang = this.main.lang?.code || 'en';
      const map = {};
      this.colFilteredItems.forEach(c => {
        const catId = c.charge_category_id;
        if (!map[catId]) {
          const cat = this.cats.find(x => x.id === catId);
          map[catId] = { name: cat ? (cat[`name_${lang}`] || cat.name || `#${catId}`) : `#${catId}`, weight: 0, price: 0, count: 0 };
        }
        map[catId].weight += Number(c.total_weight || 0);
        map[catId].price += Number(c.total_price || 0);
        map[catId].count++;
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
        colors: ['#26A69A', '#FF7043'],
        xaxis: { type: 'category', labels: { style: { fontSize: '10px', fontWeight: 600 }, rotate: -45, rotateAlways: this.chartMode === 'daily' }, tooltip: { enabled: false } },
        yaxis: [
          { title: { text: this.$t('total_weight') + ' (Kg)', style: { fontSize: '11px', fontWeight: 700, color: '#26A69A' } }, labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
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
    this.fetchCharges();
    mitt.on('model-changed', this.onMC);
  },
  beforeUnmount() { mitt.off('model-changed', this.onMC) },
  methods: {
    onMC(d) { if (d.model === 'Charge' || d.model === 'Production') this.fetchCharges() },
    async fetchCharges() {
      this.loading = true;
      try {
        const r = await axios.get('/api/charges/production-charges');
        const lang = this.main.lang?.code || 'en';
        this.charges = (r.data.charges || []).map(c => ({
          ...c,
          _suppliers: (c.suppliers || []).map(s => s.name).join(', ') || '-',
          _tracking: (c.reception_tracking_number || []).join(', ') || '-',
          _onp_tracking: (c.tracking_onp_numbers || []).join(', ') || '-',
          _fishery: c.fishery ? (c.fishery[`name_${lang}`] || c.fishery.name || '-') : '-',
          _origin: c.origin ? (c.origin[`name_${lang}`] || c.origin.name || '-') : '-',
          _ship: c.ship ? (c.ship[`name_${lang}`] || c.ship.name || '-') : '-',
          _trucks: (c.trucks || []).map(t => t.name || t[`name_${lang}`]).join(', ') || '-',
        }));
      } catch (e) { console.error(e) }
      this.loading = false;
    },
    cellText(key, item) {
      if (key === 'charge_category_id') return this.catName(item.charge_category_id);
      if (key === 'date') return this.main.formateDate(item.date);
      const v = item[key]; return v == null ? '-' : String(v);
    },
    filterOpts(key) {
      const other = Object.entries(this.colFilters).filter(([k]) => k !== key);
      let items = this.charges;
      other.forEach(([k, vals]) => { if (vals && vals.length) items = items.filter(it => vals.includes(this.cellText(k, it))) });
      const s = new Set(); items.forEach(i => s.add(this.cellText(key, i)));
      return [...s].sort();
    },
    toggleSort(key) { const i = this.colSorts.findIndex(s => s.key === key); if (i === -1) this.colSorts.push({ key, dir: 'asc' }); else if (this.colSorts[i].dir === 'asc') this.colSorts[i].dir = 'desc'; else this.colSorts.splice(i, 1) },
    sortDir(key) { const s = this.colSorts.find(x => x.key === key); return s ? s.dir : null },
    sortIdx(key) { return this.colSorts.findIndex(x => x.key === key) + 1 },
    sortItems(items) {
      if (!this.colSorts.length) return items;
      return [...items].sort((a, b) => { for (const { key, dir } of this.colSorts) { let va = key === 'charge_category_id' ? this.catName(a[key]) : a[key], vb = key === 'charge_category_id' ? this.catName(b[key]) : b[key]; if (va == null) va = ''; if (vb == null) vb = ''; const n = typeof va === 'number' && typeof vb === 'number'; if (n) { if (va !== vb) return dir === 'asc' ? va - vb : vb - va } else { const c = String(va).localeCompare(String(vb)); if (c) return dir === 'asc' ? c : -c } } return 0 });
    },
    togglePin(k) { const cur = this.pinState[k]; if (!cur) this.pinState[k] = 'left'; else if (cur === 'left') this.pinState[k] = 'right'; else delete this.pinState[k]; this.pinState = { ...this.pinState } },
    isPinned(k) { return !!this.pinState[k] },
    pinSide(k) { return this.pinState[k] || null },
    getPinLeft(key) { let o = 0; for (const h of this.headers) { if (h.key === key) break; if (this.pinState[h.key] === 'left') o += (this.colWidths[h.key] || 150) } return o },
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
    groupDetails(charge) {
      const groups = {};
      (charge.detail_charges || []).forEach(dc => {
        const catId = dc.detail_charge_category_id;
        if (!groups[catId]) groups[catId] = { category: dc.detail_charge_category, items: [], totalW: 0, totalP: 0 };
        groups[catId].items.push(dc);
        groups[catId].totalW += Number(dc.weight || 0);
        groups[catId].totalP += Number(dc.total_price || 0);
      });
      return Object.values(groups);
    },
    articleName(dc) {
      if (!dc.stockable_type || dc.stockable_type === 'text') return dc.article_description || '-';
      const s = dc.stockable; return s ? (s.name || s[`name_${this.main.lang?.code}`] || '-') : '-';
    },
    articleFamily(dc) {
      if (dc.stockable_type === 'FishType' && dc.stockable) {
        const fam = this.fishFamilies.find(f => f.id === dc.stockable.fish_family_id);
        return fam ? (fam[`name_${this.main.lang?.code}`] || fam.name || '-') : '-';
      }
      return '-';
    },
    createProduction(chargeId) {
      this.$router.push({ name: 'add_production', query: { charge_id: chargeId } });
    },
  },
};
