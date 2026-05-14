<template>
  <div>
    <!-- Hero Header -->
    <div class="cm-hero">
      <div class="cm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="cm-hero-icon"><v-icon size="28" color="white">mdi-chart-areaspline</v-icon></div>
          <div>
            <div class="cm-hero-title">{{ $t('chart_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('chart_management_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <v-tabs v-model="activeTab" color="#00BCD4" class="mb-4">
      <v-tab :value="0"><v-icon start>mdi-view-list</v-icon>{{ $t('charts_list') }}</v-tab>
      <v-tab :value="1"><v-icon start>mdi-plus</v-icon>{{ editing ? $t('edit_chart') : $t('create_chart') }}</v-tab>
    </v-tabs>

    <!-- Tab Content -->
    <v-window v-model="activeTab">
      <!-- LIST -->
      <v-window-item :value="0">
        <v-card elevation="0" class="rounded-xl">
          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="charts"
              :loading="store.loading"
              density="comfortable"
              class="elevation-0"
              hover
            >
              <template #item.chart_type="{ item }">
                <v-chip size="small" variant="tonal" color="#00BCD4">{{ item.chart_type }}</v-chip>
              </template>
              <template #item.date_range_type="{ item }">
                <v-chip size="small" variant="outlined">{{ $t(item.date_range_type === 'all' ? 'all_dates' : item.date_range_type) }}</v-chip>
              </template>
              <template #item.series_count="{ item }">
                <v-chip size="small" color="info" variant="tonal">{{ (item.series || []).length }}</v-chip>
              </template>
              <template #item.is_active="{ item }">
                <v-icon :color="item.is_active ? 'success' : 'grey'">{{ item.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
              </template>
              <template #item.actions="{ item }">
                <v-btn icon="mdi-pencil" size="small" variant="text" color="#00BCD4" @click="editItem(item)" />
                <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="confirmDelete(item)" />
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- CREATE / EDIT -->
      <v-window-item :value="1">
        <ChartForm :edit-chart="editingChart" @saved="onSaved" @cancel="activeTab = 0" />
      </v-window-item>
    </v-window>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="420" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert</v-icon>{{ $t('confirm_delete') }}</v-card-title>
        <v-card-text>{{ $t('confirm_delete_chart_message') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="tonal" :loading="deleting" @click="doDelete">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="snackbar.show" :timeout="4000" location="top right" color="surface" rounded="lg" elevation="12" min-width="300">
      <div class="d-flex align-center">
        <v-icon :color="snackbar.color" class="mr-2">{{ snackbar.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
        <span class="font-weight-medium">{{ snackbar.text }}</span>
      </div>
      <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false" /></template>
    </v-snackbar>
  </div>
</template>

<script>
import { useCustomChart } from '@/stores/CustomChart';
import ChartForm from './ChartForm.vue';

export default {
  name: 'ChartManagement',
  components: { ChartForm },
  data() {
    return {
      activeTab: 0,
      editing: false,
      editingChart: null,
      deleteDialog: false,
      deleting: false,
      deletingItem: null,
      snackbar: { show: false, text: '', color: 'success' },
    };
  },
  computed: {
    store() { return useCustomChart(); },
    charts() { return this.store.sortedCharts; },
    headers() {
      return [
        { title: this.$t('chart_title'), key: 'title', sortable: true },
        { title: this.$t('chart_type'), key: 'chart_type', sortable: true },
        { title: this.$t('date_range_type'), key: 'date_range_type' },
        { title: this.$t('series'), key: 'series_count' },
        { title: this.$t('status'), key: 'is_active' },
        { title: this.$t('actions'), key: 'actions', sortable: false, align: 'end' },
      ];
    },
  },
  methods: {
    editItem(item) {
      this.editingChart = JSON.parse(JSON.stringify(item));
      this.editing = true;
      this.activeTab = 1;
    },
    onSaved() {
      this.editing = false;
      this.editingChart = null;
      this.activeTab = 0;
      this.snackbar = { show: true, text: this.$t('chart_saved_success'), color: 'success' };
    },
    confirmDelete(item) {
      this.deletingItem = item;
      this.deleteDialog = true;
    },
    async doDelete() {
      this.deleting = true;
      try {
        await this.store.destroy(this.deletingItem.id);
        this.snackbar = { show: true, text: this.$t('chart_deleted_success'), color: 'success' };
      } catch {
        this.snackbar = { show: true, text: 'Error', color: 'error' };
      }
      this.deleting = false;
      this.deleteDialog = false;
    },
  },
  watch: {
    activeTab(v) {
      if (v === 0) { this.editing = false; this.editingChart = null; }
    },
  },
};
</script>

<style scoped>
.cm-hero { background: linear-gradient(135deg, #0097A7 0%, #00BCD4 40%, #4DD0E1 100%); border-radius: 20px; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 90px; }
.cm-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; }
.cm-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.cm-hero-title { font-size: 18px; font-weight: 800; letter-spacing: .3px; }
</style>
