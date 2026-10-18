<template>
  <div class="detail-productions">
    <!-- Quick Category Add / Switch Bar -->
    <div v-if="detailCats.length > 0" class="category-quick-bar pa-3 rounded-xl mb-4 d-flex align-center justify-space-between flex-wrap ga-2">
      <div class="d-flex align-center ga-1 flex-wrap">
        <span class="text-caption font-weight-bold text-medium-emphasis mr-1">{{ $t('sections') || 'Sections' }}:</span>
        <v-chip
          v-for="cat in detailCats"
          :key="cat.id"
          size="small"
          :color="activeSections.includes(cat.id) ? '#00897B' : 'default'"
          :variant="activeSections.includes(cat.id) ? 'flat' : 'outlined'"
          class="font-weight-medium cursor-pointer"
          @click="toggleSection(cat)"
        >
          <v-icon size="14" class="mr-1">{{ activeSections.includes(cat.id) ? 'mdi-check' : 'mdi-plus' }}</v-icon>
          {{ cat[`name_${lang}`] || cat.name_fr || cat.name_en || `#${cat.id}` }}
          <span v-if="getSectionItemCount(cat.id)" class="ml-1 text-caption opacity-80">({{ getSectionItemCount(cat.id) }})</span>
        </v-chip>
      </div>

      <v-menu v-if="availableCats.length > 0">
        <template #activator="{ props }">
          <v-btn v-bind="props" size="small" variant="text" color="#00897B" prepend-icon="mdi-plus">
            {{ $t('add_detail_section') || 'Add Section' }}
          </v-btn>
        </template>
        <v-list density="compact" class="rounded-lg">
          <v-list-item
            v-for="dc in availableCats"
            :key="dc.id"
            @click="addSection(dc)"
            :title="dc[`name_${lang}`] || dc.name_fr || dc.name_en || `#${dc.id}`"
            prepend-icon="mdi-plus-circle-outline"
          ></v-list-item>
        </v-list>
      </v-menu>
    </div>

    <!-- Per detail-category section cards -->
    <div v-for="(group, gIdx) in groupedDetails" :key="group.category.id" class="mb-5" :ref="'group_' + group.category.id">
      <v-card class="detail-section-card rounded-xl elevation-0" border>
        <!-- Card Header -->
        <div class="section-card-header pa-3 px-4 d-flex align-center justify-space-between flex-wrap ga-2 border-b">
          <div class="d-flex align-center ga-2 flex-wrap">
            <div class="section-badge-icon" style="background: rgba(0, 137, 123, .1);">
              <v-icon size="18" color="#00897B">mdi-factory</v-icon>
            </div>
            <div>
              <span class="text-subtitle-2 font-weight-bold text-prod">
                {{ group.category[`name_${lang}`] || group.category.name_fr || group.category.name_en || `#${group.category.id}` }}
              </span>
              <span class="text-caption text-medium-emphasis ml-2">
                #{{ gIdx + 1 }}
              </span>
            </div>

            <!-- Group live totals chip -->
            <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-medium">
              {{ group.items.length }} {{ $t('articles') || 'articles' }}
              <template v-if="getGroupTotalWeight(group) > 0">
                &bull; {{ main.formatDisplayNumber(getGroupTotalWeight(group)) }} Kg
              </template>
              <template v-if="getGroupTotalPrice(group) > 0">
                &bull; {{ main.formatDisplayNumber(getGroupTotalPrice(group)) }} {{ main.currency }}
              </template>
            </v-chip>
          </div>

          <div class="d-flex align-center ga-1">
            <v-btn
              v-if="groupedDetails.length > 1"
              size="small"
              icon="mdi-close"
              variant="text"
              color="error"
              :title="$t('remove_section') || 'Remove section'"
              @click="removeGroup(group.category.id)"
            ></v-btn>
          </div>
        </div>

        <v-card-text class="pa-4">
          <!-- Entry Form Grid -->
          <div class="entry-form-container pa-3 rounded-lg mb-3" style="background: rgba(var(--v-theme-on-surface), .02); border: 1px solid rgba(var(--v-theme-on-surface), .06);">
            <div class="text-caption font-weight-bold text-prod mb-2 d-flex align-center ga-1">
              <v-icon size="14">mdi-plus-circle-outline</v-icon>
              <span>{{ $t("add_details") }}</span>
            </div>

            <v-row dense>
              <!-- Article Field -->
              <v-col cols="12" :sm="group.category.article_type === 'text' ? 12 : 6">
                <template v-if="group.category.article_type === 'text'">
                  <v-textarea
                    v-model="entryForms[group.category.id].article_description"
                    :label="$t('type_article')"
                    density="compact"
                    variant="outlined"
                    rounded
                    color="#00897B"
                    class="text-prod"
                    hide-details
                    rows="1"
                    auto-grow
                    :counter="1000"
                    :maxlength="1000"
                    @keydown.enter.exact.prevent="onFormEnterKey(group.category)"
                  ></v-textarea>
                </template>
                <template v-else>
                  <v-autocomplete
                    v-model="entryForms[group.category.id].stockable_id"
                    :items="getStockOpts(group.category)"
                    :label="$t('type_article')"
                    chips
                    density="compact"
                    variant="outlined"
                    rounded
                    color="#00897B"
                    class="text-prod"
                    hide-details
                    prepend-inner-icon="mdi-fish"
                  >
                    <template #chip="{ item }">
                      <v-chip color="#00897B" closable variant="tonal" @click:close="entryForms[group.category.id].stockable_id = null">
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
                </template>
              </v-col>

              <!-- Package Type (if allowed) -->
              <v-col cols="12" sm="6" v-if="group.category.allow_package_type">
                <v-autocomplete
                  v-model="entryForms[group.category.id].package_type_id"
                  :items="packageTypeOptions"
                  :label="$t('package_type')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  clearable
                  @update:modelValue="recalcForm(group.category)"
                ></v-autocomplete>
              </v-col>

              <!-- Nbr Boxes (if allowed) -->
              <v-col cols="12" sm="6" v-if="group.category.allow_nbr_box">
                <v-text-field
                  v-model.number="entryForms[group.category.id].nbr_box"
                  type="number"
                  :label="$t('nbr_boxes')"
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  min="0"
                  readonly
                ></v-text-field>
              </v-col>

              <!-- Weight (if allowed) -->
              <v-col cols="12" sm="6" v-if="group.category.allow_weight">
                <v-text-field
                  v-model.number="entryForms[group.category.id].weight"
                  type="number"
                  :label="$t('weight') + ' (Kg)'"
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  min="0"
                  prepend-inner-icon="mdi-weight-kilogram"
                  @input="onWeightInput(group.category)"
                  @keydown.enter.exact.prevent="onFormEnterKey(group.category)"
                ></v-text-field>
              </v-col>

              <!-- Weight Invoice (if allowed) -->
              <v-col cols="12" sm="6" v-if="group.category.allow_weight">
                <v-text-field
                  v-model.number="entryForms[group.category.id].weight_invoice"
                  type="number"
                  :label="$t('total_weight_invoice') + ' (Kg)'"
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  min="0"
                  @keydown.enter.exact.prevent="onFormEnterKey(group.category)"
                ></v-text-field>
              </v-col>

              <!-- Unit Price (if allowed) -->
              <v-col cols="12" sm="6" v-if="group.category.allow_unit_price">
                <v-text-field
                  v-model.number="entryForms[group.category.id].unit_price"
                  type="number"
                  :label="$t('unit_price') + ' (Dh)'"
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  min="0"
                  prepend-inner-icon="mdi-currency-usd"
                  @input="recalcForm(group.category)"
                  @keydown.enter.exact.prevent="onFormEnterKey(group.category)"
                ></v-text-field>
              </v-col>

              <!-- Total Price (if allowed) -->
              <v-col cols="12" sm="6" v-if="group.category.allow_price">
                <v-text-field
                  v-model.number="entryForms[group.category.id].total_price"
                  type="number"
                  :label="$t('total_price') + ' (Dh)'"
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  min="0"
                  :readonly="group.category.allow_weight && group.category.allow_unit_price"
                  @keydown.enter.exact.prevent="onFormEnterKey(group.category)"
                ></v-text-field>
              </v-col>

              <!-- Warehouse (if allowed) -->
              <v-col cols="12" sm="6" v-if="group.category.allow_warehouse">
                <v-autocomplete
                  v-model="entryForms[group.category.id].warehouse_id"
                  :items="warehouseOptions"
                  :label="$t('warehouse')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  clearable
                  prepend-inner-icon="mdi-warehouse"
                >
                  <template #chip="{ item }">
                    <v-chip color="#00897B" closable variant="tonal" @click:close="entryForms[group.category.id].warehouse_id = null">
                      {{ item.title }}
                    </v-chip>
                  </template>
                </v-autocomplete>
              </v-col>

              <!-- Truck (if allowed) -->
              <v-col cols="12" sm="6" v-if="group.category.allow_detail_truck_id">
                <v-autocomplete
                  v-model="entryForms[group.category.id].truck_id"
                  :items="truckOptions"
                  :label="$t('truck')"
                  chips
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#00897B"
                  class="text-prod"
                  hide-details
                  clearable
                  prepend-inner-icon="mdi-truck"
                  @update:search="val => truckSearch[group.category.id] = val"
                  @keydown.enter.prevent="createTruckIfNew(group.category.id)"
                >
                  <template #chip="{ item }">
                    <v-chip color="#00897B" closable variant="tonal" @click:close="entryForms[group.category.id].truck_id = null">
                      {{ item.title }}
                    </v-chip>
                  </template>
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

              <!-- Add Article Button & Calculation Preview -->
              <v-col cols="12" class="pt-2 d-flex align-center justify-space-between flex-wrap ga-2">
                <div v-if="getFormPricePreview(group.category)" class="live-preview-pill px-3 py-1 rounded-pill d-flex align-center ga-2 text-caption">
                  <v-icon size="14" color="success">mdi-calculator</v-icon>
                  <span class="text-medium-emphasis">{{ $t('calculated_total') || 'Total' }}:</span>
                  <strong class="text-success">{{ getFormPricePreview(group.category) }}</strong>
                </div>
                <div v-else></div>

                <v-btn
                  rounded
                  color="#00897B"
                  variant="tonal"
                  class="font-weight-bold"
                  :disabled="!isFormValid(group.category)"
                  prepend-icon="mdi-plus-circle-outline"
                  @click="addArticle(group.category)"
                >
                  {{ $t('add_article') }}
                  <span class="ml-1 text-caption opacity-70">(Enter)</span>
                </v-btn>
              </v-col>
            </v-row>
          </div>

          <!-- Articles Table -->
          <div v-if="group.items.length > 0" class="articles-table-wrapper rounded-lg overflow-hidden border">
            <v-data-table
              v-model="selectedItems[group.category.id]"
              :headers="getHeaders(group.category)"
              :items="getFilteredItems(group)"
              item-value="_uid"
              show-select
              density="compact"
              hide-default-footer
              :items-per-page="-1"
            >
              <!-- Column filter menus in headers -->
              <template v-for="col in getFilterableColumns(group.category)" :key="'header-'+col.key" #[`header.${col.key}`]="{ column }">
                <div class="d-flex align-center" style="white-space:nowrap">
                  <span>{{ column.title }}</span>
                  <v-menu :close-on-content-click="false" location="bottom" offset="4">
                    <template #activator="{ props: menuProps }">
                      <v-btn v-bind="menuProps" icon variant="text" size="x-small" class="ml-1" :color="hasFilter(group.category.id, col.key) ? 'info' : 'grey'">
                        <v-icon size="13">mdi-filter</v-icon>
                      </v-btn>
                    </template>
                    <v-card min-width="240" class="rounded-lg shadow-lg pa-3" elevation="8">
                      <v-autocomplete
                        :model-value="getColumnFilter(group.category.id, col.key)"
                        @update:modelValue="val => setColumnFilter(group.category.id, col.key, val)"
                        :items="getFilterOptions(group, col.key)"
                        :label="$t('filter')"
                        multiple
                        chips
                        closable-chips
                        density="compact"
                        variant="outlined"
                        rounded
                        color="#00897B"
                        class="text-prod"
                        hide-details
                      ></v-autocomplete>
                      <v-btn v-if="hasFilter(group.category.id, col.key)" block variant="text" color="error" size="small" class="mt-2" @click="clearColumnFilter(group.category.id, col.key)">
                        {{ $t('clear_filter') }}
                      </v-btn>
                    </v-card>
                  </v-menu>
                </div>
              </template>

              <!-- Table cell slots -->
              <template #item.article="{ item }">
                <v-chip size="small" color="#00897B" variant="tonal" class="font-weight-medium">
                  {{ getArticleName(item, group.category) }}
                </v-chip>
              </template>

              <template #item.package_type="{ item }">
                <v-chip v-if="item.package_type_id" size="small" color="#00897B" variant="tonal">
                  {{ getPackageTypeName(item.package_type_id) }}
                </v-chip>
                <span v-else class="text-grey">—</span>
              </template>

              <template #item.nbr_box="{ item }">
                <v-chip v-if="item.nbr_box != null" size="small" color="teal" variant="tonal" class="font-weight-bold">
                  {{ item.nbr_box }} {{ $t('boxes') }}
                </v-chip>
                <span v-else class="text-grey">—</span>
              </template>

              <template #item.weight="{ item }">
                <span class="font-weight-medium">{{ main.formatDisplayNumber(item.weight) }}</span>
              </template>

              <template #item.weight_invoice="{ item }">
                <span class="text-medium-emphasis">{{ main.formatDisplayNumber(item.weight_invoice) }}</span>
              </template>

              <template #item.unit_price="{ item }">
                <span class="text-medium-emphasis">{{ main.formatDisplayNumber(item.unit_price) }}</span>
              </template>

              <template #item.total_price="{ item }">
                <span class="font-weight-bold text-success">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</span>
              </template>

              <template #item.warehouse="{ item }">
                <v-chip v-if="item.warehouse_id" size="small" color="#00897B" variant="tonal">
                  {{ getWarehouseName(item.warehouse_id) }}
                </v-chip>
                <span v-else class="text-grey">—</span>
              </template>

              <template #item.actions="{ item }">
                <div class="d-flex align-center justify-end">
                  <v-btn icon size="x-small" variant="text" color="warning" class="mr-1" :title="$t('edit')" @click="editArticle(item, group.category)">
                    <v-icon size="16">mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon size="x-small" variant="text" color="error" :title="$t('delete')" @click="prodStore.remove_detail(item._uid)">
                    <v-icon size="16">mdi-delete-outline</v-icon>
                  </v-btn>
                </div>
              </template>
            </v-data-table>

            <!-- Table Summary Footer Strip -->
            <div class="table-summary-strip pa-2 px-3 d-flex align-center justify-space-between flex-wrap ga-2 border-t">
              <div class="d-flex align-center ga-3 text-caption">
                <span class="text-medium-emphasis">{{ group.items.length }} {{ $t('records_showing') || 'items' }}</span>
                <span v-if="group.category.allow_weight" class="font-weight-bold text-prod">
                  {{ $t('total_weight') }}: {{ main.formatDisplayNumber(getGroupTotalWeight(group)) }} Kg
                </span>
                <span v-if="group.category.allow_price || group.category.allow_unit_price" class="font-weight-bold text-success">
                  {{ $t('total_price') }}: {{ main.formatDisplayNumber(getGroupTotalPrice(group)) }} {{ main.currency }}
                </span>
              </div>

              <v-btn
                v-if="selectedItems[group.category.id] && selectedItems[group.category.id].length > 0"
                size="small"
                rounded
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete-sweep"
                @click="deleteSelected(group.category.id)"
              >
                {{ $t('delete_selected') }} ({{ selectedItems[group.category.id].length }})
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- If no active groups but categories exist -->
    <div v-if="groupedDetails.length === 0 && detailCats.length > 0" class="empty-sections-prompt pa-6 rounded-xl border text-center">
      <div class="empty-icon-circle mx-auto mb-3" style="width: 56px; height: 56px; border-radius: 16px; background: rgba(0, 137, 123, 0.1); display: flex; align-items: center; justify-content: center;">
        <v-icon size="28" color="#00897B">mdi-layers-plus</v-icon>
      </div>
      <div class="text-subtitle-1 font-weight-bold text-prod mb-1">
        {{ $t('select_detail_category_to_start') || 'Select a detail category to start adding articles' }}
      </div>
      <div class="text-caption text-medium-emphasis mb-4">
        {{ $t('select_detail_category_hint') || 'Click on any available section below to activate its entry form.' }}
      </div>
      <div class="d-flex flex-wrap justify-center ga-2">
        <v-btn
          v-for="dc in detailCats"
          :key="dc.id"
          rounded="lg"
          variant="tonal"
          color="#00897B"
          prepend-icon="mdi-plus-circle-outline"
          class="font-weight-medium"
          @click="addSection(dc)"
        >
          {{ dc[`name_${lang}`] || dc.name_fr || dc.name_en || `#${dc.id}` }}
        </v-btn>
      </div>
    </div>

    <!-- If production category has NO detail categories configured in system -->
    <div v-else-if="groupedDetails.length === 0" class="empty-sections-prompt pa-8 rounded-xl border text-center">
      <div class="empty-icon-circle mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 20px; background: rgba(0, 137, 123, 0.1); display: flex; align-items: center; justify-content: center;">
        <v-icon size="32" color="#00897B">mdi-layers-off</v-icon>
      </div>
      <div class="text-subtitle-1 font-weight-bold mb-1">
        {{ $t('no_detail_categories_found') || 'No Detail Categories Configured' }}
      </div>
      <div class="text-caption text-medium-emphasis" style="max-width: 360px; margin: 0 auto;">
        {{ $t('no_detail_categories_hint') || 'This category does not have any detail categories configured in Settings.' }}
      </div>
    </div>
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
  data() {
    return {
      entryForms: {},
      activeSections: [],
      truckSearch: {},
      columnFilters: {},
      selectedItems: {},
    };
  },
  mounted() {
    try {
      const raw = sessionStorage.getItem('detail_productions_draft');
      if (raw) {
        const data = JSON.parse(raw);
        if (data.entryForms) this.entryForms = data.entryForms;
        if (data.activeSections) this.activeSections = data.activeSections;
      }
    } catch (e) { /* corrupted */ }
    this._syncActiveSections();
    const validIds = this.detailCats.map(c => c.id);
    this.activeSections = (this.activeSections || []).filter(id => validIds.includes(id));
    if (this.activeSections.length === 0 && this.detailCats.length > 0) {
      this.addSection(this.detailCats[0]);
    }
  },
  watch: {
    entryForms: { handler() { this._persistLocal(); }, deep: true },
    activeSections: { handler() { this._persistLocal(); }, deep: true },
    'prodStore.production.details': {
      handler() { this._syncActiveSections(); },
      deep: true,
      immediate: true,
    },
    'prodStore.production.production_category_id': {
      handler() {
        const validIds = this.detailCats.map(c => c.id);
        this.activeSections = (this.activeSections || []).filter(id => validIds.includes(id));
        if (this.activeSections.length === 0 && this.detailCats.length > 0) {
          this.addSection(this.detailCats[0]);
        }
      },
    },
    detailCats: {
      handler(newCats) {
        const validIds = (newCats || []).map(c => c.id);
        this.activeSections = (this.activeSections || []).filter(id => validIds.includes(id));
        if (this.activeSections.length === 0 && validIds.length > 0) {
          this.addSection(newCats[0]);
        }
      },
      immediate: true,
      deep: true,
    },
  },
  computed: {
    prodStore() { return useProduction(); },
    main() { return useMain(); },
    auth() { return useAuth(); },
    lang() { return this.main.lang?.code || 'en'; },
    detailCats() {
      return useDetailProductionCategory().list.filter(d => d.production_category_id === this.prodStore.production?.production_category_id);
    },
    availableCats() {
      return this.detailCats.filter(c => !this.activeSections.includes(c.id));
    },
    groupedDetails() {
      return this.activeSections.map(catId => {
        const category = this.detailCats.find(c => c.id === catId);
        if (!category) return null;
        return {
          category,
          items: (this.prodStore.production?.details || []).filter(d => d.detail_production_category_id === catId),
        };
      }).filter(Boolean);
    },
    warehouseOptions() {
      return this.auth.warehouse.list.map(w => ({ title: w.name, value: w.id }));
    },
    truckOptions() {
      return this.auth.truck.list.map(t => ({ title: t.registration_plate, value: t.id }));
    },
    packageTypeOptions() {
      return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en, value: p.id }));
    },
  },
  methods: {
    initForm(catId, category) {
      const defaultDesc = category && category.article_type === 'text' ? (category[`name_${this.lang}`] || category.name_fr || category.name_en || '') : '';
      this.entryForms[catId] = {
        article_description: defaultDesc,
        stockable_id: null,
        weight: null,
        unit_price: null,
        total_price: null,
        weight_invoice: null,
        warehouse_id: null,
        truck_id: null,
        package_type_id: null,
        nbr_box: null,
      };
    },
    toggleSection(cat) {
      if (this.activeSections.includes(cat.id)) {
        if (this.activeSections.length > 1) {
          this.removeGroup(cat.id);
        }
      } else {
        this.addSection(cat);
      }
    },
    addSection(dc) {
      if (!this.activeSections.includes(dc.id)) {
        this.activeSections.push(dc.id);
        this.initForm(dc.id, dc);
      }
    },
    removeGroup(catId) {
      const items = this.prodStore.production.details.filter(d => d.detail_production_category_id === catId);
      items.forEach(d => this.prodStore.remove_detail(d._uid));
      this.activeSections = this.activeSections.filter(id => id !== catId);
      delete this.entryForms[catId];
    },
    getSectionItemCount(catId) {
      return (this.prodStore.production?.details || []).filter(d => d.detail_production_category_id === catId).length;
    },
    getGroupTotalWeight(group) {
      return group.items.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0);
    },
    getGroupTotalPrice(group) {
      return group.items.reduce((sum, item) => sum + (parseFloat(item.total_price) || 0), 0);
    },
    getFormPricePreview(cat) {
      const f = this.entryForms[cat.id];
      if (!f) return null;
      if (f.total_price && Number(f.total_price) > 0) {
        return `${this.main.formatDisplayNumber(f.total_price)} ${this.main.currency}`;
      }
      return null;
    },
    isFormValid(category) {
      const f = this.entryForms[category.id];
      if (!f) return false;
      if (category.article_type === 'text') {
        if (!f.article_description?.trim()) return false;
      } else {
        if (!f.stockable_id) return false;
      }
      if (category.allow_weight && (!f.weight || f.weight <= 0)) return false;
      if (category.allow_unit_price && (!f.unit_price || f.unit_price <= 0)) return false;
      if (category.allow_price && (!category.allow_weight || !category.allow_unit_price) && (!f.total_price || f.total_price <= 0)) return false;
      if (category.allow_package_type && !f.package_type_id) return false;
      if (category.allow_warehouse && !f.warehouse_id) return false;
      return true;
    },
    onFormEnterKey(category) {
      if (this.isFormValid(category)) {
        this.addArticle(category);
      }
    },
    addArticle(cat) {
      const f = this.entryForms[cat.id];
      if (!f) return;
      if (cat.allow_weight && cat.allow_unit_price && !cat.allow_price) {
        f.total_price = (parseFloat(f.weight) || 0) * (parseFloat(f.unit_price) || 0);
      }
      this.prodStore.add_detail(cat.id);
      const last = this.prodStore.production.details[this.prodStore.production.details.length - 1];
      Object.assign(last, {
        article_description: f.article_description || null,
        stockable_type: cat.article_type !== 'text' ? cat.article_type : null,
        stockable_id: f.stockable_id,
        weight: f.weight,
        unit_price: f.unit_price,
        total_price: f.total_price,
        weight_invoice: f.weight_invoice,
        warehouse_id: f.warehouse_id,
        warehouse_operation: cat.warehouse_operation || (f.warehouse_id ? 'stock_in' : null),
        truck_id: f.truck_id,
        package_type_id: f.package_type_id,
        nbr_box: f.nbr_box,
      });
      this.prodStore.recalc_totals();
      this.initForm(cat.id, cat);
      this.$nextTick(() => {
        const c = this.$refs['group_' + cat.id];
        const el = c?.[0] || c;
        if (el) {
          const input = el.querySelector('input');
          if (input) input.focus();
        }
      });
    },
    editArticle(item, cat) {
      this.entryForms[cat.id] = {
        article_description: item.article_description || '',
        stockable_id: item.stockable_id,
        weight: item.weight,
        unit_price: item.unit_price,
        total_price: item.total_price,
        weight_invoice: item.weight_invoice,
        warehouse_id: item.warehouse_id,
        truck_id: item.truck_id,
        package_type_id: item.package_type_id,
        nbr_box: item.nbr_box,
      };
      this.prodStore.remove_detail(item._uid);
    },
    getStockOpts(category) {
      const articleType = category.article_type || category;
      if (articleType === 'FishType') {
        let fishList = this.auth.fish_type.list;
        const filter = category.fish_type_filter;
        if (filter && filter !== 'all') {
          fishList = fishList.filter(f => f.type === filter);
        }
        return fishList.map(f => ({ title: f[`name_${this.lang}`] || f.name_en, value: f.id }));
      }
      if (articleType === 'GuanoType') return this.auth.guano_type.list.map(g => ({ title: g[`name_${this.lang}`] || g.name_en, value: g.id }));
      if (articleType === 'PackageType') return this.auth.package_type.list.map(p => ({ title: p[`name_${this.lang}`] || p.name_en, value: p.id }));
      return [];
    },
    getArticleName(item, cat) {
      if (cat.article_type === 'text') return item.article_description || '-';
      return this.getStockOpts(cat).find(o => o.value === item.stockable_id)?.title || '-';
    },
    getWarehouseName(id) {
      const w = this.auth.warehouse.list.find(w => w.id === id);
      return w ? w.name : `#${id}`;
    },
    getPackageTypeName(id) {
      const p = this.auth.package_type.list.find(p => p.id === id);
      return p ? (p[`name_${this.lang}`] || p.name_en || `#${id}`) : '-';
    },
    getHeaders(cat) {
      const h = [{ title: this.$t('type_article'), key: 'article', sortable: false }];
      if (cat.allow_package_type || cat.allow_nbr_box || cat.allow_secondary_packaging) {
        h.push({ title: this.$t('package_type'), key: 'package_type', sortable: false });
        h.push({ title: this.$t('nbr_boxes'), key: 'nbr_box', sortable: false });
      }
      if (cat.allow_weight) h.push({ title: this.$t('weight') + ' (Kg)', key: 'weight', sortable: false });
      if (cat.allow_weight) h.push({ title: this.$t('total_weight_invoice'), key: 'weight_invoice', sortable: false });
      if (cat.allow_unit_price) h.push({ title: this.$t('unit_price'), key: 'unit_price', sortable: false });
      if (cat.allow_price) h.push({ title: this.$t('total_price'), key: 'total_price', sortable: false });
      if (cat.allow_warehouse) h.push({ title: this.$t('warehouse'), key: 'warehouse', sortable: false });
      h.push({ title: this.$t('actions'), key: 'actions', sortable: false, width: 80 });
      return h;
    },
    getFilterableColumns(cat) {
      return this.getHeaders(cat).filter(h => h.key !== 'actions');
    },
    getDisplayValue(item, key, cat) {
      if (key === 'article') return this.getArticleName(item, cat);
      if (key === 'package_type') return item.package_type_id ? this.getPackageTypeName(item.package_type_id) : '';
      if (key === 'nbr_box') return item.nbr_box != null ? String(item.nbr_box) : '';
      if (key === 'warehouse') return item.warehouse_id ? this.getWarehouseName(item.warehouse_id) : '';
      if (['weight', 'weight_invoice', 'unit_price', 'total_price'].includes(key)) return item[key] != null ? String(item[key]) : '';
      return item[key] != null ? String(item[key]) : '';
    },
    getFilteredItems(group) {
      const filters = this.columnFilters[group.category.id];
      if (!filters) return group.items;
      return group.items.filter(item => Object.entries(filters).every(([key, sel]) => !sel || sel.length === 0 || sel.includes(this.getDisplayValue(item, key, group.category))));
    },
    getFilterOptions(group, colKey) {
      const filters = this.columnFilters[group.category.id] || {};
      let items = group.items;
      Object.entries(filters).forEach(([key, sel]) => {
        if (key !== colKey && sel && sel.length > 0) items = items.filter(i => sel.includes(this.getDisplayValue(i, key, group.category)));
      });
      const vals = [...new Set(items.map(i => this.getDisplayValue(i, colKey, group.category)).filter(v => v !== ''))];
      return vals.sort().map(v => ({ title: v, value: v }));
    },
    hasFilter(catId, colKey) {
      const f = this.columnFilters[catId];
      return f && f[colKey] && f[colKey].length > 0;
    },
    getColumnFilter(catId, colKey) {
      return this.columnFilters[catId]?.[colKey] || [];
    },
    setColumnFilter(catId, colKey, val) {
      if (!this.columnFilters[catId]) this.columnFilters[catId] = {};
      this.columnFilters[catId][colKey] = val;
    },
    clearColumnFilter(catId, colKey) {
      if (this.columnFilters[catId]) this.columnFilters[catId][colKey] = [];
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
        if (pkg && pkg.size > 0) {
          f.nbr_box = Math.round((w / pkg.size) * 100) / 100;
        }
      } else {
        f.nbr_box = null;
      }
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
      try {
        sessionStorage.setItem('detail_productions_draft', JSON.stringify({ entryForms: this.entryForms, activeSections: this.activeSections }));
      } catch (e) { /* quota */ }
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
.detail-productions { font-family: 'Inter', sans-serif; }
.text-prod { color: #00897B !important; }

.category-quick-bar {
  background: rgba(var(--v-theme-on-surface), .03);
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
}
.detail-section-card {
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  background: rgba(var(--v-theme-surface), 1);
  overflow: hidden;
}
.section-badge-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.live-preview-pill {
  background: rgba(76, 175, 80, .1);
  border: 1px solid rgba(76, 175, 80, .25);
}
.table-summary-strip {
  background: rgba(var(--v-theme-on-surface), .02);
}
</style>

