<template>
  <v-card class="inv-main-card" elevation="0">
    <!-- Hero Header -->
    <div class="inv-hero">
      <div class="inv-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="inv-hero-icon">
            <v-icon size="28" color="white">mdi-clipboard-text-clock-outline</v-icon>
          </div>
          <div>
            <div class="inv-hero-title">{{ $t('inventory') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('inventory_desc') }}</div>
          </div>
        </div>
        <div class="inv-hero-actions d-flex align-center ga-2">
          <v-btn
            v-if="auth.hasPermission('inventory.create')"
            variant="tonal"
            color="white"
            rounded
            size="small"
            prepend-icon="mdi-restore-alert"
            @click="activeTab = 3"
          >
            {{ $t('init_warehouse_stock') || 'Init Stock to 0' }}
          </v-btn>
          <v-btn
            variant="tonal"
            color="white"
            rounded
            size="small"
            prepend-icon="mdi-refresh"
            @click="refreshInventory"
            :loading="refreshing"
          >
            {{ $t('refresh') }}
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Tab Slider (Matching Activity Breakdown Entity Toggle) -->
    <div class="d-flex align-center justify-space-between mx-4 mt-3">
      <SegmentedPillToggle
        v-model="activeTab"
        :items="visibleTabs.map(t => ({ value: t.value, label: $t(t.label), icon: t.icon }))"
        color="#6D4C41"
        @change="navigateTab"
      />
    </div>

    <v-divider class="mx-4 mt-3" color="#6D4C41"></v-divider>

    <!-- TAB: List Inventory -->
    <ListInventory v-if="activeTab === 0" ref="listInventoryRef" @edit-inventory="onEditInventory" />

    <!-- TAB: Add / Edit Inventory -->
    <v-card-text v-else-if="activeTab === 1 || activeTab === 2" class="pa-0">
      <v-row class="ma-0">
        <!-- LEFT: Inventory Info -->
        <v-col cols="12" md="5" class="pa-4">
          <v-card class="inv-form-card" elevation="0">
            <!-- (1) Necessary Information -->
            <v-row>
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#6D4C41"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon style="color:#6D4C41">mdi-numeric-1-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("necessary_information") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#6D4C41"></v-divider></div>
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
                  color="brown"
                />
              </v-col>
            </v-row>

            <!-- Tracking number -->
            <v-row>
              <v-col>
                <v-autocomplete
                  ref="trackingNumberInput"
                  v-model="inventoryStore.inventory.inventory_tracking_numbers"
                  :items="inventoryStore.inventory.inventory_tracking_numbers"
                  :label="$t('inventory_tracking_number')"
                  placeholder="ex :   --- --- --- --- ---"
                  multiple chips density="compact" variant="outlined" rounded clearable
                  @keydown.enter.prevent="addNewTrackingNumber"
                  color="#6D4C41" class="text-inventory"
                  :disabled="is_checking_tracking" :loading="is_checking_tracking"
                  hide-details
                >
                  <template #chip="{ item, index }">
                    <v-chip :key="index" color="#6D4C41" closable variant="tonal"
                      @click:close="inventoryStore.inventory.inventory_tracking_numbers = inventoryStore.inventory.inventory_tracking_numbers.filter(num => num !== item.value)">
                      {{ item.title }}
                    </v-chip>
                  </template>
                </v-autocomplete>
              </v-col>
              <v-col cols="2" class="py-2 pl-0">
                <v-btn variant="tonal" color="#6D4C41" icon="mdi-notebook-plus-outline" @click="descriptionDialog = true"></v-btn>
              </v-col>
            </v-row>

            <!-- (2) Summary -->
            <v-row v-if="inventoryStore.inventory && inventoryStore.inventory.details.length > 0">
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#6D4C41"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon style="color:#6D4C41">mdi-numeric-2-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("summary") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#6D4C41"></v-divider></div>
              </v-col>
            </v-row>

            <!-- Per-warehouse summary -->
            <div v-if="inventoryStore.inventory && inventoryStore.inventory.details.length > 0" class="inv-summary-bar">
              <div v-for="whSummary in warehouseSummaries" :key="whSummary.id" class="summary-category">
                <v-chip size="small" color="#6D4C41" variant="tonal" class="mb-1 font-weight-bold">
                  <v-icon size="14" class="mr-1">mdi-warehouse</v-icon>
                  {{ whSummary.name }}
                </v-chip>
                <div class="d-flex align-center ga-3">
                  <div class="summary-item">
                    <v-icon size="16" color="#6D4C41" class="mr-1">mdi-weight-kilogram</v-icon>
                    <span class="summary-label">{{ $t('weight') }}:</span>
                    <span class="summary-value" style="color:#6D4C41">{{ main.formatDisplayNumber(whSummary.totalWeight) }} Kg</span>
                  </div>
                  <div class="summary-item">
                    <v-icon size="16" color="success" class="mr-1">mdi-cash</v-icon>
                    <span class="summary-label">{{ $t('total_price') }}:</span>
                    <span class="summary-value text-success">{{ main.formatDisplayNumber(whSummary.totalPrice) }} {{ main.currency }}</span>
                  </div>
                  <div class="summary-item">
                    <v-icon size="16" color="#6D4C41" class="mr-1">mdi-package-variant-closed</v-icon>
                    <span class="summary-label">{{ $t('articles') }}:</span>
                    <span class="summary-value" style="color:#6D4C41">{{ whSummary.count }}</span>
                  </div>
                </div>
              </div>
              <v-divider class="my-2"></v-divider>
              <div class="summary-global">
                <v-chip size="small" color="#6D4C41" variant="elevated" class="mb-1 font-weight-bold">{{ $t('global_total') }}</v-chip>
                <div class="d-flex align-center ga-4">
                  <div class="summary-item">
                    <v-icon size="18" color="#6D4C41" class="mr-1">mdi-weight-kilogram</v-icon>
                    <span class="summary-label">{{ $t('total_weight') }}:</span>
                    <span class="summary-value" style="color:#6D4C41">{{ main.formatDisplayNumber(inventoryStore.inventory.total_weight) }} Kg</span>
                  </div>
                  <div class="summary-item">
                    <v-icon size="18" color="success" class="mr-1">mdi-cash</v-icon>
                    <span class="summary-label">{{ $t('total_price') }}:</span>
                    <span class="summary-value text-success">{{ main.formatDisplayNumber(inventoryStore.inventory.total_price) }} {{ main.currency }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit / Cancel -->
            <v-row class="mt-3">
              <v-col cols="6">
                <v-btn block rounded variant="outlined" color="#6D4C41" :disabled="!canSubmit" :loading="submitting" @click="submitInventory">{{ isEditMode ? $t('update') : $t('submit') }}</v-btn>
              </v-col>
              <v-col cols="6">
                <v-btn block rounded variant="tonal" color="red-lighten-1" :disabled="!inventoryStore.inventory" @click="resetInventory">{{ $t('cancel') }}</v-btn>
              </v-col>
            </v-row>
          </v-card>
        </v-col>

        <v-divider vertical></v-divider>

        <!-- RIGHT: Detail Inventories -->
        <v-col cols="12" md="7" class="pa-4">
          <DetailInventories v-if="inventoryStore.inventory" />
          <div v-else class="d-flex flex-column align-center justify-center" style="min-height: 300px; opacity: .5;">
            <v-icon size="80" color="#6D4C41" class="mb-4">mdi-clipboard-text-outline</v-icon>
            <div class="text-h6 font-weight-medium" style="color:#6D4C41">{{ $t('start_adding_details') }}</div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>

    <!-- TAB: Initialize / Reset Stock to 0 Checkpoint -->
    <InitWarehouseStock v-else-if="activeTab === 3" />

    <!-- Description Dialog -->
    <v-dialog v-model="descriptionDialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#6D4C41" class="mr-2">mdi-notebook-edit-outline</v-icon>
          <span>{{ $t('description') }}</span>
          <v-spacer></v-spacer>
          <v-chip size="small" :color="descriptionLength > 1000 ? 'error' : '#6D4C41'" variant="tonal">{{ descriptionLength }} / 1000</v-chip>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-4">
          <v-textarea v-model="inventoryStore.inventory.description" :label="$t('description')" :counter="1000" :maxlength="1000" rows="6" variant="outlined" rounded color="#6D4C41" auto-grow></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="#6D4C41" rounded @click="descriptionDialog = false" prepend-icon="mdi-check">{{ $t('done') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#6D4C41" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('inventory_submitted_success') }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.date"><v-icon size="14" color="#6D4C41" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('date') }}:</span> <strong>{{ summarySnackbar.date }}</strong></div>
        <div v-if="summarySnackbar.trackingNumbers"><v-icon size="14" color="#6D4C41" class="mr-1">mdi-barcode</v-icon><span class="text-medium-emphasis">{{ $t('inventory_tracking_number') }}:</span> <strong>{{ summarySnackbar.trackingNumbers }}</strong></div>
        <div v-if="summarySnackbar.warehouseCount"><v-icon size="14" color="#6D4C41" class="mr-1">mdi-warehouse</v-icon><span class="text-medium-emphasis">{{ $t('warehouses') }}:</span> <strong>{{ summarySnackbar.warehouseCount }}</strong></div>
        <v-divider class="my-1"></v-divider>
        <div class="d-flex ga-4">
          <div><v-icon size="14" color="#6D4C41" class="mr-1">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('total_weight') }}:</span> <strong style="color:#6D4C41">{{ summarySnackbar.totalWeight }} Kg</strong></div>
          <div><v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span> <strong class="text-success">{{ summarySnackbar.totalPrice }} {{ main.currency }}</strong></div>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import axios from 'axios';
import { useInventory } from '@/stores/Inventory';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import DetailInventories from './DetailInventories.vue';
import ListInventory from './ListInventory.vue';
import InitWarehouseStock from './InitWarehouseStock.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import mitt from '@/plugins/mitt';

export default {
  name: 'AddInventory',
  components: { DetailInventories, ListInventory, SegmentedPillToggle, InitWarehouseStock },
  data() {
    return {
      activeTab: 0,
      editingId: null,
      tabs: [
        { value: 0, label: 'list_inventory', icon: 'mdi-view-list-outline' },
        { value: 1, label: 'add_inventory', icon: 'mdi-sticker-plus-outline' },
        { value: 2, label: 'edit_inventory', icon: 'mdi-pencil-outline' },
        { value: 3, label: 'init_warehouse_stock', icon: 'mdi-restore-alert' },
      ],
      submitting: false, descriptionDialog: false, refreshing: false,
      is_checking_tracking: false,
      summarySnackbar: {
        show: false, date: '', trackingNumbers: '',
        warehouseCount: '', totalWeight: '', totalPrice: '',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    inventoryStore() { return useInventory(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    isEditMode() { return this.activeTab === 2 && !!this.editingId; },
    visibleTabs() {
      return this.tabs.filter(t => {
        if (t.value === 1 && !this.auth.hasPermission('inventory.create')) return false;
        if (t.value === 2 && (!this.isEditMode || !this.auth.hasPermission('inventory.edit'))) return false;
        if (t.value === 3 && !this.auth.hasPermission('inventory.create')) return false;
        return true;
      });
    },
    canSubmit() {
      const inv = this.inventoryStore.inventory;
      return inv?.date && inv?.details?.length > 0;
    },
    descriptionLength() { return (this.inventoryStore.inventory?.description || '').length; },
    dateModel: {
      get() { if (!this.inventoryStore.inventory?.date) return null; return new Date(this.inventoryStore.inventory.date + 'T00:00:00'); },
      set(val) {
        if (!this.inventoryStore.inventory) return;
        if (val instanceof Date) { const y = val.getFullYear(); const m = String(val.getMonth() + 1).padStart(2, '0'); const d = String(val.getDate()).padStart(2, '0'); this.inventoryStore.inventory.date = `${y}-${m}-${d}`; }
        else { this.inventoryStore.inventory.date = val; }
      }
    },
    warehouseSummaries() {
      const details = this.inventoryStore.inventory?.details || [];
      const map = {};
      details.forEach(d => {
        const whId = d.warehouse_id;
        if (!whId) return;
        if (!map[whId]) {
          const wh = this.auth.warehouse.list.find(w => w.id === whId);
          map[whId] = { id: whId, name: wh ? wh.name : `#${whId}`, totalWeight: 0, totalPrice: 0, count: 0 };
        }
        map[whId].totalWeight += parseFloat(d.weight) || 0;
        map[whId].totalPrice += parseFloat(d.total_price) || 0;
        map[whId].count++;
      });
      return Object.values(map);
    },
  },
  mounted() {
    this.inventoryStore._restoreDraft();
    if (!this.inventoryStore.inventory) this.inventoryStore.add_inventory();
    this.syncTabFromRoute();
  },
  watch: {
    'inventoryStore.inventory': { handler() { this.inventoryStore._persistDraft(); }, deep: true },
    '$route.name': {
      handler() { this.syncTabFromRoute(); },
      immediate: false,
    },
  },
  methods: {
    prefixWithYear(value) {
      const dateStr = this.inventoryStore.inventory?.date;
      const yy = dateStr ? dateStr.substring(2, 4) : String(new Date().getFullYear()).substring(2);
      const prefix = yy + '-';
      if (value.startsWith(prefix)) return value;
      return prefix + value;
    },
    async addNewTrackingNumber(event) {
      let inputValue = event.target.value.trim();
      if (!inputValue) return;
      if (!this.inventoryStore.inventory) return;
      // Ensure tracking number array exists
      if (!Array.isArray(this.inventoryStore.inventory.inventory_tracking_numbers)) {
        this.inventoryStore.inventory.inventory_tracking_numbers = [];
      }
      const numbersToAdd = inputValue.includes(' ') ? inputValue.split(/\s+/).filter(Boolean) : [inputValue];
      for (const input of numbersToAdd) {
        const prefixed = this.prefixWithYear(input);
        if (this.inventoryStore.inventory.inventory_tracking_numbers.includes(prefixed)) continue;
        this.is_checking_tracking = true;
        try {
          const res = await axios.post('/api/inventory/check-tracking-number', {
            tracking_number: prefixed,
          });
          if (res.data.exists) {
            const inv = res.data.inventory;
            this.main.add_snackbar({
              show: true,
              message: this.$t('tracking_number_exists', { tracking_number: prefixed })
                + ' - ' + this.$t('inventory_tracking_duplicate_details', {
                  date: inv.date, weight: inv.weight || 0, price: inv.price || 0, currency: this.main.currency
                }),
              color: 'warning'
            });
            this.is_checking_tracking = false;
            continue;
          }
        } catch (e) { console.error(e); }
        this.is_checking_tracking = false;
        this.inventoryStore.inventory.inventory_tracking_numbers.push(prefixed);
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.trackingNumberInput?.focus(); });
    },
    async refreshInventory() {
      this.refreshing = true;
      try { if (this.$refs.listInventoryRef?.fetchInventories) await this.$refs.listInventoryRef.fetchInventories(); } catch (e) { console.error(e); }
      this.refreshing = false;
    },
    resetInventory() {
      this.inventoryStore.add_inventory();
      this.inventoryStore.clearDraft();
      sessionStorage.removeItem('detail_inventories_draft');
      this.main.add_snackbar({ show: true, message: this.$t('reset_inventory'), color: 'success' });
    },
    buildSnapshot() {
      const inv = this.inventoryStore.inventory;
      const warehouseCount = [...new Set((inv?.details || []).map(d => d.warehouse_id).filter(Boolean))].length;
      return {
        date: inv?.date || '',
        trackingNumbers: (inv?.inventory_tracking_numbers || []).join(', '),
        warehouseCount: String(warehouseCount),
        totalWeight: this.main.formatDisplayNumber(inv?.total_weight || 0),
        totalPrice: this.main.formatDisplayNumber(inv?.total_price || 0),
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
    navigateTab(tabValue) {
      if (tabValue === 0) {
        this.$router.push({ name: 'list_inventory' }).catch(() => {});
      } else if (tabValue === 1) {
        this.editingId = null;
        this.inventoryStore.add_inventory();
        this.$router.push({ name: 'add_inventory' }).catch(() => {});
      } else if (tabValue === 3) {
        this.activeTab = 3;
      }
      // tab 2 (edit) is only navigated via onEditInventory
    },
    syncTabFromRoute() {
      const name = this.$route.name;
      if (name === 'list_inventory') {
        this.activeTab = 0;
        this.$nextTick(() => { this.$refs.listInventoryRef?.fetchInventories?.(); });
      } else if (name === 'add_inventory') {
        this.activeTab = 1;
        this.editingId = null;
      } else if (name === 'edit_inventory') {
        this.activeTab = 2;
        const id = parseInt(this.$route.params.id);
        if (id && id !== this.editingId) {
          this.loadInventoryForEdit(id);
        }
      }
    },
    async loadInventoryForEdit(id) {
      try {
        const res = await axios.get(`/api/inventory/${id}`);
        const inv = res.data.inventory;
        this.editingId = inv.id;
        this.inventoryStore.inventory = {
          id: inv.id,
          date: typeof inv.date === 'string' ? inv.date.slice(0, 10) : inv.date,
          inventory_tracking_numbers: inv.inventory_tracking_numbers || [],
          description: inv.description || '',
          details: (inv.detail_inventories || []).map(d => ({
            ...d,
            _uid: Date.now() + '_' + Math.random().toString(36).substr(2, 5),
          })),
          total_weight: parseFloat(inv.total_weight) || 0,
          total_price: parseFloat(inv.total_price) || 0,
        };
        const whIds = [...new Set((inv.detail_inventories || []).map(d => d.warehouse_id).filter(Boolean))];
        this.inventoryStore.activeWarehouseSections = whIds;
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('error_occurred'), color: 'error' });
        this.$router.push({ name: 'list_inventory' }).catch(() => {});
      }
    },
    async submitInventory() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const inv = JSON.parse(JSON.stringify(this.inventoryStore.inventory));
        // Map frontend field to backend field name
        inv.inventory_tracking_number = inv.inventory_tracking_numbers || [];
        delete inv.inventory_tracking_numbers;
        inv.details = inv.details.map(d => { const { _uid, ...rest } = d; return rest; });

        if (this.isEditMode) {
          // UPDATE existing inventory
          const editId = this.editingId;
          delete inv.id;
          await axios.put(`/api/inventory/${editId}`, inv);
          this.main.add_snackbar({ show: true, message: this.$t('record_updated_successfully'), color: 'success' });
        } else {
          // CREATE new inventory
          delete inv.id;
          await axios.post('/api/inventory', inv);
        }

        this.inventoryStore.clearDraft();
        sessionStorage.removeItem('detail_inventories_draft');
        this.inventoryStore.add_inventory();
        this.editingId = null;
        this.showSnackbarFromSnapshot(snapshot);
        this.$router.push({ name: 'list_inventory' }).catch(() => {});
      } catch (err) {
        this.main.add_snackbar({ show: true, message: err.response?.data?.message || this.$t('record_added_failed'), color: 'error' });
      }
      this.submitting = false;
    },
    onEditInventory(item) {
      this.$router.push({ name: 'edit_inventory', params: { id: item.id } }).catch(() => {});
    },
  },
};
</script>

<style scoped>
.inv-main-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.inv-hero { background: linear-gradient(135deg, #4E342E 0%, #6D4C41 40%, #8D6E63 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.inv-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.inv-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.inv-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.inv-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
.inv-tab-slider { border-radius: 12px; }
.inv-form-card { font-family: 'Inter', sans-serif; }
.inv-summary-bar { display: flex; flex-direction: column; gap: 12px; padding: 18px 22px; background: linear-gradient(135deg, rgba(109,76,65,.04) 0%, rgba(76,175,80,.04) 100%); border-radius: 16px; border: 1.5px dashed rgba(109,76,65,.2); }
.summary-category { padding: 8px 12px; border-radius: 10px; background: rgba(var(--v-theme-on-surface), .03); }
.summary-global { padding: 10px 12px; border-radius: 12px; background: rgba(109,76,65,.06); }
.summary-item { display: flex; align-items: center; gap: 4px; }
.summary-label { font-size: 13px; font-weight: 600; color: rgba(var(--v-theme-on-surface), .6); }
.summary-value { font-size: 15px; font-weight: 800; }
.text-inventory { color: #6D4C41 !important; }

/* VCalendar brown theme override */
:deep(.vc-brown) {
  --vc-accent-50: #EFEBE9;
  --vc-accent-100: #D7CCC8;
  --vc-accent-200: #BCAAA4;
  --vc-accent-300: #A1887F;
  --vc-accent-400: #8D6E63;
  --vc-accent-500: #6D4C41;
  --vc-accent-600: #5D4037;
  --vc-accent-700: #4E342E;
  --vc-accent-800: #3E2723;
  --vc-accent-900: #2C1B14;
}
:deep(.vc-container) {
  --vc-accent-600: #5D4037;
}
</style>
