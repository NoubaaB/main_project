<template>
  <v-dialog v-model="dialogVisible" max-width="720" scrollable>
    <v-card class="ri-card rounded-xl">
      <!-- Header -->
      <div class="ri-header" :style="{ background: headerGradient }">
        <div class="ri-header-inner">
          <div class="d-flex align-center ga-3">
            <div class="ri-header-icon">
              <v-icon size="24" color="white">mdi-alert-circle-check-outline</v-icon>
            </div>
            <div>
              <div class="ri-header-title">{{ $t('record_issues') }}</div>
              <div class="text-white text-caption" style="opacity:.7">{{ $t('record_issues_subtitle') }}</div>
            </div>
          </div>
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-plus" @click="showCreateForm = true" v-if="!showCreateForm">
            {{ $t('flag_issue') }}
          </v-btn>
          <v-btn icon variant="text" color="white" size="small" @click="dialogVisible = false" class="ml-2">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <v-progress-linear v-if="loading" indeterminate :color="moduleColor"></v-progress-linear>

      <v-card-text class="pa-4" style="max-height: 520px; overflow-y: auto;">
        <!-- Create Issue Form -->
        <v-expand-transition>
          <div v-if="showCreateForm" class="ri-create-form mb-4">
            <div class="d-flex align-center mb-3">
              <v-icon :color="moduleColor" class="mr-2" size="20">mdi-flag-plus-outline</v-icon>
              <span class="text-subtitle-2 font-weight-bold">{{ $t('report_new_issue') }}</span>
            </div>
            <v-textarea
              v-model="newIssue.description"
              :label="$t('issue_description')"
              :placeholder="$t('issue_description_placeholder')"
              :counter="2000"
              :maxlength="2000"
              rows="4"
              variant="outlined"
              rounded
              :color="moduleColor"
              auto-grow
              hide-details="auto"
            ></v-textarea>
            <div class="d-flex justify-end ga-2 mt-3">
              <v-btn variant="text" rounded @click="cancelCreate">{{ $t('cancel') }}</v-btn>
              <v-btn
                :color="moduleColor"
                variant="flat"
                rounded
                prepend-icon="mdi-flag-checkered"
                :loading="submitting"
                :disabled="!newIssue.description?.trim()"
                @click="submitIssue"
              >{{ $t('submit_issue') }}</v-btn>
            </div>
          </div>
        </v-expand-transition>

        <!-- Open Issues Count Badge -->
        <div v-if="!loading && issues.length > 0" class="d-flex align-center mb-3">
          <v-chip size="small" :color="openCount > 0 ? 'error' : 'success'" variant="tonal" class="font-weight-bold mr-2">
            <v-icon size="14" class="mr-1">{{ openCount > 0 ? 'mdi-alert-circle' : 'mdi-check-circle' }}</v-icon>
            {{ openCount }} {{ $t('open_issues') }}
          </v-chip>
          <v-chip size="small" color="success" variant="tonal" class="font-weight-bold">
            <v-icon size="14" class="mr-1">mdi-check-all</v-icon>
            {{ resolvedCount }} {{ $t('resolved_issues') }}
          </v-chip>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && issues.length === 0 && !showCreateForm" class="d-flex flex-column align-center justify-center py-12" style="opacity: .5;">
          <v-icon size="64" :color="moduleColor" class="mb-3">mdi-check-circle-outline</v-icon>
          <div class="text-body-1 font-weight-medium">{{ $t('no_issues_found') }}</div>
          <div class="text-caption text-medium-emphasis mt-1">{{ $t('no_issues_found_desc') }}</div>
        </div>

        <!-- Issues List -->
        <div v-else class="ri-timeline">
          <div v-for="(issue, idx) in issues" :key="issue.id" class="ri-entry" :class="{ 'ri-entry--last': idx === issues.length - 1 }">
            <!-- Connector -->
            <div class="ri-connector">
              <div class="ri-dot" :style="{ background: getStatusColor(issue.status) }">
                <v-icon size="14" color="white">{{ getStatusIcon(issue.status) }}</v-icon>
              </div>
              <div v-if="idx < issues.length - 1" class="ri-line"></div>
            </div>

            <!-- Content -->
            <div class="ri-content">
              <div class="d-flex align-center ga-2 mb-1 flex-wrap">
                <v-chip size="x-small" :color="getStatusColor(issue.status)" variant="tonal" class="font-weight-bold">
                  {{ $t('issue_status_' + issue.status) }}
                </v-chip>
                <span class="text-caption text-medium-emphasis">
                  <v-icon size="12" class="mr-1">mdi-account</v-icon>{{ issue.reporter?.name || '-' }}
                </span>
                <span class="text-caption text-medium-emphasis">
                  <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>{{ formatDate(issue.created_at) }}
                </span>
              </div>

              <!-- Description -->
              <div class="ri-description">
                <v-icon size="16" :color="moduleColor" class="mr-1 mt-n1">mdi-text-box-outline</v-icon>
                {{ issue.description }}
              </div>

              <!-- Resolution note -->
              <div v-if="issue.status === 'resolved' && issue.resolution_note" class="ri-resolution mt-2">
                <v-icon size="14" color="success" class="mr-1">mdi-check-decagram</v-icon>
                <span class="text-caption font-weight-bold text-success mr-1">{{ $t('resolution') }}:</span>
                <span class="text-caption">{{ issue.resolution_note }}</span>
                <div class="text-caption text-medium-emphasis mt-1">
                  <v-icon size="12" class="mr-1">mdi-account-check</v-icon>{{ issue.resolver?.name || '-' }}
                  <span class="ml-2"><v-icon size="12" class="mr-1">mdi-clock-check-outline</v-icon>{{ formatDate(issue.resolved_at) }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="d-flex ga-1 mt-2" v-if="issue.status !== 'resolved'">
                <v-btn size="x-small" variant="tonal" color="success" rounded prepend-icon="mdi-check" @click="openResolveDialog(issue)">
                  {{ $t('resolve') }}
                </v-btn>
                <v-btn v-if="issue.status === 'open'" size="x-small" variant="tonal" color="warning" rounded prepend-icon="mdi-progress-wrench" @click="markInProgress(issue)">
                  {{ $t('in_progress') }}
                </v-btn>
                <v-btn v-if="canDelete(issue)" size="x-small" variant="tonal" color="error" rounded prepend-icon="mdi-delete" @click="confirmDelete(issue)">
                  {{ $t('delete') }}
                </v-btn>
              </div>
            </div>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>

  <!-- Resolve Dialog -->
  <v-dialog v-model="resolveDialog" max-width="480" persistent>
    <v-card class="rounded-xl">
      <v-card-title class="d-flex align-center pa-4">
        <v-icon color="success" class="mr-2">mdi-check-decagram</v-icon>
        <span>{{ $t('resolve_issue') }}</span>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="pa-4">
        <v-textarea
          v-model="resolveForm.resolution_note"
          :label="$t('resolution_note')"
          :placeholder="$t('resolution_note_placeholder')"
          :counter="2000"
          :maxlength="2000"
          rows="3"
          variant="outlined"
          rounded
          color="success"
          auto-grow
          hide-details="auto"
        ></v-textarea>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="resolveDialog = false">{{ $t('cancel') }}</v-btn>
        <v-btn color="success" variant="flat" rounded prepend-icon="mdi-check" :loading="resolving" @click="resolveIssue">
          {{ $t('resolve') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Delete Confirm Dialog -->
  <v-dialog v-model="deleteDialog" max-width="420">
    <v-card class="rounded-xl">
      <v-card-title class="pa-5 pb-2 d-flex align-center">
        <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}
      </v-card-title>
      <v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_issue_message') }}</v-card-text>
      <v-card-actions class="pa-5 pt-0">
        <v-spacer></v-spacer>
        <v-btn @click="deleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
        <v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Summary Snackbar -->
  <v-snackbar v-model="snackbar.show" :timeout="5000" :color="snackbar.color" rounded="lg" location="top right" elevation="12">
    <div class="d-flex align-center">
      <v-icon class="mr-2">{{ snackbar.icon }}</v-icon>
      <span class="text-subtitle-2 font-weight-medium">{{ snackbar.message }}</span>
    </div>
    <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false"></v-btn></template>
  </v-snackbar>
</template>

<script>
import axios from 'axios';
import { useAuth } from '@/stores/Auth';
import { useMain } from '@/stores/Main';
import mitt from '@/plugins/mitt';

export default {
  name: 'RecordIssueDialog',
  props: {
    modelValue: { type: Boolean, default: false },
    recordType: { type: String, required: true, validator: v => ['Charge', 'Production', 'Shipping'].includes(v) },
    recordId: { type: Number, required: true },
    moduleColor: { type: String, default: '#42A5F5' },
  },
  emits: ['update:modelValue', 'issue-changed'],
  data() {
    return {
      issues: [],
      loading: false,
      showCreateForm: false,
      newIssue: { description: '' },
      submitting: false,
      resolveDialog: false,
      resolveForm: { resolution_note: '' },
      resolvingIssue: null,
      resolving: false,
      deleteDialog: false,
      deletingIssue: null,
      deleting: false,
      snackbar: { show: false, message: '', color: 'success', icon: 'mdi-check-circle' },
    };
  },
  computed: {
    dialogVisible: {
      get() { return this.modelValue; },
      set(v) { this.$emit('update:modelValue', v); },
    },
    auth() { return useAuth(); },
    main() { return useMain(); },
    headerGradient() {
      const gradients = {
        Charge: 'linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%)',
        Production: 'linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%)',
        Shipping: 'linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%)',
      };
      return gradients[this.recordType] || gradients.Charge;
    },
    openCount() {
      return this.issues.filter(i => i.status !== 'resolved').length;
    },
    resolvedCount() {
      return this.issues.filter(i => i.status === 'resolved').length;
    },
  },
  watch: {
    modelValue(v) {
      if (v && this.recordId) this.fetchIssues();
    },
    recordId(v) {
      if (v && this.modelValue) this.fetchIssues();
    },
  },
  mounted() {
    mitt.on('model-changed', this._onMC);
  },
  beforeUnmount() {
    mitt.off('model-changed', this._onMC);
  },
  methods: {
    _onMC(d) {
      if (d.model === 'RecordIssue' && this.modelValue) {
        this.fetchIssues();
      }
    },
    async fetchIssues() {
      this.loading = true;
      try {
        const r = await axios.get('/api/record-issues', {
          params: { record_type: this.recordType, record_id: this.recordId },
        });
        this.issues = r.data.issues || [];
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    cancelCreate() {
      this.showCreateForm = false;
      this.newIssue = { description: '' };
    },
    async submitIssue() {
      if (!this.newIssue.description?.trim()) return;
      this.submitting = true;
      try {
        await axios.post('/api/record-issues', {
          record_type: this.recordType,
          record_id: this.recordId,
          description: this.newIssue.description.trim(),
        });
        this.showSnack(this.$t('issue_reported_success'), 'success', 'mdi-check-circle');
        this.showCreateForm = false;
        this.newIssue = { description: '' };
        this.fetchIssues();
        this.$emit('issue-changed');
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.showSnack(msg, 'error', 'mdi-alert-circle');
      }
      this.submitting = false;
    },
    async markInProgress(issue) {
      try {
        await axios.put(`/api/record-issues/${issue.id}`, { status: 'in_progress' });
        this.showSnack(this.$t('issue_status_updated'), 'info', 'mdi-progress-wrench');
        this.fetchIssues();
        this.$emit('issue-changed');
      } catch (e) { console.error(e); }
    },
    openResolveDialog(issue) {
      this.resolvingIssue = issue;
      this.resolveForm = { resolution_note: '' };
      this.resolveDialog = true;
    },
    async resolveIssue() {
      if (!this.resolvingIssue) return;
      this.resolving = true;
      try {
        await axios.post(`/api/record-issues/${this.resolvingIssue.id}/resolve`, {
          resolution_note: this.resolveForm.resolution_note || null,
        });
        this.showSnack(this.$t('issue_resolved_success'), 'success', 'mdi-check-decagram');
        this.resolveDialog = false;
        this.resolvingIssue = null;
        this.fetchIssues();
        this.$emit('issue-changed');
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.showSnack(msg, 'error', 'mdi-alert-circle');
      }
      this.resolving = false;
    },
    canDelete(issue) {
      return issue.reported_by === this.auth.user?.id || this.auth.isSuperAdmin;
    },
    confirmDelete(issue) {
      this.deletingIssue = issue;
      this.deleteDialog = true;
    },
    async execDelete() {
      if (!this.deletingIssue) return;
      this.deleting = true;
      try {
        await axios.delete(`/api/record-issues/${this.deletingIssue.id}`);
        this.showSnack(this.$t('issue_deleted_success'), 'success', 'mdi-check-circle');
        this.deleteDialog = false;
        this.deletingIssue = null;
        this.fetchIssues();
        this.$emit('issue-changed');
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.showSnack(msg, 'error', 'mdi-alert-circle');
      }
      this.deleting = false;
    },
    getStatusColor(status) {
      return { open: '#E53935', in_progress: '#FB8C00', resolved: '#43A047' }[status] || '#757575';
    },
    getStatusIcon(status) {
      return { open: 'mdi-alert-circle', in_progress: 'mdi-progress-wrench', resolved: 'mdi-check-circle' }[status] || 'mdi-help-circle';
    },
    formatDate(d) {
      if (!d) return '-';
      const dt = new Date(d);
      return dt.toLocaleDateString(this.main.lang?.short || 'en', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    },
    showSnack(message, color, icon) {
      this.snackbar = { show: true, message, color, icon };
    },
  },
};
</script>

<style scoped>
.ri-card { font-family: 'Inter', sans-serif; }
.ri-header { border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 72px; }
.ri-header-inner { position: relative; z-index: 1; padding: 16px 22px; color: #fff; display: flex; align-items: center; gap: 12px; }
.ri-header-icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.ri-header-title { font-size: 17px; font-weight: 800; letter-spacing: .3px; }
.ri-create-form { padding: 18px; border-radius: 14px; border: 1.5px dashed rgba(var(--v-border-color), .25); background: rgba(var(--v-theme-on-surface), .02); }
.ri-timeline { position: relative; }
.ri-entry { display: flex; gap: 14px; margin-bottom: 0; }
.ri-entry--last .ri-line { display: none; }
.ri-connector { display: flex; flex-direction: column; align-items: center; min-width: 28px; }
.ri-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ri-line { width: 2px; flex: 1; background: rgba(var(--v-border-color), .15); margin: 4px 0; }
.ri-content { flex: 1; padding-bottom: 20px; min-width: 0; }
.ri-description { font-size: 13px; line-height: 1.6; padding: 10px 14px; border-radius: 10px; background: rgba(var(--v-theme-on-surface), .03); border: 1px solid rgba(var(--v-border-color), .08); word-wrap: break-word; }
.ri-resolution { font-size: 12px; padding: 8px 12px; border-radius: 8px; background: rgba(67, 160, 71, .06); border: 1px solid rgba(67, 160, 71, .12); }
</style>
