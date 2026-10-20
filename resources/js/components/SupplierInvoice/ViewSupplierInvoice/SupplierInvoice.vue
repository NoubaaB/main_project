<template>
    <display-supplier-invoice 
    v-for="(item, index) in chunkItems"
    :key="index"
    :items="item"
    :pageIndex="index"
    :supplier_invoice_detail="supplier_invoice_detail"
    :total="total"
    :allow_summary="allow_summary(index,item.length)"
    :pagination="itemPagination(index)" 
    :emptyRows="emptyRows(item.length)"
      />
    <display-supplier-invoice 
    v-if="checkChunkLatestItems"
    :items="[]"
    :pageIndex="chunkItems.length"
    :supplier_invoice_detail="supplier_invoice_detail"
    :total="total"
    :allow_summary="allow_summary(0,0)"
    :pagination="itemPagination(chunkItems.length)" 
    :emptyRows="emptyRows(0)"
      />
</template>

<script>
  import { useSupplierInvoice } from '@/stores/SupplierInvoice';
  import { useMain } from '@/stores/Main';
  import DisplaySupplierInvoice from './DisplaySupplierInvoice.vue';
  export default {
    components: { DisplaySupplierInvoice },
    props:{
      supplier_invoice_data:Object
    },
    computed: {
      supplierInvoice: () => useSupplierInvoice(),
      main: () => useMain(),
      items: function () {
        if (this.supplierInvoice.display_mode === 'charge') {
          return this.supplier_invoice_data.charge_items || [];
        }
        return this.supplier_invoice_data.detail_items || [];
      },
      supplier_invoice_detail: function () {
        const sup = this.supplier_invoice_data.supplier || {};
        const rawAddress = sup.address || this.supplier_invoice_data.address;
        const formattedAddress = Array.isArray(rawAddress) ? rawAddress.filter(Boolean).join(', ') : (rawAddress || '');
        const city = sup.city || '';
        const fullAddress = [formattedAddress, city].filter(Boolean).join(' • ');

        return {
          id: this.supplier_invoice_data.id,
          date: this.supplier_invoice_data.date,
          tracking_numbers: this.supplier_invoice_data.tracking_numbers ? this.supplier_invoice_data.tracking_numbers.toString() : '',
          supplier_name: sup.name || this.supplier_invoice_data.supplier_name || this.supplier_invoice_data.supplier_ids || '',
          supplier_ids: sup.name || this.supplier_invoice_data.supplier_ids || '',
          address: fullAddress,
          city: city,
          country: sup.country || '',
          ice: sup.ice || '',
          phone: sup.phone || '',
          email: sup.email || '',
          total_price: this.supplier_invoice_data.total_price,
          total_weight: this.supplier_invoice_data.total_weight,
        };
      },
      chunkItems: function () {
        const result = [];
        for (let i = 0; i < this.items.length; i += this.supplierInvoice.final_page_rows_nbr) {
          result.push(this.items.slice(i, i + this.supplierInvoice.final_page_rows_nbr));
        }
        return result;
      },
      checkChunkLatestItems: function () {
        if (this.chunkItems.length === 0) return false;
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
      }
    }
  }
</script>
