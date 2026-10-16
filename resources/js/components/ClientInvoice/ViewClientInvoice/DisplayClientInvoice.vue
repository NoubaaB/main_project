<template>
    <div 
    class="invoice-wrap pa-0 _invoice_page"
    :class="{'border-dashed border-md border-cyan-darken-3':is_show, 'export-invoice-wrap': isExport}"
    :style="invoiceWrapCustomStyle"
    >
        <img class="export-watermark" :src="'/storage/images/invoice_title.png'" v-if="isExport" />
        <div class="header-wrap">
            <v-row no-gutters>
                <v-col cols="12" class="header-wrap-strick" :style="{ backgroundColor: layout.header_primary_color || '#006064', height: layout.header_strick_height || '0.3cm' }">
                </v-col>
            </v-row>
            <v-row no-gutters class="header-wrap-divided" :style="{ background: headerDividedBg, height: layout.header_divided_height || '3cm' }">
                <v-col cols="4" class="text-center-h-v pl-3" :style="{ backgroundColor: layout.header_primary_color || '#006064' }">
                    <img class="header-img" :style="{ width: layout.header_logo_width || '6cm' }" :src="'/storage/images/invoice_title.png'"/>
                </v-col>
                <template v-if="layout.header_style === 'curved' || !layout.header_style">
                    <v-col cols="2" class="header-cursev-dark" :style="{ backgroundColor: layout.header_primary_color || '#006064' }"></v-col>
                    <v-col cols="2" class="header-cursev-light" :style="{ backgroundColor: layout.header_bg_color || '#FFFFFF' }"></v-col>
                </template>
                <template v-else-if="layout.header_style === 'slant'">
                    <v-col cols="3" class="header-slant-dark" :style="{ backgroundColor: layout.header_primary_color || '#006064' }"></v-col>
                    <v-col cols="1" class="header-slant-light" :style="{ backgroundColor: layout.header_bg_color || '#FFFFFF' }"></v-col>
                </template>
                <template v-else-if="layout.header_style === 'straight'">
                    <v-col cols="auto" style="width: 14px; background: rgba(0,0,0,0.12);"></v-col>
                </template>
                <template v-else-if="layout.header_style === 'rounded'">
                    <v-col cols="2" style="border-top-right-radius: 40px; border-bottom-right-radius: 40px;" :style="{ backgroundColor: layout.header_primary_color || '#006064' }"></v-col>
                    <v-col cols="1"></v-col>
                </template>
                <template v-else-if="layout.header_style === 'minimal'">
                    <v-col cols="auto" style="width: 4px; background: rgba(0,0,0,0.08);"></v-col>
                </template>
                <v-col cols="*" class="text-center-h-v invoice-title" :style="{ backgroundColor: layout.header_bg_color || '#FFFFFF', color: layout.header_text_color || '#006064', fontSize: layoutTitleFontSize }">
                    {{ layout.header_title_text || $t('invoice') }}
                </v-col>
            </v-row>
        </div>
        <!-- HEADER -->
        <div class="title-wrap">
            <v-row no-gutters>
                <v-col>
                    <v-row >
                        <v-col class="text-center d-flex" :style="{ color: invoicePrimaryColor }">
                            <div class="ml-5 chevron" :style="{ backgroundColor: invoicePrimaryColor }" v-for="n in 10" :key="n" ></div>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col class="ml-5">
                            <v-row no-gutters>
                                <v-col class="text-black">
                                    <h3>
                                        {{ $t('company') }} : {{company_settings.settings.company_name}}
                                    </h3>
                                </v-col>
                            </v-row>
                            <v-row no-gutters>
                                <v-col class="text-black">
                                    <strong>
                                        {{ $t("address") }} : {{company_settings.settings.company_address}}
                                    </strong>
                                </v-col>
                            </v-row>
                        </v-col>
                    </v-row>
                </v-col>
                <v-col >
                    <v-row class="text-center-h-v text-center rounded-s-xl text-white" :style="{ backgroundColor: invoiceSecondaryColor }">
                        <v-col>
                            <h4>
                                {{$t('invoice_n°') }}:
                                 {{ client_invoice_detail.tracking_numbers }}
                            </h4>
                        </v-col>
                            <v-divider vertical class="mx-0 px-0 my-1" :style="{ borderColor: 'rgba(255,255,255,0.4) !important' }" thickness="2" opacity="1"></v-divider>
                        <v-col cols="5">
                            <h4>
                                {{ $t("date") }} :
                                 {{ main.formateDate(client_invoice_detail.date) }}
                            </h4>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col class="text-center d-flex" :style="{ color: invoicePrimaryColor }">
                            <div cols="1" class="text-center ml-3" v-if="clientInvoice.allow_qr_code">
                                <div class="corner-only" v-html="qr_code_generate">
                                </div>
                            </div>
                            <div v-else class="sparkle" :style="{ backgroundColor: invoiceSecondaryColor }" v-for="n in 13" :key="n" ></div>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>
            <div class="title_doughnut_top"></div>
        </div>
        <!-- TITLE -->
        <template v-if="!clientInvoice.use_export_layout">
            <div class="content-wrap" :style="contentWrapCustomStyle">
                <img class="invoice-body" :src="'/storage/images/invoice_body.png'" v-if="clientInvoice.allow_bg_image"/>
                <!-- START: Invoice table -->
                <div class="invoice-table-wrap mt-0 pt-2">
                    <div class="table-scroll">
                        <table class="invoice-table" role="table">
                            <thead>
                                <tr role="row" class="text-white" :style="{ backgroundColor: invoicePrimaryColor }">
                                <th
                                    v-for="(h, idx) in displayedHeaders"
                                    :key="h.key"
                                    :draggable="true"
                                    class="ma-0 py-1"
                                    @dragstart="onHeaderDragStart($event, idx)"
                                    @dragover="onHeaderDragOver($event, idx)"
                                    @dragleave="onHeaderDragLeave($event, idx)"
                                    @drop="onHeaderDrop($event, idx)"
                                    :class="['col-header', { dragging: draggingIndex === idx, 'drag-over': dragOverIndex === idx }]"
                                    :style="{ width: (columnWidths[h.key] || defaultWidth) + 'px', textAlign: h.align || 'left' }"
                                >
                                    <span class="d-inline ">
                                        <span class="text-center" @click="toggleSort(h.key)">
                                            <strong class="header-text">{{ h.title }}</strong>
                                            <span  class="sort-indicator" v-if="sortKey === h.key && is_show">
                                                <v-icon>{{ sortDesc ? 'mdi-sort-ascending' : 'mdi-sort-descending' }}</v-icon>
                                            </span>
                                        </span>
    
                                        <span v-if="is_show" class="drag-handle mx-0 px-0" :title="$t('drag_to_reorder')" @mousedown.stop>
                                            <v-icon>mdi-drag</v-icon>
                                        </span>
    
                                        <span
                                            v-if="is_show"
                                            class="resize-handle"
                                            @mousedown.prevent="onResizeMouseDown($event, h.key)"
                                            :title="$t('drag_to_resize')"
                                        ></span>
                                    </span>
                                </th>
                                </tr>
                            </thead>
    
                            <tbody>
                                <tr class="ma-0 pa-0" v-for="(row, rIdx) in sortedItems" :key="rIdx" role="row">
                                <td
                                    v-for="h in displayedHeaders"
                                    :key="h.key"
                                    class="ma-0 pa-0"
                                    :class="{ numeric: isNumericKey(h.key) }"
                                    :style="{ width: (columnWidths[h.key] || defaultWidth) + 'px', textAlign: h.align || 'left' }"
                                >
                                    <strong class="cell-content">{{ formatCell(row, h.key) }}</strong>
                                </td>
                                </tr>
    
                                <!-- filler rows to mimic printed invoice spacing -->
                                <tr class="ma-0 pa-0" v-for="n in emptyRows" :key="'empty-'+n">
                                    <td class="ma-0 pa-0" v-for="h in displayedHeaders" :key="h.key + '-e'">&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- END: Invoice table -->
                <v-row
                class="flex-nowrap"
                no-gutters
                v-if="allow_summary"
                >
                    <v-col cols="7" class="border-dashed border-sm rounded-lg ml-3 border-opacity-75 text-center" :style="{ borderColor: invoicePrimaryColor + '80 !important' }">
                        <v-row >
                            <v-col class="numeric-font" :style="{ color: invoicePrimaryColor, opacity: 0.85 }">
                                {{ $t("present invoice") }} :
                            </v-col>
                        </v-row>
                        <v-divider opacity="0.5" variant="dashed" thickness="1" :style="{ borderColor: invoicePrimaryColor + '40 !important' }"></v-divider>
                        <v-row>
                            <v-col class="numeric-font font-weight-bold" :style="{ color: invoicePrimaryColor }">
                                {{convertSumPriceToWord}}
                            </v-col>
                        </v-row>
                    </v-col>
                    <v-col class="text-black text-center-h-v">
                        <v-row class="ma-1">
                            <v-col>
                                <v-row class="text-center">
                                    <v-col class="rounded-ts-lg py-0 pr-0 pl-2 text-center text-white font-weight-bold" :style="{ backgroundColor: invoicePrimaryColor }">
                                        <div class="font-weight-bold text-subtitle-2 capitalize-text text-right pr-5">{{ $t("total_price") }} :</div>
                                    </v-col>
                                    <v-col class="rounded-te-lg py-0 pl-0 pr-2 text-left pl-5 bg-grey-lighten-5">
                                        <strong>{{total.price}} {{ this.main_currency.currency }}</strong>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col class="px-0 py-1">
                                        <v-divider color="info" opacity="0.5" class="mx-2" variant="dotted" thickness="2" :style="{ borderColor: invoiceSecondaryColor + '80 !important' }"></v-divider>
                                    </v-col>
                                </v-row>
                                <v-row class="text-center">
                                    <v-col class="rounded-bs-lg py-0 pr-0 pl-2 text-center text-white font-weight-bold" :style="{ backgroundColor: invoiceSecondaryColor }">
                                        <div class="font-weight-bold text-subtitle-2 capitalize-text text-right pr-5">{{ $t("total_weight") }} :</div>
                                    </v-col>
                                    <v-col class="rounded-be-lg py-0 pl-0 pr-2 text-left pl-5 bg-grey-lighten-5">
                                        <strong>{{total.weight}} Kg</strong>
                                    </v-col>
                                </v-row>
                            </v-col>
                        </v-row>
                    </v-col>
                </v-row>
                <v-chip  v-if="pagination.allow" variant="tonal" class="mx-3" size="x-small" color="grey">
                    <span class="text-grey-darken-1">
                        {{pagination.page}}/{{pagination.total_pages}}
                    </span>
                </v-chip>
                <div class="wrap-summary">
                    <div class="summary_doughnut_top"></div>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="content-wrap" :style="contentWrapCustomStyle">
                <v-row no-gutters class="py-2">
                    <v-col class="mr-1 py-1 font-weight-bold text-center-h-v text-center rounded-ts-xl text-white" :style="{ backgroundColor: invoiceSecondaryColor }">
                        {{$t("shipper_/_exporter") }}
                    </v-col>
                    <v-col class="ml-1 py-1 font-weight-bold text-center-h-v text-center rounded-te-xl text-white" :style="{ backgroundColor: invoicePrimaryColor }">
                        {{ $t("recipient_/_importer") }}
                    </v-col>
                </v-row>
                <v-row no-gutters>
                    <v-col class="invoice-details mr-1 py-1">
                        <v-row no-gutters class="text-center">
                            <v-col>{{ $t('company') }}</v-col>
                            <v-col cols="1">:</v-col>
                            <v-col> {{ client_invoice_detail?.invoice?.client?.name || client_invoice_detail?.client_name || '' }} </v-col>
                        </v-row>
                        <v-row no-gutters class="text-center">
                            <v-col>{{ $t("address") }}</v-col>
                            <v-col cols="1">:</v-col>
                            <v-col> {{ client_invoice_detail?.invoice?.client?.address || (Array.isArray(client_invoice_detail?.address) ? client_invoice_detail.address[0] : client_invoice_detail?.address) || '' }}</v-col>
                        </v-row>
                        <v-row no-gutters class="text-center">
                            <v-col>{{ $t("country") }}</v-col>
                            <v-col cols="1">:</v-col>
                            <v-col> {{ client_invoice_detail?.invoice?.client?.country || '' }} </v-col>
                        </v-row>
                        <v-row no-gutters class="text-center">
                            <v-col>{{ $t("shipment_country") }}</v-col>
                            <v-col cols="1">:</v-col>
                            <v-col> {{ client_invoice_detail?.invoice?.shipment_country || client_invoice_detail?.shipment_country || '' }} </v-col>
                        </v-row>
                    </v-col>
                    <v-col class="invoice-details ml-1 py-1">
                        <v-row no-gutters class="text-center">
                            <v-col>{{ $t('company') }}</v-col>
                            <v-col cols="1">:</v-col>
                            <v-col> {{ client_invoice_detail?.invoice?.intermediary_company || client_invoice_detail?.intermediary_company || '' }} </v-col>
                        </v-row>
                        <v-row no-gutters class="text-center">
                            <v-col>{{ $t("address") }}</v-col>
                            <v-col cols="1">:</v-col>
                            <v-col> {{ client_invoice_detail?.invoice?.intermediary_address || client_invoice_detail?.intermediary_address || '' }}</v-col>
                        </v-row>
                        <v-row no-gutters class="text-center">
                            <v-col>{{ $t("country") }}</v-col>
                            <v-col cols="1">:</v-col>
                            <v-col> {{ client_invoice_detail?.invoice?.intermediary_country || client_invoice_detail?.intermediary_country || '' }} </v-col>
                        </v-row>
                        <v-row no-gutters class="text-center">
                            <v-col>{{ $t("importer_country") }}</v-col>
                            <v-col cols="1">:</v-col>
                            <v-col> {{ company_settings?.settings?.company_origin || '' }} </v-col>
                        </v-row>
                    </v-col>
                </v-row>
                <img class="invoice-body" :src="'/storage/images/invoice_body.png'" v-if="clientInvoice.allow_bg_image"/>
                <!-- START: Invoice table -->
                <div class="invoice-table-wrap mt-0 pt-2">
                    <div class="table-scroll">
                        <table class="invoice-table" role="table">
                            <thead>
                                <tr role="row" class="text-white" :style="{ backgroundColor: invoicePrimaryColor }">
                                <th v-for="(h, idx) in getHeaderExportKeys"
                                    :key="idx"
                                    class="ma-0 py-1 col-header">
                                    <span class="d-inline ">
                                        <span class="text-center">
                                            <strong class="header-text">{{ $t(h)}}</strong>
                                        </span>
                                    </span>
                                </th>
                                </tr>
                            </thead>
    
                            <tbody>
                                <tr class="ma-0 pa-0" v-for="(row, rIdx) in getItemsExport" :key="rIdx" role="row">
                                    <td
                                        v-for="h in getHeaderExportKeys"
                                        :key="h"
                                        class="ma-0 pa-0"
                                        :class="{ numeric: isNumericKey(h) }"
                                        :style="{ width: (columnWidths[h] || defaultWidth) + 'px', textAlign: h.align || 'center' }"
                                    >
                                        <strong class="cell-content">{{ formatCell(row, h) }}</strong>
                                    </td>
                                </tr>
                                <!-- filler rows to mimic printed invoice spacing -->
                                <tr class="ma-0 pa-0" v-for="n in emptyRows-16" :key="'empty-'+n">
                                    <td class="ma-0 pa-0" v-for="h in getHeaderExportKeys" :key="h.key + '-e'">&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- END: Invoice table -->
                <v-row
                class="flex-nowrap mt-2"
                no-gutters
                v-if="allow_summary"
                >   
                    <v-col cols="2" class="text-center mx-3 ">
                        <v-row no-gutters class="bg-cyan-darken-4 rounded-t-lg">
                            <v-col>
                                {{ $t('incoterm') }}
                            </v-col>
                        </v-row>
                        <v-row no-gutters class="text-center bg-cyan-lighten-5 rounded-b-lg">
                            <v-col class="mx-5">
                                <v-checkbox v-for="incoterm in incoterms" :key="incoterm" 
                                    :model-value="incoterm == (client_invoice_detail?.invoice?.incoterm || client_invoice_detail?.incoterm)"
                                    color="cyan-darken-4"
                                    class="my-2"
                                    :label="incoterm"
                                    readonly
                                    hide-details
                                    density=""
                                ></v-checkbox>
                            </v-col>
                        </v-row>
                    </v-col>
                    <v-col cols="5" class="text-black text-center-h-v">
                        <v-row class="ma-1">
                            <v-col>
                                <v-row class="text-center">
                                    <v-col class="bg-cyan-darken-4 rounded-ts-lg py-0 pr-0 pl-2 text-center">
                                        <div class="font-weight-bold text-subtitle-2 capitalize-text text-right pr-5">{{ $t("total_gross_weight") }} :</div>
                                    </v-col>
                                    <v-col cols="5" class="bg-cyan-lighten-5 rounded-te-lg py-0 pl-0 pr-2 text-left pl-5">
                                        <strong>{{main.formatDisplayNumber(client_invoice_detail?.invoice?.total_gross_weight || client_invoice_detail?.total_gross_weight || 0)}} Kg</strong>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col class="px-0 py-1">
                                        <v-divider color="info" opacity="0.5" class="mx-2" variant="dotted" thickness="2"></v-divider>
                                    </v-col>
                                </v-row>
                                <v-row class="text-center">
                                    <v-col class="bg-cyan-lighten-2 rounded-bs-lg py-0 pr-0 pl-2 text-center text-white">
                                        <div class="font-weight-bold text-subtitle-2 capitalize-text text-right pr-5">{{ $t("total_weight") }} :</div>
                                    </v-col>
                                    <v-col cols="5" class="bg-cyan-lighten-5 rounded-be-lg py-0 pl-0 pr-2 text-left pl-5">
                                        <strong>{{main.formatDisplayNumber(export_total.weight)}} Kg</strong>
                                    </v-col>
                                </v-row>
                            </v-col>
                        </v-row>
                    </v-col>
                    <v-col cols="5" class="text-black text-center-h-v">
                        <v-row class="ma-1">
                            <v-col>
                                <v-row class="text-center">
                                    <v-col class="rounded-ts-lg py-0 pr-0 pl-2 text-center text-white font-weight-bold" :style="{ backgroundColor: invoicePrimaryColor }">
                                        <div class="font-weight-bold text-subtitle-2 capitalize-text text-right pr-5">{{ $t("total_price") }} :</div>
                                    </v-col>
                                    <v-col class="rounded-te-lg py-0 pl-0 pr-2 text-left pl-5 bg-grey-lighten-5">
                                        <strong>{{main.formatDisplayNumber(export_total.price)}} {{ this.main_currency.currency }}</strong>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col class="px-0 py-1">
                                        <v-divider color="info" opacity="0.5" class="mx-2" variant="dotted" thickness="2" :style="{ borderColor: invoiceSecondaryColor + '80 !important' }"></v-divider>
                                    </v-col>
                                </v-row>
                                <v-row class="text-center">
                                    <v-col class="rounded-bs-lg py-0 pr-0 pl-2 text-center text-white font-weight-bold" :style="{ backgroundColor: invoiceSecondaryColor }">
                                        <div class="font-weight-bold text-subtitle-2 capitalize-text text-right pr-5">{{ $t("total_weight") }} :</div>
                                    </v-col>
                                    <v-col class="rounded-be-lg py-0 pl-0 pr-2 text-left pl-5 bg-grey-lighten-5">
                                        <strong>{{main.formatDisplayNumber(export_total.weight)}} Kg</strong>
                                    </v-col>
                                </v-row>
                            </v-col>
                        </v-row>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col class="border-dashed border-sm rounded-lg mx-8 border-opacity-75 text-center" :style="{ borderColor: invoicePrimaryColor + '80 !important' }">
                        <v-row >
                            <v-col class="numeric-font" :style="{ color: invoicePrimaryColor, opacity: 0.85 }">
                                {{ $t("present invoice") }} :
                            </v-col>
                        </v-row>
                        <v-divider opacity="0.5" variant="dashed" thickness="1" :style="{ borderColor: invoicePrimaryColor + '40 !important' }"></v-divider>
                        <v-row>
                            <v-col class="numeric-font font-weight-bold" :style="{ color: invoicePrimaryColor }">
                                {{convertSumPriceToWord}}
                            </v-col>
                        </v-row>
                    </v-col>
                </v-row>
                <v-chip  v-if="pagination.allow" variant="tonal" class="mx-3" size="x-small" color="grey">
                    <span class="text-grey-darken-1">
                        {{pagination.page}}/{{pagination.total_pages}}
                    </span>
                </v-chip>
                <div class="wrap-summary">
                    <div class="summary_doughnut_top"></div>
                </div>
            </div>
        </template>

        <!-- FOOTER -->
        <div class="footer-wrap">
            <v-row no-gutters class="footer-wrap-divided" :style="{ background: footerDividedBg, height: layout.footer_divided_height || '3cm' }">
                <v-col v-if="layout.footer_show_director !== false" cols="4" class="text-center px-8 invoice-director" :style="{ backgroundColor: layout.footer_bg_color || '#FFFFFF', color: layout.footer_text_color || '#006064' }">
                    <div class="mb-2" :style="{ fontSize: layoutDirectorFontSize }">
                        {{ layout.footer_director_title || $t("director") }}
                    </div>
                    <v-divider opacity=".7" thickness="2" class="mb-8" :style="{ borderColor: (layout.footer_primary_color || '#006064') + ' !important' }"></v-divider>
                    <v-divider v-if="layout.footer_line_style !== 'none'" opacity=".5" thickness="2" :variant="layout.footer_line_style || 'dashed'" class="mx-5" :style="{ borderColor: (layout.footer_secondary_color || '#4DD0E1') + ' !important' }"></v-divider>
                </v-col>
                <template v-if="layout.footer_style === 'curved' || !layout.footer_style">
                    <v-col cols="2" class="footer-cursev-light" :style="{ backgroundColor: layout.footer_bg_color || '#FFFFFF' }"></v-col>
                    <v-col cols="2" class="footer-cursev-dark" :style="{ backgroundColor: layout.footer_primary_color || '#006064' }"></v-col>
                </template>
                <template v-else-if="layout.footer_style === 'slant'">
                    <v-col cols="1" class="footer-slant-light" :style="{ backgroundColor: layout.footer_bg_color || '#FFFFFF' }"></v-col>
                    <v-col cols="3" class="footer-slant-dark" :style="{ backgroundColor: layout.footer_primary_color || '#006064' }"></v-col>
                </template>
                <template v-else-if="layout.footer_style === 'straight'">
                    <v-col cols="auto" style="width: 14px; background: rgba(0,0,0,0.12);"></v-col>
                </template>
                <template v-else-if="layout.footer_style === 'rounded'">
                    <v-col cols="1"></v-col>
                    <v-col cols="2" style="border-top-left-radius: 40px; border-bottom-left-radius: 40px;" :style="{ backgroundColor: layout.footer_primary_color || '#006064' }"></v-col>
                </template>
                <template v-else-if="layout.footer_style === 'minimal'">
                    <v-col cols="auto" style="width: 4px; background: rgba(0,0,0,0.08);"></v-col>
                </template>
                <v-col cols="*" :style="{ backgroundColor: layout.footer_primary_color || '#006064' }"></v-col>
            </v-row>
            <v-row no-gutters>
                <v-col cols="12" class="footer-wrap-strick" :style="{ backgroundColor: layout.footer_primary_color || '#006064', height: layout.footer_strick_height || '0.3cm' }">
                </v-col>
            </v-row>
        </div>

    </div>
</template>

<script>
    import { useTruck } from '@/stores/Truck';
    import { useCompanySettings } from '@/stores/CompanySettings';
    import { useMain } from '@/stores/Main';
    import { useClientInvoice } from '@/stores/ClientInvoice';
    import writtenNumber from 'written-number';
    import { QRCodeSVG } from '@cheprasov/qrcode';
    import { useInvoiceLayout } from '@/stores/InvoiceLayout';

    export default {
        props:{
          items : Array,  
          client_invoice_detail : Object,  
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
            company_settings: () => useCompanySettings(),
            clientInvoice: () => useClientInvoice(),
            invoiceLayout: () => useInvoiceLayout(),
            layout: function () {
                return this.invoiceLayout.getLayout('client');
            },
            invoicePrimaryColor: function () {
                return this.layout.header_primary_color || '#006064';
            },
            invoiceSecondaryColor: function () {
                return this.layout.header_secondary_color || '#4DD0E1';
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
                    height: `calc(var(--invoice-height) - (${hD}) - (${fD}) - (${hS}) - (${fS}) - var(--title-wrap-height) - var(--title-wrap-margin-top-height))`,
                    position: 'relative',
                };
            },
            headerDividedBg: function () {
                const l = this.layout;
                if (l.header_style === 'gradient') {
                    return `linear-gradient(135deg, ${l.header_primary_color || '#006064'} 0%, ${l.header_secondary_color || '#4DD0E1'} 100%)`;
                }
                return l.header_secondary_color || '#4DD0E1';
            },
            footerDividedBg: function () {
                const l = this.layout;
                if (l.footer_style === 'gradient') {
                    return `linear-gradient(135deg, ${l.footer_primary_color || '#006064'} 0%, ${l.footer_secondary_color || '#4DD0E1'} 100%)`;
                }
                return l.footer_secondary_color || '#4DD0E1';
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
                return this.clientInvoice.is_show;
            },
            export_total: function () {
                return {
                    price: _.sumBy(this.client_invoice_detail?.invoice?.export_details,"total_price"),
                    weight: _.sumBy(this.client_invoice_detail?.invoice?.export_details,"weight_mt"),
                }
            },
            incoterms : function(){
                return ['FOB','C&F','CIF'];
            },
            qr_code_generate: function () {
                const qrSVG = new QRCodeSVG('0001', {
                    level: 'H',
                    padding: 0,
                    fgColor: '#00838F',
                    image: {
                        source: '/storage/images/invoice_body.png',
                        width: '20%',
                        height: '20%',
                        x: 'center',
                        y: 'center',
                    },
                });
                const dataUrlWithSVGQRCode = qrSVG.toDataUrl();
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

                // if no centimes, return only the unit part
                if (subunit === 0) {
                    return `${this.main.capitalize(unitWords)} ${this.main_currency.unit}(s)`.trim();
                }

                // otherwise include centimes
                const subunitWords = writtenNumber(subunit, { lang: this.main.lang.code });
                return `${this.main.capitalize(unitWords)} ${this.main_currency.unit}(s) ${this.$t('and')} 
                ${this.main.capitalize(subunitWords)} ${this.main_currency.subunit}(s)`
                .trim();

            },
            main_currency : function(){
                const code = this.client_invoice_detail?.invoice?.currency || this.client_invoice_detail?.currency;
                if(code){
                    return this.main.currencies.find(c => c.short == code) || { currency: code, unit: code, subunit: '' };
                }
                return this.main.currencies.find(c => c.short == this.main.currency) || { currency: this.main.currency, unit: this.main.currency, subunit: '' };
            },
            isExport() {
                return this.client_invoice_detail?.type_invoice === 'export';
            },
            exportSubTotal() {
                return (this.client_invoice_detail?.export_details || []).reduce((s, l) => s + Number(l.total_price || 0), 0);
            },
            exportGrandTotal() {
                const sub = this.exportSubTotal;
                const tax = Number(this.client_invoice_detail?.tax_percentage || 0);
                return Number((sub + sub * (tax / 100)).toFixed(2));
            },
            // displayedHeaders: respects visibility and columnOrder (uses this.clientInvoice.getHeaders)
            displayedHeaders() {
                const visible = (this.clientInvoice.getHeaders || []).filter(h => h.visible !== false);
                if (!this.columnOrder || this.columnOrder.length === 0) {
                this.columnOrder = visible.map(h => h.key);
                }
                const byKey = visible.reduce((acc, h) => { acc[h.key] = h; return acc; }, {});
                const mapped = [];
                this.columnOrder.forEach(k => { if (byKey[k]) mapped.push(byKey[k]); });
                visible.forEach(h => { if (!this.columnOrder.includes(h.key)) mapped.push(h); });
                return mapped;
            },
            getHeaderExportKeys:function(){
                const details = this.client_invoice_detail?.invoice?.export_details || this.client_invoice_detail?.export_details;
                if(!details || details.length === 0){
                   return [];
                }
                return Object.keys(details[0] || {});
            },
            getItemsExport:function(){
                const details = this.client_invoice_detail?.invoice?.export_details || this.client_invoice_detail?.export_details;
                return details || [];
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
                const visibleKeys = (this.clientInvoice.getHeaders || []).filter(h => h.visible !== false).map(h => h.key);
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

            // utility
            resetColumnOrder() {
                this.columnOrder = (this.clientInvoice.getHeaders || []).filter(h => h.visible !== false).map(h => h.key);
            }

        }
    }
</script>

<style scoped >
    .content-wrap{
        height: calc(
            var(--invoice-height) 
            - var(--header-wrap-divided-height) 
            - var(--header-wrap-divided-height) 
            - var(--header-wrap-strick-height) 
            - var(--header-wrap-strick-height) 
            - var(--title-wrap-height) 
            - var(--title-wrap-margin-top-height)
        ); 
        position: relative;
    }
    .invoice-details .v-row:nth-child(odd) {
        background-color:rgba(135,207,235,0.12);
    }

    .invoice-details .v-row:nth-child(even) {
        background-color: rgba(255, 255, 255, 0);
    }

    .invoice-wrap{
        height: var(--invoice-height);
        width: var(--invoice-width);
        overflow: hidden;
        background-color: white;
        size:a4;
    }

    .title-wrap{
        height: var(--title-wrap-height);
        margin-top: var(--title-wrap-margin-top-height);
        position: relative;
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

    .title_doughnut_top{
        border-radius: 100px;
        border: 22px solid rgba(135, 207, 235, 0.1529411765);
        height: 3.5cm !important;
        width: 3.5cm;
        position: absolute;
        right: 19cm;
        top: 1cm;
        z-index: 0;
    }

    .summary_doughnut_top{
        border-radius: 100px;
        border: 22px solid rgba(135, 207, 235, 0.1529411765);
        height: 3.5cm !important;
        width: 3.5cm;
        position: absolute;
        left: 19cm;
        bottom: 2cm;
        z-index: 0;
    }

    .wrap-table{
        width: var(--invoice-height);
    }

    .invoice-body{
        opacity: 0.1;
        top: 13%;
        position: absolute;
        right: 15%;
        pointer-events: none;    
    }

    .chevron {
        --s: 4px;
        height: 25px;
        aspect-ratio: 3 / 5;
        clip-path: polygon(0 0, var(--s) 0, 100% 50%, var(--s) 100%, 0 100%, calc(100% - var(--s)) 50%);
    }

    .ant_slash {
        --p: 18px; /* control the shape (can be percentage) */
        height: 20px;
        width: 15px;
        aspect-ratio: 3/2;
        clip-path: polygon(var(--p) 0,100% 0,calc(100% - var(--p)) 100%,0 100%);
    }


    .sparkle {
        width: 30px; /* adjust to control the size */
        aspect-ratio: 1;
        clip-path: polygon(100% 50%,calc(85.36% - 14.03px) calc(85.36% - 14.03px),50% 100%,calc(14.64% - -14.03px) calc(85.36% - 14.03px),0% 50%,calc(14.64% - -14.03px) calc(14.64% - -14.03px),50% 0%,calc(85.36% - 14.03px) calc(14.64% - -14.03px));
    }
    
    .numeric-font{
        font-family: 'Roboto Condensed' !important;
    }

    .corner-only {
        --s: 15px; /* size of the corners */
        border: 2px solid #1d83d1; 
        position: absolute;
        bottom: 3px;
        right: 20px;
        padding: 5px;
        width: 70px !important;
        height: 70px !important;
        mask: 
            conic-gradient(#000 0 0) content-box,
            conic-gradient(at var(--s) var(--s),#0000 75%,#000 0) 
            0 0/calc(100% - var(--s)) calc(100% - var(--s));
    }

    .capitalize-text {
        text-transform: capitalize !important;
        font-family: sans-serif !important;
    }

/* Table container */
.invoice-table-wrap { padding: 0.5rem 1rem 0rem 1rem; z-index: 1; position: relative; }

/* scroll */
.table-scroll { overflow: hidden; max-width: calc(var(--invoice-width) - 2rem); border-top-left-radius: 0.5cm;border-top-right-radius: 0.5cm; background: transparent; }

/* table base */
table.invoice-table { border-collapse: collapse; width: 100%; table-layout: fixed; font-family: Arial, Helvetica, sans-serif; font-size: 12px; }

/* header look (dark-blue) */
thead{
    overflow:hidden!important
}
tbody{
    overflow:hidden!important
}
table{
    overflow:hidden!important
}
table.invoice-table thead th {
  position: relative; user-select: none; vertical-align: middle;
  color:#fff; font-weight:900; border-bottom:3px solid #fff;
  white-space: nowrap; overflow:hidden; box-shadow: inset 0 -2px 0 rgba(255,255,255,0.08);
}
table.invoice-table thead th:first-child { border-top-left-radius: 8px; }
table.invoice-table thead th:last-child  { border-top-right-radius: 8px; }

.col-header.dragging { opacity: 0.6; }
.col-header.drag-over { outline: 2px dashed rgba(0,0,0,0.15); }

.sort-indicator { font-size:0.9em; opacity:0.95; margin-left:1px; }

.drag-handle { cursor:grab; padding:4px 9px; border-radius:px; font-size:0.95em; user-select:none; }

/* resize handle */
.resize-handle { position:absolute; right:0; top:0; bottom:0; width:10px; cursor:col-resize; z-index:1; }

/* striping & body rows */
table.invoice-table tbody tr { background: transparent; }
table.invoice-table tbody tr:nth-child(odd) td { background: rgba(135,207,235,0.12); }
table.invoice-table tbody tr:nth-child(even) td { background: rgba(255, 255, 255, 0); }

table.invoice-table tbody tr:not(:last-child) td  {
    border-bottom: 1px solid rgba(87, 202, 241, 0.915); vertical-align: middle;
    
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* numeric alignment */
td.numeric, th.numeric { text-align: right; padding-right: 16px; }

/* cell content */
.cell-content { overflow:hidden; color: black; text-overflow:ellipsis; white-space:nowrap; }

/* filler empty rows appearance */
table.invoice-table tbody tr td:empty { height: 26px; }

/* responsive tweaks */
@media (max-width: 900px) {
  .invoice-table { font-size: 11px; }
  table.invoice-table thead th, table.invoice-table tbody td { padding: 8px 10px; }
}
.rounded-s-xl{
    border-top-left-radius: 24px !important;
    border-bottom-left-radius: 24px !important;
}
.rounded-ts-lg {
    border-top-left-radius: 8px !important;
}
.rounded-te-lg {
    border-top-right-radius: 8px !important;
}
.rounded-bs-lg {
    border-bottom-left-radius: 8px !important;
}
.rounded-bs-lg[data-v-043dbba6] {
    border-bottom-left-radius: 8px !important;
}
/* ═══ Export Invoice Custom Styles ═══ */
.exp-header { height: 100px; position: relative; overflow: hidden; }
.exp-header-curve { width: 300px; height: 100px; position: absolute; left: 45%; transform: skewX(-40deg); z-index: 1; }
.exp-meta-box { border: 1px solid #B2EBF2; }
.exp-party-box { height: 100%; }
.exp-table { width: 100%; border-collapse: collapse; }
.exp-table th { padding: 6px 12px; font-size: 13px; font-weight: 800; text-align: center; border-bottom: 2px solid #80DEEA; }
.exp-table td { padding: 8px 12px; font-size: 12px; border-bottom: 1px solid #E0F7FA; }
.exp-checkbox { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: 1.5px solid #00838F; border-radius: 2px; background: white; }
.exp-checkbox.checked { border-color: #00838F; }
.exp-footer-curve { position: absolute; top: 0; left: -50px; width: 150px; height: 100%; transform: skewX(-30deg); z-index: 1; }
.export-invoice-wrap { position: relative; z-index: 1; }
.export-watermark { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%); opacity: 0.05; z-index: 0; pointer-events: none; width: 60%; }
</style>
