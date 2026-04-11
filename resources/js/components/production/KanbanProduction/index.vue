<template>
  <div class="pk-page">
    <!-- Hero Header -->
    <v-card class="rounded-xl mb-5" variant="flat">
      <div class="pk-hero-bg"></div>
      <div class="pk-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="pk-hero-icon">
            <v-icon size="28" color="white">mdi-factory</v-icon>
          </div>
          <div>
            <div class="pk-hero-title">{{ $t('production_kanban') }}</div>
            <div class="text-white-darken-2 text-caption" style="opacity:.7">
              {{ $t('production_kanban_desc') }}
            </div>
          </div>
        </div>
      </div>
    </v-card>

    <!-- Date Picker Row -->
    <v-card class="rounded-xl mb-5 pa-4" variant="flat">
      <div class="pk-date-section">
        <div class="pk-datepicker-wrap">
          <DatePicker
            :is-dark="main.is_dark"
            :max-date="new Date()"
            v-model.range="date"
            mode="date"
            :locale="main.lang.short"
            :columns="2"
            expanded
          />
        </div>
        <div class="d-flex align-center flex-wrap ga-3 mt-4">
          <v-icon color="info" size="18">mdi-calendar-range</v-icon>
          <v-chip color="info" variant="tonal" size="small">
            <v-icon start size="13">mdi-calendar-start</v-icon>
            {{ main.formateDate(date.start) }}
          </v-chip>
          <v-icon size="16" color="grey">mdi-arrow-right</v-icon>
          <v-chip color="indigo" variant="tonal" size="small">
            <v-icon start size="13">mdi-calendar-end</v-icon>
            {{ main.formateDate(date.end) }}
          </v-chip>
          <v-spacer></v-spacer>
          <v-btn
            variant="tonal"
            color="info"
            size="small"
            rounded="lg"
            :loading="charge.loading"
            @click="fetchData"
          >
            <v-icon start size="16">mdi-refresh</v-icon>
            {{ $t('refresh') || 'Refresh' }}
          </v-btn>
        </div>
      </div>
    </v-card>

    <!-- Stats Row -->
    <v-row class="mb-4" dense>
      <v-col cols="12" sm="4">
        <v-card class="rounded-xl pk-stat" variant="flat">
          <v-card-text class="d-flex align-center ga-3">
            <div class="pk-stat-icon pk-stat-icon--total">
              <v-icon size="20" color="white">mdi-clipboard-list-outline</v-icon>
            </div>
            <div>
              <div class="pk-stat-lbl">{{ $t('total') }}</div>
              <div class="pk-stat-val">{{ productionItems.length }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card class="rounded-xl pk-stat" variant="flat">
          <v-card-text class="d-flex align-center ga-3">
            <div class="pk-stat-icon pk-stat-icon--received">
              <v-icon size="20" color="white">mdi-fish</v-icon>
            </div>
            <div>
              <div class="pk-stat-lbl">{{ $t('fresh_fish_received') }}</div>
              <div class="pk-stat-val">{{ receivedItems.length }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card class="rounded-xl pk-stat" variant="flat">
          <v-card-text class="d-flex align-center ga-3">
            <div class="pk-stat-icon pk-stat-icon--production">
              <v-icon size="20" color="white">mdi-cog-outline</v-icon>
            </div>
            <div>
              <div class="pk-stat-lbl">{{ $t('in_production') }}</div>
              <div class="pk-stat-val">{{ inProductionItems.length }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Loading -->
    <v-row v-if="charge.loading" justify="center" class="py-10">
      <v-progress-circular indeterminate color="info" size="48"></v-progress-circular>
    </v-row>

    <!-- Kanban Board -->
    <v-row v-else>
      <!-- Lane: Fresh Fish Received -->
      <v-col cols="12" md="6">
        <v-card class="rounded-xl pk-lane" variant="flat">
          <div class="pk-lane-head pk-lane-head--received">
            <v-icon size="20" color="white" class="me-2">mdi-fish</v-icon>
            <span class="pk-lane-title">{{ $t('fresh_fish_received') }}</span>
            <v-spacer></v-spacer>
            <v-chip color="white" variant="tonal" size="x-small" class="font-weight-bold">{{ receivedItems.length }}</v-chip>
          </div>
          <div class="pk-lane-body">
            <div v-if="receivedItems.length === 0" class="pk-lane-empty">
              <v-icon size="40" color="grey-lighten-1">mdi-tray-remove</v-icon>
              <div class="text-caption text-medium-emphasis mt-1">{{ $t('no_data_available') }}</div>
            </div>
            <div
              v-for="item in receivedItems"
              :key="item.id"
              class="pk-card pk-card--received"
              @click="viewCharge(item)"
            >
              <div class="d-flex align-center mb-2">
                <v-chip color="info" variant="tonal" size="x-small" class="font-weight-bold me-2">
                  #{{ item.id }}
                </v-chip>
                <v-chip color="grey" variant="outlined" size="x-small">
                  {{ main.formateDate(item.date) }}
                </v-chip>
                <v-spacer></v-spacer>
                <v-icon size="14" color="grey">mdi-eye-outline</v-icon>
              </div>
              <!-- Tracking -->
              <div v-if="item.tracking_numbers && item.tracking_numbers.length" class="mb-2">
                <v-chip v-for="tn in item.tracking_numbers.slice(0,3)" :key="tn" color="info" size="x-small" variant="tonal" class="me-1 mb-1">{{ tn }}</v-chip>
                <v-chip v-if="item.tracking_numbers.length > 3" color="grey" size="x-small" variant="outlined">+{{ item.tracking_numbers.length - 3 }}</v-chip>
              </div>
              <!-- Suppliers -->
              <div v-if="item.suppliers && item.suppliers.length" class="mb-2">
                <v-icon size="12" color="grey" class="me-1">mdi-truck-delivery-outline</v-icon>
                <span class="text-caption text-medium-emphasis">{{ item.suppliers.map(s => s.name).join(', ') }}</span>
              </div>
              <!-- Weight & Price -->
              <div class="d-flex ga-2 mt-1">
                <v-chip v-if="item.total_weight" color="teal" variant="tonal" size="x-small">
                  <v-icon start size="10">mdi-weight-kilogram</v-icon>
                  {{ main.formatDisplayNumber(item.total_weight) }} Kg
                </v-chip>
                <v-chip v-if="item.total_price" color="amber-darken-2" variant="tonal" size="x-small">
                  <v-icon start size="10">mdi-cash</v-icon>
                  {{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}
                </v-chip>
              </div>
            </div>
          </div>
        </v-card>
      </v-col>

      <!-- Lane: In Production -->
      <v-col cols="12" md="6">
        <v-card class="rounded-xl pk-lane" variant="flat">
          <div class="pk-lane-head pk-lane-head--production">
            <v-icon size="20" color="white" class="me-2">mdi-cog-outline</v-icon>
            <span class="pk-lane-title">{{ $t('in_production') }}</span>
            <v-spacer></v-spacer>
            <v-chip color="white" variant="tonal" size="x-small" class="font-weight-bold">{{ inProductionItems.length }}</v-chip>
          </div>
          <div class="pk-lane-body">
            <div v-if="inProductionItems.length === 0" class="pk-lane-empty">
              <v-icon size="40" color="grey-lighten-1">mdi-tray-remove</v-icon>
              <div class="text-caption text-medium-emphasis mt-1">{{ $t('no_data_available') }}</div>
            </div>
            <div
              v-for="item in inProductionItems"
              :key="item.id"
              class="pk-card pk-card--production"
              @click="viewCharge(item)"
            >
              <div class="d-flex align-center mb-2">
                <v-chip color="purple" variant="tonal" size="x-small" class="font-weight-bold me-2">
                  #{{ item.id }}
                </v-chip>
                <v-chip color="grey" variant="outlined" size="x-small">
                  {{ main.formateDate(item.date) }}
                </v-chip>
                <v-spacer></v-spacer>
                <v-icon size="14" color="grey">mdi-eye-outline</v-icon>
              </div>
              <!-- Tracking -->
              <div v-if="item.tracking_numbers && item.tracking_numbers.length" class="mb-2">
                <v-chip v-for="tn in item.tracking_numbers.slice(0,3)" :key="tn" color="purple" size="x-small" variant="tonal" class="me-1 mb-1">{{ tn }}</v-chip>
                <v-chip v-if="item.tracking_numbers.length > 3" color="grey" size="x-small" variant="outlined">+{{ item.tracking_numbers.length - 3 }}</v-chip>
              </div>
              <!-- Production Operations -->
              <div v-if="getProductionOps(item).length" class="mb-2">
                <v-chip
                  v-for="op in getProductionOps(item)"
                  :key="op.id"
                  color="purple" variant="tonal" size="x-small" class="me-1 mb-1"
                >
                  <v-icon start size="10">mdi-factory</v-icon>
                  {{ wcOp.getName(op.warehouse_category_operation_id) }}
                </v-chip>
              </div>
              <!-- Suppliers -->
              <div v-if="item.suppliers && item.suppliers.length" class="mb-2">
                <v-icon size="12" color="grey" class="me-1">mdi-truck-delivery-outline</v-icon>
                <span class="text-caption text-medium-emphasis">{{ item.suppliers.map(s => s.name).join(', ') }}</span>
              </div>
              <!-- Weight & Price -->
              <div class="d-flex ga-2 mt-1">
                <v-chip v-if="item.total_weight" color="teal" variant="tonal" size="x-small">
                  <v-icon start size="10">mdi-weight-kilogram</v-icon>
                  {{ main.formatDisplayNumber(item.total_weight) }} Kg
                </v-chip>
                <v-chip v-if="item.total_price" color="amber-darken-2" variant="tonal" size="x-small">
                  <v-icon start size="10">mdi-cash</v-icon>
                  {{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}
                </v-chip>
              </div>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useCharge } from '@/stores/Charge';
import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';

export default {
  data() {
    return {
      date: {
        start: new Date(2024, 2, 1),
        end: new Date(),
      },
    };
  },
  computed: {
    main() { return useMain(); },
    charge() { return useCharge(); },
    wcOp() { return useWarehouseCategoryOperation(); },

    productionItems() {
      return this.charge.production_fetch_items || [];
    },

    // Production WCO IDs (operations with is_production = true)
    productionOpIds() {
      return (this.wcOp.list || [])
        .filter(op => op.is_production)
        .map(op => op.id);
    },

    // Charges that have NO warehouse ops with is_production = true
    receivedItems() {
      return this.productionItems.filter(item => {
        const ops = item.warehouse_stockable_tracking_number_operations || [];
        return !ops.some(op => this.productionOpIds.includes(op.warehouse_category_operation_id));
      });
    },

    // Charges that HAVE at least one warehouse op with is_production = true
    inProductionItems() {
      return this.productionItems.filter(item => {
        const ops = item.warehouse_stockable_tracking_number_operations || [];
        return ops.some(op => this.productionOpIds.includes(op.warehouse_category_operation_id));
      });
    },
  },
  methods: {
    fetchData() {
      this.charge.fetchProductionCharges(
        this.main.formateDate(this.date.start),
        this.main.formateDate(this.date.end)
      );
    },
    viewCharge(item) {
      this.$router.push({ name: 'view_charge', params: { id: item.id } });
    },
    getProductionOps(item) {
      const ops = item.warehouse_stockable_tracking_number_operations || [];
      return ops.filter(op => this.productionOpIds.includes(op.warehouse_category_operation_id));
    },
  },
  mounted() {
    this.fetchData();
  },
};
</script>

<style scoped>
/* ============================================================
   Production Kanban — Premium Design
   ============================================================ */

.pk-page {
  font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
}

/* ── Date Section ── */
.pk-date-section {
  display: flex;
  flex-direction: column;
}

.pk-datepicker-wrap {
  width: 100%;
}

.pk-datepicker-wrap :deep(.vc-container) {
  width: 100% !important;
  max-width: 100% !important;
  border: none;
  border-radius: 12px;
}

.pk-datepicker-wrap :deep(.vc-pane-container) {
  width: 100% !important;
}

.pk-datepicker-wrap :deep(.vc-pane-layout) {
  gap: 16px;
}

.pk-datepicker-wrap :deep(.vc-pane) {
  flex: 1;
  min-width: 0;
}

/* ── Hero ── */
.pk-hero-bg {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #0d47a1 0%, #1565C0 30%, #1E88E5 60%, #42A5F5 100%);
  border-radius: inherit;
}

.pk-hero-inner {
  position: relative; z-index: 1;
  padding: 28px 30px;
  color: #fff;
}

.pk-hero-icon {
  width: 54px; height: 54px;
  border-radius: 16px;
  background: rgba(255, 255, 255, .15);
  backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255, 255, 255, .2);
  transition: transform 0.3s ease;
}

.pk-hero-icon:hover {
  transform: scale(1.05);
}

.pk-hero-title {
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: 0.2px;
  line-height: 1.3;
}

.pk-hero-inner .text-caption {
  font-size: 0.8rem !important;
  font-weight: 400;
  letter-spacing: 0.2px;
}

/* ── Stats ── */
.pk-stat {
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease;
}

.pk-stat:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, .08) !important;
}

.pk-stat-icon {
  width: 44px; height: 44px;
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
}

.pk-stat-icon--total {
  background: linear-gradient(135deg, #0d47a1, #42A5F5);
}

.pk-stat-icon--received {
  background: linear-gradient(135deg, #004D40, #26A69A);
}

.pk-stat-icon--production {
  background: linear-gradient(135deg, #4A148C, #AB47BC);
}

.pk-stat-lbl {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  opacity: 0.45;
  line-height: 1.2;
  margin-bottom: 2px;
}

.pk-stat-val {
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.3px;
  line-height: 1.2;
}

/* ── Lanes ── */
.pk-lane {
  overflow: hidden;
  border: 1px solid rgba(var(--v-border-color), .06);
}

.pk-lane-head {
  padding: 16px 20px;
  display: flex;
  align-items: center;
  color: #fff;
  font-weight: 700;
}

.pk-lane-head--received {
  background: linear-gradient(135deg, #004D40 0%, #00796B 40%, #26A69A 100%);
}

.pk-lane-head--production {
  background: linear-gradient(135deg, #4A148C 0%, #6A1B9A 40%, #AB47BC 100%);
}

.pk-lane-title {
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.3px;
}

.pk-lane-body {
  padding: 14px;
  min-height: 200px;
  max-height: 65vh;
  overflow-y: auto;
}

/* Custom scrollbar for lane body */
.pk-lane-body::-webkit-scrollbar {
  width: 5px;
}

.pk-lane-body::-webkit-scrollbar-track {
  background: transparent;
}

.pk-lane-body::-webkit-scrollbar-thumb {
  background: rgba(var(--v-border-color), .15);
  border-radius: 10px;
}

.pk-lane-body::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-border-color), .3);
}

.pk-lane-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 180px;
  gap: 6px;
}

.pk-lane-empty .text-caption {
  font-size: 0.78rem !important;
  font-weight: 500;
}

/* ── Cards ── */
.pk-card {
  padding: 14px 16px;
  border-radius: 14px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(var(--v-border-color), .08);
  position: relative;
}

.pk-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, .07);
  border-color: rgba(var(--v-border-color), .12);
}

.pk-card:active {
  transform: translateY(-1px);
}

.pk-card--received {
  border-left: 3.5px solid #26A69A;
}

.pk-card--production {
  border-left: 3.5px solid #AB47BC;
}

/* Card chip typography */
.pk-card :deep(.v-chip__content) {
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.2px;
}

/* Supplier text in cards */
.pk-card .text-caption {
  font-size: 0.75rem !important;
  font-weight: 500;
  line-height: 1.4;
}

/* Date picker row typography */
.pk-page :deep(.v-chip__content) {
  font-family: 'Inter', system-ui, sans-serif;
  font-weight: 600;
}

/* Refresh button */
.pk-page :deep(.v-btn .v-btn__content) {
  font-family: 'Inter', system-ui, sans-serif;
  font-weight: 600;
  letter-spacing: 0.2px;
}
</style>
