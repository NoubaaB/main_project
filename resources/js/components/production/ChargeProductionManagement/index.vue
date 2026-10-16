<template>
<v-card class="cpm-card" elevation="0">
  <!-- Hero -->
  <div class="cpm-hero"><div class="cpm-hero-inner">
    <div class="d-flex align-center ga-4"><div class="cpm-hero-icon"><v-icon size="28" color="white">mdi-link-variant</v-icon></div><div><div class="cpm-hero-title">{{ $t('charge_production_management') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('charge_production_management_desc') }}</div></div></div>
    <div class="cpm-hero-actions">
      <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchCharges" :loading="loading">{{ $t('refresh') }}</v-btn>
    </div>
  </div></div>
  <v-card-text class="pa-4">
    <!-- Analytics Row: Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Chart (left) -->
      <v-col cols="8">
        <div class="cpm-panel-card" style="height:100%">
          <div class="cpm-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#00897B" variant="outlined" rounded="lg" divided>
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
      <v-col cols="4">
        <div class="cpm-panel-card cpm-stats-panel" style="height:100%;overflow-y:auto">
          <div class="cpm-panel-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <!-- Pending count -->
          <div class="d-flex align-center ga-2 mb-3">
            <v-icon size="18" color="#00897B">mdi-clock-outline</v-icon>
            <span class="text-body-2 font-weight-bold">{{ $t('pending_charges') }}:</span>
            <v-chip size="small" color="#00897B" variant="tonal" class="font-weight-bold">{{ colFilteredItems.length }}</v-chip>
          </div>
          <!-- Per-category stats -->
          <div v-for="(stat,si) in categoryStats" :key="si" class="cpm-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="teal" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="teal" bg-color="teal-lighten-4" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="teal">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b class="text-teal">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <!-- Totals -->
          <v-divider class="my-2"></v-divider>
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#00897B">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b class="text-teal text-caption">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
          </div>
        </div>
      </v-col>
    </v-row>
    <!-- Filters -->
    <div class="d-flex ga-2 mb-3 justify-end">
      <v-btn v-if="Object.values(colFilters).some(f=>f&&f.length)" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="colFilters={}">{{ $t('clear_filters') }}</v-btn>
    </div>
    <!-- No data state -->
    <div v-if="!loading && !charges.length" class="d-flex flex-column align-center justify-center pa-12" style="opacity:.5">
      <v-icon size="64" color="teal">mdi-check-decagram</v-icon>
      <div class="text-h6 mt-4">{{ $t('no_pending_charges') }}</div>
    </div>
    <!-- Data Table -->
    <div v-else class="cpm-table-wrap" ref="tableWrap">
      <table class="cpm-table">
        <thead class="cpm-thead"><tr>
          <th v-for="h in headers" :key="h.key" class="cpm-th" :class="{'cpm-th-pin':isPinned(h.key),'cpm-th-dragover':dragOverCol===h.key}" :style="colPinStyle(h.key,3)" :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd" @drop="onDragEnd">
            <div class="cpm-th-inner">
              <v-icon v-if="!h.fixed" size="12" class="drag-handle" color="grey-lighten-1">mdi-drag-vertical</v-icon>
              <span class="cpm-th-title" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span>
              <template v-if="h.key!=='actions'&&h.key!=='expand'">
                <v-btn v-if="sortDir(h.key)" icon variant="text" size="14" @click.stop="toggleSort(h.key)"><v-icon size="12" color="#00897B">mdi-arrow-{{ sortDir(h.key)==='asc'?'up':'down' }}</v-icon></v-btn>
                <span v-if="sortIdx(h.key)" class="sort-badge">{{ sortIdx(h.key) }}</span>
                <v-menu v-model="filterMenus[h.key]" :close-on-content-click="false" location="bottom"><template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="14"><v-icon size="12" :color="colFilters[h.key]&&colFilters[h.key].length?'#00897B':'grey'">mdi-filter</v-icon></v-btn></template><v-card min-width="220" class="rounded-lg pa-2"><v-autocomplete v-model="colFilters[h.key]" :items="filterOpts(h.key)" density="compact" variant="outlined" color="#00897B" hide-details multiple chips closable-chips :placeholder="$t('filter')" class="text-teal"><template #chip="{item,props:p}"><v-chip v-bind="p" size="x-small" color="#00897B" variant="tonal" closable>{{item.title}}</v-chip></template></v-autocomplete></v-card></v-menu>
                <v-btn icon variant="text" size="14" @click.stop="togglePin(h.key)"><v-icon size="10" :color="pinSide(h.key)==='left'?'warning':pinSide(h.key)==='right'?'blue':'grey'">{{ pinSide(h.key)==='left'?'mdi-pin':pinSide(h.key)==='right'?'mdi-pin-outline':'mdi-pin-off-outline' }}</v-icon></v-btn>
              </template>
            </div>
          </th>
        </tr></thead>
        <tbody>
          <template v-if="loading"><tr><td :colspan="headers.length" class="text-center pa-8"><v-progress-circular indeterminate color="#00897B"></v-progress-circular></td></tr></template>
          <template v-else v-for="item in colFilteredItems" :key="item.id">
            <tr class="cpm-row" :class="{'cpm-row-expanded':expanded[item.id]}">
              <td v-for="h in headers" :key="h.key" class="cpm-td" :class="{'cpm-col-pin':isPinned(h.key)}" :style="colPinStyle(h.key,2)">
                <!-- expand -->
                <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                <!-- date -->
                <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                <!-- category -->
                <template v-else-if="h.key==='charge_category_id'"><v-chip size="small" color="#00897B" variant="tonal">{{ catName(item.charge_category_id) }}</v-chip></template>
                <!-- tracking chips -->
                <template v-else-if="h.key==='_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.reception_tracking_number||[])" :key="tn" size="x-small" color="teal" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.reception_tracking_number||[]).length" class="text-grey">-</span></div></template>
                <!-- onp tracking chips -->
                <template v-else-if="h.key==='_onp_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.tracking_onp_numbers||[])" :key="tn" size="x-small" color="deep-orange" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.tracking_onp_numbers||[]).length" class="text-grey">-</span></div></template>
                <!-- weight/price -->
                <template v-else-if="h.key==='total_weight'">{{ main.formatDisplayNumber(item.total_weight) }} Kg</template>
                <template v-else-if="h.key==='total_price'">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</template>
                <!-- actions -->
                <template v-else-if="h.key==='actions'">
                  <v-btn icon size="x-small" variant="tonal" color="info" @click="$router.push({name:'view_charge',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-eye</v-icon><v-tooltip activator="parent" location="top">{{ $t('view') }}</v-tooltip></v-btn>
                  <v-btn size="small" variant="flat" color="#00897B" rounded prepend-icon="mdi-factory" @click="createProduction(item.id)">{{ $t('create_production') }}</v-btn>
                </template>
                <!-- fishery -->
                <template v-else-if="h.key==='_fishery'"><v-chip v-if="item._fishery && item._fishery!=='-'" size="x-small" color="cyan" variant="tonal" prepend-icon="mdi-fish">{{ item._fishery }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- origin -->
                <template v-else-if="h.key==='_origin'"><v-chip v-if="item._origin && item._origin!=='-'" size="x-small" color="amber-darken-2" variant="tonal" prepend-icon="mdi-map-marker">{{ item._origin }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- ship -->
                <template v-else-if="h.key==='_ship'"><v-chip v-if="item._ship && item._ship!=='-'" size="x-small" color="indigo" variant="tonal" prepend-icon="mdi-ferry">{{ item._ship }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- trucks -->
                <template v-else-if="h.key==='_trucks'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="t in (item._trucks && item._trucks!=='-' ? item._trucks.split(', ') : [])" :key="t" size="x-small" color="deep-orange" variant="tonal" prepend-icon="mdi-truck">{{ t }}</v-chip><span v-if="!item._trucks || item._trucks==='-'" class="text-grey">-</span></div></template>
                <!-- generic text columns (suppliers) -->
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
            <!-- Expanded detail rows -->
            <tr v-if="expanded[item.id]"><td :colspan="headers.length" class="pa-0">
              <div class="cpm-expand-content">
                <div v-for="(grp,gi) in groupDetails(item)" :key="gi" class="cpm-detail-group mb-2">
                  <div class="cpm-detail-group-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-tag</v-icon><b>{{ grp.category?grp.category[`name_${main.lang?.code}`]||grp.category.name:'-' }}</b></div>
                  <table class="cpm-detail-table"><thead><tr><th>{{ $t('classification') }}</th><th>{{ $t('product') }}</th><th>{{ $t('product_family') }}</th><th>{{ $t('warehouse') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('unit_price') }}</th><th>{{ $t('total_price') }}</th><th>{{ $t('package_type') }}</th><th>{{ $t('total_package_count') }}</th></tr></thead>
                  <tbody><tr v-for="dc in grp.items" :key="dc.id">
                    <td><v-chip size="x-small" color="#00897B" variant="tonal">{{ dc.stockable_type||$t('text') }}</v-chip></td>
                    <td class="font-weight-bold">{{ articleName(dc) }}</td><td>{{ articleFamily(dc) }}</td>
                    <td><v-chip v-if="dc.warehouse" size="x-small" variant="tonal">{{ dc.warehouse[`name_${main.lang?.code}`]||dc.warehouse.name }}</v-chip><span v-else>{{ $t('non_stockable') }}</span></td>
                    <td>{{ main.formatDisplayNumber(dc.weight) }}</td>
                    <td>{{ main.formatDisplayNumber(dc.unit_price) }}</td><td>{{ main.formatDisplayNumber(dc.total_price) }} {{ main.currency }}</td>
                    <td>{{ dc.package_type?.name || '-' }}</td>
                    <td><v-chip v-if="dc.nbr_box" size="x-small" color="teal" variant="tonal"><v-icon size="12" class="mr-1">mdi-package-variant</v-icon>{{ dc.nbr_box }}</v-chip><span v-else>-</span></td>
                  </tr></tbody>
                  <tfoot><tr class="cpm-detail-foot"><td colspan="4" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalW) }}</td><td></td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalP) }} {{ main.currency }}</td><td colspan="2"></td></tr></tfoot>
                  </table>
                </div>
              </div>
            </td></tr>
          </template>
        </tbody>
        <tfoot class="cpm-tfoot"><tr><td v-for="h in headers" :key="'foot-'+h.key" class="cpm-td text-center font-weight-bold"><template v-if="h.key==='total_weight'">Σ {{ main.formatDisplayNumber(totalWeight) }} Kg</template><template v-else-if="h.key==='total_price'">Σ {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</template></td></tr></tfoot>
      </table>
    </div>
  </v-card-text>
</v-card>
</template>
<script>
import script from './script.js';
export default {
  ...script,
  methods:{...script.methods,colPinStyle(key,z){const side=this.pinSide(key);if(!side)return{};const bg=this.main?.is_dark?'#1c1f28':'#F5F5F5';if(side==='left')return{position:'sticky',left:this.getPinLeft(key)+'px',zIndex:z,background:bg};return{position:'sticky',right:this.getPinRight(key)+'px',zIndex:z,background:bg};},measureColWidths(){this.$nextTick(()=>{const ths=this.$refs.tableWrap?.querySelectorAll('thead th');if(!ths)return;const w={};ths.forEach((th,i)=>{const key=this.headers[i]?.key;if(key)w[key]=th.offsetWidth});this.colWidths=w})}},
  watch:{...script.watch,headers(){this.measureColWidths()},pinState:{handler(){this.measureColWidths()},deep:true}},
  mounted(){script.mounted.call(this);this.$nextTick(()=>this.measureColWidths())},
  beforeUnmount(){if(script.beforeUnmount)script.beforeUnmount.call(this)},
  updated(){this.measureColWidths()},
};
</script>
<style scoped>
.cpm-card{border-radius:20px!important;font-family:'Inter',sans-serif}
.cpm-hero{background:linear-gradient(135deg,#004D40 0%,#00695C 40%,#00897B 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;min-height:100px}
.cpm-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.cpm-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.15)}
.cpm-hero-title{font-size:19px;font-weight:800;letter-spacing:.3px}
.cpm-hero-actions{display:flex;gap:8px;flex-wrap:wrap}
.cpm-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.cpm-table{width:100%;border-collapse:collapse;font-size:13px}
.cpm-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.cpm-th{padding:10px 12px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s}
.cpm-td{padding:8px 12px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.cpm-col-pin{position:sticky!important;z-index:2!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.cpm-th-pin{position:sticky!important;z-index:5!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
:deep(.v-theme--dark) .cpm-col-pin,
:deep(.v-theme--dark) .cpm-th-pin,
.v-theme--dark .cpm-col-pin,
.v-theme--dark .cpm-th-pin {
  background: #1c1f28 !important;
  color: #f1f5f9 !important;
  box-shadow: 2px 0 4px -2px rgba(0,0,0,.4) !important;
}
.cpm-th-dragover{background:rgba(0,137,123,.08)!important}
.cpm-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.cpm-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#00897B;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.cpm-row:hover{background:rgba(0,137,123,.04)}
.cpm-row-expanded{background:rgba(0,137,123,.06)}
.cpm-tfoot td{padding:10px 12px;border-top:2px solid rgba(0,137,123,.2);color:#00695C;font-size:14px}
:deep(.v-theme--dark) .cpm-tfoot td,
.v-theme--dark .cpm-tfoot td {
  color: #26A69A !important;
}
.cpm-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.cpm-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow:hidden}
.cpm-detail-group-header{padding:8px 14px;background:rgba(0,137,123,.06);font-size:13px}
.cpm-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.cpm-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.cpm-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.cpm-detail-foot td{padding:8px 10px;background:rgba(0,137,123,.04);border-top:1px solid rgba(0,137,123,.15)}
.cpm-panel-card{border:1.5px solid rgba(0,137,123,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.cpm-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.cpm-stats-panel{max-height:400px}
.cpm-stat-item{padding:8px 10px;border-radius:10px;background:rgba(0,137,123,.03);border:1px solid rgba(0,137,123,.06)}
</style>
