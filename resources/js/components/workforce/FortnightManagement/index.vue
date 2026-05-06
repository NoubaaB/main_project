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
        <v-col cols="12" md="4">
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
        <v-col cols="12" md="8">
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
                  'fnm-cal-cell--active-half': cell.inActiveHalf
                }"
                @click="cell.day && !cell.isOutside && !cell.isFuture ? selectDate(cell) : null"
              >
                <div v-if="cell.day" class="fnm-cal-day-num" :class="{ 'fnm-cal-day-selected': isSelectedDate(cell) }">
                  {{ cell.day }}
                  <span v-if="cell.monthLabel" class="fnm-cal-month-label">{{ cell.monthLabel }}</span>
                </div>

                <!-- Activities in cell -->
                <div v-if="cell.activities && cell.activities.length" class="fnm-cal-activities">
                  <div
                    v-for="act in cell.activities.slice(0, 4)"
                    :key="act.id"
                    class="fnm-cal-activity"
                    :class="act.section_id ? 'fnm-act-section' : 'fnm-act-corporal'"
                    @click.stop="editActivity(act)"
                  >
                    <span v-if="multipleSelected && act.workforce" class="fnm-act-wf" :title="act.workforce.full_name">
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
              </div>
            </div>
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
            </v-card-title>
            <v-card-text class="pa-4 pt-0">
              <!-- Toggle buttons -->
              <v-btn-toggle v-model="breakdownTab" mandatory variant="outlined" density="compact" color="info" rounded class="mb-4">
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
                <v-table v-if="!drilldownItem" density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('name') }}</th>
                      <th class="text-center">{{ $t('q1_count') }}</th>
                      <th class="text-center">{{ $t('q1_total') }}</th>
                      <th class="text-center">{{ $t('q2_count') }}</th>
                      <th class="text-center">{{ $t('q2_total') }}</th>
                      <th class="text-center font-weight-bold">{{ $t('month_total') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="w in breakdownData.workforces" :key="'w-' + w.id" class="fnm-clickable-row" @click="drilldownItem = w">
                      <td>
                        <v-chip size="small" color="info" variant="tonal" label>
                          <v-icon start size="14">mdi-account</v-icon>
                          {{ w.name }}
                        </v-chip>
                      </td>
                      <td class="text-center">{{ w.q1_count }}</td>
                      <td class="text-center">{{ formatNumber(w.q1_total) }}</td>
                      <td class="text-center">{{ w.q2_count }}</td>
                      <td class="text-center">{{ formatNumber(w.q2_total) }}</td>
                      <td class="text-center font-weight-bold text-info">{{ formatNumber(w.month_total) }}</td>
                    </tr>
                    <tr class="fnm-breakdown-total-row">
                      <td class="font-weight-bold">{{ $t('total') }}</td>
                      <td class="text-center font-weight-bold">{{ workforceTotals.q1_count }}</td>
                      <td class="text-center font-weight-bold">{{ formatNumber(workforceTotals.q1_total) }}</td>
                      <td class="text-center font-weight-bold">{{ workforceTotals.q2_count }}</td>
                      <td class="text-center font-weight-bold">{{ formatNumber(workforceTotals.q2_total) }}</td>
                      <td class="text-center font-weight-bold text-info">{{ formatNumber(workforceTotals.month_total) }}</td>
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
                    </tr>
                  </tbody>
                </v-table>
              </template>

              <!-- ======= SECTIONS TAB ======= -->
              <template v-if="breakdownTab === 'sections'">
                <!-- Summary table -->
                <v-table v-if="!drilldownItem" density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('name') }}</th>
                      <th class="text-center">{{ $t('q1_count') }}</th>
                      <th class="text-center">{{ $t('q1_total') }}</th>
                      <th class="text-center">{{ $t('q2_count') }}</th>
                      <th class="text-center">{{ $t('q2_total') }}</th>
                      <th class="text-center font-weight-bold">{{ $t('month_total') }}</th>
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
                      <td class="text-center">{{ s.q1_count }}</td>
                      <td class="text-center">{{ formatNumber(s.q1_total) }}</td>
                      <td class="text-center">{{ s.q2_count }}</td>
                      <td class="text-center">{{ formatNumber(s.q2_total) }}</td>
                      <td class="text-center font-weight-bold text-orange">{{ formatNumber(s.month_total) }}</td>
                    </tr>
                    <tr class="fnm-breakdown-total-row">
                      <td class="font-weight-bold">{{ $t('total') }}</td>
                      <td class="text-center font-weight-bold">{{ sectionTotals.q1_count }}</td>
                      <td class="text-center font-weight-bold">{{ formatNumber(sectionTotals.q1_total) }}</td>
                      <td class="text-center font-weight-bold">{{ sectionTotals.q2_count }}</td>
                      <td class="text-center font-weight-bold">{{ formatNumber(sectionTotals.q2_total) }}</td>
                      <td class="text-center font-weight-bold text-orange">{{ formatNumber(sectionTotals.month_total) }}</td>
                    </tr>
                  </tbody>
                </v-table>

                <!-- Drill-down: workforces in selected section -->
                <v-table v-else density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('full_name') }}</th>
                      <th class="text-center">{{ $t('matricule') }}</th>
                      <th class="text-center">{{ $t('nationality') }}</th>
                      <th class="text-center">{{ $t('sex') }}</th>
                      <th class="text-center">{{ $t('is_migrant') }}</th>
                      <th class="text-center">{{ $t('q1_count') }}</th>
                      <th class="text-center">{{ $t('q1_total') }}</th>
                      <th class="text-center">{{ $t('q2_count') }}</th>
                      <th class="text-center">{{ $t('q2_total') }}</th>
                      <th class="text-center font-weight-bold">{{ $t('month_total') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="wf in drilldownItem.workforces" :key="'sw-' + wf.id">
                      <td>
                        <v-chip size="small" color="info" variant="tonal" label>
                          <v-icon start size="14">mdi-account</v-icon>
                          {{ wf.name }}
                        </v-chip>
                      </td>
                      <td class="text-center">{{ wf.matricule }}</td>
                      <td class="text-center">{{ wf.nationality }}</td>
                      <td class="text-center">
                        <v-chip :color="wf.sex === 'male' ? 'blue' : 'pink'" size="x-small" variant="tonal">
                          <v-icon size="12" class="mr-1">{{ wf.sex === 'male' ? 'mdi-gender-male' : 'mdi-gender-female' }}</v-icon>
                          {{ $t(wf.sex) }}
                        </v-chip>
                      </td>
                      <td class="text-center">
                        <v-icon :color="wf.is_migrant ? 'warning' : 'grey'" size="18">{{ wf.is_migrant ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                      </td>
                      <td class="text-center">{{ wf.q1_count }}</td>
                      <td class="text-center">{{ formatNumber(wf.q1_total) }}</td>
                      <td class="text-center">{{ wf.q2_count }}</td>
                      <td class="text-center">{{ formatNumber(wf.q2_total) }}</td>
                      <td class="text-center font-weight-bold text-info">{{ formatNumber(wf.month_total) }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </template>

              <!-- ======= CORPORALS TAB ======= -->
              <template v-if="breakdownTab === 'corporals'">
                <!-- Summary table -->
                <v-table v-if="!drilldownItem" density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('name') }}</th>
                      <th class="text-center">{{ $t('q1_count') }}</th>
                      <th class="text-center">{{ $t('q1_total') }}</th>
                      <th class="text-center">{{ $t('q2_count') }}</th>
                      <th class="text-center">{{ $t('q2_total') }}</th>
                      <th class="text-center font-weight-bold">{{ $t('month_total') }}</th>
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
                      <td class="text-center">{{ c.q1_count }}</td>
                      <td class="text-center">{{ formatNumber(c.q1_total) }}</td>
                      <td class="text-center">{{ c.q2_count }}</td>
                      <td class="text-center">{{ formatNumber(c.q2_total) }}</td>
                      <td class="text-center font-weight-bold text-purple">{{ formatNumber(c.month_total) }}</td>
                    </tr>
                    <tr class="fnm-breakdown-total-row">
                      <td class="font-weight-bold">{{ $t('total') }}</td>
                      <td class="text-center font-weight-bold">{{ corporalTotals.q1_count }}</td>
                      <td class="text-center font-weight-bold">{{ formatNumber(corporalTotals.q1_total) }}</td>
                      <td class="text-center font-weight-bold">{{ corporalTotals.q2_count }}</td>
                      <td class="text-center font-weight-bold">{{ formatNumber(corporalTotals.q2_total) }}</td>
                      <td class="text-center font-weight-bold text-purple">{{ formatNumber(corporalTotals.month_total) }}</td>
                    </tr>
                  </tbody>
                </v-table>

                <!-- Drill-down: workforces in selected corporal -->
                <v-table v-else density="compact" class="rounded-lg fnm-breakdown-table">
                  <thead>
                    <tr>
                      <th>{{ $t('full_name') }}</th>
                      <th class="text-center">{{ $t('matricule') }}</th>
                      <th class="text-center">{{ $t('nationality') }}</th>
                      <th class="text-center">{{ $t('sex') }}</th>
                      <th class="text-center">{{ $t('is_migrant') }}</th>
                      <th class="text-center">{{ $t('q1_count') }}</th>
                      <th class="text-center">{{ $t('q1_total') }}</th>
                      <th class="text-center">{{ $t('q2_count') }}</th>
                      <th class="text-center">{{ $t('q2_total') }}</th>
                      <th class="text-center font-weight-bold">{{ $t('month_total') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="wf in drilldownItem.workforces" :key="'cw-' + wf.id">
                      <td>
                        <v-chip size="small" color="info" variant="tonal" label>
                          <v-icon start size="14">mdi-account</v-icon>
                          {{ wf.name }}
                        </v-chip>
                      </td>
                      <td class="text-center">{{ wf.matricule }}</td>
                      <td class="text-center">{{ wf.nationality }}</td>
                      <td class="text-center">
                        <v-chip :color="wf.sex === 'male' ? 'blue' : 'pink'" size="x-small" variant="tonal">
                          <v-icon size="12" class="mr-1">{{ wf.sex === 'male' ? 'mdi-gender-male' : 'mdi-gender-female' }}</v-icon>
                          {{ $t(wf.sex) }}
                        </v-chip>
                      </td>
                      <td class="text-center">
                        <v-icon :color="wf.is_migrant ? 'warning' : 'grey'" size="18">{{ wf.is_migrant ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                      </td>
                      <td class="text-center">{{ wf.q1_count }}</td>
                      <td class="text-center">{{ formatNumber(wf.q1_total) }}</td>
                      <td class="text-center">{{ wf.q2_count }}</td>
                      <td class="text-center">{{ formatNumber(wf.q2_total) }}</td>
                      <td class="text-center font-weight-bold text-info">{{ formatNumber(wf.month_total) }}</td>
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
  </v-card>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useWorkforce } from '@/stores/Workforce';
import { useCorporal } from '@/stores/Corporal';
import { useSection } from '@/stores/Section';
import { useFortnightActivity } from '@/stores/FortnightActivity';
import { useNotification } from '@/stores/Notification';
import mitt from '@/plugins/mitt.js';

export default {
  name: 'FortnightManagement',
  data() {
    const now = new Date();
    return {
      currentYear: now.getFullYear(),
      currentMonth: now.getMonth(),
      currentHalf: now.getDate() <= 15 ? 1 : 2,
      selectedWorkforceIds: [],
      selectedDate: this.formatDateStr(now),
      activityForm: { section_id: null, corporal_id: null, price: 0 },
      submitting: false,
      deleting: false,
      exporting: false,
      editDialog: false,
      editForm: { id: null, section_id: null, corporal_id: null, price: 0 },
      editSaving: false,
      breakdownTab: 'workforces',
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
    };
  },
  computed: {
    main() { return useMain(); },
    workforceStore() { return useWorkforce(); },
    corporalStore() { return useCorporal(); },
    sectionStore() { return useSection(); },
    activityStore() { return useFortnightActivity(); },
    notification() { return useNotification(); },
    summary() { return this.activityStore.summary; },
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
    /** Whether multiple workforces are selected - used to show workforce name on calendar chips */
    multipleSelected() {
      return this.selectedWorkforceIds && this.selectedWorkforceIds.length > 1;
    },
    breakdownData() { return this.activityStore.breakdown; },
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
      const wfs = this.breakdownData.workforces || [];
      return {
        q1_count: wfs.reduce((s, r) => s + r.q1_count, 0),
        q1_total: wfs.reduce((s, r) => s + r.q1_total, 0),
        q2_count: wfs.reduce((s, r) => s + r.q2_count, 0),
        q2_total: wfs.reduce((s, r) => s + r.q2_total, 0),
        month_total: wfs.reduce((s, r) => s + r.month_total, 0),
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
    /** Build calendar cells for the current month */
    calendarCells() {
      const cells = [];
      const year = this.currentYear;
      const month = this.currentMonth;
      const firstDay = new Date(year, month, 1).getDay(); // 0=Sun
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const daysInPrevMonth = new Date(year, month, 0).getDate();
      const activitiesByDate = this.activityStore.activitiesByDate;
      const today = new Date();

      // Previous month fill
      for (let i = firstDay - 1; i >= 0; i--) {
        const d = daysInPrevMonth - i;
        cells.push({ day: d, isOutside: true, monthLabel: null, activities: [], isToday: false, inActiveHalf: false });
      }

      // Current month
      const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate()); // midnight today
      for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const cellDate = new Date(year, month, d);
        const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === d;
        const isFuture = cellDate > todayDate;
        const inActiveHalf = this.currentHalf === 1 ? d <= 15 : d >= 16;
        const dayActivities = activitiesByDate[dateStr] || [];

        cells.push({
          day: d,
          dateStr,
          isOutside: false,
          isToday,
          isFuture,
          inActiveHalf,
          activities: dayActivities,
          monthLabel: d === 1 ? this.monthNames[month]?.substring(0, 3) : null,
        });
      }

      // Next month fill to complete grid
      const remaining = (7 - (cells.length % 7)) % 7;
      for (let i = 1; i <= remaining; i++) {
        cells.push({ day: i, isOutside: true, monthLabel: null, activities: [], isToday: false, inActiveHalf: false });
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
    this.loadSummary();
    this.loadBreakdown();

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
    selectDate(cell) {
      if (cell.dateStr) this.selectedDate = cell.dateStr;
      if (cell.day) this.currentHalf = cell.day <= 15 ? 1 : 2;
    },
    isSelectedDate(cell) {
      return cell.dateStr === this.selectedDate;
    },
    getActivityLabel(act) {
      if (act.section) return act.section.name;
      if (act.corporal) return act.corporal.full_name;
      return '';
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
        this.activityForm.price = 0;
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
        this.editForm.price = 0;
      }
    },
    onWorkforceChange() {
      // loadActivities triggered by watcher on selectedWorkforceIds
    },
    async loadActivities() {
      if (!this.selectedWorkforceIds || !this.selectedWorkforceIds.length) {
        // Clear calendar activities when no workforce is selected
        this.activityStore.activities = [];
        return;
      }

      // Fetch activities for ALL selected workforces for the full month
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
          // -- Workforces summary table --
          sheetName = this.$t('workforces_activities');
          const headers = [
            this.$t('name'), this.$t('matricule'),
            `Q1 ${this.$t('count')}`, `Q1 ${this.$t('total')} (${currency})`,
            `Q2 ${this.$t('count')}`, `Q2 ${this.$t('total')} (${currency})`,
            `${this.$t('month_total')} (${currency})`
          ];
          const data = (bd.workforces || []).map(w => [
            w.name, w.matricule, w.q1_count, w.q1_total, w.q2_count, w.q2_total, w.month_total
          ]);
          data.push([]);
          data.push([this.$t('total'), '', this.workforceTotals.q1_count, this.workforceTotals.q1_total, this.workforceTotals.q2_count, this.workforceTotals.q2_total, this.workforceTotals.month_total]);
          const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
          ws['!cols'] = [{ wch: 24 }, { wch: 14 }, { wch: 10 }, { wch: 16 }, { wch: 10 }, { wch: 16 }, { wch: 18 }];
          XLSX.utils.book_append_sheet(wb, ws, sheetName);
          fileName = `Fortnight_Workforces_${monthName}_${this.currentYear}.xlsx`;

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
          fileName = `Fortnight_Sections_${monthName}_${this.currentYear}.xlsx`;

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
          fileName = `Fortnight_Section_${drill.name}_${monthName}_${this.currentYear}.xlsx`;

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
          fileName = `Fortnight_Corporals_${monthName}_${this.currentYear}.xlsx`;

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
          fileName = `Fortnight_Corporal_${drill.name}_${monthName}_${this.currentYear}.xlsx`;
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

      try {
        // Create activity for each selected workforce (silent - no individual snackbars)
        for (const wId of ids) {
          await this.activityStore.create({
            workforce_id: wId,
            date: this.selectedDate,
            section_id: this.activityForm.section_id || null,
            corporal_id: this.activityForm.corporal_id || null,
            price: priceVal,
          }, true);
        }

        // Rich notification like AddCharge
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

.fnm-cal-day-num { font-size: 12px; font-weight: 600; padding: 2px 6px; display: inline-block; border-radius: 8px; }
.fnm-cal-day-selected { background: #2196F3; color: #fff; }
.fnm-cal-month-label { font-size: 9px; font-weight: 700; text-transform: uppercase; opacity: .5; margin-left: 4px; }

/* Activities inside cells */
.fnm-cal-activities { display: flex; flex-direction: column; gap: 2px; margin-top: 2px; }
.fnm-cal-activity { display: flex; align-items: center; gap: 3px; padding: 2px 6px; border-radius: 6px; font-size: 10px; position: relative; cursor: pointer; transition: all .15s ease; }
.fnm-cal-activity:hover { filter: brightness(1.1); }
.fnm-cal-activity:hover .fnm-act-delete { opacity: 1; }
.fnm-act-section { background: linear-gradient(135deg, #E65100, #FF9800); color: #fff; }
.fnm-act-corporal { background: linear-gradient(135deg, #0D47A1, #42A5F5); color: #fff; }
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
</style>
