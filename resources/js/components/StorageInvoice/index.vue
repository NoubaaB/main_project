<template>
  <v-card class="si-card" elevation="0">
    <div class="si-hero"><div class="si-hero-inner"><div class="d-flex align-center ga-4"><div class="si-hero-icon"><v-icon size="28" color="white">mdi-receipt-text-outline</v-icon></div><div><div class="si-hero-title">{{ $t('storage_invoices') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('storage_invoice_desc') }}</div></div></div></div></div>
    <v-card-text class="pa-4">
      <v-row class="mb-4">
        <v-col cols="12" md="4">
          <v-autocomplete v-model="selectedWarehouses" :items="sejourWarehouses" item-title="name" item-value="id" :label="$t('select_warehouse')" multiple chips closable-chips density="compact" variant="outlined" rounded color="#a21ee5" class="text-info" hide-details @update:model-value="fetchInvoices">
            <template #chip="{ item, props: p }"><v-chip v-bind="p" color="#a21ee5" variant="tonal" closable>{{ item.title }}</v-chip></template>
          </v-autocomplete>
        </v-col>
        <v-col cols="6" md="2"><v-select v-model="selectedYear" :items="yearOptions" :label="$t('year')" density="compact" variant="outlined" rounded color="#a21ee5" class="text-info" hide-details @update:model-value="fetchInvoices"></v-select></v-col>
        <v-col cols="6" md="2"><v-select v-model="selectedMonth" :items="monthOptions" :label="$t('month_label')" density="compact" variant="outlined" rounded color="#a21ee5" class="text-info" hide-details></v-select></v-col>
        <v-col cols="6" md="2"><v-btn color="#a21ee5" variant="tonal" rounded block prepend-icon="mdi-eye-outline" :disabled="!selectedWarehouses.length || !selectedMonth" :loading="previewing" @click="previewInvoice">{{ $t('preview_invoice') }}</v-btn></v-col>
        <v-col cols="6" md="2"><v-btn color="#a21ee5" rounded block prepend-icon="mdi-file-document-plus-outline" :disabled="!selectedWarehouses.length || !selectedMonth" :loading="generating" @click="generateInvoice">{{ $t('generate_invoice') }}</v-btn></v-col>
      </v-row>

      <!-- Preview Card -->
      <v-expand-transition>
        <v-card v-if="previewData" class="mb-4 rounded-xl" variant="outlined" color="#a21ee5">
          <v-card-title class="d-flex align-center pa-4 pb-2">
            <v-icon color="#a21ee5" class="mr-2">mdi-calculator-variant-outline</v-icon>
            <span>{{ $t('invoice_preview') }} - {{ previewData.warehouse_name }} ({{ monthName(previewData.month) }} {{ previewData.year }})</span>
            <v-spacer></v-spacer>
            <v-btn icon size="small" variant="text" @click="previewData = null"><v-icon>mdi-close</v-icon></v-btn>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <div class="d-flex flex-wrap ga-3 mb-4">
              <v-chip color="info" variant="tonal" size="large" prepend-icon="mdi-arrow-down-bold">{{ $t('stock_in') }}: {{ formatNum(previewData.total_stock_in_kg) }} KG</v-chip>
              <v-chip color="warning" variant="tonal" size="large" prepend-icon="mdi-arrow-up-bold">{{ $t('stock_out') }}: {{ formatNum(previewData.total_stock_out_kg) }} KG</v-chip>
              <v-chip color="deep-purple" variant="tonal" size="large" prepend-icon="mdi-package-variant">{{ $t('remaining_stock') }}: {{ formatNum(previewData.remaining_stock_kg) }} KG</v-chip>
              <v-chip color="success" variant="elevated" size="large" prepend-icon="mdi-cash">{{ $t('total_to_pay') }}: {{ formatNum(previewData.total_amount) }} MAD</v-chip>
            </div>
            <v-data-table :headers="previewHeaders" :items="previewData.lines" density="comfortable" class="rounded-lg" hide-default-footer :items-per-page="-1">
              <template v-slot:[`item.description`]="{ item }"><span class="font-weight-medium">{{ item.description }}</span></template>
              <template v-slot:[`item.quantity_kg`]="{ item }">{{ formatNum(item.quantity_kg) }}</template>
              <template v-slot:[`item.quantity_days`]="{ item }">{{ item.quantity_days > 0 ? item.quantity_days : '-' }}</template>
              <template v-slot:[`item.unit_price`]="{ item }">{{ item.unit_price }} DH</template>
              <template v-slot:[`item.total_amount`]="{ item }"><span class="font-weight-bold text-success">{{ formatNum(item.total_amount) }} MAD</span></template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-expand-transition>

      <!-- Analytics Row -->
      <v-row dense class="mb-3"><v-col cols="8"><div class="si-panel"><div class="si-panel-hdr d-flex align-center justify-space-between"><div class="d-flex align-center"><v-icon size="16" color="#a21ee5" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div><v-btn-toggle v-model="chartMode" mandatory density="compact" color="#a21ee5" variant="outlined" rounded="lg" divided><v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn><v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn></v-btn-toggle></div><apexchart v-if="chartSeries[0].data.length" type="bar" :height="260" :options="chartOptions" :series="chartSeries"></apexchart><div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-bar</v-icon></div></div></v-col>
      <v-col cols="4"><div class="si-panel si-stats" style="height:100%;overflow-y:auto"><div class="si-panel-hdr"><v-icon size="16" color="#a21ee5" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div><div v-for="stat in warehouseStats" :key="stat.name" class="si-stat-item mb-2"><div class="d-flex align-center justify-space-between mb-1"><v-chip size="x-small" color="#a21ee5" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip><v-chip size="x-small" color="success" variant="elevated" class="font-weight-bold">{{ totalAmount>0?Math.round(stat.total/totalAmount*1000)/10:0 }}%</v-chip></div><v-progress-linear :model-value="totalAmount>0?(stat.total/totalAmount*100):0" color="#a21ee5" bg-color="purple-lighten-4" height="5" rounded class="mb-1"></v-progress-linear><div class="d-flex flex-column ga-1 text-caption"><div class="d-flex align-center ga-1"><v-icon size="12" color="info">mdi-arrow-down-bold</v-icon><span class="text-medium-emphasis">{{ $t('manipulation_in') }}:</span><b class="text-info">{{ formatNum(stat.manipIn) }} MAD</b></div><div class="d-flex align-center ga-1"><v-icon size="12" color="warning">mdi-arrow-up-bold</v-icon><span class="text-medium-emphasis">{{ $t('manipulation_out') }}:</span><b class="text-warning">{{ formatNum(stat.manipOut) }} MAD</b></div><div class="d-flex align-center ga-1"><v-icon size="12" color="deep-purple">mdi-clock-outline</v-icon><span class="text-medium-emphasis">{{ $t('sejour_stockage') }}:</span><b class="text-deep-purple">{{ formatNum(stat.sejour) }} MAD</b></div><div class="d-flex align-center ga-1"><v-icon size="12" color="success">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total') }}:</span><b class="text-success">{{ formatNum(stat.total) }} MAD</b></div></div></div><v-divider class="my-2"></v-divider><div class="d-flex flex-column ga-1"><div class="d-flex align-center ga-1"><v-icon size="14" color="info">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('stock_in') }}:</span><b class="text-info text-caption">{{ formatNum(totalStockIn) }} KG</b></div><div class="d-flex align-center ga-1"><v-icon size="14" color="warning">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('stock_out') }}:</span><b class="text-warning text-caption">{{ formatNum(totalStockOut) }} KG</b></div><div class="d-flex align-center ga-1"><v-icon size="14" color="success">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total') }}:</span><b class="text-success text-caption">{{ formatNum(totalAmount) }} MAD</b></div></div></div></v-col></v-row>
      <!-- Action btns -->
      <div class="d-flex ga-2 mb-3 justify-end"><v-btn v-if="selectedIds.length" color="#a21ee5" rounded size="small" prepend-icon="mdi-printer" :loading="loadingBatchPrint" @click="loadPrintMultiple">{{ $t('print') }} ({{ selectedIds.length }})</v-btn><v-btn v-if="Object.values(colFilters).some(f=>f&&f.length)" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="colFilters={}">{{ $t('clear_filters') }}</v-btn><v-btn icon variant="tonal" color="success" size="small" @click="exportExcel"><v-icon size="18">mdi-file-excel-outline</v-icon></v-btn></div>
      <!-- Invoices List -->
      <div class="si-table-wrap" ref="tableWrap"><table class="si-table"><thead class="si-thead"><tr><th class="si-th" style="width:40px"><v-checkbox-btn :model-value="allSelected" :indeterminate="someSelected && !allSelected" color="#a21ee5" density="compact" hide-details @update:model-value="toggleSelectAll"></v-checkbox-btn></th><th v-for="h in headers" :key="h.key" class="si-th" :class="{'si-th-pin':isPinned(h.key),'si-th-dragover':dragOverCol===h.key}" :style="colPinStyle(h.key,3)" :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd" @drop="onDragEnd"><div class="si-th-inner"><v-icon v-if="!h.fixed" size="12" class="drag-handle" color="grey-lighten-1">mdi-drag-vertical</v-icon><span class="si-th-title" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span><template v-if="h.key!=='actions'"><v-btn v-if="sortDir(h.key)" icon variant="text" size="14" @click.stop="toggleSort(h.key)"><v-icon size="12" color="#a21ee5">mdi-arrow-{{ sortDir(h.key)==='asc'?'up':'down' }}</v-icon></v-btn><span v-if="sortIdx(h.key)" class="sort-badge">{{ sortIdx(h.key) }}</span><v-menu v-model="filterMenus[h.key]" :close-on-content-click="false" location="bottom"><template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="14"><v-icon size="12" :color="colFilters[h.key]&&colFilters[h.key].length?'#a21ee5':'grey'">mdi-filter</v-icon></v-btn></template><v-card min-width="220" class="rounded-lg pa-2"><v-autocomplete v-model="colFilters[h.key]" :items="filterOpts(h.key)" density="compact" variant="outlined" color="#a21ee5" hide-details multiple chips closable-chips :placeholder="$t('filter')" class="text-info"></v-autocomplete></v-card></v-menu><v-btn icon variant="text" size="14" @click.stop="togglePin(h.key)"><v-icon size="10" :color="pinSide(h.key)==='left'?'warning':pinSide(h.key)==='right'?'blue':'grey'">{{ pinSide(h.key)==='left'?'mdi-pin':pinSide(h.key)==='right'?'mdi-pin-outline':'mdi-pin-off-outline' }}</v-icon></v-btn></template></div></th></tr></thead>
      <tbody><template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="#a21ee5"></v-progress-circular></td></tr></template><template v-else v-for="item in colFilteredItems" :key="item.id"><tr class="si-row"><td class="si-td"><v-checkbox-btn :model-value="selectedIds.includes(item.id)" color="#a21ee5" density="compact" hide-details @update:model-value="toggleSelect(item.id)"></v-checkbox-btn></td><td v-for="h in headers" :key="h.key" class="si-td" :class="{'si-col-pin':isPinned(h.key)}" :style="colPinStyle(h.key,2)"><template v-if="h.key==='invoice_number'"><v-chip color="#a21ee5" variant="tonal" size="small" class="font-weight-bold">{{ item.invoice_number }}</v-chip></template><template v-else-if="h.key==='_period'">{{ item._period }}</template><template v-else-if="h.key==='_warehouse'">{{ item._warehouse }}</template><template v-else-if="h.key==='total_stock_in_kg'||h.key==='total_stock_out_kg'||h.key==='manipulation_in_amount'||h.key==='manipulation_out_amount'||h.key==='sejour_amount'">{{ formatNum(item[h.key]) }}</template><template v-else-if="h.key==='total_amount'"><span class="font-weight-bold text-success">{{ formatNum(item.total_amount) }} MAD</span></template><template v-else-if="h.key==='status'"><v-chip :color="item.status==='finalized'?'success':'warning'" size="small" variant="tonal">{{ $t(item.status) }}</v-chip></template><template v-else-if="h.key==='actions'"><v-btn icon size="x-small" variant="tonal" color="#a21ee5" class="mr-1" @click="loadPrintInvoice(item)"><v-icon size="16">mdi-printer</v-icon></v-btn><v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)"><v-icon size="16">mdi-delete</v-icon></v-btn></template><template v-else>{{ item[h.key]||'-' }}</template></td></tr></template></tbody>
      <tfoot class="si-tfoot"><tr><td class="si-td"></td><td v-for="h in headers" :key="'f-'+h.key" class="si-td text-center font-weight-bold"><template v-if="h.key==='total_stock_in_kg'">Σ {{ formatNum(totalStockIn) }} KG</template><template v-else-if="h.key==='total_stock_out_kg'">Σ {{ formatNum(totalStockOut) }} KG</template><template v-else-if="h.key==='manipulation_in_amount'">Σ {{ formatNum(totalManipIn) }}</template><template v-else-if="h.key==='manipulation_out_amount'">Σ {{ formatNum(totalManipOut) }}</template><template v-else-if="h.key==='sejour_amount'">Σ {{ formatNum(totalSejour) }}</template><template v-else-if="h.key==='total_amount'">Σ {{ formatNum(totalAmount) }} MAD</template></td></tr></tfoot></table></div>
    </v-card-text>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
        <v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_invoice_storage') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Print Trigger (single or batch) -->
    <div v-if="printInvoices.length" class="d-flex align-center ga-2 pa-3" style="position:fixed;bottom:20px;right:20px;z-index:999;background:rgba(162,30,229,.95);border-radius:16px;box-shadow:0 8px 32px rgba(162,30,229,.4)">
      <v-icon color="white" size="20">mdi-receipt-text-outline</v-icon>
      <span class="text-white text-body-2 font-weight-bold mr-2">{{ printInvoices.length === 1 ? printInvoices[0].invoice_number : printInvoices.length + ' ' + $t('invoices') }}</span>
      <v-btn v-print="printCfg" color="white" variant="flat" size="small" rounded prepend-icon="mdi-printer" style="color:#a21ee5">{{ $t('print') }}</v-btn>
      <v-btn icon size="x-small" variant="text" color="white" @click="printInvoices = []; printInvoice = null"><v-icon size="18">mdi-close</v-icon></v-btn>
    </div>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420">
      <v-progress-linear :model-value="snackbarProgress" color="#a21ee5" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="summarySnackbar.isError ? 'error' : 'success'" class="mr-2">{{ summarySnackbar.isError ? 'mdi-alert-circle' : 'mdi-check-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.warehouse"><v-icon size="14" color="#a21ee5" class="mr-1">mdi-warehouse</v-icon><span class="text-medium-emphasis">{{ $t('warehouse') }}:</span> <strong>{{ summarySnackbar.warehouse }}</strong></div>
        <div v-if="summarySnackbar.period"><v-icon size="14" color="#a21ee5" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('invoice_period') }}:</span> <strong>{{ summarySnackbar.period }}</strong></div>
        <div v-if="summarySnackbar.total"><v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_to_pay') }}:</span> <strong class="text-success">{{ summarySnackbar.total }}</strong></div>
      </div>
      <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn></template>
    </v-snackbar>
  </v-card>

  <!-- Hidden Print Area -->
  <div style="display:none"><div id="si-print-area">
    <div v-for="(inv, idx) in printInvoices" :key="inv.id" :style="idx > 0 ? 'page-break-before:always' : ''" style="padding:24px 32px;max-width:900px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a1a">
      <!-- Header with Logo -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div><img :src="logoUrl" alt="Logo" style="height:52px" /></div>
        <div style="text-align:center;flex:1"><h1 style="font-size:20px;font-weight:800;color:#7B1FA2;margin:0">{{ $t('facture_de_stockage') }}</h1><p style="font-size:12px;color:#78909C;margin:2px 0 0">{{ inv.warehouse?.name }}</p></div>
        <div style="text-align:right;font-size:11px;color:#546E7A"><div><b>{{ $t('invoice_number') }}:</b> {{ inv.invoice_number }}</div><div><b>{{ $t('invoice_period') }}:</b> {{ monthName(inv.month) }} {{ inv.year }}</div><div><b>{{ $t('date') }}:</b> {{ new Date().toLocaleDateString() }}</div></div>
      </div>
      <div style="height:3px;background:linear-gradient(90deg,#7B1FA2,#CE93D8,#7B1FA2);border-radius:2px;margin:12px 0 20px"></div>
      <!-- Info Row -->
      <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
        <tbody>
        <tr><td class="si-pl">{{ $t('warehouse') }}</td><td class="si-pv">{{ inv.warehouse?.name }}</td><td class="si-pl">{{ $t('invoice_number') }}</td><td class="si-pv">{{ inv.invoice_number }}</td></tr>
        <tr><td class="si-pl">{{ $t('invoice_period') }}</td><td class="si-pv">{{ monthName(inv.month) }} {{ inv.year }}</td><td class="si-pl">{{ $t('status') }}</td><td class="si-pv">{{ inv.status }}</td></tr>
        <tr><td class="si-pl">{{ $t('stock_in') }} (KG)</td><td class="si-pv">{{ formatNum(inv.total_stock_in_kg) }}</td><td class="si-pl">{{ $t('stock_out') }} (KG)</td><td class="si-pv">{{ formatNum(inv.total_stock_out_kg) }}</td></tr>
        </tbody>
      </table>
      <!-- Lines Table -->
      <table style="width:100%;border-collapse:collapse">
        <thead><tr>
          <th class="si-ph" style="text-align:left">{{ $t('prestations') }}</th>
          <th class="si-ph">{{ $t('quantity_kg') }} (KG)</th>
          <th class="si-ph">{{ $t('quantity_days_short') }} (J)</th>
          <th class="si-ph">{{ $t('unit_price_pu') }} (MAD)</th>
          <th class="si-ph">{{ $t('total') }} (MAD)</th>
        </tr></thead>
        <tbody>
          <tr v-for="line in inv.lines" :key="line.id">
            <td class="si-pc">{{ line.description }}</td>
            <td class="si-pv2">{{ formatNum(line.quantity_kg) }}</td>
            <td class="si-pv2">{{ line.quantity_days > 0 ? line.quantity_days : '-' }}</td>
            <td class="si-pv2">{{ line.unit_price }}</td>
            <td class="si-pv2" style="font-weight:700">{{ formatNum(line.total_amount) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr><td colspan="4" style="text-align:right;font-weight:800;font-size:15px;padding:12px 10px;border-top:2px solid #7B1FA2">{{ $t('total_to_pay') }} (MAD):</td><td style="text-align:center;font-weight:800;font-size:18px;color:#7B1FA2;padding:12px 10px;border-top:2px solid #7B1FA2">{{ formatNum(inv.total_amount) }}</td></tr>
        </tfoot>
      </table>
      <!-- Signatures -->
      <div style="display:flex;justify-content:space-between;margin-top:48px;gap:60px">
        <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:40px;margin-bottom:6px"></div><div style="font-size:11px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('la_direction') }}</div></div>
        <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:40px;margin-bottom:6px"></div><div style="font-size:11px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('company_signature') }}</div></div>
      </div>
      <div style="text-align:center;margin-top:24px;font-size:10px;color:#9E9E9E">{{ $t('printed_on') }}: {{ new Date().toLocaleString() }}</div>
    </div>
  </div></div>
</template>

<script>
import script from './script.js';
import print from 'vue3-print-nb';
export default {
  ...script,
  directives: { print },
  data(){ return { ...script.data.call(this), printCfg: { id: 'si-print-area', popTitle: 'Storage Invoice' } }; },
};
</script>

<style scoped>
.si-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.si-hero { background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.si-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.si-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.si-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.si-panel{border:1.5px solid rgba(162,30,229,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.si-panel-hdr{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.si-stat-item{padding:8px 10px;border-radius:10px;background:rgba(162,30,229,.03);border:1px solid rgba(162,30,229,.06)}
.si-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.si-table{width:100%;border-collapse:collapse;font-size:13px}
.si-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.si-th{padding:10px 12px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s}
.si-td{padding:8px 12px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.si-col-pin{position:sticky!important;z-index:2!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.si-th-pin{position:sticky!important;z-index:5!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.si-th-dragover{background:rgba(162,30,229,.08)!important}
.si-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.si-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#a21ee5;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.si-row:hover{background:rgba(162,30,229,.04)}
.si-tfoot td{padding:10px 12px;border-top:2px solid rgba(162,30,229,.2);color:#a21ee5;font-size:14px}
</style>

<style>
/* Print area table styles (unscoped so they work inside the hidden print div) */
.si-pl { font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#546E7A;background:#F5F5F5;padding:8px 12px;border:1px solid #E0E0E0;width:18% }
.si-pv { font-size:13px;color:#212121;padding:8px 12px;border:1px solid #E0E0E0;width:32% }
.si-ph { background:#7B1FA2;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;padding:10px 8px;border:1px solid #6A1B9A;text-align:center }
.si-pc { padding:8px 12px;border:1px solid #E0E0E0;font-weight:600;font-size:13px }
.si-pv2 { padding:8px 8px;border:1px solid #E0E0E0;font-size:13px;text-align:center;color:#212121 }
@media print { #si-print-area { display: block !important; } }
</style>
