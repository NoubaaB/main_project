<template>
    <v-card class="luxury-controller-card pa-4" elevation="2">
        <!-- Header Section -->
        <div class="controller-header d-flex align-center justify-space-between pb-3 mb-3 border-b">
            <div class="d-flex align-center ga-2">
                <div class="header-icon-box" style="background: rgba(0, 131, 143, 0.12); color: #00838F;">
                    <v-icon size="20">mdi-tune-vertical</v-icon>
                </div>
                <div>
                    <div class="text-subtitle-2 font-weight-bold" style="letter-spacing: 0.2px; line-height: 1.2;">
                        {{ $t('invoice_controls') }}
                    </div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
                        {{ clientInvoice.invoices_length }} {{ $t('invoices') }} &bull; {{ clientInvoice.nbr_pages }} {{ $t('pages') }}
                    </div>
                </div>
            </div>
            <v-chip size="x-small" color="#00838F" variant="tonal" class="font-weight-bold">
                {{ $t('client_invoice') }}
            </v-chip>
        </div>

        <!-- Primary Action Buttons -->
        <div class="action-buttons-group mb-3">
            <v-row dense class="mb-2">
                <v-col cols="6">
                    <v-btn
                        v-print="printInvoice"
                        @click="toPDF"
                        rounded="lg"
                        block
                        class="print-btn text-none font-weight-bold"
                        prepend-icon="mdi-printer"
                        size="default"
                    >
                        {{ $t("print") }}
                    </v-btn>
                </v-col>
                <v-col cols="6">
                    <v-btn
                        @click="toEXCEL"
                        rounded="lg"
                        block
                        class="excel-btn text-none font-weight-bold"
                        prepend-icon="mdi-microsoft-excel"
                        size="default"
                    >
                        {{ $t("export_to_excel") }}
                    </v-btn>
                </v-col>
            </v-row>
            <v-btn
                rounded="lg"
                block
                variant="tonal"
                color="#00838F"
                prepend-icon="mdi-palette-swatch-outline"
                @click="layoutCustomizerOpen = true"
                class="font-weight-bold text-none customize-btn"
            >
                {{ $t('customize_invoice_layout') }}
            </v-btn>
        </div>

        <v-divider class="my-2 opacity-20"></v-divider>

        <!-- Invoice Display Mode (SegmentedPillToggle) -->
        <div class="controller-section mb-2">
            <div class="section-label d-flex align-center ga-2 mb-1">
                <v-icon size="15" color="#00838F">mdi-view-agenda-outline</v-icon>
                <span class="text-caption font-weight-bold text-uppercase" style="letter-spacing: 0.5px; color: #00838F; font-size: 11px !important;">
                    {{ $t('invoice_display_mode') }}
                </span>
            </div>
            <div class="d-flex justify-center w-100">
                <SegmentedPillToggle
                    v-model="displayMode"
                    :items="displayModeItems"
                    color="#00838F"
                    size="small"
                    class="w-100 justify-center"
                />
            </div>
        </div>

        <v-divider class="my-2 opacity-20"></v-divider>

        <!-- Display Options (Clean Switch Rows) -->
        <div class="controller-section mb-2">
            <div class="section-label d-flex align-center ga-2 mb-1">
                <v-icon size="15" color="#00838F">mdi-tune-variant</v-icon>
                <span class="text-caption font-weight-bold text-uppercase" style="letter-spacing: 0.5px; color: #00838F; font-size: 11px !important;">
                    {{ $t('display_options') }}
                </span>
            </div>

            <div class="options-container rounded-xl pa-2">
                <!-- QR Code Switch -->
                <div class="option-row d-flex align-center justify-space-between py-1 px-2 rounded-lg">
                    <div class="d-flex align-center ga-2 text-truncate mr-2">
                        <v-icon size="18" color="#00838F">mdi-qrcode</v-icon>
                        <span class="text-body-2 font-weight-medium">{{ $t('show_qr_code_on_invoice') }}</span>
                    </div>
                    <v-switch
                        v-model="clientInvoice.allow_qr_code"
                        hide-details
                        density="compact"
                        color="#00838F"
                        inset
                    ></v-switch>
                </div>

                <!-- Background Image Switch -->
                <div class="option-row d-flex align-center justify-space-between py-1 px-2 rounded-lg">
                    <div class="d-flex align-center ga-2 text-truncate mr-2">
                        <v-icon size="18" color="#00838F">mdi-image-outline</v-icon>
                        <span class="text-body-2 font-weight-medium">{{ $t('show_background_image_on_invoice') }}</span>
                    </div>
                    <v-switch
                        v-model="clientInvoice.allow_bg_image"
                        hide-details
                        density="compact"
                        color="#00838F"
                        inset
                    ></v-switch>
                </div>

                <!-- Export Layout Switch -->
                <div class="option-row d-flex align-center justify-space-between py-1 px-2 rounded-lg">
                    <div class="d-flex align-center ga-2 text-truncate mr-2">
                        <v-icon size="18" color="#00838F">mdi-export-variant</v-icon>
                        <span class="text-body-2 font-weight-medium">{{ $t('use_export_layout') }}</span>
                    </div>
                    <v-switch
                        v-model="clientInvoice.use_export_layout"
                        hide-details
                        density="compact"
                        color="#00838F"
                        inset
                    ></v-switch>
                </div>

                <!-- Intermediary Info Switch -->
                <div v-if="clientInvoice.use_export_layout" class="option-row d-flex align-center justify-space-between py-1 px-2 rounded-lg pl-4">
                    <div class="d-flex align-center ga-2 text-truncate mr-2">
                        <v-icon size="18" color="#00838F">mdi-domain</v-icon>
                        <span class="text-body-2 font-weight-medium">{{ $t('show_intermediary_info') }}</span>
                    </div>
                    <v-switch
                        v-model="clientInvoice.show_intermediary_info"
                        hide-details
                        density="compact"
                        color="#00838F"
                        inset
                    ></v-switch>
                </div>
            </div>
        </div>

        <v-divider class="my-2 opacity-20"></v-divider>

        <!-- Invoice Headers Filter & Grouping -->
        <div class="controller-section mb-2">
            <!-- Filter Invoice Headers -->
            <div class="mb-3">
                <div class="d-flex align-center justify-space-between mb-1">
                    <div class="d-flex align-center ga-1 text-caption font-weight-bold text-medium-emphasis">
                        <v-icon size="15" color="#00838F">mdi-table-headers-eye</v-icon>
                        <span>{{ $t('filter_invoice_headers') }}</span>
                    </div>
                    <span class="text-caption text-grey" style="font-size: 11px;">
                        {{ (clientInvoice.headers || []).length }}/{{ (clientInvoice.invoice_headers || []).length }}
                    </span>
                </div>
                <v-autocomplete
                    v-model="clientInvoice.headers"
                    :items="clientInvoice.invoice_headers"
                    item-value="key"
                    item-title="title"
                    multiple
                    chips
                    closable-chips
                    clearable
                    density="compact"
                    hide-details
                    variant="outlined"
                    rounded="lg"
                    color="#00838F"
                    class="header-filter-input"
                    return-object
                >
                    <template v-slot:chip="{ props, item }">
                        <v-chip
                            v-bind="props"
                            color="#00838F"
                            closable
                            variant="tonal"
                            size="small"
                            class="font-weight-medium my-1"
                        >
                            {{ item.title }}
                        </v-chip>
                    </template>
                    <template v-slot:item="{ props, item }">
                        <v-list-item
                            v-bind="props"
                            @click="props.onClick"
                            :title="item.title"
                            color="#00838F"
                        >
                            <template v-slot:prepend="{ isSelected, select }">
                                <v-list-item-action start>
                                    <v-checkbox-btn :model-value="isSelected" @update:model-value="select" color="#00838F"></v-checkbox-btn>
                                </v-list-item-action>
                            </template>
                        </v-list-item>
                    </template>
                </v-autocomplete>
            </div>

            <!-- Group Invoice Headers -->
            <div>
                <div class="d-flex align-center justify-space-between mb-1">
                    <div class="d-flex align-center ga-1 text-caption font-weight-bold text-medium-emphasis">
                        <v-icon size="15" color="#00838F">mdi-format-list-group</v-icon>
                        <span>{{ $t('group_invoice_headers') }}</span>
                    </div>
                    <v-chip v-if="groupByKeys.length" size="x-small" color="#00838F" variant="tonal" class="font-weight-bold">
                        {{ groupByKeys.length }}
                    </v-chip>
                </div>
                <v-autocomplete
                    v-model="groupByKeys"
                    :items="groupableKeys"
                    multiple
                    chips
                    closable-chips
                    clearable
                    density="compact"
                    hide-details
                    variant="outlined"
                    rounded="lg"
                    color="#00838F"
                    class="header-filter-input"
                    return-object
                >
                    <template v-slot:chip="{ props, item }">
                        <v-chip
                            v-bind="props"
                            color="#00838F"
                            closable
                            variant="tonal"
                            size="small"
                            class="font-weight-medium my-1"
                        >
                            {{ $t(item.title) }}
                        </v-chip>
                    </template>
                    <template v-slot:item="{ props, item }">
                        <v-list-item
                            v-bind="props"
                            @click="props.onClick"
                            :title="$t(item.title)"
                            color="#00838F"
                        >
                            <template v-slot:prepend="{ isSelected, select }">
                                <v-list-item-action start>
                                    <v-checkbox-btn :model-value="isSelected" @update:model-value="select" color="#00838F"></v-checkbox-btn>
                                </v-list-item-action>
                            </template>
                        </v-list-item>
                    </template>
                </v-autocomplete>
            </div>
        </div>

        <!-- Summary KPI Footer Tiles -->
        <div class="summary-kpi-row d-flex ga-2 pt-2 border-t mt-1">
            <div class="summary-kpi-tile pa-2 rounded-lg text-center flex-grow-1" style="background: rgba(0, 131, 143, 0.07); border: 1px solid rgba(0, 131, 143, 0.2);">
                <div class="d-flex align-center justify-center ga-1 text-caption text-medium-emphasis mb-1">
                    <v-icon size="14" color="#00838F">mdi-receipt-text-outline</v-icon>
                    <span>{{ $t('invoices') }}</span>
                </div>
                <div class="text-h6 font-weight-bold" style="color: #00838F; line-height: 1.2;">
                    {{ clientInvoice.invoices_length }}
                </div>
            </div>

            <div class="summary-kpi-tile pa-2 rounded-lg text-center flex-grow-1" style="background: rgba(0, 96, 100, 0.07); border: 1px solid rgba(0, 96, 100, 0.2);">
                <div class="d-flex align-center justify-center ga-1 text-caption text-medium-emphasis mb-1">
                    <v-icon size="14" color="#006064">mdi-book-open-page-variant-outline</v-icon>
                    <span>{{ $t('pages') }}</span>
                </div>
                <div class="text-h6 font-weight-bold" style="color: #006064; line-height: 1.2;">
                    {{ clientInvoice.nbr_pages }}
                </div>
            </div>
        </div>

        <!-- Invoice Layout Customizer Modal -->
        <InvoiceLayoutCustomizer
            v-model="layoutCustomizerOpen"
            invoiceType="client"
        />
    </v-card>
</template>

<script>
import { useClientInvoice } from '@/stores/ClientInvoice';
import print from 'vue3-print-nb';
import * as XLSX from 'xlsx';
import InvoiceLayoutCustomizer from '@/components/shared/InvoiceLayoutCustomizer.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import moment from 'moment';
import _ from 'lodash';

export default {
    name: 'ClientInvoiceController',
    directives: { print },
    components: {
        InvoiceLayoutCustomizer,
        SegmentedPillToggle,
    },
    data() {
        return {
            layoutCustomizerOpen: false,
            printInvoice: {
                id: "invoice",
                closeCallback: (vue) => {
                    this.toPDF();
                    console.log('closeCallback');
                },
            },
            groupByKeys: [],
        };
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
        displayModeItems() {
            return [
                { value: 'detail', label: this.$t('display_detail_mode'), icon: 'mdi-format-list-bulleted' },
                { value: 'shipping', label: this.$t('display_shipping_mode'), icon: 'mdi-package-variant-closed' }
            ];
        },
        client_invoice: function () {
            return this.clientInvoice.getItems;
        },
        /* keys available for grouping */
        groupableKeys() {
            return this.clientInvoice.getHeaders.map(m => m.key);
        },
        lines: function () {
            const mode = this.clientInvoice.display_mode;
            const key = mode === 'shipping' ? 'shipping_items' : 'detail_items';
            return this.client_invoice.flatMap(e => e[key] || []);
        },
        nbr_lines: function () {
            return this.lines.length;
        },
        total_pages: function () {
            const result = [];
            for (let i = 0; i < this.nbr_lines; i += this.clientInvoice.final_page_rows_nbr) {
                result.push(this.lines.slice(i, i + this.clientInvoice.final_page_rows_nbr));
            }
            return result.length;
        },
    },
    methods: {
        toPDF: function () {
            this.clientInvoice.is_show = !this.clientInvoice.is_show;
        },
        toEXCEL: function () {
            const invoices = Array.isArray(this.client_invoice)
                ? _.cloneDeep(this.client_invoice)
                : (this.client_invoice ? [_.cloneDeep(this.client_invoice)] : []);

            if (!invoices.length) {
                console.warn('this.client_invoice is empty or undefined - nothing to export');
                return;
            }

            const isDetailMode = this.clientInvoice.display_mode === 'detail';
            const itemsKey = isDetailMode ? 'detail_items' : 'shipping_items';

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

            const itemRows = _.flatMap(invoices, (inv) => {
                const invoice_tracking_numbers = Array.isArray(inv.tracking_numbers) ? inv.tracking_numbers.join(', ') : (inv.tracking_numbers ?? '');
                const client_name = Array.isArray(inv.client_name) ? inv.client_name.join(', ') : (inv.client_name ?? '');
                const invoice_id = inv.id ?? '';
                const invoice_date = inv.date ? moment(inv.date).format('YYYY-MM-DD') : '';

                const items = Array.isArray(inv[itemsKey]) ? inv[itemsKey] : [];
                return items.map((it) => {
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

            const invoiceRows = invoices.map((inv) => ({
                invoice_id: inv.id ?? '',
                invoice_date: inv.date ? moment(inv.date).format('YYYY-MM-DD') : '',
                invoice_tracking_numbers: Array.isArray(inv.tracking_numbers) ? inv.tracking_numbers.join(', ') : (inv.tracking_numbers ?? ''),
                client_name: Array.isArray(inv.client_name) ? inv.client_name.join(', ') : (inv.client_name ?? ''),
                total_weight: Number(inv.total_weight ?? 0),
                total_price: Number(inv.total_price ?? 0)
            }));

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

            const wsItems = XLSX.utils.json_to_sheet(itemRows, { header: itemKeys });
            const wsInvoices = XLSX.utils.json_to_sheet(invoiceRows, { header: invoiceKeys });

            const translateKey = (k) => {
                if (k.startsWith('invoice_')) return this.$t(k.replace(/^invoice_/, ''));
                return this.$t(k);
            };

            const setTranslatedHeaders = (ws, keys) => {
                for (let c = 0; c < keys.length; c++) {
                    const cellRef = XLSX.utils.encode_cell({ c, r: 0 });
                    ws[cellRef] = ws[cellRef] || { t: 's', v: '' };
                    ws[cellRef].v = String(translateKey(keys[c]) ?? keys[c]);
                    ws[cellRef].t = 's';
                }
            };

            setTranslatedHeaders(wsItems, itemKeys);
            setTranslatedHeaders(wsInvoices, invoiceKeys);

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

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, wsItems, 'Items');
            XLSX.utils.book_append_sheet(wb, wsInvoices, 'Invoices');

            const filename = `client_invoices_${moment().format('YYYYMMDD_HHmmss')}.xlsx`;
            XLSX.writeFile(wb, filename);
        },
        groupedItems() {
            if (!this.groupByKeys.length) {
                this.clientInvoice.setGroupedItems([]);
                this.clientInvoice.headers = this.clientInvoice.headers.filter(h => h.key != this.clientInvoice.grouped_headers.key);
                return [];
            }
            let data = [];
            const itemsKey = this.clientInvoice.display_mode === 'shipping' ? 'shipping_items' : 'detail_items';
            this.clientInvoice.items.forEach(client_invoice => {
                const grouped = _.groupBy(client_invoice[itemsKey] || [], (item) =>
                    this.groupByKeys.map((k) => item[k]).join("||")
                );

                let _data = Object.values(grouped).map((group) => {
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
                    id: client_invoice.id,
                    tracking_numbers: client_invoice.tracking_numbers,
                    client_name: client_invoice.client_name,
                    date: client_invoice.date,
                    total_price: client_invoice.total_price,
                    total_weight: client_invoice.total_weight,
                    items: _data
                });
            });

            this.clientInvoice.setGroupedItems(data);
        },
    },
    watch: {
        groupByKeys: {
            handler() {
                this.groupedItems();
            },
            deep: true,
        },
        displayMode() {
            this.groupByKeys = [];
            this.clientInvoice.setGroupedItems([]);
        },
    }
};
</script>

<style lang="scss" scoped>
.luxury-controller-card {
    position: sticky;
    top: 70px;
    max-height: calc(100vh - 85px);
    overflow-y: auto;
    overflow-x: hidden;
    border-radius: 18px !important;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    background: rgba(var(--v-theme-surface), 0.98);
    backdrop-filter: blur(12px);
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.06), 0 2px 6px -1px rgba(0, 0, 0, 0.04) !important;
    transition: box-shadow 0.25s ease, border-color 0.25s ease;

    &:hover {
        border-color: rgba(0, 131, 143, 0.25);
        box-shadow: 0 8px 28px rgba(0, 131, 143, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04) !important;
    }

    /* Sleek custom scrollbar */
    &::-webkit-scrollbar {
        width: 5px;
    }
    &::-webkit-scrollbar-track {
        background: transparent;
    }
    &::-webkit-scrollbar-thumb {
        background: rgba(0, 131, 143, 0.2);
        border-radius: 999px;
    }
    &::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 131, 143, 0.4);
    }
}

.header-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.print-btn {
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%) !important;
    color: #FFFFFF !important;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.28);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(220, 38, 38, 0.38);
    }
}

.excel-btn {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%) !important;
    color: #FFFFFF !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.28);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.38);
    }
}

.customize-btn {
    border: 1px solid rgba(0, 131, 143, 0.25) !important;
    transition: all 0.2s ease;
    &:hover {
        background: rgba(0, 131, 143, 0.12) !important;
    }
}

.options-container {
    background: rgba(var(--v-theme-on-surface), 0.025);
    border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.option-row {
    transition: background 0.18s ease;
    &:hover {
        background: rgba(0, 131, 143, 0.05);
    }
}

.summary-kpi-tile {
    transition: transform 0.18s ease;
    &:hover {
        transform: translateY(-1px);
    }
}

@media print {
    .luxury-controller-card {
        display: none !important;
    }
}
</style>
