<template>
        <v-row class="text-center controller-wrap border-dashed border-md rounded-lg border-info mt-2">
            <v-col>
                <v-row>
                    <v-col>
                        <v-btn v-print="printInvoice" rounded block @click="toPDF" prepend-icon="mdi-printer" color="red">{{$t("print")}}</v-btn>
                    </v-col>
                    <v-col>
                        <v-btn
                            rounded
                            block
                            @click="toEXCEL"
                            prepend-icon="mdi-microsoft-excel"
                            color="green">{{$t("export_to_excel")}}
                        </v-btn>
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
                            {{ invoices_length }} {{ $t("invoices") }}
                        </v-chip>
                    </v-col>
                    <v-col>
                        <v-chip prepend-icon="mdi-file-table" color="indigo">
                            {{ supplierInvoice.nbr_pages }} {{ $t("pages") }}
                        </v-chip>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>
</template>

<script>
import { useSupplierInvoice } from '@/stores/SupplierInvoice';
import print from 'vue3-print-nb'
import * as XLSX from 'xlsx';

export default {
        directives: { print },
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
            supplier_invoice: function () {
                return this.supplierInvoice.getItems;
            },
            /* keys available for grouping */
            groupableKeys() {
                return this.supplierInvoice.getHeaders.map(m=>m.key);
            },
            invoices_length: function () {
                return this.supplier_invoice.length;
            },
            lines: function () {
                return this.supplier_invoice.flatMap(e=>e.items)
            },
            nbr_lines: function () {
                return this.lines.length
            },
            total_pages: function () {
                const result = [];
                for (let i = 0; i < this.nbr_lines; i += this.supplierInvoice.final_page_rows_nbr) {
                    result.push(this.lines.slice(i, i + this.supplierInvoice.final_page_rows_nbr));
                }
                return result.length;
            },
        },
        methods:{
            toPDF: function () {
                this.is_show = !this.is_show;
            },
            toEXCEL: function () {
                // defensive: ensure supplier_invoice is an array
                const invoices = Array.isArray(this.supplier_invoice)
                    ? _.cloneDeep(this.supplier_invoice)
                    : (this.supplier_invoice ? [_.cloneDeep(this.supplier_invoice)] : []);

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
            /* grouped & aggregated result */
            groupedItems() {
                if (!this.groupByKeys.length) {
                    this.supplierInvoice.setGroupedItems([]);
                    this.supplierInvoice.headers = this.supplierInvoice.headers.filter(h=>h.key != this.supplierInvoice.grouped_headers.key) 
                    return [];
                }
                let data = [];
                this.supplierInvoice.items.forEach(supplier_invoice => {
                    const grouped = _.groupBy(supplier_invoice.items, (item) =>
                        this.groupByKeys.map((k) => item[k]).join("||")
                    );
    
                    let _data  = Object.values(grouped).map((group) => {
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
                    data.push({
                        id : supplier_invoice.id,
                        tracking_numbers: supplier_invoice.tracking_numbers,
                        supplier_ids: supplier_invoice.supplier_ids,
                        date: supplier_invoice.date,
                        total_price: supplier_invoice.total_price,
                        total_weight: supplier_invoice.total_weight,
                        items: _data
                    })
                });

                this.supplierInvoice.setGroupedItems(data);
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