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
     @delete_items="delete_items"
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
              <v-list-item prepend-icon="mdi-delete" @click="deleteItem(item)">
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
  import * as XLSX from 'xlsx';
    
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
            { align : "center" , title: this.$t("supplier_invoice_number_short"), key: 'tracking_numbers' , visible:true },
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
        return that.items_selected
      },
      _exportToExcel: function (that) {
        let data = this.supplier_invoice.formatedSupplierInvoiceData(this.items.filter(i=>that.items_selected.includes(i.id)))
        // defensive: ensure supplier_invoice is an array
        const invoices = Array.isArray(data)
            ? _.cloneDeep(data)
            : (data ? [_.cloneDeep(data)] : []);

        if (!invoices.length) {
            console.warn('this.supplier_invoice is empty or undefined — nothing to export');
            return;
        }

        // 1) compute invoice totals from items (safe Number conversions)
        invoices.forEach((inv) => {
            const items = Array.isArray(inv.items) ? inv.items : [];

            // compute total_weight digit-by-digit
            const totalWeight = items.reduce((acc, it) => {
            const v = Number(it.total_weight ?? 0);
            return acc + (Number.isFinite(v) ? v : 0);
            }, 0);

            // compute total_price digit-by-digit (use provided total_price or compute weight * unit_price)
            const totalPrice = items.reduce((acc, it) => {
            const w = Number(it.total_weight ?? 0);
            const u = Number(it.unit_price ?? 0);
            const itemTotal = Number(it.total_price ?? (w * u));
            return acc + (Number.isFinite(itemTotal) ? itemTotal : 0);
            }, 0);

            inv.total_weight = totalWeight;
            inv.total_price = totalPrice;
        });

        // 2) Flatten items into rows (use exact property names)
        const itemRows = _.flatMap(invoices, (inv) => {
            const invoice_tracking_numbers = Array.isArray(inv.tracking_numbers) ? inv.tracking_numbers.join(', ') : (inv.tracking_numbers ?? '');
            const supplier_ids = Array.isArray(inv.supplier_ids) ? inv.supplier_ids.join(', ') : (inv.supplier_ids ?? '');
            const invoice_id = inv.id ?? '';
            const invoice_date = inv.date ? moment(inv.date).format('YYYY-MM-DD') : '';

            const items = Array.isArray(inv.items) ? inv.items : [];
            return items.map((it, idx) => ({
            // invoice-prefixed to avoid collisions but keep original names for translation
            invoice_id,
            invoice_date,
            invoice_tracking_numbers,
            supplier_ids,

            // item properties kept exactly as provided
            date: it.date ? moment(it.date).format('YYYY-MM-DD') : '',
            charge_id: it.charge_id ?? '',
            charge_category_id: it.charge_category_id ?? '',
            truck_id: it.truck_id ?? '',
            tracking_numbers: Array.isArray(it.tracking_numbers) ? it.tracking_numbers.join(', ') : (it.tracking_numbers ?? ''),
            stockable: it.stockable ?? '',
            stockable_type: it.stockable_type ?? '',
            total_weight: Number(it.total_weight ?? 0),
            unit_price: Number(it.unit_price ?? 0),
            total_price: Number(it.total_price ?? ( (Number(it.total_weight ?? 0)) * (Number(it.unit_price ?? 0)) ))
            }));
        });

        // 3) Build invoice summary rows (invoice-prefixed keys)
        const invoiceRows = invoices.map((inv) => ({
            invoice_id: inv.id ?? '',
            invoice_date: inv.date ? moment(inv.date).format('YYYY-MM-DD') : '',
            invoice_tracking_numbers: Array.isArray(inv.tracking_numbers) ? inv.tracking_numbers.join(', ') : (inv.tracking_numbers ?? ''),
            supplier_ids: Array.isArray(inv.supplier_ids) ? inv.supplier_ids.join(', ') : (inv.supplier_ids ?? ''),
            total_weight: Number(inv.total_weight ?? 0),
            total_price: Number(inv.total_price ?? 0)
        }));

        // 4) Keys / column order (use exact property names)
        const itemKeys = [
            'invoice_id', 'invoice_date', 'invoice_tracking_numbers', 'supplier_ids',
            'date', 'charge_id', 'charge_category_id', 'truck_id', 'tracking_numbers',
            'stockable', 'stockable_type', 'total_weight', 'unit_price', 'total_price'
        ];
        const invoiceKeys = ['invoice_id', 'invoice_date', 'invoice_tracking_numbers', 'supplier_ids', 'total_weight', 'total_price'];

        // 5) Create worksheets
        const wsItems = XLSX.utils.json_to_sheet(itemRows, { header: itemKeys });
        const wsInvoices = XLSX.utils.json_to_sheet(invoiceRows, { header: invoiceKeys });

        // 6) Translate headers using this.$t(originalPropertyName)
        const translateKey = (k) => {
            if (k.startsWith('invoice_')) return this.$t(k.replace(/^invoice_/, ''));
            return this.$t(k);
        };

        const setTranslatedHeaders = (ws, keys) => {
            // header row is the first row in sheet; overwrite header cells with translations
            for (let c = 0; c < keys.length; c++) {
            const cellRef = XLSX.utils.encode_cell({ c, r: 0 });
            ws[cellRef] = ws[cellRef] || { t: 's', v: '' };
            ws[cellRef].v = String(translateKey(keys[c]) ?? keys[c]);
            ws[cellRef].t = 's';
            }
        };

        setTranslatedHeaders(wsItems, itemKeys);
        setTranslatedHeaders(wsInvoices, invoiceKeys);

        // 7) Force numeric types for numeric keys
        const forceNumeric = (ws, keys, numericKeys) => {
            if (!ws || !ws['!ref']) return;
            const range = XLSX.utils.decode_range(ws['!ref']);
            const keyIndex = {};
            keys.forEach((k, i) => keyIndex[k] = i);

            for (let r = range.s.r + 1; r <= range.e.r; ++r) {
            numericKeys.forEach((k) => {
                const c = keyIndex[k];
                if (c === undefined) return;
                const cellRef = XLSX.utils.encode_cell({ c, r });
                const cell = ws[cellRef];
                if (cell && cell.v !== '' && cell.v !== undefined && cell.v !== null) {
                const parsed = Number(cell.v);
                if (!Number.isNaN(parsed)) {
                    cell.t = 'n';
                    cell.v = parsed;
                }
                }
            });
            }
        };

        forceNumeric(wsItems, itemKeys, ['total_weight', 'unit_price', 'total_price']);
        forceNumeric(wsInvoices, invoiceKeys, ['total_weight', 'total_price']);

        // 8) Set reasonable column widths
        wsItems['!cols'] = itemKeys.map((k) => {
            if (k.includes('id')) return { wch: 12 };
            if (k.includes('date')) return { wch: 12 };
            if (k.includes('tracking')) return { wch: 30 };
            if (k.includes('weight') || k.includes('price') || k === 'unit_price') return { wch: 14 };
            return { wch: 18 };
        });
        wsInvoices['!cols'] = invoiceKeys.map((k) => {
            if (k.includes('id')) return { wch: 12 };
            if (k.includes('date')) return { wch: 12 };
            if (k.includes('tracking')) return { wch: 30 };
            if (k.includes('weight') || k.includes('price')) return { wch: 14 };
            return { wch: 18 };
        });

        // 9) Create workbook and write file
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, wsItems, 'Items');
        XLSX.utils.book_append_sheet(wb, wsInvoices, 'Invoices');

        const filename = `supplier_invoices_${moment().format('YYYYMMDD_HHmmss')}.xlsx`;
        XLSX.writeFile(wb, filename);
      },
      update_filtred_items_to_source: function (items) {
        this.supplier_invoice.filtred_supplier_invoice_fetch_items = items;
      },
      delete_items: function (ids) {
        this.supplier_invoice.delete(ids);
      },
      editItem: function (item) {
        console.log("item",item)
      },
      viewItem: function (item) {
        console.log("item",item)
      },
      deleteItem: function (item) {
        this.supplier_invoice.delete([item.id]);
        console.log("item",item)
      },
      print_invoice: function (item) {
        console.log("item",item)
      },
    },
    watch: {
      "supplier_invoice.selectTypeChart": function (item) {
        if(item != "by_supplier_invoice") this.supplier_invoice.selected_supplier_id = null
      }
    }
  }
</script>
