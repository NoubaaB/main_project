<template>
<v-card :class="['wh-card', main.is_dark ? 'wh-dark' : 'wh-light']" elevation="0">
  <!-- Modern Hero Header per claude.md specifications -->
  <div class="wh-hero">
    <div class="wh-hero-inner">
      <div class="d-flex align-center justify-space-between flex-wrap ga-3">
        <!-- Hero Title & Identity -->
        <div class="d-flex align-center ga-3">
          <div class="wh-hero-icon">
            <v-icon size="28" color="white">mdi-warehouse</v-icon>
          </div>
          <div>
            <div class="d-flex align-center ga-2 flex-wrap">
              <span class="wh-hero-title">{{ tLabel('stock_par_lot', 'Stock Par N° de Suivi de Réception & Entrepôts') }}</span>
              <v-chip size="x-small" color="white" variant="outlined" class="font-weight-bold wh-live-chip">
                <span class="wh-pulse-dot mr-1"></span>
                {{ tLabel('live_stock_sync', 'Sync Live') }}
              </v-chip>
            </div>
            <div class="text-white text-caption mt-0.5 wh-hero-sub">
              {{ tLabel('stock_par_lot_desc', 'Centre névralgique du stock d\'usine • Gestion et traçabilité par numéro de suivi de réception') }}
            </div>
          </div>
        </div>

        <!-- Quick Hero KPI Badges (ListProduction style) -->
        <div class="d-flex align-center ga-3 flex-wrap">
          <div class="wh-hero-kpi">
            <span class="wh-hero-kpi-val">{{ lotsSummary.in_stock_lots }} / {{ lotsSummary.total_lots }}</span>
            <span class="wh-hero-kpi-lbl">{{ tLabel('active_lots', 'N° Suivi en Stock') }}</span>
          </div>
          <div class="wh-hero-kpi">
            <span class="wh-hero-kpi-val">{{ main.formatDisplayNumber(lotsSummary.total_remaining_weight) }} <small>Kg</small></span>
            <span class="wh-hero-kpi-lbl">{{ tLabel('remaining_weight_label', 'Poids Restant') }}</span>
          </div>
          <div class="wh-hero-kpi">
            <span class="wh-hero-kpi-val">{{ main.formatDisplayNumber(lotsSummary.total_valuation) }} <small>{{ main.currency }}</small></span>
            <span class="wh-hero-kpi-lbl">{{ tLabel('total_stock_value', 'Valorisation Stock') }}</span>
          </div>
        </div>

        <!-- Hero Actions -->
        <div class="wh-hero-actions d-flex align-center ga-2 flex-wrap">
          <!-- Density Menu Toggle -->
          <v-menu location="bottom end">
            <template #activator="{ props: menuProps }">
              <v-btn
                v-bind="menuProps"
                variant="tonal"
                color="white"
                rounded
                size="small"
                class="font-weight-bold"
                prepend-icon="mdi-view-headline"
              >
                {{ tLabel('density_toggle', 'Densité') }}: {{ tLabel('table_density_' + (tableDensity || 'spacious'), tableDensity || 'Spacieux') }}
                <v-icon end size="14">mdi-chevron-down</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" class="rounded-xl border pa-1" elevation="6">
              <v-list-item
                v-for="d in densityOptions"
                :key="d.value"
                :value="d.value"
                :active="tableDensity === d.value"
                @click="setTableDensity(d.value)"
                rounded="lg"
              >
                <template #prepend>
                  <v-icon :icon="d.icon" size="16" class="mr-2" :color="tableDensity === d.value ? '#a21ee5' : 'grey'"></v-icon>
                </template>
                <v-list-item-title class="font-weight-medium text-caption">{{ d.label }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>

          <v-btn
            variant="tonal"
            color="white"
            rounded
            size="small"
            prepend-icon="mdi-refresh"
            @click="refreshActiveView"
            :loading="lotsLoading || chambersLoading || speciesLoading || journalLoading"
            class="font-weight-bold"
          >
            {{ tLabel('refresh', 'Actualiser') }}
          </v-btn>

          <v-btn
            variant="tonal"
            color="white"
            rounded
            size="small"
            prepend-icon="mdi-file-excel"
            @click="exportToExcel"
            class="font-weight-bold"
          >
            Excel
          </v-btn>

          <v-btn
            variant="tonal"
            color="white"
            rounded
            size="small"
            prepend-icon="mdi-printer"
            @click="openPrintDialog(activeViewMode)"
            class="font-weight-bold"
            :title="tLabel('print', 'Imprimer')"
          >
            {{ tLabel('print', 'Imprimer') }}
          </v-btn>
        </div>
      </div>
    </div>
  </div>

  <v-card-text class="pa-3 pa-md-4">
    <!-- Spacious & Practical KPI Cards Strip -->
    <v-row dense class="mb-3">
      <!-- 1. Active Lots Card -->
      <v-col cols="12" sm="6" md="3">
        <div class="wh-spacious-kpi border">
          <div class="wh-spacious-kpi-icon" style="background: rgba(162, 30, 229, 0.12);">
            <v-icon color="#a21ee5" size="22">mdi-cube-outline</v-icon>
          </div>
          <div class="wh-spacious-kpi-info">
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="wh-spacious-kpi-label">{{ tLabel('active_lots', 'N° de Suivi en Stock') }}</span>
              <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold px-2">
                {{ lotsSummary.in_stock_lots }} / {{ lotsSummary.total_lots }}
              </v-chip>
            </div>
            <div class="wh-spacious-kpi-val" style="color: #a21ee5;">
              {{ lotsSummary.in_stock_lots }} <span class="wh-unit">{{ tLabel('lots', 'N° de Suivi') }}</span>
            </div>
            <div class="wh-spacious-kpi-sub mt-1">
              <v-icon size="12" color="grey" class="mr-1">mdi-archive-arrow-down-outline</v-icon>
              <span>{{ lotsSummary.depleted_lots }} {{ tLabel('depleted', 'épuisés') }}</span>
            </div>
          </div>
        </div>
      </v-col>

      <!-- 2. Remaining Weight Card -->
      <v-col cols="12" sm="6" md="3">
        <div class="wh-spacious-kpi border">
          <div class="wh-spacious-kpi-icon" style="background: rgba(0, 137, 123, 0.12);">
            <v-icon color="#00897B" size="22">mdi-weight-kilogram</v-icon>
          </div>
          <div class="wh-spacious-kpi-info">
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="wh-spacious-kpi-label">{{ tLabel('remaining_weight_label', 'Poids en Stock') }}</span>
              <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold px-2">
                {{ lotsSummary.total_initial_weight > 0 ? Math.round((lotsSummary.total_remaining_weight / lotsSummary.total_initial_weight) * 100) : 0 }}%
              </v-chip>
            </div>
            <div class="wh-spacious-kpi-val text-teal">
              {{ main.formatDisplayNumber(lotsSummary.total_remaining_weight) }} <span class="wh-unit">Kg</span>
            </div>
            <div class="wh-spacious-kpi-sub mt-1">
              <v-icon size="12" color="teal" class="mr-1">mdi-scale-balance</v-icon>
              <span>{{ tLabel('initial', 'Init.') }}: {{ main.formatDisplayNumber(lotsSummary.total_initial_weight) }} Kg</span>
            </div>
          </div>
        </div>
      </v-col>

      <!-- 3. Boxes / Caisses Card -->
      <v-col cols="12" sm="6" md="3">
        <div class="wh-spacious-kpi border">
          <div class="wh-spacious-kpi-icon" style="background: rgba(109, 76, 65, 0.12);">
            <v-icon color="#6D4C41" size="22">mdi-package-variant-closed</v-icon>
          </div>
          <div class="wh-spacious-kpi-info">
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="wh-spacious-kpi-label">{{ tLabel('caisses', 'Colis / Cartons') }}</span>
              <v-chip size="x-small" color="#6D4C41" variant="tonal" class="font-weight-bold px-2">
                {{ tLabel('boxes', 'Colis') }}
              </v-chip>
            </div>
            <div class="wh-spacious-kpi-val" style="color: #6D4C41;">
              {{ main.formatDisplayNumber(lotsSummary.total_initial_boxes) }} <span class="wh-unit">{{ tLabel('boxes', 'Cartons') }}</span>
            </div>
            <div class="wh-spacious-kpi-sub mt-1">
              <v-icon size="12" color="#6D4C41" class="mr-1">mdi-package-variant</v-icon>
              <span>{{ tLabel('packaging_and_boxes', 'Emballages & cartons enregistrés') }}</span>
            </div>
          </div>
        </div>
      </v-col>

      <!-- 4. Total Stock Valuation Card -->
      <v-col cols="12" sm="6" md="3">
        <div class="wh-spacious-kpi border">
          <div class="wh-spacious-kpi-icon" style="background: rgba(255, 152, 0, 0.12);">
            <v-icon color="#FF9800" size="22">mdi-cash-multiple</v-icon>
          </div>
          <div class="wh-spacious-kpi-info">
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="wh-spacious-kpi-label">{{ tLabel('total_stock_value', 'Valorisation Stock') }}</span>
              <v-chip size="x-small" color="#FF9800" variant="tonal" class="font-weight-bold px-2">
                WAP / PMP
              </v-chip>
            </div>
            <div class="wh-spacious-kpi-val" style="color: #FF9800;">
              {{ main.formatDisplayNumber(lotsSummary.total_valuation) }} <span class="wh-unit">{{ main.currency }}</span>
            </div>
            <div class="wh-spacious-kpi-sub mt-1">
              <v-icon size="12" color="#FF9800" class="mr-1">mdi-snowflake</v-icon>
              <span>{{ occupiedChambersCount }} {{ tLabel('occupied_chambers', 'chambres occupées') }}</span>
            </div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Navigation Switcher with SegmentedPillToggle -->
    <div class="wh-nav-bar mb-3 pa-1 rounded-xl border bg-surface d-flex align-center justify-space-between flex-wrap ga-2">
      <SegmentedPillToggle
        v-model="activeViewMode"
        :items="viewModeOptions"
        color="#a21ee5"
        size="medium"
        @update:modelValue="onViewModeChange"
      />

      <div class="d-flex align-center ga-2 pr-2">
        <v-chip size="small" color="#a21ee5" variant="tonal" class="font-weight-bold">
          <v-icon size="14" class="mr-1">mdi-pound</v-icon>
          <span v-if="activeViewMode === 'stock_par_lot'">{{ lotsPagination.total || lots.length }} {{ tLabel('lots', 'N° de Suivi') }}</span>
          <span v-else-if="activeViewMode === 'chambers'">{{ chambersList.length }} {{ tLabel('warehouses', 'Chambres') }}</span>
          <span v-else-if="activeViewMode === 'species'">{{ speciesList.length }} {{ tLabel('species', 'Espèces') }}</span>
          <span v-else-if="activeViewMode === 'journal'">{{ journalTotals.total_count || journalList.length }} {{ tLabel('movements', 'Mouvements') }}</span>
          <span v-else>{{ tLabel('analytics', 'Analytique') }}</span>
        </v-chip>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 1: STOCK PAR LOT (Explorateur de N° de Suivi de Réception & Traçabilité) -->
    <!-- ========================================================================= -->
    <div v-if="activeViewMode === 'stock_par_lot'" class="wh-view-container">
      <!-- Practical & Spacious Command & Filter Toolbar -->
      <v-card class="wh-filter-toolbar pa-3 pa-md-4 mb-3 rounded-2xl border" elevation="0">
        <!-- Row 1: Search, Status Toggle, Sort, Expand & Density Actions -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-3">
          <!-- Search input with AddCharge style: compact, outlined, rounded-lg, color=#a21ee5 -->
          <v-text-field
            v-model="searchQuery"
            density="compact"
            variant="outlined"
            rounded="lg"
            color="#a21ee5"
            hide-details
            clearable
            :placeholder="tLabel('search_lot_hint', 'Rechercher par N° Suivi de Réception, Bateau, Fournisseur, Espèce...')"
            prepend-inner-icon="mdi-magnify"
            class="wh-search-input text-warehouse flex-grow-1"
            style="min-width: 260px; max-width: 420px;"
            @update:modelValue="fetchStockParLot(true)"
          />

          <!-- Status toggle using SegmentedPillToggle -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <span class="text-caption text-medium-emphasis font-weight-bold d-none d-xl-inline">{{ tLabel('status', 'Statut') }}:</span>
            <SegmentedPillToggle
              v-model="filterStatus"
              :items="statusToggleOptions"
              color="#a21ee5"
              size="small"
              @update:modelValue="fetchStockParLot(true)"
            />
          </div>

          <!-- Sort dropdown with AddCharge style -->
          <v-select
            v-model="sortBy"
            :items="sortOptions"
            item-title="label"
            item-value="value"
            density="compact"
            variant="outlined"
            rounded="lg"
            color="#a21ee5"
            hide-details
            class="wh-select-compact text-warehouse"
            style="min-width: 195px;"
            @update:modelValue="fetchStockParLot(true)"
          >
            <template #prepend-inner>
              <v-icon size="16" color="#a21ee5">mdi-sort</v-icon>
            </template>
          </v-select>

          <!-- Table Controls: Expand/Collapse All & Density & Reset -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-btn
              size="small"
              variant="tonal"
              color="#a21ee5"
              rounded="lg"
              class="font-weight-bold"
              @click="toggleExpandAllLots"
            >
              <v-icon size="15" class="mr-1">{{ isAllLotsExpanded ? 'mdi-arrow-collapse-vertical' : 'mdi-arrow-expand-vertical' }}</v-icon>
              {{ isAllLotsExpanded ? tLabel('collapse_all', 'Tout Replier') : tLabel('expand_all', 'Tout Déplier') }}
              <v-badge
                v-if="expandedLotsCount > 0"
                :content="expandedLotsCount"
                color="#a21ee5"
                inline
                class="ml-1"
              />
            </v-btn>

            <!-- Reset Filters Button if Active -->
            <v-btn
              v-if="hasActiveFilters"
              size="small"
              variant="tonal"
              color="warning"
              rounded="lg"
              prepend-icon="mdi-filter-off"
              class="font-weight-bold"
              @click="resetAllFilters"
            >
              {{ tLabel('reset_all_filters', 'Réinitialiser') }}
              <v-badge :content="activeFilterCount" color="warning" inline class="ml-1" />
            </v-btn>

            <!-- Print Layout Button -->
            <v-btn
              size="small"
              variant="tonal"
              color="#a21ee5"
              rounded="lg"
              prepend-icon="mdi-printer"
              class="font-weight-bold"
              @click="openPrintDialog('stock_par_lot')"
            >
              {{ tLabel('print', 'Imprimer') }}
            </v-btn>
          </div>
        </div>

        <!-- Row 2: Chamber Filter with AddCharge style -->
        <div class="d-flex align-center ga-2 flex-wrap pt-1">
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
            :placeholder="tLabel('select_warehouses', 'Filtrer par chambre froide...')"
            prepend-inner-icon="mdi-snowflake"
            class="wh-input text-warehouse flex-grow-1"
            @update:modelValue="onWarehouseChange"
          >
            <template #chip="{ item, props }">
              <v-chip v-bind="props" size="small" color="#a21ee5" variant="tonal" class="font-weight-medium my-0.5" closable>
                <v-icon start size="13">mdi-snowflake</v-icon>
                {{ item.title }}
              </v-chip>
            </template>
          </v-autocomplete>

          <v-btn size="small" variant="tonal" color="#a21ee5" rounded="lg" class="font-weight-bold" @click="selectAllWarehouses">
            {{ tLabel('select_all', 'Toutes') }}
          </v-btn>
          <v-btn size="small" variant="text" color="grey" rounded="lg" class="font-weight-bold" @click="clearAllWarehouses">
            {{ tLabel('clear_all', 'Vider') }}
          </v-btn>
        </div>

        <!-- Row 2.5: Multi-Select Article Types Filter (FishType / GuanoType / PackageType) -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-2 pt-2 mt-1 border-t">
          <div class="d-flex align-center ga-2 flex-wrap">
            <span class="text-caption font-weight-bold text-warehouse d-flex align-center ga-1" style="font-size: 11.5px;">
              <v-icon size="14" color="#a21ee5">mdi-layers-outline</v-icon>
              {{ tLabel('filter_by_article_type', 'Types d\'Articles') }}:
            </span>
            <v-chip
              v-for="typeOpt in articleTypeOptions"
              :key="typeOpt.value"
              size="small"
              rounded="lg"
              :color="filterStockableTypes.includes(typeOpt.value) ? typeOpt.color : 'grey'"
              :variant="filterStockableTypes.includes(typeOpt.value) ? 'elevated' : 'outlined'"
              class="font-weight-bold cursor-pointer"
              @click="toggleStockableTypeFilter(typeOpt.value)"
            >
              <v-icon size="13" class="mr-1">{{ typeOpt.icon }}</v-icon>
              {{ typeOpt.label }}
              <v-icon v-if="filterStockableTypes.includes(typeOpt.value)" size="12" class="ml-1">mdi-check</v-icon>
            </v-chip>
            <v-chip
              v-if="filterStockableTypes.length"
              size="x-small"
              variant="text"
              color="grey"
              class="font-weight-bold cursor-pointer"
              @click="clearStockableTypesFilter"
            >
              <v-icon size="12" class="mr-0.5">mdi-close</v-icon>
              {{ tLabel('clear', 'Effacer') }}
            </v-chip>
          </div>
          <div v-if="filterStockableTypes.length" class="text-caption text-medium-emphasis">
            <span>{{ filterStockableTypes.length }} {{ tLabel('types_selected', 'types sélectionnés') }}</span>
          </div>
        </div>

        <!-- Row 3: Quick Access Chamber Pills -->
        <div v-if="warehousesWithStockList.length" class="d-flex align-center ga-2 flex-wrap pt-2 mt-1 border-t">
          <span class="text-caption text-medium-emphasis mr-1 font-weight-bold" style="font-size: 11px;">
            <v-icon size="12" class="mr-0.5">mdi-flash</v-icon>
            {{ tLabel('warehouses_with_stock_quick', 'Accès Rapide') }}:
          </span>
          <v-chip
            v-for="wh in warehousesWithStockList.slice(0, 8)"
            :key="wh.id"
            size="small"
            rounded="lg"
            :color="selectedWarehouseIds.includes(wh.id) ? '#a21ee5' : 'grey'"
            :variant="selectedWarehouseIds.includes(wh.id) ? 'elevated' : 'outlined'"
            class="font-weight-bold cursor-pointer"
            @click="selectWarehouseDirect(wh.id)"
          >
            <v-icon size="12" class="mr-1">mdi-snowflake</v-icon>
            {{ wh.name }}
            <span class="ml-1 opacity-75 font-weight-regular">({{ main.formatDisplayNumber(wh.total_stock || wh.total_weight) }} Kg)</span>
          </v-chip>
        </div>

        <!-- Row 4: Real-time Filtered Totals Strip -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-2 pt-2 mt-2 border-t text-caption text-medium-emphasis">
          <div class="d-flex align-center ga-3 flex-wrap">
            <span>
              <b>{{ (lotsPagination.current_page - 1) * lotsPagination.per_page + 1 }} - {{ Math.min(lotsPagination.current_page * lotsPagination.per_page, lotsPagination.total) }}</b>
              {{ tLabel('of', 'sur') }} <b>{{ lotsPagination.total }}</b> {{ tLabel('lots', 'N° de Suivi') }}
            </span>
            <span>•</span>
            <span>
              {{ tLabel('remaining_weight_label', 'Poids Page') }}:
              <b class="text-teal font-weight-bold">{{ main.formatDisplayNumber(pageFilteredWeight) }} Kg</b>
            </span>
            <span>•</span>
            <span>
              {{ tLabel('total_stock_value', 'Valeur Page') }}:
              <b style="color: #FF9800;" class="font-weight-bold">{{ main.formatDisplayNumber(pageFilteredValuation) }} {{ main.currency }}</b>
            </span>
          </div>

          <div class="d-flex align-center ga-1">
            <span class="text-caption font-weight-medium mr-1">{{ tLabel('table_density', 'Densité') }}:</span>
            <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold text-uppercase">
              {{ tLabel('table_density_' + (tableDensity || 'spacious'), tableDensity || 'Spacieux') }}
            </v-chip>
          </div>
        </div>
      </v-card>

      <!-- Table Loading State -->
      <div v-if="lotsLoading" class="d-flex flex-column align-center justify-center pa-12 wh-table-card rounded-2xl border bg-surface">
        <v-progress-circular indeterminate color="#a21ee5" size="52" width="4" />
        <span class="text-caption text-medium-emphasis mt-3 font-weight-bold">{{ tLabel('loading', 'Chargement des numéros de suivi de réception...') }}</span>
      </div>

      <!-- REDESIGNED PRACTICAL & SPACIOUS DATA TABLE -->
      <div v-else-if="lots.length" class="wh-table-card rounded-2xl border bg-surface overflow-hidden">
        <!-- Active Multi-Column Filters Bar for Stock Par Lot -->
        <div v-if="hasActiveLotColFilters" class="wh-active-filters-bar d-flex align-center justify-space-between flex-wrap ga-2 px-3 py-2 border-b bg-surface">
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-icon size="15" color="#a21ee5">mdi-filter-check</v-icon>
            <span class="text-caption font-weight-bold text-medium-emphasis">
              {{ tLabel('active_filters', 'Filtres actifs') }} ({{ activeLotColFiltersCount }}):
            </span>
            <template v-for="(vals, colKey) in lotColFilters" :key="colKey">
              <v-chip
                v-if="vals && vals.length"
                size="x-small"
                color="#a21ee5"
                variant="tonal"
                closable
                class="font-weight-medium"
                @click:close="clearLotColFilter(colKey)"
              >
                <b>{{ getLotColTitle(colKey) }}:</b>
                <span class="ml-1">{{ formatLotColFilterDisplay(colKey, vals) }}</span>
              </v-chip>
            </template>
          </div>
          <v-btn
            size="x-small"
            variant="text"
            color="warning"
            prepend-icon="mdi-filter-off"
            class="font-weight-bold"
            @click="clearAllLotColFilters"
          >
            {{ tLabel('clear_all_filters', 'Effacer tous les filtres') }}
          </v-btn>
        </div>

        <div class="wh-table-wrap">
          <table :class="['wh-modern-table wh-lot-main-table', 'density-' + tableDensity]">
            <thead>
              <tr>
                <!-- 1. Expand Chevron column -->
                <th style="width: 48px;" class="text-center"></th>

                <!-- 2. Lot Number (Clickable Sort & Multi-Filter) -->
                <th
                  class="wh-sort-th text-left"
                  :class="{ 'is-sorted': isSorted('lot'), 'has-filter-active': hasLotColFilter('lot_number') }"
                  style="min-width: 205px;"
                  :title="tLabel('click_to_sort', 'Cliquer pour trier')"
                >
                  <div class="d-flex align-center justify-space-between w-100 ga-1">
                    <div class="d-flex align-center ga-2 cursor-pointer flex-grow-1" @click="onSortColumn('lot')">
                      <v-icon size="15" color="#a21ee5">mdi-barcode-scan</v-icon>
                      <span>{{ tLabel('lot_number', 'N° Suivi Réception') }}</span>
                      <v-icon size="14">{{ getSortIcon('lot') }}</v-icon>
                    </div>
                    <TableColumnFilter
                      v-model="lotColFilters.lot_number"
                      :options="lotFilterOpts('lot_number')"
                      :title="tLabel('lot_number', 'N° Suivi Réception')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 3. Status (Clickable Sort & Multi-Filter) -->
                <th
                  class="wh-sort-th text-center"
                  :class="{ 'is-sorted': isSorted('status'), 'has-filter-active': hasLotColFilter('status') }"
                  style="min-width: 145px;"
                  :title="tLabel('click_to_sort', 'Cliquer pour trier')"
                >
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <div class="d-flex align-center justify-center ga-2 cursor-pointer" @click="onSortColumn('status')">
                      <v-icon size="14" color="#a21ee5">mdi-shield-check-outline</v-icon>
                      <span>{{ tLabel('status', 'Statut') }}</span>
                      <v-icon size="14">{{ getSortIcon('status') }}</v-icon>
                    </div>
                    <TableColumnFilter
                      v-model="lotColFilters.status"
                      :options="lotFilterOpts('status')"
                      :title="tLabel('status', 'Statut')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 4. Date & Age (Clickable Sort & Multi-Filter) -->
                <th
                  class="wh-sort-th text-center"
                  :class="{ 'is-sorted': isSorted('date') || isSorted('age'), 'has-filter-active': hasLotColFilter('date') }"
                  style="min-width: 165px;"
                  :title="tLabel('click_to_sort', 'Cliquer pour trier')"
                >
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <div class="d-flex align-center justify-center ga-2 cursor-pointer" @click="onSortColumn('date')">
                      <v-icon size="14" color="#a21ee5">mdi-calendar-range</v-icon>
                      <span>{{ tLabel('date', 'Réception') }} &amp; {{ tLabel('age_days', 'Âge') }}</span>
                      <v-icon size="14">{{ getSortIcon('date') }}</v-icon>
                    </div>
                    <TableColumnFilter
                      v-model="lotColFilters.date"
                      :options="lotFilterOpts('date')"
                      :title="tLabel('date', 'Date Réception')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 5. Supplier & Vessel/Origin (Clickable Sort & Multi-Filter) -->
                <th
                  class="wh-sort-th text-left"
                  :class="{ 'is-sorted': isSorted('supplier'), 'has-filter-active': hasLotColFilter('supplier') }"
                  style="min-width: 220px;"
                  :title="tLabel('click_to_sort', 'Cliquer pour trier')"
                >
                  <div class="d-flex align-center justify-space-between w-100 ga-1">
                    <div class="d-flex align-center ga-2 cursor-pointer flex-grow-1" @click="onSortColumn('supplier')">
                      <v-icon size="14" color="#a21ee5">mdi-domain</v-icon>
                      <span>{{ tLabel('suppliers', 'Fournisseur') }} / {{ tLabel('boat_fishery', 'Bateau') }}</span>
                      <v-icon size="14">{{ getSortIcon('supplier') }}</v-icon>
                    </div>
                    <TableColumnFilter
                      v-model="lotColFilters.supplier"
                      :options="lotFilterOpts('supplier')"
                      :title="tLabel('suppliers', 'Fournisseur')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 6. Chamber Location (Multi-Filter) -->
                <th class="text-center" :class="{ 'has-filter-active': hasLotColFilter('chamber') }" style="min-width: 155px;">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <div class="d-flex align-center justify-center ga-2">
                      <v-icon size="14" color="#a21ee5">mdi-snowflake</v-icon>
                      <span>{{ tLabel('chamber_location', 'Chambre Froide') }}</span>
                    </div>
                    <TableColumnFilter
                      v-model="lotColFilters.chamber"
                      :options="lotFilterOpts('chamber')"
                      :title="tLabel('chamber_location', 'Chambre Froide')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 7. Contained Species (Multi-Filter) -->
                <th class="text-left" :class="{ 'has-filter-active': hasLotColFilter('species') }" style="min-width: 215px;">
                  <div class="d-flex align-center justify-space-between w-100 ga-1">
                    <div class="d-flex align-center ga-2">
                      <v-icon size="14" color="#a21ee5">mdi-fish</v-icon>
                      <span>{{ tLabel('contained_species', 'Espèces / Articles') }}</span>
                    </div>
                    <TableColumnFilter
                      v-model="lotColFilters.species"
                      :options="lotFilterOpts('species')"
                      :title="tLabel('contained_species', 'Espèces / Articles')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 8. Remaining Stock Balance (Clickable Sort & Multi-Filter) -->
                <th
                  class="wh-sort-th text-right"
                  :class="{ 'is-sorted': isSorted('rem_weight'), 'has-filter-active': hasLotColFilter('rem_weight') }"
                  style="min-width: 195px;"
                  :title="tLabel('click_to_sort', 'Cliquer pour trier')"
                >
                  <div class="d-flex align-center justify-end w-100 ga-1">
                    <TableColumnFilter
                      v-model="lotColFilters.rem_weight"
                      :options="lotFilterOpts('rem_weight')"
                      :title="tLabel('remaining', 'Solde en Stock')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      class="mr-1"
                      @click.stop
                    />
                    <div class="d-flex align-center ga-2 cursor-pointer" @click="onSortColumn('rem_weight')">
                      <v-icon size="14" color="#a21ee5">mdi-scale</v-icon>
                      <span>{{ tLabel('remaining', 'Solde en Stock') }} (Kg)</span>
                      <v-icon size="14">{{ getSortIcon('rem_weight') }}</v-icon>
                    </div>
                  </div>
                </th>

                <!-- 9. Packages / Boxes (Clickable Sort & Multi-Filter) -->
                <th
                  class="wh-sort-th text-center"
                  :class="{ 'is-sorted': isSorted('boxes'), 'has-filter-active': hasLotColFilter('boxes') }"
                  style="min-width: 130px;"
                  :title="tLabel('click_to_sort', 'Cliquer pour trier')"
                >
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <div class="d-flex align-center justify-center ga-2 cursor-pointer" @click="onSortColumn('boxes')">
                      <v-icon size="14" color="#a21ee5">mdi-package-variant-closed</v-icon>
                      <span>{{ tLabel('package_count', 'Cartons') }}</span>
                      <v-icon size="14">{{ getSortIcon('boxes') }}</v-icon>
                    </div>
                    <TableColumnFilter
                      v-model="lotColFilters.boxes"
                      :options="lotFilterOpts('boxes')"
                      :title="tLabel('package_count', 'Cartons')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 10. Valuation & WAP (Clickable Sort & Multi-Filter) -->
                <th
                  class="wh-sort-th text-right"
                  :class="{ 'is-sorted': isSorted('valuation'), 'has-filter-active': hasLotColFilter('valuation') }"
                  style="min-width: 185px;"
                  :title="tLabel('click_to_sort', 'Cliquer pour trier')"
                >
                  <div class="d-flex align-center justify-end w-100 ga-1">
                    <TableColumnFilter
                      v-model="lotColFilters.valuation"
                      :options="lotFilterOpts('valuation')"
                      :title="tLabel('total_price', 'Valeur')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      class="mr-1"
                      @click.stop
                    />
                    <div class="d-flex align-center ga-2 cursor-pointer" @click="onSortColumn('valuation')">
                      <v-icon size="14" color="#a21ee5">mdi-cash-multiple</v-icon>
                      <span>{{ tLabel('total_price', 'Valeur') }} &amp; {{ tLabel('wap', 'PMP') }}</span>
                      <v-icon size="14">{{ getSortIcon('valuation') }}</v-icon>
                    </div>
                  </div>
                </th>

                <!-- 11. Actions -->
                <th class="text-center" style="width: 96px;">
                  <span>{{ tLabel('actions', 'Actions') }}</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <template v-for="lot in filteredLots" :key="lot.raw_lot">
                <tr
                  :class="['wh-lot-row cursor-pointer', { 'is-expanded': expandedLots[lot.lot_number], 'is-depleted': lot.status === 'depleted' }]"
                  @click="toggleLotExpand(lot.lot_number)"
                >
                  <!-- 1. Expand Chevron Button -->
                  <td class="text-center">
                    <div class="wh-chevron-box" :class="{ 'is-active': expandedLots[lot.lot_number] }">
                      <v-icon size="18" :color="expandedLots[lot.lot_number] ? '#a21ee5' : 'grey'">
                        {{ expandedLots[lot.lot_number] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
                      </v-icon>
                    </div>
                  </td>

                  <!-- 2. Lot Number & Articles Subtitle (Spacious Vertical Separation) -->
                  <td class="text-left">
                    <div class="wh-tracking-identity-cell d-flex flex-column py-1">
                      <!-- Top: Unified Lot Badge with Copy Icon -->
                      <div
                        class="wh-lot-id-badge cursor-pointer"
                        @click.stop="copyTrackingNumber(lot.lot_number)"
                        :title="tLabel('click_to_copy', 'Cliquer pour copier le N° de suivi')"
                      >
                        <v-icon size="14" color="#a21ee5" class="wh-lot-barcode-icon mr-1.5">mdi-barcode</v-icon>
                        <span class="wh-lot-number">#{{ lot.lot_number }}</span>
                        <v-icon size="13" color="#a21ee5" class="wh-copy-icon ml-1.5">mdi-content-copy</v-icon>
                      </div>

                      <!-- Bottom: Distinct Articles Metadata Tag with Clear Space -->
                      <div class="wh-lot-articles-meta d-flex align-center ga-1.5 flex-wrap">
                        <span class="wh-articles-pill">
                          <v-icon size="12" color="#a21ee5" class="mr-1.5">mdi-layers-triple-outline</v-icon>
                          <b>{{ lot.articles_count }}</b>
                          <span class="ml-1 opacity-80">{{ lot.articles_count > 1 ? tLabel('articles_plural', 'articles') : tLabel('article_singular', 'article') }}</span>
                        </span>
                        <div v-if="lot.contained_types && lot.contained_types.length" class="d-flex align-center ga-1">
                          <v-chip
                            v-for="ct in lot.contained_types"
                            :key="ct"
                            size="x-small"
                            variant="tonal"
                            :color="ct === 'FishType' ? '#a21ee5' : (ct === 'GuanoType' ? '#6D4C41' : '#00897B')"
                            class="px-1 font-weight-bold"
                            style="font-size: 10px; height: 18px;"
                          >
                            {{ articleTypeLabel(ct) }}
                          </v-chip>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- 3. Dedicated Status Column (High Readability & Balanced Positioning) -->
                  <td class="text-center">
                    <div class="d-flex align-center justify-center">
                      <div
                        :class="['wh-status-badge', 'status-' + (lot.status || 'in_stock')]"
                        :title="lotStatusLabel(lot.status)"
                      >
                        <v-icon size="13" class="wh-status-icon mr-1.5">
                          {{ lot.status === 'in_stock' ? 'mdi-check-circle' : (lot.status === 'partially_out' ? 'mdi-progress-clock' : 'mdi-archive-check') }}
                        </v-icon>
                        <span class="wh-status-text">{{ lotStatusLabel(lot.status) }}</span>
                      </div>
                    </div>
                  </td>

                  <!-- 4. Reception Date & Age (Spacious Separation) -->
                  <td class="text-center">
                    <div class="wh-date-age-cell py-1">
                      <div class="wh-date-row d-flex align-center justify-center ga-2 font-weight-bold text-body-2">
                        <v-icon size="14" color="#a21ee5" style="opacity: 0.7;">mdi-calendar-blank-outline</v-icon>
                        <span>{{ lot.first_date ? main.formateDate(lot.first_date) : '-' }}</span>
                      </div>
                      <div
                        :class="['wh-age-badge', getAgeClass(lot.age_days)]"
                        :title="Math.round(lot.age_days || 0) + ' ' + tLabel('days_in_warehouse', 'jours en entrepôt')"
                      >
                        <v-icon size="11" class="mr-1">mdi-clock-outline</v-icon>
                        <span>{{ Math.round(lot.age_days || 0) }} {{ tLabel('days_short', 'j') }}</span>
                      </div>
                    </div>
                  </td>

                  <!-- 5. Supplier & Vessel / Fishery / Origin (Spacious Avatar & Texts) -->
                  <td class="text-left">
                    <div class="wh-supplier-cell py-1">
                      <v-avatar size="36" color="rgba(162, 30, 229, 0.08)" class="wh-supplier-avatar rounded-xl flex-shrink-0 border">
                        <v-icon size="18" color="#a21ee5">mdi-domain</v-icon>
                      </v-avatar>
                      <div class="wh-supplier-info overflow-hidden" style="max-width: 230px;">
                        <span class="wh-supplier-name text-truncate" :title="lot.supplier_name">
                          {{ lot.supplier_name || '-' }}
                        </span>
                        <div
                          v-if="lot.fishery_name !== '-' || lot.origin_name !== '-'"
                          class="wh-vessel-meta text-truncate d-flex align-center ga-1 text-caption"
                          :title="[lot.fishery_name, lot.origin_name].filter(x => x && x !== '-').join(' • ')"
                        >
                          <v-icon size="12" color="#00897B" class="flex-shrink-0">mdi-ferry</v-icon>
                          <span class="text-truncate">{{ [lot.fishery_name, lot.origin_name].filter(x => x && x !== '-').join(' • ') }}</span>
                        </div>
                        <div v-else class="wh-vessel-meta direct text-caption d-flex align-center ga-1">
                          <v-icon size="11" color="grey" class="flex-shrink-0">mdi-truck-outline</v-icon>
                          <span>{{ tLabel('direct_reception', 'Réception directe') }}</span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- 6. Chamber Location (Refined Cold Storage Pill) -->
                  <td class="text-center">
                    <div class="d-flex flex-column align-center ga-1 py-1">
                      <div class="wh-chamber-badge">
                        <v-icon size="13" color="#a21ee5" class="mr-1.5">mdi-snowflake</v-icon>
                        <span class="font-weight-bold">{{ lot.primary_warehouse }}</span>
                      </div>
                      <span
                        v-if="lot.warehouses && lot.warehouses.length > 1"
                        class="wh-multi-chambers-tag text-caption font-weight-medium"
                        :title="lot.warehouses.join(', ')"
                      >
                        +{{ lot.warehouses.length - 1 }} {{ tLabel('other_chambers', 'autre(s)') }}
                      </span>
                    </div>
                  </td>

                  <!-- 7. Contained Species Preview (Clean Fish Tags with Tooltips) -->
                  <td class="text-left">
                    <div class="wh-species-container d-flex align-center ga-1 flex-wrap py-1" style="max-width: 250px;">
                      <div
                        v-for="(sp, sidx) in (lot.species_list || []).slice(0, 3)"
                        :key="sidx"
                        class="wh-species-tag"
                        :title="sp"
                      >
                        <v-icon size="11" color="#00897B" class="mr-1">mdi-fish</v-icon>
                        <span class="text-truncate" style="max-width: 90px;">{{ sp }}</span>
                      </div>
                      <div
                        v-if="(lot.species_list || []).length > 3"
                        class="wh-species-overflow-tag"
                        :title="(lot.species_list || []).slice(3).join(', ')"
                      >
                        +{{ lot.species_list.length - 3 }}
                      </div>
                    </div>
                  </td>

                  <!-- 8. Remaining Stock Balance with Sleek Ratio Bar -->
                  <td class="text-right">
                    <div class="wh-balance-cell d-flex flex-column align-end py-1">
                      <div class="wh-remaining-weight font-weight-bold text-teal" style="font-size: 14.5px; line-height: 1.2;">
                        {{ main.formatDisplayNumber(lot.remaining_weight) }} <span class="text-caption font-weight-bold">Kg</span>
                      </div>
                      <div class="wh-progress-wrap w-100 my-1.5" style="max-width: 140px;">
                        <v-progress-linear
                          :model-value="100 - lot.consumption_pct"
                          :color="lot.status === 'in_stock' ? '#00897B' : (lot.status === 'partially_out' ? '#FB8C00' : '#9E9E9E')"
                          height="5"
                          rounded
                          bg-opacity="0.15"
                        />
                      </div>
                      <div class="wh-sub-weight-meta text-caption text-medium-emphasis font-weight-medium" style="font-size: 11px;">
                        <span class="opacity-80">{{ tLabel('initial', 'Init.') }}:</span>
                        <b>{{ main.formatDisplayNumber(lot.initial_weight) }}</b> Kg
                        <span class="opacity-70 ml-1">({{ lot.consumption_pct }}% {{ tLabel('consumed', 'sorti') }})</span>
                      </div>
                    </div>
                  </td>

                  <!-- 9. Packaging Boxes -->
                  <td class="text-center">
                    <div class="wh-boxes-badge">
                      <v-icon size="13" color="#6D4C41" class="mr-1">mdi-package-variant-closed</v-icon>
                      <span class="font-weight-bold">{{ main.formatDisplayNumber(lot.remaining_boxes) }}</span>
                    </div>
                  </td>

                  <!-- 10. Valuation & WAP -->
                  <td class="text-right">
                    <div class="wh-valuation-cell d-flex flex-column align-end py-1">
                      <div class="font-weight-bold text-deep-orange" style="font-size: 13.5px; line-height: 1.2;">
                        {{ main.formatDisplayNumber(lot.total_valuation) }} <span class="text-caption font-weight-bold">{{ main.currency }}</span>
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1" style="font-size: 11px;">
                        <span>{{ tLabel('wap_short', 'PMP') }}:</span>
                        <b style="color: #a21ee5; margin-left: 2px;">{{ lot.avg_unit_price > 0 ? main.formatDisplayNumber(lot.avg_unit_price) : '-' }}</b> /Kg
                      </div>
                    </div>
                  </td>

                  <!-- 11. Actions -->
                  <td class="text-center text-no-wrap">
                    <div class="d-flex align-center justify-center ga-2">
                      <v-btn
                        icon
                        variant="tonal"
                        size="small"
                        color="#a21ee5"
                        @click.stop="openTraceability(lot)"
                        :title="tLabel('lot_traceability', 'Fiche Traçabilité Complète du N° de Suivi de Réception')"
                        class="wh-action-btn"
                        style="width: 32px; height: 32px;"
                      >
                        <v-icon size="16">mdi-timeline-text-outline</v-icon>
                      </v-btn>

                      <v-btn
                        icon
                        variant="tonal"
                        size="small"
                        color="#00897B"
                        @click.stop="switchToTraceabilityWithLot(lot)"
                        :title="tLabel('view_traceability', 'Traçabilité Chronologique Plein Écran')"
                        class="wh-action-btn"
                        style="width: 32px; height: 32px;"
                      >
                        <v-icon size="16">mdi-timeline-clock-outline</v-icon>
                      </v-btn>

                      <v-btn
                        v-if="lot.articles && lot.articles[0]"
                        icon
                        variant="tonal"
                        size="small"
                        color="#1565C0"
                        @click.stop="goToSource(lot.articles[0])"
                        :title="tLabel('view_source', 'Voir Document Réception')"
                        class="wh-action-btn"
                        style="width: 32px; height: 32px;"
                      >
                        <v-icon size="16">mdi-open-in-new</v-icon>
                      </v-btn>
                    </div>
                  </td>
                </tr>

                <!-- EXPANDED LOT BREAKDOWN PANEL (Spacious & Highly Detailed) -->
                <tr v-if="expandedLots[lot.lot_number]">
                  <td colspan="11" class="pa-0">
                    <div class="wh-lot-expanded-panel pa-4 pa-md-5">
                      <!-- Sub-panel Header -->
                      <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
                        <div class="d-flex align-center ga-2">
                          <v-avatar size="28" color="#a21ee5" variant="tonal" rounded="lg">
                            <v-icon size="16" color="#a21ee5">mdi-format-list-bulleted</v-icon>
                          </v-avatar>
                          <div>
                            <span class="text-subtitle-2 font-weight-bold text-warehouse">
                              {{ tLabel('lot_articles', 'Articles & Espèces du N° de Suivi de Réception') }} #{{ lot.lot_number }}
                            </span>
                            <span class="text-caption text-medium-emphasis ml-2">
                              ({{ lot.articles.length }} {{ tLabel('lines', 'lignes d\'articles') }})
                            </span>
                          </div>
                        </div>

                        <v-btn
                          size="small"
                          variant="tonal"
                          color="#a21ee5"
                          prepend-icon="mdi-timeline-outline"
                          rounded="lg"
                          class="font-weight-bold"
                          @click="openTraceability(lot)"
                        >
                          {{ tLabel('lot_pedigree', 'Fiche Traçabilité Complète') }}
                        </v-btn>
                      </div>

                      <!-- Sub-articles Data Table -->
                      <div class="wh-sub-table-container rounded-xl border overflow-hidden">
                        <table class="wh-sub-articles-table">
                          <thead>
                            <tr>
                              <th style="width: 36px;"></th>
                              <th class="text-left">{{ tLabel('article_type_name', 'Désignation Article') }}</th>
                              <th class="text-center">{{ tLabel('fish_family', 'Famille') }}</th>
                              <th class="text-center">{{ tLabel('process', 'Traitement') }}</th>
                              <th class="text-center">{{ tLabel('warehouse', 'Chambre') }}</th>
                              <th class="text-right">{{ tLabel('initial_weight_label', 'Entrée') }} (Kg)</th>
                              <th class="text-right">{{ tLabel('remaining_weight_label', 'Solde Restant') }} (Kg)</th>
                              <th class="text-center">{{ tLabel('package_count', 'Cartons') }}</th>
                              <th class="text-right">{{ tLabel('wap', 'PMP') }}</th>
                              <th class="text-right">{{ tLabel('total_price', 'Valeur') }}</th>
                              <th class="text-center">{{ tLabel('actions', 'Sorties') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <template v-for="art in lot.articles" :key="art.id">
                              <tr :class="['wh-sub-art-row', { 'is-depleted': art.is_depleted }]">
                                <!-- Sub Expand chevron for outflows history -->
                                <td class="text-center">
                                  <div
                                    v-if="(art.operations || []).length"
                                    class="wh-mini-chevron-box cursor-pointer"
                                    @click="toggleArticleExpand(art.id)"
                                  >
                                    <v-icon
                                      size="15"
                                      :color="expandedArticles[art.id] ? '#a21ee5' : 'grey'"
                                    >
                                      {{ expandedArticles[art.id] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
                                    </v-icon>
                                  </div>
                                </td>

                                <!-- Article name -->
                                <td class="text-left font-weight-bold">
                                  <div class="d-flex align-center ga-2 py-1">
                                    <v-icon size="16" :color="tColor(art.clean_type)">{{ tIcon(art.clean_type) }}</v-icon>
                                    <span class="text-body-2 font-weight-bold">{{ art.article_name }}</span>
                                  </div>
                                </td>

                                <td class="text-center text-caption">{{ art.fish_family || '-' }}</td>
                                <td class="text-center text-caption">{{ art.process || '-' }}</td>
                                <td class="text-center text-caption font-weight-medium">
                                  <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-medium">
                                    {{ art.warehouse_name }}
                                  </v-chip>
                                </td>
                                <td class="text-right text-caption font-weight-medium">{{ main.formatDisplayNumber(art.initial_weight) }} Kg</td>
                                <td class="text-right font-weight-bold text-teal" style="font-size: 13.5px;">{{ main.formatDisplayNumber(art.remaining_weight) }} Kg</td>
                                <td class="text-center text-caption font-weight-bold" style="color: #6D4C41;">{{ main.formatDisplayNumber(art.remaining_boxes) }}</td>
                                <td class="text-right text-caption font-weight-bold" style="color: #a21ee5;">{{ art.wap > 0 ? main.formatDisplayNumber(art.wap) : '-' }}</td>
                                <td class="text-right font-weight-bold text-deep-orange" style="font-size: 13px;">{{ main.formatDisplayNumber(art.total_price) }} {{ main.currency }}</td>
                                <td class="text-center">
                                  <v-btn
                                    v-if="(art.operations || []).length"
                                    size="x-small"
                                    variant="tonal"
                                    color="error"
                                    rounded="lg"
                                    class="font-weight-bold"
                                    @click="toggleArticleExpand(art.id)"
                                  >
                                    <v-icon start size="12">mdi-arrow-up-circle</v-icon>
                                    {{ art.operations.length }} {{ tLabel('stock_out', 'Sorties') }}
                                  </v-btn>
                                  <span v-else class="text-caption text-medium-emphasis">{{ tLabel('intact', 'Intact') }}</span>
                                </td>
                              </tr>

                              <!-- SUB-EXPANDED OUTFLOW MOVEMENTS HISTORY -->
                              <tr v-if="expandedArticles[art.id]">
                                <td colspan="11" class="pa-2 px-3">
                                  <div class="wh-ops-history-box pa-3 rounded-xl border">
                                    <div class="d-flex align-center justify-space-between mb-2">
                                      <div class="d-flex align-center ga-2 text-caption font-weight-bold text-error">
                                        <v-icon size="15" color="error">mdi-arrow-up-bold-circle</v-icon>
                                        <span>{{ tLabel('outgoing_movements', 'Historique des Sorties / Expéditions') }} ({{ art.operations.length }})</span>
                                      </div>
                                    </div>

                                    <table class="wh-mini-ops-table">
                                      <thead>
                                        <tr>
                                          <th>{{ tLabel('date', 'Date') }}</th>
                                          <th>{{ tLabel('source', 'Opération') }}</th>
                                          <th>{{ tLabel('client', 'Destinataire / Client') }}</th>
                                          <th class="text-right">{{ tLabel('weight', 'Poids Sorti') }} (Kg)</th>
                                          <th class="text-center">{{ tLabel('package_count', 'Cartons') }}</th>
                                          <th class="text-center">{{ tLabel('actions', 'Lien Document') }}</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <tr v-for="op in art.operations" :key="op.id">
                                          <td class="font-weight-medium">{{ main.formateDate(op.date) }}</td>
                                          <td>
                                            <v-chip size="x-small" :color="srcColor(op.source_type)" variant="tonal" class="font-weight-bold">
                                              {{ srcLabel(op.source_type) }} #{{ op.source_id }}
                                            </v-chip>
                                          </td>
                                          <td>{{ op.client_name || '-' }}</td>
                                          <td class="text-right font-weight-bold text-error">-{{ main.formatDisplayNumber(op.weight) }} Kg</td>
                                          <td class="text-center font-weight-bold text-error">-{{ op.package_count || 0 }}</td>
                                          <td class="text-center">
                                            <v-btn icon variant="tonal" size="x-small" color="#1565C0" @click="goToSourceOp(op)">
                                              <v-icon size="13">mdi-open-in-new</v-icon>
                                            </v-btn>
                                          </td>
                                        </tr>
                                      </tbody>
                                    </table>
                                  </div>
                                </td>
                              </tr>
                            </template>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center pa-12 wh-table-card rounded-2xl border bg-surface">
        <v-icon size="64" color="#a21ee5" style="opacity: 0.35;" class="mb-3">mdi-cube-off-outline</v-icon>
        <div class="text-h6 font-weight-bold">{{ tLabel('empty_lot_message', 'Aucun numéro de suivi de réception ne correspond aux critères de recherche') }}</div>
        <div class="text-caption text-medium-emphasis mt-1 mb-4">{{ tLabel('try_adjusting_filters', 'Essayez d\'ajuster vos critères de recherche ou filtres de statut') }}</div>
        <v-btn
          v-if="hasActiveFilters"
          variant="tonal"
          color="#a21ee5"
          rounded="lg"
          prepend-icon="mdi-filter-off"
          class="font-weight-bold"
          @click="resetAllFilters"
        >
          {{ tLabel('reset_all_filters', 'Réinitialiser Tous les Filtres') }}
        </v-btn>
      </div>

      <!-- Lot Pagination Bar -->
      <div class="d-flex align-center justify-space-between flex-wrap ga-2 pt-4">
        <div class="text-caption text-medium-emphasis">
          {{ tLabel('showing', 'Affichage') }} <b>{{ (lotsPagination.current_page - 1) * lotsPagination.per_page + 1 }}</b> -
          <b>{{ Math.min(lotsPagination.current_page * lotsPagination.per_page, lotsPagination.total) }}</b>
          {{ tLabel('of', 'sur') }} <b>{{ lotsPagination.total }}</b> {{ tLabel('lots', 'N° de suivi') }}
        </div>

        <v-pagination
          v-model="lotsPagination.current_page"
          :length="lotsPagination.last_page"
          density="comfortable"
          rounded="circle"
          active-color="#a21ee5"
          total-visible="7"
          @update:modelValue="fetchStockParLot(false)"
        />
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 2: PAR CHAMBRE FRIGORIFIQUE (Cold Storage Room Grid)                 -->
    <!-- ========================================================================= -->
    <div v-else-if="activeViewMode === 'chambers'" class="wh-view-container">
      <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-3">
        <span class="text-subtitle-2 font-weight-bold text-medium-emphasis">
          {{ filteredChambers.length }} {{ tLabel('warehouses', 'Chambres Frigorifiques') }} • {{ occupiedChambersCount }} {{ tLabel('occupied_chambers', 'Occupées') }}
        </span>
        <div class="d-flex align-center ga-2 flex-wrap">
          <v-text-field
            v-model="chamberSearch"
            density="compact"
            variant="outlined"
            rounded="lg"
            color="#a21ee5"
            hide-details
            clearable
            :placeholder="tLabel('search_chamber', 'Filtrer par nom de chambre...')"
            prepend-inner-icon="mdi-magnify"
            style="min-width: 240px; max-width: 320px;"
          />
          <v-btn
            size="small"
            variant="tonal"
            color="#a21ee5"
            rounded="lg"
            prepend-icon="mdi-printer"
            class="font-weight-bold"
            @click="openPrintDialog('chambers')"
          >
            {{ tLabel('print', 'Imprimer') }}
          </v-btn>
        </div>
      </div>

      <v-row dense>
        <v-col v-for="c in filteredChambers" :key="c.id" cols="12" sm="6" md="4" lg="3">
          <v-card
            :class="['wh-chamber-card pa-4 rounded-2xl border h-100 cursor-pointer', { 'is-empty': c.is_empty }]"
            elevation="0"
            @click="drillIntoChamber(c)"
          >
            <div class="wh-chamber-header d-flex align-start justify-space-between mb-3 ga-2 flex-nowrap">
              <div class="wh-chamber-identity d-flex align-center min-w-0 mr-1 flex-grow-1 overflow-hidden">
                <div
                  class="wh-chamber-icon flex-shrink-0 mr-3"
                  :style="{ background: c.is_empty ? 'rgba(158, 158, 158, 0.15)' : 'rgba(162, 30, 229, 0.15)' }"
                >
                  <v-icon :color="c.is_empty ? 'grey' : '#a21ee5'" size="22">mdi-snowflake</v-icon>
                </div>
                <div class="wh-chamber-info min-w-0 flex-grow-1 overflow-hidden">
                  <div class="wh-chamber-name text-truncate" :title="c.name">{{ c.name }}</div>
                  <div class="wh-chamber-sub text-truncate">{{ c.category_name || c.warehouse_type || 'Chambre Froide' }}</div>
                </div>
              </div>
              <v-chip
                size="x-small"
                :color="c.is_empty ? 'grey' : '#00897B'"
                variant="tonal"
                class="wh-chamber-chip font-weight-bold px-2.5 flex-shrink-0"
              >
                {{ c.is_empty ? tLabel('empty', 'Vide') : c.active_lots + ' ' + (c.active_lots === 1 ? tLabel('tracking_badge_singular', 'N° Suivi') : tLabel('tracking_badge_plural', 'N° Suivi')) }}
              </v-chip>
            </div>

            <v-divider class="my-3" color="#a21ee5" style="opacity: 0.12;" />

            <div class="d-flex flex-column ga-2 text-body-2">
              <div class="d-flex align-center justify-space-between">
                <span class="text-medium-emphasis">{{ tLabel('remaining_weight_label', 'Poids en Stock') }}:</span>
                <b class="font-weight-bold" :style="{ color: c.is_empty ? 'grey' : '#00897B' }">
                  {{ main.formatDisplayNumber(c.total_weight) }} Kg
                </b>
              </div>
              <div class="d-flex align-center justify-space-between">
                <span class="text-medium-emphasis">{{ tLabel('caisses', 'Colis / Cartons') }}:</span>
                <b class="font-weight-bold" style="color: #6D4C41;">{{ main.formatDisplayNumber(c.total_boxes) }}</b>
              </div>
              <div class="d-flex align-center justify-space-between">
                <span class="text-medium-emphasis">{{ tLabel('total_stock_value', 'Valorisation') }}:</span>
                <b class="font-weight-bold" style="color: #FF9800;">{{ main.formatDisplayNumber(c.valuation) }} {{ main.currency }}</b>
              </div>
            </div>

            <div class="d-flex align-center justify-end mt-3 pt-2 border-t">
              <v-btn size="small" variant="tonal" color="#a21ee5" rounded="lg" class="font-weight-bold" append-icon="mdi-arrow-right">
                {{ tLabel('drill_down_chamber', 'Explorer les N° de Suivi') }}
              </v-btn>
            </div>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 3: PAR ESPÈCE & ARTICLE (Species Catalog View)                       -->
    <!-- ========================================================================= -->
    <div v-else-if="activeViewMode === 'species'" class="wh-view-container">
      <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-3">
        <span class="text-subtitle-2 font-weight-bold text-medium-emphasis">
          {{ filteredSpecies.length }} {{ tLabel('species_count', 'Espèces & Articles répertoriés') }}
        </span>
        <div class="d-flex align-center ga-2 flex-wrap">
          <v-text-field
            v-model="speciesSearch"
            density="compact"
            variant="outlined"
            rounded="lg"
            color="#a21ee5"
            hide-details
            clearable
            :placeholder="tLabel('search_species', 'Rechercher une espèce...')"
            prepend-inner-icon="mdi-magnify"
            style="min-width: 240px;"
          />
          <SegmentedPillToggle
            v-model="speciesFilterType"
            :items="speciesTypeToggleOptions"
            color="#a21ee5"
            size="small"
          />
          <v-btn
            size="small"
            variant="tonal"
            color="#a21ee5"
            rounded="lg"
            prepend-icon="mdi-printer"
            class="font-weight-bold"
            @click="openPrintDialog('species')"
          >
            {{ tLabel('print', 'Imprimer') }}
          </v-btn>
        </div>
      </div>

      <div class="wh-table-card rounded-2xl border bg-surface overflow-hidden">
        <!-- Active Multi-Column Filters Bar for Species -->
        <div v-if="hasActiveSpeciesColFilters" class="wh-active-filters-bar d-flex align-center justify-space-between flex-wrap ga-2 px-3 py-2 border-b bg-surface">
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-icon size="15" color="#a21ee5">mdi-filter-check</v-icon>
            <span class="text-caption font-weight-bold text-medium-emphasis">
              {{ tLabel('active_filters', 'Filtres actifs') }} ({{ activeSpeciesColFiltersCount }}):
            </span>
            <template v-for="(vals, colKey) in speciesColFilters" :key="colKey">
              <v-chip
                v-if="vals && vals.length"
                size="x-small"
                color="#a21ee5"
                variant="tonal"
                closable
                class="font-weight-medium"
                @click:close="clearSpeciesColFilter(colKey)"
              >
                <b>{{ getSpeciesColTitle(colKey) }}:</b>
                <span class="ml-1">{{ formatSpeciesColFilterDisplay(colKey, vals) }}</span>
              </v-chip>
            </template>
          </div>
          <v-btn
            size="x-small"
            variant="text"
            color="warning"
            prepend-icon="mdi-filter-off"
            class="font-weight-bold"
            @click="clearAllSpeciesColFilters"
          >
            {{ tLabel('clear_all_filters', 'Effacer tous les filtres') }}
          </v-btn>
        </div>

        <div class="wh-table-wrap">
          <table :class="['wh-modern-table', 'density-' + tableDensity]">
            <thead>
              <tr>
                <!-- 1. Article / Species -->
                <th class="text-left" :class="{ 'has-filter-active': hasSpeciesColFilter('article_name') }" style="min-width: 200px;">
                  <div class="d-flex align-center justify-space-between w-100 ga-1">
                    <span class="font-weight-bold">{{ tLabel('article_type_name', 'Espèce / Article') }}</span>
                    <TableColumnFilter
                      v-model="speciesColFilters.article_name"
                      :options="speciesFilterOpts('article_name')"
                      :title="tLabel('article_type_name', 'Espèce / Article')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 2. Family -->
                <th class="text-center" :class="{ 'has-filter-active': hasSpeciesColFilter('family_name') }" style="min-width: 140px;">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('fish_family', 'Famille') }}</span>
                    <TableColumnFilter
                      v-model="speciesColFilters.family_name"
                      :options="speciesFilterOpts('family_name')"
                      :title="tLabel('fish_family', 'Famille')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 3. Process / Treatment -->
                <th class="text-center" :class="{ 'has-filter-active': hasSpeciesColFilter('process_name') }" style="min-width: 140px;">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('process', 'Traitement') }}</span>
                    <TableColumnFilter
                      v-model="speciesColFilters.process_name"
                      :options="speciesFilterOpts('process_name')"
                      :title="tLabel('process', 'Traitement')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 4. Remaining Stock -->
                <th class="text-right" :class="{ 'has-filter-active': hasSpeciesColFilter('rem_weight') }" style="min-width: 185px;">
                  <div class="d-flex align-center justify-end w-100 ga-1">
                    <TableColumnFilter
                      v-model="speciesColFilters.rem_weight"
                      :options="speciesFilterOpts('rem_weight')"
                      :title="tLabel('remaining_weight_label', 'Poids en Stock')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      class="mr-1"
                      @click.stop
                    />
                    <span>{{ tLabel('remaining_weight_label', 'Poids en Stock') }} (Kg)</span>
                  </div>
                </th>

                <!-- 5. Cartons -->
                <th class="text-center" :class="{ 'has-filter-active': hasSpeciesColFilter('boxes') }" style="min-width: 125px;">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('package_count', 'Cartons') }}</span>
                    <TableColumnFilter
                      v-model="speciesColFilters.boxes"
                      :options="speciesFilterOpts('boxes')"
                      :title="tLabel('package_count', 'Cartons')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 6. WAP -->
                <th class="text-right" :class="{ 'has-filter-active': hasSpeciesColFilter('wap') }" style="min-width: 145px;">
                  <div class="d-flex align-center justify-end w-100 ga-1">
                    <TableColumnFilter
                      v-model="speciesColFilters.wap"
                      :options="speciesFilterOpts('wap')"
                      :title="tLabel('wap', 'PMP')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      class="mr-1"
                      @click.stop
                    />
                    <span>{{ tLabel('wap', 'PMP') }} ({{ main.currency }}/Kg)</span>
                  </div>
                </th>

                <!-- 7. Valuation -->
                <th class="text-right" :class="{ 'has-filter-active': hasSpeciesColFilter('valuation') }" style="min-width: 165px;">
                  <div class="d-flex align-center justify-end w-100 ga-1">
                    <TableColumnFilter
                      v-model="speciesColFilters.valuation"
                      :options="speciesFilterOpts('valuation')"
                      :title="tLabel('total_price', 'Valorisation')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      class="mr-1"
                      @click.stop
                    />
                    <span>{{ tLabel('total_price', 'Valorisation') }} ({{ main.currency }})</span>
                  </div>
                </th>

                <!-- 8. Active Lots -->
                <th class="text-left" style="min-width: 200px;">
                  <span>{{ tLabel('active_lots', 'N° de Suivi Porteurs') }}</span>
                </th>

                <!-- 9. Actions -->
                <th class="text-center" style="width: 100px;">
                  <span>{{ tLabel('actions', 'Actions') }}</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sp in filteredSpecies" :key="sp.stockable_type + '_' + sp.stockable_id" class="wh-article-row">
                <td class="text-left font-weight-bold">
                  <div class="d-flex align-center ga-2 py-1">
                    <v-icon size="18" :color="tColor(sp.stockable_type)">{{ tIcon(sp.stockable_type) }}</v-icon>
                    <span class="text-subtitle-2 font-weight-bold">{{ sp.article_name }}</span>
                  </div>
                </td>
                <td class="text-center text-caption">{{ sp.family_name }}</td>
                <td class="text-center text-caption">{{ sp.process_name }}</td>
                <td class="text-right font-weight-bold text-teal" style="font-size: 14px;">{{ main.formatDisplayNumber(sp.total_weight) }} Kg</td>
                <td class="text-center font-weight-bold" style="color: #6D4C41;">{{ main.formatDisplayNumber(sp.total_boxes) }}</td>
                <td class="text-right font-weight-bold" style="color: #a21ee5;">{{ sp.wap > 0 ? main.formatDisplayNumber(sp.wap) : '-' }}</td>
                <td class="text-right font-weight-bold text-deep-orange" style="font-size: 13.5px;">{{ main.formatDisplayNumber(sp.total_valuation) }}</td>
                <td class="text-left">
                  <div class="d-flex align-center ga-1 flex-wrap" style="max-width: 280px;">
                    <v-chip
                      v-for="(lt, lidx) in (sp.active_lots_preview || []).slice(0, 4)"
                      :key="lidx"
                      size="x-small"
                      color="#a21ee5"
                      variant="tonal"
                      class="font-weight-medium my-0.5"
                    >
                      #{{ lt }}
                    </v-chip>
                    <v-chip v-if="(sp.active_lots_preview || []).length > 4" size="x-small" color="grey" variant="tonal" class="my-0.5">
                      +{{ sp.lots_count - 4 }}
                    </v-chip>
                  </div>
                </td>
                <td class="text-center">
                  <v-btn size="small" variant="tonal" color="#a21ee5" rounded="lg" class="font-weight-bold" @click="filterBySpeciesLots(sp)">
                    {{ tLabel('view_lots', 'Voir N° Suivi') }}
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 4: JOURNAL DES MOUVEMENTS (FULL-WIDTH VCALENDAR & NO PAGINATION)      -->
    <!-- ========================================================================= -->
    <div v-else-if="activeViewMode === 'journal'" class="wh-view-container">
      <v-card class="wh-vcalendar-panel pa-3 pa-md-4 mb-3 rounded-2xl border bg-surface" elevation="0">
        <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-3">
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-icon size="22" color="#a21ee5">mdi-calendar-range</v-icon>
            <span class="font-weight-bold text-subtitle-1 text-warehouse">
              {{ tLabel('selected_period', 'Période Sélectionnée') }}:
            </span>
            <v-chip size="small" color="#a21ee5" variant="flat" class="font-weight-bold text-white px-3">
              <v-icon start size="14">mdi-calendar-check</v-icon>
              {{ displayMovementRangeText }}
            </v-chip>
            <v-chip size="small" color="#a21ee5" variant="tonal" class="font-weight-bold">
              {{ journalTotals.total_count || journalList.length }} {{ tLabel('movements', 'Mouvements') }}
            </v-chip>
          </div>

          <div class="d-flex align-center ga-2 flex-wrap">
            <SegmentedPillToggle
              v-model="activeMovementPreset"
              :items="movementPresetOptions"
              color="#a21ee5"
              size="small"
              @update:modelValue="setMovementPreset"
            />

            <v-btn
              size="small"
              variant="text"
              color="grey"
              rounded="lg"
              class="font-weight-bold"
              :prepend-icon="calendarExpanded ? 'mdi-chevron-up' : 'mdi-calendar-expand-horizontal'"
              @click="toggleCalendarExpand"
            >
              {{ calendarExpanded ? tLabel('collapse_calendar', 'Masquer') : tLabel('expand_calendar', 'Afficher Calendrier') }}
            </v-btn>
          </div>
        </div>

        <v-expand-transition>
          <div v-if="calendarExpanded" class="wh-vcalendar-inner rounded-2xl overflow-hidden pa-3 border">
            <DatePicker
              v-model.range="movementDateRange"
              mode="date"
              color="purple"
              expanded
              borderless
              :columns="$vuetify.display.mdAndUp ? 2 : 1"
              :is-dark="main.is_dark"
              :locale="main.lang?.short || 'fr'"
              class="w-100 wh-vcalendar"
              @update:model-value="onMovementDateRangeChange"
            />
          </div>
        </v-expand-transition>

        <!-- KPI Summary Strip for Date Range -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-2 mt-3 pt-3 border-t">
          <div class="d-flex align-center ga-3 flex-wrap">
            <div class="wh-range-kpi-pill border pa-2.5 px-3.5 rounded-xl" style="background: rgba(0, 137, 123, 0.08);">
              <div class="text-caption text-medium-emphasis">{{ tLabel('total_in', 'Total Entrées (IN)') }}</div>
              <div class="font-weight-bold text-teal text-subtitle-1">
                +{{ main.formatDisplayNumber(journalTotals.total_in_weight) }} Kg
                <span class="text-caption text-medium-emphasis">({{ main.formatDisplayNumber(journalTotals.total_in_boxes) }} caisses)</span>
              </div>
            </div>

            <div class="wh-range-kpi-pill border pa-2.5 px-3.5 rounded-xl" style="background: rgba(211, 47, 47, 0.08);">
              <div class="text-caption text-medium-emphasis">{{ tLabel('total_out', 'Total Sorties (OUT)') }}</div>
              <div class="font-weight-bold text-error text-subtitle-1">
                -{{ main.formatDisplayNumber(journalTotals.total_out_weight) }} Kg
                <span class="text-caption text-medium-emphasis">({{ main.formatDisplayNumber(journalTotals.total_out_boxes) }} caisses)</span>
              </div>
            </div>

            <div class="wh-range-kpi-pill border pa-2.5 px-3.5 rounded-xl" style="background: rgba(162, 30, 229, 0.08);">
              <div class="text-caption text-medium-emphasis">{{ tLabel('net_balance', 'Solde Net') }}</div>
              <div class="font-weight-bold text-subtitle-1" :style="{ color: journalTotals.net_weight >= 0 ? '#00897B' : '#D32F2F' }">
                {{ journalTotals.net_weight >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(journalTotals.net_weight) }} Kg
              </div>
            </div>
          </div>

          <!-- Movement Direction Toggle & Search Input -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-text-field
              v-model="journalSearch"
              density="compact"
              variant="outlined"
              rounded="lg"
              color="#a21ee5"
              hide-details
              clearable
              :placeholder="tLabel('search_journal_hint', 'Filtrer N° Suivi de Réception, Partenaire, Article...')"
              prepend-inner-icon="mdi-magnify"
              style="min-width: 240px;"
            />

            <SegmentedPillToggle
              v-model="journalFilterType"
              :items="journalDirectionToggleOptions"
              color="#a21ee5"
              size="small"
              @update:modelValue="fetchJournal"
            />

            <v-btn
              size="small"
              variant="tonal"
              color="#a21ee5"
              rounded="lg"
              prepend-icon="mdi-printer"
              class="font-weight-bold"
              @click="openPrintDialog('journal')"
            >
              {{ tLabel('print', 'Imprimer') }}
            </v-btn>
          </div>
        </div>
      </v-card>

      <!-- CONTINUOUS FULL TABLE WITH STICKY HEADER -->
      <div v-if="journalLoading" class="d-flex flex-column align-center justify-center pa-12 wh-table-card rounded-2xl border bg-surface">
        <v-progress-circular indeterminate color="#a21ee5" size="52" width="4" />
        <span class="text-caption text-medium-emphasis mt-3 font-weight-bold">{{ tLabel('loading', 'Chargement des flux...') }}</span>
      </div>

      <div v-else-if="filteredJournalList.length" class="wh-table-card wh-journal-scroll-container rounded-2xl border bg-surface">
        <div class="wh-scroll-stream-header pa-2.5 px-4 border-b bg-surface d-flex align-center justify-space-between">
          <span class="text-caption font-weight-bold text-medium-emphasis d-flex align-center ga-2">
            <v-icon size="16" color="#a21ee5">mdi-view-headline</v-icon>
            {{ tLabel('continuous_stream', 'Flux continu sans pagination') }} • {{ filteredJournalList.length }} {{ tLabel('movements', 'mouvements') }}
          </span>
          <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
            {{ displayMovementRangeText }}
          </v-chip>
        </div>

        <!-- Active Multi-Column Filters Bar for Journal -->
        <div v-if="hasActiveJournalColFilters" class="wh-active-filters-bar d-flex align-center justify-space-between flex-wrap ga-2 px-3 py-2 border-b bg-surface">
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-icon size="15" color="#a21ee5">mdi-filter-check</v-icon>
            <span class="text-caption font-weight-bold text-medium-emphasis">
              {{ tLabel('active_filters', 'Filtres actifs') }} ({{ activeJournalColFiltersCount }}):
            </span>
            <template v-for="(vals, colKey) in journalColFilters" :key="colKey">
              <v-chip
                v-if="vals && vals.length"
                size="x-small"
                color="#a21ee5"
                variant="tonal"
                closable
                class="font-weight-medium"
                @click:close="clearJournalColFilter(colKey)"
              >
                <b>{{ getJournalColTitle(colKey) }}:</b>
                <span class="ml-1">{{ formatJournalColFilterDisplay(colKey, vals) }}</span>
              </v-chip>
            </template>
          </div>
          <v-btn
            size="x-small"
            variant="text"
            color="warning"
            prepend-icon="mdi-filter-off"
            class="font-weight-bold"
            @click="clearAllJournalColFilters"
          >
            {{ tLabel('clear_all_filters', 'Effacer tous les filtres') }}
          </v-btn>
        </div>

        <div class="wh-table-wrap">
          <table :class="['wh-modern-table wh-sticky-table', 'density-' + tableDensity]">
            <thead>
              <tr>
                <!-- 1. Date -->
                <th class="text-center" :class="{ 'has-filter-active': hasJournalColFilter('date') }" style="width: 130px;">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('date', 'Date') }}</span>
                    <TableColumnFilter
                      v-model="journalColFilters.date"
                      :options="journalFilterOpts('date')"
                      :title="tLabel('date', 'Date')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 2. Direction (Sens) -->
                <th class="text-center" :class="{ 'has-filter-active': hasJournalColFilter('direction') }" style="width: 110px;">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('movement_direction', 'Sens') }}</span>
                    <TableColumnFilter
                      v-model="journalColFilters.direction"
                      :options="journalFilterOpts('direction')"
                      :title="tLabel('movement_direction', 'Sens')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 3. Lot Number -->
                <th class="text-center" :class="{ 'has-filter-active': hasJournalColFilter('lot_number') }" style="width: 140px;">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('lot_number', 'N° Suivi Réception') }}</span>
                    <TableColumnFilter
                      v-model="journalColFilters.lot_number"
                      :options="journalFilterOpts('lot_number')"
                      :title="tLabel('lot_number', 'N° Suivi Réception')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 4. Article -->
                <th class="text-left" :class="{ 'has-filter-active': hasJournalColFilter('article_name') }">
                  <div class="d-flex align-center justify-space-between w-100 ga-1">
                    <span>{{ tLabel('article_type_name', 'Article') }}</span>
                    <TableColumnFilter
                      v-model="journalColFilters.article_name"
                      :options="journalFilterOpts('article_name')"
                      :title="tLabel('article_type_name', 'Article')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 5. Warehouse / Chamber -->
                <th class="text-center" :class="{ 'has-filter-active': hasJournalColFilter('warehouse_name') }">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('warehouse', 'Chambre') }}</span>
                    <TableColumnFilter
                      v-model="journalColFilters.warehouse_name"
                      :options="journalFilterOpts('warehouse_name')"
                      :title="tLabel('warehouse', 'Chambre')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 6. Source Document -->
                <th class="text-center" :class="{ 'has-filter-active': hasJournalColFilter('source_type') }">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('source_document', 'Document Source') }}</span>
                    <TableColumnFilter
                      v-model="journalColFilters.source_type"
                      :options="journalFilterOpts('source_type')"
                      :title="tLabel('source_document', 'Document Source')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 7. Partner -->
                <th class="text-left" :class="{ 'has-filter-active': hasJournalColFilter('partner_name') }">
                  <div class="d-flex align-center justify-space-between w-100 ga-1">
                    <span>{{ tLabel('partner', 'Partenaire (Bateau / Client)') }}</span>
                    <TableColumnFilter
                      v-model="journalColFilters.partner_name"
                      :options="journalFilterOpts('partner_name')"
                      :title="tLabel('partner', 'Partenaire')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>

                <!-- 8. Weight -->
                <th class="text-right" :class="{ 'has-filter-active': hasJournalColFilter('weight') }">
                  <div class="d-flex align-center justify-end w-100 ga-1">
                    <TableColumnFilter
                      v-model="journalColFilters.weight"
                      :options="journalFilterOpts('weight')"
                      :title="tLabel('weight', 'Poids')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      class="mr-1"
                      @click.stop
                    />
                    <span>{{ tLabel('weight', 'Poids') }} (Kg)</span>
                  </div>
                </th>

                <!-- 9. Packages -->
                <th class="text-center" :class="{ 'has-filter-active': hasJournalColFilter('boxes') }">
                  <div class="d-flex align-center justify-center w-100 ga-1">
                    <span>{{ tLabel('package_count', 'Cartons') }}</span>
                    <TableColumnFilter
                      v-model="journalColFilters.boxes"
                      :options="journalFilterOpts('boxes')"
                      :title="tLabel('package_count', 'Cartons')"
                      color="#a21ee5"
                      button-size="20"
                      icon-size="13"
                      @click.stop
                    />
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in paginatedJournalList" :key="m.id + '_' + m.direction" class="wh-article-row">
                <td class="text-center text-caption font-weight-medium">{{ main.formateDate(m.date) }}</td>
                <td class="text-center">
                  <v-chip
                    size="x-small"
                    :color="m.direction === 'IN' ? '#00897B' : '#D32F2F'"
                    variant="elevated"
                    class="font-weight-bold text-white px-2"
                  >
                    <v-icon size="11" class="mr-0.5">{{ m.direction === 'IN' ? 'mdi-arrow-down-bold' : 'mdi-arrow-up-bold' }}</v-icon>
                    {{ m.direction }}
                  </v-chip>
                </td>
                <td class="text-center font-weight-bold">
                  <v-chip size="small" color="#a21ee5" variant="tonal" class="cursor-pointer font-weight-bold" @click="openTraceability(m.lot_number)">
                    #{{ m.lot_number }}
                  </v-chip>
                </td>
                <td class="text-left font-weight-bold">{{ m.article_name }}</td>
                <td class="text-center text-caption">{{ m.warehouse_name }}</td>
                <td class="text-center">
                  <v-chip size="x-small" :color="srcColor(m.source_type)" variant="tonal" class="font-weight-medium">
                    {{ srcLabel(m.source_type) }} #{{ m.source_id }}
                  </v-chip>
                </td>
                <td class="text-left text-caption font-weight-medium">{{ m.partner_name }}</td>
                <td :class="['text-right font-weight-bold', m.direction === 'IN' ? 'text-teal' : 'text-error']" style="font-size: 13.5px;">
                  {{ m.direction === 'IN' ? '+' : '-' }}{{ main.formatDisplayNumber(m.weight) }} Kg
                </td>
                <td :class="['text-center text-caption font-weight-bold', m.direction === 'IN' ? 'text-teal' : 'text-error']">
                  {{ m.direction === 'IN' ? '+' : '-' }}{{ main.formatDisplayNumber(m.boxes) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Journal Progressive Pagination Bar -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-2 pa-3 border-t bg-surface">
          <div class="d-flex align-center ga-3">
            <span class="text-caption text-medium-emphasis">
              {{ tLabel('showing', 'Affichage') }} <b>{{ (journalPage - 1) * journalPerPage + 1 }}</b> -
              <b>{{ Math.min(journalPage * journalPerPage, filteredJournalList.length) }}</b>
              {{ tLabel('of', 'sur') }} <b>{{ filteredJournalList.length }}</b> {{ tLabel('movements', 'mouvements') }}
            </span>
            <div class="d-flex align-center ga-1">
              <span class="text-caption text-medium-emphasis" style="font-size: 11px;">{{ tLabel('rows_per_page', 'Par page') }}:</span>
              <v-btn-toggle
                v-model="journalPerPage"
                mandatory
                density="compact"
                variant="outlined"
                color="#a21ee5"
                rounded="lg"
              >
                <v-btn v-for="cnt in journalPerPageOptions" :key="cnt" :value="cnt" size="x-small" class="font-weight-bold">
                  {{ cnt }}
                </v-btn>
              </v-btn-toggle>
            </div>
          </div>

          <v-pagination
            v-if="journalTotalPages > 1"
            v-model="journalPage"
            :length="journalTotalPages"
            density="comfortable"
            rounded="circle"
            active-color="#a21ee5"
            total-visible="7"
          />
        </div>
      </div>

      <div v-else class="text-center pa-12 wh-table-card rounded-2xl border bg-surface">
        <v-icon size="60" color="#a21ee5" style="opacity: 0.35;" class="mb-3">mdi-calendar-blank-outline</v-icon>
        <div class="text-subtitle-1 font-weight-bold">{{ tLabel('no_movements_in_range', 'Aucun mouvement enregistré pour cette période') }}</div>
        <div class="text-caption text-medium-emphasis mt-1">{{ tLabel('select_date_range_hint', 'Sélectionnez une plage de dates sur le calendrier ci-dessus ou utilisez les raccourcis') }}</div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 5: ANALYTIQUE & GRAPHIQUES STRATÉGIQUES (ENTIÈREMENT RECONSTRUIT)       -->
    <!-- ========================================================================= -->
    <div v-else-if="activeViewMode === 'analytics'" class="wh-view-container">
      <!-- 1. Top Analytical Command & Filter Ribbon -->
      <v-card class="wh-filter-toolbar pa-3 pa-md-4 mb-4 rounded-2xl border" elevation="0">
        <!-- Row 1: Header Title, Period Chip, Granularity & Calendar Controls -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-3">
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-avatar size="36" color="rgba(162, 30, 229, 0.12)" class="rounded-xl border">
              <v-icon size="20" color="#a21ee5">mdi-chart-timeline-variant-shimmer</v-icon>
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold text-warehouse">
                {{ tLabel('analytics_dashboard_title', 'Analytique & Intelligence des Stocks') }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ tLabel('analytics_dashboard_sub', 'Traçabilité des flux, analyse de vétusté FIFO et rotation des chambres') }}
              </div>
            </div>
            <v-chip size="small" color="#a21ee5" variant="flat" class="font-weight-bold text-white px-3 ml-2">
              <v-icon start size="14">mdi-calendar-check</v-icon>
              {{ displayAnalyticsRangeText }}
            </v-chip>
          </div>

          <div class="d-flex align-center ga-2 flex-wrap">
            <!-- Granularity Selector: Jour, Semaine, Mois -->
            <SegmentedPillToggle
              v-model="analyticsGranularity"
              :items="analyticsGranularityOptions"
              color="#a21ee5"
              size="small"
              @update:modelValue="onAnalyticsGranularityChange"
            />

            <!-- Toggle VCalendar Picker -->
            <v-btn
              size="small"
              variant="tonal"
              color="#a21ee5"
              rounded="lg"
              class="font-weight-bold"
              :prepend-icon="analyticsCalendarExpanded ? 'mdi-chevron-up' : 'mdi-calendar-range'"
              @click="toggleAnalyticsCalendar"
            >
              {{ analyticsCalendarExpanded ? tLabel('collapse_calendar', 'Masquer') : tLabel('expand_calendar', 'Calendrier') }}
            </v-btn>

            <!-- Print & Export Dialog Button -->
            <v-btn
              size="small"
              variant="tonal"
              color="#a21ee5"
              rounded="lg"
              prepend-icon="mdi-printer"
              class="font-weight-bold"
              @click="openPrintDialog('analytics')"
            >
              {{ tLabel('print', 'Imprimer') }}
            </v-btn>
          </div>
        </div>

        <!-- Row 2: Quick Presets & Chamber Filter with AddCharge Styling -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-2 pt-2 border-t">
          <div class="d-flex align-center ga-2 flex-wrap">
            <span class="text-caption text-medium-emphasis font-weight-bold mr-1">
              <v-icon size="13" class="mr-0.5">mdi-flash</v-icon>
              {{ tLabel('quick_presets', 'Période Rapide') }}:
            </span>
            <SegmentedPillToggle
              v-model="activeAnalyticsPreset"
              :items="analyticsPresetOptions"
              color="#a21ee5"
              size="small"
              @update:modelValue="setAnalyticsPreset"
            />
          </div>

          <div class="d-flex align-center ga-2" style="min-width: 280px; max-width: 380px;">
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
              :placeholder="tLabel('filter_by_chamber_placeholder', 'Toutes les chambres froides...')"
              prepend-inner-icon="mdi-snowflake"
              class="wh-input text-warehouse flex-grow-1"
              @update:modelValue="fetchAnalytics"
            >
              <template #chip="{ item, props }">
                <v-chip v-bind="props" size="x-small" color="#a21ee5" variant="tonal" class="font-weight-medium" closable>
                  {{ item.title }}
                </v-chip>
              </template>
            </v-autocomplete>
          </div>
        </div>

        <!-- Expandable VCalendar Range Picker -->
        <v-expand-transition>
          <div v-if="analyticsCalendarExpanded" class="wh-vcalendar-inner rounded-2xl overflow-hidden pa-3 mt-3 border">
            <DatePicker
              v-model.range="analyticsDateRange"
              mode="date"
              color="purple"
              expanded
              borderless
              :columns="$vuetify.display.mdAndUp ? 2 : 1"
              :is-dark="main.is_dark"
              :locale="main.lang?.short || 'fr'"
              class="w-100 wh-vcalendar"
              @update:model-value="onAnalyticsDateRangeChange"
            />
          </div>
        </v-expand-transition>
      </v-card>

      <!-- Analytics Loading Spinner -->
      <div v-if="analyticsLoading" class="d-flex flex-column align-center justify-center pa-12 wh-table-card rounded-2xl border bg-surface mb-4">
        <v-progress-circular indeterminate color="#a21ee5" size="52" width="4" />
        <span class="text-caption text-medium-emphasis mt-3 font-weight-bold">
          {{ tLabel('loading_analytics', 'Calcul et agrégation des indicateurs analytiques en cours...') }}
        </span>
      </div>

      <div v-else class="d-flex flex-column ga-4">
        <!-- 2. MACRO EXECUTIVE INDICATORS STRIP (8 SPECIALIZED METRIC CARDS) -->
        <v-row dense>
          <!-- Card 1: Total Inflows (IN) -->
          <v-col cols="12" sm="6" md="3">
            <div class="wh-analytics-kpi-card border" style="background: rgba(0, 137, 123, 0.04); border-color: rgba(0, 137, 123, 0.22) !important;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="wh-akpi-label" style="color: #00796B;">{{ tLabel('total_in', 'Flux Entrants (IN)') }}</span>
                <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
                  {{ analyticsData?.summary?.in_count || 0 }} {{ tLabel('entries', 'entrées') }}
                </v-chip>
              </div>
              <div class="wh-akpi-val" style="color: #00796B;">
                +{{ main.formatDisplayNumber(analyticsData?.summary?.in_weight || 0) }} <span class="wh-unit">Kg</span>
              </div>
              <div class="wh-akpi-sub d-flex align-center justify-space-between mt-1">
                <span>{{ main.formatDisplayNumber(analyticsData?.summary?.in_boxes || 0) }} {{ tLabel('boxes', 'caisses') }}</span>
                <b style="color: #00796B;">{{ main.formatDisplayNumber(analyticsData?.summary?.in_valuation || 0) }} {{ main.currency }}</b>
              </div>
            </div>
          </v-col>

          <!-- Card 2: Total Outflows (OUT) -->
          <v-col cols="12" sm="6" md="3">
            <div class="wh-analytics-kpi-card border" style="background: rgba(211, 47, 47, 0.04); border-color: rgba(211, 47, 47, 0.22) !important;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="wh-akpi-label" style="color: #C62828;">{{ tLabel('total_out', 'Flux Sortants (OUT)') }}</span>
                <v-chip size="x-small" color="error" variant="tonal" class="font-weight-bold">
                  {{ analyticsData?.summary?.out_count || 0 }} {{ tLabel('exits', 'sorties') }}
                </v-chip>
              </div>
              <div class="wh-akpi-val" style="color: #C62828;">
                -{{ main.formatDisplayNumber(analyticsData?.summary?.out_weight || 0) }} <span class="wh-unit">Kg</span>
              </div>
              <div class="wh-akpi-sub d-flex align-center justify-space-between mt-1">
                <span>{{ main.formatDisplayNumber(analyticsData?.summary?.out_boxes || 0) }} {{ tLabel('boxes', 'caisses') }}</span>
                <b class="text-error">{{ main.formatDisplayNumber(analyticsData?.summary?.out_valuation || 0) }} {{ main.currency }}</b>
              </div>
            </div>
          </v-col>

          <!-- Card 3: Net Variation -->
          <v-col cols="12" sm="6" md="3">
            <div class="wh-analytics-kpi-card border" style="background: rgba(162, 30, 229, 0.04); border-color: rgba(162, 30, 229, 0.22) !important;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="wh-akpi-label" style="color: #a21ee5;">{{ tLabel('net_variation', 'Variation Nette') }}</span>
                <v-chip size="x-small" :color="(analyticsData?.summary?.net_weight || 0) >= 0 ? '#00897B' : '#D32F2F'" variant="tonal" class="font-weight-bold">
                  {{ (analyticsData?.summary?.net_weight || 0) >= 0 ? tLabel('accumulation', 'Accumulation') : tLabel('destocking', 'Déstockage') }}
                </v-chip>
              </div>
              <div class="wh-akpi-val" :style="{ color: (analyticsData?.summary?.net_weight || 0) >= 0 ? '#00897B' : '#D32F2F' }">
                {{ (analyticsData?.summary?.net_weight || 0) >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(analyticsData?.summary?.net_weight || 0) }} <span class="wh-unit">Kg</span>
              </div>
              <div class="wh-akpi-sub d-flex align-center justify-space-between mt-1">
                <span>{{ (analyticsData?.summary?.net_boxes || 0) >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(analyticsData?.summary?.net_boxes || 0) }} {{ tLabel('boxes', 'caisses') }}</span>
                <b :style="{ color: (analyticsData?.summary?.net_valuation || 0) >= 0 ? '#00897B' : '#D32F2F' }">
                  {{ (analyticsData?.summary?.net_valuation || 0) >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(analyticsData?.summary?.net_valuation || 0) }} {{ main.currency }}
                </b>
              </div>
            </div>
          </v-col>

          <!-- Card 4: Destocking & Rotation Rate -->
          <v-col cols="12" sm="6" md="3">
            <div class="wh-analytics-kpi-card border" style="background: rgba(245, 124, 0, 0.04); border-color: rgba(245, 124, 0, 0.22) !important;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="wh-akpi-label" style="color: #E65100;">{{ tLabel('destocking_rate', 'Taux de Déstockage') }}</span>
                <v-chip size="x-small" color="#E65100" variant="tonal" class="font-weight-bold">
                  {{ tLabel('out_vs_in_ratio', 'Sortie / Entrée') }}
                </v-chip>
              </div>
              <div class="wh-akpi-val" style="color: #E65100;">
                {{ analyticsData?.ratios?.destocking_rate || 0 }} <span class="wh-unit">%</span>
              </div>
              <div class="wh-akpi-sub mt-1">
                <v-progress-linear
                  :model-value="Math.min(analyticsData?.ratios?.destocking_rate || 0, 100)"
                  color="#E65100"
                  height="6"
                  rounded
                />
              </div>
            </div>
          </v-col>

          <!-- Card 5: Daily Cadence Average -->
          <v-col cols="12" sm="6" md="3">
            <div class="wh-analytics-kpi-card border" style="background: rgba(30, 136, 229, 0.04); border-color: rgba(30, 136, 229, 0.22) !important;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="wh-akpi-label" style="color: #1565C0;">{{ tLabel('daily_average', 'Moyenne Journalière') }}</span>
                <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold">
                  {{ analyticsData?.date_range?.days_count || 30 }} {{ tLabel('days_short', 'j') }}
                </v-chip>
              </div>
              <div class="wh-akpi-val" style="color: #1565C0;">
                {{ main.formatDisplayNumber(analyticsData?.ratios?.daily_avg_in || 0) }} <span class="wh-unit">Kg/j IN</span>
              </div>
              <div class="wh-akpi-sub d-flex align-center justify-space-between mt-1">
                <span class="text-caption">{{ tLabel('daily_out', 'Sortie moy.') }}:</span>
                <b class="text-error">{{ main.formatDisplayNumber(analyticsData?.ratios?.daily_avg_out || 0) }} Kg/j</b>
              </div>
            </div>
          </v-col>

          <!-- Card 6: Average Weighted Valuation (PMP) -->
          <v-col cols="12" sm="6" md="3">
            <div class="wh-analytics-kpi-card border" style="background: rgba(109, 76, 65, 0.04); border-color: rgba(109, 76, 65, 0.22) !important;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="wh-akpi-label" style="color: #6D4C41;">{{ tLabel('weighted_wap', 'PMP Moyen Pondéré') }}</span>
                <v-chip size="x-small" color="#6D4C41" variant="tonal" class="font-weight-bold">
                  {{ main.currency }}/Kg
                </v-chip>
              </div>
              <div class="wh-akpi-val" style="color: #6D4C41;">
                {{ main.formatDisplayNumber(analyticsData?.ratios?.avg_in_price || 0) }} <span class="wh-unit">{{ main.currency }}/Kg</span>
              </div>
              <div class="wh-akpi-sub d-flex align-center justify-space-between mt-1">
                <span>{{ tLabel('pmp_out', 'Prix sortie') }}:</span>
                <b>{{ main.formatDisplayNumber(analyticsData?.ratios?.avg_out_price || 0) }} {{ main.currency }}/Kg</b>
              </div>
            </div>
          </v-col>

          <!-- Card 7: Instant Active Stock Balance -->
          <v-col cols="12" sm="6" md="3">
            <div class="wh-analytics-kpi-card border" style="background: rgba(162, 30, 229, 0.04); border-color: rgba(162, 30, 229, 0.22) !important;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="wh-akpi-label" style="color: #a21ee5;">{{ tLabel('current_stock_instant', 'Stock Actuel en Entrepôt') }}</span>
                <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
                  {{ tLabel('live_status', 'Temps Réel') }}
                </v-chip>
              </div>
              <div class="wh-akpi-val" style="color: #a21ee5;">
                {{ main.formatDisplayNumber(analyticsData?.aging?.total_weight || 0) }} <span class="wh-unit">Kg</span>
              </div>
              <div class="wh-akpi-sub d-flex align-center justify-space-between mt-1">
                <span>{{ analyticsData?.aging?.total_lots || 0 }} {{ tLabel('lots', 'N° Suivi') }}</span>
                <b style="color: #a21ee5;">{{ main.formatDisplayNumber(analyticsData?.aging?.total_valuation || 0) }} {{ main.currency }}</b>
              </div>
            </div>
          </v-col>

          <!-- Card 8: Average Stock Age & Quality -->
          <v-col cols="12" sm="6" md="3">
            <div class="wh-analytics-kpi-card border" style="background: rgba(0, 137, 123, 0.04); border-color: rgba(0, 137, 123, 0.22) !important;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="wh-akpi-label" style="color: #00796B;">{{ tLabel('average_age_shelf', 'Âge Moyen de Stockage') }}</span>
                <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
                  {{ tLabel('fifo_indicator', 'Audit FIFO') }}
                </v-chip>
              </div>
              <div class="wh-akpi-val" style="color: #00796B;">
                {{ Math.round(analyticsData?.aging?.avg_age_days || 0) }} <span class="wh-unit">{{ tLabel('days_short', 'jours') }}</span>
              </div>
              <div class="wh-akpi-sub d-flex align-center justify-space-between mt-1">
                <span>{{ tLabel('fresh_stock_ratio', 'Stock < 60j') }}:</span>
                <b class="text-teal font-weight-bold">
                  {{ (((analyticsData?.aging?.buckets?.fresh?.pct || 0) + (analyticsData?.aging?.buckets?.optimal?.pct || 0))).toFixed(1) }}%
                </b>
              </div>
            </div>
          </v-col>
        </v-row>

        <!-- 3. MAIN SECTION: FLOW TRAJECTORY & CHRONOLOGICAL CHART -->
        <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface" elevation="0">
          <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
            <div class="d-flex align-center ga-2">
              <v-icon size="24" color="#a21ee5">mdi-chart-areaspline</v-icon>
              <div>
                <span class="text-subtitle-1 font-weight-bold text-warehouse">
                  {{ tLabel('stock_flow_evolution', 'Évolution Temporelle des Flux de Stock') }}
                </span>
                <div class="text-caption text-medium-emphasis">
                  {{ displayAnalyticsRangeText }} • {{ (analyticsData?.timeline || []).length }} {{ tLabel('points_recorded', 'points enregistrés') }}
                </div>
              </div>
            </div>

            <div class="d-flex align-center ga-2 flex-wrap">
              <!-- Visual Chart Type Switcher: Area / Bar / Line -->
              <SegmentedPillToggle
                v-model="analyticsChartType"
                :items="analyticsChartTypeOptions"
                color="#a21ee5"
                size="small"
              />

              <!-- Metric Toggle: Flows (IN/OUT) vs Cumulative Trajectory vs Valuation vs Boxes -->
              <SegmentedPillToggle
                v-model="analyticsChartMetric"
                :items="analyticsMetricToggleOptions"
                color="#a21ee5"
                size="small"
              />
            </div>
          </div>

          <apexchart
            v-if="analyticsTimelineSeries.length && analyticsTimelineSeries[0].data.length"
            :type="analyticsChartType"
            height="340"
            :options="analyticsTimelineOptions"
            :series="analyticsTimelineSeries"
          />
          <div v-else class="text-center pa-10 text-medium-emphasis">
            <v-icon size="40" color="grey" class="mb-2">mdi-chart-timeline</v-icon>
            <div>{{ tLabel('no_chart_data', 'Aucune donnée disponible pour cette sélection') }}</div>
          </div>
        </v-card>

        <!-- 4. SECTION 2: STOCK AGING & SHELF-LIFE AUDIT (FIFO & FRAÎCHEUR) -->
        <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface" elevation="0">
          <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
            <div class="d-flex align-center ga-2">
              <v-icon size="22" color="#00897B">mdi-clock-check-outline</v-icon>
              <div>
                <span class="text-subtitle-1 font-weight-bold">
                  {{ tLabel('stock_aging_audit', 'Audit de Vétusté & Fraîcheur du Stock (Traçabilité FIFO)') }}
                </span>
                <div class="text-caption text-medium-emphasis">
                  {{ tLabel('stock_aging_audit_sub', 'Répartition des lots en stock par tranche d\'ancienneté pour anticiper les expéditions prioritaires') }}
                </div>
              </div>
            </div>

            <SegmentedPillToggle
              v-model="analyticsAgingMetric"
              :items="analyticsAgingMetricOptions"
              color="#00897B"
              size="small"
            />
          </div>

          <v-row dense class="align-center">
            <!-- Left: Donut Chart -->
            <v-col cols="12" md="5">
              <apexchart
                type="donut"
                height="280"
                :options="analyticsAgingDonutOptions"
                :series="analyticsAgingDonutSeries"
              />
            </v-col>

            <!-- Right: 4 Detailed Aging Tranche Cards -->
            <v-col cols="12" md="7">
              <div class="d-flex flex-column ga-2">
                <!-- 1. Fresh (0 - 30 days) -->
                <div class="wh-aging-card border pa-2.5 px-3 rounded-xl d-flex align-center justify-space-between" style="border-left: 4px solid #00897B !important; background: rgba(0, 137, 123, 0.04);">
                  <div>
                    <div class="d-flex align-center ga-2 font-weight-bold text-caption text-teal">
                      <v-icon size="14" color="teal">mdi-check-circle</v-icon>
                      <span>0 - 30 {{ tLabel('days_short', 'jours') }} ({{ tLabel('fresh_recent', 'Frais / Récent') }})</span>
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ analyticsData?.aging?.buckets?.fresh?.lots || 0 }} {{ tLabel('lots', 'N° Suivi') }} • {{ analyticsData?.aging?.buckets?.fresh?.pct || 0 }}% {{ tLabel('of_stock', 'du stock') }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-weight-bold text-teal text-subtitle-2">
                      {{ main.formatDisplayNumber(analyticsData?.aging?.buckets?.fresh?.weight || 0) }} Kg
                    </div>
                    <div class="text-caption font-weight-medium text-medium-emphasis">
                      {{ main.formatDisplayNumber(analyticsData?.aging?.buckets?.fresh?.valuation || 0) }} {{ main.currency }}
                    </div>
                  </div>
                </div>

                <!-- 2. Optimal (31 - 60 days) -->
                <div class="wh-aging-card border pa-2.5 px-3 rounded-xl d-flex align-center justify-space-between" style="border-left: 4px solid #1E88E5 !important; background: rgba(30, 136, 229, 0.04);">
                  <div>
                    <div class="d-flex align-center ga-2 font-weight-bold text-caption" style="color: #1565C0;">
                      <v-icon size="14" color="#1E88E5">mdi-clock-outline</v-icon>
                      <span>31 - 60 {{ tLabel('days_short', 'jours') }} ({{ tLabel('optimal_rotation', 'Rotation Optimale') }})</span>
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ analyticsData?.aging?.buckets?.optimal?.lots || 0 }} {{ tLabel('lots', 'N° Suivi') }} • {{ analyticsData?.aging?.buckets?.optimal?.pct || 0 }}% {{ tLabel('of_stock', 'du stock') }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-weight-bold text-subtitle-2" style="color: #1565C0;">
                      {{ main.formatDisplayNumber(analyticsData?.aging?.buckets?.optimal?.weight || 0) }} Kg
                    </div>
                    <div class="text-caption font-weight-medium text-medium-emphasis">
                      {{ main.formatDisplayNumber(analyticsData?.aging?.buckets?.optimal?.valuation || 0) }} {{ main.currency }}
                    </div>
                  </div>
                </div>

                <!-- 3. Warning (61 - 90 days) -->
                <div class="wh-aging-card border pa-2.5 px-3 rounded-xl d-flex align-center justify-space-between" style="border-left: 4px solid #FB8C00 !important; background: rgba(251, 140, 0, 0.04);">
                  <div>
                    <div class="d-flex align-center ga-2 font-weight-bold text-caption" style="color: #E65100;">
                      <v-icon size="14" color="#FB8C00">mdi-alert-outline</v-icon>
                      <span>61 - 90 {{ tLabel('days_short', 'jours') }} ({{ tLabel('warning_shelf', 'À Surveiller') }})</span>
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ analyticsData?.aging?.buckets?.warning?.lots || 0 }} {{ tLabel('lots', 'N° Suivi') }} • {{ analyticsData?.aging?.buckets?.warning?.pct || 0 }}% {{ tLabel('of_stock', 'du stock') }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-weight-bold text-subtitle-2" style="color: #E65100;">
                      {{ main.formatDisplayNumber(analyticsData?.aging?.buckets?.warning?.weight || 0) }} Kg
                    </div>
                    <div class="text-caption font-weight-medium text-medium-emphasis">
                      {{ main.formatDisplayNumber(analyticsData?.aging?.buckets?.warning?.valuation || 0) }} {{ main.currency }}
                    </div>
                  </div>
                </div>

                <!-- 4. Alert (> 90 days - FIFO Priority) -->
                <div class="wh-aging-card border pa-2.5 px-3 rounded-xl d-flex align-center justify-space-between" style="border-left: 4px solid #E53935 !important; background: rgba(229, 57, 53, 0.04);">
                  <div>
                    <div class="d-flex align-center ga-2 font-weight-bold text-caption text-error">
                      <v-icon size="14" color="error">mdi-alert-octagon</v-icon>
                      <span>> 90 {{ tLabel('days_short', 'jours') }} ({{ tLabel('fifo_priority', 'Priorité Expédition FIFO') }})</span>
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ analyticsData?.aging?.buckets?.alert?.lots || 0 }} {{ tLabel('lots', 'N° Suivi') }} • {{ analyticsData?.aging?.buckets?.alert?.pct || 0 }}% {{ tLabel('of_stock', 'du stock') }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-weight-bold text-error text-subtitle-2">
                      {{ main.formatDisplayNumber(analyticsData?.aging?.buckets?.alert?.weight || 0) }} Kg
                    </div>
                    <div class="text-caption font-weight-medium text-medium-emphasis">
                      {{ main.formatDisplayNumber(analyticsData?.aging?.buckets?.alert?.valuation || 0) }} {{ main.currency }}
                    </div>
                  </div>
                </div>
              </div>
            </v-col>
          </v-row>
        </v-card>

        <!-- 5. SECTION 3: SPECIES & PRODUCT FAMILIES BREAKDOWN -->
        <v-row dense>
          <!-- Left: Species Donut -->
          <v-col cols="12" md="6">
            <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface h-100" elevation="0">
              <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
                <div class="d-flex align-center ga-2">
                  <v-icon size="22" color="#00897B">mdi-chart-donut</v-icon>
                  <div>
                    <span class="text-subtitle-1 font-weight-bold">{{ tLabel('species_share_period', 'Répartition par Espèce sur la Période') }}</span>
                    <div class="text-caption text-medium-emphasis">
                      {{ (analyticsData?.species || []).length }} {{ tLabel('species_recorded', 'espèces répertoriées') }}
                    </div>
                  </div>
                </div>

                <SegmentedPillToggle
                  v-model="analyticsSpeciesMetric"
                  :items="analyticsSpeciesMetricOptions"
                  color="#00897B"
                  size="small"
                />
              </div>

              <apexchart
                v-if="analyticsSpeciesDonutSeries.length"
                type="donut"
                height="290"
                :options="analyticsSpeciesDonutOptions"
                :series="analyticsSpeciesDonutSeries"
              />
              <div v-else class="text-center pa-8 text-medium-emphasis">
                <v-icon size="40" color="grey" class="mb-2">mdi-chart-pie</v-icon>
                <div>{{ tLabel('no_chart_data', 'Aucune donnée disponible') }}</div>
              </div>
            </v-card>
          </v-col>

          <!-- Right: Families Grouped Bar -->
          <v-col cols="12" md="6">
            <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface h-100" elevation="0">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <v-icon size="22" color="#a21ee5">mdi-layers-triple</v-icon>
                  <div>
                    <span class="text-subtitle-1 font-weight-bold">{{ tLabel('family_share_period', 'Flux par Famille de Marchandise') }}</span>
                    <div class="text-caption text-medium-emphasis">
                      {{ tLabel('family_share_sub', 'Comparaison Entrées vs Sorties par catégorie de stock') }}
                    </div>
                  </div>
                </div>
                <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
                  {{ (analyticsData?.families || []).length }} {{ tLabel('categories', 'catégories') }}
                </v-chip>
              </div>

              <apexchart
                v-if="analyticsFamilyBarSeries.length && analyticsFamilyBarSeries[0].data.length"
                type="bar"
                height="290"
                :options="analyticsFamilyBarOptions"
                :series="analyticsFamilyBarSeries"
              />
              <div v-else class="text-center pa-8 text-medium-emphasis">
                <v-icon size="40" color="grey" class="mb-2">mdi-chart-bar</v-icon>
                <div>{{ tLabel('no_chart_data', 'Aucune donnée disponible') }}</div>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- 6. SECTION 4: COLD STORAGE CHAMBERS & SUPPLY CHAIN LOGISTICS -->
        <v-row dense>
          <!-- Left: Cold Storage Chambers Activity -->
          <v-col cols="12" md="6">
            <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface h-100" elevation="0">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <v-icon size="22" color="#1565C0">mdi-snowflake</v-icon>
                  <div>
                    <span class="text-subtitle-1 font-weight-bold">{{ tLabel('chambers_activity_period', 'Activité des Chambres Froides') }}</span>
                    <div class="text-caption text-medium-emphasis">
                      {{ tLabel('chambers_activity_sub', 'Comparatif des Entrées / Sorties par emplacement') }}
                    </div>
                  </div>
                </div>
                <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold">
                  {{ (analyticsData?.chambers || []).length }} {{ tLabel('warehouses', 'chambres') }}
                </v-chip>
              </div>

              <apexchart
                v-if="analyticsChambersBarSeries.length && analyticsChambersBarSeries[0].data.length"
                type="bar"
                height="300"
                :options="analyticsChambersBarOptions"
                :series="analyticsChambersBarSeries"
              />
              <div v-else class="text-center pa-8 text-medium-emphasis">
                <v-icon size="40" color="grey" class="mb-2">mdi-chart-bar</v-icon>
                <div>{{ tLabel('no_chart_data', 'Aucune donnée disponible') }}</div>
              </div>
            </v-card>
          </v-col>

          <!-- Right: Supply Chain Ecosystem (Top Suppliers vs Top Clients) -->
          <v-col cols="12" md="6">
            <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface h-100" elevation="0">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <v-icon size="22" color="#E65100">mdi-handshake</v-icon>
                  <div>
                    <span class="text-subtitle-1 font-weight-bold">{{ tLabel('partners_ecosystem', 'Écosystème Logistique & Partenaires') }}</span>
                    <div class="text-caption text-medium-emphasis">
                      {{ tLabel('partners_ecosystem_sub', 'Principaux apporteurs et destinataires sur la période') }}
                    </div>
                  </div>
                </div>
              </div>

              <v-row dense>
                <!-- Top Suppliers -->
                <v-col cols="12" sm="6">
                  <div class="pa-2.5 rounded-xl border wh-sub-box mb-2">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <span class="text-caption font-weight-bold text-teal d-flex align-center ga-1">
                        <v-icon size="14" color="teal">mdi-truck-fast</v-icon>
                        {{ tLabel('top_suppliers', 'Top Fournisseurs (IN)') }}
                      </span>
                    </div>

                    <div v-if="(analyticsData?.partners?.suppliers || []).length" class="d-flex flex-column ga-2">
                      <div
                        v-for="(sup, sidx) in (analyticsData?.partners?.suppliers || []).slice(0, 5)"
                        :key="sidx"
                        class="d-flex align-center justify-space-between pa-1.5 px-2 rounded-lg bg-surface border text-caption"
                      >
                        <div class="d-flex align-center ga-2 text-truncate mr-1">
                          <v-avatar size="20" color="rgba(0, 137, 123, 0.12)" class="font-weight-bold text-teal" style="font-size: 10px;">
                            {{ sidx + 1 }}
                          </v-avatar>
                          <span class="font-weight-bold text-truncate" :title="sup.partner_name">{{ sup.partner_name }}</span>
                        </div>
                        <div class="text-right flex-shrink-0">
                          <b class="text-teal">{{ main.formatDisplayNumber(sup.total_weight) }} Kg</b>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center py-4 text-caption text-medium-emphasis">
                      {{ tLabel('no_data', 'Aucun fournisseur') }}
                    </div>
                  </div>
                </v-col>

                <!-- Top Clients -->
                <v-col cols="12" sm="6">
                  <div class="pa-2.5 rounded-xl border wh-sub-box mb-2">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <span class="text-caption font-weight-bold text-error d-flex align-center ga-1">
                        <v-icon size="14" color="error">mdi-dolly</v-icon>
                        {{ tLabel('top_clients', 'Top Clients (OUT)') }}
                      </span>
                    </div>

                    <div v-if="(analyticsData?.partners?.clients || []).length" class="d-flex flex-column ga-2">
                      <div
                        v-for="(cli, cidx) in (analyticsData?.partners?.clients || []).slice(0, 5)"
                        :key="cidx"
                        class="d-flex align-center justify-space-between pa-1.5 px-2 rounded-lg bg-surface border text-caption"
                      >
                        <div class="d-flex align-center ga-2 text-truncate mr-1">
                          <v-avatar size="20" color="rgba(211, 47, 47, 0.12)" class="font-weight-bold text-error" style="font-size: 10px;">
                            {{ cidx + 1 }}
                          </v-avatar>
                          <span class="font-weight-bold text-truncate" :title="cli.partner_name">{{ cli.partner_name }}</span>
                        </div>
                        <div class="text-right flex-shrink-0">
                          <b class="text-error">{{ main.formatDisplayNumber(cli.total_weight) }} Kg</b>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center py-4 text-caption text-medium-emphasis">
                      {{ tLabel('no_data', 'Aucun client') }}
                    </div>
                  </div>
                </v-col>
              </v-row>
            </v-card>
          </v-col>
        </v-row>

        <!-- 7. SECTION 5: DEEP DIVE ANALYTICAL DATA GRID (TABBED MULTI-PERSPECTIVE) -->
        <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface" elevation="0">
          <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
            <div class="d-flex align-center ga-2">
              <v-icon size="22" color="#a21ee5">mdi-table-large</v-icon>
              <div>
                <span class="text-subtitle-1 font-weight-bold text-warehouse">
                  {{ tLabel('analytical_data_grid', 'Explorateur Détaillé des Données Analytiques') }}
                </span>
                <div class="text-caption text-medium-emphasis">
                  {{ tLabel('analytical_data_grid_sub', 'Tableaux exhaustifs de vérification et d\'audit des flux par perspective') }}
                </div>
              </div>
            </div>

            <div class="d-flex align-center ga-2 flex-wrap">
              <!-- Search filter input with AddCharge style -->
              <v-text-field
                v-model="analyticsTableSearch"
                density="compact"
                variant="outlined"
                rounded="lg"
                color="#a21ee5"
                hide-details
                clearable
                :placeholder="tLabel('filter_table_data', 'Filtrer le tableau...')"
                prepend-inner-icon="mdi-magnify"
                style="min-width: 220px;"
              />

              <!-- CSV Export Button -->
              <v-btn
                size="small"
                variant="tonal"
                color="#a21ee5"
                rounded="lg"
                prepend-icon="mdi-file-delimited-outline"
                class="font-weight-bold"
                @click="exportAnalyticsCsv"
              >
                {{ tLabel('export_csv', 'Export CSV') }}
              </v-btn>
            </div>
          </div>

          <!-- Tab Switcher -->
          <div class="wh-nav-bar mb-3 pa-1 rounded-xl border bg-surface d-flex align-center justify-space-between flex-wrap ga-2">
            <SegmentedPillToggle
              v-model="analyticsActiveTab"
              :items="analyticsTableTabOptions"
              color="#a21ee5"
              size="small"
            />
          </div>

          <!-- TAB 1: Chronological Timeline Journal Table -->
          <div v-if="analyticsActiveTab === 'timeline'" class="wh-table-wrap">
            <table :class="['wh-modern-table', 'density-' + tableDensity]">
              <thead>
                <tr>
                  <th class="text-center" style="width: 130px;">{{ tLabel('date', 'Période / Date') }}</th>
                  <th class="text-right">{{ tLabel('movement_in', 'Entrées (IN) Kg') }}</th>
                  <th class="text-right">{{ tLabel('movement_out', 'Sorties (OUT) Kg') }}</th>
                  <th class="text-right">{{ tLabel('net_balance', 'Solde Net Kg') }}</th>
                  <th class="text-right">{{ tLabel('cumulative_balance', 'Cumul Stock Kg') }}</th>
                  <th class="text-center">{{ tLabel('boxes_in', 'Cartons IN') }}</th>
                  <th class="text-center">{{ tLabel('boxes_out', 'Cartons OUT') }}</th>
                  <th class="text-right">{{ tLabel('financial_inflow', 'Valeur IN (Dh)') }}</th>
                  <th class="text-right">{{ tLabel('financial_outflow', 'Valeur OUT (Dh)') }}</th>
                  <th class="text-center" style="width: 100px;">{{ tLabel('movements', 'Mouvements') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(tl, tidx) in filteredAnalyticsTimeline" :key="tidx">
                  <td class="text-center font-weight-bold">{{ tl.date }}</td>
                  <td class="text-right font-weight-bold text-teal">+{{ main.formatDisplayNumber(tl.in_weight) }} Kg</td>
                  <td class="text-right font-weight-bold text-error">-{{ main.formatDisplayNumber(tl.out_weight) }} Kg</td>
                  <td class="text-right font-weight-bold" :style="{ color: tl.net_weight >= 0 ? '#00897B' : '#D32F2F' }">
                    {{ tl.net_weight >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(tl.net_weight) }} Kg
                  </td>
                  <td class="text-right font-weight-bold" style="color: #a21ee5;">{{ main.formatDisplayNumber(tl.cumulative_net_weight) }} Kg</td>
                  <td class="text-center text-caption font-weight-bold text-teal">+{{ main.formatDisplayNumber(tl.in_boxes) }}</td>
                  <td class="text-center text-caption font-weight-bold text-error">-{{ main.formatDisplayNumber(tl.out_boxes) }}</td>
                  <td class="text-right font-weight-bold text-caption text-teal">{{ main.formatDisplayNumber(tl.in_valuation) }}</td>
                  <td class="text-right font-weight-bold text-caption text-error">{{ main.formatDisplayNumber(tl.out_valuation) }}</td>
                  <td class="text-center text-caption font-weight-bold">
                    <v-chip size="x-small" color="#a21ee5" variant="tonal">{{ tl.movements_count }}</v-chip>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TAB 2: Species Performance Table -->
          <div v-else-if="analyticsActiveTab === 'species'" class="wh-table-wrap">
            <table :class="['wh-modern-table', 'density-' + tableDensity]">
              <thead>
                <tr>
                  <th class="text-left" style="min-width: 220px;">{{ tLabel('article_type_name', 'Espèce / Article') }}</th>
                  <th class="text-center">{{ tLabel('category', 'Catégorie') }}</th>
                  <th class="text-right">{{ tLabel('movement_in', 'Entrées (IN) Kg') }}</th>
                  <th class="text-right">{{ tLabel('movement_out', 'Sorties (OUT) Kg') }}</th>
                  <th class="text-right">{{ tLabel('net_balance', 'Solde Net Kg') }}</th>
                  <th class="text-right">{{ tLabel('total_flow', 'Flux Total (Kg)') }}</th>
                  <th class="text-center">{{ tLabel('boxes', 'Cartons') }}</th>
                  <th class="text-right">{{ tLabel('total_price', 'Valorisation (Dh)') }}</th>
                  <th class="text-center" style="width: 100px;">{{ tLabel('share_pct', 'Part (%)') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(sp, sidx) in filteredAnalyticsSpecies" :key="sidx">
                  <td class="text-left font-weight-bold">
                    <div class="d-flex align-center ga-2 py-1">
                      <v-icon size="16" color="#a21ee5">mdi-fish</v-icon>
                      <span>{{ sp.article_name }}</span>
                    </div>
                  </td>
                  <td class="text-center text-caption">
                    <v-chip size="x-small" color="#a21ee5" variant="tonal">{{ sp.stockable_type }}</v-chip>
                  </td>
                  <td class="text-right font-weight-bold text-teal">+{{ main.formatDisplayNumber(sp.in_weight) }} Kg</td>
                  <td class="text-right font-weight-bold text-error">-{{ main.formatDisplayNumber(sp.out_weight) }} Kg</td>
                  <td class="text-right font-weight-bold" :style="{ color: sp.net_weight >= 0 ? '#00897B' : '#D32F2F' }">
                    {{ sp.net_weight >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(sp.net_weight) }} Kg
                  </td>
                  <td class="text-right font-weight-bold" style="color: #a21ee5;">{{ main.formatDisplayNumber(sp.total_flow) }} Kg</td>
                  <td class="text-center text-caption font-weight-bold">{{ main.formatDisplayNumber(sp.total_boxes) }}</td>
                  <td class="text-right font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(sp.total_valuation) }}</td>
                  <td class="text-center">
                    <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">{{ sp.share_pct }}%</v-chip>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TAB 3: Chambers Performance Table -->
          <div v-else-if="analyticsActiveTab === 'chambers'" class="wh-table-wrap">
            <table :class="['wh-modern-table', 'density-' + tableDensity]">
              <thead>
                <tr>
                  <th class="text-left" style="min-width: 200px;">{{ tLabel('warehouse_name', 'Chambre Frigorifique') }}</th>
                  <th class="text-right">{{ tLabel('movement_in', 'Entrées (IN) Kg') }}</th>
                  <th class="text-right">{{ tLabel('movement_out', 'Sorties (OUT) Kg') }}</th>
                  <th class="text-right">{{ tLabel('net_balance', 'Solde Net Kg') }}</th>
                  <th class="text-right">{{ tLabel('total_flow', 'Flux Total (Kg)') }}</th>
                  <th class="text-right">{{ tLabel('current_stock', 'Stock Actuel (Kg)') }}</th>
                  <th class="text-center">{{ tLabel('activity_pct', 'Part Activité (%)') }}</th>
                  <th class="text-center" style="width: 100px;">{{ tLabel('movements', 'Mouvements') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ch, cidx) in filteredAnalyticsChambers" :key="cidx">
                  <td class="text-left font-weight-bold">
                    <div class="d-flex align-center ga-2 py-1">
                      <v-icon size="16" color="#1565C0">mdi-snowflake</v-icon>
                      <span>{{ ch.name }}</span>
                    </div>
                  </td>
                  <td class="text-right font-weight-bold text-teal">+{{ main.formatDisplayNumber(ch.in_weight) }} Kg</td>
                  <td class="text-right font-weight-bold text-error">-{{ main.formatDisplayNumber(ch.out_weight) }} Kg</td>
                  <td class="text-right font-weight-bold" :style="{ color: ch.net_weight >= 0 ? '#00897B' : '#D32F2F' }">
                    {{ ch.net_weight >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(ch.net_weight) }} Kg
                  </td>
                  <td class="text-right font-weight-bold" style="color: #a21ee5;">{{ main.formatDisplayNumber(ch.total_flow) }} Kg</td>
                  <td class="text-right font-weight-bold text-teal">{{ main.formatDisplayNumber(ch.current_stock) }} Kg</td>
                  <td class="text-center">
                    <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold">{{ ch.activity_pct }}%</v-chip>
                  </td>
                  <td class="text-center">
                    <v-chip size="x-small" color="#a21ee5" variant="tonal">{{ ch.movements_count }}</v-chip>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TAB 4: Logistics & Partners Table -->
          <div v-else-if="analyticsActiveTab === 'partners'" class="wh-table-wrap">
            <table :class="['wh-modern-table', 'density-' + tableDensity]">
              <thead>
                <tr>
                  <th class="text-left" style="min-width: 220px;">{{ tLabel('partner', 'Partenaire Logistique') }}</th>
                  <th class="text-center" style="width: 160px;">{{ tLabel('role', 'Rôle / Flux') }}</th>
                  <th class="text-center">{{ tLabel('movements', 'Mouvements') }}</th>
                  <th class="text-right">{{ tLabel('total_weight', 'Poids Total (Kg)') }}</th>
                  <th class="text-center">{{ tLabel('boxes', 'Cartons') }}</th>
                  <th class="text-right">{{ tLabel('total_price', 'Valeur Totale (Dh)') }}</th>
                </tr>
              </thead>
              <tbody>
                <!-- Suppliers -->
                <tr v-for="(sup, sidx) in filteredAnalyticsSuppliers" :key="'sup_' + sidx">
                  <td class="text-left font-weight-bold">
                    <div class="d-flex align-center ga-2 py-1">
                      <v-avatar size="24" color="rgba(0, 137, 123, 0.12)" class="rounded-lg">
                        <v-icon size="14" color="teal">mdi-domain</v-icon>
                      </v-avatar>
                      <span>{{ sup.partner_name }}</span>
                    </div>
                  </td>
                  <td class="text-center">
                    <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">
                      <v-icon start size="11">mdi-truck-fast</v-icon>
                      {{ tLabel('supplier', 'Fournisseur (IN)') }}
                    </v-chip>
                  </td>
                  <td class="text-center font-weight-bold">{{ sup.movements_count }}</td>
                  <td class="text-right font-weight-bold text-teal">+{{ main.formatDisplayNumber(sup.total_weight) }} Kg</td>
                  <td class="text-center font-weight-bold">{{ main.formatDisplayNumber(sup.total_boxes) }}</td>
                  <td class="text-right font-weight-bold text-teal">{{ main.formatDisplayNumber(sup.total_valuation) }}</td>
                </tr>

                <!-- Clients -->
                <tr v-for="(cli, cidx) in filteredAnalyticsClients" :key="'cli_' + cidx">
                  <td class="text-left font-weight-bold">
                    <div class="d-flex align-center ga-2 py-1">
                      <v-avatar size="24" color="rgba(211, 47, 47, 0.12)" class="rounded-lg">
                        <v-icon size="14" color="error">mdi-account-arrow-right</v-icon>
                      </v-avatar>
                      <span>{{ cli.partner_name }}</span>
                    </div>
                  </td>
                  <td class="text-center">
                    <v-chip size="x-small" color="error" variant="tonal" class="font-weight-bold">
                      <v-icon start size="11">mdi-dolly</v-icon>
                      {{ tLabel('client', 'Client (OUT)') }}
                    </v-chip>
                  </td>
                  <td class="text-center font-weight-bold">{{ cli.movements_count }}</td>
                  <td class="text-right font-weight-bold text-error">-{{ main.formatDisplayNumber(cli.total_weight) }} Kg</td>
                  <td class="text-center font-weight-bold">{{ main.formatDisplayNumber(cli.total_boxes) }}</td>
                  <td class="text-right font-weight-bold text-error">{{ main.formatDisplayNumber(cli.total_valuation) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </v-card>
      </div>
    </div>

        <!-- ========================================================================= -->
    <!-- VIEW 6: TRAÇABILITÉ CHRONOLOGIQUE PAR N° DE SUIVI & TYPES D'ARTICLES (3-STAGE REBUILD) -->
    <!-- ========================================================================= -->
    <div v-else-if="activeViewMode === 'traceability'" class="wh-view-container">
      <!-- 1. HEADER & 3-STAGE INTERACTIVE STEPPER CARD -->
      <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface mb-4" elevation="0">
        <!-- Title & Action Buttons -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-4">
          <div class="d-flex align-center ga-3">
            <div class="wh-modal-icon" style="background: rgba(162, 30, 229, 0.12);">
              <v-icon size="26" color="#a21ee5">mdi-timeline-clock-outline</v-icon>
            </div>
            <div>
              <div class="text-h6 font-weight-bold text-warehouse">
                {{ tLabel('traceability_title', 'Traçabilité Chronologique & Cycle de Vie du Stock') }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ tLabel('traceability_stepper_subtitle', 'Recherche multi-bons par N° de réception, sélection d\'articles et filiation chronologique complète') }}
              </div>
            </div>
          </div>

          <div class="d-flex align-center ga-2">
            <v-btn
              v-if="traceabilityCurrentStage > 1"
              size="small"
              variant="tonal"
              color="grey-darken-1"
              rounded="lg"
              prepend-icon="mdi-restart"
              class="font-weight-bold"
              @click="resetTraceabilityWorkflow"
            >
              {{ tLabel('reset_workflow', 'Nouvelle Recherche') }}
            </v-btn>

            <v-btn
              v-if="traceabilityCurrentStage === 3 && traceabilityTimelineData"
              size="small"
              variant="tonal"
              color="#a21ee5"
              rounded="lg"
              prepend-icon="mdi-printer"
              class="font-weight-bold"
              @click="openPrintDialog('stock_par_lot')"
            >
              {{ tLabel('print_certificate', 'Imprimer Dossier') }}
            </v-btn>
          </div>
        </div>

        <!-- 3-Stage Progress Stepper Bar -->
        <div class="wh-stepper-strip d-flex align-center justify-space-between flex-wrap ga-2 pa-2 rounded-xl border">
          <!-- Step 1 Tab -->
          <div
            class="wh-step-item d-flex align-center ga-2 px-3 py-2 rounded-lg cursor-pointer flex-grow-1"
            :class="{ 'active': traceabilityCurrentStage === 1, 'completed': traceabilityCurrentStage > 1 }"
            @click="goToTraceabilityStage(1)"
          >
            <div class="wh-step-badge">
              <v-icon v-if="traceabilityCurrentStage > 1" size="16" color="#00897B">mdi-check-circle</v-icon>
              <span v-else class="text-caption font-weight-bold">1</span>
            </div>
            <div class="d-flex flex-column">
              <span class="text-caption font-weight-bold wh-step-title">
                {{ tLabel('step_1_title', '1. N° Réception & Bons d\'Entrée') }}
              </span>
              <span class="text-caption text-medium-emphasis wh-step-desc">
                {{ selectedTraceabilityVoucher ? (voucherTypeLabel(selectedTraceabilityVoucher.voucher_type) + ' #' + selectedTraceabilityVoucher.voucher_number) : tLabel('step_1_desc', 'Identifier les bons d\'entrée') }}
              </span>
            </div>
          </div>

          <v-icon size="18" color="#a21ee5" style="opacity: 0.45;" class="d-none d-sm-flex">mdi-chevron-right</v-icon>

          <!-- Step 2 Tab -->
          <div
            class="wh-step-item d-flex align-center ga-2 px-3 py-2 rounded-lg cursor-pointer flex-grow-1"
            :class="{ 'active': traceabilityCurrentStage === 2, 'completed': traceabilityCurrentStage > 2, 'disabled': !selectedTraceabilityVoucher }"
            @click="goToTraceabilityStage(2)"
          >
            <div class="wh-step-badge">
              <v-icon v-if="traceabilityCurrentStage > 2" size="16" color="#00897B">mdi-check-circle</v-icon>
              <span v-else class="text-caption font-weight-bold">2</span>
            </div>
            <div class="d-flex flex-column">
              <span class="text-caption font-weight-bold wh-step-title">
                {{ tLabel('step_2_title', '2. Choix des Articles') }}
              </span>
              <span class="text-caption text-medium-emphasis wh-step-desc">
                {{ selectedTraceabilityArticleIds.length ? (selectedTraceabilityArticleIds.length + ' ' + tLabel('articles_selected', 'article(s)')) : tLabel('step_2_desc', 'Sélectionner espèces à tracer') }}
              </span>
            </div>
          </div>

          <v-icon size="18" color="#a21ee5" style="opacity: 0.45;" class="d-none d-sm-flex">mdi-chevron-right</v-icon>

          <!-- Step 3 Tab -->
          <div
            class="wh-step-item d-flex align-center ga-2 px-3 py-2 rounded-lg cursor-pointer flex-grow-1"
            :class="{ 'active': traceabilityCurrentStage === 3, 'disabled': !selectedTraceabilityArticleIds.length }"
            @click="goToTraceabilityStage(3)"
          >
            <div class="wh-step-badge">
              <span class="text-caption font-weight-bold">3</span>
            </div>
            <div class="d-flex flex-column">
              <span class="text-caption font-weight-bold wh-step-title">
                {{ tLabel('step_3_title', '3. Chronologie & Audit') }}
              </span>
              <span class="text-caption text-medium-emphasis wh-step-desc">
                {{ tLabel('step_3_desc', 'Parcours complet du stock') }}
              </span>
            </div>
          </div>
        </div>
      </v-card>

      <!-- ========================================================================= -->
      <!-- STAGE 1: SEARCH & ENTRANCE VOUCHER SELECTION                              -->
      <!-- ========================================================================= -->
      <div v-if="traceabilityCurrentStage === 1" class="d-flex flex-column ga-4">
        <!-- Search Card with AddCharge-styled Input -->
        <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface" elevation="0">
          <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-3">
            <div>
              <div class="text-subtitle-1 font-weight-bold text-warehouse d-flex align-center ga-2">
                <v-icon size="20" color="#a21ee5">mdi-barcode-scan</v-icon>
                {{ tLabel('search_reception_numbers', 'Recherche par N° de Suivi de Réception') }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ tLabel('search_reception_help', 'Tapez un ou plusieurs numéros de suivi (ex: 511, 514) pour retrouver les bons d\'entrée correspondants.') }}
              </div>
            </div>

            <!-- Status filter toggle -->
            <SegmentedPillToggle
              v-model="traceabilityStatus"
              :items="statusToggleOptions"
              color="#a21ee5"
              @update:modelValue="fetchTraceabilityVouchers"
            />
          </div>

          <!-- AddCharge-styled Combobox Input -->
          <v-row dense class="align-center">
            <v-col cols="12" md="9">
              <v-combobox
                v-model="traceabilityTrackingNumbers"
                v-model:search="traceabilityTrackingSearchInput"
                :items="quickTraceabilityLots"
                :label="tLabel('reception_tracking_numbers', 'Numéros de Suivi de Réception')"
                placeholder="ex : 511, 514"
                multiple
                chips
                density="compact"
                variant="outlined"
                rounded
                clearable
                prepend-inner-icon="mdi-barcode"
                color="#a21ee5"
                class="text-warehouse"
                hide-details
                :loading="traceabilityVouchersLoading"
                @keydown.enter.prevent="addTraceabilityTrackingToken(traceabilityTrackingSearchInput)"
                @update:modelValue="fetchTraceabilityVouchers"
              >
                <template #chip="{ item, index }">
                  <v-chip
                    :key="index"
                    color="#a21ee5"
                    closable
                    variant="tonal"
                    class="font-weight-bold"
                    @click:close="removeTraceabilityTrackingToken(item.value)"
                  >
                    <v-icon size="14" class="mr-1">mdi-barcode</v-icon>
                    #{{ item.title }}
                  </v-chip>
                </template>
              </v-combobox>
            </v-col>

            <v-col cols="12" md="3" class="d-flex align-center ga-2">
              <v-btn
                color="#a21ee5"
                rounded="pill"
                variant="elevated"
                class="font-weight-bold flex-grow-1 text-white"
                prepend-icon="mdi-magnify"
                :loading="traceabilityVouchersLoading"
                @click="fetchTraceabilityVouchers"
              >
                {{ tLabel('search', 'Rechercher') }}
              </v-btn>
              <v-btn
                v-if="traceabilityTrackingNumbers.length"
                variant="tonal"
                color="grey"
                rounded="pill"
                icon="mdi-close"
                size="small"
                @click="traceabilityTrackingNumbers = []; fetchTraceabilityVouchers()"
              />
            </v-col>
          </v-row>

          <!-- Quick Access Chips Carousel -->
          <div v-if="quickTraceabilityLots.length" class="d-flex align-center ga-2 flex-wrap mt-3 pt-2 border-t">
            <span class="text-caption text-medium-emphasis font-weight-bold" style="font-size: 11px;">
              <v-icon size="13" color="#a21ee5" class="mr-1">mdi-lightning-bolt</v-icon>
              {{ tLabel('quick_access', 'Accès direct') }}:
            </span>
            <v-chip
              v-for="lot in quickTraceabilityLots"
              :key="lot"
              size="small"
              rounded="lg"
              :color="traceabilityTrackingNumbers.includes(lot) ? '#a21ee5' : 'grey'"
              :variant="traceabilityTrackingNumbers.includes(lot) ? 'elevated' : 'outlined'"
              class="font-weight-bold cursor-pointer"
              @click="quickSelectTrackingNumber(lot)"
            >
              <v-icon size="12" class="mr-1">mdi-barcode</v-icon>
              #{{ lot }}
            </v-chip>
          </div>
        </v-card>

        <!-- Loading State -->
        <div v-if="traceabilityVouchersLoading" class="wh-panel-card pa-12 text-center rounded-2xl border bg-surface">
          <v-progress-circular indeterminate color="#a21ee5" size="48" width="4" />
          <div class="text-caption text-medium-emphasis mt-3 font-weight-bold">
            {{ tLabel('loading_vouchers', 'Recherche des bons d\'entrée...') }}
          </div>
        </div>

        <!-- Entrance Vouchers Results Grid -->
        <div v-else-if="traceabilityVouchersList.length" class="d-flex flex-column ga-3">
          <div class="d-flex align-center justify-space-between px-1">
            <div class="text-caption font-weight-bold text-medium-emphasis">
              {{ tLabel('matching_entrance_vouchers', 'Bons d\'Entrée Détectés') }} ({{ traceabilityVouchersList.length }}):
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ tLabel('select_voucher_to_continue', 'Cliquez sur un bon pour explorer ses articles') }}
            </div>
          </div>

          <v-row dense>
            <v-col
              v-for="v in traceabilityVouchersList"
              :key="v.key"
              cols="12"
              md="6"
              lg="4"
            >
              <v-card
                class="wh-voucher-card pa-4 rounded-2xl border bg-surface h-100 d-flex flex-column justify-space-between cursor-pointer"
                elevation="0"
                :style="selectedTraceabilityVoucher?.key === v.key ? 'border-color: #a21ee5 !important; box-shadow: 0 4px 20px rgba(162, 30, 229, 0.15) !important;' : ''"
                @click="selectTraceabilityVoucher(v)"
              >
                <div>
                  <!-- Card Header: Voucher Type & Date -->
                  <div class="d-flex align-center justify-space-between ga-2 mb-3">
                    <v-chip
                      size="small"
                      class="font-weight-bold"
                      variant="tonal"
                      :color="v.voucher_type === 'Charge' ? '#42A5F5' : (v.voucher_type === 'Production' ? '#00897B' : (v.voucher_type === 'Inventory' ? '#6D4C41' : '#F9A825'))"
                    >
                      <v-icon size="14" class="mr-1">
                        {{ v.voucher_type === 'Charge' ? 'mdi-truck-delivery' : (v.voucher_type === 'Production' ? 'mdi-factory' : (v.voucher_type === 'Inventory' ? 'mdi-clipboard-list' : 'mdi-swap-horizontal')) }}
                      </v-icon>
                      {{ voucherTypeLabel(v.voucher_type) }} #{{ v.voucher_number }}
                    </v-chip>

                    <div class="d-flex align-center ga-1 text-caption text-medium-emphasis font-weight-bold">
                      <v-icon size="14">mdi-calendar-outline</v-icon>
                      {{ main.formateDate(v.date) }}
                    </div>
                  </div>

                  <!-- Supplier / Partner -->
                  <div class="d-flex align-center ga-2 mb-3">
                    <v-avatar size="32" color="rgba(162, 30, 229, 0.1)">
                      <v-icon size="16" color="#a21ee5">mdi-account</v-icon>
                    </v-avatar>
                    <div class="d-flex flex-column text-truncate">
                      <span class="text-caption font-weight-bold text-truncate">{{ v.supplier_name }}</span>
                      <span class="text-caption text-medium-emphasis" style="font-size: 11px;">
                        {{ v.tracking_numbers.map(t => '#' + t).join(', ') }}
                      </span>
                    </div>
                  </div>

                  <!-- Weight & Stock Status Meter -->
                  <div class="wh-sub-box pa-2.5 rounded-xl border mb-3">
                    <div class="d-flex align-center justify-space-between text-caption mb-1">
                      <span class="text-medium-emphasis">{{ tLabel('remaining_stock', 'Solde en Stock') }}:</span>
                      <b :class="v.remaining_weight > 0 ? 'text-teal' : 'text-grey'">
                        {{ main.formatDisplayNumber(v.remaining_weight) }} / {{ main.formatDisplayNumber(v.total_weight) }} Kg
                      </b>
                    </div>
                    <v-progress-linear
                      :model-value="v.total_weight > 0 ? (v.remaining_weight / v.total_weight) * 100 : 0"
                      height="6"
                      rounded
                      :color="v.remaining_weight > 0 ? '#00897B' : 'grey'"
                      bg-color="rgba(162, 30, 229, 0.12)"
                    />
                  </div>

                  <!-- Articles Preview Chips -->
                  <div class="d-flex align-center ga-1 flex-wrap mb-3">
                    <v-chip
                      v-for="art in v.articles_preview.slice(0, 3)"
                      :key="art"
                      size="x-small"
                      variant="outlined"
                      color="#a21ee5"
                      class="font-weight-medium"
                    >
                      {{ art }}
                    </v-chip>
                    <span v-if="v.articles_count > 3" class="text-caption text-medium-emphasis font-weight-bold" style="font-size: 11px;">
                      +{{ v.articles_count - 3 }} {{ tLabel('others', 'autres') }}
                    </span>
                  </div>
                </div>

                <!-- Action Footer -->
                <div class="d-flex align-center justify-space-between pt-2 border-t">
                  <v-chip
                    size="x-small"
                    class="font-weight-bold"
                    variant="tonal"
                    :color="v.status === 'in_stock' ? '#00897B' : (v.status === 'partially_out' ? '#F9A825' : 'grey')"
                  >
                    {{ lotStatusLabel(v.status) }}
                  </v-chip>

                  <v-btn
                    size="small"
                    color="#a21ee5"
                    variant="flat"
                    rounded="lg"
                    append-icon="mdi-arrow-right"
                    class="font-weight-bold text-white text-caption"
                    @click.stop="selectTraceabilityVoucher(v)"
                  >
                    {{ tLabel('select_voucher', 'Sélectionner') }}
                  </v-btn>
                </div>
              </v-card>
            </v-col>
          </v-row>
        </div>

        <!-- Empty State -->
        <div v-else class="wh-panel-card pa-12 text-center rounded-2xl border bg-surface">
          <v-icon size="48" color="grey" class="mb-2">mdi-barcode-off</v-icon>
          <div class="text-subtitle-1 font-weight-bold text-medium-emphasis">
            {{ tLabel('no_vouchers_found', 'Aucun bon d\'entrée trouvé pour ce numéro de suivi de réception') }}
          </div>
          <div class="text-caption text-medium-emphasis mt-1">
            {{ tLabel('try_different_tracking_number', 'Vérifiez le numéro saisi ou réinitialisez les filtres de statut.') }}
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STAGE 2: ARTICLE SELECTION IN VOUCHER                                     -->
      <!-- ========================================================================= -->
      <div v-else-if="traceabilityCurrentStage === 2 && selectedTraceabilityVoucher" class="d-flex flex-column ga-4">
        <!-- Selected Voucher Summary Banner -->
        <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface" elevation="0">
          <div class="d-flex align-center justify-space-between flex-wrap ga-3">
            <div class="d-flex align-center ga-3">
              <v-btn
                icon="mdi-arrow-left"
                variant="tonal"
                color="#a21ee5"
                size="small"
                rounded="lg"
                @click="goToTraceabilityStage(1)"
              />
              <div>
                <div class="d-flex align-center ga-2 flex-wrap">
                  <span class="text-h6 font-weight-bold text-warehouse">
                    {{ voucherTypeLabel(selectedTraceabilityVoucher.voucher_type) }} #{{ selectedTraceabilityVoucher.voucher_number }}
                  </span>
                  <v-chip size="small" variant="tonal" color="#a21ee5" class="font-weight-bold">
                    <v-icon size="14" class="mr-1">mdi-calendar</v-icon>
                    {{ main.formateDate(selectedTraceabilityVoucher.date) }}
                  </v-chip>
                  <v-chip size="small" variant="outlined" color="grey-darken-1">
                    <v-icon size="14" class="mr-1">mdi-account</v-icon>
                    {{ selectedTraceabilityVoucher.supplier_name }}
                  </v-chip>
                </div>
                <div class="text-caption text-medium-emphasis mt-1">
                  {{ tLabel('select_articles_instruction', 'Sélectionnez les articles / espèces de ce bon que vous souhaitez tracer chronologiquement.') }}
                </div>
              </div>
            </div>

            <div class="d-flex align-center ga-2">
              <v-btn
                variant="outlined"
                color="#a21ee5"
                size="small"
                rounded="lg"
                class="font-weight-bold"
                @click="toggleSelectAllArticles"
              >
                <v-icon size="16" class="mr-1">
                  {{ allArticlesSelectedInVoucher ? 'mdi-checkbox-marked' : (someArticlesSelectedInVoucher ? 'mdi-minus-box' : 'mdi-checkbox-blank-outline') }}
                </v-icon>
                {{ allArticlesSelectedInVoucher ? tLabel('deselect_all', 'Tout Désélectionner') : tLabel('select_all', 'Tout Sélectionner') }}
              </v-btn>

              <v-btn
                color="#a21ee5"
                rounded="lg"
                class="font-weight-bold text-white"
                prepend-icon="mdi-timeline-check-outline"
                :disabled="!selectedTraceabilityArticleIds.length"
                :loading="traceabilityTimelineLoading"
                @click="generateTraceabilityTimeline"
              >
                {{ tLabel('trace_selected_articles', 'Tracer les Articles') }} ({{ selectedTraceabilityArticleIds.length }})
              </v-btn>
            </div>
          </div>
        </v-card>

        <!-- Articles Loading State -->
        <div v-if="traceabilityArticlesLoading" class="wh-panel-card pa-12 text-center rounded-2xl border bg-surface">
          <v-progress-circular indeterminate color="#a21ee5" size="48" width="4" />
          <div class="text-caption text-medium-emphasis mt-3 font-weight-bold">
            {{ tLabel('loading_articles', 'Chargement des articles du bon...') }}
          </div>
        </div>

        <!-- Articles Selection Cards Grid -->
        <v-row v-else dense>
          <v-col
            v-for="art in traceabilityArticlesList"
            :key="art.id"
            cols="12"
            sm="6"
            md="4"
          >
            <v-card
              class="wh-article-select-card pa-4 rounded-2xl border bg-surface cursor-pointer h-100 d-flex flex-column justify-space-between"
              :class="{ 'wh-article-card-selected': selectedTraceabilityArticleIds.includes(art.id) }"
              elevation="0"
              @click="toggleSelectArticle(art.id)"
            >
              <div>
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="d-flex align-center ga-2">
                    <v-checkbox-btn
                      :model-value="selectedTraceabilityArticleIds.includes(art.id)"
                      color="#a21ee5"
                      density="compact"
                      hide-details
                      @click.stop="toggleSelectArticle(art.id)"
                    />
                    <span class="text-subtitle-2 font-weight-bold text-truncate">{{ art.article_name }}</span>
                  </div>
                  <v-chip
                    size="x-small"
                    class="font-weight-bold"
                    variant="tonal"
                    :color="art.clean_type === 'FishType' ? '#a21ee5' : (art.clean_type === 'GuanoType' ? '#6D4C41' : '#00897B')"
                  >
                    {{ articleTypeLabel(art.clean_type) }}
                  </v-chip>
                </div>

                <div class="d-flex align-center ga-2 text-caption text-medium-emphasis mb-3 pl-8">
                  <v-icon size="14" color="#a21ee5">mdi-snowflake</v-icon>
                  <span>{{ art.warehouse_name }}</span>
                </div>

                <div class="wh-sub-box pa-2.5 rounded-xl border mb-2">
                  <div class="d-flex align-center justify-space-between text-caption mb-1">
                    <span class="text-medium-emphasis">{{ tLabel('initial_weight', 'Poids Reçu') }}:</span>
                    <b>{{ main.formatDisplayNumber(art.initial_weight) }} Kg</b>
                  </div>
                  <div class="d-flex align-center justify-space-between text-caption mb-1">
                    <span class="text-medium-emphasis">{{ tLabel('remaining', 'Solde Restant') }}:</span>
                    <b :class="art.remaining_weight > 0 ? 'text-teal font-weight-bold' : 'text-grey'">
                      {{ main.formatDisplayNumber(art.remaining_weight) }} Kg
                    </b>
                  </div>
                  <v-progress-linear
                    :model-value="art.initial_weight > 0 ? (art.remaining_weight / art.initial_weight) * 100 : 0"
                    height="5"
                    rounded
                    :color="art.remaining_weight > 0 ? '#00897B' : 'grey'"
                    bg-color="rgba(162, 30, 229, 0.12)"
                  />
                </div>
              </div>

              <div class="d-flex align-center justify-space-between pt-2 border-t text-caption text-medium-emphasis">
                <span>{{ tLabel('packages', 'Cartons') }}: <b>{{ main.formatDisplayNumber(art.remaining_boxes) }}</b></span>
                <v-chip
                  size="x-small"
                  variant="tonal"
                  :color="art.status === 'in_stock' ? '#00897B' : (art.status === 'partially_out' ? '#F9A825' : 'grey')"
                  class="font-weight-bold"
                >
                  {{ lotStatusLabel(art.status) }}
                </v-chip>
              </div>
            </v-card>
          </v-col>
        </v-row>
      </div>

      <!-- ========================================================================= -->
      <!-- STAGE 3: CHRONOLOGICAL PEDIGREE TIMELINE                                  -->
      <!-- ========================================================================= -->
      <div v-else-if="traceabilityCurrentStage === 3" class="d-flex flex-column ga-4">
        <!-- Top Navigation & KPI Summary Ribbon -->
        <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface" elevation="0">
          <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-4">
            <div class="d-flex align-center ga-3">
              <v-btn
                icon="mdi-arrow-left"
                variant="tonal"
                color="#a21ee5"
                size="small"
                rounded="lg"
                @click="goToTraceabilityStage(2)"
              />
              <div>
                <div class="d-flex align-center ga-2 flex-wrap">
                  <span class="text-h6 font-weight-bold text-warehouse">
                    {{ tLabel('pedigree_timeline_title', 'Filiation & Historique Chronologique') }}
                  </span>
                  <v-chip
                    v-for="lot in (traceabilityTimelineData?.summary?.tracking_numbers || [])"
                    :key="lot"
                    size="small"
                    color="#a21ee5"
                    variant="tonal"
                    class="font-weight-bold cursor-pointer"
                    @click="copyTrackingNumber(lot)"
                  >
                    <v-icon size="14" class="mr-1">mdi-barcode</v-icon>
                    #{{ lot }}
                  </v-chip>
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ voucherTypeLabel(selectedTraceabilityVoucher?.voucher_type) }} #{{ selectedTraceabilityVoucher?.voucher_number }} — {{ selectedTraceabilityArticleIds.length }} {{ tLabel('articles_traced', 'article(s) tracé(s)') }}
                </div>
              </div>
            </div>

            <!-- Filters & Sort -->
            <div class="d-flex align-center ga-2 flex-wrap">
              <v-btn-toggle
                v-model="traceabilityTimelineFilter"
                mandatory
                density="compact"
                rounded="lg"
                color="#a21ee5"
              >
                <v-btn value="all" size="small" class="text-caption font-weight-bold">
                  {{ tLabel('all_events', 'Tous') }} ({{ traceabilityTimelineData?.timeline?.length || 0 }})
                </v-btn>
                <v-btn value="inflow" size="small" class="text-caption font-weight-bold text-teal">
                  <v-icon size="14" class="mr-1">mdi-arrow-down-bold</v-icon>
                  {{ tLabel('inflows', 'Entrées') }}
                </v-btn>
                <v-btn value="transfer" size="small" class="text-caption font-weight-bold text-amber-darken-2">
                  <v-icon size="14" class="mr-1">mdi-swap-horizontal</v-icon>
                  {{ tLabel('transfers', 'Transferts') }}
                </v-btn>
                <v-btn value="production" size="small" class="text-caption font-weight-bold text-indigo">
                  <v-icon size="14" class="mr-1">mdi-factory</v-icon>
                  {{ tLabel('production', 'Atelier') }}
                </v-btn>
                <v-btn value="shipping" size="small" class="text-caption font-weight-bold text-error">
                  <v-icon size="14" class="mr-1">mdi-truck-fast</v-icon>
                  {{ tLabel('shippings', 'Expéditions') }}
                </v-btn>
              </v-btn-toggle>

              <v-btn
                size="small"
                variant="tonal"
                color="#a21ee5"
                rounded="lg"
                :icon="traceabilityTimelineSortOrder === 'asc' ? 'mdi-sort-clock-ascending-outline' : 'mdi-sort-clock-descending-outline'"
                @click="toggleTraceabilityTimelineSort"
              />
            </div>
          </div>

          <!-- Summary KPI Cards Strip -->
          <v-row v-if="traceabilityTimelineData?.summary" dense>
            <v-col cols="6" sm="4" md="2">
              <div class="wh-sub-box pa-3 rounded-xl border text-center">
                <div class="text-caption text-medium-emphasis">{{ tLabel('total_received', 'Poids Reçu') }}</div>
                <div class="text-subtitle-1 font-weight-bold text-teal mt-0.5">
                  {{ main.formatDisplayNumber(traceabilityTimelineData.summary.initial_weight) }} Kg
                </div>
              </div>
            </v-col>
            <v-col cols="6" sm="4" md="2">
              <div class="wh-sub-box pa-3 rounded-xl border text-center">
                <div class="text-caption text-medium-emphasis">{{ tLabel('remaining', 'Solde Restant') }}</div>
                <div class="text-subtitle-1 font-weight-bold text-warehouse mt-0.5">
                  {{ main.formatDisplayNumber(traceabilityTimelineData.summary.remaining_weight) }} Kg
                </div>
              </div>
            </v-col>
            <v-col cols="6" sm="4" md="2">
              <div class="wh-sub-box pa-3 rounded-xl border text-center">
                <div class="text-caption text-medium-emphasis">{{ tLabel('exited_weight', 'Poids Sorti') }}</div>
                <div class="text-subtitle-1 font-weight-bold text-error mt-0.5">
                  {{ main.formatDisplayNumber(traceabilityTimelineData.summary.exited_weight) }} Kg
                </div>
              </div>
            </v-col>
            <v-col cols="6" sm="4" md="2">
              <div class="wh-sub-box pa-3 rounded-xl border text-center">
                <div class="text-caption text-medium-emphasis">{{ tLabel('consumption_rate', 'Taux de Sortie') }}</div>
                <div class="text-subtitle-1 font-weight-bold text-warning mt-0.5">
                  {{ traceabilityTimelineData.summary.consumption_pct }}%
                </div>
              </div>
            </v-col>
            <v-col cols="6" sm="4" md="2">
              <div class="wh-sub-box pa-3 rounded-xl border text-center">
                <div class="text-caption text-medium-emphasis">{{ tLabel('lot_age', 'Ancienneté') }}</div>
                <div class="text-subtitle-1 font-weight-bold mt-0.5">
                  {{ traceabilityTimelineData.summary.age_days }} {{ tLabel('days', 'Jours') }}
                </div>
              </div>
            </v-col>
            <v-col cols="6" sm="4" md="2">
              <div class="wh-sub-box pa-3 rounded-xl border text-center">
                <div class="text-caption text-medium-emphasis">{{ tLabel('active_chambers', 'Chambres') }}</div>
                <div class="text-subtitle-1 font-weight-bold text-truncate mt-0.5">
                  {{ (traceabilityTimelineData.summary.chambers || []).join(', ') || '-' }}
                </div>
              </div>
            </v-col>
          </v-row>
        </v-card>

        <!-- High-Res Vertical Timeline -->
        <v-card class="wh-panel-card pa-4 pa-md-5 rounded-2xl border bg-surface" elevation="0">
          <!-- Timeline Header Strip -->
          <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-4 pb-3 border-b">
            <div class="d-flex align-center ga-2">
              <v-avatar size="30" color="rgba(162, 30, 229, 0.12)">
                <v-icon size="17" color="#a21ee5">mdi-history</v-icon>
              </v-avatar>
              <span class="text-subtitle-1 font-weight-bold text-warehouse">
                {{ tLabel('chronological_events', 'Événements & Mouvements Chronologiques') }}
              </span>
              <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
                {{ filteredTimelineEvents.length }}
              </v-chip>
            </div>
            <v-chip size="small" color="#a21ee5" variant="flat" class="text-white font-weight-bold">
              {{ tLabel('all_events', 'Tous les Événements') }} ({{ filteredTimelineEvents.length }})
            </v-chip>
          </div>

          <div v-if="filteredTimelineEvents.length" class="wh-pedigree-timeline position-relative py-1">
            <div
              v-for="(ev, idx) in filteredTimelineEvents"
              :key="ev.event_id || idx"
              class="wh-timeline-item d-flex align-start ga-3 mb-4 position-relative"
            >
              <!-- Icon Avatar & Connector Line Column -->
              <div class="d-flex flex-column align-center flex-shrink-0" style="width: 44px;">
                <v-avatar size="38" :color="ev.color" class="elevation-1 text-white flex-shrink-0" style="z-index: 2;">
                  <v-icon size="18">{{ ev.icon }}</v-icon>
                </v-avatar>
                <div
                  v-if="idx < filteredTimelineEvents.length - 1"
                  class="wh-timeline-connector"
                  style="width: 2px; flex-grow: 1; min-height: 40px; background: rgba(162, 30, 229, 0.22); margin-top: 4px;"
                />
              </div>

              <!-- Event Card Column -->
              <div
                class="wh-timeline-card pa-3.5 pa-md-4 rounded-xl border flex-grow-1 min-w-0"
                :style="{ borderLeft: '4px solid ' + ev.color + ' !important' }"
              >
                <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-2">
                  <div class="d-flex align-center ga-2 flex-wrap">
                    <span class="text-subtitle-2 font-weight-bold" :style="{ color: ev.color }">
                      {{ formatEventTitle(ev) }}
                    </span>
                    <v-chip size="x-small" variant="tonal" :color="ev.color" class="font-weight-bold">
                      {{ directionLabel(ev) }}
                    </v-chip>
                    <v-chip v-if="ev.clean_type" size="x-small" variant="tonal" :color="tColor(ev.clean_type)" class="font-weight-bold">
                      <v-icon size="12" class="mr-1">{{ tIcon(ev.clean_type) }}</v-icon>
                      {{ articleTypeLabel(ev.clean_type) }}
                    </v-chip>
                    <v-chip size="x-small" variant="outlined" color="#a21ee5" class="font-weight-bold">
                      {{ ev.article }}
                    </v-chip>
                  </div>
                  <div class="d-flex align-center ga-1 text-caption text-medium-emphasis font-weight-bold">
                    <v-icon size="14">mdi-clock-outline</v-icon>
                    {{ main.formateDate(ev.date) }}
                  </div>
                </div>

                <div class="d-flex align-center justify-space-between flex-wrap ga-3 text-caption pt-1">
                  <div class="d-flex align-center ga-3 flex-wrap">
                    <span v-if="ev.article" class="d-flex align-center ga-1">
                      <span class="text-medium-emphasis">{{ tLabel('article_type_name', 'Article') }}:</span>
                      <b>{{ ev.article }}</b>
                    </span>
                    <span v-if="ev.warehouse" class="d-flex align-center ga-1">
                      <v-icon size="14" color="#a21ee5">mdi-snowflake</v-icon>
                      <span class="text-medium-emphasis">{{ tLabel('warehouse', 'Chambre') }}:</span>
                      <b>{{ ev.warehouse }}</b>
                    </span>
                    <span v-if="ev.partner" class="d-flex align-center ga-1 text-medium-emphasis">
                      <v-icon size="14">mdi-handshake-outline</v-icon>
                      <span class="text-medium-emphasis">{{ tLabel('partner', 'Partenaire') }}:</span>
                      <b>{{ formatPartnerLabel(ev.partner) }}</b>
                    </span>
                    <span v-if="ev.lot_number" class="d-flex align-center ga-1 text-warehouse font-weight-bold">
                      <v-icon size="13">mdi-barcode</v-icon>
                      #{{ ev.lot_number }}
                    </span>
                  </div>

                  <div class="d-flex align-center ga-3 font-weight-bold">
                    <span :style="{ color: ev.color }">
                      {{ ev.is_inflow ? '+' : '-' }}{{ main.formatDisplayNumber(ev.weight) }} Kg
                    </span>
                    <span v-if="ev.boxes" class="text-medium-emphasis">
                      ({{ main.formatDisplayNumber(ev.boxes) }} {{ tLabel('packages_short', 'ctns') }})
                    </span>
                    <span v-if="ev.total_price > 0" class="text-teal">
                      {{ main.formatDisplayNumber(ev.total_price) }} Dh
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center pa-10 text-medium-emphasis">
            <v-icon size="40" color="grey" class="mb-2">mdi-timeline-remove-outline</v-icon>
            <div>{{ tLabel('no_events_found', 'Aucun événement chronologique trouvé pour ce filtre') }}</div>
          </div>
        </v-card>
      </div>
    </div>
  </v-card-text>

  <!-- ========================================================================= -->
  <!-- LOT TRACEABILITY / PEDIGREE DIALOG                                        -->
  <!-- ========================================================================= -->
  <v-dialog v-model="traceabilityDialog" max-width="920" scrollable>
    <v-card class="rounded-2xl border" elevation="16">
      <div class="wh-modal-header pa-4 pa-md-5 d-flex align-center justify-space-between">
        <div class="d-flex align-center ga-3">
          <div class="wh-modal-icon">
            <v-icon size="26" color="white">mdi-timeline-text-outline</v-icon>
          </div>
          <div>
            <div class="d-flex align-center ga-2 flex-wrap">
              <span class="text-h6 font-weight-bold text-white">{{ tLabel('lot_pedigree', 'Fiche Traçabilité du N° de Suivi de Réception') }}</span>
              <v-chip size="small" color="white" variant="flat" class="font-weight-black" style="color: #a21ee5 !important;">
                #{{ traceabilityData?.lot_number || selectedLotForTraceability?.lot_number }}
              </v-chip>
            </div>
            <div class="text-white text-caption" style="opacity: 0.88;">
              {{ tLabel('lot_lifecycle', 'Cycle de vie complet: de la réception fournisseur/bateau jusqu\'aux expéditions clients') }}
            </div>
          </div>
        </div>
        <div class="d-flex align-center ga-2">
          <v-btn
            size="small"
            variant="flat"
            color="white"
            class="font-weight-bold"
            style="color: #a21ee5 !important;"
            @click="switchToTraceabilityWithLot(selectedLotForTraceability || traceabilityData); closeTraceability();"
          >
            <v-icon start size="15">mdi-fullscreen</v-icon>
            {{ tLabel('open_in_tab', 'Ouvrir en Plein Écran') }}
          </v-btn>
          <v-btn icon variant="tonal" color="white" size="small" @click="closeTraceability">
            <v-icon size="18">mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <v-card-text class="pa-4 pa-md-5">
        <div v-if="traceabilityLoading" class="d-flex flex-column align-center justify-center pa-12">
          <v-progress-circular indeterminate color="#a21ee5" size="48" width="4" />
          <span class="text-caption text-medium-emphasis mt-3 font-weight-bold">{{ tLabel('loading', 'Chargement de la traçabilité...') }}</span>
        </div>

        <div v-else-if="traceabilityData">
          <v-row dense class="mb-4">
            <v-col cols="6" sm="3">
              <div class="text-caption text-medium-emphasis font-weight-bold">{{ tLabel('status', 'Statut du N° de Suivi') }}</div>
              <v-chip size="small" :color="lotStatusColor(traceabilityData.status)" variant="elevated" class="font-weight-bold text-white mt-1 text-uppercase px-2.5">
                {{ lotStatusLabel(traceabilityData.status) }}
              </v-chip>
            </v-col>
            <v-col cols="6" sm="3">
              <div class="text-caption text-medium-emphasis font-weight-bold">{{ tLabel('age_days', 'Âge du N° de Suivi') }}</div>
              <div class="font-weight-bold text-subtitle-2 mt-1" :style="{ color: ageColor(traceabilityData.summary.age_days) }">
                {{ Math.round(traceabilityData.summary.age_days || 0) }} {{ tLabel('days', 'jours') }} ({{ traceabilityData.summary.first_date }})
              </div>
            </v-col>
            <v-col cols="6" sm="3">
              <div class="text-caption text-medium-emphasis font-weight-bold">{{ tLabel('initial_weight_label', 'Poids Entré') }}</div>
              <div class="font-weight-bold text-subtitle-2 mt-1 text-teal">
                {{ main.formatDisplayNumber(traceabilityData.summary.initial_weight) }} Kg
              </div>
            </v-col>
            <v-col cols="6" sm="3">
              <div class="text-caption text-medium-emphasis font-weight-bold">{{ tLabel('remaining_weight_label', 'Solde Restant') }}</div>
              <div class="font-weight-bold text-subtitle-2 mt-1" style="color: #a21ee5;">
                {{ main.formatDisplayNumber(traceabilityData.summary.remaining_weight) }} Kg
                <span class="text-caption text-medium-emphasis">({{ main.formatDisplayNumber(traceabilityData.summary.remaining_boxes) }} caisses)</span>
              </div>
            </v-col>
          </v-row>

          <v-divider class="my-3" color="#a21ee5" style="opacity: 0.15;" />

          <!-- Chronological Stepper Timeline -->
          <div class="text-subtitle-2 font-weight-bold text-warehouse mb-3 d-flex align-center justify-space-between flex-wrap ga-2">
            <div class="d-flex align-center ga-2">
              <v-icon size="18" color="#a21ee5">mdi-history</v-icon>
              <span>{{ tLabel('chronological_events', 'Événements Chronologiques du N° de Suivi de Réception') }} ({{ traceabilityData.timeline.length }})</span>
            </div>
            <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
              {{ tLabel('all_events', 'Tous les Événements') }} ({{ traceabilityData.timeline.length }})
            </v-chip>
          </div>

          <div class="wh-timeline-container mb-4">
            <div
              v-for="(ev, eidx) in traceabilityData.timeline"
              :key="eidx"
              class="wh-timeline-item d-flex align-start ga-3 mb-3 position-relative"
            >
              <!-- Icon Column -->
              <div class="d-flex flex-column align-center flex-shrink-0" style="width: 36px;">
                <v-avatar size="32" :color="ev.color" class="text-white elevation-1 flex-shrink-0" style="z-index: 2;">
                  <v-icon size="16">{{ ev.icon }}</v-icon>
                </v-avatar>
                <div
                  v-if="eidx < traceabilityData.timeline.length - 1"
                  class="wh-timeline-connector"
                  style="width: 2px; flex-grow: 1; min-height: 28px; background: rgba(162, 30, 229, 0.22); margin-top: 4px;"
                />
              </div>

              <!-- Card Column -->
              <div
                class="wh-timeline-card pa-3 rounded-xl border flex-grow-1 min-w-0"
                :style="{ borderLeft: '3px solid ' + ev.color + ' !important' }"
              >
                <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-1">
                  <div class="d-flex align-center ga-2 flex-wrap">
                    <span class="font-weight-bold text-body-2">{{ formatEventTitle(ev) }}</span>
                    <v-chip size="x-small" :color="ev.color" variant="tonal" class="font-weight-bold">
                      {{ directionLabel(ev) }}
                    </v-chip>
                    <v-chip v-if="ev.clean_type" size="x-small" variant="tonal" :color="tColor(ev.clean_type)" class="font-weight-bold">
                      <v-icon size="11" class="mr-1">{{ tIcon(ev.clean_type) }}</v-icon>
                      {{ articleTypeLabel(ev.clean_type) }}
                    </v-chip>
                  </div>
                  <span class="text-caption text-medium-emphasis font-weight-medium">{{ main.formateDate(ev.date) }}</span>
                </div>
                <div class="d-flex align-center ga-3 text-caption text-medium-emphasis flex-wrap pt-1">
                  <span v-if="ev.article"><b>{{ tLabel('article_type_name', 'Article') }}:</b> {{ ev.article }}</span>
                  <span v-if="ev.warehouse"><b>{{ tLabel('warehouse', 'Chambre') }}:</b> {{ ev.warehouse }}</span>
                  <span v-if="ev.partner"><b>{{ tLabel('partner', 'Partenaire') }}:</b> {{ formatPartnerLabel(ev.partner) }}</span>
                  <span v-if="ev.boxes"><b>{{ tLabel('packages_short', 'Cartons') }}:</b> {{ ev.boxes }}</span>
                  <b :class="ev.is_inflow ? 'text-teal' : 'text-error'" class="ml-auto font-weight-bold">
                    {{ ev.is_inflow ? '+' : '-' }}{{ main.formatDisplayNumber(ev.weight) }} Kg
                  </b>
                </div>
              </div>
            </div>
          </div>

          <!-- Articles Breakdown -->
          <div class="text-subtitle-2 font-weight-bold text-warehouse mb-2 d-flex align-center ga-2">
            <v-icon size="18" color="#a21ee5">mdi-cube-outline</v-icon>
            <span>{{ tLabel('contained_articles', 'Espèces & Articles constitutifs du N° de Suivi de Réception') }} ({{ traceabilityData.articles.length }})</span>
          </div>

          <div class="wh-sub-table-container rounded-xl border overflow-hidden">
            <table class="wh-sub-articles-table">
              <thead>
                <tr>
                  <th class="text-left">{{ tLabel('article_type_name', 'Désignation Article') }}</th>
                  <th class="text-center">{{ tLabel('warehouse', 'Chambre') }}</th>
                  <th class="text-right">{{ tLabel('initial_weight_label', 'Entrée') }} (Kg)</th>
                  <th class="text-right">{{ tLabel('remaining_weight_label', 'Solde') }} (Kg)</th>
                  <th class="text-center">{{ tLabel('package_count', 'Cartons') }}</th>
                  <th class="text-right">{{ tLabel('wap', 'PMP') }}</th>
                  <th class="text-right">{{ tLabel('total_price', 'Valeur') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="art in traceabilityData.articles" :key="art.id">
                  <td class="text-left font-weight-bold">
                    <div class="d-flex align-center ga-2 py-1">
                      <v-icon size="16" :color="tColor(art.clean_type)">{{ tIcon(art.clean_type) }}</v-icon>
                      <span>{{ art.article_name }}</span>
                    </div>
                  </td>
                  <td class="text-center text-caption">{{ art.warehouse_name }}</td>
                  <td class="text-right text-caption">{{ main.formatDisplayNumber(art.initial_weight) }} Kg</td>
                  <td class="text-right font-weight-bold text-teal">{{ main.formatDisplayNumber(art.remaining_weight) }} Kg</td>
                  <td class="text-center text-caption font-weight-bold" style="color: #6D4C41;">{{ main.formatDisplayNumber(art.remaining_boxes) }}</td>
                  <td class="text-right text-caption font-weight-bold" style="color: #a21ee5;">{{ art.wap > 0 ? main.formatDisplayNumber(art.wap) : '-' }}</td>
                  <td class="text-right font-weight-bold text-deep-orange">{{ main.formatDisplayNumber(art.total_price) }} {{ main.currency }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </v-card-text>

      <v-card-actions class="pa-4 border-t">
        <v-btn variant="tonal" color="#a21ee5" prepend-icon="mdi-printer" @click="printReport" rounded="lg" class="font-weight-bold">
          {{ tLabel('print', 'Imprimer Pedigree') }}
        </v-btn>
        <v-spacer />
        <v-btn variant="outlined" color="grey" rounded="lg" @click="closeTraceability">
          {{ tLabel('close', 'Fermer') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- ========================================================================= -->
  <!-- RULE 5: SUMMARY SNACKBAR WITH COUNTDOWN PROGRESS BAR                      -->
  <!-- ========================================================================= -->
  <v-snackbar
    v-model="summarySnackbar.show"
    location="bottom right"
    :color="summarySnackbar.color || '#a21ee5'"
    :timeout="-1"
    elevation="12"
    rounded="xl"
    min-width="340"
    max-width="440"
    class="wh-summary-snackbar"
  >
    <div class="d-flex align-center ga-3">
      <v-avatar size="36" color="white" variant="tonal" rounded="lg">
        <v-icon size="20" color="white">{{ summarySnackbar.icon }}</v-icon>
      </v-avatar>
      <div class="flex-grow-1">
        <div class="font-weight-bold text-body-2 text-white">{{ summarySnackbar.title }}</div>
        <div class="text-caption text-white mt-0.5" style="opacity: 0.92;">{{ summarySnackbar.message }}</div>
      </div>
      <v-btn icon size="x-small" variant="text" color="white" @click="summarySnackbar.show = false">
        <v-icon size="16">mdi-close</v-icon>
      </v-btn>
    </div>
    <!-- Countdown Progress Bar per Rule 5 -->
    <v-progress-linear
      :model-value="snackbarProgress"
      color="white"
      height="3"
      class="mt-2 rounded"
    />
  </v-snackbar>

  <!-- ========================================================================= -->
  <!-- WAREHOUSE PRINT CUSTOMIZER MODAL (Client Invoice Style with Column Picker)  -->
  <!-- ========================================================================= -->
  <WarehousePrintDialog
    v-model="printModalOpen"
    :initial-view="printModalTargetView"
    :lots="lots"
    :lots-summary="lotsSummary"
    :chambers-list="chambersList"
    :species-list="speciesList"
    :journal-list="filteredJournalList"
    :journal-totals="journalTotals"
    :analytics-data="analyticsData"
    @view-change="onPrintModalViewChange"
    @data-loaded="onPrintModalDataLoaded"
    @printed="onDocumentPrinted"
  />
</v-card>
</template>

<script src="./script.js"></script>

<style scoped>
/* ==========================================================================
   BLUEPRINT FISH - WAREHOUSE / STOCK MODULE MODERN STYLING (claude.md)
   REDESIGNED FOR PRACTICALITY, CLARITY & LUXURIOUS ELEMENT SPACING
   ========================================================================== */
.wh-card {
  background: transparent;
  font-family: inherit;
}

/* Hero Header */
.wh-hero {
  background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%);
  border-radius: 18px 18px 0 0;
  padding: 18px 24px;
  color: white;
}
.wh-hero-icon {
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
  border-radius: 14px;
  width: 46px;
  height: 46px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
}
.wh-hero-title {
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}
.wh-hero-sub {
  opacity: 0.9;
  font-size: 0.82rem;
}
.wh-live-chip {
  border-color: rgba(255, 255, 255, 0.45) !important;
}
.wh-pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background-color: #69F0AE;
  display: inline-block;
  animation: whPulse 1.8s infinite;
}
@keyframes whPulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(105, 240, 174, 0.7); }
  70% { transform: scale(1.15); box-shadow: 0 0 0 7px rgba(105, 240, 174, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(105, 240, 174, 0); }
}

/* Quick Hero KPIs (ListProduction style) */
.wh-hero-kpi {
  display: flex;
  flex-direction: column;
  background: rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 12px;
  padding: 6px 14px;
  min-width: 110px;
  text-align: center;
}
.wh-hero-kpi-val {
  font-size: 0.95rem;
  font-weight: 800;
  color: white;
  line-height: 1.2;
}
.wh-hero-kpi-val small {
  font-size: 0.7rem;
  opacity: 0.85;
}
.wh-hero-kpi-lbl {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.85;
  color: white;
  margin-top: 2px;
}

/* Spacious & Practical KPI Cards Strip */
.wh-spacious-kpi {
  background: rgb(var(--v-theme-surface));
  border-radius: 16px;
  border-color: rgba(162, 30, 229, 0.15) !important;
  padding: 14px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  min-height: 84px;
}
.wh-spacious-kpi:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(162, 30, 229, 0.1);
  border-color: rgba(162, 30, 229, 0.35) !important;
}
.wh-spacious-kpi-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.wh-spacious-kpi-info {
  flex-grow: 1;
  min-width: 0;
}
.wh-spacious-kpi-label {
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.75;
}
.wh-spacious-kpi-val {
  font-size: 1.3rem;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.01em;
}
.wh-spacious-kpi-val .wh-unit {
  font-size: 0.8rem;
  font-weight: 600;
  opacity: 0.85;
}
.wh-spacious-kpi-sub {
  font-size: 11px;
  opacity: 0.7;
  display: flex;
  align-items: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Navigation bar */
.wh-nav-bar {
  border-color: rgba(162, 30, 229, 0.18) !important;
}

/* Filter Toolbar */
.wh-filter-toolbar {
  border-color: rgba(162, 30, 229, 0.18) !important;
  background: rgb(var(--v-theme-surface));
}

/* Card Wrapper for Tables */
.wh-table-card {
  border-color: rgba(162, 30, 229, 0.18) !important;
  box-shadow: 0 2px 12px rgba(162, 30, 229, 0.04);
}

/* ==========================================================================
   REDESIGNED MODERN TABLE - PRACTICAL, EASY TO USE, GENEROUS BREATHING ROOM
   ========================================================================== */
.wh-table-wrap {
  overflow-x: auto;
  width: 100%;
  -webkit-overflow-scrolling: touch;
}
.wh-modern-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.86rem;
}
.wh-lot-main-table {
  min-width: 1160px;
}
.wh-modern-table thead tr {
  background: rgba(162, 30, 229, 0.06);
}
.wh-modern-table th {
  font-size: 11.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #a21ee5;
  border-bottom: 2px solid rgba(162, 30, 229, 0.22);
  white-space: nowrap;
  transition: background 0.15s ease, color 0.15s ease;
}
.wh-modern-table td {
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  vertical-align: middle;
}

/* DENSITY VARIATIONS (User selectable via header toggle!) */
/* 1. Spacious Density (DEFAULT - Elements spacefully between each other) */
.wh-modern-table.density-spacious th {
  padding: 14px 18px;
}
.wh-modern-table.density-spacious td {
  padding: 16px 20px;
}
.wh-modern-table.density-spacious .wh-lot-row {
  min-height: 72px;
}

/* 2. Comfortable Density */
.wh-modern-table.density-comfortable th {
  padding: 11px 14px;
}
.wh-modern-table.density-comfortable td {
  padding: 12px 14px;
}

/* 3. Compact Density */
.wh-modern-table.density-compact th {
  padding: 8px 10px;
  font-size: 10.5px;
}
.wh-modern-table.density-compact td {
  padding: 8px 10px;
  font-size: 0.82rem;
}

/* Interactive Sort Headers & Multi-Filters */
.wh-sort-th {
  cursor: pointer;
  user-select: none;
}
.wh-sort-th:hover {
  background: rgba(162, 30, 229, 0.12) !important;
  color: #7b1fa2 !important;
}
.wh-sort-th.is-sorted {
  background: rgba(162, 30, 229, 0.14) !important;
  color: #6a1b9a !important;
}
.wh-sort-th.has-filter-active,
th.has-filter-active {
  background: rgba(162, 30, 229, 0.12) !important;
  color: #6a1b9a !important;
  border-bottom: 2px solid #a21ee5 !important;
}

/* Active Filters Summary Bar */
.wh-active-filters-bar {
  background: rgba(162, 30, 229, 0.035) !important;
  border-color: rgba(162, 30, 229, 0.16) !important;
}

/* Lot Row Hover & States */
.wh-lot-row {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 3px solid transparent;
}
.wh-lot-row:hover {
  background: rgba(162, 30, 229, 0.04) !important;
  border-left-color: rgba(162, 30, 229, 0.6);
}
.wh-lot-row.is-expanded {
  background: rgba(162, 30, 229, 0.065) !important;
  border-left-color: #a21ee5;
}
.wh-lot-row.is-depleted {
  opacity: 0.62;
}

/* 1. Chevron Box */
.wh-chevron-box {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  background: rgba(162, 30, 229, 0.06);
  border: 1px solid rgba(162, 30, 229, 0.12);
  transition: all 0.2s ease;
}
.wh-chevron-box.is-active {
  background: rgba(162, 30, 229, 0.18);
  border-color: rgba(162, 30, 229, 0.35);
  transform: scale(1.05);
}
.wh-lot-row:hover .wh-chevron-box {
  background: rgba(162, 30, 229, 0.14);
  border-color: rgba(162, 30, 229, 0.25);
}

/* 2. Tracking ID Badge & Articles Tag */
.wh-tracking-identity-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
  min-width: 170px;
}
.wh-lot-id-badge {
  display: inline-flex;
  align-items: center;
  background: linear-gradient(135deg, rgba(162, 30, 229, 0.08) 0%, rgba(192, 21, 180, 0.04) 100%);
  padding: 6px 12px;
  border-radius: 10px;
  border: 1px solid rgba(162, 30, 229, 0.22);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(162, 30, 229, 0.05);
}
.wh-lot-id-badge:hover {
  background: rgba(162, 30, 229, 0.15);
  border-color: #a21ee5;
  box-shadow: 0 3px 10px rgba(162, 30, 229, 0.2);
  transform: translateY(-1px);
}
.wh-lot-barcode-icon {
  opacity: 0.9;
}
.wh-lot-number {
  font-weight: 800;
  font-size: 0.95rem;
  color: #a21ee5;
  letter-spacing: -0.01em;
  font-feature-settings: 'tnum';
}
.wh-copy-icon {
  opacity: 0.55;
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.wh-lot-id-badge:hover .wh-copy-icon {
  opacity: 1;
  transform: scale(1.15);
}
.wh-lot-articles-meta {
  display: flex;
  align-items: center;
}
.wh-articles-pill {
  display: inline-flex;
  align-items: center;
  background: rgba(162, 30, 229, 0.06);
  border: 1px solid rgba(162, 30, 229, 0.18);
  border-radius: 7px;
  padding: 3.5px 9px;
  font-size: 11.5px;
  font-weight: 600;
  color: #7b1fa2;
  letter-spacing: 0.01em;
  transition: all 0.15s ease;
}
.wh-lot-row:hover .wh-articles-pill {
  background: rgba(162, 30, 229, 0.11);
  border-color: rgba(162, 30, 229, 0.28);
}

/* 3. Dedicated Status Badge */
.wh-status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 11px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 750;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: nowrap;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.wh-status-badge.status-in_stock {
  background: rgba(0, 137, 123, 0.10);
  color: #00796B;
  border: 1px solid rgba(0, 137, 123, 0.32);
}
.wh-status-badge.status-partially_out {
  background: rgba(245, 124, 0, 0.10);
  color: #E65100;
  border: 1px solid rgba(245, 124, 0, 0.32);
}
.wh-status-badge.status-depleted {
  background: rgba(117, 117, 117, 0.10);
  color: #616161;
  border: 1px solid rgba(117, 117, 117, 0.25);
}

/* 4. Age Badge */
.wh-date-age-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;
  min-width: 130px;
}
.wh-date-row {
  white-space: nowrap;
  font-size: 0.88rem;
  letter-spacing: -0.01em;
}
.wh-age-badge {
  display: inline-flex;
  align-items: center;
  padding: 3.5px 10px;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.02em;
  white-space: nowrap;
}
.wh-age-badge.age-fresh {
  background: rgba(0, 137, 123, 0.10);
  color: #00796B;
  border: 1px solid rgba(0, 137, 123, 0.26);
}
.wh-age-badge.age-optimal {
  background: rgba(30, 136, 229, 0.10);
  color: #1565C0;
  border: 1px solid rgba(30, 136, 229, 0.26);
}
.wh-age-badge.age-warning {
  background: rgba(245, 124, 0, 0.10);
  color: #E65100;
  border: 1px solid rgba(245, 124, 0, 0.26);
}
.wh-age-badge.age-alert {
  background: rgba(211, 47, 47, 0.10);
  color: #C62828;
  border: 1px solid rgba(211, 47, 47, 0.26);
}

/* 5. Supplier Cell */
.wh-supplier-cell {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 195px;
}
.wh-supplier-avatar {
  border-color: rgba(162, 30, 229, 0.2) !important;
  box-shadow: 0 1px 3px rgba(162, 30, 229, 0.06);
}
.wh-supplier-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-grow: 1;
}
.wh-supplier-name {
  color: inherit;
  font-weight: 700;
  font-size: 0.88rem;
  line-height: 1.25;
  letter-spacing: -0.01em;
}
.wh-vessel-meta {
  font-size: 11px;
  color: #00796B;
  background: rgba(0, 137, 123, 0.08);
  border: 1px solid rgba(0, 137, 123, 0.2);
  border-radius: 6px;
  padding: 2px 7px;
  width: fit-content;
  max-width: 100%;
}

/* 6. Chamber Badge */
.wh-chamber-badge {
  display: inline-flex;
  align-items: center;
  background: rgba(162, 30, 229, 0.07);
  border: 1px solid rgba(162, 30, 229, 0.22);
  border-radius: 8px;
  padding: 4px 10px;
  font-size: 12px;
  color: #a21ee5;
  white-space: nowrap;
}
.wh-multi-chambers-tag {
  color: #7b1fa2;
  font-size: 10.5px;
}

/* 7. Species Tags */
.wh-species-container {
  min-width: 160px;
}
.wh-species-tag {
  display: inline-flex;
  align-items: center;
  background: rgba(0, 137, 123, 0.08);
  border: 1px solid rgba(0, 137, 123, 0.22);
  border-radius: 6px;
  padding: 2.5px 7px;
  font-size: 11px;
  font-weight: 600;
  color: #00695C;
  white-space: nowrap;
}
.wh-species-overflow-tag {
  display: inline-flex;
  align-items: center;
  background: rgba(120, 144, 156, 0.12);
  border: 1px solid rgba(120, 144, 156, 0.28);
  border-radius: 6px;
  padding: 2.5px 6px;
  font-size: 10.5px;
  font-weight: 700;
  color: #455A64;
  cursor: pointer;
}

/* 8. Balance Cell */
.wh-balance-cell {
  min-width: 140px;
}

/* 9. Boxes Badge */
.wh-boxes-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(109, 76, 65, 0.08);
  border: 1px solid rgba(109, 76, 65, 0.22);
  border-radius: 8px;
  padding: 4px 10px;
  font-size: 12px;
  color: #6D4C41;
  white-space: nowrap;
}

/* 10. Valuation Cell */
.wh-valuation-cell {
  min-width: 130px;
}

/* 11. Action Buttons */
.wh-action-btn {
  transition: transform 0.15s ease, background-color 0.15s ease;
}
.wh-action-btn:hover {
  transform: translateY(-1px);
}

/* Expanded Panel */
.wh-lot-expanded-panel {
  background: rgba(162, 30, 229, 0.035);
  border-top: 1px solid rgba(162, 30, 229, 0.15);
  border-bottom: 2px solid rgba(162, 30, 229, 0.3);
}
.wh-sub-table-container {
  background: rgb(var(--v-theme-surface));
  border-color: rgba(162, 30, 229, 0.18) !important;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
}
.wh-sub-articles-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.84rem;
}
.wh-sub-articles-table th {
  background: rgba(162, 30, 229, 0.07);
  padding: 10px 14px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  color: #a21ee5;
  border-bottom: 1px solid rgba(162, 30, 229, 0.18);
  white-space: nowrap;
}
.wh-sub-articles-table td {
  padding: 12px 14px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  vertical-align: middle;
}
.wh-sub-art-row:hover {
  background: rgba(162, 30, 229, 0.03);
}
.wh-mini-chevron-box {
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: rgba(162, 30, 229, 0.06);
}
.wh-ops-history-box {
  background: rgba(211, 47, 47, 0.03);
  border-color: rgba(211, 47, 47, 0.18) !important;
}
.wh-mini-ops-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}
.wh-mini-ops-table th {
  padding: 8px 10px;
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  color: #D32F2F;
  border-bottom: 1px solid rgba(211, 47, 47, 0.15);
}
.wh-mini-ops-table td {
  padding: 8px 10px;
  border-bottom: 1px solid rgba(211, 47, 47, 0.08);
}

/* Full-Width VCalendar Range Container */
.wh-vcalendar-panel {
  border-color: rgba(162, 30, 229, 0.2) !important;
}
.wh-vcalendar-inner {
  border-color: rgba(162, 30, 229, 0.2) !important;
  background: rgba(162, 30, 229, 0.02);
}
.wh-range-kpi-pill {
  border-color: rgba(0, 0, 0, 0.08) !important;
}

/* Continuous Scroll Table with Sticky Header */
.wh-journal-scroll-container {
  max-height: 680px;
  overflow-y: auto;
}
.wh-scroll-stream-header {
  position: sticky;
  top: 0;
  z-index: 3;
}
.wh-sticky-table thead th {
  position: sticky;
  top: 36px;
  z-index: 2;
  background: rgba(245, 240, 250, 0.98);
  backdrop-filter: blur(4px);
}

/* Chamber Cards */
.wh-chamber-card {
  border: 1px solid rgba(162, 30, 229, 0.16) !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgb(var(--v-theme-surface));
  position: relative;
}
.wh-chamber-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(162, 30, 229, 0.14);
  border-color: rgba(162, 30, 229, 0.38) !important;
}
.wh-chamber-card.is-empty {
  opacity: 0.7;
  border-color: rgba(158, 158, 158, 0.2) !important;
}
.wh-chamber-card.is-empty:hover {
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}
.wh-chamber-header {
  gap: 10px;
}
.wh-chamber-identity {
  gap: 14px;
}
.wh-chamber-icon {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(162, 30, 229, 0.08);
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.wh-chamber-card:hover .wh-chamber-icon {
  transform: scale(1.08);
}
.wh-chamber-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
  flex: 1 1 auto;
}
.wh-chamber-name {
  font-weight: 700;
  font-size: 1.05rem;
  letter-spacing: -0.01em;
  color: rgb(var(--v-theme-on-surface));
  line-height: 1.3;
}
.wh-chamber-sub {
  font-size: 0.78rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  line-height: 1.25;
  margin-top: 2px;
}
.wh-chamber-chip {
  letter-spacing: 0.02em;
  height: 24px;
  white-space: nowrap !important;
  flex-shrink: 0 !important;
  max-width: fit-content !important;
}

.wh-nav-bar {
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.wh-nav-bar::-webkit-scrollbar {
  display: none;
}

/* Timeline in Traceability Modal */
.wh-modal-header {
  background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%);
  color: white;
}
.wh-modal-icon {
  background: rgba(255, 255, 255, 0.2);
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.wh-timeline-container {
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: relative;
  padding-left: 10px;
}
.wh-timeline-node {
  display: flex;
  gap: 16px;
  position: relative;
}
.wh-node-badge {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 2;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}
.wh-node-line {
  position: absolute;
  top: 32px;
  left: 15px;
  bottom: -18px;
  width: 2px;
  background: rgba(162, 30, 229, 0.22);
  z-index: 1;
}
.wh-node-card {
  flex-grow: 1;
  background: rgb(var(--v-theme-surface));
  border-color: rgba(0, 0, 0, 0.08) !important;
}

/* Custom Purple VCalendar overrides */
:deep(.wh-vcalendar.vc-container) {
  border: none !important;
  font-family: inherit;
  width: 100% !important;
  background: transparent !important;
}
:deep(.wh-vcalendar .vc-header) {
  padding-bottom: 8px;
}
:deep(.wh-vcalendar .vc-title) {
  font-weight: 700;
  color: #a21ee5;
}
:deep(.wh-vcalendar .vc-highlight) {
  background-color: #a21ee5 !important;
}

/* Analytics & Intelligence Suite Styling */
.wh-analytics-kpi-card {
  background: rgb(var(--v-theme-surface));
  border-radius: 14px;
  padding: 12px 14px;
  min-height: 86px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.wh-analytics-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}
.wh-akpi-label {
  font-size: 11px;
  font-weight: 750;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.wh-akpi-val {
  font-size: 1.25rem;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.01em;
}
.wh-akpi-val .wh-unit {
  font-size: 0.8rem;
  font-weight: 600;
  opacity: 0.85;
}
.wh-akpi-sub {
  font-size: 11px;
  opacity: 0.8;
}
.wh-aging-card {
  background: rgb(var(--v-theme-surface));
  border-radius: 12px;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.wh-aging-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}
.wh-sub-box {
  background: #faf6fe !important;
  border: 1px solid rgba(162, 30, 229, 0.14) !important;
  color: inherit !important;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}
.wh-light .wh-sub-box {
  background: #faf6fe !important;
  border-color: rgba(162, 30, 229, 0.14) !important;
  color: inherit !important;
}
.wh-dark .wh-sub-box,
:deep(.v-theme--dark) .wh-sub-box,
.v-theme--dark .wh-sub-box {
  background: rgba(162, 30, 229, 0.14) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
  color: inherit !important;
}

/* Scrollbar refinement */
.wh-table-wrap::-webkit-scrollbar {
  height: 8px;
  width: 8px;
}
.wh-table-wrap::-webkit-scrollbar-track {
  background: rgba(162, 30, 229, 0.04);
}
.wh-table-wrap::-webkit-scrollbar-thumb {
  background: rgba(162, 30, 229, 0.25);
  border-radius: 4px;
}
.wh-table-wrap::-webkit-scrollbar-thumb:hover {
  background: rgba(162, 30, 229, 0.45);
}

/* 3-Stage Traceability Workflow Styling */
.wh-stepper-strip {
  background: #faf6fe !important;
  border: 1px solid rgba(162, 30, 229, 0.16) !important;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}
.wh-light .wh-stepper-strip {
  background: #faf6fe !important;
  border-color: rgba(162, 30, 229, 0.16) !important;
}
.wh-step-item {
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(162, 30, 229, 0.12);
  transition: all 0.2s ease;
  border-radius: 12px;
}
.wh-light .wh-step-item {
  background: #ffffff !important;
  border-color: rgba(162, 30, 229, 0.14) !important;
}
.wh-step-item:hover:not(.disabled) {
  background: #ffffff;
  border-color: rgba(162, 30, 229, 0.28);
}
.wh-step-item.active {
  background: #ffffff !important;
  border: 2px solid #a21ee5 !important;
  box-shadow: 0 3px 12px rgba(162, 30, 229, 0.18) !important;
}
.wh-step-item .wh-step-title {
  color: rgb(var(--v-theme-on-surface));
  font-weight: 700;
  transition: color 0.2s ease;
}
.wh-light .wh-step-item .wh-step-title {
  color: #262626 !important;
}
.wh-step-item.active .wh-step-title {
  color: #a21ee5 !important;
  font-weight: 800;
}
.wh-step-item .wh-step-desc {
  color: rgba(var(--v-theme-on-surface), 0.65);
}
.wh-light .wh-step-item .wh-step-desc {
  color: #666666 !important;
}
.wh-step-item.completed {
  opacity: 0.95;
}
.wh-step-item.disabled {
  opacity: 0.65;
  cursor: not-allowed;
}
.wh-step-badge {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(162, 30, 229, 0.1);
  color: #a21ee5;
  font-weight: 800;
  flex-shrink: 0;
  transition: background-color 0.2s ease, color 0.2s ease;
}
.wh-light .wh-step-badge {
  background: rgba(162, 30, 229, 0.1) !important;
  color: #a21ee5 !important;
}
.wh-step-item.active .wh-step-badge {
  background: #a21ee5 !important;
  color: white !important;
  box-shadow: 0 2px 6px rgba(162, 30, 229, 0.35);
}
.wh-voucher-card {
  background: rgb(var(--v-theme-surface));
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.wh-light .wh-voucher-card {
  background: #ffffff !important;
  border-color: rgba(0, 0, 0, 0.08) !important;
}
.wh-voucher-card:hover {
  transform: translateY(-2px);
  border-color: rgba(162, 30, 229, 0.35) !important;
  box-shadow: 0 4px 16px rgba(162, 30, 229, 0.08);
}
.wh-article-select-card {
  background: rgb(var(--v-theme-surface));
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
  opacity: 0.88;
}
.wh-light .wh-article-select-card {
  background: #ffffff !important;
  border-color: rgba(0, 0, 0, 0.08) !important;
}
.wh-article-select-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 14px rgba(162, 30, 229, 0.08);
  opacity: 1;
}
.wh-article-select-card.wh-article-card-selected {
  border-color: #a21ee5 !important;
  background: rgba(162, 30, 229, 0.04) !important;
  opacity: 1;
  box-shadow: 0 4px 16px rgba(162, 30, 229, 0.12);
}
.wh-timeline-card {
  background: rgb(var(--v-theme-surface));
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.wh-light .wh-timeline-card {
  background: #ffffff !important;
  border-color: rgba(0, 0, 0, 0.08) !important;
}
.wh-timeline-card:hover {
  transform: translateX(2px);
  box-shadow: 0 4px 16px rgba(162, 30, 229, 0.08);
}
.wh-pedigree-timeline {
  padding-left: 4px;
}
.wh-timeline-connector {
  width: 2px;
  background: rgba(162, 30, 229, 0.22) !important;
}

/* Dark Theme Overrides for Traceability View */
.wh-dark .wh-stepper-strip,
:deep(.v-theme--dark) .wh-stepper-strip,
.v-theme--dark .wh-stepper-strip {
  background: rgba(162, 30, 229, 0.14) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}
.wh-dark .wh-step-item,
:deep(.v-theme--dark) .wh-step-item,
.v-theme--dark .wh-step-item {
  background: rgba(255, 255, 255, 0.04) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}
.wh-dark .wh-step-item .wh-step-title,
:deep(.v-theme--dark) .wh-step-item .wh-step-title,
.v-theme--dark .wh-step-item .wh-step-title {
  color: rgba(255, 255, 255, 0.88) !important;
}
.wh-dark .wh-step-item .wh-step-desc,
:deep(.v-theme--dark) .wh-step-item .wh-step-desc,
.v-theme--dark .wh-step-item .wh-step-desc {
  color: rgba(255, 255, 255, 0.55) !important;
}
.wh-dark .wh-step-item.active,
:deep(.v-theme--dark) .wh-step-item.active,
.v-theme--dark .wh-step-item.active {
  background: rgb(var(--v-theme-surface)) !important;
  border-color: rgba(162, 30, 229, 0.6) !important;
  box-shadow: 0 2px 14px rgba(162, 30, 229, 0.28) !important;
}
.wh-dark .wh-step-item:hover:not(.disabled),
:deep(.v-theme--dark) .wh-step-item:hover:not(.disabled),
.v-theme--dark .wh-step-item:hover:not(.disabled) {
  background: rgba(162, 30, 229, 0.15) !important;
}
.wh-dark .wh-step-badge,
:deep(.v-theme--dark) .wh-step-badge,
.v-theme--dark .wh-step-badge {
  background: rgba(162, 30, 229, 0.22) !important;
  color: #ce70ff !important;
}
.wh-dark .wh-step-item.active .wh-step-badge,
:deep(.v-theme--dark) .wh-step-item.active .wh-step-badge,
.v-theme--dark .wh-step-item.active .wh-step-badge {
  background: #a21ee5 !important;
  color: white !important;
}
.wh-dark .wh-voucher-card,
:deep(.v-theme--dark) .wh-voucher-card,
.v-theme--dark .wh-voucher-card {
  background: rgb(var(--v-theme-surface)) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}
.wh-dark .wh-voucher-card:hover,
:deep(.v-theme--dark) .wh-voucher-card:hover,
.v-theme--dark .wh-voucher-card:hover {
  border-color: rgba(162, 30, 229, 0.65) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.45) !important;
}
.wh-dark .wh-article-select-card,
:deep(.v-theme--dark) .wh-article-select-card,
.v-theme--dark .wh-article-select-card {
  background: rgb(var(--v-theme-surface)) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}
.wh-dark .wh-article-select-card.wh-article-card-selected,
:deep(.v-theme--dark) .wh-article-select-card.wh-article-card-selected,
.v-theme--dark .wh-article-select-card.wh-article-card-selected {
  border-color: #a21ee5 !important;
  background: rgba(162, 30, 229, 0.18) !important;
  box-shadow: 0 4px 20px rgba(162, 30, 229, 0.28) !important;
}
.wh-dark .wh-article-select-card:hover,
:deep(.v-theme--dark) .wh-article-select-card:hover,
.v-theme--dark .wh-article-select-card:hover {
  border-color: rgba(162, 30, 229, 0.45) !important;
}
.wh-dark .wh-timeline-card,
:deep(.v-theme--dark) .wh-timeline-card,
.v-theme--dark .wh-timeline-card {
  background: rgb(var(--v-theme-surface)) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}
.wh-dark .wh-timeline-card:hover,
:deep(.v-theme--dark) .wh-timeline-card:hover,
.v-theme--dark .wh-timeline-card:hover {
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.38) !important;
}
.wh-dark .wh-timeline-connector,
:deep(.v-theme--dark) .wh-timeline-connector,
.v-theme--dark .wh-timeline-connector {
  background: rgba(162, 30, 229, 0.35) !important;
}
</style>
