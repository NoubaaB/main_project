<template>
  <v-form ref="form" @submit.prevent="submitCharge">
    <v-row>
      <!-- LEFT PANEL: Necessary Information -->
      <v-col cols="12" md="5">
        <v-card class="add-charge-card ma-auto" elevation="0">
          <!-- Hero Header -->
          <div class="ac-hero">
            <div class="ac-hero-inner">
              <div class="d-flex align-center ga-4">
                <div class="ac-hero-icon">
                  <v-icon size="28" color="white">mdi-sticker-plus-outline</v-icon>
                </div>
                <div>
                  <div class="ac-hero-title">{{ $t("charge_create_interface") }}</div>
                  <div class="text-white text-caption" style="opacity:.7">{{ $t('new_charge_desc') }}</div>
                </div>
              </div>
            </div>
          </div>
          <v-card-text v-if="chargeStore.charge">
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

            <!-- Charge Category -->
            <v-row class="text-center">
              <v-col>
                <v-autocomplete
                  v-model="chargeStore.charge.charge_category_id"
                  :items="chargeCategoryOptions"
                  :label="$t('charge_category')"
                  chips density="compact" variant="outlined" rounded color="#42A5F5" class="text-charge"
                  hide-details @update:modelValue="onCategoryChange"
                >
                  <template #chip="{ item }">
                    <v-chip  color="#42A5F5" variant="tonal">{{ item.title }}</v-chip>
                  </template>
                </v-autocomplete>
              </v-col>
              <v-col cols="2" class="py-2 pl-0">
                <v-btn variant="tonal" color="#42A5F5" icon="mdi-notebook-plus-outline" :disabled="!chargeStore.charge?.charge_category_id" @click="descriptionDialog = true"></v-btn>
              </v-col>
            </v-row>

            <!-- Calendar -->
            <v-row class="text-center">
              <v-col>
                <DatePicker
                  :is-dark="main.is_dark"
                  expanded
                  :max-date="new Date()"
                  v-model="dateModel"
                  :locale="main.lang?.short || 'en'"
                  mode="date"
                  color="blue"
                />
              </v-col>
            </v-row>

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

            <!-- EXPAND toggle -->
            <v-row>
              <v-col>
                <v-btn block rounded variant="tonal" color="#42A5F5" :disabled="!chargeStore.charge?.charge_category_id" @click="chargeStore.showAdditionalInfo = !chargeStore.showAdditionalInfo" :prepend-icon="chargeStore.showAdditionalInfo ? 'mdi-chevron-up' : 'mdi-chevron-down'">
                  {{ $t('expand') }}
                </v-btn>
              </v-col>
            </v-row>

            <!-- Expandable Additional Fields -->
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

            <!-- Submit / Cancel -->
            <v-row class="mt-3">
              <v-col cols="6">
                <v-btn block rounded variant="outlined" color="#42A5F5" :disabled="!canSubmit" :loading="submitting" @click="submitCharge">{{ $t('submit') }}</v-btn>
              </v-col>
              <v-col cols="6">
                <v-btn block rounded variant="tonal" color="red-lighten-1" :disabled="!chargeStore.charge" @click="resetCharge">{{ $t('cancel') }}</v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <v-divider vertical></v-divider>

      <!-- RIGHT PANEL: Detail Charges -->
      <v-col cols="12" md="7">
        <DetailCharges v-if="chargeStore.charge && chargeStore.charge.charge_category_id" />
        <div v-else class="d-flex flex-column align-center justify-center" style="min-height: 300px; opacity: .5;">
          <v-icon size="80" color="#42A5F5" class="mb-4">mdi-clipboard-text-outline</v-icon>
          <div class="text-h6 text-info font-weight-medium">{{ $t('select_charge_category_to_start') }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Description Dialog -->
    <v-dialog v-model="descriptionDialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#42A5F5" class="mr-2">mdi-notebook-edit-outline</v-icon>
          <span>{{ $t('description') }}</span>
          <v-spacer></v-spacer>
          <v-chip size="small" :color="descriptionLength > 1000 ? 'error' : 'info'" variant="tonal">
            {{ descriptionLength }} / 1000
          </v-chip>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-4">
          <v-textarea
            v-model="chargeStore.charge.description"
            :label="$t('description')"
            :counter="1000"
            :maxlength="1000"
            rows="6"
            variant="outlined"
            rounded
            color="#42A5F5"
            class="text-charge"
            auto-grow
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="#42A5F5" rounded @click="descriptionDialog = false" prepend-icon="mdi-check">{{ $t('done') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#42A5F5" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('record_added_success') }}</span>
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

import { useChargeCategory } from '@/stores/ChargeCategory';
import { useDetailChargeCategory } from '@/stores/DetailChargeCategory';
import { useAuth } from '@/stores/Auth';
import Charge from './Charge.vue';
import DetailCharges from './DetailCharges.vue';

export default {
  name: 'AddCharge',
  components: { Charge, DetailCharges },
  data() { return { submitting: false, descriptionDialog: false, categoryColors: ['info', 'teal', 'purple', 'orange', 'pink', 'cyan', 'amber', 'indigo'], summarySnackbar: { show: false, type: '', category: '', date: '', trackingNumbers: '', supplier: '', totalWeight: '', totalPrice: '' }, snackbarProgress: 100, snackbarTimer: null }; },
  computed: {
    chargeStore() { return useCharge(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    chargeCategoryOptions() {
      const lang = this.main.lang?.code || 'en';
      return useChargeCategory().list.map(c => ({ title: c[`name_${lang}`] || c.name || `#${c.id}`, value: c.id }));
    },
    selectedCategory() {
      return useChargeCategory().list.find(c => c.id === this.chargeStore.charge?.charge_category_id);
    },
    canSubmit() {
      const c = this.chargeStore.charge;
      return c?.date && c?.charge_category_id && c?.details?.length > 0;
    },
    descriptionLength() {
      return (this.chargeStore.charge?.description || '').length;
    },
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
  mounted() {
    this.chargeStore._restoreDraft();
    if (!this.chargeStore.charge) this.chargeStore.add_charge(null);
  },
  watch: {
    'chargeStore.charge': { handler() { this.chargeStore._persistDraft(); }, deep: true },
    'chargeStore.showAdditionalInfo'() { this.chargeStore._persistDraft(); },
  },
  methods: {
    onCategoryChange() {
      if (!this.chargeStore.charge) return;
      this.chargeStore.charge.details = [];
      this.chargeStore.charge.reception_tracking_number = [];
      this.chargeStore.charge.tracking_onp_numbers = [];
      this.chargeStore.charge.supplier_id = null;
      this.chargeStore.charge.truck_ids = [];
      this.chargeStore.charge.fishery_id = null;
      this.chargeStore.charge.ship_id = null;
      this.chargeStore.charge.origin_id = null;
      this.chargeStore.charge.total_price = 0;
      this.chargeStore.charge.total_weight = 0;
      this.chargeStore.showAdditionalInfo = false;
    },
    resetCharge() { this.chargeStore.add_charge(null); this.chargeStore.clearDraft(); sessionStorage.removeItem('detail_charges_draft'); this.main.add_snackbar({ show: true, message: this.$t('reset_charge'), color: 'success' }); },

    async submitCharge() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const c = JSON.parse(JSON.stringify(this.chargeStore.charge));
        c.details = c.details.map(d => { const { _uid, ...rest } = d; return rest; });
        delete c.id;
        await axios.post('/api/charge', c);
        this.chargeStore.clearDraft();
        sessionStorage.removeItem('detail_charges_draft');
        this.chargeStore.add_charge(null);
        this.showSnackbarFromSnapshot(snapshot, 'submit');
        this.$router.push({ name: 'list_charge' });
      } catch (err) { this.main.add_snackbar({ show: true, message: err.response?.data?.message || this.$t('record_added_failed'), color: 'error' }); }
      this.submitting = false;
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
    showSummarySnackbar(type) {
      const snap = this.buildSnapshot();
      this.showSnackbarFromSnapshot(snap, type);
    },
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
.add-charge-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.ac-hero { background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.ac-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; }
.ac-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.ac-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
.text-charge { color: #42A5F5 !important; }
</style>
