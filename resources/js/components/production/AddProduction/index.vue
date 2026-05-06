<template>
  <v-form ref="form" @submit.prevent="submitProd">
    <v-row>
      <!-- LEFT PANEL -->
      <v-col cols="12" md="5">
        <v-card class="add-prod-card ma-auto" elevation="0">
          <!-- Hero Header -->
          <div class="ap-hero">
            <div class="ap-hero-inner">
              <div class="d-flex align-center ga-4">
                <div class="ap-hero-icon">
                  <v-icon size="28" color="white">mdi-factory</v-icon>
                </div>
                <div>
                  <div class="ap-hero-title">{{ $t("production_create_interface") }}</div>
                  <div class="text-white text-caption" style="opacity:.7">{{ $t('new_production_desc') }}</div>
                </div>
              </div>
            </div>
          </div>
          <v-card-text v-if="prodStore.production">
            <!-- (1) Necessary Information -->
            <v-row><v-col class="d-flex align-center">
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#00897B"></v-divider></div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-prod">mdi-numeric-1-circle-outline</v-icon></v-col></v-row>
                <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("necessary_information") }}</span></v-col></v-row>
              </div>
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#00897B"></v-divider></div>
            </v-col></v-row>

            <v-row class="text-center">
              <v-col>
                <v-autocomplete v-model="prodStore.production.production_category_id" :items="catOptions" :label="$t('production_category')" chips density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details @update:modelValue="onCategoryChange">
                  <template #chip="{ item }"><v-chip  color="#00897B" variant="tonal">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </v-col>
              <v-col cols="2" class="py-2 pl-0"><v-btn variant="tonal" color="#00897B" icon="mdi-notebook-plus-outline" :disabled="!prodStore.production?.production_category_id" @click="descriptionDialog = true"></v-btn></v-col>
            </v-row>

            <v-row v-if="selectedCategory?.enable_production_type" class="text-center">
              <v-col>
                <v-autocomplete v-model="prodStore.production.production_type" :items="[{ title: $t('intern'), value: 'intern' }, { title: $t('extern'), value: 'extern' }]" :label="$t('production_type')" chips density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details clearable></v-autocomplete>
              </v-col>
            </v-row>

            <v-row class="text-center"><v-col>
              <DatePicker :is-dark="main.is_dark" expanded :max-date="new Date()" v-model="dateModel" :locale="main.lang?.short || 'en'" mode="date" color="teal" />
            </v-col></v-row>

            <!-- (2) Additional Information -->
            <v-row><v-col class="d-flex align-center">
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#00897B"></v-divider></div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-prod">mdi-numeric-2-circle-outline</v-icon></v-col></v-row>
                <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("additional_information") }}</span></v-col></v-row>
              </div>
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#00897B"></v-divider></div>
            </v-col></v-row>

            <v-row><v-col>
              <v-btn block rounded variant="tonal" color="#00897B" :disabled="!prodStore.production?.production_category_id" @click="showAdditional = !showAdditional" :prepend-icon="showAdditional ? 'mdi-chevron-up' : 'mdi-chevron-down'">{{ $t('expand') }}</v-btn>
            </v-col></v-row>

            <v-expand-transition>
              <div v-show="showAdditional && selectedCategory">
                <Production ref="productionForm" />
              </div>
            </v-expand-transition>

            <!-- (3) Summary -->
            <v-row v-if="prodStore.production && prodStore.production.details.length > 0">
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#00897B"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-prod">mdi-numeric-3-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("summary") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#00897B"></v-divider></div>
              </v-col>
            </v-row>
            <div v-if="prodStore.production && prodStore.production.details.length > 0" class="charge-summary-bar">
              <div v-for="(cat, idx) in categorySummary" :key="cat.id" class="summary-category">
                <v-chip size="small" :color="categoryColors[idx % categoryColors.length]" variant="tonal" class="mb-1 font-weight-bold">{{ cat.name }}</v-chip>
                <div class="d-flex align-center ga-3">
                  <div class="summary-item"><v-icon size="16" :color="categoryColors[idx % categoryColors.length]" class="mr-1">mdi-weight-kilogram</v-icon><span class="summary-label">{{ $t('weight') }}:</span><span class="summary-value" :style="{ color: `rgb(var(--v-theme-${categoryColors[idx % categoryColors.length]}))` }">{{ main.formatDisplayNumber(cat.totalWeight) }} Kg</span></div>
                  <div class="summary-item"><v-icon size="16" :color="categoryColors[idx % categoryColors.length]" class="mr-1">mdi-cash</v-icon><span class="summary-label">{{ $t('total_price') }}:</span><span class="summary-value" :style="{ color: `rgb(var(--v-theme-${categoryColors[idx % categoryColors.length]}))` }">{{ main.formatDisplayNumber(cat.totalPrice) }} {{ main.currency }}</span></div>
                </div>
              </div>
              <v-divider class="my-2"></v-divider>
              <div class="summary-global">
                <v-chip size="small" color="#00897B" variant="elevated" class="mb-1 font-weight-bold">{{ $t('global_total') }}</v-chip>
                <div class="d-flex align-center ga-4">
                  <div class="summary-item"><v-icon size="18" color="#00897B" class="mr-1">mdi-weight-kilogram</v-icon><span class="summary-label">{{ $t('total_weight') }}:</span><span class="summary-value text-info">{{ main.formatDisplayNumber(prodStore.production.total_weight) }} Kg</span></div>
                  <div class="summary-item"><v-icon size="18" color="success" class="mr-1">mdi-cash</v-icon><span class="summary-label">{{ $t('total_price') }}:</span><span class="summary-value text-success">{{ main.formatDisplayNumber(prodStore.production.total_price) }} {{ main.currency }}</span></div>
                </div>
              </div>
            </div>

            <v-row class="mt-3">
              <v-col cols="6"><v-btn block rounded variant="outlined" color="#00897B" :disabled="!canSubmit" :loading="submitting" @click="submitProd">{{ $t('submit') }}</v-btn></v-col>
              <v-col cols="6"><v-btn block rounded variant="tonal" color="red-lighten-1" :disabled="!prodStore.production" @click="resetProd">{{ $t('cancel') }}</v-btn></v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <v-divider vertical></v-divider>

      <!-- RIGHT PANEL -->
      <v-col cols="12" md="7">
        <DetailProductions v-if="prodStore.production?.production_category_id" />
        <div v-else class="d-flex flex-column align-center justify-center" style="min-height: 300px; opacity: .5;">
          <v-icon size="80" color="#00897B" class="mb-4">mdi-clipboard-text-outline</v-icon>
          <div class="text-h6 font-weight-medium" style="color: #00897B">{{ $t('select_production_category_to_start') }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#00897B" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('record_added_success') }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.category"><v-icon size="14" color="#00897B" class="mr-1">mdi-tag</v-icon><span class="text-medium-emphasis">{{ $t('production_category') }}:</span> <strong>{{ summarySnackbar.category }}</strong></div>
        <div v-if="summarySnackbar.date"><v-icon size="14" color="#00897B" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('date') }}:</span> <strong>{{ summarySnackbar.date }}</strong></div>
        <div v-if="summarySnackbar.trackingNumbers"><v-icon size="14" color="#00897B" class="mr-1">mdi-barcode</v-icon><span class="text-medium-emphasis">{{ $t('production_tracking_number') }}:</span> <strong>{{ summarySnackbar.trackingNumbers }}</strong></div>
        <div v-if="summarySnackbar.supplier"><v-icon size="14" color="#00897B" class="mr-1">mdi-account</v-icon><span class="text-medium-emphasis">{{ $t('supplier') }}:</span> <strong>{{ summarySnackbar.supplier }}</strong></div>
        <v-divider class="my-1"></v-divider>
        <div class="d-flex ga-4">
          <div><v-icon size="14" color="#00897B" class="mr-1">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('total_weight') }}:</span> <strong class="text-prod">{{ summarySnackbar.totalWeight }} Kg</strong></div>
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
          <v-icon color="#00897B" class="mr-2">mdi-notebook-edit-outline</v-icon>
          <span>{{ $t('description') }}</span>
          <v-spacer></v-spacer>
          <v-chip size="small" :color="descriptionLength > 1000 ? 'error' : '#00897B'" variant="tonal">
            {{ descriptionLength }} / 1000
          </v-chip>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-4">
          <v-textarea
            v-model="prodStore.production.description"
            :label="$t('description')"
            :counter="1000"
            :maxlength="1000"
            rows="6"
            variant="outlined"
            rounded
            color="#00897B"
            class="text-prod"
            auto-grow
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="#00897B" rounded @click="descriptionDialog = false" prepend-icon="mdi-check">{{ $t('done') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-form>
</template>

<script>
import axios from 'axios';
import { useProduction } from '@/stores/Production';
import { useMain } from '@/stores/Main';

import { useProductionCategory } from '@/stores/ProductionCategory';
import { useDetailProductionCategory } from '@/stores/DetailProductionCategory';
import { useAuth } from '@/stores/Auth';
import Production from './Production.vue';
import DetailProductions from './DetailProductions.vue';

export default {
  name: 'AddProduction',
  components: { Production, DetailProductions },
  data() { return { submitting: false, showAdditional: false, descriptionDialog: false, categoryColors: ['info', 'teal', 'purple', 'orange', 'pink', 'cyan', 'amber', 'indigo'], summarySnackbar: { show: false, type: '', category: '', date: '', trackingNumbers: '', supplier: '', totalWeight: '', totalPrice: '' }, snackbarProgress: 100, snackbarTimer: null }; },
  computed: {
    prodStore() { return useProduction(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    selectedCategory() { return useProductionCategory().list.find(c => c.id === this.prodStore.production?.production_category_id); },
    catOptions() { return useProductionCategory().list.map(c => ({ title: c[`name_${this.lang}`] || c.name || `#${c.id}`, value: c.id })); },
    canSubmit() { const p = this.prodStore.production; return p?.date && p?.production_category_id && p?.details?.length > 0; },
    descriptionLength() { return (this.prodStore.production?.description || '').length; },
    dateModel: {
      get() { if (!this.prodStore.production?.date) return null; return new Date(this.prodStore.production.date + 'T00:00:00'); },
      set(val) { if (!this.prodStore.production) return; if (val instanceof Date) { const y = val.getFullYear(); const m = String(val.getMonth() + 1).padStart(2, '0'); const d = String(val.getDate()).padStart(2, '0'); this.prodStore.production.date = `${y}-${m}-${d}`; } else { this.prodStore.production.date = val; } }
    },
    categorySummary() {
      const details = this.prodStore.production?.details || []; if (!details.length) return [];
      const detailCats = useDetailProductionCategory().list; const map = {};
      details.forEach(d => {
        const catId = d.detail_production_category_id; if (!map[catId]) { const cat = detailCats.find(c => c.id === catId); map[catId] = { id: catId, name: cat ? (cat[`name_${this.lang}`] || cat.name_fr || cat.name_en || `#${catId}`) : `#${catId}`, totalWeight: 0, totalPrice: 0 }; }
        map[catId].totalWeight += parseFloat(d.weight) || 0; map[catId].totalPrice += parseFloat(d.total_price) || 0;
      });
      return Object.values(map);
    },
  },
  mounted() {
    const qChargeId = parseInt(this.$route.query.charge_id) || null;
    this.prodStore._restoreDraft();
    if (!this.prodStore.production) this.prodStore.add_production(null);
    if (qChargeId) {
      this.prodStore.production.charge_id = qChargeId;
      this.showAdditional = true;
    }
  },
  watch: {
    'prodStore.production': { handler() { this.prodStore._persistDraft(); }, deep: true },
  },
  methods: {
    onCategoryChange() { if (this.prodStore.production) { this.prodStore.production.details = []; this.prodStore.production.total_price = 0; this.prodStore.production.total_weight = 0; } this.showAdditional = false; },
    resetProd() { this.prodStore.add_production(null); this.prodStore.clearDraft(); sessionStorage.removeItem('detail_productions_draft'); this.main.add_snackbar({ show: true, message: this.$t('reset'), color: 'success' }); },

    async submitProd() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const p = JSON.parse(JSON.stringify(this.prodStore.production));
        p.details = p.details.map(d => { const { _uid, ...rest } = d; return rest; }); delete p.id;
        await axios.post('/api/productions', p);
        this.prodStore.clearDraft();
        sessionStorage.removeItem('detail_productions_draft');
        this.prodStore.add_production(null);
        this.showSnackbarFromSnapshot(snapshot, 'submit');
        this.$router.push({ name: 'production_list' });
      } catch (err) { this.main.add_snackbar({ show: true, message: err.response?.data?.message || this.$t('error_creating_production'), color: 'error' }); }
      this.submitting = false;
    },
    buildSnapshot() {
      const p = this.prodStore.production;
      const cat = useProductionCategory().list.find(c => c.id === p?.production_category_id);
      const supplierName = p?.supplier_id ? (this.auth.supplier.list.find(s => s.id === p.supplier_id)?.name || '') : '';
      return {
        category: cat ? (cat[`name_${this.lang}`] || cat.name || `#${cat.id}`) : '',
        date: p?.date || '',
        trackingNumbers: (p?.production_tracking_number || []).join(', '),
        supplier: supplierName,
        totalWeight: this.main.formatDisplayNumber(p?.total_weight || 0),
        totalPrice: this.main.formatDisplayNumber(p?.total_price || 0),
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
.add-prod-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.ap-hero { background: linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.ap-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; }
.ap-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.ap-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
.text-prod { color: #00897B !important; }
.linked-charge-card { border-color: rgba(66,165,245,.25) !important; background: rgba(66,165,245,.02); }
.cursor-pointer { cursor: pointer; }
.lc-detail-table { width: 100%; border-collapse: collapse; font-size: 11px; }
.lc-detail-table th { padding: 5px 8px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; opacity: .6; text-align: center; border-bottom: 1px solid rgba(66,165,245,.15); }
.lc-detail-table td { padding: 5px 8px; text-align: center; border-bottom: 1px solid rgba(var(--v-border-color),.06); }
</style>
