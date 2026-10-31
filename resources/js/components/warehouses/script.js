import axios from 'axios';
import moment from 'moment';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import mitt from '@/plugins/mitt.js';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import WarehousePrintDialog from './WarehousePrintDialog.vue';
import TableColumnFilter from '@/components/common/TableColumnFilter.vue';
import {
  gqlGetChambersOverview,
  gqlGetSpeciesOverview,
  gqlGetStockParLot,
  gqlGetStockMovementsJournal,
  gqlGetWarehouseAnalytics,
  gqlGetTraceabilityVouchers,
  gqlGetTraceabilityVoucherArticles,
  gqlGetTraceabilityPedigreeTimeline,
  gqlGetLotTraceability,
  gqlGetWarehouses,
} from '@/services/warehouseGraphql.js';

export default {
  name: 'WarehouseStockParLotDashboard',
  components: {
    SegmentedPillToggle,
    DatePicker,
    WarehousePrintDialog,
    TableColumnFilter,
  },
  data() {
    const today = moment().toDate();
    const d30 = moment().subtract(30, 'days').toDate();

    return {
      // Print Layout Customizer Modal State
      printModalOpen: false,
      printModalTargetView: 'stock_par_lot',

      // Multi-Column Header Filters for View 1 (Stock Par Lot)
      lotColFilters: {
        lot_number: [],
        status: [],
        date: [],
        supplier: [],
        chamber: [],
        species: [],
        rem_weight: [],
        boxes: [],
        valuation: [],
      },

      // Multi-Column Header Filters for View 3 (Species)
      speciesColFilters: {
        article_name: [],
        family_name: [],
        process_name: [],
        rem_weight: [],
        boxes: [],
        wap: [],
        valuation: [],
      },

      // Multi-Column Header Filters for View 4 (Movements Journal)
      journalColFilters: {
        date: [],
        direction: [],
        lot_number: [],
        article_name: [],
        warehouse_name: [],
        source_type: [],
        partner_name: [],
        weight: [],
        boxes: [],
      },

      // Warehouses
      warehouses: [],
      allWarehouses: [],
      selectedWarehouseIds: [],
      loading: false,
      fetchLoading: false,
      _refreshTimer: null,

      // View Mode: 'stock_par_lot' (default), 'chambers', 'species', 'journal', 'analytics'
      activeViewMode: 'stock_par_lot',
      tableDensity: localStorage.getItem('wh_table_density') || 'spacious',

      // Search & Filters for Stock Par Lot
      searchQuery: '',
      filterStatus: 'in_stock', // 'in_stock', 'partially_out', 'depleted', 'all'
      filterStockableType: null, // 'FishType', 'GuanoType', 'PackageType', null
      filterStockableTypes: [], // ['FishType', 'GuanoType', 'PackageType']
      sortBy: 'date', // 'date', 'lot', 'rem_weight', 'age', 'valuation'
      sortDir: 'desc',

      // Dedicated View 6: 3-Stage Traceability & Chronological History state
      traceabilityCurrentStage: 1, // 1: Search & Choose Voucher, 2: Select Articles, 3: Chronological Pedigree Timeline
      traceabilityTrackingNumbers: [], // e.g. ['511', '514']
      traceabilityTrackingSearchInput: '',
      traceabilityStatus: 'all', // 'all', 'in_stock', 'partially_out', 'depleted'
      traceabilityVouchersList: [],
      traceabilityVouchersLoading: false,
      selectedTraceabilityVoucher: null, // chosen entrance voucher object
      traceabilityArticlesList: [],
      traceabilityArticlesLoading: false,
      selectedTraceabilityArticleIds: [], // balance IDs selected in Stage 2
      traceabilityTimelineData: null,
      traceabilityTimelineLoading: false,
      traceabilityTimelineFilter: 'all', // 'all', 'inflow', 'outflow', 'transfer', 'production', 'shipping'
      traceabilityTimelineSortOrder: 'asc', // 'asc' or 'desc'
      quickTraceabilityLots: [],
      // Legacy compatibility
      traceabilityLotsList: [],
      traceabilityLotsLoading: false,
      traceabilitySearch: '',
      selectedTraceabilityLotNumber: null,
      traceabilityActiveLotData: null,
      traceabilityActiveLotLoading: false,

      // View 1: Stock Par Lot state
      lots: [],
      lotsSummary: {
        total_lots: 0,
        in_stock_lots: 0,
        depleted_lots: 0,
        total_initial_weight: 0,
        total_remaining_weight: 0,
        total_exited_weight: 0,
        total_initial_boxes: 0,
        total_valuation: 0,
        occupied_chambers: 0,
      },
      lotsPagination: {
        current_page: 1,
        per_page: 25,
        total: 0,
        last_page: 1,
      },
      lotsLoading: false,
      expandedLots: {},
      expandedArticles: {},

      // Lot Traceability Modal state
      traceabilityDialog: false,
      traceabilityLoading: false,
      selectedLotForTraceability: null,
      traceabilityData: null,

      // View 2: Chambers Overview state
      chambersList: [],
      chambersLoading: false,
      chamberSearch: '',

      // View 3: Species Overview state
      speciesList: [],
      speciesLoading: false,
      speciesSearch: '',
      speciesFilterType: 'all',

      // View 4: Movement Ledger (Journal) state with Full-Width VCalendar and NO pagination
      movementDateRange: { start: d30, end: today },
      calendarExpanded: true,
      activeMovementPreset: '30d',
      journalList: [],
      journalTotals: {
        total_count: 0,
        total_in_weight: 0,
        total_out_weight: 0,
        net_weight: 0,
        total_in_boxes: 0,
        total_out_boxes: 0,
      },
      journalLoading: false,
      journalFilterType: 'all', // 'all', 'in', 'out'
      journalSearch: '',
      journalPage: 1,
      journalPerPage: 50,
      journalPerPageOptions: [25, 50, 100, 250],

      // View 5: Analytics & Intelligence state
      dashChartMode: 'daily',
      analyticsDateRange: { start: d30, end: today },
      analyticsCalendarExpanded: false,
      activeAnalyticsPreset: '30d',
      analyticsLoading: false,
      analyticsGranularity: 'daily', // 'daily', 'weekly', 'monthly'
      analyticsChartType: 'area', // 'area', 'bar', 'line'
      analyticsChartMetric: 'flows', // 'flows', 'cumulative', 'valuation', 'boxes'
      analyticsSpeciesMetric: 'weight', // 'weight', 'valuation'
      analyticsAgingMetric: 'weight', // 'weight', 'valuation'
      analyticsActiveTab: 'timeline', // 'timeline', 'species', 'chambers', 'partners', 'aging'
      analyticsTableSearch: '',
      analyticsData: {
        date_range: { from: '', to: '', days_count: 30 },
        granularity: 'daily',
        summary: {
          in_weight: 0,
          out_weight: 0,
          net_weight: 0,
          in_boxes: 0,
          out_boxes: 0,
          net_boxes: 0,
          in_valuation: 0,
          out_valuation: 0,
          net_valuation: 0,
          movements_count: 0,
          in_count: 0,
          out_count: 0,
          active_lots_count: 0,
        },
        ratios: {
          destocking_rate: 0,
          daily_avg_in: 0,
          daily_avg_out: 0,
          avg_in_price: 0,
          avg_out_price: 0,
        },
        aging: {
          total_lots: 0,
          total_items: 0,
          total_weight: 0,
          total_valuation: 0,
          avg_age_days: 0,
          buckets: {
            fresh: { label: '', weight: 0, valuation: 0, lots: 0, pct: 0, color: '#00897B' },
            optimal: { label: '', weight: 0, valuation: 0, lots: 0, pct: 0, color: '#1E88E5' },
            warning: { label: '', weight: 0, valuation: 0, lots: 0, pct: 0, color: '#FB8C00' },
            alert: { label: '', weight: 0, valuation: 0, lots: 0, pct: 0, color: '#E53935' },
          },
        },
        timeline: [],
        species: [],
        families: [],
        chambers: [],
        partners: { suppliers: [], clients: [] },
        tracking_numbers: [],
      },

      // Rule 5: Summary Snackbar with Countdown Progress Bar
      summarySnackbar: {
        show: false,
        title: '',
        message: '',
        color: '#a21ee5',
        icon: 'mdi-check-circle',
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

    movementDateFrom() {
      if (!this.movementDateRange?.start) return null;
      return moment(this.movementDateRange.start).format('YYYY-MM-DD');
    },

    movementDateTo() {
      if (!this.movementDateRange?.end) return null;
      return moment(this.movementDateRange.end).format('YYYY-MM-DD');
    },

    displayMovementRangeText() {
      if (!this.movementDateFrom && !this.movementDateTo) {
        return this.tLabel('all_history', 'Tout l\'Historique');
      }
      const from = this.movementDateFrom ? this.main.formateDate(this.movementDateFrom) : '...';
      const to = this.movementDateTo ? this.main.formateDate(this.movementDateTo) : '...';
      return `${from}  ➔  ${to}`;
    },

    viewModeOptions() {
      return [
        {
          value: 'stock_par_lot',
          label: this.tLabel('view_stock_par_lot', 'N° de Suivi de Réception'),
          icon: 'mdi-cube-outline',
          badge: this.lotsSummary.in_stock_lots || null,
        },
        {
          value: 'chambers',
          label: this.tLabel('view_chambers_overview', 'Par Chambre Froide'),
          icon: 'mdi-snowflake',
          badge: this.occupiedChambersCount || null,
        },
        {
          value: 'species',
          label: this.tLabel('view_species_overview', 'Par Espèce & Article'),
          icon: 'mdi-fish',
          badge: this.speciesList.length || null,
        },
        {
          value: 'journal',
          label: this.tLabel('view_movements_journal', 'Journal des Mouvements'),
          icon: 'mdi-book-clock-outline',
          badge: this.journalTotals.total_count || this.journalList.length || null,
        },
        {
          value: 'analytics',
          label: this.tLabel('view_stock_analytics', 'Analytique & Graphiques'),
          icon: 'mdi-chart-areaspline',
          badge: null,
        },
        {
          value: 'traceability',
          label: this.tLabel('view_traceability', 'Traçabilité & Chronologie'),
          icon: 'mdi-timeline-clock-outline',
          badge: this.traceabilityLotsList.length || null,
        },
      ];
    },

    statusToggleOptions() {
      return [
        { value: 'in_stock', label: this.tLabel('in_stock_label', 'En Stock'), icon: 'mdi-check-circle-outline' },
        { value: 'partially_out', label: this.tLabel('partially_out', 'En Cours'), icon: 'mdi-progress-clock' },
        { value: 'depleted', label: this.tLabel('depleted', 'Épuisé'), icon: 'mdi-archive-arrow-down-outline' },
        { value: 'all', label: this.tLabel('all', 'Tout'), icon: 'mdi-select-all' },
      ];
    },

    movementPresetOptions() {
      return [
        { value: 'today', label: this.tLabel('today', 'Aujourd\'hui') },
        { value: '7d', label: this.tLabel('last_7_days', '7j') },
        { value: 'this_month', label: this.tLabel('this_month', 'Ce Mois') },
        { value: 'last_month', label: this.tLabel('last_month', 'Mois Dernier') },
        { value: '30d', label: this.tLabel('last_30_days', '30j') },
        { value: '90d', label: this.tLabel('last_90_days', '90j') },
        { value: 'all', label: this.tLabel('all_history', 'Tout') },
      ];
    },

    journalDirectionToggleOptions() {
      return [
        { value: 'all', label: this.tLabel('all', 'Tous'), icon: 'mdi-swap-vertical' },
        { value: 'in', label: this.tLabel('movement_in', 'Entrées (IN)'), icon: 'mdi-arrow-down-bold' },
        { value: 'out', label: this.tLabel('movement_out', 'Sorties (OUT)'), icon: 'mdi-arrow-up-bold' },
      ];
    },

    speciesTypeToggleOptions() {
      return [
        { value: 'all', label: this.tLabel('all', 'Tout'), icon: 'mdi-select-all' },
        { value: 'FishType', label: this.tLabel('FishType', 'Poissons'), icon: 'mdi-fish' },
        { value: 'GuanoType', label: this.tLabel('GuanoType', 'Guano'), icon: 'mdi-sprout' },
        { value: 'PackageType', label: this.tLabel('PackageType', 'Emballages'), icon: 'mdi-package-variant-closed' },
      ];
    },

    stockableTypeOptions() {
      return [
        { label: this.tLabel('all_articles', 'Tous les Articles'), value: null },
        { label: this.tLabel('FishType', 'Poissons (Frais & Congelés)'), value: 'FishType' },
        { label: this.tLabel('GuanoType', 'Guano & Sous-produits'), value: 'GuanoType' },
        { label: this.tLabel('PackageType', 'Emballages & Cartons'), value: 'PackageType' },
      ];
    },

    sortOptions() {
      return [
        { label: this.tLabel('sort_by_date', 'Date Réception (Récents)'), value: 'date' },
        { label: this.tLabel('status', 'Statut'), value: 'status' },
        { label: this.tLabel('sort_by_rem_weight', 'Poids Restant (Décroissant)'), value: 'rem_weight' },
        { label: this.tLabel('sort_by_lot', 'N° de Suivi de Réception'), value: 'lot' },
        { label: this.tLabel('sort_by_age', 'Âge du N° de Suivi'), value: 'age' },
        { label: this.tLabel('sort_by_valuation', 'Valorisation Stock'), value: 'valuation' },
        { label: this.tLabel('package_count', 'Cartons'), value: 'boxes' },
        { label: this.tLabel('suppliers', 'Fournisseurs'), value: 'supplier' },
      ];
    },

    densityOptions() {
      return [
        { value: 'spacious', label: this.tLabel('table_density_spacious', 'Spacieux'), icon: 'mdi-view-headline' },
        { value: 'comfortable', label: this.tLabel('table_density_comfortable', 'Confortable'), icon: 'mdi-table-row' },
        { value: 'compact', label: this.tLabel('table_density_compact', 'Compact'), icon: 'mdi-table-row-height' },
      ];
    },

    articleTypeOptions() {
      return [
        {
          value: 'FishType',
          label: this.tLabel('stockable_fishtype', 'Poissons & Espèces'),
          icon: 'mdi-fish',
          color: '#a21ee5',
        },
        {
          value: 'GuanoType',
          label: this.tLabel('stockable_guanotype', 'Guano'),
          icon: 'mdi-grain',
          color: '#6D4C41',
        },
        {
          value: 'PackageType',
          label: this.tLabel('stockable_packagetype', 'Emballages & Cartons'),
          icon: 'mdi-package-variant',
          color: '#00897B',
        },
      ];
    },

    filteredTimelineEvents() {
      const list = this.traceabilityTimelineData?.timeline || this.traceabilityActiveLotData?.timeline || [];
      if (!list.length) return [];
      let res = list;
      if (this.traceabilityTimelineFilter === 'inflow') {
        res = list.filter(e => e.is_inflow);
      } else if (this.traceabilityTimelineFilter === 'outflow') {
        res = list.filter(e => !e.is_inflow && e.type !== 'transfer');
      } else if (this.traceabilityTimelineFilter === 'transfer') {
        res = list.filter(e => e.type === 'transfer');
      } else if (this.traceabilityTimelineFilter === 'shipping') {
        res = list.filter(e => e.event_action === 'outflow_shipping');
      } else if (this.traceabilityTimelineFilter === 'production') {
        res = list.filter(e => e.event_action === 'outflow_production');
      }
      if (this.traceabilityTimelineSortOrder === 'desc') {
        return [...res].reverse();
      }
      return res;
    },

    allArticlesSelectedInVoucher() {
      if (!this.traceabilityArticlesList.length) return false;
      return this.selectedTraceabilityArticleIds.length === this.traceabilityArticlesList.length;
    },

    someArticlesSelectedInVoucher() {
      return this.selectedTraceabilityArticleIds.length > 0 && !this.allArticlesSelectedInVoucher;
    },

    selectedArticlesSummary() {
      const selected = this.traceabilityArticlesList.filter(a => this.selectedTraceabilityArticleIds.includes(a.id));
      const initW = selected.reduce((sum, a) => sum + (Number(a.initial_weight) || 0), 0);
      const remW = selected.reduce((sum, a) => sum + (Number(a.remaining_weight) || 0), 0);
      const val = selected.reduce((sum, a) => sum + (Number(a.total_price) || 0), 0);
      return {
        count: selected.length,
        initial_weight: initW,
        remaining_weight: remW,
        exited_weight: Math.max(0, initW - remW),
        total_valuation: val,
      };
    },

    hasActiveFilters() {
      return Boolean(
        (this.searchQuery && this.searchQuery.trim().length > 0) ||
        this.filterStatus !== 'in_stock' ||
        (this.selectedWarehouseIds && this.selectedWarehouseIds.length > 0) ||
        this.filterStockableType ||
        (this.filterStockableTypes && this.filterStockableTypes.length > 0) ||
        this.sortBy !== 'date'
      );
    },

    activeFilterCount() {
      let count = 0;
      if (this.searchQuery && this.searchQuery.trim().length > 0) count++;
      if (this.filterStatus && this.filterStatus !== 'in_stock') count++;
      if (this.selectedWarehouseIds && this.selectedWarehouseIds.length > 0) count += this.selectedWarehouseIds.length;
      if (this.filterStockableType) count++;
      if (this.filterStockableTypes && this.filterStockableTypes.length > 0) count += this.filterStockableTypes.length;
      if (this.sortBy && this.sortBy !== 'date') count++;
      return count;
    },

    isAllLotsExpanded() {
      return this.lots.length > 0 && this.lots.every(l => this.expandedLots[l.lot_number]);
    },

    expandedLotsCount() {
      return Object.values(this.expandedLots).filter(Boolean).length;
    },

    // 1. Stock Par Lot: Filtered Lots via Header Multi-Column Filters
    filteredLots() {
      let list = this.lots || [];
      const f = this.lotColFilters;

      // 1. Lot Number
      if (f.lot_number && f.lot_number.length > 0) {
        const set = new Set(f.lot_number.map(String));
        list = list.filter(l => set.has(String(l.lot_number)));
      }

      // 2. Status
      if (f.status && f.status.length > 0) {
        const set = new Set(f.status);
        list = list.filter(l => set.has(l.status));
      }

      // 3. Reception Date
      if (f.date && f.date.length > 0) {
        const set = new Set(f.date);
        list = list.filter(l => {
          const dStr = l.first_date ? this.main.formateDate(l.first_date) : '-';
          return set.has(dStr);
        });
      }

      // 4. Supplier
      if (f.supplier && f.supplier.length > 0) {
        const set = new Set(f.supplier.map(s => String(s).toLowerCase().trim()));
        list = list.filter(l => {
          const sup = String(l.supplier_name || '').toLowerCase().trim();
          return set.has(sup);
        });
      }

      // 5. Chamber Location
      if (f.chamber && f.chamber.length > 0) {
        const set = new Set(f.chamber);
        list = list.filter(l => {
          if (set.has(l.primary_warehouse)) return true;
          if (Array.isArray(l.warehouses)) {
            return l.warehouses.some(w => set.has(typeof w === 'object' ? w.name : w));
          }
          return false;
        });
      }

      // 6. Species / Articles
      if (f.species && f.species.length > 0) {
        const set = new Set(f.species);
        list = list.filter(l => {
          return (l.species_list || []).some(sp => set.has(sp));
        });
      }

      // 7. Stock Balance Weight Tiers
      if (f.rem_weight && f.rem_weight.length > 0) {
        const set = new Set(f.rem_weight);
        list = list.filter(l => set.has(this.getWeightTier(l.remaining_weight)));
      }

      // 8. Boxes Tiers
      if (f.boxes && f.boxes.length > 0) {
        const set = new Set(f.boxes);
        list = list.filter(l => set.has(this.getBoxesTier(l.remaining_boxes)));
      }

      // 9. Valuation Tiers
      if (f.valuation && f.valuation.length > 0) {
        const set = new Set(f.valuation);
        list = list.filter(l => set.has(this.getValuationTier(l.total_valuation)));
      }

      return list;
    },

    hasActiveLotColFilters() {
      return Object.values(this.lotColFilters).some(arr => arr && arr.length > 0);
    },

    activeLotColFiltersCount() {
      return Object.values(this.lotColFilters).filter(arr => arr && arr.length > 0).length;
    },

    pageFilteredWeight() {
      return (this.filteredLots || []).reduce((acc, l) => acc + (parseFloat(l.remaining_weight) || 0), 0);
    },

    pageFilteredValuation() {
      return (this.filteredLots || []).reduce((acc, l) => acc + (parseFloat(l.total_valuation) || 0), 0);
    },

    pageFilteredBoxes() {
      return (this.filteredLots || []).reduce((acc, l) => acc + (parseFloat(l.remaining_boxes) || 0), 0);
    },

    warehousesWithStockList() {
      return this.chambersList.filter(c => (c.total_weight > 0 || c.total_stock > 0));
    },

    occupiedChambersCount() {
      return this.chambersList.filter(c => c.total_weight > 0).length;
    },

    filteredChambers() {
      if (!this.chamberSearch) return this.chambersList;
      const q = this.chamberSearch.toLowerCase();
      return this.chambersList.filter(c => (
        (c.name && c.name.toLowerCase().includes(q)) ||
        (c.category_name && c.category_name.toLowerCase().includes(q))
      ));
    },

    filteredSpecies() {
      let list = this.speciesList;
      if (this.speciesFilterType && this.speciesFilterType !== 'all') {
        list = list.filter(s => s.stockable_type === this.speciesFilterType);
      }
      if (this.speciesSearch) {
        const q = this.speciesSearch.toLowerCase();
        list = list.filter(s => (
          (s.article_name && s.article_name.toLowerCase().includes(q)) ||
          (s.family_name && s.family_name.toLowerCase().includes(q)) ||
          (s.process_name && s.process_name.toLowerCase().includes(q))
        ));
      }

      // Multi-column header filters for Species
      const f = this.speciesColFilters;
      if (f.article_name && f.article_name.length > 0) {
        const set = new Set(f.article_name);
        list = list.filter(s => set.has(s.article_name));
      }
      if (f.family_name && f.family_name.length > 0) {
        const set = new Set(f.family_name);
        list = list.filter(s => set.has(s.family_name));
      }
      if (f.process_name && f.process_name.length > 0) {
        const set = new Set(f.process_name);
        list = list.filter(s => set.has(s.process_name));
      }
      if (f.rem_weight && f.rem_weight.length > 0) {
        const set = new Set(f.rem_weight);
        list = list.filter(s => set.has(this.getWeightTier(s.total_weight)));
      }
      if (f.boxes && f.boxes.length > 0) {
        const set = new Set(f.boxes);
        list = list.filter(s => set.has(this.getBoxesTier(s.total_boxes)));
      }
      if (f.wap && f.wap.length > 0) {
        const set = new Set(f.wap);
        list = list.filter(s => set.has(this.getWapTier(s.wap)));
      }
      if (f.valuation && f.valuation.length > 0) {
        const set = new Set(f.valuation);
        list = list.filter(s => set.has(this.getValuationTier(s.total_valuation)));
      }

      return list;
    },

    hasActiveSpeciesColFilters() {
      return Object.values(this.speciesColFilters).some(arr => arr && arr.length > 0);
    },

    activeSpeciesColFiltersCount() {
      return Object.values(this.speciesColFilters).filter(arr => arr && arr.length > 0).length;
    },

    filteredJournalList() {
      let list = this.journalList;
      if (this.journalSearch) {
        const q = this.journalSearch.toLowerCase();
        list = list.filter(m => (
          (m.lot_number && String(m.lot_number).toLowerCase().includes(q)) ||
          (m.article_name && m.article_name.toLowerCase().includes(q)) ||
          (m.warehouse_name && m.warehouse_name.toLowerCase().includes(q)) ||
          (m.partner_name && m.partner_name.toLowerCase().includes(q)) ||
          (m.source_type && m.source_type.toLowerCase().includes(q)) ||
          (m.source_id && String(m.source_id).includes(q))
        ));
      }

      // Multi-column header filters for Journal
      const f = this.journalColFilters;
      if (f.date && f.date.length > 0) {
        const set = new Set(f.date);
        list = list.filter(m => {
          const dStr = m.date ? this.main.formateDate(m.date) : '-';
          return set.has(dStr);
        });
      }
      if (f.direction && f.direction.length > 0) {
        const set = new Set(f.direction);
        list = list.filter(m => set.has(m.direction));
      }
      if (f.lot_number && f.lot_number.length > 0) {
        const set = new Set(f.lot_number.map(String));
        list = list.filter(m => set.has(String(m.lot_number)));
      }
      if (f.article_name && f.article_name.length > 0) {
        const set = new Set(f.article_name);
        list = list.filter(m => set.has(m.article_name));
      }
      if (f.warehouse_name && f.warehouse_name.length > 0) {
        const set = new Set(f.warehouse_name);
        list = list.filter(m => set.has(m.warehouse_name));
      }
      if (f.source_type && f.source_type.length > 0) {
        const set = new Set(f.source_type);
        list = list.filter(m => set.has(m.source_type));
      }
      if (f.partner_name && f.partner_name.length > 0) {
        const set = new Set(f.partner_name);
        list = list.filter(m => set.has(m.partner_name));
      }
      if (f.weight && f.weight.length > 0) {
        const set = new Set(f.weight);
        list = list.filter(m => set.has(this.getWeightTier(m.weight)));
      }
      if (f.boxes && f.boxes.length > 0) {
        const set = new Set(f.boxes);
        list = list.filter(m => set.has(this.getBoxesTier(m.boxes)));
      }

      return list;
    },

    paginatedJournalList() {
      const start = (this.journalPage - 1) * this.journalPerPage;
      return this.filteredJournalList.slice(start, start + this.journalPerPage);
    },

    journalTotalPages() {
      return Math.max(1, Math.ceil(this.filteredJournalList.length / this.journalPerPage));
    },

    hasActiveJournalColFilters() {
      return Object.values(this.journalColFilters).some(arr => arr && arr.length > 0);
    },

    activeJournalColFiltersCount() {
      return Object.values(this.journalColFilters).filter(arr => arr && arr.length > 0).length;
    },

    dashChartSeries() {
      const datesMap = {};
      this.journalList.forEach(m => {
        const d = m.date ? m.date.substring(0, 10) : 'Autre';
        if (!datesMap[d]) datesMap[d] = { in: 0, out: 0 };
        if (m.direction === 'IN') datesMap[d].in += m.weight || 0;
        else datesMap[d].out += m.weight || 0;
      });
      const sortedKeys = Object.keys(datesMap).sort();
      return [
        { name: this.tLabel('movement_in', 'Entrées (IN) Kg'), data: sortedKeys.map(k => Math.round(datesMap[k].in)) },
        { name: this.tLabel('movement_out', 'Sorties (OUT) Kg'), data: sortedKeys.map(k => Math.round(datesMap[k].out)) },
      ];
    },

    dashChartOptions() {
      const datesMap = {};
      this.journalList.forEach(m => {
        const d = m.date ? m.date.substring(0, 10) : 'Autre';
        datesMap[d] = true;
      });
      const sortedKeys = Object.keys(datesMap).sort();
      return {
        chart: { type: 'area', background: 'transparent', toolbar: { show: false } },
        colors: ['#00897B', '#D32F2F'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: {
          categories: sortedKeys.map(d => this.main.formateDate(d)),
          labels: { style: { colors: '#888', fontSize: '11px' } },
        },
        yaxis: {
          labels: {
            formatter: val => this.main.formatDisplayNumber(val) + ' Kg',
            style: { colors: '#888' },
          },
        },
        tooltip: { y: { formatter: val => this.main.formatDisplayNumber(val) + ' Kg' } },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    speciesPieSeries() {
      return this.speciesList.slice(0, 8).map(s => Math.round(s.total_weight));
    },

    speciesPieOptions() {
      return {
        chart: { type: 'donut', background: 'transparent' },
        labels: this.speciesList.slice(0, 8).map(s => s.article_name),
        colors: ['#a21ee5', '#00897B', '#1E88E5', '#FB8C00', '#6D4C41', '#E91E63', '#00ACC1', '#43A047'],
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '11px' },
        tooltip: { y: { formatter: val => this.main.formatDisplayNumber(val) + ' Kg' } },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    chambersBarSeries() {
      const active = this.chambersList.filter(c => c.total_weight > 0).slice(0, 10);
      return [{
        name: this.tLabel('remaining_weight_label', 'Poids en Stock (Kg)'),
        data: active.map(c => Math.round(c.total_weight)),
      }];
    },

    chambersBarOptions() {
      const active = this.chambersList.filter(c => c.total_weight > 0).slice(0, 10);
      return {
        chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
        plotOptions: { bar: { horizontal: true, borderRadius: 6, dataLabels: { position: 'top' } } },
        colors: ['#a21ee5'],
        xaxis: {
          categories: active.map(c => c.name),
          labels: { formatter: v => this.main.formatDisplayNumber(v) + ' Kg' },
        },
        dataLabels: {
          enabled: true,
          formatter: val => this.main.formatDisplayNumber(val) + ' Kg',
          style: { fontSize: '10px', colors: ['#fff'] },
          offsetX: 30,
        },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    analyticsPresetOptions() {
      return [
        { label: this.tLabel('preset_today', 'Aujourd\'hui'), value: 'today' },
        { label: '7J', value: '7d' },
        { label: '30J', value: '30d' },
        { label: this.tLabel('preset_this_month', 'Ce Mois'), value: 'this_month' },
        { label: this.tLabel('preset_last_month', 'Mois Dernier'), value: 'last_month' },
        { label: '90J', value: '90d' },
        { label: this.tLabel('preset_this_year', 'Cette Année'), value: 'this_year' },
        { label: this.tLabel('preset_all_time', 'Tout'), value: 'all' },
      ];
    },

    analyticsGranularityOptions() {
      return [
        { label: this.tLabel('daily', 'Par Jour'), value: 'daily', icon: 'mdi-calendar-today' },
        { label: this.tLabel('weekly', 'Par Semaine'), value: 'weekly', icon: 'mdi-calendar-week' },
        { label: this.tLabel('monthly', 'Par Mois'), value: 'monthly', icon: 'mdi-calendar-month' },
      ];
    },

    analyticsChartTypeOptions() {
      return [
        { label: this.tLabel('area_spline', 'Aires Spline'), value: 'area', icon: 'mdi-chart-areaspline' },
        { label: this.tLabel('bar_columns', 'Colonnes'), value: 'bar', icon: 'mdi-chart-bar' },
        { label: this.tLabel('line_curves', 'Courbes'), value: 'line', icon: 'mdi-chart-line' },
      ];
    },

    displayAnalyticsRangeText() {
      if (!this.analyticsDateRange?.start || !this.analyticsDateRange?.end) {
        return this.tLabel('select_date_range', 'Sélectionnez une période');
      }
      const s = moment(this.analyticsDateRange.start).format('DD/MM/YYYY');
      const e = moment(this.analyticsDateRange.end).format('DD/MM/YYYY');
      return s === e ? s : `${s} → ${e}`;
    },

    analyticsMetricToggleOptions() {
      return [
        { label: this.tLabel('inflow_vs_outflow', 'Flux (IN / OUT)'), value: 'flows', icon: 'mdi-swap-vertical' },
        { label: this.tLabel('cumulative_balance', 'Stock Cumulé'), value: 'cumulative', icon: 'mdi-chart-bell-curve-cumulative' },
        { label: this.tLabel('valuation_flow', 'Valorisation (Dh)'), value: 'valuation', icon: 'mdi-cash-multiple' },
        { label: this.tLabel('boxes_flow', 'Colis & Cartons'), value: 'boxes', icon: 'mdi-package-variant-closed' },
      ];
    },

    analyticsSpeciesMetricOptions() {
      return [
        { label: this.tLabel('by_weight', 'Poids (Kg)'), value: 'weight', icon: 'mdi-scale' },
        { label: this.tLabel('by_valuation', 'Valeur (Dh)'), value: 'valuation', icon: 'mdi-cash' },
      ];
    },

    analyticsAgingMetricOptions() {
      return [
        { label: this.tLabel('by_weight', 'Poids (Kg)'), value: 'weight', icon: 'mdi-scale' },
        { label: this.tLabel('by_valuation', 'Valeur (Dh)'), value: 'valuation', icon: 'mdi-cash' },
      ];
    },

    analyticsTableTabOptions() {
      return [
        { label: this.tLabel('timeline_journal', 'Flux Chronologiques'), value: 'timeline', icon: 'mdi-calendar-clock' },
        { label: this.tLabel('species_matrix', 'Matrice par Espèce'), value: 'species', icon: 'mdi-fish' },
        { label: this.tLabel('chambers_performance', 'Bilan des Chambres'), value: 'chambers', icon: 'mdi-snowflake' },
        { label: this.tLabel('partners_logistics', 'Partenaires (Fournisseurs / Clients)'), value: 'partners', icon: 'mdi-domain' },
      ];
    },

    analyticsTimelineSeries() {
      const tl = this.analyticsData?.timeline || [];
      if (this.analyticsChartMetric === 'cumulative') {
        return [{
          name: this.tLabel('cumulative_balance', 'Stock Cumulé (Kg)'),
          data: tl.map(t => Math.round(t.cumulative_net_weight)),
        }];
      }
      if (this.analyticsChartMetric === 'valuation') {
        return [
          { name: this.tLabel('financial_inflow', 'Valeur Entrées (Dh)'), data: tl.map(t => Math.round(t.in_valuation)) },
          { name: this.tLabel('financial_outflow', 'Valeur Sorties (Dh)'), data: tl.map(t => Math.round(t.out_valuation)) },
          { name: this.tLabel('net_valuation_var', 'Variation Nette (Dh)'), data: tl.map(t => Math.round(t.in_valuation - t.out_valuation)) },
        ];
      }
      if (this.analyticsChartMetric === 'boxes') {
        return [
          { name: this.tLabel('boxes_in', 'Cartons Entrants'), data: tl.map(t => Math.round(t.in_boxes)) },
          { name: this.tLabel('boxes_out', 'Cartons Sortants'), data: tl.map(t => Math.round(t.out_boxes)) },
          { name: this.tLabel('boxes_net', 'Solde Net Cartons'), data: tl.map(t => Math.round(t.in_boxes - t.out_boxes)) },
        ];
      }
      // Default: 'flows'
      return [
        { name: this.tLabel('movement_in', 'Entrées (IN) Kg'), data: tl.map(t => Math.round(t.in_weight)) },
        { name: this.tLabel('movement_out', 'Sorties (OUT) Kg'), data: tl.map(t => Math.round(t.out_weight)) },
        { name: this.tLabel('net_balance', 'Solde Net Kg'), data: tl.map(t => Math.round(t.net_weight)) },
      ];
    },

    analyticsTimelineOptions() {
      const tl = this.analyticsData?.timeline || [];
      const gran = this.analyticsGranularity || 'daily';
      const categories = tl.map(t => {
        if (!t.date) return '-';
        if (gran === 'monthly') return moment(t.date).format('MMM YYYY');
        if (gran === 'weekly') return 'Sem. ' + moment(t.date).format('DD/MM');
        return moment(t.date).format('DD/MM/YYYY');
      });

      let colors = ['#00897B', '#D32F2F', '#a21ee5'];
      if (this.analyticsChartMetric === 'cumulative') {
        colors = ['#a21ee5'];
      } else if (this.analyticsChartMetric === 'valuation') {
        colors = ['#00897B', '#E65100', '#a21ee5'];
      } else if (this.analyticsChartMetric === 'boxes') {
        colors = ['#6D4C41', '#E53935', '#a21ee5'];
      }

      const chartType = this.analyticsChartType || 'area';

      return {
        chart: {
          type: chartType,
          background: 'transparent',
          toolbar: { show: true, tools: { download: true, zoom: false, pan: false } },
          stacked: false,
        },
        colors,
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: chartType === 'bar' ? 0 : 2.5 },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '55%',
            borderRadius: 4,
          },
        },
        fill: {
          type: chartType === 'area' ? 'gradient' : 'solid',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [0, 95, 100],
          },
        },
        xaxis: {
          categories,
          labels: { style: { colors: '#888', fontSize: '11px' } },
        },
        yaxis: {
          labels: {
            formatter: val => {
              if (this.analyticsChartMetric === 'valuation') return this.main.formatDisplayNumber(val) + ' Dh';
              if (this.analyticsChartMetric === 'boxes') return this.main.formatDisplayNumber(val) + ' c';
              return this.main.formatDisplayNumber(val) + ' Kg';
            },
            style: { colors: '#888' },
          },
        },
        tooltip: {
          y: {
            formatter: val => {
              if (this.analyticsChartMetric === 'valuation') return this.main.formatDisplayNumber(val) + ' ' + this.main.currency;
              if (this.analyticsChartMetric === 'boxes') return this.main.formatDisplayNumber(val) + ' ' + this.tLabel('boxes', 'caisses');
              return this.main.formatDisplayNumber(val) + ' Kg';
            },
          },
        },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', fontWeight: 600 },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    analyticsAgingDonutSeries() {
      const buckets = this.analyticsData?.aging?.buckets || {};
      const metric = this.analyticsAgingMetric || 'weight';
      const f = buckets.fresh?.[metric] || 0;
      const o = buckets.optimal?.[metric] || 0;
      const w = buckets.warning?.[metric] || 0;
      const a = buckets.alert?.[metric] || 0;
      return [Math.round(f), Math.round(o), Math.round(w), Math.round(a)];
    },

    analyticsAgingDonutOptions() {
      const isVal = this.analyticsAgingMetric === 'valuation';
      const cur = this.main?.currency || 'Dh';
      return {
        chart: { type: 'donut', background: 'transparent' },
        labels: [
          '0 - 30 j (Frais)',
          '31 - 60 j (Normal)',
          '61 - 90 j (À surveiller)',
          '> 90 j (Critique / FIFO)',
        ],
        colors: ['#00897B', '#1E88E5', '#FB8C00', '#E53935'],
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '11px', fontWeight: 600 },
        tooltip: {
          y: {
            formatter: val => (isVal ? this.main.formatDisplayNumber(val) + ' ' + cur : this.main.formatDisplayNumber(val) + ' Kg'),
          },
        },
        plotOptions: {
          pie: {
            donut: {
              size: '65%',
              labels: {
                show: true,
                name: {
                  show: true,
                  fontSize: '12px',
                  fontWeight: 600,
                  color: this.main?.is_dark ? '#AAA' : '#666',
                  offsetY: -4,
                },
                value: {
                  show: true,
                  fontSize: '16px',
                  fontWeight: 800,
                  color: this.main?.is_dark ? '#FFF' : '#111',
                  offsetY: 4,
                  formatter: val => (isVal ? this.main.formatDisplayNumber(val) + ' ' + cur : this.main.formatDisplayNumber(val) + ' Kg'),
                },
                total: {
                  show: true,
                  label: this.tLabel('total_stock_value', 'Stock Total'),
                  fontSize: '12px',
                  fontWeight: 700,
                  color: this.main?.is_dark ? '#AAA' : '#666',
                  formatter: (w) => {
                    if (w && w.globals && w.globals.seriesTotals && w.globals.seriesTotals.length) {
                      const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                      return isVal ? this.main.formatDisplayNumber(sum) + ' ' + cur : this.main.formatDisplayNumber(sum) + ' Kg';
                    }
                    const aging = this.analyticsData?.aging || {};
                    const total = isVal ? aging.total_valuation : aging.total_weight;
                    return isVal ? this.main.formatDisplayNumber(total) + ' ' + cur : this.main.formatDisplayNumber(total) + ' Kg';
                  },
                },
              },
            },
          },
        },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    analyticsSpeciesDonutSeries() {
      const list = this.analyticsData?.species || [];
      const isVal = this.analyticsSpeciesMetric === 'valuation';
      const items = list.filter(s => s != null).slice(0, 8);
      const values = items.map(s => Math.round(isVal ? (Number(s?.total_valuation) || 0) : (Number(s?.total_flow) || 0)));
      return values.length ? values : [0];
    },

    analyticsSpeciesDonutOptions() {
      const list = this.analyticsData?.species || [];
      const isVal = this.analyticsSpeciesMetric === 'valuation';
      const cur = this.main?.currency || 'Dh';
      const items = list.filter(s => s != null).slice(0, 8);
      const labels = items.map((s, idx) => {
        const name = s?.article_name || s?.name || s?.label || s?.designation;
        return name ? String(name) : (this.tLabel('species', 'Espèce') + ' #' + (idx + 1));
      });
      return {
        chart: { type: 'donut', background: 'transparent' },
        labels: labels.length ? labels : [this.tLabel('no_data', 'Aucune donnée')],
        colors: ['#a21ee5', '#00897B', '#1E88E5', '#FB8C00', '#6D4C41', '#E91E63', '#00ACC1', '#43A047'],
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '11px', fontWeight: 600 },
        tooltip: {
          y: {
            formatter: val => (isVal ? this.main.formatDisplayNumber(val) + ' ' + cur : this.main.formatDisplayNumber(val) + ' Kg'),
          },
        },
        plotOptions: {
          pie: {
            donut: {
              size: '65%',
              labels: {
                show: true,
                name: {
                  show: true,
                  fontSize: '12px',
                  fontWeight: 600,
                  color: this.main?.is_dark ? '#AAA' : '#666',
                  offsetY: -4,
                },
                value: {
                  show: true,
                  fontSize: '16px',
                  fontWeight: 800,
                  color: this.main?.is_dark ? '#FFF' : '#111',
                  offsetY: 4,
                  formatter: val => (isVal ? this.main.formatDisplayNumber(val) + ' ' + cur : this.main.formatDisplayNumber(val) + ' Kg'),
                },
                total: {
                  show: true,
                  label: isVal ? this.tLabel('total_price', 'Total Valeur') : this.tLabel('total_flow', 'Total Flux'),
                  fontSize: '12px',
                  fontWeight: 700,
                  color: this.main?.is_dark ? '#AAA' : '#666',
                  formatter: (w) => {
                    if (w && w.globals && w.globals.seriesTotals && w.globals.seriesTotals.length) {
                      const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                      return isVal ? this.main.formatDisplayNumber(sum) + ' ' + cur : this.main.formatDisplayNumber(sum) + ' Kg';
                    }
                    const species = this.analyticsData?.species || [];
                    const total = species.reduce((acc, s) => acc + (isVal ? (s.total_valuation || 0) : (s.total_flow || 0)), 0);
                    return isVal ? this.main.formatDisplayNumber(total) + ' ' + cur : this.main.formatDisplayNumber(total) + ' Kg';
                  },
                },
              },
            },
          },
        },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    analyticsFamilyBarSeries() {
      const list = this.analyticsData?.families || [];
      return [
        { name: this.tLabel('movement_in', 'Entrées (IN) Kg'), data: list.map(f => Math.round(f.in_weight)) },
        { name: this.tLabel('movement_out', 'Sorties (OUT) Kg'), data: list.map(f => Math.round(f.out_weight)) },
      ];
    },

    analyticsFamilyBarOptions() {
      const list = this.analyticsData?.families || [];
      return {
        chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
        plotOptions: {
          bar: { horizontal: false, columnWidth: '45%', borderRadius: 4 },
        },
        colors: ['#00897B', '#D32F2F'],
        xaxis: {
          categories: list.map(f => f.label),
          labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
          labels: { formatter: v => this.main.formatDisplayNumber(v) + ' Kg' },
        },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'right' },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    analyticsChambersBarSeries() {
      const list = (this.analyticsData?.chambers || []).filter(c => (c.in_weight + c.out_weight) > 0).slice(0, 10);
      return [
        { name: this.tLabel('movement_in', 'Entrées (IN) Kg'), data: list.map(c => Math.round(c.in_weight)) },
        { name: this.tLabel('movement_out', 'Sorties (OUT) Kg'), data: list.map(c => Math.round(c.out_weight)) },
      ];
    },

    analyticsChambersBarOptions() {
      const list = (this.analyticsData?.chambers || []).filter(c => (c.in_weight + c.out_weight) > 0).slice(0, 10);
      return {
        chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
        plotOptions: { bar: { horizontal: true, borderRadius: 5, dataLabels: { position: 'top' } } },
        colors: ['#00897B', '#D32F2F'],
        xaxis: {
          categories: list.map(c => c.name),
          labels: { formatter: v => this.main.formatDisplayNumber(v) + ' Kg' },
        },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'right' },
        theme: { mode: this.main?.is_dark ? 'dark' : 'light' },
      };
    },

    filteredAnalyticsTimeline() {
      const list = this.analyticsData?.timeline || [];
      if (!this.analyticsTableSearch) return list;
      const q = this.analyticsTableSearch.toLowerCase();
      return list.filter(t => (t.date || '').toLowerCase().includes(q));
    },

    filteredAnalyticsSpecies() {
      const list = this.analyticsData?.species || [];
      if (!this.analyticsTableSearch) return list;
      const q = this.analyticsTableSearch.toLowerCase();
      return list.filter(s => (s.article_name || '').toLowerCase().includes(q) || (s.stockable_type || '').toLowerCase().includes(q));
    },

    filteredAnalyticsChambers() {
      const list = this.analyticsData?.chambers || [];
      if (!this.analyticsTableSearch) return list;
      const q = this.analyticsTableSearch.toLowerCase();
      return list.filter(c => (c.name || '').toLowerCase().includes(q));
    },

    filteredAnalyticsSuppliers() {
      const list = this.analyticsData?.partners?.suppliers || [];
      if (!this.analyticsTableSearch) return list;
      const q = this.analyticsTableSearch.toLowerCase();
      return list.filter(p => (p.partner_name || '').toLowerCase().includes(q));
    },

    filteredAnalyticsClients() {
      const list = this.analyticsData?.partners?.clients || [];
      if (!this.analyticsTableSearch) return list;
      const q = this.analyticsTableSearch.toLowerCase();
      return list.filter(p => (p.partner_name || '').toLowerCase().includes(q));
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
    // Safe translation label resolver
    tLabel(key, fallback) {
      if (!key) return fallback || '';
      try {
        if (this.$te && this.$te(key)) {
          return this.$t(key);
        }
      } catch (e) { }
      return fallback || key;
    },

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
        if (this._refreshTimer) clearTimeout(this._refreshTimer);
        this._refreshTimer = setTimeout(() => {
          this.refreshActiveView();
          this.triggerSummarySnackbar({
            title: this.tLabel('stock_updated_notification', 'Stock Synchronisé en Temps Réel'),
            message: `${d.model} (${d.action || this.tLabel('updated', 'mise à jour')})`,
            color: '#a21ee5',
            icon: 'mdi-sync',
          });
        }, 800);
      }
      if (d.model === 'Warehouse' && (d.action === 'created' || d.action === 'deleted')) {
        this.fetchAllWarehouses();
      }
    },

    // Trigger Rule 5 Summary Snackbar with countdown
    triggerSummarySnackbar({ title, message, color = '#a21ee5', icon = 'mdi-check-circle', details = null }) {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.summarySnackbar.title = title;
      this.summarySnackbar.message = message;
      this.summarySnackbar.color = color;
      this.summarySnackbar.icon = icon;
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
        const { warehouses } = await gqlGetWarehouses();
        this.allWarehouses = (warehouses || []).map(w => ({
          ...w,
          title: w.name,
          value: w.id,
        }));
        this.refreshActiveView();
      } catch (e) {
        console.warn('fetchAllWarehouses GraphQL error, fallback to REST:', e);
        try {
          const r = await axios.get('/api/warehouses');
          this.allWarehouses = (r.data.warehouses || []).map(w => ({
            ...w,
            title: w.name,
            value: w.id,
          }));
          this.refreshActiveView();
        } catch (err) {
          console.error('fetchAllWarehouses fallback error:', err);
        }
      }
    },

    onWarehouseChange() {
      this.saveCachedWarehouses();
      this.refreshActiveView();
    },

    selectAllWarehouses() {
      this.selectedWarehouseIds = this.allWarehouses.map(w => w.id);
      this.saveCachedWarehouses();
      this.refreshActiveView();
    },

    clearAllWarehouses() {
      this.selectedWarehouseIds = [];
      this.saveCachedWarehouses();
      this.refreshActiveView();
    },

    selectWarehouseDirect(id) {
      if (this.selectedWarehouseIds.includes(id)) {
        this.selectedWarehouseIds = this.selectedWarehouseIds.filter(x => x !== id);
      } else {
        this.selectedWarehouseIds.push(id);
      }
      this.saveCachedWarehouses();
      this.refreshActiveView();
    },

    // Date Range Presets for Movements Ledger
    setMovementPreset(preset) {
      this.activeMovementPreset = preset;
      const today = moment().toDate();

      if (preset === 'today') {
        this.movementDateRange = { start: today, end: today };
      } else if (preset === '7d') {
        this.movementDateRange = { start: moment().subtract(7, 'days').toDate(), end: today };
      } else if (preset === 'this_month') {
        this.movementDateRange = { start: moment().startOf('month').toDate(), end: moment().endOf('month').toDate() };
      } else if (preset === 'last_month') {
        this.movementDateRange = {
          start: moment().subtract(1, 'month').startOf('month').toDate(),
          end: moment().subtract(1, 'month').endOf('month').toDate(),
        };
      } else if (preset === '30d') {
        this.movementDateRange = { start: moment().subtract(30, 'days').toDate(), end: today };
      } else if (preset === '90d') {
        this.movementDateRange = { start: moment().subtract(90, 'days').toDate(), end: today };
      } else if (preset === 'all') {
        this.movementDateRange = { start: moment('2020-01-01').toDate(), end: today };
      }
      this.fetchJournal();
    },

    onMovementDateRangeChange() {
      this.activeMovementPreset = null;
      this.fetchJournal();
    },

    toggleCalendarExpand() {
      this.calendarExpanded = !this.calendarExpanded;
    },

    onViewModeChange(newMode) {
      this.activeViewMode = newMode;
      this.refreshActiveView();
    },

    refreshActiveView() {
      if (this.activeViewMode === 'stock_par_lot') {
        this.fetchStockParLot();
      } else if (this.activeViewMode === 'chambers') {
        this.fetchChambers();
      } else if (this.activeViewMode === 'species') {
        this.fetchSpecies();
      } else if (this.activeViewMode === 'journal') {
        this.fetchJournal();
      } else if (this.activeViewMode === 'analytics') {
        this.fetchAnalytics();
      } else if (this.activeViewMode === 'traceability') {
        if (this.traceabilityCurrentStage === 1) {
          this.fetchTraceabilityVouchers();
        } else if (this.traceabilityCurrentStage === 2 && this.selectedTraceabilityVoucher) {
          this.fetchTraceabilityVoucherArticles(this.selectedTraceabilityVoucher.balance_ids || []);
        } else if (this.traceabilityCurrentStage === 3 && this.selectedTraceabilityArticleIds.length) {
          this.generateTraceabilityTimeline();
        }
      }
    },

    toggleAnalyticsCalendar() {
      this.analyticsCalendarExpanded = !this.analyticsCalendarExpanded;
    },

    setAnalyticsPreset(preset) {
      this.activeAnalyticsPreset = preset;
      const today = moment().toDate();
      if (preset === 'today') {
        this.analyticsDateRange = { start: today, end: today };
      } else if (preset === '7d') {
        this.analyticsDateRange = { start: moment().subtract(7, 'days').toDate(), end: today };
      } else if (preset === '30d') {
        this.analyticsDateRange = { start: moment().subtract(30, 'days').toDate(), end: today };
      } else if (preset === 'this_month') {
        this.analyticsDateRange = { start: moment().startOf('month').toDate(), end: moment().endOf('month').toDate() };
      } else if (preset === 'last_month') {
        this.analyticsDateRange = {
          start: moment().subtract(1, 'month').startOf('month').toDate(),
          end: moment().subtract(1, 'month').endOf('month').toDate(),
        };
      } else if (preset === '90d') {
        this.analyticsDateRange = { start: moment().subtract(90, 'days').toDate(), end: today };
      } else if (preset === 'this_year') {
        this.analyticsDateRange = { start: moment().startOf('year').toDate(), end: today };
      } else if (preset === 'all') {
        this.analyticsDateRange = { start: moment('2020-01-01').toDate(), end: today };
      }
      this.fetchAnalytics();
    },

    onAnalyticsDateRangeChange() {
      this.activeAnalyticsPreset = null;
      this.fetchAnalytics();
    },

    onAnalyticsGranularityChange(gran) {
      this.analyticsGranularity = gran;
      this.fetchAnalytics();
    },

    async fetchAnalytics() {
      this.analyticsLoading = true;
      try {
        const data = await gqlGetWarehouseAnalytics({
          dateFrom: moment(this.analyticsDateRange?.start || moment().subtract(30, 'days')).format('YYYY-MM-DD'),
          dateTo: moment(this.analyticsDateRange?.end || moment()).format('YYYY-MM-DD'),
          granularity: this.analyticsGranularity || 'daily',
          warehouseIds: this.selectedWarehouseIds,
          stockableTypes: this.filterStockableTypes,
        });
        if (data) {
          this.analyticsData = data;
        } else {
          throw new Error('Empty analytics payload');
        }
      } catch (e) {
        console.warn('fetchAnalytics GraphQL error, falling back to REST:', e);
        try {
          const params = {
            date_from: moment(this.analyticsDateRange?.start || moment().subtract(30, 'days')).format('YYYY-MM-DD'),
            date_to: moment(this.analyticsDateRange?.end || moment()).format('YYYY-MM-DD'),
            granularity: this.analyticsGranularity || 'daily',
            warehouse_ids: this.selectedWarehouseIds,
          };
          const r = await axios.get('/api/warehouses/analytics', { params });
          this.analyticsData = r.data;
        } catch (err) {
          console.error('fetchAnalytics fallback error:', err);
          this.triggerSummarySnackbar({
            title: this.tLabel('error', 'Erreur'),
            message: this.tLabel('analytics_fetch_error', 'Impossible de charger les données analytiques pour cette période.'),
            color: 'error',
            icon: 'mdi-alert-circle',
          });
        }
      } finally {
        this.analyticsLoading = false;
      }
    },

    exportAnalyticsCsv() {
      try {
        const tab = this.analyticsActiveTab || 'timeline';
        let csvContent = '';
        const fileName = 'analytics_' + tab + '_' + moment().format('YYYYMMDD_HHmm') + '.csv';

        if (tab === 'timeline') {
          csvContent = 'Date;Entrees (Kg);Sorties (Kg);Solde Net (Kg);Cumul (Kg);Cartons IN;Cartons OUT;Valeur IN (Dh);Valeur OUT (Dh);Mouvements\n';
          (this.filteredAnalyticsTimeline || []).forEach(r => {
            csvContent += `"${r.date}";"${r.in_weight}";"${r.out_weight}";"${r.net_weight}";"${r.cumulative_net_weight}";"${r.in_boxes}";"${r.out_boxes}";"${r.in_valuation}";"${r.out_valuation}";"${r.movements_count}"\n`;
          });
        } else if (tab === 'species') {
          csvContent = 'Article;Famille / Type;Entrees (Kg);Sorties (Kg);Solde Net (Kg);Total Flux (Kg);Cartons;Valeur (Dh);Part (%)\n';
          (this.filteredAnalyticsSpecies || []).forEach(r => {
            csvContent += `"${r.article_name}";"${r.stockable_type}";"${r.in_weight}";"${r.out_weight}";"${r.net_weight}";"${r.total_flow}";"${r.total_boxes}";"${r.total_valuation}";"${r.share_pct}%"\n`;
          });
        } else if (tab === 'chambers') {
          csvContent = 'Chambre;Entrees (Kg);Sorties (Kg);Solde Net (Kg);Total Flux (Kg);Stock Actuel (Kg);Part Activite (%);Mouvements\n';
          (this.filteredAnalyticsChambers || []).forEach(r => {
            csvContent += `"${r.name}";"${r.in_weight}";"${r.out_weight}";"${r.net_weight}";"${r.total_flow}";"${r.current_stock}";"${r.activity_pct}%";"${r.movements_count}"\n`;
          });
        } else if (tab === 'partners') {
          csvContent = 'Partenaire;Role;Mouvements;Poids Total (Kg);Cartons;Valeur (Dh)\n';
          (this.filteredAnalyticsSuppliers || []).forEach(r => {
            csvContent += `"${r.partner_name}";"Fournisseur";"${r.movements_count}";"${r.total_weight}";"${r.total_boxes}";"${r.total_valuation}"\n`;
          });
          (this.filteredAnalyticsClients || []).forEach(r => {
            csvContent += `"${r.partner_name}";"Client / Destinataire";"${r.movements_count}";"${r.total_weight}";"${r.total_boxes}";"${r.total_valuation}"\n`;
          });
        }

        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        this.triggerSummarySnackbar({
          title: this.tLabel('export_success', 'Exportation Réussie'),
          message: this.tLabel('analytics_exported_msg', 'Données analytiques exportées en CSV avec succès.'),
          color: 'success',
          icon: 'mdi-download-circle',
        });
      } catch (err) {
        console.error('exportAnalyticsCsv error:', err);
      }
    },

    // 1. Fetch Stock Par Lot (GraphQL with REST fallback)
    async fetchStockParLot(resetPage = false) {
      if (resetPage) this.lotsPagination.current_page = 1;
      this.lotsLoading = true;
      try {
        const data = await gqlGetStockParLot({
          warehouseIds: this.selectedWarehouseIds,
          stockableTypes: (this.filterStockableTypes && this.filterStockableTypes.length) ? this.filterStockableTypes : (this.filterStockableType ? [this.filterStockableType] : []),
          status: this.filterStatus,
          search: this.searchQuery || undefined,
          page: this.lotsPagination.current_page,
          perPage: this.lotsPagination.per_page,
          sortBy: this.sortBy,
          sortDir: this.sortDir,
        });

        if (data && data.lots) {
          this.lots = data.lots || [];
          if (data.summary) this.lotsSummary = data.summary;
          if (data.pagination) this.lotsPagination = data.pagination;
          return;
        }

        // REST fallback if GraphQL payload is empty
        const params = {
          warehouse_ids: this.selectedWarehouseIds,
          search: this.searchQuery || undefined,
          status: this.filterStatus,
          stockable_types: (this.filterStockableTypes && this.filterStockableTypes.length) ? this.filterStockableTypes : (this.filterStockableType || undefined),
          page: this.lotsPagination.current_page,
          per_page: this.lotsPagination.per_page,
          sort_by: this.sortBy,
          sort_dir: this.sortDir,
        };
        const r = await axios.get('/api/warehouses/stock-par-lot', { params });
        this.lots = r.data.lots || [];
        this.lotsSummary = r.data.summary || this.lotsSummary;
        this.lotsPagination = r.data.pagination || this.lotsPagination;
      } catch (e) {
        console.warn('fetchStockParLot GraphQL error, fallback to REST:', e);
        try {
          const params = {
            warehouse_ids: this.selectedWarehouseIds,
            search: this.searchQuery || undefined,
            status: this.filterStatus,
            stockable_types: (this.filterStockableTypes && this.filterStockableTypes.length) ? this.filterStockableTypes : (this.filterStockableType || undefined),
            page: this.lotsPagination.current_page,
            per_page: this.lotsPagination.per_page,
            sort_by: this.sortBy,
            sort_dir: this.sortDir,
          };
          const r = await axios.get('/api/warehouses/stock-par-lot', { params });
          this.lots = r.data.lots || [];
          this.lotsSummary = r.data.summary || this.lotsSummary;
          this.lotsPagination = r.data.pagination || this.lotsPagination;
        } catch (err) {
          console.error('fetchStockParLot REST error:', err);
        }
      } finally {
        this.lotsLoading = false;
      }
    },

    setTableDensity(mode) {
      this.tableDensity = mode;
      try {
        localStorage.setItem('wh_table_density', mode);
      } catch (e) {
        console.error(e);
      }
      this.triggerSummarySnackbar({
        title: this.tLabel('table_density', 'Densité du Tableau'),
        message: `${this.tLabel('table_density_' + (mode || 'spacious'), mode || 'spacious')}`,
        color: '#a21ee5',
        icon: 'mdi-view-headline',
      });
    },

    toggleExpandAllLots() {
      if (this.isAllLotsExpanded) {
        this.collapseAllLots();
      } else {
        this.expandAllLots();
      }
    },

    async copyTrackingNumber(lotNumber) {
      if (!lotNumber || lotNumber === '-') return;
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(lotNumber);
        } else {
          const ta = document.createElement('textarea');
          ta.value = lotNumber;
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
        }
        this.triggerSummarySnackbar({
          title: this.tLabel('copy_tracking_number', 'Copier'),
          message: `#${lotNumber} ${this.tLabel('tracking_number_copied', 'copié dans le presse-papiers')}`,
          color: '#a21ee5',
          icon: 'mdi-content-copy',
        });
      } catch (e) {
        console.error('Copy tracking number error:', e);
      }
    },

    resetAllFilters() {
      this.searchQuery = '';
      this.filterStatus = 'in_stock';
      this.filterStockableType = null;
      this.filterStockableTypes = [];
      this.selectedWarehouseIds = [];
      this.sortBy = 'date';
      this.sortDir = 'desc';
      this.saveCachedWarehouses();
      this.fetchStockParLot(true);
      this.triggerSummarySnackbar({
        title: this.tLabel('reset_all_filters', 'Réinitialisation'),
        message: this.tLabel('filters_reset_success', 'Tous les filtres ont été réinitialisés.'),
        color: '#a21ee5',
        icon: 'mdi-filter-off',
      });
    },

    onSortColumn(colKey) {
      if (this.sortBy === colKey) {
        this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
      } else {
        this.sortBy = colKey;
        this.sortDir = (colKey === 'lot' || colKey === 'supplier' || colKey === 'status') ? 'asc' : 'desc';
      }
      this.fetchStockParLot(true);
    },

    getSortIcon(colKey) {
      if (this.sortBy !== colKey) return 'mdi-swap-vertical';
      return this.sortDir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down';
    },

    isSorted(colKey) {
      return this.sortBy === colKey;
    },

    toggleLotExpand(lotNumber) {
      this.expandedLots[lotNumber] = !this.expandedLots[lotNumber];
    },

    expandAllLots() {
      const exp = {};
      this.lots.forEach(l => { exp[l.lot_number] = true; });
      this.expandedLots = exp;
    },

    collapseAllLots() {
      this.expandedLots = {};
    },

    toggleArticleExpand(articleId) {
      this.expandedArticles[articleId] = !this.expandedArticles[articleId];
    },

    // 2. Open Lot Traceability Drawer / Dialog
    async openTraceability(lot) {
      this.selectedLotForTraceability = lot;
      this.traceabilityDialog = true;
      this.traceabilityLoading = true;
      this.traceabilityData = null;
      try {
        const lotIdentifier = String(lot.lot_number || lot.raw_lot || lot);
        const data = await gqlGetLotTraceability(lotIdentifier);
        if (data) {
          this.traceabilityData = data;
        } else {
          throw new Error('Empty lot traceability data');
        }
      } catch (e) {
        console.warn('openTraceability GraphQL error, fallback to REST:', e);
        try {
          const lotIdentifier = encodeURIComponent(lot.lot_number || lot.raw_lot || lot);
          const r = await axios.get(`/api/warehouses/lot-traceability/${lotIdentifier}`);
          this.traceabilityData = r.data;
        } catch (err) {
          console.error('openTraceability fallback error:', err);
          this.triggerSummarySnackbar({
            title: this.tLabel('error', 'Erreur'),
            message: this.tLabel('empty_traceability_message', 'Impossible de charger la traçabilité de ce numéro de suivi.'),
            color: 'error',
            icon: 'mdi-alert-circle',
          });
        }
      } finally {
        this.traceabilityLoading = false;
      }
    },

    closeTraceability() {
      this.traceabilityDialog = false;
      this.selectedLotForTraceability = null;
      this.traceabilityData = null;
    },

    // =========================================================================
    // 2.5 REBUILT 3-STAGE CHRONOLOGICAL TRACEABILITY METHODS
    // =========================================================================

    /**
     * Stage 1: Search Entrance Vouchers matching the entered reception tracking number(s)
     */
    async fetchTraceabilityVouchers() {
      this.traceabilityVouchersLoading = true;
      try {
        const tokens = Array.isArray(this.traceabilityTrackingNumbers)
          ? this.traceabilityTrackingNumbers.filter(Boolean)
          : [];
        const vouchers = await gqlGetTraceabilityVouchers({
          trackingNumbers: tokens,
          status: this.traceabilityStatus,
        });
        this.traceabilityVouchersList = vouchers || [];

        // Build quick selection chips if not populated
        if (!this.quickTraceabilityLots.length) {
          const quick = [];
          this.traceabilityVouchersList.forEach(v => {
            (v.tracking_numbers || []).forEach(t => {
              if (t && !quick.includes(t)) quick.push(t);
            });
          });
          this.quickTraceabilityLots = quick.slice(0, 15);
        }

        // Synchronize legacy lots list
        this.traceabilityLotsList = this.traceabilityVouchersList.map(v => ({
          lot_number: (v.tracking_numbers && v.tracking_numbers[0]) || v.voucher_number,
          voucher_key: v.key,
          voucher_type: v.voucher_type,
          voucher_number: v.voucher_number,
          first_date: v.date,
          supplier_name: v.supplier_name,
          initial_weight: v.total_weight,
          remaining_weight: v.remaining_weight,
          articles_count: v.articles_count,
          status: v.status,
          types: v.article_types || [],
        }));
      } catch (e) {
        console.warn('fetchTraceabilityVouchers GraphQL error, fallback to REST:', e);
        try {
          const tokens = Array.isArray(this.traceabilityTrackingNumbers)
            ? this.traceabilityTrackingNumbers.filter(Boolean)
            : [];
          const params = {
            tracking_numbers: tokens.length ? tokens.join(',') : undefined,
            status: this.traceabilityStatus,
          };
          const r = await axios.get('/api/warehouses/traceability/search-vouchers', { params });
          this.traceabilityVouchersList = r.data.vouchers || [];

          if (!this.quickTraceabilityLots.length) {
            const quick = [];
            this.traceabilityVouchersList.forEach(v => {
              (v.tracking_numbers || []).forEach(t => {
                if (t && !quick.includes(t)) quick.push(t);
              });
            });
            this.quickTraceabilityLots = quick.slice(0, 15);
          }

          this.traceabilityLotsList = this.traceabilityVouchersList.map(v => ({
            lot_number: (v.tracking_numbers && v.tracking_numbers[0]) || v.voucher_number,
            voucher_key: v.key,
            voucher_type: v.voucher_type,
            voucher_number: v.voucher_number,
            first_date: v.date,
            supplier_name: v.supplier_name,
            initial_weight: v.total_weight,
            remaining_weight: v.remaining_weight,
            articles_count: v.articles_count,
            status: v.status,
            types: v.article_types || [],
          }));
        } catch (err) {
          console.error('fetchTraceabilityVouchers fallback error:', err);
          this.triggerSummarySnackbar({
            title: this.tLabel('error', 'Erreur'),
            message: this.tLabel('error_fetching_vouchers', 'Impossible de récupérer les bons d\'entrée.'),
            color: 'error',
            icon: 'mdi-alert-circle',
          });
        }
      } finally {
        this.traceabilityVouchersLoading = false;
      }
    },

    addTraceabilityTrackingToken(val) {
      if (!val) return;
      let tokensToAdd = [];
      if (Array.isArray(val)) {
        tokensToAdd = val.flatMap(v => String(v).split(',').map(s => s.trim()));
      } else {
        tokensToAdd = String(val).split(',').map(s => s.trim());
      }
      tokensToAdd = tokensToAdd.filter(t => t && !this.traceabilityTrackingNumbers.includes(t));
      if (tokensToAdd.length) {
        this.traceabilityTrackingNumbers = [...this.traceabilityTrackingNumbers, ...tokensToAdd];
      }
      this.traceabilityTrackingSearchInput = '';
      this.fetchTraceabilityVouchers();
    },

    removeTraceabilityTrackingToken(token) {
      this.traceabilityTrackingNumbers = this.traceabilityTrackingNumbers.filter(t => t !== token);
      this.fetchTraceabilityVouchers();
    },

    quickSelectTrackingNumber(lotNum) {
      const clean = String(lotNum).trim();
      if (!this.traceabilityTrackingNumbers.includes(clean)) {
        this.traceabilityTrackingNumbers = [clean];
      }
      this.fetchTraceabilityVouchers();
    },

    /**
     * Stage 2: User selects an entrance voucher -> load its articles
     */
    async selectTraceabilityVoucher(voucher) {
      if (!voucher) return;
      this.selectedTraceabilityVoucher = voucher;
      this.traceabilityCurrentStage = 2;
      this.traceabilityArticlesList = [];
      this.selectedTraceabilityArticleIds = [];
      this.traceabilityTimelineData = null;

      await this.fetchTraceabilityVoucherArticles(voucher.balance_ids || []);
    },

    async fetchTraceabilityVoucherArticles(balanceIds) {
      if (!balanceIds || !balanceIds.length) return;
      this.traceabilityArticlesLoading = true;
      try {
        const articles = await gqlGetTraceabilityVoucherArticles(balanceIds);
        this.traceabilityArticlesList = articles || [];

        // By default, select all articles in this voucher
        this.selectedTraceabilityArticleIds = this.traceabilityArticlesList.map(a => a.id);
      } catch (e) {
        console.warn('fetchTraceabilityVoucherArticles GraphQL error, fallback to REST:', e);
        try {
          const params = {
            balance_ids: balanceIds.join(','),
          };
          const r = await axios.get('/api/warehouses/traceability/voucher-articles', { params });
          this.traceabilityArticlesList = r.data.articles || [];
          this.selectedTraceabilityArticleIds = this.traceabilityArticlesList.map(a => a.id);
        } catch (err) {
          console.error('fetchTraceabilityVoucherArticles fallback error:', err);
          this.triggerSummarySnackbar({
            title: this.tLabel('error', 'Erreur'),
            message: this.tLabel('error_fetching_voucher_articles', 'Impossible de charger les articles de ce bon.'),
            color: 'error',
            icon: 'mdi-alert-circle',
          });
        }
      } finally {
        this.traceabilityArticlesLoading = false;
      }
    },

    toggleSelectAllArticles() {
      if (this.allArticlesSelectedInVoucher) {
        this.selectedTraceabilityArticleIds = [];
      } else {
        this.selectedTraceabilityArticleIds = this.traceabilityArticlesList.map(a => a.id);
      }
    },

    toggleSelectArticle(articleId) {
      const idx = this.selectedTraceabilityArticleIds.indexOf(articleId);
      if (idx >= 0) {
        this.selectedTraceabilityArticleIds.splice(idx, 1);
      } else {
        this.selectedTraceabilityArticleIds.push(articleId);
      }
    },

    /**
     * Stage 3: User confirms article selection -> generate chronological timeline
     */
    async generateTraceabilityTimeline() {
      if (!this.selectedTraceabilityArticleIds.length) {
        this.triggerSummarySnackbar({
          title: this.tLabel('warning', 'Attention'),
          message: this.tLabel('select_at_least_one_article', 'Veuillez sélectionner au moins un article à tracer.'),
          color: 'warning',
          icon: 'mdi-alert',
        });
        return;
      }

      this.traceabilityCurrentStage = 3;
      this.traceabilityTimelineLoading = true;
      try {
        const data = await gqlGetTraceabilityPedigreeTimeline(this.selectedTraceabilityArticleIds);
        this.traceabilityTimelineData = data;
        this.traceabilityActiveLotData = data; // Sync with legacy modal/data
      } catch (e) {
        console.warn('generateTraceabilityTimeline GraphQL error, fallback to REST:', e);
        try {
          const payload = {
            balance_ids: this.selectedTraceabilityArticleIds,
          };
          const r = await axios.post('/api/warehouses/traceability/pedigree-timeline', payload);
          this.traceabilityTimelineData = r.data;
          this.traceabilityActiveLotData = r.data;
        } catch (err) {
          console.error('generateTraceabilityTimeline fallback error:', err);
          this.triggerSummarySnackbar({
            title: this.tLabel('error', 'Erreur'),
            message: this.tLabel('error_generating_timeline', 'Impossible de générer la traçabilité chronologique.'),
            color: 'error',
            icon: 'mdi-alert-circle',
          });
        }
      } finally {
        this.traceabilityTimelineLoading = false;
      }
    },

    goToTraceabilityStage(stage) {
      if (stage === 1) {
        this.traceabilityCurrentStage = 1;
      } else if (stage === 2) {
        if (this.selectedTraceabilityVoucher) {
          this.traceabilityCurrentStage = 2;
        } else {
          this.triggerSummarySnackbar({
            title: this.tLabel('notice', 'Information'),
            message: this.tLabel('select_voucher_first', 'Veuillez d\'abord sélectionner un bon d\'entrée.'),
            color: 'info',
            icon: 'mdi-information-outline',
          });
        }
      } else if (stage === 3) {
        if (this.selectedTraceabilityArticleIds.length > 0) {
          if (!this.traceabilityTimelineData) {
            this.generateTraceabilityTimeline();
          } else {
            this.traceabilityCurrentStage = 3;
          }
        } else {
          this.triggerSummarySnackbar({
            title: this.tLabel('notice', 'Information'),
            message: this.tLabel('select_at_least_one_article', 'Veuillez sélectionner au moins un article.'),
            color: 'warning',
            icon: 'mdi-alert',
          });
        }
      }
    },

    resetTraceabilityWorkflow() {
      this.traceabilityCurrentStage = 1;
      this.selectedTraceabilityVoucher = null;
      this.traceabilityArticlesList = [];
      this.selectedTraceabilityArticleIds = [];
      this.traceabilityTimelineData = null;
    },

    toggleTraceabilityTimelineSort() {
      this.traceabilityTimelineSortOrder = this.traceabilityTimelineSortOrder === 'asc' ? 'desc' : 'asc';
    },

    setTraceabilityTimelineFilter(filter) {
      this.traceabilityTimelineFilter = filter;
    },

    switchToTraceabilityWithLot(lot) {
      const lotNumber = (typeof lot === 'object' && lot !== null) ? (lot.lot_number || lot.raw_lot) : lot;
      const clean = String(lotNumber).trim();
      this.traceabilityTrackingNumbers = [clean];
      this.activeViewMode = 'traceability';
      this.traceabilityCurrentStage = 1;
      this.selectedTraceabilityVoucher = null;
      this.traceabilityArticlesList = [];
      this.selectedTraceabilityArticleIds = [];
      this.traceabilityTimelineData = null;
      this.fetchTraceabilityVouchers();
    },

    // Legacy fallbacks for compatibility
    async fetchTraceabilityLots() {
      return this.fetchTraceabilityVouchers();
    },

    async selectTraceabilityLot(lotNumber) {
      this.quickSelectTrackingNumber(lotNumber);
    },

    toggleStockableTypeFilter(val) {
      const idx = this.filterStockableTypes.indexOf(val);
      if (idx >= 0) {
        this.filterStockableTypes.splice(idx, 1);
      } else {
        this.filterStockableTypes.push(val);
      }
      if (this.activeViewMode === 'stock_par_lot') {
        this.fetchStockParLot(true);
      } else if (this.activeViewMode === 'traceability') {
        if (this.traceabilityCurrentStage === 1) {
          this.fetchTraceabilityVouchers();
        } else if (this.traceabilityCurrentStage === 2 && this.selectedTraceabilityVoucher) {
          this.fetchTraceabilityVoucherArticles(this.selectedTraceabilityVoucher.balance_ids || []);
        } else if (this.traceabilityCurrentStage === 3 && this.selectedTraceabilityArticleIds.length) {
          this.generateTraceabilityTimeline();
        }
      }
    },

    clearStockableTypesFilter() {
      this.filterStockableTypes = [];
      if (this.activeViewMode === 'stock_par_lot') {
        this.fetchStockParLot(true);
      } else if (this.activeViewMode === 'traceability') {
        if (this.traceabilityCurrentStage === 1) {
          this.fetchTraceabilityVouchers();
        } else if (this.traceabilityCurrentStage === 2 && this.selectedTraceabilityVoucher) {
          this.fetchTraceabilityVoucherArticles(this.selectedTraceabilityVoucher.balance_ids || []);
        } else if (this.traceabilityCurrentStage === 3 && this.selectedTraceabilityArticleIds.length) {
          this.generateTraceabilityTimeline();
        }
      }
    },

    // 3. Fetch Chambers Overview
    async fetchChambers() {
      this.chambersLoading = true;
      try {
        const chambers = await gqlGetChambersOverview();
        this.chambersList = chambers || [];
      } catch (e) {
        console.warn('fetchChambers GraphQL error, fallback to REST:', e);
        try {
          const r = await axios.get('/api/warehouses/chambers-overview');
          this.chambersList = r.data.chambers || [];
        } catch (err) {
          console.error('fetchChambers fallback error:', err);
        }
      } finally {
        this.chambersLoading = false;
      }
    },

    drillIntoChamber(chamber) {
      this.selectedWarehouseIds = [chamber.id];
      this.saveCachedWarehouses();
      this.activeViewMode = 'stock_par_lot';
      this.fetchStockParLot(true);
    },

    // 4. Fetch Species Overview
    async fetchSpecies() {
      this.speciesLoading = true;
      try {
        const species = await gqlGetSpeciesOverview(this.selectedWarehouseIds);
        this.speciesList = species || [];
      } catch (e) {
        console.warn('fetchSpecies GraphQL error, fallback to REST:', e);
        try {
          const r = await axios.get('/api/warehouses/species-overview', {
            params: { warehouse_ids: this.selectedWarehouseIds },
          });
          this.speciesList = r.data.species || [];
        } catch (err) {
          console.error('fetchSpecies fallback error:', err);
        }
      } finally {
        this.speciesLoading = false;
      }
    },

    filterBySpeciesLots(species) {
      this.searchQuery = species.article_name;
      this.activeViewMode = 'stock_par_lot';
      this.fetchStockParLot(true);
    },

    // 5. Fetch Movement Journal (NO PAGINATION per user requirement)
    async fetchJournal() {
      this.journalLoading = true;
      try {
        const data = await gqlGetStockMovementsJournal({
          warehouseIds: this.selectedWarehouseIds,
          stockableTypes: this.filterStockableTypes,
          type: this.journalFilterType,
          search: this.journalSearch,
          limit: 4000,
        });
        this.journalList = data.movements || [];
        this.journalTotals = data.totals || {
          total_count: this.journalList.length,
          total_in_weight: 0,
          total_out_weight: 0,
          net_weight: 0,
          total_in_boxes: 0,
          total_out_boxes: 0,
        };
      } catch (e) {
        console.warn('fetchJournal GraphQL error, fallback to REST:', e);
        try {
          const params = {
            warehouse_ids: this.selectedWarehouseIds,
            date_from: this.movementDateFrom || undefined,
            date_to: this.movementDateTo || undefined,
            search: this.journalSearch || undefined,
            movement_type: this.journalFilterType,
            no_pagination: true,
          };
          const r = await axios.get('/api/warehouses/stock-movements-journal', { params });
          this.journalList = r.data.movements || [];
          this.journalTotals = r.data.totals || {
            total_count: this.journalList.length,
            total_in_weight: 0,
            total_out_weight: 0,
            net_weight: 0,
            total_in_boxes: 0,
            total_out_boxes: 0,
          };
        } catch (err) {
          console.error('fetchJournal fallback error:', err);
        }
      } finally {
        this.journalLoading = false;
      }
    },

    // Helpers
    lotStatusColor(status) {
      if (status === 'in_stock') return '#00897B';
      if (status === 'partially_out') return '#FB8C00';
      return '#9E9E9E';
    },

    lotStatusLabel(status) {
      if (status === 'in_stock') return this.tLabel('in_stock_label', 'En Stock');
      if (status === 'partially_out') return this.tLabel('partially_out', 'Partiellement Sorti');
      return this.tLabel('depleted', 'Épuisé');
    },

    ageColor(days) {
      if (days < 7) return '#00897B';
      if (days <= 30) return '#1E88E5';
      if (days <= 90) return '#FB8C00';
      return '#E53935';
    },

    getAgeClass(days) {
      const d = Number(days) || 0;
      if (d < 7) return 'age-fresh';
      if (d <= 30) return 'age-optimal';
      if (d <= 90) return 'age-warning';
      return 'age-alert';
    },

    tIcon(cleanType) {
      if (cleanType === 'PackageType') return 'mdi-package-variant-closed';
      if (cleanType === 'GuanoType') return 'mdi-sprout';
      return 'mdi-fish';
    },

    tColor(cleanType) {
      if (cleanType === 'PackageType') return '#6D4C41';
      if (cleanType === 'GuanoType') return '#2E7D32';
      return '#00897B';
    },

    formatEventTitle(ev) {
      if (!ev) return '';
      const id = ev.source_id || '';
      const action = ev.event_action;
      
      if (action === 'inflow_charge') {
        return this.tLabel('event_inflow_charge', 'Entrée Réception / Charge #{id}').replace('{id}', id);
      }
      if (action === 'inflow_production') {
        return this.tLabel('event_inflow_production', 'Entrée Production #{id}').replace('{id}', id);
      }
      if (action === 'inflow_transfer') {
        return this.tLabel('event_inflow_transfer', 'Entrée Transfert Inter-Chambres #{id}').replace('{id}', id);
      }
      if (action === 'inflow_inventory') {
        return this.tLabel('event_inflow_inventory', 'Entrée Calibrage Inventaire #{id}').replace('{id}', id);
      }
      if (action === 'inflow_default') {
        return this.tLabel('event_inflow_default', 'Entrée en Stock #{id}').replace('{id}', id);
      }
      if (action === 'outflow_shipping') {
        return this.tLabel('event_outflow_shipping', 'Expédition / Vente Client #{id}').replace('{id}', id);
      }
      if (action === 'outflow_production') {
        return this.tLabel('event_outflow_production', 'Consommation Atelier Production #{id}').replace('{id}', id);
      }
      if (action === 'outflow_transfer') {
        return this.tLabel('event_outflow_transfer', 'Transfert Sortant Chambre #{id}').replace('{id}', id);
      }
      if (action === 'outflow_reset') {
        return this.tLabel('event_outflow_reset', 'Ajustement Réinitialisation Stock');
      }
      if (action === 'outflow_default') {
        return this.tLabel('event_outflow_default', 'Sortie de Stock #{id}').replace('{id}', id);
      }

      if (ev.title) {
        if (ev.title.includes('Entrée Calibrage Inventaire')) {
          const match = ev.title.match(/#(\d+)/);
          return this.tLabel('event_inflow_inventory', 'Entrée Calibrage Inventaire #{id}').replace('{id}', match ? match[1] : id);
        }
        if (ev.title.includes('Entrée Réception / Charge')) {
          const match = ev.title.match(/#(\d+)/);
          return this.tLabel('event_inflow_charge', 'Entrée Réception / Charge #{id}').replace('{id}', match ? match[1] : id);
        }
        if (ev.title.includes('Entrée Production')) {
          const match = ev.title.match(/#(\d+)/);
          return this.tLabel('event_inflow_production', 'Entrée Production #{id}').replace('{id}', match ? match[1] : id);
        }
        if (ev.title.includes('Entrée Transfert Inter-Chambres')) {
          const match = ev.title.match(/#(\d+)/);
          return this.tLabel('event_inflow_transfer', 'Entrée Transfert Inter-Chambres #{id}').replace('{id}', match ? match[1] : id);
        }
        if (ev.title.includes('Expédition / Vente Client')) {
          const match = ev.title.match(/#(\d+)/);
          return this.tLabel('event_outflow_shipping', 'Expédition / Vente Client #{id}').replace('{id}', match ? match[1] : id);
        }
        if (ev.title.includes('Consommation Atelier Production')) {
          const match = ev.title.match(/#(\d+)/);
          return this.tLabel('event_outflow_production', 'Consommation Atelier Production #{id}').replace('{id}', match ? match[1] : id);
        }
        if (ev.title.includes('Transfert Sortant Chambre')) {
          const match = ev.title.match(/#(\d+)/);
          return this.tLabel('event_outflow_transfer', 'Transfert Sortant Chambre #{id}').replace('{id}', match ? match[1] : id);
        }
        if (ev.title.includes('Ajustement Réinitialisation Stock')) {
          return this.tLabel('event_outflow_reset', 'Ajustement Réinitialisation Stock');
        }
      }

      return ev.title || '';
    },

    directionLabel(ev) {
      if (!ev) return '';
      if (ev.is_inflow) {
        return this.tLabel('direction_inflow', 'ENTRÉE');
      }
      if (ev.type === 'transfer') {
        return this.tLabel('direction_transfer', 'TRANSFERT');
      }
      return this.tLabel('direction_outflow', 'SORTIE');
    },

    voucherTypeLabel(type) {
      if (type === 'Charge') return this.tLabel('voucher_type_charge', 'Charge / Réception');
      if (type === 'Production') return this.tLabel('voucher_type_production', 'Production');
      if (type === 'Inventory') return this.tLabel('voucher_type_inventory', 'Inventaire');
      if (type === 'Transfer') return this.tLabel('voucher_type_transfer', 'Transfert');
      return type || '';
    },

    articleTypeLabel(cleanType) {
      if (cleanType === 'FishType') {
        return this.tLabel('stockable_fishtype_short', 'Poisson');
      }
      if (cleanType === 'GuanoType') {
        return this.tLabel('stockable_guanotype_short', 'Guano');
      }
      if (cleanType === 'PackageType') {
        return this.tLabel('stockable_packagetype_short', 'Emballage');
      }
      return cleanType || '';
    },

    formatPartnerLabel(partner) {
      if (!partner) return '-';
      if (partner === 'Inventaire') return this.tLabel('partner_inventory', 'Inventaire');
      if (partner === 'Usine') return this.tLabel('partner_factory', 'Usine');
      if (partner === 'Sortie') return this.tLabel('partner_outflow', 'Sortie');
      if (partner === 'Atelier de Traitement') return this.tLabel('partner_processing_workshop', 'Atelier de Traitement');
      return partner;
    },

    srcLabel(sourceType) {
      if (!sourceType) return this.tLabel('movement', 'Mouvement');
      if (sourceType.includes('Shipping')) return this.tLabel('src_shipping', 'Expédition');
      if (sourceType.includes('Production')) return this.tLabel('src_production', 'Production');
      if (sourceType.includes('Charge')) return this.tLabel('src_reception', 'Réception');
      if (sourceType.includes('Transfer')) return this.tLabel('src_transfer', 'Transfert');
      if (sourceType.includes('Inventory')) return this.tLabel('src_inventory', 'Inventaire');
      return sourceType;
    },

    srcColor(sourceType) {
      if (!sourceType) return 'grey';
      if (sourceType.includes('Shipping')) return '#372e7d';
      if (sourceType.includes('Production')) return '#00897B';
      if (sourceType.includes('Charge')) return '#1565C0';
      if (sourceType.includes('Transfer')) return '#F9A825';
      if (sourceType.includes('Inventory')) return '#6D4C41';
      return 'grey';
    },

    goToSource(article) {
      if (!article?.clean_source) return;
      const src = article.clean_source;
      const id = article.source_id;
      if (src === 'DetailCharge') this.$router.push(`/charges/view/${id}`);
      else if (src === 'DetailProduction') this.$router.push(`/productions/view/${id}`);
      else if (src === 'WarehouseTransfer') this.$router.push(`/warehouse-transfers/view/${id}`);
    },

    goToSourceOp(op) {
      if (!op?.source_type || !op?.source_id) return;
      const src = op.source_type;
      const id = op.source_id;
      if (src === 'DetailShipping') this.$router.push(`/shippings/view/${id}`);
      else if (src === 'DetailProduction') this.$router.push(`/productions/view/${id}`);
      else if (src === 'WarehouseTransfer') this.$router.push(`/warehouse-transfers/view/${id}`);
    },

    // Export to Excel / CSV
    exportToExcel() {
      const headers = [
        this.tLabel('reception_tracking_number', 'N° Suivi de Réception'),
        this.tLabel('date', 'Date Réception'),
        this.tLabel('age_days', 'Âge (Jours)'),
        this.tLabel('supplier', 'Fournisseur / Bateau'),
        this.tLabel('chambers', 'Chambres'),
        this.tLabel('species', 'Espèces'),
        this.tLabel('initial_weight_label', 'Poids Initial (Kg)'),
        this.tLabel('remaining_weight_label', 'Poids Restant (Kg)'),
        this.tLabel('exited_weight', 'Poids Sorti (Kg)'),
        this.tLabel('destocking_rate', 'Taux Consommation (%)'),
        this.tLabel('remaining_boxes', 'Cartons Restants'),
        this.tLabel('wap', 'PMP (DH/Kg)'),
        this.tLabel('valuation', 'Valorisation (DH)'),
        this.tLabel('status', 'Statut'),
      ];
      const rows = this.lots.map(l => [
        `"${l.lot_number}"`,
        l.first_date || '',
        l.age_days,
        `"${(l.supplier_name || '').replace(/"/g, '""')}"`,
        `"${l.warehouses.map(w => w.name).join(', ')}"`,
        `"${(l.species_list || []).join(', ')}"`,
        l.initial_weight,
        l.remaining_weight,
        l.exited_weight,
        l.consumption_pct,
        l.remaining_boxes,
        l.avg_unit_price,
        l.total_valuation,
        this.lotStatusLabel(l.status),
      ]);

      const csvContent = 'data:text/csv;charset=utf-8,\uFEFF'
        + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');

      const encodedUri = encodeURI(csvContent);
      const link = document.createElement('a');
      link.setAttribute('href', encodedUri);
      link.setAttribute('download', `Stock_Par_Suivi_Reception_${new Date().toISOString().substring(0, 10)}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      this.triggerSummarySnackbar({
        title: this.tLabel('export_excel', 'Export Excel'),
        message: `${this.lots.length} numéros de suivi de réception exportés avec succès.`,
        color: '#a21ee5',
        icon: 'mdi-file-excel',
      });
    },

    async openPrintDialog(view = null) {
      let target = view || this.activeViewMode || 'stock_par_lot';
      if (target === 'traceability') target = 'stock_par_lot';
      this.printModalTargetView = target;
      this.printModalOpen = true;
      await this.ensureViewDataLoaded(target);
    },

    printReport(view = null) {
      this.openPrintDialog(view);
    },

    async onPrintModalViewChange(view) {
      this.printModalTargetView = view;
      await this.ensureViewDataLoaded(view);
    },

    onPrintModalDataLoaded({ view, data, totals, summary }) {
      if (view === 'chambers' && (!this.chambersList || !this.chambersList.length)) {
        this.chambersList = data;
      } else if (view === 'species' && (!this.speciesList || !this.speciesList.length)) {
        this.speciesList = data;
      } else if (view === 'journal' && (!this.journalList || !this.journalList.length)) {
        this.journalList = data;
        if (totals) this.journalTotals = totals;
      } else if (view === 'analytics' && (!this.analyticsData || !this.analyticsData.timeline)) {
        this.analyticsData = data;
      } else if (view === 'stock_par_lot' && (!this.lots || !this.lots.length)) {
        this.lots = data;
        if (summary) this.lotsSummary = summary;
      }
    },

    async ensureViewDataLoaded(view) {
      try {
        if (view === 'stock_par_lot') {
          if (!this.lots || !this.lots.length) {
            await this.fetchStockParLot();
          }
        } else if (view === 'chambers') {
          if (!this.chambersList || !this.chambersList.length) {
            await this.fetchChambers();
          }
        } else if (view === 'species') {
          if (!this.speciesList || !this.speciesList.length) {
            await this.fetchSpecies();
          }
        } else if (view === 'journal') {
          if (!this.journalList || !this.journalList.length) {
            await this.fetchJournal();
          }
        } else if (view === 'analytics') {
          if (!this.analyticsData || !this.analyticsData.timeline || !this.analyticsData.timeline.length) {
            await this.fetchAnalytics();
          }
        } else if (view === 'traceability') {
          if (!this.traceabilityVouchersList || !this.traceabilityVouchersList.length) {
            await this.fetchTraceabilityVouchers();
          }
        }
      } catch (e) {
        console.error('[WarehouseDashboard] ensureViewDataLoaded error for ' + view, e);
      }
    },

    onDocumentPrinted({ view, columns }) {
      this.triggerSummarySnackbar({
        title: this.tLabel('print', 'Impression'),
        message: `${this.tLabel('warehouse_print_modal_title', 'Rapport')} (${columns.length} ${this.tLabel('selected_columns_count', 'colonnes')})`,
        color: '#a21ee5',
        icon: 'mdi-printer-check',
      });
    },

    // =========================================================================
    // MULTI-COLUMN FILTER METHODS & TIERS (ALL WAREHOUSE TABLES)
    // =========================================================================
    hasLotColFilter(key) {
      return Boolean(this.lotColFilters[key] && this.lotColFilters[key].length > 0);
    },
    hasSpeciesColFilter(key) {
      return Boolean(this.speciesColFilters[key] && this.speciesColFilters[key].length > 0);
    },
    hasJournalColFilter(key) {
      return Boolean(this.journalColFilters[key] && this.journalColFilters[key].length > 0);
    },

    clearLotColFilter(key) {
      if (this.lotColFilters[key]) {
        this.lotColFilters[key] = [];
      }
    },
    clearAllLotColFilters() {
      Object.keys(this.lotColFilters).forEach(k => {
        this.lotColFilters[k] = [];
      });
      this.triggerSummarySnackbar({
        title: this.tLabel('filter', 'Filtres'),
        message: this.tLabel('filters_cleared', 'Filtres de colonnes effacés'),
        color: '#a21ee5',
        icon: 'mdi-filter-off',
      });
    },

    getLotColTitle(key) {
      const titles = {
        lot_number: this.tLabel('lot_number', 'N° Suivi Réception'),
        status: this.tLabel('status', 'Statut'),
        date: this.tLabel('date', 'Date'),
        supplier: this.tLabel('suppliers', 'Fournisseur'),
        chamber: this.tLabel('chamber_location', 'Chambre Froide'),
        species: this.tLabel('contained_species', 'Espèces / Articles'),
        rem_weight: this.tLabel('remaining', 'Solde en Stock'),
        boxes: this.tLabel('package_count', 'Cartons'),
        valuation: this.tLabel('total_price', 'Valeur'),
      };
      return titles[key] || key;
    },

    formatLotColFilterDisplay(key, vals) {
      if (!vals || !vals.length) return '';
      if (key === 'status') {
        return vals.map(s => this.lotStatusLabel(s)).join(', ');
      }
      if (key === 'lot_number') {
        return vals.map(v => '#' + v).join(', ');
      }
      if (key === 'rem_weight' || key === 'boxes' || key === 'valuation') {
        const opts = this.lotFilterOpts(key);
        const map = Object.fromEntries(opts.map(o => [o.value, o.label]));
        return vals.map(v => map[v] || v).join(', ');
      }
      return vals.join(', ');
    },

    lotFilterOpts(key) {
      const items = this.lots || [];
      if (key === 'lot_number') {
        const map = {};
        items.forEach(l => {
          const val = String(l.lot_number);
          map[val] = (map[val] || 0) + 1;
        });
        return Object.keys(map).sort((a, b) => a.localeCompare(b, undefined, { numeric: true })).map(v => ({
          value: v,
          label: '#' + v,
          count: map[v],
        }));
      }
      if (key === 'status') {
        const map = {};
        items.forEach(l => {
          const val = l.status || 'in_stock';
          map[val] = (map[val] || 0) + 1;
        });
        return ['in_stock', 'partially_out', 'depleted'].filter(s => map[s] != null).map(s => ({
          value: s,
          label: this.lotStatusLabel(s),
          count: map[s] || 0,
        }));
      }
      if (key === 'date') {
        const map = {};
        items.forEach(l => {
          const val = l.first_date ? this.main.formateDate(l.first_date) : '-';
          map[val] = (map[val] || 0) + 1;
        });
        return Object.keys(map).sort().reverse().map(v => ({ value: v, label: v, count: map[v] }));
      }
      if (key === 'supplier') {
        const map = {};
        items.forEach(l => {
          const val = (l.supplier_name && l.supplier_name !== '-') ? l.supplier_name.trim() : '-';
          map[val] = (map[val] || 0) + 1;
        });
        return Object.keys(map).sort().map(v => ({ value: v, label: v, count: map[v] }));
      }
      if (key === 'chamber') {
        const map = {};
        items.forEach(l => {
          const val = l.primary_warehouse || '-';
          map[val] = (map[val] || 0) + 1;
          if (Array.isArray(l.warehouses)) {
            l.warehouses.forEach(w => {
              const wn = typeof w === 'object' ? w.name : w;
              if (wn && wn !== val && wn !== '-') map[wn] = (map[wn] || 0) + 1;
            });
          }
        });
        return Object.keys(map).sort().map(v => ({ value: v, label: v, count: map[v] }));
      }
      if (key === 'species') {
        const map = {};
        items.forEach(l => {
          (l.species_list || []).forEach(sp => {
            if (sp && sp !== '-') map[sp] = (map[sp] || 0) + 1;
          });
        });
        return Object.keys(map).sort().map(v => ({ value: v, label: v, count: map[v] }));
      }
      if (key === 'rem_weight') {
        const tiers = [
          { value: 'tier_gt_10k', label: '> 10 000 Kg' },
          { value: 'tier_5k_10k', label: '5 000 - 10 000 Kg' },
          { value: 'tier_1k_5k', label: '1 000 - 5 000 Kg' },
          { value: 'tier_lt_1k', label: '< 1 000 Kg' },
          { value: 'tier_zero', label: this.tLabel('zero_depleted', '0 Kg (Épuisé)') },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(l => this.getWeightTier(l.remaining_weight) === t.value).length,
        })).filter(t => t.count > 0);
      }
      if (key === 'boxes') {
        const bUnit = this.tLabel('boxes', 'cartons');
        const tiers = [
          { value: 'box_gt_1000', label: `> 1 000 ${bUnit}` },
          { value: 'box_500_1000', label: `500 - 1 000 ${bUnit}` },
          { value: 'box_100_500', label: `100 - 500 ${bUnit}` },
          { value: 'box_lt_100', label: `< 100 ${bUnit}` },
          { value: 'box_zero', label: `0 ${bUnit}` },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(l => this.getBoxesTier(l.remaining_boxes) === t.value).length,
        })).filter(t => t.count > 0);
      }
      if (key === 'valuation') {
        const tiers = [
          { value: 'val_gt_100k', label: `> 100 000 ${this.main.currency}` },
          { value: 'val_50k_100k', label: `50 000 - 100 000 ${this.main.currency}` },
          { value: 'val_10k_50k', label: `10 000 - 50 000 ${this.main.currency}` },
          { value: 'val_lt_10k', label: `< 10 000 ${this.main.currency}` },
          { value: 'val_zero', label: `0 ${this.main.currency}` },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(l => this.getValuationTier(l.total_valuation) === t.value).length,
        })).filter(t => t.count > 0);
      }
      return [];
    },

    speciesFilterOpts(key) {
      const items = this.speciesList || [];
      if (key === 'article_name' || key === 'family_name' || key === 'process_name') {
        const map = {};
        items.forEach(s => {
          const val = (s[key] && s[key] !== '-') ? s[key].trim() : '-';
          map[val] = (map[val] || 0) + 1;
        });
        return Object.keys(map).sort().map(v => ({ value: v, label: v, count: map[v] }));
      }
      if (key === 'rem_weight') {
        const tiers = [
          { value: 'tier_gt_10k', label: '> 10 000 Kg' },
          { value: 'tier_5k_10k', label: '5 000 - 10 000 Kg' },
          { value: 'tier_1k_5k', label: '1 000 - 5 000 Kg' },
          { value: 'tier_lt_1k', label: '< 1 000 Kg' },
          { value: 'tier_zero', label: '0 Kg' },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(s => this.getWeightTier(s.total_weight) === t.value).length,
        })).filter(t => t.count > 0);
      }
      if (key === 'boxes') {
        const tiers = [
          { value: 'box_gt_1000', label: '> 1 000' },
          { value: 'box_500_1000', label: '500 - 1 000' },
          { value: 'box_100_500', label: '100 - 500' },
          { value: 'box_lt_100', label: '< 100' },
          { value: 'box_zero', label: '0' },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(s => this.getBoxesTier(s.total_boxes) === t.value).length,
        })).filter(t => t.count > 0);
      }
      if (key === 'wap') {
        const tiers = [
          { value: 'wap_gt_100', label: `> 100 ${this.main.currency}/Kg` },
          { value: 'wap_50_100', label: `50 - 100 ${this.main.currency}/Kg` },
          { value: 'wap_20_50', label: `20 - 50 ${this.main.currency}/Kg` },
          { value: 'wap_lt_20', label: `< 20 ${this.main.currency}/Kg` },
          { value: 'wap_zero', label: '-' },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(s => this.getWapTier(s.wap) === t.value).length,
        })).filter(t => t.count > 0);
      }
      if (key === 'valuation') {
        const tiers = [
          { value: 'val_gt_100k', label: `> 100 000 ${this.main.currency}` },
          { value: 'val_50k_100k', label: `50 000 - 100 000 ${this.main.currency}` },
          { value: 'val_10k_50k', label: `10 000 - 50 000 ${this.main.currency}` },
          { value: 'val_lt_10k', label: `< 10 000 ${this.main.currency}` },
          { value: 'val_zero', label: `0 ${this.main.currency}` },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(s => this.getValuationTier(s.total_valuation) === t.value).length,
        })).filter(t => t.count > 0);
      }
      return [];
    },

    clearSpeciesColFilter(key) {
      if (this.speciesColFilters[key]) {
        this.speciesColFilters[key] = [];
      }
    },
    clearAllSpeciesColFilters() {
      Object.keys(this.speciesColFilters).forEach(k => {
        this.speciesColFilters[k] = [];
      });
      this.triggerSummarySnackbar({
        title: this.tLabel('filter', 'Filtres'),
        message: this.tLabel('filters_cleared', 'Filtres de colonnes effacés'),
        color: '#a21ee5',
        icon: 'mdi-filter-off',
      });
    },
    getSpeciesColTitle(key) {
      const titles = {
        article_name: this.tLabel('article_type_name', 'Espèce / Article'),
        family_name: this.tLabel('fish_family', 'Famille'),
        process_name: this.tLabel('process', 'Traitement'),
        rem_weight: this.tLabel('remaining_weight_label', 'Poids en Stock'),
        boxes: this.tLabel('package_count', 'Cartons'),
        wap: this.tLabel('wap', 'PMP'),
        valuation: this.tLabel('total_price', 'Valorisation'),
      };
      return titles[key] || key;
    },
    formatSpeciesColFilterDisplay(key, vals) {
      if (!vals || !vals.length) return '';
      if (key === 'rem_weight' || key === 'boxes' || key === 'wap' || key === 'valuation') {
        const opts = this.speciesFilterOpts(key);
        const map = Object.fromEntries(opts.map(o => [o.value, o.label]));
        return vals.map(v => map[v] || v).join(', ');
      }
      return vals.join(', ');
    },

    journalFilterOpts(key) {
      const items = this.journalList || [];
      if (key === 'direction') {
        const inLabel = this.tLabel('direction_inflow', 'IN');
        const outLabel = this.tLabel('direction_outflow', 'OUT');
        const entryLabel = this.tLabel('entry', 'Entrée');
        const exitLabel = this.tLabel('exit', 'Sortie');
        return [
          { value: 'IN', label: `${inLabel} (${entryLabel})`, count: items.filter(m => m.direction === 'IN').length },
          { value: 'OUT', label: `${outLabel} (${exitLabel})`, count: items.filter(m => m.direction === 'OUT').length },
        ].filter(t => t.count > 0);
      }
      if (key === 'date') {
        const map = {};
        items.forEach(m => {
          const val = m.date ? this.main.formateDate(m.date) : '-';
          map[val] = (map[val] || 0) + 1;
        });
        return Object.keys(map).sort().reverse().map(v => ({ value: v, label: v, count: map[v] }));
      }
      if (key === 'lot_number') {
        const map = {};
        items.forEach(m => {
          const val = String(m.lot_number || '-');
          map[val] = (map[val] || 0) + 1;
        });
        return Object.keys(map).sort((a, b) => a.localeCompare(b, undefined, { numeric: true })).map(v => ({
          value: v,
          label: '#' + v,
          count: map[v],
        }));
      }
      if (key === 'article_name' || key === 'warehouse_name' || key === 'partner_name') {
        const map = {};
        items.forEach(m => {
          const val = (m[key] && m[key] !== '-') ? m[key].trim() : '-';
          map[val] = (map[val] || 0) + 1;
        });
        return Object.keys(map).sort().map(v => ({ value: v, label: v, count: map[v] }));
      }
      if (key === 'source_type') {
        const map = {};
        items.forEach(m => {
          const val = this.srcLabel(m.source_type) || m.source_type || '-';
          map[val] = (map[val] || 0) + 1;
        });
        return Object.keys(map).sort().map(v => ({ value: v, label: v, count: map[v] }));
      }
      if (key === 'weight') {
        const tiers = [
          { value: 'tier_gt_10k', label: '> 10 000 Kg' },
          { value: 'tier_5k_10k', label: '5 000 - 10 000 Kg' },
          { value: 'tier_1k_5k', label: '1 000 - 5 000 Kg' },
          { value: 'tier_lt_1k', label: '< 1 000 Kg' },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(m => this.getWeightTier(m.weight) === t.value).length,
        })).filter(t => t.count > 0);
      }
      if (key === 'boxes') {
        const tiers = [
          { value: 'box_gt_1000', label: '> 1 000' },
          { value: 'box_500_1000', label: '500 - 1 000' },
          { value: 'box_100_500', label: '100 - 500' },
          { value: 'box_lt_100', label: '< 100' },
        ];
        return tiers.map(t => ({
          ...t,
          count: items.filter(m => this.getBoxesTier(m.boxes) === t.value).length,
        })).filter(t => t.count > 0);
      }
      return [];
    },

    clearJournalColFilter(key) {
      if (this.journalColFilters[key]) {
        this.journalColFilters[key] = [];
      }
    },
    clearAllJournalColFilters() {
      Object.keys(this.journalColFilters).forEach(k => {
        this.journalColFilters[k] = [];
      });
      this.triggerSummarySnackbar({
        title: this.tLabel('filter', 'Filtres'),
        message: this.tLabel('filters_cleared', 'Filtres de colonnes effacés'),
        color: '#a21ee5',
        icon: 'mdi-filter-off',
      });
    },
    getJournalColTitle(key) {
      const titles = {
        date: this.tLabel('date', 'Date'),
        direction: this.tLabel('movement_direction', 'Sens'),
        lot_number: this.tLabel('lot_number', 'N° Suivi'),
        article_name: this.tLabel('article_type_name', 'Article'),
        warehouse_name: this.tLabel('warehouse', 'Chambre'),
        source_type: this.tLabel('source_document', 'Document Source'),
        partner_name: this.tLabel('partner', 'Partenaire'),
        weight: this.tLabel('weight', 'Poids'),
        boxes: this.tLabel('package_count', 'Cartons'),
      };
      return titles[key] || key;
    },
    formatJournalColFilterDisplay(key, vals) {
      if (!vals || !vals.length) return '';
      if (key === 'lot_number') {
        return vals.map(v => '#' + v).join(', ');
      }
      if (key === 'weight' || key === 'boxes') {
        const opts = this.journalFilterOpts(key);
        const map = Object.fromEntries(opts.map(o => [o.value, o.label]));
        return vals.map(v => map[v] || v).join(', ');
      }
      return vals.join(', ');
    },

    getWeightTier(w) {
      const val = Number(w) || 0;
      if (val <= 0) return 'tier_zero';
      if (val >= 10000) return 'tier_gt_10k';
      if (val >= 5000) return 'tier_5k_10k';
      if (val >= 1000) return 'tier_1k_5k';
      return 'tier_lt_1k';
    },

    getBoxesTier(b) {
      const val = Number(b) || 0;
      if (val <= 0) return 'box_zero';
      if (val >= 1000) return 'box_gt_1000';
      if (val >= 500) return 'box_500_1000';
      if (val >= 100) return 'box_100_500';
      return 'box_lt_100';
    },

    getValuationTier(v) {
      const val = Number(v) || 0;
      if (val <= 0) return 'val_zero';
      if (val >= 100000) return 'val_gt_100k';
      if (val >= 50000) return 'val_50k_100k';
      if (val >= 10000) return 'val_10k_50k';
      return 'val_lt_10k';
    },

    getWapTier(p) {
      const val = Number(p) || 0;
      if (val <= 0) return 'wap_zero';
      if (val >= 100) return 'wap_gt_100';
      if (val >= 50) return 'wap_50_100';
      if (val >= 20) return 'wap_20_50';
      return 'wap_lt_20';
    },
  },
};
