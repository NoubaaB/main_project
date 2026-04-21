<template>
  <v-card class="at-card" elevation="0">
    <!-- Hero Header -->
    <div class="at-hero-bg"></div>
    <div class="at-hero-inner">
      <div class="d-flex align-center ga-4">
        <v-btn icon variant="text" @click="$router.back()" class="mr-1">
          <v-icon size="22" color="white">mdi-arrow-left</v-icon>
        </v-btn>
        <div class="at-hero-icon">
          <v-icon size="28" color="white">mdi-history</v-icon>
        </div>
        <div>
          <div class="at-hero-title">{{ $t('audit_trail') }}</div>
          <div class="text-white-darken-2 text-caption" style="opacity:.7">
            {{ $t('charge') }} #{{ chargeId }} — {{ chargeInfo.reception_tracking_number?.join(', ') || '' }}
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-6">
      <!-- Loading -->
      <div v-if="loading" class="d-flex flex-column ga-4">
        <v-skeleton-loader type="list-item-avatar-three-line" v-for="n in 4" :key="n" class="rounded-lg"></v-skeleton-loader>
      </div>

      <!-- Empty -->
      <div v-else-if="logs.length === 0" class="text-center py-16">
        <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-clipboard-text-clock-outline</v-icon>
        <div class="text-h6 text-grey">{{ $t('no_audit_logs') }}</div>
      </div>

      <!-- Timeline -->
      <v-timeline v-else side="end" density="comfortable" line-thickness="2" line-color="info">
        <v-timeline-item
          v-for="(log, i) in logs"
          :key="log.id || i"
          :dot-color="log.action === 'created' ? 'success' : log.action === 'deleted' ? 'error' : 'info'"
          :icon="log.action === 'created' ? 'mdi-plus-circle' : log.action === 'deleted' ? 'mdi-delete' : 'mdi-pencil'"
          size="small"
        >
          <v-card class="at-log-card" elevation="0" variant="outlined">
            <!-- Log Header -->
            <v-card-title class="d-flex align-center ga-3 pa-4 pb-2">
              <v-avatar size="32" :color="log.action === 'created' ? 'success' : 'info'" variant="tonal">
                <v-icon size="16">mdi-account</v-icon>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="text-body-2 font-weight-bold">{{ log.user_name }}</div>
                <div class="text-caption text-medium-emphasis">{{ formatDate(log.created_at) }}</div>
              </div>
              <v-chip
                :color="log.action === 'created' ? 'success' : log.action === 'deleted' ? 'error' : 'warning'"
                variant="tonal"
                size="small"
                class="text-uppercase font-weight-bold"
              >
                <v-icon start size="12">{{ log.action === 'created' ? 'mdi-plus' : log.action === 'deleted' ? 'mdi-delete' : 'mdi-pencil' }}</v-icon>
                {{ $t(log.action) }}
              </v-chip>
            </v-card-title>

            <!-- Changes Table -->
            <v-card-text v-if="log.changes && Object.keys(log.changes).length" class="pt-2">
              <v-table density="compact" class="at-changes-table rounded-lg">
                <thead>
                  <tr>
                    <th class="text-left">{{ $t('field') }}</th>
                    <th class="text-left">{{ $t('old_value') }}</th>
                    <th class="text-left">{{ $t('new_value') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(change, field) in log.changes" :key="field">
                    <td class="font-weight-medium">
                      <v-chip size="x-small" variant="tonal" color="info">{{ formatField(field) }}</v-chip>
                    </td>
                    <td>
                      <span v-if="change.old === null || change.old === ''" class="text-grey text-caption font-italic">—</span>
                      <span v-else class="text-error text-decoration-line-through text-caption">{{ formatValue(change.old) }}</span>
                    </td>
                    <td>
                      <span v-if="change.new === null || change.new === ''" class="text-grey text-caption font-italic">—</span>
                      <span v-else class="text-success font-weight-medium text-caption">{{ formatValue(change.new) }}</span>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-timeline-item>
      </v-timeline>
    </v-card-text>
  </v-card>
</template>

<script>
export default {
  name: 'ChargeAuditTrail',
  data() {
    return {
      loading: true,
      chargeInfo: {},
      logs: [],
    };
  },
  computed: {
    chargeId() {
      return this.$route.params.id;
    },
  },
  mounted() {
    this.fetchAuditLogs();
  },
  methods: {
    async fetchAuditLogs() {
      this.loading = true;
      try {
        const res = await axios.get(`/api/charge/${this.chargeId}/audit-logs`);
        this.chargeInfo = res.data.charge || {};
        this.logs = res.data.logs || [];
      } catch (err) {
        console.error('Failed to fetch audit logs:', err);
      } finally {
        this.loading = false;
      }
    },
    formatDate(ts) {
      if (!ts) return '';
      const d = new Date(ts);
      return d.toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      });
    },
    formatField(field) {
      return field.replace(/_/g, ' ').replace(/\bid\b/g, '').trim();
    },
    formatValue(val) {
      if (Array.isArray(val)) return val.join(', ');
      if (typeof val === 'object' && val !== null) return JSON.stringify(val);
      return String(val);
    },
  },
};
</script>

<style scoped>
.at-card {
  border-radius: 20px !important;
  overflow: visible;
  position: relative;
}
.at-hero-bg {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: inherit;
  height: 110px;
}
.at-hero-inner {
  position: relative; z-index: 1;
  padding: 22px 26px;
  color: #fff;
}
.at-hero-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255,255,255,.15);
}
.at-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.at-log-card {
  border-radius: 14px !important;
  border-color: rgba(var(--v-theme-on-surface), 0.08) !important;
  transition: all 0.2s ease;
}
.at-log-card:hover {
  border-color: rgb(var(--v-theme-info)) !important;
  box-shadow: 0 4px 20px rgba(33, 150, 243, 0.08);
}
.at-changes-table {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06) !important;
}
.at-changes-table th {
  font-size: 11px !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 700 !important;
  opacity: 0.6;
}
</style>
