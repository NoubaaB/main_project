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
                <v-row class="mt-1">
                    <v-col cols="12">
                        <v-btn
                            rounded
                            block
                            variant="tonal"
                            color="#00838F"
                            prepend-icon="mdi-palette-swatch-outline"
                            @click="layoutCustomizerOpen = true"
                            class="font-weight-bold"
                        >
                            {{ $t('customize_invoice_layout') }}
                        </v-btn>
                    </v-col>
                </v-row>
                <v-divider opacity="0.2" variant="dashed" thickness="2" color="#00838F" class="my-5"></v-divider>
                <v-row>
                    <v-col>
                        <v-switch
                            v-model="clientInvoice.allow_qr_code"
                            :label="$t('show_qr_code_on_invoice')"
                            hide-details
                            color="#00838F"
                            inset
                        ></v-switch>
                        
                    </v-col>
                    <v-col>
                        <v-switch
                            v-model="clientInvoice.allow_bg_image"
                            :label="$t('show_background_image_on_invoice')"
                            hide-details
                            color="#00838F"
                            inset
                        ></v-switch>
                    </v-col>
                </v-row>
                <v-row v-if="clientInvoice.list.some(inv => inv.type_invoice === 'export') || (clientInvoice.items.length && clientInvoice.items[0]?.invoice?.type_invoice === 'export') || true">
                    <v-col>
                        <v-switch
                            v-model="clientInvoice.use_export_layout"
                            :label="$t('use_export_layout') || 'Use Export Layout'"
                            hide-details
                            color="#00838F"
                            inset
                        ></v-switch>
                    </v-col>
                    <v-col v-if="clientInvoice.use_export_layout">
                        <v-switch
                            v-model="clientInvoice.show_intermediary_info"
                            :label="$t('show_intermediary_info') || 'Show Intermediary Info'"
                            hide-details
                            color="#00838F"
                            inset
                        ></v-switch>
                    </v-col>
                </v-row>
                <v-divider opacity="0.2" variant="dashed" thickness="2" color="#00838F" class="my-5"></v-divider>
                <v-row>
                    <v-col>
                        <div class="text-caption text-grey-darken-1 mb-2 font-weight-bold">{{ $t('invoice_display_mode') }}</div>
                        <v-btn-toggle
                            v-model="displayMode"
                            mandatory
                            rounded="lg"
                            density="compact"
                            color="#00838F"
                            class="w-100"
                            variant="outlined"
                        >
                            <v-btn value="detail" class="flex-grow-1 text-none" size="small" prepend-icon="mdi-format-list-bulleted">
                                {{ $t('display_detail_mode') }}
                            </v-btn>
                            <v-btn value="charge" class="flex-grow-1 text-none" size="small" prepend-icon="mdi-file-document-outline">
                                {{ $t('display_shipping_mode') }}
                            </v-btn>
                        </v-btn-toggle>
                    </v-col>
                </v-row>
                <v-divider opacity="0.2" variant="dashed" thickness="2" color="#00838F" class="my-5"></v-divider>
                <v-row>
                    <v-col>
                        <v-autocomplete
                            v-model="clientInvoice.headers"
                            :items="clientInvoice.invoice_headers"
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
                            class="text-cyan-darken-3 header-filter"
                            return-object
                            >
                            <template v-slot:chip="{ props, item }">
                                <v-chip
                                v-bind="props"
                                color="#00838F"
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
                                class="text-cyan-darken-3"
                                :title="item.title"
                                color="#00838F"
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
                            class="text-cyan-darken-3 header-filter"
                            return-object
                            >
                            <template v-slot:chip="{ props, item }">
                                <v-chip
                                v-bind="props"
                                color="#00838F"
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
                                class="text-cyan-darken-3"
                                :title="$t(item.title)"
                                color="#00838F"
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
                <v-divider opacity="0.2" variant="dashed" thickness="2" color="#00838F" class="my-5"></v-divider>
                <v-row>
                    <v-col>
                        <v-chip prepend-icon="mdi-table-settings" color="#00838F">
                            {{ clientInvoice.invoices_length }} {{ $t("invoices") }}
                        </v-chip>
                    </v-col>
                    <v-col>
                        <v-chip prepend-icon="mdi-file-table" color="cyan-darken-4">
                            {{ clientInvoice.nbr_pages }} {{ $t("pages") }}
                        </v-chip>
                    </v-col>
                </v-row>
            </v-col>

            <!-- Invoice Layout Customizer Modal -->
            <InvoiceLayoutCustomizer
                v-model="layoutCustomizerOpen"
                invoiceType="client"
            />
        </v-row>
</template>

<script>
import { useClientInvoice } from '@/stores/ClientInvoice';
import print from 'vue3-print-nb'
import * as XLSX from 'xlsx';
import InvoiceLayoutCustomizer from '@/components/shared/InvoiceLayoutCustomizer.vue';

export default {
        directives: { print },
        components: {
            InvoiceLayoutCustomizer,
        },
        data() {
            return {
                layoutCustomizerOpen: false,
                printInvoice : {
                    id: "invoice",
                    closeCallback : (vue) => {
                        this.toPDF()
                        console.log('closeCallback')
                    },
                },
                groupByKeys: [],
            }
        },
        mounted() {
            this.clientInvoice.initHeaders();
        },
        computed: {
            clientInvoice: () => useClientInvoice(),
            displayMode: {
                get() { return this.clientInvoice.display_mode; },
                set(val) { this.clientInvoice.setDisplayMode(val); }
            },
            client_invoice: function () {
                return this.clientInvoice.getItems;
            },
            /* keys available for grouping */
            groupableKeys() {
                return this.clientInvoice.getHeaders.map(m=>m.key);
            },
            lines: function () {
                const mode = this.clientInvoice.display_mode;
                const key = mode === 'shipping' ? 'shipping_items' : 'detail_items';
                return this.client_invoice.flatMap(e => e[key] || []);
            },
            nbr_lines: function () {
                return this.lines.length
            },
            total_pages: function () {
                const result = [];
                for (let i = 0; i < this.nbr_lines; i += this.clientInvoice.final_page_rows_nbr) {
                    result.push(this.lines.slice(i, i + this.clientInvoice.final_page_rows_nbr));
                }
                return result.length;
            },
        },
        methods:{
            toPDF: function () {
                this.clientInvoice.is_show = !this.clientInvoice.is_show;
            },
            toEXCEL: function () {
                // defensive: ensure client_invoice is an array
                const invoices = Array.isArray(this.client_invoice)
                    ? _.cloneDeep(this.client_invoice)
                    : (this.client_invoice ? [_.cloneDeep(this.client_invoice)] : []);

                if (!invoices.length) {
                    console.warn('this.client_invoice is empty or undefined - nothing to export');
                    return;
                }

                // Determine which items key to use based on display mode
                const isDetailMode = this.clientInvoice.display_mode === 'detail';
                const itemsKey = isDetailMode ? 'detail_items' : 'shipping_items';

                // 1) compute invoice totals from items (safe Number conversions)
                invoices.forEach((inv) => {
                    const items = Array.isArray(inv[itemsKey]) ? inv[itemsKey] : [];

                    const totalWeight = items.reduce((acc, it) => {
                    const v = Number(it.total_weight ?? 0);
                    return acc + (Number.isFinite(v) ? v : 0);
                    }, 0);

                    const totalPrice = items.reduce((acc, it) => {
                    const w = Number(it.total_weight ?? 0);
                    const u = Number(it.unit_price ?? 0);
                    const itemTotal = Number(it.total_price ?? (w * u));
                    return acc + (Number.isFinite(itemTotal) ? itemTotal : 0);
                    }, 0);

                    inv.total_weight = totalWeight;
                    inv.total_price = totalPrice;
                });

                // 2) Flatten items into rows based on display mode
                const itemRows = _.flatMap(invoices, (inv) => {
                    const invoice_tracking_numbers = Array.isArray(inv.tracking_numbers) ? inv.tracking_numbers.join(', ') : (inv.tracking_numbers ?? '');
                    const client_name = Array.isArray(inv.client_name) ? inv.client_name.join(', ') : (inv.client_name ?? '');
                    const invoice_id = inv.id ?? '';
                    const invoice_date = inv.date ? moment(inv.date).format('YYYY-MM-DD') : '';

                    const items = Array.isArray(inv[itemsKey]) ? inv[itemsKey] : [];
                    return items.map((it, idx) => {
                        const base = {
                            invoice_id,
                            invoice_date,
                            invoice_tracking_numbers,
                            client_name,
                            date: it.date ? moment(it.date).format('YYYY-MM-DD') : '',
                            total_weight: Number(it.total_weight ?? 0),
                            unit_price: Number(it.unit_price ?? 0),
                            total_price: Number(it.total_price ?? ((Number(it.total_weight ?? 0)) * (Number(it.unit_price ?? 0)))),
                        };
                        if (isDetailMode) {
                            base.article_type = it.article_type ?? '';
                            base.license_plate = it.license_plate ?? '';
                            base.reception_tracking_number = it.reception_tracking_number ?? '';
                        } else {
                            base.source = it.source ?? '';
                            base.reception_tracking_number = it.reception_tracking_number ?? '';
                            base.category_name = it.category_name ?? '';
                        }
                        return base;
                    });
                });

                // 3) Build invoice summary rows (invoice-prefixed keys)
                const invoiceRows = invoices.map((inv) => ({
                    invoice_id: inv.id ?? '',
                    invoice_date: inv.date ? moment(inv.date).format('YYYY-MM-DD') : '',
                    invoice_tracking_numbers: Array.isArray(inv.tracking_numbers) ? inv.tracking_numbers.join(', ') : (inv.tracking_numbers ?? ''),
                    client_name: Array.isArray(inv.client_name) ? inv.client_name.join(', ') : (inv.client_name ?? ''),
                    total_weight: Number(inv.total_weight ?? 0),
                    total_price: Number(inv.total_price ?? 0)
                }));

                // 4) Keys / column order based on display mode
                const itemKeys = isDetailMode
                    ? [
                        'invoice_id', 'invoice_date', 'invoice_tracking_numbers', 'client_name',
                        'date', 'article_type', 'license_plate', 'reception_tracking_number',
                        'total_weight', 'unit_price', 'total_price'
                    ]
                    : [
                        'invoice_id', 'invoice_date', 'invoice_tracking_numbers', 'client_name',
                        'date', 'source', 'reception_tracking_number', 'category_name',
                        'total_weight', 'unit_price', 'total_price'
                    ];
                const invoiceKeys = ['invoice_id', 'invoice_date', 'invoice_tracking_numbers', 'client_name', 'total_weight', 'total_price'];

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

                const filename = `client_invoices_${moment().format('YYYYMMDD_HHmmss')}.xlsx`;
                XLSX.writeFile(wb, filename);

            },
            /* grouped & aggregated result */
            groupedItems() {
                if (!this.groupByKeys.length) {
                    this.clientInvoice.setGroupedItems([]);
                    this.clientInvoice.headers = this.clientInvoice.headers.filter(h=>h.key != this.clientInvoice.grouped_headers.key) 
                    return [];
                }
                let data = [];
                const itemsKey = this.clientInvoice.display_mode === 'shipping' ? 'shipping_items' : 'detail_items';
                this.clientInvoice.items.forEach(client_invoice => {
                    const grouped = _.groupBy(client_invoice[itemsKey] || [], (item) =>
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
                        id : client_invoice.id,
                        tracking_numbers: client_invoice.tracking_numbers,
                        client_name: client_invoice.client_name,
                        date: client_invoice.date,
                        total_price: client_invoice.total_price,
                        total_weight: client_invoice.total_weight,
                        items: _data
                    })
                });

                this.clientInvoice.setGroupedItems(data);
            },  
        },
        watch:{
            groupByKeys: {
                handler(newVal) {
                    this.groupedItems();
                },
                deep: true,
            },
            displayMode() {
                // Reset grouping when switching display modes
                this.groupByKeys = [];
                this.clientInvoice.setGroupedItems([]);
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
