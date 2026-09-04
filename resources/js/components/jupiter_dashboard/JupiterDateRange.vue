<template>
  <v-card class="jupiter-date-card rounded-xl mb-4" elevation="1">
    <v-card-text class="pa-4">
      <div class="d-flex align-center ga-3 mb-3 flex-wrap">
        <v-icon style="color: #FF6F00">mdi-calendar-range</v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ $t('date_range') }}</span>
        <v-spacer />
        <v-chip style="background: rgba(255,111,0,.12); color: #FF6F00" variant="flat" size="small" label>
          <v-icon start size="14">mdi-calendar-start</v-icon>
          {{ formatDate(localRange.start) }}
        </v-chip>
        <v-icon size="16" color="grey">mdi-arrow-right</v-icon>
        <v-chip style="background: rgba(255,111,0,.12); color: #FF6F00" variant="flat" size="small" label>
          <v-icon start size="14">mdi-calendar-end</v-icon>
          {{ formatDate(localRange.end) }}
        </v-chip>
      </div>
      <DatePicker
        :is-dark="main.is_dark"
        v-model.range="localRange"
        mode="date"
        :locale="main.lang?.short || 'en'"
        :max-date="new Date()"
        expanded
        color="orange"
        :columns="2"
      />
    </v-card-text>
  </v-card>
</template>

<script>
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import { useMain } from '@/stores/Main';
import moment from 'moment';

export default {
  name: 'JupiterDateRange',
  components: { DatePicker },
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
    };
  },
  computed: {
    main() { return useMain(); },
  },
  watch: {
    localRange: {
      handler(val) {
        // Only emit if dates actually changed (prevents ping-pong loop)
        if (this.isSameRange(val, this.modelValue)) return;
        this.$emit('update:modelValue', { start: val.start, end: val.end });
      },
      deep: true,
    },
    modelValue: {
      handler(val) {
        // Only update local if dates actually changed (prevents ping-pong loop)
        if (this.isSameRange(val, this.localRange)) return;
        this.localRange = { start: val.start, end: val.end };
      },
      deep: true,
    },
  },
  methods: {
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
  border: 1px solid rgba(255, 111, 0, 0.15);
}
</style>
