<template>
  <div class="chart-management-page">
    <!-- Hero Header (Rolled back to previous design & color) -->
    <div class="cm-hero mb-4">
      <div class="cm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="cm-hero-icon"><v-icon size="28" color="white">mdi-chart-areaspline</v-icon></div>
          <div>
            <div class="cm-hero-title">{{ $t('chart_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('chart_management_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top KPI Metrics Strip (4 Balanced Cards) -->
    <v-row dense class="mb-4">
      <v-col cols="12" sm="6" md="3">
        <div class="kpi-metric-tile rounded-xl pa-4 d-flex align-center ga-3">
          <div class="kpi-tile-icon bg-cyan-subtle">
            <v-icon color="#00BCD4" size="22">mdi-chart-box-outline</v-icon>
          </div>
          <div>
            <div class="d-flex align-baseline ga-2">
              <span class="kpi-tile-val text-h6 font-weight-bold fnm-currency-num">{{ charts.length }}</span>
              <v-chip size="x-small" color="success" variant="tonal" class="font-weight-bold fnm-currency-num">
                {{ activeChartsCount }} {{ $t('active') || 'actifs' }}
              </v-chip>
            </div>
            <div class="kpi-tile-lbl text-caption text-medium-emphasis">{{ $t('total_charts') || 'Graphiques configurés' }}</div>
          </div>
        </div>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <div class="kpi-metric-tile rounded-xl pa-4 d-flex align-center ga-3">
          <div class="kpi-tile-icon bg-sky-subtle">
            <v-icon color="#0284c7" size="22">mdi-chart-areaspline</v-icon>
          </div>
          <div>
            <div class="kpi-tile-val text-h6 font-weight-bold fnm-currency-num">{{ typeDistributionSummary }}</div>
            <div class="kpi-tile-lbl text-caption text-medium-emphasis">{{ $t('chart_type') || 'Typologies visuelles' }}</div>
          </div>
        </div>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <div class="kpi-metric-tile rounded-xl pa-4 d-flex align-center ga-3">
          <div class="kpi-tile-icon bg-indigo-subtle">
            <v-icon color="#4f46e5" size="22">mdi-database-eye-outline</v-icon>
          </div>
          <div>
            <div class="kpi-tile-val text-h6 font-weight-bold fnm-currency-num">{{ totalSeriesCount }}</div>
            <div class="kpi-tile-lbl text-caption text-medium-emphasis">{{ $t('configured_series') || 'Flux de données / Séries' }}</div>
          </div>
        </div>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <div class="kpi-metric-tile rounded-xl pa-4 d-flex align-center ga-3">
          <div class="kpi-tile-icon bg-amber-subtle">
            <v-icon color="#d97706" size="22">mdi-engine-outline</v-icon>
          </div>
          <div>
            <div class="kpi-tile-val text-h6 font-weight-bold fnm-currency-num">{{ activeSourcesCount }} / 6</div>
            <div class="kpi-tile-lbl text-caption text-medium-emphasis">{{ $t('connected_sources') || 'Moteurs usine connectés' }}</div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Navigation View Switcher (SegmentedPillToggle) -->
    <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-4">
      <SegmentedPillToggle
        v-model="activeViewTab"
        :items="navPillItems"
        color="#00BCD4"
        size="medium"
      />

      <!-- Toolbar Actions -->
      <div class="d-flex align-center ga-2 flex-wrap">
        <!-- Live Realtime Sync Status -->
        <v-chip size="small" variant="tonal" color="success" class="text-caption font-weight-medium">
          <span class="pulse-dot mr-1"></span>
          {{ $t('realtime_sync_success') || 'Synchronisation temps réel' }}
        </v-chip>

        <!-- Quick Action if on List -->
        <template v-if="activeViewTab === 'list'">
          <!-- View mode toggle: Cards vs Table -->
          <v-btn-toggle
            v-model="listDisplayMode"
            density="compact"
            rounded="pill"
            color="#00BCD4"
            mandatory
            variant="outlined"
          >
            <v-btn value="gallery" size="small" :title="$t('view_mode_gallery') || 'Vue Cartes'">
              <v-icon size="16">mdi-view-grid-outline</v-icon>
            </v-btn>
            <v-btn value="table" size="small" :title="$t('view_mode_table') || 'Vue Tableau'">
              <v-icon size="16">mdi-table</v-icon>
            </v-btn>
          </v-btn-toggle>

          <v-btn
            size="small"
            rounded="pill"
            variant="tonal"
            color="#00BCD4"
            icon="mdi-refresh"
            :loading="store.loading"
            @click="fetchCharts"
            :title="$t('refresh') || 'Actualiser'"
          />

          <v-btn
            color="#00BCD4"
            variant="flat"
            height="38"
            rounded="xl"
            prepend-icon="mdi-plus"
            class="font-weight-bold text-white text-none param-add-btn ml-1"
            @click="startCreate"
          >
            {{ $t('create_new_chart') || 'Nouveau Graphique' }}
          </v-btn>
        </template>
      </div>
    </div>

    <!-- VIEW 1: CHARTS LIST -->
    <div v-if="activeViewTab === 'list'" class="view-panel">
      <!-- Filters Toolbar -->
      <v-card class="filter-toolbar-card rounded-xl pa-3 mb-4" elevation="0">
        <div class="d-flex align-center justify-space-between flex-wrap ga-3">
          <!-- Type Filter Chips -->
          <div class="d-flex align-center ga-1 flex-wrap">
            <v-chip
              v-for="ft in filterTypeOptions"
              :key="ft.value"
              size="small"
              filter
              :model-value="selectedTypeFilter === ft.value"
              :color="selectedTypeFilter === ft.value ? '#0d9488' : 'grey'"
              :variant="selectedTypeFilter === ft.value ? 'flat' : 'outlined'"
              class="font-weight-medium cursor-pointer"
              @click="selectedTypeFilter = ft.value"
            >
              <v-icon v-if="ft.icon" size="14" class="mr-1">{{ ft.icon }}</v-icon>
              {{ ft.label }}
              <span v-if="ft.count !== undefined" class="ml-1 opacity-70">({{ ft.count }})</span>
            </v-chip>
          </div>
          <v-chip size="small" variant="tonal" color="#00BCD4" class="font-weight-bold">
            <v-icon start size="14">mdi-chart-areaspline</v-icon>
            {{ filteredCharts.length }} {{ $t('records') || 'enregistrements' }}
          </v-chip>
        </div>
      </v-card>

      <!-- Loading State -->
      <div v-if="store.loading" class="d-flex flex-column align-center justify-center py-16">
        <v-progress-circular indeterminate color="#0d9488" size="48" class="mb-3" />
        <span class="text-caption text-medium-emphasis">{{ $t('loading') || 'Chargement des graphiques...' }}</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="!filteredCharts.length" class="empty-charts-card rounded-xl pa-12 text-center my-4">
        <div class="empty-icon-wrap mx-auto mb-3">
          <v-icon size="40" color="#0d9488">mdi-chart-line-stacked</v-icon>
        </div>
        <div class="text-h6 font-weight-bold text-high-emphasis mb-1">
          {{ searchQuery || selectedTypeFilter !== 'all' ? ($t('no_charts_found_matching') || 'Aucun graphique correspondant') : ($t('no_charts_configured') || 'Aucun graphique personnalisé') }}
        </div>
        <div class="text-caption text-medium-emphasis mb-5 max-w-md mx-auto">
          {{ searchQuery || selectedTypeFilter !== 'all' ? ($t('adjust_filters_hint') || 'Essayez d\'ajuster vos filtres de recherche') : ($t('create_first_chart_prompt') || 'Commencez par créer votre premier graphique ou utilisez un modèle prédéfini.') }}
        </div>
        <div class="d-flex align-center justify-center ga-2">
          <v-btn
            v-if="searchQuery || selectedTypeFilter !== 'all'"
            variant="outlined"
            rounded="pill"
            size="small"
            color="#0d9488"
            @click="resetFilters"
          >
            {{ $t('clear_filters') || 'Réinitialiser les filtres' }}
          </v-btn>
          <template v-else>
            <v-btn
              variant="outlined"
              color="#0d9488"
              rounded="pill"
              size="small"
              prepend-icon="mdi-creation"
              @click="activeViewTab = 'templates'"
            >
              {{ $t('quick_templates') || 'Voir les modèles' }}
            </v-btn>
            <v-btn
              color="#0d9488"
              variant="flat"
              rounded="pill"
              size="small"
              prepend-icon="mdi-plus"
              class="font-weight-bold"
              @click="startCreate"
            >
              {{ $t('create_new_chart') || 'Créer un graphique' }}
            </v-btn>
          </template>
        </div>
      </div>

      <!-- GALLERY MODE (Card Grid) -->
      <v-row v-else-if="listDisplayMode === 'gallery'" class="charts-gallery-grid">
        <v-col
          v-for="chart in filteredCharts"
          :key="chart.id"
          cols="12"
          sm="6"
          lg="4"
        >
          <v-card class="chart-gallery-card rounded-xl pa-4" elevation="0">
            <!-- Card Header -->
            <div class="d-flex align-start justify-space-between ga-2 mb-3">
              <div class="d-flex align-center ga-3 overflow-hidden">
                <div class="chart-type-avatar" :class="`type-bg--${chart.chart_type}`">
                  <v-icon size="18" :color="getTypeColor(chart.chart_type)">{{ getTypeIcon(chart.chart_type) }}</v-icon>
                </div>
                <div class="overflow-hidden">
                  <div class="font-weight-bold text-subtitle-2 text-truncate" :title="chart.title">
                    {{ chart.title }}
                  </div>
                  <div class="d-flex align-center ga-1 text-caption text-medium-emphasis">
                    <span>{{ getTypeName(chart.chart_type) }}</span>
                    <span>•</span>
                    <span>{{ getDateRangeLabel(chart.date_range_type) }}</span>
                  </div>
                </div>
              </div>

              <!-- Quick Active Switch -->
              <v-tooltip :text="chart.is_active ? ($t('active') || 'Actif') : ($t('inactive') || 'Inactif')" location="top">
                <template #activator="{ props }">
                  <v-switch
                    v-bind="props"
                    :model-value="chart.is_active"
                    color="#0d9488"
                    density="compact"
                    hide-details
                    class="ma-0 pa-0 flex-shrink-0"
                    @click.stop="toggleChartActive(chart)"
                  ></v-switch>
                </template>
              </v-tooltip>
            </div>

            <!-- Series Pills Summary -->
            <div class="series-chips-box rounded-lg pa-2 mb-3">
              <div class="text-caption font-weight-medium text-medium-emphasis mb-1">
                {{ $t('series') || 'Séries' }} ({{ (chart.series || []).length }}):
              </div>
              <div class="d-flex align-center ga-1 flex-wrap">
                <v-chip
                  v-for="(s, sIdx) in (chart.series || []).slice(0, 3)"
                  :key="sIdx"
                  size="x-small"
                  variant="tonal"
                  class="font-weight-medium"
                >
                  <span class="series-color-dot mr-1" :style="{ backgroundColor: s.color || '#0d9488' }"></span>
                  {{ s.name || `S${sIdx}` }} ({{ s.measure_unit || 'Kg' }})
                </v-chip>
                <v-chip
                  v-if="(chart.series || []).length > 3"
                  size="x-small"
                  variant="outlined"
                  class="text-caption opacity-70"
                >
                  +{{ (chart.series || []).length - 3 }}
                </v-chip>
              </div>
            </div>

            <!-- Card Bottom Bar & Actions -->
            <div class="d-flex align-center justify-space-between pt-2 border-t">
              <div class="d-flex align-center ga-1 text-caption text-medium-emphasis">
                <v-chip size="x-small" variant="outlined" class="text-caption">
                  {{ chart.grid_cols || 6 }}/12
                </v-chip>
              </div>

              <div class="d-flex align-center ga-1">
                <v-tooltip :text="$t('quick_preview') || 'Aperçu Rapide'" location="top">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-eye-outline"
                      size="x-small"
                      variant="text"
                      color="#0d9488"
                      @click="openQuickPreview(chart)"
                    ></v-btn>
                  </template>
                </v-tooltip>

                <v-tooltip :text="$t('duplicate') || 'Dupliquer'" location="top">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-content-copy"
                      size="x-small"
                      variant="text"
                      color="#0284c7"
                      @click="duplicateChart(chart)"
                    ></v-btn>
                  </template>
                </v-tooltip>

                <v-tooltip :text="$t('edit') || 'Modifier'" location="top">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-pencil-outline"
                      size="x-small"
                      variant="text"
                      color="#0d9488"
                      @click="editItem(chart)"
                    ></v-btn>
                  </template>
                </v-tooltip>

                <v-tooltip :text="$t('delete') || 'Supprimer'" location="top">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-delete-outline"
                      size="x-small"
                      variant="text"
                      color="error"
                      @click="confirmDelete(chart)"
                    ></v-btn>
                  </template>
                </v-tooltip>
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- TABLE MODE (Compact Data Table) -->
      <v-card v-else class="chart-table-card rounded-xl" elevation="0">
        <v-data-table
          :headers="headers"
          :items="filteredCharts"
          :loading="store.loading"
          :multi-sort="true"
          density="comfortable"
          hover
          class="bg-transparent rounded-xl param-table"
        >
          <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
            <tr>
              <th v-for="col in columns" :key="col.key" class="param-th">
                <div
                  class="param-th-label"
                  @click="col.sortable !== false ? toggleSort(col) : null"
                  :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
                >
                  {{ col.title }}
                  <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
                </div>
              </th>
            </tr>
            <tr class="param-filter-row">
              <th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th">
                <v-autocomplete
                  v-if="col.key !== 'actions'"
                  v-model="columnFilters[col.key]"
                  :items="getColumnOptions(col.key)"
                  density="compact"
                  variant="outlined"
                  color="#00BCD4"
                  class="param-filter-input"
                  hide-details
                  clearable
                  multiple
                  chips
                  closable-chips
                  :placeholder="$t('filter')"
                >
                  <template #chip="{ item, props }">
                    <v-chip v-bind="props" size="x-small" color="#00BCD4" variant="tonal" closable>
                      {{ item.title }}
                    </v-chip>
                  </template>
                </v-autocomplete>
              </th>
            </tr>
          </template>

          <template #item.title="{ item }">
            <div class="d-flex align-center ga-2 py-1">
              <v-avatar size="28" :color="getTypeBg(item.chart_type)">
                <v-icon size="16" :color="getTypeColor(item.chart_type)">{{ getTypeIcon(item.chart_type) }}</v-icon>
              </v-avatar>
              <div>
                <div class="font-weight-bold text-subtitle-2">{{ item.title }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.display_type === 'statistic' ? ($t('visual_type_stat') || 'Indicateur KPI') : getTypeName(item.chart_type) }}</div>
              </div>
            </div>
          </template>

          <template #item.chart_type="{ item }">
            <v-chip size="small" variant="tonal" :color="getTypeColor(item.chart_type)" class="font-weight-medium">
              {{ item.chart_type }}
            </v-chip>
          </template>

          <template #item.date_range_type="{ item }">
            <v-chip size="small" variant="outlined" class="text-caption">
              {{ getDateRangeLabel(item.date_range_type) }}
            </v-chip>
          </template>

          <template #item.series_count="{ item }">
            <v-chip size="small" color="#0d9488" variant="tonal" class="font-weight-bold">
              {{ (item.series || []).length }} {{ $t('series') || 'séries' }}
            </v-chip>
          </template>

          <template #item.is_active="{ item }">
            <v-switch
              :model-value="item.is_active"
              color="#0d9488"
              density="compact"
              hide-details
              @click.stop="toggleChartActive(item)"
            ></v-switch>
          </template>

          <template #item.actions="{ item }">
            <div class="param-actions-cell">
              <v-btn
                size="x-small"
                variant="tonal"
                color="#00BCD4"
                class="param-action-btn"
                @click="openQuickPreview(item)"
              >
                <v-icon size="16">mdi-eye-outline</v-icon>
                <v-tooltip activator="parent" location="top">{{ $t('quick_preview') || 'Aperçu' }}</v-tooltip>
              </v-btn>
              <v-btn
                size="x-small"
                variant="tonal"
                color="#0284c7"
                class="param-action-btn"
                @click="duplicateChart(item)"
              >
                <v-icon size="16">mdi-content-copy</v-icon>
                <v-tooltip activator="parent" location="top">{{ $t('duplicate') || 'Dupliquer' }}</v-tooltip>
              </v-btn>
              <v-btn
                size="x-small"
                variant="tonal"
                color="#00BCD4"
                class="param-action-btn"
                @click="editItem(item)"
              >
                <v-icon size="16">mdi-pencil-outline</v-icon>
                <v-tooltip activator="parent" location="top">{{ $t('edit') || 'Modifier' }}</v-tooltip>
              </v-btn>
              <v-btn
                size="x-small"
                variant="tonal"
                color="error"
                class="param-action-btn param-action-delete"
                @click="confirmDelete(item)"
              >
                <v-icon size="16">mdi-delete-outline</v-icon>
                <v-tooltip activator="parent" location="top">{{ $t('delete') || 'Supprimer' }}</v-tooltip>
              </v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card>
    </div>

    <!-- VIEW 2: CHART STUDIO (Create & Edit) -->
    <div v-else-if="activeViewTab === 'studio'" class="view-panel">
      <ChartForm
        :edit-chart="editingChart"
        @saved="onSaved"
        @cancel="onStudioCancel"
      />
    </div>

    <!-- VIEW 3: STARTER TEMPLATES -->
    <div v-else-if="activeViewTab === 'templates'" class="view-panel">
      <div class="templates-banner rounded-xl pa-5 mb-5 d-flex align-center justify-space-between flex-wrap ga-3">
        <div>
          <div class="text-h6 font-weight-bold text-high-emphasis mb-1">
            {{ $t('quick_templates') || 'Modèles Prédéfinis d\'Usine de Poisson' }}
          </div>
          <div class="text-caption text-medium-emphasis">
            {{ $t('quick_templates_desc') || 'Démarrez instantanément avec des analyses validées pour l\'industrie halieutique en un seul clic' }}
          </div>
        </div>
        <v-btn
          variant="outlined"
          color="#0d9488"
          size="small"
          rounded="pill"
          prepend-icon="mdi-arrow-left"
          @click="activeViewTab = 'list'"
        >
          {{ $t('back') || 'Retour à la liste' }}
        </v-btn>
      </div>

      <v-row>
        <v-col
          v-for="tpl in starterTemplates"
          :key="tpl.id"
          cols="12"
          sm="6"
          lg="3"
        >
          <v-card class="template-card rounded-xl pa-4 d-flex flex-column h-100" elevation="0">
            <div class="d-flex align-center ga-3 mb-3">
              <div class="template-icon-wrap" :style="{ backgroundColor: tpl.accentBg }">
                <v-icon :color="tpl.accentColor" size="22">{{ tpl.icon }}</v-icon>
              </div>
              <div>
                <v-chip size="x-small" :color="tpl.accentColor" variant="tonal" class="font-weight-bold text-uppercase">
                  {{ tpl.badge }}
                </v-chip>
              </div>
            </div>

            <div class="font-weight-bold text-subtitle-2 mb-1">{{ tpl.title }}</div>
            <div class="text-caption text-medium-emphasis mb-4 flex-grow-1">{{ tpl.desc }}</div>

            <div class="template-meta-strip rounded-lg pa-2 mb-3 d-flex align-center justify-space-between text-caption text-medium-emphasis">
              <span>{{ tpl.chart_type.toUpperCase() }}</span>
              <span>{{ tpl.series.length }} {{ $t('series') || 'séries' }}</span>
              <span>{{ tpl.grid_cols }}/12</span>
            </div>

            <v-btn
              block
              color="#0d9488"
              variant="flat"
              rounded="pill"
              size="small"
              class="font-weight-bold"
              prepend-icon="mdi-flash"
              @click="applyTemplate(tpl)"
            >
              {{ $t('use_template') || 'Utiliser ce modèle' }}
            </v-btn>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- QUICK PREVIEW MODAL -->
    <v-dialog v-model="previewModal.show" max-width="840" scrollable>
      <v-card class="param-dialog-card">
        <div class="param-dialog-header">
          <div class="d-flex align-center ga-3">
            <div class="param-dialog-icon-badge" style="background: rgba(0, 188, 212, 0.12); color: #00BCD4;">
              <v-icon size="20">mdi-chart-box-outline</v-icon>
            </div>
            <div>
              <div class="param-dialog-title">{{ previewModal.chart?.title }}</div>
              <div class="param-dialog-subtitle">{{ previewModal.chart?.series?.length }} {{ $t('series') || 'séries' }} • {{ previewModal.chart?.chart_type }}</div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" size="small" density="comfortable" @click="previewModal.show = false"></v-btn>
        </div>

        <v-card-text class="param-dialog-body pa-4">
          <div v-if="previewModal.loading" class="d-flex flex-column align-center justify-center py-12">
            <v-progress-circular indeterminate color="#00BCD4" size="40" class="mb-3" />
            <span class="text-caption text-medium-emphasis">{{ $t('loading') || 'Chargement des données...' }}</span>
          </div>
          <template v-else>
            <ChartPreview
              :chart-config="previewModal.chart"
              :categories="previewModal.categories"
              :series="previewModal.series"
              :height="380"
            />
          </template>
        </v-card-text>

        <v-card-actions class="param-dialog-actions">
          <v-btn
            variant="tonal"
            color="grey"
            rounded="lg"
            @click="previewModal.show = false"
          >
            {{ $t('close') || 'Fermer' }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            color="#00BCD4"
            rounded="lg"
            class="text-white"
            prepend-icon="mdi-pencil-outline"
            @click="editItemFromPreview"
          >
            {{ $t('edit') || 'Modifier' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- DELETE CONFIRMATION MODAL -->
    <v-dialog v-model="deleteDialog" max-width="440" persistent>
      <v-card class="param-dialog-card">
        <div class="param-dialog-header">
          <div class="d-flex align-center ga-3">
            <div class="param-dialog-icon-badge" style="background: rgba(244, 67, 54, 0.12); color: #F44336;">
              <v-icon size="20">mdi-alert-circle</v-icon>
            </div>
            <div>
              <div class="param-dialog-title">{{ $t('confirm_delete') || 'Confirmer la suppression' }}</div>
              <div class="param-dialog-subtitle">{{ $t('cannot_undo_action') || 'Action irréversible' }}</div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" size="small" density="comfortable" @click="deleteDialog = false"></v-btn>
        </div>
        <v-card-text class="param-dialog-body pt-2">
          <p class="text-body-2 mb-2">{{ $t('confirm_delete_chart_message') || 'Êtes-vous sûr de vouloir supprimer ce graphique ?' }}</p>
          <div v-if="deletingItem" class="pa-3 rounded-lg" style="background: rgba(244,67,54,.06); border: 1px dashed rgba(244,67,54,.25);">
            <strong class="text-subtitle-2 font-weight-bold">{{ deletingItem.title }}</strong>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ getTypeName(deletingItem.chart_type) }} • {{ (deletingItem.series || []).length }} {{ $t('series') || 'séries' }}
            </div>
          </div>
        </v-card-text>
        <v-card-actions class="param-dialog-actions">
          <v-btn variant="tonal" color="grey" rounded="lg" @click="deleteDialog = false">
            {{ $t('cancel') || 'Annuler' }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            variant="flat"
            rounded="lg"
            :loading="deleting"
            prepend-icon="mdi-delete"
            @click="doDelete"
          >
            {{ $t('delete') || 'Supprimer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- SUMMARY SNACKBAR (Rule 5 Compliant) -->
    <v-snackbar
      v-model="snackbar.show"
      :timeout="4000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="320"
    >
      <div class="d-flex align-center">
        <v-icon :color="snackbar.color" class="mr-2">
          {{ snackbar.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
        </v-icon>
        <span class="font-weight-medium text-subtitle-2">{{ snackbar.text }}</span>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false" />
      </template>
    </v-snackbar>
  </div>
</template>

<script>
import { useCustomChart } from '@/stores/CustomChart';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import ChartForm from './ChartForm.vue';
import ChartPreview from './ChartPreview.vue';

export default {
  name: 'ChartManagement',
  components: {
    SegmentedPillToggle,
    ChartForm,
    ChartPreview,
  },
  data() {
    return {
      activeViewTab: 'list', // 'list' | 'studio' | 'templates'
      listDisplayMode: 'gallery', // 'gallery' | 'table'
      searchQuery: '',
      selectedTypeFilter: 'all',
      columnFilters: {},
      editingChart: null,
      deleteDialog: false,
      deleting: false,
      deletingItem: null,
      snackbar: { show: false, text: '', color: 'success' },
      previewModal: {
        show: false,
        loading: false,
        chart: null,
        categories: [],
        series: [],
      },
    };
  },
  computed: {
    store() { return useCustomChart(); },
    charts() { return this.store.sortedCharts || []; },

    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(v => Array.isArray(v) ? v.length > 0 : !!v);
    },

    activeChartsCount() {
      return this.charts.filter(c => c.is_active).length;
    },

    totalSeriesCount() {
      return this.charts.reduce((acc, c) => acc + (c.series?.length || 0), 0);
    },

    activeSourcesCount() {
      const sources = new Set();
      this.charts.forEach(c => {
        (c.series || []).forEach(s => {
          if (s.data_source) sources.add(s.data_source);
        });
      });
      return sources.size;
    },

    typeDistributionSummary() {
      const counts = {};
      this.charts.forEach(c => {
        counts[c.chart_type] = (counts[c.chart_type] || 0) + 1;
      });
      const topType = Object.entries(counts).sort((a, b) => b[1] - a[1])[0];
      return topType ? `${topType[0].toUpperCase()} (${topType[1]})` : 'Visualisations';
    },

    navPillItems() {
      return [
        {
          value: 'list',
          label: this.$t('charts_analytics_list') || 'Visualisations',
          icon: 'mdi-view-dashboard-outline',
          badge: this.charts.length,
        },
        {
          value: 'studio',
          label: this.editingChart ? (this.$t('editing_chart') || 'Modification') : (this.$t('chart_studio') || 'Studio Graphique'),
          icon: 'mdi-pencil-ruler',
        },
        {
          value: 'templates',
          label: this.$t('quick_templates') || 'Modèles Prédéfinis',
          icon: 'mdi-creation',
        },
      ];
    },

    filterTypeOptions() {
      const counts = { all: this.charts.length };
      this.charts.forEach(c => {
        counts[c.chart_type] = (counts[c.chart_type] || 0) + 1;
      });

      return [
        { value: 'all', label: this.$t('filter_all_types') || 'Tous', count: counts.all },
        { value: 'bar', label: this.$t('visual_type_bar') || 'Barres', icon: 'mdi-chart-bar', count: counts.bar || 0 },
        { value: 'line', label: this.$t('visual_type_line') || 'Courbe', icon: 'mdi-chart-line', count: counts.line || 0 },
        { value: 'area', label: this.$t('visual_type_area') || 'Aires', icon: 'mdi-chart-areaspline', count: counts.area || 0 },
        { value: 'donut', label: this.$t('visual_type_donut') || 'Donut', icon: 'mdi-chart-donut', count: counts.donut || 0 },
        { value: 'mixed', label: this.$t('visual_type_mixed') || 'Mixte', icon: 'mdi-chart-multiline', count: counts.mixed || 0 },
      ];
    },

    filteredCharts() {
      let list = this.charts;

      if (this.selectedTypeFilter !== 'all') {
        list = list.filter(c => c.chart_type === this.selectedTypeFilter);
      }

      if (this.searchQuery && this.searchQuery.trim()) {
        const q = this.searchQuery.toLowerCase().trim();
        list = list.filter(c => {
          const matchTitle = (c.title || '').toLowerCase().includes(q);
          const matchType = (c.chart_type || '').toLowerCase().includes(q);
          const matchSeries = (c.series || []).some(s =>
            (s.name || '').toLowerCase().includes(q) ||
            (s.data_source || '').toLowerCase().includes(q)
          );
          return matchTitle || matchType || matchSeries;
        });
      }

      if (this.hasActiveColumnFilters) {
        list = list.filter(c => {
          for (const [key, filterValues] of Object.entries(this.columnFilters)) {
            if (!filterValues || (Array.isArray(filterValues) && filterValues.length === 0)) continue;
            let val = '';
            if (key === 'title') val = c.title || '';
            else if (key === 'chart_type') val = c.chart_type || '';
            else if (key === 'date_range_type') val = this.getDateRangeLabel(c.date_range_type);
            else if (key === 'series_count') val = `${(c.series || []).length} ${this.$t('series') || 'séries'}`;
            else if (key === 'is_active') val = c.is_active ? (this.$t('active') || 'Actif') : (this.$t('inactive') || 'Inactif');
            else val = String(c[key] || '');

            if (Array.isArray(filterValues)) {
              if (!filterValues.includes(val)) return false;
            } else if (typeof filterValues === 'string') {
              if (!val.toLowerCase().includes(filterValues.toLowerCase())) return false;
            }
          }
          return true;
        });
      }

      return list;
    },

    headers() {
      return [
        { title: this.$t('chart_title') || 'Titre', key: 'title', sortable: true },
        { title: this.$t('chart_type') || 'Type', key: 'chart_type', sortable: true },
        { title: this.$t('date_range_type') || 'Période', key: 'date_range_type' },
        { title: this.$t('series') || 'Séries', key: 'series_count' },
        { title: this.$t('status') || 'Statut', key: 'is_active' },
        { title: this.$t('actions') || 'Actions', key: 'actions', sortable: false, align: 'center', width: '150px' },
      ];
    },

    starterTemplates() {
      return [
        {
          id: 'tpl_prod_shipping',
          title: this.$t('template_prod_shipping_title') || 'Flux Production vs Expéditions',
          desc: this.$t('template_prod_shipping_desc') || 'Comparez la production mensuelle aux expéditions d\'exportation',
          icon: 'mdi-chart-multiline',
          badge: 'Mixte',
          accentColor: '#0d9488',
          accentBg: 'rgba(13, 148, 136, 0.1)',
          chart_type: 'mixed',
          display_type: 'chart',
          date_range_type: 'dynamic',
          grid_cols: 12,
          is_active: true,
          series: [
            {
              name: 'Production Usine (Kg)',
              series_type: 'column',
              measure_unit: 'Kg',
              color: '#0d9488',
              data_source: 'productions',
              aggregate_field: 'total_weight',
              aggregate_function: 'sum',
              group_by: 'month',
              filters: {},
            },
            {
              name: 'Expéditions Réalisées (Kg)',
              series_type: 'line',
              measure_unit: 'Kg',
              color: '#0284c7',
              data_source: 'shippings',
              aggregate_field: 'total_weight',
              aggregate_function: 'sum',
              group_by: 'month',
              filters: {},
            },
          ],
        },
        {
          id: 'tpl_fish_cost',
          title: this.$t('template_fish_cost_title') || 'Achats Matière Première (Poids & Coûts)',
          desc: this.$t('template_fish_cost_desc') || 'Suivez le tonnage de poisson brut acheté et les dépenses globales',
          icon: 'mdi-cart-outline',
          badge: 'Volumes & Coûts',
          accentColor: '#2563eb',
          accentBg: 'rgba(37, 99, 235, 0.1)',
          chart_type: 'mixed',
          display_type: 'chart',
          date_range_type: 'dynamic',
          grid_cols: 6,
          is_active: true,
          series: [
            {
              name: 'Poids Acheté (Kg)',
              series_type: 'column',
              measure_unit: 'Kg',
              color: '#2563eb',
              data_source: 'charges',
              aggregate_field: 'total_weight',
              aggregate_function: 'sum',
              group_by: 'month',
              filters: {},
            },
            {
              name: 'Montant Total (DH)',
              series_type: 'line',
              measure_unit: 'DH',
              color: '#d97706',
              data_source: 'charges',
              aggregate_field: 'total_price',
              aggregate_function: 'sum',
              group_by: 'month',
              filters: {},
            },
          ],
        },
        {
          id: 'tpl_cold_room',
          title: this.$t('template_cold_room_title') || 'Solde des Chambres Froides',
          desc: this.$t('template_cold_room_desc') || 'Répartition en temps réel du stock dans les chambres frigorifiques',
          icon: 'mdi-snowflake',
          badge: 'Stock & Froid',
          accentColor: '#7c3aed',
          accentBg: 'rgba(124, 58, 237, 0.1)',
          chart_type: 'bar',
          display_type: 'chart',
          date_range_type: 'all',
          grid_cols: 6,
          is_active: true,
          series: [
            {
              name: 'Stock Restant (Kg)',
              series_type: 'column',
              measure_unit: 'Kg',
              color: '#7c3aed',
              data_source: 'warehouses',
              aggregate_field: 'remaining_weight',
              aggregate_function: 'sum',
              group_by: 'month',
              filters: {},
            },
          ],
        },
        {
          id: 'tpl_workforce',
          title: this.$t('template_workforce_title') || 'Coûts Quinzaine Main-d\'œuvre',
          desc: this.$t('template_workforce_desc') || 'Évolution des charges de personnel par section et quinzaine',
          icon: 'mdi-account-group-outline',
          badge: 'RH & Quinzaine',
          accentColor: '#059669',
          accentBg: 'rgba(5, 150, 105, 0.1)',
          chart_type: 'area',
          display_type: 'chart',
          date_range_type: 'dynamic',
          grid_cols: 6,
          is_active: true,
          series: [
            {
              name: 'Rémunérations Quinzaine (DH)',
              series_type: 'area',
              measure_unit: 'DH',
              color: '#059669',
              data_source: 'fortnight_activities',
              aggregate_field: 'price',
              aggregate_function: 'sum',
              group_by: 'month',
              filters: {},
            },
          ],
        },
      ];
    },
  },
  mounted() {
    this.fetchCharts();
    this.initWebSocket();
  },
  methods: {
    async fetchCharts() {
      await this.store.fetch();
    },

    initWebSocket() {
      // Connect to WebSocket model events for live real-time synchronization
      if (window.Echo) {
        window.Echo.channel('jupiter-app')
          .listen('.CustomChart', (e) => {
            if (e && e.action) {
              this.store.handleWsUpdate(e);
              this.snackbar = {
                show: true,
                text: `${this.$t('chart_updated_success') || 'Données synchronisées'}: ${e.action}`,
                color: 'success',
              };
            }
          });
      }
    },

    startCreate() {
      this.editingChart = null;
      this.activeViewTab = 'studio';
    },

    getColumnOptions(key) {
      const set = new Set();
      this.charts.forEach(c => {
        let v = '';
        if (key === 'title') v = c.title || '';
        else if (key === 'chart_type') v = c.chart_type || '';
        else if (key === 'date_range_type') v = this.getDateRangeLabel(c.date_range_type);
        else if (key === 'series_count') v = `${(c.series || []).length} ${this.$t('series') || 'séries'}`;
        else if (key === 'is_active') v = c.is_active ? (this.$t('active') || 'Actif') : (this.$t('inactive') || 'Inactif');
        else v = c[key];
        if (v !== undefined && v !== null && v !== '') set.add(String(v));
      });
      return Array.from(set).sort();
    },

    clearAllColumnFilters() {
      this.columnFilters = {};
    },

    editItem(item) {
      this.editingChart = JSON.parse(JSON.stringify(item));
      this.activeViewTab = 'studio';
    },

    editItemFromPreview() {
      if (this.previewModal.chart) {
        this.editItem(this.previewModal.chart);
        this.previewModal.show = false;
      }
    },

    applyTemplate(tpl) {
      this.editingChart = {
        title: tpl.title,
        chart_type: tpl.chart_type,
        display_type: tpl.display_type,
        date_range_type: tpl.date_range_type,
        grid_cols: tpl.grid_cols,
        is_active: true,
        series: tpl.series,
      };
      this.activeViewTab = 'studio';
    },

    onSaved() {
      this.editingChart = null;
      this.activeViewTab = 'list';
      this.snackbar = {
        show: true,
        text: this.$t('chart_saved_success') || 'Graphique enregistré avec succès',
        color: 'success',
      };
    },

    onStudioCancel() {
      this.editingChart = null;
      this.activeViewTab = 'list';
    },

    async duplicateChart(item) {
      try {
        await this.store.duplicate(item.id);
        this.snackbar = {
          show: true,
          text: this.$t('chart_duplicated_success') || 'Graphique dupliqué avec succès',
          color: 'success',
        };
      } catch {
        this.snackbar = { show: true, text: 'Erreur lors de la duplication', color: 'error' };
      }
    },

    async toggleChartActive(item) {
      try {
        await this.store.toggleActive(item.id);
        this.snackbar = {
          show: true,
          text: this.$t('chart_status_updated') || 'Statut actualisé',
          color: 'success',
        };
      } catch {
        this.snackbar = { show: true, text: 'Erreur', color: 'error' };
      }
    },

    confirmDelete(item) {
      this.deletingItem = item;
      this.deleteDialog = true;
    },

    async doDelete() {
      this.deleting = true;
      try {
        await this.store.destroy(this.deletingItem.id);
        this.snackbar = {
          show: true,
          text: this.$t('chart_deleted_success') || 'Graphique supprimé avec succès',
          color: 'success',
        };
      } catch {
        this.snackbar = { show: true, text: 'Erreur lors de la suppression', color: 'error' };
      }
      this.deleting = false;
      this.deleteDialog = false;
      this.deletingItem = null;
    },

    async openQuickPreview(chart) {
      this.previewModal.chart = chart;
      this.previewModal.show = true;
      this.previewModal.loading = true;

      try {
        const result = await this.store.fetchChartData(chart.id, true);
        if (result?.categories?.length) {
          this.previewModal.categories = result.categories;
          this.previewModal.series = result.series;
          this.previewModal.loading = false;
          return;
        }
      } catch {
        /* fallback to sample */
      }

      // Sample preview
      this.previewModal.categories = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'];
      this.previewModal.series = (chart.series || []).map((s, i) => ({
        name: s.name || `Série ${i + 1}`,
        type: s.series_type || 'column',
        color: s.color || '#0d9488',
        measure_unit: s.measure_unit || 'Kg',
        data: [1200, 1500, 1300, 1800, 1600, 2100].map(v => Math.round(v * (1 + i * 0.3))),
      }));
      this.previewModal.loading = false;
    },

    resetFilters() {
      this.searchQuery = '';
      this.selectedTypeFilter = 'all';
    },

    getTypeIcon(type) {
      const map = {
        bar: 'mdi-chart-bar',
        line: 'mdi-chart-line',
        area: 'mdi-chart-areaspline',
        donut: 'mdi-chart-donut',
        pie: 'mdi-chart-pie',
        mixed: 'mdi-chart-multiline',
        radar: 'mdi-spider-web',
        statistic: 'mdi-numeric-1-box-outline',
      };
      return map[type] || 'mdi-chart-box-outline';
    },

    getTypeName(type) {
      const map = {
        bar: this.$t('visual_type_bar') || 'Barres',
        line: this.$t('visual_type_line') || 'Courbe',
        area: this.$t('visual_type_area') || 'Aires',
        donut: this.$t('visual_type_donut') || 'Donut',
        pie: this.$t('visual_type_pie') || 'Camembert',
        mixed: this.$t('visual_type_mixed') || 'Mixte',
        radar: 'Radar',
        statistic: this.$t('visual_type_stat') || 'KPI',
      };
      return map[type] || type;
    },

    getTypeColor(type) {
      const map = {
        bar: '#0d9488',
        line: '#0284c7',
        area: '#059669',
        donut: '#7c3aed',
        pie: '#e11d48',
        mixed: '#d97706',
        radar: '#4f46e5',
        statistic: '#0d9488',
      };
      return map[type] || '#0d9488';
    },

    getTypeBg(type) {
      const color = this.getTypeColor(type);
      return `${color}18`;
    },

    getDateRangeLabel(rangeType) {
      const map = {
        dynamic: this.$t('dynamic_range') || 'Dynamique',
        fixed_month: this.$t('fixed_month') || 'Mois Spécifique',
        fixed_year: this.$t('fixed_year') || 'Année Fiscale',
        all: this.$t('all_dates') || 'Tout l\'historique',
      };
      return map[rangeType] || rangeType;
    },
  },
};
</script>

<style scoped>
.chart-management-page {
  font-family: 'Inter', sans-serif;
}

/* Hero Header (Rolled back to previous design & color) */
.cm-hero {
  background: linear-gradient(135deg, #0097A7 0%, #00BCD4 40%, #4DD0E1 100%);
  border-radius: 20px;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  height: 90px;
}
.cm-hero-inner {
  position: relative;
  z-index: 1;
  padding: 20px 24px;
  color: #fff;
}
.cm-hero-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.15);
}
.cm-hero-title {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: 0.3px;
}

/* Pulse Dot for Realtime */
.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #4ade80;
  display: inline-block;
  box-shadow: 0 0 0 2px rgba(74, 222, 128, 0.4);
  animation: pulse-ring 2s infinite;
}
@keyframes pulse-ring {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(74, 222, 128, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
}

/* KPI Tiles */
.kpi-metric-tile {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: 0 2px 10px -2px rgba(0,0,0,0.03);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kpi-metric-tile:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px -4px rgba(0, 188, 212, 0.08);
}
.kpi-tile-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bg-cyan-subtle { background: rgba(0, 188, 212, 0.1); }
.bg-sky-subtle { background: rgba(2, 132, 199, 0.1); }
.bg-indigo-subtle { background: rgba(79, 70, 229, 0.1); }
.bg-amber-subtle { background: rgba(217, 119, 6, 0.1); }

/* Filter Toolbar */
.filter-toolbar-card {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

/* Chart Gallery Card */
.chart-gallery-card {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: 0 4px 16px -2px rgba(0,0,0,0.04);
  transition: all 0.25s ease;
}
.chart-gallery-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px -4px rgba(13, 148, 136, 0.1);
  border-color: rgba(13, 148, 136, 0.3);
}
.chart-type-avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.type-bg--bar { background: rgba(13, 148, 136, 0.1); }
.type-bg--line { background: rgba(2, 132, 199, 0.1); }
.type-bg--area { background: rgba(5, 150, 105, 0.1); }
.type-bg--donut { background: rgba(124, 58, 237, 0.1); }
.type-bg--pie { background: rgba(225, 29, 72, 0.1); }
.type-bg--mixed { background: rgba(217, 119, 6, 0.1); }

.series-chips-box {
  background: rgba(var(--v-theme-on-surface), 0.02);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.05);
}
.series-color-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

/* Chart Table Card */
.chart-table-card {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

/* Templates Section */
.templates-banner {
  background: linear-gradient(135deg, rgba(13, 148, 136, 0.08) 0%, rgba(2, 132, 199, 0.08) 100%);
  border: 1px solid rgba(13, 148, 136, 0.2);
}
.template-card {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: 0 4px 16px -2px rgba(0,0,0,0.04);
  transition: all 0.2s ease;
}
.template-card:hover {
  transform: translateY(-3px);
  border-color: #0d9488;
  box-shadow: 0 8px 24px -4px rgba(13, 148, 136, 0.12);
}
.template-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.template-meta-strip {
  background: rgba(var(--v-theme-on-surface), 0.025);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.05);
}

/* Empty State */
.empty-charts-card {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px dashed rgba(var(--v-theme-on-surface), 0.15);
}
.empty-icon-wrap {
  width: 72px;
  height: 72px;
  border-radius: 20px;
  background: rgba(13, 148, 136, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
}
.param-table { border: 1px solid rgba(0,188,212,.15); }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; cursor: pointer; }
.param-filter-row th { padding: 4px 6px 8px !important; }
.param-filter-input :deep(.v-field) { border-radius: 8px !important; font-size: 11px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 28px !important; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
.kpi-tile-val,
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
</style>
