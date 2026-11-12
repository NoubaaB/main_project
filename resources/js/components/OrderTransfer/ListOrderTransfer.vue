<template>
  <div>
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="12" md="3">
        <div class="ot-panel">
          <div class="ot-panel-hdr d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center">
              <v-icon size="16" color="#00897B" class="mr-1">mdi-calendar-range</v-icon>
              <span>{{ $t('date_range') }}</span>
            </div>
            <v-chip v-if="datePreset" size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
              {{ $t(datePreset) }}
            </v-chip>
          </div>
          <!-- Auto-select Date Range Segmented Pill Toggle -->
          <div class="mb-2 d-flex justify-center ot-date-presets-wrapper">
            <SegmentedPillToggle
              v-model="datePreset"
              :items="datePresetOptions"
              color="#00897B"
              size="x-small"
              block
              @update:model-value="onDatePresetChange"
            />
          </div>
          <DatePicker v-model.range="dateRangeVC" :max-date="new Date()" color="teal" :is-dark="main.is_dark" expanded @update:model-value="onDateRangeChange" />

          <!-- Selected Date Range Display Below VCalendar -->
          <div class="ot-selected-range mt-2 pt-2 border-t d-flex align-center justify-center ga-2 flex-nowrap">
            <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold" :title="$t('start_date')">
              <v-icon start size="12">mdi-calendar-start</v-icon>
              {{ main.formateDate ? main.formateDate(dateRange[0]) : (dateRange[0] || '').toString().slice(0, 10) }}
            </v-chip>
            <v-icon size="12" color="grey">mdi-arrow-right</v-icon>
            <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold" :title="$t('end_date')">
              <v-icon start size="12">mdi-calendar-end</v-icon>
              {{ main.formateDate ? main.formateDate(dateRange[1] || dateRange[0]) : (dateRange[1] || dateRange[0] || '').toString().slice(0, 10) }}
            </v-chip>
          </div>
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="12" md="6">
        <div class="ot-panel" style="height:100%">
          <div class="ot-panel-hdr d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#00897B" variant="outlined" rounded="lg" divided>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries[0].data.length" type="bar" :height="260" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-bar</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="12" md="3">
        <div class="ot-panel ot-stats-panel" style="height:100%;overflow-y:auto">
          <div class="ot-panel-hdr"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <!-- Per-currency stats -->
          <div v-for="stat in currencyStats" :key="stat.name" class="ot-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold"><span class="fnm-currency-num">{{ stat.name }} ({{ stat.count }})</span></v-chip>
              <v-chip size="x-small" color="success" variant="elevated" class="font-weight-bold"><span class="fnm-currency-num">{{ formatNum(stat.total) }}</span></v-chip>
            </div>
            <v-progress-linear :model-value="totalAmount>0?(stat.total/totalAmount*100):0" color="#00897B" bg-color="teal-lighten-4" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="#00897B">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('amount') }}:</span><b class="text-success fnm-currency-num">{{ formatNum(stat.total) }} {{ stat.name }}</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-percent</v-icon><span class="text-medium-emphasis">{{ $t('share') }}:</span><b class="text-deep-orange fnm-currency-num">{{ totalAmount>0 ? Math.round(stat.total/totalAmount*1000)/10 : 0 }}%</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <!-- Totals -->
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#00897B">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total') }}:</span><b class="text-success text-caption fnm-currency-num">{{ colFilteredItems.length }} {{ $t('records') }}</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_amount') }}:</span><b class="text-deep-orange text-caption fnm-currency-num">{{ formatNum(totalAmount) }}</b></div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Unified Command & Filter Toolbar -->
    <div class="ot-toolbar mb-3 pa-2 px-3 rounded-xl">
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
            color="#00897B"
            prepend-inner-icon="mdi-magnify"
            :placeholder="$t('search_records_placeholder') || 'Search order transfers...'"
            style="min-width: 200px; max-width: 280px;"
          ></v-text-field>

          <v-btn variant="tonal" color="#00897B" rounded size="small" prepend-icon="mdi-refresh" @click="fetchTransfers" :loading="loading">{{ $t('refresh') }}</v-btn>

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

        <!-- Right Side: Records Counter, Print, Delete, Columns Manager, Export & Expand View -->
        <div class="d-flex align-center flex-wrap ga-2">
          <v-btn v-if="selectedIds.length" color="#00897B" variant="flat" rounded size="small" prepend-icon="mdi-printer" :loading="loadingBatchPrint" @click="loadPrintMultiple">{{ $t('print') }} ({{ selectedIds.length }})</v-btn>
          <v-btn v-if="selectedIds.length && auth.hasPermission('order_transfers.delete')" color="error" variant="tonal" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete') }} ({{ selectedIds.length }})</v-btn>

          <v-chip size="small" variant="tonal" color="#00897B" class="font-weight-medium">
            {{ colFilteredItems.length }} / {{ transfers.length }} {{ $t('records') }}
          </v-chip>

          <v-divider vertical class="mx-1 my-1" style="height: 22px;"></v-divider>

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
              <v-divider v-if="selectedIds.length" class="my-1"></v-divider>
              <v-list-item v-if="selectedIds.length" prepend-icon="mdi-file-excel-outline" @click="exportSelectedToExcel">
                <v-list-item-title class="text-caption font-weight-medium">
                  {{ $t('export_selected_only', { count: selectedIds.length }) }} (.xlsx)
                </v-list-item-title>
              </v-list-item>
              <v-list-item v-if="selectedIds.length" prepend-icon="mdi-file-delimited-outline" @click="exportSelectedToCsv">
                <v-list-item-title class="text-caption font-weight-medium">
                  {{ $t('export_selected_only', { count: selectedIds.length }) }} (.csv)
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>

          <!-- Expand View Button -->
          <v-btn
            :variant="isExpandedView ? 'flat' : 'elevated'"
            :color="isExpandedView ? 'warning' : '#00897B'"
            rounded="lg"
            size="small"
            class="ot-filter-btn font-weight-bold"
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
      class="ot-table-container"
      :class="{
        'ot-fullscreen-mode': isExpandedView,
        ['density-' + tableDensity]: true,
      }"
    >
      <!-- Dedicated Command Bar when Expanded View is Active -->
      <div v-if="isExpandedView" class="ot-fullscreen-header mb-3 pa-2 px-3 rounded-xl">
        <div class="d-flex align-center justify-space-between flex-wrap ga-2">
          <!-- Left: Module Brand & Live KPI Chips -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <div class="d-flex align-center ga-2 mr-1">
              <v-avatar size="32" color="#00897B" variant="flat" rounded="lg">
                <v-icon size="18" color="white">mdi-bank-transfer</v-icon>
              </v-avatar>
              <div>
                <div class="text-caption font-weight-bold" style="line-height: 1.2; color: #00897B;">{{ $t('order_transfers') }}</div>
                <div class="text-caption text-medium-emphasis" style="font-size: 10px !important;">{{ $t('expand_view') }}</div>
              </div>
            </div>

            <v-chip size="small" variant="tonal" color="#00897B" class="font-weight-bold">
              <v-icon start size="14">mdi-counter</v-icon>
              {{ colFilteredItems.length }} / {{ transfers.length }} {{ $t('records') }}
            </v-chip>
            <v-chip size="small" variant="tonal" color="#00897B" class="font-weight-bold">
              <v-icon start size="14">mdi-cash</v-icon>
              {{ formatNum(totalAmount) }}
            </v-chip>
          </div>

          <!-- Right: Search, Density, Print Selected, Columns Manager, Export, Bulk Actions & Exit -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-text-field
              v-model="searchQuery"
              density="compact"
              variant="outlined"
              rounded="lg"
              hide-details
              clearable
              color="#00897B"
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
                :color="tableDensity === 'comfortable' ? '#00897B' : 'grey'"
                :title="$t('density_comfortable')"
                @click="setTableDensity('comfortable')"
              >
                <v-icon size="15">mdi-view-headline</v-icon>
              </v-btn>
              <v-btn
                size="x-small"
                icon
                :variant="tableDensity === 'compact' ? 'flat' : 'text'"
                :color="tableDensity === 'compact' ? '#00897B' : 'grey'"
                :title="$t('density_compact')"
                @click="setTableDensity('compact')"
              >
                <v-icon size="15">mdi-view-agenda-outline</v-icon>
              </v-btn>
              <v-btn
                size="x-small"
                icon
                :variant="tableDensity === 'dense' ? 'flat' : 'text'"
                :color="tableDensity === 'dense' ? '#00897B' : 'grey'"
                :title="$t('density_dense')"
                @click="setTableDensity('dense')"
              >
                <v-icon size="15">mdi-view-sequential-outline</v-icon>
              </v-btn>
            </div>

            <!-- Print Batch Button -->
            <v-btn v-if="selectedIds.length" color="#00897B" variant="flat" rounded size="small" prepend-icon="mdi-printer" :loading="loadingBatchPrint" @click="loadPrintMultiple">{{ $t('print') }} ({{ selectedIds.length }})</v-btn>

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
                <v-divider v-if="selectedIds.length" class="my-1"></v-divider>
                <v-list-item v-if="selectedIds.length" prepend-icon="mdi-file-excel-outline" @click="exportSelectedToExcel">
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ $t('export_selected_only', { count: selectedIds.length }) }} (.xlsx)
                  </v-list-item-title>
                </v-list-item>
                <v-list-item v-if="selectedIds.length" prepend-icon="mdi-file-delimited-outline" @click="exportSelectedToCsv">
                  <v-list-item-title class="text-caption font-weight-medium">
                    {{ $t('export_selected_only', { count: selectedIds.length }) }} (.csv)
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>

            <!-- Bulk Delete -->
            <v-btn
              v-if="selectedIds.length && auth.hasPermission('order_transfers.delete')"
              variant="tonal"
              color="error"
              rounded="lg"
              size="small"
              prepend-icon="mdi-delete"
              @click="confirmBulkDelete"
            >
              {{ $t('delete_selected') }} ({{ selectedIds.length }})
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
      <div v-if="colSorts && colSorts.length > 0" class="ot-active-sorts-strip d-flex align-center flex-wrap ga-1 px-3 py-1 mb-2 rounded-lg" style="background: rgba(0, 137, 123, 0.06); border: 1px dashed rgba(0, 137, 123, 0.3);">
        <span class="text-caption font-weight-bold d-flex align-center ga-1 mr-1" style="color: #00897B;">
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

      <!-- Data Table -->
      <div class="ot-table-wrap" ref="tableWrap" @scroll.passive="onTableScroll">
        <table class="ot-table">
          <thead class="ot-thead">
            <tr>
              <th class="ot-th ot-th-pin" :style="thPinStyles['__checkbox']"><v-checkbox-btn :model-value="allSelected" :indeterminate="someSelected && !allSelected" color="#00897B" density="compact" hide-details @update:model-value="toggleSelectAll"></v-checkbox-btn></th>
              <th
                v-for="h in headers"
                :key="h.key"
                class="ot-th"
                :class="{'ot-th-pin': isPinned(h.key) || h.fixed, 'ot-th-dragover': dragOverCol === h.key, 'ot-th-actions': h.key === 'actions'}"
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
                  class="ot-col-resizer"
                  :class="{ 'ot-col-resizer-active': activeResizeCol === h.key }"
                  @mousedown.stop.prevent="startColResize(h.key, $event)"
                  :title="$t('resize_column')"
                ></div>

                <div class="ot-th-inner">
                  <v-icon v-if="!h.fixed" size="12" class="drag-handle mr-1" color="grey-lighten-1">mdi-drag-vertical</v-icon>
                  <span
                    class="ot-th-title text-truncate"
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
                    <span v-if="sortIdx(h.key) && colSorts && colSorts.length > 1" class="sort-badge">
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
                          class="ot-th-pin-btn"
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
            </tr>
          </thead>
          <tbody>
            <template v-if="loading">
              <tr v-for="r in 8" :key="'ot-skel-' + r" class="ot-row app-skeleton-table-row">
                <td class="ot-td text-center" :style="headerPinStyles['__checkbox']"><v-skeleton-loader type="chip" width="20" height="20" class="mx-auto app-skel-cell" /></td>
                <td v-for="h in headers" :key="'ot-skel-' + h.key + '-' + r" class="ot-td" :class="{'ot-col-pin': isPinned(h.key) || h.fixed, 'ot-td-actions': h.key === 'actions'}" :style="headerPinStyles[h.key]">
                  <v-skeleton-loader :type="h.key === 'transfer_number' ? 'chip' : 'text'" class="app-skel-cell" />
                </td>
              </tr>
            </template>
            <template v-else-if="colFilteredItems.length === 0"><tr><td :colspan="headers.length+1" class="text-center pa-8 text-grey">{{ $t('no_data_available') }}</td></tr></template>
            <template v-else>
              <tr v-if="virtualTopHeight > 0" class="virtual-spacer-row" :style="{ height: virtualTopHeight + 'px' }">
                <td :colspan="headers.length + 1" :style="{ height: virtualTopHeight + 'px', padding: 0, border: 'none' }"></td>
              </tr>
              <template v-for="item in visibleItems" :key="item.id">
                <tr class="ot-row">
                  <td class="ot-td ot-col-pin" :style="headerPinStyles['__checkbox']"><v-checkbox-btn :model-value="selectedSet.has(item.id)" color="#00897B" density="compact" hide-details @update:model-value="toggleItemSelect(item.id)"></v-checkbox-btn></td>
                  <td v-for="h in headers" :key="h.key" class="ot-td" :class="{'ot-col-pin': isPinned(h.key) || h.fixed, 'ot-td-actions': h.key === 'actions'}" :style="headerPinStyles[h.key]">
                    <template v-if="h.key==='transfer_number'"><v-chip color="#00897B" variant="tonal" size="small" class="font-weight-bold fnm-currency-num">{{ item.transfer_number }}</v-chip></template>
                    <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                    <template v-else-if="h.key==='amount'"><span class="font-weight-bold text-success fnm-currency-num">{{ formatNum(item.amount) }} {{ currencySymbol(item.currency) }}</span></template>
                    <template v-else-if="h.key==='currency'"><v-chip :color="item.currency==='MAD'?'#00897B':item.currency==='EUR'?'#1565C0':'#F57C00'" variant="tonal" size="small">{{ item.currency }}</v-chip></template>
                    <template v-else-if="h.key==='_beneficiaries'"><span class="text-caption">{{ item._beneficiaries }}</span></template>
                    <template v-else-if="h.key==='motif'"><span class="text-caption" style="max-width:200px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ item.motif || '-' }}</span></template>
                    <template v-else-if="h.key==='actions'">
                      <div class="ot-actions-wrapper">
                        <v-btn v-if="auth.hasPermission('order_transfers.edit')" icon size="x-small" variant="tonal" color="#00897B" class="mr-1" @click="editTransfer(item)"><v-icon size="14">mdi-pencil</v-icon></v-btn>
                        <v-btn icon size="x-small" variant="tonal" color="#00897B" class="mr-1" @click="loadPrintSingle(item)"><v-icon size="14">mdi-printer</v-icon></v-btn>
                        <v-btn v-if="auth.hasPermission('order_transfers.delete')" icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item.id)"><v-icon size="14">mdi-delete</v-icon></v-btn>
                      </div>
                    </template>
                    <template v-else>{{ item[h.key] || '-' }}</template>
                  </td>
                </tr>
              </template>
              <tr v-if="virtualBottomHeight > 0" class="virtual-spacer-row" :style="{ height: virtualBottomHeight + 'px' }">
                <td :colspan="headers.length + 1" :style="{ height: virtualBottomHeight + 'px', padding: 0, border: 'none' }"></td>
              </tr>
            </template>
          </tbody>
          <tfoot class="ot-tfoot"><tr><td class="ot-td"></td><td v-for="h in headers" :key="'f-'+h.key" class="ot-td text-center font-weight-bold"><template v-if="h.key==='amount'"><span class="fnm-currency-num">Σ {{ formatNum(totalAmount) }}</span></template></td></tr></tfoot>
        </table>
      </div>
    </div>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
        <v-card-text class="pa-5 pt-2">{{ deleteBulk ? $t('confirm_delete_selected_transfers') : $t('confirm_delete_transfer') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Print Trigger -->
    <div v-if="printTransfers.length" class="d-flex align-center ga-2 pa-3" style="position:fixed;bottom:20px;right:20px;z-index:999;background:rgba(0,137,123,.95);border-radius:16px;box-shadow:0 8px 32px rgba(0,137,123,.4)">
      <v-icon color="white" size="20">mdi-bank-transfer</v-icon>
      <span class="text-white text-body-2 font-weight-bold mr-2">{{ printTransfers.length === 1 ? printTransfers[0].transfer_number : printTransfers.length + ' ' + $t('order_transfers') }}</span>
      <v-btn v-print="printCfg" color="white" variant="flat" size="small" rounded prepend-icon="mdi-printer" style="color:#00897B">{{ $t('print') }}</v-btn>
      <v-btn icon size="x-small" variant="text" color="white" @click="printTransfers=[]"><v-icon size="18">mdi-close</v-icon></v-btn>
    </div>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420">
      <v-progress-linear :model-value="snackbarProgress" color="#00897B" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="summarySnackbar.isError ? 'error' : 'success'" class="mr-2">{{ summarySnackbar.isError ? 'mdi-alert-circle' : 'mdi-check-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show=false"></v-btn></template>
    </v-snackbar>
  </div>

  <!-- Hidden Print Area -->
  <div style="display:none"><div id="ot-print-area">
    <div v-for="(ot, idx) in printTransfers" :key="ot.id" :style="idx > 0 ? 'page-break-before:always' : ''" style="padding:30px 40px;max-width:800px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#1a1a1a">
      <!-- Logo + Company Name -->
      <div style="text-align:center;margin-bottom:20px">
        <img :src="logoUrl" alt="Logo" style="height:70px;margin-bottom:6px" />
        <div v-if="companySettings.company_name" style="font-size:24px;font-weight:800;color:#0277BD;font-family:'Segoe UI',Arial,sans-serif">{{ companySettings.company_name }}</div>
      </div>
      <!-- Title -->
      <div style="text-align:center;margin-bottom:24px"><div style="display:inline-block;border:2px solid #333;padding:8px 32px;font-size:16px;font-weight:700;letter-spacing:1px">ORDER DE VIRMENT</div></div>
      <!-- City + Date -->
      <div style="text-align:right;margin-bottom:20px;font-size:13px">{{ companySettings.company_origin || '' }}, {{ formatDateFrench(ot.date) }}</div>
      <!-- Guichet -->
      <div style="margin-bottom:8px"><b>Guichet :</b></div>
      <!-- Nom donneur d'ordre -->
      <div style="margin-bottom:8px"><span>Nom du donneur d'ordre : </span><b>{{ companySettings.company_name || '' }}</b></div>
      <!-- Veuillez virer -->
      <div style="margin-bottom:16px"><span>Veuillez virer par débit de nom compte N° :</span> <span v-if="companySettings.bank_account_number">{{ companySettings.bank_account_number }}</span></div>
      <!-- Montant -->
      <div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:10px">
        <span style="margin-right:20px">Montant :</span>
        <div style="border:2px solid #333;padding:6px 24px;font-weight:800;font-size:16px">{{ formatNum(ot.amount) }} {{ currencySymbol(ot.currency) }}</div>
      </div>
      <!-- La somme de -->
      <div style="display:flex;align-items:center;margin-bottom:14px">
        <span style="margin-right:12px">La somme de :</span>
        <div style="flex:1;border-bottom:1px solid #999;text-align:center;padding:4px 0;font-weight:700">{{ numberToFrenchWords(Number(ot.amount), ot.currency) }}</div>
      </div>
      <!-- En faveur de -->
      <div style="margin-bottom:10px"><span>En faveur de : </span><b>{{ (ot.beneficiary_names || []).map(b => b.name).join(', ') }}</b></div>
      <!-- Domicilié chez -->
      <div style="margin-bottom:16px"><span>Domicilié chez :</span></div>
      <!-- Compte N° -->
      <div style="text-align:center;margin-bottom:16px"><span>Compte N° :</span></div>
      <!-- Motif -->
      <div style="margin-bottom:40px"><span style="color:#0277BD;font-weight:700">Motif : </span><span style="text-align:center;display:inline-block;margin-left:80px">{{ ot.motif || '' }}</span></div>
      <!-- Signature -->
      <div style="text-align:right;margin-bottom:60px;font-size:13px">Signature du donneur l'ordre</div>
      <!-- Footer -->
      <div style="text-align:center;border-top:1px solid #ccc;padding-top:10px;font-size:11px;color:#666">
        <div v-if="companySettings.company_name">Sté {{ companySettings.company_name }} - Adresse : {{ companySettings.company_address || '' }}</div>
        <div>
          <span v-if="companySettings.capital">Capital de {{ companySettings.capital }} {{ currencySymbol('MAD') }}</span>
          <span v-if="companySettings.rc"> - RC : {{ companySettings.rc }}</span>
          <span v-if="companySettings.ice"> ICE :{{ companySettings.ice }}</span>
        </div>
      </div>
    </div>
  </div></div>
</template>

<script>
import script from './listScript.js';
import print from 'vue3-print-nb';
import VueApexCharts from 'vue3-apexcharts';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import TableColumnFilter from '@/components/common/TableColumnFilter.vue';
import TableColumnManager from '@/components/common/TableColumnManager.vue';

export default {
  ...script,
  directives: { print },
  components: {
    apexchart: VueApexCharts,
    SegmentedPillToggle,
    TableColumnFilter,
    TableColumnManager,
  },
};
</script>

<style scoped>
.ot-panel { border: 1.5px solid rgba(0,137,123,.15); border-radius: 14px; padding: 12px; background: rgba(var(--v-theme-surface), 1); }
.ot-panel-hdr { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; margin-bottom: 8px; display: flex; align-items: center; color: #00897B; }
.ot-stats-panel { max-height: 400px; }
.ot-stat-item { padding: 8px 10px; border-radius: 10px; background: rgba(0,137,123,.03); border: 1px solid rgba(0,137,123,.06); }
.ot-date-presets-wrapper { width: 100%; overflow-x: auto; scrollbar-width: none; }
.ot-selected-range { font-family: 'Inter', sans-serif; }

.ot-toolbar { background: rgba(var(--v-theme-surface), .8); border: 1px solid rgba(var(--v-border-color), .12); }
.ot-table-wrap {
  overflow: auto;
  max-height: calc(100vh - 300px);
  border-radius: 12px;
  border: 1px solid rgba(var(--v-border-color), .12);
  scrollbar-width: thin !important;
  scrollbar-color: rgba(0, 0, 0, .16) transparent !important;
}
.ot-table-wrap:hover { scrollbar-color: rgba(0, 0, 0, .38) transparent !important; }
.ot-table-wrap::-webkit-scrollbar { width: 5px !important; height: 5px !important; }
.ot-table-wrap::-webkit-scrollbar-button { display: none !important; width: 0 !important; height: 0 !important; }
.ot-table-wrap::-webkit-scrollbar-track { background: transparent !important; margin: 3px !important; }
.ot-table-wrap::-webkit-scrollbar-corner { background: transparent !important; }
.ot-table-wrap::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, .14) !important; border-radius: 999px !important; transition: background .2s ease !important; }
.ot-table-wrap:hover::-webkit-scrollbar-thumb, .ot-table-wrap::-webkit-scrollbar-thumb:hover { background: rgba(0, 0, 0, .36) !important; }
.ot-table-wrap::-webkit-scrollbar-thumb:active { background: #00897B !important; }

:deep(.v-theme--dark) .ot-table-wrap,
.v-theme--dark .ot-table-wrap { scrollbar-color: rgba(255, 255, 255, .16) transparent !important; }
:deep(.v-theme--dark) .ot-table-wrap:hover,
.v-theme--dark .ot-table-wrap:hover { scrollbar-color: rgba(255, 255, 255, .38) transparent !important; }
:deep(.v-theme--dark) .ot-table-wrap::-webkit-scrollbar-thumb,
.v-theme--dark .ot-table-wrap::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .16) !important; }
:deep(.v-theme--dark) .ot-table-wrap:hover::-webkit-scrollbar-thumb,
:deep(.v-theme--dark) .ot-table-wrap::-webkit-scrollbar-thumb:hover,
.v-theme--dark .ot-table-wrap:hover::-webkit-scrollbar-thumb,
.v-theme--dark .ot-table-wrap::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, .38) !important; }
:deep(.v-theme--dark) .ot-table-wrap::-webkit-scrollbar-thumb:active,
.v-theme--dark .ot-table-wrap::-webkit-scrollbar-thumb:active { background: #00897B !important; }

.ot-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; table-layout: fixed; }
.ot-thead { position: sticky; top: 0; z-index: 4; background: rgb(var(--v-theme-surface)); }
.ot-thead th { position: sticky; top: 0; }
.ot-th { position: sticky; top: 0; padding: 8px 8px; text-align: center; white-space: nowrap; border-bottom: 2px solid rgba(var(--v-border-color), .12); background: rgb(var(--v-theme-surface)); transition: background .15s; overflow: hidden; box-sizing: border-box; }
.ot-th-actions { text-align: center !important; overflow: visible !important; width: 100px !important; min-width: 100px !important; }
.ot-td { padding: 8px 10px; text-align: center; border-bottom: 1px solid rgba(var(--v-border-color), .06); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; box-sizing: border-box; }
.ot-td-actions { overflow: visible !important; text-overflow: clip !important; padding: 4px 3px !important; white-space: nowrap !important; width: 100px !important; min-width: 100px !important; }
.ot-actions-wrapper { display: inline-flex; align-items: center; justify-content: center; gap: 3px; white-space: nowrap; flex-wrap: nowrap; }
.ot-actions-wrapper .v-btn { flex-shrink: 0 !important; width: 26px !important; height: 26px !important; min-width: 26px !important; min-height: 26px !important; }
.ot-col-resizer { position: absolute; top: 0; right: 0; width: 6px; height: 100%; cursor: col-resize; user-select: none; z-index: 6; background: transparent; transition: background .15s; }
.ot-col-resizer:hover, .ot-col-resizer-active { background: #00897B !important; width: 3px !important; }
.ot-th-pin-btn { opacity: .65; transition: opacity .15s; } .ot-th-pin-btn:hover { opacity: 1; }
.ot-active-sorts-strip { font-family: 'Inter', sans-serif; }
.ot-col-pin { position: sticky !important; z-index: 2 !important; background: #ffffff !important; }
.ot-th-pin { position: sticky !important; top: 0 !important; z-index: 8 !important; background: #f8fafc !important; }
:deep(.v-theme--dark) .ot-col-pin,
:deep(.v-theme--dark) .ot-th-pin,
.v-theme--dark .ot-col-pin,
.v-theme--dark .ot-th-pin {
  background: #1e293b !important;
  color: #f1f5f9 !important;
}
.ot-th-dragover { background: rgba(0, 137, 123, .08) !important; }
.ot-th-inner { display: flex; align-items: center; justify-content: center; gap: 2px; }
.ot-th-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; opacity: .7; cursor: pointer; }
.drag-handle { cursor: grab; opacity: .5; transition: opacity .15s; } .drag-handle:hover { opacity: 1; }
.sort-badge { font-size: 9px; background: #00897B; color: #fff; border-radius: 50%; width: 14px; height: 14px; display: inline-flex; align-items: center; justify-content: center; }
.ot-row:hover { background: rgba(0, 137, 123, .04); }
.ot-tfoot td { border-top: 2px solid rgba(0, 137, 123, .2); padding: 10px 12px; color: #00897B; font-size: 14px; }
:deep(.v-theme--dark) .ot-tfoot td,
.v-theme--dark .ot-tfoot td {
  color: #80CBC4 !important;
}

/* Fullscreen Expanded Workspace Mode */
.ot-fullscreen-mode {
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
.ot-fullscreen-mode .ot-table-wrap {
  flex: 1 1 0% !important;
  height: 100% !important;
  max-height: none !important;
  overflow: auto !important;
}
.ot-fullscreen-header {
  background: rgba(var(--v-theme-surface), 0.98);
  border: 1px solid rgba(0, 137, 123, 0.25);
  box-shadow: 0 2px 12px rgba(0, 137, 123, 0.08);
  flex-shrink: 0;
}

/* Density Controls */
.density-comfortable .ot-td {
  padding: 12px 10px !important;
  font-size: 13px !important;
}
.density-comfortable .ot-th {
  padding: 10px 8px !important;
}
.density-compact .ot-td {
  padding: 6px 8px !important;
  font-size: 12px !important;
}
.density-compact .ot-th {
  padding: 8px 8px !important;
}
.density-dense .ot-td {
  padding: 3px 5px !important;
  font-size: 11px !important;
}
.density-dense .ot-th {
  padding: 5px 4px !important;
}
.density-dense .v-chip {
  height: 18px !important;
  font-size: 10px !important;
  padding: 0 5px !important;
}
.density-dense .ot-actions-wrapper .v-btn {
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
