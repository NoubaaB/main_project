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
        <div class="inv-hero-actions"><v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="refreshInventory" :loading="refreshing">{{ $t('refresh') }}</v-btn></div>
      </div>
    </div>

    <!-- Tab Slider -->
    <v-slide-group v-model="activeTab" mandatory show-arrows class="inv-tab-slider mx-4 mt-3">
      <v-slide-group-item v-for="tab in tabs" :key="tab.value" v-slot="{ isSelected, toggle }">
        <v-btn
          :color="isSelected ? '#6D4C41' : undefined"
          :variant="isSelected ? 'flat' : 'text'"
          rounded
          class="mx-1"
          @click="toggle"
          :prepend-icon="tab.icon"
        >{{ $t(tab.label) }}</v-btn>
      </v-slide-group-item>
    </v-slide-group>

    <v-divider class="mx-4 mt-2" color="#6D4C41"></v-divider>

    <!-- TAB: List Inventory -->
    <ListInventory v-if="activeTab === 0" ref="listInventoryRef" />


    <!-- TAB: Add Inventory -->
    <v-card-text v-else-if="activeTab === 1" class="pa-0">
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
                  v-model="inventoryStore.inventory.inventory_tracking_number"
                  :items="inventoryStore.inventory.inventory_tracking_number"
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
                      @click:close="inventoryStore.inventory.inventory_tracking_number = inventoryStore.inventory.inventory_tracking_number.filter(num => num !== item.value)">
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
            <div v-if="inventoryStore.inventory && inventoryStore.inventory.details.length > 0" class="inv-summary-bar">
              <div class="d-flex align-center ga-4">
                <div class="summary-item"><v-icon size="18" color="#6D4C41" class="mr-1">mdi-weight-kilogram</v-icon><span class="summary-label">{{ $t('total_weight') }}:</span><span class="summary-value" style="color:#6D4C41">{{ main.formatDisplayNumber(inventoryStore.inventory.total_weight) }} Kg</span></div>
                <div class="summary-item"><v-icon size="18" color="success" class="mr-1">mdi-cash</v-icon><span class="summary-label">{{ $t('total_price') }}:</span><span class="summary-value text-success">{{ main.formatDisplayNumber(inventoryStore.inventory.total_price) }} {{ main.currency }}</span></div>
              </div>
            </div>

            <!-- Submit / Cancel -->
            <v-row class="mt-3">
              <v-col cols="6">
                <v-btn block rounded variant="outlined" color="#6D4C41" :disabled="!canSubmit" :loading="submitting" @click="submitInventory">{{ $t('submit') }}</v-btn>
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
  </v-card>
</template>

<script>
import axios from 'axios';
import { useInventory } from '@/stores/Inventory';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import DetailInventories from './DetailInventories.vue';
import ListInventory from './ListInventory.vue';
import mitt from '@/plugins/mitt';

export default {
  name: 'AddInventory',
  components: { DetailInventories, ListInventory },
  data() {
    return {
      activeTab: 0,
      tabs: [
        { value: 0, label: 'list_inventory', icon: 'mdi-view-list-outline' },
        { value: 1, label: 'add_inventory', icon: 'mdi-sticker-plus-outline' },
      ],
      submitting: false, descriptionDialog: false, refreshing: false,
      is_checking_tracking: false,
    };
  },
  computed: {
    inventoryStore() { return useInventory(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
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
  },
  mounted() {
    this.inventoryStore._restoreDraft();
    if (!this.inventoryStore.inventory) this.inventoryStore.add_inventory();
    if (this.$route.query.tab === 'add') this.activeTab = 1;
  },
  watch: {
    'inventoryStore.inventory': { handler() { this.inventoryStore._persistDraft(); }, deep: true },
    activeTab(v) {
      const q = v === 1 ? { tab: 'add' } : {};
      if (JSON.stringify(this.$route.query) !== JSON.stringify(q)) this.$router.replace({ query: q }).catch(() => {});
      if (v === 0 && this.$refs.listInventoryRef?.fetchInventories) this.$refs.listInventoryRef.fetchInventories();
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
      const numbersToAdd = inputValue.includes(' ') ? inputValue.split(/\s+/).filter(Boolean) : [inputValue];
      for (const input of numbersToAdd) {
        const prefixed = this.prefixWithYear(input);
        if (this.inventoryStore.inventory.inventory_tracking_number.includes(prefixed)) continue;
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
        this.inventoryStore.inventory.inventory_tracking_number.push(prefixed);
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
    async submitInventory() {
      this.submitting = true;
      try {
        const inv = JSON.parse(JSON.stringify(this.inventoryStore.inventory));
        inv.details = inv.details.map(d => { const { _uid, ...rest } = d; return rest; });
        delete inv.id;
        await axios.post('/api/inventory', inv);
        this.inventoryStore.clearDraft();
        sessionStorage.removeItem('detail_inventories_draft');
        this.inventoryStore.add_inventory();
        this.main.add_snackbar({ show: true, message: this.$t('record_added_success'), color: 'success' });
        this.activeTab = 0;
      } catch (err) {
        this.main.add_snackbar({ show: true, message: err.response?.data?.message || this.$t('record_added_failed'), color: 'error' });
      }
      this.submitting = false;
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
.inv-panel-card { background: rgba(109,76,65,.03); border-radius: 14px; border: 1px solid rgba(109,76,65,.1); padding: 10px; }
.inv-panel-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6D4C41; margin-bottom: 8px; display: flex; align-items: center; }
.inv-summary-bar { display: flex; flex-direction: column; gap: 8px; padding: 14px 18px; background: rgba(109,76,65,.04); border-radius: 14px; border: 1.5px dashed rgba(109,76,65,.2); }
.summary-item { display: flex; align-items: center; gap: 4px; }
.summary-label { font-size: 13px; font-weight: 600; color: rgba(var(--v-theme-on-surface), .6); }
.summary-value { font-size: 15px; font-weight: 800; }
.text-inventory { color: #6D4C41 !important; }
</style>
