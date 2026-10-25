<template>
<v-card class="lc-card" elevation="0">
  <!-- Hero -->
  <div class="lc-hero">
    <div class="lc-hero-inner">
      <div class="d-flex align-center ga-4">
        <div class="lc-hero-icon"><v-icon size="28" color="white">mdi-truck-cargo-container</v-icon></div>
        <div>
          <div class="lc-hero-title">{{ $t('charges') }}</div>
          <div class="text-white text-caption" style="opacity:.8">{{ $t('datatable_charges') }}</div>
        </div>
      </div>

      <!-- Live KPI Badges -->
      <div class="d-flex align-center ga-2 flex-wrap">
        <div class="lc-hero-kpi">
          <v-icon size="14" color="white" class="mr-1">mdi-counter</v-icon>
          <span class="lc-kpi-val">{{ colFilteredItems.length }}</span>
          <span class="lc-kpi-lbl">{{ $t('records') }}</span>
        </div>
        <div class="lc-hero-kpi">
          <v-icon size="14" color="white" class="mr-1">mdi-weight-kilogram</v-icon>
          <span class="lc-kpi-val">{{ main.formatDisplayNumber(totalWeight) }}</span>
          <span class="lc-kpi-lbl">Kg</span>
        </div>
        <div class="lc-hero-kpi">
          <v-icon size="14" color="white" class="mr-1">mdi-cash</v-icon>
          <span class="lc-kpi-val">{{ main.formatDisplayNumber(totalPrice) }}</span>
          <span class="lc-kpi-lbl">{{ main.currency }}</span>
        </div>
        <div class="lc-hero-kpi" v-if="totalInvoiced > 0">
          <v-icon size="14" color="#81C784" class="mr-1">mdi-check-decagram</v-icon>
          <span class="lc-kpi-val">{{ totalInvoiced }}</span>
          <span class="lc-kpi-lbl">{{ $t('invoiced') }}</span>
        </div>
      </div>

      <div class="lc-hero-actions">
        <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchCharges" :loading="loading">{{ $t('refresh') }}</v-btn>
        <v-btn v-if="selected.length && auth.hasPermission('charges.delete')" variant="tonal" color="error" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete_selected') }} ({{ selected.length }})</v-btn>
      </div>
    </div>
  </div>

  <!-- Tab Slider (Matching Activity Breakdown Entity Toggle) -->
  <div class="d-flex align-center justify-space-between mx-4 mt-3">
    <SegmentedPillToggle
      v-model="activeTab"
      :items="chargeTabs.map(t => ({ value: t.value, label: $t(t.label), icon: t.icon }))"
      color="#1976D2"
    />
  </div>

  <!-- TAB: List Charge -->
  <v-card-text v-if="activeTab === 0" class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="12" md="3">
        <div class="lc-panel-card">
          <div class="lc-panel-header"><v-icon size="16" color="#42A5F5" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="blue" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="12" md="6">
        <div class="lc-panel-card" style="height:100%">
          <div class="lc-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#42A5F5" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#42A5F5" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries[0].data.length" type="line" :height="260" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="12" md="3">
        <div class="lc-panel-card lc-stats-panel" style="height:100%;overflow-y:auto">
          <div class="lc-panel-header"><v-icon size="16" color="#42A5F5" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <!-- Per-category stats -->
          <div v-for="(stat,si) in categoryStats" :key="si" class="lc-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="#42A5F5" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#42A5F5" bg-color="blue-lighten-4" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="#42A5F5">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color: #42A5F5">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="teal">mdi-scale-balance</v-icon><span class="text-medium-emphasis">{{ $t('total_weight_invoice') }}:</span><b class="text-teal">{{ main.formatDisplayNumber(stat.weightInvoice) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <!-- Invoice stats -->
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-2"><v-icon size="14" color="success">mdi-check-decagram</v-icon><span class="text-caption text-medium-emphasis">{{ $t('invoiced') }}:</span><v-chip size="x-small" color="success" variant="tonal"><b>{{ totalInvoiced }}</b></v-chip></div>
            <div class="d-flex align-center ga-2"><v-icon size="14" color="grey">mdi-close-circle-outline</v-icon><span class="text-caption text-medium-emphasis">{{ $t('not_invoiced') }}:</span><v-chip size="x-small" color="grey" variant="tonal"><b>{{ totalNotInvoiced }}</b></v-chip></div>
          </div>
          <!-- Totals -->
          <v-divider class="my-2"></v-divider>
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#42A5F5">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b style="color: #42A5F5" class="text-caption">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="teal">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight_invoice') }}:</span><b class="text-teal text-caption">{{ main.formatDisplayNumber(totalWeightInvoice) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Unified Command & Filter Toolbar -->
    <div class="lc-toolbar mb-3 pa-2 rounded-xl">
      <v-row dense align="center">
        <!-- Quick Search Field -->
        <v-col cols="12" md="4" sm="6">
          <v-text-field
            v-model="searchQuery"
            density="compact"
            variant="outlined"
            rounded
            hide-details
            clearable
            color="#42A5F5"
            class="text-charge"
            prepend-inner-icon="mdi-magnify"
            :placeholder="$t('search_records_placeholder') || 'Search tracking #, supplier, origin...'"
          ></v-text-field>
        </v-col>

        <!-- Filter Toggle & Invoice Workflow -->
        <v-col cols="12" md="5" sm="6" class="d-flex align-center ga-2 flex-wrap">
          <v-btn
            :variant="showAdvanced ? 'flat' : 'tonal'"
            :color="showAdvanced ? '#42A5F5' : undefined"
            rounded
            size="small"
            class="lc-filter-btn"
            @click="showAdvanced = !showAdvanced"
          >
            <v-icon size="16" class="mr-1">{{ showAdvanced ? 'mdi-chevron-up' : 'mdi-filter-variant' }}</v-icon>
            {{ $t('advanced_filters') }}
            <v-badge
              v-if="activeFilterCount"
              :content="activeFilterCount"
              color="#FF7043"
              inline
              class="ml-1"
            ></v-badge>
          </v-btn>

          <v-btn
            :variant="showInvoice ? 'flat' : 'tonal'"
            :color="showInvoice ? '#00897B' : undefined"
            rounded
            size="small"
            class="lc-filter-btn"
            :disabled="!canSetupInvoice"
            @click="showInvoice = !showInvoice"
          >
            <v-icon size="16" class="mr-1">mdi-receipt-text-outline</v-icon>
            {{ $t('supplier_invoices') }}
            <v-badge
              v-if="uninvoicedSelectedCharges.length"
              :content="uninvoicedSelectedCharges.length"
              color="#00897B"
              inline
              class="ml-1"
            ></v-badge>
          </v-btn>

          <!-- Expand / Collapse All Details Button -->
          <v-btn
            :variant="isAllExpanded ? 'flat' : 'tonal'"
            :color="isAllExpanded ? 'warning' : '#42A5F5'"
            rounded
            size="small"
            class="lc-filter-btn"
            @click="toggleExpandAll"
          >
            <v-icon size="16" class="mr-1">{{ isAllExpanded ? 'mdi-arrow-collapse-vertical' : 'mdi-arrow-expand-vertical' }}</v-icon>
            {{ isAllExpanded ? $t('collapse_all') : $t('expand_all') }}
            <v-badge
              v-if="expandedCount > 0"
              :content="expandedCount"
              :color="isAllExpanded ? 'warning' : '#42A5F5'"
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
          <v-chip size="small" variant="tonal" color="#42A5F5" class="font-weight-medium">
            {{ colFilteredItems.length }} / {{ charges.length }} {{ $t('records') }}
          </v-chip>

          <!-- Table Settings Menu -->
          <v-menu v-model="tableConfigMenu" :close-on-content-click="false" location="bottom end" width="300">
            <template #activator="{ props: menuProps }">
              <v-btn
                v-bind="menuProps"
                icon
                variant="tonal"
                color="#42A5F5"
                size="small"
                :title="$t('table_settings')"
              >
                <v-icon size="18">mdi-table-cog</v-icon>
              </v-btn>
            </template>
            <v-card class="rounded-xl pa-3" elevation="8">
              <div class="d-flex align-center justify-space-between mb-2 pb-2 border-b">
                <div class="d-flex align-center ga-1 font-weight-bold text-caption text-charge">
                  <v-icon size="16" color="#42A5F5">mdi-table-cog</v-icon>
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
                      :color="pinSide(h.key) === 'left' ? '#42A5F5' : 'grey'"
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

      <!-- Advanced Filters Expandable Panel -->
      <v-expand-transition>
        <div v-if="showAdvanced" class="adv-panel pa-4 mt-2 rounded-xl">
          <!-- Advanced Filter Header -->
          <div class="d-flex align-center justify-space-between mb-3 pb-2 border-b">
            <div class="d-flex align-center ga-2">
              <v-avatar size="28" color="#42A5F5" variant="tonal" rounded="lg">
                <v-icon size="16" color="#42A5F5">mdi-filter-cog-outline</v-icon>
              </v-avatar>
              <div>
                <span class="text-subtitle-2 font-weight-bold text-charge">{{ $t('advanced_filters') }}</span>
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
                color="#42A5F5"
                class="text-charge"
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
                    color="#42A5F5"
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
                      <v-icon size="18" color="#42A5F5" class="mr-2">{{ item.raw.icon }}</v-icon>
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
                color="#42A5F5"
                class="text-charge"
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
                    color="#42A5F5"
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
              style="background: rgba(66, 165, 245, 0.04); border: 1px solid rgba(66, 165, 245, 0.18);"
            >
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="d-flex align-center ga-1 text-caption font-weight-bold text-charge">
                  <v-icon size="15" color="#42A5F5">mdi-fish</v-icon>
                  <span>{{ $t('fish_filters') }}</span>
                  <v-chip size="x-small" color="#42A5F5" variant="tonal" class="ml-1 font-weight-medium">
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
                    color="#42A5F5"
                    class="text-charge"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-family-tree"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="#42A5F5" variant="tonal" size="small" rounded="lg">
                        <v-icon start size="13">mdi-family-tree</v-icon>
                        {{ item.raw.rawTitle || item.title }}
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title">
                        <template #prepend>
                          <v-icon size="16" color="#42A5F5" class="mr-2">mdi-family-tree</v-icon>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-bold">
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
                    color="#42A5F5"
                    class="text-charge"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-snowflake"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="#42A5F5" variant="tonal" size="small" rounded="lg">
                        <v-icon start size="13">{{ item.raw.icon || 'mdi-snowflake' }}</v-icon>
                        {{ item.raw.rawTitle || item.title }}
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title">
                        <template #prepend>
                          <v-icon size="16" color="#42A5F5" class="mr-2">{{ item.raw.icon || 'mdi-snowflake' }}</v-icon>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-bold">
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
                    color="#42A5F5"
                    class="text-charge"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-cog-outline"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="#42A5F5" variant="tonal" size="small" rounded="lg">
                        <v-icon start size="13">mdi-cog-outline</v-icon>
                        {{ item.raw.rawTitle || item.title }}
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title">
                        <template #prepend>
                          <v-icon size="16" color="#42A5F5" class="mr-2">mdi-cog-outline</v-icon>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-bold">
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
                    color="#42A5F5"
                    class="text-charge"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    prepend-inner-icon="mdi-fish"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" color="#42A5F5" variant="tonal" size="small" rounded="lg" class="font-weight-medium">
                        <v-icon start size="13">mdi-fish</v-icon>
                        <span>{{ item.raw.rawTitle || item.title }}</span>
                        <v-badge
                          :content="item.raw.count"
                          inline
                          color="#42A5F5"
                          class="ml-1"
                        ></v-badge>
                      </v-chip>
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props" :title="item.raw.rawTitle || item.title" :subtitle="item.raw.subtitle">
                        <template #prepend>
                          <v-avatar size="24" color="#42A5F5" variant="tonal" rounded="md" class="mr-2">
                            <v-icon size="14" color="#42A5F5">mdi-fish</v-icon>
                          </v-avatar>
                        </template>
                        <template #append>
                          <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-bold">
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
                    color="#42A5F5"
                    class="text-charge"
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
                    color="#42A5F5"
                    class="text-charge"
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
              color="#42A5F5"
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
              color="#42A5F5"
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
              color="#42A5F5"
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
              color="#42A5F5"
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
              color="#42A5F5"
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
              color="#42A5F5"
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
        <div v-if="showInvoice&&uninvoicedSelectedCharges.length" class="inv-panel pa-3 mt-2 rounded-xl">
          <v-row dense>
            <v-col cols="12" sm="6" md="3">
              <DatePicker v-model="invForm.date" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="teal" :disabled-dates="invDisabledDates" :min-date="invMinDate" :max-date="invMaxDate">
                <template #default="{ togglePopover, inputValue }">
                  <v-text-field :model-value="inputValue" :label="$t('invoice_date')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details readonly prepend-inner-icon="mdi-calendar" @click="togglePopover"></v-text-field>
                </template>
              </DatePicker>
            </v-col>
            <v-col cols="12" sm="6" md="3"><v-text-field :model-value="invoiceSuppliers.join(', ')" :label="$t('supplier')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details readonly></v-text-field></v-col>
            <v-col cols="12" sm="6" md="3"><v-autocomplete v-model="invForm.type_invoice" :items="[{title:$t('local'),value:'local'},{title:$t('export_type'),value:'export'}]" :label="$t('invoice_type')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details @update:model-value="onTypeInvoiceChange"></v-autocomplete></v-col>
            <v-col cols="12" sm="6" md="3"><v-combobox v-model="invForm.trackingNumbers" :label="$t('invoice_tracking_number')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details multiple chips closable-chips :loading="invChecking" prepend-inner-icon="mdi-barcode" @update:model-value="onTrackingUpdate"></v-combobox></v-col>
          </v-row>
          <div class="d-flex align-center justify-space-between mt-3 flex-wrap ga-2">
            <div class="d-flex ga-4 text-body-2 flex-wrap"><span><b>{{ $t('invoice_weight') }}:</b> {{ main.formatDisplayNumber(uninvoicedSelectedCharges.reduce((s,c)=>s+Number(c.total_weight||0),0)) }} Kg</span><span><b>{{ $t('total_price') }}:</b> {{ main.formatDisplayNumber(uninvoicedSelectedCharges.reduce((s,c)=>s+Number(c.total_price||0),0)) }} {{ main.currency }}</span></div>
            <div class="d-flex ga-2">
              <v-btn color="error" variant="outlined" rounded size="small" @click="cancelInvoice" prepend-icon="mdi-close-circle-outline">{{ $t('cancel') }}</v-btn>
              <v-btn color="teal" variant="flat" rounded size="small" :disabled="!invForm.trackingNumbers.length" @click="submitInvoice" prepend-icon="mdi-content-save">{{ $t('save') }}</v-btn>
            </div>
          </div>
        </div>
      </v-expand-transition>
    </div>

    <!-- Active Multi-Sorts Strip -->
    <div v-if="colSorts && colSorts.length > 0" class="lc-active-sorts-strip d-flex align-center flex-wrap ga-1 px-3 py-1 mb-2 rounded-lg" style="background: rgba(66, 165, 245, 0.06); border: 1px dashed rgba(66, 165, 245, 0.3);">
      <span class="text-caption font-weight-bold d-flex align-center ga-1 text-charge mr-1">
        <v-icon size="14" color="#42A5F5">mdi-sort</v-icon>
        {{ $t('sorted_by') }}:
      </span>
      <v-chip
        v-for="(s, sIdx) in colSorts"
        :key="'sort_' + s.key"
        size="x-small"
        rounded="lg"
        color="#42A5F5"
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

    <!-- Data Table -->
    <div class="lc-table-wrap" ref="tableWrap" @scroll.passive="onTableScroll">
      <v-progress-linear v-if="isWorkerFiltering" indeterminate color="#42A5F5" height="2" class="lc-table-loader"></v-progress-linear>
      <table class="lc-table">
        <thead class="lc-thead"><tr>
          <th class="lc-th lc-th-pin" :style="colPinStyle('__checkbox', 3)"><v-checkbox-btn v-model="selectAll" color="#42A5F5" density="compact" hide-details></v-checkbox-btn></th>
          <th
            v-for="h in headers"
            :key="h.key"
            class="lc-th"
            :class="{'lc-th-pin': isPinned(h.key) || h.fixed, 'lc-th-dragover': dragOverCol === h.key}"
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
              class="lc-col-resizer"
              :class="{ 'lc-col-resizer-active': activeResizeCol === h.key }"
              @mousedown.stop.prevent="startColResize(h.key, $event)"
              :title="$t('resize_column')"
            ></div>

            <div class="lc-th-inner">
              <v-icon v-if="!h.fixed" size="12" class="drag-handle mr-1" color="grey-lighten-1">mdi-drag-vertical</v-icon>
              <span
                class="lc-th-title text-truncate"
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
                  <v-icon size="12" color="#42A5F5">mdi-arrow-{{ sortDir(h.key) === 'asc' ? 'up' : 'down' }}</v-icon>
                </v-btn>

                <!-- Priority Badge for Multi-Sort -->
                <span v-if="sortIdx(h.key) && colSorts && colSorts.length > 1" class="sort-badge">
                  {{ sortIdx(h.key) }}
                </span>

                <!-- Column Values Filter Menu -->
                <TableColumnFilter
                  v-model="colFilters[h.key]"
                  :options="filterOpts(h.key)"
                  :counts="filterCounts(h.key)"
                  :title="h.title"
                  color="#42A5F5"
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
                      class="lc-th-pin-btn"
                      :title="$t('pin_column')"
                      @click.stop
                    >
                      <v-icon
                        size="11"
                        :color="pinSide(h.key) === 'left' ? '#42A5F5' : pinSide(h.key) === 'right' ? '#FF7043' : 'grey-lighten-1'"
                      >
                        {{ pinSide(h.key) === 'left' ? 'mdi-dock-left' : pinSide(h.key) === 'right' ? 'mdi-dock-right' : 'mdi-pin-outline' }}
                      </v-icon>
                    </v-btn>
                  </template>
                  <v-list density="compact" class="py-1 rounded-lg" elevation="6">
                    <v-list-item density="compact" :active="pinSide(h.key) === 'left'" @click="setPin(h.key, 'left')">
                      <template #prepend><v-icon size="15" color="#42A5F5">mdi-dock-left</v-icon></template>
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
          <template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="#42A5F5"></v-progress-circular></td></tr></template>
          <template v-else-if="colFilteredItems.length === 0"><tr><td :colspan="headers.length+1" class="text-center pa-8 text-grey">{{ $t('no_data_available') }}</td></tr></template>
          <template v-else>
            <tr v-if="virtualTopHeight > 0" class="virtual-spacer-row" :style="{ height: virtualTopHeight + 'px' }">
              <td :colspan="headers.length + 1" :style="{ height: virtualTopHeight + 'px', padding: 0, border: 'none' }"></td>
            </tr>
            <template v-for="item in visibleItems" :key="item.id">
            <tr class="lc-row" :class="{'lc-row-expanded':expanded[item.id],'lc-row-invoiced':item._is_invoiced}">
              <td class="lc-td lc-th-pin" :style="colPinStyle('__checkbox', 2)"><v-checkbox-btn v-model="selected" :value="item.id" color="#42A5F5" density="compact" hide-details></v-checkbox-btn></td>
              <td v-for="h in headers" :key="h.key" class="lc-td" :class="{'lc-col-pin': isPinned(h.key) || h.fixed}" :style="colPinStyle(h.key, 2)">
                <!-- expand -->
                <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                <!-- date -->
                <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                <!-- category -->
                <template v-else-if="h.key==='charge_category_id'"><v-chip size="small" color="info" variant="tonal">{{ catName(item.charge_category_id) }}</v-chip></template>
                <!-- tracking chips -->
                <template v-else-if="h.key==='_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.reception_tracking_number||[])" :key="tn" size="x-small" color="teal" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.reception_tracking_number||[]).length" class="text-grey">-</span></div></template>
                <!-- onp tracking chips -->
                <template v-else-if="h.key==='_onp_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.tracking_onp_numbers||[])" :key="tn" size="x-small" color="deep-orange" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.tracking_onp_numbers||[]).length" class="text-grey">-</span></div></template>
                <!-- weight/price -->
                <template v-else-if="h.key==='total_weight'">{{ main.formatDisplayNumber(item.total_weight) }} Kg</template>
                <template v-else-if="h.key==='_total_weight_invoice'">{{ main.formatDisplayNumber(item._total_weight_invoice) }} Kg</template>
                <template v-else-if="h.key==='total_price'">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</template>
                <!-- invoice state -->
                <template v-else-if="h.key==='_invoice_state'">
                  <v-chip v-if="item._is_invoiced" size="x-small" color="success" variant="tonal" prepend-icon="mdi-check-circle-outline">{{ item._invoice_state }}</v-chip>
                  <v-chip v-else size="x-small" color="grey" variant="tonal" prepend-icon="mdi-close-circle-outline">{{ $t('not_invoiced') }}</v-chip>
                </template>
                <!-- qc status -->
                <template v-else-if="h.key==='_qc_status'">
                  <template v-if="catHasQualityControl(item)">
                    <v-chip v-if="item._qc_status==='passed'" size="x-small" color="success" variant="tonal" prepend-icon="mdi-check-circle" @click="openQcDialog(item)" style="cursor:pointer">{{ $t('qc_passed') }}</v-chip>
                    <v-chip v-else-if="item._qc_status==='failed'" size="x-small" color="error" variant="tonal" prepend-icon="mdi-close-circle" @click="openQcDialog(item)" style="cursor:pointer">{{ $t('qc_failed') }}</v-chip>
                    <v-chip v-else-if="item._qc_status==='pending'" size="x-small" color="warning" variant="tonal" prepend-icon="mdi-clock-outline" @click="openQcDialog(item)" style="cursor:pointer">{{ $t('qc_pending') }}</v-chip>
                    <v-btn v-else icon size="x-small" variant="tonal" color="#00838F" @click="openQcDialog(item)"><v-icon size="14">mdi-clipboard-plus-outline</v-icon></v-btn>
                  </template>
                  <span v-else class="text-grey">-</span>
                </template>
                <!-- actions -->
                <template v-else-if="h.key==='actions'">
                  <v-btn icon size="x-small" variant="tonal" color="info" @click="$router.push({name:'view_charge',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-eye</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('charges.edit')" icon size="x-small" variant="tonal" color="warning" @click="$router.push({name:'edit_charge',params:{id:item.id}})" class="mr-1" :disabled="item._is_invoiced"><v-icon size="14">mdi-pencil</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('charges.delete')" icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item.id)" class="mr-1" :disabled="item._is_invoiced"><v-icon size="14">mdi-delete</v-icon></v-btn>
                  <v-btn icon size="x-small" variant="tonal" color="blue-grey" @click="openAuditLog(item.id)" class="mr-1"><v-icon size="14">mdi-history</v-icon><v-tooltip activator="parent" location="top">{{ $t('audit_log') }}</v-tooltip></v-btn>
                  <v-btn icon size="x-small" variant="tonal" :color="(item._open_issues||0) > 0 ? 'error' : '#42A5F5'" @click="openIssueDialog(item.id)" class="mr-1"><v-badge v-if="(item._open_issues||0) > 0" :content="item._open_issues" color="error" floating><v-icon size="14">mdi-flag-outline</v-icon></v-badge><v-icon v-else size="14">mdi-flag-outline</v-icon><v-tooltip activator="parent" location="top">{{ $t('record_issues') }}</v-tooltip></v-btn>
                  <template v-if="catHasProduction(item)"><v-btn v-if="!item.production" icon size="x-small" variant="tonal" color="purple" @click="$router.push({name:'add_production',query:{charge_id:item.id}})"><v-icon size="14">mdi-factory</v-icon></v-btn><v-btn v-else icon size="x-small" variant="flat" color="purple" @click="$router.push({name:'view_production',params:{id:item.production.id}})"><v-icon size="14">mdi-pencil</v-icon></v-btn></template>
                </template>
                <!-- fishery -->
                <template v-else-if="h.key==='_fishery'"><v-chip v-if="item._fishery && item._fishery!=='-'" size="x-small" color="cyan" variant="tonal" prepend-icon="mdi-fish">{{ item._fishery }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- origin -->
                <template v-else-if="h.key==='_origin'"><v-chip v-if="item._origin && item._origin!=='-'" size="x-small" color="amber-darken-2" variant="tonal" prepend-icon="mdi-map-marker">{{ item._origin }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- ship -->
                <template v-else-if="h.key==='_ship'"><v-chip v-if="item._ship && item._ship!=='-'" size="x-small" color="indigo" variant="tonal" prepend-icon="mdi-ferry">{{ item._ship }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- trucks -->
                <template v-else-if="h.key==='_trucks'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="t in (item._trucks && item._trucks!=='-' ? item._trucks.split(', ') : [])" :key="t" size="x-small" color="deep-orange" variant="tonal" prepend-icon="mdi-truck">{{ t }}</v-chip><span v-if="!item._trucks || item._trucks==='-'" class="text-grey">-</span></div></template>
                <!-- generic text columns (suppliers) -->
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
            <!-- Expanded detail rows -->
            <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
              <div class="lc-expand-content">
                <div v-for="(grp,gi) in groupDetails(item)" :key="gi" class="lc-detail-group mb-2">
                  <div class="lc-detail-group-header"><v-icon size="16" color="info" class="mr-1">mdi-tag</v-icon><b>{{ grp.category?grp.category[`name_${main.lang?.code}`]||grp.category.name:'-' }}</b></div>
                  <table class="lc-detail-table"><thead><tr><th>{{ $t('classification') }}</th><th>{{ $t('product') }}</th><th>{{ $t('product_family') }}</th><th>{{ $t('warehouse') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('total_weight_invoice') }} (Kg)</th><th>{{ $t('gap') }}</th><th>{{ $t('unit_price') }}</th><th>{{ $t('total_price') }}</th><th>{{ $t('package_type') }}</th><th>{{ $t('total_package_count') }}</th></tr></thead>
                  <tbody><tr v-for="dc in grp.items" :key="dc.id">
                    <td><v-chip size="x-small" color="info" variant="tonal">{{ dc.stockable_type||$t('text') }}</v-chip></td>
                    <td class="font-weight-bold">{{ articleName(dc) }}</td><td>{{ articleFamily(dc) }}</td>
                    <td><v-chip v-if="dc.warehouse" size="x-small" variant="tonal">{{ dc.warehouse[`name_${main.lang?.code}`]||dc.warehouse.name }}</v-chip><span v-else>{{ $t('non_stockable') }}</span></td>
                    <td>{{ main.formatDisplayNumber(dc.weight) }}</td>
                    <td>{{ main.formatDisplayNumber(dc.weight_invoice) }}</td>
                    <td><v-chip size="x-small" :color="Math.abs(Number(dc.weight||0)-Number(dc.weight_invoice||0))<0.01?'success':'warning'" variant="tonal">{{ main.formatDisplayNumber(Math.abs(Number(dc.weight||0)-Number(dc.weight_invoice||0))) }}</v-chip></td>
                    <td>{{ main.formatDisplayNumber(dc.unit_price) }}</td><td>{{ main.formatDisplayNumber(dc.total_price) }}</td>
                    <td>{{ dc.package_type?dc.package_type.name:'N/A' }}</td>
                    <td><v-chip v-if="dc.nbr_box" size="x-small" color="teal" variant="tonal"><v-icon size="12" class="mr-1">mdi-package-variant</v-icon>{{ dc.nbr_box }}</v-chip><span v-else>-</span></td>
                  </tr></tbody>
                  <tfoot><tr class="lc-detail-foot"><td colspan="4" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalW) }}</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalWI) }}</td><td class="font-weight-bold"><v-chip size="x-small" :color="Math.abs(grp.totalW-grp.totalWI)<0.01?'success':'warning'" variant="tonal">{{ main.formatDisplayNumber(Math.abs(grp.totalW-grp.totalWI)) }}</v-chip></td><td></td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalP) }}</td><td colspan="2"></td></tr></tfoot>
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
        <tfoot class="lc-tfoot"><tr><td class="lc-td" :style="colPinStyle('__checkbox', 2)"></td><td v-for="h in headers" :key="'foot-'+h.key" :style="colPinStyle(h.key, 2)" class="lc-td text-center font-weight-bold"><template v-if="h.key==='total_weight'">Σ {{ main.formatDisplayNumber(totalWeight) }} Kg</template><template v-else-if="h.key==='_total_weight_invoice'">Σ {{ main.formatDisplayNumber(totalWeightInvoice) }} Kg</template><template v-else-if="h.key==='total_price'">Σ {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</template></td></tr></tfoot>
      </table>
    </div>
  </v-card-text>
  <!-- TAB: Add Charge -->
  <v-card-text v-else-if="activeTab === 1" class="pa-0">
    <AddCharge />
  </v-card-text>
  <v-dialog v-model="deleteDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ deleteBulk?$t('confirm_bulk_delete_message',{count:selected.length}):$t('confirm_delete_charge_message') }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions></v-card></v-dialog>
</v-card>
<!-- Quality Control Dialog -->
<QualityControlDialog v-model="qcDialogOpen" :charge="qcDialogCharge" @saved="onQcSaved" @deleted="onQcDeleted" />
<!-- Audit Log Dialog -->
<AuditLogDialog v-model="auditLogDialogOpen" auditable-type="Charge" :auditable-id="auditLogRecordId" module-color="#42A5F5" />
<!-- Record Issue Dialog -->
<RecordIssueDialog v-model="issueDialogOpen" record-type="Charge" :record-id="issueDialogRecordId" module-color="#42A5F5" @issue-changed="fetchCharges" />
</template>
<script>
import script from './script.js';
import virtualTableMixin from '@/utils/virtualTableMixin.js';
import tableConfigMixin from '@/utils/tableConfigMixin.js';
import AddCharge from '../AddCharge/index.vue';
import QualityControlDialog from '../QualityControl/QualityControlDialog.vue';
import AuditLogDialog from '@/components/shared/AuditLogDialog.vue';
import RecordIssueDialog from '@/components/shared/RecordIssueDialog.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import TableColumnFilter from '@/components/common/TableColumnFilter.vue';
import { useAuth } from '@/stores/Auth';
export default {
  ...script,
  mixins: [virtualTableMixin, tableConfigMixin],
  components:{...script.components,AddCharge,QualityControlDialog,AuditLogDialog,RecordIssueDialog,SegmentedPillToggle,TableColumnFilter},
  data(){return{...script.data.call(this),activeTab:0,qcDialogOpen:false,qcDialogCharge:null,auditLogDialogOpen:false,auditLogRecordId:0,issueDialogOpen:false,issueDialogRecordId:0,chargeTabs:[{value:0,label:'list_charge',icon:'mdi-view-list-outline'},...(useAuth().hasPermission('charges.create')?[{value:1,label:'add_charge',icon:'mdi-sticker-plus-outline'}]:[])]}},
  computed:{...script.computed,auth(){return useAuth()},selectAll:{get(){return this.selected.length===this.colFilteredItems.length&&this.colFilteredItems.length>0},set(v){this.selected=v?this.colFilteredItems.map(i=>i.id):[]}}},
  methods:{...script.methods,openQcDialog(item){this.qcDialogCharge=item;this.qcDialogOpen=true},onQcSaved(){this.fetchCharges()},onQcDeleted(){this.fetchCharges()},openAuditLog(id){this.auditLogRecordId=id;this.auditLogDialogOpen=true},openIssueDialog(id){this.issueDialogRecordId=id;this.issueDialogOpen=true}},
  watch:{...script.watch,activeTab(v){const q=v===1?{tab:'add'}:{};if(JSON.stringify(this.$route.query)!==JSON.stringify(q))this.$router.replace({query:q}).catch(()=>{});if(v===0)this.fetchCharges()},showInvoice(v){if(v&&this.uninvoicedSelectedCharges.length){this.fetchNextTracking(this.invForm.type_invoice||'local')}}},
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
.lc-table-loader {
  position: sticky;
  top: 0;
  z-index: 6;
}
.lc-card{border-radius:20px!important;font-family:'Inter',sans-serif}
.lc-tab-toolbar{background:rgba(var(--v-theme-surface),.95);border:1px solid rgba(66,165,245,.2)!important;box-shadow:0 2px 8px rgba(13,71,161,.04)}
.lc-segmented-toggle{background:rgba(var(--v-theme-on-surface),.02)}
.lc-hero{background:linear-gradient(135deg,#0D47A1 0%,#1565C0 40%,#42A5F5 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;min-height:100px}
.lc-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.lc-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.15)}
.lc-hero-title{font-size:19px;font-weight:800;letter-spacing:.3px}
.lc-hero-actions{display:flex;gap:8px;flex-wrap:wrap}
.lc-hero-kpi{display:flex;flex-direction:column;align-items:flex-start;padding:6px 14px;background:rgba(255,255,255,.14);backdrop-filter:blur(8px);border-radius:12px;border:1px solid rgba(255,255,255,.2);min-width:90px}
.lc-kpi-val{font-size:14px;font-weight:800;line-height:1.2;color:#ffffff}
.lc-kpi-lbl{font-size:10px;text-transform:uppercase;letter-spacing:.5px;opacity:.85;color:#e3f2fd}
.lc-toolbar{background:rgba(var(--v-theme-surface),.95);border:1px solid rgba(66,165,245,.15);box-shadow:0 2px 12px rgba(13,71,161,.04)}
.lc-search-field{max-width:320px}
.lc-filter-btn{font-weight:600;letter-spacing:.3px}
.adv-panel{border:1.5px solid rgba(25,118,210,.15);background:rgba(25,118,210,.03)}
.inv-panel{border:1.5px solid rgba(0,150,136,.15);background:rgba(0,150,136,.03)}
.lc-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.lc-table{width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed}
.lc-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.lc-th{position:relative;padding:8px 8px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s;overflow:hidden;box-sizing:border-box}
.lc-td{padding:8px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;box-sizing:border-box}
.lc-col-resizer{position:absolute;top:0;right:0;width:6px;height:100%;cursor:col-resize;user-select:none;z-index:6;background:transparent;transition:background .15s}
.lc-col-resizer:hover,.lc-col-resizer-active{background:#42A5F5!important;width:3px!important}
.lc-th-pin-btn{opacity:.65;transition:opacity .15s}.lc-th-pin-btn:hover{opacity:1}
.lc-active-sorts-strip{font-family:'Inter',sans-serif}
.lc-col-pin{position:sticky!important;z-index:2!important;background:#ffffff!important}
.lc-th-pin{position:sticky!important;z-index:5!important;background:#f8fafc!important}
:deep(.v-theme--dark) .lc-col-pin,
:deep(.v-theme--dark) .lc-th-pin,
.v-theme--dark .lc-col-pin,
.v-theme--dark .lc-th-pin {
  background: #1e293b !important;
  color: #f1f5f9 !important;
}
.lc-th-dragover{background:rgba(25,118,210,.08)!important}
.lc-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.lc-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#1976D2;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.lc-row:hover{background:rgba(25,118,210,.04)}
.lc-row-expanded{background:rgba(25,118,210,.06)}
.lc-row-invoiced{background:rgba(76,175,80,.08)!important}
.lc-row-invoiced:hover{background:rgba(76,175,80,.14)!important}
.lc-tfoot td{padding:10px 12px;border-top:2px solid rgba(25,118,210,.2);color:#1565C0;font-size:14px}
:deep(.v-theme--dark) .lc-tfoot td,
.v-theme--dark .lc-tfoot td {
  color: #64B5F6 !important;
}
.lc-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.lc-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow:hidden}
.lc-detail-group-header{padding:8px 14px;background:rgba(25,118,210,.06);font-size:13px}
.lc-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.lc-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.lc-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.lc-detail-foot td{padding:8px 10px;background:rgba(25,118,210,.04);border-top:1px solid rgba(25,118,210,.15)}
.lc-panel-card{border:1.5px solid rgba(25,118,210,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.lc-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.lc-stats-panel{max-height:400px}
.lc-stat-item{padding:8px 10px;border-radius:10px;background:rgba(25,118,210,.03);border:1px solid rgba(25,118,210,.06)}
</style>
