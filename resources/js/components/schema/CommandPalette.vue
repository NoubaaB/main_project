<template>
  <v-dialog
    v-model="isOpen"
    max-width="640"
    :theme="main.is_dark ? 'dark' : 'light'"
    transition="dialog-top-transition"
    class="command-palette-dialog"
    @after-leave="onClose"
  >
    <v-card class="command-palette-card" :class="main.is_dark ? 'card-dark' : 'card-light'" rounded="xl" elevation="24">
      <!-- Search input header -->
      <div class="command-input-wrapper">
        <v-icon size="22" class="command-search-icon" color="primary">mdi-magnify</v-icon>
        <input
          ref="searchInput"
          v-model="query"
          type="text"
          class="command-input"
          :placeholder="t('search_placeholder')"
          autocomplete="off"
          spellcheck="false"
          @keydown.down.prevent="navigateResults(1)"
          @keydown.up.prevent="navigateResults(-1)"
          @keydown.enter.prevent="selectCurrent"
          @keydown.esc.prevent="close"
        />
        <div class="d-flex align-center gap-1">
          <v-chip v-if="query" size="x-small" variant="text" class="cursor-pointer" @click="query = ''">
            <v-icon size="14">mdi-close</v-icon>
          </v-chip>
          <span class="command-esc-badge">ESC</span>
        </div>
      </div>

      <v-divider></v-divider>

      <!-- Results list -->
      <div class="command-results-list" ref="resultsContainer">
        <template v-if="groupedResults.length > 0">
          <div v-for="group in groupedResults" :key="group.category" class="command-group">
            <div class="command-group-title">
              {{ group.category }}
            </div>
            <div
              v-for="item in group.items"
              :key="item.routeName"
              :id="'cmd-item-' + item.index"
              class="command-item"
              :class="{ 'is-selected': selectedIndex === item.index }"
              @mouseenter="selectedIndex = item.index"
              @click="selectItem(item)"
            >
              <div class="command-item-icon" :style="{ backgroundColor: hexToRgba(item.color, 0.12), color: item.color }">
                <v-icon size="18">{{ item.icon }}</v-icon>
              </div>
              <div class="command-item-info">
                <div class="command-item-title">{{ item.title }}</div>
                <div class="command-item-subtitle">{{ item.subtitle || group.category }}</div>
              </div>
              <div class="command-item-action">
                <v-chip size="x-small" :style="{ borderColor: hexToRgba(item.color, 0.3), color: item.color }" variant="outlined">
                  {{ item.badge || t('navigation') }}
                </v-chip>
                <v-icon size="16" class="command-enter-icon ml-2">mdi-keyboard-return</v-icon>
              </div>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="command-empty-state">
          <v-icon size="42" color="medium-emphasis" class="mb-2">mdi-file-search-outline</v-icon>
          <div class="text-subtitle-2 font-weight-medium">{{ t('no_pages_found') }}</div>
          <div class="text-caption text-medium-emphasis">"{{ query }}"</div>
        </div>
      </div>

      <v-divider></v-divider>

      <!-- Footer tips -->
      <div class="command-footer">
        <div class="command-footer-tip">
          <kbd>↑</kbd> <kbd>↓</kbd> <span>{{ t('navigate_to') }}</span>
        </div>
        <div class="command-footer-tip">
          <kbd>↵</kbd> <span>{{ t('open') }}</span>
        </div>
        <div class="command-footer-tip">
          <kbd>ESC</kbd> <span>{{ t('close') }}</span>
        </div>
      </div>
    </v-card>
  </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'CommandPalette',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue'],
  data() {
    return {
      query: '',
      selectedIndex: 0,
    };
  },
  computed: {
    main() {
      return useMain();
    },
    auth() {
      return useAuth();
    },
    lang() {
      return this.main.lang?.code || 'en';
    },
    isOpen: {
      get() {
        return this.modelValue;
      },
      set(val) {
        this.$emit('update:modelValue', val);
      }
    },
    catalog() {
      const auth = this.auth;
      const t = this.t;

      const items = [
        // Analytics & Core
        { routeName: 'main_dashboard', title: t('main_dashboard'), category: t('core_analytics'), icon: 'mdi-chart-box-outline', color: '#1565C0', keywords: ['dashboard', 'panel', 'home'] },
        { routeName: 'jupiter_dashboard', title: t('jupiter_dashboard'), category: t('core_analytics'), icon: 'mdi-orbit-variant', color: '#1E88E5', keywords: ['jupiter', 'overview'] },
        { routeName: 'user_statistics', title: t('user_statistics'), category: t('core_analytics'), icon: 'mdi-chart-timeline-variant-shimmer', color: '#3949AB', perm: 'user_statistics.view', keywords: ['stats', 'estadisticas'] },
        { routeName: 'ai_prompt', title: t('ai_prompt'), category: t('core_analytics'), icon: 'mdi-robot', color: '#42A5F5', perm: 'ai_prompt.view', keywords: ['ai', 'ia', 'prompt', 'bot', 'assistant'] },

        // Operations & Logistics
        { routeName: 'warehouses', title: t('warehouses') + ' - ' + t('dashboard'), category: t('operations_logistics'), icon: 'mdi-warehouse', color: '#a21ee5', perm: 'warehouses.view', keywords: ['stock', 'almacen', 'inventaire', 'fish', 'boxes', 'chambre', 'tarifa'] },
        { routeName: 'storage_invoices', title: t('storage_invoices'), category: t('operations_logistics'), icon: 'mdi-receipt-text-outline', color: '#a21ee5', perm: 'warehouses.view', keywords: ['storage', 'invoice', 'factura', 'almacen'] },
        { routeName: 'list_inventory', title: t('list_inventory'), category: t('operations_logistics'), icon: 'mdi-clipboard-text-clock-outline', color: '#6D4C41', perm: 'inventory.view', keywords: ['inventory', 'inventario', 'stock'] },
        { routeName: 'add_inventory', title: t('add_inventory'), category: t('operations_logistics'), icon: 'mdi-sticker-plus-outline', color: '#6D4C41', perm: 'inventory.create', keywords: ['create', 'inventory', 'nuevo'] },
        { routeName: 'list_charge', title: t('list_charge'), category: t('operations_logistics'), icon: 'mdi-truck-cargo-container', color: '#42A5F5', perm: 'charges.view', keywords: ['charge', 'cargos', 'trucks', 'camiones'] },
        { routeName: 'add_charge', title: t('add_charge'), category: t('operations_logistics'), icon: 'mdi-plus-circle-outline', color: '#42A5F5', perm: 'charges.create', keywords: ['create', 'charge', 'nuevo cargo'] },
        { routeName: 'production_list', title: t('production_list'), category: t('operations_logistics'), icon: 'mdi-factory', color: '#00897B', perm: 'production.view', keywords: ['production', 'produccion', 'usine', 'fish', 'pescado'] },
        { routeName: 'add_production', title: t('add_production'), category: t('operations_logistics'), icon: 'mdi-plus-circle-outline', color: '#00897B', perm: 'production.create', keywords: ['create', 'production', 'nueva produccion'] },
        { routeName: 'charge_production_management', title: t('charge_production_management'), category: t('operations_logistics'), icon: 'mdi-link-variant', color: '#00897B', perm: 'production.view', keywords: ['link', 'association'] },
        { routeName: 'list_shipping', title: t('list_shipping'), category: t('operations_logistics'), icon: 'mdi-package-variant-closed', color: '#4643a0', perm: 'shippings.view', keywords: ['shipping', 'envios', 'expedition', 'conteneurs'] },
        { routeName: 'add_shipping', title: t('add_shipping'), category: t('operations_logistics'), icon: 'mdi-plus-circle-outline', color: '#4643a0', perm: 'shippings.create', keywords: ['create', 'shipping', 'nuevo envio'] },
        { routeName: 'list_warehouse_transfer', title: t('list_warehouse_transfer'), category: t('operations_logistics'), icon: 'mdi-swap-horizontal', color: '#F9A825', perm: 'warehouse_transfers.view', keywords: ['transfer', 'transferencias', 'inter-warehouse', 'movement'] },

        // Human Resources
        { routeName: 'workforce_management', title: t('workforce_management'), category: t('human_resources'), icon: 'mdi-account-multiple', color: '#1565C0', perm: 'workforce.view', keywords: ['workers', 'personal', 'ouvriers', 'salaries'] },
        { routeName: 'corporal_management', title: t('corporal_management'), category: t('human_resources'), icon: 'mdi-account-tie', color: '#1565C0', perm: 'workforce.view', keywords: ['corporals', 'capataces', 'chefs'] },
        { routeName: 'team_management', title: t('team_management'), category: t('human_resources'), icon: 'mdi-account-group', color: '#1565C0', perm: 'workforce.view', keywords: ['teams', 'equipes', 'grupos', 'brigades'] },
        { routeName: 'section_management', title: t('section_management'), category: t('human_resources'), icon: 'mdi-sitemap-outline', color: '#1565C0', perm: 'workforce.view', keywords: ['sections', 'secciones', 'departments'] },
        { routeName: 'fortnight_management', title: t('fortnight_management'), category: t('human_resources'), icon: 'mdi-calendar-month', color: '#1565C0', perm: 'workforce.view', keywords: ['fortnight', 'quincena', 'quinzaine', 'payroll', 'nomina'] },
        { routeName: 'workforce_absence_movement', title: t('workforce_absence_movement_title'), category: t('human_resources'), icon: 'mdi-account-clock-outline', color: '#1565C0', perm: 'workforce.view', keywords: ['absence', 'absent', 'movement', 'depart', 'retour', 'motif', 'leave'] },
        { routeName: 'workforce_statistics', title: t('workforce_statistics'), category: t('human_resources'), icon: 'mdi-chart-bar', color: '#1565C0', perm: 'workforce.view', keywords: ['workforce stats', 'rendimiento'] },

        // Finance & Billing
        { routeName: 'list_supplier_invoice', title: t('list_supplier_invoice'), category: t('billing_finance'), icon: 'mdi-file-document-multiple', color: '#E65100', perm: 'supplier_invoices.view', keywords: ['supplier', 'proveedor', 'facturas', 'facture'] },
        { routeName: 'supplier_settlement', title: t('supplier_settlement'), category: t('billing_finance'), icon: 'mdi-cash-register', color: '#E65100', perm: 'supplier_invoices.view', keywords: ['supplier payment', 'pago proveedor', 'reglement'] },
        { routeName: 'list_client_invoice', title: t('list_client_invoice'), category: t('billing_finance'), icon: 'mdi-file-document-multiple', color: '#00838F', perm: 'client_invoices.view', keywords: ['client', 'cliente', 'facturas cliente'] },
        { routeName: 'client_settlement', title: t('client_settlement'), category: t('billing_finance'), icon: 'mdi-cash-register', color: '#00838F', perm: 'client_invoices.view', keywords: ['client payment', 'cobro cliente', 'encaissement'] },
        { routeName: 'order_transfers_page', title: t('order_transfers'), category: t('billing_finance'), icon: 'mdi-bank-transfer', color: '#00897B', perm: 'order_transfers.view', keywords: ['order transfer', 'transferencias orden', 'virement'] },

        // System & Administration
        { routeName: 'admin_users', title: t('user_management'), category: t('system_administration'), icon: 'mdi-shield-account', color: '#5C6BC0', perm: 'users.manage', keywords: ['users', 'usuarios', 'roles', 'permissions'] },
        { routeName: 'profile_settings', title: t('my_profile'), category: t('system_administration'), icon: 'mdi-account-cog-outline', color: '#1976D2', keywords: ['profile', 'perfil', 'password', 'account'] },
        { routeName: 'warehouse_management', title: t('warehouse_management'), category: t('system_administration'), icon: 'mdi-warehouse', color: '#546E7A', perm: 'settings.warehouses' },
        { routeName: 'fish_management', title: t('fish_management'), category: t('system_administration'), icon: 'mdi-fish', color: '#546E7A', perm: 'settings.fish' },
        { routeName: 'charge_management', title: t('charge_management'), category: t('system_administration'), icon: 'mdi-shape-outline', color: '#546E7A', perm: 'settings.charges' },
        { routeName: 'shipping_management', title: t('shipping_management'), category: t('system_administration'), icon: 'mdi-tag-multiple-outline', color: '#546E7A', perm: 'settings.shippings' },
        { routeName: 'production_management', title: t('production_management'), category: t('system_administration'), icon: 'mdi-cog-outline', color: '#546E7A', perm: 'settings.production' },
        { routeName: 'detail_inventory_category_management', title: t('detail_inventory_category_management'), category: t('system_administration'), icon: 'mdi-clipboard-text-clock-outline', color: '#546E7A', perm: 'settings.inventory_categories' },
        { routeName: 'warehouse_transfer_category_management', title: t('warehouse_transfer_category_management'), category: t('system_administration'), icon: 'mdi-swap-horizontal', color: '#546E7A', perm: 'settings.transfer_categories' },
        { routeName: 'guano_management', title: t('guano_management'), category: t('system_administration'), icon: 'mdi-leaf', color: '#388E3C', perm: 'settings.guano' },
        { routeName: 'package_management', title: t('package_management'), category: t('system_administration'), icon: 'mdi-package-variant-closed', color: '#546E7A', perm: 'settings.packages' },
        { routeName: 'bank_name_management', title: t('bank_name_management'), category: t('system_administration'), icon: 'mdi-bank', color: '#546E7A', perm: 'settings.banks' },
        { routeName: 'company_settings', title: t('company_settings'), category: t('system_administration'), icon: 'mdi-domain', color: '#546E7A', perm: 'settings.company' },
        { routeName: 'fortnight_settings', title: t('fortnight_settings'), category: t('system_administration'), icon: 'mdi-calendar-clock', color: '#546E7A', perm: 'settings.fortnight' },
        { routeName: 'chart_management', title: t('chart_management'), category: t('system_administration'), icon: 'mdi-chart-areaspline', color: '#546E7A', perm: 'settings.charts' },
        { routeName: 'static_charge_management', title: t('static_charge_management'), category: t('system_administration'), icon: 'mdi-cash-lock-open', color: '#546E7A', perm: 'settings.static_charges' },
        { routeName: 'cost_configuration_management', title: t('cost_configuration_management'), category: t('system_administration'), icon: 'mdi-calculator-variant-outline', color: '#546E7A', perm: 'settings.charges' },
        { routeName: 'client_management', title: t('client_management'), category: t('system_administration'), icon: 'mdi-account-tie', color: '#00838F', perm: 'settings.clients' },
        { routeName: 'supplier_management', title: t('supplier_management'), category: t('system_administration'), icon: 'mdi-truck-delivery', color: '#E65100', perm: 'settings.suppliers' },
      ];

      return items.filter(item => {
        if (!item.perm) return true;
        if (item.perm === 'users.manage') return auth.isAdmin;
        return auth.hasPermission(item.perm);
      });
    },
    filteredItems() {
      const q = this.query.trim().toLowerCase();
      if (!q) {
        return this.catalog;
      }
      return this.catalog.filter(item => {
        const titleMatch = (item.title || '').toLowerCase().includes(q);
        const catMatch = (item.category || '').toLowerCase().includes(q);
        const kwMatch = (item.keywords || []).some(k => k.toLowerCase().includes(q));
        const routeMatch = (item.routeName || '').toLowerCase().includes(q);
        return titleMatch || catMatch || kwMatch || routeMatch;
      });
    },
    groupedResults() {
      const groups = {};
      let currentIndex = 0;

      for (const item of this.filteredItems) {
        if (!groups[item.category]) {
          groups[item.category] = {
            category: item.category,
            items: []
          };
        }
        groups[item.category].items.push({
          ...item,
          index: currentIndex++
        });
      }

      return Object.values(groups);
    },
    totalCount() {
      return this.filteredItems.length;
    }
  },
  watch: {
    isOpen(val) {
      if (val) {
        this.query = '';
        this.selectedIndex = 0;
        this.$nextTick(() => {
          setTimeout(() => {
            if (this.$refs.searchInput) {
              this.$refs.searchInput.focus();
            }
          }, 100);
        });
      }
    },
    query() {
      this.selectedIndex = 0;
    }
  },
  mounted() {
    window.addEventListener('keydown', this.handleGlobalKeyDown);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.handleGlobalKeyDown);
  },
  methods: {
    t(key) {
      const fallbacks = {
        core_analytics: this.lang === 'es' ? 'Analítica e IA' : this.lang === 'fr' ? 'Analytique et IA' : 'Analytics & AI',
        operations_logistics: this.lang === 'es' ? 'Operaciones y Logística' : this.lang === 'fr' ? 'Opérations et Logistique' : 'Operations & Logistics',
        human_resources: this.lang === 'es' ? 'Recursos Humanos' : this.lang === 'fr' ? 'Ressources Humaines' : 'Human Resources',
        billing_finance: this.lang === 'es' ? 'Facturación y Finanzas' : this.lang === 'fr' ? 'Facturation et Finances' : 'Billing & Finance',
        system_administration: this.lang === 'es' ? 'Sistema y Ajustes' : this.lang === 'fr' ? 'Système et Paramètres' : 'System & Settings',
        search_placeholder: this.lang === 'es' ? 'Búsqueda rápida o pulsa Ctrl+K...' : this.lang === 'fr' ? 'Recherche rapide ou Ctrl+K...' : 'Quick search or press Ctrl+K...',
        navigate_to: this.lang === 'es' ? 'Navegar' : this.lang === 'fr' ? 'Naviguer' : 'Navigate',
        navigation: this.lang === 'es' ? 'Navegación' : this.lang === 'fr' ? 'Navigation' : 'Navigation',
        no_pages_found: this.lang === 'es' ? 'No se encontraron páginas coincidentes' : this.lang === 'fr' ? 'Aucune page correspondante trouvée' : 'No matching pages found',
        open: this.lang === 'es' ? 'Abrir' : this.lang === 'fr' ? 'Ouvrir' : 'Open',
        close: this.lang === 'es' ? 'Cerrar' : this.lang === 'fr' ? 'Fermer' : 'Close',
        my_profile: this.lang === 'es' ? 'Mi Perfil' : this.lang === 'fr' ? 'Mon Profil' : 'My Profile',
      };

      if (this.$te && this.$te(key)) {
        const val = this.$t(key);
        if (val && val !== key) return val;
      }
      return fallbacks[key] || this.$t(key);
    },
    handleGlobalKeyDown(e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        this.isOpen = !this.isOpen;
      }
    },
    close() {
      this.isOpen = false;
    },
    onClose() {
      this.query = '';
      this.selectedIndex = 0;
    },
    navigateResults(direction) {
      if (this.totalCount === 0) return;
      let next = this.selectedIndex + direction;
      if (next < 0) next = this.totalCount - 1;
      if (next >= this.totalCount) next = 0;
      this.selectedIndex = next;

      this.$nextTick(() => {
        const el = document.getElementById('cmd-item-' + this.selectedIndex);
        if (el) {
          el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
      });
    },
    selectCurrent() {
      if (this.totalCount === 0) return;
      const flat = this.filteredItems;
      const target = flat[this.selectedIndex];
      if (target) {
        this.selectItem(target);
      }
    },
    selectItem(item) {
      this.close();
      if (item.routeName) {
        this.$router.push({ name: item.routeName }).catch(() => {});
      }
    },
    hexToRgba(hex, alpha = 1) {
      if (!hex || typeof hex !== 'string') return `rgba(25, 118, 210, ${alpha})`;
      if (hex.startsWith('rgb')) return hex;
      let clean = hex.replace('#', '');
      if (clean.length === 3) clean = clean.split('').map(x => x + x).join('');
      if (clean.length !== 6) return `rgba(25, 118, 210, ${alpha})`;
      const num = parseInt(clean, 16);
      const r = (num >> 16) & 255;
      const g = (num >> 8) & 255;
      const b = num & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
  }
};
</script>

<style scoped>
.command-palette-card {
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 80vh;
}

.card-light {
  background: rgba(255, 255, 255, 0.98) !important;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 20px 48px -10px rgba(0, 0, 0, 0.18) !important;
}

.card-dark {
  background: rgba(30, 36, 48, 0.98) !important;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 24px 56px -12px rgba(0, 0, 0, 0.6) !important;
}

/* Header */
.command-input-wrapper {
  display: flex;
  align-items: center;
  padding: 14px 18px;
  gap: 12px;
}

.command-search-icon {
  flex-shrink: 0;
}

.command-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 1.05rem;
  font-family: inherit;
  color: inherit;
}

.command-input::placeholder {
  opacity: 0.55;
}

.command-esc-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 6px;
  background: rgba(var(--v-theme-on-surface), 0.08);
  color: rgba(var(--v-theme-on-surface), 0.6);
  letter-spacing: 0.5px;
}

/* Results */
.command-results-list {
  flex: 1;
  overflow-y: auto;
  max-height: 440px;
  padding: 8px 12px 14px;
}

.command-results-list::-webkit-scrollbar {
  width: 5px;
}
.command-results-list::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-on-surface), 0.15);
  border-radius: 4px;
}

.command-group {
  margin-bottom: 8px;
}

.command-group-title {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: rgba(var(--v-theme-on-surface), 0.5);
  padding: 8px 10px 4px;
}

.command-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.18s ease;
  user-select: none;
}

.command-item.is-selected {
  background: rgba(33, 150, 243, 0.12);
  transform: translateX(3px);
}

.command-item-icon {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.command-item.is-selected .command-item-icon {
  transform: scale(1.08);
}

.command-item-info {
  flex: 1;
  min-width: 0;
}

.command-item-title {
  font-size: 0.92rem;
  font-weight: 600;
  line-height: 1.25;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.command-item-subtitle {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.5);
  margin-top: 1px;
}

.command-item-action {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.command-enter-icon {
  opacity: 0;
  transition: opacity 0.2s ease;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.command-item.is-selected .command-enter-icon {
  opacity: 1;
}

/* Empty State */
.command-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 16px;
  text-align: center;
}

/* Footer */
.command-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 16px;
  padding: 8px 18px;
  background: rgba(var(--v-theme-on-surface), 0.03);
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.command-footer-tip {
  display: flex;
  align-items: center;
  gap: 4px;
}

.command-footer-tip kbd {
  font-family: inherit;
  font-size: 0.7rem;
  padding: 1px 5px;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}
</style>
