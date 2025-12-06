<template>
  <v-container fluid>
    <v-card>

      <v-card-title>
        Cascading Filters with NULL (N/A) Support
      </v-card-title>

      <!-- DATE -->
      <VDatePicker
        :is-dark="main.is_dark"
        :max-date="new Date()"
        v-model.range="date"
        mode="date"
        expanded
      />

      <v-data-table-virtual
        v-model="items_selected"
        :headers="headers"
        :items="filteredItems"
        item-key="id"
        fixed-header
        height="750"
        show-select
        density="compact"
      >
        <template v-slot:[`item.data-table-select`]="{ internalItem, isSelected, toggleSelect }">
          <v-checkbox-btn
              :model-value="isSelected(internalItem)"
              color="info"
              @update:model-value="toggleSelect(internalItem)"
          ></v-checkbox-btn>
        </template>
        <template v-slot:no-data>
            <div class="text-orange">
                <v-icon icon="mdi-database-alert-outline" size="large" class="mx-2"></v-icon>
                <span>No data available</span>
            </div>
        </template>

        <!-- FILTER HEADER -->
        <template #headers="{ columns }">
          <tr>
            <th class="pa-1">
                  <v-checkbox
                    :model-value="items_selected.length == filteredItems.length"
                    :indeterminate="items_selected.length>0 && !(items_selected.length == filteredItems.length)"
                    @update:model-value="selectAll"
                    color="info"
                    class="pt-3 ma-0"
                    hide-details
                  />
            </th>
            <th v-for="col in columns.filter(col => col.key != 'data-table-select')" :key="col.key" class="pa-1">
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
                  ></v-list-item>
                </template>
              </v-autocomplete>

              <v-chip v-else color="info" class="mt-1">
                {{ get_total(col.key) }}
              </v-chip>
            </th>
          </tr>
        </template>

        <!-- CELLS -->
        <template v-slot:[`item.tracking_numbers`]="{ item }">
          <v-chip
            v-for="v in item.tracking_numbers"
            :key="v"
            color="info"
            class="ma-1"
          >
            {{ v }}
          </v-chip>
        </template>

        <template v-slot:[`item.tracking_onp_numbers`]="{ item }">
          <v-chip
            v-for="v in item.tracking_onp_numbers"
            :key="v"
            color="secondary"
            class="ma-1"
          >
            {{ v }}
          </v-chip>
        </template>

        <template v-slot:[`item.supplier_ids`]="{ item }">
          <v-chip
            v-for="id in item.supplier_ids || [null]"
            :key="id ?? 'na'"
            color="deep-purple"
            class="ma-1"
          >
            {{ suppliers.find(s => s.id === id)?.name || 'N/A' }}
          </v-chip>
        </template>
        
        <template v-slot:[`item.charge_category_id`]="{ item }">
          <v-chip  color="green" class="mx-1 mt-1">
              {{ charge_categories.find(f=>f.id == item.charge_category_id).name }} 
          </v-chip>
        </template>

        <template v-slot:[`item.origin_id`]="{ item }">
          <v-chip color="cyan" class="ma-1">
            {{ origins.find(o => o.id === item.origin_id)?.name || 'N/A' }}
          </v-chip>
        </template>

        <template v-slot:[`item.ship_id`]="{ item }">
          <v-chip color="grey" class="ma-1">
            {{ ships.find(s => s.id === item.ship_id)?.name || 'N/A' }}
          </v-chip>
        </template>

        <template v-slot:[`item.fishery_id`]="{ item }">
          <v-chip color="amber" class="ma-1">
            {{ fisheries.find(f => f.id === item.fishery_id)?.name || 'N/A' }}
          </v-chip>
        </template>

        <template v-slot:[`item.total_weight`]="{ item }">
          {{ main.formatDisplayNumber(item.total_weight) }}
        </template>

        <template v-slot:[`item.total_price`]="{ item }">
          {{ main.formatDisplayNumber(item.total_price) }}
        </template>

      </v-data-table-virtual>
    </v-card>
  </v-container>
</template>

<script>
import { useMain } from '@/stores/Main'
import { useSupplier } from '@/stores/Supplier'
import { useOrigin } from '@/stores/Origin'
import { useFishery } from '@/stores/Fishery'
import { useShip } from '@/stores/Ship'
import { useChargeCategory } from '@/stores/ChargeCategory'

const NULL_VALUE = '__NULL__'

export default {
  data () {
    return {
      date: {
        start: new Date(2024, 11, 1),
        end: new Date(2025, 11, 17)
      },
      items: [],
      items_selected: [],

      relations: {
        supplier_ids: { source: 'suppliers', label: 'name' },
        ship_id: { source: 'ships', label: 'name' },
        origin_id: { source: 'origins', label: 'name' },
        fishery_id: { source: 'fisheries', label: 'name' },
        charge_category_id: { source: 'charge_categories', label: 'name' }
      },

      filters: {
        date: [],
        charge_category_id: [],
        tracking_numbers: [],
        tracking_onp_numbers: [],
        supplier_ids: [],
        origin_id: [],
        ship_id: [],
        fishery_id: []
      }
    }
  },
  mounted : function() {
      this.fetchData();
    
  },

  computed: {
    main: () => useMain(),
    suppliers: () => useSupplier().getList,
    origins: () => useOrigin().getList,
    fisheries: () => useFishery().getList,
    ships: () => useShip().getList,
    charge_categories: () => useChargeCategory().getList,

    headers () {
      return [
        { title: 'Date', key: 'date' },
        { title: 'Charge Category', key: 'charge_category_id' },
        { title: 'Ship', key: 'ship_id' },
        { title: 'Tracking', key: 'tracking_numbers' },
        { title: 'ONP', key: 'tracking_onp_numbers' },
        { title: 'Suppliers', key: 'supplier_ids' },
        { title: 'Origin', key: 'origin_id' },
        { title: 'Fishery', key: 'fishery_id' },
        { title: 'Weight (KG)', key: 'total_weight' },
        { title: 'Price (DH)', key: 'total_price' }
      ]
    },

    filteredItems () {
      return this.items.filter(row =>
        Object.entries(this.filters).every(([key, selected]) => {
          if (!selected.length) return true
          const val = row[key]

          if (val == null) return selected.includes(NULL_VALUE)

          if (Array.isArray(val)) {
            return (
              val.some(v => selected.includes(v)) ||
              (val.length === 0 && selected.includes(NULL_VALUE))
            )
          }

          return selected.includes(val)
        })
      )
    }
  },

  methods: {
    fetchData: async function () {
      try {
        this.loading = true;
        const response = await axios.get("/api/get_charges", {
          params: {
            date_start: moment(this.date.start).format("YYYY-MM-DD"),
            date_end: moment(this.date.end).format("YYYY-MM-DD"),
          },
        });
        this.items = response.data.charges;
      } catch (error) {
        console.error("Error fetching charges:", error);
      } finally {
        this.loading = false;
      }
    },
    selectAll () {
      if (this.items_selected.length == this.filteredItems.length) {
        this.items_selected = [];
      } else {
        this.items_selected = this.filteredItems.map(e=>e.id);
      }
    },

    get_total (key) {
      return this.main.formatDisplayNumber(
        this.filteredItems.reduce((a, b) => a + (+b[key] || 0), 0)
      )
    },

    filterOptions (key) {
      const values = new Set()
      let hasNull = false

      this.filteredItems.forEach(row => {
        const v = row[key]
        if (v == null || (Array.isArray(v) && v.length === 0)) {
          hasNull = true
        } else if (Array.isArray(v)) {
          v.forEach(x => values.add(x))
        } else {
          values.add(v)
        }
      })

      const options = []

      if (hasNull) {
        options.push({ title: 'N/A', value: NULL_VALUE })
      }

      if (this.relations[key]) {
        const { source, label } = this.relations[key]
        options.push(
          ...this[source]
            .filter(i => values.has(i.id))
            .map(i => ({ title: i[label], value: i.id }))
        )
      } else {
        options.push(
          ...[...values].map(v => ({ title: String(v), value: v }))
        )
      }

      return options
    }
  },
  watch:{
    date: {
      handler: async function (newDate) {
        if (!newDate.start || !newDate.end) return;
        this.fetchData();
      },
      deep: true,
    }
  }
}
</script>

<style scoped>
th {
  vertical-align: top;
}
</style>
