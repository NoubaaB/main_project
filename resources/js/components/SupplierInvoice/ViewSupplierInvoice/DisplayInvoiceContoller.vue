<template>
        <v-row class="text-center controller-wrap border-dashed border-md rounded-lg border-info mt-2">
            <v-col>
                <v-row>
                    <v-col>
                        <v-btn v-print="printInvoice" rounded block @click="toPDF" prepend-icon="mdi-printer" color="red">{{$t("print")}}</v-btn>
                    </v-col>
                    <v-col>
                        <v-btn rounded block @click="toEXCEL" prepend-icon="mdi-microsoft-excel" color="green">{{$t("export_to_excel")}}</v-btn>
                    </v-col>
                </v-row>
                <v-divider opacity="0.2" variant="dashed" thickness="2" color="info" class="my-5"></v-divider>
                <v-row>
                    <v-col>
                        <v-switch
                            v-model="supplierInvoice.allow_qr_code"
                            :label="$t('show_qr_code_on_invoice')"
                            hide-details
                            color="info"
                            inset
                        ></v-switch>
                        
                    </v-col>
                    <v-col>
                        <v-switch
                            v-model="supplierInvoice.allow_bg_image"
                            :label="$t('show_background_image_on_invoice')"
                            hide-details
                            color="info"
                            inset
                        ></v-switch>
                    </v-col>
                    <v-col>
                        <v-switch
                            v-model="supplierInvoice.use_simple_invoice"
                            :label="$t('use_simple_invoice')"
                            hide-details
                            color="info"
                            inset
                        ></v-switch>
                    </v-col>
                </v-row>
                <v-divider opacity="0.2" variant="dashed" thickness="2" color="info" class="my-5"></v-divider>
                <v-row>
                    <v-col>
                        <v-autocomplete
                            v-model="supplierInvoice.headers"
                            :items="supplierInvoice.invoice_headers"
                            item-value="key"
                            item-title="title"
                            :label="$t('filter_invoice_headers')"
                            multiple
                            chips
                            closable-chips
                            clearable
                            density="compact"
                            hide-details
                            variant="outlined"
                            rounded
                            class="text-info header-filter"
                            return-object
                            >
                            <template v-slot:chip="{ props, item }">
                                <v-chip
                                v-bind="props"
                                class="text-white"
                                color="info"
                                closable
                                variant="tonal"
                                >
                                {{ item.title }}
                                </v-chip>
                            </template>
                            <template v-slot:item="{ props, item }">
                                <v-list-item
                                v-bind="props"
                                @click="props.onClick"
                                class="text-info"
                                :title="item.title"
                                color="info"
                                >
                                <template v-slot:prepend="{ isSelected, select }">
                                    <v-list-item-action start>
                                    <v-checkbox-btn :model-value="isSelected" @update:model-value="select"></v-checkbox-btn>
                                    </v-list-item-action>
                                </template>
                                </v-list-item>
                            </template>
                        </v-autocomplete>
                    </v-col>
                    <v-col>
                        <v-autocomplete
                            v-model="groupByKeys"
                            :items="groupableKeys"
                            :label="$t('group_invoice_headers')"
                            multiple
                            chips
                            closable-chips
                            clearable
                            density="compact"
                            hide-details
                            variant="outlined"
                            rounded
                            class="text-info header-filter"
                            return-object
                            >
                            <template v-slot:chip="{ props, item }">
                                <v-chip
                                v-bind="props"
                                class="text-white"
                                color="info"
                                closable
                                variant="tonal"
                                >
                                {{ $t(item.title) }}
                                </v-chip>
                            </template>
                            <template v-slot:item="{ props, item }">
                                <v-list-item
                                v-bind="props"
                                @click="props.onClick"
                                class="text-info"
                                :title="$t(item.title)"
                                color="info"
                                >
                                <template v-slot:prepend="{ isSelected, select }">
                                    <v-list-item-action start>
                                    <v-checkbox-btn :model-value="isSelected" @update:model-value="select"></v-checkbox-btn>
                                    </v-list-item-action>
                                </template>
                                </v-list-item>
                            </template>
                        </v-autocomplete>
                    </v-col>
                </v-row>
                <v-divider opacity="0.2" variant="dashed" thickness="2" color="info" class="my-5"></v-divider>
                <v-row>
                    <v-col>
                        <v-chip prepend-icon="mdi-table-settings" color="info">
                            {{ nbrRows }} {{ $t("row") }}
                        </v-chip>
                    </v-col>
                    <v-col>
                        <v-chip prepend-icon="mdi-file-table" color="indigo">
                            {{ pagination.total_pages }} {{ $t("pages") }}
                        </v-chip>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>
</template>

<script>
import { useSupplierInvoice } from '@/stores/SupplierInvoice';
import print from 'vue3-print-nb'

export default {
        directives: { print },
        props: {
            nbrRows: Number,
            pagination: Object,
            toPDF: Function,
            toEXCEL: Function
        },
        data() {
            return {
                printInvoice : {
                    id: "invoice",
                    priview: true,
                    previewTitle: 'print Title',
                    popTitle: 'good print',
                    closeCallback : (vue) => {
                        this.toPDF()
                        console.log('closeCallback')
                    },
                },
                groupByKeys: [],
            }
        },
        computed: {
            supplierInvoice: () => useSupplierInvoice(),
            items: function () {
                return this.supplierInvoice.getItems;
            },
            /* keys available for grouping */
            groupableKeys() {
                return this.supplierInvoice.getHeaders.map(m=>m.key);
            },
        },
        methods:{
            /* grouped & aggregated result */
            groupedItems() {
                console.log("in")
                if (!this.groupByKeys.length) {
                    this.supplierInvoice.setGroupedItems([]);
                    return [];
                }

                const grouped = _.groupBy(this.items, (item) =>
                    this.groupByKeys.map((k) => item[k]).join("||")
                );

                let data  = Object.values(grouped).map((group) => {
                    const base = {};

                    this.groupableKeys.forEach((k) => {
                    base[k] = group[0][k];
                    });

                    return {
                        ...base,
                        records_count: group.length,
                        total_weight: _.sumBy(group, "total_weight"),
                        total_price: _.sumBy(group, "total_price"),
                        unit_price: _.meanBy(group, "unit_price").toFixed(2),
                    };
                });

                this.supplierInvoice.setGroupedItems(data);
                return data;
            },  
        },
        watch:{
            groupByKeys: {
                handler(newVal) {
                    this.groupedItems();
                },
                deep: true,
            },
        }
    }
</script>

<style lang="scss" scoped>
    .controller-wrap{
        position: fixed;
        width: 32vw;
    }
</style>