import { useMain } from '@/stores/Main';
import { useSupplierPayment } from '@/stores/SupplierPayment';
import { useSupplier } from '@/stores/Supplier';
import { useBankName } from '@/stores/BankName';
import { useCompanySettings } from '@/stores/CompanySettings';
import mitt from '@/plugins/mitt.js';
import axios from 'axios';
import { gqlGetSupplierInvoices } from '@/services/appGraphql.js';
import * as XLSX from 'xlsx';

export default {
  name: 'SupplierSettlement',
  data() {
    return {
      currentYear: new Date().getFullYear(),
      yearInvoices: [],
      yearInvoicesLoading: false,
      supplierInvoices: [],
      // Add payment form
      payForm: {
        date: null,
        supplier_id: null,
        supplier_invoice_id: null,
        payment_type: 'cash',
        amount: null,
        reference: '',
        bank_name: '',
        due_date: null,
        notes: '',
      },
      editId: null,
      showPayForm: false,
      saving: false,
      // Selection states for invoices and payments
      selected: [],
      selectedPayments: [],
      selectedInvoices: [],
      deleteDialog: false, deleting: false, deleteBulk: false, deleteId: null,
      // Close year
      closeYearDialog: false, closingYear: false,
      reopenDialog: false, reopening: false,
      // Tab
      activeTab: 'balance',
      // Supplier filter for balance grid
      selectedSupplierIds: [],
      // Expanded months
      expandedMonths: {},
      // Print Customizer State
      printOptionsDialog: false,
      printOptions: {
        preset: 'active',
        monthMode: 'active',
        selectedMonths: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        includeHeader: true,
        includeKpis: true,
        includeInvoices: true,
        includePayments: true,
        includeYearSummary: true,
        includeAnalytics: true,
        includeSignatures: true,
        compactMode: false,
        notes: '',
      },
      // Rich Analytics State
      analyticsPeriod: 'year', // 'year' | 'q1' | 'q2' | 'q3' | 'q4' | 'active'
      analyticsChartType: 'flow', // 'flow' | 'cumulative' | 'rate'
      // Legacy print state
      printReady: false,
      printLoading: false,
      // Rule 5: Summary Snackbar with Countdown Progress Bar
      summarySnackbar: {
        show: false,
        title: '',
        detail: '',
        color: 'success',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain() },
    store() { return useSupplierPayment() },
    supplierStore() { return useSupplier() },
    suppliers() { return this.supplierStore.list || [] },
    supplierItems() {
      return this.suppliers.map(s => ({ title: s.name, value: s.id }));
    },
    bankNameStore() { return useBankName(); },
    companySettings() { return useCompanySettings(); },
    logoUrl() {
      const cs = useCompanySettings();
      if (cs.settings.logo_invoice_url) return cs.settings.logo_invoice_url;
      return new URL('/storage/images/logo_login.png', window.location.origin).href;
    },
    bankNameItems() {
      return this.bankNameStore.list.map(b => ({ title: b.name, value: b.name }));
    },
    yearOptions() {
      const y = new Date().getFullYear();
      const years = [];
      for (let i = y; i >= y - 10; i--) years.push(i);
      return years;
    },
    paymentTypes() {
      return [
        { title: this.$t('cash'), value: 'cash' },
        { title: this.$t('check'), value: 'check' },
        { title: this.$t('bank_transfer'), value: 'bank_transfer' },
        { title: this.$t('bill_of_exchange'), value: 'bill_of_exchange' },
        { title: this.$t('offset'), value: 'offset' },
      ];
    },
    months() {
      return Array.from({ length: 12 }, (_, i) => ({
        num: i + 1,
        label: new Date(this.currentYear, i, 1).toLocaleString(this.main.lang?.short || 'fr', { month: 'long' }),
        short: new Date(this.currentYear, i, 1).toLocaleString(this.main.lang?.short || 'fr', { month: 'short' }),
      }));
    },
    isClosed() {
      return this.store.currentClosing?.is_closed === true;
    },
    currentMonth() {
      const now = new Date();
      return { year: now.getFullYear(), month: now.getMonth() + 1 };
    },
    minPayDate() {
      return new Date(this.currentYear, 0, 1);
    },
    maxPayDate() {
      const now = new Date();
      const yearEnd = new Date(this.currentYear, 11, 31);
      return yearEnd < now ? yearEnd : now;
    },
    payDateModel: {
      get() {
        if (!this.payForm.date) return null;
        const d = new Date(this.payForm.date + 'T00:00:00');
        return isNaN(d) ? null : d;
      },
      set(val) {
        if (!val) { this.payForm.date = null; return; }
        const d = val instanceof Date ? val : new Date(val);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        this.payForm.date = `${y}-${m}-${day}`;
      },
    },
    // Print: label showing which suppliers are filtered
    printSupplierLabel() {
      if (!this.selectedSupplierIds.length) return '';
      return this.suppliers
        .filter(s => this.selectedSupplierIds.includes(s.id))
        .map(s => s.name)
        .join(', ');
    },
    // Print: only months that have data (invoices or payments)
    printMonths() {
      const now = new Date();
      const curYear = now.getFullYear();
      const curMonth = now.getMonth() + 1;
      return this.months.filter(m => {
        if (this.currentYear > curYear) return false;
        if (this.currentYear === curYear && m.num > curMonth) return false;
        const hasInv = this.invoicesByMonth[m.num] && this.invoicesByMonth[m.num].length > 0;
        const hasPay = this.paymentsByMonth[m.num] && this.paymentsByMonth[m.num].length > 0;
        return hasInv || hasPay;
      });
    },
    // Print: statistics for the footer
    printStats() {
      let invoiceCount = 0;
      let totalWeight = 0;
      let paymentCount = 0;
      const methodCounts = {};
      for (let m = 1; m <= 12; m++) {
        const invs = this.invoicesByMonth[m] || [];
        invoiceCount += invs.length;
        invs.forEach(inv => { totalWeight += Number(inv.total_weight || 0); });
        const pays = this.paymentsByMonth[m] || [];
        paymentCount += pays.length;
        pays.forEach(p => {
          const type = this.paymentTypeLabel(p.payment_type);
          methodCounts[type] = (methodCounts[type] || 0) + 1;
        });
      }
      const paymentMethodsSummary = Object.entries(methodCounts)
        .map(([type, count]) => `${type} (${count})`)
        .join(', ') || '-';
      return { invoiceCount, totalWeight, paymentCount, paymentMethodsSummary };
    },
    filteredInvoices() {
      if (!this.payForm.supplier_id) return [];
      return this.supplierInvoices;
    },
    filteredYearSummary() {
      const sel = this.selectedSupplierIds;
      if (!sel.length) return this.store.yearSummary;
      return this.store.yearSummary.filter(r => sel.includes(r.supplier_id));
    },
    totalInvoices() {
      return this.filteredYearSummary.reduce((s, r) => s + Number(r.total_invoices || 0), 0);
    },
    totalPayments() {
      return this.filteredYearSummary.reduce((s, r) => s + Number(r.total_payments || 0), 0);
    },
    totalCarryForward() {
      return this.filteredYearSummary.reduce((s, r) => s + Number(r.carry_forward || 0), 0);
    },
    totalBalance() {
      return this.filteredYearSummary.reduce((s, r) => s + Number(r.balance || 0), 0);
    },
    // Extract supplier IDs from an invoice
    // Group invoices by month, filtered by selected suppliers
    invoicesByMonth() {
      const sel = this.selectedSupplierIds;
      const grouped = {};
      for (let m = 1; m <= 12; m++) grouped[m] = [];
      (this.yearInvoices || []).forEach(inv => {
        const d = new Date(inv.date);
        const m = d.getMonth() + 1;
        if (!grouped[m]) return;
        const sId = inv.supplier_id;
        const sName = inv.supplier?.name || '-';
        // Filter: if suppliers selected, invoice must match
        if (sel.length && (!sId || !sel.includes(sId))) return;
        grouped[m].push({
          ...inv,
          _supplierNames: sName,
          _trackingStr: (inv.tracking_numbers || []).join(', ') || `#${inv.id}`,
        });
      });
      return grouped;
    },
    // Group payments by month, filtered by selected suppliers
    paymentsByMonth() {
      const sel = this.selectedSupplierIds;
      const grouped = {};
      for (let m = 1; m <= 12; m++) grouped[m] = [];
      (this.store.payments || []).forEach(p => {
        const d = new Date(p.date);
        const m = d.getMonth() + 1;
        if (!grouped[m]) return;
        if (sel.length && !sel.includes(p.supplier_id)) return;
        grouped[m].push({
          ...p,
          _supplierName: p.supplier?.name || '-',
          _trackingStr: p.supplier_invoice?.tracking_numbers?.join(', ') || '-',
        });
      });
      return grouped;
    },
    // Month totals
    monthTotals() {
      const totals = {};
      for (let m = 1; m <= 12; m++) {
        const invTotal = this.invoicesByMonth[m].reduce((s, inv) => s + Number(inv.total_price || 0), 0);
        const payTotal = this.paymentsByMonth[m].reduce((s, p) => s + Number(p.amount || 0), 0);
        totals[m] = { invoices: invTotal, payments: payTotal, balance: invTotal - payTotal };
      }
      return totals;
    },
    paymentHeaders() {
      return [
        { title: this.$t('date'), key: 'date' },
        { title: this.$t('suppliers'), key: 'supplier_name' },
        { title: this.$t('invoice_tracking_number'), key: 'invoice_tracking' },
        { title: this.$t('payment_type'), key: 'payment_type' },
        { title: this.$t('payment_amount'), key: 'amount' },
        { title: this.$t('payment_reference'), key: 'reference' },
        { title: this.$t('bank_name'), key: 'bank_name' },
        { title: this.$t('due_date'), key: 'due_date' },
        { title: this.$t('actions'), key: 'actions', sortable: false },
      ];
    },
    mappedPayments() {
      return this.store.payments.map(p => ({
        ...p,
        supplier_name: p.supplier?.name || '-',
        invoice_tracking: p.supplier_invoice?.tracking_numbers?.join(', ') || '-',
      }));
    },
    // Analytics month totals filtered by analyticsSupplierIds
    analyticsMonthTotals() {
      const sel = this.selectedSupplierIds;
      const totals = {};
      for (let m = 1; m <= 12; m++) {
        let invTotal = 0;
        let payTotal = 0;
        // Filter invoices
        (this.yearInvoices || []).forEach(inv => {
          const d = new Date(inv.date);
          if (d.getMonth() + 1 !== m) return;
          const sId = inv.supplier_id;
          if (sel.length && (!sId || !sel.includes(sId))) return;
          invTotal += Number(inv.total_price || 0);
        });
        // Filter payments
        (this.store.payments || []).forEach(p => {
          const d = new Date(p.date);
          if (d.getMonth() + 1 !== m) return;
          if (sel.length && !sel.includes(p.supplier_id)) return;
          payTotal += Number(p.amount || 0);
        });
        totals[m] = { invoices: invTotal, payments: payTotal, balance: invTotal - payTotal };
      }
      return totals;
    },
    activeMonthsList() {
      return this.months
        .filter(m => (this.invoicesByMonth[m.num]?.length > 0 || this.paymentsByMonth[m.num]?.length > 0))
        .map(m => m.num);
    },
    printableMonths() {
      if (this.printOptions.monthMode === 'all') return this.months;
      if (this.printOptions.monthMode === 'active') {
        const active = this.months.filter(m => (this.invoicesByMonth[m.num]?.length > 0 || this.paymentsByMonth[m.num]?.length > 0));
        return active.length ? active : this.months.slice(0, 1);
      }
      return this.months.filter(m => this.printOptions.selectedMonths.includes(m.num));
    },
    printableInvoicesCount() {
      return this.printableMonths.reduce((s, m) => s + (this.invoicesByMonth[m.num]?.length || 0), 0);
    },
    printablePaymentsCount() {
      return this.printableMonths.reduce((s, m) => s + (this.paymentsByMonth[m.num]?.length || 0), 0);
    },
    // Analytics period months array
    analyticsPeriodMonths() {
      if (this.analyticsPeriod === 'q1') return [1, 2, 3];
      if (this.analyticsPeriod === 'q2') return [4, 5, 6];
      if (this.analyticsPeriod === 'q3') return [7, 8, 9];
      if (this.analyticsPeriod === 'q4') return [10, 11, 12];
      if (this.analyticsPeriod === 'active') {
        const active = this.activeMonthsList;
        return active.length ? active : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
      }
      return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    },
    analyticsKPIs() {
      const months = this.analyticsPeriodMonths;
      let invoiced = 0;
      let payments = 0;
      let invoiceCount = 0;
      let paymentCount = 0;
      let totalWeight = 0;
      let peakMonthVal = -1;
      let peakMonthLabel = '-';

      months.forEach(m => {
        const invTotal = this.analyticsMonthTotals[m]?.invoices || 0;
        const payTotal = this.analyticsMonthTotals[m]?.payments || 0;
        invoiced += invTotal;
        payments += payTotal;
        const invs = this.invoicesByMonth[m] || [];
        invoiceCount += invs.length;
        invs.forEach(i => { totalWeight += Number(i.total_weight || 0); });
        paymentCount += (this.paymentsByMonth[m] || []).length;
        if (invTotal > peakMonthVal && invTotal > 0) {
          peakMonthVal = invTotal;
          peakMonthLabel = this.months[m - 1]?.label || '-';
        }
      });

      const netBalance = invoiced - payments;
      const settlementRate = invoiced > 0 ? Math.min(100, Math.round((payments / invoiced) * 1000) / 10) : (payments > 0 ? 100 : 0);
      const avgInvoice = invoiceCount > 0 ? Math.round((invoiced / invoiceCount) * 100) / 100 : 0;
      const avgPayment = paymentCount > 0 ? Math.round((payments / paymentCount) * 100) / 100 : 0;

      return {
        invoiced,
        payments,
        invoiceCount,
        paymentCount,
        avgInvoice,
        avgPayment,
        totalWeight,
        netBalance,
        settlementRate,
        peakMonthLabel,
        peakMonthVal: peakMonthVal > -1 ? peakMonthVal : 0,
      };
    },
    topSuppliersList() {
      const summary = [...(this.filteredYearSummary || [])];
      summary.sort((a, b) => Number(b.total_invoices || 0) - Number(a.total_invoices || 0));
      const totalInv = this.totalInvoices || 1;
      return summary.slice(0, 5).map((s, idx) => {
        const inv = Number(s.total_invoices || 0);
        const pay = Number(s.total_payments || 0);
        const share = Math.round((inv / totalInv) * 1000) / 10;
        const rate = inv > 0 ? Math.min(100, Math.round((pay / inv) * 100)) : (pay > 0 ? 100 : 0);
        return {
          rank: idx + 1,
          id: s.supplier_id,
          name: s.supplier_name || this.supplierName(s.supplier_id),
          totalInvoiced: inv,
          totalPayments: pay,
          balance: Number(s.balance || 0),
          share,
          rate,
        };
      });
    },
    paymentMethodsDistribution() {
      const counts = {};
      const amounts = {};
      this.paymentTypes.forEach(t => {
        counts[t.value] = 0;
        amounts[t.value] = 0;
      });

      const months = this.analyticsPeriodMonths;
      months.forEach(m => {
        (this.paymentsByMonth[m] || []).forEach(p => {
          const type = p.payment_type || 'cash';
          counts[type] = (counts[type] || 0) + 1;
          amounts[type] = (amounts[type] || 0) + Number(p.amount || 0);
        });
      });

      const totalAmount = Object.values(amounts).reduce((a, b) => a + b, 0);
      const details = this.paymentTypes.map(t => {
        const amt = amounts[t.value] || 0;
        const cnt = counts[t.value] || 0;
        const pct = totalAmount > 0 ? Math.round((amt / totalAmount) * 1000) / 10 : 0;
        return {
          type: t.value,
          title: t.title,
          count: cnt,
          amount: amt,
          percent: pct,
        };
      }).filter(d => d.count > 0 || totalAmount === 0);

      const hasData = totalAmount > 0;
      const series = details.map(d => Math.round(d.amount * 100) / 100);
      const labels = details.map(d => d.title);

      return { details, hasData, series, labels, totalAmount };
    },
    donutChartOptions() {
      return {
        chart: { type: 'donut', height: 260, fontFamily: 'Inter, sans-serif', background: 'transparent' },
        labels: this.paymentMethodsDistribution.labels,
        colors: ['#E65100', '#00897B', '#1E88E5', '#8E24AA', '#546E7A'],
        legend: { position: 'bottom', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: true, formatter: val => `${val.toFixed(1)}%` },
        tooltip: {
          theme: this.main.is_dark ? 'dark' : 'light',
          y: { formatter: v => `${this.main.formatDisplayNumber(v)} ${this.main.currency}` }
        },
        plotOptions: {
          pie: {
            donut: {
              size: '68%',
              labels: {
                show: true,
                total: {
                  show: true,
                  label: this.$t('payments'),
                  fontSize: '11px',
                  fontWeight: 600,
                  formatter: () => `${this.main.formatDisplayNumber(this.paymentMethodsDistribution.totalAmount)} ${this.main.currency}`
                }
              }
            }
          }
        },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
    primaryChartSeries() {
      const months = this.analyticsPeriodMonths;
      const r = v => v === null ? 0 : Math.round(v * 100) / 100;

      if (this.analyticsChartType === 'rate') {
        const rates = months.map(m => {
          const inv = this.analyticsMonthTotals[m].invoices || 0;
          const pay = this.analyticsMonthTotals[m].payments || 0;
          if (inv === 0 && pay === 0) return 0;
          if (inv === 0 && pay > 0) return 100;
          return Math.min(100, Math.round((pay / inv) * 1000) / 10);
        });
        return [{ name: this.$t('settlement_rate'), type: 'bar', data: rates }];
      }

      if (this.analyticsChartType === 'cumulative') {
        let running = this.totalCarryForward;
        const data = months.map(m => {
          running += (this.analyticsMonthTotals[m].invoices - this.analyticsMonthTotals[m].payments);
          return r(running);
        });
        return [{ name: this.$t('chart_cumulative_mode'), type: 'area', data }];
      }

      // Default: 'flow' (Comparison)
      let running = this.totalCarryForward;
      const invData = months.map(m => r(this.analyticsMonthTotals[m].invoices));
      const payData = months.map(m => r(this.analyticsMonthTotals[m].payments));
      const balData = months.map(m => {
        running += (this.analyticsMonthTotals[m].invoices - this.analyticsMonthTotals[m].payments);
        return r(running);
      });

      return [
        { name: this.$t('supplier_invoices'), type: 'bar', data: invData },
        { name: this.$t('payments'), type: 'bar', data: payData },
        { name: this.$t('outstanding_balance'), type: 'line', data: balData },
      ];
    },
    primaryChartOptions() {
      const categories = this.analyticsPeriodMonths.map(m => this.months[m - 1].short);
      const isDark = this.main.is_dark;

      if (this.analyticsChartType === 'rate') {
        return {
          chart: { type: 'bar', height: 330, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
          plotOptions: { bar: { borderRadius: 6, columnWidth: '40%', dataLabels: { position: 'top' } } },
          colors: ['#00897B'],
          xaxis: { categories, labels: { style: { fontSize: '11px', fontWeight: 600 } } },
          yaxis: { max: 100, labels: { formatter: v => `${v}%` } },
          dataLabels: { enabled: true, formatter: v => `${v}%`, offsetY: -18, style: { fontSize: '10px', colors: [isDark ? '#E0E0E0' : '#37474F'] } },
          grid: { borderColor: isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)', strokeDashArray: 4 },
          tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: v => `${v}%` } },
          theme: { mode: isDark ? 'dark' : 'light' },
        };
      }

      if (this.analyticsChartType === 'cumulative') {
        return {
          chart: { type: 'area', height: 330, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
          stroke: { curve: 'smooth', width: 3 },
          colors: ['#E65100'],
          fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 95, 100] }
          },
          markers: { size: 4, colors: ['#E65100'], strokeWidth: 2, strokeColors: '#fff' },
          xaxis: { categories, labels: { style: { fontSize: '11px', fontWeight: 600 } } },
          yaxis: { labels: { formatter: v => this.main.formatDisplayNumber(v) } },
          grid: { borderColor: isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)', strokeDashArray: 4 },
          tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: v => `${this.main.formatDisplayNumber(v)} ${this.main.currency}` } },
          theme: { mode: isDark ? 'dark' : 'light' },
        };
      }

      // Default: 'flow'
      return {
        chart: { type: 'line', height: 330, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
        stroke: { width: [0, 0, 3], curve: 'smooth' },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
        colors: ['#F57C00', '#26A69A', '#E53935'],
        fill: { opacity: [1, 1, 1] },
        markers: { size: [0, 0, 4], colors: ['#E53935'], strokeWidth: 0 },
        xaxis: { categories, labels: { style: { fontSize: '11px', fontWeight: 600 } } },
        yaxis: { labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
        tooltip: {
          shared: true,
          intersect: false,
          theme: isDark ? 'dark' : 'light',
          y: { formatter: v => v !== null ? `${this.main.formatDisplayNumber(v)} ${this.main.currency}` : '-' }
        },
        grid: { borderColor: isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)', strokeDashArray: 4 },
        legend: { position: 'top', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: false },
        theme: { mode: isDark ? 'dark' : 'light' },
      };
    },
    monthlyLedgerData() {
      let running = this.totalCarryForward;
      return this.analyticsPeriodMonths.map(m => {
        const invTotal = this.analyticsMonthTotals[m]?.invoices || 0;
        const payTotal = this.analyticsMonthTotals[m]?.payments || 0;
        const net = invTotal - payTotal;
        running += net;
        const invs = this.invoicesByMonth[m] || [];
        const pays = this.paymentsByMonth[m] || [];
        const rate = invTotal > 0 ? Math.min(100, Math.round((payTotal / invTotal) * 100)) : (payTotal > 0 ? 100 : 0);
        return {
          monthNum: m,
          monthLabel: this.months[m - 1]?.label || `Month ${m}`,
          invoicesCount: invs.length,
          invoicesTotal: invTotal,
          paymentsCount: pays.length,
          paymentsTotal: payTotal,
          net,
          cumulative: running,
          settlementRate: rate,
        };
      });
    },
    // Backwards compatibility for existing chart
    chartSeries() {
      return this.primaryChartSeries;
    },
    chartOptions() {
      return this.primaryChartOptions;
    },
  },
  mounted() {
    this.loadYear();
    this.bankNameStore.fetchAll();
    mitt.on('model-changed', this.onMC);
  },
  beforeUnmount() {
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
    mitt.off('model-changed', this.onMC);
  },
  methods: {
    triggerSummarySnackbar(title, detail = '', color = 'success') {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, title, detail, color };
      this.snackbarProgress = 100;
      const duration = 5000;
      const interval = 50;
      const step = (interval / duration) * 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= step;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, interval);
    },
    toggleAllMonths() {
      const activeMonths = this.months.filter(m => !this.isFutureMonth(m.num));
      const allExpanded = activeMonths.length > 0 && activeMonths.every(m => !!this.expandedMonths[m.num]);
      const nextState = {};
      activeMonths.forEach(m => {
        nextState[m.num] = !allExpanded;
      });
      this.expandedMonths = nextState;
    },
    isAllMonthsExpanded() {
      const activeMonths = this.months.filter(m => !this.isFutureMonth(m.num));
      return activeMonths.length > 0 && activeMonths.every(m => !!this.expandedMonths[m.num]);
    },
    setPayDateToday() {
      const now = new Date();
      if (now.getFullYear() === this.currentYear) {
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        this.payForm.date = `${y}-${m}-${day}`;
      } else {
        this.payForm.date = `${this.currentYear}-12-31`;
      }
    },
    setPayDateYesterday() {
      const now = new Date();
      if (now.getFullYear() === this.currentYear) {
        now.setDate(now.getDate() - 1);
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        this.payForm.date = `${y}-${m}-${day}`;
      } else {
        this.payForm.date = `${this.currentYear}-12-30`;
      }
    },
    onMC(d) {
      if (d.model === 'SupplierPayment') {
        // Another user changed a payment - refresh payments + summary only
        this.store.fetchPayments(this.currentYear);
        this.store.fetchYearSummary(this.currentYear);
        this.triggerSummarySnackbar(this.$t('realtime_sync'), this.$t('payments'), 'info');
      } else if (d.model === 'SupplierInvoice') {
        // Invoice changed - refresh invoices + summary
        this.fetchYearInvoices();
        this.store.fetchYearSummary(this.currentYear);
        this.triggerSummarySnackbar(this.$t('realtime_sync'), this.$t('supplier_invoices'), 'info');
      } else if (d.model === 'FiscalYearClosing') {
        // Year open/close status changed
        this.store.fetchYearSummary(this.currentYear);
        this.store.fetchClosings();
        this.triggerSummarySnackbar(this.$t('realtime_sync'), this.$t('fiscal_year_closing'), 'info');
      } else if (d.model === 'BankName') {
        this.bankNameStore.fetchAll();
      }
    },
    async loadYear() {
      this.store.currentYear = this.currentYear;
      await Promise.all([
        this.store.refreshAll(this.currentYear),
        this.fetchYearInvoices(),
      ]);
    },
    async fetchYearInvoices() {
      this.yearInvoicesLoading = true;
      const date_start = `${this.currentYear}-01-01`;
      const date_end = `${this.currentYear}-12-31`;
      try {
        let items = [];
        try {
          items = await gqlGetSupplierInvoices({ date_start, date_end });
        } catch (gqlErr) {
          console.warn('[SupplierSettlement] GraphQL fetchYearInvoices failed, falling back to REST:', gqlErr);
          const r = await axios.get('/api/supplier_invoice', {
            params: { date_start, date_end },
          });
          items = r.data.supplier_invoices || [];
        }
        this.yearInvoices = items || [];
      } catch (e) { console.error(e) }
      this.yearInvoicesLoading = false;
    },
    async fetchSupplierInvoices() {
      if (!this.payForm.supplier_id) { this.supplierInvoices = []; return; }
      const date_start = `${this.currentYear}-01-01`;
      const date_end = `${this.currentYear}-12-31`;
      try {
        let items = [];
        try {
          items = await gqlGetSupplierInvoices({ date_start, date_end });
        } catch (gqlErr) {
          console.warn('[SupplierSettlement] GraphQL fetchSupplierInvoices failed, falling back to REST:', gqlErr);
          const r = await axios.get('/api/supplier_invoice', {
            params: { date_start, date_end },
          });
          items = r.data.supplier_invoices || [];
        }
        this.supplierInvoices = (items || []).filter(inv => {
          return inv.supplier_id === this.payForm.supplier_id;
        });
      } catch (e) { console.error(e) }
    },
    onSupplierChange() {
      this.payForm.supplier_invoice_id = null;
      this.fetchSupplierInvoices();
    },
    resetPayForm() {
      this.payForm = { date: null, supplier_id: null, supplier_invoice_id: null, payment_type: 'cash', amount: null, reference: '', bank_name: '', due_date: null, notes: '' };
      this.editId = null;
    },
    changeYear(delta) {
      this.currentYear += delta;
      this.loadYear();
    },
    openAddPayment() {
      this.resetPayForm();
      // Default supplier: first from filter, then from year summary, then from supplier list
      if (this.selectedSupplierIds.length) {
        this.payForm.supplier_id = this.selectedSupplierIds[0];
      } else if (this.store.yearSummary?.length) {
        this.payForm.supplier_id = this.store.yearSummary[0].supplier_id;
      } else if (this.suppliers.length) {
        this.payForm.supplier_id = this.suppliers[0].id;
      }
      this.showPayForm = true;
    },
    async createBankNameIfNew() {
      const search = (this.bankNameSearch || '').trim();
      if (!search) return;
      // Check if already exists (case-insensitive)
      const existing = this.bankNameStore.list.find(b => b.name.toLowerCase() === search.toLowerCase());
      if (existing) {
        this.payForm.bank_name = existing.name;
        this.bankNameSearch = '';
        this.$nextTick(() => { this.$refs.bankNameInput?.blur(); });
        return;
      }
      try {
        const created = await this.bankNameStore.create(search);
        this.payForm.bank_name = created.name;
        this.bankNameSearch = '';
        this.main.add_snackbar({ show: true, message: this.$t('bank_name_created'), color: 'success' });
        this.$nextTick(() => { this.$refs.bankNameInput?.blur(); });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('error'), color: 'error' });
      }
    },
    editPayment(p) {
      this.payForm = {
        date: p.date ? p.date.slice(0, 10) : null,
        supplier_id: p.supplier_id,
        supplier_invoice_id: p.supplier_invoice_id,
        payment_type: p.payment_type,
        amount: p.amount,
        reference: p.reference || '',
        bank_name: p.bank_name || '',
        due_date: p.due_date ? p.due_date.slice(0, 10) : null,
        notes: p.notes || '',
      };
      this.editId = p.id;
      this.showPayForm = true;
      this.fetchSupplierInvoices();
    },
    async savePayment() {
      if (!this.payForm.supplier_id || !this.payForm.amount || !this.payForm.date) return;
      this.saving = true;
      try {
        const sName = this.supplierName(this.payForm.supplier_id);
        const amtStr = `${this.main.formatDisplayNumber(this.payForm.amount)} ${this.main.currency}`;
        if (this.editId) {
          await this.store.updatePayment(this.editId, this.payForm);
          this.triggerSummarySnackbar(this.$t('payment_updated'), `${sName} • ${amtStr}`, 'success');
        } else {
          await this.store.createPayment(this.payForm);
          this.triggerSummarySnackbar(this.$t('payment_created'), `${sName} • ${amtStr}`, 'success');
        }
        this.showPayForm = false;
        this.resetPayForm();
        // Only refresh summary cards (lightweight) - payments array already updated in store
        this.store.fetchYearSummary(this.currentYear);
      } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || this.$t('error');
        this.triggerSummarySnackbar(this.$t('error'), msg, 'error');
      }
      this.saving = false;
    },
    // === Invoices Multi-Selection Methods ===
    isInvoiceSelected(id) {
      return this.selectedInvoices.includes(id);
    },
    toggleInvoice(id) {
      const idx = this.selectedInvoices.indexOf(id);
      if (idx > -1) {
        this.selectedInvoices.splice(idx, 1);
      } else {
        this.selectedInvoices.push(id);
      }
    },
    isAllInvoicesMonthSelected(mNum) {
      const monthInvs = this.invoicesByMonth[mNum] || [];
      if (!monthInvs.length) return false;
      return monthInvs.every(inv => this.selectedInvoices.includes(inv.id));
    },
    isSomeInvoicesMonthSelected(mNum) {
      const monthInvs = this.invoicesByMonth[mNum] || [];
      if (!monthInvs.length) return false;
      const count = monthInvs.filter(inv => this.selectedInvoices.includes(inv.id)).length;
      return count > 0 && count < monthInvs.length;
    },
    toggleAllInvoicesMonth(mNum) {
      const monthInvs = this.invoicesByMonth[mNum] || [];
      if (!monthInvs.length) return;
      const allSelected = this.isAllInvoicesMonthSelected(mNum);
      const ids = monthInvs.map(i => i.id);
      if (allSelected) {
        this.selectedInvoices = this.selectedInvoices.filter(id => !ids.includes(id));
      } else {
        const toAdd = ids.filter(id => !this.selectedInvoices.includes(id));
        this.selectedInvoices = [...this.selectedInvoices, ...toAdd];
      }
    },
    getSelectedInvoicesCountMonth(mNum) {
      const monthInvs = this.invoicesByMonth[mNum] || [];
      return monthInvs.filter(i => this.selectedInvoices.includes(i.id)).length;
    },
    getSelectedInvoicesTotalMonth(mNum) {
      const monthInvs = this.invoicesByMonth[mNum] || [];
      return monthInvs
        .filter(i => this.selectedInvoices.includes(i.id))
        .reduce((acc, i) => acc + (parseFloat(i.total_price) || 0), 0);
    },
    viewSelectedInvoices(mNum) {
      const monthInvs = this.invoicesByMonth[mNum] || [];
      let ids = monthInvs.filter(i => this.selectedInvoices.includes(i.id)).map(i => i.id);
      if (!ids.length) ids = this.selectedInvoices;
      if (!ids.length) return;
      this.$router.push({ name: 'view_supplier_invoice', query: { ids } });
    },

    // === Payments Multi-Selection Methods ===
    isPaymentSelected(id) {
      return this.selectedPayments.includes(id);
    },
    togglePayment(id) {
      const idx = this.selectedPayments.indexOf(id);
      if (idx > -1) {
        this.selectedPayments.splice(idx, 1);
      } else {
        this.selectedPayments.push(id);
      }
      this.selected = [...this.selectedPayments];
    },
    isAllPaymentsMonthSelected(mNum) {
      const monthPays = this.paymentsByMonth[mNum] || [];
      if (!monthPays.length) return false;
      return monthPays.every(p => this.selectedPayments.includes(p.id));
    },
    isSomePaymentsMonthSelected(mNum) {
      const monthPays = this.paymentsByMonth[mNum] || [];
      if (!monthPays.length) return false;
      const count = monthPays.filter(p => this.selectedPayments.includes(p.id)).length;
      return count > 0 && count < monthPays.length;
    },
    toggleAllPaymentsMonth(mNum) {
      const monthPays = this.paymentsByMonth[mNum] || [];
      if (!monthPays.length) return;
      const allSelected = this.isAllPaymentsMonthSelected(mNum);
      const ids = monthPays.map(p => p.id);
      if (allSelected) {
        this.selectedPayments = this.selectedPayments.filter(id => !ids.includes(id));
      } else {
        const toAdd = ids.filter(id => !this.selectedPayments.includes(id));
        this.selectedPayments = [...this.selectedPayments, ...toAdd];
      }
      this.selected = [...this.selectedPayments];
    },
    getSelectedPaymentsCountMonth(mNum) {
      const monthPays = this.paymentsByMonth[mNum] || [];
      return monthPays.filter(p => this.selectedPayments.includes(p.id)).length;
    },
    getSelectedPaymentsTotalMonth(mNum) {
      const monthPays = this.paymentsByMonth[mNum] || [];
      return monthPays
        .filter(p => this.selectedPayments.includes(p.id))
        .reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
    },
    getSelectedPaymentsTotal() {
      if (this.deleteBulk) {
        return this.store.payments
          .filter(p => this.selected.includes(p.id))
          .reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
      }
      const single = this.store.payments.find(p => p.id === this.deleteId);
      return single ? (parseFloat(single.amount) || 0) : 0;
    },
    confirmDelete(id) {
      this.deleteId = id;
      this.deleteBulk = false;
      this.deleteDialog = true;
    },
    confirmBulkDelete() {
      this.deleteBulk = true;
      this.deleteDialog = true;
    },
    confirmBulkDeleteMonth(mNum) {
      const monthPays = this.paymentsByMonth[mNum] || [];
      const ids = monthPays.filter(p => this.selectedPayments.includes(p.id)).map(p => p.id);
      if (!ids.length) return;
      this.selected = ids;
      this.deleteBulk = true;
      this.deleteDialog = true;
    },
    async execDelete() {
      this.deleting = true;
      try {
        const ids = this.deleteBulk ? this.selected : [this.deleteId];
        await this.store.deletePayments(ids);
        this.triggerSummarySnackbar(this.$t('payment_deleted'), '', 'success');
        this.selectedPayments = this.selectedPayments.filter(id => !ids.includes(id));
        this.selected = [...this.selectedPayments];
        this.deleteDialog = false;
        // Only refresh summary cards - payments array already updated in store
        this.store.fetchYearSummary(this.currentYear);
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error'), '', 'error');
      }
      this.deleting = false;
    },
    async doCloseYear() {
      this.closingYear = true;
      try {
        await this.store.closeYear(this.currentYear);
        this.triggerSummarySnackbar(this.$t('year_closed_success', { year: this.currentYear }), '', 'success');
        this.closeYearDialog = false;
        // Reload current year (closing status + data) and preload next year (carry-forward propagation)
        await this.loadYear();
        this.store.fetchYearSummary(this.currentYear + 1);
        this.store.fetchBalance(this.currentYear + 1);
      } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || this.$t('error');
        this.triggerSummarySnackbar(this.$t('error'), msg, 'error');
      }
      this.closingYear = false;
    },
    async doReopenYear() {
      if (!this.store.currentClosing) return;
      this.reopening = true;
      try {
        await this.store.reopenYear(this.store.currentClosing.id);
        this.triggerSummarySnackbar(this.$t('reopen_year') + ' ✓', '', 'success');
        this.reopenDialog = false;
        // Reload current year (closing status + data) and preload next year (carry-forward propagation)
        await this.loadYear();
        this.store.fetchYearSummary(this.currentYear + 1);
        this.store.fetchBalance(this.currentYear + 1);
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error'), '', 'error');
      }
      this.reopening = false;
    },
    toggleMonth(m) {
      this.expandedMonths = { ...this.expandedMonths, [m]: !this.expandedMonths[m] };
    },
    isFutureMonth(monthNum) {
      if (this.currentYear < this.currentMonth.year) return false;
      if (this.currentYear > this.currentMonth.year) return true;
      return monthNum > this.currentMonth.month;
    },
    cumulativeBalance(monthNum) {
      // Carry forward from previous year + cumulative balance up to this month
      let total = this.totalCarryForward;
      for (let m = 1; m <= monthNum; m++) {
        total += this.monthTotals[m].balance;
      }
      return total;
    },
    viewInvoiceSource(inv) {
      this.$router.push({ name: 'view_supplier_invoice', query: { ids: [inv.id] } });
    },
    fmtDate(d) {
      if (!d) return '-';
      return this.main.formateDate(d);
    },
    paymentTypeLabel(type) {
      const found = this.paymentTypes.find(t => t.value === type);
      return found ? found.title : type;
    },
    supplierName(id) {
      return this.store.suppliers[id] || this.suppliers.find(s => s.id === id)?.name || '-';
    },
    exportExcel() {
      const wb = XLSX.utils.book_new();
      const t = (k) => this.$t(k);
      const fmt = (v) => Number(v || 0);
      const r2 = v => Math.round(v * 100) / 100;

      // Supplier name(s) for filename
      const supNames = this.selectedSupplierIds.length
        ? this.suppliers.filter(s => this.selectedSupplierIds.includes(s.id)).map(s => s.name).join('_')
        : 'all';

      // -- Sheet 1: Month × Supplier breakdown --
      const rows = [];
      for (let m = 1; m <= 12; m++) {
        const mLabel = this.months[m - 1].label;
        const invBySup = {}, payBySup = {};

        (this.invoicesByMonth[m] || []).forEach(inv => {
          const sId = inv.supplier_id;
          const sName = inv.supplier?.name || inv._supplierNames || '-';
          if (sId) { if (!invBySup[sId]) invBySup[sId] = { name: sName, total: 0 }; invBySup[sId].total += fmt(inv.total_price); }
        });
        (this.paymentsByMonth[m] || []).forEach(p => {
          if (!payBySup[p.supplier_id]) payBySup[p.supplier_id] = { name: p._supplierName, total: 0 };
          payBySup[p.supplier_id].total += fmt(p.amount);
        });

        // Build per-supplier carry forward for this month
        const cfBySup = {};
        (this.filteredYearSummary || []).forEach(s => {
          cfBySup[s.supplier_id] = Number(s.carry_forward || 0);
        });
        for (let pm = 1; pm < m; pm++) {
          const pInv = {}, pPay = {};
          (this.invoicesByMonth[pm] || []).forEach(inv => {
            const sId = inv.supplier_id;
            if (sId) { if (!pInv[sId]) pInv[sId] = 0; pInv[sId] += fmt(inv.total_price); }
          });
          (this.paymentsByMonth[pm] || []).forEach(p => {
            pPay[p.supplier_id] = (pPay[p.supplier_id] || 0) + fmt(p.amount);
          });
          new Set([...Object.keys(pInv), ...Object.keys(pPay)]).forEach(sid => {
            cfBySup[sid] = (cfBySup[sid] || 0) + (pInv[sid] || 0) - (pPay[sid] || 0);
          });
        }

        const allIds = new Set([...Object.keys(invBySup), ...Object.keys(payBySup), ...Object.keys(cfBySup)]);
        allIds.forEach(sid => {
          const cf = cfBySup[sid] || 0;
          const inv = invBySup[sid]?.total || 0;
          const pay = payBySup[sid]?.total || 0;
          const bal = cf + inv - pay;
          const name = invBySup[sid]?.name || payBySup[sid]?.name || this.suppliers.find(s => s.id == sid)?.name || '-';
          rows.push({
            [t('month')]: mLabel,
            [t('suppliers')]: name,
            [t('carry_forward')]: r2(cf),
            [t('supplier_invoices')]: r2(inv),
            [t('payments')]: r2(pay),
            [t('outstanding_balance')]: r2(bal),
          });
        });
      }
      // Totals row
      rows.push({
        [t('month')]: t('total'),
        [t('suppliers')]: '',
        [t('carry_forward')]: r2(this.totalCarryForward),
        [t('supplier_invoices')]: r2(this.totalInvoices),
        [t('payments')]: r2(this.totalPayments),
        [t('outstanding_balance')]: r2(this.totalBalance),
      });
      const sheetTitle = supNames !== 'all' ? supNames + ' - ' + this.currentYear : t('year_summary');
      XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(rows), sheetTitle.substring(0, 31));

      XLSX.writeFile(wb, `supplier_settlement_${supNames.replace(/\s+/g, '_')}_${this.currentYear}.xlsx`);
    },
    exportMonthExcel(monthNum, monthLabel) {
      const t = (k) => this.$t(k);
      const fmt = (v) => Number(v || 0);
      const mLabel = monthLabel + ' ' + this.currentYear;
      const mTotals = this.monthTotals[monthNum];
      const rows = [];

      // Invoice rows
      (this.invoicesByMonth[monthNum] || []).forEach(inv => {
        rows.push({
          [t('date')]: this.fmtDate(inv.date),
          [t('suppliers')]: inv._supplierNames,
          [t('invoice_tracking_number')]: inv._trackingStr,
          [t('weight')]: fmt(inv.total_weight),
          [t('price')]: fmt(inv.total_price),
          [t('payment_type')]: '',
          [t('payment_reference')]: '',
          [t('payment_amount')]: '',
        });
      });

      // Payment rows
      (this.paymentsByMonth[monthNum] || []).forEach(p => {
        rows.push({
          [t('date')]: this.fmtDate(p.date),
          [t('suppliers')]: p._supplierName,
          [t('invoice_tracking_number')]: '',
          [t('weight')]: '',
          [t('price')]: '',
          [t('payment_type')]: this.paymentTypeLabel(p.payment_type),
          [t('payment_reference')]: p.reference || '',
          [t('payment_amount')]: fmt(p.amount),
        });
      });

      // Totals row
      rows.push({
        [t('date')]: t('total'),
        [t('suppliers')]: '',
        [t('invoice_tracking_number')]: '',
        [t('weight')]: '',
        [t('price')]: fmt(mTotals.invoices),
        [t('payment_type')]: '',
        [t('payment_reference')]: '',
        [t('payment_amount')]: fmt(mTotals.payments),
      });

      const wb = XLSX.utils.book_new();
      const supNames = this.selectedSupplierIds.length
        ? this.suppliers.filter(s => this.selectedSupplierIds.includes(s.id)).map(s => s.name).join('_')
        : '';
      const sheetTitle = supNames ? supNames + ' - ' + mLabel : mLabel;
      XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(rows), sheetTitle.substring(0, 31));
      const fileSupNames = supNames || 'all';
      XLSX.writeFile(wb, `supplier_settlement_${fileSupNames.replace(/\s+/g, '_')}_${monthLabel}-${this.currentYear}.xlsx`);
    },
    openPrintCustomizer() {
      const active = this.activeMonthsList;
      if (active.length) {
        this.printOptions.monthMode = 'active';
        this.printOptions.selectedMonths = [...active];
        this.printOptions.preset = 'active';
      } else {
        this.printOptions.monthMode = 'all';
        this.printOptions.selectedMonths = Array.from({ length: 12 }, (_, i) => i + 1);
        this.printOptions.preset = 'full';
      }
      this.printOptionsDialog = true;
    },
    applyPrintPreset(preset) {
      this.printOptions.preset = preset;
      if (preset === 'full') {
        this.printOptions.monthMode = 'all';
        this.printOptions.selectedMonths = Array.from({ length: 12 }, (_, i) => i + 1);
        this.printOptions.includeHeader = true;
        this.printOptions.includeKpis = true;
        this.printOptions.includeInvoices = true;
        this.printOptions.includePayments = true;
        this.printOptions.includeYearSummary = true;
        this.printOptions.includeAnalytics = true;
        this.printOptions.includeSignatures = true;
      } else if (preset === 'active') {
        this.printOptions.monthMode = 'active';
        const active = this.activeMonthsList;
        this.printOptions.selectedMonths = active.length ? [...active] : [1];
        this.printOptions.includeHeader = true;
        this.printOptions.includeKpis = true;
        this.printOptions.includeInvoices = true;
        this.printOptions.includePayments = true;
        this.printOptions.includeYearSummary = true;
        this.printOptions.includeAnalytics = true;
        this.printOptions.includeSignatures = true;
      } else if (preset === 'summary') {
        this.printOptions.monthMode = 'all';
        this.printOptions.includeHeader = true;
        this.printOptions.includeKpis = true;
        this.printOptions.includeInvoices = false;
        this.printOptions.includePayments = false;
        this.printOptions.includeYearSummary = true;
        this.printOptions.includeAnalytics = true;
        this.printOptions.includeSignatures = true;
      } else if (preset === 'current') {
        const now = new Date();
        const curM = this.currentYear === now.getFullYear() ? (now.getMonth() + 1) : 1;
        this.printOptions.monthMode = 'custom';
        this.printOptions.selectedMonths = [curM];
        this.printOptions.includeHeader = true;
        this.printOptions.includeKpis = true;
        this.printOptions.includeInvoices = true;
        this.printOptions.includePayments = true;
        this.printOptions.includeYearSummary = false;
        this.printOptions.includeAnalytics = false;
        this.printOptions.includeSignatures = true;
      }
    },
    togglePrintMonth(mNum) {
      this.printOptions.monthMode = 'custom';
      this.printOptions.preset = 'custom';
      const idx = this.printOptions.selectedMonths.indexOf(mNum);
      if (idx > -1) {
        if (this.printOptions.selectedMonths.length > 1) {
          this.printOptions.selectedMonths.splice(idx, 1);
        }
      } else {
        this.printOptions.selectedMonths.push(mNum);
        this.printOptions.selectedMonths.sort((a, b) => a - b);
      }
    },
    isPrintMonthSelected(mNum) {
      if (this.printOptions.monthMode === 'all') return true;
      if (this.printOptions.monthMode === 'active') {
        return (this.invoicesByMonth[mNum]?.length > 0 || this.paymentsByMonth[mNum]?.length > 0);
      }
      return this.printOptions.selectedMonths.includes(mNum);
    },
    jumpToMonthInGrid(mNum) {
      this.activeTab = 'balance';
      this.expandedMonths = { ...this.expandedMonths, [mNum]: true };
      this.$nextTick(() => {
        const el = document.getElementById(`month-row-${mNum}`);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    },
    prepareSettlementPrint() {
      this.openPrintCustomizer();
    },
  },
};
