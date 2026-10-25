<template>
    <div 
    class="invoice-wrap pa-0 _invoice_page"
    :class="{'border-dashed border-md border-info':is_show}"
    :style="invoiceWrapCustomStyle"
    >
        <div class="header-wrap">
            <v-row no-gutters>
                <v-col cols="12" class="header-wrap-strick" :style="{ backgroundColor: layout.header_primary_color || '#01579B', height: layout.header_strick_height || '0.3cm' }">
                </v-col>
            </v-row>
            <v-row no-gutters class="header-wrap-divided" :style="{ background: headerDividedBg, height: layout.header_divided_height || '3cm' }">
                <v-col cols="4" class="text-center-h-v pl-3" :style="{ backgroundColor: layout.header_primary_color || '#01579B' }">
                    <img class="header-img" :style="{ width: layout.header_logo_width || '6cm' }" :src="invoiceHeaderImage"/>
                </v-col>
                <template v-if="layout.header_style === 'curved' || !layout.header_style">
                    <v-col cols="2" class="header-cursev-dark" :style="{ backgroundColor: layout.header_primary_color || '#01579B' }"></v-col>
                    <v-col cols="2" class="header-cursev-light" :style="{ backgroundColor: layout.header_bg_color || '#FFFFFF' }"></v-col>
                </template>
                <template v-else-if="layout.header_style === 'slant'">
                    <v-col cols="3" class="header-slant-dark" :style="{ backgroundColor: layout.header_primary_color || '#01579B' }"></v-col>
                    <v-col cols="1" class="header-slant-light" :style="{ backgroundColor: layout.header_bg_color || '#FFFFFF' }"></v-col>
                </template>
                <template v-else-if="layout.header_style === 'straight'">
                    <v-col cols="auto" style="width: 14px; background: rgba(0,0,0,0.12);"></v-col>
                </template>
                <template v-else-if="layout.header_style === 'rounded'">
                    <v-col cols="2" style="border-top-right-radius: 40px; border-bottom-right-radius: 40px;" :style="{ backgroundColor: layout.header_primary_color || '#01579B' }"></v-col>
                    <v-col cols="1"></v-col>
                </template>
                <template v-else-if="layout.header_style === 'minimal'">
                    <v-col cols="auto" style="width: 4px; background: rgba(0,0,0,0.08);"></v-col>
                </template>
                <v-col cols="*" class="text-center-h-v invoice-title" :style="{ backgroundColor: layout.header_bg_color || '#FFFFFF', color: layout.header_text_color || '#01579B', fontSize: layoutTitleFontSize }">
                    {{ layout.header_title_text || $t('invoice') }}
                </v-col>
            </v-row>
        </div>
        <!-- HEADER -->

        <!-- UNIFIED MIDDLE BODY SECTION (Matching Live Preview) -->
        <div class="invoice-middle-body" :style="contentWrapCustomStyle">
            <img class="invoice-body-watermark" :src="companyBackgroundWatermark" v-if="supplierInvoice.allow_bg_image"/>

            <!-- TITLE / META GRID -->
            <div class="title-wrap">
                <div class="meta-grid-card">
                    <!-- Left: Supplier / Partner Information -->
                    <div class="meta-entity-section">
                        <div class="meta-header-row">
                            <span class="meta-role-pill" :style="{ backgroundColor: invoicePrimaryColor + '18', color: invoicePrimaryColor }">
                                <v-icon size="11" class="mr-1">mdi-truck-delivery-outline</v-icon>
                                {{ $t('supplier') }}
                            </span>
                            <span class="meta-entity-name" :title="supplierDisplayName">
                                {{ supplierDisplayName }}
                            </span>
                        </div>

                        <div class="meta-detail-row" v-if="supplierAddressFormatted">
                            <v-icon size="12" class="meta-detail-icon">mdi-map-marker-outline</v-icon>
                            <span class="meta-detail-text">{{ supplierAddressFormatted }}</span>
                        </div>

                        <div class="meta-subdetail-row">
                            <span v-if="supplier_invoice_detail.ice" class="meta-tag-item">
                                <strong class="meta-tag-key">ICE:</strong> {{ supplier_invoice_detail.ice }}
                            </span>
                            <span v-if="supplier_invoice_detail.ice && supplier_invoice_detail.phone" class="meta-dot-divider">•</span>
                            <span v-if="supplier_invoice_detail.phone" class="meta-tag-item">
                                <v-icon size="11" class="meta-mini-icon">mdi-phone-outline</v-icon> {{ supplier_invoice_detail.phone }}
                            </span>
                            <span v-if="companyNameFormatted" class="meta-counterparty-box">
                                <span class="meta-counterparty-label">{{ $t('billed_to') }}:</span>
                                <span class="meta-counterparty-value">{{ companyNameFormatted }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Right: Invoice Tracking, Date, and QR / Seal -->
                    <div class="meta-ref-section">
                        <div class="meta-ref-pill" :style="{ backgroundColor: invoiceSecondaryColor }">
                            <div class="meta-ref-pill-left">
                                <span class="meta-ref-label">{{ $t('invoice_n°') }}</span>
                                <strong class="meta-ref-value">{{ supplier_invoice_detail.tracking_numbers }}</strong>
                            </div>
                            <div class="meta-ref-pill-divider"></div>
                            <div class="meta-ref-pill-right">
                                <v-icon size="13" color="#ffffff" class="mr-1">mdi-calendar-month-outline</v-icon>
                                <span class="meta-ref-date">{{ main.formateDate(supplier_invoice_detail.date) }}</span>
                            </div>
                        </div>

                        <!-- Clean QR code frame or official security badge -->
                        <div class="meta-qr-container" v-if="supplierInvoice.allow_qr_code">
                            <div class="meta-qr-frame" v-html="qr_code_generate"></div>
                        </div>
                        <div class="meta-official-seal" v-else :style="{ borderColor: invoicePrimaryColor + '35' }">
                            <v-icon size="14" :color="invoicePrimaryColor" class="mr-1">mdi-shield-check-outline</v-icon>
                            <span class="meta-seal-text" :style="{ color: invoicePrimaryColor }">{{ $t('official_document') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- TITLE / META GRID -->

            <!-- INVOICE TABLE CONTAINER (Clean Live Preview Design) -->
            <div class="invoice-table-container">
                <div class="table-scroll">
                    <table class="invoice-table" role="table">
                        <thead>
                            <tr role="row" class="text-white" :style="{ backgroundColor: invoicePrimaryColor }">
                                <th
                                    class="col-header text-center col-index"
                                    style="width: 38px; min-width: 38px; max-width: 45px;"
                                >
                                    <strong class="header-text">#</strong>
                                </th>
                                <th
                                    v-for="(h, idx) in displayedHeaders"
                                    :key="h.key"
                                    :draggable="true"
                                    class="col-header"
                                    @dragstart="onHeaderDragStart($event, idx)"
                                    @dragover="onHeaderDragOver($event, idx)"
                                    @dragleave="onHeaderDragLeave($event, idx)"
                                    @drop="onHeaderDrop($event, idx)"
                                    :class="[
                                        'col-header',
                                        { dragging: draggingIndex === idx, 'drag-over': dragOverIndex === idx },
                                        isNumericKey(h.key) ? 'text-right' : (h.align === 'center' || h.key === 'date' ? 'text-center' : 'text-left')
                                    ]"
                                    :style="{ width: (columnWidths[h.key] || defaultWidth) + 'px', textAlign: isNumericKey(h.key) ? 'right' : (h.align || 'left') }"
                                >
                                    <div class="col-header-inner" :class="{ 'justify-end': isNumericKey(h.key), 'justify-center': h.align === 'center' || h.key === 'date' }">
                                        <span class="header-sort-trigger" @click="toggleSort(h.key)">
                                            <strong class="header-text">{{ h.title }}</strong>

                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                class="invoice-data-row ma-0 pa-0"
                                v-for="(row, rIdx) in sortedItems"
                                :key="rIdx"
                                role="row"
                                :style="{ backgroundColor: rIdx % 2 === 1 ? 'rgba(248, 250, 252, 0.82)' : 'rgba(255, 255, 255, 0.82)' }"
                            >
                                <td
                                    class="text-center col-index text-grey-darken-1 font-weight-medium"
                                    style="width: 38px; min-width: 38px; max-width: 45px;"
                                >
                                    {{ getLineNumber(rIdx) }}
                                </td>
                                <td
                                    v-for="h in displayedHeaders"
                                    :key="h.key"
                                    :class="[
                                        isNumericKey(h.key) ? 'text-right' : (h.align === 'center' || h.key === 'date' ? 'text-center' : 'text-left'),
                                        {
                                            'font-weight-bold text-grey-darken-4': h.key === 'total_price',
                                            'font-weight-bold text-grey-darken-3': !isNumericKey(h.key) && h.key !== 'date',
                                            'font-weight-medium text-grey-darken-2': (isNumericKey(h.key) && h.key !== 'total_price') || h.key === 'date'
                                        }
                                    ]"
                                    :style="{ width: (columnWidths[h.key] || defaultWidth) + 'px', textAlign: isNumericKey(h.key) ? 'right' : (h.align || 'left') }"
                                >
                                    <template v-if="h.key === 'total_price'">
                                        <span class="cell-content font-weight-bold text-grey-darken-4">
                                            {{ formatCell(row, h.key) }} {{ main.currency || 'MAD' }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="cell-content">
                                            {{ formatCell(row, h.key) }}
                                        </span>
                                    </template>
                                </td>
                            </tr>

                            <!-- Subtle zebra filler rows to maintain clean A4 pagination without cyan notebook lines -->
                            <tr
                                class="invoice-empty-row ma-0 pa-0"
                                v-for="n in emptyRows"
                                :key="'empty-'+n"
                                :style="{ backgroundColor: (sortedItems.length + n - 1) % 2 === 1 ? 'rgba(248, 250, 252, 0.82)' : 'rgba(255, 255, 255, 0.82)' }"
                            >
                                <td class="text-center col-index text-transparent" style="width: 38px; min-width: 38px; max-width: 45px;">&nbsp;</td>
                                <td v-for="h in displayedHeaders" :key="h.key + '-e'">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- END: Invoice table -->

            <!-- SUMMARY CARD (Matching Live Preview) -->
            <div class="invoice-summary-card" v-if="allow_summary">
                <div class="words-pill" :style="{ borderColor: invoicePrimaryColor + '70' }">
                    <span class="words-label" :style="{ color: invoicePrimaryColor }">
                        {{ $t('present invoice') }} :
                    </span>
                    <div class="words-divider" :style="{ borderColor: invoicePrimaryColor }"></div>
                    <span class="words-value" :style="{ color: invoicePrimaryColor }">
                        {{ convertSumPriceToWord }}
                    </span>
                </div>
                <div class="totals-pill-wrap">
                    <div class="totals-pill-weight" :style="{ backgroundColor: invoiceSecondaryColor }">
                        <span class="pill-label">{{ $t('total_weight') }}:</span>
                        <strong class="pill-value">{{ total.weight }} Kg</strong>
                    </div>
                    <div class="totals-pill-price" :style="{ backgroundColor: invoicePrimaryColor }">
                        <span class="pill-label">{{ $t('total_price') }}:</span>
                        <strong class="pill-value">{{ total.price }} {{ main.currency || 'Dh(s)' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Clean pagination chip -->
            <div v-if="pagination && pagination.allow" class="pagination-wrap">
                <v-chip variant="tonal" size="x-small" color="grey">
                    <span class="text-grey-darken-1">{{ pagination.page }} / {{ pagination.total_pages }}</span>
                </v-chip>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer-wrap">
            <v-row no-gutters class="footer-wrap-divided" :style="{ background: footerDividedBg, height: layout.footer_divided_height || '3cm' }">
                <v-col v-if="layout.footer_show_director !== false" cols="4" class="text-center px-8 invoice-director" :style="{ backgroundColor: layout.footer_bg_color || '#FFFFFF', color: layout.footer_text_color || '#01579B' }">
                    <div class="mb-2" :style="{ fontSize: layoutDirectorFontSize }">
                        {{ layout.footer_director_title || $t("director") }}
                    </div>
                    <v-divider opacity=".7" thickness="2" class="mb-8" :style="{ borderColor: (layout.footer_primary_color || '#01579B') + ' !important' }"></v-divider>
                    <v-divider v-if="layout.footer_line_style !== 'none'" opacity=".5" thickness="2" :variant="layout.footer_line_style || 'dashed'" class="mx-5" :style="{ borderColor: (layout.footer_secondary_color || '#4FC3F7') + ' !important' }"></v-divider>
                </v-col>
                <template v-if="layout.footer_style === 'curved' || !layout.footer_style">
                    <v-col cols="2" class="footer-cursev-light" :style="{ backgroundColor: layout.footer_bg_color || '#FFFFFF' }"></v-col>
                    <v-col cols="2" class="footer-cursev-dark" :style="{ backgroundColor: layout.footer_primary_color || '#01579B' }"></v-col>
                </template>
                <template v-else-if="layout.footer_style === 'slant'">
                    <v-col cols="1" class="footer-slant-light" :style="{ backgroundColor: layout.footer_bg_color || '#FFFFFF' }"></v-col>
                    <v-col cols="3" class="footer-slant-dark" :style="{ backgroundColor: layout.footer_primary_color || '#01579B' }"></v-col>
                </template>
                <template v-else-if="layout.footer_style === 'straight'">
                    <v-col cols="auto" style="width: 14px; background: rgba(0,0,0,0.12);"></v-col>
                </template>
                <template v-else-if="layout.footer_style === 'rounded'">
                    <v-col cols="1"></v-col>
                    <v-col cols="2" style="border-top-left-radius: 40px; border-bottom-left-radius: 40px;" :style="{ backgroundColor: layout.footer_primary_color || '#01579B' }"></v-col>
                </template>
                <template v-else-if="layout.footer_style === 'minimal'">
                    <v-col cols="auto" style="width: 4px; background: rgba(0,0,0,0.08);"></v-col>
                </template>
                <v-col cols="*" class="text-center-h-v pr-3" :style="{ backgroundColor: layout.footer_primary_color || '#01579B' }">
                    <img class="header-img footer-img" :style="{ width: layout.header_logo_width || '6cm', maxWidth: '100%', maxHeight: '100%', objectFit: 'contain' }" :src="invoiceHeaderImage"/>
                </v-col>
            </v-row>
            <v-row no-gutters>
                <v-col cols="12" class="footer-wrap-strick" :style="{ backgroundColor: layout.footer_primary_color || '#01579B', height: layout.footer_strick_height || '0.3cm' }">
                </v-col>
            </v-row>
        </div>
    </div>
</template>

<script>
    import { useTruck } from '@/stores/Truck';
    import { useMain } from '@/stores/Main';
    import { useSupplierInvoice } from '@/stores/SupplierInvoice';
    import { useCompanySettings } from '@/stores/CompanySettings';
    import writtenNumber from 'written-number';
    import { QRCodeSVG } from '@cheprasov/qrcode';
    import { useInvoiceLayout } from '@/stores/InvoiceLayout';

    export default {
        props:{
          items : Array,  
          supplier_invoice_detail : Object,  
          pagination : Object,  
          total : Object,  
          emptyRows : Number,  
          pageIndex : Number,  
          allow_summary : Boolean,
          add_pages : Function,  
        },
        data() {
            return {
                // column order and widths
                columnOrder: [],
                columnWidths: {},

                // sorting
                sortKey: null,
                sortDesc: false,

                // header-drag state
                draggingIndex: null,
                dragOverIndex: null,

                // resizing state
                isResizing: false,
                resizingKey: null,
                resizeStartX: 0,
                resizeStartWidth: 0,
                _mouseMoveHandler: null,
                _mouseUpHandler: null,

                // config
                defaultWidth: 90,   // fallback column width in px
            }
        },
        mounted() {
            this.invoiceLayout.initSync();
            if (!this.company_settings?.settings?.company_name && !this.company_settings?.settings?.invoice_header_image_url) {
                this.company_settings?.fetchSettings?.();
            }
            // initialize widths/order on mount
            if (typeof this.initColumns === 'function') this.initColumns();
            // defensive global mouseup (stops drag/resizing if mouse released outside)
            this._globalMouseUp = (e) => {
                if (this.isResizing) this.onResizeMouseUp(e);
                this.draggingIndex = null;
                this.dragOverIndex = null;
            };
            window.addEventListener('mouseup', this._globalMouseUp);
        },
        beforeUnmount() {
            if (this._mouseMoveHandler) window.removeEventListener('mousemove', this._mouseMoveHandler);
            if (this._mouseUpHandler) window.removeEventListener('mouseup', this._mouseUpHandler);
            if (this._globalMouseUp) window.removeEventListener('mouseup', this._globalMouseUp);
        },
        computed: {
            truck: () => useTruck(),
            main: () => useMain(),
            supplierInvoice: () => useSupplierInvoice(),
            company_settings: () => useCompanySettings(),
            invoiceLayout: () => useInvoiceLayout(),
            invoiceHeaderImage: function () {
                const cs = this.company_settings?.settings || {};
                if (cs.invoice_header_image_url) return cs.invoice_header_image_url;
                if (cs.invoice_header_image_path) return `/storage/${cs.invoice_header_image_path}`;
                return '/storage/images/invoice_title.png';
            },
            layout: function () {
                return this.invoiceLayout.getLayout('supplier');
            },
            invoicePrimaryColor: function () {
                return this.layout.header_primary_color || '#01579B';
            },
            invoiceSecondaryColor: function () {
                return this.layout.header_secondary_color || '#4FC3F7';
            },
            invoiceWrapCustomStyle: function () {
                const l = this.layout;
                return {
                    '--header-wrap-divided-height': l.header_divided_height || '3cm',
                    '--header-wrap-strick-height': l.header_strick_height || '0.3cm',
                    '--footer-wrap-divided-height': l.footer_divided_height || '3cm',
                    '--footer-wrap-strick-height': l.footer_strick_height || '0.3cm',
                    '--invoice-primary-color': this.invoicePrimaryColor,
                    '--invoice-secondary-color': this.invoiceSecondaryColor,
                };
            },
            contentWrapCustomStyle: function () {
                const l = this.layout;
                const hD = l.header_divided_height || '3cm';
                const hS = l.header_strick_height || '0.3cm';
                const fD = l.footer_divided_height || '3cm';
                const fS = l.footer_strick_height || '0.3cm';
                return {
                    height: `calc(var(--invoice-height) - (${hD}) - (${fD}) - (${hS}) - (${fS}))`,
                    position: 'relative',
                };
            },
            headerDividedBg: function () {
                const l = this.layout;
                if (l.header_style === 'gradient') {
                    return `linear-gradient(135deg, ${l.header_primary_color || '#01579B'} 0%, ${l.header_secondary_color || '#4FC3F7'} 100%)`;
                }
                return l.header_secondary_color || '#4FC3F7';
            },
            footerDividedBg: function () {
                const l = this.layout;
                if (l.footer_style === 'gradient') {
                    return `linear-gradient(135deg, ${l.footer_primary_color || '#01579B'} 0%, ${l.footer_secondary_color || '#4FC3F7'} 100%)`;
                }
                return l.footer_secondary_color || '#4FC3F7';
            },
            layoutTitleFontSize: function () {
                const map = {
                    'medium': '1.5rem',
                    'large': '2rem',
                    'x-large': '2.4rem',
                    'xx-large': '3rem',
                    'xxx-large': 'xxx-large',
                };
                return map[this.layout.header_title_size] || this.layout.header_title_size || 'xxx-large';
            },
            layoutDirectorFontSize: function () {
                const map = {
                    'medium': '1.1rem',
                    'large': '1.3rem',
                    'x-large': 'x-large',
                    'xx-large': '1.8rem',
                };
                return map[this.layout.footer_director_size] || this.layout.footer_director_size || 'x-large';
            },
            is_show: function () {
                return this.supplierInvoice.is_show;
            },
            supplierDisplayName: function () {
                return this.supplier_invoice_detail?.supplier_name || this.supplier_invoice_detail?.supplier_ids || this.$t('supplier');
            },
            supplierAddressFormatted: function () {
                return this.supplier_invoice_detail?.address || '';
            },
            companyNameFormatted: function () {
                return this.company_settings?.settings?.company_name || '';
            },
            companyBackgroundWatermark: function () {
                const cs = this.company_settings?.settings || {};
                if (cs.logo_invoice_body_url) return cs.logo_invoice_body_url;
                if (cs.logo_invoice_url) return cs.logo_invoice_url;
                if (cs.logo_invoice_body_path) return `/storage/${cs.logo_invoice_body_path}`;
                if (cs.logo_invoice_path) return `/storage/${cs.logo_invoice_path}`;
                return '/storage/images/invoice_body.png';
            },
            qr_code_generate: function () {
                const qrSVG = new QRCodeSVG(this.supplier_invoice_detail?.tracking_numbers || '0001', {
                    level: 'H',
                    padding: 0,
                    fgColor: this.invoicePrimaryColor || '#01579B',
                    image: {
                        source: '/storage/images/invoice_body.png',
                        width: '20%',
                        height: '20%',
                        x: 'center',
                        y: 'center',
                    },
                });
                return qrSVG.toString();
            },
            convertSumPriceToWord: function () {
                let number = this.total._price;
                // compute using total Subunit to avoid subunit === 100 edge-case
                const totalSubunit = Math.round(Math.abs(number) * 100);
                const unit = Math.floor(totalSubunit / 100);
                const subunit = totalSubunit % 100;

                // build words with written-number (keeps user's lang)
                const unitWords = writtenNumber(unit, { lang: this.main.lang.code });

                let formattedWords = '';
                // if no centimes, return only the unit part
                if (subunit === 0) {
                    formattedWords = `${this.main.capitalize(unitWords)} ${this.main.currency_unit}(s)`.trim();
                } else {
                    const subunitWords = writtenNumber(subunit, { lang: this.main.lang.code });
                    formattedWords = `${this.main.capitalize(unitWords)} ${this.main.currency_unit}(s) ${this.$t('and')} ${this.main.capitalize(subunitWords)} ${this.main.currency_subunit}(s)`.trim();
                }

                return formattedWords;
            },
            // displayedHeaders: respects visibility and columnOrder (uses this.supplierInvoice.getHeaders)
            displayedHeaders() {
                const visible = (this.supplierInvoice.getHeaders || []).filter(h => h.visible !== false);
                if (!this.columnOrder || this.columnOrder.length === 0) {
                this.columnOrder = visible.map(h => h.key);
                }
                const byKey = visible.reduce((acc, h) => { acc[h.key] = h; return acc; }, {});
                const mapped = [];
                this.columnOrder.forEach(k => { if (byKey[k]) mapped.push(byKey[k]); });
                visible.forEach(h => { if (!this.columnOrder.includes(h.key)) mapped.push(h); });
                return mapped;
            },

            // sortedItems: sort by sortKey / sortDesc (uses this.items)
            sortedItems() {
                const arr = (this.items || []).slice();
                if (!this.sortKey) return arr;
                const key = this.sortKey;
                const desc = this.sortDesc;
                arr.sort((a, b) => {
                const va = this.getCellValue(a, key);
                const vb = this.getCellValue(b, key);

                // numeric compare
                if (typeof va === 'number' && typeof vb === 'number') return desc ? vb - va : va - vb;
                const na = Number(va), nb = Number(vb);
                if (!isNaN(na) && !isNaN(nb)) return desc ? nb - na : na - nb;

                // ISO date-ish compare
                if (typeof va === 'string' && typeof vb === 'string' &&
                    /^\d{4}-\d{2}-\d{2}/.test(va) && /^\d{4}-\d{2}-\d{2}/.test(vb)) {
                    return desc ? vb.localeCompare(va) : va.localeCompare(vb);
                }

                const sa = (va === null || va === undefined) ? '' : String(va);
                const sb = (vb === null || vb === undefined) ? '' : String(vb);
                return desc ? sb.localeCompare(sa) : sa.localeCompare(sb);
                });
                return arr;
            }

        },
        methods:{
            initColumns() {
                const visibleKeys = (this.supplierInvoice.getHeaders || []).filter(h => h.visible !== false).map(h => h.key);
                if (!this.columnOrder || this.columnOrder.length === 0) this.columnOrder = [...visibleKeys];
                visibleKeys.forEach(k => { if (!this.columnWidths[k]) this.columnWidths[k] = this.defaultWidth; });
            },

            // helper to support nested keys 'parent.child'
            getCellValue(row, key) {
                const parts = String(key).split('.');
                let cur = row;
                for (const p of parts) {
                if (cur === null || cur === undefined) return null;
                cur = cur[p];
                }
                return cur;
            },
            // number formatting: thousands (space) + 2 decimals
            formatNumber(n) {
                if (n === null || n === undefined || n === '') return '';
                const num = Number(n);
                if (isNaN(num)) return String(n);
                const s = num.toFixed(2);
                const parts = s.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                return parts.join('.');
            },

            isNumericKey(key) {
                // tweak predicate to match your numeric column keys
                const numericCandidates = ['quantity','qty','unit_price','total_price','total','amount','weight','montant','pu'];
                return numericCandidates.includes(key) || /price|qty|amount|weight|montant|pu/i.test(key);
            },

            formatCell(row, key) {
                const v = this.getCellValue(row, key);
                // Format date columns using main date format
                if (key === 'date' && v) return this.main.formateDate(v);
                if (this.isNumericKey(key)) return this.formatNumber(v);
                if (Array.isArray(v)) return v.join(', ');
                if (v && typeof v === 'object') {
                if ('name' in v) return v.name;
                if ('stockable_id' in v) return v.stockable_id;
                return JSON.stringify(v);
                }
                return v !== undefined && v !== null ? v : '';
            },

            // sorting toggle
            toggleSort(key) {
                if (this.sortKey === key) this.sortDesc = !this.sortDesc;
                else { this.sortKey = key; this.sortDesc = false; }
            },

            // drag-to-reorder handlers (HTML5 drag)
            onHeaderDragStart(e, index) {
                this.draggingIndex = index;
                try { e.dataTransfer.setData('text/plain', String(index)); } catch (err) {}
                e.dataTransfer.effectAllowed = 'move';
            },
            onHeaderDragOver(e, index) {
                e.preventDefault();
                if (this.dragOverIndex !== index) this.dragOverIndex = index;
            },
            onHeaderDragLeave(e, index) {
                if (this.dragOverIndex === index) this.dragOverIndex = null;
            },
            onHeaderDrop(e, index) {
                e.preventDefault();
                const from = this.draggingIndex !== null ? this.draggingIndex : parseInt(e.dataTransfer.getData('text/plain') || -1, 10);
                const to = index;
                if (from === -1 || from === null) { this.draggingIndex = null; this.dragOverIndex = null; return; }
                const keys = this.displayedHeaders.map(h => h.key);
                const keyFrom = keys[from];
                const keyTo = keys[to];
                if (keyFrom && keyTo && keyFrom !== keyTo) {
                const current = [...this.columnOrder];
                const removeIdx = current.indexOf(keyFrom);
                if (removeIdx !== -1) current.splice(removeIdx, 1);
                const insertIdx = Math.max(0, current.indexOf(keyTo));
                current.splice(insertIdx, 0, keyFrom);
                this.columnOrder = current;
                }
                this.draggingIndex = null;
                this.dragOverIndex = null;
            },

            // resizing logic
            onResizeMouseDown(e, key) {
                this.isResizing = true;
                this.resizingKey = key;
                this.resizeStartX = e.clientX;
                this.resizeStartWidth = Number(this.columnWidths[key] || this.defaultWidth);
                this._mouseMoveHandler = (ev) => { this.onResizeMouseMove(ev); };
                this._mouseUpHandler = (ev) => { this.onResizeMouseUp(ev); };
                window.addEventListener('mousemove', this._mouseMoveHandler);
                window.addEventListener('mouseup', this._mouseUpHandler);
            },
            onResizeMouseMove(e) {
                if (!this.isResizing || !this.resizingKey) return;
                const delta = e.clientX - this.resizeStartX;
                const newWidth = Math.max(60, Math.round(this.resizeStartWidth + delta));
                // reactive assign
                this.columnWidths = Object.assign({}, this.columnWidths, { [this.resizingKey]: newWidth });
            },
            onResizeMouseUp() {
                this.isResizing = false;
                this.resizingKey = null;
                if (this._mouseMoveHandler) { window.removeEventListener('mousemove', this._mouseMoveHandler); this._mouseMoveHandler = null; }
                if (this._mouseUpHandler) { window.removeEventListener('mouseup', this._mouseUpHandler); this._mouseUpHandler = null; }
            },

            getLineNumber(rIdx) {
                const page = (this.pageIndex !== undefined && this.pageIndex !== null)
                    ? this.pageIndex
                    : (this.pagination && this.pagination.page ? this.pagination.page - 1 : 0);
                const rowsPerPage = this.supplierInvoice?.final_page_rows_nbr || 35;
                return (page * rowsPerPage) + rIdx + 1;
            },

            // utility
            resetColumnOrder() {
                this.columnOrder = (this.supplierInvoice.getHeaders || []).filter(h => h.visible !== false).map(h => h.key);
            }

        }
    }
</script>

<style scoped >
    .invoice-middle-body {
        padding: 12px 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background-color: #ffffff;
        border-top: 1px solid #e2e8f0;
        box-sizing: border-box;
        position: relative;
        overflow: hidden;
    }

    .invoice-wrap {
        height: var(--invoice-height);
        width: var(--invoice-width);
        overflow: hidden;
        background-color: white;
        size: a4;
    }

    .title-wrap {
        width: 100%;
        margin-bottom: 10px;
        position: relative;
        box-sizing: border-box;
        flex-shrink: 0;
    }

    .header-wrap-strick{
        height: var(--header-wrap-strick-height);
    }
    .header-wrap-divided{
        height: var(--header-wrap-divided-height);
    }
    .header-cursev-dark{
        clip-path: polygon(100% 0%, -1% -1% , -1.00% 100.00%, 1.00% 99.98%, 2.00% 99.90%, 3.00% 99.78%, 4.00% 99.61%, 5.00% 99.38%, 6.00% 99.11%, 7.00% 98.80%, 8.00% 98.43%, 9.00% 98.01%, 10.00% 97.55%, 11.00% 97.04%, 12.00% 96.49%, 13.00% 95.89%, 14.00% 95.24%, 15.00% 94.55%, 16.00% 93.82%, 17.00% 93.04%, 18.00% 92.22%, 19.00% 91.35%, 20.00% 90.45%, 21.00% 89.51%, 22.00% 88.53%, 23.00% 87.51%, 24.00% 86.45%, 25.00% 85.36%, 26.00% 84.23%, 27.00% 83.07%, 28.00% 81.87%, 29.00% 80.65%, 30.00% 79.39%, 31.00% 78.10%, 32.00% 76.79%, 33.00% 75.45%, 34.00% 74.09%, 35.00% 72.70%, 36.00% 71.29%, 37.00% 69.86%, 38.00% 68.41%, 39.00% 66.94%, 40.00% 65.45%, 41.00% 63.95%, 42.00% 62.43%, 43.00% 60.91%, 44.00% 59.37%, 45.00% 57.82%, 46.00% 56.27%, 47.00% 54.71%, 48.00% 53.14%, 49.00% 51.57%, 50.00% 50.00%, 51.00% 48.43%, 52.00% 46.86%, 53.00% 45.29%, 54.00% 43.73%, 55.00% 42.18%, 56.00% 40.63%, 57.00% 39.09%, 58.00% 37.57%, 59.00% 36.05%, 60.00% 34.55%, 61.00% 33.06%, 62.00% 31.59%, 63.00% 30.14%, 64.00% 28.71%, 65.00% 27.30%, 66.00% 25.91%, 67.00% 24.55%, 68.00% 23.21%, 69.00% 21.90%, 70.00% 20.61%, 71.00% 19.35%, 72.00% 18.13%, 73.00% 16.93%, 74.00% 15.77%, 75.00% 14.64%, 76.00% 13.55%, 77.00% 12.49%, 78.00% 11.47%, 79.00% 10.49%, 80.00% 9.55%, 81.00% 8.65%, 82.00% 7.78%, 83.00% 6.96%, 84.00% 6.18%, 85.00% 5.45%, 86.00% 4.76%, 87.00% 4.11%, 88.00% 3.51%, 89.00% 2.96%, 90.00% 2.45%, 91.00% 1.99%, 92.00% 1.57%, 93.00% 1.20%, 94.00% 0.89%, 95.00% 0.62%, 96.00% 0.39%, 97.00% 0.22%, 98.00% 0.10%, 99.00% 0.02%, 100.00% 0.00%);
    }
    .header-cursev-light{
        clip-path: polygon(115% 108%, 26% 101% , -9% 119.38%, 77% 120.35%, -0.5% 100.27%, 4% 99.15%, 4.5% 98.97%, 5% 98.74%, 6% 98.47%, 7% 98.14%, 8% 97.76%, 9.00% 97.34%, 10.00% 96.87%, 11.00% 96.35%, 12.00% 95.78%, 13.00% 95.16%, 14.00% 94.50%, 15.00% 93.79%, 16.00% 93.04%, 17.00% 92.24%, 18.00% 91.40%, 19.00% 90.51%, 20.00% 89.59%, 21.00% 88.62%, 22.00% 87.61%, 23.00% 86.57%, 24.00% 85.48%, 25.00% 84.36%, 26.00% 83.21%, 27.00% 82.02%, 28.00% 80.79%, 29.00% 79.54%, 30.00% 78.25%, 31.00% 76.93%, 32.00% 75.59%, 33.00% 74.21%, 34.00% 72.81%, 35.00% 71.39%, 36.00% 69.95%, 37.00% 68.48%, 38.00% 66.99%, 39.00% 65.49%, 40.00% 63.96%, 41.00% 62.42%, 42.00% 60.87%, 43.00% 59.30%, 44.00% 57.73%, 45.00% 56.14%, 46.00% 54.55%, 47.00% 52.95%, 48.00% 51.34%, 49.00% 49.73%, 50.00% 48.12%, 51.00% 46.52%, 52.00% 44.91%, 53.00% 43.30%, 54.00% 41.70%, 55.00% 40.11%, 56.00% 38.52%, 57.00% 36.95%, 58.00% 35.38%, 59.00% 33.83%, 60.00% 32.29%, 61.00% 30.76%, 62.00% 29.26%, 63.00% 27.77%, 64.00% 26.30%, 65.00% 24.86%, 66.00% 23.44%, 67.00% 22.04%, 68.00% 20.66%, 69.00% 19.32%, 70.00% 18.00%, 71.00% 16.71%, 72.00% 15.46%, 73.00% 14.23%, 74.00% 13.04%, 75.00% 11.89%, 76.00% 10.77%, 77.00% 9.68%, 78.00% 8.64%, 79.00% 7.63%, 80.00% 6.66%, 81.00% 5.74%, 82.00% 4.85%, 83.00% 4.01%, 84.00% 3.21%, 85.00% 2.46%, 86.00% 1.75%, 87.00% 1.09%, 88.00% 0.47%, 89.00% -0.10%, 90.00% -0.62%, 91.00% -1.09%, 92.00% -1.51%, 93.00% -1.89%, 94.00% -2.22%, 95.00% -2.49%, 96.00% -2.72%, 97.00% -2.90%, 98.00% -3.02%, 99.00% -3.10%, 100.00% -3.13%);
    }
    .header-slant-dark{
        clip-path: polygon(0 0, 100% 0, 45% 100%, 0 100%);
    }
    .header-slant-light{
        clip-path: polygon(45% 0, 100% 0, 100% 100%, 0 100%);
    }
    .footer-slant-light{
        clip-path: polygon(0 0, 100% 0, 55% 100%, 0 100%);
    }
    .footer-slant-dark{
        clip-path: polygon(55% 0, 100% 0, 100% 100%, 0 100%);
    }
    .header-img{
        width: 6cm;
    }


    .footer-wrap-strick{
        height: var(--header-wrap-strick-height);
    }
    .footer-wrap-divided{
        height: var(--header-wrap-divided-height);
    }
    .footer-cursev-dark{
        clip-path: polygon(109% 158%, -4% 101% , -7% 101.14%, 3% 99.35%, 3% 99.27%, 3% 99.15%, 4% 98.97%, 5% 98.74%, 6% 98.47%, 7% 98.14%, 8% 97.76%, 9.00% 97.34%, 10.00% 96.87%, 11.00% 96.35%, 12.00% 95.78%, 13.00% 95.16%, 14.00% 94.50%, 15.00% 93.79%, 16.00% 93.04%, 17.00% 92.24%, 18.00% 91.40%, 19.00% 90.51%, 20.00% 89.59%, 21.00% 88.62%, 22.00% 87.61%, 23.00% 86.57%, 24.00% 85.48%, 25.00% 84.36%, 26.00% 83.21%, 27.00% 82.02%, 28.00% 80.79%, 29.00% 79.54%, 30.00% 78.25%, 31.00% 76.93%, 32.00% 75.59%, 33.00% 74.21%, 34.00% 72.81%, 35.00% 71.39%, 36.00% 69.95%, 37.00% 68.48%, 38.00% 66.99%, 39.00% 65.49%, 40.00% 63.96%, 41.00% 62.42%, 42.00% 60.87%, 43.00% 59.30%, 44.00% 57.73%, 45.00% 56.14%, 46.00% 54.55%, 47.00% 52.95%, 48% 51.34%, 49.00% 49.73%, 50.00% 48.12%, 51.00% 46.52%, 52.00% 44.91%, 53% 43.30%, 54.00% 41.70%, 55.00% 40.11%, 56.00% 38.52%, 57.00% 36.95%, 58.00% 35.38%, 59.00% 33.83%, 60.00% 32.29%, 61.00% 30.76%, 62.00% 29.26%, 63.00% 27.77%, 64.00% 26.30%, 65.00% 24.86%, 66.00% 23.44%, 67.00% 22.04%, 68.00% 20.66%, 69.00% 19.32%, 70.00% 18.00%, 71.00% 16.71%, 72.00% 15.46%, 73.00% 14.23%, 74.00% 13.04%, 75.00% 11.89%, 76.00% 10.77%, 77.00% 9.68%, 78.00% 8.64%, 79.00% 7.63%, 80.00% 6.66%, 81.00% 5.74%, 82.00% 4.85%, 83.00% 4.01%, 84.00% 3.21%, 85.00% 2.46%, 86.00% 1.75%, 87.00% 1.09%, 88.00% 0.47%, 89.00% -0.10%, 90.00% -0.62%, 91.00% -1.09%, 92.00% -1.51%, 93.00% -1.89%, 94.00% -2.22%, 95.00% -2.49%, 96.00% -2.72%, 98% 0%, 100% 0%, 99% 0%, 114% 0%);
    }
    .footer-cursev-light{
        clip-path: polygon(100% 0%, -3% -41% , -3% 101%, 1.00% 99.98%, 2.00% 100.9%, 3.00% 99.78%, 4.00% 99.61%, 5.00% 99.38%, 6.00% 99.11%, 7.00% 98.80%, 8.00% 98.43%, 9.00% 98.01%, 10.00% 97.55%, 11.00% 97.04%, 12.00% 96.49%, 13.00% 95.89%, 14.00% 95.24%, 15.00% 94.55%, 16.00% 93.82%, 17.00% 93.04%, 18.00% 92.22%, 19.00% 91.35%, 20.00% 90.45%, 21.00% 89.51%, 22.00% 88.53%, 23.00% 87.51%, 24.00% 86.45%, 25.00% 85.36%, 26.00% 84.23%, 27.00% 83.07%, 28.00% 81.87%, 29.00% 80.65%, 30.00% 79.39%, 31.00% 78.10%, 32.00% 76.79%, 33.00% 75.45%, 34.00% 74.09%, 35.00% 72.70%, 36.00% 71.29%, 37.00% 69.86%, 38.00% 68.41%, 39.00% 66.94%, 40.00% 65.45%, 41.00% 63.95%, 42.00% 62.43%, 43.00% 60.91%, 44.00% 59.37%, 45.00% 57.82%, 46.00% 56.27%, 47.00% 54.71%, 48.00% 53.14%, 49.00% 51.57%, 50.00% 50.00%, 51.00% 48.43%, 52.00% 46.86%, 53.00% 45.29%, 54.00% 43.73%, 55.00% 42.18%, 56.00% 40.63%, 57.00% 39.09%, 58.00% 37.57%, 59.00% 36.05%, 60.00% 34.55%, 61.00% 33.06%, 62.00% 31.59%, 63.00% 30.14%, 64.00% 28.71%, 65.00% 27.30%, 66.00% 25.91%, 67.00% 24.55%, 68.00% 23.21%, 69.00% 21.90%, 70.00% 20.61%, 71.00% 19.35%, 72.00% 18.13%, 73.00% 16.93%, 74.00% 15.77%, 75.00% 14.64%, 76.00% 13.55%, 77.00% 12.49%, 78.00% 11.47%, 79.00% 10.49%, 80.00% 9.55%, 81.00% 8.65%, 82.00% 7.78%, 83.00% 6.96%, 84.00% 6.18%, 85.00% 5.45%, 86.00% 4.76%, 87.00% 4.11%, 88.00% 3.51%, 89.00% 2.96%, 90.00% 2.45%, 91.00% 1.99%, 92.00% 1.57%, 93.00% 1.20%, 94.00% 0.89%, 95.00% 0.62%, 96.00% 0.39%, 97.00% 0.22%, 98.00% 0.10%, 100% -1.98%, 100.00% 0.00%)
    }

    .text-center-h-v{
        display: flex;        /* 1. Activates the flexbox layout */
        justify-content: center; /* 2. Centers content horizontally along the main axis */
        align-items: center;     /* 3. Centers content vertically along the cross axis */
    }

    .anti-slash{
        font-family: math;
        font-size: xxx-large;
        font-style: italic;
    }

    .invoice-title{
        font-weight: 900;
        font-size: xxx-large;
        text-transform: uppercase;
    }

    .invoice-director{
        font-weight: 600;
        font-family: sans-serif;
        font-size: x-large;
    }

    .invoice-middle-body {
        position: relative;
        overflow: hidden;
    }

    .invoice-body-watermark {
        opacity: 0.2;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        max-width: 60%;
        max-height: 60%;
        object-fit: contain;
        pointer-events: none;
        z-index: 10;
    }

    .meta-grid-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 8px 14px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-left: 5px solid var(--invoice-primary-color, #01579B);
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
        box-sizing: border-box;
    }

    .meta-entity-section {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
    }

    .meta-header-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: nowrap;
    }

    .meta-role-pill {
        display: inline-flex;
        align-items: center;
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        flex-shrink: 0;
        line-height: 1.2;
    }

    .meta-entity-name {
        font-size: 13.5px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: 0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }

    .meta-detail-row {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #475569;
        font-size: 11px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }

    .meta-detail-icon {
        color: #64748b;
        flex-shrink: 0;
    }

    .meta-detail-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .meta-subdetail-row {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        color: #64748b;
        flex-wrap: nowrap;
        overflow: hidden;
        line-height: 1.2;
    }

    .meta-tag-item {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        flex-shrink: 0;
    }

    .meta-tag-key {
        font-weight: 700;
        color: #334155;
    }

    .meta-dot-divider {
        color: #cbd5e1;
        font-weight: bold;
    }

    .meta-mini-icon {
        color: #94a3b8;
    }

    .meta-counterparty-box {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 1px 6px;
        background: rgba(241, 245, 249, 0.9);
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 9.5px;
        margin-left: 4px;
        flex-shrink: 0;
    }

    .meta-counterparty-label {
        color: #64748b;
        font-weight: 500;
    }

    .meta-counterparty-value {
        color: #1e293b;
        font-weight: 700;
    }

    .meta-ref-section {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .meta-ref-pill {
        display: flex;
        align-items: center;
        border-radius: 6px;
        padding: 4px 10px;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        gap: 8px;
    }

    .meta-ref-pill-left {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
    }

    .meta-ref-label {
        font-size: 9px;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.88);
        font-weight: 600;
    }

    .meta-ref-value {
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.03em;
        color: #ffffff;
    }

    .meta-ref-pill-divider {
        width: 1px;
        height: 22px;
        background: rgba(255, 255, 255, 0.35);
    }

    .meta-ref-pill-right {
        display: flex;
        align-items: center;
        font-size: 11px;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
    }

    .meta-qr-container {
        flex-shrink: 0;
    }

    .meta-qr-frame {
        width: 48px;
        height: 48px;
        padding: 3px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;

        :deep(svg) {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }
    }

    .meta-official-seal {
        display: flex;
        align-items: center;
        padding: 4px 8px;
        border: 1px dashed;
        border-radius: 5px;
        background: rgba(255, 255, 255, 0.85);
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .capitalize-text {
        text-transform: capitalize !important;
        font-family: sans-serif !important;
    }

/* Table Container matching Live Preview */
.invoice-table-container {
    flex: 1 1 auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.88);
    display: flex;
    flex-direction: column;
    position: relative;
    z-index: 1;
    box-sizing: border-box;
}

.table-scroll {
    overflow: hidden;
    width: 100%;
    height: 100%;
    background: transparent;
}

table.invoice-table {
    border-collapse: collapse;
    width: 100%;
    table-layout: fixed;
    font-family: inherit;
    font-size: 11px;
}

table.invoice-table thead th {
    position: relative;
    user-select: none;
    vertical-align: middle;
    color: #ffffff;
    font-weight: 700;
    letter-spacing: 0.03rem;
    padding: 5px 8px;
    white-space: nowrap;
    overflow: hidden;
    border-right: 1px solid rgba(255, 255, 255, 0.15);
}
table.invoice-table thead th:last-child {
    border-right: none;
}

.col-header-inner {
    display: inline-flex;
    align-items: center;
    width: 100%;
}

.header-sort-trigger {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.sort-indicator {
    opacity: 0.95;
    margin-left: 2px;
}

.drag-handle {
    cursor: grab;
    padding: 2px 4px;
    opacity: 0.8;
    user-select: none;
}

.resize-handle {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 8px;
    cursor: col-resize;
    z-index: 2;
}

/* Clean Body Rows */
table.invoice-table tbody tr.invoice-data-row,
table.invoice-table tbody tr.invoice-empty-row {
    box-sizing: border-box;
    transition: background-color 0.15s ease-in-out;
}

table.invoice-table tbody tr.invoice-data-row:hover td {
    background-color: #f1f5f9 !important;
}

table.invoice-table tbody td {
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    height: 21px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #334155;
    box-sizing: border-box;
    line-height: 1.25;
}

.col-index {
    width: 38px !important;
    min-width: 38px !important;
    max-width: 45px !important;
}

.cell-content {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.25;
    padding-right: 8px;
}

/* Empty filler rows */
table.invoice-table tbody tr.invoice-empty-row td {
    border-bottom: 1px solid #e2e8f0;
    height: 21px;
    box-sizing: border-box;
    line-height: 1.25;
}

/* Summary Card matching Live Preview */
.invoice-summary-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    width: 100%;
    z-index: 1;
    position: relative;
    flex-shrink: 0;
    box-sizing: border-box;
}

.words-pill {
    flex: 1 1 auto;
    padding: 6px 14px;
    border-radius: 8px;
    border: 1px dashed;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    min-height: 42px;
    box-sizing: border-box;
}

.words-label {
    font-size: 11px;
    font-weight: 500;
    line-height: 1.25;
    text-align: center;
    width: 100%;
}

.words-divider {
    width: 100%;
    border-top: 1px dashed;
    margin: 3px 0 4px 0;
    opacity: 0.45;
}

.words-value {
    font-size: 11.5px;
    font-weight: 700;
    line-height: 1.25;
    text-align: center;
    width: 100%;
}

.totals-pill-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 185px;
    flex-shrink: 0;
}

.totals-pill-weight,
.totals-pill-price {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 10px;
    border-radius: 6px;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.pill-label {
    font-size: 10.5px;
    font-weight: 600;
    opacity: 0.95;
    text-transform: capitalize;
}

.pill-value {
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: 0.02em;
    font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
    font-variant-numeric: tabular-nums !important;
    -webkit-font-feature-settings: "tnum" 1 !important;
    font-feature-settings: "tnum" 1 !important;
}

.pagination-wrap {
    display: flex;
    justify-content: flex-end;
    margin-top: 4px;
    flex-shrink: 0;
}
</style>