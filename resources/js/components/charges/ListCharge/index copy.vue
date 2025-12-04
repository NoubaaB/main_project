<template>
  <v-container fluid>
    <v-card>
      <v-card-title>
        Cascading Filters (IDs + Arrays + Relations)
      </v-card-title>
      <div>
        <VDatePicker  
          :is-dark="main.is_dark"
          :max-date="new Date()"
          v-model.range="date"
          mode="date"
          expanded
        />
      </div>
      <v-data-table-virtual
        v-model="items_selected"
        :headers="headers"
        :items="filteredItems"
        item-selectable="selectable"
        density="compact"
        show-select
        height="750"
        item-key="id"
        fixed-header
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
        <!-- FILTER ROW -->
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
              <v-row class="text-center">
                <v-col class="pb-0">
                  {{ col.title }}
                </v-col>
              </v-row>
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
                      ></v-list-item>
                    </template>
                  </v-autocomplete>
                  <v-chip v-else color="info" class="mx-1 mt-1">
                      {{ get_total(col.key) }} 
                  </v-chip>
                </v-col>
              </v-row>
            </th>
          </tr>
        </template>
        <template v-slot:[`item.charge_category_id`]="{ item }">
            <v-chip  color="green" class="mx-1 mt-1">
                {{ charge_categories.find(f=>f.id == item.charge_category_id).name }} 
            </v-chip>
        </template>
        <template v-slot:[`item.tracking_numbers`]="{ item }">
            <v-chip  color="info" v-for="tracking_number in item.tracking_numbers" :key="tracking_number" class="mx-1 mt-1">
                {{ tracking_number }}
            </v-chip>
        </template>
        <template v-slot:[`item.tracking_onp_numbers`]="{ item }">
            <v-chip  color="secondary" v-for="tracking_onp_number in item.tracking_onp_numbers" :key="tracking_onp_number" class="mx-1 mt-1">
                {{ tracking_onp_number }}
            </v-chip>
        </template>
        <template v-slot:[`item.supplier_ids`]="{ item }">
            <v-chip  color="deep-purple-lighten-2" v-for="supplier_id in item.supplier_ids" :key="supplier_id" class="mx-1 mt-1">
                {{ suppliers.find(f=>f.id == supplier_id).name || "N/A" }} 
            </v-chip>
        </template>
        <template v-slot:[`item.ship_id`]="{ item }">
            <v-chip  color="gray" class="mx-1 mt-1">
                {{ ships.find(f=>f.id == item.ship_id)?.name || "N/A" }} 
            </v-chip>
        </template>
        <template v-slot:[`item.origin_id`]="{ item }">
            <v-chip  color="cyan-lighten-3" class="mx-1 mt-1">
                {{ origins.find(f=>f.id == item.origin_id).name || "N/A" }} 
            </v-chip>
        </template>
        <template v-slot:[`item.fishery_id`]="{ item }">
            <v-chip  color="amber" class="mx-1 mt-1">
                {{ fisheries.find(f=>f.id == item.fishery_id).name || "N/A" }} 
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
import { useMain } from '@/stores/Main';
import { useSupplier } from '@/stores/Supplier';
import { useOrigin } from '@/stores/Origin';
import { useFishery } from '@/stores/Fishery';
import { useChargeCategory } from '@/stores/ChargeCategory';
import { useShip } from '@/stores/Ship';

export default {
  data () {
    return {
      date: {
        start: new Date(2024, 11, 1),
        end: new Date(2025, 11, 17)
      },
      items: [],
      items_selected: [],
      loading: false,

      /**
       * ✅ RELATION CONFIG (SINGLE SOURCE OF TRUTH)
       * supports:
       * - single id
       * - array of ids
       */
      relations: {
        supplier_ids: { source: 'suppliers', label: 'name' },
        ship_id: { source: 'ships', label: 'name' },
        charge_category_id: { source: 'charge_categories', label: 'name' },
        origin_id: { source: 'origins', label: 'name' },
        fishery_id: { source: 'fisheries', label: 'name' }
      },

      // ============================
      // FILTER STATE (IDS)
      // ============================
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
  mounted: async function () {
      this.fetchData();
  },
  computed: {
    main() {
      return useMain();
    },
    origins() {
      return useOrigin().getList;
    },
    ships() {
      return useShip().getList;
    },
    suppliers() {
      return useSupplier().getList;
    },
    fisheries() {
      return useFishery().getList;
    },
    charge_categories: function () {
      return useChargeCategory().getList;
    },
    headers() {
      return [
        { title: this.$t("date"), key: "date", align: "center" },
        { title: this.$t("charge_category"), key: "charge_category_id", align: "center" },
        { title: this.$t("ship"), key: "ship_id", align: "center" },
        { title: this.$t("tracking_numbers"), key: "tracking_numbers", align: "center" },
        { title: this.$t("tracking_onp_numbers"), key: "tracking_onp_numbers", align: "center" },
        { title: this.$t("supplier_ids"), key: "supplier_ids", align: "center" },
        { title: this.$t("origin"), key: "origin_id", align: "center" },
        { title: this.$t("fishery"), key: "fishery_id", align: "center" },
        { title: this.$t("total_weight")+" (DH)", key: "total_weight", align: "center" },
        { title: this.$t("total_price")+ " (KG)", key: "total_price", align: "center" },
      ];
    },
    /**
     * ✅ CORE FILTER ENGINE
     */
    filteredItems () {
      return this.items.filter(row =>
        Object.entries(this.filters).every(([key, selected]) => {
          if (!selected.length) return true

          const val = row[key]
          if (Array.isArray(val)) {
            return val.some(v => selected.includes(v))
          }
          return selected.includes(val)
        })
      )
    },
    /**
     * ✅ DISPLAY TRANSFORMATION (IDS → NAMES)
     */
    displayItems () {
      return this.filteredItems.map(row => {
        const r = { ...row }

        Object.entries(this.relations).forEach(([key, cfg]) => {
          const src = this[cfg.source]

          if (Array.isArray(row[key])) {
            r[key] = row[key]
              .map(id => src.find(i => i.id === id)?.[cfg.label])
              .filter(Boolean)
              .join(', ')
          } else {
            r[key] = src.find(i => i.id === row[key])?.[cfg.label] ?? '-'
          }
        })

        return r
      })
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
    selectAll() {
      if (this.items_selected.length == this.filteredItems.length) {
        this.items_selected = [];
      } else {
        this.items_selected = this.filteredItems.map(e=>e.id);
      }
    },
    get_total: function (item_key) {
      let total = this.filteredItems.reduce((acc,item)=>+item[item_key] + acc , 0.00)
      return this.main.formatDisplayNumber(total);
    },

    /**
     * ✅ CASCADING FILTER OPTIONS
     */
    filterOptions (key) {
      const values = new Set()

      this.filteredItems.forEach(row => {
        const v = row[key]
        if (Array.isArray(v)) {
          v.forEach(x => values.add(x))
        } else {
          values.add(v)
        }
      })

      // relational column
      if (this.relations[key]) {
        const { source, label } = this.relations[key]
        return this[source]
          .filter(i => values.has(i.id))
          .map(i => ({ title: i[label], value: i.id }))
      }

      // simple column
      return [...values].filter(Boolean).map(v => ({
        title: v,
        value: v
      }))
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
