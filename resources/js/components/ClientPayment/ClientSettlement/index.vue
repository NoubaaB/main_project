<template>
<v-card class="cs-card" elevation="0">
  <!-- Hero Header -->
  <div class="cs-hero">
    <div class="cs-hero-inner">
      <div class="d-flex align-center ga-3 flex-wrap">
        <div class="cs-hero-icon">
          <v-icon size="30" color="white">mdi-cash-register</v-icon>
        </div>
        <div>
          <div class="cs-hero-title">{{ $t('client_settlement') }}</div>
          <div class="cs-hero-subtitle">{{ $t('client_settlement_desc') }}</div>
        </div>
      </div>
      <div class="cs-hero-actions">
        <!-- Year Switcher Pill -->
        <div class="cs-year-pill">
          <v-btn icon variant="text" size="x-small" @click="changeYear(-1)" class="cs-year-btn">
            <v-icon size="20">mdi-chevron-left</v-icon>
          </v-btn>
          <span class="cs-year-text">{{ currentYear }}</span>
          <v-btn icon variant="text" size="x-small" @click="changeYear(1)" class="cs-year-btn">
            <v-icon size="20">mdi-chevron-right</v-icon>
          </v-btn>
        </div>
        <!-- Year Status Chip -->
        <v-chip
          v-if="isClosed"
          color="error"
          variant="elevated"
          prepend-icon="mdi-lock"
          class="font-weight-bold"
        >
          {{ $t('year_closed') }}
        </v-chip>
        <v-chip
          v-else
          color="success"
          variant="elevated"
          prepend-icon="mdi-lock-open"
          class="font-weight-bold"
        >
          {{ $t('year_open') }}
        </v-chip>
      </div>
    </div>
  </div>

  <v-card-text class="pa-4 pa-md-5">
    <!-- Responsive KPI Summary Cards -->
    <v-row dense class="mb-4 g-3">
      <!-- Carry Forward -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="cs-kpi-card cs-kpi-carry" elevation="0">
          <div class="cs-kpi-top">
            <div class="cs-kpi-icon-wrap cs-kpi-icon-blue">
              <v-icon size="19">mdi-bank-transfer</v-icon>
            </div>
            <div class="cs-kpi-label">{{ $t('carry_forward') }}</div>
          </div>
          <div class="cs-kpi-value text-cyan-darken-4">
            {{ main.formatDisplayNumber(totalCarryForward) }} <span class="cs-kpi-cur">{{ main.currency }}</span>
          </div>
        </v-card>
      </v-col>
      <!-- Invoices -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="cs-kpi-card cs-kpi-invoices" elevation="0">
          <div class="cs-kpi-top">
            <div class="cs-kpi-icon-wrap cs-kpi-icon-cyan">
              <v-icon size="19">mdi-file-document-outline</v-icon>
            </div>
            <div class="cs-kpi-label">{{ $t('client_invoices') }}</div>
          </div>
          <div class="cs-kpi-value text-cyan-darken-3">
            {{ main.formatDisplayNumber(totalInvoices) }} <span class="cs-kpi-cur">{{ main.currency }}</span>
          </div>
        </v-card>
      </v-col>
      <!-- Payments -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="cs-kpi-card cs-kpi-payments" elevation="0">
          <div class="cs-kpi-top">
            <div class="cs-kpi-icon-wrap cs-kpi-icon-teal">
              <v-icon size="19">mdi-cash-multiple</v-icon>
            </div>
            <div class="cs-kpi-label">{{ $t('payments') }}</div>
          </div>
          <div class="cs-kpi-value text-teal">
            {{ main.formatDisplayNumber(totalPayments) }} <span class="cs-kpi-cur">{{ main.currency }}</span>
          </div>
        </v-card>
      </v-col>
      <!-- Outstanding Balance -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="cs-kpi-card" :class="totalBalance > 0 ? 'cs-kpi-bal-pos' : 'cs-kpi-bal-neg'" elevation="0">
          <div class="cs-kpi-top">
            <div class="cs-kpi-icon-wrap" :class="totalBalance > 0 ? 'cs-kpi-icon-red' : 'cs-kpi-icon-green'">
              <v-icon size="19">{{ totalBalance > 0 ? 'mdi-alert-circle-outline' : 'mdi-check-circle-outline' }}</v-icon>
            </div>
            <div class="cs-kpi-label">{{ $t('outstanding_balance') }}</div>
          </div>
          <div class="cs-kpi-value" :class="totalBalance > 0 ? 'text-error' : 'text-success'">
            {{ main.formatDisplayNumber(totalBalance) }} <span class="cs-kpi-cur">{{ main.currency }}</span>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Navigation Tabs (Segment buttons matching Fortnight Management) -->
    <div class="cs-nav-toolbar mb-4">
      <SegmentedPillToggle
        v-model="activeTab"
        :items="[
          { value: 'balance', label: $t('balance_grid'), icon: 'mdi-calendar-month' },
          { value: 'analytics', label: $t('analytics'), icon: 'mdi-chart-bar' }
        ]"
        color="#00838F"
      />
    </div>

    <!-- TAB: Balance Grid -->
    <div v-if="activeTab==='balance'">
      <!-- Filter and Action Toolbar -->
      <div class="d-flex flex-wrap ga-2 mb-4 align-center cs-toolbar">
        <v-autocomplete
          v-model="selectedClientIds"
          :items="clientItems"
          :label="$t('clients')"
          variant="outlined"
          density="compact"
          rounded="lg"
          color="#00838F"
          clearable
          hide-details
          multiple
          chips
          closable-chips
          small-chips
          class="flex-grow-1 cs-filter-input"
          :placeholder="$t('all')"
        >
          <template #chip="{ item }">
            <v-chip color="cyan-darken-3" variant="tonal" size="small">{{ item.title }}</v-chip>
          </template>
        </v-autocomplete>

        <v-btn
          variant="tonal"
          rounded="lg"
          size="small"
          color="#00838F"
          @click="toggleAllMonths"
          class="flex-shrink-0 font-weight-bold"
          :prepend-icon="isAllMonthsExpanded() ? 'mdi-unfold-less-horizontal' : 'mdi-unfold-more-horizontal'"
        >
          {{ isAllMonthsExpanded() ? $t('collapse_all') : $t('expand_all') }}
        </v-btn>

        <v-btn
          color="#00838F"
          variant="flat"
          rounded="lg"
          prepend-icon="mdi-plus"
          @click="openAddPayment"
          :disabled="isClosed"
          size="small"
          class="flex-shrink-0 font-weight-bold"
        >
          {{ $t('add_payment') }}
        </v-btn>

        <v-btn
          icon
          variant="tonal"
          color="success"
          size="small"
          rounded="lg"
          @click="exportExcel"
          class="flex-shrink-0"
        >
          <v-icon size="18">mdi-file-excel-outline</v-icon>
          <v-tooltip activator="parent" location="top">{{ $t('export_excel') || 'Excel' }}</v-tooltip>
        </v-btn>

        <v-btn
          icon
          variant="tonal"
          color="#00838F"
          size="small"
          rounded="lg"
          @click="prepareSettlementPrint"
          :loading="printLoading"
          class="flex-shrink-0"
        >
          <v-icon size="18">mdi-printer-outline</v-icon>
          <v-tooltip activator="parent" location="top">{{ $t('print') }}</v-tooltip>
        </v-btn>
      </div>

      <div v-if="yearInvoicesLoading || store.loading" class="text-center pa-10">
        <v-progress-circular indeterminate color="#00838F" size="44" width="3"></v-progress-circular>
      </div>

      <template v-else>
        <!-- Month Rows Accordions -->
        <div
          v-for="m in months"
          :key="m.num"
          class="cs-month-row"
          :class="{'cs-month-disabled': isFutureMonth(m.num), 'cs-month-active': expandedMonths[m.num] && !isFutureMonth(m.num)}"
        >
          <div
            class="cs-month-bar"
            @click="!isFutureMonth(m.num) && toggleMonth(m.num)"
            :style="{cursor: isFutureMonth(m.num) ? 'default' : 'pointer'}"
          >
            <div class="d-flex align-center ga-2 min-w-0">
              <v-avatar size="30" :color="isFutureMonth(m.num) ? 'grey' : '#00838F'" variant="tonal">
                <span class="text-caption font-weight-bold">{{ m.num }}</span>
              </v-avatar>
              <span class="font-weight-bold text-capitalize cs-month-name" :style="{opacity: isFutureMonth(m.num) ? '.4' : '1'}">
                {{ m.label }} {{ currentYear }}
              </span>
              <v-chip
                v-if="currentMonth.year === currentYear && currentMonth.month === m.num"
                size="x-small"
                color="#00838F"
                variant="flat"
                class="font-weight-bold ml-1"
              >
                {{ $t('current') }}
              </v-chip>
            </div>

            <div class="month-metrics-cluster">
              <v-chip size="small" color="cyan-darken-3" variant="tonal" class="font-weight-bold font-monospace">
                <v-icon size="12" start>mdi-arrow-up-circle</v-icon>
                {{ main.formatDisplayNumber(monthTotals[m.num].invoices) }} {{ main.currency }}
              </v-chip>
              <v-chip size="small" color="teal" variant="tonal" class="font-weight-bold font-monospace">
                <v-icon size="12" start>mdi-arrow-down-circle</v-icon>
                {{ main.formatDisplayNumber(monthTotals[m.num].payments) }} {{ main.currency }}
              </v-chip>
              <v-chip
                size="small"
                :color="monthTotals[m.num].balance > 0 ? 'error' : 'success'"
                variant="flat"
                class="font-weight-bold font-monospace"
              >
                = {{ main.formatDisplayNumber(monthTotals[m.num].balance) }} {{ main.currency }}
              </v-chip>
              <v-chip size="small" color="blue-grey" variant="tonal" class="font-weight-bold font-monospace">
                <v-icon size="12" start>mdi-bank-transfer</v-icon>
                {{ $t('carry_forward') }}: {{ main.formatDisplayNumber(cumulativeBalance(m.num)) }} {{ main.currency }}
              </v-chip>
              <v-btn
                v-if="!isFutureMonth(m.num)"
                icon
                variant="tonal"
                color="success"
                size="x-small"
                rounded="lg"
                @click.stop="exportMonthExcel(m.num, m.label)"
                :disabled="!invoicesByMonth[m.num].length && !paymentsByMonth[m.num].length"
              >
                <v-icon size="14">mdi-file-excel-outline</v-icon>
                <v-tooltip activator="parent" location="top">{{ $t('export_excel') || 'Excel' }}</v-tooltip>
              </v-btn>
              <v-icon
                v-if="!isFutureMonth(m.num)"
                size="20"
                class="cs-chevron"
                :class="{'cs-chevron-expanded': expandedMonths[m.num]}"
              >
                mdi-chevron-down
              </v-icon>
            </div>
          </div>

          <!-- Expanded Content: Sub-tables -->
          <v-expand-transition>
            <div v-show="expandedMonths[m.num] && !isFutureMonth(m.num)" class="cs-expanded-area pa-3 pt-1">
              <v-row dense class="g-3">
                <!-- Left Table: Invoices (with View Eye Button) -->
                <v-col cols="12" lg="6">
                  <div class="cs-sub-card">
                    <div class="cs-sub-header cs-sub-header-inv">
                      <div class="d-flex align-center ga-2">
                        <v-icon size="16" color="#00838F">mdi-file-document-outline</v-icon>
                        <span>{{ $t('client_invoices') }}</span>
                      </div>
                      <v-chip size="x-small" color="cyan-darken-3" variant="tonal" class="font-weight-bold">
                        {{ invoicesByMonth[m.num].length }}
                      </v-chip>
                    </div>
                    <div class="cs-sub-table-wrap">
                      <table class="cs-table" v-if="invoicesByMonth[m.num].length">
                        <thead>
                          <tr>
                            <th class="cs-th text-center" style="width:90px">{{ $t('date') }}</th>
                            <th class="cs-th text-left">{{ $t('clients') }}</th>
                            <th class="cs-th text-center">{{ $t('tracking_number') }}</th>
                            <th class="cs-th text-right" style="width:90px">{{ $t('weight') }}</th>
                            <th class="cs-th text-right" style="width:110px">{{ $t('price') }}</th>
                            <th class="cs-th text-center" style="width:60px">{{ $t('actions') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="inv in invoicesByMonth[m.num]" :key="inv.id" class="cs-row">
                            <td class="cs-td text-center text-caption font-monospace">{{ fmtDate(inv.date) }}</td>
                            <td class="cs-td text-left font-weight-medium text-truncate" style="max-width:140px">{{ inv._clientNames }}</td>
                            <td class="cs-td text-center">
                              <v-chip size="x-small" color="#00838F" variant="tonal">{{ inv._trackingStr }}</v-chip>
                            </td>
                            <td class="cs-td text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(inv.total_weight) }}</td>
                            <td class="cs-td text-right font-weight-bold text-cyan-darken-3 font-monospace tabular-nums">
                              {{ main.formatDisplayNumber(inv.total_price) }}
                            </td>
                            <td class="cs-td text-center">
                              <v-btn icon size="x-small" variant="tonal" color="info" rounded="lg" @click="viewInvoiceSource(inv)">
                                <v-icon size="14">mdi-eye</v-icon>
                                <v-tooltip activator="parent" location="top">{{ $t('view') }}</v-tooltip>
                              </v-btn>
                            </td>
                          </tr>
                          <tr class="cs-foot">
                            <td colspan="4" class="font-weight-bold text-right">{{ $t('total') }}</td>
                            <td class="font-weight-bold text-cyan-darken-3 text-right font-monospace tabular-nums">
                              {{ main.formatDisplayNumber(monthTotals[m.num].invoices) }}
                            </td>
                            <td></td>
                          </tr>
                        </tbody>
                      </table>
                      <div v-else class="text-center text-caption py-8 text-medium-emphasis">
                        <v-icon size="24" color="grey-lighten-1" class="d-block mx-auto mb-1">mdi-file-document-outline</v-icon>
                        {{ $t('no_data_available') || '-' }}
                      </div>
                    </div>
                  </div>
                </v-col>

                <!-- Right Table: Payments -->
                <v-col cols="12" lg="6">
                  <div class="cs-sub-card">
                    <div class="cs-sub-header cs-sub-header-pay">
                      <div class="d-flex align-center ga-2">
                        <v-icon size="16" color="teal">mdi-cash-multiple</v-icon>
                        <span>{{ $t('payments') }}</span>
                      </div>
                      <v-chip size="x-small" color="teal" variant="tonal" class="font-weight-bold">
                        {{ paymentsByMonth[m.num].length }}
                      </v-chip>
                    </div>
                    <div class="cs-sub-table-wrap">
                      <table class="cs-table" v-if="paymentsByMonth[m.num].length">
                        <thead>
                          <tr>
                            <th class="cs-th text-center" style="width:90px">{{ $t('date') }}</th>
                            <th class="cs-th text-left">{{ $t('clients') }}</th>
                            <th class="cs-th text-center" style="width:100px">{{ $t('payment_type') }}</th>
                            <th class="cs-th text-left">{{ $t('payment_reference') }}</th>
                            <th class="cs-th text-right" style="width:110px">{{ $t('payment_amount') }}</th>
                            <th class="cs-th text-center" style="width:100px">{{ $t('actions') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="p in paymentsByMonth[m.num]" :key="p.id" class="cs-row">
                            <td class="cs-td text-center text-caption font-monospace">{{ fmtDate(p.date) }}</td>
                            <td class="cs-td text-left font-weight-medium text-truncate" style="max-width:140px">{{ p._clientName }}</td>
                            <td class="cs-td text-center">
                              <v-chip
                                size="x-small"
                                :color="p.payment_type==='cash'?'success':p.payment_type==='check'?'blue':p.payment_type==='bank_transfer'?'deep-purple':'orange'"
                                variant="tonal"
                              >
                                {{ paymentTypeLabel(p.payment_type) }}
                              </v-chip>
                            </td>
                            <td class="cs-td text-left text-truncate font-monospace" style="max-width:110px">{{ p.reference || '-' }}</td>
                            <td class="cs-td text-right font-weight-bold text-teal font-monospace tabular-nums">
                              {{ main.formatDisplayNumber(p.amount) }}
                            </td>
                            <td class="cs-td text-center">
                              <v-btn icon size="x-small" variant="tonal" color="warning" rounded="lg" class="mr-1" @click="editPayment(p)" :disabled="isClosed">
                                <v-icon size="14">mdi-pencil</v-icon>
                                <v-tooltip activator="parent" location="top">{{ $t('edit') }}</v-tooltip>
                              </v-btn>
                              <v-btn icon size="x-small" variant="tonal" color="error" rounded="lg" class="mr-1" @click="confirmDelete(p.id)" :disabled="isClosed">
                                <v-icon size="14">mdi-delete</v-icon>
                                <v-tooltip activator="parent" location="top">{{ $t('delete') }}</v-tooltip>
                              </v-btn>
                              <v-btn icon size="x-small" variant="tonal" color="blue-grey" rounded="lg" @click="openAuditLog(p.id)">
                                <v-icon size="14">mdi-history</v-icon>
                                <v-tooltip activator="parent" location="top">{{ $t('audit_log') }}</v-tooltip>
                              </v-btn>
                            </td>
                          </tr>
                          <tr class="cs-foot">
                            <td colspan="4" class="font-weight-bold text-right">{{ $t('total') }}</td>
                            <td class="font-weight-bold text-teal text-right font-monospace tabular-nums">
                              {{ main.formatDisplayNumber(monthTotals[m.num].payments) }}
                            </td>
                            <td></td>
                          </tr>
                        </tbody>
                      </table>
                      <div v-else class="text-center text-caption py-8 text-medium-emphasis">
                        <v-icon size="24" color="grey-lighten-1" class="d-block mx-auto mb-1">mdi-cash-multiple</v-icon>
                        {{ $t('no_data_available') || '-' }}
                      </div>
                    </div>
                  </div>
                </v-col>
              </v-row>
            </div>
          </v-expand-transition>
        </div>

        <!-- Year Summary Section -->
        <div class="mt-6 mb-2">
          <div class="cs-section-title">
            <v-icon size="18" color="#00838F" class="mr-2">mdi-sigma</v-icon>
            {{ $t('year_summary') }}
          </div>
        </div>
        <div class="cs-table-wrap">
          <table class="cs-table">
            <thead>
              <tr>
                <th class="cs-th text-left">{{ $t('clients') }}</th>
                <th class="cs-th text-right" style="width:160px">{{ $t('carry_forward') }}</th>
                <th class="cs-th text-right" style="width:170px">{{ $t('client_invoices') }}</th>
                <th class="cs-th text-right" style="width:170px">{{ $t('payments') }}</th>
                <th class="cs-th text-right" style="width:180px">{{ $t('outstanding_balance') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in filteredYearSummary" :key="s.client_id" class="cs-row">
                <td class="cs-td text-left font-weight-bold">{{ s.client_name }}</td>
                <td class="cs-td text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(s.carry_forward) }}</td>
                <td class="cs-td text-right text-cyan-darken-3 font-weight-medium font-monospace tabular-nums">{{ main.formatDisplayNumber(s.total_invoices) }}</td>
                <td class="cs-td text-right text-teal font-weight-medium font-monospace tabular-nums">{{ main.formatDisplayNumber(s.total_payments) }}</td>
                <td class="cs-td text-right font-weight-bold font-monospace tabular-nums" :class="{'text-success': s.balance <= 0, 'text-cyan-darken-3': s.balance > 0}">
                  {{ main.formatDisplayNumber(s.balance) }} {{ main.currency }}
                </td>
              </tr>
              <tr class="cs-foot">
                <td class="font-weight-bold text-left">{{ $t('total') }}</td>
                <td class="font-weight-bold text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(totalCarryForward) }}</td>
                <td class="font-weight-bold text-cyan-darken-3 text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(totalInvoices) }}</td>
                <td class="font-weight-bold text-teal text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(totalPayments) }}</td>
                <td class="font-weight-bold text-right font-monospace tabular-nums" :class="{'text-success': totalBalance <= 0, 'text-cyan-darken-3': totalBalance > 0}">
                  {{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Year Actions Bar -->
        <div class="d-flex ga-2 mt-4 justify-end">
          <v-btn
            v-if="!isClosed"
            color="#00838F"
            variant="flat"
            rounded="lg"
            prepend-icon="mdi-lock"
            @click="closeYearDialog=true"
            class="font-weight-bold"
          >
            {{ $t('close_year') }}
          </v-btn>
          <v-btn
            v-if="isClosed"
            color="warning"
            variant="outlined"
            rounded="lg"
            prepend-icon="mdi-lock-open"
            @click="reopenDialog=true"
            class="font-weight-bold"
          >
            {{ $t('reopen_year') }}
          </v-btn>
        </div>
      </template>
    </div>

    <!-- TAB: Analytics -->
    <div v-if="activeTab==='analytics'">
      <!-- Synergized client filter -->
      <v-autocomplete
        v-model="selectedClientIds"
        :items="clientItems"
        :label="$t('clients')"
        variant="outlined"
        density="compact"
        rounded="lg"
        color="#00838F"
        hide-details
        multiple
        chips
        closable-chips
        small-chips
        class="mb-4 cs-filter-input"
        :placeholder="$t('all')"
      >
        <template #chip="{ item }">
          <v-chip color="cyan-darken-3" variant="tonal" size="small">{{ item.title }}</v-chip>
        </template>
      </v-autocomplete>
      <div class="cs-panel-card">
        <div class="cs-section-title mb-3">
          <v-icon size="18" color="#00838F" class="mr-2">mdi-chart-bar</v-icon>
          {{ $t('invoices_vs_payments') }}
        </div>
        <apexchart type="line" :height="340" :options="chartOptions" :series="chartSeries"></apexchart>
      </div>
    </div>
  </v-card-text>

  <!-- Add/Edit Payment Dialog (AddCharge Style) -->
  <v-dialog v-model="showPayForm" max-width="620" persistent>
    <v-card class="rounded-xl shadow-lg cs-dialog-card">
      <v-card-title class="pa-4 d-flex align-center cs-modal-title">
        <div class="cs-modal-icon-wrap mr-3">
          <v-icon color="#00838F" size="22">mdi-cash-plus</v-icon>
        </div>
        <span class="font-weight-bold text-subtitle-1">{{ editId ? $t('edit_payment') : $t('add_payment') }}</span>
        <v-spacer/>
        <v-btn icon variant="text" size="small" @click="showPayForm=false;resetPayForm()"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-divider/>
      <v-card-text class="pa-4 pa-md-5">
        <!-- Date Picker with Quick Presets -->
        <div class="cs-datepicker-box mb-4">
          <div class="d-flex align-center justify-space-between mb-2 px-1">
            <span class="text-caption font-weight-bold text-medium-emphasis">{{ $t('date') }}</span>
            <div class="d-flex ga-1">
              <v-btn size="x-small" variant="tonal" color="#00838F" rounded="lg" @click="setPayDateToday">{{ $t('today') }}</v-btn>
              <v-btn size="x-small" variant="tonal" color="blue-grey" rounded="lg" @click="setPayDateYesterday">{{ $t('yesterday') }}</v-btn>
            </div>
          </div>
          <div class="d-flex justify-center">
            <DatePicker v-model="payDateModel" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="teal" :min-date="minPayDate" :max-date="maxPayDate" expanded />
          </div>
        </div>

        <!-- AddCharge Form Fields -->
        <v-row dense>
          <v-col cols="12" sm="6">
            <v-autocomplete v-model="payForm.payment_type" :items="paymentTypes" :label="$t('payment_type')" variant="outlined" density="compact" rounded="lg" color="#00838F" hide-details></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6">
            <v-autocomplete v-model="payForm.client_id" :items="clientItems" :label="$t('clients')" variant="outlined" density="compact" rounded="lg" color="#00838F" hide-details @update:model-value="onClientChange"></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6" v-if="clientInvoices.length">
            <v-autocomplete v-model="payForm.client_invoice_id" :items="clientInvoices.map(i => ({ title: `${i.tracking_numbers?.join(', ') || i.id} (${main.formatDisplayNumber(i.total_price)} ${main.currency})`, value: i.id }))" :label="$t('client_invoices')" variant="outlined" density="compact" rounded="lg" color="#00838F" hide-details clearable :placeholder="$t('all')"></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field v-model.number="payForm.amount" :label="$t('payment_amount')" type="number" variant="outlined" density="compact" rounded="lg" color="#00838F" :rules="[v => v > 0 || $t('amount_must_be_positive')]" min="0.01" step="0.01" :suffix="main.currency"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-autocomplete
              ref="bankNameInput"
              v-model="payForm.bank_name"
              :items="bankNameItems"
              :label="$t('bank_name')"
              variant="outlined"
              density="compact"
              rounded="lg"
              color="#00838F"
              hide-details
              clearable
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
          <v-col cols="12" sm="6">
            <v-text-field v-model="payForm.reference" :label="$t('payment_reference')" variant="outlined" density="compact" rounded="lg" color="#00838F" hide-details></v-text-field>
          </v-col>
          <v-col cols="12">
            <v-text-field v-model="payForm.notes" :label="$t('notes')" variant="outlined" density="compact" rounded="lg" color="#00838F" hide-details></v-text-field>
          </v-col>
        </v-row>
      </v-card-text>
      <v-divider/>
      <v-card-actions class="pa-4">
        <v-spacer/>
        <v-btn variant="text" rounded="lg" @click="showPayForm=false;resetPayForm()">{{ $t('cancel') }}</v-btn>
        <v-btn color="#00838F" variant="flat" rounded="lg" prepend-icon="mdi-content-save" :loading="saving" @click="savePayment" :disabled="!payForm.client_id||!payForm.amount||payForm.amount<=0||!payForm.date" class="font-weight-bold">
          {{ $t('save') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Delete Dialog -->
  <v-dialog v-model="deleteDialog" max-width="450">
    <v-card class="rounded-xl">
      <v-card-title class="pa-5 pb-2">
        <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
        {{ $t('confirm_delete') }}
      </v-card-title>
      <v-card-text class="pa-5 pt-2">
        {{ deleteBulk ? $t('confirm_bulk_delete_message',{count:selected.length}) : $t('confirm_delete_payment_message') }}
      </v-card-text>
      <v-card-actions class="pa-5 pt-0">
        <v-spacer/>
        <v-btn @click="deleteDialog=false" variant="text" rounded="lg">{{ $t('cancel') }}</v-btn>
        <v-btn color="error" variant="flat" rounded="lg" @click="execDelete" :loading="deleting" class="font-weight-bold">{{ $t('delete') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Close Year Dialog -->
  <v-dialog v-model="closeYearDialog" max-width="500">
    <v-card class="rounded-xl">
      <v-card-title class="pa-5 pb-2">
        <v-icon color="#00838F" class="mr-2">mdi-lock</v-icon>
        {{ $t('fiscal_year_closing') }}
      </v-card-title>
      <v-card-text class="pa-5 pt-2">
        {{ $t('confirm_close_year', {year:currentYear}) }}
      </v-card-text>
      <v-card-actions class="pa-5 pt-0">
        <v-spacer/>
        <v-btn @click="closeYearDialog=false" variant="text" rounded="lg">{{ $t('cancel') }}</v-btn>
        <v-btn color="#00838F" variant="flat" rounded="lg" @click="doCloseYear" :loading="closingYear" class="font-weight-bold">{{ $t('close_year') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Reopen Year Dialog -->
  <v-dialog v-model="reopenDialog" max-width="450">
    <v-card class="rounded-xl">
      <v-card-title class="pa-5 pb-2">
        <v-icon color="warning" class="mr-2">mdi-lock-open</v-icon>
        {{ $t('reopen_year') }}
      </v-card-title>
      <v-card-text class="pa-5 pt-2">
        {{ $t('confirm_reopen_year', {year:currentYear}) }}
      </v-card-text>
      <v-card-actions class="pa-5 pt-0">
        <v-spacer/>
        <v-btn @click="reopenDialog=false" variant="text" rounded="lg">{{ $t('cancel') }}</v-btn>
        <v-btn color="warning" variant="flat" rounded="lg" @click="doReopenYear" :loading="reopening" class="font-weight-bold">{{ $t('reopen_year') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Summary Snackbar with Countdown Progress Bar (Rule 5 & AddCharge style) -->
  <v-snackbar
    v-model="summarySnackbar.show"
    :timeout="5000"
    location="top right"
    color="surface"
    rounded="lg"
    elevation="12"
    min-width="340"
    max-width="400"
    @update:modelValue="val => { if (!val && snackbarTimer) clearInterval(snackbarTimer); }"
  >
    <v-progress-linear :model-value="snackbarProgress" color="#00838F" height="3" class="mb-2" rounded></v-progress-linear>
    <div class="d-flex align-center mb-1">
      <v-icon :color="summarySnackbar.color || 'success'" class="mr-2">
        {{ summarySnackbar.color === 'error' ? 'mdi-alert-circle' : summarySnackbar.color === 'info' ? 'mdi-information-outline' : 'mdi-check-circle' }}
      </v-icon>
      <span class="text-subtitle-2 font-weight-bold">{{ summarySnackbar.title }}</span>
    </div>
    <div v-if="summarySnackbar.detail" class="text-caption text-medium-emphasis ms-7">
      {{ summarySnackbar.detail }}
    </div>
    <template #actions>
      <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
    </template>
  </v-snackbar>

  <!-- Print Trigger Floating Bar -->
  <div v-if="printReady" class="d-flex align-center ga-2 pa-3" style="position:fixed;bottom:20px;right:20px;z-index:999;background:rgba(0,131,143,.95);border-radius:16px;box-shadow:0 8px 32px rgba(0,131,143,.4)">
    <v-icon color="white" size="20">mdi-file-document-outline</v-icon>
    <span class="text-white text-body-2 font-weight-bold mr-2">{{ $t('client_settlement_bulletin') }} — {{ currentYear }}</span>
    <v-btn v-print="printCfg" color="white" variant="flat" size="small" rounded="lg" prepend-icon="mdi-printer" style="color:#00838F">{{ $t('print') }}</v-btn>
    <v-btn icon size="x-small" variant="text" color="white" @click="printReady = false"><v-icon size="18">mdi-close</v-icon></v-btn>
  </div>
</v-card>

<!-- Hidden Print Area -->
<div style="display:none"><div id="cs-print-area">
  <div style="padding:20px 28px;max-width:1060px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:12px;color:#1a1a1a">
    <!-- Header with Logo & Company -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
      <div><img :src="logoUrl" alt="Logo" style="height:48px" /></div>
      <div style="text-align:center;flex:1">
        <h1 style="font-size:20px;font-weight:800;color:#00838F;margin:0;letter-spacing:.5px">{{ $t('client_settlement_bulletin') }}</h1>
        <p style="font-size:11px;color:#78909C;margin:2px 0 0">{{ companySettings.companyName }} — {{ companySettings.companyAddress }}</p>
      </div>
      <div style="text-align:right;font-size:10px;color:#546E7A">
        <div><b>{{ $t('year') }}:</b> {{ currentYear }}</div>
        <div><b>{{ $t('date') }}:</b> {{ new Date().toLocaleDateString() }}</div>
        <div v-if="printClientLabel"><b>{{ $t('clients') }}:</b> {{ printClientLabel }}</div>
      </div>
    </div>
    <div style="height:3px;background:linear-gradient(90deg,#006064,#26C6DA,#006064);border-radius:2px;margin:8px 0 14px"></div>

    <!-- Summary Stats Row -->
    <table style="width:100%;border-collapse:collapse;margin-bottom:14px">
      <tr>
        <td class="cs-ps" style="background:#E0F7FA;border:1px solid #B2EBF2"><div class="cs-ps-label">{{ $t('carry_forward') }}</div><div class="cs-ps-val" style="color:#006064">{{ main.formatDisplayNumber(totalCarryForward) }} {{ main.currency }}</div></td>
        <td class="cs-ps" style="background:#E0F2F1;border:1px solid #B2DFDB"><div class="cs-ps-label">{{ $t('client_invoices') }}</div><div class="cs-ps-val" style="color:#00838F">{{ main.formatDisplayNumber(totalInvoices) }} {{ main.currency }}</div></td>
        <td class="cs-ps" style="background:#E8F5E9;border:1px solid #C8E6C9"><div class="cs-ps-label">{{ $t('payments') }}</div><div class="cs-ps-val" style="color:#00695C">{{ main.formatDisplayNumber(totalPayments) }} {{ main.currency }}</div></td>
        <td class="cs-ps" :style="totalBalance>0?'background:#FFEBEE;border:1px solid #FFCDD2':'background:#E8F5E9;border:1px solid #C8E6C9'"><div class="cs-ps-label">{{ $t('outstanding_balance') }}</div><div class="cs-ps-val" :style="totalBalance>0?'color:#C62828':'color:#2E7D32'">{{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}</div></td>
      </tr>
    </table>

    <!-- Month-by-Month Breakdown -->
    <template v-for="m in printMonths" :key="m.num">
      <!-- Month Header -->
      <div style="background:linear-gradient(90deg,#006064,#00838F);color:#fff;padding:6px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;border-radius:4px 4px 0 0;margin-top:10px;display:flex;justify-content:space-between;align-items:center">
        <span>{{ m.label }} {{ currentYear }}</span>
        <span style="font-size:10px;opacity:.85">
          {{ $t('client_invoices') }}: {{ main.formatDisplayNumber(monthTotals[m.num].invoices) }} {{ main.currency }}
          &nbsp;|&nbsp;
          {{ $t('payments') }}: {{ main.formatDisplayNumber(monthTotals[m.num].payments) }} {{ main.currency }}
          &nbsp;|&nbsp;
          {{ $t('outstanding_balance') }}: {{ main.formatDisplayNumber(monthTotals[m.num].balance) }} {{ main.currency }}
        </span>
      </div>
      <!-- Side-by-Side Tables -->
      <table style="width:100%;border-collapse:collapse;border:1px solid #E0E0E0;border-top:none">
        <tr>
          <!-- LEFT: Invoices -->
          <td style="width:50%;vertical-align:top;padding:0;border-right:2px solid #00838F">
            <table style="width:100%;border-collapse:collapse;font-size:11px">
              <thead><tr>
                <th class="cs-ph" colspan="4" style="background:#E0F7FA;color:#006064;border-bottom:1px solid #B2EBF2;font-size:10px;padding:5px 6px">{{ $t('client_invoices') }} ({{ invoicesByMonth[m.num].length }})</th>
              </tr><tr>
                <th class="cs-ph2">{{ $t('date') }}</th>
                <th class="cs-ph2">{{ $t('clients') }}</th>
                <th class="cs-ph2">{{ $t('weight') }}</th>
                <th class="cs-ph2">{{ $t('price') }}</th>
              </tr></thead>
              <tbody>
                <tr v-for="inv in invoicesByMonth[m.num]" :key="'pi'+inv.id">
                  <td class="cs-pv">{{ fmtDate(inv.date) }}</td>
                  <td class="cs-pv">{{ inv._clientNames }}</td>
                  <td class="cs-pv" style="text-align:right">{{ main.formatDisplayNumber(inv.total_weight) }}</td>
                  <td class="cs-pv" style="text-align:right;font-weight:600;color:#00838F">{{ main.formatDisplayNumber(inv.total_price) }}</td>
                </tr>
                <tr v-if="!invoicesByMonth[m.num].length"><td class="cs-pv" colspan="4" style="text-align:center;opacity:.4;padding:6px">—</td></tr>
                <tr style="background:#E0F7FA"><td colspan="3" class="cs-pv" style="text-align:right;font-weight:700;font-size:10px">{{ $t('total') }}</td><td class="cs-pv" style="text-align:right;font-weight:700;color:#00838F">{{ main.formatDisplayNumber(monthTotals[m.num].invoices) }}</td></tr>
              </tbody>
            </table>
          </td>
          <!-- RIGHT: Payments -->
          <td style="width:50%;vertical-align:top;padding:0">
            <table style="width:100%;border-collapse:collapse;font-size:11px">
              <thead><tr>
                <th class="cs-ph" colspan="4" style="background:#E0F2F1;color:#00695C;border-bottom:1px solid #B2DFDB;font-size:10px;padding:5px 6px">{{ $t('payments') }} ({{ paymentsByMonth[m.num].length }})</th>
              </tr><tr>
                <th class="cs-ph2">{{ $t('date') }}</th>
                <th class="cs-ph2">{{ $t('clients') }}</th>
                <th class="cs-ph2">{{ $t('payment_type') }}</th>
                <th class="cs-ph2">{{ $t('payment_amount') }}</th>
              </tr></thead>
              <tbody>
                <tr v-for="p in paymentsByMonth[m.num]" :key="'pp'+p.id">
                  <td class="cs-pv">{{ fmtDate(p.date) }}</td>
                  <td class="cs-pv">{{ p._clientName }}</td>
                  <td class="cs-pv">{{ paymentTypeLabel(p.payment_type) }}</td>
                  <td class="cs-pv" style="text-align:right;font-weight:600;color:#00695C">{{ main.formatDisplayNumber(p.amount) }}</td>
                </tr>
                <tr v-if="!paymentsByMonth[m.num].length"><td class="cs-pv" colspan="4" style="text-align:center;opacity:.4;padding:6px">—</td></tr>
                <tr style="background:#E0F2F1"><td colspan="3" class="cs-pv" style="text-align:right;font-weight:700;font-size:10px">{{ $t('total') }}</td><td class="cs-pv" style="text-align:right;font-weight:700;color:#00695C">{{ main.formatDisplayNumber(monthTotals[m.num].payments) }}</td></tr>
              </tbody>
            </table>
          </td>
        </tr>
      </table>
    </template>

    <!-- Year Summary Table -->
    <div style="margin-top:16px">
      <div style="background:#00838F;color:#fff;padding:6px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;border-radius:4px 4px 0 0">{{ $t('year_summary') }} — {{ currentYear }}</div>
      <table style="width:100%;border-collapse:collapse;border:1px solid #E0E0E0;font-size:11px">
        <thead>
          <tr>
            <th class="cs-ph3" style="text-align:left">{{ $t('clients') }}</th>
            <th class="cs-ph3" style="text-align:right">{{ $t('carry_forward') }}</th>
            <th class="cs-ph3" style="text-align:right">{{ $t('client_invoices') }}</th>
            <th class="cs-ph3" style="text-align:right">{{ $t('payments') }}</th>
            <th class="cs-ph3" style="text-align:right">{{ $t('outstanding_balance') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in filteredYearSummary" :key="'ps'+s.client_id">
            <td class="cs-pv" style="font-weight:600">{{ s.client_name }}</td>
            <td class="cs-pv" style="text-align:right">{{ main.formatDisplayNumber(s.carry_forward) }}</td>
            <td class="cs-pv" style="text-align:right;color:#00838F">{{ main.formatDisplayNumber(s.total_invoices) }}</td>
            <td class="cs-pv" style="text-align:right;color:#00695C">{{ main.formatDisplayNumber(s.total_payments) }}</td>
            <td class="cs-pv" style="text-align:right;font-weight:700" :style="s.balance>0?'color:#C62828':'color:#2E7D32'">{{ main.formatDisplayNumber(s.balance) }} {{ main.currency }}</td>
          </tr>
          <tr style="background:#E0F7FA;font-weight:700">
            <td class="cs-pv" style="font-weight:800">{{ $t('total') }}</td>
            <td class="cs-pv" style="text-align:right">{{ main.formatDisplayNumber(totalCarryForward) }}</td>
            <td class="cs-pv" style="text-align:right;color:#00838F">{{ main.formatDisplayNumber(totalInvoices) }}</td>
            <td class="cs-pv" style="text-align:right;color:#00695C">{{ main.formatDisplayNumber(totalPayments) }}</td>
            <td class="cs-pv" style="text-align:right;font-weight:800" :style="totalBalance>0?'color:#C62828':'color:#2E7D32'">{{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Statistics Footer -->
    <div style="margin-top:14px;display:flex;gap:16px;page-break-inside:avoid">
      <div style="flex:1;border:1px solid #B2EBF2;border-radius:6px;padding:10px;background:#E0F7FA">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#006064;margin-bottom:6px;letter-spacing:.5px">{{ $t('settlement_stats_invoices') }}</div>
        <div style="font-size:11px;display:flex;flex-direction:column;gap:3px">
          <div><span style="color:#78909C">{{ $t('total_invoices_count') }}:</span> <b>{{ printStats.invoiceCount }}</b></div>
          <div><span style="color:#78909C">{{ $t('total_weight') }}:</span> <b>{{ main.formatDisplayNumber(printStats.totalWeight) }} KG</b></div>
          <div><span style="color:#78909C">{{ $t('total') }}:</span> <b style="color:#00838F">{{ main.formatDisplayNumber(totalInvoices) }} {{ main.currency }}</b></div>
        </div>
      </div>
      <div style="flex:1;border:1px solid #B2DFDB;border-radius:6px;padding:10px;background:#E0F2F1">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#00695C;margin-bottom:6px;letter-spacing:.5px">{{ $t('settlement_stats_payments') }}</div>
        <div style="font-size:11px;display:flex;flex-direction:column;gap:3px">
          <div><span style="color:#78909C">{{ $t('total_payments_count') }}:</span> <b>{{ printStats.paymentCount }}</b></div>
          <div><span style="color:#78909C">{{ $t('settlement_payment_methods') }}:</span> <b>{{ printStats.paymentMethodsSummary }}</b></div>
          <div><span style="color:#78909C">{{ $t('total') }}:</span> <b style="color:#00695C">{{ main.formatDisplayNumber(totalPayments) }} {{ main.currency }}</b></div>
        </div>
      </div>
      <div style="flex:1;border-radius:6px;padding:10px" :style="totalBalance>0?'border:1px solid #FFCDD2;background:#FFEBEE':'border:1px solid #C8E6C9;background:#E8F5E9'">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:6px;letter-spacing:.5px" :style="totalBalance>0?'color:#C62828':'color:#2E7D32'">{{ $t('settlement_stats_balance') }}</div>
        <div style="font-size:11px;display:flex;flex-direction:column;gap:3px">
          <div><span style="color:#78909C">{{ $t('carry_forward') }}:</span> <b>{{ main.formatDisplayNumber(totalCarryForward) }} {{ main.currency }}</b></div>
          <div><span style="color:#78909C">{{ $t('net_movement') }}:</span> <b>{{ main.formatDisplayNumber(totalInvoices - totalPayments) }} {{ main.currency }}</b></div>
          <div><span style="color:#78909C">{{ $t('outstanding_balance') }}:</span> <b :style="totalBalance>0?'color:#C62828':'color:#2E7D32'">{{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}</b></div>
        </div>
      </div>
    </div>

    <!-- Signatures -->
    <div style="display:flex;justify-content:space-between;margin-top:40px;gap:60px">
      <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:36px;margin-bottom:6px"></div><div style="font-size:10px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('la_direction') }}</div></div>
      <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:36px;margin-bottom:6px"></div><div style="font-size:10px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('company_signature') }}</div></div>
    </div>
    <div style="text-align:center;margin-top:16px;font-size:9px;color:#9E9E9E">{{ $t('printed_on') }}: {{ new Date().toLocaleString() }}</div>
  </div>
</div></div>

<AuditLogDialog v-model="auditLogDialogOpen" auditable-type="ClientPayment" :auditable-id="auditLogRecordId" module-color="#00838F" />
</template>

<script>
import script from './script.js';
import print from 'vue3-print-nb';
import AuditLogDialog from '@/components/shared/AuditLogDialog.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
export default {
  ...script,
  components: { ...script.components, AuditLogDialog, SegmentedPillToggle },
  directives: { print },
  data(){ return { ...script.data.call(this), printCfg: { id: 'cs-print-area', popTitle: 'Client Settlement' }, auditLogDialogOpen: false, auditLogRecordId: 0 }; },
  methods: { ...script.methods, openAuditLog(id) { this.auditLogRecordId = id; this.auditLogDialogOpen = true; } },
};
</script>

<style scoped>
.cs-card {
  border-radius: 20px !important;
  font-family: 'Inter', sans-serif;
  overflow: visible;
}
.cs-hero {
  background: linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  min-height: 104px;
}
.cs-hero-inner {
  position: relative;
  z-index: 1;
  padding: 22px 26px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}
.cs-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255,255,255,.2);
  box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
.cs-hero-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: .2px;
  color: #fff;
  line-height: 1.25;
}
.cs-hero-subtitle {
  font-size: 12.5px;
  color: rgba(255,255,255,.85);
  font-weight: 400;
  margin-top: 2px;
}
.cs-hero-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}
.cs-year-pill {
  display: inline-flex;
  align-items: center;
  background: rgba(255,255,255,.2);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 9999px;
  padding: 2px 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.cs-year-btn {
  color: #fff !important;
}
.cs-year-text {
  font-size: 15px;
  font-weight: 800;
  color: #fff;
  padding: 0 10px;
  min-width: 50px;
  text-align: center;
  letter-spacing: .5px;
}

/* KPI Cards */
.cs-kpi-card {
  border-radius: 16px !important;
  border: 1.5px solid rgba(var(--v-border-color), .12);
  padding: 16px 18px;
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
  background: rgb(var(--v-theme-surface));
  position: relative;
  overflow: hidden;
}
.cs-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,.06);
  border-color: rgba(var(--v-border-color), .24);
}
.cs-kpi-carry { border-top: 3.5px solid #00838F !important; }
.cs-kpi-invoices { border-top: 3.5px solid #00ACC1 !important; }
.cs-kpi-payments { border-top: 3.5px solid #00897B !important; }
.cs-kpi-bal-pos { border-top: 3.5px solid #E53935 !important; }
.cs-kpi-bal-neg { border-top: 3.5px solid #43A047 !important; }

.cs-kpi-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}
.cs-kpi-icon-wrap {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.cs-kpi-icon-blue { background: rgba(0,131,143,.14); color: #00838F; }
.cs-kpi-icon-cyan { background: rgba(0,172,193,.14); color: #00ACC1; }
.cs-kpi-icon-teal { background: rgba(0,137,123,.14); color: #00897B; }
.cs-kpi-icon-red { background: rgba(229,57,53,.14); color: #E53935; }
.cs-kpi-icon-green { background: rgba(67,160,71,.14); color: #43A047; }

.cs-kpi-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: rgba(var(--v-theme-on-surface), .65);
}
.cs-kpi-value {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -.3px;
  font-feature-settings: "tnum";
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
}
.cs-kpi-cur {
  font-size: 12px;
  font-weight: 600;
  opacity: .7;
}

/* Navigation Tabs Toolbar */
.cs-nav-toolbar {
  display: flex;
  align-items: center;
  padding: 2px 0;
}

/* Month rows */
.cs-month-row {
  border: 1.5px solid rgba(var(--v-border-color), .12);
  border-radius: 16px;
  background: rgb(var(--v-theme-surface));
  margin-bottom: 12px;
  transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
  overflow: hidden;
}
.cs-month-row:hover:not(.cs-month-disabled) {
  border-color: rgba(0,131,143, .35);
  box-shadow: 0 4px 16px rgba(0,0,0,.04);
}
.cs-month-active {
  border-color: rgba(0,131,143, .4) !important;
}
.cs-month-disabled {
  opacity: .42;
  pointer-events: none;
}
.cs-month-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  gap: 12px;
  flex-wrap: wrap;
  background: rgba(var(--v-theme-on-surface), .02);
}
.cs-month-name {
  font-size: 14px;
  letter-spacing: .2px;
}
.month-metrics-cluster {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-left: auto;
}
.cs-chevron {
  transition: transform .25s ease;
  color: rgba(var(--v-theme-on-surface), .6);
}
.cs-chevron-expanded {
  transform: rotate(180deg);
  color: #00838F;
}

/* Sub tables in expanded month */
.cs-expanded-area {
  border-top: 1px solid rgba(var(--v-border-color), .08);
  background: rgba(var(--v-theme-on-surface), .015);
}
.cs-sub-card {
  border: 1px solid rgba(var(--v-border-color), .1);
  border-radius: 12px;
  overflow: hidden;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 2px 8px rgba(0,0,0,.02);
}
.cs-sub-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 14px;
  border-bottom: 1px solid rgba(var(--v-border-color), .08);
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
}
.cs-sub-header-inv {
  background: rgba(0,131,143, .05);
  color: #00838F;
}
.cs-sub-header-pay {
  background: rgba(0,137,123, .05);
  color: #00695C;
}
.cs-sub-table-wrap {
  overflow-x: auto;
  max-height: 380px;
}

/* Base Tables */
.cs-table-wrap {
  overflow: auto;
  max-height: calc(100vh - 380px);
  border-radius: 14px;
  border: 1.5px solid rgba(var(--v-border-color), .12);
  background: rgb(var(--v-theme-surface));
}
.cs-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}
.cs-th {
  padding: 9px 12px;
  border-bottom: 1.5px solid rgba(var(--v-border-color), .12);
  background: rgba(var(--v-theme-on-surface), .03);
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
  color: rgba(var(--v-theme-on-surface), .7);
  white-space: nowrap;
}
.cs-td {
  padding: 8px 12px;
  border-bottom: 1px solid rgba(var(--v-border-color), .06);
  white-space: nowrap;
  font-size: 12px;
  vertical-align: middle;
}
.cs-row {
  transition: background-color .15s ease;
}
.cs-row:hover {
  background: rgba(0,131,143, .04);
}
.cs-foot td {
  padding: 10px 12px;
  border-top: 2px solid rgba(0,131,143, .25);
  background: rgba(var(--v-theme-on-surface), .02);
  font-size: 12.5px;
}

.cs-section-title {
  font-size: 13.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: rgba(var(--v-theme-on-surface), .8);
  display: flex;
  align-items: center;
}
.cs-panel-card {
  border: 1.5px solid rgba(0,131,143, .14);
  border-radius: 16px;
  padding: 20px;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 4px 16px rgba(0,0,0,.03);
}

/* AddCharge Modal Styling */
.cs-modal-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: rgba(0,131,143,.12);
  display: flex;
  align-items: center;
  justify-content: center;
}
.cs-datepicker-box {
  background: rgba(var(--v-theme-on-surface), .02);
  border: 1px solid rgba(var(--v-border-color), .1);
  border-radius: 12px;
  padding: 12px;
}
.font-monospace {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace, sans-serif !important;
}
.tabular-nums {
  font-feature-settings: "tnum";
  font-variant-numeric: tabular-nums;
}
</style>

<style>
/* Print area styles (unscoped) */
.cs-ps{padding:8px 14px;text-align:center;border-radius:4px}
.cs-ps-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#78909C;margin-bottom:2px}
.cs-ps-val{font-size:15px;font-weight:800}
.cs-ph{font-weight:700;text-transform:uppercase;letter-spacing:.3px;text-align:center}
.cs-ph2{background:#F5F5F5;color:#546E7A;font-size:9px;font-weight:700;text-transform:uppercase;padding:4px 6px;border-bottom:1px solid #E0E0E0;text-align:center;letter-spacing:.3px}
.cs-ph3{background:#E0F7FA;color:#00838F;font-size:10px;font-weight:700;text-transform:uppercase;padding:7px 10px;border:1px solid #B2EBF2;text-align:center;letter-spacing:.3px}
#cs-print-area .cs-pv{padding:4px 6px;border-bottom:1px solid #EEEEEE;font-size:11px;color:#212121}
@media print { #cs-print-area { display: block !important; } }
</style>
