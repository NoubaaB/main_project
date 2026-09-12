<template>
  <v-card class="fnm-card" elevation="0">
    <!-- Hero Header -->
    <div class="fnm-hero">
      <div class="fnm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="fnm-hero-icon">
            <v-icon size="28" color="white">mdi-calendar-month</v-icon>
          </div>
          <div>
            <div class="fnm-hero-title">{{ $t('fortnight_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_fortnight_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <v-row>
        <!-- ============= LEFT PANEL ============= -->
        <v-col cols="12" md="3">
          <v-card variant="outlined" class="rounded-xl pa-4" color="info">
            <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
              <v-icon color="info" class="mr-2" size="20">mdi-information-outline</v-icon>
              {{ $t('information') }}
            </div>

            <!-- Date display -->
            <div class="mb-3">
              <div class="text-caption text-grey">{{ $t('date') }}</div>
              <div class="d-flex align-center ga-2">
                <v-icon size="18" color="info">mdi-calendar</v-icon>
                <span class="font-weight-bold">{{ formattedDate }}</span>
              </div>
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
              color="info"
              class="text-info mb-3"
              clearable
              multiple
              chips
              closable-chips
              @update:model-value="onWorkforceChange"
            >
              <template #chip="{ item, props }">
                <v-chip v-bind="props" size="small" color="info" variant="tonal" closable>{{ item.title }}</v-chip>
              </template>
              <template #item="{ item, props }">
                <v-list-item v-bind="props">
                  <template #prepend>
                    <v-checkbox-btn
                      :model-value="selectedWorkforceIds.includes(item.value)"
                      color="info"
                      density="compact"
                      class="mr-1"
                    ></v-checkbox-btn>
                  </template>
                  <v-list-item-subtitle>{{ item.raw.matricule }}</v-list-item-subtitle>
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
              color="info"
              class="text-info mb-3"
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
              color="info"
              class="text-info mb-3"
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
              color="info"
              class="text-info mb-3"
              type="number"
              min="0"
              step="0.01"
              :prefix="'Dh'"
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
              color="info"
              variant="flat"
              rounded
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
              class="mt-2"
              :disabled="!canDeleteActivities"
              :loading="deleting"
              @click="bulkDeleteActivities"
              prepend-icon="mdi-delete-outline"
            >
              {{ $t('delete_activities_for_date') }}
            </v-btn>

            <v-divider class="my-4"></v-divider>

            <!-- ============= SUMMARY ============= -->
            <!-- 1st Fortnight -->
            <div class="fnm-summary-block mb-3">
              <div class="text-subtitle-2 font-weight-bold">{{ $t('first_fortnight') }}</div>
              <div class="d-flex justify-space-between align-center mt-1">
                <div class="text-caption text-grey">{{ $t('fortnight_amount') }}</div>
                <div class="font-weight-bold text-info">Dh {{ formatNumber(summary.first_fortnight.total) }}</div>
              </div>
              <div class="d-flex justify-space-between align-center">
                <div class="text-caption text-grey">{{ $t('working_days') }}</div>
                <div class="d-flex align-center ga-1">
                  <v-icon size="14">mdi-calendar-check</v-icon>
                  <span>{{ summary.first_fortnight.working_days }}</span>
                </div>
              </div>
            </div>

            <!-- 2nd Fortnight -->
            <div class="fnm-summary-block mb-3">
              <div class="text-subtitle-2 font-weight-bold">{{ $t('second_fortnight') }}</div>
              <div class="d-flex justify-space-between align-center mt-1">
                <div class="text-caption text-grey">{{ $t('fortnight_amount') }}</div>
                <div class="font-weight-bold text-info">Dh {{ formatNumber(summary.second_fortnight.total) }}</div>
              </div>
              <div class="d-flex justify-space-between align-center">
                <div class="text-caption text-grey">{{ $t('working_days') }}</div>
                <div class="d-flex align-center ga-1">
                  <v-icon size="14">mdi-calendar-check</v-icon>
                  <span>{{ summary.second_fortnight.working_days }}</span>
                </div>
              </div>
            </div>

            <!-- Month Total -->
            <div class="fnm-summary-total pa-3 rounded-lg">
              <div class="text-subtitle-2 font-weight-bold">{{ $t('total_month_amount') }} {{ monthNames[currentMonth] }}</div>
              <div class="text-h6 font-weight-bold text-info mt-1">Dh {{ formatNumber(summary.month_total) }}</div>
            </div>
          </v-card>
        </v-col>

        <!-- ============= RIGHT PANEL - CALENDAR ============= -->
        <v-col cols="12" md="9">
          <v-card variant="outlined" class="rounded-xl" color="info">
            <!-- Calendar Header -->
            <div class="fnm-cal-header">
              <div class="d-flex align-center ga-2">
                <v-btn icon size="small" variant="text" @click="prevMonth"><v-icon>mdi-chevron-left</v-icon></v-btn>
                <v-btn variant="tonal" color="info" size="small" rounded @click="goToday">{{ $t('today') }}</v-btn>
                <v-btn icon size="small" variant="text" @click="nextMonth"><v-icon>mdi-chevron-right</v-icon></v-btn>
              </div>
              <div class="fnm-cal-title">
                {{ monthNames[currentMonth] }} {{ currentYear }} | {{ currentHalf === 1 ? $t('first_fortnight') : $t('second_fortnight') }}
              </div>
              <div class="d-flex align-center ga-1">
                <v-btn
                  :variant="multiSelectMode ? 'flat' : 'outlined'"
                  :color="multiSelectMode ? 'warning' : 'info'"
                  size="small"
                  rounded
                  @click="toggleMultiSelect"
                  :prepend-icon="multiSelectMode ? 'mdi-checkbox-multiple-marked' : 'mdi-checkbox-multiple-blank-outline'"
                >
                  {{ multiSelectMode ? $t('selection_mode_on') : $t('selection_mode') }}
                </v-btn>
                <v-btn-toggle v-model="currentHalf" mandatory variant="outlined" density="compact" color="info" rounded>
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
                        {{ cell.dateStr }} â€” {{ cell.activities.length }} {{ $t('activities') }}
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
      <v-row class="mt-2">
        <v-col cols="12">
          <v-card variant="outlined" class="rounded-xl" color="info">
            <v-card-title class="d-flex align-center justify-space-between pa-4 pb-2">
              <div class="d-flex align-center">
                <v-icon color="info" class="mr-2">mdi-chart-box-outline</v-icon>
                <span class="text-subtitle-1 font-weight-bold">{{ $t('activity_breakdown') }}</span>
              </div>
              <div class="d-flex ga-2">
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
                  v-print="printCfg"
                  color="info"
                  variant="flat"
                  rounded
                  size="small"
                  prepend-icon="mdi-printer"
                >
                  {{ $t('print') }}
                </v-btn>
                <v-btn
                  color="success"
                  variant="flat"
                  rounded
                  size="small"
                  prepend-icon="mdi-microsoft-excel"
                  :loading="exporting"
                  @click="exportToExcel"
                >
                  {{ $t('export_excel') }}
                </v-btn>
              </div>
            </v-card-title>
            <v-card-text class="pa-4 pt-0">
              <!-- Period Toggle + Declaration Checkboxes -->
              <div class="d-flex flex-wrap align-center ga-3 mb-4">
                <!-- Entity toggle (Workforces / Sections / Corporals) -->
                <v-btn-toggle v-model="breakdownTab" mandatory variant="outlined" density="compact" color="#42A5F5" rounded>
                  <v-btn value="workforces" size="small" prepend-icon="mdi-account-multiple">
                    {{ $t('workforces_activities') }}
                  </v-btn>
                  <v-btn value="sections" size="small" prepend-icon="mdi-sitemap-outline">
                    {{ $t('sections_activities') }}
                  </v-btn>
                  <v-btn value="corporals" size="small" prepend-icon="mdi-account-tie">
                    {{ $t('corporals_activities') }}
                  </v-btn>
                </v-btn-toggle>

                <v-divider vertical class="mx-1" style="height:28px;"></v-divider>

                <!-- Period toggle (Q1 / Q2 / Month) -->
                <v-btn-toggle v-model="breakdownPeriod" mandatory variant="outlined" density="compact" color="#42A5F5" rounded>
                  <v-btn value="q1" size="small" prepend-icon="mdi-numeric-1-circle-outline">Q1</v-btn>
                  <v-btn value="q2" size="small" prepend-icon="mdi-numeric-2-circle-outline">Q2</v-btn>
                  <v-btn value="month" size="small" prepend-icon="mdi-calendar-month">{{ $t('month') }}</v-btn>
                </v-btn-toggle>

                <v-divider vertical class="mx-1" style="height:28px;"></v-divider>

                <!-- Declaration checkboxes -->
                <v-checkbox
                  v-model="showDeclared"
                  :label="$t('declared')"
                  density="compact"
                  color="#42A5F5"
                  hide-details
                  class="flex-grow-0"
                ></v-checkbox>
                <v-checkbox
                  v-model="showNonDeclared"
                  :label="$t('non_declared')"
                  density="compact"
                  color="warning"
                  hide-details
                  class="flex-grow-0"
                ></v-checkbox>
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
                      <v-chip size="x-small" color="info" variant="tonal" class="mr-1" v-if="activeWfFilterCount">
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
                          <span class="fnm-th-label" @click.stop="toggleWfSort(col.key)" :title="$t('sort_asc')">
                            {{ $t(col.label) }}
                            <span v-if="getWfSortDir(col.key)" class="fnm-sort-indicator">
                              <v-icon size="14" :color="col.headerColor || '#42A5F5'">
                                {{ getWfSortDir(col.key) === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down' }}
                              </v-icon>
                              <span v-if="wfSortRules.length > 1" class="fnm-sort-badge">{{ getWfSortIndex(col.key) }}</span>
                            </span>
                          </span>
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
                        <!-- Chip (name) -->
                        <v-chip v-if="col.type === 'chip'" size="small" color="#42A5F5" variant="tonal" label>
                          <v-icon start size="14">mdi-account</v-icon>
                          {{ w[col.key] }}
                        </v-chip>
                        <!-- Boolean icon -->
                        <v-icon v-else-if="col.type === 'boolean'" :color="w[col.key] ? (col.color || 'success') : 'grey'" size="18">
                          {{ w[col.key] ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}
                        </v-icon>
                        <!-- Computed declared/non-declared -->
                        <template v-else-if="col.type === 'computed_declared'">{{ formatNumber(getDeclared(w)) }}</template>
                        <template v-else-if="col.type === 'computed_non_declared'">{{ formatNumber(getNonDeclared(w)) }}</template>
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

              <!-- Grand Total -->
              <div class="mt-4 pa-3 rounded-lg" style="background: linear-gradient(135deg, #E3F2FD 0%, #F3E5F5 100%);">
                <v-row>
                  <v-col cols="4" class="text-center">
                    <div class="text-caption text-grey">{{ $t('first_fortnight') }}</div>
                    <div class="text-h6 font-weight-bold text-info">{{ formatNumber(grandTotals.q1_total) }} {{ main.currency || 'DH' }}</div>
                  </v-col>
                  <v-col cols="4" class="text-center">
                    <div class="text-caption text-grey">{{ $t('second_fortnight') }}</div>
                    <div class="text-h6 font-weight-bold text-info">{{ formatNumber(grandTotals.q2_total) }} {{ main.currency || 'DH' }}</div>
                  </v-col>
                  <v-col cols="4" class="text-center">
                    <div class="text-caption text-grey">{{ $t('total_month') }}</div>
                    <div class="text-h6 font-weight-bold" style="color: #7B1FA2;">{{ formatNumber(grandTotals.month_total) }} {{ main.currency || 'DH' }}</div>
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
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-pencil</v-icon>
          {{ $t('edit_activity') }}
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
                density="compact" variant="outlined" rounded color="info" class="text-info"
                clearable
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
                density="compact" variant="outlined" rounded color="info" class="text-info"
                clearable
                :disabled="!!editForm.section_id"
                @update:model-value="onEditCorporalChange"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model.number="editForm.price"
                :label="$t('price')"
                density="compact" variant="outlined" rounded color="info" class="text-info"
                type="number" min="0" step="0.01"
                :readonly="editPriceReadonly"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="editDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveEditActivity" :loading="editSaving">{{ $t('save') }}</v-btn>
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
          <v-chip size="small" color="info" variant="flat" prepend-icon="mdi-account-group">
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
            <v-progress-circular indeterminate color="info" />
          </div>
          <div v-else-if="!dayDetailActivities.length" class="text-center pa-8 text-grey">
            <v-icon size="48" color="grey-lighten-1">mdi-calendar-blank</v-icon>
            <div class="mt-2">{{ $t('no_activities_found') }}</div>
          </div>
          <v-list v-else lines="three" class="pa-2">
            <template v-for="(act, idx) in dayDetailActivities" :key="act.id">
              <v-list-item class="fnm-dd-item rounded-lg mb-1" :ripple="false">
                <template #prepend>
                  <v-avatar size="38" :color="act.section ? 'info' : 'purple'" class="mr-3">
                    <span class="text-white text-caption font-weight-bold">{{ getWorkforceInitials(act.workforce) }}</span>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-bold text-body-2">
                  {{ act.workforce ? act.workforce.full_name : 'â€”' }}
                  <v-chip v-if="act.workforce" size="x-small" variant="outlined" color="grey" class="ml-1">{{ act.workforce.matricule }}</v-chip>
                </v-list-item-title>
                <v-list-item-subtitle class="d-flex align-center ga-2 mt-1">
                  <v-chip v-if="act.section" size="x-small" color="info" variant="tonal" prepend-icon="mdi-sitemap-outline">
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
                    <v-btn icon size="x-small" variant="text" color="info" @click="editActivityFromDetail(act)">
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
    <v-dialog v-model="colSettingsDialog" max-width="460" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-table-cog</v-icon>
          {{ $t('configure_columns') }}
          <v-spacer></v-spacer>
          <v-btn icon size="x-small" variant="text" @click="colSettingsDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-card-text class="pa-5 pt-2">
          <div class="d-flex ga-2 mb-3">
            <v-btn size="x-small" variant="tonal" color="info" rounded @click="toggleAllColumns(true)" prepend-icon="mdi-eye">{{ $t('show_all') }}</v-btn>
            <v-btn size="x-small" variant="tonal" color="warning" rounded @click="toggleAllColumns(false)" prepend-icon="mdi-eye-off">{{ $t('hide_all') }}</v-btn>
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

  <!-- ============= HIDDEN PRINT AREA ============= -->
  <div style="display:none">
    <div id="fnm-print-area">
      <div style="padding:30px 40px;max-width:900px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a1a">
        <!-- Logo + Company -->
        <div style="text-align:center;margin-bottom:16px">
          <img :src="printLogoUrl" alt="Logo" style="height:65px;margin-bottom:4px" />
        </div>
        <!-- Title -->
        <div style="text-align:center;margin-bottom:12px">
          <div style="display:inline-block;border:2px solid #333;padding:6px 28px;font-size:14px;font-weight:700;letter-spacing:1px">{{ $t('activity_breakdown') }}</div>
        </div>
        <!-- Context row -->
        <div style="display:flex;justify-content:space-between;margin-bottom:18px;font-size:12px;color:#555">
          <div><b>{{ $t('month') }}:</b> {{ monthNames[currentMonth] }} {{ currentYear }}</div>
          <div><b>{{ $t('period') }}:</b> {{ printPeriodLabel }}</div>
          <div><b>{{ $t('tab') }}:</b> {{ printTabLabel }}</div>
          <div v-if="drilldownItem"><b>{{ printTabLabel }}:</b> {{ drilldownItem.name }}</div>
        </div>

        <!-- ==== WORKFORCES SUMMARY ==== -->
        <table v-if="breakdownTab === 'workforces' && !drilldownItem" style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr style="background:#E3F2FD">
              <th v-for="col in visibleWorkforceColumns" :key="'pth-'+col.key"
                :style="'padding:8px 10px;border:1px solid #ccc;' + (col.type === 'chip' ? 'text-align:left' : 'text-align:center') + (col.headerColor ? ';color:'+col.headerColor : '')"
              >{{ $t(col.label) }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="w in (sortedFilteredWorkforces || breakdownData.workforces)" :key="'pw-'+w.id">
              <td v-for="col in visibleWorkforceColumns" :key="'ptd-'+col.key+'-'+w.id"
                :style="'padding:6px 10px;border:1px solid #ddd;' + (col.type !== 'chip' ? 'text-align:center;' : '') + (col.cellColor ? 'font-weight:700;color:'+col.cellColor+';' : '') + (col.bold ? 'font-weight:700;color:#1565C0;' : '')"
              >
                <template v-if="col.type === 'boolean'">{{ w[col.key] ? $t('yes') : $t('no') }}</template>
                <template v-else-if="col.type === 'computed_declared'">{{ formatNumber(getDeclared(w)) }}</template>
                <template v-else-if="col.type === 'computed_non_declared'">{{ formatNumber(getNonDeclared(w)) }}</template>
                <template v-else-if="col.type === 'currency'">{{ formatNumber(w[col.key]) }}</template>
                <template v-else>{{ w[col.key] || '-' }}</template>
              </td>
            </tr>
            <tr style="background:#E3F2FD;font-weight:700">
              <td style="padding:8px 10px;border:1px solid #ccc" :colspan="totalColspan">{{ $t('total') }}</td>
              <template v-for="col in visibleWorkforceColumns" :key="'ptot-'+col.key">
                <td v-if="col.period || col.type === 'computed_declared' || col.type === 'computed_non_declared'"
                  :style="'padding:8px 10px;text-align:center;border:1px solid #ccc;' + (col.cellColor ? 'color:'+col.cellColor+';' : '') + (col.bold ? 'color:#1565C0;' : '')"
                >
                  <template v-if="col.key === 'q1_work_days'">{{ workforceTotals.q1_work_days }}</template>
                  <template v-else-if="col.key === 'q1_non_work_days'">{{ workforceTotals.q1_non_work_days }}</template>
                  <template v-else-if="col.key === 'q1_total'">{{ formatNumber(workforceTotals.q1_total) }}</template>
                  <template v-else-if="col.key === 'q2_work_days'">{{ workforceTotals.q2_work_days }}</template>
                  <template v-else-if="col.key === 'q2_non_work_days'">{{ workforceTotals.q2_non_work_days }}</template>
                  <template v-else-if="col.key === 'q2_total'">{{ formatNumber(workforceTotals.q2_total) }}</template>
                  <template v-else-if="col.key === 'month_total'">{{ formatNumber(workforceTotals.month_total) }}</template>
                  <template v-else-if="col.type === 'computed_declared'">{{ formatNumber(workforceTotals.declared) }}</template>
                  <template v-else-if="col.type === 'computed_non_declared'">{{ formatNumber(workforceTotals.non_declared) }}</template>
                </td>
              </template>
            </tr>
          </tbody>
        </table>

        <!-- ==== WORKFORCES DRILLDOWN ==== -->
        <table v-if="breakdownTab === 'workforces' && drilldownItem" style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr style="background:#E3F2FD">
              <th style="padding:8px 10px;text-align:left;border:1px solid #ccc">{{ $t('date') }}</th>
              <th style="padding:8px 10px;text-align:left;border:1px solid #ccc">{{ $t('section') }} / {{ $t('corporal') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ $t('price') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="act in drilldownItem.activities" :key="'pa-'+act.id">
              <td style="padding:6px 10px;border:1px solid #ddd">{{ main.formateDate(act.date) }}</td>
              <td style="padding:6px 10px;border:1px solid #ddd">{{ act.section_name || act.corporal_name || '-' }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd;font-weight:700">{{ formatNumber(act.price) }} {{ main.currency || 'DH' }}</td>
            </tr>
          </tbody>
        </table>

        <!-- ==== SECTIONS SUMMARY ==== -->
        <table v-if="breakdownTab === 'sections' && !drilldownItem" style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr style="background:#FFF3E0">
              <th style="padding:8px 10px;text-align:left;border:1px solid #ccc">{{ $t('name') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q1 {{ $t('count') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q1 {{ $t('total') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q2 {{ $t('count') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q2 {{ $t('total') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc;font-weight:700">{{ $t('month_total') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in breakdownData.sections" :key="'ps-'+s.id">
              <td style="padding:6px 10px;border:1px solid #ddd">{{ s.name }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ s.q1_count }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ formatNumber(s.q1_total) }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ s.q2_count }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ formatNumber(s.q2_total) }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd;font-weight:700;color:#E65100">{{ formatNumber(s.month_total) }}</td>
            </tr>
            <tr style="background:#FFF3E0;font-weight:700">
              <td style="padding:8px 10px;border:1px solid #ccc">{{ $t('total') }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ sectionTotals.q1_count }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ formatNumber(sectionTotals.q1_total) }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ sectionTotals.q2_count }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ formatNumber(sectionTotals.q2_total) }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc;color:#E65100">{{ formatNumber(sectionTotals.month_total) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- ==== SECTIONS/CORPORALS DRILLDOWN (workforces list) ==== -->
        <table v-if="(breakdownTab === 'sections' || breakdownTab === 'corporals') && drilldownItem && drilldownItem.workforces" style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr :style="{background: breakdownTab==='sections'?'#FFF3E0':'#F3E5F5'}">
              <th style="padding:8px 10px;text-align:left;border:1px solid #ccc">{{ $t('full_name') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ $t('matricule') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ $t('nationality') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ $t('sex') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ $t('is_migrant') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q1 {{ $t('count') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q1 {{ $t('total') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q2 {{ $t('count') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q2 {{ $t('total') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc;font-weight:700">{{ $t('month_total') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="wf in drilldownItem.workforces" :key="'pdw-'+wf.id">
              <td style="padding:6px 10px;border:1px solid #ddd">{{ wf.name }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ wf.matricule }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ wf.nationality }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ $t(wf.sex) }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ wf.is_migrant ? $t('yes') : $t('no') }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ wf.q1_count }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ formatNumber(wf.q1_total) }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ wf.q2_count }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ formatNumber(wf.q2_total) }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd;font-weight:700;color:#1565C0">{{ formatNumber(wf.month_total) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- ==== CORPORALS SUMMARY ==== -->
        <table v-if="breakdownTab === 'corporals' && !drilldownItem" style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr style="background:#F3E5F5">
              <th style="padding:8px 10px;text-align:left;border:1px solid #ccc">{{ $t('name') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q1 {{ $t('count') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q1 {{ $t('total') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q2 {{ $t('count') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc">Q2 {{ $t('total') }}</th>
              <th style="padding:8px 10px;text-align:center;border:1px solid #ccc;font-weight:700">{{ $t('month_total') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in breakdownData.corporals" :key="'pc-'+c.id">
              <td style="padding:6px 10px;border:1px solid #ddd">{{ c.name }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ c.q1_count }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ formatNumber(c.q1_total) }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ c.q2_count }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd">{{ formatNumber(c.q2_total) }}</td>
              <td style="padding:6px 10px;text-align:center;border:1px solid #ddd;font-weight:700;color:#7B1FA2">{{ formatNumber(c.month_total) }}</td>
            </tr>
            <tr style="background:#F3E5F5;font-weight:700">
              <td style="padding:8px 10px;border:1px solid #ccc">{{ $t('total') }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ corporalTotals.q1_count }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ formatNumber(corporalTotals.q1_total) }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ corporalTotals.q2_count }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc">{{ formatNumber(corporalTotals.q2_total) }}</td>
              <td style="padding:8px 10px;text-align:center;border:1px solid #ccc;color:#7B1FA2">{{ formatNumber(corporalTotals.month_total) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Grand Total footer -->
        <div style="margin-top:20px;display:flex;justify-content:space-around;padding:12px;border:2px solid #1565C0;border-radius:8px">
          <div style="text-align:center"><div style="font-size:11px;color:#888">{{ $t('first_fortnight') }}</div><div style="font-size:16px;font-weight:800;color:#1565C0">{{ formatNumber(grandTotals.q1_total) }} {{ main.currency || 'DH' }}</div></div>
          <div style="text-align:center"><div style="font-size:11px;color:#888">{{ $t('second_fortnight') }}</div><div style="font-size:16px;font-weight:800;color:#1565C0">{{ formatNumber(grandTotals.q2_total) }} {{ main.currency || 'DH' }}</div></div>
          <div style="text-align:center"><div style="font-size:11px;color:#888">{{ $t('total_month') }}</div><div style="font-size:16px;font-weight:800;color:#7B1FA2">{{ formatNumber(grandTotals.month_total) }} {{ main.currency || 'DH' }}</div></div>
        </div>

        <!-- Footer -->
        <div v-if="companySettings.company_name" style="text-align:center;margin-top:20px;border-top:1px solid #ccc;padding-top:8px;font-size:10px;color:#888">
          <div>{{ companySettings.company_name }} - {{ companySettings.company_address || '' }}</div>
          <div>
            <span v-if="companySettings.rc">RC: {{ companySettings.rc }}</span>
            <span v-if="companySettings.ice"> | ICE: {{ companySettings.ice }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
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

export default {
  name: 'FortnightManagement',
  directives: { print },
  data() {
    const now = new Date();
    return {
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
      const wfs = this.breakdownData.workforces || [];
      if (this.showDeclared && this.showNonDeclared) return wfs;
      if (!this.showDeclared && !this.showNonDeclared) return [];
      if (this.showDeclared) return wfs.filter(w => w.salary_statement === true);
      if (this.showNonDeclared) return wfs.filter(w => w.salary_statement === false);
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
    printCfg() { return { id: 'fnm-print-area', popTitle: this.$t('activity_breakdown') }; },
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
    if (this._onModelChanged) {
      mitt.off('model-changed', this._onModelChanged);
    }
  },
  methods: {
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
    colDragEnd() { this.colDragIdx = null; },
    toggleAllColumns(val) {
      this.allWorkforceColumns.forEach(c => { if (!c.locked) c.visible = val; });
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
      const name = wf.full_name || '';
      const parts = name.trim().split(/\s+/);
      if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
      return name.substring(0, 2).toUpperCase();
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
.fnm-hero { background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.fnm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.fnm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.fnm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }

/* Summary */
.fnm-summary-block { padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(var(--v-border-color), .1); }
.fnm-summary-total { background: linear-gradient(135deg, rgba(33,150,243,.06), rgba(33,150,243,.12)); border: 1px solid rgba(33,150,243,.2); }

/* Calendar Header */
.fnm-cal-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid rgba(var(--v-border-color), .1); }
.fnm-cal-title { font-size: 16px; font-weight: 700; }

/* Calendar Grid */
.fnm-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
.fnm-cal-dow { padding: 10px 4px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; opacity: .5; border-bottom: 1px solid rgba(var(--v-border-color), .08); }

/* Calendar Cells */
.fnm-cal-cell { min-height: 90px; border: 1px solid rgba(var(--v-border-color), .06); padding: 4px; position: relative; cursor: pointer; transition: background .15s ease; }
.fnm-cal-cell:hover { background: rgba(33,150,243,.03); }
.fnm-cal-cell--empty { background: transparent; cursor: default; }
.fnm-cal-cell--outside { opacity: .3; cursor: default; }
.fnm-cal-cell--future { opacity: .35; cursor: not-allowed; pointer-events: none; background: repeating-linear-gradient(135deg, transparent, transparent 4px, rgba(0,0,0,.03) 4px, rgba(0,0,0,.03) 8px) !important; }
.fnm-cal-cell--today { background: rgba(33,150,243,.06) !important; }
.fnm-cal-cell--multi { background: rgba(255, 235, 59, .12) !important; }
.fnm-cal-cell--active-half { }

/* Day header: number + delete button */
.fnm-cal-day-header { display: flex; align-items: center; justify-content: space-between; }
.fnm-cal-day-num { font-size: 12px; font-weight: 600; padding: 2px 6px; display: inline-block; border-radius: 8px; }
.fnm-cal-day-selected { background: #2196F3; color: #fff; }
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
</style>
