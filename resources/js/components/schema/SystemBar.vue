<template>
  <v-app-bar
    style="z-index: 1002"
    height="58"
    width="100vw"
    flat
    :class="['systembar-premium', main.is_dark ? 'systembar-dark' : 'systembar-light']"
    elevation="0"
  >
    <div class="systembar-inner">
      <!-- Left: Toggle & Route Breadcrumb Context -->
      <div class="systembar-left">
        <v-btn
          v-if="auth.is_auth"
          icon
          variant="tonal"
          color="primary"
          size="38"
          class="systembar-nav-toggle-btn"
          @click.prevent="main.sidebar_mini = !main.sidebar_mini"
        >
          <v-icon size="20">{{ main.sidebar_mini ? 'mdi-menu' : 'mdi-menu-open' }}</v-icon>
          <v-tooltip activator="parent" location="bottom">
            {{ main.sidebar_mini ? t('expand_sidebar') : t('collapse_sidebar') }}
          </v-tooltip>
        </v-btn>

        <!-- Dynamic Context Breadcrumb -->
        <div v-if="currentContext" class="systembar-context-pill d-none d-sm-flex" :style="contextStyle">
          <v-icon size="17" class="mr-1.5" :style="{ color: currentContext.color }">{{ currentContext.icon }}</v-icon>
          <span class="context-section">{{ currentContext.section }}</span>
          <v-icon size="14" class="context-separator mx-1 text-medium-emphasis">mdi-chevron-right</v-icon>
          <span class="context-page">{{ currentContext.title }}</span>
        </div>
      </div>

      <!-- Center: Quick Search / Command Launcher -->
      <div class="systembar-center">
        <button
          v-if="auth.is_auth"
          type="button"
          class="systembar-search-trigger"
          :class="main.is_dark ? 'search-trigger-dark' : 'search-trigger-light'"
          @click="commandPaletteOpen = true"
        >
          <v-icon size="18" class="search-trigger-icon">mdi-magnify</v-icon>
          <span class="search-trigger-text">{{ t('search_placeholder') }}</span>
          <span class="search-trigger-badge">
            <kbd>Ctrl</kbd> + <kbd>K</kbd>
          </span>
        </button>

        <!-- Brand fallback if not logged in -->
        <router-link v-else to="/" class="systembar-logo-link">
          <img :src="miniLogoUrl" alt="Jupiter" class="systembar-mini-logo" />
          <span class="systembar-logo-text">{{ $t("title_logo") }} App</span>
        </router-link>
      </div>

      <!-- Right: Unified Controls Dock -->
      <div class="systembar-right">
        <div class="systembar-dock" :class="main.is_dark ? 'dock-dark' : 'dock-light'">
          <!-- PWA Offline Indicator -->
          <v-chip
            v-if="!pwa.is_online"
            color="warning"
            variant="flat"
            size="x-small"
            class="systembar-offline-chip pulse-warning mr-1"
          >
            <v-icon start size="12">mdi-wifi-off</v-icon>
            {{ $t('offline') || 'Offline' }}
          </v-chip>

          <!-- PWA Install Button -->
          <v-btn
            v-if="pwa.isInstallable && !pwa.is_installed"
            icon
            variant="text"
            size="32"
            class="dock-btn bounce-btn text-info"
            @click="pwa.triggerInstall"
          >
            <v-icon size="18">mdi-download-network-outline</v-icon>
            <v-tooltip activator="parent" location="bottom">{{ $t('pwa_install_btn') }}</v-tooltip>
          </v-btn>

          <!-- Online Users Menu -->
          <v-menu
            transition="scale-transition"
            :close-on-content-click="false"
            v-if="auth.is_auth"
            offset="8"
          >
            <template v-slot:activator="{ props }">
              <v-btn
                v-bind="props"
                icon
                variant="text"
                size="32"
                class="dock-btn text-success"
                id="online-users-btn"
              >
                <v-badge
                  v-if="_online.onlineCount > 0"
                  :content="_online.onlineCount"
                  color="success"
                  location="bottom end"
                  offset-x="2"
                  offset-y="2"
                >
                  <v-icon size="18" :class="{ 'online-pulse-icon': _online.onlineCount > 0 }">mdi-account-group</v-icon>
                </v-badge>
                <v-icon v-else size="18">mdi-account-group-outline</v-icon>
                <v-tooltip activator="parent" location="bottom">{{ $t('online_users') }} ({{ _online.onlineCount }})</v-tooltip>
              </v-btn>
            </template>
            <v-card
              class="dock-panel"
              :class="main.is_dark ? 'panel-dark' : 'panel-light'"
              min-width="260"
              max-width="340"
              rounded="xl"
              elevation="16"
            >
              <div class="dock-panel-header">
                <v-icon class="mr-2" size="18" color="success">mdi-circle</v-icon>
                <span class="text-subtitle-2 font-weight-bold">{{ $t('online_users') }}</span>
                <v-chip size="x-small" color="success" variant="tonal" class="ml-auto font-weight-bold">{{ _online.onlineCount }}</v-chip>
              </div>
              <v-divider></v-divider>
              <v-list density="compact" class="pa-2" v-if="_online.otherUsers.length > 0">
                <v-list-item
                  v-for="user in _online.otherUsers"
                  :key="user.id"
                  class="online-user-item"
                  rounded="lg"
                >
                  <template v-slot:prepend>
                    <v-avatar size="28" color="success" class="mr-2.5">
                      <v-icon size="15" color="white">mdi-account</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="text-body-2 font-weight-medium">{{ user.name }}</v-list-item-title>
                  <template v-slot:append>
                    <v-icon size="8" color="success">mdi-circle</v-icon>
                  </template>
                </v-list-item>
              </v-list>
              <div v-else class="text-medium-emphasis d-flex align-center justify-center py-6 text-caption">
                <v-icon icon="mdi-account-off-outline" size="18" class="mr-2"></v-icon>
                <span>{{ $t('no_users_online') || 'No users online' }}</span>
              </div>
            </v-card>
          </v-menu>

          <!-- Notification Bell Menu -->
          <v-menu
            transition="scale-transition"
            :close-on-content-click="false"
            v-if="auth.is_auth"
            v-model="notifMenuOpen"
            offset="8"
          >
            <template v-slot:activator="{ props }">
              <v-btn
                v-bind="props"
                icon
                variant="text"
                size="32"
                class="dock-btn text-amber-darken-2"
                id="notification-bell-btn"
              >
                <v-badge
                  v-if="notifCenter.unreadCount > 0"
                  :content="notifCenter.unreadCount > 99 ? '99+' : notifCenter.unreadCount"
                  color="error"
                  location="bottom end"
                  offset-x="2"
                  offset-y="2"
                >
                  <v-icon size="18" class="notif-bell-ring">mdi-bell</v-icon>
                </v-badge>
                <v-icon v-else size="18">mdi-bell-outline</v-icon>
                <v-tooltip activator="parent" location="bottom">{{ $t('notifications') }}</v-tooltip>
              </v-btn>
            </template>
            <v-card
              class="dock-panel notif-panel"
              :class="main.is_dark ? 'panel-dark' : 'panel-light'"
              min-width="380"
              max-width="420"
              rounded="xl"
              elevation="16"
            >
              <div class="dock-panel-header">
                <div class="d-flex align-center">
                  <v-icon size="18" color="amber-darken-2" class="mr-2">mdi-bell-ring-outline</v-icon>
                  <span class="text-subtitle-2 font-weight-bold">{{ $t('notifications') }}</span>
                  <v-chip v-if="notifCenter.unreadCount > 0" size="x-small" color="error" variant="flat" class="ml-2 font-weight-bold">{{ notifCenter.unreadCount }}</v-chip>
                </div>
                <v-btn
                  v-if="notifCenter.unreadCount > 0"
                  variant="text"
                  size="x-small"
                  color="primary"
                  class="text-none ml-auto"
                  @click="notifCenter.markAllAsRead()"
                >
                  <v-icon size="14" class="mr-1">mdi-check-all</v-icon>
                  {{ $t('mark_all_as_read') }}
                </v-btn>
              </div>
              <v-divider></v-divider>

              <div class="notif-list" v-if="notifCenter.notifications.length > 0">
                <div
                  v-for="notif in notifCenter.notifications.slice(0, 30)"
                  :key="notif.id"
                  class="notif-item"
                  :class="{ 'notif-item--unread': !notif.read_at }"
                  @click="onNotifClick(notif)"
                >
                  <div class="notif-item-icon" :style="{ background: notifCenter.getColorForModule(notif.type) + '18' }">
                    <v-icon size="17" :color="notifCenter.getColorForModule(notif.type)">{{ notifCenter.getIconForModule(notif.type) }}</v-icon>
                  </div>
                  <div class="notif-item-body">
                    <div class="notif-item-title">
                      <strong>{{ notif.actor_name }}</strong>
                      {{ getNotifActionText(notif.action) }}
                      <v-chip size="x-small" :color="notifCenter.getColorForModule(notif.type)" variant="tonal" class="ml-1">{{ $t(notifCenter.getI18nKeyForModule(notif.type)) }}</v-chip>
                    </div>
                    <div class="notif-item-meta">
                      <span v-if="notif.data && notif.data.tracking" class="mr-2">
                        <v-icon size="10" class="mr-1">mdi-barcode</v-icon>{{ notif.data.tracking }}
                      </span>
                      <span class="notif-time">
                        <v-icon size="10" class="mr-1">mdi-clock-outline</v-icon>
                        {{ notifCenter.getRelativeTime(notif.created_at, $t) }}
                      </span>
                    </div>
                  </div>
                  <div v-if="!notif.read_at" class="notif-unread-dot"></div>
                </div>
              </div>

              <div v-else class="d-flex flex-column align-center justify-center py-8" style="opacity:.6">
                <v-icon size="40" color="amber-darken-2" class="mb-2">mdi-bell-sleep-outline</v-icon>
                <span class="text-caption font-weight-medium">{{ $t('no_notifications') }}</span>
              </div>

              <v-progress-linear v-if="notifCenter.loading" indeterminate color="amber-darken-2" height="2"></v-progress-linear>
            </v-card>
          </v-menu>

          <!-- Language Selector -->
          <v-menu
            transition="scale-transition"
            :close-on-content-click="true"
            offset="8"
          >
            <template v-slot:activator="{ props }">
              <v-btn
                v-bind="props"
                variant="text"
                size="32"
                class="dock-btn lang-btn"
              >
                <img :src="currentLangFlag" class="dock-flag-img" :alt="main.lang ? main.lang.code : 'lang'" />
                <v-tooltip activator="parent" location="bottom">{{ $t('language') || 'Language' }}</v-tooltip>
              </v-btn>
            </template>
            <v-card
              class="dock-panel"
              :class="main.is_dark ? 'panel-dark' : 'panel-light'"
              rounded="xl"
              elevation="16"
              min-width="200"
            >
              <div class="dock-panel-header">
                <v-icon size="17" class="mr-2" color="primary">mdi-translate</v-icon>
                <span class="text-subtitle-2 font-weight-bold">{{ $t('language') || 'Language' }}</span>
              </div>
              <v-divider></v-divider>
              <div class="lang-dropdown-list pa-2">
                <button
                  v-for="langItem in main.langs"
                  :key="langItem.id + langItem.code"
                  @click="changeLanguage(langItem)"
                  :class="['lang-option', { 'lang-option-active': main.lang && main.lang.code === langItem.code }]"
                >
                  <img :src="getLangFlag(langItem.code)" class="lang-option-flag" :alt="langItem.code" />
                  <div class="lang-option-info">
                    <span class="lang-option-name">{{ getLangName(langItem.code) }}</span>
                    <span class="lang-option-code">{{ langItem.code.toUpperCase() }}</span>
                  </div>
                  <v-icon
                    v-if="main.lang && main.lang.code === langItem.code"
                    size="16"
                    color="primary"
                    class="lang-option-check"
                  >mdi-check-circle</v-icon>
                </button>
              </div>
            </v-card>
          </v-menu>

          <!-- Theme Toggle (Dark / Light) -->
          <v-btn
            icon
            variant="text"
            size="32"
            class="dock-btn theme-toggle-btn"
            :class="main.is_dark ? 'text-amber' : 'text-indigo-darken-1'"
            @click="main.is_dark = !main.is_dark"
          >
            <v-icon size="18" :class="{ 'theme-icon-rotate': main.is_dark }">
              {{ main.is_dark ? 'mdi-white-balance-sunny' : 'mdi-weather-night' }}
            </v-icon>
            <v-tooltip activator="parent" location="bottom">
              {{ main.is_dark ? 'Light Mode' : 'Dark Mode' }}
            </v-tooltip>
          </v-btn>

          <!-- User Profile Menu -->
          <v-menu
            v-if="auth.is_auth"
            transition="scale-transition"
            offset="8"
          >
            <template v-slot:activator="{ props }">
              <button
                v-bind="props"
                type="button"
                class="user-profile-trigger"
                :class="main.is_dark ? 'user-trigger-dark' : 'user-trigger-light'"
              >
                <v-avatar size="26" class="user-trigger-avatar">
                  <v-icon size="16" color="white">mdi-account</v-icon>
                </v-avatar>
                <span class="user-trigger-name d-none d-md-inline">{{ auth.user?.name?.split(' ')[0] || 'User' }}</span>
                <v-icon size="14" class="user-trigger-arrow text-medium-emphasis">mdi-chevron-down</v-icon>
              </button>
            </template>
            <v-card
              class="dock-panel user-menu-panel"
              :class="main.is_dark ? 'panel-dark' : 'panel-light'"
              min-width="240"
              rounded="xl"
              elevation="16"
            >
              <div class="user-menu-header pa-4">
                <v-avatar size="44" class="user-menu-avatar mb-2">
                  <v-icon size="24" color="white">mdi-account</v-icon>
                </v-avatar>
                <div class="text-subtitle-2 font-weight-bold text-truncate">{{ auth.user?.name }}</div>
                <div class="text-caption text-medium-emphasis text-truncate">{{ auth.user?.email }}</div>
                <v-chip size="x-small" color="primary" variant="tonal" class="mt-2 font-weight-medium">
                  {{ auth.user?.role?.name || 'Administrator' }}
                </v-chip>
              </div>

              <v-divider></v-divider>

              <v-list density="compact" class="pa-2">
                <v-list-item
                  rounded="lg"
                  prepend-icon="mdi-account-circle-outline"
                  :title="t('my_profile')"
                  :to="{ name: 'profile_settings' }"
                ></v-list-item>
                <v-list-item
                  v-if="auth.isAdmin"
                  rounded="lg"
                  prepend-icon="mdi-shield-account-outline"
                  :title="$t('user_management')"
                  :to="{ name: 'admin_users' }"
                ></v-list-item>
                <v-divider class="my-1"></v-divider>
                <v-list-item
                  rounded="lg"
                  prepend-icon="mdi-logout-variant"
                  :title="$t('logout') || 'Logout'"
                  class="text-error"
                  @click="handleLogout"
                ></v-list-item>
              </v-list>
            </v-card>
          </v-menu>
        </div>
      </div>
    </div>

    <!-- Command Palette Dialog -->
    <CommandPalette v-model="commandPaletteOpen" />
  </v-app-bar>
</template>

<script>
import { useDashboardWaiter } from '@/stores/DashboardWaiter';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useOnlineUsers } from '@/stores/OnlineUsers';
import { useNotificationCenter } from '@/stores/NotificationCenter';
import { useCompanySettings } from '@/stores/CompanySettings';
import { usePwa } from '@/stores/pwa';
import annyang from 'annyang';
import CommandPalette from '@/components/schema/CommandPalette.vue';

export default {
  name: 'SystemBar',
  components: {
    CommandPalette
  },
  data() {
    return {
      notifMenuOpen: false,
      commandPaletteOpen: false,
    };
  },
  computed: {
    dashboard_waiter() { return useDashboardWaiter(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    _online() { return useOnlineUsers(); },
    notifCenter() { return useNotificationCenter(); },
    pwa() { return usePwa(); },
    lang() { return this.main.lang?.code || 'en'; },

    currentLangFlag() {
      if (!this.main.lang) return 'https://flagcdn.com/24x18/gb.png';
      return this.getLangFlag(this.main.lang.code);
    },
    miniLogoUrl() {
      const cs = useCompanySettings();
      if (cs.settings?.logo_invoice_body_url) return cs.settings.logo_invoice_body_url;
      return new URL('/images/jupiter-logo.png', window.location.origin).href;
    },
    currentContext() {
      const routeName = this.$route.name;
      if (!routeName) return null;

      const t = this.t;
      const contextMap = {
        index: { section: t('core_analytics'), title: t('home') || 'Home', icon: 'mdi-home-variant-outline', color: '#1E88E5' },
        main_dashboard: { section: t('core_analytics'), title: t('main_dashboard'), icon: 'mdi-chart-box-outline', color: '#1565C0' },
        jupiter_dashboard: { section: t('core_analytics'), title: t('jupiter_dashboard'), icon: 'mdi-orbit-variant', color: '#1E88E5' },
        user_statistics: { section: t('core_analytics'), title: t('user_statistics'), icon: 'mdi-chart-timeline-variant-shimmer', color: '#3949AB' },
        ai_prompt: { section: t('core_analytics'), title: t('ai_prompt'), icon: 'mdi-robot', color: '#42A5F5' },

        warehouses: { section: this.$t('warehouses'), title: this.$t('dashboard'), icon: 'mdi-warehouse', color: '#a21ee5' },
        storage_invoices: { section: this.$t('warehouses'), title: this.$t('storage_invoices'), icon: 'mdi-receipt-text-outline', color: '#a21ee5' },
        list_inventory: { section: this.$t('inventory'), title: this.$t('list_inventory'), icon: 'mdi-clipboard-text-clock-outline', color: '#6D4C41' },
        add_inventory: { section: this.$t('inventory'), title: this.$t('add_inventory'), icon: 'mdi-sticker-plus-outline', color: '#6D4C41' },
        edit_inventory: { section: this.$t('inventory'), title: this.$t('edit_inventory'), icon: 'mdi-pencil-outline', color: '#6D4C41' },

        list_charge: { section: this.$t('charges'), title: this.$t('list_charge'), icon: 'mdi-truck-cargo-container', color: '#42A5F5' },
        add_charge: { section: this.$t('charges'), title: this.$t('add_charge'), icon: 'mdi-plus-circle-outline', color: '#42A5F5' },

        production_list: { section: this.$t('production'), title: this.$t('production_list'), icon: 'mdi-factory', color: '#00897B' },
        add_production: { section: this.$t('production'), title: this.$t('add_production'), icon: 'mdi-plus-circle-outline', color: '#00897B' },
        charge_production_management: { section: this.$t('production'), title: this.$t('charge_production_management'), icon: 'mdi-link-variant', color: '#00897B' },

        list_shipping: { section: this.$t('shippings'), title: this.$t('list_shipping'), icon: 'mdi-package-variant-closed', color: '#4643a0' },
        add_shipping: { section: this.$t('shippings'), title: this.$t('add_shipping'), icon: 'mdi-plus-circle-outline', color: '#4643a0' },

        list_warehouse_transfer: { section: this.$t('warehouse_transfers'), title: this.$t('list_warehouse_transfer'), icon: 'mdi-swap-horizontal', color: '#F9A825' },
        edit_warehouse_transfer: { section: this.$t('warehouse_transfers'), title: this.$t('edit') || 'Edit', icon: 'mdi-pencil-outline', color: '#F9A825' },

        workforce_management: { section: this.$t('workforce'), title: this.$t('workforce_management'), icon: 'mdi-account-multiple', color: '#1565C0' },
        corporal_management: { section: this.$t('workforce'), title: this.$t('corporal_management'), icon: 'mdi-account-tie', color: '#1565C0' },
        section_management: { section: this.$t('workforce'), title: this.$t('section_management'), icon: 'mdi-sitemap-outline', color: '#1565C0' },
        fortnight_management: { section: this.$t('workforce'), title: this.$t('fortnight_management'), icon: 'mdi-calendar-month', color: '#1565C0' },
        workforce_statistics: { section: this.$t('workforce'), title: this.$t('workforce_statistics'), icon: 'mdi-chart-bar', color: '#1565C0' },

        list_supplier_invoice: { section: this.$t('supplier_invoices'), title: this.$t('list_supplier_invoice'), icon: 'mdi-file-document-multiple', color: '#E65100' },
        supplier_settlement: { section: this.$t('supplier_invoices'), title: this.$t('supplier_settlement'), icon: 'mdi-cash-register', color: '#E65100' },
        list_client_invoice: { section: this.$t('client_invoics'), title: this.$t('list_client_invoice'), icon: 'mdi-file-document-multiple', color: '#00838F' },
        client_settlement: { section: this.$t('client_invoics'), title: this.$t('client_settlement'), icon: 'mdi-cash-register', color: '#00838F' },
        order_transfers_page: { section: this.$t('order_transfers'), title: this.$t('order_transfers'), icon: 'mdi-bank-transfer', color: '#00897B' },

        profile_settings: { section: t('system_administration'), title: t('my_profile'), icon: 'mdi-account-cog-outline', color: '#1976D2' },
        admin_users: { section: t('system_administration'), title: this.$t('user_management'), icon: 'mdi-shield-account', color: '#5C6BC0' },
      };

      return contextMap[routeName] || null;
    },
    contextStyle() {
      if (!this.currentContext) return {};
      const c = this.currentContext.color;
      return {
        backgroundColor: this.hexToRgba(c, 0.08),
        borderColor: this.hexToRgba(c, 0.22),
      };
    }
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
        collapse_sidebar: this.lang === 'es' ? 'Colapsar barra lateral' : this.lang === 'fr' ? 'Réduire la barre latérale' : 'Collapse sidebar',
        expand_sidebar: this.lang === 'es' ? 'Expandir barra lateral' : this.lang === 'fr' ? 'Agrandir la barre latérale' : 'Expand sidebar',
        my_profile: this.lang === 'es' ? 'Mi Perfil' : this.lang === 'fr' ? 'Mon Profil' : 'My Profile',
        home: this.lang === 'es' ? 'Inicio' : this.lang === 'fr' ? 'Accueil' : 'Home',
        main_dashboard: this.$t('main_dashboard'),
        jupiter_dashboard: this.$t('jupiter_dashboard'),
        user_statistics: this.$t('user_statistics'),
        ai_prompt: this.$t('ai_prompt'),
      };

      if (this.$te && this.$te(key)) {
        const val = this.$t(key);
        if (val && val !== key) return val;
      }
      return fallbacks[key] || this.$t(key);
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
    },
    getLangFlag(code) {
      const flags = {
        en: 'https://flagcdn.com/24x18/gb.png',
        fr: 'https://flagcdn.com/24x18/fr.png',
        es: 'https://flagcdn.com/24x18/es.png',
        ar: 'https://flagcdn.com/24x18/ma.png',
      };
      return flags[code] || 'https://flagcdn.com/24x18/gb.png';
    },
    getLangName(code) {
      const names = {
        en: this.$t('english'),
        fr: this.$t('french'),
        es: this.$t('spanish'),
      };
      return names[code] || code;
    },
    changeLanguage(langItem) {
      localStorage.setItem('language', langItem.code);
      this.$vuetify.locale.current = langItem.code;
      this.$i18n.locale = langItem.code;
      annyang.setLanguage(langItem.annyang);
      this.main.lang = langItem;
    },
    getNotifActionText(action) {
      const map = {
        created: this.$t('created').toLowerCase(),
        updated: this.$t('updated').toLowerCase(),
        deleted: this.$t('deleted').toLowerCase()
      };
      return map[action] || action;
    },
    onNotifClick(notif) {
      if (!notif.read_at) {
        this.notifCenter.markAsRead(notif.id);
      }
      const route = this.notifCenter.getRouteForNotification(notif);
      if (route) {
        this.notifMenuOpen = false;
        this.$router.push(route).catch(() => {});
      }
    },
    async handleLogout() {
      this.auth.user = null;
      this.auth.token = null;
      localStorage.removeItem('token');
      this.$router.push({ name: 'login' });
      this.auth.logout().catch(() => {});
    }
  },
  mounted() {
    if (this.auth.is_auth) {
      this._online.connect();
      this.notifCenter.init();
    }
  },
  beforeUnmount() {
    this._online.disconnect();
  }
};
</script>

<style scoped>
/* ============================================================
   SystemBar - Redesigned Modern Top Bar
   ============================================================ */
.systembar-premium {
  border: none !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.systembar-light {
  background: rgba(255, 255, 255, 0.88) !important;
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
  box-shadow: 0 2px 14px rgba(0, 0, 0, 0.03);
}

.systembar-dark {
  background: rgba(24, 28, 38, 0.90) !important;
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.07) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
}

.systembar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0 16px;
  height: 58px;
  gap: 16px;
}

/* ---------- Left ---------- */
.systembar-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.systembar-nav-toggle-btn {
  border-radius: 12px !important;
  transition: all 0.2s ease !important;
}

.systembar-nav-toggle-btn:hover {
  transform: scale(1.04);
}

.systembar-context-pill {
  display: flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 9999px;
  border: 1px solid transparent;
  font-size: 0.82rem;
  font-weight: 500;
  user-select: none;
  transition: all 0.3s ease;
}

.context-section {
  font-weight: 600;
  opacity: 0.9;
}

.context-page {
  font-weight: 500;
  opacity: 0.75;
}

/* ---------- Center: Quick Search Trigger ---------- */
.systembar-center {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
  max-width: 440px;
}

.systembar-search-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 14px;
  border-radius: 9999px;
  border: 1px solid;
  cursor: pointer;
  outline: none;
  transition: all 0.25s ease;
  font-family: inherit;
  font-size: 0.84rem;
}

.search-trigger-light {
  background: rgba(0, 0, 0, 0.035);
  border-color: rgba(0, 0, 0, 0.08);
  color: rgba(0, 0, 0, 0.55);
}

.search-trigger-light:hover {
  background: rgba(0, 0, 0, 0.06);
  border-color: rgba(33, 150, 243, 0.3);
  color: rgba(0, 0, 0, 0.85);
  transform: translateY(-1px);
}

.search-trigger-dark {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.55);
}

.search-trigger-dark:hover {
  background: rgba(255, 255, 255, 0.09);
  border-color: rgba(66, 165, 245, 0.35);
  color: rgba(255, 255, 255, 0.9);
  transform: translateY(-1px);
}

.search-trigger-icon {
  opacity: 0.6;
}

.search-trigger-text {
  flex: 1;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-trigger-badge {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: 0.7rem;
  opacity: 0.65;
  font-weight: 600;
}

.search-trigger-badge kbd {
  font-family: inherit;
  padding: 1px 5px;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}

/* Brand Link Fallback */
.systembar-logo-link {
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.systembar-mini-logo {
  width: 28px;
  height: 28px;
  object-fit: contain;
  border-radius: 6px;
}

.systembar-logo-text {
  font-family: 'Inter', system-ui, sans-serif;
  font-weight: 800;
  font-size: 1.25rem;
  background: linear-gradient(135deg, #1565c0 0%, #42a5f5 50%, #1565c0 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* ---------- Right: Unified Dock ---------- */
.systembar-right {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.systembar-dock {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 3px 6px;
  border-radius: 9999px;
  border: 1px solid;
  transition: all 0.3s ease;
}

.dock-light {
  background: rgba(0, 0, 0, 0.03);
  border-color: rgba(0, 0, 0, 0.06);
}

.dock-dark {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.07);
}

.dock-btn {
  border-radius: 50% !important;
  transition: all 0.2s ease !important;
}

.dock-btn:hover {
  background: rgba(var(--v-theme-on-surface), 0.08) !important;
  transform: translateY(-1px);
}

.dock-flag-img {
  width: 20px;
  height: 15px;
  border-radius: 3px;
  object-fit: cover;
}

/* Theme icon animation */
.theme-toggle-btn .v-icon {
  transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}
.theme-icon-rotate {
  transform: rotate(180deg);
}

/* User Profile Trigger */
.user-profile-trigger {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 3px 8px 3px 3px;
  border-radius: 9999px;
  border: none;
  cursor: pointer;
  outline: none;
  transition: all 0.2s ease;
  font-family: inherit;
}

.user-trigger-light {
  background: rgba(0, 0, 0, 0.04);
  color: rgba(0, 0, 0, 0.85);
}

.user-trigger-light:hover {
  background: rgba(33, 150, 243, 0.1);
}

.user-trigger-dark {
  background: rgba(255, 255, 255, 0.06);
  color: rgba(255, 255, 255, 0.9);
}

.user-trigger-dark:hover {
  background: rgba(33, 150, 243, 0.15);
}

.user-trigger-avatar {
  background: linear-gradient(135deg, #1976D2, #42A5F5) !important;
}

.user-trigger-name {
  font-size: 0.82rem;
  font-weight: 600;
}

/* Panels */
.dock-panel {
  overflow: hidden;
}

.panel-light {
  background: rgba(255, 255, 255, 0.98) !important;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.panel-dark {
  background: rgba(30, 36, 48, 0.98) !important;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.dock-panel-header {
  display: flex;
  align-items: center;
  padding: 12px 16px;
}

/* User Menu Header */
.user-menu-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.user-menu-avatar {
  background: linear-gradient(135deg, #1976D2, #42A5F5) !important;
}

/* Language Dropdown */
.lang-dropdown-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.lang-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  border-radius: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
  text-align: left;
  color: inherit;
  font-family: inherit;
}

.lang-option:hover {
  background: rgba(33, 150, 243, 0.08);
}

.lang-option-active {
  background: rgba(33, 150, 243, 0.12);
}

.lang-option-flag {
  width: 28px;
  height: 20px;
  border-radius: 4px;
  object-fit: cover;
}

.lang-option-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.lang-option-name {
  font-size: 0.85rem;
  font-weight: 600;
}

.lang-option-code {
  font-size: 0.68rem;
  opacity: 0.5;
}

/* Notifications */
.notif-bell-ring {
  animation: bellRing 2.5s ease-in-out infinite;
}
@keyframes bellRing {
  0%   { transform: rotate(0); }
  5%   { transform: rotate(12deg); }
  10%  { transform: rotate(-10deg); }
  15%  { transform: rotate(8deg); }
  20%  { transform: rotate(-6deg); }
  25%  { transform: rotate(0); }
  100% { transform: rotate(0); }
}

.notif-list {
  max-height: 380px;
  overflow-y: auto;
  padding: 4px;
}
.notif-list::-webkit-scrollbar { width: 4px; }
.notif-list::-webkit-scrollbar-thumb { background: rgba(var(--v-theme-on-surface), .15); border-radius: 4px; }

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  cursor: pointer;
  transition: background .2s ease;
}
.notif-item:hover {
  background: rgba(var(--v-theme-on-surface), .05);
}
.notif-item--unread {
  background: rgba(255, 160, 0, .05);
}

.notif-item-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.notif-item-body {
  flex: 1;
  min-width: 0;
}

.notif-item-title {
  font-size: 12px;
  line-height: 1.4;
}

.notif-item-meta {
  font-size: 10.5px;
  color: rgba(var(--v-theme-on-surface), .5);
  margin-top: 2px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.notif-unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #FF9800;
  flex-shrink: 0;
  margin-top: 6px;
  box-shadow: 0 0 6px rgba(255, 152, 0, .4);
}

/* Online Pulse */
.online-pulse-icon {
  animation: onlinePulse 1.8s ease-in-out infinite;
}
@keyframes onlinePulse {
  0%   { opacity: 1; transform: scale(1); }
  5%   { opacity: 0.6; transform: scale(1.15); }
  100% { opacity: 1; transform: scale(1); }
}

.pulse-warning {
  animation: pulseWarning 2s infinite;
}
@keyframes pulseWarning {
  0%   { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.4); }
  70%  { box-shadow: 0 0 0 8px rgba(255, 152, 0, 0); }
  100% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0); }
}
</style>
