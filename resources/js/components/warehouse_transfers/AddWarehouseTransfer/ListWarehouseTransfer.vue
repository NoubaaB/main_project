<template>
<v-card class="lwt-card" elevation="0">
  <v-card-text class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="3">
        <div class="lwt-panel-card">
          <div class="lwt-panel-header"><v-icon size="16" color="#F9A825" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="yellow" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="6">
        <div class="lwt-panel-card" style="height:100%">
          <div class="lwt-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#F9A825" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#F9A825" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries.length>1 || (chartSeries[0] && chartSeries[0].data.length)" type="area" :height="300" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="3">
        <div class="lwt-panel-card lwt-stats-panel" style="height:100%;overflow-y:auto">
          <div class="lwt-panel-header"><v-icon size="16" color="#F9A825" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <div v-for="(stat,si) in categoryStats" :key="si" class="lwt-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#F9A825" variant="tonal" class="font-weight-bold">{{ stat.name }} (<span class="fnm-currency-num">{{ stat.count }}</span>)</v-chip>
              <v-chip size="x-small" color="#F9A825" variant="elevated" class="font-weight-bold fnm-currency-num">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#F9A825" rounded height="6" class="mb-1"></v-progress-linear>
            <div class="d-flex align-center ga-3 text-caption">
              <div><v-icon size="14" color="#F9A825">mdi-weight-kilogram</v-icon> <span class="fnm-currency-num">{{ main.formatDisplayNumber(stat.weight) }}</span> Kg</div>
            </div>
          </div>
          <div v-if="!categoryStats.length" class="text-center text-caption text-medium-emphasis pa-4">{{ $t('no_data_available') }}</div>
          <v-divider class="my-2" color="#F9A825"></v-divider>
          <div class="d-flex align-center ga-1"><v-icon size="14" color="#F9A825">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><span class="text-caption font-weight-bold fnm-currency-num" style="color:#F9A825">{{ main.formatDisplayNumber(totalWeight) }} Kg</span></div>
        </div>
      </v-col>
    </v-row>

    <!-- Data Table -->
    <div class="lwt-table-wrap">
      <div class="lwt-table-inner" style="overflow-x:auto">
        <table class="lwt-detail-table" ref="mainTable">
          <thead>
            <tr>
              <th class="text-center" :style="{width:'50px',position:'sticky',left:0,zIndex:3,background:main?.is_dark?'#1c1f28':'#F5F5F5'}"><v-checkbox-btn v-model="selectAll" color="#F9A825" density="compact" hide-details></v-checkbox-btn></th>
              <th v-for="h in headers" :key="h.key"
                :class="['text-center',{pinned:isPinned(h.key)}]"
                :style="isPinned(h.key)?{position:'sticky',[pinSide(h.key)]:pinSide(h.key)==='left'?getPinLeft(h.key)+'px':getPinRight(h.key)+'px',zIndex:2,background:main?.is_dark?'#1c1f28':'#F5F5F5'}:{}"
                :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd()"
              >
                <div class="d-flex align-center justify-center ga-1">
                  <span class="cursor-pointer" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span>
                  <v-icon v-if="sortDir(h.key)==='asc'" size="14" color="#F9A825">mdi-arrow-up</v-icon>
                  <v-icon v-else-if="sortDir(h.key)==='desc'" size="14" color="#F9A825">mdi-arrow-down</v-icon>
                  <TableColumnFilter
                    v-if="h.sortable!==false&&h.key!=='expand'"
                    v-model="colFilters[h.key]"
                    :options="filterOpts(h.key)"
                    :counts="filterCounts(h.key)"
                    :title="h.title"
                    color="#F9A825"
                  />
                  <v-btn v-if="!h.fixed" icon variant="text" size="x-small" :color="isPinned(h.key)?'#F9A825':'grey'" @click="togglePin(h.key)"><v-icon size="14">mdi-pin{{ isPinned(h.key)?'':'-off' }}</v-icon></v-btn>
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <template v-for="item in colFilteredItems" :key="item.id">
              <tr class="lwt-row" :class="{selected:selected.includes(item.id)}">
                <td class="text-center" :style="{position:'sticky',left:0,zIndex:1,background:main?.is_dark?'#1c1f28':'#F5F5F5'}"><v-checkbox-btn v-model="selected" :value="item.id" color="#F9A825" density="compact" hide-details></v-checkbox-btn></td>
                <td v-for="h in headers" :key="h.key" class="text-center"
                  :class="{pinned:isPinned(h.key)}"
                  :style="isPinned(h.key)?{position:'sticky',[pinSide(h.key)]:pinSide(h.key)==='left'?getPinLeft(h.key)+'px':getPinRight(h.key)+'px',zIndex:1,background:main?.is_dark?'#1c1f28':'#F5F5F5'}:{}"
                >
                  <template v-if="h.key==='expand'"><v-btn icon variant="text" size="x-small" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                  <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                  <template v-else-if="h.key==='total_weight'"><span class="font-weight-bold fnm-currency-num" style="color:#F9A825">{{ main.formatDisplayNumber(item.total_weight) }}</span></template>
                  <template v-else-if="h.key==='_tracking'"><v-chip v-for="(tn,ti) in (item.transfer_tracking_number||[])" :key="ti" size="x-small" color="#F9A825" variant="tonal" class="mr-1 fnm-currency-num">{{ tn }}</v-chip><span v-if="!(item.transfer_tracking_number||[]).length" class="text-grey">-</span></template>
                  <template v-else-if="h.key==='actions'">
                    <v-btn v-if="auth.hasPermission('warehouse_transfers.edit')" icon variant="text" size="x-small" color="#F9A825" @click="$router.push({ name: 'edit_warehouse_transfer', params: { id: item.id } })"><v-icon size="16">mdi-pencil</v-icon></v-btn>
                    <v-btn v-if="auth.hasPermission('warehouse_transfers.delete')" icon variant="text" size="x-small" color="error" @click="confirmDelete(item.id)"><v-icon size="16">mdi-delete</v-icon></v-btn>
                    <v-btn icon variant="text" size="x-small" color="blue-grey" @click="openAuditLog(item.id)"><v-icon size="16">mdi-history</v-icon><v-tooltip activator="parent" location="top">{{ $t('audit_log') }}</v-tooltip></v-btn>
                  </template>
                  <template v-else>{{ cellText(h.key, item) }}</template>
                </td>
              </tr>
              <!-- Expanded row -->
              <tr v-if="expanded[item.id]">
                <td :colspan="headers.length+1" class="pa-0">
                  <div class="lwt-expand-panel pa-3">
                    <div v-for="(grp, gi) in groupDetails(item)" :key="gi" class="mb-3">
                      <v-chip size="small" color="#F9A825" variant="tonal" class="mb-2 font-weight-bold">
                        {{ grp.category ? (grp.category[`name_${main.lang?.code||'en'}`] || grp.category.name_fr || '#') : '#' }}
                        <span class="ml-1 text-caption">(<span class="fnm-currency-num">{{ grp.items.length }}</span>)</span>
                      </v-chip>
                      <table class="lwt-detail-table lwt-sub-table">
                        <thead><tr>
                          <th>{{ $t('type_article') }}</th>
                          <th>{{ $t('weight') }} (Kg)</th>
                          <th>{{ $t('source_warehouse') }}</th>
                          <th>{{ $t('source_tracking_number') }}</th>
                          <th>{{ $t('destination_warehouse') }}</th>
                        </tr></thead>
                        <tbody>
                          <tr v-for="d in grp.items" :key="d.id">
                            <td><v-chip size="x-small" color="#F9A825" variant="tonal">{{ articleName(d) }}</v-chip></td>
                            <td class="font-weight-bold fnm-currency-num">{{ main.formatDisplayNumber(d.weight) }}</td>
                            <td><v-chip size="x-small" color="error" variant="tonal">{{ warehouseName(d.source_warehouse_id) }}</v-chip></td>
                            <td><v-chip v-if="d.source_tracking_number" size="x-small" color="#F9A825" variant="tonal" class="fnm-currency-num">{{ d.source_tracking_number }}</v-chip><span v-else class="text-grey">-</span></td>
                            <td><v-chip size="x-small" color="success" variant="tonal">{{ warehouseName(d.destination_warehouse_id) }}</v-chip></td>
                          </tr>
                        </tbody>
                        <tfoot class="lwt-tfoot"><tr><td></td><td class="text-center font-weight-bold fnm-currency-num" style="color:#F9A825">Σ {{ main.formatDisplayNumber(grp.totalW) }}</td><td colspan="3"></td></tr></tfoot>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
          <tfoot class="lwt-tfoot"><tr><td :colspan="headers.length"></td><td class="text-center font-weight-bold fnm-currency-num" style="color:#F9A825">Σ {{ main.formatDisplayNumber(totalWeight) }} Kg</td></tr></tfoot>
        </table>
      </div>
    </div>

    <!-- Bulk actions -->
    <v-row v-if="selected.length && auth.hasPermission('warehouse_transfers.delete')" class="mt-2">
      <v-col><v-btn block rounded variant="tonal" color="error" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete_selected') }} (<span class="fnm-currency-num">{{ selected.length }}</span>)</v-btn></v-col>
    </v-row>

    <!-- Excel export -->
    <div class="text-end mt-2"><v-btn icon variant="tonal" color="#F9A825" size="small" @click="exportExcel"><v-icon>mdi-microsoft-excel</v-icon></v-btn></div>

    <!-- Delete dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl"><v-card-title class="text-h6">{{ $t('confirm_delete') }}</v-card-title>
      <v-card-text>{{ deleteBulk ? $t('delete_selected_confirm', { count: selected.length }) : $t('delete_confirm') }}</v-card-text>
      <v-card-actions><v-spacer></v-spacer><v-btn variant="text" @click="deleteDialog=false">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="tonal" :loading="deleting" @click="execDelete">{{ $t('delete') }}</v-btn></v-card-actions></v-card>
    </v-dialog>
  </v-card-text>
</v-card>
<AuditLogDialog v-model="auditLogDialogOpen" auditable-type="WarehouseTransfer" :auditable-id="auditLogRecordId" module-color="#F9A825" />
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import script from './listScript.js';
import AuditLogDialog from '@/components/shared/AuditLogDialog.vue';
import TableColumnFilter from '@/components/common/TableColumnFilter.vue';
import { useAuth } from '@/stores/Auth';
export default {
  ...script,
  components: { apexchart: VueApexCharts, ...script.components, AuditLogDialog, TableColumnFilter },
  data() { return { ...script.data.call(this), auditLogDialogOpen: false, auditLogRecordId: 0 }; },
  computed: { ...script.computed, auth() { return useAuth(); } },
  methods: { ...script.methods, openAuditLog(id) { this.auditLogRecordId = id; this.auditLogDialogOpen = true; } },
};
</script>

<style scoped>
.lwt-card { font-family: 'Inter', sans-serif; }
.lwt-panel-card { border: 1px solid rgba(249,168,37,.15); border-radius: 12px; padding: 12px; }
.lwt-panel-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; display: flex; align-items: center; }
.lwt-stat-item { padding: 6px 0; }
.lwt-table-wrap { border: 1px solid rgba(249,168,37,.15); border-radius: 12px; overflow: hidden; }
.lwt-detail-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.lwt-detail-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 8px 10px; border-bottom: 2px solid rgba(249,168,37,.15); white-space: nowrap; }
.lwt-detail-table td { padding: 6px 10px; border-bottom: 1px solid rgba(0,0,0,.06); white-space: nowrap; }
.lwt-row:hover { background: rgba(249,168,37,.04); }
.lwt-row.selected { background: rgba(249,168,37,.08); }
.lwt-tfoot td { border-top: 2px solid rgba(249,168,37,.15); padding: 8px 10px; }
.lwt-expand-panel { background: rgba(249,168,37,.03); border-top: 1px dashed rgba(249,168,37,.2); }
.lwt-sub-table th { font-size: 10px; background: rgba(249,168,37,.06); }
.pinned { box-shadow: 2px 0 4px rgba(0,0,0,.06); }
:deep(.v-theme--dark) .pinned,
.v-theme--dark .pinned {
  box-shadow: 2px 0 4px rgba(0,0,0,.4);
  background: #1c1f28 !important;
  color: #f1f5f9 !important;
}
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
</style>
