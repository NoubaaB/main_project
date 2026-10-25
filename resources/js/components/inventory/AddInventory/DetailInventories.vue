<template>
  <div class="detail-inventories">
    <!-- Per-warehouse sections -->
    <div v-for="whId in inventoryStore.activeWarehouseSections" :key="whId" class="mb-5">
      <v-card class="di-wh-card rounded-lg" elevation="0">
        <!-- Warehouse Header -->
        <v-card-title class="di-wh-header pa-3">
          <v-row align="center" no-gutters>
            <v-col class="d-flex align-center ga-2">
              <v-icon size="20" color="#6D4C41">mdi-warehouse</v-icon>
              <span class="font-weight-bold" style="color:#6D4C41">{{ warehouseName(whId) }}</span>
              <v-chip size="x-small" color="#6D4C41" variant="tonal" class="ml-2">
                {{ getWarehouseDetails(whId).length }} {{ $t('articles') }}
              </v-chip>
            </v-col>
            <v-col cols="auto" class="d-flex ga-1">
              <v-btn size="small" variant="tonal" color="#6D4C41" rounded prepend-icon="mdi-download" :loading="fetchingStock[whId]" @click="fetchCurrentStock(whId)">
                {{ $t('fetch_current_stock') }}
              </v-btn>
              <v-btn size="small" icon color="error" variant="text" @click="removeWarehouseSection(whId)">
                <v-icon size="18">mdi-close</v-icon>
              </v-btn>
            </v-col>
          </v-row>
        </v-card-title>

        <v-divider color="#6D4C41"></v-divider>

        <v-card-text class="pa-3">
          <!-- Section divider: Add Articles -->
          <v-row>
            <v-col class="d-flex align-center">
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#6D4C41"></v-divider></div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0"><v-col class="pa-0"><v-icon style="color:#6D4C41">mdi-plus-circle-outline</v-icon></v-col></v-row>
                <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("add_article_to_inventory") }}</span></v-col></v-row>
              </div>
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#6D4C41"></v-divider></div>
            </v-col>
          </v-row>

          <!-- Entry form -->
          <v-row class="pt-2" dense>
            <!-- Article Type -->
            <v-col cols="12" md="3">
              <v-autocomplete
                v-model="entryForms[whId].stockable_type"
                :items="articleTypeOptions"
                :label="$t('article_type')"
                density="compact" variant="outlined" rounded color="#6D4C41" class="text-inventory"
                hide-details clearable
                @update:model-value="onArticleTypeChange(whId)"
              ></v-autocomplete>
            </v-col>
            <!-- Article Name -->
            <v-col cols="12" md="3">
              <v-autocomplete
                v-model="entryForms[whId].stockable_id"
                :items="getStockableOptions(entryForms[whId].stockable_type)"
                :label="$t('type_article')"
                chips density="compact" variant="outlined" rounded color="#6D4C41" class="text-inventory"
                hide-details clearable
                :disabled="!entryForms[whId].stockable_type"
              >
                <template #chip="{ item }"><v-chip color="#6D4C41" closable variant="tonal" @click:close="entryForms[whId].stockable_id = null">{{ item.title }}</v-chip></template>
              </v-autocomplete>
            </v-col>
            <!-- Weight -->
            <v-col cols="6" md="2">
              <v-text-field
                v-model.number="entryForms[whId].weight"
                type="number" :label="$t('weight') + ' (Kg)'"
                density="compact" variant="outlined" rounded color="#6D4C41" class="text-inventory"
                hide-details min="0" @input="recalcForm(whId)"
              ></v-text-field>
            </v-col>
            <!-- Unit Price -->
            <v-col cols="6" md="2">
              <v-text-field
                v-model.number="entryForms[whId].unit_price"
                type="number" :label="$t('unit_price')"
                density="compact" variant="outlined" rounded color="#6D4C41" class="text-inventory"
                hide-details min="0" @input="recalcForm(whId)"
              ></v-text-field>
            </v-col>
            <!-- Total Price (readonly) -->
            <v-col cols="6" md="2">
              <v-text-field
                :model-value="entryForms[whId].total_price"
                type="number" :label="$t('total_price')"
                density="compact" variant="outlined" rounded color="#6D4C41" class="text-inventory"
                hide-details readonly
              ></v-text-field>
            </v-col>
          </v-row>
          <v-row dense>
            <!-- Package Type -->
            <v-col cols="6" md="3">
              <v-autocomplete
                v-model="entryForms[whId].package_type_id"
                :items="packageTypeOptions"
                :label="$t('package_type')"
                density="compact" variant="outlined" rounded color="#6D4C41" class="text-inventory"
                hide-details clearable
                @update:model-value="recalcForm(whId)"
              ></v-autocomplete>
            </v-col>
            <!-- Nbr Box (readonly) -->
            <v-col cols="6" md="2">
              <v-text-field
                :model-value="entryForms[whId].nbr_box"
                type="number" :label="$t('nbr_boxes')"
                density="compact" variant="outlined" rounded color="#6D4C41" class="text-inventory"
                hide-details readonly
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="7" class="d-flex align-center justify-end">
              <v-btn
                rounded variant="tonal" color="#6D4C41"
                prepend-icon="mdi-plus-circle-outline"
                :disabled="!isFormValid(whId)"
                @click="addArticle(whId)"
              >{{ $t('add_article') }}</v-btn>
            </v-col>
          </v-row>

          <!-- Articles Table -->
          <div v-if="getWarehouseDetails(whId).length > 0" class="di-table-wrap mt-3">
            <table class="di-table">
              <thead>
                <tr>
                  <th>{{ $t('article_type') }}</th>
                  <th>{{ $t('type_article') }}</th>
                  <th>{{ $t('weight') }} (Kg)</th>
                  <th>{{ $t('unit_price') }}</th>
                  <th>{{ $t('total_price') }} ({{ main.currency }})</th>
                  <th>{{ $t('package_type') }}</th>
                  <th>{{ $t('nbr_boxes') }}</th>
                  <th>{{ $t('actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="detail in getWarehouseDetails(whId)" :key="detail._uid" class="di-row">
                  <td>
                    <v-chip size="x-small" :color="typeColor(detail.stockable_type)" variant="tonal">
                      <v-icon size="12" class="mr-1">{{ typeIcon(detail.stockable_type) }}</v-icon>
                      {{ typeName(detail.stockable_type) }}
                    </v-chip>
                  </td>
                  <td class="font-weight-bold">{{ getArticleName(detail) }}</td>
                  <td class="font-weight-bold fnm-currency-num" style="color:#6D4C41">{{ main.formatDisplayNumber(detail.weight) }}</td>
                  <td class="fnm-currency-num">{{ main.formatDisplayNumber(detail.unit_price) }}</td>
                  <td class="font-weight-bold text-deep-orange fnm-currency-num">{{ main.formatDisplayNumber(detail.total_price) }}</td>
                  <td>{{ getPackageTypeName(detail.package_type_id) }}</td>
                  <td>
                    <v-chip v-if="detail.nbr_box" size="x-small" color="#6D4C41" variant="tonal" class="fnm-currency-num">{{ detail.nbr_box }}</v-chip>
                    <span v-else class="text-grey">-</span>
                  </td>
                  <td class="text-no-wrap">
                    <v-btn icon size="x-small" variant="text" color="warning" class="mr-1" @click="editArticle(whId, detail)">
                      <v-icon size="16">mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn icon size="x-small" variant="text" color="error" @click="inventoryStore.remove_detail(detail._uid)">
                      <v-icon size="16">mdi-delete</v-icon>
                    </v-btn>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="di-foot">
                  <td colspan="2" class="text-right font-weight-bold">{{ $t('total') }}:</td>
                  <td class="font-weight-bold fnm-currency-num" style="color:#6D4C41">{{ main.formatDisplayNumber(warehouseTotalWeight(whId)) }} Kg</td>
                  <td></td>
                  <td class="font-weight-bold text-deep-orange fnm-currency-num">{{ main.formatDisplayNumber(warehouseTotalPrice(whId)) }} {{ main.currency }}</td>
                  <td colspan="3"></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Empty state -->
          <div v-else class="text-center text-caption text-medium-emphasis pa-6">
            <v-icon size="40" color="#6D4C4155" class="mb-2">mdi-package-variant-closed</v-icon>
            <div>{{ $t('no_articles_in_warehouse') }}</div>
          </div>

          <!-- Bulk delete -->
          <v-btn
            v-if="selectedItems[whId] && selectedItems[whId].length > 0"
            block rounded variant="tonal" color="error" class="mt-2"
            prepend-icon="mdi-delete-sweep"
            @click="deleteSelected(whId)"
          >{{ $t('delete_selected') }} ({{ selectedItems[whId].length }})</v-btn>
        </v-card-text>
      </v-card>
    </div>

    <!-- Add new warehouse section button -->
    <v-menu v-if="availableWarehouses.length > 0">
      <template #activator="{ props }">
        <v-btn v-bind="props" color="#6D4C41" variant="outlined" rounded block prepend-icon="mdi-warehouse" class="mt-2">
          {{ $t('add_warehouse_section') }}
        </v-btn>
      </template>
      <v-list density="compact" class="rounded-lg">
        <v-list-item
          v-for="wh in availableWarehouses" :key="wh.id"
          @click="addWarehouseSection(wh.id)"
          :title="wh.name"
          prepend-icon="mdi-plus-circle-outline"
        ></v-list-item>
      </v-list>
    </v-menu>

    <!-- All warehouses used message -->
    <div v-else-if="inventoryStore.activeWarehouseSections.length > 0" class="text-center text-caption text-medium-emphasis pa-4">
      <v-icon size="18" color="success" class="mr-1">mdi-check-circle</v-icon>
      {{ $t('all_warehouses_added') }}
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { useInventory } from '@/stores/Inventory';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'DetailInventories',
  data() {
    return {
      entryForms: {},
      selectedItems: {},
      fetchingStock: {},
    };
  },
  mounted() {
    // Init entry forms for any existing warehouse sections
    this.inventoryStore.activeWarehouseSections.forEach(whId => {
      if (!this.entryForms[whId]) this.initEntryForm(whId);
    });
  },
  watch: {
    'inventoryStore.activeWarehouseSections': {
      handler(sections) {
        sections.forEach(whId => {
          if (!this.entryForms[whId]) this.initEntryForm(whId);
        });
      },
      deep: true,
      immediate: true,
    },
  },
  computed: {
    inventoryStore() { return useInventory(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    warehouses() { return this.auth.warehouse.list || []; },
    availableWarehouses() {
      return this.warehouses.filter(w => !this.inventoryStore.activeWarehouseSections.includes(w.id));
    },
    articleTypeOptions() {
      return [
        { title: this.$t('fish'), value: 'FishType' },
        { title: this.$t('guano'), value: 'GuanoType' },
        { title: this.$t('packages'), value: 'PackageType' },
      ];
    },
    packageTypeOptions() {
      return this.auth.package_type.list.map(p => ({
        title: p[`name_${this.lang}`] || p.name_en || p.name,
        value: p.id,
      }));
    },
  },
  methods: {
    initEntryForm(whId) {
      this.entryForms[whId] = {
        stockable_type: null,
        stockable_id: null,
        weight: null,
        unit_price: null,
        total_price: null,
        package_type_id: null,
        nbr_box: null,
      };
      // Force reactivity
      this.entryForms = { ...this.entryForms };
    },

    warehouseName(whId) {
      const wh = this.warehouses.find(w => w.id === whId);
      return wh ? wh.name : `#${whId}`;
    },

    getWarehouseDetails(whId) {
      return this.inventoryStore.getWarehouseDetails(whId);
    },

    warehouseTotalWeight(whId) {
      return this.getWarehouseDetails(whId).reduce((s, d) => s + (parseFloat(d.weight) || 0), 0);
    },

    warehouseTotalPrice(whId) {
      return this.getWarehouseDetails(whId).reduce((s, d) => s + (parseFloat(d.total_price) || 0), 0);
    },

    addWarehouseSection(whId) {
      this.inventoryStore.add_warehouse_section(whId);
      this.initEntryForm(whId);
    },

    removeWarehouseSection(whId) {
      this.inventoryStore.remove_warehouse_section(whId);
      delete this.entryForms[whId];
      this.entryForms = { ...this.entryForms };
    },

    onArticleTypeChange(whId) {
      if (this.entryForms[whId]) {
        this.entryForms[whId].stockable_id = null;
      }
    },

    getStockableOptions(articleType) {
      if (!articleType) return [];
      if (articleType === 'FishType') {
        return this.auth.fish_type.list.map(f => ({
          title: f[`name_${this.lang}`] || f.name_en || f.name,
          value: f.id,
        }));
      }
      if (articleType === 'GuanoType') {
        return this.auth.guano_type.list.map(g => ({
          title: g[`name_${this.lang}`] || g.name_en || g.name,
          value: g.id,
        }));
      }
      if (articleType === 'PackageType') {
        return this.auth.package_type.list.map(p => ({
          title: p[`name_${this.lang}`] || p.name_en || p.name,
          value: p.id,
        }));
      }
      return [];
    },

    getArticleName(detail) {
      if (!detail.stockable_type || !detail.stockable_id) return detail.article_description || '-';
      const opts = this.getStockableOptions(detail.stockable_type);
      return opts.find(o => o.value === detail.stockable_id)?.title || '-';
    },

    getPackageTypeName(pkgId) {
      if (!pkgId) return '-';
      const pkg = this.auth.package_type.list.find(p => p.id === pkgId);
      return pkg ? (pkg[`name_${this.lang}`] || pkg.name_en || pkg.name) : '-';
    },

    typeColor(t) {
      return t === 'FishType' ? '#00ACC1' : t === 'GuanoType' ? '#FB8C00' : t === 'PackageType' ? '#6D4C41' : '#9E9E9E';
    },
    typeIcon(t) {
      return t === 'FishType' ? 'mdi-fish' : t === 'GuanoType' ? 'mdi-leaf' : t === 'PackageType' ? 'mdi-package-variant' : 'mdi-cube-outline';
    },
    typeName(t) {
      return t === 'FishType' ? this.$t('fish') : t === 'GuanoType' ? this.$t('guano') : t === 'PackageType' ? this.$t('packages') : t || '-';
    },

    recalcForm(whId) {
      const f = this.entryForms[whId];
      if (!f) return;
      const w = parseFloat(f.weight) || 0;
      const up = parseFloat(f.unit_price) || 0;
      f.total_price = Math.round(w * up * 100) / 100;

      // Auto-calculate nbr_box from weight and package size
      if (f.package_type_id) {
        const pkg = this.auth.package_type.list.find(p => p.id === f.package_type_id);
        if (pkg && pkg.size > 0) {
          f.nbr_box = Math.round((w / pkg.size) * 100) / 100;
        } else {
          f.nbr_box = null;
        }
      } else {
        f.nbr_box = null;
      }
    },

    isFormValid(whId) {
      const f = this.entryForms[whId];
      if (!f) return false;
      if (!f.stockable_type || !f.stockable_id) return false;
      if (!f.weight || f.weight <= 0) return false;
      return true;
    },

    addArticle(whId) {
      const f = this.entryForms[whId];
      if (!f) return;

      this.inventoryStore.add_detail({
        warehouse_id: whId,
        stockable_type: f.stockable_type,
        stockable_id: f.stockable_id,
        weight: f.weight,
        unit_price: f.unit_price,
        total_price: f.total_price,
        package_type_id: f.package_type_id,
        nbr_box: f.nbr_box,
        warehouse_operation: 'stock_in',
      });

      this.initEntryForm(whId);
    },

    editArticle(whId, detail) {
      this.entryForms[whId] = {
        stockable_type: detail.stockable_type,
        stockable_id: detail.stockable_id,
        weight: detail.weight,
        unit_price: detail.unit_price,
        total_price: detail.total_price,
        package_type_id: detail.package_type_id,
        nbr_box: detail.nbr_box,
      };
      this.entryForms = { ...this.entryForms };
      this.inventoryStore.remove_detail(detail._uid);
    },

    deleteSelected(whId) {
      const uids = this.selectedItems[whId] || [];
      uids.forEach(uid => this.inventoryStore.remove_detail(uid));
      this.selectedItems[whId] = [];
    },

    /**
     * Fetch current warehouse balances and populate the detail table.
     */
    async fetchCurrentStock(whId) {
      this.fetchingStock[whId] = true;
      this.fetchingStock = { ...this.fetchingStock };
      try {
        const r = await axios.get('/api/warehouses/dashboard-data', {
          params: { warehouse_ids: [whId] }
        });
        const balances = r.data.balances || [];

        // Remove existing details for this warehouse
        const existingDetails = this.getWarehouseDetails(whId);
        existingDetails.forEach(d => this.inventoryStore.remove_detail(d._uid));

        // Add fetched balances as new detail lines
        balances.forEach(bal => {
          if (!bal.stockable_type || !bal.stockable_id) return;
          const remainingWeight = parseFloat(bal.remaining_weight) || 0;
          if (remainingWeight <= 0) return;

          this.inventoryStore.add_detail({
            warehouse_id: whId,
            stockable_type: bal.stockable_type,
            stockable_id: bal.stockable_id,
            weight: remainingWeight,
            unit_price: parseFloat(bal.unit_price) || 0,
            total_price: Math.round(remainingWeight * (parseFloat(bal.unit_price) || 0) * 100) / 100,
            package_type_id: bal.package_type_id || null,
            nbr_box: parseFloat(bal.package_count) || null,
            warehouse_operation: 'stock_in',
          });
        });

        this.main.add_snackbar({
          show: true,
          message: this.$t('stock_fetched_success', { count: balances.filter(b => parseFloat(b.remaining_weight) > 0).length }),
          color: 'success',
        });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('stock_fetch_failed'), color: 'error' });
      }
      this.fetchingStock[whId] = false;
      this.fetchingStock = { ...this.fetchingStock };
    },
  },
};
</script>

<style scoped>
.detail-inventories { font-family: 'Inter', sans-serif; }
.di-wh-card { border: 1.5px solid rgba(109,76,65,.15); border-radius: 14px !important; overflow: hidden; }
.di-wh-header { background: rgba(109,76,65,.04); }
.di-table-wrap { overflow: auto; max-height: 400px; border-radius: 10px; border: 1px solid rgba(var(--v-border-color),.12); }
.di-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.di-table th { padding: 8px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; opacity: .6; text-align: center; border-bottom: 1.5px solid rgba(var(--v-border-color),.1); background: rgba(109,76,65,.04); }
.di-table td { padding: 8px 12px; text-align: center; border-bottom: 1px solid rgba(var(--v-border-color),.05); }
.di-row:hover { background: rgba(109,76,65,.04) !important; }
.di-foot td { padding: 8px 12px; background: rgba(109,76,65,.06); border-top: 1.5px solid rgba(109,76,65,.15); font-size: 13px; }
.text-inventory { color: #6D4C41 !important; }
.text-inventory :deep(.v-field) { border-radius: 10px !important; }
</style>
