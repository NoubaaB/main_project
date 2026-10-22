<template>
  <v-card class="wam-card" elevation="0">
    <!-- Hero Header (claude.md: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)) -->
    <div class="wam-hero">
      <div class="wam-hero-inner">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3">
          <div class="d-flex align-center ga-4">
            <div class="wam-hero-icon">
              <v-icon size="28" color="white">mdi-account-clock-outline</v-icon>
            </div>
            <div>
              <div class="wam-hero-title">{{ $t('workforce_absence_movement_title') }}</div>
              <div class="text-white text-caption" style="opacity: .85;">
                {{ $t('workforce_absence_movement_desc') }}
              </div>
            </div>
          </div>

          <!-- Hero Right: Quick Stats & Navigation Links -->
          <div class="d-flex flex-wrap align-center ga-2">
            <v-chip
              variant="flat"
              color="rgba(255,255,255,0.2)"
              class="text-white font-weight-bold"
              size="small"
              prepend-icon="mdi-timer-sand"
            >
              {{ $t('threshold') }}: {{ movementStore.thresholdDays }} {{ $t('days') }}
            </v-chip>

            <v-btn
              variant="tonal"
              color="white"
              size="small"
              rounded
              prepend-icon="mdi-cog-outline"
              @click="openThresholdQuickDialog"
            >
              {{ $t('change_threshold') }}
            </v-btn>

            <v-btn
              variant="flat"
              color="white"
              class="text-primary font-weight-bold"
              size="small"
              rounded
              prepend-icon="mdi-calendar-check"
              @click="$router.push('/fortnight_management')"
            >
              {{ $t('fortnight_management') }}
            </v-btn>
          </div>
        </div>
      </div>
    </div>

    <!-- =========== 4 EXECUTIVE KPI CARDS =========== -->
    <div class="px-4 pt-4">
      <v-row dense class="mb-2">
        <!-- KPI 1: Currently Absent -->
        <v-col cols="12" sm="6" lg="3">
          <div class="wam-kpi-card wam-kpi--absent" @click="statusFilter = 'absent'">
            <div class="wam-kpi-accent"></div>
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="wam-kpi-lbl">{{ $t('currently_absent') }}</span>
              <div class="wam-kpi-icon wam-kpi-icon--absent">
                <v-icon color="#E65100" size="22">mdi-account-alert-outline</v-icon>
              </div>
            </div>
            <div class="wam-kpi-val text-warning">{{ movementStore.summary.absent_count || 0 }}</div>
            <div class="wam-kpi-sub">
              <v-icon size="13" color="#E65100" class="mr-1">mdi-timer-alert-outline</v-icon>
              <span>{{ $t('inactive_over_days', { days: movementStore.thresholdDays }) }}</span>
            </div>
          </div>
        </v-col>

        <!-- KPI 2: Active Workforces -->
        <v-col cols="12" sm="6" lg="3">
          <div class="wam-kpi-card wam-kpi--active" @click="statusFilter = 'active'">
            <div class="wam-kpi-accent"></div>
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="wam-kpi-lbl">{{ $t('active_workforces') }}</span>
              <div class="wam-kpi-icon wam-kpi-icon--active">
                <v-icon color="#00897B" size="22">mdi-account-check-outline</v-icon>
              </div>
            </div>
            <div class="wam-kpi-val" style="color: #00897B;">{{ movementStore.summary.active_count || 0 }}</div>
            <div class="wam-kpi-sub">
              <v-icon size="13" color="#00897B" class="mr-1">mdi-shield-check</v-icon>
              <span>{{ $t('active_within_threshold') }}</span>
            </div>
          </div>
        </v-col>

        <!-- KPI 3: Left Company -->
        <v-col cols="12" sm="6" lg="3">
          <div class="wam-kpi-card wam-kpi--left" @click="statusFilter = 'left_company'">
            <div class="wam-kpi-accent"></div>
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="wam-kpi-lbl">{{ $t('left_company') }}</span>
              <div class="wam-kpi-icon wam-kpi-icon--left">
                <v-icon color="#D32F2F" size="22">mdi-account-arrow-right-outline</v-icon>
              </div>
            </div>
            <div class="wam-kpi-val" style="color: #D32F2F;">{{ movementStore.summary.left_company_count || 0 }}</div>
            <div class="wam-kpi-sub">
              <v-icon size="13" color="#D32F2F" class="mr-1">mdi-account-off-outline</v-icon>
              <span>{{ $t('detached_from_teams') }}</span>
            </div>
          </div>
        </v-col>

        <!-- KPI 4: Total Absence Incidents & Days -->
        <v-col cols="12" sm="6" lg="3">
          <div class="wam-kpi-card wam-kpi--blue" @click="statusFilter = 'all'">
            <div class="wam-kpi-accent"></div>
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="wam-kpi-lbl">{{ $t('total_absence_tracking') }}</span>
              <div class="wam-kpi-icon wam-kpi-icon--blue">
                <v-icon color="#1565C0" size="22">mdi-calendar-remove-outline</v-icon>
              </div>
            </div>
            <div class="wam-kpi-val" style="color: #1565C0;">
              {{ movementStore.summary.total_absence_days || 0 }} <span class="wam-kpi-unit">{{ $t('days') }}</span>
            </div>
            <div class="wam-kpi-sub">
              <v-icon size="13" color="#1565C0" class="mr-1">mdi-counter</v-icon>
              <span>{{ movementStore.summary.total_absence_incidents || 0 }} {{ $t('incidents_recorded') }}</span>
            </div>
          </div>
        </v-col>
      </v-row>
    </div>

    <!-- =========== TOOLBAR & FILTER BAR =========== -->
    <div class="wam-toolbar mx-4 mt-2 mb-3 pa-3 rounded-xl border d-flex flex-wrap align-center justify-space-between ga-3">
      <!-- Left: Status Filter Toggle -->
      <div class="d-flex flex-wrap align-center ga-2">
        <SegmentedPillToggle
          v-model="statusFilter"
          :items="[
            { value: 'all', label: $t('all_workforces'), icon: 'mdi-account-multiple', badge: movementStore.absences.length },
            { value: 'absent', label: $t('absent'), icon: 'mdi-account-alert-outline', badge: movementStore.summary.absent_count },
            { value: 'active', label: $t('active'), icon: 'mdi-account-check-outline', badge: movementStore.summary.active_count },
            { value: 'left_company', label: $t('left_company'), icon: 'mdi-account-arrow-right-outline', badge: movementStore.summary.left_company_count }
          ]"
          color="#1565C0"
        />
      </div>

      <!-- Right: Search Field & Quick Refresh -->
      <div class="d-flex align-center ga-3 flex-wrap">
        <v-text-field
          v-model="searchQuery"
          density="compact"
          variant="outlined"
          rounded
          color="#42A5F5"
          class="wam-input-field"
          :placeholder="$t('search_workforce_or_motif') + '...'"
          prepend-inner-icon="mdi-magnify"
          clearable
          hide-details
          style="min-width: 240px; max-width: 320px;"
        ></v-text-field>

        <v-btn
          icon
          size="small"
          variant="tonal"
          color="#1565C0"
          :loading="movementStore.loading"
          @click="refreshData"
          :title="$t('refresh')"
        >
          <v-icon size="18">mdi-refresh</v-icon>
        </v-btn>
      </div>
    </div>

    <!-- =========== DATA TABLE =========== -->
    <div class="px-4 pb-4">
      <v-card variant="outlined" class="rounded-xl border" color="rgba(66, 165, 245, 0.2)">
        <v-data-table-virtual
          :headers="headers"
          :items="processedWorkforces"
          :height="tableHeight"
          fixed-header
          item-value="id"
          v-model:sort-by="sortBy"
          :loading="movementStore.loading || isWorkerFiltering"
          density="comfortable"
          hover
          class="wam-table"
        >
          <!-- Headers Slot with Sorting and Column Multiselect Filter Row -->
          <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
            <tr class="wam-header-row">
              <th
                v-for="col in columns"
                :key="col.key"
                class="wam-th"
                :class="col.align ? `text-${col.align}` : ''"
              >
                <div
                  v-if="col.key !== 'actions'"
                  class="wam-th-label d-inline-flex align-center"
                  @click="col.sortable !== false ? toggleSort(col) : null"
                  :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
                >
                  <span>{{ col.title }}</span>
                  <v-icon
                    v-if="isSorted(col)"
                    size="15"
                    color="#1565C0"
                    class="ms-1"
                  >
                    {{ getSortIcon(col) }}
                  </v-icon>
                  <v-icon
                    v-else-if="col.sortable !== false"
                    size="13"
                    class="ms-1 wam-sort-idle"
                  >
                    mdi-unfold-more-horizontal
                  </v-icon>
                </div>
                <div v-else class="wam-th-label text-end">
                  <span>{{ col.title }}</span>
                </div>
              </th>
            </tr>
            <tr class="wam-filter-row">
              <th
                v-for="col in columns"
                :key="col.key + '-filter'"
                class="wam-filter-th"
                :class="col.align ? `text-${col.align}` : ''"
              >
                <v-autocomplete
                  v-if="col.key !== 'actions'"
                  v-model="columnFilters[col.key]"
                  :items="columnOptions[col.key] || []"
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#42A5F5"
                  class="wam-col-filter text-workforce"
                  hide-details
                  clearable
                  multiple
                  chips
                  closable-chips
                  :placeholder="$t('filter')"
                  :no-data-text="$t('no_options_found')"
                >
                  <template #chip="{ item, props }">
                    <v-chip
                      v-bind="props"
                      size="x-small"
                      color="#42A5F5"
                      variant="tonal"
                      closable
                    >
                      {{ item.title }}
                    </v-chip>
                  </template>
                </v-autocomplete>
                <div v-else-if="hasActiveColumnFilters" class="d-flex justify-end">
                  <v-btn
                    size="x-small"
                    variant="tonal"
                    color="error"
                    prepend-icon="mdi-filter-remove"
                    @click="clearAllColumnFilters"
                    :title="$t('clear_all_filters')"
                  >
                    {{ $t('reset') }}
                  </v-btn>
                </div>
              </th>
            </tr>
          </template>

          <!-- Worker Column: Avatar, Name, Matricule, CNI -->
          <template v-slot:[`item.full_name`]="{ item }">
            <div class="d-flex align-center ga-3 py-2">
              <v-avatar
                size="36"
                :color="item.status === 'absent' ? 'warning' : item.status === 'left_company' ? 'error' : '#1565C0'"
                variant="tonal"
                class="font-weight-bold"
              >
                {{ (item.full_name || 'W').charAt(0).toUpperCase() }}
              </v-avatar>
              <div>
                <div class="font-weight-bold text-body-2 d-flex align-center ga-2">
                  <span>{{ item.full_name }}</span>
                  <v-chip v-if="item.is_absent_by_threshold && item.status !== 'left_company'" size="x-small" color="warning" variant="flat" label>
                    {{ $t('auto_flagged') }}
                  </v-chip>
                </div>
                <div class="text-caption text-medium-emphasis">
                  <span v-if="item.matricule" class="mr-2">#{{ item.matricule }}</span>
                  <span v-if="item.cni" class="mr-2">CNI: {{ item.cni }}</span>
                </div>
              </div>
            </div>
          </template>

          <!-- Status Column -->
          <template v-slot:[`item.status`]="{ item }">
            <v-chip
              v-if="item.status === 'active'"
              size="small"
              color="success"
              variant="tonal"
              prepend-icon="mdi-check-circle"
              class="font-weight-bold"
            >
              {{ $t('active') }}
            </v-chip>
            <v-chip
              v-else-if="item.status === 'absent'"
              size="small"
              color="warning"
              variant="flat"
              prepend-icon="mdi-alert-circle"
              class="font-weight-bold"
            >
              {{ $t('absent') }} ({{ item.days_since_last_activity }} {{ $t('days') }})
            </v-chip>
            <v-chip
              v-else-if="item.status === 'left_company'"
              size="small"
              color="error"
              variant="tonal"
              prepend-icon="mdi-account-remove"
              class="font-weight-bold"
            >
              {{ $t('left_company') }}
            </v-chip>
          </template>

          <!-- Teams Affiliation Column -->
          <template v-slot:[`item.teams`]="{ item }">
            <div v-if="item.status === 'left_company'" class="text-caption text-medium-emphasis font-italic">
              <v-icon size="14" color="grey" class="mr-1">mdi-link-off</v-icon>
              {{ $t('detached') }} ({{ item.previous_team_ids?.length || 0 }} {{ $t('previous') }})
            </div>
            <div v-else-if="item.teams && item.teams.length > 0" class="d-flex flex-wrap ga-1">
              <v-chip
                v-for="t in item.teams"
                :key="'t-' + t.id"
                size="x-small"
                color="#1565C0"
                variant="tonal"
                prepend-icon="mdi-account-group"
              >
                {{ t.name }}
              </v-chip>
            </div>
            <span v-else class="text-caption text-medium-emphasis">-</span>
          </template>

          <!-- Last Activity Date -->
          <template v-slot:[`item.last_activity_date`]="{ item }">
            <div v-if="item.last_activity_date">
              <div class="font-weight-medium text-body-2">
                {{ formatDate(item.last_activity_date) }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ item.days_since_last_activity }} {{ $t('days_ago') }}
              </div>
            </div>
            <span v-else class="text-caption text-medium-emphasis font-italic">
              {{ $t('no_activities_recorded') }}
            </span>
          </template>

          <!-- Total Absence Metrics: Count & Total Days -->
          <template v-slot:[`item.total_absence_days`]="{ item }">
            <div class="text-center">
              <v-chip
                size="small"
                :color="item.total_absence_days > 0 ? (item.status === 'absent' ? 'warning' : '#1565C0') : 'grey'"
                variant="tonal"
                class="font-weight-bold"
              >
                <v-icon start size="14">mdi-timer-sand</v-icon>
                {{ item.total_absence_days }} {{ $t('days') }}
              </v-chip>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ item.absence_count }} {{ $t('incidents') }}
              </div>
            </div>
          </template>

          <!-- Latest Motif Column -->
          <template v-slot:[`item.latest_motif`]="{ item }">
            <div v-if="item.status === 'left_company'">
              <v-chip size="x-small" color="error" variant="outlined" label>
                {{ item.departure_reason || $t('departure') }}
              </v-chip>
              <div v-if="item.departure_date" class="text-caption text-medium-emphasis mt-1">
                {{ formatDate(item.departure_date) }}
              </div>
            </div>
            <div v-else-if="item.latest_motif">
              <v-chip size="x-small" color="warning" variant="tonal" label>
                <v-icon start size="12">mdi-note-text</v-icon>
                {{ item.latest_motif }}
              </v-chip>
            </div>
            <span v-else-if="item.status === 'absent'" class="text-caption text-warning font-italic">
              {{ $t('motif_unreported') }}
            </span>
            <span v-else class="text-caption text-medium-emphasis">-</span>
          </template>

          <!-- Actions Column -->
          <template v-slot:[`item.actions`]="{ item }">
            <div class="d-flex align-center justify-end ga-1">
              <!-- Report Motif Button -->
              <v-btn
                icon
                size="x-small"
                variant="tonal"
                color="warning"
                :title="$t('report_absence_motif')"
                @click="openMotifDialog(item)"
              >
                <v-icon size="16">mdi-note-edit-outline</v-icon>
              </v-btn>

              <!-- Mark as Left Company Button (if not already left) -->
              <v-btn
                v-if="item.status !== 'left_company'"
                icon
                size="x-small"
                variant="tonal"
                color="error"
                :title="$t('mark_as_leave_company')"
                @click="openLeaveCompanyDialog(item)"
              >
                <v-icon size="16">mdi-account-remove-outline</v-icon>
              </v-btn>

              <!-- Mark as Back to Work Button (if absent or left_company) -->
              <v-btn
                v-if="item.status === 'absent' || item.status === 'left_company'"
                icon
                size="x-small"
                variant="flat"
                color="success"
                :title="$t('mark_as_back_to_work')"
                @click="openReturnToWorkDialog(item)"
              >
                <v-icon size="16">mdi-account-arrow-left-outline</v-icon>
              </v-btn>

              <!-- View Movement History Button -->
              <v-btn
                icon
                size="x-small"
                variant="tonal"
                color="#1565C0"
                :title="$t('view_movement_history')"
                @click="openHistoryDialog(item)"
              >
                <v-icon size="16">mdi-history</v-icon>
              </v-btn>
            </div>
          </template>
        </v-data-table-virtual>

        <!-- Table Footer: Status info without pagination -->
        <div class="wam-table-footer d-flex align-center justify-space-between flex-wrap px-4 py-2 border-t">
          <div class="text-caption text-medium-emphasis d-flex align-center ga-2">
            <v-icon size="16" color="#1565C0">mdi-table</v-icon>
            <span>
              {{ $t('showing') }}
              <strong class="text-high-emphasis">{{ processedWorkforces.length }}</strong>
              <template v-if="processedWorkforces.length !== baseWorkforcesCount">
                {{ $t('of') }} <strong class="text-high-emphasis">{{ baseWorkforcesCount }}</strong>
              </template>
              {{ $t('workforces') }}
            </span>
            <v-chip
              v-if="hasActiveColumnFilters"
              size="x-small"
              color="#1565C0"
              variant="tonal"
              class="font-weight-medium"
            >
              {{ activeFiltersCount }} {{ $t('active_filters') }}
            </v-chip>
          </div>

          <div v-if="hasActiveColumnFilters" class="d-flex align-center ga-2">
            <v-btn
              size="x-small"
              variant="text"
              color="error"
              prepend-icon="mdi-filter-remove"
              @click="clearAllColumnFilters"
            >
              {{ $t('clear_all_filters') }}
            </v-btn>
          </div>
        </div>
      </v-card>
    </div>

    <!-- ================= DIALOG 1: RECORD ABSENCE MOTIF ================= -->
    <v-dialog v-model="motifDialog.show" max-width="620" persistent scrollable>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4" style="background: rgba(230, 81, 0, 0.08);">
          <v-icon color="#E65100" class="mr-2">mdi-note-edit-outline</v-icon>
          <span class="font-weight-bold" style="color: #E65100;">{{ $t('report_absence_motif') }}</span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="motifDialog.show = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider></v-divider>

        <v-card-text class="pa-4" style="max-height: 72vh;">
          <div v-if="selectedWorker" class="pa-3 rounded-lg mb-4 d-flex align-center ga-3" style="background: rgba(var(--v-theme-surface), 0.5); border: 1px solid rgba(0,0,0,0.08);">
            <v-avatar size="38" color="#1565C0" variant="tonal" class="font-weight-bold">
              {{ selectedWorker.full_name?.charAt(0) }}
            </v-avatar>
            <div>
              <div class="font-weight-bold">{{ selectedWorker.full_name }}</div>
              <div class="text-caption text-medium-emphasis">
                #{{ selectedWorker.matricule }} | {{ selectedWorker.cni || $t('no_cin') }}
              </div>
            </div>
          </div>

          <v-row dense>
            <!-- Reason / Motif Selection -->
            <v-col cols="12">
              <label class="text-caption font-weight-bold mb-1 d-block text-primary">
                {{ $t('absence_reason_motif') }} *
              </label>
              <v-combobox
                v-model="motifDialog.form.reason"
                :items="absenceMotifPresets"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                prepend-inner-icon="mdi-tag-text-outline"
                :placeholder="$t('select_or_type_motif')"
              ></v-combobox>
            </v-col>

            <!-- Ongoing Absence Toggle Switch Card -->
            <v-col cols="12">
              <div
                class="wam-switch-card d-flex align-center justify-space-between pa-3 rounded-xl border mb-1"
                :class="{ 'wam-switch-active': motifDialog.form.is_ongoing }"
              >
                <div class="d-flex align-center ga-3">
                  <v-avatar size="32" :color="motifDialog.form.is_ongoing ? '#42A5F5' : 'grey-lighten-2'">
                    <v-icon size="18" :color="motifDialog.form.is_ongoing ? 'white' : 'grey-darken-1'">
                      {{ motifDialog.form.is_ongoing ? 'mdi-clock-fast' : 'mdi-calendar-check' }}
                    </v-icon>
                  </v-avatar>
                  <div>
                    <div class="text-body-2 font-weight-bold" :style="{ color: motifDialog.form.is_ongoing ? '#1565C0' : 'inherit' }">
                      {{ $t('ongoing_absence') }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ motifDialog.form.is_ongoing ? $t('absence_has_no_end_date') : $t('specify_end_date') }}
                    </div>
                  </div>
                </div>
                <v-switch
                  v-model="motifDialog.form.is_ongoing"
                  color="#42A5F5"
                  hide-details
                  density="compact"
                  inset
                  @update:model-value="onOngoingToggle"
                ></v-switch>
              </div>
            </v-col>

            <!-- Date Range: Start Date & End Date (VCalendar) -->
            <v-col cols="12">
              <div class="wam-datepicker-card rounded-xl border pa-3 bg-surface mb-2">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="d-flex align-center ga-2">
                    <v-icon size="18" color="#42A5F5">
                      {{ motifDialog.form.is_ongoing ? 'mdi-calendar-start' : 'mdi-calendar-range' }}
                    </v-icon>
                    <span class="text-caption font-weight-bold text-primary">
                      {{ motifDialog.form.is_ongoing ? $t('start_date') : $t('date_range') }}
                    </span>
                  </div>
                  <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-medium">
                    {{ motifDateSummary }}
                  </v-chip>
                </div>

                <!-- VCalendar DatePicker: Range mode when NOT ongoing, Single mode when ongoing -->
                <DatePicker
                  v-if="!motifDialog.form.is_ongoing"
                  v-model.range="motifDateRangeVC"
                  :is-dark="main.is_dark"
                  :locale="main.lang?.short || 'en'"
                  mode="date"
                  color="blue"
                  expanded
                  borderless
                  class="w-100 rounded-lg mb-2"
                  @update:model-value="onMotifRangeChange"
                />
                <DatePicker
                  v-else
                  v-model="motifSingleDateVC"
                  :is-dark="main.is_dark"
                  :locale="main.lang?.short || 'en'"
                  mode="date"
                  color="blue"
                  expanded
                  borderless
                  class="w-100 rounded-lg mb-2"
                  @update:model-value="onMotifSingleDateChange"
                />

                <!-- Synchronized Inputs with AddCharge design style -->
                <v-row dense class="mt-1 pt-2 border-t">
                  <v-col cols="12" sm="6">
                    <label class="text-caption font-weight-bold mb-1 d-block text-medium-emphasis">
                      {{ $t('start_date') }} *
                    </label>
                    <v-text-field
                      v-model="motifDialog.form.start_date"
                      type="date"
                      density="compact"
                      variant="outlined"
                      rounded
                      color="#42A5F5"
                      class="wam-input-field"
                      prepend-inner-icon="mdi-calendar-start"
                      @update:model-value="onManualStartDateChange"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <label class="text-caption font-weight-bold mb-1 d-block text-medium-emphasis">
                      {{ $t('end_date') }}
                    </label>
                    <v-text-field
                      v-if="!motifDialog.form.is_ongoing"
                      v-model="motifDialog.form.end_date"
                      type="date"
                      density="compact"
                      variant="outlined"
                      rounded
                      color="#42A5F5"
                      class="wam-input-field"
                      prepend-inner-icon="mdi-calendar-end"
                      @update:model-value="onManualEndDateChange"
                    ></v-text-field>
                    <v-text-field
                      v-else
                      :model-value="$t('ongoing')"
                      density="compact"
                      variant="outlined"
                      rounded
                      disabled
                      color="#42A5F5"
                      class="wam-input-field"
                      prepend-inner-icon="mdi-clock-fast"
                    >
                      <template #append-inner>
                        <v-chip size="x-small" color="#42A5F5" variant="flat" class="text-white font-weight-bold">
                          {{ $t('in_progress') }}
                        </v-chip>
                      </template>
                    </v-text-field>
                  </v-col>
                </v-row>
              </div>
            </v-col>

            <!-- Days Count (Auto-computed or manual override) -->
            <v-col cols="12">
              <label class="text-caption font-weight-bold mb-1 d-block text-medium-emphasis">
                {{ $t('days_count') }}
              </label>
              <v-text-field
                v-model.number="motifDialog.form.days_count"
                type="number"
                min="1"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                :suffix="$t('days')"
                prepend-inner-icon="mdi-counter"
              ></v-text-field>
            </v-col>

            <!-- Notes / Comments -->
            <v-col cols="12">
              <label class="text-caption font-weight-bold mb-1 d-block text-medium-emphasis">
                {{ $t('notes') }}
              </label>
              <v-textarea
                v-model="motifDialog.form.notes"
                rows="2"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                :placeholder="$t('optional_notes_or_details')"
                hide-details
              ></v-textarea>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider></v-divider>
        <v-card-actions class="pa-4">
          <v-btn variant="text" color="red-lighten-1" rounded @click="motifDialog.show = false">
            {{ $t('cancel') }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            style="background-color: #E65100; color: #fff;"
            rounded
            :loading="motifDialog.saving"
            :disabled="!motifDialog.form.reason || !motifDialog.form.start_date || motifDialog.saving"
            prepend-icon="mdi-content-save"
            @click="saveMotif"
          >
            {{ $t('save_motif') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ================= DIALOG 2: MARK AS LEAVE COMPANY ================= -->
    <v-dialog v-model="leaveDialog.show" max-width="540" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4" style="background: rgba(211, 47, 47, 0.08);">
          <v-icon color="#D32F2F" class="mr-2">mdi-account-remove-outline</v-icon>
          <span class="font-weight-bold" style="color: #D32F2F;">{{ $t('mark_as_leave_company') }}</span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="leaveDialog.show = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider></v-divider>

        <v-card-text class="pa-4">
          <v-alert
            type="warning"
            variant="tonal"
            density="comfortable"
            class="mb-4 rounded-xl"
            icon="mdi-alert-octagon"
          >
            <div class="font-weight-bold">{{ $t('departure_warning_title') }}</div>
            <div class="text-caption mt-1">{{ $t('departure_warning_desc') }}</div>
          </v-alert>

          <div v-if="selectedWorker" class="pa-3 rounded-lg mb-4" style="background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.08);">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="font-weight-bold">{{ selectedWorker.full_name }}</div>
                <div class="text-caption text-medium-emphasis">#{{ selectedWorker.matricule }}</div>
              </div>
              <v-chip color="error" size="small" variant="tonal">{{ $t('leaving_company') }}</v-chip>
            </div>

            <!-- Teams to detach warning -->
            <div v-if="selectedWorker.teams && selectedWorker.teams.length > 0" class="mt-3 pt-2 border-t">
              <div class="text-caption font-weight-bold text-error mb-1">
                {{ $t('teams_will_be_detached') }} ({{ selectedWorker.teams.length }}):
              </div>
              <div class="d-flex flex-wrap ga-1">
                <v-chip
                  v-for="t in selectedWorker.teams"
                  :key="'dt-' + t.id"
                  size="x-small"
                  color="error"
                  variant="outlined"
                >
                  {{ t.name }}
                </v-chip>
              </div>
            </div>
          </div>

          <v-row dense>
            <!-- Departure Date -->
            <v-col cols="12" sm="6">
              <label class="text-caption font-weight-bold mb-1 d-block text-primary">
                {{ $t('departure_date') }} *
              </label>
              <v-text-field
                v-model="leaveDialog.form.departure_date"
                type="date"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                prepend-inner-icon="mdi-calendar-remove"
              ></v-text-field>
            </v-col>

            <!-- Departure Reason -->
            <v-col cols="12" sm="6">
              <label class="text-caption font-weight-bold mb-1 d-block text-primary">
                {{ $t('departure_reason') }} *
              </label>
              <v-combobox
                v-model="leaveDialog.form.reason"
                :items="departureReasonPresets"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                prepend-inner-icon="mdi-tag-text-outline"
                :placeholder="$t('reason')"
              ></v-combobox>
            </v-col>

            <!-- Notes -->
            <v-col cols="12">
              <label class="text-caption font-weight-bold mb-1 d-block text-medium-emphasis">
                {{ $t('notes') }}
              </label>
              <v-textarea
                v-model="leaveDialog.form.notes"
                rows="2"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                :placeholder="$t('optional_departure_notes')"
                hide-details
              ></v-textarea>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider></v-divider>
        <v-card-actions class="pa-4">
          <v-btn variant="text" rounded @click="leaveDialog.show = false">
            {{ $t('cancel') }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            color="error"
            rounded
            :loading="leaveDialog.saving"
            :disabled="!leaveDialog.form.departure_date || !leaveDialog.form.reason || leaveDialog.saving"
            prepend-icon="mdi-account-remove"
            @click="confirmLeaveCompany"
          >
            {{ $t('confirm_departure') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ================= DIALOG 3: MARK AS BACK TO WORK ================= -->
    <v-dialog v-model="returnDialog.show" max-width="540" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4" style="background: rgba(0, 137, 123, 0.08);">
          <v-icon color="#00897B" class="mr-2">mdi-account-arrow-left-outline</v-icon>
          <span class="font-weight-bold" style="color: #00897B;">{{ $t('mark_as_back_to_work') }}</span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="returnDialog.show = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider></v-divider>

        <v-card-text class="pa-4">
          <div v-if="selectedWorker" class="pa-3 rounded-lg mb-4" style="background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.08);">
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="font-weight-bold">{{ selectedWorker.full_name }}</div>
                <div class="text-caption text-medium-emphasis">#{{ selectedWorker.matricule }}</div>
              </div>
              <v-chip color="success" size="small" variant="tonal">{{ $t('return_to_work') }}</v-chip>
            </div>
          </div>

          <!-- Restore Previous Teams Toggle (User Request Explicit Requirement) -->
          <v-card
            variant="outlined"
            class="pa-4 rounded-xl mb-4 border"
            :color="returnDialog.form.restore_teams ? 'success' : 'grey'"
          >
            <div class="d-flex align-center justify-space-between">
              <div class="d-flex align-center ga-2">
                <v-icon :color="returnDialog.form.restore_teams ? 'success' : 'grey'">mdi-account-group-outline</v-icon>
                <div>
                  <div class="text-subtitle-2 font-weight-bold">{{ $t('restore_team_associations_question') }}</div>
                  <div class="text-caption text-medium-emphasis">{{ $t('restore_teams_explanation') }}</div>
                </div>
              </div>
              <v-switch
                v-model="returnDialog.form.restore_teams"
                color="success"
                hide-details
                density="compact"
              ></v-switch>
            </div>

            <div v-if="returnDialog.form.restore_teams && selectedWorker?.previous_team_ids?.length" class="mt-3 pt-2 border-t">
              <div class="text-caption font-weight-bold text-success mb-1">
                {{ $t('teams_to_be_restored') }} ({{ selectedWorker.previous_team_ids.length }}):
              </div>
              <div class="d-flex flex-wrap ga-1">
                <v-chip
                  v-for="tid in selectedWorker.previous_team_ids"
                  :key="'rt-' + tid"
                  size="x-small"
                  color="success"
                  variant="tonal"
                >
                  {{ getTeamName(tid) }}
                </v-chip>
              </div>
            </div>
          </v-card>

          <v-row dense>
            <!-- Return Date -->
            <v-col cols="12" sm="6">
              <label class="text-caption font-weight-bold mb-1 d-block text-primary">
                {{ $t('return_date') }} *
              </label>
              <v-text-field
                v-model="returnDialog.form.return_date"
                type="date"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                prepend-inner-icon="mdi-calendar-check"
              ></v-text-field>
            </v-col>

            <!-- Return Motif -->
            <v-col cols="12" sm="6">
              <label class="text-caption font-weight-bold mb-1 d-block text-primary">
                {{ $t('return_motif') }} *
              </label>
              <v-combobox
                v-model="returnDialog.form.reason"
                :items="returnMotifPresets"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                prepend-inner-icon="mdi-tag-text-outline"
                :placeholder="$t('reason')"
              ></v-combobox>
            </v-col>

            <!-- Notes -->
            <v-col cols="12">
              <label class="text-caption font-weight-bold mb-1 d-block text-medium-emphasis">
                {{ $t('notes') }}
              </label>
              <v-textarea
                v-model="returnDialog.form.notes"
                rows="2"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="wam-input-field"
                :placeholder="$t('optional_notes_or_details')"
                hide-details
              ></v-textarea>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider></v-divider>
        <v-card-actions class="pa-4">
          <v-btn variant="text" rounded @click="returnDialog.show = false">
            {{ $t('cancel') }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            style="background-color: #00897B; color: #fff;"
            rounded
            :loading="returnDialog.saving"
            :disabled="!returnDialog.form.return_date || !returnDialog.form.reason || returnDialog.saving"
            prepend-icon="mdi-account-check"
            @click="confirmReturnToWork"
          >
            {{ $t('confirm_return') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ================= DIALOG 4: MOVEMENT HISTORY TIMELINE ================= -->
    <v-dialog v-model="historyDialog.show" max-width="680">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4" style="background: rgba(21, 101, 192, 0.08);">
          <v-icon color="#1565C0" class="mr-2">mdi-history</v-icon>
          <span class="font-weight-bold" style="color: #1565C0;">
            {{ $t('movement_history') }} - {{ historyDialog.workforce?.full_name }}
          </span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="historyDialog.show = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider></v-divider>

        <v-card-text class="pa-4">
          <div v-if="movementStore.history.loading" class="text-center pa-6">
            <v-progress-circular indeterminate color="#1565C0" size="32"></v-progress-circular>
            <div class="mt-2 text-caption text-medium-emphasis">{{ $t('loading_history') }}...</div>
          </div>

          <div v-else-if="!movementStore.history.movements.length" class="text-center pa-8 text-grey">
            <v-icon size="48" color="grey-lighten-2">mdi-history</v-icon>
            <div class="mt-2 font-weight-medium">{{ $t('no_movements_recorded') }}</div>
            <div class="text-caption text-medium-emphasis">{{ $t('no_movements_recorded_sub') }}</div>
          </div>

          <!-- Visual Timeline of Movements -->
          <v-timeline v-else density="compact" side="end" class="wam-timeline">
            <v-timeline-item
              v-for="m in movementStore.history.movements"
              :key="'m-' + m.id"
              :dot-color="getMovementColor(m.type)"
              :icon="getMovementIcon(m.type)"
              size="small"
            >
              <div class="pa-3 rounded-lg border mb-2" :style="{ background: getMovementBg(m.type) }">
                <div class="d-flex align-center justify-space-between flex-wrap ga-2">
                  <v-chip size="x-small" :color="getMovementColor(m.type)" variant="flat" class="font-weight-bold">
                    {{ getMovementLabel(m.type) }}
                  </v-chip>
                  <span class="text-caption text-medium-emphasis">
                    {{ formatDate(m.start_date) }}
                    <span v-if="m.end_date"> → {{ formatDate(m.end_date) }}</span>
                    <v-chip
                      v-else-if="m.is_ongoing || m.metadata?.is_ongoing || (!m.end_date && m.type === 'absence')"
                      size="x-small"
                      color="#E65100"
                      variant="tonal"
                      class="ml-1 font-weight-bold"
                    >
                      {{ $t('ongoing') }}
                    </v-chip>
                  </span>
                </div>

                <div class="mt-2 font-weight-bold text-body-2">
                  {{ m.reason }}
                </div>

                <div v-if="m.days_count" class="text-caption text-medium-emphasis mt-1">
                  <v-icon size="12" class="mr-1">mdi-timer-sand</v-icon>
                  <span>{{ m.days_count }} {{ $t('days') }}</span>
                </div>

                <div v-if="m.notes" class="text-caption mt-1 pa-2 rounded" style="background: rgba(0,0,0,0.03);">
                  {{ m.notes }}
                </div>

                <!-- Restored teams info -->
                <div v-if="m.restored_teams && m.previous_team_names?.length" class="text-caption text-success mt-2">
                  <v-icon size="12" color="success" class="mr-1">mdi-check-all</v-icon>
                  <span>{{ $t('restored_teams') }}: {{ m.previous_team_names.join(', ') }}</span>
                </div>

                <div class="text-caption text-medium-emphasis mt-2 d-flex align-center ga-1" style="font-size: 10px !important;">
                  <v-icon size="10">mdi-account-edit</v-icon>
                  <span>{{ m.created_by_name }} ({{ m.created_at }})</span>
                </div>
              </div>
            </v-timeline-item>
          </v-timeline>
        </v-card-text>

        <v-divider></v-divider>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="#1565C0" rounded @click="historyDialog.show = false">
            {{ $t('close') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ================= DIALOG 5: QUICK THRESHOLD CHANGE ================= -->
    <v-dialog v-model="quickThresholdDialog.show" max-width="440">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#1565C0" class="mr-2">mdi-timer-sand</v-icon>
          <span>{{ $t('change_absence_threshold') }}</span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="quickThresholdDialog.show = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider></v-divider>

        <v-card-text class="pa-4">
          <p class="text-caption text-medium-emphasis mb-3">
            {{ $t('absence_rule_explanation', { days: quickThresholdDialog.value }) }}
          </p>

          <v-text-field
            v-model.number="quickThresholdDialog.value"
            type="number"
            min="1"
            max="365"
            density="compact"
            variant="outlined"
            rounded
            color="#42A5F5"
            :suffix="$t('days')"
            prepend-inner-icon="mdi-calendar-alert"
          ></v-text-field>

          <div class="d-flex flex-wrap ga-2 mt-2">
            <v-chip
              v-for="d in [3, 5, 7, 10, 14, 21, 30]"
              :key="'qt-' + d"
              :variant="quickThresholdDialog.value === d ? 'flat' : 'tonal'"
              :color="quickThresholdDialog.value === d ? '#1565C0' : '#42A5F5'"
              size="small"
              class="font-weight-bold cursor-pointer"
              @click="quickThresholdDialog.value = d"
            >
              {{ d }} {{ $t('days') }}
            </v-chip>
          </div>
        </v-card-text>

        <v-divider></v-divider>
        <v-card-actions class="pa-4">
          <v-btn variant="text" rounded @click="quickThresholdDialog.show = false">
            {{ $t('cancel') }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            style="background-color: #1565C0; color: #fff;"
            rounded
            :loading="quickThresholdDialog.saving"
            prepend-icon="mdi-content-save"
            @click="saveQuickThreshold"
          >
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ================= SUMMARY SNACKBAR (Rule 5) ================= -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      location="top right"
      :timeout="snackbar.timeout"
      elevation="4"
      class="summary-snackbar"
    >
      <div class="d-flex align-center ga-2">
        <v-icon :icon="snackbar.icon" size="20" color="white" />
        <span class="font-weight-medium text-white">{{ snackbar.message }}</span>
      </div>
      <template #actions>
        <v-btn icon="mdi-close" variant="text" size="small" color="white" @click="closeSnackbar" />
      </template>
      <v-progress-linear
        :model-value="snackbar.progress"
        color="white"
        height="3"
        absolute
        location="bottom"
      />
    </v-snackbar>
  </v-card>
</template>

<script>
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useWorkforceMovement } from '@/stores/WorkforceMovement';
import { useTeam } from '@/stores/Team';
import { useMain } from '@/stores/Main';
import { useWebWorkerFn } from '@vueuse/core';
import { toRaw } from 'vue';

/**
 * High-performance worker function to filter, search, and extract options
 * completely off the main thread so the browser UI never blocks or freezes.
 */
function workerProcessAbsenceData(payload) {
  const {
    items = [],
    statusFilter = 'all',
    searchQuery = '',
    columnFilters = {},
    labels = {},
  } = payload || {};

  function getDisplayVal(key, item) {
    if (!item) return '';
    if (key === 'full_name') return item.full_name || '';
    if (key === 'status') {
      if (item.status === 'active') return labels.active || 'Active';
      if (item.status === 'absent') return labels.absent || 'Absent';
      if (item.status === 'left_company') return labels.left_company || 'Left company';
      return item.status || '';
    }
    if (key === 'teams') {
      if (item.status === 'left_company') return labels.detached || 'Detached';
      if (item.teams && item.teams.length > 0) {
        return item.teams.map(t => t.name).join(', ');
      }
      return labels.no_team || 'No Team';
    }
    if (key === 'last_activity_date') {
      return item.last_activity_date
        ? String(item.last_activity_date).substring(0, 10)
        : (labels.no_activities_recorded || 'No activities recorded');
    }
    if (key === 'total_absence_days') {
      return (item.total_absence_days || 0) + ' ' + (labels.days || 'days');
    }
    if (key === 'latest_motif') {
      if (item.status === 'left_company') {
        return item.departure_reason || labels.departure || 'Departure';
      }
      if (item.latest_motif) {
        return item.latest_motif;
      }
      if (item.status === 'absent') {
        return labels.motif_unreported || 'Motif unreported';
      }
      return '-';
    }
    return String(item[key] ?? '');
  }

  const q = searchQuery ? searchQuery.toLowerCase().trim() : '';
  const baseItems = [];

  for (let i = 0; i < items.length; i++) {
    const w = items[i];
    if (statusFilter !== 'all' && w.status !== statusFilter) {
      continue;
    }
    if (q) {
      const match =
        (w.full_name && w.full_name.toLowerCase().includes(q)) ||
        (w.matricule && String(w.matricule).toLowerCase().includes(q)) ||
        (w.cni && String(w.cni).toLowerCase().includes(q)) ||
        (w.latest_motif && w.latest_motif.toLowerCase().includes(q)) ||
        (w.departure_reason && w.departure_reason.toLowerCase().includes(q)) ||
        (w.teams && w.teams.some(t => t.name && t.name.toLowerCase().includes(q)));
      if (!match) continue;
    }
    baseItems.push(w);
  }

  const nameSet = new Set();
  const statusSet = new Set();
  const teamSet = new Set();
  const dateSet = new Set();
  const absenceDaysSet = new Set();
  const motifSet = new Set();

  for (let i = 0; i < baseItems.length; i++) {
    const item = baseItems[i];
    if (item.full_name) nameSet.add(item.full_name);

    statusSet.add(getDisplayVal('status', item));

    if (item.status === 'left_company') {
      teamSet.add(labels.detached || 'Detached');
    } else if (item.teams && item.teams.length > 0) {
      for (let j = 0; j < item.teams.length; j++) {
        const t = item.teams[j];
        if (t && t.name) teamSet.add(t.name);
      }
    } else {
      teamSet.add(labels.no_team || 'No Team');
    }

    dateSet.add(getDisplayVal('last_activity_date', item));
    absenceDaysSet.add(Number(item.total_absence_days) || 0);
    motifSet.add(getDisplayVal('latest_motif', item));
  }

  const columnOptions = {
    full_name: Array.from(nameSet).sort((a, b) => a.localeCompare(b)),
    status: Array.from(statusSet).sort((a, b) => a.localeCompare(b)),
    teams: Array.from(teamSet).sort((a, b) => a.localeCompare(b)),
    last_activity_date: Array.from(dateSet).sort((a, b) => b.localeCompare(a)),
    total_absence_days: Array.from(absenceDaysSet)
      .sort((a, b) => a - b)
      .map(d => d + ' ' + (labels.days || 'days')),
    latest_motif: Array.from(motifSet).sort((a, b) => a.localeCompare(b)),
  };

  const activeEntries = Object.entries(columnFilters).filter(
    ([, sel]) => Array.isArray(sel) && sel.length > 0
  );

  let filteredItems = baseItems;
  if (activeEntries.length > 0) {
    filteredItems = [];
    for (let i = 0; i < baseItems.length; i++) {
      const item = baseItems[i];
      let matchesAll = true;

      for (let e = 0; e < activeEntries.length; e++) {
        const [colKey, selected] = activeEntries[e];
        if (colKey === 'teams') {
          const itemTeams = [];
          if (item.status === 'left_company') {
            itemTeams.push(labels.detached || 'Detached');
          } else if (item.teams && item.teams.length > 0) {
            for (let t = 0; t < item.teams.length; t++) {
              if (item.teams[t] && item.teams[t].name) itemTeams.push(item.teams[t].name);
            }
          } else {
            itemTeams.push(labels.no_team || 'No Team');
          }
          if (!itemTeams.some(t => selected.includes(t))) {
            matchesAll = false;
            break;
          }
        } else {
          const val = getDisplayVal(colKey, item);
          if (!selected.includes(val)) {
            matchesAll = false;
            break;
          }
        }
      }

      if (matchesAll) {
        filteredItems.push(item);
      }
    }
  }

  return {
    filteredItems,
    columnOptions,
    baseCount: baseItems.length,
    totalCount: filteredItems.length,
  };
}

export default {
  name: 'WorkforceAbsenceManagement',
  components: { SegmentedPillToggle },
  setup() {
    const { workerFn, workerStatus, workerTerminate } = useWebWorkerFn(workerProcessAbsenceData);
    return {
      workerFn,
      workerStatus,
      workerTerminate,
    };
  },
  data() {
    return {
      statusFilter: 'all',
      searchQuery: '',
      columnFilters: {
        full_name: [],
        status: [],
        teams: [],
        last_activity_date: [],
        total_absence_days: [],
        latest_motif: [],
      },
      sortBy: [{ key: 'full_name', order: 'asc' }],
      processedWorkforces: [],
      columnOptions: {
        full_name: [],
        status: [],
        teams: [],
        last_activity_date: [],
        total_absence_days: [],
        latest_motif: [],
      },
      baseWorkforcesCount: 0,
      isWorkerFiltering: false,
      searchDebounceTimer: null,
      windowHeight: typeof window !== 'undefined' ? window.innerHeight : 800,
      selectedWorker: null,
      motifDateRangeVC: null,
      motifSingleDateVC: null,
      motifDialog: {
        show: false,
        saving: false,
        form: {
          reason: '',
          start_date: new Date().toISOString().substring(0, 10),
          end_date: null,
          is_ongoing: true,
          days_count: 1,
          notes: '',
        },
      },
      leaveDialog: {
        show: false,
        saving: false,
        form: {
          departure_date: new Date().toISOString().substring(0, 10),
          reason: 'Fin de contrat',
          notes: '',
        },
      },
      returnDialog: {
        show: false,
        saving: false,
        form: {
          return_date: new Date().toISOString().substring(0, 10),
          reason: 'Reprise normale du travail',
          restore_teams: true,
          notes: '',
        },
      },
      historyDialog: {
        show: false,
        workforce: null,
      },
      quickThresholdDialog: {
        show: false,
        value: 7,
        saving: false,
      },
      snackbar: { show: false, message: '', color: 'success', icon: 'mdi-check-circle', timeout: 4000, progress: 100 },
      snackbarTimer: null,
    };
  },
  computed: {
    absenceMotifPresets() {
      return [
        this.$t('preset_illness'),
        this.$t('preset_unjustified_absence'),
        this.$t('preset_family_emergency'),
        this.$t('preset_unpaid_leave'),
        this.$t('preset_work_accident'),
        this.$t('preset_seasonal_travel'),
        this.$t('preset_other'),
      ];
    },
    departureReasonPresets() {
      return [
        this.$t('preset_resignation'),
        this.$t('preset_contract_end'),
        this.$t('preset_job_abandonment'),
        this.$t('preset_dismissal'),
        this.$t('preset_retirement'),
        this.$t('preset_other'),
      ];
    },
    returnMotifPresets() {
      return [
        this.$t('preset_normal_resumption'),
        this.$t('preset_medical_recovery'),
        this.$t('preset_trip_return'),
        this.$t('preset_rehire'),
        this.$t('preset_leave_end'),
        this.$t('preset_other'),
      ];
    },
    motifDateSummary() {
      if (!this.motifDialog.form.start_date) return '-';
      if (this.motifDialog.form.is_ongoing) {
        return `${this.motifDialog.form.start_date} → ${this.$t('ongoing')} (${this.motifDialog.form.days_count || 1} ${this.$t('days')})`;
      }
      if (this.motifDialog.form.end_date) {
        return `${this.motifDialog.form.start_date} → ${this.motifDialog.form.end_date} (${this.motifDialog.form.days_count || 1} ${this.$t('days')})`;
      }
      return `${this.motifDialog.form.start_date}`;
    },
    movementStore() { return useWorkforceMovement(); },
    teamStore() { return useTeam(); },
    main() { return useMain(); },
    headers() {
      return [
        {
          title: this.$t('workforce'),
          key: 'full_name',
          align: 'start',
          sortable: true,
          sort: (a, b) => String(a || '').localeCompare(String(b || '')),
        },
        {
          title: this.$t('status'),
          key: 'status',
          align: 'center',
          sortable: true,
          sort: (a, b) => String(a || '').localeCompare(String(b || '')),
        },
        {
          title: this.$t('teams'),
          key: 'teams',
          align: 'start',
          sortable: true,
          sort: (a, b) => {
            const aStr = (a || []).map(t => t.name).join(', ').toLowerCase();
            const bStr = (b || []).map(t => t.name).join(', ').toLowerCase();
            return aStr.localeCompare(bStr);
          },
        },
        {
          title: this.$t('last_fortnight_activity'),
          key: 'last_activity_date',
          align: 'start',
          sortable: true,
          sort: (a, b) => {
            const aT = a ? new Date(a).getTime() : 0;
            const bT = b ? new Date(b).getTime() : 0;
            return aT - bT;
          },
        },
        {
          title: this.$t('absence_metrics'),
          key: 'total_absence_days',
          align: 'center',
          sortable: true,
          sort: (a, b) => (Number(a) || 0) - (Number(b) || 0),
        },
        {
          title: this.$t('latest_motif'),
          key: 'latest_motif',
          align: 'start',
          sortable: true,
          sort: (a, b) => String(a || '').localeCompare(String(b || '')),
        },
        {
          title: this.$t('actions'),
          key: 'actions',
          align: 'end',
          sortable: false,
        },
      ];
    },
    tableHeight() {
      return Math.max(420, this.windowHeight - 340);
    },
    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(f => Array.isArray(f) && f.length > 0);
    },
    activeFiltersCount() {
      return Object.values(this.columnFilters).reduce((count, f) => count + (Array.isArray(f) ? f.length : 0), 0);
    },
  },
  watch: {
    statusFilter() {
      this.triggerWorkerProcessing();
    },
    searchQuery() {
      if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
      this.searchDebounceTimer = setTimeout(() => {
        this.triggerWorkerProcessing();
      }, 150);
    },
    columnFilters: {
      deep: true,
      handler() {
        this.triggerWorkerProcessing();
      },
    },
    'movementStore.absences': {
      deep: false,
      handler() {
        this.triggerWorkerProcessing();
      },
    },
  },
  methods: {
    formatDate(d) {
      if (!d) return '-';
      return this.main.formateDate(d);
    },
    getTeamName(tid) {
      const t = this.teamStore.list.find(x => x.id === tid);
      return t ? t.name : `Team #${tid}`;
    },
    getMovementColor(type) {
      switch (type) {
        case 'absence': return '#E65100';
        case 'departure': return '#D32F2F';
        case 'return': return '#00897B';
        default: return '#1565C0';
      }
    },
    getMovementIcon(type) {
      switch (type) {
        case 'absence': return 'mdi-calendar-alert';
        case 'departure': return 'mdi-account-remove';
        case 'return': return 'mdi-account-check';
        default: return 'mdi-history';
      }
    },
    getMovementBg(type) {
      switch (type) {
        case 'absence': return 'rgba(230, 81, 0, 0.05)';
        case 'departure': return 'rgba(211, 47, 47, 0.05)';
        case 'return': return 'rgba(0, 137, 123, 0.05)';
        default: return 'rgba(21, 101, 192, 0.05)';
      }
    },
    getMovementLabel(type) {
      switch (type) {
        case 'absence': return this.$t('absence');
        case 'departure': return this.$t('departure');
        case 'return': return this.$t('return_to_work');
        default: return type;
      }
    },
    async refreshData() {
      await this.movementStore.fetchAbsences();
      await this.teamStore.fetch();
      this.showSummarySnackbar(this.$t('data_refreshed'), 'info', 'mdi-refresh');
    },
    openThresholdQuickDialog() {
      this.quickThresholdDialog.value = this.movementStore.thresholdDays || 7;
      this.quickThresholdDialog.show = true;
    },
    async saveQuickThreshold() {
      this.quickThresholdDialog.saving = true;
      try {
        await this.movementStore.saveSetting(this.quickThresholdDialog.value);
        await this.movementStore.fetchAbsences();
        this.quickThresholdDialog.show = false;
        this.showSummarySnackbar(this.$t('settings_updated'), 'success', 'mdi-check-circle');
      } catch (e) {
        this.showSummarySnackbar(this.$t('error'), 'error', 'mdi-alert-circle');
      } finally {
        this.quickThresholdDialog.saving = false;
      }
    },
    formatDateToYMD(date) {
      if (!date) return null;
      const d = new Date(date);
      if (isNaN(d.getTime())) return null;
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
    parseYMDToDate(str) {
      if (!str) return null;
      const parts = String(str).split('-');
      if (parts.length !== 3) return new Date(str);
      return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    },
    computeMotifDays() {
      if (!this.motifDialog.form.start_date) return;
      const start = this.parseYMDToDate(this.motifDialog.form.start_date);
      if (!start) return;

      if (this.motifDialog.form.is_ongoing) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        start.setHours(0, 0, 0, 0);
        const diff = today.getTime() - start.getTime();
        this.motifDialog.form.days_count = Math.max(1, Math.round(diff / (1000 * 60 * 60 * 24)) + 1);
      } else if (this.motifDialog.form.end_date) {
        const end = this.parseYMDToDate(this.motifDialog.form.end_date);
        if (end) {
          start.setHours(0, 0, 0, 0);
          end.setHours(0, 0, 0, 0);
          const diff = end.getTime() - start.getTime();
          this.motifDialog.form.days_count = Math.max(1, Math.round(diff / (1000 * 60 * 60 * 24)) + 1);
        }
      }
    },
    onMotifRangeChange(range) {
      if (!range) return;
      if (range.start) {
        this.motifDialog.form.start_date = this.formatDateToYMD(range.start);
      }
      if (range.end) {
        this.motifDialog.form.end_date = this.formatDateToYMD(range.end);
      }
      this.computeMotifDays();
    },
    onMotifSingleDateChange(date) {
      if (!date) return;
      this.motifDialog.form.start_date = this.formatDateToYMD(date);
      this.motifDialog.form.end_date = null;
      this.computeMotifDays();
    },
    onOngoingToggle(isOngoing) {
      if (isOngoing) {
        this.motifDialog.form.end_date = null;
        const start = this.parseYMDToDate(this.motifDialog.form.start_date) || new Date();
        this.motifSingleDateVC = start;
      } else {
        const start = this.parseYMDToDate(this.motifDialog.form.start_date) || new Date();
        const end = this.parseYMDToDate(this.motifDialog.form.end_date) || new Date();
        if (end.getTime() < start.getTime()) {
          end.setTime(start.getTime());
        }
        this.motifDateRangeVC = { start, end };
        this.motifDialog.form.end_date = this.formatDateToYMD(end);
      }
      this.computeMotifDays();
    },
    onManualStartDateChange(val) {
      if (!val) return;
      const d = this.parseYMDToDate(val);
      if (!d || isNaN(d.getTime())) return;
      if (this.motifDialog.form.is_ongoing) {
        this.motifSingleDateVC = d;
      } else {
        const end = this.parseYMDToDate(this.motifDialog.form.end_date) || d;
        this.motifDateRangeVC = { start: d, end };
      }
      this.computeMotifDays();
    },
    onManualEndDateChange(val) {
      if (!val) return;
      const d = this.parseYMDToDate(val);
      if (!d || isNaN(d.getTime())) return;
      const start = this.parseYMDToDate(this.motifDialog.form.start_date) || d;
      this.motifDateRangeVC = { start, end: d };
      this.computeMotifDays();
    },
    openMotifDialog(worker) {
      this.selectedWorker = worker;
      const todayYMD = new Date().toISOString().substring(0, 10);
      const startDate = worker.last_activity_date ? worker.last_activity_date : todayYMD;
      const isOngoing = true;

      this.motifDialog.form = {
        reason: worker.latest_motif || (this.absenceMotifPresets && this.absenceMotifPresets[0]) || 'Maladie',
        start_date: startDate,
        end_date: null,
        is_ongoing: isOngoing,
        days_count: Math.max(1, worker.days_since_last_activity || 1),
        notes: '',
      };

      const startObj = this.parseYMDToDate(startDate) || new Date();
      this.motifSingleDateVC = startObj;
      this.motifDateRangeVC = { start: startObj, end: new Date() };
      this.computeMotifDays();
      this.motifDialog.show = true;
    },
    async saveMotif() {
      if (!this.selectedWorker) return;
      this.motifDialog.saving = true;
      try {
        await this.movementStore.recordMotif(this.selectedWorker.id, this.motifDialog.form);
        this.motifDialog.show = false;
        this.showSummarySnackbar(this.$t('motif_recorded_successfully'), 'success', 'mdi-check-circle');
      } catch (e) {
        this.showSummarySnackbar(this.$t('error'), 'error', 'mdi-alert-circle');
      } finally {
        this.motifDialog.saving = false;
      }
    },
    openLeaveCompanyDialog(worker) {
      this.selectedWorker = worker;
      this.leaveDialog.form = {
        departure_date: new Date().toISOString().substring(0, 10),
        reason: 'Fin de contrat',
        notes: '',
      };
      this.leaveDialog.show = true;
    },
    async confirmLeaveCompany() {
      if (!this.selectedWorker) return;
      this.leaveDialog.saving = true;
      try {
        await this.movementStore.leaveCompany(this.selectedWorker.id, this.leaveDialog.form);
        this.leaveDialog.show = false;
        this.showSummarySnackbar(this.$t('workforce_departed_success'), 'success', 'mdi-account-remove');
      } catch (e) {
        this.showSummarySnackbar(this.$t('error'), 'error', 'mdi-alert-circle');
      } finally {
        this.leaveDialog.saving = false;
      }
    },
    openReturnToWorkDialog(worker) {
      this.selectedWorker = worker;
      this.returnDialog.form = {
        return_date: new Date().toISOString().substring(0, 10),
        reason: 'Reprise normale du travail',
        restore_teams: Boolean(worker.previous_team_ids && worker.previous_team_ids.length > 0),
        notes: '',
      };
      this.returnDialog.show = true;
    },
    async confirmReturnToWork() {
      if (!this.selectedWorker) return;
      this.returnDialog.saving = true;
      try {
        const res = await this.movementStore.returnToWork(this.selectedWorker.id, this.returnDialog.form);
        this.returnDialog.show = false;
        const msg = res.teams_restored
          ? this.$t('workforce_returned_teams_restored')
          : this.$t('workforce_returned_success');
        this.showSummarySnackbar(msg, 'success', 'mdi-account-check');
      } catch (e) {
        this.showSummarySnackbar(this.$t('error'), 'error', 'mdi-alert-circle');
      } finally {
        this.returnDialog.saving = false;
      }
    },
    async openHistoryDialog(worker) {
      this.historyDialog.workforce = worker;
      this.historyDialog.show = true;
      try {
        await this.movementStore.fetchHistory(worker.id);
      } catch (e) {
        this.showSummarySnackbar(this.$t('error'), 'error', 'mdi-alert-circle');
      }
    },
    getCellDisplayValue(key, item) {
      if (!item) return '';
      switch (key) {
        case 'full_name':
          return item.full_name || '';
        case 'status':
          if (item.status === 'active') return this.$t('active');
          if (item.status === 'absent') return this.$t('absent');
          if (item.status === 'left_company') return this.$t('left_company');
          return item.status || '';
        case 'teams':
          if (item.status === 'left_company') {
            return this.$t('detached');
          }
          if (item.teams && item.teams.length > 0) {
            return item.teams.map(t => t.name).join(', ');
          }
          return this.$t('no_team');
        case 'last_activity_date':
          return item.last_activity_date ? this.formatDate(item.last_activity_date) : this.$t('no_activities_recorded');
        case 'total_absence_days':
          return `${item.total_absence_days || 0} ${this.$t('days')}`;
        case 'latest_motif':
          if (item.status === 'left_company') {
            return item.departure_reason || this.$t('departure');
          }
          if (item.latest_motif) {
            return item.latest_motif;
          }
          if (item.status === 'absent') {
            return this.$t('motif_unreported');
          }
          return '-';
        default:
          return String(item[key] ?? '');
      }
    },
    async triggerWorkerProcessing() {
      // Cleanly terminate previous execution if still running to prevent warnings / race conditions
      if (this.workerStatus === 'RUNNING') {
        this.workerTerminate();
      }

      const rawAbsences = toRaw(this.movementStore.absences) || [];
      if (!rawAbsences.length) {
        this.processedWorkforces = [];
        this.baseWorkforcesCount = 0;
        return;
      }

      this.isWorkerFiltering = true;

      // Plain serializable payload for Web Worker
      const payload = {
        items: JSON.parse(JSON.stringify(rawAbsences)),
        statusFilter: this.statusFilter,
        searchQuery: this.searchQuery || '',
        columnFilters: JSON.parse(JSON.stringify(this.columnFilters || {})),
        labels: {
          active: this.$t('active'),
          absent: this.$t('absent'),
          left_company: this.$t('left_company'),
          detached: this.$t('detached'),
          no_team: this.$t('no_team'),
          days: this.$t('days'),
          days_ago: this.$t('days_ago'),
          no_activities_recorded: this.$t('no_activities_recorded'),
          motif_unreported: this.$t('motif_unreported'),
          departure: this.$t('departure'),
        },
      };

      try {
        const result = await this.workerFn(payload);
        if (result) {
          this.processedWorkforces = result.filteredItems || [];
          this.columnOptions = result.columnOptions || this.columnOptions;
          this.baseWorkforcesCount = result.baseCount || 0;
        }
      } catch (err) {
        if (err) {
          console.warn('Worker processing error or cancelled:', err);
        }
      } finally {
        this.isWorkerFiltering = false;
      }
    },
    clearAllColumnFilters() {
      this.columnFilters = {
        full_name: [],
        status: [],
        teams: [],
        last_activity_date: [],
        total_absence_days: [],
        latest_motif: [],
      };
      this.triggerWorkerProcessing();
    },
    onResize() {
      this.windowHeight = window.innerHeight;
    },
    showSummarySnackbar(message, color = 'success', icon = 'mdi-check-circle') {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.snackbar.message = message;
      this.snackbar.color = color;
      this.snackbar.icon = icon;
      this.snackbar.progress = 100;
      this.snackbar.show = true;
      const stepMs = 50;
      const decrement = 100 / (this.snackbar.timeout / stepMs);
      this.snackbarTimer = setInterval(() => {
        this.snackbar.progress = Math.max(0, this.snackbar.progress - decrement);
        if (this.snackbar.progress <= 0) {
          clearInterval(this.snackbarTimer);
          this.snackbar.show = false;
        }
      }, stepMs);
    },
    closeSnackbar() {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.snackbar.show = false;
    },
  },
  async mounted() {
    window.addEventListener('resize', this.onResize);
    await Promise.all([
      this.movementStore.fetchAbsences(),
      this.teamStore.fetch(),
    ]);
    this.triggerWorkerProcessing();
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.onResize);
    if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
    this.workerTerminate();
  },
};
</script>

<style scoped>
.wam-card {
  border-radius: 20px !important;
  overflow: visible;
  position: relative;
  font-family: 'Inter', sans-serif;
}
.wam-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  min-height: 100px;
}
.wam-hero-inner {
  position: relative;
  z-index: 1;
  padding: 22px 26px;
  color: #fff;
}
.wam-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.15);
}
.wam-hero-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0.3px;
}
.wam-toolbar {
  background: rgba(var(--v-theme-surface), 0.7);
  backdrop-filter: blur(8px);
}
.wam-kpi-card {
  position: relative;
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 16px;
  padding: 16px;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: pointer;
}
.wam-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}
.wam-kpi-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}
.wam-kpi--absent .wam-kpi-accent { background: linear-gradient(90deg, #E65100, #FF9800); }
.wam-kpi--active .wam-kpi-accent { background: linear-gradient(90deg, #00897B, #26A69A); }
.wam-kpi--left .wam-kpi-accent { background: linear-gradient(90deg, #D32F2F, #EF5350); }
.wam-kpi--blue .wam-kpi-accent { background: linear-gradient(90deg, #1565C0, #42A5F5); }

.wam-kpi-lbl {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(var(--v-theme-on-surface), 0.6);
}
.wam-kpi-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.wam-kpi-icon--absent { background: rgba(230, 81, 0, 0.1); }
.wam-kpi-icon--active { background: rgba(0, 137, 123, 0.1); }
.wam-kpi-icon--left { background: rgba(211, 47, 47, 0.1); }
.wam-kpi-icon--blue { background: rgba(21, 101, 192, 0.1); }

.wam-kpi-val {
  font-size: 26px;
  font-weight: 900;
  line-height: 1.1;
  font-family: 'Outfit', 'Inter', sans-serif;
}
.wam-kpi-unit {
  font-size: 14px;
  font-weight: 600;
  opacity: 0.7;
}
.wam-kpi-sub {
  font-size: 11px;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin-top: 6px;
  display: flex;
  align-items: center;
}
.wam-input-field :deep(.v-field) {
  border-radius: 12px !important;
}
.wam-table {
  border-radius: 16px !important;
  overflow: hidden;
}

.wam-datepicker-card {
  border-color: rgba(66, 165, 245, 0.28) !important;
  background: rgba(66, 165, 245, 0.02) !important;
}

.wam-switch-card {
  border: 1px solid rgba(var(--v-border-color), 0.12);
  background: rgba(var(--v-theme-surface-variant), 0.03);
  transition: all 0.2s ease;
}

.wam-switch-card.wam-switch-active {
  border-color: rgba(66, 165, 245, 0.45) !important;
  background: linear-gradient(135deg, rgba(21, 101, 192, 0.04) 0%, rgba(66, 165, 245, 0.08) 100%) !important;
  box-shadow: 0 2px 8px rgba(66, 165, 245, 0.12);
}

/* Table Headers and Multiselect Filters */
.wam-header-row th {
  background: rgb(var(--v-theme-surface)) !important;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12) !important;
}
.wam-th {
  padding: 10px 8px 4px !important;
  white-space: nowrap;
}
.wam-th-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  opacity: 0.85;
  transition: all 0.2s ease;
}
.wam-th-label:hover {
  opacity: 1;
  color: #1565C0;
}
.wam-sort-idle {
  opacity: 0.35;
  transition: opacity 0.2s ease;
}
.wam-th-label:hover .wam-sort-idle {
  opacity: 0.85;
  color: #1565C0;
}
.wam-filter-row th {
  padding: 2px 6px 8px !important;
  background: rgb(var(--v-theme-surface)) !important;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12) !important;
}
.wam-filter-th {
  vertical-align: top;
}
.wam-col-filter {
  min-width: 120px;
}
.wam-col-filter :deep(.v-field) {
  font-size: 11px !important;
  min-height: 28px !important;
  border-radius: 8px !important;
  background: rgb(var(--v-theme-surface)) !important;
}
.wam-col-filter :deep(.v-field__input) {
  padding: 2px 6px !important;
  min-height: 26px !important;
  font-size: 11px !important;
}
.wam-col-filter :deep(.v-field__clearable) {
  font-size: 13px !important;
  margin-inline-end: 2px;
}
.wam-col-filter :deep(.v-chip) {
  height: 18px !important;
  font-size: 10px !important;
  margin: 1px !important;
}
.wam-table-footer {
  background: rgba(var(--v-theme-surface-variant), 0.02);
}
</style>
