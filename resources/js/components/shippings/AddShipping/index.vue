<template>
  <v-form ref="form" @submit.prevent="submitShipping">
    <v-row>
      <!-- LEFT PANEL -->
      <v-col cols="12" md="5">
        <v-card class="add-shipping-card ma-auto" elevation="0">
          <!-- Hero Header -->
          <div class="ash-hero">
            <div class="ash-hero-inner">
              <div class="d-flex align-center ga-4">
                <div class="ash-hero-icon">
                  <v-icon size="28" color="white">mdi-truck-delivery-outline</v-icon>
                </div>
                <div>
                  <div class="ash-hero-title">{{ $t("shipping_create_interface") }}</div>
                  <div class="text-white text-caption" style="opacity:.7">{{ $t('new_shipping_desc') }}</div>
                </div>
              </div>
            </div>
          </div>
          <v-card-text v-if="shippingStore.shipping">
            <!-- - Necessary Information -->
            <v-row><v-col class="d-flex align-center">
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-shipping">mdi-numeric-1-circle-outline</v-icon></v-col></v-row>
                <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("necessary_information") }}</span></v-col></v-row>
              </div>
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
            </v-col></v-row>

            <v-row class="text-center">
              <v-col>
                <v-autocomplete v-model="shippingStore.shipping.shipping_category_id" :items="shippingCategoryOptions" :label="$t('shipping_category')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details @update:modelValue="onCategoryChange">
                  <template #chip="{ item }"><v-chip  color="#4643a0" variant="tonal">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </v-col>
              <v-col cols="2" class="py-2 pl-0"><v-btn variant="tonal" color="#4643a0" icon="mdi-notebook-plus-outline" :disabled="!shippingStore.shipping?.shipping_category_id" @click="descriptionDialog = true"></v-btn></v-col>
            </v-row>

            <v-row class="text-center"><v-col>
              <DatePicker :is-dark="main.is_dark" expanded :max-date="new Date()" v-model="dateModel" :locale="main.lang?.short || 'en'" mode="date" color="purple" />
            </v-col></v-row>

            <!-- - Additional Information -->
            <v-row><v-col class="d-flex align-center">
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-shipping">mdi-numeric-2-circle-outline</v-icon></v-col></v-row>
                <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("additional_information") }}</span></v-col></v-row>
              </div>
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
            </v-col></v-row>

            <v-row><v-col>
              <v-btn block rounded variant="tonal" color="#4643a0" :disabled="!shippingStore.shipping?.shipping_category_id" @click="shippingStore.showAdditionalInfo = !shippingStore.showAdditionalInfo" :prepend-icon="shippingStore.showAdditionalInfo ? 'mdi-chevron-up' : 'mdi-chevron-down'">{{ $t('expand') }}</v-btn>
            </v-col></v-row>

            <v-expand-transition>
              <div v-show="shippingStore.showAdditionalInfo && selectedCategory">
                <Shipping ref="shippingForm" />
              </div>
            </v-expand-transition>

            <!-- - Summary -->
            <v-row v-if="shippingStore.shipping && shippingStore.shipping.details.length > 0">
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-shipping">mdi-numeric-3-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("summary") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
              </v-col>
            </v-row>
            <div v-if="shippingStore.shipping && shippingStore.shipping.details.length > 0" class="charge-summary-bar">
              <div v-for="(cat, idx) in categorySummary" :key="cat.id" class="summary-category">
                <v-chip size="small" :color="categoryColors[idx % categoryColors.length]" variant="tonal" class="mb-1 font-weight-bold">{{ cat.name }}</v-chip>
                <div class="d-flex align-center ga-3">
                  <div class="summary-item"><v-icon size="16" :color="categoryColors[idx % categoryColors.length]" class="mr-1">mdi-weight-kilogram</v-icon><span class="summary-label">{{ $t('weight') }}:</span><span class="summary-value" :style="{ color: `rgb(var(--v-theme-${categoryColors[idx % categoryColors.length]}))` }">{{ main.formatDisplayNumber(cat.totalWeight) }} Kg</span></div>
                  <div class="summary-item"><v-icon size="16" :color="categoryColors[idx % categoryColors.length]" class="mr-1">mdi-cash</v-icon><span class="summary-label">{{ $t('total_price') }}:</span><span class="summary-value" :style="{ color: `rgb(var(--v-theme-${categoryColors[idx % categoryColors.length]}))` }">{{ main.formatDisplayNumber(cat.totalPrice) }} {{ main.currency }}</span></div>
                </div>
              </div>
              <v-divider class="my-2"></v-divider>
              <div class="summary-global">
                <v-chip size="small" color="#4643a0" variant="elevated" class="mb-1 font-weight-bold">{{ $t('global_total') }}</v-chip>
                <div class="d-flex align-center ga-4">
                  <div class="summary-item"><v-icon size="18" color="#4643a0" class="mr-1">mdi-weight-kilogram</v-icon><span class="summary-label">{{ $t('total_weight') }}:</span><span class="summary-value text-info">{{ main.formatDisplayNumber(shippingStore.shipping.total_weight) }} Kg</span></div>
                  <div class="summary-item"><v-icon size="18" color="success" class="mr-1">mdi-cash</v-icon><span class="summary-label">{{ $t('total_price') }}:</span><span class="summary-value text-success">{{ main.formatDisplayNumber(shippingStore.shipping.total_price) }} {{ main.currency }}</span></div>
                </div>
              </div>
            </div>

            <v-row class="mt-3">
              <v-col cols="6"><v-btn block rounded variant="outlined" color="#4643a0" :disabled="!canSubmit" :loading="submitting" @click="submitShipping">{{ $t('submit') }}</v-btn></v-col>
              <v-col cols="6"><v-btn block rounded variant="tonal" color="red-lighten-1" :disabled="!shippingStore.shipping" @click="resetShipping">{{ $t('cancel') }}</v-btn></v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <v-divider vertical></v-divider>

      <!-- RIGHT PANEL -->
      <v-col cols="12" md="7">
        <detailShippings v-if="shippingStore.shipping && shippingStore.shipping.shipping_category_id" />
        <div v-else class="d-flex flex-column align-center justify-center" style="min-height: 300px; opacity: .5;">
          <v-icon size="80" color="#4643a0" class="mb-4">mdi-clipboard-text-outline</v-icon>
          <div class="text-h6 font-weight-medium" style="color: #4643a0">{{ $t('select_shipping_category_to_start') }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#4643a0" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('record_added_success') }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.category"><v-icon size="14" color="#4643a0" class="mr-1">mdi-tag</v-icon><span class="text-medium-emphasis">{{ $t('shipping_category') }}:</span> <strong>{{ summarySnackbar.category }}</strong></div>
        <div v-if="summarySnackbar.date"><v-icon size="14" color="#4643a0" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('date') }}:</span> <strong>{{ summarySnackbar.date }}</strong></div>
        <div v-if="summarySnackbar.trackingNumbers"><v-icon size="14" color="#4643a0" class="mr-1">mdi-barcode</v-icon><span class="text-medium-emphasis">{{ $t('shipping_tracking_number') }}:</span> <strong>{{ summarySnackbar.trackingNumbers }}</strong></div>
        <div v-if="summarySnackbar.client"><v-icon size="14" color="#4643a0" class="mr-1">mdi-account</v-icon><span class="text-medium-emphasis">{{ $t('client') }}:</span> <strong>{{ summarySnackbar.client }}</strong></div>
        <v-divider class="my-1"></v-divider>
        <div class="d-flex ga-4">
          <div><v-icon size="14" color="#4643a0" class="mr-1">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('total_weight') }}:</span> <strong class="text-shipping">{{ summarySnackbar.totalWeight }} Kg</strong></div>
          <div><v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span> <strong class="text-success">{{ summarySnackbar.totalPrice }} {{ main.currency }}</strong></div>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>

    <!-- Description Dialog -->
    <v-dialog v-model="descriptionDialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#4643a0" class="mr-2">mdi-notebook-edit-outline</v-icon>
          <span>{{ $t('description') }}</span>
          <v-spacer></v-spacer>
          <v-chip size="small" :color="descriptionLength > 1000 ? 'error' : '#4643a0'" variant="tonal">
            {{ descriptionLength }} / 1000
          </v-chip>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-4">
          <v-textarea
            v-model="shippingStore.shipping.description"
            :label="$t('description')"
            :counter="1000"
            :maxlength="1000"
            rows="6"
            variant="outlined"
            rounded
            color="#4643a0"
            class="text-shipping"
            auto-grow
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="#4643a0" rounded @click="descriptionDialog = false" prepend-icon="mdi-check">{{ $t('done') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-form>
</template>

<script>
import axios from 'axios';
import { useShipping } from '@/stores/Shipping';
import { useMain } from '@/stores/Main';

import { useShippingCategory } from '@/stores/ShippingCategory';
import { useDetailShippingCategory } from '@/stores/DetailShippingCategory';
import { useAuth } from '@/stores/Auth';
import Shipping from './Shipping.vue';
import detailShippings from './DetailShippings.vue';

export default {
  name: 'AddShipping',
  components: { Shipping, detailShippings },
  data() { return { submitting: false, descriptionDialog: false, categoryColors: ['info', 'teal', 'purple', 'orange', 'pink', 'cyan', 'amber', 'indigo'], summarySnackbar: { show: false, type: '', category: '', date: '', trackingNumbers: '', client: '', totalWeight: '', totalPrice: '' }, snackbarProgress: 100, snackbarTimer: null }; },
  computed: {
    shippingStore() { return useShipping(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    selectedCategory() { return useShippingCategory().list.find(c => c.id === this.shippingStore.shipping?.shipping_category_id); },
    shippingCategoryOptions() { return useShippingCategory().list.map(c => ({ title: c[`name_${this.lang}`] || c.name || `#${c.id}`, value: c.id })); },
    canSubmit() { const s = this.shippingStore.shipping; return s?.date && s?.shipping_category_id && s?.details?.length > 0; },
    descriptionLength() { return (this.shippingStore.shipping?.description || '').length; },
    dateModel: {
      get() { if (!this.shippingStore.shipping?.date) return null; return new Date(this.shippingStore.shipping.date + 'T00:00:00'); },
      set(val) { if (!this.shippingStore.shipping) return; if (val instanceof Date) { const y = val.getFullYear(); const m = String(val.getMonth() + 1).padStart(2, '0'); const d = String(val.getDate()).padStart(2, '0'); this.shippingStore.shipping.date = `${y}-${m}-${d}`; } else { this.shippingStore.shipping.date = val; } }
    },
    categorySummary() {
      const details = this.shippingStore.shipping?.details || []; if (!details.length) return [];
      const detailCats = useDetailShippingCategory().list; const map = {};
      details.forEach(d => {
        const catId = d.detail_shipping_category_id; if (!map[catId]) { const cat = detailCats.find(c => c.id === catId); map[catId] = { id: catId, name: cat ? (cat[`name_${this.lang}`] || cat.name_fr || cat.name_en || `#${catId}`) : `#${catId}`, totalWeight: 0, totalPrice: 0 }; }
        map[catId].totalWeight += parseFloat(d.weight) || 0; map[catId].totalPrice += parseFloat(d.total_price) || 0;
      });
      return Object.values(map);
    },
  },
  mounted() {
    this.shippingStore._restoreDraft();
    if (!this.shippingStore.shipping) this.shippingStore.add_shipping(null);
  },
  watch: {
    'shippingStore.shipping': { handler() { this.shippingStore._persistDraft(); }, deep: true },
    'shippingStore.showAdditionalInfo'() { this.shippingStore._persistDraft(); },
  },
  methods: {
    onCategoryChange() { if (this.shippingStore.shipping) { this.shippingStore.shipping.details = []; this.shippingStore.shipping.shipping_tracking_number = []; this.shippingStore.shipping.tracking_onp_numbers = []; this.shippingStore.shipping.client_id = null; this.shippingStore.shipping.truck_ids = []; this.shippingStore.shipping.origin_id = null; this.shippingStore.shipping.direction = null; this.shippingStore.shipping.fishery_id = null; this.shippingStore.shipping.ship_id = null; this.shippingStore.shipping.total_price = 0; this.shippingStore.shipping.total_weight = 0; } this.shippingStore.showAdditionalInfo = false; },
    resetShipping() { this.shippingStore.add_shipping(null); this.shippingStore.clearDraft(); sessionStorage.removeItem('detail_shippings_draft'); this.main.add_snackbar({ show: true, message: this.$t('reset_shipping'), color: 'success' }); },

    async submitShipping() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const s = JSON.parse(JSON.stringify(this.shippingStore.shipping));
        s.details = s.details.map(d => { const { _uid, ...rest } = d; return rest; }); delete s.id;
        await axios.post('/api/shipping', s);
        this.shippingStore.clearDraft();
        sessionStorage.removeItem('detail_shippings_draft');
        this.shippingStore.add_shipping(null);
        this.showSnackbarFromSnapshot(snapshot, 'submit');
        this.$router.push({ name: 'list_shipping' });
      } catch (err) { this.main.add_snackbar({ show: true, message: err.response?.data?.message || this.$t('record_added_failed'), color: 'error' }); }
      this.submitting = false;
    },
    buildSnapshot() {
      const s = this.shippingStore.shipping;
      const cat = useShippingCategory().list.find(c => c.id === s?.shipping_category_id);
      const clientName = s?.client_id ? (this.auth.client.list.find(c => c.id === s.client_id)?.name || '') : '';
      return {
        category: cat ? (cat[`name_${this.lang}`] || cat.name || `#${cat.id}`) : '',
        date: s?.date || '',
        trackingNumbers: (s?.shipping_tracking_number || []).join(', '),
        client: clientName,
        totalWeight: this.main.formatDisplayNumber(s?.total_weight || 0),
        totalPrice: this.main.formatDisplayNumber(s?.total_price || 0),
      };
    },
    showSummarySnackbar(type) { this.showSnackbarFromSnapshot(this.buildSnapshot(), type); },
    showSnackbarFromSnapshot(snap, type) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, type, ...snap };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 1.25;
        if (this.snackbarProgress <= 0) { clearInterval(this.snackbarTimer); }
      }, 100);
    },
  },
};
</script>

<style scoped>
.charge-summary-bar { display: flex; flex-direction: column; gap: 12px; padding: 18px 22px; background: linear-gradient(135deg, rgba(33,150,243,.04) 0%, rgba(76,175,80,.04) 100%); border-radius: 16px; border: 1.5px dashed rgba(33,150,243,.2); }
.summary-category { padding: 8px 12px; border-radius: 10px; background: rgba(var(--v-theme-on-surface), .03); }
.summary-global { padding: 10px 12px; border-radius: 12px; background: rgba(33,150,243,.06); }
.summary-item { display: flex; align-items: center; gap: 4px; }
.summary-label { font-size: 13px; font-weight: 600; color: rgba(var(--v-theme-on-surface), .6); }
.summary-value { font-size: 15px; font-weight: 800; }
.add-shipping-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.ash-hero { background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.ash-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; }
.ash-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.ash-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
.text-shipping { color: #4643a0 !important; }
</style>



