<template>
  <v-card class="view-shipping-card" elevation="0">
    <div class="vs-hero"><div class="vs-hero-inner">
      <div class="d-flex align-center ga-4"><div class="vs-hero-icon"><v-icon size="28" color="white">mdi-eye</v-icon></div><div><div class="vs-hero-title">{{ $t('view_shipping') }} #{{ sell?.id }}</div><div class="text-white text-caption" style="opacity:.7">{{ sell ? main.formateDate(shipping.date) : '' }}</div></div></div>
      <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-arrow-left" @click="$router.push({ name: 'list_shipping' })">{{ $t('back') }}</v-btn>
    </div></div>
    <v-card-text v-if="loading" class="pa-10 text-center"><v-progress-circular indeterminate color="#4643a0"></v-progress-circular></v-card-text>
    <v-card-text v-else-if="shipping" class="pa-5">
      <v-row>
        <v-col cols="12" sm="6" md="3"><div class="info-card"><v-icon size="20" color="success" class="mb-1">mdi-tag-outline</v-icon><div class="info-label">{{ $t('shipping_category') }}</div><div class="info-value">{{ shipping.shipping_category?.[`name_${lang}`] || shipping.shipping_category?.name || '#' + shipping.shipping_category_id }}</div></div></v-col>
        <v-col cols="12" sm="6" md="3"><div class="info-card"><v-icon size="20" color="#4643a0" class="mb-1">mdi-calendar</v-icon><div class="info-label">{{ $t('date') }}</div><div class="info-value">{{ main.formateDate(shipping.date) }}</div></div></v-col>
        <v-col cols="12" sm="6" md="3"><div class="info-card"><v-icon size="20" color="#4643a0" class="mb-1">mdi-weight-kilogram</v-icon><div class="info-label">{{ $t('total_weight') }}</div><div class="info-value">{{ main.formatDisplayNumber(shipping.total_weight) }} Kg</div></div></v-col>
        <v-col cols="12" sm="6" md="3"><div class="info-card"><v-icon size="20" color="success" class="mb-1">mdi-cash</v-icon><div class="info-label">{{ $t('total_price') }}</div><div class="info-value">{{ main.formatDisplayNumber(shipping.total_price) }} {{ main.currency }}</div></div></v-col>
      </v-row>
      <div v-if="shipping.clients?.length" class="mt-4"><div class="text-subtitle-2 font-weight-bold mb-2"><v-icon size="18" color="orange" class="mr-2">mdi-account-group</v-icon>{{ $t('clients') }}</div><div class="d-flex flex-wrap ga-2"><v-chip v-for="c in shipping.clients" :key="c.id" color="orange" variant="tonal">{{ c.name }}</v-chip></div></div>
      <div class="mt-5"><div class="text-subtitle-1 font-weight-bold mb-3"><v-icon size="20" color="#4643a0" class="mr-2">mdi-table</v-icon>{{ $t('details') }}</div>
        <v-data-table :headers="detailHeaders" :items="shipping.detail_shippings || []" density="comfortable" class="rounded-lg" hover>
          <template v-slot:[`item.article`]="{ item }"><span v-if="item.article_description">{{ item.article_description }}</span><span v-else-if="item.stockable_type">{{ main.getStockableName(item.stockable_id, item.stockable_type) }}</span><span v-else class="text-grey">-</span></template>
          <template v-slot:[`item.weight`]="{ item }">{{ item.weight ? main.formatDisplayNumber(item.weight) + ' Kg' : '-' }}</template>
          <template v-slot:[`item.total_price`]="{ item }">{{ item.total_price ? main.formatDisplayNumber(item.total_price) + ' ' + main.currency : '-' }}</template>
          <template v-slot:[`item.warehouse`]="{ item }">{{ item.warehouse ? item.warehouse.name : '-' }}</template>
        </v-data-table>
      </div>
    </v-card-text>
    <v-card-text v-else class="pa-10 text-center"><v-icon size="60" color="grey-lighten-2" class="mb-3">mdi-file-search-outline</v-icon><p class="text-body-1 text-medium-emphasis">{{ $t('sell_not_found_hint') }}</p></v-card-text>
  </v-card>
</template>

<script>
import axios from 'axios';
import { useMain } from '@/stores/Main';
export default {
  name: 'ViewShipping',
  data() { return { shipping: null, loading: true }; },
  computed: {
    main() { return useMain(); },
    lang() { return this.main.lang?.code || 'en'; },
    detailHeaders() { return [
      { title: this.$t('type_article'), key: 'article', align: 'center', sortable: false },
      { title: this.$t('weight'), key: 'weight', align: 'center' },
      { title: this.$t('total_price'), key: 'total_price', align: 'center' },
      { title: this.$t('warehouse'), key: 'warehouse', align: 'center', sortable: false },
    ]; },
  },
  mounted() { this.fetchShipping(); },
  methods: {
    async fetchShipping() { this.loading = true; try { const res = await axios.get(`/api/shipping/${this.$route.params.id}`); this.shipping = res.data.shipping; } catch (e) { this.shipping = null; } this.loading = false; },
  },
};
</script>

<style scoped>
.view-shipping-card { border-radius: 20px !important; font-family: 'Inter', sans-serif; }
.vs-hero { background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 80px; }
.vs-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.vs-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; }
.vs-hero-title { font-size: 18px; font-weight: 800; }
.info-card { padding: 16px; border-radius: 14px; border: 1.5px solid rgba(var(--v-border-color), .1); text-align: center; }
.info-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: rgba(var(--v-theme-on-surface), .5); }
.info-value { font-size: 15px; font-weight: 800; margin-top: 2px; }
</style>



