<template>
  <v-dialog v-model="dialogModel" max-width="850" persistent scrollable>
    <v-card class="rounded-xl qc-dialog-card">
      <!-- Hero Header -->
      <div class="qc-hero">
        <div class="qc-hero-inner">
          <div class="d-flex align-center ga-4">
            <div class="qc-hero-icon">
              <v-icon size="28" color="white">mdi-clipboard-check-outline</v-icon>
            </div>
            <div>
              <div class="qc-hero-title">{{ isEditing ? $t('edit_quality_control') : $t('add_quality_control') }}</div>
              <div class="text-white text-caption" style="opacity:.7">{{ chargeLabel }}</div>
            </div>
          </div>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" color="white" @click="close" size="small">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <v-card-text class="pa-5" style="max-height: 65vh; overflow-y: auto;">
        <!-- General Info -->
        <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
          <v-icon color="#00838F" class="mr-2" size="20">mdi-information-outline</v-icon>
          {{ $t('qc_general_info') }}
        </div>
        <v-row dense>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.inspector_name"
              :label="$t('qc_inspector')"
              variant="outlined" density="compact" rounded
              color="#00838F" class="text-qc"
              prepend-inner-icon="mdi-account-hard-hat"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.inspection_date"
              :label="$t('qc_inspection_date')"
              type="date"
              variant="outlined" density="compact" rounded
              color="#00838F" class="text-qc"
              prepend-inner-icon="mdi-calendar"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.temperature"
              :label="$t('qc_temperature')"
              type="number"
              step="0.01"
              variant="outlined" density="compact" rounded
              color="#00838F" class="text-qc"
              prepend-inner-icon="mdi-thermometer"
              suffix="°C"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.status"
              :items="statusOptions"
              :label="$t('qc_overall_status')"
              variant="outlined" density="compact" rounded
              color="#00838F" class="text-qc"
              prepend-inner-icon="mdi-shield-check-outline"
              hide-details
            >
              <template #selection="{ item }">
                <v-chip :color="getStatusColor(item.value)" size="small" variant="tonal">
                  <v-icon size="14" class="mr-1">{{ getStatusIcon(item.value) }}</v-icon>
                  {{ item.title }}
                </v-chip>
              </template>
            </v-select>
          </v-col>
          <v-col cols="12">
            <v-textarea
              v-model="form.notes"
              :label="$t('qc_notes')"
              variant="outlined" density="compact" rounded
              color="#00838F" class="text-qc"
              rows="2"
              hide-details
            ></v-textarea>
          </v-col>
        </v-row>

        <v-divider class="my-5" color="#00838F"></v-divider>

        <!-- Check Items -->
        <div class="d-flex align-center mb-3">
          <v-icon color="#00838F" class="mr-2" size="20">mdi-format-list-checks</v-icon>
          <span class="text-subtitle-2 font-weight-bold">{{ $t('qc_check_items') }}</span>
          <v-spacer></v-spacer>
          <v-btn size="small" variant="tonal" color="#00838F" rounded prepend-icon="mdi-plus" @click="addItem">
            {{ $t('qc_add_check_item') }}
          </v-btn>
        </div>

        <div v-for="(item, idx) in form.items" :key="idx" class="qc-item-card mb-3">
          <v-row dense align="center">
            <v-col cols="12" sm="4">
              <v-combobox
                v-model="item.check_name"
                :items="presetCheckNames"
                :label="$t('qc_check_name')"
                variant="outlined" density="compact" rounded
                color="#00838F" class="text-qc"
                hide-details
              ></v-combobox>
            </v-col>
            <v-col cols="6" sm="2">
              <v-select
                v-model="item.result"
                :items="resultOptions"
                :label="$t('qc_result')"
                variant="outlined" density="compact" rounded
                color="#00838F" class="text-qc"
                hide-details
              >
                <template #selection="{ item: sel }">
                  <v-icon :color="getResultColor(sel.value)" size="18">{{ getResultIcon(sel.value) }}</v-icon>
                </template>
              </v-select>
            </v-col>
            <v-col cols="6" sm="3">
              <v-text-field
                v-model="item.value"
                :label="$t('qc_value')"
                variant="outlined" density="compact" rounded
                color="#00838F" class="text-qc"
                hide-details
              ></v-text-field>
            </v-col>
            <v-col cols="10" sm="2">
              <v-text-field
                v-model="item.notes"
                :label="$t('qc_notes')"
                variant="outlined" density="compact" rounded
                color="#00838F" class="text-qc"
                hide-details
              ></v-text-field>
            </v-col>
            <v-col cols="2" sm="1" class="text-center">
              <v-btn icon size="x-small" variant="tonal" color="error" @click="removeItem(idx)">
                <v-icon size="16">mdi-delete</v-icon>
              </v-btn>
            </v-col>
          </v-row>
        </div>

        <div v-if="form.items.length === 0" class="text-center text-grey-darken-1 py-6">
          <v-icon size="48" color="grey-lighten-1">mdi-clipboard-text-off-outline</v-icon>
          <div class="mt-2 text-body-2">{{ $t('qc_no_items_yet') }}</div>
        </div>
      </v-card-text>

      <v-card-actions class="pa-5 pt-2">
        <v-btn v-if="isEditing" color="error" variant="tonal" rounded prepend-icon="mdi-delete" @click="confirmDeleteDialog = true" :disabled="saving">
          {{ $t('delete') }}
        </v-btn>
        <v-btn v-if="isEditing" v-print="printConfig" color="#00838F" variant="tonal" rounded prepend-icon="mdi-printer" :disabled="saving">
          {{ $t('print') }}
        </v-btn>
        <v-spacer></v-spacer>
        <v-btn @click="close" variant="text">{{ $t('cancel') }}</v-btn>
        <v-btn color="#00838F" variant="flat" rounded @click="save" :loading="saving" :disabled="!isValid">
          {{ $t('save') }}
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Delete Confirm -->
    <v-dialog v-model="confirmDeleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('qc_confirm_delete_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-dialog>

  <!-- Summary Snackbar -->
  <v-snackbar v-model="snackbar.show" :timeout="4000" :color="snackbar.color" rounded="lg" location="top right" elevation="12">
    <div class="d-flex align-center">
      <v-icon class="mr-2">{{ snackbar.icon }}</v-icon>
      <span class="text-subtitle-2 font-weight-medium">{{ snackbar.message }}</span>
    </div>
    <template #actions>
      <v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false"></v-btn>
    </template>
  </v-snackbar>

  <!-- Hidden Printable QC Report -->
  <div style="display:none">
    <div id="qc-print-area">
      <div class="qc-print-page">
        <!-- Header with logo -->
        <div class="qc-print-header">
          <div class="qc-print-header-left">
            <img :src="logoUrl" alt="Logo" class="qc-print-logo" />
          </div>
          <div class="qc-print-header-center">
            <h1 class="qc-print-title">{{ $t('qc_report_title') }}</h1>
            <p class="qc-print-subtitle">{{ $t('quality_control') }} — {{ chargeLabel }}</p>
          </div>
          <div class="qc-print-header-right">
            <div class="qc-print-doc-info">
              <span class="qc-print-label">{{ $t('date') }}:</span>
              {{ new Date().toLocaleDateString() }}
            </div>
            <div class="qc-print-doc-info">
              <span class="qc-print-label">QC #{{ existingQcId }}</span>
            </div>
          </div>
        </div>

        <div class="qc-print-divider"></div>

        <!-- General Info Section -->
        <div class="qc-print-section">
          <div class="qc-print-section-title">
            <span class="qc-print-section-icon">&#9432;</span>
            {{ $t('qc_general_info') }}
          </div>
          <table class="qc-print-info-table">
            <tr>
              <td class="qc-print-info-label">{{ $t('qc_inspector') }}</td>
              <td class="qc-print-info-value">{{ form.inspector_name || '-' }}</td>
              <td class="qc-print-info-label">{{ $t('qc_inspection_date') }}</td>
              <td class="qc-print-info-value">{{ form.inspection_date || '-' }}</td>
            </tr>
            <tr>
              <td class="qc-print-info-label">{{ $t('qc_temperature') }}</td>
              <td class="qc-print-info-value">{{ form.temperature != null ? form.temperature + '°C' : '-' }}</td>
              <td class="qc-print-info-label">{{ $t('qc_overall_status') }}</td>
              <td class="qc-print-info-value">
                <span :class="'qc-print-status qc-print-status-' + form.status">{{ getStatusLabel(form.status) }}</span>
              </td>
            </tr>
            <tr v-if="form.notes">
              <td class="qc-print-info-label">{{ $t('qc_notes') }}</td>
              <td class="qc-print-info-value" colspan="3">{{ form.notes }}</td>
            </tr>
          </table>
        </div>

        <!-- Check Items Section -->
        <div class="qc-print-section">
          <div class="qc-print-section-title">
            <span class="qc-print-section-icon">&#10003;</span>
            {{ $t('qc_check_items') }} ({{ form.items.length }})
          </div>
          <table class="qc-print-items-table">
            <thead>
              <tr>
                <th>#</th>
                <th>{{ $t('qc_check_name') }}</th>
                <th>{{ $t('qc_result') }}</th>
                <th>{{ $t('qc_value') }}</th>
                <th>{{ $t('qc_notes') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in form.items" :key="idx" :class="{'qc-print-row-fail': item.result === 'fail'}">
                <td>{{ idx + 1 }}</td>
                <td>{{ item.check_name || '-' }}</td>
                <td>
                  <span :class="'qc-print-result-' + item.result">{{ getResultLabel(item.result) }}</span>
                </td>
                <td>{{ item.value || '-' }}</td>
                <td>{{ item.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="qc-print-summary">
          <div class="qc-print-summary-item">
            <span class="qc-print-summary-count qc-print-pass">{{ passCount }}</span>
            <span class="qc-print-summary-label">{{ $t('qc_pass') }}</span>
          </div>
          <div class="qc-print-summary-item">
            <span class="qc-print-summary-count qc-print-fail">{{ failCount }}</span>
            <span class="qc-print-summary-label">{{ $t('qc_fail') }}</span>
          </div>
          <div class="qc-print-summary-item">
            <span class="qc-print-summary-count qc-print-na">{{ naCount }}</span>
            <span class="qc-print-summary-label">{{ $t('qc_n_a') }}</span>
          </div>
        </div>

        <!-- Footer -->
        <div class="qc-print-footer">
          <div class="qc-print-footer-left">
            {{ $t('qc_report_title') }} — {{ chargeLabel }}
          </div>
          <div class="qc-print-footer-right">
            {{ $t('qc_printed_on') }}: {{ new Date().toLocaleString() }}
          </div>
        </div>

        <!-- Signatures -->
        <div class="qc-print-signatures">
          <div class="qc-print-sig-box">
            <div class="qc-print-sig-line"></div>
            <div class="qc-print-sig-label">{{ $t('qc_inspector') }}</div>
          </div>
          <div class="qc-print-sig-box">
            <div class="qc-print-sig-line"></div>
            <div class="qc-print-sig-label">{{ $t('qc_supervisor_signature') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import print from 'vue3-print-nb';

export default {
  name: 'QualityControlDialog',
  directives: { print },
  props: {
    modelValue: { type: Boolean, default: false },
    charge: { type: Object, default: null },
  },
  emits: ['update:modelValue', 'saved', 'deleted'],
  data() {
    return {
      form: this.emptyForm(),
      saving: false,
      deleting: false,
      existingQcId: null,
      confirmDeleteDialog: false,
      snackbar: { show: false, message: '', color: 'success', icon: 'mdi-check-circle' },
      printConfig: {
        id: 'qc-print-area',
        popTitle: 'Quality Control Report',
      },
      presetCheckNames: [
        this.$t('qc_temperature'),
        this.$t('qc_appearance'),
        this.$t('qc_freshness'),
        this.$t('qc_packaging_integrity'),
        this.$t('qc_weight_accuracy'),
        this.$t('qc_odor'),
      ],
      statusOptions: [
        { title: this.$t('qc_passed'), value: 'passed' },
        { title: this.$t('qc_failed'), value: 'failed' },
        { title: this.$t('qc_pending'), value: 'pending' },
      ],
      resultOptions: [
        { title: this.$t('qc_pass'), value: 'pass' },
        { title: this.$t('qc_fail'), value: 'fail' },
        { title: this.$t('qc_n_a'), value: 'n_a' },
      ],
    };
  },
  computed: {
    main() { return useMain(); },
    logoUrl() { return new URL('/storage/images/logo_login.png', window.location.origin).href; },
    dialogModel: {
      get() { return this.modelValue; },
      set(v) { this.$emit('update:modelValue', v); },
    },
    isEditing() { return !!this.existingQcId; },
    isValid() {
      return this.form.inspection_date && this.form.status && this.form.items.length > 0 &&
        this.form.items.every(i => i.check_name && i.result);
    },
    chargeLabel() {
      if (!this.charge) return '';
      const cat = this.charge.charge_category?.name || '';
      const date = this.charge.date ? new Date(this.charge.date).toLocaleDateString() : '';
      return `${cat} — ${date}`;
    },
    passCount() { return this.form.items.filter(i => i.result === 'pass').length; },
    failCount() { return this.form.items.filter(i => i.result === 'fail').length; },
    naCount() { return this.form.items.filter(i => i.result === 'n_a').length; },
  },
  watch: {
    modelValue(v) {
      if (v && this.charge) {
        this.loadExistingQc();
      }
    },
  },
  methods: {
    emptyForm() {
      return {
        inspector_name: '',
        inspection_date: new Date().toISOString().substr(0, 10),
        status: 'pending',
        temperature: null,
        notes: '',
        items: [
          { check_name: '', result: 'n_a', value: '', notes: '' },
          { check_name: '', result: 'n_a', value: '', notes: '' },
          { check_name: '', result: 'n_a', value: '', notes: '' },
        ],
      };
    },
    addItem() {
      this.form.items.push({ check_name: '', result: 'n_a', value: '', notes: '' });
    },
    removeItem(idx) {
      this.form.items.splice(idx, 1);
    },
    async loadExistingQc() {
      if (!this.charge?.id) return;
      try {
        const res = await axios.get(`/api/quality-control/${this.charge.id}`);
        const qc = res.data.quality_control;
        if (qc) {
          this.existingQcId = qc.id;
          this.form = {
            inspector_name: qc.inspector_name || '',
            inspection_date: qc.inspection_date ? qc.inspection_date.substring(0, 10) : '',
            status: qc.status,
            temperature: qc.temperature,
            notes: qc.notes || '',
            items: (qc.items || []).map(i => ({
              check_name: i.check_name,
              result: i.result,
              value: i.value || '',
              notes: i.notes || '',
            })),
          };
        } else {
          this.existingQcId = null;
          this.form = this.emptyForm();
        }
      } catch (e) {
        console.error(e);
        this.existingQcId = null;
        this.form = this.emptyForm();
      }
    },
    async save() {
      this.saving = true;
      try {
        const payload = { ...this.form };
        if (!this.isEditing) {
          payload.charge_id = this.charge.id;
          const res = await axios.post('/api/quality-control', payload);
          this.existingQcId = res.data.quality_control.id;
          this.showSnackbar(this.$t('qc_created'), 'success', 'mdi-check-circle');
        } else {
          await axios.put(`/api/quality-control/${this.existingQcId}`, payload);
          this.showSnackbar(this.$t('qc_updated'), 'success', 'mdi-check-circle');
        }
        this.$emit('saved');
        this.close();
      } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || this.$t('qc_save_error');
        this.showSnackbar(msg, 'error', 'mdi-alert-circle');
      }
      this.saving = false;
    },
    async executeDelete() {
      this.deleting = true;
      try {
        await axios.delete(`/api/quality-control/${this.existingQcId}`);
        this.showSnackbar(this.$t('qc_deleted'), 'success', 'mdi-check-circle');
        this.$emit('deleted');
        this.confirmDeleteDialog = false;
        this.close();
      } catch (e) {
        console.error(e);
        this.showSnackbar(this.$t('qc_delete_error'), 'error', 'mdi-alert-circle');
      }
      this.deleting = false;
    },
    showSnackbar(message, color, icon) {
      this.snackbar = { show: true, message, color, icon };
      // Also notify other users via main store
      this.main.add_snackbar({ show: true, message, color });
    },
    close() {
      this.dialogModel = false;
      this.existingQcId = null;
      this.form = this.emptyForm();
    },
    getStatusColor(status) {
      return { passed: 'success', failed: 'error', pending: 'warning' }[status] || 'grey';
    },
    getStatusIcon(status) {
      return { passed: 'mdi-check-circle', failed: 'mdi-close-circle', pending: 'mdi-clock-outline' }[status] || 'mdi-help-circle';
    },
    getResultColor(result) {
      return { pass: 'success', fail: 'error', n_a: 'grey' }[result] || 'grey';
    },
    getResultIcon(result) {
      return { pass: 'mdi-check-circle', fail: 'mdi-close-circle', n_a: 'mdi-minus-circle-outline' }[result] || 'mdi-help-circle';
    },
    getStatusLabel(status) {
      const map = { passed: this.$t('qc_passed'), failed: this.$t('qc_failed'), pending: this.$t('qc_pending') };
      return map[status] || status;
    },
    getResultLabel(result) {
      const map = { pass: this.$t('qc_pass'), fail: this.$t('qc_fail'), n_a: this.$t('qc_n_a') };
      return map[result] || result;
    },
  },
};
</script>

<style scoped>
.qc-dialog-card {
  font-family: 'Inter', sans-serif;
}
.qc-hero {
  background: linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
.qc-hero-inner {
  display: flex;
  align-items: center;
  padding: 18px 22px;
  color: #fff;
}
.qc-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255,255,255,.15);
}
.qc-hero-title {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: .3px;
}
.qc-item-card {
  border: 1px solid rgba(0, 131, 143, 0.15);
  border-radius: 12px;
  padding: 12px 14px;
  background: rgba(0, 131, 143, 0.02);
  transition: all 0.2s ease;
}
.qc-item-card:hover {
  border-color: rgba(0, 131, 143, 0.3);
  background: rgba(0, 131, 143, 0.04);
}
</style>

<!-- Print-only styles (non-scoped for print popup) -->
<style>
#qc-print-area {
  font-family: 'Segoe UI', 'Inter', Arial, sans-serif;
  color: #1a1a1a;
  font-size: 13px;
  line-height: 1.5;
}
.qc-print-page {
  padding: 24px 32px;
  max-width: 800px;
  margin: 0 auto;
}
/* Header */
.qc-print-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.qc-print-header-left { flex: 0 0 auto; }
.qc-print-logo { height: 52px; width: auto; }
.qc-print-header-center { text-align: center; flex: 1; }
.qc-print-title {
  font-size: 22px;
  font-weight: 800;
  color: #00695C;
  margin: 0;
  letter-spacing: .5px;
}
.qc-print-subtitle {
  font-size: 12px;
  color: #607D8B;
  margin: 2px 0 0;
}
.qc-print-header-right { text-align: right; flex: 0 0 auto; }
.qc-print-doc-info {
  font-size: 11px;
  color: #546E7A;
}
.qc-print-label { font-weight: 700; }
/* Divider */
.qc-print-divider {
  height: 3px;
  background: linear-gradient(90deg, #006064, #26C6DA, #006064);
  border-radius: 2px;
  margin: 12px 0 20px;
}
/* Section */
.qc-print-section { margin-bottom: 20px; }
.qc-print-section-title {
  font-size: 15px;
  font-weight: 700;
  color: #00695C;
  border-bottom: 2px solid #B2DFDB;
  padding-bottom: 6px;
  margin-bottom: 12px;
}
.qc-print-section-icon {
  display: inline-block;
  width: 20px;
  height: 20px;
  text-align: center;
  margin-right: 6px;
  color: #00838F;
  font-weight: 700;
}
/* General Info Table */
.qc-print-info-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 4px;
}
.qc-print-info-table td {
  padding: 8px 12px;
  border: 1px solid #E0E0E0;
  vertical-align: top;
}
.qc-print-info-label {
  font-weight: 700;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #546E7A;
  background: #F5F5F5;
  width: 18%;
}
.qc-print-info-value {
  font-size: 13px;
  color: #212121;
  width: 32%;
}
/* Status badges */
.qc-print-status {
  display: inline-block;
  padding: 3px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.qc-print-status-passed { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
.qc-print-status-failed { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }
.qc-print-status-pending { background: #FFF8E1; color: #F57F17; border: 1px solid #FFE082; }
/* Check Items Table */
.qc-print-items-table {
  width: 100%;
  border-collapse: collapse;
}
.qc-print-items-table th {
  background: #00695C;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  padding: 10px 12px;
  text-align: left;
  border: 1px solid #004D40;
}
.qc-print-items-table th:first-child { width: 40px; text-align: center; }
.qc-print-items-table td {
  padding: 8px 12px;
  border: 1px solid #E0E0E0;
  font-size: 13px;
}
.qc-print-items-table td:first-child { text-align: center; font-weight: 600; color: #78909C; }
.qc-print-items-table tbody tr:nth-child(even) { background: #FAFAFA; }
.qc-print-items-table tbody tr:hover { background: #E0F2F1; }
.qc-print-row-fail { background: #FFF3F0 !important; }
/* Result text */
.qc-print-result-pass { color: #2E7D32; font-weight: 700; }
.qc-print-result-pass::before { content: '✓ '; }
.qc-print-result-fail { color: #C62828; font-weight: 700; }
.qc-print-result-fail::before { content: '✗ '; }
.qc-print-result-n_a { color: #9E9E9E; font-style: italic; }
/* Summary */
.qc-print-summary {
  display: flex;
  justify-content: center;
  gap: 32px;
  margin: 24px 0;
  padding: 16px;
  background: #F5F5F5;
  border-radius: 8px;
  border: 1px solid #E0E0E0;
}
.qc-print-summary-item { text-align: center; }
.qc-print-summary-count {
  display: block;
  font-size: 28px;
  font-weight: 800;
  line-height: 1;
}
.qc-print-summary-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #78909C;
  font-weight: 600;
  margin-top: 4px;
  display: block;
}
.qc-print-pass { color: #2E7D32; }
.qc-print-fail { color: #C62828; }
.qc-print-na { color: #9E9E9E; }
/* Footer */
.qc-print-footer {
  display: flex;
  justify-content: space-between;
  border-top: 1px solid #E0E0E0;
  padding-top: 10px;
  margin-top: 20px;
  font-size: 10px;
  color: #9E9E9E;
}
/* Signatures */
.qc-print-signatures {
  display: flex;
  justify-content: space-between;
  margin-top: 48px;
  gap: 60px;
}
.qc-print-sig-box { flex: 1; text-align: center; }
.qc-print-sig-line {
  border-bottom: 1px solid #333;
  margin-bottom: 6px;
  height: 40px;
}
.qc-print-sig-label {
  font-size: 11px;
  font-weight: 600;
  color: #546E7A;
  text-transform: uppercase;
  letter-spacing: .5px;
}
@media print {
  #qc-print-area { display: block !important; }
  .qc-print-page { padding: 16px; }
}
</style>
