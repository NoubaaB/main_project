import { useMain } from '@/stores/Main';
import { useSupplierPayment } from '@/stores/SupplierPayment';
import { useSupplier } from '@/stores/Supplier';
import { useBankName } from '@/stores/BankName';
import mitt from '@/plugins/mitt.js';
import axios from 'axios';
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
      // Payments table
      selected: [],
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
      bankNameSearch: '',
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
    chartSeries() {
      const invByMonth = Array(12).fill(null);
      const payByMonth = Array(12).fill(null);
      const balByMonth = Array(12).fill(null);
      let running = this.totalCarryForward;
      const now = new Date();
      const curYear = now.getFullYear();
      const curMonth = now.getMonth() + 1;
      // For the current year show up to current month; for past years show all 12; for future years show none
      const maxMonth = this.currentYear < curYear ? 12 : (this.currentYear === curYear ? curMonth : 0);
      for (let m = 1; m <= 12; m++) {
        if (m <= maxMonth) {
          invByMonth[m - 1] = this.analyticsMonthTotals[m].invoices;
          payByMonth[m - 1] = this.analyticsMonthTotals[m].payments;
          running += this.analyticsMonthTotals[m].balance;
          balByMonth[m - 1] = running;
        } else {
          running += this.analyticsMonthTotals[m].balance;
        }
      }
      const r = v => v === null ? null : Math.round(v * 100) / 100;
      return [
        { name: this.$t('supplier_invoices'), type: 'bar', data: invByMonth.map(r) },
        { name: this.$t('payments'), type: 'bar', data: payByMonth.map(r) },
        { name: this.$t('outstanding_balance'), type: 'line', data: balByMonth.map(r) },
      ];
    },
    chartOptions() {
      return {
        chart: { type: 'line', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
        stroke: { width: [0, 0, 3], curve: 'smooth' },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
        colors: ['#F57C00', '#26A69A', '#E53935'],
        fill: { opacity: [1, 1, 1] },
        markers: { size: [0, 0, 4], colors: ['#E53935'], strokeWidth: 0 },
        xaxis: { categories: this.months.map(m => m.short), labels: { style: { fontSize: '11px', fontWeight: 600 } } },
        yaxis: { labels: { style: { fontSize: '10px' }, formatter: v => this.main.formatDisplayNumber(v) } },
        tooltip: { shared: true, intersect: false, custom: ({ series, dataPointIndex, w }) => {
          if (series.every(s => s[dataPointIndex] == null)) return '<span></span>';
          const cat = w.globals.categoryLabels[dataPointIndex] || '';
          let h = '<div class="apexcharts-tooltip-title" style="font-family:Inter,sans-serif;font-size:12px">' + cat + '</div>';
          series.forEach((s, i) => { if (s[dataPointIndex] == null) return; const c = w.globals.colors[i]; const n = w.globals.seriesNames[i]; const v = this.main.formatDisplayNumber(s[dataPointIndex]) + ' ' + this.main.currency; h += '<div style="display:flex;align-items:center;gap:6px;padding:3px 8px"><span style="width:10px;height:10px;border-radius:50%;background:' + c + ';display:inline-block"></span><span style="font-size:12px">' + n + ': <b>' + v + '</b></span></div>'; });
          return h;
        }},
        grid: { borderColor: 'rgba(0,0,0,.06)', strokeDashArray: 4 },
        legend: { position: 'top', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: false },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
  },
  mounted() {
    this.loadYear();
    this.bankNameStore.fetchAll();
    mitt.on('model-changed', this.onMC);
  },
  activated() { this.loadYear(); },
  beforeUnmount() { mitt.off('model-changed', this.onMC) },
  methods: {
    onMC(d) {
      if (d.model === 'SupplierPayment') {
        // Another user changed a payment - refresh payments + summary only
        this.store.fetchPayments(this.currentYear);
        this.store.fetchYearSummary(this.currentYear);
      } else if (d.model === 'SupplierInvoice') {
        // Invoice changed - refresh invoices + summary
        this.fetchYearInvoices();
        this.store.fetchYearSummary(this.currentYear);
      } else if (d.model === 'FiscalYearClosing') {
        // Year open/close status changed
        this.store.fetchYearSummary(this.currentYear);
        this.store.fetchClosings();
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
      try {
        const r = await axios.get('/api/supplier_invoice', {
          params: { date_start: `${this.currentYear}-01-01`, date_end: `${this.currentYear}-12-31` },
        });
        this.yearInvoices = r.data.supplier_invoices || [];
      } catch (e) { console.error(e) }
      this.yearInvoicesLoading = false;
    },
    async fetchSupplierInvoices() {
      if (!this.payForm.supplier_id) { this.supplierInvoices = []; return; }
      try {
        const r = await axios.get('/api/supplier_invoice', {
          params: {
            date_start: `${this.currentYear}-01-01`,
            date_end: `${this.currentYear}-12-31`,
          }
        });
        this.supplierInvoices = (r.data.supplier_invoices || []).filter(inv => {
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
        if (this.editId) {
          await this.store.updatePayment(this.editId, this.payForm);
          this.main.add_snackbar({ show: true, message: this.$t('payment_updated'), color: 'success' });
        } else {
          await this.store.createPayment(this.payForm);
          this.main.add_snackbar({ show: true, message: this.$t('payment_created'), color: 'success' });
        }
        this.showPayForm = false;
        this.resetPayForm();
        // Only refresh summary cards (lightweight) - payments array already updated in store
        this.store.fetchYearSummary(this.currentYear);
      } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || this.$t('error');
        this.main.add_snackbar({ show: true, message: msg, color: 'error' });
      }
      this.saving = false;
    },
    confirmDelete(id) { this.deleteId = id; this.deleteBulk = false; this.deleteDialog = true },
    confirmBulkDelete() { this.deleteBulk = true; this.deleteDialog = true },
    async execDelete() {
      this.deleting = true;
      try {
        const ids = this.deleteBulk ? this.selected : [this.deleteId];
        await this.store.deletePayments(ids);
        this.main.add_snackbar({ show: true, message: this.$t('payment_deleted'), color: 'success' });
        this.selected = [];
        this.deleteDialog = false;
        // Only refresh summary cards - payments array already updated in store
        this.store.fetchYearSummary(this.currentYear);
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('error'), color: 'error' });
      }
      this.deleting = false;
    },
    async doCloseYear() {
      this.closingYear = true;
      try {
        await this.store.closeYear(this.currentYear);
        this.main.add_snackbar({ show: true, message: this.$t('year_closed_success', { year: this.currentYear }), color: 'success' });
        this.closeYearDialog = false;
        // Reload current year (closing status + data) and preload next year (carry-forward propagation)
        await this.loadYear();
        this.store.fetchYearSummary(this.currentYear + 1);
        this.store.fetchBalance(this.currentYear + 1);
      } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || this.$t('error');
        this.main.add_snackbar({ show: true, message: msg, color: 'error' });
      }
      this.closingYear = false;
    },
    async doReopenYear() {
      if (!this.store.currentClosing) return;
      this.reopening = true;
      try {
        await this.store.reopenYear(this.store.currentClosing.id);
        this.main.add_snackbar({ show: true, message: this.$t('reopen_year') + ' ✓', color: 'success' });
        this.reopenDialog = false;
        // Reload current year (closing status + data) and preload next year (carry-forward propagation)
        await this.loadYear();
        this.store.fetchYearSummary(this.currentYear + 1);
        this.store.fetchBalance(this.currentYear + 1);
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('error'), color: 'error' });
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
  },
};
