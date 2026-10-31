<template>
  <div class="wfs-dashboard" :class="{ 'wfs-dark': main.is_dark, 'wfs-light': !main.is_dark }">
    <!-- =========== HERO HEADER =========== -->
    <div class="wfs-header">
      <div class="wfs-header-glow"></div>
      <div class="d-flex flex-wrap align-center justify-space-between ga-4">
        <!-- Title & Icon -->
        <div class="d-flex align-center ga-4">
          <div class="wfs-icon-wrap">
            <v-icon size="30" color="white">mdi-chart-timeline-variant</v-icon>
          </div>
          <div>
            <div class="d-flex align-center ga-2 flex-wrap">
              <h1 class="wfs-title">{{ $t('workforce_statistics') }}</h1>
              <v-chip size="x-small" color="white" variant="outlined" class="font-weight-bold opacity-90">
                {{ selectedYear }}
              </v-chip>
            </div>
            <p class="wfs-subtitle">{{ dynamicSubtitle }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex align-center flex-wrap ga-2">
          <!-- Active Months Badge -->
          <div class="wfs-period-badge d-none d-sm-flex align-center ga-2">
            <v-icon size="16" color="white">mdi-calendar-check</v-icon>
            <span class="text-caption font-weight-bold text-white">{{ activePeriodSummary }}</span>
          </div>

          <!-- Refresh Button -->
          <v-btn
            size="small"
            rounded="pill"
            variant="tonal"
            color="white"
            class="font-weight-bold"
            :loading="loading || workerProcessing"
            @click="loadStats"
            prepend-icon="mdi-refresh"
          >
            {{ $t('refresh_data') }}
          </v-btn>

          <!-- Export Excel Button -->
          <v-btn
            size="small"
            rounded="pill"
            variant="flat"
            color="white"
            class="text-primary font-weight-bold"
            @click="exportToExcel"
            prepend-icon="mdi-file-excel"
            :disabled="loading || workerProcessing || !stats.grand_total"
          >
            {{ $t('export_excel') }}
          </v-btn>

          <!-- Print Button -->
          <v-btn
            size="small"
            icon
            variant="tonal"
            color="white"
            @click="printPage('full_summary')"
            :title="$t('print_now')"
          >
            <v-icon size="18">mdi-printer</v-icon>
          </v-btn>
        </div>
      </div>
    </div>

    <!-- =========== PERIOD & MONTH SELECTOR =========== -->
    <v-card class="wfs-card mb-4" elevation="0">
      <v-card-text class="pa-4">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-3">
          <!-- Left: Label & Presets -->
          <div class="d-flex align-center flex-wrap ga-2">
            <div class="d-flex align-center mr-2">
              <v-icon color="#42A5F5" size="20" class="mr-1">mdi-calendar-month</v-icon>
              <span class="text-subtitle-2 font-weight-bold">{{ $t('select_months') }}</span>
              <v-badge
                :content="selectedMonths.length"
                color="#42A5F5"
                inline
                class="ml-2 font-weight-bold"
              ></v-badge>
            </div>

            <!-- Quick Preset Chips -->
            <v-chip
              size="x-small"
              variant="tonal"
              color="#42A5F5"
              class="font-weight-medium cursor-pointer"
              @click="selectCurrentMonth"
            >
              {{ $t('current_month') }}
            </v-chip>
            <v-chip
              size="x-small"
              variant="tonal"
              color="#42A5F5"
              class="font-weight-medium cursor-pointer"
              @click="selectLastMonth"
            >
              {{ $t('last_month') }}
            </v-chip>
            <v-chip
              size="x-small"
              variant="tonal"
              color="#42A5F5"
              class="font-weight-medium cursor-pointer"
              @click="selectAllAvailableMonths"
            >
              {{ $t('select_all') }}
            </v-chip>
          </div>

          <!-- Right: Year Selection (Supplier Settlement Style) -->
          <div class="d-flex align-center ga-2">
            <span class="text-caption text-medium-emphasis font-weight-bold d-none d-sm-inline">{{ $t('year') }}:</span>
            <div class="wfs-year-pill">
              <v-btn
                icon
                variant="text"
                size="x-small"
                @click="changeYear(-1)"
                class="wfs-year-btn"
                :title="$t('previous_year')"
              >
                <v-icon size="20">mdi-chevron-left</v-icon>
              </v-btn>
              <v-menu v-model="yearMenuOpen" location="bottom center" transition="scale-transition">
                <template #activator="{ props }">
                  <span
                    v-bind="props"
                    class="wfs-year-text"
                    :title="$t('select_year')"
                  >
                    {{ selectedYear }}
                  </span>
                </template>
                <v-list density="compact" elevation="6" rounded="lg" class="py-1">
                  <v-list-item
                    v-for="y in yearOptions"
                    :key="y"
                    :value="y"
                    :active="y === selectedYear"
                    color="#E65100"
                    @click="selectedYear = y; yearMenuOpen = false"
                  >
                    <v-list-item-title class="font-weight-bold text-caption text-center">{{ y }}</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
              <v-btn
                icon
                variant="text"
                size="x-small"
                @click="changeYear(1)"
                class="wfs-year-btn"
                :title="$t('next_year')"
              >
                <v-icon size="20">mdi-chevron-right</v-icon>
              </v-btn>
            </div>
          </div>
        </div>

        <!-- 12 Month Pills Grid -->
        <div class="wfs-month-grid">
          <div
            v-for="(name, idx) in monthNames"
            :key="idx"
            class="wfs-month-pill"
            :class="{
              'wfs-month-pill--active': selectedMonths.includes(idx),
              'wfs-month-pill--disabled': isMonthDisabled(idx),
            }"
            @click="toggleMonth(idx)"
          >
            <span class="wfs-month-name">{{ name }}</span>
            <v-icon
              v-if="selectedMonths.includes(idx)"
              size="14"
              color="white"
              class="ml-1"
            >mdi-check-circle</v-icon>
          </div>
        </div>

        <!-- Date Range Caption -->
        <div class="d-flex align-center justify-space-between mt-3 pt-2 wfs-range-caption">
          <div class="text-caption text-medium-emphasis d-flex align-center ga-1">
            <v-icon size="14" color="#42A5F5">mdi-information-outline</v-icon>
            <span>{{ formattedDateRangeText }}</span>
          </div>
          <div v-if="loading || workerProcessing" class="d-flex align-center ga-2">
            <v-progress-circular indeterminate size="14" width="2" color="#42A5F5"></v-progress-circular>
            <span class="text-caption text-medium-emphasis">{{ $t('loading') }}...</span>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- =========== FILTERS (AddCharge Style) =========== -->
    <v-card class="wfs-card mb-4" elevation="0">
      <v-card-text class="pa-4">
        <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
          <div class="d-flex align-center ga-2">
            <v-icon color="#42A5F5" size="20">mdi-filter-variant</v-icon>
            <span class="text-subtitle-2 font-weight-bold">{{ $t('filters') }}</span>
            <v-chip
              v-if="activeFiltersCount > 0"
              size="x-small"
              color="#42A5F5"
              variant="flat"
              class="font-weight-bold ml-1"
            >
              {{ activeFiltersCount }} {{ $t('filters_active') }}
            </v-chip>
          </div>
          <v-btn
            v-if="activeFiltersCount > 0"
            size="x-small"
            variant="text"
            color="warning"
            prepend-icon="mdi-filter-remove-outline"
            class="font-weight-bold"
            @click="resetFilters"
          >
            {{ $t('reset_filters') }}
          </v-btn>
        </div>

        <!-- 3 Autocomplete Inputs Styled as in AddCharge -->
        <v-row dense>
          <!-- Workforces Autocomplete -->
          <v-col cols="12" md="4">
            <v-autocomplete
              v-model="filterWorkforceIds"
              :items="workforceStore.list"
              item-value="id"
              :item-title="workforceItemTitle"
              :label="$t('select_workforces')"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-charge wfs-input-field"
              multiple
              chips
              closable-chips
              clearable
              hide-details
              :filter="workforceFilter"
            >
              <template #chip="{ item, props }">
                <v-chip
                  v-bind="props"
                  color="#42A5F5"
                  variant="tonal"
                  size="small"
                  class="font-weight-medium"
                >
                  <v-icon start size="14">mdi-account</v-icon>
                  {{ item.title }}
                </v-chip>
              </template>
              <template #item="{ props, item }">
                <v-list-item v-bind="props" :subtitle="item.raw.matricule ? `${$t('matricule')}: ${item.raw.matricule}` : ''">
                  <template #prepend>
                    <v-checkbox-btn
                      :model-value="filterWorkforceIds.includes(item.raw.id)"
                      density="compact"
                      color="#42A5F5"
                      hide-details
                    ></v-checkbox-btn>
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>

          <!-- Sections Autocomplete -->
          <v-col cols="12" md="4">
            <v-autocomplete
              v-model="filterSectionIds"
              :items="sectionStore.list"
              item-value="id"
              item-title="name"
              :label="$t('sections')"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-charge wfs-input-field"
              multiple
              chips
              closable-chips
              clearable
              hide-details
            >
              <template #chip="{ item, props }">
                <v-chip
                  v-bind="props"
                  color="#42A5F5"
                  variant="tonal"
                  size="small"
                  class="font-weight-medium"
                >
                  <v-icon start size="14">mdi-sitemap-outline</v-icon>
                  {{ item.title }}
                </v-chip>
              </template>
              <template #item="{ props, item }">
                <v-list-item v-bind="props">
                  <template #prepend>
                    <v-checkbox-btn
                      :model-value="filterSectionIds.includes(item.raw.id)"
                      density="compact"
                      color="#42A5F5"
                      hide-details
                    ></v-checkbox-btn>
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>

          <!-- Corporals Autocomplete -->
          <v-col cols="12" md="4">
            <v-autocomplete
              v-model="filterCorporalIds"
              :items="corporalStore.list"
              item-value="id"
              item-title="full_name"
              :label="$t('corporals')"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-charge wfs-input-field"
              multiple
              chips
              closable-chips
              clearable
              hide-details
            >
              <template #chip="{ item, props }">
                <v-chip
                  v-bind="props"
                  color="#42A5F5"
                  variant="tonal"
                  size="small"
                  class="font-weight-medium"
                >
                  <v-icon start size="14">mdi-account-tie</v-icon>
                  {{ item.title }}
                </v-chip>
              </template>
              <template #item="{ props, item }">
                <v-list-item v-bind="props">
                  <template #prepend>
                    <v-checkbox-btn
                      :model-value="filterCorporalIds.includes(item.raw.id)"
                      density="compact"
                      color="#42A5F5"
                      hide-details
                    ></v-checkbox-btn>
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- =========== 4 EXECUTIVE KPI CARDS =========== -->
    <v-row class="mb-4" dense>
      <!-- KPI 1: Total Payroll -->
      <v-col cols="12" sm="6" lg="3">
        <div class="wfs-kpi-card wfs-kpi--blue">
          <div class="wfs-kpi-accent"></div>
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="wfs-kpi-lbl">{{ $t('total_amount') }}</span>
            <div class="wfs-kpi-icon wfs-kpi-icon--blue">
              <v-icon color="#1976D2" size="22">mdi-cash-multiple</v-icon>
            </div>
          </div>
          <div class="wfs-kpi-val text-primary fnm-currency-num">{{ fmt(stats.grand_total) }} <span class="wfs-kpi-unit">{{ currency }}</span></div>
          <div class="wfs-kpi-sub">
            <v-icon size="13" color="#1976D2" class="mr-1">mdi-chart-line</v-icon>
            <span>~ <span class="fnm-currency-num">{{ fmt(dailyAverage) }}</span> {{ currency }} {{ $t('avg_per_day') }}</span>
          </div>
        </div>
      </v-col>

      <!-- KPI 2: Total Activities -->
      <v-col cols="12" sm="6" lg="3">
        <div class="wfs-kpi-card wfs-kpi--teal">
          <div class="wfs-kpi-accent"></div>
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="wfs-kpi-lbl">{{ $t('total_activities_kpi') }}</span>
            <div class="wfs-kpi-icon wfs-kpi-icon--teal">
              <v-icon color="#00897B" size="22">mdi-calendar-check-outline</v-icon>
            </div>
          </div>
          <div class="wfs-kpi-val fnm-currency-num" style="color: #00897B;">{{ stats.total_activities }}</div>
          <div class="wfs-kpi-sub">
            <v-icon size="13" color="#00897B" class="mr-1">mdi-calendar-range</v-icon>
            <span><span class="fnm-currency-num">{{ stats.daily ? stats.daily.length : 0 }}</span> {{ $t('active_work_days') }}</span>
          </div>
        </div>
      </v-col>

      <!-- KPI 3: Active Sections -->
      <v-col cols="12" sm="6" lg="3">
        <div class="wfs-kpi-card wfs-kpi--orange">
          <div class="wfs-kpi-accent"></div>
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="wfs-kpi-lbl">{{ $t('sections') }}</span>
            <div class="wfs-kpi-icon wfs-kpi-icon--orange">
              <v-icon color="#E65100" size="22">mdi-sitemap-outline</v-icon>
            </div>
          </div>
          <div class="wfs-kpi-val fnm-currency-num" style="color: #E65100;">{{ stats.sections ? stats.sections.length : 0 }}</div>
          <div class="wfs-kpi-sub" :title="topSectionInfo">
            <v-icon size="13" color="#E65100" class="mr-1">mdi-trophy-outline</v-icon>
            <span class="text-truncate">{{ topSectionInfo }}</span>
          </div>
        </div>
      </v-col>

      <!-- KPI 4: Active Workforces -->
      <v-col cols="12" sm="6" lg="3">
        <div class="wfs-kpi-card wfs-kpi--purple">
          <div class="wfs-kpi-accent"></div>
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="wfs-kpi-lbl">{{ $t('active_workforces') }}</span>
            <div class="wfs-kpi-icon wfs-kpi-icon--purple">
              <v-icon color="#7B1FA2" size="22">mdi-account-group-outline</v-icon>
            </div>
          </div>
          <div class="wfs-kpi-val fnm-currency-num" style="color: #7B1FA2;">{{ stats.workforces ? stats.workforces.length : 0 }}</div>
          <div class="wfs-kpi-sub">
            <v-icon size="13" color="#7B1FA2" class="mr-1">mdi-account-tie</v-icon>
            <span><span class="fnm-currency-num">{{ stats.corporals ? stats.corporals.length : 0 }}</span> {{ $t('corporals') }}</span>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- =========== ACTIVITY TIMELINE CHART =========== -->
    <v-card class="wfs-card mb-4" elevation="0">
      <v-card-title class="d-flex align-center justify-space-between pa-4 pb-2 flex-wrap ga-2">
        <div class="d-flex align-center ga-2">
          <v-icon color="#42A5F5" size="22">mdi-chart-areaspline</v-icon>
          <div>
            <span class="text-subtitle-1 font-weight-bold">{{ $t('activity_totals') }}</span>
            <span class="text-caption text-medium-emphasis ml-2 d-none d-sm-inline">({{ activePeriodSummary }})</span>
          </div>
        </div>

        <div class="d-flex align-center ga-2 flex-wrap">
          <!-- Chart Type Toggle (Area vs Bar) -->
          <SegmentedPillToggle
            v-model="chartType"
            :items="chartTypeItems"
            color="#1E88E5"
            size="small"
          />

          <!-- Period Toggle (Daily / Monthly / Yearly) -->
          <SegmentedPillToggle
            v-model="chartPeriod"
            :items="chartPeriodItems"
            color="#1E88E5"
            size="small"
          />

          <!-- Quick Print Button -->
          <v-btn
            size="small"
            variant="tonal"
            color="#1565C0"
            rounded="lg"
            prepend-icon="mdi-printer"
            class="font-weight-bold"
            @click="printPage('daily')"
            :title="$t('print_now')"
          >
            {{ $t('print') }}
          </v-btn>
        </div>
      </v-card-title>

      <v-card-text class="pa-4 pt-2">
        <v-skeleton-loader v-if="loading || workerProcessing" type="image" height="320" class="rounded-xl"></v-skeleton-loader>
        <div v-else-if="mainChartSeries.length > 0 && mainChartSeries[0].data.length > 0">
          <apexchart
            :key="`${chartType}-${chartPeriod}-${main.is_dark}`"
            :type="chartType"
            height="320"
            :options="mainChartOptions"
            :series="mainChartSeries"
          ></apexchart>
        </div>
        <div v-else class="text-center pa-10 text-grey">
          <v-icon size="52" color="grey-lighten-1">mdi-chart-line-variant</v-icon>
          <div class="mt-2 text-body-1 font-weight-medium">{{ $t('no_activities_found') }}</div>
        </div>
      </v-card-text>
    </v-card>

    <!-- =========== SECTIONS & CORPORALS DUAL BREAKDOWN =========== -->
    <v-row class="mb-4" dense>
      <!-- Section Breakdown -->
      <v-col cols="12" md="6">
        <v-card class="wfs-card h-100" elevation="0">
          <v-card-title class="d-flex align-center justify-space-between pa-4 pb-2">
            <div class="d-flex align-center ga-2">
              <v-icon color="#E65100" size="20">mdi-sitemap-outline</v-icon>
              <span class="text-subtitle-1 font-weight-bold">{{ $t('total_by_section') }}</span>
              <v-chip size="x-small" color="#E65100" variant="tonal" class="font-weight-bold">
                {{ stats.sections ? stats.sections.length : 0 }}
              </v-chip>
            </div>
            <div class="d-flex align-center ga-2">
              <v-btn
                size="x-small"
                variant="tonal"
                color="#E65100"
                rounded="lg"
                prepend-icon="mdi-printer"
                class="font-weight-bold"
                @click="printPage('sections')"
                :title="$t('print_now')"
              >
                {{ $t('print') }}
              </v-btn>
              <!-- View Switcher -->
              <SegmentedPillToggle
                v-model="sectionViewMode"
                :items="sectionViewItems"
                color="#E65100"
                size="small"
              />
            </div>
          </v-card-title>

          <v-card-text class="pa-4 pt-2">
            <v-skeleton-loader v-if="loading || workerProcessing" type="image" height="280" class="rounded-xl"></v-skeleton-loader>
            <template v-else-if="stats.sections && stats.sections.length > 0">
              <!-- Chart View -->
              <div v-show="sectionViewMode === 'chart'">
                <apexchart
                  :key="`sec-chart-${main.is_dark}`"
                  type="bar"
                  height="280"
                  :options="sectionChartOptions"
                  :series="sectionChartSeries"
                ></apexchart>
              </div>

              <!-- List / Ranking View -->
              <div v-show="sectionViewMode === 'list'" class="wfs-rank-list">
                <div
                  v-for="(sec, idx) in stats.sections"
                  :key="sec.id"
                  class="wfs-rank-item"
                >
                  <div class="d-flex align-center justify-space-between mb-1">
                    <div class="d-flex align-center ga-2">
                      <span class="wfs-rank-badge" :class="'wfs-rank-' + (idx + 1)">#{{ idx + 1 }}</span>
                      <span class="font-weight-bold text-body-2">{{ sec.name }}</span>
                      <span class="text-caption text-medium-emphasis">(<span class="fnm-currency-num">{{ sec.count }}</span> {{ $t('count') }})</span>
                    </div>
                    <div class="text-right">
                      <span class="font-weight-bold text-body-2 fnm-currency-num" style="color: #E65100;">{{ fmt(sec.total) }} {{ currency }}</span>
                      <span class="text-caption text-medium-emphasis ml-2">(<span class="fnm-currency-num">{{ calcPercent(sec.total, stats.grand_total) }}</span>%)</span>
                    </div>
                  </div>
                  <v-progress-linear
                    :model-value="calcPercent(sec.total, stats.grand_total)"
                    color="#E65100"
                    height="6"
                    rounded
                  ></v-progress-linear>
                </div>
              </div>
            </template>
            <div v-else class="text-center pa-8 text-grey">
              <v-icon size="40" color="grey-lighten-1">mdi-sitemap-outline</v-icon>
              <div class="mt-2 text-caption">{{ $t('no_section_data') }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Corporal Breakdown -->
      <v-col cols="12" md="6">
        <v-card class="wfs-card h-100" elevation="0">
          <v-card-title class="d-flex align-center justify-space-between pa-4 pb-2">
            <div class="d-flex align-center ga-2">
              <v-icon color="#7B1FA2" size="20">mdi-account-tie</v-icon>
              <span class="text-subtitle-1 font-weight-bold">{{ $t('total_by_corporal') }}</span>
              <v-chip size="x-small" color="#7B1FA2" variant="tonal" class="font-weight-bold">
                {{ stats.corporals ? stats.corporals.length : 0 }}
              </v-chip>
            </div>
            <div class="d-flex align-center ga-2">
              <v-btn
                size="x-small"
                variant="tonal"
                color="#7B1FA2"
                rounded="lg"
                prepend-icon="mdi-printer"
                class="font-weight-bold"
                @click="printPage('corporals')"
                :title="$t('print_now')"
              >
                {{ $t('print') }}
              </v-btn>
              <!-- View Switcher -->
              <SegmentedPillToggle
                v-model="corporalViewMode"
                :items="corporalViewItems"
                color="#7B1FA2"
                size="small"
              />
            </div>
          </v-card-title>

          <v-card-text class="pa-4 pt-2">
            <v-skeleton-loader v-if="loading || workerProcessing" type="image" height="280" class="rounded-xl"></v-skeleton-loader>
            <template v-else-if="stats.corporals && stats.corporals.length > 0">
              <!-- Chart View -->
              <div v-show="corporalViewMode === 'chart'">
                <apexchart
                  :key="`corp-chart-${main.is_dark}`"
                  type="bar"
                  height="280"
                  :options="corporalChartOptions"
                  :series="corporalChartSeries"
                ></apexchart>
              </div>

              <!-- List / Ranking View -->
              <div v-show="corporalViewMode === 'list'" class="wfs-rank-list">
                <div
                  v-for="(corp, idx) in stats.corporals"
                  :key="corp.id"
                  class="wfs-rank-item"
                >
                  <div class="d-flex align-center justify-space-between mb-1">
                    <div class="d-flex align-center ga-2">
                      <span class="wfs-rank-badge" :class="'wfs-rank-' + (idx + 1)">#{{ idx + 1 }}</span>
                      <span class="font-weight-bold text-body-2">{{ corp.name }}</span>
                      <span class="text-caption text-medium-emphasis">(<span class="fnm-currency-num">{{ corp.count }}</span> {{ $t('count') }})</span>
                    </div>
                    <div class="text-right">
                      <span class="font-weight-bold text-body-2 fnm-currency-num" style="color: #7B1FA2;">{{ fmt(corp.total) }} {{ currency }}</span>
                      <span class="text-caption text-medium-emphasis ml-2">(<span class="fnm-currency-num">{{ calcPercent(corp.total, stats.grand_total) }}</span>%)</span>
                    </div>
                  </div>
                  <v-progress-linear
                    :model-value="calcPercent(corp.total, stats.grand_total)"
                    color="#7B1FA2"
                    height="6"
                    rounded
                  ></v-progress-linear>
                </div>
              </div>
            </template>
            <div v-else class="text-center pa-8 text-grey">
              <v-icon size="40" color="grey-lighten-1">mdi-account-tie</v-icon>
              <div class="mt-2 text-body-2 font-weight-medium">{{ $t('no_corporal_assigned') }}</div>
              <div class="mt-1 text-caption text-medium-emphasis">{{ $t('no_corporal_data') }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- =========== TOP WORKFORCE CONTRIBUTORS BREAKDOWN TABLE =========== -->
    <v-card class="wfs-card mb-6" elevation="0">
      <v-card-title class="d-flex align-center justify-space-between pa-4 pb-2 flex-wrap ga-2">
        <div class="d-flex align-center ga-2">
          <v-icon color="#42A5F5" size="22">mdi-account-star-outline</v-icon>
          <div>
            <span class="text-subtitle-1 font-weight-bold">{{ $t('top_workforces') }}</span>
            <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-bold ml-2">
              {{ filteredWorkforces.length }}
            </v-chip>
          </div>
        </div>

        <!-- Search & Limit & Print -->
        <div class="d-flex align-center ga-3 flex-wrap">
          <v-btn
            size="small"
            variant="tonal"
            color="#1565C0"
            rounded="lg"
            prepend-icon="mdi-printer"
            class="font-weight-bold"
            @click="printPage('workforces')"
            :title="$t('print_now')"
          >
            {{ $t('print') }}
          </v-btn>

          <v-text-field
            v-model="workforceSearch"
            density="compact"
            variant="outlined"
            rounded
            color="#42A5F5"
            class="text-charge wfs-search-field"
            :placeholder="$t('search') + '...'"
            prepend-inner-icon="mdi-magnify"
            clearable
            hide-details
            style="min-width: 200px; max-width: 280px;"
          ></v-text-field>

          <SegmentedPillToggle
            v-model="workforceLimit"
            :items="workforceLimitItems"
            color="#1E88E5"
            size="small"
          />
        </div>
      </v-card-title>

      <v-card-text class="pa-4 pt-2">
        <v-skeleton-loader v-if="loading || workerProcessing" type="table" class="rounded-xl"></v-skeleton-loader>
        <div v-else-if="paginatedWorkforces.length > 0" class="wfs-table-wrap">
          <table class="wfs-table">
            <thead>
              <tr>
                <th class="text-center" style="width: 60px;">{{ $t('rank') }}</th>
                <th>{{ $t('full_name') }}</th>
                <th>{{ $t('matricule') }}</th>
                <th>{{ $t('cin') }}</th>
                <th>{{ $t('section') }}</th>
                <th>{{ $t('corporal') }}</th>
                <th class="text-center">{{ $t('work_days') }}</th>
                <th class="text-center">{{ $t('count') }}</th>
                <th class="text-right">{{ $t('total_amount') }}</th>
                <th class="text-center" style="width: 80px;">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(wf, idx) in paginatedWorkforces"
                :key="wf.id"
                class="wfs-table-row"
              >
                <!-- Rank -->
                <td class="text-center">
                  <span class="wfs-medal-badge" :class="'wfs-medal-' + (idx + 1)">
                    {{ idx === 0 ? '🥇' : idx === 1 ? '🥈' : idx === 2 ? '🥉' : '#' + (idx + 1) }}
                  </span>
                </td>

                <!-- Full Name -->
                <td>
                  <div class="d-flex align-center ga-2">
                    <v-avatar size="28" color="#1976D2" variant="tonal" class="font-weight-bold text-caption">
                      {{ (wf.name || 'W').charAt(0) }}
                    </v-avatar>
                    <span class="font-weight-bold text-body-2">{{ wf.name }}</span>
                  </div>
                </td>

                <!-- Matricule -->
                <td>
                  <v-chip size="x-small" variant="outlined" color="primary" class="font-weight-medium fnm-currency-num">
                    {{ wf.matricule || '-' }}
                  </v-chip>
                </td>

                <!-- CIN -->
                <td><span class="text-body-2 fnm-currency-num">{{ wf.cin || '-' }}</span></td>

                <!-- Section -->
                <td>
                  <span class="text-body-2 text-medium-emphasis">{{ wf.section || '-' }}</span>
                </td>

                <!-- Corporal -->
                <td>
                  <span class="text-body-2 text-medium-emphasis">{{ wf.corporal || '-' }}</span>
                </td>

                <!-- Work Days -->
                <td class="text-center">
                  <span class="font-weight-bold text-body-2 fnm-currency-num">{{ wf.work_days || 0 }}</span>
                </td>

                <!-- Activities Count -->
                <td class="text-center">
                  <v-chip size="x-small" color="info" variant="tonal" class="font-weight-bold fnm-currency-num">
                    {{ wf.count || 0 }}
                  </v-chip>
                </td>

                <!-- Total Amount -->
                <td class="text-right">
                  <span class="font-weight-bold text-body-2 text-primary fnm-currency-num">{{ fmt(wf.total) }} {{ currency }}</span>
                </td>

                <!-- Action: Quick Filter -->
                <td class="text-center">
                  <v-btn
                    size="x-small"
                    variant="tonal"
                    color="#42A5F5"
                    icon="mdi-filter"
                    :title="$t('filter_this_workforce')"
                    @click="filterSingleWorkforce(wf.id)"
                  ></v-btn>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center pa-8 text-grey">
          <v-icon size="44" color="grey-lighten-1">mdi-account-off-outline</v-icon>
          <div class="mt-2 text-body-2">{{ $t('no_data_available') }}</div>
        </div>
      </v-card-text>
    </v-card>

    <!-- =========== WORKFORCE MOVEMENT & ASSIGNMENT TRACKER (Goal Request 2) =========== -->
    <v-card class="wfs-card mb-6" elevation="0">
      <v-card-title class="d-flex align-center justify-space-between pa-4 pb-2 flex-wrap ga-2">
        <div class="d-flex align-center ga-3">
          <v-avatar color="#1565C0" size="40" class="text-white">
            <v-icon size="22">mdi-routes</v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold" style="color: #1565C0;">
              {{ $t('workforce_movement_tracker_title') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ $t('workforce_movement_tracker_desc') }} ({{ formattedDateRangeText }})
            </div>
          </div>
        </div>

        <!-- Granularity Selector: Daily / Monthly / Yearly -->
        <div class="d-flex align-center ga-2 flex-wrap">
          <span class="text-caption text-medium-emphasis font-weight-bold">{{ $t('granularity') }}:</span>
          <SegmentedPillToggle
            v-model="trackerGranularity"
            :items="trackerGranularityItems"
            color="#1565C0"
            size="small"
            @change="loadMovementTracker"
          />

          <!-- Quick Print Button -->
          <v-btn
            size="small"
            variant="tonal"
            color="#1565C0"
            rounded="lg"
            prepend-icon="mdi-printer"
            class="font-weight-bold"
            @click="printPage('tracker')"
            :title="$t('print_now')"
          >
            {{ $t('print') }}
          </v-btn>
        </div>
      </v-card-title>

      <v-card-text class="pa-4 pt-2">
        <!-- Controls: Multi-select Workforces (One or Many) & Search within tracker -->
        <v-row dense class="mb-3 align-center">
          <v-col cols="12" md="8">
            <v-autocomplete
              v-model="trackerWorkforceIds"
              :items="workforceStore.list"
              item-value="id"
              :item-title="workforceItemTitle"
              :label="$t('select_workforces_to_track')"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-charge wfs-input-field"
              multiple
              chips
              closable-chips
              clearable
              hide-details
              prepend-inner-icon="mdi-account-search"
              @update:model-value="loadMovementTracker"
            >
              <template #chip="{ item, props }">
                <v-chip
                  v-bind="props"
                  color="#1565C0"
                  variant="tonal"
                  size="small"
                  class="font-weight-medium"
                >
                  <v-icon start size="14">mdi-account-hard-hat</v-icon>
                  {{ item.title }}
                </v-chip>
              </template>
            </v-autocomplete>
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="trackerSearch"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-charge wfs-search-field"
              :placeholder="$t('search_corporal_section_team') + '...'"
              prepend-inner-icon="mdi-magnify"
              clearable
              hide-details
            ></v-text-field>
          </v-col>
        </v-row>

        <!-- KPI Chips / Summary Bar -->
        <div class="pa-3 rounded-xl mb-4 d-flex flex-wrap align-center justify-space-between ga-3" style="background: rgba(21, 101, 192, 0.05); border: 1px solid rgba(21, 101, 192, 0.15);">
          <div class="d-flex flex-wrap align-center ga-3">
            <!-- Corporals Worked Under Metric -->
            <div class="d-flex align-center ga-2">
              <v-avatar size="28" color="#7B1FA2" variant="tonal">
                <v-icon size="16">mdi-account-tie</v-icon>
              </v-avatar>
              <div>
                <span class="text-caption text-medium-emphasis">{{ $t('corporals_worked_with') }}: </span>
                <span class="text-subtitle-2 font-weight-bold" style="color: #7B1FA2;">
                  {{ movementStore.statisticsData.summary.distinct_corporals_count || 0 }}
                </span>
              </div>
            </div>

            <v-divider vertical style="height: 24px;"></v-divider>

            <!-- Sections Worked In Metric -->
            <div class="d-flex align-center ga-2">
              <v-avatar size="28" color="#E65100" variant="tonal">
                <v-icon size="16">mdi-sitemap-outline</v-icon>
              </v-avatar>
              <div>
                <span class="text-caption text-medium-emphasis">{{ $t('sections_worked_in') }}: </span>
                <span class="text-subtitle-2 font-weight-bold" style="color: #E65100;">
                  {{ movementStore.statisticsData.summary.distinct_sections_count || 0 }}
                </span>
              </div>
            </div>

            <v-divider vertical style="height: 24px;"></v-divider>

            <!-- Teams Metric -->
            <div class="d-flex align-center ga-2">
              <v-avatar size="28" color="#1565C0" variant="tonal">
                <v-icon size="16">mdi-account-group</v-icon>
              </v-avatar>
              <div>
                <span class="text-caption text-medium-emphasis">{{ $t('teams') }}: </span>
                <span class="text-subtitle-2 font-weight-bold" style="color: #1565C0;">
                  {{ movementStore.statisticsData.summary.distinct_teams_count || 0 }}
                </span>
              </div>
            </div>
          </div>

          <div class="d-flex align-center ga-2">
            <v-chip size="small" color="#1565C0" variant="flat" class="font-weight-bold">
              {{ movementStore.statisticsData.summary.total_activities || 0 }} {{ $t('activities') }}
            </v-chip>
            <v-chip size="small" color="success" variant="flat" class="font-weight-bold">
              {{ fmt(movementStore.statisticsData.summary.total_amount || 0) }} {{ currency }}
            </v-chip>
          </div>
        </div>

        <!-- Per-Workforce Movement Cards Breakdown -->
        <div v-if="movementStore.statisticsData.workforce_summaries?.length > 0" class="mb-4">
          <div class="text-caption font-weight-bold text-uppercase mb-2" style="color: #1565C0; letter-spacing: 0.5px;">
            {{ $t('workforce_movement_breakdown') }}
          </div>
          <v-row dense>
            <v-col
              v-for="wf in movementStore.statisticsData.workforce_summaries"
              :key="'wf-sum-' + wf.id"
              cols="12"
              md="6"
              lg="4"
            >
              <div class="pa-3 rounded-xl border h-100" style="background: rgb(var(--v-theme-surface));">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="d-flex align-center ga-2">
                    <v-avatar size="32" color="#1565C0" variant="tonal" class="font-weight-bold">
                      {{ (wf.full_name || 'W').charAt(0) }}
                    </v-avatar>
                    <div>
                      <div class="font-weight-bold text-body-2">{{ wf.full_name }}</div>
                      <div class="text-caption text-medium-emphasis">#{{ wf.matricule || '-' }}</div>
                    </div>
                  </div>
                  <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold">
                    {{ wf.work_days_count }} {{ $t('days') }}
                  </v-chip>
                </div>

                <!-- Counts: Corporals & Sections -->
                <div class="d-flex align-center justify-space-between text-caption mt-2 pt-2 border-t">
                  <span class="text-medium-emphasis">{{ $t('corporals_count') }}:</span>
                  <v-chip size="x-small" color="#7B1FA2" variant="tonal" class="font-weight-bold">
                    {{ wf.corporals_count }} {{ $t('corporals') }}
                  </v-chip>
                </div>
                <div class="d-flex flex-wrap ga-1 mt-1">
                  <v-chip v-for="cName in wf.corporals_list" :key="'c-' + cName" size="x-small" color="#7B1FA2" variant="outlined">
                    {{ cName }}
                  </v-chip>
                </div>

                <div class="d-flex align-center justify-space-between text-caption mt-2 pt-1 border-t">
                  <span class="text-medium-emphasis">{{ $t('sections_count') }}:</span>
                  <v-chip size="x-small" color="#E65100" variant="tonal" class="font-weight-bold">
                    {{ wf.sections_count }} {{ $t('sections') }}
                  </v-chip>
                </div>
                <div class="d-flex flex-wrap ga-1 mt-1">
                  <v-chip v-for="sName in wf.sections_list" :key="'s-' + sName" size="x-small" color="#E65100" variant="outlined">
                    {{ sName }}
                  </v-chip>
                </div>
              </div>
            </v-col>
          </v-row>
        </div>

        <!-- Detailed Movement Timeline Data Table -->
        <div class="text-caption font-weight-bold text-uppercase mb-2 mt-4" style="color: #1565C0; letter-spacing: 0.5px;">
          {{ $t('movement_activity_log') }} ({{ trackerGranularity.toUpperCase() }})
        </div>

        <v-skeleton-loader v-if="trackerLoading" type="table" class="rounded-xl"></v-skeleton-loader>
        <div v-else-if="filteredTrackerTimeline.length > 0" class="wfs-table-wrap">
          <table class="wfs-table">
            <thead>
              <tr>
                <th>{{ trackerGranularity === 'daily' ? $t('date') : trackerGranularity === 'monthly' ? $t('month') : $t('year') }}</th>
                <th>{{ $t('workforce') }}</th>
                <th>{{ $t('corporal_in_charge') }}</th>
                <th>{{ $t('section') }}</th>
                <th>{{ $t('teams') }}</th>
                <th class="text-center">{{ trackerGranularity === 'daily' ? $t('activities') : $t('days_worked') }}</th>
                <th class="text-right">{{ $t('total_amount') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in filteredTrackerTimeline"
                :key="'t-row-' + idx"
                class="wfs-table-row"
              >
                <!-- Period / Date -->
                <td>
                  <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold">
                    <v-icon start size="12">mdi-calendar</v-icon>
                    {{ row.period_label }}
                  </v-chip>
                </td>

                <!-- Workforce -->
                <td>
                  <div class="font-weight-bold text-body-2">{{ row.workforce_name }}</div>
                  <div v-if="row.matricule" class="text-caption text-medium-emphasis">#{{ row.matricule }}</div>
                </td>

                <!-- Corporal -->
                <td>
                  <v-chip v-if="row.corporal_name && row.corporal_name !== '-'" size="small" color="#7B1FA2" variant="tonal" prepend-icon="mdi-account-tie">
                    {{ row.corporal_name }}
                  </v-chip>
                  <span v-else class="text-medium-emphasis">-</span>
                </td>

                <!-- Section -->
                <td>
                  <v-chip v-if="row.section_name && row.section_name !== '-'" size="small" color="#E65100" variant="tonal" prepend-icon="mdi-sitemap-outline">
                    {{ row.section_name }}
                  </v-chip>
                  <span v-else class="text-medium-emphasis">-</span>
                </td>

                <!-- Teams -->
                <td>
                  <span class="text-body-2">{{ row.team_label || '-' }}</span>
                </td>

                <!-- Count -->
                <td class="text-center">
                  <v-chip size="x-small" color="#00897B" variant="flat" class="font-weight-bold">
                    {{ trackerGranularity === 'daily' ? row.activities_count : row.days_count }}
                  </v-chip>
                </td>

                <!-- Amount -->
                <td class="text-right">
                  <span class="font-weight-bold text-body-2" style="color: #1565C0;">
                    {{ fmt(row.total_amount) }} {{ currency }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center pa-8 text-grey">
          <v-icon size="44" color="grey-lighten-1">mdi-routes</v-icon>
          <div class="mt-2 text-body-2 font-weight-medium">{{ $t('no_movements_for_period') }}</div>
          <div class="mt-1 text-caption text-medium-emphasis">{{ $t('select_different_months_or_workers') }}</div>
        </div>
      </v-card-text>
    </v-card>

    <!-- =========== SUMMARY SNACKBAR (Rule 5 & WebSocket real-time) =========== -->
    <!-- Summary Snackbar -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="7000"
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
        color="#42A5F5"
        height="3"
        class="mb-2"
        rounded
      ></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="summarySnackbar.iconColor || '#42A5F5'" class="mr-2">{{ summarySnackbar.icon || 'mdi-sync' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.message" class="text-medium-emphasis">
          {{ summarySnackbar.message }}
        </div>
        <div v-if="summarySnackbar.details" class="font-weight-medium">
          {{ summarySnackbar.details }}
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>

    <!-- ========================================================================= -->
    <!-- WORKFORCE PRINT CUSTOMIZER MODAL & PRINTER AREA (Warehouses style)        -->
    <!-- ========================================================================= -->
    <WorkforceStatisticsPrintDialog
      v-model="printModalOpen"
      :initial-view="printModalTargetView"
      :selected-year="selectedYear"
      :selected-months="selectedMonths"
      :month-names="monthNames"
      :formatted-date-range-text="formattedDateRangeText"
      :active-period-summary="activePeriodSummary"
      :stats="stats"
      :movement-stats="movementStore.statisticsData"
      @view-change="v => printModalTargetView = v"
    />
  </div>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import * as XLSX from 'xlsx';
import mitt from '@/plugins/mitt';
import { useWebWorkerFn } from '@vueuse/core';
import { toRaw } from 'vue';
import { useMain } from '@/stores/Main';
import { useWorkforce } from '@/stores/Workforce';
import { useCorporal } from '@/stores/Corporal';
import { useSection } from '@/stores/Section';
import { useFortnightActivity } from '@/stores/FortnightActivity';
import { useWorkforceMovement } from '@/stores/WorkforceMovement';
import WorkforceStatisticsPrintDialog from './WorkforceStatisticsPrintDialog.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';

/**
 * High-performance pure worker function executed in a dedicated Web Worker thread
 * via useWebWorkerFn. Ensures the Vue 3 main UI thread stays completely responsive
 * (60fps) during multi-month statistics aggregation, sorting, rankings, chart datasets
 * calculation, and real-time filtering without blocking the browser.
 */
function workerProcessWorkforceStats(payload) {
  const {
    action = 'aggregate',
    results = [],
    workforceSearch = '',
    workforceLimit = 10,
    trackerSearch = '',
    trackerTimeline = [],
    allWorkforces = [],
  } = payload || {};

  // 1. FILTER TRACKER TIMELINE ACTION
  if (action === 'filter_tracker_timeline') {
    const list = trackerTimeline || [];
    const q = (trackerSearch || '').toLowerCase().trim();
    if (!q) {
      return { action: 'filter_tracker_timeline', filteredTrackerTimeline: list };
    }
    const filtered = [];
    for (let i = 0; i < list.length; i++) {
      const row = list[i];
      if (!row) continue;
      const wName = (row.workforce_name || '').toLowerCase();
      const mat = String(row.matricule || '').toLowerCase();
      const cName = (row.corporal_name || '').toLowerCase();
      const sName = (row.section_name || '').toLowerCase();
      const tLabel = (row.team_label || '').toLowerCase();
      if (
        wName.includes(q) ||
        mat.includes(q) ||
        cName.includes(q) ||
        sName.includes(q) ||
        tLabel.includes(q)
      ) {
        filtered.push(row);
      }
    }
    return { action: 'filter_tracker_timeline', filteredTrackerTimeline: filtered };
  }

  // 2. FILTER WORKFORCES ACTION
  if (action === 'filter_workforces') {
    const list = allWorkforces || [];
    const q = (workforceSearch || '').toLowerCase().trim();
    let filtered = [];
    if (!q) {
      filtered = list;
    } else {
      for (let i = 0; i < list.length; i++) {
        const w = list[i];
        if (!w) continue;
        const name = (w.name || '').toLowerCase();
        const mat = String(w.matricule || '').toLowerCase();
        const cin = String(w.cin || '').toLowerCase();
        const sec = String(w.section || '').toLowerCase();
        const corp = String(w.corporal || '').toLowerCase();
        if (
          name.includes(q) ||
          mat.includes(q) ||
          cin.includes(q) ||
          sec.includes(q) ||
          corp.includes(q)
        ) {
          filtered.push(w);
        }
      }
    }
    const paginated = (workforceLimit && workforceLimit > 0)
      ? filtered.slice(0, workforceLimit)
      : filtered;
    return {
      action: 'filter_workforces',
      filteredWorkforces: filtered,
      paginatedWorkforces: paginated,
    };
  }

  // 3. AGGREGATE ACTION (Multi-month statistics aggregation + charts + filtering)
  const merged = {
    daily: [],
    sections: [],
    corporals: [],
    workforces: [],
    grand_total: 0,
    total_activities: 0,
  };

  const dailyMap = {};
  const sectionMap = {};
  const corporalMap = {};
  const workforceMap = {};

  const resList = Array.isArray(results) ? results : [];
  for (let r = 0; r < resList.length; r++) {
    const res = resList[r];
    if (!res) continue;

    // Daily items
    const dailyArr = res.daily || [];
    for (let i = 0; i < dailyArr.length; i++) {
      const d = dailyArr[i];
      if (!d || !d.date) continue;
      if (!dailyMap[d.date]) {
        dailyMap[d.date] = { date: d.date, total: Number(d.total) || 0, count: Number(d.count) || 0 };
      } else {
        dailyMap[d.date].total += Number(d.total) || 0;
        dailyMap[d.date].count += Number(d.count) || 0;
      }
    }

    // Sections
    const secArr = res.sections || [];
    for (let i = 0; i < secArr.length; i++) {
      const s = secArr[i];
      if (!s) continue;
      const sId = s.id != null ? String(s.id) : (s.name || String(i));
      if (!sectionMap[sId]) {
        sectionMap[sId] = {
          id: s.id,
          name: s.name || '',
          total: Number(s.total) || 0,
          count: Number(s.count) || 0,
        };
      } else {
        sectionMap[sId].total += Number(s.total) || 0;
        sectionMap[sId].count += Number(s.count) || 0;
      }
    }

    // Corporals
    const corpArr = res.corporals || [];
    for (let i = 0; i < corpArr.length; i++) {
      const c = corpArr[i];
      if (!c) continue;
      const cId = c.id != null ? String(c.id) : (c.name || String(i));
      if (!corporalMap[cId]) {
        corporalMap[cId] = {
          id: c.id,
          name: c.name || '',
          total: Number(c.total) || 0,
          count: Number(c.count) || 0,
        };
      } else {
        corporalMap[cId].total += Number(c.total) || 0;
        corporalMap[cId].count += Number(c.count) || 0;
      }
    }

    // Workforces
    const wfArr = res.workforces || [];
    for (let i = 0; i < wfArr.length; i++) {
      const w = wfArr[i];
      if (!w) continue;
      const wId = w.id != null ? String(w.id) : (w.name || String(i));
      if (!workforceMap[wId]) {
        workforceMap[wId] = {
          id: w.id,
          name: w.name || '',
          matricule: w.matricule || '',
          cin: w.cin || '',
          section: w.section || '',
          corporal: w.corporal || '',
          total: Number(w.total) || 0,
          count: Number(w.count) || 0,
          work_days: Number(w.work_days) || 0,
        };
      } else {
        workforceMap[wId].total += Number(w.total) || 0;
        workforceMap[wId].count += Number(w.count) || 0;
        workforceMap[wId].work_days += Number(w.work_days) || 0;
      }
    }

    merged.grand_total += Number(res.grand_total) || 0;
    merged.total_activities += Number(res.total_activities) || 0;
  }

  // Sort daily chronologically
  const sortedDaily = Object.values(dailyMap).sort((a, b) => a.date.localeCompare(b.date));
  merged.daily = sortedDaily;

  // Sort sections descending by total
  const sortedSections = Object.values(sectionMap).sort((a, b) => b.total - a.total);
  for (let i = 0; i < sortedSections.length; i++) {
    sortedSections[i].rank = i + 1;
    sortedSections[i].sharePct = merged.grand_total > 0
      ? Math.round((sortedSections[i].total / merged.grand_total) * 100)
      : 0;
  }
  merged.sections = sortedSections;

  // Sort corporals descending by total
  const sortedCorporals = Object.values(corporalMap).sort((a, b) => b.total - a.total);
  for (let i = 0; i < sortedCorporals.length; i++) {
    sortedCorporals[i].rank = i + 1;
    sortedCorporals[i].sharePct = merged.grand_total > 0
      ? Math.round((sortedCorporals[i].total / merged.grand_total) * 100)
      : 0;
  }
  merged.corporals = sortedCorporals;

  // Sort workforces descending by total
  const sortedWorkforces = Object.values(workforceMap).sort((a, b) => b.total - a.total);
  for (let i = 0; i < sortedWorkforces.length; i++) {
    sortedWorkforces[i].rank = i + 1;
    sortedWorkforces[i].sharePct = merged.grand_total > 0
      ? Math.round((sortedWorkforces[i].total / merged.grand_total) * 100)
      : 0;
  }
  merged.workforces = sortedWorkforces;

  // Precompute chart datasets for daily, monthly, yearly
  const chartDaily = sortedDaily.map(d => ({ label: d.date, total: d.total, count: d.count }));

  const monthAggMap = {};
  for (let i = 0; i < sortedDaily.length; i++) {
    const d = sortedDaily[i];
    const mKey = d.date.substring(0, 7);
    if (!monthAggMap[mKey]) monthAggMap[mKey] = { label: mKey, total: 0, count: 0 };
    monthAggMap[mKey].total += d.total;
    monthAggMap[mKey].count += d.count;
  }
  const chartMonthly = Object.values(monthAggMap);

  const yearAggMap = {};
  for (let i = 0; i < sortedDaily.length; i++) {
    const d = sortedDaily[i];
    const yKey = d.date.substring(0, 4);
    if (!yearAggMap[yKey]) yearAggMap[yKey] = { label: yKey, total: 0, count: 0 };
    yearAggMap[yKey].total += d.total;
    yearAggMap[yKey].count += d.count;
  }
  const chartYearly = Object.values(yearAggMap);

  // Filter workforces by search query
  const qWf = (workforceSearch || '').toLowerCase().trim();
  let filteredWf = [];
  if (!qWf) {
    filteredWf = sortedWorkforces;
  } else {
    for (let i = 0; i < sortedWorkforces.length; i++) {
      const w = sortedWorkforces[i];
      const name = (w.name || '').toLowerCase();
      const mat = String(w.matricule || '').toLowerCase();
      const cin = String(w.cin || '').toLowerCase();
      const sec = String(w.section || '').toLowerCase();
      const corp = String(w.corporal || '').toLowerCase();
      if (
        name.includes(qWf) ||
        mat.includes(qWf) ||
        cin.includes(qWf) ||
        sec.includes(qWf) ||
        corp.includes(qWf)
      ) {
        filteredWf.push(w);
      }
    }
  }

  const paginatedWf = (workforceLimit && workforceLimit > 0)
    ? filteredWf.slice(0, workforceLimit)
    : filteredWf;

  return {
    action: 'aggregate',
    stats: merged,
    precomputedCharts: {
      daily: chartDaily,
      monthly: chartMonthly,
      yearly: chartYearly,
    },
    filteredWorkforces: filteredWf,
    paginatedWorkforces: paginatedWf,
  };
}

export default {
  name: 'WorkforceStatistics',
  components: {
    apexchart: VueApexCharts,
    WorkforceStatisticsPrintDialog,
    SegmentedPillToggle,
  },
  setup() {
    let workerFn = null;
    let workerStatus = null;
    let workerTerminate = null;
    try {
      const workerTools = useWebWorkerFn(workerProcessWorkforceStats);
      workerFn = workerTools.workerFn;
      workerStatus = workerTools.workerStatus;
      workerTerminate = workerTools.workerTerminate;
    } catch (e) {
      console.warn('[WorkforceStatistics] useWebWorkerFn initialization fallback:', e);
    }
    return {
      workerFn,
      workerStatus,
      workerTerminate,
    };
  },
  data() {
    const now = new Date();
    return {
      selectedYear: now.getFullYear(),
      selectedMonths: [now.getMonth()],
      yearMenuOpen: false,
      printModalOpen: false,
      printModalTargetView: 'full_summary',
      filterWorkforceIds: [],
      filterSectionIds: [],
      filterCorporalIds: [],
      trackerWorkforceIds: [],
      trackerGranularity: 'daily',
      trackerSearch: '',
      trackerLoading: false,
      loading: false,
      chartType: 'area', // 'area' or 'bar'
      chartPeriod: 'daily', // 'daily', 'monthly', 'yearly'
      sectionViewMode: 'chart', // 'chart' or 'list'
      corporalViewMode: 'chart', // 'chart' or 'list'
      workforceSearch: '',
      workforceLimit: 10,
      stats: {
        daily: [],
        sections: [],
        corporals: [],
        workforces: [],
        grand_total: 0,
        total_activities: 0,
      },
      workerProcessing: false,
      workerFilteredWorkforces: null,
      workerPaginatedWorkforces: null,
      workerFilteredTimeline: null,
      precomputedCharts: {
        daily: [],
        monthly: [],
        yearly: [],
      },
      monthNames: [
        this.$t('month_january') || 'Jan', this.$t('month_february') || 'Feb',
        this.$t('month_march') || 'Mar', this.$t('month_april') || 'Apr',
        this.$t('month_may') || 'May', this.$t('month_june') || 'Jun',
        this.$t('month_july') || 'Jul', this.$t('month_august') || 'Aug',
        this.$t('month_september') || 'Sep', this.$t('month_october') || 'Oct',
        this.$t('month_november') || 'Nov', this.$t('month_december') || 'Dec',
      ],
      // Summary Snackbar state
      summarySnackbar: {
        show: false,
        title: '',
        message: '',
        details: '',
        icon: 'mdi-sync',
        iconColor: '#42A5F5',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    workforceStore() { return useWorkforce(); },
    movementStore() { return useWorkforceMovement(); },
    corporalStore() { return useCorporal(); },
    sectionStore() { return useSection(); },
    activityStore() { return useFortnightActivity(); },
    filteredTrackerTimeline() {
      if (this.workerFilteredTimeline !== null) {
        return this.workerFilteredTimeline;
      }
      const list = this.movementStore.statisticsData.timeline || [];
      if (!this.trackerSearch) return list;
      const q = this.trackerSearch.toLowerCase().trim();
      return list.filter(row =>
        (row.workforce_name && row.workforce_name.toLowerCase().includes(q)) ||
        (row.matricule && String(row.matricule).toLowerCase().includes(q)) ||
        (row.corporal_name && row.corporal_name.toLowerCase().includes(q)) ||
        (row.section_name && row.section_name.toLowerCase().includes(q)) ||
        (row.team_label && row.team_label.toLowerCase().includes(q))
      );
    },
    yearOptions() {
      const y = new Date().getFullYear();
      const options = [];
      for (let i = y - 5; i <= y + 1; i++) {
        options.push(i);
      }
      return options;
    },
    chartTypeItems() {
      return [
        { value: 'area', label: this.$t('area_chart') || 'Area', icon: 'mdi-chart-areaspline' },
        { value: 'bar', label: this.$t('bar_chart') || 'Bar', icon: 'mdi-chart-bar' },
      ];
    },
    chartPeriodItems() {
      return [
        { value: 'daily', label: this.$t('daily') || 'Daily' },
        { value: 'monthly', label: this.$t('monthly') || 'Monthly' },
        { value: 'yearly', label: this.$t('yearly') || 'Yearly' },
      ];
    },
    sectionViewItems() {
      return [
        { value: 'chart', label: this.$t('chart_view') || 'Chart', icon: 'mdi-chart-bar' },
        { value: 'list', label: this.$t('list_view') || 'List', icon: 'mdi-format-list-bulleted' },
      ];
    },
    corporalViewItems() {
      return [
        { value: 'chart', label: this.$t('chart_view') || 'Chart', icon: 'mdi-chart-bar' },
        { value: 'list', label: this.$t('list_view') || 'List', icon: 'mdi-format-list-bulleted' },
      ];
    },
    workforceLimitItems() {
      return [
        { value: 10, label: 'Top 10' },
        { value: 25, label: 'Top 25' },
        { value: 0, label: this.$t('all_available') || 'All' },
      ];
    },
    trackerGranularityItems() {
      return [
        { value: 'daily', label: this.$t('daily') || 'Daily', icon: 'mdi-calendar-today' },
        { value: 'monthly', label: this.$t('monthly') || 'Monthly', icon: 'mdi-calendar-month' },
        { value: 'yearly', label: this.$t('yearly') || 'Yearly', icon: 'mdi-calendar' },
      ];
    },
    currency() {
      return this.main.currency || 'DH';
    },
    dynamicSubtitle() {
      return `${this.$t('workforce_statistics_desc')} • ${this.selectedYear}`;
    },
    activePeriodSummary() {
      if (!this.selectedMonths.length) return '';
      if (this.selectedMonths.length === 1) {
        return `${this.monthNames[this.selectedMonths[0]]} ${this.selectedYear}`;
      }
      return `${this.selectedMonths.length} ${this.$t('months') || 'Months'} (${this.selectedYear})`;
    },
    activeFiltersCount() {
      return this.filterWorkforceIds.length + this.filterSectionIds.length + this.filterCorporalIds.length;
    },
    dailyAverage() {
      const activeDays = (this.stats.daily || []).length;
      if (!activeDays) return 0;
      return this.stats.grand_total / activeDays;
    },
    topSectionInfo() {
      const secs = this.stats.sections || [];
      if (!secs.length) return this.$t('no_section_data') || 'No sections';
      const top = secs[0];
      return `${this.$t('top_contributor')}: ${top.name} (${this.fmt(top.total)} ${this.currency})`;
    },
    formattedDateRangeText() {
      if (!this.selectedMonths.length) return '';
      const sorted = [...this.selectedMonths].sort((a, b) => a - b);
      const startMonth = sorted[0];
      const endMonth = sorted[sorted.length - 1];
      const start = `01 ${this.monthNames[startMonth]} ${this.selectedYear}`;
      const lastDay = new Date(this.selectedYear, endMonth + 1, 0).getDate();
      const end = `${lastDay} ${this.monthNames[endMonth]} ${this.selectedYear}`;
      return `${this.$t('from') || 'From'} ${start} ${this.$t('to') || 'to'} ${end}`;
    },
    filteredWorkforces() {
      if (this.workerFilteredWorkforces !== null) {
        return this.workerFilteredWorkforces;
      }
      const list = this.stats.workforces || [];
      if (!this.workforceSearch) return list;
      const q = this.workforceSearch.toLowerCase().trim();
      return list.filter(w =>
        (w.name && w.name.toLowerCase().includes(q)) ||
        (w.matricule && String(w.matricule).toLowerCase().includes(q)) ||
        (w.cin && String(w.cin).toLowerCase().includes(q)) ||
        (w.section && String(w.section).toLowerCase().includes(q))
      );
    },
    paginatedWorkforces() {
      if (this.workerPaginatedWorkforces !== null) {
        return this.workerPaginatedWorkforces;
      }
      const list = this.filteredWorkforces;
      if (this.workforceLimit === 0) return list;
      return list.slice(0, this.workforceLimit);
    },
    // Main Trend Chart aggregation
    aggregatedChartData() {
      if (
        this.precomputedCharts &&
        this.precomputedCharts[this.chartPeriod] &&
        this.precomputedCharts[this.chartPeriod].length
      ) {
        return this.precomputedCharts[this.chartPeriod];
      }

      const daily = this.stats.daily || [];
      if (!daily.length) return [];

      if (this.chartPeriod === 'daily') {
        return daily.map(d => ({ label: d.date, total: d.total, count: d.count }));
      }

      if (this.chartPeriod === 'monthly') {
        const map = {};
        daily.forEach(d => {
          const key = d.date.substring(0, 7); // YYYY-MM
          if (!map[key]) map[key] = { label: key, total: 0, count: 0 };
          map[key].total += d.total;
          map[key].count += d.count;
        });
        return Object.values(map);
      }

      // yearly
      const map = {};
      daily.forEach(d => {
        const key = d.date.substring(0, 4); // YYYY
        if (!map[key]) map[key] = { label: key, total: 0, count: 0 };
        map[key].total += d.total;
        map[key].count += d.count;
      });
      return Object.values(map);
    },
    mainChartSeries() {
      return [{
        name: this.$t('total_amount'),
        data: this.aggregatedChartData.map(d => d.total),
      }];
    },
    mainChartOptions() {
      const isDark = Boolean(this.main?.is_dark);
      const isSinglePoint = this.aggregatedChartData.length <= 1;
      return {
        chart: {
          type: this.chartType,
          toolbar: {
            show: true,
            tools: { download: true, selection: false, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
          },
          fontFamily: 'Inter, sans-serif',
          background: 'transparent',
        },
        colors: ['#1E88E5'],
        stroke: {
          curve: 'smooth',
          width: this.chartType === 'area' ? 3 : 0,
        },
        plotOptions: {
          bar: {
            borderRadius: 6,
            columnWidth: '50%',
          },
        },
        fill: {
          type: this.chartType === 'area' ? 'gradient' : 'solid',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [0, 95, 100],
          },
        },
        markers: {
          size: isSinglePoint ? 7 : (this.chartPeriod === 'daily' && this.aggregatedChartData.length > 20 ? 0 : 4),
          strokeColors: '#ffffff',
          strokeWidth: 2,
          hover: { size: 7 },
        },
        xaxis: {
          categories: this.aggregatedChartData.map(d => d.label),
          labels: {
            rotate: -45,
            rotateAlways: this.aggregatedChartData.length > 12,
            style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px', fontWeight: 600 },
          },
          axisBorder: { show: false },
          axisTicks: { show: false },
        },
        yaxis: {
          labels: {
            formatter: v => this.fmt(v) + ' ' + this.currency,
            style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px', fontWeight: 600 },
          },
        },
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          y: {
            formatter: v => this.fmt(v) + ' ' + this.currency,
          },
        },
        grid: {
          borderColor: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)',
          strokeDashArray: 3,
        },
        dataLabels: {
          enabled: this.chartPeriod !== 'daily' || isSinglePoint,
          formatter: v => this.fmt(v),
          style: { fontSize: '11px', fontWeight: 700 },
        },
        theme: { mode: isDark ? 'dark' : 'light' },
      };
    },
    // Section chart
    sectionChartSeries() {
      return [{
        name: this.$t('total_amount'),
        data: (this.stats.sections || []).map(s => s.total),
      }];
    },
    sectionChartOptions() {
      const isDark = Boolean(this.main?.is_dark);
      return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
        colors: ['#EF6C00'],
        plotOptions: { bar: { borderRadius: 6, horizontal: false, columnWidth: '55%', distributed: true } },
        xaxis: {
          categories: (this.stats.sections || []).map(s => s.name),
          labels: {
            rotate: -35,
            rotateAlways: true,
            style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px', fontWeight: 600 },
          },
        },
        yaxis: {
          labels: {
            formatter: v => this.fmt(v),
            style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px' },
          },
        },
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          y: { formatter: v => this.fmt(v) + ' ' + this.currency },
        },
        grid: {
          borderColor: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)',
          strokeDashArray: 3,
        },
        dataLabels: { enabled: true, formatter: v => this.fmt(v), style: { fontSize: '10px' } },
        legend: { show: false },
        theme: { mode: isDark ? 'dark' : 'light' },
      };
    },
    // Corporal chart
    corporalChartSeries() {
      return [{
        name: this.$t('total_amount'),
        data: (this.stats.corporals || []).map(c => c.total),
      }];
    },
    corporalChartOptions() {
      const isDark = Boolean(this.main?.is_dark);
      return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
        colors: ['#7B1FA2'],
        plotOptions: { bar: { borderRadius: 6, horizontal: false, columnWidth: '55%', distributed: true } },
        xaxis: {
          categories: (this.stats.corporals || []).map(c => c.name),
          labels: {
            rotate: -35,
            rotateAlways: true,
            style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px', fontWeight: 600 },
          },
        },
        yaxis: {
          labels: {
            formatter: v => this.fmt(v),
            style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px' },
          },
        },
        tooltip: {
          theme: isDark ? 'dark' : 'light',
          y: { formatter: v => this.fmt(v) + ' ' + this.currency },
        },
        grid: {
          borderColor: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)',
          strokeDashArray: 3,
        },
        dataLabels: { enabled: true, formatter: v => this.fmt(v), style: { fontSize: '10px' } },
        legend: { show: false },
        theme: { mode: isDark ? 'dark' : 'light' },
      };
    },
  },
  watch: {
    selectedYear() {
      this.selectedMonths = this.selectedMonths.filter(m => !this.isMonthDisabled(m));
      if (!this.selectedMonths.length) {
        const now = new Date();
        if (this.selectedYear === now.getFullYear()) {
          this.selectedMonths = [now.getMonth()];
        } else {
          this.selectedMonths = [11];
        }
      }
      this.loadStats();
    },
    selectedMonths: {
      handler() {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => {
          this.loadStats();
        }, 400);
      },
      deep: true,
    },
    filterWorkforceIds() { this.loadStats(); },
    filterSectionIds() { this.loadStats(); },
    filterCorporalIds() { this.loadStats(); },
    workforceSearch() {
      this.scheduleWorkerFilterWorkforces();
    },
    workforceLimit() {
      this.scheduleWorkerFilterWorkforces();
    },
    trackerSearch() {
      this.scheduleWorkerFilterTimeline();
    },
  },
  async mounted() {
    await Promise.all([
      this.workforceStore.fetch(),
      this.corporalStore.fetch(),
      this.sectionStore.fetch(),
    ]);
    this.loadStats();

    // Real-time synchronization via mitt event bus
    mitt.on('model-changed', this.handleModelChanged);
  },
  beforeUnmount() {
    clearTimeout(this._debounceTimer);
    clearTimeout(this._wfSearchDebounce);
    clearTimeout(this._trackerSearchDebounce);
    clearInterval(this.snackbarTimer);
    if (this.workerTerminate) {
      this.workerTerminate();
    }
    mitt.off('model-changed', this.handleModelChanged);
  },
  methods: {
    changeYear(delta) {
      const next = this.selectedYear + delta;
      if (next < 2020) return;
      this.selectedYear = next;
    },
    fmt(num) {
      if (this.main.formatDisplayNumber) return this.main.formatDisplayNumber(num);
      return Number(num || 0).toFixed(2);
    },
    calcPercent(val, total) {
      if (!total || total <= 0) return 0;
      return Math.round(((val || 0) / total) * 100);
    },
    workforceItemTitle(item) {
      return `${item.full_name} (${item.matricule || '-'})`;
    },
    workforceFilter(itemTitle, queryText, item) {
      const q = queryText.toLowerCase();
      const raw = item.raw;
      return (raw.full_name || '').toLowerCase().includes(q)
        || (raw.matricule || '').toLowerCase().includes(q)
        || (raw.cin || '').toLowerCase().includes(q);
    },
    isMonthDisabled(idx) {
      const now = new Date();
      if (this.selectedYear === now.getFullYear()) {
        return idx > now.getMonth();
      }
      return false;
    },
    toggleMonth(idx) {
      if (this.isMonthDisabled(idx)) return;
      const pos = this.selectedMonths.indexOf(idx);
      if (pos >= 0) {
        if (this.selectedMonths.length > 1) this.selectedMonths.splice(pos, 1);
      } else {
        this.selectedMonths.push(idx);
      }
    },
    selectCurrentMonth() {
      const now = new Date();
      this.selectedYear = now.getFullYear();
      this.selectedMonths = [now.getMonth()];
    },
    selectLastMonth() {
      const now = new Date();
      let m = now.getMonth() - 1;
      let y = now.getFullYear();
      if (m < 0) {
        m = 11;
        y -= 1;
      }
      this.selectedYear = y;
      this.selectedMonths = [m];
    },
    selectAllAvailableMonths() {
      const now = new Date();
      const maxMonth = this.selectedYear === now.getFullYear() ? now.getMonth() : 11;
      const all = [];
      for (let i = 0; i <= maxMonth; i++) all.push(i);
      this.selectedMonths = all;
    },
    resetFilters() {
      this.filterWorkforceIds = [];
      this.filterSectionIds = [];
      this.filterCorporalIds = [];
      this.workforceSearch = '';
    },
    filterSingleWorkforce(wfId) {
      this.filterWorkforceIds = [wfId];
      window.scrollTo({ top: 300, behavior: 'smooth' });
    },
    async loadStats() {
      if (!this.selectedMonths.length) return;

      this.loading = true;

      const baseParams = {};
      if (this.filterWorkforceIds.length) baseParams.workforce_ids = this.filterWorkforceIds;
      if (this.filterSectionIds.length) baseParams.section_ids = this.filterSectionIds;
      if (this.filterCorporalIds.length) baseParams.corporal_ids = this.filterCorporalIds;

      const requests = this.selectedMonths.map(monthIdx => {
        const start = `${this.selectedYear}-${String(monthIdx + 1).padStart(2, '0')}-01`;
        const lastDay = new Date(this.selectedYear, monthIdx + 1, 0).getDate();
        const end = `${this.selectedYear}-${String(monthIdx + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
        return this.activityStore.fetchStatistics({ ...baseParams, date_start: start, date_end: end });
      });

      const rawResults = await Promise.all(requests);

      this.workerProcessing = true;
      try {
        const payload = {
          action: 'aggregate',
          results: JSON.parse(JSON.stringify(toRaw(rawResults))),
          workforceSearch: this.workforceSearch,
          workforceLimit: this.workforceLimit,
        };

        let workerResult = null;
        if (this.workerFn) {
          try {
            workerResult = await this.workerFn(payload);
          } catch (workerErr) {
            console.warn('[WorkforceStats] Worker error, falling back to sync:', workerErr);
            workerResult = workerProcessWorkforceStats(payload);
          }
        } else {
          workerResult = workerProcessWorkforceStats(payload);
        }

        if (workerResult && workerResult.stats) {
          this.stats = workerResult.stats;
          this.precomputedCharts = workerResult.precomputedCharts;
          this.workerFilteredWorkforces = workerResult.filteredWorkforces;
          this.workerPaginatedWorkforces = workerResult.paginatedWorkforces;
        }
      } catch (err) {
        console.error('[WorkforceStats] Processing error:', err);
      } finally {
        this.workerProcessing = false;
        this.loading = false;
      }

      this.loadMovementTracker();
    },

    /**
     * Load workforce movement tracking data for the selected period and workforces.
     */
    async loadMovementTracker() {
      if (!this.selectedMonths.length) return;
      this.trackerLoading = true;
      try {
        const sorted = [...this.selectedMonths].sort((a, b) => a - b);
        const startMonth = String(sorted[0] + 1).padStart(2, '0');
        const endMonthIdx = sorted[sorted.length - 1];
        const endMonth = String(endMonthIdx + 1).padStart(2, '0');
        const lastDay = String(new Date(this.selectedYear, endMonthIdx + 1, 0).getDate()).padStart(2, '0');
        const startDate = `${this.selectedYear}-${startMonth}-01`;
        const endDate = `${this.selectedYear}-${endMonth}-${lastDay}`;

        const params = {
          start_date: startDate,
          end_date: endDate,
          granularity: this.trackerGranularity,
        };

        const wfIds = this.trackerWorkforceIds.length ? this.trackerWorkforceIds : this.filterWorkforceIds;
        if (wfIds.length) {
          params.workforce_ids = wfIds.join(',');
        }

        await this.movementStore.fetchStatisticsMovements(params);
        this.scheduleWorkerFilterTimeline();
      } catch (e) {
        console.error('Failed to load movement tracker statistics:', e);
      } finally {
        this.trackerLoading = false;
      }
    },

    scheduleWorkerFilterWorkforces() {
      clearTimeout(this._wfSearchDebounce);
      this._wfSearchDebounce = setTimeout(async () => {
        const all = this.stats.workforces || [];
        if (!all.length) {
          this.workerFilteredWorkforces = [];
          this.workerPaginatedWorkforces = [];
          return;
        }
        try {
          const payload = {
            action: 'filter_workforces',
            allWorkforces: JSON.parse(JSON.stringify(toRaw(all))),
            workforceSearch: this.workforceSearch,
            workforceLimit: this.workforceLimit,
          };
          let res = null;
          if (this.workerFn) {
            try {
              res = await this.workerFn(payload);
            } catch (workerErr) {
              res = workerProcessWorkforceStats(payload);
            }
          } else {
            res = workerProcessWorkforceStats(payload);
          }
          if (res) {
            this.workerFilteredWorkforces = res.filteredWorkforces;
            this.workerPaginatedWorkforces = res.paginatedWorkforces;
          }
        } catch (err) {
          console.warn('[WorkforceStats] Worker filter workforces error:', err);
        }
      }, 100);
    },

    scheduleWorkerFilterTimeline() {
      clearTimeout(this._trackerSearchDebounce);
      this._trackerSearchDebounce = setTimeout(async () => {
        const list = this.movementStore.statisticsData?.timeline || [];
        if (!list.length) {
          this.workerFilteredTimeline = [];
          return;
        }
        try {
          const payload = {
            action: 'filter_tracker_timeline',
            trackerTimeline: JSON.parse(JSON.stringify(toRaw(list))),
            trackerSearch: this.trackerSearch,
          };
          let res = null;
          if (this.workerFn) {
            try {
              res = await this.workerFn(payload);
            } catch (workerErr) {
              res = workerProcessWorkforceStats(payload);
            }
          } else {
            res = workerProcessWorkforceStats(payload);
          }
          if (res && res.filteredTrackerTimeline) {
            this.workerFilteredTimeline = res.filteredTrackerTimeline;
          }
        } catch (err) {
          console.warn('[WorkforceStats] Worker timeline filter error:', err);
        }
      }, 100);
    },

    /**
     * Handle incoming WebSocket model-changed events.
     * Re-fetches statistics when FortnightActivity records change.
     */
    handleModelChanged(data) {
      const model = data?.model || '';
      const relevantModels = ['FortnightActivity', 'Corporal', 'Section', 'Workforce', 'WorkforceMovement'];
      if (!relevantModels.includes(model)) return;

      if (model === 'FortnightActivity' || model === 'WorkforceMovement') {
        this.loadStats();
        this.loadMovementTracker();
        this.triggerSummarySnackbar({
          title: this.$t('realtime_sync') || 'Real-time Update',
          message: data?.user_name ? `${data.user_name} updated workforce records` : 'Activity data refreshed in real-time',
          details: data?.date ? `${data.date} • ${this.fmt(data?.price || 0)} ${this.currency}` : '',
          icon: 'mdi-sync',
          iconColor: '#42A5F5',
        });
      } else {
        if (model === 'Workforce') this.workforceStore.fetch();
        if (model === 'Section') this.sectionStore.fetch();
        if (model === 'Corporal') this.corporalStore.fetch();
        this.loadStats();
        this.loadMovementTracker();
      }
    },

    triggerSummarySnackbar({ title, message, details, icon, iconColor }) {
      this.summarySnackbar = {
        show: true,
        title,
        message,
        details,
        icon: icon || 'mdi-check-circle',
        iconColor: iconColor || '#42A5F5',
      };
      this.snackbarProgress = 100;
      clearInterval(this.snackbarTimer);
      const startTime = Date.now();
      const duration = 7000;
      this.snackbarTimer = setInterval(() => {
        const elapsed = Date.now() - startTime;
        this.snackbarProgress = Math.max(0, 100 - (elapsed / duration) * 100);
        if (elapsed >= duration) clearInterval(this.snackbarTimer);
      }, 50);
    },

    /**
     * 1-Click Export to Excel Workbook using XLSX
     */
    exportToExcel() {
      try {
        const wb = XLSX.utils.book_new();

        // 1. Sheet: Daily Totals
        const dailyRows = (this.stats.daily || []).map(d => ({
          Date: d.date,
          'Total Amount (DH)': d.total,
          'Activities Count': d.count,
        }));
        const wsDaily = XLSX.utils.json_to_sheet(dailyRows);
        XLSX.utils.book_append_sheet(wb, wsDaily, 'Daily Totals');

        // 2. Sheet: Sections
        const sectionRows = (this.stats.sections || []).map(s => ({
          Section: s.name,
          'Activities Count': s.count,
          'Total Amount (DH)': s.total,
          'Share (%)': this.calcPercent(s.total, this.stats.grand_total) + '%',
        }));
        const wsSections = XLSX.utils.json_to_sheet(sectionRows);
        XLSX.utils.book_append_sheet(wb, wsSections, 'Sections');

        // 3. Sheet: Corporals
        const corporalRows = (this.stats.corporals || []).map(c => ({
          Corporal: c.name,
          'Activities Count': c.count,
          'Total Amount (DH)': c.total,
          'Share (%)': this.calcPercent(c.total, this.stats.grand_total) + '%',
        }));
        const wsCorporals = XLSX.utils.json_to_sheet(corporalRows);
        XLSX.utils.book_append_sheet(wb, wsCorporals, 'Corporals');

        // 4. Sheet: Workforces
        const workforceRows = (this.stats.workforces || []).map((w, idx) => ({
          Rank: idx + 1,
          'Full Name': w.name,
          Matricule: w.matricule || '',
          CIN: w.cin || '',
          Section: w.section || '',
          Corporal: w.corporal || '',
          'Work Days': w.work_days || 0,
          'Activities Count': w.count || 0,
          'Total Amount (DH)': w.total || 0,
        }));
        const wsWorkforces = XLSX.utils.json_to_sheet(workforceRows);
        XLSX.utils.book_append_sheet(wb, wsWorkforces, 'Workforces Ranking');

        const fileName = `Workforce_Statistics_${this.selectedYear}_${this.selectedMonths.map(m => m + 1).join('-')}.xlsx`;
        XLSX.writeFile(wb, fileName);

        this.triggerSummarySnackbar({
          title: this.$t('export_excel') || 'Excel Export',
          message: this.$t('export_excel_success') || 'Workbook generated and downloaded successfully',
          details: fileName,
          icon: 'mdi-file-excel',
          iconColor: '#2E7D32',
        });
      } catch (err) {
        console.error('Failed to export Excel:', err);
      }
    },

    printPage(view = null) {
      this.printModalTargetView = view || 'full_summary';
      this.printModalOpen = true;
    },
  },
};
</script>

<style scoped>
.wfs-dashboard {
  padding: 0 4px 32px;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  transition: background 0.3s ease, color 0.3s ease;
}

/* =========================================================
   HERO HEADER (Workforce Module compliant)
   ========================================================= */
.wfs-header {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: 20px;
  padding: 24px 28px;
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(21, 101, 192, 0.22);
}
.wfs-header-glow {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 220px;
  height: 220px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
  pointer-events: none;
}
.wfs-icon-wrap {
  width: 54px;
  height: 54px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}
.wfs-title {
  font-size: 20px;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: 0.3px;
  margin: 0;
  line-height: 1.2;
}
.wfs-subtitle {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.85);
  margin: 2px 0 0;
  font-weight: 500;
}
.wfs-period-badge {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(6px);
  padding: 6px 14px;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.28);
}

/* =========================================================
   CONTAINER CARDS (Dark/Light surface styling)
   ========================================================= */
.wfs-card {
  border-radius: 18px !important;
  background: #ffffff;
  border: 1px solid rgba(66, 165, 245, 0.2) !important;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.25s ease;
}
.wfs-dark .wfs-card {
  background: #1a1e24 !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25) !important;
}

/* =========================================================
   MONTH SELECTOR PILLS
   ========================================================= */
.wfs-month-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
  gap: 8px;
}
.wfs-month-pill {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  border-radius: 12px;
  cursor: pointer;
  background: #f1f5f9;
  color: #334155;
  font-size: 13px;
  font-weight: 600;
  border: 1.5px solid transparent;
  transition: all 0.2s ease;
  user-select: none;
}
.wfs-month-pill:hover:not(.wfs-month-pill--disabled) {
  background: #e2e8f0;
  transform: translateY(-1px);
}
.wfs-month-pill--active {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 100%) !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(21, 101, 192, 0.35) !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
}
.wfs-month-pill--disabled {
  opacity: 0.38;
  cursor: not-allowed;
  background: #f8fafc;
}
.wfs-dark .wfs-month-pill {
  background: #20252d;
  color: #cbd5e1;
}
.wfs-dark .wfs-month-pill:hover:not(.wfs-month-pill--disabled) {
  background: #282f3a;
}
.wfs-dark .wfs-month-pill--disabled {
  background: #14171c;
  opacity: 0.28;
}
.wfs-range-caption {
  border-top: 1px dashed rgba(66, 165, 245, 0.2);
}
.wfs-dark .wfs-range-caption {
  border-top-color: rgba(255, 255, 255, 0.08);
}

/* =========================================================
   YEAR SWITCHER PILL (Supplier Settlement Style - 2nd Image)
   ========================================================= */
.wfs-year-pill {
  display: inline-flex;
  align-items: center;
  background: linear-gradient(135deg, #0056e6 0%, #009bf5 50%, #26afff 100%);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 9999px;
  padding: 2px 6px;
  box-shadow: 0 2px 10px rgba(230, 81, 0, 0.32);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.wfs-year-pill:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(230, 81, 0, 0.45);
}
.wfs-year-btn {
  color: #ffffff !important;
  width: 24px !important;
  height: 24px !important;
  min-width: 24px !important;
  border-radius: 50% !important;
  transition: transform 0.15s ease, background-color 0.15s ease;
}
.wfs-year-btn:hover {
  background-color: rgba(255, 255, 255, 0.25) !important;
  transform: scale(1.15);
}
.wfs-year-text {
  font-size: 15px;
  font-weight: 800;
  color: #ffffff;
  padding: 0 10px;
  min-width: 50px;
  text-align: center;
  letter-spacing: 0.5px;
  cursor: pointer;
  user-select: none;
  transition: opacity 0.15s ease;
}
.wfs-year-text:hover {
  opacity: 0.92;
}

/* Dark mode adjustments */
.wfs-dark .wfs-year-pill {
  background: linear-gradient(135deg, #BF360C 0%, #E65100 50%, #F57C00 100%) !important;
  border-color: rgba(255, 255, 255, 0.18) !important;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.45) !important;
}

/* Chart Toggles */
.wfs-chart-toggle {
  border-radius: 12px !important;
  border: 1px solid rgba(66, 165, 245, 0.3) !important;
  background: #f0f7ff !important;
  padding: 2px !important;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03) !important;
}
.wfs-chart-toggle .v-btn {
  border-radius: 8px !important;
  border: none !important;
  text-transform: none !important;
  font-size: 11px !important;
}
.wfs-chart-toggle .v-btn--active {
  background: #1976D2 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 6px rgba(25, 118, 210, 0.35) !important;
}
.wfs-dark .wfs-chart-toggle {
  background: #12151a !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

/* =========================================================
   VIEW SWITCHER (Section & Corporal)
   ========================================================= */
.wfs-view-switcher {
  display: inline-flex !important;
  align-items: center !important;
  border-radius: 10px !important;
  border: 1px solid rgba(0, 0, 0, 0.08) !important;
  background: #f1f5f9 !important;
  padding: 3px !important;
  gap: 3px !important;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  height: 32px !important;
  flex-shrink: 0 !important;
}
.wfs-dark .wfs-view-switcher {
  background: #14171c !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}

.wfs-view-switcher .v-btn.wfs-view-btn {
  min-width: 34px !important;
  width: 34px !important;
  height: 26px !important;
  padding: 0 !important;
  border-radius: 7px !important;
  border: none !important;
  transition: all 0.2s ease !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}
.wfs-view-switcher .v-btn.wfs-view-btn:not(.v-btn--active) {
  background: transparent !important;
  color: #64748b !important;
}
.wfs-dark .wfs-view-switcher .v-btn.wfs-view-btn:not(.v-btn--active) {
  color: #94a3b8 !important;
}
.wfs-view-switcher .v-btn.wfs-view-btn:not(.v-btn--active):hover {
  background: rgba(0, 0, 0, 0.06) !important;
  color: #1e293b !important;
}
.wfs-dark .wfs-view-switcher .v-btn.wfs-view-btn:not(.v-btn--active):hover {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #f1f5f9 !important;
}

/* Orange variant for Section Switcher */
.wfs-view-switcher--orange .v-btn.wfs-view-btn.v-btn--active {
  background: #E65100 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 6px rgba(230, 81, 0, 0.35) !important;
}

/* Purple variant for Corporal Switcher */
.wfs-view-switcher--purple .v-btn.wfs-view-btn.v-btn--active {
  background: #7B1FA2 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 6px rgba(123, 31, 162, 0.35) !important;
}

/* =========================================================
   INPUT FIELD STYLING (matching AddCharge)
   ========================================================= */
.wfs-input-field :deep(.v-field) {
  border-radius: 20px !important;
  transition: all 0.2s ease;
}
.wfs-input-field :deep(.v-field__outline) {
  --v-field-border-opacity: 0.3;
}
.wfs-search-field :deep(.v-field) {
  border-radius: 20px !important;
}

/* =========================================================
   EXECUTIVE KPI STAT CARDS
   ========================================================= */
.wfs-kpi-card {
  position: relative;
  background: #ffffff;
  border-radius: 16px;
  padding: 18px 20px;
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.03);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.wfs-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}
.wfs-kpi-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}
.wfs-kpi--blue .wfs-kpi-accent { background: linear-gradient(90deg, #1565C0, #42A5F5); }
.wfs-kpi--teal .wfs-kpi-accent { background: linear-gradient(90deg, #00695C, #26A69A); }
.wfs-kpi--orange .wfs-kpi-accent { background: linear-gradient(90deg, #E65100, #FFA726); }
.wfs-kpi--purple .wfs-kpi-accent { background: linear-gradient(90deg, #6A1B9A, #AB47BC); }

.wfs-kpi-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.wfs-kpi-icon--blue { background: rgba(25, 118, 210, 0.12); }
.wfs-kpi-icon--teal { background: rgba(0, 137, 123, 0.12); }
.wfs-kpi-icon--orange { background: rgba(230, 81, 0, 0.12); }
.wfs-kpi-icon--purple { background: rgba(123, 31, 162, 0.12); }

.wfs-kpi-val {
  font-size: 22px;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.3px;
  margin-bottom: 4px;
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
.wfs-kpi-unit {
  font-size: 13px;
  font-weight: 600;
  opacity: 0.85;
}
.wfs-kpi-lbl {
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.wfs-kpi-sub {
  font-size: 12px;
  color: #64748b;
  display: flex;
  align-items: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.wfs-dark .wfs-kpi-card {
  background: #1a1e24;
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.wfs-dark .wfs-kpi-lbl { color: #94a3b8; }
.wfs-dark .wfs-kpi-sub { color: #94a3b8; }

/* =========================================================
   RANKING LISTS (Sections & Corporals)
   ========================================================= */
.wfs-rank-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 280px;
  overflow-y: auto;
  padding-right: 4px;
}
.wfs-rank-item {
  padding: 8px 10px;
  border-radius: 10px;
  background: #f8fafc;
  transition: background 0.15s ease;
}
.wfs-rank-item:hover {
  background: #f1f5f9;
}
.wfs-dark .wfs-rank-item {
  background: #20252d;
}
.wfs-dark .wfs-rank-item:hover {
  background: #282f3a;
}
.wfs-rank-badge {
  font-size: 10px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 6px;
  background: #e2e8f0;
  color: #475569;
}
.wfs-rank-1 { background: #fef3c7; color: #b45309; }
.wfs-rank-2 { background: #e2e8f0; color: #334155; }
.wfs-rank-3 { background: #ffedd5; color: #c2410c; }
.wfs-dark .wfs-rank-badge {
  background: #334155;
  color: #cbd5e1;
}

/* =========================================================
   TOP WORKFORCE BREAKDOWN TABLE
   ========================================================= */
.wfs-table-wrap {
  width: 100%;
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.06);
}
.wfs-dark .wfs-table-wrap {
  border-color: rgba(255, 255, 255, 0.08);
}
.wfs-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.wfs-table th {
  background: #f1f5f9;
  color: #475569;
  font-weight: 700;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}
.wfs-dark .wfs-table th {
  background: #14171c;
  color: #94a3b8;
  border-bottom-color: rgba(255, 255, 255, 0.08);
}
.wfs-table td {
  padding: 10px 14px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}
.wfs-dark .wfs-table td {
  border-bottom-color: rgba(255, 255, 255, 0.05);
}
.wfs-table-row:hover {
  background: rgba(66, 165, 245, 0.05);
}
.wfs-dark .wfs-table-row:hover {
  background: rgba(66, 165, 245, 0.1);
}
.wfs-medal-badge {
  font-size: 14px;
  font-weight: 700;
}

/* =========================================================
   PRINT STYLES
   ========================================================= */
@media print {
  .wfs-header,
  .wfs-month-grid,
  .wfs-year-pill,
  .wfs-chart-toggle,
  .segmented-pill-container,
  .v-btn,
  .wfs-search-field,
  .v-snackbar {
    display: none !important;
  }
  .wfs-card {
    border: 1px solid #ccc !important;
    box-shadow: none !important;
    page-break-inside: avoid;
  }
}
</style>
