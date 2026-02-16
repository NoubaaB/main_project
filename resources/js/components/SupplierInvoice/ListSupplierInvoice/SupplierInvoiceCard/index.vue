<template>
    <v-row>
        <v-col>
            <ChartCardOverview v-if="supplier_invoice.selectTypeChart == 'overview'"/>
            <ChartCardBySupplier v-if="supplier_invoice.selectTypeChart == 'by_supplier_invoice'"/>
        </v-col>
        <v-col cols="3" class="mx-0 px-0">
            <SideCard/>
        </v-col>
    </v-row>
</template>
    
<script>
    import ChartCardOverview from './ChartCardOverview.vue';
    import ChartCardBySupplier from './ChartCardBySupplier.vue';
    import SideCard from './SideCard.vue';
    import { useSupplierInvoice } from '@/stores/SupplierInvoice';
    
    export default {
        components:{
            ChartCardOverview,
            ChartCardBySupplier,
            SideCard
        },
        computed:{
            supplier_invoice : ()=>useSupplierInvoice()
        }
    }
</script>

<style lang="scss" scoped>

</style>