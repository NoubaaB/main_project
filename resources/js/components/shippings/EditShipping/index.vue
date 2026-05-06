<template>
  <v-form ref="form" @submit.prevent="updateShipping">
    <v-row>
      <!-- LEFT PANEL -->
      <v-col cols="12" md="5">
        <v-card class="edit-shipping-card ma-auto" elevation="0">
          <!-- Hero Header -->
          <div class="es-hero">
            <div class="es-hero-inner">
              <div class="d-flex align-center ga-4">
                <div class="es-hero-icon">
                  <v-icon size="28" color="white">mdi-pencil-circle-outline</v-icon>
                </div>
                <div>
                  <div class="es-hero-title">{{ $t("shipping_edit_interface") }} #{{ $route.params.id }}</div>
                  <div class="text-white text-caption" style="opacity:.7">{{ $t('edit_shipping_desc') }}</div>
                </div>
              </div>
            </div>
          </div>

          <v-card-text v-if="loading" class="pa-10 text-center"><v-progress-circular indeterminate color="#4643a0"></v-progress-circular></v-card-text>

          <v-card-text v-else-if="shippingStore.shipping">
            <!-- - Necessary Information -->
            <v-row>
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-shipping">mdi-numeric-1-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("necessary_information") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
              </v-col>
            </v-row>

            <v-row class="text-center">
              <v-col>
                <v-autocomplete v-model="shippingStore.shipping.shipping_category_id" :items="shippingCategoryOptions" :label="$t('shipping_category')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details>
                  <template #chip="{ item }"><v-chip color="#4643a0" variant="tonal">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </v-col>
              <v-col cols="2" class="py-2 pl-0">
                <v-btn variant="tonal" color="#4643a0" icon="mdi-notebook-plus-outline" @click="descriptionDialog = true"></v-btn>
              </v-col>
            </v-row>

            <v-row class="text-center"><v-col>
              <DatePicker :is-dark="main.is_dark" expanded :max-date="new Date()" v-model="dateModel" :locale="main.lang?.short || 'en'" mode="date" color="purple" />
            </v-col></v-row>

            <!-- - Additional Information -->
            <v-row>
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-shipping">mdi-numeric-2-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("additional_information") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
              </v-col>
            </v-row>

            <v-row><v-col>
              <v-btn block rounded variant="tonal" color="#4643a0" @click="shippingStore.showAdditionalInfo = !shippingStore.showAdditionalInfo" :prepend-icon="shippingStore.showAdditionalInfo ? 'mdi-chevron-up' : 'mdi-chevron-down'">{{ $t('expand') }}</v-btn>
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
            <div v-if="shippingStore.shipping && shippingStore.shipping.details.length > 0" class="sell-summary-bar">
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
              <v-col cols="6"><v-btn block rounded variant="flat" color="#4643a0" :loading="submitting" @click="updateShipping" prepend-icon="mdi-check-circle">{{ $t('save') }}</v-btn></v-col>
              <v-col cols="6"><v-btn block rounded variant="outlined" color="grey" @click="$router.push({ name: 'list_shipping' })" prepend-icon="mdi-arrow-left">{{ $t('back') }}</v-btn></v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <v-divider vertical></v-divider>

      <!-- RIGHT PANEL -->
      <v-col cols="12" md="7">
        <detailShippings v-if="shippingStore.shipping && shippingStore.shipping.shipping_category_id" />
      </v-col>
    </v-row>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#4643a0" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('shipping_updated_successfully') }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.category"><v-icon size="14" color="#4643a0" class="mr-1">mdi-tag</v-icon><span class="text-medium-emphasis">{{ $t('shipping_category') }}:</span> <strong>{{ summarySnackbar.category }}</strong></div>
        <div v-if="summarySnackbar.date"><v-icon size="14" color="#4643a0" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('date') }}:</span> <strong>{{ summarySnackbar.date }}</strong></div>
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
          <v-chip size="small" :color="descriptionLength > 1000 ? 'error' : '#4643a0'" variant="tonal">{{ descriptionLength }} / 1000</v-chip>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-4">
          <v-textarea v-model="shippingStore.shipping.description" :label="$t('description')" :counter="1000" :maxlength="1000" rows="6" variant="outlined" rounded color="#4643a0" class="text-shipping" auto-grow></v-textarea>
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
import { useAuth } from '@/stores/Auth';
import { useShippingCategory } from '@/stores/ShippingCategory';
import { useDetailShippingCategory } from '@/stores/DetailShippingCategory';
import { v4 as uuidv4 } from 'uuid';
import Shipping from '../AddShipping/Shipping.vue';
import detailShippings from '../AddShipping/DetailShippings.vue';

export default {
  name: 'EditShipping',
  components: { Shipping, detailShippings },
  data() { return { loading: true, submitting: false, descriptionDialog: false, categoryColors: ['info', 'teal', 'purple', 'orange', 'pink', 'cyan', 'amber', 'indigo'], summarySnackbar: { show: false }, snackbarProgress: 100, snackbarTimer: null }; },
  computed: {
    shippingStore() { return useShipping(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    shippingCategoryOptions() { return useShippingCategory().list.map(c => ({ title: c[`name_${this.lang}`] || c.name || `#${c.id}`, value: c.id })); },
    selectedCategory() { return useShippingCategory().list.find(c => c.id === this.shippingStore.shipping?.shipping_category_id); },
    descriptionLength() { return (this.shippingStore.shipping?.description || '').length; },
    dateModel: {
      get() { if (!this.shippingStore.shipping?.date) return null; return new Date(this.shippingStore.shipping.date + 'T00:00:00'); },
      set(val) {
        if (!this.shippingStore.shipping) return;
        if (val instanceof Date) { const y = val.getFullYear(); const m = String(val.getMonth() + 1).padStart(2, '0'); const d = String(val.getDate()).padStart(2, '0'); this.shippingStore.shipping.date = `${y}-${m}-${d}`; }
        else { this.shippingStore.shipping.date = val; }
      }
    },
    categorySummary() {
      const details = this.shippingStore.shipping?.details || [];
      if (!details.length) return [];
      const detailCats = useDetailShippingCategory().list;
      const map = {};
      details.forEach(d => {
        const catId = d.detail_shipping_category_id;
        if (!map[catId]) {
          const cat = detailCats.find(c => c.id === catId);
          map[catId] = { id: catId, name: cat ? (cat[`name_${this.lang}`] || cat.name_fr || cat.name_en || `#${catId}`) : `#${catId}`, totalWeight: 0, totalPrice: 0 };
        }
        map[catId].totalWeight += parseFloat(d.weight) || 0;
        map[catId].totalPrice += parseFloat(d.total_price) || 0;
      });
      return Object.values(map);
    },
  },
  mounted() { this.fetchAndLoad(); },
  methods: {
    async fetchAndLoad() {
      this.loading = true;
      try {
        const res = await axios.get(`/api/shipping/${this.$route.params.id}`);
        const shipping = res.data.shipping;
        this.shippingStore.shipping = {
          id: shipping.id,
          date: shipping.date?.substring(0, 10),
          shipping_category_id: shipping.shipping_category_id,
          shipping_tracking_number: shipping.shipping_tracking_number || [],
          tracking_onp_numbers: shipping.tracking_onp_numbers || [],
          client_id: shipping.client_id || null,
          truck_ids: shipping.trucks?.map(t => t.id) || [],
          origin_id: shipping.origin_id,
          direction: shipping.direction,
          fishery_id: shipping.fishery_id || null,
          ship_id: shipping.ship_id || null,
          description: shipping.description || '',
          details: (shipping.detail_shippings || []).map(d => ({
            _uid: uuidv4(),
            detail_shipping_category_id: d.detail_shipping_category_id,
            article_description: d.article_description,
            stockable_type: d.stockable_type,
            stockable_id: d.stockable_id,
            weight: d.weight,
            unit_price: d.unit_price,
            total_price: d.total_price,
            weight_invoice: d.weight_invoice,
            package_type_id: d.package_type_id,
            nbr_box: d.nbr_box,
            warehouse_id: d.warehouse_id,
            warehouse_operation: d.warehouse_operation,
            truck_id: d.truck_id,
          })),
          total_price: shipping.total_price,
          total_weight: shipping.total_weight,
        };
        this.shippingStore.showAdditionalInfo = true;
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    buildSnapshot() {
      const s = this.shippingStore.shipping;
      const cat = useShippingCategory().list.find(c => c.id === s?.shipping_category_id);
      const clientName = s?.client_id ? (this.auth.client.list.find(c => c.id === s.client_id)?.name || '') : '';
      return {
        category: cat ? (cat[`name_${this.lang}`] || cat.name || `#${cat.id}`) : '',
        date: s?.date || '',
        client: clientName,
        totalWeight: this.main.formatDisplayNumber(s?.total_weight || 0),
        totalPrice: this.main.formatDisplayNumber(s?.total_price || 0),
      };
    },
    showSnackbarFromSnapshot(snap) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, ...snap };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 1.25;
        if (this.snackbarProgress <= 0) { clearInterval(this.snackbarTimer); }
      }, 100);
    },
    async updateShipping() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const s = JSON.parse(JSON.stringify(this.shippingStore.shipping));
        s.details = s.details.map(d => { const { _uid, ...rest } = d; return rest; });
        const id = s.id; delete s.id;
        await axios.put(`/api/shipping/${id}`, s);
        this.shippingStore.add_shipping(null);
        this.shippingStore.clearDraft();
        sessionStorage.removeItem('detail_shippings_draft');
        this.showSnackbarFromSnapshot(snapshot);
        this.$router.push({ name: 'list_shipping' });
      } catch (err) { this.main.add_snackbar({ show: true, message: this.$t('shipping_update_failed'), color: 'error' }); }
      this.submitting = false;
    },
  },
};
</script>

<style scoped>
.shipping-summary-bar { display: flex; flex-direction: column; gap: 12px; padding: 18px 22px; background: linear-gradient(135deg, rgba(70,67,160,.04) 0%, rgba(76,175,80,.04) 100%); border-radius: 16px; border: 1.5px dashed rgba(70,67,160,.2); }
.summary-category { padding: 8px 12px; border-radius: 10px; background: rgba(var(--v-theme-on-surface), .03); }
.summary-global { padding: 10px 12px; border-radius: 12px; background: rgba(70,67,160,.06); }
.summary-item { display: flex; align-items: center; gap: 4px; }
.summary-label { font-size: 13px; font-weight: 600; color: rgba(var(--v-theme-on-surface), .6); }
.summary-value { font-size: 15px; font-weight: 800; }
.edit-shipping-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.es-hero { background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.es-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; }
.es-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.es-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
.text-shipping { color: #4643a0 !important; }
</style>



