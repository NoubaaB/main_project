<template>
  <v-card class="jupiter-date-card rounded-2xl mb-4" elevation="1">
    <v-card-text class="pa-3 pa-sm-4">
      <!-- Top Control Deck -->
      <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-3">
        <!-- Title & Icon with Duration badge -->
        <div class="d-flex align-center ga-3">
          <div class="date-hero-mini-icon">
            <v-icon size="20" color="#0891B2">mdi-calendar-range</v-icon>
          </div>
          <div>
            <div class="d-flex align-center ga-2 flex-wrap">
              <span class="text-subtitle-2 font-weight-bold text-slate-800">{{ $t('date_range') || 'Date Range' }}</span>
              <v-chip size="x-small" variant="flat" class="duration-chip font-weight-bold">
                {{ daysCount }} {{ $t('days_selected') || 'Days Selected' }}
              </v-chip>
            </div>
            <div class="text-caption text-medium-emphasis mt-0.5 d-none d-sm-block" style="font-size: 11px !important; line-height: 1.4;">
              {{ formatDate(localRange.start) }} &rarr; {{ formatDate(localRange.end) }}
            </div>
          </div>
        </div>

        <!-- Assistant Segmented-Pill Toggle Buttons (6 Presets: 7D, 30D, Month, 90D, This Year, Previous Year) -->
        <div class="d-flex align-center flex-wrap ga-2">
          <SegmentedPillToggle
            v-model="activePreset"
            :items="presetItems"
            color="#0891B2"
            size="small"
            @change="applyPreset"
          />

          <!-- Periodicity Toggle (Daily / Monthly / Yearly) -->
          <SegmentedPillToggle
            :model-value="periodicity"
            :items="periodicityItems"
            color="#0891B2"
            size="small"
            @change="onPeriodicitySelect"
          />

          <v-divider vertical class="mx-1 d-none d-md-block" style="height: 28px;"></v-divider>

          <!-- Toggle Calendar Collapse / Expand -->
          <v-btn
            size="small"
            rounded="pill"
            :variant="calendarExpanded ? 'flat' : 'outlined'"
            :color="calendarExpanded ? '#0891B2' : undefined"
            :class="['custom-cal-btn', { 'text-white': calendarExpanded }]"
            @click="calendarExpanded = !calendarExpanded"
          >
            <v-icon start size="15" class="mr-1">{{ calendarExpanded ? 'mdi-chevron-up' : 'mdi-calendar-edit' }}</v-icon>
            {{ calendarExpanded ? ($t('collapse_calendar') || 'Collapse') : ($t('expand_calendar') || 'Expand') }}
          </v-btn>
        </div>
      </div>

      <!-- AddCharge-style Input Fields for Start & End Dates -->
      <div class="date-inputs-row d-flex align-center flex-wrap ga-3 mb-3">
        <div class="date-input-field flex-grow-1" style="min-width: 170px;">
          <v-text-field
            v-model="startDateString"
            :label="$t('start_date') || 'Start Date'"
            type="date"
            density="compact"
            variant="outlined"
            rounded
            color="#0891B2"
            hide-details
            prepend-inner-icon="mdi-calendar-start"
            class="addcharge-style-field"
            @change="onStartDateInputChange"
          />
        </div>

        <div class="date-input-field flex-grow-1" style="min-width: 170px;">
          <v-text-field
            v-model="endDateString"
            :label="$t('end_date') || 'End Date'"
            type="date"
            density="compact"
            variant="outlined"
            rounded
            color="#0891B2"
            hide-details
            prepend-inner-icon="mdi-calendar-end"
            class="addcharge-style-field"
            @change="onEndDateInputChange"
          />
        </div>
      </div>

      <!-- Extended Full-Width Range VCalendar Area -->
      <v-expand-transition>
        <div v-if="calendarExpanded" class="expanded-calendar-wrap pt-3 border-t">
          <div class="d-flex align-center justify-space-between mb-2 px-1">
            <span class="text-caption font-weight-medium text-medium-emphasis d-flex align-center">
              <v-icon size="15" class="mr-2 text-cyan-darken-2">mdi-cursor-default-click</v-icon>
              {{ $t('dynamic_range_dashboard_hint') || 'Select custom date range on the calendar' }}
            </span>
            <v-chip size="x-small" variant="tonal" color="cyan" class="font-weight-medium">
              <v-icon start size="12">mdi-calendar-sync</v-icon>
              {{ $t('vcalendar_full_width') || 'Full Width Range Calendar' }}
            </v-chip>
          </div>

          <div class="vcalendar-styled-container rounded-xl overflow-hidden pa-2 border">
            <DatePicker
              :is-dark="main.is_dark"
              v-model.range="localRange"
              mode="date"
              :locale="main.lang?.short || 'en'"
              :max-date="new Date()"
              expanded
              color="cyan"
              borderless
              :columns="$vuetify.display.mdAndUp ? 2 : 1"
              class="w-100 jupiter-vcalendar"
            />
          </div>
        </div>
      </v-expand-transition>
    </v-card-text>
  </v-card>
</template>

<script>
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useMain } from '@/stores/Main';
import moment from 'moment';

export default {
  name: 'JupiterDateRange',
  components: { DatePicker, SegmentedPillToggle },
  props: {
    modelValue: {
      type: Object,
      default: () => ({
        start: moment().subtract(30, 'days').toDate(),
        end: new Date(),
      }),
    },
    periodicity: {
      type: String,
      default: 'daily',
    },
  },
  emits: ['update:modelValue', 'update:periodicity'],
  data() {
    return {
      localRange: {
        start: this.modelValue?.start || moment().subtract(30, 'days').toDate(),
        end: this.modelValue?.end || new Date(),
      },
      startDateString: '',
      endDateString: '',
      calendarExpanded: true, // Extended by default as requested
      activePreset: '30d',
    };
  },
  computed: {
    main() { return useMain(); },
    daysCount() {
      if (!this.localRange.start || !this.localRange.end) return 0;
      return Math.max(1, moment(this.localRange.end).diff(moment(this.localRange.start), 'days') + 1);
    },
    presetItems() {
      return [
        { value: '7d', label: this.$t('last_7_days') || '7D' },
        { value: '30d', label: this.$t('last_30_days') || '30D' },
        { value: 'month', label: this.$t('this_month') || 'Month' },
        { value: '90d', label: this.$t('last_90_days') || '90D' },
        { value: 'ytd', label: this.$t('this_year') || 'YTD' },
        { value: 'prev_year', label: this.$t('previous_year') || 'Prev Year' },
      ];
    },
    periodicityItems() {
      return [
        { value: 'daily', label: this.$t('daily') || 'Daily', icon: 'mdi-calendar-today' },
        { value: 'monthly', label: this.$t('monthly') || 'Monthly', icon: 'mdi-calendar-month' },
        { value: 'yearly', label: this.$t('yearly') || 'Yearly', icon: 'mdi-calendar-blank-multiple' },
      ];
    },
  },
  watch: {
    localRange: {
      handler(val) {
        if (!val?.start || !val?.end) return;
        this.syncInputStrings(val);
        if (this.isSameRange(val, this.modelValue)) return;
        this.detectActivePreset(val);
        this.$emit('update:modelValue', { start: val.start, end: val.end });
      },
      deep: true,
    },
    modelValue: {
      handler(val) {
        if (!val?.start || !val?.end) return;
        if (this.isSameRange(val, this.localRange)) return;
        this.localRange = { start: val.start, end: val.end };
        this.syncInputStrings(val);
        this.detectActivePreset(val);
      },
      deep: true,
    },
  },
  mounted() {
    this.syncInputStrings(this.localRange);
    this.detectActivePreset(this.localRange);
  },
  methods: {
    syncInputStrings(range) {
      if (range?.start) {
        this.startDateString = moment(range.start).format('YYYY-MM-DD');
      }
      if (range?.end) {
        this.endDateString = moment(range.end).format('YYYY-MM-DD');
      }
    },
    onStartDateInputChange(e) {
      const val = e?.target?.value || this.startDateString;
      if (!val) return;
      const parsed = moment(val, 'YYYY-MM-DD', true);
      if (parsed.isValid()) {
        const newStart = parsed.startOf('day').toDate();
        this.localRange = {
          start: newStart,
          end: this.localRange.end < newStart ? newStart : this.localRange.end,
        };
      }
    },
    onEndDateInputChange(e) {
      const val = e?.target?.value || this.endDateString;
      if (!val) return;
      const parsed = moment(val, 'YYYY-MM-DD', true);
      if (parsed.isValid()) {
        const newEnd = parsed.endOf('day').toDate();
        this.localRange = {
          start: this.localRange.start > newEnd ? newEnd : this.localRange.start,
          end: newEnd,
        };
      }
    },
    applyPreset(id) {
      this.activePreset = id;
      const today = moment().endOf('day').toDate();
      let start = moment().subtract(30, 'days').startOf('day').toDate();
      let end = today;

      if (id === '7d') {
        start = moment().subtract(6, 'days').startOf('day').toDate();
      } else if (id === '30d') {
        start = moment().subtract(29, 'days').startOf('day').toDate();
      } else if (id === 'month') {
        start = moment().startOf('month').toDate();
      } else if (id === '90d') {
        start = moment().subtract(89, 'days').startOf('day').toDate();
      } else if (id === 'ytd') {
        start = moment().startOf('year').toDate();
      } else if (id === 'prev_year') {
        start = moment().subtract(1, 'year').startOf('year').toDate();
        end = moment().subtract(1, 'year').endOf('year').toDate();
      }

      this.localRange = { start, end };
    },
    detectActivePreset(range) {
      if (!range?.start || !range?.end) return;
      const mStart = moment(range.start);
      const mEnd = moment(range.end);
      const prevYearStart = moment().subtract(1, 'year').startOf('year');
      const prevYearEnd = moment().subtract(1, 'year').endOf('year');

      if (mStart.isSame(prevYearStart, 'day') && mEnd.isSame(prevYearEnd, 'day')) {
        this.activePreset = 'prev_year';
        return;
      }
      const diff = mEnd.diff(mStart, 'days');
      if (diff === 6) this.activePreset = '7d';
      else if (diff === 29 || diff === 30) this.activePreset = '30d';
      else if (diff >= 88 && diff <= 91) this.activePreset = '90d';
      else if (mStart.isSame(moment().startOf('month'), 'day') && mEnd.isSame(moment().endOf('day'), 'day')) this.activePreset = 'month';
      else if (mStart.isSame(moment().startOf('year'), 'day') && mEnd.isSame(moment().endOf('day'), 'day')) this.activePreset = 'ytd';
      else this.activePreset = 'custom';
    },
    isSameRange(a, b) {
      if (!a || !b) return false;
      if (!a.start || !b.start || !a.end || !b.end) return false;
      return moment(a.start).isSame(moment(b.start), 'day')
          && moment(a.end).isSame(moment(b.end), 'day');
    },
    formatDate(d) {
      if (!d) return '—';
      return moment(d).format('DD/MM/YYYY');
    },
    onPeriodicitySelect(val) {
      this.$emit('update:periodicity', val);
    },
  },
};
</script>

<style scoped>
.jupiter-date-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(8, 145, 178, 0.18);
  box-shadow: 0 4px 20px rgba(8, 145, 178, 0.05);
  transition: all 0.25s ease;
}

:deep(.v-theme--dark) .jupiter-date-card,
.v-theme--dark .jupiter-date-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(6, 182, 212, 0.2);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.date-hero-mini-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.15) 0%, rgba(6, 182, 212, 0.08) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(8, 145, 178, 0.2);
}

.duration-chip {
  background: rgba(8, 145, 178, 0.12) !important;
  color: #0891B2 !important;
  font-size: 10.5px !important;
}

.custom-cal-btn {
  font-size: 11px !important;
  font-weight: 600;
  text-transform: none;
  border-color: rgba(8, 145, 178, 0.3) !important;
}

.expanded-calendar-wrap {
  border-top-color: rgba(8, 145, 178, 0.15) !important;
}

.vcalendar-styled-container {
  border-color: rgba(8, 145, 178, 0.18) !important;
}

/* AddCharge Style Input Field Polish */
.addcharge-style-field :deep(.v-field) {
  border-radius: 20px !important;
  background: rgba(255, 255, 255, 0.8) !important;
  font-size: 13px !important;
}

:deep(.v-theme--dark) .addcharge-style-field :deep(.v-field) {
  background: rgba(30, 41, 59, 0.7) !important;
}

/* VCalendar Futuristic Jupiter Cyan Theme Styling */
.expanded-calendar-wrap :deep(.vc-container) {
  border: none !important;
  font-family: inherit;
  background: transparent !important;
  width: 100% !important;
}

.expanded-calendar-wrap :deep(.vc-pane-layout) {
  width: 100% !important;
}

.expanded-calendar-wrap :deep(.vc-header) {
  padding-bottom: 12px !important;
}

.expanded-calendar-wrap :deep(.vc-title) {
  font-weight: 800 !important;
  font-size: 16px !important;
  color: #0891B2 !important;
}

.expanded-calendar-wrap :deep(.vc-dark .vc-title),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-title) {
  color: #22D3EE !important;
}

.expanded-calendar-wrap :deep(.vc-arrow) {
  color: #0891B2 !important;
  border-radius: 8px !important;
  transition: all 0.2s ease !important;
}

.expanded-calendar-wrap :deep(.vc-arrow:hover) {
  background: rgba(8, 145, 178, 0.12) !important;
}

.expanded-calendar-wrap :deep(.vc-dark .vc-arrow),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-arrow) {
  color: #22D3EE !important;
}

.expanded-calendar-wrap :deep(.vc-weekday) {
  font-weight: 700 !important;
  font-size: 12px !important;
  color: #0e7490 !important;
  padding-bottom: 8px !important;
}

.expanded-calendar-wrap :deep(.vc-dark .vc-weekday),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-weekday) {
  color: #67e8f9 !important;
}

/* Day base layout and cell size */
.expanded-calendar-wrap :deep(.vc-day-content) {
  width: 32px !important;
  height: 32px !important;
  line-height: 32px !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  border-radius: 9999px !important;
  transition: all 0.15s ease-in-out !important;
}

.expanded-calendar-wrap :deep(.vc-day-content:hover) {
  background-color: rgba(8, 145, 178, 0.12) !important;
}

/* 1. SELECTED DATES CIRCLE: Start & End */
.expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-bg-solid) {
  width: 32px !important;
  height: 32px !important;
  border-radius: 9999px !important;
  background-color: #0891B2 !important;
  box-shadow: 0 2px 10px rgba(8, 145, 178, 0.5) !important;
  z-index: 2 !important;
}

.expanded-calendar-wrap :deep(.vc-dark .vc-highlight.vc-highlight-bg-solid),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-bg-solid) {
  background-color: #06B6D4 !important;
  box-shadow: 0 2px 12px rgba(6, 182, 212, 0.55) !important;
}

/* 2. TEXT FOR SELECTED START & END DATES: Bold White */
.expanded-calendar-wrap :deep(.vc-highlight-content-solid),
.expanded-calendar-wrap :deep(.vc-day-content.vc-highlight-content-solid) {
  color: #ffffff !important;
  font-weight: 800 !important;
  position: relative !important;
  z-index: 3 !important;
  background-color: transparent !important;
}

.expanded-calendar-wrap :deep(.vc-day-content.vc-highlight-content-solid:hover) {
  background-color: transparent !important;
  color: #ffffff !important;
}

/* 3. CONNECTING RIBBON (Between Start & End): Soft translucent cyan */
.expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-base-start),
.expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-base-end) {
  background-color: rgba(8, 145, 178, 0.15) !important;
  height: 30px !important;
  z-index: 1 !important;
}

.expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-base-middle),
.expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-bg-light) {
  background-color: rgba(8, 145, 178, 0.15) !important;
  height: 30px !important;
  z-index: 1 !important;
}

.expanded-calendar-wrap :deep(.vc-dark .vc-highlight.vc-highlight-base-start),
.expanded-calendar-wrap :deep(.vc-dark .vc-highlight.vc-highlight-base-end),
.expanded-calendar-wrap :deep(.vc-dark .vc-highlight.vc-highlight-base-middle),
.expanded-calendar-wrap :deep(.vc-dark .vc-highlight.vc-highlight-bg-light),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-base-start),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-base-end),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-base-middle),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-highlight.vc-highlight-bg-light) {
  background-color: rgba(6, 182, 212, 0.22) !important;
}

/* 4. TEXT FOR CONNECTED MIDDLE DATES */
.expanded-calendar-wrap :deep(.vc-highlight-content-light),
.expanded-calendar-wrap :deep(.vc-day-content.vc-highlight-content-light) {
  color: #0e7490 !important;
  font-weight: 700 !important;
}

.expanded-calendar-wrap :deep(.vc-dark .vc-highlight-content-light),
.expanded-calendar-wrap :deep(.vc-dark .vc-day-content.vc-highlight-content-light),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-highlight-content-light),
:deep(.v-theme--dark) .expanded-calendar-wrap :deep(.vc-day-content.vc-highlight-content-light) {
  color: #67e8f9 !important;
  font-weight: 700 !important;
}

.expanded-calendar-wrap :deep(.vc-focus:focus-within) {
  box-shadow: 0 0 0 2px rgba(8, 145, 178, 0.4) !important;
}
</style>
