<template>
  <v-dialog v-model="dialogVisible" max-width="720" scrollable>
    <v-card class="audit-log-card rounded-xl">
      <!-- Header -->
      <div class="audit-header">
        <div class="audit-header-inner">
          <div class="d-flex align-center ga-3">
            <div class="audit-header-icon">
              <v-icon size="24" color="white">mdi-history</v-icon>
            </div>
            <div>
              <div class="audit-header-title">{{ $t('audit_log_title') }}</div>
              <div class="text-white text-caption" style="opacity:.7">{{ $t('audit_log_subtitle') }}</div>
            </div>
          </div>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" color="white" size="small" @click="dialogVisible = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <!-- Loading -->
      <v-progress-linear v-if="loading" indeterminate :color="moduleColor"></v-progress-linear>

      <!-- Content -->
      <v-card-text class="pa-4" style="max-height: 520px; overflow-y: auto;">
        <!-- Empty state -->
        <div v-if="!loading && logs.length === 0" class="d-flex flex-column align-center justify-center py-12" style="opacity: .5;">
          <v-icon size="64" :color="moduleColor" class="mb-3">mdi-history</v-icon>
          <div class="text-body-1 font-weight-medium">{{ $t('no_audit_logs') }}</div>
        </div>

        <!-- Timeline -->
        <div v-else class="audit-timeline">
          <div v-for="(log, idx) in logs" :key="log.id" class="audit-entry" :class="{ 'audit-entry--last': idx === logs.length - 1 }">
            <!-- Connector line -->
            <div class="audit-connector">
              <div class="audit-dot" :style="{ background: getActionColor(log.action) }">
                <v-icon size="14" color="white">{{ getActionIcon(log.action) }}</v-icon>
              </div>
              <div v-if="idx < logs.length - 1" class="audit-line"></div>
            </div>

            <!-- Content -->
            <div class="audit-content">
              <div class="d-flex align-center ga-2 mb-1 flex-wrap">
                <v-chip size="x-small" :color="getActionColor(log.action)" variant="tonal" class="font-weight-bold">
                  {{ $t(getActionLabel(log.action)) }}
                </v-chip>
                <span class="text-caption text-medium-emphasis">
                  <v-icon size="12" class="mr-1">mdi-account</v-icon>{{ log.user_name }}
                </span>
                <span class="text-caption text-medium-emphasis">
                  <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>{{ formatDate(log.created_at) }}
                </span>
              </div>

              <!-- Updated fields table -->
              <div v-if="log.action === 'updated' && log.old_values && log.new_values" class="audit-changes-table mt-2">
                <table class="w-100">
                  <thead>
                    <tr>
                      <th>{{ $t('field_changed') }}</th>
                      <th>{{ $t('old_value') }}</th>
                      <th>{{ $t('new_value') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="field in getChangedFields(log)" :key="field.key">
                      <td class="font-weight-medium">{{ formatFieldName(field.key) }}</td>
                      <td class="audit-old-value">
                        <audit-value :value="field.old" :field-key="field.key" />
                      </td>
                      <td class="audit-new-value">
                        <audit-value :value="field.new" :field-key="field.key" is-new />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Created fields -->
              <div v-else-if="log.action === 'created' && log.new_values" class="audit-changes-table mt-2">
                <table class="w-100">
                  <thead>
                    <tr>
                      <th>{{ $t('field_changed') }}</th>
                      <th>{{ $t('initial_values') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="field in getInitialFields(log)" :key="field.key">
                      <td class="font-weight-medium">{{ formatFieldName(field.key) }}</td>
                      <td>
                        <audit-value :value="field.value" :field-key="field.key" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Deleted -->
              <div v-else-if="log.action === 'deleted'" class="mt-1">
                <v-chip size="x-small" color="error" variant="flat" class="font-weight-bold">
                  <v-icon size="12" class="mr-1">mdi-delete</v-icon>
                  {{ $t('record_deleted') }}
                </v-chip>
              </div>
            </div>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script>
import axios from 'axios';
import { h } from 'vue';

/**
 * Inline functional component for rendering audit values intelligently.
 * Handles arrays, objects, booleans, nulls, numbers, dates, etc.
 */
const AuditValue = {
  name: 'AuditValue',
  props: {
    value: { default: null },
    fieldKey: { type: String, default: '' },
    isNew: { type: Boolean, default: false },
  },
  render() {
    const val = this.value;
    const key = this.fieldKey;

    // null / undefined / empty
    if (val === null || val === undefined || val === '') {
      return h('span', { class: 'text-grey', style: 'font-style:italic' }, '—');
    }

    // Boolean
    if (typeof val === 'boolean') {
      return h('v-chip', {
        size: 'x-small',
        color: val ? 'success' : 'error',
        variant: 'tonal',
      }, { default: () => val ? '✓ Yes' : '✗ No' });
    }

    // Array (e.g. tracking numbers, truck_ids)
    if (Array.isArray(val)) {
      if (val.length === 0) {
        return h('span', { class: 'text-grey', style: 'font-style:italic' }, '—');
      }
      // Check if array of primitives
      if (val.every(v => typeof v !== 'object' || v === null)) {
        const chips = val.map((item, i) =>
          h('v-chip', {
            key: i,
            size: 'x-small',
            color: this.isNew ? 'success' : 'blue-grey',
            variant: 'tonal',
            class: 'mr-1 mb-1',
          }, { default: () => String(item) })
        );
        return h('div', { class: 'd-flex flex-wrap ga-1' }, chips);
      }
      // Array of objects → display count + summary
      return h('v-chip', {
        size: 'x-small',
        color: 'info',
        variant: 'tonal',
      }, { default: () => `${val.length} items` });
    }

    // Object (nested data)
    if (typeof val === 'object') {
      // Try to extract a meaningful display
      const display = this._extractObjectDisplay(val);
      if (display) {
        return h('span', {}, display);
      }
      // Fallback: show key-value pairs
      const entries = Object.entries(val).filter(([, v]) => v !== null && v !== undefined && v !== '');
      if (entries.length === 0) {
        return h('span', { class: 'text-grey', style: 'font-style:italic' }, '—');
      }
      const items = entries.slice(0, 5).map(([k, v]) =>
        h('div', { class: 'audit-kv-row', key: k }, [
          h('span', { class: 'audit-kv-key' }, this._formatKey(k) + ':'),
          h('span', { class: 'audit-kv-val' }, typeof v === 'object' ? JSON.stringify(v) : String(v)),
        ])
      );
      if (entries.length > 5) {
        items.push(h('div', { class: 'text-caption text-grey', style: 'font-style:italic' }, `+${entries.length - 5} more fields`));
      }
      return h('div', { class: 'audit-kv-container' }, items);
    }

    // Number — check if it looks like a price/weight and format
    if (typeof val === 'number') {
      if (key.includes('price') || key.includes('amount') || key.includes('cost')) {
        return h('span', { class: 'font-weight-bold' }, this._formatNumber(val));
      }
      if (key.includes('weight')) {
        return h('span', {}, this._formatNumber(val) + ' Kg');
      }
      if (key.includes('_id')) {
        return h('v-chip', { size: 'x-small', variant: 'tonal', color: 'blue-grey' },
          { default: () => `ID: ${val}` });
      }
      return h('span', {}, this._formatNumber(val));
    }

    // String — check for date patterns and format
    if (typeof val === 'string') {
      // Date pattern detection
      if (/^\d{4}-\d{2}-\d{2}(T|\s)/.test(val)) {
        const d = new Date(val);
        if (!isNaN(d.getTime())) {
          return h('span', {}, d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }));
        }
      }
      if (/^\d{4}-\d{2}-\d{2}$/.test(val)) {
        const d = new Date(val + 'T00:00:00');
        if (!isNaN(d.getTime())) {
          return h('span', {}, d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }));
        }
      }
      return h('span', {}, val);
    }

    return h('span', {}, String(val));
  },
  methods: {
    _extractObjectDisplay(obj) {
      // Common patterns: {name: ..., ...}, {name_fr: ..., name_en: ...}
      if (obj.name) return obj.name;
      if (obj.name_fr) return obj.name_fr;
      if (obj.name_en) return obj.name_en;
      if (obj.full_name) return obj.full_name;
      if (obj.title) return obj.title;
      if (obj.label) return obj.label;
      return null;
    },
    _formatKey(key) {
      return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },
    _formatNumber(n) {
      return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(n);
    },
  },
};

export default {
  name: 'AuditLogDialog',
  components: { AuditValue },
  props: {
    modelValue: { type: Boolean, default: false },
    auditableType: { type: String, required: true },
    auditableId: { type: [Number, String], required: true },
    moduleColor: { type: String, default: '#78909C' },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      logs: [],
      loading: false,
    };
  },
  computed: {
    dialogVisible: {
      get() { return this.modelValue; },
      set(val) { this.$emit('update:modelValue', val); },
    },
  },
  watch: {
    modelValue(val) {
      if (val) this.fetchLogs();
    },
  },
  methods: {
    async fetchLogs() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/audit-logs', {
          params: {
            auditable_type: this.auditableType,
            auditable_id: this.auditableId,
          },
        });
        this.logs = data.audit_logs || [];
      } catch (err) {
        console.error('[AuditLog] Failed to fetch:', err);
        this.logs = [];
      } finally {
        this.loading = false;
      }
    },

    getActionColor(action) {
      return { created: '#4CAF50', updated: '#FF9800', deleted: '#EF5350' }[action] || '#78909C';
    },
    getActionIcon(action) {
      return { created: 'mdi-plus', updated: 'mdi-pencil', deleted: 'mdi-delete' }[action] || 'mdi-information';
    },
    getActionLabel(action) {
      return { created: 'record_created', updated: 'record_updated', deleted: 'record_deleted' }[action] || action;
    },

    getChangedFields(log) {
      const fields = [];
      if (!log.old_values || !log.new_values) return fields;
      for (const key of Object.keys(log.new_values)) {
        fields.push({ key, old: log.old_values[key], new: log.new_values[key] });
      }
      return fields;
    },

    getInitialFields(log) {
      const fields = [];
      if (!log.new_values) return fields;
      for (const [key, value] of Object.entries(log.new_values)) {
        if (value !== null && value !== undefined && value !== '') {
          fields.push({ key, value });
        }
      }
      return fields;
    },

    formatFieldName(key) {
      // Convert snake_case to Title Case
      return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) +
        ' ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    },
  },
};
</script>

<style scoped>
.audit-log-card { overflow: hidden; font-family: 'Inter', sans-serif; }
.audit-header { background: linear-gradient(135deg, #455A64 0%, #607D8B 40%, #78909C 100%); }
.audit-header-inner { display: flex; align-items: center; padding: 16px 20px; color: #fff; }
.audit-header-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.audit-header-title { font-size: 16px; font-weight: 700; letter-spacing: .3px; }

.audit-timeline { padding: 4px 0; }
.audit-entry { display: flex; gap: 14px; min-height: 48px; }
.audit-connector { display: flex; flex-direction: column; align-items: center; width: 28px; flex-shrink: 0; }
.audit-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
.audit-line { width: 2px; flex-grow: 1; background: rgba(var(--v-theme-on-surface), .12); margin: 4px 0; }
.audit-content { flex: 1; padding-bottom: 16px; }

.audit-changes-table { border-radius: 10px; overflow: hidden; border: 1px solid rgba(var(--v-theme-on-surface), .08); }
.audit-changes-table table { border-collapse: collapse; }
.audit-changes-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 6px 10px; background: rgba(var(--v-theme-on-surface), .04); color: rgba(var(--v-theme-on-surface), .6); text-align: left; }
.audit-changes-table td { font-size: 12px; padding: 5px 10px; border-top: 1px solid rgba(var(--v-theme-on-surface), .06); vertical-align: top; }
.audit-old-value { color: #EF5350; opacity: .8; }
.audit-new-value { color: #4CAF50; font-weight: 600; }

/* Key-value display for nested objects */
.audit-kv-container { display: flex; flex-direction: column; gap: 2px; }
.audit-kv-row { display: flex; gap: 4px; font-size: 11px; line-height: 1.4; }
.audit-kv-key { color: rgba(var(--v-theme-on-surface), .5); font-weight: 600; white-space: nowrap; }
.audit-kv-val { word-break: break-word; }
</style>

