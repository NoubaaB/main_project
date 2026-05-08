<template>
  <div class="ot-add-form">
    <!-- Title -->
    <div class="d-flex align-center mb-4">
      <v-icon color="#00897B" class="mr-2">{{ editData ? 'mdi-pencil-outline' : 'mdi-plus-circle-outline' }}</v-icon>
      <span class="text-h6 font-weight-bold">{{ editData ? $t('edit_order_transfer') : $t('add_order_transfer') }}</span>
      <v-spacer></v-spacer>
      <v-btn v-if="editData" variant="text" color="red-lighten-1" prepend-icon="mdi-close" rounded @click="$emit('cancel-edit')">{{ $t('cancel') }}</v-btn>
    </div>

    <v-row dense>
      <!-- Date -->
      <v-col cols="12" md="3">
        <DatePicker
          v-model="dateModel"
          :is-dark="main.is_dark"
          :locale="main.lang?.short || 'en'"
          mode="date"
          color="teal"
          :max-date="new Date()"
        >
          <template #default="{ togglePopover, inputValue }">
            <v-text-field
              :model-value="inputValue"
              :label="$t('date')"
              density="compact" variant="outlined" rounded
              color="#00897B" class="text-ot"
              hide-details
              readonly
              prepend-inner-icon="mdi-calendar"
              @click="togglePopover"
              :rules="[v => !!v || $t('field_required')]"
            ></v-text-field>
          </template>
        </DatePicker>
      </v-col>

      <!-- Amount -->
      <v-col cols="12" md="3">
        <v-text-field
          v-model.number="form.amount"
          :label="$t('amount')"
          type="number"
          min="0" step="0.01"
          density="compact" variant="outlined" rounded
          color="#00897B" class="text-ot"
          hide-details
          :prefix="currencySymbol"
          :rules="[v => v > 0 || $t('field_required')]"
        ></v-text-field>
      </v-col>

      <!-- Currency -->
      <v-col cols="12" md="3">
        <v-select
          v-model="form.currency"
          :items="currencyOptions"
          :label="$t('currency')"
          density="compact" variant="outlined" rounded
          color="#00897B" class="text-ot"
          hide-details
        ></v-select>
      </v-col>

      <!-- Beneficiaries (merged suppliers + clients) -->
      <v-col cols="12" md="3">
        <v-autocomplete
          v-model="form.beneficiaries"
          :items="mergedProviders"
          item-title="label"
          item-value="key"
          :label="$t('beneficiaries')"
          multiple chips closable-chips
          density="compact" variant="outlined" rounded
          color="#00897B" class="text-ot"
          hide-details
          :rules="[v => v.length > 0 || $t('field_required')]"
        >
          <template #chip="{ item, props: p }">
            <v-chip v-bind="p" :color="item.raw.type === 'supplier' ? '#E65100' : '#00838F'" variant="tonal" closable>
              <v-icon start size="14">{{ item.raw.type === 'supplier' ? 'mdi-truck' : 'mdi-account' }}</v-icon>
              {{ item.raw.name }}
            </v-chip>
          </template>
          <template #item="{ item, props: p }">
            <v-list-item v-bind="p">
              <template #prepend>
                <v-icon size="18" :color="item.raw.type === 'supplier' ? '#E65100' : '#00838F'">{{ item.raw.type === 'supplier' ? 'mdi-truck' : 'mdi-account' }}</v-icon>
              </template>
              <v-list-item-subtitle class="text-caption">{{ item.raw.type === 'supplier' ? $t('supplier') : $t('client') }}</v-list-item-subtitle>
            </v-list-item>
          </template>
        </v-autocomplete>
      </v-col>
    </v-row>

    <!-- Motif -->
    <v-row dense class="mt-2">
      <v-col cols="12">
        <v-textarea
          v-model="form.motif"
          :label="$t('motif')"
          :placeholder="$t('motif_placeholder')"
          density="compact" variant="outlined" rounded
          color="#00897B" class="text-ot"
          rows="3" auto-grow
          counter="500" maxlength="500"
          hide-details="auto"
        ></v-textarea>
      </v-col>
    </v-row>

    <!-- Submit -->
    <div class="d-flex justify-end mt-4 ga-2">
      <v-btn
        color="#00897B" rounded
        :prepend-icon="editData ? 'mdi-content-save-outline' : 'mdi-send'"
        :loading="saving"
        @click="submit"
        :disabled="!isValid"
      >
        {{ editData ? $t('update') : $t('submit') }}
      </v-btn>
    </div>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="summarySnackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420">
      <v-progress-linear :model-value="snackbarProgress" color="#00897B" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="summarySnackbar.isError ? 'error' : 'success'" class="mr-2">{{ summarySnackbar.isError ? 'mdi-alert-circle' : 'mdi-check-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.number"><v-icon size="14" color="#00897B" class="mr-1">mdi-file-document</v-icon><span class="text-medium-emphasis">{{ $t('transfer_number') }}:</span> <strong>{{ summarySnackbar.number }}</strong></div>
        <div v-if="summarySnackbar.amount"><v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('amount') }}:</span> <strong class="text-success">{{ summarySnackbar.amount }}</strong></div>
        <div v-if="summarySnackbar.beneficiaries"><v-icon size="14" color="#00897B" class="mr-1">mdi-account-multiple</v-icon><span class="text-medium-emphasis">{{ $t('beneficiaries') }}:</span> <strong>{{ summarySnackbar.beneficiaries }}</strong></div>
      </div>
      <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn></template>
    </v-snackbar>
  </div>
</template>

<script>
import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import { useSupplier } from '@/stores/Supplier';
import { useClient } from '@/stores/Client';
import { DatePicker } from 'v-calendar';

export default {
  name: 'AddOrderTransfer',
  components: { DatePicker },
  props: {
    editData: { type: Object, default: null },
  },
  emits: ['saved', 'cancel-edit'],
  data() {
    return {
      form: {
        date: new Date().toISOString().slice(0, 10),
        amount: null,
        currency: 'MAD',
        motif: '',
        beneficiaries: [],
      },
      saving: false,
      summarySnackbar: { show: false, title: '', number: '', amount: '', beneficiaries: '', isError: false },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    supplierStore() { return useSupplier(); },
    clientStore() { return useClient(); },
    currencyOptions() {
      return [
        { title: 'MAD (DH)', value: 'MAD' },
        { title: 'EUR (€)', value: 'EUR' },
        { title: 'USD ($)', value: 'USD' },
      ];
    },
    currencySymbol() {
      switch (this.form.currency) {
        case 'EUR': return '€';
        case 'USD': return '$';
        default: return 'DH';
      }
    },
    mergedProviders() {
      const suppliers = (this.supplierStore.list || []).map(s => ({
        key: `supplier-${s.id}`,
        label: `${s.name} (${this.$t('supplier')})`,
        name: s.name,
        type: 'supplier',
        id: s.id,
      }));
      const clients = (this.clientStore.list || []).map(c => ({
        key: `client-${c.id}`,
        label: `${c.name} (${this.$t('client')})`,
        name: c.name,
        type: 'client',
        id: c.id,
      }));
      return [...suppliers, ...clients];
    },
    isValid() {
      return this.form.date && this.form.amount > 0 && this.form.beneficiaries.length > 0;
    },
    dateModel: {
      get() {
        if (!this.form.date) return null;
        return new Date(this.form.date + 'T00:00:00');
      },
      set(val) {
        if (val instanceof Date) {
          const y = val.getFullYear();
          const m = String(val.getMonth() + 1).padStart(2, '0');
          const d = String(val.getDate()).padStart(2, '0');
          this.form.date = `${y}-${m}-${d}`;
        } else {
          this.form.date = val;
        }
      },
    },
  },
  watch: {
    editData: {
      immediate: true,
      handler(val) {
        if (val) {
          this.form.date = val.date ? val.date.slice(0, 10) : new Date().toISOString().slice(0, 10);
          this.form.amount = Number(val.amount);
          this.form.currency = val.currency || 'MAD';
          this.form.motif = val.motif || '';
          // Rebuild beneficiaries keys
          this.form.beneficiaries = (val.beneficiary_names || val.beneficiaries || []).map(b => `${b.type || b.beneficiary_type}-${b.id || b.beneficiary_id}`);
        } else {
          this.resetForm();
        }
      },
    },
  },
  methods: {
    resetForm() {
      this.form = { date: new Date().toISOString().slice(0, 10), amount: null, currency: 'MAD', motif: '', beneficiaries: [] };
    },
    async submit() {
      if (!this.isValid) return;
      this.saving = true;
      try {
        const beneficiaries = this.form.beneficiaries.map(key => {
          const [type, id] = key.split('-');
          return { type, id: parseInt(id) };
        });
        const payload = { ...this.form, beneficiaries };

        let res;
        if (this.editData) {
          res = await axios.put(`/api/order-transfers/${this.editData.id}`, payload);
        } else {
          res = await axios.post('/api/order-transfers', payload);
        }

        const ot = res.data.order_transfer;
        const benNames = (ot.beneficiary_names || []).map(b => b.name).join(', ');
        this.showSnackbar(false,
          this.editData ? this.$t('order_transfer_updated') : this.$t('order_transfer_created'),
          ot.transfer_number,
          `${this.main.formatDisplayNumber(ot.amount)} ${ot.currency}`,
          benNames
        );
        this.resetForm();
        this.$emit('saved');
      } catch (e) {
        console.error(e);
        this.showSnackbar(true, this.$t('error_occurred'), '', '', '');
      }
      this.saving = false;
    },
    showSnackbar(isError, title, number, amount, beneficiaries) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, isError, title, number, amount, beneficiaries };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => { this.snackbarProgress -= 1.25; if (this.snackbarProgress <= 0) clearInterval(this.snackbarTimer); }, 100);
    },
  },
  beforeUnmount() { clearInterval(this.snackbarTimer); },
};
</script>

<style scoped>
.ot-add-form { font-family: 'Inter', sans-serif; }
.text-ot { color: #00897B !important; }
</style>
