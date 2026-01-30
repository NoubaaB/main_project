<template>
  <v-row>
    <v-col>
      <div id="invoice" class="invoice-width">
        <supplier-invoice 
        v-for="(item, index) in chunkItems"
        :key="index"
        :is_show="is_show"
        :items="item"
        :total="total"
        :allow_summary="allow_summary(index,item.length)"
        :pagination="itemPagination(index)" 
        :emptyRows="emptyRows(item.length)"
          />
        <supplier-invoice 
        v-if="checkChunkLatestItems"
        :is_show="is_show"
        :items="[]"
        :total="total"
        :allow_summary="allow_summary(0,0)"
        :pagination="itemPagination(chunkItems.length)" 
        :emptyRows="emptyRows(0)"
          />
      </div>
    </v-col>
    <v-col cols="4">
      <DisplayInvoiceContoller :nbrRows="nbrRows" :pagination="itemPagination()" :toEXCEL="toEXCEL" :toPDF="toPDF"/>
    </v-col>
  </v-row>
</template>

<script>
  import { useSupplierInvoice } from '@/stores/SupplierInvoice';
  import { useMain } from '@/stores/Main';
  import SupplierInvoice from './DisplaySupplierInvoice.vue';
  import DisplayInvoiceContoller from './DisplayInvoiceContoller.vue';
  export default {
    components: { SupplierInvoice , DisplayInvoiceContoller },
    data() {
      return {
        is_show: true,
      };
    },
    computed: {
      supplierInvoice: () => useSupplierInvoice(),
      main: () => useMain(),
      items: function () {
        if (this.supplierInvoice.items.length) return this.supplierInvoice.items; return this.supplierInvoice.getItems;
      },
      nbrRows: function () {
        return this.items.length;
      },
      chunkItems: function () {
        const result = [];
        for (let i = 0; i < this.items.length; i += this.supplierInvoice.final_page_rows_nbr) {
          result.push(this.items.slice(i, i + this.supplierInvoice.final_page_rows_nbr));
        }
        return result;
      },
      checkChunkLatestItems: function () {
        if(this.chunkItems.length === 0) return false;
        return this.chunkItems[this.chunkItems.length - 1].length > this.supplierInvoice.active_page_rows_nbr;
      },
      total: function () {
        return {
          _price: +this.items.reduce((acc, item) => acc + parseFloat(item.total_price), 0).toFixed(2),
          price: this.main.formatDisplayNumber(this.items.reduce((acc, item) => acc + parseFloat(item.total_price), 0).toFixed(2)),
          weight: this.main.formatDisplayNumber(this.items.reduce((acc, item) => acc + parseFloat(item.total_weight), 0).toFixed(2)),
        }
      }
    },
    methods:{
      emptyRows: function (items_length) {
        if (this.supplierInvoice.active_page_rows_nbr >= items_length) {
          return this.supplierInvoice.active_page_rows_nbr - items_length ;
        } else {
          if (this.supplierInvoice.active_page_rows_nbr < items_length) {
          return this.supplierInvoice.final_page_rows_nbr - items_length ;
          }
        }
        return 0;
      },
      itemPagination: function (index=1) {
        let length = this.chunkItems.length + (this.checkChunkLatestItems ? 1 : 0)
        return {
          allow: (length > 1),
          page: index + 1,
          total_pages : length
        }
      },
      allow_summary: function (index, items_length) {
        return  (items_length <= this.supplierInvoice.active_page_rows_nbr)
      },
      toPDF: function () {
          this.is_show = !this.is_show;
      },
      toEXCEL: function () {
          // Support single invoice or array of invoices:
          const invoices = Array.isArray(this.invoice) ? this.invoice : [this.invoice];

          // Flatten invoices into rows: one row per item
          const rows = _.flatMap(invoices, (inv) =>
            (inv.itemes || []).map((item) => ({
              InvoiceID: inv.id,
              InvoiceDate: moment(inv.date).format('YYYY-MM-DD'),
              Suppliers: Array.isArray(inv.supplier_ids) ? inv.supplier_ids.join(', ') : inv.supplier_ids,
              ItemDate: item.date ? moment(item.date).format('YYYY-MM-DD') : '',
              TruckID: item.truck_id ?? '',
              TrackingNumbers: Array.isArray(item.tracking_numbers) ? item.tracking_numbers.join(', ') : item.tracking_numbers,
              Stockable: item.stockable ?? '',
              StockableType: item.stockable_type ?? '',
              TotalWeight: item.total_weight ?? 0,
              UnitPrice: item.unit_price ?? 0,
              TotalPrice: item.total_price ?? ( (item.total_weight ?? 0) * (item.unit_price ?? 0) )
            }))
          );

          // Column order
          const headers = [
            'InvoiceID',
            'InvoiceDate',
            'Suppliers',
            'ItemDate',
            'TruckID',
            'TrackingNumbers',
            'Stockable',
            'StockableType',
            'TotalWeight',
            'UnitPrice',
            'TotalPrice'
          ];

          // Create worksheet from JSON with desired header order
          const ws = XLSX.utils.json_to_sheet(rows, { header: headers, skipHeader: false });

          // Optionally set column widths (in characters approximate). Adjust as desired.
          ws['!cols'] = [
            { wch: 10 }, // InvoiceID
            { wch: 12 }, // InvoiceDate
            { wch: 18 }, // Suppliers
            { wch: 12 }, // ItemDate
            { wch: 8 },  // TruckID
            { wch: 20 }, // TrackingNumbers
            { wch: 18 }, // Stockable
            { wch: 14 }, // StockableType
            { wch: 12 }, // TotalWeight
            { wch: 10 }, // UnitPrice
            { wch: 12 }  // TotalPrice
          ];

          // Optionally format numeric cells: set number type for numeric columns
          // Walk through worksheet cells and set cell types for numeric columns
          const range = XLSX.utils.decode_range(ws['!ref']);
          for (let R = range.s.r + 1; R <= range.e.r; ++R) {
            // columns by index (0-based): 8 TotalWeight, 9 UnitPrice, 10 TotalPrice
            const numericCols = [8, 9, 10];
            numericCols.forEach((cIdx) => {
              const cellAddress = { c: cIdx, r: R };
              const cellRef = XLSX.utils.encode_cell(cellAddress);
              const cell = ws[cellRef];
              if (cell && (cell.v !== null && cell.v !== undefined && cell.v !== '')) {
                // Ensure number type
                const parsed = Number(cell.v);
                if (!Number.isNaN(parsed)) {
                  cell.t = 'n';
                  cell.v = parsed;
                }
              }
            });
          }

          // Create workbook and append worksheet
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Invoices');

          // Trigger download
          XLSX.writeFile(wb, this.fileName);
        }
    }
  }
</script>
<style scoped>
  .invoice-width{
    width : var(--invoice-width)!important
  }
  
</style>
