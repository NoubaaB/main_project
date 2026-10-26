<template>
  <v-card class="jupiter-statistics-card rounded-2xl mb-4" elevation="1">
    <v-card-text class="pa-3 pa-sm-4">
      <!-- Section Header -->
      <div class="d-flex align-center justify-space-between pb-3 mb-3 border-b">
        <div class="d-flex align-center ga-2">
          <div class="stats-mini-icon">
            <v-icon size="18" color="#0891B2">mdi-chart-box-outline</v-icon>
          </div>
          <div>
            <span class="text-subtitle-2 font-weight-bold text-slate-800">{{ $t('statistics') }}</span>
            <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
              {{ $t('factory_operations_breakdown') || 'Comprehensive Factory Operational Metrics' }}
            </div>
          </div>
        </div>

        <v-chip size="x-small" variant="flat" color="#0891B2" class="text-white font-weight-bold px-2">
          {{ $t('live_sync') || 'Live Data' }}
        </v-chip>
      </div>

      <!-- Scrollable Statistics Body matching production/list -->
      <div class="stats-scroll-container">
        <!-- 1. CATEGORY STATISTICS (with Progress Bars) -->
        <div class="stat-section-block mb-3">
          <div class="stat-block-title d-flex align-center ga-1 mb-2">
            <v-icon size="14" color="#0891B2">mdi-tag-multiple-outline</v-icon>
            <span class="text-caption font-weight-bold text-slate-700">{{ $t('production_categories') }}</span>
          </div>

          <div v-if="!categoryStats.length" class="text-caption text-medium-emphasis text-center py-3">
            {{ $t('no_category_records') || 'No category records for this period' }}
          </div>

          <div
            v-for="(stat, si) in categoryStats"
            :key="si"
            class="lp-stat-item pa-2.5 rounded-xl border mb-2"
          >
            <!-- Header: Category Name (count) & % of Weight -->
            <div class="d-flex align-center justify-space-between mb-1.5">
              <v-chip size="x-small" color="#0891B2" variant="tonal" class="font-weight-bold text-truncate" style="max-width: 180px;">
                {{ stat.name }} ({{ stat.count }})
              </v-chip>
              <v-chip size="x-small" color="#0891B2" variant="elevated" class="font-weight-bold text-white">
                {{ stat.percent }}%
              </v-chip>
            </div>

            <!-- Progress Bar -->
            <v-progress-linear
              :model-value="stat.percent"
              color="#0891B2"
              bg-color="#CFFAFE"
              height="5"
              rounded
              class="mb-2"
            ></v-progress-linear>

            <!-- Metrics List -->
            <div class="d-flex flex-column ga-1 text-caption">
              <!-- Weight -->
              <div class="d-flex align-center justify-space-between">
                <span class="d-flex align-center text-medium-emphasis">
                  <v-icon size="13" color="#0891B2" class="mr-1">mdi-weight-kilogram</v-icon>
                  {{ $t('weight') }}:
                </span>
                <b class="text-cyan-darken-3 fnm-num">{{ main.formatDisplayNumber(stat.weight) }} kg</b>
              </div>

              <!-- Invoice Weight -->
              <div class="d-flex align-center justify-space-between">
                <span class="d-flex align-center text-medium-emphasis">
                  <v-icon size="13" color="teal-lighten-1" class="mr-1">mdi-scale-balance</v-icon>
                  {{ $t('invoice_weight') }}:
                </span>
                <b class="text-teal-darken-2 fnm-num">{{ main.formatDisplayNumber(stat.invoiceWeight) }} kg</b>
              </div>

              <!-- Total Price -->
              <div class="d-flex align-center justify-space-between">
                <span class="d-flex align-center text-medium-emphasis">
                  <v-icon size="13" color="deep-orange" class="mr-1">mdi-cash</v-icon>
                  {{ $t('total_price') }}:
                </span>
                <b class="text-deep-orange fnm-num">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b>
              </div>

              <!-- Invoice Price -->
              <div class="d-flex align-center justify-space-between">
                <span class="d-flex align-center text-medium-emphasis">
                  <v-icon size="13" color="amber-darken-2" class="mr-1">mdi-cash-check</v-icon>
                  {{ $t('invoice_price') }}:
                </span>
                <b class="text-amber-darken-2 fnm-num">{{ main.formatDisplayNumber(stat.invoicePrice) }} {{ main.currency }}</b>
              </div>
            </div>
          </div>
        </div>

        <v-divider class="my-3 border-dashed" />

        <!-- 2. INVOICING STATISTICS -->
        <div class="stat-section-block mb-3">
          <div class="stat-block-title d-flex align-center ga-1 mb-2">
            <v-icon size="14" color="success">mdi-file-document-check-outline</v-icon>
            <span class="text-caption font-weight-bold text-slate-700">{{ $t('invoicing_status') || 'Invoicing Status' }}</span>
          </div>

          <div class="d-flex flex-column ga-1.5 pa-2.5 rounded-xl border bg-slate-50">
            <!-- Invoiced vs Not Invoiced counts -->
            <div class="d-flex align-center justify-space-between">
              <span class="d-flex align-center text-caption text-medium-emphasis">
                <v-icon size="14" color="success" class="mr-1">mdi-check-decagram</v-icon>
                {{ $t('invoiced') }}:
              </span>
              <v-chip size="x-small" color="success" variant="tonal" class="font-weight-bold">
                {{ invoicingStats.total_invoiced }}
              </v-chip>
            </div>

            <div class="d-flex align-center justify-space-between">
              <span class="d-flex align-center text-caption text-medium-emphasis">
                <v-icon size="14" color="grey" class="mr-1">mdi-close-circle-outline</v-icon>
                {{ $t('not_invoiced') }}:
              </span>
              <v-chip size="x-small" color="grey" variant="tonal" class="font-weight-bold">
                {{ invoicingStats.total_not_invoiced }}
              </v-chip>
            </div>

            <!-- Invoiced Weight & Price -->
            <div class="d-flex align-center justify-space-between pt-1 border-t">
              <span class="d-flex align-center text-caption font-weight-bold">
                <v-icon size="13" color="teal-lighten-1" class="mr-1">mdi-scale-balance</v-icon>
                {{ $t('invoice_weight') }}:
              </span>
              <b class="text-teal-darken-2 text-caption fnm-num">{{ main.formatDisplayNumber(invoicingStats.invoiced_weight) }} kg</b>
            </div>

            <div class="d-flex align-center justify-space-between">
              <span class="d-flex align-center text-caption font-weight-bold">
                <v-icon size="13" color="amber-darken-2" class="mr-1">mdi-cash-check</v-icon>
                {{ $t('invoice_price') }}:
              </span>
              <b class="text-amber-darken-2 text-caption fnm-num">{{ main.formatDisplayNumber(invoicingStats.invoiced_price) }} {{ main.currency }}</b>
            </div>
          </div>
        </div>

        <v-divider class="my-3 border-dashed" />

        <!-- 3. MASS EQUILIBRIUM & OPERATIONAL BALANCE (Exact like production/list) -->
        <div class="stat-section-block mb-3">
          <div class="stat-block-title d-flex align-center ga-1 mb-2">
            <v-icon size="14" color="#0284C7">mdi-scale-balance</v-icon>
            <span class="text-caption font-weight-bold text-slate-700">{{ $t('operational_balance') || 'Mass Equilibrium & Totals' }}</span>
          </div>

          <div class="d-flex flex-column ga-1.5 pa-2.5 rounded-xl border bg-slate-50">
            <!-- Fresh Reception -->
            <div class="d-flex align-center justify-space-between text-caption">
              <span class="d-flex align-center font-weight-bold" style="color: #42A5F5;">
                <v-icon size="14" color="#42A5F5" class="mr-1">mdi-fishbowl-outline</v-icon>
                {{ $t('fresh_reception') }}:
              </span>
              <b class="fnm-num" style="color: #42A5F5;">{{ main.formatDisplayNumber(totals.fresh_reception) }} kg</b>
            </div>

            <!-- Frozen Production -->
            <div class="d-flex align-center justify-space-between text-caption">
              <span class="d-flex align-center font-weight-bold" style="color: #AB47BC;">
                <v-icon size="14" color="#AB47BC" class="mr-1">mdi-snowflake</v-icon>
                {{ $t('frozen_production') }}:
              </span>
              <b class="fnm-num" style="color: #AB47BC;">{{ main.formatDisplayNumber(totals.frozen_production) }} kg</b>
            </div>

            <!-- Guano Production -->
            <div class="d-flex align-center justify-space-between text-caption">
              <span class="d-flex align-center font-weight-bold" style="color: #8D6E63;">
                <v-icon size="14" color="#8D6E63" class="mr-1">mdi-leaf</v-icon>
                {{ $t('guano_production') }}:
              </span>
              <b class="fnm-num" style="color: #8D6E63;">{{ main.formatDisplayNumber(totals.guano_production) }} kg</b>
            </div>

            <!-- Equilibrium (Surplus / Shortage) -->
            <div class="d-flex align-center justify-space-between text-caption pt-1 border-t">
              <span class="d-flex align-center font-weight-bold">
                <v-icon size="14" :color="totals.is_surplus ? '#4CAF50' : '#EF5350'" class="mr-1">
                  {{ totals.is_surplus ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold' }}
                </v-icon>
                {{ $t('equilibrium') }}:
              </span>
              <v-chip
                size="x-small"
                :color="totals.is_surplus ? '#4CAF50' : '#EF5350'"
                variant="flat"
                class="font-weight-bold text-white px-2"
              >
                {{ main.formatDisplayNumber(Math.abs(totals.equilibrium)) }} kg ({{ $t(totals.is_surplus ? 'surplus' : 'shortage') }})
              </v-chip>
            </div>

            <v-divider class="my-1 border-dashed" />

            <!-- Factory Shippings Total -->
            <div class="d-flex align-center justify-space-between text-caption">
              <span class="d-flex align-center text-medium-emphasis">
                <v-icon size="13" color="#3B82F6" class="mr-1">mdi-truck-fast</v-icon>
                {{ $t('shippings') }}:
              </span>
              <span class="text-slate-800 font-weight-bold fnm-num">
                {{ main.formatDisplayNumber(totals.shippings?.total_weight ?? 0) }} kg
              </span>
            </div>

            <!-- Factory Charges Total -->
            <div class="d-flex align-center justify-space-between text-caption">
              <span class="d-flex align-center text-medium-emphasis">
                <v-icon size="13" color="#EF4444" class="mr-1">mdi-cash-minus</v-icon>
                {{ $t('charges') }}:
              </span>
              <span class="text-slate-800 font-weight-bold fnm-num">
                {{ main.formatDisplayNumber(totals.charges?.total_price ?? 0) }} {{ main.currency }}
              </span>
            </div>
          </div>
        </div>

        <v-divider class="my-3 border-dashed" />

        <!-- 4. SECONDARY PACKAGING CONFIGURATION (Exact like production/list) -->
        <div class="stat-section-block mb-3">
          <div class="stat-block-title d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center ga-1">
              <v-icon size="14" color="#546E7A">mdi-layers-outline</v-icon>
              <span class="text-caption font-weight-bold" style="color: #37474F;">{{ $t('secondary_packaging_config') }}</span>
            </div>
            <v-chip size="x-small" color="#546E7A" variant="elevated" class="font-weight-bold text-white">
              {{ main.formatDisplayNumber(packagingStats.total_cost) }} {{ main.currency }}
            </v-chip>
          </div>

          <div class="d-flex flex-column ga-1 pa-2 rounded-xl border bg-slate-50">
            <div
              v-for="comp in packagingStats.items"
              :key="comp.key"
              class="d-flex align-center justify-space-between px-2 py-1 rounded"
              :style="{ background: comp.qte > 0 ? 'rgba(84, 110, 122, 0.08)' : 'transparent', opacity: comp.qte > 0 ? 1 : 0.6 }"
            >
              <span class="text-caption font-weight-medium">
                {{ $t(comp.key) || comp.label }}
                <span v-if="comp.unit" class="text-grey text-caption font-weight-bold" style="opacity: 0.8">({{ comp.unit }})</span>
              </span>
              <div class="d-flex align-center ga-2 text-caption">
                <span v-if="comp.qte > 0" class="text-medium-emphasis">
                  <b class="fnm-num">{{ main.formatDisplayNumber(comp.qte) }}</b> {{ comp.unit || '' }}
                </span>
                <span v-else class="text-grey">-</span>
                <b class="text-blue-grey-darken-3 fnm-num">{{ main.formatDisplayNumber(comp.total_cost) }} {{ main.currency }}</b>
              </div>
            </div>
          </div>
        </div>

        <v-divider class="my-3 border-dashed" />

        <!-- 5. PROCESS YIELDS SUMMARY ROW -->
        <div v-if="yieldStats.length" class="stat-section-block">
          <div class="stat-block-title d-flex align-center ga-1 mb-2">
            <v-icon size="14" color="#10B981">mdi-chart-bell-curve-cumulative</v-icon>
            <span class="text-caption font-weight-bold text-emerald-800">{{ $t('yield_calculation') }}</span>
          </div>

          <div class="d-flex flex-column ga-1.5">
            <div
              v-for="py in yieldStats"
              :key="py.id"
              class="pa-2 rounded-xl border d-flex align-center justify-space-between bg-slate-50"
            >
              <div class="d-flex align-center ga-1.5">
                <v-icon size="14" color="#10B981">mdi-tag-outline</v-icon>
                <span class="text-caption font-weight-bold">{{ py.name }}</span>
              </div>
              <div class="d-flex align-center ga-2 text-caption">
                <span class="text-medium-emphasis">
                  {{ main.formatDisplayNumber(py.total_guano) }} kg / {{ main.formatDisplayNumber(py.total_frozen) }} kg
                </span>
                <v-chip
                  size="x-small"
                  variant="flat"
                  :color="py.overall_yield > 0 ? '#10B981' : '#94A3B8'"
                  class="font-weight-bold text-white px-2"
                >
                  {{ py.overall_yield }}%
                </v-chip>
              </div>
            </div>
          </div>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script>
import { useMain } from '@/stores/Main';

export default {
  name: 'JupiterStatistics',
  props: {
    statistics: {
      type: Object,
      default: () => ({
        totals: {
          fresh_reception: 0,
          frozen_production: 0,
          guano_production: 0,
          equilibrium: 0,
          is_surplus: true,
          charges: {},
          productions: {},
          shippings: {},
        },
        invoicing: {
          total_invoiced: 0,
          total_not_invoiced: 0,
          invoiced_weight: 0,
          invoiced_price: 0,
        },
        category_stats: [],
        secondary_packaging: { total_cost: 0, items: [] },
        yields: [],
      }),
    },
  },
  computed: {
    main() { return useMain(); },
    totals() { return this.statistics?.totals || {}; },
    invoicingStats() { return this.statistics?.invoicing || {}; },
    categoryStats() { return this.statistics?.category_stats || []; },
    packagingStats() { return this.statistics?.secondary_packaging || { total_cost: 0, items: [] }; },
    yieldStats() { return this.statistics?.yields || []; },
  },
};
</script>

<style scoped>
.jupiter-statistics-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(8, 145, 178, 0.18);
  box-shadow: 0 4px 20px rgba(8, 145, 178, 0.05);
  transition: all 0.25s ease;
  height: 100%;
}

:deep(.v-theme--dark) .jupiter-statistics-card,
.v-theme--dark .jupiter-statistics-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(6, 182, 212, 0.2);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.stats-mini-icon {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.15) 0%, rgba(6, 182, 212, 0.08) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(8, 145, 178, 0.2);
}

.stats-scroll-container {
  max-height: 520px;
  overflow-y: auto;
  padding-right: 4px;
}

.stats-scroll-container::-webkit-scrollbar {
  width: 5px;
}
.stats-scroll-container::-webkit-scrollbar-thumb {
  background: rgba(8, 145, 178, 0.3);
  border-radius: 4px;
}

.lp-stat-item {
  background: rgba(248, 250, 252, 0.85);
  border-color: rgba(226, 232, 240, 0.9) !important;
  transition: all 0.2s ease;
}

:deep(.v-theme--dark) .lp-stat-item,
.v-theme--dark .lp-stat-item {
  background: rgba(30, 41, 59, 0.6);
  border-color: rgba(51, 65, 85, 0.8) !important;
}

.lp-stat-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(8, 145, 178, 0.08);
}

.fnm-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
}
</style>
