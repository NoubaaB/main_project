<template>
  <v-navigation-drawer
    ref="drawerRoot"
    permanent
    :scrim="false"
    :disable-resize-watcher="true"
    :rail="main.sidebar_mini"
    :rail-width="74"
    :width="286"
    :theme="main.is_dark ? 'dark' : 'light'"
    :class="[
      'futuristic-nav-drawer',
      main.is_dark ? 'cyber-dark-theme' : 'cyber-light-theme',
      main.sidebar_mini ? 'drawer-is-rail' : 'drawer-is-expanded'
    ]"
    @click="handleDrawerClick"
  >
    <!-- ============================================================
         1. BRAND / SYSTEM HEADER
         Displays Jupiter system image avatar if DB has no custom logo
         ============================================================ -->
    <div class="cyber-brand-header" :class="{ 'brand-rail': main.sidebar_mini }">
      <div
        class="brand-clickable-container"
        @click.stop="handleBrandClick"
        :title="main.sidebar_mini ? t('expand_sidebar') : t('home')"
      >
        <!-- Orbit Glow Avatar Container -->
        <div class="brand-avatar-halo-wrap">
          <div class="brand-avatar-glow-ring"></div>
          <v-avatar size="44" class="brand-avatar-orb">
            <img
              :src="displayAvatarUrl"
              alt="Jupiter"
              class="brand-avatar-img"
              @error="onAvatarError"
            />
          </v-avatar>
          <span
            class="brand-online-orb-dot"
            :class="isServerConnected ? 'status-green' : 'status-cut'"
            :title="isServerConnected ? t('server_connected') : t('connection_cut')"
          ></span>
        </div>

        <!-- Expanded Brand Identity Text -->
        <transition name="fade-slide">
          <div v-show="!main.sidebar_mini" class="brand-details-block">
            <div class="brand-title-row">
              <span class="brand-name-glitch">Jupiter</span>
              <span class="cyber-badge-pill">ERP</span>
            </div>
            <div class="brand-sub-row">
              <span
                class="brand-live-pulse-dot"
                :class="isServerConnected ? 'pulse-live' : 'pulse-severed'"
              ></span>
              <span class="brand-live-caption">
                {{ isServerConnected ? (t('active') + ' • Cloud') : t('connection_cut') }}
              </span>
            </div>
          </div>
        </transition>

        <!-- Rail Tooltip -->
        <v-tooltip
          v-if="main.sidebar_mini"
          activator="parent"
          location="end"
          offset="14"
          :content-class="main.is_dark ? 'cyber-tooltip-dark' : 'cyber-tooltip-light'"
        >
          <div class="d-flex align-center ga-2">
            <v-icon size="16" color="#42A5F5">mdi-orbit-variant</v-icon>
            <span class="font-weight-bold">Jupiter ERP</span>
            <span class="text-caption text-medium-emphasis">({{ t('expand_sidebar') }})</span>
          </div>
        </v-tooltip>
      </div>
    </div>

    <!-- ============================================================
         2. NAVIGATION SEARCH FILTER (Matches AddCharge Input Style)
         ============================================================ -->
    <transition name="fade-slide">
      <div v-show="!main.sidebar_mini" class="cyber-search-wrapper px-3.5 mb-2">
        <v-text-field
          v-model="filterQuery"
          :placeholder="t('filter_menu')"
          prepend-inner-icon="mdi-magnify"
          density="compact"
          variant="outlined"
          rounded
          color="#42A5F5"
          clearable
          hide-details
          class="nav-cyber-search-field"
        ></v-text-field>
      </div>
    </transition>

    <div class="cyber-header-divider mx-3.5 mb-2"></div>

    <!-- ============================================================
         3. UNIFIED FUTURISTIC NAVIGATION LIST
         ============================================================ -->
    <div class="nav-cyber-list" @click="handleNavListClick">
      <template v-for="(section, sIdx) in filteredSections" :key="'sec-' + sIdx">
        <!-- Section Header Label or Rail Micro Divider -->
        <div class="cyber-section-header" :class="{ 'section-rail': main.sidebar_mini }">
          <span v-if="!main.sidebar_mini" class="cyber-section-title">{{ section.title }}</span>
          <div v-else-if="sIdx > 0" class="rail-micro-divider"></div>
        </div>

        <template v-for="item in section.items" :key="'item-' + item.id">
          <!-- ─── 3A. AI PROMPT ITEM WITH COLORED GLOWING BORDER ─── -->
          <div
            v-if="item.url === 'ai_prompt'"
            class="ai-glowing-nav-card my-1.5"
            :class="{
              'ai-rail-mode': main.sidebar_mini,
              'ai-is-active': isRouteActive(item.url)
            }"
            :style="getItemAccentStyle(item)"
          >
            <!-- Expanded AI Item -->
            <div
              v-if="!main.sidebar_mini"
              class="nav-cyber-item ai-item-inner cursor-pointer"
              :class="{ 'route-active': isRouteActive(item.url) }"
              @click.stop="navigateTo(item.url)"
            >
              <div class="cyber-icon-pill ai-icon-glow">
                <v-icon size="19" :color="item.color">{{ item.icon || 'mdi-robot' }}</v-icon>
              </div>
              <span class="nav-item-label font-weight-bold ai-gradient-text">
                {{ item.title }}
              </span>
              <span class="ai-spark-chip ml-auto">
                <v-icon size="11" class="mr-0.5">mdi-sparkles</v-icon>AI
              </span>
            </div>

            <!-- Rail Mode AI Item with Hover Popover -->
            <v-menu
              v-else
              location="end center"
              offset="14"
              open-on-hover
              :open-delay="80"
              :close-delay="160"
              transition="slide-x-transition"
              :theme="main.is_dark ? 'dark' : 'light'"
            >
              <template v-slot:activator="{ props: aiMenuProps }">
                <div
                  v-bind="aiMenuProps"
                  class="rail-icon-wrapper ai-rail-activator cursor-pointer"
                  :class="{ 'route-active': isRouteActive(item.url) }"
                  @click.stop="handleRailAiClick(item.url)"
                >
                  <div class="cyber-icon-pill ai-icon-glow">
                    <v-icon size="20" :color="item.color">{{ item.icon || 'mdi-robot' }}</v-icon>
                  </div>
                  <span class="ai-rail-spark-dot"></span>
                </div>
              </template>

              <!-- Popover for AI Prompt in Rail Mode -->
              <div
                class="cyber-rail-popover ai-popover-card cursor-pointer"
                :class="main.is_dark ? 'popover-dark' : 'popover-light'"
                @click.stop="navigateTo(item.url)"
              >
                <div class="popover-hero-header ai-header-gradient">
                  <div class="popover-hero-icon-box mr-2.5">
                    <v-icon size="17" color="white">mdi-robot</v-icon>
                  </div>
                  <span class="popover-hero-title">{{ item.title }}</span>
                  <span class="popover-ai-pill ml-auto">PROMPT</span>
                </div>
                <div class="popover-body-content pa-3">
                  <div class="popover-caption font-weight-medium d-flex align-center justify-space-between">
                    <span>{{ t('click_to_open') || 'Click to launch AI Prompt assistant' }}</span>
                    <v-icon size="15" color="#42A5F5" class="ml-2">mdi-arrow-right</v-icon>
                  </div>
                </div>
              </div>
            </v-menu>
          </div>

          <!-- ─── 3B. SINGLE LEAF NAV LINK (No Children) ─── -->
          <template v-else-if="!item.children || item.children.length === 0">
            <!-- Expanded Leaf Link -->
            <div
              v-if="!main.sidebar_mini"
              class="nav-cyber-item mb-1 cursor-pointer"
              :class="{ 'route-active': isRouteActive(item.url) }"
              :style="getItemAccentStyle(item)"
              @click.stop="item.url && navigateTo(item.url)"
            >
              <div
                class="cyber-icon-pill"
                :style="{ backgroundColor: hexToRgba(item.color, 0.12) }"
              >
                <v-icon size="18" :color="item.color">{{ item.icon }}</v-icon>
              </div>
              <span class="nav-item-label">
                {{ item.title }}
              </span>
            </div>

            <!-- Rail Leaf Link with Hover Popover Card -->
            <v-menu
              v-else
              location="end center"
              offset="14"
              open-on-hover
              :open-delay="70"
              :close-delay="150"
              transition="slide-x-transition"
              :theme="main.is_dark ? 'dark' : 'light'"
            >
              <template v-slot:activator="{ props: leafMenuProps }">
                <div
                  v-bind="leafMenuProps"
                  class="rail-icon-wrapper mb-1 cursor-pointer"
                  :class="{ 'route-active': isRouteActive(item.url) }"
                  :style="getItemAccentStyle(item)"
                  @click.stop="handleRailLeafClick(item.url)"
                >
                  <div
                    class="cyber-icon-pill"
                    :style="{ backgroundColor: hexToRgba(item.color, 0.13) }"
                  >
                    <v-icon size="19" :color="item.color">{{ item.icon }}</v-icon>
                  </div>
                  <span
                    v-if="isRouteActive(item.url)"
                    class="rail-active-glow-bar"
                    :style="{ backgroundColor: item.color }"
                  ></span>
                </div>
              </template>

              <!-- Rail Popover for Leaf Item -->
              <div
                class="cyber-rail-popover leaf-popover-card cursor-pointer"
                :class="main.is_dark ? 'popover-dark' : 'popover-light'"
                @click.stop="item.url && navigateTo(item.url)"
              >
                <div class="d-flex align-center ga-2.5 pa-3">
                  <div
                    class="cyber-icon-pill mini-pill"
                    :style="{ backgroundColor: hexToRgba(item.color, 0.16) }"
                  >
                    <v-icon size="17" :color="item.color">{{ item.icon }}</v-icon>
                  </div>
                  <span class="popover-leaf-title font-weight-bold">{{ item.title }}</span>
                  <v-icon size="14" class="ml-auto text-medium-emphasis leaf-arrow">mdi-arrow-right</v-icon>
                </div>
              </div>
            </v-menu>
          </template>

          <!-- ─── 3C. ACCORDION GROUP WITH SUB-NAVS ─── -->
          <template v-else>
            <!-- 1) EXPANDED DRAWER: Custom Accordion Group (Immune to unmount state loss) -->
            <div
              v-if="!main.sidebar_mini"
              class="cyber-group-block mb-1"
              :class="{
                'group-is-expanded': isGroupExpanded(item.id),
                'route-active': isAnyChildActive(item)
              }"
              :style="getItemAccentStyle(item)"
            >
              <!-- Group Activator Header -->
              <div
                class="nav-cyber-item nav-cyber-parent"
                :class="{
                  'route-active': isAnyChildActive(item),
                  'group-is-open': isGroupExpanded(item.id)
                }"
                @click.stop="toggleGroup(item.id)"
              >
                <div
                  class="cyber-icon-pill"
                  :style="{ backgroundColor: hexToRgba(item.color, 0.12) }"
                >
                  <v-icon size="18" :color="item.color">{{ item.icon }}</v-icon>
                </div>

                <span class="nav-item-label">
                  {{ item.title }}
                </span>

                <v-chip
                  size="x-small"
                  variant="tonal"
                  :color="item.color"
                  class="mr-2 group-count-chip font-weight-bold"
                >
                  {{ item.children.length }}
                </v-chip>

                <v-icon
                  size="16"
                  class="group-chevron-icon"
                  :class="{ 'chevron-rotated': isGroupExpanded(item.id) }"
                >
                  mdi-chevron-down
                </v-icon>
              </div>

              <!-- Expanded Sub-Nav Items List with smooth animation -->
              <v-expand-transition>
                <div
                  v-show="isGroupExpanded(item.id)"
                  class="nav-group-children-wrap"
                >
                  <div
                    v-for="(child, cIdx) in item.children"
                    :key="'child-' + cIdx"
                    class="nav-cyber-item nav-child-subitem mb-0.5 cursor-pointer"
                    :class="{ 'route-active': isRouteActive(child[2]) }"
                    :style="getChildAccentStyle(child, item)"
                    @click.stop="navigateTo(child[2])"
                  >
                    <div
                      class="cyber-child-icon-box"
                      :style="{ backgroundColor: hexToRgba(getChildColor(child, item), 0.11) }"
                    >
                      <v-icon size="16" :color="getChildColor(child, item)">
                        {{ child[0] || 'mdi-circle-small' }}
                      </v-icon>
                    </div>
                    <span class="nav-child-label">
                      {{ child[1] }}
                    </span>
                  </div>
                </div>
              </v-expand-transition>
            </div>

            <!-- 2) COLLAPSED RAIL MODE:
                 - When group was expanded: displays parent icon + small mini icons of expanded sub-navs!
                 - Hover popover on rail icon displays interactive list of all sub-navs! -->
            <div
              v-else
              class="rail-group-container mb-1"
              :class="{
                'rail-group-expanded': isGroupExpanded(item.id),
                'route-active': isAnyChildActive(item)
              }"
              :style="getItemAccentStyle(item)"
            >
              <!-- Popover on Hover: Full Interactive Sub-Navs List for Rail Mode -->
              <v-menu
                v-model="groupPopovers[item.id]"
                location="end top"
                offset="14"
                open-on-hover
                :open-delay="80"
                :close-delay="200"
                transition="slide-x-transition"
                :theme="main.is_dark ? 'dark' : 'light'"
                :close-on-content-click="false"
              >
                <!-- Rail Activator: Parent Group Icon (click toggles mini expanded state) -->
                <template v-slot:activator="{ props: groupMenuProps }">
                  <div
                    v-bind="groupMenuProps"
                    class="rail-icon-wrapper rail-group-parent cursor-pointer"
                    :class="{ 'is-active': isAnyChildActive(item) }"
                    @click.stop="handleRailGroupClick(item.id)"
                  >
                    <div
                      class="cyber-icon-pill"
                      :style="{ backgroundColor: hexToRgba(item.color, 0.13) }"
                    >
                      <v-icon size="19" :color="item.color">{{ item.icon }}</v-icon>
                    </div>

                    <!-- Indicator dots or active line -->
                    <span
                      v-if="isAnyChildActive(item)"
                      class="rail-active-glow-bar"
                      :style="{ backgroundColor: item.color }"
                    ></span>

                    <span
                      v-if="isGroupExpanded(item.id)"
                      class="rail-group-expanded-badge"
                      :style="{ backgroundColor: item.color }"
                    ></span>
                  </div>
                </template>

                <!-- Interactive Popover Menu of Sub-Nav Links -->
                <div
                  class="cyber-rail-popover group-popover-card"
                  :class="main.is_dark ? 'popover-dark' : 'popover-light'"
                  @click.stop=""
                >
                  <!-- Popover Top Gradient Header -->
                  <div
                    class="popover-hero-header"
                    :style="{
                      background: getGroupHeaderGradient(item)
                    }"
                  >
                    <div class="popover-hero-icon-box mr-2.5">
                      <v-icon size="17" color="white">{{ item.icon }}</v-icon>
                    </div>
                    <span class="popover-hero-title">{{ item.title }}</span>
                    <span class="popover-count-chip ml-auto">{{ item.children.length }}</span>
                  </div>

                  <!-- Sub-Nav Items List (Clickable!) with clean margins between elements -->
                  <div class="popover-children-scroll">
                    <div
                      v-for="(child, scIdx) in item.children"
                      :key="'pop-child-' + scIdx"
                      class="popover-child-row cursor-pointer"
                      :class="{ 'is-child-active': isRouteActive(child[2]) }"
                      @click.stop="onPopoverChildClick(item.id, child[2])"
                    >
                      <div
                        class="popover-child-icon-box"
                        :style="{ backgroundColor: hexToRgba(getChildColor(child, item), 0.14) }"
                      >
                        <v-icon size="15" :color="getChildColor(child, item)">
                          {{ child[0] || 'mdi-circle-small' }}
                        </v-icon>
                      </div>
                      <span class="popover-child-text">{{ child[1] }}</span>
                      <v-icon
                        v-if="isRouteActive(child[2])"
                        size="13"
                        :color="getChildColor(child, item)"
                        class="ml-auto flex-shrink-0"
                      >mdi-check-circle</v-icon>
                      <v-icon
                        v-else
                        size="14"
                        class="ml-auto flex-shrink-0 popover-arrow-icon"
                      >mdi-chevron-right</v-icon>
                    </div>
                  </div>
                </div>
              </v-menu>

              <!-- Expanded Sub-Navs Mini Icons Column in Rail Mode
                   Displays small icons of sub-navs that were expanded before collapse -->
              <div
                v-if="isGroupExpanded(item.id)"
                class="rail-mini-subnavs-col"
              >
                <div
                  v-for="(child, msIdx) in item.children"
                  :key="'rail-sub-' + msIdx"
                  class="rail-mini-sub-item cursor-pointer"
                  :class="{ 'mini-sub-active': isRouteActive(child[2]) }"
                  @click.stop="handleRailSubItemClick(child[2])"
                >
                  <div
                    class="rail-mini-sub-icon-box"
                    :style="{
                      backgroundColor: hexToRgba(getChildColor(child, item), isRouteActive(child[2]) ? 0.25 : 0.12),
                      borderColor: isRouteActive(child[2]) ? getChildColor(child, item) : 'transparent'
                    }"
                  >
                    <v-icon size="14" :color="getChildColor(child, item)">
                      {{ child[0] || 'mdi-circle-small' }}
                    </v-icon>
                  </div>

                  <!-- Individual Sub-Nav Tooltip in Rail Mode -->
                  <v-tooltip
                    activator="parent"
                    location="end"
                    offset="12"
                    :content-class="main.is_dark ? 'cyber-tooltip-dark' : 'cyber-tooltip-light'"
                  >
                    <div class="d-flex align-center ga-1.5">
                      <v-icon size="14" :color="getChildColor(child, item)">{{ child[0] || 'mdi-circle-small' }}</v-icon>
                      <span class="font-weight-medium">{{ child[1] }}</span>
                    </div>
                  </v-tooltip>
                </div>
              </div>
            </div>
          </template>
        </template>
      </template>
    </div>

    <!-- ============================================================
         4. FOOTER: USER INFORMATION, PROFILE & SERVER STATUS
         - When Opened: User info, logout button, edit profile icon button
         - When Collapsed: User icon + status (online or server connection cut)
         ============================================================ -->
    <template v-slot:append>
      <div class="cyber-footer-divider mx-3.5 mb-2"></div>
      <div class="cyber-nav-footer" :class="{ 'footer-is-rail': main.sidebar_mini }">
        <!-- 4A. RAIL FOOTER: User Icon with Online/Cut Status + Popover -->
        <div v-if="main.sidebar_mini" class="rail-footer-wrap">
          <v-menu
            v-model="userMenuOpen"
            location="end bottom"
            offset="14"
            open-on-hover
            :open-delay="80"
            :close-delay="200"
            transition="slide-x-transition"
            :theme="main.is_dark ? 'dark' : 'light'"
            :close-on-content-click="false"
          >
            <template v-slot:activator="{ props: railUserProps }">
              <div
                v-bind="railUserProps"
                class="rail-user-avatar-btn cursor-pointer"
                id="rail-user-avatar-btn"
                @click.stop="main.sidebar_mini = false"
              >
                <div class="rail-user-avatar-inner">
                  <v-avatar size="40" class="rail-avatar-orb">
                    <v-icon size="20" color="white">mdi-account</v-icon>
                  </v-avatar>
                  <span
                    class="rail-status-badge-dot"
                    :class="isServerConnected ? 'status-green' : 'status-cut'"
                    :title="isServerConnected ? t('server_connected') : t('connection_cut')"
                  ></span>
                </div>
              </div>
            </template>

            <!-- Luxury User Popover Card in Rail Mode -->
            <div
              class="cyber-rail-popover user-popover-card"
              :class="main.is_dark ? 'popover-dark' : 'popover-light'"
              @click.stop=""
            >
              <!-- Top Banner with Live Status Pill -->
              <div class="user-popover-banner">
                <div class="user-status-pill" :class="isServerConnected ? 'pill-connected' : 'pill-severed'">
                  <span class="user-status-orb"></span>
                  <span>{{ isServerConnected ? t('server_connected') : t('connection_cut') }}</span>
                </div>
              </div>

              <!-- User Identity Section with generous margins -->
              <div class="user-popover-identity pa-3.5">
                <div class="d-flex align-center ga-3 mb-3">
                  <v-avatar size="44" class="popover-avatar">
                    <v-icon size="22" color="white">mdi-account</v-icon>
                  </v-avatar>
                  <div class="user-identity-text">
                    <div class="user-name-text font-weight-bold" :title="auth.user?.name">
                      {{ auth.user?.name }}
                    </div>
                    <div class="user-email-text text-caption" :title="auth.user?.email">
                      <v-icon size="11" class="mr-1">mdi-email-outline</v-icon>
                      {{ auth.user?.email }}
                    </div>
                  </div>
                </div>

                <div class="user-role-badge-row mb-3">
                  <div class="user-role-chip">
                    <v-icon size="13" color="#42A5F5" class="mr-1">mdi-shield-check</v-icon>
                    <span>{{ auth.user?.role?.name || t('administrator') }}</span>
                  </div>
                </div>

                <v-divider class="mb-3"></v-divider>

                <!-- Quick Action Buttons inside Popover with clear margins between elements -->
                <div class="user-popover-actions">
                  <button
                    type="button"
                    class="user-popover-action-row cursor-pointer"
                    @click.stop="navigateTo('profile_settings')"
                  >
                    <div class="user-action-icon-box action-profile mr-2.5">
                      <v-icon size="16" color="#42A5F5">mdi-account-cog-outline</v-icon>
                    </div>
                    <span>{{ t('my_profile') }}</span>
                    <v-icon size="14" class="ml-auto text-medium-emphasis action-arrow">mdi-chevron-right</v-icon>
                  </button>

                  <button
                    v-if="auth.isAdmin"
                    type="button"
                    class="user-popover-action-row cursor-pointer"
                    @click.stop="navigateTo('admin_users')"
                  >
                    <div class="user-action-icon-box action-admin mr-2.5">
                      <v-icon size="16" color="#5C6BC0">mdi-shield-account</v-icon>
                    </div>
                    <span>{{ t('user_management') || 'User Management' }}</span>
                    <v-icon size="14" class="ml-auto text-medium-emphasis action-arrow">mdi-chevron-right</v-icon>
                  </button>

                  <button
                    type="button"
                    class="user-popover-action-row logout-row cursor-pointer"
                    @click.stop="logout()"
                  >
                    <div class="user-action-icon-box action-logout mr-2.5">
                      <v-icon size="16" color="#EF5350">mdi-power</v-icon>
                    </div>
                    <span class="text-error font-weight-semibold">{{ $t('logout') || 'Logout' }}</span>
                    <v-icon size="14" color="#EF5350" class="ml-auto action-arrow">mdi-chevron-right</v-icon>
                  </button>
                </div>
              </div>
            </div>
          </v-menu>
        </div>

        <!-- 4B. EXPANDED FOOTER: Full User Info, Status, Edit Profile & Logout Buttons -->
        <div v-else class="expanded-footer-card pa-3">
          <div class="d-flex align-center justify-space-between ga-2">
            <!-- Left: Avatar + Identity -->
            <div class="d-flex align-center ga-2.5 overflow-hidden">
              <div class="footer-avatar-wrap">
                <v-avatar size="40" class="footer-avatar-orb">
                  <v-icon size="20" color="white">mdi-account</v-icon>
                </v-avatar>
                <span
                  class="footer-status-dot"
                  :class="isServerConnected ? 'status-green' : 'status-cut'"
                  :title="isServerConnected ? t('server_connected') : t('connection_cut')"
                ></span>
              </div>

              <div class="footer-user-details overflow-hidden">
                <div class="footer-user-name font-weight-bold text-truncate" :title="auth.user?.name">
                  {{ auth.user?.name }}
                </div>
                <div class="footer-user-role-line d-flex align-center ga-1">
                  <span
                    class="footer-live-micro-dot"
                    :class="isServerConnected ? 'dot-green' : 'dot-red'"
                  ></span>
                  <span class="footer-role-text text-truncate">
                    {{ auth.user?.role?.name || 'Administrator' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Right: Action Buttons (Edit Profile + Logout) -->
            <div class="footer-actions-cluster d-flex align-center ga-1">
              <!-- Edit Profile Icon Button -->
              <v-btn
                icon
                size="32"
                variant="tonal"
                color="#42A5F5"
                class="footer-btn edit-profile-btn"
                id="footer-edit-profile-btn"
                @click.stop="navigateTo('profile_settings')"
              >
                <v-icon size="17">mdi-account-cog-outline</v-icon>
                <v-tooltip activator="parent" location="top">
                  {{ t('my_profile') }}
                </v-tooltip>
              </v-btn>

              <!-- Logout Icon Button -->
              <v-btn
                icon
                size="32"
                variant="tonal"
                color="error"
                class="footer-btn logout-btn"
                id="footer-logout-btn"
                @click.stop="logout()"
              >
                <v-icon size="17">mdi-power</v-icon>
                <v-tooltip activator="parent" location="top">
                  {{ $t('logout') || 'Logout' }}
                </v-tooltip>
              </v-btn>
            </div>
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
import { useOnlineUsers } from '@/stores/OnlineUsers';

export default {
  name: 'NavigationDrawer',
  data() {
    return {
      persistedExpandedGroups: ['warehouses', 'workforce'],
      filterQuery: '',
      userMenuOpen: false,
      groupPopovers: {},
      adaptiveLogoUrl: null,
      avatarLoadFailed: false,
    };
  },
  computed: {
    dashboard_waiter() { return useDashboardWaiter(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    online() { return useOnlineUsers(); },
    lang() { return this.main.lang?.code || 'en'; },

    /**
     * Real-time server connection state
     */
    isServerConnected() {
      if (this.online && typeof this.online.isConnected !== 'undefined') {
        return !!this.online.isConnected;
      }
      return typeof window !== 'undefined' && !!window.Echo;
    },

    /**
     * Display Avatar URL (prefers transparent canvas processed version)
     */
    displayAvatarUrl() {
      return this.adaptiveLogoUrl || this.systemAvatarUrl;
    },

    /**
     * System Jupiter Avatar or DB uploaded company logo
     */
    systemAvatarUrl() {
      if (this.avatarLoadFailed) {
        return '/images/jupiter-logo.png';
      }
      const cs = useCompanySettings();
      if (cs.settings?.logo_invoice_body_url) return cs.settings.logo_invoice_body_url;
      if (cs.settings?.logo_url) return cs.settings.logo_url;
      return '/images/jupiter-logo.png';
    },

    /**
     * Navigation Sections definition respecting claude.md Module Colors
     */
    rawSections() {
      const t = this.t;

      return [
        {
          id: 'analytics',
          title: t('core_analytics'),
          items: [
            {
              id: 'home',
              icon: 'mdi-home-variant-outline',
              title: this.$t('home') || 'Home',
              color: '#1E88E5',
              url: 'index'
            },
            {
              id: 'main_dashboard',
              icon: 'mdi-chart-box-outline',
              title: this.$t('main_dashboard'),
              color: '#1565C0',
              url: 'main_dashboard'
            },
            {
              id: 'jupiter_dashboard',
              icon: 'mdi-orbit-variant',
              title: this.$t('jupiter_dashboard'),
              color: '#0288D1',
              url: 'jupiter_dashboard'
            },
            {
              id: 'user_statistics',
              icon: 'mdi-chart-timeline-variant-shimmer',
              title: this.$t('user_statistics'),
              color: '#3949AB',
              url: 'user_statistics',
              perm: 'user_statistics.view'
            },
            {
              id: 'ai_prompt',
              icon: 'mdi-robot',
              title: this.$t('ai_prompt'),
              color: '#42A5F5',
              url: 'ai_prompt',
              perm: 'ai_prompt.view'
            },
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
              color: '#a21ee5', // Warehouse module button color
              children: [
                ['mdi-view-dashboard-outline', this.$t('dashboard'), 'warehouses', 'warehouses.view', '#a21ee5'],
                ['mdi-receipt-text-outline', this.$t('storage_invoices'), 'storage_invoices', 'warehouses.storage_invoices', '#9742f5'],
              ]
            },
            {
              id: 'inventory',
              icon: 'mdi-clipboard-text-clock-outline',
              title: this.$t('inventory'),
              color: '#6D4C41', // Inventory module button color
              perm: 'inventory.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_inventory'), 'list_inventory', null, '#6D4C41'],
                ['mdi-sticker-plus-outline', this.$t('add_inventory'), 'add_inventory', null, '#8D6E63'],
              ]
            },
            {
              id: 'charges',
              icon: 'mdi-truck-cargo-container',
              title: this.$t('charges'),
              color: '#42A5F5', // Charge module button color
              perm: 'charges.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_charge'), 'list_charge', null, '#42A5F5'],
              ]
            },
            {
              id: 'production',
              icon: 'mdi-factory',
              title: this.$t('production'),
              color: '#00897B', // Production module button color
              children: [
                ['mdi-format-list-bulleted', this.$t('production_list'), 'production_list', 'production.view', '#00897B'],
                ['mdi-link-variant', this.$t('charge_production_management'), 'charge_production_management', 'production.charge_management', '#26A69A'],
              ]
            },
            {
              id: 'shippings',
              icon: 'mdi-package-variant-closed',
              title: this.$t('shippings'),
              color: '#4643a0', // Shipping module button color
              perm: 'shippings.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_shipping'), 'list_shipping', null, '#4643a0'],
              ]
            },
            {
              id: 'transfers',
              icon: 'mdi-swap-horizontal',
              title: this.$t('warehouse_transfers'),
              color: '#F9A825', // Warehouse Transfer button color
              perm: 'warehouse_transfers.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_warehouse_transfer'), 'list_warehouse_transfer', null, '#F9A825'],
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
              color: '#1565C0', // Workforce module button color
              children: [
                ['mdi-account-multiple', this.$t('workforce_management'), 'workforce_management', 'workforce.view', '#1565C0'],
                ['mdi-account-tie', this.$t('corporal_management'), 'corporal_management', 'workforce.corporals', '#1E88E5'],
                ['mdi-account-group', this.$t('team_management'), 'team_management', 'workforce.teams', '#2196F3'],
                ['mdi-sitemap-outline', this.$t('section_management'), 'section_management', 'workforce.sections', '#42A5F5'],
                ['mdi-calendar-month', this.$t('fortnight_management'), 'fortnight_management', 'workforce.fortnight', '#1565C0'],
                ['mdi-account-clock-outline', this.$t('workforce_absence_movement_title'), 'workforce_absence_movement', 'workforce.absence', '#3949AB'],
                ['mdi-chart-bar', this.$t('workforce_statistics'), 'workforce_statistics', 'workforce.statistics', '#5C6BC0'],
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
              color: '#E65100', // Supplier Invoice button color
              children: [
                ['mdi-file-document-multiple', this.$t('list_supplier_invoice'), 'list_supplier_invoice', 'supplier_invoices.view', '#E65100'],
                ['mdi-cash-register', this.$t('supplier_settlement'), 'supplier_settlement', 'supplier_invoices.settlement', '#F57C00'],
              ]
            },
            {
              id: 'client_invoices',
              icon: 'mdi-account-cash',
              title: this.$t('client_invoics'),
              color: '#00838F', // Client Invoice button color
              children: [
                ['mdi-file-document-multiple', this.$t('list_client_invoice'), 'list_client_invoice', 'client_invoices.view', '#00838F'],
                ['mdi-cash-register', this.$t('client_settlement'), 'client_settlement', 'client_invoices.settlement', '#00ACC1'],
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
                ['mdi-warehouse', this.$t('warehouse_management'), 'warehouse_management', 'settings.warehouses', '#a21ee5'],
                ['mdi-fish', this.$t('fish_management'), 'fish_management', 'settings.fish', '#00838F'],
                ['mdi-shape-outline', this.$t('charge_management'), 'charge_management', 'settings.charges', '#42A5F5'],
                ['mdi-tag-multiple-outline', this.$t('shipping_management'), 'shipping_management', 'settings.shippings', '#4643a0'],
                ['mdi-factory', this.$t('production_management'), 'production_management', 'settings.production', '#00897B'],
                ['mdi-clipboard-text-clock-outline', this.$t('detail_inventory_category_management'), 'detail_inventory_category_management', 'settings.inventory_categories', '#6D4C41'],
                ['mdi-swap-horizontal', this.$t('warehouse_transfer_category_management'), 'warehouse_transfer_category_management', 'settings.transfer_categories', '#F9A825'],
                ['mdi-leaf', this.$t('guano_management'), 'guano_management', 'settings.guano', '#388E3C'],
                ['mdi-package-variant-closed', this.$t('package_management'), 'package_management', 'settings.packages', '#546E7A'],
                ['mdi-bank', this.$t('bank_name_management'), 'bank_name_management', 'settings.banks', '#1E88E5'],
                ['mdi-domain', this.$t('company_settings'), 'company_settings', 'settings.company', '#3949AB'],
                ['mdi-calendar-clock', this.$t('fortnight_settings'), 'fortnight_settings', 'settings.fortnight', '#1565C0'],
                ['mdi-chart-areaspline', this.$t('chart_management'), 'chart_management', 'settings.charts', '#8E24AA'],
                ['mdi-cash-lock-open', this.$t('static_charge_management'), 'static_charge_management', 'settings.static_charges', '#42A5F5'],
                ['mdi-calculator-variant-outline', this.$t('cost_configuration_management'), 'cost_configuration_management', 'settings.cost_configuration', '#D81B60'],
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
      const q = (this.filterQuery || '').trim().toLowerCase();
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
        for (const sec of this.filteredSections) {
          for (const item of sec.items) {
            if (item.children && item.children.length > 0) {
              if (!this.persistedExpandedGroups.includes(item.id)) {
                this.persistedExpandedGroups.push(item.id);
              }
            }
          }
        }
      }
    },
    systemAvatarUrl: {
      immediate: true,
      handler(newVal) {
        this.processLogoForTheme(newVal);
      }
    }
  },
  mounted() {
    document.addEventListener('click', this.handleOutsideClick, true);
    document.addEventListener('touchstart', this.handleOutsideClick, { passive: true, capture: true });
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick, true);
    document.removeEventListener('touchstart', this.handleOutsideClick, { passive: true, capture: true });
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
        expand_sidebar: this.lang === 'es' ? 'Expandir barra lateral' : this.lang === 'fr' ? 'Agrandir la barre latérale' : 'Expand sidebar',
        home: this.lang === 'es' ? 'Inicio' : this.lang === 'fr' ? 'Accueil' : 'Home',
        my_profile: this.lang === 'es' ? 'Mi Perfil' : this.lang === 'fr' ? 'Mon Profil' : 'My Profile',
        administrator: this.lang === 'es' ? 'Administrador' : this.lang === 'fr' ? 'Administrateur' : 'Administrator',
        server_connected: this.lang === 'es' ? 'Servidor Conectado' : this.lang === 'fr' ? 'Serveur Connecté' : 'Server Connected',
        connection_cut: this.lang === 'es' ? 'Conexión Cortada' : this.lang === 'fr' ? 'Connexion Coupée' : 'Connection Cut',
        active: this.lang === 'es' ? 'Activo' : this.lang === 'fr' ? 'Actif' : 'Active',
      };

      if (this.$te && this.$te(key)) {
        const val = this.$t(key);
        if (val && val !== key) return val;
      }
      return fallbacks[key] || this.$t(key);
    },

    onAvatarError() {
      this.avatarLoadFailed = true;
    },

    hexToRgba(hex, alpha = 1) {
      if (!hex || typeof hex !== 'string') return `rgba(66, 165, 245, ${alpha})`;
      if (hex.startsWith('rgb')) return hex;
      let clean = hex.replace('#', '');
      if (clean.length === 3) clean = clean.split('').map(x => x + x).join('');
      if (clean.length !== 6) return `rgba(66, 165, 245, ${alpha})`;
      const num = parseInt(clean, 16);
      const r = (num >> 16) & 255;
      const g = (num >> 8) & 255;
      const b = num & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },

    getItemAccentStyle(item) {
      const c = item.color || '#42A5F5';
      return {
        '--accent-color': c,
        '--accent-bg-active': this.hexToRgba(c, 0.14),
        '--accent-bg-hover': this.hexToRgba(c, 0.08),
        '--accent-glow': this.hexToRgba(c, 0.35),
      };
    },

    getChildColor(child, parentItem) {
      return child[4] || parentItem.color || '#42A5F5';
    },

    getChildAccentStyle(child, parentItem) {
      const c = this.getChildColor(child, parentItem);
      return {
        '--child-color': c,
        '--child-bg-active': this.hexToRgba(c, 0.14),
        '--child-bg-hover': this.hexToRgba(c, 0.07),
      };
    },

    getGroupHeaderGradient(item) {
      const c = item.color || '#42A5F5';
      const gradients = {
        '#a21ee5': 'linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%)',
        '#6D4C41': 'linear-gradient(135deg, #4E342E 0%, #6D4C41 40%, #8D6E63 100%)',
        '#42A5F5': 'linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%)',
        '#00897B': 'linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%)',
        '#4643a0': 'linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%)',
        '#F9A825': 'linear-gradient(135deg, #F57F17 0%, #F9A825 40%, #FDD835 100%)',
        '#1565C0': 'linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)',
        '#E65100': 'linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%)',
        '#00838F': 'linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%)',
        '#546E7A': 'linear-gradient(135deg, #37474F 0%, #546E7A 40%, #78909C 100%)',
      };
      return gradients[c] || `linear-gradient(135deg, ${c} 0%, #1565C0 100%)`;
    },

    isRouteActive(routeName) {
      return this.$route.name === routeName;
    },

    isAnyChildActive(item) {
      if (!item.children) return false;
      return item.children.some(c => c[2] === this.$route.name);
    },

    toggleGroup(groupId) {
      if (!Array.isArray(this.persistedExpandedGroups)) {
        this.persistedExpandedGroups = [];
      }
      const idx = this.persistedExpandedGroups.indexOf(groupId);
      if (idx > -1) {
        this.persistedExpandedGroups.splice(idx, 1);
      } else {
        this.persistedExpandedGroups.push(groupId);
      }
    },

    isGroupExpanded(groupId) {
      return Array.isArray(this.persistedExpandedGroups) && this.persistedExpandedGroups.includes(groupId);
    },

    findAndOpenActiveGroup() {
      if (!this.visibleSections) return;
      for (const section of this.visibleSections) {
        for (const item of section.items) {
          if (item.children && this.isAnyChildActive(item)) {
            if (!this.persistedExpandedGroups.includes(item.id)) {
              this.persistedExpandedGroups.push(item.id);
            }
          }
        }
      }
    },

    /**
     * Process logo for transparent background on any theme
     */
    processLogoForTheme(url) {
      const src = url || this.systemAvatarUrl;
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
    },

    /**
     * Document Click Outside: collapse when user clicks anywhere outside the drawer
     * (and outside active popovers / menus)
     */
    handleOutsideClick(event) {
      if (this.main.sidebar_mini) return;

      const target = event.target;
      const drawerEl = this.$refs.drawerRoot?.$el || this.$el || document.querySelector('.futuristic-nav-drawer');

      // 1. Direct DOM containment check on drawer element
      if (drawerEl && drawerEl instanceof Element && target && drawerEl.contains(target)) {
        return;
      }

      // 2. Direct target closest selector check: covers any element within the drawer or toggle button
      if (target && target instanceof Element) {
        if (
          target.closest('.futuristic-nav-drawer') ||
          target.closest('.v-navigation-drawer') ||
          target.closest('.nav-cyber-list') ||
          target.closest('.cyber-brand-header') ||
          target.closest('.cyber-nav-footer') ||
          target.closest('.nav-cyber-item') ||
          target.closest('.ai-glowing-nav-card') ||
          target.closest('.cyber-group-block') ||
          target.closest('.systembar-nav-toggle-btn')
        ) {
          return;
        }
      }

      // 3. Composed path check: covers SVG icons, shadow trees, or elements detached during interaction
      const path = event.composedPath ? event.composedPath() : [];
      const isInsideDrawer = path.some(node => {
        if (!node || !(node instanceof Element)) return false;
        return (
          node === drawerEl ||
          node === this.$el ||
          node.classList.contains('futuristic-nav-drawer') ||
          node.classList.contains('v-navigation-drawer') ||
          node.classList.contains('nav-cyber-list') ||
          node.classList.contains('cyber-brand-header') ||
          node.classList.contains('cyber-nav-footer') ||
          node.classList.contains('nav-cyber-item') ||
          node.classList.contains('ai-glowing-nav-card') ||
          node.classList.contains('cyber-group-block') ||
          node.classList.contains('cyber-section-header')
        );
      });
      if (isInsideDrawer) return;

      // 4. Check if click was inside any active popovers, dialogs, or menus
      const isInsidePopover = path.some(node => {
        if (!node || !(node instanceof Element)) return false;
        return (
          node.classList.contains('cyber-rail-popover') ||
          node.classList.contains('group-popover-card') ||
          node.classList.contains('user-popover-card') ||
          node.classList.contains('leaf-popover-card') ||
          node.classList.contains('ai-popover-card') ||
          node.classList.contains('v-overlay') ||
          node.classList.contains('v-menu') ||
          node.classList.contains('v-overlay-container') ||
          node.classList.contains('systembar-nav-toggle-btn')
        );
      });
      if (isInsidePopover) return;

      // Outside click confirmed: collapse the drawer into rail mode
      // Notice: persistedExpandedGroups is NEVER reset or modified!
      this.main.sidebar_mini = true;
    },

    handleBrandClick() {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      } else {
        this.$router.push({ name: 'index' });
      }
    },

    handleDrawerClick() {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      }
    },

    handleNavListClick() {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      }
    },

    handleRailGroupClick(groupId) {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
        this.persistedExpandedGroups[groupId] = true;
      } else {
        this.toggleGroup(groupId);
      }
    },

    handleRailLeafClick(url) {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      }
      if (url) {
        this.navigateTo(url);
      }
    },

    handleRailAiClick(url) {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      }
      if (url) {
        this.navigateTo(url);
      }
    },

    handleRailSubItemClick(url) {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      }
      if (url) {
        this.navigateTo(url);
      }
    },

    navigateTo(routeName) {
      this.userMenuOpen = false;
      if (routeName) {
        this.$router.push({ name: routeName }).catch(() => {});
      }
    },

    onPopoverChildClick(groupId, routeName) {
      if (this.groupPopovers[groupId]) {
        this.groupPopovers[groupId] = false;
      }
      this.navigateTo(routeName);
    },

    async logout() {
      this.userMenuOpen = false;
      this.auth.user = null;
      this.auth.token = null;
      localStorage.removeItem('token');
      this.$router.push({ name: 'login' });
      this.auth.logout().catch(() => {});
    }
  }
};
</script>

<style scoped>
/* ============================================================
   FUTURISTIC NAVIGATION DRAWER - CYBER ARCHITECTURE
   ============================================================ */

.futuristic-nav-drawer {
  border: none !important;
  transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
  user-select: none;
  z-index: 1001 !important;
}

/* ============================================================
   TINY Y-SCROLLBAR (PRACTICAL, OPACITY 0.5, ZERO FLECHES)
   ============================================================ */

/* 1. Standards for Firefox & modern engines */
.futuristic-nav-drawer :deep(.v-navigation-drawer__content),
.futuristic-nav-drawer,
.popover-children-scroll {
  scrollbar-width: thin !important;
  scrollbar-color: rgba(208, 211, 218, 0.29) transparent !important;
}

.futuristic-nav-drawer :deep(.v-navigation-drawer__content):hover,
.futuristic-nav-drawer:hover,
.popover-children-scroll:hover {
  scrollbar-color: rgba(208, 211, 218, 0.65) transparent !important;
}

/* 2. WebKit / Chromium (Chrome, Edge, Safari, Opera) */
.futuristic-nav-drawer :deep(.v-navigation-drawer__content)::-webkit-scrollbar,
.futuristic-nav-drawer::-webkit-scrollbar,
.popover-children-scroll::-webkit-scrollbar {
  width: 4.5px !important;
  height: 4.5px !important;
}

/* Remove top and bottom arrow buttons ("fleches") completely */
.futuristic-nav-drawer :deep(.v-navigation-drawer__content)::-webkit-scrollbar-button,
.futuristic-nav-drawer::-webkit-scrollbar-button,
.popover-children-scroll::-webkit-scrollbar-button {
  display: none !important;
  width: 0 !important;
  height: 0 !important;
}

/* Fully transparent track */
.futuristic-nav-drawer :deep(.v-navigation-drawer__content)::-webkit-scrollbar-track,
.futuristic-nav-drawer::-webkit-scrollbar-track,
.popover-children-scroll::-webkit-scrollbar-track {
  background: transparent !important;
  margin: 4px 0 !important;
}

.futuristic-nav-drawer :deep(.v-navigation-drawer__content)::-webkit-scrollbar-corner,
.futuristic-nav-drawer::-webkit-scrollbar-corner,
.popover-children-scroll::-webkit-scrollbar-corner {
  background: transparent !important;
}

/* Scrollbar Thumb: Unactive = rgba(208, 211, 218, 0.29) */
.futuristic-nav-drawer :deep(.v-navigation-drawer__content)::-webkit-scrollbar-thumb,
.futuristic-nav-drawer::-webkit-scrollbar-thumb,
.popover-children-scroll::-webkit-scrollbar-thumb {
  background: rgba(208, 211, 218, 0.29) !important;
  border-radius: 999px !important;
  transition: background 0.2s ease !important;
}

/* Scrollbar Thumb: Active = rgba(208, 211, 218, 0.65) */
.futuristic-nav-drawer :deep(.v-navigation-drawer__content):hover::-webkit-scrollbar-thumb,
.futuristic-nav-drawer:hover::-webkit-scrollbar-thumb,
.futuristic-nav-drawer :deep(.v-navigation-drawer__content)::-webkit-scrollbar-thumb:hover,
.futuristic-nav-drawer::-webkit-scrollbar-thumb:hover,
.futuristic-nav-drawer :deep(.v-navigation-drawer__content)::-webkit-scrollbar-thumb:active,
.futuristic-nav-drawer::-webkit-scrollbar-thumb:active,
.popover-children-scroll:hover::-webkit-scrollbar-thumb,
.popover-children-scroll::-webkit-scrollbar-thumb:hover,
.popover-children-scroll::-webkit-scrollbar-thumb:active {
  background: rgba(208, 211, 218, 0.65) !important;
}

/* ── Dark Cyber Theme ── */
.cyber-dark-theme {
  background: rgba(13, 17, 23, 0.94) !important;
  backdrop-filter: blur(28px) saturate(180%) !important;
  -webkit-backdrop-filter: blur(28px) saturate(180%) !important;
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
  box-shadow: 4px 0 32px rgba(0, 0, 0, 0.45);
}

/* ── Light Cyber Theme ── */
.cyber-light-theme {
  background: rgba(255, 255, 255, 0.92) !important;
  backdrop-filter: blur(24px) saturate(180%) !important;
  -webkit-backdrop-filter: blur(24px) saturate(180%) !important;
  border-right: 1px solid rgba(0, 0, 0, 0.07) !important;
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.04);
}

/* ── 1. Brand Header ── */
.cyber-brand-header {
  padding: 16px 14px 12px 14px;
  transition: all 0.25s ease;
}

.brand-rail {
  padding: 14px 10px 10px 10px;
  display: flex;
  justify-content: center;
}

.brand-clickable-container {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  padding: 4px;
  border-radius: 14px;
  transition: background 0.2s ease, transform 0.2s ease;
}

.brand-clickable-container:hover {
  background: rgba(66, 165, 245, 0.08);
}

.brand-avatar-halo-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.brand-avatar-glow-ring {
  position: absolute;
  inset: -2px;
  border-radius: 50%;
  background: conic-gradient(from 180deg at 50% 50%, rgba(0, 229, 255, 0.5), rgba(66, 165, 245, 0.5), rgba(124, 77, 255, 0.5), rgba(0, 229, 255, 0.5));
  opacity: 0.35;
  filter: blur(5px);
  animation: cyber-spin 9s linear infinite;
  pointer-events: none;
}

@keyframes cyber-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.brand-avatar-orb,
.brand-avatar-orb.v-avatar {
  position: relative;
  z-index: 2;
  border: none !important;
  background: transparent !important;
  background-color: transparent !important;
  box-shadow: none !important;
  overflow: visible !important;
}

.brand-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: contain !important;
  background: transparent !important;
  background-color: transparent !important;
  filter: drop-shadow(0 2px 8px rgba(66, 165, 245, 0.45));
  transition: transform 0.25s ease, filter 0.25s ease;
  transform: scale(1.18);
  display: block;
}

.brand-clickable-container:hover .brand-avatar-img {
  transform: scale(1.26);
  filter: drop-shadow(0 0 12px rgba(0, 229, 255, 0.75));
}

.brand-online-orb-dot {
  position: absolute;
  bottom: 0;
  right: -1px;
  width: 11px;
  height: 11px;
  border-radius: 50%;
  border: 2px solid #0d1117;
  z-index: 3;
}

.brand-online-orb-dot.status-green {
  border: 1px solid #5cb379 !important;
}

.status-green {
  background-color: #00E676;
  box-shadow: 0 0 8px #00E676;
}

.status-cut {
  background-color: #FF5252;
  box-shadow: 0 0 8px #FF5252;
  animation: pulse-red 1.4s infinite;
}

@keyframes pulse-red {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.15); }
}

.brand-details-block {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.brand-title-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.brand-name-glitch {
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  background: linear-gradient(135deg, #42A5F5 0%, #00E5FF 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.cyber-badge-pill {
  font-size: 0.65rem;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 999px;
  background: rgba(66, 165, 245, 0.18);
  color: #42A5F5;
  border: 1px solid rgba(66, 165, 245, 0.35);
  letter-spacing: 0.8px;
}

.brand-sub-row {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 1px;
}

.brand-live-pulse-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.pulse-live {
  background-color: #00E676;
  box-shadow: 0 0 6px #00E676;
}

.pulse-severed {
  background-color: #FF5252;
  box-shadow: 0 0 6px #FF5252;
}

.brand-live-caption {
  font-size: 0.72rem;
  opacity: 0.7;
  font-weight: 500;
  letter-spacing: 0.2px;
}

/* ── 2. Search Field ── */
.cyber-search-wrapper {
  transition: all 0.2s ease;
}

.nav-cyber-search-field :deep(.v-field) {
  border-radius: 12px !important;
  font-size: 0.82rem;
  background: rgba(66, 165, 245, 0.04) !important;
  transition: all 0.2s ease;
}

.nav-cyber-search-field :deep(.v-field:hover) {
  background: rgba(66, 165, 245, 0.08) !important;
}

.nav-cyber-search-field :deep(.v-field--focused) {
  box-shadow: 0 0 12px rgba(66, 165, 245, 0.25) !important;
}

.cyber-header-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(66, 165, 245, 0.25), transparent);
}

/* ── 3. Navigation List ── */
.nav-cyber-list {
  padding: 4px 8px 16px 8px !important;
  background: transparent !important;
}

.drawer-is-rail .nav-cyber-list {
  padding: 4px 4px 16px 4px !important;
  cursor: pointer;
}

.cyber-section-header {
  padding: 8px 10px 4px 10px;
}

.cyber-section-title {
  font-size: 0.68rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.9px;
  opacity: 0.55;
}

.section-rail {
  padding: 4px 0;
  display: flex;
  justify-content: center;
}

.rail-micro-divider {
  width: 28px;
  height: 1px;
  margin: 6px auto;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
}

/* ── Cyber Nav Items ── */
.nav-cyber-item {
  display: flex;
  align-items: center;
  border-radius: 12px !important;
  min-height: 42px !important;
  padding: 0 10px;
  margin-bottom: 3px;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
  border: 1px solid transparent !important;
}

.nav-cyber-item:hover {
  background: var(--accent-bg-hover, rgba(66, 165, 245, 0.08)) !important;
  border-color: rgba(66, 165, 245, 0.15) !important;
  transform: translateX(2px);
}

.nav-cyber-item.route-active {
  background: var(--accent-bg-active, rgba(66, 165, 245, 0.15)) !important;
  border-color: var(--accent-color, #42A5F5) !important;
  box-shadow: 0 2px 14px var(--accent-glow, rgba(66, 165, 245, 0.2)) !important;
}

.cyber-icon-pill {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
  transition: all 0.22s ease;
  flex-shrink: 0;
}

.nav-item-label {
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.2px;
}

.nav-cyber-parent {
  cursor: pointer;
}

.group-count-chip {
  font-size: 0.65rem;
  height: 18px;
  padding: 0 5px;
}

.group-chevron-icon {
  margin-left: auto;
  opacity: 0.7;
  transition: transform 0.24s cubic-bezier(0.16, 1, 0.3, 1);
}

.chevron-rotated {
  transform: rotate(180deg);
}

.nav-group-children-wrap {
  padding-left: 6px;
}

/* ── Sub-Nav Children in Expanded Mode ── */
.nav-child-subitem {
  padding-left: 14px !important;
  min-height: 36px !important;
}

.cyber-child-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 8px;
  transition: all 0.2s ease;
}

.nav-child-label {
  font-size: 0.82rem;
  font-weight: 500;
  opacity: 0.9;
}

/* ── 3A. AI PROMPT GLOWING CARD ── */
.ai-glowing-nav-card {
  position: relative;
  border-radius: 14px;
  padding: 1.5px;
  background: linear-gradient(135deg, #00E5FF, #42A5F5, #7C4DFF, #FF4081, #00E5FF);
  background-size: 300% 300%;
  animation: ai-border-glow 6s ease infinite;
  box-shadow: 0 0 16px rgba(66, 165, 245, 0.35), 0 0 28px rgba(124, 77, 255, 0.2);
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes ai-border-glow {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.ai-item-inner {
  background: rgba(15, 23, 42, 0.95) !important;
  border-radius: 12.5px !important;
  border: none !important;
  width: 100%;
  box-sizing: border-box;
}

.cyber-light-theme .ai-item-inner {
  background: rgba(255, 255, 255, 0.96) !important;
}

.ai-gradient-text {
  background: linear-gradient(135deg, #00E5FF 0%, #42A5F5 50%, #7C4DFF 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.ai-spark-chip {
  font-size: 0.65rem;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(0, 229, 255, 0.2), rgba(124, 77, 255, 0.25));
  color: #00E5FF;
  border: 1px solid rgba(0, 229, 255, 0.4);
  display: flex;
  align-items: center;
}

/* AI Rail Mode - Glowing color strictly in border around rail button */
.ai-glowing-nav-card.ai-rail-mode {
  width: 50px !important;
  height: 44px !important;
  margin: 0 auto 4px auto !important;
  border-radius: 12px !important;
  padding: 1.5px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-sizing: border-box !important;
}

.ai-glowing-nav-card.ai-rail-mode .ai-rail-activator {
  position: relative !important;
  width: 100% !important;
  height: 100% !important;
  margin: 0 !important;
  border-radius: 10.5px !important;
  background: rgba(15, 23, 42, 0.95) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-sizing: border-box !important;
  transition: background 0.2s ease, transform 0.2s ease;
}

.cyber-light-theme .ai-glowing-nav-card.ai-rail-mode .ai-rail-activator {
  background: rgba(255, 255, 255, 0.96) !important;
}

.ai-glowing-nav-card.ai-rail-mode .ai-rail-activator:hover {
  background: rgba(24, 38, 64, 0.96) !important;
}

.cyber-light-theme .ai-glowing-nav-card.ai-rail-mode .ai-rail-activator:hover {
  background: rgba(243, 246, 251, 0.98) !important;
}

.ai-glowing-nav-card.ai-rail-mode:hover {
  transform: scale(1.04);
  box-shadow: 0 0 20px rgba(0, 229, 255, 0.45), 0 0 32px rgba(124, 77, 255, 0.3);
}

/* ── Rail Mode Items Architecture ── */
.rail-icon-wrapper {
  position: relative;
  width: 50px;
  height: 44px;
  margin: 0 auto 4px auto;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.rail-icon-wrapper .cyber-icon-pill {
  margin-right: 0 !important;
}

.rail-icon-wrapper:hover {
  background: rgba(66, 165, 245, 0.12);
  transform: scale(1.04);
}

.rail-icon-wrapper.route-active {
  background: rgba(66, 165, 245, 0.18);
  box-shadow: 0 0 16px rgba(66, 165, 245, 0.35);
}

.rail-active-glow-bar {
  position: absolute;
  left: 1px;
  top: 10px;
  bottom: 10px;
  width: 3.5px;
  border-radius: 999px;
  box-shadow: 0 0 8px currentColor;
}

.rail-group-expanded-badge {
  position: absolute;
  top: 4px;
  right: 5px;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  box-shadow: 0 0 6px currentColor;
}

.ai-rail-spark-dot {
  position: absolute;
  top: 4px;
  right: 6px;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #00E5FF;
  box-shadow: 0 0 8px #00E5FF;
  animation: pulse-ai 1.5s infinite;
}

@keyframes pulse-ai {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.3); opacity: 0.6; }
}

/* ── Rail Group Container with Mini Subnavs Column ── */
.rail-group-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.rail-group-expanded {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 14px;
  padding: 2px 0 6px 0;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.rail-mini-subnavs-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
}

.rail-mini-sub-item {
  width: 36px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.rail-mini-sub-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.rail-mini-sub-item:hover .rail-mini-sub-icon-box {
  transform: scale(1.12);
}

.mini-sub-active .rail-mini-sub-icon-box {
  border-color: currentColor !important;
  box-shadow: 0 0 10px rgba(66, 165, 245, 0.4);
}

/* ── Interactive Popovers in Rail Mode ── */
.cyber-rail-popover {
  border-radius: 16px !important;
  overflow: hidden;
  min-width: 250px;
  max-width: 310px;
  box-shadow: 0 16px 48px -6px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.popover-dark {
  background: rgba(15, 23, 42, 0.95) !important;
  backdrop-filter: blur(28px) saturate(190%) !important;
  -webkit-backdrop-filter: blur(28px) saturate(190%) !important;
  border: 1px solid rgba(66, 165, 245, 0.24) !important;
  color: #F8FAFC !important;
}

.popover-light {
  background: rgba(255, 255, 255, 0.96) !important;
  backdrop-filter: blur(24px) saturate(180%) !important;
  -webkit-backdrop-filter: blur(24px) saturate(180%) !important;
  border: 1px solid rgba(0, 0, 0, 0.09) !important;
  color: #0F172A !important;
  box-shadow: 0 16px 40px -6px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.06) !important;
}

.popover-hero-header {
  padding: 12px 16px;
  display: flex;
  align-items: center;
  color: white;
  position: relative;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
}

.popover-hero-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  backdrop-filter: blur(4px);
}

.popover-hero-title {
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.3px;
}

.popover-count-chip {
  font-size: 0.68rem;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(4px);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
}

/* Sub-Nav Scroll List with Clean Margins Between Elements */
.popover-children-scroll {
  max-height: 320px;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 8px 6px;
  display: flex;
  flex-direction: column;
  gap: 5px; /* Clean gap/margin between element rows! */
}

.popover-child-row {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  margin: 1px 2px; /* Margin around each item */
  border-radius: 10px;
  gap: 11px;
  border: 1px solid transparent;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
}

.popover-child-row:hover {
  background: rgba(66, 165, 245, 0.12) !important;
  border-color: rgba(66, 165, 245, 0.28) !important;
  transform: translateX(3px);
}

.popover-child-row.is-child-active {
  background: rgba(66, 165, 245, 0.18) !important;
  border-color: rgba(66, 165, 245, 0.45) !important;
  font-weight: 700;
  box-shadow: 0 2px 10px rgba(66, 165, 245, 0.25);
}

.popover-child-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.popover-child-row:hover .popover-child-icon-box {
  transform: scale(1.1);
}

.popover-child-text {
  font-size: 0.82rem;
  font-weight: 550;
  letter-spacing: 0.2px;
  line-height: 1.3;
}

.popover-arrow-icon {
  opacity: 0.25;
  transform: translateX(-3px);
  transition: all 0.2s ease;
}

.popover-child-row:hover .popover-arrow-icon {
  opacity: 0.9;
  transform: translateX(1px);
  color: #42A5F5;
}

/* ── 4. Footer Architecture ── */
.cyber-footer-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(66, 165, 245, 0.2), transparent);
}

.cyber-nav-footer {
  padding: 4px 6px 12px 6px;
  transition: all 0.25s ease;
}

.footer-is-rail {
  display: flex;
  justify-content: center;
}

.expanded-footer-card {
  border-radius: 14px;
  background: rgba(66, 165, 245, 0.05);
  border: 1px solid rgba(66, 165, 245, 0.15);
  transition: all 0.2s ease;
}

.expanded-footer-card:hover {
  background: rgba(66, 165, 245, 0.09);
}

.footer-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}

.footer-avatar-orb {
  background: linear-gradient(135deg, #1565C0, #42A5F5) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.footer-status-dot {
  position: absolute;
  bottom: 0;
  right: -1px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid #0d1117;
}

.footer-user-name {
  font-size: 0.84rem;
  letter-spacing: 0.2px;
}

.footer-live-micro-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
}

.dot-green {
  background-color: #00E676;
  box-shadow: 0 0 5px #00E676;
}

.dot-red {
  background-color: #FF5252;
  box-shadow: 0 0 5px #FF5252;
}

.footer-role-text {
  font-size: 0.72rem;
  opacity: 0.7;
  font-weight: 500;
}

.footer-btn {
  border-radius: 10px !important;
}

/* ── Rail User Avatar & Popover ── */
.rail-footer-wrap {
  display: flex;
  justify-content: center;
  padding: 4px 0;
}

.rail-user-avatar-btn {
  padding: 4px;
  border-radius: 50%;
  transition: transform 0.2s ease;
}

.rail-user-avatar-btn:hover {
  transform: scale(1.08);
}

.rail-user-avatar-inner {
  position: relative;
}

.rail-avatar-orb {
  background: linear-gradient(135deg, #1565C0, #42A5F5) !important;
}

.rail-status-badge-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 11px;
  height: 11px;
  border-radius: 50%;
  border: 2px solid #0d1117;
}

.user-popover-banner {
  padding: 12px 16px;
  background: linear-gradient(135deg, #0D47A1 0%, #1976D2 50%, #42A5F5 100%);
  display: flex;
  align-items: center;
}

.user-status-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  background: rgba(0, 0, 0, 0.28);
  color: white;
  backdrop-filter: blur(4px);
}

.user-status-orb {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #00E676;
  box-shadow: 0 0 6px #00E676;
}

.pill-severed .user-status-orb {
  background: #FF5252;
  box-shadow: 0 0 6px #FF5252;
}

.user-popover-identity {
  width: 260px;
  padding: 14px 14px 12px 14px !important;
}

.popover-avatar {
  background: linear-gradient(135deg, #1565C0, #42A5F5) !important;
  box-shadow: 0 2px 10px rgba(66, 165, 245, 0.4);
}

.user-name-text {
  font-size: 0.9rem;
  letter-spacing: 0.2px;
}

.user-email-text {
  opacity: 0.7;
}

.user-role-chip {
  display: inline-flex;
  align-items: center;
  padding: 3px 9px;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 700;
  background: rgba(66, 165, 245, 0.14);
  color: #42A5F5;
  border: 1px solid rgba(66, 165, 245, 0.28);
}

.user-popover-actions {
  display: flex;
  flex-direction: column;
  gap: 5px; /* Clean margin between element action buttons! */
  margin-top: 6px;
}

.user-popover-action-row {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 8px 12px;
  margin: 1px 0; /* Clean vertical spacing */
  border-radius: 10px;
  background: transparent;
  border: 1px solid transparent;
  font-size: 0.82rem;
  font-weight: 550;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  color: inherit;
  cursor: pointer;
}

.user-popover-action-row:hover {
  background: rgba(66, 165, 245, 0.12) !important;
  border-color: rgba(66, 165, 245, 0.28) !important;
  transform: translateX(3px);
}

.logout-row:hover {
  background: rgba(239, 83, 80, 0.14) !important;
  border-color: rgba(239, 83, 80, 0.35) !important;
}

.user-action-icon-box {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-profile {
  background: rgba(66, 165, 245, 0.15);
}

.action-admin {
  background: rgba(92, 107, 192, 0.15);
}

.action-logout {
  background: rgba(239, 83, 80, 0.15);
}

.action-arrow {
  opacity: 0.35;
  transition: all 0.2s ease;
}

.user-popover-action-row:hover .action-arrow {
  opacity: 0.95;
  transform: translateX(2px);
}

/* Leaf & AI Popover Details */
.leaf-popover-card {
  min-width: 200px !important;
}

.leaf-popover-card:hover .leaf-arrow {
  transform: translateX(2px);
  color: #42A5F5;
}

.ai-header-gradient {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 50%, #42A5F5 100%) !important;
}

.popover-ai-pill {
  font-size: 0.65rem;
  font-weight: 800;
  padding: 2px 7px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(4px);
}

/* ── Tooltips ── */
:deep(.cyber-tooltip-dark) {
  background: rgba(17, 24, 39, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.15) !important;
  border-radius: 8px !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
}

:deep(.cyber-tooltip-light) {
  background: rgba(255, 255, 255, 0.96) !important;
  color: #111827 !important;
  border: 1px solid rgba(0, 0, 0, 0.1) !important;
  border-radius: 8px !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
}

/* ── Animations ── */
.fade-slide-enter-active, .fade-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-slide-enter-from, .fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>