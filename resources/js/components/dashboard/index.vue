<template>
  <div :class="['home-portal', main.is_dark ? 'home-dark' : 'home-light']">

    <!-- ============================================================
         1. Atmospheric Hero Greeting & Live Operations Beacon
         ============================================================ -->
    <div class="home-hero-card">
      <div class="hero-ambient-glow"></div>
      <div class="hero-content-wrap">
        
        <!-- Left: User Welcome & Live Status -->
        <div class="hero-identity-block">
          <div class="hero-avatar-wrap">
            <img :src="logoUrl" alt="Jupiter" class="hero-brand-logo" />
            <span class="hero-pulse-dot"></span>
          </div>

          <div class="hero-text-block">
            <div class="hero-badge-row">
              <span class="live-pill">
                <span class="live-dot"></span>
                {{ $t('live_operations') || 'Live Operations' }}
              </span>
              <span class="shift-pill">
                <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
                {{ systemInfo.shift || 'Shift A' }}
              </span>
              <span class="time-pill">
                <v-icon size="12" class="mr-1">mdi-timer-outline</v-icon>
                {{ currentTime }}
              </span>
              <span class="sync-pill" :class="{ 'is-online': pwa.is_online }">
                <v-icon size="12" class="mr-1">{{ pwa.is_online ? 'mdi-wifi-check' : 'mdi-wifi-off' }}</v-icon>
                {{ pwa.is_online ? ($t('live_sync_active') || 'Real-time Sync Active') : ($t('pwa_offline') || 'Offline') }}
              </span>
            </div>

            <h1 class="hero-greeting-title">
              {{ greetingTime }},
              <span class="hero-username-highlight">{{ auth.user?.name || 'Operator' }}</span>
            </h1>

            <p class="hero-greeting-subtitle">
              {{ $t('dashboard_subtitle') || 'Factory control center for fish processing, cold storage & dispatch' }}
            </p>
          </div>
        </div>

        <!-- Right: Primary Quick Action Shortcuts -->
        <div class="hero-quick-actions">
          <v-btn
            variant="flat"
            rounded="lg"
            class="hero-action-btn btn-charge"
            @click="$router.push({ name: 'add_charge' })"
          >
            <v-icon size="17" class="mr-1.5">mdi-plus-circle-outline</v-icon>
            {{ $t('add_charge') }}
          </v-btn>

          <v-btn
            variant="flat"
            rounded="lg"
            class="hero-action-btn btn-production"
            @click="$router.push({ name: 'add_production' })"
          >
            <v-icon size="17" class="mr-1.5">mdi-plus-circle-outline</v-icon>
            {{ $t('add_production') }}
          </v-btn>

          <v-btn
            variant="flat"
            rounded="lg"
            class="hero-action-btn btn-shipping"
            @click="$router.push({ name: 'add_shipping' })"
          >
            <v-icon size="17" class="mr-1.5">mdi-plus-circle-outline</v-icon>
            {{ $t('add_shipping') }}
          </v-btn>

          <v-btn
            variant="tonal"
            rounded="lg"
            class="hero-action-btn btn-ai"
            @click="$router.push({ name: 'ai_prompt' })"
          >
            <v-icon size="17" class="mr-1.5">mdi-robot-outline</v-icon>
            {{ $t('ai_prompt') }}
          </v-btn>
        </div>

      </div>
    </div>

    <!-- ============================================================
         2. Real-Time Factory KPIs Strip (Harmonious Module Colors)
         ============================================================ -->
    <div class="kpi-section-header">
      <div class="kpi-title-block">
        <v-icon size="18" color="#1E88E5" class="mr-2">mdi-chart-timeline-variant</v-icon>
        <span class="kpi-section-heading">{{ $t('factory_kpis') || 'Factory Overview' }}</span>
      </div>

      <!-- Period Toggle: Month vs Today -->
      <div class="kpi-period-toggle">
        <button
          type="button"
          class="period-tab"
          :class="{ 'is-active': kpiPeriod === 'month' }"
          @click="kpiPeriod = 'month'"
        >
          {{ $t('this_month') || 'This Month' }}
        </button>
        <button
          type="button"
          class="period-tab"
          :class="{ 'is-active': kpiPeriod === 'today' }"
          @click="kpiPeriod = 'today'"
        >
          {{ $t('today') || 'Today' }}
        </button>
      </div>
    </div>

    <div class="kpi-cards-grid">
      <!-- 1. Raw Reception (Charge) -->
      <div
        class="kpi-glass-card card-charge cursor-pointer"
        @click="$router.push({ name: 'list_charge' })"
      >
        <div class="kpi-icon-pill icon-charge">
          <v-icon size="24" color="#42A5F5">mdi-truck-cargo-container</v-icon>
        </div>
        <div class="kpi-card-data">
          <span class="kpi-label">{{ $t('raw_reception') || 'Raw Reception' }}</span>
          <div class="kpi-metric-row">
            <span class="kpi-main-val">{{ formatWeight(kpiPeriod === 'month' ? kpis.charges.month_weight : kpis.charges.today_weight) }}</span>
            <span class="kpi-unit">T</span>
          </div>
          <div class="kpi-footer-row">
            <span class="kpi-sub-count">
              {{ kpiPeriod === 'month' ? kpis.charges.month_count : kpis.charges.today_count }} {{ $t('charges') }}
            </span>
            <span class="kpi-total-hint">({{ formatNumber(kpis.charges.total_count) }} {{ $t('total_records') || 'total' }})</span>
          </div>
        </div>
        <div class="kpi-card-watermark">
          <v-icon size="64">mdi-truck-cargo-container</v-icon>
        </div>
      </div>

      <!-- 2. Fish Processed (Production) -->
      <div
        class="kpi-glass-card card-production cursor-pointer"
        @click="$router.push({ name: 'production_list' })"
      >
        <div class="kpi-icon-pill icon-production">
          <v-icon size="24" color="#00897B">mdi-factory</v-icon>
        </div>
        <div class="kpi-card-data">
          <span class="kpi-label">{{ $t('fish_processed') || 'Processed Fish' }}</span>
          <div class="kpi-metric-row">
            <span class="kpi-main-val">{{ formatWeight(kpiPeriod === 'month' ? kpis.productions.month_weight : kpis.productions.today_weight) }}</span>
            <span class="kpi-unit">T</span>
          </div>
          <div class="kpi-footer-row">
            <span class="kpi-sub-count">
              {{ kpiPeriod === 'month' ? kpis.productions.month_count : kpis.productions.today_count }} {{ $t('production') }}
            </span>
            <span class="kpi-total-hint">({{ formatNumber(kpis.productions.total_count) }} {{ $t('total_records') || 'total' }})</span>
          </div>
        </div>
        <div class="kpi-card-watermark">
          <v-icon size="64">mdi-factory</v-icon>
        </div>
      </div>

      <!-- 3. Export Dispatches (Shipping) -->
      <div
        class="kpi-glass-card card-shipping cursor-pointer"
        @click="$router.push({ name: 'list_shipping' })"
      >
        <div class="kpi-icon-pill icon-shipping">
          <v-icon size="24" color="#6c66bb">mdi-package-variant-closed</v-icon>
        </div>
        <div class="kpi-card-data">
          <span class="kpi-label">{{ $t('export_dispatch') || 'Export Shipments' }}</span>
          <div class="kpi-metric-row">
            <span class="kpi-main-val">{{ formatWeight(kpiPeriod === 'month' ? kpis.shippings.month_weight : kpis.shippings.today_weight) }}</span>
            <span class="kpi-unit">T</span>
          </div>
          <div class="kpi-footer-row">
            <span class="kpi-sub-count">
              {{ kpiPeriod === 'month' ? kpis.shippings.month_count : kpis.shippings.today_count }} {{ $t('shippings') }}
            </span>
            <span class="kpi-total-hint">({{ formatNumber(kpis.shippings.total_count) }} {{ $t('total_records') || 'total' }})</span>
          </div>
        </div>
        <div class="kpi-card-watermark">
          <v-icon size="64">mdi-package-variant-closed</v-icon>
        </div>
      </div>

      <!-- 4. Cold Storage (Warehouses) -->
      <div
        class="kpi-glass-card card-warehouse cursor-pointer"
        @click="$router.push({ name: 'warehouses' })"
      >
        <div class="kpi-icon-pill icon-warehouse">
          <v-icon size="24" color="#a21ee5">mdi-warehouse</v-icon>
        </div>
        <div class="kpi-card-data">
          <span class="kpi-label">{{ $t('cold_storage') || 'Cold Storage' }}</span>
          <div class="kpi-metric-row">
            <span class="kpi-main-val">{{ formatWeight(kpis.warehouses.stored_weight) }}</span>
            <span class="kpi-unit">T</span>
          </div>
          <div class="kpi-footer-row">
            <span class="kpi-sub-count">
              {{ kpis.warehouses.total_warehouses }} {{ $t('warehouses') }}
            </span>
            <span class="kpi-total-hint">({{ formatShort(kpis.warehouses.stored_packages) }} {{ $t('package_count_label') || 'pkgs' }})</span>
          </div>
        </div>
        <div class="kpi-card-watermark">
          <v-icon size="64">mdi-warehouse</v-icon>
        </div>
      </div>
    </div>

    <!-- ============================================================
         3. Main Dual-Column Hub: Module Launchpad + Live Feed
         ============================================================ -->
    <div class="home-main-grid">

      <!-- Column 1: Filterable Module Launchpad (8 cols) -->
      <div class="launchpad-column">
        <div class="hub-card launchpad-card">
          <!-- Hub Card Header -->
          <div class="hub-header">
            <div class="hub-title-block">
              <v-icon size="20" color="#1976D2" class="mr-2">mdi-view-grid-outline</v-icon>
              <h2 class="hub-title">{{ $t('module_launchpad') || 'Module Launchpad' }}</h2>
            </div>

            <!-- Filter Search Bar -->
            <div class="launchpad-search-wrap">
              <v-icon size="16" class="search-icon">mdi-magnify</v-icon>
              <input
                v-model="filterQuery"
                type="text"
                class="search-input"
                :placeholder="$t('search_shortcuts') || 'Filter modules or quick jump...'"
                spellcheck="false"
              />
              <v-btn
                v-if="filterQuery"
                icon
                size="20"
                variant="text"
                class="search-clear-btn"
                @click="filterQuery = ''"
              >
                <v-icon size="12">mdi-close</v-icon>
              </v-btn>
            </div>
          </div>

          <!-- Shortcuts Grid -->
          <div class="launchpad-grid">
            <div
              v-for="mod in filteredModules"
              :key="mod.id"
              class="module-shortcut-card cursor-pointer"
              :style="{ '--mod-color': mod.color }"
              @click="$router.push({ name: mod.route })"
            >
              <div class="mod-icon-wrapper" :style="{ backgroundColor: hexToRgba(mod.color, 0.12) }">
                <v-icon size="24" :color="mod.color">{{ mod.icon }}</v-icon>
              </div>

              <div class="mod-info-block">
                <div class="mod-title-row">
                  <span class="mod-title">{{ mod.title }}</span>
                  <v-icon size="15" class="mod-hover-arrow">mdi-arrow-right</v-icon>
                </div>
                <span class="mod-desc">{{ mod.description }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Column 2: Live Activity Feed & Interactive AI Query (4 cols) -->
      <div class="feed-column">

        <!-- Live Activity Stream -->
        <div class="hub-card feed-card mb-4">
          <div class="hub-header">
            <div class="hub-title-block">
              <span class="feed-live-dot"></span>
              <h2 class="hub-title">{{ $t('recent_activity') || 'Recent Activity' }}</h2>
            </div>
            
            <button
              type="button"
              class="refresh-icon-btn"
              :class="{ 'is-spinning': loading }"
              :title="$t('refresh') || 'Refresh'"
              @click="fetchOverview(true)"
            >
              <v-icon size="16">mdi-refresh</v-icon>
            </button>
          </div>

          <!-- Feed Category Filter Tabs -->
          <div class="feed-tabs-row">
            <button
              v-for="tab in feedTabs"
              :key="tab.id"
              type="button"
              class="feed-tab-btn"
              :class="{ 'is-active': activeFeedTab === tab.id }"
              @click="activeFeedTab = tab.id"
            >
              {{ tab.label }}
            </button>
          </div>

          <!-- Feed Items List -->
          <div class="feed-list-wrap">
            <template v-if="filteredActivities.length > 0">
              <div
                v-for="act in filteredActivities"
                :key="act.key"
                class="feed-item-row cursor-pointer"
                @click="onActivityClick(act)"
              >
                <div class="feed-item-icon" :style="{ backgroundColor: hexToRgba(act.color, 0.12) }">
                  <v-icon size="16" :color="act.color">{{ act.icon }}</v-icon>
                </div>
                <div class="feed-item-body">
                  <div class="feed-item-top">
                    <span class="feed-item-title">{{ act.title }}</span>
                    <span class="feed-item-time">{{ formatTimeAgo(act.date) }}</span>
                  </div>
                  <div class="feed-item-bottom">
                    <span class="feed-item-entity">{{ act.subtitle }}</span>
                    <span class="feed-item-weight">{{ formatNumber(act.weight) }} kg</span>
                  </div>
                </div>
              </div>
            </template>
            <div v-else class="feed-empty-state">
              <v-icon size="28" color="grey">mdi-inbox-outline</v-icon>
              <span>{{ $t('no_recent_records') || 'No recent activity found' }}</span>
            </div>
          </div>
        </div>

        <!-- Jupiter AI Interactive Prompt Box -->
        <div class="hub-card ai-smart-card">
          <div class="ai-ambient-layer"></div>
          <div class="ai-smart-content">
            <div class="ai-smart-header">
              <div class="ai-avatar-badge">
                <v-icon size="20" color="white">mdi-robot-excited-outline</v-icon>
              </div>
              <div>
                <span class="ai-smart-title">Jupiter AI</span>
                <span class="ai-smart-subtitle">{{ $t('ask_ai') || 'Ask AI' }}</span>
              </div>
            </div>

            <!-- Direct Prompt Form -->
            <div class="ai-input-box">
              <input
                v-model="aiQueryInput"
                type="text"
                class="ai-input-field"
                :placeholder="$t('ask_jupiter_ai_placeholder') || 'Ask anything about charges, production, stock...'"
                @keyup.enter="submitAiQuery"
              />
              <v-btn
                icon
                size="30"
                variant="flat"
                color="#1976D2"
                class="ai-submit-btn"
                :disabled="!aiQueryInput.trim()"
                @click="submitAiQuery"
              >
                <v-icon size="16" color="white">mdi-arrow-right</v-icon>
              </v-btn>
            </div>

            <!-- Quick Suggestions Chips -->
            <div class="ai-chips-wrap">
              <button
                type="button"
                class="ai-suggestion-chip"
                @click="triggerSuggestion($t('ai_suggestion_1') || 'Today\'s production')"
              >
                <v-icon size="12" class="mr-1">mdi-sparkles</v-icon>
                {{ $t('ai_suggestion_1') || 'Today\'s production' }}
              </button>
              <button
                type="button"
                class="ai-suggestion-chip"
                @click="triggerSuggestion($t('ai_suggestion_2') || 'Warehouse capacity')"
              >
                <v-icon size="12" class="mr-1">mdi-warehouse</v-icon>
                {{ $t('ai_suggestion_2') || 'Warehouse capacity' }}
              </button>
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>

<script>
import { useMain } from "@/stores/Main";
import { useAuth } from "@/stores/Auth";
import { useCompanySettings } from "@/stores/CompanySettings";
import { usePwa } from "@/stores/Pwa";
import axios from "axios";

export default {
  name: "HomeDashboard",
  data() {
    return {
      loading: false,
      currentTime: "",
      clockTimer: null,
      kpiPeriod: "month", // 'month' or 'today'
      filterQuery: "",
      activeFeedTab: "all",
      aiQueryInput: "",

      kpis: {
        charges: { today_count: 0, today_weight: 0, today_price: 0, month_count: 0, month_weight: 0, total_count: 0 },
        productions: { today_count: 0, today_weight: 0, frozen_weight: 0, month_count: 0, month_weight: 0, total_count: 0 },
        shippings: { today_count: 0, today_weight: 0, today_price: 0, month_count: 0, month_weight: 0, total_count: 0 },
        warehouses: { total_warehouses: 0, stored_weight: 0, stored_packages: 0 },
        transfers: { today_count: 0, month_count: 0, month_weight: 0 },
      },

      recentActivity: {
        charges: [],
        productions: [],
        shippings: [],
        transfers: [],
      },

      systemInfo: {
        shift: "Morning Shift (A)",
        user_count: 1,
      },
    };
  },

  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    pwa() { return usePwa(); },

    logoUrl() {
      const cs = useCompanySettings();
      if (cs.settings?.logo_invoice_body_url) return cs.settings.logo_invoice_body_url;
      return new URL('/images/jupiter-logo.png', window.location.origin).href;
    },

    greetingTime() {
      const h = new Date().getHours();
      if (h < 12) return this.$t('good_morning') || 'Good morning';
      if (h < 18) return this.$t('good_afternoon') || 'Good afternoon';
      return this.$t('good_evening') || 'Good evening';
    },

    modulesList() {
      const t = (k, fb) => this.$t(k) || fb;
      return [
        {
          id: 'charges',
          title: t('charges', 'Raw Reception'),
          description: t('charges_desc', 'Incoming fish trucks & reception batches'),
          icon: 'mdi-truck-cargo-container',
          color: '#42A5F5',
          route: 'list_charge',
          keywords: 'charges reception raw fish truck',
        },
        {
          id: 'production',
          title: t('production', 'Factory Production'),
          description: t('production_desc', 'Standard, sub-contracting & processing lines'),
          icon: 'mdi-factory',
          color: '#00897B',
          route: 'production_list',
          keywords: 'production factory processing batch quality',
        },
        {
          id: 'shippings',
          title: t('shippings', 'Export Shipments'),
          description: t('shippings_desc', 'Container dispatch, packing list & transport'),
          icon: 'mdi-package-variant-closed',
          color: '#4643a0',
          route: 'list_shipping',
          keywords: 'shipping export client delivery transport',
        },
        {
          id: 'warehouses',
          title: t('warehouses', 'Cold Storage'),
          description: t('warehouses_desc', 'Chamber occupancy, tariffs & storage invoices'),
          icon: 'mdi-warehouse',
          color: '#a21ee5',
          route: 'warehouses',
          keywords: 'warehouses storage chamber cold stock',
        },
        {
          id: 'inventory',
          title: t('inventory', 'Inventory Management'),
          description: t('inventory_desc', 'Physical audits, barcode & stock variance'),
          icon: 'mdi-clipboard-text-clock-outline',
          color: '#6D4C41',
          route: 'list_inventory',
          keywords: 'inventory audit count stock physical',
        },
        {
          id: 'transfers',
          title: t('warehouse_transfers', 'Chamber Transfers'),
          description: t('warehouse_transfers_desc', 'Inter-chamber fish relocations & history'),
          icon: 'mdi-swap-horizontal-bold',
          color: '#f59e0b',
          route: 'warehouse_transfers',
          keywords: 'transfer relocate chamber move warehouse',
        },
        {
          id: 'workforce',
          title: t('workforce', 'Workforce & Fortnight'),
          description: t('workforce_desc', 'Worker declarations, shifts & bi-weekly pay'),
          icon: 'mdi-account-hard-hat',
          color: '#1565C0',
          route: 'fortnight_management',
          keywords: 'workforce worker fortnight shift corporals salary',
        },
        {
          id: 'main_dashboard',
          title: t('main_dashboard', 'Main Dashboard'),
          description: t('custom_charts_subtitle', 'Executive operational charts & custom widgets'),
          icon: 'mdi-chart-box-multiple-outline',
          color: '#00838F',
          route: 'main_dashboard',
          keywords: 'dashboard executive custom charts widgets analytics',
        },
        {
          id: 'jupiter_dashboard',
          title: t('jupiter_dashboard', 'Jupiter Analytics'),
          description: t('jupiter_subtitle', 'Cost graphs, production yield & trends'),
          icon: 'mdi-orbit-variant',
          color: '#1E88E5',
          route: 'jupiter_dashboard',
          keywords: 'analytics charts graphs trends kpi stats',
        },
        {
          id: 'supplier_invoices',
          title: t('supplier_invoice', 'Supplier Invoices'),
          description: t('supplier_invoices_desc', 'Raw material bills & settlement reconciliations'),
          icon: 'mdi-file-document-outline',
          color: '#E65100',
          route: 'supplier_invoice',
          keywords: 'supplier invoice billing payment settlement',
        },
        {
          id: 'client_invoices',
          title: t('client_invoice', 'Client Invoices'),
          description: t('client_invoices_desc', 'Commercial export invoices & accounts receivable'),
          icon: 'mdi-receipt-outline',
          color: '#00838F',
          route: 'client_invoice',
          keywords: 'client invoice customer sales receivable',
        },
      ];
    },

    filteredModules() {
      if (!this.filterQuery.trim()) return this.modulesList;
      const q = this.filterQuery.toLowerCase();
      return this.modulesList.filter(
        m => m.title.toLowerCase().includes(q) ||
             m.description.toLowerCase().includes(q) ||
             m.keywords.includes(q)
      );
    },

    feedTabs() {
      const t = (k, fb) => this.$t(k) || fb;
      return [
        { id: 'all', label: t('all_activities', 'All') },
        { id: 'charges', label: t('receptions_tab', 'Receptions') },
        { id: 'productions', label: t('productions_tab', 'Productions') },
        { id: 'shippings', label: t('shipments_tab', 'Shipments') },
        { id: 'transfers', label: t('transfers_tab', 'Transfers') },
      ];
    },

    allActivityItems() {
      const items = [];

      // Charges
      (this.recentActivity.charges || []).forEach(c => {
        items.push({
          key: 'charge-' + c.id,
          type: 'charges',
          icon: 'mdi-truck-cargo-container',
          color: '#42A5F5',
          title: `${this.$t('charges') || 'Reception'} #${c.id}`,
          subtitle: c.supplier?.name || 'Raw Reception',
          weight: parseFloat(c.total_weight) || 0,
          date: c.created_at || c.date,
          route: 'list_charge',
        });
      });

      // Productions
      (this.recentActivity.productions || []).forEach(p => {
        items.push({
          key: 'prod-' + p.id,
          type: 'productions',
          icon: 'mdi-factory',
          color: '#00897B',
          title: `${this.$t('production') || 'Production'} #${p.id}`,
          subtitle: p.production_type ? (p.production_type.replace('_', ' ')) : 'Processing',
          weight: parseFloat(p.total_weight) || 0,
          date: p.created_at || p.date,
          route: 'production_list',
        });
      });

      // Shippings
      (this.recentActivity.shippings || []).forEach(s => {
        items.push({
          key: 'ship-' + s.id,
          type: 'shippings',
          icon: 'mdi-package-variant-closed',
          color: '#6c66bb',
          title: `${this.$t('shippings') || 'Shipment'} #${s.id}`,
          subtitle: s.client?.name || 'Export Dispatch',
          weight: parseFloat(s.total_weight) || 0,
          date: s.created_at || s.date,
          route: 'list_shipping',
        });
      });

      // Transfers
      (this.recentActivity.transfers || []).forEach(tr => {
        items.push({
          key: 'trans-' + tr.id,
          type: 'transfers',
          icon: 'mdi-swap-horizontal-bold',
          color: '#f59e0b',
          title: `${this.$t('transfers') || 'Transfer'} #${tr.id}`,
          subtitle: Array.isArray(tr.transfer_tracking_number) ? tr.transfer_tracking_number.join(', ') : 'Chamber Relocation',
          weight: parseFloat(tr.total_weight) || 0,
          date: tr.created_at || tr.date,
          route: 'warehouse_transfers',
        });
      });

      // Sort by date descending
      return items.sort((a, b) => new Date(b.date) - new Date(a.date));
    },

    filteredActivities() {
      if (this.activeFeedTab === 'all') return this.allActivityItems;
      return this.allActivityItems.filter(a => a.type === this.activeFeedTab);
    },
  },

  mounted() {
    this.updateClock();
    this.clockTimer = setInterval(this.updateClock, 1000);

    // Rule 3: Load from cache first for instant UI response
    this.loadFromCache();
    // Then fetch fresh data
    this.fetchOverview();

    // Rule 3: WebSocket listeners for real-time synchronization
    this.initWebSocketListeners();
  },

  beforeUnmount() {
    if (this.clockTimer) clearInterval(this.clockTimer);
    this.destroyWebSocketListeners();
  },

  methods: {
    updateClock() {
      const d = new Date();
      this.currentTime = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    },

    hexToRgba(hex, alpha = 1) {
      if (!hex || !hex.startsWith('#')) return `rgba(33, 150, 243, ${alpha})`;
      let c = hex.substring(1);
      if (c.length === 3) c = c.split('').map(x => x + x).join('');
      const num = parseInt(c, 16);
      const r = (num >> 16) & 255;
      const g = (num >> 8) & 255;
      const b = num & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },

    formatWeight(kg) {
      if (!kg) return "0.0";
      const tons = kg / 1000;
      if (tons >= 1000) {
        return (tons / 1000).toFixed(1) + "K";
      }
      return tons.toFixed(1);
    },

    formatNumber(num) {
      if (!num) return "0";
      return new Intl.NumberFormat().format(Math.round(num));
    },

    formatShort(num) {
      if (!num) return "0";
      if (num >= 1000000) return (num / 1000000).toFixed(1) + "M";
      if (num >= 1000) return (num / 1000).toFixed(1) + "K";
      return num.toString();
    },

    formatTimeAgo(dateStr) {
      if (!dateStr) return "";
      const d = new Date(dateStr);
      const now = new Date();
      const diffMs = now - d;
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMins / 60);
      const diffDays = Math.floor(diffHours / 24);

      if (diffMins < 1) return this.$t('just_now') || 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffHours < 24) return `${diffHours}h ago`;
      if (diffDays === 1) return this.$t('yesterday') || 'Yesterday';
      return `${diffDays}d ago`;
    },

    loadFromCache() {
      try {
        const cached = sessionStorage.getItem("jupiter_home_overview");
        if (cached) {
          const parsed = JSON.parse(cached);
          if (parsed.kpis) this.kpis = parsed.kpis;
          if (parsed.recent_activity) this.recentActivity = parsed.recent_activity;
          if (parsed.system) this.systemInfo = parsed.system;
        }
      } catch (e) {
        // Cache miss/parse fail
      }
    },

    saveToCache(data) {
      try {
        sessionStorage.setItem("jupiter_home_overview", JSON.stringify(data));
      } catch (e) {}
    },

    async fetchOverview(isManual = false) {
      if (isManual) this.loading = true;
      try {
        const { data } = await axios.get("/api/home/overview");
        if (data && data.success) {
          this.kpis = data.kpis;
          this.recentActivity = data.recent_activity;
          if (data.system) this.systemInfo = data.system;
          this.saveToCache(data);
        }
      } catch (err) {
        console.warn("[HomeDashboard] fetchOverview failed:", err);
      } finally {
        if (isManual) this.loading = false;
      }
    },

    initWebSocketListeners() {
      if (typeof window.Echo === "undefined") return;

      const channels = ["charges", "productions", "shippings", "warehouses", "inventory"];
      channels.forEach(ch => {
        try {
          window.Echo.channel(ch)
            .listen(".created", () => this.fetchOverview(false))
            .listen(".updated", () => this.fetchOverview(false))
            .listen(".deleted", () => this.fetchOverview(false));
        } catch (e) {}
      });
    },

    destroyWebSocketListeners() {
      if (typeof window.Echo === "undefined") return;
      const channels = ["charges", "productions", "shippings", "warehouses", "inventory"];
      channels.forEach(ch => {
        try {
          window.Echo.leave(ch);
        } catch (e) {}
      });
    },

    onActivityClick(act) {
      if (act.route) {
        this.$router.push({ name: act.route });
      }
    },

    submitAiQuery() {
      if (!this.aiQueryInput.trim()) return;
      const q = this.aiQueryInput.trim();
      this.$router.push({
        name: 'ai_prompt',
        query: { q }
      });
    },

    triggerSuggestion(text) {
      this.aiQueryInput = text;
      this.submitAiQuery();
    },
  },
};
</script>

<style scoped>
/* ============================================================
   Home Portal - Eye-Relief Luxury Modern Dashboard
   ============================================================ */
.home-portal {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  max-width: 1440px;
  margin: 0 auto;
  padding: 0 4px 40px;
  transition: background 0.3s ease;
}

/* Light & Dark Foundations */
.home-light {
  color: #0F172A;
}

.home-dark {
  color: #F8FAFC;
}

/* ============================================================
   1. Atmospheric Hero Greeting Card
   ============================================================ */
.home-hero-card {
  position: relative;
  border-radius: 24px;
  padding: 28px 32px;
  margin-bottom: 24px;
  overflow: hidden;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.06);
  border: 1px solid;
  transition: all 0.3s ease;
}

.home-light .home-hero-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 246, 255, 0.85) 100%);
  border-color: rgba(33, 150, 243, 0.12);
}

.home-dark .home-hero-card {
  background: linear-gradient(135deg, rgba(26, 32, 46, 0.95) 0%, rgba(15, 23, 42, 0.92) 100%);
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 16px 40px -15px rgba(0, 0, 0, 0.45);
}

.hero-ambient-glow {
  position: absolute;
  top: -40px;
  right: 10%;
  width: 320px;
  height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(33, 150, 243, 0.15) 0%, rgba(0, 137, 123, 0.08) 60%, transparent 80%);
  pointer-events: none;
  filter: blur(40px);
}

.hero-content-wrap {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}

.hero-identity-block {
  display: flex;
  align-items: center;
  gap: 20px;
  flex: 1;
  min-width: 320px;
}

.hero-avatar-wrap {
  position: relative;
  width: 68px;
  height: 68px;
  border-radius: 18px;
  background: transparent !important;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.hero-brand-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.1));
}

.hero-pulse-dot {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 13px;
  height: 13px;
  border-radius: 50%;
  background: #10B981;
  border: 2.5px solid rgb(var(--v-theme-surface));
  box-shadow: 0 0 8px #10B981;
}

.hero-text-block {
  flex: 1;
}

.hero-badge-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.live-pill, .shift-pill, .time-pill, .sync-pill {
  display: inline-flex;
  align-items: center;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
  letter-spacing: 0.2px;
}

.live-pill {
  background: rgba(16, 185, 129, 0.12);
  color: #10B981;
}
.live-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10B981;
  margin-right: 6px;
  box-shadow: 0 0 6px #10B981;
  animation: pulseBeacon 2s infinite;
}
@keyframes pulseBeacon {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.3); }
}

.shift-pill, .time-pill {
  background: rgba(var(--v-theme-on-surface), 0.05);
  color: rgba(var(--v-theme-on-surface), 0.75);
}

.sync-pill {
  background: rgba(33, 150, 243, 0.1);
  color: #1976D2;
}
.home-dark .sync-pill {
  color: #42A5F5;
}

.hero-greeting-title {
  font-size: 1.55rem;
  font-weight: 800;
  letter-spacing: -0.3px;
  line-height: 1.25;
  margin: 0 0 4px;
}

.hero-username-highlight {
  background: linear-gradient(135deg, #1976D2 0%, #42A5F5 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-greeting-subtitle {
  font-size: 0.88rem;
  opacity: 0.7;
  margin: 0;
  max-width: 580px;
}

/* Quick Action Buttons */
.hero-quick-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.hero-action-btn {
  font-size: 0.82rem !important;
  font-weight: 600 !important;
  text-transform: none !important;
  letter-spacing: 0.2px !important;
  padding: 0 16px !important;
  height: 38px !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.hero-action-btn:hover {
  transform: translateY(-2px);
}

.btn-charge {
  background: #1976D2 !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.25) !important;
}
.btn-production {
  background: #00897B !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(0, 137, 123, 0.25) !important;
}
.btn-shipping {
  background: #4643a0 !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(70, 67, 160, 0.25) !important;
}
.btn-ai {
  color: #1976D2 !important;
  border: 1px solid rgba(25, 118, 210, 0.3) !important;
}
.home-dark .btn-ai {
  color: #64B5F6 !important;
  border-color: rgba(100, 181, 246, 0.3) !important;
}

/* ============================================================
         2. Real-Time Factory KPIs Strip
   ============================================================ */
.kpi-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  padding: 0 4px;
}

.kpi-title-block {
  display: flex;
  align-items: center;
}

.kpi-section-heading {
  font-size: 0.92rem;
  font-weight: 700;
  letter-spacing: 0.2px;
  color: rgba(var(--v-theme-on-surface), 0.85);
  text-transform: uppercase;
}

.kpi-period-toggle {
  display: flex;
  background: rgba(var(--v-theme-on-surface), 0.05);
  border-radius: 10px;
  padding: 3px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.period-tab {
  font-size: 0.76rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 7px;
  border: none;
  background: transparent;
  color: rgba(var(--v-theme-on-surface), 0.6);
  cursor: pointer;
  transition: all 0.2s ease;
}

.period-tab.is-active {
  background: rgb(var(--v-theme-surface));
  color: #1976D2;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}
.home-dark .period-tab.is-active {
  color: #64B5F6;
}

.kpi-cards-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.kpi-glass-card {
  position: relative;
  border-radius: 20px;
  padding: 20px;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1px solid;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 14px -2px rgba(0, 0, 0, 0.04);
}

.home-light .kpi-glass-card {
  background: #ffffff;
  border-color: rgba(0, 0, 0, 0.06);
}

.home-dark .kpi-glass-card {
  background: rgba(26, 32, 46, 0.8);
  border-color: rgba(255, 255, 255, 0.07);
  box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.35);
}

.kpi-glass-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 24px -4px rgba(0, 0, 0, 0.1);
}
.home-dark .kpi-glass-card:hover {
  box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.5);
}

.card-charge:hover { border-color: rgba(66, 165, 245, 0.45); }
.card-production:hover { border-color: rgba(0, 137, 123, 0.45); }
.card-shipping:hover { border-color: rgba(70, 67, 160, 0.45); }
.card-warehouse:hover { border-color: rgba(162, 30, 229, 0.45); }

.kpi-icon-pill {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-charge { background: rgba(33, 150, 243, 0.12); }
.icon-production { background: rgba(0, 137, 123, 0.12); }
.icon-shipping { background: rgba(70, 67, 160, 0.12); }
.icon-warehouse { background: rgba(162, 30, 229, 0.12); }

.kpi-card-data {
  flex: 1;
  min-width: 0;
  position: relative;
  z-index: 1;
}

.kpi-label {
  font-size: 0.74rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: rgba(var(--v-theme-on-surface), 0.6);
  display: block;
}

.kpi-metric-row {
  display: flex;
  align-items: baseline;
  gap: 4px;
  margin: 2px 0;
}

.kpi-main-val {
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.5px;
  line-height: 1.1;
  color: rgba(var(--v-theme-on-surface), 0.95);
}

.kpi-unit {
  font-size: 0.88rem;
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.kpi-footer-row {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.74rem;
}

.kpi-sub-count {
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.8);
}

.kpi-total-hint {
  opacity: 0.55;
  font-size: 0.7rem;
}

.kpi-card-watermark {
  position: absolute;
  right: -10px;
  bottom: -12px;
  opacity: 0.05;
  pointer-events: none;
  transform: rotate(-10deg);
}
.home-dark .kpi-card-watermark {
  opacity: 0.03;
}

/* ============================================================
   3. Main Dual Column Hub
   ============================================================ */
.home-main-grid {
  display: grid;
  grid-template-columns: 8fr 4fr;
  gap: 20px;
}

.hub-card {
  border-radius: 22px;
  padding: 24px;
  border: 1px solid;
  transition: all 0.3s ease;
}

.home-light .hub-card {
  background: #ffffff;
  border-color: rgba(0, 0, 0, 0.06);
  box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.04);
}

.home-dark .hub-card {
  background: rgba(26, 32, 46, 0.8);
  border-color: rgba(255, 255, 255, 0.07);
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.35);
}

.hub-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.hub-title-block {
  display: flex;
  align-items: center;
}

.hub-title {
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: -0.2px;
  margin: 0;
  color: rgba(var(--v-theme-on-surface), 0.9);
}

/* Launchpad Search Bar (addcharge rounded style) */
.launchpad-search-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(var(--v-theme-surface), 0.85);
  border: 1.5px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 9999px;
  padding: 6px 16px;
  min-width: 250px;
  backdrop-filter: blur(8px);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.launchpad-search-wrap:focus-within {
  background: rgb(var(--v-theme-surface));
  border-color: #42A5F5;
  box-shadow: 0 0 0 3px rgba(66, 165, 245, 0.18);
}

.search-icon {
  opacity: 0.6;
  color: #42A5F5;
  transition: opacity 0.2s ease;
}
.launchpad-search-wrap:focus-within .search-icon {
  opacity: 1;
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 0.82rem;
  font-family: inherit;
  color: inherit;
}
.search-input::placeholder {
  opacity: 0.5;
}

.search-clear-btn {
  opacity: 0.6;
}

/* Shortcuts Grid */
.launchpad-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
}

.module-shortcut-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border-radius: 16px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  background: rgba(var(--v-theme-on-surface), 0.02);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.module-shortcut-card:hover {
  background: rgb(var(--v-theme-surface));
  border-color: var(--mod-color, #1976D2);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.08);
}
.home-dark .module-shortcut-card:hover {
  box-shadow: 0 10px 24px -4px rgba(0, 0, 0, 0.35);
}

.mod-icon-wrapper {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.module-shortcut-card:hover .mod-icon-wrapper {
  transform: scale(1.08);
}

.mod-info-block {
  flex: 1;
  min-width: 0;
}

.mod-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.mod-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.92);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mod-hover-arrow {
  opacity: 0;
  transform: translateX(-4px);
  transition: all 0.2s ease;
  color: var(--mod-color, #1976D2);
}

.module-shortcut-card:hover .mod-hover-arrow {
  opacity: 1;
  transform: translateX(0);
}

.mod-desc {
  display: block;
  font-size: 0.74rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin-top: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ============================================================
   Feed Column Styles
   ============================================================ */
.feed-live-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10B981;
  margin-right: 10px;
  box-shadow: 0 0 8px #10B981;
}

.refresh-icon-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 8px;
  opacity: 0.6;
  color: inherit;
  transition: all 0.2s ease;
}
.refresh-icon-btn:hover {
  opacity: 1;
  background: rgba(var(--v-theme-on-surface), 0.08);
}
.refresh-icon-btn.is-spinning {
  animation: spinRefresh 1s linear infinite;
}
@keyframes spinRefresh {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Feed Tabs */
.feed-tabs-row {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  padding-bottom: 10px;
  margin-bottom: 8px;
  scrollbar-width: none;
}
.feed-tabs-row::-webkit-scrollbar {
  display: none;
}

.feed-tab-btn {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3.5px 10px;
  border-radius: 8px;
  border: 1px solid transparent;
  background: rgba(var(--v-theme-on-surface), 0.04);
  color: rgba(var(--v-theme-on-surface), 0.65);
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.feed-tab-btn.is-active {
  background: rgba(33, 150, 243, 0.12);
  color: #1976D2;
  border-color: rgba(33, 150, 243, 0.25);
}
.home-dark .feed-tab-btn.is-active {
  color: #64B5F6;
}

/* Feed Items */
.feed-list-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 380px;
  overflow-y: auto;
  padding-right: 2px;
}

.feed-item-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(var(--v-theme-on-surface), 0.02);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.05);
  transition: all 0.2s ease;
}

.feed-item-row:hover {
  background: rgb(var(--v-theme-surface));
  border-color: rgba(33, 150, 243, 0.3);
  transform: translateX(2px);
}

.feed-item-icon {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.feed-item-body {
  flex: 1;
  min-width: 0;
}

.feed-item-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.feed-item-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.9);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.feed-item-time {
  font-size: 0.68rem;
  color: rgba(var(--v-theme-on-surface), 0.45);
  flex-shrink: 0;
}

.feed-item-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 2px;
}

.feed-item-entity {
  font-size: 0.72rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.feed-item-weight {
  font-size: 0.72rem;
  font-weight: 700;
  color: #10B981;
  flex-shrink: 0;
}

.feed-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  gap: 8px;
  font-size: 0.8rem;
  opacity: 0.5;
}

/* ============================================================
   AI Smart Card
   ============================================================ */
.ai-smart-card {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%) !important;
  border: none !important;
  color: #ffffff;
  box-shadow: 0 10px 25px -4px rgba(21, 101, 192, 0.35) !important;
}

.ai-ambient-layer {
  position: absolute;
  top: -50%;
  right: -30%;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
  pointer-events: none;
}

.ai-smart-content {
  position: relative;
  z-index: 1;
}

.ai-smart-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.ai-avatar-badge {
  width: 38px;
  height: 38px;
  border-radius: 11px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.ai-smart-title {
  display: block;
  font-size: 0.92rem;
  font-weight: 800;
  color: #ffffff;
}

.ai-smart-subtitle {
  display: block;
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.75);
}

.ai-input-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.96);
  border: 1.5px solid rgba(255, 255, 255, 0.4);
  border-radius: 9999px;
  padding: 4px 6px 4px 16px;
  margin-bottom: 10px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.ai-input-box:focus-within {
  background: #ffffff;
  border-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35), 0 6px 20px rgba(0, 0, 0, 0.12);
}

.ai-input-field {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 0.82rem;
  color: #0F172A;
  font-family: inherit;
}
.ai-input-field::placeholder {
  color: #64748B;
  font-size: 0.8rem;
}

.ai-submit-btn {
  flex-shrink: 0;
}

.ai-chips-wrap {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.ai-suggestion-chip {
  display: inline-flex;
  align-items: center;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 3.5px 9px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(6px);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.2);
  cursor: pointer;
  transition: all 0.2s ease;
}
.ai-suggestion-chip:hover {
  background: rgba(255, 255, 255, 0.26);
  transform: translateY(-1px);
}

/* ============================================================
   Responsive Layouts
   ============================================================ */
@media (max-width: 1100px) {
  .kpi-cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .home-main-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .hero-content-wrap {
    flex-direction: column;
    align-items: flex-start;
  }
  .hero-quick-actions {
    width: 100%;
  }
  .hero-action-btn {
    flex: 1;
  }
  .kpi-cards-grid {
    grid-template-columns: 1fr;
  }
  .launchpad-grid {
    grid-template-columns: 1fr;
  }
  .launchpad-search-wrap {
    width: 100%;
  }
  .hub-header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
