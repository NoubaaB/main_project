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
      fixed-header
      height="650"
      show-select
      density="compact"
      :loading="loading"
    >
      <template v-slot:top>
        <v-row>
          <v-col>
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

            <!-- EXPORT BUTTON -->
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
        </v-row>
      </template>

      <!-- expand button column -->
      <template v-slot:[`item.data-table-expand`]="{ internalItem, isExpanded, toggleExpand }">
        <v-btn
          :append-icon="isExpanded(internalItem) ? 'mdi-chevron-up' : 'mdi-chevron-down'"
          :text="isExpanded(internalItem) ? $t('collapse') : $t('view_details')"
          class="text-none"
          color="medium-emphasis"
          size="small"
          variant="text"
          width="105"
          border
          slim
          @click="toggleExpand(internalItem)"
        ></v-btn>
      </template>

      <template #loader>
        <v-progress-linear color="info" indeterminate></v-progress-linear>
      </template>

      <!-- ROW SELECT -->
      <template v-slot:[`item.data-table-select`]="{ internalItem, isSelected, toggleSelect }">
        <v-checkbox-btn
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

      <!-- FILTER HEADER with native drag & drop -->
      <template #headers>
        <tr>
          <!-- SELECT ALL CHECKBOX TH (kept first and not draggable) -->
          <th class="pa-1 header-th select-th">
            <v-checkbox
              :model-value="items_selected.length === filteredItems.length && filteredItems.length > 0"
              :indeterminate="items_selected.length > 0 && items_selected.length !== filteredItems.length"
              @update:model-value="selectAll"
              color="info"
              class="pt-3 ma-0"
              hide-details
              :disabled="loading"
            />
          </th>

          <!-- Render THs according to headerOrder (excluding select column) -->
          <th
            v-for="(col, idx) in headerOrder.filter(h => h.key !== 'data-table-select')"
            :key="idx"
            class="pa-1 header-th"
            @dragover.prevent="onDragOver($event, col.key)"
            @drop.prevent="onDrop($event, col.key)"
            :class="{ 'drag-over': dragOverKey === col.key }"
          >
            <!-- header-top contains a small drag handle icon which is draggable.
                 Drag is initiated from the handle to avoid interfering with buttons/inputs. -->
            <div class="header-top d-flex align-center justify-space-between flex-nowrap mb-1">
              <div class="header-title d-flex align-center flex-nowrap">
                <!-- DRAG HANDLE (draggable) -->
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

                <!-- sort button (small) -->
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
                <!-- pin / unpin column -->
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

                <!-- remove header -->
                <v-btn variant="text" icon size="x-small" class="ml-1" :disabled="loading" @click.stop="removeHeader(col.key)" :title="$t('remove_column')">
                  <v-icon small color="error">mdi-close</v-icon>
                </v-btn>
              </div>
            </div>

            <!-- BOTTOM: filter / total -->
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
                  placeholder="Filter"
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

                <v-chip v-else-if="col.key != 'data-table-expand'" color="info" class="mt-1 header-total">
                  <v-icon>mdi-sigma</v-icon> =
                  {{ get_total(col.key) }}
                </v-chip>
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
import * as XLSX from 'xlsx'
import { nextTick } from 'vue'

const NULL_VALUE = '__NULL__'

export default {
  name: 'VirtualTableNativeReorder',
  props: {
    relations: Object,
    date: Object,
    filters: Object,
    items: Array,
    fetchData: Function,
    headers: { type: Array, required: true }
  },
  emits: ['sync_items'],
  data () {
    return {
      items_selected: [],
      loading: false,

      hiddenHeaderKeys: [],
      defaultHeaderKeys: [],
      pinnedHeaderKeys: ['data-table-select'],
      // pinnedHeaderKeys: ['data-table-select','data-table-expand'],
      sortOrders: [],
      pinnedCss: '',

      // ORDER of header objects used to render table columns (this is what we reorder)
      headerOrder: [],

      // drag state
      draggingKey: null,
      dragOverKey: null
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

    // original visible headers by prop order
    visibleHeaders () {
      return this.headers.filter(h => !this.hiddenHeaderKeys.includes(h.key))
    },

    // headerOrder filtered for visibility (source used by table)
    orderedVisibleHeaders () {
      return this.headerOrder.filter(h => !this.hiddenHeaderKeys.includes(h.key))
    },

    filteredItems () {
      return this.items.filter(row =>
        Object.entries(this.filters).every(([key, selected]) => {
          if (!selected.length) return true
          const val = row[key]

          if (val == null) return selected.includes(NULL_VALUE)

          if (Array.isArray(val)) {
            return val.some(v => selected.includes(v)) ||
                   (val.length === 0 && selected.includes(NULL_VALUE))
          }

          return selected.includes(val)
        })
      )
    },

    sortedFilteredItems () {
      if (!this.sortOrders.length) return this.filteredItems

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

      const arr = this.filteredItems.slice()

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
    filteredItems(value){
      this.$emit("sync_items",value);
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

    // ---- Sync headerOrder keeping previous user order where possible ----
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
      // ensure select column exists and is first if present
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
        this.items_selected.length === this.filteredItems.length
          ? []
          : this.filteredItems.map(i => i.id)
    },

    get_total (key) {
      return this.main.formatDisplayNumber(
        this.filteredItems.reduce((a, b) => a + (+b[key] || 0), 0)
      )
    },

    itemsFilteredExcept (exceptKey) {
      return this.items.filter(row =>
        Object.entries(this.filters).every(([key, selected]) => {
          if (key === exceptKey || !selected.length) return true
          const val = row[key]
          if (val == null) return selected.includes(NULL_VALUE)
          if (Array.isArray(val)) {
            return val.some(v => selected.includes(v)) ||
                   (val.length === 0 && selected.includes(NULL_VALUE))
          }
          return selected.includes(val)
        })
      )
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
      if (hasNull) options.push({ title: 'N/A', value: NULL_VALUE })

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

    /* Sorting helpers (unchanged) */
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

    /* Pin / CSS (unchanged) */
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

      // visible keys in DOM order based on orderedVisibleHeaders
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
                  // Body cell (tbody)
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
        css += `
          theader:{
            position:absolute
          }
        `
        cumLeft += entry.width;
        cumRight -= 0;
        // cumRight -= entry.width - 56;
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

    /* ---------------- Native Drag & Drop handlers ---------------- */

    onDragStart (evt, key) {
      // disallow dragging select column
      if (key === 'data-table-select') {
        evt.preventDefault(); return
      }
      // store the dragged key
      try {
        evt.dataTransfer.effectAllowed = 'move'
        evt.dataTransfer.setData('text/plain', key)
      } catch (e) {
        // some browsers require try/catch
      }
      this.draggingKey = key
      // add a 'dragging' class for visual feedback if desired
      const el = evt.currentTarget
      if (el) el.classList.add('dragging')
    },

    onDragOver (evt, targetKey) {
      // show visual indicator only when dragging a different header
      const srcKey = this.draggingKey || (evt.dataTransfer && evt.dataTransfer.getData && evt.dataTransfer.getData('text/plain'))
      if (!srcKey || srcKey === targetKey) {
        this.dragOverKey = null
        return
      }
      // don't allow dropping ONTO select col (we render select separately)
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
      // find indices in headerOrder
      const srcIdx = this.headerOrder.findIndex(h => h.key === srcKey)
      const tgtIdx = this.headerOrder.findIndex(h => h.key === targetKey)
      if (srcIdx === -1 || tgtIdx === -1) {
        this.dragOverKey = null
        this.draggingKey = null
        return
      }
      // move src to position of target (insert before target)
      const [item] = this.headerOrder.splice(srcIdx, 1)
      // re-compute tgtIdx if srcIdx < tgtIdx (because removal shifts indices)
      const adjustedTgtIdx = srcIdx < tgtIdx ? tgtIdx  : tgtIdx
      this.headerOrder.splice(adjustedTgtIdx, 0, item)

      // ensure select column remains first (if present)
      const si = this.headerOrder.findIndex(h => h.key === 'data-table-select')
      if (si > 0) {
        const s = this.headerOrder.splice(si, 1)[0]
        this.headerOrder.unshift(s)
      }

      this.dragOverKey = null
      this.draggingKey = null

      // recompute pinned css after DOM updates
      this.$nextTick(() => this.computePinnedCss())
    },

    onDragEnd (evt) {
      this.draggingKey = null
      this.dragOverKey = null
      const el = evt.currentTarget
      if (el) el.classList.remove('dragging')
    },

    /* ---------------- Excel export & helpers (unchanged) ---------------- */

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

      const selectedRows = this.items.filter(i => this.items_selected.includes(i.id))
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
    }
  }
}
</script>

<style scoped>
/* Ensure table expands horizontally to force a horizontal scrollbar when needed */
.table-scope table {
  min-width: max-content;
}

/* Keep header cells from wrapping and keep layout inline */
.header-th { vertical-align: top; }

/* Select checkbox column */
.select-th {
  min-width: 56px;
  max-width: 56px;
  width: 56px;
  white-space: nowrap;
}

/* Top row: title + actions inline, never wrap */
.header-top {
  gap: 8px;
  align-items: center;
}

/* Title text: prevent wrapping, show ellipsis if too long */
.header-title-text {
  white-space: nowrap;
  text-overflow: ellipsis;
  display: inline-block;
  max-width: 220px;
}

/* Filter / autocomplete: no-wrap and ellipsis */
.header-bottom .header-filter,
.header-bottom .header-total {
  white-space: nowrap;
  text-overflow: ellipsis;
  max-width: 260px;
}

/* Ensure action buttons don't expand */
.header-actions {
  flex: 0 0 auto;
}

/* Make sure header wrapper doesn't wrap into multiple lines */
.flex-nowrap {
  flex-wrap: nowrap !important;
}

th {
  min-width: 80px;
}

/* Smooth left transitions when pinning/unpinning */
.table-scope thead tr th, .table-scope tbody tr td {
  transition: left 0.12s;
  will-change: left;
}

/* Ensure pinned cells overlay other cells when needed */
.table-scope thead tr th[style*="position: sticky"], .table-scope tbody tr td[style*="position: sticky"] {
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
}

/* drag visuals */
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
</style>
