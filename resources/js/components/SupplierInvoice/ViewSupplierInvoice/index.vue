<template>
  <supplier-invoice :invoice="invoice" :charges="charges" />
</template>

<script>
import SupplierInvoice from './DisplaySupplierInvoice.vue';
export default {
  components: { SupplierInvoice },
  data() {
    return {
      invoice: { id: 2500047, date: '2026-01-16', type_invoice: 'local', tracking_numbers: '["00001"]', supplier_ids: '[5]', charge_ids: [1,2], supplier_name: 'Poisson Export SARL' },
      charges: [
        { id:1, description: 'Fresh Anchovies', quantity: 200, unit: 'kg', unit_price: 11.75 },
        { id:2, description: 'Freight', quantity: 1, unit: 'service', unit_price: 500.0 }
      ]
    };
  }
}
</script>
