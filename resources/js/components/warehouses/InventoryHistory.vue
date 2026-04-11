<template>
  <div class="hist-page">
    <!-- ═══════════ HEADER ═══════════ -->
    <div class="hist-header">
      <div class="hist-header-glow"></div>
      <div class="d-flex align-center ga-4 mb-1">
        <div class="hist-icon-wrap">
          <v-icon size="28" color="white">mdi-history</v-icon>
        </div>
        <div>
          <h1 class="hist-title">{{ $t('adjustment_history') }}</h1>
          <p class="hist-subtitle">{{ $t('adjustment_history_subtitle') }}</p>
        </div>
      </div>
    </div>

    <!-- ═══════════ FILTERS ═══════════ -->
    <v-row class="mb-5" dense>
      <!-- Left: Date Range Calendar -->
      <v-col cols="12" md="3">
        <v-card class="hist-card" elevation="0">
          <v-card-text class="pa-3">
            <DatePicker
              :is-dark="main.is_dark"
              :max-date="new Date()"
              v-model.range="dateRange"
              mode="date"
              :locale="main.lang.short"
              expanded
            />
            <div class="text-center mt-3" role="group" aria-label="Date range">
              <v-chip color="primary" variant="tonal" size="small" class="mr-2">
                <v-icon start small>mdi-calendar-start</v-icon>
                {{ main.formateDate(dateRange.start) }}
              </v-chip>
              <v-chip color="indigo" variant="tonal" size="small" class="ml-2">
                {{ main.formateDate(dateRange.end) }}
                <v-icon end small>mdi-calendar-end</v-icon>
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Right: Filters + Table -->
      <v-col cols="12" md="9">
        <!-- Filter Bar -->
        <v-card class="hist-card mb-5" elevation="0">
          <v-card-text class="py-4 px-5">
            <div class="d-flex align-center ga-4 flex-wrap">
              <v-autocomplete
                v-model="filters.warehouse_id"
                :items="warehouses"
                item-value="id"
                item-title="name"
                :label="$t('warehouse')"
                density="compact"
                variant="outlined"
                rounded
                hide-details
                clearable
                color="info"
                prepend-inner-icon="mdi-warehouse"
                class="text-info"
                style="max-width: 260px; min-width: 220px;"
                @update:model-value="fetchAdjustments"
              ></v-autocomplete>

              <v-text-field
                v-model="filters.search"
                :label="$t('search_reason')"
                density="compact"
                variant="outlined"
                rounded
                hide-details
                clearable
                color="info"
                prepend-inner-icon="mdi-magnify"
                class="flex-grow-1 text-info"
                style="min-width: 200px;"
                @keyup.enter="fetchAdjustments"
                @click:clear="filters.search = ''; fetchAdjustments()"
              ></v-text-field>

              <v-btn color="info" variant="tonal" rounded icon="mdi-refresh" size="small"
                @click="fetchAdjustments" :loading="loading">
              </v-btn>
            </div>
          </v-card-text>
        </v-card>

        <!-- Skeleton Loading -->
        <div v-if="loading">
          <v-card class="hist-card hist-table-card mb-4" elevation="0">
            <v-card-text class="pa-0">
              <div class="pa-5 pb-0">
                <v-skeleton-loader type="heading" class="mb-3"></v-skeleton-loader>
              </div>
              <v-skeleton-loader type="table-heading, table-row-divider@6, table-tfoot" class="rounded-lg"></v-skeleton-loader>
            </v-card-text>
          </v-card>
        </div>

        <!-- Empty State -->
        <div v-else-if="adjustments.length === 0" class="hist-empty-state">
          <v-card class="hist-card pa-10 text-center" elevation="0">
            <div class="hist-empty-icon">
              <v-icon size="56" color="info">mdi-clipboard-text-off-outline</v-icon>
            </div>
            <p class="text-h6 text-medium-emphasis mt-4">{{ $t('no_adjustments_found') }}</p>
            <p class="text-body-2 text-medium-emphasis">{{ $t('adjustment_history_subtitle') }}</p>
          </v-card>
        </div>

        <!-- Adjustments Table -->
        <v-card v-else class="hist-card hist-table-card" elevation="0">
          <v-card-text class="pa-0">
            <v-table density="comfortable" class="hist-table">
              <thead>
                <tr>
                  <th>{{ $t('date') }}</th>
                  <th>{{ $t('warehouse') }}</th>
                  <th>{{ $t('user') }}</th>
                  <th>{{ $t('reason') }}</th>
                  <th class="text-center">{{ $t('operations') }}</th>
                  <th class="text-right">{{ $t('weight_in') }}</th>
                  <th class="text-right">{{ $t('weight_out') }}</th>
                  <th class="text-center">{{ $t('net_change') }}</th>
                  <th class="text-center">{{ $t('actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="adj in adjustments" :key="adj.id" class="hist-row">
                  <td class="py-3">
                    <v-chip size="small" variant="tonal" color="primary" prepend-icon="mdi-calendar" class="font-weight-medium">
                      {{ formatDate(adj.date) }}
                    </v-chip>
                  </td>
                  <td class="py-3">
                    <div class="d-flex align-center ga-2">
                      <v-avatar size="28" color="primary" variant="tonal" rounded="lg">
                        <v-icon size="16">mdi-warehouse</v-icon>
                      </v-avatar>
                      <span class="font-weight-medium text-body-2">{{ adj.warehouse?.name || 'N/A' }}</span>
                    </div>
                  </td>
                  <td class="py-3">
                    <div class="d-flex align-center ga-2">
                      <v-avatar size="24" color="info" variant="tonal">
                        <v-icon size="14">mdi-account</v-icon>
                      </v-avatar>
                      <span class="text-body-2">{{ adj.user?.name || 'System' }}</span>
                    </div>
                  </td>
                  <td class="py-3">
                    <span class="text-body-2 text-medium-emphasis" :title="adj.reason">
                      {{ adj.reason ? truncate(adj.reason, 30) : '—' }}
                    </span>
                  </td>
                  <td class="text-center py-3">
                    <v-chip size="x-small" variant="tonal" color="default" class="font-weight-bold">
                      {{ adj.warehouse_stockable_tracking_number_operations_count }}
                    </v-chip>
                  </td>
                  <td class="text-right py-3">
                    <span v-if="adj.total_weight_in > 0" class="text-success font-weight-bold text-body-2">
                      +{{ fmt(adj.total_weight_in) }}
                    </span>
                    <span v-else class="text-medium-emphasis text-body-2">—</span>
                  </td>
                  <td class="text-right py-3">
                    <span v-if="adj.total_weight_out > 0" class="text-error font-weight-bold text-body-2">
                      -{{ fmt(adj.total_weight_out) }}
                    </span>
                    <span v-else class="text-medium-emphasis text-body-2">—</span>
                  </td>
                  <td class="text-center py-3">
                    <v-chip
                      :color="adj.net_weight_change > 0 ? 'success' : adj.net_weight_change < 0 ? 'error' : 'default'"
                      variant="tonal" size="x-small" class="font-weight-bold">
                      {{ adj.net_weight_change > 0 ? '+' : '' }}{{ fmt(adj.net_weight_change) }} KG
                    </v-chip>
                  </td>
                  <td class="text-center py-3">
                    <div class="d-flex justify-center ga-2">
                      <v-btn icon size="x-small" variant="tonal" color="primary" @click.stop="openDetail(adj)">
                        <v-icon size="14">mdi-eye</v-icon>
                        <v-tooltip activator="parent" location="top">{{ $t('view_details') }}</v-tooltip>
                      </v-btn>
                      <v-btn icon size="x-small" variant="tonal" color="warning" @click.stop="openEdit(adj)">
                        <v-icon size="14">mdi-pencil</v-icon>
                        <v-tooltip activator="parent" location="top">{{ $t('edit_adjustment') }}</v-tooltip>
                      </v-btn>
                      <v-btn icon size="x-small" variant="tonal" color="error" @click.stop="openDeleteConfirm(adj)">
                        <v-icon size="14">mdi-delete</v-icon>
                        <v-tooltip activator="parent" location="top">{{ $t('delete_adjustment') }}</v-tooltip>
                      </v-btn>
                    </div>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="d-flex justify-center py-4">
            <v-pagination
              v-model="pagination.current_page"
              :length="pagination.last_page"
              :total-visible="5"
              rounded="lg"
              color="primary"
              density="comfortable"
              @update:model-value="fetchAdjustments"
            ></v-pagination>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- ═══════════ DETAIL DIALOG ═══════════ -->
    <v-dialog v-model="detailDialog" max-width="900" scrollable>
      <v-card rounded="xl" class="hist-dialog-card">
        <v-card-title class="d-flex align-center pt-5 px-5">
          <v-icon start color="primary">mdi-clipboard-text-clock</v-icon>
          {{ $t('adjustment_details') }}
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="detailDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text v-if="selectedAdjustment" class="px-5">
          <div class="d-flex flex-wrap ga-3 mb-5">
            <v-chip color="primary" variant="tonal" prepend-icon="mdi-calendar" size="default">
              {{ formatDate(selectedAdjustment.date) }}
            </v-chip>
            <v-chip color="info" variant="tonal" prepend-icon="mdi-warehouse" size="default">
              {{ selectedAdjustment.warehouse?.name || 'N/A' }}
            </v-chip>
            <v-chip color="default" variant="tonal" prepend-icon="mdi-account" size="default">
              {{ selectedAdjustment.user?.name || 'System' }}
            </v-chip>
            <v-chip v-if="selectedAdjustment.reason" color="warning" variant="tonal"
              prepend-icon="mdi-text-box-outline" size="default">
              {{ selectedAdjustment.reason }}
            </v-chip>
          </div>

          <div class="d-flex flex-wrap ga-3 mb-5">
            <v-card variant="tonal" color="default" class="flex-grow-1 pa-3" rounded="lg" min-width="140">
              <div class="text-caption text-medium-emphasis">{{ $t('total_operations') }}</div>
              <div class="text-h6 font-weight-bold">{{ detailOperations.length }}</div>
            </v-card>
            <v-card variant="tonal" color="success" class="flex-grow-1 pa-3" rounded="lg" min-width="140">
              <div class="text-caption">{{ $t('weight_in') }}</div>
              <div class="text-h6 font-weight-bold">
                +{{ fmt(detailOperations.filter(o => o.type_operation === 'in').reduce((s,o) => s + o.total_weight, 0)) }} KG
              </div>
            </v-card>
            <v-card variant="tonal" color="error" class="flex-grow-1 pa-3" rounded="lg" min-width="140">
              <div class="text-caption">{{ $t('weight_out') }}</div>
              <div class="text-h6 font-weight-bold">
                -{{ fmt(detailOperations.filter(o => o.type_operation === 'out').reduce((s,o) => s + o.total_weight, 0)) }} KG
              </div>
            </v-card>
          </div>

          <div v-if="detailLoading" class="text-center py-6">
            <v-progress-circular indeterminate color="primary" size="36"></v-progress-circular>
          </div>

          <v-table v-else density="comfortable" class="hist-detail-table">
            <thead>
              <tr>
                <th>{{ $t('article') }}</th>
                <th>{{ $t('tracking_number') }}</th>
                <th class="text-center">{{ $t('type') }}</th>
                <th class="text-right">{{ $t('weight') }} (KG)</th>
                <th class="text-right">{{ $t('price') }} (MAD)</th>
                <th class="text-right">{{ $t('unit_price') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="op in detailOperations" :key="op.id">
                <td>
                  <div class="d-flex align-center gap-2">
                    <v-avatar :color="typeColor(op.article_type)" variant="tonal" size="28" rounded="lg">
                      <v-icon size="14">{{ typeIcon(op.article_type) }}</v-icon>
                    </v-avatar>
                    <span class="font-weight-medium text-body-2">{{ getArticleName(op) }}</span>
                  </div>
                </td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <v-chip v-for="(num, idx) in (op.tracking_numbers || [])" :key="idx"
                      size="x-small" color="info" variant="outlined" class="font-weight-medium">
                      {{ num }}
                    </v-chip>
                    <span v-if="!op.tracking_numbers || op.tracking_numbers.length === 0" class="text-medium-emphasis">—</span>
                  </div>
                </td>
                <td class="text-center">
                  <v-chip :color="op.type_operation === 'in' ? 'success' : 'error'"
                    variant="tonal" size="small" class="font-weight-bold">
                    <v-icon start size="14">{{ op.type_operation === 'in' ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold' }}</v-icon>
                    {{ op.type_operation === 'in' ? $t('stock_in') : $t('stock_out') }}
                  </v-chip>
                </td>
                <td class="text-right font-weight-bold">
                  <span :class="op.type_operation === 'in' ? 'text-success' : 'text-error'">
                    {{ op.type_operation === 'in' ? '+' : '-' }}{{ fmt(op.total_weight) }}
                  </span>
                </td>
                <td class="text-right">{{ fmt(op.total_price) }}</td>
                <td class="text-right">{{ fmt(op.unit_price) }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- ═══════════ EDIT DIALOG ═══════════ -->
    <v-dialog v-model="editDialog" max-width="520" persistent>
      <v-card rounded="xl" class="hist-dialog-card">
        <v-card-title class="d-flex align-center pt-5 px-5">
          <v-icon start color="warning">mdi-pencil</v-icon>
          {{ $t('edit_adjustment') }}
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="editDialog = false" :disabled="editLoading">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text class="px-5 pt-4">
          <v-chip color="info" variant="tonal" prepend-icon="mdi-warehouse" class="mb-4" size="default">
            {{ editForm.warehouseName }}
          </v-chip>

          <v-text-field
            v-model="editForm.date"
            :label="$t('date')"
            type="date"
            density="compact"
            variant="outlined"
            rounded
            color="info"
            prepend-inner-icon="mdi-calendar"
            :max="todayDate"
            class="mb-4 text-info"
          ></v-text-field>

          <v-textarea
            v-model="editForm.reason"
            :label="$t('reason')"
            density="compact"
            variant="outlined"
            rounded
            color="info"
            prepend-inner-icon="mdi-text-box-outline"
            rows="3"
            auto-grow
            counter="1000"
            maxlength="1000"
            class="text-info"
          ></v-textarea>
        </v-card-text>

        <v-card-actions class="px-5 pb-5">
          <v-spacer></v-spacer>
          <v-btn variant="text" rounded @click="editDialog = false" :disabled="editLoading">
            {{ $t('cancel') }}
          </v-btn>
          <v-btn color="info" variant="tonal" rounded @click="submitEdit" :loading="editLoading"
            prepend-icon="mdi-content-save">
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ═══════════ DELETE DIALOG ═══════════ -->
    <v-dialog v-model="deleteDialog" max-width="480" persistent>
      <v-card rounded="xl" class="hist-dialog-card">
        <v-card-title class="d-flex align-center pt-5 px-5">
          <v-icon start color="error">mdi-alert-circle</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>

        <v-card-text class="px-5 pt-3" v-if="deleteTarget">
          <v-alert type="warning" variant="tonal" rounded="lg" class="mb-4" density="compact">
            {{ $t('delete_adjustment_warning') }}
          </v-alert>

          <div class="d-flex flex-wrap ga-2 mb-3">
            <v-chip color="primary" variant="tonal" prepend-icon="mdi-calendar" size="small">
              {{ formatDate(deleteTarget.date) }}
            </v-chip>
            <v-chip color="info" variant="tonal" prepend-icon="mdi-warehouse" size="small">
              {{ deleteTarget.warehouse?.name || 'N/A' }}
            </v-chip>
            <v-chip color="default" variant="tonal" size="small">
              {{ deleteTarget.warehouse_stockable_tracking_number_operations_count }} {{ $t('operations') }}
            </v-chip>
          </div>

          <p v-if="deleteTarget.reason" class="text-body-2 text-medium-emphasis">
            <strong>{{ $t('reason') }}:</strong> {{ deleteTarget.reason }}
          </p>
        </v-card-text>

        <v-card-actions class="px-5 pb-5">
          <v-spacer></v-spacer>
          <v-btn variant="text" rounded="lg" @click="deleteDialog = false" :disabled="deleteLoading">
            {{ $t('cancel') }}
          </v-btn>
          <v-btn color="error" variant="flat" rounded="lg" @click="submitDelete" :loading="deleteLoading"
            prepend-icon="mdi-delete">
            {{ $t('delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000" rounded="lg" location="bottom">
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">{{ $t('close') }}</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script>
import axios from 'axios';
import { useMain } from '@/stores/Main';

export default {
  name: 'InventoryHistory',
  data() {
    return {
      loading: false,
      adjustments: [],
      warehouses: [],
      dateRange: {
        start: new Date(new Date().getFullYear(), new Date().getMonth() - 3, 1),
        end: new Date(),
      },
      filters: {
        warehouse_id: null,
        search: '',
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0,
      },
      detailDialog: false,
      detailLoading: false,
      selectedAdjustment: null,
      detailOperations: [],
      editDialog: false,
      editLoading: false,
      editForm: { id: null, date: '', reason: '', warehouseName: '' },
      deleteDialog: false,
      deleteLoading: false,
      deleteTarget: null,
      snackbar: { show: false, message: '', color: 'success' },
    };
  },
  computed: {
    main() { return useMain(); },
    langCode() { return this.$i18n?.locale || 'fr'; },
    todayDate() { return new Date().toISOString().split('T')[0]; },
  },
  watch: {
    dateRange: {
      handler() { this.fetchAdjustments(); },
      deep: true,
    },
  },
  mounted() {
    this.fetchWarehouses();
    this.fetchAdjustments();
  },
  methods: {
    fmt(num) {
      if (!num && num !== 0) return '0,00';
      return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    },
    formatDate(d) {
      if (!d) return 'N/A';
      return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
    },
    truncate(str, len) { return str.length > len ? str.substring(0, len) + '…' : str; },
    typeColor(type) {
      if (type === 'FishType') return 'blue';
      if (type === 'GuanoType') return 'brown';
      if (type === 'PackageType') return 'teal';
      return 'grey';
    },
    typeIcon(type) {
      if (type === 'FishType') return 'mdi-fish';
      if (type === 'GuanoType') return 'mdi-leaf';
      if (type === 'PackageType') return 'mdi-package-variant';
      return 'mdi-help-circle';
    },
    getArticleName(op) {
      const lang = this.langCode;
      if (lang === 'en') return op.article_name_en || op.article_name;
      if (lang === 'es') return op.article_name_es || op.article_name;
      return op.article_name;
    },
    showSnack(message, color = 'success') {
      this.snackbar = { show: true, message, color };
    },
    toYMD(d) {
      if (!d) return '';
      const dt = new Date(d);
      return `${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}-${String(dt.getDate()).padStart(2,'0')}`;
    },

    async fetchWarehouses() {
      try {
        const res = await axios.get('/api/warehouses');
        this.warehouses = res.data.warehouses || res.data || [];
      } catch (e) { console.error(e); }
    },

    async fetchAdjustments() {
      this.loading = true;
      try {
        const params = { page: this.pagination.current_page };
        if (this.filters.warehouse_id) params.warehouse_id = this.filters.warehouse_id;
        if (this.dateRange.start) params.date_from = this.toYMD(this.dateRange.start);
        if (this.dateRange.end) params.date_to = this.toYMD(this.dateRange.end);
        if (this.filters.search) params.search = this.filters.search;

        const res = await axios.get('/api/inventory/adjustments', { params });
        this.adjustments = res.data.data || [];
        this.pagination.current_page = res.data.current_page;
        this.pagination.last_page = res.data.last_page;
        this.pagination.total = res.data.total;
      } catch (e) { console.error(e); }
      finally { this.loading = false; }
    },

    async openDetail(adj) {
      this.selectedAdjustment = adj;
      this.detailOperations = [];
      this.detailDialog = true;
      this.detailLoading = true;
      try {
        const res = await axios.get(`/api/inventory/adjustments/${adj.id}`);
        this.selectedAdjustment = res.data.adjustment;
        this.detailOperations = res.data.operations || [];
      } catch (e) { console.error(e); }
      finally { this.detailLoading = false; }
    },

    openEdit(adj) {
      this.editForm = {
        id: adj.id,
        date: adj.date || '',
        reason: adj.reason || '',
        warehouseName: adj.warehouse?.name || 'N/A',
      };
      this.editDialog = true;
    },

    async submitEdit() {
      this.editLoading = true;
      try {
        await axios.put(`/api/inventory/adjustments/${this.editForm.id}`, {
          date: this.editForm.date,
          reason: this.editForm.reason,
        });
        this.editDialog = false;
        this.showSnack(this.$t('adjustment_updated_success'));
        this.fetchAdjustments();
      } catch (e) {
        console.error(e);
        this.showSnack(e.response?.data?.message || 'Error', 'error');
      }
      finally { this.editLoading = false; }
    },

    openDeleteConfirm(adj) {
      this.deleteTarget = adj;
      this.deleteDialog = true;
    },

    async submitDelete() {
      if (!this.deleteTarget) return;
      this.deleteLoading = true;
      try {
        await axios.delete(`/api/inventory/adjustments/${this.deleteTarget.id}`);
        this.deleteDialog = false;
        this.deleteTarget = null;
        this.showSnack(this.$t('adjustment_deleted_success'));
        this.fetchAdjustments();
      } catch (e) {
        console.error(e);
        this.showSnack(e.response?.data?.message || 'Error', 'error');
      }
      finally { this.deleteLoading = false; }
    },
  },
};
</script>

<style scoped>
.hist-page {
  padding: 24px;
  max-width: 1500px;
  margin: 0 auto;
}

.hist-header {
  margin-bottom: 32px;
  position: relative;
}

.hist-header-glow {
  position: absolute;
  top: -30px;
  left: -20px;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(25,118,210,0.08) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.hist-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #1976d2, #42a5f5);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(25,118,210,0.25);
}

.hist-title {
  font-size: 26px;
  font-weight: 800;
  margin-bottom: 2px;
  background: linear-gradient(135deg, #1976d2, #42a5f5);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hist-subtitle {
  font-size: 14px;
  opacity: 0.55;
  margin: 0;
}

.hist-card {
  background: rgba(var(--v-theme-surface), 0.85) !important;
  backdrop-filter: blur(16px);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  border-radius: 20px !important;
  box-shadow: 0 4px 24px rgba(0,0,0,0.04);
  transition: box-shadow 0.3s ease;
}

.hist-card:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,0.06);
}

.hist-table-card {
  overflow: hidden;
}

.hist-table th {
  font-weight: 700 !important;
  font-size: 11px !important;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  opacity: 0.6;
  background: rgba(var(--v-theme-on-surface), 0.02) !important;
}

.hist-row {
  cursor: pointer;
  transition: all 0.2s ease;
}

.hist-row:hover {
  background: rgba(25, 118, 210, 0.04);
}

.hist-detail-table th {
  font-weight: 700 !important;
  font-size: 11px !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  opacity: 0.6;
}

.hist-dialog-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  box-shadow: 0 24px 64px rgba(0,0,0,0.12) !important;
}

.hist-empty-state {
  text-align: center;
  padding: 60px 20px;
}

.hist-empty-icon {
  width: 100px;
  height: 100px;
  border-radius: 28px;
  background: rgba(25,118,210, 0.06);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* v-calendar overrides for glass look */
:deep(.vc-container) {
  border-radius: 16px !important;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06) !important;
}

@media (max-width: 960px) {
  .hist-page { padding: 12px; }
}
</style>
