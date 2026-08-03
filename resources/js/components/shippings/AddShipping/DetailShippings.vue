<template>
  <div class="detail-sells">
    <div v-for="(group, gIdx) in groupedDetails" :key="group.category.id" class="mb-5" :ref="'group_' + group.category.id">
      <v-card class="rounded-lg" elevation="0">
        <v-card-title>
          <v-row class="mt-1">
            <v-col cols="6">
              <v-autocomplete :model-value="group.category.id" :items="detailCats.map(c => ({ title: c[`name_${lang}`] || c.name_fr || c.name_en || `#${c.id}`, value: c.id }))" :label="$t('detail_shipping_category')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details readonly>
                <template #chip="{ item }"><v-chip  color="#4643a0" variant="tonal">{{ item.title }}</v-chip></template>
              </v-autocomplete>
            </v-col>
            <v-col class="text-end"><v-btn class="pa-2" size="small" color="red" icon="mdi-close" variant="outlined" @click="removeGroup(group.category.id)"></v-btn></v-col>
          </v-row>
        </v-card-title>
        <v-card-text>
          <v-row><v-col class="d-flex align-center">
            <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
            <div class="px-2 d-block text-center">
              <v-row class="pa-0"><v-col class="pa-0"><v-icon class="text-shipping">mdi-numeric-{{ gIdx + 1 }}-circle-outline</v-icon></v-col></v-row>
              <v-row class="pa-0"><v-col class="pa-0"><span>{{ $t("add_details") }}</span></v-col></v-row>
            </div>
            <div class="flex-grow-1"><v-divider class="border-opacity-25" color="#4643a0"></v-divider></div>
          </v-col></v-row>

          <v-row class="pt-2">
            <!-- === WAREHOUSE-FIRST FLOW (allow_warehouse + stock_out) === -->
            <template v-if="isWhFlow(group.category)">
              <!-- Step 1: Warehouse -->
              <v-col cols="12" md="6">
                <v-autocomplete v-model="entryForms[group.category.id].warehouse_id" :items="warehouseOptions" :label="$t('warehouse')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details clearable @update:modelValue="onWhChange(group.category)">
                  <template #chip="{ item }"><v-chip color="#4643a0" closable variant="tonal" @click:close="clearWh(group.category)">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </v-col>
              <!-- Step 2: Article (filtered by warehouse stock) -->
              <v-col cols="12" md="6" v-if="entryForms[group.category.id].warehouse_id">
                <v-autocomplete v-model="entryForms[group.category.id].stockable_id" :items="getWhArticleOpts(group.category)" :label="$t('type_article')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details :loading="balLoading[group.category.id]" @update:modelValue="onWhArticleChange(group.category)">
                  <template #chip="{ item }"><v-chip color="#4643a0" closable variant="tonal" @click:close="clearWhArticle(group.category)">{{ item.title }}</v-chip></template>
                  <template #item="{ item, props: p }">
                    <v-list-item v-bind="p"><template #append><v-chip size="x-small" color="teal" variant="tonal" class="font-weight-bold">{{ item.raw.remainLabel }}</v-chip></template></v-list-item>
                  </template>
                  <template #no-data><v-list-item v-if="!balLoading[group.category.id]"><v-list-item-title class="text-caption text-medium-emphasis"><v-icon size="14" class="mr-1">mdi-alert-circle-outline</v-icon>{{ $t('no_stock_in_warehouse') }}</v-list-item-title></v-list-item></template>
                </v-autocomplete>
              </v-col>
              <!-- Step 3: Tracking Number -->
              <v-col cols="12" md="6" v-if="entryForms[group.category.id].stockable_id && entryForms[group.category.id].warehouse_id">
                <v-autocomplete v-model="entryForms[group.category.id].selected_balance_id" :items="getWhBalanceOpts(group.category)" :label="$t('select_tracking_number')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details clearable @update:modelValue="onBalSelect(group.category)">
                  <template #chip="{ item }"><v-chip color="teal" closable variant="tonal" @click:close="clearBal(group.category)">{{ item.title }}</v-chip></template>
                  <template #item="{ item, props: p }">
                    <v-list-item v-bind="p" :subtitle="item.raw.sub"><template #prepend><v-icon size="18" color="teal" class="mr-2">mdi-package-variant</v-icon></template></v-list-item>
                  </template>
                  <template #no-data><v-list-item><v-list-item-title class="text-caption text-medium-emphasis"><v-icon size="14" class="mr-1">mdi-alert-circle-outline</v-icon>{{ $t('no_tracking_numbers_available') }}</v-list-item-title></v-list-item></template>
                </v-autocomplete>
              </v-col>
              <!-- Balance Info -->
              <v-col cols="12" v-if="getSelBal(group.category)" class="py-1">
                <div class="d-flex align-center ga-2 px-2 flex-wrap">
                  <v-icon size="16" color="teal">mdi-information-outline</v-icon>
                  <span class="text-caption text-medium-emphasis">{{ $t('remaining_stock') }}:</span>
                  <v-chip size="x-small" color="teal" variant="tonal" class="font-weight-bold">{{ main.formatDisplayNumber(getSelBalRemain(group.category)) }} {{ getSelBalUnit(group.category) }}</v-chip>
                  <span class="text-caption text-medium-emphasis">{{ $t('initial_stock') }}:</span>
                  <v-chip size="x-small" color="grey" variant="tonal">
                    <template v-if="getSelBal(group.category).stockable_type === 'PackageType' && getSelBal(group.category).package_count > 0">
                      {{ main.formatDisplayNumber(getSelBal(group.category).package_count) }} {{ $t('boxes') }}
                    </template>
                    <template v-else>
                      {{ main.formatDisplayNumber(getSelBal(group.category).total_weight) }} Kg
                    </template>
                  </v-chip>
                </div>
              </v-col>
              <!-- Remaining fields after tracking selected -->
              <template v-if="entryForms[group.category.id].selected_balance_id || !isWhFlow(group.category)">
                <v-col cols="12" md="6" v-if="group.category.allow_weight">
                  <v-text-field v-model.number="entryForms[group.category.id].weight" type="number" :label="$t('weight') + ' (Kg)'" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" min="0" @input="onWeightInput(group.category)" :hint="getWtHint(group.category)" persistent-hint :error="isWtOver(group.category)" :error-messages="isWtOver(group.category) ? [$t('exceeds_stock', { qty: main.formatDisplayNumber(getSelBalRemain(group.category)) })] : []"></v-text-field>
                </v-col>
                <v-col cols="12" md="6" v-if="group.category.allow_weight">
                  <v-text-field v-model.number="entryForms[group.category.id].weight_invoice" type="number" :label="$t('total_weight_invoice') + ' (Kg)'" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details min="0"></v-text-field>
                </v-col>
                <v-col cols="12" md="6" v-if="group.category.allow_unit_price">
                  <v-text-field v-model.number="entryForms[group.category.id].unit_price" type="number" :label="$t('unit_price') + ' (Dh)'" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details min="0" @input="recalcForm(group.category)"></v-text-field>
                </v-col>
                <v-col cols="12" md="6" v-if="group.category.allow_price">
                  <v-text-field v-model.number="entryForms[group.category.id].total_price" type="number" :label="$t('total_price') + ' (Dh)'" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details min="0" :readonly="group.category.allow_weight && group.category.allow_unit_price"></v-text-field>
                </v-col>
                <v-col cols="12" md="6" v-if="group.category.allow_package_type && group.category.article_type !== 'PackageType'">
                  <v-autocomplete v-model="entryForms[group.category.id].package_type_id" :items="packageTypeOptions" :label="$t('package_type')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details clearable @update:modelValue="recalcForm(group.category)"></v-autocomplete>
                </v-col>
                <v-col cols="12" md="6" v-if="group.category.allow_nbr_box">
                  <v-text-field v-model.number="entryForms[group.category.id].nbr_box" type="number" :label="$t('nbr_boxes')" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" min="0" :readonly="group.category.article_type !== 'PackageType'" @input="recalcForm(group.category)" :hint="group.category.article_type === 'PackageType' ? getWtHint(group.category) : ''" persistent-hint :error="isWtOver(group.category)" :error-messages="isWtOver(group.category) ? [$t('exceeds_stock', { qty: main.formatDisplayNumber(getSelBalRemain(group.category)) })] : []"></v-text-field>
                </v-col>
              </template>
            </template>
            <!-- === STANDARD FLOW (no warehouse or stock_in) === -->
            <template v-else>
              <v-col cols="12" md="6">
                <template v-if="group.category.article_type === 'text'">
                  <v-textarea v-model="entryForms[group.category.id].article_description" :label="$t('type_article')" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details rows="2" auto-grow :counter="1000" :maxlength="1000"></v-textarea>
                </template>
                <template v-else>
                  <v-autocomplete v-model="entryForms[group.category.id].stockable_id" :items="getStockOpts(group.category)" :label="$t('type_article')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details>
                    <template #chip="{ item }"><v-chip color="#4643a0" closable variant="tonal" @click:close="entryForms[group.category.id].stockable_id = null">{{ item.title }}</v-chip></template>
                  </v-autocomplete>
                </template>
              </v-col>
              <v-col cols="12" md="6" v-if="group.category.allow_warehouse">
                <v-autocomplete v-model="entryForms[group.category.id].warehouse_id" :items="warehouseOptions" :label="$t('warehouse')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details clearable>
                  <template #chip="{ item }"><v-chip color="#4643a0" closable variant="tonal" @click:close="entryForms[group.category.id].warehouse_id = null">{{ item.title }}</v-chip></template>
                </v-autocomplete>
              </v-col>
              <v-col cols="12" md="6" v-if="group.category.allow_weight">
                <v-text-field v-model.number="entryForms[group.category.id].weight" type="number" :label="$t('weight') + ' (Kg)'" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details min="0" @input="onWeightInput(group.category)"></v-text-field>
              </v-col>
              <v-col cols="12" md="6" v-if="group.category.allow_weight">
                <v-text-field v-model.number="entryForms[group.category.id].weight_invoice" type="number" :label="$t('total_weight_invoice') + ' (Kg)'" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details min="0"></v-text-field>
              </v-col>
              <v-col cols="12" md="6" v-if="group.category.allow_unit_price">
                <v-text-field v-model.number="entryForms[group.category.id].unit_price" type="number" :label="$t('unit_price') + ' (Dh)'" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details min="0" @input="recalcForm(group.category)"></v-text-field>
              </v-col>
              <v-col cols="12" md="6" v-if="group.category.allow_price">
                <v-text-field v-model.number="entryForms[group.category.id].total_price" type="number" :label="$t('total_price') + ' (Dh)'" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details min="0" :readonly="group.category.allow_weight && group.category.allow_unit_price"></v-text-field>
              </v-col>
              <v-col cols="12" md="6" v-if="group.category.allow_package_type">
                <v-autocomplete v-model="entryForms[group.category.id].package_type_id" :items="packageTypeOptions" :label="$t('package_type')" chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details clearable @update:modelValue="recalcForm(group.category)"></v-autocomplete>
              </v-col>
              <v-col cols="12" md="6" v-if="group.category.allow_nbr_box">
                <v-text-field v-model.number="entryForms[group.category.id].nbr_box" type="number" :label="$t('nbr_boxes')" density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details min="0" readonly></v-text-field>
              </v-col>
            </template>
          </v-row>

          <v-row class="text-center mt-2"><v-col><v-btn block rounded variant="tonal" color="#4643a0" @click="addArticle(group.category)" prepend-icon="mdi-plus-circle-outline" :disabled="!isFormValid(group.category)">{{ $t('add_article') }}</v-btn></v-col></v-row>

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
                      <v-autocomplete :model-value="getColumnFilter(group.category.id, col.key)" @update:modelValue="val => setColumnFilter(group.category.id, col.key, val)" :items="getFilterOptions(group, col.key)" :label="$t('filter')" multiple chips closable-chips density="compact" variant="outlined" rounded color="#4643a0" class="text-shipping" hide-details></v-autocomplete>
                      <v-btn v-if="hasFilter(group.category.id, col.key)" block variant="text" color="error" size="small" class="mt-2" @click="clearColumnFilter(group.category.id, col.key)">{{ $t('clear_filter') }}</v-btn>
                    </v-card-text>
                  </v-card>
                </v-menu>
              </div>
            </template>
            <template #item.article="{ item }"><v-chip size="small" color="#4643a0" variant="tonal">{{ getArticleName(item, group.category) }}</v-chip></template>
            <template #item.package_type="{ item }">
              <v-chip v-if="item.package_type_id" size="small" color="#4643a0" variant="tonal">{{ getPackageTypeName(item.package_type_id) }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template #item.nbr_box="{ item }">
              <v-chip v-if="item.nbr_box != null" size="small" color="teal" variant="tonal" class="font-weight-bold">{{ item.nbr_box }} {{ $t('boxes') }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template #item.weight="{ item }">{{ main.formatDisplayNumber(item.weight) }}</template>
            <template #item.weight_invoice="{ item }">{{ main.formatDisplayNumber(item.weight_invoice) }}</template>
            <template #item.unit_price="{ item }">{{ main.formatDisplayNumber(item.unit_price) }}</template>
            <template #item.total_price="{ item }">{{ main.formatDisplayNumber(item.total_price) }}</template>
            <template #item.actions="{ item }">
              <v-btn icon size="x-small" variant="text" color="warning" class="mr-1" @click="editArticle(item, group.category)"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="text" color="error" @click="shippingStore.remove_detail(item._uid)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
          <v-btn v-if="selectedItems[group.category.id] && selectedItems[group.category.id].length > 0" block rounded variant="tonal" color="error" class="mt-2" prepend-icon="mdi-delete-sweep" @click="deleteSelected(group.category.id)">{{ $t('delete_selected') }} ({{ selectedItems[group.category.id].length }})</v-btn>
        </v-card-text>
      </v-card>
    </div>

    <v-menu v-if="availableCats.length > 0">
      <template #activator="{ props }"><v-btn v-bind="props" color="#4643a0" variant="outlined" rounded block prepend-icon="mdi-plus-circle-outline" class="mt-2">{{ $t('add_detail_section') }}</v-btn></template>
      <v-list density="compact" class="rounded-lg"><v-list-item v-for="dc in availableCats" :key="dc.id" @click="addSection(dc)" :title="dc[`name_${lang}`] || dc.name_fr || `#${dc.id}`" prepend-icon="mdi-plus-circle-outline"></v-list-item></v-list>
    </v-menu>
  </div>
</template>

<script>
import axios from 'axios';
import { useShipping } from '@/stores/Shipping';
import { useMain } from '@/stores/Main';
import { useDetailShippingCategory } from '@/stores/DetailShippingCategory';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'detailShippings',
  data() { return { entryForms: {}, activeSections: [], columnFilters: {}, selectedItems: {}, whBalances: {}, balLoading: {}, localDeductions: {} }; },
  mounted() {
    try {
      const raw = sessionStorage.getItem('detail_shippings_draft');
      if (raw) { const data = JSON.parse(raw); if (data.entryForms) this.entryForms = data.entryForms; if (data.activeSections) this.activeSections = data.activeSections; }
    } catch (e) { /* corrupted */ }
    this._syncActiveSections();
  },
  watch: {
    entryForms: { handler() { this._persistLocal(); }, deep: true },
    activeSections: { handler() { this._persistLocal(); }, deep: true },
    'shippingStore.shipping.details': {
      handler() { this._syncActiveSections(); },
      deep: true,
      immediate: true,
    },
  },
  computed: {
    shippingStore() { return useShipping(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    detailCats() { return useDetailShippingCategory().list.filter(d => d.shipping_category_id === this.shippingStore.shipping?.shipping_category_id); },
    availableCats() { return this.detailCats.filter(c => !this.activeSections.includes(c.id)); },
    groupedDetails() {
      return this.activeSections.map(catId => {
        const category = this.detailCats.find(c => c.id === catId);
        if (!category) return null;
        return { category, items: (this.shippingStore.shipping?.details || []).filter(d => d.detail_shipping_category_id === catId) };
      }).filter(Boolean);
    },
    warehouseOptions() { return this.auth.warehouse.list.map(w => ({ title: w.name, value: w.id })); },
    packageTypeOptions() { return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en, value: p.id })); },
  },
  methods: {
    isWhFlow(cat) { return cat.allow_warehouse && cat.warehouse_operation === 'stock_out' && cat.article_type !== 'text'; },
    initForm(catId, category) { const defaultDesc = category && category.article_type === 'text' ? (category[`name_${this.lang}`] || category.name_fr || category.name_en || '') : ''; this.entryForms[catId] = { article_description: defaultDesc, stockable_id: null, weight: null, unit_price: null, total_price: null, weight_invoice: null, warehouse_id: null, package_type_id: null, nbr_box: null, selected_balance_id: null }; },
    isFormValid(category) {
      const f = this.entryForms[category.id];
      if (!f) return false;
      if (category.article_type === 'text') { if (!f.article_description?.trim()) return false; }
      else { if (!f.stockable_id) return false; }
      if (category.allow_weight && (!f.weight || f.weight <= 0)) return false;
      if (category.allow_unit_price && (!f.unit_price || f.unit_price <= 0)) return false;
      if (category.allow_price && (!category.allow_weight || !category.allow_unit_price) && (!f.total_price || f.total_price <= 0)) return false;
      if (category.allow_package_type && !f.package_type_id) return false;
      if (category.allow_warehouse && !f.warehouse_id) return false;
      return true;
    },
    addSection(dc) { if (!this.activeSections.includes(dc.id)) { this.activeSections.push(dc.id); this.initForm(dc.id, dc); } },
    removeGroup(catId) { const items = this.shippingStore.shipping.details.filter(d => d.detail_shipping_category_id === catId); items.forEach(d => this.shippingStore.remove_detail(d._uid)); this.activeSections = this.activeSections.filter(id => id !== catId); delete this.entryForms[catId]; },
    addArticle(cat) {
      const f = this.entryForms[cat.id]; if (!f) return;
      if (cat.article_type === 'PackageType') { f.package_type_id = f.stockable_id; }
      this.recalcForm(cat);
      this.shippingStore.add_detail(cat.id);
      const last = this.shippingStore.shipping.details[this.shippingStore.shipping.details.length - 1];
      Object.assign(last, { article_description: f.article_description || null, stockable_type: cat.article_type !== 'text' ? cat.article_type : null, stockable_id: f.stockable_id, weight: f.weight, unit_price: f.unit_price, total_price: f.total_price, weight_invoice: f.weight_invoice, warehouse_id: f.warehouse_id, warehouse_operation: cat.warehouse_operation || (f.warehouse_id ? 'stock_out' : null), package_type_id: f.package_type_id || (cat.article_type === 'PackageType' ? f.stockable_id : null), nbr_box: f.nbr_box, selected_balance_id: f.selected_balance_id });
      // Track local deduction for frontend sync
      if (f.selected_balance_id) {
        const qty = (cat.article_type === 'PackageType' && f.nbr_box > 0) ? f.nbr_box : (f.weight || 0);
        if (qty > 0) this._addDeduction(f.selected_balance_id, qty);
      }
      this.shippingStore.recalc_totals(); this.initForm(cat.id, cat);
      this.$nextTick(() => { const c = this.$refs['group_' + cat.id]; const el = c?.[0] || c; if (el) { const input = el.querySelector('input'); if (input) input.focus(); } });
    },
    editArticle(item, cat) {
      // Restore deduction when editing
      if (item.selected_balance_id && item.weight > 0) { this._removeDeduction(item.selected_balance_id, item.weight); }
      this.entryForms[cat.id] = { article_description: item.article_description || '', stockable_id: item.stockable_id, weight: item.weight, unit_price: item.unit_price, total_price: item.total_price, weight_invoice: item.weight_invoice, warehouse_id: item.warehouse_id, package_type_id: item.package_type_id, nbr_box: item.nbr_box, selected_balance_id: item.selected_balance_id || null };
      this.shippingStore.remove_detail(item._uid);
      if (item.warehouse_id && this.isWhFlow(cat) && !this.whBalances[cat.id]?.length) this.fetchWhBalances(cat);
    },
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
    getArticleName(item, cat) {
      if (cat.article_type === 'text') return item.article_description || '-';
      // Try category-based lookup first
      const opts = this.getStockOpts(cat);
      const found = opts.find(o => o.value === item.stockable_id);
      if (found) return found.title;
      // Fallback: search by item's actual stockable_type
      const st = item.stockable_type;
      if (st === 'FishType') {
        const f = this.auth.fish_type.list.find(f => f.id === item.stockable_id);
        if (f) return f[`name_${this.lang}`] || f.name_en || f.name_fr || `#${item.stockable_id}`;
      }
      if (st === 'GuanoType') {
        const g = this.auth.guano_type.list.find(g => g.id === item.stockable_id);
        if (g) return g[`name_${this.lang}`] || g.name_en || g.name_fr || `#${item.stockable_id}`;
      }
      if (st === 'PackageType') {
        const p = this.auth.package_type.list.find(p => p.id === item.stockable_id);
        if (p) return p[`name_${this.lang}`] || p.name_en || p.name_fr || `#${item.stockable_id}`;
      }
      return '-';
    },
    getPackageTypeName(id) {
      const p = this.auth.package_type.list.find(p => p.id === id);
      return p ? (p[`name_${this.lang}`] || p.name_en || `#${id}`) : '-';
    },
    getHeaders(cat) {
      const h = [{ title: this.$t('type_article'), key: 'article', sortable: false }];
      if (cat.allow_package_type || cat.allow_nbr_box) {
        h.push({ title: this.$t('package_type'), key: 'package_type', sortable: false });
        h.push({ title: this.$t('nbr_boxes'), key: 'nbr_box', sortable: false });
      }
      if (cat.allow_weight) h.push({ title: this.$t('weight') + ' (Kg)', key: 'weight', sortable: false });
      if (cat.allow_weight) h.push({ title: this.$t('total_weight_invoice'), key: 'weight_invoice', sortable: false });
      if (cat.allow_unit_price) h.push({ title: this.$t('unit_price'), key: 'unit_price', sortable: false });
      if (cat.allow_price) h.push({ title: this.$t('total_price'), key: 'total_price', sortable: false });
      h.push({ title: this.$t('actions'), key: 'actions', sortable: false, width: 80 });
      return h;
    },
    getFilterableColumns(cat) { return this.getHeaders(cat).filter(h => h.key !== 'actions'); },
    getDisplayValue(item, key, cat) {
      if (key === 'article') return this.getArticleName(item, cat);
      if (key === 'package_type') return item.package_type_id ? this.getPackageTypeName(item.package_type_id) : '';
      if (key === 'nbr_box') return item.nbr_box != null ? String(item.nbr_box) : '';
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
      const nbrBox = parseFloat(f.nbr_box) || 0;
      const up = parseFloat(f.unit_price) || 0;

      if (category.article_type === 'PackageType') {
        f.package_type_id = f.stockable_id;
        const qty = nbrBox > 0 ? nbrBox : w;
        if (category.allow_unit_price && qty > 0) {
          f.total_price = Math.round(qty * up * 100) / 100;
        }
      } else {
        if (category.allow_weight && category.allow_unit_price) {
          f.total_price = Math.round(w * up * 100) / 100;
        }
        if (category.allow_package_type && category.allow_nbr_box && f.package_type_id) {
          const pkg = this.auth.package_type.list.find(p => p.id === f.package_type_id);
          if (pkg && pkg.size > 0 && w > 0) { f.nbr_box = Math.round((w / pkg.size) * 100) / 100; }
        } else if (category.article_type !== 'PackageType') { f.nbr_box = null; }
      }
    },
    onWeightInput(category) {
      const f = this.entryForms[category.id];
      if (f) f.weight_invoice = f.weight;
      this.recalcForm(category);
    },
    deleteSelected(catId) {
      const cat = this.detailCats.find(c => c.id === catId);
      const uids = this.selectedItems[catId] || [];
      uids.forEach(uid => {
        const item = this.shippingStore.shipping.details.find(d => d._uid === uid);
        if (item?.selected_balance_id && item.weight > 0) this._removeDeduction(item.selected_balance_id, item.weight);
        this.shippingStore.remove_detail(uid);
      });
      this.selectedItems[catId] = [];
    },
    // === Warehouse-first flow methods ===
    async fetchWhBalances(category) {
      const f = this.entryForms[category.id];
      if (!f?.warehouse_id) { this.whBalances[category.id] = []; this.whBalances = { ...this.whBalances }; return; }
      this.balLoading[category.id] = true; this.balLoading = { ...this.balLoading };
      try {
        const params = { warehouse_id: f.warehouse_id };
        if (category.article_type && category.article_type !== 'text') params.stockable_type = category.article_type;
        const res = await axios.get('/api/warehouses/available-balances', { params });
        this.whBalances[category.id] = res.data.balances || [];
      } catch (e) { console.error(e); this.whBalances[category.id] = []; }
      this.balLoading[category.id] = false;
      this.whBalances = { ...this.whBalances }; this.balLoading = { ...this.balLoading };
    },
    getWhArticleOpts(category) {
      const bals = this.whBalances[category.id] || [];
      const map = {};
      bals.forEach(b => {
        const key = `${b.stockable_type}_${b.stockable_id}`;
        if (!map[key]) {
          const name = b.stockable ? (b.stockable[`name_${this.lang}`] || b.stockable.name_en || b.stockable.name_fr || `#${b.stockable_id}`) : `#${b.stockable_id}`;
          map[key] = { id: b.stockable_id, type: b.stockable_type, name, totalW: 0, totalPkg: 0 };
        }
        const ded = this._getDeduction(b.id);
        const remW = Math.max(0, parseFloat(b.remaining_weight || 0) - ded);
        const outPkg = (b.operations || []).reduce((s, op) => s + (parseFloat(op.package_count) || 0), 0);
        const remPkg = Math.max(0, parseFloat(b.package_count || 0) - outPkg - ded);
        map[key].totalW += remW;
        map[key].totalPkg += remPkg;
      });
      return Object.values(map)
        .filter(a => a.type === 'PackageType' ? (a.totalPkg > 0 || a.totalW > 0) : a.totalW > 0)
        .map(a => {
          let remainLabel = `${this.main.formatDisplayNumber(a.totalW)} Kg`;
          if (a.type === 'PackageType' && a.totalPkg > 0) {
            remainLabel = `${this.main.formatDisplayNumber(a.totalPkg)} ${this.$t('boxes')}`;
          }
          return { title: a.name, value: a.id, remainLabel };
        });
    },
    getWhBalanceOpts(category) {
      const f = this.entryForms[category.id]; if (!f) return [];
      return (this.whBalances[category.id] || []).filter(b => b.stockable_id === f.stockable_id).map(b => {
        const ded = this._getDeduction(b.id);
        const remW = Math.max(0, parseFloat(b.remaining_weight || 0) - ded);
        const outPkg = (b.operations || []).reduce((s, op) => s + (parseFloat(op.package_count) || 0), 0);
        const remPkg = Math.max(0, parseFloat(b.package_count || 0) - outPkg - ded);
        const isPkg = b.stockable_type === 'PackageType';
        const remain = isPkg && remPkg > 0 ? remPkg : remW;
        const unit = isPkg && remPkg > 0 ? this.$t('boxes') : 'Kg';

        return {
          title: `${b.reception_tracking_number || '-'} - (${this.main.formatDisplayNumber(remain)} ${unit})`,
          sub: `${this.$t('date')}: ${b.date ? b.date.substring(0, 10) : '-'} · ${b.supplier_name || ''}`,
          value: b.id,
          _remain: remain,
        };
      }).filter(o => o._remain > 0);
    },
    getSelBal(category) {
      const f = this.entryForms[category.id]; if (!f?.selected_balance_id) return null;
      return (this.whBalances[category.id] || []).find(b => b.id === f.selected_balance_id) || null;
    },
    getSelBalRemain(category) {
      const bal = this.getSelBal(category); if (!bal) return 0;
      const ded = this._getDeduction(bal.id);
      const isPkg = bal.stockable_type === 'PackageType';
      if (isPkg) {
        const outPkg = (bal.operations || []).reduce((s, op) => s + (parseFloat(op.package_count) || 0), 0);
        const remPkg = Math.max(0, parseFloat(bal.package_count || 0) - outPkg - ded);
        if (remPkg > 0) return remPkg;
      }
      return Math.max(0, parseFloat(bal.remaining_weight || 0) - ded);
    },
    getSelBalUnit(category) {
      const bal = this.getSelBal(category); if (!bal) return 'Kg';
      if (bal.stockable_type === 'PackageType') return this.$t('boxes');
      return 'Kg';
    },
    onWhChange(category) {
      const f = this.entryForms[category.id];
      if (f) { f.stockable_id = null; f.selected_balance_id = null; }
      if (f?.warehouse_id) { this.fetchWhBalances(category); } else { this.whBalances[category.id] = []; this.whBalances = { ...this.whBalances }; }
    },
    clearWh(category) { const f = this.entryForms[category.id]; if (f) { f.warehouse_id = null; f.stockable_id = null; f.selected_balance_id = null; } this.whBalances[category.id] = []; this.whBalances = { ...this.whBalances }; },
    onWhArticleChange(category) {
      const f = this.entryForms[category.id];
      if (f) {
        f.selected_balance_id = null;
        if (category.article_type === 'PackageType') { f.package_type_id = f.stockable_id; }
      }
    },
    clearWhArticle(category) { const f = this.entryForms[category.id]; if (f) { f.stockable_id = null; f.selected_balance_id = null; } },
    onBalSelect(category) {
      const f = this.entryForms[category.id];
      if (f && category.article_type === 'PackageType') { f.package_type_id = f.stockable_id; }
    },
    clearBal(category) { const f = this.entryForms[category.id]; if (f) f.selected_balance_id = null; },
    isWtOver(category) {
      const bal = this.getSelBal(category); if (!bal) return false;
      const f = this.entryForms[category.id];
      const qty = (category.article_type === 'PackageType' && f?.nbr_box > 0) ? f.nbr_box : f?.weight;
      return qty > 0 && qty > this.getSelBalRemain(category);
    },
    getWtHint(category) {
      const bal = this.getSelBal(category); if (!bal) return '';
      const f = this.entryForms[category.id];
      const qty = (category.article_type === 'PackageType' && f?.nbr_box > 0) ? f.nbr_box : f?.weight;
      if (!qty || qty <= 0) return '';
      const r = this.getSelBalRemain(category) - qty;
      const unit = this.getSelBalUnit(category);
      return r >= 0 ? `${this.$t('stock_after_sell')}: ${this.main.formatDisplayNumber(r)} ${unit}` : '';
    },
    // === Local deduction tracking (frontend sync) ===
    _addDeduction(balId, weight) { if (!this.localDeductions[balId]) this.localDeductions[balId] = 0; this.localDeductions[balId] += parseFloat(weight) || 0; this.localDeductions = { ...this.localDeductions }; },
    _removeDeduction(balId, weight) { if (!this.localDeductions[balId]) return; this.localDeductions[balId] -= parseFloat(weight) || 0; if (this.localDeductions[balId] <= 0) delete this.localDeductions[balId]; this.localDeductions = { ...this.localDeductions }; },
    _getDeduction(balId) { return this.localDeductions[balId] || 0; },
    _persistLocal() {
      try { sessionStorage.setItem('detail_shippings_draft', JSON.stringify({ entryForms: this.entryForms, activeSections: this.activeSections })); } catch (e) { /* quota */ }
    },
    _syncActiveSections() {
      const details = this.shippingStore.shipping?.details || [];
      if (!details.length) return;
      const catIds = [...new Set(details.map(d => d.detail_shipping_category_id).filter(Boolean))];
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
.text-shipping { color: #4643a0 !important; }
</style>



