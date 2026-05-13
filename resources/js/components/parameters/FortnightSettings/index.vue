<template>
  <v-container fluid class="pa-6">
    <!-- Hero Header -->
    <div class="fns-hero mb-6">
      <div class="fns-hero-content">
        <div class="fns-hero-icon">
          <v-icon size="32" color="white">mdi-calendar-clock</v-icon>
        </div>
        <div>
          <h1 class="fns-hero-title">{{ $t('fortnight_settings') }}</h1>
          <p class="fns-hero-subtitle">{{ $t('fortnight_settings_description') }}</p>
        </div>
        <v-spacer></v-spacer>
        <v-btn
          variant="flat"
          style="background: rgba(255,255,255,.2); color: #fff;"
          rounded
          prepend-icon="mdi-plus"
          @click="openAddDialog"
        >
          {{ $t('add_setting') }}
        </v-btn>
      </div>
    </div>

    <!-- Settings List Table -->
    <v-card variant="outlined" class="rounded-xl" color="#42A5F5">
      <v-card-title class="d-flex align-center pa-4 pb-2">
        <v-icon color="#42A5F5" class="mr-2">mdi-format-list-bulleted</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('salary_thresholds') }}</span>
        <v-spacer></v-spacer>
        <v-chip color="#42A5F5" variant="tonal" size="small">
          {{ settingStore.list.length }} {{ $t('entries') }}
        </v-chip>
      </v-card-title>

      <v-card-text class="pa-4 pt-0">
        <!-- Empty State -->
        <div v-if="!settingStore.list.length && !settingStore.loading" class="text-center pa-8 text-grey">
          <v-icon size="56" color="grey-lighten-2">mdi-calendar-blank-outline</v-icon>
          <div class="mt-3 text-body-1">{{ $t('no_settings_found') }}</div>
          <v-btn
            class="mt-4"
            variant="flat"
            style="background-color: #42A5F5; color: #fff;"
            rounded
            prepend-icon="mdi-plus"
            @click="openAddDialog"
          >
            {{ $t('add_setting') }}
          </v-btn>
        </div>

        <!-- Table -->
        <v-table v-else density="compact" class="rounded-lg fns-table">
          <thead>
            <tr>
              <th>{{ $t('date_start') }}</th>
              <th>{{ $t('date_end') }}</th>
              <th class="text-center">{{ $t('q1_max_salary') }}</th>
              <th class="text-center">{{ $t('q2_max_salary') }}</th>
              <th class="text-center">{{ $t('month_max_salary') }}</th>
              <th class="text-center" style="width: 120px;">{{ $t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sortedSettings" :key="s.id" :class="{'fns-active-row': isCurrentlyActive(s)}">
              <td>
                <v-chip size="small" color="#42A5F5" variant="tonal" label>
                  <v-icon start size="14">mdi-calendar-start</v-icon>
                  {{ formatDate(s.date_start) }}
                </v-chip>
              </td>
              <td>
                <v-chip v-if="s.date_end" size="small" color="warning" variant="tonal" label>
                  <v-icon start size="14">mdi-calendar-end</v-icon>
                  {{ formatDate(s.date_end) }}
                </v-chip>
                <v-chip v-else size="small" color="success" variant="tonal" label>
                  <v-icon start size="14">mdi-infinity</v-icon>
                  {{ $t('open_ended') }}
                </v-chip>
              </td>
              <td class="text-center font-weight-bold">{{ formatNumber(s.q1_max_salary) }} DH</td>
              <td class="text-center font-weight-bold">{{ formatNumber(s.q2_max_salary) }} DH</td>
              <td class="text-center font-weight-bold" style="color: #42A5F5;">
                {{ formatNumber(parseFloat(s.q1_max_salary || 0) + parseFloat(s.q2_max_salary || 0)) }} DH
              </td>
              <td class="text-center">
                <v-btn icon variant="text" size="small" color="#42A5F5" @click="openEditDialog(s)">
                  <v-icon size="18">mdi-pencil</v-icon>
                  <v-tooltip activator="parent">{{ $t('edit') }}</v-tooltip>
                </v-btn>
                <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(s)">
                  <v-icon size="18">mdi-delete</v-icon>
                  <v-tooltip activator="parent">{{ $t('delete') }}</v-tooltip>
                </v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <!-- Info Card -->
    <v-card variant="outlined" class="rounded-xl mt-4" color="#42A5F5">
      <v-card-title class="d-flex align-center pa-4 pb-2">
        <v-icon color="#42A5F5" class="mr-2">mdi-information-outline</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ $t('how_it_works') }}</span>
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
          <v-icon size="18" color="#42A5F5" class="mr-2">mdi-calculator</v-icon>
          <span class="text-body-2">{{ $t('fortnight_info_3') }}</span>
        </div>
        <div class="fns-info-item">
          <v-icon size="18" color="#42A5F5" class="mr-2">mdi-calendar-range</v-icon>
          <span class="text-body-2">{{ $t('fortnight_info_4') }}</span>
        </div>
      </v-card-text>
    </v-card>

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

    <!-- Summary Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" location="bottom end" rounded="lg">
      {{ snackbar.message }}
    </v-snackbar>
  </v-container>
</template>

<script>
import { useFortnightSetting } from '@/stores/FortnightSetting';
import { useMain } from '@/stores/Main';

export default {
  name: 'FortnightSettings',
  data() {
    return {
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
      snackbar: { show: false, message: '', color: 'success' },
    };
  },
  computed: {
    settingStore() { return useFortnightSetting(); },
    main() { return useMain(); },
    sortedSettings() {
      return [...this.settingStore.list].sort((a, b) => {
        return new Date(b.date_start) - new Date(a.date_start);
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
        this.snackbar = { show: true, message: this.$t('settings_updated'), color: 'success' };
      } catch (e) {
        this.snackbar = { show: true, message: this.$t('error'), color: 'error' };
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
        this.snackbar = { show: true, message: this.$t('setting_deleted'), color: 'success' };
      } catch (e) {
        this.snackbar = { show: true, message: this.$t('error'), color: 'error' };
      }
      this.deleteDialog.deleting = false;
    },
  },
  async mounted() {
    await this.settingStore.fetch();
  },
};
</script>

<style scoped>
.fns-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: 16px;
  padding: 28px 32px;
  color: #fff;
}
.fns-hero-content {
  display: flex;
  align-items: center;
  gap: 16px;
}
.fns-hero-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
}
.fns-hero-title {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -.3px;
  margin: 0;
}
.fns-hero-subtitle {
  font-size: 13px;
  opacity: .8;
  margin: 2px 0 0;
}
.fns-table {
  border: 1px solid rgba(66,165,245,.15);
}
.fns-table th {
  background: rgba(66,165,245,.06) !important;
  font-weight: 700 !important;
  font-size: 12px !important;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.fns-active-row {
  background: rgba(66,165,245,.06) !important;
  border-left: 3px solid #42A5F5;
}
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
