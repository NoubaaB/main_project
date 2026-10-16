<template>
  <div>
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="3">
        <div class="ot-panel">
          <div class="ot-panel-hdr"><v-icon size="16" color="#00897B" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :max-date="new Date()" color="teal" :is-dark="main.is_dark" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="6">
        <div class="ot-panel" style="height:100%">
          <div class="ot-panel-hdr d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#00897B" variant="outlined" rounded="lg" divided>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries[0].data.length" type="bar" :height="260" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-bar</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="3">
        <div class="ot-panel ot-stats-panel" style="height:100%;overflow-y:auto">
          <div class="ot-panel-hdr"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <!-- Per-currency stats -->
          <div v-for="stat in currencyStats" :key="stat.name" class="ot-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="success" variant="elevated" class="font-weight-bold">{{ formatNum(stat.total) }}</v-chip>
            </div>
            <v-progress-linear :model-value="totalAmount>0?(stat.total/totalAmount*100):0" color="#00897B" bg-color="teal-lighten-4" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="#00897B">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('amount') }}:</span><b class="text-success">{{ formatNum(stat.total) }} {{ stat.name }}</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-percent</v-icon><span class="text-medium-emphasis">{{ $t('share') }}:</span><b class="text-deep-orange">{{ totalAmount>0 ? Math.round(stat.total/totalAmount*1000)/10 : 0 }}%</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <!-- Totals -->
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#00897B">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total') }}:</span><b class="text-success text-caption">{{ colFilteredItems.length }} {{ $t('records') }}</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_amount') }}:</span><b class="text-deep-orange text-caption">{{ formatNum(totalAmount) }}</b></div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Action Buttons -->
    <div class="d-flex ga-2 mb-3 justify-end flex-wrap">
      <v-btn v-if="selectedIds.length" color="#00897B" rounded size="small" prepend-icon="mdi-printer" :loading="loadingBatchPrint" @click="loadPrintMultiple">{{ $t('print') }} ({{ selectedIds.length }})</v-btn>
      <v-btn v-if="selectedIds.length && auth.hasPermission('order_transfers.delete')" color="error" variant="tonal" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete') }} ({{ selectedIds.length }})</v-btn>
      <v-btn v-if="Object.values(colFilters).some(f=>f&&f.length)" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="colFilters={}">{{ $t('clear_filters') }}</v-btn>
      <v-btn icon variant="tonal" color="success" size="small" @click="exportExcel"><v-icon size="18">mdi-file-excel-outline</v-icon></v-btn>
    </div>

    <!-- Data Table -->
    <div class="ot-table-wrap" ref="tableWrap">
      <table class="ot-table">
        <thead class="ot-thead">
          <tr>
            <th class="ot-th" style="width:40px"><v-checkbox-btn :model-value="allSelected" :indeterminate="someSelected && !allSelected" color="#00897B" density="compact" hide-details @update:model-value="toggleSelectAll"></v-checkbox-btn></th>
            <th v-for="h in headers" :key="h.key" class="ot-th" :class="{'ot-th-pin':isPinned(h.key),'ot-th-dragover':dragOverCol===h.key}" :style="colPinStyle(h.key,5)" :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd" @drop="onDragEnd">
              <div class="ot-th-inner">
                <v-icon v-if="!h.fixed" size="12" class="drag-handle" color="grey-lighten-1">mdi-drag-vertical</v-icon>
                <span class="ot-th-title" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span>
                <template v-if="h.key!=='actions'">
                  <v-btn v-if="sortDir(h.key)" icon variant="text" size="14" @click.stop="toggleSort(h.key)"><v-icon size="12" color="#00897B">mdi-arrow-{{ sortDir(h.key)==='asc'?'up':'down' }}</v-icon></v-btn>
                  <span v-if="sortIdx(h.key)" class="sort-badge">{{ sortIdx(h.key) }}</span>
                  <v-menu v-model="filterMenus[h.key]" :close-on-content-click="false" location="bottom">
                    <template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="14"><v-icon size="12" :color="colFilters[h.key]&&colFilters[h.key].length?'#00897B':'grey'">mdi-filter</v-icon></v-btn></template>
                    <v-card min-width="220" class="rounded-lg pa-2"><v-autocomplete v-model="colFilters[h.key]" :items="filterOpts(h.key)" density="compact" variant="outlined" color="#00897B" hide-details multiple chips closable-chips :placeholder="$t('filter')" class="text-ot"></v-autocomplete></v-card>
                  </v-menu>
                  <v-btn icon variant="text" size="14" @click.stop="togglePin(h.key)"><v-icon size="10" :color="pinSide(h.key)==='left'?'warning':pinSide(h.key)==='right'?'blue':'grey'">{{ pinSide(h.key)==='left'?'mdi-pin':pinSide(h.key)==='right'?'mdi-pin-outline':'mdi-pin-off-outline' }}</v-icon></v-btn>
                </template>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="#00897B"></v-progress-circular></td></tr></template>
          <template v-else v-for="item in colFilteredItems" :key="item.id">
            <tr class="ot-row">
              <td class="ot-td"><v-checkbox-btn :model-value="selectedIds.includes(item.id)" color="#00897B" density="compact" hide-details @update:model-value="toggleSelect(item.id)"></v-checkbox-btn></td>
              <td v-for="h in headers" :key="h.key" class="ot-td" :class="{'ot-col-pin':isPinned(h.key)}" :style="colPinStyle(h.key,2)">
                <template v-if="h.key==='transfer_number'"><v-chip color="#00897B" variant="tonal" size="small" class="font-weight-bold">{{ item.transfer_number }}</v-chip></template>
                <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                <template v-else-if="h.key==='amount'"><span class="font-weight-bold text-success">{{ formatNum(item.amount) }} {{ currencySymbol(item.currency) }}</span></template>
                <template v-else-if="h.key==='currency'"><v-chip :color="item.currency==='MAD'?'#00897B':item.currency==='EUR'?'#1565C0':'#F57C00'" variant="tonal" size="small">{{ item.currency }}</v-chip></template>
                <template v-else-if="h.key==='_beneficiaries'"><span class="text-caption">{{ item._beneficiaries }}</span></template>
                <template v-else-if="h.key==='motif'"><span class="text-caption" style="max-width:200px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ item.motif || '-' }}</span></template>
                <template v-else-if="h.key==='actions'">
                  <v-btn v-if="auth.hasPermission('order_transfers.edit')" icon size="x-small" variant="tonal" color="#00897B" class="mr-1" @click="editTransfer(item)"><v-icon size="16">mdi-pencil</v-icon></v-btn>
                  <v-btn icon size="x-small" variant="tonal" color="#00897B" class="mr-1" @click="loadPrintSingle(item)"><v-icon size="16">mdi-printer</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('order_transfers.delete')" icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item.id)"><v-icon size="16">mdi-delete</v-icon></v-btn>
                </template>
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
          </template>
        </tbody>
        <tfoot class="ot-tfoot"><tr><td class="ot-td"></td><td v-for="h in headers" :key="'f-'+h.key" class="ot-td text-center font-weight-bold"><template v-if="h.key==='amount'">Σ {{ formatNum(totalAmount) }}</template></td></tr></tfoot>
      </table>
    </div>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
        <v-card-text class="pa-5 pt-2">{{ deleteBulk ? $t('confirm_delete_selected_transfers') : $t('confirm_delete_transfer') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Print Trigger -->
    <div v-if="printTransfers.length" class="d-flex align-center ga-2 pa-3" style="position:fixed;bottom:20px;right:20px;z-index:999;background:rgba(0,137,123,.95);border-radius:16px;box-shadow:0 8px 32px rgba(0,137,123,.4)">
      <v-icon color="white" size="20">mdi-bank-transfer</v-icon>
      <span class="text-white text-body-2 font-weight-bold mr-2">{{ printTransfers.length === 1 ? printTransfers[0].transfer_number : printTransfers.length + ' ' + $t('order_transfers') }}</span>
      <v-btn v-print="printCfg" color="white" variant="flat" size="small" rounded prepend-icon="mdi-printer" style="color:#00897B">{{ $t('print') }}</v-btn>
      <v-btn icon size="x-small" variant="text" color="white" @click="printTransfers=[]"><v-icon size="18">mdi-close</v-icon></v-btn>
    </div>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420">
      <v-progress-linear :model-value="snackbarProgress" color="#00897B" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="summarySnackbar.isError ? 'error' : 'success'" class="mr-2">{{ summarySnackbar.isError ? 'mdi-alert-circle' : 'mdi-check-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show=false"></v-btn></template>
    </v-snackbar>
  </div>

  <!-- Hidden Print Area -->
  <div style="display:none"><div id="ot-print-area">
    <div v-for="(ot, idx) in printTransfers" :key="ot.id" :style="idx > 0 ? 'page-break-before:always' : ''" style="padding:30px 40px;max-width:800px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#1a1a1a">
      <!-- Logo + Company Name -->
      <div style="text-align:center;margin-bottom:20px">
        <img :src="logoUrl" alt="Logo" style="height:70px;margin-bottom:6px" />
        <div v-if="companySettings.company_name" style="font-size:24px;font-weight:800;color:#0277BD;font-family:'Segoe UI',Arial,sans-serif">{{ companySettings.company_name }}</div>
      </div>
      <!-- Title -->
      <div style="text-align:center;margin-bottom:24px"><div style="display:inline-block;border:2px solid #333;padding:8px 32px;font-size:16px;font-weight:700;letter-spacing:1px">ORDER DE VIRMENT</div></div>
      <!-- City + Date -->
      <div style="text-align:right;margin-bottom:20px;font-size:13px">{{ companySettings.company_origin || '' }}, {{ formatDateFrench(ot.date) }}</div>
      <!-- Guichet -->
      <div style="margin-bottom:8px"><b>Guichet :</b></div>
      <!-- Nom donneur d'ordre -->
      <div style="margin-bottom:8px"><span>Nom du donneur d'ordre : </span><b>{{ companySettings.company_name || '' }}</b></div>
      <!-- Veuillez virer -->
      <div style="margin-bottom:16px"><span>Veuillez virer par débit de nom compte N° :</span> <span v-if="companySettings.bank_account_number">{{ companySettings.bank_account_number }}</span></div>
      <!-- Montant -->
      <div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:10px">
        <span style="margin-right:20px">Montant :</span>
        <div style="border:2px solid #333;padding:6px 24px;font-weight:800;font-size:16px">{{ formatNum(ot.amount) }} {{ currencySymbol(ot.currency) }}</div>
      </div>
      <!-- La somme de -->
      <div style="display:flex;align-items:center;margin-bottom:14px">
        <span style="margin-right:12px">La somme de :</span>
        <div style="flex:1;border-bottom:1px solid #999;text-align:center;padding:4px 0;font-weight:700">{{ numberToFrenchWords(Number(ot.amount), ot.currency) }}</div>
      </div>
      <!-- En faveur de -->
      <div style="margin-bottom:10px"><span>En faveur de : </span><b>{{ (ot.beneficiary_names || []).map(b => b.name).join(', ') }}</b></div>
      <!-- Domicilié chez -->
      <div style="margin-bottom:16px"><span>Domicilié chez :</span></div>
      <!-- Compte N° -->
      <div style="text-align:center;margin-bottom:16px"><span>Compte N° :</span></div>
      <!-- Motif -->
      <div style="margin-bottom:40px"><span style="color:#0277BD;font-weight:700">Motif : </span><span style="text-align:center;display:inline-block;margin-left:80px">{{ ot.motif || '' }}</span></div>
      <!-- Signature -->
      <div style="text-align:right;margin-bottom:60px;font-size:13px">Signature du donneur l'ordre</div>
      <!-- Footer -->
      <div style="text-align:center;border-top:1px solid #ccc;padding-top:10px;font-size:11px;color:#666">
        <div v-if="companySettings.company_name">Sté {{ companySettings.company_name }} - Adresse : {{ companySettings.company_address || '' }}</div>
        <div>
          <span v-if="companySettings.capital">Capital de {{ companySettings.capital }} {{ currencySymbol('MAD') }}</span>
          <span v-if="companySettings.rc"> - RC : {{ companySettings.rc }}</span>
          <span v-if="companySettings.ice"> ICE :{{ companySettings.ice }}</span>
        </div>
      </div>
    </div>
  </div></div>
</template>

<script>
import script from './listScript.js';
import print from 'vue3-print-nb';
import { DatePicker } from 'v-calendar';
export default {
  ...script,
  directives: { print },
  components: { DatePicker },
  emits: ['edit'],
};
</script>

<style scoped>
.ot-panel{border:1.5px solid rgba(0,137,123,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.ot-panel-hdr{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.ot-stat-item{padding:8px 12px;border-radius:10px;background:rgba(0,137,123,.03);border:1px solid rgba(0,137,123,.06);flex:1;min-width:180px}
.ot-stats-panel{max-height:400px}
.ot-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.ot-table{width:100%;border-collapse:collapse;font-size:13px}
.ot-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.ot-th{padding:10px 12px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s}
.ot-td{padding:8px 12px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.ot-col-pin{position:sticky!important;z-index:2!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.ot-th-pin{position:sticky!important;z-index:5!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
:deep(.v-theme--dark) .ot-col-pin,
:deep(.v-theme--dark) .ot-th-pin,
.v-theme--dark .ot-col-pin,
.v-theme--dark .ot-th-pin {
  background: #1c1f28 !important;
  color: #f1f5f9 !important;
  box-shadow: 2px 0 4px -2px rgba(0,0,0,.4) !important;
}
.ot-th-dragover{background:rgba(0,137,123,.08)!important}
.ot-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.ot-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#00897B;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.ot-row:hover{background:rgba(0,137,123,.04)}
.ot-tfoot td{padding:10px 12px;border-top:2px solid rgba(0,137,123,.2);color:#00897B;font-size:14px}
:deep(.v-theme--dark) .ot-tfoot td,
.v-theme--dark .ot-tfoot td {
  color: #26A69A !important;
}
.text-ot{color:#00897B!important}
</style>
