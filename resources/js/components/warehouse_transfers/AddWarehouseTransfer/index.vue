<template>
  <v-card class="wt-main-card" elevation="0">
    <!-- Hero Header -->
    <div class="wt-hero">
      <div class="wt-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="wt-hero-icon">
            <v-icon size="28" color="white">mdi-swap-horizontal</v-icon>
          </div>
          <div>
            <div class="wt-hero-title">{{ $t('warehouse_transfers') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('warehouse_transfer_desc') }}</div>
          </div>
        </div>
        <div class="wt-hero-actions"><v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="refreshTransfers" :loading="refreshing">{{ $t('refresh') }}</v-btn></div>
      </div>
    </div>

    <!-- Tab Slider -->
    <v-slide-group v-model="activeTab" mandatory show-arrows class="wt-tab-slider mx-4 mt-3">
      <v-slide-group-item v-for="tab in tabs" :key="tab.value" v-slot="{ isSelected, toggle }">
        <v-btn
          :color="isSelected ? '#F9A825' : undefined"
          :variant="isSelected ? 'flat' : 'text'"
          rounded
          class="mx-1"
          @click="toggle"
          :prepend-icon="tab.icon"
        >{{ $t(tab.label) }}</v-btn>
      </v-slide-group-item>
    </v-slide-group>

    <v-divider class="mx-4 mt-2" color="#F9A825"></v-divider>

    <!-- TAB: List Transfers -->
    <ListWarehouseTransfer v-if="activeTab === 0" ref="listTransferRef" @transfer-deleted="showDeleteSnackbar" />

    <!-- TAB: Add Transfer -->
    <v-card-text v-else-if="activeTab === 1" class="pa-0">
      <v-row class="ma-0">
        <!-- LEFT: Transfer Info -->
        <v-col cols="12" md="5" class="pa-4">
          <v-card class="wt-form-card" elevation="0">
            <!-- Add Transfer Hero -->
            <div class="wt-add-hero">
              <div class="wt-add-hero-inner">
                <div class="d-flex align-center ga-3">
                  <div class="wt-add-hero-icon">
                    <v-icon size="24" color="white">mdi-sticker-plus-outline</v-icon>
                  </div>
                  <div>
                    <div class="wt-add-hero-title">{{ $t('add_warehouse_transfer') }}</div>
                    <div class="text-white text-caption" style="opacity:.7">{{ $t('fill_transfer_details') }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- (1) Necessary Information -->
            <v-row class="mt-2">
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#F9A825"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon style="color:#F9A825">mdi-numeric-1-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("necessary_information") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#F9A825"></v-divider></div>
              </v-col>
            </v-row>

            <!-- Description button above Calendar (right-aligned) -->
            <v-row class="mt-1">
              <v-col class="d-flex justify-end">
                <v-btn variant="tonal"
                 color="#F9A825" 
                 icon="mdi-notebook-plus-outline" 
                @click="descriptionDialog = true">

                </v-btn>
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
                  color="yellow"
                />
              </v-col>
            </v-row>

            <!-- Tracking number + Trucks (same row) -->
            <v-row>
              <v-col cols="12" md="6">
                <v-autocomplete
                  ref="trackingNumberInput"
                  v-model="transferStore.transfer.transfer_tracking_number"
                  :items="transferStore.transfer.transfer_tracking_number"
                  :label="$t('transfer_tracking_number')"
                  placeholder="ex :   --- --- --- --- ---"
                  multiple chips density="compact" variant="outlined" rounded clearable
                  @keydown.enter.prevent="addNewTrackingNumber"
                  color="#F9A825" class="text-transfer"
                  :disabled="is_checking_tracking" :loading="is_checking_tracking"
                  hide-details
                >
                  <template #chip="{ item, index }">
                    <v-chip :key="index" color="#F9A825" closable variant="tonal"
                      @click:close="transferStore.transfer.transfer_tracking_number = transferStore.transfer.transfer_tracking_number.filter(num => num !== item.value)">
                      {{ item.title }}
                    </v-chip>
                  </template>
                </v-autocomplete>
              </v-col>
              <v-col cols="12" md="6">
                <v-autocomplete
                  ref="truckInput"
                  v-model="transferStore.transfer.truck_ids"
                  :items="truckOptions"
                  :label="$t('trucks')"
                  multiple chips density="compact" variant="outlined" rounded clearable
                  color="#F9A825" class="text-transfer" hide-details
                  @update:search="val => truckSearch = val"
                  @keydown.enter.prevent="createTruckIfNew"
                >
                  <template #chip="{ item, index }">
                    <v-chip :key="index" color="#F9A825" closable variant="tonal"
                      @click:close="transferStore.transfer.truck_ids = transferStore.transfer.truck_ids.filter(id => id !== item.value)">
                      {{ item.title }}
                    </v-chip>
                  </template>
                  <template #no-data>
                    <v-list-item v-if="truckSearch?.trim()">
                      <v-list-item-title class="text-caption">
                        <v-icon size="16" class="mr-1">mdi-plus-circle-outline</v-icon>
                        {{ $t('press_enter_to_create') }} "<strong>{{ truckSearch }}</strong>"
                      </v-list-item-title>
                    </v-list-item>
                  </template>
                </v-autocomplete>
              </v-col>
            </v-row>

            <!-- (2) Summary -->
            <v-row v-if="transferStore.transfer && transferStore.transfer.details.length > 0" class="mt-3">
              <v-col class="d-flex align-center">
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#F9A825"></v-divider></div>
                <div class="px-2 d-block text-center">
                  <v-row class="pa-0"><v-col class="pa-0"><v-icon style="color:#F9A825">mdi-numeric-2-circle-outline</v-icon></v-col></v-row>
                  <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("summary") }}</span></v-col></v-row>
                </div>
                <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#F9A825"></v-divider></div>
              </v-col>
            </v-row>
            <div v-if="transferStore.transfer && transferStore.transfer.details.length > 0" class="wt-summary-bar">
              <div class="d-flex align-center ga-4">
                <div class="summary-item"><v-icon size="18" color="#F9A825" class="mr-1">mdi-weight-kilogram</v-icon><span class="summary-label">{{ $t('total_weight') }}:</span><span class="summary-value" style="color:#F9A825">{{ main.formatDisplayNumber(transferStore.transfer.total_weight) }} Kg</span></div>
                <div class="summary-item"><v-icon size="18" color="#F9A825" class="mr-1">mdi-package-variant</v-icon><span class="summary-label">{{ $t('details') }}:</span><span class="summary-value" style="color:#F9A825">{{ transferStore.transfer.details.length }}</span></div>
              </div>
            </div>

            <!-- Submit / Cancel -->
            <v-row class="mt-3">
              <v-col cols="6">
                <v-btn block rounded variant="outlined" color="#F9A825" :disabled="!canSubmit" :loading="submitting" @click="submitTransfer">{{ $t('submit') }}</v-btn>
              </v-col>
              <v-col cols="6">
                <v-btn block rounded variant="tonal" color="red-lighten-1" :disabled="!transferStore.transfer" @click="resetTransfer">{{ $t('cancel') }}</v-btn>
              </v-col>
            </v-row>
          </v-card>
        </v-col>

        <v-divider vertical></v-divider>

        <!-- RIGHT: Detail Transfers -->
        <v-col cols="12" md="7" class="pa-4">
          <DetailTransfers v-if="transferStore.transfer" :transferDate="transferStore.transfer.date" />
          <div v-else class="d-flex flex-column align-center justify-center" style="min-height: 300px; opacity: .5;">
            <v-icon size="80" color="#F9A825" class="mb-4">mdi-swap-horizontal</v-icon>
            <div class="text-h6 font-weight-medium" style="color:#F9A825">{{ $t('start_adding_details') }}</div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>

    <!-- Description Dialog -->
    <v-dialog v-model="descriptionDialog" max-width="600" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#F9A825" class="mr-2">mdi-notebook-edit-outline</v-icon>
          <span>{{ $t('description') }}</span>
          <v-spacer></v-spacer>
          <v-chip size="small" :color="descriptionLength > 1000 ? 'error' : '#F9A825'" variant="tonal">{{ descriptionLength }} / 1000</v-chip>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-4">
          <v-textarea v-model="transferStore.transfer.description" :label="$t('description')" :counter="1000" :maxlength="1000" rows="6" variant="outlined" rounded color="#F9A825" auto-grow></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="#F9A825" rounded @click="descriptionDialog = false" prepend-icon="mdi-check">{{ $t('done') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#F9A825" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="summarySnackbar.type === 'delete' ? 'error' : 'success'" class="mr-2">{{ summarySnackbar.type === 'delete' ? 'mdi-delete-circle' : 'mdi-check-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.type === 'delete' ? $t('transfer_deleted_success') : $t('transfer_submitted_success') }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.date"><v-icon size="14" color="#F9A825" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('date') }}:</span> <strong>{{ summarySnackbar.date }}</strong></div>
        <div v-if="summarySnackbar.trackingNumbers"><v-icon size="14" color="#F9A825" class="mr-1">mdi-barcode</v-icon><span class="text-medium-emphasis">{{ $t('transfer_tracking_number') }}:</span> <strong>{{ summarySnackbar.trackingNumbers }}</strong></div>
        <div v-if="summarySnackbar.trucks"><v-icon size="14" color="#F9A825" class="mr-1">mdi-truck</v-icon><span class="text-medium-emphasis">{{ $t('trucks') }}:</span> <strong>{{ summarySnackbar.trucks }}</strong></div>
        <v-divider class="my-1"></v-divider>
        <div class="d-flex ga-4">
          <div><v-icon size="14" color="#F9A825" class="mr-1">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('total_weight') }}:</span> <strong style="color:#F9A825">{{ summarySnackbar.totalWeight }} Kg</strong></div>
          <div><v-icon size="14" color="#F9A825" class="mr-1">mdi-package-variant-closed</v-icon><span class="text-medium-emphasis">{{ $t('total_articles') }}:</span> <strong style="color:#F9A825">{{ summarySnackbar.articleCount }}</strong></div>
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
import { useWarehouseTransfer } from '@/stores/WarehouseTransfer';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import DetailTransfers from './DetailTransfers.vue';
import ListWarehouseTransfer from './ListWarehouseTransfer.vue';
import mitt from '@/plugins/mitt';

export default {
  name: 'AddWarehouseTransfer',
  components: { DetailTransfers, ListWarehouseTransfer },
  data() {
    return {
      activeTab: 0,
      tabs: [
        { value: 0, label: 'list_warehouse_transfer', icon: 'mdi-view-list-outline' },
        { value: 1, label: 'add_warehouse_transfer', icon: 'mdi-swap-horizontal' },
      ],
      submitting: false, descriptionDialog: false, refreshing: false,
      is_checking_tracking: false,
      truckSearch: '',
      summarySnackbar: { show: false, type: '', date: '', trackingNumbers: '', trucks: '', totalWeight: '', articleCount: '' },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    transferStore() { return useWarehouseTransfer(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    canSubmit() {
      const t = this.transferStore.transfer;
      return t?.date && t?.details?.length > 0;
    },
    descriptionLength() { return (this.transferStore.transfer?.description || '').length; },
    truckOptions() { return this.auth.truck.list.map(t => ({ title: t.registration_plate || t.name, value: t.id })); },
    dateModel: {
      get() { if (!this.transferStore.transfer?.date) return null; return new Date(this.transferStore.transfer.date + 'T00:00:00'); },
      set(val) {
        if (!this.transferStore.transfer) return;
        if (val instanceof Date) { const y = val.getFullYear(); const m = String(val.getMonth() + 1).padStart(2, '0'); const d = String(val.getDate()).padStart(2, '0'); this.transferStore.transfer.date = `${y}-${m}-${d}`; }
        else { this.transferStore.transfer.date = val; }
      }
    },
  },
  mounted() {
    this.transferStore._restoreDraft();
    if (!this.transferStore.transfer) this.transferStore.add_transfer();
    if (this.$route.query.tab === 'add') this.activeTab = 1;
  },
  watch: {
    'transferStore.transfer': { handler() { this.transferStore._persistDraft(); }, deep: true },
    activeTab(v) {
      const q = v === 1 ? { tab: 'add' } : {};
      if (JSON.stringify(this.$route.query) !== JSON.stringify(q)) this.$router.replace({ query: q }).catch(() => {});
      if (v === 0 && this.$refs.listTransferRef?.fetchTransfers) this.$refs.listTransferRef.fetchTransfers();
    },
  },
  methods: {
    prefixWithYear(value) {
      const dateStr = this.transferStore.transfer?.date;
      const yy = dateStr ? dateStr.substring(2, 4) : String(new Date().getFullYear()).substring(2);
      const prefix = yy + '-';
      if (value.startsWith(prefix)) return value;
      return prefix + value;
    },
    async addNewTrackingNumber(event) {
      let inputValue = event.target.value.trim();
      if (!inputValue) return;
      const numbersToAdd = inputValue.includes(' ') ? inputValue.split(/\s+/).filter(Boolean) : [inputValue];
      for (const input of numbersToAdd) {
        const prefixed = this.prefixWithYear(input);
        if (this.transferStore.transfer.transfer_tracking_number.includes(prefixed)) continue;
        this.is_checking_tracking = true;
        try {
          const res = await axios.post('/api/warehouse-transfer/check-tracking-number', {
            tracking_number: prefixed,
          });
          if (res.data.exists) {
            const t = res.data.transfer;
            this.main.add_snackbar({
              show: true,
              message: this.$t('tracking_number_exists', { tracking_number: prefixed })
                + ' - ' + this.$t('transfer_tracking_duplicate_details', {
                  date: t.date, weight: t.weight || 0,
                }),
              color: 'warning'
            });
            this.is_checking_tracking = false;
            continue;
          }
        } catch (e) { console.error(e); }
        this.is_checking_tracking = false;
        this.transferStore.transfer.transfer_tracking_number.push(prefixed);
      }
      event.target.value = '';
      this.$nextTick(() => { this.$refs.trackingNumberInput?.focus(); });
    },
    async createTruckIfNew() {
      const search = (this.truckSearch || '').trim();
      if (!search) return;
      const existing = this.auth.truck.list.find(t => (t.registration_plate || t.name || '').toLowerCase() === search.toLowerCase());
      if (existing) {
        if (!this.transferStore.transfer.truck_ids.includes(existing.id)) {
          this.transferStore.transfer.truck_ids.push(existing.id);
        }
        this.truckSearch = '';
        this.$nextTick(() => { this.$refs.truckInput?.focus(); });
        return;
      }
      try {
        const res = await axios.post('/api/truck', { registration_plate: search });
        const newTruck = res.data.truck;
        this.auth.truck.list.push(newTruck);
        this.transferStore.transfer.truck_ids.push(newTruck.id);
        this.truckSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('truck_created', { name: newTruck.registration_plate }), color: 'success' });
        this.$nextTick(() => { this.$refs.truckInput?.focus(); });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('truck_create_error'), color: 'error' });
      }
    },
    async refreshTransfers() {
      this.refreshing = true;
      try { if (this.$refs.listTransferRef?.fetchTransfers) await this.$refs.listTransferRef.fetchTransfers(); } catch (e) { console.error(e); }
      this.refreshing = false;
    },
    resetTransfer() {
      this.transferStore.add_transfer();
      this.transferStore.clearDraft();
      sessionStorage.removeItem('detail_transfers_draft');
      this.main.add_snackbar({ show: true, message: this.$t('reset_transfer'), color: 'success' });
    },
    async submitTransfer() {
      this.submitting = true;
      try {
        const snapshot = this.buildSnapshot();
        const t = JSON.parse(JSON.stringify(this.transferStore.transfer));
        t.details = t.details.map(d => { const { _uid, ...rest } = d; return rest; });
        delete t.id;
        await axios.post('/api/warehouse-transfer', t);
        this.transferStore.clearDraft();
        sessionStorage.removeItem('detail_transfers_draft');
        this.transferStore.add_transfer();
        this.showSnackbarFromSnapshot(snapshot, 'submit');
        this.activeTab = 0;
      } catch (err) {
        this.main.add_snackbar({ show: true, message: err.response?.data?.message || this.$t('record_added_failed'), color: 'error' });
      }
      this.submitting = false;
    },
    buildSnapshot() {
      const t = this.transferStore.transfer;
      const truckNames = (t?.truck_ids || []).map(id => this.auth.truck.list.find(tr => tr.id === id)?.registration_plate).filter(Boolean).join(', ');
      return {
        date: t?.date || '',
        trackingNumbers: (t?.transfer_tracking_number || []).join(', '),
        trucks: truckNames,
        totalWeight: this.main.formatDisplayNumber(t?.total_weight || 0),
        articleCount: (t?.details || []).length,
      };
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
    showDeleteSnackbar(info) {
      this.showSnackbarFromSnapshot(info, 'delete');
    },
  },
};
</script>

<style scoped>
.wt-main-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.wt-hero { background: linear-gradient(135deg, #F57F17 0%, #F9A825 40%, #FDD835 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.wt-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.wt-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.wt-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.wt-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
.wt-tab-slider { border-radius: 12px; }
.wt-form-card { font-family: 'Inter', sans-serif; }
.wt-add-hero { background: linear-gradient(135deg, #F57F17 0%, #F9A825 50%, #FDD835 100%); border-radius: 14px; overflow: hidden; }
.wt-add-hero-inner { padding: 16px 20px; color: #fff; display: flex; align-items: center; gap: 12px; }
.wt-add-hero-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.wt-add-hero-title { font-size: 16px; font-weight: 700; letter-spacing: .2px; }
.wt-summary-bar { display: flex; flex-direction: column; gap: 8px; padding: 14px 18px; background: rgba(249,168,37,.04); border-radius: 14px; border: 1.5px dashed rgba(249,168,37,.2); }
.summary-item { display: flex; align-items: center; gap: 4px; }
.summary-label { font-size: 13px; font-weight: 600; color: rgba(var(--v-theme-on-surface), .6); }
.summary-value { font-size: 15px; font-weight: 800; }
.text-transfer { color: #F9A825 !important; }
</style>
