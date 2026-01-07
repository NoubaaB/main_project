<template>
    <div>
        <v-data-table-virtual
        :headers="detail_headers"
        :items="items"
        item-key="id"
        color="red"
        density="compact"
        class="table_size"
        >
            <template v-slot:[`item.total_weight`]="{ item }">
                {{ main.formatDisplayNumber(item.total_weight) }}
            </template>

            <template v-slot:[`item.total_price`]="{ item }">
                {{ main.formatDisplayNumber(item.total_price) }}
            </template>
            <template v-slot:[`item.unit_price`]="{ item }">
                {{ main.formatDisplayNumber(item.unit_price) }}
            </template>
            <template v-slot:[`item.total_package_count`]="{ item }">
                <v-chip prepend-icon="mdi-package-variant-closed" :color="item.total_package_count?'blue':main.not_aviable_color" class="ma-1">
                    {{ main.formatDisplayNumberNonFloat(item.total_package_count) }}
                </v-chip>
            </template>
            <template v-slot:[`item.package_type_id`]="{ item }">
                <v-chip prepend-icon="mdi-package-variant" :color="item.package_type_id?'success':main.not_aviable_color" class="ma-1">
                     {{ main.getPackageName(item.package_type_id) }} 
                </v-chip>
            </template>
            <template v-slot:[`item.classification`]="{ item }">
                <v-chip :color="item.classification.color" class="ma-1">
                     {{ item.classification.name }} 
                </v-chip>
            </template>
            <template v-slot:[`item.warehouse`]="{ item }">
                <v-chip v-if="item.warehouse != 'N/A'"  color="green" class="ma-1">
                    <v-icon class="mr-1">mdi-package-variant-closed-plus</v-icon> {{ item.warehouse }} 
                </v-chip>
                <v-chip v-else  :color="main.not_aviable_color" class="ma-1">
                    <v-icon class="mr-1">mdi-cube-off-outline</v-icon> <div v-html="$t('non_stockable_p')"></div> 
                </v-chip>
            </template>
            <template v-slot:[`item.product_family`]="{ item }">
                <v-chip v-if="item.product_family != 'N/A'"  color="green" class="ma-1">
                     {{ item.product_family }} 
                </v-chip>
                <v-chip v-else :color="main.not_aviable_color" class="ma-1">
                    <v-icon class="mr-1">mdi-circle-off-outline</v-icon> {{ $t("n/a") }} 
                </v-chip>
            </template>
        </v-data-table-virtual>
    </div>
</template>

<script>
    import { useMain } from '@/stores/Main';
    export default {
        props:{
            detail_headers:{ type: Array , required : true},
            item:{ type: Object , required : true}
        },
        computed:{
            main:()=>useMain(),
            items: function () {
                return [...this.warehouse_stockable_tracking_number_operations , ...this.non_warehouse_stockable_operations]
            },
            warehouse_stockable_tracking_number_operations: function () {
                return this.item.warehouse_stockable_tracking_number_operations.map(e => {
                    return {
                        id : e.id,
                        classification: {
                            name: this.$t(e.stockable_type),
                            color : this.main.getClassificationColor(e.stockable_type)
                        },
                        product: this.main.getStockableName(e.stockable_id, e.stockable_type),
                        product_family : this.main.getStockableFishFamily(e.stockable_id,e.stockable_type),
                        warehouse : this.main.getWarehouseName(e.warehouse_id),
                        total_weight:e.total_weight,
                        unit_price:e.unit_price,
                        total_price:e.total_price,
                        total_package_count:e.total_package_count,
                        package_type_id:e.package_type_id,
                    }
                })
            },
            non_warehouse_stockable_operations: function () {
                return this.item.non_warehouse_stockable_operations.map(e => {
                    return {
                        id : e.id,
                        classification: {
                            name: this.$t(e.stockable_type),
                            color : this.main.getClassificationColor(e.stockable_type)
                        },
                        product : this.main.getStockableName(e.stockable_id,e.stockable_type),
                        product_family : this.main.getStockableFishFamily(e.stockable_id,e.stockable_type),
                        warehouse : this.main.getWarehouseName(e.warehouse_id),
                        total_weight : e.total_weight,
                        unit_price : e.unit_price,
                        total_price :  e.total_price,
                        total_package_count: e.total_package_count,
                        package_type_id:e.package_type_id,
                    }
                })
            }
        },
        methods:{

        }
    }
</script>

<style  scoped>

.table_size{
    width: 86vw !important;
}
</style>