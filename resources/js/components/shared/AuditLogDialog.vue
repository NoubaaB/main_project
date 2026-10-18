<template>
  <v-dialog v-model="dialogVisible" max-width="820" scrollable>
    <v-card class="audit-log-card rounded-xl">
      <!-- Dynamic Hero Header -->
      <div class="audit-header" :style="{ background: heroGradient }">
        <div class="audit-header-inner">
          <div class="d-flex align-center justify-space-between w-100 flex-wrap ga-3">
            <div class="d-flex align-center ga-3">
              <div class="audit-header-icon">
                <v-icon size="26" color="white">mdi-history</v-icon>
              </div>
              <div>
                <div class="d-flex align-center ga-2 flex-wrap">
                  <span class="audit-header-title">{{ $t('audit_log_title') }}</span>
                  <v-chip size="x-small" color="white" variant="outlined" class="font-weight-bold" style="border-color: rgba(255,255,255,0.4)">
                    #{{ auditableId }} &bull; {{ auditableType }}
                  </v-chip>
                </div>
                <div class="text-white text-caption" style="opacity:.8">{{ $t('audit_log_subtitle') }}</div>
              </div>
            </div>

            <div class="d-flex align-center ga-2">
              <v-btn icon variant="text" color="white" size="small" @click="fetchLogs" :loading="loading" :title="$t('refresh')">
                <v-icon size="20">mdi-refresh</v-icon>
              </v-btn>
              <v-btn icon variant="text" color="white" size="small" @click="dialogVisible = false">
                <v-icon size="20">mdi-close</v-icon>
              </v-btn>
            </div>
          </div>

          <!-- KPI Summary Strip -->
          <div v-if="logs.length > 0" class="audit-kpi-strip mt-3">
            <div class="audit-kpi-pill">
              <v-icon size="14" class="mr-1">mdi-counter</v-icon>
              <span class="text-caption font-weight-bold">{{ logs.length }}</span>
              <span class="text-caption ml-1" style="opacity:.75">{{ $t('revisions_count') }}</span>
            </div>

            <div v-if="firstLog" class="audit-kpi-pill">
              <v-icon size="14" class="mr-1">mdi-plus-circle-outline</v-icon>
              <span class="text-caption" style="opacity:.75">{{ $t('created_by') }}:</span>
              <span class="text-caption font-weight-bold ml-1">{{ firstLog.user_name || 'System' }}</span>
              <span class="text-caption ml-1" style="opacity:.6">({{ formatDateShort(firstLog.created_at) }})</span>
            </div>

            <div v-if="lastLog && logs.length > 1" class="audit-kpi-pill">
              <v-icon size="14" class="mr-1">mdi-update</v-icon>
              <span class="text-caption" style="opacity:.75">{{ $t('last_modified_by') }}:</span>
              <span class="text-caption font-weight-bold ml-1">{{ lastLog.user_name || 'System' }}</span>
              <span class="text-caption ml-1" style="opacity:.6">({{ formatDateShort(lastLog.created_at) }})</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Controls: Search & Filter Toolbar -->
      <div v-if="logs.length > 0" class="audit-toolbar pa-3 px-4 d-flex align-center justify-space-between flex-wrap ga-2 border-b">
        <!-- Action Filter Chips -->
        <div class="d-flex align-center ga-1 flex-wrap">
          <v-chip
            size="small"
            :variant="selectedAction === 'all' ? 'flat' : 'tonal'"
            :color="selectedAction === 'all' ? moduleColor : 'default'"
            class="font-weight-medium cursor-pointer"
            @click="selectedAction = 'all'"
          >
            {{ $t('all_actions') }}
            <span class="ml-1 text-caption opacity-70">({{ logs.length }})</span>
          </v-chip>

          <v-chip
            v-if="actionCounts.created"
            size="small"
            :variant="selectedAction === 'created' ? 'flat' : 'tonal'"
            color="#4CAF50"
            class="font-weight-medium cursor-pointer"
            @click="selectedAction = 'created'"
          >
            <v-icon size="14" class="mr-1">mdi-plus</v-icon>
            {{ $t('record_created') }}
            <span class="ml-1 text-caption opacity-70">({{ actionCounts.created }})</span>
          </v-chip>

          <v-chip
            v-if="actionCounts.updated"
            size="small"
            :variant="selectedAction === 'updated' ? 'flat' : 'tonal'"
            color="#FF9800"
            class="font-weight-medium cursor-pointer"
            @click="selectedAction = 'updated'"
          >
            <v-icon size="14" class="mr-1">mdi-pencil</v-icon>
            {{ $t('record_updated') }}
            <span class="ml-1 text-caption opacity-70">({{ actionCounts.updated }})</span>
          </v-chip>

          <v-chip
            v-if="actionCounts.deleted"
            size="small"
            :variant="selectedAction === 'deleted' ? 'flat' : 'tonal'"
            color="#EF5350"
            class="font-weight-medium cursor-pointer"
            @click="selectedAction = 'deleted'"
          >
            <v-icon size="14" class="mr-1">mdi-delete</v-icon>
            {{ $t('record_deleted') }}
            <span class="ml-1 text-caption opacity-70">({{ actionCounts.deleted }})</span>
          </v-chip>
        </div>

        <!-- Search Input -->
        <div style="min-width: 200px; max-width: 280px; flex-grow: 1;">
          <v-text-field
            v-model="searchQuery"
            density="compact"
            variant="outlined"
            rounded
            hide-details
            :placeholder="$t('search_history')"
            prepend-inner-icon="mdi-magnify"
            clearable
            :color="moduleColor"
          ></v-text-field>
        </div>
      </div>

      <!-- Loading Bar -->
      <v-progress-linear v-if="loading" indeterminate :color="moduleColor" height="3"></v-progress-linear>

      <!-- Content -->
      <v-card-text class="pa-4 audit-body-scroll" style="max-height: 560px; overflow-y: auto;">
        <!-- Empty state: No logs at all -->
        <div v-if="!loading && logs.length === 0" class="d-flex flex-column align-center justify-center py-12" style="opacity: .6;">
          <div class="empty-icon-circle mb-3" :style="{ background: `${moduleColor}15` }">
            <v-icon size="48" :color="moduleColor">mdi-history</v-icon>
          </div>
          <div class="text-subtitle-1 font-weight-bold">{{ $t('no_audit_logs') }}</div>
          <div class="text-caption text-medium-emphasis mt-1">{{ $t('no_audit_logs_desc') }}</div>
        </div>

        <!-- Filtered empty state: Search yielded 0 items -->
        <div v-else-if="!loading && filteredLogs.length === 0" class="d-flex flex-column align-center justify-center py-10" style="opacity: .75;">
          <v-icon size="44" color="grey" class="mb-2">mdi-text-box-search-outline</v-icon>
          <div class="text-body-1 font-weight-bold">{{ $t('no_matching_logs') }}</div>
          <v-btn size="small" variant="text" :color="moduleColor" class="mt-2" @click="resetFilters">
            {{ $t('clear_filter') }}
          </v-btn>
        </div>

        <!-- Timeline of Events -->
        <div v-else class="audit-timeline">
          <div
            v-for="(log, idx) in filteredLogs"
            :key="log.id"
            class="audit-entry"
            :class="{ 'audit-entry--last': idx === filteredLogs.length - 1 }"
          >
            <!-- Connector Line & Node -->
            <div class="audit-connector">
              <div class="audit-dot" :style="{ background: getActionColor(log.action), boxShadow: `0 3px 8px ${getActionColor(log.action)}40` }">
                <v-icon size="14" color="white">{{ getActionIcon(log.action) }}</v-icon>
              </div>
              <div v-if="idx < filteredLogs.length - 1" class="audit-line"></div>
            </div>

            <!-- Content Card -->
            <div class="audit-content">
              <div class="audit-event-card pa-3 rounded-lg">
                <!-- Event Header -->
                <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-2">
                  <div class="d-flex align-center ga-2 flex-wrap">
                    <v-chip size="x-small" :color="getActionColor(log.action)" variant="flat" class="font-weight-bold text-white">
                      {{ $t(getActionLabel(log.action)) }}
                    </v-chip>

                    <div class="d-flex align-center text-caption font-weight-medium">
                      <v-avatar size="18" :color="getActionColor(log.action)" class="mr-1 text-white text-caption font-weight-bold">
                        {{ (log.user_name || 'U').charAt(0).toUpperCase() }}
                      </v-avatar>
                      <span>{{ log.user_name || 'System' }}</span>
                    </div>
                  </div>

                  <div class="d-flex align-center text-caption text-medium-emphasis">
                    <v-icon size="13" class="mr-1">mdi-clock-outline</v-icon>
                    <span>{{ formatDate(log.created_at) }}</span>
                  </div>
                </div>

                <!-- Updated Fields Diff (Modern Side-by-Side Diff Cards) -->
                <div v-if="log.action === 'updated' && log.old_values && log.new_values" class="audit-diff-container mt-2">
                  <div
                    v-for="field in getChangedFields(log)"
                    :key="field.key"
                    class="audit-diff-row pa-2 rounded mb-2"
                  >
                    <div class="audit-field-badge mb-1 d-flex align-center">
                      <v-icon size="14" :color="moduleColor" class="mr-1">mdi-label-outline</v-icon>
                      <span class="font-weight-bold text-caption">{{ formatFieldName(field.key) }}</span>
                    </div>

                    <div class="d-flex align-center ga-2 flex-wrap">
                      <!-- Old Value -->
                      <div class="audit-diff-box audit-diff-old flex-grow-1">
                        <div class="text-caption text-grey font-weight-medium mb-1 d-flex align-center">
                          <v-icon size="12" color="error" class="mr-1">mdi-minus</v-icon>
                          <span>{{ $t('old_value') }}</span>
                        </div>
                        <div class="audit-val-wrap text-body-2">
                          <audit-value :value="field.old" :field-key="field.key" />
                        </div>
                      </div>

                      <!-- Arrow Indicator -->
                      <div class="audit-diff-arrow">
                        <v-icon size="16" color="grey">mdi-arrow-right</v-icon>
                      </div>

                      <!-- New Value -->
                      <div class="audit-diff-box audit-diff-new flex-grow-1">
                        <div class="text-caption text-success font-weight-medium mb-1 d-flex align-center">
                          <v-icon size="12" color="success" class="mr-1">mdi-check</v-icon>
                          <span>{{ $t('new_value') }}</span>
                        </div>
                        <div class="audit-val-wrap text-body-2">
                          <audit-value :value="field.new" :field-key="field.key" is-new />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Created Initial Fields -->
                <div v-else-if="log.action === 'created' && log.new_values" class="audit-initial-grid mt-2">
                  <div class="text-caption font-weight-bold text-medium-emphasis mb-2 d-flex align-center">
                    <v-icon size="14" color="success" class="mr-1">mdi-creation</v-icon>
                    <span>{{ $t('initial_values') }}</span>
                  </div>
                  <div class="d-flex flex-wrap ga-2">
                    <div
                      v-for="field in getInitialFields(log)"
                      :key="field.key"
                      class="audit-initial-pill pa-2 rounded"
                    >
                      <span class="font-weight-bold text-caption text-medium-emphasis mr-1">{{ formatFieldName(field.key) }}:</span>
                      <audit-value :value="field.value" :field-key="field.key" is-new />
                    </div>
                  </div>
                </div>

                <!-- Deleted Banner -->
                <div v-else-if="log.action === 'deleted'" class="mt-2 pa-2 rounded audit-deleted-banner d-flex align-center ga-2">
                  <v-icon size="18" color="error">mdi-alert-octagon-outline</v-icon>
                  <span class="text-caption font-weight-medium text-error">{{ $t('record_deleted_message') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </v-card-text>

      <!-- Footer -->
      <v-divider></v-divider>
      <v-card-actions class="pa-3 px-4 d-flex align-center justify-space-between">
        <span class="text-caption text-medium-emphasis">
          {{ filteredLogs.length }} {{ $t('records_showing') }}
        </span>
        <v-btn variant="tonal" rounded size="small" :color="moduleColor" @click="dialogVisible = false">
          {{ $t('close') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
import axios from 'axios';
import { h } from 'vue';

/**
 * Intelligent AuditValue renderer for primitives, objects, arrays, and dates.
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

    if (val === null || val === undefined || val === '') {
      return h('span', { class: 'text-grey', style: 'font-style:italic' }, '—');
    }

    if (typeof val === 'boolean') {
      return h('v-chip', {
        size: 'x-small',
        color: val ? 'success' : 'error',
        variant: 'tonal',
        class: 'font-weight-bold'
      }, { default: () => val ? '✓ Yes' : '✗ No' });
    }

    if (Array.isArray(val)) {
      if (val.length === 0) {
        return h('span', { class: 'text-grey', style: 'font-style:italic' }, '—');
      }
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
      return h('v-chip', {
        size: 'x-small',
        color: 'info',
        variant: 'tonal',
      }, { default: () => `${val.length} items` });
    }

    if (typeof val === 'object') {
      const display = this._extractObjectDisplay(val);
      if (display) return h('span', { class: 'font-weight-medium' }, display);

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

    if (typeof val === 'number') {
      if (key.includes('price') || key.includes('amount') || key.includes('cost')) {
        return h('span', { class: 'font-weight-bold text-success' }, this._formatNumber(val));
      }
      if (key.includes('weight')) {
        return h('span', { class: 'font-weight-bold' }, this._formatNumber(val) + ' Kg');
      }
      if (key.includes('_id')) {
        return h('v-chip', { size: 'x-small', variant: 'tonal', color: 'blue-grey' },
          { default: () => `ID: ${val}` });
      }
      return h('span', { class: 'font-weight-medium' }, this._formatNumber(val));
    }

    if (typeof val === 'string') {
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
    moduleColor: { type: String, default: '#42A5F5' },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      logs: [],
      loading: false,
      selectedAction: 'all',
      searchQuery: '',
    };
  },
  computed: {
    dialogVisible: {
      get() { return this.modelValue; },
      set(val) { this.$emit('update:modelValue', val); },
    },
    heroGradient() {
      const c = this.moduleColor.toUpperCase();
      // Tailored gradients for known modules matching claude.md
      if (c === '#42A5F5' || c.includes('42A5F5')) {
        return 'linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%)';
      }
      if (c === '#00897B' || c.includes('00897B')) {
        return 'linear-gradient(135deg, #004D40 0%, #00695C 40%, #00897B 100%)';
      }
      if (c === '#4643A0' || c.includes('4643A0')) {
        return 'linear-gradient(135deg, #282163 0%, #372e7d 40%, #4643a0 100%)';
      }
      if (c === '#F9A825' || c.includes('F9A825')) {
        return 'linear-gradient(135deg, #E65100 0%, #F57F17 50%, #F9A825 100%)';
      }
      // Fallback dynamic gradient
      return `linear-gradient(135deg, #263238 0%, ${this.moduleColor} 100%)`;
    },
    actionCounts() {
      const counts = { created: 0, updated: 0, deleted: 0 };
      this.logs.forEach(l => {
        if (counts[l.action] !== undefined) counts[l.action]++;
      });
      return counts;
    },
    firstLog() {
      return this.logs.length > 0 ? this.logs[this.logs.length - 1] : null;
    },
    lastLog() {
      return this.logs.length > 0 ? this.logs[0] : null;
    },
    filteredLogs() {
      let result = this.logs;

      // Filter by action
      if (this.selectedAction !== 'all') {
        result = result.filter(l => l.action === this.selectedAction);
      }

      // Filter by search query
      if (this.searchQuery?.trim()) {
        const q = this.searchQuery.trim().toLowerCase();
        result = result.filter(l => {
          if (l.user_name && l.user_name.toLowerCase().includes(q)) return true;
          if (l.action && l.action.toLowerCase().includes(q)) return true;
          // Check changed fields
          if (l.new_values) {
            for (const [k, v] of Object.entries(l.new_values)) {
              if (k.toLowerCase().includes(q)) return true;
              if (String(v).toLowerCase().includes(q)) return true;
            }
          }
          if (l.old_values) {
            for (const [k, v] of Object.entries(l.old_values)) {
              if (k.toLowerCase().includes(q)) return true;
              if (String(v).toLowerCase().includes(q)) return true;
            }
          }
          return false;
        });
      }

      return result;
    },
  },
  watch: {
    modelValue(val) {
      if (val) {
        this.selectedAction = 'all';
        this.searchQuery = '';
        this.fetchLogs();
      }
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

    resetFilters() {
      this.selectedAction = 'all';
      this.searchQuery = '';
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
      return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) +
        ' • ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    },

    formatDateShort(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    },
  },
};
</script>

<style scoped>
.audit-log-card { overflow: hidden; font-family: 'Inter', sans-serif; }
.audit-header { border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
.audit-header-inner { padding: 18px 22px; color: #fff; }
.audit-header-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255,255,255,.2);
}
.audit-header-title { font-size: 17px; font-weight: 800; letter-spacing: .2px; }

/* KPI strip */
.audit-kpi-strip {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.audit-kpi-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 20px;
  background: rgba(255,255,255,.14);
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255,255,255,.12);
}

/* Toolbar */
.audit-toolbar {
  background: rgba(var(--v-theme-on-surface), .02);
}

/* Timeline */
.audit-timeline { padding: 8px 4px; }
.audit-entry { display: flex; gap: 14px; min-height: 52px; }
.audit-connector { display: flex; flex-direction: column; align-items: center; width: 28px; flex-shrink: 0; }
.audit-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.audit-line { width: 2px; flex-grow: 1; background: rgba(var(--v-theme-on-surface), .12); margin: 6px 0; }
.audit-content { flex: 1; padding-bottom: 16px; }

/* Event Card */
.audit-event-card {
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  background: rgba(var(--v-theme-surface), 1);
  box-shadow: 0 1px 4px rgba(0,0,0,.04);
  transition: transform .15s ease, box-shadow .15s ease;
}
.audit-event-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,.08);
}

/* Diff Visualization */
.audit-diff-container { display: flex; flex-direction: column; gap: 6px; }
.audit-diff-row {
  background: rgba(var(--v-theme-on-surface), .025);
  border: 1px solid rgba(var(--v-theme-on-surface), .06);
}
.audit-diff-box {
  padding: 6px 10px;
  border-radius: 6px;
  min-width: 140px;
}
.audit-diff-old {
  background: rgba(244, 67, 54, .08);
  border: 1px dashed rgba(244, 67, 54, .25);
}
.audit-diff-new {
  background: rgba(76, 175, 80, .08);
  border: 1px solid rgba(76, 175, 80, .25);
}
.audit-diff-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}
.audit-val-wrap {
  word-break: break-word;
}

/* Initial values */
.audit-initial-pill {
  background: rgba(var(--v-theme-on-surface), .04);
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  display: inline-flex;
  align-items: center;
}

/* Deleted banner */
.audit-deleted-banner {
  background: rgba(244, 67, 54, .08);
  border: 1px solid rgba(244, 67, 54, .2);
}

/* Empty State */
.empty-icon-circle {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* KV objects */
.audit-kv-container { display: flex; flex-direction: column; gap: 2px; }
.audit-kv-row { display: flex; gap: 4px; font-size: 11px; line-height: 1.4; }
.audit-kv-key { color: rgba(var(--v-theme-on-surface), .6); font-weight: 600; white-space: nowrap; }
.audit-kv-val { word-break: break-word; }
</style>


