<template>
<div>
  <v-card-text class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="12" md="3">
        <div class="li-panel-card">
          <div class="li-panel-header d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center">
              <v-icon size="16" color="#6D4C41" class="mr-1">mdi-calendar-range</v-icon>
              <span>{{ $t('date_range') }}</span>
            </div>
            <v-chip v-if="datePreset" size="x-small" color="#6D4C41" variant="tonal" class="font-weight-bold">
              {{ $t(datePreset) }}
            </v-chip>
          </div>
          <!-- Auto-select Date Range Segmented Pill Toggle -->
          <div class="mb-2 d-flex justify-center li-date-presets-wrapper">
            <SegmentedPillToggle
              v-model="datePreset"
              :items="datePresetOptions"
              color="#6D4C41"
              size="x-small"
              block
              @update:model-value="onDatePresetChange"
            />
          </div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="brown" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />

          <!-- Selected Date Range Display Below VCalendar -->
          <div class="li-selected-range mt-2 pt-2 border-t d-flex align-center justify-center ga-2 flex-nowrap">
            <v-chip size="x-small" color="#6D4C41" variant="tonal" class="font-weight-bold" :title="$t('start_date')">
              <v-icon start size="12">mdi-calendar-start</v-icon>
              {{ main.formateDate ? main.formateDate(dateRange[0]) : (dateRange[0] || '').toString().slice(0, 10) }}
            </v-chip>
            <v-icon size="12" color="grey">mdi-arrow-right</v-icon>
            <v-chip size="x-small" color="#6D4C41" variant="tonal" class="font-weight-bold" :title="$t('end_date')">
              <v-icon start size="12">mdi-calendar-end</v-icon>
              {{ main.formateDate ? main.formateDate(dateRange[1] || dateRange[0]) : (dateRange[1] || dateRange[0] || '').toString().slice(0, 10) }}
            </v-chip>
          </div>
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="12" md="6">
        <div class="li-panel-card" style="height:100%">
          <div class="li-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#6D4C41" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#6D4C41" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <div v-if="loading" class="d-flex flex-column justify-space-between" style="height: 260px; padding: 16px;">
            <div class="d-flex align-end justify-space-between ga-2" style="height: 200px; width: 100%;">
              <div v-for="b in 12" :key="'li-chart-skel-' + b" class="skel-bone" :style="{ width: '5%', height: (25 + (b * 19) % 65) + '%', borderRadius: '4px 4px 0 0' }"></div>
            </div>
            <div class="d-flex justify-space-between mt-2">
              <div v-for="t in 6" :key="'li-chart-lbl-' + t" class="skel-bone" style="width: 36px; height: 10px; border-radius: 4px;"></div>
            </div>
          </div>
          <apexchart v-else-if="chartSeries[0].data.length" type="line" :height="260" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="12" md="3">
        <div class="li-panel-card li-stats-panel" style="height:100%;overflow-y:auto">
          <div class="li-panel-header"><v-icon size="16" color="#6D4C41" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <div v-if="loading" class="d-flex flex-column ga-3 py-2">
            <div v-for="s in 3" :key="'inv-skel-stat-' + s" class="li-stat-item mb-2">
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
            <!-- Per-category stats -->
            <div v-for="(stat,si) in categoryStats" :key="si" class="li-stat-item mb-2">
              <div class="d-flex align-center justify-space-between mb-1">
                <v-chip size="x-small" color="#6D4C41" variant="tonal" class="font-weight-bold">{{ stat.name }} (<span class="fnm-currency-num">{{ stat.count }}</span>)</v-chip>
                <v-chip size="x-small" color="#6D4C41" variant="elevated" class="font-weight-bold fnm-currency-num">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
              </div>
              <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#6D4C41" bg-color="brown-lighten-4" height="5" rounded class="mb-1"></v-progress-linear>
              <div class="d-flex flex-column ga-1 text-caption">
                <div class="d-flex align-center ga-1"><v-icon size="12" color="#6D4C41">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color:#6D4C41" class="fnm-currency-num">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
                <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange fnm-currency-num">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
              </div>
            </div>
            <!-- Totals -->
            <v-divider class="my-2"></v-divider>
            <div class="d-flex flex-column ga-1">
              <div class="d-flex align-center ga-1"><v-icon size="14" color="#6D4C41">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b style="color:#6D4C41" class="text-caption fnm-currency-num">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption fnm-currency-num">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
            </div>
          </template>
        </div>
      </v-col>
    </v-row>

    <!-- Unified Command & Filter Toolbar -->
    <div class="li-toolbar mb-3 pa-2 px-3 rounded-xl">
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
            color="#6D4C41"
            prepend-inner-icon="mdi-magnify"
            :placeholder="$t('search_records_placeholder') || 'Search inventories...'"
            style="min-width: 200px; max-width: 280px;"
          ></v-text-field>

          <v-btn variant="tonal" color="#6D4C41" rounded size="small" prepend-icon="mdi-refresh" @click="fetchInventories" :loading="loading">{{ $t('refresh') }}</v-btn>

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
          <v-chip size="small" variant="tonal" color="#6D4C41" class="font-weight-medium">
            {{ colFilteredItems.length }} / {{ inventories.length }} {{ $t('records') }}
          </v-chip>

          <v-divider vertical class="mx-1 my-1" style="height: 22px;"></v-divider>

          <!-- Expand / Collapse All Details Button -->
          <v-btn
            :variant="isAllExpanded ? 'flat' : 'tonal'"
            :color="isAllExpanded ? 'warning' : '#6D4C41'"
            rounded="lg"
            size="small"
            class="li-filter-btn"
            :title="isAllExpanded ? $t('collapse_all') : $t('expand_all')"
            @click="toggleExpandAll"
          >
            <v-icon size="16" class="mr-1">{{ isAllExpanded ? 'mdi-arrow-collapse-vertical' : 'mdi-arrow-expand-vertical' }}</v-icon>
            {{ isAllExpanded ? $t('collapse_all') : $t('expand_all') }}
            <v-badge
              v-if="expandedCount > 0"
              :content="expandedCount"
              :color="isAllExpanded ? 'warning' : '#6D4C41'"
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
            :color="isExpandedView ? 'warning' : '#6D4C41'"
            rounded="lg"
            size="small"
            class="li-filter-btn font-weight-bold"
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
      class="li-table-container"
      :class="{
        'li-fullscreen-mode': isExpandedView,
        ['density-' + tableDensity]: true,
      }"
    >
      <!-- Dedicated Command Bar when Expanded View is Active -->
      <div v-if="isExpandedView" class="li-fullscreen-header mb-3 pa-2 px-3 rounded-xl">
        <div class="d-flex align-center justify-space-between flex-wrap ga-2">
          <!-- Left: Module Brand & Live KPI Chips -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <div class="d-flex align-center ga-2 mr-1">
              <v-avatar size="32" color="#6D4C41" variant="flat" rounded="lg">
                <v-icon size="18" color="white">mdi-warehouse</v-icon>
              </v-avatar>
              <div>
                <div class="text-caption font-weight-bold" style="line-height: 1.2; color: #6D4C41;">{{ $t('inventory') }}</div>
                <div class="text-caption text-medium-emphasis" style="font-size: 10px !important;">{{ $t('expand_view') }}</div>
              </div>
            </div>

            <v-chip size="small" variant="tonal" color="#6D4C41" class="font-weight-bold">
              <v-icon start size="14">mdi-counter</v-icon>
              {{ colFilteredItems.length }} / {{ inventories.length }} {{ $t('records') }}
            </v-chip>
            <v-chip size="small" variant="tonal" color="#6D4C41" class="font-weight-bold">
              <v-icon start size="14">mdi-weight-kilogram</v-icon>
              {{ main.formatDisplayNumber(totalWeight) }} Kg
            </v-chip>
            <v-chip size="small" variant="tonal" color="#6D4C41" class="font-weight-bold">
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
              color="#6D4C41"
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
                :color="tableDensity === 'comfortable' ? '#6D4C41' : 'grey'"
                :title="$t('density_comfortable')"
                @click="setTableDensity('comfortable')"
              >
                <v-icon size="15">mdi-view-headline</v-icon>
              </v-btn>
              <v-btn
                size="x-small"
                icon
                :variant="tableDensity === 'compact' ? 'flat' : 'text'"
                :color="tableDensity === 'compact' ? '#6D4C41' : 'grey'"
                :title="$t('density_compact')"
                @click="setTableDensity('compact')"
              >
                <v-icon size="15">mdi-view-agenda-outline</v-icon>
              </v-btn>
              <v-btn
                size="x-small"
                icon
                :variant="tableDensity === 'dense' ? 'flat' : 'text'"
                :color="tableDensity === 'dense' ? '#6D4C41' : 'grey'"
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
              :color="isAllExpanded ? 'warning' : '#6D4C41'"
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
              v-if="selected.length && auth.hasPermission('inventory.delete')"
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
      <div v-if="colSorts && colSorts.length > 0" class="li-active-sorts-strip d-flex align-center flex-wrap ga-1 px-3 py-1 mb-2 rounded-lg" style="background: rgba(109, 76, 65, 0.06); border: 1px dashed rgba(109, 76, 65, 0.3);">
        <span class="text-caption font-weight-bold d-flex align-center ga-1 mr-1" style="color: #6D4C41;">
          <v-icon size="14" color="#6D4C41">mdi-sort</v-icon>
          {{ $t('sorted_by') }}:
        </span>
        <v-chip
          v-for="(s, sIdx) in colSorts"
          :key="'sort_' + s.key"
          size="x-small"
          rounded="lg"
          color="#6D4C41"
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
      <div class="li-table-wrap" ref="tableWrap" @scroll.passive="onTableScroll">
        <table class="li-table">
          <thead class="li-thead"><tr>
            <th class="li-th li-th-pin" :style="thPinStyles['__checkbox']"><v-checkbox-btn v-model="selectAll" color="#6D4C41" density="compact" hide-details></v-checkbox-btn></th>
            <th
              v-for="h in headers"
              :key="h.key"
              class="li-th"
              :class="{'li-th-pin': isPinned(h.key) || h.fixed, 'li-th-dragover': dragOverCol === h.key, 'li-th-actions': h.key === 'actions'}"
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
                class="li-col-resizer"
                :class="{ 'li-col-resizer-active': activeResizeCol === h.key }"
                @mousedown.stop.prevent="startColResize(h.key, $event)"
                :title="$t('resize_column')"
              ></div>

              <div class="li-th-inner">
                <v-icon v-if="!h.fixed" size="12" class="drag-handle mr-1" color="grey-lighten-1">mdi-drag-vertical</v-icon>
                <span
                  class="li-th-title text-truncate"
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
                    <v-icon size="12" color="#6D4C41">mdi-arrow-{{ sortDir(h.key) === 'asc' ? 'up' : 'down' }}</v-icon>
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
                    color="#6D4C41"
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
                        class="li-th-pin-btn"
                        :title="$t('pin_column')"
                        @click.stop
                      >
                        <v-icon
                          size="11"
                          :color="pinSide(h.key) === 'left' ? '#6D4C41' : pinSide(h.key) === 'right' ? '#FF7043' : 'grey-lighten-1'"
                        >
                          {{ pinSide(h.key) === 'left' ? 'mdi-dock-left' : pinSide(h.key) === 'right' ? 'mdi-dock-right' : 'mdi-pin-outline' }}
                        </v-icon>
                      </v-btn>
                    </template>
                    <v-list density="compact" class="py-1 rounded-lg" elevation="6">
                      <v-list-item density="compact" :active="pinSide(h.key) === 'left'" @click="setPin(h.key, 'left')">
                        <template #prepend><v-icon size="15" color="#6D4C41">mdi-dock-left</v-icon></template>
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
              <tr v-for="r in 8" :key="'inv-skel-' + r" class="li-row app-skeleton-table-row">
                <td class="li-td text-center" :style="headerPinStyles['__checkbox']">
                  <div class="skel-bone mx-auto" style="width: 18px; height: 18px; border-radius: 4px;"></div>
                </td>
                <td v-for="h in headers" :key="'inv-skel-' + h.key + '-' + r" class="li-td" :class="{'li-col-pin': isPinned(h.key) || h.fixed, 'li-td-actions': h.key === 'actions'}" :style="headerPinStyles[h.key]">
                  <div v-if="h.key === 'expand'" class="skel-bone skel-circle mx-auto" style="width: 22px; height: 22px;"></div>
                  <div v-else-if="h.key === 'actions'" class="d-flex align-center justify-center ga-1">
                    <div v-for="a in 3" :key="a" class="skel-bone skel-circle" style="width: 20px; height: 20px;"></div>
                  </div>
                  <div v-else-if="h.key === '_tracking' || h.key.includes('status') || h.key.includes('type')" class="skel-bone" style="width: 85px; height: 20px; border-radius: 999px;"></div>
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
                <tr class="li-row" :class="{'li-row-expanded':expanded[item.id]}">
                  <td class="li-td li-col-pin" :style="headerPinStyles['__checkbox']"><v-checkbox-btn :model-value="selectedSet.has(item.id)" @update:model-value="toggleItemSelect(item.id)" color="#6D4C41" density="compact" hide-details></v-checkbox-btn></td>
                  <td v-for="h in headers" :key="h.key" class="li-td" :class="{'li-col-pin': isPinned(h.key) || h.fixed, 'li-td-actions': h.key === 'actions'}" :style="headerPinStyles[h.key]">
                    <!-- expand -->
                    <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                    <!-- date -->
                    <template v-else-if="h.key==='date'">{{ (item.date||'').slice(0,10) }}</template>
                    <!-- tracking chips -->
                    <template v-else-if="h.key==='_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.inventory_tracking_numbers||[])" :key="tn" size="x-small" color="#6D4C41" variant="tonal" class="fnm-currency-num">{{ tn }}</v-chip><span v-if="!(item.inventory_tracking_numbers||[]).length" class="text-grey">-</span></div></template>
                    <!-- weight/price -->
                    <template v-else-if="h.key==='total_weight'"><span class="fnm-currency-num">{{ main.formatDisplayNumber(item.total_weight) }}</span> Kg</template>
                    <template v-else-if="h.key==='total_price'"><span class="fnm-currency-num font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(item.total_price) }}</span> {{ main.currency }}</template>
                    <!-- details count -->
                    <template v-else-if="h.key==='_details_count'"><v-chip size="x-small" color="#6D4C41" variant="tonal" class="fnm-currency-num font-weight-bold">{{ item._details_count }}</v-chip></template>
                    <!-- actions -->
                    <template v-else-if="h.key==='actions'">
                      <div class="li-actions-wrapper">
                        <v-btn v-if="auth.hasPermission('inventory.edit')" icon size="x-small" variant="tonal" color="#6D4C41" @click="editInventory(item)"><v-icon size="14">mdi-pencil</v-icon></v-btn>
                        <v-btn v-if="auth.hasPermission('inventory.delete')" icon size="x-small" variant="tonal" color="warning" @click="confirmDelete(item.id)"><v-icon size="14">mdi-delete</v-icon></v-btn>
                        <v-btn icon size="x-small" variant="tonal" color="blue-grey" @click="openAuditLog(item.id)"><v-icon size="14">mdi-history</v-icon><v-tooltip activator="parent" location="top">{{ $t('audit_log') }}</v-tooltip></v-btn>
                      </div>
                    </template>
                    <!-- generic -->
                    <template v-else>{{ item[h.key] || '-' }}</template>
                  </td>
                </tr>
                <!-- Expanded detail rows -->
                <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
                  <div class="li-expand-content">
                    <div v-for="(grp,gi) in groupDetails(item)" :key="gi" class="li-detail-group mb-2">
                      <div class="li-detail-group-header"><v-icon size="16" color="#6D4C41" class="mr-1">mdi-tag</v-icon><b>{{ grp.category[`name_${lang}`]||grp.category.name_fr||grp.category.name_en||'-' }}</b></div>
                      <table class="li-detail-table"><thead><tr>
                        <th>{{ $t('type_article') }}</th>
                        <th>{{ $t('warehouse') }}</th>
                        <th>{{ $t('warehouse_operation') }}</th>
                        <th>{{ $t('weight') }} (Kg)</th>
                        <th>{{ $t('unit_price') }}</th>
                        <th>{{ $t('total_price') }}</th>
                      </tr></thead>
                      <tbody><tr v-for="di in grp.items" :key="di.id">
                        <td class="font-weight-bold">{{ articleName(di) }}</td>
                        <td><v-chip v-if="di.warehouse" size="x-small" variant="tonal">{{ di.warehouse.name }}</v-chip><span v-else>-</span></td>
                        <td><v-chip v-if="di.warehouse_operation" :color="di.warehouse_operation==='stock_in'?'success':'warning'" size="x-small" variant="tonal">{{ di.warehouse_operation }}</v-chip><span v-else>-</span></td>
                        <td class="fnm-currency-num">{{ main.formatDisplayNumber(di.weight) }}</td>
                        <td class="fnm-currency-num">{{ main.formatDisplayNumber(di.unit_price) }}</td>
                        <td class="fnm-currency-num">{{ main.formatDisplayNumber(di.total_price) }}</td>
                      </tr></tbody>
                      <tfoot><tr class="li-detail-foot"><td colspan="3" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold fnm-currency-num">{{ main.formatDisplayNumber(grp.totalW) }}</td><td></td><td class="font-weight-bold fnm-currency-num">{{ main.formatDisplayNumber(grp.totalP) }}</td></tr></tfoot>
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
          <tfoot class="li-tfoot"><tr><td class="li-td"></td><td v-for="h in headers" :key="'foot-'+h.key" class="li-td text-center font-weight-bold"><template v-if="h.key==='total_weight'">Σ <span class="fnm-currency-num">{{ main.formatDisplayNumber(totalWeight) }}</span> Kg</template><template v-else-if="h.key==='total_price'">Σ <span class="fnm-currency-num">{{ main.formatDisplayNumber(totalPrice) }}</span> {{ main.currency }}</template></td></tr></tfoot>
        </table>
      </div>
    </div>
  </v-card-text>

  <!-- Delete Dialog -->
  <v-dialog v-model="deleteDialog" max-width="450">
    <v-card class="rounded-xl">
      <v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
      <v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_message') }}</v-card-text>
      <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions>
    </v-card>
  </v-dialog>

  <AuditLogDialog v-model="auditLogDialogOpen" auditable-type="Inventory" :auditable-id="auditLogRecordId" module-color="#6D4C41" />

  <!-- Summary Snackbar -->
  <v-snackbar v-for="(sb, i) in main.snackbars" :key="i" v-model="sb.show" :color="sb.color" :timeout="3000" location="bottom right">{{ sb.message }}</v-snackbar>
</div>
</template>

<script>
import script from './listScript.js';
import AuditLogDialog from '@/components/shared/AuditLogDialog.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import TableColumnFilter from '@/components/common/TableColumnFilter.vue';
import TableColumnManager from '@/components/common/TableColumnManager.vue';

export default {
  ...script,
  components: {
    ...script.components,
    AuditLogDialog,
    SegmentedPillToggle,
    TableColumnFilter,
    TableColumnManager,
  },
  data() {
    return {
      ...script.data.call(this),
      auditLogDialogOpen: false,
      auditLogRecordId: 0,
    };
  },
  methods: {
    ...script.methods,
    openAuditLog(id) {
      this.auditLogRecordId = id;
      this.auditLogDialogOpen = true;
    },
  },
};
</script>

<style scoped>
.li-panel-card{border:1.5px solid rgba(109,76,65,.15);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.li-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center;color:#6D4C41}
.li-stats-panel{max-height:400px}
.li-stat-item{padding:8px 10px;border-radius:10px;background:rgba(109,76,65,.03);border:1px solid rgba(109,76,65,.06)}
.li-date-presets-wrapper{width:100%;overflow-x:auto;scrollbar-width:none}
.li-selected-range{font-family:'Inter',sans-serif}

.li-toolbar{background:rgba(var(--v-theme-surface),.8);border:1px solid rgba(var(--v-border-color),.12)}
.li-table-wrap{
  overflow:auto;
  max-height:calc(100vh - 300px);
  border-radius:12px;
  border:1px solid rgba(var(--v-border-color),.12);
  scrollbar-width:thin!important;
  scrollbar-color:rgba(0,0,0,.16) transparent!important;
}
.li-table-wrap:hover{scrollbar-color:rgba(0,0,0,.38) transparent!important}
.li-table-wrap::-webkit-scrollbar{width:5px!important;height:5px!important}
.li-table-wrap::-webkit-scrollbar-button{display:none!important;width:0!important;height:0!important}
.li-table-wrap::-webkit-scrollbar-track{background:transparent!important;margin:3px!important}
.li-table-wrap::-webkit-scrollbar-corner{background:transparent!important}
.li-table-wrap::-webkit-scrollbar-thumb{background:rgba(0,0,0,.14)!important;border-radius:999px!important;transition:background .2s ease!important}
.li-table-wrap:hover::-webkit-scrollbar-thumb,.li-table-wrap::-webkit-scrollbar-thumb:hover{background:rgba(0,0,0,.36)!important}
.li-table-wrap::-webkit-scrollbar-thumb:active{background:#6D4C41!important}

:deep(.v-theme--dark) .li-table-wrap,
.v-theme--dark .li-table-wrap{scrollbar-color:rgba(255,255,255,.16) transparent!important}
:deep(.v-theme--dark) .li-table-wrap:hover,
.v-theme--dark .li-table-wrap:hover{scrollbar-color:rgba(255,255,255,.38) transparent!important}
:deep(.v-theme--dark) .li-table-wrap::-webkit-scrollbar-thumb,
.v-theme--dark .li-table-wrap::-webkit-scrollbar-thumb{background:rgba(255,255,255,.16)!important}
:deep(.v-theme--dark) .li-table-wrap:hover::-webkit-scrollbar-thumb,
:deep(.v-theme--dark) .li-table-wrap::-webkit-scrollbar-thumb:hover,
.v-theme--dark .li-table-wrap:hover::-webkit-scrollbar-thumb,
.v-theme--dark .li-table-wrap::-webkit-scrollbar-thumb:hover{background:rgba(255,255,255,.38)!important}
:deep(.v-theme--dark) .li-table-wrap::-webkit-scrollbar-thumb:active,
.v-theme--dark .li-table-wrap::-webkit-scrollbar-thumb:active{background:#6D4C41!important}

.li-table{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;table-layout:fixed}
.li-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.li-thead th{position:sticky;top:0}
.li-th{position:sticky;top:0;padding:8px 8px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s;overflow:hidden;box-sizing:border-box}
.li-th-actions{text-align:center!important;overflow:visible!important;width:100px!important;min-width:100px!important}
.li-td{padding:8px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;box-sizing:border-box}
.li-td-actions{overflow:visible!important;text-overflow:clip!important;padding:4px 3px!important;white-space:nowrap!important;width:100px!important;min-width:100px!important}
.li-actions-wrapper{display:inline-flex;align-items:center;justify-content:center;gap:3px;white-space:nowrap;flex-wrap:nowrap}
.li-actions-wrapper .v-btn{flex-shrink:0!important;width:26px!important;height:26px!important;min-width:26px!important;min-height:26px!important}
.li-col-resizer{position:absolute;top:0;right:0;width:6px;height:100%;cursor:col-resize;user-select:none;z-index:6;background:transparent;transition:background .15s}
.li-col-resizer:hover,.li-col-resizer-active{background:#6D4C41!important;width:3px!important}
.li-th-pin-btn{opacity:.65;transition:opacity .15s}.li-th-pin-btn:hover{opacity:1}
.li-active-sorts-strip{font-family:'Inter',sans-serif}
.li-col-pin{position:sticky!important;z-index:2!important;background:#ffffff!important}
.li-th-pin{position:sticky!important;top:0!important;z-index:8!important;background:#f8fafc!important}
:deep(.v-theme--dark) .li-col-pin,
:deep(.v-theme--dark) .li-th-pin,
.v-theme--dark .li-col-pin,
.v-theme--dark .li-th-pin {
  background: #1e293b !important;
  color: #f1f5f9 !important;
}
.li-th-dragover{background:rgba(109,76,65,.08)!important}
.li-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.li-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#6D4C41;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.li-row:hover{background:rgba(109,76,65,.04)}
.li-row-expanded{background:rgba(109,76,65,.06)}
.li-tfoot td{padding:10px 12px;border-top:2px solid rgba(109,76,65,.2);color:#6D4C41;font-size:14px}
:deep(.v-theme--dark) .li-tfoot td,
.v-theme--dark .li-tfoot td {
  color: #BCAAA4 !important;
}
.li-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.li-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow-x:auto}
.li-detail-group-header{padding:8px 14px;background:rgba(109,76,65,.06);font-size:13px}
.li-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.li-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.li-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.li-detail-foot td{padding:8px 10px;background:rgba(109,76,65,.04);border-top:1px solid rgba(109,76,65,.15)}

/* Fullscreen Expanded Workspace Mode */
.li-fullscreen-mode {
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
.li-fullscreen-mode .li-table-wrap {
  flex: 1 1 0% !important;
  height: 100% !important;
  max-height: none !important;
  overflow: auto !important;
}
.li-fullscreen-header {
  background: rgba(var(--v-theme-surface), 0.98);
  border: 1px solid rgba(109, 76, 65, 0.25);
  box-shadow: 0 2px 12px rgba(109, 76, 65, 0.08);
  flex-shrink: 0;
}

/* Density Controls */
.density-comfortable .li-td {
  padding: 12px 10px !important;
  font-size: 13px !important;
}
.density-comfortable .li-th {
  padding: 10px 8px !important;
}
.density-compact .li-td {
  padding: 6px 8px !important;
  font-size: 12px !important;
}
.density-compact .li-th {
  padding: 8px 8px !important;
}
.density-dense .li-td {
  padding: 3px 5px !important;
  font-size: 11px !important;
}
.density-dense .li-th {
  padding: 5px 4px !important;
}
.density-dense .v-chip {
  height: 18px !important;
  font-size: 10px !important;
  padding: 0 5px !important;
}
.density-dense .li-actions-wrapper .v-btn {
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
