<template>
  <div>
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  1. APP ACCESS UNRESOLVED ISSUES BANNER SNACKBAR                      -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <v-snackbar
      v-model="recordIssues.bannerSnackbar.show"
      :timeout="-1"
      location="top center"
      elevation="16"
      rounded="xl"
      class="ri-app-access-snackbar"
      color="surface"
    >
      <div class="d-flex align-center pa-1">
        <!-- Pulsing alert avatar -->
        <div class="ri-alert-pulse-wrapper mr-3">
          <v-avatar color="error" size="38" class="elevation-2">
            <v-icon color="white" size="22" class="ri-bell-shake">mdi-alert-circle</v-icon>
          </v-avatar>
        </div>

        <!-- Text details & module breakdown -->
        <div class="flex-grow-1 pr-2">
          <div class="d-flex align-center flex-wrap ga-2">
            <span class="text-subtitle-2 font-weight-bold text-high-emphasis">
              {{ recordIssues.bannerSnackbar.title || $t('unresolved_issues_alert_title') }}
            </span>
            <v-chip size="x-small" color="error" variant="flat" class="font-weight-bold">
              {{ recordIssues.bannerSnackbar.count }} {{ $t('pending_action') }}
            </v-chip>
          </div>

          <div class="text-caption text-medium-emphasis mt-0.5">
            {{ recordIssues.bannerSnackbar.message }}
          </div>

          <!-- Module mini badges -->
          <div class="d-flex align-center flex-wrap ga-1 mt-1.5" v-if="recordIssues.byModule">
            <v-chip
              v-if="recordIssues.byModule.Charge > 0"
              size="x-small"
              variant="tonal"
              style="color: #42A5F5; background: rgba(66, 165, 245, 0.12); font-weight: 700;"
            >
              <v-icon start size="11">mdi-truck-delivery-outline</v-icon>
              {{ recordIssues.byModule.Charge }} {{ $t('charges') }}
            </v-chip>
            <v-chip
              v-if="recordIssues.byModule.Production > 0"
              size="x-small"
              variant="tonal"
              style="color: #00897B; background: rgba(0, 137, 123, 0.12); font-weight: 700;"
            >
              <v-icon start size="11">mdi-factory</v-icon>
              {{ recordIssues.byModule.Production }} {{ $t('productions') }}
            </v-chip>
            <v-chip
              v-if="recordIssues.byModule.Shipping > 0"
              size="x-small"
              variant="tonal"
              style="color: #6c66bb; background: rgba(70, 67, 160, 0.12); font-weight: 700;"
            >
              <v-icon start size="11">mdi-ship-wheel</v-icon>
              {{ recordIssues.byModule.Shipping }} {{ $t('shippings') }}
            </v-chip>
          </div>
        </div>

        <!-- Actions -->
        <div class="d-flex align-center ga-1 pl-1">
          <v-btn
            color="primary"
            variant="flat"
            size="small"
            rounded="lg"
            class="text-none font-weight-bold px-3 elevation-1"
            prepend-icon="mdi-format-list-checks"
            @click="openDialog"
          >
            {{ $t('view_issues') }}
          </v-btn>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="x-small"
            color="medium-emphasis"
            @click="dismissBanner"
          ></v-btn>
        </div>
      </div>
    </v-snackbar>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  2. SUMMARY SNACKBAR WITH COUNTDOWN PROGRESS (Rule 5)                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <v-snackbar
      v-model="recordIssues.summarySnackbar.show"
      :timeout="-1"
      location="top right"
      elevation="16"
      rounded="xl"
      class="ri-summary-snackbar"
      color="surface"
      min-width="340"
      max-width="440"
    >
      <div class="pa-2">
        <div class="d-flex align-start ga-3 mb-2">
          <!-- Status icon avatar -->
          <v-avatar
            :color="recordIssues.summarySnackbar.type === 'error' ? 'error' : (recordIssues.summarySnackbar.type === 'warning' ? 'warning' : 'success')"
            size="34"
            class="elevation-1 mt-0.5"
          >
            <v-icon color="white" size="20">{{ recordIssues.summarySnackbar.icon || 'mdi-bell-ring' }}</v-icon>
          </v-avatar>

          <!-- Text block -->
          <div class="flex-grow-1 min-w-0">
            <div class="text-subtitle-2 font-weight-bold text-high-emphasis text-truncate">
              {{ recordIssues.summarySnackbar.title }}
            </div>

            <!-- Bon reference pill -->
            <div v-if="recordIssues.summarySnackbar.bonReference" class="mt-1 d-flex align-center flex-wrap ga-1">
              <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-bold">
                <v-icon start size="11">mdi-file-document-outline</v-icon>
                {{ recordIssues.summarySnackbar.bonReference }}
              </v-chip>
            </div>

            <!-- Reporter / Resolver context -->
            <div v-if="recordIssues.summarySnackbar.reporterName || recordIssues.summarySnackbar.resolverName" class="text-caption text-medium-emphasis mt-1">
              <span v-if="recordIssues.summarySnackbar.reporterName">
                <v-icon size="12" class="mr-0.5">mdi-account</v-icon>
                {{ $t('reported_by') }}: <strong>{{ recordIssues.summarySnackbar.reporterName }}</strong>
              </span>
              <span v-if="recordIssues.summarySnackbar.resolverName" class="ml-2">
                <v-icon size="12" class="mr-0.5">mdi-account-check</v-icon>
                {{ $t('resolved_by') }}: <strong>{{ recordIssues.summarySnackbar.resolverName }}</strong>
              </span>
            </div>

            <!-- Note or description -->
            <div
              v-if="recordIssues.summarySnackbar.note"
              class="text-caption text-medium-emphasis mt-1 ri-summary-note text-truncate-2"
            >
              "{{ recordIssues.summarySnackbar.note }}"
            </div>
          </div>

          <!-- Close button -->
          <v-btn
            icon="mdi-close"
            variant="text"
            size="x-small"
            color="medium-emphasis"
            class="mt-n1 mr-n1"
            @click="closeSummary"
          ></v-btn>
        </div>

        <!-- Quick link to view dialog -->
        <div class="d-flex justify-end pt-1">
          <v-btn
            variant="text"
            size="x-small"
            color="primary"
            class="text-none font-weight-bold"
            prepend-icon="mdi-open-in-new"
            @click="openDialog"
          >
            {{ $t('view_all_issues') }}
          </v-btn>
        </div>

        <!-- Animated Progress Linear Countdown Bar (Rule 5 & claude.md) -->
        <v-progress-linear
          :model-value="recordIssues.summarySnackbar.progress"
          :color="recordIssues.summarySnackbar.type === 'error' ? 'error' : (recordIssues.summarySnackbar.type === 'warning' ? '#FFA726' : '#4CAF50')"
          height="3"
          rounded
          class="mt-2"
        ></v-progress-linear>
      </div>
    </v-snackbar>
  </div>
</template>

<script>
import { useRecordIssues } from '@/stores/RecordIssues';

export default {
  name: 'UnresolvedIssuesSnackbar',
  computed: {
    recordIssues() {
      return useRecordIssues();
    },
  },
  methods: {
    openDialog() {
      this.recordIssues.openIssuesDialog();
    },
    dismissBanner() {
      this.recordIssues.dismissBanner();
    },
    closeSummary() {
      if (this.recordIssues.summarySnackbar.timer) {
        clearInterval(this.recordIssues.summarySnackbar.timer);
      }
      this.recordIssues.summarySnackbar.show = false;
    },
  },
};
</script>

<style scoped>
.ri-app-access-snackbar :deep(.v-snackbar__wrapper),
.ri-summary-snackbar :deep(.v-snackbar__wrapper) {
  border: 1px solid rgba(128, 128, 128, 0.18) !important;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.16) !important;
}

.ri-alert-pulse-wrapper {
  position: relative;
}

.ri-bell-shake {
  animation: bellShake 3s infinite ease-in-out;
}

@keyframes bellShake {
  0%, 100% { transform: rotate(0deg); }
  10% { transform: rotate(-12deg); }
  15% { transform: rotate(12deg); }
  20% { transform: rotate(-8deg); }
  25% { transform: rotate(8deg); }
  30% { transform: rotate(0deg); }
}

.ri-summary-note {
  font-style: italic;
  background: rgba(128, 128, 128, 0.06);
  padding: 4px 8px;
  border-radius: 6px;
  border-left: 3px solid rgba(128, 128, 128, 0.3);
}

.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

