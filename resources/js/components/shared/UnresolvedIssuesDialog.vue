<template>
  <div>
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  MAIN UNRESOLVED ISSUES MODAL DIALOG                                   -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <v-dialog
      v-model="recordIssues.dialogOpen"
      max-width="920"
      scrollable
      transition="dialog-bottom-transition"
    >
      <v-card class="rounded-xl overflow-hidden elevation-24">
        <!-- Dialog Header with Gradient Accent -->
        <div class="ri-dialog-header px-5 py-4 d-flex align-center justify-space-between border-b">
          <div class="d-flex align-center ga-3">
            <v-avatar color="error" size="40" class="elevation-2">
              <v-icon color="white" size="22">mdi-alert-circle-outline</v-icon>
            </v-avatar>
            <div>
              <div class="text-h6 font-weight-bold d-flex align-center ga-2">
                <span>{{ $t('unresolved_issues_title') }}</span>
                <v-chip size="x-small" color="error" variant="flat" class="font-weight-bold">
                  {{ recordIssues.unresolvedCount }}
                </v-chip>
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ $t('unresolved_issues_subtitle') }}
              </div>
            </div>
          </div>

          <div class="d-flex align-center ga-2">
            <v-btn
              icon="mdi-refresh"
              variant="text"
              size="small"
              :loading="recordIssues.loading"
              @click="recordIssues.fetchUnresolvedIssues()"
            >
              <v-tooltip activator="parent" location="bottom">{{ $t('refresh') }}</v-tooltip>
            </v-btn>
            <v-btn
              icon="mdi-close"
              variant="text"
              size="small"
              @click="recordIssues.closeIssuesDialog()"
            ></v-btn>
          </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="px-5 py-3 border-b bg-surface-light d-flex flex-wrap align-center justify-space-between ga-3">
          <!-- Scope selector buttons -->
          <div class="d-flex align-center ga-1">
            <v-btn
              size="small"
              rounded="lg"
              :variant="recordIssues.selectedScope === 'my_bons' ? 'flat' : 'text'"
              :color="recordIssues.selectedScope === 'my_bons' ? 'primary' : undefined"
              class="text-none font-weight-bold"
              @click="changeScope('my_bons')"
            >
              <v-icon start size="16">mdi-account-file-text-outline</v-icon>
              {{ $t('my_created_bons') }}
              <v-badge
                v-if="recordIssues.myBonsCount > 0"
                :content="recordIssues.myBonsCount"
                color="error"
                inline
                class="ml-1"
              />
            </v-btn>

            <v-btn
              size="small"
              rounded="lg"
              :variant="recordIssues.selectedScope === 'all' ? 'flat' : 'text'"
              :color="recordIssues.selectedScope === 'all' ? 'primary' : undefined"
              class="text-none font-weight-bold"
              @click="changeScope('all')"
            >
              <v-icon start size="16">mdi-format-list-bulleted</v-icon>
              {{ $t('all_unresolved') }}
              <v-badge
                v-if="recordIssues.totalOpen > 0"
                :content="recordIssues.totalOpen"
                color="grey"
                inline
                class="ml-1"
              />
            </v-btn>

            <v-btn
              size="small"
              rounded="lg"
              :variant="recordIssues.selectedScope === 'reported_by_me' ? 'flat' : 'text'"
              :color="recordIssues.selectedScope === 'reported_by_me' ? 'primary' : undefined"
              class="text-none font-weight-bold"
              @click="changeScope('reported_by_me')"
            >
              <v-icon start size="16">mdi-flag-outline</v-icon>
              {{ $t('reported_by_me') }}
              <v-badge
                v-if="recordIssues.reportedCount > 0"
                :content="recordIssues.reportedCount"
                color="grey"
                inline
                class="ml-1"
              />
            </v-btn>
          </div>

          <!-- Module pills filter -->
          <div class="d-flex align-center ga-1">
            <v-chip
              size="small"
              filter
              :variant="recordIssues.selectedModule === 'all' ? 'flat' : 'outlined'"
              color="primary"
              class="font-weight-bold cursor-pointer"
              @click="changeModule('all')"
            >
              {{ $t('all_modules') }}
            </v-chip>

            <v-chip
              size="small"
              filter
              :variant="recordIssues.selectedModule === 'Charge' ? 'flat' : 'outlined'"
              style="border-color: #42A5F5; color: #42A5F5;"
              :style="recordIssues.selectedModule === 'Charge' ? 'background: #42A5F5 !important; color: white !important;' : ''"
              class="font-weight-bold cursor-pointer"
              @click="changeModule('Charge')"
            >
              <v-icon start size="13">mdi-truck-delivery-outline</v-icon>
              {{ $t('charges') }}
              <span v-if="recordIssues.byModule.Charge > 0" class="ml-1">({{ recordIssues.byModule.Charge }})</span>
            </v-chip>

            <v-chip
              size="small"
              filter
              :variant="recordIssues.selectedModule === 'Production' ? 'flat' : 'outlined'"
              style="border-color: #00897B; color: #00897B;"
              :style="recordIssues.selectedModule === 'Production' ? 'background: #00897B !important; color: white !important;' : ''"
              class="font-weight-bold cursor-pointer"
              @click="changeModule('Production')"
            >
              <v-icon start size="13">mdi-factory</v-icon>
              {{ $t('productions') }}
              <span v-if="recordIssues.byModule.Production > 0" class="ml-1">({{ recordIssues.byModule.Production }})</span>
            </v-chip>

            <v-chip
              size="small"
              filter
              :variant="recordIssues.selectedModule === 'Shipping' ? 'flat' : 'outlined'"
              style="border-color: #4643a0; color: #4643a0;"
              :style="recordIssues.selectedModule === 'Shipping' ? 'background: #4643a0 !important; color: white !important;' : ''"
              class="font-weight-bold cursor-pointer"
              @click="changeModule('Shipping')"
            >
              <v-icon start size="13">mdi-ship-wheel</v-icon>
              {{ $t('shippings') }}
              <span v-if="recordIssues.byModule.Shipping > 0" class="ml-1">({{ recordIssues.byModule.Shipping }})</span>
            </v-chip>
          </div>
        </div>

        <!-- Search Bar with AddCharge input field design style -->
        <div class="px-5 py-2.5 bg-surface border-b">
          <v-text-field
            v-model="searchQuery"
            :placeholder="$t('search_issue_or_bon')"
            density="compact"
            variant="outlined"
            rounded
            clearable
            hide-details
            prepend-inner-icon="mdi-magnify"
            color="#42A5F5"
            class="ri-search-input"
          ></v-text-field>
        </div>

        <!-- Dialog Scrollable Body -->
        <v-card-text class="pa-5" style="max-height: 580px; overflow-y: auto;">
          <!-- Skeleton Loading -->
          <div v-if="recordIssues.loading" class="d-flex flex-column ga-3">
            <div
              v-for="i in 3"
              :key="'unres-skel-' + i"
              class="rounded-xl border pa-4 bg-surface"
            >
              <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-3">
                <div class="d-flex align-center ga-2">
                  <div class="skel-bone" style="width: 85px; height: 24px; border-radius: 999px;"></div>
                  <div class="skel-bone" style="width: 140px; height: 18px; border-radius: 4px;"></div>
                  <div class="skel-bone" style="width: 70px; height: 14px; border-radius: 4px;"></div>
                </div>
                <div class="d-flex align-center ga-2">
                  <div class="skel-bone" style="width: 70px; height: 22px; border-radius: 999px;"></div>
                  <div class="skel-bone" style="width: 85px; height: 28px; border-radius: 8px;"></div>
                </div>
              </div>
              <div class="skel-bone mb-2" style="width: 90%; height: 15px; border-radius: 4px;"></div>
              <div class="skel-bone mb-3" style="width: 60%; height: 15px; border-radius: 4px;"></div>
              <div class="d-flex align-center justify-space-between pt-2 border-t">
                <div class="skel-bone" style="width: 110px; height: 12px; border-radius: 4px;"></div>
                <div class="skel-bone" style="width: 80px; height: 12px; border-radius: 4px;"></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div
            v-else-if="filteredList.length === 0"
            class="text-center py-12 px-4"
          >
            <v-avatar color="success" size="64" class="mb-3" variant="tonal">
              <v-icon size="36" color="success">mdi-check-decagram-outline</v-icon>
            </v-avatar>
            <div class="text-h6 font-weight-bold">{{ $t('no_unresolved_issues_found') }}</div>
            <div class="text-caption text-medium-emphasis mt-1 max-w-sm mx-auto">
              {{ $t('no_unresolved_issues_found_desc') }}
            </div>
          </div>

          <!-- List of Unresolved Issues -->
          <div v-else class="d-flex flex-column ga-3">
            <v-card
              v-for="issue in filteredList"
              :key="issue.id"
              class="rounded-xl border ri-issue-card overflow-hidden"
              elevation="1"
            >
              <!-- Top Module Stripe -->
              <div
                class="ri-card-stripe"
                :style="{ background: getModuleColor(issue.record_type) }"
              ></div>

              <div class="pa-4">
                <div class="d-flex align-start justify-space-between flex-wrap ga-2 mb-2">
                  <!-- Bon Badge & Direct Link -->
                  <div class="d-flex align-center flex-wrap ga-2">
                    <v-chip
                      size="small"
                      variant="flat"
                      class="font-weight-bold"
                      :style="{
                        background: getModuleBg(issue.record_type),
                        color: getModuleColor(issue.record_type),
                        border: '1px solid ' + getModuleColor(issue.record_type) + '33'
                      }"
                    >
                      <v-icon start size="14">{{ getModuleIcon(issue.record_type) }}</v-icon>
                      {{ issue.record_type }}
                    </v-chip>

                    <!-- Clickable Bon Reference Title -->
                    <a
                      href="#"
                      class="text-subtitle-2 font-weight-bold text-decoration-none ri-bon-link"
                      @click.prevent="navigateToBon(issue)"
                    >
                      <v-icon size="14" class="mr-1">mdi-open-in-new</v-icon>
                      {{ issue.bon_reference || `${issue.record_type} #${issue.record_id}` }}
                    </a>

                    <!-- Bon Date -->
                    <span v-if="issue.bon_date" class="text-caption text-medium-emphasis">
                      <v-icon size="12" class="mr-0.5">mdi-calendar</v-icon>
                      {{ issue.bon_date }}
                    </span>
                  </div>

                  <!-- Creator / User B badge -->
                  <div>
                    <v-chip
                      v-if="isMyBon(issue)"
                      size="x-small"
                      color="primary"
                      variant="tonal"
                      class="font-weight-bold"
                    >
                      <v-icon start size="12">mdi-account-check</v-icon>
                      {{ $t('created_by_you') }}
                    </v-chip>
                    <v-chip
                      v-else-if="issue.bon_creator"
                      size="x-small"
                      variant="outlined"
                      color="medium-emphasis"
                    >
                      <v-icon start size="12">mdi-account-outline</v-icon>
                      {{ $t('creator') }}: {{ issue.bon_creator.name }}
                    </v-chip>
                  </div>
                </div>

                <!-- Issue Description -->
                <div class="ri-issue-description pa-3 rounded-lg my-2">
                  <div class="d-flex align-start ga-2">
                    <v-icon size="18" color="error" class="mt-0.5">mdi-alert-circle</v-icon>
                    <div class="text-body-2 font-weight-medium flex-grow-1">
                      {{ issue.description }}
                    </div>
                  </div>
                </div>

                <!-- Footer Metadata & Resolve Button -->
                <div class="d-flex align-center justify-space-between flex-wrap ga-2 pt-2 border-t mt-3">
                  <!-- Reporter & Time -->
                  <div class="d-flex align-center ga-3 text-caption text-medium-emphasis">
                    <span>
                      <v-icon size="13" class="mr-1">mdi-account</v-icon>
                      {{ $t('reported_by') }}: <strong>{{ issue.reporter?.name || '-' }}</strong>
                    </span>
                    <span>
                      <v-icon size="13" class="mr-1">mdi-clock-outline</v-icon>
                      {{ formatTime(issue.created_at) }}
                    </span>
                  </div>

                  <!-- Action Buttons -->
                  <div class="d-flex align-center ga-2">
                    <v-btn
                      variant="tonal"
                      size="small"
                      rounded="lg"
                      class="text-none font-weight-bold"
                      @click="navigateToBon(issue)"
                    >
                      <v-icon start size="15">mdi-eye-outline</v-icon>
                      {{ $t('view_bon_details') }}
                    </v-btn>

                    <v-btn
                      color="success"
                      variant="flat"
                      size="small"
                      rounded="lg"
                      class="text-none font-weight-bold elevation-1"
                      prepend-icon="mdi-check-decagram"
                      @click="recordIssues.promptResolve(issue)"
                    >
                      {{ $t('resolve_issue') }}
                    </v-btn>
                  </div>
                </div>
              </div>
            </v-card>
          </div>
        </v-card-text>

        <!-- Dialog Footer -->
        <div class="px-5 py-3 border-t bg-surface-light d-flex align-center justify-space-between">
          <div class="text-caption text-medium-emphasis">
            {{ $t('unresolved_count_label', { count: filteredList.length }) }}
          </div>
          <v-btn
            variant="tonal"
            rounded="lg"
            size="small"
            class="text-none font-weight-bold"
            @click="recordIssues.closeIssuesDialog()"
          >
            {{ $t('close') }}
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  RESOLUTION CONFIRMATION DIALOG (Input field AddCharge style)         -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <v-dialog
      v-model="recordIssues.resolveDialog.open"
      max-width="520"
      persistent
      transition="dialog-bottom-transition"
    >
      <v-card class="rounded-xl overflow-hidden elevation-24">
        <!-- Header -->
        <div class="px-5 py-4 d-flex align-center ga-3 bg-success-light border-b">
          <v-avatar color="success" size="38" class="elevation-1">
            <v-icon color="white" size="22">mdi-check-decagram</v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold text-success-darken-2">
              {{ $t('resolve_issue_title') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ recordIssues.resolveDialog.issue?.bon_reference || `${recordIssues.resolveDialog.issue?.record_type} #${recordIssues.resolveDialog.issue?.record_id}` }}
            </div>
          </div>
        </div>

        <v-card-text class="pa-5">
          <!-- Issue Summary Box -->
          <div class="pa-3 rounded-lg border mb-4 bg-surface-light">
            <div class="text-caption font-weight-bold text-medium-emphasis mb-1">
              {{ $t('issue_being_resolved') }}:
            </div>
            <div class="text-body-2 font-weight-medium">
              "{{ recordIssues.resolveDialog.issue?.description }}"
            </div>
            <div class="text-caption text-disabled mt-1">
              {{ $t('reported_by') }}: {{ recordIssues.resolveDialog.issue?.reporter?.name || '-' }}
            </div>
          </div>

          <!-- Resolution Note Input (AddCharge style: variant="outlined", rounded, density="comfortable") -->
          <div class="mb-2">
            <label class="text-caption font-weight-bold d-block mb-1 text-high-emphasis">
              {{ $t('resolution_note_label') }}
            </label>
            <v-textarea
              v-model="recordIssues.resolveDialog.resolution_note"
              :label="$t('resolution_note')"
              :placeholder="$t('resolution_note_placeholder')"
              :counter="2000"
              :maxlength="2000"
              rows="3"
              density="compact"
              variant="outlined"
              rounded
              color="success"
              auto-grow
              hide-details="auto"
            ></v-textarea>
          </div>

          <v-alert
            type="info"
            variant="tonal"
            density="compact"
            rounded="lg"
            class="text-caption mt-3"
            icon="mdi-information-outline"
          >
            {{ $t('resolve_notification_notice') }}
          </v-alert>
        </v-card-text>

        <!-- Actions -->
        <div class="px-5 py-3 border-t bg-surface-light d-flex justify-end ga-2">
          <v-btn
            variant="text"
            rounded="lg"
            size="small"
            class="text-none font-weight-bold"
            :disabled="recordIssues.resolveDialog.submitting"
            @click="recordIssues.resolveDialog.open = false"
          >
            {{ $t('cancel') }}
          </v-btn>

          <v-btn
            color="success"
            variant="flat"
            rounded="lg"
            size="small"
            class="text-none font-weight-bold elevation-1 px-4"
            prepend-icon="mdi-check-decagram"
            :loading="recordIssues.resolveDialog.submitting"
            @click="recordIssues.submitResolution()"
          >
            {{ $t('confirm_resolution') }}
          </v-btn>
        </div>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import { useRecordIssues } from '@/stores/RecordIssues';
import { useAuth } from '@/stores/Auth';
import { useNotificationCenter } from '@/stores/NotificationCenter';

export default {
  name: 'UnresolvedIssuesDialog',
  data() {
    return {
      searchQuery: '',
    };
  },
  computed: {
    recordIssues() {
      return useRecordIssues();
    },
    auth() {
      return useAuth();
    },
    notifCenter() {
      return useNotificationCenter();
    },
    filteredList() {
      let list = this.recordIssues.filteredIssues || [];
      if (this.searchQuery && this.searchQuery.trim()) {
        const q = this.searchQuery.trim().toLowerCase();
        list = list.filter((i) => {
          const desc = (i.description || '').toLowerCase();
          const ref = (i.bon_reference || '').toLowerCase();
          const rep = (i.reporter?.name || '').toLowerCase();
          const type = (i.record_type || '').toLowerCase();
          return desc.includes(q) || ref.includes(q) || rep.includes(q) || type.includes(q);
        });
      }
      return list;
    },
  },
  methods: {
    changeScope(scope) {
      this.recordIssues.selectedScope = scope;
      this.recordIssues.fetchUnresolvedIssues();
    },
    changeModule(module) {
      this.recordIssues.selectedModule = module;
      this.recordIssues.fetchUnresolvedIssues();
    },
    isMyBon(issue) {
      return issue.bon_creator_id && this.auth.user?.id && issue.bon_creator_id === this.auth.user.id;
    },
    getModuleColor(type) {
      switch (type) {
        case 'Charge':
          return '#42A5F5';
        case 'Production':
          return '#00897B';
        case 'Shipping':
          return '#4643a0';
        default:
          return '#1565C0';
      }
    },
    getModuleBg(type) {
      switch (type) {
        case 'Charge':
          return 'rgba(66, 165, 245, 0.12)';
        case 'Production':
          return 'rgba(0, 137, 123, 0.12)';
        case 'Shipping':
          return 'rgba(70, 67, 160, 0.12)';
        default:
          return 'rgba(21, 101, 192, 0.12)';
      }
    },
    getModuleIcon(type) {
      switch (type) {
        case 'Charge':
          return 'mdi-truck-delivery-outline';
        case 'Production':
          return 'mdi-factory';
        case 'Shipping':
          return 'mdi-ship-wheel';
        default:
          return 'mdi-file-document-outline';
      }
    },
    formatTime(dateStr) {
      if (!dateStr) return '';
      try {
        return this.notifCenter.getRelativeTime(dateStr, this.$t);
      } catch (e) {
        return dateStr;
      }
    },
    navigateToBon(issue) {
      this.recordIssues.closeIssuesDialog();
      const type = issue.record_type;
      const id = issue.record_id;
      if (type === 'Charge') {
        this.$router.push({ name: 'view_charge', params: { id } });
      } else if (type === 'Production') {
        this.$router.push({ name: 'view_production', params: { id } });
      } else if (type === 'Shipping') {
        this.$router.push({ name: 'view_shipping', params: { id } });
      }
    },
  },
};
</script>

<style scoped>
.ri-dialog-header {
  background: linear-gradient(135deg, rgba(211, 47, 47, 0.08) 0%, rgba(21, 101, 192, 0.04) 100%);
}

.bg-surface-light {
  background: rgba(128, 128, 128, 0.035);
}

.bg-success-light {
  background: rgba(76, 175, 80, 0.08);
}

.ri-issue-card {
  position: relative;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.ri-issue-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
}

.ri-card-stripe {
  height: 4px;
  width: 100%;
}

.ri-bon-link {
  color: inherit;
  transition: color 0.15s ease;
}

.ri-bon-link:hover {
  color: #1976D2 !important;
  text-decoration: underline !important;
}

.ri-issue-description {
  background: rgba(128, 128, 128, 0.05);
  border-left: 3px solid #E53935;
}
</style>
