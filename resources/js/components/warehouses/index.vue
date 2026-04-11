<template>
  <div class="wh-dashboard">
    <!-- ═══════════ HEADER ═══════════ -->
    <div class="wh-header">
      <div class="wh-header-glow"></div>
      <div class="d-flex align-center ga-4 mb-1">
        <div class="wh-icon-wrap">
          <v-icon size="28" color="white">mdi-warehouse</v-icon>
        </div>
        <div>
          <h1 class="wh-title">{{ $t('warehouse_dashboard') }}</h1>
          <p class="wh-subtitle">{{ $t('warehouse_dashboard_subtitle') }}</p>
        </div>
      </div>
    </div>

    <!-- ═══════════ SECTION 1: KPI CARDS ═══════════ -->

    <!-- Skeleton KPIs -->
    <template v-if="loadingStats">
      <div class="wh-kpi-group">
        <v-skeleton-loader type="chip" width="160" class="mb-3"></v-skeleton-loader>
        <div class="wh-kpi-row">
          <div class="wh-kpi-card" v-for="i in 4" :key="'sk-kpi-'+i">
            <v-skeleton-loader type="avatar" class="me-3"></v-skeleton-loader>
            <div class="flex-grow-1">
              <v-skeleton-loader type="text@2"></v-skeleton-loader>
            </div>
          </div>
        </div>
      </div>
      <div class="wh-kpi-group" v-for="j in 2" :key="'sk-cat-'+j">
        <v-skeleton-loader type="chip" width="120" class="mb-3"></v-skeleton-loader>
        <div class="wh-kpi-row">
          <div class="wh-kpi-card wh-kpi-card--small" v-for="i in 4" :key="'sk-ckpi-'+j+'-'+i">
            <v-skeleton-loader type="avatar" class="me-2"></v-skeleton-loader>
            <div class="flex-grow-1">
              <v-skeleton-loader type="text@2"></v-skeleton-loader>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Real KPIs -->
    <template v-else>
    <!-- Global Totals -->
    <div class="wh-kpi-group">
      <div class="wh-kpi-group-label">
        <v-chip color="primary" variant="flat" size="small" class="text-uppercase font-weight-bold">
          <v-icon start size="14">mdi-sigma</v-icon>
          {{ $t('all_warehouses') }}
        </v-chip>
      </div>
      <div class="wh-kpi-row">
        <div class="wh-kpi-card" v-for="kpi in kpiCards" :key="kpi.key">
          <div class="wh-kpi-icon-wrap" :style="{ background: kpi.bg }">
            <v-icon :color="kpi.color" size="26">{{ kpi.icon }}</v-icon>
          </div>
          <div class="wh-kpi-info">
            <span class="wh-kpi-val">{{ kpi.value }}</span>
            <span class="wh-kpi-lbl">{{ kpi.label }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Per-Category KPIs -->
    <div v-for="cat in categoryKpiCards" :key="cat.id" class="wh-kpi-group wh-kpi-group--category">
      <div class="wh-kpi-group-label">
        <v-chip :color="categoryChipColor(cat.name)" variant="tonal" size="small" class="text-uppercase font-weight-bold">
          <v-icon start size="14">{{ categoryChipIcon(cat.name) }}</v-icon>
          {{ cat.name }}
        </v-chip>
      </div>
      <div class="wh-kpi-row">
        <div class="wh-kpi-card wh-kpi-card--small" v-for="kpi in cat.cards" :key="kpi.key">
          <div class="wh-kpi-icon-wrap wh-kpi-icon-wrap--sm" :style="{ background: kpi.bg }">
            <v-icon :color="kpi.color" size="20">{{ kpi.icon }}</v-icon>
          </div>
          <div class="wh-kpi-info">
            <span class="wh-kpi-val wh-kpi-val--sm">{{ kpi.value }}</span>
            <span class="wh-kpi-lbl">{{ kpi.label }}</span>
          </div>
        </div>
      </div>
    </div>
    </template>

    <!-- ═══════════ SECTION 2: LINE CHART ═══════════ -->
    <div class="wh-chart-card">
      <div class="wh-chart-header">
        <h2 class="wh-chart-title">
          <v-icon start color="primary">mdi-chart-line</v-icon>
          {{ $t('stock_movement') }}
        </h2>
        <div class="wh-chart-controls">
          <v-btn-toggle v-model="chartPeriod" mandatory density="compact" color="primary" variant="outlined" rounded="lg">
            <v-btn value="daily" size="small">{{ $t('daily') }}</v-btn>
            <v-btn value="monthly" size="small">{{ $t('monthly') }}</v-btn>
            <v-btn value="yearly" size="small">{{ $t('yearly') }}</v-btn>
          </v-btn-toggle>
        </div>
      </div>
      <div class="wh-chart-body">
        <v-skeleton-loader v-if="loadingStats" type="image" height="320" class="rounded-lg"></v-skeleton-loader>
        <apexchart
          v-else-if="chartSeries.length > 0"
          type="line"
          height="320"
          :options="chartOptions"
          :series="chartSeries"
        ></apexchart>
        <div v-else class="wh-chart-empty">
          <v-icon size="48" color="grey-lighten-1">mdi-chart-line-variant</v-icon>
          <p class="text-medium-emphasis mt-2">{{ $t('no_data_available') }}</p>
        </div>
      </div>
    </div>

    <!-- ═══════════ GLOBAL ARTICLE SEARCH ═══════════ -->
    <div class="wh-global-search-card">
      <div class="wh-global-search-header">
        <h2 class="wh-chart-title">
          <v-icon start color="deep-purple">mdi-text-search</v-icon>
          {{ $t('global_article_search') }}
        </h2>
      </div>
      <v-text-field
        v-model="globalSearchQuery"
        :placeholder="$t('global_search_placeholder')"
        density="compact"
        variant="outlined"
        rounded
        prepend-inner-icon="mdi-magnify"
        hide-details
        clearable
        color="info"
        class="mb-3 text-info"
        :loading="globalSearchLoading"
      ></v-text-field>
      <v-expand-transition>
        <div v-if="globalSearchResults.length > 0">
          <v-table class="wh-detail-table" density="compact">
            <thead>
              <tr>
                <th>{{ $t('warehouse') }}</th>
                <th>{{ $t('article_name_col') }}</th>
                <th>{{ $t('family') }}</th>
                <th>{{ $t('tracking_numbers') }}</th>
                <th>{{ $t('date_entered') }}</th>
                <th>{{ $t('remaining_weight') }}</th>
                <th>{{ $t('wap') }}</th>
                <th>{{ $t('total_price') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in globalSearchResults" :key="r.tracking_number_id">
                <td>
                  <v-chip size="small" color="primary" variant="tonal" class="font-weight-bold">{{ r.warehouse_name }}</v-chip>
                </td>
                <td>{{ r.article_name?.name_en || r.article_name?.name_fr || 'N/A' }}</td>
                <td><span class="text-caption">{{ r.group_name }}</span></td>
                <td>
                  <v-chip v-for="tn in r.tracking_numbers" :key="tn" size="x-small" color="info" variant="tonal" class="me-1">{{ tn }}</v-chip>
                </td>
                <td>{{ formatDate(r.date_entered) }}</td>
                <td class="font-weight-bold">{{ fmt(r.remaining_weight) }} KG</td>
                <td>
                  <v-chip size="small" :color="r.wap !== null ? 'success' : 'grey'" variant="tonal">
                    {{ r.wap !== null ? fmt(r.wap) + ' MAD/KG' : 'N/A' }}
                  </v-chip>
                </td>
                <td class="font-weight-bold text-primary">{{ r.total_price !== null ? fmt(r.total_price) + ' MAD' : 'N/A' }}</td>
              </tr>
            </tbody>
          </v-table>
          <div class="text-caption text-medium-emphasis mt-2 px-2">
            {{ globalSearchResults.length }} {{ $t('results_found') }}
          </div>
        </div>
      </v-expand-transition>
      <div v-if="globalSearchQuery && globalSearchQuery.length >= 2 && !globalSearchLoading && globalSearchResults.length === 0" class="text-center py-4 text-medium-emphasis">
        <v-icon size="32" class="mb-1">mdi-magnify-close</v-icon>
        <p>{{ $t('no_results_found') }}</p>
      </div>
    </div>

    <!-- ═══════════ SECTION 3: WAREHOUSE LIST ═══════════ -->
    <div class="wh-section-header">
      <h2 class="wh-section-title">
        <v-icon start color="primary">mdi-warehouse</v-icon>
        {{ $t('warehouses') }}
      </h2>
      <div class="wh-filters">
        <v-select
          v-model="selectedCategory"
          :items="categoryOptions"
          item-title="name"
          item-value="id"
          :placeholder="$t('filter_by_category')"
          density="compact"
          variant="outlined"
          rounded
          hide-details
          clearable
          color="info"
          prepend-inner-icon="mdi-filter-variant"
          class="wh-category-filter text-info"
        ></v-select>
        <v-text-field
          v-model="searchQuery"
          :placeholder="$t('search_warehouse')"
          density="compact"
          variant="outlined"
          rounded
          prepend-inner-icon="mdi-magnify"
          hide-details
          class="wh-search text-info"
          clearable
          color="info"
        ></v-text-field>
      </div>
    </div>

    <v-row v-if="loadingWarehouses">
      <v-col cols="12" md="4" sm="6" v-for="i in 6" :key="i">
        <v-skeleton-loader type="card, article" class="rounded-xl"></v-skeleton-loader>
      </v-col>
    </v-row>

    <v-row v-else>
      <v-col
        cols="12" md="4" sm="6"
        v-for="wh in filteredWarehouses"
        :key="wh.id"
      >
        <div class="wh-card" :class="{ 'wh-card-expanded': expandedWarehouse === wh.id }">
          <div class="wh-card-bg"></div>
          <div class="wh-card-content">
            <div class="d-flex justify-space-between align-start mb-3">
              <div>
                <h3 class="wh-card-name" @click="toggleWarehouse(wh.id)">{{ wh.name }}</h3>
                <div class="wh-card-date">
                  <v-icon size="12" class="me-1">mdi-calendar</v-icon>
                  {{ wh.created_at || 'N/A' }}
                </div>
              </div>
              <div class="d-flex gap-1">
                <v-btn icon size="x-small" variant="tonal" color="primary" @click.stop="openPieChart(wh)">
                  <v-icon size="16">mdi-chart-pie</v-icon>
                  <v-tooltip activator="parent" location="top">{{ $t('article_distribution') }}</v-tooltip>
                </v-btn>
              </div>
            </div>
            <div class="wh-card-stats">
              <div class="wh-card-stat">
                <v-icon size="16" color="success" class="me-1">mdi-weight-kilogram</v-icon>
                <span class="wh-stat-value">{{ fmt(wh.total_weight) }} KG</span>
              </div>
              <div class="wh-card-stat">
                <v-icon size="16" color="warning" class="me-1">mdi-cash-multiple</v-icon>
                <span class="wh-stat-value">{{ fmt(wh.total_price) }} MAD</span>
              </div>
            </div>
            <v-btn
              block
              variant="tonal"
              color="primary"
              size="small"
              class="mt-3 text-none"
              rounded="lg"
              @click="toggleWarehouse(wh.id)"
              :prepend-icon="expandedWarehouse === wh.id ? 'mdi-chevron-up' : 'mdi-chevron-down'"
            >
              {{ expandedWarehouse === wh.id ? $t('collapse') : $t('view_stock') }}
            </v-btn>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- ═══════════ SECTION 4: EXPANDED STOCK DETAILS (3-Level Drill-Down) ═══════════ -->
    <v-expand-transition>
      <div v-if="expandedWarehouse && stockFamilies.length > 0" ref="stockPanel" class="wh-stock-panel">
        <div class="wh-stock-panel-header">
          <h3>
            <v-icon start color="info">mdi-package-variant-closed</v-icon>
            {{ $t('stock_details') }} — {{ expandedWarehouseName }}
          </h3>
        </div>

        <!-- ── Breadcrumb Navigation ── -->
        <div class="wh-breadcrumb">
          <v-btn
            variant="text"
            size="small"
            class="text-none font-weight-bold"
            :color="drillLevel === 'families' ? 'primary' : 'default'"
            @click="drillToFamilies"
          >
            <v-icon start size="16">mdi-home</v-icon>
            {{ $t('all_families') }}
          </v-btn>
          <template v-if="drillLevel === 'articles' || drillLevel === 'tracking'">
            <v-icon size="16" class="mx-1 text-medium-emphasis">mdi-chevron-right</v-icon>
            <v-btn
              variant="text"
              size="small"
              class="text-none font-weight-bold"
              :color="drillLevel === 'articles' ? 'primary' : 'default'"
              @click="drillToArticles(selectedFamily)"
            >
              <v-avatar :color="familyColor(selectedFamily.group_type)" size="20" class="me-1" variant="tonal">
                <v-icon size="12">{{ familyIcon(selectedFamily.group_type) }}</v-icon>
              </v-avatar>
              {{ selectedFamily.group_name }}
            </v-btn>
          </template>
          <template v-if="drillLevel === 'tracking'">
            <v-icon size="16" class="mx-1 text-medium-emphasis">mdi-chevron-right</v-icon>
            <v-chip color="primary" size="small" variant="tonal" class="font-weight-bold">
              {{ selectedArticleName }}
            </v-chip>
          </template>
        </div>

        <!-- ═══ LEVEL 1: FAMILIES TABLE ═══ -->
        <v-slide-x-transition leave-absolute>
          <v-table v-if="drillLevel === 'families'" class="wh-detail-table wh-drill-table" density="compact" key="families">
            <thead>
              <tr>
                <th>{{ $t('family_name') }}</th>
                <th class="text-center">{{ $t('type') }}</th>
                <th class="text-center">{{ $t('articles_count') }}</th>
                <th class="text-center">{{ $t('weight') }}</th>
                <th class="text-center">{{ $t('value') }}</th>
                <th class="text-center">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="family in stockFamilies" :key="family.group_name" class="wh-drill-row" @click="drillToArticles(family)">
                <td>
                  <div class="d-flex align-center ga-3">
                    <v-avatar :color="familyColor(family.group_type)" variant="tonal" rounded="lg" size="36">
                      <v-icon size="18">{{ familyIcon(family.group_type) }}</v-icon>
                    </v-avatar>
                    <span class="font-weight-bold">{{ family.group_name }}</span>
                  </div>
                </td>
                <td class="text-center">
                  <v-chip :color="familyColor(family.group_type)" size="small" variant="tonal">
                    {{ $t(family.group_type) }}
                  </v-chip>
                </td>
                <td class="text-center">
                  <v-chip color="info" size="small" variant="tonal">{{ groupedArticlesForFamily(family).length }}</v-chip>
                </td>
                <td class="text-center font-weight-bold">{{ fmt(family.total_weight) }} KG</td>
                <td class="text-center font-weight-bold text-primary">{{ fmt(family.total_price) }} MAD</td>
                <td class="text-center">
                  <v-btn size="small" variant="tonal" color="info" rounded @click.stop="drillToArticles(family)">
                    <v-icon start size="16">mdi-arrow-right</v-icon>
                    {{ $t('view') }}
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-slide-x-transition>

        <!-- ═══ LEVEL 2: ARTICLES TABLE ═══ -->
        <v-slide-x-transition leave-absolute>
          <div v-if="drillLevel === 'articles'" key="articles">
            <v-btn variant="text" color="info" size="small" class="text-none mb-3" @click="drillToFamilies">
              <v-icon start size="18">mdi-arrow-left</v-icon>
              {{ $t('back') }}
            </v-btn>
            <v-table class="wh-detail-table wh-drill-table" density="compact">
              <thead>
                <tr>
                  <th>{{ $t('article_name') }}</th>
                  <th v-if="selectedFamily.group_type === 'FishType'" class="text-center">{{ $t('nbr_boxes') }}</th>
                  <th class="text-center">{{ $t('total_tracking_numbers') }}</th>
                  <th class="text-center">{{ $t('weight') }}</th>
                  <th class="text-center">{{ $t('wap') }}</th>
                  <th class="text-center">{{ $t('value') }}</th>
                  <th class="text-center">{{ $t('actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="art in currentArticles" :key="art.stockable_id" class="wh-drill-row" @click="drillToTracking(art)">
                  <td>
                    <div class="d-flex align-center ga-2">
                      <v-avatar :color="familyColor(selectedFamily.group_type)" variant="tonal" size="28" rounded>
                        <v-icon size="14">{{ familyIcon(selectedFamily.group_type) }}</v-icon>
                      </v-avatar>
                      <span class="font-weight-bold">{{ art.display_name }}</span>
                    </div>
                  </td>
                  <td v-if="selectedFamily.group_type === 'FishType'" class="text-center">{{ art.total_boxes ?? 'N/A' }}</td>
                  <td class="text-center">
                    <v-chip color="info" size="small" variant="tonal">{{ art.tracking_count }}</v-chip>
                  </td>
                  <td class="text-center font-weight-bold">{{ fmt(art.total_weight) }} KG</td>
                  <td class="text-center">
                    <v-chip size="small" :color="art.avg_wap !== null ? 'success' : 'grey'" variant="tonal">
                      {{ art.avg_wap !== null ? fmt(art.avg_wap) + ' MAD/KG' : 'N/A' }}
                    </v-chip>
                  </td>
                  <td class="text-center font-weight-bold text-primary">{{ art.total_price !== null ? fmt(art.total_price) + ' MAD' : 'N/A' }}</td>
                  <td class="text-center">
                    <v-btn size="small" variant="tonal" color="info" rounded @click.stop="drillToTracking(art)">
                      <v-icon start size="16">mdi-arrow-right</v-icon>
                      {{ $t('view') }}
                    </v-btn>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-slide-x-transition>

        <!-- ═══ LEVEL 3: TRACKING NUMBERS TABLE ═══ -->
        <v-slide-x-transition leave-absolute>
          <div v-if="drillLevel === 'tracking'" key="tracking">
            <v-btn variant="text" color="info" size="small" class="text-none mb-3" @click="drillToArticles(selectedFamily)">
              <v-icon start size="18">mdi-arrow-left</v-icon>
              {{ $t('back') }}
            </v-btn>
            <v-table class="wh-detail-table wh-drill-table" density="compact">
              <thead>
                <tr>
                  <th>{{ $t('tracking_numbers') }}</th>
                  <th>{{ $t('date_entered') }}</th>
                  <th>{{ $t('provider') }}</th>
                  <th v-if="selectedFamily.group_type === 'FishType'">{{ $t('nbr_boxes') }}</th>
                  <th>{{ $t('remaining_weight') }}</th>
                  <th>{{ $t('wap') }}</th>
                  <th>{{ $t('total_price') }}</th>
                  <th>{{ $t('actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tn in currentTrackingNumbers" :key="tn.tracking_number_id">
                  <td>
                    <v-chip v-for="t in tn.tracking_numbers" :key="t" size="x-small" color="info" variant="tonal" class="me-1">{{ t }}</v-chip>
                  </td>
                  <td>{{ formatDate(tn.date_entered) }}</td>
                  <td>{{ tn.supplier_name || 'N/A' }}</td>
                  <td v-if="selectedFamily.group_type === 'FishType'">{{ tn.nbr_boxes ?? 'N/A' }}</td>
                  <td class="font-weight-bold">{{ fmt(tn.remaining_weight) }} KG</td>
                  <td>
                    <v-chip size="small" :color="tn.wap !== null ? 'success' : 'grey'" variant="tonal">
                      {{ tn.wap !== null ? fmt(tn.wap) + ' MAD/KG' : 'N/A' }}
                    </v-chip>
                  </td>
                  <td class="font-weight-bold text-primary">{{ tn.total_price !== null ? fmt(tn.total_price) + ' MAD' : 'N/A' }}</td>
                  <td>
                    <v-btn icon size="x-small" variant="text" color="info" @click="openTransactions(tn)">
                      <v-icon size="18">mdi-chart-timeline-variant</v-icon>
                      <v-tooltip activator="parent" location="top">{{ $t('transaction_history') }}</v-tooltip>
                    </v-btn>
                    <v-btn icon size="x-small" variant="text" color="warning" @click="openEditWap(tn)">
                      <v-icon size="18">mdi-pencil</v-icon>
                      <v-tooltip activator="parent" location="top">{{ $t('edit_wap') }}</v-tooltip>
                    </v-btn>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-slide-x-transition>
      </div>
    </v-expand-transition>
    <div v-if="expandedWarehouse && loadingStock" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="40"></v-progress-circular>
    </div>

    <!-- ═══════════ DIALOG: TRANSACTION HISTORY ═══════════ -->
    <v-dialog v-model="txDialog" max-width="800" scrollable>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon start color="info">mdi-chart-timeline-variant</v-icon>
          {{ $t('transaction_history') }}
          <v-spacer></v-spacer>
          <v-btn icon size="small" variant="text" @click="txDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text v-if="txLoading" class="text-center py-10">
          <v-progress-circular indeterminate color="primary"></v-progress-circular>
        </v-card-text>
        <v-card-text v-else>
          <apexchart
            v-if="txChartSeries.length > 0"
            type="line"
            height="250"
            :options="txChartOptions"
            :series="txChartSeries"
          ></apexchart>
          <v-table density="compact" class="mt-4 wh-detail-table">
            <thead>
              <tr>
                <th>{{ $t('date') }}</th>
                <th>{{ $t('type') }}</th>
                <th>{{ $t('weight') }}</th>
                <th>{{ $t('price') }}</th>
                <th>{{ $t('remaining_weight') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in txList" :key="tx.id">
                <td>{{ formatDate(tx.date) }}</td>
                <td>
                  <v-chip size="x-small" :color="tx.type === 'in' ? 'success' : 'error'" class="text-uppercase font-weight-bold">
                    <v-icon start size="12">{{ tx.type === 'in' ? 'mdi-arrow-down-left' : 'mdi-arrow-up-right' }}</v-icon>
                    {{ tx.type }}
                  </v-chip>
                </td>
                <td>{{ fmt(tx.weight) }} KG</td>
                <td>{{ fmt(tx.price) }} MAD</td>
                <td class="font-weight-bold">{{ fmt(tx.running_weight) }} KG</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- ═══════════ DIALOG: EDIT WAP ═══════════ -->
    <v-dialog v-model="wapDialog" max-width="400">
      <v-card class="rounded-xl pa-4">
        <v-card-title class="d-flex align-center">
          <v-icon start color="warning">mdi-pencil</v-icon>
          {{ $t('edit_wap') }}
          <v-spacer></v-spacer>
          <v-btn icon size="small" variant="text" @click="wapDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-card-text>
          <p class="text-body-2 text-medium-emphasis mb-4">
            {{ $t('edit_wap_desc') }}
          </p>
          <v-text-field
            v-model="wapValue"
            :label="$t('wap') + ' (MAD/KG)'"
            type="number"
            :min="0"
            density="compact"
            variant="outlined"
            rounded
            color="warning"
            class="text-warning"
            hide-details
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="wapDialog = false">{{ $t('cancel') }}</v-btn>
          <v-btn color="warning" variant="flat" rounded="lg" :loading="wapSaving" @click="saveWap">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ═══════════ DIALOG: PIE CHART ═══════════ -->
    <v-dialog v-model="pieDialog" max-width="600">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon start color="primary">mdi-chart-pie</v-icon>
          {{ $t('article_distribution') }} — {{ pieWarehouseName }}
          <v-spacer></v-spacer>
          <v-btn icon size="small" variant="text" @click="pieDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text v-if="pieLoading" class="text-center py-10">
          <v-progress-circular indeterminate color="primary"></v-progress-circular>
        </v-card-text>
        <v-card-text v-else>
          <apexchart
            v-if="pieSeries.length > 0"
            type="pie"
            height="350"
            :options="pieOptions"
            :series="pieSeries"
          ></apexchart>
          <div v-else class="text-center py-6 text-medium-emphasis">{{ $t('no_data_available') }}</div>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import { useMain } from "@/stores/Main";

export default {
  name: "WarehouseDashboard",
  components: { apexchart: VueApexCharts },
  data() {
    return {
      // KPIs
      kpis: { total_warehouses: 0, total_stock_weight: 0, total_stock_value: 0, total_articles: 0 },
      categoryKpis: [],
      // Chart
      chartPeriod: 'monthly',
      chartData: [],
      // Warehouses
      warehouses: [],
      categories: [],
      selectedCategory: null,
      loadingWarehouses: true,
      loadingStats: true,
      searchQuery: '',
      // Global article search
      globalSearchQuery: '',
      globalSearchResults: [],
      globalSearchLoading: false,
      globalSearchTimer: null,
      // Expanded warehouse
      expandedWarehouse: null,
      stockFamilies: [],
      loadingStock: false,
      // Drill-down state
      drillLevel: 'families',
      selectedFamily: null,
      selectedArticle: null,
      // Transaction dialog
      txDialog: false,
      txLoading: false,
      txList: [],
      txCurrentArticle: null,
      // WAP dialog
      wapDialog: false,
      wapSaving: false,
      wapValue: null,
      wapCurrentArticle: null,
      // Pie dialog
      pieDialog: false,
      pieLoading: false,
      pieData: [],
      pieWarehouseName: '',
      pieTotalWeight: 0,
    };
  },
  computed: {
    _main() { return useMain(); },
    kpiCards() {
      return [
        { key: 'wh', icon: 'mdi-warehouse', label: this.$t('total_warehouses'), value: this.kpis.total_warehouses, bg: 'rgba(25,118,210,0.1)', color: 'primary' },
        { key: 'wt', icon: 'mdi-weight-kilogram', label: this.$t('total_stock_weight'), value: this.fmt(this.kpis.total_stock_weight) + ' KG', bg: 'rgba(46,125,50,0.1)', color: 'success' },
        { key: 'vl', icon: 'mdi-cash-multiple', label: this.$t('total_stock_value'), value: this.fmt(this.kpis.total_stock_value) + ' MAD', bg: 'rgba(239,108,0,0.1)', color: 'warning' },
        { key: 'ar', icon: 'mdi-package-variant', label: this.$t('total_articles'), value: this.kpis.total_articles, bg: 'rgba(156,39,176,0.1)', color: 'purple' },
      ];
    },
    categoryKpiCards() {
      return this.categoryKpis.map(cat => ({
        id: cat.id,
        name: cat.name,
        cards: [
          { key: `${cat.id}-wh`, icon: 'mdi-warehouse', label: this.$t('warehouses'), value: cat.total_warehouses, bg: 'rgba(25,118,210,0.08)', color: 'primary' },
          { key: `${cat.id}-wt`, icon: 'mdi-weight-kilogram', label: this.$t('weight'), value: this.fmt(cat.total_stock_weight) + ' KG', bg: 'rgba(46,125,50,0.08)', color: 'success' },
          { key: `${cat.id}-vl`, icon: 'mdi-cash-multiple', label: this.$t('value'), value: this.fmt(cat.total_stock_value) + ' MAD', bg: 'rgba(239,108,0,0.08)', color: 'warning' },
          { key: `${cat.id}-ar`, icon: 'mdi-package-variant', label: this.$t('articles'), value: cat.total_articles, bg: 'rgba(156,39,176,0.08)', color: 'purple' },
        ],
      }));
    },
    categoryOptions() {
      return this.categories;
    },
    filteredWarehouses() {
      let result = this.warehouses;
      if (this.selectedCategory) {
        result = result.filter(w => w.warehouse_category_id === this.selectedCategory);
      }
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase();
        result = result.filter(w => w.name.toLowerCase().includes(q));
      }
      return result;
    },
    expandedWarehouseName() {
      return this.warehouses.find(w => w.id === this.expandedWarehouse)?.name || '';
    },
    currentArticles() {
      if (!this.selectedFamily) return [];
      return this.groupedArticlesForFamily(this.selectedFamily);
    },
    currentTrackingNumbers() {
      if (!this.selectedFamily || !this.selectedArticle) return [];
      return this.selectedFamily.articles.filter(a => a.stockable_id === this.selectedArticle.stockable_id);
    },
    selectedArticleName() {
      if (!this.selectedArticle) return '';
      return this.selectedArticle.display_name || '';
    },
    chartSeries() {
      if (!this.chartData.length) return [];
      return [
        { name: this.$t('stock_in'), data: this.chartData.map(d => d.stock_in_weight) },
        { name: this.$t('sold_out'), data: this.chartData.map(d => d.sold_out_weight) },
      ];
    },
    chartOptions() {
      return {
        chart: {
          type: 'line',
          toolbar: {
            show: true,
            tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
          },
          fontFamily: 'inherit',
          zoom: { enabled: true, type: 'x', autoScaleYaxis: true },
        },
        colors: ['#2E7D32', '#D32F2F'],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 4 },
        xaxis: { categories: this.chartData.map(d => d.label) },
        yaxis: { labels: { formatter: v => this.fmt(v) + ' KG' } },
        tooltip: { y: { formatter: v => this.fmt(v) + ' KG' } },
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
        legend: { position: 'top' },
      };
    },
    // Transaction chart
    txChartSeries() {
      if (!this.txList.length) return [];
      return [{ name: this.$t('remaining_weight'), data: this.txList.map(t => t.running_weight) }];
    },
    txChartOptions() {
      return {
        chart: {
          type: 'line',
          toolbar: {
            show: true,
            tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
          },
          fontFamily: 'inherit',
          zoom: { enabled: true, type: 'x', autoScaleYaxis: true },
        },
        colors: ['#1976D2'],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 4 },
        xaxis: { categories: this.txList.map(t => this.formatDate(t.date)) },
        yaxis: { labels: { formatter: v => this.fmt(v) + ' KG' } },
        tooltip: { y: { formatter: v => this.fmt(v) + ' KG' } },
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
      };
    },
    // Pie chart
    pieSeries() {
      return this.pieData.map(d => d.weight);
    },
    pieOptions() {
      return {
        chart: { type: 'pie', fontFamily: 'inherit' },
        labels: this.pieData.map(d => d.name),
        tooltip: {
          y: { formatter: (v, { seriesIndex }) => {
            const item = this.pieData[seriesIndex];
            return this.fmt(item.weight) + ' KG (' + item.percentage + '%)';
          }}
        },
        legend: { position: 'bottom' },
        colors: ['#1976D2', '#2E7D32', '#EF6C00', '#9C27B0', '#00897B', '#D32F2F', '#5C6BC0', '#F9A825'],
      };
    },
  },
  watch: {
    chartPeriod() { this.fetchDashboardStats(); },
    globalSearchQuery(val) {
      clearTimeout(this.globalSearchTimer);
      if (!val || val.length < 2) {
        this.globalSearchResults = [];
        return;
      }
      this.globalSearchTimer = setTimeout(() => this.fetchGlobalSearch(val), 400);
    },
  },
  mounted() {
    this.fetchDashboardStats();
    this.fetchWarehouses();
  },
  methods: {
    fmt(num) {
      if (!num && num !== 0) return '0,00';
      return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    },
    formatDate(d) {
      if (!d) return 'N/A';
      return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
    },
    familyColor(type) {
      if (type === 'FishType') return 'info';
      if (type === 'GuanoType') return 'brown';
      if (type === 'PackageType') return 'teal';
      return 'grey';
    },
    familyIcon(type) {
      if (type === 'FishType') return 'mdi-fish';
      if (type === 'GuanoType') return 'mdi-leaf';
      if (type === 'PackageType') return 'mdi-package-variant';
      return 'mdi-help-circle';
    },
    categoryChipColor(name) {
      const n = (name || '').toLowerCase();
      if (n === 'local') return 'blue';
      if (n === 'foreign') return 'teal';
      if (n === 'client') return 'orange';
      return 'grey';
    },
    categoryChipIcon(name) {
      const n = (name || '').toLowerCase();
      if (n === 'local') return 'mdi-home-city';
      if (n === 'foreign') return 'mdi-earth';
      if (n === 'client') return 'mdi-account-group';
      return 'mdi-tag';
    },
    async fetchDashboardStats() {
      this.loadingStats = true;
      try {
        const res = await axios.get('/api/warehouses/dashboard-stats', { params: { period: this.chartPeriod } });
        this.chartData = res.data.chart;
        this.kpis = res.data.kpis;
        this.categoryKpis = res.data.category_kpis || [];
      } catch (e) { console.error('Dashboard stats error:', e); }
      finally { this.loadingStats = false; }
    },
    async fetchWarehouses() {
      this.loadingWarehouses = true;
      try {
        const res = await axios.get('/api/warehouses');
        this.warehouses = res.data.warehouses;
        this.categories = res.data.categories || [];
      } catch (e) { console.error('Warehouses error:', e); }
      finally { this.loadingWarehouses = false; }
    },
    // ── Drill-down helpers ──
    groupedArticlesForFamily(family) {
      const map = {};
      for (const art of family.articles) {
        const key = art.stockable_id;
        if (!map[key]) {
          const name = art.article_name?.name_en || art.article_name?.name_fr || art.article_name?.name_es || 'N/A';
          map[key] = {
            stockable_id: key,
            display_name: name,
            article_name: art.article_name,
            stockable_type: art.stockable_type,
            total_weight: 0,
            total_price: 0,
            total_boxes: 0,
            tracking_count: 0,
            wap_sum: 0,
            wap_count: 0,
          };
        }
        map[key].total_weight += art.remaining_weight || 0;
        map[key].total_price += art.total_price || 0;
        map[key].total_boxes += art.nbr_boxes || 0;
        map[key].tracking_count += 1;
        if (art.wap !== null && art.wap !== undefined) {
          map[key].wap_sum += art.wap;
          map[key].wap_count += 1;
        }
      }
      return Object.values(map).map(a => ({
        ...a,
        total_weight: Math.round(a.total_weight * 100) / 100,
        total_price: Math.round(a.total_price * 100) / 100,
        total_boxes: a.total_boxes || null,
        avg_wap: a.wap_count > 0 ? Math.round((a.wap_sum / a.wap_count) * 100) / 100 : null,
      }));
    },
    drillToFamilies() {
      this.drillLevel = 'families';
      this.selectedFamily = null;
      this.selectedArticle = null;
    },
    drillToArticles(family) {
      this.selectedFamily = family;
      this.selectedArticle = null;
      this.drillLevel = 'articles';
    },
    drillToTracking(article) {
      this.selectedArticle = article;
      this.drillLevel = 'tracking';
    },
    async toggleWarehouse(id) {
      if (this.expandedWarehouse === id) {
        this.expandedWarehouse = null;
        this.stockFamilies = [];
        this.drillToFamilies();
        return;
      }
      this.expandedWarehouse = id;
      this.loadingStock = true;
      this.stockFamilies = [];
      this.drillToFamilies();
      try {
        const res = await axios.get(`/api/warehouses/${id}/stock-details`);
        this.stockFamilies = res.data.families;
        this.$nextTick(() => {
          setTimeout(() => {
            if (this.$refs.stockPanel) {
              this.$refs.stockPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          }, 150);
        });
      } catch (e) { console.error('Stock details error:', e); }
      finally { this.loadingStock = false; }
    },
    async openTransactions(article) {
      this.txCurrentArticle = article;
      this.txDialog = true;
      this.txLoading = true;
      this.txList = [];
      try {
        const res = await axios.get(`/api/warehouses/${this.expandedWarehouse}/tracking-number/${article.tracking_number_id}/transactions`);
        this.txList = res.data.transactions;
      } catch (e) { console.error('Transactions error:', e); }
      finally { this.txLoading = false; }
    },
    openEditWap(article) {
      this.wapCurrentArticle = article;
      this.wapValue = article.custom_wap || article.wap || 0;
      this.wapDialog = true;
    },
    async saveWap() {
      this.wapSaving = true;
      try {
        await axios.put(`/api/warehouses/tracking-number/${this.wapCurrentArticle.tracking_number_id}/wap`, { custom_wap: this.wapValue });
        // Refresh stock
        if (this.expandedWarehouse) {
          const res = await axios.get(`/api/warehouses/${this.expandedWarehouse}/stock-details`);
          this.stockFamilies = res.data.families;
        }
        this.wapDialog = false;
      } catch (e) { console.error('WAP save error:', e); }
      finally { this.wapSaving = false; }
    },
    async openPieChart(wh) {
      this.pieWarehouseName = wh.name;
      this.pieDialog = true;
      this.pieLoading = true;
      this.pieData = [];
      try {
        const res = await axios.get(`/api/warehouses/${wh.id}/pie-chart`);
        this.pieData = res.data.distribution;
        this.pieTotalWeight = res.data.total_weight;
      } catch (e) { console.error('Pie chart error:', e); }
      finally { this.pieLoading = false; }
    },
    async fetchGlobalSearch(q) {
      this.globalSearchLoading = true;
      try {
        const res = await axios.get('/api/warehouses/global-search', { params: { q } });
        this.globalSearchResults = res.data.results;
      } catch (e) { console.error('Global search error:', e); }
      finally { this.globalSearchLoading = false; }
    },
  },
};
</script>

<style scoped>
.wh-dashboard {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

/* ── Header ── */
.wh-header {
  margin-bottom: 32px;
  position: relative;
}

.wh-header-glow {
  position: absolute;
  top: -30px;
  left: -20px;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(25,118,210,0.08) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.wh-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #1976d2, #42a5f5);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(25,118,210,0.25);
}

.wh-title {
  font-size: 26px;
  font-weight: 800;
  margin-bottom: 2px;
  background: linear-gradient(135deg, #1976d2, #42a5f5);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.wh-subtitle {
  font-size: 14px;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin: 0;
}

/* ── KPI Groups ── */
.wh-kpi-group {
  margin-bottom: 24px;
}
.wh-kpi-group--category {
  margin-bottom: 16px;
}
.wh-kpi-group-label {
  margin-bottom: 12px;
}

/* ── KPI Cards ── */
.wh-kpi-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}
.wh-kpi-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: rgba(var(--v-theme-surface), 0.8);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.03);
  transition: transform 0.3s, box-shadow 0.3s;
}
.wh-kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}
.wh-kpi-card--small {
  padding: 14px 16px;
  border-radius: 12px;
}
.wh-kpi-card--small:hover {
  transform: translateY(-2px);
}
.wh-kpi-icon-wrap {
  width: 52px; height: 52px;
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
}
.wh-kpi-icon-wrap--sm {
  width: 40px; height: 40px;
  border-radius: 10px;
}
.wh-kpi-info { display: flex; flex-direction: column; }
.wh-kpi-val { font-size: 20px; font-weight: 800; color: rgba(var(--v-theme-on-surface), 0.9); }
.wh-kpi-val--sm { font-size: 16px; font-weight: 700; }
.wh-kpi-lbl { font-size: 12px; color: rgba(var(--v-theme-on-surface), 0.55); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

/* ── Chart Card ── */
.wh-chart-card {
  background: rgba(var(--v-theme-surface), 0.8);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 28px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.03);
}
.wh-chart-header {
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;
}
.wh-chart-title { font-size: 18px; font-weight: 700; }
.wh-chart-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; }

/* ── Global Search ── */
.wh-global-search-card {
  background: rgba(var(--v-theme-surface), 0.8);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 28px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.03);
}
.wh-global-search-header {
  margin-bottom: 16px;
}

/* ── Section Header ── */
.wh-section-header {
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
  margin-bottom: 24px;
}
.wh-section-title { font-size: 20px; font-weight: 700; }
.wh-filters { display: flex; gap: 16px; flex-wrap: wrap; align-items: center; }
.wh-category-filter { min-width: 200px; max-width: 280px; }
.wh-search { max-width: 320px; min-width: 200px; }

/* ── Warehouse Cards ── */
.wh-card {
  position: relative;
  background: rgba(var(--v-theme-surface), 0.7);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 20px;
  padding: 20px;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 6px 24px rgba(0,0,0,0.03);
  height: 100%;
}
.wh-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.07); }
.wh-card-expanded { border-color: rgba(var(--v-theme-primary), 0.4); box-shadow: 0 12px 32px rgba(25,118,210,0.12); }
.wh-card-bg {
  position: absolute; top: -40px; right: -40px; width: 120px; height: 120px;
  background: radial-gradient(circle, rgba(var(--v-theme-primary), 0.12) 0%, transparent 70%);
  border-radius: 50%; z-index: 0;
}
.wh-card-content { position: relative; z-index: 1; }
.wh-card-name { font-size: 16px; font-weight: 700; cursor: pointer; }
.wh-card-name:hover { color: rgb(var(--v-theme-primary)); }
.wh-card-date { font-size: 11px; color: rgba(var(--v-theme-on-surface), 0.5); margin-top: 2px; }
.wh-card-stats { display: flex; gap: 16px; flex-wrap: wrap; }
.wh-card-stat { display: flex; align-items: center; }
.wh-stat-value { font-size: 14px; font-weight: 700; }

/* ── Stock Panel ── */
.wh-stock-panel {
  background: rgba(var(--v-theme-surface), 0.7);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 20px;
  padding: 24px;
  margin-top: 20px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.03);
}
.wh-stock-panel-header { margin-bottom: 16px; }
.wh-stock-panel-header h3 { font-size: 18px; font-weight: 700; }

/* ── Breadcrumb ── */
.wh-breadcrumb {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 2px;
  padding: 8px 12px;
  margin-bottom: 16px;
  background: rgba(var(--v-theme-on-surface), 0.03);
  border-radius: 12px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

/* ── Tables ── */
.wh-detail-table { background: transparent !important; }
.wh-detail-table :deep(th) {
  background: rgba(var(--v-theme-on-surface), 0.03) !important;
  font-size: 11px !important; font-weight: 700 !important;
  text-transform: uppercase; letter-spacing: 0.5px;
  border-bottom: 2px solid rgba(var(--v-theme-on-surface), 0.06) !important;
}
.wh-detail-table :deep(td) {
  padding: 12px 16px !important;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.04) !important;
}
.wh-detail-table :deep(tr:hover td) {
  background: rgba(var(--v-theme-primary), 0.02) !important;
}

/* ── Drill-down rows ── */
.wh-drill-table :deep(.wh-drill-row) {
  cursor: pointer;
  transition: background 0.2s;
}
.wh-drill-table :deep(.wh-drill-row:hover td) {
  background: rgba(var(--v-theme-primary), 0.05) !important;
}

.gap-1 { gap: 4px; }
.gap-4 { gap: 16px; }
</style>
