<template>
  <div class="jupiter-summary-section mb-6">
    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- MASTER BREAKDOWN VIEW SELECTOR (Both / Charges / Shippings) -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-4 breakdown-master-nav pa-3 rounded-2xl border">
      <div class="d-flex align-center ga-3">
        <div class="breakdown-master-icon-orb">
          <v-icon size="20" color="#4643A0">mdi-view-dashboard-variant-outline</v-icon>
        </div>
        <div>
          <div class="text-subtitle-2 font-weight-bold text-slate-800" style="font-size: 15px !important;">
            {{ $t('breakdown_view') || 'Breakdown View' }}
          </div>
          <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
            {{ $t('charges_breakdown') || 'Charges Breakdown' }} &amp; {{ $t('shippings_breakdown') || 'Shipping Breakdown' }}
          </div>
        </div>
      </div>
      <SegmentedPillToggle
        v-model="activeBreakdownSection"
        :items="breakdownSectionItems"
        color="#4643A0"
        size="small"
      />
    </div>

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- DEDICATED LAYOUT: ALL CATEGORIES CHARGES BREAKDOWN -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <v-card
      v-show="activeBreakdownSection === 'both' || activeBreakdownSection === 'charges'"
      class="charges-breakdown-card rounded-2xl elevation-2 border overflow-hidden mb-6"
    >
      <!-- 1. Toolbar / Controls Banner (Styled in AddCharge design) -->
      <div class="breakdown-toolbar pa-4 pa-sm-5 border-b">
        <!-- Top Section: Title & High-level actions -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-3">
          <!-- Left: Title & Subtitle -->
          <div class="d-flex align-center ga-3">
            <div class="breakdown-icon-badge">
              <v-icon size="24" color="#0D47A1">mdi-shape-outline</v-icon>
            </div>
            <div>
              <div class="d-flex align-center ga-2 flex-wrap">
                <span class="text-subtitle-1 font-weight-bold text-slate-800" style="font-size: 17px !important;">
                  {{ $t('charge_categories_breakdown') || 'All Categories Charges Breakdown' }}
                </span>
                <v-chip size="small" color="#42A5F5" variant="flat" class="font-weight-bold text-white px-2.5">
                  {{ filteredCategories.length }} {{ $t('filter_categories') || 'Categories' }}
                </v-chip>
              </div>
              <div class="text-caption text-medium-emphasis mt-1" style="font-size: 12px !important; line-height: 1.4;">
                {{ $t('categories_charges_desc') || 'Total price and physical weight across all charge categories' }}
              </div>
            </div>
          </div>

          <!-- Right: Total Categorized Expenditure & View Mode Toggle -->
          <div class="d-flex align-center ga-3 flex-wrap">
            <!-- Futuristic Categorized Total KPI Capsule -->
            <div class="categorized-total-pill d-flex align-center ga-3 px-3.5 py-1.5 pa-2 rounded-pill">
              <!-- Glowing Sigma Orb -->
              <div class="total-icon-orb">
                <v-icon size="17" color="white">mdi-sigma</v-icon>
              </div>

              <!-- Metrics Stack -->
              <div class="d-flex flex-column justify-center" style="line-height: 1.15;">
                <!-- Label + Categories Count Badge -->
                <div class="d-flex align-center ga-1.5">
                  <span class="total-pill-label">
                    {{ $t('categorized_total') || 'Categorized Total' }}
                  </span>
                  <span class="total-pill-counter">
                    {{ chargesCategoryStats.length }}
                  </span>
                </div>

                <!-- Price + Weight Preview -->
                <div class="d-flex align-baseline ga-1 mt-0.5">
                  <span class="total-pill-value fnm-currency-num">
                    {{ main.formatDisplayNumber(categorizedTotalPrice) }}
                  </span>
                  <span class="total-pill-unit">{{ main.currency }}</span>

                  <!-- Weight preview if weight exists -->
                  <div
                    v-if="categorizedTotalWeight > 0"
                    class="total-pill-weight d-flex align-center ga-1 ml-1.5"
                    :title="$t('total_weight_sum') || 'Total Weight'"
                  >
                    <span class="total-pill-dot">•</span>
                    <v-icon size="12" color="#0891B2">mdi-weight-kilogram</v-icon>
                    <span>{{ (categorizedTotalWeight / 1000).toFixed(1) }} t</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- View Mode Segmented Buttons: Cards vs Table -->
            <SegmentedPillToggle
              v-model="viewMode"
              :items="viewModeItems"
              color="#42A5F5"
              size="small"
            />
          </div>
        </div>

        <v-divider class="my-3 opacity-30" />

        <!-- Filter Chips & Search / Sort Bar -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-3">
          <!-- Left: Filter Chips -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-chip
              v-for="f in filterPillOptions"
              :key="f.value"
              size="small"
              :variant="activeTypeFilter === f.value ? 'flat' : 'outlined'"
              :color="activeTypeFilter === f.value ? '#42A5F5' : undefined"
              class="category-filter-chip font-weight-medium text-none px-3"
              @click="activeTypeFilter = f.value"
            >
              <v-icon start size="14">{{ f.icon }}</v-icon>
              {{ f.label }}
              <span class="ml-1 opacity-80 font-weight-bold">({{ f.count }})</span>
            </v-chip>
          </div>

          <!-- Right: Search + Sort + Reset (AddCharge Style) -->
          <div class="d-flex align-center ga-2 flex-wrap flex-grow-1 flex-sm-grow-0 justify-end">
            <!-- Search Input -->
            <v-text-field
              v-model="searchQuery"
              :placeholder="$t('search_categories') || 'Search categories...'"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              hide-details
              clearable
              prepend-inner-icon="mdi-magnify"
              class="addcharge-input search-field"
              style="width: 220px;"
            />

            <!-- Sort Select -->
            <v-select
              v-model="sortBy"
              :items="sortOptions"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              hide-details
              prepend-inner-icon="mdi-sort"
              class="addcharge-input sort-field"
              style="width: 180px;"
            />

            <!-- Clear/Reset Button if filtered -->
            <v-btn
              v-if="searchQuery || activeTypeFilter !== 'all' || sortBy !== 'price_desc'"
              icon="mdi-filter-remove-outline"
              size="small"
              variant="tonal"
              color="warning"
              :title="$t('reset_filters') || 'Reset Filters'"
              @click="resetCategoryFilters"
            />
          </div>
        </div>
      </div>

      <!-- VIEW OPTION A: CHARGES CARDS GRID -->
      <div v-if="viewMode === 'cards'" class="pa-4 pa-sm-5 bg-surface-variant-subtle">
        <v-row v-if="filteredCategories.length > 0" class="category-cards-grid">
          <v-col
            v-for="cat in filteredCategories"
            :key="cat.id"
            cols="12"
            md="6"
            lg="4"
            class="d-flex"
          >
            <v-card class="category-charge-card rounded-2xl elevation-1 border h-100 w-100 d-flex flex-column">
              <!-- Card Header -->
              <div class="category-card-top pa-4 border-b">
                <div class="d-flex align-center justify-space-between ga-3 mb-2.5">
                  <div class="d-flex align-center ga-3 overflow-hidden flex-grow-1">
                    <div class="cat-avatar" :class="{ 'is-prod-avatar': cat.is_production }">
                      <v-icon size="20" :color="cat.is_production ? '#0891B2' : '#1565C0'">
                        {{ getCategoryIcon(cat) }}
                      </v-icon>
                    </div>
                    <div class="overflow-hidden">
                      <div class="font-weight-bold text-subtitle-2 text-truncate text-slate-800" :title="getCategoryName(cat)" style="font-size: 15px !important; letter-spacing: -0.2px;">
                        {{ getCategoryName(cat) }}
                      </div>
                      <div class="text-caption text-medium-emphasis text-truncate" v-if="cat.description" style="font-size: 11px !important;">
                        {{ cat.description }}
                      </div>
                    </div>
                  </div>

                  <!-- Weight Status Badge -->
                  <div class="flex-shrink-0">
                    <v-chip
                      v-if="cat.has_weight"
                      size="small"
                      color="#42A5F5"
                      variant="tonal"
                      class="font-weight-bold text-caption px-2.5"
                    >
                      <v-icon start size="13">mdi-scale</v-icon>
                      {{ $t('has_weight_badge') || 'Weight' }}
                    </v-chip>
                    <v-chip
                      v-else
                      size="small"
                      color="blue-grey"
                      variant="tonal"
                      class="font-weight-medium text-caption px-2.5"
                    >
                      <v-icon start size="13">mdi-receipt-outline</v-icon>
                      {{ $t('financial_only_badge') || 'Financial' }}
                    </v-chip>
                  </div>
                </div>

                <!-- Meta chips row -->
                <div class="d-flex align-center ga-2 flex-wrap pt-1">
                  <v-chip size="x-small" variant="flat" class="cat-count-chip font-weight-medium px-2.5 py-1">
                    <v-icon start size="12">mdi-counter</v-icon>
                    {{ main.formatDisplayNumber(cat.count) }} {{ $t('records') }}
                  </v-chip>
                  <v-chip v-if="cat.is_production" size="x-small" color="#0891B2" variant="tonal" class="font-weight-bold px-2.5 py-1">
                    <v-icon start size="12">mdi-factory</v-icon>
                    {{ $t('raw_material') || 'Production' }}
                  </v-chip>
                  <v-chip size="x-small" variant="tonal" color="#1565C0" class="font-weight-bold px-2.5 py-1">
                    <v-icon start size="12">mdi-chart-pie</v-icon>
                    <span>{{ cat.price_percentage }}% {{ $t('share') || 'Share' }}</span>
                  </v-chip>
                </div>
              </div>

              <!-- Card Body: Price & Weight Metrics -->
              <div class="pa-4 d-flex flex-column ga-3 flex-grow-1 justify-space-between">
                <!-- 1. TOTAL PRICE -->
                <div class="category-metric-box pa-2 price-metric-box pa-3.5 rounded-xl border">
                  <div class="metric-title-bar d-flex align-center justify-space-between ga-3 mb-2.5">
                    <div class="d-flex align-center ga-2.5">
                      <div class="metric-mini-badge bg-blue-subtle-badge ma-1">
                        <v-icon size="16" color="#1565C0">mdi-cash-multiple</v-icon>
                      </div>
                      <span class="text-caption font-weight-bold text-blue-darken-4" style="font-size: 13px !important; letter-spacing: -0.1px;">
                        {{ $t('price_dh') || 'Total Price' }}
                      </span>
                    </div>
                    <v-chip
                      size="small"
                      color="#1565C0"
                      variant="flat"
                      class="price-share-pill font-weight-bold text-white px-3 py-1"
                    >
                      <v-icon start size="12" class="mr-1">mdi-percent</v-icon>
                      {{ cat.price_percentage }}%
                    </v-chip>
                  </div>

                  <div class="d-flex align-baseline justify-space-between flex-wrap ga-2 my-1.5">
                    <div class="metric-value text-blue-darken-4 fnm-currency-num" style="font-size: 23px !important;">
                      {{ main.formatDisplayNumber(cat.total_price) }}
                      <span class="metric-unit">{{ main.currency }}</span>
                    </div>
                  </div>

                  <v-progress-linear
                    :model-value="Math.min(100, Math.max(0, cat.price_percentage))"
                    height="5"
                    rounded
                    color="#1565C0"
                    bg-color="blue-lighten-4"
                    class="my-2"
                  />

                  <div class="metric-sub-row pa-1 pt-2.5 mt-2.5 border-t border-solid d-flex flex-column ga-2">
                    <div class="d-flex align-center justify-space-between ga-3 text-caption font-weight-medium">
                      <div class="d-flex align-center ga-2 text-medium-emphasis overflow-hidden">
                        <v-icon size="14" color="#1565C0">mdi-chart-pie</v-icon>
                        <span class="text-truncate">{{ $t('share_of_expenditure') || 'Share of Total Charges' }}</span>
                      </div>
                      <span class="text-blue-darken-3 font-weight-bold font-mono px-2 py-0.5 rounded bg-blue-subtle-badge text-caption">
                        {{ cat.price_percentage }}%
                      </span>
                    </div>
                    <div class="d-flex align-center justify-space-between ga-3 text-caption font-weight-medium">
                      <div class="d-flex align-center ga-2 text-medium-emphasis overflow-hidden">
                        <v-icon size="14" color="#1565C0">mdi-calculator</v-icon>
                        <span class="text-truncate">{{ $t('avg_per_record') || 'Avg / Rec' }}</span>
                      </div>
                      <span class="text-blue-darken-3 font-weight-bold font-mono text-caption">
                        {{ main.formatDisplayNumber(cat.avg_price) }} {{ main.currency }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- 2. TOTAL WEIGHT (IF EXIST) -->
                <div v-if="cat.has_weight" class="category-metric-box pa-2 weight-metric-box pa-3.5 rounded-xl border bg-blue-subtle">
                  <div class="metric-title-bar d-flex align-center justify-space-between ga-3 mb-2.5">
                    <div class="d-flex align-center ga-2.5">
                      <div class="metric-mini-badge bg-cyan-subtle-badge ma-1">
                        <v-icon size="16" color="#0891B2">mdi-weight-kilogram</v-icon>
                      </div>
                      <span class="text-caption font-weight-bold text-cyan-darken-3" style="font-size: 13px !important; letter-spacing: -0.1px;">
                        {{ $t('total_weight_sum') || 'Total Weight' }}
                      </span>
                    </div>
                    <v-chip
                      size="small"
                      color="#0891B2"
                      variant="flat"
                      class="volume-share-pill font-weight-bold text-white px-3 py-1"
                    >
                      <v-icon start size="12" class="mr-1">mdi-percent</v-icon>
                      {{ cat.weight_percentage }}%
                    </v-chip>
                  </div>

                  <div class="d-flex align-baseline justify-space-between flex-wrap ga-2 my-1.5">
                    <div class="metric-value text-cyan-darken-4 fnm-currency-num" style="font-size: 23px !important;">
                      {{ main.formatDisplayNumber(cat.total_weight) }}
                      <span class="metric-unit">kg</span>
                    </div>
                    <v-chip size="x-small" variant="tonal" color="cyan-darken-2" class="font-weight-medium px-2">
                      ≈ {{ (cat.total_weight / 1000).toFixed(1) }} t
                    </v-chip>
                  </div>

                  <v-progress-linear
                    :model-value="Math.min(100, Math.max(0, cat.weight_percentage))"
                    height="5"
                    rounded
                    color="#0891B2"
                    bg-color="cyan-lighten-4"
                    class="my-2"
                  />

                  <div class="metric-sub-row pt-2.5 mt-2.5 pt-1 d-flex align-center justify-space-between ga-3">
                    <div class="d-flex align-center ga-2 text-caption font-weight-medium text-medium-emphasis">
                      <v-icon size="14" color="#0891B2">mdi-tag-outline</v-icon>
                      <span>{{ $t('unit_cost') || 'Unit Cost' }}</span>
                    </div>
                    <strong class="text-cyan-darken-3 font-weight-bold font-mono px-2 py-0.5 rounded bg-cyan-subtle-badge text-caption">
                      {{ cat.avg_price_per_kg ? main.formatDisplayNumber(cat.avg_price_per_kg) + ' ' + main.currency + ' / kg' : '—' }}
                    </strong>
                  </div>
                </div>

                <!-- 2b. NO WEIGHT -->
                <div v-else class="category-metric-box no-weight-metric-box pa-3.5 rounded-xl border bg-neutral-subtle d-flex align-center ga-3">
                  <div class="no-weight-avatar flex-shrink-0 ma-2">
                    <v-icon size="22" color="blue-grey-darken-1">mdi-receipt-text-outline</v-icon>
                  </div>
                  <div class="overflow-hidden">
                    <div class="text-subtitle-2 font-weight-bold text-blue-grey-darken-3 mb-0.5">
                      {{ $t('weight_not_applicable') || 'No Physical Weight' }}
                    </div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px !important; line-height: 1.35;">
                      {{ $t('financial_expense_note') || 'Financial overhead or service expense without physical weight.' }}
                    </div>
                    <div class="mt-2 d-flex align-center ga-2 flex-wrap">
                      <v-chip size="x-small" variant="tonal" color="blue-grey" class="font-weight-medium">
                        {{ $t('financial_only_badge') || 'Financial Expense' }}
                      </v-chip>
                      <span class="text-caption text-medium-emphasis" style="font-size: 11px;">
                        Weight: <strong>—</strong>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Empty Filter State -->
        <div v-else class="text-center pa-10 bg-white rounded-2xl border">
          <v-icon size="48" color="grey-lighten-1">mdi-filter-remove-outline</v-icon>
          <div class="text-subtitle-1 font-weight-bold text-medium-emphasis mt-3">
            {{ $t('no_categories_match_filter') || 'No categories match your search or filter' }}
          </div>
          <v-btn
            size="small"
            variant="tonal"
            color="#42A5F5"
            class="mt-4 text-none rounded-pill px-4"
            @click="resetCategoryFilters"
          >
            {{ $t('reset_filters') || 'Reset Filters' }}
          </v-btn>
        </div>
      </div>

      <!-- VIEW OPTION B: CHARGES EXECUTIVE ANALYTICAL TABLE -->
      <div v-else class="pa-4 pa-sm-5">
        <div class="table-responsive rounded-2xl border overflow-hidden">
          <v-table density="comfortable" hover class="breakdown-table">
            <thead>
              <tr class="table-header-row">
                <th class="text-left font-weight-bold py-3.5 px-4">{{ $t('category_name') || 'Category' }}</th>
                <th class="text-center font-weight-bold py-3.5 px-3" style="width: 120px;">{{ $t('records') }}</th>
                <th class="text-right font-weight-bold py-3.5 px-4" style="min-width: 190px;">{{ $t('price_dh') || 'Total Price' }}</th>
                <th class="text-right font-weight-bold py-3.5 px-4" style="min-width: 190px;">{{ $t('total_weight_sum') || 'Total Weight' }}</th>
                <th class="text-right font-weight-bold py-3.5 px-4" style="min-width: 140px;">{{ $t('unit_cost') || 'Unit Cost' }}</th>
                <th class="text-right font-weight-bold py-3.5 px-4" style="min-width: 140px;">{{ $t('avg_per_record') || 'Avg / Rec' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in filteredCategories" :key="cat.id" class="table-row-hover">
                <td class="py-3 px-4">
                  <div class="d-flex align-center ga-3">
                    <div class="cat-avatar-mini" :class="{ 'is-prod-avatar': cat.is_production }">
                      <v-icon size="16" :color="cat.is_production ? '#0891B2' : '#1565C0'">
                        {{ getCategoryIcon(cat) }}
                      </v-icon>
                    </div>
                    <div>
                      <div class="font-weight-bold text-subtitle-2 text-slate-800">
                        {{ getCategoryName(cat) }}
                      </div>
                      <div class="d-flex align-center ga-1.5 mt-1 flex-wrap">
                        <v-chip v-if="cat.is_production" size="x-small" color="#0891B2" variant="tonal" class="font-weight-bold">
                          {{ $t('raw_material') || 'Production' }}
                        </v-chip>
                        <v-chip
                          :color="cat.has_weight ? '#42A5F5' : 'blue-grey'"
                          variant="tonal"
                          size="x-small"
                          class="font-weight-medium"
                        >
                          {{ cat.has_weight ? ($t('has_weight_badge') || 'Weight') : ($t('financial_only_badge') || 'Financial') }}
                        </v-chip>
                      </div>
                    </div>
                  </div>
                </td>

                <td class="text-center py-3 px-3">
                  <v-chip size="small" variant="flat" class="cat-count-chip font-weight-bold">
                    {{ main.formatDisplayNumber(cat.count) }}
                  </v-chip>
                </td>

                <td class="text-right py-3 px-4">
                  <div class="font-weight-bold text-blue-darken-3 fnm-currency-num">
                    {{ main.formatDisplayNumber(cat.total_price) }} <span class="metric-unit">{{ main.currency }}</span>
                  </div>
                  <div class="d-flex align-center justify-end ga-2 mt-1">
                    <v-progress-linear
                      :model-value="Math.min(100, Math.max(0, cat.price_percentage))"
                      height="4"
                      rounded
                      color="#1565C0"
                      style="width: 70px;"
                    />
                    <span class="text-caption text-medium-emphasis font-weight-bold" style="font-size: 11px !important;">
                      {{ cat.price_percentage }}%
                    </span>
                  </div>
                </td>

                <td class="text-right py-3 px-4">
                  <template v-if="cat.has_weight">
                    <div class="font-weight-bold text-cyan-darken-3 fnm-currency-num">
                      {{ main.formatDisplayNumber(cat.total_weight) }} <span class="metric-unit">kg</span>
                    </div>
                    <div class="d-flex align-center justify-end ga-2 mt-1">
                      <v-progress-linear
                        :model-value="Math.min(100, Math.max(0, cat.weight_percentage))"
                        height="4"
                        rounded
                        color="#0891B2"
                        style="width: 70px;"
                      />
                      <span class="text-caption text-medium-emphasis font-weight-bold" style="font-size: 11px !important;">
                        {{ cat.weight_percentage }}%
                      </span>
                    </div>
                  </template>
                  <template v-else>
                    <v-chip size="small" variant="tonal" color="blue-grey" class="font-weight-medium">
                      {{ $t('weight_not_applicable') || '—' }}
                    </v-chip>
                  </template>
                </td>

                <td class="text-right font-weight-medium text-caption py-3 px-4">
                  <span v-if="cat.has_weight && cat.avg_price_per_kg" class="font-weight-bold text-cyan-darken-3">
                    {{ main.formatDisplayNumber(cat.avg_price_per_kg) }} {{ main.currency }}/kg
                  </span>
                  <span v-else class="text-medium-emphasis">—</span>
                </td>

                <td class="text-right font-weight-medium text-caption text-medium-emphasis py-3 px-4">
                  {{ main.formatDisplayNumber(cat.avg_price) }} {{ main.currency }}
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </div>
    </v-card>

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- DEDICATED LAYOUT: ALL CATEGORIES SHIPPING BREAKDOWN -->
    <!-- (Exact design, structure, and features matching Charges) -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <v-card
      v-show="activeBreakdownSection === 'both' || activeBreakdownSection === 'shippings'"
      class="shippings-breakdown-card rounded-2xl elevation-2 border overflow-hidden"
    >
      <!-- 1. Toolbar / Controls Banner (Styled in AddCharge design with Shipping Theme) -->
      <div class="shipping-breakdown-toolbar pa-4 pa-sm-5 border-b">
        <!-- Top Section: Title & High-level actions -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-3">
          <!-- Left: Title & Subtitle -->
          <div class="d-flex align-center ga-3">
            <div class="shipping-icon-badge">
              <v-icon size="24" color="#4643A0">mdi-truck-fast-outline</v-icon>
            </div>
            <div>
              <div class="d-flex align-center ga-2 flex-wrap">
                <span class="text-subtitle-1 font-weight-bold text-slate-800" style="font-size: 17px !important;">
                  {{ $t('shipping_categories_breakdown') || 'All Categories Shipping Breakdown' }}
                </span>
                <v-chip size="small" color="#6C66BB" variant="flat" class="font-weight-bold text-white px-2.5">
                  {{ filteredShippingCategories.length }} {{ $t('filter_categories') || 'Categories' }}
                </v-chip>
              </div>
              <div class="text-caption text-medium-emphasis mt-1" style="font-size: 12px !important; line-height: 1.4;">
                {{ $t('categories_shipping_desc') || 'Consolidated total revenue and physical weight across all shipping categories' }}
              </div>
            </div>
          </div>

          <!-- Right: Total Categorized Revenue & View Mode Toggle -->
          <div class="d-flex align-center ga-3 flex-wrap">
            <!-- Futuristic Categorized Total KPI Capsule (Shipping Indigo / Violet) -->
            <div class="shipping-total-pill d-flex align-center ga-3 px-3.5 py-1.5 pa-2 rounded-pill">
              <!-- Glowing Sigma Orb -->
              <div class="shipping-total-icon-orb">
                <v-icon size="17" color="white">mdi-sigma</v-icon>
              </div>

              <!-- Metrics Stack -->
              <div class="d-flex flex-column justify-center" style="line-height: 1.15;">
                <!-- Label + Categories Count Badge -->
                <div class="d-flex align-center ga-1.5">
                  <span class="shipping-total-pill-label">
                    {{ $t('categorized_total') || 'Categorized Total' }}
                  </span>
                  <span class="shipping-total-pill-counter">
                    {{ shippingCategoryStats.length }}
                  </span>
                </div>

                <!-- Price + Weight Preview -->
                <div class="d-flex align-baseline ga-1 mt-0.5">
                  <span class="shipping-total-pill-value fnm-currency-num">
                    {{ main.formatDisplayNumber(categorizedShippingTotalPrice) }}
                  </span>
                  <span class="shipping-total-pill-unit">{{ main.currency }}</span>

                  <!-- Weight preview if weight exists -->
                  <div
                    v-if="categorizedShippingTotalWeight > 0"
                    class="shipping-total-pill-weight d-flex align-center ga-1 ml-1.5"
                    :title="$t('total_weight_sum') || 'Total Weight'"
                  >
                    <span class="total-pill-dot">•</span>
                    <v-icon size="12" color="#6C66BB">mdi-weight-kilogram</v-icon>
                    <span>{{ (categorizedShippingTotalWeight / 1000).toFixed(1) }} t</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- View Mode Segmented Buttons: Cards vs Table -->
            <SegmentedPillToggle
              v-model="shippingViewMode"
              :items="shippingViewModeItems"
              color="#4643A0"
              size="small"
            />
          </div>
        </div>

        <v-divider class="my-3 opacity-30" style="color: #4643A0;" />

        <!-- Filter Chips & Search / Sort Bar (Spacious 2-column or wrapping flex) -->
        <div class="d-flex align-center justify-space-between flex-wrap ga-3">
          <!-- Left: Filter Chips -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-chip
              v-for="f in shippingFilterPillOptions"
              :key="f.value"
              size="small"
              :variant="shippingActiveTypeFilter === f.value ? 'flat' : 'outlined'"
              :color="shippingActiveTypeFilter === f.value ? '#4643A0' : undefined"
              class="category-filter-chip font-weight-medium text-none px-3"
              @click="shippingActiveTypeFilter = f.value"
            >
              <v-icon start size="14">{{ f.icon }}</v-icon>
              {{ f.label }}
              <span class="ml-1 opacity-80 font-weight-bold">({{ f.count }})</span>
            </v-chip>
          </div>

          <!-- Right: Search + Sort + Reset (AddCharge Style) -->
          <div class="d-flex align-center ga-2 flex-wrap flex-grow-1 flex-sm-grow-0 justify-end">
            <!-- Search Input (AddCharge style) -->
            <v-text-field
              v-model="shippingSearchQuery"
              :placeholder="$t('search_shipping_categories') || 'Search shipping categories...'"
              density="compact"
              variant="outlined"
              rounded
              color="#4643A0"
              hide-details
              clearable
              prepend-inner-icon="mdi-magnify"
              class="addcharge-input search-field"
              style="width: 230px;"
            />

            <!-- Sort Select (AddCharge style) -->
            <v-select
              v-model="shippingSortBy"
              :items="shippingSortOptions"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              rounded
              color="#4643A0"
              hide-details
              prepend-inner-icon="mdi-sort"
              class="addcharge-input sort-field"
              style="width: 180px;"
            />

            <!-- Clear/Reset Button if filtered -->
            <v-btn
              v-if="shippingSearchQuery || shippingActiveTypeFilter !== 'all' || shippingSortBy !== 'price_desc'"
              icon="mdi-filter-remove-outline"
              size="small"
              variant="tonal"
              color="warning"
              :title="$t('reset_filters') || 'Reset Filters'"
              @click="resetShippingCategoryFilters"
            />
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════════════════════ -->
      <!-- VIEW OPTION A: SHIPPING CARDS GRID -->
      <!-- ════════════════════════════════════════════════════════════════ -->
      <div v-if="shippingViewMode === 'cards'" class="pa-4 pa-sm-5 bg-surface-variant-subtle">
        <v-row v-if="filteredShippingCategories.length > 0" class="category-cards-grid">
          <v-col
            v-for="cat in filteredShippingCategories"
            :key="cat.id"
            cols="12"
            md="6"
            lg="4"
            class="d-flex"
          >
            <v-card class="shipping-category-card rounded-2xl elevation-1 border h-100 w-100 d-flex flex-column">
              <!-- Card Header with spacious two-tier layout -->
              <div class="category-card-top pa-4 border-b">
                <!-- Tier 1: Avatar + Title on left, Status badge on right -->
                <div class="d-flex align-center justify-space-between ga-3 mb-2.5">
                  <div class="d-flex align-center ga-3 overflow-hidden flex-grow-1">
                    <div class="shipping-cat-avatar" :class="{ 'is-export-avatar': cat.is_export }">
                      <v-icon size="20" :color="cat.is_export ? '#4643A0' : '#6C66BB'">
                        {{ getShippingCategoryIcon(cat) }}
                      </v-icon>
                    </div>
                    <div class="overflow-hidden">
                      <div class="font-weight-bold text-subtitle-2 text-truncate text-slate-800" :title="getShippingCategoryName(cat)" style="font-size: 15px !important; letter-spacing: -0.2px;">
                        {{ getShippingCategoryName(cat) }}
                      </div>
                      <div class="text-caption text-medium-emphasis text-truncate" v-if="cat.description" style="font-size: 11px !important;">
                        {{ cat.description }}
                      </div>
                    </div>
                  </div>

                  <!-- Weight Status Badge -->
                  <div class="flex-shrink-0">
                    <v-chip
                      v-if="cat.has_weight"
                      size="small"
                      color="#6C66BB"
                      variant="tonal"
                      class="font-weight-bold text-caption px-2.5"
                    >
                      <v-icon start size="13">mdi-scale</v-icon>
                      {{ $t('has_weight_badge') || 'Weight' }}
                    </v-chip>
                    <v-chip
                      v-else
                      size="small"
                      color="blue-grey"
                      variant="tonal"
                      class="font-weight-medium text-caption px-2.5"
                    >
                      <v-icon start size="13">mdi-receipt-outline</v-icon>
                      {{ $t('financial_only_badge') || 'Financial' }}
                    </v-chip>
                  </div>
                </div>

                <!-- Tier 2: Meta chips row -->
                <div class="d-flex align-center ga-2 flex-wrap pt-1">
                  <v-chip size="x-small" variant="flat" class="cat-count-chip font-weight-medium px-2.5 py-1">
                    <v-icon start size="12">mdi-counter</v-icon>
                    {{ main.formatDisplayNumber(cat.count) }} {{ $t('records') }}
                  </v-chip>
                  <v-chip v-if="cat.is_export" size="x-small" color="#4643A0" variant="tonal" class="font-weight-bold px-2.5 py-1">
                    <v-icon start size="12">mdi-ferry</v-icon>
                    {{ $t('export_sales') || 'Export Sales' }}
                  </v-chip>
                  <v-chip v-else size="x-small" color="#6C66BB" variant="tonal" class="font-weight-bold px-2.5 py-1">
                    <v-icon start size="12">mdi-storefront-outline</v-icon>
                    {{ $t('domestic_sale') || 'Domestic Sale' }}
                  </v-chip>
                  <v-chip size="x-small" variant="tonal" color="#4643A0" class="font-weight-bold px-2.5 py-1">
                    <v-icon start size="12">mdi-chart-pie</v-icon>
                    <span>{{ cat.price_percentage }}% {{ $t('share') || 'Share' }}</span>
                  </v-chip>
                </div>
              </div>

              <!-- Card Body: Revenue & Weight Metrics -->
              <div class="pa-4 d-flex flex-column ga-3 flex-grow-1 justify-space-between">
                <!-- 1. TOTAL REVENUE (SALES / PRICE) -->
                <div class="category-metric-box pa-2 shipping-price-metric-box pa-3.5 rounded-xl border">
                  <!-- Metric Title & Share Pill -->
                  <div class="metric-title-bar d-flex align-center justify-space-between ga-3 mb-2.5">
                    <div class="d-flex align-center ga-2.5">
                      <div class="metric-mini-badge bg-indigo-subtle-badge ma-1">
                        <v-icon size="16" color="#4643A0">mdi-cash-multiple</v-icon>
                      </div>
                      <span class="text-caption font-weight-bold text-indigo-darken-4" style="font-size: 13px !important; letter-spacing: -0.1px;">
                        {{ $t('total_revenue') || 'Total Revenue' }}
                      </span>
                    </div>
                    <v-chip
                      size="small"
                      color="#4643A0"
                      variant="flat"
                      class="shipping-price-share-pill font-weight-bold text-white px-3 py-1"
                    >
                      <v-icon start size="12" class="mr-1">mdi-percent</v-icon>
                      {{ cat.price_percentage }}%
                    </v-chip>
                  </div>

                  <!-- Revenue Value -->
                  <div class="d-flex align-baseline justify-space-between flex-wrap ga-2 my-1.5">
                    <div class="metric-value text-indigo-darken-4 fnm-currency-num" style="font-size: 23px !important;">
                      {{ main.formatDisplayNumber(cat.total_price) }}
                      <span class="metric-unit">{{ main.currency }}</span>
                    </div>
                  </div>

                  <!-- Progress Bar -->
                  <v-progress-linear
                    :model-value="Math.min(100, Math.max(0, cat.price_percentage))"
                    height="5"
                    rounded
                    color="#4643A0"
                    bg-color="indigo-lighten-4"
                    class="my-2"
                  />

                  <!-- Sub-metrics Rows: Share of Total Shippings & Avg / Dispatch -->
                  <div class="metric-sub-row pa-1 pt-2.5 mt-2.5 border-t border-solid d-flex flex-column ga-2">
                    <div class="d-flex align-center justify-space-between ga-3 text-caption font-weight-medium">
                      <div class="d-flex align-center ga-2 text-medium-emphasis overflow-hidden">
                        <v-icon size="14" color="#4643A0">mdi-chart-pie</v-icon>
                        <span class="text-truncate">{{ $t('share_of_shippings') || 'Share of Total Shippings' }}</span>
                      </div>
                      <span class="text-indigo-darken-3 font-weight-bold font-mono px-2 py-0.5 rounded bg-indigo-subtle-badge text-caption">
                        {{ cat.price_percentage }}%
                      </span>
                    </div>
                    <div class="d-flex align-center justify-space-between ga-3 text-caption font-weight-medium">
                      <div class="d-flex align-center ga-2 text-medium-emphasis overflow-hidden">
                        <v-icon size="14" color="#4643A0">mdi-calculator</v-icon>
                        <span class="text-truncate">{{ $t('avg_per_record') || 'Avg / Rec' }}</span>
                      </div>
                      <span class="text-indigo-darken-3 font-weight-bold font-mono text-caption">
                        {{ main.formatDisplayNumber(cat.avg_price) }} {{ main.currency }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- 2. TOTAL WEIGHT (IF EXIST - PHYSICAL SHIPPING CATEGORIES) -->
                <div v-if="cat.has_weight" class="category-metric-box pa-2 shipping-weight-metric-box pa-3.5 rounded-xl border bg-violet-subtle">
                  <!-- Metric Title & Volume Share Pill -->
                  <div class="metric-title-bar d-flex align-center justify-space-between ga-3 mb-2.5">
                    <div class="d-flex align-center ga-2.5">
                      <div class="metric-mini-badge bg-violet-subtle-badge ma-1">
                        <v-icon size="16" color="#6C66BB">mdi-weight-kilogram</v-icon>
                      </div>
                      <span class="text-caption font-weight-bold text-violet-darken-3" style="font-size: 13px !important; letter-spacing: -0.1px;">
                        {{ $t('total_weight_sum') || 'Total Weight' }}
                      </span>
                    </div>
                    <v-chip
                      size="small"
                      color="#6C66BB"
                      variant="flat"
                      class="shipping-volume-share-pill font-weight-bold text-white px-3 py-1"
                    >
                      <v-icon start size="12" class="mr-1">mdi-percent</v-icon>
                      {{ cat.weight_percentage }}%
                    </v-chip>
                  </div>

                  <!-- Weight Value with tons preview -->
                  <div class="d-flex align-baseline justify-space-between flex-wrap ga-2 my-1.5">
                    <div class="metric-value text-indigo-darken-4 fnm-currency-num" style="font-size: 23px !important;">
                      {{ main.formatDisplayNumber(cat.total_weight) }}
                      <span class="metric-unit">kg</span>
                    </div>
                    <v-chip size="x-small" variant="tonal" color="#6C66BB" class="font-weight-medium px-2">
                      ≈ {{ (cat.total_weight / 1000).toFixed(1) }} t
                    </v-chip>
                  </div>

                  <!-- Progress Bar -->
                  <v-progress-linear
                    :model-value="Math.min(100, Math.max(0, cat.weight_percentage))"
                    height="5"
                    rounded
                    color="#6C66BB"
                    bg-color="deep-purple-lighten-4"
                    class="my-2"
                  />

                  <!-- Selling Price / Unit Cost Sub-row -->
                  <div class="metric-sub-row pt-2.5 mt-2.5 pt-1 d-flex align-center justify-space-between ga-3">
                    <div class="d-flex align-center ga-2 text-caption font-weight-medium text-medium-emphasis">
                      <v-icon size="14" color="#6C66BB">mdi-tag-outline</v-icon>
                      <span>{{ $t('unit_cost') || 'Unit Price' }}</span>
                    </div>
                    <strong class="text-indigo-darken-3 font-weight-bold font-mono px-2 py-0.5 rounded bg-violet-subtle-badge text-caption">
                      {{ cat.avg_price_per_kg ? main.formatDisplayNumber(cat.avg_price_per_kg) + ' ' + main.currency + ' / kg' : '—' }}
                    </strong>
                  </div>
                </div>

                <!-- 2b. NO WEIGHT (FINANCIAL / NON-PHYSICAL DISPATCH) -->
                <div v-else class="category-metric-box no-weight-metric-box pa-3.5 rounded-xl border bg-neutral-subtle d-flex align-center ga-3">
                  <div class="no-weight-avatar flex-shrink-0 ma-2">
                    <v-icon size="22" color="blue-grey-darken-1">mdi-receipt-text-outline</v-icon>
                  </div>
                  <div class="overflow-hidden">
                    <div class="text-subtitle-2 font-weight-bold text-blue-grey-darken-3 mb-0.5">
                      {{ $t('weight_not_applicable') || 'No Physical Weight' }}
                    </div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px !important; line-height: 1.35;">
                      {{ $t('financial_expense_note') || 'Financial dispatch or service invoice without physical weight.' }}
                    </div>
                    <div class="mt-2 d-flex align-center ga-2 flex-wrap">
                      <v-chip size="x-small" variant="tonal" color="blue-grey" class="font-weight-medium">
                        {{ $t('financial_only_badge') || 'Financial' }}
                      </v-chip>
                      <span class="text-caption text-medium-emphasis" style="font-size: 11px;">
                        Weight: <strong>—</strong>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Empty Filter State -->
        <div v-else class="text-center pa-10 bg-white rounded-2xl border">
          <v-icon size="48" color="grey-lighten-1">mdi-filter-remove-outline</v-icon>
          <div class="text-subtitle-1 font-weight-bold text-medium-emphasis mt-3">
            {{ $t('no_shipping_categories_match') || 'No shipping categories match your search or filter' }}
          </div>
          <v-btn
            size="small"
            variant="tonal"
            color="#4643A0"
            class="mt-4 text-none rounded-pill px-4"
            @click="resetShippingCategoryFilters"
          >
            {{ $t('reset_filters') || 'Reset Filters' }}
          </v-btn>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════════════════════ -->
      <!-- VIEW OPTION B: SHIPPING EXECUTIVE ANALYTICAL TABLE -->
      <!-- ════════════════════════════════════════════════════════════════ -->
      <div v-else class="pa-4 pa-sm-5">
        <div class="table-responsive rounded-2xl border overflow-hidden">
          <v-table density="comfortable" hover class="breakdown-table">
            <thead>
              <tr class="shipping-table-header-row">
                <th class="text-left font-weight-bold py-3.5 px-4">{{ $t('category_name') || 'Category' }}</th>
                <th class="text-center font-weight-bold py-3.5 px-3" style="width: 120px;">{{ $t('records') }}</th>
                <th class="text-right font-weight-bold py-3.5 px-4" style="min-width: 190px;">{{ $t('total_revenue') || 'Total Revenue' }}</th>
                <th class="text-right font-weight-bold py-3.5 px-4" style="min-width: 190px;">{{ $t('total_weight_sum') || 'Total Weight' }}</th>
                <th class="text-right font-weight-bold py-3.5 px-4" style="min-width: 140px;">{{ $t('unit_cost') || 'Unit Price' }}</th>
                <th class="text-right font-weight-bold py-3.5 px-4" style="min-width: 140px;">{{ $t('avg_per_record') || 'Avg / Rec' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in filteredShippingCategories" :key="cat.id" class="shipping-table-row-hover">
                <!-- Category Name + Icon + Badges -->
                <td class="py-3 px-4">
                  <div class="d-flex align-center ga-3">
                    <div class="shipping-cat-avatar-mini" :class="{ 'is-export-avatar': cat.is_export }">
                      <v-icon size="16" :color="cat.is_export ? '#4643A0' : '#6C66BB'">
                        {{ getShippingCategoryIcon(cat) }}
                      </v-icon>
                    </div>
                    <div>
                      <div class="font-weight-bold text-subtitle-2 text-slate-800">
                        {{ getShippingCategoryName(cat) }}
                      </div>
                      <div class="d-flex align-center ga-1.5 mt-1 flex-wrap">
                        <v-chip v-if="cat.is_export" size="x-small" color="#4643A0" variant="tonal" class="font-weight-bold">
                          {{ $t('export_sales') || 'Export Sales' }}
                        </v-chip>
                        <v-chip v-else size="x-small" color="#6C66BB" variant="tonal" class="font-weight-bold">
                          {{ $t('domestic_sale') || 'Domestic Sale' }}
                        </v-chip>
                        <v-chip
                          :color="cat.has_weight ? '#6C66BB' : 'blue-grey'"
                          variant="tonal"
                          size="x-small"
                          class="font-weight-medium"
                        >
                          {{ cat.has_weight ? ($t('has_weight_badge') || 'Weight') : ($t('financial_only_badge') || 'Financial') }}
                        </v-chip>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Records Count -->
                <td class="text-center py-3 px-3">
                  <v-chip size="small" variant="flat" class="cat-count-chip font-weight-bold">
                    {{ main.formatDisplayNumber(cat.count) }}
                  </v-chip>
                </td>

                <!-- Total Revenue & % Bar -->
                <td class="text-right py-3 px-4">
                  <div class="font-weight-bold text-indigo-darken-3 fnm-currency-num">
                    {{ main.formatDisplayNumber(cat.total_price) }} <span class="metric-unit">{{ main.currency }}</span>
                  </div>
                  <div class="d-flex align-center justify-end ga-2 mt-1">
                    <v-progress-linear
                      :model-value="Math.min(100, Math.max(0, cat.price_percentage))"
                      height="4"
                      rounded
                      color="#4643A0"
                      style="width: 70px;"
                    />
                    <span class="text-caption text-medium-emphasis font-weight-bold" style="font-size: 11px !important;">
                      {{ cat.price_percentage }}%
                    </span>
                  </div>
                </td>

                <!-- Total Weight (IF EXIST) -->
                <td class="text-right py-3 px-4">
                  <template v-if="cat.has_weight">
                    <div class="font-weight-bold text-violet-darken-3 fnm-currency-num">
                      {{ main.formatDisplayNumber(cat.total_weight) }} <span class="metric-unit">kg</span>
                    </div>
                    <div class="d-flex align-center justify-end ga-2 mt-1">
                      <v-progress-linear
                        :model-value="Math.min(100, Math.max(0, cat.weight_percentage))"
                        height="4"
                        rounded
                        color="#6C66BB"
                        style="width: 70px;"
                      />
                      <span class="text-caption text-medium-emphasis font-weight-bold" style="font-size: 11px !important;">
                        {{ cat.weight_percentage }}%
                      </span>
                    </div>
                  </template>
                  <template v-else>
                    <v-chip size="small" variant="tonal" color="blue-grey" class="font-weight-medium">
                      {{ $t('weight_not_applicable') || '—' }}
                    </v-chip>
                  </template>
                </td>

                <!-- Unit Price (DH / kg if weight exists) -->
                <td class="text-right font-weight-medium text-caption py-3 px-4">
                  <span v-if="cat.has_weight && cat.avg_price_per_kg" class="font-weight-bold text-indigo-darken-3">
                    {{ main.formatDisplayNumber(cat.avg_price_per_kg) }} {{ main.currency }}/kg
                  </span>
                  <span v-else class="text-medium-emphasis">—</span>
                </td>

                <!-- Average Price / Record -->
                <td class="text-right font-weight-medium text-caption text-medium-emphasis py-3 px-4">
                  {{ main.formatDisplayNumber(cat.avg_price) }} {{ main.currency }}
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </div>
    </v-card>
  </div>
</template>

<script>
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useMain } from '@/stores/Main';

export default {
  name: 'JupiterSummaryCards',
  components: {
    SegmentedPillToggle,
  },
  props: {
    summary: {
      type: Object,
      default: () => ({
        charges: { count: 0, total_weight: 0, total_price: 0, avg_price_per_kg: 0, avg_price: 0, avg_weight: 0 },
        productions: { count: 0, total_weight: 0, total_price: 0, total_sec_cost: 0 },
        shippings: { count: 0, total_weight: 0, total_price: 0 },
        yields: [],
        equilibrium: 0,
        is_surplus: true,
      }),
    },
    chargesCategoryStats: {
      type: Array,
      default: () => [],
    },
    chargeCategories: {
      type: Array,
      default: () => [],
    },
    shippingCategoryStats: {
      type: Array,
      default: () => [],
    },
    shippingCategories: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      activeBreakdownSection: 'both', // 'both' | 'charges' | 'shippings'
      // Charges section state
      viewMode: 'cards', // 'cards' | 'table'
      searchQuery: '',
      activeTypeFilter: 'all', // 'all' | 'with_weight' | 'overhead' | 'production'
      sortBy: 'price_desc', // 'price_desc' | 'weight_desc' | 'name_asc' | 'count_desc'
      // Shippings section state
      shippingViewMode: 'cards', // 'cards' | 'table'
      shippingSearchQuery: '',
      shippingActiveTypeFilter: 'all', // 'all' | 'with_weight' | 'export' | 'local'
      shippingSortBy: 'price_desc', // 'price_desc' | 'weight_desc' | 'name_asc' | 'count_desc'
    };
  },
  computed: {
    main() {
      return useMain();
    },
    breakdownSectionItems() {
      return [
        {
          value: 'both',
          label: this.$t('both_breakdowns') || 'Both Breakdowns',
          icon: 'mdi-view-split-vertical',
        },
        {
          value: 'charges',
          label: this.$t('charges_breakdown') || 'Charges Breakdown',
          icon: 'mdi-shape-outline',
        },
        {
          value: 'shippings',
          label: this.$t('shippings_breakdown') || 'Shipping Breakdown',
          icon: 'mdi-truck-fast-outline',
        },
      ];
    },
    viewModeItems() {
      return [
        {
          value: 'cards',
          label: this.$t('cards_view') || 'Cards',
          icon: 'mdi-view-grid-outline',
        },
        {
          value: 'table',
          label: this.$t('table_view') || 'Table',
          icon: 'mdi-table-large',
        },
      ];
    },
    shippingViewModeItems() {
      return [
        {
          value: 'cards',
          label: this.$t('cards_view') || 'Cards',
          icon: 'mdi-view-grid-outline',
        },
        {
          value: 'table',
          label: this.$t('table_view') || 'Table',
          icon: 'mdi-table-large',
        },
      ];
    },
    computedChargesAvgPricePerKg() {
      const c = this.summary?.charges;
      if (!c) return '0.00';
      if (c.avg_price_per_kg && c.avg_price_per_kg > 0) {
        return this.main.formatDisplayNumber(c.avg_price_per_kg);
      }
      if (c.total_weight > 0 && c.total_price) {
        return this.main.formatDisplayNumber((c.total_price / c.total_weight).toFixed(2));
      }
      return '0.00';
    },
    // Charges Totals
    categorizedTotalPrice() {
      return (this.chargesCategoryStats || []).reduce((acc, cat) => acc + (Number(cat.total_price) || 0), 0);
    },
    categorizedTotalWeight() {
      return (this.chargesCategoryStats || []).reduce((acc, cat) => acc + (Number(cat.total_weight) || 0), 0);
    },
    filterPillOptions() {
      const stats = this.chargesCategoryStats || [];
      const withWeightCount = stats.filter(c => c.has_weight && c.total_weight > 0).length;
      const overheadCount = stats.filter(c => !c.has_weight || c.total_weight <= 0).length;
      const prodCount = stats.filter(c => c.is_production).length;

      return [
        {
          value: 'all',
          label: this.$t('all_charges_categories') || 'All Categories',
          icon: 'mdi-view-grid-outline',
          count: stats.length,
        },
        {
          value: 'with_weight',
          label: this.$t('with_weight_categories') || 'With Weight',
          icon: 'mdi-scale',
          count: withWeightCount,
        },
        {
          value: 'overhead',
          label: this.$t('overhead_categories') || 'Financial / Overhead',
          icon: 'mdi-receipt-outline',
          count: overheadCount,
        },
        {
          value: 'production',
          label: this.$t('raw_material') || 'Production Raw',
          icon: 'mdi-fish',
          count: prodCount,
        },
      ];
    },
    sortOptions() {
      return [
        { title: this.$t('highest_price') || 'Highest Price', value: 'price_desc' },
        { title: this.$t('highest_weight') || 'Highest Weight', value: 'weight_desc' },
        { title: this.$t('most_records') || 'Most Records', value: 'count_desc' },
        { title: this.$t('category_name') || 'Category Name', value: 'name_asc' },
      ];
    },
    filteredCategories() {
      let list = [...(this.chargesCategoryStats || [])];

      // 1. Filter by category type
      if (this.activeTypeFilter === 'with_weight') {
        list = list.filter(c => c.has_weight && c.total_weight > 0);
      } else if (this.activeTypeFilter === 'overhead') {
        list = list.filter(c => !c.has_weight || c.total_weight <= 0);
      } else if (this.activeTypeFilter === 'production') {
        list = list.filter(c => c.is_production);
      }

      // 2. Search query filter
      if (this.searchQuery && this.searchQuery.trim()) {
        const q = this.searchQuery.trim().toLowerCase();
        list = list.filter(c => {
          const name = this.getCategoryName(c).toLowerCase();
          const desc = (c.description || '').toLowerCase();
          return name.includes(q) || desc.includes(q);
        });
      }

      // 3. Sorting
      list.sort((a, b) => {
        if (this.sortBy === 'price_desc') {
          return (b.total_price || 0) - (a.total_price || 0);
        }
        if (this.sortBy === 'weight_desc') {
          return (b.total_weight || 0) - (a.total_weight || 0);
        }
        if (this.sortBy === 'count_desc') {
          return (b.count || 0) - (a.count || 0);
        }
        if (this.sortBy === 'name_asc') {
          return this.getCategoryName(a).localeCompare(this.getCategoryName(b));
        }
        return 0;
      });

      return list;
    },
    // Shippings Totals & Options
    categorizedShippingTotalPrice() {
      return (this.shippingCategoryStats || []).reduce((acc, cat) => acc + (Number(cat.total_price) || 0), 0);
    },
    categorizedShippingTotalWeight() {
      return (this.shippingCategoryStats || []).reduce((acc, cat) => acc + (Number(cat.total_weight) || 0), 0);
    },
    shippingFilterPillOptions() {
      const stats = this.shippingCategoryStats || [];
      const withWeightCount = stats.filter(c => c.has_weight && c.total_weight > 0).length;
      const exportCount = stats.filter(c => c.is_export).length;
      const localCount = stats.filter(c => !c.is_export).length;

      return [
        {
          value: 'all',
          label: this.$t('all_shipping_categories') || 'All Shipping Categories',
          icon: 'mdi-view-grid-outline',
          count: stats.length,
        },
        {
          value: 'with_weight',
          label: this.$t('with_weight_categories') || 'With Weight',
          icon: 'mdi-scale',
          count: withWeightCount,
        },
        {
          value: 'export',
          label: this.$t('export_sales') || 'Export Sales',
          icon: 'mdi-ferry',
          count: exportCount,
        },
        {
          value: 'local',
          label: this.$t('local_subcontracting') || 'Local / Other',
          icon: 'mdi-store-outline',
          count: localCount,
        },
      ];
    },
    shippingSortOptions() {
      return [
        { title: this.$t('highest_price') || 'Highest Revenue', value: 'price_desc' },
        { title: this.$t('highest_weight') || 'Highest Weight', value: 'weight_desc' },
        { title: this.$t('most_records') || 'Most Records', value: 'count_desc' },
        { title: this.$t('category_name') || 'Category Name', value: 'name_asc' },
      ];
    },
    filteredShippingCategories() {
      let list = [...(this.shippingCategoryStats || [])];

      // 1. Filter by category type
      if (this.shippingActiveTypeFilter === 'with_weight') {
        list = list.filter(c => c.has_weight && c.total_weight > 0);
      } else if (this.shippingActiveTypeFilter === 'export') {
        list = list.filter(c => c.is_export);
      } else if (this.shippingActiveTypeFilter === 'local') {
        list = list.filter(c => !c.is_export);
      }

      // 2. Search query filter
      if (this.shippingSearchQuery && this.shippingSearchQuery.trim()) {
        const q = this.shippingSearchQuery.trim().toLowerCase();
        list = list.filter(c => {
          const name = this.getShippingCategoryName(c).toLowerCase();
          const desc = (c.description || '').toLowerCase();
          return name.includes(q) || desc.includes(q);
        });
      }

      // 3. Sorting
      list.sort((a, b) => {
        if (this.shippingSortBy === 'price_desc') {
          return (b.total_price || 0) - (a.total_price || 0);
        }
        if (this.shippingSortBy === 'weight_desc') {
          return (b.total_weight || 0) - (a.total_weight || 0);
        }
        if (this.shippingSortBy === 'count_desc') {
          return (b.count || 0) - (a.count || 0);
        }
        if (this.shippingSortBy === 'name_asc') {
          return this.getShippingCategoryName(a).localeCompare(this.getShippingCategoryName(b));
        }
        return 0;
      });

      return list;
    },
  },
  methods: {
    avgWeight(item) {
      if (!item || !item.count || !item.total_weight) return '0';
      return this.main.formatDisplayNumber((item.total_weight / item.count).toFixed(1));
    },
    avgPrice(item) {
      if (!item || !item.count || !item.total_price) return '0';
      return this.main.formatDisplayNumber((item.total_price / item.count).toFixed(1));
    },
    getCategoryName(cat) {
      if (!cat) return '';
      const lang = this.main?.lang?.short || 'en';
      if (lang === 'fr' && cat.name_fr) return cat.name_fr;
      if (lang === 'es' && cat.name_es) return cat.name_es;
      if (lang === 'en' && cat.name_en) return cat.name_en;
      return cat.name_fr || cat.name_en || cat.name || '';
    },
    getCategoryIcon(cat) {
      const name = ((cat.name || '') + ' ' + (cat.name_fr || '') + ' ' + (cat.name_en || '')).toLowerCase();
      if (name.includes('frais') || name.includes('fresh') || name.includes('poisson') || name.includes('fish')) return 'mdi-fish';
      if (name.includes('congel') || name.includes('frozen')) return 'mdi-snowflake';
      if (name.includes('guano')) return 'mdi-leaf';
      if (name.includes('carton') || name.includes('emballage') || name.includes('package') || name.includes('box')) return 'mdi-package-variant';
      if (name.includes('rsw')) return 'mdi-waves';
      if (name.includes('glace') || name.includes('ice')) return 'mdi-cube-outline';
      if (name.includes('transport') || name.includes('camion') || name.includes('truck')) return 'mdi-truck-fast';
      if (name.includes('maintenance') || name.includes('reparation') || name.includes('repair')) return 'mdi-wrench';
      if (name.includes('autre') || name.includes('other')) return 'mdi-tag-multiple';
      return 'mdi-shape-outline';
    },
    resetCategoryFilters() {
      this.searchQuery = '';
      this.activeTypeFilter = 'all';
      this.sortBy = 'price_desc';
    },
    // Shipping category helpers
    getShippingCategoryName(cat) {
      if (!cat) return '';
      const lang = this.main?.lang?.short || 'en';
      if (lang === 'fr' && cat.name_fr) return cat.name_fr;
      if (lang === 'es' && cat.name_es) return cat.name_es;
      if (lang === 'en' && cat.name_en) return cat.name_en;
      return cat.name_fr || cat.name_en || cat.name || '';
    },
    getShippingCategoryIcon(cat) {
      const name = ((cat.name || '') + ' ' + (cat.name_fr || '') + ' ' + (cat.name_en || '')).toLowerCase();
      if (cat.is_export || name.includes('export')) return 'mdi-ferry';
      if (name.includes('local') || name.includes('vente locale')) return 'mdi-storefront-outline';
      if (name.includes('traitance') || name.includes('subcontract')) return 'mdi-handshake-outline';
      if (name.includes('emballage') || name.includes('package') || name.includes('carton')) return 'mdi-package-variant-closed';
      if (name.includes('congel') || name.includes('frozen')) return 'mdi-snowflake';
      if (name.includes('frais') || name.includes('fresh')) return 'mdi-fish';
      return 'mdi-truck-delivery-outline';
    },
    resetShippingCategoryFilters() {
      this.shippingSearchQuery = '';
      this.shippingActiveTypeFilter = 'all';
      this.shippingSortBy = 'price_desc';
    },
  },
};
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════ */
/* MASTER BREAKDOWN VIEW SELECTOR */
/* ════════════════════════════════════════════════════════════════ */
.breakdown-master-nav {
  background: rgba(255, 255, 255, 0.96);
  border-color: rgba(70, 67, 160, 0.18) !important;
  box-shadow: 0 4px 16px rgba(55, 46, 125, 0.05);
}

:deep(.v-theme--dark) .breakdown-master-nav,
.v-theme--dark .breakdown-master-nav {
  background: rgba(15, 23, 42, 0.9);
  border-color: rgba(108, 102, 187, 0.3) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
}

.breakdown-master-icon-orb {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(70, 67, 160, 0.12) 0%, rgba(108, 102, 187, 0.22) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(70, 67, 160, 0.25);
  flex-shrink: 0;
}

/* ════════════════════════════════════════════════════════════════ */
/* METRICS TYPOGRAPHY & TABULAR NUMBERS */
/* ════════════════════════════════════════════════════════════════ */
.metric-value {
  font-size: 21px;
  font-weight: 800;
  letter-spacing: -0.3px;
  line-height: 1.25;
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  -webkit-font-feature-settings: "tnum" 1 !important;
  font-feature-settings: "tnum" 1 !important;
}

.metric-unit {
  font-size: 13px;
  font-weight: 600;
  opacity: 0.85;
  margin-left: 3px;
}

.font-mono {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
}

.total-pill-dot {
  color: rgba(100, 116, 139, 0.5);
  font-size: 12px;
  margin-right: 2px;
}

.bg-surface-variant-subtle {
  background: rgba(248, 250, 252, 0.7);
}

:deep(.v-theme--dark) .bg-surface-variant-subtle,
.v-theme--dark .bg-surface-variant-subtle {
  background: rgba(11, 17, 33, 0.55);
}

/* ════════════════════════════════════════════════════════════════ */
/* CHARGES BREAKDOWN SECTION */
/* ════════════════════════════════════════════════════════════════ */
.charges-breakdown-card {
  background: rgba(255, 255, 255, 0.98);
  border-color: rgba(66, 165, 245, 0.2) !important;
}

:deep(.v-theme--dark) .charges-breakdown-card,
.v-theme--dark .charges-breakdown-card {
  background: rgba(15, 23, 42, 0.92);
  border-color: rgba(66, 165, 245, 0.25) !important;
}

.breakdown-toolbar {
  background: linear-gradient(180deg, rgba(66, 165, 245, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
}

:deep(.v-theme--dark) .breakdown-toolbar,
.v-theme--dark .breakdown-toolbar {
  background: linear-gradient(180deg, rgba(66, 165, 245, 0.08) 0%, rgba(15, 23, 42, 0) 100%);
}

.breakdown-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(13, 71, 161, 0.15) 0%, rgba(66, 165, 245, 0.1) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(66, 165, 245, 0.25);
  flex-shrink: 0;
}

.categorized-total-pill {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96) 0%, rgba(240, 247, 255, 0.92) 50%, rgba(224, 242, 254, 0.85) 100%);
  border: 1.5px solid rgba(66, 165, 245, 0.35);
  box-shadow: 0 4px 16px rgba(13, 71, 161, 0.08), 0 1px 3px rgba(66, 165, 245, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: default;
  user-select: none;
  min-width: 260px;
}

:deep(.v-theme--dark) .categorized-total-pill,
.v-theme--dark .categorized-total-pill {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.88) 0%, rgba(15, 23, 42, 0.95) 100%);
  border: 1.5px solid rgba(66, 165, 245, 0.45);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4), 0 0 16px rgba(66, 165, 245, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.categorized-total-pill:hover {
  transform: translateY(-2px);
  border-color: rgba(66, 165, 245, 0.7);
  box-shadow: 0 8px 24px rgba(66, 165, 245, 0.24), inset 0 1px 0 rgba(255, 255, 255, 0.95);
}

.total-icon-orb {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 50%, #42A5F5 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 3px 10px rgba(13, 71, 161, 0.35), inset 0 1px 2px rgba(255, 255, 255, 0.4);
  border: 1.5px solid rgba(255, 255, 255, 0.3);
  transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.categorized-total-pill:hover .total-icon-orb {
  transform: scale(1.08) rotate(4deg);
}

.total-pill-label {
  font-size: 10px !important;
  font-weight: 700 !important;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  color: #1565C0;
}

:deep(.v-theme--dark) .total-pill-label,
.v-theme--dark .total-pill-label {
  color: #90CAF9;
}

.total-pill-counter {
  font-size: 10px !important;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 9999px;
  background: rgba(66, 165, 245, 0.16);
  color: #0D47A1;
  letter-spacing: 0.2px;
  border: 1px solid rgba(66, 165, 245, 0.25);
}

:deep(.v-theme--dark) .total-pill-counter,
.v-theme--dark .total-pill-counter {
  background: rgba(66, 165, 245, 0.22);
  color: #60A5FA;
  border-color: rgba(66, 165, 245, 0.35);
}

.total-pill-value {
  font-size: 17px !important;
  font-weight: 800 !important;
  letter-spacing: -0.4px;
  color: #0D47A1;
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  line-height: 1;
}

:deep(.v-theme--dark) .total-pill-value,
.v-theme--dark .total-pill-value {
  color: #60A5FA;
  text-shadow: 0 0 14px rgba(96, 165, 250, 0.35);
}

.total-pill-unit {
  font-size: 11px !important;
  font-weight: 700;
  color: #1565C0;
  opacity: 0.9;
  margin-left: 2px;
}

:deep(.v-theme--dark) .total-pill-unit,
.v-theme--dark .total-pill-unit {
  color: #90CAF9;
}

.total-pill-weight {
  font-size: 11px !important;
  font-weight: 700;
  color: #0891B2;
  font-family: 'JetBrains Mono', monospace !important;
}

:deep(.v-theme--dark) .total-pill-weight,
.v-theme--dark .total-pill-weight {
  color: #22D3EE;
}

/* Category Charge Card */
.category-charge-card {
  background: #FFFFFF;
  border-radius: 18px !important;
  border: 1px solid rgba(0, 0, 0, 0.07) !important;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04) !important;
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

:deep(.v-theme--dark) .category-charge-card,
.v-theme--dark .category-charge-card {
  background: rgba(30, 41, 59, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.28) !important;
}

.category-charge-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 36px rgba(13, 71, 161, 0.12) !important;
  border-color: rgba(66, 165, 245, 0.45) !important;
}

:deep(.v-theme--dark) .category-charge-card:hover,
.v-theme--dark .category-charge-card:hover {
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45) !important;
  border-color: rgba(66, 165, 245, 0.4) !important;
}

.category-card-top {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.85) 100%);
  border-color: rgba(0, 0, 0, 0.06) !important;
}

:deep(.v-theme--dark) .category-card-top,
.v-theme--dark .category-card-top {
  background: linear-gradient(180deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.75) 100%);
  border-color: rgba(255, 255, 255, 0.07) !important;
}

.cat-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(13, 71, 161, 0.08) 0%, rgba(66, 165, 245, 0.16) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(66, 165, 245, 0.28);
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(66, 165, 245, 0.12);
}

.is-prod-avatar {
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.12) 0%, rgba(6, 182, 212, 0.22) 100%) !important;
  border-color: rgba(8, 145, 178, 0.35) !important;
  box-shadow: 0 2px 8px rgba(8, 145, 178, 0.15) !important;
}

.cat-avatar-mini {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: rgba(66, 165, 245, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(66, 165, 245, 0.2);
  flex-shrink: 0;
}

.cat-count-chip {
  background: rgba(0, 0, 0, 0.05) !important;
  color: inherit !important;
}

:deep(.v-theme--dark) .cat-count-chip,
.v-theme--dark .cat-count-chip {
  background: rgba(255, 255, 255, 0.08) !important;
}

.category-metric-box {
  border-radius: 14px !important;
  transition: all 0.2s ease;
}

.price-metric-box {
  background: linear-gradient(180deg, #FFFFFF 0%, rgba(240, 249, 255, 0.65) 100%);
  border: 1px solid rgba(13, 71, 161, 0.1) !important;
}

:deep(.v-theme--dark) .price-metric-box,
.v-theme--dark .price-metric-box {
  background: linear-gradient(180deg, rgba(30, 41, 59, 0.75) 0%, rgba(15, 23, 42, 0.8) 100%);
  border: 1px solid rgba(66, 165, 245, 0.2) !important;
}

.weight-metric-box {
  background: linear-gradient(180deg, #FFFFFF 0%, rgba(236, 254, 255, 0.65) 100%);
  border: 1px solid rgba(8, 145, 178, 0.16) !important;
}

:deep(.v-theme--dark) .weight-metric-box,
.v-theme--dark .weight-metric-box {
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(8, 145, 178, 0.15) 100%);
  border: 1px solid rgba(8, 145, 178, 0.26) !important;
}

.no-weight-metric-box {
  background: linear-gradient(180deg, #FFFFFF 0%, rgba(241, 245, 249, 0.7) 100%);
  border: 1px solid rgba(148, 163, 184, 0.22) !important;
  border-style: solid !important;
  min-height: 140px;
}

:deep(.v-theme--dark) .no-weight-metric-box,
.v-theme--dark .no-weight-metric-box {
  background: linear-gradient(180deg, rgba(30, 41, 59, 0.55) 0%, rgba(15, 23, 42, 0.65) 100%);
  border: 1px solid rgba(148, 163, 184, 0.2) !important;
  border-style: solid !important;
}

.metric-mini-badge {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-blue-subtle-badge {
  background: rgba(13, 71, 161, 0.08);
  border: 1px solid rgba(66, 165, 245, 0.22);
}

:deep(.v-theme--dark) .bg-blue-subtle-badge,
.v-theme--dark .bg-blue-subtle-badge {
  background: rgba(66, 165, 245, 0.16);
  border: 1px solid rgba(66, 165, 245, 0.3);
}

.bg-cyan-subtle-badge {
  background: rgba(8, 145, 178, 0.1);
  border: 1px solid rgba(8, 145, 178, 0.22);
}

:deep(.v-theme--dark) .bg-cyan-subtle-badge,
.v-theme--dark .bg-cyan-subtle-badge {
  background: rgba(6, 182, 212, 0.16);
  border: 1px solid rgba(6, 182, 212, 0.3);
}

.price-share-pill {
  font-family: 'JetBrains Mono', monospace !important;
  letter-spacing: 0.2px;
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 100%) !important;
  box-shadow: 0 2px 6px rgba(13, 71, 161, 0.2);
}

.volume-share-pill {
  font-family: 'JetBrains Mono', monospace !important;
  letter-spacing: 0.2px;
  background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%) !important;
  box-shadow: 0 2px 6px rgba(8, 145, 178, 0.2);
}

.metric-sub-row {
  border-top-style: solid !important;
  border-color: rgba(0, 0, 0, 0.06) !important;
}

:deep(.v-theme--dark) .metric-sub-row,
.v-theme--dark .metric-sub-row {
  border-color: rgba(255, 255, 255, 0.08) !important;
}

.border-solid {
  border-style: solid !important;
}

.bg-blue-subtle {
  background: rgba(6, 182, 212, 0.04) !important;
}

:deep(.v-theme--dark) .bg-blue-subtle,
.v-theme--dark .bg-blue-subtle {
  background: rgba(6, 182, 212, 0.08) !important;
}

.bg-neutral-subtle {
  background: rgba(148, 163, 184, 0.06) !important;
  border-style: solid !important;
  border-color: rgba(148, 163, 184, 0.2) !important;
  min-height: 140px;
}

:deep(.v-theme--dark) .bg-neutral-subtle,
.v-theme--dark .bg-neutral-subtle {
  background: rgba(148, 163, 184, 0.08) !important;
  border-color: rgba(148, 163, 184, 0.2) !important;
  border-style: solid !important;
}

.no-weight-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(100, 116, 139, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(100, 116, 139, 0.2);
}

.breakdown-table {
  background: transparent !important;
}

.table-header-row {
  background: rgba(66, 165, 245, 0.05) !important;
}

:deep(.v-theme--dark) .table-header-row,
.v-theme--dark .table-header-row {
  background: rgba(66, 165, 245, 0.1) !important;
}

.table-row-hover:hover {
  background: rgba(66, 165, 245, 0.04) !important;
}

:deep(.v-theme--dark) .table-row-hover:hover,
.v-theme--dark .table-row-hover:hover {
  background: rgba(66, 165, 245, 0.08) !important;
}

/* ════════════════════════════════════════════════════════════════ */
/* SHIPPING BREAKDOWN SECTION (MODULE COLORS: #4643A0, #372E7D, #6C66BB) */
/* ════════════════════════════════════════════════════════════════ */
.shippings-breakdown-card {
  background: rgba(255, 255, 255, 0.98);
  border-color: rgba(108, 102, 187, 0.22) !important;
}

:deep(.v-theme--dark) .shippings-breakdown-card,
.v-theme--dark .shippings-breakdown-card {
  background: rgba(15, 23, 42, 0.92);
  border-color: rgba(108, 102, 187, 0.3) !important;
}

.shipping-breakdown-toolbar {
  background: linear-gradient(180deg, rgba(70, 67, 160, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
}

:deep(.v-theme--dark) .shipping-breakdown-toolbar,
.v-theme--dark .shipping-breakdown-toolbar {
  background: linear-gradient(180deg, rgba(108, 102, 187, 0.1) 0%, rgba(15, 23, 42, 0) 100%);
}

.shipping-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(55, 46, 125, 0.15) 0%, rgba(108, 102, 187, 0.15) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(108, 102, 187, 0.3);
  flex-shrink: 0;
}

/* Shipping Total KPI Capsule */
.shipping-total-pill {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96) 0%, rgba(245, 243, 255, 0.92) 50%, rgba(237, 233, 254, 0.85) 100%);
  border: 1.5px solid rgba(108, 102, 187, 0.35);
  box-shadow: 0 4px 16px rgba(55, 46, 125, 0.08), 0 1px 3px rgba(108, 102, 187, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: default;
  user-select: none;
  min-width: 260px;
}

:deep(.v-theme--dark) .shipping-total-pill,
.v-theme--dark .shipping-total-pill {
  background: linear-gradient(135deg, rgba(30, 27, 75, 0.6) 0%, rgba(15, 23, 42, 0.95) 100%);
  border: 1.5px solid rgba(108, 102, 187, 0.45);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4), 0 0 16px rgba(108, 102, 187, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.shipping-total-pill:hover {
  transform: translateY(-2px);
  border-color: rgba(108, 102, 187, 0.7);
  box-shadow: 0 8px 24px rgba(108, 102, 187, 0.24), inset 0 1px 0 rgba(255, 255, 255, 0.95);
}

.shipping-total-icon-orb {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #372E7D 0%, #4643A0 50%, #6C66BB 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 3px 10px rgba(70, 67, 160, 0.38), inset 0 1px 2px rgba(255, 255, 255, 0.4);
  border: 1.5px solid rgba(255, 255, 255, 0.3);
  transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.shipping-total-pill:hover .shipping-total-icon-orb {
  transform: scale(1.08) rotate(4deg);
}

.shipping-total-pill-label {
  font-size: 10px !important;
  font-weight: 700 !important;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  color: #4643A0;
}

:deep(.v-theme--dark) .shipping-total-pill-label,
.v-theme--dark .shipping-total-pill-label {
  color: #A78BFA;
}

.shipping-total-pill-counter {
  font-size: 10px !important;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 9999px;
  background: rgba(108, 102, 187, 0.16);
  color: #372E7D;
  letter-spacing: 0.2px;
  border: 1px solid rgba(108, 102, 187, 0.28);
}

:deep(.v-theme--dark) .shipping-total-pill-counter,
.v-theme--dark .shipping-total-pill-counter {
  background: rgba(108, 102, 187, 0.25);
  color: #DDD6FE;
  border-color: rgba(108, 102, 187, 0.4);
}

.shipping-total-pill-value {
  font-size: 17px !important;
  font-weight: 800 !important;
  letter-spacing: -0.4px;
  color: #372E7D;
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  line-height: 1;
}

:deep(.v-theme--dark) .shipping-total-pill-value,
.v-theme--dark .shipping-total-pill-value {
  color: #DDD6FE;
  text-shadow: 0 0 14px rgba(167, 139, 250, 0.35);
}

.shipping-total-pill-unit {
  font-size: 11px !important;
  font-weight: 700;
  color: #4643A0;
  opacity: 0.9;
  margin-left: 2px;
}

:deep(.v-theme--dark) .shipping-total-pill-unit,
.v-theme--dark .shipping-total-pill-unit {
  color: #A78BFA;
}

.shipping-total-pill-weight {
  font-size: 11px !important;
  font-weight: 700;
  color: #6C66BB;
  font-family: 'JetBrains Mono', monospace !important;
}

:deep(.v-theme--dark) .shipping-total-pill-weight,
.v-theme--dark .shipping-total-pill-weight {
  color: #C4B5FD;
}

/* Shipping Category Cards */
.shipping-category-card {
  background: #FFFFFF;
  border-radius: 18px !important;
  border: 1px solid rgba(108, 102, 187, 0.12) !important;
  box-shadow: 0 4px 20px rgba(55, 46, 125, 0.04) !important;
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

:deep(.v-theme--dark) .shipping-category-card,
.v-theme--dark .shipping-category-card {
  background: rgba(30, 41, 59, 0.85);
  border: 1px solid rgba(108, 102, 187, 0.25) !important;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.28) !important;
}

.shipping-category-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 36px rgba(70, 67, 160, 0.14) !important;
  border-color: rgba(108, 102, 187, 0.45) !important;
}

:deep(.v-theme--dark) .shipping-category-card:hover,
.v-theme--dark .shipping-category-card:hover {
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45) !important;
  border-color: rgba(108, 102, 187, 0.45) !important;
}

.shipping-cat-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(70, 67, 160, 0.08) 0%, rgba(108, 102, 187, 0.18) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(108, 102, 187, 0.28);
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(108, 102, 187, 0.12);
}

.is-export-avatar {
  background: linear-gradient(135deg, rgba(55, 46, 125, 0.18) 0%, rgba(70, 67, 160, 0.26) 100%) !important;
  border-color: rgba(70, 67, 160, 0.45) !important;
  box-shadow: 0 2px 8px rgba(70, 67, 160, 0.2) !important;
}

.shipping-cat-avatar-mini {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: rgba(108, 102, 187, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(108, 102, 187, 0.22);
  flex-shrink: 0;
}

.shipping-price-metric-box {
  background: linear-gradient(180deg, #FFFFFF 0%, rgba(245, 243, 255, 0.75) 100%);
  border: 1px solid rgba(70, 67, 160, 0.12) !important;
}

:deep(.v-theme--dark) .shipping-price-metric-box,
.v-theme--dark .shipping-price-metric-box {
  background: linear-gradient(180deg, rgba(30, 41, 59, 0.75) 0%, rgba(24, 20, 60, 0.8) 100%);
  border: 1px solid rgba(108, 102, 187, 0.28) !important;
}

.shipping-weight-metric-box {
  background: linear-gradient(180deg, #FFFFFF 0%, rgba(237, 233, 254, 0.65) 100%);
  border: 1px solid rgba(108, 102, 187, 0.18) !important;
}

:deep(.v-theme--dark) .shipping-weight-metric-box,
.v-theme--dark .shipping-weight-metric-box {
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(55, 46, 125, 0.2) 100%);
  border: 1px solid rgba(108, 102, 187, 0.3) !important;
}

.bg-indigo-subtle-badge {
  background: rgba(70, 67, 160, 0.08);
  border: 1px solid rgba(108, 102, 187, 0.25);
}

:deep(.v-theme--dark) .bg-indigo-subtle-badge,
.v-theme--dark .bg-indigo-subtle-badge {
  background: rgba(108, 102, 187, 0.18);
  border: 1px solid rgba(108, 102, 187, 0.35);
}

.bg-violet-subtle-badge {
  background: rgba(108, 102, 187, 0.1);
  border: 1px solid rgba(108, 102, 187, 0.25);
}

:deep(.v-theme--dark) .bg-violet-subtle-badge,
.v-theme--dark .bg-violet-subtle-badge {
  background: rgba(108, 102, 187, 0.2);
  border: 1px solid rgba(108, 102, 187, 0.35);
}

.bg-violet-subtle {
  background: rgba(108, 102, 187, 0.04) !important;
}

:deep(.v-theme--dark) .bg-violet-subtle,
.v-theme--dark .bg-violet-subtle {
  background: rgba(108, 102, 187, 0.08) !important;
}

.shipping-price-share-pill {
  font-family: 'JetBrains Mono', monospace !important;
  letter-spacing: 0.2px;
  background: linear-gradient(135deg, #372E7D 0%, #4643A0 100%) !important;
  box-shadow: 0 2px 6px rgba(70, 67, 160, 0.25);
}

.shipping-volume-share-pill {
  font-family: 'JetBrains Mono', monospace !important;
  letter-spacing: 0.2px;
  background: linear-gradient(135deg, #4643A0 0%, #6C66BB 100%) !important;
  box-shadow: 0 2px 6px rgba(108, 102, 187, 0.25);
}

.shipping-table-header-row {
  background: rgba(108, 102, 187, 0.06) !important;
}

:deep(.v-theme--dark) .shipping-table-header-row,
.v-theme--dark .shipping-table-header-row {
  background: rgba(108, 102, 187, 0.14) !important;
}

.shipping-table-row-hover:hover {
  background: rgba(108, 102, 187, 0.04) !important;
}

:deep(.v-theme--dark) .shipping-table-row-hover:hover,
.v-theme--dark .shipping-table-row-hover:hover {
  background: rgba(108, 102, 187, 0.09) !important;
}

/* ════════════════════════════════════════════════════════════════ */
/* DARK MODE TEXT COLOR CONTRAST GUARDS */
/* ════════════════════════════════════════════════════════════════ */
:deep(.v-theme--dark) .text-slate-800,
.v-theme--dark .text-slate-800 {
  color: #F1F5F9 !important;
}

:deep(.v-theme--dark) .text-blue-darken-4,
.v-theme--dark .text-blue-darken-4 {
  color: #93C5FD !important;
}

:deep(.v-theme--dark) .text-blue-darken-3,
.v-theme--dark .text-blue-darken-3 {
  color: #BFDBFE !important;
}

:deep(.v-theme--dark) .text-cyan-darken-3,
.v-theme--dark .text-cyan-darken-3 {
  color: #67E8F9 !important;
}

:deep(.v-theme--dark) .text-cyan-darken-4,
.v-theme--dark .text-cyan-darken-4 {
  color: #A5F3FC !important;
}

:deep(.v-theme--dark) .text-indigo-darken-4,
.v-theme--dark .text-indigo-darken-4 {
  color: #C7D2FE !important;
}

:deep(.v-theme--dark) .text-indigo-darken-3,
.v-theme--dark .text-indigo-darken-3 {
  color: #DDD6FE !important;
}

:deep(.v-theme--dark) .text-violet-darken-3,
.v-theme--dark .text-violet-darken-3 {
  color: #E0E7FF !important;
}

:deep(.v-theme--dark) .text-blue-grey-darken-3,
.v-theme--dark .text-blue-grey-darken-3 {
  color: #CBD5E1 !important;
}

/* AddCharge style inputs */
.addcharge-input :deep(.v-field__outline) {
  --v-field-border-opacity: 0.25;
}
</style>
