<template>
  <v-form ref="form" @submit.prevent="submitCharge">
    <v-row>
      <!-- LEFT PANEL: Necessary & Additional Info + Live Summary -->
      <v-col cols="12" md="5">
        <v-card class="add-charge-card elevation-0" rounded="xl">
          <!-- Hero Header -->
          <div class="ac-hero">
            <div class="ac-hero-inner">
              <div class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center ga-3">
                  <div class="ac-hero-icon">
                    <v-icon size="26" color="white">mdi-sticker-plus-outline</v-icon>
                  </div>
                  <div>
                    <div class="ac-hero-title">{{ $t("charge_create_interface") }}</div>
                    <div class="text-white text-caption" style="opacity:.8">{{ $t('new_charge_desc') }}</div>
                  </div>
                </div>

                <v-chip size="small" color="white" variant="outlined" class="font-weight-bold" style="border-color: rgba(255,255,255,.35)">
                  <v-icon size="14" class="mr-1">mdi-pound</v-icon>
                  {{ chargeStore.charge?.details?.length || 0 }} {{ $t('articles_count') || 'Articles' }}
                </v-chip>
              </div>
            </div>
          </div>

          <v-card-text v-if="chargeStore.charge" class="pa-4">
            <!-- STEP 1: Necessary Information -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(66, 165, 245, 0.12); color: #1565C0;">1</div>
                  <span class="text-subtitle-2 font-weight-bold text-charge">{{ $t("necessary_information") }}</span>
                </div>
                <span class="text-caption text-medium-emphasis">* {{ $t('required') || 'Required' }}</span>
              </div>

              <!-- Category & Description -->
              <div class="d-flex align-center ga-2 mb-3">
                <v-autocomplete
                  v-model="chargeStore.charge.charge_category_id"
                  :items="chargeCategoryOptions"
                  :label="$t('charge_category')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#42A5F5"
                  class="text-charge flex-grow-1"
                  hide-details
                  prepend-inner-icon="mdi-shape-outline"
                  @update:modelValue="onCategoryChange"
                >
                  <template #chip="{ item }">
                    <v-chip color="#42A5F5" variant="tonal" class="font-weight-medium">{{ item.title }}</v-chip>
                  </template>
                </v-autocomplete>

                <v-btn
                  variant="tonal"
                  color="#42A5F5"
                  rounded="lg"
                  icon="mdi-notebook-edit-outline"
                  :disabled="!chargeStore.charge?.charge_category_id"
                  :title="$t('description')"
                  @click="descriptionDialog = true"
                >
                  <v-badge v-if="descriptionLength > 0" dot color="primary">
                    <v-icon>mdi-notebook-edit-outline</v-icon>
                  </v-badge>
                  <v-icon v-else>mdi-notebook-edit-outline</v-icon>
                </v-btn>
              </div>

              <!-- Full-width Inline VCalendar DatePicker (matching design specification) -->
              <div class="date-picker-card rounded-xl overflow-hidden border pa-2 mb-1 bg-surface">
                <div class="d-flex align-center justify-space-between px-2 pt-1 pb-2">
                  <div class="d-flex align-center ga-1">
                    <v-icon size="16" color="#42A5F5">mdi-calendar-month</v-icon>
                    <span class="text-caption font-weight-bold text-charge">{{ $t('date') }}</span>
                    <v-chip v-if="displayFormattedDate" size="x-small" color="#42A5F5" variant="flat" class="ml-1 font-weight-bold text-white">
                      {{ displayFormattedDate }}
                    </v-chip>
                  </div>
                  <div class="d-flex align-center ga-1">
                    <v-btn
                      size="x-small"
                      variant="tonal"
                      color="#42A5F5"
                      class="font-weight-medium"
                      rounded
                      @click="setToday"
                    >
                      {{ $t('today') || 'Today' }}
                    </v-btn>
                    <v-btn
                      size="x-small"
                      variant="text"
                      color="grey"
                      class="font-weight-medium"
                      rounded
                      @click="setYesterday"
                    >
                      {{ $t('yesterday') || 'Yest.' }}
                    </v-btn>
                  </div>
                </div>

                <DatePicker
                  :is-dark="main.is_dark"
                  expanded
                  :max-date="new Date()"
                  v-model="dateModel"
                  :locale="main.lang?.short || 'en'"
                  mode="date"
                  color="blue"
                  borderless
                  class="w-100"
                />
              </div>
            </div>

            <!-- STEP 2: Additional Information (Accordion) -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div
                class="d-flex align-center justify-space-between cursor-pointer"
                @click="chargeStore.charge?.charge_category_id ? (chargeStore.showAdditionalInfo = !chargeStore.showAdditionalInfo) : null"
              >
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(66, 165, 245, 0.12); color: #1565C0;">2</div>
                  <span class="text-subtitle-2 font-weight-bold text-charge">{{ $t("additional_information") }}</span>
                </div>

                <div class="d-flex align-center ga-2">
                  <v-chip
                    v-if="additionalFieldsCount > 0"
                    size="x-small"
                    color="#42A5F5"
                    variant="flat"
                    class="font-weight-bold text-white"
                  >
                    {{ additionalFieldsCount }} {{ $t('configured') || 'set' }}
                  </v-chip>
                  <v-btn
                    icon
                    size="small"
                    variant="text"
                    color="#42A5F5"
                    :disabled="!chargeStore.charge?.charge_category_id"
                  >
                    <v-icon>{{ chargeStore.showAdditionalInfo ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                  </v-btn>
                </div>
              </div>

              <!-- Expandable Additional Fields -->
              <v-expand-transition>
                <div v-show="chargeStore.showAdditionalInfo && selectedCategory" class="pt-3 border-t mt-3">
                  <Charge ref="chargeForm" />
                </div>
              </v-expand-transition>
            </div>

            <!-- STEP 3: Live Summary Cards -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(66, 165, 245, 0.12); color: #1565C0;">3</div>
                  <span class="text-subtitle-2 font-weight-bold text-charge">{{ $t("summary") }}</span>
                </div>
                <v-chip size="x-small" :color="canSubmit ? 'success' : 'warning'" variant="tonal" class="font-weight-bold">
                  {{ canSubmit ? ($t('ready') || 'Ready') : ($t('incomplete') || 'Incomplete') }}
                </v-chip>
              </div>

              <!-- Top KPI metric tiles -->
              <div class="summary-kpi-row d-flex ga-2 mb-3 w-100">
                <!-- Total Articles -->
                <div class="summary-kpi-tile pa-2 rounded-lg text-center" style="flex: 1 1 0; min-width: 0; background: rgba(var(--v-theme-on-surface), .03); border: 1px solid rgba(var(--v-theme-on-surface), .06);">
                  <div class="text-caption text-medium-emphasis mb-1 text-truncate">{{ $t('articles') || 'Articles' }}</div>
                  <div class="text-h6 font-weight-bold" style="line-height: 1.2;">
                    {{ chargeStore.charge?.details?.length || 0 }}
                  </div>
                </div>

                <!-- Total Weight -->
                <div class="summary-kpi-tile pa-2 rounded-lg text-center" style="flex: 1 1 0; min-width: 0; background: rgba(66, 165, 245, .08); border: 1px solid rgba(66, 165, 245, .2);">
                  <div class="text-caption text-medium-emphasis mb-1 text-truncate">{{ $t('total_weight') }}</div>
                  <div class="text-h6 font-weight-bold text-charge" style="line-height: 1.2;">
                    {{ main.formatDisplayNumber(chargeStore.charge?.total_weight || 0) }}
                    <span class="text-caption font-weight-normal">Kg</span>
                  </div>
                </div>

                <!-- Total Price -->
                <div class="summary-kpi-tile pa-2 rounded-lg text-center" style="flex: 1 1 0; min-width: 0; background: rgba(76, 175, 80, .08); border: 1px solid rgba(76, 175, 80, .2);">
                  <div class="text-caption text-medium-emphasis mb-1 text-truncate">{{ $t('total_price') }}</div>
                  <div class="text-h6 font-weight-bold text-success" style="line-height: 1.2;">
                    {{ main.formatDisplayNumber(chargeStore.charge?.total_price || 0) }}
                    <span class="text-caption font-weight-normal">{{ main.currency }}</span>
                  </div>
                </div>
              </div>

              <!-- Category Breakdown (if multiple) -->
              <div v-if="categorySummary.length > 0" class="category-summary-list pt-2 border-t">
                <div
                  v-for="(cat, idx) in categorySummary"
                  :key="cat.id"
                  class="d-flex align-center justify-space-between py-1 text-caption"
                >
                  <div class="d-flex align-center ga-1">
                    <span class="cat-dot" :style="{ background: getCategoryColor(idx) }"></span>
                    <span class="font-weight-medium">{{ cat.name }}</span>
                  </div>
                  <div class="d-flex align-center ga-2">
                    <span class="text-medium-emphasis">{{ main.formatDisplayNumber(cat.totalWeight) }} Kg</span>
                    <span class="font-weight-bold text-success">{{ main.formatDisplayNumber(cat.totalPrice) }} {{ main.currency }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Validation helper message -->
            <div class="validation-helper mb-3 text-caption px-1">
              <div v-if="!chargeStore.charge?.charge_category_id" class="text-warning d-flex align-center ga-1">
                <v-icon size="14">mdi-alert-circle-outline</v-icon>
                <span>{{ $t('select_charge_category_to_start') }}</span>
              </div>
              <div v-else-if="!chargeStore.charge?.date" class="text-warning d-flex align-center ga-1">
                <v-icon size="14">mdi-alert-circle-outline</v-icon>
                <span>{{ $t('date_required') || 'Date is required' }}</span>
              </div>
              <div v-else-if="!chargeStore.charge?.details?.length" class="text-info d-flex align-center ga-1">
                <v-icon size="14">mdi-information-outline</v-icon>
                <span>{{ $t('add_at_least_one_article') || 'Add at least one article on the right panel' }}</span>
              </div>
              <div v-else class="text-success d-flex align-center ga-1">
                <v-icon size="14">mdi-check-circle-outline</v-icon>
                <span>{{ $t('submit_ready_hint') || 'Ready to submit record (Ctrl+Enter)' }}</span>
              </div>
            </div>

            <!-- Submit / Cancel Action Buttons -->
            <v-row dense>
              <v-col cols="8">
                <v-btn
                  block
                  rounded
                  color="#42A5F5"
                  class="font-weight-bold text-white shadow-sm"
                  size="large"
                  :disabled="!canSubmit"
                  :loading="submitting"
                  prepend-icon="mdi-check"
                  @click="submitCharge"
                >
                  {{ $t('submit') }}
                  <span class="ml-1 text-caption opacity-70">(Ctrl+Enter)</span>
                </v-btn>
              </v-col>
              <v-col cols="4">
                <v-btn
                  block
                  rounded
                  variant="tonal"
                  color="red-lighten-1"
                  size="large"
                  :disabled="!chargeStore.charge"
                  @click="resetCharge"
                >
                  {{ $t('cancel') }}
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- RIGHT PANEL: Detail Charges Entry & Table -->
      <v-col cols="12" md="7">
        <DetailCharges v-if="chargeStore.charge && chargeStore.charge.charge_category_id" />
        <div v-else class="d-flex flex-column align-center justify-center rounded-xl pa-8" style="min-height: 440px; background: rgba(var(--v-theme-on-surface), .02); border: 2px dashed rgba(66,165,245,.2);">
          <div class="empty-hero-icon mb-3" style="background: rgba(66, 165, 245, .1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <v-icon size="42" color="#42A5F5">mdi-clipboard-arrow-left-outline</v-icon>
          </div>
          <div class="text-h6 font-weight-bold text-charge mb-1">{{ $t('select_charge_category_to_start') }}</div>
          <div class="text-caption text-medium-emphasis text-center" style="max-width: 320px;">
            {{ $t('select_category_desc') || 'Choose a charge category on the left to configure articles, quantities, and pricing.' }}
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Description Dialog -->
    <v-dialog v-model="descriptionDialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#42A5F5" class="mr-2">mdi-notebook-edit-outline</v-icon>
          <span class="font-weight-bold">{{ $t('description') }}</span>
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
            hide-details
            placeholder="Add relevant notes, supplier invoice number, reference info..."
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="#42A5F5" rounded @click="descriptionDialog = false" prepend-icon="mdi-check">
            {{ $t('done') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar with Countdown Progress Bar -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="8000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="360"
      max-width="420"
      @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
    >
      <v-progress-linear :model-value="snackbarProgress" color="#42A5F5" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('record_added_success') }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.category">
          <v-icon size="14" color="#42A5F5" class="mr-1">mdi-tag</v-icon>
          <span class="text-medium-emphasis">{{ $t('charge_category') }}:</span>
          <strong>{{ summarySnackbar.category }}</strong>
        </div>
        <div v-if="summarySnackbar.date">
          <v-icon size="14" color="#42A5F5" class="mr-1">mdi-calendar</v-icon>
          <span class="text-medium-emphasis">{{ $t('date') }}:</span>
          <strong>{{ summarySnackbar.date }}</strong>
        </div>
        <div v-if="summarySnackbar.trackingNumbers">
          <v-icon size="14" color="#42A5F5" class="mr-1">mdi-barcode</v-icon>
          <span class="text-medium-emphasis">{{ $t('reception_tracking_number') }}:</span>
          <strong>{{ summarySnackbar.trackingNumbers }}</strong>
        </div>
        <div v-if="summarySnackbar.supplier">
          <v-icon size="14" color="#42A5F5" class="mr-1">mdi-account</v-icon>
          <span class="text-medium-emphasis">{{ $t('supplier') }}:</span>
          <strong>{{ summarySnackbar.supplier }}</strong>
        </div>
        <v-divider class="my-1"></v-divider>
        <div class="d-flex ga-4">
          <div>
            <v-icon size="14" color="#42A5F5" class="mr-1">mdi-weight-kilogram</v-icon>
            <span class="text-medium-emphasis">{{ $t('total_weight') }}:</span>
            <strong class="text-charge">{{ summarySnackbar.totalWeight }} Kg</strong>
          </div>
          <div>
            <v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon>
            <span class="text-medium-emphasis">{{ $t('total_price') }}:</span>
            <strong class="text-success">{{ summarySnackbar.totalPrice }} {{ main.currency }}</strong>
          </div>
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
  data() {
    return {
      submitting: false,
      descriptionDialog: false,
      dateMenu: false,
      categoryColors: ['#42A5F5', '#26A69A', '#7E57C2', '#FFA726', '#EC407A', '#29B6F6', '#AB47BC', '#5C6BC0'],
      summarySnackbar: {
        show: false,
        type: '',
        category: '',
        date: '',
        trackingNumbers: '',
        supplier: '',
        totalWeight: '',
        totalPrice: '',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    chargeStore() { return useCharge(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    chargeCategoryOptions() {
      const lang = this.main.lang?.code || 'en';
      return useChargeCategory().list.map(c => ({
        title: c[`name_${lang}`] || c.name || `#${c.id}`,
        value: c.id,
      }));
    },
    selectedCategory() {
      return useChargeCategory().list.find(c => c.id === this.chargeStore.charge?.charge_category_id);
    },
    canSubmit() {
      const c = this.chargeStore.charge;
      if (this.chargeStore.plafondCheckResult?.is_blocked) return false;
      return !!(c?.date && c?.charge_category_id && c?.details?.length > 0);
    },
    descriptionLength() {
      return (this.chargeStore.charge?.description || '').length;
    },
    additionalFieldsCount() {
      const c = this.chargeStore.charge;
      if (!c) return 0;
      let count = 0;
      if (c.reception_tracking_number?.length) count++;
      if (c.tracking_onp_numbers?.length) count++;
      if (c.supplier_id) count++;
      if (c.fishery_id) count++;
      if (c.origin_id) count++;
      if (c.truck_ids?.length) count++;
      if (c.ship_id) count++;
      return count;
    },
    dateModel: {
      get() {
        if (!this.chargeStore.charge?.date) return null;
        return new Date(this.chargeStore.charge.date + 'T00:00:00');
      },
      set(val) {
        if (!this.chargeStore.charge) return;
        if (val instanceof Date) {
          const y = val.getFullYear();
          const m = String(val.getMonth() + 1).padStart(2, '0');
          const d = String(val.getDate()).padStart(2, '0');
          this.chargeStore.charge.date = `${y}-${m}-${d}`;
        } else {
          this.chargeStore.charge.date = val;
        }
      },
    },
    displayFormattedDate() {
      if (!this.chargeStore.charge?.date) return '';
      const d = new Date(this.chargeStore.charge.date + 'T00:00:00');
      if (isNaN(d.getTime())) return this.chargeStore.charge.date;
      return d.toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
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
          map[catId] = {
            id: catId,
            name: cat ? (cat[`name_${lang}`] || cat.name_fr || cat.name_en || `#${catId}`) : `#${catId}`,
            totalWeight: 0,
            totalPrice: 0,
          };
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
    if (!this.chargeStore.charge?.date) this.setToday();
    window.addEventListener('keydown', this.handleGlobalKeyDown);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.handleGlobalKeyDown);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  watch: {
    'chargeStore.charge': { handler() { this.chargeStore._persistDraft(); }, deep: true },
    'chargeStore.showAdditionalInfo'() { this.chargeStore._persistDraft(); },
  },
  methods: {
    handleGlobalKeyDown(e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        if (this.canSubmit && !this.submitting) {
          e.preventDefault();
          this.submitCharge();
        }
      }
    },
    setToday() {
      const today = new Date();
      const y = today.getFullYear();
      const m = String(today.getMonth() + 1).padStart(2, '0');
      const d = String(today.getDate()).padStart(2, '0');
      if (this.chargeStore.charge) {
        this.chargeStore.charge.date = `${y}-${m}-${d}`;
      }
      this.dateMenu = false;
    },
    setYesterday() {
      const yest = new Date();
      yest.setDate(yest.getDate() - 1);
      const y = yest.getFullYear();
      const m = String(yest.getMonth() + 1).padStart(2, '0');
      const d = String(yest.getDate()).padStart(2, '0');
      if (this.chargeStore.charge) {
        this.chargeStore.charge.date = `${y}-${m}-${d}`;
      }
      this.dateMenu = false;
    },
    getCategoryColor(idx) {
      return this.categoryColors[idx % this.categoryColors.length];
    },
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
    resetCharge() {
      this.chargeStore.add_charge(null);
      this.chargeStore.clearDraft();
      sessionStorage.removeItem('detail_charges_draft');
      this.setToday();
      this.main.add_snackbar({ show: true, message: this.$t('reset_charge'), color: 'success' });
    },
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
      } catch (err) {
        this.main.add_snackbar({
          show: true,
          message: err.response?.data?.message || this.$t('record_added_failed'),
          color: 'error',
        });
      }
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
.add-charge-card {
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  font-family: 'Inter', sans-serif;
  overflow: hidden;
}
.ac-hero {
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
.ac-hero-inner {
  padding: 16px 20px;
  color: #fff;
}
.ac-hero-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255,255,255,.2);
}
.ac-hero-title {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: .2px;
}
.text-charge {
  color: #42A5F5 !important;
}

/* Step cards */
.step-card {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  transition: border-color .2s ease;
}
.step-card:hover {
  border-color: rgba(66, 165, 245, .3);
}
.step-number {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* KPI metric row full-width */
.summary-kpi-row {
  width: 100% !important;
  display: flex !important;
}
.summary-kpi-tile {
  flex: 1 1 0 !important;
  min-width: 0 !important;
}

/* Category breakdown */
.cat-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}
</style>

