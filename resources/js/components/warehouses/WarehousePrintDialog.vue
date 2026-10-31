<template>
  <v-dialog
    v-model="internalOpen"
    max-width="1340"
    width="96vw"
    scrollable
    transition="dialog-bottom-transition"
  >
    <v-card class="wh-print-modal-card rounded-2xl overflow-hidden d-flex flex-column" elevation="24">
      <!-- HERO HEADER (claude.md Warehouse Gradient) -->
      <div class="wh-print-hero px-5 py-3 text-white d-flex align-center justify-space-between flex-shrink-0">
        <div class="d-flex align-center ga-3">
          <div class="wh-print-hero-icon">
            <v-icon size="24" color="white">mdi-printer</v-icon>
          </div>
          <div>
            <div class="text-subtitle-1 font-weight-bold d-flex align-center ga-2">
              <span>{{ tLabel('warehouse_print_modal_title', 'Mise en Page d\'Impression des Entrepôts') }}</span>
              <v-chip size="x-small" color="white" variant="outlined" class="font-weight-bold text-uppercase">
                {{ currentViewLabel }}
              </v-chip>
            </div>
            <div class="text-caption text-white" style="opacity: 0.88; font-size: 11.5px !important;">
              {{ tLabel('warehouse_print_modal_sub', 'Choisissez les colonnes, l\'orientation et personnalisez la mise en page d\'impression') }}
            </div>
          </div>
        </div>

        <div class="d-flex align-center ga-2">
          <!-- Print Button (Primary) with v-print -->
          <v-btn
            v-print="printConfig"
            variant="flat"
            color="white"
            size="small"
            rounded="lg"
            class="font-weight-bold text-uppercase"
            prepend-icon="mdi-printer"
            style="color: #a21ee5 !important;"
            @click="onPrintTriggered"
          >
            {{ tLabel('print_now', 'Imprimer') }}
          </v-btn>

          <v-btn icon variant="text" color="white" size="small" @click="close">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <!-- SPLIT MAIN BODY: CONTROLS (LEFT) + LIVE PRINT PREVIEW (RIGHT) -->
      <div class="wh-print-split-body d-flex flex-column flex-md-row flex-grow-1 overflow-hidden">
        <!-- ===================================================================== -->
        <!-- LEFT PANE: CONTROLS & COLUMN PICKER                                   -->
        <!-- ===================================================================== -->
        <div class="wh-print-controls-pane d-flex flex-column border-e">
          <!-- Pinned Top: Target View Switcher -->
          <div class="pa-3 border-b bg-surface flex-shrink-0">
            <div class="d-flex align-center justify-space-between mb-1.5">
              <span class="text-caption font-weight-bold text-warehouse text-uppercase" style="letter-spacing: 0.04em;">
                <v-icon size="14" color="#a21ee5" class="mr-1">mdi-view-headline</v-icon>
                {{ tLabel('target_view_to_print', 'Vue à Imprimer') }}
              </span>
              <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
                <v-progress-circular v-if="loadingData" size="10" width="2" indeterminate color="#a21ee5" class="mr-1" />
                {{ printableRowsCount }} {{ tLabel('records', 'lignes') }}
              </v-chip>
            </div>

            <SegmentedPillToggle
              v-model="selectedView"
              :items="viewOptions"
              color="#a21ee5"
              size="x-small"
              block
              @update:modelValue="onViewChanged"
            />
          </div>

          <!-- Controls Scrollable Form Area -->
          <div class="wh-print-controls-scroll pa-4 overflow-y-auto flex-grow-1">
            <!-- 1. COLUMN PICKER (Client Invoice Style) -->
            <div class="wh-control-section mb-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="d-flex align-center ga-2 text-caption font-weight-bold text-warehouse">
                  <v-icon size="16" color="#a21ee5">mdi-table-headers-eye</v-icon>
                  <span>{{ tLabel('selected_columns_count', 'Colonnes à Imprimer') }}</span>
                </div>
                <div class="d-flex align-center ga-1">
                  <v-btn size="x-small" variant="text" color="#a21ee5" class="font-weight-bold" @click="selectAllColumns">
                    {{ tLabel('select_all_columns', 'Toutes') }}
                  </v-btn>
                  <span class="text-grey">•</span>
                  <v-btn size="x-small" variant="text" color="grey" class="font-weight-bold" @click="deselectAllColumns">
                    {{ tLabel('deselect_all_columns', 'Vider') }}
                  </v-btn>
                </div>
              </div>

              <!-- Multi-select Autocomplete with AddCharge style -->
              <v-autocomplete
                v-model="activeColumnKeys"
                :items="availableColumns"
                item-value="key"
                item-title="label"
                multiple
                chips
                closable-chips
                density="compact"
                variant="outlined"
                rounded="lg"
                color="#a21ee5"
                hide-details
                :placeholder="tLabel('choose_columns_to_print', 'Choisir les colonnes à imprimer...')"
                prepend-inner-icon="mdi-view-column-outline"
                class="mb-2"
              >
                <template #chip="{ props, item }">
                  <v-chip
                    v-bind="props"
                    size="x-small"
                    color="#a21ee5"
                    variant="tonal"
                    class="font-weight-medium my-0.5"
                    closable
                  >
                    {{ item.raw.label }}
                  </v-chip>
                </template>
                <template #item="{ props, item }">
                  <v-list-item v-bind="props" :title="item.raw.label">
                    <template #prepend="{ isSelected }">
                      <v-checkbox-btn :model-value="isSelected" color="#a21ee5" density="compact" />
                    </template>
                  </v-list-item>
                </template>
              </v-autocomplete>

              <!-- Quick Columns Toggle Checklist Strip -->
              <div class="wh-quick-col-list pa-2 rounded-xl border bg-surface-variant-subtle">
                <div class="d-flex flex-wrap ga-1">
                  <v-chip
                    v-for="col in availableColumns"
                    :key="col.key"
                    size="x-small"
                    rounded="lg"
                    :color="activeColumnKeys.includes(col.key) ? '#a21ee5' : 'grey'"
                    :variant="activeColumnKeys.includes(col.key) ? 'flat' : 'outlined'"
                    class="cursor-pointer font-weight-medium"
                    @click="toggleColumn(col.key)"
                  >
                    <v-icon start size="12">{{ activeColumnKeys.includes(col.key) ? 'mdi-check' : 'mdi-plus' }}</v-icon>
                    {{ col.label }}
                  </v-chip>
                </div>
              </div>
            </div>

            <!-- 2. ORIENTATION & FONT SIZE -->
            <div class="wh-control-section mb-4">
              <div class="d-flex align-center ga-2 text-caption font-weight-bold text-warehouse mb-2">
                <v-icon size="16" color="#a21ee5">mdi-page-layout-header</v-icon>
                <span>{{ tLabel('print_orientation', 'Format & Orientation de Page') }}</span>
              </div>

              <v-row dense class="mb-2">
                <v-col cols="12" sm="6">
                  <div class="text-caption text-medium-emphasis mb-1 font-weight-medium">{{ tLabel('print_orientation', 'Orientation') }}:</div>
                  <SegmentedPillToggle
                    v-model="orientation"
                    :items="[
                      { value: 'landscape', label: tLabel('orientation_landscape', 'Paysage'), icon: 'mdi-page-layout-header' },
                      { value: 'portrait', label: tLabel('orientation_portrait', 'Portrait'), icon: 'mdi-page-layout-body' },
                    ]"
                    color="#a21ee5"
                    size="x-small"
                    block
                  />
                </v-col>

                <v-col cols="12" sm="6">
                  <div class="text-caption text-medium-emphasis mb-1 font-weight-medium">{{ tLabel('font_size', 'Taille de Police') }}:</div>
                  <SegmentedPillToggle
                    v-model="fontSize"
                    :items="[
                      { value: 'compact', label: tLabel('font_compact', 'Compact') },
                      { value: 'normal', label: tLabel('font_normal', 'Normal') },
                      { value: 'large', label: tLabel('font_large', 'Grand') },
                    ]"
                    color="#a21ee5"
                    size="x-small"
                    block
                  />
                </v-col>
              </v-row>
            </div>

            <!-- 3. DISPLAY SWITCHES (Client Invoice style) -->
            <div class="wh-control-section mb-4">
              <div class="d-flex align-center ga-2 text-caption font-weight-bold text-warehouse mb-2">
                <v-icon size="16" color="#a21ee5">mdi-tune-variant</v-icon>
                <span>{{ tLabel('display_options', 'Options d\'Affichage') }}</span>
              </div>

              <div class="options-container rounded-xl pa-2 border bg-surface">
                <!-- Company Header & Logo -->
                <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#a21ee5">mdi-domain</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_company_header', 'Afficher l\'En-tête & Logo') }}</span>
                  </div>
                  <v-switch v-model="showCompanyHeader" hide-details density="compact" color="#a21ee5" inset />
                </div>

                <!-- KPI Summary Strip -->
                <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#a21ee5">mdi-chart-box-outline</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_kpi_summary', 'Afficher le Résumé des KPIs') }}</span>
                  </div>
                  <v-switch v-model="showKpiSummary" hide-details density="compact" color="#a21ee5" inset />
                </div>

                <!-- Zebra Striping -->
                <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#a21ee5">mdi-view-sequential-outline</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_zebra_striping', 'Lignes Alternées Zébrées') }}</span>
                  </div>
                  <v-switch v-model="zebraStriping" hide-details density="compact" color="#a21ee5" inset />
                </div>

                <!-- Timestamp -->
                <div class="d-flex align-center justify-space-between py-1 px-2" :class="{ 'border-b': selectedView === 'analytics' }">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#a21ee5">mdi-clock-outline</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_timestamp', 'Date & Heure d\'Édition') }}</span>
                  </div>
                  <v-switch v-model="showTimestamp" hide-details density="compact" color="#a21ee5" inset />
                </div>

                <!-- If Analytics View: Charts & Metric Toggles -->
                <template v-if="selectedView === 'analytics'">
                  <!-- Show Charts & Graphics -->
                  <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                    <div class="d-flex align-center ga-2">
                      <v-icon size="16" color="#a21ee5">mdi-chart-areaspline</v-icon>
                      <span class="text-caption font-weight-medium">{{ tLabel('show_charts_graphics', 'Afficher Graphiques & Diagrammes') }}</span>
                    </div>
                    <v-switch v-model="showAnalyticsCharts" hide-details density="compact" color="#a21ee5" inset />
                  </div>

                  <!-- Chart Metric Selection (when charts enabled) -->
                  <div v-if="showAnalyticsCharts" class="py-2 px-2 border-b bg-surface-variant-subtle rounded-lg my-1">
                    <div class="text-caption text-medium-emphasis mb-1 font-weight-bold">
                      <v-icon size="13" color="#a21ee5" class="mr-1">mdi-swap-horizontal</v-icon>
                      {{ tLabel('chart_metric', 'Métrique du Graphique') }}:
                    </div>
                    <SegmentedPillToggle
                      v-model="analyticsChartMetric"
                      :items="analyticsChartMetricOptions"
                      color="#a21ee5"
                      size="x-small"
                      block
                    />
                  </div>

                  <!-- Show Detailed Table -->
                  <div class="d-flex align-center justify-space-between py-1 px-2">
                    <div class="d-flex align-center ga-2">
                      <v-icon size="16" color="#a21ee5">mdi-table</v-icon>
                      <span class="text-caption font-weight-medium">{{ tLabel('show_detailed_table', 'Afficher Tableau Détaillé') }}</span>
                    </div>
                    <v-switch v-model="showAnalyticsTable" hide-details density="compact" color="#a21ee5" inset />
                  </div>
                </template>
              </div>
            </div>

            <!-- 4. CUSTOM TITLE & NOTES (AddCharge style inputs) -->
            <div class="wh-control-section mb-3">
              <v-text-field
                v-model="customTitle"
                :label="tLabel('document_custom_title', 'Titre du Document')"
                :placeholder="defaultDocumentTitle"
                density="compact"
                variant="outlined"
                rounded="lg"
                color="#a21ee5"
                hide-details
                clearable
                prepend-inner-icon="mdi-format-title"
                class="mb-2"
              />

              <v-textarea
                v-model="customNotes"
                :label="tLabel('document_notes', 'Remarques / Observations de Bas de Page')"
                density="compact"
                variant="outlined"
                rounded="lg"
                color="#a21ee5"
                rows="2"
                hide-details
                clearable
                prepend-inner-icon="mdi-note-text-outline"
              />
            </div>
          </div>

          <!-- Bottom Pinned Action Strip -->
          <div class="pa-3 border-t bg-surface flex-shrink-0 d-flex align-center justify-space-between ga-2">
            <v-btn
              variant="outlined"
              color="grey"
              rounded="lg"
              size="small"
              class="font-weight-bold"
              @click="close"
            >
              {{ tLabel('close', 'Fermer') }}
            </v-btn>

            <div class="d-flex align-center ga-2">
              <v-btn
                variant="tonal"
                color="#a21ee5"
                rounded="lg"
                size="small"
                prepend-icon="mdi-file-excel"
                class="font-weight-bold"
                @click="exportExcelPreview"
              >
                Excel
              </v-btn>

              <v-btn
                v-print="printConfig"
                variant="flat"
                color="#a21ee5"
                rounded="lg"
                size="small"
                prepend-icon="mdi-printer"
                class="font-weight-bold text-white text-uppercase"
                @click="onPrintTriggered"
              >
                {{ tLabel('print_now', 'Imprimer') }}
              </v-btn>
            </div>
          </div>
        </div>

        <!-- ===================================================================== -->
        <!-- RIGHT PANE: LIVE INTERACTIVE PRINT PREVIEW                             -->
        <!-- ===================================================================== -->
        <div class="wh-print-preview-pane flex-grow-1 pa-4 pa-md-6 overflow-y-auto">
          <div class="d-flex align-center justify-space-between mb-3">
            <div class="d-flex align-center ga-2">
              <v-icon size="18" color="#a21ee5">mdi-eye-check-outline</v-icon>
              <span class="text-subtitle-2 font-weight-bold text-warehouse">
                {{ tLabel('print_preview', 'Aperçu Avant Impression') }}
              </span>
              <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold text-uppercase">
                {{ orientation }} &bull; {{ fontSize }}
              </v-chip>
            </div>
            <span class="text-caption text-medium-emphasis">
              {{ activeColumnKeys.length }} / {{ availableColumns.length }} {{ tLabel('selected_columns_count', 'colonnes actives') }}
            </span>
          </div>

          <!-- THE PHYSICAL PRINT SHEET CONTAINER -->
          <div
            id="warehouse-printable-document"
            :class="['wh-print-sheet', 'wh-orientation-' + orientation, 'wh-font-' + fontSize]"
          >
            <!-- 1. Header with Company Logo -->
            <div v-if="showCompanyHeader" class="wh-sheet-header pb-3 mb-3 border-b">
              <div class="d-flex align-center justify-space-between">
                <!-- Left: Logo & Company -->
                <div class="d-flex align-center ga-3">
                  <img
                    v-if="companyLogoUrl"
                    :src="companyLogoUrl"
                    alt="Company Logo"
                    class="wh-sheet-logo"
                    @error="onLogoError"
                  />
                  <div>
                    <div class="wh-sheet-company-name">{{ companyName }}</div>
                    <div class="wh-sheet-subtext">{{ companyAddress || tLabel('industrial_unit_cold_storage', 'Unité Industrielle & Entrepôt Frigorifique') }}</div>
                    <div v-if="companyIce" class="wh-sheet-subtext"><b>ICE:</b> {{ companyIce }} &bull; <b>{{ tLabel('phone', 'Tél') }}:</b> {{ companyPhone || '-' }}</div>
                  </div>
                </div>

                <!-- Right: Document Meta Stamp -->
                <div class="text-right">
                  <div class="wh-sheet-doc-badge">{{ tLabel('official_warehouse_report', 'Rapport Officiel de Stock d\'Usine') }}</div>
                  <div class="wh-sheet-date mt-1">
                    <b>{{ tLabel('date', 'Date') }}:</b> {{ currentDateFormatted }}
                  </div>
                  <div v-if="showTimestamp" class="wh-sheet-subtext">
                    {{ tLabel('generated_by', 'Édité par') }}: {{ authUser?.name || tLabel('administrator', 'Administrateur') }} ({{ currentTimeFormatted }})
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. Document Title Banner -->
            <div class="wh-sheet-title-banner pa-2.5 px-3 mb-3 rounded-lg text-white d-flex align-center justify-space-between">
              <div>
                <h1 class="wh-sheet-h1 mb-0">{{ customTitle || defaultDocumentTitle }}</h1>
                <div class="wh-sheet-desc" style="opacity: 0.9;">
                  {{ currentViewSubtitle }}
                </div>
              </div>
              <div class="text-right d-none d-sm-block">
                <span class="wh-sheet-chip">
                  <v-progress-circular v-if="loadingData" size="12" width="2" indeterminate color="white" class="mr-1" />
                  {{ printableRowsCount }} {{ tLabel('records', 'Enregistrements') }}
                </span>
              </div>
            </div>

            <!-- 3. KPI Summary Strip (if enabled) -->
            <div v-if="showKpiSummary" class="wh-sheet-kpis-strip d-flex align-center ga-2 mb-3">
              <div class="wh-sheet-kpi-pill">
                <span class="wh-kpi-lbl">{{ kpiBadge1Label }}</span>
                <span class="wh-kpi-val">{{ kpiBadge1Value }}</span>
              </div>
              <div class="wh-sheet-kpi-pill">
                <span class="wh-kpi-lbl">{{ tLabel('remaining_weight_label', 'Poids Total Stock') }}</span>
                <span class="wh-kpi-val text-teal">{{ main.formatDisplayNumber(totalRemainingWeightPreview) }} Kg</span>
              </div>
              <div class="wh-sheet-kpi-pill">
                <span class="wh-kpi-lbl">{{ tLabel('caisses', 'Colis / Cartons') }}</span>
                <span class="wh-kpi-val">{{ main.formatDisplayNumber(totalBoxesPreview) }}</span>
              </div>
              <div class="wh-sheet-kpi-pill">
                <span class="wh-kpi-lbl">{{ tLabel('total_stock_value', 'Valorisation Stock') }}</span>
                <span class="wh-kpi-val" style="color: #FF9800;">{{ main.formatDisplayNumber(totalValuationPreview) }} {{ main.currency }}</span>
              </div>
            </div>

            <!-- ANALYTICS CHARTS & GRAPHICS SECTION (Printed when Analytics view & enabled) -->
            <div
              v-if="selectedView === 'analytics' && showAnalyticsCharts && (activeAnalyticsData?.timeline?.length || activeAnalyticsData?.species?.length || activeAnalyticsData?.summary)"
              class="wh-print-charts-section mb-3"
            >
              <!-- 1. Range KPI Strip (4 Cards matching Real Analytics) -->
              <div class="wh-analytics-kpi-grid mb-3">
                <!-- Inflows Card -->
                <div class="wh-analytics-kpi-card in-card">
                  <div class="d-flex align-center justify-space-between mb-0.5">
                    <span class="wh-akpi-lbl">{{ tLabel('total_inflows', 'Total Entrées (IN)') }}</span>
                    <span class="wh-akpi-tag in-tag">IN</span>
                  </div>
                  <div class="wh-akpi-val in-val">
                    +{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.in_weight || 0) }} Kg
                  </div>
                  <div class="wh-akpi-sub">
                    <span>{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.in_boxes || 0) }} {{ tLabel('boxes', 'colis') }}</span>
                    <span>&bull;</span>
                    <span>{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.in_valuation || 0) }} {{ main.currency }}</span>
                  </div>
                </div>

                <!-- Outflows Card -->
                <div class="wh-analytics-kpi-card out-card">
                  <div class="d-flex align-center justify-space-between mb-0.5">
                    <span class="wh-akpi-lbl">{{ tLabel('total_outflows', 'Total Sorties (OUT)') }}</span>
                    <span class="wh-akpi-tag out-tag">OUT</span>
                  </div>
                  <div class="wh-akpi-val out-val">
                    -{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.out_weight || 0) }} Kg
                  </div>
                  <div class="wh-akpi-sub">
                    <span>{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.out_boxes || 0) }} {{ tLabel('boxes', 'colis') }}</span>
                    <span>&bull;</span>
                    <span>{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.out_valuation || 0) }} {{ main.currency }}</span>
                  </div>
                </div>

                <!-- Net Period Balance Card -->
                <div class="wh-analytics-kpi-card net-card">
                  <div class="d-flex align-center justify-space-between mb-0.5">
                    <span class="wh-akpi-lbl">{{ tLabel('net_period_balance', 'Solde Net Période') }}</span>
                    <span class="wh-akpi-tag net-tag">&Delta;</span>
                  </div>
                  <div class="wh-akpi-val net-val" :style="{ color: (activeAnalyticsData?.summary?.net_weight || 0) >= 0 ? '#00897B' : '#D32F2F' }">
                    {{ (activeAnalyticsData?.summary?.net_weight || 0) >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.net_weight || 0) }} Kg
                  </div>
                  <div class="wh-akpi-sub">
                    <span>{{ (activeAnalyticsData?.summary?.net_boxes || 0) >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.net_boxes || 0) }} {{ tLabel('boxes', 'colis') }}</span>
                    <span>&bull;</span>
                    <span>{{ (activeAnalyticsData?.summary?.net_valuation || 0) >= 0 ? '+' : '' }}{{ main.formatDisplayNumber(activeAnalyticsData?.summary?.net_valuation || 0) }} {{ main.currency }}</span>
                  </div>
                </div>

                <!-- Activity Card -->
                <div class="wh-analytics-kpi-card act-card">
                  <div class="d-flex align-center justify-space-between mb-0.5">
                    <span class="wh-akpi-lbl">{{ tLabel('activity', 'Activité Période') }}</span>
                    <span class="wh-akpi-tag act-tag">#</span>
                  </div>
                  <div class="wh-akpi-val act-val">
                    {{ activeAnalyticsData?.summary?.movements_count || 0 }} {{ tLabel('movements', 'mouvements') }}
                  </div>
                  <div class="wh-akpi-sub">
                    <b>{{ activeAnalyticsData?.summary?.active_lots_count || 0 }}</b> {{ tLabel('active_lots', 'N° Suivi actifs') }}
                  </div>
                </div>
              </div>

              <!-- 2. Primary Timeline Area Chart -->
              <div v-if="activeAnalyticsData?.timeline?.length" class="wh-print-chart-box mb-3">
                <div class="wh-chart-header d-flex align-center justify-space-between mb-1 pb-1 border-b">
                  <div class="d-flex align-center ga-2 font-weight-bold" style="font-size: 11px; color: #a21ee5;">
                    <v-icon size="14" color="#a21ee5">mdi-chart-areaspline</v-icon>
                    <span>{{ tLabel('stock_flow_evolution', 'Évolution Temporelle des Flux de Stock') }}</span>
                  </div>
                  <span class="text-caption text-grey font-weight-medium" style="font-size: 9.5px;">
                    {{ activeAnalyticsData.timeline.length }} {{ tLabel('days', 'jours enregistrés') }}
                  </span>
                </div>
                <div class="wh-chart-canvas-wrap">
                  <apexchart
                    type="area"
                    height="210"
                    :options="analyticsTimelinePrintOptions"
                    :series="analyticsTimelinePrintSeries"
                  />
                </div>
              </div>

              <!-- 3. Side-by-side Secondary Charts (Species Donut + Chambers Bar) -->
              <div class="wh-print-charts-row d-flex ga-3">
                <!-- Species Donut -->
                <div class="wh-print-chart-box flex-grow-1 flex-basis-0" style="flex: 1 1 50%; min-width: 0;">
                  <div class="wh-chart-header d-flex align-center justify-space-between mb-1 pb-1 border-b">
                    <div class="d-flex align-center ga-2 font-weight-bold" style="font-size: 11px; color: #00897B;">
                      <v-icon size="14" color="#00897B">mdi-chart-donut</v-icon>
                      <span>{{ tLabel('species_share_period', 'Répartition par Espèce') }}</span>
                    </div>
                    <span class="text-caption text-grey font-weight-medium" style="font-size: 9.5px;">
                      {{ (activeAnalyticsData?.species || []).length }} {{ tLabel('species', 'espèces') }}
                    </span>
                  </div>
                  <div class="wh-chart-canvas-wrap">
                    <apexchart
                      v-if="analyticsSpeciesDonutPrintSeries.length"
                      type="donut"
                      height="180"
                      :options="analyticsSpeciesDonutPrintOptions"
                      :series="analyticsSpeciesDonutPrintSeries"
                    />
                    <div v-else class="text-center pa-4 text-medium-emphasis" style="font-size: 10px;">
                      {{ tLabel('no_chart_data', 'Aucune donnée') }}
                    </div>
                  </div>
                </div>

                <!-- Cold Rooms Bar -->
                <div class="wh-print-chart-box flex-grow-1 flex-basis-0" style="flex: 1 1 50%; min-width: 0;">
                  <div class="wh-chart-header d-flex align-center justify-space-between mb-1 pb-1 border-b">
                    <div class="d-flex align-center ga-2 font-weight-bold" style="font-size: 11px; color: #a21ee5;">
                      <v-icon size="14" color="#a21ee5">mdi-chart-bar</v-icon>
                      <span>{{ tLabel('chambers_activity_period', 'Activité Chambres Froides') }}</span>
                    </div>
                    <span class="text-caption text-grey font-weight-medium" style="font-size: 9.5px;">
                      {{ (activeAnalyticsData?.chambers || []).length }} {{ tLabel('warehouses', 'chambres') }}
                    </span>
                  </div>
                  <div class="wh-chart-canvas-wrap">
                    <apexchart
                      v-if="analyticsChambersBarPrintSeries.length && analyticsChambersBarPrintSeries[0].data.length"
                      type="bar"
                      height="180"
                      :options="analyticsChambersBarPrintOptions"
                      :series="analyticsChambersBarPrintSeries"
                    />
                    <div v-else class="text-center pa-4 text-medium-emphasis" style="font-size: 10px;">
                      {{ tLabel('no_chart_data', 'Aucune donnée') }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 4. THE PRINTABLE DATA TABLE (Respects Selected Columns) -->
            <div v-if="selectedView !== 'analytics' || showAnalyticsTable" class="wh-sheet-table-wrap">
              <div v-if="selectedView === 'analytics'" class="d-flex align-center ga-2 font-weight-bold mb-1.5 text-warehouse" style="font-size: 11px;">
                <v-icon size="14" color="#a21ee5">mdi-table</v-icon>
                <span>{{ tLabel('detailed_flows_table', 'Détail Journalier des Flux de Stock') }}</span>
              </div>
              <table :class="['wh-sheet-table', { 'zebra': zebraStriping }]">
                <thead>
                  <tr>
                    <th style="width: 34px;" class="text-center">#</th>
                    <th
                      v-for="col in activeColumnsList"
                      :key="col.key"
                      :class="col.align === 'right' ? 'text-right' : (col.align === 'center' ? 'text-center' : 'text-left')"
                    >
                      {{ col.label }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, rIdx) in previewDataRows" :key="rIdx">
                    <td class="text-center font-weight-medium text-grey" style="font-size: 10px;">{{ rIdx + 1 }}</td>
                    <td
                      v-for="col in activeColumnsList"
                      :key="col.key"
                      :class="col.align === 'right' ? 'text-right' : (col.align === 'center' ? 'text-center' : 'text-left')"
                    >
                      <!-- Custom Cell Formatters based on Column Key -->
                      <template v-if="col.key === 'lot_number'">
                        <span class="font-weight-bold text-warehouse">#{{ row.lot_number }}</span>
                      </template>
                      <template v-else-if="col.key === 'status'">
                        <span :class="['wh-print-tag', 'wh-tag-' + row.status]">{{ lotStatusLabel(row.status) }}</span>
                      </template>
                      <template v-else-if="col.key === 'first_date' || col.key === 'date'">
                        <span>{{ row.first_date ? main.formateDate(row.first_date) : (row.date ? main.formateDate(row.date) : '-') }}</span>
                      </template>
                      <template v-else-if="col.key === 'age_days'">
                        <span>{{ Math.round(row.age_days || 0) }} {{ tLabel('day_unit', 'j') }}</span>
                      </template>
                      <template v-else-if="col.key === 'remaining_weight' || col.key === 'initial_weight' || col.key === 'exited_weight' || col.key === 'total_weight' || col.key === 'weight' || col.key === 'in_weight' || col.key === 'out_weight' || col.key === 'net_weight' || col.key === 'cumulative_net_weight'">
                        <span class="font-weight-bold">{{ main.formatDisplayNumber(row[col.key]) }} Kg</span>
                      </template>
                      <template v-else-if="col.key === 'total_valuation' || col.key === 'valuation' || col.key === 'in_valuation' || col.key === 'out_valuation'">
                        <span class="font-weight-bold">{{ main.formatDisplayNumber(row[col.key]) }} {{ main.currency }}</span>
                      </template>
                      <template v-else-if="col.key === 'remaining_boxes' || col.key === 'total_boxes' || col.key === 'boxes' || col.key === 'in_boxes' || col.key === 'out_boxes'">
                        <span class="font-weight-medium">{{ main.formatDisplayNumber(row[col.key]) }}</span>
                      </template>
                      <template v-else-if="col.key === 'avg_unit_price' || col.key === 'wap'">
                        <span>{{ (row.avg_unit_price > 0 || row.wap > 0) ? main.formatDisplayNumber(row.avg_unit_price || row.wap) : '-' }}</span>
                      </template>
                      <template v-else-if="col.key === 'species_list'">
                        <span>{{ (row.species_list || []).join(', ') || '-' }}</span>
                      </template>
                      <template v-else-if="col.key === 'direction'">
                        <span :class="['wh-print-tag', row.direction === 'IN' ? 'wh-tag-in' : 'wh-tag-out']">{{ row.direction }}</span>
                      </template>
                      <template v-else>
                        <span>{{ (row[col.key] !== null && row[col.key] !== undefined && row[col.key] !== '') ? row[col.key] : '-' }}</span>
                      </template>
                    </td>
                  </tr>

                  <!-- Loading State in Table -->
                  <tr v-if="loadingData">
                    <td :colspan="activeColumnsList.length + 1" class="text-center pa-8">
                      <v-progress-circular indeterminate color="#a21ee5" size="36" width="3" />
                      <div class="text-caption text-medium-emphasis mt-2 font-weight-bold">
                        {{ tLabel('loading', 'Chargement des données...') }}
                      </div>
                    </td>
                  </tr>

                  <!-- Empty State in Table -->
                  <tr v-else-if="!previewDataRows.length">
                    <td :colspan="activeColumnsList.length + 1" class="text-center pa-6 text-medium-emphasis">
                      {{ tLabel('empty_view_records', 'Aucun enregistrement trouvé pour cette vue') }}
                    </td>
                  </tr>
                </tbody>

                <!-- Totals Summary Row -->
                <tfoot v-if="previewDataRows.length">
                  <tr class="wh-sheet-totals-row">
                    <td :colspan="totalsLabelColspan" class="text-right font-weight-black text-uppercase">
                      {{ tLabel('total', 'TOTAL GÉNÉRAL') }}:
                    </td>
                    <td
                      v-for="col in trailingColumnsForTotals"
                      :key="'tot_' + col.key"
                      :class="col.align === 'right' ? 'text-right font-weight-black' : (col.align === 'center' ? 'text-center font-weight-bold' : 'text-left font-weight-bold')"
                    >
                      <span>{{ getColumnTotal(col.key) }}</span>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- 5. Custom Notes & Signature / Legal Seal Area -->
            <div class="wh-sheet-footer mt-4 pt-3 border-t">
              <div v-if="customNotes" class="wh-sheet-notes mb-3">
                <b>{{ tLabel('document_notes', 'Remarques') }}:</b> {{ customNotes }}
              </div>

              <div class="d-flex align-center justify-space-between text-caption text-medium-emphasis">
                <div>
                  <span>{{ companyName }} &bull; {{ tLabel('blueprint_traceability_system', 'Système de Traçabilité BluePrintFish') }}</span>
                </div>
                <div class="d-flex align-center ga-4">
                  <span>{{ tLabel('page_count_indicator', 'Page') }} 1/1</span>
                  <span><b>{{ tLabel('visa_stock_manager', 'Visa Responsable Stock') }}:</b> ____________________</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </v-card>
  </v-dialog>
</template>

<script>
import axios from 'axios';
import print from 'vue3-print-nb';
import * as XLSX from 'xlsx';
import moment from 'moment';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import VueApexCharts from 'vue3-apexcharts';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useCompanySettings } from '@/stores/CompanySettings';

export default {
  name: 'WarehousePrintDialog',
  directives: { print },
  components: {
    SegmentedPillToggle,
    apexchart: VueApexCharts,
  },
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    initialView: {
      type: String,
      default: 'stock_par_lot',
    },
    lots: {
      type: Array,
      default: () => [],
    },
    lotsSummary: {
      type: Object,
      default: () => ({}),
    },
    chambersList: {
      type: Array,
      default: () => [],
    },
    speciesList: {
      type: Array,
      default: () => [],
    },
    journalList: {
      type: Array,
      default: () => [],
    },
    journalTotals: {
      type: Object,
      default: () => ({}),
    },
    analyticsData: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: ['update:modelValue', 'printed', 'view-change', 'data-loaded'],
  data() {
    return {
      selectedView: 'stock_par_lot',
      loadingData: false,
      internalLots: [],
      internalLotsSummary: null,
      internalChambers: [],
      internalSpecies: [],
      internalJournal: [],
      internalJournalTotals: null,
      internalAnalytics: null,
      activeColumnKeys: [],
      orientation: 'landscape',
      fontSize: 'normal',
      showCompanyHeader: true,
      showKpiSummary: true,
      showAnalyticsCharts: true,
      showAnalyticsTable: true,
      analyticsChartMetric: 'flows',
      zebraStriping: true,
      showTimestamp: true,
      customTitle: '',
      customNotes: '',
      printConfig: {
        id: 'warehouse-printable-document',
        popTitle: 'BluePrintFish - Rapport Entrepôts & Stock',
      },
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    authUser() { return this.auth.user; },

    internalOpen: {
      get() { return this.modelValue; },
      set(val) { this.$emit('update:modelValue', val); },
    },

    companySettingsStore() {
      return useCompanySettings();
    },
    companySettings() {
      return this.companySettingsStore.settings || {};
    },
    companyName() {
      return this.companySettings.company_name || this.companySettingsStore.companyName || 'Congelation Villamar';
    },
    companyAddress() {
      return this.companySettings.address || this.companySettings.company_address || '';
    },
    companyIce() {
      return this.companySettings.ice || '';
    },
    companyPhone() {
      return this.companySettings.phone || this.companySettings.phone_number || '';
    },
    companyLogoUrl() {
      const cs = this.companySettings;
      if (cs.logo_invoice_url) return cs.logo_invoice_url;
      if (cs.logo_invoice_path) return `/storage/${cs.logo_invoice_path}`;
      if (cs.logo_invoice_body_url) return cs.logo_invoice_body_url;
      if (cs.logo_invoice_body_path) return `/storage/${cs.logo_invoice_body_path}`;
      if (cs.invoice_header_image_url) return cs.invoice_header_image_url;
      if (cs.invoice_header_image_path) return `/storage/${cs.invoice_header_image_path}`;
      if (cs.logo_url) return cs.logo_url;
      if (cs.logo) return `/storage/${cs.logo}`;
      return '/storage/images/logo_login.png';
    },

    currentLocale() {
      return this.$i18n?.locale || this.main?.lang?.code || localStorage.getItem('language') || 'en';
    },

    currentDateFormatted() {
      const loc = this.currentLocale;
      try { moment.locale(loc); } catch (e) {}
      return moment().format('L') || moment().format('DD/MM/YYYY');
    },
    currentTimeFormatted() {
      const loc = this.currentLocale;
      try { moment.locale(loc); } catch (e) {}
      return moment().format('LT') || moment().format('HH:mm');
    },

    viewOptions() {
      return [
        { value: 'stock_par_lot', label: this.tLabel('view_stock_par_lot', 'N° Suivi Réception'), icon: 'mdi-cube-outline' },
        { value: 'chambers', label: this.tLabel('view_chambers_overview', 'Chambres'), icon: 'mdi-snowflake' },
        { value: 'species', label: this.tLabel('view_species_overview', 'Espèces'), icon: 'mdi-fish' },
        { value: 'journal', label: this.tLabel('view_movements_journal', 'Journal'), icon: 'mdi-book-clock-outline' },
        { value: 'analytics', label: this.tLabel('view_stock_analytics', 'Analytique'), icon: 'mdi-chart-areaspline' },
      ];
    },

    currentViewLabel() {
      const opt = this.viewOptions.find(o => o.value === this.selectedView);
      return opt ? opt.label : this.selectedView;
    },

    allColumnsRegistry() {
      return {
        stock_par_lot: [
          { key: 'lot_number', label: this.tLabel('lot_number', 'N° Suivi Réception'), align: 'left', default: true },
          { key: 'status', label: this.tLabel('status', 'Statut'), align: 'center', default: true },
          { key: 'first_date', label: this.tLabel('date', 'Date Réception'), align: 'center', default: true },
          { key: 'age_days', label: this.tLabel('age_days', 'Âge'), align: 'center', default: true },
          { key: 'supplier_name', label: this.tLabel('suppliers', 'Fournisseur'), align: 'left', default: true },
          { key: 'primary_warehouse', label: this.tLabel('chamber_location', 'Chambre'), align: 'center', default: true },
          { key: 'species_list', label: this.tLabel('contained_species', 'Espèces'), align: 'left', default: true },
          { key: 'initial_weight', label: this.tLabel('initial_weight_label', 'Poids Init. (Kg)'), align: 'right', default: true },
          { key: 'remaining_weight', label: this.tLabel('remaining_weight_label', 'Solde Stock (Kg)'), align: 'right', default: true },
          { key: 'remaining_boxes', label: this.tLabel('package_count', 'Cartons'), align: 'center', default: true },
          { key: 'avg_unit_price', label: this.tLabel('wap', 'PMP (DH/Kg)'), align: 'right', default: true },
          { key: 'total_valuation', label: this.tLabel('total_price', 'Valeur (DH)'), align: 'right', default: true },
          { key: 'articles_count', label: this.tLabel('articles', 'Nbr Articles'), align: 'center', default: false },
        ],
        chambers: [
          { key: 'name', label: this.tLabel('warehouse', 'Chambre Froide'), align: 'left', default: true },
          { key: 'category_name', label: this.tLabel('type', 'Catégorie'), align: 'center', default: true },
          { key: 'active_lots', label: this.tLabel('active_lots', 'N° Suivi Actifs'), align: 'center', default: true },
          { key: 'total_weight', label: this.tLabel('remaining_weight_label', 'Poids Stock (Kg)'), align: 'right', default: true },
          { key: 'total_boxes', label: this.tLabel('caisses', 'Colis / Cartons'), align: 'center', default: true },
          { key: 'valuation', label: this.tLabel('total_price', 'Valorisation (DH)'), align: 'right', default: true },
        ],
        species: [
          { key: 'article_name', label: this.tLabel('article_type_name', 'Espèce / Article'), align: 'left', default: true },
          { key: 'family_name', label: this.tLabel('fish_family', 'Famille'), align: 'center', default: true },
          { key: 'process_name', label: this.tLabel('process', 'Traitement'), align: 'center', default: true },
          { key: 'total_weight', label: this.tLabel('remaining_weight_label', 'Poids Stock (Kg)'), align: 'right', default: true },
          { key: 'total_boxes', label: this.tLabel('package_count', 'Cartons'), align: 'center', default: true },
          { key: 'wap', label: this.tLabel('wap', 'PMP (DH/Kg)'), align: 'right', default: true },
          { key: 'total_valuation', label: this.tLabel('total_price', 'Valorisation (DH)'), align: 'right', default: true },
          { key: 'lots_count', label: this.tLabel('active_lots', 'Nbr Lots'), align: 'center', default: true },
        ],
        journal: [
          { key: 'date', label: this.tLabel('date', 'Date'), align: 'center', default: true },
          { key: 'direction', label: this.tLabel('movement_direction', 'Sens (IN/OUT)'), align: 'center', default: true },
          { key: 'lot_number', label: this.tLabel('lot_number', 'N° Suivi Réception'), align: 'center', default: true },
          { key: 'article_name', label: this.tLabel('article_type_name', 'Article'), align: 'left', default: true },
          { key: 'warehouse_name', label: this.tLabel('warehouse', 'Chambre'), align: 'center', default: true },
          { key: 'partner_name', label: this.tLabel('partner', 'Partenaire'), align: 'left', default: true },
          { key: 'weight', label: this.tLabel('weight', 'Poids (Kg)'), align: 'right', default: true },
          { key: 'boxes', label: this.tLabel('package_count', 'Cartons'), align: 'center', default: true },
        ],
        analytics: [
          { key: 'date', label: this.tLabel('date', 'Date'), align: 'center', default: true },
          { key: 'in_weight', label: this.tLabel('total_in', 'Entrées IN (Kg)'), align: 'right', default: true },
          { key: 'out_weight', label: this.tLabel('total_out', 'Sorties OUT (Kg)'), align: 'right', default: true },
          { key: 'net_weight', label: this.tLabel('net_balance', 'Solde Net (Kg)'), align: 'right', default: true },
          { key: 'cumulative_net_weight', label: this.tLabel('cumulative_balance', 'Cumul Net (Kg)'), align: 'right', default: true },
          { key: 'in_valuation', label: this.tLabel('financial_inflow', 'Valeur IN'), align: 'right', default: true },
          { key: 'out_valuation', label: this.tLabel('financial_outflow', 'Valeur OUT'), align: 'right', default: true },
        ],
      };
    },

    availableColumns() {
      return this.allColumnsRegistry[this.selectedView] || [];
    },

    activeColumnsList() {
      return this.availableColumns.filter(c => this.activeColumnKeys.includes(c.key));
    },

    activeAnalyticsData() {
      if (this.analyticsData && (this.analyticsData.timeline?.length || this.analyticsData.summary?.in_weight !== undefined)) {
        return this.analyticsData;
      }
      return this.internalAnalytics || {};
    },

    analyticsChartMetricOptions() {
      return [
        { value: 'flows', label: this.tLabel('movement_in_out', 'Flux (IN/OUT)') },
        { value: 'cumulative', label: this.tLabel('cumulative_balance', 'Cumul (Kg)') },
        { value: 'valuation', label: this.tLabel('valuation', 'Valeur (DH)') },
      ];
    },

    analyticsTimelinePrintSeries() {
      const tl = this.activeAnalyticsData?.timeline || [];
      if (this.analyticsChartMetric === 'cumulative') {
        return [{
          name: this.tLabel('cumulative_balance', 'Cumul Solde Net (Kg)'),
          data: tl.map(t => Math.round(t.cumulative_net_weight || 0)),
        }];
      }
      if (this.analyticsChartMetric === 'valuation') {
        return [
          { name: this.tLabel('financial_inflow', 'Valeur Entrées (DH)'), data: tl.map(t => Math.round(t.in_valuation || 0)) },
          { name: this.tLabel('financial_outflow', 'Valeur Sorties (DH)'), data: tl.map(t => Math.round(t.out_valuation || 0)) },
        ];
      }
      return [
        { name: this.tLabel('movement_in', 'Entrées (IN) Kg'), data: tl.map(t => Math.round(t.in_weight || 0)) },
        { name: this.tLabel('movement_out', 'Sorties (OUT) Kg'), data: tl.map(t => Math.round(t.out_weight || 0)) },
        { name: this.tLabel('net_balance', 'Solde Net Kg'), data: tl.map(t => Math.round(t.net_weight || 0)) },
      ];
    },

    analyticsTimelinePrintOptions() {
      const tl = this.activeAnalyticsData?.timeline || [];
      const categories = tl.map(t => this.main.formateDate(t.date));
      let colors = ['#00897B', '#D32F2F', '#a21ee5'];
      if (this.analyticsChartMetric === 'cumulative') {
        colors = ['#a21ee5'];
      } else if (this.analyticsChartMetric === 'valuation') {
        colors = ['#00897B', '#E65100'];
      }
      return {
        chart: {
          type: 'area',
          background: '#ffffff',
          toolbar: { show: false },
          animations: { enabled: false },
        },
        colors,
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.05,
            stops: [0, 95, 100],
          },
        },
        xaxis: {
          categories,
          labels: {
            rotate: -45,
            rotateAlways: tl.length > 8,
            style: { fontSize: '9px', fontWeight: 600, colors: '#555' },
          },
        },
        yaxis: {
          labels: {
            style: { fontSize: '9px', colors: '#555' },
            formatter: v => this.main.formatDisplayNumber(v),
          },
        },
        legend: {
          position: 'top',
          horizontalAlign: 'right',
          fontSize: '10px',
          fontWeight: 700,
        },
        grid: {
          borderColor: '#eee',
          strokeDashArray: 3,
        },
        theme: { mode: 'light' },
      };
    },

    analyticsSpeciesDonutPrintSeries() {
      const list = this.activeAnalyticsData?.species || [];
      return list.slice(0, 8).map(s => Math.round(s.total_flow || 0));
    },

    analyticsSpeciesDonutPrintOptions() {
      const list = this.activeAnalyticsData?.species || [];
      return {
        chart: {
          type: 'donut',
          background: '#ffffff',
          toolbar: { show: false },
          animations: { enabled: false },
        },
        labels: list.slice(0, 8).map(s => s.article_name || 'Article'),
        colors: ['#a21ee5', '#00897B', '#1E88E5', '#FB8C00', '#6D4C41', '#E91E63', '#00ACC1', '#43A047'],
        dataLabels: {
          enabled: true,
          formatter: (val) => `${Math.round(val)}%`,
          style: { fontSize: '9px', fontWeight: 700 },
          dropShadow: { enabled: false },
        },
        legend: {
          position: 'bottom',
          fontSize: '9.5px',
          itemMargin: { horizontal: 4, vertical: 2 },
        },
        plotOptions: {
          pie: {
            donut: {
              size: '65%',
              labels: {
                show: true,
                name: {
                  show: true,
                  fontSize: '10px',
                  fontWeight: 600,
                  offsetY: -3,
                },
                value: {
                  show: true,
                  fontSize: '13px',
                  fontWeight: 800,
                  offsetY: 3,
                  formatter: (val) => `${this.main?.formatDisplayNumber ? this.main.formatDisplayNumber(val) : val} Kg`,
                },
                total: {
                  show: true,
                  label: this.tLabel('total_flow', 'Total Flux'),
                  fontSize: '10px',
                  fontWeight: 700,
                  formatter: (w) => {
                    if (w && w.globals && w.globals.seriesTotals && w.globals.seriesTotals.length) {
                      const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                      return `${this.main?.formatDisplayNumber ? this.main.formatDisplayNumber(sum) : sum} Kg`;
                    }
                    const total = list.slice(0, 8).reduce((acc, s) => acc + (parseFloat(s.total_flow) || 0), 0);
                    return `${this.main?.formatDisplayNumber ? this.main.formatDisplayNumber(total) : total} Kg`;
                  },
                },
              },
            },
          },
        },
        theme: { mode: 'light' },
      };
    },

    analyticsChambersBarPrintSeries() {
      const list = (this.activeAnalyticsData?.chambers || []).filter(c => (c.in_weight + c.out_weight) > 0).slice(0, 8);
      return [
        { name: this.tLabel('movement_in', 'Entrées (Kg)'), data: list.map(c => Math.round(c.in_weight || 0)) },
        { name: this.tLabel('movement_out', 'Sorties (Kg)'), data: list.map(c => Math.round(c.out_weight || 0)) },
      ];
    },

    analyticsChambersBarPrintOptions() {
      const list = (this.activeAnalyticsData?.chambers || []).filter(c => (c.in_weight + c.out_weight) > 0).slice(0, 8);
      return {
        chart: {
          type: 'bar',
          background: '#ffffff',
          toolbar: { show: false },
          animations: { enabled: false },
        },
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 3,
            barHeight: '65%',
          },
        },
        colors: ['#00897B', '#D32F2F'],
        xaxis: {
          categories: list.map(c => c.name),
          labels: {
            style: { fontSize: '9px', colors: '#555' },
            formatter: v => this.main.formatDisplayNumber(v) + ' Kg',
          },
        },
        yaxis: {
          labels: {
            style: { fontSize: '9.5px', fontWeight: 600, colors: '#333' },
          },
        },
        dataLabels: { enabled: false },
        legend: {
          position: 'top',
          horizontalAlign: 'right',
          fontSize: '10px',
          fontWeight: 700,
        },
        grid: {
          borderColor: '#eee',
          strokeDashArray: 3,
        },
        theme: { mode: 'light' },
      };
    },

    previewDataRows() {
      if (this.selectedView === 'stock_par_lot') {
        if (this.lots && this.lots.length) return this.lots;
        return this.internalLots || [];
      }
      if (this.selectedView === 'chambers') {
        if (this.chambersList && this.chambersList.length) return this.chambersList;
        return this.internalChambers || [];
      }
      if (this.selectedView === 'species') {
        if (this.speciesList && this.speciesList.length) return this.speciesList;
        return this.internalSpecies || [];
      }
      if (this.selectedView === 'journal') {
        if (this.journalList && this.journalList.length) return this.journalList;
        return this.internalJournal || [];
      }
      if (this.selectedView === 'analytics') {
        return this.activeAnalyticsData?.timeline || [];
      }
      return [];
    },

    kpiBadge1Label() {
      if (this.selectedView === 'stock_par_lot') return this.tLabel('active_lots', 'N° Suivi Actifs');
      if (this.selectedView === 'chambers') return this.tLabel('warehouses', 'Chambres Froides');
      if (this.selectedView === 'species') return this.tLabel('species', 'Espèces');
      if (this.selectedView === 'journal') return this.tLabel('movements', 'Mouvements');
      return this.tLabel('records', 'Périodes');
    },

    kpiBadge1Value() {
      if (this.selectedView === 'stock_par_lot') {
        return (this.internalLotsSummary?.in_stock_lots ?? this.lotsSummary.in_stock_lots) || this.previewDataRows.length;
      }
      return this.previewDataRows.length;
    },

    printableRowsCount() {
      return this.previewDataRows.length;
    },

    defaultDocumentTitle() {
      const _loc = this.currentLocale;
      if (this.selectedView === 'stock_par_lot') return this.tLabel('report_title_stock_par_lot', 'Rapport de Stock Usine par N° de Suivi de Réception');
      if (this.selectedView === 'chambers') return this.tLabel('report_title_chambers', 'État d\'Occupation des Chambres Frigorifiques');
      if (this.selectedView === 'species') return this.tLabel('report_title_species', 'Catalogue des Stocks par Espèce & Article');
      if (this.selectedView === 'journal') return this.tLabel('report_title_journal', 'Journal Chronologique des Mouvements de Stock');
      if (this.selectedView === 'analytics') return this.tLabel('report_title_analytics', 'Analytique & Évolution des Flux de Marchandises');
      return this.tLabel('report_title_default', 'Rapport des Stocks d\'Entrepôt');
    },

    currentViewSubtitle() {
      const _loc = this.currentLocale;
      if (this.selectedView === 'stock_par_lot') return this.tLabel('report_subtitle_stock_par_lot', 'Traçabilité complète par lot de réception, fournisseur, chambre et solde disponible');
      if (this.selectedView === 'chambers') return this.tLabel('report_subtitle_chambers', 'Capacités, poids stockés, emballages et valorisation par chambre froide');
      if (this.selectedView === 'species') return this.tLabel('report_subtitle_species', 'Inventaire quantitatif et valorisation financière par article');
      if (this.selectedView === 'journal') return this.tLabel('report_subtitle_journal', 'Historique continu des entrées et sorties de marchandise');
      if (this.selectedView === 'analytics') return this.tLabel('report_subtitle_analytics', 'Synthèse analytique des flux entrants et sortants');
      return this.tLabel('report_subtitle_default', 'Synthèse globale des stocks d\'usine');
    },

    // Totals calculations
    totalRemainingWeightPreview() {
      const rows = this.previewDataRows;
      if (this.selectedView === 'stock_par_lot') return rows.reduce((s, l) => s + (parseFloat(l.remaining_weight) || 0), 0);
      if (this.selectedView === 'chambers') return rows.reduce((s, c) => s + (parseFloat(c.total_weight) || 0), 0);
      if (this.selectedView === 'species') return rows.reduce((s, sp) => s + (parseFloat(sp.total_weight) || 0), 0);
      if (this.selectedView === 'journal') return (this.internalJournalTotals?.net_weight ?? this.journalTotals.net_weight) || 0;
      if (this.selectedView === 'analytics') return rows.reduce((s, a) => s + (parseFloat(a.net_weight) || 0), 0);
      return (this.internalLotsSummary?.total_remaining_weight ?? this.lotsSummary.total_remaining_weight) || 0;
    },

    totalInitialWeightPreview() {
      const rows = this.previewDataRows;
      if (this.selectedView === 'stock_par_lot') return rows.reduce((s, l) => s + (parseFloat(l.initial_weight) || 0), 0);
      return 0;
    },

    totalBoxesPreview() {
      const rows = this.previewDataRows;
      if (this.selectedView === 'stock_par_lot') return rows.reduce((s, l) => s + (parseFloat(l.remaining_boxes) || 0), 0);
      if (this.selectedView === 'chambers') return rows.reduce((s, c) => s + (parseFloat(c.total_boxes) || 0), 0);
      if (this.selectedView === 'species') return rows.reduce((s, sp) => s + (parseFloat(sp.total_boxes) || 0), 0);
      if (this.selectedView === 'journal') return (this.internalJournalTotals?.total_in_boxes ?? this.journalTotals.total_in_boxes) || 0;
      if (this.selectedView === 'analytics') return rows.reduce((s, a) => s + (parseFloat(a.in_boxes || 0) - parseFloat(a.out_boxes || 0)), 0);
      return (this.internalLotsSummary?.total_initial_boxes ?? this.lotsSummary.total_initial_boxes) || 0;
    },

    totalValuationPreview() {
      const rows = this.previewDataRows;
      if (this.selectedView === 'stock_par_lot') return rows.reduce((s, l) => s + (parseFloat(l.total_valuation) || 0), 0);
      if (this.selectedView === 'chambers') return rows.reduce((s, c) => s + (parseFloat(c.valuation) || 0), 0);
      if (this.selectedView === 'species') return rows.reduce((s, sp) => s + (parseFloat(sp.total_valuation) || 0), 0);
      if (this.selectedView === 'analytics') return rows.reduce((s, a) => s + (parseFloat(a.in_valuation || 0) - parseFloat(a.out_valuation || 0)), 0);
      return (this.internalLotsSummary?.total_valuation ?? this.lotsSummary.total_valuation) || 0;
    },

    // Table Footers Colspan math (Robust across all views)
    firstNumericColIndex() {
      return this.activeColumnsList.findIndex(c => this.hasColumnTotal(c.key));
    },

    totalsLabelColspan() {
      const idx = this.firstNumericColIndex;
      if (idx >= 0) return idx + 1; // +1 for row number column
      return this.activeColumnsList.length + 1;
    },

    trailingColumnsForTotals() {
      const idx = this.firstNumericColIndex;
      if (idx >= 0) return this.activeColumnsList.slice(idx);
      return [];
    },
  },
  watch: {
    currentLocale(newLoc) {
      if (newLoc) {
        try { moment.locale(newLoc); } catch (e) {}
        if (this.printConfig) {
          this.printConfig.popTitle = this.tLabel('print_pop_title', 'BluePrintFish - Rapport Entrepôts & Stock');
        }
      }
    },
    modelValue(val) {
      if (val) {
        this.selectedView = this.initialView || 'stock_par_lot';
        this.initColumnsForView();
        try { moment.locale(this.currentLocale); } catch (e) {}
        if (this.printConfig) {
          this.printConfig.popTitle = this.tLabel('print_pop_title', 'BluePrintFish - Rapport Entrepôts & Stock');
        }
        this.fetchViewData(this.selectedView);
        if (!this.companySettings || !Object.keys(this.companySettings).length) {
          this.companySettingsStore.fetchSettings();
        }
      }
    },
    initialView(val) {
      if (val) {
        this.selectedView = val;
        this.initColumnsForView();
        this.fetchViewData(val);
      }
    },
  },
  mounted() {
    this.selectedView = this.initialView || 'stock_par_lot';
    this.initColumnsForView();
    try { moment.locale(this.currentLocale); } catch (e) {}
    if (this.printConfig) {
      this.printConfig.popTitle = this.tLabel('print_pop_title', 'BluePrintFish - Rapport Entrepôts & Stock');
    }
    if (this.modelValue) {
      this.fetchViewData(this.selectedView);
    }
    if (!this.companySettings || !Object.keys(this.companySettings).length) {
      this.companySettingsStore.fetchSettings();
    }
  },
  methods: {
    tLabel(key, fallback) {
      const _loc = this.currentLocale;
      if (!key) return fallback || '';
      try {
        if (this.$te && this.$te(key)) {
          return this.$t(key);
        }
      } catch (e) {}
      return fallback || key;
    },

    hasColumnTotal(key) {
      const totalableKeys = [
        'remaining_weight', 'initial_weight', 'total_weight', 'weight',
        'in_weight', 'out_weight', 'net_weight', 'cumulative_net_weight',
        'remaining_boxes', 'total_boxes', 'boxes', 'in_boxes', 'out_boxes',
        'total_valuation', 'valuation', 'in_valuation', 'out_valuation',
        'avg_unit_price', 'wap',
        'active_lots', 'lots_count', 'articles_count',
      ];
      return totalableKeys.includes(key);
    },

    getColumnTotal(key) {
      const rows = this.previewDataRows;
      if (!rows || !rows.length) return '-';

      // 1. WEIGHTS
      if (['remaining_weight', 'initial_weight', 'total_weight', 'weight', 'in_weight', 'out_weight', 'net_weight'].includes(key)) {
        const sum = rows.reduce((s, r) => s + (parseFloat(r[key]) || 0), 0);
        return `${this.main.formatDisplayNumber(sum)} Kg`;
      }

      if (key === 'cumulative_net_weight') {
        const lastRow = rows[rows.length - 1];
        const val = lastRow?.cumulative_net_weight !== undefined
          ? parseFloat(lastRow.cumulative_net_weight)
          : rows.reduce((s, r) => s + (parseFloat(r.net_weight) || 0), 0);
        return `${this.main.formatDisplayNumber(val)} Kg`;
      }

      // 2. BOXES / CARTONS
      if (['remaining_boxes', 'total_boxes', 'boxes', 'in_boxes', 'out_boxes'].includes(key)) {
        const sum = rows.reduce((s, r) => s + (parseFloat(r[key]) || 0), 0);
        return this.main.formatDisplayNumber(sum);
      }

      // 3. VALUATION
      if (['total_valuation', 'valuation', 'in_valuation', 'out_valuation'].includes(key)) {
        const sum = rows.reduce((s, r) => s + (parseFloat(r[key]) || 0), 0);
        return `${this.main.formatDisplayNumber(sum)} ${this.main.currency}`;
      }

      // 4. WEIGHTED AVERAGE PRICE (WAP / PMP)
      if (key === 'avg_unit_price' || key === 'wap') {
        if (this.selectedView === 'stock_par_lot') {
          const totalVal = rows.reduce((s, r) => s + (parseFloat(r.total_valuation) || 0), 0);
          const totalW = rows.reduce((s, r) => s + (parseFloat(r.remaining_weight) || 0), 0);
          if (totalW > 0) {
            const wap = totalVal / totalW;
            return `${this.main.formatDisplayNumber(wap)} ${this.main.currency}/Kg`;
          }
        } else if (this.selectedView === 'species') {
          const totalVal = rows.reduce((s, r) => s + (parseFloat(r.total_valuation) || 0), 0);
          const totalW = rows.reduce((s, r) => s + (parseFloat(r.total_weight) || 0), 0);
          if (totalW > 0) {
            const wap = totalVal / totalW;
            return `${this.main.formatDisplayNumber(wap)} ${this.main.currency}/Kg`;
          }
        }
        return '-';
      }

      // 5. COUNTS
      if (['active_lots', 'lots_count', 'articles_count'].includes(key)) {
        const sum = rows.reduce((s, r) => s + (parseFloat(r[key]) || 0), 0);
        return this.main.formatDisplayNumber(sum);
      }

      return '-';
    },

    onLogoError(event) {
      if (event?.target) {
        if (event.target.src && !event.target.src.includes('logo_login.png')) {
          event.target.src = '/storage/images/logo_login.png';
        } else {
          event.target.style.display = 'none';
        }
      }
    },

    initColumnsForView() {
      const cols = this.availableColumns;
      this.activeColumnKeys = cols.filter(c => c.default !== false).map(c => c.key);
      this.customTitle = '';
    },

    async fetchViewData(view) {
      const v = view || this.selectedView;
      const hasData = (
        (v === 'stock_par_lot' && ((this.lots && this.lots.length) || (this.internalLots && this.internalLots.length))) ||
        (v === 'chambers' && ((this.chambersList && this.chambersList.length) || (this.internalChambers && this.internalChambers.length))) ||
        (v === 'species' && ((this.speciesList && this.speciesList.length) || (this.internalSpecies && this.internalSpecies.length))) ||
        (v === 'journal' && ((this.journalList && this.journalList.length) || (this.internalJournal && this.internalJournal.length))) ||
        (v === 'analytics' && ((this.analyticsData?.timeline?.length) || (this.internalAnalytics?.timeline?.length)))
      );

      if (hasData) return;

      this.loadingData = true;
      try {
        if (v === 'chambers') {
          const res = await axios.get('/api/warehouses/chambers-overview');
          this.internalChambers = res.data.chambers || [];
          this.$emit('data-loaded', { view: 'chambers', data: this.internalChambers });
        } else if (v === 'species') {
          const res = await axios.get('/api/warehouses/species-overview');
          this.internalSpecies = res.data.species || [];
          this.$emit('data-loaded', { view: 'species', data: this.internalSpecies });
        } else if (v === 'journal') {
          const res = await axios.get('/api/warehouses/stock-movements-journal', {
            params: { no_pagination: true }
          });
          this.internalJournal = res.data.movements || [];
          this.internalJournalTotals = res.data.totals || null;
          this.$emit('data-loaded', { view: 'journal', data: this.internalJournal, totals: res.data.totals });
        } else if (v === 'analytics') {
          const res = await axios.get('/api/warehouses/analytics');
          this.internalAnalytics = res.data || {};
          this.$emit('data-loaded', { view: 'analytics', data: this.internalAnalytics });
        } else if (v === 'stock_par_lot') {
          const res = await axios.get('/api/warehouses/stock-par-lot', {
            params: { status: 'in_stock', per_page: 100 }
          });
          this.internalLots = res.data.lots || [];
          this.internalLotsSummary = res.data.summary || null;
          this.$emit('data-loaded', { view: 'stock_par_lot', data: this.internalLots, summary: res.data.summary });
        }
      } catch (err) {
        console.error(`[WarehousePrintDialog] Error fetching ${v}:`, err);
      } finally {
        this.loadingData = false;
      }
    },

    onViewChanged() {
      this.initColumnsForView();
      this.$emit('view-change', this.selectedView);
      this.fetchViewData(this.selectedView);
    },

    selectAllColumns() {
      this.activeColumnKeys = this.availableColumns.map(c => c.key);
    },

    deselectAllColumns() {
      this.activeColumnKeys = [];
    },

    toggleColumn(key) {
      if (this.activeColumnKeys.includes(key)) {
        this.activeColumnKeys = this.activeColumnKeys.filter(k => k !== key);
      } else {
        this.activeColumnKeys.push(key);
      }
    },

    lotStatusLabel(status) {
      if (status === 'in_stock') return this.tLabel('in_stock_label', 'En Stock');
      if (status === 'partially_out') return this.tLabel('partially_out', 'Partiellement Sorti');
      return this.tLabel('depleted', 'Épuisé');
    },

    onPrintTriggered() {
      this.$emit('printed', {
        view: this.selectedView,
        columns: this.activeColumnKeys,
      });
    },

    exportExcelPreview() {
      if (!this.previewDataRows.length) return;
      const headers = this.activeColumnsList.map(c => c.label);
      const rows = this.previewDataRows.map((r, rIdx) => {
        return this.activeColumnsList.map(col => {
          if (col.key === 'species_list') return (r.species_list || []).join(', ');
          return r[col.key] !== undefined ? r[col.key] : '';
        });
      });

      const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Stock_Export');
      const filename = `Stock_${this.selectedView}_${moment().format('YYYY-MM-DD_HHmm')}.xlsx`;
      XLSX.writeFile(wb, filename);
    },

    close() {
      this.internalOpen = false;
    },
  },
};
</script>

<style scoped>
/* ==========================================================================
   BLUEPRINT FISH - WAREHOUSE PRINT MODAL & LIVE PREVIEW (claude.md)
   ========================================================================== */
.wh-print-modal-card {
  height: 90vh;
  max-height: 880px;
  background: rgb(var(--v-theme-surface));
}

/* Hero Header */
.wh-print-hero {
  background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%);
}
.wh-print-hero-icon {
  background: rgba(255, 255, 255, 0.2);
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Left Pane: Controls */
.wh-print-controls-pane {
  width: 480px;
  min-width: 420px;
  max-width: 520px;
  flex-shrink: 0;
  min-height: 0;
  background: rgb(var(--v-theme-surface));
}
.wh-print-controls-scroll {
  min-height: 0;
}
.wh-quick-col-list {
  border-color: rgba(162, 30, 229, 0.15) !important;
}

/* Right Pane: Live Preview */
.wh-print-preview-pane {
  min-height: 0;
  background: rgba(0, 0, 0, 0.04);
}

/* THE PHYSICAL A4 SHEET SIMULATION */
.wh-print-sheet {
  background: #ffffff;
  color: #1a1a1a;
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
  padding: 24px 28px;
  margin: 0 auto;
  transition: max-width 0.2s ease;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}
.wh-print-sheet.wh-orientation-landscape {
  max-width: 1060px;
  min-width: 900px;
}
.wh-print-sheet.wh-orientation-portrait {
  max-width: 780px;
}

/* Font Size Scales */
.wh-print-sheet.wh-font-compact {
  font-size: 11px;
}
.wh-print-sheet.wh-font-normal {
  font-size: 12px;
}
.wh-print-sheet.wh-font-large {
  font-size: 13.5px;
}

/* Sheet Header */
.wh-sheet-logo {
  height: 48px;
  max-width: 160px;
  object-fit: contain;
}
.wh-sheet-company-name {
  font-weight: 800;
  font-size: 14px;
  color: #a21ee5;
  letter-spacing: -0.01em;
}
.wh-sheet-subtext {
  font-size: 10px;
  color: #666;
}
.wh-sheet-doc-badge {
  display: inline-block;
  background: rgba(162, 30, 229, 0.08);
  color: #a21ee5;
  font-weight: 800;
  font-size: 10px;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid rgba(162, 30, 229, 0.2);
}
.wh-sheet-date {
  font-size: 11px;
  color: #333;
}

/* Title Banner */
.wh-sheet-title-banner {
  background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%);
}
.wh-sheet-h1 {
  font-size: 15px;
  font-weight: 800;
  line-height: 1.2;
}
.wh-sheet-desc {
  font-size: 10px;
}
.wh-sheet-chip {
  background: rgba(255, 255, 255, 0.22);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 10.5px;
  font-weight: 700;
}

/* KPI Summary Strip */
.wh-sheet-kpi-pill {
  flex: 1;
  background: #fbf9fd;
  border: 1px solid #ebdcf5;
  border-radius: 6px;
  padding: 6px 10px;
  text-align: center;
}
.wh-kpi-lbl {
  display: block;
  font-size: 9.5px;
  font-weight: 700;
  text-transform: uppercase;
  color: #777;
}
.wh-kpi-val {
  font-size: 13px;
  font-weight: 800;
  color: #222;
}

/* Printable Table */
.wh-sheet-table-wrap {
  width: 100%;
  overflow-x: auto;
}
.wh-sheet-table {
  width: 100%;
  border-collapse: collapse;
  font-size: inherit;
}
.wh-sheet-table th {
  background: #7b1fa2;
  color: #ffffff;
  padding: 7px 9px;
  font-size: 10.5px;
  font-weight: 800;
  text-transform: uppercase;
  border: 1px solid #6a1b9a;
  white-space: nowrap;
}
.wh-sheet-table td {
  padding: 6px 8px;
  border: 1px solid #e0e0e0;
  vertical-align: middle;
}
.wh-sheet-table.zebra tbody tr:nth-child(even) {
  background: #fbf8fd;
}
.wh-sheet-totals-row td {
  background: #f3e5f5;
  border-top: 2px solid #7b1fa2;
  padding: 8px 9px;
  color: #4a148c;
}

/* Print Tags */
.wh-print-tag {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 800;
  font-size: 9px;
  text-transform: uppercase;
}
.wh-tag-in_stock { background: #e0f2f1; color: #00796b; }
.wh-tag-partially_out { background: #fff3e0; color: #e65100; }
.wh-tag-depleted { background: #eeeeee; color: #616161; }
.wh-tag-in { background: #e0f2f1; color: #00796b; }
.wh-tag-out { background: #ffebee; color: #c62828; }

/* Print Footer */
.wh-sheet-notes {
  font-size: 10.5px;
  color: #444;
}

/* Analytics Print Charts & KPIs */
.wh-print-charts-section {
  width: 100%;
}
.wh-analytics-kpi-grid {
  display: flex;
  gap: 8px;
  width: 100%;
}
.wh-analytics-kpi-card {
  flex: 1 1 25%;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
  padding: 6px 10px;
}
.wh-analytics-kpi-card.in-card { background: #f0fdf4; border-color: #bbf7d0; }
.wh-analytics-kpi-card.out-card { background: #fef2f2; border-color: #fecaca; }
.wh-analytics-kpi-card.net-card { background: #fbf8fd; border-color: #ebdcf5; }
.wh-analytics-kpi-card.act-card { background: #eff6ff; border-color: #bfdbfe; }
.wh-akpi-lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #666; }
.wh-akpi-tag { font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 3px; }
.in-tag { background: #dcfce7; color: #15803d; }
.out-tag { background: #fee2e2; color: #b91c1c; }
.net-tag { background: #f3e8ff; color: #7e22ce; }
.act-tag { background: #dbeafe; color: #1d4ed8; }
.wh-akpi-val { font-size: 13px; font-weight: 800; line-height: 1.2; margin-top: 2px; }
.in-val { color: #15803d; }
.out-val { color: #b91c1c; }
.wh-akpi-sub { font-size: 8.5px; color: #777; margin-top: 2px; display: flex; gap: 4px; align-items: center; }

.wh-print-chart-box {
  background: #ffffff;
  border: 1px solid #e8daf0;
  border-radius: 8px;
  padding: 8px 10px;
  page-break-inside: avoid !important;
  break-inside: avoid !important;
}
.wh-chart-header {
  font-size: 11px;
}
.wh-chart-canvas-wrap {
  width: 100%;
  overflow: hidden;
}
.wh-print-charts-row {
  display: flex;
  gap: 10px;
  page-break-inside: avoid !important;
  break-inside: avoid !important;
}

/* ==========================================================================
   CSS PRINT MEDIA RULES FOR HIGH RESOLUTION PHYSICAL PRINTING
   ========================================================================== */
@media print {
  body * {
    visibility: hidden;
  }
  #warehouse-printable-document,
  #warehouse-printable-document * {
    visibility: visible;
  }
  #warehouse-printable-document {
    position: absolute;
    left: 0;
    top: 0;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 100% !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .wh-print-chart-box {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }
  .wh-analytics-kpi-grid {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
  }
  .wh-sheet-table tr {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
  }
  @page {
    size: landscape;
    margin: 8mm;
  }
}
</style>

