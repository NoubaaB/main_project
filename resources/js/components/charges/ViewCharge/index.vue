<template>
  <div class="view-charge-page">
    <!-- Loading -->
    <div v-if="_charge.view_charge_loading" class="d-flex justify-center align-center" style="min-height:400px">
      <div class="text-center">
        <v-progress-circular indeterminate color="info" size="52" width="4"></v-progress-circular>
        <div class="text-medium-emphasis text-body-2 mt-4">{{ $t('loading') }}...</div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="!charge" class="d-flex justify-center align-center" style="min-height:400px">
      <v-card class="vc-empty rounded-xl text-center pa-12" variant="flat" max-width="460">
        <div class="vc-empty-ring mx-auto mb-5">
          <v-icon size="48" color="info">mdi-file-search-outline</v-icon>
        </div>
        <h3 class="text-h6 font-weight-bold mb-1">{{ $t('no_data_available') }}</h3>
        <p class="text-body-2 text-medium-emphasis mb-6">{{ $t('charge_not_found_hint') }}</p>
        <v-btn color="info" rounded="lg" size="large" @click="$router.push({ name: 'add_charge' })">
          <v-icon start>mdi-plus</v-icon> {{ $t('add_charge') }}
        </v-btn>
      </v-card>
    </div>

    <!-- Content -->
    <template v-else>

      <!-- ══════ HEADER ══════ -->
      <div class="vc-hero rounded-xl mb-5">
        <div class="vc-hero-bg"></div>
        <div class="vc-hero-inner">
          <v-row align="center" no-gutters>
            <v-col cols="auto" class="me-4">
              <div class="vc-hero-icon">
                <v-icon size="28" color="white">mdi-warehouse</v-icon>
              </div>
            </v-col>
            <v-col>
              <div class="vc-hero-title">{{ $t('charge_summary') }}</div>
              <div class="vc-hero-chips mt-1">
                <v-chip size="x-small" color="white" variant="outlined" class="me-1">
                  <v-icon start size="11">mdi-pound</v-icon>{{ charge.id }}
                </v-chip>
                <v-chip size="x-small" color="white" variant="outlined" class="me-1">
                  <v-icon start size="11">mdi-calendar</v-icon>{{ main.formateDate(charge.date) }}
                </v-chip>
                <v-chip v-for="tn in (charge.tracking_numbers || [])" :key="tn" size="x-small" color="white" variant="outlined" class="me-1">
                  <v-icon start size="11">mdi-barcode</v-icon>{{ tn }}
                </v-chip>
              </div>
            </v-col>
            <v-col cols="auto" class="d-flex ga-2 flex-wrap">
              <v-btn color="white" variant="tonal" rounded="lg" size="small" @click="$router.push({ name: 'edit_charge', params: { id: charge.id } })">
                <v-icon start size="16">mdi-pencil-outline</v-icon>{{ $t('edit_item') }}
              </v-btn>
              <v-btn color="white" variant="outlined" rounded="lg" size="small" @click="$router.push({ name: 'list_charge' })">
                <v-icon start size="16">mdi-format-list-bulleted</v-icon>{{ $t('list_charge') }}
              </v-btn>
              <v-btn color="white" variant="text" rounded="lg" size="small" @click="$router.back()">
                <v-icon start size="16">mdi-arrow-left</v-icon>{{ $t('back_previous') }}
              </v-btn>
            </v-col>
          </v-row>
        </div>
      </div>

      <!-- ══════ STAT CARDS ══════ -->
      <v-row class="mb-5" dense>
        <v-col v-for="(s, i) in stats" :key="i" cols="6" sm="3">
          <v-card class="vc-stat rounded-xl" variant="flat">
            <v-card-text class="d-flex align-center ga-3 pa-4">
              <div :class="['vc-stat-icon', `vc-stat-icon--${s.t}`]">
                <v-icon size="20" color="white">{{ s.icon }}</v-icon>
              </div>
              <div class="min-width-0">
                <div class="vc-stat-lbl">{{ s.label }}</div>
                <div class="vc-stat-val text-truncate" :title="s.value">{{ s.value }}</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- ══════ PANELS ══════ -->
      <v-row>
        <!-- LEFT — Info -->
        <v-col cols="12" md="5">
          <v-card class="rounded-xl vc-info-card" variant="flat">
            <div class="vc-info-head">
              <v-icon color="info" size="18" class="me-2">mdi-information-slab-circle-outline</v-icon>
              {{ $t('necessary_information') }}
            </div>
            <v-divider></v-divider>
            <v-list density="compact" class="pa-0">
              <template v-for="(r, ri) in infoRows" :key="ri">
                <v-list-item class="vc-info-row">
                  <template #prepend>
                    <v-icon :color="r.color || 'info'" size="17" class="me-3">{{ r.icon }}</v-icon>
                  </template>
                  <v-list-item-title class="vc-info-lbl">{{ r.label }}</v-list-item-title>
                  <template #append>
                    <div v-if="r.chips" class="d-flex flex-wrap ga-1 justify-end" style="max-width:200px">
                      <v-chip v-for="(c, ci) in r.chips" :key="ci" :color="r.chipColor || 'info'" variant="tonal" size="x-small">{{ c }}</v-chip>
                    </div>
                    <span v-else class="vc-info-val" :class="{'font-weight-bold': r.bold, 'text-info': r.bold}">{{ r.value }}</span>
                  </template>
                </v-list-item>
                <v-divider v-if="ri < infoRows.length - 1" class="mx-4 border-opacity-5"></v-divider>
              </template>
            </v-list>
          </v-card>
        </v-col>

        <!-- RIGHT — Timeline Detail View -->
        <v-col cols="12" md="7">
          <template v-if="groupedDetails.length">
            <!-- Column visibility toggles -->
            <v-card class="rounded-xl mb-4 pa-3" variant="flat">
              <div class="d-flex align-center flex-wrap ga-1">
                <span class="text-caption font-weight-bold text-medium-emphasis me-2">
                  <v-icon size="14" class="me-1">mdi-view-column-outline</v-icon>{{ $t('columns') }}:
                </span>
                <v-btn
                  v-for="(col, ci) in allColumnsWithTitles"
                  :key="col.key"
                  size="small"
                  rounded="lg"
                  :variant="col.visible ? 'tonal' : 'outlined'"
                  :color="col.visible ? 'info' : 'grey'"
                  class="vc-col-btn"
                  @click="allColumns[ci].visible = !allColumns[ci].visible"
                >
                  <v-icon start size="14">{{ col.icon }}</v-icon>
                  {{ col.title }}
                </v-btn>
              </div>
            </v-card>
            <v-timeline side="end" density="compact" line-color="info" class="vc-timeline">
              <v-timeline-item
                v-for="(group, gi) in groupedDetails"
                :key="gi"
                :dot-color="timelineColors[gi % timelineColors.length]"
                size="small"
              >
                <!-- Dot icon -->
                <template #icon>
                  <v-icon size="14" color="white">mdi-layers-outline</v-icon>
                </template>

                <!-- Timeline card -->
                <v-card class="rounded-xl vc-tl-card" variant="flat">
                  <!-- Card header -->
                  <div class="vc-tl-head" :style="{ borderLeft: `3px solid ${timelineHexColors[gi % timelineHexColors.length]}` }">
                    <div class="d-flex align-center flex-wrap ga-2">
                      <v-chip :color="timelineColors[gi % timelineColors.length]" variant="tonal" size="small" class="font-weight-bold">
                        <v-icon start size="13">mdi-layers-outline</v-icon>
                        {{ group.operationName }}
                      </v-chip>
                      <v-chip color="grey" variant="outlined" size="x-small">
                        {{ getGroupFiltered(group).length }} {{ $t('items') }}
                      </v-chip>
                      <v-spacer></v-spacer>
                      <v-btn v-if="hasGroupFilters(group.opId)" icon size="x-small" variant="tonal" color="warning" @click="clearGroupFilters(group.opId)">
                        <v-icon size="14">mdi-filter-off</v-icon>
                        <v-tooltip activator="parent" location="top">{{ $t('clear_filters') }}</v-tooltip>
                      </v-btn>
                    </div>
                  </div>

                  <v-card-text class="pa-0">
                    <div class="vc-tbl-wrap">
                      <table class="vc-tbl">
                        <thead>
                          <tr>
                            <th v-for="col in visibleColumns" :key="col.key" :style="{ minWidth: col.minW || 'auto' }">
                              <div class="vc-th-cell">
                                <span class="vc-th-text">{{ col.title }}</span>
                                <v-menu :close-on-content-click="false" location="bottom start" max-height="320" min-width="200">
                                  <template #activator="{ props }">
                                    <v-btn v-bind="props" :icon="true" size="18" variant="text"
                                      :color="getGroupFilter(group.opId, col.key).length ? 'info' : 'grey'" class="vc-filter-btn">
                                      <v-icon size="13">{{ getGroupFilter(group.opId, col.key).length ? 'mdi-filter' : 'mdi-filter-outline' }}</v-icon>
                                    </v-btn>
                                  </template>
                                  <v-card class="rounded-lg" min-width="220" max-width="300">
                                    <v-card-title class="text-caption font-weight-bold py-2 px-3 d-flex align-center">
                                      <v-icon size="14" class="me-1" color="info">mdi-filter-variant</v-icon>
                                      {{ col.title }}
                                      <v-spacer></v-spacer>
                                      <v-btn size="x-small" variant="text" color="warning" @click="clearGroupFilter(group.opId, col.key)" v-if="getGroupFilter(group.opId, col.key).length">
                                        {{ $t('clear') }}
                                      </v-btn>
                                    </v-card-title>
                                    <v-divider></v-divider>
                                    <v-list density="compact" class="pa-1" style="max-height:200px;overflow-y:auto">
                                      <v-list-item
                                        v-for="opt in getGroupColumnOptions(group, col)"
                                        :key="opt"
                                        @click="toggleGroupFilter(group.opId, col.key, opt)"
                                        :class="{ 'vc-filter-active': getGroupFilter(group.opId, col.key).includes(opt) }"
                                        class="vc-filter-item rounded-lg"
                                        density="compact"
                                      >
                                        <template #prepend>
                                          <v-icon size="18" :color="getGroupFilter(group.opId, col.key).includes(opt) ? 'info' : 'grey-lighten-1'" class="me-2">
                                            {{ getGroupFilter(group.opId, col.key).includes(opt) ? 'mdi-checkbox-marked' : 'mdi-checkbox-blank-outline' }}
                                          </v-icon>
                                        </template>
                                        <v-list-item-title class="text-caption">{{ col.format ? col.format(opt) : opt }}</v-list-item-title>
                                      </v-list-item>
                                      <v-list-item v-if="getGroupColumnOptions(group, col).length === 0" class="text-center">
                                        <v-list-item-title class="text-caption text-medium-emphasis">{{ $t('no_data_available') }}</v-list-item-title>
                                      </v-list-item>
                                    </v-list>
                                  </v-card>
                                </v-menu>
                              </div>
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, idx) in getGroupFiltered(group)" :key="idx" class="vc-tbl-row">
                            <td v-for="col in visibleColumns" :key="col.key">
                              <!-- Article -->
                              <template v-if="col.key === 'article'">
                                <v-chip :color="getColor(item.stockable_type)" size="small" variant="tonal" class="font-weight-medium">
                                  {{ main.getStockableName(item.stockable_id, item.stockable_type) }}
                                </v-chip>
                              </template>
                              <!-- Weight -->
                              <template v-else-if="col.key === 'weight'">
                                <span class="font-weight-medium">{{ main.formatDisplayNumber(item.total_weight) }}</span>
                                <span class="vc-unit">Kg</span>
                              </template>
                              <!-- Weight Bill -->
                              <template v-else-if="col.key === 'weightBill'">
                                <span class="font-weight-medium">{{ main.formatDisplayNumber(item.total_weight_bill) }}</span>
                                <span class="vc-unit">Kg</span>
                              </template>
                              <!-- Unit Price -->
                              <template v-else-if="col.key === 'unitPrice'">
                                <span class="font-weight-medium">{{ main.formatDisplayNumber(item.unit_price) }}</span>
                                <span class="vc-unit">{{ main.currency }}</span>
                              </template>
                              <!-- Total Price -->
                              <template v-else-if="col.key === 'totalPrice'">
                                <span class="font-weight-bold text-info">{{ main.formatDisplayNumber(item.total_price) }}</span>
                                <span class="vc-unit">{{ main.currency }}</span>
                              </template>
                              <!-- Package Count -->
                              <template v-else-if="col.key === 'packageCount'">
                                <span class="font-weight-medium">{{ item.total_package_count || '—' }}</span>
                              </template>
                              <!-- Package Type -->
                              <template v-else-if="col.key === 'packageType'">
                                <span class="font-weight-medium">{{ main.getPackageTypeName ? main.getPackageTypeName(item.package_type_id) : (item.package_type_id || '—') }}</span>
                              </template>
                              <!-- Type -->
                              <template v-else-if="col.key === 'type'">
                                <v-chip :color="getColor(item.stockable_type)" size="x-small" variant="outlined">{{ item.stockable_type }}</v-chip>
                              </template>
                              <!-- Source -->
                              <template v-else-if="col.key === 'source'">
                                <v-chip :color="item.source === 'warehouse' ? 'indigo' : 'teal'" size="x-small" variant="tonal" class="font-weight-bold">
                                  <v-icon start size="10">{{ item.source === 'warehouse' ? 'mdi-warehouse' : 'mdi-arrow-right-bold' }}</v-icon>
                                  {{ item.source === 'warehouse' ? $t('warehouse_name') : $t('direct') }}
                                </v-chip>
                              </template>
                              <!-- Fallback -->
                              <template v-else>
                                <span>{{ col.extract(item) }}</span>
                              </template>
                            </td>
                          </tr>
                          <tr v-if="getGroupFiltered(group).length === 0">
                            <td :colspan="visibleColumns.length" class="text-center pa-6">
                              <v-icon size="30" color="grey" class="mb-1">mdi-filter-off-outline</v-icon>
                              <div class="text-medium-emphasis text-caption">{{ $t('no_data_available') }}</div>
                            </td>
                          </tr>
                        </tbody>
                        <tfoot>
                          <tr class="vc-tbl-foot">
                            <td v-for="(col, ci) in visibleColumns" :key="col.key">
                              <template v-if="ci === 0">
                                <span class="font-weight-bold text-uppercase text-caption" style="opacity:.5">{{ $t('total') }}</span>
                              </template>
                              <template v-else-if="col.key === 'weight'">
                                <v-chip :color="timelineColors[gi % timelineColors.length]" variant="tonal" size="small">
                                  <v-icon start size="13">mdi-weight-kilogram</v-icon>
                                  {{ main.formatDisplayNumber(getGroupTotalWeight(group)) }} Kg
                                </v-chip>
                              </template>
                              <template v-else-if="col.key === 'totalPrice'">
                                <v-chip :color="timelineColors[gi % timelineColors.length]" variant="tonal" size="small">
                                  <v-icon start size="13">mdi-cash</v-icon>
                                  {{ main.formatDisplayNumber(getGroupTotalPrice(group)) }} {{ main.currency }}
                                </v-chip>
                              </template>
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </v-card-text>
                </v-card>
              </v-timeline-item>
            </v-timeline>
          </template>

          <!-- No details -->
          <v-card v-else class="rounded-xl text-center pa-10" variant="flat">
            <v-icon size="48" color="grey" class="mb-3">mdi-clipboard-text-outline</v-icon>
            <div class="text-medium-emphasis">{{ $t('no_data_available') }}</div>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script>
import { useCharge } from '@/stores/Charge';
import { useMain } from '@/stores/Main';
import { useChargeCategory } from '@/stores/ChargeCategory';
import { useFishery } from '@/stores/Fishery';
import { useOrigin } from '@/stores/Origin';
import { useTruck } from '@/stores/Truck';
import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';

export default {
  beforeMount() {
    if (this.$route.params.id) this._charge.fetchChargeForView(this.$route.params.id);
  },
  data() {
    return {
      filters: {},
      filterSearches: {},
      timelineColors: ['info', 'teal', 'purple', 'deep-orange', 'indigo', 'green'],
      timelineHexColors: ['#1E88E5', '#00897B', '#8E24AA', '#F4511E', '#3949AB', '#43A047'],
      allColumns: [
        { key: 'article',      icon: 'mdi-fish',             visible: true  },
        { key: 'weight',       icon: 'mdi-weight-kilogram',  visible: true  },
        { key: 'weightBill',   icon: 'mdi-scale-balance',    visible: false },
        { key: 'unitPrice',    icon: 'mdi-currency-usd',     visible: true  },
        { key: 'totalPrice',   icon: 'mdi-cash-multiple',    visible: true  },
        { key: 'packageCount', icon: 'mdi-package-variant',  visible: false },
        { key: 'packageType',  icon: 'mdi-package',          visible: false },
        { key: 'type',         icon: 'mdi-shape-outline',    visible: false },
        { key: 'source',       icon: 'mdi-source-branch',    visible: false },
      ],
    };
  },
  computed: {
    _charge() { return useCharge(); },
    main()    { return useMain(); },
    cCat()    { return useChargeCategory(); },
    fishery() { return useFishery(); },
    origin()  { return useOrigin(); },
    truck()   { return useTruck(); },
    wcOp()    { return useWarehouseCategoryOperation(); },
    charge()  { return this._charge.view_charge; },

    // Column definitions
    columnDefs() {
      return {
        article:      { title: this.$t('type_article'),                                     minW: '140px', extract: item => this.main.getStockableName(item.stockable_id, item.stockable_type), format: v => v },
        weight:       { title: this.$t('weight_(KG)'),                                      minW: '90px',  extract: item => this.main.formatDisplayNumber(item.total_weight), format: v => v + ' Kg' },
        weightBill:   { title: this.$t('weight_invoice_(KG)'),                               minW: '90px',  extract: item => this.main.formatDisplayNumber(item.total_weight_bill), format: v => v + ' Kg' },
        unitPrice:    { title: this.$t('pu_(DH)', { currency: this.main.currency }),          minW: '90px',  extract: item => this.main.formatDisplayNumber(item.unit_price), format: v => v + ' ' + this.main.currency },
        totalPrice:   { title: this.$t('total_price_(DH)', { currency: this.main.currency }),minW: '100px', extract: item => this.main.formatDisplayNumber(item.total_price), format: v => v + ' ' + this.main.currency },
        packageCount: { title: this.$t('nbr_boxes'),                                        minW: '80px',  extract: item => String(item.total_package_count || 0), format: v => v },
        packageType:  { title: this.$t('package_type'),                                     minW: '100px', extract: item => String(item.package_type_id || '—'), format: v => v },
        type:         { title: this.$t('type'),                                             minW: '90px',  extract: item => item.stockable_type, format: v => v },
        source:       { title: this.$t('source'),                                           minW: '90px',  extract: item => item.source === 'warehouse' ? this.$t('warehouse_name') : this.$t('direct'), format: v => v },
      };
    },

    // Visible columns = merge allColumns visibility + columnDefs metadata
    visibleColumns() {
      return this.allColumns
        .filter(c => c.visible)
        .map(c => ({ key: c.key, ...this.columnDefs[c.key] }));
    },

    // All columns with titles (for toggle buttons)
    allColumnsWithTitles() {
      return this.allColumns.map(c => ({ ...c, title: this.columnDefs[c.key]?.title || c.key }));
    },


    hasActiveFilters() {
      return Object.values(this.filters).some(f => f.length > 0);
    },

    // Stats
    stats() {
      return [
        { icon: 'mdi-cash-multiple',   t: 'blue',   label: this.$t('total_price'),     value: this.main.formatDisplayNumber(this.charge?.total_price || 0) + ' ' + this.main.currency },
        { icon: 'mdi-weight-kilogram', t: 'teal',   label: this.$t('total_weight'),    value: this.main.formatDisplayNumber(this.charge?.total_weight || 0) + ' Kg' },
        { icon: 'mdi-truck-delivery',  t: 'purple', label: this.$t('suppliers'),        value: this.supplierNames || this.$t('n/a') },
        { icon: 'mdi-tag-outline',     t: 'amber',  label: this.$t('charge_category'),  value: this.catName || this.$t('n/a') },
      ];
    },

    catName() {
      if (!this.charge?.charge_category_id) return '';
      const c = this.cCat.getList.find(x => x.id === this.charge.charge_category_id);
      return c ? c[`name_${this.main.lang.short}`] : '';
    },
    supplierNames() {
      if (this.charge?.suppliers?.length) return this.charge.suppliers.map(s => s.name).join(', ');
      if (this.charge?.supplier_ids?.length) return this.charge.supplier_ids.map(id => this.main.supplier.list.find(s => s.id === id)?.name).filter(Boolean).join(', ');
      return '';
    },
    fisheryName() {
      if (!this.charge?.fishery_id) return '';
      const f = this.fishery.getList.find(x => x.id === this.charge.fishery_id);
      return f ? f[`name_${this.main.lang.short}`] : '';
    },
    originName() {
      if (!this.charge?.origin_id) return '';
      const o = this.origin.getList.find(x => x.id === this.charge.origin_id);
      return o ? o.name : '';
    },
    truckPlates() {
      if (!this.charge?.truck_ids?.length) return '';
      return this.charge.truck_ids.map(id => this.truck.list?.find(t => t.id === id)?.registration_plate || id).join(', ');
    },

    infoRows() {
      const rows = [
        { icon: 'mdi-calendar', label: this.$t('date'), value: this.main.formateDate(this.charge.date) },
        { icon: 'mdi-tag-outline', label: this.$t('charge_category'), chips: this.catName ? [this.catName] : null, value: this.$t('n/a'), chipColor: 'info' },
      ];
      if (this.charge.tracking_numbers?.length)     rows.push({ icon: 'mdi-barcode',          label: this.$t('tracking_numbers'),     chips: this.charge.tracking_numbers,                  chipColor: 'info' });
      if (this.charge.tracking_onp_numbers?.length)  rows.push({ icon: 'mdi-barcode-scan',     label: this.$t('tracking_onp_numbers'),  chips: this.charge.tracking_onp_numbers,               chipColor: 'teal' });
      if (this.charge.suppliers?.length)             rows.push({ icon: 'mdi-truck-delivery',   label: this.$t('suppliers'),             chips: this.charge.suppliers.map(s => s.name),         chipColor: 'purple' });
      if (this.fisheryName) rows.push({ icon: 'mdi-fish',               label: this.$t('fishery'), chips: [this.fisheryName], chipColor: 'blue-grey' });
      if (this.originName)  rows.push({ icon: 'mdi-map-marker-outline', label: this.$t('origin'),  chips: [this.originName],  chipColor: 'deep-orange' });
      if (this.truckPlates) rows.push({ icon: 'mdi-truck-outline',      label: this.$t('truck_id'), value: this.truckPlates });
      rows.push({ icon: 'mdi-cash',            label: this.$t('total_price'),  value: this.main.formatDisplayNumber(this.charge.total_price || 0) + ' ' + this.main.currency, bold: true });
      rows.push({ icon: 'mdi-weight-kilogram', label: this.$t('total_weight'), value: this.main.formatDisplayNumber(this.charge.total_weight || 0) + ' Kg', bold: true });
      if (this.charge.note) rows.push({ icon: 'mdi-note-text-outline', label: this.$t('charge_note'), value: this.charge.note });
      return rows;
    },

    allDetails() {
      if (!this.charge) return [];
      const items = [];
      (this.charge.non_warehouse_stockable_operations || []).forEach(op => items.push({ ...op, source: 'direct' }));
      (this.charge.warehouse_stockable_tracking_number_operations || []).forEach(op => items.push({ ...op, source: 'warehouse' }));
      return items;
    },

    // Group items by warehouse_category_operation_id
    groupedDetails() {
      const map = {};
      this.allDetails.forEach(item => {
        const opId = item.warehouse_category_operation_id || 'none';
        if (!map[opId]) {
          map[opId] = {
            opId,
            operationName: opId !== 'none' ? this.wcOp.getName(opId) : this.$t('direct'),
            items: [],
          };
        }
        map[opId].items.push(item);
      });
      return Object.values(map);
    },
  },
  methods: {
    getColor(type) {
      return { FishType: 'info', PackageType: 'teal', GuanoType: 'brown' }[type] || 'grey';
    },
    // Filter helpers per group
    getGroupFilter(opId, colKey) {
      const k = `${opId}_${colKey}`;
      if (!this.filters[k]) this.filters[k] = [];
      return this.filters[k];
    },
    toggleGroupFilter(opId, colKey, val) {
      const k = `${opId}_${colKey}`;
      if (!this.filters[k]) this.filters[k] = [];
      const idx = this.filters[k].indexOf(val);
      if (idx === -1) this.filters[k].push(val); else this.filters[k].splice(idx, 1);
    },
    clearGroupFilter(opId, colKey) {
      const k = `${opId}_${colKey}`;
      this.filters[k] = [];
    },
    hasGroupFilters(opId) {
      return this.allColumns.some(col => this.getGroupFilter(opId, col.key).length > 0);
    },
    clearGroupFilters(opId) {
      this.allColumns.forEach(col => { this.filters[`${opId}_${col.key}`] = []; });
    },
    clearAllFilters() {
      Object.keys(this.filters).forEach(k => { this.filters[k] = []; });
    },
    getGroupFiltered(group) {
      return group.items.filter(item => {
        return this.visibleColumns.every(col => {
          const f = this.getGroupFilter(group.opId, col.key);
          if (!f.length) return true;
          return f.includes(col.extract(item));
        });
      });
    },
    getGroupColumnOptions(group, col) {
      const set = new Set();
      group.items.forEach(item => set.add(col.extract(item)));
      return [...set].sort();
    },
    getGroupTotalWeight(group) {
      return this.getGroupFiltered(group).reduce((s, i) => s + (parseFloat(i.total_weight) || 0), 0);
    },
    getGroupTotalPrice(group) {
      return this.getGroupFiltered(group).reduce((s, i) => s + (parseFloat(i.total_price) || 0), 0);
    },
  },
  watch: {
    '$route.params.id'(id) { if (id) this._charge.fetchChargeForView(id); },
  },
};
</script>

<style scoped>
/* ══════════════════════════════════════
   ViewCharge — Premium Design v3
   ══════════════════════════════════════ */

/* ── Hero Header ── */
.vc-hero {
  position: relative;
  overflow: hidden;
  color: #fff;
}
.vc-hero-bg {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
}
.vc-hero-inner {
  position: relative; z-index: 1;
  padding: 22px 26px;
}
.vc-hero-icon {
  width: 52px; height: 52px;
  border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255,255,255,.15);
}
.vc-hero-title {
  font-size: 19px;
  font-weight: 800;
  letter-spacing: .3px;
}
.vc-hero-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

/* ── Empty ── */
.vc-empty {
  border: 2px dashed rgba(var(--v-border-color), .10);
}
.vc-empty-ring {
  width: 96px; height: 96px;
  border-radius: 50%;
  background: rgba(33,150,243,.06);
  display: flex; align-items: center; justify-content: center;
}

/* ── Stat Cards ── */
.vc-stat {
  transition: transform .2s, box-shadow .2s;
}
.vc-stat:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,.06) !important;
}
.vc-stat-icon {
  width: 42px; height: 42px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.vc-stat-icon--blue   { background: linear-gradient(135deg, #1565C0, #42A5F5); }
.vc-stat-icon--teal   { background: linear-gradient(135deg, #00796B, #26A69A); }
.vc-stat-icon--purple { background: linear-gradient(135deg, #6A1B9A, #AB47BC); }
.vc-stat-icon--amber  { background: linear-gradient(135deg, #E65100, #FFA726); }
.vc-stat-lbl {
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .7px;
  opacity: .4;
}
.vc-stat-val {
  font-size: 14px; font-weight: 800;
  margin-top: 1px;
}

/* ── Info Panel ── */
.vc-info-card { overflow: hidden; }
.vc-info-head {
  font-size: 14px; font-weight: 700;
  padding: 13px 16px;
  display: flex; align-items: center;
}
.vc-info-row {
  padding: 8px 16px !important;
  min-height: 38px;
}
.vc-info-lbl {
  font-size: 12.5px; font-weight: 500; opacity: .55;
}
.vc-info-val {
  font-size: 12.5px; font-weight: 600;
}

/* ── Table Panel ── */
.vc-tbl-head {
  font-size: 14px; font-weight: 700;
  padding: 13px 16px;
  display: flex; align-items: center;
}

/* Custom table */
.vc-tbl-wrap {
  overflow-x: auto;
  padding: 0 4px;
}
.vc-tbl {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}
.vc-tbl th {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  opacity: .5;
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid rgba(var(--v-border-color), .08);
  white-space: nowrap;
  position: sticky;
  top: 0;
  z-index: 1;
}
.vc-th-cell {
  display: flex;
  align-items: center;
  gap: 4px;
}
.vc-th-text { flex-shrink: 0; }
.vc-filter-btn {
  opacity: .6;
  transition: opacity .15s;
}
.vc-filter-btn:hover { opacity: 1; }
.vc-tbl td {
  padding: 9px 12px;
  font-size: 12.5px;
  border-bottom: 1px solid rgba(var(--v-border-color), .04);
  vertical-align: middle;
}
.vc-tbl-row {
  transition: background .12s;
}
.vc-tbl-row:hover {
  background: rgba(var(--v-theme-info), .03);
}
.vc-tbl-foot td {
  padding: 12px;
  border-top: 1px solid rgba(var(--v-border-color), .08);
  border-bottom: none;
}
.vc-unit {
  font-size: 10px;
  opacity: .4;
  margin-left: 2px;
}

/* ── Filter dropdown ── */
.vc-filter-active {
  background: rgba(var(--v-theme-info), .06) !important;
}
.vc-filter-item {
  padding: 4px 10px !important;
  min-height: 34px !important;
  margin-bottom: 1px;
  cursor: pointer;
  transition: background .12s;
}
.vc-filter-item:hover {
  background: rgba(var(--v-border-color), .04);
}

/* ── Timeline ── */
.vc-timeline {
  padding-top: 0;
}
.vc-timeline :deep(.v-timeline-item__body) {
  padding-top: 0;
  padding-bottom: 16px;
}
.vc-tl-card {
  overflow: hidden;
  transition: box-shadow .2s;
}
.vc-tl-card:hover {
  box-shadow: 0 6px 20px rgba(0,0,0,.06) !important;
}
.vc-tl-head {
  padding: 12px 16px;
  border-radius: 12px 12px 0 0;
}

/* ── Column Toggle Buttons ── */
.vc-col-btn {
  text-transform: none !important;
  font-weight: 600;
  font-size: 11px !important;
  letter-spacing: .2px;
  transition: all .15s;
}

/* ── Responsive ── */
@media (max-width: 600px) {
  .vc-hero-inner { padding: 16px; }
  .vc-stat-val { font-size: 12px; }
}
</style>
