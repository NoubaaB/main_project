<template>
  <div class="jupiter-summary-section">
    <div class="d-flex align-center ga-3 mb-3">
      <v-icon style="color: #FF6F00">mdi-clipboard-text-outline</v-icon>
      <span class="text-subtitle-2 font-weight-bold">{{ $t('jupiter_summary') }}</span>
    </div>

    <v-row dense>
      <!-- Charges Card -->
      <v-col cols="12" md="4">
        <v-card class="jupiter-summary-card rounded-xl" elevation="1">
          <div class="jupiter-summary-header" style="background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%)">
            <v-icon color="white" size="24">mdi-truck-cargo-container</v-icon>
            <span class="text-white font-weight-bold text-subtitle-2">{{ $t('charges') }}</span>
          </div>
          <v-card-text class="pa-3">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-medium-emphasis">{{ $t('total_count') }}</span>
              <v-chip size="x-small" color="#42A5F5" variant="tonal" label>{{ summary.charges?.count ?? 0 }}</v-chip>
            </div>
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-medium-emphasis">
                <v-icon size="12" class="mr-1">mdi-weight-kilogram</v-icon>
                {{ $t('total_weight_sum') }}
              </span>
              <span class="text-body-2 font-weight-bold" style="color: #42A5F5">{{ main.formatDisplayNumber(summary.charges?.total_weight ?? 0) }} kg</span>
            </div>
            <div class="d-flex justify-space-between align-center">
              <span class="text-caption text-medium-emphasis">
                <v-icon size="12" class="mr-1">mdi-cash</v-icon>
                {{ $t('total_price_sum') }}
              </span>
              <span class="text-body-2 font-weight-bold" style="color: #1565C0">{{ main.formatDisplayNumber(summary.charges?.total_price ?? 0) }} {{ main.currency }}</span>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Productions Card -->
      <v-col cols="12" md="4">
        <v-card class="jupiter-summary-card rounded-xl" elevation="1">
          <div class="jupiter-summary-header" style="background: linear-gradient(135deg, #00695C 0%, #00897B 40%, #26A69A 100%)">
            <v-icon color="white" size="24">mdi-factory</v-icon>
            <span class="text-white font-weight-bold text-subtitle-2">{{ $t('production') }}</span>
          </div>
          <v-card-text class="pa-3">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-medium-emphasis">{{ $t('total_count') }}</span>
              <v-chip size="x-small" color="#00897B" variant="tonal" label>{{ summary.productions?.count ?? 0 }}</v-chip>
            </div>
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-medium-emphasis">
                <v-icon size="12" class="mr-1">mdi-weight-kilogram</v-icon>
                {{ $t('total_weight_sum') }}
              </span>
              <span class="text-body-2 font-weight-bold" style="color: #00897B">{{ main.formatDisplayNumber(summary.productions?.total_weight ?? 0) }} kg</span>
            </div>
            <div class="d-flex justify-space-between align-center">
              <span class="text-caption text-medium-emphasis">
                <v-icon size="12" class="mr-1">mdi-cash</v-icon>
                {{ $t('total_price_sum') }}
              </span>
              <span class="text-body-2 font-weight-bold" style="color: #004D40">{{ main.formatDisplayNumber(summary.productions?.total_price ?? 0) }} {{ main.currency }}</span>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Shippings Card -->
      <v-col cols="12" md="4">
        <v-card class="jupiter-summary-card rounded-xl" elevation="1">
          <div class="jupiter-summary-header" style="background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%)">
            <v-icon color="white" size="24">mdi-package-variant-closed</v-icon>
            <span class="text-white font-weight-bold text-subtitle-2">{{ $t('shippings') }}</span>
          </div>
          <v-card-text class="pa-3">
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-medium-emphasis">{{ $t('total_count') }}</span>
              <v-chip size="x-small" color="#4643a0" variant="tonal" label>{{ summary.shippings?.count ?? 0 }}</v-chip>
            </div>
            <div class="d-flex justify-space-between align-center mb-2">
              <span class="text-caption text-medium-emphasis">
                <v-icon size="12" class="mr-1">mdi-weight-kilogram</v-icon>
                {{ $t('total_weight_sum') }}
              </span>
              <span class="text-body-2 font-weight-bold" style="color: #6c66bb">{{ main.formatDisplayNumber(summary.shippings?.total_weight ?? 0) }} kg</span>
            </div>
            <div class="d-flex justify-space-between align-center">
              <span class="text-caption text-medium-emphasis">
                <v-icon size="12" class="mr-1">mdi-cash</v-icon>
                {{ $t('total_price_sum') }}
              </span>
              <span class="text-body-2 font-weight-bold" style="color: #372e7d">{{ main.formatDisplayNumber(summary.shippings?.total_price ?? 0) }} {{ main.currency }}</span>
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
};
</script>

<style scoped>
.jupiter-summary-card {
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.jupiter-summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,.12) !important;
}

.jupiter-summary-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
}
</style>
