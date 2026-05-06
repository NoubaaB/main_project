<template>
  <div class="detail-inventories">
    <!-- Per detail-category sections -->
    <div v-for="(group, gIdx) in groupedDetails" :key="group.category.id" class="mb-5" :ref="'group_' + group.category.id">
      <v-card class="rounded-lg" elevation="0">
        <v-card-title>
          <v-row class="mt-1">
            <v-col cols="6">
              <v-autocomplete
                :model-value="group.category.id"
                :items="detailCategories.map(c => ({ title: c[`name_${lang}`] || c.name_fr || c.name_en || `#${c.id}`, value: c.id }))"
                :label="$t('detail_inventory_category')"
                chips density="compact" variant="outlined" rounded color="#6D4C41"
                hide-details readonly
              >
                <template #chip="{ item }"><v-chip color="#6D4C41" variant="tonal">{{ item.title }}</v-chip></template>
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
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#6D4C41"></v-divider></div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0"><v-col class="pa-0"><v-icon style="color:#6D4C41">mdi-numeric-{{ gIdx + 1 }}-circle-outline</v-icon></v-col></v-row>
                <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("add_details") }}</span></v-col></v-row>
              </div>
              <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#6D4C41"></v-divider></div>
            </v-col>
          </v-row>

          <!-- Entry Form Fields -->
          <v-row class="pt-2">
            <!-- Type Article -->
            <v-col cols="12" md="6">
              <template v-if="group.category.article_type === 'text'">
                <v-textarea v-model="entryForms[group.category.id].article_description" :label="$t('type_article')" density="compact" variant="outlined" rounded color="#6D4C41" hide-details rows="2" auto-grow :counter="1000" :maxlength="1000"></v-textarea>
              </template>
              <template v-else>
                <v-autocomplete v-model="entryForms[group.category.id].stockable_id" :items="getStockableOptions(group.category)" :label="$t('type_article')" chips density="compact" variant="outlined" rounded color="#6D4C41" hide-details>
                  <template #chip="{ item }"><v-chip color="#6D4C41" closable variant="tonal" @click:close="entryForms[group.category.id].stockable_id = null">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </template>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_weight">
              <v-text-field v-model.number="entryForms[group.category.id].weight" type="number" :label="$t('weight') + ' (Kg)'" density="compact" variant="outlined" rounded color="#6D4C41" hide-details min="0" @input="recalcForm(group.category)"></v-text-field>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_unit_price">
              <v-text-field v-model.number="entryForms[group.category.id].unit_price" type="number" :label="$t('unit_price') + ' (Dh)'" density="compact" variant="outlined" rounded color="#6D4C41" hide-details min="0" @input="recalcForm(group.category)"></v-text-field>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_price">
              <v-text-field v-model.number="entryForms[group.category.id].total_price" type="number" :label="$t('total_price') + ' (Dh)'" density="compact" variant="outlined" rounded color="#6D4C41" hide-details min="0" readonly></v-text-field>
            </v-col>
            <!-- Warehouse -->
            <v-col cols="12" md="6" v-if="group.category.allow_warehouse">
              <v-autocomplete v-model="entryForms[group.category.id].warehouse_id" :items="warehouseOptions" :label="$t('warehouse')" chips density="compact" variant="outlined" rounded color="#6D4C41" hide-details clearable>
                <template #chip="{ item }"><v-chip color="#6D4C41" closable variant="tonal" @click:close="entryForms[group.category.id].warehouse_id = null">{{ item.title }}</v-chip></template>
              </v-autocomplete>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_package_type">
              <v-autocomplete v-model="entryForms[group.category.id].package_type_id" :items="packageTypeOptions" :label="$t('package_type')" chips density="compact" variant="outlined" rounded color="#6D4C41" hide-details clearable @update:modelValue="recalcForm(group.category)"></v-autocomplete>
            </v-col>
            <v-col cols="12" md="6" v-if="group.category.allow_nbr_box">
              <v-text-field v-model.number="entryForms[group.category.id].nbr_box" type="number" :label="$t('nbr_boxes')" density="compact" variant="outlined" rounded color="#6D4C41" hide-details min="0" readonly></v-text-field>
            </v-col>
          </v-row>

          <!-- ADD ARTICLE button -->
          <v-row class="text-center mt-2">
            <v-col>
              <v-btn block rounded variant="tonal" color="#6D4C41" @click="addArticleToGroup(group.category)" prepend-icon="mdi-plus-circle-outline" :disabled="!isFormValid(group.category)">{{ $t('add_article') }}</v-btn>
            </v-col>
          </v-row>

          <!-- Articles Table -->
          <v-data-table
            v-if="group.items.length > 0"
            v-model="selectedItems[group.category.id]"
            :headers="getTableHeaders(group.category)"
            :items="group.items"
            item-value="_uid"
            show-select
            density="compact"
            class="rounded-lg mt-2"
            hide-default-footer
            :items-per-page="-1"
          >
            <template #item.article="{ item }">
              <v-chip size="small" color="#6D4C41" variant="tonal">{{ getArticleName(item, group.category) }}</v-chip>
            </template>
            <template #item.weight="{ item }">{{ main.formatDisplayNumber(item.weight) }}</template>
            <template #item.unit_price="{ item }">{{ main.formatDisplayNumber(item.unit_price) }}</template>
            <template #item.total_price="{ item }">{{ main.formatDisplayNumber(item.total_price) }}</template>
            <template #item.actions="{ item }">
              <v-btn icon size="x-small" variant="text" color="warning" class="mr-1" @click="editArticle(item, group.category)"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="text" color="error" @click="inventoryStore.remove_detail(item._uid)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
          <v-btn v-if="selectedItems[group.category.id] && selectedItems[group.category.id].length > 0" block rounded variant="tonal" color="error" class="mt-2" prepend-icon="mdi-delete-sweep" @click="deleteSelected(group.category.id)">
            {{ $t('delete_selected') }} ({{ selectedItems[group.category.id].length }})
          </v-btn>
        </v-card-text>
      </v-card>
    </div>

    <!-- Add new section button -->
    <v-menu v-if="availableCategories.length > 0">
      <template #activator="{ props }">
        <v-btn v-bind="props" color="#6D4C41" variant="outlined" rounded block prepend-icon="mdi-plus-circle-outline" class="mt-2">
          {{ $t('add_detail_inventory') }}
        </v-btn>
      </template>
      <v-list density="compact" class="rounded-lg">
        <v-list-item v-for="dc in availableCategories" :key="dc.id" @click="addSection(dc)" :title="dc[`name_${lang}`] || dc.name_fr || dc.name_en || `#${dc.id}`" prepend-icon="mdi-plus-circle-outline"></v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>

<script>
import { useInventory } from '@/stores/Inventory';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'DetailInventories',
  data() {
    return {
      entryForms: {},
      activeSections: [],
      selectedItems: {},
    };
  },
  mounted() {
    try {
      const raw = sessionStorage.getItem('detail_inventories_draft');
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
    'inventoryStore.inventory.details': {
      handler() { this._syncActiveSections(); },
      deep: true,
      immediate: true,
    },
  },
  computed: {
    inventoryStore() { return useInventory(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    detailCategories() {
      return this.auth.detail_inventory_category.list;
    },
    availableCategories() {
      return this.detailCategories.filter(c => !this.activeSections.includes(c.id));
    },
    groupedDetails() {
      return this.activeSections.map(catId => {
        const category = this.detailCategories.find(c => c.id === catId);
        if (!category) return null;
        const items = (this.inventoryStore.inventory?.details || []).filter(d => d.detail_inventory_category_id === catId);
        return { category, items };
      }).filter(Boolean);
    },
    packageTypeOptions() { return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en, value: p.id })); },
    warehouseOptions() { return this.auth.warehouse.list.map(w => ({ title: w.name, value: w.id })); },
  },
  methods: {
    initEntryForm(catId, category) {
      const defaultDesc = category && category.article_type === 'text' ? (category[`name_${this.lang}`] || category.name_fr || category.name_en || '') : '';
      this.entryForms[catId] = {
        article_description: defaultDesc, stockable_id: null, weight: null, unit_price: null, total_price: null,
        package_type_id: null, nbr_box: null, warehouse_id: null,
      };
    },
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
    addSection(dc) {
      if (!this.activeSections.includes(dc.id)) {
        this.activeSections.push(dc.id);
        this.initEntryForm(dc.id, dc);
      }
    },
    removeGroup(catId) {
      const details = this.inventoryStore.inventory.details.filter(d => d.detail_inventory_category_id === catId);
      details.forEach(d => this.inventoryStore.remove_detail(d._uid));
      this.activeSections = this.activeSections.filter(id => id !== catId);
      delete this.entryForms[catId];
    },
    addArticleToGroup(category) {
      const form = this.entryForms[category.id];
      if (!form) return;
      if (category.allow_weight && category.allow_unit_price && !category.allow_price) {
        form.total_price = (parseFloat(form.weight) || 0) * (parseFloat(form.unit_price) || 0);
      }
      this.inventoryStore.add_detail(category.id);
      const lastDetail = this.inventoryStore.inventory.details[this.inventoryStore.inventory.details.length - 1];
      Object.assign(lastDetail, {
        article_description: form.article_description || null,
        stockable_type: category.article_type !== 'text' ? category.article_type : null,
        stockable_id: form.stockable_id,
        weight: form.weight,
        unit_price: form.unit_price,
        total_price: form.total_price,
        package_type_id: form.package_type_id,
        nbr_box: form.nbr_box,
        warehouse_id: form.warehouse_id,
        warehouse_operation: category.warehouse_operation || (form.warehouse_id ? 'stock_in' : null),
      });
      this.inventoryStore.recalc_totals();
      this.initEntryForm(category.id, category);
      this.$nextTick(() => {
        const container = this.$refs['group_' + category.id];
        const el = container?.[0] || container;
        if (el) { const input = el.querySelector('input'); if (input) input.focus(); }
      });
    },
    editArticle(item, category) {
      this.entryForms[category.id] = {
        article_description: item.article_description || '',
        stockable_id: item.stockable_id,
        weight: item.weight,
        unit_price: item.unit_price,
        total_price: item.total_price,
        package_type_id: item.package_type_id,
        nbr_box: item.nbr_box,
        warehouse_id: item.warehouse_id,
      };
      this.inventoryStore.remove_detail(item._uid);
    },
    getStockableOptions(category) {
      const articleType = category.article_type || category;
      if (articleType === 'FishType') {
        let fishList = this.auth.fish_type.list;
        const filter = category.fish_type_filter;
        if (filter && filter !== 'all') fishList = fishList.filter(f => f.type === filter);
        return fishList.map(f => ({ title: f[`name_${this.lang}`] || f.name_en, value: f.id }));
      }
      if (articleType === 'GuanoType') return this.auth.guano_type.list.map(g => ({ title: g[`name_${this.lang}`] || g.name_en, value: g.id }));
      if (articleType === 'PackageType') return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en, value: p.id }));
      return [];
    },
    getArticleName(item, category) {
      if (category.article_type === 'text') return item.article_description || '-';
      const opts = this.getStockableOptions(category);
      return opts.find(o => o.value === item.stockable_id)?.title || '-';
    },
    getTableHeaders(category) {
      const h = [{ title: this.$t('type_article'), key: 'article', sortable: false }];
      if (category.allow_weight) h.push({ title: this.$t('weight') + ' (Kg)', key: 'weight', sortable: false });
      if (category.allow_unit_price) h.push({ title: this.$t('unit_price'), key: 'unit_price', sortable: false });
      if (category.allow_price) h.push({ title: this.$t('total_price'), key: 'total_price', sortable: false });
      h.push({ title: this.$t('actions'), key: 'actions', sortable: false, width: 80 });
      return h;
    },
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
        if (pkg && pkg.size > 0) f.nbr_box = Math.round((w / pkg.size) * 100) / 100;
      } else {
        f.nbr_box = null;
      }
    },
    deleteSelected(catId) {
      const uids = this.selectedItems[catId] || [];
      uids.forEach(uid => this.inventoryStore.remove_detail(uid));
      this.selectedItems[catId] = [];
    },
    _persistLocal() {
      try {
        sessionStorage.setItem('detail_inventories_draft', JSON.stringify({ entryForms: this.entryForms, activeSections: this.activeSections }));
      } catch (e) { /* quota exceeded */ }
    },
    _syncActiveSections() {
      const details = this.inventoryStore.inventory?.details || [];
      if (!details.length) return;
      const catIds = [...new Set(details.map(d => d.detail_inventory_category_id).filter(Boolean))];
      catIds.forEach(catId => {
        if (!this.activeSections.includes(catId)) {
          this.activeSections.push(catId);
          const cat = this.detailCategories.find(c => c.id === catId);
          if (cat && !this.entryForms[catId]) this.initEntryForm(catId, cat);
        }
      });
    },
  },
};
</script>

<style scoped>
.detail-inventories { font-family: 'Inter', sans-serif; }
</style>
