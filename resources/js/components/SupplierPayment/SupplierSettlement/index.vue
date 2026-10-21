<template>
<v-card class="ss-card" elevation="0">
  <!-- Hero Header -->
  <div class="ss-hero">
    <div class="ss-hero-inner">
      <div class="d-flex align-center ga-3 flex-wrap">
        <div class="ss-hero-icon">
          <v-icon size="30" color="white">mdi-cash-register</v-icon>
        </div>
        <div>
          <div class="ss-hero-title">{{ $t('supplier_settlement') }}</div>
          <div class="ss-hero-subtitle">{{ $t('supplier_settlement_desc') }}</div>
        </div>
      </div>
      <div class="ss-hero-actions">
        <!-- Year Switcher Pill -->
        <div class="ss-year-pill">
          <v-btn icon variant="text" size="x-small" @click="changeYear(-1)" class="ss-year-btn">
            <v-icon size="20">mdi-chevron-left</v-icon>
          </v-btn>
          <span class="ss-year-text">{{ currentYear }}</span>
          <v-btn icon variant="text" size="x-small" @click="changeYear(1)" class="ss-year-btn">
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
        <v-card class="ss-kpi-card ss-kpi-carry" elevation="0">
          <div class="ss-kpi-top">
            <div class="ss-kpi-icon-wrap ss-kpi-icon-amber">
              <v-icon size="19">mdi-bank-transfer</v-icon>
            </div>
            <div class="ss-kpi-label">{{ $t('carry_forward') }}</div>
          </div>
          <div class="ss-kpi-value text-amber-darken-3">
            {{ main.formatDisplayNumber(totalCarryForward) }} <span class="ss-kpi-cur">{{ main.currency }}</span>
          </div>
        </v-card>
      </v-col>
      <!-- Invoices -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="ss-kpi-card ss-kpi-invoices" elevation="0">
          <div class="ss-kpi-top">
            <div class="ss-kpi-icon-wrap ss-kpi-icon-orange">
              <v-icon size="19">mdi-file-document-outline</v-icon>
            </div>
            <div class="ss-kpi-label">{{ $t('supplier_invoices') }}</div>
          </div>
          <div class="ss-kpi-value text-deep-orange">
            {{ main.formatDisplayNumber(totalInvoices) }} <span class="ss-kpi-cur">{{ main.currency }}</span>
          </div>
        </v-card>
      </v-col>
      <!-- Payments -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="ss-kpi-card ss-kpi-payments" elevation="0">
          <div class="ss-kpi-top">
            <div class="ss-kpi-icon-wrap ss-kpi-icon-teal">
              <v-icon size="19">mdi-cash-multiple</v-icon>
            </div>
            <div class="ss-kpi-label">{{ $t('payments') }}</div>
          </div>
          <div class="ss-kpi-value text-teal">
            {{ main.formatDisplayNumber(totalPayments) }} <span class="ss-kpi-cur">{{ main.currency }}</span>
          </div>
        </v-card>
      </v-col>
      <!-- Outstanding Balance -->
      <v-col cols="12" sm="6" lg="3">
        <v-card class="ss-kpi-card" :class="totalBalance > 0 ? 'ss-kpi-bal-pos' : 'ss-kpi-bal-neg'" elevation="0">
          <div class="ss-kpi-top">
            <div class="ss-kpi-icon-wrap" :class="totalBalance > 0 ? 'ss-kpi-icon-red' : 'ss-kpi-icon-green'">
              <v-icon size="19">{{ totalBalance > 0 ? 'mdi-alert-circle-outline' : 'mdi-check-circle-outline' }}</v-icon>
            </div>
            <div class="ss-kpi-label">{{ $t('outstanding_balance') }}</div>
          </div>
          <div class="ss-kpi-value" :class="totalBalance > 0 ? 'text-error' : 'text-success'">
            {{ main.formatDisplayNumber(totalBalance) }} <span class="ss-kpi-cur">{{ main.currency }}</span>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Navigation Tabs (Segment buttons matching Fortnight Management) -->
    <div class="ss-nav-toolbar mb-4">
      <SegmentedPillToggle
        v-model="activeTab"
        :items="[
          { value: 'balance', label: $t('balance_grid'), icon: 'mdi-calendar-month' },
          { value: 'analytics', label: $t('analytics'), icon: 'mdi-chart-bar' }
        ]"
        color="#E65100"
      />
    </div>

    <!-- TAB: Balance Grid -->
    <div v-if="activeTab==='balance'">
      <!-- Filter and Action Toolbar -->
      <div class="d-flex flex-wrap ga-2 mb-4 align-center ss-toolbar">
        <v-autocomplete
          v-model="selectedSupplierIds"
          :items="supplierItems"
          :label="$t('suppliers')"
          variant="outlined"
          density="compact"
          rounded="lg"
          color="#E65100"
          clearable
          hide-details
          multiple
          chips
          closable-chips
          small-chips
          class="flex-grow-1 ss-filter-input"
          :placeholder="$t('all')"
        >
          <template #chip="{ item }">
            <v-chip color="deep-orange" variant="tonal" size="small">{{ item.title }}</v-chip>
          </template>
        </v-autocomplete>

        <v-btn
          variant="tonal"
          rounded="lg"
          size="small"
          color="#E65100"
          @click="toggleAllMonths"
          class="flex-shrink-0 font-weight-bold"
          :prepend-icon="isAllMonthsExpanded() ? 'mdi-unfold-less-horizontal' : 'mdi-unfold-more-horizontal'"
        >
          {{ isAllMonthsExpanded() ? $t('collapse_all') : $t('expand_all') }}
        </v-btn>

        <v-btn
          color="#E65100"
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
          color="#E65100"
          size="small"
          rounded="lg"
          @click="openPrintCustomizer"
          :loading="printLoading"
          class="flex-shrink-0"
        >
          <v-icon size="18">mdi-printer-outline</v-icon>
          <v-tooltip activator="parent" location="top">{{ $t('print') }}</v-tooltip>
        </v-btn>
      </div>

      <div v-if="yearInvoicesLoading || store.loading" class="text-center pa-10">
        <v-progress-circular indeterminate color="#E65100" size="44" width="3"></v-progress-circular>
      </div>

      <template v-else>
        <!-- Month Rows Accordions -->
        <div
          v-for="m in months"
          :key="m.num"
          class="ss-month-row"
          :class="{'ss-month-disabled': isFutureMonth(m.num), 'ss-month-active': expandedMonths[m.num] && !isFutureMonth(m.num)}"
        >
          <div
            class="ss-month-bar"
            @click="!isFutureMonth(m.num) && toggleMonth(m.num)"
            :style="{cursor: isFutureMonth(m.num) ? 'default' : 'pointer'}"
          >
            <div class="d-flex align-center ga-2 min-w-0">
              <v-avatar size="30" :color="isFutureMonth(m.num) ? 'grey' : '#E65100'" variant="tonal">
                <span class="text-caption font-weight-bold">{{ m.num }}</span>
              </v-avatar>
              <span class="font-weight-bold text-capitalize ss-month-name" :style="{opacity: isFutureMonth(m.num) ? '.4' : '1'}">
                {{ m.label }} {{ currentYear }}
              </span>
              <v-chip
                v-if="currentMonth.year === currentYear && currentMonth.month === m.num"
                size="x-small"
                color="#E65100"
                variant="flat"
                class="font-weight-bold ml-1"
              >
                {{ $t('current') }}
              </v-chip>
            </div>

            <div class="month-metrics-cluster">
              <v-chip size="small" color="deep-orange" variant="tonal" class="font-weight-bold font-monospace">
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
                class="ss-chevron"
                :class="{'ss-chevron-expanded': expandedMonths[m.num]}"
              >
                mdi-chevron-down
              </v-icon>
            </div>
          </div>

          <!-- Expanded Content: Sub-tables -->
          <v-expand-transition>
            <div v-show="expandedMonths[m.num] && !isFutureMonth(m.num)" class="ss-expanded-area pa-3 pt-1">
              <v-row dense class="g-3">
                <!-- Left Table: Invoices -->
                <v-col cols="12" lg="6">
                  <div class="ss-sub-card">
                    <div class="ss-sub-header ss-sub-header-inv">
                      <div class="d-flex align-center ga-2">
                        <v-icon size="16" color="#E65100">mdi-file-document-outline</v-icon>
                        <span>{{ $t('supplier_invoices') }}</span>
                      </div>
                      <v-chip size="x-small" color="deep-orange" variant="tonal" class="font-weight-bold">
                        {{ invoicesByMonth[m.num].length }}
                      </v-chip>
                    </div>
                    <div class="ss-sub-table-wrap">
                      <table class="ss-table" v-if="invoicesByMonth[m.num].length">
                        <thead>
                          <tr>
                            <th class="ss-th text-center" style="width:90px">{{ $t('date') }}</th>
                            <th class="ss-th text-left">{{ $t('suppliers') }}</th>
                            <th class="ss-th text-center">{{ $t('tracking_number') }}</th>
                            <th class="ss-th text-right" style="width:90px">{{ $t('weight') }}</th>
                            <th class="ss-th text-right" style="width:110px">{{ $t('price') }}</th>
                            <th class="ss-th text-center" style="width:60px">{{ $t('actions') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="inv in invoicesByMonth[m.num]" :key="inv.id" class="ss-row">
                            <td class="ss-td text-center text-caption font-monospace">{{ fmtDate(inv.date) }}</td>
                            <td class="ss-td text-left font-weight-medium text-truncate" style="max-width:140px">{{ inv._supplierNames }}</td>
                            <td class="ss-td text-center">
                              <v-chip size="x-small" color="#E65100" variant="tonal">{{ inv._trackingStr }}</v-chip>
                            </td>
                            <td class="ss-td text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(inv.total_weight) }}</td>
                            <td class="ss-td text-right font-weight-bold text-deep-orange font-monospace tabular-nums">
                              {{ main.formatDisplayNumber(inv.total_price) }}
                            </td>
                            <td class="ss-td text-center">
                              <v-btn icon size="x-small" variant="tonal" color="info" rounded="lg" @click="viewInvoiceSource(inv)">
                                <v-icon size="14">mdi-eye</v-icon>
                                <v-tooltip activator="parent" location="top">{{ $t('view') }}</v-tooltip>
                              </v-btn>
                            </td>
                          </tr>
                          <tr class="ss-foot">
                            <td colspan="4" class="font-weight-bold text-right">{{ $t('total') }}</td>
                            <td class="font-weight-bold text-deep-orange text-right font-monospace tabular-nums">
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
                  <div class="ss-sub-card">
                    <div class="ss-sub-header ss-sub-header-pay">
                      <div class="d-flex align-center ga-2">
                        <v-icon size="16" color="teal">mdi-cash-multiple</v-icon>
                        <span>{{ $t('payments') }}</span>
                      </div>
                      <v-chip size="x-small" color="teal" variant="tonal" class="font-weight-bold">
                        {{ paymentsByMonth[m.num].length }}
                      </v-chip>
                    </div>
                    <div class="ss-sub-table-wrap">
                      <table class="ss-table" v-if="paymentsByMonth[m.num].length">
                        <thead>
                          <tr>
                            <th class="ss-th text-center" style="width:90px">{{ $t('date') }}</th>
                            <th class="ss-th text-left">{{ $t('suppliers') }}</th>
                            <th class="ss-th text-center" style="width:100px">{{ $t('payment_type') }}</th>
                            <th class="ss-th text-left">{{ $t('payment_reference') }}</th>
                            <th class="ss-th text-right" style="width:110px">{{ $t('payment_amount') }}</th>
                            <th class="ss-th text-center" style="width:100px">{{ $t('actions') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="p in paymentsByMonth[m.num]" :key="p.id" class="ss-row">
                            <td class="ss-td text-center text-caption font-monospace">{{ fmtDate(p.date) }}</td>
                            <td class="ss-td text-left font-weight-medium text-truncate" style="max-width:140px">{{ p._supplierName }}</td>
                            <td class="ss-td text-center">
                              <v-chip
                                size="x-small"
                                :color="p.payment_type==='cash'?'success':p.payment_type==='check'?'blue':p.payment_type==='bank_transfer'?'deep-purple':'orange'"
                                variant="tonal"
                              >
                                {{ paymentTypeLabel(p.payment_type) }}
                              </v-chip>
                            </td>
                            <td class="ss-td text-left text-truncate font-monospace" style="max-width:110px">{{ p.reference || '-' }}</td>
                            <td class="ss-td text-right font-weight-bold text-teal font-monospace tabular-nums">
                              {{ main.formatDisplayNumber(p.amount) }}
                            </td>
                            <td class="ss-td text-center">
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
                          <tr class="ss-foot">
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

        <!-- Year Summary Section (Loops over filteredYearSummary to fix filter bug) -->
        <div class="mt-6 mb-2">
          <div class="ss-section-title">
            <v-icon size="18" color="#E65100" class="mr-2">mdi-sigma</v-icon>
            {{ $t('year_summary') }}
          </div>
        </div>
        <div class="ss-table-wrap">
          <table class="ss-table">
            <thead>
              <tr>
                <th class="ss-th text-left">{{ $t('suppliers') }}</th>
                <th class="ss-th text-right" style="width:160px">{{ $t('carry_forward') }}</th>
                <th class="ss-th text-right" style="width:170px">{{ $t('supplier_invoices') }}</th>
                <th class="ss-th text-right" style="width:170px">{{ $t('payments') }}</th>
                <th class="ss-th text-right" style="width:180px">{{ $t('outstanding_balance') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in filteredYearSummary" :key="s.supplier_id" class="ss-row">
                <td class="ss-td text-left font-weight-bold">{{ s.supplier_name }}</td>
                <td class="ss-td text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(s.carry_forward) }}</td>
                <td class="ss-td text-right text-deep-orange font-weight-medium font-monospace tabular-nums">{{ main.formatDisplayNumber(s.total_invoices) }}</td>
                <td class="ss-td text-right text-teal font-weight-medium font-monospace tabular-nums">{{ main.formatDisplayNumber(s.total_payments) }}</td>
                <td class="ss-td text-right font-weight-bold font-monospace tabular-nums" :class="{'text-success': s.balance <= 0, 'text-deep-orange': s.balance > 0}">
                  {{ main.formatDisplayNumber(s.balance) }} {{ main.currency }}
                </td>
              </tr>
              <tr class="ss-foot">
                <td class="font-weight-bold text-left">{{ $t('total') }}</td>
                <td class="font-weight-bold text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(totalCarryForward) }}</td>
                <td class="font-weight-bold text-deep-orange text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(totalInvoices) }}</td>
                <td class="font-weight-bold text-teal text-right font-monospace tabular-nums">{{ main.formatDisplayNumber(totalPayments) }}</td>
                <td class="font-weight-bold text-right font-monospace tabular-nums" :class="{'text-success': totalBalance <= 0, 'text-deep-orange': totalBalance > 0}">
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
            color="#E65100"
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
    <div v-if="activeTab==='analytics'" class="ss-analytics-container">
      <!-- Top Control Toolbar -->
      <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-4 ss-analytics-toolbar">
        <div class="d-flex flex-wrap align-center ga-2 flex-grow-1" style="max-width: 480px;">
          <!-- Synergized supplier filter -->
          <v-autocomplete
            v-model="selectedSupplierIds"
            :items="supplierItems"
            :label="$t('suppliers')"
            variant="outlined"
            density="compact"
            rounded="lg"
            color="#E65100"
            hide-details
            multiple
            chips
            closable-chips
            small-chips
            class="flex-grow-1 ss-filter-input"
            :placeholder="$t('all')"
          >
            <template #chip="{ item }">
              <v-chip color="deep-orange" variant="tonal" size="small">{{ item.title }}</v-chip>
            </template>
          </v-autocomplete>
        </div>

        <div class="d-flex flex-wrap align-center ga-2">
          <!-- Horizon Period Filter -->
          <SegmentedPillToggle
            v-model="analyticsPeriod"
            :items="[
              { value: 'year', label: $t('full_year') },
              { value: 'q1', label: 'Q1' },
              { value: 'q2', label: 'Q2' },
              { value: 'q3', label: 'Q3' },
              { value: 'q4', label: 'Q4' },
              { value: 'active', label: $t('preset_active_months') }
            ]"
            color="#E65100"
          />

          <!-- Chart Visualization Mode -->
          <SegmentedPillToggle
            v-model="analyticsChartType"
            :items="[
              { value: 'flow', label: $t('chart_flow_mode'), icon: 'mdi-chart-line' },
              { value: 'cumulative', label: $t('chart_cumulative_mode'), icon: 'mdi-chart-areaspline' },
              { value: 'rate', label: $t('chart_rate_mode'), icon: 'mdi-chart-box' }
            ]"
            color="#E65100"
          />
        </div>
      </div>

      <!-- 4 Executive KPI Insight Cards -->
      <v-row dense class="mb-4">
        <!-- 1. Total Invoiced -->
        <v-col cols="12" sm="6" lg="3">
          <v-card class="ss-kpi-card pa-4" rounded="xl" elevation="0">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase">{{ $t('supplier_invoices') }}</span>
              <v-avatar size="36" color="orange-lighten-5">
                <v-icon size="20" color="#E65100">mdi-file-document-multiple-outline</v-icon>
              </v-avatar>
            </div>
            <div class="ss-analytics-kpi-val text-deep-orange-darken-3">
              {{ main.formatDisplayNumber(analyticsKPIs.invoiced) }} <span class="text-caption font-weight-bold text-medium-emphasis">{{ main.currency }}</span>
            </div>
            <div class="d-flex align-center justify-space-between mt-2 pt-2 border-t text-caption text-medium-emphasis">
              <span>{{ analyticsKPIs.invoiceCount }} {{ $t('supplier_invoices') }}</span>
              <span v-if="analyticsKPIs.totalWeight > 0" class="font-weight-medium">{{ main.formatDisplayNumber(analyticsKPIs.totalWeight) }} KG</span>
              <span v-else class="font-weight-medium">{{ $t('average_invoice') }}: {{ main.formatDisplayNumber(analyticsKPIs.avgInvoice) }}</span>
            </div>
          </v-card>
        </v-col>

        <!-- 2. Total Settled / Paid -->
        <v-col cols="12" sm="6" lg="3">
          <v-card class="ss-kpi-card pa-4" rounded="xl" elevation="0">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase">{{ $t('payments') }}</span>
              <v-avatar size="36" color="teal-lighten-5">
                <v-icon size="20" color="#00897B">mdi-cash-check</v-icon>
              </v-avatar>
            </div>
            <div class="ss-analytics-kpi-val text-teal-darken-2">
              {{ main.formatDisplayNumber(analyticsKPIs.payments) }} <span class="text-caption font-weight-bold text-medium-emphasis">{{ main.currency }}</span>
            </div>
            <div class="d-flex align-center justify-space-between mt-2 pt-2 border-t text-caption text-medium-emphasis">
              <span>{{ analyticsKPIs.paymentCount }} {{ $t('payments') }}</span>
              <span class="font-weight-medium">{{ $t('average_payment') }}: {{ main.formatDisplayNumber(analyticsKPIs.avgPayment) }}</span>
            </div>
          </v-card>
        </v-col>

        <!-- 3. Settlement Rate -->
        <v-col cols="12" sm="6" lg="3">
          <v-card class="ss-kpi-card pa-4" rounded="xl" elevation="0">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase">{{ $t('settlement_rate') }}</span>
              <v-chip size="x-small" :color="analyticsKPIs.settlementRate >= 90 ? 'success' : (analyticsKPIs.settlementRate >= 50 ? 'warning' : 'error')" variant="flat" class="font-weight-bold">
                {{ analyticsKPIs.settlementRate >= 90 ? $t('healthy_status') : (analyticsKPIs.settlementRate >= 50 ? $t('pending_settlements') : $t('action_required')) }}
              </v-chip>
            </div>
            <div class="ss-analytics-kpi-val" :class="analyticsKPIs.settlementRate >= 90 ? 'text-success' : (analyticsKPIs.settlementRate >= 50 ? 'text-warning' : 'text-error')">
              {{ analyticsKPIs.settlementRate }}%
            </div>
            <div class="mt-2">
              <v-progress-linear :model-value="analyticsKPIs.settlementRate" :color="analyticsKPIs.settlementRate >= 90 ? 'success' : (analyticsKPIs.settlementRate >= 50 ? '#E65100' : 'error')" height="6" rounded></v-progress-linear>
            </div>
          </v-card>
        </v-col>

        <!-- 4. Net Outstanding Balance -->
        <v-col cols="12" sm="6" lg="3">
          <v-card class="ss-kpi-card pa-4" rounded="xl" elevation="0">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase">{{ $t('outstanding_balance') }}</span>
              <v-avatar size="36" :color="totalBalance > 0 ? 'red-lighten-5' : 'green-lighten-5'">
                <v-icon size="20" :color="totalBalance > 0 ? '#C62828' : '#2E7D32'">mdi-scale-balance</v-icon>
              </v-avatar>
            </div>
            <div class="ss-analytics-kpi-val" :class="totalBalance > 0 ? 'text-error' : 'text-success'">
              {{ main.formatDisplayNumber(totalBalance) }} <span class="text-caption font-weight-bold text-medium-emphasis">{{ main.currency }}</span>
            </div>
            <div class="d-flex align-center justify-space-between mt-2 pt-2 border-t text-caption text-medium-emphasis">
              <span>{{ $t('carry_forward') }}: {{ main.formatDisplayNumber(totalCarryForward) }}</span>
              <span class="font-weight-medium">{{ $t('net_annual_movement') }}: {{ main.formatDisplayNumber(analyticsKPIs.netBalance) }}</span>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Primary Visualization Card -->
      <div class="ss-panel-card mb-4">
        <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
          <div class="ss-section-title">
            <v-icon size="20" color="#E65100" class="mr-2">
              {{ analyticsChartType === 'cumulative' ? 'mdi-chart-areaspline' : (analyticsChartType === 'rate' ? 'mdi-chart-box' : 'mdi-chart-bar') }}
            </v-icon>
            {{ analyticsChartType === 'cumulative' ? $t('chart_cumulative_mode') : (analyticsChartType === 'rate' ? $t('chart_rate_mode') : $t('invoices_vs_payments')) }}
          </div>
          <span class="text-caption text-medium-emphasis font-weight-medium">
            {{ currentYear }} • {{ analyticsPeriod === 'year' ? $t('full_year') : analyticsPeriod.toUpperCase() }}
          </span>
        </div>
        <apexchart
          :key="analyticsChartType + analyticsPeriod + main.is_dark"
          :type="analyticsChartType === 'cumulative' ? 'area' : (analyticsChartType === 'rate' ? 'bar' : 'line')"
          :height="330"
          :options="primaryChartOptions"
          :series="primaryChartSeries"
        ></apexchart>
      </div>

      <!-- Secondary Visualizations Grid -->
      <v-row dense class="mb-4">
        <!-- Left: Top 5 Suppliers Leaderboard -->
        <v-col cols="12" md="6">
          <div class="ss-panel-card h-100">
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="ss-section-title">
                <v-icon size="20" color="#E65100" class="mr-2">mdi-trophy-outline</v-icon>
                {{ $t('top_suppliers') }}
              </div>
              <span class="text-caption text-medium-emphasis">{{ $t('share_of_total') }}</span>
            </div>

            <div v-if="topSuppliersList.length" class="d-flex flex-column ga-3">
              <div
                v-for="sup in topSuppliersList"
                :key="sup.id"
                class="pa-3 rounded-lg border bg-surface"
              >
                <div class="d-flex align-center justify-space-between mb-1">
                  <div class="d-flex align-center ga-2">
                    <v-avatar size="24" :color="sup.rank === 1 ? 'amber-darken-2' : (sup.rank === 2 ? 'blue-grey-darken-1' : (sup.rank === 3 ? 'brown-darken-1' : 'grey-lighten-2'))" class="text-white text-caption font-weight-bold">
                      {{ sup.rank }}
                    </v-avatar>
                    <span class="text-body-2 font-weight-bold">{{ sup.name }}</span>
                  </div>
                  <div class="text-right">
                    <span class="text-body-2 font-weight-bold text-deep-orange-darken-3">{{ main.formatDisplayNumber(sup.totalInvoiced) }} {{ main.currency }}</span>
                    <span class="text-caption text-medium-emphasis ml-1 font-weight-medium">({{ sup.share }}%)</span>
                  </div>
                </div>
                <v-progress-linear :model-value="sup.share" color="#E65100" height="5" rounded class="my-1"></v-progress-linear>
                <div class="d-flex align-center justify-space-between text-caption text-medium-emphasis mt-1">
                  <span>{{ $t('payments') }}: {{ main.formatDisplayNumber(sup.totalPayments) }} {{ main.currency }} ({{ sup.rate }}%)</span>
                  <span :class="sup.balance > 0 ? 'text-error font-weight-bold' : 'text-success font-weight-bold'">
                    {{ $t('outstanding_balance') }}: {{ main.formatDisplayNumber(sup.balance) }} {{ main.currency }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-medium-emphasis text-caption">
              {{ $t('no_data_period') }}
            </div>
          </div>
        </v-col>

        <!-- Right: Payment Methods Breakdown -->
        <v-col cols="12" md="6">
          <div class="ss-panel-card h-100">
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="ss-section-title">
                <v-icon size="20" color="#00897B" class="mr-2">mdi-chart-donut</v-icon>
                {{ $t('payment_methods_distribution') }}
              </div>
              <span class="text-caption text-medium-emphasis">{{ main.formatDisplayNumber(paymentMethodsDistribution.totalAmount) }} {{ main.currency }}</span>
            </div>

            <div v-if="paymentMethodsDistribution.hasData">
              <apexchart
                :key="'donut-' + analyticsPeriod + main.is_dark"
                type="donut"
                :height="240"
                :options="donutChartOptions"
                :series="paymentMethodsDistribution.series"
              ></apexchart>
              <div class="mt-3 d-flex flex-column ga-2">
                <div
                  v-for="method in paymentMethodsDistribution.details"
                  :key="method.type"
                  class="d-flex align-center justify-space-between text-caption px-2 py-1 rounded border bg-surface"
                >
                  <div class="d-flex align-center ga-2">
                    <span class="font-weight-medium">{{ method.title }}</span>
                    <v-chip size="x-small" variant="tonal" color="blue-grey">{{ method.count }}</v-chip>
                  </div>
                  <div class="d-flex align-center ga-2">
                    <span class="font-weight-bold">{{ main.formatDisplayNumber(method.amount) }} {{ main.currency }}</span>
                    <span class="text-medium-emphasis">({{ method.percent }}%)</span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-medium-emphasis text-caption">
              {{ $t('no_data_period') }}
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Monthly Performance Ledger Table -->
      <div class="ss-panel-card">
        <div class="d-flex align-center justify-space-between mb-3">
          <div class="ss-section-title">
            <v-icon size="20" color="#E65100" class="mr-2">mdi-table-large</v-icon>
            {{ $t('monthly_performance') }}
          </div>
          <span class="text-caption text-medium-emphasis font-weight-medium">{{ currentYear }}</span>
        </div>

        <div class="table-responsive">
          <table class="ss-ledger-table">
            <thead>
              <tr>
                <th class="text-left">{{ $t('month') }}</th>
                <th class="text-right">{{ $t('supplier_invoices') }}</th>
                <th class="text-right">{{ $t('payments') }}</th>
                <th class="text-right">{{ $t('net_movement') }}</th>
                <th class="text-right">{{ $t('outstanding_balance') }}</th>
                <th class="text-center">{{ $t('settlement_rate') }}</th>
                <th class="text-center" style="width: 80px">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in monthlyLedgerData" :key="'ledger-' + row.monthNum">
                <td class="font-weight-bold text-left">
                  <div class="d-flex align-center ga-2">
                    <v-badge dot :color="row.invoicesTotal > 0 || row.paymentsTotal > 0 ? '#E65100' : 'grey'"></v-badge>
                    <span>{{ row.monthLabel }}</span>
                  </div>
                </td>
                <td class="text-right text-deep-orange-darken-3 font-weight-bold">
                  {{ main.formatDisplayNumber(row.invoicesTotal) }}
                  <span class="text-caption text-medium-emphasis font-weight-regular" v-if="row.invoicesCount">({{ row.invoicesCount }})</span>
                </td>
                <td class="text-right text-teal-darken-2 font-weight-bold">
                  {{ main.formatDisplayNumber(row.paymentsTotal) }}
                  <span class="text-caption text-medium-emphasis font-weight-regular" v-if="row.paymentsCount">({{ row.paymentsCount }})</span>
                </td>
                <td class="text-right font-weight-bold" :class="row.net > 0 ? 'text-error' : (row.net < 0 ? 'text-success' : '')">
                  {{ main.formatDisplayNumber(row.net) }}
                </td>
                <td class="text-right font-weight-bold" :class="row.cumulative > 0 ? 'text-error' : 'text-success'">
                  {{ main.formatDisplayNumber(row.cumulative) }} {{ main.currency }}
                </td>
                <td class="text-center">
                  <v-chip size="x-small" :color="row.settlementRate >= 90 ? 'success' : (row.settlementRate >= 50 ? 'warning' : (row.invoicesTotal > 0 ? 'error' : 'grey'))" variant="tonal" class="font-weight-bold">
                    {{ row.settlementRate }}%
                  </v-chip>
                </td>
                <td class="text-center">
                  <v-btn
                    icon
                    size="x-small"
                    variant="tonal"
                    color="#E65100"
                    @click="jumpToMonthInGrid(row.monthNum)"
                    :title="$t('jump_to_month')"
                  >
                    <v-icon size="14">mdi-arrow-right-circle-outline</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </v-card-text>

  <!-- Add/Edit Payment Dialog (AddCharge Style) -->
  <v-dialog v-model="showPayForm" max-width="620" persistent>
    <v-card class="rounded-xl shadow-lg ss-dialog-card">
      <v-card-title class="pa-4 d-flex align-center ss-modal-title">
        <div class="ss-modal-icon-wrap mr-3">
          <v-icon color="#E65100" size="22">mdi-cash-plus</v-icon>
        </div>
        <span class="font-weight-bold text-subtitle-1">{{ editId ? $t('edit_payment') : $t('add_payment') }}</span>
        <v-spacer/>
        <v-btn icon variant="text" size="small" @click="showPayForm=false;resetPayForm()"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-divider/>
      <v-card-text class="pa-4 pa-md-5">
        <!-- Date Picker with Quick Presets -->
        <div class="ss-datepicker-box mb-4">
          <div class="d-flex align-center justify-space-between mb-2 px-1">
            <span class="text-caption font-weight-bold text-medium-emphasis">{{ $t('date') }}</span>
            <div class="d-flex ga-1">
              <v-btn size="x-small" variant="tonal" color="#E65100" rounded="lg" @click="setPayDateToday">{{ $t('today') }}</v-btn>
              <v-btn size="x-small" variant="tonal" color="blue-grey" rounded="lg" @click="setPayDateYesterday">{{ $t('yesterday') }}</v-btn>
            </div>
          </div>
          <div class="d-flex justify-center">
            <DatePicker v-model="payDateModel" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="orange" :min-date="minPayDate" :max-date="maxPayDate" expanded />
          </div>
        </div>

        <!-- AddCharge Form Fields -->
        <v-row dense>
          <v-col cols="12" sm="6">
            <v-autocomplete v-model="payForm.payment_type" :items="paymentTypes" :label="$t('payment_type')" variant="outlined" density="compact" rounded="lg" color="#E65100" hide-details></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6">
            <v-autocomplete v-model="payForm.supplier_id" :items="supplierItems" :label="$t('suppliers')" variant="outlined" density="compact" rounded="lg" color="#E65100" hide-details @update:model-value="onSupplierChange"></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6" v-if="supplierInvoices.length">
            <v-autocomplete v-model="payForm.supplier_invoice_id" :items="supplierInvoices.map(i => ({ title: `${i.tracking_numbers?.join(', ') || i.id} (${main.formatDisplayNumber(i.total_price)} ${main.currency})`, value: i.id }))" :label="$t('supplier_invoices')" variant="outlined" density="compact" rounded="lg" color="#E65100" hide-details clearable :placeholder="$t('all')"></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field v-model.number="payForm.amount" :label="$t('payment_amount')" type="number" variant="outlined" density="compact" rounded="lg" color="#E65100" :rules="[v => v > 0 || $t('amount_must_be_positive')]" min="0.01" step="0.01" :suffix="main.currency"></v-text-field>
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
              color="#E65100"
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
            <v-text-field v-model="payForm.reference" :label="$t('payment_reference')" variant="outlined" density="compact" rounded="lg" color="#E65100" hide-details></v-text-field>
          </v-col>
          <v-col cols="12">
            <v-text-field v-model="payForm.notes" :label="$t('notes')" variant="outlined" density="compact" rounded="lg" color="#E65100" hide-details></v-text-field>
          </v-col>
        </v-row>
      </v-card-text>
      <v-divider/>
      <v-card-actions class="pa-4">
        <v-spacer/>
        <v-btn variant="text" rounded="lg" @click="showPayForm=false;resetPayForm()">{{ $t('cancel') }}</v-btn>
        <v-btn color="#E65100" variant="flat" rounded="lg" prepend-icon="mdi-content-save" :loading="saving" @click="savePayment" :disabled="!payForm.supplier_id||!payForm.amount||payForm.amount<=0||!payForm.date" class="font-weight-bold">
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
        <v-icon color="#E65100" class="mr-2">mdi-lock</v-icon>
        {{ $t('fiscal_year_closing') }}
      </v-card-title>
      <v-card-text class="pa-5 pt-2">
        {{ $t('confirm_close_year', {year:currentYear}) }}
      </v-card-text>
      <v-card-actions class="pa-5 pt-0">
        <v-spacer/>
        <v-btn @click="closeYearDialog=false" variant="text" rounded="lg">{{ $t('cancel') }}</v-btn>
        <v-btn color="#E65100" variant="flat" rounded="lg" @click="doCloseYear" :loading="closingYear" class="font-weight-bold">{{ $t('close_year') }}</v-btn>
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
    <v-progress-linear :model-value="snackbarProgress" color="#E65100" height="3" class="mb-2" rounded></v-progress-linear>
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
  <div v-if="printReady" class="d-flex align-center ga-2 pa-3" style="position:fixed;bottom:20px;right:20px;z-index:999;background:rgba(230,81,0,.95);border-radius:16px;box-shadow:0 8px 32px rgba(230,81,0,.4)">
    <v-icon color="white" size="20">mdi-file-document-outline</v-icon>
    <span class="text-white text-body-2 font-weight-bold mr-2">{{ $t('settlement_bulletin') }} — {{ currentYear }}</span>
    <v-btn v-print="printCfg" color="white" variant="flat" size="small" rounded="lg" prepend-icon="mdi-printer" style="color:#E65100">{{ $t('print') }}</v-btn>
    <v-btn icon size="x-small" variant="text" color="white" @click="printReady = false"><v-icon size="18">mdi-close</v-icon></v-btn>
  </div>
  </v-card>

  <!-- Print Customizer Dialog -->
  <v-dialog v-model="printOptionsDialog" max-width="920" scrollable>
    <v-card class="rounded-xl shadow-lg ss-dialog-card">
      <v-card-title class="pa-4 d-flex align-center ss-modal-title" style="background: linear-gradient(135deg, #E65100 0%, #F57C00 100%); color: white;">
        <div class="ss-modal-icon-wrap mr-3" style="background: rgba(255,255,255,.2)">
          <v-icon color="white" size="22">mdi-printer-cog</v-icon>
        </div>
        <div class="flex-grow-1">
          <div class="font-weight-bold text-subtitle-1 text-white">{{ $t('print_customizer') }}</div>
          <div class="text-caption text-white" style="opacity:.85">{{ $t('choose_data_to_print') }}</div>
        </div>
        <v-chip color="white" variant="outlined" size="small" class="font-weight-bold mr-2">
          {{ currentYear }}
        </v-chip>
        <v-btn icon variant="text" size="small" color="white" @click="printOptionsDialog = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text class="pa-4 pa-md-5">
        <!-- Quick Presets -->
        <div class="mb-4">
          <div class="text-caption font-weight-bold text-medium-emphasis mb-2 text-uppercase">{{ $t('presets') || 'Presets' }}</div>
          <div class="d-flex flex-wrap ga-2">
            <v-btn
              size="small"
              rounded="lg"
              :variant="printOptions.preset === 'active' ? 'flat' : 'tonal'"
              color="#E65100"
              prepend-icon="mdi-calendar-check"
              class="font-weight-bold"
              @click="applyPrintPreset('active')"
            >
              {{ $t('preset_active_months') }}
            </v-btn>
            <v-btn
              size="small"
              rounded="lg"
              :variant="printOptions.preset === 'full' ? 'flat' : 'tonal'"
              color="#E65100"
              prepend-icon="mdi-file-document-multiple"
              class="font-weight-bold"
              @click="applyPrintPreset('full')"
            >
              {{ $t('preset_full_report') }}
            </v-btn>
            <v-btn
              size="small"
              rounded="lg"
              :variant="printOptions.preset === 'summary' ? 'flat' : 'tonal'"
              color="#E65100"
              prepend-icon="mdi-table"
              class="font-weight-bold"
              @click="applyPrintPreset('summary')"
            >
              {{ $t('preset_summary_only') }}
            </v-btn>
            <v-btn
              size="small"
              rounded="lg"
              :variant="printOptions.preset === 'current' ? 'flat' : 'tonal'"
              color="#E65100"
              prepend-icon="mdi-calendar-today"
              class="font-weight-bold"
              @click="applyPrintPreset('current')"
            >
              {{ $t('preset_current_month') }}
            </v-btn>
          </div>
        </div>

        <v-row dense>
          <!-- Left Column: Scope & Filters -->
          <v-col cols="12" md="6">
            <div class="pa-4 rounded-xl border bg-surface h-100">
              <div class="d-flex align-center ga-2 mb-3">
                <v-icon size="18" color="#E65100">mdi-filter-variant</v-icon>
                <span class="text-subtitle-2 font-weight-bold text-uppercase">{{ $t('document_scope') }}</span>
              </div>

              <!-- Partner Filter -->
              <div class="mb-3">
                <v-autocomplete
                  v-model="selectedSupplierIds"
                  :items="supplierItems"
                  :label="$t('suppliers')"
                  variant="outlined"
                  density="compact"
                  rounded="lg"
                  color="#E65100"
                  hide-details
                  multiple
                  chips
                  closable-chips
                  small-chips
                  :placeholder="$t('all')"
                >
                  <template #chip="{ item }">
                    <v-chip color="deep-orange" variant="tonal" size="small">{{ item.title }}</v-chip>
                  </template>
                </v-autocomplete>
              </div>

              <!-- Month Selection Mode -->
              <div class="mb-3">
                <label class="text-caption font-weight-bold text-medium-emphasis mb-1 d-block">{{ $t('months_to_include') }}</label>
                <v-btn-toggle
                  v-model="printOptions.monthMode"
                  mandatory
                  density="compact"
                  rounded="lg"
                  color="#E65100"
                  variant="outlined"
                  divided
                  class="w-100"
                >
                  <v-btn value="active" size="small" class="flex-grow-1 font-weight-bold">{{ $t('active_months_only') }}</v-btn>
                  <v-btn value="all" size="small" class="flex-grow-1 font-weight-bold">{{ $t('all_months') }}</v-btn>
                  <v-btn value="custom" size="small" class="flex-grow-1 font-weight-bold">{{ $t('custom_months') }}</v-btn>
                </v-btn-toggle>
              </div>

              <!-- Interactive Month Chips (if custom) -->
              <div v-if="printOptions.monthMode === 'custom'" class="mb-3 pa-2 rounded-lg border bg-surface-light">
                <div class="d-flex flex-wrap ga-1">
                  <v-chip
                    v-for="m in months"
                    :key="'pm-' + m.num"
                    size="small"
                    :color="isPrintMonthSelected(m.num) ? '#E65100' : 'grey'"
                    :variant="isPrintMonthSelected(m.num) ? 'flat' : 'outlined'"
                    class="font-weight-bold cursor-pointer"
                    @click="togglePrintMonth(m.num)"
                  >
                    {{ m.short }}
                  </v-chip>
                </div>
              </div>

              <!-- Custom Note / Observations -->
              <div class="mt-3">
                <v-textarea
                  v-model="printOptions.notes"
                  :label="$t('print_notes')"
                  :placeholder="$t('print_notes_placeholder')"
                  variant="outlined"
                  density="compact"
                  rounded="lg"
                  color="#E65100"
                  rows="2"
                  auto-grow
                  hide-details
                ></v-textarea>
              </div>
            </div>
          </v-col>

          <!-- Right Column: Sections to Include -->
          <v-col cols="12" md="6">
            <div class="pa-4 rounded-xl border bg-surface h-100">
              <div class="d-flex align-center ga-2 mb-3">
                <v-icon size="18" color="#E65100">mdi-checkbox-multiple-marked-outline</v-icon>
                <span class="text-subtitle-2 font-weight-bold text-uppercase">{{ $t('sections_to_include') }}</span>
              </div>

              <div class="d-flex flex-column ga-2">
                <div class="d-flex align-center justify-space-between pa-2 rounded-lg border">
                  <div>
                    <div class="text-body-2 font-weight-bold">{{ $t('include_header') }}</div>
                    <div class="text-caption text-medium-emphasis">Logo, company identity & document number</div>
                  </div>
                  <v-switch v-model="printOptions.includeHeader" color="#E65100" density="compact" hide-details></v-switch>
                </div>

                <div class="d-flex align-center justify-space-between pa-2 rounded-lg border">
                  <div>
                    <div class="text-body-2 font-weight-bold">{{ $t('include_kpis') }}</div>
                    <div class="text-caption text-medium-emphasis">Carry forward, invoiced, payments & balance</div>
                  </div>
                  <v-switch v-model="printOptions.includeKpis" color="#E65100" density="compact" hide-details></v-switch>
                </div>

                <div class="d-flex align-center justify-space-between pa-2 rounded-lg border">
                  <div>
                    <div class="text-body-2 font-weight-bold">{{ $t('include_invoices') }}</div>
                    <div class="text-caption text-medium-emphasis">Itemized invoice list per month</div>
                  </div>
                  <v-switch v-model="printOptions.includeInvoices" color="#E65100" density="compact" hide-details></v-switch>
                </div>

                <div class="d-flex align-center justify-space-between pa-2 rounded-lg border">
                  <div>
                    <div class="text-body-2 font-weight-bold">{{ $t('include_payments') }}</div>
                    <div class="text-caption text-medium-emphasis">Itemized payment receipts per month</div>
                  </div>
                  <v-switch v-model="printOptions.includePayments" color="#E65100" density="compact" hide-details></v-switch>
                </div>

                <div class="d-flex align-center justify-space-between pa-2 rounded-lg border">
                  <div>
                    <div class="text-body-2 font-weight-bold">{{ $t('include_year_summary') }}</div>
                    <div class="text-caption text-medium-emphasis">Matrix summary table by supplier</div>
                  </div>
                  <v-switch v-model="printOptions.includeYearSummary" color="#E65100" density="compact" hide-details></v-switch>
                </div>

                <div class="d-flex align-center justify-space-between pa-2 rounded-lg border">
                  <div>
                    <div class="text-body-2 font-weight-bold">{{ $t('include_analytics') }}</div>
                    <div class="text-caption text-medium-emphasis">Invoice count, weights & payment methods</div>
                  </div>
                  <v-switch v-model="printOptions.includeAnalytics" color="#E65100" density="compact" hide-details></v-switch>
                </div>

                <div class="d-flex align-center justify-space-between pa-2 rounded-lg border">
                  <div>
                    <div class="text-body-2 font-weight-bold">{{ $t('include_signatures') }}</div>
                    <div class="text-caption text-medium-emphasis">Official management and partner stamps</div>
                  </div>
                  <v-switch v-model="printOptions.includeSignatures" color="#E65100" density="compact" hide-details></v-switch>
                </div>

                <div class="d-flex align-center justify-space-between pa-2 rounded-lg border">
                  <div>
                    <div class="text-body-2 font-weight-bold">{{ $t('compact_print_mode') }}</div>
                    <div class="text-caption text-medium-emphasis">Dense rows for paper-saving</div>
                  </div>
                  <v-switch v-model="printOptions.compactMode" color="#E65100" density="compact" hide-details></v-switch>
                </div>
              </div>
            </div>
          </v-col>
        </v-row>

        <!-- Status Bar -->
        <div class="mt-4 pa-3 rounded-lg border d-flex align-center justify-space-between flex-wrap ga-2" style="background: rgba(230,81,0,.06); border-color: rgba(230,81,0,.2) !important;">
          <div class="d-flex align-center ga-2">
            <v-icon color="#E65100" size="18">mdi-information-outline</v-icon>
            <span class="text-caption font-weight-bold" style="color: #E65100">
              {{ $t('ready_to_print') }}: {{ printableMonths.length }} {{ $t('months') || 'months' }} • {{ printableInvoicesCount }} {{ $t('supplier_invoices') }} • {{ printablePaymentsCount }} {{ $t('payments') }}
            </span>
          </div>
          <div class="text-caption font-weight-bold">
            {{ $t('outstanding_balance') }}: {{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}
          </div>
        </div>
      </v-card-text>

      <v-divider/>
      <v-card-actions class="pa-4 d-flex align-center justify-space-between">
        <v-btn variant="text" rounded="lg" @click="printOptionsDialog = false">{{ $t('cancel') }}</v-btn>
        <v-btn
          v-print="printCfg"
          color="#E65100"
          variant="flat"
          rounded="lg"
          size="default"
          prepend-icon="mdi-printer"
          class="font-weight-bold px-6"
        >
          {{ $t('print_now') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Hidden Print Area -->
  <div style="display:none"><div id="ss-print-area">
    <div class="ss-print-document" :class="{ 'ss-print-compact': printOptions.compactMode }">
      <!-- Header with Logo & Company (if includeHeader) -->
      <div v-if="printOptions.includeHeader" class="ss-print-header">
        <div class="ss-print-header-top">
          <div class="ss-print-logo-box"><img :src="logoUrl" alt="Logo" style="height:52px;object-fit:contain" /></div>
          <div class="ss-print-company-info">
            <h2 style="font-size:17px;font-weight:800;color:#1e293b;margin:0 0 2px">{{ companySettings.companyName }}</h2>
            <p style="font-size:10px;color:#64748b;margin:0">{{ companySettings.companyAddress }}</p>
          </div>
          <div class="ss-print-doc-badge">
            <div style="font-size:8px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#E65100">{{ $t('official_document') }}</div>
            <div style="font-size:14px;font-weight:900;color:#E65100">{{ $t('settlement_bulletin') }}</div>
            <div style="font-size:10px;font-weight:700;color:#334155">{{ currentYear }}</div>
          </div>
        </div>
        <div class="ss-print-meta-bar">
          <div><b>{{ $t('year') }}:</b> {{ currentYear }}</div>
          <div><b>{{ $t('date') }}:</b> {{ new Date().toLocaleDateString() }}</div>
          <div v-if="printSupplierLabel"><b>{{ $t('suppliers') }}:</b> {{ printSupplierLabel }}</div>
          <div style="margin-left:auto"><b>REF:</b> STL-SUP-{{ currentYear }}-{{ String(new Date().getMonth()+1).padStart(2,'0') }}</div>
        </div>
      </div>

      <!-- Custom Notes Callout (if entered) -->
      <div v-if="printOptions.notes" class="ss-print-note-callout">
        <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#E65100;margin-bottom:2px">{{ $t('print_notes') }}</div>
        <div style="font-size:11px;color:#334155;white-space:pre-wrap">{{ printOptions.notes }}</div>
      </div>

      <!-- Summary Stats Strip (if includeKpis) -->
      <div v-if="printOptions.includeKpis" class="ss-print-kpis-grid">
        <div class="ss-print-kpi-box" style="border-top:3px solid #E65100">
          <div class="ss-print-kpi-lbl">{{ $t('carry_forward') }}</div>
          <div class="ss-print-kpi-num" style="color:#E65100">{{ main.formatDisplayNumber(totalCarryForward) }} <span style="font-size:9px">{{ main.currency }}</span></div>
        </div>
        <div class="ss-print-kpi-box" style="border-top:3px solid #BF360C">
          <div class="ss-print-kpi-lbl">{{ $t('supplier_invoices') }}</div>
          <div class="ss-print-kpi-num" style="color:#BF360C">{{ main.formatDisplayNumber(totalInvoices) }} <span style="font-size:9px">{{ main.currency }}</span></div>
        </div>
        <div class="ss-print-kpi-box" style="border-top:3px solid #00695C">
          <div class="ss-print-kpi-lbl">{{ $t('payments') }}</div>
          <div class="ss-print-kpi-num" style="color:#00695C">{{ main.formatDisplayNumber(totalPayments) }} <span style="font-size:9px">{{ main.currency }}</span></div>
        </div>
        <div class="ss-print-kpi-box" :style="totalBalance>0?'border-top:3px solid #C62828':'border-top:3px solid #2E7D32'">
          <div class="ss-print-kpi-lbl">{{ $t('outstanding_balance') }}</div>
          <div class="ss-print-kpi-num" :style="totalBalance>0?'color:#C62828':'color:#2E7D32'">{{ main.formatDisplayNumber(totalBalance) }} <span style="font-size:9px">{{ main.currency }}</span></div>
        </div>
      </div>

      <!-- Month-by-Month Breakdown (if invoices or payments included) -->
      <div v-if="printOptions.includeInvoices || printOptions.includePayments" class="ss-print-months-wrap">
        <div v-for="m in printableMonths" :key="'print-m-'+m.num" class="ss-print-month-block">
          <!-- Month Header -->
          <div class="ss-print-month-hdr">
            <span style="font-weight:800;letter-spacing:.5px">{{ m.label }} {{ currentYear }}</span>
            <div style="font-size:10px;font-weight:600;display:flex;gap:12px">
              <span v-if="printOptions.includeInvoices">{{ $t('supplier_invoices') }}: <b>{{ main.formatDisplayNumber(monthTotals[m.num].invoices) }} {{ main.currency }}</b></span>
              <span v-if="printOptions.includePayments">{{ $t('payments') }}: <b>{{ main.formatDisplayNumber(monthTotals[m.num].payments) }} {{ main.currency }}</b></span>
              <span>{{ $t('outstanding_balance') }}: <b>{{ main.formatDisplayNumber(monthTotals[m.num].balance) }} {{ main.currency }}</b></span>
            </div>
          </div>

          <!-- Tables -->
          <table class="ss-print-side-table">
            <tr>
              <!-- Invoices Side -->
              <td v-if="printOptions.includeInvoices" :style="{ width: printOptions.includePayments ? '50%' : '100%' }" style="vertical-align:top;padding:0;border-right:1px solid #CBD5E1">
                <table class="ss-print-subtable">
                  <thead>
                    <tr style="background:#FFF3E0">
                      <th colspan="4" style="color:#BF360C;font-weight:800;font-size:10px;text-align:left;padding:4px 8px">{{ $t('supplier_invoices') }} ({{ invoicesByMonth[m.num].length }})</th>
                    </tr>
                    <tr style="background:#F8FAFC">
                      <th>{{ $t('date') }}</th>
                      <th>{{ $t('suppliers') }}</th>
                      <th style="text-align:right">{{ $t('weight') }}</th>
                      <th style="text-align:right">{{ $t('price') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="inv in invoicesByMonth[m.num]" :key="'p-inv-'+inv.id">
                      <td>{{ fmtDate(inv.date) }}</td>
                      <td style="font-weight:600">{{ inv._supplierNames }}</td>
                      <td style="text-align:right">{{ main.formatDisplayNumber(inv.total_weight) }}</td>
                      <td style="text-align:right;font-weight:700;color:#BF360C">{{ main.formatDisplayNumber(inv.total_price) }}</td>
                    </tr>
                    <tr v-if="!invoicesByMonth[m.num].length">
                      <td colspan="4" style="text-align:center;color:#94a3b8;padding:6px">—</td>
                    </tr>
                    <tr class="ss-print-tot-row" style="background:#FFF3E0">
                      <td colspan="3" style="text-align:right;font-weight:800">{{ $t('total') }}</td>
                      <td style="text-align:right;font-weight:800;color:#BF360C">{{ main.formatDisplayNumber(monthTotals[m.num].invoices) }}</td>
                    </tr>
                  </tbody>
                </table>
              </td>

              <!-- Payments Side -->
              <td v-if="printOptions.includePayments" :style="{ width: printOptions.includeInvoices ? '50%' : '100%' }" style="vertical-align:top;padding:0">
                <table class="ss-print-subtable">
                  <thead>
                    <tr style="background:#E0F2F1">
                      <th colspan="4" style="color:#00695C;font-weight:800;font-size:10px;text-align:left;padding:4px 8px">{{ $t('payments') }} ({{ paymentsByMonth[m.num].length }})</th>
                    </tr>
                    <tr style="background:#F8FAFC">
                      <th>{{ $t('date') }}</th>
                      <th>{{ $t('suppliers') }}</th>
                      <th>{{ $t('payment_type') }}</th>
                      <th style="text-align:right">{{ $t('payment_amount') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="p in paymentsByMonth[m.num]" :key="'p-pay-'+p.id">
                      <td>{{ fmtDate(p.date) }}</td>
                      <td style="font-weight:600">{{ p._supplierName }}</td>
                      <td>{{ paymentTypeLabel(p.payment_type) }}</td>
                      <td style="text-align:right;font-weight:700;color:#00695C">{{ main.formatDisplayNumber(p.amount) }}</td>
                    </tr>
                    <tr v-if="!paymentsByMonth[m.num].length">
                      <td colspan="4" style="text-align:center;color:#94a3b8;padding:6px">—</td>
                    </tr>
                    <tr class="ss-print-tot-row" style="background:#E0F2F1">
                      <td colspan="3" style="text-align:right;font-weight:800">{{ $t('total') }}</td>
                      <td style="text-align:right;font-weight:800;color:#00695C">{{ main.formatDisplayNumber(monthTotals[m.num].payments) }}</td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Year Summary Matrix Table (if includeYearSummary) -->
      <div v-if="printOptions.includeYearSummary" class="ss-print-section-block">
        <div class="ss-print-block-hdr" style="background:#E65100">
          {{ $t('year_summary') }} — {{ currentYear }}
        </div>
        <table class="ss-print-matrix-table">
          <thead>
            <tr>
              <th style="text-align:left">{{ $t('suppliers') }}</th>
              <th style="text-align:right">{{ $t('carry_forward') }}</th>
              <th style="text-align:right">{{ $t('supplier_invoices') }}</th>
              <th style="text-align:right">{{ $t('payments') }}</th>
              <th style="text-align:right">{{ $t('outstanding_balance') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in filteredYearSummary" :key="'p-ys-'+s.supplier_id">
              <td style="font-weight:700">{{ s.supplier_name }}</td>
              <td style="text-align:right">{{ main.formatDisplayNumber(s.carry_forward) }}</td>
              <td style="text-align:right;color:#BF360C;font-weight:600">{{ main.formatDisplayNumber(s.total_invoices) }}</td>
              <td style="text-align:right;color:#00695C;font-weight:600">{{ main.formatDisplayNumber(s.total_payments) }}</td>
              <td style="text-align:right;font-weight:800" :style="s.balance>0?'color:#C62828':'color:#2E7D32'">
                {{ main.formatDisplayNumber(s.balance) }} {{ main.currency }}
              </td>
            </tr>
            <tr class="ss-print-matrix-tot" style="background:#FFF3E0">
              <td style="font-weight:900">{{ $t('total') }}</td>
              <td style="text-align:right;font-weight:800">{{ main.formatDisplayNumber(totalCarryForward) }}</td>
              <td style="text-align:right;color:#BF360C;font-weight:900">{{ main.formatDisplayNumber(totalInvoices) }}</td>
              <td style="text-align:right;color:#00695C;font-weight:900">{{ main.formatDisplayNumber(totalPayments) }}</td>
              <td style="text-align:right;font-weight:900" :style="totalBalance>0?'color:#C62828':'color:#2E7D32'">
                {{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Financial Analytics & Statistics Strip (if includeAnalytics) -->
      <div v-if="printOptions.includeAnalytics" class="ss-print-stats-strip">
        <div class="ss-print-stat-card" style="border-left:3px solid #E65100;background:#FFF8E1">
          <div class="ss-print-stat-title" style="color:#E65100">{{ $t('settlement_stats_invoices') }}</div>
          <div class="ss-print-stat-item"><span>{{ $t('total_invoices_count') }}:</span> <b>{{ printStats.invoiceCount }}</b></div>
          <div class="ss-print-stat-item"><span>{{ $t('total_weight') }}:</span> <b>{{ main.formatDisplayNumber(printStats.totalWeight) }} KG</b></div>
          <div class="ss-print-stat-item"><span>{{ $t('total') }}:</span> <b style="color:#BF360C">{{ main.formatDisplayNumber(totalInvoices) }} {{ main.currency }}</b></div>
        </div>

        <div class="ss-print-stat-card" style="border-left:3px solid #00695C;background:#E0F7FA">
          <div class="ss-print-stat-title" style="color:#00695C">{{ $t('settlement_stats_payments') }}</div>
          <div class="ss-print-stat-item"><span>{{ $t('total_payments_count') }}:</span> <b>{{ printStats.paymentCount }}</b></div>
          <div class="ss-print-stat-item"><span>{{ $t('settlement_payment_methods') }}:</span> <b>{{ printStats.paymentMethodsSummary }}</b></div>
          <div class="ss-print-stat-item"><span>{{ $t('total') }}:</span> <b style="color:#00695C">{{ main.formatDisplayNumber(totalPayments) }} {{ main.currency }}</b></div>
        </div>

        <div class="ss-print-stat-card" :style="totalBalance>0?'border-left:3px solid #C62828;background:#FFEBEE':'border-left:3px solid #2E7D32;background:#E8F5E9'">
          <div class="ss-print-stat-title" :style="totalBalance>0?'color:#C62828':'color:#2E7D32'">{{ $t('settlement_stats_balance') }}</div>
          <div class="ss-print-stat-item"><span>{{ $t('carry_forward') }}:</span> <b>{{ main.formatDisplayNumber(totalCarryForward) }} {{ main.currency }}</b></div>
          <div class="ss-print-stat-item"><span>{{ $t('net_movement') }}:</span> <b>{{ main.formatDisplayNumber(totalInvoices - totalPayments) }} {{ main.currency }}</b></div>
          <div class="ss-print-stat-item"><span>{{ $t('outstanding_balance') }}:</span> <b :style="totalBalance>0?'color:#C62828':'color:#2E7D32'">{{ main.formatDisplayNumber(totalBalance) }} {{ main.currency }}</b></div>
        </div>
      </div>

      <!-- Signatures & Authorization (if includeSignatures) -->
      <div v-if="printOptions.includeSignatures" class="ss-print-signatures-block">
        <div class="ss-print-sign-box">
          <div class="ss-print-sign-line"></div>
          <div class="ss-print-sign-label">{{ $t('la_direction') }}</div>
        </div>
        <div class="ss-print-sign-box">
          <div class="ss-print-sign-line"></div>
          <div class="ss-print-sign-label">{{ $t('company_signature') }}</div>
        </div>
      </div>

      <!-- Footer -->
      <div class="ss-print-footer">
        <div>Jupiter Financial Suite • {{ $t('printed_on') }}: {{ new Date().toLocaleString() }}</div>
        <div>{{ companySettings.companyName }}</div>
      </div>
    </div>
  </div></div>

<AuditLogDialog v-model="auditLogDialogOpen" auditable-type="SupplierPayment" :auditable-id="auditLogRecordId" module-color="#E65100" />
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
  data(){ return { ...script.data.call(this), printCfg: { id: 'ss-print-area', popTitle: 'Supplier Settlement' }, auditLogDialogOpen: false, auditLogRecordId: 0 }; },
  methods: { ...script.methods, openAuditLog(id) { this.auditLogRecordId = id; this.auditLogDialogOpen = true; } },
};
</script>

<style scoped>
.ss-card {
  border-radius: 20px !important;
  font-family: 'Inter', sans-serif;
  overflow: visible;
}
.ss-hero {
  background: linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  min-height: 104px;
}
.ss-hero-inner {
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
.ss-hero-icon {
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
.ss-hero-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: .2px;
  color: #fff;
  line-height: 1.25;
}
.ss-hero-subtitle {
  font-size: 12.5px;
  color: rgba(255,255,255,.85);
  font-weight: 400;
  margin-top: 2px;
}
.ss-hero-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}
.ss-year-pill {
  display: inline-flex;
  align-items: center;
  background: rgba(255,255,255,.2);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 9999px;
  padding: 2px 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.ss-year-btn {
  color: #fff !important;
}
.ss-year-text {
  font-size: 15px;
  font-weight: 800;
  color: #fff;
  padding: 0 10px;
  min-width: 50px;
  text-align: center;
  letter-spacing: .5px;
}

/* KPI Cards */
.ss-kpi-card {
  border-radius: 16px !important;
  border: 1.5px solid rgba(var(--v-border-color), .12);
  padding: 16px 18px;
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
  background: rgb(var(--v-theme-surface));
  position: relative;
  overflow: hidden;
}
.ss-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,.06);
  border-color: rgba(var(--v-border-color), .24);
}
.ss-kpi-carry { border-top: 3.5px solid #FB8C00 !important; }
.ss-kpi-invoices { border-top: 3.5px solid #E65100 !important; }
.ss-kpi-payments { border-top: 3.5px solid #00897B !important; }
.ss-kpi-bal-pos { border-top: 3.5px solid #E53935 !important; }
.ss-kpi-bal-neg { border-top: 3.5px solid #43A047 !important; }

.ss-kpi-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}
.ss-kpi-icon-wrap {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ss-kpi-icon-amber { background: rgba(251,140,0,.14); color: #F57C00; }
.ss-kpi-icon-orange { background: rgba(230,81,0,.14); color: #E65100; }
.ss-kpi-icon-teal { background: rgba(0,137,123,.14); color: #00897B; }
.ss-kpi-icon-red { background: rgba(229,57,53,.14); color: #E53935; }
.ss-kpi-icon-green { background: rgba(67,160,71,.14); color: #43A047; }

.ss-kpi-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: rgba(var(--v-theme-on-surface), .65);
}
.ss-kpi-value {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -.3px;
  font-feature-settings: "tnum";
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
}
.ss-kpi-cur {
  font-size: 12px;
  font-weight: 600;
  opacity: .7;
}

/* Navigation Tabs Toolbar */
.ss-nav-toolbar {
  display: flex;
  align-items: center;
  padding: 2px 0;
}

/* Month rows */
.ss-month-row {
  border: 1.5px solid rgba(var(--v-border-color), .12);
  border-radius: 16px;
  background: rgb(var(--v-theme-surface));
  margin-bottom: 12px;
  transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
  overflow: hidden;
}
.ss-month-row:hover:not(.ss-month-disabled) {
  border-color: rgba(230,81,0, .35);
  box-shadow: 0 4px 16px rgba(0,0,0,.04);
}
.ss-month-active {
  border-color: rgba(230,81,0, .4) !important;
}
.ss-month-disabled {
  opacity: .42;
  pointer-events: none;
}
.ss-month-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  gap: 12px;
  flex-wrap: wrap;
  background: rgba(var(--v-theme-on-surface), .02);
}
.ss-month-name {
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
.ss-chevron {
  transition: transform .25s ease;
  color: rgba(var(--v-theme-on-surface), .6);
}
.ss-chevron-expanded {
  transform: rotate(180deg);
  color: #E65100;
}

/* Sub tables in expanded month */
.ss-expanded-area {
  border-top: 1px solid rgba(var(--v-border-color), .08);
  background: rgba(var(--v-theme-on-surface), .015);
}
.ss-sub-card {
  border: 1px solid rgba(var(--v-border-color), .1);
  border-radius: 12px;
  overflow: hidden;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 2px 8px rgba(0,0,0,.02);
}
.ss-sub-header {
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
.ss-sub-header-inv {
  background: rgba(230,81,0, .05);
  color: #BF360C;
}
.ss-sub-header-pay {
  background: rgba(0,137,123, .05);
  color: #00695C;
}
.ss-sub-table-wrap {
  overflow-x: auto;
  max-height: 380px;
}

/* Base Tables */
.ss-table-wrap {
  overflow: auto;
  max-height: calc(100vh - 380px);
  border-radius: 14px;
  border: 1.5px solid rgba(var(--v-border-color), .12);
  background: rgb(var(--v-theme-surface));
}
.ss-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}
.ss-th {
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
.ss-td {
  padding: 8px 12px;
  border-bottom: 1px solid rgba(var(--v-border-color), .06);
  white-space: nowrap;
  font-size: 12px;
  vertical-align: middle;
}
.ss-row {
  transition: background-color .15s ease;
}
.ss-row:hover {
  background: rgba(230,81,0, .04);
}
.ss-foot td {
  padding: 10px 12px;
  border-top: 2px solid rgba(230,81,0, .25);
  background: rgba(var(--v-theme-on-surface), .02);
  font-size: 12.5px;
}

.ss-section-title {
  font-size: 13.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: rgba(var(--v-theme-on-surface), .8);
  display: flex;
  align-items: center;
}
.ss-panel-card {
  border: 1.5px solid rgba(230,81,0, .14);
  border-radius: 16px;
  padding: 20px;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 4px 16px rgba(0,0,0,.03);
}

/* AddCharge Modal Styling */
.ss-modal-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: rgba(230,81,0,.12);
  display: flex;
  align-items: center;
  justify-content: center;
}
.ss-datepicker-box {
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

/* Analytics Styles */
.ss-analytics-container {
  animation: fadeIn 0.3s ease;
}
.ss-analytics-toolbar {
  padding: 4px 0;
}
.ss-kpi-card {
  border: 1.5px solid rgba(var(--v-border-color), .12);
  background: rgb(var(--v-theme-surface));
  transition: transform .2s ease, box-shadow .2s ease;
}
.ss-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,.06);
}
.ss-analytics-kpi-val {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -.5px;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}
.ss-ledger-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.ss-ledger-table th {
  padding: 10px 12px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), .6);
  border-bottom: 2px solid rgba(var(--v-border-color), .15);
  background: rgba(var(--v-theme-surface-variant), .3);
}
.ss-ledger-table td {
  padding: 10px 12px;
  border-bottom: 1px solid rgba(var(--v-border-color), .08);
  font-variant-numeric: tabular-nums;
}
.ss-ledger-table tr:hover td {
  background: rgba(var(--v-theme-on-surface), .02);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

<style>
/* Print area styles (unscoped) */
#ss-print-area {
  background: #ffffff;
  color: #1e293b;
}
.ss-print-document {
  padding: 24px 30px;
  max-width: 1060px;
  margin: 0 auto;
  font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
  font-size: 11px;
  color: #1e293b;
  background: #ffffff;
}
.ss-print-compact {
  padding: 14px 20px;
  font-size: 10px;
}
.ss-print-header {
  margin-bottom: 14px;
}
.ss-print-header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 10px;
}
.ss-print-company-info {
  flex-grow: 1;
}
.ss-print-doc-badge {
  text-align: right;
  border-left: 3px solid #E65100;
  padding-left: 12px;
}
.ss-print-meta-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 10px;
  color: #475569;
}
.ss-print-note-callout {
  border-left: 4px solid #E65100;
  background: #FFF8E1;
  padding: 8px 12px;
  border-radius: 4px;
  margin-bottom: 14px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.ss-print-kpis-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 16px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.ss-print-kpi-box {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 6px;
  padding: 8px 12px;
}
.ss-print-kpi-lbl {
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
  letter-spacing: .5px;
}
.ss-print-kpi-num {
  font-size: 15px;
  font-weight: 900;
  margin-top: 2px;
  font-variant-numeric: tabular-nums;
}
.ss-print-months-wrap {
  margin-bottom: 16px;
}
.ss-print-month-block {
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 16px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.ss-print-month-hdr {
  background: linear-gradient(90deg, #E65100, #F57C00);
  color: #ffffff;
  padding: 6px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11px;
}
.ss-print-side-table {
  width: 100%;
  border-collapse: collapse;
}
.ss-print-subtable {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
}
.ss-print-subtable th {
  padding: 5px 8px;
  font-size: 9px;
  font-weight: 700;
  border-bottom: 1px solid #E2E8F0;
  text-align: left;
}
.ss-print-subtable td {
  padding: 4px 8px;
  border-bottom: 1px solid #F1F5F9;
  font-variant-numeric: tabular-nums;
}
.ss-print-tot-row td {
  font-size: 10px;
  padding: 5px 8px;
  border-top: 1px solid #CBD5E1;
}
.ss-print-section-block {
  border: 1px solid #CBD5E1;
  border-radius: 8px;
  overflow: hidden;
  margin-top: 16px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.ss-print-block-hdr {
  color: #ffffff;
  padding: 6px 12px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .5px;
  text-transform: uppercase;
}
.ss-print-matrix-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
}
.ss-print-matrix-table th {
  background: #F8FAFC;
  padding: 6px 10px;
  font-weight: 700;
  font-size: 9px;
  border-bottom: 1px solid #E2E8F0;
  color: #475569;
}
.ss-print-matrix-table td {
  padding: 5px 10px;
  border-bottom: 1px solid #F1F5F9;
  font-variant-numeric: tabular-nums;
}
.ss-print-matrix-tot td {
  padding: 6px 10px;
  border-top: 2px solid #CBD5E1;
}
.ss-print-stats-strip {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-top: 16px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.ss-print-stat-card {
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #E2E8F0;
}
.ss-print-stat-title {
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: 6px;
}
.ss-print-stat-item {
  font-size: 10px;
  color: #475569;
  margin-bottom: 2px;
  display: flex;
  justify-content: space-between;
}
.ss-print-signatures-block {
  display: flex;
  justify-content: space-between;
  gap: 60px;
  margin-top: 36px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.ss-print-sign-box {
  flex: 1;
  text-align: center;
}
.ss-print-sign-line {
  border-bottom: 1.5px solid #334155;
  height: 36px;
  margin-bottom: 6px;
}
.ss-print-sign-label {
  font-size: 10px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
}
.ss-print-footer {
  margin-top: 20px;
  padding-top: 8px;
  border-top: 1px dashed #CBD5E1;
  font-size: 9px;
  color: #94a3b8;
  display: flex;
  justify-content: space-between;
  page-break-inside: avoid;
  break-inside: avoid;
}

@media print {
  body {
    background: #ffffff !important;
  }
  #ss-print-area {
    display: block !important;
  }
  @page {
    margin: 10mm 12mm;
    size: auto;
  }
}
</style>
