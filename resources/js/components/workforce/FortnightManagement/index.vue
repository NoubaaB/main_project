<template>
  <v-card class="fnm-card" elevation="0">
    <!-- Hero Header -->
    <div class="fnm-hero">
      <div class="fnm-hero-inner">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3">
          <div class="d-flex align-center ga-4">
            <div class="fnm-hero-icon">
              <v-icon size="28" color="white">mdi-calendar-month</v-icon>
            </div>
            <div>
              <div class="fnm-hero-title">{{ $t('fortnight_management') }}</div>
              <div class="text-white text-caption" style="opacity:.85">{{ $t('manage_fortnight_desc') }}</div>
            </div>
          </div>

          <!-- Hero Right: Quick Stats & View Switcher -->
          <div class="d-flex flex-wrap align-center ga-3">
            <div class="fnm-hero-stat d-none d-sm-flex align-center ga-2">
              <v-icon size="16" color="white">mdi-account-group</v-icon>
              <span class="text-caption font-weight-medium">{{ (breakdownData.workforces || []).length }} {{ $t('workforces') }}</span>
            </div>
            <div class="fnm-hero-stat d-none d-sm-flex align-center ga-2">
              <v-icon size="16" color="white">mdi-cash-multiple</v-icon>
              <span class="text-caption font-weight-bold">{{ formatNumber(summary.month_total) }} {{ main.currency || 'DH' }}</span>
            </div>

            <!-- View Switcher -->
            <SegmentedPillToggle
              v-model="viewMode"
              :items="[
                { value: 'all', label: $t('all_views'), icon: 'mdi-view-dashboard-outline' },
                { value: 'calendar', label: $t('calendar_and_entry'), icon: 'mdi-calendar-month-outline' },
                { value: 'breakdown', label: $t('activity_breakdown'), icon: 'mdi-chart-box-outline' }
              ]"
              color="#1976D2"
            />
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <v-row v-show="viewMode === 'all' || viewMode === 'calendar'">
        <!-- ============= LEFT PANEL ============= -->
        <v-col cols="12" md="3">
          <v-card elevation="0" class="rounded-2xl border pa-4 fnm-panel-card bg-surface">
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="d-flex align-center ga-2">
                <div class="fnm-panel-icon-wrap">
                  <v-icon color="#42A5F5" size="18">mdi-calendar-edit</v-icon>
                </div>
                <div>
                  <div class="text-subtitle-2 font-weight-bold">{{ $t('activity_entry') }}</div>
                  <div class="text-caption text-medium-emphasis">{{ $t('daily_workforce_entry') }}</div>
                </div>
              </div>
              <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-medium">
                {{ selectedWorkforceIds.length }} {{ $t('total_selected') }}
              </v-chip>
            </div>

            <!-- Date capsule -->
            <div class="fnm-date-capsule mb-3 pa-2 rounded-xl border d-flex align-center justify-space-between">
              <div class="d-flex align-center ga-2">
                <v-avatar size="32" color="#42A5F5" class="text-white">
                  <v-icon size="16">mdi-calendar</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">{{ $t('date') }}</div>
                  <div class="font-weight-bold text-body-2">{{ formattedDate }}</div>
                </div>
              </div>
              <v-btn size="x-small" variant="text" color="#42A5F5" rounded @click="goToday">{{ $t('today') }}</v-btn>
            </div>

            <!-- Workforce selector -->
            <v-autocomplete
              ref="workforceAutocomplete"
              v-model="selectedWorkforceIds"
              :items="workforceStore.list"
              item-value="id"
              :item-title="workforceItemTitle"
              :label="$t('select_workforces')"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-workforce mb-3"
              clearable
              multiple
              chips
              closable-chips
              prepend-inner-icon="mdi-account-hard-hat"
              @update:model-value="onWorkforceChange"
            >
              <template #chip="{ item, props }">
                <v-chip v-bind="props" size="small" color="#42A5F5" variant="tonal" closable>{{ item.title }}</v-chip>
              </template>
              <template #item="{ item, props }">
                <v-list-item v-bind="props" class="rounded-lg my-1">
                  <template #prepend>
                    <div class="fnm-avatar-circle mr-2">
                      {{ getWorkforceInitials(item.raw) }}
                    </div>
                    <v-checkbox-btn
                      :model-value="selectedWorkforceIds.includes(item.value)"
                      color="#42A5F5"
                      density="compact"
                      class="mr-1"
                    ></v-checkbox-btn>
                  </template>
                  <v-list-item-title class="font-weight-medium">{{ item.raw.full_name }}</v-list-item-title>
                  <v-list-item-subtitle class="text-caption text-medium-emphasis">{{ item.raw.matricule }}</v-list-item-subtitle>
                </v-list-item>
              </template>
            </v-autocomplete>

            <!-- Section affiliee -->
            <v-autocomplete
              v-model="activityForm.section_id"
              :items="sectionStore.list"
              item-value="id"
              item-title="name"
              :label="$t('section_affiliated')"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-workforce mb-3"
              clearable
              prepend-inner-icon="mdi-sitemap-outline"
              @update:model-value="onSectionChange"
              :disabled="!!activityForm.corporal_id"
            ></v-autocomplete>

            <!-- OR Corporal -->
            <v-autocomplete
              v-model="activityForm.corporal_id"
              :items="corporalStore.list"
              item-value="id"
              item-title="full_name"
              :label="$t('corporal')"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-workforce mb-3"
              clearable
              prepend-inner-icon="mdi-account-tie"
              @update:model-value="onCorporalChange"
              :disabled="!!activityForm.section_id"
            ></v-autocomplete>
            
            <!-- Price -->
            <v-text-field
              v-model.number="activityForm.price"
              :label="$t('price_dh', { currency: main.currency || 'DH' })"
              density="compact"
              variant="outlined"
              rounded
              color="#42A5F5"
              class="text-workforce mb-3"
              type="number"
              min="0"
              step="0.01"
              :prefix="'Dh'"
              prepend-inner-icon="mdi-cash"
              :readonly="priceReadonly"
            ></v-text-field>

            <!-- Fixed Daily Price (shown when any selected workforce has fixed_daily_pay) -->
            <div v-if="hasFixedDailyPaySelected" class="fnm-fixed-price-wrap mb-3">
              <div class="fnm-fixed-price-badge">
                <v-icon size="16" color="amber-darken-2" class="mr-1">mdi-cash-lock</v-icon>
                <span class="text-caption font-weight-bold">{{ $t('fixed_daily_pay') }}</span>
                <v-chip size="x-small" color="amber-darken-2" variant="flat" class="ml-1">{{ fixedDailyPayCount }}</v-chip>
              </div>
              <v-text-field
                v-model.number="fixedDailyPrice"
                :label="$t('fixed_daily_price')"
                density="compact"
                variant="outlined"
                rounded
                color="amber-darken-2"
                class="text-amber-darken-2"
                type="number"
                min="0"
                step="0.01"
                :prefix="'Dh'"
                :hint="$t('fixed_daily_price_hint', { count: fixedDailyPayCount })"
                persistent-hint
              ></v-text-field>
            </div>

            <!-- Submit button -->
            <v-btn
              block
              color="#42A5F5"
              variant="flat"
              rounded
              class="text-white font-weight-bold fnm-btn-primary mb-2"
              elevation="2"
              :disabled="!canSubmitActivity"
              :loading="submitting"
              @click="submitActivity"
              prepend-icon="mdi-plus"
            >
              {{ $t('add_activity') }}
            </v-btn>

            <!-- Delete activities button -->
            <v-btn
              block
              color="error"
              variant="outlined"
              rounded
              class="fnm-btn-delete"
              :disabled="!canDeleteActivities"
              :loading="deleting"
              @click="bulkDeleteActivities"
              prepend-icon="mdi-delete-outline"
            >
              {{ $t('delete_activities_for_date') }}
            </v-btn>

            <v-divider class="my-4"></v-divider>

            <!-- ============= SUMMARY KPI CARDS ============= -->
            <!-- 1st Fortnight -->
            <div class="fnm-kpi-card mb-3 pa-3 rounded-xl border">
              <div class="d-flex align-center justify-space-between mb-1">
                <div class="d-flex align-center ga-1">
                  <v-icon size="16" color="#42A5F5">mdi-numeric-1-circle</v-icon>
                  <span class="text-caption font-weight-bold">{{ $t('first_fortnight') }}</span>
                </div>
                <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-medium">
                  <v-icon start size="10">mdi-calendar-check</v-icon>
                  {{ summary.first_fortnight.working_days }} {{ $t('days') }}
                </v-chip>
              </div>
              <div class="text-h6 font-weight-bold text-workforce fnm-currency-num">
                Dh {{ formatNumber(summary.first_fortnight.total) }}
              </div>
            </div>

            <!-- 2nd Fortnight -->
            <div class="fnm-kpi-card mb-3 pa-3 rounded-xl border">
              <div class="d-flex align-center justify-space-between mb-1">
                <div class="d-flex align-center ga-1">
                  <v-icon size="16" color="#42A5F5">mdi-numeric-2-circle</v-icon>
                  <span class="text-caption font-weight-bold">{{ $t('second_fortnight') }}</span>
                </div>
                <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-medium">
                  <v-icon start size="10">mdi-calendar-check</v-icon>
                  {{ summary.second_fortnight.working_days }} {{ $t('days') }}
                </v-chip>
              </div>
              <div class="text-h6 font-weight-bold text-workforce fnm-currency-num">
                Dh {{ formatNumber(summary.second_fortnight.total) }}
              </div>
            </div>

            <!-- Month Total -->
            <div class="fnm-kpi-card-total pa-3 rounded-xl border">
              <div class="d-flex align-center justify-space-between mb-1">
                <div class="d-flex align-center ga-1">
                  <v-icon size="16" color="#1565C0">mdi-sigma</v-icon>
                  <span class="text-caption font-weight-bold text-medium-emphasis">{{ $t('total_month_amount') }} {{ monthNames[currentMonth] }}</span>
                </div>
              </div>
              <div class="text-h6 font-weight-black text-primary fnm-currency-num">
                Dh {{ formatNumber(summary.month_total) }}
              </div>
            </div>
          </v-card>
        </v-col>

        <!-- ============= RIGHT PANEL - CALENDAR ============= -->
        <v-col cols="12" md="9">
          <v-card elevation="0" class="rounded-2xl border fnm-cal-card bg-surface">
            <!-- Calendar Header -->
            <div class="fnm-cal-header">
              <div class="d-flex align-center ga-2">
                <v-btn icon size="small" variant="tonal" rounded @click="prevMonth"><v-icon size="18">mdi-chevron-left</v-icon></v-btn>
                <v-btn variant="tonal" color="#42A5F5" size="small" rounded class="font-weight-medium" @click="goToday">{{ $t('today') }}</v-btn>
                <v-btn icon size="small" variant="tonal" rounded @click="nextMonth"><v-icon size="18">mdi-chevron-right</v-icon></v-btn>
              </div>
              <div class="fnm-cal-title d-flex align-center ga-2">
                <span>{{ monthNames[currentMonth] }} {{ currentYear }}</span>
                <v-chip size="small" color="#42A5F5" variant="flat" class="text-white font-weight-bold">
                  {{ currentHalf === 1 ? 'Q1: ' + $t('first_fortnight') : 'Q2: ' + $t('second_fortnight') }}
                </v-chip>
              </div>
              <div class="d-flex align-center ga-2">
                <v-btn
                  :variant="multiSelectMode ? 'flat' : 'outlined'"
                  :color="multiSelectMode ? 'warning' : '#42A5F5'"
                  size="small"
                  rounded
                  @click="toggleMultiSelect"
                  :prepend-icon="multiSelectMode ? 'mdi-checkbox-multiple-marked' : 'mdi-checkbox-multiple-blank-outline'"
                >
                  {{ multiSelectMode ? $t('selection_mode_on') : $t('selection_mode') }}
                </v-btn>
                <v-btn-toggle v-model="currentHalf" mandatory variant="outlined" density="compact" color="#42A5F5" rounded class="fnm-segmented-toggle">
                  <v-btn :value="1" size="small">Q1</v-btn>
                  <v-btn :value="2" size="small">Q2</v-btn>
                </v-btn-toggle>
              </div>
            </div>

            <!-- Calendar Grid -->
            <div class="fnm-cal-grid">
              <!-- Day of week headers -->
              <div class="fnm-cal-dow" v-for="d in dayLabels" :key="d">{{ d }}</div>

              <!-- Calendar cells -->
              <div
                v-for="(cell, idx) in calendarCells"
                :key="idx"
                class="fnm-cal-cell"
                :class="{
                  'fnm-cal-cell--empty': !cell.day,
                  'fnm-cal-cell--today': cell.isToday,
                  'fnm-cal-cell--outside': cell.isOutside,
                  'fnm-cal-cell--future': cell.isFuture,
                  'fnm-cal-cell--multi': cell.activities && cell.activities.length >= 2,
                  'fnm-cal-cell--active-half': cell.inActiveHalf,
                  'fnm-cal-cell--summary': cell.isSummary,
                  'fnm-cal-cell--ms-selected': multiSelectMode && selectedDates.includes(cell.dateStr)
                }"
                @click="cell.day && !cell.isOutside && !cell.isFuture ? handleCellClick(cell) : null"
              >
                <div v-if="cell.day" class="fnm-cal-day-header">
                  <div class="fnm-cal-day-num" :class="{ 'fnm-cal-day-selected': isSelectedDate(cell) }">
                    {{ cell.day }}
                    <span v-if="cell.monthLabel" class="fnm-cal-month-label">{{ cell.monthLabel }}</span>
                  </div>
                  <!-- Cell delete button -->
                  <v-btn
                    v-if="cell.activities && cell.activities.length && !cell.isOutside && !cell.isFuture"
                    icon
                    size="18"
                    variant="text"
                    color="error"
                    class="fnm-cell-delete-btn"
                    @click.stop="deleteCellActivities(cell)"
                    :loading="deletingCell === cell.dateStr"
                  >
                    <v-icon size="14">mdi-delete-outline</v-icon>
                    <v-tooltip activator="parent" location="top">{{ $t('delete_all_cell_activities') }}</v-tooltip>
                  </v-btn>
                </div>

                <!-- Activities in cell (normal mode: workforce selected) -->
                <div v-if="cell.activities && cell.activities.length && !cell.isSummary" class="fnm-cal-activities">
                  <div
                    v-for="act in cell.activities.slice(0, 4)"
                    :key="act.id"
                    class="fnm-cal-activity"
                    :class="getActivityClass(act)"
                    @click.stop="editActivity(act)"
                  >
                    <span v-if="(multipleSelected || act.is_fixed_price) && act.workforce" class="fnm-act-wf" :title="act.workforce.full_name">
                      {{ getWorkforceInitials(act.workforce) }}
                    </span>
                    <span class="fnm-act-price">{{ formatNumber(act.price) }} Dh</span>
                    <span class="fnm-act-label">{{ getActivityLabel(act) }}</span>
                    <!-- Delete button on hover -->
                    <v-btn
                      icon
                      size="12"
                      variant="flat"
                      color="error"
                      class="fnm-act-delete"
                      @click.stop="deleteActivity(act)"
                    >
                      <v-icon size="10">mdi-close</v-icon>
                    </v-btn>
                  </div>
                  <!-- "+X more" indicator -->
                  <v-menu v-if="cell.activities.length > 4" location="bottom center" :close-on-content-click="false" max-width="320">
                    <template #activator="{ props }">
                      <div v-bind="props" class="fnm-cal-more" @click.stop>
                        +{{ cell.activities.length - 4 }} {{ $t('more') }}
                      </div>
                    </template>
                    <v-card class="rounded-lg pa-0" elevation="8" min-width="280">
                      <div class="fnm-more-header">
                        <v-icon size="16" color="white" class="mr-1">mdi-calendar</v-icon>
                        {{ cell.dateStr }} — {{ cell.activities.length }} {{ $t('activities') }}
                      </div>
                      <div class="fnm-more-list">
                        <div
                          v-for="act in cell.activities"
                          :key="'more-' + act.id"
                          class="fnm-more-item"
                          @click="editActivity(act)"
                        >
                          <span v-if="act.workforce" class="fnm-more-wf">{{ act.workforce.full_name }}</span>
                          <v-icon v-if="act.is_fixed_price" size="12" color="amber-darken-2" class="mr-1">mdi-cash-lock</v-icon>
                          <span class="fnm-more-price">{{ formatNumber(act.price) }} Dh</span>
                          <span class="fnm-more-label">{{ getActivityLabel(act) }}</span>
                          <v-btn icon size="18" variant="text" color="error" class="fnm-more-del" @click.stop="deleteActivity(act)">
                            <v-icon size="14">mdi-close</v-icon>
                          </v-btn>
                        </div>
                      </div>
                    </v-card>
                  </v-menu>
                </div>

                <!-- Summary mode: no workforce selected - show aggregated total -->
                <div v-if="cell.isSummary && cell.summaryData" class="fnm-cal-activities">
                  <div class="fnm-cal-activity fnm-act-summary" @click.stop="openDayDetail(cell)" style="cursor:pointer">
                    <v-icon size="12" color="white" class="mr-1">mdi-sigma</v-icon>
                    <span class="fnm-act-price">{{ formatNumber(cell.summaryData.total_price) }} Dh</span>
                    <span class="fnm-act-label">{{ cell.summaryData.count }} {{ $t('activities') }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Multi-select floating action bar -->
            <v-slide-y-reverse-transition>
              <div v-if="multiSelectMode && selectedDates.length > 0" class="fnm-ms-action-bar">
                <div class="fnm-ms-info">
                  <v-icon size="18" color="white" class="mr-2">mdi-calendar-check</v-icon>
                  <span class="fnm-ms-count">{{ selectedDates.length }} {{ $t('days_selected') }}</span>
                  <v-chip v-if="selectedWorkforceIds.length" size="x-small" color="white" variant="outlined" class="ml-2">
                    {{ selectedWorkforceIds.length }} {{ $t('workforces') }}
                  </v-chip>
                  <v-chip v-else size="x-small" color="white" variant="outlined" class="ml-2">
                    {{ $t('all_workforces') }}
                  </v-chip>
                </div>
                <div class="fnm-ms-actions">
                  <v-btn
                    size="small"
                    variant="text"
                    color="white"
                    @click="clearMultiSelect"
                    prepend-icon="mdi-close"
                  >
                    {{ $t('clear_selection') }}
                  </v-btn>
                  <v-btn
                    size="small"
                    variant="flat"
                    color="error"
                    rounded
                    @click="bulkDeleteSelectedDays"
                    :loading="bulkDeleting"
                    prepend-icon="mdi-delete-sweep"
                  >
                    {{ $t('delete_selected_activities') }}
                  </v-btn>
                </div>
              </div>
            </v-slide-y-reverse-transition>
          </v-card>
        </v-col>
      </v-row>

      <!-- ============= BREAKDOWN SUMMARY ============= -->
      <v-row class="mt-2" v-show="viewMode === 'all' || viewMode === 'breakdown'">
        <v-col cols="12">
          <v-card elevation="0" class="rounded-2xl border fnm-breakdown-card bg-surface">
            <v-card-title class="d-flex flex-wrap align-center justify-space-between pa-4 pb-2 ga-3">
              <div class="d-flex align-center ga-3">
                <div class="fnm-panel-icon-wrap">
                  <v-icon color="#42A5F5" size="20">mdi-chart-box-outline</v-icon>
                </div>
                <div>
                  <div class="text-subtitle-1 font-weight-bold">{{ $t('activity_breakdown') }}</div>
                  <div class="text-caption text-medium-emphasis">{{ $t('workforces_activities') }}</div>
                </div>
                <v-chip size="small" color="#42A5F5" variant="tonal" class="font-weight-medium ml-1">
                  {{ printDataRowCount }} {{ $t('records') || 'Records' }}
                </v-chip>
              </div>
              <div class="d-flex align-center ga-2">
                <v-btn
                  color="grey-darken-1"
                  variant="tonal"
                  rounded
                  size="small"
                  icon="mdi-table-cog"
                  @click="colSettingsDialog = true"
                >
                  <v-icon>mdi-table-cog</v-icon>
                  <v-tooltip activator="parent" location="bottom">{{ $t('configure_columns') }}</v-tooltip>
                </v-btn>
                <v-btn
                  color="#42A5F5"
                  variant="flat"
                  rounded
                  size="small"
                  class="text-white font-weight-medium"
                  prepend-icon="mdi-printer"
                  @click="openPrintDialog"
                >
                  {{ $t('print') }}
                </v-btn>
                <v-btn
                  color="success"
                  variant="flat"
                  rounded
                  size="small"
                  class="font-weight-medium"
                  prepend-icon="mdi-microsoft-excel"
                  :loading="exporting"
                  @click="exportToExcel"
                >
                  {{ $t('export_excel') }}
                </v-btn>
              </div>
            </v-card-title>
            <v-card-text class="pa-4 pt-1">
              <!-- Period Toggle + Declaration Chips + Search Toolbar -->
              <div class="d-flex flex-wrap align-center ga-3 mb-4 fnm-breakdown-toolbar pa-2 rounded-xl border">
                <!-- Entity toggle (Workforces / Sections / Corporals) -->
                <SegmentedPillToggle
                  v-model="breakdownTab"
                  :items="[
                    { value: 'workforces', label: $t('workforces_activities'), icon: 'mdi-account-multiple' },
                    { value: 'sections', label: $t('sections_activities'), icon: 'mdi-sitemap-outline' },
                    { value: 'corporals', label: $t('corporals_activities'), icon: 'mdi-account-tie' }
                  ]"
                  color="#1976D2"
                />

                <v-divider vertical class="mx-1 d-none d-sm-block" style="height:28px;"></v-divider>

                <!-- Period toggle (Q1 / Q2 / Month) -->
                <SegmentedPillToggle
                  v-model="breakdownPeriod"
                  :items="[
                    { value: 'q1', label: 'Q1', icon: 'mdi-numeric-1-circle-outline' },
                    { value: 'q2', label: 'Q2', icon: 'mdi-numeric-2-circle-outline' },
                    { value: 'month', label: $t('month'), icon: 'mdi-calendar-month' }
                  ]"
                  color="#1976D2"
                />

                <v-divider vertical class="mx-1 d-none d-sm-block" style="height:28px;"></v-divider>

                <!-- Declaration filter chips -->
                <div class="d-flex align-center ga-2">
                  <v-chip
                    :variant="showDeclared ? 'flat' : 'outlined'"
                    color="#2E7D32"
                    size="small"
                    class="cursor-pointer font-weight-medium"
                    @click="showDeclared = !showDeclared"
                  >
                    <v-icon start size="14">{{ showDeclared ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline' }}</v-icon>
                    {{ $t('declared') }}
                  </v-chip>
                  <v-chip
                    :variant="showNonDeclared ? 'flat' : 'outlined'"
                    color="#E65100"
                    size="small"
                    class="cursor-pointer font-weight-medium"
                    @click="showNonDeclared = !showNonDeclared"
                  >
                    <v-icon start size="14">{{ showNonDeclared ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline' }}</v-icon>
                    {{ $t('non_declared') }}
                  </v-chip>
                </div>

                <v-spacer></v-spacer>

                <!-- Full name search -->
                <v-text-field
                  v-model="workforceSearch"
                  :label="$t('search_workforce_name')"
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#42A5F5"
                  hide-details
                  prepend-inner-icon="mdi-magnify"
                  clearable
                  class="fnm-search-input"
                  style="min-width: 200px; max-width: 280px;"
                ></v-text-field>
              </div>

              <!-- Breadcrumb -->
              <div v-if="drilldownItem" class="d-flex align-center ga-2 mb-3 fnm-breadcrumb">
                <button class="fnm-breadcrumb-link" @click="drilldownItem = null">
                  <v-icon size="16" class="mr-1">{{ breakdownTabIcon }}</v-icon>
                  {{ breakdownTabLabel }}
                </button>
                <v-icon size="14" color="grey">mdi-chevron-right</v-icon>
                <span class="fnm-breadcrumb-current">
                  <v-icon size="14" class="mr-1">{{ drilldownIcon }}</v-icon>
                  {{ drilldownItem.name }}
                </span>
              </div>

              <!-- ======= WORKFORCE TAB ======= -->
              <template v-if="breakdownTab === 'workforces'">
                <!-- Summary table -->
                <v-table v-if="!drilldownItem" density="compact" class="rounded-lg fnm-breakdown-table" id="fnm-breakdown-print-area">
                  <!-- Clear filters bar -->
                  <template v-if="hasActiveWfFilters || wfSortRules.length">
                    <div class="fnm-filter-bar">
                      <v-chip size="x-small" color="#42A5F5" variant="tonal" class="mr-1" v-if="activeWfFilterCount">
                        <v-icon start size="12">mdi-filter</v-icon>
                        {{ activeWfFilterCount }} {{ $t('clear_filters').toLowerCase() }}
                      </v-chip>
                      <v-chip size="x-small" color="purple" variant="tonal" class="mr-1" v-if="wfSortRules.length">
                        <v-icon start size="12">mdi-sort</v-icon>
                        {{ wfSortRules.length }} sort{{ wfSortRules.length > 1 ? 's' : '' }}
                      </v-chip>
                      <v-btn size="x-small" variant="text" color="error" @click.stop="clearWfFilters" class="ml-1">
                        <v-icon start size="14">mdi-filter-remove</v-icon>
                        {{ $t('clear_all_filters') }}
                      </v-btn>
                    </div>
                  </template>
                  <thead>
                    <tr>
                      <th v-for="col in visibleWorkforceColumns" :key="'th-'+col.key"
                        :class="[col.type === 'chip' ? '' : 'text-center', 'fnm-th-sortable']"
                        :style="col.headerColor ? 'color:'+col.headerColor : ''"
                      >
                        <div class="fnm-th-inner">
                          <!-- Sort toggle area -->
                          <div class="d-flex align-center">
                            <v-checkbox-btn
                              v-if="col.type === 'checkbox_q1' || col.type === 'checkbox_q2'"
                              :model-value="isAllPaid(col.key)"
                              :true-value="true"
                              :false-value="false"
                              @update:model-value="v => toggleAllPayments(col.period, Boolean(v))"
                              color="success"
                              class="mr-1"
                              @click.stop
                            ></v-checkbox-btn>
                            <span class="fnm-th-label" @click.stop="toggleWfSort(col.key)" :title="$t('sort_asc')">
                              {{ $t(col.label) }}
                              <span v-if="getWfSortDir(col.key)" class="fnm-sort-indicator">
                                <v-icon size="14" :color="col.headerColor || '#42A5F5'">
                                  {{ getWfSortDir(col.key) === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down' }}
                                </v-icon>
                                <span v-if="wfSortRules.length > 1" class="fnm-sort-badge">{{ getWfSortIndex(col.key) }}</span>
                              </span>
                            </span>
                          </div>
                          <!-- Filter menu -->
                          <v-menu :close-on-content-click="false" location="bottom center" offset="4" max-width="220">
                            <template v-slot:activator="{ props }">
                              <v-btn v-bind="props" icon variant="text" size="x-small"
                                :color="isWfColFiltered(col.key) ? '#42A5F5' : 'grey'"
                                class="fnm-filter-btn" @click.stop="ensureWfFilter(col.key)"
                              >
                                <v-icon size="14">{{ isWfColFiltered(col.key) ? 'mdi-filter' : 'mdi-filter-outline' }}</v-icon>
                              </v-btn>
                            </template>
                            <v-card min-width="180" class="pa-3 fnm-filter-card">
                              <div class="text-caption font-weight-bold mb-2" style="color:#42A5F5;">
                                <v-icon size="14" class="mr-1">mdi-filter</v-icon>{{ $t('filter_column') }}
                              </div>
                              <!-- Text/Chip filter: checklist of unique values -->
                              <template v-if="getWfColFilterType(col) === 'text'">
                                <v-text-field
                                  :model-value="wfColumnFilters[col.key] ? wfColumnFilters[col.key].search : ''"
                                  @update:model-value="v => { ensureWfFilter(col.key); wfColumnFilters[col.key].search = v; }"
                                  :placeholder="$t('search') + '...'"
                                  density="compact" variant="outlined" hide-details
                                  prepend-inner-icon="mdi-magnify" clearable
                                  class="fnm-filter-input mb-2"
                                />
                                <!-- Select All / Deselect All -->
                                <div class="d-flex align-center justify-space-between mb-1">
                                  <v-btn size="x-small" variant="text" color="primary" @click="selectAllWfFilterValues(col)">
                                    {{ $t('all') }}
                                  </v-btn>
                                  <v-btn size="x-small" variant="text" color="grey" @click="deselectAllWfFilterValues(col)">
                                    {{ $t('clear_sort') }}
                                  </v-btn>
                                </div>
                                <div class="fnm-filter-checklist">
                                  <template v-for="val in getFilteredUniqueValues(col)" :key="val">
                                    <label class="fnm-filter-check-item">
                                      <v-checkbox
                                        :model-value="isWfValueChecked(col.key, val)"
                                        @update:model-value="v => toggleWfFilterValue(col.key, val, v)"
                                        density="compact" hide-details color="#42A5F5"
                                        class="fnm-filter-checkbox"
                                      />
                                      <span class="fnm-filter-check-label text-truncate">{{ val || '-' }}</span>
                                    </label>
                                  </template>
                                </div>
                              </template>
                              <!-- Boolean filter -->
                              <template v-else-if="getWfColFilterType(col) === 'boolean'">
                                <v-btn-toggle
                                  :model-value="wfColumnFilters[col.key] ? wfColumnFilters[col.key].bool : ''"
                                  @update:model-value="v => { ensureWfFilter(col.key); wfColumnFilters[col.key].bool = v; }"
                                  density="compact" mandatory color="#42A5F5" variant="outlined" divided class="w-100"
                                >
                                  <v-btn value="" size="small">{{ $t('all') }}</v-btn>
                                  <v-btn value="true" size="small">{{ $t('yes') }}</v-btn>
                                  <v-btn value="false" size="small">{{ $t('no') }}</v-btn>
                                </v-btn-toggle>
                              </template>
                              <!-- Range filter (number/currency) -->
                              <template v-else>
                                <div class="d-flex ga-2">
                                  <v-text-field
                                    :model-value="wfColumnFilters[col.key] ? wfColumnFilters[col.key].min : ''"
                                    @update:model-value="v => { ensureWfFilter(col.key); wfColumnFilters[col.key].min = v; }"
                                    :placeholder="$t('min_value')" type="number"
                                    density="compact" variant="outlined" hide-details
                                    class="fnm-filter-input"
                                  />
                                  <v-text-field
                                    :model-value="wfColumnFilters[col.key] ? wfColumnFilters[col.key].max : ''"
                                    @update:model-value="v => { ensureWfFilter(col.key); wfColumnFilters[col.key].max = v; }"
                                    :placeholder="$t('max_value')" type="number"
                                    density="compact" variant="outlined" hide-details
                                    class="fnm-filter-input"
                                  />
                                </div>
                              </template>
                              <!-- Clear this column filter -->
                              <v-btn v-if="isWfColFiltered(col.key)" size="x-small" variant="text" color="error"
                                class="mt-2" block @click="clearWfColFilter(col.key)">
                                <v-icon start size="12">mdi-close</v-icon>{{ $t('clear_filters') }}
                              </v-btn>
                            </v-card>
                          </v-menu>
                        </div>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="w in sortedFilteredWorkforces" :key="'w-' + w.id" class="fnm-clickable-row" @click="drilldownItem = w">
                      <td v-for="col in visibleWorkforceColumns" :key="'td-'+col.key+'-'+w.id"
                        :class="col.type === 'chip' ? '' : 'text-center'"
                        :style="col.bold ? 'font-weight:700;color:#42A5F5;' : (col.cellColor ? 'font-weight:700;color:'+col.cellColor : '')"
                      >
                        <!-- Avatar + Name -->
                        <div v-if="col.type === 'chip'" class="d-flex align-center ga-2 py-1">
                          <div class="fnm-wf-avatar">
                            {{ getWorkforceInitials(w) }}
                          </div>
                          <div class="text-left">
                            <div class="font-weight-bold text-body-2 fnm-wf-name">{{ w[col.key] || w.name || w.full_name }}</div>
                            <div class="text-caption text-medium-emphasis">{{ w.matricule || '-' }}</div>
                          </div>
                        </div>
                        <!-- Boolean icon -->
                        <v-icon v-else-if="col.type === 'boolean'" :color="w[col.key] ? (col.color || 'success') : 'grey'" size="18">
                          {{ w[col.key] ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
                        </v-icon>
                        <!-- Computed declared/non-declared -->
                        <template v-else-if="col.type === 'computed_declared'">{{ formatNumber(getDeclared(w)) }}</template>
                        <template v-else-if="col.type === 'computed_non_declared'">{{ formatNumber(getNonDeclared(w)) }}</template>
                        <!-- Checkbox Q1/Q2 -->
                        <template v-else-if="col.type === 'checkbox_q1' || col.type === 'checkbox_q2'">
                          <v-checkbox
                            :model-value="Boolean(w[col.key])"
                            :true-value="true"
                            :false-value="false"
                            @update:model-value="v => togglePaymentStatus(w, col.period, Boolean(v))"
                            density="compact"
                            hide-details
                            color="success"
                            @click.stop
                          ></v-checkbox>
                        </template>
                        <!-- Currency -->
                        <template v-else-if="col.type === 'currency'">{{ formatNumber(w[col.key]) }}</template>
                        <!-- Text / Number -->
                        <template v-else>{{ getCellValue(w, col) }}</template>
                      </td>
                    </tr>
                    <!-- Empty state when filters produce no results -->
                    <tr v-if="sortedFilteredWorkforces.length === 0">
                      <td :colspan="visibleWorkforceColumns.length" class="text-center pa-6 text-grey">
                        <v-icon size="32" color="grey" class="mb-2">mdi-filter-off-outline</v-icon>
                        <div>{{ $t('no_results_filter') }}</div>
                      </td>
                    </tr>
                    <tr class="fnm-breakdown-total-row">
                      <td class="font-weight-bold" :colspan="totalColspan">{{ $t('total') }}</td>
                      <template v-for="col in visibleWorkforceColumns" :key="'tot-'+col.key">
                        <td v-if="col.period || col.type === 'computed_declared' || col.type === 'computed_non_declared'"
                          class="text-center font-weight-bold"
                          :style="col.cellColor ? 'color:'+col.cellColor : (col.bold ? 'color:#42A5F5;' : '')"
                        >
                          <template v-if="col.key === 'q1_work_days'">{{ workforceTotals.q1_work_days }}</template>
                          <template v-else-if="col.key === 'q1_non_work_days'">{{ workforceTotals.q1_non_work_days }}</template>
                          <template v-else-if="col.key === 'q1_total'">{{ formatNumber(workforceTotals.q1_total) }}</template>
                          <template v-else-if="col.key === 'q2_work_days'">{{ workforceTotals.q2_work_days }}</template>
                          <template v-else-if="col.key === 'q2_non_work_days'">{{ workforceTotals.q2_non_work_days }}</template>
                          <template v-else-if="col.key === 'q2_total'">{{ formatNumber(workforceTotals.q2_total) }}</template>
                          <template v-else-if="col.key === 'month_total'">{{ formatNumber(workforceTotals.month_total) }}</template>
                          <template v-else-if="col.type === 'checkbox_q1' || col.type === 'checkbox_q2'">-</template>
                          <template v-else-if="col.type === 'computed_declared'">{{ formatNumber(workforceTotals.declared) }}</template>
                          <template v-else-if="col.type === 'computed_non_declared'">{{ formatNumber(workforceTotals.non_declared) }}</template>
                        </td>
                      </template>
                    </tr>
                  </tbody>
                </v-table>

                <!-- Drill-down: activities for selected workforce -->
                <v-table v-else density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('date') }}</th>
                      <th>{{ $t('section') }} / {{ $t('corporal') }}</th>
                      <th class="text-center">{{ $t('price') }}</th>
                      <th class="text-center">{{ $t('type') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="act in drilldownItem.activities" :key="act.id">
                      <td>
                        <v-chip size="x-small" variant="tonal" color="grey">
                          <v-icon start size="12">mdi-calendar</v-icon>
                          {{ main.formateDate(act.date) }}
                        </v-chip>
                      </td>
                      <td>
                        <v-chip v-if="act.section_name" size="x-small" color="orange" variant="tonal" label>
                          <v-icon start size="12">mdi-sitemap-outline</v-icon>
                          {{ act.section_name }}
                        </v-chip>
                        <v-chip v-else-if="act.corporal_name" size="x-small" color="purple" variant="tonal" label>
                          <v-icon start size="12">mdi-account-tie</v-icon>
                          {{ act.corporal_name }}
                        </v-chip>
                        <span v-else class="text-grey">-</span>
                      </td>
                      <td class="text-center font-weight-bold">{{ formatNumber(act.price) }} {{ main.currency || 'DH' }}</td>
                      <td class="text-center">
                        <v-chip v-if="act.is_fixed_price" size="x-small" color="amber-darken-2" variant="flat">
                          <v-icon start size="12">mdi-cash-lock</v-icon>
                          {{ $t('fixed_daily_pay') }}
                        </v-chip>
                        <v-chip v-else size="x-small" color="grey" variant="tonal">{{ $t('regular') }}</v-chip>
                      </td>
                    </tr>
                  </tbody>
                </v-table>
              </template>

              <!-- ======= SECTIONS TAB ======= -->
              <template v-if="breakdownTab === 'sections'">
                <v-table v-if="!drilldownItem" density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('name') }}</th>
                      <th class="text-center">{{ $t('workforce_count') }}</th>
                      <th v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ $t('q1_count') }}</th>
                      <th v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ $t('q1_total') }}</th>
                      <th v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ $t('q2_count') }}</th>
                      <th v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ $t('q2_total') }}</th>
                      <th v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ $t('month_total') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="s in breakdownData.sections" :key="'s-' + s.id" class="fnm-clickable-row" @click="drilldownItem = s">
                      <td>
                        <v-chip size="small" color="orange" variant="tonal" label>
                          <v-icon start size="14">mdi-sitemap-outline</v-icon>
                          {{ s.name }}
                        </v-chip>
                      </td>
                      <td class="text-center">
                        <v-chip size="x-small" color="#42A5F5" variant="tonal">{{ (s.workforces || []).length }}</v-chip>
                      </td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ s.q1_count }}</td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ formatNumber(s.q1_total) }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ s.q2_count }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ formatNumber(s.q2_total) }}</td>
                      <td v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold text-orange">{{ formatNumber(s.month_total) }}</td>
                    </tr>
                    <tr class="fnm-breakdown-total-row">
                      <td class="font-weight-bold" colspan="2">{{ $t('total') }}</td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ sectionTotals.q1_count }}</td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ formatNumber(sectionTotals.q1_total) }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ sectionTotals.q2_count }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ formatNumber(sectionTotals.q2_total) }}</td>
                      <td v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold text-orange">{{ formatNumber(sectionTotals.month_total) }}</td>
                    </tr>
                  </tbody>
                </v-table>

                <!-- Drill-down -->
                <v-table v-else density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('full_name') }}</th>
                      <th class="text-center">{{ $t('matricule') }}</th>
                      <th class="text-center">{{ $t('cin') }}</th>
                      <th class="text-center">{{ $t('cnss_id') }}</th>
                      <th class="text-center">{{ $t('nationality') }}</th>
                      <th class="text-center">{{ $t('sex') }}</th>
                      <th class="text-center">{{ $t('is_migrant') }}</th>
                      <th class="text-center">{{ $t('salary_statement') }}</th>
                      <th class="text-center">{{ $t('fixed_daily_pay') }}</th>
                      <th v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ $t('q1_count') }}</th>
                      <th v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ $t('q1_total') }}</th>
                      <th v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ $t('q2_count') }}</th>
                      <th v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ $t('q2_total') }}</th>
                      <th v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ $t('month_total') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="wf in drilldownItem.workforces" :key="'sw-' + wf.id">
                      <td>
                        <v-chip size="small" color="#42A5F5" variant="tonal" label>
                          <v-icon start size="14">mdi-account</v-icon>
                          {{ wf.name }}
                        </v-chip>
                      </td>
                      <td class="text-center">{{ wf.matricule || '-' }}</td>
                      <td class="text-center">{{ wf.cin || '-' }}</td>
                      <td class="text-center">{{ wf.cnss_id || '-' }}</td>
                      <td class="text-center">{{ wf.nationality || '-' }}</td>
                      <td class="text-center">{{ wf.sex || '-' }}</td>
                      <td class="text-center">
                        <v-icon :color="wf.is_migrant ? 'warning' : 'grey'" size="18">{{ wf.is_migrant ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                      </td>
                      <td class="text-center">
                        <v-icon :color="wf.salary_statement ? 'success' : 'grey'" size="18">{{ wf.salary_statement ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                      </td>
                      <td class="text-center">
                        <v-icon :color="wf.fixed_daily_pay ? 'info' : 'grey'" size="18">{{ wf.fixed_daily_pay ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                      </td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ wf.q1_count }}</td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ formatNumber(wf.q1_total) }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ wf.q2_count }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ formatNumber(wf.q2_total) }}</td>
                      <td v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold" style="color:#42A5F5;">{{ formatNumber(wf.month_total) }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </template>

              <!-- ======= CORPORALS TAB ======= -->
              <template v-if="breakdownTab === 'corporals'">
                <v-table v-if="!drilldownItem" density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('name') }}</th>
                      <th class="text-center">{{ $t('workforce_count') }}</th>
                      <th v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ $t('q1_count') }}</th>
                      <th v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ $t('q1_total') }}</th>
                      <th v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ $t('q2_count') }}</th>
                      <th v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ $t('q2_total') }}</th>
                      <th v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ $t('month_total') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in breakdownData.corporals" :key="'c-' + c.id" class="fnm-clickable-row" @click="drilldownItem = c">
                      <td>
                        <v-chip size="small" color="purple" variant="tonal" label>
                          <v-icon start size="14">mdi-account-tie</v-icon>
                          {{ c.name }}
                        </v-chip>
                      </td>
                      <td class="text-center">
                        <v-chip size="x-small" color="#42A5F5" variant="tonal">{{ (c.workforces || []).length }}</v-chip>
                      </td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ c.q1_count }}</td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ formatNumber(c.q1_total) }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ c.q2_count }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ formatNumber(c.q2_total) }}</td>
                      <td v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold text-purple">{{ formatNumber(c.month_total) }}</td>
                    </tr>
                    <tr class="fnm-breakdown-total-row">
                      <td class="font-weight-bold" colspan="2">{{ $t('total') }}</td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ corporalTotals.q1_count }}</td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ formatNumber(corporalTotals.q1_total) }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ corporalTotals.q2_count }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ formatNumber(corporalTotals.q2_total) }}</td>
                      <td v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold text-purple">{{ formatNumber(corporalTotals.month_total) }}</td>
                    </tr>
                  </tbody>
                </v-table>

                <!-- Drill-down -->
                <v-table v-else density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('full_name') }}</th>
                      <th class="text-center">{{ $t('matricule') }}</th>
                      <th class="text-center">{{ $t('cin') }}</th>
                      <th class="text-center">{{ $t('cnss_id') }}</th>
                      <th class="text-center">{{ $t('nationality') }}</th>
                      <th class="text-center">{{ $t('sex') }}</th>
                      <th class="text-center">{{ $t('is_migrant') }}</th>
                      <th class="text-center">{{ $t('salary_statement') }}</th>
                      <th class="text-center">{{ $t('fixed_daily_pay') }}</th>
                      <th v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ $t('q1_count') }}</th>
                      <th v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ $t('q1_total') }}</th>
                      <th v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ $t('q2_count') }}</th>
                      <th v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ $t('q2_total') }}</th>
                      <th v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold">{{ $t('month_total') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="wf in drilldownItem.workforces" :key="'cw-' + wf.id">
                      <td>
                        <v-chip size="small" color="#42A5F5" variant="tonal" label>
                          <v-icon start size="14">mdi-account</v-icon>
                          {{ wf.name }}
                        </v-chip>
                      </td>
                      <td class="text-center">{{ wf.matricule || '-' }}</td>
                      <td class="text-center">{{ wf.cin || '-' }}</td>
                      <td class="text-center">{{ wf.cnss_id || '-' }}</td>
                      <td class="text-center">{{ wf.nationality || '-' }}</td>
                      <td class="text-center">{{ wf.sex || '-' }}</td>
                      <td class="text-center">
                        <v-icon :color="wf.is_migrant ? 'warning' : 'grey'" size="18">{{ wf.is_migrant ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                      </td>
                      <td class="text-center">
                        <v-icon :color="wf.salary_statement ? 'success' : 'grey'" size="18">{{ wf.salary_statement ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                      </td>
                      <td class="text-center">
                        <v-icon :color="wf.fixed_daily_pay ? 'info' : 'grey'" size="18">{{ wf.fixed_daily_pay ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                      </td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ wf.q1_count }}</td>
                      <td v-if="breakdownPeriod === 'q1' || breakdownPeriod === 'month'" class="text-center">{{ formatNumber(wf.q1_total) }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ wf.q2_count }}</td>
                      <td v-if="breakdownPeriod === 'q2' || breakdownPeriod === 'month'" class="text-center">{{ formatNumber(wf.q2_total) }}</td>
                      <td v-if="breakdownPeriod === 'month'" class="text-center font-weight-bold" style="color:#42A5F5;">{{ formatNumber(wf.month_total) }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </template>

              <!-- Grand Total Executive KPI Cards -->
              <div class="mt-4">
                <v-row class="ga-0">
                  <v-col cols="12" md="4" class="pa-2">
                    <div class="fnm-executive-kpi pa-4 rounded-2xl border">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase letter-spacing-1">{{ $t('first_fortnight') }} (Q1)</span>
                        <v-avatar size="32" color="#E3F2FD" class="text-primary">
                          <v-icon size="18" color="#1565C0">mdi-numeric-1-circle</v-icon>
                        </v-avatar>
                      </div>
                      <div class="text-h5 font-weight-bold text-workforce fnm-currency-num">
                        {{ formatNumber(grandTotals.q1_total) }} <span class="text-caption font-weight-bold">{{ main.currency || 'DH' }}</span>
                      </div>
                      <div class="d-flex align-center ga-2 mt-2 text-caption text-medium-emphasis">
                        <v-icon size="14">mdi-calendar-check</v-icon>
                        <span>{{ workforceTotals.q1_work_days }} {{ $t('working_days') }}</span>
                      </div>
                    </div>
                  </v-col>

                  <v-col cols="12" md="4" class="pa-2">
                    <div class="fnm-executive-kpi pa-4 rounded-2xl border">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase letter-spacing-1">{{ $t('second_fortnight') }} (Q2)</span>
                        <v-avatar size="32" color="#E3F2FD" class="text-primary">
                          <v-icon size="18" color="#1565C0">mdi-numeric-2-circle</v-icon>
                        </v-avatar>
                      </div>
                      <div class="text-h5 font-weight-bold text-workforce fnm-currency-num">
                        {{ formatNumber(grandTotals.q2_total) }} <span class="text-caption font-weight-bold">{{ main.currency || 'DH' }}</span>
                      </div>
                      <div class="d-flex align-center ga-2 mt-2 text-caption text-medium-emphasis">
                        <v-icon size="14">mdi-calendar-check</v-icon>
                        <span>{{ workforceTotals.q2_work_days }} {{ $t('working_days') }}</span>
                      </div>
                    </div>
                  </v-col>

                  <v-col cols="12" md="4" class="pa-2">
                    <div class="fnm-executive-kpi fnm-executive-kpi--hero pa-4 rounded-2xl">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <span class="text-caption font-weight-bold text-white text-uppercase letter-spacing-1">{{ $t('total_month') }} {{ monthNames[currentMonth] }}</span>
                        <v-avatar size="32" color="rgba(255,255,255,0.2)" class="text-white">
                          <v-icon size="18" color="white">mdi-cash-multiple</v-icon>
                        </v-avatar>
                      </div>
                      <div class="text-h5 font-weight-black text-white fnm-currency-num">
                        {{ formatNumber(grandTotals.month_total) }} <span class="text-caption font-weight-bold" style="opacity:0.8">{{ main.currency || 'DH' }}</span>
                      </div>
                      <div class="d-flex flex-wrap align-center ga-3 mt-2 text-caption text-white" style="opacity: 0.95;">
                        <span>{{ $t('declared') }}: <strong>{{ formatNumber(workforceTotals.declared) }}</strong></span>
                        <span>&bull;</span>
                        <span>{{ $t('non_declared') }}: <strong>{{ formatNumber(workforceTotals.non_declared) }}</strong></span>
                      </div>
                    </div>
                  </v-col>
                </v-row>
              </div>

              <!-- Empty state -->
              <div v-if="(!breakdownData.sections || !breakdownData.sections.length) && (!breakdownData.corporals || !breakdownData.corporals.length) && (!breakdownData.workforces || !breakdownData.workforces.length)" class="text-center pa-6 text-grey">
                <v-icon size="48" color="grey-lighten-1">mdi-chart-box-outline</v-icon>
                <div class="mt-2">{{ $t('no_activities_found') }}</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>

    <!-- ============= EDIT ACTIVITY DIALOG ============= -->
    <v-dialog v-model="editDialog" max-width="450" persistent>
      <v-card class="rounded-2xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#42A5F5" class="mr-2">mdi-pencil</v-icon>
          <span class="font-weight-bold">{{ $t('edit_activity') }}</span>
        </v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="12">
              <v-autocomplete
                v-model="editForm.section_id"
                :items="sectionStore.list"
                item-value="id"
                item-title="name"
                :label="$t('section_affiliated')"
                density="compact" variant="outlined" rounded color="#42A5F5" class="text-workforce"
                clearable
                prepend-inner-icon="mdi-sitemap-outline"
                :disabled="!!editForm.corporal_id"
                @update:model-value="onEditSectionChange"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12">
              <v-autocomplete
                v-model="editForm.corporal_id"
                :items="corporalStore.list"
                item-value="id"
                item-title="full_name"
                :label="$t('corporal')"
                density="compact" variant="outlined" rounded color="#42A5F5" class="text-workforce"
                clearable
                prepend-inner-icon="mdi-account-tie"
                :disabled="!!editForm.section_id"
                @update:model-value="onEditCorporalChange"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model.number="editForm.price"
                :label="$t('price')"
                density="compact" variant="outlined" rounded color="#42A5F5" class="text-workforce"
                type="number" min="0" step="0.01"
                prefix="Dh"
                prepend-inner-icon="mdi-cash"
                :readonly="editPriceReadonly"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="editDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn color="#42A5F5" variant="flat" rounded class="text-white font-weight-medium" @click="saveEditActivity" :loading="editSaving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= DAY DETAIL DIALOG (Summary mode) ============= -->
    <v-dialog v-model="dayDetailDialog" max-width="680" scrollable>
      <v-card class="rounded-xl">
        <!-- Header -->
        <div class="fnm-dd-header">
          <div class="d-flex align-center ga-2">
            <div class="fnm-dd-icon">
              <v-icon color="white" size="22">mdi-calendar-text</v-icon>
            </div>
            <div>
              <div class="fnm-dd-title">{{ $t('day_activities_detail') }}</div>
              <div class="fnm-dd-subtitle">{{ dayDetailDate }}</div>
            </div>
          </div>
          <v-btn icon size="small" variant="text" color="white" @click="dayDetailDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>

        <!-- Summary bar -->
        <div v-if="dayDetailActivities.length" class="fnm-dd-summary">
          <v-chip size="small" color="#42A5F5" variant="flat" prepend-icon="mdi-account-group" class="text-white">
            {{ dayDetailWorkforceCount }} {{ $t('workforces') }}
          </v-chip>
          <v-chip size="small" color="purple" variant="flat" prepend-icon="mdi-sigma">
            {{ formatNumber(dayDetailTotal) }} {{ main.currency || 'DH' }}
          </v-chip>
          <v-chip size="small" variant="flat" prepend-icon="mdi-counter">
            {{ dayDetailActivities.length }} {{ $t('activities') }}
          </v-chip>
        </div>

        <!-- Activity list -->
        <v-card-text class="pa-0" style="max-height: 420px;">
          <div v-if="dayDetailLoading" class="d-flex justify-center pa-8">
            <v-progress-circular indeterminate color="#42A5F5" />
          </div>
          <div v-else-if="!dayDetailActivities.length" class="text-center pa-8 text-grey">
            <v-icon size="48" color="grey-lighten-1">mdi-calendar-blank</v-icon>
            <div class="mt-2">{{ $t('no_activities_found') }}</div>
          </div>
          <v-list v-else lines="three" class="pa-2">
            <template v-for="(act, idx) in dayDetailActivities" :key="act.id">
              <v-list-item class="fnm-dd-item rounded-lg mb-1" :ripple="false">
                <template #prepend>
                  <v-avatar size="38" :color="act.section ? '#42A5F5' : 'purple'" class="mr-3">
                    <span class="text-white text-caption font-weight-bold">{{ getWorkforceInitials(act.workforce) }}</span>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-bold text-body-2">
                  {{ act.workforce ? act.workforce.full_name : '—' }}
                  <v-chip v-if="act.workforce" size="x-small" variant="outlined" color="grey" class="ml-1">{{ act.workforce.matricule }}</v-chip>
                </v-list-item-title>
                <v-list-item-subtitle class="d-flex align-center ga-2 mt-1">
                  <v-chip v-if="act.section" size="x-small" color="#42A5F5" variant="tonal" prepend-icon="mdi-sitemap-outline">
                    {{ act.section.name }}
                  </v-chip>
                  <v-chip v-if="act.corporal" size="x-small" color="purple" variant="tonal" prepend-icon="mdi-account-tie">
                    {{ act.corporal.full_name }}
                  </v-chip>
                  <v-chip size="x-small" color="success" variant="tonal" prepend-icon="mdi-cash">
                    {{ formatNumber(act.price) }} {{ main.currency || 'DH' }}
                  </v-chip>
                </v-list-item-subtitle>
                <template #append>
                  <div class="d-flex ga-1">
                    <v-btn icon size="x-small" variant="text" color="#42A5F5" @click="editActivityFromDetail(act)">
                      <v-icon size="16">mdi-pencil</v-icon>
                      <v-tooltip activator="parent" location="top">{{ $t('edit_activity') }}</v-tooltip>
                    </v-btn>
                    <v-btn icon size="x-small" variant="text" color="error" @click="deleteActivityFromDetail(act)" :loading="deletingDetailId === act.id">
                      <v-icon size="16">mdi-delete-outline</v-icon>
                      <v-tooltip activator="parent" location="top">{{ $t('delete') }}</v-tooltip>
                    </v-btn>
                  </div>
                </template>
              </v-list-item>
              <v-divider v-if="idx < dayDetailActivities.length - 1" class="mx-3" />
            </template>
          </v-list>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- ============= COLUMN SETTINGS DIALOG ============= -->
    <v-dialog v-model="colSettingsDialog" max-width="480" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="#42A5F5" class="mr-2">mdi-table-cog</v-icon>
          {{ $t('configure_columns') }}
          <v-spacer></v-spacer>
          <v-btn icon size="x-small" variant="text" @click="colSettingsDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-card-text class="pa-5 pt-2">
          <div class="d-flex flex-wrap ga-2 mb-3">
            <v-btn size="x-small" variant="tonal" color="#42A5F5" rounded @click="toggleAllColumns(true)" prepend-icon="mdi-eye">{{ $t('show_all') }}</v-btn>
            <v-btn size="x-small" variant="tonal" color="warning" rounded @click="toggleAllColumns(false)" prepend-icon="mdi-eye-off">{{ $t('hide_all') }}</v-btn>
            <v-btn size="x-small" variant="tonal" color="error" rounded @click="resetColumnsToDefault" prepend-icon="mdi-restore">{{ $t('reset_columns_default') }}</v-btn>
          </div>
          <div class="text-caption text-grey mb-2">{{ $t('drag_to_reorder') }}</div>
          <div class="col-settings-list">
            <div
              v-for="(col, idx) in allWorkforceColumns"
              :key="col.key"
              class="col-settings-item"
              :class="{ 'col-settings-item--locked': col.locked, 'col-settings-item--dragging': colDragIdx === idx }"
              :draggable="!col.locked"
              @dragstart="colDragStart(idx)"
              @dragover="colDragOver(idx, $event)"
              @dragend="colDragEnd"
            >
              <v-icon v-if="!col.locked" size="18" class="col-settings-drag-handle text-grey">mdi-drag-vertical</v-icon>
              <v-icon v-else size="18" class="text-grey-lighten-1 mr-1">mdi-lock-outline</v-icon>
              <v-checkbox
                v-model="col.visible"
                :disabled="col.locked"
                density="compact"
                color="#42A5F5"
                hide-details
                class="col-settings-cb"
                @update:model-value="onColumnVisibilityChange"
              ></v-checkbox>
              <span class="col-settings-label" :class="{ 'text-grey': !col.visible }">{{ $t(col.label) }}</span>
              <v-chip v-if="col.period" size="x-small" :color="col.period === 'q1' ? 'blue-lighten-4' : col.period === 'q2' ? 'orange-lighten-4' : 'purple-lighten-4'" variant="flat" class="ml-auto">
                {{ col.period === 'month' ? $t('month') : col.period.toUpperCase() }}
              </v-chip>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-card>

    <!-- ============= PRINT PREVIEW & CUSTOMIZATION MODAL ============= -->
  <v-dialog
    v-model="printPreviewDialog"
    max-width="1440"
    transition="dialog-bottom-transition"
  >
    <v-card
      class="rounded-xl overflow-hidden fnm-print-dialog-card"
      :class="{ 'fnm-theme--dark': main.is_dark, 'fnm-theme--light': !main.is_dark }"
    >
      <!-- Modal Header -->
      <div class="fnm-print-modal-header">
        <div class="d-flex align-center ga-3">
          <div class="fnm-print-header-icon">
            <v-icon size="24" color="#fff">mdi-printer-eye</v-icon>
          </div>
          <div>
            <div class="text-h6 font-weight-bold text-white leading-tight">{{ $t('print_preview') }}</div>
            <div class="text-caption text-white opacity-80">{{ $t('print_customization_desc') }}</div>
          </div>
        </div>

        <v-spacer></v-spacer>

        <!-- Zoom Controls -->
        <div class="d-none d-sm-flex align-center ga-1 mr-3 fnm-zoom-pill">
          <v-btn
            size="x-small"
            variant="text"
            rounded="pill"
            :color="printSettings.zoom === 75 ? (main.is_dark ? '#90CAF9' : '#1565C0') : (main.is_dark ? 'grey-lighten-2' : 'grey-darken-2')"
            :class="{ 'font-weight-bold fnm-zoom-btn--active': printSettings.zoom === 75 }"
            @click="printSettings.zoom = 75; savePrintSettings()"
          >
            75%
          </v-btn>
          <v-btn
            size="x-small"
            variant="text"
            rounded="pill"
            :color="printSettings.zoom === 90 ? (main.is_dark ? '#90CAF9' : '#1565C0') : (main.is_dark ? 'grey-lighten-2' : 'grey-darken-2')"
            :class="{ 'font-weight-bold fnm-zoom-btn--active': printSettings.zoom === 90 }"
            @click="printSettings.zoom = 90; savePrintSettings()"
          >
            90%
          </v-btn>
          <v-btn
            size="x-small"
            variant="text"
            rounded="pill"
            :color="printSettings.zoom === 100 ? (main.is_dark ? '#90CAF9' : '#1565C0') : (main.is_dark ? 'grey-lighten-2' : 'grey-darken-2')"
            :class="{ 'font-weight-bold fnm-zoom-btn--active': printSettings.zoom === 100 }"
            @click="printSettings.zoom = 100; savePrintSettings()"
          >
            100%
          </v-btn>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex align-center ga-2">
          <v-btn
            @click="triggerPrint"
            color="white"
            class="text-primary font-weight-bold"
            rounded="pill"
            prepend-icon="mdi-printer"
            elevation="2"
          >
            {{ $t('print_now') }}
          </v-btn>
          <v-btn
            icon
            variant="text"
            color="white"
            @click="printPreviewDialog = false"
          >
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <!-- Modal Body (Two-column layout: Controls + Preview) -->
      <v-card-text class="pa-0 fnm-print-modal-body">
        <div class="fnm-print-layout">
          <!-- Left Sidebar: Customization Controls -->
          <div class="fnm-print-sidebar">
            <div class="pa-4">
              <!-- Orientation -->
              <div class="fnm-print-ctrl-group">
                <div class="fnm-ctrl-title">
                  <v-icon size="16" color="#42A5F5" class="mr-1">mdi-page-layout-header</v-icon>
                  {{ $t('orientation') }}
                </div>
                <v-btn-toggle
                  v-model="printSettings.orientation"
                  mandatory
                  density="compact"
                  color="#42A5F5"
                  class="fnm-segmented-toggle w-100 mb-4"
                  @update:model-value="savePrintSettings"
                >
                  <v-btn value="landscape" class="flex-grow-1" prepend-icon="mdi-phone-rotate-landscape">
                    {{ $t('landscape') }}
                  </v-btn>
                  <v-btn value="portrait" class="flex-grow-1" prepend-icon="mdi-phone-rotate-portrait">
                    {{ $t('portrait') }}
                  </v-btn>
                </v-btn-toggle>
              </div>

              <!-- Density Scale -->
              <div class="fnm-print-ctrl-group">
                <div class="fnm-ctrl-title">
                  <v-icon size="16" color="#42A5F5" class="mr-1">mdi-format-size</v-icon>
                  {{ $t('font_size_scale') }}
                </div>
                <v-btn-toggle
                  v-model="printSettings.density"
                  mandatory
                  density="compact"
                  color="#42A5F5"
                  class="fnm-segmented-toggle w-100 mb-4"
                  @update:model-value="savePrintSettings"
                >
                  <v-btn value="compact" class="flex-grow-1">
                    {{ $t('compact') }}
                  </v-btn>
                  <v-btn value="standard" class="flex-grow-1">
                    {{ $t('standard') }}
                  </v-btn>
                  <v-btn value="spacious" class="flex-grow-1">
                    {{ $t('spacious') }}
                  </v-btn>
                </v-btn-toggle>
              </div>

              <!-- Document Elements Toggles -->
              <div class="fnm-print-ctrl-group">
                <div class="fnm-ctrl-title">
                  <v-icon size="16" color="#42A5F5" class="mr-1">mdi-layers-outline</v-icon>
                  {{ $t('table_styling') }}
                </div>
                <div class="fnm-switch-list">
                  <v-switch
                    v-model="printSettings.showLogo"
                    density="compact"
                    color="#42A5F5"
                    hide-details
                    :label="$t('show_company_logo')"
                    @update:model-value="savePrintSettings"
                  ></v-switch>
                  <v-switch
                    v-model="printSettings.showCompanyInfo"
                    density="compact"
                    color="#42A5F5"
                    hide-details
                    :label="$t('show_company_info')"
                    @update:model-value="savePrintSettings"
                  ></v-switch>
                  <v-switch
                    v-model="printSettings.showMetadataBanner"
                    density="compact"
                    color="#42A5F5"
                    hide-details
                    :label="$t('show_metadata_banner')"
                    @update:model-value="savePrintSettings"
                  ></v-switch>
                  <v-switch
                    v-model="printSettings.zebraStriping"
                    density="compact"
                    color="#42A5F5"
                    hide-details
                    :label="$t('zebra_striping')"
                    @update:model-value="savePrintSettings"
                  ></v-switch>
                  <v-switch
                    v-model="printSettings.showGrandTotals"
                    density="compact"
                    color="#42A5F5"
                    hide-details
                    :label="$t('show_grand_totals')"
                    @update:model-value="savePrintSettings"
                  ></v-switch>
                  <v-switch
                    v-model="printSettings.showSignatures"
                    density="compact"
                    color="#42A5F5"
                    hide-details
                    :label="$t('show_signatures')"
                    @update:model-value="savePrintSettings"
                  ></v-switch>
                  <v-switch
                    v-model="printSettings.showNotes"
                    density="compact"
                    color="#42A5F5"
                    hide-details
                    :label="$t('show_notes')"
                    @update:model-value="savePrintSettings"
                  ></v-switch>
                </div>
              </div>

              <!-- Notes Textarea -->
              <div v-if="printSettings.showNotes" class="mt-3">
                <v-textarea
                  v-model="printSettings.notes"
                  density="compact"
                  variant="outlined"
                  rounded
                  color="#42A5F5"
                  rows="2"
                  hide-details
                  :placeholder="$t('print_notes_placeholder')"
                  @update:model-value="savePrintSettings"
                ></v-textarea>
              </div>

              <!-- Columns Selection (Workforce summary tab) -->
              <div v-if="breakdownTab === 'workforces' && !drilldownItem" class="fnm-print-ctrl-group mt-4">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="fnm-ctrl-title mb-0">
                    <v-icon size="16" color="#42A5F5" class="mr-1">mdi-table-column</v-icon>
                    {{ $t('print_columns') }} ({{ activePrintColumns.length }})
                  </div>
                  <v-btn
                    size="x-small"
                    variant="text"
                    color="#42A5F5"
                    rounded="pill"
                    prepend-icon="mdi-restore"
                    class="text-none font-weight-bold"
                    @click="syncPrintColumnsWithTable"
                  >
                    {{ $t('reset_to_table_columns') }}
                  </v-btn>
                </div>
                <div class="fnm-print-cols-box">
                  <div class="d-flex ga-1 mb-2">
                    <v-btn size="x-small" variant="tonal" color="#42A5F5" rounded="pill" prepend-icon="mdi-eye" class="text-none font-weight-bold" @click="toggleAllPrintColumns(true)">
                      {{ $t('show_all') }}
                    </v-btn>
                    <v-btn size="x-small" variant="tonal" color="warning" rounded="pill" prepend-icon="mdi-eye-off" class="text-none font-weight-bold" @click="toggleAllPrintColumns(false)">
                      {{ $t('hide_all') }}
                    </v-btn>
                  </div>
                  <div class="text-caption text-grey mb-1 d-flex align-center" style="font-size: 11px;">
                    <v-icon size="14" class="mr-1">mdi-cursor-move</v-icon>
                    {{ $t('drag_to_reorder') }}
                  </div>
                  <div class="fnm-print-cols-list">
                    <div
                      v-for="(col, pIdx) in printColumns"
                      :key="'pcol-'+col.key"
                      class="fnm-print-col-row"
                      :class="{
                        'fnm-print-col-row--dragging': printColDragIdx === pIdx,
                        'fnm-print-col-row--dragover': printColDragOverIdx === pIdx,
                        'fnm-print-col-row--locked': col.locked
                      }"
                      :draggable="!col.locked"
                      @dragstart="onPrintColDragStart(pIdx, $event)"
                      @dragover="onPrintColDragOver(pIdx, $event)"
                      @dragleave="onPrintColDragLeave(pIdx, $event)"
                      @drop="onPrintColDrop(pIdx, $event)"
                      @dragend="onPrintColDragEnd"
                    >
                      <v-icon
                        v-if="!col.locked"
                        size="16"
                        class="fnm-print-drag-handle mr-1"
                      >mdi-drag-vertical</v-icon>
                      <v-icon
                        v-else
                        size="16"
                        class="mr-1 text-grey-lighten-1"
                      >mdi-lock-outline</v-icon>
                      <v-checkbox
                        :model-value="isPrintColSelected(col.key)"
                        :disabled="col.locked"
                        density="compact"
                        color="#42A5F5"
                        hide-details
                        class="flex-grow-1"
                        @update:model-value="(val) => onPrintColumnToggle(col.key, val)"
                      >
                        <template #label>
                          <span class="text-caption" :class="{ 'font-weight-bold': col.locked }">
                            {{ $t(col.label) }}
                          </span>
                          <v-chip
                            v-if="col.period"
                            size="x-small"
                            :color="col.period === 'q1' ? 'blue-lighten-4' : col.period === 'q2' ? 'orange-lighten-4' : 'purple-lighten-4'"
                            variant="flat"
                            class="ml-auto px-1"
                            style="font-size: 9px; height: 16px;"
                          >
                            {{ col.period === 'month' ? $t('month') : col.period.toUpperCase() }}
                          </v-chip>
                        </template>
                      </v-checkbox>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Area: Live Printable Document Stage -->
          <div class="fnm-print-stage">
            <div
              class="fnm-print-sheet-scaler"
              :style="{ zoom: printSettings.zoom / 100, transformOrigin: 'top center' }"
            >
              <!-- ============= PRINTABLE AREA (#fnm-print-area) ============= -->
              <div
                id="fnm-print-area"
                class="fnm-print-sheet v-theme--light"
                data-theme="light"
                :class="[
                  'fnm-print-' + printSettings.orientation,
                  'fnm-density-' + printSettings.density,
                  { 'fnm-zebra-table': printSettings.zebraStriping }
                ]"
              >

                <!-- Document Header -->
                <div class="fnm-pdoc-header">
                  <!-- Company Column -->
                  <div class="fnm-pdoc-company-col">
                    <img
                      v-if="printSettings.showLogo"
                      :src="printLogoUrl"
                      alt="Logo"
                      class="fnm-pdoc-logo"
                    />
                    <div v-if="printSettings.showCompanyInfo && companySettings.company_name" class="fnm-pdoc-company-info">
                      <div class="fnm-pdoc-company-name">{{ companySettings.company_name }}</div>
                      <div v-if="companySettings.company_address" class="fnm-pdoc-company-sub">{{ companySettings.company_address }}</div>
                      <div class="fnm-pdoc-company-reg">
                        <span v-if="companySettings.rc">RC: {{ companySettings.rc }}</span>
                        <span v-if="companySettings.ice"> | ICE: {{ companySettings.ice }}</span>
                        <span v-if="companySettings.cnss"> | CNSS: {{ companySettings.cnss }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Title Column -->
                  <div class="fnm-pdoc-title-col">
                    <div class="fnm-pdoc-title-box">
                      <div class="fnm-pdoc-title-main">{{ $t('activity_breakdown') }}</div>
                      <div class="fnm-pdoc-title-tab">{{ printTabLabel }}</div>
                    </div>
                    <div class="fnm-pdoc-period-badge">
                      <span>{{ monthNames[currentMonth] }} {{ currentYear }}</span>
                      <span class="mx-1">&bull;</span>
                      <span>{{ printPeriodLabel }}</span>
                    </div>
                  </div>

                  <!-- Metadata Column -->
                  <div class="fnm-pdoc-meta-col">
                    <div class="fnm-pdoc-meta-card">
                      <div class="fnm-pdoc-meta-line">
                        <span class="fnm-pdoc-meta-label">{{ $t('report_ref') }}:</span>
                        <span class="fnm-pdoc-meta-value">FNM-{{ currentYear }}{{ String(currentMonth + 1).padStart(2, '0') }}-{{ breakdownPeriod.toUpperCase() }}</span>
                      </div>
                      <div class="fnm-pdoc-meta-line">
                        <span class="fnm-pdoc-meta-label">{{ $t('printed_at') }}:</span>
                        <span class="fnm-pdoc-meta-value">{{ printDateTimeFormatted }}</span>
                      </div>
                      <div class="fnm-pdoc-meta-line">
                        <span class="fnm-pdoc-meta-label">{{ $t('status') }}:</span>
                        <span class="fnm-pdoc-meta-value">{{ filterDeclaredLabel }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Metadata Banner Strip -->
                <div v-if="printSettings.showMetadataBanner" class="fnm-pdoc-meta-strip">
                  <div class="fnm-pdoc-strip-cell">
                    <b>{{ $t('period') }}:</b> {{ printPeriodLabel }}
                  </div>
                  <div class="fnm-pdoc-strip-cell">
                    <b>{{ $t('tab') }}:</b> {{ printTabLabel }}
                  </div>
                  <div v-if="drilldownItem" class="fnm-pdoc-strip-cell">
                    <b>{{ printTabLabel }}:</b> {{ drilldownItem.name }}
                  </div>
                  <div class="fnm-pdoc-strip-cell">
                    <b>{{ $t('count') }}:</b> {{ printDataRowCount }}
                  </div>
                  <div class="fnm-pdoc-strip-cell">
                    <b>{{ $t('currency') || 'Devise' }}:</b> {{ main.currency || 'DH' }}
                  </div>
                </div>

                <!-- ================= TABLE: WORKFORCES SUMMARY ================= -->
                <div v-if="breakdownTab === 'workforces' && !drilldownItem" class="fnm-pdoc-table-wrap">
                  <table class="fnm-pdoc-table">
                    <thead>
                      <tr class="fnm-pdoc-head-tr">
                        <th
                          v-for="col in activePrintColumns"
                          :key="'pth-'+col.key"
                          :class="getPrintColAlignClass(col)"
                          class="fnm-pdoc-th"
                        >
                          {{ $t(col.label) }}
                          <span v-if="col.period" class="fnm-pdoc-th-period">
                            ({{ col.period === 'month' ? $t('month') : col.period.toUpperCase() }})
                          </span>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(w, rIdx) in (sortedFilteredWorkforces || breakdownData.workforces)"
                        :key="'pw-'+w.id"
                        class="fnm-pdoc-body-tr"
                        :class="{ 'fnm-pdoc-row-alt': printSettings.zebraStriping && rIdx % 2 === 1 }"
                      >
                        <td
                          v-for="col in activePrintColumns"
                          :key="'ptd-'+col.key+'-'+w.id"
                          :class="getPrintColAlignClass(col)"
                          class="fnm-pdoc-td"
                        >
                          <template v-if="col.type === 'boolean'">
                            <span :class="w[col.key] ? 'fnm-badge-yes' : 'fnm-badge-no'">
                              {{ w[col.key] ? $t('yes') : $t('no') }}
                            </span>
                          </template>
                          <template v-else-if="col.type === 'checkbox_q1' || col.type === 'checkbox_q2'">
                            <span :class="w[col.key] ? 'fnm-badge-paid' : 'fnm-badge-unpaid'">
                              {{ w[col.key] ? $t('paid') : $t('unpaid') }}
                            </span>
                          </template>
                          <template v-else-if="col.type === 'computed_declared'">
                            <span class="font-weight-bold text-success">{{ formatNumber(getDeclared(w)) }}</span>
                          </template>
                          <template v-else-if="col.type === 'computed_non_declared'">
                            <span class="font-weight-bold text-warning">{{ formatNumber(getNonDeclared(w)) }}</span>
                          </template>
                          <template v-else-if="col.type === 'currency'">
                            <span :class="{ 'font-weight-bold text-primary': col.bold }">{{ formatNumber(w[col.key]) }}</span>
                          </template>
                          <template v-else-if="col.type === 'number'">
                            {{ w[col.key] ?? 0 }}
                          </template>
                          <template v-else>
                            {{ w[col.key] || '-' }}
                          </template>
                        </td>
                      </tr>

                      <!-- 1:1 Aligned Totals Row (No colspan drift) -->
                      <tr class="fnm-pdoc-total-tr">
                        <td
                          v-for="(col, cIdx) in activePrintColumns"
                          :key="'ptot-'+col.key"
                          :class="getPrintColAlignClass(col)"
                          class="fnm-pdoc-total-td"
                        >
                          <template v-if="(activePrintColumns.some(c => c.key === 'name') ? col.key === 'name' : cIdx === 0)">
                            <b>{{ $t('total') }}</b>
                          </template>
                          <template v-else-if="col.key === 'q1_work_days'">
                            <b>{{ workforceTotals.q1_work_days }}</b>
                          </template>
                          <template v-else-if="col.key === 'q1_non_work_days'">
                            <b>{{ workforceTotals.q1_non_work_days }}</b>
                          </template>
                          <template v-else-if="col.key === 'q1_total'">
                            <b>{{ formatNumber(workforceTotals.q1_total) }}</b>
                          </template>
                          <template v-else-if="col.key === 'q2_work_days'">
                            <b>{{ workforceTotals.q2_work_days }}</b>
                          </template>
                          <template v-else-if="col.key === 'q2_non_work_days'">
                            <b>{{ workforceTotals.q2_non_work_days }}</b>
                          </template>
                          <template v-else-if="col.key === 'q2_total'">
                            <b>{{ formatNumber(workforceTotals.q2_total) }}</b>
                          </template>
                          <template v-else-if="col.key === 'month_total'">
                            <b class="text-primary">{{ formatNumber(workforceTotals.month_total) }}</b>
                          </template>
                          <template v-else-if="col.type === 'computed_declared'">
                            <b class="text-success">{{ formatNumber(workforceTotals.declared) }}</b>
                          </template>
                          <template v-else-if="col.type === 'computed_non_declared'">
                            <b class="text-warning">{{ formatNumber(workforceTotals.non_declared) }}</b>
                          </template>
                          <template v-else>
                            &nbsp;
                          </template>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- ================= TABLE: WORKFORCES DRILLDOWN ================= -->
                <div v-if="breakdownTab === 'workforces' && drilldownItem" class="fnm-pdoc-table-wrap">
                  <table class="fnm-pdoc-table">
                    <thead>
                      <tr class="fnm-pdoc-head-tr">
                        <th class="fnm-pdoc-th text-left">{{ $t('date') }}</th>
                        <th class="fnm-pdoc-th text-left">{{ $t('section') }} / {{ $t('corporal') }}</th>
                        <th class="fnm-pdoc-th text-right">{{ $t('price') }} ({{ main.currency || 'DH' }})</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(act, rIdx) in drilldownItem.activities"
                        :key="'pa-'+act.id"
                        class="fnm-pdoc-body-tr"
                        :class="{ 'fnm-pdoc-row-alt': printSettings.zebraStriping && rIdx % 2 === 1 }"
                      >
                        <td class="fnm-pdoc-td text-left">{{ main.formateDate(act.date) }}</td>
                        <td class="fnm-pdoc-td text-left">{{ act.section_name || act.corporal_name || '-' }}</td>
                        <td class="fnm-pdoc-td text-right font-weight-bold">{{ formatNumber(act.price) }}</td>
                      </tr>
                      <tr class="fnm-pdoc-total-tr">
                        <td colspan="2" class="fnm-pdoc-total-td text-left"><b>{{ $t('total') }}</b></td>
                        <td class="fnm-pdoc-total-td text-right"><b class="text-primary">{{ formatNumber(drilldownItem.activities.reduce((s, a) => s + parseFloat(a.price || 0), 0)) }}</b></td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- ================= TABLE: SECTIONS SUMMARY ================= -->
                <div v-if="breakdownTab === 'sections' && !drilldownItem" class="fnm-pdoc-table-wrap">
                  <table class="fnm-pdoc-table">
                    <thead>
                      <tr class="fnm-pdoc-head-tr fnm-head-amber">
                        <th class="fnm-pdoc-th text-left">{{ $t('name') }}</th>
                        <th class="fnm-pdoc-th text-center">Q1 {{ $t('count') }}</th>
                        <th class="fnm-pdoc-th text-right">Q1 {{ $t('total') }}</th>
                        <th class="fnm-pdoc-th text-center">Q2 {{ $t('count') }}</th>
                        <th class="fnm-pdoc-th text-right">Q2 {{ $t('total') }}</th>
                        <th class="fnm-pdoc-th text-right font-weight-bold">{{ $t('month_total') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(s, rIdx) in breakdownData.sections"
                        :key="'ps-'+s.id"
                        class="fnm-pdoc-body-tr"
                        :class="{ 'fnm-pdoc-row-alt': printSettings.zebraStriping && rIdx % 2 === 1 }"
                      >
                        <td class="fnm-pdoc-td text-left font-weight-bold">{{ s.name }}</td>
                        <td class="fnm-pdoc-td text-center">{{ s.q1_count }}</td>
                        <td class="fnm-pdoc-td text-right">{{ formatNumber(s.q1_total) }}</td>
                        <td class="fnm-pdoc-td text-center">{{ s.q2_count }}</td>
                        <td class="fnm-pdoc-td text-right">{{ formatNumber(s.q2_total) }}</td>
                        <td class="fnm-pdoc-td text-right font-weight-bold text-warning">{{ formatNumber(s.month_total) }}</td>
                      </tr>
                      <tr class="fnm-pdoc-total-tr fnm-total-amber">
                        <td class="fnm-pdoc-total-td text-left"><b>{{ $t('total') }}</b></td>
                        <td class="fnm-pdoc-total-td text-center"><b>{{ sectionTotals.q1_count }}</b></td>
                        <td class="fnm-pdoc-total-td text-right"><b>{{ formatNumber(sectionTotals.q1_total) }}</b></td>
                        <td class="fnm-pdoc-total-td text-center"><b>{{ sectionTotals.q2_count }}</b></td>
                        <td class="fnm-pdoc-total-td text-right"><b>{{ formatNumber(sectionTotals.q2_total) }}</b></td>
                        <td class="fnm-pdoc-total-td text-right"><b class="text-warning">{{ formatNumber(sectionTotals.month_total) }}</b></td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- ================= TABLE: SECTIONS/CORPORALS DRILLDOWN ================= -->
                <div v-if="(breakdownTab === 'sections' || breakdownTab === 'corporals') && drilldownItem && drilldownItem.workforces" class="fnm-pdoc-table-wrap">
                  <table class="fnm-pdoc-table">
                    <thead>
                      <tr class="fnm-pdoc-head-tr" :class="breakdownTab === 'sections' ? 'fnm-head-amber' : 'fnm-head-purple'">
                        <th class="fnm-pdoc-th text-left">{{ $t('full_name') }}</th>
                        <th class="fnm-pdoc-th text-center">{{ $t('matricule') }}</th>
                        <th class="fnm-pdoc-th text-center">{{ $t('nationality') }}</th>
                        <th class="fnm-pdoc-th text-center">{{ $t('sex') }}</th>
                        <th class="fnm-pdoc-th text-center">{{ $t('is_migrant') }}</th>
                        <th class="fnm-pdoc-th text-center">Q1 {{ $t('count') }}</th>
                        <th class="fnm-pdoc-th text-right">Q1 {{ $t('total') }}</th>
                        <th class="fnm-pdoc-th text-center">Q2 {{ $t('count') }}</th>
                        <th class="fnm-pdoc-th text-right">Q2 {{ $t('total') }}</th>
                        <th class="fnm-pdoc-th text-right font-weight-bold">{{ $t('month_total') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(wf, rIdx) in drilldownItem.workforces"
                        :key="'pdw-'+wf.id"
                        class="fnm-pdoc-body-tr"
                        :class="{ 'fnm-pdoc-row-alt': printSettings.zebraStriping && rIdx % 2 === 1 }"
                      >
                        <td class="fnm-pdoc-td text-left font-weight-bold">{{ wf.name }}</td>
                        <td class="fnm-pdoc-td text-center">{{ wf.matricule }}</td>
                        <td class="fnm-pdoc-td text-center">{{ wf.nationality }}</td>
                        <td class="fnm-pdoc-td text-center">{{ $t(wf.sex) }}</td>
                        <td class="fnm-pdoc-td text-center">
                          <span :class="wf.is_migrant ? 'fnm-badge-yes' : 'fnm-badge-no'">
                            {{ wf.is_migrant ? $t('yes') : $t('no') }}
                          </span>
                        </td>
                        <td class="fnm-pdoc-td text-center">{{ wf.q1_count }}</td>
                        <td class="fnm-pdoc-td text-right">{{ formatNumber(wf.q1_total) }}</td>
                        <td class="fnm-pdoc-td text-center">{{ wf.q2_count }}</td>
                        <td class="fnm-pdoc-td text-right">{{ formatNumber(wf.q2_total) }}</td>
                        <td class="fnm-pdoc-td text-right font-weight-bold text-primary">{{ formatNumber(wf.month_total) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- ================= TABLE: CORPORALS SUMMARY ================= -->
                <div v-if="breakdownTab === 'corporals' && !drilldownItem" class="fnm-pdoc-table-wrap">
                  <table class="fnm-pdoc-table">
                    <thead>
                      <tr class="fnm-pdoc-head-tr fnm-head-purple">
                        <th class="fnm-pdoc-th text-left">{{ $t('name') }}</th>
                        <th class="fnm-pdoc-th text-center">Q1 {{ $t('count') }}</th>
                        <th class="fnm-pdoc-th text-right">Q1 {{ $t('total') }}</th>
                        <th class="fnm-pdoc-th text-center">Q2 {{ $t('count') }}</th>
                        <th class="fnm-pdoc-th text-right">Q2 {{ $t('total') }}</th>
                        <th class="fnm-pdoc-th text-right font-weight-bold">{{ $t('month_total') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(c, rIdx) in breakdownData.corporals"
                        :key="'pc-'+c.id"
                        class="fnm-pdoc-body-tr"
                        :class="{ 'fnm-pdoc-row-alt': printSettings.zebraStriping && rIdx % 2 === 1 }"
                      >
                        <td class="fnm-pdoc-td text-left font-weight-bold">{{ c.name }}</td>
                        <td class="fnm-pdoc-td text-center">{{ c.q1_count }}</td>
                        <td class="fnm-pdoc-td text-right">{{ formatNumber(c.q1_total) }}</td>
                        <td class="fnm-pdoc-td text-center">{{ c.q2_count }}</td>
                        <td class="fnm-pdoc-td text-right">{{ formatNumber(c.q2_total) }}</td>
                        <td class="fnm-pdoc-td text-right font-weight-bold text-purple">{{ formatNumber(c.month_total) }}</td>
                      </tr>
                      <tr class="fnm-pdoc-total-tr fnm-total-purple">
                        <td class="fnm-pdoc-total-td text-left"><b>{{ $t('total') }}</b></td>
                        <td class="fnm-pdoc-total-td text-center"><b>{{ corporalTotals.q1_count }}</b></td>
                        <td class="fnm-pdoc-total-td text-right"><b>{{ formatNumber(corporalTotals.q1_total) }}</b></td>
                        <td class="fnm-pdoc-total-td text-center"><b>{{ corporalTotals.q2_count }}</b></td>
                        <td class="fnm-pdoc-total-td text-right"><b>{{ formatNumber(corporalTotals.q2_total) }}</b></td>
                        <td class="fnm-pdoc-total-td text-right"><b class="text-purple">{{ formatNumber(corporalTotals.month_total) }}</b></td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Grand Totals Cards Block -->
                <div v-if="printSettings.showGrandTotals" class="fnm-pdoc-grand-totals">
                  <div class="fnm-pdoc-gt-card">
                    <div class="fnm-pdoc-gt-label">{{ $t('first_fortnight') }} (Q1)</div>
                    <div class="fnm-pdoc-gt-value text-primary">
                      {{ formatNumber(grandTotals.q1_total) }} <span class="fnm-pdoc-gt-cur">{{ main.currency || 'DH' }}</span>
                    </div>
                  </div>
                  <div class="fnm-pdoc-gt-card">
                    <div class="fnm-pdoc-gt-label">{{ $t('second_fortnight') }} (Q2)</div>
                    <div class="fnm-pdoc-gt-value text-primary">
                      {{ formatNumber(grandTotals.q2_total) }} <span class="fnm-pdoc-gt-cur">{{ main.currency || 'DH' }}</span>
                    </div>
                  </div>
                  <div class="fnm-pdoc-gt-card fnm-pdoc-gt-card-month">
                    <div class="fnm-pdoc-gt-label">{{ $t('total_month') }}</div>
                    <div class="fnm-pdoc-gt-value text-purple">
                      {{ formatNumber(grandTotals.month_total) }} <span class="fnm-pdoc-gt-cur">{{ main.currency || 'DH' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Notes / Observations Block -->
                <div v-if="printSettings.showNotes && printSettings.notes" class="fnm-pdoc-notes-block">
                  <div class="fnm-pdoc-notes-title">{{ $t('notes') || 'Observations' }}:</div>
                  <div class="fnm-pdoc-notes-body">{{ printSettings.notes }}</div>
                </div>

                <!-- Official Signatures Block -->
                <div v-if="printSettings.showSignatures" class="fnm-pdoc-signatures">
                  <div class="fnm-pdoc-sig-box">
                    <div class="fnm-pdoc-sig-header">{{ $t('prepared_by') }}</div>
                    <div class="fnm-pdoc-sig-space"></div>
                    <div class="fnm-pdoc-sig-line">{{ $t('date') }}: ____________________</div>
                  </div>
                  <div class="fnm-pdoc-sig-box">
                    <div class="fnm-pdoc-sig-header">{{ $t('verified_by') }}</div>
                    <div class="fnm-pdoc-sig-space"></div>
                    <div class="fnm-pdoc-sig-line">{{ $t('date') }}: ____________________</div>
                  </div>
                  <div class="fnm-pdoc-sig-box">
                    <div class="fnm-pdoc-sig-header">{{ $t('approved_by') }}</div>
                    <div class="fnm-pdoc-sig-space"></div>
                    <div class="fnm-pdoc-sig-line">{{ $t('date') }}: ____________________</div>
                  </div>
                </div>

                <!-- Document Footer -->
                <div class="fnm-pdoc-footer">
                  <div v-if="printSettings.showCompanyInfo && companySettings.company_name" class="fnm-pdoc-footer-legal">
                    <span>{{ companySettings.company_name }}</span>
                    <span v-if="companySettings.company_address"> &bull; {{ companySettings.company_address }}</span>
                    <span v-if="companySettings.rc"> &bull; RC: {{ companySettings.rc }}</span>
                    <span v-if="companySettings.ice"> &bull; ICE: {{ companySettings.ice }}</span>
                  </div>
                  <div class="fnm-pdoc-footer-notice">
                    <span>{{ $t('confidential_document') }}</span>
                    <span> &bull; </span>
                    <span>{{ $t('printed_at') }}: {{ printDateTimeFormatted }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="8000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="360"
      max-width="440"
      @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
    >
      <v-progress-linear :model-value="snackbarProgress" color="#42A5F5" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="summarySnackbar.iconColor || 'success'" class="mr-2">{{ summarySnackbar.icon || 'mdi-check-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.workforce">
          <v-icon size="14" color="#42A5F5" class="mr-1">{{ summarySnackbar.workforceIcon || 'mdi-account-hard-hat' }}</v-icon>
          <span class="text-medium-emphasis">{{ summarySnackbar.workforceLabel || $t('workforce') }}:</span> <strong>{{ summarySnackbar.workforce }}</strong>
        </div>
        <div v-if="summarySnackbar.section">
          <v-icon size="14" color="#42A5F5" class="mr-1">mdi-sitemap-outline</v-icon>
          <span class="text-medium-emphasis">{{ $t('section_affiliated') }}:</span> <strong>{{ summarySnackbar.section }}</strong>
        </div>
        <div v-if="summarySnackbar.corporal">
          <v-icon size="14" color="#42A5F5" class="mr-1">mdi-account-tie</v-icon>
          <span class="text-medium-emphasis">{{ $t('corporal') }}:</span> <strong>{{ summarySnackbar.corporal }}</strong>
        </div>
        <div v-if="summarySnackbar.date">
          <v-icon size="14" color="#42A5F5" class="mr-1">mdi-calendar</v-icon>
          <span class="text-medium-emphasis">{{ $t('date') }}:</span> <strong>{{ summarySnackbar.date }}</strong>
        </div>
        <div v-if="summarySnackbar.days">
          <v-icon size="14" color="#42A5F5" class="mr-1">mdi-calendar-check</v-icon>
          <span class="text-medium-emphasis">{{ $t('activities_count') || $t('days') }}:</span> <strong>{{ summarySnackbar.days }}</strong>
        </div>
        <v-divider class="my-1" v-if="summarySnackbar.amount"></v-divider>
        <div v-if="summarySnackbar.amount" class="d-flex ga-4">
          <div>
            <v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon>
            <span class="text-medium-emphasis">{{ $t('amount') }}:</span> <strong class="text-success">{{ summarySnackbar.amount }}</strong>
          </div>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useWorkforce } from '@/stores/Workforce';
import { useCorporal } from '@/stores/Corporal';
import { useSection } from '@/stores/Section';
import { useFortnightActivity } from '@/stores/FortnightActivity';
import { useFortnightSetting } from '@/stores/FortnightSetting';
import { useNotification } from '@/stores/Notification';
import mitt from '@/plugins/mitt.js';
import print from 'vue3-print-nb';
import axios from 'axios';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';

export default {
  name: 'FortnightManagement',
  components: { SegmentedPillToggle },
  directives: { print },
  data() {
    const now = new Date();
    return {
      viewMode: 'all', // 'all' | 'calendar' | 'breakdown'
      summarySnackbar: {
        show: false,
        title: '',
        workforce: '',
        section: '',
        corporal: '',
        date: '',
        days: '',
        amount: '',
        icon: 'mdi-check-circle',
        iconColor: 'success',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
      currentYear: now.getFullYear(),
      currentMonth: now.getMonth(),
      currentHalf: now.getDate() <= 15 ? 1 : 2,
      selectedWorkforceIds: [],
      selectedDate: this.formatDateStr(now),
      activityForm: { section_id: null, corporal_id: null, price: 0 },
      fixedDailyPrice: 0,
      submitting: false,
      deleting: false,
      deletingCell: null,
      multiSelectMode: false,
      selectedDates: [],
      bulkDeleting: false,
      dayDetailDialog: false,
      dayDetailDate: '',
      dayDetailActivities: [],
      dayDetailLoading: false,
      deletingDetailId: null,
      exporting: false,
      companySettings: {},
      editDialog: false,
      editForm: { id: null, section_id: null, corporal_id: null, price: 0 },
      editSaving: false,
      breakdownTab: 'workforces',
      breakdownPeriod: 'month',
      showDeclared: true,
      showNonDeclared: true,
      workforceSearch: '',
      drilldownItem: null,
      monthNames: [
        this.$t('month_january') || 'January', this.$t('month_february') || 'February',
        this.$t('month_march') || 'March', this.$t('month_april') || 'April',
        this.$t('month_may') || 'May', this.$t('month_june') || 'June',
        this.$t('month_july') || 'July', this.$t('month_august') || 'August',
        this.$t('month_september') || 'September', this.$t('month_october') || 'October',
        this.$t('month_november') || 'November', this.$t('month_december') || 'December',
      ],
      dayLabels: [
        this.$t('day_sun') || 'SUN', this.$t('day_mon') || 'MON',
        this.$t('day_tue') || 'TUE', this.$t('day_wed') || 'WED',
        this.$t('day_thu') || 'THU', this.$t('day_fri') || 'FRI',
        this.$t('day_sat') || 'SAT',
      ],
      colSettingsDialog: false,
      colDragIdx: null,
      // Column filter/sort state for workforce breakdown table
      wfSortRules: [],         // [{key, dir}] multi-sort: dir = 'asc' | 'desc'
      wfColumnFilters: {},     // { column_key: { text, min, max, bool } }
      wfFilterMenuCol: null,   // which column's filter menu is open
      printPreviewDialog: false,
      printColumns: [],
      printColDragIdx: null,
      printColDragOverIdx: null,
      printSettings: {
        orientation: 'landscape',
        density: 'compact',
        showLogo: true,
        showCompanyInfo: true,
        showMetadataBanner: true,
        showGrandTotals: true,
        showSignatures: true,
        zebraStriping: true,
        showNotes: false,
        notes: '',
        selectedColKeys: [],
        columnOrder: [],
        zoom: 100,
      },
      allWorkforceColumns: [
        { key: 'name', label: 'full_name', type: 'chip', visible: true, locked: true },
        { key: 'matricule', label: 'matricule', type: 'text', visible: true },
        { key: 'cin', label: 'cin', type: 'text', visible: true },
        { key: 'cnss_id', label: 'cnss_id', type: 'text', visible: true },
        { key: 'nationality', label: 'nationality', type: 'text', visible: true },
        { key: 'sex', label: 'sex', type: 'text', visible: true },
        { key: 'is_migrant', label: 'is_migrant', type: 'boolean', visible: true, color: 'warning' },
        { key: 'salary_statement', label: 'salary_statement', type: 'boolean', visible: true, color: 'success' },
        { key: 'fixed_daily_pay', label: 'fixed_daily_pay', type: 'boolean', visible: true, color: 'info' },
        { key: 'q1_work_days', label: 'work_days_q1', type: 'number', visible: true, period: 'q1' },
        { key: 'q1_non_work_days', label: 'non_work_days_q1', type: 'number', visible: true, period: 'q1' },
        { key: 'q1_total', label: 'q1_total', type: 'currency', visible: true, period: 'q1' },
        { key: 'q2_work_days', label: 'work_days_q2', type: 'number', visible: true, period: 'q2' },
        { key: 'q2_non_work_days', label: 'non_work_days_q2', type: 'number', visible: true, period: 'q2' },
        { key: 'q2_total', label: 'q2_total', type: 'currency', visible: true, period: 'q2' },
        { key: 'month_total', label: 'month_total', type: 'currency', visible: true, period: 'month', bold: true },
        { key: 'is_paid_q1', label: 'q1_paid', type: 'checkbox_q1', visible: true, period: 'q1' },
        { key: 'is_paid_q2', label: 'q2_paid', type: 'checkbox_q2', visible: true, period: 'q2' },
        { key: 'declared', label: 'declared', type: 'computed_declared', visible: true, headerColor: '#2E7D32', cellColor: '#2E7D32' },
        { key: 'non_declared', label: 'non_declared', type: 'computed_non_declared', visible: true, headerColor: '#E65100', cellColor: '#E65100' },
      ],
    };
  },
  computed: {
    main() { return useMain(); },
    workforceStore() { return useWorkforce(); },
    corporalStore() { return useCorporal(); },
    sectionStore() { return useSection(); },
    activityStore() { return useFortnightActivity(); },
    fortnightSettingStore() { return useFortnightSetting(); },
    notification() { return useNotification(); },
    summary() { return this.activityStore.summary; },
    /** Columns visible in the workforce breakdown table, respecting period filter */
    activePrintColumns() {
      const baseList = (this.printColumns && this.printColumns.length)
        ? this.printColumns
        : this.visibleWorkforceColumns;
      if (this.printSettings.selectedColKeys && this.printSettings.selectedColKeys.length > 0) {
        return baseList.filter(c => this.printSettings.selectedColKeys.includes(c.key));
      }
      return baseList;
    },
    printDateTimeFormatted() {
      const now = new Date();
      const pad = (n) => String(n).padStart(2, '0');
      return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
    },
    filterDeclaredLabel() {
      if (this.showDeclared && this.showNonDeclared) return this.$t('all') || 'All';
      if (this.showDeclared) return this.$t('declared') || 'Declared';
      if (this.showNonDeclared) return this.$t('non_declared') || 'Non-Declared';
      return '-';
    },
    printDataRowCount() {
      if (this.breakdownTab === 'workforces') {
        if (this.drilldownItem) return this.drilldownItem.activities ? this.drilldownItem.activities.length : 0;
        return (this.sortedFilteredWorkforces || this.breakdownData.workforces || []).length;
      }
      if (this.breakdownTab === 'sections') {
        if (this.drilldownItem) return this.drilldownItem.workforces ? this.drilldownItem.workforces.length : 0;
        return (this.breakdownData.sections || []).length;
      }
      if (this.breakdownTab === 'corporals') {
        if (this.drilldownItem) return this.drilldownItem.workforces ? this.drilldownItem.workforces.length : 0;
        return (this.breakdownData.corporals || []).length;
      }
      return 0;
    },
    visibleWorkforceColumns() {
      const p = this.breakdownPeriod;
      return this.allWorkforceColumns.filter(col => {
        if (!col.visible) return false;
        if (!col.period) return true;
        if (col.period === 'month') return p === 'month';
        if (col.period === 'q1') return p === 'q1' || p === 'month';
        if (col.period === 'q2') return p === 'q2' || p === 'month';
        return true;
      });
    },
    /** Number of non-period (info) columns for total row colspan */
    totalColspan() {
      return this.visibleWorkforceColumns.filter(c => !c.period && c.type !== 'computed_declared' && c.type !== 'computed_non_declared').length;
    },
    /** First selected workforce - used for calendar display */
    primaryWorkforce() {
      if (!this.selectedWorkforceIds || !this.selectedWorkforceIds.length) return null;
      return this.workforceStore.list.find(w => w.id === this.selectedWorkforceIds[0]);
    },
    /** For backward compat with calendar - use first selected */
    selectedWorkforceId() {
      return this.selectedWorkforceIds && this.selectedWorkforceIds.length ? this.selectedWorkforceIds[0] : null;
    },
    formattedDate() {
      return `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${this.selectedDate ? this.selectedDate.split('-')[2] : '01'}`;
    },
    fortnightPeriod() {
      return this.currentHalf === 1 ? 'Q1' : 'Q2';
    },
    canSubmitActivity() {
      return this.selectedWorkforceIds && this.selectedWorkforceIds.length > 0 && this.selectedDate &&
        (this.activityForm.section_id || this.activityForm.corporal_id) &&
        this.activityForm.price >= 0;
    },
    canDeleteActivities() {
      return this.selectedWorkforceIds && this.selectedWorkforceIds.length >= 1 && this.selectedDate;
    },
    /** Whether no workforce is selected â€” enables the aggregated calendar view */
    noWorkforceSelected() {
      return !this.selectedWorkforceIds || this.selectedWorkforceIds.length === 0;
    },
    /** Unique workforce count in day detail activities */
    dayDetailWorkforceCount() {
      const ids = new Set(this.dayDetailActivities.map(a => a.workforce_id));
      return ids.size;
    },
    /** Total price of day detail activities */
    dayDetailTotal() {
      return this.dayDetailActivities.reduce((sum, a) => sum + parseFloat(a.price || 0), 0);
    },
    /** Whether there are multi-selected dates */
    hasMultiSelection() {
      return this.multiSelectMode && this.selectedDates.length > 0;
    },
    /** Whether multiple workforces are selected - used to show workforce name on calendar chips */
    multipleSelected() {
      return this.selectedWorkforceIds && this.selectedWorkforceIds.length > 1;
    },
    /** IDs of selected workforces that have fixed_daily_pay enabled */
    fixedDailyPayWorkforceIds() {
      if (!this.selectedWorkforceIds || !this.selectedWorkforceIds.length) return [];
      return this.selectedWorkforceIds.filter(id => {
        const wf = this.workforceStore.list.find(w => w.id === id);
        return wf && wf.fixed_daily_pay;
      });
    },
    /** Count of selected workforces with fixed_daily_pay */
    fixedDailyPayCount() {
      return this.fixedDailyPayWorkforceIds.length;
    },
    /** Whether to show the fixed daily price input */
    hasFixedDailyPaySelected() {
      return this.fixedDailyPayCount > 0;
    },
    /** IDs of selected workforces that do NOT have fixed_daily_pay */
    regularWorkforceIds() {
      if (!this.selectedWorkforceIds || !this.selectedWorkforceIds.length) return [];
      return this.selectedWorkforceIds.filter(id => !this.fixedDailyPayWorkforceIds.includes(id));
    },
    breakdownData() { return this.activityStore.breakdown; },
    /**
     * Filter workforces based on declared/non-declared checkboxes.
     * Declared = salary_statement enabled (true)
     * Non-Declared = salary_statement disabled (false)
     */
    filteredWorkforces() {
      let wfs = this.breakdownData.workforces || [];
      
      if (!this.showDeclared && !this.showNonDeclared) return [];
      if (this.showDeclared && !this.showNonDeclared) wfs = wfs.filter(w => w.salary_statement === true);
      if (!this.showDeclared && this.showNonDeclared) wfs = wfs.filter(w => w.salary_statement === false);
      
      if (this.workforceSearch && this.workforceSearch.trim() !== '') {
        const query = this.workforceSearch.trim().toLowerCase();
        wfs = wfs.filter(w => (w.name || '').toLowerCase().includes(query));
      }
      return wfs;
    },
    /** Apply column filters + multi-sort on top of declared/non-declared filter */
    sortedFilteredWorkforces() {
      let result = [...this.filteredWorkforces];
      // Apply column filters
      const filters = this.wfColumnFilters;
      for (const colKey of Object.keys(filters)) {
        const f = filters[colKey];
        if (!f) continue;
        const col = this.allWorkforceColumns.find(c => c.key === colKey);
        if (!col) continue;
        result = result.filter(w => {
          const raw = this.getRawCellValue(w, col);
          // Checklist filter (text/chip columns)
          if (f.checked && f.checked.length > 0) {
            const val = String(raw || '');
            if (!f.checked.includes(val)) return false;
          }
          // Boolean filter
          if (f.bool !== undefined && f.bool !== null && f.bool !== '') {
            const boolVal = f.bool === 'true' || f.bool === true;
            if (raw !== boolVal) return false;
          }
          // Min/max range filter
          const numVal = parseFloat(raw);
          if (f.min !== '' && f.min !== undefined && f.min !== null) {
            if (isNaN(numVal) || numVal < parseFloat(f.min)) return false;
          }
          if (f.max !== '' && f.max !== undefined && f.max !== null) {
            if (isNaN(numVal) || numVal > parseFloat(f.max)) return false;
          }
          return true;
        });
      }
      // Apply multi-sort
      if (this.wfSortRules.length > 0) {
        result.sort((a, b) => {
          for (const rule of this.wfSortRules) {
            const col = this.allWorkforceColumns.find(c => c.key === rule.key);
            if (!col) continue;
            const valA = this.getRawCellValue(a, col);
            const valB = this.getRawCellValue(b, col);
            let cmp = 0;
            if (typeof valA === 'number' && typeof valB === 'number') {
              cmp = valA - valB;
            } else if (typeof valA === 'boolean' && typeof valB === 'boolean') {
              cmp = (valA === valB) ? 0 : (valA ? -1 : 1);
            } else {
              cmp = String(valA || '').localeCompare(String(valB || ''), undefined, { numeric: true });
            }
            if (cmp !== 0) return rule.dir === 'desc' ? -cmp : cmp;
          }
          return 0;
        });
      }
      return result;
    },
    /** Whether any column filter is active */
    hasActiveWfFilters() {
      for (const key of Object.keys(this.wfColumnFilters)) {
        const f = this.wfColumnFilters[key];
        if (!f) continue;
        if (f.checked && f.checked.length > 0) return true;
        if (f.bool !== undefined && f.bool !== null && f.bool !== '') return true;
        if (f.min !== '' && f.min !== undefined && f.min !== null) return true;
        if (f.max !== '' && f.max !== undefined && f.max !== null) return true;
      }
      return false;
    },
    /** Count of active column filters */
    activeWfFilterCount() {
      let count = 0;
      for (const key of Object.keys(this.wfColumnFilters)) {
        const f = this.wfColumnFilters[key];
        if (!f) continue;
        if ((f.checked && f.checked.length > 0) || (f.bool !== undefined && f.bool !== null && f.bool !== '') ||
            (f.min !== '' && f.min !== undefined && f.min !== null) ||
            (f.max !== '' && f.max !== undefined && f.max !== null)) {
          count++;
        }
      }
      return count;
    },
    sectionTotals() {
      const secs = this.breakdownData.sections || [];
      return {
        q1_count: secs.reduce((s, r) => s + r.q1_count, 0),
        q1_total: secs.reduce((s, r) => s + r.q1_total, 0),
        q2_count: secs.reduce((s, r) => s + r.q2_count, 0),
        q2_total: secs.reduce((s, r) => s + r.q2_total, 0),
        month_count: secs.reduce((s, r) => s + r.month_count, 0),
        month_total: secs.reduce((s, r) => s + r.month_total, 0),
      };
    },
    corporalTotals() {
      const corps = this.breakdownData.corporals || [];
      return {
        q1_count: corps.reduce((s, r) => s + r.q1_count, 0),
        q1_total: corps.reduce((s, r) => s + r.q1_total, 0),
        q2_count: corps.reduce((s, r) => s + r.q2_count, 0),
        q2_total: corps.reduce((s, r) => s + r.q2_total, 0),
        month_count: corps.reduce((s, r) => s + r.month_count, 0),
        month_total: corps.reduce((s, r) => s + r.month_total, 0),
      };
    },
    workforceTotals() {
      const wfs = this.sortedFilteredWorkforces;
      return {
        q1_count: wfs.reduce((s, r) => s + r.q1_count, 0),
        q1_total: wfs.reduce((s, r) => s + r.q1_total, 0),
        q2_count: wfs.reduce((s, r) => s + r.q2_count, 0),
        q2_total: wfs.reduce((s, r) => s + r.q2_total, 0),
        month_total: wfs.reduce((s, r) => s + r.month_total, 0),
        q1_work_days: wfs.reduce((s, r) => s + (r.q1_work_days || 0), 0),
        q1_non_work_days: wfs.reduce((s, r) => s + (r.q1_non_work_days || 0), 0),
        q2_work_days: wfs.reduce((s, r) => s + (r.q2_work_days || 0), 0),
        q2_non_work_days: wfs.reduce((s, r) => s + (r.q2_non_work_days || 0), 0),
        declared: wfs.reduce((s, r) => s + this.getDeclared(r), 0),
        non_declared: wfs.reduce((s, r) => s + this.getNonDeclared(r), 0),
      };
    },
    breakdownTabIcon() {
      const icons = { workforces: 'mdi-account-multiple', sections: 'mdi-sitemap-outline', corporals: 'mdi-account-tie' };
      return icons[this.breakdownTab] || 'mdi-chart-box-outline';
    },
    breakdownTabLabel() {
      const labels = { workforces: this.$t('workforces_activities'), sections: this.$t('sections_activities'), corporals: this.$t('corporals_activities') };
      return labels[this.breakdownTab] || '';
    },
    drilldownIcon() {
      if (this.breakdownTab === 'workforces') return 'mdi-account';
      if (this.breakdownTab === 'sections') return 'mdi-sitemap-outline';
      return 'mdi-account-tie';
    },
    grandTotals() {
      return {
        q1_total: this.sectionTotals.q1_total + this.corporalTotals.q1_total,
        q2_total: this.sectionTotals.q2_total + this.corporalTotals.q2_total,
        month_total: this.sectionTotals.month_total + this.corporalTotals.month_total,
      };
    },
    priceReadonly() {
      if (this.activityForm.section_id) {
        const section = this.sectionStore.list.find(s => s.id === this.activityForm.section_id);
        return section && !section.is_price_modifiable;
      }
      return false;
    },
    editPriceReadonly() {
      if (this.editForm.section_id) {
        const section = this.sectionStore.list.find(s => s.id === this.editForm.section_id);
        return section && !section.is_price_modifiable;
      }
      return false;
    },
    /** Logo URL for print area - uses Order Transfer invoice logo pattern */
    printLogoUrl() {
      if (this.companySettings.logo_invoice_url) return this.companySettings.logo_invoice_url;
      return new URL('/storage/images/logo_login.png', window.location.origin).href;
    },
    /** Print configuration for vue3-print-nb */
    printCfg() {
      return {
        id: 'fnm-print-area',
        popTitle: `${this.$t('activity_breakdown')} - ${this.monthNames[this.currentMonth]} ${this.currentYear}`,
        extraHead: `<style>${this.printDocStyles}</style>`,
      };
    },
    printDocStyles() {
      const density = this.printSettings.density || 'compact';
      const cellPad = density === 'compact' ? '4px 6px' : density === 'spacious' ? '8px 12px' : '6px 8px';
      const fontSize = density === 'compact' ? '10px' : density === 'spacious' ? '12px' : '11px';
      return this.getPrintCss(fontSize, cellPad);
    },
    /** Human-readable label for current breakdown tab */
    printTabLabel() {
      const labels = { workforces: this.$t('workforces_activities'), sections: this.$t('sections_activities'), corporals: this.$t('corporals_activities') };
      return labels[this.breakdownTab] || '';
    },
    /** Human-readable label for current period toggle */
    printPeriodLabel() {
      const labels = { q1: this.$t('first_fortnight'), q2: this.$t('second_fortnight'), month: this.$t('month') };
      return labels[this.breakdownPeriod] || '';
    },
    /** Build calendar cells for the current month */
    calendarCells() {
      const cells = [];
      const year = this.currentYear;
      const month = this.currentMonth;
      const firstDay = new Date(year, month, 1).getDay(); // 0=Sun
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const daysInPrevMonth = new Date(year, month, 0).getDate();
      const activitiesByDate = this.activityStore.activitiesByDate;
      const dailyTotalsByDate = this.activityStore.dailyTotalsByDate;
      const isOverview = this.noWorkforceSelected;
      const today = new Date();

      // Previous month fill
      for (let i = firstDay - 1; i >= 0; i--) {
        const d = daysInPrevMonth - i;
        cells.push({ day: d, isOutside: true, monthLabel: null, activities: [], isToday: false, inActiveHalf: false, isSummary: false, summaryData: null });
      }

      // Current month
      const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate()); // midnight today
      for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const cellDate = new Date(year, month, d);
        const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === d;
        const isFuture = cellDate > todayDate;
        const inActiveHalf = this.currentHalf === 1 ? d <= 15 : d >= 16;

        if (isOverview) {
          // Aggregated daily totals mode
          const dt = dailyTotalsByDate[dateStr] || null;
          const hasData = dt && dt.total_price > 0;
          cells.push({
            day: d,
            dateStr,
            isOutside: false,
            isToday,
            isFuture,
            inActiveHalf,
            activities: hasData ? [{ id: `summary-${dateStr}`, price: dt.total_price, count: dt.count }] : [],
            isSummary: true,
            summaryData: dt,
            monthLabel: d === 1 ? this.monthNames[month]?.substring(0, 3) : null,
          });
        } else {
          // Normal mode: individual activities per workforce
          const dayActivities = activitiesByDate[dateStr] || [];
          cells.push({
            day: d,
            dateStr,
            isOutside: false,
            isToday,
            isFuture,
            inActiveHalf,
            activities: dayActivities,
            isSummary: false,
            summaryData: null,
            monthLabel: d === 1 ? this.monthNames[month]?.substring(0, 3) : null,
          });
        }
      }

      // Next month fill to complete grid
      const remaining = (7 - (cells.length % 7)) % 7;
      for (let i = 1; i <= remaining; i++) {
        cells.push({ day: i, isOutside: true, monthLabel: null, activities: [], isToday: false, inActiveHalf: false, isSummary: false, summaryData: null });
      }

      return cells;
    },
  },
  watch: {
    fortnightPeriod() {
      this.loadActivities();
      this.loadSummary();
      this.loadBreakdown();
    },
    selectedWorkforceIds() {
      this.loadActivities();
      this.loadSummary();
      this.loadBreakdown();
      // Auto-fill fixedDailyPrice from the first fixed workforce's daily_price
      if (this.fixedDailyPayWorkforceIds.length > 0) {
        const firstFixed = this.workforceStore.list.find(w => w.id === this.fixedDailyPayWorkforceIds[0]);
        if (firstFixed && firstFixed.daily_price) {
          this.fixedDailyPrice = parseFloat(firstFixed.daily_price) || 0;
        }
      } else {
        this.fixedDailyPrice = 0;
      }
    },
    currentMonth() {
      this.loadActivities();
      this.loadSummary();
      this.loadBreakdown();
    },
    currentYear() {
      this.loadActivities();
      this.loadSummary();
      this.loadBreakdown();
    },
    breakdownTab() {
      this.drilldownItem = null;
    },
  },
  async mounted() {
    this.loadColumnsConfig();
    this.loadPrintSettings();
    this.initPrintColumns();
    await Promise.all([
      this.workforceStore.fetch(),
      this.corporalStore.fetch(),
      this.sectionStore.fetch(),
    ]);
    // Load initial summary and breakdown for all workforces
    this.loadActivities();
    this.loadSummary();
    this.loadBreakdown();
    this.fetchCompanySettings();
    this.fortnightSettingStore.fetch();

    // Listen for model changes from AI Assistant or WebSocket (other users)
    this._onModelChanged = (data) => this.handleModelChanged(data);
    mitt.on('model-changed', this._onModelChanged);
  },
  beforeUnmount() {
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
    if (this._onModelChanged) {
      mitt.off('model-changed', this._onModelChanged);
    }
  },
  methods: {
    triggerSummarySnackbar({ title, workforce = '', workforceLabel = '', workforceIcon = '', section = '', corporal = '', date = '', days = '', amount = '', icon = 'mdi-check-circle', iconColor = 'success' }) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = {
        show: true,
        title,
        workforce,
        workforceLabel,
        workforceIcon,
        section,
        corporal,
        date,
        days: days ? String(days) : '',
        amount,
        icon,
        iconColor,
      };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 1.25;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, 100);
    },
    formatDateStr(d) {
      return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },
    /**
     * Calculate declared salary for a workforce based on fortnight settings.
     * Resolves the matching setting for the currently viewed month.
     * salary_statement ENABLED  → split: declared = min(total, max), rest = non-declared
     * salary_statement DISABLED → all salary is non-declared (declared = 0)
     */
    getDeclared(w) {
      if (!w) return 0;
      if (!w.salary_statement) return 0;
      // Resolve the setting for the current month (use 15th as mid-month reference)
      const dateStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-15`;
      const setting = this.fortnightSettingStore.getSettingForDate(dateStr);
      if (!setting) return 0;
      const q1Max = parseFloat(setting.q1_max_salary) || 0;
      const q2Max = parseFloat(setting.q2_max_salary) || 0;
      const period = this.breakdownPeriod;
      if (period === 'q1') {
        return q1Max > 0 ? Math.min(w.q1_total || 0, q1Max) : (w.q1_total || 0);
      } else if (period === 'q2') {
        return q2Max > 0 ? Math.min(w.q2_total || 0, q2Max) : (w.q2_total || 0);
      } else {
        const monthTotal = (w.q1_total || 0) + (w.q2_total || 0);
        const monthMax = q1Max + q2Max;
        return monthMax > 0 ? Math.min(monthTotal, monthMax) : monthTotal;
      }
    },
    /**
     * Calculate non-declared salary = total - declared.
     * salary_statement DISABLED → everything is non-declared.
     */
    getNonDeclared(w) {
      if (!w) return 0;
      const period = this.breakdownPeriod;
      let total = 0;
      if (period === 'q1') total = w.q1_total || 0;
      else if (period === 'q2') total = w.q2_total || 0;
      else total = w.month_total || 0;
      return total - this.getDeclared(w);
    },
    /**
     * Handle real-time model change events from AI Assistant or WebSocket.
     * Re-fetches calendar activities, summary, and breakdown when relevant models change.
     */
    handleModelChanged(data) {
      const model = data?.model || '';
      const relevantModels = ['FortnightActivity', 'Corporal', 'Section', 'Workforce'];
      if (!relevantModels.includes(model)) return;

      if (model === 'FortnightActivity') {
        // Re-fetch activities, summary, and breakdown
        this.loadActivities();
        this.loadSummary();
        this.loadBreakdown();
        this.triggerSummarySnackbar({
          title: this.$t('realtime_sync') || 'Real-time Update',
          workforce: data?.workforce_name || '',
          date: data?.date || '',
          amount: data?.price ? `${this.formatNumber(data.price)} ${this.main.currency || 'DH'}` : '',
          icon: 'mdi-sync',
          iconColor: '#42A5F5',
        });
      } else {
        // Corporal, Section, or Workforce changed - re-fetch the reference store + breakdown
        if (model === 'Workforce') this.workforceStore.fetch();
        if (model === 'Section') this.sectionStore.fetch();
        if (model === 'Corporal') this.corporalStore.fetch();
        this.loadBreakdown();
      }
    },
    formatNumber(n) {
      if (this.main.formatDisplayNumber) return this.main.formatDisplayNumber(n);
      return Number(n || 0).toFixed(2);
    },
    async togglePaymentStatus(workforce, periodStr, isPaid) {
      // periodStr is 'q1' or 'q2'. API expects 'Q1' or 'Q2'
      const periodParam = periodStr.toUpperCase();
      const paidBool = Boolean(isPaid);
      try {
        await axios.post('/api/workforce-payments/toggle', {
          workforce_id: workforce.id,
          year: this.currentYear,
          month: this.currentMonth + 1,
          fortnight_period: periodParam,
          is_paid: paidBool,
        });
        
        // Update local state
        workforce[`is_paid_${periodStr}`] = paidBool;
        
        const statusText = paidBool ? (this.$t('paid') || 'Paid') : (this.$t('unpaid') || 'Unpaid');
        const periodTotal = workforce[`${periodStr}_total`] != null 
          ? ` (${this.formatNumber(workforce[`${periodStr}_total`])} ${this.main.currency || 'DH'})` 
          : '';

        this.triggerSummarySnackbar({
          title: this.$t('payment_status_updated') || 'Payment status updated',
          workforce: workforce.name || workforce.full_name || '',
          workforceIcon: 'mdi-account-hard-hat',
          amount: `${periodParam}: ${statusText}${periodTotal}`,
          icon: paidBool ? 'mdi-check-decagram' : 'mdi-clock-outline',
          iconColor: paidBool ? 'success' : 'warning',
        });
      } catch (error) {
        console.error(error);
        this.notification.push({
          title: this.$t('error'),
          message: error.response?.data?.message || this.$t('error_occurred'),
          icon: 'mdi-alert-circle',
          color: 'error'
        });
      }
    },
    async toggleAllPayments(periodStr, isPaid) {
      const periodParam = periodStr.toUpperCase();
      const workforces = this.sortedFilteredWorkforces;
      const workforceIds = workforces.map(w => w.id);
      if (!workforceIds.length) return;
      const paidBool = Boolean(isPaid);

      try {
        await axios.post('/api/workforce-payments/bulk-toggle', {
          workforce_ids: workforceIds,
          year: this.currentYear,
          month: this.currentMonth + 1,
          fortnight_period: periodParam,
          is_paid: paidBool,
        });
        
        // Update local state for all currently visible workforces
        workforces.forEach(w => {
          w[`is_paid_${periodStr}`] = paidBool;
        });

        const totalPrice = workforces.reduce((sum, w) => sum + Number(w[`${periodStr}_total`] || 0), 0);
        const statusText = paidBool ? (this.$t('paid') || 'Paid') : (this.$t('unpaid') || 'Unpaid');

        this.triggerSummarySnackbar({
          title: this.$t('payment_status_updated') || 'Payment status updated',
          workforce: `${workforces.length} ${this.$t('workforces') || 'Workforces'}`,
          workforceLabel: this.$t('workforces') || 'Workforces',
          workforceIcon: 'mdi-account-group',
          amount: `${periodParam}: ${statusText} • ${this.formatNumber(totalPrice)} ${this.main.currency || 'DH'}`,
          icon: paidBool ? 'mdi-check-all' : 'mdi-clock-outline',
          iconColor: paidBool ? 'success' : 'warning',
        });
      } catch (error) {
        console.error(error);
        this.notification.push({
          title: this.$t('error'),
          message: error.response?.data?.message || this.$t('error_occurred'),
          icon: 'mdi-alert-circle',
          color: 'error'
        });
      }
    },
    isAllPaid(colKey) {
      const workforces = this.sortedFilteredWorkforces;
      if (!workforces || !workforces.length) return false;
      return workforces.every(w => Boolean(w[colKey]));
    },
    // ---- Column Persistence & Print Settings ----
    getDefaultColumns() {
      return [
        { key: 'name', label: 'full_name', type: 'chip', visible: true, locked: true },
        { key: 'matricule', label: 'matricule', type: 'text', visible: true },
        { key: 'cin', label: 'cin', type: 'text', visible: true },
        { key: 'cnss_id', label: 'cnss_id', type: 'text', visible: true },
        { key: 'nationality', label: 'nationality', type: 'text', visible: true },
        { key: 'sex', label: 'sex', type: 'text', visible: true },
        { key: 'is_migrant', label: 'is_migrant', type: 'boolean', visible: true, color: 'warning' },
        { key: 'salary_statement', label: 'salary_statement', type: 'boolean', visible: true, color: 'success' },
        { key: 'fixed_daily_pay', label: 'fixed_daily_pay', type: 'boolean', visible: true, color: 'info' },
        { key: 'q1_work_days', label: 'work_days_q1', type: 'number', visible: true, period: 'q1' },
        { key: 'q1_non_work_days', label: 'non_work_days_q1', type: 'number', visible: true, period: 'q1' },
        { key: 'q1_total', label: 'q1_total', type: 'currency', visible: true, period: 'q1' },
        { key: 'q2_work_days', label: 'work_days_q2', type: 'number', visible: true, period: 'q2' },
        { key: 'q2_non_work_days', label: 'non_work_days_q2', type: 'number', visible: true, period: 'q2' },
        { key: 'q2_total', label: 'q2_total', type: 'currency', visible: true, period: 'q2' },
        { key: 'month_total', label: 'month_total', type: 'currency', visible: true, period: 'month', bold: true },
        { key: 'is_paid_q1', label: 'q1_paid', type: 'checkbox_q1', visible: true, period: 'q1' },
        { key: 'is_paid_q2', label: 'q2_paid', type: 'checkbox_q2', visible: true, period: 'q2' },
        { key: 'declared', label: 'declared', type: 'computed_declared', visible: true, headerColor: '#2E7D32', cellColor: '#2E7D32' },
        { key: 'non_declared', label: 'non_declared', type: 'computed_non_declared', visible: true, headerColor: '#E65100', cellColor: '#E65100' },
      ];
    },
    saveColumnsConfig() {
      try {
        const config = this.allWorkforceColumns.map(c => ({
          key: c.key,
          visible: !!c.visible,
        }));
        localStorage.setItem('fnm_workforce_columns_config', JSON.stringify(config));
      } catch (e) {
        console.error('Failed to save columns configuration to localStorage:', e);
      }
    },
    loadColumnsConfig() {
      try {
        const savedStr = localStorage.getItem('fnm_workforce_columns_config');
        if (!savedStr) return;
        const saved = JSON.parse(savedStr);
        if (!Array.isArray(saved) || !saved.length) return;

        const savedMap = new Map(saved.map((item, idx) => [item.key, { visible: item.visible, order: idx }]));
        const defaultCols = this.getDefaultColumns();
        const orderedCols = [];
        const unmappedCols = [];

        for (const col of defaultCols) {
          if (savedMap.has(col.key)) {
            const conf = savedMap.get(col.key);
            orderedCols.push({
              ...col,
              visible: col.locked ? true : (conf.visible !== false),
              _order: conf.order,
            });
          } else {
            unmappedCols.push(col);
          }
        }

        orderedCols.sort((a, b) => a._order - b._order);
        orderedCols.forEach(c => delete c._order);

        this.allWorkforceColumns = [...orderedCols, ...unmappedCols];
      } catch (e) {
        console.error('Failed to load columns configuration from localStorage:', e);
      }
    },
    resetColumnsToDefault() {
      try {
        localStorage.removeItem('fnm_workforce_columns_config');
        this.allWorkforceColumns = this.getDefaultColumns();
        this.notification.push({
          title: this.$t('success'),
          message: this.$t('columns_reset_success'),
          icon: 'mdi-restore',
          color: 'info',
        });
      } catch (e) {
        console.error('Failed to reset columns configuration:', e);
      }
    },
    onColumnVisibilityChange() {
      this.saveColumnsConfig();
    },
    savePrintSettings() {
      try {
        const toSave = {
          orientation: this.printSettings.orientation,
          density: this.printSettings.density,
          showLogo: this.printSettings.showLogo,
          showCompanyInfo: this.printSettings.showCompanyInfo,
          showMetadataBanner: this.printSettings.showMetadataBanner,
          showGrandTotals: this.printSettings.showGrandTotals,
          showSignatures: this.printSettings.showSignatures,
          zebraStriping: this.printSettings.zebraStriping,
          showNotes: this.printSettings.showNotes,
          notes: this.printSettings.notes,
          selectedColKeys: this.printSettings.selectedColKeys,
          columnOrder: this.printSettings.columnOrder,
          zoom: this.printSettings.zoom,
        };
        localStorage.setItem('fnm_print_settings', JSON.stringify(toSave));
      } catch (e) {
        console.error('Failed to save print settings:', e);
      }
    },
    loadPrintSettings() {
      try {
        const savedStr = localStorage.getItem('fnm_print_settings');
        if (!savedStr) return;
        const saved = JSON.parse(savedStr);
        if (saved && typeof saved === 'object') {
          Object.assign(this.printSettings, saved);
        }
      } catch (e) {
        console.error('Failed to load print settings:', e);
      }
    },
    initPrintColumns() {
      const baseCols = [...this.visibleWorkforceColumns];
      const savedOrder = this.printSettings.columnOrder;
      if (Array.isArray(savedOrder) && savedOrder.length > 0) {
        const colMap = new Map(baseCols.map(c => [c.key, c]));
        const ordered = [];
        savedOrder.forEach(key => {
          if (colMap.has(key)) {
            ordered.push(colMap.get(key));
            colMap.delete(key);
          }
        });
        colMap.forEach(col => ordered.push(col));
        this.printColumns = ordered;
      } else {
        this.printColumns = baseCols;
      }

      if (!this.printSettings.selectedColKeys || !this.printSettings.selectedColKeys.length) {
        this.printSettings.selectedColKeys = this.printColumns.map(c => c.key);
      }
    },
    openPrintDialog() {
      this.loadPrintSettings();
      this.initPrintColumns();
      this.printPreviewDialog = true;
    },
    syncPrintColumnsWithTable() {
      this.printColumns = [...this.visibleWorkforceColumns];
      this.printSettings.columnOrder = this.visibleWorkforceColumns.map(c => c.key);
      this.printSettings.selectedColKeys = this.visibleWorkforceColumns.map(c => c.key);
      this.savePrintSettings();
    },
    toggleAllPrintColumns(val) {
      if (val) {
        this.printSettings.selectedColKeys = this.printColumns.map(c => c.key);
      } else {
        this.printSettings.selectedColKeys = this.printColumns.filter(c => c.locked).map(c => c.key);
      }
      this.savePrintSettings();
    },
    onPrintColumnToggle(key, val) {
      const set = new Set(this.printSettings.selectedColKeys || []);
      if (val) set.add(key);
      else set.delete(key);
      this.printSettings.selectedColKeys = Array.from(set);
      this.savePrintSettings();
    },
    isPrintColSelected(key) {
      if (!this.printSettings.selectedColKeys || !this.printSettings.selectedColKeys.length) return true;
      return this.printSettings.selectedColKeys.includes(key);
    },
    onPrintColDragStart(idx, e) {
      this.printColDragIdx = idx;
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(idx));
      }
    },
    onPrintColDragOver(idx, e) {
      e.preventDefault();
      if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
      this.printColDragOverIdx = idx;
    },
    onPrintColDragLeave(idx, e) {
      if (this.printColDragOverIdx === idx) {
        this.printColDragOverIdx = null;
      }
    },
    onPrintColDrop(idx, e) {
      e.preventDefault();
      const from = this.printColDragIdx;
      this.printColDragOverIdx = null;
      if (from === null || from === idx) return;
      const list = [...this.printColumns];
      const item = list.splice(from, 1)[0];
      list.splice(idx, 0, item);
      this.printColumns = list;
      this.printSettings.columnOrder = list.map(c => c.key);
      this.savePrintSettings();
    },
    onPrintColDragEnd() {
      this.printColDragIdx = null;
      this.printColDragOverIdx = null;
    },
    getPrintColAlignClass(col) {
      if (!col) return 'text-center';
      if (col.key === 'name' || col.type === 'chip') return 'text-left';
      if (col.type === 'currency' || col.type === 'number' || col.type === 'computed_declared' || col.type === 'computed_non_declared') {
        return 'text-right';
      }
      return 'text-center';
    },
    getPrintCss(fontSize, cellPad) {
      const isLandscape = this.printSettings.orientation === 'landscape';
      const zebraCss = this.printSettings.zebraStriping ? '.fnm-pdoc-row-alt .fnm-pdoc-td { background-color: #f8fafc !important; }' : '';

      return `
        @page {
          size: ${isLandscape ? 'A4 landscape' : 'A4 portrait'};
          margin: 6mm 8mm;
        }
        * {
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
          color-adjust: exact !important;
          box-sizing: border-box !important;
        }
        html, body {
          margin: 0 !important;
          padding: 0 !important;
          background: #ffffff !important;
          color: #1a1a1a !important;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
          font-size: ${fontSize} !important;
          line-height: 1.35 !important;
        }
        #fnm-print-area {
          width: 100% !important;
          max-width: 100% !important;
          margin: 0 !important;
          padding: 0 !important;
          box-shadow: none !important;
          border-radius: 0 !important;
          background: #ffffff !important;
          display: block !important;
        }
        .fnm-pdoc-header {
          display: flex !important;
          align-items: center !important;
          justify-content: space-between !important;
          border-bottom: 2px solid #1565C0 !important;
          padding-bottom: 10px !important;
          margin-bottom: 10px !important;
          gap: 12px !important;
          page-break-inside: avoid !important;
        }
        .fnm-pdoc-company-col {
          display: flex !important;
          align-items: center !important;
          gap: 10px !important;
          flex: 1 !important;
        }
        .fnm-pdoc-logo {
          height: 46px !important;
          max-width: 130px !important;
          object-fit: contain !important;
        }
        .fnm-pdoc-company-name {
          font-size: 13px !important;
          font-weight: 800 !important;
          color: #0D47A1 !important;
        }
        .fnm-pdoc-company-sub {
          font-size: 9.5px !important;
          color: #555555 !important;
        }
        .fnm-pdoc-company-reg {
          font-size: 8.5px !important;
          color: #777777 !important;
        }
        .fnm-pdoc-title-col {
          text-align: center !important;
          flex: 1.3 !important;
        }
        .fnm-pdoc-title-main {
          font-size: 15px !important;
          font-weight: 800 !important;
          color: #1565C0 !important;
          text-transform: uppercase !important;
          letter-spacing: 0.5px !important;
        }
        .fnm-pdoc-title-tab {
          font-size: 11px !important;
          font-weight: 600 !important;
          color: #455a64 !important;
        }
        .fnm-pdoc-period-badge {
          display: inline-block !important;
          background-color: #E3F2FD !important;
          color: #0D47A1 !important;
          padding: 2px 8px !important;
          border-radius: 10px !important;
          font-size: 10px !important;
          font-weight: 700 !important;
          border: 1px solid rgba(21,101,192,0.2) !important;
        }
        .fnm-pdoc-meta-col {
          flex: 1 !important;
          display: flex !important;
          justify-content: flex-end !important;
        }
        .fnm-pdoc-meta-card {
          background-color: #f8fafc !important;
          border: 1px solid #e2e8f0 !important;
          border-radius: 6px !important;
          padding: 5px 8px !important;
          font-size: 9.5px !important;
          min-width: 160px !important;
        }
        .fnm-pdoc-meta-line {
          display: flex !important;
          justify-content: space-between !important;
          gap: 6px !important;
          margin-bottom: 2px !important;
        }
        .fnm-pdoc-meta-label {
          color: #64748b !important;
          font-weight: 600 !important;
        }
        .fnm-pdoc-meta-value {
          color: #0f172a !important;
          font-weight: 700 !important;
        }
        .fnm-pdoc-meta-strip {
          display: flex !important;
          flex-wrap: wrap !important;
          gap: 6px 14px !important;
          background-color: #f1f5f9 !important;
          border: 1px solid #e2e8f0 !important;
          border-radius: 6px !important;
          padding: 5px 10px !important;
          font-size: 9.5px !important;
          margin-bottom: 10px !important;
          page-break-inside: avoid !important;
        }
        .fnm-pdoc-strip-cell {
          color: #334155 !important;
        }
        .fnm-pdoc-table-wrap {
          width: 100% !important;
          overflow: visible !important;
          margin-bottom: 12px !important;
        }
        .fnm-pdoc-table {
          width: 100% !important;
          border-collapse: collapse !important;
          font-variant-numeric: tabular-nums !important;
          font-size: ${fontSize} !important;
          table-layout: auto !important;
        }
        thead {
          display: table-header-group !important;
        }
        tr {
          page-break-inside: avoid !important;
          break-inside: avoid !important;
        }
        .fnm-pdoc-th {
          background-color: #E3F2FD !important;
          color: #0D47A1 !important;
          border: 1px solid #90CAF9 !important;
          font-weight: 700 !important;
          text-align: center !important;
          padding: ${cellPad} !important;
          white-space: normal !important;
          word-break: break-word !important;
          line-height: 1.2 !important;
          vertical-align: middle !important;
        }
        .fnm-head-amber .fnm-pdoc-th {
          background-color: #FFF3E0 !important;
          color: #E65100 !important;
          border-color: #FFCC80 !important;
        }
        .fnm-head-purple .fnm-pdoc-th {
          background-color: #F3E5F5 !important;
          color: #6A1B9A !important;
          border-color: #CE93D8 !important;
        }
        .fnm-pdoc-th-period {
          font-size: 8.5px !important;
          font-weight: 500 !important;
          opacity: 0.85 !important;
          display: block !important;
        }
        .fnm-pdoc-td {
          border: 1px solid #e0e0e0 !important;
          color: #212121 !important;
          padding: ${cellPad} !important;
          vertical-align: middle !important;
          line-height: 1.25 !important;
        }
        ${zebraCss}
        .fnm-pdoc-total-tr {
          background-color: #E3F2FD !important;
          font-weight: 800 !important;
          page-break-inside: avoid !important;
          break-inside: avoid !important;
        }
        .fnm-total-amber {
          background-color: #FFF3E0 !important;
        }
        .fnm-total-purple {
          background-color: #F3E5F5 !important;
        }
        .fnm-pdoc-total-td {
          border: 1px solid #90CAF9 !important;
          color: #0D47A1 !important;
          padding: ${cellPad} !important;
        }
        .fnm-total-amber .fnm-pdoc-total-td {
          border-color: #FFCC80 !important;
          color: #E65100 !important;
        }
        .fnm-total-purple .fnm-pdoc-total-td {
          border-color: #CE93D8 !important;
          color: #6A1B9A !important;
        }
        .fnm-badge-yes {
          display: inline-block !important;
          padding: 1px 5px !important;
          border-radius: 4px !important;
          background-color: #E8F5E9 !important;
          color: #2E7D32 !important;
          font-weight: 700 !important;
          font-size: 8.5px !important;
        }
        .fnm-badge-no {
          display: inline-block !important;
          padding: 1px 5px !important;
          border-radius: 4px !important;
          background-color: #FFEBEE !important;
          color: #C62828 !important;
          font-weight: 600 !important;
          font-size: 8.5px !important;
        }
        .fnm-badge-paid {
          display: inline-block !important;
          padding: 1px 5px !important;
          border-radius: 4px !important;
          background-color: #E8F5E9 !important;
          color: #1B5E20 !important;
          font-weight: 800 !important;
          font-size: 8.5px !important;
        }
        .fnm-badge-unpaid {
          display: inline-block !important;
          padding: 1px 5px !important;
          border-radius: 4px !important;
          background-color: #ECEFF1 !important;
          color: #546E7A !important;
          font-weight: 600 !important;
          font-size: 8.5px !important;
        }
        .fnm-pdoc-grand-totals {
          display: flex !important;
          justify-content: space-between !important;
          gap: 10px !important;
          margin: 12px 0 !important;
          page-break-inside: avoid !important;
          break-inside: avoid !important;
        }
        .fnm-pdoc-gt-card {
          flex: 1 !important;
          background-color: #f8fafc !important;
          border: 1.5px solid #1E88E5 !important;
          border-radius: 6px !important;
          padding: 6px 10px !important;
          text-align: center !important;
        }
        .fnm-pdoc-gt-card-month {
          border-color: #7B1FA2 !important;
          background-color: #FAF5FF !important;
        }
        .fnm-pdoc-gt-label {
          font-size: 9px !important;
          font-weight: 700 !important;
          color: #64748b !important;
          text-transform: uppercase !important;
          margin-bottom: 2px !important;
        }
        .fnm-pdoc-gt-value {
          font-size: 13px !important;
          font-weight: 800 !important;
        }
        .fnm-pdoc-gt-cur {
          font-size: 9px !important;
          font-weight: 600 !important;
        }
        .fnm-pdoc-notes-block {
          border: 1px dashed #90CAF9 !important;
          background-color: #F8FBFE !important;
          border-radius: 6px !important;
          padding: 6px 10px !important;
          margin-bottom: 12px !important;
          font-size: 9.5px !important;
          page-break-inside: avoid !important;
          break-inside: avoid !important;
        }
        .fnm-pdoc-notes-title {
          font-weight: 700 !important;
          color: #1565C0 !important;
          margin-bottom: 2px !important;
        }
        .fnm-pdoc-notes-body {
          color: #374151 !important;
          white-space: pre-wrap !important;
        }
        .fnm-pdoc-signatures {
          display: flex !important;
          justify-content: space-between !important;
          gap: 12px !important;
          margin: 14px 0 8px !important;
          page-break-inside: avoid !important;
          break-inside: avoid !important;
        }
        .fnm-pdoc-sig-box {
          flex: 1 !important;
          border: 1px solid #cbd5e1 !important;
          border-radius: 6px !important;
          padding: 6px 8px !important;
          background-color: #ffffff !important;
        }
        .fnm-pdoc-sig-header {
          font-size: 9.5px !important;
          font-weight: 700 !important;
          color: #334155 !important;
          text-align: center !important;
          border-bottom: 1px solid #e2e8f0 !important;
          padding-bottom: 3px !important;
        }
        .fnm-pdoc-sig-space {
          height: 38px !important;
        }
        .fnm-pdoc-sig-line {
          font-size: 8.5px !important;
          color: #64748b !important;
          border-top: 1px dashed #cbd5e1 !important;
          padding-top: 2px !important;
        }
        .fnm-pdoc-footer {
          border-top: 1px solid #cbd5e1 !important;
          padding-top: 6px !important;
          margin-top: 10px !important;
          display: flex !important;
          justify-content: space-between !important;
          align-items: center !important;
          font-size: 8.5px !important;
          color: #64748b !important;
          page-break-inside: avoid !important;
          break-inside: avoid !important;
        }
        .text-left { text-align: left !important; }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-primary { color: #1565C0 !important; }
        .text-success { color: #2E7D32 !important; }
        .text-warning { color: #E65100 !important; }
        .text-purple { color: #7B1FA2 !important; }
        .font-weight-bold { font-weight: 700 !important; }
      `;
    },
    triggerPrint() {
      const printEl = document.getElementById('fnm-print-area');
      if (!printEl) return;

      const iframe = document.createElement('iframe');
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '0';
      iframe.style.height = '0';
      iframe.style.border = '0';
      iframe.setAttribute('aria-hidden', 'true');
      document.body.appendChild(iframe);

      const doc = iframe.contentWindow.document;
      const orientation = this.printSettings.orientation || 'landscape';
      const density = this.printSettings.density || 'compact';
      const cellPad = density === 'compact' ? '4px 6px' : density === 'spacious' ? '8px 10px' : '6px 8px';
      const fontSize = density === 'compact' ? '10px' : density === 'spacious' ? '12px' : '11px';
      const isLandscape = orientation === 'landscape';

      const title = `${this.$t('activity_breakdown')} - ${this.monthNames[this.currentMonth]} ${this.currentYear}`;

      const printHtml = `
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="utf-8" />
            <title>${title}</title>
            <style>
              ${this.getPrintCss(fontSize, cellPad)}
            </style>
          </head>
          <body>
            <div id="fnm-print-area" class="fnm-print-sheet fnm-print-${orientation} fnm-density-${density} ${this.printSettings.zebraStriping ? 'fnm-zebra-table' : ''}">
              ${printEl.innerHTML}
            </div>
          </body>
        </html>
      `;

      doc.open();
      doc.write(printHtml);
      doc.close();

      const images = doc.querySelectorAll('img');
      let loaded = 0;
      const doPrint = () => {
        setTimeout(() => {
          try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
          } catch (err) {
            console.error('Print trigger error:', err);
          } finally {
            setTimeout(() => {
              if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
            }, 1000);
          }
        }, 300);
      };

      if (images.length === 0) {
        doPrint();
      } else {
        images.forEach(img => {
          if (img.complete) {
            loaded++;
            if (loaded === images.length) doPrint();
          } else {
            img.onload = img.onerror = () => {
              loaded++;
              if (loaded === images.length) doPrint();
            };
          }
        });
      }
    },
    // ---- Column Management ----
    colDragStart(idx) { this.colDragIdx = idx; },
    colDragOver(idx, e) {
      e.preventDefault();
      if (this.colDragIdx === null || this.colDragIdx === idx) return;
      const from = this.colDragIdx;
      const moved = this.allWorkforceColumns.splice(from, 1)[0];
      this.allWorkforceColumns.splice(idx, 0, moved);
      this.colDragIdx = idx;
    },
    colDragEnd() { this.colDragIdx = null; this.saveColumnsConfig(); },
    toggleAllColumns(val) {
      this.allWorkforceColumns.forEach(c => { if (!c.locked) c.visible = val; });
      this.saveColumnsConfig();
    },
    getCellValue(w, col) {
      if (col.type === 'chip') return w[col.key] || '-';
      if (col.type === 'text') return w[col.key] || '-';
      if (col.type === 'number') return w[col.key] || 0;
      if (col.type === 'currency') return this.formatNumber(w[col.key]);
      if (col.type === 'boolean') return w[col.key];
      if (col.type === 'computed_declared') return this.formatNumber(this.getDeclared(w));
      if (col.type === 'computed_non_declared') return this.formatNumber(this.getNonDeclared(w));
      return w[col.key] || '-';
    },
    /** Get raw (unformatted) cell value for sorting and filtering */
    getRawCellValue(w, col) {
      if (col.type === 'computed_declared') return this.getDeclared(w);
      if (col.type === 'computed_non_declared') return this.getNonDeclared(w);
      if (col.type === 'currency' || col.type === 'number') return parseFloat(w[col.key]) || 0;
      if (col.type === 'boolean') return !!w[col.key];
      return w[col.key] || '';
    },
    /** Toggle sort on a column: none → asc → desc → remove */
    toggleWfSort(colKey) {
      const idx = this.wfSortRules.findIndex(r => r.key === colKey);
      if (idx === -1) {
        this.wfSortRules.push({ key: colKey, dir: 'asc' });
      } else if (this.wfSortRules[idx].dir === 'asc') {
        this.wfSortRules[idx].dir = 'desc';
      } else {
        this.wfSortRules.splice(idx, 1);
      }
    },
    /** Get current sort direction for a column */
    getWfSortDir(colKey) {
      const rule = this.wfSortRules.find(r => r.key === colKey);
      return rule ? rule.dir : null;
    },
    /** Get sort priority index (1-based) for multi-sort badge */
    getWfSortIndex(colKey) {
      const idx = this.wfSortRules.findIndex(r => r.key === colKey);
      return idx >= 0 ? idx + 1 : 0;
    },
    /** Check if a column has an active filter */
    isWfColFiltered(colKey) {
      const f = this.wfColumnFilters[colKey];
      if (!f) return false;
      return (f.checked && f.checked.length > 0) ||
             (f.bool !== undefined && f.bool !== null && f.bool !== '') ||
             (f.min !== '' && f.min !== undefined && f.min !== null) ||
             (f.max !== '' && f.max !== undefined && f.max !== null);
    },
    /** Clear all column filters and sorts */
    clearWfFilters() {
      this.wfColumnFilters = {};
      this.wfSortRules = [];
    },
    /** Get column filter type for the filter menu UI */
    getWfColFilterType(col) {
      if (col.type === 'chip' || col.type === 'text') return 'text';
      if (col.type === 'boolean') return 'boolean';
      return 'range';
    },
    /** Ensure filter object exists for a column key */
    ensureWfFilter(colKey) {
      if (!this.wfColumnFilters[colKey]) {
        this.wfColumnFilters[colKey] = { checked: [], search: '', bool: '', min: '', max: '' };
      }
    },
    /** Clear a single column filter */
    clearWfColFilter(colKey) {
      this.wfColumnFilters[colKey] = { checked: [], search: '', bool: '', min: '', max: '' };
    },
    /** Get all unique values for a text/chip column from current data */
    getUniqueColValues(col) {
      const wfs = this.filteredWorkforces;
      const vals = new Set();
      for (const w of wfs) {
        const v = String(w[col.key] || '');
        vals.add(v);
      }
      return [...vals].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
    },
    /** Get unique values filtered by the search box */
    getFilteredUniqueValues(col) {
      const all = this.getUniqueColValues(col);
      const f = this.wfColumnFilters[col.key];
      if (!f || !f.search || !f.search.trim()) return all;
      const q = f.search.trim().toLowerCase();
      return all.filter(v => v.toLowerCase().includes(q));
    },
    /** Check if a value is in the checked list for a column */
    isWfValueChecked(colKey, val) {
      const f = this.wfColumnFilters[colKey];
      if (!f || !f.checked || f.checked.length === 0) return true; // all checked by default when no filter
      return f.checked.includes(val);
    },
    /** Toggle a value in the checked list */
    toggleWfFilterValue(colKey, val, checked) {
      this.ensureWfFilter(colKey);
      const f = this.wfColumnFilters[colKey];
      // If checked array is empty (means "all selected"), populate it with all values first
      if (f.checked.length === 0) {
        const col = this.allWorkforceColumns.find(c => c.key === colKey);
        f.checked = [...this.getUniqueColValues(col)];
      }
      if (checked) {
        if (!f.checked.includes(val)) f.checked.push(val);
      } else {
        f.checked = f.checked.filter(v => v !== val);
      }
      // If all values are checked again, clear the filter
      const col = this.allWorkforceColumns.find(c => c.key === colKey);
      const allVals = this.getUniqueColValues(col);
      if (f.checked.length >= allVals.length) {
        f.checked = [];
      }
    },
    /** Select all values for a column filter */
    selectAllWfFilterValues(col) {
      this.ensureWfFilter(col.key);
      this.wfColumnFilters[col.key].checked = [];
    },
    /** Deselect all values for a column filter */
    deselectAllWfFilterValues(col) {
      this.ensureWfFilter(col.key);
      this.wfColumnFilters[col.key].checked = ['__none__']; // impossible value = show nothing
    },
    getColExcelValue(w, col) {
      if (col.type === 'boolean') return w[col.key] ? this.$t('yes') : this.$t('no');
      if (col.type === 'computed_declared') return this.getDeclared(w);
      if (col.type === 'computed_non_declared') return this.getNonDeclared(w);
      if (col.type === 'currency') return w[col.key] || 0;
      if (col.type === 'number') return w[col.key] || 0;
      return w[col.key] || '';
    },
    async fetchCompanySettings() {
      try { const r = await axios.get('/api/company-settings'); this.companySettings = r.data.settings || {}; } catch (e) { console.error(e); }
    },
    workforceItemTitle(item) {
      return `${item.full_name} (${item.matricule})`;
    },
    prevMonth() {
      if (this.currentMonth === 0) { this.currentMonth = 11; this.currentYear--; }
      else { this.currentMonth--; }
    },
    nextMonth() {
      if (this.currentMonth === 11) { this.currentMonth = 0; this.currentYear++; }
      else { this.currentMonth++; }
    },
    goToday() {
      const now = new Date();
      this.currentYear = now.getFullYear();
      this.currentMonth = now.getMonth();
      this.currentHalf = now.getDate() <= 15 ? 1 : 2;
      this.selectedDate = this.formatDateStr(now);
    },
    /** Handle cell click - either multi-select or single select */
    handleCellClick(cell) {
      if (this.multiSelectMode) {
        this.toggleDateSelection(cell);
      } else {
        this.selectDate(cell);
      }
    },
    selectDate(cell) {
      if (cell.dateStr) this.selectedDate = cell.dateStr;
      if (cell.day) this.currentHalf = cell.day <= 15 ? 1 : 2;
    },
    isSelectedDate(cell) {
      return cell.dateStr === this.selectedDate;
    },
    /** Toggle multi-select mode on/off */
    toggleMultiSelect() {
      this.multiSelectMode = !this.multiSelectMode;
      if (!this.multiSelectMode) {
        this.selectedDates = [];
      }
    },
    /** Toggle a date in the multi-selection array */
    toggleDateSelection(cell) {
      if (!cell.dateStr || cell.isOutside || cell.isFuture) return;
      // Only allow selecting cells that have activities
      if (!cell.activities || !cell.activities.length) return;
      const idx = this.selectedDates.indexOf(cell.dateStr);
      if (idx >= 0) {
        this.selectedDates.splice(idx, 1);
      } else {
        this.selectedDates.push(cell.dateStr);
      }
    },
    /** Clear multi-selection */
    clearMultiSelect() {
      this.selectedDates = [];
    },
    /** Bulk delete activities for all selected dates */
    async bulkDeleteSelectedDays() {
      if (!this.selectedDates.length) return;
      this.bulkDeleting = true;
      try {
        const wfIds = this.noWorkforceSelected ? null : [...this.selectedWorkforceIds];
        const dates = [...this.selectedDates].sort();
        const deletedCount = await this.activityStore.bulkDeleteByDates(
          wfIds,
          dates
        );

        // Rich notification
        const fields = [];
        fields.push({ label: this.$t('dates_count'), value: dates.length, icon: 'mdi-calendar-multiple' });
        fields.push({ label: this.$t('date_range_label'), value: `${dates[0]} â†’ ${dates[dates.length - 1]}`, icon: 'mdi-calendar-range' });
        if (wfIds && wfIds.length === 1) {
          const w = this.workforceStore.list.find(w => w.id === wfIds[0]);
          fields.push({ label: this.$t('workforce'), value: w ? w.full_name : '', icon: 'mdi-account' });
        } else if (wfIds && wfIds.length > 1) {
          fields.push({ label: this.$t('workforces_count'), value: wfIds.length, icon: 'mdi-account-group' });
        } else {
          fields.push({ label: this.$t('scope'), value: this.$t('all_workforces'), icon: 'mdi-account-group' });
        }
        fields.push({ label: this.$t('activities_removed'), value: deletedCount, icon: 'mdi-delete-sweep' });

        this.notification.notifyDeletion({
          type: 'fortnight_activity',
          icon: 'mdi-calendar-remove',
          color: 'error',
          fields,
        });

        this.triggerSummarySnackbar({
          title: this.$t('workforce_activities_deleted') || 'Activities deleted successfully',
          days: `${dates.length} ${this.$t('days_selected')}`,
          icon: 'mdi-delete-sweep',
          iconColor: 'error',
        });

        // Reset selection and reload
        this.selectedDates = [];
        this.multiSelectMode = false;
        await this.loadActivities();
        await this.loadSummary();
        await this.loadBreakdown();
      } catch (e) {
        console.error(e);
        this.notification.push({ title: this.$t('error'), message: this.$t('workforce_delete_error'), icon: 'mdi-alert-circle', color: 'error' });
      }
      this.bulkDeleting = false;
    },
    getActivityClass(act) {
      if (act.is_fixed_price) return 'fnm-act-fixed';
      return act.section_id ? 'fnm-act-section' : 'fnm-act-corporal';
    },
    getActivityLabel(act) {
      // Fixed price activities always show workforce name
      if (act.is_fixed_price && act.workforce) return act.workforce.full_name;
      // When workforces are selected, show workforce name instead of section/corporal
      if (!this.noWorkforceSelected && act.workforce) return act.workforce.full_name;
      if (act.section) return act.section.name;
      if (act.corporal) return act.corporal.full_name;
      return '';
    },
    /** Open day detail dialog for a summary cell */
    async openDayDetail(cell) {
      if (!cell || !cell.dateStr) return;
      this.dayDetailDate = cell.dateStr;
      this.dayDetailActivities = [];
      this.dayDetailDialog = true;
      this.dayDetailLoading = true;
      try {
        this.dayDetailActivities = await this.activityStore.fetchActivitiesByDate(cell.dateStr);
      } catch (e) {
        console.error(e);
      }
      this.dayDetailLoading = false;
    },
    /** Edit an activity from the detail dialog - opens the standard edit dialog */
    editActivityFromDetail(act) {
      this.editForm = {
        id: act.id,
        section_id: act.section_id,
        corporal_id: act.corporal_id,
        price: parseFloat(act.price) || 0,
      };
      this.editDialog = true;
    },
    /** Delete an activity from the detail dialog */
    async deleteActivityFromDetail(act) {
      this.deletingDetailId = act.id;
      try {
        await this.activityStore.remove(act.id);
        // Remove from local detail list
        this.dayDetailActivities = this.dayDetailActivities.filter(a => a.id !== act.id);

        // Rich notification
        const fields = [];
        if (act.workforce) fields.push({ label: this.$t('workforce'), value: act.workforce.full_name, icon: 'mdi-account' });
        if (act.section) fields.push({ label: this.$t('section_affiliated'), value: act.section.name, icon: 'mdi-sitemap-outline' });
        if (act.corporal) fields.push({ label: this.$t('corporal'), value: act.corporal.full_name, icon: 'mdi-account-tie' });
        fields.push({ label: this.$t('price'), value: `${this.formatNumber(act.price)} ${this.main.currency || 'DH'}`, icon: 'mdi-cash' });

        this.notification.notifyDeletion({
          type: 'fortnight_activity',
          icon: 'mdi-calendar-remove',
          color: 'error',
          fields,
        });

        // Reload totals
        await this.loadActivities();
        await this.loadSummary();
        await this.loadBreakdown();

        // Close dialog if no activities left
        if (!this.dayDetailActivities.length) {
          this.dayDetailDialog = false;
        }
      } catch (e) {
        console.error(e);
      }
      this.deletingDetailId = null;
    },
    /** Get short initials from a workforce for compact display */
    getWorkforceInitials(wf) {
      if (!wf) return '?';
      const name = wf.full_name || wf.name || '';
      const parts = name.trim().split(/\s+/).filter(Boolean);
      if (parts.length >= 2 && parts[0] && parts[parts.length - 1]) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
      }
      return name.substring(0, 2).toUpperCase() || '?';
    },
    onSectionChange(val) {
      if (val) {
        this.activityForm.corporal_id = null;
        const section = this.sectionStore.list.find(s => s.id === val);
        if (section) this.activityForm.price = parseFloat(section.daily_price) || 0;
      }
    },
    onCorporalChange(val) {
      if (val) {
        this.activityForm.section_id = null;
        const corporal = this.corporalStore.list.find(c => c.id === val);
        this.activityForm.price = corporal ? (parseFloat(corporal.daily_price) || 0) : 0;
      }
    },
    onEditSectionChange(val) {
      if (val) {
        this.editForm.corporal_id = null;
        const section = this.sectionStore.list.find(s => s.id === val);
        if (section) this.editForm.price = parseFloat(section.daily_price) || 0;
      }
    },
    onEditCorporalChange(val) {
      if (val) {
        this.editForm.section_id = null;
        const corporal = this.corporalStore.list.find(c => c.id === val);
        this.editForm.price = corporal ? (parseFloat(corporal.daily_price) || 0) : 0;
      }
    },
    onWorkforceChange() {
      // loadActivities triggered by watcher on selectedWorkforceIds
    },
    async loadActivities() {
      if (this.noWorkforceSelected) {
        // Fetch aggregated daily totals for all workforces
        this.activityStore.activities = [];
        await this.activityStore.fetchDailyTotals(this.currentYear, this.currentMonth + 1);
        return;
      }

      // Fetch activities for ALL selected workforces for the full month
      this.activityStore.dailyTotals = [];
      await this.activityStore.fetchActivities(this.selectedWorkforceIds, this.currentYear, this.currentMonth + 1);
    },
    /** Load summary - filters only by workforce(s) and month/year */
    async loadSummary() {
      const params = {
        year: this.currentYear,
        month: this.currentMonth + 1,
      };

      // Apply workforce filter if any selected
      if (this.selectedWorkforceIds && this.selectedWorkforceIds.length > 0) {
        params.workforce_ids = this.selectedWorkforceIds;
      }

      await this.activityStore.fetchSummary(params);
    },
    /** Load breakdown - same filter as summary */
    async loadBreakdown() {
      const params = {
        year: this.currentYear,
        month: this.currentMonth + 1,
      };
      if (this.selectedWorkforceIds && this.selectedWorkforceIds.length > 0) {
        params.workforce_ids = this.selectedWorkforceIds;
      }
      await this.activityStore.fetchBreakdown(params);
    },
    /** Export activities to Excel - exports exactly what is displayed on screen */
    async exportToExcel() {
      this.exporting = true;
      try {
        const XLSX = (await import('xlsx')).default || (await import('xlsx'));
        const monthName = this.monthNames[this.currentMonth] || (this.currentMonth + 1);
        const currency = this.main.currency || 'DH';
        const bd = this.breakdownData;
        const wb = XLSX.utils.book_new();
        const tab = this.breakdownTab;
        const drill = this.drilldownItem;
        let sheetName = '';
        let fileName = '';

        if (tab === 'workforces' && !drill) {
          // -- Workforces summary table (uses visible columns) --
          sheetName = this.$t('workforces_activities');
          const cols = this.visibleWorkforceColumns;
          const headers = cols.map(c => {
            const label = this.$t(c.label);
            if (c.type === 'currency') return `${label} (${currency})`;
            return label;
          });
          const rows = (this.sortedFilteredWorkforces || bd.workforces || []).map(w =>
            cols.map(c => this.getColExcelValue(w, c))
          );
          rows.push([]);
          const totRow = cols.map(c => {
            if (c.type === 'chip') return this.$t('total');
            if (c.type === 'text' || c.type === 'boolean') return '';
            if (c.key === 'q1_work_days') return this.workforceTotals.q1_work_days;
            if (c.key === 'q1_non_work_days') return this.workforceTotals.q1_non_work_days;
            if (c.key === 'q1_total') return this.workforceTotals.q1_total;
            if (c.key === 'q2_work_days') return this.workforceTotals.q2_work_days;
            if (c.key === 'q2_non_work_days') return this.workforceTotals.q2_non_work_days;
            if (c.key === 'q2_total') return this.workforceTotals.q2_total;
            if (c.key === 'month_total') return this.workforceTotals.month_total;
            if (c.type === 'computed_declared') return this.workforceTotals.declared;
            if (c.type === 'computed_non_declared') return this.workforceTotals.non_declared;
            return '';
          });
          rows.push(totRow);
          const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
          ws['!cols'] = cols.map(c => ({ wch: c.type === 'chip' ? 24 : c.type === 'text' ? 16 : 14 }));
          XLSX.utils.book_append_sheet(wb, ws, sheetName);
          fileName = `Fortnight_Workforces_${this.breakdownPeriod.toUpperCase()}_${monthName}_${this.currentYear}.xlsx`;

        } else if (tab === 'workforces' && drill) {
          // -- Drill-down: activities for a specific workforce --
          sheetName = `${drill.name} - ${this.$t('activities')}`;
          const headers = [
            this.$t('date'),
            `${this.$t('section')} / ${this.$t('corporal')}`,
            `${this.$t('price')} (${currency})`
          ];
          const data = (drill.activities || []).map(act => [
            this.main.formateDate(act.date),
            act.section_name || act.corporal_name || '-',
            act.price
          ]);
          const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
          ws['!cols'] = [{ wch: 14 }, { wch: 24 }, { wch: 14 }];
          XLSX.utils.book_append_sheet(wb, ws, sheetName.substring(0, 31));
          fileName = `Fortnight_${drill.name}_Activities_${monthName}_${this.currentYear}.xlsx`;

        } else if (tab === 'sections' && !drill) {
          // -- Sections summary table --
          sheetName = this.$t('sections_activities');
          const headers = [
            this.$t('name'),
            `Q1 ${this.$t('count')}`, `Q1 ${this.$t('total')} (${currency})`,
            `Q2 ${this.$t('count')}`, `Q2 ${this.$t('total')} (${currency})`,
            `${this.$t('month_total')} (${currency})`
          ];
          const data = (bd.sections || []).map(s => [
            s.name, s.q1_count, s.q1_total, s.q2_count, s.q2_total, s.month_total
          ]);
          data.push([]);
          data.push([this.$t('total'), this.sectionTotals.q1_count, this.sectionTotals.q1_total, this.sectionTotals.q2_count, this.sectionTotals.q2_total, this.sectionTotals.month_total]);
          const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
          ws['!cols'] = [{ wch: 22 }, { wch: 10 }, { wch: 16 }, { wch: 10 }, { wch: 16 }, { wch: 18 }];
          XLSX.utils.book_append_sheet(wb, ws, sheetName);
          fileName = `Fortnight_Sections_${this.breakdownPeriod.toUpperCase()}_${monthName}_${this.currentYear}.xlsx`;

        } else if (tab === 'sections' && drill) {
          // -- Drill-down: workforces in a specific section --
          sheetName = `${drill.name} - ${this.$t('workforces')}`;
          const headers = [
            this.$t('full_name'), this.$t('matricule'),
            this.$t('nationality'), this.$t('sex'), this.$t('is_migrant'),
            `Q1 ${this.$t('count')}`, `Q1 ${this.$t('total')} (${currency})`,
            `Q2 ${this.$t('count')}`, `Q2 ${this.$t('total')} (${currency})`,
            `${this.$t('month_total')} (${currency})`
          ];
          const data = (drill.workforces || []).map(wf => [
            wf.name, wf.matricule, wf.nationality,
            this.$t(wf.sex), wf.is_migrant ? this.$t('yes') : this.$t('no'),
            wf.q1_count, wf.q1_total, wf.q2_count, wf.q2_total, wf.month_total
          ]);
          const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
          ws['!cols'] = [{ wch: 22 }, { wch: 14 }, { wch: 14 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 16 }, { wch: 10 }, { wch: 16 }, { wch: 18 }];
          XLSX.utils.book_append_sheet(wb, ws, sheetName.substring(0, 31));
          fileName = `Fortnight_Section_${drill.name}_${this.breakdownPeriod.toUpperCase()}_${monthName}_${this.currentYear}.xlsx`;

        } else if (tab === 'corporals' && !drill) {
          // -- Corporals summary table --
          sheetName = this.$t('corporals_activities');
          const headers = [
            this.$t('name'),
            `Q1 ${this.$t('count')}`, `Q1 ${this.$t('total')} (${currency})`,
            `Q2 ${this.$t('count')}`, `Q2 ${this.$t('total')} (${currency})`,
            `${this.$t('month_total')} (${currency})`
          ];
          const data = (bd.corporals || []).map(c => [
            c.name, c.q1_count, c.q1_total, c.q2_count, c.q2_total, c.month_total
          ]);
          data.push([]);
          data.push([this.$t('total'), this.corporalTotals.q1_count, this.corporalTotals.q1_total, this.corporalTotals.q2_count, this.corporalTotals.q2_total, this.corporalTotals.month_total]);
          const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
          ws['!cols'] = [{ wch: 22 }, { wch: 10 }, { wch: 16 }, { wch: 10 }, { wch: 16 }, { wch: 18 }];
          XLSX.utils.book_append_sheet(wb, ws, sheetName);
          fileName = `Fortnight_Corporals_${this.breakdownPeriod.toUpperCase()}_${monthName}_${this.currentYear}.xlsx`;

        } else if (tab === 'corporals' && drill) {
          // -- Drill-down: workforces in a specific corporal --
          sheetName = `${drill.name} - ${this.$t('workforces')}`;
          const headers = [
            this.$t('full_name'), this.$t('matricule'),
            this.$t('nationality'), this.$t('sex'), this.$t('is_migrant'),
            `Q1 ${this.$t('count')}`, `Q1 ${this.$t('total')} (${currency})`,
            `Q2 ${this.$t('count')}`, `Q2 ${this.$t('total')} (${currency})`,
            `${this.$t('month_total')} (${currency})`
          ];
          const data = (drill.workforces || []).map(wf => [
            wf.name, wf.matricule, wf.nationality,
            this.$t(wf.sex), wf.is_migrant ? this.$t('yes') : this.$t('no'),
            wf.q1_count, wf.q1_total, wf.q2_count, wf.q2_total, wf.month_total
          ]);
          const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
          ws['!cols'] = [{ wch: 22 }, { wch: 14 }, { wch: 14 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 16 }, { wch: 10 }, { wch: 16 }, { wch: 18 }];
          XLSX.utils.book_append_sheet(wb, ws, sheetName.substring(0, 31));
          fileName = `Fortnight_Corporal_${drill.name}_${this.breakdownPeriod.toUpperCase()}_${monthName}_${this.currentYear}.xlsx`;
        }

        XLSX.writeFile(wb, fileName);
      } catch (e) {
        console.error('Export error:', e);
      }
      this.exporting = false;
    },
    async submitActivity() {
      if (!this.canSubmitActivity) return;
      this.submitting = true;
      const ids = [...this.selectedWorkforceIds];
      const assignmentLabel = this.activityForm.section_id
        ? (this.sectionStore.list.find(s => s.id === this.activityForm.section_id)?.name || '')
        : (this.corporalStore.list.find(c => c.id === this.activityForm.corporal_id)?.full_name || '');
      const assignmentType = this.activityForm.section_id ? this.$t('section_affiliated') : this.$t('corporal');
      const priceVal = this.activityForm.price;

      // Split workforces into regular and fixed daily pay
      const fixedIds = this.fixedDailyPayWorkforceIds;
      const regularIds = this.regularWorkforceIds;
      const fixedPrice = this.fixedDailyPrice;

      try {
        // 1) Create regular activities for ALL selected workforces
        for (const wId of ids) {
          await this.activityStore.create({
            workforce_id: wId,
            date: this.selectedDate,
            section_id: this.activityForm.section_id || null,
            corporal_id: this.activityForm.corporal_id || null,
            price: priceVal,
            is_fixed_price: false,
          }, true);
        }

        // 2) Additionally create fixed price activities for workforces with fixed daily pay
        if (fixedIds.length > 0) {
          for (const wId of fixedIds) {
            await this.activityStore.create({
              workforce_id: wId,
              date: this.selectedDate,
              section_id: this.activityForm.section_id || null,
              corporal_id: this.activityForm.corporal_id || null,
              price: fixedPrice,
              is_fixed_price: true,
            }, true);
          }
        }

        // Rich notification
        const fields = [];
        if (ids.length === 1) {
          const w = this.workforceStore.list.find(w => w.id === ids[0]);
          fields.push({ label: this.$t('workforce'), value: w ? w.full_name : '', icon: 'mdi-account' });
        } else {
          fields.push({ label: this.$t('workforces_count'), value: ids.length, icon: 'mdi-account-group' });
        }
        fields.push({ label: this.$t('date'), value: this.selectedDate, icon: 'mdi-calendar' });
        fields.push({ label: assignmentType, value: assignmentLabel, icon: this.activityForm.section_id ? 'mdi-sitemap-outline' : 'mdi-account-tie' });
        fields.push({ label: this.$t('price'), value: `${this.formatNumber(priceVal)} ${this.main.currency || 'DH'}`, icon: 'mdi-cash' });
        if (fixedIds.length > 0) {
          fields.push({ label: this.$t('fixed_daily_pay'), value: `${this.formatNumber(fixedPrice)} ${this.main.currency || 'DH'} (${fixedIds.length})`, icon: 'mdi-cash-lock' });
        }

        this.notification.notifyCreation({
          type: 'fortnight_activity',
          icon: 'mdi-calendar-plus',
          color: 'info',
          fields,
        });

        const wfDisplay = ids.length === 1
          ? (this.workforceStore.list.find(w => w.id === ids[0])?.full_name || '')
          : `${ids.length} ${this.$t('workforces')}`;
        this.triggerSummarySnackbar({
          title: this.$t('workforce_activity_added') || 'Activity added successfully',
          workforce: wfDisplay,
          section: this.activityForm.section_id ? assignmentLabel : '',
          corporal: this.activityForm.corporal_id ? assignmentLabel : '',
          date: this.selectedDate,
          amount: `${this.formatNumber(priceVal)} ${this.main.currency || 'DH'}`,
          icon: 'mdi-check-circle',
          iconColor: 'success',
        });

        // Reload activities and summary
        await this.loadActivities();
        await this.loadSummary();
        await this.loadBreakdown();

        // Preserve form values - focus back to workforce selector
        this.$nextTick(() => {
          if (this.$refs.workforceAutocomplete) {
            this.$refs.workforceAutocomplete.focus();
          }
        });
      } catch (e) {
        console.error(e);
        this.notification.push({ title: this.$t('error'), message: this.$t('workforce_save_error'), icon: 'mdi-alert-circle', color: 'error' });
      }
      this.submitting = false;
    },
    editActivity(act) {
      this.editForm = {
        id: act.id,
        section_id: act.section_id,
        corporal_id: act.corporal_id,
        price: parseFloat(act.price) || 0,
      };
      this.editDialog = true;
    },
    async saveEditActivity() {
      this.editSaving = true;
      try {
        const updatedAct = await this.activityStore.update(this.editForm.id, {
          section_id: this.editForm.section_id || null,
          corporal_id: this.editForm.corporal_id || null,
          price: this.editForm.price,
        });
        this.editDialog = false;

        // Rich notification
        const fields = [];
        const sectionName = this.editForm.section_id ? (this.sectionStore.list.find(s => s.id === this.editForm.section_id)?.name || '') : null;
        const corporalName = this.editForm.corporal_id ? (this.corporalStore.list.find(c => c.id === this.editForm.corporal_id)?.full_name || '') : null;
        if (sectionName) fields.push({ label: this.$t('section_affiliated'), value: sectionName, icon: 'mdi-sitemap-outline' });
        if (corporalName) fields.push({ label: this.$t('corporal'), value: corporalName, icon: 'mdi-account-tie' });
        fields.push({ label: this.$t('price'), value: `${this.formatNumber(this.editForm.price)} ${this.main.currency || 'DH'}`, icon: 'mdi-cash' });

        this.notification.notifyUpdate({
          type: 'fortnight_activity',
          icon: 'mdi-calendar-edit',
          color: 'info',
          fields,
        });

        this.triggerSummarySnackbar({
          title: this.$t('workforce_activity_updated') || 'Activity updated successfully',
          section: sectionName || '',
          corporal: corporalName || '',
          amount: `${this.formatNumber(this.editForm.price)} ${this.main.currency || 'DH'}`,
          icon: 'mdi-pencil-circle',
          iconColor: '#42A5F5',
        });

        await this.loadActivities();
        await this.loadSummary();
        await this.loadBreakdown();

        // Refresh day detail dialog if open
        if (this.dayDetailDialog && this.dayDetailDate) {
          this.dayDetailActivities = await this.activityStore.fetchActivitiesByDate(this.dayDetailDate);
        }
      } catch (e) {
        console.error(e);
      }
      this.editSaving = false;
    },
    async deleteActivity(act) {
      try {
        await this.activityStore.remove(act.id);

        // Rich notification
        const fields = [];
        if (act.section) fields.push({ label: this.$t('section_affiliated'), value: act.section.name, icon: 'mdi-sitemap-outline' });
        if (act.corporal) fields.push({ label: this.$t('corporal'), value: act.corporal.full_name, icon: 'mdi-account-tie' });
        fields.push({ label: this.$t('price'), value: `${this.formatNumber(act.price)} ${this.main.currency || 'DH'}`, icon: 'mdi-cash' });
        fields.push({ label: this.$t('date'), value: act.date, icon: 'mdi-calendar' });

        this.notification.notifyDeletion({
          type: 'fortnight_activity',
          icon: 'mdi-calendar-remove',
          color: 'error',
          fields,
        });

        this.triggerSummarySnackbar({
          title: this.$t('workforce_activities_deleted') || 'Activity deleted successfully',
          workforce: act.workforce ? act.workforce.full_name : '',
          section: act.section ? act.section.name : '',
          corporal: act.corporal ? act.corporal.full_name : '',
          date: act.date,
          amount: `${this.formatNumber(act.price)} ${this.main.currency || 'DH'}`,
          icon: 'mdi-delete-circle',
          iconColor: 'error',
        });

        await this.loadActivities();
        await this.loadSummary();
        await this.loadBreakdown();
      } catch (e) {
        console.error(e);
      }
    },
    async bulkDeleteActivities() {
      if (!this.canDeleteActivities) return;
      this.deleting = true;
      const ids = [...this.selectedWorkforceIds];
      try {
        const deletedCount = await this.activityStore.bulkDeleteByDate(ids, this.selectedDate);

        // Rich notification
        const fields = [];
        if (ids.length === 1) {
          const w = this.workforceStore.list.find(w => w.id === ids[0]);
          fields.push({ label: this.$t('workforce'), value: w ? w.full_name : '', icon: 'mdi-account' });
        } else {
          fields.push({ label: this.$t('workforces_count'), value: ids.length, icon: 'mdi-account-group' });
        }
        fields.push({ label: this.$t('date'), value: this.selectedDate, icon: 'mdi-calendar' });
        fields.push({ label: this.$t('activities_removed'), value: deletedCount, icon: 'mdi-delete-sweep' });

        this.notification.notifyDeletion({
          type: 'fortnight_activity',
          icon: 'mdi-calendar-remove',
          color: 'error',
          fields,
        });

        this.triggerSummarySnackbar({
          title: this.$t('workforce_activities_deleted') || 'Activities deleted successfully',
          workforce: ids.length === 1 ? (this.workforceStore.list.find(w => w.id === ids[0])?.full_name || '') : `${ids.length} ${this.$t('workforces')}`,
          date: this.selectedDate,
          days: deletedCount,
          icon: 'mdi-delete-sweep',
          iconColor: 'error',
        });

        // Reload
        await this.loadActivities();
        await this.loadSummary();
        await this.loadBreakdown();
      } catch (e) {
        console.error(e);
        this.notification.push({ title: this.$t('error'), message: this.$t('workforce_delete_error'), icon: 'mdi-alert-circle', color: 'error' });
      }
      this.deleting = false;
    },
    /** Delete all activities for a specific calendar cell (date) */
    async deleteCellActivities(cell) {
      if (!cell || !cell.dateStr) return;
      this.deletingCell = cell.dateStr;
      try {
        const wfIds = this.noWorkforceSelected ? [] : [...this.selectedWorkforceIds];
        const deletedCount = await this.activityStore.bulkDeleteByDate(
          wfIds.length > 0 ? wfIds : null,
          cell.dateStr
        );

        // Rich notification
        const fields = [];
        fields.push({ label: this.$t('date'), value: cell.dateStr, icon: 'mdi-calendar' });
        if (!this.noWorkforceSelected && wfIds.length === 1) {
          const w = this.workforceStore.list.find(w => w.id === wfIds[0]);
          fields.push({ label: this.$t('workforce'), value: w ? w.full_name : '', icon: 'mdi-account' });
        } else if (!this.noWorkforceSelected && wfIds.length > 1) {
          fields.push({ label: this.$t('workforces_count'), value: wfIds.length, icon: 'mdi-account-group' });
        } else {
          fields.push({ label: this.$t('scope'), value: this.$t('all_workforces'), icon: 'mdi-account-group' });
        }
        fields.push({ label: this.$t('activities_removed'), value: deletedCount, icon: 'mdi-delete-sweep' });

        this.notification.notifyDeletion({
          type: 'fortnight_activity',
          icon: 'mdi-calendar-remove',
          color: 'error',
          fields,
        });

        this.triggerSummarySnackbar({
          title: this.$t('workforce_activities_deleted') || 'Activities deleted successfully',
          workforce: !this.noWorkforceSelected && wfIds.length === 1 ? (this.workforceStore.list.find(w => w.id === wfIds[0])?.full_name || '') : this.$t('all_workforces'),
          date: cell.dateStr,
          days: deletedCount,
          icon: 'mdi-delete-sweep',
          iconColor: 'error',
        });

        // Reload
        await this.loadActivities();
        await this.loadSummary();
        await this.loadBreakdown();
      } catch (e) {
        console.error(e);
        this.notification.push({ title: this.$t('error'), message: this.$t('workforce_delete_error'), icon: 'mdi-alert-circle', color: 'error' });
      }
      this.deletingCell = null;
    },
  },
};
</script>

<style scoped>
.fnm-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.fnm-hero { background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 96px; }
.fnm-hero-inner { position: relative; z-index: 1; padding: 18px 24px; color: #fff; }
.fnm-hero-icon { width: 50px; height: 50px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.2); flex-shrink: 0; }
.fnm-hero-title { font-size: 20px; font-weight: 800; letter-spacing: .3px; }
.fnm-hero-stat { padding: 6px 14px; border-radius: 20px; background: rgba(255,255,255,.18); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.22); color: #fff; }
.fnm-hero-toggle { background: rgba(255,255,255,.18) !important; backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.25) !important; color: #fff !important; }
.fnm-hero-toggle :deep(.v-btn--active) { background: #fff !important; color: #1565C0 !important; font-weight: 700 !important; }

/* Left Panel */
.fnm-panel-card { border-color: rgba(var(--v-border-color), .12) !important; }
.fnm-panel-icon-wrap { width: 36px; height: 36px; border-radius: 10px; background: rgba(66,165,245,.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.fnm-date-capsule { background: rgba(var(--v-theme-surface-variant), .03); border-color: rgba(var(--v-border-color), .1) !important; }
.fnm-avatar-circle { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #1565C0, #42A5F5); color: #fff; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; letter-spacing: .3px; flex-shrink: 0; }
.fnm-btn-primary { box-shadow: 0 4px 14px rgba(66, 165, 245, 0.35) !important; transition: all .2s ease; }
.fnm-btn-primary:hover { box-shadow: 0 6px 18px rgba(66, 165, 245, 0.45) !important; transform: translateY(-1px); }
.fnm-btn-delete { transition: all .2s ease; }
.fnm-btn-delete:hover { background: rgba(244,67,54,.04); }

/* Summary KPI Cards */
.fnm-kpi-card { background: linear-gradient(135deg, rgba(21,101,192,.02) 0%, rgba(66,165,245,.06) 100%); border-color: rgba(66,165,245,.2) !important; transition: all .15s ease; }
.fnm-kpi-card:hover { border-color: rgba(66,165,245,.4) !important; transform: translateY(-1px); }
.fnm-kpi-card-total { background: linear-gradient(135deg, rgba(21,101,192,.08) 0%, rgba(66,165,245,.14) 100%); border-color: rgba(21,101,192,.25) !important; }
.fnm-currency-num { font-family: 'JetBrains Mono', 'Roboto Mono', monospace; font-variant-numeric: tabular-nums; }
.text-workforce { color: #42A5F5 !important; }

/* Calendar Header & Grid */
.fnm-cal-card { border-color: rgba(var(--v-border-color), .12) !important; }
.fnm-cal-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-bottom: 1px solid rgba(var(--v-border-color), .1); }
.fnm-cal-title { font-size: 16px; font-weight: 700; }
.fnm-segmented-toggle { border-color: rgba(66,165,245,.25) !important; }

/* Calendar Grid */
.fnm-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
.fnm-cal-dow { padding: 10px 4px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; opacity: .6; border-bottom: 1px solid rgba(var(--v-border-color), .08); }

/* Calendar Cells */
.fnm-cal-cell { min-height: 94px; border: 1px solid rgba(var(--v-border-color), .06); padding: 5px; position: relative; cursor: pointer; transition: background .15s ease, border-color .15s ease; }
.fnm-cal-cell:hover { background: rgba(66,165,245,.04); }
.fnm-cal-cell--empty { background: transparent; cursor: default; }
.fnm-cal-cell--outside { opacity: .25; cursor: default; }
.fnm-cal-cell--future { opacity: .35; cursor: not-allowed; pointer-events: none; background: repeating-linear-gradient(135deg, transparent, transparent 4px, rgba(0,0,0,.03) 4px, rgba(0,0,0,.03) 8px) !important; }
.fnm-cal-cell--today { background: rgba(66,165,245,.08) !important; box-shadow: inset 0 0 0 1.5px #42A5F5; }
.fnm-cal-cell--multi { background: rgba(255, 235, 59, .12) !important; }
.fnm-cal-cell--active-half { background-color: rgba(66,165,245,.015); border-color: rgba(66,165,245,.14); }

/* Day header: number + delete button */
.fnm-cal-day-header { display: flex; align-items: center; justify-content: space-between; }
.fnm-cal-day-num { font-size: 12px; font-weight: 700; padding: 2px 7px; display: inline-block; border-radius: 8px; }
.fnm-cal-day-selected { background: #42A5F5 !important; color: #fff !important; box-shadow: 0 2px 6px rgba(66,165,245,.35); }
.fnm-cal-month-label { font-size: 9px; font-weight: 700; text-transform: uppercase; opacity: .5; margin-left: 4px; }

/* Cell delete button - appears on hover */
.fnm-cell-delete-btn { opacity: 0; transition: opacity .2s ease; }
.fnm-cal-cell:hover .fnm-cell-delete-btn { opacity: 1; }

/* Summary mode cell */
.fnm-cal-cell--summary { }
.fnm-act-summary { background: linear-gradient(135deg, #1565C0, #7B1FA2); color: #fff; cursor: default; }
.fnm-act-summary:hover { filter: brightness(1.05); }

/* Multi-select mode */
.fnm-cal-cell--ms-selected {
  background: rgba(255, 152, 0, .15) !important;
  box-shadow: inset 0 0 0 2px rgba(255, 152, 0, .6);
  position: relative;
}
.fnm-cal-cell--ms-selected::after {
  content: '\F0132';
  font-family: 'Material Design Icons';
  position: absolute;
  top: 3px;
  right: 3px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #FF9800;
  color: #fff;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

/* Day Detail Dialog */
.fnm-dd-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: linear-gradient(135deg, #1565C0 0%, #7B1FA2 100%);
  color: #fff;
}
.fnm-dd-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
}
.fnm-dd-title {
  font-size: 15px;
  font-weight: 700;
  letter-spacing: .3px;
}
.fnm-dd-subtitle {
  font-size: 12px;
  opacity: .8;
}
.fnm-dd-summary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), .1);
}
.fnm-dd-item {
  border: 1px solid rgba(var(--v-border-color), .08);
  transition: all .15s ease;
}
.fnm-dd-item:hover {
  background: rgba(33, 150, 243, .04);
  border-color: rgba(33, 150, 243, .15);
}

/* Multi-select floating action bar */
.fnm-ms-action-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: linear-gradient(135deg, #E65100, #FF9800);
  border-bottom-left-radius: inherit;
  border-bottom-right-radius: inherit;
  gap: 12px;
}
.fnm-ms-info {
  display: flex;
  align-items: center;
  color: #fff;
}
.fnm-ms-count {
  font-weight: 700;
  font-size: 13px;
  letter-spacing: .3px;
}
.fnm-ms-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Activities inside cells */
.fnm-cal-activities { display: flex; flex-direction: column; gap: 2px; margin-top: 2px; }
.fnm-cal-activity { display: flex; align-items: center; gap: 3px; padding: 2px 6px; border-radius: 6px; font-size: 10px; position: relative; cursor: pointer; transition: all .15s ease; }
.fnm-cal-activity:hover { filter: brightness(1.1); }
.fnm-cal-activity:hover .fnm-act-delete { opacity: 1; }
.fnm-act-section { background: linear-gradient(135deg, #E65100, #FF9800); color: #fff; }
.fnm-act-corporal { background: linear-gradient(135deg, #0D47A1, #42A5F5); color: #fff; }
.fnm-act-fixed { background: linear-gradient(135deg, #F57F17, #FFD600); color: #33300d; border: 1px solid rgba(245,127,23,.3); }
.fnm-act-fixed .fnm-act-wf { background: rgba(0,0,0,.15); color: #fff; }
.fnm-act-fixed .fnm-act-price { color: #33300d; }
.fnm-act-fixed .fnm-act-label { color: #5d4a00; }
.fnm-act-wf { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 14px; padding: 0 3px; border-radius: 3px; background: rgba(255,255,255,.3); font-size: 8px; font-weight: 800; letter-spacing: .3px; line-height: 1; flex-shrink: 0; }
.fnm-act-price { font-weight: 700; white-space: nowrap; }
.fnm-act-label { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.fnm-act-delete { opacity: 0; position: absolute; right: 2px; top: 50%; transform: translateY(-50%); transition: opacity .15s ease; width: 14px !important; height: 14px !important; }

/* "+X more" badge */
.fnm-cal-more {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2px 6px;
  border-radius: 6px;
  font-size: 9px;
  font-weight: 800;
  color: #1565C0;
  background: rgba(33, 150, 243, .1);
  border: 1px dashed rgba(33, 150, 243, .3);
  cursor: pointer;
  transition: all .15s ease;
  letter-spacing: .3px;
}
.fnm-cal-more:hover {
  background: rgba(33, 150, 243, .2);
  border-color: rgba(33, 150, 243, .5);
}

/* Popup for all activities */
.fnm-more-header {
  display: flex;
  align-items: center;
  padding: 10px 14px;
  background: linear-gradient(135deg, #1565C0, #42A5F5);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  border-radius: 8px 8px 0 0;
}
.fnm-more-list {
  max-height: 280px;
  overflow-y: auto;
  padding: 6px;
}
.fnm-more-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 12px;
  cursor: pointer;
  transition: background .15s ease;
  border-bottom: 1px solid rgba(0,0,0,.04);
}
.fnm-more-item:last-child { border-bottom: none; }
.fnm-more-item:hover { background: rgba(33,150,243,.06); }
.fnm-more-wf {
  font-weight: 700;
  color: #1565C0;
  font-size: 11px;
  flex-shrink: 0;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.fnm-more-price {
  font-weight: 700;
  font-size: 11px;
  color: #2E7D32;
  white-space: nowrap;
  flex-shrink: 0;
}
.fnm-more-label {
  font-size: 11px;
  color: #78909C;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}
.fnm-more-del {
  flex-shrink: 0;
  opacity: .4;
  transition: opacity .15s ease;
}
.fnm-more-item:hover .fnm-more-del { opacity: 1; }

/* Responsive */
@media (max-width: 960px) {
  .fnm-cal-cell { min-height: 70px; }
  .fnm-cal-activity { font-size: 9px; padding: 1px 4px; }
}

/* Breakdown Table */
.fnm-breakdown-table { border: 1px solid rgba(var(--v-border-color), .12); }
.fnm-breakdown-table th { font-size: 11px !important; text-transform: uppercase; letter-spacing: .5px; background: rgba(var(--v-theme-surface-variant), .04); }
.fnm-breakdown-table td { font-size: 13px; }
.fnm-breakdown-total-row { background: rgba(33,150,243,.04); border-top: 2px solid rgba(33,150,243,.2); }

/* Sortable table headers */
.fnm-th-sortable { cursor: default; padding: 6px 4px !important; }
.fnm-th-inner {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  width: 100%;
  justify-content: inherit;
}
.fnm-th-label {
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  user-select: none;
  border-radius: 4px;
  padding: 2px 4px;
  transition: background .15s ease;
}
.fnm-th-label:hover { background: rgba(33,150,243,.1); }
.fnm-sort-indicator {
  display: inline-flex;
  align-items: center;
  gap: 1px;
  margin-left: 1px;
}
.fnm-sort-badge {
  font-size: 9px;
  font-weight: 700;
  color: #fff;
  background: #42A5F5;
  border-radius: 50%;
  width: 14px;
  height: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}
.fnm-filter-btn {
  opacity: .4;
  transition: opacity .15s ease;
  flex-shrink: 0;
}
.fnm-th-inner:hover .fnm-filter-btn,
.fnm-filter-btn[aria-expanded="true"] { opacity: 1; }
.fnm-filter-card {
  border-radius: 12px !important;
  box-shadow: 0 4px 20px rgba(0,0,0,.12) !important;
}
.fnm-filter-input {
  font-size: 12px;
}
.fnm-filter-bar {
  display: flex;
  align-items: center;
  padding: 6px 12px;
  background: rgba(33,150,243,.03);
  border-bottom: 1px solid rgba(33,150,243,.1);
}
.fnm-filter-checklist {
  max-height: 180px;
  overflow-y: auto;
  border: 1px solid rgba(var(--v-border-color), .12);
  border-radius: 8px;
  padding: 2px;
}
.fnm-filter-check-item {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 0 6px;
  border-radius: 6px;
  cursor: pointer;
  transition: background .1s ease;
}
.fnm-filter-check-item:hover {
  background: rgba(33,150,243,.06);
}
.fnm-filter-checkbox {
  flex-shrink: 0;
  max-width: 28px;
}
.fnm-filter-check-label {
  font-size: 12px;
  line-height: 1.3;
  max-width: 140px;
}

/* Clickable rows */
.fnm-clickable-row { cursor: pointer; transition: background .15s ease; }
.fnm-clickable-row:hover { background: rgba(33,150,243,.06) !important; }

/* Breadcrumb */
.fnm-breadcrumb {
  padding: 6px 12px;
  background: rgba(33,150,243,.04);
  border-radius: 8px;
  border: 1px solid rgba(33,150,243,.1);
  font-size: 13px;
}
.fnm-breadcrumb-link {
  display: inline-flex;
  align-items: center;
  background: none;
  border: none;
  color: #2196F3;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  padding: 2px 6px;
  border-radius: 6px;
  transition: background .15s ease;
}
.fnm-breadcrumb-link:hover { background: rgba(33,150,243,.1); }
.fnm-breadcrumb-current {
  display: inline-flex;
  align-items: center;
  font-weight: 600;
  font-size: 13px;
  opacity: .7;
}

/* Fixed Daily Price input wrapper */
.fnm-fixed-price-wrap {
  border: 1.5px solid rgba(245,127,23,.25);
  border-radius: 12px;
  padding: 10px 12px 4px;
  background: rgba(245,127,23,.04);
  transition: all .2s ease;
}
.fnm-fixed-price-wrap:hover {
  border-color: rgba(245,127,23,.4);
}
.fnm-fixed-price-badge {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  font-size: 12px;
}
/* Column Settings Dialog */
.col-settings-list {
  max-height: 420px;
  overflow-y: auto;
  border: 1px solid rgba(var(--v-border-color), .12);
  border-radius: 12px;
  padding: 4px;
}
.col-settings-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid transparent;
  transition: all .15s ease;
  cursor: grab;
  user-select: none;
}
.col-settings-item:hover {
  background: rgba(66, 165, 245, .06);
  border-color: rgba(66, 165, 245, .15);
}
.col-settings-item--locked {
  cursor: default;
  opacity: .7;
  background: rgba(var(--v-border-color), .04);
}
.col-settings-item--dragging {
  background: rgba(66, 165, 245, .12);
  border-color: #42A5F5;
  box-shadow: 0 2px 8px rgba(66,165,245,.18);
}
.col-settings-drag-handle {
  cursor: grab;
  flex-shrink: 0;
}
.col-settings-cb {
  flex-shrink: 0;
  max-width: 28px;
}
.col-settings-label {
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Breakdown Table Refinements */
.fnm-breakdown-table th { font-size: 11px !important; text-transform: uppercase; letter-spacing: .5px; background: rgba(var(--v-theme-surface-variant), .05) !important; font-weight: 700 !important; }
.fnm-breakdown-table td { font-size: 13px; font-variant-numeric: tabular-nums; }
.fnm-breakdown-table tbody tr:nth-child(even) { background-color: rgba(var(--v-theme-on-surface), 0.015); }
.fnm-breakdown-table tbody tr:hover { background-color: rgba(66, 165, 245, 0.05) !important; }
.fnm-breakdown-total-row { background: linear-gradient(135deg, rgba(21,101,192,.06) 0%, rgba(66,165,245,.1) 100%) !important; border-top: 2px solid #42A5F5 !important; font-weight: 700; }

/* Workforce Avatar in Table */
.fnm-wf-avatar { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #1565C0, #42A5F5); color: #fff; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; letter-spacing: .4px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(21,101,192,.2); }
.fnm-wf-name { color: rgba(var(--v-theme-on-surface), .95); letter-spacing: .2px; font-weight: 700; }

/* Executive KPI Cards */
.fnm-executive-kpi { background: linear-gradient(135deg, rgba(21,101,192,.02) 0%, rgba(66,165,245,.06) 100%); border-color: rgba(66,165,245,.2) !important; transition: all .2s ease; }
.fnm-executive-kpi:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.06); border-color: rgba(66,165,245,.35) !important; }
.fnm-executive-kpi--hero { background: linear-gradient(135deg, #1565C0 0%, #1E88E5 50%, #42A5F5 100%) !important; color: #fff !important; box-shadow: 0 8px 24px rgba(21,101,192,.3) !important; border: none !important; }
.letter-spacing-1 { letter-spacing: 1px; }
</style>

<!-- ================= PRINT PREVIEW MODAL & STAGE STYLING (Unscoped for Teleported Dialog) ================= -->
<style>
.fnm-print-dialog-card {
  display: flex;
  flex-direction: column;
  max-height: 94vh;
}
.fnm-print-dialog-card ::-webkit-scrollbar-corner {
  background: transparent;
}
.fnm-print-modal-header {
  display: flex;
  align-items: center;
  padding: 14px 20px;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  color: #fff;
  flex-shrink: 0;
}
.fnm-print-header-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}
.fnm-zoom-pill {
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(8px);
  padding: 3px 6px;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}
.fnm-zoom-btn--active {
  background: #ffffff !important;
  color: #1565C0 !important;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18) !important;
}
.v-theme--dark .fnm-zoom-btn--active,
.fnm-theme--dark .fnm-zoom-btn--active {
  background: #1976D2 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 6px rgba(25, 118, 210, 0.4) !important;
}
.fnm-print-modal-body {
  overflow: hidden !important;
  display: flex !important;
  flex: 1 !important;
  padding: 0 !important;
  max-height: calc(94vh - 70px) !important;
}
.fnm-print-layout {
  display: flex;
  width: 100%;
  height: calc(94vh - 70px);
}
.fnm-print-sidebar {
  width: 340px;
  min-width: 340px;
  border-right: 1px solid rgba(0, 0, 0, 0.08);
  background: #fbfcfe;
  color: #1e293b;
  overflow-y: auto !important;
  transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}
.fnm-print-sidebar::-webkit-scrollbar,
.fnm-print-cols-list::-webkit-scrollbar {
  width: 5px !important;
  height: 5px !important;
}
.fnm-print-sidebar::-webkit-scrollbar-button,
.fnm-print-cols-list::-webkit-scrollbar-button {
  display: none !important;
  width: 0 !important;
  height: 0 !important;
}
.fnm-print-sidebar::-webkit-scrollbar-track,
.fnm-print-cols-list::-webkit-scrollbar-track {
  background: transparent !important;
}
.fnm-print-sidebar::-webkit-scrollbar-thumb,
.fnm-print-cols-list::-webkit-scrollbar-thumb {
  background: rgba(128, 128, 128, 0.35) !important;
  border-radius: 5px !important;
  transition: background 0.3s ease !important;
}
.fnm-print-sidebar::-webkit-scrollbar-thumb:hover,
.fnm-print-cols-list::-webkit-scrollbar-thumb:hover {
  background: rgba(128, 128, 128, 0.65) !important;
}

@supports not selector(::-webkit-scrollbar) {
  .fnm-print-sidebar,
  .fnm-print-cols-list {
    scrollbar-width: thin;
    scrollbar-color: rgba(128, 128, 128, 0.4) transparent;
  }
}

.fnm-print-ctrl-group {
  margin-bottom: 16px;
}
.fnm-ctrl-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #455a64;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
}
.fnm-switch-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.fnm-switch-list .v-label {
  color: #334155;
  font-size: 13px;
  font-weight: 500;
}
.fnm-print-cols-box {
  background: #ffffff;
  border: 1.5px solid rgba(66, 165, 245, 0.25);
  border-radius: 12px;
  padding: 10px;
}
.fnm-print-cols-list {
  max-height: 240px;
  overflow-y: auto !important;
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding-right: 2px;
}
.fnm-print-col-row {
  display: flex;
  align-items: center;
  padding: 3px 6px;
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  background: #ffffff;
  cursor: grab;
  user-select: none;
  transition: all 0.15s ease;
}
.fnm-print-col-row:hover {
  background: rgba(66, 165, 245, 0.07);
  border-color: rgba(66, 165, 245, 0.35);
}
.fnm-print-col-row--dragging {
  opacity: 0.35;
  background: rgba(66, 165, 245, 0.15);
  border: 1.5px dashed #1565C0;
}
.fnm-print-col-row--dragover {
  border-color: #1565C0;
  background: rgba(66, 165, 245, 0.14);
  transform: translateY(2px);
  box-shadow: 0 3px 8px rgba(21, 101, 192, 0.18);
}
.fnm-print-col-row--locked {
  cursor: default;
  background: #f8fafc;
  color: #64748b;
}
.fnm-print-drag-handle {
  cursor: grab;
  color: #90a4ae;
}
.fnm-print-col-row:active .fnm-print-drag-handle {
  cursor: grabbing;
}

/* Segmented Toggle Rounded Pill Style */
.fnm-segmented-toggle {
  border-radius: 14px !important;
  border: 1.5px solid rgba(66, 165, 245, 0.35) !important;
  background: #f0f7ff !important;
  padding: 3px !important;
  display: flex !important;
  gap: 4px !important;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04) !important;
}
.fnm-segmented-toggle .v-btn {
  border-radius: 10px !important;
  border: none !important;
  text-transform: none !important;
  font-weight: 600 !important;
  font-size: 12px !important;
  letter-spacing: 0.2px !important;
  transition: all 0.2s ease !important;
}
.fnm-segmented-toggle .v-btn--active {
  background: #1565C0 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(21, 101, 192, 0.35) !important;
}
.fnm-segmented-toggle .v-btn:not(.v-btn--active) {
  background: transparent !important;
  color: #546e7a !important;
}

/* ========================================================
   DARK THEME OVERRIDES FOR PRINT PREVIEW CONTROLS & SIDEBAR
   ======================================================== */
.v-theme--dark .fnm-print-dialog-card,
.fnm-theme--dark.fnm-print-dialog-card {
  background: #181b20 !important;
  color: #f1f5f9 !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
}

.v-theme--dark .fnm-print-sidebar,
.fnm-theme--dark .fnm-print-sidebar {
  background: #1a1e24 !important;
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
  color: #e2e8f0 !important;
}

.v-theme--dark .fnm-ctrl-title,
.fnm-theme--dark .fnm-ctrl-title {
  color: #90caf9 !important;
}

.v-theme--dark .fnm-switch-list .v-label,
.fnm-theme--dark .fnm-switch-list .v-label {
  color: #e2e8f0 !important;
  opacity: 0.95 !important;
}

.v-theme--dark .fnm-segmented-toggle,
.fnm-theme--dark .fnm-segmented-toggle {
  background: rgba(255, 255, 255, 0.06) !important;
  border-color: rgba(66, 165, 245, 0.35) !important;
}

.v-theme--dark .fnm-segmented-toggle .v-btn:not(.v-btn--active),
.fnm-theme--dark .fnm-segmented-toggle .v-btn:not(.v-btn--active) {
  color: #b0bec5 !important;
}

.v-theme--dark .fnm-segmented-toggle .v-btn--active,
.fnm-theme--dark .fnm-segmented-toggle .v-btn--active {
  background: #1976D2 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(25, 118, 210, 0.45) !important;
}

.v-theme--dark .fnm-print-cols-box,
.fnm-theme--dark .fnm-print-cols-box {
  background: #14171b !important;
  border-color: rgba(66, 165, 245, 0.3) !important;
}

.v-theme--dark .fnm-print-col-row,
.fnm-theme--dark .fnm-print-col-row {
  background: #20252c !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
  color: #f1f5f9 !important;
}

.v-theme--dark .fnm-print-col-row:hover,
.fnm-theme--dark .fnm-print-col-row:hover {
  background: rgba(66, 165, 245, 0.22) !important;
  border-color: rgba(66, 165, 245, 0.5) !important;
}

.v-theme--dark .fnm-print-col-row--locked,
.fnm-theme--dark .fnm-print-col-row--locked {
  background: #16191d !important;
  color: #94a3b8 !important;
}

.v-theme--dark .fnm-print-col-row .v-label,
.fnm-theme--dark .fnm-print-col-row .v-label {
  color: #f1f5f9 !important;
}

.v-theme--dark .fnm-print-stage,
.fnm-theme--dark .fnm-print-stage {
  background: #121417 !important;
}

/* Print Stage (Preview Container) - Sleek Auto-hide X & Y Scrollbars matching Navigation Drawer */
.fnm-print-stage {
  flex: 1;
  background: #3c4043;
  overflow: auto !important;
  padding: 24px 20px 48px;
  display: block;
}
.fnm-print-stage::-webkit-scrollbar {
  width: 6px !important;
  height: 6px !important;
}
.fnm-print-stage::-webkit-scrollbar-button {
  display: none !important;
  width: 0 !important;
  height: 0 !important;
}
.fnm-print-stage::-webkit-scrollbar-track {
  background: transparent !important;
}
.fnm-print-stage::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.35) !important;
  border-radius: 6px !important;
  transition: background 0.3s ease !important;
}
.fnm-print-stage::-webkit-scrollbar-corner {
  background: transparent !important;
}
.fnm-print-stage:hover::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.6) !important;
}
.fnm-print-stage::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.85) !important;
}
.fnm-print-stage::-webkit-scrollbar-thumb:active {
  background: #42A5F5 !important;
}

@supports not selector(::-webkit-scrollbar) {
  .fnm-print-stage {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.4) transparent;
  }
}
.fnm-print-sheet-scaler {
  display: block;
  text-align: left;
  margin: 0 auto;
  width: max-content;
  max-width: none;
  transform-origin: top center;
  transition: transform 0.2s ease-out;
}

/* Authentic Document Sheet - Expands to contain ALL content without clipping */
.fnm-print-sheet {
  background: #ffffff !important;
  color: #1a1a1a !important;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  box-shadow: 0 14px 44px rgba(0, 0, 0, 0.4);
  border-radius: 6px;
  padding: 28px 32px 40px;
  box-sizing: border-box;
  height: auto !important;
  display: block;
  overflow: visible;
  margin-bottom: 36px;
  transition: min-width 0.2s ease;
}

/* ========================================================
   PRINTABLE AREA (#fnm-print-area) - PERMANENT LIGHT THEME
   The physical printed document sheet is ALWAYS pure white 
   paper with high-contrast executive styling, completely 
   insulated from dark theme settings.
   ======================================================== */
#fnm-print-area,
#fnm-print-area * {
  color-scheme: light !important;
  box-sizing: border-box;
}

#fnm-print-area {
  background-color: #ffffff !important;
  color: #1a1a1a !important;
  border-color: #e0e0e0 !important;
}

#fnm-print-area .fnm-pdoc-header {
  border-bottom: 2px solid #1565C0 !important;
}

#fnm-print-area .fnm-pdoc-company-name {
  color: #0D47A1 !important;
}

#fnm-print-area .fnm-pdoc-company-sub {
  color: #555555 !important;
}

#fnm-print-area .fnm-pdoc-company-reg {
  color: #777777 !important;
}

#fnm-print-area .fnm-pdoc-title-main {
  color: #1565C0 !important;
}

#fnm-print-area .fnm-pdoc-title-tab {
  color: #455a64 !important;
}

#fnm-print-area .fnm-pdoc-period-badge {
  background: #E3F2FD !important;
  color: #0D47A1 !important;
  border: 1px solid rgba(21, 101, 192, 0.2) !important;
}

#fnm-print-area .fnm-pdoc-meta-card {
  background: #f8fafc !important;
  border: 1px solid #e2e8f0 !important;
}

#fnm-print-area .fnm-pdoc-meta-label {
  color: #64748b !important;
}

#fnm-print-area .fnm-pdoc-meta-value {
  color: #0f172a !important;
}

#fnm-print-area .fnm-pdoc-meta-strip {
  background: #f1f5f9 !important;
  border: 1px solid #e2e8f0 !important;
}

#fnm-print-area .fnm-pdoc-strip-cell {
  color: #334155 !important;
}

#fnm-print-area .fnm-pdoc-th {
  background: #E3F2FD !important;
  color: #0D47A1 !important;
  border: 1px solid #90CAF9 !important;
}

#fnm-print-area .fnm-head-amber .fnm-pdoc-th {
  background: #FFF3E0 !important;
  color: #E65100 !important;
  border-color: #FFCC80 !important;
}

#fnm-print-area .fnm-head-purple .fnm-pdoc-th {
  background: #F3E5F5 !important;
  color: #6A1B9A !important;
  border-color: #CE93D8 !important;
}

#fnm-print-area .fnm-pdoc-td {
  border: 1px solid #e0e0e0 !important;
  color: #212121 !important;
  background-color: #ffffff !important;
}

#fnm-print-area .fnm-pdoc-row-alt .fnm-pdoc-td {
  background-color: #f9fbfd !important;
}

#fnm-print-area .fnm-pdoc-total-tr {
  background: #E3F2FD !important;
}

#fnm-print-area .fnm-pdoc-total-td {
  border: 1px solid #90CAF9 !important;
  color: #0D47A1 !important;
}

#fnm-print-area .fnm-total-amber {
  background: #FFF3E0 !important;
}

#fnm-print-area .fnm-total-amber .fnm-pdoc-total-td {
  border-color: #FFCC80 !important;
  color: #E65100 !important;
}

#fnm-print-area .fnm-total-purple {
  background: #F3E5F5 !important;
}

#fnm-print-area .fnm-total-purple .fnm-pdoc-total-td {
  border-color: #CE93D8 !important;
  color: #6A1B9A !important;
}

#fnm-print-area .fnm-badge-yes {
  background: #E8F5E9 !important;
  color: #2E7D32 !important;
}

#fnm-print-area .fnm-badge-no {
  background: #FFEBEE !important;
  color: #C62828 !important;
}

#fnm-print-area .fnm-badge-paid {
  background: #E8F5E9 !important;
  color: #1B5E20 !important;
}

#fnm-print-area .fnm-badge-unpaid {
  background: #ECEFF1 !important;
  color: #546E7A !important;
}

#fnm-print-area .fnm-pdoc-gt-card {
  background: #f8fafc !important;
  border: 1.5px solid #1E88E5 !important;
}

#fnm-print-area .fnm-pdoc-gt-val {
  color: #0D47A1 !important;
}

#fnm-print-area .fnm-pdoc-gt-lbl {
  color: #455a64 !important;
}

#fnm-print-area .fnm-pdoc-sig-box {
  background: #f8fafc !important;
  border: 1px solid #cfd8dc !important;
}

#fnm-print-area .fnm-pdoc-sig-lbl {
  color: #37474f !important;
}

#fnm-print-area .fnm-pdoc-notes {
  background: #fffde7 !important;
  border: 1px solid #fff59d !important;
}

#fnm-print-area .fnm-pdoc-notes-title {
  color: #f57f17 !important;
}

#fnm-print-area .fnm-pdoc-notes-body {
  color: #424242 !important;
}

#fnm-print-area .fnm-pdoc-footer {
  border-top: 1px solid #e0e0e0 !important;
  color: #888888 !important;
}
.fnm-print-sheet.fnm-print-landscape {
  min-width: 297mm;
  width: max-content;
  max-width: none;
  min-height: 210mm;
}
.fnm-print-sheet.fnm-print-portrait {
  min-width: 210mm;
  width: max-content;
  max-width: none;
  min-height: 297mm;
}

/* Density Scales */
.fnm-density-compact {
  font-size: 11px;
}
.fnm-density-compact .fnm-pdoc-th,
.fnm-density-compact .fnm-pdoc-td,
.fnm-density-compact .fnm-pdoc-total-td {
  padding: 4px 6px !important;
}
.fnm-density-standard {
  font-size: 12px;
}
.fnm-density-standard .fnm-pdoc-th,
.fnm-density-standard .fnm-pdoc-td,
.fnm-density-standard .fnm-pdoc-total-td {
  padding: 6px 8px !important;
}
.fnm-density-spacious {
  font-size: 13px;
}
.fnm-density-spacious .fnm-pdoc-th,
.fnm-density-spacious .fnm-pdoc-td,
.fnm-density-spacious .fnm-pdoc-total-td {
  padding: 8px 12px !important;
}

/* Document Header */
.fnm-pdoc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid #1565C0;
  padding-bottom: 14px;
  margin-bottom: 12px;
  gap: 16px;
}
.fnm-pdoc-company-col {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}
.fnm-pdoc-logo {
  height: 52px;
  max-width: 140px;
  object-fit: contain;
}
.fnm-pdoc-company-name {
  font-size: 14px;
  font-weight: 800;
  color: #0D47A1;
}
.fnm-pdoc-company-sub {
  font-size: 10px;
  color: #555;
}
.fnm-pdoc-company-reg {
  font-size: 9px;
  color: #777;
}
.fnm-pdoc-title-col {
  text-align: center;
  flex: 1.2;
}
.fnm-pdoc-title-box {
  margin-bottom: 4px;
}
.fnm-pdoc-title-main {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: 0.5px;
  color: #1565C0;
  text-transform: uppercase;
}
.fnm-pdoc-title-tab {
  font-size: 12px;
  font-weight: 600;
  color: #455a64;
}
.fnm-pdoc-period-badge {
  display: inline-block;
  background: #E3F2FD;
  color: #0D47A1;
  padding: 2px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
  border: 1px solid rgba(21, 101, 192, 0.2);
}
.fnm-pdoc-meta-col {
  flex: 1;
  display: flex;
  justify-content: flex-end;
}
.fnm-pdoc-meta-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 10px;
  min-width: 170px;
}
.fnm-pdoc-meta-line {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 2px;
}
.fnm-pdoc-meta-line:last-child {
  margin-bottom: 0;
}
.fnm-pdoc-meta-label {
  color: #64748b;
  font-weight: 600;
}
.fnm-pdoc-meta-value {
  color: #0f172a;
  font-weight: 700;
}

/* Metadata Strip */
.fnm-pdoc-meta-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 6px 12px;
  font-size: 10.5px;
  margin-bottom: 12px;
}
.fnm-pdoc-strip-cell {
  color: #334155;
}

/* Datatables */
.fnm-pdoc-table-wrap {
  width: 100%;
  overflow: visible;
  margin-bottom: 14px;
}
.fnm-pdoc-table {
  width: 100%;
  border-collapse: collapse;
  font-variant-numeric: tabular-nums;
  table-layout: auto;
}
.fnm-pdoc-th {
  background: #E3F2FD;
  color: #0D47A1;
  border: 1px solid #90CAF9;
  font-weight: 700;
  text-align: center;
  letter-spacing: 0.2px;
  white-space: normal;
  word-break: break-word;
  line-height: 1.25;
  vertical-align: middle;
}
.fnm-head-amber .fnm-pdoc-th {
  background: #FFF3E0 !important;
  color: #E65100 !important;
  border-color: #FFCC80 !important;
}
.fnm-head-purple .fnm-pdoc-th {
  background: #F3E5F5 !important;
  color: #6A1B9A !important;
  border-color: #CE93D8 !important;
}
.fnm-pdoc-th-period {
  font-size: 9px;
  font-weight: 500;
  opacity: 0.85;
  margin-left: 2px;
}
.fnm-pdoc-td {
  border: 1px solid #e0e0e0;
  color: #212121;
}
.fnm-pdoc-row-alt .fnm-pdoc-td {
  background: #f9fbfd;
}
.fnm-pdoc-total-tr {
  background: #E3F2FD;
  font-weight: 800;
}
.fnm-total-amber {
  background: #FFF3E0 !important;
}
.fnm-total-purple {
  background: #F3E5F5 !important;
}
.fnm-pdoc-total-td {
  border: 1px solid #90CAF9;
  color: #0D47A1;
}
.fnm-total-amber .fnm-pdoc-total-td {
  border-color: #FFCC80 !important;
  color: #E65100 !important;
}
.fnm-total-purple .fnm-pdoc-total-td {
  border-color: #CE93D8 !important;
  color: #6A1B9A !important;
}

/* Badges */
.fnm-badge-yes {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  background: #E8F5E9;
  color: #2E7D32;
  font-weight: 700;
  font-size: 9.5px;
}
.fnm-badge-no {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  background: #FFEBEE;
  color: #C62828;
  font-weight: 600;
  font-size: 9.5px;
}
.fnm-badge-paid {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  background: #E8F5E9;
  color: #1B5E20;
  font-weight: 800;
  font-size: 9.5px;
}
.fnm-badge-unpaid {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  background: #ECEFF1;
  color: #546E7A;
  font-weight: 600;
  font-size: 9.5px;
}

/* Grand Totals KPI Cards */
.fnm-pdoc-grand-totals {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin: 14px 0;
}
.fnm-pdoc-gt-card {
  flex: 1;
  background: #f8fafc;
  border: 1.5px solid #1E88E5;
  border-radius: 8px;
  padding: 8px 12px;
  text-align: center;
}
.fnm-pdoc-gt-card-month {
  border-color: #7B1FA2;
  background: #FAF5FF;
}
.fnm-pdoc-gt-label {
  font-size: 10px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  margin-bottom: 2px;
}
.fnm-pdoc-gt-value {
  font-size: 15px;
  font-weight: 800;
}
.fnm-pdoc-gt-cur {
  font-size: 10px;
  font-weight: 600;
}

/* Notes Block */
.fnm-pdoc-notes-block {
  border: 1px dashed #90CAF9;
  background: #F8FBFE;
  border-radius: 6px;
  padding: 8px 12px;
  margin-bottom: 14px;
  font-size: 10.5px;
}
.fnm-pdoc-notes-title {
  font-weight: 700;
  color: #1565C0;
  margin-bottom: 3px;
}
.fnm-pdoc-notes-body {
  color: #374151;
  white-space: pre-wrap;
}

/* Signatures Block */
.fnm-pdoc-signatures {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin: 18px 0 10px;
}
.fnm-pdoc-sig-box {
  flex: 1;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 6px 10px;
  background: #fff;
}
.fnm-pdoc-sig-header {
  font-size: 10.5px;
  font-weight: 700;
  color: #334155;
  text-align: center;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 4px;
}
.fnm-pdoc-sig-space {
  height: 48px;
}
.fnm-pdoc-sig-line {
  font-size: 9.5px;
  color: #64748b;
  border-top: 1px dashed #cbd5e1;
  padding-top: 3px;
}

/* Footer */
.fnm-pdoc-footer {
  border-top: 1px solid #cbd5e1;
  padding-top: 8px;
  margin-top: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 9.5px;
  color: #64748b;
}
.fnm-pdoc-footer-legal {
  font-weight: 600;
}
.fnm-pdoc-footer-notice {
  font-style: italic;
}

/* ================= PRINT MEDIA OVERRIDES ================= */
@media print {
  body * {
    visibility: hidden;
  }
  #fnm-print-area,
  #fnm-print-area * {
    visibility: visible;
  }
  #fnm-print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .fnm-pdoc-table {
    page-break-inside: auto;
  }
  .fnm-pdoc-head-tr {
    page-break-after: avoid;
    page-break-inside: avoid;
    display: table-header-group;
  }
  .fnm-pdoc-body-tr {
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .fnm-pdoc-total-tr {
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .fnm-pdoc-grand-totals,
  .fnm-pdoc-signatures,
  .fnm-pdoc-footer {
    page-break-inside: avoid;
    break-inside: avoid;
  }
}

@media (max-width: 960px) {
  .fnm-print-layout {
    flex-direction: column !important;
    height: auto !important;
    max-height: calc(90vh - 60px) !important;
  }
  .fnm-print-sidebar {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    max-height: 280px !important;
    border-right: none !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
  }
  .fnm-print-stage {
    padding: 16px 12px 36px !important;
  }
}
</style>
