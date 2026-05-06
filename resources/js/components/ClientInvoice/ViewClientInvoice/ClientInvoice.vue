<template>
    <display-client-invoice 
    v-for="(item, index) in chunkItems"
    :key="index"
    :items="item"
    :client_invoice_detail="client_invoice_detail"
    :total="total"
    :allow_summary="allow_summary(index,item.length)"
    :pagination="itemPagination(index)" 
    :emptyRows="emptyRows(item.length)"
      />
    <display-client-invoice 
    v-if="checkChunkLatestItems"
    :items="[]"
    :client_invoice_detail="client_invoice_detail"
    :total="total"
    :allow_summary="allow_summary(0,0)"
    :pagination="itemPagination(chunkItems.length)" 
    :emptyRows="emptyRows(0)"
      />
</template>

<script>
  import { useClientInvoice } from '@/stores/ClientInvoice';
  import { useMain } from '@/stores/Main';
  import DisplayClientInvoice from './DisplayClientInvoice.vue';
  export default {
    components: { DisplayClientInvoice },
    props:{
      client_invoice_data:Object
    },
    computed: {
      clientInvoice: () => useClientInvoice(),
      main: () => useMain(),
      items: function () {
        if (this.clientInvoice.display_mode === 'shipping') {
          return this.client_invoice_data.shipping_items || [];
        }
        return this.client_invoice_data.detail_items || [];
      },
      client_invoice_detail: function () {
        return {
          id: this.client_invoice_data.id,
          date: this.client_invoice_data.date,
          tracking_numbers: this.client_invoice_data.tracking_numbers.toString(),
          client_name: this.client_invoice_data.client_name || '',
          address: this.client_invoice_data.address || [],
          total_price: this.client_invoice_data.total_price,
          total_weight: this.client_invoice_data.total_weight,
        }
      },
      chunkItems: function () {
        const result = [];
        for (let i = 0; i < this.items.length; i += this.clientInvoice.final_page_rows_nbr) {
          result.push(this.items.slice(i, i + this.clientInvoice.final_page_rows_nbr));
        }
        return result;
      },
      checkChunkLatestItems: function () {
        if (this.chunkItems.length === 0) return false;
        return this.chunkItems[this.chunkItems.length - 1].length > this.clientInvoice.active_page_rows_nbr;
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
        if (this.clientInvoice.active_page_rows_nbr >= items_length) {
          return this.clientInvoice.active_page_rows_nbr - items_length ;
        } else {
          if (this.clientInvoice.active_page_rows_nbr < items_length) {
          return this.clientInvoice.final_page_rows_nbr - items_length ;
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
        return  (items_length <= this.clientInvoice.active_page_rows_nbr)
      }
    }
  }
</script>
