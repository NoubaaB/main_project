import { defineStore } from 'pinia';
import axios from 'axios';

export const useSupplierPayment = defineStore('supplierPayment', {
  state: () => ({
    payments: [],
    balance: [],
    suppliers: {},
    yearSummary: [],
    closings: [],
    currentClosing: null,
    carryForwardTotal: 0,
    currentYear: new Date().getFullYear(),
    loading: false,
    balanceLoading: false,
    summaryLoading: false,
  }),
  actions: {
    async fetchPayments(year) {
      this.loading = true;
      try {
        const r = await axios.get('/api/supplier_payments', { params: { year: year || this.currentYear } });
        this.payments = r.data.payments || [];
      } catch (e) { console.error(e) }
      this.loading = false;
    },
    async createPayment(data) {
      const r = await axios.post('/api/supplier_payments', data);
      const payment = r.data.payment;
      if (payment) this.payments.unshift(payment);
      return payment;
    },
    async updatePayment(id, data) {
      const r = await axios.put(`/api/supplier_payments/${id}`, data);
      const payment = r.data.payment;
      if (payment) {
        const idx = this.payments.findIndex(p => p.id === id);
        if (idx !== -1) this.payments.splice(idx, 1, payment);
        else this.payments.unshift(payment);
      }
      return payment;
    },
    async deletePayments(ids) {
      await axios.delete('/api/supplier_payments', { data: { ids } });
      this.payments = this.payments.filter(p => !ids.includes(p.id));
    },
    async fetchBalance(year) {
      this.balanceLoading = true;
      try {
        const r = await axios.get('/api/supplier_payments/balance', { params: { year: year || this.currentYear } });
        this.balance = r.data.balance || [];
        this.suppliers = r.data.suppliers || {};
        this.carryForwardTotal = r.data.carry_forward_total || 0;
      } catch (e) { console.error(e) }
      this.balanceLoading = false;
    },
    async fetchYearSummary(year) {
      this.summaryLoading = true;
      try {
        const r = await axios.get('/api/supplier_payments/year-summary', { params: { year: year || this.currentYear } });
        this.yearSummary = r.data.summary || [];
        this.currentClosing = r.data.closing || null;
      } catch (e) { console.error(e) }
      this.summaryLoading = false;
    },
    async fetchClosings() {
      try {
        const r = await axios.get('/api/fiscal_year_closings');
        this.closings = r.data.closings || [];
      } catch (e) { console.error(e) }
    },
    async closeYear(year) {
      const r = await axios.post('/api/fiscal_year_closings', { year });
      return r.data.closing;
    },
    async reopenYear(id) {
      const r = await axios.put(`/api/fiscal_year_closings/${id}/reopen`);
      return r.data.closing;
    },
    async refreshAll(year) {
      const y = year || this.currentYear;
      await Promise.all([
        this.fetchPayments(y),
        this.fetchBalance(y),
        this.fetchYearSummary(y),
        this.fetchClosings(),
      ]);
    },
  },
});
