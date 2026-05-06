<template>
<div>
  <v-card-text class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="3">
        <div class="li-panel-card">
          <div class="li-panel-header"><v-icon size="16" color="#6D4C41" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="brown" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="6">
        <div class="li-panel-card" style="height:100%">
          <div class="li-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#6D4C41" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#6D4C41" variant="outlined" rounded="lg" divided>
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
        <div class="li-panel-card li-stats-panel" style="height:100%;overflow-y:auto">
          <div class="li-panel-header"><v-icon size="16" color="#6D4C41" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <!-- Per-category stats -->
          <div v-for="(stat,si) in categoryStats" :key="si" class="li-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#6D4C41" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="#6D4C41" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#6D4C41" bg-color="brown-lighten-4" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="#6D4C41">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color:#6D4C41">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <!-- Totals -->
          <v-divider class="my-2"></v-divider>
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#6D4C41">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b style="color:#6D4C41" class="text-caption">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Action buttons -->
    <div class="d-flex ga-2 mb-3 justify-space-between">
      <div class="d-flex ga-2">
        <v-btn variant="tonal" color="#6D4C41" rounded size="small" prepend-icon="mdi-refresh" @click="fetchInventories" :loading="loading">{{ $t('refresh') }}</v-btn>
        <v-btn v-if="selected.length" variant="tonal" color="error" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete_selected') }} ({{ selected.length }})</v-btn>
      </div>
      <div class="d-flex ga-2">
        <v-btn v-if="Object.values(colFilters).some(f=>f&&f.length)" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="colFilters={}">{{ $t('clear_filters') }}</v-btn>
        <v-btn icon variant="tonal" color="success" size="small" @click="exportExcel"><v-icon size="18">mdi-file-excel-outline</v-icon></v-btn>
      </div>
    </div>

    <!-- Data Table -->
    <div class="li-table-wrap" ref="tableWrap">
      <table class="li-table">
        <thead class="li-thead"><tr>
          <th class="li-th li-th-pin" style="min-width:50px;left:0"><v-checkbox-btn v-model="selectAll" color="#6D4C41" density="compact" hide-details></v-checkbox-btn></th>
          <th v-for="h in headers" :key="h.key" class="li-th" :class="{'li-th-pin':isPinned(h.key),'li-th-dragover':dragOverCol===h.key}" :style="colPinStyle(h.key,3)" :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd" @drop="onDragEnd">
            <div class="li-th-inner">
              <v-icon v-if="!h.fixed" size="12" class="drag-handle" color="grey-lighten-1">mdi-drag-vertical</v-icon>
              <span class="li-th-title" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span>
              <template v-if="h.key!=='actions'&&h.key!=='expand'">
                <v-btn v-if="sortDir(h.key)" icon variant="text" size="14" @click.stop="toggleSort(h.key)"><v-icon size="12" color="#6D4C41">mdi-arrow-{{ sortDir(h.key)==='asc'?'up':'down' }}</v-icon></v-btn>
                <span v-if="sortIdx(h.key)" class="sort-badge">{{ sortIdx(h.key) }}</span>
                <v-menu v-model="filterMenus[h.key]" :close-on-content-click="false" location="bottom"><template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="14"><v-icon size="12" :color="colFilters[h.key]&&colFilters[h.key].length?'#6D4C41':'grey'">mdi-filter</v-icon></v-btn></template><v-card min-width="220" class="rounded-lg pa-2"><v-autocomplete v-model="colFilters[h.key]" :items="filterOpts(h.key)" density="compact" variant="outlined" color="#6D4C41" hide-details multiple chips closable-chips :placeholder="$t('filter')" class="text-brown"><template #chip="{item,props:p}"><v-chip v-bind="p" size="x-small" color="#6D4C41" variant="tonal" closable>{{item.title}}</v-chip></template></v-autocomplete></v-card></v-menu>
                <v-btn icon variant="text" size="14" @click.stop="togglePin(h.key)"><v-icon size="10" :color="pinSide(h.key)==='left'?'warning':pinSide(h.key)==='right'?'#6D4C41':'grey'">{{ pinSide(h.key)==='left'?'mdi-pin':pinSide(h.key)==='right'?'mdi-pin-outline':'mdi-pin-off-outline' }}</v-icon></v-btn>
              </template>
            </div>
          </th>
        </tr></thead>
        <tbody>
          <template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="#6D4C41"></v-progress-circular></td></tr></template>
          <template v-else v-for="item in colFilteredItems" :key="item.id">
            <tr class="li-row" :class="{'li-row-expanded':expanded[item.id]}">
              <td class="li-td li-th-pin" style="left:0"><v-checkbox-btn v-model="selected" :value="item.id" color="#6D4C41" density="compact" hide-details></v-checkbox-btn></td>
              <td v-for="h in headers" :key="h.key" class="li-td" :class="{'li-col-pin':isPinned(h.key)}" :style="colPinStyle(h.key,2)">
                <!-- expand -->
                <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                <!-- date -->
                <template v-else-if="h.key==='date'">{{ (item.date||'').slice(0,10) }}</template>
                <!-- tracking chips -->
                <template v-else-if="h.key==='_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.inventory_tracking_number||[])" :key="tn" size="x-small" color="#6D4C41" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.inventory_tracking_number||[]).length" class="text-grey">-</span></div></template>
                <!-- weight/price -->
                <template v-else-if="h.key==='total_weight'">{{ main.formatDisplayNumber(item.total_weight) }} Kg</template>
                <template v-else-if="h.key==='total_price'">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</template>
                <!-- details count -->
                <template v-else-if="h.key==='_details_count'"><v-chip size="x-small" color="#6D4C41" variant="tonal">{{ item._details_count }}</v-chip></template>
                <!-- actions -->
                <template v-else-if="h.key==='actions'">
                  <v-btn icon size="x-small" variant="tonal" color="warning" @click="confirmDelete(item.id)" class="mr-1"><v-icon size="14">mdi-delete</v-icon></v-btn>
                </template>
                <!-- generic -->
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
            <!-- Expanded detail rows -->
            <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
              <div class="li-expand-content">
                <div v-for="(grp,gi) in groupDetails(item)" :key="gi" class="li-detail-group mb-2">
                  <div class="li-detail-group-header"><v-icon size="16" color="#6D4C41" class="mr-1">mdi-tag</v-icon><b>{{ grp.category[`name_${lang}`]||grp.category.name_fr||grp.category.name_en||'-' }}</b></div>
                  <table class="li-detail-table"><thead><tr>
                    <th>{{ $t('type_article') }}</th>
                    <th>{{ $t('warehouse') }}</th>
                    <th>{{ $t('warehouse_operation') }}</th>
                    <th>{{ $t('weight') }} (Kg)</th>
                    <th>{{ $t('unit_price') }}</th>
                    <th>{{ $t('total_price') }}</th>
                  </tr></thead>
                  <tbody><tr v-for="di in grp.items" :key="di.id">
                    <td class="font-weight-bold">{{ articleName(di) }}</td>
                    <td><v-chip v-if="di.warehouse" size="x-small" variant="tonal">{{ di.warehouse.name }}</v-chip><span v-else>-</span></td>
                    <td><v-chip v-if="di.warehouse_operation" :color="di.warehouse_operation==='stock_in'?'success':'warning'" size="x-small" variant="tonal">{{ di.warehouse_operation }}</v-chip><span v-else>-</span></td>
                    <td>{{ main.formatDisplayNumber(di.weight) }}</td>
                    <td>{{ main.formatDisplayNumber(di.unit_price) }}</td>
                    <td>{{ main.formatDisplayNumber(di.total_price) }}</td>
                  </tr></tbody>
                  <tfoot><tr class="li-detail-foot"><td colspan="3" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalW) }}</td><td></td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalP) }}</td></tr></tfoot>
                  </table>
                </div>
              </div>
            </td></tr>
          </template>
        </tbody>
        <tfoot class="li-tfoot"><tr><td class="li-td"></td><td v-for="h in headers" :key="'foot-'+h.key" class="li-td text-center font-weight-bold"><template v-if="h.key==='total_weight'">Σ {{ main.formatDisplayNumber(totalWeight) }} Kg</template><template v-else-if="h.key==='total_price'">Σ {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</template></td></tr></tfoot>
      </table>
    </div>
  </v-card-text>
  <v-dialog v-model="deleteDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_message') }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions></v-card></v-dialog>
</div>
</template>
<script>
import script from './listScript.js';
export default {
  ...script,
  computed:{...script.computed,selectAll:{get(){return this.selected.length===this.colFilteredItems.length&&this.colFilteredItems.length>0},set(v){this.selected=v?this.colFilteredItems.map(i=>i.id):[]}}},
  methods:{...script.methods,colPinStyle(key,z){const side=this.pinSide(key);if(!side)return{};if(side==='left')return{position:'sticky',left:this.getPinLeft(key)+'px',zIndex:z,background:'#F5F5F5'};return{position:'sticky',right:this.getPinRight(key)+'px',zIndex:z,background:'#F5F5F5'};},measureColWidths(){this.$nextTick(()=>{const ths=this.$refs.tableWrap?.querySelectorAll('thead th');if(!ths)return;const w={};ths.forEach((th,i)=>{const key=i===0?'__checkbox':this.headers[i-1]?.key;if(key)w[key]=th.offsetWidth});this.colWidths=w})}},
  watch:{...script.watch,headers(){this.measureColWidths()},pinState:{handler(){this.measureColWidths()},deep:true}},
  mounted(){script.mounted.call(this);this.$nextTick(()=>this.measureColWidths())},
  beforeUnmount(){if(script.beforeUnmount)script.beforeUnmount.call(this)},
  updated(){this.measureColWidths()},
};
</script>
<style scoped>
.li-panel-card{border:1.5px solid rgba(109,76,65,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.li-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center;color:#6D4C41}
.li-stats-panel{max-height:400px}
.li-stat-item{padding:8px 10px;border-radius:10px;background:rgba(109,76,65,.03);border:1px solid rgba(109,76,65,.06)}
.li-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.li-table{width:100%;border-collapse:collapse;font-size:13px}
.li-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.li-th{padding:10px 12px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s}
.li-td{padding:8px 12px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.li-col-pin{position:sticky!important;z-index:2!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.li-th-pin{position:sticky!important;z-index:5!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.li-th-dragover{background:rgba(109,76,65,.08)!important}
.li-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.li-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#6D4C41;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.li-row:hover{background:rgba(109,76,65,.04)}
.li-row-expanded{background:rgba(109,76,65,.06)}
.li-tfoot td{padding:10px 12px;border-top:2px solid rgba(109,76,65,.2);color:#6D4C41;font-size:14px}
.li-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.li-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow:hidden}
.li-detail-group-header{padding:8px 14px;background:rgba(109,76,65,.06);font-size:13px}
.li-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.li-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.li-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.li-detail-foot td{padding:8px 10px;background:rgba(109,76,65,.04);border-top:1px solid rgba(109,76,65,.15)}
</style>
