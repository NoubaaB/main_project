<template>
  <v-form ref="form" @submit.prevent="updateCharge">
    <v-row>
      <!-- LEFT PANEL -->
      <v-col cols="12" md="5">
        <v-card class="edit-charge-card ma-auto" elevation="0">
          <!-- Hero Header -->
          <div class="ec-hero">
            <div class="ec-hero-inner">
              <div class="d-flex align-center ga-4">
                <div class="ec-hero-icon">
                  <v-icon size="28" color="white">mdi-pencil-circle-outline</v-icon>
                </div>
                <div>
                  <div class="ec-hero-title">{{ $t("charge_edit_interface") }} #{{ $route.params.id }}</div>
                  <div class="text-white text-caption" style="opacity:.7">{{ $t('edit_charge_desc') }}</div>
                </div>
              </div>
            </div>
          </div>

          <v-card-text v-if="loading" class="pa-10 text-center"><v-progress-circular indeterminate color="#42A5F5"></v-progress-circular></v-card-text>

          <v-card-text v-else-if="chargeStore.charge">
            <!-- (1) Necessary Information -->
            <v-row>
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#42A5F5"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-charge">mdi-numeric-1-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("necessary_information") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#42A5F5"></v-divider></div>
              </v-col>
            </v-row>

            <v-row class="text-center">
              <v-col>
                <v-autocomplete v-model="chargeStore.charge.charge_category_id" :items="chargeCategoryOptions" :label="$t('charge_category')" chips density="compact" variant="outlined" rounded color="#42A5F5" class="text-charge" hide-details>
                  <template #chip="{ item }"><v-chip  color="#42A5F5" variant="tonal">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </v-col>
              <v-col cols="2" class="py-2 pl-0">
                <v-btn variant="tonal" color="#42A5F5" icon="mdi-notebook-plus-outline"></v-btn>
              </v-col>
            </v-row>

            <v-row class="text-center"><v-col>
              <DatePicker :is-dark="main.is_dark" expanded :max-date="new Date()" v-model="dateModel" :locale="main.lang?.short || 'en'" mode="date" color="blue" />
            </v-col></v-row>

            <!-- (2) Additional Information -->
            <v-row>
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#42A5F5"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-charge">mdi-numeric-2-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("additional_information") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#42A5F5"></v-divider></div>
              </v-col>
            </v-row>

            <v-row><v-col>
              <v-btn block rounded variant="tonal" color="#42A5F5" @click="chargeStore.showAdditionalInfo = !chargeStore.showAdditionalInfo" :prepend-icon="chargeStore.showAdditionalInfo ? 'mdi-chevron-up' : 'mdi-chevron-down'">{{ $t('expand') }}</v-btn>
            </v-col></v-row>

            <v-expand-transition>
              <div v-show="chargeStore.showAdditionalInfo && selectedCategory">
                <Charge ref="chargeForm" />
              </div>
            </v-expand-transition>

            <!-- (3) Summary -->
            <v-row v-if="chargeStore.charge && chargeStore.charge.details.length > 0">
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#42A5F5"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-charge">mdi-numeric-3-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("summary") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#42A5F5"></v-divider></div>
              </v-col>
            </v-row>
            <div v-if="chargeStore.charge && chargeStore.charge.details.length > 0" class="charge-summary-bar">
              <div v-for="(cat, idx) in categorySummary" :key="cat.id" class="summary-category">
                <v-chip size="small" :color="categoryColors[idx % categoryColors.length]" variant="tonal" class="mb-1 font-weight-bold">{{ cat.name }}</v-chip>
                <div class="d-flex align-center ga-3">
                  <div class="summary-item">
                    <v-icon size="16" :color="categoryColors[idx % categoryColors.length]" class="mr-1">mdi-weight-kilogram</v-icon>
                    <span class="summary-label">{{ $t('weight') }}:</span>
                    <span class="summary-value" :style="{ color: `rgb(var(--v-theme-${categoryColors[idx % categoryColors.length]}))` }">{{ main.formatDisplayNumber(cat.totalWeight) }} Kg</span>
                  </div>
                  <div class="summary-item">
                    <v-icon size="16" :color="categoryColors[idx % categoryColors.length]" class="mr-1">mdi-cash</v-icon>
                    <span class="summary-label">{{ $t('total_price') }}:</span>
                    <span class="summary-value" :style="{ color: `rgb(var(--v-theme-${categoryColors[idx % categoryColors.length]}))` }">{{ main.formatDisplayNumber(cat.totalPrice) }} {{ main.currency }}</span>
                  </div>
                </div>
              </div>
              <v-divider class="my-2"></v-divider>
              <div class="summary-global">
                <v-chip size="small" color="#42A5F5" variant="elevated" class="mb-1 font-weight-bold">{{ $t('global_total') }}</v-chip>
                <div class="d-flex align-center ga-4">
                  <div class="summary-item">
                    <v-icon size="18" color="#42A5F5" class="mr-1">mdi-weight-kilogram</v-icon>
                    <span class="summary-label">{{ $t('total_weight') }}:</span>
                    <span class="summary-value text-info">{{ main.formatDisplayNumber(chargeStore.charge.total_weight) }} Kg</span>
                  </div>
                  <div class="summary-item">
                    <v-icon size="18" color="success" class="mr-1">mdi-cash</v-icon>
                    <span class="summary-label">{{ $t('total_price') }}:</span>
                    <span class="summary-value text-success">{{ main.formatDisplayNumber(chargeStore.charge.total_price) }} {{ main.currency }}</span>
                  </div>
                </div>
              </div>
            </div>

            <v-row class="mt-3">
              <v-col cols="6"><v-btn block rounded variant="flat" color="#42A5F5" :loading="submitting" @click="updateCharge" prepend-icon="mdi-check-circle">{{ $t('save') }}</v-btn></v-col>
              <v-col cols="6"><v-btn block rounded variant="outlined" color="grey" @click="$router.push({ name: 'list_charge' })" prepend-icon="mdi-arrow-left">{{ $t('back') }}</v-btn></v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <v-divider vertical></v-divider>

      <!-- RIGHT PANEL -->
      <v-col cols="12" md="7">
        <DetailCharges v-if="chargeStore.charge && chargeStore.charge.charge_category_id" />
      </v-col>
    </v-row>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#42A5F5" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('charge_updated_successfully') }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.category"><v-icon size="14" color="#42A5F5" class="mr-1">mdi-tag</v-icon><span class="text-medium-emphasis">{{ $t('charge_category') }}:</span> <strong>{{ summarySnackbar.category }}</strong></div>
        <div v-if="summarySnackbar.date"><v-icon size="14" color="#42A5F5" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('date') }}:</span> <strong>{{ summarySnackbar.date }}</strong></div>
        <div v-if="summarySnackbar.trackingNumbers"><v-icon size="14" color="#42A5F5" class="mr-1">mdi-barcode</v-icon><span class="text-medium-emphasis">{{ $t('reception_tracking_number') }}:</span> <strong>{{ summarySnackbar.trackingNumbers }}</strong></div>
        <div v-if="summarySnackbar.supplier"><v-icon size="14" color="#42A5F5" class="mr-1">mdi-account</v-icon><span class="text-medium-emphasis">{{ $t('supplier') }}:</span> <strong>{{ summarySnackbar.supplier }}</strong></div>
        <v-divider class="my-1"></v-divider>
        <div class="d-flex ga-4">
          <div><v-icon size="14" color="#42A5F5" class="mr-1">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('total_weight') }}:</span> <strong class="text-charge">{{ summarySnackbar.totalWeight }} Kg</strong></div>
          <div><v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span> <strong class="text-success">{{ summarySnackbar.totalPrice }} {{ main.currency }}</strong></div>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-form>
</template>

<script>
import axios from 'axios';
import { useCharge } from '@/stores/Charge';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useChargeCategory } from '@/stores/ChargeCategory';
import { useDetailChargeCategory } from '@/stores/DetailChargeCategory';
import { v4 as uuidv4 } from 'uuid';
import Charge from '../AddCharge/Charge.vue';
import DetailCharges from '../AddCharge/DetailCharges.vue';

export default {
  name: 'EditCharge',
  components: { Charge, DetailCharges },
  data() { return { loading: true, submitting: false, categoryColors: ['info', 'teal', 'purple', 'orange', 'pink', 'cyan', 'amber', 'indigo'], summarySnackbar: { show: false }, snackbarProgress: 100, snackbarTimer: null }; },
  computed: {
    chargeStore() { return useCharge(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    chargeCategoryOptions() {
      const lang = this.main.lang?.code || 'en';
      return useChargeCategory().list.map(c => ({ title: c[`name_${lang}`] || c.name || `#${c.id}`, value: c.id }));
    },
    selectedCategory() { return useChargeCategory().list.find(c => c.id === this.chargeStore.charge?.charge_category_id); },
    dateModel: {
      get() { if (!this.chargeStore.charge?.date) return null; return new Date(this.chargeStore.charge.date + 'T00:00:00'); },
      set(val) {
        if (!this.chargeStore.charge) return;
        if (val instanceof Date) { const y = val.getFullYear(); const m = String(val.getMonth() + 1).padStart(2, '0'); const d = String(val.getDate()).padStart(2, '0'); this.chargeStore.charge.date = `${y}-${m}-${d}`; }
        else { this.chargeStore.charge.date = val; }
      }
    },
    categorySummary() {
      const details = this.chargeStore.charge?.details || [];
      if (!details.length) return [];
      const lang = this.main.lang?.code || 'en';
      const detailCats = useDetailChargeCategory().list;
      const map = {};
      details.forEach(d => {
        const catId = d.detail_charge_category_id;
        if (!map[catId]) {
          const cat = detailCats.find(c => c.id === catId);
          map[catId] = { id: catId, name: cat ? (cat[`name_${lang}`] || cat.name_fr || cat.name_en || `#${catId}`) : `#${catId}`, totalWeight: 0, totalPrice: 0 };
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
        const res = await axios.get(`/api/charge/${this.$route.params.id}`);
        const charge = res.data.charge;
        this.chargeStore.charge = {
          id: charge.id,
          date: charge.date?.substring(0, 10),
          charge_category_id: charge.charge_category_id,
          reception_tracking_number: charge.reception_tracking_number || [],
          tracking_onp_numbers: charge.tracking_onp_numbers || [],
          supplier_id: charge.supplier_id || null,
          truck_ids: charge.trucks?.map(t => t.id) || [],
          fishery_id: charge.fishery_id,
          ship_id: charge.ship_id,
          origin_id: charge.origin_id,
          description: charge.description || '',
          details: (charge.detail_charges || []).map(d => ({
            _uid: uuidv4(),
            detail_charge_category_id: d.detail_charge_category_id,
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
          total_price: charge.total_price,
          total_weight: charge.total_weight,
        };
        this.chargeStore.showAdditionalInfo = true;
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    buildSnapshot() {
      const c = this.chargeStore.charge;
      const lang = this.main.lang?.code || 'en';
      const cat = useChargeCategory().list.find(cc => cc.id === c?.charge_category_id);
      const supplierName = c?.supplier_id ? (this.auth?.supplier?.list?.find(s => s.id === c.supplier_id)?.name || '') : '';
      return {
        category: cat ? (cat[`name_${lang}`] || cat.name || `#${cat.id}`) : '',
        date: c?.date || '',
        trackingNumbers: (c?.reception_tracking_number || []).join(', '),
        supplier: supplierName,
        totalWeight: this.main.formatDisplayNumber(c?.total_weight || 0),
        totalPrice: this.main.formatDisplayNumber(c?.total_price || 0),
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
    async updateCharge() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const c = JSON.parse(JSON.stringify(this.chargeStore.charge));
        c.details = c.details.map(d => { const { _uid, ...rest } = d; return rest; });
        const id = c.id; delete c.id;
        await axios.put(`/api/charge/${id}`, c);
        this.chargeStore.add_charge(null);
        this.chargeStore.clearDraft();
        sessionStorage.removeItem('detail_charges_draft');
        this.showSnackbarFromSnapshot(snapshot);
        this.$router.push({ name: 'list_charge' });
      } catch (err) { this.main.add_snackbar({ show: true, message: this.$t('charge_update_failed'), color: 'error' }); }
      this.submitting = false;
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
.edit-charge-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.ec-hero { background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.ec-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; }
.ec-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.ec-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
.text-charge { color: #42A5F5 !important; }
</style>
