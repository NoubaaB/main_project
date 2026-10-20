<template>
  <v-card class="jupiter-date-card rounded-2xl mb-4" elevation="1">
    <v-card-text class="pa-3 pa-sm-4">
      <!-- Compact Top Control Bar -->
      <div class="d-flex align-center justify-space-between flex-wrap ga-3">
        <!-- Title & Icon with generous spacing and relief -->
        <div class="d-flex align-center ga-3">
          <div class="date-hero-mini-icon">
            <v-icon size="20" color="#0891B2">mdi-calendar-range</v-icon>
          </div>
          <div>
            <div class="d-flex align-center ga-2">
              <span class="text-subtitle-2 font-weight-bold text-slate-800">{{ $t('date_range') }}</span>
              <v-chip size="x-small" variant="flat" class="duration-chip font-weight-bold ml-1">
                {{ daysCount }} {{ $t('days_selected') }}
              </v-chip>
            </div>
            <div class="text-caption text-medium-emphasis mt-1 d-none d-sm-block" style="font-size: 11px !important; line-height: 1.4;">
              {{ formatDate(localRange.start) }} &rarr; {{ formatDate(localRange.end) }}
            </div>
          </div>
        </div>

        <!-- Quick Presets with SegmentedPillToggle (Activity Breakdown style) -->
        <div class="d-flex align-center flex-wrap ga-2.5">
          <SegmentedPillToggle
            v-model="activePreset"
            :items="presetItems"
            color="#0891B2"
            size="small"
            @change="applyPreset"
          />

          <v-divider vertical class="mx-1 d-none d-sm-block" style="height: 28px;"></v-divider>

          <!-- Toggle Custom Calendar Expand -->
          <v-btn
            size="small"
            rounded="pill"
            :variant="calendarExpanded ? 'flat' : 'outlined'"
            :color="calendarExpanded ? '#0891B2' : undefined"
            :class="['custom-cal-btn', { 'text-white': calendarExpanded }]"
            @click="calendarExpanded = !calendarExpanded"
          >
            <v-icon start size="15" class="mr-1.5">{{ calendarExpanded ? 'mdi-chevron-up' : 'mdi-calendar-edit' }}</v-icon>
            {{ calendarExpanded ? $t('collapse_calendar') : $t('expand_calendar') }}
          </v-btn>
        </div>
      </div>

      <!-- Expandable DatePicker Area -->
      <v-expand-transition>
        <div v-if="calendarExpanded" class="expanded-calendar-wrap mt-3 pt-3 border-t">
          <div class="d-flex align-center justify-space-between mb-2 px-1">
            <span class="text-caption font-weight-medium text-medium-emphasis d-flex align-center">
              <v-icon size="15" class="mr-2 text-cyan-darken-2">mdi-cursor-default-click</v-icon>
              {{ $t('dynamic_range_dashboard_hint') || $t('date_range') }}
            </span>
            <v-btn size="x-small" variant="text" color="#0891B2" class="px-3" @click="calendarExpanded = false">
              <v-icon start size="14" class="mr-1">mdi-check</v-icon>
              {{ $t('done') || 'OK' }}
            </v-btn>
          </div>
          <DatePicker
            :is-dark="main.is_dark"
            v-model.range="localRange"
            mode="date"
            :locale="main.lang?.short || 'en'"
            :max-date="new Date()"
            expanded
            color="cyan"
            :columns="$vuetify.display.mdAndUp ? 2 : 1"
          />
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
  },
  emits: ['update:modelValue'],
  data() {
    return {
      localRange: {
        start: this.modelValue?.start || moment().subtract(30, 'days').toDate(),
        end: this.modelValue?.end || new Date(),
      },
      calendarExpanded: false,
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
      ];
    },
  },
  watch: {
    localRange: {
      handler(val) {
        if (this.isSameRange(val, this.modelValue)) return;
        this.detectActivePreset(val);
        this.$emit('update:modelValue', { start: val.start, end: val.end });
      },
      deep: true,
    },
    modelValue: {
      handler(val) {
        if (this.isSameRange(val, this.localRange)) return;
        this.localRange = { start: val.start, end: val.end };
        this.detectActivePreset(val);
      },
      deep: true,
    },
  },
  mounted() {
    this.detectActivePreset(this.localRange);
  },
  methods: {
    applyPreset(id) {
      this.activePreset = id;
      const today = moment().endOf('day').toDate();
      let start = moment().subtract(30, 'days').startOf('day').toDate();

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
      }

      this.localRange = { start, end: today };
    },
    detectActivePreset(range) {
      if (!range?.start || !range?.end) return;
      const diff = moment(range.end).diff(moment(range.start), 'days');
      if (diff === 6) this.activePreset = '7d';
      else if (diff === 29 || diff === 30) this.activePreset = '30d';
      else if (diff >= 88 && diff <= 91) this.activePreset = '90d';
      else if (moment(range.start).isSame(moment().startOf('month'), 'day')) this.activePreset = 'month';
      else if (moment(range.start).isSame(moment().startOf('year'), 'day')) this.activePreset = 'ytd';
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

.preset-btn {
  font-size: 11px !important;
  font-weight: 600;
  letter-spacing: 0.1px;
  text-transform: none;
  background: rgba(8, 145, 178, 0.08);
  color: #0891B2;
  transition: all 0.18s ease;
}

.preset-btn:hover {
  background: rgba(8, 145, 178, 0.16);
}

.preset-btn.preset-active {
  background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%) !important;
  color: white !important;
  box-shadow: 0 2px 8px rgba(8, 145, 178, 0.3);
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
.expanded-calendar-wrap :deep(.vc-container) {
  border: none !important;
  width: 100% !important;
}
</style>
