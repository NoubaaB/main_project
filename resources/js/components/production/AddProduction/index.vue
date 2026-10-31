<template>
  <v-form ref="form" @submit.prevent="submitProd">
    <v-row>
      <!-- LEFT PANEL: Necessary & Additional Info + Live Summary -->
      <v-col cols="12" md="5">
        <v-card class="add-prod-card elevation-0" rounded="xl">
          <!-- Hero Header -->
          <div class="ap-hero">
            <div class="ap-hero-inner">
              <div class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center ga-3">
                  <div class="ap-hero-icon">
                    <v-icon size="26" color="white">mdi-factory</v-icon>
                  </div>
                  <div>
                    <div class="ap-hero-title">{{ $t("production_create_interface") }}</div>
                    <div class="text-white text-caption" style="opacity:.8">{{ $t('new_production_desc') }}</div>
                  </div>
                </div>

                <v-chip size="small" color="white" variant="outlined" class="font-weight-bold" style="border-color: rgba(255,255,255,.35)">
                  <v-icon size="14" class="mr-1">mdi-pound</v-icon>
                  {{ prodStore.production?.details?.length || 0 }} {{ $t('articles_count') || 'Articles' }}
                </v-chip>
              </div>
            </div>
          </div>

          <v-card-text v-if="prodStore.production" class="pa-4">
            <!-- STEP 1: Necessary Information -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(0, 137, 123, 0.12); color: #00695C;">1</div>
                  <span class="text-subtitle-2 font-weight-bold text-prod">{{ $t("necessary_information") }}</span>
                </div>
                <span class="text-caption text-medium-emphasis">* {{ $t('required') || 'Required' }}</span>
              </div>

              <!-- Production Category & Description -->
              <div class="d-flex align-center ga-2 mb-3">
                <v-autocomplete
                  v-model="prodStore.production.production_category_id"
                  :items="catOptions"
                  :label="$t('production_category')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod flex-grow-1"
                  hide-details
                  prepend-inner-icon="mdi-shape-outline"
                  @update:modelValue="onCategoryChange"
                >
                  <template #chip="{ item }">
                    <v-chip color="#00897B" variant="tonal" class="font-weight-medium">{{ item.title }}</v-chip>
                  </template>
                </v-autocomplete>

                <v-btn
                  variant="tonal"
                  color="#00897B"
                  rounded="lg"
                  icon="mdi-notebook-edit-outline"
                  :disabled="!prodStore.production?.production_category_id"
                  :title="$t('description')"
                  @click="descriptionDialog = true"
                >
                  <v-badge v-if="descriptionLength > 0" dot color="primary">
                    <v-icon>mdi-notebook-edit-outline</v-icon>
                  </v-badge>
                  <v-icon v-else>mdi-notebook-edit-outline</v-icon>
                </v-btn>
              </div>

              <!-- Production Type (Intern / Extern) if enabled -->
              <div v-if="selectedCategory?.enable_production_type" class="mb-3">
                <v-autocomplete
                  v-model="prodStore.production.production_type"
                  :items="productionTypeOptions"
                  :label="$t('production_type')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  clearable
                  prepend-inner-icon="mdi-domain"
                >
                  <template #chip="{ item }">
                    <v-chip color="#00897B" variant="tonal" class="font-weight-medium">{{ item.title }}</v-chip>
                  </template>
                </v-autocomplete>
              </div>

              <!-- Full-width Inline VCalendar DatePicker (matching design specification) -->
              <div class="date-picker-card rounded-xl overflow-hidden border pa-2 mb-1 bg-surface">
                <div class="d-flex align-center justify-space-between px-2 pt-1 pb-2">
                  <div class="d-flex align-center ga-1">
                    <v-icon size="16" color="#00897B">mdi-calendar-month</v-icon>
                    <span class="text-caption font-weight-bold text-prod">{{ $t('date') }}</span>
                    <v-chip v-if="displayFormattedDate" size="x-small" color="#00897B" variant="flat" class="ml-1 font-weight-bold text-white">
                      {{ displayFormattedDate }}
                    </v-chip>
                  </div>
                  <div class="d-flex align-center ga-1">
                    <v-btn
                      size="x-small"
                      variant="tonal"
                      color="#00897B"
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
                  color="teal"
                  borderless
                  class="w-100"
                />
              </div>
            </div>

            <!-- STEP 2: Additional Information (Accordion) -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div
                class="d-flex align-center justify-space-between cursor-pointer"
                @click="prodStore.production?.production_category_id ? (showAdditional = !showAdditional) : null"
              >
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(0, 137, 123, 0.12); color: #00695C;">2</div>
                  <span class="text-subtitle-2 font-weight-bold text-prod">{{ $t("additional_information") }}</span>
                </div>

                <div class="d-flex align-center ga-2">
                  <v-chip
                    v-if="additionalFieldsCount > 0"
                    size="x-small"
                    color="#00897B"
                    variant="flat"
                    class="font-weight-bold text-white"
                  >
                    {{ additionalFieldsCount }} {{ $t('configured') || 'set' }}
                  </v-chip>
                  <v-btn
                    icon
                    size="small"
                    variant="text"
                    color="#00897B"
                    :disabled="!prodStore.production?.production_category_id"
                  >
                    <v-icon>{{ showAdditional ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                  </v-btn>
                </div>
              </div>

              <!-- Expandable Additional Fields -->
              <v-expand-transition>
                <div v-show="showAdditional && selectedCategory" class="pt-3 border-t mt-3">
                  <Production ref="productionForm" />
                </div>
              </v-expand-transition>
            </div>

            <!-- STEP 3: Live Summary Cards -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(0, 137, 123, 0.12); color: #00695C;">3</div>
                  <span class="text-subtitle-2 font-weight-bold text-prod">{{ $t("summary") }}</span>
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
                    {{ prodStore.production?.details?.length || 0 }}
                  </div>
                </div>

                <!-- Total Weight -->
                <div class="summary-kpi-tile pa-2 rounded-lg text-center" style="flex: 1 1 0; min-width: 0; background: rgba(0, 137, 123, .08); border: 1px solid rgba(0, 137, 123, .2);">
                  <div class="text-caption text-medium-emphasis mb-1 text-truncate">{{ $t('total_weight') }}</div>
                  <div class="text-h6 font-weight-bold text-prod" style="line-height: 1.2;">
                    {{ main.formatDisplayNumber(prodStore.production?.total_weight || 0) }}
                    <span class="text-caption font-weight-normal">Kg</span>
                  </div>
                </div>

                <!-- Total Price -->
                <div class="summary-kpi-tile pa-2 rounded-lg text-center" style="flex: 1 1 0; min-width: 0; background: rgba(76, 175, 80, .08); border: 1px solid rgba(76, 175, 80, .2);">
                  <div class="text-caption text-medium-emphasis mb-1 text-truncate">{{ $t('total_price') }}</div>
                  <div class="text-h6 font-weight-bold text-success" style="line-height: 1.2;">
                    {{ main.formatDisplayNumber(prodStore.production?.total_price || 0) }}
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
              <div v-if="!prodStore.production?.production_category_id" class="text-warning d-flex align-center ga-1">
                <v-icon size="14">mdi-alert-circle-outline</v-icon>
                <span>{{ $t('select_production_category_to_start') }}</span>
              </div>
              <div v-else-if="!prodStore.production?.date" class="text-warning d-flex align-center ga-1">
                <v-icon size="14">mdi-alert-circle-outline</v-icon>
                <span>{{ $t('date_required') || 'Date is required' }}</span>
              </div>
              <div v-else-if="!prodStore.production?.details?.length" class="text-info d-flex align-center ga-1">
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
                  color="#00897B"
                  class="font-weight-bold text-white shadow-sm"
                  size="large"
                  :disabled="!canSubmit"
                  :loading="submitting"
                  prepend-icon="mdi-check"
                  @click="submitProd"
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
                  :disabled="!prodStore.production"
                  @click="resetProd"
                >
                  {{ $t('cancel') }}
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- RIGHT PANEL: Detail Productions Entry & Table -->
      <v-col cols="12" md="7">
        <DetailProductions v-if="prodStore.production?.production_category_id" />
        <div v-else class="d-flex flex-column align-center justify-center rounded-xl pa-8" style="min-height: 440px; background: rgba(var(--v-theme-on-surface), .02); border: 2px dashed rgba(0,137,123,.2);">
          <div class="empty-hero-icon mb-3" style="background: rgba(0, 137, 123, .1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <v-icon size="42" color="#00897B">mdi-clipboard-arrow-left-outline</v-icon>
          </div>
          <div class="text-h6 font-weight-bold text-prod mb-1">{{ $t('select_production_category_to_start') }}</div>
          <div class="text-caption text-medium-emphasis text-center" style="max-width: 320px;">
            {{ $t('select_prod_category_desc') || 'Choose a production category on the left to configure articles, batches, and outputs.' }}
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Description Dialog -->
    <v-dialog v-model="descriptionDialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#00897B" class="mr-2">mdi-notebook-edit-outline</v-icon>
          <span class="font-weight-bold">{{ $t('description') }}</span>
          <v-spacer></v-spacer>
          <v-chip size="small" :color="descriptionLength > 1000 ? 'error' : 'teal'" variant="tonal">
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
            hide-details
            placeholder="Add relevant notes, batch numbers, shift info..."
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="#00897B" rounded @click="descriptionDialog = false" prepend-icon="mdi-check">
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
      <v-progress-linear :model-value="snackbarProgress" color="#00897B" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('record_added_success') }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.category">
          <v-icon size="14" color="#00897B" class="mr-1">mdi-tag</v-icon>
          <span class="text-medium-emphasis">{{ $t('production_category') }}:</span>
          <strong>{{ summarySnackbar.category }}</strong>
        </div>
        <div v-if="summarySnackbar.date">
          <v-icon size="14" color="#00897B" class="mr-1">mdi-calendar</v-icon>
          <span class="text-medium-emphasis">{{ $t('date') }}:</span>
          <strong>{{ summarySnackbar.date }}</strong>
        </div>
        <div v-if="summarySnackbar.trackingNumbers">
          <v-icon size="14" color="#00897B" class="mr-1">mdi-barcode</v-icon>
          <span class="text-medium-emphasis">{{ $t('production_tracking_number') }}:</span>
          <strong>{{ summarySnackbar.trackingNumbers }}</strong>
        </div>
        <div v-if="summarySnackbar.supplier">
          <v-icon size="14" color="#00897B" class="mr-1">mdi-account</v-icon>
          <span class="text-medium-emphasis">{{ $t('supplier') }}:</span>
          <strong>{{ summarySnackbar.supplier }}</strong>
        </div>
        <v-divider class="my-1"></v-divider>
        <div class="d-flex ga-4">
          <div>
            <v-icon size="14" color="#00897B" class="mr-1">mdi-weight-kilogram</v-icon>
            <span class="text-medium-emphasis">{{ $t('total_weight') }}:</span>
            <strong class="text-prod">{{ summarySnackbar.totalWeight }} Kg</strong>
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
  data() {
    return {
      submitting: false,
      showAdditional: false,
      descriptionDialog: false,
      dateMenu: false,
      categoryColors: ['#00897B', '#26A69A', '#00ACC1', '#43A047', '#7CB342', '#3949AB', '#FB8C00', '#8E24AA'],
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
    prodStore() { return useProduction(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    selectedCategory() {
      return useProductionCategory().list.find(c => c.id === this.prodStore.production?.production_category_id);
    },
    catOptions() {
      return useProductionCategory().list.map(c => ({
        title: c[`name_${this.lang}`] || c.name || `#${c.id}`,
        value: c.id,
      }));
    },
    productionTypeOptions() {
      return [
        { title: this.$t('intern') || 'Internal', value: 'intern' },
        { title: this.$t('extern') || 'External', value: 'extern' },
        { title: this.$t('standard') || 'Standard', value: 'standard' },
        { title: this.$t('sous_traitance') || 'Subcontracting', value: 'sous_traitance' },
        { title: this.$t('guano') || 'Guano', value: 'guano' },
      ];
    },
    canSubmit() {
      const p = this.prodStore.production;
      if (this.prodStore.plafondCheckResult?.is_blocked) return false;
      return !!(p?.date && p?.production_category_id && p?.details?.length > 0);
    },
    descriptionLength() {
      return (this.prodStore.production?.description || '').length;
    },
    additionalFieldsCount() {
      const p = this.prodStore.production;
      if (!p) return 0;
      let count = 0;
      if (p.production_tracking_number?.length) count++;
      if (p.supplier_id) count++;
      if (p.charge_id) count++;
      if (p.shift) count++;
      return count;
    },
    dateModel: {
      get() {
        if (!this.prodStore.production?.date) return null;
        return new Date(this.prodStore.production.date + 'T00:00:00');
      },
      set(val) {
        if (!this.prodStore.production) return;
        if (val instanceof Date) {
          const y = val.getFullYear();
          const m = String(val.getMonth() + 1).padStart(2, '0');
          const d = String(val.getDate()).padStart(2, '0');
          this.prodStore.production.date = `${y}-${m}-${d}`;
        } else {
          this.prodStore.production.date = val;
        }
      },
    },
    displayFormattedDate() {
      if (!this.prodStore.production?.date) return '';
      const d = new Date(this.prodStore.production.date + 'T00:00:00');
      if (isNaN(d.getTime())) return this.prodStore.production.date;
      return d.toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    },
    categorySummary() {
      const details = this.prodStore.production?.details || [];
      if (!details.length) return [];
      const detailCats = useDetailProductionCategory().list;
      const map = {};
      details.forEach(d => {
        const catId = d.detail_production_category_id;
        if (!map[catId]) {
          const cat = detailCats.find(c => c.id === catId);
          map[catId] = {
            id: catId,
            name: cat ? (cat[`name_${this.lang}`] || cat.name_fr || cat.name_en || `#${catId}`) : `#${catId}`,
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
    const qChargeId = parseInt(this.$route.query.charge_id) || null;
    this.prodStore._restoreDraft();
    if (!this.prodStore.production) this.prodStore.add_production(null);
    if (!this.prodStore.production?.date) this.setToday();
    if (qChargeId) {
      this.prodStore.production.charge_id = qChargeId;
      this.showAdditional = true;
    }
    window.addEventListener('keydown', this.handleGlobalKeyDown);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.handleGlobalKeyDown);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  watch: {
    'prodStore.production': { handler() { this.prodStore._persistDraft(); }, deep: true },
  },
  methods: {
    handleGlobalKeyDown(e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        if (this.canSubmit && !this.submitting) {
          e.preventDefault();
          this.submitProd();
        }
      }
    },
    setToday() {
      const today = new Date();
      const y = today.getFullYear();
      const m = String(today.getMonth() + 1).padStart(2, '0');
      const d = String(today.getDate()).padStart(2, '0');
      if (this.prodStore.production) {
        this.prodStore.production.date = `${y}-${m}-${d}`;
      }
      this.dateMenu = false;
    },
    setYesterday() {
      const yest = new Date();
      yest.setDate(yest.getDate() - 1);
      const y = yest.getFullYear();
      const m = String(yest.getMonth() + 1).padStart(2, '0');
      const d = String(yest.getDate()).padStart(2, '0');
      if (this.prodStore.production) {
        this.prodStore.production.date = `${y}-${m}-${d}`;
      }
      this.dateMenu = false;
    },
    getCategoryColor(idx) {
      return this.categoryColors[idx % this.categoryColors.length];
    },
    onCategoryChange() {
      if (this.prodStore.production) {
        this.prodStore.production.details = [];
        this.prodStore.production.total_price = 0;
        this.prodStore.production.total_weight = 0;
      }
      this.showAdditional = false;
    },
    resetProd() {
      this.prodStore.add_production(null);
      this.prodStore.clearDraft();
      sessionStorage.removeItem('detail_productions_draft');
      this.setToday();
      this.main.add_snackbar({ show: true, message: this.$t('reset'), color: 'success' });
    },
    async submitProd() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const p = JSON.parse(JSON.stringify(this.prodStore.production));
        p.details = p.details.map(d => { const { _uid, ...rest } = d; return rest; });
        delete p.id;
        await axios.post('/api/productions', p);
        this.prodStore.clearDraft();
        sessionStorage.removeItem('detail_productions_draft');
        this.prodStore.add_production(null);
        this.showSnackbarFromSnapshot(snapshot, 'submit');
        this.$router.push({ name: 'production_list' });
      } catch (err) {
        this.main.add_snackbar({
          show: true,
          message: err.response?.data?.message || this.$t('error_creating_production'),
          color: 'error',
        });
      }
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
    showSummarySnackbar(type) {
      this.showSnackbarFromSnapshot(this.buildSnapshot(), type);
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
.add-prod-card {
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  font-family: 'Inter', sans-serif;
  overflow: hidden;
}
.ap-hero {
  background: linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
.ap-hero-inner {
  padding: 16px 20px;
  color: #fff;
}
.ap-hero-icon {
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
.ap-hero-title {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: .2px;
}
.text-prod {
  color: #00897B !important;
}

/* Step cards */
.step-card {
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  transition: border-color .2s ease;
}
.step-card:hover {
  border-color: rgba(0, 137, 123, .3);
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

