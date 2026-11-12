<template>
<v-card class="lc-card" elevation="0">
  <div class="lc-hero"><div class="lc-hero-inner">
    <div class="d-flex align-center ga-4">
      <div class="lc-hero-icon"><v-icon size="28" color="white">mdi-file-document-multiple-outline</v-icon></div>
      <div>
        <div class="lc-hero-title">{{ $t('supplier_invoices') }}</div>
        <div class="text-white text-caption" style="opacity:.7">{{ $t('datatable_supplier_invoice') }}</div>
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
    </div>
    <div class="lc-hero-actions">
      <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchInvoices" :loading="loading">{{ $t('refresh') }}</v-btn>
      <v-btn v-if="selected.length" variant="tonal" color="white" rounded size="small" prepend-icon="mdi-eye" @click="viewSelected">{{ $t('view_invoices') }} ({{ selected.length }})</v-btn>
      <v-btn v-if="selected.length && auth.hasPermission('supplier_invoices.delete')" variant="tonal" color="error" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete_selected') }} ({{ selected.length }})</v-btn>
    </div>
  </div></div>

  <v-card-text class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="12" md="3">
        <div class="lc-panel-card">
          <div class="lc-panel-header d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center">
              <v-icon size="16" color="#E65100" class="mr-1">mdi-calendar-range</v-icon>
              <span>{{ $t('date_range') }}</span>
            </div>
            <v-chip v-if="datePreset" size="x-small" color="#E65100" variant="tonal" class="font-weight-bold">
              {{ $t(datePreset) }}
            </v-chip>
          </div>
          <!-- Auto-select Date Range Segmented Pill Toggle -->
          <div class="mb-2 d-flex justify-center lc-date-presets-wrapper">
            <SegmentedPillToggle
              v-model="datePreset"
              :items="datePresetOptions"
              color="#E65100"
              size="x-small"
              block
              @update:model-value="onDatePresetChange"
            />
          </div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="orange" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />

          <!-- Selected Date Range Display Below VCalendar -->
          <div class="lc-selected-range mt-2 pt-2 border-t d-flex align-center justify-center ga-2 flex-nowrap">
            <v-chip size="x-small" color="#E65100" variant="tonal" class="font-weight-bold" :title="$t('start_date')">
              <v-icon start size="12">mdi-calendar-start</v-icon>
              {{ main.formateDate(dateRange[0]) }}
            </v-chip>
            <v-icon size="12" color="grey">mdi-arrow-right</v-icon>
            <v-chip size="x-small" color="#E65100" variant="tonal" class="font-weight-bold" :title="$t('end_date')">
              <v-icon start size="12">mdi-calendar-end</v-icon>
              {{ main.formateDate(dateRange[1] || dateRange[0]) }}
            </v-chip>
          </div>
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="12" md="6">
        <div class="lc-panel-card" style="height:100%">
          <div class="lc-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#E65100" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#E65100" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <div v-if="loading" class="d-flex flex-column justify-space-between" style="height: 260px; padding: 16px;">
            <div class="d-flex align-end justify-space-between ga-2" style="height: 200px; width: 100%;">
              <div v-for="b in 12" :key="'lsi-chart-skel-' + b" class="skel-bone" :style="{ width: '5%', height: (25 + (b * 19) % 65) + '%', borderRadius: '4px 4px 0 0' }"></div>
            </div>
            <div class="d-flex justify-space-between mt-2">
              <div v-for="t in 6" :key="'lsi-chart-lbl-' + t" class="skel-bone" style="width: 36px; height: 10px; border-radius: 4px;"></div>
            </div>
          </div>
          <apexchart v-else-if="chartSeries[0].data.length" type="line" :height="260" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="12" md="3">
        <div class="lc-panel-card lc-stats-panel" style="height:100%;overflow-y:auto">
          <div class="lc-panel-header"><v-icon size="16" color="#E65100" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <div v-if="loading" class="d-flex flex-column ga-3 py-2">
            <div v-for="s in 3" :key="'si-skel-stat-' + s" class="lc-stat-item mb-2">
              <div class="d-flex align-center justify-space-between mb-1.5">
                <div class="skel-bone" style="width: 90px; height: 18px; border-radius: 999px;"></div>
                <div class="skel-bone" style="width: 40px; height: 18px; border-radius: 999px;"></div>
              </div>
              <div class="skel-bone mb-2" style="width: 100%; height: 5px; border-radius: 999px;"></div>
              <div class="d-flex flex-column ga-1.5">
                <div class="d-flex align-center ga-1.5">
                  <div class="skel-bone skel-circle" style="width: 12px; height: 12px; flex-shrink: 0;"></div>
                  <div class="skel-bone" style="width: 50px; height: 11px;"></div>
                  <div class="skel-bone ml-auto" style="width: 65px; height: 11px;"></div>
                </div>
                <div class="d-flex align-center ga-1.5">
                  <div class="skel-bone skel-circle" style="width: 12px; height: 12px; flex-shrink: 0;"></div>
                  <div class="skel-bone" style="width: 60px; height: 11px;"></div>
                  <div class="skel-bone ml-auto" style="width: 75px; height: 11px;"></div>
                </div>
              </div>
            </div>
            <v-divider class="my-2"></v-divider>
            <div class="d-flex flex-column ga-2">
              <div class="d-flex align-center ga-2">
                <div class="skel-bone skel-circle" style="width: 14px; height: 14px; flex-shrink: 0;"></div>
                <div class="skel-bone" style="width: 70px; height: 12px;"></div>
                <div class="skel-bone ml-auto" style="width: 80px; height: 12px;"></div>
              </div>
              <div class="d-flex align-center ga-2">
                <div class="skel-bone skel-circle" style="width: 14px; height: 14px; flex-shrink: 0;"></div>
                <div class="skel-bone" style="width: 65px; height: 12px;"></div>
                <div class="skel-bone ml-auto" style="width: 85px; height: 12px;"></div>
              </div>
            </div>
          </div>
          <template v-else>
            <!-- Per-supplier stats -->
            <div v-for="(stat,si) in supplierStats" :key="si" class="lc-stat-item mb-2">
              <div class="d-flex align-center justify-space-between mb-1">
                <v-chip size="x-small" color="#E65100" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
                <v-chip size="x-small" color="#E65100" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
              </div>
              <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#E65100" bg-color="#FFE0B2" height="5" rounded class="mb-1"></v-progress-linear>
              <div class="d-flex flex-column ga-1 text-caption">
                <div class="d-flex align-center ga-1"><v-icon size="12" color="#E65100">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color:#E65100" class="fnm-currency-num">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
                <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange fnm-currency-num">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
              </div>
            </div>
            <v-divider class="my-2"></v-divider>
            <!-- Summary totals -->
            <div class="d-flex flex-column ga-1">
              <div class="d-flex align-center ga-2"><v-icon size="14" color="#E65100">mdi-file-document-multiple</v-icon><span class="text-caption text-medium-emphasis">{{ $t('supplier_invoices') }}:</span><v-chip size="x-small" color="#E65100" variant="tonal"><b class="fnm-currency-num">{{ colFilteredItems.length }}</b></v-chip></div>
              <div class="d-flex align-center ga-2"><v-icon size="14" color="teal">mdi-package-variant</v-icon><span class="text-caption text-medium-emphasis">{{ $t('charges') }}:</span><v-chip size="x-small" color="teal" variant="tonal"><b class="fnm-currency-num">{{ totalChargesCount }}</b></v-chip></div>
              <div class="d-flex align-center ga-2"><v-icon size="14" color="purple">mdi-factory</v-icon><span class="text-caption text-medium-emphasis">{{ $t('productions') }}:</span><v-chip size="x-small" color="purple" variant="tonal"><b class="fnm-currency-num">{{ totalProductionsCount }}</b></v-chip></div>
            </div>
            <v-divider class="my-2"></v-divider>
            <div class="d-flex flex-column ga-1">
              <div class="d-flex align-center ga-1"><v-icon size="14" color="#E65100">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b style="color:#E65100" class="text-caption fnm-currency-num">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption fnm-currency-num">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
            </div>
          </template>
        </div>
      </v-col>
    </v-row>

    <!-- Unified Command & Filter Toolbar -->
    <div class="lc-toolbar mb-3 pa-2 px-3 rounded-xl">
      <div class="d-flex align-center justify-space-between flex-wrap ga-2">
        <!-- Left Side: Quick Search & Filter Controls -->
        <div class="d-flex align-center flex-wrap ga-2 flex-grow-1" style="min-width: 260px;">
          <v-text-field
            v-model="searchQuery"
            density="compact"
            variant="outlined"
            rounded="lg"
            hide-details
            clearable
            color="#E65100"
            prepend-inner-icon="mdi-magnify"
            :placeholder="$t('search_records_placeholder') || 'Search invoices...'"
            style="min-width: 200px; max-width: 280px;"
          ></v-text-field>

          <v-btn
            v-if="Object.values(colFilters).some(f=>f&&f.length)"
            variant="text"
            color="warning"
            rounded="lg"
            size="small"
            prepend-icon="mdi-filter-off"
            @click="colFilters={}"
          >
            {{ $t('clear_filters') }}
          </v-btn>
        </div>

        <!-- Right Side: Records Counter, Row Expander, Columns Manager, Export & Expand View -->
        <div class="d-flex align-center flex-wrap ga-2">
          <v-chip size="small" variant="tonal" color="#E65100" class="font-weight-medium">
            {{ colFilteredItems.length }} / {{ invoices.length }} {{ $t('records') }}
          </v-chip>

          <v-divider vertical class="mx-1 my-1" style="height: 22px;"></v-divider>

          <!-- Expand / Collapse All Details Button -->
          <v-btn
            :variant="isAllExpanded ? 'flat' : 'tonal'"
            :color="isAllExpanded ? 'warning' : '#E65100'"
            rounded="lg"
            size="small"
            class="lc-filter-btn"
            :title="isAllExpanded ? $t('collapse_all') : $t('expand_all')"
            @click="toggleExpandAll"
          >
            <v-icon size="16" class="mr-1">{{ isAllExpanded ? 'mdi-arrow-collapse-vertical' : 'mdi-arrow-expand-vertical' }}</v-icon>
            {{ isAllExpanded ? $t('collapse_all') : $t('expand_all') }}
            <v-badge
              v-if="expandedCount > 0"
              :content="expandedCount"
              :color="isAllExpanded ? 'warning' : '#E65100'"
              inline
              class="ml-1"
            ></v-badge>
          </v-btn>

          <!-- Columns Manager -->
          <TableColumnManager
            :columns="configColumns"
            :color="moduleColor"
            :hidden-columns="hiddenColumns"
            :pin-state="pinState"
            @toggle-visibility="toggleColumnVisibility"
            @show-all="showAllColumns"
            @hide-all="hideAllColumns"
            @move-up="moveColumnUp"
            @move-down="moveColumnDown"
            @set-pin="setPin($event.key, $event.side)"
            @reset="resetTableConfig"
            @reorder="onColumnManagerReorder"
          />

          <!-- Export Menu (Excel & CSV) -->
          <v-menu location="bottom end" :close-on-content-click="true">
            <template #activator="{ props: expProps }">
              <v-btn
                v-bind="expProps"
                variant="tonal"
                color="#4CAF50"
                size="small"
                rounded="lg"
                prepend-icon="mdi-file-export-outline"
                class="font-weight-medium"
              >
                {{ $t('export') || 'Export' }}
                <v-icon end size="14">mdi-chevron-down</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" class="py-1 rounded-lg" elevation="6">
              <v-list-item prepend-icon="mdi-file-excel-outline" @click="exportTableToExcel()">
                <v-list-item-title class="text-caption font-weight-medium">
                  {{ $t('export_all_filtered', { count: colFilteredItems.length }) }} (.xlsx)
                </v-list-item-title>
              </v-list-item>
              <v-list-item prepend-icon="mdi-file-delimited-outline" @click="exportTableToCsv()">
                <v-list-item-title class="text-caption font-weight-medium">
                  {{ $t('export_all_filtered', { count: colFilteredItems.length }) }} (.csv)
                </v-list-item-title>
              </v-list-item>
              <v-divider v-if="selected.length" class="my-1"></v-divider>
              <v-list-item v-if="selected.length" prepend-icon="mdi-file-excel-outline" @click="exportSelectedToExcel">
                <v-list-item-title class="text-caption font-weight-medium">
                  {{ $t('export_selected_only', { count: selected.length }) }} (.xlsx)
                </v-list-item-title>
              </v-list-item>
              <v-list-item v-if="selected.length" prepend-icon="mdi-file-delimited-outline" @click="exportSelectedToCsv">
                <v-list-item-title class="text-caption font-weight-medium">
                  {{ $t('export_selected_only', { count: selected.length }) }} (.csv)
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>

          <!-- Expand View Button -->
          <v-btn
            :variant="isExpandedView ? 'flat' : 'elevated'"
            :color="isExpandedView ? 'warning' : '#E65100'"
            rounded="lg"
            size="small"
            class="lc-filter-btn font-weight-bold"
            :class="{ 'text-white': !isExpandedView }"
            :prepend-icon="isExpandedView ? 'mdi-arrow-collapse-all' : 'mdi-arrow-expand-all'"
            :title="isExpandedView ? $t('collapse_view') : $t('expand_view')"
            @click="toggleExpandedView"
          >
            {{ isExpandedView ? $t('collapse_view') : $t('expand_view') }}
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Table Container (Supports Normal Mode and Expanded Fullscreen Mode) -->
    <div
      class="lc-table-container"
      :class="{
        'lc-fullscreen-mode': isExpandedView,
        ['density-' + tableDensity]: true,
      }"
    >
      <!-- Dedicated Command Bar when Expanded View is Active -->
      <div v-if="isExpandedView" class="lc-fullscreen-header mb-3 pa-2 px-3 rounded-xl">
        <div class="d-flex align-center justify-space-between flex-wrap ga-2">
          <!-- Left: Module Brand & Live KPI Chips -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <div class="d-flex align-center ga-2 mr-1">
              <v-avatar size="32" color="#E65100" variant="flat" rounded="lg">
                <v-icon size="18" color="white">mdi-file-document-multiple-outline</v-icon>
              </v-avatar>
              <div>
                <div class="text-caption font-weight-bold" style="line-height: 1.2; color: #E65100;">{{ $t('supplier_invoices') }}</div>
                <div class="text-caption text-medium-emphasis" style="font-size: 10px !important;">{{ $t('expand_view') }}</div>
              </div>
            </div>

            <v-chip size="small" variant="tonal" color="#E65100" class="font-weight-bold">
              <v-icon start size="14">mdi-counter</v-icon>
              {{ colFilteredItems.length }} / {{ invoices.length }} {{ $t('records') }}
            </v-chip>
            <v-chip size="small" variant="tonal" color="#E65100" class="font-weight-bold">
              <v-icon start size="14">mdi-weight-kilogram</v-icon>
              {{ main.formatDisplayNumber(totalWeight) }} Kg
            </v-chip>
            <v-chip size="small" variant="tonal" color="#E65100" class="font-weight-bold">
              <v-icon start size="14">mdi-cash</v-icon>
              {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}
            </v-chip>
          </div>

          <!-- Right: Search, Density, Expand All, Columns Manager, Export, Bulk Actions & Exit -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-text-field
              v-model="searchQuery"
              density="compact"
              variant="outlined"
              rounded="lg"
              hide-details
              clearable
              color="#E65100"
              prepend-inner-icon="mdi-magnify"
              :placeholder="$t('search_records_placeholder') || 'Search records...'"
              style="min-width: 170px; max-width: 220px;"
            ></v-text-field>

            <!-- Table Density Selector -->
            <div class="d-flex align-center border rounded-lg pa-0-5 bg-surface">
              <v-btn
                size="x-small"
                icon
                :variant="tableDensity === 'comfortable' ? 'flat' : 'text'"
                :color="tableDensity === 'comfortable' ? '#E65100' : 'grey'"
                :title="$t('density_comfortable')"
                @click="setTableDensity('comfortable')"
              >
                <v-icon size="15">mdi-view-headline</v-icon>
              </v-btn>
              <v-btn
                size="x-small"
                icon
                :variant="tableDensity === 'compact' ? 'flat' : 'text'"
                :color="tableDensity === 'compact' ? '#E65100' : 'grey'"
                :title="$t('density_compact')"
                @click="setTableDensity('compact')"
              >
                <v-icon size="15">mdi-view-agenda-outline</v-icon>
              </v-btn>
              <v-btn
                size="x-small"
                icon
                :variant="tableDensity === 'dense' ? 'flat' : 'text'"
                :color="tableDensity === 'dense' ? '#E65100' : 'grey'"
                :title="$t('density_dense')"
                @click="setTableDensity('dense')"
              >
                <v-icon size="15">mdi-view-sequential-outline</v-icon>
              </v-btn>
            </div>

            <!-- Expand / Collapse All Details -->
            <v-btn
              size="small"
              variant="tonal"
              :color="isAllExpanded ? 'warning' : '#E65100'"
              rounded="lg"
              :title="isAllExpanded ? $t('collapse_all') : $t('expand_all')"
              @click="toggleExpandAll"
            >
              <v-icon size="16">{{ isAllExpanded ? 'mdi-arrow-collapse-vertical' : 'mdi-arrow-expand-vertical' }}</v-icon>
            </v-btn>

            <!-- Column Visibility & Order Manager -->
            <TableColumnManager
              :columns="configColumns"
              :color="moduleColor"
              :hidden-columns="hiddenColumns"
              :pin-state="pinState"
              @toggle-visibility="toggleColumnVisibility"
              @show-all="showAllColumns"
              @hide-all="hideAllColumns"
              @move-up="moveColumnUp"
              @move-down="moveColumnDown"
              @set-pin="setPin($event.key, $event.side)"
              @reset="resetTableConfig"
              @reorder="onColumnManagerReorder"
            />

            <!-- Export Menu -->
            <v-menu location="bottom end" :close-on-content-click="true">
              <template #activator="{ props: expProps }">
                <v-btn
                  v-bind="expProps"
                  variant="tonal"
                  color="#4CAF50"
                  size="small"
                  rounded="lg"
                  prepend-icon="mdi-file-export-outline"
                  class="font-weight-medium"
                >
                  {{ $t('export') || 'Export' }}
                  <v-icon end size="14">mdi-chevron-down</v-icon>
                </v-btn>
              </template>
              <v-list density="compact" class="py-1 rounded-lg" elevation="6">
                <v-list-item prepend-icon="mdi-file-excel-outline" @click="exportTableToExcel()">
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ $t('export_all_filtered', { count: colFilteredItems.length }) }} (.xlsx)
                  </v-list-item-title>
                </v-list-item>
                <v-list-item prepend-icon="mdi-file-delimited-outline" @click="exportTableToCsv()">
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ $t('export_all_filtered', { count: colFilteredItems.length }) }} (.csv)
                  </v-list-item-title>
                </v-list-item>
                <v-divider v-if="selected.length" class="my-1"></v-divider>
                <v-list-item v-if="selected.length" prepend-icon="mdi-file-excel-outline" @click="exportSelectedToExcel">
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ $t('export_selected_only', { count: selected.length }) }} (.xlsx)
                  </v-list-item-title>
                </v-list-item>
                <v-list-item v-if="selected.length" prepend-icon="mdi-file-delimited-outline" @click="exportSelectedToCsv">
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ $t('export_selected_only', { count: selected.length }) }} (.csv)
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>

            <!-- Bulk Delete -->
            <v-btn
              v-if="selected.length && auth.hasPermission('supplier_invoices.delete')"
              variant="tonal"
              color="error"
              rounded="lg"
              size="small"
              prepend-icon="mdi-delete"
              @click="confirmBulkDelete"
            >
              {{ $t('delete_selected') }} ({{ selected.length }})
            </v-btn>

            <v-divider vertical class="mx-1 my-1" style="height: 22px;"></v-divider>

            <!-- Exit Fullscreen Button -->
            <v-btn
              variant="flat"
              color="error"
              rounded="lg"
              size="small"
              prepend-icon="mdi-fullscreen-exit"
              @click="exitExpandedView"
              class="font-weight-bold text-white shadow-sm"
            >
              {{ $t('exit_expand_view') }}
              <v-chip size="x-small" color="white" variant="outlined" class="ml-1 font-weight-bold px-1" style="height: 18px; font-size: 10px;">ESC</v-chip>
            </v-btn>
          </div>
        </div>
      </div>

      <!-- Active Multi-Sorts Strip -->
      <div v-if="colSorts && colSorts.length > 0" class="lc-active-sorts-strip d-flex align-center flex-wrap ga-1 px-3 py-1 mb-2 rounded-lg" style="background: rgba(230, 81, 0, 0.06); border: 1px dashed rgba(230, 81, 0, 0.3);">
        <span class="text-caption font-weight-bold d-flex align-center ga-1 mr-1" style="color: #E65100;">
          <v-icon size="14" color="#E65100">mdi-sort</v-icon>
          {{ $t('sorted_by') }}:
        </span>
        <v-chip
          v-for="(s, sIdx) in colSorts"
          :key="'sort_' + s.key"
          size="x-small"
          rounded="lg"
          color="#E65100"
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
        <table class="lc-table">
          <thead class="lc-thead"><tr>
            <th class="lc-th lc-th-pin" :style="thPinStyles['__checkbox']"><v-checkbox-btn v-model="selectAll" color="#E65100" density="compact" hide-details></v-checkbox-btn></th>
            <th
              v-for="h in headers"
              :key="h.key"
              class="lc-th"
              :class="{'lc-th-pin': isPinned(h.key) || h.fixed, 'lc-th-dragover': dragOverCol === h.key, 'lc-th-actions': h.key === 'actions'}"
              :style="thPinStyles[h.key]"
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
                    <v-icon size="12" color="#E65100">mdi-arrow-{{ sortDir(h.key) === 'asc' ? 'up' : 'down' }}</v-icon>
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
                    color="#E65100"
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
                          :color="pinSide(h.key) === 'left' ? '#E65100' : pinSide(h.key) === 'right' ? '#FF7043' : 'grey-lighten-1'"
                        >
                          {{ pinSide(h.key) === 'left' ? 'mdi-dock-left' : pinSide(h.key) === 'right' ? 'mdi-dock-right' : 'mdi-pin-outline' }}
                        </v-icon>
                      </v-btn>
                    </template>
                    <v-list density="compact" class="py-1 rounded-lg" elevation="6">
                      <v-list-item density="compact" :active="pinSide(h.key) === 'left'" @click="setPin(h.key, 'left')">
                        <template #prepend><v-icon size="15" color="#E65100">mdi-dock-left</v-icon></template>
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
            <template v-if="loading">
              <tr v-for="r in 8" :key="'si-skel-' + r" class="lc-row app-skeleton-table-row">
                <td class="lc-td text-center" :style="headerPinStyles['__checkbox']">
                  <div class="skel-bone mx-auto" style="width: 18px; height: 18px; border-radius: 4px;"></div>
                </td>
                <td v-for="h in headers" :key="'si-skel-' + h.key + '-' + r" class="lc-td" :class="{'lc-col-pin': isPinned(h.key) || h.fixed, 'lc-td-actions': h.key === 'actions'}" :style="headerPinStyles[h.key]">
                  <div v-if="h.key === 'expand'" class="skel-bone skel-circle mx-auto" style="width: 22px; height: 22px;"></div>
                  <div v-else-if="h.key === 'actions'" class="d-flex align-center justify-center ga-1">
                    <div v-for="a in 3" :key="a" class="skel-bone skel-circle" style="width: 20px; height: 20px;"></div>
                  </div>
                  <div v-else-if="h.key === 'invoice_number' || h.key.includes('status')" class="skel-bone" style="width: 85px; height: 20px; border-radius: 999px;"></div>
                  <div v-else class="skel-bone" style="width: 75%; height: 13px;"></div>
                </td>
              </tr>
            </template>
            <template v-else-if="colFilteredItems.length === 0"><tr><td :colspan="headers.length+1" class="text-center pa-8 text-grey">{{ $t('no_data_available') }}</td></tr></template>
            <template v-else>
              <tr v-if="virtualTopHeight > 0" class="virtual-spacer-row" :style="{ height: virtualTopHeight + 'px' }">
                <td :colspan="headers.length + 1" :style="{ height: virtualTopHeight + 'px', padding: 0, border: 'none' }"></td>
              </tr>
              <template v-for="item in visibleItems" :key="item.id">
                <tr class="lc-row" :class="{'lc-row-expanded':expanded[item.id]}">
                  <td class="lc-td lc-col-pin" :style="headerPinStyles['__checkbox']"><v-checkbox-btn :model-value="selectedSet.has(item.id)" @update:model-value="toggleItemSelect(item.id)" color="#E65100" density="compact" hide-details></v-checkbox-btn></td>
                  <td v-for="h in headers" :key="h.key" class="lc-td" :class="{'lc-col-pin': isPinned(h.key) || h.fixed, 'lc-td-actions': h.key === 'actions'}" :style="headerPinStyles[h.key]">
                    <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                    <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                    <template v-else-if="h.key==='_tracking_numbers'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.tracking_numbers||[])" :key="tn" size="x-small" color="#E65100" variant="tonal" class="fnm-currency-num">{{ tn }}</v-chip><span v-if="!(item.tracking_numbers||[]).length" class="text-grey">-</span></div></template>
                    <template v-else-if="h.key==='_suppliers'">
                      <div class="d-flex align-center ga-1 justify-center">
                        <span>{{ item._suppliers }}</span>
                        <v-chip v-if="item.supplier?.enable_plafond" size="x-small" color="#E65100" variant="outlined" class="ml-1 font-weight-bold" :title="$t('debt_plafond')">
                          <v-icon start size="10">mdi-shield-check</v-icon>
                          {{ main.formatDisplayNumber(item.supplier.plafond_amount) }}
                        </v-chip>
                      </div>
                    </template>
                    <template v-else-if="h.key==='total_weight'"><span class="fnm-currency-num">{{ main.formatDisplayNumber(item.total_weight) }} Kg</span></template>
                    <template v-else-if="h.key==='total_price'"><span class="fnm-currency-num font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</span></template>
                    <template v-else-if="h.key==='print_counter'"><v-chip size="small" :color="item.print_counter>0?'success':'grey'" variant="tonal"><v-icon size="14" class="mr-1">mdi-printer</v-icon>{{ item.print_counter }}</v-chip></template>
                    <template v-else-if="h.key==='actions'">
                      <div class="lc-actions-wrapper">
                        <v-menu location="bottom end">
                          <template v-slot:activator="{ props }"><v-btn icon="mdi-dots-vertical" size="x-small" variant="outlined" color="#E65100" v-bind="props"></v-btn></template>
                          <v-list slim density="compact">
                            <v-list-item prepend-icon="mdi-eye" @click="$router.push({name:'view_supplier_invoice',query:{ids:[item.id]}})"><v-list-item-title>{{$t('view_item')}}</v-list-item-title></v-list-item>
                            <v-divider v-if="auth.hasPermission('supplier_invoices.delete')"></v-divider>
                            <v-list-item v-if="auth.hasPermission('supplier_invoices.delete')" prepend-icon="mdi-delete" @click="confirmDelete(item.id)"><v-list-item-title>{{$t('delete_item')}}</v-list-item-title></v-list-item>
                          </v-list>
                        </v-menu>
                      </div>
                    </template>
                    <template v-else>{{ item[h.key] || '-' }}</template>
                  </td>
                </tr>
                <!-- Expanded detail -->
                <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
                  <div class="lc-expand-content">
                    <!-- Charges sub-table -->
                    <div v-if="(item.charges||[]).length" class="lc-detail-group mb-3">
                      <div class="lc-detail-group-header"><v-icon size="16" color="#E65100" class="mr-1">mdi-package-variant</v-icon><b>{{ $t('charges') }} ({{ item.charges.length }})</b></div>
                      <table class="lc-detail-table"><thead><tr>
                        <th>{{ $t('date') }}</th><th>{{ $t('charge_category') }}</th><th>{{ $t('reception_tracking_number') }}</th><th>{{ $t('suppliers') }}</th><th>{{ $t('fishery') }}</th><th>{{ $t('origin') }}</th><th>{{ $t('ship') }}</th><th>{{ $t('trucks') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('total_price') }}</th>
                      </tr></thead>
                      <tbody><tr v-for="ch in item.charges" :key="ch.id">
                        <td>{{ main.formateDate(ch.date) }}</td>
                        <td><v-chip size="x-small" color="#E65100" variant="tonal">{{ catName(ch) }}</v-chip></td>
                        <td><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (ch.reception_tracking_number||[])" :key="tn" size="x-small" color="teal" variant="tonal">{{ tn }}</v-chip><span v-if="!(ch.reception_tracking_number||[]).length" class="text-grey">-</span></div></td>
                        <td>{{ supplierName(ch) }}</td>
                        <td>{{ ch.fishery ? (ch.fishery[`name_${main.lang?.code}`]||ch.fishery.name) : '-' }}</td>
                        <td>{{ ch.origin ? (ch.origin[`name_${main.lang?.code}`]||ch.origin.name) : '-' }}</td>
                        <td>{{ ch.ship ? (ch.ship[`name_${main.lang?.code}`]||ch.ship.name) : '-' }}</td>
                        <td>{{ (ch.trucks||[]).map(t=>t.registration_plate||t.name).join(', ') || '-' }}</td>
                        <td class="fnm-currency-num">{{ main.formatDisplayNumber(ch.total_weight) }}</td>
                        <td class="fnm-currency-num">{{ main.formatDisplayNumber(ch.total_price) }} {{ main.currency }}</td>
                      </tr></tbody>
                      <tfoot><tr class="lc-detail-foot"><td colspan="8" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold fnm-currency-num">{{ main.formatDisplayNumber(item.charges.reduce((s,c)=>s+Number(c.total_weight||0),0)) }}</td><td class="font-weight-bold fnm-currency-num">{{ main.formatDisplayNumber(item.charges.reduce((s,c)=>s+Number(c.total_price||0),0)) }} {{ main.currency }}</td></tr></tfoot>
                      </table>
                    </div>
                    <!-- Productions sub-table -->
                    <div v-if="(item.productions||[]).length" class="lc-detail-group mb-2">
                      <div class="lc-detail-group-header"><v-icon size="16" color="purple" class="mr-1">mdi-factory</v-icon><b>{{ $t('productions') }} ({{ item.productions.length }})</b></div>
                      <table class="lc-detail-table"><thead><tr>
                        <th>{{ $t('date') }}</th><th>{{ $t('production_category') }}</th><th>{{ $t('production_tracking_number') }}</th><th>{{ $t('suppliers') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('total_price') }}</th>
                      </tr></thead>
                      <tbody><tr v-for="pr in item.productions" :key="pr.id">
                        <td>{{ main.formateDate(pr.date) }}</td>
                        <td><v-chip size="x-small" color="purple" variant="tonal">{{ prodCatName(pr) }}</v-chip></td>
                        <td><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (pr.production_tracking_number||[])" :key="tn" size="x-small" color="deep-orange" variant="tonal">{{ tn }}</v-chip><span v-if="!(pr.production_tracking_number||[]).length" class="text-grey">-</span></div></td>
                        <td>{{ (pr.suppliers||[]).map(s=>s.name).join(', ') || '-' }}</td>
                        <td class="fnm-currency-num">{{ main.formatDisplayNumber(pr.total_weight) }}</td>
                        <td class="fnm-currency-num">{{ main.formatDisplayNumber(pr.total_price) }} {{ main.currency }}</td>
                      </tr></tbody>
                      <tfoot><tr class="lc-detail-foot"><td colspan="4" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold fnm-currency-num">{{ main.formatDisplayNumber(item.productions.reduce((s,p)=>s+Number(p.total_weight||0),0)) }}</td><td class="font-weight-bold fnm-currency-num">{{ main.formatDisplayNumber(item.productions.reduce((s,p)=>s+Number(p.total_price||0),0)) }} {{ main.currency }}</td></tr></tfoot>
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
          <tfoot class="lc-tfoot"><tr><td class="lc-td"></td><td v-for="h in headers" :key="'foot-'+h.key" class="lc-td text-center font-weight-bold"><template v-if="h.key==='total_weight'">Σ {{ main.formatDisplayNumber(totalWeight) }} Kg</template><template v-else-if="h.key==='total_price'">Σ {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</template></td></tr></tfoot>
        </table>
      </div>
    </div>
  </v-card-text>

  <!-- Delete Confirmation Dialog -->
  <v-dialog v-model="deleteDialog" max-width="450">
    <v-card class="rounded-xl">
      <v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
      <v-card-text class="pa-5 pt-2">{{ deleteBulk?$t('confirm_bulk_delete_message',{count:selected.length}):$t('confirm_delete_charge_message') }}</v-card-text>
      <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Summary Snackbar -->
  <v-snackbar v-for="(sb, i) in main.snackbars" :key="i" v-model="sb.show" :color="sb.color" :timeout="3000" location="bottom right">{{ sb.message }}</v-snackbar>
</v-card>
</template>

<script>
import script from './script.js';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import TableColumnFilter from '@/components/common/TableColumnFilter.vue';
import TableColumnManager from '@/components/common/TableColumnManager.vue';

export default {
  ...script,
  components: {
    ...script.components,
    SegmentedPillToggle,
    TableColumnFilter,
    TableColumnManager,
  },
};
</script>

<style scoped>
.lc-hero{background:linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%);color:#fff;padding:20px 24px;border-radius:16px 16px 0 0}
.lc-hero-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px}
.lc-hero-icon{width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.18);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center}
.lc-hero-title{font-size:22px;font-weight:700;letter-spacing:-.5px}
.lc-hero-kpi{display:flex;align-items:center;background:rgba(255,255,255,.15);backdrop-filter:blur(4px);padding:4px 12px;border-radius:20px;font-size:12px}
.lc-kpi-val{font-weight:700;margin-right:4px}
.lc-kpi-lbl{opacity:.8}
.lc-hero-actions{display:flex;gap:8px;align-items:center}
.lc-panel-card{border:1.5px solid rgba(230,81,0,.15);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.lc-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center;color:#E65100}
.lc-stats-panel{max-height:400px}
.lc-stat-item{padding:8px 10px;border-radius:10px;background:rgba(230,81,0,.03);border:1px solid rgba(230,81,0,.06)}
.lc-date-presets-wrapper{width:100%;overflow-x:auto;scrollbar-width:none}
.lc-selected-range{font-family:'Inter',sans-serif}

.lc-toolbar{background:rgba(var(--v-theme-surface),.8);border:1px solid rgba(var(--v-border-color),.12)}
.lc-table-wrap{
  overflow:auto;
  max-height:calc(100vh - 300px);
  border-radius:12px;
  border:1px solid rgba(var(--v-border-color),.12);
  scrollbar-width:thin!important;
  scrollbar-color:rgba(0,0,0,.16) transparent!important;
}
.lc-table-wrap:hover{scrollbar-color:rgba(0,0,0,.38) transparent!important}
.lc-table-wrap::-webkit-scrollbar{width:5px!important;height:5px!important}
.lc-table-wrap::-webkit-scrollbar-button{display:none!important;width:0!important;height:0!important}
.lc-table-wrap::-webkit-scrollbar-track{background:transparent!important;margin:3px!important}
.lc-table-wrap::-webkit-scrollbar-corner{background:transparent!important}
.lc-table-wrap::-webkit-scrollbar-thumb{background:rgba(0,0,0,.14)!important;border-radius:999px!important;transition:background .2s ease!important}
.lc-table-wrap:hover::-webkit-scrollbar-thumb,.lc-table-wrap::-webkit-scrollbar-thumb:hover{background:rgba(0,0,0,.36)!important}
.lc-table-wrap::-webkit-scrollbar-thumb:active{background:#E65100!important}

:deep(.v-theme--dark) .lc-table-wrap,
.v-theme--dark .lc-table-wrap{scrollbar-color:rgba(255,255,255,.16) transparent!important}
:deep(.v-theme--dark) .lc-table-wrap:hover,
.v-theme--dark .lc-table-wrap:hover{scrollbar-color:rgba(255,255,255,.38) transparent!important}
:deep(.v-theme--dark) .lc-table-wrap::-webkit-scrollbar-thumb,
.v-theme--dark .lc-table-wrap::-webkit-scrollbar-thumb{background:rgba(255,255,255,.16)!important}
:deep(.v-theme--dark) .lc-table-wrap:hover::-webkit-scrollbar-thumb,
:deep(.v-theme--dark) .lc-table-wrap::-webkit-scrollbar-thumb:hover,
.v-theme--dark .lc-table-wrap:hover::-webkit-scrollbar-thumb,
.v-theme--dark .lc-table-wrap::-webkit-scrollbar-thumb:hover{background:rgba(255,255,255,.38)!important}
:deep(.v-theme--dark) .lc-table-wrap::-webkit-scrollbar-thumb:active,
.v-theme--dark .lc-table-wrap::-webkit-scrollbar-thumb:active{background:#E65100!important}

.lc-table{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;table-layout:fixed}
.lc-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.lc-thead th{position:sticky;top:0}
.lc-th{position:sticky;top:0;padding:8px 8px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s;overflow:hidden;box-sizing:border-box}
.lc-th-actions{text-align:center!important;overflow:visible!important;width:92px!important;min-width:92px!important}
.lc-td{padding:8px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;box-sizing:border-box}
.lc-td-actions{overflow:visible!important;text-overflow:clip!important;padding:4px 3px!important;white-space:nowrap!important;width:92px!important;min-width:92px!important}
.lc-actions-wrapper{display:inline-flex;align-items:center;justify-content:center;gap:3px;white-space:nowrap;flex-wrap:nowrap}
.lc-actions-wrapper .v-btn{flex-shrink:0!important;width:26px!important;height:26px!important;min-width:26px!important;min-height:26px!important}
.lc-col-resizer{position:absolute;top:0;right:0;width:6px;height:100%;cursor:col-resize;user-select:none;z-index:6;background:transparent;transition:background .15s}
.lc-col-resizer:hover,.lc-col-resizer-active{background:#E65100!important;width:3px!important}
.lc-th-pin-btn{opacity:.65;transition:opacity .15s}.lc-th-pin-btn:hover{opacity:1}
.lc-active-sorts-strip{font-family:'Inter',sans-serif}
.lc-col-pin{position:sticky!important;z-index:2!important;background:#ffffff!important}
.lc-th-pin{position:sticky!important;top:0!important;z-index:8!important;background:#f8fafc!important}
:deep(.v-theme--dark) .lc-col-pin,
:deep(.v-theme--dark) .lc-th-pin,
.v-theme--dark .lc-col-pin,
.v-theme--dark .lc-th-pin {
  background: #1e293b !important;
  color: #f1f5f9 !important;
}
.lc-th-dragover{background:rgba(230,81,0,.08)!important}
.lc-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.lc-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#E65100;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.lc-row:hover{background:rgba(230,81,0,.04)}
.lc-row-expanded{background:rgba(230,81,0,.06)}
.lc-tfoot td{padding:10px 12px;border-top:2px solid rgba(230,81,0,.2);color:#E65100;font-size:14px}
:deep(.v-theme--dark) .lc-tfoot td,
.v-theme--dark .lc-tfoot td {
  color: #FFB74D !important;
}
.lc-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.lc-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow-x:auto}
.lc-detail-group-header{padding:8px 14px;background:rgba(230,81,0,.06);font-size:13px}
.lc-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.lc-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.lc-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.lc-detail-foot td{padding:8px 10px;background:rgba(230,81,0,.04);border-top:1px solid rgba(230,81,0,.15)}

/* Fullscreen Expanded Workspace Mode */
.lc-fullscreen-mode {
  position: fixed !important;
  top: var(--v-layout-top, 64px) !important;
  left: var(--v-layout-left, 64px) !important;
  right: 0 !important;
  bottom: 0 !important;
  width: auto !important;
  height: auto !important;
  z-index: 1000 !important;
  background: rgb(var(--v-theme-surface)) !important;
  display: flex !important;
  flex-direction: column !important;
  padding: 12px 16px !important;
  box-sizing: border-box !important;
  overflow: hidden !important;
  border-radius: 0 !important;
  box-shadow: none !important;
}
.lc-fullscreen-mode .lc-table-wrap {
  flex: 1 1 0% !important;
  height: 100% !important;
  max-height: none !important;
  overflow: auto !important;
}
.lc-fullscreen-header {
  background: rgba(var(--v-theme-surface), 0.98);
  border: 1px solid rgba(230, 81, 0, 0.25);
  box-shadow: 0 2px 12px rgba(230, 81, 0, 0.08);
  flex-shrink: 0;
}

/* Density Controls */
.density-comfortable .lc-td {
  padding: 12px 10px !important;
  font-size: 13px !important;
}
.density-comfortable .lc-th {
  padding: 10px 8px !important;
}
.density-compact .lc-td {
  padding: 6px 8px !important;
  font-size: 12px !important;
}
.density-compact .lc-th {
  padding: 8px 8px !important;
}
.density-dense .lc-td {
  padding: 3px 5px !important;
  font-size: 11px !important;
}
.density-dense .lc-th {
  padding: 5px 4px !important;
}
.density-dense .v-chip {
  height: 18px !important;
  font-size: 10px !important;
  padding: 0 5px !important;
}
.density-dense .lc-actions-wrapper .v-btn {
  width: 22px !important;
  height: 22px !important;
  min-width: 22px !important;
  min-height: 22px !important;
}
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
</style>
