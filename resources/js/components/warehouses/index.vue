<template>
<v-card class="wh-card" elevation="0">
  <!-- Hero Header per claude.md specifications -->
  <div class="wh-hero">
    <div class="wh-hero-inner">
      <div class="d-flex align-center ga-4 flex-wrap">
        <div class="wh-hero-icon">
          <v-icon size="30" color="white">mdi-warehouse</v-icon>
        </div>
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <span class="wh-hero-title">{{ $t('warehouse_dashboard') }}</span>
            <v-chip size="x-small" color="white" variant="outlined" class="font-weight-bold" style="border-color: rgba(255,255,255,.45)">
              <span class="wh-pulse-dot mr-1"></span>
              {{ $t('live_stock_sync') }}
            </v-chip>
          </div>
          <div class="text-white text-caption mt-1" style="opacity:.85">
            {{ $t('warehouse_dashboard_desc') }}
          </div>
        </div>
      </div>

      <div class="wh-hero-actions d-flex align-center ga-2 flex-wrap">
        <v-btn
          variant="tonal"
          color="white"
          rounded
          size="small"
          prepend-icon="mdi-refresh"
          @click="fetchDashboardData"
          :loading="fetchLoading"
          class="font-weight-bold"
        >
          {{ $t('refresh') }}
        </v-btn>
      </div>
    </div>
  </div>

  <v-card-text class="pa-4 pa-md-5">
    <!-- WAREHOUSE CONTROLS BAR: Multiselect styled like AddCharge + Quick Chips -->
    <v-card class="wh-selector-card pa-3 mb-4 rounded-xl border" elevation="0">
      <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-2">
        <div class="d-flex align-center ga-2">
          <v-icon size="18" color="#a21ee5">mdi-store-search-outline</v-icon>
          <span class="text-subtitle-2 font-weight-bold text-warehouse">{{ $t('warehouses') }}</span>
          <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
            {{ selectedWarehouseIds.length }} / {{ allWarehouses.length }}
          </v-chip>
        </div>
        <div class="d-flex align-center ga-1">
          <v-btn
            size="x-small"
            variant="tonal"
            color="#a21ee5"
            rounded
            class="font-weight-bold"
            @click="selectAllWarehouses"
          >
            {{ $t('select_all') }}
          </v-btn>
          <v-btn
            size="x-small"
            variant="text"
            color="grey"
            rounded
            class="font-weight-bold"
            @click="clearAllWarehouses"
          >
            {{ $t('clear_all') }}
          </v-btn>
        </div>
      </div>

      <!-- Input field matching AddCharge design: compact, outlined, rounded, color="#a21ee5" -->
      <v-autocomplete
        v-model="selectedWarehouseIds"
        :items="allWarehouses"
        density="compact"
        variant="outlined"
        rounded="lg"
        color="#a21ee5"
        hide-details
        multiple
        chips
        closable-chips
        :placeholder="$t('select_warehouses')"
        prepend-inner-icon="mdi-warehouse"
        class="wh-input text-warehouse mb-2"
        @update:model-value="onWarehouseChange"
      >
        <template #chip="{ item, props: p }">
          <v-chip
            v-bind="p"
            size="small"
            color="#a21ee5"
            variant="tonal"
            class="font-weight-medium"
            closable
          >
            {{ item.title }}
          </v-chip>
        </template>
      </v-autocomplete>

      <!-- Quick warehouse suggestion chips showing active stock in Kg -->
      <div v-if="warehousesWithStockList.length" class="d-flex align-center ga-1 flex-wrap pt-1">
        <span class="text-caption text-medium-emphasis mr-1 font-weight-bold">
          {{ $t('warehouses_with_stock_quick') }}:
        </span>
        <v-chip
          v-for="w in warehousesWithStockList.slice(0, 10)"
          :key="w.id"
          size="x-small"
          rounded="lg"
          :color="selectedWarehouseIds.includes(w.id) ? '#a21ee5' : 'grey'"
          :variant="selectedWarehouseIds.includes(w.id) ? 'elevated' : 'outlined'"
          class="font-weight-bold cursor-pointer"
          @click="selectWarehouseDirect(w.id)"
        >
          <v-icon size="12" class="mr-1">mdi-cube-outline</v-icon>
          {{ w.name }}
          <span class="ml-1 opacity-75">({{ main.formatDisplayNumber(w.total_stock || w.total_weight) }} Kg)</span>
        </v-chip>
      </div>
    </v-card>

    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-4">
      <!-- Calendar (left) -->
      <v-col cols="12" md="3">
        <div class="wh-panel-card" style="height:100%">
          <div class="wh-panel-header d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center">
              <v-icon size="16" color="#a21ee5" class="mr-1">mdi-calendar-range</v-icon>
              <span>{{ $t('date_range') }}</span>
            </div>
            <div class="d-flex align-center ga-1">
              <v-btn size="x-small" variant="text" color="#a21ee5" class="px-1" @click="setDatePreset('7d')">7j</v-btn>
              <v-btn size="x-small" variant="text" color="#a21ee5" class="px-1" @click="setDatePreset('30d')">30j</v-btn>
              <v-btn size="x-small" variant="text" color="#a21ee5" class="px-1" @click="setDatePreset('all')">{{ $t('all') || 'Tout' }}</v-btn>
            </div>
          </div>
          <DatePicker
            v-model.range="dateRangeVC"
            :is-dark="main.is_dark"
            :locale="main.lang?.short || 'en'"
            mode="date"
            color="purple"
            :max-date="new Date()"
            expanded
            borderless
            class="w-100"
            @update:model-value="onDateRangeVCChange"
          />
        </div>
      </v-col>

      <!-- Chart (center) -->
      <v-col cols="12" md="6">
        <div class="wh-panel-card" style="height:100%">
          <div class="wh-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon size="16" color="#a21ee5" class="mr-1">mdi-chart-areaspline</v-icon>
              <span>{{ $t('analytics') }}</span>
            </div>
            <v-btn-toggle
              v-model="dashChartMode"
              mandatory
              density="compact"
              color="#a21ee5"
              variant="outlined"
              rounded="lg"
              divided
            >
              <v-btn value="daily" size="x-small" class="font-weight-bold">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small" class="font-weight-bold">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small" class="font-weight-bold">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart
            v-if="dashChartSeries.length && dashChartSeries[0].data.length"
            type="area"
            :height="290"
            :options="dashChartOptions"
            :series="dashChartSeries"
          ></apexchart>
          <div v-else class="d-flex flex-column align-center justify-center" style="height:220px;opacity:.4">
            <v-icon size="48" color="grey">mdi-chart-line-variant</v-icon>
            <span class="text-caption mt-2">{{ $t('no_chart_data') }}</span>
          </div>
        </div>
      </v-col>

      <!-- Statistics (right) -->
      <v-col cols="12" md="3">
        <div class="wh-panel-card wh-stats-panel" style="height:100%;overflow-y:auto">
          <div class="wh-panel-header">
            <v-icon size="16" color="#a21ee5" class="mr-1">mdi-chart-box-outline</v-icon>
            <span>{{ $t('statistics') }}</span>
          </div>
          <div v-for="(stat, si) in warehouseStats" :key="si" class="wh-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
                <v-icon size="12" class="mr-1">{{ tIcon(stat.type) }}</v-icon>
                {{ stat.name }} ({{ stat.count }})
              </v-chip>
              <v-chip size="x-small" color="#a21ee5" variant="elevated" class="font-weight-bold">
                {{ stat.type === 'PackageType' ? (totalPackages > 0 ? Math.round(stat.package_count / totalPackages * 1000) / 10 : 0) : (totalWeight > 0 ? Math.round(stat.weight / totalWeight * 1000) / 10 : 0) }}%
              </v-chip>
            </div>
            <v-progress-linear
              :model-value="stat.type === 'PackageType' ? (totalPackages > 0 ? (stat.package_count / totalPackages * 100) : 0) : (totalWeight > 0 ? (stat.weight / totalWeight * 100) : 0)"
              color="#a21ee5"
              bg-color="rgba(162, 30, 229, 0.15)"
              height="5"
              rounded
              class="mb-1"
            ></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div v-if="stat.type === 'PackageType'" class="d-flex align-center ga-1">
                <v-icon size="12" color="#6D4C41">mdi-package-variant-closed</v-icon>
                <span class="text-medium-emphasis">{{ $t('caisses') }}:</span>
                <b style="color:#6D4C41">{{ main.formatDisplayNumber(stat.package_count) }} {{ $t('boxes') }}</b>
              </div>
              <div v-else class="d-flex align-center ga-1">
                <v-icon size="12" color="#a21ee5">mdi-weight-kilogram</v-icon>
                <span class="text-medium-emphasis">{{ $t('weight') }}:</span>
                <b style="color:#a21ee5">{{ main.formatDisplayNumber(stat.weight) }} Kg</b>
              </div>
              <div class="d-flex align-center ga-1">
                <v-icon size="12" color="#FF9800">mdi-cash</v-icon>
                <span class="text-medium-emphasis">{{ $t('value') }}:</span>
                <b style="color:#FF9800">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b>
              </div>
            </div>
          </div>
          <v-divider class="my-2" color="#a21ee5"></v-divider>
          <!-- Totals -->
          <div class="d-flex flex-column ga-1">
            <div v-if="!isProductionEffectWarehouse" class="d-flex align-center ga-1">
              <v-icon size="14" color="#00897B">mdi-sigma</v-icon>
              <span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span>
              <b class="text-caption ml-auto" style="color:#00897B">{{ main.formatDisplayNumber(totalWeight) }} Kg</b>
            </div>
            <div v-if="totalPackages > 0" class="d-flex align-center ga-1">
              <v-icon size="14" color="#6D4C41">mdi-package-variant-closed</v-icon>
              <span class="text-caption font-weight-bold">{{ $t('caisses') }}:</span>
              <b class="text-caption ml-auto" style="color:#6D4C41">{{ main.formatDisplayNumber(totalPackages) }} {{ $t('boxes') }}</b>
            </div>
            <div class="d-flex align-center ga-1">
              <v-icon size="14" color="#FF9800">mdi-cash</v-icon>
              <span class="text-caption font-weight-bold">{{ $t('total_stock_value') }}:</span>
              <b class="text-caption ml-auto" style="color:#FF9800">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b>
            </div>
            <div class="d-flex align-center ga-1">
              <v-icon size="14" color="#a21ee5">mdi-format-list-bulleted-type</v-icon>
              <span class="text-caption font-weight-bold">{{ $t('total_articles') }}:</span>
              <b class="text-caption ml-auto" style="color:#a21ee5">{{ totalActiveBalancesCount }}</b>
            </div>
            <div v-if="totalEcart !== 0" class="d-flex align-center ga-1 mt-1 pa-2 rounded-lg" style="background: rgba(239, 83, 80, 0.08); border: 1px dashed rgba(239, 83, 80, 0.3)">
              <v-icon size="14" color="error">mdi-alert-circle-outline</v-icon>
              <span class="text-caption font-weight-bold" style="color:#D32F2F">{{ $t('ecart') }}:</span>
              <b class="text-caption font-weight-bold ml-auto" :style="{ color: totalEcart > 0 ? '#FB8C00' : '#D32F2F' }">
                {{ totalEcart > 0 ? '+' : '' }}{{ main.formatDisplayNumber(totalEcart) }} {{ $t('boxes') }}
              </b>
            </div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- EMPTY STATE -->
    <div v-if="!selectedWarehouseIds.length" class="wh-empty-state">
      <v-icon size="80" color="#a21ee555">mdi-warehouse</v-icon>
      <div class="text-h6 mt-3 font-weight-bold" style="opacity:.6">{{ $t('no_warehouses_selected') }}</div>
      <div class="text-caption text-medium-emphasis mt-1">{{ $t('select_detail_category_hint') || 'Veuillez sélectionner au moins un entrepôt pour afficher les données' }}</div>
    </div>

    <!-- LOADING STATE -->
    <div v-else-if="fetchLoading" class="d-flex flex-column align-center justify-center pa-12">
      <v-progress-circular indeterminate color="#a21ee5" size="52" width="4"></v-progress-circular>
      <span class="text-caption text-medium-emphasis mt-3 font-weight-bold">{{ $t('loading') }}...</span>
    </div>

    <!-- DATA AREA: Practical, Nice, and Displaying All Articles in Sub-Element / Phase -->
    <!-- DATA AREA -->
    <div v-else class="wh-data-area">
      <!-- TOP DATA AREA TOOLBAR: Segmented Mode Switcher + Global Search + Action Buttons -->
      <div class="wh-data-header d-flex align-center justify-space-between flex-wrap ga-3 mb-4 p-3 pa-3 rounded-xl border">
        <!-- View Mode Switcher using SegmentedPillToggle -->
        <div class="d-flex align-center ga-2 flex-wrap">
          <SegmentedPillToggle
            v-model="activeViewMode"
            :items="viewModeOptions"
            color="#a21ee5"
            size="md"
          />
        </div>

        <!-- Search & Filter Controls -->
        <div class="d-flex align-center ga-2 flex-wrap flex-grow-1 justify-end">
          <!-- Global Search field matching AddCharge style -->
          <v-text-field
            v-model="searchQuery"
            density="compact"
            variant="outlined"
            rounded="lg"
            color="#a21ee5"
            hide-details
            clearable
            :placeholder="$t('search_articles')"
            prepend-inner-icon="mdi-magnify"
            class="wh-search-input text-warehouse"
            style="min-width: 220px; max-width: 320px"
          ></v-text-field>

          <!-- Phase Filter -->
          <v-select
            v-model="filterPhase"
            :items="[{ title: $t('all') || 'Toutes les Phases', value: null }, ...availablePhases]"
            density="compact"
            variant="outlined"
            rounded="lg"
            color="#a21ee5"
            hide-details
            clearable
            :label="$t('factory_phase')"
            class="wh-select-compact text-warehouse"
            style="min-width: 170px;"
          ></v-select>

          <!-- Status Toggle (In stock vs all) -->
          <v-btn-toggle
            v-model="filterStatus"
            mandatory
            density="compact"
            color="#a21ee5"
            variant="outlined"
            rounded="lg"
          >
            <v-btn value="in_stock" size="x-small" class="font-weight-bold">{{ $t('in_stock') }}</v-btn>
            <v-btn value="all" size="x-small" class="font-weight-bold">{{ $t('all') || 'Tout' }}</v-btn>
          </v-btn-toggle>

          <!-- Export & Print Actions -->
          <v-btn
            variant="tonal"
            color="#a21ee5"
            rounded="lg"
            size="small"
            prepend-icon="mdi-file-excel"
            class="font-weight-bold"
            @click="exportToExcel"
            :title="$t('export_excel')"
          >
            Excel
          </v-btn>

          <v-btn
            icon
            variant="tonal"
            color="#a21ee5"
            rounded="lg"
            size="small"
            @click="printTable"
            :title="$t('print_table')"
          >
            <v-icon size="18">mdi-printer</v-icon>
          </v-btn>
        </div>
      </div>

      <!-- OVERALL KPI CARDS -->
      <v-row dense class="mb-4">
        <v-col cols="6" sm="3">
          <div class="wh-stat-card border" style="border-color: rgba(162, 30, 229, 0.25)">
            <div class="wh-stat-icon" style="background: rgba(162, 30, 229, 0.12)">
              <v-icon color="#a21ee5" size="22">mdi-package-variant-closed</v-icon>
            </div>
            <div>
              <div class="wh-stat-value" style="color:#a21ee5">{{ totalActiveBalancesCount }}</div>
              <div class="wh-stat-label">{{ $t('batches_count') }}</div>
            </div>
          </div>
        </v-col>

        <v-col cols="6" sm="3" v-if="!isProductionEffectWarehouse">
          <div class="wh-stat-card border" style="border-color: rgba(0, 137, 123, 0.25)">
            <div class="wh-stat-icon" style="background: rgba(0, 137, 123, 0.12)">
              <v-icon color="#00897B" size="22">mdi-weight-kilogram</v-icon>
            </div>
            <div>
              <div class="wh-stat-value" style="color:#00897B">{{ main.formatDisplayNumber(totalWeight) }} Kg</div>
              <div class="wh-stat-label">{{ $t('total_weight') }} ({{ main.formatDisplayNumber(totalInitialWeight) }} Kg {{ $t('initial') || 'init.' }})</div>
            </div>
          </div>
        </v-col>

        <v-col cols="6" sm="3" v-if="totalPackages > 0">
          <div class="wh-stat-card border" style="border-color: rgba(109, 76, 65, 0.25)">
            <div class="wh-stat-icon" style="background: rgba(109, 76, 65, 0.12)">
              <v-icon color="#6D4C41" size="22">mdi-package-variant</v-icon>
            </div>
            <div>
              <div class="wh-stat-value" style="color:#6D4C41">{{ main.formatDisplayNumber(totalPackages) }} {{ $t('boxes') }}</div>
              <div class="wh-stat-label">{{ $t('caisses') }} ({{ main.formatDisplayNumber(totalInitialPackages) }} init.)</div>
            </div>
          </div>
        </v-col>

        <v-col cols="6" sm="3">
          <div class="wh-stat-card border" style="border-color: rgba(255, 152, 0, 0.25)">
            <div class="wh-stat-icon" style="background: rgba(255, 152, 0, 0.12)">
              <v-icon color="#FF9800" size="22">mdi-cash-multiple</v-icon>
            </div>
            <div>
              <div class="wh-stat-value" style="color:#FF9800">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</div>
              <div class="wh-stat-label">{{ $t('total_stock_value') }}</div>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- ========================================================================= -->
      <!-- VIEW 1: SUB-ELEMENT VIEW (Display all articles inside each sub-element) -->
      <!-- ========================================================================= -->
      <div v-if="activeViewMode === 'sub_element'" class="wh-subelement-view">
        <div class="d-flex align-center justify-space-between mb-3">
          <span class="text-caption font-weight-bold text-medium-emphasis">
            {{ subElementsGrouped.length }} {{ $t('sub_elements') }} &bull; {{ filteredBalancesList.length }} {{ $t('articles') }}
          </span>
          <div class="d-flex align-center ga-1">
            <v-btn size="x-small" variant="text" color="#a21ee5" class="font-weight-bold" @click="expandAllSubElements">
              <v-icon size="14" class="mr-1">mdi-arrow-expand-vertical</v-icon>
              {{ $t('expand_all') }}
            </v-btn>
            <v-btn size="x-small" variant="text" color="grey" class="font-weight-bold" @click="collapseAllSubElements">
              <v-icon size="14" class="mr-1">mdi-arrow-collapse-vertical</v-icon>
              {{ $t('collapse_all') }}
            </v-btn>
          </div>
        </div>

        <div v-if="!subElementsGrouped.length" class="text-center pa-8 text-medium-emphasis border rounded-xl">
          <v-icon size="40" color="grey" class="mb-2">mdi-folder-open-outline</v-icon>
          <div>{{ $t('no_data_available') }}</div>
        </div>

        <!-- Sub-element Cards containing all individual articles -->
        <div v-for="sub in subElementsGrouped" :key="sub.key" class="wh-sub-card mb-4 rounded-xl border overflow-hidden">
          <!-- Sub-Element Header Banner -->
          <div
            class="wh-sub-header pa-3 px-4 d-flex align-center justify-space-between flex-wrap ga-2 cursor-pointer"
            :style="{ background: sub.typeColor + '12', borderBottom: '1.5px solid ' + sub.typeColor + '30' }"
            @click="toggleSubElement(sub.key)"
          >
            <div class="d-flex align-center ga-2 flex-wrap">
              <v-icon size="20" :color="sub.typeColor">
                {{ expandedSubElements[sub.key] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
              </v-icon>
              <div class="wh-sub-badge-icon" :style="{ background: sub.typeColor + '25' }">
                <v-icon size="18" :color="sub.typeColor">{{ sub.typeIcon }}</v-icon>
              </div>
              <div>
                <span class="text-subtitle-1 font-weight-bold" :style="{ color: sub.typeColor }">
                  {{ sub.name }}
                </span>
                <span class="text-caption text-medium-emphasis ml-2">
                  ({{ typeName(sub.type) }} &bull; {{ sub.speciesCount }} {{ $t('species_count') }})
                </span>
              </div>
            </div>

            <!-- Sub-element KPI badges -->
            <div class="d-flex align-center ga-2 flex-wrap">
              <v-chip size="small" :color="sub.typeColor" variant="tonal" class="font-weight-bold">
                <v-icon size="14" class="mr-1">mdi-tag-outline</v-icon>
                {{ sub.articles.length }} {{ $t('batches_count') }}
              </v-chip>

              <v-chip v-if="sub.type !== 'PackageType'" size="small" color="#00897B" variant="tonal" class="font-weight-bold">
                <v-icon size="14" class="mr-1">mdi-weight-kilogram</v-icon>
                {{ main.formatDisplayNumber(sub.totalWeight) }} Kg
              </v-chip>

              <v-chip v-if="sub.totalBoxes > 0" size="small" color="#6D4C41" variant="tonal" class="font-weight-bold">
                <v-icon size="14" class="mr-1">mdi-package-variant-closed</v-icon>
                {{ main.formatDisplayNumber(sub.totalBoxes) }} {{ $t('boxes') }}
              </v-chip>

              <v-chip v-if="sub.totalPrice > 0" size="small" color="#FF9800" variant="tonal" class="font-weight-bold">
                <v-icon size="14" class="mr-1">mdi-cash</v-icon>
                {{ main.formatDisplayNumber(sub.totalPrice) }} {{ main.currency }}
              </v-chip>

              <v-btn
                icon
                variant="text"
                size="small"
                :color="sub.typeColor"
                @click.stop="openSubElementPie(sub)"
                :title="$t('stock_distribution')"
              >
                <v-icon size="18">mdi-chart-pie</v-icon>
              </v-btn>
            </div>
          </div>

          <!-- Inside the Sub-Element: Display All Articles that Hold that Sub-Element & Phase -->
          <v-expand-transition>
            <div v-show="expandedSubElements[sub.key]" class="pa-3 pa-md-4">
              <div class="wh-articles-table-wrap">
                <table class="wh-modern-table">
                  <thead>
                    <tr>
                      <th style="width: 32px"></th>
                      <th class="text-left">{{ $t('article_type_name') }}</th>
                      <th class="text-center">{{ $t('factory_phase') }}</th>
                      <th class="text-center">{{ $t('reception_tracking_number_col') }}</th>
                      <th class="text-center">{{ $t('date') }}</th>
                      <th class="text-center">{{ $t('suppliers') }}</th>
                      <th class="text-right" v-if="sub.type !== 'PackageType'">{{ $t('remaining') }} (Kg)</th>
                      <th class="text-center">{{ $t('package_count') }}</th>
                      <th class="text-right" v-if="sub.type !== 'PackageType'">{{ $t('wap') }} ({{ main.currency }}/Kg)</th>
                      <th class="text-right">{{ $t('total_price') }} ({{ main.currency }})</th>
                      <th class="text-center">{{ $t('actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="bal in sub.articles" :key="bal.id">
                      <tr
                        class="wh-article-row"
                        :class="{ 'is-finished': bal._isDepleted }"
                        @click="toggleExpand(bal.id)"
                      >
                        <!-- Expand Chevron for Outgoing Movements -->
                        <td>
                          <v-icon size="16" :color="expandedBalances[bal.id] ? '#a21ee5' : 'grey'">
                            {{ expandedBalances[bal.id] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
                          </v-icon>
                        </td>

                        <!-- Article Name + Process -->
                        <td class="text-left">
                          <div class="d-flex align-center ga-2">
                            <span class="font-weight-bold text-subtitle-2">{{ bal._articleName }}</span>
                            <v-chip v-if="bal._processName" size="x-small" color="#a21ee5" variant="outlined" class="font-weight-medium">
                              {{ bal._processName }}
                            </v-chip>
                            <v-chip v-if="bal.stockable?.type" size="x-small" :color="bal.stockable.type === 'frozen' ? 'cyan-darken-2' : 'teal'" variant="tonal">
                              {{ bal.stockable.type }}
                            </v-chip>
                          </div>
                          <div class="text-caption text-medium-emphasis">
                            {{ bal._warehouseName }}
                          </div>
                        </td>

                        <!-- Factory Phase Badge with Direct Source Link -->
                        <td class="text-center">
                          <v-chip
                            size="x-small"
                            :color="phaseMeta(bal._cleanPhase).color"
                            variant="tonal"
                            class="font-weight-bold cursor-pointer"
                            @click.stop="goToSource(bal)"
                            :title="$t('view_source')"
                          >
                            <v-icon size="12" class="mr-1">{{ phaseMeta(bal._cleanPhase).icon }}</v-icon>
                            {{ phaseMeta(bal._cleanPhase).shortTitle }}
                          </v-chip>
                        </td>

                        <!-- Tracking / Lot Number -->
                        <td class="text-center">
                          <v-chip size="x-small" color="purple-darken-1" variant="tonal" class="font-weight-bold">
                            {{ bal.reception_tracking_number || '-' }}
                          </v-chip>
                        </td>

                        <!-- Entry Date -->
                        <td class="text-center text-caption font-weight-medium">
                          {{ main.formateDate(bal.date) }}
                        </td>

                        <!-- Supplier / Origin -->
                        <td class="text-center text-caption">
                          <div class="font-weight-medium">{{ bal.supplier_name || '-' }}</div>
                          <div v-if="bal.origin_name || bal.fishery_name" class="text-medium-emphasis" style="font-size: 10px">
                            {{ [bal.origin_name, bal.fishery_name].filter(Boolean).join(' • ') }}
                          </div>
                        </td>

                        <!-- Remaining vs Initial Weight (Kg) -->
                        <td class="text-right font-weight-bold text-teal" v-if="sub.type !== 'PackageType'">
                          <div>{{ main.formatDisplayNumber(bal._remainingWeight) }} Kg</div>
                          <div class="text-caption text-medium-emphasis font-weight-regular" style="font-size: 10px">
                            / {{ main.formatDisplayNumber(bal._initialWeight) }} Kg
                          </div>
                          <v-progress-linear
                            :model-value="bal._pctRemaining"
                            color="#00897B"
                            height="4"
                            rounded
                            class="mt-1"
                          ></v-progress-linear>
                        </td>

                        <!-- Packaging / Boxes -->
                        <td class="text-center">
                          <v-chip size="x-small" color="#6D4C41" variant="tonal" class="font-weight-bold">
                            {{ bal._remainingBoxes }} {{ $t('boxes') }}
                          </v-chip>
                          <div v-if="bal.package_type" class="text-caption text-medium-emphasis" style="font-size: 10px">
                            {{ bal.package_type.name || bal.package_type.name_fr }}
                          </div>
                        </td>

                        <!-- WAP (Weighted Average Price) -->
                        <td class="text-right" v-if="sub.type !== 'PackageType'">
                          <b v-if="bal._wap > 0" style="color:#a21ee5">{{ main.formatDisplayNumber(bal._wap) }}</b>
                          <span v-else class="text-grey">-</span>
                        </td>

                        <!-- Effective Total Value -->
                        <td class="text-right font-weight-bold" style="color:#FF9800">
                          <span v-if="bal._effectivePrice > 0">{{ main.formatDisplayNumber(bal._effectivePrice) }}</span>
                          <span v-else class="text-grey">-</span>
                        </td>

                        <!-- Action Buttons -->
                        <td class="text-center text-no-wrap">
                          <v-btn
                            v-if="bal.source_type"
                            icon
                            variant="text"
                            size="x-small"
                            color="#1565C0"
                            @click.stop="goToSource(bal)"
                            :title="$t('view_source')"
                          >
                            <v-icon size="14">mdi-open-in-new</v-icon>
                          </v-btn>
                          <v-btn
                            icon
                            variant="text"
                            size="x-small"
                            color="#a21ee5"
                            @click.stop="openArticlePie(bal)"
                            :title="$t('stock_distribution')"
                          >
                            <v-icon size="14">mdi-chart-pie</v-icon>
                          </v-btn>
                          <v-btn
                            v-if="bal._cleanType === 'FishType'"
                            icon
                            variant="text"
                            size="x-small"
                            color="#00897B"
                            @click.stop="openLineChart({ type: 'article', stockableId: bal.stockable_id, articleName: bal._articleName })"
                            :title="$t('price_history')"
                          >
                            <v-icon size="14">mdi-chart-line</v-icon>
                          </v-btn>
                        </td>
                      </tr>

                      <!-- Expandable Stock-Out Operations Panel -->
                      <tr v-if="expandedBalances[bal.id]">
                        <td :colspan="sub.type !== 'PackageType' ? 11 : 9" class="pa-0">
                          <div class="wh-ops-drawer pa-3">
                            <div class="d-flex align-center justify-space-between mb-2">
                              <div class="d-flex align-center ga-1 font-weight-bold text-caption text-error">
                                <v-icon size="14" color="error">mdi-arrow-up-circle</v-icon>
                                <span>{{ $t('stock_operations_history') }} ({{ (bal.operations || []).length }})</span>
                              </div>
                              <span class="text-caption text-medium-emphasis">
                                {{ $t('stock_out') }}: {{ main.formatDisplayNumber(bal._outWeight) }} Kg &bull; {{ bal._outBoxes }} {{ $t('boxes') }}
                              </span>
                            </div>

                            <table v-if="(bal.operations || []).length" class="wh-ops-inner-table">
                              <thead>
                                <tr>
                                  <th>{{ $t('date') }}</th>
                                  <th>{{ $t('factory_phase') }} / {{ $t('source') }}</th>
                                  <th v-if="sub.type !== 'PackageType'">{{ $t('weight') }} (Kg)</th>
                                  <th>{{ $t('package_count') }}</th>
                                  <th>{{ $t('client') }}</th>
                                  <th>{{ $t('actions') }}</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="op in bal.operations" :key="op.id">
                                  <td>{{ main.formateDate(op.date) }}</td>
                                  <td>
                                    <v-chip size="x-small" :color="srcColor(op.source_type)" variant="tonal" class="font-weight-bold">
                                      {{ srcLabel(op.source_type) }} #{{ op.source_id || '-' }}
                                    </v-chip>
                                  </td>
                                  <td v-if="sub.type !== 'PackageType'" class="font-weight-bold text-error">
                                    -{{ main.formatDisplayNumber(op.weight) }} Kg
                                  </td>
                                  <td class="font-weight-bold text-error">
                                    -{{ op.package_count || 0 }} {{ $t('boxes') }}
                                  </td>
                                  <td>{{ op.client_name || '-' }}</td>
                                  <td>
                                    <v-btn
                                      v-if="op.source_type"
                                      icon
                                      variant="text"
                                      size="x-small"
                                      color="#1565C0"
                                      @click.stop="goToSourceOp(op)"
                                      :title="$t('view_source')"
                                    >
                                      <v-icon size="14">mdi-open-in-new</v-icon>
                                    </v-btn>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            <div v-else class="text-caption text-medium-emphasis text-center pa-2">
                              {{ $t('no_outgoing_operations') || 'Aucune sortie enregistrée pour ce lot (Stock intact)' }}
                            </div>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </div>
          </v-expand-transition>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- VIEW 2: FACTORY PHASE VIEW (Grouped by Factory Phase containing all articles) -->
      <!-- ========================================================================= -->
      <div v-else-if="activeViewMode === 'phase'" class="wh-phase-view">
        <div class="mb-3">
          <span class="text-caption font-weight-bold text-medium-emphasis">
            {{ $t('factory_phases') }} &bull; {{ filteredBalancesList.length }} {{ $t('articles') }}
          </span>
        </div>

        <div v-for="ph in phasesGrouped" :key="ph.key" class="wh-phase-card mb-4 rounded-xl border overflow-hidden">
          <!-- Phase Header Banner -->
          <div
            class="wh-phase-header pa-3 px-4 d-flex align-center justify-space-between flex-wrap ga-2 cursor-pointer"
            :style="{ background: ph.meta.bg, borderBottom: '1.5px solid ' + ph.meta.color + '30' }"
            @click="togglePhase(ph.key)"
          >
            <div class="d-flex align-center ga-2 flex-wrap">
              <v-icon size="20" :color="ph.meta.color">
                {{ expandedPhases[ph.key] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
              </v-icon>
              <div class="wh-sub-badge-icon" :style="{ background: ph.meta.color + '25' }">
                <v-icon size="20" :color="ph.meta.color">{{ ph.meta.icon }}</v-icon>
              </div>
              <div>
                <span class="text-subtitle-1 font-weight-bold" :style="{ color: ph.meta.color }">
                  {{ ph.meta.title }}
                </span>
                <span class="text-caption text-medium-emphasis ml-2">
                  ({{ ph.meta.desc }})
                </span>
              </div>
            </div>

            <!-- Phase KPI badges -->
            <div class="d-flex align-center ga-2 flex-wrap">
              <v-chip size="small" :color="ph.meta.color" variant="tonal" class="font-weight-bold">
                {{ ph.count }} {{ $t('batches_count') }}
              </v-chip>
              <v-chip v-if="ph.totalWeight > 0" size="small" color="#00897B" variant="tonal" class="font-weight-bold">
                {{ main.formatDisplayNumber(ph.totalWeight) }} Kg
              </v-chip>
              <v-chip v-if="ph.totalBoxes > 0" size="small" color="#6D4C41" variant="tonal" class="font-weight-bold">
                {{ main.formatDisplayNumber(ph.totalBoxes) }} {{ $t('boxes') }}
              </v-chip>
              <v-chip v-if="ph.totalPrice > 0" size="small" color="#FF9800" variant="tonal" class="font-weight-bold">
                {{ main.formatDisplayNumber(ph.totalPrice) }} {{ main.currency }}
              </v-chip>
              <v-btn
                icon
                variant="text"
                size="small"
                :color="ph.meta.color"
                @click.stop="openPhasePie(ph)"
                :title="$t('stock_distribution')"
              >
                <v-icon size="18">mdi-chart-pie</v-icon>
              </v-btn>
            </div>
          </div>

          <!-- Inside the Phase: Display All Articles Created in that Factory Phase -->
          <v-expand-transition>
            <div v-show="expandedPhases[ph.key]" class="pa-3 pa-md-4">
              <div v-if="!ph.articles.length" class="text-caption text-medium-emphasis text-center pa-4">
                {{ $t('no_data_available') }}
              </div>
              <div v-else class="wh-articles-table-wrap">
                <table class="wh-modern-table">
                  <thead>
                    <tr>
                      <th style="width: 32px"></th>
                      <th class="text-left">{{ $t('article_type_name') }}</th>
                      <th class="text-center">{{ $t('reception_tracking_number_col') }}</th>
                      <th class="text-center">{{ $t('date') }}</th>
                      <th class="text-center">{{ $t('suppliers') }}</th>
                      <th class="text-right">{{ $t('remaining') }} (Kg)</th>
                      <th class="text-center">{{ $t('package_count') }}</th>
                      <th class="text-right">{{ $t('wap') }}</th>
                      <th class="text-right">{{ $t('total_price') }}</th>
                      <th class="text-center">{{ $t('actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="bal in ph.articles" :key="bal.id">
                      <tr class="wh-article-row" :class="{ 'is-finished': bal._isDepleted }" @click="toggleExpand(bal.id)">
                        <td>
                          <v-icon size="16" :color="expandedBalances[bal.id] ? ph.meta.color : 'grey'">
                            {{ expandedBalances[bal.id] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
                          </v-icon>
                        </td>
                        <td class="text-left">
                          <div class="d-flex align-center ga-1">
                            <v-icon size="16" :color="tColor(bal._cleanType)">{{ tIcon(bal._cleanType) }}</v-icon>
                            <span class="font-weight-bold">{{ bal._articleName }}</span>
                            <v-chip size="x-small" color="#a21ee5" variant="tonal" class="ml-1 font-weight-medium">
                              {{ bal._familyName }}
                            </v-chip>
                          </div>
                          <div class="text-caption text-medium-emphasis">{{ bal._warehouseName }}</div>
                        </td>
                        <td class="text-center">
                          <v-chip size="x-small" :color="ph.meta.color" variant="tonal" class="font-weight-bold">
                            {{ bal.reception_tracking_number || '-' }}
                          </v-chip>
                        </td>
                        <td class="text-center text-caption font-weight-medium">
                          {{ main.formateDate(bal.date) }}
                        </td>
                        <td class="text-center text-caption">
                          {{ bal.supplier_name || '-' }}
                        </td>
                        <td class="text-right font-weight-bold text-teal">
                          {{ main.formatDisplayNumber(bal._remainingWeight) }} Kg
                          <v-progress-linear :model-value="bal._pctRemaining" color="#00897B" height="3" rounded class="mt-1"></v-progress-linear>
                        </td>
                        <td class="text-center">
                          <v-chip size="x-small" color="#6D4C41" variant="tonal">
                            {{ bal._remainingBoxes }} {{ $t('boxes') }}
                          </v-chip>
                        </td>
                        <td class="text-right">
                          <b v-if="bal._wap > 0" style="color:#a21ee5">{{ main.formatDisplayNumber(bal._wap) }}</b>
                          <span v-else class="text-grey">-</span>
                        </td>
                        <td class="text-right font-weight-bold" style="color:#FF9800">
                          {{ main.formatDisplayNumber(bal._effectivePrice) }}
                        </td>
                        <td class="text-center text-no-wrap">
                          <v-btn v-if="bal.source_type" icon variant="text" size="x-small" color="#1565C0" @click.stop="goToSource(bal)" :title="$t('view_source')">
                            <v-icon size="14">mdi-open-in-new</v-icon>
                          </v-btn>
                          <v-btn icon variant="text" size="x-small" color="#a21ee5" @click.stop="openArticlePie(bal)">
                            <v-icon size="14">mdi-chart-pie</v-icon>
                          </v-btn>
                        </td>
                      </tr>
                      <!-- Operations drawer in Phase view -->
                      <tr v-if="expandedBalances[bal.id]">
                        <td colspan="10" class="pa-0">
                          <div class="wh-ops-drawer pa-3">
                            <div class="font-weight-bold text-caption text-error mb-1 d-flex align-center ga-1">
                              <v-icon size="14" color="error">mdi-arrow-up-circle</v-icon>
                              <span>{{ $t('stock_operations_history') }} ({{ (bal.operations || []).length }})</span>
                            </div>
                            <div v-for="op in bal.operations" :key="op.id" class="d-flex align-center justify-space-between text-caption py-1 border-b">
                              <span>{{ main.formateDate(op.date) }} &bull; {{ srcLabel(op.source_type) }} #{{ op.source_id }}</span>
                              <span class="font-weight-bold text-error">-{{ main.formatDisplayNumber(op.weight) }} Kg &bull; -{{ op.package_count || 0 }} {{ $t('boxes') }}</span>
                              <span>{{ op.client_name || '-' }}</span>
                            </div>
                            <div v-if="!(bal.operations || []).length" class="text-caption text-medium-emphasis">
                              {{ $t('no_outgoing_operations') || 'Aucune sortie enregistrée' }}
                            </div>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </div>
          </v-expand-transition>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- VIEW 3: MASTER ARTICLES TABLE (Unified High-Density List of All Articles) -->
      <!-- ========================================================================= -->
      <div v-else-if="activeViewMode === 'master_list'" class="wh-master-view wh-panel-card">
        <div class="wh-panel-header d-flex align-center justify-space-between mb-3">
          <div class="d-flex align-center">
            <v-icon size="18" color="#a21ee5" class="mr-2">mdi-table-large</v-icon>
            <span class="text-subtitle-1 font-weight-bold text-warehouse">{{ $t('view_master_list') }}</span>
            <v-chip size="x-small" color="#a21ee5" variant="tonal" class="ml-2 font-weight-bold">
              {{ sortedMasterList.length }} {{ $t('articles') }}
            </v-chip>
          </div>
          <div class="d-flex align-center ga-2">
            <span class="text-caption text-medium-emphasis">{{ $t('page') || 'Page' }} {{ tablePage }} / {{ masterTotalPages }}</span>
          </div>
        </div>

        <div class="wh-articles-table-wrap">
          <table class="wh-modern-table">
            <thead>
              <tr>
                <th style="width: 32px"></th>
                <th class="text-center cursor-pointer" @click="toggleSort('date')">
                  {{ $t('date') }} <v-icon size="12" v-if="tableSortKey === 'date'">{{ tableSortDir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down' }}</v-icon>
                </th>
                <th class="text-left cursor-pointer" @click="toggleSort('_articleName')">
                  {{ $t('article_type_name') }} <v-icon size="12" v-if="tableSortKey === '_articleName'">{{ tableSortDir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down' }}</v-icon>
                </th>
                <th class="text-center">{{ $t('factory_phase') }}</th>
                <th class="text-center cursor-pointer" @click="toggleSort('reception_tracking_number')">
                  {{ $t('reception_tracking_number_col') }}
                </th>
                <th class="text-center">{{ $t('suppliers') }}</th>
                <th class="text-center">{{ $t('warehouse') }}</th>
                <th class="text-right cursor-pointer" @click="toggleSort('_remainingWeight')">
                  {{ $t('remaining') }} (Kg)
                </th>
                <th class="text-center cursor-pointer" @click="toggleSort('_remainingBoxes')">
                  {{ $t('package_count') }}
                </th>
                <th class="text-right cursor-pointer" @click="toggleSort('_wap')">
                  {{ $t('wap') }}
                </th>
                <th class="text-right cursor-pointer" @click="toggleSort('_effectivePrice')">
                  {{ $t('total_price') }}
                </th>
                <th class="text-center">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="bal in paginatedMasterList" :key="bal.id">
                <tr class="wh-article-row" :class="{ 'is-finished': bal._isDepleted }" @click="toggleExpand(bal.id)">
                  <td>
                    <v-icon size="16" :color="expandedBalances[bal.id] ? '#a21ee5' : 'grey'">
                      {{ expandedBalances[bal.id] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
                    </v-icon>
                  </td>
                  <td class="text-center text-caption font-weight-medium">
                    {{ bal._dateDisplay }}
                  </td>
                  <td class="text-left">
                    <div class="d-flex align-center ga-1">
                      <v-icon size="16" :color="tColor(bal._cleanType)">{{ tIcon(bal._cleanType) }}</v-icon>
                      <span class="font-weight-bold">{{ bal._articleName }}</span>
                      <v-chip size="x-small" color="#a21ee5" variant="tonal" class="ml-1 font-weight-medium">
                        {{ bal._familyName }}
                      </v-chip>
                    </div>
                  </td>
                  <td class="text-center">
                    <v-chip size="x-small" :color="phaseMeta(bal._cleanPhase).color" variant="tonal" class="font-weight-bold">
                      <v-icon size="12" class="mr-1">{{ phaseMeta(bal._cleanPhase).icon }}</v-icon>
                      {{ phaseMeta(bal._cleanPhase).shortTitle }}
                    </v-chip>
                  </td>
                  <td class="text-center font-weight-bold">
                    <v-chip size="x-small" color="purple-darken-1" variant="tonal">
                      {{ bal.reception_tracking_number || '-' }}
                    </v-chip>
                  </td>
                  <td class="text-center text-caption">
                    {{ bal.supplier_name || '-' }}
                  </td>
                  <td class="text-center text-caption font-weight-medium">
                    {{ bal._warehouseName }}
                  </td>
                  <td class="text-right font-weight-bold text-teal">
                    {{ main.formatDisplayNumber(bal._remainingWeight) }} Kg
                  </td>
                  <td class="text-center">
                    <v-chip size="x-small" color="#6D4C41" variant="tonal">
                      {{ bal._remainingBoxes }} {{ $t('boxes') }}
                    </v-chip>
                  </td>
                  <td class="text-right font-weight-bold" style="color:#a21ee5">
                    {{ bal._wap > 0 ? main.formatDisplayNumber(bal._wap) : '-' }}
                  </td>
                  <td class="text-right font-weight-bold text-deep-orange">
                    {{ main.formatDisplayNumber(bal._effectivePrice) }}
                  </td>
                  <td class="text-center text-no-wrap">
                    <v-btn v-if="bal.source_type" icon variant="text" size="x-small" color="#1565C0" @click.stop="goToSource(bal)">
                      <v-icon size="14">mdi-open-in-new</v-icon>
                    </v-btn>
                    <v-btn icon variant="text" size="x-small" color="#a21ee5" @click.stop="openArticlePie(bal)">
                      <v-icon size="14">mdi-chart-pie</v-icon>
                    </v-btn>
                  </td>
                </tr>
                <tr v-if="expandedBalances[bal.id]">
                  <td colspan="12" class="pa-0">
                    <div class="wh-ops-drawer pa-3">
                      <div class="font-weight-bold text-caption text-error mb-2 d-flex align-center ga-1">
                        <v-icon size="14" color="error">mdi-arrow-up-circle</v-icon>
                        <span>{{ $t('stock_operations_history') }} ({{ (bal.operations || []).length }})</span>
                      </div>
                      <table v-if="(bal.operations || []).length" class="wh-ops-inner-table">
                        <thead>
                          <tr>
                            <th>{{ $t('date') }}</th>
                            <th>{{ $t('source') }}</th>
                            <th>{{ $t('weight') }} (Kg)</th>
                            <th>{{ $t('package_count') }}</th>
                            <th>{{ $t('client') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="op in bal.operations" :key="op.id">
                            <td>{{ main.formateDate(op.date) }}</td>
                            <td><v-chip size="x-small" :color="srcColor(op.source_type)" variant="tonal">{{ srcLabel(op.source_type) }} #{{ op.source_id }}</v-chip></td>
                            <td class="text-error font-weight-bold">-{{ main.formatDisplayNumber(op.weight) }}</td>
                            <td class="text-error font-weight-bold">-{{ op.package_count || 0 }}</td>
                            <td>{{ op.client_name || '-' }}</td>
                          </tr>
                        </tbody>
                      </table>
                      <div v-else class="text-caption text-medium-emphasis">
                        {{ $t('no_outgoing_operations') || 'Aucune sortie enregistrée' }}
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Master List Pagination Controls -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-2 pt-3">
          <div class="text-caption text-medium-emphasis">
            {{ $t('showing') || 'Affichage' }} {{ ((tablePage - 1) * tableItemsPerPage) + 1 }} - {{ Math.min(tablePage * tableItemsPerPage, sortedMasterList.length) }} {{ $t('of') || 'sur' }} {{ sortedMasterList.length }}
          </div>
          <v-pagination
            v-model="tablePage"
            :length="masterTotalPages"
            density="compact"
            rounded="circle"
            active-color="#a21ee5"
            total-visible="7"
          ></v-pagination>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- VIEW 4: STEP-BY-STEP DRILLDOWN (Classic Step-by-Step, Now Fully Fixed) -->
      <!-- ========================================================================= -->
      <div v-else-if="activeViewMode === 'drilldown'" class="wh-drilldown-view">
        <!-- Breadcrumbs Navigation -->
        <div class="d-flex align-center ga-1 mb-4 flex-wrap">
          <template v-for="(bc, i) in breadcrumbs" :key="i">
            <v-btn
              v-if="i < breadcrumbs.length - 1"
              variant="text"
              color="#a21ee5"
              size="small"
              class="font-weight-bold"
              @click="drillTo(bc.level)"
              :prepend-icon="bc.icon"
            >
              {{ bc.label }}
            </v-btn>
            <v-chip v-else size="small" color="#a21ee5" variant="tonal" class="font-weight-bold" :prepend-icon="bc.icon">
              {{ bc.label }}
            </v-chip>
            <v-icon v-if="i < breadcrumbs.length - 1" size="14" color="grey">mdi-chevron-right</v-icon>
          </template>
        </div>

        <!-- Level 0: Article Type Table -->
        <div v-if="drillLevel === 0" class="wh-panel-card">
          <div class="wh-panel-header">
            <v-icon size="16" color="#a21ee5" class="mr-1">mdi-shape-outline</v-icon>
            <span>{{ $t('article_type_level') }}</span>
          </div>
          <table class="wh-modern-table">
            <thead>
              <tr>
                <th class="text-left">{{ $t('article_type_name') }}</th>
                <th class="text-right">{{ $t('quantity') }}</th>
                <th class="text-right">{{ $t('total_price') }} ({{ main.currency }})</th>
                <th class="text-center">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in articleTypes" :key="row.type" class="wh-article-row" @click="drillIntoType(row.type)">
                <td class="text-left">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="20" :color="tColor(row.type)">{{ tIcon(row.type) }}</v-icon>
                    <span class="font-weight-bold text-subtitle-2">{{ typeName(row.type) }}</span>
                  </div>
                </td>
                <td class="text-right font-weight-bold text-teal">
                  <span v-if="row.type === 'PackageType'">{{ main.formatDisplayNumber(row.package_count) }} {{ $t('boxes') }}</span>
                  <span v-else>{{ main.formatDisplayNumber(row.weight) }} Kg</span>
                </td>
                <td class="text-right font-weight-bold text-deep-orange">
                  {{ main.formatDisplayNumber(row.price) }}
                </td>
                <td class="text-center">
                  <v-btn icon variant="text" size="x-small" color="#a21ee5" @click.stop="drillIntoType(row.type)">
                    <v-icon size="18">mdi-arrow-right</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Level 1: Fish Family Table -->
        <div v-if="drillLevel === 1 && drillType === 'FishType'" class="wh-panel-card">
          <div class="wh-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon size="18" color="#a21ee5" class="mr-2">mdi-fish</v-icon>
              <span>{{ $t('fish_family_level') }}</span>
            </div>
          </div>
          <table class="wh-modern-table">
            <thead>
              <tr>
                <th class="text-left">{{ $t('fish_family_name') }}</th>
                <th class="text-center">{{ $t('package_count') }}</th>
                <th class="text-right">{{ $t('weight') }} (Kg)</th>
                <th class="text-right">{{ $t('wap') }} ({{ main.currency }}/Kg)</th>
                <th class="text-right">{{ $t('total_price') }} ({{ main.currency }})</th>
                <th class="text-center">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="fam in fishFamilies" :key="fam.id" class="wh-article-row" @click="drillIntoFamily(fam)">
                <td class="text-left font-weight-bold text-subtitle-2">{{ fam.name }}</td>
                <td class="text-center">
                  <v-chip v-if="fam.packages" size="x-small" color="#6D4C41" variant="tonal">{{ fam.packages }}</v-chip>
                  <span v-else class="text-grey">-</span>
                </td>
                <td class="text-right font-weight-bold text-teal">{{ main.formatDisplayNumber(fam.weight) }}</td>
                <td class="text-right font-weight-bold" style="color:#a21ee5">{{ fam.wap > 0 ? main.formatDisplayNumber(fam.wap) : '-' }}</td>
                <td class="text-right font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(fam.price) }}</td>
                <td class="text-center">
                  <v-btn icon variant="text" size="x-small" color="#a21ee5" @click.stop="drillIntoFamily(fam)">
                    <v-icon size="18">mdi-arrow-right</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Level 2: Fish Name Table -->
        <div v-if="drillLevel === 2 && drillType === 'FishType'" class="wh-panel-card">
          <div class="wh-panel-header">
            <v-icon size="18" color="#a21ee5" class="mr-2">mdi-fish</v-icon>
            <span>{{ $t('fish_name_level') }} - {{ drillFamilyName }}</span>
          </div>
          <table class="wh-modern-table">
            <thead>
              <tr>
                <th class="text-left">{{ $t('fish_name') }}</th>
                <th class="text-center">{{ $t('batches_count') }}</th>
                <th class="text-center">{{ $t('package_count') }}</th>
                <th class="text-right">{{ $t('weight') }} (Kg)</th>
                <th class="text-right">{{ $t('wap') }}</th>
                <th class="text-right">{{ $t('total_price') }}</th>
                <th class="text-center">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="fn in fishNames" :key="fn.id" class="wh-article-row" @click="drillIntoFishName(fn)">
                <td class="text-left font-weight-bold text-subtitle-2">{{ fn.name }}</td>
                <td class="text-center"><v-chip size="x-small" color="#a21ee5" variant="tonal">{{ fn.count }}</v-chip></td>
                <td class="text-center"><v-chip size="x-small" color="#6D4C41" variant="tonal">{{ fn.packages }}</v-chip></td>
                <td class="text-right font-weight-bold text-teal">{{ main.formatDisplayNumber(fn.weight) }}</td>
                <td class="text-right font-weight-bold" style="color:#a21ee5">{{ fn.wap > 0 ? main.formatDisplayNumber(fn.wap) : '-' }}</td>
                <td class="text-right font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(fn.price) }}</td>
                <td class="text-center">
                  <v-btn icon variant="text" size="x-small" color="#a21ee5" @click.stop="drillIntoFishName(fn)">
                    <v-icon size="18">mdi-arrow-right</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Level 3: Fish Articles Table (Batches) -->
        <div v-if="drillLevel === 3" class="wh-panel-card">
          <div class="wh-panel-header">
            <v-icon size="18" color="#a21ee5" class="mr-2">mdi-table</v-icon>
            <span>{{ $t('fish_article_level') }} - {{ drillFishName }}</span>
          </div>
          <table class="wh-modern-table">
            <thead>
              <tr>
                <th style="width: 32px"></th>
                <th>{{ $t('date') }}</th>
                <th>{{ $t('reception_tracking_number_col') }}</th>
                <th>{{ $t('suppliers') }}</th>
                <th>{{ $t('factory_phase') }}</th>
                <th class="text-right">{{ $t('remaining') }} (Kg)</th>
                <th class="text-center">{{ $t('package_count') }}</th>
                <th class="text-right">{{ $t('wap') }}</th>
                <th class="text-right">{{ $t('total_price') }}</th>
                <th class="text-center">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="bal in rawFishArticles" :key="bal.id">
                <tr class="wh-article-row" @click="toggleExpand(bal.id)">
                  <td>
                    <v-icon size="16" :color="expandedBalances[bal.id] ? '#a21ee5' : 'grey'">
                      {{ expandedBalances[bal.id] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
                    </v-icon>
                  </td>
                  <td>{{ bal._dateDisplay }}</td>
                  <td><v-chip size="x-small" color="purple-darken-1" variant="tonal">{{ bal.reception_tracking_number || '-' }}</v-chip></td>
                  <td>{{ bal.supplier_name || '-' }}</td>
                  <td><v-chip size="x-small" :color="phaseMeta(bal._cleanPhase).color" variant="tonal">{{ phaseMeta(bal._cleanPhase).shortTitle }}</v-chip></td>
                  <td class="text-right font-weight-bold text-teal">{{ main.formatDisplayNumber(bal._remainingWeight) }}</td>
                  <td class="text-center">{{ bal._remainingBoxes }}</td>
                  <td class="text-right font-weight-bold" style="color:#a21ee5">{{ bal._wap > 0 ? main.formatDisplayNumber(bal._wap) : '-' }}</td>
                  <td class="text-right font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(bal._effectivePrice) }}</td>
                  <td class="text-center">
                    <v-btn icon variant="text" size="x-small" color="#a21ee5" @click.stop="openArticlePie(bal)"><v-icon size="14">mdi-chart-pie</v-icon></v-btn>
                  </td>
                </tr>
                <tr v-if="expandedBalances[bal.id]">
                  <td colspan="10" class="pa-0">
                    <div class="wh-ops-drawer pa-3">
                      <div class="font-weight-bold text-caption text-error mb-1">{{ $t('stock_operations_history') }}</div>
                      <div v-for="op in bal.operations" :key="op.id" class="d-flex align-center justify-space-between text-caption py-1">
                        <span>{{ main.formateDate(op.date) }} &bull; {{ srcLabel(op.source_type) }}</span>
                        <span class="text-error font-weight-bold">-{{ main.formatDisplayNumber(op.weight) }} Kg</span>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </v-card-text>

  <!-- ========================================================================= -->
  <!-- RULE 5: SUMMARY SNACKBAR WITH COUNTDOWN PROGRESS BAR -->
  <!-- ========================================================================= -->
  <!-- Summary Snackbar -->
  <v-snackbar
    v-model="summarySnackbar.show"
    :timeout="8000"
    location="top right"
    color="surface"
    rounded="xl"
    elevation="12"
    min-width="360"
    max-width="440"
    @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
  >
    <v-progress-linear :model-value="snackbarProgress" color="#a21ee5" height="4" class="mb-2" rounded></v-progress-linear>
    <div class="d-flex align-center mb-2">
      <v-icon :color="summarySnackbar.color || '#a21ee5'" class="mr-2">{{ summarySnackbar.icon }}</v-icon>
      <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
    </div>
    <v-divider class="mb-2" color="#a21ee5"></v-divider>
    <div class="text-body-2 text-medium-emphasis">
      {{ summarySnackbar.message }}
    </div>
    <template #actions>
      <v-btn variant="text" color="#a21ee5" size="small" class="font-weight-bold" @click="summarySnackbar.show = false">
        {{ $t('close') }}
      </v-btn>
    </template>
  </v-snackbar>

  <!-- === CHART DIALOG === -->
  <v-dialog v-model="chartDialog" max-width="720" scrollable>
    <v-card class="rounded-xl">
      <v-card-title class="d-flex align-center pa-4">
        <v-icon size="22" :color="chartMode === 'pie' ? '#a21ee5' : '#00897B'" class="mr-2">
          {{ chartMode === 'pie' ? 'mdi-chart-pie' : 'mdi-chart-line' }}
        </v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ chartTitle }}</span>
        <v-spacer></v-spacer>
        <v-btn icon variant="text" size="small" @click="chartDialog = false"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-divider color="#a21ee5"></v-divider>
      <v-card-text class="pa-4">
        <div v-if="chartMode === 'pie' && pieSeries.length">
          <apexchart type="pie" :height="380" :options="pieOptions" :series="pieSeries"></apexchart>
        </div>
        <div v-else-if="chartMode === 'line' && lineSeries.length && lineSeries[0].data.length">
          <apexchart type="line" :height="350" :options="lineOptions" :series="lineSeries"></apexchart>
        </div>
        <div v-else class="text-center text-caption text-medium-emphasis pa-8">
          <v-icon size="40" color="grey" class="mb-2">mdi-chart-box-outline</v-icon>
          <div>{{ $t('no_chart_data') }}</div>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</v-card>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import script from './script.js';
export default { ...script, components: { ...script.components, apexchart: VueApexCharts } };
</script>

<style scoped>
.wh-card {
  border-radius: 20px !important;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  background: rgba(var(--v-theme-surface), 1);
}

/* Hero Header per claude.md: background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%) */
.wh-hero {
  background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  min-height: 104px;
}
.wh-hero-inner {
  position: relative;
  z-index: 1;
  padding: 22px 28px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
}
.wh-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
}
.wh-hero-title {
  font-size: 21px;
  font-weight: 800;
  letter-spacing: 0.3px;
}
.wh-pulse-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #00E676;
  box-shadow: 0 0 8px #00E676;
  animation: wh-pulse 1.8s infinite;
}
@keyframes wh-pulse {
  0% { transform: scale(0.9); opacity: 0.8; }
  50% { transform: scale(1.3); opacity: 1; }
  100% { transform: scale(0.9); opacity: 0.8; }
}

/* Warehouse Selector Card */
.wh-selector-card {
  border-color: rgba(162, 30, 229, 0.2) !important;
  background: rgba(var(--v-theme-surface), 1);
  box-shadow: 0 2px 10px rgba(162, 30, 229, 0.04);
}
.text-warehouse {
  color: #a21ee5 !important;
}

/* KPI Stat Cards */
.wh-stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 14px;
  background: rgba(var(--v-theme-surface), 1);
  transition: transform 0.2s, box-shadow 0.2s;
}
.wh-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
}
.wh-stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.wh-stat-value {
  font-size: 17px;
  font-weight: 800;
  line-height: 1.2;
}
.wh-stat-label {
  font-size: 11px;
  font-weight: 600;
  opacity: 0.65;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-top: 2px;
}

/* Panels */
.wh-panel-card {
  border: 1.5px solid rgba(162, 30, 229, 0.15);
  border-radius: 14px;
  padding: 16px;
  background: rgba(var(--v-theme-surface), 1);
}
.wh-panel-header {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
}
.wh-stats-panel {
  max-height: 420px;
}
.wh-stat-item {
  padding: 10px 12px;
  border-radius: 10px;
  background: rgba(162, 30, 229, 0.03);
  border: 1px solid rgba(162, 30, 229, 0.08);
}

/* Sub-Element & Phase Cards */
.wh-sub-card, .wh-phase-card {
  border-color: rgba(var(--v-border-color), 0.12) !important;
  background: rgba(var(--v-theme-surface), 1);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
  transition: box-shadow 0.2s;
}
.wh-sub-card:hover, .wh-phase-card:hover {
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
}
.wh-sub-badge-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Modern Tables */
.wh-articles-table-wrap {
  width: 100%;
  overflow-x: auto;
}
.wh-modern-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.wh-modern-table th {
  padding: 11px 14px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  opacity: 0.7;
  border-bottom: 1.5px solid rgba(var(--v-border-color), 0.12);
  background: rgba(162, 30, 229, 0.04);
  white-space: nowrap;
}
.wh-modern-table td {
  padding: 11px 14px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.06);
  vertical-align: middle;
}
.wh-article-row {
  cursor: pointer;
  transition: background 0.15s;
}
.wh-article-row:hover {
  background: rgba(162, 30, 229, 0.04) !important;
}
.wh-article-row.is-finished {
  opacity: 0.55;
}

/* Outgoing Operations Drawer */
.wh-ops-drawer {
  background: rgba(244, 67, 54, 0.03);
  border-top: 1px solid rgba(244, 67, 54, 0.12);
  border-bottom: 1px solid rgba(244, 67, 54, 0.12);
}
.wh-ops-inner-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}
.wh-ops-inner-table th {
  padding: 7px 10px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  background: rgba(244, 67, 54, 0.06);
  text-align: left;
}
.wh-ops-inner-table td {
  padding: 7px 10px;
  border-bottom: 1px solid rgba(244, 67, 54, 0.05);
}

.wh-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 72px 0;
}
.wh-input :deep(.v-field), .wh-search-input :deep(.v-field), .wh-select-compact :deep(.v-field) {
  border-radius: 12px !important;
}
</style>
