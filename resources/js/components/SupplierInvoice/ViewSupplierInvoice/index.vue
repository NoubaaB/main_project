<template>
  <v-row>
    <v-col>
      <div id="invoice" class="invoice-width">
        <supplier-invoice 
        v-for="(item, index) in chunkItems"
        :key="index"
        :is_show="is_show"
        :items="item"
        :allow_summary="allow_summary(index,item.length)"
        :pagination="itemPagination(index)" 
        :emptyRows="emptyRows(item.length)"
          />
        <supplier-invoice 
        v-if="checkChunkLatestItems"
        :is_show="is_show"
        :items="[]"
        :allow_summary="allow_summary(0,0)"
        :pagination="itemPagination(chunkItems.length)" 
        :emptyRows="emptyRows(0)"
          />
      </div>
    </v-col>
    <v-col cols="4">
      <DisplayInvoiceContoller :toPDF="toPDF"/>
    </v-col>
  </v-row>
</template>

<script>
  import { useSupplierInvoice } from '@/stores/SupplierInvoice';
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
      items: function () {
        return Array.from({ length: 60 }, () => (
          {
            date: "2025-10-01",
            truck_id: 5,
            tracking_numbers: ["100001","10002"],
            stockable: {
                stockable_id: 4,
                stockable_type : "FishType"
            },
            total_weight: 200,
            unit_price: 5,
            total_price: 1000
          }
        ));
      },
      chunkItems: function () {
        const result = [];
        for (let i = 0; i < this.items.length; i += this.supplierInvoice.final_page_rows_nbr) {
          result.push(this.items.slice(i, i + this.supplierInvoice.final_page_rows_nbr));
        }
        return result;
      },
      checkChunkLatestItems: function () {
        return this.chunkItems[this.chunkItems.length - 1].length > this.supplierInvoice.active_page_rows_nbr;
      },
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
      itemPagination: function (index) {
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
      }
    }
  }
</script>
<style>
  .invoice-width{
    width : var(--invoice-width)!important
  }
  
</style>
