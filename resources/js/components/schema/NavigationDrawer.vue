<template>
  <v-navigation-drawer
    permanent
    :rail="main.sidebar_mini"
    :rail-width="72"
    :width="285"
    :theme="main.is_dark ? 'dark' : 'light'"
    :class="['nav-drawer-premium', main.is_dark ? 'nav-dark' : 'nav-light']"
    @click="onDrawerClick"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <!-- Brand / Workspace Header (Relief to the eyes) -->
    <div class="nav-brand-section" :class="{ 'brand-rail': main.sidebar_mini }">
      <!-- Rail mode: Clickable centered avatar logo -->
      <div
        v-if="main.sidebar_mini"
        class="nav-rail-logo cursor-pointer"
        @click.stop="expandSidebar"
      >
        <v-avatar
          size="42"
          class="brand-avatar-squircle"
          :class="main.is_dark ? 'brand-avatar-dark' : 'brand-avatar-light'"
        >
          <img v-if="themeLogoUrl" :src="themeLogoUrl" alt="Jupiter" class="brand-logo-img" />
          <v-icon v-else size="22" :color="main.is_dark ? '#90CAF9' : '#1976D2'">mdi-orbit-variant</v-icon>
        </v-avatar>
        <v-tooltip
          activator="parent"
          location="end"
          offset="14"
          :content-class="main.is_dark ? 'nav-rail-tooltip-dark' : 'nav-rail-tooltip-light'"
        >
          <div class="rail-tooltip-card">
            <span class="rail-tooltip-title">{{ t('expand_sidebar') }}</span>
          </div>
        </v-tooltip>
      </div>

      <!-- Expanded mode: Brand + Workspace + Collapse Toggle -->
      <div v-else class="nav-expanded-brand">
        <div
          class="brand-clickable-area cursor-pointer"
          @click="$router.push({ name: 'index' })"
        >
          <v-avatar
            size="42"
            class="brand-avatar-squircle"
            :class="main.is_dark ? 'brand-avatar-dark' : 'brand-avatar-light'"
          >
            <img v-if="themeLogoUrl" :src="themeLogoUrl" alt="Jupiter" class="brand-logo-img" />
            <v-icon v-else size="22" :color="main.is_dark ? '#90CAF9' : '#1976D2'">mdi-orbit-variant</v-icon>
          </v-avatar>
          <div class="brand-text-block">
            <div class="brand-title-row">
              <span class="brand-title">Jupiter</span>
              <span class="brand-app-pill">ERP</span>
            </div>
            <div class="brand-subtitle-row">
              <span class="live-pulse-dot"></span>
              <span class="brand-status-text">{{ $t('active') || 'Active' }} • Cloud</span>
            </div>
          </div>
          <v-tooltip activator="parent" location="bottom">{{ t('home') || 'Home' }}</v-tooltip>
        </div>
      </div>
    </div>

    <!-- Quick Navigation Search Filter (Expanded mode only) -->
    <div v-if="!main.sidebar_mini" class="nav-search-wrapper px-3.5 mb-2.5">
      <div class="nav-filter-input-box" :class="main.is_dark ? 'filter-dark' : 'filter-light'">
        <v-icon size="16" class="filter-icon">mdi-magnify</v-icon>
        <input
          v-model="filterQuery"
          type="text"
          class="filter-input"
          :placeholder="t('filter_menu')"
          spellcheck="false"
        />
        <v-btn
          v-if="filterQuery"
          icon
          size="20"
          variant="text"
          class="filter-clear-btn"
          @click="filterQuery = ''"
        >
          <v-icon size="12">mdi-close</v-icon>
        </v-btn>
      </div>
    </div>

    <v-divider class="mx-3.5 mb-1.5"></v-divider>

    <!-- ======== RAIL MODE: Perfectly centered colorful icons with readable theme-matching tooltips ======== -->
    <div v-if="main.sidebar_mini" class="nav-rail-content">
      <template v-for="(section, sIdx) in visibleSections" :key="'rail-sec-' + sIdx">
        <div v-if="sIdx > 0" class="rail-divider"></div>
        <div class="rail-items-group">
          <template v-for="item in section.items" :key="'rail-item-' + item.id">
            <!-- Special AI Prompt with glowing border -->
            <div
              v-if="item.url === 'ai_prompt'"
              class="rail-icon-wrapper my-1"
              :class="{ 'has-active': isRouteActive(item.url) }"
              :style="getItemStyle(item)"
            >
              <div class="ai-glow-wrapper ai-glow-rail">
                <v-list-item
                  class="rail-icon-btn ai-prompt-item"
                  :to="{ name: item.url }"
                  :exact="true"
                  rounded="lg"
                >
                  <v-icon :size="20" :color="item.color">{{ item.icon }}</v-icon>
                  <v-tooltip
                    activator="parent"
                    location="end"
                    offset="14"
                    :content-class="main.is_dark ? 'nav-rail-tooltip-dark' : 'nav-rail-tooltip-light'"
                  >
                    <div class="rail-tooltip-card">
                      <div class="rail-tooltip-header">
                        <v-icon size="16" :color="item.color" class="rail-tooltip-icon">{{ item.icon }}</v-icon>
                        <span class="rail-tooltip-title">{{ item.title }}</span>
                      </div>
                    </div>
                  </v-tooltip>
                </v-list-item>
              </div>
            </div>

            <!-- Normal Single Link -->
            <div
              v-else-if="!item.children || item.children.length === 0"
              class="rail-icon-wrapper my-0.5"
              :class="{ 'has-active': isRouteActive(item.url) }"
              :style="getItemStyle(item)"
            >
              <v-list-item
                class="rail-icon-btn"
                :class="{ 'is-active': isRouteActive(item.url) }"
                :style="getItemStyle(item)"
                :to="item.url ? { name: item.url } : undefined"
                :exact="true"
                rounded="lg"
              >
                <v-icon :size="20" :color="item.color">{{ item.icon }}</v-icon>
                <v-tooltip
                  activator="parent"
                  location="end"
                  offset="14"
                  :content-class="main.is_dark ? 'nav-rail-tooltip-dark' : 'nav-rail-tooltip-light'"
                >
                  <div class="rail-tooltip-card">
                    <div class="rail-tooltip-header">
                      <v-icon size="16" :color="item.color" class="rail-tooltip-icon">{{ item.icon }}</v-icon>
                      <span class="rail-tooltip-title">{{ item.title }}</span>
                    </div>
                  </div>
                </v-tooltip>
              </v-list-item>
            </div>

            <!-- Group item with expandable subnav in rail mode -->
            <div
              v-else
              class="rail-group-block"
              :class="{ 'is-opened': isGroupOpened(item.title) }"
              :style="getItemStyle(item)"
            >
              <div
                class="rail-icon-wrapper my-0.5"
                :class="{ 'has-active': isAnyChildActive(item) }"
              >
                <!-- If group is NOT opened, show with flyout menu on hover so quick access without expanding is still available -->
                <v-menu
                  v-if="!isGroupOpened(item.title)"
                  location="end"
                  :theme="main.is_dark ? 'dark' : 'light'"
                  open-on-hover
                  :open-delay="60"
                  :close-delay="160"
                  offset="12"
                  transition="slide-x-transition"
                >
                  <template v-slot:activator="{ props: menuProps }">
                    <v-list-item
                      v-bind="menuProps"
                      class="rail-icon-btn"
                      :class="{ 'is-active': isAnyChildActive(item) }"
                      :style="getItemStyle(item)"
                      rounded="lg"
                      @click.stop="toggleGroup(item.title)"
                    >
                      <v-icon :size="20" :color="item.color">{{ item.icon }}</v-icon>
                    </v-list-item>
                  </template>

                  <!-- Rail Flyout Menu Panel (Theme-Synced) -->
                  <div
                    class="rail-flyout-panel"
                    :class="main.is_dark ? 'flyout-dark' : 'flyout-light'"
                    :style="getItemStyle(item)"
                  >
                    <div class="rail-flyout-header" @click.stop="toggleGroup(item.title)">
                      <div class="rail-flyout-icon-pill" :style="{ backgroundColor: hexToRgba(item.color, 0.15) }">
                        <v-icon size="17" :color="item.color">{{ item.icon }}</v-icon>
                      </div>
                      <span class="rail-flyout-title">{{ item.title }}</span>
                      <v-icon size="14" class="ml-auto text-medium-emphasis">mdi-chevron-down</v-icon>
                    </div>
                    <v-divider class="my-1"></v-divider>
                    <div class="rail-flyout-children">
                      <router-link
                        v-for="([childIcon, childText, childUrl]) in item.children"
                        :key="childUrl"
                        :to="{ name: childUrl }"
                        class="rail-flyout-item"
                        :class="{ 'is-active': isRouteActive(childUrl) }"
                      >
                        <span class="rail-flyout-dot" :style="{ backgroundColor: item.color }"></span>
                        <span class="rail-flyout-item-text">{{ childText }}</span>
                        <v-icon v-if="isRouteActive(childUrl)" size="14" :color="item.color" class="ml-auto">mdi-check</v-icon>
                      </router-link>
                    </div>
                  </div>
                </v-menu>

                <!-- If group IS opened, parent button toggles group close, and tooltip describes group -->
                <v-list-item
                  v-else
                  class="rail-icon-btn is-group-expanded"
                  :class="{ 'is-active': isAnyChildActive(item) }"
                  :style="getItemStyle(item)"
                  rounded="lg"
                  @click.stop="toggleGroup(item.title)"
                >
                  <v-icon :size="20" :color="item.color">{{ item.icon }}</v-icon>
                  <span class="rail-opened-indicator-dot" :style="{ backgroundColor: item.color }"></span>
                  <v-tooltip
                    activator="parent"
                    location="end"
                    offset="14"
                    :content-class="main.is_dark ? 'nav-rail-tooltip-dark' : 'nav-rail-tooltip-light'"
                  >
                    <div class="rail-tooltip-card">
                      <div class="rail-tooltip-header">
                        <v-icon size="16" :color="item.color" class="rail-tooltip-icon">{{ item.icon }}</v-icon>
                        <span class="rail-tooltip-title">{{ item.title }}</span>
                      </div>
                      <div class="child-label mt-0.5 opacity-70">{{ $t('click_to_collapse') || 'Click to collapse' }}</div>
                    </div>
                  </v-tooltip>
                </v-list-item>
              </div>

              <!-- Rail Subnav Children (Visible when group was opened!) -->
              <transition-group
                v-if="isGroupOpened(item.title)"
                name="rail-subnav"
                tag="div"
                class="rail-subnav-items"
              >
                <div
                  v-for="([childIcon, childText, childUrl], cIdx) in item.children"
                  :key="'rail-sub-' + childUrl"
                  class="rail-subnav-wrapper my-0.5"
                  :class="{ 'has-active': isRouteActive(childUrl) }"
                >
                  <v-list-item
                    class="rail-subnav-item"
                    :class="{ 'is-active': isRouteActive(childUrl) }"
                    :to="{ name: childUrl }"
                    :exact="true"
                    rounded="lg"
                    @click.stop
                  >
                    <v-icon :size="16" :color="item.color">{{ childIcon || 'mdi-circle-small' }}</v-icon>
                    <v-tooltip
                      activator="parent"
                      location="end"
                      offset="14"
                      :content-class="main.is_dark ? 'nav-rail-tooltip-dark' : 'nav-rail-tooltip-light'"
                    >
                      <div class="rail-tooltip-card">
                        <div class="rail-tooltip-header">
                          <v-icon size="15" :color="item.color" class="rail-tooltip-icon">{{ childIcon || 'mdi-circle-small' }}</v-icon>
                          <span class="rail-tooltip-title">{{ childText }}</span>
                        </div>
                      </div>
                    </v-tooltip>
                  </v-list-item>
                </div>
              </transition-group>
            </div>
          </template>
        </div>
      </template>
    </div>

    <!-- ======== EXPANDED MODE: Categorized Groups with Smooth Hierarchy ======== -->
    <v-list
      v-else
      v-model:opened="openedGroups"
      nav
      density="compact"
      :theme="main.is_dark ? 'dark' : 'light'"
      class="nav-list-expanded"
    >
      <template v-for="(section, sIdx) in filteredSections" :key="'exp-sec-' + sIdx">
        <!-- Section Header -->
        <div class="nav-section-header">
          <span>{{ section.title }}</span>
        </div>

        <template v-for="item in section.items" :key="'exp-item-' + item.id">
          <!-- AI Prompt Special Iridescent Glow -->
          <div v-if="!item.children && item.url === 'ai_prompt'" class="ai-glow-wrapper my-1">
            <v-list-item
              class="nav-list-item ai-prompt-item"
              :to="{ name: item.url }"
              :exact="true"
              rounded="lg"
            >
              <template v-slot:prepend>
                <div class="item-icon-pill" :style="{ backgroundColor: hexToRgba(item.color, 0.15) }">
                  <v-icon size="18" :color="item.color">{{ item.icon }}</v-icon>
                </div>
              </template>
              <v-list-item-title class="font-weight-semibold">{{ item.title }}</v-list-item-title>
            </v-list-item>
          </div>

          <!-- Single Leaf Item -->
          <v-list-item
            v-else-if="!item.children || item.children.length === 0"
            class="nav-list-item"
            :class="{ 'is-active': isRouteActive(item.url) }"
            :style="getItemStyle(item)"
            :to="item.url ? { name: item.url } : undefined"
            :exact="true"
            rounded="lg"
          >
            <template v-slot:prepend>
              <div class="item-icon-pill" :style="{ backgroundColor: hexToRgba(item.color, 0.12) }">
                <v-icon size="18" :color="item.color">{{ item.icon }}</v-icon>
              </div>
            </template>
            <v-list-item-title class="nav-item-title">{{ item.title }}</v-list-item-title>
          </v-list-item>

          <!-- Group Item (Collapsible Accordion) -->
          <v-list-group
            v-else
            :value="item.title"
          >
            <template v-slot:activator="{ isOpen, props }">
              <v-list-item
                v-bind="props"
                class="nav-list-item nav-group-parent"
                :class="{ 'is-active': isAnyChildActive(item) }"
                :style="getItemStyle(item)"
                rounded="lg"
              >
                <template v-slot:prepend>
                  <div class="item-icon-pill" :style="{ backgroundColor: hexToRgba(item.color, 0.12) }">
                    <v-icon size="18" :color="item.color">{{ item.icon }}</v-icon>
                  </div>
                </template>
                <v-list-item-title class="nav-item-title">{{ item.title }}</v-list-item-title>

                <!-- Tooltip showing info of sub nav links when cursor hovers over group nav link -->
                <v-tooltip
                  v-if="item.children && item.children.length > 0"
                  activator="parent"
                  location="end"
                  offset="14"
                  :content-class="main.is_dark ? 'nav-rail-tooltip-dark' : 'nav-rail-tooltip-light'"
                >
                  <div class="rail-tooltip-card">
                    <div class="rail-tooltip-header">
                      <v-icon size="16" :color="item.color" class="rail-tooltip-icon">{{ item.icon }}</v-icon>
                      <span class="rail-tooltip-title">{{ item.title }}</span>
                    </div>
                    <div class="rail-tooltip-children">
                      <div v-for="c in item.children" :key="c[2]" class="rail-tooltip-child">
                        <span class="child-bullet-dot" :style="{ backgroundColor: item.color }"></span>
                        <span class="child-label">{{ c[1] }}</span>
                      </div>
                    </div>
                  </div>
                </v-tooltip>
              </v-list-item>
            </template>

            <!-- Group Children (Readable, proportional spacing, no text truncation) -->
            <v-list-item
              v-for="([childIcon, childText, childUrl], cIdx) in item.children"
              :key="'child-' + cIdx"
              class="nav-list-item nav-child-item"
              :class="{ 'is-active': isRouteActive(childUrl) }"
              :style="getItemStyle(item)"
              :to="{ name: childUrl }"
              :exact="true"
              rounded="lg"
            >
              <div class="nav-child-inner">
                <span class="nav-child-bullet" :style="{ backgroundColor: item.color }"></span>
                <span class="nav-child-title">{{ childText }}</span>
              </div>
            </v-list-item>
          </v-list-group>
        </template>
      </template>
    </v-list>

    <!-- Bottom User Section -->
    <template v-slot:append>
      <v-divider class="mx-3.5"></v-divider>
      <div class="nav-footer" :class="{ 'footer-rail': main.sidebar_mini }">
        <!-- Rail mode: Perfectly centered User avatar with displayed actions & rich popover card -->
        <div v-if="main.sidebar_mini" class="rail-footer-content">
          <!-- Centered User Avatar with Rich Popover Card -->
          <v-menu
            location="end"
            :theme="main.is_dark ? 'dark' : 'light'"
            open-on-hover
            :open-delay="80"
            :close-delay="180"
            offset="14"
            transition="slide-x-transition"
          >
            <template v-slot:activator="{ props: userMenuProps }">
              <div
                v-bind="userMenuProps"
                class="rail-user-avatar cursor-pointer"
                id="rail-user-avatar-btn"
                @click.stop="expandSidebar"
              >
                <div class="user-avatar-wrapper">
                  <v-avatar size="38" class="nav-user-avatar-spacious">
                    <v-icon size="19" color="white">mdi-account</v-icon>
                  </v-avatar>
                  <span class="user-status-online-dot"></span>
                </div>

                <v-tooltip
                  activator="parent"
                  location="end"
                  offset="14"
                  :content-class="main.is_dark ? 'nav-rail-tooltip-dark' : 'nav-rail-tooltip-light'"
                >
                  <div class="rail-tooltip-card">
                    <div class="rail-tooltip-title">{{ auth.user?.name }}</div>
                    <div class="child-label mt-0.5">{{ auth.user?.role?.name || 'Administrator' }}</div>
                  </div>
                </v-tooltip>
              </div>
            </template>

            <!-- Rail User Flyout Menu Panel -->
            <div
              class="rail-flyout-panel rail-user-menu-panel"
              :class="main.is_dark ? 'flyout-dark' : 'flyout-light'"
            >
              <div class="rail-user-menu-header">
                <v-avatar size="42" class="nav-user-avatar-spacious mr-3">
                  <v-icon size="21" color="white">mdi-account</v-icon>
                </v-avatar>
                <div class="rail-user-menu-info">
                  <div class="rail-user-fullname">{{ auth.user?.name }}</div>
                  <div class="rail-user-email">{{ auth.user?.email }}</div>
                  <div class="nav-user-role-badge mt-1">
                    <span class="role-badge-dot"></span>
                    <span class="role-badge-text">{{ auth.user?.role?.name || 'Administrator' }}</span>
                  </div>
                </div>
              </div>

              <v-divider class="my-2"></v-divider>

              <div class="rail-user-menu-actions">
                <v-btn
                  block
                  variant="tonal"
                  color="primary"
                  size="small"
                  prepend-icon="mdi-account-cog-outline"
                  class="mb-1.5 justify-start text-none font-weight-medium rail-menu-action-btn"
                  @click.stop="$router.push({ name: 'profile_settings' })"
                >
                  {{ t('my_profile') }}
                </v-btn>
                <v-btn
                  block
                  variant="tonal"
                  color="error"
                  size="small"
                  prepend-icon="mdi-power"
                  class="justify-start text-none font-weight-medium rail-menu-action-btn"
                  @click.stop="logout()"
                >
                  {{ $t('logout') || 'Logout' }}
                </v-btn>
              </div>
            </div>
          </v-menu>

          <!-- Direct Actions Displayed in Rail Footer -->
          <div class="rail-footer-actions">
            <v-btn
              icon
              size="28"
              variant="tonal"
              color="primary"
              class="rail-mini-action-btn"
              @click.stop="$router.push({ name: 'profile_settings' })"
              id="rail-edit-profile-btn"
            >
              <v-icon size="15">mdi-account-cog-outline</v-icon>
              <v-tooltip activator="parent" location="end" offset="12">{{ t('my_profile') }}</v-tooltip>
            </v-btn>

            <v-btn
              icon
              size="28"
              variant="tonal"
              color="error"
              class="rail-mini-action-btn"
              @click.stop="logout()"
              id="rail-logout-btn"
            >
              <v-icon size="15">mdi-power</v-icon>
              <v-tooltip activator="parent" location="end" offset="12">{{ $t('logout') || 'Logout' }}</v-tooltip>
            </v-btn>
          </div>
        </div>

        <!-- Expanded mode: Spacious, Relief-for-the-eyes Modern User Card -->
        <div v-else class="nav-user-card-spacious">
          <div class="user-avatar-wrapper">
            <v-avatar size="42" class="nav-user-avatar-spacious">
              <v-icon size="21" color="white">mdi-account</v-icon>
            </v-avatar>
            <span class="user-status-online-dot"></span>
          </div>

          <div class="nav-user-info-block">
            <div class="nav-user-fullname" :title="auth.user?.name">
              {{ auth.user?.name }}
            </div>
            <div class="nav-user-role-badge">
              <span class="role-badge-dot"></span>
              <span class="role-badge-text">{{ auth.user?.role?.name || 'Administrator' }}</span>
            </div>
          </div>

          <div class="nav-user-actions-spacious">
            <v-btn
              icon
              size="32"
              variant="tonal"
              color="primary"
              class="user-action-icon-btn profile-action-btn"
              @click.stop="$router.push({ name: 'profile_settings' })"
              id="nav-edit-profile-btn"
            >
              <v-icon size="18">mdi-account-cog-outline</v-icon>
              <v-tooltip activator="parent" location="top">{{ t('my_profile') }}</v-tooltip>
            </v-btn>
            <v-btn
              icon
              size="32"
              variant="tonal"
              color="error"
              class="user-action-icon-btn logout-action-btn-spacious"
              @click.stop="logout()"
            >
              <v-icon size="18">mdi-power</v-icon>
              <v-tooltip activator="parent" location="top">{{ $t('logout') || 'Logout' }}</v-tooltip>
            </v-btn>
          </div>
        </div>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script>
import { useDashboardWaiter } from '@/stores/DashboardWaiter';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useCompanySettings } from '@/stores/CompanySettings';

export default {
  name: 'NavigationDrawer',
  data() {
    return {
      openedGroups: [],
      filterQuery: '',
      adaptiveLogoUrl: null,
      savedScrollTop: 0,
      isTransitioning: false,
      enterTimeout: null,
      leaveTimeout: null,
    };
  },
  computed: {
    dashboard_waiter() { return useDashboardWaiter(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },

    miniLogoUrl() {
      const cs = useCompanySettings();
      if (cs.settings?.logo_invoice_body_url) return cs.settings.logo_invoice_body_url;
      return new URL('/images/jupiter-logo.png', window.location.origin).href;
    },

    themeLogoUrl() {
      return this.adaptiveLogoUrl || this.miniLogoUrl;
    },

    rawSections() {
      const t = this.t;

      return [
        {
          id: 'analytics',
          title: t('core_analytics'),
          items: [
            { id: 'home', icon: 'mdi-home-variant-outline', title: this.$t('home') || 'Home', color: '#1E88E5', url: 'index' },
            { id: 'main_dashboard', icon: 'mdi-chart-box-outline', title: this.$t('main_dashboard'), color: '#1565C0', url: 'main_dashboard' },
            { id: 'jupiter_dashboard', icon: 'mdi-orbit-variant', title: this.$t('jupiter_dashboard'), color: '#1E88E5', url: 'jupiter_dashboard' },
            { id: 'user_statistics', icon: 'mdi-chart-timeline-variant-shimmer', title: this.$t('user_statistics'), color: '#3949AB', url: 'user_statistics', perm: 'user_statistics.view' },
            { id: 'ai_prompt', icon: 'mdi-robot', title: this.$t('ai_prompt'), color: '#42A5F5', url: 'ai_prompt', perm: 'ai_prompt.view' },
          ]
        },
        {
          id: 'operations',
          title: t('operations_logistics'),
          items: [
            {
              id: 'warehouses',
              icon: 'mdi-warehouse',
              title: this.$t('warehouses'),
              color: '#a21ee5',
              children: [
                ['mdi-view-dashboard-outline', this.$t('dashboard'), 'warehouses', 'warehouses.view'],
                ['mdi-receipt-text-outline', this.$t('storage_invoices'), 'storage_invoices', 'warehouses.storage_invoices'],
              ]
            },
            {
              id: 'inventory',
              icon: 'mdi-clipboard-text-clock-outline',
              title: this.$t('inventory'),
              color: '#6D4C41',
              perm: 'inventory.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_inventory'), 'list_inventory'],
                ['mdi-sticker-plus-outline', this.$t('add_inventory'), 'add_inventory'],
              ]
            },
            {
              id: 'charges',
              icon: 'mdi-truck-cargo-container',
              title: this.$t('charges'),
              color: '#42A5F5',
              perm: 'charges.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_charge'), 'list_charge'],
              ]
            },
            {
              id: 'production',
              icon: 'mdi-factory',
              title: this.$t('production'),
              color: '#00897B',
              children: [
                ['mdi-format-list-bulleted', this.$t('production_list'), 'production_list', 'production.view'],
                ['mdi-link-variant', this.$t('charge_production_management'), 'charge_production_management', 'production.charge_management'],
              ]
            },
            {
              id: 'shippings',
              icon: 'mdi-package-variant-closed',
              title: this.$t('shippings'),
              color: '#4643a0',
              perm: 'shippings.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_shipping'), 'list_shipping'],
              ]
            },
            {
              id: 'transfers',
              icon: 'mdi-swap-horizontal',
              title: this.$t('warehouse_transfers'),
              color: '#F9A825',
              perm: 'warehouse_transfers.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_warehouse_transfer'), 'list_warehouse_transfer'],
              ]
            },
          ]
        },
        {
          id: 'workforce',
          title: t('human_resources'),
          items: [
            {
              id: 'workforce_group',
              icon: 'mdi-account-hard-hat',
              title: this.$t('workforce'),
              color: '#1565C0',
              children: [
                ['mdi-account-multiple', this.$t('workforce_management'), 'workforce_management', 'workforce.view'],
                ['mdi-account-tie', this.$t('corporal_management'), 'corporal_management', 'workforce.corporals'],
                ['mdi-account-group', this.$t('team_management'), 'team_management', 'workforce.teams'],
                ['mdi-sitemap-outline', this.$t('section_management'), 'section_management', 'workforce.sections'],
                ['mdi-calendar-month', this.$t('fortnight_management'), 'fortnight_management', 'workforce.fortnight'],
                ['mdi-account-clock-outline', this.$t('workforce_absence_movement_title'), 'workforce_absence_movement', 'workforce.absence'],
                ['mdi-chart-bar', this.$t('workforce_statistics'), 'workforce_statistics', 'workforce.statistics'],
              ]
            }
          ]
        },
        {
          id: 'finance',
          title: t('billing_finance'),
          items: [
            {
              id: 'supplier_invoices',
              icon: 'mdi-file-document-check',
              title: this.$t('supplier_invoices'),
              color: '#E65100',
              children: [
                ['mdi-file-document-multiple', this.$t('list_supplier_invoice'), 'list_supplier_invoice', 'supplier_invoices.view'],
                ['mdi-cash-register', this.$t('supplier_settlement'), 'supplier_settlement', 'supplier_invoices.settlement'],
              ]
            },
            {
              id: 'client_invoices',
              icon: 'mdi-account-cash',
              title: this.$t('client_invoics'),
              color: '#00838F',
              children: [
                ['mdi-file-document-multiple', this.$t('list_client_invoice'), 'list_client_invoice', 'client_invoices.view'],
                ['mdi-cash-register', this.$t('client_settlement'), 'client_settlement', 'client_invoices.settlement'],
              ]
            },
            {
              id: 'order_transfers',
              icon: 'mdi-bank-transfer',
              title: this.$t('order_transfers'),
              color: '#00897B',
              url: 'order_transfers_page',
              perm: 'order_transfers.view'
            }
          ]
        },
        {
          id: 'system',
          title: t('system_administration'),
          items: [
            {
              id: 'parameters',
              icon: 'mdi-cog-outline',
              title: this.$t('parameters'),
              color: '#546E7A',
              children: [
                ['mdi-warehouse', this.$t('warehouse_management'), 'warehouse_management', 'settings.warehouses'],
                ['mdi-fish', this.$t('fish_management'), 'fish_management', 'settings.fish'],
                ['mdi-shape-outline', this.$t('charge_management'), 'charge_management', 'settings.charges'],
                ['mdi-tag-multiple-outline', this.$t('shipping_management'), 'shipping_management', 'settings.shippings'],
                ['mdi-cog-outline', this.$t('production_management'), 'production_management', 'settings.production'],
                ['mdi-clipboard-text-clock-outline', this.$t('detail_inventory_category_management'), 'detail_inventory_category_management', 'settings.inventory_categories'],
                ['mdi-swap-horizontal', this.$t('warehouse_transfer_category_management'), 'warehouse_transfer_category_management', 'settings.transfer_categories'],
                ['mdi-leaf', this.$t('guano_management'), 'guano_management', 'settings.guano'],
                ['mdi-package-variant-closed', this.$t('package_management'), 'package_management', 'settings.packages'],
                ['mdi-bank', this.$t('bank_name_management'), 'bank_name_management', 'settings.banks'],
                ['mdi-domain', this.$t('company_settings'), 'company_settings', 'settings.company'],
                ['mdi-calendar-clock', this.$t('fortnight_settings'), 'fortnight_settings', 'settings.fortnight'],
                ['mdi-chart-areaspline', this.$t('chart_management'), 'chart_management', 'settings.charts'],
                ['mdi-cash-lock-open', this.$t('static_charge_management'), 'static_charge_management', 'settings.static_charges'],
                ['mdi-calculator-variant-outline', this.$t('cost_configuration_management'), 'cost_configuration_management', 'settings.cost_configuration'],
              ]
            },
            {
              id: 'admin_users',
              icon: 'mdi-shield-account',
              title: this.$t('user_management'),
              color: '#5C6BC0',
              url: 'admin_users',
              perm: 'users.manage'
            }
          ]
        }
      ];
    },

    visibleSections() {
      const auth = this.auth;

      return this.rawSections
        .map(section => {
          const filteredItems = section.items
            .map(item => {
              if (item.perm) {
                if (item.perm === 'users.manage' && !auth.isAdmin) return null;
                if (!auth.hasPermission(item.perm)) return null;
              }

              if (item.children) {
                const filteredChildren = item.children.filter(child => {
                  const childPerm = child[3];
                  if (!childPerm) return true;
                  return auth.hasPermission(childPerm);
                });
                if (filteredChildren.length === 0) return null;
                return { ...item, children: filteredChildren };
              }

              return item;
            })
            .filter(Boolean);

          if (filteredItems.length === 0) return null;
          return { ...section, items: filteredItems };
        })
        .filter(Boolean);
    },

    filteredSections() {
      const q = this.filterQuery.trim().toLowerCase();
      if (!q) return this.visibleSections;

      return this.visibleSections
        .map(section => {
          const matchingItems = section.items
            .map(item => {
              const itemMatches = (item.title || '').toLowerCase().includes(q);
              if (item.children) {
                const matchingChildren = item.children.filter(child => (child[1] || '').toLowerCase().includes(q));
                if (matchingChildren.length > 0 || itemMatches) {
                  return { ...item, children: matchingChildren.length > 0 ? matchingChildren : item.children };
                }
                return null;
              }
              return itemMatches ? item : null;
            })
            .filter(Boolean);

          if (matchingItems.length === 0) return null;
          return { ...section, items: matchingItems };
        })
        .filter(Boolean);
    }
  },
  watch: {
    'main.sidebar_mini'(newVal, oldVal) {
      if (newVal === oldVal) return;
      this.isTransitioning = true;
      const targetScroll = this.savedScrollTop;

      const restore = () => {
        const el = this.getContentEl();
        if (el && targetScroll !== undefined) {
          const maxScroll = Math.max(0, el.scrollHeight - el.clientHeight);
          el.scrollTop = Math.min(targetScroll, maxScroll);
        }
      };

      restore();
      this.$nextTick(restore);
      requestAnimationFrame(restore);
      setTimeout(restore, 50);
      setTimeout(restore, 150);
      setTimeout(restore, 320);
      setTimeout(() => {
        restore();
        this.isTransitioning = false;
      }, 360);
    },
    '$route.name': {
      immediate: true,
      handler() {
        this.$nextTick(() => {
          this.findAndOpenActiveGroup();
        });
      }
    },
    filterQuery(newVal) {
      if (newVal && newVal.trim().length > 0) {
        const groupsToOpen = [];
        for (const sec of this.filteredSections) {
          for (const item of sec.items) {
            if (item.children && item.children.length > 0) {
              groupsToOpen.push(item.title);
            }
          }
        }
        this.openedGroups = groupsToOpen;
      }
    },
    miniLogoUrl: {
      immediate: true,
      handler(newVal) {
        this.processLogoForTheme(newVal);
      }
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
        filter_menu: this.lang === 'es' ? 'Filtrar navegación...' : this.lang === 'fr' ? 'Filtrer la navigation...' : 'Filter navigation...',
        collapse_sidebar: this.lang === 'es' ? 'Colapsar barra lateral' : this.lang === 'fr' ? 'Réduire la barre latérale' : 'Collapse sidebar',
        expand_sidebar: this.lang === 'es' ? 'Expandir barra lateral' : this.lang === 'fr' ? 'Agrandir la barre latérale' : 'Expand sidebar',
        home: this.lang === 'es' ? 'Inicio' : this.lang === 'fr' ? 'Accueil' : 'Home',
        my_profile: this.lang === 'es' ? 'Mi Perfil' : this.lang === 'fr' ? 'Mon Profil' : 'My Profile',
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
    getItemStyle(item) {
      const c = item.color || '#1976D2';
      return {
        '--item-accent': c,
        '--item-active-bg': this.hexToRgba(c, 0.12),
        '--item-hover-bg': this.hexToRgba(c, 0.08),
      };
    },
    isRouteActive(routeName) {
      return this.$route.name === routeName;
    },
    isAnyChildActive(item) {
      if (!item.children) return false;
      return item.children.some(c => c[2] === this.$route.name);
    },
    expandSidebar() {
      this.saveScrollPos();
      this.main.sidebar_mini = false;
    },
    minimizeSidebar() {
      this.saveScrollPos();
      this.main.sidebar_mini = true;
    },
    onDrawerClick() {
      if (this.main.sidebar_mini) {
        this.expandSidebar();
      }
    },
    expandAndOpenGroup(groupTitle) {
      this.expandSidebar();
      this.$nextTick(() => {
        if (!this.openedGroups.includes(groupTitle)) {
          this.openedGroups.push(groupTitle);
        }
      });
    },
    isGroupOpened(groupTitle) {
      return Array.isArray(this.openedGroups) && this.openedGroups.includes(groupTitle);
    },
    toggleGroup(groupTitle) {
      if (!Array.isArray(this.openedGroups)) {
        this.openedGroups = [];
      }
      this.saveScrollPos();
      const idx = this.openedGroups.indexOf(groupTitle);
      if (idx > -1) {
        this.openedGroups.splice(idx, 1);
      } else {
        this.openedGroups.push(groupTitle);
      }
    },
    findAndOpenActiveGroup() {
      if (!this.visibleSections) return;
      for (const section of this.visibleSections) {
        for (const item of section.items) {
          if (item.children && this.isAnyChildActive(item)) {
            if (!this.openedGroups.includes(item.title)) {
              this.openedGroups.push(item.title);
            }
          }
        }
      }
    },
    getDrawerEl() {
      if (this.$el && this.$el.nodeType === 1) return this.$el;
      return document.querySelector('.v-navigation-drawer');
    },
    getContentEl() {
      const drawer = this.getDrawerEl();
      if (drawer && drawer.querySelector) {
        return drawer.querySelector('.v-navigation-drawer__content');
      }
      return document.querySelector('.v-navigation-drawer .v-navigation-drawer__content');
    },
    saveScrollPos() {
      const el = this.getContentEl();
      if (el) {
        this.savedScrollTop = el.scrollTop;
      }
    },
    bindScrollListener() {
      const el = this.getContentEl();
      if (el) {
        el.addEventListener('scroll', this.onScroll, { passive: true });
      }
    },
    unbindScrollListener() {
      const el = this.getContentEl();
      if (el) {
        el.removeEventListener('scroll', this.onScroll);
      }
    },
    onScroll(e) {
      if (this.isTransitioning) return;
      this.savedScrollTop = e.target.scrollTop;
    },
    handleDocumentClick(event) {
      if (this.main.sidebar_mini) return;

      const drawerEl = this.getDrawerEl();
      if (!drawerEl) return;

      // Click inside drawer: do not collapse
      if (drawerEl.contains(event.target)) return;

      // Click on system bar nav toggle button: ignore so button handler runs
      const toggleBtn = document.querySelector('.systembar-nav-toggle-btn');
      if (toggleBtn && (toggleBtn === event.target || toggleBtn.contains(event.target))) {
        return;
      }

      // Click inside any teleported overlay/menu/tooltip (e.g. flyout menu, tooltip, dialog)
      if (event.target.closest('.v-overlay-container') || event.target.closest('.rail-flyout-panel') || event.target.closest('.v-tooltip')) {
        return;
      }

      // Outside click: collapse drawer to rail
      this.saveScrollPos();
      this.minimizeSidebar();
    },
    handleMouseEnter() {
      if (this.leaveTimeout) {
        clearTimeout(this.leaveTimeout);
        this.leaveTimeout = null;
      }
      if (this.main.sidebar_mini) {
        this.enterTimeout = setTimeout(() => {
          this.expandSidebar();
        }, 60);
      }
    },
    handleMouseLeave(event) {
      if (this.enterTimeout) {
        clearTimeout(this.enterTimeout);
        this.enterTimeout = null;
      }
      if (!this.main.sidebar_mini) {
        if (event?.relatedTarget) {
          const related = event.relatedTarget;
          const drawer = this.getDrawerEl();
          if (drawer && drawer.contains(related)) return;
          if (related.closest?.('.v-overlay-container') || related.closest?.('.rail-flyout-panel') || related.closest?.('.rail-user-menu-panel')) {
            return;
          }
        }
        this.leaveTimeout = setTimeout(() => {
          const drawer = this.getDrawerEl();
          if (drawer && drawer.matches(':hover')) return;
          this.minimizeSidebar();
        }, 150);
      }
    },
    async logout() {
      this.auth.user = null;
      this.auth.token = null;
      localStorage.removeItem('token');
      this.$router.push({ name: 'login' });
      this.auth.logout().catch(() => {});
    },
    processLogoForTheme(url) {
      const src = url || this.miniLogoUrl;
      if (!src) {
        this.adaptiveLogoUrl = null;
        return;
      }
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = () => {
        try {
          const w = img.naturalWidth || img.width;
          const h = img.naturalHeight || img.height;
          if (!w || !h) {
            this.adaptiveLogoUrl = src;
            return;
          }
          const canvas = document.createElement('canvas');
          canvas.width = w;
          canvas.height = h;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);

          const imgData = ctx.getImageData(0, 0, w, h);
          const d = imgData.data;

          // Sample border pixels to detect solid white background
          const corners = [
            0,
            (w - 1) * 4,
            (h - 1) * w * 4,
            ((h - 1) * w + (w - 1)) * 4,
            (Math.min(5, h - 1) * w + Math.min(5, w - 1)) * 4,
            (Math.min(5, h - 1) * w + Math.max(0, w - 6)) * 4,
            (Math.max(0, h - 6) * w + Math.min(5, w - 1)) * 4,
            (Math.max(0, h - 6) * w + Math.max(0, w - 6)) * 4,
          ];

          let whiteCount = 0;
          for (const idx of corners) {
            const r = d[idx];
            const g = d[idx + 1];
            const b = d[idx + 2];
            const a = d[idx + 3];
            if (a > 200 && r > 235 && g > 235 && b > 235) {
              whiteCount++;
            }
          }

          // If logo has opaque white border background, convert white pixels to transparent
          // so it matches the theme background (dark or light mode) of brand-avatar-squircle
          if (whiteCount >= 4) {
            for (let i = 0; i < d.length; i += 4) {
              const r = d[i];
              const g = d[i + 1];
              const b = d[i + 2];
              const minC = Math.min(r, g, b);
              if (minC >= 248) {
                d[i + 3] = 0;
              } else if (minC >= 220) {
                const factor = (minC - 220) / (248 - 220);
                d[i + 3] = Math.min(d[i + 3], Math.round((1 - factor) * 255));
              }
            }
            ctx.putImageData(imgData, 0, 0);
            this.adaptiveLogoUrl = canvas.toDataURL('image/png');
          } else {
            this.adaptiveLogoUrl = src;
          }
        } catch (e) {
          this.adaptiveLogoUrl = src;
        }
      };
      img.onerror = () => {
        this.adaptiveLogoUrl = src;
      };
      img.src = src;
    }
  },
  mounted() {
    this.main.sidebar_mini = true;
    this.$nextTick(() => {
      this.bindScrollListener();
      this.findAndOpenActiveGroup();
      const drawerEl = this.getDrawerEl();
      if (drawerEl) {
        drawerEl.addEventListener('mouseenter', this.handleMouseEnter);
        drawerEl.addEventListener('mouseleave', this.handleMouseLeave);
      }
    });
    document.addEventListener('click', this.handleDocumentClick, true);
  },
  beforeUnmount() {
    if (this.enterTimeout) clearTimeout(this.enterTimeout);
    if (this.leaveTimeout) clearTimeout(this.leaveTimeout);
    this.unbindScrollListener();
    document.removeEventListener('click', this.handleDocumentClick, true);
    const drawerEl = this.getDrawerEl();
    if (drawerEl) {
      drawerEl.removeEventListener('mouseenter', this.handleMouseEnter);
      drawerEl.removeEventListener('mouseleave', this.handleMouseLeave);
    }
  }
};
</script>

<style>
/* ============================================================
   Teleported Tooltips - Global Classes for Theme-Matching
   ============================================================ */
.nav-rail-tooltip-light {
  background: #FFFFFF !important;
  color: #1E293B !important;
  border: 1px solid rgba(0, 0, 0, 0.12) !important;
  box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.16), 0 4px 10px -2px rgba(0, 0, 0, 0.08) !important;
  border-radius: 12px !important;
  padding: 8px 14px !important;
  opacity: 1 !important;
  font-family: 'Inter', system-ui, sans-serif !important;
}

.nav-rail-tooltip-light .rail-tooltip-title {
  color: #0F172A !important;
  font-weight: 700 !important;
  font-size: 0.85rem !important;
}

.nav-rail-tooltip-light .child-label {
  color: #334155 !important;
  font-size: 0.78rem !important;
  font-weight: 500 !important;
}

.nav-rail-tooltip-dark {
  background: #1E2432 !important;
  color: #F8FAFC !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
  box-shadow: 0 12px 30px -4px rgba(0, 0, 0, 0.6) !important;
  border-radius: 12px !important;
  padding: 8px 14px !important;
  opacity: 1 !important;
  font-family: 'Inter', system-ui, sans-serif !important;
}

.nav-rail-tooltip-dark .rail-tooltip-title {
  color: #FFFFFF !important;
  font-weight: 700 !important;
  font-size: 0.85rem !important;
}

.nav-rail-tooltip-dark .child-label {
  color: #CBD5E1 !important;
  font-size: 0.78rem !important;
  font-weight: 500 !important;
}

.rail-tooltip-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 140px;
}

.rail-tooltip-header {
  display: flex;
  align-items: center;
  gap: 10px !important;
}

.rail-tooltip-icon {
  margin-right: 10px !important;
  flex-shrink: 0;
}

.rail-tooltip-header .v-icon {
  margin-right: 10px !important;
  flex-shrink: 0;
}

.rail-tooltip-title {
  letter-spacing: 0.25px;
}

.rail-tooltip-children {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-top: 4px;
  padding-left: 2px;
  border-top: 1px solid rgba(128, 128, 128, 0.2);
  padding-top: 6px;
}

.rail-tooltip-child {
  display: flex;
  align-items: center;
  gap: 9px;
  padding-left: 2px;
}

.child-bullet-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* ============================================================
   Rail Flyout Menu Panel - Teleported & Theme-Synced
   ============================================================ */
.rail-flyout-panel {
  min-width: 215px;
  max-width: 270px;
  border-radius: 14px;
  padding: 8px;
  overflow: hidden;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  user-select: none;
  font-family: 'Inter', system-ui, sans-serif;
}

.flyout-light {
  background: rgba(255, 255, 255, 0.98) !important;
  color: #0F172A !important;
  border: 1px solid rgba(0, 0, 0, 0.1) !important;
  box-shadow: 0 16px 36px -6px rgba(0, 0, 0, 0.16), 0 4px 12px -2px rgba(0, 0, 0, 0.08) !important;
}

.flyout-dark {
  background: rgba(26, 31, 44, 0.98) !important;
  color: #F8FAFC !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 20px 48px -8px rgba(0, 0, 0, 0.65), 0 6px 16px -2px rgba(0, 0, 0, 0.4) !important;
}

.rail-flyout-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 8px;
  cursor: pointer;
  border-radius: 8px;
  transition: background 0.18s ease;
}
.flyout-light .rail-flyout-header:hover {
  background: rgba(0, 0, 0, 0.04);
}
.flyout-dark .rail-flyout-header:hover {
  background: rgba(255, 255, 255, 0.06);
}

.rail-flyout-icon-pill {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.rail-flyout-title {
  font-weight: 700;
  font-size: 0.86rem;
  letter-spacing: 0.2px;
}
.flyout-light .rail-flyout-title {
  color: #0F172A !important;
}
.flyout-dark .rail-flyout-title {
  color: #FFFFFF !important;
}

.rail-flyout-children {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 4px;
}

.rail-flyout-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.82rem;
  font-weight: 500;
  transition: all 0.18s ease;
}

.flyout-light .rail-flyout-item {
  color: #334155 !important;
}
.flyout-light .rail-flyout-item:hover {
  background: rgba(0, 0, 0, 0.05) !important;
  color: #0F172A !important;
  transform: translateX(2px);
}
.flyout-light .rail-flyout-item.is-active {
  background: var(--item-active-bg, rgba(33, 150, 243, 0.12)) !important;
  color: var(--item-accent, #1976D2) !important;
  font-weight: 600;
}

.flyout-dark .rail-flyout-item {
  color: #CBD5E1 !important;
}
.flyout-dark .rail-flyout-item:hover {
  background: rgba(255, 255, 255, 0.06) !important;
  color: #FFFFFF !important;
  transform: translateX(2px);
}
.flyout-dark .rail-flyout-item.is-active {
  background: var(--item-active-bg, rgba(33, 150, 243, 0.14)) !important;
  color: var(--item-accent, #90CAF9) !important;
  font-weight: 600;
}

.rail-flyout-dot {
  width: 6.5px;
  height: 6.5px;
  border-radius: 50%;
  flex-shrink: 0;
  opacity: 0.7;
  transition: all 0.18s ease;
}
.rail-flyout-item:hover .rail-flyout-dot,
.rail-flyout-item.is-active .rail-flyout-dot {
  opacity: 1;
  transform: scale(1.2);
}

.rail-flyout-item-text {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ============================================================
   Rail User Popover Menu Panel - Teleported & Theme-Synced
   ============================================================ */
.rail-user-menu-panel {
  min-width: 250px;
  max-width: 300px;
  padding: 14px 14px 12px;
}
.rail-user-menu-header {
  display: flex;
  align-items: center;
}
.rail-user-menu-info {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.rail-user-fullname {
  font-size: 0.92rem;
  font-weight: 700;
  line-height: 1.25;
}
.flyout-light .rail-user-fullname {
  color: #0F172A !important;
}
.flyout-dark .rail-user-fullname {
  color: #FFFFFF !important;
}
.rail-user-email {
  font-size: 0.74rem;
  opacity: 0.7;
}
.rail-menu-action-btn {
  border-radius: 9px !important;
}
</style>

<style scoped>
/* ============================================================
   Navigation Drawer - Refined Luxury Sidebar
   ============================================================ */
.nav-drawer-premium {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  user-select: none;
}

/* Explicit width guarantees */
:deep(.v-navigation-drawer:not(.v-navigation-drawer--rail)),
.v-navigation-drawer:not(.v-navigation-drawer--rail) {
  width: 285px !important;
  min-width: 285px !important;
  max-width: 285px !important;
}

:deep(.v-navigation-drawer--rail),
.v-navigation-drawer--rail {
  width: 72px !important;
  min-width: 72px !important;
  max-width: 72px !important;
}

.nav-light {
  background: rgba(255, 255, 255, 0.96) !important;
  border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
  color: #0F172A !important;
}

.nav-light .nav-section-header {
  color: #64748B !important;
}

.nav-light .nav-item-title {
  color: #1E293B !important;
}

.nav-light .nav-child-title {
  color: #334155 !important;
}

.nav-light .nav-list-item:hover {
  background: var(--item-hover-bg, rgba(0, 0, 0, 0.05)) !important;
}

.nav-light .nav-child-item:hover {
  background: rgba(0, 0, 0, 0.04) !important;
}

.nav-light .brand-title {
  color: #0F172A !important;
}

.nav-light .brand-status-text {
  color: #64748B !important;
}

.nav-light :deep(.v-list-group__items) {
  background: rgba(0, 0, 0, 0.025) !important;
  border-left: 2px solid rgba(0, 0, 0, 0.08) !important;
  border-radius: 0 10px 10px 0 !important;
  margin-left: 20px !important;
  margin-right: 6px !important;
  margin-top: 2px !important;
  margin-bottom: 4px !important;
  padding: 4px 6px !important;
}

.nav-light .nav-user-fullname {
  color: #0F172A !important;
}

.nav-light .nav-user-card-spacious {
  background: rgba(0, 0, 0, 0.035) !important;
  border: 1px solid rgba(0, 0, 0, 0.08) !important;
}

.nav-dark {
  background: rgba(22, 26, 36, 0.96) !important;
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
  color: #F8FAFC !important;
}

.nav-dark .nav-section-header {
  color: rgba(255, 255, 255, 0.45) !important;
}

.nav-dark .nav-item-title {
  color: rgba(255, 255, 255, 0.88) !important;
}

.nav-dark .nav-child-title {
  color: rgba(255, 255, 255, 0.82) !important;
}

.nav-dark .nav-list-item:hover {
  background: var(--item-hover-bg, rgba(255, 255, 255, 0.06)) !important;
}

.nav-dark .nav-child-item:hover {
  background: rgba(255, 255, 255, 0.05) !important;
}

.nav-dark .brand-title {
  color: #FFFFFF !important;
}

.nav-dark .brand-status-text {
  color: rgba(255, 255, 255, 0.55) !important;
}

.nav-dark :deep(.v-list-group__items) {
  background: rgba(255, 255, 255, 0.025) !important;
  border-left: 2px solid rgba(255, 255, 255, 0.09) !important;
  border-radius: 0 10px 10px 0 !important;
  margin-left: 20px !important;
  margin-right: 6px !important;
  margin-top: 2px !important;
  margin-bottom: 4px !important;
  padding: 4px 6px !important;
}

.nav-dark .nav-user-fullname {
  color: #F8FAFC !important;
}

.nav-dark .nav-user-card-spacious {
  background: rgba(255, 255, 255, 0.035) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
}

/* Scrollbar styling: hide in rail mode so icons remain 100% symmetrically centered */
.nav-drawer-premium :deep(.v-navigation-drawer__content) {
  overflow-x: hidden !important;
  overflow-y: auto !important;
  scrollbar-width: none !important;
  -ms-overflow-style: none !important;
}
.nav-drawer-premium :deep(.v-navigation-drawer__content)::-webkit-scrollbar {
  width: 0px !important;
  display: none !important;
}

/* Show subtle scrollbar on hover only when expanded */
.nav-drawer-premium:not(.v-navigation-drawer--rail):hover :deep(.v-navigation-drawer__content) {
  scrollbar-width: thin !important;
  scrollbar-color: rgba(128, 128, 128, 0.25) transparent !important;
}
.nav-drawer-premium:not(.v-navigation-drawer--rail):hover :deep(.v-navigation-drawer__content)::-webkit-scrollbar {
  width: 4px !important;
  display: block !important;
}
.nav-drawer-premium:not(.v-navigation-drawer--rail):hover :deep(.v-navigation-drawer__content)::-webkit-scrollbar-thumb {
  background: rgba(128, 128, 128, 0.25);
  border-radius: 4px;
}

/* ============================================================
   Brand Section - Redesigned for Visual Relief and Spacious Elegance
   ============================================================ */
.nav-brand-section {
  padding: 18px 16px 14px;
  min-height: 68px;
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
}

.brand-rail {
  width: 72px !important;
  padding: 14px 0 10px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.nav-rail-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.nav-rail-logo:hover {
  transform: scale(1.08);
}

.brand-avatar-squircle {
  border-radius: 12px !important;
  box-shadow: none !important;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  transition: background-color 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.25s ease;
}

.brand-avatar-dark,
.nav-dark .brand-avatar-squircle {
  background-color: rgba(22, 26, 36, 0.96) !important;
  background: rgba(22, 26, 36, 0.96) !important;
}

.brand-avatar-light,
.nav-light .brand-avatar-squircle {
  background-color: rgba(255, 255, 255, 0.95) !important;
  background: rgba(255, 255, 255, 0.95) !important;
}

.brand-logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: transparent !important;
}

.nav-expanded-brand {
  display: flex;
  align-items: center;
  width: 100%;
  gap: 12px;
}

.brand-clickable-area {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
  transition: opacity 0.2s ease, transform 0.2s ease;
  user-select: none;
}
.brand-clickable-area:hover {
  opacity: 0.9;
  transform: translateX(1px);
}

.brand-text-block {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.brand-title-row {
  display: flex;
  align-items: center;
  gap: 6px;
  line-height: 1.2;
}

.brand-title {
  font-family: 'Inter', system-ui, sans-serif;
  font-weight: 800;
  font-size: 1.12rem;
  letter-spacing: 0.3px;
  color: rgba(var(--v-theme-on-surface), 0.92);
}

.brand-app-pill {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  color: #1976D2;
  background: rgba(33, 150, 243, 0.12);
  padding: 1.5px 6px;
  border-radius: 5px;
}

.brand-subtitle-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 3px;
}

.live-pulse-dot {
  width: 6.5px;
  height: 6.5px;
  border-radius: 50%;
  background-color: #10B981;
  display: inline-block;
  box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
  flex-shrink: 0;
}

.brand-status-text {
  font-size: 0.72rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-collapse-btn {
  border-radius: 8px !important;
  opacity: 0.7;
  transition: all 0.2s ease;
  flex-shrink: 0;
}
.nav-collapse-btn:hover {
  opacity: 1;
  transform: translateX(-1px);
}

/* ---------- Search / Filter Bar ---------- */
.nav-filter-input-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 11px;
  border-radius: 10px;
  border: 1px solid;
  transition: all 0.2s ease;
}

.filter-light {
  background: rgba(0, 0, 0, 0.035);
  border-color: rgba(0, 0, 0, 0.07);
}
.filter-light:focus-within {
  background: rgba(255, 255, 255, 1);
  border-color: rgba(33, 150, 243, 0.45);
  box-shadow: 0 2px 8px rgba(33, 150, 243, 0.12);
}

.filter-dark {
  background: rgba(255, 255, 255, 0.045);
  border-color: rgba(255, 255, 255, 0.08);
}
.filter-dark:focus-within {
  background: rgba(30, 36, 48, 1);
  border-color: rgba(66, 165, 245, 0.45);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
}

.filter-icon {
  opacity: 0.55;
}

.filter-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 0.82rem;
  font-family: inherit;
  color: inherit;
}
.filter-input::placeholder {
  opacity: 0.5;
}

.filter-clear-btn {
  opacity: 0.6;
}

/* ============================================================
   Rail Mode Styles - Symmetrically Centered on 72px Axis
   ============================================================ */
.nav-rail-content {
  width: 72px !important;
  margin: 0 auto !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 4px 0 16px !important;
}

.rail-items-group {
  width: 72px !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  justify-content: center !important;
}

.rail-divider {
  width: 32px;
  height: 1px;
  background: rgba(var(--v-theme-on-surface), 0.08);
  margin: 6px auto;
}

.rail-icon-wrapper {
  width: 72px !important;
  height: 48px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  position: relative !important;
  margin: 1px 0;
}

.rail-icon-btn {
  width: 44px !important;
  min-width: 44px !important;
  max-width: 44px !important;
  height: 44px !important;
  padding: 0 !important;
  margin: 0 auto !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 12px !important;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.rail-icon-btn :deep(.v-list-item__content) {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: 100% !important;
  height: 100% !important;
  padding: 0 !important;
  margin: 0 !important;
}

.rail-icon-btn:hover {
  background: var(--item-hover-bg, rgba(var(--v-theme-on-surface), 0.08)) !important;
  transform: scale(1.08);
}

.rail-icon-btn.is-active,
.rail-icon-btn.v-list-item--active {
  background: var(--item-active-bg, rgba(33, 150, 243, 0.14)) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Symmetrical glowing active border on the left edge of the rail drawer */
.rail-icon-wrapper.has-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 9px;
  bottom: 9px;
  width: 3.5px;
  border-radius: 0 4px 4px 0;
  background-color: var(--item-accent, #1976D2);
  box-shadow: 0 0 8px var(--item-accent, #1976D2);
}

/* ============================================================
   Expanded Mode Styles - Spacious 285px
   ============================================================ */
.nav-list-expanded {
  padding: 4px 12px 18px !important;
}

.nav-section-header {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.9px;
  color: rgba(var(--v-theme-on-surface), 0.45);
  padding: 14px 8px 5px;
}

.nav-list-item {
  margin-bottom: 3px !important;
  min-height: 40px !important;
  border-radius: 10px !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
  position: relative;
}

.nav-list-item:hover {
  background: var(--item-hover-bg, rgba(var(--v-theme-on-surface), 0.06)) !important;
  transform: translateX(2px);
}

.nav-list-item.is-active,
.nav-list-item.v-list-item--active {
  background: var(--item-active-bg, rgba(33, 150, 243, 0.12)) !important;
}

.nav-list-item.is-active::before,
.nav-list-item.v-list-item--active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 8px;
  bottom: 8px;
  width: 3.5px;
  border-radius: 0 4px 4px 0;
  background-color: var(--item-accent, #1976D2);
}

.item-icon-pill {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 11px;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.nav-list-item:hover .item-icon-pill {
  transform: scale(1.08);
}

.nav-item-title {
  font-size: 0.86rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.88);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.nav-list-item.is-active .nav-item-title,
.nav-list-item.v-list-item--active .nav-item-title {
  font-weight: 600;
  color: var(--item-accent, #1976D2);
}

/* Child item indent & styling (proportional spacing, no truncation, high readability) */
:deep(.v-list-group__items) {
  --indent-padding: 0px !important;
}

:deep(.v-list-group__items .v-list-item),
.nav-child-item {
  padding-inline-start: 28px !important;
  padding-inline-end: 12px !important;
  margin-left: 0 !important;
  min-height: 35px !important;
  margin-bottom: 2px !important;
  border-radius: 8px !important;
}

/* Sublink should not have top-level left edge indicator bar */
.nav-child-item::before,
.nav-child-item.is-active::before,
.nav-child-item.v-list-item--active::before {
  display: none !important;
}

:deep(.v-list-group__items .v-list-item__prepend) {
  display: none !important;
}

:deep(.v-list-group__items .v-list-item__content) {
  width: 100% !important;
  min-width: 0 !important;
  overflow: hidden !important;
}

.nav-child-inner {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  min-width: 0;
}

.nav-child-bullet {
  width: 6px;
  height: 6px;
  min-width: 6px;
  min-height: 6px;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  flex-shrink: 0;
  opacity: 0.6;
  background-color: var(--item-accent, #1976D2);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-child-item:hover .nav-child-bullet {
  opacity: 0.9;
}

.nav-child-item.is-active .nav-child-bullet,
.nav-child-item.v-list-item--active .nav-child-bullet {
  width: 7px;
  height: 7px;
  min-width: 7px;
  min-height: 7px;
  opacity: 1;
  background-color: var(--item-accent, #1976D2) !important;
  box-shadow: 0 0 0 3px var(--item-active-bg, rgba(25, 118, 210, 0.15)), 0 0 8px var(--item-accent, #1976D2);
}

.nav-child-title {
  font-size: 0.83rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.82);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  min-width: 0;
}

.nav-child-item.is-active .nav-child-title,
.nav-child-item.v-list-item--active .nav-child-title {
  font-weight: 600;
  color: var(--item-accent, #1976D2);
}

/* ---------- AI Prompt Glowing Conic Border ---------- */
@keyframes ai-border-rotate {
  0%   { --ai-angle: 0deg; }
  100% { --ai-angle: 360deg; }
}
@keyframes ai-glow-pulse {
  0%, 100% { opacity: 0.5; filter: blur(4px); }
  50%      { opacity: 0.9; filter: blur(8px); }
}

.ai-glow-wrapper {
  position: relative;
  border-radius: 12px;
  padding: 2px;
  isolation: isolate;
  background: conic-gradient(
    from var(--ai-angle, 0deg),
    #a855f7, #06b6d4, #ec4899, #3b82f6, #a855f7
  );
  animation: ai-border-rotate 3.5s linear infinite;
}

.ai-glow-wrapper::before {
  content: '';
  position: absolute;
  inset: -1.5px;
  border-radius: 14px;
  background: conic-gradient(
    from var(--ai-angle, 0deg),
    #a855f7, #06b6d4, #ec4899, #3b82f6, #a855f7
  );
  z-index: -1;
  animation: ai-glow-pulse 2.5s ease-in-out infinite;
}

.ai-glow-wrapper :deep(.v-list-item) {
  background: rgb(var(--v-theme-surface)) !important;
  border-radius: 10px;
  position: relative;
  z-index: 1;
}

.ai-glow-rail {
  width: 44px;
  height: 44px;
  margin: 0 auto !important;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ai-glow-rail :deep(.v-list-item) {
  width: 40px !important;
  height: 40px !important;
  min-width: 40px !important;
  padding: 0 !important;
}

/* ---------- Redesigned Spacious Footer User Section ---------- */
.nav-footer {
  padding: 14px 14px 16px;
  display: flex;
  align-items: center;
  transition: all 0.25s ease;
}

.footer-rail {
  width: 72px !important;
  justify-content: center;
  padding: 10px 0;
  margin: 0 auto;
}

.rail-footer-content {
  width: 72px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.rail-footer-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
}

.rail-mini-action-btn {
  border-radius: 8px !important;
  opacity: 0.85;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.rail-mini-action-btn:hover {
  opacity: 1;
  transform: translateY(-1px);
}

.rail-user-avatar {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  cursor: pointer;
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.rail-user-avatar:hover {
  transform: scale(1.08);
}

.user-online-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 9.5px;
  height: 9.5px;
  border-radius: 50%;
  background: #10B981;
  border: 2px solid rgb(var(--v-theme-surface));
}

/* ============================================================
   Rail Subnav Items (Rendered when group is open in rail mode)
   ============================================================ */
.rail-group-block {
  width: 72px;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 1px 0;
  position: relative;
}

.rail-group-parent-btn.is-group-expanded {
  background: var(--item-hover-bg, rgba(var(--v-theme-on-surface), 0.08)) !important;
}

.rail-opened-indicator-dot {
  position: absolute;
  right: 12px;
  top: 12px;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  pointer-events: none;
  box-shadow: 0 0 5px currentColor;
}

.rail-subnav-items {
  width: 72px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 3px 0 5px;
  position: relative;
}

/* Elegant vertical connector tree line */
.rail-subnav-items::before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 8px;
  left: 50%;
  transform: translateX(-50%);
  width: 2px;
  background: var(--item-accent, #1976D2);
  opacity: 0.22;
  border-radius: 2px;
}

.rail-subnav-wrapper {
  width: 72px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 1;
}

.rail-subnav-item {
  width: 36px !important;
  min-width: 36px !important;
  max-width: 36px !important;
  height: 34px !important;
  padding: 0 !important;
  margin: 0 auto !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 10px !important;
  background: rgba(var(--v-theme-surface), 0.75) !important;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08) !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.rail-subnav-item :deep(.v-list-item__content) {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: 100% !important;
  height: 100% !important;
  padding: 0 !important;
  margin: 0 !important;
}

.rail-subnav-item:hover {
  background: var(--item-hover-bg, rgba(var(--v-theme-on-surface), 0.1)) !important;
  border-color: var(--item-accent, #1976D2) !important;
  transform: scale(1.08);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.rail-subnav-item.is-active,
.rail-subnav-item.v-list-item--active {
  background: var(--item-active-bg, rgba(33, 150, 243, 0.16)) !important;
  border-color: var(--item-accent, #1976D2) !important;
  box-shadow: 0 0 10px var(--item-active-bg, rgba(33, 150, 243, 0.25));
}

.rail-subnav-wrapper.has-active::before {
  content: '';
  position: absolute;
  left: 6px;
  top: 10px;
  bottom: 10px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background-color: var(--item-accent, #1976D2);
  box-shadow: 0 0 6px var(--item-accent, #1976D2);
}

/* Subnav transition */
.rail-subnav-enter-active,
.rail-subnav-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.rail-subnav-enter-from,
.rail-subnav-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.9);
}

.nav-user-card-spacious {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 10px 12px;
  border-radius: 14px;
  background: rgba(var(--v-theme-on-surface), 0.035);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  gap: 12px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-user-card-spacious:hover {
  background: rgba(var(--v-theme-on-surface), 0.055);
  border-color: rgba(var(--v-theme-primary), 0.2);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
}

.user-avatar-wrapper {
  position: relative;
  flex-shrink: 0;
  display: inline-flex;
}

.nav-user-avatar-spacious {
  background: linear-gradient(135deg, #1976D2, #42A5F5) !important;
  box-shadow: 0 3px 10px rgba(25, 118, 210, 0.35);
  transition: transform 0.2s ease;
}

.user-status-online-dot {
  position: absolute;
  bottom: 0px;
  right: 0px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #10B981;
  border: 2px solid rgb(var(--v-theme-surface));
  box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
}

.nav-user-info-block {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
  overflow: hidden;
}

.nav-user-fullname {
  font-size: 0.88rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: rgba(var(--v-theme-on-surface), 0.94);
  line-height: 1.25;
}

.nav-user-role-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 1.5px 7px;
  border-radius: 6px;
  background: rgba(25, 118, 210, 0.09);
  align-self: flex-start;
  max-width: 100%;
}

.role-badge-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #1976D2;
  flex-shrink: 0;
}

.role-badge-text {
  font-size: 0.70rem;
  font-weight: 600;
  color: #1976D2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-user-actions-spacious {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.user-action-icon-btn {
  border-radius: 9px !important;
  transition: all 0.2s ease !important;
}

.profile-action-btn {
  opacity: 0.85;
}
.profile-action-btn:hover {
  opacity: 1;
  transform: translateY(-1px);
}

.logout-action-btn-spacious {
  opacity: 0.85;
}
.logout-action-btn-spacious:hover {
  opacity: 1;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(244, 67, 54, 0.25);
}

@property --ai-angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}
</style>