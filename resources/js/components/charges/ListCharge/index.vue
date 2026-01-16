<template>
  <v-card>
    <v-card-title>
      {{ $t('datatable_charges') }}
    </v-card-title>

    <v-card-text>
      <v-row>
        <v-col cols="3">
          <!-- DATE -->
          <DatePicker
            :is-dark="main.is_dark"
            :max-date="new Date()"
            v-model.range="date"
            mode="date"
            :locale="main.lang.short"
            expanded 
          />
          <div class="text-center mt-3" role="group" aria-label="Date range">
            <!-- Start date chip -->
            <v-chip color="info" variant="tonal" class="mr-2">
              <v-icon left small class="mr-2">mdi-calendar-start</v-icon>
              <span>{{ main.formateDate(date.start) }}</span>
            </v-chip>
            <v-divider vertical></v-divider>
            <!-- End date chip -->
            <v-chip color="indigo" variant="tonal" class="ml-2">
              <span>{{ main.formateDate(date.end) }}</span>
              <v-icon left small class="ml-2">mdi-calendar-end</v-icon>
            </v-chip>
          </div>
        </v-col>
        <v-col style="overflow-y: auto; max-height: 47vh !important;">
            <div class="d-flex align-content-end flex-wrap " >
              <CustomChartWarehouseCategoryOpration v-for="formula in charge.get_formulas_list" :key="formula.id" :formula="formula"/>
            </div>
            <div class="d-flex align-content-end flex-wrap " >
                <FixedChartWarehouseCategoryOpration v-for="warehouse_category_operation in charge.get_fetched_warehouse_category_operation_list"
                 :key="warehouse_category_operation.id" 
                 :id="warehouse_category_operation.id"
                 :data="warehouse_category_operation"
                 :date="date"
                 /> 
                 <FixedChartWarehouseCategoryOpration v-for="warehouse_category_operation in charge.get_placeholder_warehouse_category_operation_list"
                 :key="warehouse_category_operation.id" 
                 :id="warehouse_category_operation.id"
                 />
            </div>
        </v-col>        
      </v-row>
    </v-card-text>

    <DataTable
     :relations="relations"
     :advanced_relations="advanced_relations"
     :filters="filters"
     :advanced_filters="advanced_filters"
     :advanced_filters_keys="advanced_filters_keys"
     :invoicer="suppliers"
     :invoice="charge.invoice"
     :invoicer_name_prop="invoicer_name_prop"
     :select_invoicer="select_invoicer"
     :items="items"
     :sigma="sigma"
     :invoice_prop="invoice_prop"
     :date="date"
     :headers="headers"
     :detail_headers="detail_headers"
     :fetchData="fetchCharge"
     :PostInvoice="PostInvoice"
     @sync_items="update_filtred_items_to_source"
     >
        <!-- CELLS (same as before) -->
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
          <template v-if="item.supplier_ids.length>0">
            <v-chip
              v-for="id in item.supplier_ids || [null]"
              :key="id ?? 'na'"
              color="deep-purple"
              class="ma-1"
            >
              {{ suppliers.find(s => s.id === id)?.name || $t("n/a") }}
            </v-chip>
          </template>
          <template v-else >
                <v-chip :color="main.not_aviable_color" class="ma-1">
                    <v-icon class="mr-1">mdi-circle-off-outline</v-icon> {{ $t("n/a") }} 
                </v-chip>
          </template>
        </template>
        
        <template v-slot:[`item.charge_category_id`]="{ item }">
          <v-chip  color="green" class="mx-1 mt-1">
              {{ charge_categories.find(f=>f.id == item.charge_category_id).name }} 
          </v-chip>
        </template>

        <template v-slot:[`item.origin_id`]="{ item }">
          <v-chip color="cyan" class="ma-1">
            {{ origins.find(o => o.id === item.origin_id)?.name || $t("n/a") }}
          </v-chip>
        </template>

        <template v-slot:[`item.ship_id`]="{ item }">
          <v-chip color="grey" class="ma-1">
            {{ ships.find(s => s.id === item.ship_id)?.name || $t("n/a") }}
          </v-chip>
        </template>

        <template v-slot:[`item.fishery_id`]="{ item }">
          <v-chip color="amber" class="ma-1">
            {{ fisheries.find(f => f.id === item.fishery_id)?.name || $t("n/a") }}
          </v-chip>
        </template>

        <template v-slot:[`item.total_weight`]="{ item }">
          {{ main.formatDisplayNumber(item.total_weight) }}
        </template>

        <template v-slot:[`item.total_price`]="{ item }">
          {{ main.formatDisplayNumber(item.total_price) }}
        </template>

        <template v-slot:[`item.actions`]="{ item }">
          <v-btn color="warning" class="mx-1" icon="mdi-pencil" size="x-small" variant="tonal" @click="editItem(item)"
          v-tooltip:top="$t('edit_item')"
          ></v-btn>
          <v-btn color="success" class="mx-1" icon="mdi-eye" size="x-small" variant="tonal" @click="editItem(item)"
          v-tooltip:top="$t('view_item')"
          ></v-btn>
          <v-btn color="red" class="mx-1" icon="mdi-delete" size="x-small" variant="tonal" @click="editItem(item)"
          v-tooltip:top="$t('delete_item')"
          ></v-btn>
        </template>

        <template v-slot:[`item.allow_invoice`]="{ item }">
          <template v-if="item.allow_invoice">
            <v-chip
              v-if="item.invoice_id != null"
              color="green"
              @click="print_invoice(item)"
              class="ma-1"
              v-tooltip:top="$t('print_invoice')"
            >
              <v-icon>
                mdi-printer-check
              </v-icon>
            </v-chip>
            <v-chip
              v-else
              color="warning"
              class="ma-1"
              v-tooltip:top="$t('uninvoiced')"
            >
              <v-icon>
                mdi-printer-pos-cog
              </v-icon>
            </v-chip>
          </template>
          <template v-else>
            <v-chip
              color="gray"
              class="ma-1"
              v-tooltip:top="$t('non_posting_invoice')"
            >
              <v-icon>
                mdi-printer-pos-cancel
              </v-icon>
            </v-chip>
          </template>
        </template>
    </DataTable>
  </v-card>
</template>

<script>
  import DataTable from '../../schema/plugins/DataTable.vue';
  import FixedChartWarehouseCategoryOpration from './FixedChart/WarehouseCategoryOpration.vue';
  import CustomChartWarehouseCategoryOpration from './CustomChart/WarehouseCategoryOpration.vue';

  import { useMain } from '@/stores/Main';
  import { useSupplier } from '@/stores/Supplier';
  import { useOrigin } from '@/stores/Origin';
  import { useFishery } from '@/stores/Fishery';
  import { useShip } from '@/stores/Ship';
  import { useChargeCategory } from '@/stores/ChargeCategory';
  import { useCharge } from '@/stores/Charge';
  import { useWarehouse } from '@/stores/Warehouse';
  import { useFishFamily } from '@/stores/FishFamily';
  export default {
      components: {
        DataTable,
        FixedChartWarehouseCategoryOpration,
        CustomChartWarehouseCategoryOpration
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
            fishery_id: [],
          },
          advanced_filters: {
            warehouse_id: [],     // array of warehouse ids
            stockable_type: [],   // array of strings (types)
            stockable_id: [],     // array of strings in form "Type__id" (see DataTable)
            fish_family_id:[]     // array of fish family ids
          },
          advanced_filters_keys: ["warehouse_id", "stockable_type", "stockable_id", "fish_family_id"],
          invoicer_name_prop:"supplier_ids",
          select_invoicer:"select_suppliers",
          date: {
            start: new Date(2023, 2, 1),
            end: new Date(2023, 11, 17)
          },
        }
    },
    computed:{
        main: () => useMain(),
        suppliers: () => useSupplier().getList,
        origins: () => useOrigin().getList,
        fisheries: () => useFishery().getList,
        ships: () => useShip().getList,
        warehouses: () => useWarehouse().getList,
        charge: () => useCharge(),
        fetchCharge: function(){
          return this.charge.fetchCharge
        },
        PostInvoice: function(){
          return this.charge.PostInvoice
        },
        items: function () {
            return this.charge.charge_fetch_items
        },
        sigma: function(){
          return [
            {
              name: 'total_price',
              paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'] 
              
            },
            {
              name: 'total_weight',
              paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations']
            }
          ];
        },
        invoice_prop: function(){
          return "allow_invoice";
        },
        charge_categories: () => useChargeCategory().getList,
        fish_families : ()=> useFishFamily().getList,
        relations:function(){
          return {
            supplier_ids: { source: this.suppliers, label: 'name' },
            ship_id: { source: this.ships, label: 'name' },
            origin_id: { source: this.origins, label: 'name' },
            fishery_id: { source: this.fisheries, label: 'name' },
            charge_category_id: { source: this.charge_categories, label: 'name' }
          }
        },
        advanced_relations:function(){
          return {
            // warehouse id (single path, uses warehouses source)
            warehouse_id: { path: 'warehouse_stockable_tracking_number_operations', source: this.warehouses, label: "name" },

            // stockable filters: scan two nested arrays and extract stockable.* fields
            stockable_type: { paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'] },
            stockable_id: { paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'] },
            // fish family uses fish_families as source for labels
            fish_family_id: { paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'], source: this.fish_families,  label: `name_${this.main.lang.code}` }
          }
        },
        
        headers:function(){
          return [
            { key: 'data-table-expand', align: 'center', visible: true },
            { align : "center" , title: this.$t("date"), key: 'date' , visible:true },
            { align : "center" , title: this.$t("category"), key: 'charge_category_id' , visible:true },
            { align : "center" , title: this.$t("ship"), key: 'ship_id' , visible:false },
            { align : "center" , title: this.$t("tracking_number_short"), key: 'tracking_numbers' , visible:true },
            { align : "center" , title: this.$t("tracking_onp_number_short"), key: 'tracking_onp_numbers' , visible:false },
            { align : "center" , title: this.$t("suppliers"), key: 'supplier_ids' , visible:true },
            { align : "center" , title: this.$t("weight_kg"), key: 'total_weight' , visible:true },
            { align : "center" , title: this.$t("price_dh",{currency:this.main.currency}), key: 'total_price', visible: true },
            { align : "center" , title: this.$t("origin"), key: 'origin_id' , visible:true },
            { align : "center" , title: this.$t("fishery"), key: 'fishery_id' , visible:false },
            { align : "center" , title: this.$t("status"), key: 'allow_invoice' , visible:true },
            { align : "center" , title: this.$t("actions"), key: 'actions' , visible:true ,sortable: false },
            
          ]
        },
        detail_headers:function(){
          return [
            { align : "center" , title: this.$t("classification"), key: 'classification' , visible:true },
            { align : "center" , title: this.$t("product"), key: 'product' , visible:true },
            { align : "center" , title: this.$t("product_family"), key: 'product_family' , visible:true },
            { align : "center" , title: this.$t("warehouse"), key: 'warehouse' , visible:true },
            { align : "center" , title: this.$t("total_weight_on_kg"), key: 'total_weight' , visible:true },
            { align : "center" , title: this.$t("unit_price_currency",{currency:this.main.currency}), key: 'unit_price' , visible:true },
            { align : "center" , title: this.$t("total_price_on",{currency:this.main.currency}), key: 'total_price' , visible:true },
            { align : "center" , title: this.$t("package_type"), key: 'package_type_id' , visible:true },
            { align : "center" , title: this.$t("total_package_count_un"), key: 'total_package_count' , visible:true },
          ]
        }
    },
    methods:{
      update_filtred_items_to_source: function (items) {
        this.charge.filtred_charge_fetch_items = items;
      },
      editItem: function (item) {
        console.log("item",item)
      },
      viewItem: function (item) {
        console.log("item",item)
      },
      deleteItem: function (item) {
        console.log("item",item)
      },
      print_invoice: function (item) {
        console.log("item",item)
      },
    }
  }
</script>
