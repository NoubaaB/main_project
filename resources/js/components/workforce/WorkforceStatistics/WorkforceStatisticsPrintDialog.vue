<template>
  <v-dialog
    v-model="internalOpen"
    max-width="1340"
    width="96vw"
    scrollable
    transition="dialog-bottom-transition"
  >
    <v-card class="wfs-print-modal-card rounded-2xl overflow-hidden d-flex flex-column" elevation="24">
      <!-- HERO HEADER (claude.md Workforce Module Gradient) -->
      <div class="wfs-print-hero px-5 py-3 text-white d-flex align-center justify-space-between flex-shrink-0">
        <div class="d-flex align-center ga-3">
          <div class="wfs-print-hero-icon">
            <v-icon size="24" color="white">mdi-printer</v-icon>
          </div>
          <div>
            <div class="text-subtitle-1 font-weight-bold d-flex align-center ga-2">
              <span>{{ tLabel('workforce_print_modal_title', 'Mise en Page d\'Impression des Statistiques de Main-d\'œuvre') }}</span>
              <v-chip size="x-small" color="white" variant="outlined" class="font-weight-bold text-uppercase">
                {{ currentViewLabel }}
              </v-chip>
            </div>
            <div class="text-caption text-white" style="opacity: 0.88; font-size: 11.5px !important;">
              {{ tLabel('workforce_print_modal_sub', 'Personnalisez les sections, colonnes, l\'orientation et les options d\'affichage avant impression') }}
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
            style="color: #1565C0 !important;"
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
      <div class="wfs-print-split-body d-flex flex-column flex-md-row flex-grow-1 overflow-hidden">
        <!-- ===================================================================== -->
        <!-- LEFT PANE: CONTROLS & COLUMN PICKER                                   -->
        <!-- ===================================================================== -->
        <div class="wfs-print-controls-pane d-flex flex-column border-e">
          <!-- Pinned Top: Target View Switcher -->
          <div class="pa-3 border-b bg-surface flex-shrink-0">
            <div class="d-flex align-center justify-space-between mb-1.5">
              <span class="text-caption font-weight-bold text-uppercase" style="color: #1565C0; letter-spacing: 0.04em;">
                <v-icon size="14" color="#1565C0" class="mr-1">mdi-view-headline</v-icon>
                {{ tLabel('target_view_to_print', 'Section / Vue à Imprimer') }}
              </span>
              <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold">
                {{ activePeriodSummary }}
              </v-chip>
            </div>

            <SegmentedPillToggle
              v-model="selectedView"
              :items="viewOptions"
              color="#1565C0"
              size="x-small"
              block
              @update:modelValue="onViewChanged"
            />
          </div>

          <!-- Controls Scrollable Form Area -->
          <div class="wfs-print-controls-scroll pa-4 overflow-y-auto flex-grow-1">
            <!-- 1. COLUMN PICKER (if applicable to selected view) -->
            <div v-if="availableColumns.length > 0" class="wfs-control-section mb-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="d-flex align-center ga-2 text-caption font-weight-bold" style="color: #1565C0;">
                  <v-icon size="16" color="#1565C0">mdi-table-headers-eye</v-icon>
                  <span>{{ tLabel('selected_columns_count', 'Colonnes à Imprimer') }}</span>
                </div>
                <div class="d-flex align-center ga-1">
                  <v-btn size="x-small" variant="text" color="#1565C0" class="font-weight-bold" @click="selectAllColumns">
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
                color="#42A5F5"
                hide-details
                :placeholder="tLabel('choose_columns_to_print', 'Choisir les colonnes à imprimer...')"
                prepend-inner-icon="mdi-view-column-outline"
                class="mb-2"
              >
                <template #chip="{ props, item }">
                  <v-chip
                    v-bind="props"
                    size="x-small"
                    color="#1565C0"
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
                      <v-checkbox-btn :model-value="isSelected" color="#1565C0" density="compact" />
                    </template>
                  </v-list-item>
                </template>
              </v-autocomplete>

              <!-- Quick Columns Toggle Checklist Strip -->
              <div class="wfs-quick-col-list pa-2 rounded-xl border bg-surface-variant-subtle">
                <div class="d-flex flex-wrap ga-1">
                  <v-chip
                    v-for="col in availableColumns"
                    :key="col.key"
                    size="x-small"
                    rounded="lg"
                    :color="activeColumnKeys.includes(col.key) ? '#1565C0' : 'grey'"
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
            <div class="wfs-control-section mb-4">
              <div class="d-flex align-center ga-2 text-caption font-weight-bold mb-2" style="color: #1565C0;">
                <v-icon size="16" color="#1565C0">mdi-page-layout-header</v-icon>
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
                    color="#1565C0"
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
                    color="#1565C0"
                    size="x-small"
                    block
                  />
                </v-col>
              </v-row>
            </div>

            <!-- 3. DISPLAY SWITCHES (AddCharge / Client Invoice style) -->
            <div class="wfs-control-section mb-4">
              <div class="d-flex align-center ga-2 text-caption font-weight-bold mb-2" style="color: #1565C0;">
                <v-icon size="16" color="#1565C0">mdi-tune-variant</v-icon>
                <span>{{ tLabel('display_options', 'Options d\'Affichage') }}</span>
              </div>

              <div class="options-container rounded-xl pa-2 border bg-surface">
                <!-- Company Header & Logo -->
                <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#1565C0">mdi-domain</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_company_header', 'Afficher l\'En-tête & Logo') }}</span>
                  </div>
                  <v-switch v-model="showCompanyHeader" hide-details density="compact" color="#1565C0" inset />
                </div>

                <!-- Executive KPI Summary Strip -->
                <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#1565C0">mdi-chart-box-outline</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_kpi_summary', 'Afficher les KPIs de Synthèse') }}</span>
                  </div>
                  <v-switch v-model="showKpiSummary" hide-details density="compact" color="#1565C0" inset />
                </div>

                <!-- Charts & Evolution Graphics -->
                <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#1565C0">mdi-chart-areaspline</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_charts_graphics', 'Afficher Graphiques d\'Évolution') }}</span>
                  </div>
                  <v-switch v-model="showCharts" hide-details density="compact" color="#1565C0" inset />
                </div>

                <!-- Detailed Tables -->
                <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#1565C0">mdi-table</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_detailed_table', 'Afficher Tableaux Détaillés') }}</span>
                  </div>
                  <v-switch v-model="showDetailedTable" hide-details density="compact" color="#1565C0" inset />
                </div>

                <!-- Zebra Striping -->
                <div class="d-flex align-center justify-space-between py-1 px-2 border-b">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#1565C0">mdi-view-sequential-outline</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_zebra_striping', 'Lignes Alternées Zébrées') }}</span>
                  </div>
                  <v-switch v-model="zebraStriping" hide-details density="compact" color="#1565C0" inset />
                </div>

                <!-- Timestamp & Signature Line -->
                <div class="d-flex align-center justify-space-between py-1 px-2">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="16" color="#1565C0">mdi-clock-outline</v-icon>
                    <span class="text-caption font-weight-medium">{{ tLabel('show_timestamp', 'Date & Heure d\'Édition') }}</span>
                  </div>
                  <v-switch v-model="showTimestamp" hide-details density="compact" color="#1565C0" inset />
                </div>
              </div>
            </div>

            <!-- 4. CUSTOM TITLE & NOTES (AddCharge style inputs) -->
            <div class="wfs-control-section mb-3">
              <v-text-field
                v-model="customTitle"
                :label="tLabel('document_custom_title', 'Titre du Document')"
                :placeholder="defaultDocumentTitle"
                density="compact"
                variant="outlined"
                rounded="lg"
                color="#42A5F5"
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
                color="#42A5F5"
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
                color="#1565C0"
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
                color="#1565C0"
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
        <div class="wfs-print-preview-pane flex-grow-1 pa-4 pa-md-6 overflow-y-auto">
          <div class="d-flex align-center justify-space-between mb-3">
            <div class="d-flex align-center ga-2">
              <v-icon size="18" color="#1565C0">mdi-eye-check-outline</v-icon>
              <span class="text-subtitle-2 font-weight-bold" style="color: #1565C0;">
                {{ tLabel('print_preview', 'Aperçu Avant Impression') }}
              </span>
              <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold text-uppercase">
                {{ orientation }} &bull; {{ fontSize }}
              </v-chip>
            </div>
            <span class="text-caption text-medium-emphasis">
              {{ activeColumnKeys.length }} / {{ availableColumns.length }} {{ tLabel('selected_columns_count', 'colonnes actives') }}
            </span>
          </div>

          <!-- THE PHYSICAL PRINT SHEET CONTAINER -->
          <div
            id="workforce-statistics-printable-document"
            :class="['wfs-print-sheet', 'wfs-orientation-' + orientation, 'wfs-font-' + fontSize]"
          >
            <!-- 1. Header with Company Logo -->
            <div v-if="showCompanyHeader" class="wfs-sheet-header pb-3 mb-3 border-b">
              <div class="d-flex align-center justify-space-between">
                <!-- Left: Logo & Company -->
                <div class="d-flex align-center ga-3">
                  <img
                    v-if="companyLogoUrl"
                    :src="companyLogoUrl"
                    alt="Company Logo"
                    class="wfs-sheet-logo"
                    @error="onLogoError"
                  />
                  <div>
                    <div class="wfs-sheet-company-name">{{ companyName }}</div>
                    <div class="wfs-sheet-subtext">{{ companyAddress || tLabel('industrial_unit_cold_storage', 'Unité Industrielle & Entrepôt Frigorifique') }}</div>
                    <div v-if="companyIce" class="wfs-sheet-subtext"><b>ICE:</b> {{ companyIce }} &bull; <b>{{ tLabel('phone', 'Tél') }}:</b> {{ companyPhone || '-' }}</div>
                  </div>
                </div>

                <!-- Right: Document Meta Stamp -->
                <div class="text-right">
                  <div class="wfs-sheet-doc-badge">{{ tLabel('official_workforce_report', 'Rapport Officiel de Main-d\'œuvre') }}</div>
                  <div class="wfs-sheet-date mt-1">
                    <b>{{ tLabel('date', 'Date') }}:</b> {{ currentDateFormatted }}
                  </div>
                  <div v-if="showTimestamp" class="wfs-sheet-subtext">
                    {{ tLabel('generated_by', 'Édité par') }}: {{ authUser?.name || tLabel('administrator', 'Administrateur') }} ({{ currentTimeFormatted }})
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. Document Title Banner -->
            <div class="wfs-sheet-title-banner pa-2.5 px-3 mb-3 rounded-lg text-white d-flex align-center justify-space-between">
              <div>
                <h1 class="wfs-sheet-h1 mb-0">{{ customTitle || defaultDocumentTitle }}</h1>
                <div class="wfs-sheet-desc" style="opacity: 0.9;">
                  {{ currentViewSubtitle }} &bull; {{ formattedDateRangeText }}
                </div>
              </div>
              <div class="text-right d-none d-sm-block">
                <span class="wfs-sheet-chip">
                  {{ printableRowsCount }} {{ tLabel('records', 'Enregistrements') }}
                </span>
              </div>
            </div>

            <!-- 3. KPI Summary Strip (if enabled) -->
            <div v-if="showKpiSummary" class="wfs-sheet-kpis-strip d-flex align-center ga-2 mb-3">
              <div class="wfs-sheet-kpi-pill">
                <span class="wfs-kpi-lbl">{{ tLabel('grand_total', 'Montant Total') }}</span>
                <span class="wfs-kpi-val" style="color: #1565C0;">{{ fmt(stats.grand_total || 0) }} {{ currency }}</span>
              </div>
              <div class="wfs-sheet-kpi-pill">
                <span class="wfs-kpi-lbl">{{ tLabel('active_days', 'Jours Travaillés') }}</span>
                <span class="wfs-kpi-val" style="color: #00897B;">{{ stats.daily?.length || 0 }} {{ tLabel('days', 'Jours') }}</span>
              </div>
              <div class="wfs-sheet-kpi-pill">
                <span class="wfs-kpi-lbl">{{ tLabel('total_workforces', 'Effectif Total') }}</span>
                <span class="wfs-kpi-val">{{ stats.workforces?.length || 0 }} {{ tLabel('workers', 'Ouvriers') }}</span>
              </div>
              <div class="wfs-sheet-kpi-pill">
                <span class="wfs-kpi-lbl">{{ tLabel('daily_average', 'Moyenne / Jour') }}</span>
                <span class="wfs-kpi-val" style="color: #E65100;">{{ fmt(dailyAverage) }} {{ currency }}</span>
              </div>
              <div v-if="stats.sections?.length" class="wfs-sheet-kpi-pill">
                <span class="wfs-kpi-lbl">{{ tLabel('top_section', 'Top Section') }}</span>
                <span class="wfs-kpi-val" style="color: #7B1FA2;">{{ stats.sections[0].name }} ({{ fmt(stats.sections[0].total) }} {{ currency }})</span>
              </div>
            </div>

            <!-- 4. CHARTS SECTION (when enabled and daily data available) -->
            <div v-if="showCharts && stats.daily?.length && (selectedView === 'full_summary' || selectedView === 'daily')" class="wfs-print-chart-box mb-3">
              <div class="d-flex align-center justify-space-between mb-1 pb-1 border-b">
                <span class="font-weight-bold text-caption text-uppercase" style="color: #1565C0;">
                  <v-icon size="14" color="#1565C0" class="mr-1">mdi-chart-areaspline</v-icon>
                  {{ tLabel('activity_totals', 'Évolution des Montants & Activités par Jour') }}
                </span>
                <span class="text-caption text-medium-emphasis">{{ stats.daily.length }} {{ tLabel('days', 'jours') }}</span>
              </div>
              <apexchart
                type="area"
                height="150"
                :options="dailyChartOptions"
                :series="dailyChartSeries"
              />
            </div>

            <!-- 5. DETAILED DATA TABLES -->
            <div v-if="showDetailedTable" class="wfs-sheet-table-section">
              <!-- A. FULL SUMMARY VIEW: Multi-table overview -->
              <template v-if="selectedView === 'full_summary'">
                <!-- Top Sections & Corporals 2-column breakdown -->
                <div class="d-flex ga-3 mb-3 flex-column flex-sm-row">
                  <!-- Sections Table -->
                  <div class="flex-grow-1" style="flex: 1 1 50%;">
                    <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1" style="color: #E65100;">
                      <v-icon size="14" color="#E65100">mdi-sitemap-outline</v-icon>
                      <span>{{ tLabel('total_by_section', 'Répartition par Section') }}</span>
                    </div>
                    <table class="wfs-sheet-table" :class="{ zebra: zebraStriping }">
                      <thead>
                        <tr>
                          <th>{{ tLabel('section', 'Section') }}</th>
                          <th class="text-center">{{ tLabel('days', 'Jours') }}</th>
                          <th class="text-right">{{ tLabel('total', 'Montant (DH)') }}</th>
                          <th class="text-right">%</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="s in stats.sections || []" :key="'sum-s-' + s.id">
                          <td class="font-weight-bold">{{ s.name }}</td>
                          <td class="text-center">{{ s.days_count }}</td>
                          <td class="text-right font-weight-bold" style="color: #E65100;">{{ fmt(s.total) }}</td>
                          <td class="text-right font-weight-bold">{{ s.percent }}%</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Corporals Table -->
                  <div class="flex-grow-1" style="flex: 1 1 50%;">
                    <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1" style="color: #7B1FA2;">
                      <v-icon size="14" color="#7B1FA2">mdi-account-tie</v-icon>
                      <span>{{ tLabel('total_by_corporal', 'Répartition par Caporal') }}</span>
                    </div>
                    <table class="wfs-sheet-table" :class="{ zebra: zebraStriping }">
                      <thead>
                        <tr>
                          <th>{{ tLabel('corporal', 'Caporal') }}</th>
                          <th class="text-center">{{ tLabel('days', 'Jours') }}</th>
                          <th class="text-right">{{ tLabel('total', 'Montant (DH)') }}</th>
                          <th class="text-right">%</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="c in stats.corporals || []" :key="'sum-c-' + c.id">
                          <td class="font-weight-bold">{{ c.name }}</td>
                          <td class="text-center">{{ c.days_count }}</td>
                          <td class="text-right font-weight-bold" style="color: #7B1FA2;">{{ fmt(c.total) }}</td>
                          <td class="text-right font-weight-bold">{{ c.percent }}%</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Top Workforces Table -->
                <div class="mb-3">
                  <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1" style="color: #1565C0;">
                    <v-icon size="14" color="#1565C0">mdi-account-star-outline</v-icon>
                    <span>{{ tLabel('top_workforces', 'Classement des Ouvriers (Top Performers)') }}</span>
                  </div>
                  <table class="wfs-sheet-table" :class="{ zebra: zebraStriping }">
                    <thead>
                      <tr>
                        <th style="width: 45px;" class="text-center">#</th>
                        <th>{{ tLabel('matricule', 'Matricule') }}</th>
                        <th>{{ tLabel('workforce', 'Nom & Prénom') }}</th>
                        <th>{{ tLabel('section', 'Section') }}</th>
                        <th>{{ tLabel('corporal', 'Caporal') }}</th>
                        <th class="text-center">{{ tLabel('days', 'Jours') }}</th>
                        <th class="text-right">{{ tLabel('daily_avg', 'Moyenne/J') }}</th>
                        <th class="text-right">{{ tLabel('total', 'Montant (DH)') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="w in (stats.workforces || []).slice(0, 15)" :key="'sum-w-' + w.id">
                        <td class="text-center font-weight-bold">
                          <span :class="['wfs-rank-badge', 'wfs-rank-' + (w.rank || 4)]">{{ w.rank }}</span>
                        </td>
                        <td class="font-mono text-caption">{{ w.matricule || '-' }}</td>
                        <td class="font-weight-bold">{{ w.full_name }}</td>
                        <td>{{ w.section_name || '-' }}</td>
                        <td>{{ w.corporal_name || '-' }}</td>
                        <td class="text-center font-weight-bold">{{ w.work_days_count }}</td>
                        <td class="text-right">{{ fmt(w.average_daily) }}</td>
                        <td class="text-right font-weight-bold" style="color: #1565C0;">{{ fmt(w.total) }}</td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr class="wfs-sheet-totals-row font-weight-bold">
                        <td colspan="5" class="text-right">{{ tLabel('grand_total', 'TOTAL GÉNÉRAL') }}:</td>
                        <td class="text-center">{{ stats.daily?.length || 0 }}</td>
                        <td class="text-right">{{ fmt(dailyAverage) }}</td>
                        <td class="text-right">{{ fmt(stats.grand_total || 0) }} {{ currency }}</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </template>

              <!-- B. DAILY EVOLUTION VIEW -->
              <template v-else-if="selectedView === 'daily'">
                <table class="wfs-sheet-table" :class="{ zebra: zebraStriping }">
                  <thead>
                    <tr>
                      <th v-if="activeColumnKeys.includes('date')">{{ tLabel('date', 'Date') }}</th>
                      <th v-if="activeColumnKeys.includes('day_name')">{{ tLabel('day', 'Jour') }}</th>
                      <th v-if="activeColumnKeys.includes('count')" class="text-center">{{ tLabel('activities', 'Activités / Effectif') }}</th>
                      <th v-if="activeColumnKeys.includes('average')" class="text-right">{{ tLabel('average_cost', 'Moyenne / Ouvrier') }}</th>
                      <th v-if="activeColumnKeys.includes('total')" class="text-right">{{ tLabel('total', 'Total Journalier (DH)') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="d in stats.daily || []" :key="'d-row-' + d.date">
                      <td v-if="activeColumnKeys.includes('date')" class="font-weight-bold font-mono">{{ d.date }}</td>
                      <td v-if="activeColumnKeys.includes('day_name')">{{ formatDayName(d.date) }}</td>
                      <td v-if="activeColumnKeys.includes('count')" class="text-center font-weight-bold">{{ d.count }}</td>
                      <td v-if="activeColumnKeys.includes('average')" class="text-right">{{ fmt(d.count ? (d.total / d.count) : 0) }}</td>
                      <td v-if="activeColumnKeys.includes('total')" class="text-right font-weight-bold" style="color: #1565C0;">{{ fmt(d.total) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="wfs-sheet-totals-row font-weight-bold">
                      <td :colspan="activeColumnKeys.filter(k => k !== 'total' && k !== 'count').length" class="text-right">{{ tLabel('grand_total', 'TOTAL') }}:</td>
                      <td v-if="activeColumnKeys.includes('count')" class="text-center">{{ totalActivitiesCount }}</td>
                      <td v-if="activeColumnKeys.includes('total')" class="text-right">{{ fmt(stats.grand_total || 0) }} {{ currency }}</td>
                    </tr>
                  </tfoot>
                </table>
              </template>

              <!-- C. SECTIONS BREAKDOWN VIEW -->
              <template v-else-if="selectedView === 'sections'">
                <table class="wfs-sheet-table" :class="{ zebra: zebraStriping }">
                  <thead>
                    <tr>
                      <th v-if="activeColumnKeys.includes('section_name')">{{ tLabel('section', 'Section / Atelier') }}</th>
                      <th v-if="activeColumnKeys.includes('days_count')" class="text-center">{{ tLabel('days', 'Jours Travaillés') }}</th>
                      <th v-if="activeColumnKeys.includes('percent')" class="text-right">% {{ tLabel('contribution', 'Contribution') }}</th>
                      <th v-if="activeColumnKeys.includes('total')" class="text-right">{{ tLabel('total', 'Montant Total (DH)') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="s in stats.sections || []" :key="'sec-row-' + s.id">
                      <td v-if="activeColumnKeys.includes('section_name')" class="font-weight-bold">{{ s.name }}</td>
                      <td v-if="activeColumnKeys.includes('days_count')" class="text-center font-weight-bold">{{ s.days_count }}</td>
                      <td v-if="activeColumnKeys.includes('percent')" class="text-right font-weight-bold">{{ s.percent }}%</td>
                      <td v-if="activeColumnKeys.includes('total')" class="text-right font-weight-bold" style="color: #E65100;">{{ fmt(s.total) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="wfs-sheet-totals-row font-weight-bold">
                      <td :colspan="activeColumnKeys.filter(k => k !== 'total').length" class="text-right">{{ tLabel('grand_total', 'TOTAL') }}:</td>
                      <td v-if="activeColumnKeys.includes('total')" class="text-right">{{ fmt(stats.grand_total || 0) }} {{ currency }}</td>
                    </tr>
                  </tfoot>
                </table>
              </template>

              <!-- D. CORPORALS BREAKDOWN VIEW -->
              <template v-else-if="selectedView === 'corporals'">
                <table class="wfs-sheet-table" :class="{ zebra: zebraStriping }">
                  <thead>
                    <tr>
                      <th v-if="activeColumnKeys.includes('corporal_name')">{{ tLabel('corporal', 'Caporal') }}</th>
                      <th v-if="activeColumnKeys.includes('days_count')" class="text-center">{{ tLabel('days', 'Jours Travaillés') }}</th>
                      <th v-if="activeColumnKeys.includes('percent')" class="text-right">% {{ tLabel('contribution', 'Contribution') }}</th>
                      <th v-if="activeColumnKeys.includes('total')" class="text-right">{{ tLabel('total', 'Montant Total (DH)') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in stats.corporals || []" :key="'corp-row-' + c.id">
                      <td v-if="activeColumnKeys.includes('corporal_name')" class="font-weight-bold">{{ c.name }}</td>
                      <td v-if="activeColumnKeys.includes('days_count')" class="text-center font-weight-bold">{{ c.days_count }}</td>
                      <td v-if="activeColumnKeys.includes('percent')" class="text-right font-weight-bold">{{ c.percent }}%</td>
                      <td v-if="activeColumnKeys.includes('total')" class="text-right font-weight-bold" style="color: #7B1FA2;">{{ fmt(c.total) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="wfs-sheet-totals-row font-weight-bold">
                      <td :colspan="activeColumnKeys.filter(k => k !== 'total').length" class="text-right">{{ tLabel('grand_total', 'TOTAL') }}:</td>
                      <td v-if="activeColumnKeys.includes('total')" class="text-right">{{ fmt(stats.grand_total || 0) }} {{ currency }}</td>
                    </tr>
                  </tfoot>
                </table>
              </template>

              <!-- E. WORKFORCES RANKING VIEW -->
              <template v-else-if="selectedView === 'workforces'">
                <table class="wfs-sheet-table" :class="{ zebra: zebraStriping }">
                  <thead>
                    <tr>
                      <th v-if="activeColumnKeys.includes('rank')" style="width: 45px;" class="text-center">#</th>
                      <th v-if="activeColumnKeys.includes('matricule')">{{ tLabel('matricule', 'Matricule') }}</th>
                      <th v-if="activeColumnKeys.includes('name')">{{ tLabel('workforce', 'Nom & Prénom') }}</th>
                      <th v-if="activeColumnKeys.includes('section')">{{ tLabel('section', 'Section') }}</th>
                      <th v-if="activeColumnKeys.includes('corporal')">{{ tLabel('corporal', 'Caporal') }}</th>
                      <th v-if="activeColumnKeys.includes('team')">{{ tLabel('team', 'Équipe') }}</th>
                      <th v-if="activeColumnKeys.includes('days')" class="text-center">{{ tLabel('days', 'Jours') }}</th>
                      <th v-if="activeColumnKeys.includes('average')" class="text-right">{{ tLabel('daily_avg', 'Moyenne/J') }}</th>
                      <th v-if="activeColumnKeys.includes('total')" class="text-right">{{ tLabel('total', 'Montant Total (DH)') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="w in stats.workforces || []" :key="'wf-row-' + w.id">
                      <td v-if="activeColumnKeys.includes('rank')" class="text-center font-weight-bold">
                        <span :class="['wfs-rank-badge', 'wfs-rank-' + (w.rank || 4)]">{{ w.rank }}</span>
                      </td>
                      <td v-if="activeColumnKeys.includes('matricule')" class="font-mono text-caption">{{ w.matricule || '-' }}</td>
                      <td v-if="activeColumnKeys.includes('name')" class="font-weight-bold">{{ w.full_name }}</td>
                      <td v-if="activeColumnKeys.includes('section')">{{ w.section_name || '-' }}</td>
                      <td v-if="activeColumnKeys.includes('corporal')">{{ w.corporal_name || '-' }}</td>
                      <td v-if="activeColumnKeys.includes('team')">{{ w.team_name || '-' }}</td>
                      <td v-if="activeColumnKeys.includes('days')" class="text-center font-weight-bold">{{ w.work_days_count }}</td>
                      <td v-if="activeColumnKeys.includes('average')" class="text-right">{{ fmt(w.average_daily) }}</td>
                      <td v-if="activeColumnKeys.includes('total')" class="text-right font-weight-bold" style="color: #1565C0;">{{ fmt(w.total) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="wfs-sheet-totals-row font-weight-bold">
                      <td :colspan="activeColumnKeys.filter(k => k !== 'total').length" class="text-right">{{ tLabel('grand_total', 'TOTAL') }}:</td>
                      <td v-if="activeColumnKeys.includes('total')" class="text-right">{{ fmt(stats.grand_total || 0) }} {{ currency }}</td>
                    </tr>
                  </tfoot>
                </table>
              </template>

              <!-- F. WORKFORCE MOVEMENT & ASSIGNMENT TRACKER VIEW -->
              <template v-else-if="selectedView === 'tracker'">
                <table class="wfs-sheet-table" :class="{ zebra: zebraStriping }">
                  <thead>
                    <tr>
                      <th v-if="activeColumnKeys.includes('period')">{{ tLabel('period', 'Période / Date') }}</th>
                      <th v-if="activeColumnKeys.includes('matricule')">{{ tLabel('matricule', 'Matricule') }}</th>
                      <th v-if="activeColumnKeys.includes('workforce')">{{ tLabel('workforce', 'Ouvrier') }}</th>
                      <th v-if="activeColumnKeys.includes('corporal')">{{ tLabel('corporal', 'Caporal') }}</th>
                      <th v-if="activeColumnKeys.includes('section')">{{ tLabel('section', 'Section') }}</th>
                      <th v-if="activeColumnKeys.includes('team')">{{ tLabel('team', 'Équipe') }}</th>
                      <th v-if="activeColumnKeys.includes('count')" class="text-center">{{ tLabel('activities_or_days', 'Activités') }}</th>
                      <th v-if="activeColumnKeys.includes('amount')" class="text-right">{{ tLabel('total', 'Montant (DH)') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in movementStats.timeline || []" :key="'tr-row-' + idx">
                      <td v-if="activeColumnKeys.includes('period')" class="font-weight-bold font-mono">{{ row.period_label }}</td>
                      <td v-if="activeColumnKeys.includes('matricule')" class="font-mono text-caption">{{ row.matricule || '-' }}</td>
                      <td v-if="activeColumnKeys.includes('workforce')" class="font-weight-bold">{{ row.workforce_name }}</td>
                      <td v-if="activeColumnKeys.includes('corporal')">{{ row.corporal_name || '-' }}</td>
                      <td v-if="activeColumnKeys.includes('section')">{{ row.section_name || '-' }}</td>
                      <td v-if="activeColumnKeys.includes('team')">{{ row.team_label || '-' }}</td>
                      <td v-if="activeColumnKeys.includes('count')" class="text-center font-weight-bold">{{ row.activities_count || row.days_count || 1 }}</td>
                      <td v-if="activeColumnKeys.includes('amount')" class="text-right font-weight-bold" style="color: #1565C0;">{{ fmt(row.total_amount) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="wfs-sheet-totals-row font-weight-bold">
                      <td :colspan="activeColumnKeys.filter(k => k !== 'amount').length" class="text-right">{{ tLabel('grand_total', 'TOTAL') }}:</td>
                      <td v-if="activeColumnKeys.includes('amount')" class="text-right">{{ fmt(movementStats.summary?.total_amount || 0) }} {{ currency }}</td>
                    </tr>
                  </tfoot>
                </table>
              </template>
            </div>

            <!-- 6. DOCUMENT FOOTER NOTES & SIGNATURE BLOCK -->
            <div class="wfs-sheet-footer mt-4 pt-3 border-t">
              <div v-if="customNotes" class="wfs-sheet-notes mb-3 pa-2 rounded bg-surface-variant-subtle border">
                <b>{{ tLabel('notes', 'Remarques / Notes') }}:</b> {{ customNotes }}
              </div>

              <div class="d-flex align-center justify-space-between text-caption text-medium-emphasis">
                <div>
                  <span>{{ companyName }} &bull; {{ tLabel('workforce_module_system', 'Système de Gestion de la Main-d\'œuvre') }}</span>
                </div>
                <div class="d-flex align-center ga-4">
                  <span>{{ tLabel('page_count_indicator', 'Page') }} 1/1</span>
                  <span><b>{{ tLabel('visa_hr_manager', 'Visa Direction / Ressources Humaines') }}:</b> ____________________</span>
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
import print from 'vue3-print-nb';
import * as XLSX from 'xlsx';
import moment from 'moment';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import VueApexCharts from 'vue3-apexcharts';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useCompanySettings } from '@/stores/CompanySettings';

export default {
  name: 'WorkforceStatisticsPrintDialog',
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
      default: 'full_summary',
    },
    selectedYear: {
      type: Number,
      default: () => new Date().getFullYear(),
    },
    selectedMonths: {
      type: Array,
      default: () => [new Date().getMonth()],
    },
    monthNames: {
      type: Array,
      default: () => [],
    },
    formattedDateRangeText: {
      type: String,
      default: '',
    },
    activePeriodSummary: {
      type: String,
      default: '',
    },
    stats: {
      type: Object,
      default: () => ({
        grand_total: 0,
        daily: [],
        sections: [],
        corporals: [],
        workforces: [],
      }),
    },
    movementStats: {
      type: Object,
      default: () => ({
        timeline: [],
        summary: {},
        workforce_summaries: [],
      }),
    },
  },
  emits: ['update:modelValue', 'view-change', 'printed'],
  data() {
    return {
      selectedView: 'full_summary',
      orientation: 'landscape',
      fontSize: 'normal',
      activeColumnKeys: [],
      showCompanyHeader: true,
      showKpiSummary: true,
      showCharts: true,
      showDetailedTable: true,
      zebraStriping: true,
      showTimestamp: true,
      customTitle: '',
      customNotes: '',
      printConfig: {
        id: 'workforce-statistics-printable-document',
        popTitle: 'BluePrintFish - Rapport Statistiques Main-d\'œuvre',
      },
    };
  },
  computed: {
    internalOpen: {
      get() { return this.modelValue; },
      set(val) { this.$emit('update:modelValue', val); },
    },
    main() { return useMain(); },
    auth() { return useAuth(); },
    authUser() { return this.auth.user; },
    currency() { return this.main.currency || 'DH'; },

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
      return null;
    },

    currentLocale() {
      return this.$i18n?.locale || 'fr';
    },
    currentDateFormatted() {
      return moment().format('DD/MM/YYYY');
    },
    currentTimeFormatted() {
      return moment().format('HH:mm');
    },

    viewOptions() {
      return [
        { value: 'full_summary', label: this.tLabel('full_summary', 'Synthèse Complète'), icon: 'mdi-view-dashboard-outline' },
        { value: 'daily', label: this.tLabel('daily_evolution', 'Évolution Journalière'), icon: 'mdi-calendar-today' },
        { value: 'sections', label: this.tLabel('sections_breakdown', 'Sections'), icon: 'mdi-sitemap-outline' },
        { value: 'corporals', label: this.tLabel('corporals_breakdown', 'Caporaux'), icon: 'mdi-account-tie' },
        { value: 'workforces', label: this.tLabel('workforces_ranking', 'Ouvriers'), icon: 'mdi-account-star-outline' },
        { value: 'tracker', label: this.tLabel('movement_tracker', 'Mouvements'), icon: 'mdi-routes' },
      ];
    },

    currentViewLabel() {
      const opt = this.viewOptions.find(o => o.value === this.selectedView);
      return opt ? opt.label : this.selectedView;
    },

    dailyAverage() {
      const activeDays = (this.stats.daily || []).length;
      if (!activeDays) return 0;
      return (this.stats.grand_total || 0) / activeDays;
    },

    totalActivitiesCount() {
      return (this.stats.daily || []).reduce((acc, d) => acc + (d.count || 0), 0);
    },

    printableRowsCount() {
      if (this.selectedView === 'daily') return (this.stats.daily || []).length;
      if (this.selectedView === 'sections') return (this.stats.sections || []).length;
      if (this.selectedView === 'corporals') return (this.stats.corporals || []).length;
      if (this.selectedView === 'workforces') return (this.stats.workforces || []).length;
      if (this.selectedView === 'tracker') return (this.movementStats.timeline || []).length;
      return (this.stats.workforces || []).length + (this.stats.sections || []).length;
    },

    defaultDocumentTitle() {
      if (this.selectedView === 'daily') return `${this.tLabel('daily_evolution', 'Évolution Journalière')} — ${this.activePeriodSummary}`;
      if (this.selectedView === 'sections') return `${this.tLabel('sections_breakdown', 'Répartition par Section')} — ${this.activePeriodSummary}`;
      if (this.selectedView === 'corporals') return `${this.tLabel('corporals_breakdown', 'Répartition par Caporal')} — ${this.activePeriodSummary}`;
      if (this.selectedView === 'workforces') return `${this.tLabel('workforces_ranking', 'Classement & Rendement des Ouvriers')} — ${this.activePeriodSummary}`;
      if (this.selectedView === 'tracker') return `${this.tLabel('movement_tracker', 'Suivi des Mouvements & Affectations')} — ${this.activePeriodSummary}`;
      return `${this.tLabel('workforce_statistics', 'Rapport Statistique de la Main-d\'œuvre')} — ${this.activePeriodSummary}`;
    },

    currentViewSubtitle() {
      if (this.selectedView === 'daily') return this.tLabel('daily_evolution_sub', 'Récapitulatif des présences et des coûts journaliers');
      if (this.selectedView === 'sections') return this.tLabel('sections_sub', 'Répartition des coûts et journées de travail par atelier / section');
      if (this.selectedView === 'corporals') return this.tLabel('corporals_sub', 'Répartition des charges et effectifs supervisés par caporal');
      if (this.selectedView === 'workforces') return this.tLabel('workforces_sub', 'Tableau d\'honneur, effectifs, assiduité et montants attribués');
      if (this.selectedView === 'tracker') return this.tLabel('tracker_sub', 'Audit chronologique des arrivées, départs et affectations');
      return this.tLabel('full_summary_sub', 'Synthèse exécutive globale des effectifs et des coûts salariaux');
    },

    availableColumns() {
      if (this.selectedView === 'daily') {
        return [
          { key: 'date', label: this.tLabel('date', 'Date') },
          { key: 'day_name', label: this.tLabel('day', 'Jour de Semaine') },
          { key: 'count', label: this.tLabel('activities', 'Activités / Effectif') },
          { key: 'average', label: this.tLabel('average_cost', 'Moyenne / Ouvrier') },
          { key: 'total', label: this.tLabel('total', 'Total Journalier (DH)') },
        ];
      }
      if (this.selectedView === 'sections') {
        return [
          { key: 'section_name', label: this.tLabel('section', 'Section / Atelier') },
          { key: 'days_count', label: this.tLabel('days', 'Jours Travaillés') },
          { key: 'percent', label: '% ' + this.tLabel('contribution', 'Contribution') },
          { key: 'total', label: this.tLabel('total', 'Montant Total (DH)') },
        ];
      }
      if (this.selectedView === 'corporals') {
        return [
          { key: 'corporal_name', label: this.tLabel('corporal', 'Caporal') },
          { key: 'days_count', label: this.tLabel('days', 'Jours Travaillés') },
          { key: 'percent', label: '% ' + this.tLabel('contribution', 'Contribution') },
          { key: 'total', label: this.tLabel('total', 'Montant Total (DH)') },
        ];
      }
      if (this.selectedView === 'workforces') {
        return [
          { key: 'rank', label: '#' },
          { key: 'matricule', label: this.tLabel('matricule', 'Matricule') },
          { key: 'name', label: this.tLabel('workforce', 'Nom & Prénom') },
          { key: 'section', label: this.tLabel('section', 'Section') },
          { key: 'corporal', label: this.tLabel('corporal', 'Caporal') },
          { key: 'team', label: this.tLabel('team', 'Équipe') },
          { key: 'days', label: this.tLabel('days', 'Jours Travaillés') },
          { key: 'average', label: this.tLabel('daily_avg', 'Moyenne/J') },
          { key: 'total', label: this.tLabel('total', 'Montant Total (DH)') },
        ];
      }
      if (this.selectedView === 'tracker') {
        return [
          { key: 'period', label: this.tLabel('period', 'Période / Date') },
          { key: 'matricule', label: this.tLabel('matricule', 'Matricule') },
          { key: 'workforce', label: this.tLabel('workforce', 'Ouvrier') },
          { key: 'corporal', label: this.tLabel('corporal', 'Caporal') },
          { key: 'section', label: this.tLabel('section', 'Section') },
          { key: 'team', label: this.tLabel('team', 'Équipe') },
          { key: 'count', label: this.tLabel('activities_or_days', 'Activités') },
          { key: 'amount', label: this.tLabel('total', 'Montant (DH)') },
        ];
      }
      return [];
    },

    // Daily Evolution Area Chart options for print preview
    dailyChartOptions() {
      const daily = this.stats.daily || [];
      return {
        chart: {
          type: 'area',
          background: '#ffffff',
          toolbar: { show: false },
          animations: { enabled: false },
          fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        },
        colors: ['#1565C0', '#00897B'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
          type: 'gradient',
          gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 95, 100] },
        },
        xaxis: {
          categories: daily.map(d => d.date),
          labels: { style: { colors: '#64748b', fontSize: '9px', fontWeight: 600 }, rotate: -45 },
          axisBorder: { show: false },
          axisTicks: { show: false },
        },
        yaxis: [
          {
            title: { text: 'Montant (DH)', style: { color: '#1565C0', fontSize: '9px', fontWeight: 700 } },
            labels: { style: { colors: '#64748b', fontSize: '9px' }, formatter: v => this.fmt(v) },
          },
          {
            opposite: true,
            title: { text: 'Effectif', style: { color: '#00897B', fontSize: '9px', fontWeight: 700 } },
            labels: { style: { colors: '#00897B', fontSize: '9px' }, formatter: v => Math.round(v) },
          },
        ],
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '10px' },
      };
    },

    dailyChartSeries() {
      const daily = this.stats.daily || [];
      return [
        { name: 'Montant (DH)', data: daily.map(d => Math.round(d.total || 0)) },
        { name: 'Effectif', data: daily.map(d => d.count || 0) },
      ];
    },
  },
  watch: {
    modelValue(val) {
      if (val) {
        this.selectedView = this.initialView || 'full_summary';
        this.initColumnsForView();
        if (this.printConfig) {
          this.printConfig.popTitle = `${this.companyName} - ${this.defaultDocumentTitle}`;
        }
        if (!this.companySettings || !Object.keys(this.companySettings).length) {
          this.companySettingsStore.fetchSettings();
        }
      }
    },
    initialView(val) {
      if (val) {
        this.selectedView = val;
        this.initColumnsForView();
      }
    },
  },
  created() {
    this.selectedView = this.initialView || 'full_summary';
    this.initColumnsForView();
    if (this.modelValue && (!this.companySettings || !Object.keys(this.companySettings).length)) {
      this.companySettingsStore.fetchSettings();
    }
  },
  methods: {
    tLabel(key, fallback) {
      return this.$t ? this.$t(key) : fallback;
    },

    fmt(num) {
      if (this.main.formatDisplayNumber) return this.main.formatDisplayNumber(num);
      return Number(num || 0).toLocaleString();
    },

    formatDayName(dateStr) {
      try {
        return moment(dateStr).locale(this.currentLocale).format('dddd');
      } catch (e) {
        return '';
      }
    },

    initColumnsForView() {
      this.activeColumnKeys = this.availableColumns.map(c => c.key);
    },

    onViewChanged(newView) {
      this.selectedView = newView;
      this.initColumnsForView();
      this.$emit('view-change', newView);
    },

    toggleColumn(key) {
      const idx = this.activeColumnKeys.indexOf(key);
      if (idx >= 0) {
        if (this.activeColumnKeys.length > 1) {
          this.activeColumnKeys.splice(idx, 1);
        }
      } else {
        this.activeColumnKeys.push(key);
      }
    },

    selectAllColumns() {
      this.activeColumnKeys = this.availableColumns.map(c => c.key);
    },

    deselectAllColumns() {
      if (this.availableColumns.length > 0) {
        this.activeColumnKeys = [this.availableColumns[0].key];
      }
    },

    onLogoError(e) {
      if (e?.target) e.target.style.display = 'none';
    },

    onPrintTriggered() {
      this.$emit('printed', { view: this.selectedView });
    },

    exportExcelPreview() {
      let rows = [];
      let filename = `Workforce_Statistics_${this.selectedView}_${this.selectedYear}.xlsx`;

      if (this.selectedView === 'daily') {
        rows = (this.stats.daily || []).map(d => ({
          Date: d.date,
          Jour: this.formatDayName(d.date),
          Activites_Effectif: d.count,
          Total_DH: d.total,
        }));
      } else if (this.selectedView === 'sections') {
        rows = (this.stats.sections || []).map(s => ({
          Section: s.name,
          Jours: s.days_count,
          Pourcentage: `${s.percent}%`,
          Total_DH: s.total,
        }));
      } else if (this.selectedView === 'corporals') {
        rows = (this.stats.corporals || []).map(c => ({
          Caporal: c.name,
          Jours: c.days_count,
          Pourcentage: `${c.percent}%`,
          Total_DH: c.total,
        }));
      } else if (this.selectedView === 'workforces') {
        rows = (this.stats.workforces || []).map(w => ({
          Rang: w.rank,
          Matricule: w.matricule,
          Nom: w.full_name,
          Section: w.section_name,
          Caporal: w.corporal_name,
          Equipe: w.team_name,
          Jours: w.work_days_count,
          Moyenne_J: w.average_daily,
          Total_DH: w.total,
        }));
      } else if (this.selectedView === 'tracker') {
        rows = (this.movementStats.timeline || []).map(r => ({
          Periode: r.period_label,
          Matricule: r.matricule,
          Ouvrier: r.workforce_name,
          Caporal: r.corporal_name,
          Section: r.section_name,
          Equipe: r.team_label,
          Activites: r.activities_count || r.days_count,
          Montant_DH: r.total_amount,
        }));
      } else {
        // full summary
        rows = (this.stats.workforces || []).map(w => ({
          Rang: w.rank,
          Matricule: w.matricule,
          Nom: w.full_name,
          Section: w.section_name,
          Caporal: w.corporal_name,
          Jours: w.work_days_count,
          Total_DH: w.total,
        }));
      }

      const ws = XLSX.utils.json_to_sheet(rows);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Workforce_Report');
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
   WORKFORCE STATISTICS PRINT MODAL & LIVE PREVIEW (claude.md)
   ========================================================================== */
.wfs-print-modal-card {
  height: 90vh;
  max-height: 880px;
  background: rgb(var(--v-theme-surface));
}

/* Hero Header */
.wfs-print-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
}
.wfs-print-hero-icon {
  background: rgba(255, 255, 255, 0.2);
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Left Pane: Controls */
.wfs-print-controls-pane {
  width: 480px;
  min-width: 420px;
  max-width: 520px;
  flex-shrink: 0;
  min-height: 0;
  background: rgb(var(--v-theme-surface));
}
.wfs-print-controls-scroll {
  min-height: 0;
}
.wfs-quick-col-list {
  border-color: rgba(21, 101, 192, 0.15) !important;
}

/* Right Pane: Live Preview */
.wfs-print-preview-pane {
  min-height: 0;
  background: rgba(0, 0, 0, 0.04);
}

/* THE PHYSICAL A4 SHEET SIMULATION */
.wfs-print-sheet {
  background: #ffffff;
  color: #1a1a1a;
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
  padding: 24px 28px;
  margin: 0 auto;
  transition: max-width 0.2s ease;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}
.wfs-print-sheet.wfs-orientation-landscape {
  max-width: 1060px;
  min-width: 900px;
}
.wfs-print-sheet.wfs-orientation-portrait {
  max-width: 780px;
}

/* Font Size Scales */
.wfs-print-sheet.wfs-font-compact {
  font-size: 11px;
}
.wfs-print-sheet.wfs-font-normal {
  font-size: 12px;
}
.wfs-print-sheet.wfs-font-large {
  font-size: 13.5px;
}

/* Sheet Header */
.wfs-sheet-logo {
  height: 48px;
  max-width: 160px;
  object-fit: contain;
}
.wfs-sheet-company-name {
  font-weight: 800;
  font-size: 14px;
  color: #1565C0;
  letter-spacing: -0.01em;
}
.wfs-sheet-subtext {
  font-size: 10px;
  color: #666;
}
.wfs-sheet-doc-badge {
  display: inline-block;
  background: rgba(21, 101, 192, 0.08);
  color: #1565C0;
  font-weight: 800;
  font-size: 10px;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid rgba(21, 101, 192, 0.2);
}
.wfs-sheet-date {
  font-size: 11px;
  color: #333;
}

/* Title Banner */
.wfs-sheet-title-banner {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
}
.wfs-sheet-h1 {
  font-size: 15px;
  font-weight: 800;
  line-height: 1.2;
}
.wfs-sheet-desc {
  font-size: 10px;
}
.wfs-sheet-chip {
  background: rgba(255, 255, 255, 0.22);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 10.5px;
  font-weight: 700;
}

/* KPI Summary Strip */
.wfs-sheet-kpi-pill {
  flex: 1;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 6px 10px;
  text-align: center;
}
.wfs-kpi-lbl {
  display: block;
  font-size: 9.5px;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
}
.wfs-kpi-val {
  font-size: 13px;
  font-weight: 800;
  color: #1e293b;
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace;
}

/* Chart Box */
.wfs-print-chart-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px 12px;
  page-break-inside: avoid !important;
  break-inside: avoid !important;
}

/* Printable Table */
.wfs-sheet-table-section {
  width: 100%;
}
.wfs-sheet-table {
  width: 100%;
  border-collapse: collapse;
  font-size: inherit;
  margin-bottom: 8px;
}
.wfs-sheet-table th {
  background: #1565C0;
  color: #ffffff;
  padding: 7px 9px;
  font-size: 10.5px;
  font-weight: 800;
  text-transform: uppercase;
  border: 1px solid #0D47A1;
  white-space: nowrap;
}
.wfs-sheet-table td {
  padding: 6px 8px;
  border: 1px solid #e0e0e0;
  vertical-align: middle;
}
.wfs-sheet-table.zebra tbody tr:nth-child(even) {
  background: #f8fafc;
}
.wfs-sheet-totals-row td {
  background: #e3f2fd;
  border-top: 2px solid #1565C0;
  padding: 8px 9px;
  color: #0D47A1;
}

/* Rank Badge */
.wfs-rank-badge {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 800;
  font-size: 9.5px;
}
.wfs-rank-1 { background: #fef3c7; color: #b45309; }
.wfs-rank-2 { background: #e2e8f0; color: #334155; }
.wfs-rank-3 { background: #ffedd5; color: #c2410c; }
.wfs-rank-4 { background: #f1f5f9; color: #475569; }

/* Print Footer */
.wfs-sheet-notes {
  font-size: 10.5px;
  color: #444;
}

/* ==========================================================================
   CSS PRINT MEDIA RULES FOR HIGH RESOLUTION PHYSICAL PRINTING
   ========================================================================== */
@media print {
  body * {
    visibility: hidden;
  }
  #workforce-statistics-printable-document,
  #workforce-statistics-printable-document * {
    visibility: visible;
  }
  #workforce-statistics-printable-document {
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
  .wfs-print-chart-box {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }
  .wfs-sheet-table tr {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
  }
  @page {
    size: landscape;
    margin: 8mm;
  }
}
</style>

