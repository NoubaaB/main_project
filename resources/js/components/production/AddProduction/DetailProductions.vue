<template>
  <div class="detail-productions">
    <div v-for="(group, gIdx) in groupedDetails" :key="group.category.id" class="mb-5" :ref="'group_' + group.category.id">
      <v-card class="rounded-lg" elevation="0">
        <v-card-title>
          <v-row class="mt-1">
            <v-col cols="6">
              <v-autocomplete :model-value="group.category.id" :items="detailCats.map(c => ({ title: c[`name_${lang}`] || c.name_fr || c.name_en || `#${c.id}`, value: c.id }))" :label="$t('detail_production_category')" chips density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details readonly>
                <template #chip="{ item }"><v-chip  color="#00897B" variant="tonal">{{ item.title }}</v-chip></template>
              </v-autocomplete>
            </v-col>
            <v-col class="text-end"><v-btn class="pa-2" size="small" color="red" icon="mdi-close" variant="outlined" @click="removeGroup(group.category.id)"></v-btn></v-col>
          </v-row>
        </v-card-title>
        <v-card-text>
          <v-row><v-col class="d-flex align-center">
            <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#00897B"></v-divider></div>
            <div class="px-2 d-block text-center">
              <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-prod">mdi-numeric-{{ gIdx + 1 }}-circle-outline</v-icon></v-col></v-row>
              <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("add_details") }}</span></v-col></v-row>
            </div>
            <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#00897B"></v-divider></div>
          </v-col></v-row>

          <v-row class="pt-2">
            <v-col cols="12" md="6">
              <template v-if="group.category.article_type === 'text'">
                <v-textarea v-model="entryForms[group.category.id].article_description" :label="$t('type_article')" density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details rows="2" auto-grow :counter="1000" :maxlength="1000"></v-textarea>
              </template>
              <template v-else>
                <v-autocomplete v-model="entryForms[group.category.id].stockable_id" :items="getStockOpts(group.category)" :label="$t('type_article')" chips density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details>
                  <template #chip="{ item }"><v-chip  color="#00897B" closable variant="tonal" @click:close="entryForms[group.category.id].stockable_id = null">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </template>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_warehouse">
              <v-autocomplete v-model="entryForms[group.category.id].warehouse_id" :items="warehouseOptions" :label="$t('warehouse')" chips density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details clearable>
                <template #chip="{ item }"><v-chip  color="#00897B" closable variant="tonal" @click:close="entryForms[group.category.id].warehouse_id = null">{{ item.title }}</v-chip></template>
              </v-autocomplete>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_weight">
              <v-text-field v-model.number="entryForms[group.category.id].weight" type="number" :label="$t('weight') + ' (Kg)'" density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details min="0" @input="onWeightInput(group.category)"></v-text-field>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_weight">
              <v-text-field v-model.number="entryForms[group.category.id].weight_invoice" type="number" :label="$t('total_weight_invoice') + ' (Kg)'" density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details min="0"></v-text-field>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_unit_price">
              <v-text-field v-model.number="entryForms[group.category.id].unit_price" type="number" :label="$t('unit_price') + ' (Dh)'" density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details min="0" @input="recalcForm(group.category)"></v-text-field>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_price">
              <v-text-field v-model.number="entryForms[group.category.id].total_price" type="number" :label="$t('total_price') + ' (Dh)'" density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details min="0" readonly></v-text-field>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_package_type">
              <v-autocomplete v-model="entryForms[group.category.id].package_type_id" :items="packageTypeOptions" :label="$t('package_type')" chips density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details clearable @update:modelValue="recalcForm(group.category)"></v-autocomplete>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_nbr_box">
              <v-text-field v-model.number="entryForms[group.category.id].nbr_box" type="number" :label="$t('nbr_boxes')" density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details min="0" readonly></v-text-field>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_detail_truck_id">
              <v-autocomplete v-model="entryForms[group.category.id].truck_id" :items="truckOptions" :label="$t('truck')" chips density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details clearable @update:search="val => truckSearch[group.category.id] = val" @keydown.enter.prevent="createTruckIfNew(group.category.id)">
                <template #chip="{ item }"><v-chip  color="#00897B" closable variant="tonal" @click:close="entryForms[group.category.id].truck_id = null">{{ item.title }}</v-chip></template>
                <template #no-data>
                  <v-list-item v-if="truckSearch[group.category.id]?.trim()">
                    <v-list-item-title class="text-caption">
                      <v-icon size="16" class="mr-1">mdi-plus-circle-outline</v-icon>
                      {{ $t('press_enter_to_create') }} "<strong>{{ truckSearch[group.category.id] }}</strong>"
                    </v-list-item-title>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>
          </v-row>

          <v-row class="text-center mt-2"><v-col><v-btn block rounded variant="tonal" color="#00897B" @click="addArticle(group.category)" prepend-icon="mdi-plus-circle-outline" :disabled="!isFormValid(group.category)">{{ $t('add_article') }}</v-btn></v-col></v-row>

          <v-data-table v-if="group.items.length > 0" v-model="selectedItems[group.category.id]" :headers="getHeaders(group.category)" :items="getFilteredItems(group)" item-value="_uid" show-select density="compact" class="rounded-lg mt-2" hide-default-footer :items-per-page="-1">
            <template v-for="col in getFilterableColumns(group.category)" :key="'header-'+col.key" #[`header.${col.key}`]="{ column }">
              <div class="d-flex align-center" style="white-space:nowrap">
                <span>{{ column.title }}</span>
                <v-menu :close-on-content-click="false" location="bottom" offset="4">
                  <template #activator="{ props: menuProps }">
                    <v-btn v-bind="menuProps" icon variant="text" size="x-small" class="ml-1" :color="hasFilter(group.category.id, col.key) ? 'info' : 'grey'"><v-icon size="14">mdi-filter</v-icon></v-btn>
                  </template>
                  <v-card min-width="240" class="rounded-lg" elevation="8">
                    <v-card-text class="pa-3">
                      <v-autocomplete :model-value="getColumnFilter(group.category.id, col.key)" @update:modelValue="val => setColumnFilter(group.category.id, col.key, val)" :items="getFilterOptions(group, col.key)" :label="$t('filter')" multiple chips closable-chips density="compact" variant="outlined" rounded color="#00897B" class="text-prod" hide-details></v-autocomplete>
                      <v-btn v-if="hasFilter(group.category.id, col.key)" block variant="text" color="error" size="small" class="mt-2" @click="clearColumnFilter(group.category.id, col.key)">{{ $t('clear_filter') }}</v-btn>
                    </v-card-text>
                  </v-card>
                </v-menu>
              </div>
            </template>
            <template #item.article="{ item }"><v-chip size="small" color="#00897B" variant="tonal">{{ getArticleName(item, group.category) }}</v-chip></template>
            <template #item.weight="{ item }">{{ main.formatDisplayNumber(item.weight) }}</template>
            <template #item.weight_invoice="{ item }">{{ main.formatDisplayNumber(item.weight_invoice) }}</template>
            <template #item.unit_price="{ item }">{{ main.formatDisplayNumber(item.unit_price) }}</template>
            <template #item.total_price="{ item }">{{ main.formatDisplayNumber(item.total_price) }}</template>
            <template #item.warehouse="{ item }"><v-chip v-if="item.warehouse_id" size="small" color="#00897B" variant="tonal">{{ getWarehouseName(item.warehouse_id) }}</v-chip></template>
            <template #item.actions="{ item }">
              <v-btn icon size="x-small" variant="text" color="warning" class="mr-1" @click="editArticle(item, group.category)"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="text" color="error" @click="prodStore.remove_detail(item._uid)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
          <v-btn v-if="selectedItems[group.category.id] && selectedItems[group.category.id].length > 0" block rounded variant="tonal" color="error" class="mt-2" prepend-icon="mdi-delete-sweep" @click="deleteSelected(group.category.id)">{{ $t('delete_selected') }} ({{ selectedItems[group.category.id].length }})</v-btn>
        </v-card-text>
      </v-card>
    </div>

    <v-menu v-if="availableCats.length > 0">
      <template #activator="{ props }"><v-btn v-bind="props" color="#00897B" variant="outlined" rounded block prepend-icon="mdi-plus-circle-outline" class="mt-2">{{ $t('add_detail_section') }}</v-btn></template>
      <v-list density="compact" class="rounded-lg"><v-list-item v-for="dc in availableCats" :key="dc.id" @click="addSection(dc)" :title="dc[`name_${lang}`] || dc.name_fr || `#${dc.id}`" prepend-icon="mdi-plus-circle-outline"></v-list-item></v-list>
    </v-menu>
  </div>
</template>

<script>
import axios from 'axios';
import { useProduction } from '@/stores/Production';
import { useMain } from '@/stores/Main';
import { useDetailProductionCategory } from '@/stores/DetailProductionCategory';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'DetailProductions',
  data() { return { entryForms: {}, activeSections: [], truckSearch: {}, columnFilters: {}, selectedItems: {} }; },
  mounted() {
    try {
      const raw = sessionStorage.getItem('detail_productions_draft');
      if (raw) { const data = JSON.parse(raw); if (data.entryForms) this.entryForms = data.entryForms; if (data.activeSections) this.activeSections = data.activeSections; }
    } catch (e) { /* corrupted */ }
    this._syncActiveSections();
  },
  watch: {
    entryForms: { handler() { this._persistLocal(); }, deep: true },
    activeSections: { handler() { this._persistLocal(); }, deep: true },
    'prodStore.production.details': {
      handler() { this._syncActiveSections(); },
      deep: true,
      immediate: true,
    },
  },
  computed: {
    prodStore() { return useProduction(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    detailCats() { return useDetailProductionCategory().list.filter(d => d.production_category_id === this.prodStore.production?.production_category_id); },
    availableCats() { return this.detailCats.filter(c => !this.activeSections.includes(c.id)); },
    groupedDetails() {
      return this.activeSections.map(catId => {
        const category = this.detailCats.find(c => c.id === catId);
        if (!category) return null;
        return { category, items: (this.prodStore.production?.details || []).filter(d => d.detail_production_category_id === catId) };
      }).filter(Boolean);
    },
    warehouseOptions() { return this.auth.warehouse.list.map(w => ({ title: w.name, value: w.id })); },
    truckOptions() { return this.auth.truck.list.map(t => ({ title: t.registration_plate, value: t.id })); },
    packageTypeOptions() { return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en, value: p.id })); },
  },
  methods: {
    initForm(catId, category) { const defaultDesc = category && category.article_type === 'text' ? (category[`name_${this.lang}`] || category.name_fr || category.name_en || '') : ''; this.entryForms[catId] = { article_description: defaultDesc, stockable_id: null, weight: null, unit_price: null, total_price: null, weight_invoice: null, warehouse_id: null, truck_id: null, package_type_id: null, nbr_box: null }; },
    isFormValid(category) {
      const f = this.entryForms[category.id];
      if (!f) return false;
      if (category.article_type === 'text') { if (!f.article_description?.trim()) return false; }
      else { if (!f.stockable_id) return false; }
      if (category.allow_weight && (!f.weight || f.weight <= 0)) return false;
      if (category.allow_unit_price && (!f.unit_price || f.unit_price <= 0)) return false;
      if (category.allow_package_type && !f.package_type_id) return false;
      if (category.allow_warehouse && !f.warehouse_id) return false;
      return true;
    },
    addSection(dc) { if (!this.activeSections.includes(dc.id)) { this.activeSections.push(dc.id); this.initForm(dc.id, dc); } },
    removeGroup(catId) { const items = this.prodStore.production.details.filter(d => d.detail_production_category_id === catId); items.forEach(d => this.prodStore.remove_detail(d._uid)); this.activeSections = this.activeSections.filter(id => id !== catId); delete this.entryForms[catId]; },
    addArticle(cat) {
      const f = this.entryForms[cat.id]; if (!f) return;
      if (cat.allow_weight && cat.allow_unit_price && !cat.allow_price) f.total_price = (parseFloat(f.weight) || 0) * (parseFloat(f.unit_price) || 0);
      this.prodStore.add_detail(cat.id);
      const last = this.prodStore.production.details[this.prodStore.production.details.length - 1];
      Object.assign(last, { article_description: f.article_description || null, stockable_type: cat.article_type !== 'text' ? cat.article_type : null, stockable_id: f.stockable_id, weight: f.weight, unit_price: f.unit_price, total_price: f.total_price, weight_invoice: f.weight_invoice, warehouse_id: f.warehouse_id, warehouse_operation: cat.warehouse_operation || (f.warehouse_id ? 'stock_in' : null), truck_id: f.truck_id, package_type_id: f.package_type_id, nbr_box: f.nbr_box });
      this.prodStore.recalc_totals(); this.initForm(cat.id, cat);
      this.$nextTick(() => { const c = this.$refs['group_' + cat.id]; const el = c?.[0] || c; if (el) { const input = el.querySelector('input'); if (input) input.focus(); } });
    },
    editArticle(item, cat) { this.entryForms[cat.id] = { article_description: item.article_description || '', stockable_id: item.stockable_id, weight: item.weight, unit_price: item.unit_price, total_price: item.total_price, weight_invoice: item.weight_invoice, warehouse_id: item.warehouse_id, truck_id: item.truck_id, package_type_id: item.package_type_id, nbr_box: item.nbr_box }; this.prodStore.remove_detail(item._uid); },
    getStockOpts(category) {
      const articleType = category.article_type || category;
      if (articleType === 'FishType') {
        let fishList = this.auth.fish_type.list;
        const filter = category.fish_type_filter;
        if (filter && filter !== 'all') { fishList = fishList.filter(f => f.type === filter); }
        return fishList.map(f => ({ title: f[`name_${this.lang}`] || f.name_en, value: f.id }));
      }
      if (articleType === 'GuanoType') return this.auth.guano_type.list.map(g => ({ title: g[`name_${this.lang}`] || g.name_en, value: g.id }));
      if (articleType === 'PackageType') return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en, value: p.id }));
      return [];
    },
    getArticleName(item, cat) { if (cat.article_type === 'text') return item.article_description || '-'; return this.getStockOpts(cat).find(o => o.value === item.stockable_id)?.title || '-'; },
    getWarehouseName(id) { return this.auth.warehouse.list.find(w => w.id === id)?.name || `#${id}`; },
    getHeaders(cat) {
      const h = [{ title: this.$t('type_article'), key: 'article', sortable: false }];
      if (cat.allow_weight) h.push({ title: this.$t('weight') + ' (Kg)', key: 'weight', sortable: false });
      if (cat.allow_weight) h.push({ title: this.$t('total_weight_invoice'), key: 'weight_invoice', sortable: false });
      if (cat.allow_unit_price) h.push({ title: this.$t('unit_price'), key: 'unit_price', sortable: false });
      if (cat.allow_price) h.push({ title: this.$t('total_price'), key: 'total_price', sortable: false });
      if (cat.allow_warehouse) h.push({ title: this.$t('warehouse'), key: 'warehouse', sortable: false });
      h.push({ title: this.$t('actions'), key: 'actions', sortable: false, width: 80 });
      return h;
    },
    getFilterableColumns(cat) { return this.getHeaders(cat).filter(h => h.key !== 'actions'); },
    getDisplayValue(item, key, cat) {
      if (key === 'article') return this.getArticleName(item, cat);
      if (key === 'warehouse') return item.warehouse_id ? this.getWarehouseName(item.warehouse_id) : '';
      if (['weight', 'weight_invoice', 'unit_price', 'total_price'].includes(key)) return item[key] != null ? String(item[key]) : '';
      return item[key] != null ? String(item[key]) : '';
    },
    getFilteredItems(group) {
      const filters = this.columnFilters[group.category.id]; if (!filters) return group.items;
      return group.items.filter(item => Object.entries(filters).every(([key, sel]) => !sel || sel.length === 0 || sel.includes(this.getDisplayValue(item, key, group.category))));
    },
    getFilterOptions(group, colKey) {
      const filters = this.columnFilters[group.category.id] || {};
      let items = group.items;
      Object.entries(filters).forEach(([key, sel]) => { if (key !== colKey && sel && sel.length > 0) items = items.filter(i => sel.includes(this.getDisplayValue(i, key, group.category))); });
      const vals = [...new Set(items.map(i => this.getDisplayValue(i, colKey, group.category)).filter(v => v !== ''))];
      return vals.sort().map(v => ({ title: v, value: v }));
    },
    hasFilter(catId, colKey) { const f = this.columnFilters[catId]; return f && f[colKey] && f[colKey].length > 0; },
    getColumnFilter(catId, colKey) { return this.columnFilters[catId]?.[colKey] || []; },
    setColumnFilter(catId, colKey, val) { if (!this.columnFilters[catId]) this.columnFilters[catId] = {}; this.columnFilters[catId][colKey] = val; },
    clearColumnFilter(catId, colKey) { if (this.columnFilters[catId]) this.columnFilters[catId][colKey] = []; },
    recalcForm(category) {
      const f = this.entryForms[category.id];
      if (!f) return;
      const w = parseFloat(f.weight) || 0;
      const up = parseFloat(f.unit_price) || 0;
      if (category.allow_weight && category.allow_unit_price) {
        f.total_price = Math.round(w * up * 100) / 100;
      }
      if (category.allow_package_type && category.allow_nbr_box && f.package_type_id) {
        const pkg = this.auth.package_type.list.find(p => p.id === f.package_type_id);
        if (pkg && pkg.size > 0) { f.nbr_box = Math.round((w / pkg.size) * 100) / 100; }
      } else { f.nbr_box = null; }
    },
    onWeightInput(category) {
      const f = this.entryForms[category.id];
      if (f) f.weight_invoice = f.weight;
      this.recalcForm(category);
    },
    async createTruckIfNew(catId) {
      const search = (this.truckSearch[catId] || '').trim();
      if (!search) return;
      const existing = this.auth.truck.list.find(t => t.registration_plate.toLowerCase() === search.toLowerCase());
      if (existing) {
        this.entryForms[catId].truck_id = existing.id;
        this.truckSearch[catId] = '';
        return;
      }
      try {
        const res = await axios.post('/api/truck', { registration_plate: search });
        const newTruck = res.data.truck;
        this.auth.truck.list.push(newTruck);
        this.entryForms[catId].truck_id = newTruck.id;
        this.truckSearch[catId] = '';
        this.main.add_snackbar({ show: true, message: this.$t('truck_created', { name: newTruck.registration_plate }), color: 'success' });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('truck_create_error'), color: 'error' });
      }
    },
    deleteSelected(catId) {
      const uids = this.selectedItems[catId] || [];
      uids.forEach(uid => this.prodStore.remove_detail(uid));
      this.selectedItems[catId] = [];
    },
    _persistLocal() {
      try { sessionStorage.setItem('detail_productions_draft', JSON.stringify({ entryForms: this.entryForms, activeSections: this.activeSections })); } catch (e) { /* quota */ }
    },
    _syncActiveSections() {
      const details = this.prodStore.production?.details || [];
      if (!details.length) return;
      const catIds = [...new Set(details.map(d => d.detail_production_category_id).filter(Boolean))];
      catIds.forEach(catId => {
        if (!this.activeSections.includes(catId)) {
          this.activeSections.push(catId);
          const cat = this.detailCats.find(c => c.id === catId);
          if (cat && !this.entryForms[catId]) this.initForm(catId, cat);
        }
      });
    },
  },
};
</script>

<style scoped>
.text-prod { color: #00897B !important; }
</style>
