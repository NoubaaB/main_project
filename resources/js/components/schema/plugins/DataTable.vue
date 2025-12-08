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

      <div style="position:relative">
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
              <v-icon class="mr-1">mdi-table-headers-eye</v-icon>  {{ $t("reset_header") }} ({{ hiddenHeaderKeys.length }})
            </v-btn>
          </v-col>
          <v-col>
            test
          </v-col>
        </v-row>

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
            <template #loader>
                <v-progress-linear
                color="info"
                indeterminate
                ></v-progress-linear>
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
              <th class="pa-1">
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

              <th
                v-for="col in columns.filter(c => c.key !== 'data-table-select')"
                :key="col.key"
                class="pa-1"
              >
                <!-- header title / actions -->
                <v-row class="text-center align-center mb-1 d-inlin" no-gutters>
                  <v-col cols="7" class="pa-0 ">
                    <div class="text-subtitle-2  font-weight-medium d-inlin">
                        <!-- sort button (small) -->
                        <v-btn variant="text" icon size="x-small" :disabled="loading" @click="toggleSort(col.key)" :title="sortTitle(col.key)">
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
                      {{ col.title }}
                    </div>
                  </v-col>

                  <v-col class="justify-end pa-0 d-inlin">
                    <!-- remove header -->
                    <v-btn variant="text" icon size="x-small" class="ml-1" :disabled="loading" @click="removeHeader(col.key)" :title="$t('remove_column')">
                      <v-icon small color="error">mdi-close</v-icon>
                    </v-btn>
                  </v-col>
                </v-row>

                <!-- filter / total -->
                <v-row class="text-center">
                  <v-col class="pt-0">
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
                        class="text-info"
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

                    <v-chip v-else color="info" class="mt-1">
                      {{ get_total(col.key) }}
                    </v-chip>
                  </v-col>
                </v-row>
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
            <v-progress-circular
                indeterminate
                size="48"
                width="4"
                color="info"
            />
                <div class="mt-3 text-info font-weight-medium">{{ $t('loading_data') }}</div>
            </div>
        </v-overlay>
    </div>
</template>

<script>
import { useMain } from '@/stores/Main'
import axios from 'axios'
import moment from 'moment'

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
        start: new Date(2024, 11, 1),
        end: new Date(2025, 11, 17)
      },
      items: [],
      items_selected: [],
      loading: false,

      // reactive array of hidden header keys
      hiddenHeaderKeys: [],

      // store default header keys for reset
      defaultHeaderKeys: [],

      // multi column sortOrders: each item { key, dir } where dir is 'asc'|'desc'
      // first item = highest priority
      sortOrders: []
    }
  },

  created () {
    // initialize defaultHeaderKeys
    this.defaultHeaderKeys = this.headers.map(h => h.key)
    this.hiddenHeaderKeys = this.headers.filter(h=>!h.visible).map(h => h.key)
  },

  mounted () {
    this.fetchData()
  },

  computed: {
    main: () => useMain(),

    // visible headers computed from prop headers and hiddenHeaderKeys
    visibleHeaders () {
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
    }
  },

  watch: {
    date: {
      deep: true,
      handler (d) {
        if (d.start && d.end) this.fetchData()
      }
    }
  }
}
</script>

<style scoped>
th {
  vertical-align: top;
}
.v-card {
  min-height: 200px;
}
</style>
