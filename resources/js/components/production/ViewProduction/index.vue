<template>
  <div>
    <v-progress-circular v-if="_production.view_production_loading" indeterminate color="teal" size="60" class="d-block mx-auto my-10"></v-progress-circular>
    <v-card v-else-if="prod" class="rounded-xl ma-4" elevation="2">
      <!-- Hero Header -->
      <div class="vp-hero">
        <div class="vp-hero-inner">
          <div class="d-flex align-center ga-4">
            <div class="vp-hero-icon">
              <v-icon size="28" color="white">mdi-cog-outline</v-icon>
            </div>
            <div>
              <div class="vp-hero-title">{{ $t('view_production') }}</div>
              <div class="text-white-darken-2 text-caption" style="opacity:.7">
                {{ $t('production_details') }}
              </div>
            </div>
          </div>
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="white" rounded prepend-icon="mdi-arrow-left" size="small" @click="$router.push({ name: 'production_list' })">
            {{ $t('back') }}
          </v-btn>
        </div>
      </div>

      <v-card-text class="pa-5">
        <!-- Info Rows -->
        <v-list lines="two" class="rounded-lg">
          <!-- Date -->
          <v-list-item prepend-icon="mdi-calendar" :title="$t('date')" :subtitle="main.formateDate(prod.date)"></v-list-item>
          <v-divider></v-divider>

          <!-- Production Category -->
          <v-list-item prepend-icon="mdi-cog-outline" :title="$t('production_category')" :subtitle="prod.production_category?.[`name_${main.lang.short}`] || prod.production_category?.name || '-'"></v-list-item>
          <v-divider></v-divider>

          <!-- Linked Charge -->
          <v-list-item prepend-icon="mdi-link-variant" :title="$t('linked_charge')">
            <template #subtitle>
              <v-chip v-if="prod.charge" color="info" size="small" variant="tonal" @click="$router.push({ name: 'view_charge', params: { id: prod.charge.id } })">
                #{{ prod.charge.id }} — {{ main.formateDate(prod.charge.date) }}
              </v-chip>
              <span v-else>-</span>
            </template>
          </v-list-item>
          <v-divider></v-divider>

          <!-- Suppliers -->
          <v-list-item prepend-icon="mdi-truck-delivery" :title="$t('suppliers')">
            <template #subtitle>
              <v-chip v-for="s in (prod.suppliers || [])" :key="s.id" color="teal" size="small" variant="tonal" class="mr-1">{{ s.name }}</v-chip>
              <span v-if="!prod.suppliers?.length">-</span>
            </template>
          </v-list-item>
          <v-divider></v-divider>

          <!-- Tracking Numbers -->
          <v-list-item v-if="prod.tracking_numbers?.length" prepend-icon="mdi-barcode" :title="$t('tracking_numbers')">
            <template #subtitle>
              <v-chip v-for="tn in prod.tracking_numbers" :key="tn" color="blue" size="small" variant="tonal" class="mr-1">{{ tn }}</v-chip>
            </template>
          </v-list-item>

          <!-- Production Tracking Numbers -->
          <v-list-item v-if="prod.production_tracking_numbers?.length" prepend-icon="mdi-factory" :title="$t('production_tracking_numbers')">
            <template #subtitle>
              <v-chip v-for="ptn in prod.production_tracking_numbers" :key="ptn" color="teal" size="small" variant="tonal" class="mr-1">{{ ptn }}</v-chip>
            </template>
          </v-list-item>

          <v-divider></v-divider>

          <!-- Totals -->
          <v-list-item prepend-icon="mdi-weight-kilogram" :title="$t('total_weight')" :subtitle="main.formatDisplayNumber(prod.total_weight) + ' Kg'"></v-list-item>
          <v-divider></v-divider>
          <v-list-item prepend-icon="mdi-cash" :title="$t('total_price')" :subtitle="main.formatDisplayNumber(prod.total_price) + ' ' + main.currency"></v-list-item>
          <v-divider></v-divider>
          <v-list-item prepend-icon="mdi-package-variant-closed" :title="$t('total_package_count')" :subtitle="main.formatDisplayNumber(prod.total_package_count)"></v-list-item>

          <!-- Note -->
          <template v-if="prod.note">
            <v-divider></v-divider>
            <v-list-item prepend-icon="mdi-note-text" :title="$t('note')" :subtitle="prod.note"></v-list-item>
          </template>
        </v-list>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useProduction } from '@/stores/Production';

export default {
  computed: {
    main() { return useMain(); },
    _production() { return useProduction(); },
    prod() { return this._production.view_production; },
  },
  mounted() {
    const id = this.$route.params.id;
    this._production.fetchProductionById(id);
  },
}
</script>

<style scoped>
.vp-hero { background: linear-gradient(135deg, #00695C 0%, #00897B 40%, #26A69A 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
.vp-hero-inner { display: flex; align-items: center; position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.vp-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.vp-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
</style>
