<template>
<v-card class="lc-card" elevation="0">
  <div class="lc-hero"><div class="lc-hero-inner">
    <div class="d-flex align-center ga-4"><div class="lc-hero-icon"><v-icon size="28" color="white">mdi-file-document-multiple-outline</v-icon></div><div><div class="lc-hero-title">{{ $t('supplier_invoices') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('datatable_supplier_invoice') }}</div></div></div>
    <div class="lc-hero-actions">
      <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchInvoices" :loading="loading">{{ $t('refresh') }}</v-btn>
      <v-btn v-if="selected.length" variant="tonal" color="white" rounded size="small" prepend-icon="mdi-eye" @click="viewSelected">{{ $t('view_invoices') }} ({{ selected.length }})</v-btn>
      <v-btn v-if="selected.length" variant="tonal" color="error" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete_selected') }} ({{ selected.length }})</v-btn>
    </div>
  </div></div>
  <v-card-text class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="3">
        <div class="lc-panel-card">
          <div class="lc-panel-header"><v-icon size="16" color="info" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="blue" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="6">
        <div class="lc-panel-card" style="height:100%">
          <div class="lc-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="info" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="info" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries[0].data.length" type="line" :height="260" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="3">
        <div class="lc-panel-card lc-stats-panel" style="height:100%;overflow-y:auto">
          <div class="lc-panel-header"><v-icon size="16" color="info" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <!-- Per-supplier stats -->
          <div v-for="(stat,si) in supplierStats" :key="si" class="lc-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="info" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="blue" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="blue" bg-color="blue-lighten-4" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="blue">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b class="text-blue">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <!-- Summary totals -->
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-2"><v-icon size="14" color="info">mdi-file-document-multiple</v-icon><span class="text-caption text-medium-emphasis">{{ $t('supplier_invoices') }}:</span><v-chip size="x-small" color="info" variant="tonal"><b>{{ colFilteredItems.length }}</b></v-chip></div>
            <div class="d-flex align-center ga-2"><v-icon size="14" color="teal">mdi-package-variant</v-icon><span class="text-caption text-medium-emphasis">{{ $t('charges') }}:</span><v-chip size="x-small" color="teal" variant="tonal"><b>{{ totalChargesCount }}</b></v-chip></div>
            <div class="d-flex align-center ga-2"><v-icon size="14" color="purple">mdi-factory</v-icon><span class="text-caption text-medium-emphasis">{{ $t('productions') }}:</span><v-chip size="x-small" color="purple" variant="tonal"><b>{{ totalProductionsCount }}</b></v-chip></div>
          </div>
          <v-divider class="my-2"></v-divider>
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="info">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b class="text-info text-caption">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
          </div>
        </div>
      </v-col>
    </v-row>
    <div class="d-flex ga-2 mb-3 justify-end">
      <v-btn v-if="Object.values(colFilters).some(f=>f&&f.length)" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="colFilters={}">{{ $t('clear_filters') }}</v-btn>
      <v-btn icon variant="tonal" color="success" size="small" @click="exportExcel"><v-icon size="18">mdi-file-excel-outline</v-icon></v-btn>
    </div>
    <div class="lc-table-wrap" ref="tableWrap">
      <table class="lc-table">
        <thead class="lc-thead"><tr>
          <th class="lc-th lc-th-pin" style="min-width:50px;left:0"><v-checkbox-btn v-model="selectAll" color="info" density="compact" hide-details></v-checkbox-btn></th>
          <th v-for="h in headers" :key="h.key" class="lc-th" :class="{'lc-th-pin':isPinned(h.key),'lc-th-dragover':dragOverCol===h.key}" :style="colPinStyle(h.key,3)" :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd" @drop="onDragEnd">
            <div class="lc-th-inner">
              <v-icon v-if="!h.fixed" size="12" class="drag-handle" color="grey-lighten-1">mdi-drag-vertical</v-icon>
              <span class="lc-th-title" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span>
              <template v-if="h.key!=='actions'&&h.key!=='expand'">
                <v-btn v-if="sortDir(h.key)" icon variant="text" size="14" @click.stop="toggleSort(h.key)"><v-icon size="12" color="info">mdi-arrow-{{ sortDir(h.key)==='asc'?'up':'down' }}</v-icon></v-btn>
                <span v-if="sortIdx(h.key)" class="sort-badge">{{ sortIdx(h.key) }}</span>
                <v-menu v-model="filterMenus[h.key]" :close-on-content-click="false" location="bottom"><template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="14"><v-icon size="12" :color="colFilters[h.key]&&colFilters[h.key].length?'info':'grey'">mdi-filter</v-icon></v-btn></template><v-card min-width="220" class="rounded-lg pa-2"><v-autocomplete v-model="colFilters[h.key]" :items="filterOpts(h.key)" density="compact" variant="outlined" color="info" hide-details multiple chips closable-chips :placeholder="$t('filter')" class="text-info"><template #chip="{item,props:p}"><v-chip v-bind="p" size="x-small" color="info" variant="tonal" closable>{{item.title}}</v-chip></template></v-autocomplete></v-card></v-menu>
                <v-btn icon variant="text" size="14" @click.stop="togglePin(h.key)"><v-icon size="10" :color="pinSide(h.key)==='left'?'warning':pinSide(h.key)==='right'?'blue':'grey'">{{ pinSide(h.key)==='left'?'mdi-pin':pinSide(h.key)==='right'?'mdi-pin-outline':'mdi-pin-off-outline' }}</v-icon></v-btn>
              </template>
            </div>
          </th>
        </tr></thead>
        <tbody>
          <template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="info"></v-progress-circular></td></tr></template>
          <template v-else v-for="item in colFilteredItems" :key="item.id">
            <tr class="lc-row" :class="{'lc-row-expanded':expanded[item.id]}">
              <td class="lc-td lc-th-pin" style="left:0"><v-checkbox-btn v-model="selected" :value="item.id" color="info" density="compact" hide-details></v-checkbox-btn></td>
              <td v-for="h in headers" :key="h.key" class="lc-td" :class="{'lc-col-pin':isPinned(h.key)}" :style="colPinStyle(h.key,2)">
                <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                <template v-else-if="h.key==='_tracking_numbers'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.tracking_numbers||[])" :key="tn" size="x-small" color="teal" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.tracking_numbers||[]).length" class="text-grey">-</span></div></template>
                <template v-else-if="h.key==='_suppliers'">{{ item._suppliers }}</template>
                <template v-else-if="h.key==='total_weight'">{{ main.formatDisplayNumber(item.total_weight) }} Kg</template>
                <template v-else-if="h.key==='total_price'">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</template>
                <template v-else-if="h.key==='print_counter'"><v-chip size="small" :color="item.print_counter>0?'success':'grey'" variant="tonal"><v-icon size="14" class="mr-1">mdi-printer</v-icon>{{ item.print_counter }}</v-chip></template>
                <template v-else-if="h.key==='actions'">
                  <v-btn icon size="x-small" variant="tonal" color="info" @click="viewSelected(); selected=[item.id]" class="mr-1" v-if="false"></v-btn>
                  <v-menu>
                    <template v-slot:activator="{ props }"><v-btn icon="mdi-dots-vertical" size="x-small" variant="outlined" color="info" v-bind="props"></v-btn></template>
                    <v-list slim density="compact">
                      <v-list-item prepend-icon="mdi-eye" @click="$router.push({name:'view_supplier_invoice',query:{ids:[item.id]}})"><v-list-item-title>{{$t('view_item')}}</v-list-item-title></v-list-item>
                      <v-divider></v-divider>
                      <v-list-item prepend-icon="mdi-delete" @click="confirmDelete(item.id)"><v-list-item-title>{{$t('delete_item')}}</v-list-item-title></v-list-item>
                    </v-list>
                  </v-menu>
                </template>
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
            <!-- Expanded detail -->
            <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
              <div class="lc-expand-content">
                <!-- Charges sub-table -->
                <div v-if="(item.charges||[]).length" class="lc-detail-group mb-3">
                  <div class="lc-detail-group-header"><v-icon size="16" color="info" class="mr-1">mdi-package-variant</v-icon><b>{{ $t('charges') }} ({{ item.charges.length }})</b></div>
                  <table class="lc-detail-table"><thead><tr>
                    <th>{{ $t('date') }}</th><th>{{ $t('charge_category') }}</th><th>{{ $t('reception_tracking_number') }}</th><th>{{ $t('suppliers') }}</th><th>{{ $t('fishery') }}</th><th>{{ $t('origin') }}</th><th>{{ $t('ship') }}</th><th>{{ $t('trucks') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('total_price') }}</th>
                  </tr></thead>
                  <tbody><tr v-for="ch in item.charges" :key="ch.id">
                    <td>{{ main.formateDate(ch.date) }}</td>
                    <td><v-chip size="x-small" color="info" variant="tonal">{{ catName(ch) }}</v-chip></td>
                    <td><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (ch.reception_tracking_number||[])" :key="tn" size="x-small" color="teal" variant="tonal">{{ tn }}</v-chip><span v-if="!(ch.reception_tracking_number||[]).length" class="text-grey">-</span></div></td>
                    <td>{{ supplierName(ch) }}</td>
                    <td>{{ ch.fishery ? (ch.fishery[`name_${main.lang?.code}`]||ch.fishery.name) : '-' }}</td>
                    <td>{{ ch.origin ? (ch.origin[`name_${main.lang?.code}`]||ch.origin.name) : '-' }}</td>
                    <td>{{ ch.ship ? (ch.ship[`name_${main.lang?.code}`]||ch.ship.name) : '-' }}</td>
                    <td>{{ (ch.trucks||[]).map(t=>t.registration_plate||t.name).join(', ') || '-' }}</td>
                    <td>{{ main.formatDisplayNumber(ch.total_weight) }}</td>
                    <td>{{ main.formatDisplayNumber(ch.total_price) }} {{ main.currency }}</td>
                  </tr></tbody>
                  <tfoot><tr class="lc-detail-foot"><td colspan="8" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(item.charges.reduce((s,c)=>s+Number(c.total_weight||0),0)) }}</td><td class="font-weight-bold">{{ main.formatDisplayNumber(item.charges.reduce((s,c)=>s+Number(c.total_price||0),0)) }} {{ main.currency }}</td></tr></tfoot>
                  </table>
                </div>
                <!-- Productions sub-table -->
                <div v-if="(item.productions||[]).length" class="lc-detail-group mb-2">
                  <div class="lc-detail-group-header"><v-icon size="16" color="purple" class="mr-1">mdi-factory</v-icon><b>{{ $t('productions') }} ({{ item.productions.length }})</b></div>
                  <table class="lc-detail-table"><thead><tr>
                    <th>{{ $t('date') }}</th><th>{{ $t('production_category') }}</th><th>{{ $t('production_tracking_number') }}</th><th>{{ $t('suppliers') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('total_price') }}</th>
                  </tr></thead>
                  <tbody><tr v-for="pr in item.productions" :key="pr.id">
                    <td>{{ main.formateDate(pr.date) }}</td>
                    <td><v-chip size="x-small" color="purple" variant="tonal">{{ prodCatName(pr) }}</v-chip></td>
                    <td><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (pr.production_tracking_number||[])" :key="tn" size="x-small" color="deep-orange" variant="tonal">{{ tn }}</v-chip><span v-if="!(pr.production_tracking_number||[]).length" class="text-grey">-</span></div></td>
                    <td>{{ (pr.suppliers||[]).map(s=>s.name).join(', ') || '-' }}</td>
                    <td>{{ main.formatDisplayNumber(pr.total_weight) }}</td>
                    <td>{{ main.formatDisplayNumber(pr.total_price) }} {{ main.currency }}</td>
                  </tr></tbody>
                  <tfoot><tr class="lc-detail-foot"><td colspan="4" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(item.productions.reduce((s,p)=>s+Number(p.total_weight||0),0)) }}</td><td class="font-weight-bold">{{ main.formatDisplayNumber(item.productions.reduce((s,p)=>s+Number(p.total_price||0),0)) }} {{ main.currency }}</td></tr></tfoot>
                  </table>
                </div>
              </div>
            </td></tr>
          </template>
        </tbody>
        <tfoot class="lc-tfoot"><tr><td class="lc-td"></td><td v-for="h in headers" :key="'foot-'+h.key" class="lc-td text-center font-weight-bold"><template v-if="h.key==='total_weight'">Σ {{ main.formatDisplayNumber(totalWeight) }} Kg</template><template v-else-if="h.key==='total_price'">Σ {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</template></td></tr></tfoot>
      </table>
    </div>
  </v-card-text>
  <v-dialog v-model="deleteDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ deleteBulk?$t('confirm_bulk_delete_message',{count:selected.length}):$t('confirm_delete_charge_message') }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions></v-card></v-dialog>

  <!-- Summary Snackbar -->
  <v-snackbar v-for="(sb, i) in main.snackbars" :key="i" v-model="sb.show" :color="sb.color" :timeout="3000" location="bottom right">{{ sb.message }}</v-snackbar>
</v-card>
</template>
<script>
import script from './script.js';
export default {
  ...script,
  computed:{...script.computed,selectAll:{get(){return this.selected.length===this.colFilteredItems.length&&this.colFilteredItems.length>0},set(v){this.selected=v?this.colFilteredItems.map(i=>i.id):[]}}},
  methods:{...script.methods,colPinStyle(key,z){const side=this.pinSide(key);if(!side)return{};if(side==='left')return{position:'sticky',left:this.getPinLeft(key)+'px',zIndex:z,background:'#F5F5F5'};return{position:'sticky',right:this.getPinRight(key)+'px',zIndex:z,background:'#F5F5F5'};},measureColWidths(){this.$nextTick(()=>{const ths=this.$refs.tableWrap?.querySelectorAll('thead th');if(!ths)return;const w={};ths.forEach((th,i)=>{const key=i===0?'__checkbox':this.headers[i-1]?.key;if(key)w[key]=th.offsetWidth});this.colWidths=w})}},
  watch:{...script.watch||{},headers(){this.measureColWidths()},pinState:{handler(){this.measureColWidths()},deep:true}},
  mounted(){script.mounted.call(this);this.$nextTick(()=>this.measureColWidths())},
  beforeUnmount(){if(script.beforeUnmount)script.beforeUnmount.call(this)},
  updated(){this.measureColWidths()},
};
</script>
<style scoped>
.lc-card{border-radius:20px!important;font-family:'Inter',sans-serif}
.lc-hero{background:linear-gradient(135deg,#0D47A1 0%,#1565C0 40%,#42A5F5 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;min-height:100px}
.lc-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.lc-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.15)}
.lc-hero-title{font-size:19px;font-weight:800;letter-spacing:.3px}
.lc-hero-actions{display:flex;gap:8px;flex-wrap:wrap}
.lc-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.lc-table{width:100%;border-collapse:collapse;font-size:13px}
.lc-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.lc-th{padding:10px 12px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s}
.lc-td{padding:8px 12px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.lc-col-pin{position:sticky!important;z-index:2!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.lc-th-pin{position:sticky!important;z-index:5!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.lc-th-dragover{background:rgba(25,118,210,.08)!important}
.lc-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.lc-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#1976D2;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.lc-row:hover{background:rgba(25,118,210,.04)}
.lc-row-expanded{background:rgba(25,118,210,.06)}
.lc-tfoot td{padding:10px 12px;border-top:2px solid rgba(25,118,210,.2);color:#1565C0;font-size:14px}
.lc-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.lc-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow:hidden}
.lc-detail-group-header{padding:8px 14px;background:rgba(25,118,210,.06);font-size:13px}
.lc-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.lc-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.lc-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.lc-detail-foot td{padding:8px 10px;background:rgba(25,118,210,.04);border-top:1px solid rgba(25,118,210,.15)}
.lc-panel-card{border:1.5px solid rgba(25,118,210,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.lc-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.lc-stats-panel{max-height:400px}
.lc-stat-item{padding:8px 10px;border-radius:10px;background:rgba(25,118,210,.03);border:1px solid rgba(25,118,210,.06)}
</style>
