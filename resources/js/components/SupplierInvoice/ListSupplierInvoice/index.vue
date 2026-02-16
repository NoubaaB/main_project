<template>
  <v-card>
    <v-card-title>
      {{ $t('datatable_supplier_invoice') }}
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
          <v-divider opacity="0.25" variant="dashed" thickness="1" class="my-5"></v-divider>
          <div class="text-center mt-3" role="group" aria-label="select supplier">
            <v-select
                dense
                color="info"
                class="text-info"
                density="compact"
                variant="outlined"
                rounded
                item-value="value"
                item-title="text"
                hide-details
                :items="supplier_invoice.typeCharts"
                v-model="supplier_invoice.selectTypeChart"
                :label="$t('select_type_chart')"
            >
              <template v-slot:chip="{ props, item }">
                  <v-chip
                  v-bind="props"
                  class="text-white"
                  color="info"
                  variant="tonal"
                  :prepend-icon="item.raw.chart_icon"
                  >
                  {{ $t(item.raw.text) }}
                  </v-chip>
              </template>
              <template v-slot:item="{ props, item }">
                  <v-list-item
                  v-bind="props"
                  @click="props.onClick"
                  class="text-info"
                  :title="$t(item.raw.text)"
                  :prepend-icon="item.raw.chart_icon"
                  color="info"
                  >
                  </v-list-item>
              </template>
            </v-select>
          </div>
        </v-col>
        <v-col >
          <SupplierInvoiceCard/>
        </v-col>
      </v-row>
    </v-card-text>
    <DataTable
     :show_expand="false"
     :is_neasted="false"
     :_invoices_print_selected="_invoices_print_selected"
     :_exportToExcel="_exportToExcel"
     :relations="relations"
     :filters="filters"
     :invoicer="suppliers"
     :invoicer_name_prop="invoicer_name_prop"
     :select_invoicer="select_invoicer"
     :items="items"
     :sigma="sigma"
     :invoice_prop="invoice_prop"
     :date="date"
     :headers="headers"
     :fetchData="fetchSupplierInvoice"
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

        <template v-slot:[`item.truck_ids`]="{ item }">
          <template v-if="item.truck_ids.length>0">
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

        <!-- ROW SELECT -->
        <template v-slot:[`item.data-table-select`]="{ internalItem, isSelected, toggleSelect }">
          <v-checkbox-btn
            :model-value="isSelected(internalItem)"
            color="info"
            @update:model-value="toggleSelect(internalItem)"
          />
        </template>
        

        <template v-slot:[`item.total_weight`]="{ item }">
          {{ main.formatDisplayNumber(item.total_weight) }}
        </template>

        <template v-slot:[`item.total_price`]="{ item }">
          {{ main.formatDisplayNumber(item.total_price) }}
        </template>

        <template v-slot:[`item.actions`]="{ item }">
          <v-menu>
            <template v-slot:activator="{ props }">
              <v-btn
                icon="mdi-dots-vertical"
                size="x-small"
                variant="outlined"
                color="info"
                v-bind="props"
              ></v-btn>

            </template>

            <v-list slim density="compact" lines>
              <v-list-item  prepend-icon="mdi-pencil" @click="editItem(item)">
                <v-list-item-title color="warning" size="x-small" variant="tonal" v-tooltip:top="$t('edit_item')">{{$t('edit_item')}}</v-list-item-title>
              </v-list-item>
              <v-divider></v-divider>
              <v-list-item  prepend-icon="mdi-eye" @click="editItem(item)">
                <v-list-item-title color="success" size="x-small" variant="tonal" v-tooltip:top="$t('view_item')">{{$t('view_item')}}</v-list-item-title>
              </v-list-item>
              <v-divider></v-divider>
              <v-list-item prepend-icon="mdi-delete" @click="editItem(item)">
                <v-list-item-title color="red" size="x-small" variant="tonal" v-tooltip:top="$t('delete_item')">{{$t('delete_item')}}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </template>

    </DataTable>
  </v-card>
</template>

<script>
  import DataTable from '@/components/schema/plugins/DataTable.vue';
  import SupplierInvoiceCard from './SupplierInvoiceCard';
  import { useMain } from '@/stores/Main';
  import { useSupplier } from '@/stores/Supplier';
  import { useTruck } from '@/stores/Truck';
  import { useSupplierInvoice } from '@/stores/SupplierInvoice';
  export default {
      components: {
        DataTable,
        SupplierInvoiceCard
      },
      data:function(){
        return {
          filters: {
            date: [],
            charge_category_id: [],
            tracking_numbers: [],
            supplier_ids: [],
            truck_ids: [],
          },
          invoicer_name_prop:"supplier_ids",
          select_invoicer:"select_suppliers",
          date: {
            start: new Date(2024, 2, 1),
            end: new Date()
          },
        }
    },
    computed:{
        main: () => useMain(),
        supplier_invoice: () => useSupplierInvoice(),
        suppliers: () => useSupplier().getList,
        trucks: () => useTruck().getList,
        fetchSupplierInvoice: function(){
          return this.supplier_invoice.fetchSupplierInvoice
        },
        items: function () {
            return this.supplier_invoice.supplier_invoice_fetch_items
        },
        sigma: function(){
          return [
            {
              name: 'total_price',
              paths: ['charges'] 
              
            },
            {
              name: 'total_weight',
              paths: ['charges']
            }
          ];
        },
        invoice_prop: function(){
          return "allow_invoice";
        },
        relations:function(){
          return {
            supplier_ids: { source: this.suppliers, label: 'name' },
            truck_ids: { source: this.trucks, label: 'registration_plate' },
          }
        },
        
        headers:function(){
          return [
            { align : "center" , title: this.$t("date"), key: 'date' , visible:true },
            { align : "center" , title: this.$t("tracking_number_short"), key: 'tracking_numbers' , visible:true },
            { align : "center" , title: this.$t("suppliers"), key: 'supplier_ids' , visible:true },
            { align : "center" , title: this.$t("truck_id"), key: 'truck_ids' , visible:true },
            { align : "center" , title: this.$t("weight_kg"), key: 'total_weight' , visible:true },
            { align : "center" , title: this.$t("price_dh",{currency:this.main.currency}), key: 'total_price', visible: true },
            { align : "center" , title: this.$t("actions"), key: 'actions' , visible:true ,sortable: false },
          ]
        },
    },
    methods:{
      _invoices_print_selected: function (that) {
        console.log("that",that.items_selected)
        return that.items_selected
      },
      _exportToExcel: function (that) {
        
      },
      update_filtred_items_to_source: function (items) {
        this.supplier_invoice.filtred_supplier_invoice_fetch_items = items;
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
