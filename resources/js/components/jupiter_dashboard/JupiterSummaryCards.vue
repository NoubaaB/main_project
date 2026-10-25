<template>
  <div class="jupiter-summary-section mb-6">
    <!-- Header with generous spacing and relief -->
    <div class="d-flex align-center ga-3 mb-3">
      <div class="summary-mini-icon">
        <v-icon size="20" color="#0891B2">mdi-chart-box-multiple-outline</v-icon>
      </div>
      <div>
        <div class="text-subtitle-1 font-weight-bold text-slate-800">{{ $t('jupiter_summary') }}</div>
        <div class="text-caption text-medium-emphasis mt-1" style="font-size: 11px !important; line-height: 1.4;">
          {{ $t('all_time_stats') || 'Consolidated Operations' }}
        </div>
      </div>
    </div>

    <!-- 3 Luxury Cards -->
    <v-row dense>
      <!-- 1. CHARGES CARD (Blue) -->
      <v-col cols="12" md="4">
        <v-card class="luxury-summary-card card-charges rounded-2xl elevation-1">
          <!-- Card Header Banner -->
          <div class="card-header-banner banner-charges">
            <div class="d-flex align-center ga-3">
              <div class="banner-icon-badge">
                <v-icon color="white" size="22">mdi-truck-cargo-container</v-icon>
              </div>
              <div>
                <div class="text-white font-weight-bold text-subtitle-2">{{ $t('charges') }}</div>
                <div class="text-white text-caption mt-0.5" style="opacity: 0.85; font-size: 11px !important;">
                  {{ summary.charges?.count ?? 0 }} {{ $t('records') }}
                </div>
              </div>
            </div>
            <v-chip size="x-small" variant="flat" class="banner-chip-charges font-weight-bold">
              {{ summary.charges?.count ?? 0 }}
            </v-chip>
          </div>

          <!-- Card Content Metrics -->
          <v-card-text class="pa-4">
            <!-- Total Weight -->
            <div class="metric-block mb-3">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis font-weight-medium d-flex align-center">
                  <v-icon size="15" class="mr-2 text-blue">mdi-weight-kilogram</v-icon>
                  {{ $t('total_weight_sum') }}
                </span>
                <span class="text-caption text-medium-emphasis">
                  {{ avgWeight(summary.charges) }} kg / rec
                </span>
              </div>
              <div class="metric-value text-blue-darken-2 fnm-currency-num">
                {{ main.formatDisplayNumber(summary.charges?.total_weight ?? 0) }}
                <span class="metric-unit">kg</span>
              </div>
            </div>

            <v-divider class="my-2 border-dashed opacity-50" />

            <!-- Total Price -->
            <div class="metric-block">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis font-weight-medium d-flex align-center">
                  <v-icon size="15" class="mr-2 text-blue-darken-3">mdi-cash</v-icon>
                  {{ $t('total_price_sum') }}
                </span>
                <span class="text-caption text-medium-emphasis">
                  {{ avgPrice(summary.charges) }} {{ main.currency }} / rec
                </span>
              </div>
              <div class="metric-value text-blue-darken-4 fnm-currency-num">
                {{ main.formatDisplayNumber(summary.charges?.total_price ?? 0) }}
                <span class="metric-unit">{{ main.currency }}</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- 2. PRODUCTIONS CARD (Teal) -->
      <v-col cols="12" md="4">
        <v-card class="luxury-summary-card card-productions rounded-2xl elevation-1">
          <!-- Card Header Banner -->
          <div class="card-header-banner banner-productions">
            <div class="d-flex align-center ga-3">
              <div class="banner-icon-badge">
                <v-icon color="white" size="22">mdi-factory</v-icon>
              </div>
              <div>
                <div class="text-white font-weight-bold text-subtitle-2">{{ $t('production') }}</div>
                <div class="text-white text-caption mt-0.5" style="opacity: 0.85; font-size: 11px !important;">
                  {{ summary.productions?.count ?? 0 }} {{ $t('records') }}
                </div>
              </div>
            </div>
            <v-chip size="x-small" variant="flat" class="banner-chip-productions font-weight-bold">
              {{ summary.productions?.count ?? 0 }}
            </v-chip>
          </div>

          <!-- Card Content Metrics -->
          <v-card-text class="pa-4">
            <!-- Total Weight -->
            <div class="metric-block mb-3">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis font-weight-medium d-flex align-center">
                  <v-icon size="15" class="mr-2 text-teal">mdi-weight-kilogram</v-icon>
                  {{ $t('total_weight_sum') }}
                </span>
                <span class="text-caption text-medium-emphasis">
                  {{ avgWeight(summary.productions) }} kg / rec
                </span>
              </div>
              <div class="metric-value text-teal-darken-2 fnm-currency-num">
                {{ main.formatDisplayNumber(summary.productions?.total_weight ?? 0) }}
                <span class="metric-unit">kg</span>
              </div>
            </div>

            <v-divider class="my-2 border-dashed opacity-50" />

            <!-- Total Price -->
            <div class="metric-block">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis font-weight-medium d-flex align-center">
                  <v-icon size="15" class="mr-2 text-teal-darken-3">mdi-cash</v-icon>
                  {{ $t('total_price_sum') }}
                </span>
                <span class="text-caption text-medium-emphasis">
                  {{ avgPrice(summary.productions) }} {{ main.currency }} / rec
                </span>
              </div>
              <div class="metric-value text-teal-darken-4 fnm-currency-num">
                {{ main.formatDisplayNumber(summary.productions?.total_price ?? 0) }}
                <span class="metric-unit">{{ main.currency }}</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- 3. SHIPPINGS CARD (Purple) -->
      <v-col cols="12" md="4">
        <v-card class="luxury-summary-card card-shippings rounded-2xl elevation-1">
          <!-- Card Header Banner -->
          <div class="card-header-banner banner-shippings">
            <div class="d-flex align-center ga-3">
              <div class="banner-icon-badge">
                <v-icon color="white" size="22">mdi-package-variant-closed</v-icon>
              </div>
              <div>
                <div class="text-white font-weight-bold text-subtitle-2">{{ $t('shippings') }}</div>
                <div class="text-white text-caption mt-0.5" style="opacity: 0.85; font-size: 11px !important;">
                  {{ summary.shippings?.count ?? 0 }} {{ $t('records') }}
                </div>
              </div>
            </div>
            <v-chip size="x-small" variant="flat" class="banner-chip-shippings font-weight-bold">
              {{ summary.shippings?.count ?? 0 }}
            </v-chip>
          </div>

          <!-- Card Content Metrics -->
          <v-card-text class="pa-4">
            <!-- Total Weight -->
            <div class="metric-block mb-3">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis font-weight-medium d-flex align-center">
                  <v-icon size="15" class="mr-2 text-deep-purple">mdi-weight-kilogram</v-icon>
                  {{ $t('total_weight_sum') }}
                </span>
                <span class="text-caption text-medium-emphasis">
                  {{ avgWeight(summary.shippings) }} kg / rec
                </span>
              </div>
              <div class="metric-value text-deep-purple-darken-1 fnm-currency-num">
                {{ main.formatDisplayNumber(summary.shippings?.total_weight ?? 0) }}
                <span class="metric-unit">kg</span>
              </div>
            </div>

            <v-divider class="my-2 border-dashed opacity-50" />

            <!-- Total Price -->
            <div class="metric-block">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis font-weight-medium d-flex align-center">
                  <v-icon size="15" class="mr-2 text-deep-purple-darken-3">mdi-cash</v-icon>
                  {{ $t('total_price_sum') }}
                </span>
                <span class="text-caption text-medium-emphasis">
                  {{ avgPrice(summary.shippings) }} {{ main.currency }} / rec
                </span>
              </div>
              <div class="metric-value text-deep-purple-darken-3 fnm-currency-num">
                {{ main.formatDisplayNumber(summary.shippings?.total_price ?? 0) }}
                <span class="metric-unit">{{ main.currency }}</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';

export default {
  name: 'JupiterSummaryCards',
  props: {
    summary: {
      type: Object,
      default: () => ({
        charges: { count: 0, total_weight: 0, total_price: 0 },
        productions: { count: 0, total_weight: 0, total_price: 0 },
        shippings: { count: 0, total_weight: 0, total_price: 0 },
      }),
    },
  },
  computed: {
    main() { return useMain(); },
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
  },
};
</script>

<style scoped>
.summary-mini-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.15) 0%, rgba(6, 182, 212, 0.08) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(8, 145, 178, 0.2);
  flex-shrink: 0;
}

.luxury-summary-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
  transition: all 0.25s ease;
  overflow: hidden;
}

:deep(.v-theme--dark) .luxury-summary-card,
.v-theme--dark .luxury-summary-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.luxury-summary-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 26px rgba(0, 0, 0, 0.08) !important;
}

.card-header-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
}

.banner-charges {
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%);
}
.banner-productions {
  background: linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%);
}
.banner-shippings {
  background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%);
}

.banner-icon-badge {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.banner-chip-charges {
  background: rgba(255, 255, 255, 0.22) !important;
  color: white !important;
}
.banner-chip-productions {
  background: rgba(255, 255, 255, 0.22) !important;
  color: white !important;
}
.banner-chip-shippings {
  background: rgba(255, 255, 255, 0.22) !important;
  color: white !important;
}

.metric-value {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.3px;
  line-height: 1.2;
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  -webkit-font-feature-settings: "tnum" 1 !important;
  font-feature-settings: "tnum" 1 !important;
}

:deep(.v-theme--dark) .card-charges .metric-value,
.v-theme--dark .card-charges .metric-value {
  color: #60A5FA !important;
}
:deep(.v-theme--dark) .card-productions .metric-value,
.v-theme--dark .card-productions .metric-value {
  color: #2DD4BF !important;
}
:deep(.v-theme--dark) .card-shippings .metric-value,
.v-theme--dark .card-shippings .metric-value {
  color: #A78BFA !important;
}

.metric-unit {
  font-size: 13px;
  font-weight: 600;
  opacity: 0.8;
  margin-left: 2px;
}
</style>
