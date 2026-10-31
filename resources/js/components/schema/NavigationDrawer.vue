<template>
  <v-navigation-drawer
    ref="drawerRoot"
    permanent
    :scrim="false"
    :disable-resize-watcher="true"
    :rail="main.sidebar_mini"
    :rail-width="70"
    :width="272"
    :theme="main.is_dark ? 'dark' : 'light'"
    class="pro-nav-drawer"
    :class="[
      main.is_dark ? 'drawer-theme-dark' : 'drawer-theme-light',
      main.sidebar_mini ? 'drawer-is-rail' : 'drawer-is-expanded'
    ]"
  >
    <!-- ============================================================
         1. BRAND & SYSTEM HEADER
         ============================================================ -->
    <div class="pro-header-area">
      <div
        class="pro-brand-row cursor-pointer"
        @click.stop="handleBrandClick"
        :title="main.sidebar_mini ? t('expand_sidebar') : t('home')"
      >
        <!-- Orbit Logo Avatar -->
        <div class="pro-brand-orb-box">
          <img
            :src="displayAvatarUrl"
            alt="Jupiter"
            class="pro-brand-logo-img"
            @error="onAvatarError"
          />
          <span
            class="pro-brand-status-dot"
            :class="isServerConnected ? 'status-connected' : 'status-cut'"
            :title="isServerConnected ? t('server_connected') : t('connection_cut')"
          ></span>
        </div>

        <!-- Brand Details (Expanded Mode) -->
        <div class="pro-brand-text-block">
          <div class="pro-brand-name-line">
            <span class="pro-brand-title">Jupiter</span>
            <span class="pro-brand-edition-chip">ERP</span>
          </div>
          <div class="pro-brand-meta-line">
            <span
              class="pro-meta-pulse-dot"
              :class="isServerConnected ? 'pulse-live' : 'pulse-cut'"
            ></span>
            <span class="pro-meta-label">
              {{ isServerConnected ? (t('active') + ' • Cloud') : t('connection_cut') }}
            </span>
          </div>
        </div>

        <!-- In-Drawer Toggle Button (Visible when expanded) -->
        <button
          v-if="!main.sidebar_mini"
          type="button"
          class="pro-collapse-btn ml-auto"
          @click.stop="toggleSidebar"
          :title="t('collapse_sidebar')"
        >
          <v-icon size="16">mdi-chevron-left</v-icon>
        </button>
      </div>

      <!-- Rail Tooltip for Brand -->
      <v-tooltip
        v-if="main.sidebar_mini"
        activator="parent"
        location="end"
        offset="14"
        :open-delay="50"
        :close-delay="100"
        :theme="main.is_dark ? 'dark' : 'light'"
        :content-class="['pro-nav-tooltip', 'pro-nav-single-tooltip', main.is_dark ? 'pro-nav-tooltip--dark' : 'pro-nav-tooltip--light']"
      >
        <div class="pro-single-tooltip-card">
          <div
            class="pro-single-tooltip-icon-box"
            :style="{
              backgroundColor: hexToRgba('#42A5F5', main.is_dark ? 0.22 : 0.12),
              borderColor: hexToRgba('#42A5F5', 0.35)
            }"
          >
            <v-icon size="16" color="#42A5F5">mdi-orbit-variant</v-icon>
          </div>
          <div class="pro-tooltip-text-col">
            <span
              class="pro-tooltip-theme-title font-weight-bold"
              :style="{ color: getAccessibleThemeColor('#42A5F5', main.is_dark) }"
            >
              Jupiter ERP
            </span>
            <span
              class="pro-tooltip-parent-tag"
              :style="{ color: main.is_dark ? '#94A3B8' : '#64748B' }"
            >
              {{ isServerConnected ? (t('active') + ' • Cloud') : t('connection_cut') }}
            </span>
          </div>
        </div>
      </v-tooltip>
    </div>

    <!-- ============================================================
         2. NAVIGATION SEARCH FILTER
         ============================================================ -->
    <div class="pro-search-area">
      <!-- Expanded Search Input -->
      <div v-if="!main.sidebar_mini" class="pro-search-input-wrap">
        <v-text-field
          v-model="filterQuery"
          :placeholder="t('filter_menu')"
          prepend-inner-icon="mdi-magnify"
          density="compact"
          variant="outlined"
          rounded="lg"
          hide-details
          clearable
          color="#42A5F5"
          class="pro-search-text-field"
        ></v-text-field>
      </div>

      <!-- Rail Search Icon Button -->
      <div v-else class="pro-rail-search-box">
        <button
          type="button"
          class="pro-rail-search-btn"
          @click.stop="openSearchFromRail"
        >
          <v-icon size="17">mdi-magnify</v-icon>
          <v-tooltip
            activator="parent"
            location="end"
            offset="14"
            :open-delay="50"
            :close-delay="100"
            :theme="main.is_dark ? 'dark' : 'light'"
            :content-class="['pro-nav-tooltip', 'pro-nav-single-tooltip', main.is_dark ? 'pro-nav-tooltip--dark' : 'pro-nav-tooltip--light']"
          >
            <div class="pro-single-tooltip-card">
              <div
                class="pro-single-tooltip-icon-box"
                :style="{
                  backgroundColor: hexToRgba('#42A5F5', main.is_dark ? 0.22 : 0.12),
                  borderColor: hexToRgba('#42A5F5', 0.35)
                }"
              >
                <v-icon size="16" color="#42A5F5">mdi-magnify</v-icon>
              </div>
              <div class="pro-tooltip-text-col">
                <span
                  class="pro-tooltip-theme-title font-weight-bold"
                  :style="{ color: getAccessibleThemeColor('#42A5F5', main.is_dark) }"
                >
                  {{ t('filter_menu') }}
                </span>
                <span
                  class="pro-tooltip-parent-tag"
                  :style="{ color: main.is_dark ? '#94A3B8' : '#64748B' }"
                >
                  {{ t('search') || 'Search' }}
                </span>
              </div>
            </div>
          </v-tooltip>
        </button>
      </div>
    </div>

    <!-- Micro Section Divider -->
    <div class="pro-nav-divider mx-3 my-1"></div>

    <!-- ============================================================
         3. UNIFIED NAVIGATION LIST (Zero Destroy/Mount Lag)
         ============================================================ -->
    <div class="pro-nav-scroll-area">
      <div
        v-for="(section, sIdx) in filteredSections"
        :key="'sec-' + sIdx"
        class="pro-section-group"
      >
        <!-- Section Header Label or Rail Micro Separator -->
        <div class="pro-section-header">
          <span class="pro-section-title">{{ section.title }}</span>
          <div v-if="sIdx > 0" class="pro-rail-micro-separator"></div>
        </div>

        <!-- Section Items -->
        <div class="pro-section-items-col">
          <template v-for="item in section.items" :key="'item-' + item.id">
            <!-- ── 3A. AI PROMPT SPECIAL CARD ── -->
            <div
              v-if="item.url === 'ai_prompt'"
              class="pro-nav-item pro-ai-card cursor-pointer"
              :class="{ 'is-active': isRouteActive(item.url) }"
              :style="getItemAccentStyle(item)"
              @click.stop="navigateTo(item.url)"
            >
              <div class="pro-item-active-bar" :style="{ backgroundColor: item.color }"></div>
              <div class="pro-icon-box pro-ai-icon-box">
                <v-icon size="18" :color="item.color">{{ item.icon || 'mdi-robot' }}</v-icon>
              </div>
              <div class="pro-item-label-wrap">
                <span class="pro-item-title pro-ai-gradient-title">{{ item.title }}</span>
                <span class="pro-ai-sparkle-pill ml-auto">
                  <v-icon size="11" class="mr-0.5">mdi-sparkles</v-icon>AI
                </span>
              </div>

              <!-- Rail Tooltip for AI Prompt -->
              <v-tooltip
                v-if="main.sidebar_mini"
                activator="parent"
                location="end"
                offset="14"
                :open-delay="50"
                :close-delay="100"
                :theme="main.is_dark ? 'dark' : 'light'"
                :content-class="['pro-nav-tooltip', 'pro-nav-single-tooltip', main.is_dark ? 'pro-nav-tooltip--dark' : 'pro-nav-tooltip--light']"
              >
                <div class="pro-single-tooltip-card">
                  <div
                    class="pro-single-tooltip-icon-box pro-ai-tooltip-icon"
                    :style="{
                      backgroundColor: hexToRgba(item.color, main.is_dark ? 0.25 : 0.12),
                      borderColor: hexToRgba(item.color, 0.4)
                    }"
                  >
                    <v-icon size="16" :color="item.color">{{ item.icon || 'mdi-robot' }}</v-icon>
                  </div>
                  <div class="pro-tooltip-text-col">
                    <div class="pro-tooltip-title-row">
                      <span
                        class="pro-tooltip-theme-title font-weight-bold"
                        :style="{ color: getAccessibleThemeColor(item.color, main.is_dark) }"
                      >
                        {{ item.title }}
                      </span>
                      <span class="pro-ai-sparkle-pill">
                        <v-icon size="10" class="mr-0.5">mdi-sparkles</v-icon>AI
                      </span>
                    </div>
                    <span
                      class="pro-tooltip-parent-tag"
                      :style="{ color: main.is_dark ? '#94A3B8' : '#64748B' }"
                    >
                      {{ t('ai_assistant') || 'AI Assistant' }}
                    </span>
                  </div>
                  <span
                    v-if="isRouteActive(item.url)"
                    class="pro-subnav-tooltip-badge"
                    :style="{
                      color: getAccessibleThemeColor(item.color, main.is_dark),
                      backgroundColor: hexToRgba(item.color, main.is_dark ? 0.25 : 0.12),
                      borderColor: hexToRgba(item.color, 0.35)
                    }"
                  >
                    {{ t('active') }}
                  </span>
                </div>
              </v-tooltip>
            </div>

            <!-- ── 3B. SINGLE LEAF NAV LINK (No Children) ── -->
            <div
              v-else-if="!item.children || item.children.length === 0"
              class="pro-nav-item cursor-pointer"
              :class="{ 'is-active': isRouteActive(item.url) }"
              :style="getItemAccentStyle(item)"
              @click.stop="item.url && navigateTo(item.url)"
            >
              <div class="pro-item-active-bar" :style="{ backgroundColor: item.color }"></div>
              <div
                class="pro-icon-box"
                :style="{ backgroundColor: hexToRgba(item.color, isRouteActive(item.url) ? 0.22 : 0.1) }"
              >
                <v-icon size="17" :color="item.color">{{ item.icon }}</v-icon>
              </div>
              <div class="pro-item-label-wrap">
                <span class="pro-item-title">{{ item.title }}</span>
              </div>

              <!-- Rail Tooltip for Single Leaf Nav Link -->
              <v-tooltip
                v-if="main.sidebar_mini"
                activator="parent"
                location="end"
                offset="14"
                :open-delay="50"
                :close-delay="100"
                :theme="main.is_dark ? 'dark' : 'light'"
                :content-class="['pro-nav-tooltip', 'pro-nav-single-tooltip', main.is_dark ? 'pro-nav-tooltip--dark' : 'pro-nav-tooltip--light']"
              >
                <div class="pro-single-tooltip-card">
                  <div
                    class="pro-single-tooltip-icon-box"
                    :style="{
                      backgroundColor: hexToRgba(item.color, main.is_dark ? 0.22 : 0.12),
                      borderColor: hexToRgba(item.color, 0.35)
                    }"
                  >
                    <v-icon size="16" :color="item.color">{{ item.icon }}</v-icon>
                  </div>
                  <div class="pro-tooltip-text-col">
                    <span
                      class="pro-tooltip-theme-title font-weight-bold"
                      :style="{ color: getAccessibleThemeColor(item.color, main.is_dark) }"
                    >
                      {{ item.title }}
                    </span>
                    <span
                      v-if="section && section.title"
                      class="pro-tooltip-parent-tag"
                      :style="{ color: main.is_dark ? '#94A3B8' : '#64748B' }"
                    >
                      {{ section.title }}
                    </span>
                  </div>
                  <span
                    v-if="isRouteActive(item.url)"
                    class="pro-subnav-tooltip-badge"
                    :style="{
                      color: getAccessibleThemeColor(item.color, main.is_dark),
                      backgroundColor: hexToRgba(item.color, main.is_dark ? 0.25 : 0.12),
                      borderColor: hexToRgba(item.color, 0.35)
                    }"
                  >
                    {{ t('active') }}
                  </span>
                </div>
              </v-tooltip>
            </div>

            <!-- ── 3C. ACCORDION GROUP WITH SUB-ITEMS ── -->
            <div
              v-else
              class="pro-nav-group-root"
              :class="{
                'group-open': isGroupExpanded(item.id) && !main.sidebar_mini,
                'has-active-child': isAnyChildActive(item)
              }"
              :style="getItemAccentStyle(item)"
            >
              <!-- Group Trigger Row -->
              <div
                class="pro-nav-item pro-group-header cursor-pointer"
                :class="{ 'is-active': isAnyChildActive(item) }"
                @click.stop="handleGroupClick(item.id)"
              >
                <div class="pro-item-active-bar" :style="{ backgroundColor: item.color }"></div>
                <div
                  class="pro-icon-box"
                  :style="{ backgroundColor: hexToRgba(item.color, isAnyChildActive(item) ? 0.22 : 0.1) }"
                >
                  <v-icon size="17" :color="item.color">{{ item.icon }}</v-icon>
                </div>
                <div class="pro-item-label-wrap">
                  <span class="pro-item-title">{{ item.title }}</span>
                  <span
                    class="pro-group-count-pill ml-auto mr-1"
                    :style="{ color: item.color, backgroundColor: hexToRgba(item.color, 0.14) }"
                  >
                    {{ item.children.length }}
                  </span>
                  <v-icon
                    size="15"
                    class="pro-group-chevron"
                    :class="{ 'chevron-rotated': isGroupExpanded(item.id) }"
                  >
                    mdi-chevron-down
                  </v-icon>
                </div>

                <!-- Rail Tooltip: Detail of all sub-navs with original icons & theme colors -->
                <v-tooltip
                  v-if="main.sidebar_mini"
                  activator="parent"
                  location="end"
                  offset="14"
                  :open-delay="60"
                  :close-delay="150"
                  :theme="main.is_dark ? 'dark' : 'light'"
                  :content-class="['pro-nav-tooltip', 'pro-nav-group-tooltip', main.is_dark ? 'pro-nav-tooltip--dark' : 'pro-nav-tooltip--light']"
                >
                  <div class="pro-group-tooltip-card">
                    <!-- Group Header Row -->
                    <div class="pro-group-tooltip-header">
                      <div
                        class="pro-group-tooltip-header-icon-box"
                        :style="{
                          backgroundColor: hexToRgba(item.color, main.is_dark ? 0.22 : 0.12),
                          borderColor: hexToRgba(item.color, 0.35)
                        }"
                      >
                        <v-icon size="17" :color="item.color">{{ item.icon }}</v-icon>
                      </div>
                      <div class="pro-tooltip-text-col">
                        <span
                          class="pro-group-tooltip-title font-weight-bold"
                          :style="{ color: getAccessibleThemeColor(item.color, main.is_dark) }"
                        >
                          {{ item.title }}
                        </span>
                        <span
                          class="pro-group-tooltip-subtitle"
                          :style="{ color: main.is_dark ? '#94A3B8' : '#64748B' }"
                        >
                          {{ item.children.length }} {{ item.children.length === 1 ? t('item') : t('items') }}
                        </span>
                      </div>
                      <span
                        class="pro-group-tooltip-badge"
                        :style="{
                          color: getAccessibleThemeColor(item.color, main.is_dark),
                          backgroundColor: hexToRgba(item.color, main.is_dark ? 0.22 : 0.1),
                          borderColor: hexToRgba(item.color, 0.3)
                        }"
                      >
                        {{ item.children.length }}
                      </span>
                    </div>

                    <!-- Divider -->
                    <div
                      class="pro-group-tooltip-divider"
                      :style="{ borderColor: main.is_dark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.08)' }"
                    ></div>

                    <!-- Sub-Nav Items List -->
                    <div class="pro-group-tooltip-list">
                      <div
                        v-for="(child, cIdx) in item.children"
                        :key="'tooltip-child-' + cIdx"
                        class="pro-group-tooltip-item"
                        :class="{ 'is-active': isRouteActive(child[2]) }"
                        :style="{
                          '--child-hover-bg': hexToRgba(getChildColor(child, item), main.is_dark ? 0.18 : 0.1),
                          '--child-active-bg': hexToRgba(getChildColor(child, item), main.is_dark ? 0.25 : 0.15)
                        }"
                        @click.stop="navigateTo(child[2])"
                      >
                        <!-- Child Icon in original theme color -->
                        <div
                          class="pro-group-tooltip-child-icon"
                          :style="{
                            backgroundColor: hexToRgba(getChildColor(child, item), main.is_dark ? 0.22 : 0.12),
                            borderColor: isRouteActive(child[2])
                              ? getChildColor(child, item)
                              : hexToRgba(getChildColor(child, item), 0.25)
                          }"
                        >
                          <v-icon size="14" :color="getChildColor(child, item)">
                            {{ child[0] || 'mdi-circle-small' }}
                          </v-icon>
                        </div>

                        <!-- Child Title in Theme Color -->
                        <span
                          class="pro-group-tooltip-child-title font-weight-medium"
                          :style="{
                            color: isRouteActive(child[2])
                              ? getAccessibleThemeColor(getChildColor(child, item), main.is_dark)
                              : (main.is_dark ? '#E2E8F0' : '#1E293B')
                          }"
                        >
                          {{ child[1] }}
                        </span>

                        <!-- Active Indicator Dot or Right Arrow -->
                        <span
                          v-if="isRouteActive(child[2])"
                          class="pro-group-tooltip-active-dot"
                          :style="{
                            backgroundColor: getAccessibleThemeColor(getChildColor(child, item), main.is_dark),
                            boxShadow: '0 0 8px ' + hexToRgba(getChildColor(child, item), 0.85)
                          }"
                        ></span>
                        <v-icon
                          v-else
                          size="13"
                          class="pro-group-tooltip-arrow opacity-40"
                          :style="{ color: getAccessibleThemeColor(getChildColor(child, item), main.is_dark) }"
                        >
                          mdi-chevron-right
                        </v-icon>
                      </div>
                    </div>
                  </div>
                </v-tooltip>
              </div>

              <!-- Rail Mode Mini Sub-navs Column (Visible only in rail mode when group is expanded) -->
              <div
                v-if="main.sidebar_mini && isGroupExpanded(item.id)"
                class="pro-rail-subnavs-col"
              >
                <div
                  v-for="(child, msIdx) in item.children"
                  :key="'rail-sub-' + msIdx"
                  class="pro-rail-sub-item cursor-pointer"
                  :class="{ 'is-active': isRouteActive(child[2]) }"
                  @click.stop="navigateTo(child[2])"
                >
                  <div
                    class="pro-rail-sub-icon-box"
                    :style="{
                      backgroundColor: hexToRgba(getChildColor(child, item), isRouteActive(child[2]) ? 0.3 : 0.12),
                      borderColor: isRouteActive(child[2]) ? getChildColor(child, item) : hexToRgba(getChildColor(child, item), 0.25)
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
                    offset="14"
                    :open-delay="50"
                    :close-delay="100"
                    :theme="main.is_dark ? 'dark' : 'light'"
                    :content-class="['pro-nav-tooltip', 'pro-nav-single-tooltip', main.is_dark ? 'pro-nav-tooltip--dark' : 'pro-nav-tooltip--light']"
                  >
                    <div class="pro-subnav-tooltip-card">
                      <div
                        class="pro-single-tooltip-icon-box"
                        :style="{
                          backgroundColor: hexToRgba(getChildColor(child, item), main.is_dark ? 0.22 : 0.12),
                          borderColor: hexToRgba(getChildColor(child, item), 0.35)
                        }"
                      >
                        <v-icon size="15" :color="getChildColor(child, item)">
                          {{ child[0] || 'mdi-circle-small' }}
                        </v-icon>
                      </div>
                      <div class="pro-tooltip-text-col">
                        <span
                          class="pro-tooltip-theme-title font-weight-bold"
                          :style="{ color: getAccessibleThemeColor(getChildColor(child, item), main.is_dark) }"
                        >
                          {{ child[1] }}
                        </span>
                        <span
                          class="pro-tooltip-parent-tag"
                          :style="{ color: main.is_dark ? '#94A3B8' : '#64748B' }"
                        >
                          {{ item.title }}
                        </span>
                      </div>
                      <span
                        v-if="isRouteActive(child[2])"
                        class="pro-subnav-tooltip-badge"
                        :style="{
                          color: getAccessibleThemeColor(getChildColor(child, item), main.is_dark),
                          backgroundColor: hexToRgba(getChildColor(child, item), main.is_dark ? 0.25 : 0.12),
                          borderColor: hexToRgba(getChildColor(child, item), 0.35)
                        }"
                      >
                        {{ t('active') }}
                      </span>
                    </div>
                  </v-tooltip>
                </div>
              </div>

              <!-- Sub-items List (Smooth 60fps CSS Grid Accordion for Expanded Mode) -->
              <div
                class="pro-subnav-accordion"
                :class="{ 'accordion-expanded': isGroupExpanded(item.id) && !main.sidebar_mini }"
              >
                <div class="pro-subnav-inner">
                  <div
                    v-for="(child, cIdx) in item.children"
                    :key="'sub-' + cIdx"
                    class="pro-subnav-row cursor-pointer"
                    :class="{ 'is-active': isRouteActive(child[2]) }"
                    :style="getChildAccentStyle(child, item)"
                    @click.stop="navigateTo(child[2])"
                  >
                    <div class="pro-subnav-tree-line"></div>
                    <div
                      class="pro-subnav-icon-pill"
                      :style="{ backgroundColor: hexToRgba(getChildColor(child, item), 0.12) }"
                    >
                      <v-icon size="13" :color="getChildColor(child, item)">
                        {{ child[0] || 'mdi-circle-small' }}
                      </v-icon>
                    </div>
                    <span
                      class="pro-subnav-title"
                      :style="{ color: main.is_dark ? (isRouteActive(child[2]) ? getChildColor(child, item) : '#CBD5E1') : (isRouteActive(child[2]) ? getChildColor(child, item) : '#334155') }"
                    >
                      {{ child[1] }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- ============================================================
         4. USER PROFILE & FOOTER AREA
         ============================================================ -->
    <div class="pro-footer-area">
      <div class="pro-nav-divider mx-3 mb-2"></div>
      <div class="pro-user-row cursor-pointer" @click.stop="handleProfileClick">
        <v-avatar size="34" class="pro-user-avatar">
          <span class="pro-avatar-initials">{{ userInitials }}</span>
        </v-avatar>

        <div class="pro-user-info-wrap">
          <span class="pro-user-name">{{ userName }}</span>
          <span class="pro-user-role">{{ userRole }}</span>
        </div>

        <div v-if="!main.sidebar_mini" class="pro-footer-actions ml-auto">
          <button
            type="button"
            class="pro-footer-action-btn"
            @click.stop="navigateTo('profile_settings')"
            :title="t('my_profile')"
          >
            <v-icon size="16">mdi-cog-outline</v-icon>
          </button>
          <button
            type="button"
            class="pro-footer-action-btn logout-action-btn"
            @click.stop="logout"
            :title="$t('logout') || 'Logout'"
          >
            <v-icon size="16">mdi-power</v-icon>
          </button>
        </div>

        <!-- Rail Tooltip for Profile -->
        <v-tooltip
          v-if="main.sidebar_mini"
          activator="parent"
          location="top"
          offset="14"
          :open-delay="50"
          :close-delay="100"
          :theme="main.is_dark ? 'dark' : 'light'"
          :content-class="['pro-nav-tooltip', 'pro-nav-single-tooltip', main.is_dark ? 'pro-nav-tooltip--dark' : 'pro-nav-tooltip--light']"
        >
          <div class="pro-single-tooltip-card">
            <div
              class="pro-single-tooltip-icon-box"
              :style="{
                backgroundColor: hexToRgba('#42A5F5', main.is_dark ? 0.22 : 0.12),
                borderColor: hexToRgba('#42A5F5', 0.35)
              }"
            >
              <v-icon size="16" color="#42A5F5">mdi-account</v-icon>
            </div>
            <div class="pro-tooltip-text-col">
              <span
                class="pro-tooltip-theme-title font-weight-bold"
                :style="{ color: getAccessibleThemeColor('#42A5F5', main.is_dark) }"
              >
                {{ userName }}
              </span>
              <span
                class="pro-tooltip-parent-tag"
                :style="{ color: main.is_dark ? '#94A3B8' : '#64748B' }"
              >
                {{ userRole }}
              </span>
            </div>
          </div>
        </v-tooltip>
      </div>
    </div>
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

    userName() {
      return this.auth.user?.name || this.auth.user?.username || 'User';
    },

    userRole() {
      if (this.auth.isAdmin) return this.t('administrator') || 'Admin';
      return this.auth.user?.role || 'Staff';
    },

    userInitials() {
      const name = this.userName;
      if (!name) return 'U';
      const parts = name.split(' ').filter(Boolean);
      if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
      }
      return name.slice(0, 2).toUpperCase();
    },

    isServerConnected() {
      if (this.online && typeof this.online.isConnected !== 'undefined') {
        return !!this.online.isConnected;
      }
      return typeof window !== 'undefined' && !!window.Echo;
    },

    displayAvatarUrl() {
      return this.adaptiveLogoUrl || this.systemAvatarUrl;
    },

    systemAvatarUrl() {
      if (this.avatarLoadFailed) {
        return '/images/jupiter-logo.png';
      }
      const cs = useCompanySettings();
      if (cs.settings?.logo_invoice_body_url) return cs.settings.logo_invoice_body_url;
      if (cs.settings?.logo_url) return cs.settings.logo_url;
      return '/images/jupiter-logo.png';
    },

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
              color: '#a21ee5',
              children: [
                ['mdi-view-dashboard-outline', this.$t('dashboard'), 'warehouses', 'warehouses.view', '#a21ee5'],
                ['mdi-receipt-text-outline', this.$t('storage_invoices'), 'storage_invoices', 'warehouses.storage_invoices', '#9742f5'],
              ]
            },
            {
              id: 'inventory',
              icon: 'mdi-clipboard-text-clock-outline',
              title: this.$t('inventory'),
              color: '#6D4C41',
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
              color: '#42A5F5',
              perm: 'charges.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_charge'), 'list_charge', null, '#42A5F5'],
                ['mdi-plus-circle-outline', this.$t('add_charge'), 'add_charge', 'charges.create', '#42A5F5'],
              ]
            },
            {
              id: 'production',
              icon: 'mdi-factory',
              title: this.$t('production'),
              color: '#00897B',
              children: [
                ['mdi-format-list-bulleted', this.$t('production_list'), 'production_list', 'production.view', '#00897B'],
                ['mdi-link-variant', this.$t('charge_production_management'), 'charge_production_management', 'production.charge_management', '#26A69A'],
              ]
            },
            {
              id: 'shippings',
              icon: 'mdi-package-variant-closed',
              title: this.$t('shippings'),
              color: '#4643a0',
              perm: 'shippings.view',
              children: [
                ['mdi-view-list-outline', this.$t('list_shipping'), 'list_shipping', null, '#4643a0'],
                ['mdi-plus-circle-outline', this.$t('add_shipping'), 'add_shipping', 'shippings.create', '#4643a0'],
              ]
            },
            {
              id: 'transfers',
              icon: 'mdi-swap-horizontal',
              title: this.$t('warehouse_transfers'),
              color: '#F9A825',
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
              color: '#1565C0',
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
              color: '#E65100',
              children: [
                ['mdi-file-document-multiple', this.$t('list_supplier_invoice'), 'list_supplier_invoice', 'supplier_invoices.view', '#E65100'],
                ['mdi-cash-register', this.$t('supplier_settlement'), 'supplier_settlement', 'supplier_invoices.settlement', '#F57C00'],
              ]
            },
            {
              id: 'client_invoices',
              icon: 'mdi-account-cash',
              title: this.$t('client_invoics'),
              color: '#00838F',
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
                ['mdi-account-tie', this.$t('client_management'), 'client_management', 'settings.clients', '#00838F'],
                ['mdi-truck-delivery', this.$t('supplier_management'), 'supplier_management', 'settings.suppliers', '#E65100'],
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
        collapse_sidebar: this.lang === 'es' ? 'Plegar barra lateral' : this.lang === 'fr' ? 'Réduire la barre latérale' : 'Collapse sidebar',
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

    toggleSidebar() {
      this.main.sidebar_mini = !this.main.sidebar_mini;
    },

    handleBrandClick() {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      } else {
        this.$router.push({ name: 'index' }).catch(() => {});
      }
    },

    openSearchFromRail() {
      this.main.sidebar_mini = false;
      this.$nextTick(() => {
        const input = this.$el?.querySelector('.pro-search-text-field input');
        if (input) input.focus();
      });
    },

    handleProfileClick() {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      } else {
        this.navigateTo('profile_settings');
      }
    },

    handleGroupClick(groupId) {
      this.toggleGroup(groupId);
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

    isRouteActive(routeName) {
      if (this.$route.name === routeName) {
        if (routeName === 'list_charge' && this.$route.query.tab === 'add') return false;
        if (routeName === 'list_shipping' && this.$route.query.tab === 'add') return false;
        return true;
      }
      if (routeName === 'add_charge' && (this.$route.name === 'list_charge' && this.$route.query.tab === 'add')) return true;
      if (routeName === 'add_shipping' && (this.$route.name === 'list_shipping' && this.$route.query.tab === 'add')) return true;
      if (routeName === 'list_charge' && (this.$route.name === 'view_charge' || this.$route.name === 'edit_charge')) return true;
      if (routeName === 'list_shipping' && (this.$route.name === 'view_shipping' || this.$route.name === 'edit_shipping')) return true;
      return false;
    },

    isAnyChildActive(item) {
      if (!item.children) return false;
      return item.children.some(c => this.isRouteActive(c[2]));
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

    getAccessibleThemeColor(hexColor, isDark) {
      if (!hexColor) return isDark ? '#FFFFFF' : '#0F172A';

      const darkMap = {
        '#4643a0': '#818CF8', // Shipping -> Luminous indigo
        '#372e7d': '#818CF8',
        '#6c66bb': '#9B98E8',
        '#6d4c41': '#D7CCC8', // Inventory -> Light warm beige
        '#8d6e63': '#D7CCC8',
        '#4e342e': '#BCAAA4',
        '#00897b': '#2DD4BF', // Production -> Luminous teal
        '#00695c': '#2DD4BF',
        '#26a69a': '#2DD4BF',
        '#a21ee5': '#C084FC', // Warehouse -> Luminous purple
        '#9742f5': '#C084FC',
        '#c015b4': '#E879F9',
        '#e65100': '#FB923C', // Invoices -> Vibrant orange
        '#f57c00': '#FB923C',
        '#00838f': '#22D3EE', // Client Invoices -> Vibrant cyan
        '#006064': '#22D3EE',
        '#f9a825': '#FDE047', // Transfer -> Vibrant yellow/amber
        '#f57f17': '#FDE047',
        '#388e3c': '#4ADE80', // Guano -> Vibrant emerald
        '#2e7d32': '#4ADE80',
        '#546e7a': '#94A3B8', // Package -> Slate
        '#42a5f5': '#38BDF8', // Charge/Workforce -> Sky blue
        '#1e88e5': '#60A5FA',
        '#1565c0': '#60A5FA',
        '#0288d1': '#38BDF8',
        '#3949ab': '#818CF8',
      };

      const lightMap = {
        '#4643a0': '#372e7d',
        '#6c66bb': '#372e7d',
        '#6d4c41': '#4E342E',
        '#8d6e63': '#4E342E',
        '#00897b': '#00695C',
        '#26a69a': '#00695C',
        '#a21ee5': '#7E22CE',
        '#9742f5': '#7E22CE',
        '#c015b4': '#9333EA',
        '#e65100': '#C2410C',
        '#f57c00': '#C2410C',
        '#00838f': '#0E7490',
        '#006064': '#0E7490',
        '#f9a825': '#B45309',
        '#f57f17': '#B45309',
        '#388e3c': '#15803D',
        '#2e7d32': '#15803D',
        '#546e7a': '#334155',
        '#42a5f5': '#0284C7',
        '#1e88e5': '#1D4ED8',
        '#1565c0': '#1E40AF',
        '#0288d1': '#0369A1',
        '#3949ab': '#312E81',
      };

      const lower = String(hexColor).toLowerCase();
      if (isDark) {
        return darkMap[lower] || hexColor;
      } else {
        return lightMap[lower] || hexColor;
      }
    },

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

          const corners = [
            0,
            (w - 1) * 4,
            (h - 1) * w * 4,
            ((h - 1) * w + (w - 1)) * 4,
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

          if (whiteCount >= 3) {
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

    navigateTo(routeName) {
      if (routeName) {
        this.$router.push({ name: routeName }).catch(() => {});
      }
    },

    async logout() {
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
/* =========================================================================
 * PRO NAVIGATION DRAWER (Engineered for Instant 60fps GPU Transitions)
 * ========================================================================= */
.pro-nav-drawer {
  user-select: none;
  z-index: 1001 !important;
  will-change: width;
}

/* ── Dark Theme Surfaces ── */
.drawer-theme-dark {
  background: #0B0F19 !important;
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
  color: #F8FAFC !important;
}

/* ── Light Theme Surfaces ── */
.drawer-theme-light {
  background: #FFFFFF !important;
  border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
  color: #0F172A !important;
}

/* =========================================================================
 * 1. HEADER & BRAND
 * ========================================================================= */
.pro-header-area {
  padding: 14px 12px 10px;
  position: relative;
}

.pro-brand-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px;
  border-radius: 12px;
  transition: background-color 0.15s ease;
}
.pro-brand-row:hover {
  background-color: rgba(66, 165, 245, 0.06);
}

.pro-brand-orb-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(66, 165, 245, 0.1);
  border: 1px solid rgba(66, 165, 245, 0.22);
}

.pro-brand-logo-img {
  width: 26px;
  height: 26px;
  object-fit: contain;
}

.pro-brand-status-dot {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  border: 1.5px solid #0B0F19;
}
.status-connected {
  background: #00E676;
  box-shadow: 0 0 6px rgba(0, 230, 118, 0.6);
}
.status-cut {
  background: #FF5252;
}

.pro-brand-text-block {
  display: flex;
  flex-direction: column;
  min-width: 0;
  white-space: nowrap;
  transition: opacity 0.14s ease, transform 0.14s ease;
}

.pro-brand-name-line {
  display: flex;
  align-items: center;
  gap: 6px;
}
.pro-brand-title {
  font-size: 15px;
  font-weight: 800;
  letter-spacing: -0.2px;
  line-height: 1.2;
}
.drawer-theme-dark .pro-brand-title { color: #FFFFFF; }
.drawer-theme-light .pro-brand-title { color: #0F172A; }

.pro-brand-edition-chip {
  font-size: 9.5px;
  font-weight: 800;
  padding: 1px 5px;
  border-radius: 6px;
  letter-spacing: 0.4px;
  background: linear-gradient(135deg, #1565C0 0%, #42A5F5 100%);
  color: #FFFFFF;
}

.pro-brand-meta-line {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 1px;
}
.pro-meta-pulse-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
}
.pulse-live { background: #00E676; box-shadow: 0 0 4px #00E676; }
.pulse-cut { background: #FF5252; }

.pro-meta-label {
  font-size: 11px;
  opacity: 0.65;
  font-weight: 500;
}

.pro-collapse-btn {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  color: inherit;
  opacity: 0.55;
  transition: opacity 0.15s ease, background-color 0.15s ease;
}
.pro-collapse-btn:hover {
  opacity: 1;
  background-color: rgba(255, 255, 255, 0.08);
}
.drawer-theme-light .pro-collapse-btn:hover {
  background-color: rgba(0, 0, 0, 0.06);
}

/* =========================================================================
 * 2. SEARCH AREA
 * ========================================================================= */
.pro-search-area {
  padding: 0 12px 6px;
}
.pro-search-input-wrap {
  width: 100%;
}
.pro-search-text-field :deep(.v-field) {
  border-radius: 10px !important;
  font-size: 12.5px !important;
}

.pro-rail-search-box {
  display: flex;
  justify-content: center;
}
.pro-rail-search-btn {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid rgba(var(--v-border-color), 0.12);
  cursor: pointer;
  color: inherit;
  opacity: 0.75;
  transition: all 0.15s ease;
}
.pro-rail-search-btn:hover {
  opacity: 1;
  background: rgba(66, 165, 245, 0.1);
  border-color: #42A5F5;
  color: #42A5F5;
}

/* Divider */
.pro-nav-divider {
  height: 1px;
  background: rgba(var(--v-border-color), 0.08);
}

/* =========================================================================
 * 3. NAVIGATION LIST (Scroll Area)
 * ========================================================================= */
.pro-nav-scroll-area {
  padding: 4px 8px 12px;
  overflow-y: auto;
  overflow-x: hidden;
  height: calc(100% - 150px);
}
.pro-nav-scroll-area::-webkit-scrollbar {
  width: 4px;
}
.pro-nav-scroll-area::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.12);
  border-radius: 999px;
}
.drawer-theme-light .pro-nav-scroll-area::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.12);
}

.pro-section-group {
  margin-bottom: 8px;
}

.pro-section-header {
  padding: 6px 8px 4px;
  display: flex;
  align-items: center;
}
.pro-section-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  opacity: 0.55;
  white-space: nowrap;
  transition: opacity 0.12s ease;
}
.pro-rail-micro-separator {
  display: none;
  width: 24px;
  height: 1px;
  margin: 6px auto;
  background: rgba(var(--v-border-color), 0.15);
}

.pro-section-items-col {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

/* Nav Item Root (Used by Leaf & Group Header) */
.pro-nav-item {
  position: relative;
  display: flex;
  align-items: center;
  height: 38px;
  border-radius: 10px;
  padding: 0 8px;
  gap: 10px;
  transition: background-color 0.15s ease, transform 0.15s ease;
  overflow: hidden;
}

.pro-item-active-bar {
  position: absolute;
  left: 0;
  top: 6px;
  bottom: 6px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  opacity: 0;
  transition: opacity 0.15s ease, transform 0.15s ease;
  transform: scaleY(0.4);
}
.pro-nav-item.is-active .pro-item-active-bar {
  opacity: 1;
  transform: scaleY(1);
}

.pro-icon-box {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background-color 0.15s ease, transform 0.15s ease;
}
.pro-nav-item:hover .pro-icon-box {
  transform: scale(1.05);
}

.pro-item-label-wrap {
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  transition: opacity 0.12s ease, transform 0.12s ease;
}

.pro-item-title {
  font-size: 13px;
  font-weight: 550;
  letter-spacing: 0.1px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pro-group-count-pill {
  font-size: 9.5px;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 6px;
  letter-spacing: 0.2px;
  line-height: 1.2;
}

.pro-group-chevron {
  opacity: 0.45;
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.15s ease;
}
.chevron-rotated {
  transform: rotate(180deg);
  opacity: 0.85;
}

/* Hover & Active States (Dark Theme) */
.drawer-theme-dark .pro-nav-item .pro-item-title {
  color: #F1F5F9;
}
.drawer-theme-dark .pro-nav-item:hover {
  background-color: rgba(255, 255, 255, 0.05);
}
.drawer-theme-dark .pro-nav-item:hover .pro-item-title {
  color: #FFFFFF;
}
.drawer-theme-dark .pro-nav-item.is-active {
  background-color: var(--accent-bg-active, rgba(66, 165, 245, 0.14));
}
.drawer-theme-dark .pro-nav-item.is-active .pro-item-title {
  color: #FFFFFF;
  font-weight: 700;
}

/* Hover & Active States (Light Theme) */
.drawer-theme-light .pro-nav-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}
.drawer-theme-light .pro-nav-item:hover .pro-item-title {
  color: #0F172A;
}
.drawer-theme-light .pro-nav-item.is-active {
  background-color: var(--accent-bg-active, rgba(66, 165, 245, 0.12));
}
.drawer-theme-light .pro-nav-item.is-active .pro-item-title {
  color: #0F172A;
  font-weight: 700;
}

/* ── 3A. AI Prompt Special Item ── */
.pro-ai-card {
  border: 1px solid rgba(66, 165, 245, 0.35);
  background: linear-gradient(135deg, rgba(21, 101, 192, 0.15) 0%, rgba(66, 165, 245, 0.08) 100%);
  margin: 2px 0 4px;
}
.drawer-theme-dark .pro-ai-card:hover {
  border-color: rgba(66, 165, 245, 0.65);
  box-shadow: 0 0 16px rgba(66, 165, 245, 0.2);
}
.drawer-theme-light .pro-ai-card {
  background: linear-gradient(135deg, rgba(21, 101, 192, 0.08) 0%, rgba(66, 165, 245, 0.04) 100%);
  border-color: rgba(66, 165, 245, 0.3);
}
.pro-ai-icon-box {
  background: rgba(66, 165, 245, 0.2) !important;
}
.pro-ai-gradient-title {
  font-weight: 700;
  background: linear-gradient(135deg, #42A5F5 0%, #00E5FF 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.drawer-theme-light .pro-ai-gradient-title {
  background: linear-gradient(135deg, #1565C0 0%, #0288D1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.pro-ai-sparkle-pill {
  font-size: 9px;
  font-weight: 800;
  padding: 1px 5px;
  border-radius: 999px;
  background: linear-gradient(135deg, #00E5FF, #42A5F5);
  color: #ffffff;
  letter-spacing: 0.3px;
  line-height: 1.2;
}

/* ── 3C. Sub-items Accordion (Pure 60fps CSS Grid) ── */
.pro-subnav-accordion {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}
.pro-subnav-accordion.accordion-expanded {
  grid-template-rows: 1fr;
}
.pro-subnav-inner {
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 2px 0 4px 18px;
}

.pro-subnav-row {
  display: flex;
  align-items: center;
  height: 32px;
  border-radius: 8px;
  padding: 0 8px;
  gap: 8px;
  position: relative;
  transition: background-color 0.15s ease;
}
.pro-subnav-tree-line {
  position: absolute;
  left: -8px;
  top: 0;
  bottom: 0;
  width: 1.5px;
  background: rgba(var(--v-border-color), 0.12);
}
.pro-subnav-icon-pill {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.pro-subnav-title {
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  opacity: 0.85;
}

.drawer-theme-dark .pro-subnav-row .pro-subnav-title {
  color: #CBD5E1;
}
.drawer-theme-dark .pro-subnav-row:hover {
  background-color: rgba(255, 255, 255, 0.05);
}
.drawer-theme-dark .pro-subnav-row:hover .pro-subnav-title {
  color: #FFFFFF;
}
.drawer-theme-dark .pro-subnav-row.is-active {
  background-color: var(--child-bg-active, rgba(66, 165, 245, 0.12));
}
.drawer-theme-dark .pro-subnav-row.is-active .pro-subnav-title {
  color: var(--child-color, #42A5F5);
  font-weight: 700;
  opacity: 1;
}

.drawer-theme-light .pro-subnav-row:hover {
  background-color: rgba(0, 0, 0, 0.04);
}
.drawer-theme-light .pro-subnav-row.is-active {
  background-color: var(--child-bg-active, rgba(66, 165, 245, 0.12));
}
.drawer-theme-light .pro-subnav-row.is-active .pro-subnav-title {
  color: var(--child-color, #1565C0);
  font-weight: 700;
  opacity: 1;
}

/* =========================================================================
 * 4. USER PROFILE & FOOTER
 * ========================================================================= */
.pro-footer-area {
  padding: 4px 8px 10px;
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
}

.pro-user-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 8px;
  border-radius: 12px;
  transition: background-color 0.15s ease;
}
.pro-user-row:hover {
  background-color: rgba(255, 255, 255, 0.05);
}
.drawer-theme-light .pro-user-row:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.pro-user-avatar {
  background: linear-gradient(135deg, #1565C0 0%, #42A5F5 100%) !important;
  color: #FFFFFF !important;
  font-weight: 800;
  font-size: 11px;
  flex-shrink: 0;
}

.pro-user-info-wrap {
  display: flex;
  flex-direction: column;
  min-width: 0;
  white-space: nowrap;
  transition: opacity 0.12s ease, transform 0.12s ease;
}
.pro-user-name {
  font-size: 13px;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.25;
}
.pro-user-role {
  font-size: 10px;
  opacity: 0.6;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.pro-footer-actions {
  display: flex;
  align-items: center;
  gap: 2px;
}
.pro-footer-action-btn {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  color: inherit;
  opacity: 0.6;
  transition: opacity 0.15s ease, background-color 0.15s ease, transform 0.15s ease;
}
.pro-footer-action-btn:hover {
  opacity: 1;
  background-color: rgba(255, 255, 255, 0.08);
  transform: scale(1.08);
}
.drawer-theme-light .pro-footer-action-btn:hover {
  background-color: rgba(0, 0, 0, 0.06);
}
.logout-action-btn:hover {
  color: #EF5350 !important;
}

/* =========================================================================
 * RAIL MODE RULES (Zero-Jank GPU Transforms)
 * ========================================================================= */
.drawer-is-rail .pro-brand-text-block,
.drawer-is-rail .pro-collapse-btn,
.drawer-is-rail .pro-item-label-wrap,
.drawer-is-rail .pro-subnav-accordion,
.drawer-is-rail .pro-user-info-wrap,
.drawer-is-rail .pro-footer-actions,
.drawer-is-rail .pro-section-title {
  opacity: 0 !important;
  transform: translateX(-6px) !important;
  pointer-events: none !important;
  max-width: 0 !important;
  overflow: hidden !important;
  margin: 0 !important;
  padding: 0 !important;
}

.drawer-is-rail .pro-section-header {
  justify-content: center;
  padding: 4px 0;
}
.drawer-is-rail .pro-rail-micro-separator {
  display: block !important;
}

.drawer-is-rail .pro-nav-item {
  justify-content: center;
  padding: 0;
}
.drawer-is-rail .pro-user-row {
  justify-content: center;
  padding: 6px 0;
}
.drawer-is-rail .pro-brand-row {
  justify-content: center;
  padding: 4px 0;
}

/* ── Rail Subnavs Column (Mini icons under expanded group in rail mode) ── */
.pro-rail-subnavs-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 6px 0;
  margin: 4px 8px 6px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px dashed rgba(255, 255, 255, 0.12);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-theme-light .pro-rail-subnavs-col {
  background: rgba(0, 0, 0, 0.03);
  border-color: rgba(0, 0, 0, 0.1);
}

.pro-rail-sub-item {
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.15s ease, background-color 0.15s ease;
}
.pro-rail-sub-item:hover {
  transform: scale(1.1);
  background-color: rgba(255, 255, 255, 0.08);
}
.drawer-theme-light .pro-rail-sub-item:hover {
  background-color: rgba(0, 0, 0, 0.06);
}

.pro-rail-sub-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid transparent;
  transition: all 0.15s ease;
}
.pro-rail-sub-item.is-active .pro-rail-sub-icon-box {
  box-shadow: 0 0 10px rgba(66, 165, 245, 0.45);
}
</style>

<style>
/* =========================================================================
 * GLOBAL UNSCOPED OVERLAY RULES (Teleported Tooltips & Flyouts)
 * Sleek, high-contrast, theme-color aware tooltip design system
 * ========================================================================= */
.pro-nav-tooltip,
.v-overlay__content.pro-nav-tooltip,
.cyber-tooltip-dark,
.v-overlay__content.cyber-tooltip-dark {
  padding: 0 !important;
  border-radius: 12px !important;
  backdrop-filter: blur(16px) !important;
  -webkit-backdrop-filter: blur(16px) !important;
  pointer-events: auto !important;
  background: rgba(15, 23, 42, 0.96) !important;
  color: #F8FAFC !important;
  border: 1px solid rgba(255, 255, 255, 0.15) !important;
  box-shadow: 0 14px 36px -4px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
}

.pro-nav-tooltip--light,
.v-overlay__content.pro-nav-tooltip--light,
.v-theme--light .pro-nav-tooltip,
.pro-nav-tooltip.theme-light,
.cyber-tooltip-light,
.v-overlay__content.cyber-tooltip-light {
  background: rgba(255, 255, 255, 0.98) !important;
  color: #0F172A !important;
  border: 1px solid rgba(15, 23, 42, 0.12) !important;
  box-shadow: 0 14px 36px -4px rgba(15, 23, 42, 0.16), 0 0 0 1px rgba(15, 23, 42, 0.04) !important;
}

/* ── Single Leaf & Sub-Nav Tooltip Card ── */
.pro-single-tooltip-card,
.pro-subnav-tooltip-card {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 8px 14px !important;
  min-height: 44px !important;
  white-space: nowrap !important;
  user-select: none !important;
  box-sizing: border-box !important;
}

.pro-single-tooltip-icon-box {
  width: 28px !important;
  height: 28px !important;
  border-radius: 8px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: 1px solid !important;
  flex-shrink: 0 !important;
  transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
}

.pro-tooltip-text-col {
  display: flex !important;
  flex-direction: column !important;
  justify-content: center !important;
  gap: 2px !important;
  min-width: 0 !important;
}

.pro-tooltip-title-row {
  display: flex !important;
  align-items: center !important;
  gap: 7px !important;
}

.pro-tooltip-theme-title {
  font-size: 13px !important;
  font-weight: 700 !important;
  line-height: 1.25 !important;
  letter-spacing: 0.15px !important;
}

.pro-tooltip-parent-tag {
  font-size: 11px !important;
  font-weight: 500 !important;
  line-height: 1.2 !important;
  opacity: 0.9 !important;
}

.pro-subnav-tooltip-badge {
  font-size: 9.5px !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  padding: 2px 8px !important;
  border-radius: 999px !important;
  border: 1px solid transparent !important;
  margin-left: 10px !important;
  flex-shrink: 0 !important;
}

/* ── Group Card (Parent Nav with Sub-Navs) ── */
.pro-group-tooltip-card {
  padding: 12px 14px !important;
  min-width: 230px !important;
  max-width: 300px !important;
  user-select: none !important;
  box-sizing: border-box !important;
}

.pro-group-tooltip-header {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding-bottom: 9px !important;
  border-bottom: 1px solid transparent !important;
}

.pro-group-tooltip-header-icon-box {
  width: 32px !important;
  height: 32px !important;
  border-radius: 8px !important;
  border: 1px solid !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  flex-shrink: 0 !important;
}

.pro-group-tooltip-title {
  font-size: 13.5px !important;
  font-weight: 700 !important;
  line-height: 1.25 !important;
  letter-spacing: 0.15px !important;
}

.pro-group-tooltip-subtitle {
  font-size: 11px !important;
  font-weight: 500 !important;
  line-height: 1.2 !important;
  margin-top: 1px !important;
}

.pro-group-tooltip-badge {
  font-size: 10.5px !important;
  font-weight: 700 !important;
  padding: 2px 8px !important;
  border-radius: 999px !important;
  border: 1px solid !important;
  margin-left: auto !important;
  flex-shrink: 0 !important;
}

.pro-group-tooltip-divider {
  width: 100% !important;
  height: 1px !important;
  border-top-width: 1px !important;
  border-top-style: solid !important;
  margin: 6px 0 8px 0 !important;
}

.pro-group-tooltip-list {
  display: flex !important;
  flex-direction: column !important;
  gap: 4px !important;
}

.pro-group-tooltip-item {
  display: flex !important;
  align-items: center !important;
  gap: 11px !important;
  padding: 7px 10px !important;
  border-radius: 8px !important;
  transition: background-color 0.14s ease, transform 0.14s ease !important;
  text-decoration: none !important;
}
.pro-group-tooltip-item:hover {
  background-color: var(--child-hover-bg, rgba(255, 255, 255, 0.08)) !important;
  transform: translateX(3px) !important;
}
.pro-group-tooltip-item.is-active {
  background-color: var(--child-active-bg, rgba(255, 255, 255, 0.14)) !important;
}

.pro-group-tooltip-child-icon {
  width: 26px !important;
  height: 26px !important;
  border-radius: 7px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: 1px solid !important;
  flex-shrink: 0 !important;
}

.pro-group-tooltip-child-title {
  font-size: 12.5px !important;
  font-weight: 600 !important;
  line-height: 1.25 !important;
  letter-spacing: 0.1px !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  flex: 1 !important;
}

.pro-group-tooltip-active-dot {
  width: 7px !important;
  height: 7px !important;
  border-radius: 50% !important;
  margin-left: auto !important;
  flex-shrink: 0 !important;
}

.pro-group-tooltip-arrow {
  margin-left: auto !important;
  flex-shrink: 0 !important;
}
</style>
