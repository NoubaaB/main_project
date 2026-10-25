<template>
  <div class="chart-studio-wrap">
    <!-- Studio Sub-Header (Clean, calm, single-tier, NO double hero banner!) -->
    <div class="studio-header d-flex align-center justify-space-between flex-wrap ga-3 mb-4">
      <div class="d-flex align-center ga-3">
        <v-btn
          variant="tonal"
          color="#0d9488"
          size="small"
          rounded="pill"
          prepend-icon="mdi-arrow-left"
          @click="$emit('cancel')"
        >
          {{ $t('charts_list') || 'Liste des graphiques' }}
        </v-btn>
        <div>
          <div class="d-flex align-center ga-2">
            <span class="text-h6 font-weight-bold text-high-emphasis">
              {{ editing ? ($t('editing_chart') || 'Modifier le graphique') : ($t('create_new_chart') || 'Nouveau graphique') }}
            </span>
            <v-chip size="x-small" :color="editing ? '#0284c7' : '#0d9488'" variant="tonal" class="font-weight-bold">
              {{ editing ? `#${editChart?.id}` : ($t('chart_studio') || 'Studio') }}
            </v-chip>
          </div>
          <div class="text-caption text-medium-emphasis">
            {{ form.title || ($t('chart_builder_desc') || 'Configurez votre visualisation personnalisée') }}
          </div>
        </div>
      </div>

      <!-- Header actions & Split Preview Toggle -->
      <div class="d-flex align-center ga-2 flex-wrap">
        <v-btn
          v-if="$vuetify.display.lgAndUp"
          size="small"
          rounded="pill"
          :variant="showSplitPreview ? 'flat' : 'outlined'"
          :color="showSplitPreview ? '#0d9488' : 'grey'"
          class="font-weight-medium text-capitalize"
          prepend-icon="mdi-view-split-vertical"
          @click="showSplitPreview = !showSplitPreview"
        >
          {{ $t('side_by_side_preview') || 'Aperçu Simultané' }}
        </v-btn>

        <v-btn
          variant="outlined"
          color="grey"
          size="small"
          rounded="pill"
          @click="resetForm"
        >
          {{ $t('clear') || 'Réinitialiser' }}
        </v-btn>

        <v-btn
          color="#0d9488"
          variant="flat"
          size="small"
          rounded="pill"
          :loading="saving"
          prepend-icon="mdi-content-save-outline"
          class="font-weight-bold px-4"
          @click="save"
        >
          {{ editing ? ($t('update') || 'Mettre à jour') : ($t('save') || 'Enregistrer') }}
        </v-btn>
      </div>
    </div>

    <!-- Stepper Navigation Pills -->
    <div class="stepper-nav-bar mb-5">
      <div class="stepper-track d-flex align-center justify-space-between ga-2">
        <button
          v-for="s in steps"
          :key="s.step"
          type="button"
          class="step-pill-btn"
          :class="{
            'is-active': step === s.step,
            'is-completed': step > s.step,
          }"
          @click="goToStep(s.step)"
        >
          <div class="step-pill-num">
            <v-icon v-if="step > s.step" size="14">mdi-check</v-icon>
            <span v-else>{{ s.step }}</span>
          </div>
          <div class="step-pill-text text-start">
            <div class="step-pill-title">{{ s.title }}</div>
            <div class="step-pill-sub d-none d-sm-block">{{ s.sub }}</div>
          </div>
        </button>
      </div>
    </div>

    <!-- Main Workspace (Grid for Optional Split Preview) -->
    <v-row>
      <!-- LEFT: Form Configuration -->
      <v-col cols="12" :lg="showSplitPreview && $vuetify.display.lgAndUp ? 7 : 12">
        <v-card class="form-canvas-card rounded-xl pa-5" elevation="0">
          <!-- STEP 1: Canvas & Identity -->
          <div v-show="step === 1" class="step-content">
            <div class="section-title-wrap mb-4">
              <div class="d-flex align-center ga-2">
                <v-icon color="#0d9488" size="20">mdi-palette-swatch-outline</v-icon>
                <span class="text-subtitle-1 font-weight-bold">{{ $t('step_identity') || 'Identité & Canevas' }}</span>
              </div>
              <div class="text-caption text-medium-emphasis">{{ $t('step_identity_desc') || 'Nom du graphique, type visuel et disposition dans le tableau de bord' }}</div>
            </div>

            <!-- Title & Display Type -->
            <v-row dense class="mb-3" align="center">
              <v-col cols="12" md="7">
                <v-text-field
                  v-model="form.title"
                  :label="$t('chart_title') || 'Titre du graphique'"
                  placeholder="Ex: Production vs Expéditions Mensuelles"
                  prepend-inner-icon="mdi-format-title"
                  variant="outlined"
                  rounded="lg"
                  density="compact"
                  color="#0d9488"
                  hide-details="auto"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="5" class="d-flex align-center">
                <SegmentedPillToggle
                  v-model="form.display_type"
                  :items="displayTypeOptions"
                  color="#0d9488"
                  size="medium"
                  block
                />
              </v-col>
            </v-row>

            <!-- Visual Chart Type Cards (if display_type === 'chart') -->
            <div v-if="form.display_type === 'chart'" class="mb-4">
              <div class="text-caption font-weight-bold text-medium-emphasis mb-2">
                {{ $t('chart_type') || 'Type de graphique' }}
              </div>
              <v-row dense>
                <v-col
                  v-for="t in chartTypeCards"
                  :key="t.value"
                  cols="6"
                  sm="6"
                  md="4"
                  lg="3"
                >
                  <div
                    class="visual-type-card cursor-pointer"
                    :class="{ 'is-selected': form.chart_type === t.value }"
                    @click="selectChartType(t.value)"
                  >
                    <div class="d-flex align-center justify-space-between mb-1">
                      <v-icon :color="form.chart_type === t.value ? '#0d9488' : 'grey'" size="22">{{ t.icon }}</v-icon>
                      <v-icon v-if="form.chart_type === t.value" color="#0d9488" size="16">mdi-check-circle</v-icon>
                    </div>
                    <div class="type-name font-weight-bold">{{ t.title }}</div>
                    <div class="type-desc text-caption text-medium-emphasis">{{ t.desc }}</div>
                  </div>
                </v-col>
              </v-row>
            </div>

            <!-- Grid Layout & Toggles Card -->
            <div class="layout-controls-card rounded-xl pa-4 mt-3 border">
              <v-row dense align="center">
                <v-col cols="12" md="6">
                  <div class="d-flex align-center justify-space-between mb-1">
                    <span class="text-caption font-weight-bold">{{ $t('grid_columns') || 'Largeur de grille' }}</span>
                    <v-chip size="x-small" color="#0d9488" variant="tonal" class="font-weight-bold">
                      {{ form.grid_cols }}/12 ({{ Math.round((form.grid_cols / 12) * 100) }}%)
                    </v-chip>
                  </div>
                  <div class="d-flex align-center ga-1 pt-1">
                    <v-btn
                      v-for="g in [3, 4, 6, 8, 12]"
                      :key="g"
                      size="x-small"
                      rounded="lg"
                      :variant="form.grid_cols === g ? 'flat' : 'outlined'"
                      :color="form.grid_cols === g ? '#0d9488' : 'grey'"
                      class="flex-grow-1"
                      @click="form.grid_cols = g"
                    >
                      {{ g === 12 ? '100%' : `${g}/12` }}
                    </v-btn>
                  </div>
                </v-col>

                <v-col cols="6" md="3">
                  <v-switch
                    v-if="['bar', 'mixed'].includes(form.chart_type)"
                    v-model="chartOptions.stacked"
                    :label="$t('stacked') || 'Empilé'"
                    color="#0d9488"
                    density="compact"
                    hide-details
                  ></v-switch>
                </v-col>

                <v-col cols="6" md="3">
                  <v-switch
                    v-model="form.is_active"
                    :label="$t('is_active') || 'Actif'"
                    color="#0d9488"
                    density="compact"
                    hide-details
                  ></v-switch>
                </v-col>
              </v-row>
            </div>
          </div>

          <!-- STEP 2: Time Horizon -->
          <div v-show="step === 2" class="step-content">
            <div class="section-title-wrap mb-4">
              <div class="d-flex align-center ga-2">
                <v-icon color="#0d9488" size="20">mdi-calendar-range</v-icon>
                <span class="text-subtitle-1 font-weight-bold">{{ $t('step_dates') || 'Horizon Temporel' }}</span>
              </div>
              <div class="text-caption text-medium-emphasis">{{ $t('step_dates_desc') || 'Définissez si les données suivent le filtre dynamique du tableau ou une période fixe' }}</div>
            </div>

            <!-- Date Range Selector Cards -->
            <v-row dense class="mb-4">
              <v-col
                v-for="dr in dateRangeCards"
                :key="dr.value"
                cols="12"
                sm="6"
              >
                <div
                  class="date-mode-card cursor-pointer"
                  :class="{ 'is-selected': form.date_range_type === dr.value }"
                  @click="form.date_range_type = dr.value"
                >
                  <div class="d-flex align-center justify-space-between mb-2">
                    <div class="d-flex align-center ga-2">
                      <v-avatar size="28" :color="form.date_range_type === dr.value ? 'rgba(13, 148, 136, 0.15)' : 'rgba(var(--v-theme-on-surface), 0.05)'">
                        <v-icon :color="form.date_range_type === dr.value ? '#0d9488' : 'grey'" size="16">{{ dr.icon }}</v-icon>
                      </v-avatar>
                      <span class="font-weight-bold text-subtitle-2">{{ dr.title }}</span>
                    </div>
                    <v-icon v-if="form.date_range_type === dr.value" color="#0d9488" size="18">mdi-radiobox-marked</v-icon>
                    <v-icon v-else color="grey" size="18">mdi-radiobox-blank</v-icon>
                  </div>
                  <div class="text-caption text-medium-emphasis">{{ dr.desc }}</div>
                </div>
              </v-col>
            </v-row>

            <!-- Specific Month / Year Controls -->
            <div v-if="form.date_range_type === 'fixed_month' || form.date_range_type === 'fixed_year'" class="period-picker-box rounded-xl pa-4 mt-2 border">
              <v-row dense>
                <v-col v-if="form.date_range_type === 'fixed_month'" cols="12" sm="6">
                  <v-select
                    v-model="form.fixed_month"
                    :items="months"
                    :label="$t('month') || 'Mois'"
                    prepend-inner-icon="mdi-calendar-month"
                    variant="outlined"
                    rounded="lg"
                    density="compact"
                    color="#0d9488"
                    hide-details
                  ></v-select>
                </v-col>
                <v-col cols="12" :sm="form.date_range_type === 'fixed_month' ? 6 : 12">
                  <v-select
                    v-model="form.fixed_year"
                    :items="years"
                    :label="$t('year') || 'Année'"
                    prepend-inner-icon="mdi-calendar-blank"
                    variant="outlined"
                    rounded="lg"
                    density="compact"
                    color="#0d9488"
                    hide-details
                  ></v-select>
                </v-col>
              </v-row>
            </div>

            <!-- Dynamic Range Calm Alert -->
            <v-alert
              v-if="form.date_range_type === 'dynamic'"
              variant="tonal"
              color="#0d9488"
              density="compact"
              rounded="lg"
              icon="mdi-sync"
              class="text-body-2 mt-2"
            >
              {{ $t('dynamic_range_dashboard_hint') || 'Le graphique s\'actualisera automatiquement selon la période sélectionnée dans le sélecteur du tableau de bord.' }}
            </v-alert>
          </div>

          <!-- STEP 3: Series & Data Pipelines -->
          <div v-show="step === 3" class="step-content">
            <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-4">
              <div>
                <div class="d-flex align-center ga-2">
                  <v-icon color="#0d9488" size="20">mdi-database-cog-outline</v-icon>
                  <span class="text-subtitle-1 font-weight-bold">{{ $t('step_series') || 'Séries & Flux de Données' }} ({{ form.series.length }})</span>
                </div>
                <div class="text-caption text-medium-emphasis">{{ $t('step_series_desc') || 'Connectez vos sources, sélectionnez vos métriques ou créez des formules calculées' }}</div>
              </div>
              <v-btn
                variant="tonal"
                color="#0d9488"
                size="small"
                rounded="pill"
                prepend-icon="mdi-plus"
                class="font-weight-medium"
                @click="addSeries"
              >
                {{ $t('add_series') || 'Ajouter une série' }}
              </v-btn>
            </div>

            <!-- Expansion Panels for Series -->
            <v-expansion-panels v-model="openSeriesPanel" variant="accordion" class="series-panels-group">
              <v-expansion-panel
                v-for="(s, idx) in form.series"
                :key="idx"
                class="series-panel-card mb-2"
              >
                <v-expansion-panel-title class="py-2 px-3">
                  <div class="d-flex align-center ga-2 flex-grow-1 overflow-hidden pr-2">
                    <span
                      class="series-color-dot"
                      :style="{ backgroundColor: s.color || '#0d9488' }"
                    ></span>
                    <span class="font-weight-bold text-truncate">{{ s.name || `Série S${idx}` }}</span>
                    <v-chip size="x-small" variant="tonal" :color="s.is_formula ? 'purple' : '#0d9488'" class="font-weight-bold">
                      {{ s.is_formula ? ($t('formula') || 'Formule') : (getSourceLabel(s.data_source)) }}
                    </v-chip>
                    <v-chip v-if="!s.is_formula && s.aggregate_field" size="x-small" variant="outlined" class="text-caption opacity-80">
                      {{ s.aggregate_function?.toUpperCase() }} • {{ s.aggregate_field }}
                    </v-chip>
                  </div>
                  <template #actions>
                    <div class="d-flex align-center ga-1" @click.stop>
                      <v-btn
                        :icon="s.is_visible === false ? 'mdi-eye-off' : 'mdi-eye'"
                        size="x-small"
                        variant="text"
                        :color="s.is_visible === false ? 'grey' : '#0d9488'"
                        @click.stop="s.is_visible = !s.is_visible"
                      />
                      <v-btn
                        v-if="form.series.length > 1"
                        icon="mdi-delete-outline"
                        size="x-small"
                        variant="text"
                        color="error"
                        @click.stop="removeSeries(idx)"
                      />
                    </div>
                  </template>
                </v-expansion-panel-title>

                <v-expansion-panel-text class="pt-3 px-3 pb-3">
                  <!-- Row 1: Identity & Mode -->
                  <v-row dense class="mb-2">
                    <v-col cols="12" sm="5">
                      <v-text-field
                        v-model="s.name"
                        :label="$t('name') || 'Nom de la série'"
                        placeholder="Ex: Tonnage Traité"
                        prepend-inner-icon="mdi-tag-outline"
                        variant="outlined"
                        rounded="lg"
                        density="compact"
                        color="#0d9488"
                        hide-details
                      ></v-text-field>
                    </v-col>

                    <v-col cols="6" sm="3">
                      <v-select
                        v-model="s.series_type"
                        :items="seriesTypes"
                        :label="$t('type') || 'Type'"
                        prepend-inner-icon="mdi-chart-line"
                        variant="outlined"
                        rounded="lg"
                        density="compact"
                        color="#0d9488"
                        hide-details
                      ></v-select>
                    </v-col>

                    <v-col cols="6" sm="2">
                      <v-select
                        v-model="s.measure_unit"
                        :items="measureUnits"
                        :label="$t('measure_unit') || 'Unité'"
                        prepend-inner-icon="mdi-ruler"
                        variant="outlined"
                        rounded="lg"
                        density="compact"
                        color="#0d9488"
                        hide-details
                      ></v-select>
                    </v-col>

                    <v-col cols="12" sm="2" class="d-flex align-center">
                      <v-switch
                        v-model="s.is_formula"
                        :label="$t('formula_mode') || 'Formule'"
                        color="purple"
                        density="compact"
                        hide-details
                      ></v-switch>
                    </v-col>
                  </v-row>

                  <!-- Harmonious Color Swatches -->
                  <div class="color-swatches-strip mb-3 d-flex align-center ga-2 flex-wrap">
                    <span class="text-caption font-weight-medium text-medium-emphasis mr-1">{{ $t('color') || 'Couleur' }}:</span>
                    <button
                      v-for="c in designerColors"
                      :key="c"
                      type="button"
                      class="color-dot-btn"
                      :style="{ backgroundColor: c }"
                      :class="{ 'is-selected': s.color === c }"
                      @click="s.color = c"
                    >
                      <v-icon v-if="s.color === c" size="12" color="white">mdi-check</v-icon>
                    </button>
                    <!-- Native Custom Picker fallback -->
                    <div class="custom-color-picker-wrap ml-2">
                      <input
                        type="color"
                        v-model="s.color"
                        class="custom-color-input"
                        :title="$t('custom_color') || 'Couleur personnalisée'"
                      />
                    </div>
                  </div>

                  <!-- Standard Database Query Mode -->
                  <template v-if="!s.is_formula">
                    <div class="source-pipeline-card rounded-xl pa-4 mb-3 border">
                      <div class="text-caption font-weight-bold text-medium-emphasis mb-2">
                        {{ $t('data_source') || 'Source de données' }}
                      </div>
                      <div class="d-flex align-center ga-2 flex-wrap mb-3">
                        <v-btn
                          v-for="src in dataSources"
                          :key="src.value"
                          size="small"
                          rounded="lg"
                          :variant="s.data_source === src.value ? 'flat' : 'outlined'"
                          :color="s.data_source === src.value ? '#0d9488' : 'grey'"
                          :prepend-icon="src.icon"
                          @click="s.data_source = src.value"
                        >
                          {{ src.title }}
                        </v-btn>
                      </div>

                      <v-row dense>
                        <v-col cols="12" sm="4">
                          <v-select
                            v-model="s.aggregate_field"
                            :items="getAggFields(s.data_source)"
                            :label="$t('aggregate_field') || 'Champ métrique'"
                            prepend-inner-icon="mdi-database-search"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col cols="6" sm="4">
                          <v-select
                            v-model="s.aggregate_function"
                            :items="aggFunctions"
                            :label="$t('aggregate_function') || 'Fonction'"
                            prepend-inner-icon="mdi-calculator"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col cols="6" sm="4">
                          <v-select
                            v-model="s.group_by"
                            :items="groupByOptions"
                            :label="$t('group_by_period') || 'Regroupement'"
                            prepend-inner-icon="mdi-calendar-clock"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            hide-details
                          ></v-select>
                        </v-col>
                      </v-row>
                    </div>

                    <!-- Contextual Filters Section -->
                    <div class="filters-card rounded-xl pa-4 border mb-2">
                      <div class="text-caption font-weight-bold text-medium-emphasis mb-2">
                        {{ $t('filters') || 'Filtres contextuels' }}
                      </div>
                      <v-row dense>
                        <v-col v-if="showFilter(s.data_source, 'category')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.category_id"
                            :items="getCategoryOptions(s.data_source)"
                            :label="$t('category') || 'Catégorie'"
                            prepend-inner-icon="mdi-tag-outline"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'fish_process_type')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.fish_process_type_id"
                            :items="fishProcessTypeOptions"
                            :label="$t('process_types') || 'Type de traitement'"
                            prepend-inner-icon="mdi-cogs"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'supplier')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.supplier_id"
                            :items="supplierOptions"
                            :label="$t('supplier') || 'Fournisseur'"
                            prepend-inner-icon="mdi-account-group"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'client')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.client_id"
                            :items="clientOptions"
                            :label="$t('client') || 'Client'"
                            prepend-inner-icon="mdi-account-tie"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'fishery')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.fishery_id"
                            :items="fisheryOptions"
                            :label="$t('fishery') || 'Zone de pêche'"
                            prepend-inner-icon="mdi-fish"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'origin')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.origin_id"
                            :items="originOptions"
                            :label="$t('origin') || 'Origine'"
                            prepend-inner-icon="mdi-map-marker"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'ship')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.ship_id"
                            :items="shipOptions"
                            :label="$t('ship') || 'Bateau'"
                            prepend-inner-icon="mdi-ferry"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'stockable')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.stockable_id"
                            :items="stockableOptions"
                            :label="$t('product') || 'Espèce / Article'"
                            prepend-inner-icon="mdi-package-variant"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'warehouse')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.warehouse_id"
                            :items="warehouseOptions"
                            :label="$t('warehouse_name') || 'Chambre / Entrepôt'"
                            prepend-inner-icon="mdi-warehouse"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'warehouse_category')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.warehouse_category_id"
                            :items="warehouseCategoryOptions"
                            :label="$t('warehouse_category') || 'Catégorie Entrepôt'"
                            prepend-inner-icon="mdi-shape"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'production_type')" cols="12" sm="4">
                          <v-select
                            v-model="s.filters.production_type"
                            :items="productionTypeOptions"
                            :label="$t('production_type') || 'Type de production'"
                            prepend-inner-icon="mdi-factory"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'direction')" cols="12" sm="4">
                          <v-select
                            v-model="s.filters.direction"
                            :items="directionOptions"
                            :label="$t('direction') || 'Destination'"
                            prepend-inner-icon="mdi-compass-outline"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'section')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.section_id"
                            :items="sectionOptions"
                            :label="$t('section') || 'Section'"
                            prepend-inner-icon="mdi-layers-outline"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                        <v-col v-if="showFilter(s.data_source, 'corporal')" cols="12" sm="4">
                          <v-autocomplete
                            v-model="s.filters.corporal_id"
                            :items="corporalOptions"
                            :label="$t('corporal') || 'Caporal'"
                            prepend-inner-icon="mdi-account-star"
                            variant="outlined"
                            rounded="lg"
                            density="compact"
                            color="#0d9488"
                            clearable
                            hide-details
                          ></v-autocomplete>
                        </v-col>
                      </v-row>
                    </div>
                  </template>

                  <!-- Formula Calculation Mode -->
                  <template v-else>
                    <div class="formula-builder-card rounded-xl pa-4 border">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <span class="text-caption font-weight-bold text-medium-emphasis">
                          {{ $t('formula_expression') || 'Expression mathématique' }}
                        </span>
                        <span class="text-caption text-purple font-weight-medium">Ex: (S0 / S1) * 100</span>
                      </div>

                      <v-text-field
                        v-model="s.formula_expression"
                        placeholder="(S0 / S1) * 100"
                        prepend-inner-icon="mdi-function-variant"
                        variant="outlined"
                        rounded="lg"
                        density="compact"
                        color="purple"
                        class="mb-3"
                        hide-details
                      ></v-text-field>

                      <!-- Interactive Tokens Bar -->
                      <div class="text-caption font-weight-bold text-medium-emphasis mb-1">
                        {{ $t('click_token_to_insert') || 'Cliquez pour insérer dans la formule' }}:
                      </div>
                      <div class="d-flex align-center ga-1 flex-wrap mb-3">
                        <v-chip
                          v-for="(refSeries, rIdx) in form.series"
                          :key="`ref-${rIdx}`"
                          v-show="rIdx !== idx && !refSeries.is_formula"
                          size="small"
                          color="purple"
                          variant="tonal"
                          class="cursor-pointer font-weight-bold"
                          @click="appendFormulaToken(s, `S${rIdx}`)"
                        >
                          S{{ rIdx }} ({{ refSeries.name || `S${rIdx}` }})
                        </v-chip>

                        <v-chip
                          v-for="op in ['+', '-', '*', '/', '(', ')', '* 100']"
                          :key="op"
                          size="small"
                          variant="outlined"
                          class="cursor-pointer font-weight-bold"
                          @click="appendFormulaToken(s, ` ${op} `)"
                        >
                          {{ op }}
                        </v-chip>
                      </div>

                      <v-select
                        v-model="s.group_by"
                        :items="groupByOptions"
                        :label="$t('group_by_period') || 'Période'"
                        prepend-inner-icon="mdi-calendar-clock"
                        variant="outlined"
                        rounded="lg"
                        density="compact"
                        color="purple"
                        hide-details
                      ></v-select>
                    </div>
                  </template>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </div>

          <!-- STEP 4: Preview & Deploy -->
          <div v-show="step === 4" class="step-content">
            <div class="section-title-wrap mb-4">
              <div class="d-flex align-center ga-2">
                <v-icon color="#0d9488" size="20">mdi-eye-check-outline</v-icon>
                <span class="text-subtitle-1 font-weight-bold">{{ $t('step_preview') || 'Aperçu & Déploiement' }}</span>
              </div>
              <div class="text-caption text-medium-emphasis">{{ $t('step_preview_desc') || 'Vérifiez la visualisation finale et déployez-la sur votre tableau de bord' }}</div>
            </div>

            <div v-if="previewLoading" class="d-flex flex-column align-center justify-center py-12">
              <v-progress-circular indeterminate color="#0d9488" size="48" class="mb-3" />
              <span class="text-caption text-medium-emphasis">{{ $t('loading') || 'Génération de l\'aperçu...' }}</span>
            </div>

            <template v-else>
              <!-- KPI Single Stat Mode Preview -->
              <div v-if="form.display_type === 'statistic'" class="kpi-executive-card rounded-xl pa-8 text-center mb-4">
                <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-2">
                  {{ form.title || ($t('visual_type_stat') || 'Indicateur KPI') }}
                </div>
                <div class="kpi-executive-number">
                  {{ previewStatValue }}
                </div>
                <div class="text-caption text-medium-emphasis mt-2">
                  {{ form.series[0]?.name }} • {{ form.series[0]?.measure_unit || 'Kg' }}
                </div>
              </div>

              <!-- ApexCharts Chart Preview -->
              <div v-else class="chart-box-card rounded-xl pa-4 mb-4">
                <div class="d-flex align-center justify-space-between mb-3 px-2">
                  <div class="d-flex align-center ga-2">
                    <v-icon color="#0d9488" size="20">mdi-chart-box-outline</v-icon>
                    <span class="font-weight-bold text-subtitle-2">{{ form.title || 'Graphique sans titre' }}</span>
                  </div>
                  <v-chip size="x-small" color="#0d9488" variant="tonal" class="font-weight-bold text-uppercase">
                    {{ form.chart_type }}
                  </v-chip>
                </div>
                <ChartPreview
                  :chart-config="form"
                  :categories="previewCategories"
                  :series="previewSeries"
                  :height="360"
                />
              </div>

              <!-- Specifications Summary Strip -->
              <div class="spec-summary-strip rounded-lg pa-3 d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center ga-3 flex-wrap text-caption text-medium-emphasis">
                  <span><strong>{{ $t('type') || 'Type' }}:</strong> {{ form.chart_type }}</span>
                  <span>•</span>
                  <span><strong>{{ $t('date_range') || 'Période' }}:</strong> {{ form.date_range_type }}</span>
                  <span>•</span>
                  <span><strong>{{ $t('series') || 'Séries' }}:</strong> {{ form.series.length }}</span>
                  <span>•</span>
                  <span><strong>{{ $t('grid_columns') || 'Largeur' }}:</strong> {{ form.grid_cols }}/12</span>
                </div>
                <v-chip size="x-small" :color="form.is_active ? 'success' : 'grey'" variant="tonal" class="font-weight-bold">
                  {{ form.is_active ? ($t('active') || 'Actif') : ($t('inactive') || 'Inactif') }}
                </v-chip>
              </div>
            </template>
          </div>

          <!-- Bottom Step Navigation Buttons -->
          <div class="d-flex align-center justify-space-between mt-6 pt-4 border-t">
            <v-btn
              v-if="step > 1"
              variant="outlined"
              color="#0d9488"
              rounded="lg"
              size="small"
              prepend-icon="mdi-arrow-left"
              @click="step--"
            >
              {{ $t('back') || 'Précédent' }}
            </v-btn>
            <div v-else></div>

            <div class="d-flex align-center ga-2">
              <v-btn
                v-if="step < 4"
                variant="flat"
                color="#0d9488"
                rounded="lg"
                size="small"
                append-icon="mdi-arrow-right"
                class="font-weight-bold px-4"
                @click="goNext"
              >
                {{ step === 3 ? ($t('step_preview') || 'Aperçu Direct') : ($t('next') || 'Suivant') }}
              </v-btn>
              <v-btn
                v-else
                variant="flat"
                color="#0d9488"
                rounded="lg"
                size="small"
                :loading="saving"
                prepend-icon="mdi-check-circle-outline"
                class="font-weight-bold px-5"
                @click="save"
              >
                {{ editing ? ($t('update') || 'Mettre à jour') : ($t('submit') || 'Déployer') }}
              </v-btn>
            </div>
          </div>
        </v-card>
      </v-col>

      <!-- RIGHT: Live Split Preview Panel (Optional side-by-side mode on desktop) -->
      <v-col
        v-if="showSplitPreview && $vuetify.display.lgAndUp"
        cols="12"
        lg="5"
      >
        <v-card class="split-preview-sticky-card rounded-xl pa-4" elevation="0">
          <div class="d-flex align-center justify-space-between mb-3">
            <div class="d-flex align-center ga-2">
              <v-icon color="#0d9488" size="18">mdi-television-guide</v-icon>
              <span class="text-subtitle-2 font-weight-bold">{{ $t('live_preview') || 'Aperçu en Direct' }}</span>
            </div>
            <v-btn
              size="x-small"
              icon="mdi-refresh"
              variant="text"
              color="#0d9488"
              @click="loadPreview"
            ></v-btn>
          </div>

          <div v-if="previewLoading" class="d-flex justify-center py-10">
            <v-progress-circular indeterminate color="#0d9488" size="32" />
          </div>
          <template v-else>
            <div v-if="form.display_type === 'statistic'" class="kpi-executive-card rounded-xl pa-6 text-center">
              <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-1">
                {{ form.title || ($t('visual_type_stat') || 'Indicateur KPI') }}
              </div>
              <div class="kpi-executive-number text-h4">
                {{ previewStatValue }}
              </div>
            </div>
            <div v-else>
              <ChartPreview
                :chart-config="form"
                :categories="previewCategories"
                :series="previewSeries"
                :height="280"
              />
            </div>
          </template>

          <!-- Live Series Chips -->
          <div class="mt-3 pt-3 border-t">
            <div class="text-caption font-weight-bold text-medium-emphasis mb-2">
              {{ $t('series') || 'Séries actives' }} ({{ form.series.length }})
            </div>
            <div class="d-flex align-center ga-1 flex-wrap">
              <v-chip
                v-for="(s, sIdx) in form.series"
                :key="sIdx"
                size="x-small"
                variant="tonal"
                :style="{ borderColor: s.color || '#0d9488' }"
                class="font-weight-medium"
              >
                <span class="series-color-dot mr-1" :style="{ backgroundColor: s.color || '#0d9488' }"></span>
                {{ s.name || `S${sIdx}` }} ({{ s.measure_unit || 'Kg' }})
              </v-chip>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Summary Snackbar -->
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
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useCustomChart } from '@/stores/CustomChart';
import ChartPreview from './ChartPreview.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';

const DESIGNER_COLORS = [
  '#0d9488', // Ocean Teal
  '#0284c7', // Sky Blue
  '#2563eb', // Sapphire Blue
  '#059669', // Emerald Green
  '#d97706', // Warm Amber
  '#e11d48', // Crimson Coral
  '#7c3aed', // Purple Violet
  '#4f46e5', // Deep Indigo
  '#ea580c', // Bright Orange
  '#475569', // Slate Steel
];

export default {
  name: 'ChartForm',
  components: { ChartPreview, SegmentedPillToggle },
  props: {
    editChart: { type: Object, default: null }
  },
  emits: ['saved', 'cancel'],
  data() {
    return {
      step: 1,
      saving: false,
      openSeriesPanel: 0,
      showSplitPreview: true,
      previewLoading: false,
      previewCategories: [],
      previewSeries: [],
      chartOptions: { stacked: false },
      snackbar: { show: false, text: '', color: 'success' },
      designerColors: DESIGNER_COLORS,
      form: this.getDefaultForm(),
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    store() { return useCustomChart(); },
    editing() { return !!this.editChart?.id && typeof this.editChart.id === 'number'; },
    lang() { return this.main.lang?.code || 'en'; },

    displayTypeOptions() {
      return [
        {
          value: 'chart',
          label: this.$t('chart') || 'Chart',
          icon: 'mdi-chart-areaspline',
        },
        {
          value: 'statistic',
          label: this.$t('visual_type_stat') || 'Single Executive KPI',
          icon: 'mdi-numeric-1-box-outline',
        },
      ];
    },

    steps() {
      return [
        { step: 1, title: this.$t('step_identity') || 'Identité', sub: this.$t('chart_type') || 'Type & Grille' },
        { step: 2, title: this.$t('step_dates') || 'Horizon', sub: this.$t('date_range') || 'Période' },
        { step: 3, title: this.$t('step_series') || 'Séries', sub: this.$t('configured_series') || 'Données & Filtres' },
        { step: 4, title: this.$t('step_preview') || 'Aperçu', sub: this.$t('deploy') || 'Finalisation' },
      ];
    },

    chartTypeCards() {
      return [
        { value: 'bar', title: this.$t('visual_type_bar') || 'Barres', icon: 'mdi-chart-bar', desc: this.$t('visual_type_bar_desc') || 'Comparaison de volumes' },
        { value: 'line', title: this.$t('visual_type_line') || 'Courbe', icon: 'mdi-chart-line', desc: this.$t('visual_type_line_desc') || 'Tendances et progression' },
        { value: 'area', title: this.$t('visual_type_area') || 'Aires', icon: 'mdi-chart-areaspline', desc: this.$t('visual_type_area_desc') || 'Volumes cumulatifs' },
        { value: 'donut', title: this.$t('visual_type_donut') || 'Donut', icon: 'mdi-chart-donut', desc: this.$t('visual_type_donut_desc') || 'Proportions & parts' },
        { value: 'pie', title: this.$t('visual_type_pie') || 'Camembert', icon: 'mdi-chart-pie', desc: this.$t('visual_type_pie_desc') || 'Répartition d\'un ensemble' },
        { value: 'mixed', title: this.$t('visual_type_mixed') || 'Mixte', icon: 'mdi-chart-multiline', desc: this.$t('visual_type_mixed_desc') || 'Barres + Lignes combinées' },
        { value: 'radar', title: 'Radar', icon: 'mdi-spider-web', desc: 'Performance multi-critères' },
      ];
    },

    dateRangeCards() {
      return [
        { value: 'dynamic', title: this.$t('dynamic_range') || 'Dynamique', icon: 'mdi-sync', desc: this.$t('dynamic_range_desc') || 'Synchronisé en direct avec le sélecteur du tableau de bord' },
        { value: 'fixed_month', title: this.$t('fixed_month') || 'Mois Spécifique', icon: 'mdi-calendar-month', desc: this.$t('fixed_month_desc') || 'Données verrouillées sur un mois précis' },
        { value: 'fixed_year', title: this.$t('fixed_year') || 'Année Fiscale', icon: 'mdi-calendar-range', desc: this.$t('fixed_year_desc') || 'Bilan annuel consolidé' },
        { value: 'all', title: this.$t('all_dates') || 'Tout l\'historique', icon: 'mdi-all-inclusive', desc: this.$t('all_dates_desc') || 'Toutes les archives sans borne' },
      ];
    },

    months() {
      return Array.from({ length: 12 }, (_, i) => ({
        title: String(i + 1).padStart(2, '0'),
        value: i + 1,
      }));
    },
    years() {
      const y = new Date().getFullYear();
      return Array.from({ length: 10 }, (_, i) => y - 5 + i);
    },
    seriesTypes() {
      return [
        { title: this.$t('visual_type_bar') || 'Barre / Colonne', value: 'column' },
        { title: this.$t('visual_type_line') || 'Ligne', value: 'line' },
        { title: this.$t('visual_type_area') || 'Aire', value: 'area' },
        { title: this.$t('visual_type_donut') || 'Donut', value: 'donut' },
        { title: this.$t('visual_type_pie') || 'Camembert', value: 'pie' },
      ];
    },
    measureUnits() {
      return [
        { title: 'Kg (Poids)', value: 'Kg' },
        { title: 'DH (Montant)', value: 'DH' },
        { title: 'DH/KG (Prix Moyen)', value: 'DH/KG' },
        { title: '% (Pourcentage)', value: '%' },
      ];
    },
    dataSources() {
      return [
        { title: this.$t('source_charges') || 'Charges / Achats', value: 'charges', icon: 'mdi-cart-outline' },
        { title: this.$t('source_productions') || 'Productions', value: 'productions', icon: 'mdi-factory' },
        { title: this.$t('source_shippings') || 'Expéditions / Ventes', value: 'shippings', icon: 'mdi-truck-cargo-container' },
        { title: this.$t('source_warehouses') || 'Entrepôts / Stocks', value: 'warehouses', icon: 'mdi-snowflake' },
        { title: this.$t('source_fortnight') || 'Main-d\'œuvre (Quinzaine)', value: 'fortnight_activities', icon: 'mdi-account-group' },
        { title: this.$t('source_static_charges') || 'Charges Fixes', value: 'static_charges', icon: 'mdi-cash-multiple' },
      ];
    },
    aggFunctions() {
      return [
        { title: 'SUM (Somme)', value: 'sum' },
        { title: 'AVG (Moyenne)', value: 'avg' },
        { title: 'COUNT (Nombre)', value: 'count' },
        { title: 'MIN (Minimum)', value: 'min' },
        { title: 'MAX (Maximum)', value: 'max' },
      ];
    },
    groupByOptions() {
      return [
        { title: this.$t('day') || 'Par Jour', value: 'day' },
        { title: this.$t('month') || 'Par Mois', value: 'month' },
        { title: this.$t('year') || 'Par Année', value: 'year' },
      ];
    },

    fishProcessTypeOptions() {
      return (this.auth.fish_process_type?.list || []).map(f => ({ title: f.name, value: f.id }));
    },
    supplierOptions() {
      return (this.auth.supplier?.list || []).map(s => ({ title: s.name, value: s.id }));
    },
    clientOptions() {
      return (this.auth.client?.list || []).map(c => ({ title: c.name, value: c.id }));
    },
    fisheryOptions() {
      return (this.auth.fishery?.list || []).map(f => ({ title: f.name, value: f.id }));
    },
    stockableOptions() {
      const items = [];
      (this.auth.fish_type?.list || []).forEach(f => {
        items.push({ title: f[`name_${this.lang}`] || f.name_fr || f.name_en, value: f.id });
      });
      return items;
    },
    originOptions() {
      return (this.auth.origin?.list || []).map(o => ({ title: o.name, value: o.id }));
    },
    shipOptions() {
      return (this.auth.ship?.list || []).map(s => ({ title: s.name, value: s.id }));
    },
    sectionOptions() {
      return (this.auth.section?.list || []).map(s => ({
        title: s[`name_${this.lang}`] || s.name_fr || s.name_en || s.name,
        value: s.id,
      }));
    },
    corporalOptions() {
      return (this.auth.corporal?.list || []).map(c => ({ title: c.name, value: c.id }));
    },
    productionTypeOptions() {
      return [
        { title: this.$t('intern') || 'Interne', value: 'intern' },
        { title: this.$t('extern') || 'Externe', value: 'extern' },
      ];
    },
    directionOptions() {
      return [
        { title: this.$t('local') || 'Local', value: 'local' },
        { title: this.$t('export') || 'Export', value: 'export' },
      ];
    },
    warehouseOptions() {
      return (this.auth.warehouse?.list || []).map(w => ({ title: w.name, value: w.id }));
    },
    warehouseCategoryOptions() {
      const cats = {};
      (this.auth.warehouse?.list || []).forEach(w => {
        if (w.category?.id) {
          cats[w.category.id] = { title: w.category.name, value: w.category.id };
        } else if (w.warehouse_category_id) {
          cats[w.warehouse_category_id] = { title: `Catégorie #${w.warehouse_category_id}`, value: w.warehouse_category_id };
        }
      });
      return Object.values(cats);
    },

    previewStatValue() {
      if (!this.previewSeries?.length || !this.previewSeries[0]?.data?.length) return '0.00';
      const unit = this.previewSeries[0].measure_unit || '';
      const total = this.previewSeries[0].data.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
      const suffix = unit ? ` ${unit}` : '';
      const formatted = total.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).replace(/\u202F/g, ' ').replace(',', '.');
      if (unit === '%') return formatted + '%';
      return formatted + suffix;
    },
  },
  watch: {
    editChart: {
      handler(v) {
        if (v) {
          this.loadEdit(v);
        } else {
          this.resetForm();
        }
      },
      immediate: true,
    },
    'form.chart_type'(newType) {
      this.updateSeriesTypesForMainType(newType);
      this.loadPreview();
    },
    'form.display_type'() {
      this.loadPreview();
    },
    'form.series': {
      deep: true,
      handler() {
        this.loadPreviewDebounced();
      },
    },
  },
  methods: {
    getDefaultForm() {
      return {
        title: '',
        chart_type: 'bar',
        display_type: 'chart',
        date_range_type: 'dynamic',
        fixed_month: null,
        fixed_year: new Date().getFullYear(),
        dynamic_start: new Date(new Date().getFullYear(), 0, 1).toISOString().slice(0, 10),
        dynamic_end: new Date().toISOString().slice(0, 10),
        grid_cols: 6,
        is_active: true,
        chart_options: {},
        series: [this.getDefaultSeries(0)],
      };
    },
    getDefaultSeries(idx) {
      return {
        name: '',
        series_type: 'column',
        measure_unit: 'Kg',
        is_visible: true,
        color: DESIGNER_COLORS[idx % DESIGNER_COLORS.length],
        data_source: 'productions',
        aggregate_field: 'total_weight',
        aggregate_function: 'sum',
        group_by: 'month',
        filters: {},
        is_formula: false,
        formula_expression: '',
        formula_series_refs: [],
        sort_order: idx,
      };
    },
    loadEdit(chart) {
      this.form = {
        ...chart,
        dynamic_start: chart.dynamic_start ? chart.dynamic_start.slice(0, 10) : null,
        dynamic_end: chart.dynamic_end ? chart.dynamic_end.slice(0, 10) : null,
        chart_options: chart.chart_options || {},
        series: (chart.series || []).map(s => ({ ...s, filters: s.filters || {} })),
      };
      this.chartOptions.stacked = this.form.chart_options?.stacked || false;
      this.step = 1;
      this.loadPreview();
    },
    resetForm() {
      this.form = this.getDefaultForm();
      this.chartOptions.stacked = false;
      this.step = 1;
      this.loadPreview();
    },
    addSeries() {
      const newIdx = this.form.series.length;
      this.form.series.push(this.getDefaultSeries(newIdx));
      this.openSeriesPanel = newIdx;
    },
    removeSeries(idx) {
      if (this.form.series.length > 1) {
        this.form.series.splice(idx, 1);
      }
    },
    selectChartType(type) {
      this.form.chart_type = type;
      this.updateSeriesTypesForMainType(type);
      this.loadPreview();
    },
    updateSeriesTypesForMainType(ct = this.form.chart_type) {
      if (!ct) return;
      if (['donut', 'pie'].includes(ct)) {
        this.form.series.forEach(s => { s.series_type = ct; });
      } else if (ct === 'bar') {
        this.form.series.forEach(s => { s.series_type = 'column'; });
      } else if (ct === 'line') {
        this.form.series.forEach(s => { s.series_type = 'line'; });
      } else if (ct === 'area') {
        this.form.series.forEach(s => { s.series_type = 'area'; });
      } else if (ct === 'radar') {
        this.form.series.forEach(s => { s.series_type = 'radar'; });
      }
    },
    getSourceLabel(source) {
      const found = this.dataSources.find(s => s.value === source);
      return found ? found.title : source;
    },
    getAggFields(source) {
      const map = {
        charges: ['total_weight', 'total_price', 'count'],
        productions: ['total_weight', 'total_price', 'fresh_reception_weight', 'frozen_production_weight', 'guano_production_weight', 'count'],
        shippings: ['total_weight', 'total_price', 'count'],
        warehouses: ['total_weight', 'remaining_weight', 'total_price', 'count'],
        fortnight_activities: ['price', 'count'],
        static_charges: ['amount', 'count'],
      };
      return (map[source] || ['total_weight']).map(v => ({
        title: this.$t(`field_${v}`) || v,
        value: v,
      }));
    },
    showFilter(source, filter) {
      const map = {
        charges: ['category', 'fish_process_type', 'supplier', 'fishery', 'origin', 'ship', 'stockable'],
        productions: ['category', 'fish_process_type', 'supplier', 'client', 'fishery', 'origin', 'ship', 'stockable', 'production_type'],
        shippings: ['category', 'client', 'fishery', 'origin', 'ship', 'stockable', 'direction'],
        warehouses: ['stockable', 'warehouse', 'warehouse_category'],
        fortnight_activities: ['section', 'corporal'],
        static_charges: [],
      };
      return (map[source] || []).includes(filter);
    },
    getCategoryOptions(source) {
      const map = {
        charges: this.auth.charge_category?.list || [],
        productions: this.auth.production_category?.list || [],
        shippings: this.auth.sell_category?.list || [],
      };
      return (map[source] || []).map(c => ({
        title: c[`name_${this.lang}`] || c.name_fr || c.name_en || `#${c.id}`,
        value: c.id,
      }));
    },
    appendFormulaToken(series, token) {
      series.formula_expression = (series.formula_expression || '') + token;
    },
    async goToStep(target) {
      this.step = target;
      if (target === 4) {
        await this.loadPreview();
      }
    },
    async goNext() {
      if (this.step < 4) {
        this.step++;
        if (this.step === 4) {
          await this.loadPreview();
        }
      }
    },
    loadPreviewDebounced() {
      clearTimeout(this._previewTimer);
      this._previewTimer = setTimeout(() => {
        this.loadPreview();
      }, 300);
    },
    async loadPreview() {
      this.previewLoading = true;
      const ct = this.form.chart_type || 'bar';
      const defaultType = ct === 'bar' ? 'column' : ct;
      try {
        if (this.editing && this.editChart?.id) {
          const result = await this.store.fetchChartData(this.editChart.id, true);
          if (result?.categories?.length) {
            this.previewCategories = result.categories;
            this.previewSeries = this.form.series.map((s, i) => {
              const serverSeries = result.series?.[i];
              return {
                name: s.name || `Série ${i + 1}`,
                type: ct === 'mixed' ? (s.series_type || 'column') : defaultType,
                color: s.color || this.designerColors[i % this.designerColors.length],
                measure_unit: s.measure_unit || 'Kg',
                data: serverSeries?.data || Array.from({ length: result.categories.length }, () => Math.floor(Math.random() * 1200 + 200)),
              };
            });
            this.previewLoading = false;
            return;
          }
        }
      } catch {
        /* fallback to sample preview */
      }

      // Elegant realistic sample preview
      const sampleMonths = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août'];
      this.previewCategories = sampleMonths;
      this.previewSeries = this.form.series
        .filter(s => s.is_visible !== false)
        .map((s, i) => ({
          name: s.name || (s.is_formula ? `Formule S${i}` : `${this.getSourceLabel(s.data_source)}`),
          type: ct === 'mixed' ? (s.series_type || 'column') : defaultType,
          color: s.color || this.designerColors[i % this.designerColors.length],
          measure_unit: s.measure_unit || 'Kg',
          data: [850, 1120, 940, 1400, 1250, 1680, 1520, 1890].map(v => Math.round(v * (1 + i * 0.25))),
        }));
      this.previewLoading = false;
    },
    async save() {
      if (!this.form.title) {
        this.snackbar = {
          show: true,
          text: this.$t('chart_title_required') || 'Veuillez saisir un titre pour le graphique',
          color: 'error',
        };
        this.step = 1;
        return;
      }
      this.saving = true;
      try {
        this.form.chart_options = {
          ...this.form.chart_options,
          stacked: this.chartOptions.stacked,
        };
        let saved;
        if (this.editing) {
          saved = await this.store.update(this.editChart.id, this.form);
          this.snackbar = {
            show: true,
            text: this.$t('chart_updated_success') || 'Graphique mis à jour avec succès',
            color: 'success',
          };
        } else {
          saved = await this.store.create(this.form);
          this.snackbar = {
            show: true,
            text: this.$t('chart_saved_success') || 'Graphique créé avec succès',
            color: 'success',
          };
        }
        this.$emit('saved', saved);
      } catch (e) {
        this.snackbar = {
          show: true,
          text: e.response?.data?.message || 'Erreur lors de l\'enregistrement',
          color: 'error',
        };
      }
      this.saving = false;
    },
  },
};
</script>

<style scoped>
.chart-studio-wrap {
  font-family: 'Inter', sans-serif;
}

/* Stepper Track */
.stepper-nav-bar {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 16px;
  padding: 8px 12px;
  overflow-x: auto;
  scrollbar-width: thin;
  -webkit-overflow-scrolling: touch;
}
.stepper-nav-bar::-webkit-scrollbar {
  height: 4px;
}
.stepper-nav-bar::-webkit-scrollbar-thumb {
  background: rgba(13, 148, 136, 0.25);
  border-radius: 4px;
}
.stepper-track {
  min-width: 560px;
}
.step-pill-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  border-radius: 12px;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  flex: 1;
  min-width: 135px;
}
.step-pill-btn:hover {
  background: rgba(13, 148, 136, 0.05);
  border-color: rgba(13, 148, 136, 0.15);
}
.step-pill-btn.is-active {
  background: rgba(13, 148, 136, 0.09);
  border-color: rgba(13, 148, 136, 0.35);
  box-shadow: 0 2px 8px -2px rgba(13, 148, 136, 0.15);
}
.step-pill-num {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  background: rgba(var(--v-theme-on-surface), 0.08);
  color: rgba(var(--v-theme-on-surface), 0.6);
  transition: all 0.2s ease;
  flex-shrink: 0;
}
.step-pill-btn.is-active .step-pill-num {
  background: #0d9488;
  color: white;
  box-shadow: 0 2px 6px rgba(13, 148, 136, 0.35);
}
.step-pill-btn.is-completed .step-pill-num {
  background: rgba(13, 148, 136, 0.18);
  color: #0d9488;
}
.step-pill-title {
  font-size: 13px;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.85);
  line-height: 1.2;
}
.step-pill-btn.is-active .step-pill-title {
  color: #0d9488;
  font-weight: 700;
}
.step-pill-sub {
  font-size: 11px;
  color: rgba(var(--v-theme-on-surface), 0.5);
  line-height: 1.2;
  margin-top: 2px;
}

/* Form Canvas Card */
.form-canvas-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-surface), 1);
  box-shadow: 0 4px 20px -2px rgba(15, 118, 110, 0.04) !important;
}

/* Visual Type Card */
.visual-type-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-on-surface), 0.015);
  transition: all 0.2s ease;
}
.visual-type-card:hover {
  background: rgba(13, 148, 136, 0.04);
  border-color: rgba(13, 148, 136, 0.2);
}
.visual-type-card.is-selected {
  background: rgba(13, 148, 136, 0.08);
  border-color: #0d9488;
  box-shadow: 0 0 0 1px #0d9488;
}
.visual-type-card .type-name {
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), 0.9);
}
.visual-type-card.is-selected .type-name {
  color: #0d9488;
}
.visual-type-card .type-desc {
  font-size: 10px;
  line-height: 1.2;
}

/* Layout Controls Card */
.layout-controls-card,
.period-picker-box,
.source-pipeline-card,
.filters-card,
.formula-builder-card {
  background: rgba(var(--v-theme-on-surface), 0.02);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

/* Date Mode Card */
.date-mode-card {
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-on-surface), 0.01);
  transition: all 0.2s ease;
}
.date-mode-card:hover {
  background: rgba(13, 148, 136, 0.03);
}
.date-mode-card.is-selected {
  border-color: #0d9488;
  background: rgba(13, 148, 136, 0.06);
}

/* Series Panels */
.series-panels-group :deep(.v-expansion-panel) {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 14px !important;
  overflow: hidden;
  margin-bottom: 8px;
}
.series-color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

/* Color Swatches */
.color-dot-btn {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
  transition: transform 0.15s ease;
}
.color-dot-btn:hover {
  transform: scale(1.18);
}
.color-dot-btn.is-selected {
  transform: scale(1.22);
  box-shadow: 0 0 0 2px #0d9488;
}
.custom-color-picker-wrap {
  position: relative;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,0.15);
}
.custom-color-input {
  width: 32px;
  height: 32px;
  border: none;
  cursor: pointer;
  position: absolute;
  top: -4px;
  left: -4px;
}

/* Split Preview Sticky Card */
.split-preview-sticky-card {
  position: sticky;
  top: 80px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-surface), 1);
  box-shadow: 0 4px 20px -2px rgba(15, 118, 110, 0.06) !important;
}

/* Executive KPI Card */
.kpi-executive-card {
  background: linear-gradient(135deg, rgba(13, 148, 136, 0.08) 0%, rgba(2, 132, 199, 0.08) 100%);
  border: 1px solid rgba(13, 148, 136, 0.2);
}
.kpi-executive-number {
  font-size: 40px;
  font-weight: 800;
  color: #0d9488;
  letter-spacing: -1px;
}

/* Chart Box Card */
.chart-box-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-on-surface), 0.01);
}
.spec-summary-strip {
  background: rgba(var(--v-theme-on-surface), 0.02);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.05);
}
</style>
