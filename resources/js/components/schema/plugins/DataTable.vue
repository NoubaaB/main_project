<template>
  <!-- TITLE + RESET BUTTON -->
  <v-card-text class="d-flex align-center justify-space-between">
    <div class="text-caption grey--text">{{$t('sprting_priority')}}</div>
  </v-card-text>

  <!-- DATE -->
  <VDatePicker
    :is-dark="main.is_dark"
    :max-date="new Date()"
    v-model.range="date"
    mode="date"
    expanded
    :disabled="loading"
  />

  <!-- injected dynamic CSS for pinned columns -->
  <div v-html="pinnedStyleHtml"></div>

  <!-- table-scope is the horizontal scroll container -->
  <div class="table-scope" ref="tableRoot" style="position:relative">
    <v-data-table-virtual
      v-model="items_selected"
      :headers="visibleHeaders"
      :items="sortedFilteredItems"
      item-key="id"
      fixed-header
      height="750"
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
          <v-col>
            test
          </v-col>
        </v-row>
      </template>

      <!-- expand button column (kept in UI but excluded from Excel) -->
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

      <!-- FILTER HEADER -->
      <template #headers="{ columns }">
        <tr>
          <!-- SELECT ALL CHECKBOX TH -->
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

          <!-- DATA HEADERS (we render every visible header except selection) -->
          <th
            v-for="(col, idx) in columns.filter(c => c.key !== 'data-table-select')"
            :key="idx"
            class="pa-1 header-th"
          >
            <!-- TOP: title + actions (single-line, no-wrap) -->
            <div class="header-top d-flex align-center justify-space-between flex-nowrap mb-1">
              <div class="header-title d-flex align-center flex-nowrap">
                <!-- sort button (small) -->
                <v-btn
                  variant="text"
                  icon
                  size="x-small"
                  :disabled="loading"
                  @click="toggleSort(col.key)"
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
                  @click="togglePin(col.key)"
                  :title="pinnedHeaderKeys.includes(col.key) ? $t('unpin_column') : $t('pin_column')"
                >
                  <v-icon small>
                    {{ pinnedHeaderKeys.includes(col.key) ? 'mdi-pin' : 'mdi-pin-off' }}
                  </v-icon>
                </v-btn>

                <!-- remove header -->
                <v-btn variant="text" icon size="x-small" class="ml-1" :disabled="loading" @click="removeHeader(col.key)" :title="$t('remove_column')">
                  <v-icon small color="error">mdi-close</v-icon>
                </v-btn>
              </div>
            </div>

            <!-- BOTTOM: filter / total (kept compact and no-wrap) -->
            <div class="header-bottom d-flex align-center">
              <div class="filter-wrap" :class="{ 'filter-has': filters[col.key] !== undefined }">
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
                  <!-- Custom Chips Slot -->
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
import { useMain } from '@/stores/Main'
import axios from 'axios'
import moment from 'moment'
import * as XLSX from 'xlsx'
import { nextTick } from 'vue'

const NULL_VALUE = '__NULL__'

export default {
  props: {
    api_url: String,
    api_url_prop: String,
    relations: Object,
    filters: Object,
    headers: { type: Array, required: true }
  },

  data () {
    return {
      date: {
        start: new Date(2024, 10, 1),
        end: new Date(2025, 11, 17)
      },
      items: [],
      items_selected: [],
      loading: false,

      // reactive array of hidden header keys
      hiddenHeaderKeys: [],

      // store default header keys for reset
      defaultHeaderKeys: [],

      // pinned header keys (columns the user fixed)
      // selection column pinned by default
      pinnedHeaderKeys: ['data-table-select'],

      // multi column sortOrders: each item { key, dir } where dir is 'asc'|'desc'
      // first item = highest priority
      sortOrders: [],

      // dynamic CSS for pinned columns (string)
      pinnedCss: ''
    }
  },

  created () {
    // initialize defaultHeaderKeys
    this.defaultHeaderKeys = this.headers.map(h => h.key)
    this.hiddenHeaderKeys = this.headers.filter(h => h.visible === false).map(h => h.key)
  },

  mounted () {
    this.fetchData()
    // make tableRoot horizontally scrollable so sticky works inside it
  },

  beforeUnmount () {
    const root = this.$refs.tableRoot
    if (root) root.removeEventListener('scroll', this.debouncedComputePinnedCss)
    window.removeEventListener('resize', this.debouncedComputePinnedCss)
  },

  computed: {
    main: () => useMain(),

    // visible headers computed from prop headers and hiddenHeaderKeys
    visibleHeaders () {
      // Return headers in their original order but without hidden ones.
      return this.headers.filter(h => !this.hiddenHeaderKeys.includes(h.key))
    },

    /**
     * CORE FILTER ENGINE: row must satisfy every column's selected set (column-wise OR, across columns AND)
     */
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

    // apply multi-column sorting on filteredItems
    sortedFilteredItems () {
      if (!this.sortOrders.length) return this.filteredItems

      const orders = this.sortOrders.slice() // clone

      const getComparable = (row, key) => {
        const val = row[key]
        // relations: try to resolve label via relations prop (source may be array name or array)
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

          // numeric compare if both can be numbers
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

    // returns HTML string with style tag containing pinnedCss
    pinnedStyleHtml () {
      if (!this.pinnedCss) return ''
      return `<style>${this.pinnedCss}</style>`
    }
  },

  watch: {
    // recompute pinned CSS when headers visibility or pinned keys change
    hiddenHeaderKeys () {
      this.debouncedComputePinnedCss()
    },
    pinnedHeaderKeys () {
      this.debouncedComputePinnedCss()
    },
    visibleHeaders () {
      // if visible headers changed (e.g. after reset), recompute CSS
      this.$nextTick(() => this.computePinnedCss())
    },
    "main.is_dark": function () {
      this.computePinnedCss();
    },
    date: {
      deep: true,
      handler (d) {
        if (d.start && d.end) this.fetchData()
      }
    }
  },

  methods: {
    async fetchData () {
      this.loading = true
      this.items_selected = []

      try {
        const res = await axios.get(this.api_url, {
          params: {
            date_start: moment(this.date.start).format('YYYY-MM-DD'),
            date_end: moment(this.date.end).format('YYYY-MM-DD')
          }
        })
        this.items = res.data[this.api_url_prop] || []
      } catch (err) {
        console.error(`Error fetching ${this.api_url}`, err)
      } finally {
        this.loading = false
        // recompute pinned columns after data finishes loading (table may re-render)
        this.$nextTick(() => this.computePinnedCss())
      }
    },

    // remove header (hide)
    removeHeader (key) {
      if (!this.hiddenHeaderKeys.includes(key)) {
        this.hiddenHeaderKeys.push(key)
      }
    },

    // reset to show all headers
    resetHeaders () {
      this.hiddenHeaderKeys = []
      // recompute after reset
      this.$nextTick(() => this.computePinnedCss())
    },

    // select all visible items
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

    // items filtered by all other columns except exceptKey (used to compute options)
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

    /* ---------------- Sorting helpers ---------------- */

    // return 'asc' | 'desc' | null
    sortDir (key) {
      const o = this.sortOrders.find(s => s.key === key)
      return o ? o.dir : null
    },

    // return index (priority) or null
    sortIndex (key) {
      const i = this.sortOrders.findIndex(s => s.key === key)
      return i === -1 ? null : i
    },

    // return icon name
    sortIcon (key) {
      const dir = this.sortDir(key)
      if (dir === 'asc') return 'mdi-sort-ascending'
      if (dir === 'desc') return 'mdi-sort-descending'
      return 'mdi-swap-vertical' // unsorted
    },

    sortTitle (key) {
      const dir = this.sortDir(key)
      if (!dir) return 'No sort (click to add asc)'
      return dir === 'asc' ? 'Sorted asc (click to change to desc)' : 'Sorted desc (click to remove)'
    },

    // toggle sort state for key: none -> asc -> desc -> none
    toggleSort (key) {
      const idx = this.sortOrders.findIndex(s => s.key === key)
      if (idx === -1) {
        // not present -> add as highest priority (unshift)
        this.sortOrders.unshift({ key, dir: 'asc' })
      } else if (this.sortOrders[idx].dir === 'asc') {
        // replace entry at idx with desc using splice to keep reactivity
        this.sortOrders.splice(idx, 1, { key, dir: 'desc' })
      } else {
        // remove the sort entry
        this.sortOrders.splice(idx, 1)
      }
    },

    // return priority index (0-based) or null
    sortPriority (key) {
      return this.sortIndex(key)
    },

    /* ---------------- Pin (fixed) columns functionality ---------------- */

    // toggle pin state for a header key
    togglePin (key) {
      const i = this.pinnedHeaderKeys.indexOf(key)
      if (i === -1) {
        // add to pinned (push to end so newest pins appear to the right of other pinned columns)
        this.pinnedHeaderKeys.push(key)
      } else {
        // remove
        this.pinnedHeaderKeys.splice(i, 1)
      }
      // recompute CSS after DOM update
      this.$nextTick(() => this.computePinnedCss())
    },

    // compute pinned CSS rules and store into pinnedCss
    async computePinnedCss () {
      // no pinned columns -> clear css
      if (!this.pinnedHeaderKeys.length) {
        this.pinnedCss = ''
        return
      }

      // wait for DOM to be ready
      await nextTick()

      // find table header cells inside our table-scope container
      const root = this.$refs.tableRoot
      if (!root) {
        this.pinnedCss = ''
        return
      }

      // thead th list (includes the select th we manually render as first th)
      // NOTE: searching inside root ensures we measure sizes within the scroll container
      const ths = Array.from(root.querySelectorAll('thead tr th'))
      if (!ths.length) {
        this.pinnedCss = ''
        return
      }

      // Build an array of widths for each column (in DOM order)
      const widths = ths.map(th => {
        const rect = th.getBoundingClientRect()
        // widths should be measured relative to the table (use offsetWidth fallback)
        return Math.max(1, Math.round(rect.width || th.offsetWidth || 0))
      })

      // theme-aware colors (light/dark)
      const isDark = !!(this.main && this.main.is_dark)
      // pick appropriate backgrounds and shadows for pinned headers/body
      const headerBg = isDark ? 'rgba(255,255,255,0.06)' : '#ffffff'
      const bodyBg = isDark ? 'rgba(33,33,33,1)' : '#f9f9fa'
      const selectBg = isDark ? 'rgba(255,255,255,0.04)' : '#f1f1f1'
      const headerShadow = isDark ? '2px 0 12px rgba(0,0,0,0.6)' : '2px 0 8px rgba(0,0,0,0.06)'
      const bodyShadow = isDark ? '1px 0 8px rgba(0,0,0,0.45)' : '1px 0 6px rgba(0,0,0,0.03)'

      // visibleHeaders keys in DOM order:
      const visibleKeys = this.visibleHeaders.map(h => h.key)

      // Build pinned entries in the order of pinnedHeaderKeys (so pinned order equals pinnedHeaderKeys order).
      // Each entry will contain domIndex and width
      const pinnedDomEntries = []
      for (const pinnedKey of this.pinnedHeaderKeys) {
        if (pinnedKey === 'data-table-select') {
          // special-case: our select column is the first th in DOM
          pinnedDomEntries.push({ key: pinnedKey, domIndex: 1, width: widths[0] || 56 })
          continue
        }
        const visibleIndex = visibleKeys.indexOf(pinnedKey)
        if (visibleIndex === -1) continue // pinned key not visible
        const domIndex = visibleIndex + 2 // nth-child (1: select checkbox)
        pinnedDomEntries.push({ key: pinnedKey, domIndex, width: widths[domIndex - 1] || 100 })
      }

      if (!pinnedDomEntries.length) {
        this.pinnedCss = ''
        return
      }

      // compute left offsets so pinned columns stack from the left (cumulative widths of previous pinned columns)
      let css = ''
      let cumLeft = 0
      for (let i = 0; i < pinnedDomEntries.length; i++) {
        const entry = pinnedDomEntries[i]
        const nth = entry.domIndex
        const left = Math.round(cumLeft)

        // Header cell (thead)
        css += `
        .table-scope thead tr th:nth-child(${nth}) {
          position: sticky;
          left: ${left}px;
          right: ${left}px;
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
          right: ${left}px;
          z-index: 1;
          background: ${bodyBg};
          box-shadow: ${bodyShadow};
          border-right: 1px solid rgba(0,0,0,0.03);
        }
        theader:{
          position:absolute
        }
        `

        cumLeft += entry.width
      }

      // ensure select-th has top z (if pinned or not)
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

    // a debounced wrapper to avoid computing too frequently on resize/scroll
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
      const cols = this.visibleHeaders.filter(
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
            cell.z = 'yyyy-mm-dd'
          } else {
            const m = moment(cell.v)
            if (cell.v && typeof cell.v === 'string' && m.isValid()) {
              cell.v = m.toDate()
              cell.t = 'd'
              cell.z = 'yyyy-mm-dd'
            }
          }
        }
      }

      const filename = `export_${moment().format('YYYYMMDD_HHmmss')}.xlsx`
      XLSX.writeFile(wb, filename)
    }
  }
}
</script>

<style scoped>

/* Ensure table expands horizontally to force a horizontal scrollbar when needed */
.table-scope table {
  min-width: max-content; /* table grows to content width enabling horizontal scroll */
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
  max-width: 220px; /* adjust as needed */
}

/* Filter / autocomplete: no-wrap and ellipsis */
.header-bottom .header-filter,
.header-bottom .header-total {
  white-space: nowrap;
  text-overflow: ellipsis;
  max-width: 260px; /* adjust as needed */
}

/* Ensure action buttons don't expand */
.header-actions {
  flex: 0 0 auto;
}

/* Make sure header wrapper doesn't wrap into multiple lines */
.flex-nowrap {
  flex-wrap: nowrap !important;
}

/* Optional: give th a minimum width so layout is stable */
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
</style>
