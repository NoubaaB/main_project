<template>
  <v-card class="view-charge-card" elevation="0">
    <div class="vc-hero">
      <div class="vc-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="vc-hero-icon"><v-icon size="28" color="white">mdi-eye</v-icon></div>
          <div>
            <div class="vc-hero-title">{{ $t('view_charge') }} #{{ charge?.id }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ charge ? main.formateDate(charge.date) : '' }}</div>
          </div>
        </div>
        <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-arrow-left" @click="$router.push({ name: 'list_charge' })">{{ $t('back') }}</v-btn>
      </div>
    </div>

    <v-card-text v-if="loading" class="pa-10 text-center"><v-progress-circular indeterminate color="info"></v-progress-circular></v-card-text>

    <v-card-text v-else-if="charge" class="pa-5">
      <!-- Main info cards -->
      <v-row>
        <v-col cols="12" sm="6" md="3">
          <div class="info-card">
            <v-icon size="20" color="info" class="mb-1">mdi-shape-outline</v-icon>
            <div class="info-label">{{ $t('charge_category') }}</div>
            <div class="info-value">{{ main.getChargeCategoryName(charge.charge_category_id) }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <div class="info-card">
            <v-icon size="20" color="info" class="mb-1">mdi-calendar</v-icon>
            <div class="info-label">{{ $t('date') }}</div>
            <div class="info-value">{{ main.formateDate(charge.date) }}</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <div class="info-card">
            <v-icon size="20" color="info" class="mb-1">mdi-weight-kilogram</v-icon>
            <div class="info-label">{{ $t('total_weight') }}</div>
            <div class="info-value">{{ main.formatDisplayNumber(charge.total_weight) }} Kg</div>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <div class="info-card">
            <v-icon size="20" color="success" class="mb-1">mdi-cash</v-icon>
            <div class="info-label">{{ $t('total_price') }}</div>
            <div class="info-value">{{ main.formatDisplayNumber(charge.total_price) }} {{ main.currency }}</div>
          </div>
        </v-col>
      </v-row>

      <!-- Tracking numbers -->
      <div v-if="charge.reception_tracking_number?.length" class="mt-4">
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="teal" class="mr-2">mdi-barcode-scan</v-icon>{{ $t('reception_tracking_number') }}</div>
        <div class="d-flex flex-wrap ga-2">
          <v-chip v-for="tn in charge.reception_tracking_number" :key="tn" color="teal" variant="tonal">{{ tn }}</v-chip>
        </div>
      </div>

      <!-- Suppliers -->
      <div v-if="charge.suppliers?.length" class="mt-4">
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="orange" class="mr-2">mdi-truck-delivery</v-icon>{{ $t('suppliers') }}</div>
        <div class="d-flex flex-wrap ga-2">
          <v-chip v-for="s in charge.suppliers" :key="s.id" color="orange" variant="tonal">{{ s.name }}</v-chip>
        </div>
      </div>

      <!-- Detail charges table -->
      <div class="mt-5">
        <div class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center"><v-icon size="20" color="info" class="mr-2">mdi-table</v-icon>{{ $t('details') }}</div>
        <v-data-table
          :headers="detailHeaders"
          :items="charge.detail_charges || []"
          density="comfortable"
          class="rounded-lg"
          hover
        >
          <template v-slot:[`item.article`]="{ item }">
            <span v-if="item.article_description">{{ item.article_description }}</span>
            <span v-else-if="item.stockable_type">{{ main.getStockableName(item.stockable_id, item.stockable_type) }}</span>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:[`item.weight`]="{ item }">{{ item.weight ? main.formatDisplayNumber(item.weight) + ' Kg' : '-' }}</template>
          <template v-slot:[`item.unit_price`]="{ item }">{{ item.unit_price ? main.formatDisplayNumber(item.unit_price) : '-' }}</template>
          <template v-slot:[`item.total_price`]="{ item }">{{ item.total_price ? main.formatDisplayNumber(item.total_price) + ' ' + main.currency : '-' }}</template>
          <template v-slot:[`item.warehouse`]="{ item }">{{ item.warehouse ? item.warehouse.name : '-' }}</template>
          <template v-slot:[`item.warehouse_operation`]="{ item }">
            <v-chip v-if="item.warehouse_operation" :color="item.warehouse_operation === 'stock_in' ? 'success' : 'warning'" size="x-small" variant="tonal">{{ $t(item.warehouse_operation) }}</v-chip>
            <span v-else class="text-grey">-</span>
          </template>
        </v-data-table>
      </div>

    </v-card-text>

    <v-card-text v-else class="pa-10 text-center">
      <v-icon size="60" color="grey-lighten-2" class="mb-3">mdi-file-search-outline</v-icon>
      <p class="text-body-1 text-medium-emphasis">{{ $t('charge_not_found_hint') }}</p>
    </v-card-text>
  </v-card>
</template>

<script>
import axios from 'axios';
import { useMain } from '@/stores/Main';

export default {
  name: 'ViewCharge',
  data() { return { charge: null, loading: true }; },
  computed: {
    main() { return useMain(); },
    detailHeaders() {
      return [
        { title: this.$t('type_article'), key: 'article', align: 'center', sortable: false },
        { title: this.$t('weight'), key: 'weight', align: 'center' },
        { title: this.$t('unit_price'), key: 'unit_price', align: 'center' },
        { title: this.$t('total_price'), key: 'total_price', align: 'center' },
        { title: this.$t('warehouse'), key: 'warehouse', align: 'center', sortable: false },
        { title: this.$t('warehouse_operation'), key: 'warehouse_operation', align: 'center', sortable: false },
      ];
    },
  },
  mounted() { this.fetchCharge(); },
  methods: {
    async fetchCharge() {
      this.loading = true;
      try {
        const res = await axios.get(`/api/charge/${this.$route.params.id}`);
        this.charge = res.data.charge;
      } catch (e) { console.error(e); this.charge = null; }
      this.loading = false;
    },
  },
};
</script>

<style scoped>
.view-charge-card { border-radius: 20px !important; font-family: 'Inter', sans-serif; }
.vc-hero { background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 80px; }
.vc-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.vc-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; }
.vc-hero-title { font-size: 18px; font-weight: 800; }
.info-card { padding: 16px; border-radius: 14px; border: 1.5px solid rgba(var(--v-border-color), .1); text-align: center; }
.info-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: rgba(var(--v-theme-on-surface), .5); }
.info-value { font-size: 15px; font-weight: 800; margin-top: 2px; }
</style>
