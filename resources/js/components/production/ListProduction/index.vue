<template>
<v-card class="lp-card" elevation="0">
  <div class="lp-hero">
    <div class="lp-hero-inner">
      <div class="d-flex align-center ga-4">
        <div class="lp-hero-icon"><v-icon size="28" color="white">mdi-factory</v-icon></div>
        <div>
          <div class="lp-hero-title">{{ $t('productions') }}</div>
          <div class="text-white text-caption" style="opacity:.8">{{ $t('production_list_desc') }}</div>
        </div>
      </div>

      <!-- Quick KPI Badges in Hero -->
      <div class="d-flex align-center ga-3 flex-wrap">
        <div class="lp-hero-kpi">
          <span class="lp-kpi-val">{{ colFilteredItems.length }}</span>
          <span class="lp-kpi-lbl">{{ $t('records') }}</span>
        </div>
        <div class="lp-hero-kpi">
          <span class="lp-kpi-val">{{ main.formatDisplayNumber(totalWeight) }} <small>Kg</small></span>
          <span class="lp-kpi-lbl">{{ $t('total_weight') }}</span>
        </div>
        <div class="lp-hero-kpi">
          <span class="lp-kpi-val">{{ main.formatDisplayNumber(totalPrice) }} <small>{{ main.currency }}</small></span>
          <span class="lp-kpi-lbl">{{ $t('total_price') }}</span>
        </div>
        <div class="lp-hero-kpi">
          <span class="lp-kpi-val">{{ totalInvoiced }} / {{ colFilteredItems.length }}</span>
          <span class="lp-kpi-lbl">{{ $t('invoiced') }}</span>
        </div>
      </div>

      <div class="lp-hero-actions">
        <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchProductions(); fetchAnalytics()" :loading="loading">{{ $t('refresh') }}</v-btn>
        <v-btn v-if="selected.length && auth.hasPermission('production.delete')" variant="tonal" color="error" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete_selected') }} ({{ selected.length }})</v-btn>
      </div>
    </div>
  </div>
  <!-- Tab Slider (Matching Activity Breakdown Entity Toggle) -->
  <div class="d-flex align-center justify-space-between mx-4 mt-3">
    <SegmentedPillToggle
      v-model="activeTab"
      :items="productionTabs.map(t => ({ value: t.value, label: $t(t.label), icon: t.icon }))"
      color="#00897B"
    />
  </div>
  <!-- TAB: List Production -->
  <v-card-text v-if="activeTab === 0" class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <v-col cols="12" md="3" lg="3">
        <div class="lp-panel-card">
          <div class="lp-panel-header mb-2 pa-1"><v-icon size="16" color="#00897B" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="teal" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <v-col cols="12" md="6" lg="6">
        <div class="lp-panel-card" style="height:100%">
          <div class="lp-panel-header mb-2 pa-1 d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#00897B" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries.length>1 || (chartSeries[0] && chartSeries[0].data.length)" type="line" :height="300" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <v-col cols="12" md="3" lg="3">
        <div class="lp-panel-card lp-stats-panel" style="height:100%;overflow-y:auto">
          <div class="lp-panel-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <div v-for="(stat,si) in categoryStats" :key="si" class="lp-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="#00897B" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#00897B" bg-color="#B2DFDB" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="#00897B">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color: #00897B">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="teal-lighten-1">mdi-scale-balance</v-icon><span class="text-medium-emphasis">{{ $t('invoice_weight') }}:</span><b class="text-teal-lighten-1">{{ main.formatDisplayNumber(stat.invoiceWeight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="amber-darken-2">mdi-cash-check</v-icon><span class="text-medium-emphasis">{{ $t('invoice_price') }}:</span><b class="text-amber-darken-2">{{ main.formatDisplayNumber(stat.invoicePrice) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <!-- Invoice stats -->
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-2"><v-icon size="14" color="success">mdi-check-decagram</v-icon><span class="text-caption text-medium-emphasis">{{ $t('invoiced') }}:</span><v-chip size="x-small" color="success" variant="tonal"><b>{{ totalInvoiced }}</b></v-chip></div>
            <div class="d-flex align-center ga-2"><v-icon size="14" color="grey">mdi-close-circle-outline</v-icon><span class="text-caption text-medium-emphasis">{{ $t('not_invoiced') }}:</span><v-chip size="x-small" color="grey" variant="tonal"><b>{{ totalNotInvoiced }}</b></v-chip></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="teal-lighten-1">mdi-scale-balance</v-icon><span class="text-caption font-weight-bold">{{ $t('invoice_weight') }}:</span><b class="text-teal-lighten-1 text-caption">{{ main.formatDisplayNumber(totalInvoiceWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="amber-darken-2">mdi-cash-check</v-icon><span class="text-caption font-weight-bold">{{ $t('invoice_price') }}:</span><b class="text-amber-darken-2 text-caption">{{ main.formatDisplayNumber(totalInvoicePrice) }} {{ main.currency }}</b></div>
          </div>
          <v-divider class="my-2"></v-divider>
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#42A5F5">mdi-fishbowl-outline</v-icon><span class="text-caption font-weight-bold">{{ $t('fresh_reception') }}:</span><b style="color:#42A5F5" class="text-caption">{{ main.formatDisplayNumber(totalFreshReception) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#AB47BC">mdi-snowflake</v-icon><span class="text-caption font-weight-bold">{{ $t('frozen_production') }}:</span><b style="color:#AB47BC" class="text-caption">{{ main.formatDisplayNumber(totalFrozenProduction) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#8D6E63">mdi-leaf</v-icon><span class="text-caption font-weight-bold">{{ $t('guano_production') }}:</span><b style="color:#8D6E63" class="text-caption">{{ main.formatDisplayNumber(totalGuanoProduction) }} Kg</b></div>
            <div class="d-flex align-center ga-1">
              <v-icon size="14" :color="totalEquilibrium >= 0 ? '#4CAF50' : '#EF5350'">{{ totalEquilibrium >= 0 ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold' }}</v-icon>
              <span class="text-caption font-weight-bold">{{ $t('equilibrium') }}:</span>
              <b :style="{ color: totalEquilibrium >= 0 ? '#4CAF50' : '#EF5350' }" class="text-caption">{{ main.formatDisplayNumber(Math.abs(totalEquilibrium)) }} Kg ({{ $t(totalEquilibrium >= 0 ? 'surplus' : 'shortage') }})</b>
            </div>
            <v-divider class="my-1"></v-divider>
            <div class="lp-stat-item mt-1 pa-2 rounded-lg" style="background: rgba(84, 110, 122, 0.05); border: 1px solid rgba(84, 110, 122, 0.15)">
              <div class="d-flex align-center justify-space-between mb-1">
                <div class="d-flex align-center ga-1">
                  <v-icon size="14" color="#546E7A">mdi-layers-outline</v-icon>
                  <span class="text-caption font-weight-bold" style="color: #37474F">{{ $t('secondary_packaging_config') }}</span>
                </div>
                <v-chip size="x-small" color="#546E7A" variant="elevated" class="font-weight-bold">
                  {{ main.formatDisplayNumber(secondaryPackagingStats.total_cost) }} {{ main.currency }}
                </v-chip>
              </div>
              <div class="d-flex flex-column ga-1 mt-1">
                <div
                  v-for="comp in secondaryPackagingStats.items"
                  :key="comp.key"
                  class="d-flex align-center justify-space-between px-2 py-1 rounded"
                  :style="{ background: comp.qte > 0 ? 'rgba(84, 110, 122, 0.08)' : 'transparent', opacity: comp.qte > 0 ? 1 : 0.5 }"
                >
                  <span class="text-caption font-weight-medium">{{ comp.label }} <span v-if="comp.unit" class="text-grey text-caption font-weight-bold" style="opacity: 0.8">({{ comp.unit }})</span></span>
                  <div class="d-flex align-center ga-2 text-caption">
                    <span v-if="comp.qte > 0" class="text-medium-emphasis"><b>{{ main.formatDisplayNumber(comp.qte) }}</b> {{ comp.unit || '' }}</span>
                    <span v-else class="text-grey">-</span>
                    <b class="text-blue-grey-darken-3">{{ main.formatDisplayNumber(comp.total_cost) }} {{ main.currency }}</b>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </v-col>
    </v-row>
    <!-- Unified Command & Filter Toolbar -->
    <div class="lp-toolbar pa-2 px-3 mb-3 rounded-xl">
      <v-row dense align="center">
        <!-- Left: Quick Search -->
        <v-col cols="12" md="4" sm="6">
          <v-text-field
            v-model="searchQuery"
            prepend-inner-icon="mdi-magnify"
            :placeholder="$t('search_records_placeholder')"
            density="compact"
            variant="outlined"
            rounded
            hide-details
            clearable
            color="#00897B"
            class="lp-search-field"
          ></v-text-field>
        </v-col>

        <!-- Center: Quick Actions & Filter Triggers -->
        <v-col cols="12" md="5" sm="6" class="d-flex align-center ga-2 flex-wrap">
          <v-btn
            :variant="showAdvanced ? 'flat' : 'tonal'"
            :color="showAdvanced ? '#00897B' : undefined"
            rounded
            size="small"
            class="lp-filter-btn"
            @click="showAdvanced = !showAdvanced"
            :prepend-icon="showAdvanced ? 'mdi-filter-check' : 'mdi-filter-variant'"
          >
            {{ $t('advanced_filters') }}
            <v-badge
              v-if="activeFilterCount > 0"
              :content="activeFilterCount"
              color="#00897B"
              inline
              class="ml-1"
            ></v-badge>
          </v-btn>

          <v-btn
            :variant="showInvoice ? 'flat' : 'tonal'"
            :color="showInvoice ? '#00897B' : (uninvoicedSelectedProductions.length ? '#00897B' : undefined)"
            rounded
            size="small"
            class="lp-filter-btn"
            :disabled="!canSetupInvoice"
            @click="showInvoice = !showInvoice"
            prepend-icon="mdi-receipt-text-outline"
          >
            {{ $t('invoices') }}
            <v-badge
              v-if="uninvoicedSelectedProductions.length"
              :content="uninvoicedSelectedProductions.length"
              color="#00897B"
              inline
              class="ml-1"
            ></v-badge>
          </v-btn>

          <!-- Expand / Collapse All Details Button -->
          <v-btn
            :variant="isAllExpanded ? 'flat' : 'tonal'"
            :color="isAllExpanded ? 'warning' : '#00897B'"
            rounded
            size="small"
            class="lp-filter-btn"
            @click="toggleExpandAll"
          >
            <v-icon size="16" class="mr-1">{{ isAllExpanded ? 'mdi-arrow-collapse-vertical' : 'mdi-arrow-expand-vertical' }}</v-icon>
            {{ isAllExpanded ? $t('collapse_all') : $t('expand_all') }}
            <v-badge
              v-if="expandedCount > 0"
              :content="expandedCount"
              :color="isAllExpanded ? 'warning' : '#00897B'"
              inline
              class="ml-1"
            ></v-badge>
          </v-btn>

          <v-btn
            v-if="activeFilterCount > 0"
            variant="text"
            color="warning"
            rounded
            size="small"
            prepend-icon="mdi-filter-off"
            @click="clearAllFilters"
          >
            {{ $t('clear_filters') }}
          </v-btn>
        </v-col>

        <!-- Right: Actions (Records Counter, Table Settings & Excel) -->
        <v-col cols="12" md="3" sm="12" class="d-flex justify-end align-center ga-2">
          <v-chip size="small" variant="tonal" color="#00897B" class="font-weight-medium">
            {{ colFilteredItems.length }} / {{ productions.length }} {{ $t('records') }}
          </v-chip>

          <!-- Table Settings Menu -->
          <v-menu v-model="tableConfigMenu" :close-on-content-click="false" location="bottom end" width="300">
            <template #activator="{ props: menuProps }">
              <v-btn
                v-bind="menuProps"
                icon
                variant="tonal"
                color="#00897B"
                size="small"
                :title="$t('table_settings')"
              >
                <v-icon size="18">mdi-table-cog</v-icon>
              </v-btn>
            </template>
            <v-card class="rounded-xl pa-3" elevation="8">
              <div class="d-flex align-center justify-space-between mb-2 pb-2 border-b">
                <div class="d-flex align-center ga-1 font-weight-bold text-caption text-prod">
                  <v-icon size="16" color="#00897B">mdi-table-cog</v-icon>
                  <span>{{ $t('table_settings') }}</span>
                </div>
                <v-btn icon="mdi-close" size="x-small" variant="text" @click="tableConfigMenu = false"></v-btn>
              </div>

              <!-- Quick Reset Button -->
              <v-btn
                size="small"
                variant="tonal"
                color="warning"
                prepend-icon="mdi-restore"
                rounded="lg"
                block
                class="mb-3"
                @click="resetTableConfig"
              >
                {{ $t('reset_table_config') }}
              </v-btn>

              <div class="text-caption text-medium-emphasis mb-2 font-weight-bold">
                {{ $t('columns_order_and_pin') }}:
              </div>

              <div class="table-config-col-list" style="max-height: 260px; overflow-y: auto;">
                <div
                  v-for="h in configColumns"
                  :key="h.key"
                  class="d-flex align-center justify-space-between pa-1 px-2 mb-1 rounded bg-surface-variant-subtle"
                  style="font-size: 11px;"
                >
                  <span class="text-truncate font-weight-medium" style="max-width: 140px;" :title="h.title">
                    {{ h.title }}
                  </span>
                  <div class="d-flex align-center ga-1">
                    <v-btn
                      size="20"
                      icon
                      :variant="pinSide(h.key) === 'left' ? 'flat' : 'text'"
                      :color="pinSide(h.key) === 'left' ? '#00897B' : 'grey'"
                      :title="$t('pin_left')"
                      @click="setPin(h.key, pinSide(h.key) === 'left' ? 'none' : 'left')"
                    >
                      <v-icon size="12">mdi-dock-left</v-icon>
                    </v-btn>
                    <v-btn
                      size="20"
                      icon
                      :variant="pinSide(h.key) === 'right' ? 'flat' : 'text'"
                      :color="pinSide(h.key) === 'right' ? '#FF7043' : 'grey'"
                      :title="$t('pin_right')"
                      @click="setPin(h.key, pinSide(h.key) === 'right' ? 'none' : 'right')"
                    >
                      <v-icon size="12">mdi-dock-right</v-icon>
                    </v-btn>
                  </div>
                </div>
              </div>
            </v-card>
          </v-menu>

          <v-btn
            icon
            variant="tonal"
            color="#4CAF50"
            size="small"
            :title="$t('export_excel')"
            @click="exportExcel"
          >
            <v-icon size="18">mdi-file-excel-outline</v-icon>
          </v-btn>
        </v-col>
      </v-row>

      <v-alert v-if="uninvoicedSelectedProductions.length && !canSetupInvoice" type="warning" density="compact" variant="tonal" class="mt-2 text-caption" closable>
        {{ $t('selected_records_different_owners') }}
      </v-alert>

      <!-- Advanced Filters Expandable Panel -->
      <v-expand-transition>
        <div v-if="showAdvanced" class="adv-panel pa-4 mt-2 rounded-xl">
          <!-- Advanced Filter Header -->
          <div class="d-flex align-center justify-space-between mb-3 pb-2 border-b">
            <div class="d-flex align-center ga-2">
              <v-avatar size="28" color="#00897B" variant="tonal" rounded="lg">
                <v-icon size="16" color="#00897B">mdi-filter-cog-outline</v-icon>
              </v-avatar>
              <div>
                <span class="text-subtitle-2 font-weight-bold text-prod">{{ $t('advanced_filters') }}</span>
                <span class="text-caption text-medium-emphasis ml-2">{{ $t('active_advanced_filters') }}: <b>{{ activeAdvFilterCount }}</b></span>
              </div>
            </div>

            <div class="d-flex align-center ga-1">
              <v-btn
                v-if="activeAdvFilterCount > 0"
                variant="tonal"
                color="warning"
                size="small"
                rounded="lg"
                prepend-icon="mdi-filter-off"
                @click="clearAdvFilters"
              >
                {{ $t('clear_advanced_filters') }}
              </v-btn>
              <v-btn
                icon="mdi-close"
                size="small"
                variant="text"
                @click="showAdvanced = false"
              ></v-btn>
            </div>
          </div>

          <!-- Primary Multi-Filter Controls (Row 1) -->
          <v-row dense class="mb-2">
            <!-- Designation / Article Types (Multi-Filter) -->
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="advFilter.articleTypes"
                :items="articleTypeOptions"
                item-title="title"
                item-value="value"
                :label="$t('article_types')"
                variant="outlined"
                density="compact"
                rounded="lg"
                color="#00897B"
                class="text-prod"
                hide-details
                clearable
                multiple
                chips
                closable-chips
                prepend-inner-icon="mdi-shape-outline"
                :placeholder="$t('all_types')"
              >
                <template #chip="{ item, props }">
                  <v-chip
                    v-bind="props"
                    color="#00897B"
                    variant="tonal"
                    size="small"
                    class="font-weight-medium"
                    rounded="lg"
                  >
                    <v-icon start size="14">{{ item.raw.icon }}</v-icon>
                    {{ item.title }}
                  </v-chip>
                </template>
                <template #item="{ props, item }">
                  <v-list-item v-bind="props" :title="item.title">
                    <template #prepend>
                      <v-icon size="18" color="#00897B" class="mr-2">{{ item.raw.icon }}</v-icon>
                    </template>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>

            <!-- Warehouses (Multi-Filter) -->
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="advFilter.warehouses"
                :items="warehouseOptions"
                item-title="title"
                item-value="value"
                :label="$t('warehouse')"
                variant="outlined"
                density="compact"
                rounded="lg"
                color="#00897B"
                class="text-prod"
                hide-details
                clearable
                multiple
                chips
                closable-chips
                prepend-inner-icon="mdi-warehouse"
              >
                <template #chip="{ item, props }">
                  <v-chip
                    v-bind="props"
                    color="#00897B"
                    variant="tonal"
                    size="small"
                    class="font-weight-medium"
                    rounded="lg"
                  >
                    <v-icon start size="14">mdi-warehouse</v-icon>
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </v-col>
          </v-row>

          <!-- Contextual Sub-Filters: Fish Specifications -->
          <v-expand-transition>
            <div
              v-if="!advFilter.articleTypes?.length || advFilter.articleTypes.includes('FishType')"
              class="subfilter-card pa-3 rounded-lg mb-2"
              style="background: rgba(0, 137, 123, 0.04); border: 1px solid rgba(0, 137, 123, 0.18);"
            >
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="d-flex align-center ga-1 text-caption font-weight-bold text-prod">
                  <v-icon size="15" color="#00897B">mdi-fish</v-icon>
                  <span>{{ $t('fish_filters') }}</span>
                  <v-chip size="x-small" color="#00897B" variant="tonal" class="ml-1 font-weight-medium">
                    {{ fishTypeOptions.length }} {{ $t('in_table') }}
                  </v-chip>
                </div>
                <span class="text-caption text-medium-emphasis" style="font-size: 11px;">
                  {{ $t('cascading_hint') }}
                </span>
              </div>
              <v-row dense>
                <!-- Cascading Step 1: Fish Family -->
                <v-col cols="12" md="4" sm="6">
                  <v-autocomplete
                    v-model="advFilter.fishFamilies"
                    :items="fishFamilyOptions"
                    item-title="title"
                    item-value="value"
                    :label="$t('fish_family')"
                    :placeholder="$t('select_fish_family')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#00897B"
                    class="text-prod"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-family-tree"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="#00897B" variant="tonal" size="small" rounded="lg">
                        <v-icon start size="13">mdi-family-tree</v-icon>
                        {{ item.raw.rawTitle || item.title }}
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title">
                        <template #prepend>
                          <v-icon size="16" color="#00897B" class="mr-2">mdi-family-tree</v-icon>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
                            {{ item.raw.count }} {{ $t('in_table') }}
                          </v-chip>
                        </template>
                      </v-list-item>
                    </template>
                  </v-autocomplete>
                </v-col>

                <!-- Cascading Step 2: Fresh / Frozen State -->
                <v-col cols="12" md="4" sm="6">
                  <v-autocomplete
                    v-model="advFilter.fishFreshFrozen"
                    :items="fishFreshFrozenOptions"
                    item-title="title"
                    item-value="value"
                    :label="$t('fish_type_fresh_frozen')"
                    :placeholder="$t('fish_type_fresh_frozen')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#00897B"
                    class="text-prod"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-snowflake"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="#00897B" variant="tonal" size="small" rounded="lg">
                        <v-icon start size="13">{{ item.raw.icon || 'mdi-snowflake' }}</v-icon>
                        {{ item.raw.rawTitle || item.title }}
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title">
                        <template #prepend>
                          <v-icon size="16" color="#00897B" class="mr-2">{{ item.raw.icon || 'mdi-snowflake' }}</v-icon>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
                            {{ item.raw.count }} {{ $t('in_table') }}
                          </v-chip>
                        </template>
                      </v-list-item>
                    </template>
                  </v-autocomplete>
                </v-col>

                <!-- Cascading Step 3: Process Type -->
                <v-col cols="12" md="4" sm="12">
                  <v-autocomplete
                    v-model="advFilter.fishProcessTypes"
                    :items="fishProcessTypeOptions"
                    item-title="title"
                    item-value="value"
                    :label="$t('fish_process_type')"
                    :placeholder="$t('select_fish_process_type')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#00897B"
                    class="text-prod"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-cog-outline"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="#00897B" variant="tonal" size="small" rounded="lg">
                        <v-icon start size="13">mdi-cog-outline</v-icon>
                        {{ item.raw.rawTitle || item.title }}
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title">
                        <template #prepend>
                          <v-icon size="16" color="#00897B" class="mr-2">mdi-cog-outline</v-icon>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
                            {{ item.raw.count }} {{ $t('in_table') }}
                          </v-chip>
                        </template>
                      </v-list-item>
                    </template>
                  </v-autocomplete>
                </v-col>

                <!-- Cascading Step 4: Specific Fish Types Autocomplete (Crosses all active filters!) -->
                <v-col cols="12" class="mt-1">
                  <v-autocomplete
                    v-model="advFilter.fishTypes"
                    :items="fishTypeOptions"
                    item-title="title"
                    item-value="value"
                    :label="$t('fish_types')"
                    :placeholder="$t('select_fish_types')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#00897B"
                    class="text-prod"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-fish"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="#00897B" variant="tonal" size="small" rounded="lg" class="font-weight-medium">
                        <v-icon start size="13">mdi-fish</v-icon>
                        <span>{{ item.raw.rawTitle || item.title }}</span>
                        <v-badge
                          :content="item.raw.count"
                          inline
                          color="#00897B"
                          class="ml-1"
                        ></v-badge>
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title" :subtitle="item.raw.subtitle">
                        <template #prepend>
                          <v-avatar size="24" color="#00897B" variant="tonal" rounded="md" class="mr-2">
                            <v-icon size="14" color="#00897B">mdi-fish</v-icon>
                          </v-avatar>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
                            {{ item.raw.count }} {{ $t('in_table') }}
                          </v-chip>
                        </template>
                      </v-list-item>
                    </template>
                  </v-autocomplete>
                </v-col>
              </v-row>
            </div>
          </v-expand-transition>

          <!-- Contextual Sub-Filters: Guano Specifications -->
          <v-expand-transition>
            <div
              v-if="!advFilter.articleTypes?.length || advFilter.articleTypes.includes('GuanoType')"
              class="subfilter-card pa-3 rounded-lg mb-2"
              style="background: rgba(76, 175, 80, 0.04); border: 1px solid rgba(76, 175, 80, 0.18);"
            >
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="d-flex align-center ga-1 text-caption font-weight-bold text-success">
                  <v-icon size="15" color="success">mdi-leaf</v-icon>
                  <span>{{ $t('guano_filters') }}</span>
                  <v-chip size="x-small" color="success" variant="tonal" class="ml-1 font-weight-medium">
                    {{ guanoTypeOptions.length }} {{ $t('in_table') }}
                  </v-chip>
                </div>
              </div>
              <v-row dense>
                <v-col cols="12">
                  <v-autocomplete
                    v-model="advFilter.guanoTypes"
                    :items="guanoTypeOptions"
                    item-title="title"
                    item-value="value"
                    :label="$t('guano_type')"
                    :placeholder="$t('select_guano_types')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#00897B"
                    class="text-prod"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-leaf"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="success" variant="tonal" size="small" rounded="lg" class="font-weight-medium">
                        <v-icon start size="13">mdi-leaf</v-icon>
                        <span>{{ item.raw.rawTitle || item.title }}</span>
                        <v-badge :content="item.raw.count" inline color="success" class="ml-1"></v-badge>
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title" :subtitle="item.raw.subtitle">
                        <template #prepend>
                          <v-avatar size="24" color="success" variant="tonal" rounded="md" class="mr-2">
                            <v-icon size="14" color="success">mdi-leaf</v-icon>
                          </v-avatar>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="success" variant="tonal" class="font-weight-bold">
                            {{ item.raw.count }} {{ $t('in_table') }}
                          </v-chip>
                        </template>
                      </v-list-item>
                    </template>
                  </v-autocomplete>
                </v-col>
              </v-row>
            </div>
          </v-expand-transition>

          <!-- Contextual Sub-Filters: Packaging Specifications -->
          <v-expand-transition>
            <div
              v-if="!advFilter.articleTypes?.length || advFilter.articleTypes.includes('PackageType')"
              class="subfilter-card pa-3 rounded-lg mb-2"
              style="background: rgba(120, 144, 156, 0.06); border: 1px solid rgba(120, 144, 156, 0.2);"
            >
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="d-flex align-center ga-1 text-caption font-weight-bold text-blue-grey">
                  <v-icon size="15" color="blue-grey">mdi-package-variant-closed</v-icon>
                  <span>{{ $t('package_filters') }}</span>
                  <v-chip size="x-small" color="blue-grey" variant="tonal" class="ml-1 font-weight-medium">
                    {{ packageTypeOptions.length }} {{ $t('in_table') }}
                  </v-chip>
                </div>
              </div>
              <v-row dense>
                <v-col cols="12">
                  <v-autocomplete
                    v-model="advFilter.packageTypes"
                    :items="packageTypeOptions"
                    item-title="title"
                    item-value="value"
                    :label="$t('package_type')"
                    :placeholder="$t('select_package_types')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#00897B"
                    class="text-prod"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-package-variant"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="blue-grey" variant="tonal" size="small" rounded="lg" class="font-weight-medium">
                        <v-icon start size="13">mdi-package-variant</v-icon>
                        <span>{{ item.raw.rawTitle || item.title }}</span>
                        <v-badge :content="item.raw.count" inline color="blue-grey" class="ml-1"></v-badge>
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title" :subtitle="item.raw.subtitle">
                        <template #prepend>
                          <v-avatar size="24" color="blue-grey" variant="tonal" rounded="md" class="mr-2">
                            <v-icon size="14" color="blue-grey">mdi-package-variant</v-icon>
                          </v-avatar>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="blue-grey" variant="tonal" class="font-weight-bold">
                            {{ item.raw.count }} {{ $t('in_table') }}
                          </v-chip>
                        </template>
                      </v-list-item>
                    </template>
                  </v-autocomplete>
                </v-col>
              </v-row>
            </div>
          </v-expand-transition>

          <!-- Active Filter Chips Strip -->
          <div v-if="activeAdvFilterCount > 0" class="active-chips-strip d-flex align-center flex-wrap ga-1 pt-2 border-t mt-2">
            <span class="text-caption font-weight-medium text-medium-emphasis mr-1 d-flex align-center ga-1">
              <v-icon size="12">mdi-tag-multiple-outline</v-icon>
              {{ $t('active_advanced_filters') }}:
            </span>
            <v-chip
              v-for="at in (advFilter.articleTypes || [])"
              :key="'at_' + at"
              size="x-small"
              rounded="lg"
              color="#00897B"
              variant="flat"
              closable
              @click:close="removeAdvFilterItem('articleTypes', at)"
            >
              {{ at }}
            </v-chip>
            <v-chip
              v-for="wid in (advFilter.warehouses || [])"
              :key="'w_' + wid"
              size="x-small"
              rounded="lg"
              color="#00897B"
              variant="tonal"
              closable
              @click:close="removeAdvFilterItem('warehouses', wid)"
            >
              {{ warehouseOptions.find(w => w.value === wid)?.rawTitle || wid }}
            </v-chip>
            <v-chip
              v-for="fid in (advFilter.fishFamilies || [])"
              :key="'ff_' + fid"
              size="x-small"
              rounded="lg"
              color="#00897B"
              variant="tonal"
              closable
              @click:close="removeAdvFilterItem('fishFamilies', fid)"
            >
              {{ fishFamilyOptions.find(f => f.value === fid)?.rawTitle || fid }}
            </v-chip>
            <v-chip
              v-for="ff in (advFilter.fishFreshFrozen || [])"
              :key="'fff_' + ff"
              size="x-small"
              rounded="lg"
              color="#00897B"
              variant="tonal"
              closable
              @click:close="removeAdvFilterItem('fishFreshFrozen', ff)"
            >
              {{ ff === 'frozen' ? $t('frozen') : $t('fresh') }}
            </v-chip>
            <v-chip
              v-for="pid in (advFilter.fishProcessTypes || [])"
              :key="'fpt_' + pid"
              size="x-small"
              rounded="lg"
              color="#00897B"
              variant="tonal"
              closable
              @click:close="removeAdvFilterItem('fishProcessTypes', pid)"
            >
              {{ fishProcessTypeOptions.find(p => p.value === pid)?.rawTitle || pid }}
            </v-chip>
            <v-chip
              v-for="ftid in (advFilter.fishTypes || [])"
              :key="'ft_' + ftid"
              size="x-small"
              rounded="lg"
              color="#00897B"
              variant="tonal"
              closable
              @click:close="removeAdvFilterItem('fishTypes', ftid)"
            >
              <v-icon start size="11">mdi-fish</v-icon>
              {{ fishTypeOptions.find(ft => ft.value === ftid)?.rawTitle || ftid }}
            </v-chip>
            <v-chip
              v-for="gid in (advFilter.guanoTypes || [])"
              :key="'gt_' + gid"
              size="x-small"
              rounded="lg"
              color="success"
              variant="tonal"
              closable
              @click:close="removeAdvFilterItem('guanoTypes', gid)"
            >
              {{ guanoTypeOptions.find(g => g.value === gid)?.rawTitle || gid }}
            </v-chip>
            <v-chip
              v-for="ptid in (advFilter.packageTypes || [])"
              :key="'pt_' + ptid"
              size="x-small"
              rounded="lg"
              color="blue-grey"
              variant="tonal"
              closable
              @click:close="removeAdvFilterItem('packageTypes', ptid)"
            >
              {{ packageTypeOptions.find(p => p.value === ptid)?.rawTitle || ptid }}
            </v-chip>
          </div>
        </div>
      </v-expand-transition>

      <!-- Invoice Setup Expandable Panel -->
      <v-expand-transition>
        <div v-if="showInvoice&&uninvoicedSelectedProductions.length" class="inv-panel pa-3 mt-2 rounded-xl">
          <div class="d-flex justify-center mb-3">
            <v-btn-toggle v-model="invForm.invoiceKind" mandatory density="compact" rounded color="#00897B" variant="outlined">
              <v-btn value="supplier" size="small"><v-icon size="14" class="mr-1">mdi-truck-delivery</v-icon>{{ $t('supplier_invoices') }}</v-btn>
              <v-btn value="client" size="small"><v-icon size="14" class="mr-1">mdi-account-group</v-icon>{{ $t('client_invoices') }}</v-btn>
            </v-btn-toggle>
          </div>
          <v-row dense>
            <v-col cols="12" sm="6" md="3">
              <DatePicker v-model="invForm.date" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="teal" :disabled-dates="invDisabledDates" :min-date="invMinDate" :max-date="invMaxDate">
                <template #default="{ togglePopover, inputValue }">
                  <v-text-field :model-value="inputValue" :label="$t('invoice_date')" variant="outlined" density="compact" rounded color="#00897B" class="text-prod" hide-details readonly prepend-inner-icon="mdi-calendar" @click="togglePopover"></v-text-field>
                </template>
              </DatePicker>
            </v-col>
            <v-col cols="12" sm="6" md="3"><v-text-field :model-value="invoiceSuppliers.join(', ')" :label="invForm.invoiceKind === 'supplier' ? $t('supplier') : $t('client')" variant="outlined" density="compact" rounded color="#00897B" class="text-prod" hide-details readonly></v-text-field></v-col>
            <v-col cols="12" sm="6" md="3"><v-autocomplete v-model="invForm.type_invoice" :items="[{title:$t('local'),value:'local'},{title:$t('export_type'),value:'export'}]" :label="$t('invoice_type')" variant="outlined" density="compact" rounded color="#00897B" class="text-prod" hide-details @update:model-value="onTypeInvoiceChange"></v-autocomplete></v-col>
            <v-col cols="12" sm="6" md="3"><v-combobox v-model="invForm.trackingNumbers" :label="$t('invoice_tracking_number')" variant="outlined" density="compact" rounded color="#00897B" class="text-prod" hide-details multiple chips closable-chips :loading="invChecking" prepend-inner-icon="mdi-barcode" @update:model-value="onTrackingUpdate"></v-combobox></v-col>
          </v-row>
          <div class="d-flex align-center justify-space-between mt-3 flex-wrap ga-2">
            <div class="d-flex ga-4 text-body-2 flex-wrap"><span><b>{{ $t('invoice_weight') }}:</b> {{ main.formatDisplayNumber(uninvoicedSelectedProductions.reduce((s,c)=>s+Number(c.total_weight||0),0)) }} Kg</span><span><b>{{ $t('total_price') }}:</b> {{ main.formatDisplayNumber(uninvoicedSelectedProductions.reduce((s,c)=>s+Number(c.total_price||0),0)) }} {{ main.currency }}</span></div>
            <div class="d-flex ga-2">
              <v-btn color="error" variant="outlined" rounded size="small" @click="cancelInvoice" prepend-icon="mdi-close-circle-outline">{{ $t('cancel') }}</v-btn>
              <v-btn color="#00897B" variant="flat" rounded size="small" :disabled="!invForm.trackingNumbers.length" @click="submitInvoice" prepend-icon="mdi-content-save">{{ $t('save') }}</v-btn>
            </div>
          </div>
        </div>
      </v-expand-transition>
    </div>
    <!-- Active Multi-Sorts Strip -->
    <div v-if="colSorts && colSorts.length > 0" class="lp-active-sorts-strip d-flex align-center flex-wrap ga-1 px-3 py-1 mb-2 rounded-lg" style="background: rgba(0, 137, 123, 0.06); border: 1px dashed rgba(0, 137, 123, 0.3);">
      <span class="text-caption font-weight-bold d-flex align-center ga-1 text-prod mr-1">
        <v-icon size="14" color="#00897B">mdi-sort</v-icon>
        {{ $t('sorted_by') }}:
      </span>
      <v-chip
        v-for="(s, sIdx) in colSorts"
        :key="'sort_' + s.key"
        size="x-small"
        rounded="lg"
        color="#00897B"
        variant="flat"
        closable
        @click:close="removeColSort(s.key)"
        @click="toggleMultiSort(s.key)"
      >
        <span class="font-weight-bold mr-1">{{ sIdx + 1 }}.</span>
        <span>{{ getHeaderTitle(s.key) }}</span>
        <v-icon end size="12">{{ s.dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down' }}</v-icon>
      </v-chip>
      <v-btn
        size="x-small"
        variant="text"
        color="error"
        class="ml-1 text-caption font-weight-medium"
        @click="clearColSorts"
      >
        {{ $t('clear_sorts') }}
      </v-btn>
    </div>

    <!-- Table -->
    <div class="lp-table-wrap" ref="tableWrap" @scroll.passive="onTableScroll">
      <v-progress-linear v-if="isWorkerFiltering" indeterminate color="#00897B" height="2" class="lp-table-loader"></v-progress-linear>
      <table class="lp-table">
        <thead class="lp-thead"><tr>
          <th class="lp-th lp-th-pin" :style="colPinStyle('__checkbox', 3)"><v-checkbox-btn v-model="selectAll" color="#00897B" density="compact" hide-details></v-checkbox-btn></th>
          <th
            v-for="h in headers"
            :key="h.key"
            class="lp-th"
            :class="{'lp-th-pin': isPinned(h.key) || h.fixed, 'lp-th-dragover': dragOverCol === h.key}"
            :style="colPinStyle(h.key, 3)"
            :draggable="!h.fixed"
            @dragstart="onDragStart(h.key)"
            @dragover="onDragOver(h.key, $event)"
            @dragend="onDragEnd"
            @drop="onDragEnd"
          >
            <!-- Column Resizer Handle -->
            <div
              v-if="!h.fixed"
              class="lp-col-resizer"
              :class="{ 'lp-col-resizer-active': activeResizeCol === h.key }"
              @mousedown.stop.prevent="startColResize(h.key, $event)"
              :title="$t('resize_column')"
            ></div>

            <div class="lp-th-inner">
              <v-icon v-if="!h.fixed" size="12" class="drag-handle mr-1" color="grey-lighten-1">mdi-drag-vertical</v-icon>
              <span
                class="lp-th-title text-truncate"
                @click="h.sortable !== false && toggleSort(h.key, $event)"
                :title="$t('multi_sort_hint')"
              >
                {{ h.title }}
              </span>

              <template v-if="h.key !== 'actions' && h.key !== 'expand'">
                <!-- Sort Arrow Button -->
                <v-btn
                  v-if="sortDir(h.key)"
                  icon
                  variant="text"
                  size="14"
                  @click.stop="toggleMultiSort(h.key)"
                  :title="sortDir(h.key) === 'asc' ? 'ASC (Click to toggle)' : 'DESC (Click to clear)'"
                >
                  <v-icon size="12" color="#00897B">mdi-arrow-{{ sortDir(h.key) === 'asc' ? 'up' : 'down' }}</v-icon>
                </v-btn>

                <!-- Priority Badge for Multi-Sort -->
                <span v-if="sortIdx(h.key) && colSorts && colSorts.length > 1" class="sort-badge-p">
                  {{ sortIdx(h.key) }}
                </span>

                <!-- Column Values Filter Menu -->
                <TableColumnFilter
                  v-model="colFilters[h.key]"
                  :options="filterOpts(h.key)"
                  :counts="filterCounts(h.key)"
                  :title="h.title"
                  color="#00897B"
                  @change="onColFilterChange"
                />

                <!-- Modern Pin / Unpin Menu -->
                <v-menu v-if="!h.fixed" location="bottom end" :close-on-content-click="true">
                  <template #activator="{ props: pinMenuProps }">
                    <v-btn
                      v-bind="pinMenuProps"
                      icon
                      variant="text"
                      size="14"
                      class="lp-th-pin-btn"
                      :title="$t('pin_column')"
                      @click.stop
                    >
                      <v-icon
                        size="11"
                        :color="pinSide(h.key) === 'left' ? '#00897B' : pinSide(h.key) === 'right' ? '#FF7043' : 'grey-lighten-1'"
                      >
                        {{ pinSide(h.key) === 'left' ? 'mdi-dock-left' : pinSide(h.key) === 'right' ? 'mdi-dock-right' : 'mdi-pin-outline' }}
                      </v-icon>
                    </v-btn>
                  </template>
                  <v-list density="compact" class="py-1 rounded-lg" elevation="6">
                    <v-list-item density="compact" :active="pinSide(h.key) === 'left'" @click="setPin(h.key, 'left')">
                      <template #prepend><v-icon size="15" color="#00897B">mdi-dock-left</v-icon></template>
                      <v-list-item-title class="text-caption font-weight-medium">{{ $t('pin_left') }}</v-list-item-title>
                    </v-list-item>
                    <v-list-item density="compact" :active="pinSide(h.key) === 'right'" @click="setPin(h.key, 'right')">
                      <template #prepend><v-icon size="15" color="#FF7043">mdi-dock-right</v-icon></template>
                      <v-list-item-title class="text-caption font-weight-medium">{{ $t('pin_right') }}</v-list-item-title>
                    </v-list-item>
                    <v-divider v-if="pinSide(h.key)" class="my-1"></v-divider>
                    <v-list-item v-if="pinSide(h.key)" density="compact" @click="setPin(h.key, 'none')">
                      <template #prepend><v-icon size="15" color="grey">mdi-pin-off-outline</v-icon></template>
                      <v-list-item-title class="text-caption text-error">{{ $t('unpin') }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </template>
            </div>
          </th>
        </tr></thead>
        <tbody>
          <template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="#00897B"></v-progress-circular></td></tr></template>
          <template v-else-if="colFilteredItems.length === 0"><tr><td :colspan="headers.length+1" class="text-center pa-8 text-grey">{{ $t('no_data_available') }}</td></tr></template>
          <template v-else>
            <tr v-if="virtualTopHeight > 0" class="virtual-spacer-row" :style="{ height: virtualTopHeight + 'px' }">
              <td :colspan="headers.length + 1" :style="{ height: virtualTopHeight + 'px', padding: 0, border: 'none' }"></td>
            </tr>
            <template v-for="item in visibleItems" :key="item.id">
            <tr class="lp-row" :class="{'lp-row-expanded':expanded[item.id],'lp-row-invoiced':item._is_invoiced}">
              <td class="lp-td lp-th-pin" :style="colPinStyle('__checkbox', 2)"><v-checkbox-btn v-model="selected" :value="item.id" color="#00897B" density="compact" hide-details></v-checkbox-btn></td>
              <td v-for="h in headers" :key="h.key" class="lp-td" :class="{'lp-col-pin': isPinned(h.key) || h.fixed}" :style="colPinStyle(h.key, 2)">
                <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                <template v-else-if="h.key==='production_category_id'"><v-chip size="small" color="#00897B" variant="tonal">{{ catName(item.production_category_id) }}</v-chip></template>
                <template v-else-if="h.key==='production_type'"><v-chip v-if="item.production_type" size="x-small" :color="productionTypeColor(item.production_type)" variant="tonal">{{ $t(item.production_type) }}</v-chip><span v-else class="text-grey">-</span></template>
                <template v-else-if="h.key==='_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.production_tracking_number||[])" :key="tn" size="x-small" color="deep-purple" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.production_tracking_number||[]).length" class="text-grey">-</span></div></template>
                <template v-else-if="h.key==='_charge'"><v-chip v-if="item.charge" size="x-small" color="#00897B" variant="tonal" @click="$router.push({name:'view_charge',params:{id:item.charge.id}})"><v-icon size="12" class="mr-1">mdi-link</v-icon>{{ item._charge }}</v-chip><span v-else class="text-grey">-</span></template>
                <template v-else-if="h.key==='total_weight'">{{ main.formatDisplayNumber(item.total_weight) }} Kg</template>
                <template v-else-if="h.key==='total_price'">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</template>
                <template v-else-if="h.key==='actions'">
                  <v-btn icon size="x-small" variant="tonal" color="#00897B" @click="$router.push({name:'view_production',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-eye</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('production.edit')" icon size="x-small" variant="tonal" color="warning" @click="$router.push({name:'edit_production',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-pencil</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('production.delete')" icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item.id)" class="mr-1"><v-icon size="14">mdi-delete</v-icon></v-btn>
                  <v-btn icon size="x-small" variant="tonal" color="blue-grey" @click="openAuditLog(item.id)"><v-icon size="14">mdi-history</v-icon><v-tooltip activator="parent" location="top">{{ $t('audit_log') }}</v-tooltip></v-btn>
                  <v-btn icon size="x-small" variant="tonal" :color="(item._open_issues||0) > 0 ? 'error' : '#00897B'" @click="openIssueDialog(item.id)" class="ml-1"><v-badge v-if="(item._open_issues||0) > 0" :content="item._open_issues" color="error" floating><v-icon size="14">mdi-flag-outline</v-icon></v-badge><v-icon v-else size="14">mdi-flag-outline</v-icon><v-tooltip activator="parent" location="top">{{ $t('record_issues') }}</v-tooltip></v-btn>
                </template>
                <template v-else-if="h.key==='_invoice_state'">
                  <v-chip v-if="item._is_invoiced && item._invoice_type === 'supplier'" size="x-small" color="orange-darken-4" variant="tonal" prepend-icon="mdi-truck-delivery">{{ item._invoice_state }}</v-chip>
                  <v-chip v-else-if="item._is_invoiced && item._invoice_type === 'client'" size="x-small" color="cyan-darken-3" variant="tonal" prepend-icon="mdi-account-group">{{ item._invoice_state }}</v-chip>
                  <v-chip v-else size="x-small" color="grey" variant="tonal" prepend-icon="mdi-close-circle-outline">{{ $t('not_invoiced') }}</v-chip>
                </template>
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
            <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
              <div class="lp-expand-content">
                <div v-for="(grp,gi) in groupDetails(item)" :key="gi" class="lp-detail-group mb-2">
                  <div class="lp-detail-group-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-tag</v-icon><b>{{ grp.category?grp.category[`name_${main.lang?.code}`]||grp.category.name:'-' }}</b></div>
                  <table class="lp-detail-table"><thead><tr><th>{{ $t('classification') }}</th><th>{{ $t('product') }}</th><th>{{ $t('product_family') }}</th><th>{{ $t('warehouse') }}</th><th>{{ $t('total_weight') }}</th><th>{{ $t('unit_price') }}</th><th>{{ $t('total_price') }}</th><th>{{ $t('secondary_packaging_cost') }}</th><th>{{ $t('package_type') }}</th><th>{{ $t('total_package_count') }}</th></tr></thead>
                  <tbody><tr v-for="dc in grp.items" :key="dc.id">
                    <td><v-chip size="x-small" color="#00897B" variant="tonal">{{ dc.stockable_type||$t('text') }}</v-chip></td>
                    <td class="font-weight-bold">{{ articleName(dc) }}</td><td>{{ articleFamily(dc) }}</td>
                    <td><span v-if="dc.warehouse">{{ dc.warehouse[`name_${main.lang?.code}`]||dc.warehouse.name }}</span><span v-else>{{ $t('non_stockable') }}</span></td>
                    <td>{{ main.formatDisplayNumber(dc.weight) }}</td><td>{{ main.formatDisplayNumber(dc.unit_price) }}</td><td>{{ main.formatDisplayNumber(dc.total_price) }}</td>
                    <td><span v-if="dc.secondary_packaging_cost > 0" class="font-weight-bold text-blue-grey">{{ main.formatDisplayNumber(dc.secondary_packaging_cost) }} {{ main.currency }}</span><span v-else class="text-grey">-</span></td>
                    <td>{{ dc.package_type?dc.package_type.name:'N/A' }}</td><td>{{ dc.nbr_box||'-' }}</td>
                  </tr></tbody>
                  <tfoot><tr class="lp-detail-foot"><td colspan="4" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalW) }}</td><td></td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalP) }}</td><td colspan="3"></td></tr></tfoot>
                  </table>
                </div>
              </div>
            </td></tr>
            </template>
            <tr v-if="virtualBottomHeight > 0" class="virtual-spacer-row" :style="{ height: virtualBottomHeight + 'px' }">
              <td :colspan="headers.length + 1" :style="{ height: virtualBottomHeight + 'px', padding: 0, border: 'none' }"></td>
            </tr>
          </template>
        </tbody>
        <tfoot class="lp-tfoot"><tr><td :style="colPinStyle('__checkbox', 2)"></td><td v-for="h in headers" :key="'foot-'+h.key" :style="colPinStyle(h.key, 2)" class="text-center font-weight-bold"><template v-if="h.key==='total_weight'">Σ {{ main.formatDisplayNumber(totalWeight) }} Kg</template><template v-else-if="h.key==='total_price'">Σ {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</template></td></tr></tfoot>
      </table>
    </div>
  </v-card-text>
  <!-- TAB: Add Production -->
  <v-card-text v-else-if="activeTab === 1" class="pa-0">
    <AddProduction />
  </v-card-text>
  <v-dialog v-model="deleteDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ deleteBulk?$t('confirm_bulk_delete_message',{count:selected.length}):$t('confirm_delete_production_message') }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions></v-card></v-dialog>
</v-card>
<AuditLogDialog v-model="auditLogDialogOpen" auditable-type="Production" :auditable-id="auditLogRecordId" module-color="#00897B" />
<RecordIssueDialog v-model="issueDialogOpen" record-type="Production" :record-id="issueDialogRecordId" module-color="#00897B" @issue-changed="fetchProductions" />

<!-- Summary Snackbar with Countdown Progress Bar (Rule 5 & AddCharge style) -->
<v-snackbar
  v-model="summarySnackbar.show"
  :timeout="6000"
  location="top right"
  color="surface"
  rounded="lg"
  elevation="12"
  min-width="340"
  max-width="420"
  @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
>
  <v-progress-linear :model-value="snackbarProgress" color="#00897B" height="3" class="mb-2" rounded></v-progress-linear>
  <div class="d-flex align-center mb-1">
    <v-icon :color="summarySnackbar.color || 'success'" class="mr-2">
      {{ summarySnackbar.color === 'error' ? 'mdi-alert-circle' : (summarySnackbar.color === 'info' ? 'mdi-information' : 'mdi-check-circle') }}
    </v-icon>
    <span class="text-subtitle-2 font-weight-bold">{{ summarySnackbar.title }}</span>
  </div>
  <div v-if="summarySnackbar.detail" class="text-caption text-medium-emphasis ms-7">
    {{ summarySnackbar.detail }}
  </div>
  <template #actions>
    <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
  </template>
</v-snackbar>
</template>
<script>
import script from './script.js';
import virtualTableMixin from '@/utils/virtualTableMixin.js';
import tableConfigMixin from '@/utils/tableConfigMixin.js';
import AddProduction from '../AddProduction/index.vue';
import AuditLogDialog from '@/components/shared/AuditLogDialog.vue';
import RecordIssueDialog from '@/components/shared/RecordIssueDialog.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import TableColumnFilter from '@/components/common/TableColumnFilter.vue';
import { useAuth } from '@/stores/Auth';
export default {
  ...script,
  mixins: [virtualTableMixin, tableConfigMixin],
  components:{...script.components,AddProduction,AuditLogDialog,RecordIssueDialog,SegmentedPillToggle,TableColumnFilter},
  data(){
    return {
      ...script.data.call(this),
      activeTab: 0,
      auditLogDialogOpen: false,
      auditLogRecordId: 0,
      issueDialogOpen: false,
      issueDialogRecordId: 0,
      productionTabs: [
        { value: 0, label: 'list_production', icon: 'mdi-view-list-outline' },
        ...(useAuth().hasPermission('production.create') ? [{ value: 1, label: 'add_production', icon: 'mdi-sticker-plus-outline' }] : [])
      ]
    };
  },
  computed:{
    ...script.computed,
    auth(){return useAuth()},
    selectAll:{get(){return this.selected.length===this.colFilteredItems.length&&this.colFilteredItems.length>0},set(v){this.selected=v?this.colFilteredItems.map(i=>i.id):[]}},
  },
  methods:{
    ...script.methods,
    openAuditLog(id){this.auditLogRecordId=id;this.auditLogDialogOpen=true},
    openIssueDialog(id){this.issueDialogRecordId=id;this.issueDialogOpen=true}
  },
  watch:{...script.watch,activeTab(v){const q=v===1?{tab:'add'}:{};if(JSON.stringify(this.$route.query)!==JSON.stringify(q))this.$router.replace({query:q}).catch(()=>{});if(v===0){this.fetchProductions();this.fetchAnalytics()}},showInvoice(v){if(v&&this.uninvoicedSelectedProductions.length){this.fetchNextTracking(this.invForm.type_invoice||'local')}}},
  mounted(){script.mounted.call(this);if(this.loadTableConfig)this.loadTableConfig();if(this.$route.query.tab==='add')this.activeTab=1},
  beforeUnmount(){if(script.beforeUnmount)script.beforeUnmount.call(this)},
};
</script>
<style scoped>
.virtual-spacer-row, .virtual-spacer-row td {
  padding: 0 !important;
  margin: 0 !important;
  border: none !important;
  line-height: 0 !important;
  font-size: 0 !important;
  background: transparent !important;
  pointer-events: none;
}
.lp-table-loader {
  position: sticky;
  top: 0;
  z-index: 6;
}
.lp-card{border-radius:20px!important;font-family:'Inter',sans-serif}
.lp-tab-toolbar{background:rgba(var(--v-theme-surface),.95);border:1px solid rgba(0,137,123,.2)!important;box-shadow:0 2px 8px rgba(0,105,92,.04)}
.lp-segmented-toggle{background:rgba(var(--v-theme-on-surface),.02)}
.lp-hero{background:linear-gradient(135deg,rgb(0, 105, 92) 0%,rgb(0, 137, 123) 40%,rgb(38, 166, 154) 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;min-height:100px}
.lp-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.lp-hero-title{font-size:19px;font-weight:800}
.lp-hero-actions{display:flex;gap:8px;flex-wrap:wrap}
.lp-hero-kpi{display:flex;flex-direction:column;align-items:flex-start;padding:6px 14px;background:rgba(255,255,255,.14);backdrop-filter:blur(8px);border-radius:12px;border:1px solid rgba(255,255,255,.2);min-width:90px}
.lp-kpi-val{font-size:14px;font-weight:800;line-height:1.2;color:#ffffff}
.lp-kpi-lbl{font-size:10px;text-transform:uppercase;letter-spacing:.5px;opacity:.85;color:#e0f2f1}
.lp-toolbar{background:rgba(var(--v-theme-surface),.95);border:1px solid rgba(0,137,123,.15);box-shadow:0 2px 12px rgba(0,105,92,.04)}
.lp-search-field{max-width:320px}
.lp-filter-btn{font-weight:600;letter-spacing:.3px}
.adv-panel{border:1.5px solid rgba(0,137,123,.15);background:rgba(0,137,123,.03)}
.inv-panel{border:1.5px solid rgba(0,150,136,.15);background:rgba(0,150,136,.03)}
.lp-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.lp-table{width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed}
.lp-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.lp-th{position:relative;padding:8px 8px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s;overflow:hidden;box-sizing:border-box}
.lp-td{padding:8px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;box-sizing:border-box}
.lp-col-resizer{position:absolute;top:0;right:0;width:6px;height:100%;cursor:col-resize;user-select:none;z-index:6;background:transparent;transition:background .15s}
.lp-col-resizer:hover,.lp-col-resizer-active{background:#00897B!important;width:3px!important}
.lp-th-pin-btn{opacity:.65;transition:opacity .15s}.lp-th-pin-btn:hover{opacity:1}
.lp-active-sorts-strip{font-family:'Inter',sans-serif}
.lp-col-pin{position:sticky!important;z-index:2!important;background:#ffffff!important}
.lp-th-pin{position:sticky!important;z-index:5!important;background:#f8fafc!important}
:deep(.v-theme--dark) .lp-col-pin,
:deep(.v-theme--dark) .lp-th-pin,
.v-theme--dark .lp-col-pin,
.v-theme--dark .lp-th-pin {
  background: #1e293b !important;
  color: #f1f5f9 !important;
}
.lp-row:hover{background:rgba(0,137,123,.04)}.lp-row-expanded{background:rgba(0,137,123,.06)}
.lp-row-invoiced{background:rgba(76,175,80,.08)!important}
.lp-row-invoiced:hover{background:rgba(76,175,80,.14)!important}
.lp-tfoot td{padding:10px 12px;border-top:2px solid rgba(0,137,123,.2);color:#00897B;font-size:14px}
:deep(.v-theme--dark) .lp-tfoot td,
.v-theme--dark .lp-tfoot td {
  color: #26A69A !important;
}
.lp-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.lp-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow:hidden}
.lp-detail-group-header{padding:8px 14px;background:rgba(0,137,123,.06);font-size:13px}
.lp-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.lp-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.lp-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.lp-detail-foot td{padding:8px 10px;background:rgba(0,137,123,.04);border-top:1px solid rgba(0,137,123,.15)}
.lp-panel-card{border:1.5px solid rgba(0,137,123,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.lp-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.lp-stats-panel{max-height:400px}
.lp-stat-item{padding:8px 10px;border-radius:10px;background:rgba(0,137,123,.03);border:1px solid rgba(0,137,123,.06)}
:deep(.mdi-pallet:before),.mdi-pallet:before{content:"\F184E"}
</style>

