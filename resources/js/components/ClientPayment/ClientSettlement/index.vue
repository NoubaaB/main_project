<template>
<v-card class="cs-card" elevation="0">
  <div class="cs-hero"><div class="cs-hero-inner">
    <div class="d-flex align-center ga-4"><div class="cs-hero-icon"><v-icon size="28" color="white">mdi-cash-register</v-icon></div><div><div class="cs-hero-title">{{ $t('client_settlement') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('client_settlement_desc') }}</div></div></div>
    <div class="cs-hero-actions">
      <div class="d-flex align-center ga-1">
        <v-btn icon variant="text" size="small" @click="changeYear(-1)" style="color:#fff"><v-icon size="20">mdi-chevron-left</v-icon></v-btn>
        <v-chip color="white" variant="elevated" class="font-weight-bold text-cyan-darken-3" style="font-size:15px;min-width:70px;justify-content:center">{{ currentYear }}</v-chip>
        <v-btn icon variant="text" size="small" @click="changeYear(1)" style="color:#fff"><v-icon size="20">mdi-chevron-right</v-icon></v-btn>
      </div>
      <v-chip v-if="isClosed" color="error" variant="elevated" prepend-icon="mdi-lock" class="font-weight-bold">{{ $t('year_closed') }}</v-chip>
      <v-chip v-else color="success" variant="elevated" prepend-icon="mdi-lock-open" class="font-weight-bold">{{ $t('year_open') }}</v-chip>
    </div>
  </div></div>
  <v-card-text class="pa-4">
    <!-- Summary Cards -->
    <v-row dense class="mb-4">
      <v-col cols="3"><v-card variant="tonal" color="cyan-darken-4" rounded="xl"><v-card-text class="text-center"><div class="text-caption text-medium-emphasis mb-1">{{ $t('carry_forward') }}</div><div class="text-h6 font-weight-bold">{{ main.formatDisplayNumber(totalCarryForward) }} {{ main.currency }}</div></v-card-text></v-card></v-col>
      <v-col cols="3"><v-card variant="tonal" color="cyan-darken-3" rounded="xl"><v-card-text class="text-center"><div class="text-caption text-medium-emphasis mb-1">{{ $t('client_invoices') }}</div><div class="text-h6 font-weight-bold">{{ main.formatDisplayNumber(totalInvoices) }} {{ main.currency }}</div></v-card-text></v-card></v-col>
      <v-col cols="3"><v-card variant="tonal" color="teal" rounded="xl"><v-card-text class="text-center"><div class="text-caption text-medium-emphasis mb-1">{{ $t('payments') }}</div><div class="text-h6 font-weight-bold">{{ main.formatDisplayNumber(totalPayments) }} {{ main.currency }}</div></v-card-text></v-card></v-col>
      <v-col cols="3"><v-card variant="tonal" :color="totalBalance>0?'error':'success'" rounded="xl"><v-card-text class="text-center"><div class="text-caption text-medium-emphasis mb-1">{{ $t('outstanding_balance') }}</div><div class="text-h6 font-weight-bold">{{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}</div></v-card-text></v-card></v-col>
    </v-row>
    <!-- Tabs -->
    <v-tabs v-model="activeTab" color="#00838F" density="compact" class="mb-3">
      <v-tab value="balance" prepend-icon="mdi-calendar-month">{{ $t('balance_grid') }}</v-tab>
      <v-tab value="analytics" prepend-icon="mdi-chart-bar">{{ $t('analytics') }}</v-tab>
    </v-tabs>

    <!-- TAB: Balance Grid -->
    <div v-if="activeTab==='balance'">
      <!-- Client Filter + Add Payment -->
      <div class="d-flex ga-2 mb-4 align-center">
        <v-autocomplete v-model="selectedClientIds" 
        :items="clientItems" 
        :label="$t('clients')" 
        variant="outlined" 
        density="compact" 
        rounded color="#00838F"
        clearable
        hide-details multiple chips 
        closable-chips small-chips class="flex-grow-1" 
        :placeholder="$t('all')">
          <template #chip="{ item }">
            <v-chip  color="cyan-darken-3" variant="tonal">{{ item.title }}</v-chip>
          </template>
        </v-autocomplete>
        <v-btn color="#00838F" variant="flat" rounded prepend-icon="mdi-plus" @click="openAddPayment" :disabled="isClosed" size="small" class="flex-shrink-0">{{ $t('add_payment') }}</v-btn>
        <v-btn icon variant="tonal" color="success" size="small" @click="exportExcel" class="flex-shrink-0"><v-icon size="18">mdi-file-excel-outline</v-icon></v-btn>
      </div>
      <div v-if="yearInvoicesLoading || store.loading" class="text-center pa-8"><v-progress-circular indeterminate color="#00838F" size="40"></v-progress-circular></div>
      <template v-else>
        <!-- Month Rows -->
        <div v-for="m in months" :key="m.num" class="cs-month-row mb-3" :class="{'cs-month-disabled': isFutureMonth(m.num)}">
          <div class="cs-month-bar" @click="!isFutureMonth(m.num) && toggleMonth(m.num)" :style="{cursor: isFutureMonth(m.num)?'default':'pointer'}">
            <div class="d-flex align-center ga-2"><v-avatar size="28" :color="isFutureMonth(m.num)?'grey':'#00838F'" variant="tonal"><span class="text-caption font-weight-bold">{{ m.num }}</span></v-avatar><span class="font-weight-bold text-capitalize" :style="{opacity:isFutureMonth(m.num)?'.35':'1'}">{{ m.label }} {{ currentYear }}</span></div>
            <div class="d-flex ga-3 align-center">
              <v-chip size="x-small" color="cyan-darken-3" variant="tonal" class="font-weight-bold"><v-icon size="10" start>mdi-arrow-up</v-icon>{{ main.formatDisplayNumber(monthTotals[m.num].invoices) }} {{ main.currency }}</v-chip>
              <v-chip size="x-small" color="teal" variant="tonal" class="font-weight-bold"><v-icon size="10" start>mdi-arrow-down</v-icon>{{ main.formatDisplayNumber(monthTotals[m.num].payments) }} {{ main.currency }}</v-chip>
              <v-chip size="x-small" :color="monthTotals[m.num].balance>0?'error':'success'" variant="flat" class="font-weight-bold">= {{ main.formatDisplayNumber(monthTotals[m.num].balance) }} {{ main.currency }}</v-chip>
              <v-chip size="x-small" color="cyan-darken-4" variant="tonal" class="font-weight-bold"><v-icon size="10" start>mdi-bank-transfer</v-icon>{{ $t('carry_forward') }}: {{ main.formatDisplayNumber(cumulativeBalance(m.num)) }} {{ main.currency }}</v-chip>
              <v-btn v-if="!isFutureMonth(m.num)" icon variant="tonal" color="success" size="x-small" @click.stop="exportMonthExcel(m.num, m.label)" :disabled="!invoicesByMonth[m.num].length && !paymentsByMonth[m.num].length"><v-icon size="14">mdi-file-excel-outline</v-icon></v-btn>
              <v-icon v-if="!isFutureMonth(m.num)" size="18" :style="{transform:expandedMonths[m.num]?'rotate(180deg)':'',transition:'.2s'}">mdi-chevron-down</v-icon>
            </div>
          </div>
          <v-expand-transition><div v-show="expandedMonths[m.num] && !isFutureMonth(m.num)">
          <v-row dense class="mt-1">
            <v-col cols="12" md="6">
              <div class="cs-sub-label"><v-icon size="12" color="#00838F" class="mr-1">mdi-file-document-outline</v-icon>{{ $t('client_invoices') }} ({{ invoicesByMonth[m.num].length }})</div>
              <div class="cs-sub-table-wrap"><table class="cs-table" v-if="invoicesByMonth[m.num].length"><thead><tr><th class="cs-th">{{ $t('date') }}</th><th class="cs-th">{{ $t('clients') }}</th><th class="cs-th">{{ $t('invoice_tracking_number') }}</th><th class="cs-th">{{ $t('weight') }}</th><th class="cs-th">{{ $t('price') }}</th></tr></thead><tbody>
                <tr v-for="inv in invoicesByMonth[m.num]" :key="inv.id" class="cs-row"><td class="cs-td">{{ fmtDate(inv.date) }}</td><td class="cs-td">{{ inv._clientNames }}</td><td class="cs-td"><v-chip size="x-small" color="#00838F" variant="tonal">{{ inv._trackingStr }}</v-chip></td><td class="cs-td">{{ main.formatDisplayNumber(inv.total_weight) }}</td><td class="cs-td font-weight-bold text-cyan-darken-3">{{ main.formatDisplayNumber(inv.total_price) }}</td></tr>
                <tr class="cs-foot"><td colspan="4" class="font-weight-bold text-right">{{ $t('total') }}</td><td class="font-weight-bold text-cyan-darken-3">{{ main.formatDisplayNumber(monthTotals[m.num].invoices) }}</td></tr>
              </tbody></table><div v-else class="text-center text-caption pa-4" style="opacity:.35">-</div></div>
            </v-col>
            <v-col cols="12" md="6">
              <div class="cs-sub-label"><v-icon size="12" color="teal" class="mr-1">mdi-cash-multiple</v-icon>{{ $t('payments') }} ({{ paymentsByMonth[m.num].length }})</div>
              <div class="cs-sub-table-wrap"><table class="cs-table" v-if="paymentsByMonth[m.num].length"><thead><tr><th class="cs-th">{{ $t('date') }}</th><th class="cs-th">{{ $t('clients') }}</th><th class="cs-th">{{ $t('payment_type') }}</th><th class="cs-th">{{ $t('payment_reference') }}</th><th class="cs-th">{{ $t('payment_amount') }}</th><th class="cs-th">{{ $t('actions') }}</th></tr></thead><tbody>
                <tr v-for="p in paymentsByMonth[m.num]" :key="p.id" class="cs-row"><td class="cs-td">{{ fmtDate(p.date) }}</td><td class="cs-td">{{ p._clientName }}</td><td class="cs-td"><v-chip size="x-small" :color="p.payment_type==='cash'?'success':p.payment_type==='check'?'blue':'purple'" variant="tonal">{{ paymentTypeLabel(p.payment_type) }}</v-chip></td><td class="cs-td">{{ p.reference || '-' }}</td><td class="cs-td font-weight-bold text-teal">{{ main.formatDisplayNumber(p.amount) }}</td><td class="cs-td"><v-btn icon size="x-small" variant="tonal" color="warning" class="mr-1" @click="editPayment(p)" :disabled="isClosed"><v-icon size="14">mdi-pencil</v-icon></v-btn><v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(p.id)" :disabled="isClosed"><v-icon size="14">mdi-delete</v-icon></v-btn></td></tr>
                <tr class="cs-foot"><td colspan="5" class="font-weight-bold text-right">{{ $t('total') }}</td><td class="font-weight-bold text-teal">{{ main.formatDisplayNumber(monthTotals[m.num].payments) }}</td></tr>
              </tbody></table><div v-else class="text-center text-caption pa-4" style="opacity:.35">-</div></div>
            </v-col>
          </v-row>
          </div></v-expand-transition>
        </div>
        <!-- Year Summary -->
        <div class="mt-4"><div class="cs-section-title"><v-icon size="16" color="#00838F" class="mr-1">mdi-sigma</v-icon>{{ $t('year_summary') }}</div></div>
        <div class="cs-table-wrap mt-2"><table class="cs-table"><thead><tr><th class="cs-th">{{ $t('clients') }}</th><th class="cs-th">{{ $t('carry_forward') }}</th><th class="cs-th">{{ $t('client_invoices') }}</th><th class="cs-th">{{ $t('payments') }}</th><th class="cs-th">{{ $t('outstanding_balance') }}</th></tr></thead><tbody>
          <tr v-for="s in filteredYearSummary" :key="s.client_id" class="cs-row"><td class="cs-td font-weight-bold">{{ s.client_name }}</td><td class="cs-td">{{ main.formatDisplayNumber(s.carry_forward) }}</td><td class="cs-td text-cyan-darken-3">{{ main.formatDisplayNumber(s.total_invoices) }}</td><td class="cs-td text-teal">{{ main.formatDisplayNumber(s.total_payments) }}</td><td class="cs-td font-weight-bold" :class="{'text-success':s.balance<=0,'text-cyan-darken-3':s.balance>0}">{{ main.formatDisplayNumber(s.balance) }} {{ main.currency }}</td></tr>
          <tr class="cs-foot"><td class="font-weight-bold">{{ $t('total') }}</td><td class="font-weight-bold">{{ main.formatDisplayNumber(totalCarryForward) }}</td><td class="font-weight-bold text-cyan-darken-3">{{ main.formatDisplayNumber(totalInvoices) }}</td><td class="font-weight-bold text-teal">{{ main.formatDisplayNumber(totalPayments) }}</td><td class="font-weight-bold" :class="{'text-success':totalBalance<=0,'text-cyan-darken-3':totalBalance>0}">{{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}</td></tr>
        </tbody></table></div>
        <div class="d-flex ga-2 mt-4 justify-end">
          <v-btn v-if="!isClosed" color="#00838F" variant="flat" rounded prepend-icon="mdi-lock" @click="closeYearDialog=true">{{ $t('close_year') }}</v-btn>
          <v-btn v-if="isClosed" color="warning" variant="outlined" rounded prepend-icon="mdi-lock-open" @click="reopenDialog=true">{{ $t('reopen_year') }}</v-btn>
        </div>
      </template>
    </div>

    <!-- TAB: Analytics -->
    <div v-if="activeTab==='analytics'">
      <!-- Shared client filter (synced with balance grid) -->
      <v-autocomplete v-model="selectedClientIds" :items="clientItems" :label="$t('clients')" variant="outlined" density="compact" rounded color="#00838F" hide-details multiple chips closable-chips small-chips class="mb-4" :placeholder="$t('all')"></v-autocomplete>
      <div class="cs-panel-card">
        <div class="cs-section-title mb-2"><v-icon size="16" color="#00838F" class="mr-1">mdi-chart-bar</v-icon>{{ $t('invoices_vs_payments') }}</div>
        <apexchart type="line" :height="320" :options="chartOptions" :series="chartSeries"></apexchart>
      </div>
    </div>
  </v-card-text>

  <!-- Add/Edit Payment Dialog -->
  <v-dialog v-model="showPayForm" max-width="580" persistent>
    <v-card class="rounded-xl">
      <v-card-title class="pa-5 pb-3 d-flex align-center">
        <v-icon color="#00838F" class="mr-2">mdi-cash-plus</v-icon>
        {{ editId ? $t('edit_payment') : $t('add_payment') }}
        <v-spacer/>
        <v-btn icon variant="text" size="small" @click="showPayForm=false;resetPayForm()"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-divider/>
      <v-card-text class="pa-5">
        <!-- Date Picker -->
        <div class="d-flex justify-center mb-4">
          <DatePicker v-model="payDateModel" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="teal" :min-date="minPayDate" :max-date="maxPayDate" expanded />
        </div>
        <v-row dense>
          <v-col cols="6"><v-autocomplete v-model="payForm.payment_type" :items="paymentTypes" :label="$t('payment_type')" variant="outlined" density="compact" rounded color="#00838F" hide-details></v-autocomplete></v-col>
          <v-col cols="6"><v-autocomplete v-model="payForm.client_id" :items="clientItems" :label="$t('clients')" variant="outlined" density="compact" rounded color="#00838F" hide-details @update:model-value="onClientChange"></v-autocomplete></v-col>
          <v-col cols="6"><v-text-field v-model.number="payForm.amount" :label="$t('payment_amount')" type="number" variant="outlined" density="compact" rounded color="#00838F" :rules="[v => v > 0 || $t('amount_must_be_positive')]" min="0.01" step="0.01" :suffix="main.currency"></v-text-field></v-col>
          <v-col cols="6"><v-text-field v-model="payForm.reference" :label="$t('payment_reference')" variant="outlined" density="compact" rounded color="#00838F" hide-details></v-text-field></v-col>
          <v-col cols="6">
            <v-autocomplete
              ref="bankNameInput"
              v-model="payForm.bank_name"
              :items="bankNameItems"
              :label="$t('bank_name')"
              variant="outlined" density="compact" rounded color="#00838F" hide-details clearable
              @update:search="val => bankNameSearch = val"
              @keydown.enter.prevent="createBankNameIfNew"
            >
              <template #no-data>
                <v-list-item v-if="bankNameSearch?.trim()">
                  <v-list-item-title class="text-caption">
                    <v-icon size="16" class="mr-1">mdi-plus-circle-outline</v-icon>
                    {{ $t('press_enter_to_create') }} "<strong>{{ bankNameSearch }}</strong>"
                  </v-list-item-title>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>
          <v-col cols="6"><v-text-field v-model="payForm.notes" :label="$t('notes')" variant="outlined" density="compact" rounded color="#00838F" hide-details></v-text-field></v-col>
        </v-row>
      </v-card-text>
      <v-divider/>
      <v-card-actions class="pa-5 pt-3">
        <v-spacer/>
        <v-btn variant="text" rounded @click="showPayForm=false;resetPayForm()">{{ $t('cancel') }}</v-btn>
        <v-btn color="#00838F" variant="flat" rounded prepend-icon="mdi-content-save" :loading="saving" @click="savePayment" :disabled="!payForm.client_id||!payForm.amount||payForm.amount<=0||!payForm.date">{{ $t('save') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Delete Dialog -->
  <v-dialog v-model="deleteDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ deleteBulk ? $t('confirm_bulk_delete_message',{count:selected.length}) : $t('confirm_delete_payment_message') }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer/><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions></v-card></v-dialog>
  <!-- Close Year Dialog -->
  <v-dialog v-model="closeYearDialog" max-width="500"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2"><v-icon color="#00838F" class="mr-2">mdi-lock</v-icon>{{ $t('fiscal_year_closing') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ $t('confirm_close_year', {year:currentYear}) }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer/><v-btn @click="closeYearDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="#00838F" variant="flat" rounded @click="doCloseYear" :loading="closingYear">{{ $t('close_year') }}</v-btn></v-card-actions></v-card></v-dialog>
  <!-- Reopen Year Dialog -->
  <v-dialog v-model="reopenDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2"><v-icon color="warning" class="mr-2">mdi-lock-open</v-icon>{{ $t('reopen_year') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ $t('confirm_reopen_year', {year:currentYear}) }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer/><v-btn @click="reopenDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="warning" variant="flat" rounded @click="doReopenYear" :loading="reopening">{{ $t('reopen_year') }}</v-btn></v-card-actions></v-card></v-dialog>

  <!-- Summary Snackbar -->
  <v-snackbar v-for="(sb, i) in main.snackbars" :key="i" v-model="sb.show" :color="sb.color" :timeout="3000" location="bottom right">{{ sb.message }}</v-snackbar>
</v-card>
</template>
<script>
import script from './script.js';
export default { ...script };
</script>
<style scoped>
.cs-card{border-radius:20px!important;font-family:'Inter',sans-serif}
.cs-hero{background:linear-gradient(135deg,#006064 0%,#00838F 40%,#26C6DA 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;min-height:100px}
.cs-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.cs-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.15)}
.cs-hero-title{font-size:19px;font-weight:800;letter-spacing:.3px}
.cs-hero-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.cs-section-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;display:flex;align-items:center}
/* Month rows */
.cs-month-row{border:1.5px solid rgba(var(--v-border-color),.1);border-radius:14px;padding:4px 8px 8px;transition:border-color .2s,opacity .2s}
.cs-month-row:hover:not(.cs-month-disabled){border-color:rgba(0,131,143,.25)}
.cs-month-disabled{opacity:.4;pointer-events:none}
.cs-month-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;gap:8px;flex-wrap:wrap}
.cs-sub-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;padding:4px 8px;opacity:.6;display:flex;align-items:center}
.cs-sub-table-wrap{overflow-x:auto;border-radius:8px;border:1px solid rgba(var(--v-border-color),.08)}
/* Tables */
.cs-table-wrap{overflow:auto;max-height:calc(100vh - 400px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.cs-table{width:100%;border-collapse:collapse;font-size:12px}
.cs-th{padding:8px 10px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7}
.cs-td{padding:6px 8px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.cs-row:hover{background:rgba(0,131,143,.04)}
.cs-foot td{padding:8px 10px;border-top:2px solid rgba(0,131,143,.2);color:#00838F;font-size:12px}
.cs-panel-card{border:1.5px solid rgba(0,131,143,.12);border-radius:14px;padding:16px;background:rgba(var(--v-theme-surface),1)}
.cs-date-picker{border:1.5px solid rgba(0,131,143,.15)!important}
</style>
