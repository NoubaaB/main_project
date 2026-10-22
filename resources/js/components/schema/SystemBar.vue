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
      <!-- Left: Toggle, Navigation History Stepper & Route Breadcrumb Context -->
      <div class="systembar-left">
        <v-btn
          v-if="auth.is_auth"
          icon
          variant="tonal"
          color="primary"
          size="36"
          class="systembar-nav-toggle-btn"
          @click.prevent="main.sidebar_mini = !main.sidebar_mini"
        >
          <v-icon size="19">{{ main.sidebar_mini ? 'mdi-menu' : 'mdi-menu-open' }}</v-icon>
          <v-tooltip activator="parent" location="bottom">
            {{ main.sidebar_mini ? t('expand_sidebar') : t('collapse_sidebar') }}
          </v-tooltip>
        </v-btn>

        <!-- Navigation History Stepper (Back, Forward, History Menu) -->
        <div v-if="auth.is_auth" class="systembar-stepper-cluster" :class="main.is_dark ? 'stepper-dark' : 'stepper-light'">
          <!-- Go Back Button -->
          <v-btn
            icon
            variant="text"
            size="30"
            class="stepper-action-btn"
            :disabled="!navHistory?.canGoBack"
            @click="navHistory?.goBack?.($router)"
            id="sys-nav-back"
          >
            <v-icon size="18">mdi-arrow-left</v-icon>
            <v-tooltip activator="parent" location="bottom">
              <div class="font-weight-bold">{{ $t('navigate_back') }} (Alt + ←)</div>
              <div v-if="navHistory?.previousPage" class="text-caption text-truncate" style="max-width: 220px; opacity: .85">
                {{ navHistory?.previousPage?.title }}
              </div>
            </v-tooltip>
          </v-btn>

          <!-- Go Forward Button -->
          <v-btn
            icon
            variant="text"
            size="30"
            class="stepper-action-btn"
            :disabled="!navHistory?.canGoForward"
            @click="navHistory?.goForward?.($router)"
            id="sys-nav-forward"
          >
            <v-icon size="18">mdi-arrow-right</v-icon>
            <v-tooltip activator="parent" location="bottom">
              <div class="font-weight-bold">{{ $t('navigate_forward') }} (Alt + →)</div>
              <div v-if="navHistory?.nextPage" class="text-caption text-truncate" style="max-width: 220px; opacity: .85">
                {{ navHistory?.nextPage?.title }}
              </div>
            </v-tooltip>
          </v-btn>

          <div class="stepper-sep"></div>

          <!-- History & Recents Dropdown Menu -->
          <v-menu
            v-model="navHistory.historyMenuOpen"
            transition="scale-transition"
            :close-on-content-click="false"
            :theme="main.is_dark ? 'dark' : 'light'"
            offset="8"
            location="bottom start"
          >
            <template v-slot:activator="{ props }">
              <v-btn
                v-bind="props"
                icon
                variant="text"
                size="30"
                class="stepper-action-btn history-pill-btn"
                :class="{ 'history-pill-active': navHistory.historyMenuOpen }"
                id="sys-nav-history"
              >
                <v-icon size="16">mdi-history</v-icon>
                <v-tooltip activator="parent" location="bottom">
                  {{ $t('navigation_history') }} (Ctrl + H)
                </v-tooltip>
              </v-btn>
            </template>

            <v-card
              class="nav-history-panel"
              :class="main.is_dark ? 'panel-dark' : 'panel-light'"
              width="430"
              max-width="95vw"
              rounded="xl"
              elevation="18"
            >
              <!-- Panel Header -->
              <div class="nav-history-header">
                <div class="d-flex align-center ga-2">
                  <div class="nav-history-icon-box">
                    <v-icon size="18" color="primary">mdi-compass-outline</v-icon>
                  </div>
                  <div>
                    <div class="text-subtitle-2 font-weight-bold leading-tight">{{ $t('navigation_history') }}</div>
                    <div class="text-caption text-medium-emphasis">{{ $t('quick_jump') }} • {{ navHistory.recentPages.length }} {{ $t('recent_pages').toLowerCase() }}</div>
                  </div>
                </div>

                <div class="d-flex align-center ga-1 ml-auto">
                  <v-btn
                    v-if="navHistory.recentPages.length > 1 || navHistory.stack.length > 1"
                    icon
                    size="x-small"
                    variant="text"
                    color="error"
                    @click="navHistory.clearHistory()"
                    :title="$t('clear_history')"
                  >
                    <v-icon size="16">mdi-trash-can-outline</v-icon>
                  </v-btn>
                  <v-btn
                    icon
                    size="x-small"
                    variant="text"
                    @click="navHistory.historyMenuOpen = false"
                  >
                    <v-icon size="16">mdi-close</v-icon>
                  </v-btn>
                </div>
              </div>

              <!-- Filter Search Input (AddCharge style) -->
              <div class="px-3 pt-2 pb-1">
                <v-text-field
                  v-model="historySearchQuery"
                  density="compact"
                  variant="outlined"
                  color="primary"
                  rounded="lg"
                  hide-details
                  clearable
                  prepend-inner-icon="mdi-magnify"
                  :placeholder="$t('search_history')"
                  class="history-search-input"
                ></v-text-field>
              </div>

              <!-- Segmented View Tabs -->
              <div class="px-3 py-1">
                <v-btn-toggle
                  v-model="historyTab"
                  mandatory
                  density="compact"
                  variant="outlined"
                  color="primary"
                  rounded="lg"
                  divided
                  class="w-100 history-tab-toggle"
                >
                  <v-btn value="recents" size="small" class="flex-grow-1 text-none font-weight-medium">
                    <v-icon size="15" start>mdi-clock-outline</v-icon>
                    {{ $t('recents_tab') }}
                  </v-btn>
                  <v-btn value="trail" size="small" class="flex-grow-1 text-none font-weight-medium">
                    <v-icon size="15" start>mdi-swap-horizontal</v-icon>
                    {{ $t('trail_tab') }}
                  </v-btn>
                  <v-btn value="pinned" size="small" class="flex-grow-1 text-none font-weight-medium">
                    <v-icon size="15" start>mdi-pin</v-icon>
                    {{ $t('pinned_tab') }}
                    <v-badge v-if="navHistory.pinnedPages.length" :content="navHistory.pinnedPages.length" color="primary" inline></v-badge>
                  </v-btn>
                </v-btn-toggle>
              </div>

              <v-divider class="my-1"></v-divider>

              <!-- Panel Body (Scrollable) -->
              <div class="nav-history-body">
                <!-- TAB 1: RECENTS -->
                <div v-if="historyTab === 'recents'">
                  <div v-if="filteredRecentPages.length" class="history-items-list pa-2">
                    <div
                      v-for="item in filteredRecentPages"
                      :key="'recent-' + item.fullPath"
                      class="nav-history-item"
                      :class="{ 'item-current': isCurrentPage(item) }"
                      @click="navHistory.navigateTo(item, $router)"
                    >
                      <div class="item-icon-box" :style="{ backgroundColor: hexToRgba(item.color, 0.14), color: item.color }">
                        <v-icon size="18">{{ item.icon }}</v-icon>
                      </div>
                      <div class="item-text-box">
                        <div class="item-title-row">
                          <span class="item-title">{{ item.title }}</span>
                          <v-chip v-if="isCurrentPage(item)" size="x-small" color="primary" variant="flat" class="ml-1 font-weight-bold">
                            {{ $t('you_are_here') }}
                          </v-chip>
                        </div>
                        <div class="item-sub-row">
                          <span class="item-section" :style="{ color: item.color }">{{ item.section }}</span>
                          <span class="item-dot">•</span>
                          <span class="item-time">{{ navHistory.getRelativeTime(item.visitedAt) }}</span>
                        </div>
                      </div>
                      <v-btn
                        icon
                        size="x-small"
                        variant="text"
                        :color="navHistory.isPinned(item.fullPath) ? 'primary' : 'grey'"
                        class="item-pin-btn"
                        @click.stop="navHistory.togglePin(item)"
                        :title="navHistory.isPinned(item.fullPath) ? $t('unpin_page') : $t('pin_page')"
                      >
                        <v-icon size="15">{{ navHistory.isPinned(item.fullPath) ? 'mdi-pin' : 'mdi-pin-outline' }}</v-icon>
                      </v-btn>
                    </div>
                  </div>
                  <div v-else class="empty-history pa-6 text-center text-medium-emphasis">
                    <v-icon size="36" color="grey" class="mb-2">mdi-history</v-icon>
                    <div class="text-caption font-weight-medium">{{ $t('no_recent_pages') }}</div>
                  </div>
                </div>

                <!-- TAB 2: TRAIL (History Stack: Forward, Current, Back) -->
                <div v-else-if="historyTab === 'trail'" class="pa-2">
                  <!-- Forward Stack (Next pages) -->
                  <div v-if="navHistory.forwardStack.length" class="mb-2">
                    <div class="trail-header text-caption font-weight-bold text-success d-flex align-center ga-1 px-2 mb-1">
                      <v-icon size="14">mdi-arrow-right</v-icon>
                      <span>{{ $t('forward_history') }}</span>
                    </div>
                    <div
                      v-for="(fItem, fIdx) in navHistory.forwardStack"
                      :key="'fwd-' + fItem.id"
                      class="nav-history-item trail-item-forward"
                      @click="navHistory.jumpTo(navHistory.currentIndex + 1 + fIdx, $router)"
                    >
                      <v-icon size="16" color="success" class="mr-2">mdi-arrow-right-thin</v-icon>
                      <div class="item-text-box">
                        <span class="item-title">{{ fItem.title }}</span>
                        <span class="item-section text-caption" :style="{ color: fItem.color }">{{ fItem.section }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Current Page (Here) -->
                  <div v-if="navHistory.currentPage" class="trail-current-box pa-2.5 rounded-lg mb-2" :style="{ borderColor: navHistory.currentPage.color, background: hexToRgba(navHistory.currentPage.color, 0.08) }">
                    <div class="d-flex align-center ga-2">
                      <v-icon size="20" :style="{ color: navHistory.currentPage.color }">{{ navHistory.currentPage.icon }}</v-icon>
                      <div class="flex-grow-1 min-w-0">
                        <div class="text-caption font-weight-bold text-uppercase" :style="{ color: navHistory.currentPage.color }">
                          ● {{ $t('you_are_here') }}
                        </div>
                        <div class="font-weight-bold text-body-2 text-truncate">{{ navHistory.currentPage.title }}</div>
                      </div>
                    </div>
                  </div>

                  <!-- Back Stack (Previous pages) -->
                  <div v-if="navHistory.backStack.length">
                    <div class="trail-header text-caption font-weight-bold text-medium-emphasis d-flex align-center ga-1 px-2 mb-1">
                      <v-icon size="14">mdi-arrow-left</v-icon>
                      <span>{{ $t('back_history') }}</span>
                    </div>
                    <div
                      v-for="(bItem, bIdx) in navHistory.backStack"
                      :key="'back-' + bItem.id"
                      class="nav-history-item trail-item-back"
                      @click="navHistory.jumpTo(navHistory.currentIndex - 1 - bIdx, $router)"
                    >
                      <v-icon size="16" color="grey" class="mr-2">mdi-arrow-left-thin</v-icon>
                      <div class="item-text-box">
                        <span class="item-title">{{ bItem.title }}</span>
                        <span class="item-section text-caption" :style="{ color: bItem.color }">{{ bItem.section }}</span>
                      </div>
                    </div>
                  </div>

                  <div v-if="!navHistory.forwardStack.length && !navHistory.backStack.length" class="empty-history pa-6 text-center text-medium-emphasis">
                    <v-icon size="36" color="grey" class="mb-2">mdi-swap-horizontal</v-icon>
                    <div class="text-caption font-weight-medium">{{ $t('no_history') }}</div>
                  </div>
                </div>

                <!-- TAB 3: PINNED WORKSPACES -->
                <div v-else-if="historyTab === 'pinned'">
                  <div v-if="navHistory.pinnedPages.length" class="history-items-list pa-2">
                    <div
                      v-for="pin in navHistory.pinnedPages"
                      :key="'pin-' + pin.fullPath"
                      class="nav-history-item"
                      @click="navHistory.navigateTo(pin, $router)"
                    >
                      <div class="item-icon-box" :style="{ backgroundColor: hexToRgba(pin.color, 0.14), color: pin.color }">
                        <v-icon size="18">{{ pin.icon }}</v-icon>
                      </div>
                      <div class="item-text-box">
                        <span class="item-title">{{ pin.title }}</span>
                        <span class="item-section text-caption" :style="{ color: pin.color }">{{ pin.section }}</span>
                      </div>
                      <v-btn
                        icon
                        size="x-small"
                        variant="text"
                        color="primary"
                        class="item-pin-btn"
                        @click.stop="navHistory.togglePin(pin)"
                        :title="$t('unpin_page')"
                      >
                        <v-icon size="16">mdi-pin-off</v-icon>
                      </v-btn>
                    </div>
                  </div>
                  <div v-else class="empty-history pa-6 text-center text-medium-emphasis">
                    <v-icon size="36" color="grey" class="mb-2">mdi-pin-outline</v-icon>
                    <div class="text-caption font-weight-medium">{{ $t('no_recent_pages') }}</div>
                  </div>
                </div>
              </div>

              <!-- Footer Tip -->
              <div class="nav-history-footer">
                <span class="text-caption text-medium-emphasis">
                  <kbd>Alt</kbd> + <kbd>←</kbd> {{ $t('navigate_back') }} &nbsp;•&nbsp; <kbd>Alt</kbd> + <kbd>→</kbd> {{ $t('navigate_forward') }}
                </span>
              </div>
            </v-card>
          </v-menu>
        </div>

        <!-- Dynamic Interactive Context Breadcrumb (with Sibling Switcher) -->
        <v-menu
          v-if="currentContext && currentContext.siblings && currentContext.siblings.length"
          transition="scale-transition"
          :theme="main.is_dark ? 'dark' : 'light'"
          offset="6"
        >
          <template v-slot:activator="{ props }">
            <button
              v-bind="props"
              type="button"
              class="systembar-context-pill systembar-context-interactive d-none d-sm-flex"
              :style="contextStyle"
            >
              <v-icon size="17" class="mr-1.5" :style="{ color: currentContext.color }">{{ currentContext.icon }}</v-icon>
              <span class="context-section">{{ currentContext.section }}</span>
              <v-icon size="14" class="context-separator mx-1 text-medium-emphasis">mdi-chevron-right</v-icon>
              <span class="context-page">{{ currentContext.title }}</span>
              <v-icon size="13" class="ml-1.5 context-dropdown-arrow" :style="{ color: currentContext.color }">mdi-chevron-down</v-icon>
              <v-tooltip activator="parent" location="bottom">
                {{ $t('jump_to_sibling') }}
              </v-tooltip>
            </button>
          </template>

          <v-card
            class="dock-panel"
            :class="main.is_dark ? 'panel-dark' : 'panel-light'"
            min-width="220"
            rounded="xl"
            elevation="16"
          >
            <div class="dock-panel-header">
              <v-icon size="16" class="mr-2" :style="{ color: currentContext.color }">{{ currentContext.icon }}</v-icon>
              <span class="text-subtitle-2 font-weight-bold">{{ currentContext.section }}</span>
            </div>
            <v-divider></v-divider>
            <v-list density="compact" class="pa-2">
              <v-list-item
                v-for="sib in currentContext.siblings"
                :key="'sib-' + sib.name"
                rounded="lg"
                :prepend-icon="sib.icon"
                :title="sib.title"
                @click="$router.push({ name: sib.name })"
                class="sibling-item"
              >
                <template v-slot:append>
                  <v-icon size="14" color="grey">mdi-arrow-right</v-icon>
                </template>
              </v-list-item>
            </v-list>
          </v-card>
        </v-menu>

        <!-- Fallback non-interactive breadcrumb if no siblings -->
        <div v-else-if="currentContext" class="systembar-context-pill d-none d-sm-flex" :style="contextStyle">
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
            :theme="main.is_dark ? 'dark' : 'light'"
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
            :theme="main.is_dark ? 'dark' : 'light'"
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

              <!-- Web Push Notification Status / Control Banner -->
              <div v-if="webPush.isSupported" class="webpush-bar px-3 py-2" :class="main.is_dark ? 'webpush-bar--dark' : 'webpush-bar--light'">
                <div class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center ga-2">
                    <v-icon
                      size="18"
                      :color="webPush.isEnabled ? '#4CAF50' : webPush.isBlocked ? '#EF5350' : '#42A5F5'"
                    >
                      {{ webPush.isEnabled ? 'mdi-bell-check' : webPush.isBlocked ? 'mdi-bell-cancel' : 'mdi-bell-ring-outline' }}
                    </v-icon>
                    <div>
                      <div class="text-caption font-weight-bold d-flex align-center ga-1">
                        <span>{{ $t('web_push_notifications') }}</span>
                        <v-chip
                          size="x-small"
                          density="compact"
                          :color="webPush.isEnabled ? 'success' : webPush.isBlocked ? 'error' : 'info'"
                          variant="tonal"
                          class="ml-1 font-weight-bold"
                        >
                          {{ webPush.isEnabled ? $t('active') : webPush.isBlocked ? $t('blocked') : $t('disabled') }}
                        </v-chip>
                      </div>
                      <div class="text-caption text-medium-emphasis" style="font-size: 10px !important; line-height: 1.2;">
                        <span v-if="webPush.isEnabled">
                          {{ webPush.deviceCount }} {{ $t('devices_registered') }}
                        </span>
                        <span v-else-if="webPush.isBlocked">
                          {{ $t('webpush_status_blocked') }}
                        </span>
                        <span v-else>
                          {{ $t('webpush_enable_prompt') }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex align-center ga-1">
                    <!-- Test Button (when enabled) -->
                    <v-btn
                      v-if="webPush.isEnabled"
                      size="x-small"
                      variant="tonal"
                      color="primary"
                      class="text-none font-weight-medium"
                      :loading="webPush.loading"
                      @click="webPush.sendTestNotification()"
                      id="webpush-test-btn"
                    >
                      <v-icon size="13" class="mr-1">mdi-bell-ring</v-icon>
                      {{ $t('test_push') }}
                    </v-btn>

                    <!-- Enable Button (when default) -->
                    <v-btn
                      v-if="webPush.needsPermission"
                      size="x-small"
                      variant="flat"
                      color="#42A5F5"
                      class="text-none font-weight-bold"
                      :loading="webPush.loading"
                      @click="webPush.subscribe()"
                      id="webpush-enable-btn"
                    >
                      <v-icon size="13" class="mr-1">mdi-plus</v-icon>
                      {{ $t('enable') }}
                    </v-btn>

                    <!-- Toggle Switch if granted -->
                    <v-btn
                      v-if="webPush.permission === 'granted'"
                      size="x-small"
                      icon
                      variant="text"
                      :color="webPush.isSubscribed ? 'error' : 'success'"
                      :title="webPush.isSubscribed ? $t('disable') : $t('enable')"
                      @click="webPush.isSubscribed ? webPush.unsubscribe() : webPush.subscribe()"
                    >
                      <v-icon size="16">{{ webPush.isSubscribed ? 'mdi-bell-off-outline' : 'mdi-bell-plus-outline' }}</v-icon>
                    </v-btn>
                  </div>
                </div>
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
            :theme="main.is_dark ? 'dark' : 'light'"
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
            :theme="main.is_dark ? 'dark' : 'light'"
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
import { useNavigationHistory } from '@/stores/NavigationHistory';
import { useWebPush } from '@/stores/WebPush';
import { getRouteMetadata } from '@/utils/routeMetadata';
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
      historyTab: 'recents',
      historySearchQuery: '',
    };
  },
  computed: {
    dashboard_waiter() { return useDashboardWaiter(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    _online() { return useOnlineUsers(); },
    notifCenter() { return useNotificationCenter(); },
    webPush() { return useWebPush(); },
    pwa() { return usePwa(); },
    navHistory() {
      try {
        const h = useNavigationHistory();
        return h || {
          canGoBack: false,
          canGoForward: false,
          historyMenuOpen: false,
          recentPages: [],
          stack: [],
          pinnedPages: [],
          currentPage: null,
          previousPage: null,
          nextPage: null,
          goBack() {},
          goForward() {},
          init() {},
          clearHistory() {}
        };
      } catch (e) {
        return {
          canGoBack: false,
          canGoForward: false,
          historyMenuOpen: false,
          recentPages: [],
          stack: [],
          pinnedPages: [],
          currentPage: null,
          previousPage: null,
          nextPage: null,
          goBack() {},
          goForward() {},
          init() {},
          clearHistory() {}
        };
      }
    },
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
      return getRouteMetadata(this.$route);
    },
    filteredRecentPages() {
      const list = this.navHistory.recentPages || [];
      if (!this.historySearchQuery) return list;
      const q = this.historySearchQuery.toLowerCase().trim();
      return list.filter(p =>
        (p.title || '').toLowerCase().includes(q) ||
        (p.section || '').toLowerCase().includes(q) ||
        (p.fullPath || '').toLowerCase().includes(q)
      );
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
    },
    isCurrentPage(item) {
      if (!item) return false;
      const curPath = this.$route.fullPath || this.$route.path;
      return item.fullPath === curPath || item.name === this.$route.name;
    }
  },
  mounted() {
    this.navHistory.init(this.$router);
    if (this.auth.is_auth) {
      this._online.connect();
      this.notifCenter.init();
      this.webPush.init();
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

/* ---------- Navigation History Stepper Cluster ---------- */
.systembar-stepper-cluster {
  display: inline-flex;
  align-items: center;
  border-radius: 12px;
  padding: 2px 4px;
  gap: 2px;
  transition: all 0.2s ease;
}

.stepper-light {
  background: rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.stepper-dark {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.stepper-action-btn {
  border-radius: 8px !important;
  transition: all 0.15s ease !important;
  color: rgba(var(--v-theme-on-surface), 0.75) !important;
}

.stepper-action-btn:hover:not(:disabled) {
  background: rgba(var(--v-theme-primary), 0.12) !important;
  color: rgb(var(--v-theme-primary)) !important;
  transform: translateY(-0.5px);
}

.stepper-action-btn:disabled {
  opacity: 0.28 !important;
  pointer-events: none;
}

.stepper-sep {
  width: 1px;
  height: 16px;
  background: rgba(var(--v-border-color), 0.16);
  margin: 0 1px;
}

.history-pill-btn {
  color: rgb(var(--v-theme-primary)) !important;
}

.history-pill-active {
  background: rgba(var(--v-theme-primary), 0.16) !important;
}

/* ---------- Navigation History Dropdown Panel ---------- */
.nav-history-panel {
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.nav-history-header {
  display: flex;
  align-items: center;
  padding: 12px 14px 10px;
}

.nav-history-icon-box {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(var(--v-theme-primary), 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-history-body {
  max-height: 420px;
  overflow-y: auto;
  overflow-x: hidden;
}

.history-items-list {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.nav-history-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: background-color 0.15s ease, transform 0.15s ease;
  user-select: none;
}

.nav-history-item:hover {
  background: rgba(var(--v-theme-primary), 0.08);
  transform: translateX(2px);
}

.item-current {
  background: rgba(var(--v-theme-primary), 0.1) !important;
  border-left: 3px solid rgb(var(--v-theme-primary));
}

.item-icon-box {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.item-text-box {
  flex: 1;
  min-width: 0;
}

.item-title-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.item-title {
  font-size: 0.84rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-sub-row {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  opacity: 0.8;
  margin-top: 1px;
}

.item-section {
  font-weight: 600;
}

.item-dot {
  opacity: 0.5;
}

.item-time {
  opacity: 0.7;
}

.item-pin-btn {
  opacity: 0.4;
  transition: opacity 0.15s ease;
}

.nav-history-item:hover .item-pin-btn {
  opacity: 1;
}

.trail-header {
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.68rem;
}

.trail-item-forward {
  border-left: 2px solid rgba(76, 175, 80, 0.4);
}

.trail-item-back {
  border-left: 2px solid rgba(158, 158, 158, 0.3);
}

.trail-current-box {
  border: 1.5px solid;
}

.nav-history-footer {
  padding: 8px 14px;
  border-top: 1px solid rgba(var(--v-border-color), 0.1);
  background: rgba(var(--v-theme-on-surface), 0.02);
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-history-footer kbd {
  font-family: inherit;
  padding: 1px 4px;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}

/* Interactive Breadcrumb */
.systembar-context-interactive {
  background: none;
  border: 1px solid transparent;
  cursor: pointer;
  outline: none;
  font-family: inherit;
  transition: all 0.2s ease;
}

.systembar-context-interactive:hover {
  transform: translateY(-1px);
  filter: brightness(1.08);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.context-dropdown-arrow {
  opacity: 0.7;
  transition: transform 0.2s ease;
}

.systembar-context-interactive:hover .context-dropdown-arrow {
  opacity: 1;
  transform: translateY(1px);
}

.sibling-item {
  transition: background-color 0.15s ease;
}

.sibling-item:hover {
  background: rgba(var(--v-theme-primary), 0.08);
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
  font-family: 'Inter', system-ui, sans-serif;
}

.panel-light {
  background: #FFFFFF !important;
  border: 1px solid rgba(0, 0, 0, 0.09) !important;
  box-shadow: 0 16px 40px -10px rgba(0, 0, 0, 0.16), 0 0 1px 1px rgba(0, 0, 0, 0.04) !important;
  color: #0F172A !important;
}

.panel-light .dock-panel-header {
  background: #F8FAFC !important;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  color: #0F172A !important;
}

.panel-light .user-menu-header {
  background: #F8FAFC !important;
  color: #0F172A !important;
}
.panel-light .user-menu-header .text-subtitle-2 {
  color: #0F172A !important;
}
.panel-light .user-menu-header .text-caption {
  color: #64748B !important;
}

.panel-light :deep(.v-list) {
  background: #FFFFFF !important;
  color: #0F172A !important;
}
.panel-light :deep(.v-list-item) {
  color: #1E293B !important;
}
.panel-light :deep(.v-list-item:hover) {
  background: rgba(0, 0, 0, 0.04) !important;
}
.panel-light :deep(.v-list-item-title) {
  color: #1E293B !important;
  font-weight: 500;
}

.panel-light .lang-option {
  color: #1E293B !important;
}
.panel-light .lang-option:hover {
  background: rgba(33, 150, 243, 0.08) !important;
}
.panel-light .lang-option-name {
  color: #0F172A !important;
}
.panel-light .lang-option-code {
  color: #64748B !important;
}

.panel-light .notif-item {
  color: #0F172A !important;
}
.panel-light .notif-item:hover {
  background: rgba(0, 0, 0, 0.035) !important;
}
.panel-light .notif-item-title {
  color: #0F172A !important;
}
.panel-light .notif-item-meta {
  color: #64748B !important;
}

.panel-dark {
  background: #181C26 !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.65), 0 0 1px 1px rgba(255, 255, 255, 0.05) !important;
  color: #F8FAFC !important;
}

.panel-dark .dock-panel-header {
  background: rgba(255, 255, 255, 0.03) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  color: #F8FAFC !important;
}

.panel-dark .user-menu-header {
  background: rgba(255, 255, 255, 0.03) !important;
  color: #F8FAFC !important;
}
.panel-dark .user-menu-header .text-subtitle-2 {
  color: #F8FAFC !important;
}
.panel-dark .user-menu-header .text-caption {
  color: #94A3B8 !important;
}

.panel-dark :deep(.v-list) {
  background: #181C26 !important;
  color: #F8FAFC !important;
}
.panel-dark :deep(.v-list-item) {
  color: #E2E8F0 !important;
}
.panel-dark :deep(.v-list-item:hover) {
  background: rgba(255, 255, 255, 0.06) !important;
}
.panel-dark :deep(.v-list-item-title) {
  color: #E2E8F0 !important;
  font-weight: 500;
}

.panel-dark .lang-option {
  color: #E2E8F0 !important;
}
.panel-dark .lang-option:hover {
  background: rgba(33, 150, 243, 0.15) !important;
}
.panel-dark .lang-option-name {
  color: #F8FAFC !important;
}
.panel-dark .lang-option-code {
  color: #94A3B8 !important;
}

.panel-dark .notif-item {
  color: #F8FAFC !important;
}
.panel-dark .notif-item:hover {
  background: rgba(255, 255, 255, 0.05) !important;
}
.panel-dark .notif-item-title {
  color: #F8FAFC !important;
}
.panel-dark .notif-item-meta {
  color: #94A3B8 !important;
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
.webpush-bar {
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), .06);
  transition: all 0.2s ease;
}
.webpush-bar--light {
  background: rgba(66, 165, 245, 0.05);
}
.webpush-bar--dark {
  background: rgba(66, 165, 245, 0.08);
}

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
