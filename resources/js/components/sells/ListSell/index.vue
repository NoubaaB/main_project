<template>
  <v-card>
    <v-card-title>
      {{ $t('datatable_sells') }}
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
            <!-- Toggle Buttons -->
            <div class="d-flex justify-center mb-3">
              <v-btn-toggle v-model="chartView" mandatory rounded="lg" density="compact" color="info" variant="outlined">
                <v-btn value="operations" size="small">
                  <v-icon start size="16">mdi-warehouse</v-icon>
                  {{ $t('by_operations') }}
                </v-btn>
                <v-btn value="categories" size="small">
                  <v-icon start size="16">mdi-tag-multiple-outline</v-icon>
                  {{ $t('by_categories') }}
                </v-btn>
              </v-btn-toggle>
            </div>

            <!-- Operations View (existing) -->
            <template v-if="chartView === 'operations'">
              <div class="d-flex align-content-end flex-wrap">
                <CustomChartWarehouseCategoryOpration v-for="formula in sell.get_formulas_list" :key="formula.id" :formula="formula"/>
              </div>
              <div class="d-flex align-content-end flex-wrap">
                <FixedChartWarehouseCategoryOpration v-for="warehouse_category_operation in sell.get_fetched_warehouse_category_operation_list"
                  :key="warehouse_category_operation.id"
                  :id="warehouse_category_operation.id"
                  :data="warehouse_category_operation"
                  :date="date"
                />
                <FixedChartWarehouseCategoryOpration v-for="warehouse_category_operation in sell.get_placeholder_warehouse_category_operation_list"
                  :key="warehouse_category_operation.id"
                  :id="warehouse_category_operation.id"
                />
              </div>
            </template>

            <!-- Categories View (new) -->
            <template v-if="chartView === 'categories'">
              <div class="d-flex align-content-end flex-wrap">
                <SellCategoryChart
                  v-for="cat in sell.get_fetched_sell_category_list"
                  :key="cat.id"
                  :data="cat"
                />
              </div>
              <div v-if="sell.get_fetched_sell_category_list.length === 0" class="text-center text-medium-emphasis pa-6">
                <v-icon size="40" color="grey" class="mb-2">mdi-chart-box-outline</v-icon>
                <div class="text-body-2">{{ $t('no_data_available') }}</div>
              </div>
            </template>
        </v-col>        
      </v-row>
    </v-card-text>

    <DataTable
     :relations="relations"
     :show_expand="true"
     :is_neasted="true"
     :advanced_relations="advanced_relations"
     :filters="filters"
     :advanced_filters="advanced_filters"
     :advanced_filters_keys="advanced_filters_keys"
     :invoicer="clients"
     :invoice="sell.invoice"
     :invoicer_name_prop="invoicer_name_prop"
     :select_invoicer="select_invoicer"
     :items="items"
     :sigma="sigma"
     :invoice_prop="invoice_prop"
     :date="date"
     :headers="headers"
     :detail_headers="detail_headers"
     :fetchData="fetchSell"
     :_invoices_print_selected="_invoices_print_selected"
     :_exportToExcel="_exportToExcel"
     :PostInvoice="PostInvoice"
     @sync_items="update_filtred_items_to_source"
     :delete_items="delete_items"
     :get_invoices_delete_selected_length="get_invoices_delete_selected_length"
     :listen_models="['Sell']"
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

        <template v-slot:[`item.client_invoice_tracking_number`]="{ item }">
          <v-chip
            v-for="v in (item.client_invoice_tracking_number || [])"
            :key="v"
            color="brown-darken-1"
            class="ma-1"
          >
            {{ v }}
          </v-chip>
          <v-chip
            v-if="!item.client_invoice_tracking_number || item.client_invoice_tracking_number.length == 0"
            color="brown-darken-1"
            class="ma-1"
          >
            {{ $t("n/a") }}
          </v-chip>
          
        </template>

        <template v-slot:[`item.client_ids`]="{ item }">
          <template v-if="(item.client_ids || []).length>0">
            <v-chip
              v-for="id in item.client_ids || [null]"
              :key="id ?? 'na'"
              color="deep-purple"
              class="ma-1"
            >
              {{ clients.find(s => s.id === id)?.name || $t("n/a") }}
            </v-chip>
          </template>
          <template v-else >
                <v-chip :color="main.not_aviable_color" class="ma-1">
                    <v-icon class="mr-1">mdi-circle-off-outline</v-icon> {{ $t("n/a") }} 
                </v-chip>
          </template>
        </template>

        <template v-slot:[`item.truck_ids`]="{ item }">
          <template v-if="(item.truck_ids || []).length>0">
            <v-chip
              v-for="id in item.truck_ids || [null]"
              :key="id ?? 'na'"
              color="pink"
              class="ma-1"
            >
              {{ trucks.find(s => s.id === id)?.registration_plate || $t("n/a") }}
            </v-chip>
          </template>
          <template v-else >
                <v-chip :color="main.not_aviable_color" class="ma-1">
                    <v-icon class="mr-1">mdi-circle-off-outline</v-icon> {{ $t("n/a") }} 
                </v-chip>
          </template>
        </template>
        
        <template v-slot:[`item.sell_category_id`]="{ item }">
          <v-chip  color="green" class="mx-1 mt-1">
              {{ sell_categories.find(f=>f.id == item.sell_category_id).name }} 
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
          <div class="d-flex align-center ga-1">
            <!-- Audit trail icon -->
            <v-btn
              icon
              size="x-small"
              variant="tonal"
              :color="item.audit_logs_count > 0 ? 'warning' : 'grey'"
              :disabled="item.audit_logs_count === 0"
              @click="viewAuditTrail(item)"
              class="vc-action-btn"
              :title="$t('audit_trail')"
            >
              <v-icon size="16">mdi-history</v-icon>
            </v-btn>

            <v-menu location="bottom end" transition="scale-transition" :close-on-content-click="true">
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-dots-vertical"
                  size="x-small"
                  variant="tonal"
                  color="info"
                  v-bind="props"
                  class="vc-action-btn"
                ></v-btn>
              </template>

            <v-card class="rounded-lg vc-action-menu" min-width="200" variant="flat">
              <v-list slim density="compact" class="pa-1" lines>
                <!-- View -->
                <v-list-item
                  class="rounded-lg vc-action-item"
                  prepend-icon="mdi-eye-outline"
                  @click="viewItem(item)"
                >
                  <v-list-item-title class="text-caption font-weight-bold text-info">{{ $t('view_sell') }}</v-list-item-title>
                </v-list-item>

                <!-- Audit Trail -->
                <v-list-item
                  class="rounded-lg vc-action-item"
                  prepend-icon="mdi-history"
                  @click="viewAuditTrail(item)"
                >
                  <v-list-item-title class="text-caption font-weight-bold text-warning">{{ $t('audit_trail') }}</v-list-item-title>
                </v-list-item>

                <!-- Invoice -->
                <v-list-item
                  v-if="item.invoice_id"
                  class="rounded-lg vc-action-item"
                  prepend-icon="mdi-file-document-check-outline"
                  @click="invoiceItem(item)"
                >
                  <v-list-item-title class="text-caption font-weight-bold text-green">{{ $t('invoice_item') }}</v-list-item-title>
                </v-list-item>

                <!-- Undo Invoice -->
                <v-list-item
                  v-if="item.invoice_id"
                  class="rounded-lg vc-action-item"
                  prepend-icon="mdi-file-document-remove-outline"
                  @click="undoInvoiceItem(item)"
                >
                  <v-list-item-title class="text-caption font-weight-bold text-red">{{ $t('undo_invoice_item') }}</v-list-item-title>
                </v-list-item>

                <v-divider v-if="item.invoice_id == null" class="mx-2 my-1"></v-divider>

                <!-- Edit -->
                <v-list-item
                  v-if="item.invoice_id == null"
                  class="rounded-lg vc-action-item"
                  prepend-icon="mdi-pencil-outline"
                  @click="editItem(item)"
                >
                  <v-list-item-title class="text-caption font-weight-bold text-orange">{{ $t('edit_item') }}</v-list-item-title>
                </v-list-item>

                <!-- Delete -->
                <v-list-item
                  v-if="item.invoice_id == null"
                  class="rounded-lg vc-action-item"
                  prepend-icon="mdi-delete-outline"
                  @click="deleteItem(item)"
                >
                  <v-list-item-title class="text-caption font-weight-bold text-red">{{ $t('delete_item') }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card>
          </v-menu>
          </div>
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
  import DataTable from '@/components/schema/plugins/DataTable.vue';
  import * as XLSX from 'xlsx';

  import FixedChartWarehouseCategoryOpration from './FixedChart/WarehouseCategoryOpration.vue';
  import CustomChartWarehouseCategoryOpration from './CustomChart/WarehouseCategoryOpration.vue';
  import SellCategoryChart from './CategoryChart/SellCategoryChart.vue';

  import { useMain } from '@/stores/Main';
  import { useClient } from '@/stores/Client';
  import { useOrigin } from '@/stores/Origin';
  import { useFishery } from '@/stores/Fishery';
  import { useShip } from '@/stores/Ship';
  import { useSellCategory } from '@/stores/SellCategory';
  import { useSell } from '@/stores/Sell';
  import { useWarehouse } from '@/stores/Warehouse';
  import { useFishFamily } from '@/stores/FishFamily';
  import { useTruck } from '@/stores/Truck';
  export default {
      components: {
        DataTable,
        FixedChartWarehouseCategoryOpration,
        CustomChartWarehouseCategoryOpration,
        SellCategoryChart
      },
      data:function(){
        return {
          chartView: 'operations',
          filters: {
            date: [],
            sell_category_id: [],
            tracking_numbers: [],
            tracking_onp_numbers: [],
            client_invoice_tracking_number: [],
            client_ids: [],
            truck_ids: [],
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
          invoicer_name_prop:"client_ids",
          select_invoicer:"select_clients",
          date: {
            start: new Date(2024, 2, 1),
            end: new Date()
          },
        }
    },
    computed:{
        main: () => useMain(),
        clients: () => useClient().getList,
        trucks: () => useTruck().getList,
        origins: () => useOrigin().getList,
        fisheries: () => useFishery().getList,
        ships: () => useShip().getList,
        warehouses: () => useWarehouse().getList,
        sell: () => useSell(),
        fetchSell: function(){
          return this.sell.fetchSell
        },
        PostInvoice: function(){
          return this.sell.PostInvoice
        },
        items: function () {
            return this.sell.sell_fetch_items
        },
        sigma: function(){
          return [
            {
              name: 'total_price',
              icon: 'mdi-cash',
              paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'] 
              
            },
            {
              name: 'total_weight',
              icon: 'mdi-weight-kilogram',
              paths: ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations']
            }
          ];
        },
        invoice_prop: function(){
          return "allow_invoice";
        },
        sell_categories: () => useSellCategory().getList,
        fish_families : ()=> useFishFamily().getList,
        relations:function(){
          return {
            client_ids: { source: this.clients, label: 'name' },
            truck_ids: { source: this.trucks, label: 'registration_plate' },
            ship_id: { source: this.ships, label: 'name' },
            origin_id: { source: this.origins, label: 'name' },
            fishery_id: { source: this.fisheries, label: 'name' },
            sell_category_id: { source: this.sell_categories, label: 'name' }
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
            { align : "center" , title: this.$t("category"), key: 'sell_category_id' , visible:true },
            { align : "center" , title: this.$t("ship"), key: 'ship_id' , visible:true },
            { align : "center" , title: this.$t("tracking_number_short"), key: 'tracking_numbers' , visible:true },
            { align : "center" , title: this.$t("tracking_onp_number_short"), key: 'tracking_onp_numbers' , visible:false },
            { align : "center" , title: this.$t("client_invoice_number_short"), key: 'client_invoice_tracking_number' , visible:true },
            { align : "center" , title: this.$t("clients"), key: 'client_ids' , visible:true },
            { align : "center" , title: this.$t("truck_id"), key: 'truck_ids' , visible:true },
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
            { align : "center" , title: this.$t("truck_id"), key: 'truck_ids' , visible:true },
            { align : "center" , title: this.$t("total_weight_on_kg"), key: 'total_weight' , visible:true },
            { align : "center" , title: this.$t("unit_price_currency",{currency:this.main.currency}), key: 'unit_price' , visible:true },
            { align : "center" , title: this.$t("total_price_on",{currency:this.main.currency}), key: 'total_price' , visible:true },
            { align : "center" , title: this.$t("package_type"), key: 'package_type_id' , visible:true },
            { align : "center" , title: this.$t("total_package_count_un"), key: 'total_package_count' , visible:true },
          ]
        }
    },
    methods:{
      delete_items: function (ids) {
        this.sell.delete(ids);
      },
      update_filtred_items_to_source: function (items) {
        this.sell.filtred_sell_fetch_items = items;
      },
      get_invoices_delete_selected_length: function (that) {
        return that.items_selected.length;
      },
      _invoices_print_selected: function (that) {
        return that.items.filter(i => that.items_selected.includes(i.id)).filter(e => e.allow_invoice && e.invoice_id != null).map(i => i.id);
      },
      _exportToExcel: function (that) {
        if (!that.items_selected || !that.items_selected_length) {
          console.warn('No rows selected to export')
          return
        }

        const selectedRows = that.processedItems.filter(i => that.items_selected.includes(i.id))
        const cols = that.orderedVisibleHeaders.filter(
          h => h.key !== 'data-table-select' && h.key !== 'data-table-expand'
        )
        const headerTitles = cols.map(h => h.title ?? h.key)

        const dataAoA = [
          headerTitles,
          ...selectedRows.map(row => cols.map(h => {
            if (h.key === 'date') {
              return row[h.key]
            }
            const v = that.resolveDisplayValue(row, h.key)
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
              cell.z = that.main.date_format
            } else {
              const m = moment(cell.v)
              if (cell.v && typeof cell.v === 'string' && m.isValid()) {
                cell.t = 'd'
                cell.z = that.main.date_format
              }
            }
          } 
        }

        const filename = `export_sell_${moment().format('YYYYMMDD_HHmmss')}.xlsx`
        XLSX.writeFile(wb, filename)
      },
      editItem: function (item) {
        this.$router.push({
          name: 'edit_sell',
          params: { id: item.id }
        })
      },
      invoiceItem: function (item) {
        this.$router.push({
          name: 'view_client_invoice',
          query: { ids: [item.invoice_id] }
        })
      },
      undoInvoiceItem: function (item) {
        this.sell.undoInvoiceItem(item.id);
      },
      viewItem: function (item) {
        this.$router.push({
          name: 'view_sell',
          params: { id: item.id }
        })
      },
      viewAuditTrail: function (item) {
        this.$router.push({ name: 'sell_audit_trail', params: { id: item.id } });
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

<style scoped>
.vc-action-btn {
  transition: transform .15s;
}
.vc-action-btn:hover {
  transform: scale(1.1);
}
.vc-action-menu {
  box-shadow: 0 8px 24px rgba(0,0,0,.1) !important;
}
.vc-action-item {
  padding: 6px 12px !important;
  min-height: 36px !important;
  margin-bottom: 2px;
  transition: background .12s;
}
.vc-action-item:hover {
  background: rgba(var(--v-border-color), .06);
}
</style>
