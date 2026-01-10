<template>
  <!-- TITLE + RESET BUTTON -->
  <v-card-text class="d-flex align-center justify-space-between">
    <div class="text-caption grey--text">{{$t('sprting_priority')}}</div>
  </v-card-text>

  <!-- injected dynamic CSS for pinned columns -->
  <div v-html="pinnedStyleHtml"></div>

  <!-- table-scope is the horizontal scroll container -->
  <div class="table-scope" ref="tableRoot" style="position:relative">
    <v-data-table-virtual
      v-model="items_selected"
      :headers="orderedVisibleHeaders"
      :items="sortedFilteredItems"
      item-key="id"
      show-select
      density="compact"
      fixed-header
      height="650"
      :loading="loading"
      :rowProps="rowProps"
    >
      <template v-slot:top>
        <v-row class="mx-1">
          <v-col class="text-center" cols="2">
            <v-btn
              v-if="hiddenHeaderKeys.length>0"
              small
              rounded
              color="info"
              variant="tonal"
              @click="resetHeaders"
              :disabled="loading"
              :title="$t('reset_hidden_headers')"
            >
              <v-icon class="mr-1">mdi-table-headers-eye</v-icon>
              {{ $t("reset_header") }} ({{ hiddenHeaderKeys.length }})
            </v-btn>
          </v-col>

          <v-col class="text-center" cols="2">
            <v-btn
              color="success"
              variant="tonal"
              small
              rounded
              class="ml-2"
              :disabled="items_selected.length === 0 || loading"
              @click="exportToExcel"
              :title="$t('export_selected_to_excel')"
            >
              <v-icon class="mr-1">mdi-file-excel</v-icon>
              {{ $t('export') }} ({{ items_selected.length }})
            </v-btn>
          </v-col>

          <v-col class="text-center" cols="2">
            <v-btn
              color="amber"
              variant="tonal"
              small
              rounded
              class="ml-2"
              :disabled="invoices_selected.length === 0 || loading"
              @click="submitToinvoice"
              :title="$t('export_selected_to_excel')"
            >
              <v-icon class="mr-1">mdi-file-excel</v-icon>
              {{ $t('invoice') }} ({{ invoices_selected.length }})
            </v-btn>
          </v-col>

          <v-col class="text-center" cols="2">
            <DatePicker
              :is-dark="main.is_dark"
              :max-date="new Date()"
              v-model="invoice_date"
              :locale="main.lang.short"
              mode="date"
            >
              <template #default="{ inputEvents }">
                <v-text-field
                  :label="$t('invoice_date')"
                  density="compact"
                  variant="outlined"
                  rounded
                  readonly
                  color="info"
                  class="text-info"
                  type="text"
                  :model-value="main.formateDate(invoice_date)"
                  v-on="inputEvents"
                  append-inner-icon="mdi-calendar"
                />
              </template>
            </DatePicker>
          </v-col>

          <v-col class="text-center" cols="2">
            <v-autocomplete
              v-model="type_invoice"
              :items="type_invoice_items"
              chips
              item-title="name"
              item-value="name"
              density="compact"
              hide-details
              :placeholder="$t('type_invoice')"
              variant="outlined"
              rounded
              class="text-info header-filter"
              prepend-inner-icon="mdi-printer-pos-star"
            >
              <template v-slot:chip="{ props, item }">
                <v-chip
                  v-bind="props"
                  class="text-white"
                  :prepend-icon="item.raw.icon"
                  :base-color="item.raw.color"
                  :bg-color="item.raw.color"
                  :color="item.raw.color"
                  variant="tonal"
                >
                  {{ $t(item.title) }}
                </v-chip>
              </template>
              <template v-slot:item="{ props, item }">
                <v-list-item
                  v-bind="props"
                  class="text-info"
                  :title="$t(item.title)"
                  :append-icon="item.raw.icon"
                  :base-color="item.raw.color"
                  :bg-color="item.raw.color"
                  :color="item.raw.color"
                >
                  <template v-slot:prepend="{ isSelected, select }">
                    <v-list-item-action start>
                      <v-checkbox-btn :model-value="isSelected" @update:model-value="select"></v-checkbox-btn>
                    </v-list-item-action>
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>

          <v-col>
            <v-autocomplete
              v-model="invoice_filters"
              :items="invoice_filters_items"
              item-title="name"
              item-value="value"
              multiple
              chips
              closable-chips
              clearable
              density="compact"
              hide-details
              :placeholder="$t('filter_invoices')"
              variant="outlined"
              rounded
              class="text-info header-filter"
              :prepend-inner-icon="invoice_filters.length>0? 'mdi-filter' :'mdi-filter-outline'"
            >
              <template v-slot:chip="{ props, item }">
                <v-chip
                  v-bind="props"
                  class="text-white"
                  :prepend-icon="item.raw.icon"
                  :base-color="item.raw.color"
                  :bg-color="item.raw.color"
                  :color="item.raw.color"
                  closable
                  variant="tonal"
                >
                  {{ item.title }}
                </v-chip>
              </template>
              <template v-slot:item="{ props, item }">
                <v-list-item
                  v-bind="props"
                  @click="props.onClick"
                  class="text-info"
                  :title="item.title"
                  :append-icon="item.raw.icon"
                  :base-color="item.raw.color"
                  :bg-color="item.raw.color"
                  :color="item.raw.color"
                >
                  <template v-slot:prepend="{ isSelected, select }">
                    <v-list-item-action start>
                      <v-checkbox-btn :model-value="isSelected" @update:model-value="select"></v-checkbox-btn>
                    </v-list-item-action>
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>
        </v-row>

        <v-row class="mx-1">
          <v-col cols="6" class="border border-dotted border-md rounded-xl mb-3">
            <v-row>
              <v-col cols="12" class="text-center ">
                <v-btn @click="advanced_filter_btn = !advanced_filter_btn" :prepend-icon="advanced_filter_btn ? 'mdi-chevron-up' : 'mdi-chevron-down'" block rounded color="info" density="compact">
                  {{ $t('advanced_filters') }}
                </v-btn>
              </v-col>
            </v-row>

            <v-expand-transition>
              <v-row v-show="advanced_filter_btn">
                <!-- Advanced filters columns -->
                <v-col cols="6" v-for="col_key in advanced_filters_keys" :key="col_key">
                  <v-autocomplete
                    v-if="advanced_filters[col_key] !== undefined"
                    v-model="advanced_filters[col_key]"
                    :items="advancedFilterOptions(col_key)"
                    item-title="title"
                    item-value="value"
                    multiple
                    chips
                    closable-chips
                    clearable
                    density="compact"
                    hide-details
                    :placeholder="$t(col_key)"
                    variant="outlined"
                    rounded
                    class="text-info header-filter"
                    :prepend-inner-icon="advanced_filters[col_key].length>0? 'mdi-filter' :'mdi-filter-outline'"
                  >
                    <template v-slot:chip="{ props, item }">
                      <v-chip
                        v-bind="props"
                        class="text-white"
                        color="info accent-4"
                        closable
                        variant="tonal"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                    <template v-slot:item="{ props, item }">
                      <v-list-item
                        v-bind="props"
                        @click="props.onClick"
                        class="text-info"
                        :title="item.title"
                      >
                        <template v-slot:prepend="{ isSelected, select }">
                          <v-list-item-action start>
                            <v-checkbox-btn :model-value="isSelected" @update:model-value="select"></v-checkbox-btn>
                          </v-list-item-action>
                        </template>
                      </v-list-item>
                    </template>
                  </v-autocomplete>
                </v-col>
              </v-row>
            </v-expand-transition>
          </v-col>

          <div cols="1" class="mx-1"></div>

          <v-expand-transition>
            <v-col cols="5.5" class="border border-dotted border-md rounded-xl mb-3" v-show="type_invoice == 'export'">
              <v-btn>testament</v-btn>
            </v-col>
          </v-expand-transition>
        </v-row>
      </template>

      <!-- expand button column -->
      <template v-slot:[`item.data-table-expand`]="{ internalItem, isExpanded, toggleExpand }">
        <v-btn
          class="text-center"
          :color="isExpanded(internalItem)?'orange':'info'"
          size="x-small"
          variant="tonal"
          :icon="isExpanded(internalItem) ? 'mdi-chevron-up' : 'mdi-chevron-down'"
          slim
          @click="toggleExpand(internalItem)"
        />
      </template>

      <template #loader>
        <v-progress-linear color="info" indeterminate></v-progress-linear>
      </template>

      <!-- ROW SELECT -->
      <template v-slot:[`item.data-table-select`]="{ internalItem, isSelected, toggleSelect }">
        <v-checkbox-btn
          v-if="internalItem.raw.allow_invoice"
          :model-value="isSelected(internalItem)"
          color="info"
          @update:model-value="toggleSelect(internalItem)"
        />
      </template>

      <template v-slot:no-data>
        <div class="text-orange">
          <v-icon icon="mdi-database-alert-outline" size="large" class="mx-2" />
          <span>{{ $t("no_data_available") }}</span>
        </div>
      </template>

      <template v-slot:expanded-row="{ item }">
        <tr >
          <td :colspan="8" class="py-2 row_color">
            <v-sheet class="table_size" rounded="lg" border>
              <NestedDataTable
                :detail_headers="detail_headers"
                :item="item"
              />
            </v-sheet>
          </td>
        </tr>
      </template>

      <!-- FILTER HEADER with native drag & drop -->
      <template #headers>
        <tr>
          <th class="pa-1 header-th select-th">
            <v-checkbox
              :model-value="items_selected.length === filteredForSelection.length && filteredForSelection.length > 0"
              :indeterminate="items_selected.length > 0 && items_selected.length !== filteredForSelection.length"
              @update:model-value="selectAll"
              color="info"
              class="pt-3 ma-0"
              hide-details
              :disabled="loading"
            />
          </th>

          <th
            v-for="(col, idx) in headerOrder.filter(h => h.key !== 'data-table-select')"
            :key="idx"
            class="pa-1 header-th"
            @dragover.prevent="onDragOver($event, col.key)"
            @drop.prevent="onDrop($event, col.key)"
            :class="{ 'drag-over': dragOverKey === col.key }"
          >
            <div class="header-top d-flex align-center justify-space-between flex-nowrap mb-1">
              <div class="header-title d-flex align-center flex-nowrap">
                <span
                  class="drag-handle me-2"
                  role="button"
                  tabindex="0"
                  :title="$t('drag_to_reorder')"
                  draggable="true"
                  @dragstart="onDragStart($event, col.key)"
                  @dragend="onDragEnd($event)"
                >
                  <v-icon small class="me-1">mdi-drag</v-icon>
                </span>

                <v-btn
                  v-if="col.key != 'data-table-expand'"
                  variant="text"
                  icon
                  size="x-small"
                  :disabled="loading"
                  @click.stop="toggleSort(col.key)"
                  :title="sortTitle(col.key)"
                >
                  <v-badge
                    v-if="sortPriority(col.key) !== null"
                    :content="sortPriority(col.key) + 1"
                    overlap
                    color="info"
                  >
                    <v-icon small>{{ sortIcon(col.key) }}</v-icon>
                  </v-badge>

                  <template v-else>
                    <v-icon small>{{ sortIcon(col.key) }}</v-icon>
                  </template>
                </v-btn>

                <span class="header-title-text ml-1">{{ col.title }}</span>
              </div>

              <div class="header-actions d-flex align-center flex-shrink-0">
                <v-btn
                  variant="text"
                  icon
                  size="x-small"
                  class="ml-1"
                  :disabled="loading"
                  @click.stop="togglePin(col.key)"
                  :title="pinnedHeaderKeys.includes(col.key) ? $t('unpin_column') : $t('pin_column')"
                >
                  <v-icon small>
                    {{ pinnedHeaderKeys.includes(col.key) ? 'mdi-pin' : 'mdi-pin-off' }}
                  </v-icon>
                </v-btn>

                <v-btn v-if="col.key != 'data-table-expand'" variant="text" icon size="x-small" class="ml-1" :disabled="loading" @click.stop="removeHeader(col.key)" :title="$t('remove_column')">
                  <v-icon small color="error">mdi-close</v-icon>
                </v-btn>
              </div>
            </div>

            <div class="header-bottom">
              <div class="filter-wrap text-center" :class="{ 'filter-has': filters[col.key] !== undefined }">
                <v-autocomplete
                  v-if="filters[col.key] !== undefined"
                  v-model="filters[col.key]"
                  :items="filterOptions(col.key)"
                  item-title="title"
                  item-value="value"
                  multiple
                  chips
                  closable-chips
                  clearable
                  density="compact"
                  hide-details
                  :placeholder="`${$t('filter_by')} ${col.title}`"
                  variant="outlined"
                  rounded
                  class="text-info header-filter"
                  :prepend-inner-icon="filters[col.key].length>0? 'mdi-filter' :'mdi-filter-outline'"
                >
                  <template v-slot:chip="{ props, item }">
                    <v-chip
                      v-bind="props"
                      class="text-white"
                      color="info accent-4"
                      closable
                      variant="tonal"
                    >
                      {{ item.title }}
                    </v-chip>
                  </template>
                  <template v-slot:item="{ props, item }">
                    <v-list-item
                      v-bind="props"
                      @click="props.onClick"
                      class="text-info"
                      :title="item.title"
                    >
                      <template v-slot:prepend="{ isSelected, select }">
                        <v-list-item-action start>
                          <v-checkbox-btn :model-value="isSelected" @update:model-value="select"></v-checkbox-btn>
                        </v-list-item-action>
                      </template>
                    </v-list-item>
                  </template>
                </v-autocomplete>

                <v-chip v-else-if="allowed_sigma(col.key)" color="info" class="mt-1 header-total">
                  <v-icon>mdi-sigma</v-icon> =
                  {{ get_total(col.key) }}
                </v-chip>
                <div v-if="invoice_prop == col.key" >
                  <v-chip color="success" class="mt-1 header-total">
                    <v-icon>mdi-sigma</v-icon> =
                    {{ get_total_invoice() }}
                    <v-icon class="ml-1">mdi-printer-check</v-icon>
                  </v-chip>
                  <v-divider class="mt-1"></v-divider>
                  <v-chip color="warning" class="mt-1 header-total">
                    <v-icon>mdi-sigma</v-icon> =
                    {{ get_total_none_invoice() }}
                    <v-icon class="ml-1">mdi-printer-pos-cog</v-icon>
                  </v-chip>
                </div>
              </div>
            </div>
          </th>
        </tr>
      </template>

      <!-- PASS CUSTOM SLOTS -->
      <template v-for="(_, name) in $slots" :key="name" v-slot:[name]="slotProps">
        <slot :name="name" v-bind="slotProps" />
      </template>
    </v-data-table-virtual>

    <!-- LOADER OVERLAY -->
    <v-overlay
      :model-value="loading"
      class="align-center justify-center"
      contained
      scrim="info"
    >
      <div class="text-center">
        <v-progress-circular indeterminate size="48" width="4" color="info" />
        <div class="mt-3 text-info font-weight-medium">{{ $t('loading_data') }}</div>
      </div>
    </v-overlay>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useFishType } from '@/stores/FishType';
import { useGuanoType } from '@/stores/GuanoType';
import { usePackageType } from '@/stores/PackageType';
import * as XLSX from 'xlsx';
import { nextTick } from 'vue';
import { Parser } from 'expr-eval';
import _ from 'lodash';

const NULL_VALUE = '__NULL__';

import NestedDataTable from "./NestedDataTable.vue";
export default {
  name: 'DataTable',
  props: {
    relations: Object,
    advanced_relations: Object,
    date: Object,
    filters: Object,
    advanced_filters: Object,
    advanced_filters_keys: Array,
    items: Array,
    sigma: Array,
    invoice_prop: String,
    fetchData: Function,
    headers: { type: Array, required: true },
    detail_headers: { type: Array, required: true }
  },
  components: {
    NestedDataTable
  },
  emits: ['sync_items'],
  data () {
    return {
      items_selected: [],
      loading: false,

      hiddenHeaderKeys: [],
      defaultHeaderKeys: [],
      pinnedHeaderKeys: ['data-table-select','data-table-expand'],
      sortOrders: [],
      pinnedCss: '',

      headerOrder: [],

      draggingKey: null,
      dragOverKey: null,

      //invoice
      invoice_date: new Date(),
      invoice_expression: "1 == 1",
      invoice_filters: [],
      type_invoice:"local",
      advanced_filter_btn:false
    }
  },

  mounted() {
    this.defaultHeaderKeys = this.headers.map(h => h.key)
    this.hiddenHeaderKeys = this.headers.filter(h => h.visible === false).map(h => h.key)
    this.syncHeaderOrder()
    this._fetchData(this.date.start , this.date.end)
    const root = this.$refs.tableRoot
    if (root) {
      root.addEventListener('scroll', this.debouncedComputePinnedCss)
      window.addEventListener('resize', this.debouncedComputePinnedCss)
    }
  },

  beforeUnmount () {
    const root = this.$refs.tableRoot
    if (root) root.removeEventListener('scroll', this.debouncedComputePinnedCss)
    window.removeEventListener('resize', this.debouncedComputePinnedCss)
  },

  computed: {
    main: () => useMain(),
    parser: () => new Parser(),
    fish_types: () => useFishType().getList,
    guano_types: () => useGuanoType().getList,
    package_types: () => usePackageType().getList,

    invoices_selected: function () {
      return this.items.filter(i => this.items_selected.includes(i.id)).filter(e => e.allow_invoice).map(i => i.id);
    },
    invoice_filters_items: function () {
      return [
        {
          name: this.$t('non_posting_invoice'),
          value: "(allow_invoice == 0)",
          color:"info",
          icon:"mdi-printer-pos-cancel"
        },
        {
          name: this.$t('final_invoice'),
          value: "(allow_invoice == 1 and invoice_id == 1)",
          color:"success",
          icon:"mdi-printer-check"
        },
        {
          name: this.$t('uninvoiced'),
          value: "(allow_invoice == 1 and invoice_id == 0)",
          color:"warning",
          icon:"mdi-printer-pos-cog"
        },
      ]
    },
    type_invoice_items: function () {
      return [
        {
          name:"local",
          color:"info",
          icon:"mdi-truck"
        },
        {
          name:"export",
          color:"warning",
          icon:"mdi-truck-delivery"
        },
      ]
    },

    visibleHeaders () {
      return this.headers.filter(h => !this.hiddenHeaderKeys.includes(h.key))
    },

    orderedVisibleHeaders () {
      return this.headerOrder.filter(h => !this.hiddenHeaderKeys.includes(h.key))
    },

    /* BASE FILTERS */
    baseFilteredItems () {
      const base = (this.items || []).filter(row =>
        Object.entries(this.filters || {}).every(([key, selected]) => {
          if (!selected || !selected.length) return true
          const val = row[key]

          if (val == null) return selected.includes(NULL_VALUE)

          if (Array.isArray(val)) {
            return val.some(v => selected.includes(v)) ||
                   (val.length === 0 && selected.includes(NULL_VALUE))
          }

          return selected.includes(val)
        })
      )

      return base.filter(e => {
        const expr = (this.invoice_filters || []).join(' or ')
        if (!expr.length) return true
        return this.parser.evaluate(expr, {
          invoice_id: e.invoice_id == null ? 0 : e.invoice_id,
          allow_invoice: e.allow_invoice
        })
      })
    },

    /* PROCESSED ITEMS: advanced filters applied and nested arrays pruned */
    processedItems () {
      const advanced = this.advanced_filters || {}
      const advKeys = Object.keys(advanced).filter(k => advanced[k] && advanced[k].length)
      if (!advKeys.length) return this.baseFilteredItems.map(r => ({ ...r }))

      const pathsToScan = []
      advKeys.forEach(k => {
        const rel = this.advanced_relations?.[k]
        if (!rel) return
        if (rel.paths && Array.isArray(rel.paths)) rel.paths.forEach(p => { if (!pathsToScan.includes(p)) pathsToScan.push(p) })
        if (rel.path && !pathsToScan.includes(rel.path)) pathsToScan.push(rel.path)
      })

      if (!pathsToScan.length) pathsToScan.push('warehouse_stockable_tracking_number_operations','non_warehouse_stockable_operations')

      const result = []

      for (const row of this.baseFilteredItems) {
        const cloned = { ...row }
        let hadAnyMatch = false
        for (const p of pathsToScan) {
          const arr = Array.isArray(row[p]) ? row[p] : []
          const matched = arr.filter(nested => this._nestedMatchesAdvancedFilters(nested, advanced))
          cloned[p] = matched
          if (matched.length) hadAnyMatch = true
        }
        if (hadAnyMatch) result.push(cloned)
      }

      return result
    },

    filteredForSelection () {
      return this.processedItems
    },

    sortedFilteredItems () {
      const arr = this.processedItems.slice()
      if (!this.sortOrders.length) return arr

      const orders = this.sortOrders.slice()

      const getComparable = (row, key) => {
        const val = row[key]
        if (this.relations && this.relations[key]) {
          const { source, label } = this.relations[key]
          const list = Array.isArray(source) ? source : (this[source] || [])
          if (Array.isArray(val)) {
            return val.map(id => list.find(i => i.id === id)?.[label] ?? '').join('|')
          }
          return list.find(i => i.id === val)?.[label] ?? (val == null ? '' : String(val))
        }

        if (Array.isArray(val)) return val.join('|')
        if (val == null) return ''
        return String(val)
      }

      arr.sort((a, b) => {
        for (const { key, dir } of orders) {
          const va = getComparable(a, key)
          const vb = getComparable(b, key)
          const na = parseFloat(va)
          const nb = parseFloat(vb)
          let cmp = 0
          if (!Number.isNaN(na) && !Number.isNaN(nb)) {
            cmp = na - nb
          } else {
            cmp = String(va).localeCompare(String(vb))
          }
          if (cmp !== 0) return dir === 'asc' ? cmp : -cmp
        }
        return 0
      })

      return arr
    },

    pinnedStyleHtml () {
      if (!this.pinnedCss) return ''
      return `<style>${this.pinnedCss}</style>`
    }
  },

  watch: {
    processedItems (value) {
      this.$emit('sync_items', value)
    },
    hiddenHeaderKeys () {
      this.debouncedComputePinnedCss()
      this.syncHeaderOrder()
    },
    pinnedHeaderKeys () {
      this.debouncedComputePinnedCss()
    },
    visibleHeaders () {
      this.$nextTick(() => {
        this.syncHeaderOrder()
        this.computePinnedCss()
      })
    },
    headers: {
      deep: true,
      handler () {
        this.$nextTick(() => this.syncHeaderOrder())
      }
    },
    "main.is_dark": function () {
      this.computePinnedCss();
    },
    date: {
      deep: true,
      handler (d) {
        if (d.start && d.end) this._fetchData()
      }
    }
  },

  methods: {
    async _fetchData () {
      this.loading = true
      this.items_selected = []

      this.fetchData(this.date.start, this.date.end)
      .catch(err => {
        console.error(`Error fetching ${this.api_url}`, err)
      }).finally(() => {
        this.loading = false
        this.$nextTick(() => this.computePinnedCss())
      });
    },

    syncHeaderOrder () {
      const currentKeys = this.headerOrder.map(h => h.key)
      const headerMap = {}
      this.headers.forEach(h => { headerMap[h.key] = h })
      const newOrder = []
      currentKeys.forEach(k => {
        if (!this.hiddenHeaderKeys.includes(k) && headerMap[k]) newOrder.push(headerMap[k])
      })
      this.visibleHeaders.forEach(h => {
        if (!newOrder.find(x => x.key === h.key)) newOrder.push(h)
      })
      const selectIdx = newOrder.findIndex(h => h.key === 'data-table-select')
      if (selectIdx > 0) {
        const sel = newOrder.splice(selectIdx, 1)[0]
        newOrder.unshift(sel)
      } else if (selectIdx === -1) {
        const selectProp = this.headers.find(h => h.key === 'data-table-select')
        if (selectProp) newOrder.unshift(selectProp)
      }
      this.headerOrder = newOrder
    },

    removeHeader (key) {
      if (!this.hiddenHeaderKeys.includes(key)) {
        this.hiddenHeaderKeys.push(key)
      }
    },

    resetHeaders () {
      this.hiddenHeaderKeys = []
      this.$nextTick(() => {
        this.syncHeaderOrder()
        this.computePinnedCss()
      })
    },

    selectAll () {
      this.items_selected =
        this.items_selected.length === this.filteredForSelection.length
          ? []
          : this.filteredForSelection.map(i => i.id)
    },

    get_total(key) {
      const sigma_allowed = this.sigma || [];
      let sigma = sigma_allowed.find(s => s.name == key);
      const paths = sigma.paths || [];

      const total = _.sum(
        _.map(this.sortedFilteredItems || [], item => { 
          const total = _.sum(
            paths.map(path =>
              _.sumBy(_.get(item, path, []), o => +o[key] || 0)
            )
          );

          // overwrite parent value
          item[key] = total;
          return total;
        })
      );

      return this.main.formatDisplayNumber(total);

    },

    get_total_invoice () {
      return this.main.formatDisplayNumber(
        this.processedItems.filter(e=>e.allow_invoice && e.invoice_id != null).reduce((a, b) => a + (+b.total_price || 0), 0)
      )
    },

    get_total_none_invoice () {
      return this.main.formatDisplayNumber(
        this.processedItems.filter(e=>e.allow_invoice && e.invoice_id == null).reduce((a, b) => a + (+b.total_price || 0), 0)
      )
    },

    itemsFilteredExcept (exceptKey) {
      return this.items.filter(row => {
        const colOk = Object.entries(this.filters || {}).every(([key, selected]) => {
          if (key === exceptKey || !selected || !selected.length) return true
          const val = row[key]
          if (val == null) return selected.includes(NULL_VALUE)
          if (Array.isArray(val)) {
            return val.some(v => selected.includes(v)) ||
                   (val.length === 0 && selected.includes(NULL_VALUE))
          }
          return selected.includes(val)
        })
        if (!colOk) return false
        return true
      })
    },

    filterOptions (key) {
      const values = new Set()
      let hasNull = false
      this.itemsFilteredExcept(key).forEach(row => {
        const v = row[key]
        if (v == null || (Array.isArray(v) && !v.length)) hasNull = true
        else if (Array.isArray(v)) v.forEach(x => values.add(x))
        else values.add(v)
      })

      const options = []
      if (hasNull) options.push({ title: this.$t("n/a"), value: NULL_VALUE })

      if (this.relations?.[key]) {
        const { source, label } = this.relations[key]
        const list = Array.isArray(source) ? source : (this[source] || [])
        options.push(
          ...list
            .filter(i => values.has(i.id))
            .map(i => ({ title: i[label], value: i.id }))
        )
      } else {
        options.push(...[...values].map(v => ({ title: String(v), value: v })))
      }

      return options.sort((a, b) => String(a.title).localeCompare(String(b.title)))
    },

    normalizeStockableType (type) {
      if (!type && type !== 0) return type
      if (typeof type !== 'string') return type
      const parts = type.split('\\')
      return parts[parts.length - 1]
    },

    getStockableFromNested (nestedItem) {
      if (!nestedItem) return null

      const wstn = nestedItem.warehouse_stockable_tracking_number
      if (wstn && wstn.warehouse_stockable) {
        const ws = wstn.warehouse_stockable
        return {
          stockable_type: this.normalizeStockableType(ws.stockable_type ?? ws.type ?? nestedItem.stockable_type),
          stockable_id: ws.stockable_id ?? ws.id ?? nestedItem.stockable_id
        }
      }

      if (nestedItem.warehouse_stockable) {
        const ws = nestedItem.warehouse_stockable
        return {
          stockable_type: this.normalizeStockableType(ws.stockable_type ?? ws.type ?? nestedItem.stockable_type),
          stockable_id: ws.stockable_id ?? ws.id ?? nestedItem.stockable_id
        }
      }

      if (nestedItem.stockable) {
        const s = nestedItem.stockable
        return {
          stockable_type: this.normalizeStockableType(s.stockable_type ?? s.type ?? nestedItem.stockable_type),
          stockable_id: s.stockable_id ?? s.id ?? nestedItem.stockable_id
        }
      }

      if (nestedItem.stockable_type || nestedItem.stockable_id) {
        return {
          stockable_type: this.normalizeStockableType(nestedItem.stockable_type),
          stockable_id: nestedItem.stockable_id
        }
      }

      return null
    },

    // Resolve fish_family_id from a stockable (only for FishType)
    resolveFishFamilyIdFromStockable (stockable) {
      if (!stockable) return null
      const stype = this.normalizeStockableType(stockable.stockable_type)
      const sid = stockable.stockable_id
      if (!stype || !sid) return null
      if (stype !== 'FishType') return null
      // lookup fish_types store
      const ft = (this.fish_types || []).find(x => String(x.id) === String(sid))
      if (!ft) return null
      return ft.fish_family_id ?? ft.family_id ?? null
    },

    _nestedMatchesAdvancedFilters (nested, advancedFilters) {
      const activeKeys = Object.keys(advancedFilters || {}).filter(k => advancedFilters[k] && advancedFilters[k].length)
      if (!activeKeys.length) return true

      const stockable = this.getStockableFromNested(nested)
      const stype = stockable ? this.normalizeStockableType(stockable.stockable_type) : this.normalizeStockableType(nested.stockable_type)
      const sId = stockable ? stockable.stockable_id : nested.stockable_id
      // Resolve fish family using fish_types lookup if stockable is FishType
      const fishFamilyFromStockable = this.resolveFishFamilyIdFromStockable(stockable) || nested.fish_family_id

      return activeKeys.every(key => {
        const selected = advancedFilters[key]
        if (!selected || !selected.length) return true

        if (key === 'warehouse_id') {
          const wid = nested.warehouse_id ?? nested.warehouse_stockable_tracking_number?.warehouse_id ?? nested.warehouse_id
          return selected.includes(wid)
        }

        if (key === 'stockable_type') {
          if (!stype) return false
          const normalizedSelected = selected.map(s => this.normalizeStockableType(s))
          return normalizedSelected.includes(stype)
        }

        if (key === 'stockable_id') {
          return selected.some(sel => {
            if (typeof sel === 'string' && sel.includes('__')) {
              const [selType, selId] = sel.split('__')
              const normSelType = this.normalizeStockableType(selType)
              return (String(normSelType) === String(stype)) && (String(selId) === String(sId))
            }
            return String(sel) === String(sId)
          })
        }

        if (key === 'fish_family_id') {
          if (!stype) return false
          if (this.normalizeStockableType(stype) !== 'FishType') return false
          if (fishFamilyFromStockable == null) return false
          return selected.some(ff => String(ff) === String(fishFamilyFromStockable))
        }

        return true
      })
    },

    advancedFilterOptions (key) {
      const rel = this.advanced_relations?.[key]
      const valuesMap = new Map()

      const sourceRows = this.itemsFilteredExcept(key)
      const paths = (rel && (rel.paths || (rel.path ? [rel.path] : [])))
        || ['warehouse_stockable_tracking_number_operations','non_warehouse_stockable_operations']

      sourceRows.forEach(row => {
        paths.forEach(p => {
          const arr = Array.isArray(row[p]) ? row[p] : []
          arr.forEach(nested => {
            const stockable = this.getStockableFromNested(nested)

            if (key === 'warehouse_id') {
              const wid = nested.warehouse_id ?? nested.warehouse_stockable_tracking_number?.warehouse_id
              if (wid != null && !valuesMap.has(wid)) {
                const label = (() => {
                  if (rel?.source && Array.isArray(rel.source)) {
                    const found = rel.source.find(x => x.id === wid)
                    if (found) return found[rel.label] ?? found.name ?? String(wid)
                  }
                  return String(wid)
                })()
                valuesMap.set(wid, label)
              }
            }

            if (key === 'stockable_type') {
              const st = this.normalizeStockableType(stockable?.stockable_type ?? nested.stockable_type)
              if (st != null && !valuesMap.has(st)) {
                let name = this.$t(st);
                valuesMap.set(name, String(name))
              }
            }

            if (key === 'stockable_id') {
              const stype = this.normalizeStockableType(stockable?.stockable_type ?? nested.stockable_type)
              const sid = stockable?.stockable_id ?? nested.stockable_id
              if (stype != null && sid != null) {
                const combined = `${stype}__${sid}`;
                if (!valuesMap.has(combined)) {
                  let friendly = `${stype} ${sid}`;
                  let ft = {};
                  switch (stype) {
                    case "FishType":
                      ft = this.fish_types.find(f => String(f.id) === String(sid))
                      if (ft) friendly = ft[`name_${this.main.lang.code}`]                      
                    break;
                    case "GuanoType":
                      ft = this.guano_types.find(f => String(f.id) === String(sid))
                      if (ft) friendly = ft[`name_${this.main.lang.code}`]                      
                    break;
                    case "PackageType":
                      ft = this.package_types.find(f => String(f.id) === String(sid))
                      if (ft) friendly = ft[`name_${this.main.lang.code}`]                      
                    break;
                  }
                  valuesMap.set(combined, `${friendly}`)
                }
              }
            }

            if (key === 'fish_family_id') {
              // get fish family id by resolving fish type record
              const resolvedFishFamily = (() => {
                const stock = stockable || null
                const stype = this.normalizeStockableType(stock ? stock.stockable_type : nested.stockable_type)
                if (stype !== 'FishType') return null
                // sid:
                const sid = stock ? stock.stockable_id : nested.stockable_id
                if (!sid) return null
                const ft = (this.fish_types || []).find(x => String(x.id) === String(sid))
                if (!ft) return null
                return ft.fish_family_id ?? ft.family_id ?? null
              })()

              if (resolvedFishFamily != null && !valuesMap.has(resolvedFishFamily)) {
                if (rel?.source && Array.isArray(rel.source)) {
                  const found = rel.source.find(x => x.id === resolvedFishFamily)
                  const title = found ? (rel.label ? found[rel.label] : found.name) : String(resolvedFishFamily)
                  valuesMap.set(resolvedFishFamily, title)
                } else {
                  valuesMap.set(resolvedFishFamily, String(resolvedFishFamily))
                }
              }
            }
          })
        })
      })

      const options = []
      for (const [value, title] of valuesMap.entries()) {
        options.push({ title, value })
      }
      options.sort((a, b) => String(a.title).localeCompare(String(b.title)))
      return options
    },

    /* Sorting helpers, pinning, drag/drop, excel export, etc. (unchanged) */
    sortDir (key) {
      const o = this.sortOrders.find(s => s.key === key)
      return o ? o.dir : null
    },
    sortIndex (key) { const i = this.sortOrders.findIndex(s => s.key === key); return i === -1 ? null : i },
    sortIcon (key) {
      const dir = this.sortDir(key)
      if (dir === 'asc') return 'mdi-sort-ascending'
      if (dir === 'desc') return 'mdi-sort-descending'
      return 'mdi-swap-vertical'
    },
    sortTitle (key) {
      const dir = this.sortDir(key)
      if (!dir) return this.$t('no_sort_click_to_add_asc')
      return dir === this.$t('asc') ? this.$t('sorted_click_to_change_to_desc') : this.$t('sorted_desc_click_to_remove')
    },
    toggleSort (key) {
      const idx = this.sortOrders.findIndex(s => s.key === key)
      if (idx === -1) this.sortOrders.unshift({ key, dir: 'asc' })
      else if (this.sortOrders[idx].dir === 'asc') this.sortOrders.splice(idx, 1, { key, dir: 'desc' })
      else this.sortOrders.splice(idx, 1)
    },
    sortPriority (key) { return this.sortIndex(key) },

    togglePin (key) {
      const i = this.pinnedHeaderKeys.indexOf(key)
      if (i === -1) this.pinnedHeaderKeys.push(key)
      else this.pinnedHeaderKeys.splice(i, 1)
      this.$nextTick(() => this.computePinnedCss())
    },

    async computePinnedCss () {
      if (!this.pinnedHeaderKeys.length) { this.pinnedCss = ''; return }
      await nextTick()
      const root = this.$refs.tableRoot
      if (!root) { this.pinnedCss = ''; return }
      const ths = Array.from(root.querySelectorAll('thead tr th'))
      if (!ths.length) { this.pinnedCss = ''; return }
      const widths = ths.map(th => {
        const rect = th.getBoundingClientRect()
        return Math.max(1, Math.round(rect.width || th.offsetWidth || 0))
      })
      const isDark = !!(this.main && this.main.is_dark)
      const headerBg = isDark ? 'rgba(255,255,255,0.06)' : '#ffffff'
      const bodyBg = isDark ? 'rgba(33,33,33,1)' : '#f9f9fa'
      const selectBg = isDark ? 'rgba(255,255,255,0.04)' : '#f1f1f1'
      const headerShadow = isDark ? '2px 0 12px rgba(0,0,0,0.6)' : '2px 0 8px rgba(0,0,0,0.06)'
      const bodyShadow = isDark ? '1px 0 8px rgba(0,0,0,0.45)' : '1px 0 6px rgba(0,0,0,0.03)'

      const visibleKeys = this.orderedVisibleHeaders.map(h => h.key)

      const pinnedDomEntries = []
      for (const pinnedKey of this.pinnedHeaderKeys) {
        if (pinnedKey === 'data-table-select') {
          pinnedDomEntries.push({ key: pinnedKey, domIndex: 1, width: widths[0] || 56 })
          continue
        }
        const visibleIndex = visibleKeys.indexOf(pinnedKey)
        if (visibleIndex === -1) continue
        const domIndex = visibleIndex + 2
        pinnedDomEntries.push({ key: pinnedKey, domIndex, width: widths[domIndex - 1] || 56 })
      }

      if (!pinnedDomEntries.length) { this.pinnedCss = ''; return }

      let css = ''
      let cumLeft = 0
      let cumRight = 0
      for ( const pinnedDomEntrie of _.sortBy(pinnedDomEntries,'domIndex')) {
        const entry = pinnedDomEntrie
        const nth = entry.domIndex
        const left = Math.round(cumLeft)
        const right = Math.round(cumRight)
        css += `
          .table-scope thead tr th:nth-child(${nth}) {
            position: sticky;
            left: ${left}px;
            right: ${right}px;
            z-index: 1;
            background: ${headerBg};
            box-shadow: ${headerShadow};
            border-right: 1px solid rgba(0,0,0,0.06);
          }
          `
        css += `
          .table-scope tbody tr td:nth-child(${nth}) {
            position: sticky;
            left: ${left}px;
            right: ${right}px;
            z-index: 1;
            background: ${bodyBg};
            box-shadow: ${bodyShadow};
            border-right: 1px solid rgba(0,0,0,0.03);
          }
        `
        cumLeft += entry.width;
        cumRight -= 0;
      }

      css += `
.table-scope thead tr th.select-th {
  z-index: 6;
  background: ${selectBg};
}
.table-scope thead tr th, .table-scope tbody tr td {
  transition: left 0.12s;
}
`
      this.pinnedCss = css
    },

    debouncedComputePinnedCss: (function () {
      let timeout = null
      return function () {
        if (timeout) clearTimeout(timeout)
        timeout = setTimeout(() => {
          this.computePinnedCss()
          timeout = null
        }, 120)
      }
    })(),

    onDragStart (evt, key) {
      if (key === 'data-table-select') {
        evt.preventDefault(); return
      }
      try {
        evt.dataTransfer.effectAllowed = 'move'
        evt.dataTransfer.setData('text/plain', key)
      } catch (e) {}
      this.draggingKey = key
      const el = evt.currentTarget
      if (el) el.classList.add('dragging')
    },

    onDragOver (evt, targetKey) {
      const srcKey = this.draggingKey || (evt.dataTransfer && evt.dataTransfer.getData && evt.dataTransfer.getData('text/plain'))
      if (!srcKey || srcKey === targetKey) {
        this.dragOverKey = null
        return
      }
      if (targetKey === 'data-table-select') { this.dragOverKey = null; return }
      this.dragOverKey = targetKey
    },

    onDrop (evt, targetKey) {
      evt.preventDefault()
      const srcKey = evt.dataTransfer.getData('text/plain') || this.draggingKey
      if (!srcKey || srcKey === targetKey) {
        this.dragOverKey = null
        this.draggingKey = null
        return
      }
      const srcIdx = this.headerOrder.findIndex(h => h.key === srcKey)
      const tgtIdx = this.headerOrder.findIndex(h => h.key === targetKey)
      if (srcIdx === -1 || tgtIdx === -1) {
        this.dragOverKey = null
        this.draggingKey = null
        return
      }
      const [item] = this.headerOrder.splice(srcIdx, 1)
      const adjustedTgtIdx = srcIdx < tgtIdx ? tgtIdx  : tgtIdx
      this.headerOrder.splice(adjustedTgtIdx, 0, item)

      const si = this.headerOrder.findIndex(h => h.key === 'data-table-select')
      if (si > 0) {
        const s = this.headerOrder.splice(si, 1)[0]
        this.headerOrder.unshift(s)
      }

      this.dragOverKey = null
      this.draggingKey = null
      this.$nextTick(() => this.computePinnedCss())
    },

    onDragEnd (evt) {
      this.draggingKey = null
      this.dragOverKey = null
      const el = evt.currentTarget
      if (el) el.classList.remove('dragging')
    },

    resolveDisplayValue (row, key) {
      const val = row[key]

      if (this.relations && this.relations[key]) {
        const { source, label } = this.relations[key]
        const list = Array.isArray(source) ? source : (this[source] || [])
        if (Array.isArray(val)) {
          return val.map(id => {
            const found = list.find(i => i.id === id)
            return found ? (found[label] ?? '') : ''
          }).filter(Boolean).join(', ')
        }
        const found = list.find(i => i.id === val)
        return found ? (found[label] ?? '') : (val == null ? '' : String(val))
      }

      if (Array.isArray(val)) return val.join(', ')
      if (val == null) return ''
      return val
    },

    exportToExcel () {
      if (!this.items_selected || !this.items_selected.length) {
        console.warn('No rows selected to export')
        return
      }

      const selectedRows = this.processedItems.filter(i => this.items_selected.includes(i.id))
      const cols = this.orderedVisibleHeaders.filter(
        h => h.key !== 'data-table-select' && h.key !== 'data-table-expand'
      )
      const headerTitles = cols.map(h => h.title ?? h.key)

      const dataAoA = [
        headerTitles,
        ...selectedRows.map(row => cols.map(h => {
          if (h.key === 'date') {
            const raw = row[h.key]
            if (raw == null || raw === '') return ''
            if (raw instanceof Date) return raw
            const m = moment(raw)
            if (m.isValid()) return m.toDate()
            return String(raw)
          }
          const v = this.resolveDisplayValue(row, h.key)
          if (v === '') return ''
          const num = parseFloat(v)
          if (!Number.isNaN(num) && String(v).trim() !== '') return num
          return String(v)
        }))
      ]

      const ws = XLSX.utils.aoa_to_sheet(dataAoA)
      const wb = XLSX.utils.book_new()
      XLSX.utils.book_append_sheet(wb, ws, 'Export')

      const dateColIndex = cols.findIndex(h => h.key === 'date')
      if (dateColIndex !== -1) {
        for (let r = 1; r <= selectedRows.length; r++) {
          const cellRef = XLSX.utils.encode_cell({ c: dateColIndex, r })
          const cell = ws[cellRef]
          if (!cell) continue
          if (cell.v instanceof Date) {
            cell.t = 'd'
            cell.z = this.main.date_format
          } else {
            const m = moment(cell.v)
            if (cell.v && typeof cell.v === 'string' && m.isValid()) {
              cell.v = m.toDate()
              cell.t = 'd'
              cell.z = this.main.date_format
            }
          }
        }
      }

      const filename = `export_charge_${moment().format('YYYYMMDD_HHmmss')}.xlsx`
      XLSX.writeFile(wb, filename)
    },
    submitToinvoice: function () {
      if (!this.invoices_selected || !this.invoices_selected.length) {
        console.warn('No rows selected to export')
        return
      }
    },
    allowed_sigma(key) {
      const sigma_allowed = this.sigma || [];
      if (sigma_allowed.find(s=>s.name == key)) {
        return key;
      }
      return null;
    },
    rowProps: function (item) {
      if (this.items_selected.find(e=>e == item.item.id)) {
        return {
          style:  {"background-color": "rgba(255, 130, 58, 0.516)"},
        }
      }
      return {
        style:  {"background-color": "rgba(90, 185, 253, 0.1)"},
      }
    }
  }
}
</script>

<style scoped>
.table_size{
    width: 86vw !important;
}
.row_color{
  background-color: rgba(90, 185, 253, 0.1) !important;
}
.table-scope table {
  min-width: max-content;
}
.header-th { vertical-align: top; }
.select-th {
  min-width: 56px;
  max-width: 56px;
  width: 56px;
  white-space: nowrap;
}
.header-top {
  gap: 8px;
  align-items: center;
}
.header-title-text {
  white-space: nowrap;
  text-overflow: ellipsis;
  display: inline-block;
  max-width: 220px;
}
.header-bottom .header-filter,
.header-bottom .header-total {
  white-space: nowrap;
  text-overflow: ellipsis;
  max-width: 260px;
}
.header-actions {
  flex: 0 0 auto;
}
.flex-nowrap {
  flex-wrap: nowrap !important;
}
th {
  min-width: 80px;
}
.table-scope thead tr th, .table-scope tbody tr td {
  transition: left 0.12s;
  will-change: left;
}
.table-scope thead tr th[style*="position: sticky"], .table-scope tbody tr td[style*="position: sticky"] {
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
}
.drag-handle {
  display: inline-flex;
  align-items: center;
  cursor: grab;
  user-select: none;
}
.drag-handle:active { cursor: grabbing; }
.dragging {
  opacity: 0.7;
}
.drag-over {
  outline: 3px dashed rgba(16, 110, 234, 0.22);
  outline-offset: -6px;
  transition: outline 0.08s;
}
.bg-v-d-t-v{
  background-color: rgba(49, 164, 251, 0.081);
}
</style>
