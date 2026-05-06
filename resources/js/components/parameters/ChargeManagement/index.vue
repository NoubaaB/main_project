<template>
<v-card class="cm-card" elevation="0">
  <!-- Hero Header -->
  <div class="cm-hero"><div class="cm-hero-inner">
    <div class="d-flex align-center ga-4"><div class="cm-hero-icon"><v-icon size="28" color="white">mdi-truck-cargo-container</v-icon></div><div>
      <div class="cm-hero-title">{{ $t('charge_management') }}</div>
      <div class="text-white text-caption" style="opacity:.7">{{ $t('charge_management_desc') }}</div>
    </div></div>
  </div></div>
  <!-- Tab Slider -->
  <v-slide-group v-model="activeTab" mandatory show-arrows class="cm-tab-slider mx-4 mt-3">
    <v-slide-group-item v-for="tab in tabs" :key="tab.value" v-slot="{ isSelected, toggle }">
      <v-btn :color="isSelected ? '#1565C0' : undefined" :variant="isSelected ? 'flat' : 'text'" rounded class="mx-1" @click="toggle" :prepend-icon="tab.icon">{{ $t(tab.label) }}</v-btn>
    </v-slide-group-item>
  </v-slide-group>
  <v-divider class="mx-4 mt-2" color="#42A5F5"></v-divider>
  <!-- TAB: Charge Categories -->
  <div v-show="activeTab === 0" class="pa-0">
    <ChargeCategoryManagement :embedded="true" />
  </div>
  <!-- TAB: Detail Charge Categories -->
  <div v-show="activeTab === 1" class="pa-0">
    <DetailChargeCategoryManagement :embedded="true" />
  </div>
</v-card>
</template>

<script>
import ChargeCategoryManagement from '@/components/parameters/ChargeCategoryManagement/index.vue';
import DetailChargeCategoryManagement from '@/components/parameters/DetailChargeCategoryManagement/index.vue';

export default {
  name: 'ChargeManagement',
  components: { ChargeCategoryManagement, DetailChargeCategoryManagement },
  data() {
    return {
      activeTab: 0,
      tabs: [
        { value: 0, label: 'charge_categories', icon: 'mdi-shape-outline' },
        { value: 1, label: 'detail_charge_categories', icon: 'mdi-format-list-checks' },
      ],
    };
  },
};
</script>

<style scoped>
.cm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.cm-hero { background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.cm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.cm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.cm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.cm-tab-slider { border-radius: 12px; }
</style>
