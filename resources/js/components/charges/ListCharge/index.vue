<template>
  <v-card>
    <v-card-title>
      {{ $t('datatable_list_charges') }}
    </v-card-title>

    <v-card-text>
      <v-row>
        <v-col>
          <!-- DATE -->
          <VDatePicker
            :is-dark="main.is_dark"
            :max-date="new Date()"
            v-model.range="date"
            mode="date"
          />
        </v-col>
        <v-col>
          {{ charge.get_fetched_warehouse_category_operation_list }}
        </v-col>
        <v-col>
          test
        </v-col>
      </v-row>
    </v-card-text>


    <DataTable
     :relations="relations"
     :filters="filters"
     :items="items"
     :date="date"
     :headers="headers"
     :fetchData="fetchCharge"
     >
        <!-- CELLS -->
        <template v-slot:[`item.date`]="{ item }">
          <v-chip class="text-info">
            {{ main.formateDate(item.date) }}
          </v-chip>
        </template>

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
    </DataTable>
  </v-card>
</template>

<script>
  import DataTable from '../../schema/plugins/DataTable.vue';

  import { useMain } from '@/stores/Main';
  import { useSupplier } from '@/stores/Supplier';
  import { useOrigin } from '@/stores/Origin';
  import { useFishery } from '@/stores/Fishery';
  import { useShip } from '@/stores/Ship';
  import { useChargeCategory } from '@/stores/ChargeCategory';
  import { useCharge } from '@/stores/Charge';
  export default {
    components: {
      DataTable
    },
    data:function(){
      return {
        filters: {
          date: [],
          charge_category_id: [],
          tracking_numbers: [],
          tracking_onp_numbers: [],
          supplier_ids: [],
          origin_id: [],
          ship_id: [],
          fishery_id: []
        },
        date: {
          start: new Date(2024, 10, 1),
          end: new Date(2025, 11, 17)
        },
      }
    },
    computed:{
        main: () => useMain(),
        suppliers: () => useSupplier().getList,
        origins: () => useOrigin().getList,
        fisheries: () => useFishery().getList,
        ships: () => useShip().getList,
        charge: () => useCharge(),
        fetchCharge: function(){
          return this.charge.fetchCharge
        },
        items: function(){
          return this.charge.charge_fetch_items
        },
        charge_categories: () => useChargeCategory().getList,
        relations:function(){
          return {
            supplier_ids: { source: this.suppliers, label: 'name' },
            ship_id: { source: this.ships, label: 'name' },
            origin_id: { source: this.origins, label: 'name' },
            fishery_id: { source: this.fisheries, label: 'name' },
            charge_category_id: { source: this.charge_categories, label: 'name' }
          }
        },
        headers:function(){
          return [
            { align : "center" , title: this.$t("date"), key: 'date' , visible:true },
            { align : "center" , title: this.$t("category"), key: 'charge_category_id' , visible:true },
            { align : "center" , title: this.$t("ship"), key: 'ship_id' , visible:false },
            { align : "center" , title: this.$t("tracking_number_short"), key: 'tracking_numbers' , visible:true },
            { align : "center" , title: this.$t("tracking_onp_number_short"), key: 'tracking_onp_numbers' , visible:false },
            { align : "center" , title: this.$t("suppliers"), key: 'supplier_ids' , visible:true },
            { align : "center" , title: this.$t("origin"), key: 'origin_id' , visible:true },
            { align : "center" , title: this.$t("fishery"), key: 'fishery_id' , visible:false },
            { align : "center" , title: this.$t("weight_kg"), key: 'total_weight' , visible:true },
            { align : "center" , title: this.$t("price_dh"), key: 'total_price', visible: true },
            { key: 'data-table-expand', align: 'end', visible: true }
            
          ]
        }
    }
  }
</script>