<template>
  <div class="detail-transfers">
    <!-- Per category sections -->
    <div v-for="(group, gIdx) in groupedDetails" :key="group.category.id" class="mb-5" :ref="'group_' + group.category.id">
      <v-card class="rounded-lg" elevation="0">
        <v-card-title>
          <v-row class="mt-1">
            <v-col cols="6">
              <v-autocomplete
                :model-value="group.category.id"
                :items="allCategoryOptions"
                :label="$t('transfer_category')"
                chips density="compact" variant="outlined" rounded color="#F9A825" class="text-transfer"
                hide-details readonly
              >
                <template #chip="{ item }">
                  <v-chip color="#F9A825" variant="tonal">{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </v-col>
            <v-col class="text-end">
              <v-btn class="pa-2" size="small" color="red" icon="mdi-close" variant="outlined" @click="removeGroup(group.category.id)"></v-btn>
            </v-col>
          </v-row>
        </v-card-title>
        <v-card-text>
          <!-- Section divider -->
          <v-row>
            <v-col class="d-flex align-center">
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#F9A825"></v-divider></div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0"><v-col class="pa-0"><v-icon style="color:#F9A825">mdi-numeric-{{ gIdx + 1 }}-circle-outline</v-icon></v-col></v-row>
                <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("add_details") }}</span></v-col></v-row>
              </div>
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#F9A825"></v-divider></div>
            </v-col>
          </v-row>

          <!-- Entry Form -->
          <v-row class="pt-2">
            <!-- Source Warehouse -->
            <v-col cols="12" md="6" v-if="group.category.allow_warehouse">
              <v-autocomplete
                v-model="entryForms[group.category.id].source_warehouse_id"
                :items="sourceWarehouseOptions"
                :label="$t('source_warehouse')"
                chips density="compact" variant="outlined" rounded color="#F9A825" hide-details clearable
                :loading="loadingWarehouses"
                @update:modelValue="onSourceChange(group.category.id)"
              >
                <template #chip="{ item }"><v-chip color="error" variant="tonal"><v-icon start size="14">mdi-warehouse</v-icon>{{ item.title }}</v-chip></template>
              </v-autocomplete>
            </v-col>

            <!-- Article Type -->
            <v-col cols="12" md="6">
              <template v-if="group.category.article_type === 'text'">
                <v-textarea v-model="entryForms[group.category.id].article_description" :label="$t('type_article')" density="compact" variant="outlined" rounded color="#F9A825" hide-details rows="2" auto-grow></v-textarea>
              </template>
              <template v-else>
                <v-autocomplete
                  v-model="entryForms[group.category.id].stockable_id"
                  :items="getStockableOptions(group.category)"
                  :label="$t('type_article')"
                  chips density="compact" variant="outlined" rounded color="#F9A825" hide-details clearable
                  :disabled="group.category.allow_warehouse && !entryForms[group.category.id].source_warehouse_id"
                  :loading="loadingArticles[group.category.id]"
                  @update:modelValue="onArticleChange(group.category.id)"
                >
                  <template #chip="{ item }"><v-chip color="#F9A825" closable variant="tonal" @click:close="entryForms[group.category.id].stockable_id = null">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </template>
            </v-col>

            <!-- Reception Tracking Number -->
            <v-col cols="12" md="6" v-if="group.category.allow_warehouse">
              <v-autocomplete
                v-model="entryForms[group.category.id].balance_id"
                :items="getBalanceOptions(group.category.id)"
                :label="$t('reception_tracking_number')"
                chips density="compact" variant="outlined" rounded color="#F9A825" hide-details clearable
                :disabled="!entryForms[group.category.id].stockable_id"
                :loading="loadingBalances[group.category.id]"
                @update:modelValue="onBalanceChange(group.category.id)"
              >
                <template #chip="{ item }"><v-chip color="#F9A825" variant="tonal">{{ item.title }}</v-chip></template>
                <template #item="{ item, props }">
                  <v-list-item v-bind="props" :subtitle="$t('remaining_weight') + ': ' + formatNum(item.raw.remainingWeight) + ' Kg - ' + item.raw.dateFormatted"></v-list-item>
                </template>
              </v-autocomplete>
            </v-col>

            <!-- Weight -->
            <v-col cols="12" md="6" v-if="group.category.allow_weight">
              <v-text-field
                v-model.number="entryForms[group.category.id].weight"
                type="number" :label="$t('weight') + ' (Kg)'"
                density="compact" variant="outlined" rounded color="#F9A825" hide-details min="0"
                :max="getMaxWeight(group.category.id)"
                @input="recalcForm(group.category)"
              >
                <template #append-inner v-if="getMaxWeight(group.category.id)">
                  <v-chip size="x-small" color="#F9A825" variant="tonal" class="fnm-currency-num">max: {{ formatNum(getMaxWeight(group.category.id)) }}</v-chip>
                </template>
              </v-text-field>
            </v-col>

            <!-- Destination Warehouse -->
            <v-col cols="12" md="6" v-if="group.category.allow_warehouse">
              <v-autocomplete
                v-model="entryForms[group.category.id].destination_warehouse_id"
                :items="getDestWarehouseOptions(group.category.id)"
                :label="$t('destination_warehouse')"
                chips density="compact" variant="outlined" rounded color="#F9A825" hide-details clearable
                :disabled="!entryForms[group.category.id].source_warehouse_id"
              >
                <template #chip="{ item }"><v-chip color="success" variant="tonal"><v-icon start size="14">mdi-warehouse</v-icon>{{ item.title }}</v-chip></template>
              </v-autocomplete>
            </v-col>

            <!-- Package Type -->
            <v-col cols="12" md="3" v-if="group.category.allow_package_type">
              <v-autocomplete
                v-model="entryForms[group.category.id].package_type_id"
                :items="packageTypeOptions"
                :label="$t('package_type')"
                chips density="compact" variant="outlined" rounded color="#F9A825" hide-details clearable
                @update:modelValue="recalcForm(group.category)"
              >
                <template #chip="{ item }"><v-chip color="#F9A825" variant="tonal" size="small">{{ item.title }}</v-chip></template>
              </v-autocomplete>
            </v-col>

            <!-- Nbr Boxes -->
            <v-col cols="12" md="3" v-if="group.category.allow_nbr_box">
              <v-text-field
                v-model.number="entryForms[group.category.id].nbr_box"
                type="number" :label="$t('nbr_boxes')"
                density="compact" variant="outlined" rounded color="#F9A825" hide-details readonly
                class="fnm-currency-num"
              ></v-text-field>
            </v-col>
          </v-row>

          <!-- ADD ARTICLE -->
          <v-row class="text-center mt-2">
            <v-col>
              <v-btn block rounded variant="tonal" color="#F9A825" @click="addArticleToGroup(group.category)" prepend-icon="mdi-plus-circle-outline" :disabled="!isFormValid(group.category)">{{ $t('add_article') }}</v-btn>
            </v-col>
          </v-row>

          <!-- Articles Table -->
          <v-data-table
            v-if="group.items.length > 0"
            v-model="selectedItems[group.category.id]"
            :headers="getTableHeaders(group.category)"
            :items="group.items"
            item-value="_uid"
            show-select density="compact"
            class="rounded-lg mt-2"
            hide-default-footer :items-per-page="-1"
          >
            <template #item.article="{ item }">
              <v-chip size="small" :color="articleTypeIcon(item.stockable_type).color" variant="tonal">
                <v-icon start size="12">{{ articleTypeIcon(item.stockable_type).icon }}</v-icon>
                {{ getArticleName(item, group.category) }}
              </v-chip>
            </template>
            <template #item.weight="{ item }"><span class="fnm-currency-num">{{ formatNum(item.weight) }}</span> Kg</template>
            <template #item.source_warehouse="{ item }">
              <v-chip v-if="item.source_warehouse_id" size="x-small" color="error" variant="tonal">{{ getWarehouseName(item.source_warehouse_id) }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template #item.destination_warehouse="{ item }">
              <v-chip v-if="item.destination_warehouse_id" size="x-small" color="success" variant="tonal">{{ getWarehouseName(item.destination_warehouse_id) }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template #item.source_tracking="{ item }">
              <v-chip v-if="item.source_tracking_number" size="x-small" color="#F9A825" variant="tonal" class="fnm-currency-num">{{ item.source_tracking_number }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template #item.package="{ item }">
              <span v-if="item.package_type_id">{{ getPackageName(item.package_type_id) }}</span>
              <span v-else class="text-grey">-</span>
            </template>
            <template #item.nbr_box="{ item }"><span class="fnm-currency-num">{{ item.nbr_box != null ? formatNum(item.nbr_box) : '-' }}</span></template>
            <template #item.actions="{ item }">
              <v-btn icon size="x-small" variant="text" color="warning" class="mr-1" @click="editArticle(item, group.category)"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="text" color="error" @click="transferStore.remove_detail(item._uid)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
          <v-btn v-if="selectedItems[group.category.id] && selectedItems[group.category.id].length > 0" block rounded variant="tonal" color="error" class="mt-2" prepend-icon="mdi-delete-sweep" @click="deleteSelected(group.category.id)">
            {{ $t('delete_selected') }} (<span class="fnm-currency-num">{{ selectedItems[group.category.id].length }}</span>)
          </v-btn>
        </v-card-text>
      </v-card>
    </div>

    <!-- Add new section button -->
    <v-menu v-if="availableCategories.length > 0">
      <template #activator="{ props }">
        <v-btn v-bind="props" color="#F9A825" variant="outlined" rounded block prepend-icon="mdi-plus-circle-outline" class="mt-2">
          {{ $t('add_transfer_detail') }}
        </v-btn>
      </template>
      <v-list density="compact" class="rounded-lg">
        <v-list-item v-for="dc in availableCategories" :key="dc.id" @click="addSection(dc)" :title="dc[`name_${lang}`] || dc.name_fr || dc.name_en || `#${dc.id}`" prepend-icon="mdi-plus-circle-outline"></v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>

<script>
import axios from 'axios';
import { useWarehouseTransfer } from '@/stores/WarehouseTransfer';
import { useWarehouseTransferCategory } from '@/stores/WarehouseTransferCategory';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'DetailTransfers',
  props: {
    transferDate: { type: String, default: null },
  },
  data() {
    return {
      entryForms: {},
      activeSections: [],
      selectedItems: {},
      balancesCache: {},   // { catId: [] }
      articlesCache: {},   // { catId: [] }
      loadingBalances: {},
      loadingArticles: {},
      warehousesWithStock: [],
      loadingWarehouses: false,
    };
  },
  computed: {
    transferStore() { return useWarehouseTransfer(); },
    categoryStore() { return useWarehouseTransferCategory(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    allCategories() { return this.categoryStore.list || []; },
    allCategoryOptions() {
      return this.allCategories.map(c => ({ title: c[`name_${this.lang}`] || c.name_fr || c.name_en || `#${c.id}`, value: c.id }));
    },
    availableCategories() {
      return this.allCategories.filter(c => !this.activeSections.includes(c.id));
    },
    groupedDetails() {
      return this.activeSections.map(catId => {
        const category = this.allCategories.find(c => c.id === catId);
        if (!category) return null;
        const items = (this.transferStore.transfer?.details || []).filter(d => d.warehouse_transfer_category_id === catId);
        return { category, items };
      }).filter(Boolean);
    },
    sourceWarehouseOptions() {
      return this.warehousesWithStock.map(w => ({ title: w.name + ' (' + this.formatNum(w.total_stock) + ' Kg)', value: w.id }));
    },
    packageTypeOptions() {
      return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en || p.name_fr || p.name, value: p.id }));
    },
  },
  mounted() {
    this.fetchWarehousesWithStock();
    try {
      const raw = sessionStorage.getItem('detail_transfers_draft');
      if (raw) {
        const data = JSON.parse(raw);
        if (data.entryForms) this.entryForms = data.entryForms;
        if (data.activeSections) this.activeSections = data.activeSections;
      }
    } catch (e) { /* corrupted */ }
    this._syncActiveSections();
  },
  watch: {
    entryForms: { handler() { this._persistLocal(); }, deep: true },
    activeSections: { handler() { this._persistLocal(); }, deep: true },
    'transferStore.transfer.details': { handler() { this._syncActiveSections(); }, deep: true, immediate: true },
  },
  methods: {
    formatNum(v) { return this.main.formatDisplayNumber ? this.main.formatDisplayNumber(v) : v; },
    articleTypeIcon(type) {
      if (type === 'FishType') return { icon: 'mdi-fish', color: '#42A5F5' };
      if (type === 'GuanoType') return { icon: 'mdi-leaf', color: '#66BB6A' };
      if (type === 'PackageType') return { icon: 'mdi-package-variant-closed', color: '#AB47BC' };
      return { icon: 'mdi-help-circle-outline', color: 'grey' };
    },
    getWarehouseName(id) { const w = this.auth.warehouse.list.find(x => x.id === id); return w ? w.name : '-'; },
    getPackageName(id) { const p = this.auth.package_type.list.find(x => x.id === id); return p ? (p[`name_${this.lang}`] || p.name_en || p.name) : '-'; },
    getStockableOptions(category) {
      // If warehouse is enabled, use articles from API (filtered by warehouse stock)
      if (category.allow_warehouse) {
        const articles = this.articlesCache[category.id] || [];
        return articles.map(a => {
          const name = a.stockable
            ? (a.stockable[`name_${this.lang}`] || a.stockable.name_en || a.stockable.name_fr || a.stockable.name || `#${a.stockable_id}`)
            : `#${a.stockable_id}`;
          return { title: name + ' (' + this.formatNum(a.total_remaining) + ' Kg)', value: a.stockable_id };
        });
      }
      // Fallback: use full auth store lists
      const t = category.article_type;
      if (t === 'FishType') {
        let list = this.auth.fish_type.list;
        if (category.fish_type_filter && category.fish_type_filter !== 'all') list = list.filter(f => f.type === category.fish_type_filter);
        return list.map(f => ({ title: f[`name_${this.lang}`] || f.name_en || f.name, value: f.id }));
      }
      if (t === 'GuanoType') return this.auth.guano_type.list.map(g => ({ title: g[`name_${this.lang}`] || g.name_en || g.name, value: g.id }));
      if (t === 'PackageType') return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en || p.name, value: p.id }));
      return [];
    },
    getArticleName(item, category) {
      if (category.article_type === 'text') return item.article_description || '-';
      // Look up pure name without weight suffix
      if (category.allow_warehouse) {
        const articles = this.articlesCache[category.id] || [];
        const match = articles.find(a => a.stockable_id === item.stockable_id);
        if (match?.stockable) {
          return match.stockable[`name_${this.lang}`] || match.stockable.name_en || match.stockable.name_fr || match.stockable.name || `#${item.stockable_id}`;
        }
      }
      // Fallback: look up from auth store
      const t = category.article_type;
      let list = [];
      if (t === 'FishType') list = this.auth.fish_type.list;
      else if (t === 'GuanoType') list = this.auth.guano_type.list;
      else if (t === 'PackageType') list = this.auth.package_type.list;
      const found = list.find(x => x.id === item.stockable_id);
      if (found) return found[`name_${this.lang}`] || found.name_en || found.name || `#${item.stockable_id}`;
      return `#${item.stockable_id || '-'}`;
    },
    getDestWarehouseOptions(catId) {
      const srcId = this.entryForms[catId]?.source_warehouse_id;
      return this.auth.warehouse.list.filter(w => w.id !== srcId).map(w => ({ title: w.name, value: w.id }));
    },
    getBalanceOptions(catId) {
      return (this.balancesCache[catId] || []).map(b => ({
        title: b.reception_tracking_number || `Balance #${b.id}`,
        value: b.id,
        remainingWeight: Number(b.remaining_weight),
        dateFormatted: this.main.formateDate ? this.main.formateDate(b.date) : (b.date ? b.date.substring(0, 10) : '-'),
      }));
    },
    getMaxWeight(catId) {
      const f = this.entryForms[catId];
      if (!f?.balance_id) return null;
      const bal = (this.balancesCache[catId] || []).find(b => b.id === f.balance_id);
      return bal ? Number(bal.remaining_weight) : null;
    },
    initEntryForm(catId) {
      this.entryForms[catId] = { source_warehouse_id: null, stockable_id: null, balance_id: null, weight: null, destination_warehouse_id: null, package_type_id: null, nbr_box: null, article_description: '' };
    },
    addSection(dc) {
      if (!this.activeSections.includes(dc.id)) {
        this.activeSections.push(dc.id);
        this.initEntryForm(dc.id);
      }
    },
    removeGroup(catId) {
      const details = this.transferStore.transfer.details.filter(d => d.warehouse_transfer_category_id === catId);
      details.forEach(d => this.transferStore.remove_detail(d._uid));
      this.activeSections = this.activeSections.filter(id => id !== catId);
      delete this.entryForms[catId];
      delete this.balancesCache[catId];
    },
    async fetchWarehousesWithStock() {
      this.loadingWarehouses = true;
      try {
        const params = {};
        if (this.transferDate) params.max_date = this.transferDate;
        const r = await axios.get('/api/warehouses/with-stock', { params });
        this.warehousesWithStock = r.data.warehouses || [];
      } catch (e) { console.error(e); }
      this.loadingWarehouses = false;
    },
    async fetchArticles(catId) {
      const f = this.entryForms[catId];
      if (!f?.source_warehouse_id) return;
      this.loadingArticles[catId] = true;
      try {
        const params = { warehouse_id: f.source_warehouse_id };
        if (this.transferDate) params.max_date = this.transferDate;
        const r = await axios.get('/api/warehouses/articles', { params });
        this.articlesCache[catId] = r.data.articles || [];
      } catch (e) { console.error(e); }
      this.loadingArticles[catId] = false;
    },
    async onSourceChange(catId) {
      const f = this.entryForms[catId];
      f.stockable_id = null; f.balance_id = null; f.weight = null; f.nbr_box = null;
      this.balancesCache[catId] = [];
      this.articlesCache[catId] = [];
      if (f.source_warehouse_id) await this.fetchArticles(catId);
    },
    async onArticleChange(catId) {
      const f = this.entryForms[catId];
      f.balance_id = null; f.weight = null; f.nbr_box = null;
      this.balancesCache[catId] = [];
      if (!f.source_warehouse_id || !f.stockable_id) return;
      await this.fetchBalances(catId);
    },
    onBalanceChange(catId) {
      const f = this.entryForms[catId];
      f.weight = null; f.nbr_box = null;
    },
    async fetchBalances(catId) {
      const f = this.entryForms[catId];
      const cat = this.allCategories.find(c => c.id === catId);
      if (!cat || !f.source_warehouse_id || !f.stockable_id) return;
      this.loadingBalances[catId] = true;
      try {
        const params = { warehouse_id: f.source_warehouse_id, stockable_type: cat.article_type, stockable_id: f.stockable_id };
        if (this.transferDate) params.max_date = this.transferDate;
        const r = await axios.get('/api/warehouses/balances-for-transfer', { params });
        this.balancesCache[catId] = r.data.balances || [];
      } catch (e) { console.error(e); }
      this.loadingBalances[catId] = false;
    },
    recalcForm(category) {
      const f = this.entryForms[category.id]; if (!f) return;
      if (category.allow_package_type && category.allow_nbr_box && f.package_type_id && f.weight) {
        const pkg = this.auth.package_type.list.find(p => p.id === f.package_type_id);
        if (pkg && pkg.size > 0) { f.nbr_box = Math.round(((parseFloat(f.weight) || 0) / pkg.size) * 100) / 100; return; }
      }
      f.nbr_box = null;
    },
    isFormValid(category) {
      const f = this.entryForms[category.id]; if (!f) return false;
      if (category.article_type === 'text') { if (!f.article_description?.trim()) return false; }
      else { if (!f.stockable_id) return false; }
      if (category.allow_weight && (!f.weight || f.weight <= 0)) return false;
      if (category.allow_weight && this.getMaxWeight(category.id) && f.weight > this.getMaxWeight(category.id)) return false;
      if (category.allow_warehouse && !f.source_warehouse_id) return false;
      if (category.allow_warehouse && !f.destination_warehouse_id) return false;
      if (category.allow_warehouse && f.source_warehouse_id === f.destination_warehouse_id) return false;
      return true;
    },
    async addArticleToGroup(category) {
      const f = this.entryForms[category.id]; if (!f) return;
      const bal = (this.balancesCache[category.id] || []).find(b => b.id === f.balance_id);
      const addedWeight = parseFloat(f.weight) || 0;
      const srcWarehouseId = f.source_warehouse_id;
      const stockableId = f.stockable_id;

      this.transferStore.add_detail(category.id);
      const last = this.transferStore.transfer.details[this.transferStore.transfer.details.length - 1];
      Object.assign(last, {
        article_description: f.article_description || null,
        stockable_type: category.article_type !== 'text' ? category.article_type : null,
        stockable_id: stockableId,
        weight: addedWeight,
        source_warehouse_id: srcWarehouseId,
        source_tracking_number: bal?.reception_tracking_number || null,
        destination_warehouse_id: f.destination_warehouse_id,
        package_type_id: f.package_type_id,
        nbr_box: f.nbr_box,
      });
      this.transferStore.recalc_totals();

      // Locally subtract weight from cached data so UI updates instantly
      if (bal && addedWeight > 0) {
        bal.remaining_weight = Math.max(0, Number(bal.remaining_weight) - addedWeight);
        // Remove balance from cache if depleted
        if (bal.remaining_weight <= 0) {
          this.balancesCache[category.id] = (this.balancesCache[category.id] || []).filter(b => b.id !== bal.id);
        }
      }
      // Locally update article cache weight
      if (category.allow_warehouse && srcWarehouseId) {
        const articles = this.articlesCache[category.id] || [];
        const art = articles.find(a => a.stockable_id === stockableId);
        if (art) {
          art.total_remaining = Math.max(0, Number(art.total_remaining) - addedWeight);
          if (art.total_remaining <= 0) {
            this.articlesCache[category.id] = articles.filter(a => a.stockable_id !== stockableId);
          }
        }
      }
      // Locally update warehouse cache weight
      const wh = this.warehousesWithStock.find(w => w.id === srcWarehouseId);
      if (wh) {
        wh.total_stock = Math.max(0, Number(wh.total_stock) - addedWeight);
        if (wh.total_stock <= 0) {
          this.warehousesWithStock = this.warehousesWithStock.filter(w => w.id !== srcWarehouseId);
        }
      }

      // Keep source warehouse selected, reset the rest
      this.entryForms[category.id] = {
        source_warehouse_id: srcWarehouseId,
        stockable_id: null, balance_id: null, weight: null,
        destination_warehouse_id: null, package_type_id: null, nbr_box: null,
        article_description: '',
      };
    },
    editArticle(item, category) {
      this.entryForms[category.id] = {
        source_warehouse_id: item.source_warehouse_id,
        stockable_id: item.stockable_id,
        balance_id: null, weight: item.weight,
        destination_warehouse_id: item.destination_warehouse_id,
        package_type_id: item.package_type_id,
        nbr_box: item.nbr_box,
        article_description: item.article_description || '',
      };
      if (item.source_warehouse_id && item.stockable_id) {
        this.fetchBalances(category.id).then(() => {
          const bal = (this.balancesCache[category.id] || []).find(b => b.reception_tracking_number === item.source_tracking_number);
          if (bal) this.entryForms[category.id].balance_id = bal.id;
        });
      }
      this.transferStore.remove_detail(item._uid);
    },
    deleteSelected(catId) {
      (this.selectedItems[catId] || []).forEach(uid => this.transferStore.remove_detail(uid));
      this.selectedItems[catId] = [];
    },
    getTableHeaders(cat) {
      const h = [{ title: this.$t('type_article'), key: 'article', sortable: false }];
      if (cat.allow_weight) h.push({ title: this.$t('weight') + ' (Kg)', key: 'weight', sortable: false, align: 'center' });
      if (cat.allow_warehouse) {
        h.push({ title: this.$t('source_warehouse'), key: 'source_warehouse', sortable: false, align: 'center' });
        h.push({ title: this.$t('reception_tracking_number'), key: 'source_tracking', sortable: false, align: 'center' });
        h.push({ title: this.$t('destination_warehouse'), key: 'destination_warehouse', sortable: false, align: 'center' });
      }
      if (cat.allow_package_type) h.push({ title: this.$t('package_type'), key: 'package', sortable: false, align: 'center' });
      if (cat.allow_nbr_box) h.push({ title: this.$t('nbr_boxes'), key: 'nbr_box', sortable: false, align: 'center' });
      h.push({ title: this.$t('actions'), key: 'actions', sortable: false, width: 80, align: 'center' });
      return h;
    },
    _persistLocal() {
      try { sessionStorage.setItem('detail_transfers_draft', JSON.stringify({ entryForms: this.entryForms, activeSections: this.activeSections })); } catch (e) {}
    },
    _syncActiveSections() {
      const details = this.transferStore.transfer?.details || [];
      if (!details.length) return;
      const catIds = [...new Set(details.map(d => d.warehouse_transfer_category_id).filter(Boolean))];
      catIds.forEach(catId => {
        if (!this.activeSections.includes(catId)) {
          this.activeSections.push(catId);
          if (!this.entryForms[catId]) this.initEntryForm(catId);
        }
      });
    },
  },
};
</script>

<style scoped>
.detail-transfers { font-family: 'Inter', sans-serif; }
.text-transfer { color: #F9A825 !important; }
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
</style>
