<template>
  <v-card class="fm-card" elevation="0">
    <!-- Hero Header -->
    <div class="fm-hero">
      <div class="fm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="fm-hero-icon">
            <v-icon size="28" color="white">mdi-calendar-clock</v-icon>
          </div>
          <div>
            <div class="fm-hero-title">{{ $t('fortnight_settings') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('fortnight_settings_description') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toolbar with SegmentedPillToggle (Fish Management style) -->
    <div class="fm-toolbar-card mx-4 mt-3 mb-2 pa-2 rounded-xl border d-flex flex-wrap align-center justify-space-between ga-3">
      <div class="d-flex flex-wrap align-center ga-3">
        <SegmentedPillToggle
          v-model="activeTab"
          :items="[
            { value: 'settings', label: $t('salary_thresholds'), icon: 'mdi-format-list-bulleted', badge: settingStore.list.length },
            { value: 'info', label: $t('how_it_works'), icon: 'mdi-information-outline' }
          ]"
          color="#1565C0"
        />
      </div>
    </div>

    <v-card-text class="pa-4">
      <v-window v-model="activeTab">
        <!-- ============= SALARY THRESHOLDS TAB ============= -->
        <v-window-item value="settings">
          <div class="d-flex align-center ga-3 mb-3">
            <v-spacer></v-spacer>
            <v-btn
              v-if="hasActiveColumnFilters"
              variant="tonal"
              color="warning"
              rounded
              size="small"
              prepend-icon="mdi-filter-off"
              @click="clearAllColumnFilters"
            >
              {{ $t('clear_filters') }}
            </v-btn>
            <v-btn color="#1565C0" prepend-icon="mdi-plus" rounded @click="openAddDialog">
              {{ $t('add_setting') }}
            </v-btn>
          </div>

          <!-- Empty State -->
          <div v-if="!settingStore.list.length && !settingStore.loading" class="text-center pa-8 text-grey">
            <v-icon size="56" color="grey-lighten-2">mdi-calendar-blank-outline</v-icon>
            <div class="mt-3 text-body-1">{{ $t('no_settings_found') }}</div>
            <v-btn
              class="mt-4"
              variant="flat"
              style="background-color: #1565C0; color: #fff;"
              rounded
              prepend-icon="mdi-plus"
              @click="openAddDialog"
            >
              {{ $t('add_setting') }}
            </v-btn>
          </div>

          <!-- Data Table with Multi-Sort & Column Filters -->
          <v-data-table
            v-else
            :headers="headers"
            :items="filteredSettings"
            :loading="settingStore.loading ? '#1565C0' : false"
            :multi-sort="true"
            density="comfortable"
            class="rounded-lg param-table"
            hover
          >
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr>
                <th v-for="col in columns" :key="col.key" class="param-th">
                  <div
                    class="param-th-label"
                    @click="col.sortable !== false ? toggleSort(col) : null"
                    :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
                  >
                    {{ col.title }}
                    <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
                  </div>
                </th>
              </tr>
              <tr class="param-filter-row">
                <th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th">
                  <v-autocomplete
                    v-if="col.key !== 'actions'"
                    v-model="columnFilters[col.key]"
                    :items="getColumnOptions(col.key)"
                    density="compact"
                    variant="outlined"
                    color="#1565C0"
                    class="param-filter-input"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    :placeholder="$t('filter')"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" size="x-small" color="#1565C0" variant="tonal" closable>
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
                </th>
              </tr>
            </template>

            <template v-slot:[`item.date_start`]="{ item }">
              <v-chip size="small" color="#42A5F5" variant="tonal" label>
                <v-icon start size="14">mdi-calendar-start</v-icon>
                {{ formatDate(item.date_start) }}
              </v-chip>
            </template>

            <template v-slot:[`item.date_end`]="{ item }">
              <v-chip v-if="item.date_end" size="small" color="warning" variant="tonal" label>
                <v-icon start size="14">mdi-calendar-end</v-icon>
                {{ formatDate(item.date_end) }}
              </v-chip>
              <v-chip v-else size="small" color="success" variant="tonal" label>
                <v-icon start size="14">mdi-infinity</v-icon>
                {{ $t('open_ended') }}
              </v-chip>
            </template>

            <template v-slot:[`item.q1_max_salary`]="{ item }">
              <div class="text-center font-weight-bold">{{ formatNumber(item.q1_max_salary) }} DH</div>
            </template>

            <template v-slot:[`item.q2_max_salary`]="{ item }">
              <div class="text-center font-weight-bold">{{ formatNumber(item.q2_max_salary) }} DH</div>
            </template>

            <template v-slot:[`item.month_max_salary`]="{ item }">
              <div class="text-center font-weight-bold" style="color: #1565C0;">
                {{ formatNumber(parseFloat(item.q1_max_salary || 0) + parseFloat(item.q2_max_salary || 0)) }} DH
              </div>
            </template>

            <template v-slot:[`item.actions`]="{ item }">
              <div class="d-flex align-center justify-center">
                <v-btn icon size="x-small" variant="tonal" color="#1565C0" @click="openEditDialog(item)" class="mr-1">
                  <v-icon size="16">mdi-pencil</v-icon>
                  <v-tooltip activator="parent">{{ $t('edit') }}</v-tooltip>
                </v-btn>
                <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)">
                  <v-icon size="16">mdi-delete</v-icon>
                  <v-tooltip activator="parent">{{ $t('delete') }}</v-tooltip>
                </v-btn>
              </div>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= HOW IT WORKS TAB ============= -->
        <v-window-item value="info">
          <v-card variant="outlined" class="rounded-xl border" color="#42A5F5">
            <v-card-title class="d-flex align-center pa-4 pb-2">
              <v-icon color="#1565C0" class="mr-2">mdi-information-outline</v-icon>
              <span class="text-subtitle-1 font-weight-bold" style="color: #1565C0;">{{ $t('how_it_works') }}</span>
            </v-card-title>
            <v-card-text class="pa-4 pt-2">
              <div class="fns-info-item mb-3">
                <v-icon size="18" color="success" class="mr-2">mdi-check-circle</v-icon>
                <span class="text-body-2">{{ $t('fortnight_info_1') }}</span>
              </div>
              <div class="fns-info-item mb-3">
                <v-icon size="18" color="warning" class="mr-2">mdi-alert-circle</v-icon>
                <span class="text-body-2">{{ $t('fortnight_info_2') }}</span>
              </div>
              <div class="fns-info-item mb-3">
                <v-icon size="18" color="#1565C0" class="mr-2">mdi-calculator</v-icon>
                <span class="text-body-2">{{ $t('fortnight_info_3') }}</span>
              </div>
              <div class="fns-info-item">
                <v-icon size="18" color="#1565C0" class="mr-2">mdi-calendar-range</v-icon>
                <span class="text-body-2">{{ $t('fortnight_info_4') }}</span>
              </div>
            </v-card-text>
          </v-card>
        </v-window-item>
      </v-window>
    </v-card-text>

    <!-- Add/Edit Dialog -->
    <v-dialog v-model="dialog.show" max-width="520" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="#42A5F5" class="mr-2">{{ dialog.editId ? 'mdi-pencil' : 'mdi-plus-circle' }}</v-icon>
          <span>{{ dialog.editId ? $t('edit_setting') : $t('add_setting') }}</span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="dialog.show = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider style="border-color: #42A5F5;"></v-divider>

        <v-card-text class="pa-4">
          <v-row>
            <!-- VCalendar Date Picker Section -->
            <v-col cols="12">
              <v-switch
                v-model="dialog.openEnded"
                :label="$t('open_ended')"
                color="warning"
                density="compact"
                hide-details
                class="mb-2"
              ></v-switch>

              <DatePicker
                :is-dark="main.is_dark"
                v-model.range="dialog.dateRange"
                mode="date"
                :locale="main.lang.short"
                expanded
                color="blue"
                v-if="!dialog.openEnded"
              />
              <DatePicker
                :is-dark="main.is_dark"
                v-model="dialog.dateRange.start"
                mode="date"
                :locale="main.lang.short"
                expanded
                color="blue"
                v-if="dialog.openEnded"
              />

              <div class="text-center mt-3" role="group">
                <v-chip color="#42A5F5" variant="tonal" class="mr-2">
                  <v-icon left small class="mr-2">mdi-calendar-start</v-icon>
                  <span>{{ main.formateDate(dialog.dateRange.start) }}</span>
                </v-chip>
                <v-divider vertical></v-divider>
                <v-chip :color="dialog.openEnded ? 'warning' : '#42A5F5'" variant="tonal" class="ml-2">
                  <span>{{ dialog.openEnded ? $t('open_ended') : main.formateDate(dialog.dateRange.end) }}</span>
                  <v-icon left small class="ml-2">{{ dialog.openEnded ? 'mdi-infinity' : 'mdi-calendar-end' }}</v-icon>
                </v-chip>
              </div>
            </v-col>

            <!-- Q1 Max Salary -->
            <v-col cols="12" sm="6">
              <v-text-field
                v-model.number="dialog.form.q1_max_salary"
                :label="$t('q1_max_salary')"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                type="number"
                min="0"
                step="0.01"
                prefix="DH"
                prepend-inner-icon="mdi-numeric-1-circle"
                :hint="$t('first_fortnight_threshold')"
                persistent-hint
              ></v-text-field>
            </v-col>

            <!-- Q2 Max Salary -->
            <v-col cols="12" sm="6">
              <v-text-field
                v-model.number="dialog.form.q2_max_salary"
                :label="$t('q2_max_salary')"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                type="number"
                min="0"
                step="0.01"
                prefix="DH"
                prepend-inner-icon="mdi-numeric-2-circle"
                :hint="$t('second_fortnight_threshold')"
                persistent-hint
              ></v-text-field>
            </v-col>
          </v-row>

          <!-- Month Total Preview -->
          <v-divider class="my-3" style="border-color: #42A5F5;"></v-divider>
          <div class="fns-month-total">
            <v-icon color="#42A5F5" class="mr-2">mdi-sigma</v-icon>
            <span class="text-body-1 font-weight-medium">{{ $t('month_max_salary') }}:</span>
            <v-chip color="#42A5F5" variant="flat" size="small" class="ml-2 font-weight-bold">
              {{ formatNumber(dialogMonthTotal) }} DH
            </v-chip>
          </div>
        </v-card-text>

        <v-divider></v-divider>
        <v-card-actions class="pa-4">
          <v-btn variant="text" color="red-lighten-1" rounded @click="dialog.show = false">
            {{ $t('cancel') }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            style="background-color: #42A5F5; color: #fff;"
            rounded
            :loading="dialog.saving"
            :disabled="!dialog.dateRange.start || dialog.saving"
            prepend-icon="mdi-content-save"
            @click="saveDialog"
          >
            {{ dialog.editId ? $t('update') : $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog.show" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="error" class="mr-2">mdi-alert-circle</v-icon>
          <span>{{ $t('confirm_delete_setting') }}</span>
        </v-card-title>
        <v-card-text class="pa-4 pt-0">
          {{ $t('confirm_delete_setting_text') }}
          <div v-if="deleteDialog.setting" class="mt-3 pa-3 rounded-lg" style="background: rgba(244,67,54,.06);">
            <strong>{{ formatDate(deleteDialog.setting.date_start) }}</strong>
            →
            <strong>{{ deleteDialog.setting.date_end ? formatDate(deleteDialog.setting.date_end) : $t('open_ended') }}</strong>
            <br>
            Q1: {{ formatNumber(deleteDialog.setting.q1_max_salary) }} DH |
            Q2: {{ formatNumber(deleteDialog.setting.q2_max_salary) }} DH
          </div>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn variant="text" rounded @click="deleteDialog.show = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            color="error"
            rounded
            :loading="deleteDialog.deleting"
            prepend-icon="mdi-delete"
            @click="doDelete"
          >
            {{ $t('delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar (Rule 5: Countdown style) -->
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
import { useFortnightSetting } from '@/stores/FortnightSetting';
import { useMain } from '@/stores/Main';

export default {
  name: 'FortnightSettings',
  components: { SegmentedPillToggle },
  data() {
    return {
      activeTab: 'settings',
      columnFilters: {},
      dialog: {
        show: false,
        editId: null,
        saving: false,
        openEnded: false,
        dateRange: { start: new Date(), end: new Date() },
        form: {
          q1_max_salary: 0,
          q2_max_salary: 0,
        },
      },
      deleteDialog: {
        show: false,
        setting: null,
        deleting: false,
      },
      snackbar: { show: false, message: '', color: 'success', icon: 'mdi-check-circle', timeout: 4000, progress: 100 },
      snackbarTimer: null,
    };
  },
  computed: {
    settingStore() { return useFortnightSetting(); },
    main() { return useMain(); },
    headers() {
      return [
        { title: this.$t('date_start'), key: 'date_start', align: 'start' },
        { title: this.$t('date_end'), key: 'date_end', align: 'start' },
        { title: this.$t('q1_max_salary'), key: 'q1_max_salary', align: 'center' },
        { title: this.$t('q2_max_salary'), key: 'q2_max_salary', align: 'center' },
        { title: this.$t('month_max_salary'), key: 'month_max_salary', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(v => Array.isArray(v) ? v.length > 0 : !!v);
    },
    sortedSettings() {
      return [...this.settingStore.list].sort((a, b) => {
        return new Date(b.date_start) - new Date(a.date_start);
      });
    },
    filteredSettings() {
      if (!this.hasActiveColumnFilters) return this.sortedSettings;
      return this.sortedSettings.filter(item => {
        for (const [key, filterValues] of Object.entries(this.columnFilters)) {
          if (!filterValues || (Array.isArray(filterValues) && filterValues.length === 0)) continue;
          let val = '';
          if (key === 'date_start') val = this.formatDate(item.date_start);
          else if (key === 'date_end') val = item.date_end ? this.formatDate(item.date_end) : this.$t('open_ended');
          else if (key === 'q1_max_salary') val = `${this.formatNumber(item.q1_max_salary)} DH`;
          else if (key === 'q2_max_salary') val = `${this.formatNumber(item.q2_max_salary)} DH`;
          else if (key === 'month_max_salary') {
            val = `${this.formatNumber(parseFloat(item.q1_max_salary || 0) + parseFloat(item.q2_max_salary || 0))} DH`;
          } else {
            val = String(item[key] || '');
          }

          if (Array.isArray(filterValues)) {
            if (!filterValues.includes(val)) return false;
          } else if (typeof filterValues === 'string') {
            if (!val.toLowerCase().includes(filterValues.toLowerCase())) return false;
          }
        }
        return true;
      });
    },
    dialogMonthTotal() {
      return (parseFloat(this.dialog.form.q1_max_salary) || 0) + (parseFloat(this.dialog.form.q2_max_salary) || 0);
    },
  },
  methods: {
    formatNumber(n) {
      if (n == null || isNaN(n)) return '0';
      return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(d) {
      if (!d) return '-';
      return this.main.formateDate(d);
    },
    getColumnOptions(key) {
      const set = new Set();
      this.sortedSettings.forEach(item => {
        let v = '';
        if (key === 'date_start') v = this.formatDate(item.date_start);
        else if (key === 'date_end') v = item.date_end ? this.formatDate(item.date_end) : this.$t('open_ended');
        else if (key === 'q1_max_salary') v = `${this.formatNumber(item.q1_max_salary)} DH`;
        else if (key === 'q2_max_salary') v = `${this.formatNumber(item.q2_max_salary)} DH`;
        else if (key === 'month_max_salary') {
          v = `${this.formatNumber(parseFloat(item.q1_max_salary || 0) + parseFloat(item.q2_max_salary || 0))} DH`;
        } else {
          v = item[key];
        }
        if (v !== undefined && v !== null && v !== '') set.add(String(v));
      });
      return Array.from(set).sort();
    },
    clearAllColumnFilters() {
      this.columnFilters = {};
    },
    isCurrentlyActive(s) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const start = new Date(s.date_start);
      start.setHours(0, 0, 0, 0);
      if (today < start) return false;
      if (s.date_end) {
        const end = new Date(s.date_end);
        end.setHours(0, 0, 0, 0);
        if (today > end) return false;
      }
      return true;
    },
    formatDateForApi(d) {
      if (!d) return null;
      const dt = d instanceof Date ? d : new Date(d);
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const day = String(dt.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    },
    openAddDialog() {
      this.dialog.editId = null;
      this.dialog.openEnded = false;
      this.dialog.dateRange = { start: new Date(), end: new Date() };
      this.dialog.form = { q1_max_salary: 0, q2_max_salary: 0 };
      this.dialog.show = true;
    },
    openEditDialog(s) {
      this.dialog.editId = s.id;
      this.dialog.openEnded = !s.date_end;
      this.dialog.dateRange = {
        start: new Date(s.date_start),
        end: s.date_end ? new Date(s.date_end) : new Date(),
      };
      this.dialog.form = {
        q1_max_salary: parseFloat(s.q1_max_salary) || 0,
        q2_max_salary: parseFloat(s.q2_max_salary) || 0,
      };
      this.dialog.show = true;
    },
    async saveDialog() {
      this.dialog.saving = true;
      try {
        const payload = {
          date_start: this.formatDateForApi(this.dialog.dateRange.start),
          date_end: this.dialog.openEnded ? null : this.formatDateForApi(this.dialog.dateRange.end),
          q1_max_salary: this.dialog.form.q1_max_salary,
          q2_max_salary: this.dialog.form.q2_max_salary,
        };
        if (this.dialog.editId) {
          await this.settingStore.update(this.dialog.editId, payload);
        } else {
          await this.settingStore.store(payload);
        }
        this.dialog.show = false;
        this.showSummarySnackbar(this.$t('settings_updated'), 'success', 'mdi-check-circle');
      } catch (e) {
        this.showSummarySnackbar(this.$t('error'), 'error', 'mdi-alert-circle');
      }
      this.dialog.saving = false;
    },
    confirmDelete(s) {
      this.deleteDialog.setting = s;
      this.deleteDialog.show = true;
    },
    async doDelete() {
      if (!this.deleteDialog.setting) return;
      this.deleteDialog.deleting = true;
      try {
        await this.settingStore.destroy(this.deleteDialog.setting.id);
        this.deleteDialog.show = false;
        this.showSummarySnackbar(this.$t('setting_deleted'), 'success', 'mdi-check-circle');
      } catch (e) {
        this.showSummarySnackbar(this.$t('error'), 'error', 'mdi-alert-circle');
      }
      this.deleteDialog.deleting = false;
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
    await this.settingStore.fetch();
  },
  beforeUnmount() {
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
};
</script>

<style scoped>
.fm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.fm-hero { background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.fm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.fm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.fm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.fm-toolbar-card { background: rgba(var(--v-theme-surface), 0.7); backdrop-filter: blur(8px); }
.param-table { border: 1px solid rgba(66,165,245,.15); }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; cursor: pointer; }
.param-filter-row th { padding: 4px 6px 8px !important; }
.param-filter-input :deep(.v-field) { border-radius: 8px !important; font-size: 11px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 28px !important; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
.fns-month-total {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-radius: 10px;
  background: rgba(66,165,245,.06);
}
.fns-info-item {
  display: flex;
  align-items: flex-start;
}
</style>
