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
          console.log("export to excel")
      }
    }
  }
</script>
<style scoped>
  .invoice-width{
    width : var(--invoice-width)!important
  }
  
</style>
