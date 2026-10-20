<template>
  <div class="init-stock-container pa-4">
    <!-- Informational Banner -->
    <v-alert
      density="comfortable"
      variant="tonal"
      rounded="xl"
      class="mb-4 init-stock-alert"
      color="#6D4C41"
      border="start"
    >
      <template #prepend>
        <v-icon size="26" color="#6D4C41">mdi-shield-refresh-outline</v-icon>
      </template>
      <div class="d-flex flex-column ga-1">
        <div class="text-subtitle-2 font-weight-bold" style="color: #6D4C41">
          {{ $t('init_warehouse_stock_checkpoint_title') || 'Stock Initialization & Cutoff Checkpoint' }}
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ $t('init_warehouse_stock_checkpoint_desc') || 'Select warehouses and a cutoff date to initialize stock to 0 on that date. Historical stock prior to this date remains intact. Any vouchers dated before this cutoff date will contribute to historical stock prior to the cutoff, but will NOT be added to stock on or after the cutoff date.' }}
        </div>
      </div>
    </v-alert>

    <v-row>
      <!-- LEFT: Configuration & Form Card -->
      <v-col cols="12" lg="5">
        <v-card class="init-stock-card pa-4" elevation="0" rounded="xl">
          <div class="d-flex align-center ga-2 mb-3">
            <v-icon size="20" color="#6D4C41">mdi-numeric-1-circle-outline</v-icon>
            <span class="text-subtitle-1 font-weight-bold" style="color: #6D4C41">
              {{ $t('select_warehouses_and_date') || 'Select Warehouses & Cutoff Date' }}
            </span>
          </div>

          <!-- Multi-select Warehouses -->
          <div class="mb-3">
            <label class="form-label text-caption font-weight-bold mb-1 d-block">
              {{ $t('target_warehouses') || 'Target Warehouses' }} *
            </label>
            <v-autocomplete
              v-model="selectedWarehouseIds"
              :items="warehouseOptions"
              item-title="name"
              item-value="id"
              multiple
              chips
              closable-chips
              density="compact"
              variant="outlined"
              rounded
              color="#6D4C41"
              :placeholder="$t('choose_warehouses') || 'Choose one or more warehouses...'"
              prepend-inner-icon="mdi-warehouse"
              hide-details="auto"
              :loading="loadingWarehouses"
              @update:modelValue="onConfigChanged"
            >
              <template #chip="{ item, props }">
                <v-chip
                  v-bind="props"
                  color="#6D4C41"
                  variant="tonal"
                  size="small"
                  class="font-weight-medium"
                >
                  <v-icon size="14" class="mr-1">mdi-warehouse</v-icon>
                  {{ item.title }}
                </v-chip>
              </template>
            </v-autocomplete>
          </div>

          <!-- Cutoff Date Picker -->
          <div class="mb-3">
            <label class="form-label text-caption font-weight-bold mb-1 d-block">
              {{ $t('cutoff_date') || 'Cutoff Date' }} *
            </label>
            <div class="date-picker-wrapper mb-2">
              <DatePicker
                :is-dark="main.is_dark"
                expanded
                v-model="dateModel"
                :locale="main.lang?.short || 'en'"
                mode="date"
                color="brown"
              />
            </div>
            <v-text-field
              v-model="resetDate"
              type="date"
              density="compact"
              variant="outlined"
              rounded
              color="#6D4C41"
              :label="$t('selected_date') || 'Selected Date (YYYY-MM-DD)'"
              prepend-inner-icon="mdi-calendar"
              hide-details="auto"
              @change="onConfigChanged"
            />
          </div>

          <!-- Reason / Audit Note -->
          <div class="mb-4">
            <label class="form-label text-caption font-weight-bold mb-1 d-block">
              {{ $t('reset_reason_audit_note') || 'Reason / Audit Note (Optional)' }}
            </label>
            <v-text-field
              v-model="resetReason"
              density="compact"
              variant="outlined"
              rounded
              color="#6D4C41"
              :placeholder="$t('reset_reason_placeholder') || 'e.g. Annual inventory reconciliation, physical stock audit...'"
              prepend-inner-icon="mdi-note-text-outline"
              hide-details="auto"
              maxlength="500"
            />
          </div>

          <!-- Action Buttons -->
          <v-row class="ma-0 ga-2">
            <v-col class="pa-0">
              <v-btn
                block
                rounded
                variant="outlined"
                color="#6D4C41"
                prepend-icon="mdi-eye-outline"
                :loading="loadingPreview"
                :disabled="!canPreview"
                @click="fetchPreview"
              >
                {{ $t('preview_cutoff') || 'Preview Cutoff' }}
              </v-btn>
            </v-col>
            <v-col class="pa-0">
              <v-btn
                block
                rounded
                variant="elevated"
                color="#6D4C41"
                class="text-white"
                prepend-icon="mdi-restore-alert"
                :disabled="!canSubmit"
                @click="openConfirmDialog"
              >
                {{ $t('init_stock_to_zero_btn') || 'Init Stock to 0' }}
              </v-btn>
            </v-col>
          </v-row>
        </v-card>
      </v-col>

      <!-- RIGHT: Live Preview of Stock to be Zeroed -->
      <v-col cols="12" lg="7">
        <v-card class="init-stock-card pa-4 h-100 d-flex flex-column" elevation="0" rounded="xl">
          <div class="d-flex align-center justify-space-between mb-3">
            <div class="d-flex align-center ga-2">
              <v-icon size="20" color="#6D4C41">mdi-numeric-2-circle-outline</v-icon>
              <span class="text-subtitle-1 font-weight-bold" style="color: #6D4C41">
                {{ $t('cutoff_impact_preview') || 'Cutoff Impact Preview' }}
              </span>
            </div>
            <v-chip
              v-if="previewData"
              size="small"
              color="#6D4C41"
              variant="tonal"
              class="font-weight-bold"
            >
              {{ previewData.grand_articles_count }} {{ $t('articles') || 'Articles' }}
            </v-chip>
          </div>

          <!-- Empty placeholder if no warehouses selected -->
          <div
            v-if="!selectedWarehouseIds.length"
            class="d-flex flex-column align-center justify-center flex-grow-1 text-center pa-6"
            style="opacity: 0.65; min-height: 280px;"
          >
            <v-icon size="64" color="#6D4C41" class="mb-3">mdi-warehouse</v-icon>
            <div class="text-subtitle-1 font-weight-bold" style="color: #6D4C41">
              {{ $t('no_warehouse_selected') || 'No Warehouses Selected' }}
            </div>
            <div class="text-caption text-medium-emphasis" style="max-width: 340px;">
              {{ $t('select_warehouses_prompt') || 'Select one or more warehouses and a date on the left to see which articles and balances will be zeroed out.' }}
            </div>
          </div>

          <!-- Loading state -->
          <div
            v-else-if="loadingPreview"
            class="d-flex flex-column align-center justify-center flex-grow-1 pa-8"
          >
            <v-progress-circular indeterminate color="#6D4C41" size="48" class="mb-3"></v-progress-circular>
            <div class="text-caption font-weight-medium" style="color: #6D4C41">
              {{ $t('calculating_cutoff_stock') || 'Calculating stock balances up to cutoff date...' }}
            </div>
          </div>

          <!-- Preview Content -->
          <div v-else-if="previewData" class="flex-grow-1 d-flex flex-column">
            <!-- Summary KPI Badges -->
            <v-row class="ma-0 mb-3 ga-2">
              <v-col class="pa-2 kpi-mini-card rounded-lg">
                <div class="text-caption text-medium-emphasis">{{ $t('warehouses') || 'Warehouses' }}</div>
                <div class="text-h6 font-weight-bold" style="color: #6D4C41">
                  {{ previewData.warehouses.length }}
                </div>
              </v-col>
              <v-col class="pa-2 kpi-mini-card rounded-lg">
                <div class="text-caption text-medium-emphasis">{{ $t('total_weight_to_clear') || 'Weight to Clear' }}</div>
                <div class="text-h6 font-weight-bold" style="color: #6D4C41">
                  {{ main.formatDisplayNumber(previewData.grand_total_weight) }} Kg
                </div>
              </v-col>
              <v-col class="pa-2 kpi-mini-card rounded-lg">
                <div class="text-caption text-medium-emphasis">{{ $t('total_value_to_clear') || 'Value to Clear' }}</div>
                <div class="text-h6 font-weight-bold text-success">
                  {{ main.formatDisplayNumber(previewData.grand_total_price) }} {{ main.currency }}
                </div>
              </v-col>
            </v-row>

            <!-- Warehouse Accordions -->
            <div class="preview-warehouses-list flex-grow-1 overflow-y-auto" style="max-height: 380px;">
              <v-expansion-panels variant="accordion" class="init-stock-panels">
                <v-expansion-panel
                  v-for="wh in previewData.warehouses"
                  :key="'wh-prev-' + wh.warehouse_id"
                  class="mb-2 rounded-lg border"
                >
                  <v-expansion-panel-title class="py-2 px-3">
                    <div class="d-flex align-center justify-space-between w-100 pr-2">
                      <div class="d-flex align-center ga-2">
                        <v-icon size="18" color="#6D4C41">mdi-warehouse</v-icon>
                        <span class="font-weight-bold text-body-2">{{ wh.warehouse_name }}</span>
                      </div>
                      <div class="d-flex align-center ga-3 text-caption">
                        <span style="color: #6D4C41" class="font-weight-bold">
                          {{ main.formatDisplayNumber(wh.total_weight) }} Kg
                        </span>
                        <span class="text-success font-weight-bold">
                          {{ main.formatDisplayNumber(wh.total_price) }} {{ main.currency }}
                        </span>
                        <v-chip size="x-small" color="#6D4C41" variant="tonal">
                          {{ wh.articles_count }} {{ $t('articles') || 'articles' }}
                        </v-chip>
                      </div>
                    </div>
                  </v-expansion-panel-title>
                  <v-expansion-panel-text class="pa-0">
                    <v-table density="compact" class="preview-articles-table">
                      <thead>
                        <tr>
                          <th>{{ $t('article') || 'Article' }}</th>
                          <th class="text-right">{{ $t('packages') || 'Packages' }}</th>
                          <th class="text-right">{{ $t('remaining_weight') || 'Weight' }} (Kg)</th>
                          <th class="text-right">{{ $t('value') || 'Value' }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(art, aIdx) in wh.articles" :key="'art-' + aIdx">
                          <td class="font-weight-medium">
                            <span class="text-body-2">{{ art.article_name }}</span>
                            <span class="text-caption text-medium-emphasis ml-1">({{ art.stockable_type }})</span>
                          </td>
                          <td class="text-right">{{ art.package_count || 0 }}</td>
                          <td class="text-right font-weight-bold" style="color: #6D4C41">
                            {{ main.formatDisplayNumber(art.remaining_weight) }}
                          </td>
                          <td class="text-right text-success font-weight-medium">
                            {{ main.formatDisplayNumber(art.total_price) }}
                          </td>
                        </tr>
                        <tr v-if="!wh.articles.length">
                          <td colspan="4" class="text-center text-medium-emphasis py-3 text-caption">
                            {{ $t('no_active_stock_in_warehouse') || 'No active stock found in this warehouse before the cutoff date.' }}
                          </td>
                        </tr>
                      </tbody>
                    </v-table>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- SECTION: Stock Reset Checkpoints History Ledger -->
    <v-card class="init-stock-card mt-4 pa-4" elevation="0" rounded="xl">
      <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
        <div class="d-flex align-center ga-2">
          <v-icon size="20" color="#6D4C41">mdi-history</v-icon>
          <span class="text-subtitle-1 font-weight-bold" style="color: #6D4C41">
            {{ $t('stock_reset_checkpoints_history') || 'Stock Initialization Checkpoints History' }}
          </span>
          <v-chip size="small" color="#6D4C41" variant="tonal" class="ml-1 font-weight-bold">
            {{ stockResets.length }}
          </v-chip>
        </div>
        <div class="d-flex align-center ga-2">
          <v-btn
            size="small"
            variant="tonal"
            color="#6D4C41"
            rounded
            prepend-icon="mdi-refresh"
            :loading="loadingResets"
            @click="fetchStockResets"
          >
            {{ $t('refresh') || 'Refresh' }}
          </v-btn>
        </div>
      </div>

      <!-- History Table -->
      <v-table density="comfortable" class="history-table rounded-lg border">
        <thead>
          <tr>
            <th>{{ $t('cutoff_date') || 'Cutoff Date' }}</th>
            <th>{{ $t('warehouse') || 'Warehouse' }}</th>
            <th class="text-right">{{ $t('cleared_weight') || 'Cleared Weight' }} (Kg)</th>
            <th class="text-right">{{ $t('cleared_value') || 'Cleared Value' }}</th>
            <th class="text-center">{{ $t('articles') || 'Articles' }}</th>
            <th>{{ $t('reason_audit_note') || 'Reason / Audit Note' }}</th>
            <th>{{ $t('created_by') || 'Created By' }}</th>
            <th class="text-center">{{ $t('actions') || 'Actions' }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="reset in stockResets" :key="'reset-' + reset.id">
            <td class="font-weight-bold text-body-2">
              <v-icon size="16" color="#6D4C41" class="mr-1">mdi-calendar-check</v-icon>
              {{ formatDate(reset.reset_date) }}
            </td>
            <td>
              <v-chip size="small" color="#6D4C41" variant="tonal" class="font-weight-medium">
                <v-icon size="13" class="mr-1">mdi-warehouse</v-icon>
                {{ reset.warehouse?.name || ('#' + reset.warehouse_id) }}
              </v-chip>
            </td>
            <td class="text-right font-weight-bold" style="color: #6D4C41">
              {{ main.formatDisplayNumber(reset.total_weight_cleared) }}
            </td>
            <td class="text-right font-weight-medium text-success">
              {{ main.formatDisplayNumber(reset.total_price_cleared) }} {{ main.currency }}
            </td>
            <td class="text-center">
              <v-chip size="x-small" variant="outlined" color="#6D4C41">
                {{ reset.articles_count }}
              </v-chip>
            </td>
            <td class="text-caption text-medium-emphasis" style="max-width: 220px;">
              {{ reset.reason || '-' }}
            </td>
            <td class="text-caption">
              <span class="font-weight-medium">{{ reset.creator?.name || '-' }}</span>
              <div class="text-medium-emphasis" style="font-size: 0.72rem;">{{ formatDateTime(reset.created_at) }}</div>
            </td>
            <td class="text-center">
              <v-btn
                size="small"
                variant="tonal"
                color="warning"
                rounded
                prepend-icon="mdi-undo-variant"
                @click="openUndoDialog(reset)"
              >
                {{ $t('undo_checkpoint') || 'Undo' }}
              </v-btn>
            </td>
          </tr>
          <tr v-if="!stockResets.length && !loadingResets">
            <td colspan="8" class="text-center text-medium-emphasis py-6">
              <v-icon size="32" color="#6D4C41" class="mb-1 d-block mx-auto" style="opacity: 0.5;">mdi-history</v-icon>
              {{ $t('no_stock_resets_recorded') || 'No stock reset checkpoints recorded yet.' }}
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <!-- CONFIRMATION DIALOG FOR STOCK RESET -->
    <v-dialog v-model="confirmDialog" max-width="540" persistent>
      <v-card class="rounded-xl pa-2">
        <v-card-title class="d-flex align-center ga-2 pa-4 pb-2">
          <v-icon color="#6D4C41" size="26">mdi-alert-decagram-outline</v-icon>
          <span class="font-weight-bold text-h6" style="color: #6D4C41">
            {{ $t('confirm_stock_initialization') || 'Confirm Stock Initialization to 0' }}
          </span>
        </v-card-title>
        <v-card-text class="pa-4 pt-1">
          <p class="text-body-2 mb-3">
            {{ $t('confirm_stock_initialization_msg') || 'Are you sure you want to initialize all stock in the selected warehouses to 0 as of the specified date?' }}
          </p>
          <v-divider class="mb-3"></v-divider>
          <div class="d-flex flex-column ga-2 text-caption">
            <div class="d-flex justify-space-between">
              <span class="text-medium-emphasis">{{ $t('target_warehouses') }}:</span>
              <strong>{{ getSelectedWarehouseNames() }}</strong>
            </div>
            <div class="d-flex justify-space-between">
              <span class="text-medium-emphasis">{{ $t('cutoff_date') }}:</span>
              <strong>{{ resetDate }}</strong>
            </div>
            <div v-if="previewData" class="d-flex justify-space-between">
              <span class="text-medium-emphasis">{{ $t('total_weight_cleared') }}:</span>
              <strong style="color: #6D4C41">{{ main.formatDisplayNumber(previewData.grand_total_weight) }} Kg</strong>
            </div>
            <div v-if="previewData" class="d-flex justify-space-between">
              <span class="text-medium-emphasis">{{ $t('total_value_cleared') }}:</span>
              <strong class="text-success">{{ main.formatDisplayNumber(previewData.grand_total_price) }} {{ main.currency }}</strong>
            </div>
          </div>
          <v-alert
            density="compact"
            variant="tonal"
            color="warning"
            rounded="lg"
            class="mt-3 text-caption"
            icon="mdi-information-outline"
          >
            {{ $t('stock_checkpoint_bleed_rule_notice') || 'Stock prior to this date remains intact. Any new vouchers dated prior to this cutoff date will contribute to historical stock before the cutoff, but will NOT be added to stock on or after this cutoff date.' }}
          </v-alert>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn
            variant="tonal"
            color="red-lighten-1"
            rounded
            :disabled="submittingReset"
            @click="confirmDialog = false"
          >
            {{ $t('cancel') || 'Cancel' }}
          </v-btn>
          <v-btn
            variant="elevated"
            color="#6D4C41"
            class="text-white"
            rounded
            :loading="submittingReset"
            @click="executeStockReset"
          >
            {{ $t('confirm_and_execute') || 'Confirm & Initialize' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- CONFIRMATION DIALOG FOR UNDO RESET -->
    <v-dialog v-model="undoDialog" max-width="500" persistent>
      <v-card class="rounded-xl pa-2">
        <v-card-title class="d-flex align-center ga-2 pa-4 pb-2">
          <v-icon color="warning" size="26">mdi-undo-variant</v-icon>
          <span class="font-weight-bold text-h6">
            {{ $t('undo_stock_reset_title') || 'Undo Stock Initialization' }}
          </span>
        </v-card-title>
        <v-card-text class="pa-4 pt-1">
          <p class="text-body-2 mb-3">
            {{ $t('undo_stock_reset_confirm') || 'Are you sure you want to revert this stock checkpoint? All balances closed by this checkpoint will have their remaining stock restored.' }}
          </p>
          <div v-if="resetToUndo" class="text-caption d-flex flex-column ga-1">
            <div>{{ $t('warehouse') }}: <strong>{{ resetToUndo.warehouse?.name }}</strong></div>
            <div>{{ $t('cutoff_date') }}: <strong>{{ formatDate(resetToUndo.reset_date) }}</strong></div>
            <div>{{ $t('weight') }}: <strong>{{ main.formatDisplayNumber(resetToUndo.total_weight_cleared) }} Kg</strong></div>
          </div>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn
            variant="tonal"
            color="red-lighten-1"
            rounded
            :disabled="undoingReset"
            @click="undoDialog = false"
          >
            {{ $t('cancel') || 'Cancel' }}
          </v-btn>
          <v-btn
            variant="elevated"
            color="warning"
            rounded
            :loading="undoingReset"
            @click="executeUndoReset"
          >
            {{ $t('confirm_undo') || 'Revert & Restore Stock' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- RULE 5 SUMMARY SNACKBAR WITH PROGRESS BAR -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="8000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="360"
      max-width="440"
      @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
    >
      <v-progress-linear
        :model-value="snackbarProgress"
        color="#6D4C41"
        height="3"
        class="mb-2"
        rounded
      ></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold" style="color: #6D4C41">
          {{ summarySnackbar.title }}
        </span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.date">
          <v-icon size="14" color="#6D4C41" class="mr-1">mdi-calendar</v-icon>
          <span class="text-medium-emphasis">{{ $t('cutoff_date') }}:</span>
          <strong>{{ summarySnackbar.date }}</strong>
        </div>
        <div v-if="summarySnackbar.warehouseCount">
          <v-icon size="14" color="#6D4C41" class="mr-1">mdi-warehouse</v-icon>
          <span class="text-medium-emphasis">{{ $t('warehouses') }}:</span>
          <strong>{{ summarySnackbar.warehouseCount }}</strong>
        </div>
        <div v-if="summarySnackbar.totalWeight">
          <v-icon size="14" color="#6D4C41" class="mr-1">mdi-weight-kilogram</v-icon>
          <span class="text-medium-emphasis">{{ $t('cleared_weight') }}:</span>
          <strong style="color: #6D4C41">{{ summarySnackbar.totalWeight }} Kg</strong>
        </div>
        <div v-if="summarySnackbar.totalPrice">
          <v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon>
          <span class="text-medium-emphasis">{{ $t('cleared_value') }}:</span>
          <strong class="text-success">{{ summarySnackbar.totalPrice }} {{ main.currency }}</strong>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script>
import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import mitt from '@/plugins/mitt';

export default {
  name: 'InitWarehouseStock',
  data() {
    const today = new Date().toISOString().substring(0, 10);
    return {
      selectedWarehouseIds: [],
      resetDate: today,
      resetReason: '',
      warehouses: [],
      loadingWarehouses: false,
      loadingPreview: false,
      previewData: null,
      submittingReset: false,
      confirmDialog: false,
      undoDialog: false,
      resetToUndo: null,
      undoingReset: false,
      stockResets: [],
      loadingResets: false,
      summarySnackbar: {
        show: false,
        title: '',
        date: '',
        warehouseCount: 0,
        totalWeight: '0',
        totalPrice: '0',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    warehouseOptions() {
      return this.warehouses.map(w => ({
        id: w.id,
        name: w.name,
      }));
    },
    canPreview() {
      return this.selectedWarehouseIds.length > 0 && !!this.resetDate;
    },
    canSubmit() {
      return this.selectedWarehouseIds.length > 0 && !!this.resetDate && !this.submittingReset;
    },
    dateModel: {
      get() {
        if (!this.resetDate) return new Date();
        return new Date(this.resetDate + 'T00:00:00');
      },
      set(val) {
        if (val instanceof Date) {
          const y = val.getFullYear();
          const m = String(val.getMonth() + 1).padStart(2, '0');
          const d = String(val.getDate()).padStart(2, '0');
          this.resetDate = `${y}-${m}-${d}`;
        } else {
          this.resetDate = val;
        }
        this.onConfigChanged();
      },
    },
  },
  mounted() {
    this.fetchWarehouses();
    this.fetchStockResets();
    mitt.on('model-changed', this.onModelChanged);
  },
  beforeUnmount() {
    mitt.off('model-changed', this.onModelChanged);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  methods: {
    async fetchWarehouses() {
      this.loadingWarehouses = true;
      try {
        const r = await axios.get('/api/warehouses');
        this.warehouses = r.data.warehouses || [];
      } catch (e) {
        console.error('Error fetching warehouses:', e);
      } finally {
        this.loadingWarehouses = false;
      }
    },

    async fetchPreview() {
      if (!this.canPreview) return;
      this.loadingPreview = true;
      try {
        const r = await axios.get('/api/inventory/preview-reset', {
          params: {
            warehouse_ids: this.selectedWarehouseIds,
            date: this.resetDate,
          },
        });
        this.previewData = r.data;
      } catch (e) {
        console.error('Error fetching reset preview:', e);
      } finally {
        this.loadingPreview = false;
      }
    },

    onConfigChanged() {
      this.previewData = null;
      if (this.canPreview) {
        this.fetchPreview();
      }
    },

    openConfirmDialog() {
      if (!this.canSubmit) return;
      if (!this.previewData) {
        this.fetchPreview().then(() => {
          this.confirmDialog = true;
        });
      } else {
        this.confirmDialog = true;
      }
    },

    getSelectedWarehouseNames() {
      const selected = this.warehouses.filter(w => this.selectedWarehouseIds.includes(w.id));
      return selected.map(w => w.name).join(', ') || '-';
    },

    async executeStockReset() {
      this.submittingReset = true;
      try {
        const r = await axios.post('/api/inventory/reset-stock', {
          warehouse_ids: this.selectedWarehouseIds,
          date: this.resetDate,
          reason: this.resetReason,
        });

        this.confirmDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('stock_initialized_success') || 'Stock Initialized to 0 Successfully',
          date: this.resetDate,
          warehouseCount: this.selectedWarehouseIds.length,
          totalWeight: this.main.formatDisplayNumber(r.data.total_weight_cleared),
          totalPrice: this.main.formatDisplayNumber(r.data.total_price_cleared),
        });

        this.selectedWarehouseIds = [];
        this.resetReason = '';
        this.previewData = null;
        this.fetchStockResets();

        // Broadcast to notify entire application
        mitt.emit('model-changed', { model: 'WarehouseBalance', action: 'updated', id: 0 });
        mitt.emit('model-changed', { model: 'Warehouse', action: 'updated', id: 0 });
        mitt.emit('model-changed', { model: 'WarehouseStockReset', action: 'created', id: 0 });
      } catch (e) {
        console.error('Error executing stock reset:', e);
        alert(e.response?.data?.message || 'Error executing stock initialization');
      } finally {
        this.submittingReset = false;
      }
    },

    async fetchStockResets() {
      this.loadingResets = true;
      try {
        const r = await axios.get('/api/inventory/stock-resets');
        this.stockResets = r.data.resets || [];
      } catch (e) {
        console.error('Error fetching stock resets history:', e);
      } finally {
        this.loadingResets = false;
      }
    },

    openUndoDialog(reset) {
      this.resetToUndo = reset;
      this.undoDialog = true;
    },

    async executeUndoReset() {
      if (!this.resetToUndo) return;
      this.undoingReset = true;
      try {
        await axios.post(`/api/inventory/stock-resets/${this.resetToUndo.id}/undo`);
        this.undoDialog = false;

        this.triggerSummarySnackbar({
          title: this.$t('stock_reset_undone_success') || 'Stock Checkpoint Reverted Successfully',
          date: this.formatDate(this.resetToUndo.reset_date),
          warehouseCount: 1,
          totalWeight: this.main.formatDisplayNumber(this.resetToUndo.total_weight_cleared),
          totalPrice: this.main.formatDisplayNumber(this.resetToUndo.total_price_cleared),
        });

        this.resetToUndo = null;
        this.fetchStockResets();

        mitt.emit('model-changed', { model: 'WarehouseBalance', action: 'updated', id: 0 });
        mitt.emit('model-changed', { model: 'Warehouse', action: 'updated', id: 0 });
      } catch (e) {
        console.error('Error undoing stock reset:', e);
        alert(e.response?.data?.message || 'Error reverting stock checkpoint');
      } finally {
        this.undoingReset = false;
      }
    },

    triggerSummarySnackbar({ title, date, warehouseCount, totalWeight, totalPrice }) {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.summarySnackbar.title = title;
      this.summarySnackbar.date = date;
      this.summarySnackbar.warehouseCount = warehouseCount;
      this.summarySnackbar.totalWeight = totalWeight;
      this.summarySnackbar.totalPrice = totalPrice;
      this.summarySnackbar.show = true;
      this.snackbarProgress = 100;
      const step = 100 / 80;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= step;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, 100);
    },

    onModelChanged(d) {
      if (['WarehouseStockReset', 'Warehouse', 'WarehouseBalance'].includes(d.model)) {
        this.fetchStockResets();
      }
    },

    formatDate(d) {
      if (!d) return '-';
      return String(d).substring(0, 10);
    },

    formatDateTime(d) {
      if (!d) return '-';
      return String(d).replace('T', ' ').substring(0, 16);
    },
  },
};
</script>

<style scoped>
.init-stock-container {
  max-width: 1400px;
  margin: 0 auto;
}

.init-stock-alert {
  border-left: 4px solid #6D4C41 !important;
}

.init-stock-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-surface), 1);
  transition: all 0.2s ease;
}

.kpi-mini-card {
  background: rgba(109, 76, 65, 0.06);
  border: 1px solid rgba(109, 76, 65, 0.15);
  text-align: center;
}

.preview-articles-table th {
  font-size: 0.76rem !important;
  font-weight: 700 !important;
  color: rgba(var(--v-theme-on-surface), 0.7) !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.history-table th {
  font-size: 0.78rem !important;
  font-weight: 700 !important;
  color: rgba(var(--v-theme-on-surface), 0.75) !important;
}

.date-picker-wrapper :deep(.vc-container) {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12) !important;
  border-radius: 12px !important;
}
</style>
