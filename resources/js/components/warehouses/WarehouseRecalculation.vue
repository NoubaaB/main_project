<template>
<div class="wh-recalculation-view">
  <!-- Top Navigation & Control Toolbar -->
  <v-card class="wh-toolbar-card mb-4 rounded-2xl border pa-3 pa-md-4" elevation="0">
    <div class="d-flex align-center justify-space-between flex-wrap ga-3">
      <!-- Title & Context -->
      <div class="d-flex align-center ga-3">
        <v-avatar size="44" color="rgba(162, 30, 229, 0.12)" class="wh-toolbar-avatar">
          <v-icon size="24" color="#a21ee5">mdi-calculator-variant-outline</v-icon>
        </v-avatar>
        <div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <h2 class="text-subtitle-1 font-weight-bold text-high-emphasis mb-0">
              {{ $t('warehouse_recalculation_title') || 'Recalcul des Stocks & Inventaires d\'Entrepôts' }}
            </h2>
            <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
              <v-icon size="11" class="mr-1">mdi-shield-sync-outline</v-icon>
              {{ $t('stock_integrity_engine') || 'Moteur d\'Intégrité' }}
            </v-chip>
          </div>
          <span class="text-caption text-medium-emphasis d-block mt-0.5">
            {{ $t('warehouse_recalculation_subtitle') || 'Recalculez et réconciliez les poids restants, colis, valorisations WAP et soldes d\'opérations.' }}
          </span>
        </div>
      </div>

      <!-- Quick KPI Chips & Action Buttons -->
      <div class="d-flex align-center ga-2 flex-wrap">
        <div class="wh-pill-badge d-flex align-center ga-1.5 px-3 py-1.5 rounded-pill border">
          <v-icon size="13" color="#00897B">mdi-calendar-today</v-icon>
          <span class="text-caption font-weight-medium text-medium-emphasis">{{ $t('actual_date') || 'Aujourd\'hui' }}:</span>
          <span class="text-caption font-weight-bold font-mono text-high-emphasis">{{ todayFormatted }}</span>
        </div>

        <div class="wh-pill-badge d-flex align-center ga-1.5 px-3 py-1.5 rounded-pill border">
          <v-icon size="13" color="#a21ee5">mdi-clipboard-list-outline</v-icon>
          <span class="text-caption font-weight-medium text-medium-emphasis">{{ inventoryDays.length }}</span>
          <span class="text-caption text-medium-emphasis">sessions</span>
        </div>

        <v-btn
          variant="tonal"
          color="#a21ee5"
          rounded="pill"
          size="small"
          class="font-weight-bold px-3 ml-1"
          prepend-icon="mdi-eye-check-outline"
          :loading="previewLoading"
          :disabled="executeLoading"
          @click="runPreview"
        >
          {{ $t('simulate_preview') || 'Simuler l\'Aperçu' }}
        </v-btn>

        <v-btn
          variant="flat"
          color="#a21ee5"
          rounded="pill"
          size="small"
          class="font-weight-bold text-white px-4"
          prepend-icon="mdi-play-circle-outline"
          :loading="executeLoading"
          :disabled="previewLoading"
          @click="confirmExecuteDialog = true"
        >
          {{ $t('execute_recalculation') || 'Lancer le Recalcul' }}
        </v-btn>
      </div>
    </div>
  </v-card>

  <!-- STEP 1: INVENTORY BASELINE SELECTION -->
  <v-card class="wh-section-card mb-4 rounded-2xl border pa-4 pa-md-5" elevation="0">
    <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-3 pb-3 border-b">
      <div class="d-flex align-center ga-2">
        <v-avatar size="30" color="rgba(162, 30, 229, 0.12)">
          <v-icon size="16" color="#a21ee5">mdi-clipboard-text-clock-outline</v-icon>
        </v-avatar>
        <div>
          <span class="text-subtitle-2 font-weight-bold text-high-emphasis d-block">
            {{ $t('inventory_baseline_title') || '1. Date d\'Inventaire de Référence' }}
          </span>
          <span class="text-caption text-medium-emphasis">
            {{ $t('inventory_baseline_subtitle') || 'Point de départ physique pour le recalcul des mouvements.' }}
          </span>
        </div>
      </div>

      <!-- Segmented Mode Toggle (Inventaires Réels vs Plage Personnalisée) -->
      <SegmentedPillToggle
        v-model="selectionMode"
        :items="selectionModeOptions"
        color="#a21ee5"
        size="small"
      />
    </div>

    <!-- MODE A: INVENTAIRES RÉELS (INVENTORY DAYS CAROUSEL / CARDS) -->
    <div v-if="selectionMode === 'inventory'">
      <div v-if="loadingInventoryDays" class="wh-inv-scroll-strip d-flex ga-3 overflow-x-auto pb-2">
        <div
          v-for="sk in 4"
          :key="'inv-day-skel-' + sk"
          class="wh-inv-pill-card pa-3 rounded-xl border flex-shrink-0 d-flex flex-column ga-2"
          style="min-width: 175px;"
        >
          <div class="d-flex align-center justify-space-between ga-2">
            <div class="skel-bone" style="width: 75px; height: 16px;"></div>
            <div class="skel-bone" style="width: 44px; height: 18px; border-radius: 999px;"></div>
          </div>
          <div class="skel-bone" style="width: 95px; height: 15px;"></div>
          <div class="pt-1 border-t d-flex align-center justify-space-between">
            <div class="skel-bone" style="width: 85px; height: 11px;"></div>
            <div class="skel-bone skel-circle" style="width: 14px; height: 14px;"></div>
          </div>
        </div>
      </div>

      <div v-else-if="inventoryDays.length === 0" class="text-center pa-6 text-medium-emphasis border rounded-xl">
        <v-icon size="32" color="grey" class="mb-1">mdi-calendar-blank-outline</v-icon>
        <div class="text-caption font-weight-medium">{{ $t('no_inventory_days_found') || 'Aucun inventaire enregistré trouvé.' }}</div>
      </div>

      <div v-else>
        <!-- Horizontal Scrollable Inventory Cards Bar -->
        <div class="wh-inv-scroll-strip d-flex ga-3 overflow-x-auto pb-2">
          <div
            v-for="inv in inventoryDays"
            :key="inv.date"
            class="wh-inv-pill-card pa-3 rounded-xl border cursor-pointer transition-all flex-shrink-0"
            :class="{
              'is-active': selectedInventoryDate === inv.date,
              'is-latest': inv.is_latest,
            }"
            @click="selectInventoryDay(inv)"
          >
            <div class="d-flex align-center justify-space-between ga-2 mb-1.5">
              <span class="text-subtitle-2 font-weight-bold font-mono text-high-emphasis">
                {{ inv.date_formatted }}
              </span>
              <v-chip
                v-if="inv.is_latest"
                size="x-small"
                color="teal"
                variant="flat"
                class="font-weight-bold text-white px-1.5"
              >
                {{ $t('latest_inventory') || 'Dernier' }}
              </v-chip>
            </div>

            <div class="text-caption font-mono text-teal font-weight-bold">
              {{ main.formatDisplayNumber(inv.total_weight) }} Kg
            </div>

            <div class="text-caption text-medium-emphasis d-flex align-center justify-space-between mt-1 pt-1 border-t" style="font-size: 11px !important;">
              <span>{{ inv.items_count }} arts • {{ inv.warehouses_count }} ch.</span>
              <v-icon
                v-if="selectedInventoryDate === inv.date"
                size="14"
                color="#a21ee5"
              >mdi-check-circle</v-icon>
            </div>
          </div>
        </div>

        <!-- Featured Active Baseline Summary Banner -->
        <div v-if="selectedInventoryObject" class="wh-baseline-summary mt-3 pa-3 rounded-xl border d-flex align-center justify-space-between flex-wrap ga-2">
          <div class="d-flex align-center ga-3 flex-wrap">
            <v-chip size="small" color="#a21ee5" variant="flat" class="font-weight-bold text-white px-2.5">
              <v-icon start size="13">mdi-check-circle-outline</v-icon>
              {{ selectedInventoryObject.date_formatted }}
            </v-chip>
            <span class="text-caption text-medium-emphasis">
              Stock physique : <strong class="text-teal font-mono">{{ main.formatDisplayNumber(selectedInventoryObject.total_weight) }} Kg</strong> •
              <strong class="text-high-emphasis font-mono">{{ selectedInventoryObject.items_count }}</strong> articles •
              <strong class="text-high-emphasis font-mono">{{ selectedInventoryObject.warehouses_count }}</strong> chambres froides
            </span>
          </div>

          <!-- Quick button to select warehouses of this inventory -->
          <v-btn
            v-if="selectedInventoryObject.warehouses && selectedInventoryObject.warehouses.length"
            size="x-small"
            variant="tonal"
            color="#a21ee5"
            rounded="pill"
            class="text-none font-weight-bold"
            @click="selectWarehousesFromInventory"
          >
            <v-icon size="12" class="mr-1">mdi-filter-check-outline</v-icon>
            {{ $t('select_wh_from_inventory') || 'Cocher les entrepôts de cet inventaire' }} ({{ selectedInventoryObject.warehouses.length }})
          </v-btn>
        </div>
      </div>
    </div>

    <!-- MODE B: PLAGE PERSONNALISÉE (VCALENDAR) -->
    <div v-else class="wh-custom-range-box py-1">
      <v-row dense align="center">
        <v-col cols="12" md="7">
          <div class="mb-1 text-caption font-weight-bold text-medium-emphasis d-flex align-center justify-space-between">
            <span class="d-flex align-center ga-1">
              <v-icon size="14" color="#a21ee5">mdi-calendar-range</v-icon>
              {{ $t('recalculation_date_range') || 'Plage de Dates Personnalisée' }}
              <v-chip size="x-small" color="purple" variant="tonal" class="font-weight-bold ml-1">
                {{ rangeDaysCount }} {{ $t('days') || 'jours' }}
              </v-chip>
            </span>
            <v-btn
              size="x-small"
              variant="text"
              color="#a21ee5"
              class="font-weight-bold text-none px-1"
              :prepend-icon="calendarExpanded ? 'mdi-chevron-up' : 'mdi-calendar-expand-horizontal'"
              @click="calendarExpanded = !calendarExpanded"
            >
              {{ calendarExpanded ? ($t('collapse_calendar') || 'Masquer') : ($t('expand_calendar') || 'Afficher Calendrier') }}
            </v-btn>
          </div>

          <v-text-field
            :model-value="formattedRangeDisplay"
            readonly
            density="compact"
            variant="outlined"
            rounded
            color="#a21ee5"
            hide-details
            prepend-inner-icon="mdi-calendar-range"
            :append-inner-icon="calendarExpanded ? 'mdi-chevron-up' : 'mdi-chevron-down'"
            @click="calendarExpanded = !calendarExpanded"
            class="wh-recalc-input cursor-pointer"
            style="cursor: pointer;"
          ></v-text-field>

          <v-expand-transition>
            <div v-if="calendarExpanded" class="wh-vcalendar-inner rounded-2xl overflow-hidden pa-3 mt-2 border">
              <DatePicker
                v-model.range="dateRangeVC"
                mode="date"
                color="purple"
                expanded
                borderless
                :columns="$vuetify.display.mdAndUp ? 2 : 1"
                :is-dark="main.is_dark"
                :locale="main.lang?.short || 'fr'"
                :max-date="new Date()"
                class="w-100 wh-vcalendar"
              />
              <div class="d-flex align-center justify-end mt-2 pt-2 border-t">
                <v-btn
                  size="x-small"
                  variant="tonal"
                  color="#a21ee5"
                  rounded="lg"
                  class="font-weight-bold"
                  @click="calendarExpanded = false"
                >
                  {{ $t('done') || 'Terminé' }}
                </v-btn>
              </div>
            </div>
          </v-expand-transition>
        </v-col>

        <v-col cols="12" md="5">
          <div class="text-caption text-medium-emphasis mb-1 font-weight-bold">{{ $t('quick_presets') || 'Raccourcis' }}:</div>
          <div class="d-flex flex-wrap ga-1">
            <v-btn
              v-for="preset in datePresets"
              :key="preset.id"
              size="x-small"
              rounded="pill"
              variant="tonal"
              color="#a21ee5"
              class="font-weight-medium"
              @click="applyDatePreset(preset)"
            >
              {{ preset.label }}
            </v-btn>
          </div>
        </v-col>
      </v-row>
    </div>
  </v-card>

  <!-- STEP 2: RECALCULATION MODE & THE CASCADE SWITCH -->
  <v-card class="wh-section-card mb-4 rounded-2xl border pa-4 pa-md-5" elevation="0">
    <div class="d-flex align-center justify-space-between mb-3 pb-3 border-b flex-wrap ga-2">
      <div class="d-flex align-center ga-2">
        <v-avatar size="30" color="rgba(162, 30, 229, 0.12)">
          <v-icon size="16" color="#a21ee5">mdi-source-branch-sync</v-icon>
        </v-avatar>
        <div>
          <span class="text-subtitle-2 font-weight-bold text-high-emphasis d-block">
            {{ $t('recalculation_parameters') || '2. Mode d\'Exécution & Enchaînement des Inventaires' }}
          </span>
          <span class="text-caption text-medium-emphasis">
            Choisissez si le calcul s'arrête avant le prochain inventaire ou s'il s'enchaîne en cascade jusqu'à aujourd'hui.
          </span>
        </div>
      </div>
    </div>

    <!-- Dual Interactive Selection Cards (Mode A: Période Unique vs Mode B: Cascade) -->
    <v-row dense class="mb-3">
      <!-- OPTION 1: PÉRIODE UNIQUE -->
      <v-col cols="12" md="6">
        <div
          class="wh-mode-card pa-3.5 rounded-2xl border cursor-pointer transition-all"
          :class="{ 'is-selected': !cascadeAllInventories }"
          @click="cascadeAllInventories = false"
        >
          <div class="d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center ga-2">
              <v-avatar size="32" :color="!cascadeAllInventories ? '#a21ee5' : 'rgba(162, 30, 229, 0.12)'">
                <v-icon size="18" :color="!cascadeAllInventories ? 'white' : '#a21ee5'">mdi-calendar-end</v-icon>
              </v-avatar>
              <div>
                <span class="text-subtitle-2 font-weight-bold text-high-emphasis d-block">
                  Période Unique (Arrêt avant prochain inventaire)
                </span>
                <span class="text-caption font-mono text-purple font-weight-bold">
                  {{ main.formateDate(effectiveDateFrom) }} ➔ {{ main.formateDate(effectiveDateTo) }}
                </span>
              </div>
            </div>
            <v-icon
              size="20"
              :color="!cascadeAllInventories ? '#a21ee5' : 'grey-lighten-1'"
            >
              {{ !cascadeAllInventories ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank' }}
            </v-icon>
          </div>

          <p class="text-caption text-medium-emphasis mb-0" style="line-height: 1.45;">
            {{ $t('cascade_all_inventories_desc_off') || 'Le recalcul débute à la date d\'inventaire choisie et s\'arrête strictement la veille de l\'inventaire suivant, afin de préserver l\'intégrité du stock physique suivant.' }}
          </p>

          <div v-if="selectedInventoryObject && selectedInventoryObject.next_inventory_date" class="mt-2 pt-2 border-t text-caption d-flex align-center justify-space-between">
            <span class="text-medium-emphasis">Inventaire suivant :</span>
            <span class="font-weight-bold font-mono text-purple">{{ selectedInventoryObject.next_inventory_date_formatted }}</span>
          </div>
        </div>
      </v-col>

      <!-- OPTION 2: CASCADE COMPLÈTE -->
      <v-col cols="12" md="6">
        <div
          class="wh-mode-card pa-3.5 rounded-2xl border cursor-pointer transition-all"
          :class="{ 'is-selected': cascadeAllInventories }"
          @click="cascadeAllInventories = true"
        >
          <div class="d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center ga-2">
              <v-avatar size="32" :color="cascadeAllInventories ? 'teal' : 'rgba(0, 137, 123, 0.12)'">
                <v-icon size="18" :color="cascadeAllInventories ? 'white' : 'teal'">mdi-source-branch-sync</v-icon>
              </v-avatar>
              <div>
                <span class="text-subtitle-2 font-weight-bold text-high-emphasis d-block">
                  Cascade Complète (Jusqu'à aujourd'hui)
                </span>
                <span class="text-caption font-mono text-teal font-weight-bold">
                  {{ main.formateDate(effectiveDateFrom) }} ➔ {{ todayFormatted }} (Aujourd'hui)
                </span>
              </div>
            </div>
            <v-icon
              size="20"
              :color="cascadeAllInventories ? 'teal' : 'grey-lighten-1'"
            >
              {{ cascadeAllInventories ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank' }}
            </v-icon>
          </div>

          <p class="text-caption text-medium-emphasis mb-0" style="line-height: 1.45;">
            {{ $t('cascade_all_inventories_desc_on') || 'Le recalcul traite chaque intervalle, applique les nouvelles valeurs physiques de l\'inventaire suivant, puis continue étape par étape jusqu\'à la date actuelle.' }}
          </p>

          <div class="mt-2 pt-2 border-t text-caption d-flex align-center justify-space-between">
            <span class="text-medium-emphasis">Chaîne séquentielle :</span>
            <v-chip size="x-small" color="teal" variant="tonal" class="font-weight-bold font-mono">
              {{ computedPeriods.length }} étapes jusqu'à aujourd'hui
            </v-chip>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Chronological Stepper Preview of the Stages -->
    <div v-if="computedPeriods.length > 0" class="wh-stages-timeline pa-3 rounded-xl border mb-3">
      <div class="text-caption font-weight-bold text-medium-emphasis mb-2 d-flex align-center justify-space-between">
        <span class="d-flex align-center ga-1">
          <v-icon size="13" color="#a21ee5">mdi-timeline-outline</v-icon>
          {{ $t('recalculation_stages_timeline') || 'Chronologie des Étapes de Recalcul' }}
        </span>
        <span class="text-caption text-medium-emphasis font-mono">{{ computedPeriods.length }} étape(s)</span>
      </div>

      <div class="d-flex align-center flex-wrap ga-2">
        <div
          v-for="(p, pi) in computedPeriods"
          :key="pi"
          class="wh-stage-chip pa-2 px-3 rounded-xl border d-flex align-center ga-2 text-caption"
        >
          <span class="font-weight-bold text-purple font-mono">#{{ p.stage }}</span>
          <span class="font-weight-bold font-mono">{{ main.formateDate(p.date_from) }}</span>
          <v-icon size="11" color="grey">mdi-arrow-right</v-icon>
          <span class="font-weight-bold font-mono" :class="p.date_to === todayDateStr ? 'text-teal' : ''">{{ main.formateDate(p.date_to) }}</span>

          <v-chip
            v-if="p.next_inventory_date"
            size="x-small"
            color="purple"
            variant="tonal"
            class="font-weight-medium"
          >
            Inv. {{ main.formateDate(p.next_inventory_date) }}
          </v-chip>
          <v-chip
            v-else
            size="x-small"
            color="teal"
            variant="flat"
            class="font-weight-bold text-white"
          >
            Aujourd'hui
          </v-chip>
        </div>
      </div>
    </div>

    <!-- Warehouse Scope & Modules -->
    <v-row dense align="center">
      <!-- Warehouse Selector -->
      <v-col cols="12" md="6">
        <div class="mb-1 text-caption font-weight-bold text-medium-emphasis d-flex align-center ga-1">
          <v-icon size="14" color="#a21ee5">mdi-warehouse</v-icon>
          <span>{{ $t('warehouse_scope') || 'Périmètre des Entrepôts' }}</span>
        </div>
        <v-autocomplete
          v-model="selectedWarehouseIds"
          :items="warehousesList"
          item-title="name"
          item-value="id"
          multiple
          chips
          closable-chips
          density="compact"
          variant="outlined"
          rounded
          color="#a21ee5"
          hide-details
          prepend-inner-icon="mdi-home-analytics"
          :placeholder="$t('all_warehouses_scope') || 'Tous les entrepôts (global)'"
          class="wh-recalc-input"
        >
          <template #chip="{ item, props }">
            <v-chip v-bind="props" size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Recalculation Modules Chips -->
      <v-col cols="12" md="6">
        <div class="mb-1 text-caption font-weight-bold text-medium-emphasis d-flex align-center ga-1">
          <v-icon size="14" color="#a21ee5">mdi-cog-sync-outline</v-icon>
          <span>{{ $t('recalculation_modules') || 'Composantes Incluses' }}</span>
        </div>
        <div class="d-flex align-center flex-wrap ga-2 pt-1">
          <v-chip
            filter
            size="small"
            :color="options.sync_source_documents ? '#a21ee5' : 'grey'"
            :variant="options.sync_source_documents ? 'tonal' : 'outlined'"
            class="font-weight-medium cursor-pointer"
            @click="options.sync_source_documents = !options.sync_source_documents"
          >
            <v-icon start size="14">mdi-file-document-sync</v-icon>
            {{ $t('recalc_sync_sources') || 'Sources' }}
          </v-chip>

          <v-chip
            filter
            size="small"
            :color="options.recalculate_wap ? '#a21ee5' : 'grey'"
            :variant="options.recalculate_wap ? 'tonal' : 'outlined'"
            class="font-weight-medium cursor-pointer"
            @click="options.recalculate_wap = !options.recalculate_wap"
          >
            <v-icon start size="14">mdi-currency-usd</v-icon>
            {{ $t('recalc_wap_prices') || 'Prix WAP / PMP' }}
          </v-chip>

          <v-chip
            filter
            size="small"
            :color="options.recalculate_operations ? '#a21ee5' : 'grey'"
            :variant="options.recalculate_operations ? 'tonal' : 'outlined'"
            class="font-weight-medium cursor-pointer"
            @click="options.recalculate_operations = !options.recalculate_operations"
          >
            <v-icon start size="14">mdi-scale-balance</v-icon>
            {{ $t('recalc_operations') || 'Soldes & Poids' }}
          </v-chip>

          <v-chip
            filter
            size="small"
            :color="options.fix_reset_checkpoints ? '#a21ee5' : 'grey'"
            :variant="options.fix_reset_checkpoints ? 'tonal' : 'outlined'"
            class="font-weight-medium cursor-pointer"
            @click="options.fix_reset_checkpoints = !options.fix_reset_checkpoints"
          >
            <v-icon start size="14">mdi-shield-refresh</v-icon>
            {{ $t('recalc_reset_checkpoints') || 'Reset Checkpoints' }}
          </v-chip>
        </div>
      </v-col>
    </v-row>
  </v-card>

  <!-- STEP 3: SIMULATION & RECALCULATION RESULTS SECTION -->
  <v-expand-transition>
    <div v-if="results" class="wh-results-section mb-4">
      <!-- Status Feedback Alert -->
      <v-alert
        :type="results.mode === 'execute' ? 'success' : 'info'"
        variant="tonal"
        class="mb-3 rounded-2xl border"
      >
        <template #title>
          <div class="d-flex align-center justify-space-between flex-wrap ga-2 font-weight-bold">
            <span>
              {{ results.mode === 'execute'
                ? ($t('recalculation_applied_title') || 'Recalcul Appliqué avec Succès dans la Base de Données')
                : ($t('simulation_completed_title') || 'Simulation Terminée — Aperçu des Changements Détectés')
              }}
            </span>
            <v-chip size="x-small" :color="results.mode === 'execute' ? 'success' : 'info'" variant="flat" class="font-weight-bold text-white">
              {{ results.duration_seconds }}s • {{ results.periods.length }} étape(s)
            </v-chip>
          </div>
        </template>
        <div class="text-caption mt-1">
          {{ results.mode === 'execute'
            ? ($t('recalculation_applied_desc') || 'Tous les stocks, soldes et valorisations ont été recalculés et synchronisés en temps réel via WebSockets.')
            : ($t('simulation_completed_desc') || 'Aucune modification n\'a été enregistrée. Examinez ci-dessous les ajustements proposés.')
          }}
        </div>
      </v-alert>

      <!-- Spacious Results KPI Cards Strip (matching warehouses/index.vue) -->
      <v-row dense class="mb-3">
        <!-- 1. Chambers & Balances Scanned -->
        <v-col cols="12" sm="6" md="3">
          <div class="wh-spacious-kpi border">
            <div class="wh-spacious-kpi-icon" style="background: rgba(162, 30, 229, 0.12);">
              <v-icon color="#a21ee5" size="22">mdi-warehouse</v-icon>
            </div>
            <div class="wh-spacious-kpi-info">
              <span class="wh-spacious-kpi-label">{{ $t('scope_analyzed') || 'Chambres Analysées' }}</span>
              <div class="wh-spacious-kpi-val" style="color: #a21ee5;">
                {{ results.warehouses.length }} <span class="wh-unit">chambres</span>
              </div>
              <div class="wh-spacious-kpi-sub mt-0.5">
                <span>{{ results.summary.balances_scanned }} {{ $t('lots_analyzed') || 'soldes scannés' }}</span>
              </div>
            </div>
          </div>
        </v-col>

        <!-- 2. Discrepancies & Adjustments -->
        <v-col cols="12" sm="6" md="3">
          <div class="wh-spacious-kpi border">
            <div
              class="wh-spacious-kpi-icon"
              :style="{ background: results.summary.balances_updated > 0 ? 'rgba(217, 119, 6, 0.12)' : 'rgba(5, 150, 105, 0.12)' }"
            >
              <v-icon :color="results.summary.balances_updated > 0 ? '#D97706' : '#059669'" size="22">
                {{ results.summary.balances_updated > 0 ? 'mdi-alert-circle-outline' : 'mdi-check-decagram-outline' }}
              </v-icon>
            </div>
            <div class="wh-spacious-kpi-info">
              <span class="wh-spacious-kpi-label">{{ $t('discrepancies_detected') || 'Écarts Détectés' }}</span>
              <div class="wh-spacious-kpi-val" :class="results.summary.balances_updated > 0 ? 'text-warning' : 'text-success'">
                {{ results.summary.balances_updated }} <span class="wh-unit">lignes</span>
              </div>
              <div class="wh-spacious-kpi-sub mt-0.5">
                <span>{{ results.summary.operations_scanned }} opérations vérifiées</span>
              </div>
            </div>
          </div>
        </v-col>

        <!-- 3. Total Stock Weight Evolution -->
        <v-col cols="12" sm="6" md="3">
          <div class="wh-spacious-kpi border">
            <div class="wh-spacious-kpi-icon" style="background: rgba(0, 137, 123, 0.12);">
              <v-icon color="#00897B" size="22">mdi-scale</v-icon>
            </div>
            <div class="wh-spacious-kpi-info">
              <span class="wh-spacious-kpi-label">{{ $t('recalculated_weight') || 'Poids Total Recalculé' }}</span>
              <div class="wh-spacious-kpi-val text-teal font-mono">
                {{ main.formatDisplayNumber(results.summary.weight_after) }} <span class="wh-unit">Kg</span>
              </div>
              <div class="wh-spacious-kpi-sub mt-0.5">
                <span>{{ main.formatDisplayNumber(results.summary.weight_before) }} ➔ </span>
                <v-chip
                  v-if="results.summary.weight_diff !== 0"
                  size="x-small"
                  :color="results.summary.weight_diff > 0 ? 'teal' : 'warning'"
                  variant="tonal"
                  class="font-weight-bold ml-1"
                >
                  {{ results.summary.weight_diff > 0 ? '+' : '' }}{{ main.formatDisplayNumber(results.summary.weight_diff) }} Kg
                </v-chip>
              </div>
            </div>
          </div>
        </v-col>

        <!-- 4. Stock Valuation Evolution -->
        <v-col cols="12" sm="6" md="3">
          <div class="wh-spacious-kpi border">
            <div class="wh-spacious-kpi-icon" style="background: rgba(230, 81, 0, 0.12);">
              <v-icon color="#E65100" size="22">mdi-cash-multiple</v-icon>
            </div>
            <div class="wh-spacious-kpi-info">
              <span class="wh-spacious-kpi-label">{{ $t('recalculated_valuation') || 'Valorisation Stock' }}</span>
              <div class="wh-spacious-kpi-val font-mono" style="color: #E65100;">
                {{ main.formatDisplayNumber(results.summary.valuation_after) }} <span class="wh-unit">{{ main.currency }}</span>
              </div>
              <div class="wh-spacious-kpi-sub mt-0.5">
                <span>{{ main.formatDisplayNumber(results.summary.valuation_before) }} ➔ </span>
                <v-chip
                  v-if="results.summary.valuation_diff !== 0"
                  size="x-small"
                  :color="results.summary.valuation_diff > 0 ? 'success' : 'warning'"
                  variant="tonal"
                  class="font-weight-bold ml-1"
                >
                  {{ results.summary.valuation_diff > 0 ? '+' : '' }}{{ main.formatDisplayNumber(results.summary.valuation_diff) }}
                </v-chip>
              </div>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Warehouses Breakdown Table Card -->
      <v-card class="wh-table-card mb-4 rounded-2xl border pa-3 pa-md-4" elevation="0">
        <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2 border-b pb-2">
          <div class="d-flex align-center ga-2">
            <v-icon color="#a21ee5" size="18">mdi-table-pivot</v-icon>
            <span class="text-subtitle-2 font-weight-bold text-high-emphasis">
              {{ $t('warehouse_recalculation_breakdown') || 'Synthèse par Entrepôt & Chambre Froide' }}
            </span>
          </div>
        </div>

        <div class="wh-table-responsive rounded-xl border overflow-hidden">
          <table class="wh-styled-table w-100">
            <thead>
              <tr>
                <th class="text-left pa-2.5">{{ $t('warehouse') || 'Entrepôt' }}</th>
                <th class="text-center pa-2.5">{{ $t('current_weight_label') || 'Poids Actuel' }}</th>
                <th class="text-center pa-2.5">{{ $t('recalculated_weight_label') || 'Poids Recalculé' }}</th>
                <th class="text-center pa-2.5">{{ $t('difference_label') || 'Écart Poids' }}</th>
                <th class="text-center pa-2.5">{{ $t('current_valuation_label') || 'Valo Actuelle' }}</th>
                <th class="text-center pa-2.5">{{ $t('recalculated_valuation') || 'Valo Recalculée' }}</th>
                <th class="text-center pa-2.5">{{ $t('difference_label') || 'Écart Valo' }}</th>
                <th class="text-center pa-2.5">{{ $t('balances_adjusted') || 'Lignes Ajustées' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="w in results.warehouses" :key="w.warehouse_id">
                <td class="pa-2.5 font-weight-bold">{{ w.warehouse_name }}</td>
                <td class="text-center pa-2.5 font-mono">{{ main.formatDisplayNumber(w.current_weight) }} Kg</td>
                <td class="text-center pa-2.5 font-mono font-weight-bold text-teal">{{ main.formatDisplayNumber(w.recalculated_weight) }} Kg</td>
                <td class="text-center pa-2.5">
                  <v-chip
                    v-if="w.weight_diff !== 0"
                    size="x-small"
                    :color="w.weight_diff > 0 ? 'teal' : 'warning'"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    {{ w.weight_diff > 0 ? '+' : '' }}{{ main.formatDisplayNumber(w.weight_diff) }} Kg
                  </v-chip>
                  <span v-else class="text-medium-emphasis">-</span>
                </td>
                <td class="text-center pa-2.5 font-mono">{{ main.formatDisplayNumber(w.current_valuation) }} {{ main.currency }}</td>
                <td class="text-center pa-2.5 font-mono font-weight-bold" style="color: #FF7043;">{{ main.formatDisplayNumber(w.recalculated_valuation) }} {{ main.currency }}</td>
                <td class="text-center pa-2.5">
                  <v-chip
                    v-if="w.valuation_diff !== 0"
                    size="x-small"
                    :color="w.valuation_diff > 0 ? 'success' : 'warning'"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    {{ w.valuation_diff > 0 ? '+' : '' }}{{ main.formatDisplayNumber(w.valuation_diff) }}
                  </v-chip>
                  <span v-else class="text-medium-emphasis">-</span>
                </td>
                <td class="text-center pa-2.5">
                  <v-chip
                    size="x-small"
                    :color="w.balances_affected > 0 ? '#a21ee5' : 'grey'"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    {{ w.balances_affected }}
                  </v-chip>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </v-card>

      <!-- Discrepancies Detailed Data Table -->
      <v-card class="wh-table-card rounded-2xl border pa-3 pa-md-4" elevation="0">
        <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2 border-b pb-2">
          <div class="d-flex align-center ga-2">
            <v-icon color="#a21ee5" size="18">mdi-clipboard-list-outline</v-icon>
            <span class="text-subtitle-2 font-weight-bold text-high-emphasis">
              {{ $t('discrepancies_list_title') || 'Détail des Ajustements & Écarts Détectés' }}
            </span>
            <v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">
              {{ filteredDiscrepancies.length }} / {{ results.discrepancies.length }}
            </v-chip>
          </div>

          <!-- Discrepancies Search Field -->
          <div class="d-flex align-center ga-2 flex-grow-1 justify-end" style="max-width: 320px;">
            <v-text-field
              v-model="discrepancySearch"
              density="compact"
              variant="outlined"
              rounded
              color="#a21ee5"
              hide-details
              clearable
              prepend-inner-icon="mdi-magnify"
              :placeholder="$t('search_discrepancies_placeholder') || 'Filtrer par lot, article...'"
              class="wh-recalc-input w-100"
            ></v-text-field>
          </div>
        </div>

        <div v-if="filteredDiscrepancies.length === 0" class="text-center pa-8 text-medium-emphasis">
          <v-icon size="32" color="grey" class="mb-1">mdi-check-circle-outline</v-icon>
          <div class="font-weight-medium text-caption">{{ $t('no_discrepancies_found') || 'Aucun écart détecté dans les filtres sélectionnés.' }}</div>
        </div>

        <div v-else class="wh-table-responsive rounded-xl border overflow-hidden">
          <table class="wh-styled-table w-100">
            <thead>
              <tr>
                <th class="text-left pa-2.5">{{ $t('lot_number') || 'N° de Suivi / Lot' }}</th>
                <th class="text-center pa-2.5">{{ $t('date') || 'Date' }}</th>
                <th class="text-left pa-2.5">{{ $t('warehouse') || 'Entrepôt' }}</th>
                <th class="text-left pa-2.5">{{ $t('article') || 'Article' }}</th>
                <th class="text-center pa-2.5">{{ $t('remaining_weight') || 'Poids Restant' }}</th>
                <th class="text-center pa-2.5">{{ $t('unit_price') || 'Prix U. / WAP' }}</th>
                <th class="text-center pa-2.5">{{ $t('valuation') || 'Valorisation' }}</th>
                <th class="text-left pa-2.5">{{ $t('reasons') || 'Raisons' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in paginatedDiscrepancies" :key="d.balance_id">
                <td class="pa-2.5 font-weight-bold">
                  <v-chip size="x-small" color="teal" variant="tonal" class="font-weight-bold font-mono">
                    {{ d.lot_number }}
                  </v-chip>
                </td>
                <td class="text-center pa-2.5 font-mono text-caption text-medium-emphasis">{{ main.formateDate(d.date) }}</td>
                <td class="pa-2.5 text-caption font-weight-medium">{{ d.warehouse_name }}</td>
                <td class="pa-2.5 font-weight-bold text-high-emphasis">
                  <div class="text-caption font-weight-bold">{{ d.article_name }}</div>
                  <span class="text-caption text-medium-emphasis" style="font-size: 10px !important;">{{ d.stockable_type }}</span>
                </td>
                <td class="text-center pa-2.5 font-mono">
                  <div class="d-flex align-center justify-center ga-1 text-caption">
                    <span class="text-medium-emphasis">{{ main.formatDisplayNumber(d.weight_before) }}</span>
                    <v-icon size="11" color="grey">mdi-arrow-right</v-icon>
                    <span class="font-weight-bold text-teal">{{ main.formatDisplayNumber(d.weight_after) }} Kg</span>
                  </div>
                </td>
                <td class="text-center pa-2.5 font-mono font-weight-bold">{{ main.formatDisplayNumber(d.unit_price) }}</td>
                <td class="text-center pa-2.5 font-mono">
                  <div class="d-flex align-center justify-center ga-1 text-caption">
                    <span class="text-medium-emphasis">{{ main.formatDisplayNumber(d.valuation_before) }}</span>
                    <v-icon size="11" color="grey">mdi-arrow-right</v-icon>
                    <span class="font-weight-bold" style="color: #FF7043;">{{ main.formatDisplayNumber(d.valuation_after) }}</span>
                  </div>
                </td>
                <td class="pa-2.5 text-caption">
                  <div class="d-flex flex-column ga-1">
                    <v-chip
                      v-for="(r, ri) in d.reasons"
                      :key="ri"
                      size="x-small"
                      color="purple"
                      variant="tonal"
                      class="font-weight-medium"
                    >
                      {{ r }}
                    </v-chip>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div v-if="filteredDiscrepancies.length > discrepancyPageSize" class="d-flex align-center justify-space-between mt-3 flex-wrap ga-2 pt-2 border-t">
          <span class="text-caption text-medium-emphasis">
            {{ (discrepancyPage - 1) * discrepancyPageSize + 1 }} - {{ Math.min(discrepancyPage * discrepancyPageSize, filteredDiscrepancies.length) }} sur {{ filteredDiscrepancies.length }}
          </span>
          <v-pagination
            v-model="discrepancyPage"
            :length="Math.ceil(filteredDiscrepancies.length / discrepancyPageSize)"
            density="compact"
            size="small"
            color="#a21ee5"
            total-visible="5"
          ></v-pagination>
        </div>
      </v-card>
    </div>
  </v-expand-transition>

  <!-- Execution Confirmation Dialog -->
  <v-dialog v-model="confirmExecuteDialog" max-width="500">
    <v-card class="rounded-2xl overflow-hidden elevation-8 border">
      <div class="pa-4" style="background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%); color: white;">
        <div class="d-flex align-center ga-3">
          <v-avatar size="36" color="rgba(255, 255, 255, 0.2)">
            <v-icon color="white" size="22">mdi-alert-decagram-outline</v-icon>
          </v-avatar>
          <div>
            <h3 class="text-subtitle-1 font-weight-bold text-white mb-0">{{ $t('confirm_recalculation_title') || 'Confirmer le Recalcul des Entrepôts' }}</h3>
            <span class="text-caption text-white" style="opacity: 0.9;">{{ $t('database_update_confirmation') || 'Cette action va synchroniser la base de données' }}</span>
          </div>
        </div>
      </div>

      <v-card-text class="pa-4 pa-md-5">
        <p class="text-body-2 text-medium-emphasis mb-3">
          {{ $t('recalculation_confirm_message') || 'Vous êtes sur le point de recalculer l\'ensemble des stocks, poids restants, opérations et valorisations pour la période suivante :' }}
        </p>

        <div class="pa-3 rounded-xl border mb-3 bg-surface">
          <div class="d-flex align-center justify-space-between text-caption mb-1">
            <span class="text-medium-emphasis">{{ $t('period') || 'Période' }}:</span>
            <span class="font-weight-bold font-mono">{{ main.formateDate(effectiveDateFrom) }} ➔ {{ main.formateDate(effectiveDateTo) }}</span>
          </div>
          <div class="d-flex align-center justify-space-between text-caption mb-1">
            <span class="text-medium-emphasis">Mode Cascade:</span>
            <span class="font-weight-bold" :class="cascadeAllInventories ? 'text-teal' : 'text-purple'">
              {{ cascadeAllInventories ? 'Oui (À travers tous les inventaires)' : 'Non (Arrêt avant prochain inventaire)' }}
            </span>
          </div>
          <div class="d-flex align-center justify-space-between text-caption">
            <span class="text-medium-emphasis">{{ $t('scope') || 'Périmètre' }}:</span>
            <span class="font-weight-bold">{{ selectedWarehouseIds.length ? `${selectedWarehouseIds.length} entrepôt(s)` : ($t('all_warehouses') || 'Tous les entrepôts') }}</span>
          </div>
        </div>

        <v-alert type="warning" variant="tonal" density="compact" class="text-caption rounded-lg">
          {{ $t('recalc_broadcast_notice') || 'Toutes les sessions connectées recevront immédiatement une notification WebSocket pour rafraîchir leur stock.' }}
        </v-alert>
      </v-card-text>

      <v-card-actions class="pa-4 pt-0 d-flex align-center justify-end ga-2">
        <v-btn
          variant="text"
          rounded
          @click="confirmExecuteDialog = false"
        >
          {{ $t('cancel') || 'Annuler' }}
        </v-btn>
        <v-btn
          variant="flat"
          color="#a21ee5"
          rounded
          class="font-weight-bold text-white px-4"
          :loading="executeLoading"
          @click="runExecute"
        >
          {{ $t('confirm_and_recalculate') || 'Confirmer & Recalculer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Summary Snackbar (Rule 5) -->
  <v-snackbar
    v-model="localSnackbar.show"
    :color="localSnackbar.color"
    :timeout="localSnackbar.timeout"
    location="bottom right"
    class="wh-recalc-snackbar"
    rounded="xl"
    elevation="8"
  >
    <div class="d-flex align-center ga-3">
      <v-avatar size="32" color="rgba(255, 255, 255, 0.2)">
        <v-icon color="white" size="20">{{ localSnackbar.icon }}</v-icon>
      </v-avatar>
      <div>
        <div class="font-weight-bold text-subtitle-2 text-white">{{ localSnackbar.title }}</div>
        <div class="text-caption text-white" style="opacity: 0.92;">{{ localSnackbar.message }}</div>
      </div>
    </div>
    <template #actions>
      <v-btn variant="text" color="white" icon size="small" @click="localSnackbar.show = false">
        <v-icon size="16">mdi-close</v-icon>
      </v-btn>
    </template>
  </v-snackbar>
</div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';

export default {
  name: 'WarehouseRecalculation',

  components: {
    DatePicker,
    SegmentedPillToggle,
  },

  props: {
    warehouses: {
      type: Array,
      default: () => [],
    },
  },

  emits: ['recalculated'],

  data() {
    const today = moment().toDate();
    const startOfYear = moment().startOf('year').toDate();

    return {
      selectionMode: 'inventory', // 'inventory' | 'custom'
      loadingInventoryDays: false,
      inventoryDays: [],
      selectedInventoryDate: null,
      cascadeAllInventories: false,

      // Custom date range state
      dateRangeVC: {
        start: startOfYear,
        end: today,
      },
      calendarExpanded: false,

      selectedWarehouseIds: [],
      options: {
        sync_source_documents: true,
        recalculate_wap: true,
        recalculate_operations: true,
        fix_reset_checkpoints: true,
      },
      previewLoading: false,
      executeLoading: false,
      results: null,
      confirmExecuteDialog: false,
      discrepancySearch: '',
      discrepancyPage: 1,
      discrepancyPageSize: 15,
      localSnackbar: {
        show: false,
        title: '',
        message: '',
        color: '#a21ee5',
        icon: 'mdi-check-circle',
        timeout: 5000,
      },
    };
  },

  computed: {
    main() {
      return useMain();
    },
    auth() {
      return useAuth();
    },
    todayDateStr() {
      return moment().format('YYYY-MM-DD');
    },
    todayFormatted() {
      return moment().format('DD/MM/YYYY');
    },
    warehousesList() {
      return this.warehouses || [];
    },
    selectionModeOptions() {
      return [
        {
          value: 'inventory',
          label: this.$t('inventory_days_tab') || 'Inventaires Réels',
          icon: 'mdi-format-list-checks',
        },
        {
          value: 'custom',
          label: this.$t('custom_date_range_tab') || 'Plage Personnalisée',
          icon: 'mdi-calendar-range',
        },
      ];
    },
    selectedInventoryObject() {
      if (!this.selectedInventoryDate) return null;
      return this.inventoryDays.find(d => d.date === this.selectedInventoryDate) || null;
    },
    effectiveDateFrom() {
      if (this.selectionMode === 'inventory') {
        return this.selectedInventoryDate || this.todayDateStr;
      }
      return this.dateRangeVC?.start ? moment(this.dateRangeVC.start).format('YYYY-MM-DD') : this.todayDateStr;
    },
    effectiveDateTo() {
      if (this.selectionMode === 'inventory') {
        const inv = this.selectedInventoryObject;
        if (!inv) return this.todayDateStr;
        if (this.cascadeAllInventories) {
          return this.todayDateStr;
        }
        return inv.stop_date_default || this.todayDateStr;
      }
      return this.dateRangeVC?.end ? moment(this.dateRangeVC.end).format('YYYY-MM-DD') : this.todayDateStr;
    },
    computedPeriods() {
      if (this.selectionMode === 'custom') {
        return [{
          stage: 1,
          date_from: this.effectiveDateFrom,
          date_to: this.effectiveDateTo,
          next_inventory_date: null,
        }];
      }

      if (!this.selectedInventoryDate) return [];

      const invList = [...this.inventoryDays].sort((a, b) => a.date.localeCompare(b.date));
      const startIdx = invList.findIndex(d => d.date === this.selectedInventoryDate);
      if (startIdx === -1) return [];

      if (!this.cascadeAllInventories) {
        const cur = invList[startIdx];
        return [{
          stage: 1,
          date_from: cur.date,
          date_to: cur.stop_date_default || this.todayDateStr,
          next_inventory_date: cur.next_inventory_date,
        }];
      }

      // Cascade across all inventories until today
      const stages = [];
      let stageNum = 1;
      for (let i = startIdx; i < invList.length; i++) {
        const cur = invList[i];
        const isLast = (i === invList.length - 1);
        const stageEnd = isLast ? this.todayDateStr : (cur.stop_date_default || cur.date);

        stages.push({
          stage: stageNum++,
          date_from: cur.date,
          date_to: stageEnd,
          next_inventory_date: cur.next_inventory_date,
        });
      }
      return stages;
    },
    formattedRangeDisplay() {
      const s = this.dateRangeVC?.start ? moment(this.dateRangeVC.start).format('DD/MM/YYYY') : '';
      const e = this.dateRangeVC?.end ? moment(this.dateRangeVC.end).format('DD/MM/YYYY') : '';
      if (s && e) {
        return `${s}  ➔  ${e}  •  ${this.rangeDaysCount} ${this.$t('days') || 'jours'}`;
      }
      return s || '';
    },
    rangeDaysCount() {
      if (!this.dateRangeVC?.start || !this.dateRangeVC?.end) return 1;
      const diff = moment(this.dateRangeVC.end).diff(moment(this.dateRangeVC.start), 'days');
      return Math.max(1, diff + 1);
    },
    datePresets() {
      return [
        {
          id: 'year_start',
          label: this.$t('preset_year_start') || '1er Janvier',
          date: moment().startOf('year').format('YYYY-MM-DD'),
        },
        {
          id: 'month_start',
          label: this.$t('preset_month_start') || 'Début Mois',
          date: moment().startOf('month').format('YYYY-MM-DD'),
        },
        {
          id: 'last_30_days',
          label: this.$t('preset_last_30_days') || '30 Jours',
          date: moment().subtract(30, 'days').format('YYYY-MM-DD'),
        },
        {
          id: 'all_history',
          label: this.$t('preset_all_history') || 'Historique Complet',
          date: '2020-01-01',
        },
      ];
    },
    filteredDiscrepancies() {
      const list = this.results?.discrepancies || [];
      if (!this.discrepancySearch) return list;
      const q = this.discrepancySearch.toLowerCase().trim();
      return list.filter((item) => {
        return (
          (item.lot_number || '').toLowerCase().includes(q) ||
          (item.article_name || '').toLowerCase().includes(q) ||
          (item.warehouse_name || '').toLowerCase().includes(q) ||
          (item.stockable_type || '').toLowerCase().includes(q) ||
          (item.reasons || []).some((r) => r.toLowerCase().includes(q))
        );
      });
    },
    paginatedDiscrepancies() {
      const start = (this.discrepancyPage - 1) * this.discrepancyPageSize;
      return this.filteredDiscrepancies.slice(start, start + this.discrepancyPageSize);
    },
  },

  watch: {
    discrepancySearch() {
      this.discrepancyPage = 1;
    },
  },

  mounted() {
    this.fetchInventoryDays();
  },

  methods: {
    async fetchInventoryDays() {
      this.loadingInventoryDays = true;
      try {
        const response = await axios.get('/api/warehouses/recalculate/inventory-days');
        this.inventoryDays = response.data.inventory_days || [];
        if (this.inventoryDays.length > 0 && !this.selectedInventoryDate) {
          const withItems = this.inventoryDays.find(d => d.items_count > 0);
          this.selectedInventoryDate = withItems ? withItems.date : this.inventoryDays[0].date;
        }
      } catch (err) {
        console.error('Failed to load inventory days:', err);
      } finally {
        this.loadingInventoryDays = false;
      }
    },

    selectInventoryDay(inv) {
      this.selectedInventoryDate = inv.date;
    },

    selectWarehousesFromInventory() {
      if (this.selectedInventoryObject && this.selectedInventoryObject.warehouses) {
        this.selectedWarehouseIds = this.selectedInventoryObject.warehouses.map(w => w.id);
      }
    },

    applyDatePreset(preset) {
      this.dateRangeVC = {
        start: moment(preset.date).toDate(),
        end: moment().toDate(),
      };
    },

    buildPayload() {
      const isInvMode = (this.selectionMode === 'inventory');
      return {
        inventory_date: isInvMode ? this.selectedInventoryDate : undefined,
        date_from: this.effectiveDateFrom,
        date_to: this.effectiveDateTo,
        cascade_all_inventories: isInvMode ? this.cascadeAllInventories : false,
        warehouse_ids: this.selectedWarehouseIds.length ? this.selectedWarehouseIds : undefined,
        sync_source_documents: this.options.sync_source_documents,
        recalculate_wap: this.options.recalculate_wap,
        recalculate_operations: this.options.recalculate_operations,
        fix_reset_checkpoints: this.options.fix_reset_checkpoints,
      };
    },

    async runPreview() {
      this.previewLoading = true;
      try {
        const payload = this.buildPayload();
        const response = await axios.post('/api/warehouses/recalculate/preview', payload);
        this.results = response.data;
        this.discrepancyPage = 1;

        this.triggerSummarySnackbar({
          title: this.$t('simulation_ready_title') || 'Aperçu du Recalcul Prêt',
          message: `${response.data.summary.discrepancies_count || 0} ${this.$t('discrepancies_found_in_simulation') || 'écarts détectés dans la période'} (${response.data.periods.length} étapes)`,
          color: '#a21ee5',
          icon: 'mdi-eye-check-outline',
        });
      } catch (err) {
        console.error('Recalculation preview failed:', err);
        const errorMsg = err.response?.data?.message || this.$t('error_running_simulation') || 'Erreur lors de la simulation.';
        this.triggerSummarySnackbar({
          title: this.$t('error') || 'Erreur',
          message: errorMsg,
          color: 'error',
          icon: 'mdi-alert-circle',
        });
      } finally {
        this.previewLoading = false;
      }
    },

    async runExecute() {
      this.confirmExecuteDialog = false;
      this.executeLoading = true;
      try {
        const payload = this.buildPayload();
        const response = await axios.post('/api/warehouses/recalculate', payload);
        this.results = response.data;
        this.discrepancyPage = 1;

        // Rule 5: Trigger Summary Snackbar with Countdown Progress Bar
        this.triggerSummarySnackbar({
          title: this.$t('recalculation_successful_title') || 'Recalcul des Entrepôts Réussi',
          message: `${response.data.summary.balances_updated || 0} ${this.$t('balances_synchronized_msg') || 'lignes de stock synchronisées avec succès !'}`,
          color: '#00897B',
          icon: 'mdi-check-decagram',
        });

        // Notify parent to refresh all warehouse views
        this.$emit('recalculated', response.data);
      } catch (err) {
        console.error('Recalculation execution failed:', err);
        const errorMsg = err.response?.data?.message || this.$t('error_executing_recalculation') || 'Erreur lors de l\'exécution du recalcul.';
        this.triggerSummarySnackbar({
          title: this.$t('error') || 'Erreur',
          message: errorMsg,
          color: 'error',
          icon: 'mdi-alert-circle',
        });
      } finally {
        this.executeLoading = false;
      }
    },

    triggerSummarySnackbar({ title, message, color = '#a21ee5', icon = 'mdi-check-decagram' }) {
      this.localSnackbar = {
        show: true,
        title,
        message,
        color,
        icon,
        timeout: 5000,
      };
      if (this.$parent && typeof this.$parent.triggerSummarySnackbar === 'function') {
        this.$parent.triggerSummarySnackbar({ title, message, color, icon });
      }
    },
  },
};
</script>

<style scoped>
/* Toolbar Card */
.wh-toolbar-card {
  background: rgb(var(--v-theme-surface)) !important;
  border-color: rgba(162, 30, 229, 0.18) !important;
  box-shadow: 0 2px 12px rgba(162, 30, 229, 0.04);
}

.wh-toolbar-avatar {
  border: 1px solid rgba(162, 30, 229, 0.25);
}

.wh-pill-badge {
  background: rgba(var(--v-theme-surface-variant), 0.35);
  border-color: rgba(var(--v-border-color), 0.1) !important;
}

/* Sections */
.wh-section-card {
  background: rgb(var(--v-theme-surface)) !important;
  border-color: rgba(162, 30, 229, 0.15) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

/* Inventory Scroll Strip */
.wh-inv-scroll-strip {
  scrollbar-width: thin;
  scrollbar-color: rgba(162, 30, 229, 0.3) transparent;
}

.wh-inv-pill-card {
  min-width: 175px;
  background: rgb(var(--v-theme-surface));
  border-color: rgba(var(--v-border-color), 0.12) !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.wh-inv-pill-card:hover {
  transform: translateY(-2px);
  border-color: rgba(162, 30, 229, 0.45) !important;
  box-shadow: 0 4px 12px rgba(162, 30, 229, 0.08);
}

.wh-inv-pill-card.is-active {
  border: 2px solid #a21ee5 !important;
  background: rgba(162, 30, 229, 0.05) !important;
  box-shadow: 0 4px 14px rgba(162, 30, 229, 0.12) !important;
}

:deep(.v-theme--dark) .wh-inv-pill-card.is-active {
  background: rgba(162, 30, 229, 0.15) !important;
}

.wh-baseline-summary {
  background: rgba(162, 30, 229, 0.03);
  border-color: rgba(162, 30, 229, 0.18) !important;
}

:deep(.v-theme--dark) .wh-baseline-summary {
  background: rgba(162, 30, 229, 0.1) !important;
}

/* Interactive Mode Cards (Option A vs Option B) */
.wh-mode-card {
  background: rgb(var(--v-theme-surface));
  border-color: rgba(var(--v-border-color), 0.12) !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 120px;
}

.wh-mode-card:hover {
  border-color: rgba(162, 30, 229, 0.4) !important;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}

.wh-mode-card.is-selected {
  border: 2px solid #a21ee5 !important;
  background: rgba(162, 30, 229, 0.04) !important;
  box-shadow: 0 4px 16px rgba(162, 30, 229, 0.1) !important;
}

:deep(.v-theme--dark) .wh-mode-card.is-selected {
  background: rgba(162, 30, 229, 0.14) !important;
}

/* Chronological Stepper Preview */
.wh-stages-timeline {
  background: rgba(var(--v-theme-surface-variant), 0.2);
  border-color: rgba(162, 30, 229, 0.14) !important;
}

.wh-stage-chip {
  background: rgb(var(--v-theme-surface));
  border-color: rgba(162, 30, 229, 0.18) !important;
}

/* Spacious KPI Cards matching warehouses/index.vue */
.wh-spacious-kpi {
  background: rgb(var(--v-theme-surface));
  border-radius: 16px;
  border: 1px solid rgba(162, 30, 229, 0.15) !important;
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

/* Table Cards */
.wh-table-card {
  background: rgb(var(--v-theme-surface)) !important;
  border-color: rgba(162, 30, 229, 0.18) !important;
  box-shadow: 0 2px 12px rgba(162, 30, 229, 0.04);
}

.wh-styled-table {
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
}

.wh-styled-table thead th {
  background: rgba(162, 30, 229, 0.05);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #a21ee5;
  border-bottom: 2px solid rgba(162, 30, 229, 0.16);
  white-space: nowrap;
}

:deep(.v-theme--dark) .wh-styled-table thead th {
  background: rgba(162, 30, 229, 0.18);
  color: #d884f8;
}

.wh-styled-table tbody tr {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
  transition: background 0.15s ease;
}

.wh-styled-table tbody tr:hover {
  background: rgba(162, 30, 229, 0.04);
}

:deep(.v-theme--dark) .wh-styled-table tbody tr:hover {
  background: rgba(162, 30, 229, 0.1);
}

.wh-table-responsive {
  overflow-x: auto;
  border-color: rgba(var(--v-border-color), 0.12) !important;
}

/* AddCharge Form Input Styling */
.wh-recalc-input :deep(.v-field) {
  border-radius: 12px !important;
  transition: all 0.2s ease;
}

.wh-recalc-input :deep(.v-field:hover) {
  border-color: #a21ee5 !important;
}

/* VCalendar Styling */
.wh-vcalendar-inner {
  border-color: rgba(162, 30, 229, 0.18) !important;
  background: rgba(162, 30, 229, 0.02);
}

:deep(.v-theme--dark) .wh-vcalendar-inner {
  background: rgba(162, 30, 229, 0.08) !important;
}

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

:deep(.v-theme--dark) .wh-vcalendar .vc-title {
  color: #d884f8;
}
</style>
