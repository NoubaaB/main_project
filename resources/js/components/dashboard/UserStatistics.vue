<template>
<div class="us-page">
  <!-- Hero Header -->
  <div class="us-hero">
    <div class="us-hero-inner">
      <div class="d-flex align-center ga-4 flex-wrap">
        <div class="us-hero-icon">
          <v-icon size="30" color="white">mdi-chart-timeline-variant-shimmer</v-icon>
        </div>
        <div>
          <div class="d-flex align-center ga-3 flex-wrap">
            <h1 class="us-hero-title mb-0">{{ $t('user_statistics') }}</h1>
            <div class="us-live-beacon">
              <span class="us-beacon-dot"></span>
              <span class="us-beacon-text">{{ $t('live_sync_active') }}</span>
            </div>
          </div>
          <div class="us-hero-subtitle mt-1">
            <v-icon size="14" color="rgba(255,255,255,0.7)" class="mr-1">mdi-shield-check-outline</v-icon>
            {{ $t('user_statistics_subtitle') }}
          </div>
        </div>
      </div>

      <div class="d-flex align-center ga-2 flex-wrap">
        <v-btn
          variant="tonal"
          color="white"
          size="small"
          rounded
          prepend-icon="mdi-refresh"
          @click="fetchData(true)"
          :loading="loading"
          class="us-action-btn"
        >
          {{ $t('refresh') }}
        </v-btn>
        <v-btn
          variant="tonal"
          color="white"
          size="small"
          rounded
          prepend-icon="mdi-arrow-left"
          @click="$router.push({ name: 'index' })"
          class="us-action-btn"
        >
          {{ $t('back') }}
        </v-btn>
      </div>
    </div>
  </div>

  <!-- Period & Control Toolbar (SegmentedPillToggle + Date Range + Search) -->
  <v-card class="rounded-xl mb-4 us-control-card" elevation="0">
    <v-card-text class="pa-3">
      <div class="d-flex flex-wrap align-center justify-space-between ga-3">
        <!-- Period Toggle -->
        <div class="d-flex align-center ga-2 flex-wrap">
          <SegmentedPillToggle
            v-model="activePreset"
            :items="presetItems"
            color="#2563EB"
            size="medium"
            @change="onPresetChange"
          />

          <!-- Active Date Chip & Calendar Toggle -->
          <v-chip
            variant="tonal"
            color="#2563EB"
            class="us-date-chip font-weight-medium cursor-pointer"
            @click="showCalendar = !showCalendar"
          >
            <v-icon start size="16">mdi-calendar-range</v-icon>
            <span>{{ dateStart }}</span>
            <v-icon size="14" class="mx-1">mdi-arrow-right</v-icon>
            <span>{{ dateEnd }}</span>
            <v-icon end size="16">{{ showCalendar ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
          </v-chip>
        </div>

        <!-- Quick Info & Stats Indicator -->
        <div class="d-flex align-center ga-2">
          <v-chip size="small" variant="outlined" color="primary" class="font-weight-medium">
            <v-icon start size="14">mdi-lightning-bolt</v-icon>
            {{ totalPeriodActions }} {{ $t('actions') }}
          </v-chip>
          <v-chip size="small" variant="tonal" color="success" class="font-weight-medium">
            <v-icon start size="14">mdi-account-check</v-icon>
            {{ activeUsersCount }} {{ $t('users') }}
          </v-chip>
        </div>
      </div>

      <!-- Collapsible Date Picker Dialog / Section -->
      <v-expand-transition>
        <div v-if="showCalendar" class="us-calendar-drawer mt-3 pt-3 border-t">
          <div class="d-flex align-center justify-space-between mb-2">
            <div class="text-caption font-weight-bold text-medium-emphasis text-uppercase">
              <v-icon size="14" color="#2563EB" class="mr-1">mdi-calendar-edit</v-icon>
              {{ $t('custom_range') }}
            </div>
            <v-btn
              variant="text"
              density="compact"
              size="small"
              icon="mdi-close"
              @click="showCalendar = false"
            ></v-btn>
          </div>
          <div class="d-flex justify-center">
            <DatePicker
              v-model.range="dateRange"
              :is-dark="main.is_dark"
              :locale="main.lang?.short || 'en'"
              mode="date"
              color="blue"
              :max-date="new Date()"
              expanded
              :columns="isMobile ? 1 : 2"
              @update:model-value="onDateChange"
            />
          </div>
        </div>
      </v-expand-transition>
    </v-card-text>
  </v-card>

  <!-- Loading State -->
  <div v-if="loading && !stats" class="d-flex flex-column align-center justify-center py-16">
    <v-progress-circular indeterminate color="#2563EB" size="52" width="4" />
    <span class="text-body-2 text-medium-emphasis mt-3">{{ $t('loading') }}...</span>
  </div>

  <template v-else-if="stats">
    <!-- 4 Executive KPI Cards -->
    <v-row dense class="mb-4">
      <!-- Total Operations -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="rounded-xl us-kpi-card" elevation="0">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption text-medium-emphasis font-weight-bold text-uppercase">
                {{ $t('period_total_operations') }}
              </span>
              <div class="us-kpi-icon-bubble us-kpi-blue">
                <v-icon size="20" color="#2563EB">mdi-lightning-bolt-circle</v-icon>
              </div>
            </div>
            <div class="d-flex align-baseline ga-2">
              <div class="text-h4 font-weight-black us-stat-val fnm-currency-num text-primary">
                {{ formatNumber(periodSummary.total_actions) }}
              </div>
              <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-bold">
                {{ activePresetLabel }}
              </v-chip>
            </div>
            <div class="d-flex align-center justify-space-between mt-3 text-caption text-medium-emphasis">
              <span class="d-flex align-center">
                <v-icon size="12" color="success" class="mr-1">mdi-arrow-up</v-icon>
                {{ $t('today') }}: <strong class="ml-1 text-high-emphasis fnm-currency-num">{{ stats.today_stats?.total || 0 }}</strong>
              </span>
              <span>{{ stats.user_stats?.length || 0 }} {{ $t('users') }}</span>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Active Operators -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="rounded-xl us-kpi-card" elevation="0">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption text-medium-emphasis font-weight-bold text-uppercase">
                {{ $t('active_operators') }}
              </span>
              <div class="us-kpi-icon-bubble us-kpi-teal">
                <v-icon size="20" color="#00897B">mdi-account-group-outline</v-icon>
              </div>
            </div>
            <div class="d-flex align-baseline ga-2">
              <div class="text-h4 font-weight-black us-stat-val fnm-currency-num text-teal-darken-1">
                {{ periodSummary.active_users }}
              </div>
              <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
                {{ onlineCount }} {{ $t('online_now') }}
              </v-chip>
            </div>
            <div class="d-flex align-center justify-space-between mt-3 text-caption text-medium-emphasis">
              <span>{{ $t('today') }}: <strong class="text-high-emphasis fnm-currency-num">{{ stats.today_stats?.active_users || 0 }}</strong></span>
              <span class="text-success font-weight-medium">
                <v-icon size="12" color="success" class="mr-1">mdi-check-decagram</v-icon>
                {{ percentActive }}%
              </span>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Creations -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="rounded-xl us-kpi-card" elevation="0">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption text-medium-emphasis font-weight-bold text-uppercase">
                {{ $t('operations_created') }}
              </span>
              <div class="us-kpi-icon-bubble us-kpi-green">
                <v-icon size="20" color="#10B981">mdi-plus-circle-outline</v-icon>
              </div>
            </div>
            <div class="d-flex align-baseline ga-2">
              <div class="text-h4 font-weight-black us-stat-val fnm-currency-num text-success">
                {{ formatNumber(periodSummary.creates) }}
              </div>
              <v-chip size="x-small" color="success" variant="tonal" class="font-weight-bold fnm-currency-num">
                {{ percentCreates }}%
              </v-chip>
            </div>
            <div class="d-flex align-center justify-space-between mt-3 text-caption text-medium-emphasis">
              <span>{{ $t('today') }}: <strong class="text-high-emphasis fnm-currency-num">{{ stats.today_stats?.creates || 0 }}</strong></span>
              <span>{{ $t('creates') }}</span>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Updates & Modifications -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="rounded-xl us-kpi-card" elevation="0">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption text-medium-emphasis font-weight-bold text-uppercase">
                {{ $t('operations_updated') }}
              </span>
              <div class="us-kpi-icon-bubble us-kpi-amber">
                <v-icon size="20" color="#F59E0B">mdi-file-edit-outline</v-icon>
              </div>
            </div>
            <div class="d-flex align-baseline ga-2">
              <div class="text-h4 font-weight-black us-stat-val fnm-currency-num text-warning">
                {{ formatNumber(periodSummary.updates) }}
              </div>
              <v-chip size="x-small" color="warning" variant="tonal" class="font-weight-bold fnm-currency-num">
                {{ percentUpdates }}%
              </v-chip>
            </div>
            <div class="d-flex align-center justify-space-between mt-3 text-caption text-medium-emphasis">
              <span>{{ $t('today') }}: <strong class="text-high-emphasis fnm-currency-num">{{ stats.today_stats?.updates || 0 }}</strong></span>
              <span class="text-error fnm-currency-num">{{ periodSummary.deletes }} {{ $t('deletes') }}</span>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Daily Activity Trend Chart -->
    <v-card class="rounded-xl mb-4 us-panel-card" elevation="0">
      <v-card-text class="pa-5">
        <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-4">
          <div class="d-flex align-center ga-3">
            <div class="us-card-header-icon us-bg-blue">
              <v-icon size="18" color="#2563EB">mdi-chart-areaspline</v-icon>
            </div>
            <div>
              <div class="text-subtitle-1 font-weight-bold">{{ $t('daily_activity_trend') }}</div>
              <div class="text-caption text-medium-emphasis">{{ $t('activity_timeline') }} ({{ dateStart }} — {{ dateEnd }})</div>
            </div>
          </div>

          <!-- Trend Legend Chips -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-chip size="small" variant="tonal" color="success" class="font-weight-medium">
              <v-icon start size="12">mdi-circle</v-icon>
              {{ $t('creates') }}: {{ periodSummary.creates }}
            </v-chip>
            <v-chip size="small" variant="tonal" color="warning" class="font-weight-medium">
              <v-icon start size="12">mdi-circle</v-icon>
              {{ $t('updates') }}: {{ periodSummary.updates }}
            </v-chip>
            <v-chip size="small" variant="tonal" color="error" class="font-weight-medium">
              <v-icon start size="12">mdi-circle</v-icon>
              {{ $t('deletes') }}: {{ periodSummary.deletes }}
            </v-chip>
          </div>
        </div>

        <div v-if="hasDailyData" class="us-chart-wrap">
          <apexchart
            type="area"
            :height="290"
            :options="dailyChartOptions"
            :series="dailyChartSeries"
          />
        </div>
        <div v-else class="text-center py-12 text-medium-emphasis">
          <v-icon size="36" color="grey" class="mb-2">mdi-chart-line-variant</v-icon>
          <div>{{ $t('no_activity_found') }}</div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Visualizations: Module Breakdown & Peak Operating Hours -->
    <v-row dense class="mb-4">
      <!-- Module Breakdown -->
      <v-col cols="12" lg="5">
        <v-card class="rounded-xl us-panel-card h-100" elevation="0">
          <v-card-text class="pa-5 d-flex flex-column justify-space-between">
            <div>
              <div class="d-flex align-center justify-space-between mb-4">
                <div class="d-flex align-center ga-3">
                  <div class="us-card-header-icon us-bg-purple">
                    <v-icon size="18" color="#a21ee5">mdi-chart-donut</v-icon>
                  </div>
                  <div>
                    <div class="text-subtitle-1 font-weight-bold">{{ $t('module_breakdown') }}</div>
                    <div class="text-caption text-medium-emphasis">{{ $t('operations_overview') }}</div>
                  </div>
                </div>
                <v-chip size="small" variant="tonal" color="#a21ee5" class="font-weight-medium">
                  {{ stats.module_breakdown?.length || 0 }} {{ $t('all_modules') }}
                </v-chip>
              </div>

              <div v-if="moduleSeries.length" class="us-donut-chart-wrap my-2">
                <apexchart
                  type="donut"
                  :height="260"
                  :options="moduleChartOptions"
                  :series="moduleSeries"
                />
              </div>
              <div v-else class="text-center py-12 text-medium-emphasis">
                <v-icon size="36" color="grey" class="mb-2">mdi-chart-pie</v-icon>
                <div>{{ $t('no_data_available') }}</div>
              </div>
            </div>

            <!-- Module Breakdown Custom Badges -->
            <div v-if="stats.module_breakdown?.length" class="us-module-pills-wrap mt-3 pt-3 border-t">
              <div class="d-flex flex-wrap ga-2">
                <div
                  v-for="m in stats.module_breakdown.slice(0, 6)"
                  :key="m.auditable_type"
                  class="us-module-badge-item"
                  @click="navigateToModule(m.auditable_type)"
                  :title="$t('jump_to_source')"
                >
                  <div class="us-module-badge-indicator" :style="{ background: moduleColor(m.auditable_type) }"></div>
                  <v-icon size="13" :color="moduleColor(m.auditable_type)" class="mr-1">
                    {{ moduleIcon(m.auditable_type) }}
                  </v-icon>
                  <span class="font-weight-medium text-caption">{{ m.auditable_type }}</span>
                  <span class="us-module-badge-count ml-1">{{ m.count }}</span>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Peak Operating Hours -->
      <v-col cols="12" lg="7">
        <v-card class="rounded-xl us-panel-card h-100" elevation="0">
          <v-card-text class="pa-5">
            <div class="d-flex align-center justify-space-between mb-4">
              <div class="d-flex align-center ga-3">
                <div class="us-card-header-icon us-bg-amber">
                  <v-icon size="18" color="#F59E0B">mdi-clock-time-four-outline</v-icon>
                </div>
                <div>
                  <div class="text-subtitle-1 font-weight-bold">{{ $t('peak_hours') }}</div>
                  <div class="text-caption text-medium-emphasis">{{ $t('peak_hours_desc') }}</div>
                </div>
              </div>
              <v-chip v-if="peakHourLabel" size="small" variant="tonal" color="warning" class="font-weight-bold">
                <v-icon start size="12">mdi-fire</v-icon>
                {{ peakHourLabel }}
              </v-chip>
            </div>

            <div class="us-chart-wrap">
              <apexchart
                type="bar"
                :height="290"
                :options="hourlyChartOptions"
                :series="hourlySeries"
              />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- User Leaderboard Section -->
    <v-card class="rounded-xl mb-4 us-panel-card" elevation="0">
      <v-card-text class="pa-5">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-4">
          <div class="d-flex align-center ga-3">
            <div class="us-card-header-icon us-bg-gold">
              <v-icon size="18" color="#D97706">mdi-podium-gold</v-icon>
            </div>
            <div>
              <div class="text-subtitle-1 font-weight-bold">{{ $t('user_leaderboard') }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ filteredUsers.length }} {{ $t('users') }} • {{ $t('top_contributor') }}
              </div>
            </div>
          </div>

          <!-- Search & Role Filter with AddCharge input style -->
          <div class="d-flex align-center ga-3 flex-wrap">
            <v-text-field
              v-model="userSearchQuery"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-charge us-search-input"
              hide-details
              prepend-inner-icon="mdi-magnify"
              :placeholder="$t('filter_users')"
              clearable
              style="min-width: 240px;"
            />

            <!-- Role filter chips -->
            <div class="d-flex align-center ga-1">
              <v-chip
                v-for="r in roleFilters"
                :key="r"
                size="small"
                :variant="selectedRoleFilter === r ? 'flat' : 'outlined'"
                :color="selectedRoleFilter === r ? 'primary' : undefined"
                class="cursor-pointer font-weight-medium"
                @click="selectedRoleFilter = r"
              >
                {{ r === 'all' ? $t('all_roles') : r }}
              </v-chip>
            </div>
          </div>
        </div>

        <!-- Leaderboard Table -->
        <div class="us-table-container">
          <table class="us-modern-table">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>{{ $t('user') }}</th>
                <th>{{ $t('role') }}</th>
                <th>{{ $t('total_actions') }}</th>
                <th class="text-success">{{ $t('creates') }}</th>
                <th class="text-warning">{{ $t('updates') }}</th>
                <th class="text-error">{{ $t('deletes') }}</th>
                <th>{{ $t('top_module') }}</th>
                <th>{{ $t('last_activity') }}</th>
                <th style="width: 140px;">{{ $t('activity_chart') }}</th>
                <th style="width: 60px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(u, index) in filteredUsers"
                :key="u.user_id"
                class="us-table-row"
                :class="{ 'us-row-active-filter': filterUserId === u.user_id }"
              >
                <!-- Podium Rank -->
                <td>
                  <div
                    class="us-rank-badge font-weight-bold"
                    :class="rankBadgeClass(index)"
                  >
                    {{ index + 1 }}
                  </div>
                </td>

                <!-- User Avatar & Identity -->
                <td>
                  <div class="d-flex align-center ga-3">
                    <div class="position-relative">
                      <v-avatar size="34" :color="avatarColor(u.user_id)" class="elevation-1">
                        <span class="text-white text-body-2 font-weight-bold">
                          {{ u.user_name?.charAt(0)?.toUpperCase() || 'U' }}
                        </span>
                      </v-avatar>
                      <span
                        v-if="isUserOnline(u.user_id)"
                        class="us-online-dot-pulse"
                        :title="$t('online_now')"
                      ></span>
                    </div>
                    <div>
                      <div class="font-weight-bold text-body-2 us-user-name">
                        {{ u.user_name }}
                      </div>
                      <div class="text-caption text-medium-emphasis">
                        {{ u.user_email }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Role Badge -->
                <td>
                  <v-chip
                    size="x-small"
                    variant="tonal"
                    :color="roleColor(u.user_role)"
                    class="font-weight-medium"
                  >
                    {{ u.user_role || 'Operator' }}
                  </v-chip>
                </td>

                <!-- Total Actions -->
                <td>
                  <div class="font-weight-black text-body-2 text-primary fnm-currency-num">
                    {{ formatNumber(u.total_actions) }}
                  </div>
                  <v-progress-linear
                    :model-value="actionPercentOfTop(u.total_actions)"
                    color="#2563EB"
                    height="3"
                    rounded
                    class="mt-1"
                    style="max-width: 90px;"
                  />
                </td>

                <!-- Creates -->
                <td>
                  <v-chip size="x-small" color="success" variant="tonal" class="font-weight-bold fnm-currency-num">
                    +{{ formatNumber(u.creates) }}
                  </v-chip>
                </td>

                <!-- Updates -->
                <td>
                  <v-chip size="x-small" color="warning" variant="tonal" class="font-weight-bold fnm-currency-num">
                    ~{{ formatNumber(u.updates) }}
                  </v-chip>
                </td>

                <!-- Deletes -->
                <td>
                  <v-chip size="x-small" color="error" variant="tonal" class="font-weight-bold fnm-currency-num">
                    -{{ formatNumber(u.deletes) }}
                  </v-chip>
                </td>

                <!-- Top Module with Navigation Link -->
                <td>
                  <v-chip
                    v-if="topModule(u)"
                    size="small"
                    variant="tonal"
                    :style="{
                      borderColor: moduleColor(topModule(u)),
                      color: moduleColor(topModule(u)),
                      background: hexToRgba(moduleColor(topModule(u)), 0.1)
                    }"
                    class="cursor-pointer font-weight-medium us-module-chip"
                    @click="navigateToModule(topModule(u))"
                  >
                    <v-icon start size="13" :color="moduleColor(topModule(u))">
                      {{ moduleIcon(topModule(u)) }}
                    </v-icon>
                    <span>{{ topModule(u) }}</span>
                    <v-icon end size="11">mdi-open-in-new</v-icon>
                  </v-chip>
                  <span v-else class="text-medium-emphasis">-</span>
                </td>

                <!-- Last Activity -->
                <td>
                  <div class="text-caption font-weight-medium">
                    {{ relativeTime(u.last_activity) }}
                  </div>
                  <div class="text-caption text-medium-emphasis" style="font-size: 10px !important;">
                    {{ formatDateTime(u.last_activity) }}
                  </div>
                </td>

                <!-- 7-Day Sparkline -->
                <td>
                  <div class="us-sparkline-wrap">
                    <apexchart
                      type="area"
                      :height="36"
                      :width="130"
                      :options="sparkOpts"
                      :series="[{ data: u.sparkline || [0,0,0,0,0,0,0] }]"
                    />
                  </div>
                </td>

                <!-- Filter Button -->
                <td>
                  <v-btn
                    variant="text"
                    density="comfortable"
                    size="small"
                    icon
                    :color="filterUserId === u.user_id ? 'primary' : 'grey'"
                    @click="toggleUserFilter(u.user_id)"
                    :title="filterUserId === u.user_id ? 'Clear filter' : 'Filter feed for this user'"
                  >
                    <v-icon size="18">{{ filterUserId === u.user_id ? 'mdi-filter-check' : 'mdi-filter-outline' }}</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="!filteredUsers.length" class="text-center py-12 text-medium-emphasis">
            <v-icon size="36" color="grey" class="mb-2">mdi-account-search-outline</v-icon>
            <div>{{ $t('no_activity_found') }}</div>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Recent Activity Stream (Live Audit Stream) -->
    <v-card class="rounded-xl mb-4 us-panel-card" elevation="0">
      <v-card-text class="pa-5">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-4">
          <div class="d-flex align-center ga-3">
            <div class="us-card-header-icon us-bg-emerald">
              <v-icon size="18" color="#059669">mdi-history</v-icon>
            </div>
            <div>
              <div class="text-subtitle-1 font-weight-bold">{{ $t('activity_stream') }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ filteredRecentActivity.length }} {{ $t('actions') }}
                <span v-if="filterUserId" class="ml-1 text-primary font-weight-bold">
                  (Filtered by user)
                  <v-icon size="12" class="cursor-pointer" @click="filterUserId = null">mdi-close</v-icon>
                </span>
              </div>
            </div>
          </div>

          <!-- Filter Toolbar for Activity Feed -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <!-- Search Text (AddCharge Input Style) -->
            <v-text-field
              v-model="activitySearchQuery"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-charge us-search-input"
              hide-details
              prepend-inner-icon="mdi-magnify"
              :placeholder="$t('filter_activity')"
              clearable
              style="min-width: 220px;"
            />

            <!-- Action Filter Segmented Pills -->
            <div class="d-flex align-center ga-1">
              <v-chip
                v-for="act in ['all', 'created', 'updated', 'deleted']"
                :key="act"
                size="small"
                :variant="activityActionFilter === act ? 'flat' : 'outlined'"
                :color="activityActionFilter === act ? actionFilterColor(act) : undefined"
                class="cursor-pointer font-weight-medium"
                @click="activityActionFilter = act"
              >
                {{ act === 'all' ? $t('all_actions') : $t(actionLabel(act)) }}
              </v-chip>
            </div>
          </div>
        </div>

        <!-- Activity Feed List -->
        <div class="us-feed-wrap">
          <!-- Column Headers for Orderly Alignment (Desktop) -->
          <div class="us-feed-header d-none d-md-grid mb-2">
            <div></div>
            <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis">{{ $t('user') }}</div>
            <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis">{{ $t('action') }}</div>
            <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis">{{ $t('target_record') }}</div>
            <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">{{ $t('time') }}</div>
            <div></div>
          </div>

          <transition-group name="us-feed-item-anim" tag="div" class="us-feed-list">
            <div
              v-for="a in filteredRecentActivity"
              :key="a.id"
              class="us-feed-row"
              :class="{ 'us-row-new': a._isNew }"
            >
              <!-- 1. Action Indicator Bubble -->
              <div
                class="us-feed-dot-bubble"
                :style="{ background: hexToRgba(actionColor(a.action), 0.15) }"
              >
                <v-icon size="14" :color="actionColor(a.action)">
                  {{ actionIcon(a.action) }}
                </v-icon>
              </div>

              <!-- 2. Operator Identity -->
              <div class="us-feed-user-cell">
                <v-avatar size="30" :color="avatarColor(a.user_id)" class="mr-2 flex-shrink-0">
                  <span class="text-white text-caption font-weight-bold" style="font-size: 11px !important;">
                    {{ a.user_name?.charAt(0)?.toUpperCase() || 'U' }}
                  </span>
                </v-avatar>
                <div class="us-feed-user-meta min-w-0">
                  <div class="font-weight-bold text-body-2 us-truncate" :title="a.user_name">
                    {{ a.user_name }}
                  </div>
                  <v-chip
                    v-if="a.user_role"
                    size="x-small"
                    variant="tonal"
                    :color="roleColor(a.user_role)"
                    class="us-feed-role-chip font-weight-medium"
                  >
                    {{ a.user_role }}
                  </v-chip>
                </div>
              </div>

              <!-- 3. Action Pill (Neat Column) -->
              <div class="us-feed-action-cell">
                <v-chip
                  :color="actionColor(a.action)"
                  size="small"
                  variant="tonal"
                  class="font-weight-bold text-uppercase us-action-pill"
                >
                  <v-icon start size="14">{{ actionIcon(a.action) }}</v-icon>
                  <span>{{ $t(actionLabel(a.action)) }}</span>
                </v-chip>
              </div>

              <!-- 4. Target Record / Resource (Neat Column) -->
              <div class="us-feed-target-cell">
                <v-chip
                  size="small"
                  variant="outlined"
                  class="cursor-pointer us-record-pill font-weight-medium"
                  :style="{
                    borderColor: moduleColor(a.auditable_type),
                    color: moduleColor(a.auditable_type),
                    background: hexToRgba(moduleColor(a.auditable_type), 0.08)
                  }"
                  @click="navigateToRecord(a.auditable_type, a.auditable_id)"
                >
                  <v-icon start size="14" :color="moduleColor(a.auditable_type)">
                    {{ moduleIcon(a.auditable_type) }}
                  </v-icon>
                  <span class="font-weight-bold">{{ a.auditable_type }}</span>
                  <span class="ml-1 opacity-70">#{{ a.auditable_id }}</span>
                  <v-icon end size="12">mdi-open-in-new</v-icon>
                </v-chip>
              </div>

              <!-- 5. Timestamp (Right-Aligned Column) -->
              <div class="us-feed-time-cell text-right">
                <div class="d-flex align-center justify-end ga-1 font-weight-bold text-caption text-high-emphasis">
                  <v-icon size="12" color="grey">mdi-clock-outline</v-icon>
                  <span>{{ relativeTime(a.created_at) }}</span>
                </div>
                <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
                  {{ formatDateTime(a.created_at) }}
                </div>
              </div>

              <!-- 6. Quick Action Button -->
              <div class="us-feed-btn-cell text-right">
                <v-btn
                  variant="text"
                  density="comfortable"
                  size="small"
                  icon="mdi-arrow-top-right"
                  :color="moduleColor(a.auditable_type)"
                  @click="navigateToRecord(a.auditable_type, a.auditable_id)"
                  :title="$t('jump_to_source')"
                ></v-btn>
              </div>
            </div>
          </transition-group>

          <div v-if="!filteredRecentActivity.length" class="text-center py-12 text-medium-emphasis">
            <v-icon size="36" color="grey" class="mb-2">mdi-history</v-icon>
            <div>{{ $t('no_activity_found') }}</div>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </template>

  <!-- Summary Snackbar with Countdown Progress Bar (User Rule 5) -->
  <v-snackbar
    v-model="summarySnackbar.show"
    :timeout="8000"
    location="top right"
    color="surface"
    rounded="lg"
    elevation="12"
    min-width="360"
    max-width="440"
    @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
  >
    <v-progress-linear
      :model-value="snackbarProgress"
      color="#2563EB"
      height="3"
      class="mb-2"
      rounded
    />
    <div class="d-flex align-center mb-2">
      <v-icon :color="summarySnackbar.color || 'success'" class="mr-2">
        {{ summarySnackbar.icon || 'mdi-check-circle' }}
      </v-icon>
      <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
    </div>
    <v-divider class="mb-2" />
    <div class="d-flex flex-column ga-1 text-body-2">
      <div v-if="summarySnackbar.user">
        <v-icon size="14" color="#2563EB" class="mr-1">mdi-account</v-icon>
        <span class="text-medium-emphasis">{{ $t('user') }}:</span>
        <strong class="ml-1">{{ summarySnackbar.user }}</strong>
      </div>
      <div v-if="summarySnackbar.module">
        <v-icon size="14" :color="moduleColor(summarySnackbar.module)" class="mr-1">
          {{ moduleIcon(summarySnackbar.module) }}
        </v-icon>
        <span class="text-medium-emphasis">{{ $t('module') }}:</span>
        <strong class="ml-1" :style="{ color: moduleColor(summarySnackbar.module) }">
          {{ summarySnackbar.module }} #{{ summarySnackbar.recordId }}
        </strong>
      </div>
      <div v-if="summarySnackbar.action">
        <v-icon size="14" :color="actionColor(summarySnackbar.action)" class="mr-1">
          {{ actionIcon(summarySnackbar.action) }}
        </v-icon>
        <span class="text-medium-emphasis">{{ $t('action') }}:</span>
        <v-chip
          size="x-small"
          :color="actionColor(summarySnackbar.action)"
          variant="tonal"
          class="font-weight-bold ml-1 text-uppercase"
        >
          {{ $t(actionLabel(summarySnackbar.action)) }}
        </v-chip>
      </div>
      <div v-if="summarySnackbar.time">
        <v-icon size="14" color="grey" class="mr-1">mdi-clock-outline</v-icon>
        <span class="text-medium-emphasis">{{ $t('time') }}:</span>
        <span class="ml-1 text-medium-emphasis">{{ summarySnackbar.time }}</span>
      </div>
    </div>
    <template #actions>
      <v-btn
        variant="text"
        size="small"
        icon="mdi-close"
        @click="summarySnackbar.show = false"
      />
    </template>
  </v-snackbar>
</div>
</template>

<script>
import axios from 'axios';
import VueApexCharts from 'vue3-apexcharts';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useOnlineUsers } from '@/stores/OnlineUsers';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';

export default {
  name: 'UserStatistics',
  components: {
    apexchart: VueApexCharts,
    SegmentedPillToggle,
  },
  data() {
    return {
      loading: false,
      stats: null,
      activePreset: '30d',
      showCalendar: false,
      dateRange: {
        start: new Date(Date.now() - 29 * 86400000),
        end: new Date(),
      },

      // Leaderboard Filters
      userSearchQuery: '',
      selectedRoleFilter: 'all',
      filterUserId: null,

      // Activity Feed Filters
      activitySearchQuery: '',
      activityActionFilter: 'all',

      // Summary Snackbar state (User Rule 5)
      summarySnackbar: {
        show: false,
        title: '',
        user: '',
        module: '',
        recordId: null,
        action: '',
        time: '',
        color: 'success',
        icon: 'mdi-check-circle',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    onlineUsersStore() { return useOnlineUsers(); },

    isMobile() {
      return typeof window !== 'undefined' ? window.innerWidth < 768 : false;
    },

    presetItems() {
      return [
        { value: 'today', label: this.$t('today'), icon: 'mdi-calendar-today' },
        { value: '7d', label: this.$t('last_7_days'), icon: 'mdi-calendar-week' },
        { value: '30d', label: this.$t('last_30_days'), icon: 'mdi-calendar-month' },
        { value: 'this_month', label: this.$t('this_month'), icon: 'mdi-calendar-text' },
        { value: 'custom', label: this.$t('custom_range'), icon: 'mdi-calendar-range' },
      ];
    },

    activePresetLabel() {
      const match = this.presetItems.find(p => p.value === this.activePreset);
      return match ? match.label : this.$t('custom_range');
    },

    dateStart() {
      return this.fmtDate(this.dateRange.start);
    },
    dateEnd() {
      return this.fmtDate(this.dateRange.end);
    },

    periodSummary() {
      if (this.stats?.period_summary) {
        return this.stats.period_summary;
      }
      const users = this.stats?.user_stats || [];
      return {
        total_actions: users.reduce((acc, u) => acc + (u.total_actions || 0), 0),
        active_users: users.length,
        creates: users.reduce((acc, u) => acc + (u.creates || 0), 0),
        updates: users.reduce((acc, u) => acc + (u.updates || 0), 0),
        deletes: users.reduce((acc, u) => acc + (u.deletes || 0), 0),
      };
    },

    totalPeriodActions() {
      return this.formatNumber(this.periodSummary.total_actions || 0);
    },

    activeUsersCount() {
      return this.periodSummary.active_users || 0;
    },

    onlineCount() {
      return this.onlineUsersStore.users?.length || 0;
    },

    percentActive() {
      const totalPossible = Math.max(this.stats?.user_stats?.length || 1, 1);
      return Math.round((this.periodSummary.active_users / totalPossible) * 100);
    },

    percentCreates() {
      const tot = this.periodSummary.total_actions || 1;
      return Math.round((this.periodSummary.creates / tot) * 100);
    },

    percentUpdates() {
      const tot = this.periodSummary.total_actions || 1;
      return Math.round((this.periodSummary.updates / tot) * 100);
    },

    hasDailyData() {
      return (this.stats?.global_daily || []).some(d => d.total > 0 || d.creates > 0 || d.updates > 0);
    },

    dailyChartSeries() {
      const daily = this.stats?.global_daily || [];
      return [
        { name: this.$t('creates'), data: daily.map(d => d.creates || 0) },
        { name: this.$t('updates'), data: daily.map(d => d.updates || 0) },
        { name: this.$t('deletes'), data: daily.map(d => d.deletes || 0) },
      ];
    },

    dailyChartOptions() {
      const daily = this.stats?.global_daily || [];
      const categories = daily.map(d => {
        const parts = (d.date || '').split('-');
        if (parts.length === 3) {
          return `${parts[1]}/${parts[2]}`;
        }
        return d.date;
      });

      const isDark = this.main.is_dark;

      return {
        chart: {
          toolbar: { show: false },
          fontFamily: 'Inter, sans-serif',
          background: 'transparent',
          sparkline: { enabled: false },
          zoom: { enabled: false },
        },
        xaxis: {
          categories,
          labels: {
            rotate: -45,
            style: {
              colors: isDark ? 'rgba(255,255,255,0.6)' : 'rgba(0,0,0,0.6)',
              fontSize: '11px',
              fontFamily: 'Inter',
            },
          },
          axisBorder: { show: false },
          axisTicks: { show: false },
        },
        yaxis: {
          labels: {
            style: {
              colors: isDark ? 'rgba(255,255,255,0.6)' : 'rgba(0,0,0,0.6)',
              fontSize: '11px',
            },
          },
        },
        colors: ['#10B981', '#F59E0B', '#EF4444'],
        stroke: { curve: 'smooth', width: 2.5 },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: isDark ? 0.45 : 0.35,
            opacityTo: 0.03,
            stops: [0, 95, 100],
          },
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          x: {
            formatter: (val, { dataPointIndex }) => {
              return daily[dataPointIndex]?.date || val;
            },
          },
        },
        grid: {
          borderColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)',
          strokeDashArray: 4,
          padding: { left: 10, right: 10 },
        },
        theme: { mode: isDark ? 'dark' : 'light' },
      };
    },

    moduleSeries() {
      return (this.stats?.module_breakdown || []).map(m => m.count);
    },

    moduleChartOptions() {
      const breakdown = this.stats?.module_breakdown || [];
      const labels = breakdown.map(m => m.auditable_type);
      const colors = breakdown.map(m => this.moduleColor(m.auditable_type));
      const isDark = this.main.is_dark;

      return {
        chart: {
          background: 'transparent',
          fontFamily: 'Inter, sans-serif',
        },
        labels,
        colors,
        legend: {
          show: false,
        },
        dataLabels: {
          enabled: true,
          formatter: (v) => `${Math.round(v)}%`,
          style: {
            fontSize: '11px',
            fontFamily: 'Inter',
            fontWeight: 'bold',
          },
          dropShadow: { enabled: false },
        },
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          y: {
            formatter: (val) => `${val} ${this.$t('actions')}`,
          },
        },
        plotOptions: {
          pie: {
            donut: {
              size: '64%',
              labels: {
                show: true,
                name: {
                  show: true,
                  fontSize: '12px',
                  fontFamily: 'Inter',
                  color: isDark ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)',
                },
                value: {
                  show: true,
                  fontSize: '22px',
                  fontFamily: 'Inter',
                  fontWeight: 800,
                  color: isDark ? '#FFFFFF' : '#0F172A',
                  formatter: (val) => val,
                },
                total: {
                  show: true,
                  label: this.$t('total'),
                  fontSize: '12px',
                  color: isDark ? 'rgba(255,255,255,0.6)' : 'rgba(0,0,0,0.6)',
                  formatter: () => this.formatNumber(this.periodSummary.total_actions || 0),
                },
              },
            },
          },
        },
        stroke: {
          width: 2,
          colors: [isDark ? '#1E293B' : '#FFFFFF'],
        },
        theme: { mode: isDark ? 'dark' : 'light' },
      };
    },

    hourlySeries() {
      const hourData = new Array(24).fill(0);
      (this.stats?.hourly || []).forEach(h => {
        hourData[h.hour] = h.count;
      });
      return [{ name: this.$t('actions'), data: hourData }];
    },

    peakHourLabel() {
      const hourly = this.stats?.hourly || [];
      if (!hourly.length) return null;
      const sorted = [...hourly].sort((a, b) => b.count - a.count);
      const top = sorted[0];
      if (!top || top.count === 0) return null;
      return `${String(top.hour).padStart(2, '0')}:00 (${top.count})`;
    },

    hourlyChartOptions() {
      const isDark = this.main.is_dark;
      const hourData = this.hourlySeries[0].data;
      const maxVal = Math.max(...hourData, 1);

      return {
        chart: {
          toolbar: { show: false },
          fontFamily: 'Inter, sans-serif',
          background: 'transparent',
        },
        xaxis: {
          categories: Array.from({ length: 24 }, (_, i) => `${String(i).padStart(2, '0')}h`),
          labels: {
            style: {
              colors: isDark ? 'rgba(255,255,255,0.6)' : 'rgba(0,0,0,0.6)',
              fontSize: '10px',
            },
          },
          axisBorder: { show: false },
          axisTicks: { show: false },
        },
        yaxis: {
          labels: {
            style: {
              colors: isDark ? 'rgba(255,255,255,0.6)' : 'rgba(0,0,0,0.6)',
              fontSize: '10px',
            },
          },
        },
        colors: ['#2563EB'],
        plotOptions: {
          bar: {
            borderRadius: 5,
            columnWidth: '55%',
            distributed: false,
            colors: {
              ranges: [
                {
                  from: maxVal,
                  to: maxVal,
                  color: '#F59E0B',
                },
              ],
            },
          },
        },
        fill: {
          type: 'gradient',
          gradient: {
            shade: isDark ? 'dark' : 'light',
            type: 'vertical',
            opacityFrom: 0.9,
            opacityTo: 0.4,
          },
        },
        dataLabels: { enabled: false },
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          y: {
            formatter: (val) => `${val} ${this.$t('actions')}`,
          },
        },
        grid: {
          borderColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)',
          strokeDashArray: 4,
        },
        theme: { mode: isDark ? 'dark' : 'light' },
      };
    },

    sparkOpts() {
      const isDark = this.main.is_dark;
      return {
        chart: {
          sparkline: { enabled: true },
          type: 'area',
          background: 'transparent',
        },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
          type: 'gradient',
          gradient: {
            opacityFrom: 0.45,
            opacityTo: 0.05,
          },
        },
        colors: ['#2563EB'],
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          fixed: { enabled: false },
          x: { show: false },
          y: {
            title: { formatter: () => '' },
            formatter: (v) => `${v} acts`,
          },
        },
      };
    },

    roleFilters() {
      const roles = new Set();
      (this.stats?.user_stats || []).forEach(u => {
        if (u.user_role) roles.add(u.user_role);
      });
      return ['all', ...Array.from(roles)];
    },

    filteredUsers() {
      let users = this.stats?.user_stats || [];

      // Filter by search text
      if (this.userSearchQuery?.trim()) {
        const q = this.userSearchQuery.trim().toLowerCase();
        users = users.filter(u =>
          (u.user_name || '').toLowerCase().includes(q) ||
          (u.user_email || '').toLowerCase().includes(q) ||
          (u.user_role || '').toLowerCase().includes(q)
        );
      }

      // Filter by role
      if (this.selectedRoleFilter !== 'all') {
        users = users.filter(u => u.user_role === this.selectedRoleFilter);
      }

      return users;
    },

    filteredRecentActivity() {
      let items = this.stats?.recent_activity || [];

      // Filter by user ID if clicked on leaderboard
      if (this.filterUserId) {
        items = items.filter(a => a.user_id === this.filterUserId);
      }

      // Filter by action
      if (this.activityActionFilter !== 'all') {
        items = items.filter(a => a.action === this.activityActionFilter);
      }

      // Filter by search query
      if (this.activitySearchQuery?.trim()) {
        const q = this.activitySearchQuery.trim().toLowerCase();
        items = items.filter(a =>
          (a.user_name || '').toLowerCase().includes(q) ||
          (a.auditable_type || '').toLowerCase().includes(q) ||
          String(a.auditable_id || '').includes(q) ||
          (a.action || '').toLowerCase().includes(q)
        );
      }

      return items;
    },
  },

  mounted() {
    this.fetchData();
    this.initWebSocketListeners();
  },

  beforeUnmount() {
    this.destroyWebSocketListeners();
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },

  methods: {
    fmtDate(d) {
      if (!d) return '';
      const dt = d instanceof Date ? d : new Date(d);
      return `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
    },

    formatNumber(num) {
      return Number(num || 0).toLocaleString();
    },

    formatDateTime(dt) {
      if (!dt) return '-';
      const d = new Date(dt);
      return (
        d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) +
        ' ' +
        d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
      );
    },

    relativeTime(dt) {
      if (!dt) return '-';
      const date = new Date(dt);
      const now = new Date();
      const diffSec = Math.floor((now - date) / 1000);

      if (diffSec < 60) return `${diffSec}s ago`;
      const diffMin = Math.floor(diffSec / 60);
      if (diffMin < 60) return `${diffMin}m ago`;
      const diffHour = Math.floor(diffMin / 60);
      if (diffHour < 24) return `${diffHour}h ago`;
      const diffDays = Math.floor(diffHour / 24);
      if (diffDays < 7) return `${diffDays}d ago`;
      return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    },

    onPresetChange(val) {
      this.activePreset = val;
      const now = new Date();

      if (val === 'today') {
        this.dateRange = { start: new Date(), end: new Date() };
        this.showCalendar = false;
        this.fetchData();
      } else if (val === '7d') {
        this.dateRange = {
          start: new Date(Date.now() - 6 * 86400000),
          end: new Date(),
        };
        this.showCalendar = false;
        this.fetchData();
      } else if (val === '30d') {
        this.dateRange = {
          start: new Date(Date.now() - 29 * 86400000),
          end: new Date(),
        };
        this.showCalendar = false;
        this.fetchData();
      } else if (val === 'this_month') {
        this.dateRange = {
          start: new Date(now.getFullYear(), now.getMonth(), 1),
          end: new Date(),
        };
        this.showCalendar = false;
        this.fetchData();
      } else if (val === 'custom') {
        this.showCalendar = true;
      }
    },

    onDateChange() {
      this.activePreset = 'custom';
      this.fetchData();
    },

    async fetchData(isManual = false) {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/user-statistics', {
          params: {
            date_start: this.dateStart,
            date_end: this.dateEnd,
          },
        });
        this.stats = data;

        if (isManual) {
          this.triggerSummarySnackbar({
            title: this.$t('refreshed_successfully') || 'Data Refreshed',
            user: this.auth.user?.name || 'Current User',
            action: 'updated',
            color: '#2563EB',
            icon: 'mdi-refresh',
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
          });
        }
      } catch (e) {
        console.error('[UserStats]', e);
      } finally {
        this.loading = false;
      }
    },

    isUserOnline(userId) {
      if (!userId || !this.onlineUsersStore.users) return false;
      return this.onlineUsersStore.users.some(u => u.id === userId);
    },

    toggleUserFilter(userId) {
      this.filterUserId = this.filterUserId === userId ? null : userId;
    },

    topModule(u) {
      if (!u.modules?.length) return null;
      return [...u.modules].sort((a, b) => b.count - a.count)[0].module;
    },

    actionPercentOfTop(count) {
      const topCount = this.stats?.user_stats?.[0]?.total_actions || 1;
      return Math.round((count / topCount) * 100);
    },

    rankBadgeClass(index) {
      if (index === 0) return 'us-rank-gold';
      if (index === 1) return 'us-rank-silver';
      if (index === 2) return 'us-rank-bronze';
      return 'us-rank-default';
    },

    avatarColor(id) {
      const colors = ['#2563EB', '#00897B', '#4643a0', '#a21ee5', '#6D4C41', '#E65100', '#F9A825', '#00838F'];
      return colors[(id || 0) % colors.length];
    },

    roleColor(role) {
      const r = (role || '').toLowerCase();
      if (r.includes('admin')) return '#DC2626';
      if (r.includes('manager') || r.includes('supervisor')) return '#7C3AED';
      if (r.includes('operator')) return '#0284C7';
      return '#64748B';
    },

    actionColor(a) {
      return {
        created: '#10B981',
        updated: '#F59E0B',
        deleted: '#EF4444',
      }[a] || '#64748B';
    },

    actionFilterColor(a) {
      return {
        all: 'primary',
        created: 'success',
        updated: 'warning',
        deleted: 'error',
      }[a] || 'primary';
    },

    actionIcon(a) {
      return {
        created: 'mdi-plus-circle',
        updated: 'mdi-pencil-circle',
        deleted: 'mdi-delete-circle',
      }[a] || 'mdi-circle-medium';
    },

    actionLabel(a) {
      return {
        created: 'record_created',
        updated: 'record_updated',
        deleted: 'record_deleted',
      }[a] || a;
    },

    /* ── Module Branding (claude.md) ── */
    moduleColor(type) {
      const colors = {
        Charge: '#42A5F5',
        Production: '#00897B',
        Shipping: '#4643a0',
        Warehouse: '#a21ee5',
        StorageInvoice: '#a21ee5',
        Inventory: '#6D4C41',
        WarehouseTransfer: '#F9A825',
        SupplierInvoice: '#E65100',
        ClientInvoice: '#00838F',
        OrderTransfer: '#F9A825',
        FortnightActivity: '#1E88E5',
        Guano: '#388E3C',
        Package: '#546E7A',
        User: '#2563EB',
      };
      return colors[type] || '#2563EB';
    },

    moduleIcon(type) {
      const icons = {
        Charge: 'mdi-truck-cargo-container',
        Production: 'mdi-factory',
        Shipping: 'mdi-package-variant-closed',
        Warehouse: 'mdi-warehouse',
        StorageInvoice: 'mdi-warehouse',
        Inventory: 'mdi-clipboard-text-clock-outline',
        WarehouseTransfer: 'mdi-swap-horizontal',
        SupplierInvoice: 'mdi-file-document-check',
        ClientInvoice: 'mdi-account-cash',
        OrderTransfer: 'mdi-bank-transfer',
        FortnightActivity: 'mdi-calendar-month',
        Guano: 'mdi-leaf',
        Package: 'mdi-cube-outline',
      };
      return icons[type] || 'mdi-cube-outline';
    },

    hexToRgba(hex, alpha) {
      if (!hex || hex[0] !== '#') return `rgba(37, 99, 235, ${alpha})`;
      const clean = hex.replace('#', '');
      const bigint = parseInt(clean, 16);
      const r = (bigint >> 16) & 255;
      const g = (bigint >> 8) & 255;
      const b = bigint & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },

    /* ── Navigation Helpers ── */
    navigateToModule(type) {
      const listRoutes = {
        Charge: 'list_charge',
        Production: 'production_list',
        Shipping: 'list_shipping',
        Inventory: 'list_inventory',
        WarehouseTransfer: 'list_warehouse_transfer',
        SupplierInvoice: 'list_supplier_invoice',
        ClientInvoice: 'list_client_invoice',
        StorageInvoice: 'storage_invoices',
        OrderTransfer: 'order_transfers_page',
        FortnightActivity: 'fortnight_management',
      };
      const routeName = listRoutes[type];
      if (routeName) {
        this.$router.push({ name: routeName });
      }
    },

    navigateToRecord(type, id) {
      const viewRoutes = {
        Charge:            { name: 'view_charge',            paramKey: 'id' },
        Production:        { name: 'view_production',        paramKey: 'id' },
        Shipping:          { name: 'view_shipping',          paramKey: 'id' },
        Inventory:         { name: 'edit_inventory',         paramKey: 'id' },
        WarehouseTransfer: { name: 'edit_warehouse_transfer', paramKey: 'id' },
        SupplierInvoice:   { name: 'list_supplier_invoice',  paramKey: null },
        ClientInvoice:     { name: 'list_client_invoice',    paramKey: null },
        StorageInvoice:    { name: 'storage_invoices',       paramKey: null },
        OrderTransfer:     { name: 'order_transfers_page',   paramKey: null },
        FortnightActivity: { name: 'fortnight_management',   paramKey: null },
      };
      const route = viewRoutes[type];
      if (!route) return;
      if (route.paramKey && id) {
        this.$router.push({ name: route.name, params: { [route.paramKey]: id } });
      } else {
        this.$router.push({ name: route.name });
      }
    },

    /* ── WebSocket Real-Time Listeners (User Rule 3 & 5) ── */
    initWebSocketListeners() {
      if (typeof window.Echo === 'undefined') return;

      // 1. Listen on global model changes
      try {
        window.Echo.channel('model-changes')
          .listen('.model.changed', (data) => {
            this.handleLiveEvent(data);
          });
      } catch (e) {
        console.warn('[UserStats] Echo model-changes error:', e);
      }

      // 2. Listen on module individual channels
      const channels = ['charges', 'productions', 'shippings', 'warehouses', 'inventory'];
      channels.forEach(ch => {
        try {
          window.Echo.channel(ch)
            .listen('.created', (e) => this.handleChannelEvent(ch, 'created', e))
            .listen('.updated', (e) => this.handleChannelEvent(ch, 'updated', e))
            .listen('.deleted', (e) => this.handleChannelEvent(ch, 'deleted', e));
        } catch (e) {}
      });
    },

    destroyWebSocketListeners() {
      if (typeof window.Echo === 'undefined') return;
      try {
        window.Echo.leave('model-changes');
      } catch (e) {}

      const channels = ['charges', 'productions', 'shippings', 'warehouses', 'inventory'];
      channels.forEach(ch => {
        try {
          window.Echo.leave(ch);
        } catch (e) {}
      });
    },

    handleLiveEvent(data) {
      if (!data) return;

      // 1. Build new activity item
      const newAct = {
        id: 'live_' + Date.now(),
        user_id: data.user_id || null,
        user_name: data.user_name || 'System Operator',
        user_role: data.role || 'Operator',
        auditable_type: data.model || 'Record',
        auditable_id: data.record_id || data.id,
        action: data.action || 'created',
        created_at: new Date().toISOString(),
        _isNew: true,
      };

      // 2. Prepend to live activity array
      if (this.stats && this.stats.recent_activity) {
        this.stats.recent_activity.unshift(newAct);
        if (this.stats.recent_activity.length > 60) {
          this.stats.recent_activity.pop();
        }
      }

      // 3. Update summary counters alive
      if (this.stats && this.stats.period_summary) {
        this.stats.period_summary.total_actions += 1;
        if (data.action === 'created') this.stats.period_summary.creates += 1;
        if (data.action === 'updated') this.stats.period_summary.updates += 1;
        if (data.action === 'deleted') this.stats.period_summary.deletes += 1;
      }
      if (this.stats && this.stats.today_stats) {
        this.stats.today_stats.total = (this.stats.today_stats.total || 0) + 1;
        if (data.action === 'created') this.stats.today_stats.creates = (this.stats.today_stats.creates || 0) + 1;
        if (data.action === 'updated') this.stats.today_stats.updates = (this.stats.today_stats.updates || 0) + 1;
        if (data.action === 'deleted') this.stats.today_stats.deletes = (this.stats.today_stats.deletes || 0) + 1;
      }

      // 4. Trigger Summary Snackbar (User Rule 5)
      this.triggerSummarySnackbar({
        title: this.$t('new_activity_toast'),
        user: newAct.user_name,
        module: newAct.auditable_type,
        recordId: newAct.auditable_id,
        action: newAct.action,
        color: this.actionColor(newAct.action),
        icon: this.actionIcon(newAct.action),
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      });
    },

    handleChannelEvent(channel, action, payload) {
      const channelToModel = {
        charges: 'Charge',
        productions: 'Production',
        shippings: 'Shipping',
        warehouses: 'Warehouse',
        inventory: 'Inventory',
      };
      const model = channelToModel[channel] || 'Record';
      this.handleLiveEvent({
        model,
        action,
        record_id: payload?.id || payload?.record_id,
        user_name: payload?.user_name || this.auth.user?.name || 'Operator',
        user_id: payload?.user_id,
      });
    },

    triggerSummarySnackbar(options) {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.summarySnackbar = {
        show: true,
        title: options.title || this.$t('new_activity_toast'),
        user: options.user || '',
        module: options.module || '',
        recordId: options.recordId || null,
        action: options.action || 'created',
        time: options.time || '',
        color: options.color || 'success',
        icon: options.icon || 'mdi-check-circle',
      };
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
  },
};
</script>

<style scoped>
.us-page {
  font-family: 'Inter', sans-serif;
  padding-bottom: 32px;
}

/* ── Hero Header ── */
.us-hero {
  background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 50%, #2563EB 100%);
  border-radius: 20px;
  margin-bottom: 16px;
  box-shadow: 0 10px 30px -10px rgba(37, 99, 235, 0.35);
  position: relative;
  overflow: hidden;
}

.us-hero::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 320px;
  height: 320px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.us-hero-inner {
  position: relative;
  z-index: 1;
  padding: 22px 26px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.us-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

.us-hero-title {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.3px;
  color: #FFFFFF;
}

.us-hero-subtitle {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.82);
  display: flex;
  align-items: center;
}

.us-live-beacon {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 12px;
  background: rgba(16, 185, 129, 0.18);
  border: 1px solid rgba(16, 185, 129, 0.35);
}

.us-beacon-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background-color: #10B981;
  box-shadow: 0 0 8px #10B981;
  animation: us-pulse 2s infinite;
}

.us-beacon-text {
  font-size: 11px;
  font-weight: 700;
  color: #A7F3D0;
  letter-spacing: 0.2px;
}

@keyframes us-pulse {
  0% { transform: scale(0.95); opacity: 0.8; }
  50% { transform: scale(1.25); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.8; }
}

.us-action-btn {
  background: rgba(255, 255, 255, 0.12) !important;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
  transition: all 0.2s ease;
}

.us-action-btn:hover {
  background: rgba(255, 255, 255, 0.22) !important;
  transform: translateY(-1px);
}

/* ── Control Card & Toolbar ── */
.us-control-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface)) !important;
}

.us-date-chip {
  height: 34px !important;
  border-radius: 10px !important;
}

.us-calendar-drawer {
  background: rgba(var(--v-theme-on-surface), 0.02);
  border-radius: 14px;
  padding: 12px;
}

/* ── Modern KPI Cards ── */
.us-kpi-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface)) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.us-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px -6px rgba(0, 0, 0, 0.08);
}

.us-kpi-icon-bubble {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.us-kpi-blue { background: rgba(37, 99, 235, 0.1); }
.us-kpi-teal { background: rgba(0, 137, 123, 0.1); }
.us-kpi-green { background: rgba(16, 185, 129, 0.1); }
.us-kpi-amber { background: rgba(245, 158, 11, 0.1); }

.us-stat-val {
  letter-spacing: -0.5px;
  line-height: 1.1;
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  -webkit-font-feature-settings: "tnum" 1 !important;
  font-feature-settings: "tnum" 1 !important;
}

/* ── Panel Cards & Visuals ── */
.us-panel-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface)) !important;
}

.us-card-header-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.us-bg-blue { background: rgba(37, 99, 235, 0.1); }
.us-bg-purple { background: rgba(162, 30, 229, 0.1); }
.us-bg-amber { background: rgba(245, 158, 11, 0.1); }
.us-bg-gold { background: rgba(217, 119, 6, 0.1); }
.us-bg-emerald { background: rgba(5, 150, 105, 0.1); }

.us-chart-wrap {
  position: relative;
  min-height: 280px;
}

.us-donut-chart-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
}

/* ── Module Pills In Donut Card ── */
.us-module-pills-wrap {
  border-color: rgba(var(--v-border-color), 0.12) !important;
}

.us-module-badge-item {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  border: 1px solid rgba(var(--v-border-color), 0.12);
  cursor: pointer;
  transition: all 0.2s ease;
}

.us-module-badge-item:hover {
  background: rgba(var(--v-theme-on-surface), 0.08);
  transform: translateY(-1px);
}

.us-module-badge-indicator {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  margin-right: 6px;
}

.us-module-badge-count {
  font-size: 11px;
  font-weight: 700;
  opacity: 0.7;
}

/* ── Leaderboard Modern Table ── */
.us-table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid rgba(var(--v-border-color), 0.12);
}

.us-modern-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
}

.us-modern-table th {
  padding: 12px 14px;
  text-align: left;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: rgba(var(--v-theme-on-surface), 0.03);
  border-bottom: 2px solid rgba(var(--v-border-color), 0.14);
  white-space: nowrap;
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.us-modern-table td {
  padding: 12px 14px;
  vertical-align: middle;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
  white-space: nowrap;
}

.us-table-row {
  transition: background 0.15s ease;
}

.us-table-row:hover {
  background: rgba(37, 99, 235, 0.04);
}

.us-row-active-filter {
  background: rgba(37, 99, 235, 0.08) !important;
}

.us-rank-badge {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
}

.us-rank-gold {
  background: linear-gradient(135deg, #F59E0B, #D97706);
  color: #fff;
  box-shadow: 0 2px 6px rgba(217, 119, 6, 0.4);
}

.us-rank-silver {
  background: linear-gradient(135deg, #94A3B8, #64748B);
  color: #fff;
  box-shadow: 0 2px 6px rgba(100, 116, 139, 0.3);
}

.us-rank-bronze {
  background: linear-gradient(135deg, #D97706, #B45309);
  color: #fff;
  box-shadow: 0 2px 6px rgba(180, 83, 9, 0.3);
}

.us-rank-default {
  background: rgba(var(--v-theme-on-surface), 0.06);
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.us-online-dot-pulse {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background-color: #10B981;
  border: 2px solid rgb(var(--v-theme-surface));
  box-shadow: 0 0 6px #10B981;
}

.us-user-name {
  color: rgb(var(--v-theme-on-surface));
}

.us-module-chip {
  transition: all 0.2s ease;
}

.us-module-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.us-sparkline-wrap {
  width: 130px;
  display: flex;
  align-items: center;
}

/* ── Activity Stream Feed ── */
.us-feed-wrap {
  max-height: 520px;
  overflow-y: auto;
  padding-right: 4px;
}

.us-feed-header {
  display: grid;
  grid-template-columns: 36px 200px 140px 1fr 150px 36px;
  gap: 16px;
  padding: 8px 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
  align-items: center;
}

.us-feed-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

.us-feed-row {
  display: grid;
  grid-template-columns: 36px 200px 140px 1fr 150px 36px;
  gap: 16px;
  align-items: center;
  padding: 10px 16px;
  border-radius: 12px;
  background: rgba(var(--v-theme-on-surface), 0.02);
  border: 1px solid rgba(var(--v-border-color), 0.08);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.us-feed-row:hover {
  background: rgba(var(--v-theme-on-surface), 0.05);
  border-color: rgba(var(--v-border-color), 0.18);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.us-feed-user-cell {
  display: flex;
  align-items: center;
  min-width: 0;
}

.us-feed-user-meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.us-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.us-feed-role-chip {
  font-size: 9px !important;
  height: 18px !important;
  padding: 0 6px !important;
  width: fit-content;
  margin-top: 2px;
}

.us-feed-action-cell {
  display: flex;
  align-items: center;
}

.us-action-pill {
  border-radius: 8px !important;
  letter-spacing: 0.3px;
  font-size: 11px !important;
}

.us-feed-target-cell {
  display: flex;
  align-items: center;
  min-width: 0;
}

.us-feed-time-cell {
  white-space: nowrap;
}

.us-feed-btn-cell {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.us-row-new {
  animation: us-highlight 2.5s ease-out;
}

@keyframes us-highlight {
  0% { background: rgba(37, 99, 235, 0.2); }
  100% { background: rgba(var(--v-theme-on-surface), 0.02); }
}

.us-feed-dot-bubble {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.us-record-pill {
  border-radius: 8px !important;
  transition: all 0.2s ease;
}

.us-record-pill:hover {
  transform: scale(1.03);
}

/* ── Feed transition ── */
.us-feed-item-anim-enter-active {
  transition: all 0.4s ease-out;
}
.us-feed-item-anim-leave-active {
  transition: all 0.3s ease-in;
}
.us-feed-item-anim-enter-from {
  opacity: 0;
  transform: translateY(-16px);
}
.us-feed-item-anim-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* ── Mobile responsive fallback ── */
@media (max-width: 960px) {
  .us-feed-header {
    display: none !important;
  }

  .us-feed-row {
    grid-template-columns: 36px 1fr auto;
    gap: 10px;
    align-items: flex-start;
  }

  .us-feed-action-cell,
  .us-feed-target-cell {
    grid-column: 2 / -1;
  }

  .us-feed-btn-cell {
    display: none;
  }
}
</style>
