<template>
<v-card class="wh-card" elevation="0">
  <div class="wh-hero"><div class="wh-hero-inner">
    <div class="d-flex align-center ga-4">
      <div class="wh-hero-icon"><v-icon size="28" color="white">mdi-warehouse</v-icon></div>
      <div><div class="wh-hero-title">{{ $t('warehouse_dashboard') }}</div>
      <div class="text-white text-caption" style="opacity:.7">{{ $t('warehouse_dashboard_desc') }}</div></div>
    </div>
    <div class="wh-hero-actions"><v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchDashboardData" :loading="fetchLoading">{{ $t('refresh') }}</v-btn></div>
  </div></div>

  <v-card-text class="pa-5">
    <!-- WAREHOUSE MULTISELECT -->
    <v-autocomplete v-model="selectedWarehouseIds" :items="allWarehouses" density="compact" variant="outlined" color="#5C6BC0" hide-details multiple chips closable-chips :placeholder="$t('select_warehouses')" prepend-inner-icon="mdi-warehouse" class="wh-input mb-3" @update:model-value="onWarehouseChange">
      <template #chip="{ item, props: p }"><v-chip v-bind="p" size="small" color="#5C6BC0" variant="tonal" closable>{{ item.title }}</v-chip></template>
    </v-autocomplete>

    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="3">
        <div class="wh-panel-card">
          <div class="wh-panel-header"><v-icon size="16" color="#5C6BC0" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="indigo" :max-date="new Date()" expanded @update:model-value="onDateRangeVCChange" />
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="6">
        <div class="wh-panel-card" style="height:100%">
          <div class="wh-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#5C6BC0" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="dashChartMode" mandatory density="compact" color="#5C6BC0" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="dashChartSeries.length && dashChartSeries[0].data.length" type="line" :height="300" :options="dashChartOptions" :series="dashChartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="3">
        <div class="wh-panel-card wh-stats-panel" style="height:100%;overflow-y:auto">
          <div class="wh-panel-header"><v-icon size="16" color="#5C6BC0" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <div v-for="(stat, si) in warehouseStats" :key="si" class="wh-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#5C6BC0" variant="tonal" class="font-weight-bold"><v-icon size="12" class="mr-1">{{ tIcon(stat.type) }}</v-icon>{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="#5C6BC0" variant="elevated" class="font-weight-bold">{{ totalWeight > 0 ? Math.round(stat.weight / totalWeight * 1000) / 10 : 0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight > 0 ? (stat.weight / totalWeight * 100) : 0" color="#5C6BC0" bg-color="#C5CAE9" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="#5C6BC0">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color:#5C6BC0">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <!-- Totals -->
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#26A69A">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b class="text-teal text-caption">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#FF7043">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#42A5F5">mdi-package-variant-closed</v-icon><span class="text-caption font-weight-bold">{{ $t('total_articles') }}:</span><b class="text-blue text-caption">{{ totalBalances }}</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#AB47BC">mdi-arrow-up-bold</v-icon><span class="text-caption font-weight-bold">{{ $t('stock_in') }}:</span><b style="color:#AB47BC" class="text-caption">{{ main.formatDisplayNumber(totalStockIn) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#EF5350">mdi-arrow-down-bold</v-icon><span class="text-caption font-weight-bold">{{ $t('stock_out') }}:</span><b class="text-error text-caption">{{ main.formatDisplayNumber(totalStockOut) }} Kg</b></div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- EMPTY STATE -->
    <div v-if="!selectedWarehouseIds.length" class="wh-empty-state">
      <v-icon size="80" color="#5C6BC055">mdi-warehouse</v-icon>
      <div class="text-h6 mt-3" style="opacity:.4">{{ $t('no_warehouses_selected') }}</div>
    </div>

    <!-- LOADING -->
    <div v-else-if="fetchLoading" class="d-flex justify-center pa-12"><v-progress-circular indeterminate color="#5C6BC0" size="48"></v-progress-circular></div>

    <!-- DATA AREA -->
    <div v-else>
      <!-- BREADCRUMBS -->
      <div class="d-flex align-center ga-1 mb-4 flex-wrap">
        <template v-for="(bc, i) in breadcrumbs" :key="i">
          <v-btn v-if="i < breadcrumbs.length - 1" variant="text" color="#5C6BC0" size="small" @click="drillTo(bc.level)" :prepend-icon="bc.icon">{{ bc.label }}</v-btn>
          <v-chip v-else size="small" color="indigo" variant="tonal" :prepend-icon="bc.icon">{{ bc.label }}</v-chip>
          <v-icon v-if="i < breadcrumbs.length - 1" size="14" color="grey">mdi-chevron-right</v-icon>
        </template>
      </div>

      <!-- STAT CARDS -->
      <v-row v-if="drillLevel === 0" dense class="mb-5">
        <v-col v-for="(c, i) in [
          { icon: 'mdi-package-variant-closed', l: $t('total_articles'), v: totalBalances, color: '#26A69A', sfx: '' },
          { icon: 'mdi-weight-kilogram', l: $t('stock_weight'), v: main.formatDisplayNumber(totalWeight), color: '#42A5F5', sfx: ' Kg' },
          { icon: 'mdi-cash-multiple', l: $t('stock_value'), v: main.formatDisplayNumber(totalPrice), color: '#FF7043', sfx: ' ' + main.currency }
        ]" :key="i" cols="4">
          <div class="wh-stat-card" :style="{ borderColor: c.color + '30' }">
            <div class="wh-stat-icon" :style="{ background: c.color + '18' }"><v-icon :color="c.color" size="22">{{ c.icon }}</v-icon></div>
            <div><div class="wh-stat-value" :style="{ color: c.color }">{{ c.v }}{{ c.sfx }}</div>
            <div class="wh-stat-label">{{ c.l }}</div></div>
          </div>
        </v-col>
      </v-row>

      <!-- === LEVEL 0: ARTICLE TYPE TABLE === -->
      <div v-if="drillLevel === 0" class="wh-panel-card">
        <div class="wh-panel-header"><v-icon size="16" color="#5C6BC0" class="mr-1">mdi-shape-outline</v-icon><span>{{ $t('article_type_level') }}</span></div>
        <table class="wh-detail-table">
          <thead><tr><th>{{ $t('article_type_name') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('total_price') }} ({{ main.currency }})</th><th>{{ $t('actions') }}</th></tr></thead>
          <tbody>
            <tr v-for="row in articleTypes" :key="row.type" class="wh-stock-row" @click="drillIntoType(row.type)">
              <td><div class="d-flex align-center ga-2"><v-icon size="18" :color="tColor(row.type)">{{ tIcon(row.type) }}</v-icon><span class="font-weight-bold">{{ typeName(row.type) }}</span></div></td>
              <td class="font-weight-bold text-teal">{{ main.formatDisplayNumber(row.weight) }}</td>
              <td class="font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(row.price) }}</td>
              <td>
                <v-btn icon variant="text" size="x-small" color="purple" @click.stop="openPieL0(row.type)" :title="$t('stock_distribution')"><v-icon size="16">mdi-chart-pie</v-icon></v-btn>
                <v-btn icon variant="text" size="x-small" color="#5C6BC0" @click.stop="drillIntoType(row.type)"><v-icon size="16">mdi-arrow-right</v-icon></v-btn>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!articleTypes.length" class="text-center text-caption text-medium-emphasis pa-6">{{ $t('no_data_available') }}</div>
      </div>

      <!-- === LEVEL 1: FISH FAMILY TABLE === -->
      <div v-if="drillLevel === 1 && drillType === 'FishType'" class="wh-panel-card">
        <div class="wh-panel-header"><v-icon size="16" color="#00ACC1" class="mr-1">mdi-fish</v-icon><span>{{ $t('fish_family_level') }}</span></div>
        <table class="wh-detail-table">
          <thead><tr><th>{{ $t('fish_family_name') }}</th><th>{{ $t('package_count') }}</th><th>{{ $t('package_type_size') }}</th><th>{{ $t('weight') }} (Kg)</th><th>{{ $t('wap') }} ({{ main.currency }})</th><th>{{ $t('total_price') }} ({{ main.currency }})</th><th>{{ $t('actions') }}</th></tr></thead>
          <tbody>
            <tr v-for="fam in fishFamilies" :key="fam.id" class="wh-stock-row" @click="drillIntoFamily(fam)">
              <td class="font-weight-bold">{{ fam.name }}</td>
              <td><v-chip v-if="fam.packages" size="x-small" color="teal" variant="tonal">{{ fam.packages }}</v-chip><span v-else class="text-grey">-</span></td>
              <td>{{ fam.packageTypeNames }}</td>
              <td class="font-weight-bold text-teal">{{ main.formatDisplayNumber(fam.weight) }}</td>
              <td><v-chip size="x-small" color="indigo" variant="tonal">{{ main.formatDisplayNumber(fam.wap) }}</v-chip></td>
              <td class="font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(fam.price) }}</td>
              <td>
                <v-btn icon variant="text" size="x-small" color="purple" @click.stop="openPieL1(fam)" :title="$t('stock_distribution')"><v-icon size="16">mdi-chart-pie</v-icon></v-btn>
                <v-btn icon variant="text" size="x-small" color="teal" @click.stop="openLineChart({ type:'family', familyId:fam.id, familyName:fam.name })" :title="$t('price_history')"><v-icon size="16">mdi-chart-line</v-icon></v-btn>
                <v-btn icon variant="text" size="x-small" color="#5C6BC0" @click.stop="drillIntoFamily(fam)"><v-icon size="16">mdi-arrow-right</v-icon></v-btn>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!fishFamilies.length" class="text-center text-caption text-medium-emphasis pa-6">{{ $t('no_data_available') }}</div>
      </div>

      <!-- === LEVEL 2: FISH NAME TABLE === -->
      <div v-if="drillLevel === 2 && drillType === 'FishType'" class="wh-panel-card">
        <div class="wh-panel-header"><v-icon size="16" color="#00ACC1" class="mr-1">mdi-fish</v-icon><span>{{ $t('fish_name_level') }} - {{ drillFamilyName }}</span></div>
        <table class="wh-detail-table">
          <thead><tr><th>{{ $t('fish_name') }}</th><th>{{ $t('entries_count') }}</th><th>{{ $t('package_count') }}</th><th>{{ $t('package_type_size') }}</th><th>{{ $t('weight') }} (Kg)</th><th>{{ $t('wap') }} ({{ main.currency }})</th><th>{{ $t('total_price') }} ({{ main.currency }})</th><th>{{ $t('actions') }}</th></tr></thead>
          <tbody>
            <tr v-for="fn in fishNames" :key="fn.id" class="wh-stock-row" @click="drillIntoFishName(fn)">
              <td class="font-weight-bold">{{ fn.name }}</td>
              <td><v-chip size="x-small" color="indigo" variant="tonal">{{ fn.count }}</v-chip></td>
              <td><v-chip v-if="fn.packages" size="x-small" color="teal" variant="tonal">{{ fn.packages }}</v-chip><span v-else class="text-grey">-</span></td>
              <td>{{ fn.packageTypeNames }}</td>
              <td class="font-weight-bold text-teal">{{ main.formatDisplayNumber(fn.weight) }}</td>
              <td><v-chip size="x-small" color="indigo" variant="tonal">{{ main.formatDisplayNumber(fn.wap) }}</v-chip></td>
              <td class="font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(fn.price) }}</td>
              <td>
                <v-btn icon variant="text" size="x-small" color="teal" @click.stop="openLineChart({ type:'article', stockableId:fn.id, articleName:fn.name })" :title="$t('price_history')"><v-icon size="16">mdi-chart-line</v-icon></v-btn>
                <v-btn icon variant="text" size="x-small" color="#5C6BC0" @click.stop="drillIntoFishName(fn)"><v-icon size="16">mdi-arrow-right</v-icon></v-btn>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!fishNames.length" class="text-center text-caption text-medium-emphasis pa-6">{{ $t('no_data_available') }}</div>
      </div>

      <!-- === LEVEL 1: GUANO TABLE === -->
      <div v-if="drillLevel === 1 && drillType === 'GuanoType'" class="wh-panel-card">
        <div class="wh-panel-header"><v-icon size="16" color="#FB8C00" class="mr-1">mdi-leaf</v-icon><span>{{ $t('guano_level') }}</span></div>
        <table class="wh-detail-table">
          <thead><tr><th>{{ $t('guano_name') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('wap') }} ({{ main.currency }})</th><th>{{ $t('total_price') }} ({{ main.currency }})</th></tr></thead>
          <tbody><tr v-for="row in guanoArticles" :key="row.name"><td class="font-weight-bold">{{ row.name }}</td><td class="font-weight-bold text-teal">{{ main.formatDisplayNumber(row.weight) }}</td><td><v-chip size="x-small" color="indigo" variant="tonal">{{ main.formatDisplayNumber(row.wap) }}</v-chip></td><td class="font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(row.price) }}</td></tr></tbody>
        </table>
        <div v-if="!guanoArticles.length" class="text-center text-caption text-medium-emphasis pa-6">{{ $t('no_data_available') }}</div>
      </div>

      <!-- === LEVEL 1: PACKAGE TABLE === -->
      <div v-if="drillLevel === 1 && drillType === 'PackageType'" class="wh-panel-card">
        <div class="wh-panel-header"><v-icon size="16" color="#6D4C41" class="mr-1">mdi-package-variant</v-icon><span>{{ $t('package_level') }}</span></div>
        <table class="wh-detail-table">
          <thead><tr><th>{{ $t('package_name') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('wap') }} ({{ main.currency }})</th><th>{{ $t('total_price') }} ({{ main.currency }})</th></tr></thead>
          <tbody><tr v-for="row in packageArticles" :key="row.name"><td class="font-weight-bold">{{ row.name }}</td><td class="font-weight-bold text-teal">{{ main.formatDisplayNumber(row.weight) }}</td><td><v-chip size="x-small" color="indigo" variant="tonal">{{ main.formatDisplayNumber(row.wap) }}</v-chip></td><td class="font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(row.price) }}</td></tr></tbody>
        </table>
        <div v-if="!packageArticles.length" class="text-center text-caption text-medium-emphasis pa-6">{{ $t('no_data_available') }}</div>
      </div>

      <!-- === LEVEL 3: FISH ARTICLES TABLE === -->
      <div v-if="drillLevel === 3" class="wh-panel-card">
        <div class="wh-panel-header"><v-icon size="16" color="#00ACC1" class="mr-1">mdi-table</v-icon><span>{{ $t('fish_article_level') }} - {{ drillFishName }}</span></div>
        <div class="wh-balance-list">
          <!-- Clear filters button -->
          <div v-if="Object.values(colFilters['level3']||{}).some(f=>f&&f.length)" class="d-flex justify-end mb-2">
            <v-btn variant="tonal" color="warning" rounded size="x-small" prepend-icon="mdi-filter-off" @click="colFilters['level3']={};colFilters={...colFilters}">{{ $t('clear_filters') }}</v-btn>
          </div>
          <table class="wh-detail-table">
            <thead><tr>
              <th style="width:30px"></th>
              <th v-for="h in l2Headers" :key="h.key" class="wh-filter-th">
                <div class="wh-th-inner">
                  <span>{{ h.label }}</span>
                  <v-menu v-if="h.filterable" v-model="l2FilterMenus[h.key]" :close-on-content-click="false" location="bottom">
                    <template #activator="{ props }">
                      <v-btn v-bind="props" icon variant="text" size="14">
                        <v-icon size="12" :color="colFilters['level3']?.[h.key]?.length ? '#5C6BC0' : 'grey'">mdi-filter</v-icon>
                      </v-btn>
                    </template>
                    <v-card min-width="220" class="rounded-lg pa-2">
                      <v-autocomplete
                        :model-value="colFilters['level3']?.[h.key]||[]"
                        @update:model-value="setFilter('level3', h.key, $event)"
                        :items="filterOpts(h.key, rawFishArticles)"
                        density="compact" variant="outlined" color="#5C6BC0" hide-details
                        multiple chips closable-chips :placeholder="$t('filter')" class="text-indigo"
                      >
                        <template #chip="{ item, props: p }"><v-chip v-bind="p" size="x-small" color="#5C6BC0" variant="tonal" closable>{{ item.title }}</v-chip></template>
                      </v-autocomplete>
                    </v-card>
                  </v-menu>
                </div>
              </th>
              <th>{{ $t('actions') }}</th>
            </tr></thead>
            <tbody>
              <template v-for="bal in fishArticles" :key="bal.id">
                <tr class="wh-balance-row" :class="{ 'wh-finished': bal.is_finished }" @click="toggleExpand(bal.id)">
                  <td><v-icon size="16" :color="expandedBalances[bal.id] ? '#5C6BC0' : 'grey'">{{ expandedBalances[bal.id] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}</v-icon></td>
                  <td>{{ main.formateDate(bal.date) }}</td>
                  <td><v-chip size="x-small" color="indigo" variant="tonal">{{ bal.reception_tracking_number || '-' }}</v-chip></td>
                  <td>{{ bal.supplier_name || '-' }}</td>
                  <td>{{ bal.origin_name || '-' }}</td>
                  <td>{{ bal.fishery_name || '-' }}</td>
                  <td><v-chip v-if="bal.source_type" size="x-small" :color="srcColor(bal.source_type)" variant="tonal">{{ srcLabel(bal.source_type) }}</v-chip><span v-else class="text-grey">-</span></td>
                  <td class="font-weight-bold text-teal">{{ main.formatDisplayNumber(bal.total_weight) }}</td>
                  <td><v-chip v-if="bal.package_count" size="x-small" color="teal" variant="tonal">{{ bal.package_count }}</v-chip><span v-else class="text-grey">-</span></td>
                  <td><v-chip size="x-small" color="indigo" variant="tonal">{{ main.formatDisplayNumber(bal.wap) }}</v-chip></td>
                  <td class="font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(bal.total_price) }}</td>
                  <td><v-chip size="x-small" :color="Number(bal.remaining_weight) > 0 ? 'success' : 'grey'" variant="tonal" class="font-weight-bold">{{ main.formatDisplayNumber(bal.remaining_weight) }}</v-chip></td>
                  <td class="text-no-wrap">
                    <v-btn v-if="bal.source_type" icon variant="text" size="x-small" color="blue" @click.stop="goToSource(bal)" :title="$t('view_source')"><v-icon size="14">mdi-open-in-new</v-icon></v-btn>
                    <v-btn icon variant="text" size="x-small" color="purple" @click.stop="openPieL2(bal)" :title="$t('stock_distribution')"><v-icon size="14">mdi-chart-pie</v-icon></v-btn>
                    <v-btn icon variant="text" size="x-small" color="teal" @click.stop="openLineChart({ type:'article', stockableId:bal.stockable_id, articleName:bal.articleName })" :title="$t('price_history')"><v-icon size="14">mdi-chart-line</v-icon></v-btn>
                    <v-btn icon variant="text" size="x-small" color="orange" @click.stop="openWapDialog(bal)" :title="$t('edit_wap')"><v-icon size="14">mdi-pencil</v-icon></v-btn>
                  </td>
                </tr>
                <!-- Expanded operations -->
                <tr v-if="expandedBalances[bal.id] && bal.operations && bal.operations.length">
                  <td :colspan="13" class="pa-0">
                    <div class="wh-ops-panel">
                      <div class="wh-ops-header"><v-icon size="14" color="error" class="mr-1">mdi-arrow-up</v-icon><span class="text-caption font-weight-bold">{{ $t('stock_out') }} ({{ bal.operations.length }})</span></div>
                      <table class="wh-detail-table wh-ops-table">
                        <thead><tr><th>{{ $t('date') }}</th><th>{{ $t('weight') }} (Kg)</th><th>{{ $t('total_price') }} ({{ main.currency }})</th><th>{{ $t('source') }}</th><th>{{ $t('client') }}</th><th>{{ $t('actions') }}</th></tr></thead>
                        <tbody>
                          <tr v-for="op in bal.operations" :key="op.id" class="wh-out-row">
                            <td>{{ main.formateDate(op.date) }}</td>
                            <td class="font-weight-bold text-error">-{{ main.formatDisplayNumber(op.weight) }}</td>
                            <td>{{ main.formatDisplayNumber(op.total_price) }}</td>
                            <td><v-chip size="x-small" :color="srcColor(op.source_type)" variant="tonal">{{ srcLabel(op.source_type) }}</v-chip></td>
                            <td>{{ op.client_name || '-' }}</td>
                            <td><v-btn v-if="op.source_type" icon variant="text" size="x-small" color="blue" @click.stop="goToSourceOp(op)" :title="$t('view_source')"><v-icon size="14">mdi-open-in-new</v-icon></v-btn></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="expandedBalances[bal.id]"><td :colspan="13" class="text-center text-caption text-medium-emphasis pa-3">{{ $t('no_data_available') }}</td></tr>
              </template>
            </tbody>
          </table>
          <div v-if="!fishArticles.length" class="text-center text-caption text-medium-emphasis pa-6">{{ $t('no_data_available') }}</div>
        </div>
      </div>
    </div>
  </v-card-text>

  <!-- === CHART DIALOG === -->
  <v-dialog v-model="chartDialog" max-width="720" scrollable>
    <v-card class="rounded-xl">
      <v-card-title class="d-flex align-center pa-4">
        <v-icon size="20" :color="chartMode === 'pie' ? 'purple' : 'teal'" class="mr-2">{{ chartMode === 'pie' ? 'mdi-chart-pie' : 'mdi-chart-line' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ chartTitle }}</span>
        <v-spacer></v-spacer>
        <v-btn icon variant="text" size="small" @click="chartDialog = false"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="pa-4">
        <div v-if="chartMode === 'pie' && pieSeries.length">
          <apexchart type="pie" :height="380" :options="pieOptions" :series="pieSeries"></apexchart>
        </div>
        <div v-else-if="chartMode === 'line' && lineSeries.length && lineSeries[0].data.length">
          <apexchart type="line" :height="350" :options="lineOptions" :series="lineSeries"></apexchart>
        </div>
        <div v-else class="text-center text-caption text-medium-emphasis pa-8"><v-icon size="40" color="grey" class="mb-2">mdi-chart-box-outline</v-icon><div>{{ $t('no_chart_data') }}</div></div>
      </v-card-text>
    </v-card>
  </v-dialog>

  <!-- === WAP DIALOG === -->
  <v-dialog v-model="wapDialog" max-width="420">
    <v-card class="rounded-xl">
      <v-card-title class="d-flex align-center pa-4"><v-icon size="20" color="orange" class="mr-2">mdi-calculator</v-icon><span class="text-subtitle-1 font-weight-bold">{{ $t('edit_wap') }}</span><v-spacer></v-spacer><v-btn icon variant="text" size="small" @click="wapDialog = false"><v-icon>mdi-close</v-icon></v-btn></v-card-title>
      <v-divider></v-divider>
      <v-card-text class="pa-5">
        <div v-if="wapBalance" class="mb-4">
          <v-chip size="small" color="indigo" variant="tonal" class="mr-2">{{ wapBalance.reception_tracking_number || '-' }}</v-chip>
          <span class="text-caption text-medium-emphasis">{{ wapBalance.articleName || sLabel(wapBalance) }}</span>
        </div>
        <v-text-field v-model.number="wapValue" type="number" :label="$t('wap') + ' (' + main.currency + '/Kg)'" density="compact" variant="outlined" rounded color="orange" hide-details min="0" step="0.01"></v-text-field>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="wapDialog = false">{{ $t('cancel') }}</v-btn>
        <v-btn color="orange" variant="tonal" rounded :loading="wapSaving" @click="saveWap" prepend-icon="mdi-check">{{ $t('save') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</v-card>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import script from './script.js';
export default { ...script, components: { apexchart: VueApexCharts } };
</script>

<style scoped>
.wh-card { border-radius: 20px !important; font-family: 'Inter', sans-serif }
.wh-hero { background: linear-gradient(135deg, #283593 0%, #3949AB 40%, #5C6BC0 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 100px }
.wh-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px }
.wh-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15) }
.wh-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px }
.wh-stat-card { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 14px; border: 1.5px solid; background: rgba(var(--v-theme-surface),1); transition: transform .15s, box-shadow .15s }
.wh-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08) }
.wh-stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0 }
.wh-stat-value { font-size: 16px; font-weight: 800; line-height: 1.2 }
.wh-stat-label { font-size: 11px; font-weight: 600; opacity: .55; text-transform: uppercase; letter-spacing: .3px }
.wh-panel-card { border: 1.5px solid rgba(92,107,192,.12); border-radius: 14px; padding: 16px; background: rgba(var(--v-theme-surface),1); margin-bottom: 16px }
.wh-panel-header { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; margin-bottom: 12px; display: flex; align-items: center }
.wh-detail-table { width: 100%; border-collapse: collapse; font-size: 12.5px }
.wh-detail-table th { padding: 10px 14px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; opacity: .6; text-align: center; border-bottom: 1.5px solid rgba(var(--v-border-color),.1); background: rgba(92,107,192,.04) }
.wh-detail-table td { padding: 10px 14px; text-align: center; border-bottom: 1px solid rgba(var(--v-border-color),.05) }
.wh-stock-row { cursor: pointer; transition: background .15s }
.wh-stock-row:hover { background: rgba(92,107,192,.06) !important }
.wh-balance-row { cursor: pointer; transition: background .15s }
.wh-balance-row:hover { background: rgba(92,107,192,.05) !important }
.wh-balance-row.wh-finished { opacity: .45 }
.wh-ops-panel { margin: 0 16px 8px 40px; padding: 10px 14px; border-radius: 10px; background: rgba(244,67,54,.03); border: 1px solid rgba(244,67,54,.1) }
.wh-ops-header { margin-bottom: 8px; display: flex; align-items: center }
.wh-ops-table th { background: rgba(244,67,54,.04) !important }
.wh-out-row { background: rgba(244,67,54,.02) }
.wh-out-row:hover { background: rgba(244,67,54,.06) !important }
.wh-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 64px 0 }
.wh-input :deep(.v-field) { border-radius: 10px !important }
.wh-filter-th { white-space: nowrap }
.wh-th-inner { display: flex; align-items: center; justify-content: center; gap: 2px }
.wh-stats-panel { max-height: 400px }
.wh-stat-item { padding: 8px 10px; border-radius: 10px; background: rgba(92,107,192,.03); border: 1px solid rgba(92,107,192,.06) }
</style>
