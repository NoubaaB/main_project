<template>
  <v-card class="rounded-lg" elevation="1">
    <v-card-title class="text-teal d-flex align-center">
      <v-icon class="mr-2" color="teal">mdi-sigma</v-icon>
      {{ $t('production_summary') }}
    </v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="4">
          <v-card variant="tonal" color="teal" class="pa-3 text-center rounded-lg">
            <div class="text-caption text-medium-emphasis">{{ $t('total_weight') }}</div>
            <div class="text-h6 font-weight-bold">{{ main.formatDisplayNumber(_production.production.total_weight || 0) }} Kg</div>
          </v-card>
        </v-col>
        <v-col cols="4">
          <v-card variant="tonal" color="blue" class="pa-3 text-center rounded-lg">
            <div class="text-caption text-medium-emphasis">{{ $t('total_price') }}</div>
            <div class="text-h6 font-weight-bold">{{ main.formatDisplayNumber(_production.production.total_price || 0) }} {{ main.currency }}</div>
          </v-card>
        </v-col>
        <v-col cols="4">
          <v-card variant="tonal" color="purple" class="pa-3 text-center rounded-lg">
            <div class="text-caption text-medium-emphasis">{{ $t('total_package_count') }}</div>
            <div class="text-h6 font-weight-bold">{{ main.formatDisplayNumber(_production.production.total_package_count || 0) }}</div>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useProduction } from '@/stores/Production';
export default {
  computed: {
    main() { return useMain(); },
    _production() { return useProduction(); },
  }
}
</script>
