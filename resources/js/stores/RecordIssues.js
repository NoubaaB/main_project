import { defineStore } from 'pinia';
import axios from 'axios';
import { useAuth } from './Auth';
import i18n from '@/plugins/i18n';

export const useRecordIssues = defineStore('recordIssues', {
  state: () => ({
    unresolvedIssues: [],
    unresolvedCount: 0,
    myBonsCount: 0,
    reportedCount: 0,
    totalOpen: 0,
    byModule: { Charge: 0, Production: 0, Shipping: 0 },
    loading: false,
    dialogOpen: false,
    selectedScope: 'all',
    selectedModule: 'all',

    // Banner Snackbar shown on app access
    bannerSnackbar: {
      show: false,
      dismissed: false,
      title: '',
      message: '',
      count: 0,
    },

    // Resolution dialog state
    resolveDialog: {
      open: false,
      issue: null,
      resolution_note: '',
      submitting: false,
    },

    // Summary Snackbar with countdown (Rule 5)
    summarySnackbar: {
      show: false,
      type: 'success',
      title: '',
      bonReference: '',
      reporterName: '',
      resolverName: '',
      note: '',
      icon: 'mdi-check-circle',
      timer: null,
      progress: 100,
    },
  }),

  getters: {
    hasUnresolved: (state) => state.unresolvedCount > 0,
    filteredIssues: (state) => {
      if (state.selectedModule === 'all') return state.unresolvedIssues;
      return state.unresolvedIssues.filter((i) => i.record_type === state.selectedModule);
    },
  },

  actions: {
    /**
     * Check for unresolved issues upon accessing the application.
     * Triggers the Unresolved Issues Snackbar banner if any exist.
     */
    async checkOnAppAccess() {
      const auth = useAuth();
      if (!auth.is_auth) return;

      try {
        const { data } = await axios.get('/api/record-issues/unresolved-summary');
        this.unresolvedCount = data.count || 0;
        this.myBonsCount = data.my_bons_count || 0;
        this.reportedCount = data.reported_count || 0;
        this.totalOpen = data.total_open || 0;
        this.byModule = data.by_module || { Charge: 0, Production: 0, Shipping: 0 };
        if (data.recent_issues && data.recent_issues.length > 0) {
          this.unresolvedIssues = data.recent_issues;
        }

        // Set default scope: if user has issues on their own bons, default to my_bons; otherwise all
        this.selectedScope = data.my_bons_count > 0 ? 'my_bons' : 'all';

        // Show banner snackbar if user has unresolved issues and has not dismissed it this session
        if (this.unresolvedCount > 0 && !this.bannerSnackbar.dismissed) {
          const t = i18n.global.t;
          this.bannerSnackbar.show = true;
          this.bannerSnackbar.count = this.unresolvedCount;
          this.bannerSnackbar.title = t('unresolved_issues_alert_title');
          this.bannerSnackbar.message = t('unresolved_issues_alert_desc', { count: this.unresolvedCount });
        }
      } catch (err) {
        console.warn('[RecordIssues] checkOnAppAccess failed:', err);
      }
    },

    /**
     * Fetch the full list of unresolved issues.
     */
    async fetchUnresolvedIssues() {
      this.loading = true;
      try {
        const params = {
          scope: this.selectedScope,
          per_page: 100,
        };
        if (this.selectedModule !== 'all') {
          params.record_type = this.selectedModule;
        }

        const { data } = await axios.get('/api/record-issues/unresolved', { params });
        this.unresolvedIssues = data.data || [];
      } catch (err) {
        console.warn('[RecordIssues] fetchUnresolvedIssues failed:', err);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Open the full unresolved issues dialog.
     */
    openIssuesDialog() {
      this.dialogOpen = true;
      this.bannerSnackbar.show = false;
      this.fetchUnresolvedIssues();
    },

    /**
     * Close the full unresolved issues dialog.
     */
    closeIssuesDialog() {
      this.dialogOpen = false;
    },

    /**
     * Dismiss the persistent banner snackbar for this session.
     */
    dismissBanner() {
      this.bannerSnackbar.show = false;
      this.bannerSnackbar.dismissed = true;
    },

    /**
     * Open the resolution confirmation prompt.
     */
    promptResolve(issue) {
      this.resolveDialog.issue = issue;
      this.resolveDialog.resolution_note = '';
      this.resolveDialog.submitting = false;
      this.resolveDialog.open = true;
    },

    /**
     * Submit resolution for an issue.
     */
    async submitResolution() {
      const issue = this.resolveDialog.issue;
      if (!issue) return;

      this.resolveDialog.submitting = true;
      try {
        const { data } = await axios.post(`/api/record-issues/${issue.id}/resolve`, {
          resolution_note: this.resolveDialog.resolution_note,
        });

        // Close resolution dialog
        this.resolveDialog.open = false;

        // Remove resolved issue from current open list
        this.unresolvedIssues = this.unresolvedIssues.filter((i) => i.id !== issue.id);
        this.unresolvedCount = Math.max(0, this.unresolvedCount - 1);
        if (this.byModule[issue.record_type]) {
          this.byModule[issue.record_type] = Math.max(0, this.byModule[issue.record_type] - 1);
        }

        // Show Summary Snackbar with countdown (Rule 5)
        const t = i18n.global.t;
        this.triggerSummarySnackbar({
          type: 'success',
          title: t('issue_resolved_success_title'),
          bonReference: issue.bon_reference || `${issue.record_type} #${issue.record_id}`,
          reporterName: issue.reporter?.name || t('user'),
          resolverName: data.issue?.resolver?.name || t('you'),
          note: this.resolveDialog.resolution_note,
          icon: 'mdi-check-decagram',
        });
      } catch (err) {
        console.error('[RecordIssues] submitResolution failed:', err);
        const t = i18n.global.t;
        this.triggerSummarySnackbar({
          type: 'error',
          title: t('error'),
          bonReference: issue.bon_reference || '',
          reporterName: '',
          resolverName: '',
          note: err.response?.data?.message || t('action_failed'),
          icon: 'mdi-alert-circle',
        });
      } finally {
        this.resolveDialog.submitting = false;
      }
    },

    /**
     * Trigger the Rule 5 Summary Snackbar with animated countdown progress.
     */
    triggerSummarySnackbar(opts = {}) {
      if (this.summarySnackbar.timer) {
        clearInterval(this.summarySnackbar.timer);
      }

      this.summarySnackbar.show = true;
      this.summarySnackbar.type = opts.type || 'success';
      this.summarySnackbar.title = opts.title || '';
      this.summarySnackbar.bonReference = opts.bonReference || '';
      this.summarySnackbar.reporterName = opts.reporterName || '';
      this.summarySnackbar.resolverName = opts.resolverName || '';
      this.summarySnackbar.note = opts.note || '';
      this.summarySnackbar.icon = opts.icon || 'mdi-check-circle';
      this.summarySnackbar.progress = 100;

      const duration = 7000;
      const interval = 70;
      const step = (interval / duration) * 100;

      this.summarySnackbar.timer = setInterval(() => {
        this.summarySnackbar.progress -= step;
        if (this.summarySnackbar.progress <= 0) {
          clearInterval(this.summarySnackbar.timer);
          this.summarySnackbar.show = false;
        }
      }, interval);
    },

    /**
     * Real-time WebSocket event dispatcher.
     * Reactively updates counts and displays user-targeted alerts.
     */
    handleRealtimeEvent(eventData) {
      const auth = useAuth();
      const currentUserId = auth.user?.id;
      const t = i18n.global.t;

      const action = eventData.action;
      const recordData = eventData.record_data || {};
      const targetUserId = eventData.target_user_id || recordData.bon_creator_id;
      const reporterId = eventData.actor_id || recordData.reported_by || recordData.reporter_id;

      const isReported = action === 'created' || action === 'issue_reported' || action === 'issue_flagged';
      const isResolved = action === 'issue_resolved' || (action === 'updated' && recordData.status === 'resolved');
      const isDeleted = action === 'deleted';

      // ── CASE 1: Issue Reported ──────────────────────────────────────
      if (isReported) {
        const bonRef = recordData.bon_reference || `${recordData.record_type || 'Bon'} #${recordData.record_id || eventData.record_id}`;
        const reporterName = eventData.user_name || recordData.reporter?.name || t('a_user');
        const moduleType = recordData.record_type || eventData.record_type;

        // Check if the current user is the Bon creator (User B)
        const isTargetCreator = targetUserId && targetUserId === currentUserId;

        if (isTargetCreator) {
          this.unresolvedCount++;
          this.myBonsCount++;
          if (moduleType && this.byModule[moduleType] !== undefined) {
            this.byModule[moduleType]++;
          }

          // Prepend to unresolved list if open
          if (recordData.id) {
            const exists = this.unresolvedIssues.some((i) => i.id === recordData.id);
            if (!exists) {
              this.unresolvedIssues.unshift({
                ...recordData,
                reporter: recordData.reporter || { id: reporterId, name: reporterName },
              });
            }
          }

          // Trigger high-priority alert snackbar for User B
          this.triggerSummarySnackbar({
            type: 'warning',
            title: t('issue_reported_notification_title'),
            bonReference: bonRef,
            reporterName: reporterName,
            resolverName: '',
            note: recordData.description || '',
            icon: 'mdi-alert-circle',
          });

          // Also activate the banner snackbar
          this.bannerSnackbar.show = true;
          this.bannerSnackbar.count = this.unresolvedCount;
          this.bannerSnackbar.title = t('unresolved_issues_alert_title');
          this.bannerSnackbar.message = t('unresolved_issues_alert_desc', { count: this.unresolvedCount });
        } else if (auth.isSuperAdmin) {
          // Super admin also gets count increment
          this.unresolvedCount++;
          this.totalOpen++;
          if (moduleType && this.byModule[moduleType] !== undefined) {
            this.byModule[moduleType]++;
          }
          if (recordData.id) {
            const exists = this.unresolvedIssues.some((i) => i.id === recordData.id);
            if (!exists) {
              this.unresolvedIssues.unshift({
                ...recordData,
                reporter: recordData.reporter || { id: reporterId, name: reporterName },
              });
            }
          }
        }
      }

      // ── CASE 2: Issue Resolved ──────────────────────────────────────
      else if (isResolved) {
        const bonRef = recordData.bon_reference || `${recordData.record_type || 'Bon'} #${recordData.record_id || eventData.record_id}`;
        const resolverName = eventData.user_name || recordData.resolver?.name || t('a_user');
        const originalReporterId = recordData.reported_by;
        const moduleType = recordData.record_type || eventData.record_type;

        // Remove from local unresolved list
        this.unresolvedIssues = this.unresolvedIssues.filter((i) => i.id !== (recordData.id || eventData.record_id));
        this.unresolvedCount = Math.max(0, this.unresolvedCount - 1);
        if (this.myBonsCount > 0 && targetUserId === currentUserId) {
          this.myBonsCount = Math.max(0, this.myBonsCount - 1);
        }
        if (moduleType && this.byModule[moduleType] > 0) {
          this.byModule[moduleType] = Math.max(0, this.byModule[moduleType] - 1);
        }

        // If current user is User A (the reporter), notify them that User B solved it!
        if (originalReporterId && originalReporterId === currentUserId) {
          this.triggerSummarySnackbar({
            type: 'success',
            title: t('issue_resolved_by_creator_title'),
            bonReference: bonRef,
            reporterName: t('you'),
            resolverName: resolverName,
            note: recordData.resolution_note || '',
            icon: 'mdi-check-decagram',
          });
        }
      }

      // ── CASE 3: Issue Deleted ───────────────────────────────────────
      else if (isDeleted) {
        this.unresolvedIssues = this.unresolvedIssues.filter((i) => i.id !== eventData.record_id);
        this.unresolvedCount = Math.max(0, this.unresolvedCount - 1);
      }
    },
  },
});

