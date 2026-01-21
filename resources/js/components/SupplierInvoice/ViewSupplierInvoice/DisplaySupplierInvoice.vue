<template>
    <v-container class="invoice-wrap pa-0">
        <div class="header-wrap">
            <v-row no-gutters>
                <v-col cols="12" class="bg-light-blue-darken-4 header-wrap-strick">
                </v-col>
            </v-row>
            <v-row no-gutters class="header-wrap-divided bg-light-blue-lighten-2 ">
                <v-col cols="4" class="bg-light-blue-darken-4 text-center-h-v pl-3">
                    <v-img  src="/storage/images/invoice_title.png"></v-img>
                </v-col>
                <v-col cols="2" class="bg-light-blue-darken-4 header-cursev-dark">
                    
                </v-col>
                <v-col cols="2" class="bg-white header-cursev-light">
    
                </v-col>
                <v-col cols="*" class="bg-white text-light-blue-darken-4 text-center-h-v invoice-title">
                    {{ $t('invoice') }}
                </v-col>
            </v-row>
        </div>
        <!-- HEADER -->

        <div class="title-wrap">
            <v-row no-gutters>
                <v-col>
                    <v-row >
                        <v-col class="text-center text-light-blue-darken-4 d-flex">
                            <div class="ml-5 bg-light-blue-darken-4 chevron" v-for="n in 10" :key="n" ></div>
                        </v-col>
                    </v-row>
                    <v-row >
                        <v-col style="margin-left : 1cm">
                            <v-row no-gutters>
                                <v-col class="text-black">
                                    <strong>
                                        {{ $t('company') }} : IGLO FISH
                                    </strong>
                                </v-col>
                            </v-row>
                            <v-row no-gutters>
                                <v-col class="text-black">
                                    <strong>
                                        {{ $t("address") }} : LAAYOUNE
                                    </strong>
                                </v-col>
                            </v-row>
                        </v-col>
                    </v-row>
                </v-col>
                <v-col >
                    <v-row  class="text-center-h-v text-center bg-light-blue-lighten-2 rounded-s-xl">
                        <v-col>
                            {{$t('invoice_n°') }}:
                            <strong>
                             2500047
                            </strong>
                        </v-col>
                        <v-col cols="1">
                            │
                        </v-col>
                        <v-col>
                            {{ $t("date") }} :
                            <strong>
                             03/08/2025
                            </strong>
                        </v-col>
                    </v-row>
                    <v-row >
                        <v-col class="anti-slash text-light-blue-lighten-2">
                            <strong v-for="n in 15" :key="n">/</strong>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>
            <div class="title_doughnut_top"></div>
        </div>
        <!-- TITLE -->
        
        <div class="content-wrap">
            <img class="invoice_body" src="/storage/images/invoice_body.png"/>
            <!-- START: Invoice table -->
            <div class="invoice-table-wrap">
                <div class="table-scroll">
                    <table class="invoice-table" role="table">
                        <thead>
                            <tr role="row" class="bg-light-blue-darken-4">
                            <th
                                v-for="(h, idx) in displayedHeaders"
                                :key="h.key"
                                :draggable="true"
                                @dragstart="onHeaderDragStart($event, idx)"
                                @dragover="onHeaderDragOver($event, idx)"
                                @dragleave="onHeaderDragLeave($event, idx)"
                                @drop="onHeaderDrop($event, idx)"
                                :class="['col-header', { dragging: draggingIndex === idx, 'drag-over': dragOverIndex === idx }]"
                                :style="{ width: (columnWidths[h.key] || defaultWidth) + 'px', textAlign: h.align || 'left' }"
                            >
                                <div class="header-cell">
                                <div class="header-title text-center-h-v" @click="toggleSort(h.key)">
                                    <span class="header-text">{{ h.title }}</span>
                                    <span class="sort-indicator" v-if="sortKey === h.key">
                                        <v-icon>{{ sortDesc ? 'mdi-sort-ascending' : 'mdi-sort-descending' }}</v-icon>
                                    </span>
                                </div>

                                <div class="drag-handle" title="Drag to reorder" @mousedown.stop>
                                    <v-icon>mdi-drag</v-icon>
                                </div>

                                <div
                                    class="resize-handle"
                                    @mousedown.prevent="onResizeMouseDown($event, h.key)"
                                    title="Drag to resize"
                                ></div>
                                </div>
                            </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(row, rIdx) in sortedItems" :key="rIdx" role="row">
                            <td
                                v-for="h in displayedHeaders"
                                :key="h.key"
                                :class="{ numeric: isNumericKey(h.key) }"
                                :style="{ width: (columnWidths[h.key] || defaultWidth) + 'px', textAlign: h.align || 'left' }"
                            >
                                <strong class="cell-content text-center-h-v">{{ formatCell(row, h.key) }}</strong>
                            </td>
                            </tr>

                            <!-- filler rows to mimic printed invoice spacing -->
                            <tr v-for="n in emptyRows" :key="'empty-'+n">
                                <td v-for="h in displayedHeaders" :key="h.key + '-e'">&nbsp;</td>
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
                <v-col cols="7" class="border-dotted border-md rounded-lg ml-3 border-info text-blue text-center">
                    <v-row >
                        <v-col class="text-light-blue-lighten-1 ">
                            Arrêtée la présente facture à la somme :
                        </v-col>
                    </v-row>
                    <v-divider variant="dotted" thickness="2"></v-divider>
                    <v-row>
                        <v-col class="text-light-blue-darken-4">
                            Deux Cent Trente-quatre Mille Trois Cent Quatre-vingt-cinq
                            Dirham(s) Et Vingt Centime(s)
                        </v-col>
                    </v-row>
                </v-col>
                <v-col class="text-black text-center-h-v">
                    <v-row class="ma-1">
                        <v-col>
                            <v-row class="text-center">
                                <v-col class="bg-light-blue-darken-4 rounded-ts-lg py-0 pr-0 pl-2 text-left">
                                    <div class="font-weight-regular text-subtitle-2">Total En Dhs :</div>
                                </v-col>
                                <v-col class="bg-light-blue-lighten-5 rounded-te-lg py-0 pl-0 pr-2">
                                    <strong>234 385.20 Dhs</strong>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col class="px-0 py-1">
                                    <v-divider color="info" class="mx-2" variant="dotted" thickness="2"></v-divider>
                                </v-col>
                            </v-row>
                            <v-row class="text-center">
                                <v-col class="bg-light-blue-lighten-2 rounded-bs-lg py-0 pr-0 pl-2 text-left text-white">
                                    <div class="font-weight-regular text-subtitle-2">Total En Kg :</div>
                                </v-col>
                                <v-col class="bg-light-blue-lighten-5 rounded-be-lg py-0 pl-0 pr-2 ">
                                    <strong>111 798.00 Kg</strong>
                                </v-col>
                            </v-row>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>
            <v-chip  v-if="pagination.allow" variant="tonal" class="mx-3" size="x-small" color="grey">
                {{pagination.page}}/{{pagination.total_pages}}
            </v-chip>
            <div class="wrap-summary">
                <div class="summary_doughnut_top"></div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer-wrap">
            <v-row no-gutters class="footer-wrap-divided bg-light-blue-lighten-2">
                <v-col cols="4" class="bg-white text-light-blue-darken-4 text-center px-8 invoice-director">
                    <div class="mb-2">
                        {{ $t("director") }}
                    </div>
                    <v-divider opacity=".7" thickness="2" class="mb-8 text-light-blue-darken-4"></v-divider>
                    <v-divider opacity=".5" thickness="2" variant="dashed" class="text-light-blue-lighten-2 mx-5"></v-divider>
                </v-col>
                <v-col cols="2" class="bg-white footer-cursev-light">
    
                </v-col>
                <v-col cols="2" class="bg-light-blue-darken-4 footer-cursev-dark">
                    
                </v-col>
                <v-col cols="4" class="bg-light-blue-darken-4">
    
                </v-col>
            </v-row>
            <v-row no-gutters>
                <v-col cols="12" class="bg-light-blue-darken-4 footer-wrap-strick">
    
                </v-col>
            </v-row>
        </div>
    </v-container>
</template>

<script>
    import { useTruck } from '@/stores/Truck';
    import { useMain } from '@/stores/Main';
    import { useSupplierInvoice } from '@/stores/SupplierInvoice';
    export default {
        props:{
          items : Array,  
          pagination : Object,  
          emptyRows : Number,  
          pageIndex : Number,  
          allow_summary : Boolean,  
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
            headers:function(){
                return [
                    { align : "center" , title: this.$t("date"), key: 'date' , visible:true },
                    { align : "center" , title: this.$t("registration_plate"), key: 'truck_id' , visible:true },
                    { align : "center" , title: this.$t("tracking_number_short"), key: 'tracking_numbers' , visible:true },
                    { align : "center" , title: this.$t("stockable"), key: 'stockable' , visible:false },
                    { align : "center" , title: this.$t("weight_kg"), key: 'total_weight' , visible:true },
                    { align : "center" , title: this.$t("unit_price_currency",{currency:this.main.currency}), key: 'unit_price', visible: true },
                    { align : "center" , title: this.$t("price_dh",{currency:this.main.currency}), key: 'total_price', visible: true },                
                ]
            },
            // displayedHeaders: respects visibility and columnOrder (uses this.headers)
            displayedHeaders() {
                const visible = (this.headers || []).filter(h => h.visible !== false);
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
                const visibleKeys = (this.headers || []).filter(h => h.visible !== false).map(h => h.key);
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
                this.columnOrder = (this.headers || []).filter(h => h.visible !== false).map(h => h.key);
            }

        }
    }
</script>

<style lang="scss" scoped>
    .content-wrap{
        height: calc(
            var(--invoice-height) 
            - var(--header-wrap-divided-height) 
            - var(--header-wrap-divided-height) 
            - var(--header-wrap-strick-height) 
            - var(--header-wrap-strick-height) 
            - var(--title-wrap-height) 
            - var(--title-wrap-margin-top-height)
        ); // 
        position: relative;
    }

    .invoice-wrap{
        height: var(--invoice-height);
        width: var(--invoice-width);
        overflow: hidden;
        background-color: white;
    }

    .title-wrap{
        height: var(--title-wrap-height);
        margin-top: var(--title-wrap-margin-top-height);
        position: relative;
    }

    //stylish header-wrap
    .header-wrap-strick{
        height: var(--header-wrap-strick-height);
    }
    .header-wrap-divided{
        height: var(--header-wrap-divided-height);
    }
    .header-cursev-dark{
        clip-path: polygon(100% 0%, 0% 0% , 0.00% 100.00%, 1.00% 99.98%, 2.00% 99.90%, 3.00% 99.78%, 4.00% 99.61%, 5.00% 99.38%, 6.00% 99.11%, 7.00% 98.80%, 8.00% 98.43%, 9.00% 98.01%, 10.00% 97.55%, 11.00% 97.04%, 12.00% 96.49%, 13.00% 95.89%, 14.00% 95.24%, 15.00% 94.55%, 16.00% 93.82%, 17.00% 93.04%, 18.00% 92.22%, 19.00% 91.35%, 20.00% 90.45%, 21.00% 89.51%, 22.00% 88.53%, 23.00% 87.51%, 24.00% 86.45%, 25.00% 85.36%, 26.00% 84.23%, 27.00% 83.07%, 28.00% 81.87%, 29.00% 80.65%, 30.00% 79.39%, 31.00% 78.10%, 32.00% 76.79%, 33.00% 75.45%, 34.00% 74.09%, 35.00% 72.70%, 36.00% 71.29%, 37.00% 69.86%, 38.00% 68.41%, 39.00% 66.94%, 40.00% 65.45%, 41.00% 63.95%, 42.00% 62.43%, 43.00% 60.91%, 44.00% 59.37%, 45.00% 57.82%, 46.00% 56.27%, 47.00% 54.71%, 48.00% 53.14%, 49.00% 51.57%, 50.00% 50.00%, 51.00% 48.43%, 52.00% 46.86%, 53.00% 45.29%, 54.00% 43.73%, 55.00% 42.18%, 56.00% 40.63%, 57.00% 39.09%, 58.00% 37.57%, 59.00% 36.05%, 60.00% 34.55%, 61.00% 33.06%, 62.00% 31.59%, 63.00% 30.14%, 64.00% 28.71%, 65.00% 27.30%, 66.00% 25.91%, 67.00% 24.55%, 68.00% 23.21%, 69.00% 21.90%, 70.00% 20.61%, 71.00% 19.35%, 72.00% 18.13%, 73.00% 16.93%, 74.00% 15.77%, 75.00% 14.64%, 76.00% 13.55%, 77.00% 12.49%, 78.00% 11.47%, 79.00% 10.49%, 80.00% 9.55%, 81.00% 8.65%, 82.00% 7.78%, 83.00% 6.96%, 84.00% 6.18%, 85.00% 5.45%, 86.00% 4.76%, 87.00% 4.11%, 88.00% 3.51%, 89.00% 2.96%, 90.00% 2.45%, 91.00% 1.99%, 92.00% 1.57%, 93.00% 1.20%, 94.00% 0.89%, 95.00% 0.62%, 96.00% 0.39%, 97.00% 0.22%, 98.00% 0.10%, 99.00% 0.02%, 100.00% 0.00%)
    }
    .header-cursev-light{
        clip-path: polygon(100% 100%, 0% 100% , 0.00% 99.38%, 1.00% 99.35%, 2.00% 99.27%, 3.00% 99.15%, 4.00% 98.97%, 5.00% 98.74%, 6.00% 98.47%, 7.00% 98.14%, 8.00% 97.76%, 9.00% 97.34%, 10.00% 96.87%, 11.00% 96.35%, 12.00% 95.78%, 13.00% 95.16%, 14.00% 94.50%, 15.00% 93.79%, 16.00% 93.04%, 17.00% 92.24%, 18.00% 91.40%, 19.00% 90.51%, 20.00% 89.59%, 21.00% 88.62%, 22.00% 87.61%, 23.00% 86.57%, 24.00% 85.48%, 25.00% 84.36%, 26.00% 83.21%, 27.00% 82.02%, 28.00% 80.79%, 29.00% 79.54%, 30.00% 78.25%, 31.00% 76.93%, 32.00% 75.59%, 33.00% 74.21%, 34.00% 72.81%, 35.00% 71.39%, 36.00% 69.95%, 37.00% 68.48%, 38.00% 66.99%, 39.00% 65.49%, 40.00% 63.96%, 41.00% 62.42%, 42.00% 60.87%, 43.00% 59.30%, 44.00% 57.73%, 45.00% 56.14%, 46.00% 54.55%, 47.00% 52.95%, 48.00% 51.34%, 49.00% 49.73%, 50.00% 48.12%, 51.00% 46.52%, 52.00% 44.91%, 53.00% 43.30%, 54.00% 41.70%, 55.00% 40.11%, 56.00% 38.52%, 57.00% 36.95%, 58.00% 35.38%, 59.00% 33.83%, 60.00% 32.29%, 61.00% 30.76%, 62.00% 29.26%, 63.00% 27.77%, 64.00% 26.30%, 65.00% 24.86%, 66.00% 23.44%, 67.00% 22.04%, 68.00% 20.66%, 69.00% 19.32%, 70.00% 18.00%, 71.00% 16.71%, 72.00% 15.46%, 73.00% 14.23%, 74.00% 13.04%, 75.00% 11.89%, 76.00% 10.77%, 77.00% 9.68%, 78.00% 8.64%, 79.00% 7.63%, 80.00% 6.66%, 81.00% 5.74%, 82.00% 4.85%, 83.00% 4.01%, 84.00% 3.21%, 85.00% 2.46%, 86.00% 1.75%, 87.00% 1.09%, 88.00% 0.47%, 89.00% -0.10%, 90.00% -0.62%, 91.00% -1.09%, 92.00% -1.51%, 93.00% -1.89%, 94.00% -2.22%, 95.00% -2.49%, 96.00% -2.72%, 97.00% -2.90%, 98.00% -3.02%, 99.00% -3.10%, 100.00% -3.13%)
    }


    //stylish footer-wrap
    .footer-wrap-strick{
        height: var(--header-wrap-strick-height);
    }
    .footer-wrap-divided{
        height: var(--header-wrap-divided-height);
    }
    .footer-cursev-dark{
        clip-path: polygon(100% 100%, 0% 100% , 0.00% 99.38%, 1.00% 99.35%, 2.00% 99.27%, 3.00% 99.15%, 4.00% 98.97%, 5.00% 98.74%, 6.00% 98.47%, 7.00% 98.14%, 8.00% 97.76%, 9.00% 97.34%, 10.00% 96.87%, 11.00% 96.35%, 12.00% 95.78%, 13.00% 95.16%, 14.00% 94.50%, 15.00% 93.79%, 16.00% 93.04%, 17.00% 92.24%, 18.00% 91.40%, 19.00% 90.51%, 20.00% 89.59%, 21.00% 88.62%, 22.00% 87.61%, 23.00% 86.57%, 24.00% 85.48%, 25.00% 84.36%, 26.00% 83.21%, 27.00% 82.02%, 28.00% 80.79%, 29.00% 79.54%, 30.00% 78.25%, 31.00% 76.93%, 32.00% 75.59%, 33.00% 74.21%, 34.00% 72.81%, 35.00% 71.39%, 36.00% 69.95%, 37.00% 68.48%, 38.00% 66.99%, 39.00% 65.49%, 40.00% 63.96%, 41.00% 62.42%, 42.00% 60.87%, 43.00% 59.30%, 44.00% 57.73%, 45.00% 56.14%, 46.00% 54.55%, 47.00% 52.95%, 48.00% 51.34%, 49.00% 49.73%, 50.00% 48.12%, 51.00% 46.52%, 52.00% 44.91%, 53.00% 43.30%, 54.00% 41.70%, 55.00% 40.11%, 56.00% 38.52%, 57.00% 36.95%, 58.00% 35.38%, 59.00% 33.83%, 60.00% 32.29%, 61.00% 30.76%, 62.00% 29.26%, 63.00% 27.77%, 64.00% 26.30%, 65.00% 24.86%, 66.00% 23.44%, 67.00% 22.04%, 68.00% 20.66%, 69.00% 19.32%, 70.00% 18.00%, 71.00% 16.71%, 72.00% 15.46%, 73.00% 14.23%, 74.00% 13.04%, 75.00% 11.89%, 76.00% 10.77%, 77.00% 9.68%, 78.00% 8.64%, 79.00% 7.63%, 80.00% 6.66%, 81.00% 5.74%, 82.00% 4.85%, 83.00% 4.01%, 84.00% 3.21%, 85.00% 2.46%, 86.00% 1.75%, 87.00% 1.09%, 88.00% 0.47%, 89.00% -0.10%, 90.00% -0.62%, 91.00% -1.09%, 92.00% -1.51%, 93.00% -1.89%, 94.00% -2.22%, 95.00% -2.49%, 96.00% -2.72%, 97.00% -2.90%, 98.00% -3.02%, 99.00% -3.10%, 100.00% -3.13%)
    }
    .footer-cursev-light{
        clip-path: polygon(100% 0%, 0% 0% , 0.00% 100.00%, 1.00% 99.98%, 2.00% 99.90%, 3.00% 99.78%, 4.00% 99.61%, 5.00% 99.38%, 6.00% 99.11%, 7.00% 98.80%, 8.00% 98.43%, 9.00% 98.01%, 10.00% 97.55%, 11.00% 97.04%, 12.00% 96.49%, 13.00% 95.89%, 14.00% 95.24%, 15.00% 94.55%, 16.00% 93.82%, 17.00% 93.04%, 18.00% 92.22%, 19.00% 91.35%, 20.00% 90.45%, 21.00% 89.51%, 22.00% 88.53%, 23.00% 87.51%, 24.00% 86.45%, 25.00% 85.36%, 26.00% 84.23%, 27.00% 83.07%, 28.00% 81.87%, 29.00% 80.65%, 30.00% 79.39%, 31.00% 78.10%, 32.00% 76.79%, 33.00% 75.45%, 34.00% 74.09%, 35.00% 72.70%, 36.00% 71.29%, 37.00% 69.86%, 38.00% 68.41%, 39.00% 66.94%, 40.00% 65.45%, 41.00% 63.95%, 42.00% 62.43%, 43.00% 60.91%, 44.00% 59.37%, 45.00% 57.82%, 46.00% 56.27%, 47.00% 54.71%, 48.00% 53.14%, 49.00% 51.57%, 50.00% 50.00%, 51.00% 48.43%, 52.00% 46.86%, 53.00% 45.29%, 54.00% 43.73%, 55.00% 42.18%, 56.00% 40.63%, 57.00% 39.09%, 58.00% 37.57%, 59.00% 36.05%, 60.00% 34.55%, 61.00% 33.06%, 62.00% 31.59%, 63.00% 30.14%, 64.00% 28.71%, 65.00% 27.30%, 66.00% 25.91%, 67.00% 24.55%, 68.00% 23.21%, 69.00% 21.90%, 70.00% 20.61%, 71.00% 19.35%, 72.00% 18.13%, 73.00% 16.93%, 74.00% 15.77%, 75.00% 14.64%, 76.00% 13.55%, 77.00% 12.49%, 78.00% 11.47%, 79.00% 10.49%, 80.00% 9.55%, 81.00% 8.65%, 82.00% 7.78%, 83.00% 6.96%, 84.00% 6.18%, 85.00% 5.45%, 86.00% 4.76%, 87.00% 4.11%, 88.00% 3.51%, 89.00% 2.96%, 90.00% 2.45%, 91.00% 1.99%, 92.00% 1.57%, 93.00% 1.20%, 94.00% 0.89%, 95.00% 0.62%, 96.00% 0.39%, 97.00% 0.22%, 98.00% 0.10%, 99.00% 0.02%, 100.00% 0.00%)
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
        padding-top: 3%;
        font-weight: 900;
        font-family: sans-serif;
        font-size: xxx-large;
        height: 3.05cm;
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

    .invoice_body{
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
    

/* Table container */
.invoice-table-wrap { padding: 0.5rem 1rem 0rem 1rem; z-index: 1; position: relative; }

/* scroll */
.table-scroll { overflow-x: hidden; max-width: calc(var(--invoice-width) - 2rem); border-top-left-radius: 0.5cm;border-top-right-radius: 0.5cm; background: transparent; }

/* table base */
table.invoice-table { border-collapse: collapse; width: 100%; table-layout: fixed; font-family: Arial, Helvetica, sans-serif; font-size: 12px; }

/* header look (dark-blue) */
table.invoice-table thead th {
  position: relative; user-select: none; vertical-align: middle;
  color:#fff; font-weight:900; border-bottom:3px solid #fff;
  white-space: nowrap; overflow:hidden; box-shadow: inset 0 -2px 0 rgba(255,255,255,0.08);
}
table.invoice-table thead th:first-child { border-top-left-radius: 8px; }
table.invoice-table thead th:last-child  { border-top-right-radius: 8px; }

.col-header.dragging { opacity: 0.6; }
.col-header.drag-over { outline: 2px dashed rgba(0,0,0,0.15); }

.header-cell { display:flex; align-items:center; gap:8px; position:relative; }
.header-title { flex:1; cursor:pointer; display:flex; align-items:center; gap:8px; }
.sort-indicator { font-size:0.9em; opacity:0.95; margin-left:1px; }

.drag-handle { cursor:grab; padding:4px 9px; border-radius:4px; font-size:0.95em; user-select:none; }

/* resize handle */
.resize-handle { position:absolute; right:0; top:0; bottom:0; width:10px; cursor:col-resize; z-index:1; }

/* striping & body rows */
table.invoice-table tbody tr { background: transparent; }
table.invoice-table tbody tr:nth-child(odd) td { background: rgba(135,207,235,0.12); }
table.invoice-table tbody tr:nth-child(even) td { background: rgba(255, 255, 255, 0); }

table.invoice-table tbody td {
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

</style>