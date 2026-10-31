<template>
  <v-form ref="form" @submit.prevent="submitShipping">
    <v-row>
      <!-- LEFT PANEL: Necessary & Additional Info + Live Summary -->
      <v-col cols="12" md="5">
        <v-card class="add-shipping-card elevation-0" rounded="xl">
          <!-- Hero Header -->
          <div class="ash-hero">
            <div class="ash-hero-inner">
              <div class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center ga-3">
                  <div class="ash-hero-icon">
                    <v-icon size="26" color="white">mdi-truck-delivery-outline</v-icon>
                  </div>
                  <div>
                    <div class="ash-hero-title">{{ $t("shipping_create_interface") }}</div>
                    <div class="text-white text-caption" style="opacity:.8">{{ $t('new_shipping_desc') }}</div>
                  </div>
                </div>

                <v-chip size="small" color="white" variant="outlined" class="font-weight-bold" style="border-color: rgba(255,255,255,.35)">
                  <v-icon size="14" class="mr-1">mdi-pound</v-icon>
                  {{ shippingStore.shipping?.details?.length || 0 }} {{ $t('articles_count') || 'Articles' }}
                </v-chip>
              </div>
            </div>
          </div>

          <v-card-text v-if="shippingStore.shipping" class="pa-4">
            <!-- STEP 1: Necessary Information -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(70, 67, 160, 0.12); color: #4643a0;">1</div>
                  <span class="text-subtitle-2 font-weight-bold text-shipping">{{ $t("necessary_information") }}</span>
                </div>
                <span class="text-caption text-medium-emphasis">* {{ $t('required') || 'Required' }}</span>
              </div>

              <!-- Shipping Category & Description -->
              <div class="d-flex align-center ga-2 mb-3">
                <v-autocomplete
                  v-model="shippingStore.shipping.shipping_category_id"
                  :items="shippingCategoryOptions"
                  :label="$t('shipping_category')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#4643a0"
                  class="text-shipping flex-grow-1"
                  hide-details
                  prepend-inner-icon="mdi-shape-outline"
                  @update:modelValue="onCategoryChange"
                >
                  <template #chip="{ item }">
                    <v-chip color="#4643a0" variant="tonal" class="font-weight-medium">{{ item.title }}</v-chip>
                  </template>
                </v-autocomplete>

                <v-btn
                  variant="tonal"
                  color="#4643a0"
                  rounded="lg"
                  icon="mdi-notebook-edit-outline"
                  :disabled="!shippingStore.shipping?.shipping_category_id"
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
                    <v-icon size="16" color="#4643a0">mdi-calendar-month</v-icon>
                    <span class="text-caption font-weight-bold text-shipping">{{ $t('date') }}</span>
                    <v-chip v-if="displayFormattedDate" size="x-small" color="#4643a0" variant="flat" class="ml-1 font-weight-bold text-white">
                      {{ displayFormattedDate }}
                    </v-chip>
                  </div>
                  <div class="d-flex align-center ga-1">
                    <v-btn
                      size="x-small"
                      variant="tonal"
                      color="#4643a0"
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
                  color="purple"
                  borderless
                  class="w-100"
                />
              </div>
            </div>

            <!-- STEP 2: Additional Information (Accordion) -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div
                class="d-flex align-center justify-space-between cursor-pointer"
                @click="shippingStore.shipping?.shipping_category_id ? (showAdditional = !showAdditional) : null"
              >
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(70, 67, 160, 0.12); color: #4643a0;">2</div>
                  <span class="text-subtitle-2 font-weight-bold text-shipping">{{ $t("additional_information") }}</span>
                </div>

                <div class="d-flex align-center ga-2">
                  <v-chip
                    v-if="additionalFieldsCount > 0"
                    size="x-small"
                    color="#4643a0"
                    variant="flat"
                    class="font-weight-bold text-white"
                  >
                    {{ additionalFieldsCount }} {{ $t('configured') || 'set' }}
                  </v-chip>
                  <v-btn
                    icon
                    size="small"
                    variant="text"
                    color="#4643a0"
                    :disabled="!shippingStore.shipping?.shipping_category_id"
                  >
                    <v-icon>{{ showAdditional ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                  </v-btn>
                </div>
              </div>

              <!-- Expandable Additional Fields -->
              <v-expand-transition>
                <div v-show="showAdditional && selectedCategory" class="pt-3 border-t mt-3">
                  <Shipping ref="shippingForm" />
                </div>
              </v-expand-transition>
            </div>

            <!-- STEP 3: Live Summary Cards -->
            <div class="step-card pa-3 rounded-lg mb-3">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <div class="step-number" style="background: rgba(70, 67, 160, 0.12); color: #4643a0;">3</div>
                  <span class="text-subtitle-2 font-weight-bold text-shipping">{{ $t("summary") }}</span>
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
                    {{ shippingStore.shipping?.details?.length || 0 }}
                  </div>
                </div>

                <!-- Total Weight -->
                <div class="summary-kpi-tile pa-2 rounded-lg text-center" style="flex: 1 1 0; min-width: 0; background: rgba(70, 67, 160, .08); border: 1px solid rgba(70, 67, 160, .2);">
                  <div class="text-caption text-medium-emphasis mb-1 text-truncate">{{ $t('total_weight') }}</div>
                  <div class="text-h6 font-weight-bold text-shipping" style="line-height: 1.2;">
                    {{ main.formatDisplayNumber(shippingStore.shipping?.total_weight || 0) }}
                    <span class="text-caption font-weight-normal">Kg</span>
                  </div>
                </div>

                <!-- Total Price -->
                <div class="summary-kpi-tile pa-2 rounded-lg text-center" style="flex: 1 1 0; min-width: 0; background: rgba(76, 175, 80, .08); border: 1px solid rgba(76, 175, 80, .2);">
                  <div class="text-caption text-medium-emphasis mb-1 text-truncate">{{ $t('total_price') }}</div>
                  <div class="text-h6 font-weight-bold text-success" style="line-height: 1.2;">
                    {{ main.formatDisplayNumber(shippingStore.shipping?.total_price || 0) }}
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
              <div v-if="!shippingStore.shipping?.shipping_category_id" class="text-warning d-flex align-center ga-1">
                <v-icon size="14">mdi-alert-circle-outline</v-icon>
                <span>{{ $t('select_shipping_category_to_start') }}</span>
              </div>
              <div v-else-if="!shippingStore.shipping?.date" class="text-warning d-flex align-center ga-1">
                <v-icon size="14">mdi-alert-circle-outline</v-icon>
                <span>{{ $t('date_required') || 'Date is required' }}</span>
              </div>
              <div v-else-if="!shippingStore.shipping?.details?.length" class="text-info d-flex align-center ga-1">
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
                  color="#4643a0"
                  class="font-weight-bold text-white shadow-sm"
                  size="large"
                  :disabled="!canSubmit"
                  :loading="submitting"
                  prepend-icon="mdi-check"
                  @click="submitShipping"
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
                  :disabled="!shippingStore.shipping"
                  @click="resetShipping"
                >
                  {{ $t('cancel') }}
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- RIGHT PANEL: Detail Shippings -->
      <v-col cols="12" md="7">
        <detailShippings v-if="shippingStore.shipping && shippingStore.shipping.shipping_category_id" />
        <div v-else class="empty-detail-state d-flex flex-column align-center justify-center pa-8 rounded-xl border">
          <div class="empty-icon-circle mb-3">
            <v-icon size="40" color="#4643a0">mdi-clipboard-text-outline</v-icon>
          </div>
          <div class="text-subtitle-1 font-weight-bold text-shipping mb-1">
            {{ $t('select_shipping_category_to_start') }}
          </div>
          <div class="text-caption text-medium-emphasis text-center" style="max-width: 320px;">
            {{ $t('select_category_hint') || 'Choose a shipping category from the left panel to configure items and quantities.' }}
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Summary Snackbar with Countdown Progress Bar -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="8000"
      location="top right"
      color="surface"
      rounded="xl"
      elevation="16"
      min-width="360"
      max-width="420"
      class="summary-snackbar"
      @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
    >
      <v-progress-linear
        :model-value="snackbarProgress"
        color="#4643a0"
        height="4"
        class="mb-3 rounded-pill"
      ></v-progress-linear>

      <div class="d-flex align-center justify-space-between mb-2">
        <div class="d-flex align-center ga-2">
          <v-icon color="success" size="22">mdi-check-circle</v-icon>
          <span class="text-subtitle-2 font-weight-bold">{{ $t('record_added_success') }}</span>
        </div>
        <v-btn variant="text" size="x-small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </div>

      <v-divider class="mb-2"></v-divider>

      <div class="d-flex flex-column ga-1 text-caption">
        <div v-if="summarySnackbar.category" class="d-flex align-center justify-space-between">
          <span class="text-medium-emphasis">{{ $t('shipping_category') }}:</span>
          <strong>{{ summarySnackbar.category }}</strong>
        </div>
        <div v-if="summarySnackbar.date" class="d-flex align-center justify-space-between">
          <span class="text-medium-emphasis">{{ $t('date') }}:</span>
          <strong>{{ summarySnackbar.date }}</strong>
        </div>
        <div v-if="summarySnackbar.trackingNumbers" class="d-flex align-center justify-space-between">
          <span class="text-medium-emphasis">{{ $t('shipping_tracking_number') }}:</span>
          <strong>{{ summarySnackbar.trackingNumbers }}</strong>
        </div>
        <div v-if="summarySnackbar.client" class="d-flex align-center justify-space-between">
          <span class="text-medium-emphasis">{{ $t('client') }}:</span>
          <strong>{{ summarySnackbar.client }}</strong>
        </div>

        <v-divider class="my-1"></v-divider>

        <div class="d-flex align-center justify-space-between pt-1">
          <div>
            <span class="text-medium-emphasis">{{ $t('total_weight') }}:</span>
            <strong class="ml-1 text-shipping">{{ summarySnackbar.totalWeight }} Kg</strong>
          </div>
          <div>
            <span class="text-medium-emphasis">{{ $t('total_price') }}:</span>
            <strong class="ml-1 text-success">{{ summarySnackbar.totalPrice }} {{ main.currency }}</strong>
          </div>
        </div>
      </div>
    </v-snackbar>

    <!-- Description Dialog -->
    <v-dialog v-model="descriptionDialog" max-width="600" persistent>
      <v-card class="rounded-xl pa-2">
        <v-card-title class="d-flex align-center pa-3">
          <v-icon color="#4643a0" class="mr-2">mdi-notebook-edit-outline</v-icon>
          <span class="text-subtitle-1 font-weight-bold">{{ $t('description') }}</span>
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
            hide-details
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-3 pt-0">
          <v-spacer></v-spacer>
          <v-btn
            variant="tonal"
            color="#4643a0"
            rounded
            @click="descriptionDialog = false"
            prepend-icon="mdi-check"
          >
            {{ $t('done') }}
          </v-btn>
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
  data() {
    return {
      submitting: false,
      descriptionDialog: false,
      dateMenu: false,
      showAdditional: false,
      categoryColors: ['#4643a0', '#2E7D32', '#00838F', '#E65100', '#AD1457', '#283593'],
      summarySnackbar: {
        show: false,
        type: '',
        category: '',
        date: '',
        trackingNumbers: '',
        client: '',
        totalWeight: '',
        totalPrice: '',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    shippingStore() { return useShipping(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    selectedCategory() {
      return useShippingCategory().list.find(c => c.id === this.shippingStore.shipping?.shipping_category_id);
    },
    shippingCategoryOptions() {
      return useShippingCategory().list.map(c => ({
        title: c[`name_${this.lang}`] || c.name || `#${c.id}`,
        value: c.id,
      }));
    },
    canSubmit() {
      const s = this.shippingStore.shipping;
      if (this.shippingStore.plafondCheckResult?.is_blocked) return false;
      return !!(s?.date && s?.shipping_category_id && s?.details?.length > 0);
    },
    descriptionLength() {
      return (this.shippingStore.shipping?.description || '').length;
    },
    displayFormattedDate() {
      return this.shippingStore.shipping?.date || '';
    },
    dateModel: {
      get() {
        if (!this.shippingStore.shipping?.date) return null;
        return new Date(this.shippingStore.shipping.date + 'T00:00:00');
      },
      set(val) {
        if (!this.shippingStore.shipping) return;
        if (val instanceof Date) {
          const y = val.getFullYear();
          const m = String(val.getMonth() + 1).padStart(2, '0');
          const d = String(val.getDate()).padStart(2, '0');
          this.shippingStore.shipping.date = `${y}-${m}-${d}`;
        } else {
          this.shippingStore.shipping.date = val;
        }
      },
    },
    additionalFieldsCount() {
      const s = this.shippingStore.shipping;
      if (!s) return 0;
      let count = 0;
      if (s.shipping_tracking_number?.length) count += s.shipping_tracking_number.length;
      if (s.tracking_onp_numbers?.length) count += s.tracking_onp_numbers.length;
      if (s.client_id) count++;
      if (s.origin_id) count++;
      if (s.direction) count++;
      if (s.fishery_id) count++;
      if (s.ship_id) count++;
      if (s.truck_ids?.length) count += s.truck_ids.length;
      return count;
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
    this.shippingStore._restoreDraft();
    if (!this.shippingStore.shipping) this.shippingStore.add_shipping(null);
    if (!this.shippingStore.shipping.date) {
      this.setToday();
    }
    this.showAdditional = !!this.shippingStore.showAdditionalInfo;
    window.addEventListener('keydown', this.handleGlobalKeydown);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.handleGlobalKeydown);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  watch: {
    'shippingStore.shipping': {
      handler() { this.shippingStore._persistDraft(); },
      deep: true,
    },
    showAdditional(val) {
      this.shippingStore.showAdditionalInfo = val;
      this.shippingStore._persistDraft();
    },
  },
  methods: {
    handleGlobalKeydown(e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        if (this.canSubmit && !this.submitting) {
          this.submitShipping();
        }
      }
    },
    setToday() {
      const now = new Date();
      const y = now.getFullYear();
      const m = String(now.getMonth() + 1).padStart(2, '0');
      const d = String(now.getDate()).padStart(2, '0');
      if (this.shippingStore.shipping) {
        this.shippingStore.shipping.date = `${y}-${m}-${d}`;
      }
      this.dateMenu = false;
    },
    setYesterday() {
      const prev = new Date();
      prev.setDate(prev.getDate() - 1);
      const y = prev.getFullYear();
      const m = String(prev.getMonth() + 1).padStart(2, '0');
      const d = String(prev.getDate()).padStart(2, '0');
      if (this.shippingStore.shipping) {
        this.shippingStore.shipping.date = `${y}-${m}-${d}`;
      }
      this.dateMenu = false;
    },
    getCategoryColor(idx) {
      return this.categoryColors[idx % this.categoryColors.length];
    },
    onCategoryChange() {
      if (this.shippingStore.shipping) {
        this.shippingStore.shipping.details = [];
        this.shippingStore.shipping.shipping_tracking_number = [];
        this.shippingStore.shipping.tracking_onp_numbers = [];
        this.shippingStore.shipping.client_id = null;
        this.shippingStore.shipping.truck_ids = [];
        this.shippingStore.shipping.origin_id = null;
        this.shippingStore.shipping.direction = null;
        this.shippingStore.shipping.fishery_id = null;
        this.shippingStore.shipping.ship_id = null;
        this.shippingStore.shipping.total_price = 0;
        this.shippingStore.shipping.total_weight = 0;
      }
      this.showAdditional = false;
    },
    resetShipping() {
      this.shippingStore.add_shipping(null);
      this.shippingStore.clearDraft();
      sessionStorage.removeItem('detail_shippings_draft');
      this.setToday();
      this.main.add_snackbar({ show: true, message: this.$t('reset_shipping'), color: 'success' });
    },
    async submitShipping() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const s = JSON.parse(JSON.stringify(this.shippingStore.shipping));
        s.details = s.details.map(d => {
          const { _uid, ...rest } = d;
          return rest;
        });
        delete s.id;
        await axios.post('/api/shipping', s);
        this.shippingStore.clearDraft();
        sessionStorage.removeItem('detail_shippings_draft');
        this.shippingStore.add_shipping(null);
        this.showSnackbarFromSnapshot(snapshot, 'submit');
        this.$router.push({ name: 'list_shipping' });
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
    showSummarySnackbar(type) {
      this.showSnackbarFromSnapshot(this.buildSnapshot(), type);
    },
    showSnackbarFromSnapshot(snap, type) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, type, ...snap };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 1.25;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
        }
      }, 100);
    },
  },
};
</script>

<style scoped>
.add-shipping-card {
  font-family: 'Inter', sans-serif;
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  background: rgba(var(--v-theme-surface), 1);
  overflow: hidden;
}

.ash-hero {
  background: linear-gradient(135deg, #282163 0%, #372e7d 40%, #4643a0 100%);
  position: relative;
}

.ash-hero-inner {
  padding: 16px 20px;
}

.ash-hero-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.ash-hero-title {
  color: #fff;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.step-card {
  background: rgba(var(--v-theme-on-surface), 0.02);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  transition: all 0.2s ease;
}

.step-number {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
}

.text-shipping {
  color: #4643a0 !important;
}

.cursor-pointer {
  cursor: pointer;
}

.cat-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.empty-detail-state {
  min-height: 380px;
  background: rgba(var(--v-theme-on-surface), 0.015);
  border: 1.5px dashed rgba(70, 67, 160, 0.25) !important;
}

.empty-icon-circle {
  width: 72px;
  height: 72px;
  border-radius: 20px;
  background: rgba(70, 67, 160, 0.08);
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
</style>
